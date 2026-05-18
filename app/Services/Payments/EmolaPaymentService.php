<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentFailedException;
use App\Services\Emola\EmolaOrderPaymentService;
use App\Services\Emola\EmolaSpec;
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

        // USSD must be accepted by Movitel (errorCode 0 or 22) — payment confirmed only via callback.
        if ($res->isUssdPushAccepted()) {
            $this->status = self::STATUS_PENDING;

            return $this;
        }

        // Keep the order so the customer can resend USSD from the order page.
        $this->status = self::STATUS_PENDING;
        $this->paymentNotice = $res->failureMessage();

        return $this;
    }

    public function setConfig()
    {
        // Client is configured through config/services.php + .env
        if (! $this->amount || ! is_numeric($this->amount)) {
            throw new PaymentFailedException('Invalid amount.');
        }

        EmolaSpec::formatTransAmount($this->amount);

        if (! $this->request->filled('emola_number')) {
            throw new PaymentFailedException('Invalid eMola number.');
        }

        return $this;
    }
}

