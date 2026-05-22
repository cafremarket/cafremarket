<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentFailedException;
use App\Services\Emola\EmolaDailyLimit;
use App\Services\Emola\EmolaOrderPaymentService;
use App\Services\Emola\EmolaSpec;
use App\Services\Emola\EmolaWalletDepositService;
use Illuminate\Http\Request;

class EmolaPaymentService extends PaymentService
{
    public function __construct(
        Request $request,
        private readonly EmolaOrderPaymentService $emolaOrders,
        private readonly EmolaWalletDepositService $emolaWallet,
    ) {
        parent::__construct($request);
    }

    public function charge()
    {
        if ($this->order) {
            return $this->chargeOrder();
        }

        if ($this->payee) {
            return $this->chargeWalletDeposit();
        }

        throw new PaymentFailedException('Order not found for eMola payment.');
    }

    private function chargeOrder()
    {
        $res = $this->emolaOrders->pushPaymentForOrder(
            $this->order,
            (string) $this->request->input('emola_number'),
            $this->description ?: 'Pagamento',
        );

        if ($res->isUssdPushAccepted()) {
            $this->status = self::STATUS_PENDING;

            return $this;
        }

        $this->status = self::STATUS_PENDING;
        $this->paymentNotice = $res->failureMessage();

        return $this;
    }

    private function chargeWalletDeposit()
    {
        $baseAmount = $this->meta['payment_base_amount'] ?? $this->amount;
        $result = $this->emolaWallet->pushDeposit(
            $this->payee,
            $baseAmount,
            (string) $this->request->input('emola_number'),
            $this->description ?: null,
        );

        $res = $result['response'];

        if ($res->isUssdPushAccepted()) {
            $this->status = self::STATUS_PENDING;

            return redirect()->to(url(
                'wallet/deposit/emola/complete?ref='.urlencode($result['transId'])
            ));
        }

        throw new PaymentFailedException($res->failureMessage());
    }

    public function setConfig()
    {
        if (! $this->amount || ! is_numeric($this->amount)) {
            throw new PaymentFailedException('Invalid amount.');
        }

        $context = $this->order ? EmolaSpec::CONTEXT_ORDER : EmolaSpec::CONTEXT_DEPOSIT;
        EmolaSpec::formatTransAmount($this->amount, $context);
        if (! empty($this->meta['payment_base_amount'])) {
            EmolaSpec::formatTransAmount($this->meta['payment_base_amount'], $context);
        }

        if ($this->request->filled('emola_number')) {
            EmolaDailyLimit::assertCanPay(
                (string) $this->request->input('emola_number'),
                EmolaSpec::parseMeticalAmount($this->amount),
            );
        }

        if (! $this->request->filled('emola_number')) {
            throw new PaymentFailedException(trans('theme.emola_number_required'));
        }

        return $this;
    }
}
