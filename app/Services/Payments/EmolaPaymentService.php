<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Services\Emola\EmolaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmolaPaymentService extends PaymentService
{
    public function __construct(Request $request, private readonly EmolaClient $client)
    {
        parent::__construct($request);
    }

    public function charge()
    {
        if (! $this->order) {
            throw new PaymentFailedException('Order not found for eMola payment.');
        }

        $msisdn = (string) $this->request->input('emola_number');

        // eMola requires a unique transId per transaction (15-30 in doc). We use deterministic + random suffix.
        $transId = (string) ($this->order->id.'-'.Str::upper(Str::random(10)));
        $transId = substr(preg_replace('/[^A-Za-z0-9\-]/', '', $transId), 0, 30);

        $refNo = (string) $this->order->id;
        $refNo = substr(preg_replace('/[^A-Za-z0-9]/', '', $refNo), 0, 20);

        $amount = (string) intval($this->amount);

        $sms = $this->description ?: 'Pagamento';

        $res = $this->client->pushUssdMessage([
            'msisdn' => $msisdn,
            'transId' => $transId,
            'transAmount' => $amount,
            'smsContent' => $sms,
            'language' => app()->getLocale() === 'en' ? 'en' : 'pt',
            'refNo' => $refNo,
        ]);

        Log::info('eMola pushUssdMessage result', [
            'order_id' => $this->order->id,
            'gateway_ok' => $res->ok(),
            'gateway_error' => $res->gatewayError,
            'gwtransid' => $res->gwtransid,
            'original' => $res->originalData,
        ]);

        // Persist tracking fields on the order (even if gateway errors).
        $this->order->emola_trans_id = $transId;
        $this->order->emola_ref_no = $refNo;
        $this->order->emola_gwtransid = $res->gwtransid;
        $this->order->emola_gateway_error = $res->gatewayError;
        $this->order->emola_gateway_description = $res->gatewayDescription;

        $detailCode = $res->originalData['errorCode'] ?? null;
        $detailMsg = $res->originalData['message'] ?? null;
        $requestId = $res->originalData['reqeustId'] ?? null; // spec misspelling

        $this->order->emola_error_code = $detailCode;
        $this->order->emola_message = $detailMsg;
        $this->order->emola_request_id = $requestId;

        // Keep existing generic reference column populated for admin/support.
        if ($requestId) {
            $this->order->payment_ref_id = $requestId;
        }

        $this->order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
        $this->order->payment_status = Order::PAYMENT_STATUS_PENDING;
        $this->order->save();

        // If synchronous mode returns success immediately.
        if ($res->ok() && $detailCode === '0') {
            $this->status = self::STATUS_PAID;

            return $this;
        }

        // Async mode returns "22 Push message done" or a pending-like response.
        if ($res->ok()) {
            $this->status = self::STATUS_PENDING;

            return $this;
        }

        $this->status = self::STATUS_ERROR;
        throw new PaymentFailedException($res->gatewayDescription ?: 'eMola payment request failed.');
    }

    public function setConfig()
    {
        // Client is configured through config/services.php + .env
        if (! $this->amount || ! is_numeric($this->amount) || intval($this->amount) < 1) {
            throw new PaymentFailedException('Invalid amount.');
        }

        if (! $this->request->filled('emola_number')) {
            throw new PaymentFailedException('Invalid eMola number.');
        }

        return $this;
    }
}

