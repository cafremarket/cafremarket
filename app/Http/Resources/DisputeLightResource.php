<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisputeLightResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticketRef(),
            'raised_by' => $this->raised_by,
            'raised_by_label' => $this->raisedByLabel(),
            'reason' => optional($this->dispute_type)->detail,
            'closed' => $this->isClosed(),
            'resolved' => $this->isResolved(),
            'close_requested' => $this->isCloseRequested(),
            'can_resolve' => $this->canMarkResolved(),
            'can_request_close' => $this->canRequestClose(),
            'goods_received' => $this->order_received,
            'return_goods' => $this->return_goods,
            'status' => $this->statusName(true),
            'updated_at' => $this->updated_at->diffForHumans(),
            'shop' => new ShopLightResource($this->shop),
            'order_details' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'order_status' => $this->order->orderStatus(true),
                'payment_status' => $this->order->paymentStatusName(true),
                'grand_total' => get_formated_currency($this->order->grand_total, 2, $this->order->currency_id),
                'grand_total_raw' => get_formated_value($this->order->grand_total, $this->order->currency_id),
                'order_date' => date('F j, Y', strtotime($this->order->created_at)),
                'items' => OrderItemResource::collection($this->order->inventories),
            ],
        ];
    }
}
