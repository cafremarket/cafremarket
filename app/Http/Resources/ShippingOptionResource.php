<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $rate = $this->rate ?? $this->cost_raw ?? 0;
        $outOfRange = (bool) ($this->out_of_range ?? false);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'shipping_zone_id' => $this->shipping_zone_id,
            'carrier_id' => $this->carrier_id,
            'carrier_name' => $this->carrier_name,
            'cost' => $outOfRange
                ? null
                : get_formated_currency($rate, config('system_settings.decimals', 2)),
            'cost_raw' => $outOfRange ? null : $rate,
            'distance_km' => $this->distance_km ?? null,
            'out_of_range' => $outOfRange,
            'service_radius_km' => $this->service_radius_km ?? null,
            'delivery_takes' => $this->delivery_takes
                ? (is_numeric($this->delivery_takes)
                    ? trans('api.estimated_delivery_time', ['time' => $this->delivery_takes])
                    : $this->delivery_takes)
                : null,
        ];
    }
}
