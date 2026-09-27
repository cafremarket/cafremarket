<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    protected $currency_id;

    public function __construct($resource, $currency_id)
    {
        parent::__construct($resource);
        $this->$currency_id = $currency_id;
    }

    /**
     * The order this item belongs to, unless it is canceled. Loaded by id: when
     * orders are eager-loaded, the pivot's parent is an empty placeholder model.
     */
    protected function refundOrder(): ?\App\Models\Order
    {
        static $orders = [];

        $orderId = $this->pivot->order_id ?? null;

        if (! $orderId) {
            return null; // cart line, not an order item
        }

        $order = $orders[$orderId] ??= \App\Models\Order::find($orderId);

        return $order && ! $order->isCanceled() ? $order : null;
    }

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
            'slug' => $this->slug,
            'description' => $this->pivot->item_description,
            'quantity' => $this->pivot->quantity,
            'download_limit' => $this->download_limit,
            'download' => $this->pivot->download,
            'unit_price' => get_formated_currency($this->pivot->unit_price, config('system_settings.decimals', 2), $this->currency_id),
            'unit_price_raw' => strval(round((float) $this->pivot->unit_price, 2)),
            'total' => get_formated_currency($this->pivot->unit_price * $this->pivot->quantity, config('system_settings.decimals', 2), $this->currency_id),
            'total_raw' => strval(round((float) $this->pivot->unit_price * (int) $this->pivot->quantity, 2)),
            'refund_days' => \App\Services\Orders\RefundWindow::daysFor($this->resource),
            'refund_policy' => refund_period_label(\App\Services\Orders\RefundWindow::daysFor($this->resource)),
            // Only for order items (not cart lines): the item's refund/return window right now.
            'refund_window' => $this->when($this->refundOrder() !== null, function () {
                $window = \App\Services\Orders\RefundWindow::forItem($this->refundOrder(), $this->resource);

                return [
                    'status' => $window['status'],
                    'allowed' => $window['allowed'],
                    'days' => $window['days'],
                    'deadline' => optional($window['deadline'])->toDateTimeString(),
                    'label' => $window['label'],
                ];
            }),
            'image' => get_inventory_img_src($this, 'small'),
            'attachments' => AttachmentResource::collection($this->attachments),
            'feedback' => $this->when($request->is('api/order/*'), function () {
                $feedback = \App\Models\Feedback::find($this->pivot->feedback_id);

                return $feedback ? new FeedbackResource($feedback) : null;
            }),
        ];
    }
}
