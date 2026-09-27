<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Services\Reports\MarketplaceReportService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared filter parsing and CSV export for the report pages.
 */
abstract class BaseReportController extends Controller
{
    protected function reportFor(Request $request, ?int $shopId = null): MarketplaceReportService
    {
        if ($shopId === null) {
            $requested = (int) $request->get('shop_id');
            $shopId = $requested > 0 && DB::table('shops')->where('id', $requested)->exists() ? $requested : null;
        }

        return new MarketplaceReportService(ReportPeriod::fromRequest($request), $shopId);
    }

    /** Shops for the filter dropdown. */
    protected function shopOptions(): array
    {
        return DB::table('shops')->whereNull('deleted_at')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Stream rows as a CSV download (UTF-8 with BOM so Excel reads accents).
     *
     * @param  array<string, string>  $columns  row key => column heading
     * @param  iterable<int, array<string, mixed>>  $rows
     */
    protected function csv(string $name, MarketplaceReportService $report, array $columns, iterable $rows): StreamedResponse
    {
        $period = $report->period();
        $filename = sprintf('%s_%s_%s.csv', $name, $period->from->toDateString(), $period->to->toDateString());

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_values($columns));

            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($columns) as $key) {
                    $value = $row[$key] ?? '';
                    $line[] = is_bool($value) ? ($value ? trans('app.yes') : trans('app.no')) : $value;
                }
                fputcsv($out, $line);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** CSV columns for the per-order commission ledger. */
    protected function ledgerColumns(bool $withShop = true): array
    {
        return array_filter([
            'date' => trans('reports.col.date'),
            'order_number' => trans('reports.col.order'),
            'shop' => $withShop ? trans('reports.col.shop') : null,
            'customer' => trans('reports.col.customer'),
            'payment_method' => trans('reports.col.payment_method'),
            'order_status' => trans('reports.col.order_status'),
            'gross' => trans('reports.col.gross'),
            'commission' => trans('reports.col.commission'),
            'rate' => trans('reports.col.rate'),
            'affiliate' => trans('reports.col.affiliate'),
            'customer_fee' => trans('reports.col.customer_fee'),
            'net_vendor' => trans('reports.col.net_vendor'),
            'status_label' => trans('reports.col.settlement'),
            'settled_at' => trans('reports.col.settled_at'),
        ]);
    }

    protected function productColumns(): array
    {
        return [
            'name' => trans('reports.col.product'),
            'units' => trans('reports.col.units'),
            'orders' => trans('reports.col.orders'),
            'revenue' => trans('reports.col.revenue'),
        ];
    }
}
