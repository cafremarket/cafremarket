<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'to',
        'cc',
        'bcc',
        'subject',
        'notification',
        'status',
        'error',
        'context',
        'related_type',
        'related_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function related()
    {
        return $this->morphTo();
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => '<span class="label label-success">'.e(trans('app.sent')).'</span>',
            self::STATUS_FAILED => '<span class="label label-danger">'.e(trans('app.failed')).'</span>',
            default => '<span class="label label-warning">'.e(trans('app.pending')).'</span>',
        };
    }

    public function scopeFilter($query, array $filters)
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('to', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")
                    ->orWhere('notification', 'like', "%{$q}%")
                    ->orWhere('error', 'like', "%{$q}%");
            });
        }

        return $query;
    }
}
