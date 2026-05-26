<?php

namespace App\Services\Emola;

use App\Events\Order\OrderCreated;
use App\Events\Order\OrderPaymentFailed;
use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class EmolaOrderPaymentService
{
    public function __construct(private readonly EmolaClient $client)
    {
    }

    /**
     * Send (or resend) USSD push (pushUssdMessage). Payment confirmed only via callback or status query.
     */
    public function pushPaymentForOrder(Order $order, string $msisdn, ?string $smsContent = null): EmolaResponse
    {
        $msisdn = EmolaSpec::normalizeMsisdn($msisdn);
        $feeBreakdown = get_customer_transaction_fee_for_order($order, 'emola');
        if ($feeBreakdown['subscription_fee'] > 0) {
            $order->platform_payment_fee = 0;
            $order->subscription_transaction_fee = $feeBreakdown['subscription_fee'];
            $order->save();
        }
        $transAmount = EmolaSpec::formatTransAmount($feeBreakdown['total'], EmolaSpec::CONTEXT_ORDER);
        $amountMzn = (int) $transAmount;
        $transId = $this->client->generateTransId();
        $refNo = EmolaSpec::sanitizeRefNo('REF'.(string) $order->id);

        $res = $this->client->pushUssdMessage([
            'msisdn' => $msisdn,
            'transId' => $transId,
            'transAmount' => $transAmount,
            'smsContent' => $smsContent ?: trans('app.purchase_from', ['marketplace' => get_platform_title()]),
            'language' => EmolaSpec::sanitizeLanguage(app()->getLocale() === 'en' ? 'en' : 'pt'),
            'refNo' => $refNo,
        ]);

        Log::info('eMola USSD push for order', [
            'order_id' => $order->id,
            'grand_total' => $order->grand_total,
            'trans_amount' => $transAmount,
            'trans_id' => $transId,
            'msisdn' => $msisdn,
            'gateway_error' => $res->gatewayError,
            'business_code' => $res->businessErrorCode(),
            'business_message' => $res->businessMessage(),
            'request_id' => $res->requestId(),
            'ussd_push_accepted' => $res->isUssdPushAccepted(),
        ]);

        $this->syncOrderFromResponse($order, $res, $transId, $refNo);

        if ($res->isUssdPushAccepted()) {
            EmolaDailyLimit::recordAcceptedPush($msisdn, $amountMzn);
        }

        return $res;
    }

    public function resendPaymentRequest(Order $order, string $msisdn): void
    {
        $res = $this->pushPaymentForOrder($order, $msisdn);

        if (! $res->isUssdPushAccepted()) {
            throw new PaymentFailedException($res->failureMessage());
        }
    }

    /**
     * Movitel async callback (spec §B.4).
     *
     * @param  array{reqeustId: string, transId: string, refNo: string, errorCode: string, message: string}  $data
     */
    public function processCallbackPayload(array $data): bool
    {
        Log::info('eMola callback processing', [
            'reqeustId' => $data['reqeustId'],
            'transId' => $data['transId'],
            'refNo' => $data['refNo'],
            'errorCode' => $data['errorCode'],
        ]);

        $order = $this->findOrderByEmolaReference($data['transId'], $data['refNo']);

        if (! $order) {
            Log::warning('eMola callback: order not found', $data);

            return false;
        }

        $order->emola_request_id = $data['reqeustId'];
        $order->payment_ref_id = $order->payment_ref_id ?: $data['reqeustId'];
        $order->emola_error_code = $data['errorCode'];
        $order->emola_message = $data['message'];
        $order->save();

        if (EmolaSpec::isPaymentSuccessCode($data['errorCode'])) {
            return $this->markOrderPaid($order);
        }

        $this->applyPaymentFailure($order, $data['errorCode'], $data['message']);

        return false;
    }

    /**
     * Query Movitel for payment status (fallback when callback is delayed or missing).
     *
     * @return array{paid: bool, message: string, error_code: ?string, org_response_code: ?string}
     */
    public function syncPaymentStatusFromGateway(Order $order): array
    {
        if (! $order->emola_trans_id) {
            return [
                'paid' => false,
                'message' => trans('theme.emola_sync_no_trans_id'),
                'error_code' => null,
                'org_response_code' => null,
            ];
        }

        if ($order->isPaid()) {
            return [
                'paid' => true,
                'message' => trans('theme.emola_payment_confirmed'),
                'error_code' => $order->emola_error_code,
                'org_response_code' => null,
            ];
        }

        $res = $this->client->pushUssdQueryTrans(
            $order->emola_trans_id,
            (string) config('emola.trans_types.c2b', 'C2B')
        );

        $original = $res->originalData ?? [];

        Log::info('eMola status query for order', [
            'order_id' => $order->id,
            'trans_id' => $order->emola_trans_id,
            'gateway_error' => $res->gatewayError,
            'error_code' => $original['errorCode'] ?? null,
            'org_response_code' => $original['orgResponseCode'] ?? null,
            'paid' => $res->isTransactionPaid(),
        ]);

        $order->emola_error_code = $original['errorCode'] ?? $order->emola_error_code;
        $order->emola_message = $original['message'] ?? $res->businessMessage() ?? $order->emola_message;
        if ($res->requestId()) {
            $order->emola_request_id = $res->requestId();
            $order->payment_ref_id = $order->payment_ref_id ?: $res->requestId();
        }
        $order->save();

        $errorCode = $original['errorCode'] ?? null;
        $paid = false;

        if ($res->isTransactionPaid()) {
            $paid = $this->markOrderPaid($order->fresh());
        } elseif (EmolaSpec::isPaymentFailureCode($errorCode)) {
            $this->applyPaymentFailure($order->fresh(), (string) $errorCode, $order->emola_message);
        }

        $message = $paid
            ? trans('theme.emola_payment_confirmed')
            : (EmolaSpec::isPaymentFailureCode($errorCode)
                ? $res->failureMessage()
                : trans('theme.emola_payment_pending'));

        return [
            'paid' => $paid,
            'message' => $message,
            'error_code' => $errorCode,
            'org_response_code' => $original['orgResponseCode'] ?? null,
        ];
    }

    private function findOrderByEmolaReference(string $transId, string $refNo): ?Order
    {
        return Order::query()
            ->where(function ($q) use ($transId, $refNo) {
                $q->where('emola_trans_id', $transId)
                    ->orWhere('emola_ref_no', $refNo);
            })
            ->latest('id')
            ->first();
    }

    private function markOrderPaid(Order $order): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        $order->markAsPaid();

        safe_dispatch_order_event(new OrderCreated($order), 'OrderCreated (eMola)');

        return true;
    }

    /**
     * PIN cancelled, timeout, or other terminal failure — keep order unpaid / revert false paid.
     */
    private function applyPaymentFailure(Order $order, string $errorCode, ?string $message = null): void
    {
        if ($message) {
            $order->emola_message = $message;
        }
        $order->emola_error_code = $errorCode;

        if ($order->isPaid() && EmolaSpec::isPaymentFailureCode($errorCode)) {
            Log::warning('eMola reverting order marked paid after payment failure', [
                'order_id' => $order->id,
                'error_code' => $errorCode,
                'message' => $message,
            ]);
            $order->markAsUnpaid();
        }

        if (! $order->isPaid()) {
            $order->payment_status = Order::PAYMENT_STATUS_PENDING;
            $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
            $order->save();

            try {
                event(new OrderPaymentFailed($order));
            } catch (\Throwable $e) {
                Log::warning('eMola OrderPaymentFailed event failed: '.$e->getMessage());
            }
        }
    }

    private function syncOrderFromResponse(Order $order, EmolaResponse $res, string $transId, string $refNo): void
    {
        $original = $res->originalData ?? [];

        $order->emola_trans_id = $transId;
        $order->emola_ref_no = $refNo;
        $order->emola_gwtransid = $res->gwtransid;
        $order->emola_gateway_error = $res->gatewayError;
        $order->emola_gateway_description = $res->gatewayDescription;
        $order->emola_error_code = $original['errorCode'] ?? null;
        $order->emola_message = $original['message'] ?? null;
        $order->emola_request_id = $res->requestId();

        if ($order->emola_request_id) {
            $order->payment_ref_id = $order->emola_request_id;
        }

        $order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
        $order->payment_status = Order::PAYMENT_STATUS_PENDING;
        $order->save();
    }
}
