<?php

namespace Incevio\Package\Wallet\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $currencyId = config('system_settings.currency.id');
        $showBreakdown = $this->showsDepositPaymentBreakdown();
        $walletCredit = $showBreakdown ? $this->depositWalletCredit() : null;
        $paymentTotal = $showBreakdown ? $this->depositPaymentTotal() : null;
        $platformFee = $showBreakdown ? $this->depositPlatformFee() : 0.0;

        return [
            'id' => $this->id,
            'date' => $this->created_at->toDayDateTimeString(),
            'description' => $this->meta['description'] ?? null,
            'type' => $this->type,
            'amount' => get_formated_currency($this->amount, 2, $currencyId),
            'amount_raw' => $this->amount,
            'balance' => get_formated_currency($this->balance, 2, $currencyId),
            'balance_raw' => $this->balance,
            'wallet_credit' => $walletCredit !== null
                ? get_formated_currency($walletCredit, 2, $currencyId)
                : null,
            'wallet_credit_raw' => $walletCredit,
            'payment_total' => $paymentTotal !== null
                ? get_formated_currency($paymentTotal, 2, $currencyId)
                : null,
            'payment_total_raw' => $paymentTotal,
            'platform_fee' => $platformFee > 0
                ? get_formated_currency($platformFee, 2, $currencyId)
                : null,
            'platform_fee_raw' => $platformFee > 0 ? $platformFee : null,
            'shows_deposit_breakdown' => $showBreakdown,
        ];
    }
}
