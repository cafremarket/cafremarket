<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\Refund;
use App\Models\Shop;
use App\Services\OrderCheckoutFeeService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Figures behind the admin and merchant report pages.
 *
 * Dates are order dates (orders.created_at) unless noted. A "sale" is any paid
 * order, including ones refunded later; refunds are subtracted separately
 * (net sales = gross sales - approved refunds). Marketplace commission comes
 * from the vendor wallet sale credit written on delivery
 * (OrderWalletService::payVendor); paid orders not settled yet show the
 * commission the fee service expects to deduct, unless they were refunded or
 * canceled before delivery (then no commission is charged).
 */
class MarketplaceReportService
{
    public const STATUS_SETTLED = 'settled';

    public const STATUS_AWAITING = 'awaiting';

    public const STATUS_REVERSED = 'reversed';

    /** Refunded or canceled before delivery: never settled, no commission. */
    public const STATUS_VOID = 'void';

    /** Ledgers are cached per period so one page can reuse them. */
    private array $ledgers = [];

    public function __construct(
        private ReportPeriod $period,
        private ?int $shopId = null,
    ) {}

    public function period(): ReportPeriod
    {
        return $this->period;
    }

    public function shopId(): ?int
    {
        return $this->shopId;
    }

    // ---------------------------------------------------------------------
    // Orders & sales
    // ---------------------------------------------------------------------

