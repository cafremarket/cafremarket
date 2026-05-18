<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentFailedException;
use App\Services\Emola\EmolaOrderPaymentService;
use Illuminate\Http\Request;

class EmolaPaymentService extends PaymentService
{
    public function __construct(
        Request $request,
        private readonly EmolaOrderPaymentService $emolaOrders,
    ) {
        parent::__construct($request);
    }

    public function charge()
    {
        if (! $this->order) {
            throw new PaymentFailedException('Order not found for eMola payment.');
        }

        $res = $this->emolaOrders->pushPaymentForOrder(
            $this->order,
            (string) $this->request->input('emola_number'),
            $this->description ?: 'Pagamento',
        );

        // USSD push accepted — payment is confirmed only via eMola callback, never here.
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

