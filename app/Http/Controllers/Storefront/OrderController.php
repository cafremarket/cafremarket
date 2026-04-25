<?php

namespace App\Http\Controllers\Storefront;

use App\Common\ShoppingCart;
use App\Contracts\PaymentServiceContract as PaymentGateway;
use App\Events\Order\OrderCreated;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CheckoutCartRequest;
use App\Http\Requests\Validations\ConfirmGoodsReceivedRequest;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Models\Attachment;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaypalPaymentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    use ShoppingCart;

    /**
     * Encode order number for URL route segments.
     * Order prefixes can contain reserved chars like '#'.
     */
    private function toRouteSafeOrderNumber(?string $orderNumber): string
    {
        return rawurlencode((string) $orderNumber);
    }

    /**
     * Resolve order from gateway callback identifier.
     * Some gateways may return cart/checkout-like references instead of order id.
     */
    private function resolveGatewayOrder($identifier)
    {
        // Direct order id hit
        if (is_numeric($identifier)) {
            $order = Order::withTrashed()->find((int) $identifier);
            if ($order) {
                return $order;
            }
        }

        // Try by order number / payment ref id
        $order = Order::withTrashed()
            ->where('order_number', (string) $identifier)
            ->orWhere('payment_ref_id', (string) $identifier)
            ->latest('id')
            ->first();

        if ($order) {
            return $order;
        }

        // Last resort: latest pending order of current customer (fresh callback)
        if (auth('customer')->check()) {
            return Order::withTrashed()
                ->where('customer_id', auth('customer')->id())
                ->whereIn('payment_status', [
                    Order::PAYMENT_STATUS_UNPAID,
                    Order::PAYMENT_STATUS_PENDING,
                ])
                ->latest('id')
                ->first();
        }

        return null;
    }

    /**
     * Checkout the cart and process the payment.
     *
     * @param  CheckoutCartRequest  $request  The request object containing the cart data.
     * @param  Cart  $cart  The cart object.
     * @param  PaymentGateway  $payment  The payment gateway object.
     * @return \Illuminate\Http\RedirectResponse The response to redirect to the payment gateway or the order complete page.
     *
     * @throws PaymentFailedException If the payment fails.
     */
    public function create(CheckoutCartRequest $request, Cart $cart, PaymentGateway $payment)
    {
        $cart = crosscheckAndUpdateOldCartInfo($request, $cart);

        DB::beginTransaction();

        try {
            $order = $this->saveOrderFromCart($request, $cart);     // Create the order

            $order->fulfilment_type = $request->fulfilment_type;      // Assign the fulfillment type

            $order->currency_id = config('system_settings.currency.id');

            if (is_incevio_package_loaded('dynamic-currency')) {    // Added Converted Currency Details
                $order['exchange_rate'] = get_dynamic_currency_attr('exchange_rate');
                $order['currency_id'] = get_dynamic_currency_attr('id');
            }

            $receiver = vendor_get_paid_directly() ? PaymentService::RECEIVER_MERCHANT : PaymentService::RECEIVER_PLATFORM;

            $response = $payment->setReceiver($receiver)
                ->setOrderInfo($order)
                ->setAmount($order->grand_total)
                ->setDescription(trans('app.purchase_from', [
                    'marketplace' => get_platform_title(),
                ]))
                ->setConfig()
                ->charge();

            // Check if the response needs to redirect to gateways
            if ($response instanceof RedirectResponse) {
                DB::commit();

                return $response;
            }

            switch ($response->status) {
                case PaymentService::STATUS_PAID:
                    $order->markAsPaid();     // Order has been paid
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

                default:
                    throw new PaymentFailedException(trans('theme.notify.payment_failed'));
            }

            // Save the order
            $order->save();
        } catch (Exception $e) {
            DB::rollback(); // Rollback the transaction and log the error

            Log::error($request->payment_method.' payment failed:: '.$e->getMessage());
            Log::error($e);

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        // Everything is fine. Now commit the transaction
        DB::commit();

        // Trigger the Event (do not fail checkout when SMTP is unavailable)
        try {
            event(new OrderCreated($order));
        } catch (Exception $e) {
            Log::warning('OrderCreated event failed: '.$e->getMessage());
        }

        $cart_item_count = cart_item_count();               // Update the cart count

        // $order->load('inventories.product');

        $flashKey = 'success';
        $flashMessage = trans('theme.notify.order_placed');

        // For async wallets like eMola, order exists but payment can still be pending.
        if (
            isset($response->status) &&
            $response->status === PaymentService::STATUS_PENDING &&
            optional($order->paymentMethod)->code === 'emola'
        ) {
            $flashKey = 'warning';
            $flashMessage = trans('app.waiting_for_payment');
        }

        return redirect()->route('order.detail.number', ['order_number' => $this->toRouteSafeOrderNumber($order->order_number)])
            ->with($flashKey, $flashMessage);
    }

    /**
     * Return from payment gateways with payment success
     *
     * @param  \App\Models\Order  $order
     * @param  string  $gateway  Payment gateway code
     * @return \Illuminate\Http\Response
     */
    public function paymentGatewaySuccessResponse(Request $request, $gateway, $order)
    {
        // Normalize order identifier from callback.
        $resolved = $this->resolveGatewayOrder($order);
        if ($resolved) {
            $order = $resolved->id;
        }

        // Verify Payment Gateway Calls
        if (! $this->verifyPaymentGatewayCalls($request, $gateway)) {
            return redirect()->route('payment.failed', $order);
        }

        if ($gateway == 'paypal') {
            // Log::info($request->all());

            $receiver = vendor_get_paid_directly() ? PaymentService::RECEIVER_MERCHANT : PaymentService::RECEIVER_PLATFORM;

            try {
                $service = new PaypalPaymentService($request);
                $response = $service->setReceiver($receiver)->setConfig()->paymentExecution($request);

                // If the payment failed
                if ($response->status != PaymentService::STATUS_PAID) {
                    return redirect()->route('payment.failed', $order);
                }
            } catch (Exception $e) {
                Log::error('Paypal payment failed on execution step:: ');
                Log::error($e->getMessage());
            }
        }
        // Order has been paid

        // OneCheckout plugin
        $orders = explode('-', $order);
        $order = count($orders) > 1 ? $orders : $order;
        if (is_array($order)) {
            foreach ($order as $id) {
                $temp = Order::withTrashed()->findOrFail($id);

                $temp->markAsPaid();
            }

            $order = $temp;
        } else {
            // Single order
            if (! $order instanceof Order) {
                $order = Order::withTrashed()->findOrFail($order);
            }

            $order->markAsPaid();
        }

        // Trigger the Event (do not fail redirect if mail server is down)
        try {
            event(new OrderCreated($order));
        } catch (Exception $e) {
            Log::warning('OrderCreated event failed: '.$e->getMessage());
        }

        return redirect()->route('order.detail.number', ['order_number' => $this->toRouteSafeOrderNumber($order->order_number)])
            ->with('success', trans('theme.notify.order_placed'));
    }

    /**
     * Payment failed or cancelled
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function paymentFailed(Request $request, $order)
    {
        $resolved = $this->resolveGatewayOrder($order);
        if ($resolved) {
            $order = $resolved->id;
        }

        if (! is_array($order)) {
            $orders = explode('-', $order);
            $order = count($orders) > 1 ? $orders : $order;
        }

        if (is_array($order)) {
            $cart = [];
            foreach ($order as $temp) {
                $cart[] = $this->moveAllItemsToCartAgain($temp, true);
            }
        } else {
            $cart = $this->moveAllItemsToCartAgain($order, true);
        }

        // Set failed message
        $msg = trans('theme.notify.payment_failed');

        $errors = $request->session()->get('errors');
        if (isset($errors) && count($errors) > 0) {
            $msg = $errors->all()[0];
        }

        if (is_array($cart)) {
            return redirect()->route('cart.index')->with('error', $msg);
        }

        return redirect()->route('cart.checkout', $cart)->with('error', $msg);
    }

    /**
     * Display order detail page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function detail(OrderDetailRequest $request, Order $order)
    {
        $order->load(['inventories.image', 'conversation.replies.attachments']);

        return view('theme::order_detail', compact('order'));
    }

    /**
     * Display order detail by order number to avoid cart-id confusion.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function detailByOrderNumber(Request $request, $order_number)
    {
        $customer = auth('customer')->user();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $order = Order::withTrashed()
            ->where('order_number', $order_number)
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->firstOrFail();

        $order->load(['inventories.image', 'conversation.replies.attachments']);

        return view('theme::order_detail', compact('order'));
    }

    /**
     * Buyer confirmed goods received
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function goods_received(ConfirmGoodsReceivedRequest $request, Order $order)
    {
        $order->mark_as_goods_received();

        return redirect()->route('order.feedback', $order)
            ->with('success', trans('theme.notify.order_updated'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function invoice(Order $order)
    {
        // $this->authorize('view', $order); // Check permission

        return $order->invoice(); // Download the invoice
    }

    public function downloadShippingLabel(Order $order)
    {
        return $order->shippingLabelPdf();
    }

    /**
     * Track order shipping.
     *
     *
     * @return \Illuminate\View\View
     */
    public function track(Request $request, Order $order)
    {
        return view('theme::order_tracking', compact('order'));
    }

    /**
     * Order again by moving all items into th cart
     */
    public function again(Request $request, Order $order)
    {
        $cart = $this->moveAllItemsToCartAgain($order);

        // If any waring returns from cart, normally out of stock items
        if (Session::has('warning')) {
            return redirect()->route('cart.index');
        }

        return redirect()->route('cart.checkout', $cart)
            ->with('success', trans('theme.notify.cart_updated'));
    }

    /**
     * Verify Payment Gateway Calls
     *
     * @param  string  $gateway
     * @return bool
     */
    private function verifyPaymentGatewayCalls(Request $request, $gateway)
    {
        switch ($gateway) {
            case 'paypal':
                return $request->has('token') && $request->has('PayerID');
                // return $request->has('token') && $request->has('paymentId') && $request->has('PayerID');

            case 'mollie':
                $mollie = new \Incevio\Package\Mollie\Services\MolliePaymentService($request);
                $mollie->setConfig();
                $mollie->verifyPaidPayment();

                return $mollie->status == PaymentService::STATUS_PAID;

            case 'bkash':
                if ($request->status != 'success') {
                    return false;
                }

                $bkash = new \Incevio\Package\Bkash\Services\BkashPaymentService($request);
                $bkash->setConfig();
                $bkash->verifyPaidPayment();

                return $bkash->status == PaymentService::STATUS_PAID;

            case 'stripeWeb':
                $stripeWeb = new \App\Services\Payments\StripeWebPaymentService($request);
                $stripeWeb->setConfig();
                $stripeWeb->verifyPaidPayment();

                return $stripeWeb->status == PaymentService::STATUS_PAID;

            case 'paytm':
                $paytm = new \Incevio\Package\Paytm\Services\PaytmPaymentService($request);
                $paytm->setConfig();
                $paytm->verifyPaidPayment();

                return $paytm->status == PaymentService::STATUS_PAID;
        }

        return false;
    }

    private function logErrors($error, $feedback)
    {
        Log::error($error);

        // Set error messages:
        // $error = new \Illuminate\Support\MessageBag();
        // $error->add('errors', $feedback);

        return $error;
    }

    /**
     * download attachment file
     *
     *
     * @return file
     */
    public function download(Request $request, Attachment $attachment, $order, Inventory $inventory)
    {
        // Check the existence of the file
        if (! Storage::exists($attachment->path)) {
            return back()->with('error', trans('messages.file_not_exist'));
        }

        $currentValue = DB::table('order_items')
            ->where('order_id', $order)
            ->where('inventory_id', $inventory->id)
            ->value('download');

        if ($currentValue >= $inventory->download_limit) {
            $msg = trans('messages.reached_download_maximum_limit');

            if (url()->previous()) {
                return back()->with('error', $msg);
            }

            return redirect('/')->with('error', $msg);
        }

        // Increment the download count
        DB::table('order_items')
            ->where('order_id', $order)
            ->where('inventory_id', $inventory->id)
            ->increment('download');

        return Storage::download($attachment->path, $attachment->name);
    }
}
