<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\PolicyPages;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $slug = (string) $this->slug;
        $content = PolicyPages::isPolicySlug($slug)
            ? PolicyPages::resolveContent($slug, $this->content)
            : $this->content;

        $publishedAt = $this->published_at;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $content,
            'updated_at' => $publishedAt ? $publishedAt->diffForHumans() : null,
            'published_at' => $publishedAt ? date('F j, Y', strtotime($publishedAt)) : null,
        ];
    }
}
