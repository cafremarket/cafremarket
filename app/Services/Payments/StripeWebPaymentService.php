<?php

namespace App\Services\Payments;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Stripe\Stripe;

class StripeWebPaymentService extends PaymentService
{
    private $secret_key;

    private $payment_id;

    private $stripe_account_id;

    public $redirect_url;

    public $fee;

    public function __construct(Request $request)
    {
        $this->secret_key = config('services.stripe.secret');
    }

    /**
     * Sets The amount in proper format for api
     *
     * @return StripeWebPaymentService
     */
    public function setAmount($amount)
    {
        $this->amount = number_format((float) $amount, 2, '.', '');

        return $this;
    }

    public function setConfig()
    {
        if ($this->order && $this->receiver == 'merchant') {
            $this->stripe_account_id = $this->order->shop->config->stripe->stripe_user_id;
        } else {
            $this->stripe_account_id = config('services.stripe.account_id');
        }

        return $this;
    }

    /**
     * Initiate payment process
     *
     * @return \Illuminate\HTTP\RedirectResponse
     */
    public function charge()
    {
        $this->status = self::STATUS_PENDING;

        try {
            $checkoutSession = $this->createPayment();
        } catch (\Exception $e) {
            Log::error('StripeWebPaymentService::charge() - Error while creating payment: '.$e->getMessage());

            $this->status = self::STATUS_ERROR;

            return $this;
        }

        $this->status = self::STATUS_INITIATED;

        Session::put('payment_id', $this->payment_id);

        return redirect()->away($checkoutSession->url);
    }

    /**
     * Check if payment was successful
     *
     * @return StripeWebPaymentService
     */
    public function verifyPaidPayment()
    {
        $paymentStatus = $this->queryPayment()['payment_status'];

        $this->status = $paymentStatus === 'paid' ? self::STATUS_PAID : self::STATUS_ERROR;

        return $this;
    }

    /**
     * Creates a Stripe checkout session and redirects the user to it.
     *
     * @return \Stripe\Checkout\Session
     *
     * @throws \Exception if there is an error in creating the checkout session
     */
    public function createPayment()
    {
        $stripeClient = new \Stripe\StripeClient($this->secret_key);

        // Set application fee if merchant get paid
        if (
            $this->receiver == 'merchant' &&
            $this->order && $this->payee instanceof Customer
        ) {
            // Set platform fee for order if not already set
            if (! $this->fee) {
                $this->setPlatformFee(getPlatformFeeForOrder($this->order));
            }
        }

        $checkoutSessionParams = [
            'mode' => 'payment',
            'line_items' => $this->getLineItems(),
            'success_url' => route('payment.success', ['gateway' => 'stripeWeb', 'order' => $this->getOrderId()]),
            'cancel_url' => route('payment.failed', ['order' => $this->getOrderId()]),
        ];

        if ($this->receiver == 'merchant') {
            $checkoutSessionParams['payment_intent_data'] = [
                'application_fee_amount' => $this->fee ?? 0,
                'on_behalf_of' => $this->stripe_account_id,
                'transfer_data' => ['destination' => $this->stripe_account_id],
            ];
        }

        // Add discount
        if ($this->order->discount > 0) {
            $discount_id = $stripeClient->coupons->create([
                'duration' => 'once',
                'id' => 'discount'.$this->order->id,
                'amount_off' => $this->order->discount,
            ]);

            $checkoutSessionParams['discounts'] = [[
                'coupon' => $discount_id,
            ]];
        }

        try {
            $checkoutSession = $stripeClient->checkout->sessions->create($checkoutSessionParams);
        } catch (\Exception $e) {
            Log::error('StripeWebPaymentService::createPayment() - Error while creating checkout session: '.$e->getMessage());

            throw $e;
        }

        $this->payment_id = $checkoutSession->id;

        return $checkoutSession;
    }

    /**
     * Query Stripe for the status of the current payment.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function queryPayment()
    {
        \Stripe\Stripe::setApiKey($this->secret_key);

        $session = \Stripe\Checkout\Session::retrieve(session('payment_id'));

        if ($session->payment_status === 'paid') {
            return $session->toArray();
        }

        throw new \Exception('Payment not completed');
    }

    /**
     * Generates an array of line items for each order item. Each line item
     * contains the price data and quantity of the order item.
     *
     * @return array An array of line items.
     */
    private function getLineItems()
    {
        $lineItems = [];
        $currency_code = $this->order->currency_id ? strtolower($this->order->currency->iso_code) : get_currency_code();

        foreach ($this->order->items()->get() as $orderItem) {
            $lineItem = [
                'price_data' => [
                    'currency' => $currency_code,
                    'unit_amount' => get_cent_from_dollar($orderItem->unit_price),
                    'product_data' => [
                        'name' => $orderItem->inventory->title,
                        'description' => Str::limit($orderItem->inventory->description, 150),
                    ],
                ],
                'quantity' => $orderItem->quantity,
            ];
            $lineItems[] = $lineItem;
        }

        if ($this->order->packaging > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency_code,
                    'unit_amount' => get_cent_from_dollar($this->order->packaging),
                    'product_data' => [
                        'name' => trans('app.packaging_cost'),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        if ($this->order->handling > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency_code,
                    'unit_amount' => get_cent_from_dollar($this->order->handling),
                    'product_data' => [
                        'name' => trans('app.handling_cost'),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        if ($this->order->taxes > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency_code,
                    'unit_amount' => get_cent_from_dollar($this->order->taxes),
                    'product_data' => [
                        'name' => trans('app.taxes'),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        if ($this->order->shipping > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency_code,
                    'unit_amount' => get_cent_from_dollar($this->order->shipping),
                    'product_data' => [
                        'name' => trans('app-static-customer.shipping_cost'),
                    ],
                ],
                'quantity' => 1,
            ];
        }

        return $lineItems;
    }

    public function setPlatformFee($fee = 0)
    {
        $this->fee = $fee > 0 ? get_cent_from_dollar($fee) : 0;

        return $this;
    }
}
