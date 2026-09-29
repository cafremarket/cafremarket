<?php

namespace App\Services\Payments;

use App\Common\ShoppingCart;
use App\Events\Order\OrderCreated;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\Emola\EmolaClient;
use App\Services\Emola\EmolaDailyLimit;
use App\Services\Emola\EmolaSpec;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Incevio\Package\MPesa\Http\Requests\HttpRequest as MPesaClient;

/**
 * Checkout for asynchronous gateways (M-Pesa, eMola).
 *
 * The carts are left untouched while the customer approves the payment on the
 * phone. Orders are created from the carts only after the gateway confirms the
 * payment (callback, status query or synchronous M-Pesa success).
 */
class CheckoutPaymentIntentService
{
    use ShoppingCart;

    /** Request fields that saveOrderFromCart() reads, kept to rebuild it later. */
    const PAYLOAD_FIELDS = [
        'ship_to', 'fulfilment_type', 'warehouse_id', 'payment_method', 'payment_method_id',
        'shipping_address', 'email', 'phone', 'buyer_note', 'device_id', 'customer_id',
        'customer_latitude', 'customer_longitude', 'latitude', 'longitude',
    ];

    /** M-Pesa response codes that do not mean a final failure. */
    const MPESA_UNDECIDED_CODES = ['INS-9', 'INS-23'];

    public function __construct(private readonly EmolaClient $emola)
    {
    }

    /**
     * Price the carts and store a payment intent. No order and no gateway call yet.
     *
     * @param  Collection<int, Cart>  $carts
     *
     * @throws PaymentFailedException
     */
    public function create(Request $request, Collection $carts, bool $checkoutAll, string $channel): PaymentIntent
    {
        $method = (string) $request->input('payment_method');

        if ($carts->isEmpty()) {
            throw new PaymentFailedException(trans('theme.notify.cart_empty'));
        }

        $base = 0.0;
        $fee = 0.0;
        $total = 0.0;

        foreach ($carts as $cart) {
            $cart->loadMissing('shop');
            $this->applyCheckoutFulfilment($request, $cart);
            $breakdown = get_customer_transaction_fee($method, $cart->calculate_grand_total(), $cart->shop);

            $base += (float) $breakdown['base'];
            $fee += (float) $breakdown['fee'];
            $total += (float) $breakdown['total'];
        }

        $amount = (int) round($total);

        if ($amount < 1) {
            throw new PaymentFailedException(trans('api.invalid_amount'));
        }

        if ($method === 'emola') {
            EmolaSpec::formatTransAmount($amount, EmolaSpec::CONTEXT_ORDER); // Throws for out-of-range amounts
        }

        $cartIds = $carts->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $msisdn = $this->msisdnFrom($request, $method);
        $customerId = $this->getCartOwnerId($request, $carts->first());

        // Double submit: hand back the intent that is already running for these carts.
        $running = PaymentIntent::open()
            ->where('customer_id', $customerId)
            ->where('payment_method', $method)
            ->where('msisdn', $msisdn)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->latest('id')
            ->get()
            ->first(fn (PaymentIntent $intent) => $intent->cart_ids == $cartIds && (int) $intent->amount === $amount);

        if ($running) {
            return $running;
        }

        return PaymentIntent::create([
            'customer_id' => $customerId,
            'payment_method' => $method,
            'payment_method_id' => $request->input('payment_method_id') ?: get_id_of_model('payment_methods', 'code', $method),
            'channel' => $channel,
            'checkout_all' => $checkoutAll,
            'cart_ids' => $cartIds,
            'amount' => $amount,
            'fee' => round($fee, 2),
            'currency_code' => get_currency_code(),
            'status' => PaymentIntent::STATUS_CREATED,
            'msisdn' => $msisdn,
            'payload' => $this->payloadFrom($request, $customerId, round($base, 2)),
        ]);
    }

