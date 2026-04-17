<?php

namespace Incevio\Package\MPesa\Http\Controllers;

use App\Models\Order;
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
            $success = ($response->output_ResponseCode ?? '') === 'INS-0' || ($response->output_ResponseCode ?? '') === '0';
        } else {
            $refId = $response->CheckoutRequestID ?? null;
            $success = (int) ($response->ResultCode ?? 1) === 0;
        }

        if ($refId) {
            $order = Order::where('payment_ref_id', $refId)->first();
            if ($order) {
                if ($success) {
                    $order->markAsPaid();
                } else {
                    $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                    $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
                    $order->save();
                }
            } elseif ($success) {
                $this->creditWalletDeposit($refId);
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
            'description' => trans('packages.wallet.deposit_description', [
                'marketplace' => get_platform_title(),
                'payment_method' => 'M-Pesa',
            ]),
        ];

        $trans = $holder->deposit($data['amount'], $meta, true);
        SendNotificationJob::dispatch($trans, Deposit::class);

        Cache::forget($cacheKey);
    }
}
