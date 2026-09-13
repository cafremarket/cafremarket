<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray($request)
    {
        $vendor = $request->is('api/vendor/*');

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticketRef(),
            'raised_by' => $this->raised_by,
            'raised_by_label' => $this->raisedByLabel(),
            'reason' => optional($this->dispute_type)->detail,
            'progress' => $this->progress(),
            'closed' => $this->isClosed(),
            'resolved' => $this->isResolved(),
            'close_requested' => $this->isCloseRequested(),
            'can_reply' => $this->canReply(),
            'can_resolve' => $this->canMarkResolved(),
            'can_request_close' => $this->canRequestClose(),
            'description' => $this->description,
            'goods_received' => $this->order_received,
            'return_goods' => $this->return_goods,
            'status' => $this->statusName(true),
            'refund_amount' => $this->refund_amount ? get_formated_currency($this->refund_amount, 2, $this->order->currency_id) : null,
            'refund_amount_raw' => $this->refund_amount,
            'updated_at' => $this->updated_at->diffForHumans(),
            'shop' => $this->when(! $vendor, new ShopLightResource($this->shop)),
            'customer' => $this->when($vendor, new CustomerLightResource($this->customer)),
            'order_details' => new OrderLightResource($this->order),
            'attachments' => AttachmentResource::collection($this->attachments),
            'replies' => ReplyResource::collection($this->replies),
        ];
    }
}
