<?php

namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Merchant view of their own sales: gross, marketplace commission deducted,
 * and what reaches (or will reach) their wallet.
 */
class ShopEarningsReportController extends BaseReportController
{
    public function index(Request $request)
    {
        $report = $this->reportFor($request, $this->shopId());

        return view('admin.report.merchant.earnings', [
            'report' => $report,
            'sales' => $report->salesSummary(),
            'summary' => $report->commissionSummary(),
            'payouts' => $report->payoutSummary(),
            'trend' => $report->trend(),
            'ledger' => $report->commissionLedger(),
            'topProducts' => $report->topProducts(10),
        ]);
    }

    public function export(Request $request, string $type)
    {
        $report = $this->reportFor($request, $this->shopId());

        return match ($type) {
            'earnings' => $this->csv('earnings', $report, $this->ledgerColumns(false), $report->commissionLedger()),
            'products' => $this->csv('top_products', $report, $this->productColumns(), $report->topProducts(1000)),
            default => abort(404),
        };
    }

    private function shopId(): int
    {
        $shopId = (int) Auth::user()->merchantId();

        abort_if($shopId <= 0, 403);

        return $shopId;
    }
}
