<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A checkout payment waiting on the gateway. No order exists until it is paid.
 */
class PaymentIntent extends Model
{
    const STATUS_CREATED = 'created';       // Carts validated, gateway not called yet

    const STATUS_PROCESSING = 'processing'; // Gateway request in flight

    const STATUS_PENDING = 'pending';       // Waiting for the customer to approve on the phone

    const STATUS_PAID = 'paid';             // Gateway confirmed, orders not created yet

    const STATUS_COMPLETED = 'completed';   // Orders created

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_EXPIRED = 'expired';

    /** Gateways that confirm asynchronously and therefore go through an intent. */
    const ASYNC_METHODS = ['mpesa', 'emola'];

    const LIFETIME_MINUTES = 15;

    protected $table = 'payment_intents';

    protected $guarded = ['id'];

    protected $casts = [
        'cart_ids' => 'array',
        'payload' => 'array',
        'order_ids' => 'array',
        'checkout_all' => 'boolean',
        'amount' => 'float',
        'fee' => 'float',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (PaymentIntent $intent) {
            $intent->uuid = $intent->uuid ?: (string) Str::uuid();
            $intent->expires_at = $intent->expires_at ?: now()->addMinutes(self::LIFETIME_MINUTES);
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public static function usesIntent(?string $paymentMethod): bool
    {
        return in_array((string) $paymentMethod, self::ASYNC_METHODS, true);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return Order::withTrashed()->whereIn('id', $this->order_ids ?: [])->orderBy('id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_CREATED, self::STATUS_PROCESSING, self::STATUS_PENDING], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_EXPIRED], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpiredByTime(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_CREATED, self::STATUS_PROCESSING, self::STATUS_PENDING]);
    }

    /**
     * Seconds until the waiting screen should give up.
     */
    public function secondsLeft(): int
    {
        return $this->expires_at ? (int) max(0, now()->diffInSeconds($this->expires_at, false)) : 0;
    }
}
