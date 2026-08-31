<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Helpers\Statistics;
use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ResolvesVendorShop;

    public function index(Request $request)
    {
        $days = (int) ($request->get('days') ?? 30);
        $days = max(1, min($days, 365));
        $decimal = config('system_settings.decimals', 2);
        $currency = config('system_settings.currency.id');
        $shopId = $this->merchantShopId();

        $since = now()->subDays($days);

        $salesTotal = Order::mine()
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->where('created_at', '>=', $since)
            ->sum('grand_total');

        $ordersCount = Order::mine()->where('created_at', '>=', $since)->count();
        $productsCount = Inventory::mine()->count();
        $stockOutCount = Statistics::stock_out_count();
        $supplierCount = Supplier::mine()->count();

        return response()->json([
            'data' => [
                'period_days' => $days,
                'sales' => [
                    'total' => get_formated_currency($salesTotal, $decimal, $currency),
                    'total_raw' => round($salesTotal, $decimal),
                    'orders_count' => $ordersCount,
                    'todays_amount' => get_formated_currency(Statistics::todays_sale_amount(), $decimal, $currency),
                    'yesterdays_amount' => get_formated_currency(Statistics::yesterdays_sale_amount(), $decimal, $currency),
                ],
                'products' => [
                    'total' => $productsCount,
                    'stock_out' => $stockOutCount,
                    'top_selling_count' => Statistics::shop_inventories_count(),
                ],
                'inventory' => [
                    'total' => $productsCount,
                    'stock_out' => $stockOutCount,
                    'alert_quantity' => optional($this->shop()->config)->alert_quantity,
                ],
                'orders' => [
                    'total' => $ordersCount,
                    'unfulfilled' => Statistics::unfulfilled_order_count(),
                    'fulfilled' => Order::mine()->fulfilled()->where('created_at', '>=', $since)->count(),
                    'abandoned_carts' => Statistics::abandoned_carts_count(),
                ],
                'suppliers' => [
                    'total' => $supplierCount,
                ],
                'financial' => [
                    'sales_total' => get_formated_currency($salesTotal, $decimal, $currency),
                    'refunds' => get_formated_currency(Statistics::latest_refund_total($days), $decimal, $currency),
                ],
                'affiliate' => [
                    'enabled' => is_incevio_package_loaded('affiliate'),
                    'default_commission' => optional($this->shop()->config)->default_affiliate_commission_percentage,
                ],
            ],
        ]);
    }

    public function salesChart(Request $request)
    {
        $days = (int) ($request->get('days') ?? 30);
        $days = max(7, min($days, 90));
        $since = now()->subDays($days)->startOfDay();

        $rows = Order::mine()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(grand_total) as total'))
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
