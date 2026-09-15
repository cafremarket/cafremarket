<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\Inventory;
use App\Models\Merchant;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Provide statistics all over the application
 */
class Statistics
{
    public static function visitor_count($period = null)
    {
        $visitor = new Visitor;

        if (is_null($period)) {
            return $visitor->count();
        } elseif (is_numeric($period)) {
            $date = Carbon::today()->subDays($period)->startOfDay();
        } elseif ($period == 'today') {
            $date = Carbon::today()->startOfDay();
        } else {
            $date = Carbon::today()->startOfDay();
        }

        return $visitor->of($date)->count();
    }

    public static function merchant_count($period = null)
    {
        $merchant = new Merchant;

        if ($period) {
            $date = Carbon::today()->subDays($period);
            $merchant = $merchant->where('created_at', '>=', $date);
        }

        return $merchant->count();
    }

    public static function new_vendor_count($hrs = 24)
    {
        $merchant = new Merchant;

        return $merchant->where('created_at', '>=', Carbon::now()->subHours($hrs))->count();
    }

    public static function customer_count($period = null)
    {
        $customer = new Customer;

        if ($period) {
            $date = Carbon::today()->subDays($period);

            $customer = $customer->where('created_at', '>=', $date);
        }

        return $customer->count();
    }

    public static function stock_out_count()
    {
        return Inventory::mine()->stockOut()->count();
    }

    public static function customer_orders_count($customer)
    {
        return Order::withTrashed()->where('customer_id', $customer)->count();
    }

    public static function total_spent($customer)
    {
        return Order::withTrashed()->where('customer_id', $customer)->sum('total');
    }

    public static function last_sale()
    {
        return Order::mine()->withTrashed()->visibleToVendor()->orderBy('created_at', 'desc')->first();
    }

    /**
     * Raw money breakdown for dashboard sale cards (products + fees − discount).
     *
     * @param  \App\Models\Order|null  $order
     * @return array{products: float, taxes: float, shipping: float, handling: float, packaging: float, discount: float, grand_total: float, order_count: int, order_number: string|null}
     */
    public static function sale_breakdown_from_order($order): array
    {
        if (! $order) {
            return static::empty_sale_breakdown();
        }

        return [
            'products' => (float) $order->total,
            'taxes' => (float) $order->taxes,
            'shipping' => (float) $order->shipping,
            'handling' => (float) $order->handling,
            'packaging' => (float) $order->packaging,
            'discount' => (float) $order->discount,
            'grand_total' => (float) $order->grand_total,
            'order_count' => 1,
            'order_number' => $order->order_number,
        ];
    }

