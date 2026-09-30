<?php

namespace Incevio\Package\MPesa\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\Payments\CheckoutPaymentIntentService;
use Incevio\Package\MPesa\Http\Requests\HttpRequest as MPesaHttpClient;
use Incevio\Package\MPesa\Services\MPesaPaymentService;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Notifications\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

/**
 * M-Pesa Mozambique (Vodacom) callback handler.
 * Accepts both Mozambique (output_*) and legacy Kenya-style payloads.
 * Handles both order payment and wallet deposit.
 */
class ResponseController extends Controller
{
    /**
     * M-Pesa payment callback (webhook).
     */
    public function callback(Request $request)
    {
        Log::info("M-Pesa callback received");
        Log::info($request->getContent());

        $raw = $request->getContent();
        $response = json_decode($raw);

        if (!$response) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON'], 400);
        }

        // Mozambique: output_TransactionID, output_ResponseCode, output_ThirdPartyConversationID
        $isMozambique = isset($response->output_ResponseCode) || isset($response->output_TransactionID);

        if ($isMozambique) {
            $refId = $response->output_TransactionID ?? $response->output_ThirdPartyConversationID ?? null;
        } else {
            $refId = $response->CheckoutRequestID ?? null;
        }

        // The callback is unsigned: its result code is ignored. Only a reference we issued is acted
        // on, and only with the status M-Pesa itself reports for that reference.
        if ($refId) {
            $refId = (string) $refId;
            $orders = Order::where('payment_ref_id', $refId)->get();
            $intent = PaymentIntent::where('gateway_ref', $refId)->where('payment_method', 'mpesa')->exists();
            $deposit = Cache::has(MPesaPaymentService::CACHE_KEY_WALLET_DEPOSIT . $refId);

            if ($orders->isEmpty() && ! $intent && ! $deposit) {
                Log::warning('M-Pesa callback: unknown reference ignored', ['ref' => $refId, 'ip' => $request->ip()]);
            } else {
                $status = $this->verifiedStatus($refId, $orders->first());

                if ($status === null) {
                    Log::info('M-Pesa callback: gateway status not final, left for polling', ['ref' => $refId]);
                } elseif ($orders->isNotEmpty()) {
                    foreach ($orders as $order) {
                        if ($status === 'paid') {
                            if (! $order->isPaid()) {
                                $order->markAsPaid();
                            }
                        } elseif (! $order->isPaid()) {
                            $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                            $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
                            $order->save();
                        }
                    }
                } elseif ($intent) {
                    app(CheckoutPaymentIntentService::class)
                        ->handleMpesaCallback($refId, $status === 'paid', $status === 'paid' ? 'INS-0' : 'INS-2006');
                } elseif ($status === 'paid') {
                    $this->creditWalletDeposit($refId);
                }
            }
        }

        // Mozambique callback expects this format for acceptance
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accept Service',
            'ThirdPartyTransID' => Str::random(13),
        ]);
    }

    /**
     * M-Pesa's own answer for the reference: 'paid', 'failed', or null while undecided (or when the
     * query is disabled or fails, so polling settles it instead of the unsigned callback).
     */
    private function verifiedStatus(string $refId, ?Order $order): ?string
    {
        if (! config('mpesa.query_enabled', true)) {
            Log::warning('M-Pesa callback: status query disabled, callback not trusted', ['ref' => $refId]);

            return null;
        }

        try {
            $client = new MPesaHttpClient(request());
            if ($order && $order->shop && vendor_get_paid_directly()) {
                $client->setVendorAPIKey($order->shop);
            }
            $json = json_decode((string) $client->verifyTransaction($refId));
        } catch (\Throwable $e) {
            Log::warning('M-Pesa callback: status query failed', ['ref' => $refId, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $json) {
            return null;
        }

        $code = $json->output_ResponseCode ?? null;
        $txStatus = strtolower((string) ($json->output_ResponseTransactionStatus ?? ''));

        if (($code === 'INS-0' || $code === '0') && in_array($txStatus, ['', 'completed'], true)) {
            return 'paid';
        }

        return in_array($txStatus, ['cancelled', 'expired', 'failed'], true) ? 'failed' : null;
    }

    /**
     * Credit wallet when M-Pesa callback confirms a wallet deposit (no order for this ref).
     */
    private function creditWalletDeposit(string $refId): void
    {
        $cacheKey = MPesaPaymentService::CACHE_KEY_WALLET_DEPOSIT . $refId;
        $paidKey = MPesaPaymentService::CACHE_KEY_WALLET_PAID . $refId;

        if (Cache::has($paidKey)) {
            return;
        }

        $data = Cache::get($cacheKey);
        if (!$data || !isset($data['holder_type'], $data['holder_id'], $data['amount'])) {
            return;
        }

        $holder = $data['holder_type']::find($data['holder_id']);
        if (!$holder || !method_exists($holder, 'deposit')) {
            return;
        }

        Cache::put($paidKey, 1, now()->addHours(24));
        $meta = [
            'type' => Transaction::TYPE_DEPOSIT,
            'payment_method' => 'mpesa',
            'description' => Transaction::depositDescriptionFor('mpesa'),
        ];
        if (! empty($data['platform_fee'])) {
            $meta['platform_fee'] = $data['platform_fee'];
        }
        if (! empty($data['charge_amount'])) {
            $meta['charge_amount'] = $data['charge_amount'];
        }

        $trans = $holder->deposit($data['amount'], $meta, true);
        SendNotificationJob::dispatch($trans, Deposit::class);

        Cache::forget($cacheKey);

        if (! empty($data['subscription_plan_id']) && $holder instanceof \App\Models\Shop) {
            app(\App\Services\Subscription\SubscriptionPaymentCompletionService::class)
                ->completeAfterDeposit($holder, (string) $data['subscription_plan_id']);
        }
    }
}
