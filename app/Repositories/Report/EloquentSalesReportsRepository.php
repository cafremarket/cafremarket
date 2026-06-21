<?php

namespace App\Repositories\Report;

use App\Models\Order;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use App\Repositories\Report\SalesReportsRepository as Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

class EloquentSalesReportsRepository extends EloquentRepository implements BaseRepository, Contract
{
    private $orders = 'orders';

    private $customers = 'customers';

    private $paymentMethod = 'payment_methods';

    private $shops = 'shops';

    private $products = 'products';

    private $orderItems = 'order_items';

    private $inventories = 'inventories';

    public function getStatusFromOrder($status)
    {
        $order = new ReflectionClass(\App\Models\Order::class);

        return ! empty($status) ? $order->getConstant($status) : 0;
    }

    public function getDaysFromDate($fromDate, $toDate)
    {
        return date_diff($fromDate, $toDate)->days;
    }

    /**
     * Date expression for chart grouping (daily vs monthly).
     */
    protected function dateGroupingExpression(int $days, ?string $table = null): \Illuminate\Database\Query\Expression
    {
        $table = $table ?? $this->orders;

        if ($days > 30) {
            return DB::raw('DATE_FORMAT('.$table.'.created_at, "%Y-%m") as date');
        }

        return DB::raw('DATE('.$table.'.created_at) as date');
    }

    protected function defaultSinceDate(): Carbon
    {
        return Carbon::today()->subDays(config('report.sales.default', 7));
    }

    /**
     * Orders all Data default show 7 days data:
     */
    public function orders($date = null)
    {
        return self::commonDataQuery()
            ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
            ->orderBy($this->orders.'.created_at', 'desc')
            ->get();
    }

