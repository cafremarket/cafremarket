<?php

namespace App\Http\Controllers\Api;

use App\Common\ShoppingCart;
use App\Contracts\PaymentServiceContract as PaymentGateway;
use App\Events\Order\OrderCreated;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CheckoutCartRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderLightResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentMethod;
// use App\Contracts\PaymentServiceContract as PaymentService;
// use App\Http\Requests\Validations\DirectCheckoutRequest;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    use ShoppingCart;

    private function walletVisibilityDebugData(Cart $cart): array
    {
        $shop = $cart->shop;
        $customer = Auth::guard('customer')->user() ?: Auth::guard('api')->user();
        $defaultWalletSlug = config('wallet.wallet.default.slug', 'default');
        $walletTableExists = Schema::hasTable('wallets');
        $walletRow = null;

        if ($walletTableExists && $customer) {
            $walletRow = DB::table('wallets')
                ->where('holder_type', get_class($customer))
                ->where('holder_id', $customer->id)
                ->where('slug', $defaultWalletSlug)
                ->first();
        }

        $activePaymentMethods = PaymentMethod::active()->get();
        $activePaymentCodes = $activePaymentMethods->pluck('code')->toArray();

        $shopConfig = null;
        if (vendor_get_paid_directly()) {
            $activePaymentMethods = $shop->paymentMethods;
            $shopConfig = $shop;
        }

        $methods = $activePaymentMethods->map(function ($payment) use ($activePaymentCodes, $shopConfig) {
            $config = get_payment_config_info($payment->code, $shopConfig);

            return [
                'code' => $payment->code,
                'enabled_in_payment_methods' => in_array($payment->code, $activePaymentCodes, true),
                'config_present' => (bool) $config,
                'config' => $config,
                'would_show' => $payment->code !== 'cod'
                    && in_array($payment->code, $activePaymentCodes, true)
                    && (bool) $config,
            ];
        })->values();

        return [
            'cart_id' => $cart->id,
            'shop' => [
                'id' => optional($shop)->id,
                'slug' => optional($shop)->slug,
                'vendor_get_paid_directly' => (bool) vendor_get_paid_directly(),
                'shop_payment_method_codes' => $shop
                    ? $shop->paymentMethods()->pluck('code')->values()
                    : [],
            ],
            'customer' => [
                'authenticated' => (bool) $customer,
                'id' => optional($customer)->id,
                'email' => optional($customer)->email,
                'customer_has_wallet' => function_exists('customer_has_wallet')
                    ? (bool) customer_has_wallet()
                    : false,
                'wallet_checkout_option' => (bool) get_from_option_table('wallet_checkout'),
                'wallet_table_exists' => $walletTableExists,
                'default_wallet_slug' => $defaultWalletSlug,
                'wallet_row_exists' => (bool) $walletRow,
                'wallet_balance' => $walletRow->balance ?? null,
            ],
            'package' => [
                'wallet_loaded' => (bool) is_incevio_package_loaded('wallet'),
            ],
            'db_payment_methods' => DB::table('payment_methods')
                ->whereIn('code', ['mpesa', 'emola', 'zcart-wallet'])
                ->select('id', 'code', 'name', 'enabled', 'order')
                ->get(),
            'payment_methods' => $methods,
            'wallet_method' => $methods->firstWhere('code', 'zcart-wallet'),
            'diagnosis' => $this->walletVisibilityDiagnosis($methods, $customer, $walletRow),
        ];
    }

    private function walletVisibilityDiagnosis($methods, $customer, $walletRow): array
    {
        $issues = [];

        if (! DB::table('payment_methods')->where('code', 'zcart-wallet')->where('enabled', 1)->exists()) {
            $issues[] = 'MISSING_OR_DISABLED: payment_methods row for zcart-wallet — run php artisan migrate --force';
        }

        if (! $customer) {
            $issues[] = 'NOT_LOGGED_IN: Cafrepay only shows in the app when the buyer is logged in (accessAllowed)';
        } elseif (! $walletRow) {
            $issues[] = 'NO_WALLET_ROW: customer has no default wallet — run migration 2026_09_09_000004 or ensure_customer_wallet()';
        }

        if (! (bool) get_from_option_table('wallet_checkout')) {
            $issues[] = 'wallet_checkout option is off';
        }

        $walletMethod = $methods->firstWhere('code', 'zcart-wallet');
        if ($walletMethod && ! ($walletMethod['would_show'] ?? false)) {
            $issues[] = 'zcart-wallet filtered out by get_payment_config_info()';
        }

        return [
            'ok' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Checkout the cart and process the payment.
     *
     * @param  CheckoutCartRequest  $request  The request object containing the cart data.
     * @param  Cart  $cart  The cart object.
     * @param  PaymentGateway  $payment  The payment gateway object.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the order information.
     *
     * @throws PaymentFailedException If the payment fails.
     */
    public function checkout(CheckoutCartRequest $request, Cart $cart, PaymentGateway $payment)
    {
        $cart = crosscheckAndUpdateOldCartInfo($request, $cart);

        $cart->loadMissing(['shop', 'shippingAddress']);
        if (! shop_can_accept_sales($cart->shop)) {
            return response()->json([
                'message' => trans('packages.wallet.vendor_sales_require_subscription'),
            ], 422);
        }

        $deliveryRange = app(\App\Services\Cart\CartDeliveryRangeService::class);
        $deliveryRange->annotate(collect([$cart]));
        $request->merge(['delivery_validated' => true]);
        if (! empty($cart->needs_delivery_location)) {
            return response()->json([
                'message' => trans('theme.notify.set_location_for_delivery'),
            ], 422);
        }
        if (! empty($cart->out_of_range)) {
            return response()->json([
                'message' => trans('theme.notify.product_out_of_delivery_range', [
                    'store' => optional($cart->shop)->name ?? 'This store',
                    'distance' => $cart->delivery_distance_km ?? '—',
                    'radius' => $cart->service_radius_km ?? '—',
                ]),
            ], 422);
        }

        $paymentMethod = (string) $request->input('payment_method', '');
        if ($paymentMethod === 'zcart-wallet') {
            $walletCheck = $this->assertCustomerWalletCanPay((float) $cart->grand_total);
            if ($walletCheck !== null) {
                return $walletCheck;
            }
        }

        DB::beginTransaction();

        try {
            // Create the order
            $order = $this->saveOrderFromCart($request, $cart);

            if (is_incevio_package_loaded('dynamic-currency')) {
                // Added Converted Currency Details
                $order['exchange_rate'] = get_dynamic_currency_attr('exchange_rate');
                $order['currency_id'] = get_dynamic_currency_attr('id');
            }

            $receiver = vendor_get_paid_directly() ? 'merchant' : 'platform';

            // When the order has been paid on the app end
            if ($request->input('payment_status') == 'paid' && $request->has('payment_meta')) {
                $response = $payment->verifyPaidPayment();
            } else {
                $paymentMethod = (string) $request->input('payment_method', '');
                if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                    persist_order_checkout_fees($order, $paymentMethod);
                }

                $amountSetter = in_array($paymentMethod, ['mpesa', 'emola'], true)
                    ? fn ($p) => $p->setAmountWithPlatformFee($order->grand_total, $paymentMethod)
                    : fn ($p) => $p->setAmount($order->grand_total);

                $response = $amountSetter($payment->setReceiver($receiver)->setOrderInfo($order))
                    ->setDescription(trans('app.purchase_from', [
                        'marketplace' => get_platform_title(),
                    ]))
                    ->setConfig()
                    ->charge();
            }

            // Gateway returned redirect (e.g. M-Pesa when API detection failed): treat as pending for API
            if ($response instanceof RedirectResponse) {
                $response = (object) ['status' => PaymentService::STATUS_PENDING];
            }

            switch ($response->status) {
                case PaymentService::STATUS_PAID:
                    if (optional($order->paymentMethod)->code !== 'emola') {
                        $order->markAsPaid();
                    }
                    break;

                case PaymentService::STATUS_PENDING:
                    if ($order->paymentMethod->code == 'cod') {
                        $order->order_status_id = Order::STATUS_CONFIRMED;
                        $order->payment_status = Order::PAYMENT_STATUS_UNPAID;
                    } else {
                        $order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
                        $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                    }
                    break;

                case PaymentService::STATUS_ERROR:
                    $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                    $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
                    break;

                default:
                    throw new PaymentFailedException(trans('theme.notify.payment_failed'));
            }

            // throw new \Exception("Error Payment Processing Request");
        } catch (PaymentFailedException $e) {
            DB::rollback();

            Log::warning($request->payment_method.' payment failed: '.$e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
                'cart' => new CartResource($cart),
            ], 403);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error($request->payment_method.' payment failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'cart' => new CartResource($cart),
            ], 403);
        }

        // Everything is fine. Now commit the transaction
        DB::commit();

        // Cart already force-deleted inside saveOrderFromCart.

        // eMola: defer order-placed notifications until Movitel callback confirms payment.
        if (! $this->shouldDeferEmolaConfirmation($order, $response)) {
            safe_dispatch_order_event(new OrderCreated($order), 'OrderCreated');
        }

        $message = trans('theme.notify.order_placed');
        if (! empty($response->paymentNotice)) {
            $message = $response->paymentNotice;
        } elseif (
            isset($response->status) &&
            $response->status === PaymentService::STATUS_PENDING &&
            optional($order->paymentMethod)->code === 'emola'
        ) {
            $message = trans('app.waiting_for_payment');
        }

        return response()->json([
            'message' => $message,
            'payment_notice' => $response->paymentNotice ?? null,
            'order' => new OrderResource($order),
        ], 200);
    }

    /**
     * Checkout every cart for the customer/guest as a separate per-store order.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkoutAll(CheckoutCartRequest $request, PaymentGateway $payment)
    {
        $carts = $this->getShoppingCarts(true)->loadMissing([
            'shop',
            'shippingAddress',
            'inventories',
            'shippingRate.carrier',
            'coupon',
        ]);

        if ($carts->isEmpty()) {
            return response()->json([
                'message' => trans('theme.notify.cart_empty'),
            ], 422);
        }

        $deliveryRange = app(\App\Services\Cart\CartDeliveryRangeService::class);
        $deliveryRange->annotate($carts);
        // Tell saveOrderFromCart to skip a second hyperlocal radius check.
        $request->merge(['delivery_validated' => true]);

        foreach ($carts as $cart) {
            if (! crosscheckCartOwnership($request, $cart)) {
                return response()->json(['message' => trans('api.auth_required')], 403);
            }

            if (! shop_can_accept_sales($cart->shop)) {
                return response()->json([
                    'message' => trans('packages.wallet.vendor_sales_require_subscription'),
                ], 422);
            }

            if (! empty($cart->needs_delivery_location)) {
                return response()->json([
                    'message' => trans('theme.notify.set_location_for_delivery'),
                ], 422);
            }

            if (! empty($cart->out_of_range)) {
                return response()->json([
                    'message' => trans('theme.notify.product_out_of_delivery_range', [
                        'store' => optional($cart->shop)->name ?? 'This store',
                        'distance' => $cart->delivery_distance_km ?? '—',
                        'radius' => $cart->service_radius_km ?? '—',
                    ]),
                ], 422);
            }
        }

        // Same delivery destination required for checkout-all.
        $shipToIds = $carts->map(fn (Cart $cart) => (int) ($cart->ship_to ?? 0))->unique()->values();
        if ($shipToIds->count() > 1 && ! $request->filled('ship_to')) {
            return response()->json([
                'message' => trans('packages.checkout.checkout_all_not_possible'),
            ], 422);
        }

        $paymentMethod = (string) $request->input('payment_method', '');

        // Fail fast for wallet: do not create orders when balance is too low.
        if ($paymentMethod === 'zcart-wallet') {
            $walletCheck = $this->assertCustomerWalletCanPay(
                $carts->sum(fn (Cart $cart) => (float) $cart->grand_total)
            );
            if ($walletCheck !== null) {
                return $walletCheck;
            }
        }

        $customerId = Auth::guard('api')->id() ?: $request->input('customer_id');
        if ($customerId) {
            Cart::whereIn('id', $carts->pluck('id'))->update(['customer_id' => $customerId]);
            $carts->each(fn (Cart $cart) => $cart->customer_id = $customerId);
        }

        DB::beginTransaction();

        $orders = [];
        $response = null;

        try {
            foreach ($carts as $cart) {
                $cart = crosscheckAndUpdateOldCartInfo($request, $cart);
                $orders[] = $this->saveOrderFromCart($request, $cart);
            }

            if (is_incevio_package_loaded('dynamic-currency')) {
                foreach ($orders as $order) {
                    $order['exchange_rate'] = get_dynamic_currency_attr('exchange_rate');
                    $order['currency_id'] = get_dynamic_currency_attr('id');
                }
            }

            // Multi-shop: charge once on the platform when vendors are paid directly.
            $receiver = (vendor_get_paid_directly() && count($orders) > 1)
                ? 'platform'
                : (vendor_get_paid_directly() ? 'merchant' : 'platform');

            if ($request->input('payment_status') == 'paid' && $request->has('payment_meta')) {
                $response = $payment->setOrderInfo($orders)->verifyPaidPayment();
            } else {
                $chargeAmount = 0.0;

                foreach ($orders as $order) {
                    if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                        persist_order_checkout_fees($order, $paymentMethod);
                    }
                }

                if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                    $chargeAmount = get_customer_charge_total_for_orders($orders, $paymentMethod);
                } else {
                    foreach ($orders as $order) {
                        $chargeAmount += (float) $order->grand_total;
                    }
                }

                // Re-check wallet against final charge (after shipping/tax updates).
                if ($paymentMethod === 'zcart-wallet') {
                    $walletCheck = $this->assertCustomerWalletCanPay($chargeAmount);
                    if ($walletCheck !== null) {
                        DB::rollback();

                        return $walletCheck;
                    }
                }

                $paymentBuilder = $payment->setReceiver($receiver)->setOrderInfo($orders);

                if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                    $paymentBuilder->setAmount((int) round($chargeAmount));
                } else {
                    $paymentBuilder->setAmount($chargeAmount);
                }

                $response = $paymentBuilder
                    ->setDescription(trans('app.purchase_from', [
                        'marketplace' => get_platform_title(),
                    ]))
                    ->setConfig()
                    ->charge();
            }

            if ($response instanceof RedirectResponse) {
                $response = (object) ['status' => PaymentService::STATUS_PENDING];
            }

            foreach ($orders as $order) {
                switch ($response->status) {
                    case PaymentService::STATUS_PAID:
                        if (optional($order->paymentMethod)->code !== 'emola') {
                            $order->markAsPaid();
                        }
                        break;

                    case PaymentService::STATUS_PENDING:
                        if ($order->paymentMethod->code == 'cod') {
                            $order->order_status_id = Order::STATUS_CONFIRMED;
                            $order->payment_status = Order::PAYMENT_STATUS_UNPAID;
                        } else {
                            $order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
                            $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                        }
                        $order->save();
                        break;

                    case PaymentService::STATUS_ERROR:
                        $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                        $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
                        $order->save();
                        break;

                    default:
                        throw new PaymentFailedException(trans('theme.notify.payment_failed'));
                }
            }
        } catch (PaymentFailedException $e) {
            DB::rollback();

            Log::warning($request->payment_method.' checkout_all payment failed: '.$e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
                'carts' => CartResource::collection($carts),
            ], 403);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error($request->payment_method.' checkout_all payment failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'carts' => CartResource::collection($carts),
            ], 403);
        }

        DB::commit();

        // Carts are already force-deleted inside saveOrderFromCart.

        foreach ($orders as $order) {
            if (! $this->shouldDeferEmolaConfirmation($order, $response)) {
                safe_dispatch_order_event(new OrderCreated($order), 'OrderCreated');
            }
        }

        $primary = $orders[0];
        $message = trans('theme.notify.order_placed');
        if (! empty($response->paymentNotice)) {
            $message = $response->paymentNotice;
        } elseif (
            isset($response->status) &&
            $response->status === PaymentService::STATUS_PENDING &&
            optional($primary->paymentMethod)->code === 'emola'
        ) {
            $message = trans('app.waiting_for_payment');
        }

        // Light payload for speed — load only what the app needs for confirmation.
        $primary->loadMissing(['shop', 'paymentMethod', 'inventories.image', 'customer']);

        return response()->json([
            'message' => $message,
            'payment_notice' => $response->paymentNotice ?? null,
            'order' => new OrderResource($primary),
            'orders' => collect($orders)->map(function (Order $order) use ($primary) {
                if ($order->id === $primary->id) {
                    return (new OrderResource($order))->resolve();
                }

                // Secondary orders: light resource (avoid N full OrderResource graphs).
                $order->loadMissing(['shop', 'paymentMethod']);

                return (new OrderLightResource($order))->resolve();
            })->values(),
        ], 200);
    }

    /**
     * Return available payment options for the cart.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function paymentOptions(Cart $cart)
    {
        // Get the shop
        $shop = $cart->shop;

        // Get all active payment methods
        $activePaymentMethods = PaymentMethod::active()->get();
        $activePaymentCodes = $activePaymentMethods->pluck('code')->toArray();

        $shop_config = null;
        if (vendor_get_paid_directly()) {
            $activePaymentMethods = $shop->paymentMethods;
            $shop_config = $shop;
        }

        $results = $activePaymentMethods->filter(function ($payment) use ($activePaymentCodes, $shop_config) {
            // Prepaid only — hide Cash on Delivery
            if ($payment->code === 'cod') {
                return false;
            }

            $config = get_payment_config_info($payment->code, $shop_config);
            $isActiveAndHasValidConfig = in_array($payment->code, $activePaymentCodes) && $config;

            if ($isActiveAndHasValidConfig) {
                $inner = $config['config'] ?? null;
                $payment->additional_details = is_array($inner)
                    ? ($inner['additional_details'] ?? $config['msg'] ?? null)
                    : ($config['msg'] ?? null);
            }

            return $isActiveAndHasValidConfig;
        });

        if (request()->boolean('debug_wallet')) {
            Log::info('wallet visibility debug', $this->walletVisibilityDebugData($cart));
        }

        return PaymentMethodResource::collection($results);
    }

    /**
     * Debug wallet/payment visibility for a cart without changing
     * the normal payment options API shape consumed by the app.
     */
    public function paymentOptionsDebug(Cart $cart)
    {
        return response()->json($this->walletVisibilityDebugData($cart));
    }

    /**
     * @return \Illuminate\Http\JsonResponse|null Null when the customer can pay.
     */
    private function assertCustomerWalletCanPay(float $amount)
    {
        $customer = Auth::guard('api')->user();
        if (! $customer) {
            return response()->json([
                'message' => trans('api.auth_required'),
                'error' => trans('api.auth_required'),
            ], 401);
        }

        if (! isset($customer->wallet)) {
            return response()->json([
                'message' => trans('packages.wallet.wallet_empty'),
                'error' => trans('packages.wallet.wallet_empty'),
            ], 422);
        }

        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return null;
        }

        try {
            (new \Incevio\Package\Wallet\Services\CommonService)
                ->verifyWithdraw($customer->wallet, $amount);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: trans('packages.wallet.insufficient_funds'),
                'error' => $e->getMessage() ?: trans('packages.wallet.insufficient_funds'),
            ], 422);
        }

        return null;
    }

    /**
     * Create a Stripe payment intent, given a card number, expiry month & year, and CVC.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stripePaymentIntent(Request $request, Cart $cart)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $newToken = \Stripe\Token::create([
            'card' => [
                'number' => $request->card_number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvc' => $request->cvc,
            ],
        ], ['stripe_account' => config('services.stripe.account_id')]);

        return json_encode($newToken);
    }
}
