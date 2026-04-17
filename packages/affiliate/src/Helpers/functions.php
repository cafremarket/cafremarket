<?php

use Illuminate\Support\Facades\Auth;
use \Incevio\Package\Affiliate\Models\AffiliateLink;

if (!function_exists('affiliate_link_exists')) {
    /**
     * Check if an affiliate link exists for a given inventory ID.
     *
     * @param int $inventory_id The ID of the inventory.
     * @return bool Returns true if an affiliate link exists, false otherwise.
     */
    function affiliate_link_exists($inventory_id)
    {
        return AffiliateLink::where('affiliate_id', Auth::guard('affiliate')->id())
            ->where('inventory_id', $inventory_id)
            ->exists();
    }
}

if (!function_exists('current_affiliates_link_for_item')) {
    /**
     * Get the current affiliate link for the item.
     *
     * @param int $inventory_id The ID of the inventory.
     * @return \Incevio\Package\Affiliate\Models\AffiliateLink The affiliate link for the item.
     */
    function current_affiliates_link_for_item($inventory_id)
    {
        return AffiliateLink::where('affiliate_id', Auth::guard('affiliate')->id())
            ->where('inventory_id', $inventory_id)
            ->first();
    }
}
