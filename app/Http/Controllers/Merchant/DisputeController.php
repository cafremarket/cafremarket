<?php

namespace App\Http\Controllers\Merchant;

use App\Events\Dispute\DisputeCreated;
use App\Events\Dispute\DisputeUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDisputeRequest;
use App\Http\Requests\Validations\ResponseDisputeRequest;
use App\Models\Dispute;
use App\Models\DisputeType;
use App\Models\Order;
use App\Models\System;
use App\Notifications\SuperAdmin\DisputeAppealed as DisputeAppealedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Merchant dispute tickets — ticket workflow (not chat).
 * Vendor can raise a ticket on an order; Admin manages resolution.
 */
class DisputeController extends Controller
{
    public function index()
    {
        $shopId = Auth::user()->merchantId();

        $open = Dispute::with('dispute_type', 'order', 'customer')
            ->withCount('replies')
            ->where('shop_id', $shopId)
            ->open()
            ->orderByDesc('updated_at')
            ->get();

        $closed = Dispute::with('dispute_type', 'order', 'customer')
            ->withCount('replies')
            ->where('shop_id', $shopId)
            ->closed()
            ->orderByDesc('updated_at')
            ->get();

        return view('merchant.dispute.index', compact('open', 'closed'));
    }

    public function show(Dispute $dispute)
    {
        $this->authorizeShop($dispute);

        $dispute->load([
            'dispute_type',
            'order.inventories',
            'customer',
            'replies.attachments',
            'replies.user',
            'replies.customer',
            'attachments',
        ]);

        $statuses = \App\Helpers\ListHelper::dispute_statuses();

        return view('merchant.dispute.show', compact('dispute', 'statuses'));
    }

    public function create(Request $request)
    {
        $shopId = Auth::user()->merchantId();

        $orders = Order::mine()
            ->with('customer')
            ->whereDoesntHave('dispute')
            ->latest()
            ->limit(100)
            ->get(['id', 'order_number', 'customer_id', 'grand_total', 'currency_id', 'exchange_rate', 'shop_id']);

        $types = DisputeType::orderBy('id')->pluck('detail', 'id');
        $selectedOrderId = $request->query('order_id');

        return view('merchant.dispute.create', compact('orders', 'types', 'selectedOrderId'));
    }

    public function store(CreateDisputeRequest $request, Order $order)
    {
        if ((int) $order->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }

        if ($order->dispute) {
            return redirect()
                ->route('merchant.support.dispute.show', $order->dispute)
                ->with('error', 'A dispute ticket already exists for this order.');
        }

        $payload = $request->all();
        $payload['raised_by'] = Dispute::RAISED_BY_VENDOR;
        $payload['status'] = Dispute::STATUS_NEW;

        $dispute = $order->dispute()->create($payload);

        if ($request->hasFile('attachments')) {
            $dispute->saveAttachments($request->file('attachments'));
        }

        event(new DisputeCreated($dispute));

        return redirect()
            ->route('merchant.support.dispute.show', $dispute)
            ->with('success', trans('theme.notify.dispute_created') ?? 'Dispute ticket created.');
    }

    public function response(ResponseDisputeRequest $request, Dispute $dispute)
    {
        $this->authorizeShop($dispute);

        $oldStatus = $dispute->status;
        $dispute->update($request->only(['status']));

        $reply = $dispute->replies()->create([
            'user_id' => Auth::id(),
            'reply' => $request->input('reply'),
        ]);

        if ($request->hasFile('attachments')) {
            $reply->saveAttachments($request->file('attachments'));
        }

        // If vendor sets appealed, escalate to admin ticket queue.
        if ((int) $dispute->status === Dispute::STATUS_APPEALED && (int) $oldStatus !== Dispute::STATUS_APPEALED) {
            try {
                $system = System::orderBy('id', 'asc')->first();
                if ($system && $system->superAdmin()) {
                    safe_notify($system->superAdmin(), new DisputeAppealedNotification($reply), 'vendor dispute ticket appealed');
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        event(new DisputeUpdated($reply));

        return back()->with('success', trans('theme.notify.dispute_updated') ?? 'Ticket updated.');
    }

    protected function authorizeShop(Dispute $dispute): void
    {
        if ((int) $dispute->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }
    }
}
