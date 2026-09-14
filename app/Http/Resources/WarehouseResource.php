<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // opening_time/close_time default to the string "0" at the DB level
        // when never set — never hand that back as if it were a real time.
        $openingTime = $this->opening_time && $this->opening_time !== '0' ? $this->opening_time : null;
        $closeTime = $this->close_time && $this->close_time !== '0' ? $this->close_time : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'incharge' => new StaffLightResource($this->manager),
            'description' => $this->description,
            'pickup_instruction' => $this->pickup_instruction,
            'opening_time' => $openingTime,
            'closing_time' => $closeTime,
            'business_days' => is_array($this->business_days) ? $this->business_days : null,
            'primary_address' => new AddressResource($this->pickupAddress()),
            'manager' => $this->manager,
            'active' => (bool) $this->active,
            'image' => get_storage_file_url($this->image),
        ];
    }
}
