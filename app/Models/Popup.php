<?php

namespace App\Models;

use App\Common\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Popup extends BaseModel
{
    use HasFactory, Imageable;

    protected $table = 'popups';

    public const PLATFORM_ALL = 'all';

    public const PLATFORM_WEB = 'web';

    public const PLATFORM_APP = 'app';

    public const PAGE_ALL = 'all';

    public const PAGE_HOME = 'home';

    public const PAGE_PRODUCT = 'product';

    public const PAGE_CATEGORY = 'category';

    public const PAGE_CART = 'cart';

    public const PAGE_CHECKOUT = 'checkout';

    public const USER_TYPE_ALL = 'all';

    public const USER_TYPE_GUEST = 'guest';

    public const USER_TYPE_CUSTOMER = 'customer';

    public const FREQUENCY_EVERY_PAGE_LOAD = 'every_page_load';

    public const FREQUENCY_ONCE_PER_SESSION = 'once_per_session';

    public const FREQUENCY_ONCE_PER_DAY = 'once_per_day';

    public const FREQUENCY_ONCE_ONLY = 'once_only';

    protected $casts = [
        'active' => 'boolean',
        'hide_text' => 'boolean',
        'delay_ms' => 'integer',
        'priority' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $fillable = [
        'title',
        'headline',
        'description',
        'button_label',
        'button_link',
        'bg_color',
        'hide_text',
        'platform',
        'page',
        'user_type',
        'frequency',
        'delay_ms',
        'priority',
        'active',
        'starts_at',
        'ends_at',
    ];

    /**
     * Scope a query to popups eligible for the given platform (or platform-agnostic ones).
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where(function ($q) use ($platform) {
            $q->where('platform', self::PLATFORM_ALL)->orWhere('platform', $platform);
        });
    }

    /**
     * Scope a query to popups eligible for the given page (or page-agnostic ones).
     */
    public function scopeForPage($query, string $page)
    {
        return $query->where(function ($q) use ($page) {
            $q->where('page', self::PAGE_ALL)->orWhere('page', $page);
        });
    }

    /**
     * Scope a query to popups eligible for the given viewer type (or type-agnostic ones).
     */
    public function scopeForUserType($query, string $userType)
    {
        return $query->where(function ($q) use ($userType) {
            $q->where('user_type', self::USER_TYPE_ALL)->orWhere('user_type', $userType);
        });
    }

    /**
     * Scope a query to popups that are active and within their scheduled window.
     */
    public function scopeScheduled($query)
    {
        $now = now();

        return $query->where('active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }
}