    /**
     * Send the payment request to the customer's phone. Runs once per intent.
     */
    public function initiate(PaymentIntent $intent): PaymentIntent
    {
        $claimed = PaymentIntent::whereKey($intent->id)
            ->where('status', PaymentIntent::STATUS_CREATED)
            ->update(['status' => PaymentIntent::STATUS_PROCESSING, 'updated_at' => now()]);

        if (! $claimed) {
            return $intent->refresh(); // Already sent by another request
        }

        $intent->refresh();

        try {
            if ($intent->payment_method === 'mpesa') {
                $this->pushMpesa($intent);
            } elseif ($intent->payment_method === 'emola') {
                $this->pushEmola($intent);
            } else {
                throw new PaymentFailedException(trans('theme.notify.payment_failed'));
            }
        } catch (PaymentFailedException $e) {
            $this->markFailed($intent, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Payment intent push failed', [
                'intent' => $intent->uuid,
                'method' => $intent->payment_method,
                'error' => $e->getMessage(),
            ]);

            // The gateway may have received the request before the error, so keep waiting.
            $intent->refresh();
            if ($intent->gateway_ref || $intent->emola_trans_id) {
                $this->markWaiting($intent);
            } else {
                $this->markFailed($intent, trans('theme.notify.payment_failed'));
            }
        }

