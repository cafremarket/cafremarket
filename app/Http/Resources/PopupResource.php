<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PopupResource extends JsonResource
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
            'headline' => $this->hide_text ? null : $this->headline,
            'description' => $this->hide_text ? null : $this->description,
            'image' => $this->when($this->featureImage, get_storage_file_url(optional($this->featureImage)->path, null)),
            // When hide_text is on, the button's label is dropped (no text is
            // shown) but the link itself survives — the image becomes the tap
            // target instead of a separate button.
            'button_label' => $this->hide_text ? null : $this->button_label,
            'button_link' => $this->button_link,
            'bg_color' => $this->bg_color,
            'hide_text' => (bool) $this->hide_text,
            'delay_ms' => (int) $this->delay_ms,
            'frequency' => $this->frequency,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