    public function orderSearch(Carbon $fromDate, Carbon $toDate, array $packet)
    {
        $data = self::commonSearchQuery(self::commonDataQuery(), $packet);

        return $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])
            ->orderBy($this->orders.'.created_at', 'desc')
            ->get();
    }

    public function orderChart($date = null)
    {
        return self::commonChartQuery(7)
            ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
            ->get();
    }

    public function orderChartSearch(Carbon $fromDate, Carbon $toDate, array $packet)
    {
        $days = self::getDaysFromDate($fromDate, $toDate);
        $data = self::commonSearchQuery(self::commonChartQuery($days), $packet);

        return $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])
            ->orderBy('date')
            ->get();
    }

    public function ordersSummary(Carbon $fromDate, Carbon $toDate, array $packet = []): object
    {
        $query = self::commonSearchQuery(DB::table($this->orders), $packet);

        return $query->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])
            ->selectRaw('
                COUNT('.$this->orders.'.id) as total_orders,
                ROUND(COALESCE(SUM('.$this->orders.'.grand_total), 0), 2) as total_revenue,
                SUM(CASE WHEN '.$this->orders.'.payment_status >= ? THEN 1 ELSE 0 END) as paid_orders,
                ROUND(COALESCE(SUM(CASE WHEN '.$this->orders.'.payment_status >= ? THEN '.$this->orders.'.grand_total ELSE 0 END), 0), 2) as paid_revenue,
                ROUND(COALESCE(SUM(CASE WHEN '.$this->orders.'.payment_status IN (?, ?) THEN '.$this->orders.'.grand_total ELSE 0 END), 0), 2) as pending_revenue
            ', [
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_UNPAID,
                Order::PAYMENT_STATUS_PENDING,
            ])
            ->first();
    }

    public function commonDataQuery()
    {
        return DB::table($this->orders)
            ->leftJoin($this->customers, $this->customers.'.id', $this->orders.'.customer_id')
            ->leftJoin($this->shops, $this->shops.'.id', $this->orders.'.shop_id')
            ->leftJoin($this->paymentMethod, $this->paymentMethod.'.id', $this->orders.'.payment_method_id')
            ->select(
                $this->orders.'.id',
                $this->orders.'.order_number',
                DB::raw('DATE('.$this->orders.'.created_at) as date'),
                $this->orders.'.total',
                $this->shops.'.name as shop',
                $this->orders.'.quantity',
                $this->orders.'.grand_total',
                $this->orders.'.delivery_date',
                $this->orders.'.payment_status',
                $this->paymentMethod.'.name as payment_method',
                $this->customers.'.name as customer'
            );
    }

    public function commonChartQuery($days)
    {
        $dateExpr = $this->dateGroupingExpression((int) $days);

        return DB::table($this->orders)
            ->select(
                $dateExpr,
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_AWAITING_DELIVERY.' THEN 1 ELSE 0 END) as awaiting_delivery'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_WAITING_FOR_PAYMENT.' THEN 1 ELSE 0 END) as awaiting_payment'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_CANCELED.' THEN 1 ELSE 0 END) as canceled'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_CONFIRMED.' THEN 1 ELSE 0 END) as confirmed'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_DELIVERED.' THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_FULFILLED.' THEN 1 ELSE 0 END) as fulfilled'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_PAYMENT_ERROR.' THEN 1 ELSE 0 END) as payment_error'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_RETURNED.' THEN 1 ELSE 0 END) as returned'),
                DB::raw('SUM(CASE WHEN order_status_id = '.Order::STATUS_DISPUTED.' THEN 1 ELSE 0 END) as disputed')
            )
            ->groupBy('date')
            ->orderBy('date');
    }

    public function commonSearchQuery($data, array $packet)
    {
        $statusId = self::getStatusFromOrder($packet['order_status'] ?? null);
        $paymentStatus = self::getStatusFromOrder($packet['payment_status'] ?? null);

        if ($statusId !== 0) {
            $data = $data->where($this->orders.'.order_status_id', $statusId);
        }

        if ($paymentStatus !== 0) {
            $data = $data->where($this->orders.'.payment_status', $paymentStatus);
        }

        if (! empty($packet['customer_id'])) {
            $data = $data->where($this->orders.'.customer_id', (int) $packet['customer_id']);
        }

        if (! empty($packet['shop_id'])) {
            $data = $data->where($this->orders.'.shop_id', (int) $packet['shop_id']);
        }

        if (! empty($packet['order_number'])) {
            $data = $data->where($this->orders.'.order_number', $packet['order_number']);
        }

        if (! empty($packet['payment_method'])) {
            $data = $data->where($this->orders.'.payment_method_id', (int) $packet['payment_method']);
        }

        return $data;
    }

    /**
     ** Payments Report
     **/

    public function paymentChart($date = null)
    {
        return self::paymentChartCommonQuery(7)
            ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
            ->get();
    }

    public function paymentChartByPaymentMethod($date = null)
    {
        try {
            return self::commonQueryByPaymentMethods()
                ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
                ->get();
        } catch (\Exception $exception) {
            return collect();
        }
    }

    public function paymentChartByPaymentStatus($date = null)
    {
        try {
            $row = self::commonQueryByPaymentStatus()
                ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
                ->first();

            return collect([$row ?: (object) ['pending' => 0, 'paid' => 0, 'refunded' => 0]]);
        } catch (\Exception $exception) {
            return collect([(object) ['pending' => 0, 'paid' => 0, 'refunded' => 0]]);
        }
    }

    public function getMoreByMethod(Carbon $fromDate, Carbon $toDate, $packet)
    {
        $data = self::commonQueryByPaymentMethods();
        $data = self::commonSearchQuery($data, $packet);

        return $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])->get();
    }

    public function getMoreByStatus(Carbon $fromDate, Carbon $toDate, $packet)
    {
        $data = self::commonSearchQuery(DB::table($this->orders), $packet);

        return collect([
            $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])
                ->selectRaw('
                    ROUND(COALESCE(SUM(CASE WHEN payment_status IN (?, ?) THEN grand_total ELSE 0 END), 0), 2) AS pending,
                    ROUND(COALESCE(SUM(CASE WHEN payment_status IN (?, ?) THEN grand_total ELSE 0 END), 0), 2) AS refunded,
                    ROUND(COALESCE(SUM(CASE WHEN payment_status IN (?, ?) THEN grand_total ELSE 0 END), 0), 2) AS paid
                ', [
                    Order::PAYMENT_STATUS_UNPAID,
                    Order::PAYMENT_STATUS_PENDING,
                    Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
                    Order::PAYMENT_STATUS_REFUNDED,
                    Order::PAYMENT_STATUS_PAID,
                    Order::PAYMENT_STATUS_INITIATED_REFUND,
                ])
                ->first(),
        ]);
    }

    public function paymentChartSearch(Carbon $fromDate, Carbon $toDate, $packet)
    {
        $days = self::getDaysFromDate($fromDate, $toDate);
        $data = self::commonSearchQuery(self::paymentChartCommonQuery($days), $packet);

        return $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])
            ->orderBy('date')
            ->get();
    }

    public function paymentsSummary(Carbon $fromDate, Carbon $toDate, array $packet = []): object
    {
        return self::ordersSummary($fromDate, $toDate, $packet);
    }

    public function paymentChartCommonQuery($days)
    {
        $dateExpr = $this->dateGroupingExpression((int) $days);

        return DB::table($this->orders)
            ->select(
                $dateExpr,
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN payment_status IN ('.Order::PAYMENT_STATUS_UNPAID.', '.Order::PAYMENT_STATUS_PENDING.') THEN grand_total ELSE 0 END), 0), 2) AS pending'),
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN payment_status IN ('.Order::PAYMENT_STATUS_PARTIALLY_REFUNDED.', '.Order::PAYMENT_STATUS_REFUNDED.') THEN grand_total ELSE 0 END), 0), 2) AS refunded'),
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN payment_status IN ('.Order::PAYMENT_STATUS_PAID.', '.Order::PAYMENT_STATUS_INITIATED_REFUND.') THEN grand_total ELSE 0 END), 0), 2) AS paid')
            )
            ->groupBy('date')
            ->orderBy('date');
    }

    public function commonQueryByPaymentStatus()
    {
        return DB::table($this->orders)
            ->selectRaw('
                ROUND(COALESCE(SUM(CASE WHEN payment_status IN (?, ?) THEN grand_total ELSE 0 END), 0), 2) AS pending,
                ROUND(COALESCE(SUM(CASE WHEN payment_status IN (?, ?) THEN grand_total ELSE 0 END), 0), 2) AS refunded,
                ROUND(COALESCE(SUM(CASE WHEN payment_status IN (?, ?) THEN grand_total ELSE 0 END), 0), 2) AS paid
            ', [
                Order::PAYMENT_STATUS_UNPAID,
                Order::PAYMENT_STATUS_PENDING,
                Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
                Order::PAYMENT_STATUS_REFUNDED,
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_INITIATED_REFUND,
            ]);
    }

    public function commonQueryByPaymentMethods()
    {
        return DB::table($this->orders)
            ->join($this->paymentMethod, $this->orders.'.payment_method_id', '=', $this->paymentMethod.'.id')
            ->select(
                $this->paymentMethod.'.name',
                DB::raw('COUNT(DISTINCT '.$this->orders.'.id) as order_count'),
                DB::raw('ROUND(COALESCE(SUM('.$this->orders.'.grand_total), 0), 2) as total')
            )
            ->groupBy($this->paymentMethod.'.id', $this->paymentMethod.'.name')
            ->orderBy($this->paymentMethod.'.name', 'asc');
    }

    public function paymentsCommonDataQuery()
    {
        return DB::table($this->orders)
            ->leftJoin($this->customers, $this->customers.'.id', $this->orders.'.customer_id')
            ->leftJoin($this->shops, $this->shops.'.id', $this->orders.'.shop_id')
            ->leftJoin($this->paymentMethod, $this->paymentMethod.'.id', $this->orders.'.payment_method_id')
            ->select(
                $this->orders.'.id',
                $this->orders.'.order_number',
                DB::raw('DATE('.$this->orders.'.created_at) as date'),
                $this->customers.'.name as customer',
                $this->shops.'.name as shop',
                $this->orders.'.payment_status',
                $this->paymentMethod.'.name as payment_method',
                $this->orders.'.quantity as item',
                $this->orders.'.total',
                $this->orders.'.grand_total'
            );
    }

    public function payments($date = null)
    {
        return self::paymentsCommonDataQuery()
            ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
            ->orderBy($this->orders.'.created_at', 'desc')
            ->get();
    }

    public function paymentSearch(Carbon $fromDate, Carbon $toDate, array $packet)
    {
        $data = self::commonSearchQuery(self::paymentsCommonDataQuery(), $packet);

        return $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate])
            ->orderBy($this->orders.'.created_at', 'desc')
            ->get();
    }

    /**
     ** Products Report
     **/

    public function products($date = null)
    {
        return self::productsCommonQuery()
            ->whereDate($this->orders.'.created_at', '>', $date ?? $this->defaultSinceDate())
            ->get();
    }

    public function productsSearch(Carbon $fromDate, Carbon $toDate, array $packet)
    {
        $data = self::productsCommonQuery();
        $data = $data->whereBetween($this->orders.'.created_at', [$fromDate, $toDate]);

        if (! empty($packet['product_id'])) {
            $data = $data->where($this->products.'.id', (int) $packet['product_id']);
        }

        if (! empty($packet['shop_id'])) {
            $data = $data->where($this->orders.'.shop_id', (int) $packet['shop_id']);
        }

        return $data->get();
    }

    public function productsSummary(Carbon $fromDate, Carbon $toDate, array $packet = []): object
    {
        $query = DB::table($this->orderItems)
            ->leftJoin($this->inventories, $this->inventories.'.id', $this->orderItems.'.inventory_id')
            ->leftJoin($this->orders, $this->orders.'.id', $this->orderItems.'.order_id')
            ->leftJoin($this->products, $this->products.'.id', $this->inventories.'.product_id')
            ->where($this->orders.'.payment_status', '>=', Order::PAYMENT_STATUS_PAID)
            ->whereBetween($this->orders.'.created_at', [$fromDate, $toDate]);

        if (! empty($packet['product_id'])) {
            $query->where($this->products.'.id', (int) $packet['product_id']);
        }

        if (! empty($packet['shop_id'])) {
            $query->where($this->orders.'.shop_id', (int) $packet['shop_id']);
        }

        return $query->selectRaw('
                COUNT(DISTINCT '.$this->products.'.id) as products_sold,
                COALESCE(SUM('.$this->orderItems.'.quantity), 0) as units_sold,
                COUNT(DISTINCT '.$this->orderItems.'.order_id) as order_count,
                ROUND(COALESCE(SUM('.$this->orderItems.'.unit_price * '.$this->orderItems.'.quantity), 0), 2) as revenue
            ')
            ->first();
    }

    public function productsCommonQuery()
    {
        return DB::table($this->orderItems)
            ->leftJoin($this->inventories, $this->inventories.'.id', $this->orderItems.'.inventory_id')
            ->leftJoin($this->orders, $this->orders.'.id', $this->orderItems.'.order_id')
            ->leftJoin($this->products, $this->products.'.id', $this->inventories.'.product_id')
            ->where($this->orders.'.payment_status', '>=', Order::PAYMENT_STATUS_PAID)
            ->select(
                DB::raw('COUNT(DISTINCT '.$this->orderItems.'.order_id) as uniquePurchase'),
                DB::raw('ROUND(COALESCE(SUM('.$this->orderItems.'.unit_price * '.$this->orderItems.'.quantity) / NULLIF(SUM('.$this->orderItems.'.quantity), 0), 0), 2) as avgPrice'),
                DB::raw('SUM('.$this->orderItems.'.quantity) as quantity'),
                $this->products.'.name',
                $this->products.'.model_number',
                $this->products.'.gtin',
                $this->products.'.gtin_type',
                DB::raw('ROUND(COALESCE(SUM('.$this->orderItems.'.unit_price * '.$this->orderItems.'.quantity), 0), 2) as totalSale')
            )
            ->groupBy($this->products.'.id', $this->products.'.name', $this->products.'.model_number', $this->products.'.gtin', $this->products.'.gtin_type')
            ->orderByDesc('totalSale');
    }
}
