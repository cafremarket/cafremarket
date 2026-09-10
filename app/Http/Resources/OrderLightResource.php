<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderLightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $vendor = $request->is('api/vendor/*');

        return [
            'id' => $this->id,
            'type' => $this->type,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer->name,
            'auction_bid_id' => $this->auction_bid_id,
            'dispute_id' => optional($this->dispute)->id,
            'order_status' => $this->orderStatus(true),
            'payment_status' => $this->paymentStatusName(true),
            'payment_is_paid' => $this->isPaid(),
            'message_to_customer' => $this->message_to_customer,
            'grand_total' => get_formated_currency($this->grand_total, 2, $this->currency_id),
            'grand_total_raw' => get_formated_value($this->grand_total, $this->currency_id),
            // Transaction fees are admin-only (stored on order; not exposed to customer/vendor apps).
            'transaction_fee' => null,
            'total_paid' => null,
            'order_date' => date('F j, Y', strtotime($this->created_at)),
            'shipping_date' => $this->shipping_date ? date('F j, Y', strtotime($this->shipping_date)) : null,
            'delivery_date' => $this->delivery_date ? date('F j, Y', strtotime($this->delivery_date)) : null,
            'goods_received' => $this->goods_received,
            // 'feedback_given' => (bool) $this->feedback_id,
            'can_evaluate' => $this->canEvaluate(),
            'tracking_id' => $this->tracking_id,
            'tracking_url' => $this->getTrackingUrl(),
            'fulfillment_method' => $this->fulfillment_method,
            'delivery_status_label' => $this->deliveryStatusLabel(),
            'item_count' => $this->when($vendor, $this->inventories_count),
            'delivery_boy' => new DeliveryBoyLightResource($this->deliveryBoy),
            $this->mergeWhen(! $vendor, [
                'shop' => new ShopLightResource($this->shop),
                'items' => OrderItemResource::collection($this->inventories),
            ]),
        ];
    }
}