    /**
     * @return array<string, float|int>
     */
    public function salesSummary(?ReportPeriod $period = null): array
    {
        $period = $period ?? $this->period;
        $paid = $this->paidSql();

        $row = $this->ordersQuery($period)
            ->selectRaw("
                COUNT(orders.id) as placed_orders,
                COALESCE(SUM(orders.grand_total), 0) as placed_value,
                SUM(CASE WHEN {$paid} THEN 1 ELSE 0 END) as paid_orders,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.grand_total ELSE 0 END), 0) as gross_sales,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.quantity ELSE 0 END), 0) as units_sold,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.discount ELSE 0 END), 0) as discounts,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.shipping ELSE 0 END), 0) as shipping,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.taxes ELSE 0 END), 0) as taxes,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.subscription_transaction_fee ELSE 0 END), 0) as customer_fees,
                COALESCE(SUM(CASE WHEN orders.payment_status < ? AND orders.order_status_id <> ? THEN orders.grand_total ELSE 0 END), 0) as unpaid_value,
                SUM(CASE WHEN orders.payment_status < ? AND orders.order_status_id <> ? THEN 1 ELSE 0 END) as unpaid_orders,
                SUM(CASE WHEN orders.order_status_id = ? THEN 1 ELSE 0 END) as canceled_orders,
                SUM(CASE WHEN orders.order_status_id = ? THEN 1 ELSE 0 END) as delivered_orders,
                COUNT(DISTINCT CASE WHEN {$paid} THEN orders.customer_id END) as buyers
            ", [
                Order::PAYMENT_STATUS_PAID, Order::STATUS_CANCELED,
                Order::PAYMENT_STATUS_PAID, Order::STATUS_CANCELED,
                Order::STATUS_CANCELED,
                Order::STATUS_DELIVERED,
            ])
            ->first();

        $paidOrders = (int) ($row->paid_orders ?? 0);
        $grossSales = round((float) ($row->gross_sales ?? 0), 2);
        $refunds = $this->refundTotals($period);

        return [
            'placed_orders' => (int) ($row->placed_orders ?? 0),
            'placed_value' => round((float) ($row->placed_value ?? 0), 2),
            'paid_orders' => $paidOrders,
            'gross_sales' => $grossSales,
            'units_sold' => (int) ($row->units_sold ?? 0),
            'discounts' => round((float) ($row->discounts ?? 0), 2),
            'shipping' => round((float) ($row->shipping ?? 0), 2),
            'taxes' => round((float) ($row->taxes ?? 0), 2),
            'customer_fees' => round((float) ($row->customer_fees ?? 0), 2),
            'unpaid_orders' => (int) ($row->unpaid_orders ?? 0),
            'unpaid_value' => round((float) ($row->unpaid_value ?? 0), 2),
            'canceled_orders' => (int) ($row->canceled_orders ?? 0),
            'delivered_orders' => (int) ($row->delivered_orders ?? 0),
            'buyers' => (int) ($row->buyers ?? 0),
            'average_order_value' => $paidOrders > 0 ? round($grossSales / $paidOrders, 2) : 0.0,
            'refunded' => $refunds['approved_amount'],
            'refund_count' => $refunds['approved_count'],
            'net_sales' => round($grossSales - $refunds['approved_amount'], 2),
            'cancel_rate' => ($row->placed_orders ?? 0) > 0
                ? round(((int) $row->canceled_orders / (int) $row->placed_orders) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * Orders placed per status (all orders, paid or not).
     *
     * @return Collection<int, array{status_id: int, label: string, orders: int, value: float}>
     */
    public function orderStatusBreakdown(): Collection
    {
        return $this->ordersQuery()
            ->groupBy('orders.order_status_id')
            ->selectRaw('orders.order_status_id, COUNT(*) as orders, COALESCE(SUM(orders.grand_total), 0) as value')
            ->orderByDesc('orders')
            ->get()
            ->map(fn ($row) => [
                'status_id' => (int) $row->order_status_id,
                'label' => self::orderStatusLabel((int) $row->order_status_id),
                'orders' => (int) $row->orders,
                'value' => round((float) $row->value, 2),
            ]);
    }

    /**
     * @return Collection<int, array{method: string, orders: int, paid_orders: int, paid_value: float, share: float}>
     */
    public function paymentMethodBreakdown(): Collection
    {
        $paid = $this->paidSql();

        $rows = $this->ordersQuery()
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'orders.payment_method_id')
            ->groupBy('orders.payment_method_id')
            ->selectRaw("
                MAX(payment_methods.name) as method,
                COUNT(*) as orders,
                SUM(CASE WHEN {$paid} THEN 1 ELSE 0 END) as paid_orders,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.grand_total ELSE 0 END), 0) as paid_value
            ")
            ->orderByDesc('paid_value')
            ->get();

        $total = max(0.01, (float) $rows->sum('paid_value'));

        return $rows->map(fn ($row) => [
            'method' => $row->method ?: trans('reports.unknown'),
            'orders' => (int) $row->orders,
            'paid_orders' => (int) $row->paid_orders,
            'paid_value' => round((float) $row->paid_value, 2),
            'share' => round(((float) $row->paid_value / $total) * 100, 1),
        ]);
    }

    /**
     * Sales, commission and orders per day (or per month for long ranges).
     *
     * @return array{labels: array<int, string>, gross: array<int, float>, commission: array<int, float>, orders: array<int, int>}
     */
    public function trend(): array
    {
        $buckets = $this->period->buckets();
        $gross = array_fill_keys(array_keys($buckets), 0.0);
        $commission = $gross;
        $orders = array_fill_keys(array_keys($buckets), 0);

        foreach ($this->commissionLedger() as $row) {
            $key = $this->period->bucketKey($row['date']);
            if (! array_key_exists($key, $gross)) {
                continue;
            }

            $gross[$key] += $row['gross'];
            $commission[$key] += $row['commission'];
            $orders[$key]++;
        }

        return [
            'labels' => array_values($buckets),
            'gross' => array_map(fn ($v) => round($v, 2), array_values($gross)),
            'commission' => array_map(fn ($v) => round($v, 2), array_values($commission)),
            'orders' => array_values($orders),
        ];
    }

    // ---------------------------------------------------------------------
    // Commission
    // ---------------------------------------------------------------------

    /**
     * One row per paid (or settled) order in the period with its commission.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function commissionLedger(?ReportPeriod $period = null): Collection
    {
        $period = $period ?? $this->period;
        $cacheKey = $period->from->toDateTimeString().'|'.$period->to->toDateTimeString();

        if (isset($this->ledgers[$cacheKey])) {
            return $this->ledgers[$cacheKey];
        }

        $rows = $this->ordersQuery($period)
            ->leftJoinSub($this->settlementsQuery($period), 'settle', 'settle.settle_order_id', '=', 'orders.id')
            ->leftJoin('shops', 'shops.id', '=', 'orders.shop_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'orders.payment_method_id')
            ->where(function (Builder $q) {
                $q->whereRaw($this->paidSql())->orWhereNotNull('settle.settle_order_id');
            })
            ->orderByDesc('orders.created_at')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.created_at',
                'orders.shop_id',
                'orders.grand_total',
                'orders.affiliate_commission_amount',
                'orders.subscription_transaction_fee',
                'orders.order_status_id',
                'orders.payment_status',
                'shops.name as shop',
                'customers.name as customer',
                'payment_methods.name as payment_method',
                'settle.settle_order_id',
                'settle.credited_commission',
                'settle.reversed_commission',
                'settle.credited_affiliate',
                'settle.net_credited',
                'settle.net_reversed',
                'settle.settled_at',
            ])
            ->get();

        $expected = $this->expectedCommissions(
            $rows->whereNull('settle_order_id')
                ->where('payment_status', '!=', Order::PAYMENT_STATUS_REFUNDED)
                ->where('order_status_id', '!=', Order::STATUS_CANCELED)
                ->pluck('id')->all()
        );

        return $this->ledgers[$cacheKey] = $rows->map(function ($row) use ($expected) {
            $gross = round((float) $row->grand_total, 2);

            if ($row->settle_order_id) {
                $credited = (float) $row->credited_commission;
                $reversed = (float) $row->reversed_commission;
                $commission = max(0, round($credited - $reversed, 2));
                // A refund returns only part of the commission; the order stays "collected" while some is kept.
                $status = $reversed > 0 && $commission <= 0 ? self::STATUS_REVERSED : self::STATUS_SETTLED;
                $affiliate = $status === self::STATUS_REVERSED ? 0.0 : round((float) $row->credited_affiliate, 2);
                $net = max(0, round((float) $row->net_credited - (float) $row->net_reversed, 2));
            } elseif ((int) $row->payment_status === Order::PAYMENT_STATUS_REFUNDED
                || (int) $row->order_status_id === Order::STATUS_CANCELED) {
                $status = self::STATUS_VOID;
                $commission = 0.0;
                $affiliate = 0.0;
                $net = 0.0;
            } else {
                $status = self::STATUS_AWAITING;
                $commission = $expected[$row->id] ?? 0.0;
                $affiliate = round((float) $row->affiliate_commission_amount, 2);
                $net = max(0, round($gross - $commission - $affiliate, 2));
            }

            return [
                'id' => (int) $row->id,
                'order_number' => $row->order_number,
                'date' => $row->created_at,
                'shop_id' => (int) $row->shop_id,
                'shop' => $row->shop,
                'customer' => $row->customer,
                'payment_method' => $row->payment_method,
                'order_status' => self::orderStatusLabel((int) $row->order_status_id),
                'payment_status' => get_payment_status_name((int) $row->payment_status),
                'gross' => $gross,
                'commission' => $commission,
                'reversed_commission' => round((float) ($row->reversed_commission ?? 0), 2),
                'rate' => $gross > 0 ? round(($commission / $gross) * 100, 2) : 0.0,
                'affiliate' => $affiliate,
                'customer_fee' => round((float) $row->subscription_transaction_fee, 2),
                'net_vendor' => $net,
                'status' => $status,
                'status_label' => trans('reports.commission_status.'.$status),
                'settled_at' => $row->settled_at,
            ];
        });
    }

    /**
     * @return array<string, float|int>
     */
    public function commissionSummary(?ReportPeriod $period = null): array
    {
        $ledger = $this->commissionLedger($period);
        $active = $ledger->whereIn('status', [self::STATUS_SETTLED, self::STATUS_AWAITING]);
        $settled = $ledger->where('status', self::STATUS_SETTLED);
        $awaiting = $ledger->where('status', self::STATUS_AWAITING);
        $gross = (float) $active->sum('gross');
        $commission = (float) $active->sum('commission');

        return [
            'orders' => $active->count(),
            'gross' => round($gross, 2),
            'settled' => round((float) $settled->sum('commission'), 2),
            'settled_orders' => $settled->count(),
            'awaiting' => round((float) $awaiting->sum('commission'), 2),
            'awaiting_orders' => $awaiting->count(),
            'reversed' => round((float) $ledger->sum('reversed_commission'), 2),
            'reversed_orders' => $ledger->where('reversed_commission', '>', 0)->count(),
            'void_orders' => $ledger->where('status', self::STATUS_VOID)->count(),
            'total' => round($commission, 2),
            'affiliate' => round((float) $active->sum('affiliate'), 2),
            'customer_fees' => round((float) $active->sum('customer_fee'), 2),
            'net_vendor' => round((float) $active->sum('net_vendor'), 2),
            'effective_rate' => $gross > 0 ? round(($commission / $gross) * 100, 2) : 0.0,
        ];
    }

    /**
     * Commission totals per shop, biggest earners first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function commissionByShop(): Collection
    {
        $plans = $this->shopPlans();

        return $this->commissionLedger()
            ->whereIn('status', [self::STATUS_SETTLED, self::STATUS_AWAITING])
            ->groupBy('shop_id')
            ->map(function (Collection $rows, $shopId) use ($plans) {
                $gross = (float) $rows->sum('gross');
                $commission = (float) $rows->sum('commission');

                return [
                    'shop_id' => (int) $shopId,
                    'shop' => $rows->first()['shop'],
                    'plan' => $plans[$shopId] ?? null,
                    'orders' => $rows->count(),
                    'gross' => round($gross, 2),
                    'settled' => round((float) $rows->where('status', self::STATUS_SETTLED)->sum('commission'), 2),
                    'awaiting' => round((float) $rows->where('status', self::STATUS_AWAITING)->sum('commission'), 2),
                    'commission' => round($commission, 2),
                    'rate' => $gross > 0 ? round(($commission / $gross) * 100, 2) : 0.0,
                    'affiliate' => round((float) $rows->sum('affiliate'), 2),
                    'net_vendor' => round((float) $rows->sum('net_vendor'), 2),
                ];
            })
            ->sortByDesc('commission')
            ->values();
    }

    /**
     * Platform income for the period, by source.
     *
     * @return array<string, float>
     */
    public function platformRevenue(): array
    {
        $commission = $this->commissionSummary();
        $subscriptions = $this->walletSubscriptionFees();

        return [
            'commission_settled' => $commission['settled'],
            'commission_awaiting' => $commission['awaiting'],
            'customer_fees' => $commission['customer_fees'],
            'subscription_fees' => $subscriptions,
            'total' => round($commission['total'] + $commission['customer_fees'] + $subscriptions, 2),
        ];
    }

    // ---------------------------------------------------------------------
    // Vendors
    // ---------------------------------------------------------------------

    /**
     * Every shop with its sales, commission, refunds and payouts in the period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function vendorPerformance(): Collection
    {
        $paid = $this->paidSql();

        $sales = $this->ordersQuery()
            ->groupBy('orders.shop_id')
            ->selectRaw("
                orders.shop_id,
                COUNT(*) as placed_orders,
                SUM(CASE WHEN {$paid} THEN 1 ELSE 0 END) as paid_orders,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.grand_total ELSE 0 END), 0) as gross,
                COALESCE(SUM(CASE WHEN {$paid} THEN orders.quantity ELSE 0 END), 0) as units,
                SUM(CASE WHEN orders.order_status_id = ? THEN 1 ELSE 0 END) as canceled,
                SUM(CASE WHEN orders.order_status_id = ? THEN 1 ELSE 0 END) as delivered,
                MAX(orders.created_at) as last_order_at
            ", [Order::STATUS_CANCELED, Order::STATUS_DELIVERED])
            ->get()
            ->keyBy('shop_id');

        $commission = $this->commissionByShop()->keyBy('shop_id');

        $refunds = $this->refundsQuery()
            ->where('refunds.status', Refund::STATUS_APPROVED)
            ->groupBy('refunds.shop_id')
            ->selectRaw('refunds.shop_id, COUNT(*) as refunds, COALESCE(SUM(refunds.amount), 0) as refunded')
            ->get()
            ->keyBy('shop_id');

        $payouts = $this->payoutsQuery()
            ->where('transactions.approved', 1)
            ->groupBy('transactions.payable_id')
            ->selectRaw('transactions.payable_id as shop_id, COALESCE(SUM(ABS(transactions.amount)), 0) as paid_out')
            ->get()
            ->keyBy('shop_id');

        $balances = $this->walletBalances();
        $plans = $this->shopPlans();

        return DB::table('shops')
            ->whereNull('shops.deleted_at')
            ->when($this->shopId, fn ($q) => $q->where('shops.id', $this->shopId))
            ->select('shops.id', 'shops.name', 'shops.active', 'shops.created_at')
            ->get()
            ->map(function ($shop) use ($sales, $commission, $refunds, $payouts, $balances, $plans) {
                $s = $sales->get($shop->id);
                $c = $commission->get($shop->id);
                $paidOrders = (int) ($s->paid_orders ?? 0);
                $gross = round((float) ($s->gross ?? 0), 2);
                $placed = (int) ($s->placed_orders ?? 0);
                $refunded = round((float) ($refunds->get($shop->id)->refunded ?? 0), 2);

                return [
                    'shop_id' => (int) $shop->id,
                    'shop' => $shop->name,
                    'active' => (bool) $shop->active,
                    'joined' => $shop->created_at,
                    'plan' => $plans[$shop->id] ?? null,
                    'placed_orders' => $placed,
                    'paid_orders' => $paidOrders,
                    'units' => (int) ($s->units ?? 0),
                    'gross' => $gross,
                    'average_order_value' => $paidOrders > 0 ? round($gross / $paidOrders, 2) : 0.0,
                    'commission' => (float) ($c['commission'] ?? 0),
                    'rate' => (float) ($c['rate'] ?? 0),
                    'net_vendor' => (float) ($c['net_vendor'] ?? 0),
                    'earnings' => round((float) ($c['net_vendor'] ?? 0) - $refunded, 2),
                    'refunds' => (int) ($refunds->get($shop->id)->refunds ?? 0),
                    'refunded' => $refunded,
                    'refund_rate' => $gross > 0 ? round(($refunded / $gross) * 100, 1) : 0.0,
                    'cancel_rate' => $placed > 0 ? round(((int) ($s->canceled ?? 0) / $placed) * 100, 1) : 0.0,
                    'delivered' => (int) ($s->delivered ?? 0),
                    'paid_out' => round((float) ($payouts->get($shop->id)->paid_out ?? 0), 2),
                    'wallet_balance' => $balances[$shop->id] ?? 0.0,
                    'last_order_at' => $s->last_order_at ?? null,
                ];
            })
            ->sortByDesc('gross')
            ->values();
    }

    /**
     * @return array<string, float|int>
     */
    public function payoutSummary(): array
    {
        $row = $this->payoutsQuery()
            ->selectRaw('
                SUM(CASE WHEN transactions.approved = 1 THEN 1 ELSE 0 END) as paid_count,
                COALESCE(SUM(CASE WHEN transactions.approved = 1 THEN ABS(transactions.amount) ELSE 0 END), 0) as paid_amount,
                SUM(CASE WHEN transactions.approved IS NULL OR transactions.approved = 0 THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN transactions.approved IS NULL OR transactions.approved = 0 THEN ABS(transactions.amount) ELSE 0 END), 0) as pending_amount
            ')
            ->first();

        return [
            'paid_count' => (int) ($row->paid_count ?? 0),
            'paid_amount' => round((float) ($row->paid_amount ?? 0), 2),
            'pending_count' => (int) ($row->pending_count ?? 0),
            'pending_amount' => round((float) ($row->pending_amount ?? 0), 2),
            'wallet_balance' => round(array_sum($this->walletBalances()), 2),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function growth(?ReportPeriod $period = null): array
    {
        $period = $period ?? $this->period;
        $range = [$period->from, $period->to];

        return [
            'new_customers' => DB::table('customers')->whereNull('deleted_at')->whereBetween('created_at', $range)->count(),
            'new_shops' => DB::table('shops')->whereNull('deleted_at')->whereBetween('created_at', $range)->count(),
            'selling_shops' => (int) $this->ordersQuery($period)->whereRaw($this->paidSql())->distinct()->count('orders.shop_id'),
        ];
    }

    // ---------------------------------------------------------------------
    // Products & categories
    // ---------------------------------------------------------------------

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topProducts(int $limit = 10): Collection
    {
        return $this->orderItemsQuery()
            ->leftJoin('inventories', 'inventories.id', '=', 'order_items.inventory_id')
            ->leftJoin('products', 'products.id', '=', 'inventories.product_id')
            ->groupBy('products.id')
            ->selectRaw('
                products.id,
                MAX(COALESCE(products.name, inventories.title, order_items.item_description)) as name,
                SUM(order_items.quantity) as units,
                COUNT(DISTINCT order_items.order_id) as orders,
                COALESCE(SUM(order_items.quantity * order_items.unit_price), 0) as revenue
            ')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name ?: trans('reports.unknown'),
                'units' => (int) $row->units,
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
            ]);
    }

    /**
     * Sales per category. A product in two categories counts in both.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function topCategories(int $limit = 10): Collection
    {
        return $this->orderItemsQuery()
            ->join('inventories', 'inventories.id', '=', 'order_items.inventory_id')
            ->join('category_product', 'category_product.product_id', '=', 'inventories.product_id')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->groupBy('categories.id')
            ->selectRaw('
                MAX(categories.name) as name,
                SUM(order_items.quantity) as units,
                COUNT(DISTINCT order_items.order_id) as orders,
                COALESCE(SUM(order_items.quantity * order_items.unit_price), 0) as revenue
            ')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'units' => (int) $row->units,
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
            ]);
    }

    // ---------------------------------------------------------------------
    // Customers
    // ---------------------------------------------------------------------

    /**
     * Buyers in the period with their spend and whether they bought before.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function customers(): Collection
    {
        $paid = $this->paidSql();

        $firstPurchase = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereNotNull('customer_id')
            ->whereRaw($paid)
            ->when($this->shopId, fn ($q) => $q->where('shop_id', $this->shopId))
            ->groupBy('customer_id')
            ->selectRaw('customer_id, MIN(created_at) as first_order_at');

        return $this->ordersQuery()
            ->whereRaw($paid)
            ->whereNotNull('orders.customer_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoinSub($firstPurchase, 'firsts', 'firsts.customer_id', '=', 'orders.customer_id')
            ->groupBy('orders.customer_id')
            ->selectRaw('
                orders.customer_id,
                MAX(customers.name) as name,
                MAX(customers.email) as email,
                MAX(customers.created_at) as registered_at,
                COUNT(*) as orders,
                COALESCE(SUM(orders.grand_total), 0) as spent,
                COUNT(DISTINCT orders.shop_id) as shops,
                MAX(orders.created_at) as last_order_at,
                MIN(firsts.first_order_at) as first_order_at
            ')
            ->orderByDesc('spent')
            ->get()
            ->map(function ($row) {
                $orders = (int) $row->orders;
                $spent = round((float) $row->spent, 2);
                $isNew = $row->first_order_at && $row->first_order_at >= $this->period->from->toDateTimeString();

                return [
                    'customer_id' => (int) $row->customer_id,
                    'name' => $row->name ?: trans('reports.guest'),
                    'email' => $row->email,
                    'registered_at' => $row->registered_at,
                    'orders' => $orders,
                    'spent' => $spent,
                    'average_order_value' => $orders > 0 ? round($spent / $orders, 2) : 0.0,
                    'shops' => (int) $row->shops,
                    'first_order_at' => $row->first_order_at,
                    'last_order_at' => $row->last_order_at,
                    'is_new' => (bool) $isNew,
                ];
            });
    }

    /**
     * @return array<string, float|int>
     */
    public function customerSummary(): array
    {
        $customers = $this->customers();
        $buyers = $customers->count();
        $spent = (float) $customers->sum('spent');
        $orders = (int) $customers->sum('orders');

        return [
            'buyers' => $buyers,
            'new_buyers' => $customers->where('is_new', true)->count(),
            'returning_buyers' => $customers->where('is_new', false)->count(),
            'repeat_buyers' => $customers->where('orders', '>', 1)->count(),
            'repeat_rate' => $buyers > 0 ? round(($customers->where('orders', '>', 1)->count() / $buyers) * 100, 1) : 0.0,
            'orders_per_buyer' => $buyers > 0 ? round($orders / $buyers, 2) : 0.0,
            'spend_per_buyer' => $buyers > 0 ? round($spent / $buyers, 2) : 0.0,
            'registered' => $this->shopId ? 0 : $this->growth()['new_customers'],
        ];
    }

    // ---------------------------------------------------------------------
    // Refunds
    // ---------------------------------------------------------------------

    /**
     * Refund requests created in the period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function refunds(): Collection
    {
        return $this->refundsQuery()
            ->leftJoin('orders', 'orders.id', '=', 'refunds.order_id')
            ->leftJoin('shops', 'shops.id', '=', 'refunds.shop_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->orderByDesc('refunds.created_at')
            ->select([
                'refunds.id',
                'refunds.created_at',
                'refunds.amount',
                'refunds.status',
                'refunds.return_goods',
                'refunds.order_fulfilled',
                'refunds.description',
                'orders.order_number',
                'orders.grand_total',
                'shops.name as shop',
                'customers.name as customer',
            ])
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'date' => $row->created_at,
                'order_number' => $row->order_number,
                'order_total' => round((float) $row->grand_total, 2),
                'shop' => $row->shop,
                'customer' => $row->customer,
                'amount' => round((float) $row->amount, 2),
                'status' => (int) $row->status,
                'status_label' => self::refundStatusLabel((int) $row->status),
                'return_goods' => (bool) $row->return_goods,
                'reason' => $row->description,
            ]);
    }

    /**
     * @return array<string, float|int>
     */
    public function refundTotals(?ReportPeriod $period = null): array
    {
        $row = $this->refundsQuery($period)
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(refunds.amount), 0) as total_amount,
                SUM(CASE WHEN refunds.status = ? THEN 1 ELSE 0 END) as approved_count,
                COALESCE(SUM(CASE WHEN refunds.status = ? THEN refunds.amount ELSE 0 END), 0) as approved_amount,
                SUM(CASE WHEN refunds.status = ? THEN 1 ELSE 0 END) as open_count,
                COALESCE(SUM(CASE WHEN refunds.status = ? THEN refunds.amount ELSE 0 END), 0) as open_amount,
                SUM(CASE WHEN refunds.status IN (?, ?) THEN 1 ELSE 0 END) as declined_count
            ', [
                Refund::STATUS_APPROVED, Refund::STATUS_APPROVED,
                Refund::STATUS_NEW, Refund::STATUS_NEW,
                Refund::STATUS_DECLINED, Refund::STATUS_FAILED,
            ])
            ->first();

        return [
            'total_count' => (int) ($row->total_count ?? 0),
            'total_amount' => round((float) ($row->total_amount ?? 0), 2),
            'approved_count' => (int) ($row->approved_count ?? 0),
            'approved_amount' => round((float) ($row->approved_amount ?? 0), 2),
            'open_count' => (int) ($row->open_count ?? 0),
            'open_amount' => round((float) ($row->open_amount ?? 0), 2),
            'declined_count' => (int) ($row->declined_count ?? 0),
        ];
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /** Relative change in percent, or null when there is nothing to compare with. */
    public static function change(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous == 0.0) {
            return (float) $current == 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    public static function orderStatusLabel(int $status): string
    {
        return match ($status) {
            Order::STATUS_WAITING_FOR_PAYMENT => trans('app.waiting_for_payment'),
            Order::STATUS_PAYMENT_ERROR => trans('app.payment_error'),
            Order::STATUS_CONFIRMED => trans('app.confirmed'),
            Order::STATUS_FULFILLED => trans('app.fulfilled'),
            Order::STATUS_AWAITING_DELIVERY => trans('app.awaiting_delivery'),
            Order::STATUS_DELIVERED => trans('app.delivered'),
            Order::STATUS_RETURNED => trans('app.returns'),
            Order::STATUS_CANCELED => trans('app.canceled'),
            Order::STATUS_DISPUTED => trans('app.disputed'),
            default => trans('reports.unknown'),
        };
    }

    public static function refundStatusLabel(int $status): string
    {
        return match ($status) {
            Refund::STATUS_NEW => trans('reports.refund_status.new'),
            Refund::STATUS_APPROVED => trans('reports.refund_status.approved'),
            Refund::STATUS_DECLINED => trans('reports.refund_status.declined'),
            Refund::STATUS_FAILED => trans('reports.refund_status.failed'),
            default => trans('reports.unknown'),
        };
    }

    /** SQL condition for an order that counts as a sale (paid, even if refunded later). */
    private function paidSql(): string
    {
        return sprintf('(orders.payment_status >= %d)', Order::PAYMENT_STATUS_PAID);
    }

    private function ordersQuery(?ReportPeriod $period = null): Builder
    {
        $period = $period ?? $this->period;

        return DB::table('orders')
            ->whereNull('orders.deleted_at')
            ->whereBetween('orders.created_at', [$period->from, $period->to])
            ->when($this->shopId, fn ($q) => $q->where('orders.shop_id', $this->shopId));
    }

    private function orderItemsQuery(): Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->whereBetween('orders.created_at', [$this->period->from, $this->period->to])
            ->when($this->shopId, fn ($q) => $q->where('orders.shop_id', $this->shopId))
            ->whereRaw($this->paidSql());
    }

    private function refundsQuery(?ReportPeriod $period = null): Builder
    {
        $period = $period ?? $this->period;

        return DB::table('refunds')
            ->whereBetween('refunds.created_at', [$period->from, $period->to])
            ->when($this->shopId, fn ($q) => $q->where('refunds.shop_id', $this->shopId));
    }

    /** Vendor payout withdrawals requested in the period. */
    private function payoutsQuery(): Builder
    {
        return DB::table('transactions')
            ->where('transactions.payable_type', (new Shop)->getMorphClass())
            ->where('transactions.type', 'withdraw')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.type')) = 'payout'")
            ->whereBetween('transactions.created_at', [$this->period->from, $this->period->to])
            ->when($this->shopId, fn ($q) => $q->where('transactions.payable_id', $this->shopId));
    }

    /**
     * Vendor wallet sale credits, their reversals and refund commission
     * returns (OrderWalletService::returnCommissionForRefund), one row per order.
     * Settlement always happens after the order is placed, so rows older
     * than the period start can be skipped.
     */
    private function settlementsQuery(ReportPeriod $period): Builder
    {
        $num = fn (string $key) => "CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.{$key}')), '0') AS DECIMAL(20,6))";

        return DB::table('transactions')
            ->where('transactions.payable_type', (new Shop)->getMorphClass())
            ->where('transactions.created_at', '>=', $period->from)
            ->where(function (Builder $q) {
                $q->whereRaw("(JSON_CONTAINS_PATH(transactions.meta, 'one', '$.marketplace_commission') AND JSON_CONTAINS_PATH(transactions.meta, 'one', '$.order_id'))")
                    ->orWhereRaw("JSON_CONTAINS_PATH(transactions.meta, 'one', '$.commission_return_order_id')")
                    ->orWhereRaw("JSON_CONTAINS_PATH(transactions.meta, 'one', '$.affiliate_return_order_id')");
            })
            ->groupBy('settle_order_id')
            ->selectRaw("
                CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.order_id')), JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.commission_return_order_id')), JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.affiliate_return_order_id'))) AS UNSIGNED) as settle_order_id,
                SUM(CASE WHEN transactions.type = 'deposit' THEN {$num('marketplace_commission')} ELSE 0 END) as credited_commission,
                SUM(CASE WHEN transactions.type = 'withdraw' THEN {$num('marketplace_commission')} ELSE {$num('commission_return')} END) as reversed_commission,
                SUM(CASE WHEN transactions.type = 'deposit' THEN {$num('affiliate_commission')} - {$num('affiliate_return')} ELSE 0 END) as credited_affiliate,
                SUM(CASE WHEN transactions.type = 'deposit' THEN ABS(transactions.amount) ELSE 0 END) as net_credited,
                SUM(CASE WHEN transactions.type = 'withdraw' THEN ABS(transactions.amount) ELSE 0 END) as net_reversed,
                MIN(CASE WHEN transactions.type = 'deposit' THEN transactions.created_at END) as settled_at
            ");
    }

    /**
     * Commission the fee service would deduct for orders not settled yet.
     *
     * @param  array<int, int>  $orderIds
     * @return array<int, float>
     */
    private function expectedCommissions(array $orderIds): array
    {
        $expected = [];

        foreach (array_chunk($orderIds, 500) as $chunk) {
            Order::with('shop')->whereIn('id', $chunk)->get()->each(function (Order $order) use (&$expected) {
                try {
                    $expected[$order->id] = $order->shop
                        ? OrderCheckoutFeeService::marketplaceCommissionForOrder($order)
                        : 0.0;
                } catch (\Throwable $e) {
                    report($e);
                    $expected[$order->id] = 0.0;
                }
            });
        }

        return $expected;
    }

    private function walletSubscriptionFees(): float
    {
        return round((float) DB::table('transactions')
            ->where('transactions.payable_type', (new Shop)->getMorphClass())
            ->where('transactions.type', 'withdraw')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(transactions.meta, '$.purpose')) = 'subscription'")
            ->whereBetween('transactions.created_at', [$this->period->from, $this->period->to])
            ->when($this->shopId, fn ($q) => $q->where('transactions.payable_id', $this->shopId))
            ->sum(DB::raw('ABS(transactions.amount)')), 2);
    }

    /** @return array<int, float> shop id => current wallet balance */
    private function walletBalances(): array
    {
        return DB::table('wallets')
            ->where('holder_type', (new Shop)->getMorphClass())
            ->when($this->shopId, fn ($q) => $q->where('holder_id', $this->shopId))
            ->groupBy('holder_id')
            ->selectRaw('holder_id, SUM(balance) as balance')
            ->pluck('balance', 'holder_id')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
    }

    /** @return array<int, string> shop id => current subscription plan name */
    private function shopPlans(): array
    {
        return DB::table('shops')
            ->join('subscription_plans', 'subscription_plans.plan_id', '=', 'shops.current_billing_plan')
            ->when($this->shopId, fn ($q) => $q->where('shops.id', $this->shopId))
            ->pluck('subscription_plans.name', 'shops.id')
            ->all();
    }
}
