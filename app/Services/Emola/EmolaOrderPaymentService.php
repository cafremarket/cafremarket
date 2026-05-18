<?php

namespace App\Services\Emola;

use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class EmolaOrderPaymentService
{
    public function __construct(private readonly EmolaClient $client)
    {
    }

    /**
     * Send (or resend) USSD push (pushUssdMessage). Payment confirmed only via callback (spec §B.4).
     */
    public function pushPaymentForOrder(Order $order, string $msisdn, ?string $smsContent = null): EmolaResponse
    {
        $msisdn = EmolaSpec::normalizeMsisdn($msisdn);
        $transId = $this->client->generateTransId();
        $refNo = EmolaSpec::sanitizeRefNo('REF'.(string) $order->id);

        $res = $this->client->pushUssdMessage([
            'msisdn' => $msisdn,
            'transId' => $transId,
            'transAmount' => EmolaSpec::transAmountFromOrder($order),
            'smsContent' => $smsContent ?: trans('app.purchase_from', ['marketplace' => get_platform_title()]),
            'language' => EmolaSpec::sanitizeLanguage(app()->getLocale() === 'en' ? 'en' : 'pt'),
            'refNo' => $refNo,
        ]);

        Log::info('eMola USSD push for order', [
            'order_id' => $order->id,
            'trans_id' => $transId,
            'msisdn' => $msisdn,
            'gateway_error' => $res->gatewayError,
            'business_code' => $res->businessErrorCode(),
            'business_message' => $res->businessMessage(),
            'request_id' => $res->requestId(),
            'ussd_push_accepted' => $res->isUssdPushAccepted(),
        ]);

        $this->syncOrderFromResponse($order, $res, $transId, $refNo);

        return $res;
    }

    public function resendPaymentRequest(Order $order, string $msisdn): void
    {
        $res = $this->pushPaymentForOrder($order, $msisdn);

        if (! $res->isUssdPushAccepted()) {
            throw new PaymentFailedException($res->failureMessage());
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
