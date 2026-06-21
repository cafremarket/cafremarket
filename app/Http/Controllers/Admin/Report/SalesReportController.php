<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Repositories\Report\SalesReportsRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    protected $reports;

    public function __construct(SalesReportsRepository $reports)
    {
        $this->reports = $reports;
    }

    public function orders(Request $request)
    {
        $fromDate = Carbon::today()->subDays(config('report.sales.default', 7));
        $toDate = Carbon::today()->endOfDay();

        $data = $this->reports->orders();
        $data = $this->appendPaymentLabels($data);
        $chartData = $this->reports->orderChart();
        $chartDataArray = json_decode(json_encode($chartData), true);
        $summary = $this->formatSummary($this->reports->ordersSummary($fromDate, $toDate));

        return view('admin.report.platform.sales.orders', compact('data', 'chartDataArray', 'summary'));
    }

    public function getMoreOrder(Request $request)
    {
        [$fromDate, $toDate, $status] = $this->parseOrderFilters($request);

        $salesReport = $this->appendPaymentLabels(
            $this->reports->orderSearch($fromDate, $toDate, $status)
        );

        return response()->json([
            'data' => $salesReport,
            'summary' => $this->formatSummary($this->reports->ordersSummary($fromDate, $toDate, $status)),
        ]);
    }

    public function getMoreForChart(Request $request)
    {
        [$fromDate, $toDate, $status] = $this->parseOrderFilters($request);

        $salesReport = $this->reports->orderChartSearch($fromDate, $toDate, $status);

        return response()->json(['data' => json_decode(json_encode($salesReport), true)]);
    }

    public function payments(Request $request)
    {
        $fromDate = Carbon::today()->subDays(config('report.sales.default', 7));
        $toDate = Carbon::today()->endOfDay();
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        $data = $this->appendPaymentLabels($this->reports->payments());
        $chartData = $this->reports->paymentChart();
        $paymentMethod = $this->reports->paymentChartByPaymentMethod();
        $paymentStatus = $this->reports->paymentChartByPaymentStatus();

        $chartDataArray = json_decode(json_encode($chartData), true);
        $paymentMethod = json_decode(json_encode($paymentMethod), true);
        $paymentStatus = json_decode(json_encode($paymentStatus), true);
        $summary = $this->formatSummary($this->reports->paymentsSummary($fromDate, $toDate));

        return view('admin.report.platform.sales.payments', compact(
            'data',
            'chartDataArray',
            'paymentMethods',
            'paymentMethod',
            'paymentStatus',
            'summary'
        ));
    }

    public function getMorePayments(Request $request)
    {
        [$fromDate, $toDate, $packet] = $this->parsePaymentFilters($request);

        $payments = $this->appendPaymentLabels(
            $this->reports->paymentSearch($fromDate, $toDate, $packet)
        );

        return response()->json([
            'data' => $payments,
            'summary' => $this->formatSummary($this->reports->paymentsSummary($fromDate, $toDate, $packet)),
        ]);
    }

    public function getMoreByMethod(Request $request)
    {
        [$fromDate, $toDate, $packet] = $this->parsePaymentFilters($request);

        return response()->json([
            'data' => $this->reports->getMoreByMethod($fromDate, $toDate, $packet),
        ]);
    }

    public function getMoreByStatus(Request $request)
    {
        [$fromDate, $toDate, $packet] = $this->parsePaymentFilters($request);

        return response()->json([
            'data' => $this->reports->getMoreByStatus($fromDate, $toDate, $packet),
        ]);
    }

    public function getMorePaymentForChart(Request $request)
    {
        [$fromDate, $toDate, $packet] = $this->parsePaymentFilters($request);

        $chartData = $this->reports->paymentChartSearch($fromDate, $toDate, $packet);

        return response()->json(['data' => json_decode(json_encode($chartData), true)]);
    }

    public function products(Request $request)
    {
        $fromDate = Carbon::today()->subDays(config('report.sales.default', 7));
        $toDate = Carbon::today()->endOfDay();

        $data = $this->formatProductRows($this->reports->products());
        $summary = $this->formatProductsSummary($this->reports->productsSummary($fromDate, $toDate));

        return view('admin.report.platform.sales.products', compact('data', 'summary'));
    }

    public function productsSearch(Request $request)
    {
        $fromDate = Carbon::createFromDate($request->get('fromDate'));
        $toDate = Carbon::createFromDate($request->get('toDate'))->endOfDay();
        $packet = [
            'product_id' => ($request->get('productId') !== 'null') ? $request->get('productId') : null,
            'shop_id' => ($request->get('shopId') !== 'null') ? $request->get('shopId') : null,
        ];

        $data = $this->formatProductRows($this->reports->productsSearch($fromDate, $toDate, $packet));

        return response()->json([
            'data' => $data,
            'summary' => $this->formatProductsSummary($this->reports->productsSummary($fromDate, $toDate, $packet)),
        ]);
    }

    private function parseOrderFilters(Request $request): array
    {
        $fromDate = Carbon::createFromDate($request->get('fromDate'));
        $toDate = Carbon::createFromDate($request->get('toDate'))->endOfDay();
        $status = [
            'payment_status' => $request->get('paymentStatus') ?: null,
            'order_status' => $request->get('orderStatus') ?: null,
            'customer_id' => ($request->get('customerId') !== 'null') ? $request->get('customerId') : null,
            'shop_id' => ($request->get('shopId') !== 'null') ? $request->get('shopId') : null,
            'order_number' => ($request->get('orderNumber') !== 'null') ? $request->get('orderNumber') : null,
        ];

        return [$fromDate, $toDate, $status];
    }

    private function parsePaymentFilters(Request $request): array
    {
        $fromDate = Carbon::createFromDate($request->get('fromDate'));
        $toDate = Carbon::createFromDate($request->get('toDate'))->endOfDay();
        $packet = [
            'payment_status' => $request->get('paymentStatus') ?: null,
            'payment_method' => $request->get('paymentMethod') ?: null,
            'customer_id' => ($request->get('customerId') !== 'null') ? $request->get('customerId') : null,
            'shop_id' => ($request->get('shopId') !== 'null') ? $request->get('shopId') : null,
            'order_number' => ($request->get('orderNumber') !== 'null') ? $request->get('orderNumber') : null,
        ];

        return [$fromDate, $toDate, $packet];
    }

    private function formatSummary($stats): array
    {
        $currencyId = config('system_settings.currency.id');

        return [
            'total_orders' => (int) ($stats->total_orders ?? 0),
            'total_revenue' => get_formated_currency($stats->total_revenue ?? 0, 2, $currencyId),
            'paid_orders' => (int) ($stats->paid_orders ?? 0),
            'paid_revenue' => get_formated_currency($stats->paid_revenue ?? 0, 2, $currencyId),
            'pending_revenue' => get_formated_currency($stats->pending_revenue ?? 0, 2, $currencyId),
        ];
    }

    private function formatProductsSummary($stats): array
    {
        $currencyId = config('system_settings.currency.id');

        return [
            'products_sold' => (int) ($stats->products_sold ?? 0),
            'units_sold' => (int) ($stats->units_sold ?? 0),
            'order_count' => (int) ($stats->order_count ?? 0),
            'revenue' => get_formated_currency($stats->revenue ?? 0, 2, $currencyId),
        ];
    }

    private function appendPaymentLabels($rows)
    {
        $currencyId = config('system_settings.currency.id');

        return collect($rows)->map(function ($row) use ($currencyId) {
            $row->payment_status_name = get_payment_status_name($row->payment_status ?? null);

            if (isset($row->grand_total)) {
                $row->grand_total = round((float) $row->grand_total, 2);
                $row->grand_total_formatted = get_formated_currency($row->grand_total, 2, $currencyId);
            }

            if (isset($row->total)) {
                $row->total = round((float) $row->total, 2);
                $row->total_formatted = get_formated_currency($row->total, 2, $currencyId);
            }

            if (isset($row->quantity)) {
                $row->quantity = (int) $row->quantity;
            }

            if (isset($row->item)) {
                $row->item = (int) $row->item;
            }

            return $row;
        });
    }

    private function formatProductRows($rows)
    {
        $currencyId = config('system_settings.currency.id');

        return collect($rows)->map(function ($row) use ($currencyId) {
            if (isset($row->quantity)) {
                $row->quantity = (int) $row->quantity;
            }

            if (isset($row->uniquePurchase)) {
                $row->uniquePurchase = (int) $row->uniquePurchase;
            }

            if (isset($row->avgPrice)) {
                $row->avgPrice = round((float) $row->avgPrice, 2);
                $row->avgPrice_formatted = get_formated_currency($row->avgPrice, 2, $currencyId);
            }

            if (isset($row->totalSale)) {
                $row->totalSale = round((float) $row->totalSale, 2);
                $row->totalSale_formatted = get_formated_currency($row->totalSale, 2, $currencyId);
            }

            return $row;
        });
    }
}
