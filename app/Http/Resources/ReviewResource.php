<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'type' => $this->type,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'approved' => $this->approved,
            'spam' => $this->spam,
            'updated_at' => $this->updated_at->diffForHumans(),
            'reply' => $this->reply,
            'replied_at' => $this->when($this->reply, optional($this->replied_at)->diffForHumans()),
            'images' => $this->attachments->map(fn ($attachment) => get_storage_file_url($attachment->path))->values(),
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->getName(),
                'avatar' => get_storage_file_url(optional($this->customer->avatarImage)->path, 'tiny'),
            ],
        ];
    }
}
