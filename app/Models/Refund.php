<?php

namespace App\Models;

class Refund extends BaseModel
{
    const STATUS_NEW = 1;         // Pending (default)

    const STATUS_APPROVED = 2;    // Completed

    const STATUS_DECLINED = 3;    // Issue (declined)

    const STATUS_FAILED = 4;      // Issue (wallet/payment failure)

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'refunds';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id',
        'order_id',
        'order_fulfilled',
        'return_goods',
        'amount',
        'description',
        'admin_note',
        'failure_reason',
        'status',
    ];

    /**
     * Get the shop for the refund.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the order for the refund.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Set the order_fulfilled.
     */
    public function setOrderFulfilledAttribute($value)
    {
        $this->attributes['order_fulfilled'] = (bool) $value;
    }

    /**
     * Set the return_goods.
     */
    public function setReturnGoodsAttribute($value)
    {
        $this->attributes['return_goods'] = (bool) $value;
    }

    /**
     * Pending refunds awaiting action.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', static::STATUS_NEW);
    }

    /**
     * Completed (approved) refunds.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', static::STATUS_APPROVED);
    }

    /**
     * Issue refunds (declined or failed).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIssue($query)
    {
        return $query->whereIn('status', [static::STATUS_DECLINED, static::STATUS_FAILED]);
    }

    /**
     * Scope a query to only include open (pending) records.
     * Legacy alias for pending (status 1).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->pending();
    }

    /**
     * Scope a query to only include closed (non-pending) records.
     * Legacy: Approved/Completed + Declined/Failed (statuses 2, 3, 4).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            static::STATUS_APPROVED,
            static::STATUS_DECLINED,
            static::STATUS_FAILED,
        ]);
    }

    /**
     * Scope a query to only include records that have the given status.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatusOf($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Search by order number.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->whereHas('order', function ($q) use ($term) {
            $q->where('order_number', 'like', '%'.$term.'%');
        });
    }

    /**
     * Check if the refund is open / pending
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->status == static::STATUS_NEW;
    }

    /**
     * Check if the refund has been approved / completed
     *
     * @return bool
     */
    public function isApproved()
    {
        return $this->status == static::STATUS_APPROVED;
    }

    /**
     * Check if the refund has been declined
     *
     * @return bool
     */
    public function isDeclined()
    {
        return $this->status == static::STATUS_DECLINED;
    }

    /**
     * Check if the refund failed (wallet/payment)
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->status == static::STATUS_FAILED;
    }

    /**
     * Whether this refund is in the Issue bucket.
     *
     * @return bool
     */
    public function isIssue()
    {
        return $this->isDeclined() || $this->isFailed();
    }

    /**
     * Plain-text status label for API, email, and push.
     */
    public function statusLabel(): string
    {
        return match ((int) $this->status) {
            static::STATUS_NEW => trans('app.refund_status.pending'),
            static::STATUS_APPROVED => trans('app.refund_status.completed'),
            static::STATUS_DECLINED => trans('app.refund_status.issue'),
            static::STATUS_FAILED => trans('app.refund_status.issue_failed'),
            default => (string) $this->status,
        };
    }

    /**
     * Legacy plain-text label (New / Approved / Declined) for older clients.
     */
    public function legacyStatusLabel(): string
    {
        return match ((int) $this->status) {
            static::STATUS_NEW => trans('app.statuses.new'),
            static::STATUS_APPROVED => trans('app.statuses.approved'),
            static::STATUS_DECLINED => trans('app.statuses.declined'),
            static::STATUS_FAILED => trans('app.refund_status.issue_failed'),
            default => (string) $this->status,
        };
    }

    public function statusName()
    {
        switch ($this->status) {
            case static::STATUS_NEW:
                return '<span class="label label-outline">'.trans('app.refund_status.pending').'</span>';

            case static::STATUS_APPROVED:
                return '<span class="label label-primary">'.trans('app.refund_status.completed').'</span>';

            case static::STATUS_DECLINED:
                return '<span class="label label-danger">'.trans('app.refund_status.issue').'</span>';

            case static::STATUS_FAILED:
                return '<span class="label label-danger">'.trans('app.refund_status.issue_failed').'</span>';
        }

        return null;
    }
}
