<?php

namespace Incevio\Package\Affiliate\Models;

use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Incevio\Package\Affiliate\Models\AffiliateCommission;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $table = 'affiliate_links';

    protected $fillable = [
        'affiliate_id',
        'inventory_id',
        'commission_id',
        'slug',
        'visitor_count',
        'order_count',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * Get the URL for the affiliate link.
     *
     * @return string The URL for the affiliate link.
     */
    public function getFullUrlAttribute()
    {
        return route('affiliate.link', [
            'affiliate' => $this->affiliate->username ,
            'slug' => $this->slug,
        ]);
    }

    /**
     * Create a commission for the affiliate link.
     *
     * @param int $order_id The ID of the order.
     * @param object $item The item object containing information about the product.
     * @return \Incevio\Package\Affiliate\Models\AffiliateCommission The created affiliate commission.
     */
    public function createCommission($order_id, $item)
    {
        $total_commission = $item->pivot->unit_price * $item->pivot->quantity * ($item->affiliates_percentage / 100);

        return AffiliateCommission::create([
            'affiliate_id' => $this->affiliate_id,
            'affiliate_link_id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'order_id' => $order_id,
            'commission_rate' => $item->affiliates_percentage,
            'total_commission' => $total_commission,
        ]);
    }

    /**
     * Scope a query to only include records from the users shop.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMine($query)
    {
        return $query->where('affiliate_id', Auth::guard('affiliate')->user()->id);
    }
}
