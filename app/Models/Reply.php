<?php

namespace App\Models;

use App\Common\Attachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;

class Reply extends BaseModel
{
    use Attachable, HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'replies';

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['repliable'];

    /**
     * The attributes that should be casted to boolean types.
     *
     * @var array
     */
    protected $casts = [
        'read' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reply',
        'user_id',
        'customer_id',
        'read',
        'repliable_id',
        'repliable_type',
        'parent_id',
    ];

    /**
     * Extra JSON fields for apps and the storefront chatbox.
     *
     * @var array
     */
    protected $appends = [
        'quoted_reply',
    ];

    /**
     * Get all of the owning repliable models.
     */
    public function repliable()
    {
        return $this->morphTo();
    }

    /**
     * Message this reply is quoting (WhatsApp-style).
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Direct quote-replies to this message.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Resolve a quoted parent that belongs to the same conversation thread.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $conversation
     * @param  \Illuminate\Http\Request|array|null  $request
     */
    public static function resolveQuotedParent($conversation, $request = null): ?self
    {
        if (! $conversation || ! Schema::hasColumn('replies', 'parent_id')) {
            return null;
        }

        $payload = is_array($request) ? $request : (array) ($request?->all() ?? []);
        $id = (int) ($payload['parent_id'] ?? $payload['reply_to_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return static::query()
            ->where('id', $id)
            ->where('repliable_id', $conversation->getKey())
            ->where('repliable_type', $conversation->getMorphClass())
            ->first();
    }

    /**
     * Compact quote payload for API, sockets, and web bubbles.
     *
     * @return array<string, mixed>|null
     */
    public static function quoteSnapshot(?self $reply): ?array
    {
        if (! $reply) {
            return null;
        }

        $snippet = function_exists('livechat_quoted_snippet')
            ? livechat_quoted_snippet((string) ($reply->reply ?? ''))
            : mb_substr(trim(strip_tags((string) ($reply->reply ?? ''))), 0, 80);

        return [
            'id' => $reply->id,
            'reply' => $snippet,
            'sender_name' => $reply->getName(),
            'sender_type' => $reply->customer_id ? 'customer' : 'merchant',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getQuotedReplyAttribute(): ?array
    {
        try {
            if (! Schema::hasColumn('replies', 'parent_id')) {
                return null;
            }

            $parentId = $this->getAttribute('parent_id');
            if (! $parentId) {
                return null;
            }

            $parent = $this->relationLoaded('parent')
                ? $this->getRelation('parent')
                : $this->parent()->first();

            return self::quoteSnapshot($parent);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Get the user associated with the model.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => trans('app.user'),
        ]);
    }

    /**
     * Get the customer associated with the model.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class)
            ->withDefault([
                'name' => trans('app.guest_customer'),
            ]);
    }

    /**
     * Get the sender Name.
     */
    public function getName()
    {
        $user = $this->customer_id ? $this->customer : $this->user;

        return $user->getName();
    }

    /**
     * Get the sender avatar.
     */
    public function getAvatar()
    {
        $user = $this->customer_id ? $this->customer : $this->user;

        return get_avatar_src($user, 'mini');
    }
}
