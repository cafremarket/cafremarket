<?php

namespace App\Services\Payments;

use App\Contracts\PaymentServiceContract;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentService implements PaymentServiceContract
{
    // Statuses
    const STATUS_INITIATED = 'initiated';        // Default

    const STATUS_PAID = 'paid';

    const STATUS_PENDING = 'pending';

    // const STATUS_VALIDATING = 'validating';
    const STATUS_ERROR = 'error';

    // Transaction types
    const TRNS_TYPE_PURCHASE = 'purchase';

    const TRNS_TYPE_DEPOSIT = 'deposit';

    // Payee types
    const PAYEE_TYPE_CUSTOMER = 'customer';

    const PAYEE_TYPE_SHOP = 'shop';

    const PAYEE_TYPE_GUEST = 'guest';

    // Receivers
    const RECEIVER_PLATFORM = 'platform';

    const RECEIVER_MERCHANT = 'merchant';

    public $request;

    public $payee;

    public $payee_type;

    public $receiver;

    public $order;

    public $fee;

    public $amount;

    public $currency_code;

    public $meta;

    public $description;

    public $sandbox;

    // public $success;
    public $status;

    /** Optional message when payment is pending but gateway reported an issue (e.g. eMola USSD not sent). */
    public ?string $paymentNotice = null;

    public $base_url;

    /**
     * Initiating with basic data
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->receiver = self::RECEIVER_PLATFORM;
        $this->currency_code = get_currency_code();

        // Set the default status
        $this->status = self::STATUS_INITIATED;

        // Set the payee type
        $this->payee_type = self::PAYEE_TYPE_GUEST;

        // Get payee model
        if ($this->request->has('payee')) {
            $this->setPayee($this->request->payee);
        } elseif (Auth::guard('customer')->check()) {
            $this->setPayee(Auth::guard('customer')->user(), self::PAYEE_TYPE_CUSTOMER);
        } elseif (Auth::guard('api')->check()) {
            $this->setPayee(Auth::guard('api')->user(), self::PAYEE_TYPE_CUSTOMER);
        } elseif (Auth::guard('web')->check() && Auth::user()->isMerchant()) {
            $this->setPayee(Auth::user()->owns, self::PAYEE_TYPE_SHOP);
        }
    }

    /**
     * Set the payee
     * return $this
     */
    public function setPayee($payee, $payee_type = self::PAYEE_TYPE_GUEST)
    {
        $this->payee = $payee;

        $this->payee_type = $payee_type;

        return $this;
    }

    /**
     * Set the payable amount
     * return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set charge amount including transaction fee for M-Pesa / eMola (gateway + subscription plan).
     */
    public function setAmountWithPlatformFee(float|int|string $baseAmount, ?string $paymentMethod = null): self
    {
        $method = $paymentMethod
            ?? $this->request?->input('payment_method')
            ?? (optional($this->order)->paymentMethod ? $this->order->paymentMethod->code : null)
            ?? '';

        $shop = optional($this->order)->shop;
        $breakdown = get_customer_transaction_fee((string) $method, $baseAmount, $shop);

        $this->fee = $breakdown['fee'];
        $this->amount = in_array(strtolower((string) $method), ['mpesa', 'emola'], true)
            ? (int) round($breakdown['total'])
            : $breakdown['total'];

        $this->meta = array_merge($this->meta ?? [], [
            'subscription_transaction_fee' => $breakdown['subscription_fee'],
            'customer_transaction_fee' => $breakdown['fee'],
            'payment_base_amount' => $breakdown['base'],
        ]);

        return $this;
    }

    /**
     * Set the description
     * return $this
     */
    public function setDescription($description = '')
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the receiver
     * return $this
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Set order info
     *
     * @param  Order  $order  | array
     * @return self
     */
    public function setOrderInfo($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Return order id or the last order id
     * return $this
     */
    public function getOrderId()
    {
        if ($this->order) {
            if (is_array($this->order)) {
                return implode('-', array_column($this->order, 'id'));
            }

            if (! $this->order instanceof Order) {
                $this->order = Order::findOrFail($this->order);
            }

            return $this->order->id;
        }

        return null;
    }

    /**
     * Set payment gate configs
     * Overwrite on child class
     */
    public function setConfig()
    {
        return $this;
    }

    /**
     * The payment will execute here, overwrite on child class
     */
    public function charge()
    {
        // Set the status as awaiting to process the payment later
        $this->status = self::STATUS_PENDING;

        return $this;
    }
}
