<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
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
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category_slug' => optional($this->category)->slug,
            'url' => get_category_url($this->resource),
            'description' => $this->description,
            'featured' => (bool) $this->featured,
            'feature_image' => $this->when($this->featureImage, get_storage_file_url(optional($this->featureImage)->path, 'medium')),
            'cover_image' => get_cover_img_src($this, 'category'),
            'active' => (bool) $this->active,
        ];
    }
}
