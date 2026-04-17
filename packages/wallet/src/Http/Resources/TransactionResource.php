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
        return [
            'id' => $this->id,
            'date' => $this->created_at->toDayDateTimeString(),
            'description' => $this->meta['description'],
            'type' => $this->type,
            'amount' => get_formated_currency($this->amount, 2, config('system_settings.currency.id')),
            'amount_raw' => $this->amount,
            'balance' => get_formated_currency($this->balance, 2, config('system_settings.currency.id')),
            'balance_raw' => $this->balance,
            // 'status' => $this->confirmed ? trans('packages.wallet.confirmed') : trans('packages.wallet.pending'),
            // 'approved' => (bool) $this->approved,
        ];
    }
}
