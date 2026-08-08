<?php

namespace Incevio\Package\LiveChat\Models;

use App\Common\Attachable;
use App\Common\Repliable;
use App\Models\BaseModel;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatConversation extends BaseModel
{
    use SoftDeletes, Attachable, Repliable;

    const STATUS_NEW = 1;         //Default
    const STATUS_UNREAD = 2;       //All status before UNREAD value consider as unread
    const STATUS_READ = 3;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'chat_conversations';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'private' => 'boolean',
        'read' => 'boolean',
    ];

    /**
     * Get the customer associated with the model.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class)
            ->withDefault([
                'name' => trans('app.guest_customer'),
            ])
            ->withoutGlobalScope('MineScope');
    }

    /**
     * Get the shop.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the shop agent.
     */
    public function agent()
    {
        return $this->shop->config->supportAgent();
    }

    /**
     * Get the sender avatar.
     */
    public function getAvatar()
    {
        return get_avatar_src($this->customer, 'mini');
    }

    /**
     * Get the sender Name.
     */
    public function getName()
    {
        return $this->customer->getName();
    }

    /**
     * Mark the chat as read
     */
    public function markAsRead()
    {
        $this->status = self::STATUS_READ;
        $this->save();
    }

    /**
     * Mark peer replies as read for the given viewer.
     * merchant → marks customer replies; customer → marks merchant replies.
     */
    public function markPeerRepliesAsRead(string $viewer = 'merchant'): int
    {
        $query = $this->replies()->where(function ($q) {
            $q->whereNull('read')->orWhere('read', false);
        });

        if ($viewer === 'customer') {
            // Merchant messages have no customer_id
            $query->whereNull('customer_id');
        } else {
            // Customer messages have customer_id
            $query->whereNotNull('customer_id');
        }

        return $query->update(['read' => true]);
    }

    /**
     * Update last preview text + bump sorting timestamp.
     * When $markUnread is true (customer message), set status to unread for merchant inbox.
     */
    public function bumpLastMessage(string $text, bool $markUnread = false): void
    {
        $this->message = $text;
        if ($markUnread) {
            $this->status = self::STATUS_UNREAD;
        }
        $this->updated_at = $this->freshTimestamp();
        $this->save();
    }

    /**
     * Unread replies for the current viewer.
     * Vendor API: customer messages. Customer API: merchant messages.
     */
    public function unreadCustomerRepliesCount(): int
    {
        if (isset($this->unread_count)) {
            return (int) $this->unread_count;
        }

        // Default count used by merchant inbox (customer-authored replies).
        return (int) $this->replies()
            ->whereNotNull('customer_id')
            ->where(function ($q) {
                $q->whereNull('read')->orWhere('read', false)->orWhere('read', 0);
            })
            ->count();
    }

    /**
     * Plain last message text for API/inbox preview.
     */
    public function lastMessagePlain(): string
    {
        $reply = $this->relationLoaded('lastReply') ? $this->lastReply : $this->lastReply()->first();
        if ($reply && filled($reply->reply)) {
            return (string) $reply->reply;
        }

        return (string) ($this->message ?? '');
    }

    /**
     * Mark the chat as unread
     */
    public function markAsUnread()
    {
        $this->status = self::STATUS_UNREAD;
        $this->save();
    }

    /**
     * Return the recent message in a Conversation.
     *
     * @return HasOne
     */
    public function last_message()
    {
        $reply = $this->lastReply;

        if ($reply) {
            return $reply->customer_id ? $reply->reply :
                '<span class="small text-muted">' . trans('app.you') . ': </span>' . $reply->reply;
        }

        return $this->message;
    }

    public function isUnread()
    {
        return $this->status < self::STATUS_READ;
    }

    public function statusName($plain = false)
    {
        $status = get_chat_status_name($this->status);

        if ($plain) {
            return $status;
        }

        switch ($this->status) {
            case static::STATUS_NEW:
                return '<span class="label label-primary">' . $status . '</span>';
            case static::STATUS_READ:
                return '<span class="label label-primary">' . $status . '</span>';
            case static::STATUS_UNREAD:
                return '<span class="label label-primary">' . $status . '</span>';
        }
    }
}
