<?php

namespace App\Http\Controllers\Admin\Report;

use App\Services\Reports\MarketplaceReportService;
use Illuminate\Http\Request;

/**
 * Platform-wide reports: overview, commission, vendors, customers, refunds.
 */
class MarketplaceReportController extends BaseReportController
{
    public function overview(Request $request)
    {
        $report = $this->reportFor($request);
        $previous = $report->period()->previous();

        $sales = $report->salesSummary();
        $salesBefore = $report->salesSummary($previous);
        $commission = $report->commissionSummary();
        $commissionBefore = $report->commissionSummary($previous);

        $changes = [
            'gross_sales' => MarketplaceReportService::change($sales['gross_sales'], $salesBefore['gross_sales']),
            'paid_orders' => MarketplaceReportService::change($sales['paid_orders'], $salesBefore['paid_orders']),
            'average_order_value' => MarketplaceReportService::change($sales['average_order_value'], $salesBefore['average_order_value']),
            'commission' => MarketplaceReportService::change($commission['total'], $commissionBefore['total']),
            'buyers' => MarketplaceReportService::change($sales['buyers'], $salesBefore['buyers']),
            'refunded' => MarketplaceReportService::change($sales['refunded'], $salesBefore['refunded']),
        ];

        return view('admin.report.platform.overview', [
            'report' => $report,
            'shops' => $this->shopOptions(),
            'sales' => $sales,
            'commission' => $commission,
            'changes' => $changes,
            'revenue' => $report->platformRevenue(),
            'payouts' => $report->payoutSummary(),
            'growth' => $report->growth(),
            'trend' => $report->trend(),
            'statuses' => $report->orderStatusBreakdown(),
            'paymentMethods' => $report->paymentMethodBreakdown(),
            'topShops' => $report->commissionByShop()->sortByDesc('gross')->take(10)->values(),
            'topProducts' => $report->topProducts(10),
            'topCategories' => $report->topCategories(8),
            'topCustomers' => $report->customers()->take(10),
        ]);
    }

    public function commission(Request $request)
    {
        $report = $this->reportFor($request);

        return view('admin.report.platform.commission', [
            'report' => $report,
            'shops' => $this->shopOptions(),
            'summary' => $report->commissionSummary(),
            'revenue' => $report->platformRevenue(),
            'trend' => $report->trend(),
            'byShop' => $report->commissionByShop(),
            'ledger' => $report->commissionLedger(),
        ]);
    }

    public function vendors(Request $request)
    {
        $report = $this->reportFor($request);
        $vendors = $report->vendorPerformance();

        return view('admin.report.platform.vendors', [
            'report' => $report,
            'shops' => $this->shopOptions(),
            'vendors' => $vendors,
            'payouts' => $report->payoutSummary(),
            'growth' => $report->growth(),
            'summary' => [
                'shops' => $vendors->count(),
                'selling' => $vendors->where('paid_orders', '>', 0)->count(),
                'idle' => $vendors->where('active', true)->where('placed_orders', 0)->count(),
                'gross' => round((float) $vendors->sum('gross'), 2),
                'commission' => round((float) $vendors->sum('commission'), 2),
            ],
        ]);
    }

    public function customers(Request $request)
    {
        $report = $this->reportFor($request);

        return view('admin.report.platform.customers', [
            'report' => $report,
            'shops' => $this->shopOptions(),
            'summary' => $report->customerSummary(),
            'customers' => $report->customers(),
        ]);
    }

    public function refunds(Request $request)
    {
        $report = $this->reportFor($request);
        $totals = $report->refundTotals();
        $gross = $report->salesSummary()['gross_sales'];

        return view('admin.report.platform.refunds', [
            'report' => $report,
            'shops' => $this->shopOptions(),
            'totals' => $totals,
            'refundRate' => $gross > 0 ? round(($totals['approved_amount'] / $gross) * 100, 1) : 0.0,
            'refunds' => $report->refunds(),
        ]);
    }

    public function export(Request $request, string $type)
    {
        $report = $this->reportFor($request);

        return match ($type) {
            'commission' => $this->csv('commission_orders', $report, $this->ledgerColumns(), $report->commissionLedger()),
            'commission-shops' => $this->csv('commission_by_shop', $report, [
                'shop' => trans('reports.col.shop'),
                'plan' => trans('reports.col.plan'),
                'orders' => trans('reports.col.orders'),
                'gross' => trans('reports.col.gross'),
                'settled' => trans('reports.col.settled'),
                'awaiting' => trans('reports.col.awaiting'),
                'commission' => trans('reports.col.commission'),
                'rate' => trans('reports.col.rate'),
                'affiliate' => trans('reports.col.affiliate'),
                'net_vendor' => trans('reports.col.net_vendor'),
            ], $report->commissionByShop()),
            'vendors' => $this->csv('vendors', $report, [
                'shop' => trans('reports.col.shop'),
                'plan' => trans('reports.col.plan'),
                'active' => trans('reports.col.active'),
                'placed_orders' => trans('reports.col.placed_orders'),
                'paid_orders' => trans('reports.col.paid_orders'),
                'units' => trans('reports.col.units'),
                'gross' => trans('reports.col.gross'),
                'average_order_value' => trans('reports.col.aov'),
                'commission' => trans('reports.col.commission'),
                'net_vendor' => trans('reports.col.net_vendor'),
                'refunded' => trans('reports.col.refunded'),
                'refund_rate' => trans('reports.col.refund_rate'),
                'earnings' => trans('reports.col.earnings'),
                'cancel_rate' => trans('reports.col.cancel_rate'),
                'paid_out' => trans('reports.col.paid_out'),
                'wallet_balance' => trans('reports.col.wallet_balance'),
                'last_order_at' => trans('reports.col.last_order'),
            ], $report->vendorPerformance()),
            'customers' => $this->csv('customers', $report, [
                'name' => trans('reports.col.customer'),
                'email' => trans('reports.col.email'),
                'orders' => trans('reports.col.orders'),
                'spent' => trans('reports.col.spent'),
                'average_order_value' => trans('reports.col.aov'),
                'shops' => trans('reports.col.shops'),
                'is_new' => trans('reports.col.new_buyer'),
                'first_order_at' => trans('reports.col.first_order'),
                'last_order_at' => trans('reports.col.last_order'),
            ], $report->customers()),
            'refunds' => $this->csv('refunds', $report, [
                'date' => trans('reports.col.date'),
                'order_number' => trans('reports.col.order'),
                'shop' => trans('reports.col.shop'),
                'customer' => trans('reports.col.customer'),
                'order_total' => trans('reports.col.order_total'),
                'amount' => trans('reports.col.amount'),
                'status_label' => trans('reports.col.status'),
                'return_goods' => trans('reports.col.return_goods'),
                'reason' => trans('reports.col.reason'),
            ], $report->refunds()),
            'products' => $this->csv('top_products', $report, $this->productColumns(), $report->topProducts(1000)),
            default => abort(404),
        };
    }
}
