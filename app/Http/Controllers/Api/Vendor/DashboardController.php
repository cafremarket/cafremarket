<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Helpers\ListHelper;
use App\Helpers\Statistics;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryLightResource;
use App\Http\Resources\OrderLightResource;
use App\Http\Resources\TopSellingItemResource;
use App\Http\Controllers\Api\Concerns\CachesApiResponses;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use CachesApiResponses;
    /**
     * get basic statistics for vendor dashboard
     */
    public function basicStatistics(Request $request)
    {
        $shopId = (int) Auth::user()->merchantId();
        $days = $request->get('latest_in_days') ?? config('charts.latest_sales.days');

        return $this->rememberApi($shopId.':basic:'.$days, function () use ($days) {
            $decimal = config('system_settings.decimals', 2);
            $currency = config('system_settings.currency.id');

            $last_sale = Statistics::last_sale();
            $last_sale_breakdown = Statistics::sale_breakdown_from_order($last_sale);
            $todays_sale_breakdown = Statistics::sale_breakdown_for_date(\Carbon\Carbon::today());
            $yesterdays_sale_breakdown = Statistics::sale_breakdown_for_date(\Carbon\Carbon::yesterday());

            return [
                'data' => [
                    'latest_order_count' => Statistics::latest_order_count($days),
                    'unfulfilled_order_count' => Statistics::unfulfilled_order_count(),
                    'todays_order_count' => Statistics::todays_order_count(),
                    'stock_count' => Statistics::shop_inventories_count(),
                    'stock_out_count' => Statistics::stock_out_count(),
                    'last_sale_amount' => get_formated_currency($last_sale->grand_total ?? 0, $decimal, $currency),
                    'todays_sale_amount' => get_formated_currency(Statistics::todays_sale_amount(), $decimal, $currency),
                    'yesterdays_sale_amount' => get_formated_currency(Statistics::yesterdays_sale_amount(), $decimal, $currency),
                    'latest_refund_amount' => get_formated_currency(Statistics::latest_refund_total($days), $decimal, $currency),
                    'last_sale_breakdown' => Statistics::format_sale_breakdown($last_sale_breakdown, $currency, $decimal),
                    'todays_sale_breakdown' => Statistics::format_sale_breakdown($todays_sale_breakdown, $currency, $decimal),
                    'yesterdays_sale_breakdown' => Statistics::format_sale_breakdown($yesterdays_sale_breakdown, $currency, $decimal),
                ],
            ];
        }, null, 'vendor_stats');
    }

    /**
     * get latest items for vendor dashboard
     */
    public function latestOrders(Request $request)
    {
        $shopId = (int) Auth::user()->merchantId();
        $limit = $request->get('limit');

        return $this->rememberApi($shopId.':orders:'.$limit, function () use ($limit) {
            $orders = ListHelper::latest_orders($limit);

            return OrderLightResource::collection($orders);
        }, 30, 'vendor_stats');
    }

    /**
     * get latest top selling items for vendor dashboard
     */
    public function topSellingItems(Request $request)
    {
        $shopId = (int) Auth::user()->merchantId();
        $limit = $request->get('limit');

        return $this->rememberApi($shopId.':top-selling:'.$limit, function () use ($limit) {
            $items = ListHelper::top_listing_items(null, $limit);

            return TopSellingItemResource::collection($items);
        }, null, 'vendor_stats');
    }

    /**
     * get out of stock items for vendor dashboard
     */
    public function outOfStocksItems(Request $request)
    {
        $shopId = (int) Auth::user()->merchantId();
        $limit = $request->get('limit');
        $limit = $limit < 5 ? 5 : ($limit > 100 ? 100 : $limit);

        return $this->rememberApi($shopId.':oos:'.$limit, function () use ($limit) {
            $inventories = Inventory::mine()->stockOut()
                ->with('image:path,imageable_id,imageable_type')
                ->latest()->limit($limit)->get();

            return InventoryLightResource::collection($inventories);
        }, null, 'vendor_stats');
    }
}
