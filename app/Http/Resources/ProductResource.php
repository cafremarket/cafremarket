<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $inventory = $this->inventories->first();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'model_number' => $this->model_number,
            'status' => $this->active,
            'gtin' => $this->gtin,
            'gtin_type' => $this->gtin_type,
            'mpn' => $this->mpn,
            'brand' => $this->brand,
            'downloadable' => $this->downloadable,
            'manufacturer' => $this->manufacturer
                ? [
                    'id' => $this->manufacturer->id,
                    'name' => $this->manufacturer->name,
                    'slug' => $this->manufacturer->slug,
                ]
                : null,
            'requirement_shipping' => $this->requires_shipping,
            'categories' => CategoryLightResource::collection($this->categories),
            'origin' => optional($this->origin)->name,
            'listing_count' => $this->inventories_count,
            'description' => $this->description,
            'video' => $this->video_path ? get_product_video_url($this->video_path) : null,
            'available_from' => date('F j, Y', strtotime($this->created_at)),
            'sku' => optional($inventory)->sku,
            'condition' => optional($inventory)->condition,
            'condition_note' => optional($inventory)->condition_note,
            'stock_quantity' => optional($inventory)->stock_quantity,
            'min_order_quantity' => optional($inventory)->min_order_quantity,
            'sale_price' => optional($inventory)->sale_price,
            'offer_price' => optional($inventory)->offer_price,
            'offer_start' => optional($inventory)->offer_start,
            'offer_end' => optional($inventory)->offer_end,
            'images' => ImageResource::collection($this->images),
            'tag_list' => $this->tags->pluck('id')->values()->all(),
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])->values()->all(),
        ];
    }
}
