<?php

namespace App\Services\Dispute;

use App\Events\Dispute\DisputeClosed;
use App\Events\Dispute\DisputeCloseRequested;
use App\Events\Dispute\DisputeCreated;
use App\Events\Dispute\DisputeSolved;
use App\Events\Dispute\DisputeUpdated;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisputeTicketService
{
    public function createFromOrder(Order $order, array $data, string $raisedBy, $attachments = null): Dispute
    {
        if ($order->dispute) {
            throw ValidationException::withMessages([
                'order' => trans('messages.dispute_ticket_exists'),
            ]);
        }

        return DB::transaction(function () use ($order, $data, $raisedBy, $attachments) {
            $dispute = $order->dispute()->create([
                'shop_id' => $order->shop_id,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'dispute_type_id' => $data['dispute_type_id'] ?? null,
                'product_id' => is_numeric($data['product_id'] ?? null) ? $data['product_id'] : null,
                'description' => $data['description'] ?? null,
                'order_received' => $data['order_received'] ?? 1,
                'return_goods' => $data['return_goods'] ?? null,
                'refund_amount' => $data['refund_amount'] ?? null,
                'raised_by' => $raisedBy,
                'status' => Dispute::STATUS_NEW,
            ]);

            if ($attachments) {
                $dispute->saveAttachments($attachments);
            }

            event(new DisputeCreated($dispute));

            return $dispute->fresh(['dispute_type', 'order', 'customer', 'shop', 'attachments']);
        });
    }

    public function reply(Dispute $dispute, Request $request, $actor): Reply
    {
        $this->assertNotClosed($dispute);

        $isAdmin = $this->isAdmin($actor);
        $requestedStatus = $this->requestedStatus($request);

        if ($requestedStatus !== null) {
            if (! $isAdmin && in_array($requestedStatus, [Dispute::STATUS_CLOSED, Dispute::STATUS_CLOSE_REQUESTED], true)) {
                throw ValidationException::withMessages([
                    'status' => trans('messages.dispute_only_admin_can_close'),
                ]);
            }

            if ($isAdmin && $requestedStatus === Dispute::STATUS_CLOSED) {
                $this->close($dispute, $actor, false);
            } elseif ($requestedStatus === Dispute::STATUS_SOLVED) {
                $this->markResolved($dispute, $this->roleOf($actor), false);
            } else {
                $dispute->status = $requestedStatus;
                $dispute->save();
            }
        } elseif ((int) $dispute->status === Dispute::STATUS_NEW) {
            $dispute->status = Dispute::STATUS_OPEN;
            $dispute->save();
        }

        $reply = $dispute->replies()->create($this->replyPayload($request, $actor));

        if ($request->hasFile('attachments')) {
            $reply->saveAttachments($request->file('attachments'));
        }

        if ($request->boolean('solved') && ! $dispute->isResolved()) {
            $this->markResolved($dispute->fresh(), $this->roleOf($actor), false);
        }

        event(new DisputeUpdated($reply));

        return $reply;
    }

    public function markResolved(Dispute $dispute, string $by, bool $fireEvent = true): Dispute
    {
        $this->assertNotClosed($dispute);

        $dispute->status = Dispute::STATUS_SOLVED;
        $dispute->resolved_by = $by;
        $dispute->resolved_at = $dispute->resolved_at ?: now();
        $dispute->save();

        if ($fireEvent) {
            event(new DisputeSolved($dispute));
        }

        return $dispute;
    }

    public function requestClose(Dispute $dispute, string $by): Dispute
    {
        $this->assertNotClosed($dispute);

        if (! $dispute->isResolved()) {
            throw ValidationException::withMessages([
                'dispute' => trans('messages.dispute_mark_resolved_first'),
            ]);
        }

        $dispute->status = Dispute::STATUS_CLOSE_REQUESTED;
        $dispute->close_requested_by = $by;
        $dispute->close_requested_at = now();
        $dispute->save();

        event(new DisputeCloseRequested($dispute));

        return $dispute;
    }

    public function close(Dispute $dispute, User $admin, bool $fireEvent = true): Dispute
    {
        if (! $admin->isFromPlatform()) {
            throw ValidationException::withMessages([
                'dispute' => trans('messages.dispute_only_admin_can_close'),
            ]);
        }

        if ($dispute->isClosed()) {
            return $dispute;
        }

        $dispute->status = Dispute::STATUS_CLOSED;
        $dispute->closed_by = $admin->id;
        $dispute->closed_at = now();
        $dispute->save();

        if ($fireEvent) {
            event(new DisputeClosed($dispute));
        }

        return $dispute;
    }

    public function roleOf($actor): string
    {
        if ($actor instanceof Customer) {
            return Dispute::RAISED_BY_CUSTOMER;
        }

        if ($this->isAdmin($actor)) {
            return Dispute::RAISED_BY_ADMIN;
        }

        return Dispute::RAISED_BY_VENDOR;
    }

    public function isAdmin($actor): bool
    {
        return $actor instanceof User && method_exists($actor, 'isFromPlatform') && $actor->isFromPlatform();
    }

    protected function requestedStatus(Request $request): ?int
    {
        $status = $request->input('status', $request->input('status_id'));

        if ($status === null || $status === '') {
            return null;
        }

        return (int) $status;
    }

    protected function replyPayload(Request $request, $actor): array
    {
        $payload = [
            'reply' => $request->input('reply'),
        ];

        if ($actor instanceof Customer) {
            $payload['customer_id'] = $actor->id;
        } elseif ($actor instanceof User) {
            $payload['user_id'] = $actor->id;
        }

        return $payload;
    }

    protected function assertNotClosed(Dispute $dispute): void
    {
        if ($dispute->isClosed()) {
            throw ValidationException::withMessages([
                'dispute' => trans('messages.dispute_ticket_closed'),
            ]);
        }
    }
}
