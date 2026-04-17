<?php

namespace Incevio\Package\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\Inventory;
use Exception;

class AffiliateCommission extends Model
{
    protected $table = 'affiliate_commissions';

    protected $fillable = [
        'affiliate_id',
        'affiliate_link_id',
        'inventory_id',
        'order_id',
        'paid',
        'commission_rate',
        'total_commission',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function affiliateLink()
    {
        return $this->belongsTo(AffiliateLink::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function isPaid()
    {
        return $this->paid;
    }

    public function markAsPaid()
    {
        if ($this->isPaid()) {
            return $this;
        }

        try {
            $this->paid = true;
            
            $this->affiliate->wallet->deposit($this->total_commission, [
                'type' => 'affiliate_commission',
                'description' => trans('packages.affiliate.commission_for_order', ['order' => $this->order->order_number]),
                'fee' => 0,
                'commission_id' => $this->id,
                'order_id' => $this->order->id,
            ], true);

            $this->save();

            return $this;
        } catch (Exception $e) {
            \Log::error('Commission couldn\'t be paid. '. $e);

            return $this;
        }
    }

    public function getQuantityAttribute()
    {
        return $this->order->items()->where('inventory_id', $this->inventory_id)->value('quantity');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('paid', false);
    }

    public function scopePaid($query)
    {
        return $query->where('paid', true);
    }
}
