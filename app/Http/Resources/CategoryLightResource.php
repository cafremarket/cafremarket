<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryLightResource extends JsonResource
{
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
            'name' => $this->name,
            'category_id' => $this->resource->getAttribute('category_id'),
            $this->mergeWhen($request->is('api/vendor/categories'), [
                'featured' => (bool) $this->featured,
                'category_id' => $this->category_id,
                'category_name' => optional($this->category)->name,
                'feature_image' => get_storage_file_url(optional($this->featureImage)->path, 'medium'),
                'cover_image' => get_cover_img_src($this, 'category'),
                'active' => (bool) $this->active,
            ]),
        ];
    }
}
