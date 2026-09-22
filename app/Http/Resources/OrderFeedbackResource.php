<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderFeedbackResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'created_at_human' => optional($this->created_at)->diffForHumans(),
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->getName(),
            ],
        ];
    }
}