    /**
     * Aggregated sale breakdown for orders created on a calendar day.
     *
     * @return array{products: float, taxes: float, shipping: float, handling: float, packaging: float, discount: float, grand_total: float, order_count: int, order_number: string|null}
     */
    public static function sale_breakdown_for_date(Carbon $day): array
    {
        $query = Auth::user()->isFromPlatform()
            ? Order::withTrashed()
            : Order::mine()->withTrashed()->visibleToVendor();

        $row = $query->whereDate('created_at', $day)
            ->selectRaw('
                COALESCE(SUM(total), 0) as products,
                COALESCE(SUM(taxes), 0) as taxes,
                COALESCE(SUM(shipping), 0) as shipping,
                COALESCE(SUM(handling), 0) as handling,
                COALESCE(SUM(packaging), 0) as packaging,
                COALESCE(SUM(discount), 0) as discount,
                COALESCE(SUM(grand_total), 0) as grand_total,
                COUNT(*) as order_count
            ')
            ->first();

        if (! $row || (int) $row->order_count === 0) {
            return static::empty_sale_breakdown();
        }

        return [
            'products' => (float) $row->products,
            'taxes' => (float) $row->taxes,
            'shipping' => (float) $row->shipping,
            'handling' => (float) $row->handling,
            'packaging' => (float) $row->packaging,
            'discount' => (float) $row->discount,
            'grand_total' => (float) $row->grand_total,
            'order_count' => (int) $row->order_count,
            'order_number' => null,
        ];
    }

    public static function empty_sale_breakdown(): array
    {
        return [
            'products' => 0.0,
            'taxes' => 0.0,
            'shipping' => 0.0,
            'handling' => 0.0,
            'packaging' => 0.0,
            'discount' => 0.0,
            'grand_total' => 0.0,
            'order_count' => 0,
            'order_number' => null,
        ];
    }

    /**
     * Format a sale breakdown for API / UI display.
     */
    public static function format_sale_breakdown(array $breakdown, $currency = null, $decimal = null): array
    {
        $currency = $currency ?? config('system_settings.currency.id');
        $decimal = $decimal ?? config('system_settings.decimals', 2);

        return [
            'products' => get_formated_currency($breakdown['products'], $decimal, $currency),
            'taxes' => get_formated_currency($breakdown['taxes'], $decimal, $currency),
            'shipping' => get_formated_currency($breakdown['shipping'], $decimal, $currency),
            'handling' => get_formated_currency($breakdown['handling'], $decimal, $currency),
            'packaging' => get_formated_currency($breakdown['packaging'], $decimal, $currency),
            'discount' => get_formated_currency($breakdown['discount'], $decimal, $currency),
            'grand_total' => get_formated_currency($breakdown['grand_total'], $decimal, $currency),
            'order_count' => (int) ($breakdown['order_count'] ?? 0),
            'order_number' => $breakdown['order_number'] ?? null,
        ];
    }

    public static function todays_sale_amount()
    {
        if (Auth::user()->isFromPlatform()) {
            return Order::withTrashed()->whereDate('created_at', Carbon::today())->sum('grand_total');
        }

        return Order::mine()->withTrashed()->visibleToVendor()
            ->whereDate('created_at', Carbon::today())->sum('grand_total');
    }

    public static function yesterdays_sale_amount()
    {
        if (Auth::user()->isFromPlatform()) {
            return Order::withTrashed()->whereDate('created_at', Carbon::yesterday())->sum('grand_total');
        }

        return Order::mine()->withTrashed()->visibleToVendor()
            ->whereDate('created_at', Carbon::yesterday())->sum('grand_total');
    }

    public static function sales_data_by_period(Carbon $startTime, Carbon $endTime)
    {
        return Order::mine()->withTrashed()
            ->with('shop')
            ->paid()
            ->where('created_at', '<=', $startTime)
            ->where('created_at', '>=', $endTime)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Paid merchant orders within an inclusive datetime range.
     */
    public static function merchant_paid_orders_between(Carbon $from, Carbon $to)
    {
        return Order::mine()->withTrashed()
            ->with('shop')
            ->paid()
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Monthly net sales totals aligned with SalesByPeriod chart labels.
     */
    public static function merchant_sales_chart_dataset(Carbon $rangeStart, Carbon $rangeEnd, int $months): array
    {
        $orders = static::merchant_paid_orders_between($rangeStart, $rangeEnd);

        $monthTotals = $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m');
        })->map(function ($group) {
            return round(static::merchant_net_sales_total($group), 2);
        });

        $dataset = [];
        $monthCursor = Carbon::today()->startOfMonth();

        for ($i = $months - 1; $i >= 0; $i--) {
            $key = $monthCursor->copy()->subMonths($i)->format('Y-m');
            $dataset[] = (float) ($monthTotals->get($key, 0));
        }

        return $dataset;
    }

    public static function merchant_net_sales_total($orders)
    {
        return round(collect($orders)->sum(function ($order) {
            return get_vendor_settlement_for_order($order)['net'];
        }), 2);
    }

    public static function platform_marketplace_sales($days = 30)
    {
        return round(Order::whereDate('created_at', '>=', Carbon::today()->subDays($days))
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->sum('grand_total'), 2);
    }

    public static function platform_marketplace_orders_count($days = 30)
    {
        return Order::whereDate('created_at', '>=', Carbon::today()->subDays($days))
            ->where('payment_status', '>=', Order::PAYMENT_STATUS_PAID)
            ->count();
    }

    public static function latest_refund_total($period = 15)
    {
        return Refund::mine()->statusOf(Refund::STATUS_APPROVED)
            ->whereDate('updated_at', '>=', Carbon::today()->subDays($period))->sum('amount');
    }

    public static function latest_order_count($period = 15)
    {
        return Order::mine()->withTrashed()->visibleToVendor()
            ->whereDate('created_at', '>=', Carbon::today()->subDays($period))->count();
    }

    public static function todays_order_count()
    {
        return Order::mine()->withTrashed()->visibleToVendor()->whereDate('created_at', Carbon::today())->count();
    }

    public static function todays_all_order_count()
    {
        return Order::query()->whereDate('created_at', Carbon::today())->count();
    }

    public static function yesterday_order_count()
    {
        return Order::mine()->withTrashed()->visibleToVendor()->whereDate('created_at', Carbon::yesterday())->count();
    }

    public static function yesterday_all_order_count()
    {
        return Order::query()->whereDate('created_at', Carbon::yesterday())->count();
    }

    public static function total_order_count()
    {
        return Order::all()->count();
    }

    public static function admin_created_total_product_count()
    {
        return Product::withTrashed()->whereNull('shop_id')->count();
    }

    public static function merchant_created_total_product_count()
    {
        return Product::withTrashed()->whereNotNull('shop_id')->count();
    }

    public static function unfulfilled_order_count()
    {
        return Order::mine()->visibleToVendor()->unfulfilled()->count();
    }

    public static function abandoned_carts_count($period = 15)
    {
        return Cart::mine()->whereDate('created_at', '>=', Carbon::today()->subDays($period))->count();
    }

    public static function shop_user_count($shop_id = null)
    {
        if ($shop_id) {
            return \DB::table('users')->where('shop_id', $shop_id)->count();
        }

        return User::mine()->count();
    }

    public static function shop_inventories_count($shop_id = null)
    {
        // Count listing parents only (child attribute SKUs are not separate products).
        if ($shop_id) {
            return \DB::table('inventories')
                ->where('shop_id', $shop_id)
                ->whereNull('parent_id')
                ->whereNull('deleted_at')
                ->count();
        }

        return Inventory::mine()->whereNull('parent_id')->count();
    }

    public static function unread_msg_count()
    {
        return \DB::table('messages')->where('shop_id', Auth::user()->merchantId())
            ->where('label', Message::LABEL_INBOX)
            ->where('status', '<', Message::STATUS_READ)->count();
    }

    public static function draft_msg_count()
    {
        return \DB::table('messages')->where('shop_id', Auth::user()->merchantId())
            ->where('label', Message::LABEL_DRAFT)->count();
    }

    public static function spam_msg_count()
    {
        return \DB::table('messages')->where('shop_id', Auth::user()->merchantId())
            ->where('label', Message::LABEL_SPAM)->count();
    }

    public static function trash_msg_count()
    {
        return \DB::table('messages')->where('shop_id', Auth::user()->merchantId())->where('label', Message::LABEL_TRASH)->count();
    }

    public static function open_refund_request_count($shop = null)
    {
        if ($shop && Auth::user()->isFromPlatform()) {
            return Refund::where('shop_id', $shop)->open()->count();
        }

        return Refund::mine()->open()->count();
    }

    public static function refund_request_count($period = null, $shop = null)
    {
        $refund = new Refund;

        if (! Auth::user()->isFromPlatform()) {
            $refund->mine();
        } elseif ($shop) {
            $refund->where('shop_id', $shop);
        }

        if ($period) {
            $date = Carbon::today()->subDays($period);

            $refund = $refund->where('created_at', '>=', $date);
        }

        return $refund->count();
    }

    public static function dispute_count($shop = null, $period = null)
    {
        $dispute = new Dispute;

        if (! Auth::user()->isFromPlatform()) {
            $dispute = $dispute->mine();
        } elseif ($shop) {
            $dispute = $dispute->where('shop_id', $shop);
        }

        if ($period) {
            $date = Carbon::today()->subDays($period);

            $dispute = $dispute->where('created_at', '>=', $date);
        }

        return $dispute->count();
    }

    public static function appealed_dispute_count($shop = null, $period = null)
    {
        $dispute = new Dispute;

        if (! Auth::user()->isFromPlatform()) {
            $dispute = $dispute->mine();
        } elseif ($shop) {
            $dispute = $dispute->where('shop_id', $shop);
        }

        if ($period) {
            $date = Carbon::today()->subDays($period);

            // If include all disputes of every statuses
            return $dispute->where('created_at', '>=', $date)->count();
        }

        return $dispute->statusOf(Dispute::STATUS_CLOSE_REQUESTED)->count();
    }

    public static function disputes_by_customer_count($customer, $period = null)
    {
        if ($period) {
            $date = Carbon::today()->subDays($period);

            return \DB::table('disputes')->where('customer_id', $customer)
                ->where('created_at', '>=', $date)->count();
        }

        return \DB::table('disputes')->where('customer_id', $customer)->count();
    }

    public static function pending_approval_count()
    {
        return \DB::table('shops')->where('deleted_at', null)->where('active', '!=', 1)->count();
    }

    public static function pending_verification_count()
    {
        return \DB::table('shops')->where('deleted_at', null)
            ->join('configs', 'configs.shop_id', '=', 'shops.id')
            ->where('configs.pending_verification', 1)->count();
    }

    public static function pending_address_change_count()
    {
        return \App\Models\ShopAddressChangeRequest::query()
            ->where('status', \App\Models\ShopAddressChangeRequest::STATUS_PENDING)
            ->count();
    }

    public static function pending_slug_change_count()
    {
        return \App\Models\ShopSlugChangeRequest::query()
            ->where('status', \App\Models\ShopSlugChangeRequest::STATUS_PENDING)
            ->count();
    }

    public static function pending_review_delete_count()
    {
        return \App\Models\ReviewDeleteRequest::query()
            ->where('status', \App\Models\ReviewDeleteRequest::STATUS_PENDING)
            ->count();
    }
}
