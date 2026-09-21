<?php

namespace App\Models;

use App\Common\Attachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;

class Reply extends BaseModel
{
    use Attachable, HasFactory;

    const TYPE_TEXT = 'text';

    const TYPE_ATTACHMENT = 'attachment';

    const TYPE_LOCATION = 'location';

    const TYPE_CONTACT = 'contact';

    const TYPE_PRODUCT_SHARE = 'product_share';

    const TYPE_ORDER_SHARE = 'order_share';

    /**
     * Legacy magic-string prefixes once used to encode a message "type"
     * directly inside the plain-text body. New replies use the `type`/
     * `payload` columns instead — these are kept only to render old rows.
     */
    const LEGACY_PREFIXES = [
        '[product_share]' => self::TYPE_PRODUCT_SHARE,
        '[order_share]' => self::TYPE_ORDER_SHARE,
    ];

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
        'payload' => 'array',
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
        'type',
        'payload',
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
     * Parse the legacy `[product_share]{json}` / `[order_share]{json}` prefix
     * convention out of a plain-text body. Used only as a read-time fallback
     * for rows created before the `type`/`payload` columns existed.
     *
     * @return array{type: string, payload: array<string, mixed>|null}
     */
    public static function legacyTypeFromBody(?string $reply): array
    {
        $body = (string) $reply;

        foreach (self::LEGACY_PREFIXES as $prefix => $type) {
            if (! str_starts_with($body, $prefix)) {
                continue;
            }

            $json = substr($body, strlen($prefix));
            $payload = json_decode($json, true);

            if (! is_array($payload)) {
                $start = strpos($json, '{');
                $end = strrpos($json, '}');
                if ($start !== false && $end !== false && $end > $start) {
                    $payload = json_decode(substr($json, $start, $end - $start + 1), true);
                }
            }

            return ['type' => $type, 'payload' => is_array($payload) ? $payload : null];
        }

        if (trim($body) === '[attachment]') {
            return ['type' => self::TYPE_ATTACHMENT, 'payload' => null];
        }

        return ['type' => self::TYPE_TEXT, 'payload' => null];
    }

    /**
     * The real `type` when it's anything other than the column default,
     * else derived from the legacy body prefix (covers both pre-migration
     * rows and genuinely plain-text messages, which parse back to TYPE_TEXT).
     */
    public function resolvedType(): string
    {
        $type = $this->getAttribute('type');

        if (filled($type) && $type !== self::TYPE_TEXT) {
            return $type;
        }

        return self::legacyTypeFromBody($this->reply)['type'];
    }

    /**
     * The real `payload` when set, else derived from the legacy body prefix.
     *
     * @return array<string, mixed>|null
     */
    public function resolvedPayload(): ?array
    {
        if (filled($this->getAttribute('payload'))) {
            return $this->getAttribute('payload');
        }

        return self::legacyTypeFromBody($this->reply)['payload'];
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
