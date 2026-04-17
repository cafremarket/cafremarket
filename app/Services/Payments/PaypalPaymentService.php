<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalPaymentService extends PaymentService
{
    private $client_id;

    private $client_secret;

    private $app_id;

    private $mode;

    private $currency;

    private $provider;

    private $returnUrl;

    private $cancelUrl;

    public $response;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->mode = config('paypal.mode');
        $this->client_id = config('paypal.'.$this->mode.'.client_id');
        $this->client_secret = config('paypal.'.$this->mode.'.client_secret');
        $this->app_id = config('paypal.'.$this->mode.'.app_id');
    }

    public function setConfig()
    {
        $this->currency = Session::get('currency.'.'iso_code') ?? get_system_currency();

        $provider = new PayPalClient;
        $provider->getAccessToken();

        // Get the vendor configs
        if ($this->receiver == 'merchant') {
            $vendorConfig = $this->order->shop->config->paypal;

            $this->client_id = $vendorConfig->client_id;
            $this->client_secret = $vendorConfig->secret;

            $this->mode = $vendorConfig->sandbox == 1 ? 'sandbox' : 'live';
        }

        $config = [
            'mode' => $this->mode,
            'live' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'app_id' => $this->app_id,
            ],
            'sandbox' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'app_id' => $this->app_id,
            ],
            'payment_action' => 'Sale',
            'currency' => Session::get('currency.'.'iso_code') ?? get_system_currency(),
            'notify_url' => config('app.url').'/paypal/notify',
            'locale' => Session::get('locale') ?? 'en_US',
            'validate_ssl' => true,
        ];

        $provider->setApiCredentials($config);

        $this->provider = $provider;

        if ($this->order) {
            $items = [];
            $taxes = 0;
            $packaging = 0;
            $discount = 0;
            $shipping = 0;
            $total = 0;

            if (is_array($this->order)) {
                foreach ($this->order as $tOrder) {
                    $taxes += $tOrder->taxes;
                    $packaging += $tOrder->packaging;
                    $discount += $tOrder->discount;
                    $shipping += $tOrder->get_shipping_cost();

                    // foreach ($tOrder->inventories as $item) {
                    //     $total += (format_price_for_paypal($item->pivot->unit_price) * $item->pivot->quantity);

                    //     $items[] = $this->setPayPalItem(
                    //         $item->title,
                    //         $item->pivot->unit_price,
                    //         $item->pivot->quantity,
                    //         $tOrder->taxrate,
                    //         $item->pivot->item_description
                    //     );
                    // }
                }
            } else {
                $taxes = $this->order->taxes;
                $packaging = $this->order->packaging;
                $discount = $this->order->discount;
                $shipping = $this->order->get_shipping_cost();

                // foreach ($this->order->inventories as $item) {
                //     $total += (format_price_for_paypal($item->pivot->unit_price) * $item->pivot->quantity);

                //     $items[] = $this->setPayPalItem(
                //         $item->title,
                //         $item->pivot->unit_price,
                //         $item->pivot->quantity,
                //         $this->order->taxrate,
                //         $item->pivot->item_description
                //     );
                // }
            }

            $paymentMethod = is_array($this->order) ? $this->order[0]->paymentMethod : $this->order->paymentMethod;

            $this->returnUrl = route('payment.success', ['gateway' => $paymentMethod->code, 'order' => $this->getOrderId()]);
            $this->cancelUrl = route('payment.failed', ['order' => $this->getOrderId()]);

            // $details = new Details;
            // $details->setShipping($shipping)
            //     ->setTax($taxes)
            //     ->setGiftWrap($packaging)
            //     ->setShippingDiscount($discount)
            //     ->setSubtotal(format_price_for_paypal($total)); // total of items prices
        } else {
            // $items[] = $this->setPayPalItem($this->description, $this->amount, 1, 0, $this->description);
            // $response = $provider->addProduct('Demo Product', 'Demo Product', 'SERVICE', 'SOFTWARE');

            $this->returnUrl = route('wallet.deposit.paypal.success');
            $this->cancelUrl = route('wallet.deposit.failed');

            // return redirect()->to($order->getApprovalLink());

            // $details = new Details;
            // $details->setShipping(0)->setTax(0)
            //     ->setSubtotal($this->amount); // total of items prices
        }

        return $this;

        // $itemList = new ItemList;
        // $itemList->setItems($items);

        // // Set Redirect Urls
        // $redirectUrls = new RedirectUrls;
        // $redirectUrls->setReturnUrl($returnUrl)
        //     ->setCancelUrl($cancelUrl);

        // $this->setDescription($details);

        // $this->redirectUrls = $redirectUrls;

        // $payer = new Payer;
        // $this->payee = $payer->setPaymentMethod('paypal');

        // // Payment Amount
        // $amount = new Amount;
        // $amount->setCurrency(get_currency_code())
        //     ->setTotal($this->amount)
        //     ->setDetails($this->description);

        // // ### Transaction
        // // A transaction defines the contract of a payment - what is the payment for and who
        // // is fulfilling it. Transaction is created with a `Payee` and `Amount` types
        // $transaction = new Transaction;
        // $this->transaction = $transaction->setAmount($amount)
        //     ->setItemList($itemList);
        // // ->setInvoiceNumber($this->order->order_number)
        // // ->setDescription($this->description);

        // return $this;
    }

    public function charge()
    {
        // Set Items
        $data = [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => $this->returnUrl,
                'cancel_url' => $this->cancelUrl,
            ],
            'purchase_units' => [
                0 => [
                    'amount' => [
                        'currency_code' => $this->currency,
                        'value' => $this->amount,
                    ],
                ],
            ],
        ];

        $payPalOrder = $this->provider->createOrder($data);

        if (isset($payPalOrder['id']) && $payPalOrder['id'] != null) {
            foreach ($payPalOrder['links'] as $link) {
                if ($link['rel'] == 'approve') {
                    return redirect()->away($link['href']);  // redirect to approve href
                }
            }

            return redirect()->route('wallet.deposit.failed')->with('error', trans('message.something_went_wrong'));
        }

        if (isset($payPalOrder['error']['message'])) {
            throw new \Exception($payPalOrder['error']['message']);
        }

        // $payment = new Payment;
        // $payment->setIntent('sale')
        //     ->setPayer($this->payee)
        //     ->setTransactions([$this->transaction])
        //     ->setRedirectUrls($this->redirectUrls);

        // $payment->create($this->api_context);

        // return redirect()->to($payment->getApprovalLink());
        return $this;
    }

    public function paymentExecution($request)
    {
        // $provider = new PayPalClient;
        // $provider->setApiCredentials(config('paypal'));
        // $provider->getAccessToken();

        // $payment = Payment::get($paymentId, $this->api_context);

        // Execute the payment;
        try {
            // $paymentExecution = new PaymentExecution;
            // $paymentExecution->setPayerId($payerID);
            // $payment->execute($paymentExecution, $this->api_context);
            $response = $this->provider->capturePaymentOrder($request['token']);
            // Log::info($response);

            if (isset($response['status']) && $response['status'] == 'COMPLETED') {
                $this->status = self::STATUS_PAID;
                // return redirect()
                //     ->route('createTransaction')
                //     ->with('success', 'Transaction complete.');
            } else {
                $this->status = self::STATUS_ERROR;
                // return redirect()
                //     ->route('createTransaction')
                //     ->with('error', $response['message'] ?? 'Something went wrong.');
            }

            // $this->status = self::STATUS_PAID;
            $this->response = $response;
        } catch (\Exception $e) {
            $this->status = self::STATUS_ERROR;
            throw new \Exception($e->getMessage());
            // return $ex;
        }

        return $this;
    }

    public function setAmount($amount)
    {
        $this->amount = format_price_for_paypal($amount);

        return $this;
    }

    private function setPayPalItem($title, $unit_price, $quantity = 1, $taxrate = 0, $description = '')
    {
        return [
            'name' => $title,
            'description' => $description,
            'unit_amount' => [
                'currency_code' => get_currency_code(),
                'value' => $unit_price,
            ],
            'quantity' => $quantity,
        ];
    }

    // private function setPayPalItem($title, $unit_price, $quantity = 1, $taxrate = 0, $description = '')
    // {
    //     // Set Items
    //     $data = [
    //         'intent' => 'CAPTURE',
    //         'application_context' => [
    //             'return_url' => route('successTransaction'),
    //             'cancel_url' => route('cancelTransaction'),
    //         ],
    //         'purchase_units' => [
    //             0 => [
    //                 'amount' => [
    //                     'currency_code' => 'USD',
    //                     'value' => $unit_price * $quantity,
    //                 ],
    //             ],
    //         ],
    //     ];

    //     $order = $provider->createOrder($data);

    //     $tempItem = new Item;

    //     return $tempItem->setName($title)
    //         ->setDescription($description)
    //         ->setQuantity($quantity)
    //         ->setCurrency(get_currency_code())
    //         ->setTax($taxrate > 0 ? format_price_for_paypal($taxrate) : 0)
    //         ->setPrice(format_price_for_paypal($unit_price));
    // }

    /**
     * Verify Paid Payment
     */
    // public function verifyPaidPayment()
    // {
    //     $payment_meta = json_decode($this->request->input('payment_meta'));

    //     try {
    //         if ($payment_meta->paymentId && $payment_meta->payerID) {
    //             // Verify the payment;
    //             $this->paymentExecution($payment_meta->paymentId, $payment_meta->payerID);
    //             $this->status = self::STATUS_PAID;
    //         }
    //     } catch (\Exception $e) {
    //         $this->status = self::STATUS_ERROR;

    //         Log::error($e);

    //         throw new \Exception($e->getMessage());
    //     }

    //     return $this;
    // }
}
