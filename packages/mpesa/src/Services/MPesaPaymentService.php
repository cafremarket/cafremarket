<?php

namespace Incevio\Package\MPesa\Services;

use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Exceptions\PaymentFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Incevio\Package\MPesa\Http\Requests\HttpRequest as PaymentHttpClient;

/**
 * M-Pesa Mozambique (Vodacom Open API) payment service.
 */
class MPesaPaymentService extends PaymentService
{
    public $httpClient;

    public const CACHE_KEY_WALLET_DEPOSIT = 'mpesa_wallet_deposit_';

    public const CACHE_KEY_WALLET_PAID = 'mpesa_wallet_paid_';

    public function __construct(Request $request)
    {
        $this->httpClient = new PaymentHttpClient($request);

        parent::__construct($request);
    }

    /**
     * Execute the payment (C2B single-stage). Handles both order and wallet deposit.
     */
    public function charge()
    {
        $response = $this->httpClient->processTransaction($this->amount, $this->description);

        Log::info('M-Pesa Mozambique process transaction response: ', ['response' => $response]);

        $data = json_decode($response);

        // Mozambique: output_TransactionID or output_ThirdPartyConversationID for reference
        $refId = $data->output_TransactionID ?? $data->output_ThirdPartyConversationID ?? null;
        $code = $data->output_ResponseCode ?? null;
        $success = $code === 'INS-0' || $code === '0' || ($refId && $code !== 'INS-1' && $code !== 'INS-6');

        if ($refId && $success) {
            if ($this->order) {
                $this->order->payment_ref_id = $refId;
                $this->order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
                $this->order->payment_status = Order::PAYMENT_STATUS_PENDING;
                $this->order->save();

                // API / JSON request (e.g. Flutter app): return pending status so client can poll order status
                $path = $this->request->path();
                $isApi = $this->request->wantsJson()
                    || $this->request->is('api/*')
                    || (is_string($path) && strpos($path, 'api/') === 0)
                    || $this->request->expectsJson();
                if ($isApi) {
                    $this->status = self::STATUS_PENDING;

                    return $this;
                }

                return redirect()->to(url('mpesa/' . $this->order->id . '/complete'));
            }

            // Wallet deposit: store pending deposit and redirect to wallet complete page
            $payee = $this->payee;
            if ($payee) {
                Cache::put(self::CACHE_KEY_WALLET_DEPOSIT . $refId, [
                    'holder_type' => get_class($payee),
                    'holder_id' => $payee->id,
                    'amount' => $this->amount,
                ], now()->addHours(24));

                return redirect()->to(url('wallet/deposit/mpesa/complete?ref=' . urlencode($refId)));
            }
        }

        $this->status = self::STATUS_ERROR;
        $message = $data->output_ResponseDesc ?? $data->output_ResponseCode ?? 'Payment request failed';
        throw new PaymentFailedException($message);
    }

    public function setAmount($amount)
    {
        $this->amount = intval($amount);

        return $this;
    }

    public function setConfig()
    {
        if ($this->order && $this->receiver == 'merchant') {
            $this->httpClient->setVendorAPIKey($this->order->shop);
        }

        $ref = $this->order ? 'order' : 'deposit';
        $this->httpClient->setReference($ref);

        if (!$this->amount || !is_numeric($this->amount) || $this->amount < 1) {
            throw new PaymentFailedException("Invalid Amount.");
        }
        if (!$this->request->mpesa_number || !preg_match('/^[\d\s\+]+$/', $this->request->mpesa_number)) {
            throw new PaymentFailedException("Invalid M-Pesa number.");
        }

        return $this;
    }

    /**
     * Verify payment (query transaction status).
     */
    public function verifyPayment($transactionRef)
    {
        return $this->httpClient->verifyTransaction($transactionRef);
    }
}
