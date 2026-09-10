<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type ?: \App\Models\Tax::TYPE_PERCENT,
            'taxrate' => $this->taxrate,
            'label' => $this->label,
            'country' => new CountryResource($this->country),
            'state' => new StateResource($this->state),
            'active' => (bool) $this->active,
        ];
    }
}
