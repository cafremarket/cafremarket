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
     * Send (or resend) USSD push for an existing order. Payment is confirmed only via callback.
     */
    public function pushPaymentForOrder(Order $order, string $msisdn, ?string $smsContent = null): EmolaResponse
    {
        $msisdn = preg_replace('/\D/', '', $msisdn);

        if (! preg_match('/^(86|87)\d{7}$/', $msisdn)) {
            throw new PaymentFailedException(trans('theme.emola_number_invalid'));
        }

        $transId = $this->client->generateTransId();
        $refNo = substr(preg_replace('/[^A-Za-z0-9]/', '', 'REF'.(string) $order->id), 0, 20);
        $amount = (string) intval($order->grand_total);

        $res = $this->client->pushUssdMessage([
            'msisdn' => $msisdn,
            'transId' => $transId,
            'transAmount' => $amount,
            'smsContent' => $smsContent ?: trans('app.purchase_from', ['marketplace' => get_platform_title()]),
            'language' => app()->getLocale() === 'en' ? 'en' : 'pt',
            'refNo' => $refNo,
        ]);

        Log::info('eMola USSD push for order', [
            'order_id' => $order->id,
            'trans_id' => $transId,
            'gateway_ok' => $res->ok(),
            'gateway_error' => $res->gatewayError,
        ]);

        $this->syncOrderFromResponse($order, $res, $transId, $refNo);

        return $res;
    }

    public function resendPaymentRequest(Order $order, string $msisdn): void
    {
        $res = $this->pushPaymentForOrder($order, $msisdn);

        if (! $res->ok()) {
            throw new PaymentFailedException(
                $res->gatewayDescription ?: trans('theme.emola_resend_failed')
            );
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
        $order->emola_request_id = $original['reqeustId'] ?? null;

        if (! empty($original['reqeustId'])) {
            $order->payment_ref_id = $original['reqeustId'];
        }

        $order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
        $order->payment_status = Order::PAYMENT_STATUS_PENDING;
        $order->save();
    }
}
