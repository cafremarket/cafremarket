<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Helpers\Statistics;
use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\Reports\MarketplaceReportService;
use App\Services\Reports\ReportPeriod;
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

        $ordersCount = Order::mine()->visibleToVendor()->where('created_at', '>=', $since)->count();
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
                'commission' => $this->commissionSection($shopId, $days),
                'affiliate' => array_merge([
                    'enabled' => is_incevio_package_loaded('affiliate'),
                    'default_commission' => optional($this->shop()->config)->default_affiliate_commission_percentage,
                ], $this->affiliateSection($shopId)),
            ],
        ]);
    }

    /** Gross sales, marketplace commission and earnings for the shop over the last $days days. */
    private function commissionSection(?int $shopId, int $days): array
    {
        if (! $shopId) {
            return [];
        }

        $period = new ReportPeriod(now()->subDays($days - 1)->startOfDay(), now()->endOfDay());
        $report = new MarketplaceReportService($period, $shopId);
        $summary = $report->commissionSummary();
        $sales = $report->salesSummary();
        $refunded = $sales['refunded'];

        return [
            'gross_sales' => report_money($sales['gross_sales']),
            'commission_deducted' => report_money($summary['total']),
            'commission_rate' => report_percent($summary['effective_rate'], 2),
            'commission_collected' => report_money($summary['settled']),
            'commission_awaiting_delivery' => report_money($summary['awaiting']),
            'commission_returned_on_refunds' => report_money($summary['reversed']),
            'affiliate_commission' => report_money($summary['affiliate']),
            'refunded' => report_money($refunded),
            'your_earnings' => report_money($summary['net_vendor'] - $refunded),
        ];
    }

    /**
     * Affiliate commissions on this shop's orders: waiting for the refund/return
     * period to end, paid to affiliates, or cancelled (returned to the shop).
     */
    private function affiliateSection(?int $shopId): array
    {
        if (! $shopId || ! is_incevio_package_loaded('affiliate')
            || ! \Illuminate\Support\Facades\Schema::hasColumn('affiliate_commissions', 'voided_at')) {
            return [];
        }

        $base = DB::table('affiliate_commissions')
            ->join('orders', 'orders.id', '=', 'affiliate_commissions.order_id')
            ->where('orders.shop_id', $shopId);

        $pending = (clone $base)->where('affiliate_commissions.paid', false)->whereNull('affiliate_commissions.voided_at');
        $nextRelease = (clone $pending)->whereNotNull('affiliate_commissions.release_at')->min('affiliate_commissions.release_at');

        return [
            'affiliate_pending' => report_money((clone $pending)->sum('affiliate_commissions.total_commission'))
                .' ('.(clone $pending)->count().')',
            'affiliate_next_payout' => $nextRelease ? \Carbon\Carbon::parse($nextRelease)->format('d/m/Y') : '—',
            'affiliate_paid' => report_money((clone $base)->where('affiliate_commissions.paid', true)->sum('affiliate_commissions.total_commission')),
            'affiliate_cancelled' => report_money((clone $base)->whereNotNull('affiliate_commissions.voided_at')->sum('affiliate_commissions.total_commission'))
                .' ('.(clone $base)->whereNotNull('affiliate_commissions.voided_at')->count().')',
        ];
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
