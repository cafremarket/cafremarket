<?php

namespace App\Http\Controllers\Api;

use App\Common\ShoppingCart;
use App\Contracts\PaymentServiceContract as PaymentGateway;
use App\Events\Order\OrderCreated;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CheckoutCartRequest;
use App\Http\Resources\CartResource;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    use ShoppingCart;

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
                    $feeBreakdown = get_platform_payment_fee($paymentMethod, $order->grand_total);
                    $order->platform_payment_fee = $feeBreakdown['fee'];
                    $order->save();
                }

                $amountSetter = in_array($paymentMethod, ['mpesa', 'emola'], true)
                    ? fn ($p) => $p->setAmountWithPlatformFee($order->grand_total, $paymentMethod)
                    : fn ($p) => $p->setAmount($order->grand_total);

                $response = $amountSetter($payment->setReceiver($receiver)
                    ->setOrderInfo($order))
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

        // Order confirmed – only mark as paid when payment gateway returned STATUS_PAID (eMola uses callback instead).
        if ($response->status === PaymentService::STATUS_PAID && optional($order->paymentMethod)->code !== 'emola') {
            $order->markAsPaid();
        }

        // Everything is fine. Now commit the transaction
        DB::commit();

        // Delete the cart
        $cart->forceDelete();

        // eMola: defer order-placed notifications until Movitel callback confirms payment.
        if (! $this->shouldDeferEmolaConfirmation($order, $response)) {
            try {
                event(new OrderCreated($order));
            } catch (\Exception $e) {
                Log::warning('OrderCreated event failed: '.$e->getMessage());
            }
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

            $config = get_payment_config_info($payment->code, $shop_config);
            $isActiveAndHasValidConfig = in_array($payment->code, $activePaymentCodes) && $config;

            if ($isActiveAndHasValidConfig) {
                $payment->additional_details = $config['config']['additional_details'] ?? $config['msg'];
            }

            return $isActiveAndHasValidConfig;
        });

        return PaymentMethodResource::collection($results);
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
