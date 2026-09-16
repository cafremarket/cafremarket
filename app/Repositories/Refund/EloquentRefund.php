<?php

namespace App\Repositories\Refund;

use App\Models\Order;
use App\Models\Refund;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Support\Facades\Auth;

class EloquentRefund extends EloquentRepository implements BaseRepository, RefundRepository
{
    protected $model;

    public function __construct(Refund $refund)
    {
        $this->model = $refund;
    }

    protected function baseQuery()
    {
        $query = $this->model->with(['order.customer', 'shop']);

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query;
    }

    public function all()
    {
        return $this->baseQuery()->latest()->get();
    }

    public function open()
    {
        return $this->baseQuery()->pending()->latest()->get();
    }

    public function closed()
    {
        return $this->baseQuery()->closed()->latest()->get();
    }

    public function pending()
    {
        return $this->baseQuery()->pending()->latest()->get();
    }

    public function completed()
    {
        return $this->baseQuery()->completed()->latest()->get();
    }

    public function issue()
    {
        return $this->baseQuery()->issue()->latest()->get();
    }

    public function forTab(string $tab, ?string $search = null)
    {
        $query = $this->baseQuery()->search($search);

        $query = match ($tab) {
            'completed' => $query->completed(),
            'issue' => $query->issue(),
            default => $query->pending(),
        };

        return $query->latest()->get();
    }

    public function pendingCount(): int
    {
        $query = $this->model->pending();

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query->count();
    }

    public function statusOf($status)
    {
        return $this->baseQuery()->statusOf($status)->latest()->get();
    }

    public function approve($refund)
    {
        if (! $refund instanceof Refund) {
            $refund = $this->getInst($refund);
        }

        $refund->update(['status' => Refund::STATUS_APPROVED]);

        return $refund;
    }

    public function decline($refund, ?string $adminNote = null)
    {
        if (! $refund instanceof Refund) {
            $refund = $this->getInst($refund);
        }

        $payload = ['status' => Refund::STATUS_DECLINED];
        if ($adminNote !== null && $adminNote !== '') {
            $payload['admin_note'] = $adminNote;
        }

        $refund->update($payload);

        return $refund;
    }

    public function markIssue($refund, string $adminNote)
    {
        if (! $refund instanceof Refund) {
            $refund = $this->getInst($refund);
        }

        $refund->update([
            'status' => Refund::STATUS_FAILED,
            'admin_note' => $adminNote,
            'failure_reason' => $adminNote,
        ]);

        return $refund;
    }

    private function getInst($refund)
    {
        return Refund::findOrFail($refund);
    }

    public function findOrder($order)
    {
        return Order::findOrFail($order);
    }
}
