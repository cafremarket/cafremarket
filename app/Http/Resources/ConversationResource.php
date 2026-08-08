<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $lastMessage = method_exists($this->resource, 'lastMessagePlain')
            ? $this->resource->lastMessagePlain()
            : (string) ($this->message ?? '');

        $unreadCount = method_exists($this->resource, 'unreadCustomerRepliesCount')
            ? $this->resource->unreadCustomerRepliesCount()
            : 0;

        $hasUnreadCountAttr = array_key_exists('unread_count', $this->resource->getAttributes());
        $isUnread = $hasUnreadCountAttr
            ? ((int) $unreadCount > 0)
            : (method_exists($this->resource, 'isUnread')
                ? (bool) $this->resource->isUnread()
                : ((int) $unreadCount > 0));

        return [
            'id' => $this->id,
            'shop_id' => (int) $this->shop_id,
            'user' => $this->when($this->user_id, new UserResource($this->user)),
            'customer' => $this->when($this->customer_id, new CustomerLightResource($this->customer)),
            'shop' => $this->when($this->shop_id, new ShopDryResource($this->shop)),
            'subject' => $this->subject,
            // Prefer latest reply text so inbox always shows the newest chat line.
            'message' => $lastMessage !== '' ? $lastMessage : $this->message,
            'order_id' => $this->when($this->order_id, (int) $this->order_id),
            'item' => $this->when($this->item, new ItemLightResource($this->item)),
            'status' => $this->status,
            'is_unread' => (bool) $isUnread,
            'unread_count' => (int) $unreadCount,
            'label' => $this->label,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'updated_at_human' => optional($this->updated_at)->diffForHumans(),
            'attachments' => $this->when($this->attachments, AttachmentResource::collection($this->attachments)),
            'replies' => ReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
