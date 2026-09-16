<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'order_id' => $this->order_id,
            'order_number' => $this->order->order_number ?? null,
            'order_fulfilled' => $this->order_fulfilled,
            'return_goods' => $this->return_goods,
            'amount' => $this->amount,
            'description' => $this->description,
            'admin_note' => $this->admin_note,
            'failure_reason' => $this->failure_reason,
            'status' => $this->status,
            // New module labels: Pending / Completed / Issue
            'status_label' => $this->statusLabel(),
            // Legacy labels for older apps: New / Approved / Declined
            'status_name' => $this->legacyStatusLabel(),
            'legacy_status' => $this->legacyStatusLabel(),
            'is_open' => $this->isOpen(),
            'is_closed' => ! $this->isOpen(),
        ];
    }
}
