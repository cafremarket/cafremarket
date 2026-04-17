<?php

namespace Incevio\Package\Wallet\Models;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Incevio\Package\Wallet\Exceptions\WalletOwnerInvalid;

class CreditReward extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'wallet_credit_rewards';

    /**
     * @var array
     */
    protected $fillable = [
        'shop_id',
        'customer_id',
        'order_id',
        'details',
        'amount',
        'fee',
        'released',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'released' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class)->withDefault([
            'name' => trans('app.shop_deleted'),
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault([
            'name' => trans('app.guest_customer'),
        ]);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withDefault([
            'order_number' => trans('app.order_deleted'),
        ]);
    }

    /**
     * Check if the transection is released.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Return status barge as html
     *
     * @return string
     */
    public function getStatusBadgeAttribute()
    {
        if ($this->released) {
            return '<span class="label label-primary">'.trans('packages.wallet.released').'</span>';
        }

        return '<span class="label label-outline">'.trans('packages.wallet.pending').'</span>';
    }

    /**
     * Return status barge as html
     *
     * @return string
     */
    public function getDetailListAttribute()
    {
        return is_serialized($this->details) ? unserialize($this->details) : $this->details;
    }

    /**
     * Scope a query to only include released records.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReleased($query)
    {
        return $query->where('released', true);
    }

    /**
     * Scope a query to only include released records.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereNull('released');
    }

    /**
     * Scope a query to only include released records.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReleasable($query)
    {
        return $query->whereNull('released')
            ->where('created_at', '<=', now()->subDays(get_reward_credit_holding_duration()));
    }

    /**
     * Release the credit
     *
     * @return null|transection
     */
    public function release()
    {
        if (! $this->customer) {
            throw new WalletOwnerInvalid(trans('packages.wallet.owner_invalid'));
        }

        $this->released = true;
        $this->save();

        $deposit = $this->customer->deposit($this->amount, [
            'type' => trans('packages.wallet.rewards'),
            'description' => trans('packages.wallet.credit_back_for_order', ['order' => $this->order->order_number]),
            'fee' => 0,
            'order_id' => $this->order_id,
        ], true);

        $this->shop->total_reward_given += $this->amount;
        $this->shop->save();

        return $deposit;
    }
}
