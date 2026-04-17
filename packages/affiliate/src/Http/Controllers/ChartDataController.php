<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ChartDataController extends Controller
{
    /**
     * Get visitor count by link for the authenticated affiliate.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVisitorByLink()
    {
        $affiliate = Auth::guard('affiliate')->user();

        $affiliateLinks = $affiliate->affiliateLinks()->get();

        $visitor_counts = [];
        $slugs = [];
        foreach ($affiliateLinks as $link) {
            $slugs[] = $link->slug;
            $visitor_counts[] = $link->visitor_count;
        }

        return response()->json([
            'labels' => $slugs,
            'data' => $visitor_counts,
        ]);
    }

    /**
     * Retrieves commission data for each affiliate link.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCommissionByLink()
    {
        $affiliate = Auth::guard('affiliate')->user();

        $affiliateLinks = $affiliate->affiliateLinks()->get();

        $labels = [];
        $data = [];
        foreach ($affiliateLinks as $link) {
            $labels[] = $link->slug;
            $data[] = $link->commissions()->count();
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    /**
     * Retrieve commission data grouped by shop.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCommissionByShop()
    {
        $affiliate = Auth::guard('affiliate')->user();

        $affiliateCommissions = $affiliate->commissions()->get();
        
        $total_shop_commission = [];
        
        foreach ($affiliateCommissions as $commission) {
            $shop_name = $commission->order->shop->name;
            
            if (!isset($total_shop_commission[$shop_name])) {
                $total_shop_commission[$shop_name] = 0;
            }

            $total_shop_commission[$shop_name] += $commission->total_commission;
        }

        return response()->json([
            'labels' => array_keys($total_shop_commission),
            'data' => array_values($total_shop_commission),
        ]);
    }
}