        return $intent->refresh();
    }

    /**
     * Poll hook: ask the gateway when the callback is late, retry order creation
     * after a paid-but-not-completed hiccup, and time out stale intents.
     */
    public function refresh(PaymentIntent $intent, bool $force = false): PaymentIntent
    {
        if ($intent->status === PaymentIntent::STATUS_PAID) {
            return $this->completeOrders($intent);
        }

        if (! in_array($intent->status, [PaymentIntent::STATUS_PENDING, PaymentIntent::STATUS_PROCESSING], true)) {
            if ($intent->status === PaymentIntent::STATUS_CREATED && $intent->isExpiredByTime()) {
                $intent->update(['status' => PaymentIntent::STATUS_EXPIRED]);
            }

            return $intent;
        }

        $throttleKey = 'payment_intent_query_'.$intent->id;
        if ($force || ! Cache::has($throttleKey)) {
            Cache::put($throttleKey, 1, now()->addSeconds(8));
            $this->queryGateway($intent);
            $intent->refresh();
        }

        if ($intent->isOpen() && $intent->isExpiredByTime()) {
            $intent->update([
                'status' => PaymentIntent::STATUS_EXPIRED,
                'message' => trans('theme.payment_wait.expired'),
            ]);
        }

        return $intent->refresh();
    }

    /**
     * Gateway confirmed the payment. A confirmation always wins, even after the
     * customer cancelled or the waiting screen timed out, because the money is taken.
     */
    public function markPaid(PaymentIntent $intent, array $attributes = []): PaymentIntent
    {
        DB::transaction(function () use ($intent, $attributes) {
            $locked = PaymentIntent::whereKey($intent->id)->lockForUpdate()->first();

            if (in_array($locked->status, [PaymentIntent::STATUS_PAID, PaymentIntent::STATUS_COMPLETED], true)) {
                return;
            }

            $locked->fill($attributes);
            $locked->status = PaymentIntent::STATUS_PAID;
            $locked->paid_at = now();
            $locked->save();
        });

        return $this->completeOrders($intent->refresh());
    }

    public function markFailed(PaymentIntent $intent, ?string $message = null, array $attributes = []): PaymentIntent
    {
        PaymentIntent::whereKey($intent->id)
            ->whereNotIn('status', [PaymentIntent::STATUS_PAID, PaymentIntent::STATUS_COMPLETED])
            ->update(array_merge($attributes, [
                'status' => PaymentIntent::STATUS_FAILED,
                'message' => $message ?: trans('theme.payment_wait.failed'),
                'updated_at' => now(),
            ]));

        return $intent->refresh();
    }

    public function cancel(PaymentIntent $intent): PaymentIntent
    {
        PaymentIntent::whereKey($intent->id)
            ->whereIn('status', [PaymentIntent::STATUS_CREATED, PaymentIntent::STATUS_PROCESSING, PaymentIntent::STATUS_PENDING])
            ->update([
                'status' => PaymentIntent::STATUS_CANCELLED,
                'message' => trans('theme.payment_wait.cancelled'),
                'updated_at' => now(),
            ]);

        return $intent->refresh();
    }

    /**
     * Start over after a failure: a fresh intent (the old one keeps its gateway
     * references so a late confirmation can still be matched).
     *
     * @throws PaymentFailedException
     */
    public function retry(PaymentIntent $intent, ?string $msisdn = null): PaymentIntent
    {
        if ($intent->isOpen() || in_array($intent->status, [PaymentIntent::STATUS_PAID, PaymentIntent::STATUS_COMPLETED], true)) {
            return $intent;
        }

        $carts = Cart::whereIn('id', $intent->cart_ids)->get();
        if ($carts->count() !== count($intent->cart_ids)) {
            throw new PaymentFailedException(trans('theme.payment_wait.carts_changed'));
        }

        $retry = $intent->replicate([
            'uuid', 'status', 'gateway_ref', 'emola_trans_id', 'emola_ref_no', 'emola_request_id',
            'gateway_code', 'message', 'order_ids', 'expires_at', 'paid_at', 'completed_at',
        ]);
        $retry->status = PaymentIntent::STATUS_CREATED;

        if ($msisdn) {
            $retry->msisdn = $intent->payment_method === 'emola' ? EmolaSpec::normalizeMsisdn($msisdn) : preg_replace('/\s+/', '', $msisdn);
        }

        $retry->save();

        return $retry;
    }

    /**
     * M-Pesa callback. Returns false when the reference belongs to something else.
     */
    public function handleMpesaCallback(string $refId, bool $success, ?string $code = null): bool
    {
        $intent = PaymentIntent::where('gateway_ref', $refId)->latest('id')->first();

        if (! $intent || $intent->payment_method !== 'mpesa') {
            return false;
        }

        if ($success) {
            $this->markPaid($intent, ['gateway_code' => $code]);
        } elseif (! in_array($code, self::MPESA_UNDECIDED_CODES, true)) {
            $this->markFailed($intent, trans('mpesa::lang.payment_not_updated'), ['gateway_code' => $code]);
        }

        return true;
    }

    /**
     * eMola (Movitel) callback. Returns false when no intent matches.
     *
     * @param  array{reqeustId: string, transId: string, refNo: string, errorCode: string, message: string}  $data
     */
    public function handleEmolaCallback(array $data): bool
    {
        $intent = PaymentIntent::query()
            ->where(function ($q) use ($data) {
                $q->where('emola_trans_id', $data['transId'])
                    ->orWhere('emola_ref_no', $data['refNo']);
            })
            ->latest('id')
            ->first();

        if (! $intent) {
            return false;
        }

        $attributes = [
            'emola_request_id' => $data['reqeustId'] ?: $intent->emola_request_id,
            'gateway_code' => $data['errorCode'],
        ];

        if (EmolaSpec::isPaymentSuccessCode($data['errorCode'])) {
            $this->markPaid($intent, $attributes);
        } elseif (EmolaSpec::isPaymentFailureCode($data['errorCode'])) {
            $this->markFailed($intent, $this->emolaFailureMessage($data['errorCode'], $data['message']), $attributes);
        } else {
            $intent->update($attributes);
        }

        return true;
    }

    /**
     * Scheduler: resolve intents the customer walked away from.
     */
    public function sweep(): int
    {
        $count = 0;

        PaymentIntent::whereIn('status', [PaymentIntent::STATUS_PENDING, PaymentIntent::STATUS_PROCESSING, PaymentIntent::STATUS_CREATED, PaymentIntent::STATUS_PAID])
            ->where('updated_at', '<=', now()->subMinute())
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (PaymentIntent $intent) use (&$count) {
                try {
                    $this->refresh($intent, true);
                    $count++;
                } catch (\Throwable $e) {
                    Log::warning('Payment intent sweep failed', ['intent' => $intent->uuid, 'error' => $e->getMessage()]);
                }
            });

        return $count;
    }

    /**
     * Shape shared by the web waiting page and the mobile app.
     */
    public function present(PaymentIntent $intent): array
    {
        $orders = $intent->isCompleted()
            ? $intent->orders()->get(['id', 'order_number', 'grand_total', 'shop_id', 'payment_status'])
            : collect();

        return [
            'id' => $intent->uuid,
            'status' => $intent->status,
            'payment_method' => $intent->payment_method,
            'amount' => (float) $intent->amount,
            'amount_formatted' => get_formated_currency($intent->amount, 2),
            'msisdn' => $this->maskMsisdn($intent->msisdn),
            'store_count' => count($intent->cart_ids ?: []),
            'message' => $intent->message ?: $this->defaultMessage($intent),
            'expires_in' => $intent->isOpen() ? $intent->secondsLeft() : 0,
            'poll_interval' => 4,
            'can_retry' => in_array($intent->status, [PaymentIntent::STATUS_FAILED, PaymentIntent::STATUS_EXPIRED, PaymentIntent::STATUS_CANCELLED], true),
            'can_cancel' => $intent->isOpen(),
            'order_ids' => $orders->pluck('id')->values(),
            'order_numbers' => $orders->pluck('order_number')->values(),
        ];
    }

    // ------------------------------------------------------------------
    // Order creation
    // ------------------------------------------------------------------

    /**
     * Turn the paid intent's carts into orders. Idempotent.
     */
    private function completeOrders(PaymentIntent $intent): PaymentIntent
    {
        $orders = [];

        try {
            DB::transaction(function () use ($intent, &$orders) {
                $locked = PaymentIntent::whereKey($intent->id)->lockForUpdate()->first();

                if ($locked->status !== PaymentIntent::STATUS_PAID) {
                    return;
                }

                $carts = Cart::with(['shop', 'inventories', 'coupon'])
                    ->whereIn('id', $locked->cart_ids)
                    ->orderBy('id')
                    ->get();

                if ($carts->isEmpty()) {
                    Log::critical('Payment intent paid but its carts are gone — refund or create the order manually', [
                        'intent' => $locked->uuid,
                        'customer_id' => $locked->customer_id,
                        'amount' => $locked->amount,
                        'method' => $locked->payment_method,
                        'gateway_ref' => $locked->gateway_ref ?: $locked->emola_trans_id,
                    ]);
                    $locked->update(['message' => trans('theme.payment_wait.paid_processing')]);

                    return;
                }

                $request = $this->rebuildRequest($locked);
                $this->restoreAffiliateClick($locked);

                $method = $locked->payment_method;
                $paymentRef = $locked->gateway_ref ?: ($locked->emola_request_id ?: ($locked->emola_trans_id ?: $locked->uuid));

                foreach ($carts as $cart) {
                    $order = $this->saveOrderFromCart($request, $cart);
                    $order->currency_id = config('system_settings.currency.id');
                    $order->payment_ref_id = $paymentRef;

                    if ($method === 'emola') {
                        $order->emola_trans_id = $locked->emola_trans_id;
                        $order->emola_ref_no = $locked->emola_ref_no;
                        $order->emola_request_id = $locked->emola_request_id;
                        $order->emola_error_code = $locked->gateway_code;
                    }

                    persist_order_checkout_fees($order, $method); // Saves the order
                    $order->markAsPaid();
                    $orders[] = $order;
                }

                $expected = (float) ($locked->payload['base_amount'] ?? 0);
                $actual = round(collect($orders)->sum(fn (Order $o) => (float) $o->grand_total), 2);
                if ($expected > 0 && abs($expected - $actual) > 0.01) {
                    Log::warning('Payment intent cart total changed while waiting for payment', [
                        'intent' => $locked->uuid,
                        'charged_base' => $expected,
                        'order_total' => $actual,
                    ]);
                }

                $locked->update([
                    'status' => PaymentIntent::STATUS_COMPLETED,
                    'order_ids' => collect($orders)->pluck('id')->all(),
                    'completed_at' => now(),
                    'message' => null,
                ]);
            });
        } catch (\Throwable $e) {
            // Intent stays "paid"; the next poll or the scheduler retries.
            Log::error('Payment intent paid but order creation failed', [
                'intent' => $intent->uuid,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return $intent->refresh();
        }

        foreach ($orders as $order) {
            safe_dispatch_order_event(new OrderCreated($order), 'OrderCreated (payment intent)');
        }

        return $intent->refresh();
    }

    private function rebuildRequest(PaymentIntent $intent): Request
    {
        $payload = $intent->payload ?: [];
        $payload['payment_method'] = $intent->payment_method;
        $payload['payment_method_id'] = $intent->payment_method_id;
        $payload['customer_id'] = $payload['customer_id'] ?? $intent->customer_id;

        return Request::create('/', 'POST', $payload);
    }

    private function restoreAffiliateClick(PaymentIntent $intent): void
    {
        $click = $intent->payload['affiliate_click'] ?? null;

        if (is_array($click) && is_incevio_package_loaded('affiliate')) {
            Session::put(\Incevio\Package\Affiliate\Services\AffiliateAttributionService::SESSION_KEY, $click);
        }
    }

    private function payloadFrom(Request $request, $customerId, float $baseAmount): array
    {
        $payload = [];
        foreach (self::PAYLOAD_FIELDS as $field) {
            $value = $request->input($field);
            if ($value !== null && ! is_array($value)) {
                $payload[$field] = $value;
            }
        }

        // Pin the buyer location now; the callback request has no session.
        $location = app(BuyerLocationService::class);
        $payload['customer_latitude'] = $payload['customer_latitude'] ?? $payload['latitude'] ?? $location->latitude();
        $payload['customer_longitude'] = $payload['customer_longitude'] ?? $payload['longitude'] ?? $location->longitude();
        $payload['customer_id'] = $customerId;
        $payload['base_amount'] = $baseAmount;

        if (is_incevio_package_loaded('affiliate')) {
            try {
                $payload['affiliate_click'] = app(\Incevio\Package\Affiliate\Services\AffiliateAttributionService::class)->current($request);
            } catch (\Throwable $e) {
                Log::debug('Payment intent affiliate capture: '.$e->getMessage());
            }
        }

        return $payload;
    }

    // ------------------------------------------------------------------
    // Gateways
    // ------------------------------------------------------------------

    private function pushMpesa(PaymentIntent $intent): void
    {
        $client = $this->mpesaClient($intent);
        $client->setReference('order');

        $raw = $client->processTransaction($intent->amount, trans('app.purchase_from', [
            'marketplace' => get_platform_title(),
        ]));

        Log::info('M-Pesa payment intent push', ['intent' => $intent->uuid, 'response' => $raw]);

        $data = is_string($raw) ? json_decode($raw) : null;
        $code = $data->output_ResponseCode ?? null;
        $refId = $data->output_TransactionID ?? $data->output_ThirdPartyConversationID ?? null;

        // Keep a reference we can query even if the response was lost.
        $intent->update([
            'gateway_ref' => $refId ?: $client->lastThirdPartyRef,
            'gateway_code' => $code,
        ]);

        // Single-stage C2B returns after the customer approved on the phone.
        if ($code === 'INS-0' || $code === '0') {
            $this->markPaid($intent);

            return;
        }

        if (! $data || in_array($code, self::MPESA_UNDECIDED_CODES, true)) {
            $this->markWaiting($intent);

            return;
        }

        Log::warning('M-Pesa payment intent failed', [
            'intent' => $intent->uuid,
            'code' => $code,
            'detail' => $data->output_ResponseDesc ?? null,
        ]);

        throw new PaymentFailedException(trans('mpesa::lang.payment_not_updated'));
    }

    private function pushEmola(PaymentIntent $intent): void
    {
        $msisdn = EmolaSpec::normalizeMsisdn((string) $intent->msisdn);
        $transAmount = EmolaSpec::formatTransAmount((int) $intent->amount, EmolaSpec::CONTEXT_ORDER);
        $transId = $this->emola->generateTransId();
        $refNo = EmolaSpec::sanitizeRefNo('PI'.$intent->id);

        // Save the references first: the callback can arrive before pushUssdMessage returns.
        $intent->update(['emola_trans_id' => $transId, 'emola_ref_no' => $refNo]);

        $res = $this->emola->pushUssdMessage([
            'msisdn' => $msisdn,
            'transId' => $transId,
            'transAmount' => $transAmount,
            'smsContent' => trans('app.purchase_from', ['marketplace' => get_platform_title()]),
            'language' => EmolaSpec::sanitizeLanguage(app()->getLocale() === 'en' ? 'en' : 'pt'),
            'refNo' => $refNo,
        ]);

        Log::info('eMola payment intent push', [
            'intent' => $intent->uuid,
            'trans_id' => $transId,
            'trans_amount' => $transAmount,
            'gateway_error' => $res->gatewayError,
            'business_code' => $res->businessErrorCode(),
            'ussd_push_accepted' => $res->isUssdPushAccepted(),
        ]);

        $intent->refresh();
        $intent->update(array_filter([
            'emola_request_id' => $res->requestId(),
            'gateway_code' => $intent->gateway_code ?: $res->businessErrorCode(),
        ]));

        if (! $res->isUssdPushAccepted()) {
            throw new PaymentFailedException($res->failureMessage());
        }

        EmolaDailyLimit::recordAcceptedPush($msisdn, (int) $transAmount);

        $this->markWaiting($intent);
    }

    /** Push sent; wait for the customer. A callback may already have settled it. */
    private function markWaiting(PaymentIntent $intent): void
    {
        PaymentIntent::whereKey($intent->id)
            ->where('status', PaymentIntent::STATUS_PROCESSING)
            ->update(['status' => PaymentIntent::STATUS_PENDING, 'updated_at' => now()]);
    }

    private function queryGateway(PaymentIntent $intent): void
    {
        try {
            if ($intent->payment_method === 'mpesa' && $intent->gateway_ref) {
                if (! config('mpesa.query_enabled', true)) {
                    return;
                }

                $raw = $this->mpesaClient($intent)->verifyTransaction($intent->gateway_ref);
                $json = $raw ? json_decode($raw) : null;

                if (! $json) {
                    return;
                }

                $code = $json->output_ResponseCode ?? null;
                $txStatus = strtolower((string) ($json->output_ResponseTransactionStatus ?? ''));

                if (($code === 'INS-0' || $code === '0') && in_array($txStatus, ['', 'completed'], true)) {
                    $this->markPaid($intent);
                } elseif (in_array($txStatus, ['cancelled', 'expired', 'failed'], true)) {
                    $this->markFailed($intent, trans('mpesa::lang.payment_not_updated'));
                }

                return;
            }

            if ($intent->payment_method === 'emola' && $intent->emola_trans_id) {
                $res = $this->emola->pushUssdQueryTrans(
                    $intent->emola_trans_id,
                    (string) config('emola.trans_types.c2b', 'C2B')
                );
                $original = $res->originalData ?? [];
                $errorCode = $original['errorCode'] ?? null;

                if ($res->isTransactionPaid()) {
                    $this->markPaid($intent, ['gateway_code' => $errorCode]);
                } elseif (EmolaSpec::isPaymentFailureCode($errorCode)) {
                    $this->markFailed($intent, $this->emolaFailureMessage($errorCode, $original['message'] ?? null), ['gateway_code' => $errorCode]);
                }
            }
        } catch (\Throwable $e) {
            Log::debug('Payment intent status query: '.$e->getMessage(), ['intent' => $intent->uuid]);
        }
    }

    private function mpesaClient(PaymentIntent $intent): MPesaClient
    {
        $client = new MPesaClient(Request::create('/', 'POST', ['mpesa_number' => $intent->msisdn]));

        // Same receiver rule as the order checkout: one shop paid directly uses its own till.
        if (vendor_get_paid_directly() && count($intent->cart_ids) === 1) {
            $cart = Cart::with('shop')->find($intent->cart_ids[0]);
            if ($cart && $cart->shop) {
                $client->setVendorAPIKey($cart->shop);
            }
        }

        return $client;
    }

    private function msisdnFrom(Request $request, string $method): ?string
    {
        if ($method === 'emola') {
            return EmolaSpec::normalizeMsisdn((string) $request->input('emola_number'));
        }

        if ($method === 'mpesa') {
            return preg_replace('/\s+/', '', (string) $request->input('mpesa_number'));
        }

        return null;
    }

    private function emolaFailureMessage(?string $code, ?string $message): string
    {
        $key = EmolaSpec::businessErrorThemeKey($code);

        return $key ? trans($key) : trans('theme.payment_wait.failed');
    }

    private function defaultMessage(PaymentIntent $intent): ?string
    {
        return match ($intent->status) {
            PaymentIntent::STATUS_CREATED, PaymentIntent::STATUS_PROCESSING => trans('theme.payment_wait.starting'),
            PaymentIntent::STATUS_PENDING => trans('theme.payment_wait.'.$intent->payment_method.'_instructions', [
                'number' => $this->maskMsisdn($intent->msisdn),
            ]),
            PaymentIntent::STATUS_PAID => trans('theme.payment_wait.paid_processing'),
            PaymentIntent::STATUS_COMPLETED => trans('theme.notify.order_placed'),
            PaymentIntent::STATUS_CANCELLED => trans('theme.payment_wait.cancelled'),
            PaymentIntent::STATUS_EXPIRED => trans('theme.payment_wait.expired'),
            default => trans('theme.payment_wait.failed'),
        };
    }

    private function maskMsisdn(?string $msisdn): ?string
    {
        if (! $msisdn) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $msisdn);

        return strlen($digits) > 4 ? str_repeat('•', strlen($digits) - 4).substr($digits, -4) : $digits;
    }
}
