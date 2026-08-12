<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PushCampaign extends Model
{
    public const AUDIENCE_CUSTOMERS = 'customers';

    public const AUDIENCE_VENDORS = 'vendors';

    public const AUDIENCE_DELIVERY = 'delivery';

    public const AUDIENCE_ALL = 'all';

    public const TYPE_PROMOTION = 'promotion';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_CUSTOM = 'custom';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'title',
        'body',
        'image_url',
        'audience',
        'type',
        'deep_link',
        'data',
        'status',
        'target_count',
        'sent_count',
        'failed_count',
        'error_message',
        'created_by',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_CUSTOMERS => 'Customers (Customer App)',
            self::AUDIENCE_VENDORS => 'Vendors (Vendor App)',
            self::AUDIENCE_DELIVERY => 'Delivery partners',
            self::AUDIENCE_ALL => 'All apps',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_ANNOUNCEMENT => 'Announcement',
            self::TYPE_CUSTOM => 'Custom',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markQueued(): void
    {
        $this->status = self::STATUS_QUEUED;
        $this->error_message = null;
        if (! $this->created_by && Auth::check()) {
            $this->created_by = Auth::id();
        }
        $this->save();
    }
}
