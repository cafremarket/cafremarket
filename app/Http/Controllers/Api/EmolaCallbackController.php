<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\Emola\EmolaCallbackPayload;
use App\Services\Emola\EmolaClient;
use App\Services\Emola\EmolaResponse;
use App\Services\Emola\EmolaSpec;
use App\Services\Emola\EmolaOrderPaymentService;
use App\Services\Emola\EmolaWalletDepositService;
use App\Services\Payments\CheckoutPaymentIntentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmolaCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        EmolaOrderPaymentService $emolaOrders,
        EmolaWalletDepositService $emolaWallet,
        CheckoutPaymentIntentService $intents,
        EmolaClient $client,
    ) {
        Log::info('eMola callback received', [
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
            'keys' => array_keys($request->all()),
        ]);

        $data = EmolaCallbackPayload::fromRequest($request);

        if ($data === null) {
            Log::warning('eMola callback: invalid payload', [
                'body_preview' => substr((string) $request->getContent(), 0, 500),
            ]);

            return response()->json([
                'ResponseCode' => '1',
                'ResponseMessage' => 'Invalid payload',
            ], 422);
        }

        // The callback is unsigned, so its errorCode is only a hint: act on what Movitel reports
        // for our own transId, and only for a transaction we actually started.
        if (! $this->isKnownTransaction($data['transId'], $emolaWallet)) {
            Log::warning('eMola callback: unknown transId ignored', [
                'transId' => $data['transId'],
                'ip' => $request->ip(),
            ]);

            return $this->ack();
        }

        $verifiedCode = $this->verifiedErrorCode($data['transId'], $client);

        if ($verifiedCode === null) {
            Log::info('eMola callback: gateway status not final, left for polling', [
                'transId' => $data['transId'],
                'claimed_error_code' => $data['errorCode'],
            ]);

            return $this->ack();
        }

        $data['errorCode'] = $verifiedCode;

        if (! $emolaWallet->processCallbackPayload($data)
            && ! $intents->handleEmolaCallback($data)) {
            $emolaOrders->processCallbackPayload($data);
        }

        return $this->ack();
    }

    private function ack()
    {
        return response()->json([
            'ResponseCode' => '0',
            'ResponseMessage' => 'OK',
        ]);
    }

    private function isKnownTransaction(string $transId, EmolaWalletDepositService $emolaWallet): bool
    {
        return $emolaWallet->hasPendingDeposit($transId)
            || PaymentIntent::where('emola_trans_id', $transId)->exists()
            || Order::where('emola_trans_id', $transId)->exists();
    }

    /**
     * Movitel's own answer for the transaction: the success code when paid, the failure code when
     * terminally failed, null while still undecided (or when the status query itself fails).
     */
    private function verifiedErrorCode(string $transId, EmolaClient $client): ?string
    {
        try {
            $res = $client->pushUssdQueryTrans($transId, (string) config('emola.trans_types.c2b', 'C2B'));
        } catch (\Throwable $e) {
            Log::warning('eMola callback: status query failed', ['transId' => $transId, 'error' => $e->getMessage()]);

            return null;
        }

        if ($res->isTransactionPaid()) {
            return EmolaResponse::CODE_SUCCESS;
        }

        $code = $res->businessErrorCode();

        return $res->isGatewaySuccess() && EmolaSpec::isPaymentFailureCode($code) ? $code : null;
    }
}
