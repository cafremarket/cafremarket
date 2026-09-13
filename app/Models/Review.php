<?php

namespace App\Models;

use App\Common\Attachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends BaseModel
{
    use Attachable, HasFactory, SoftDeletes;

    public const TYPE_PRODUCT = 'product';

    public const TYPE_STORE = 'store';

    protected $table = 'reviews';

    protected $casts = [
        'approved' => 'boolean',
        'spam' => 'boolean',
        'replied_at' => 'datetime',
    ];

    protected $fillable = [
        'type',
        'reviewable_id',
        'reviewable_type',
        'shop_id',
        'customer_id',
        'order_id',
        'rating',
        'comment',
        'approved',
        'spam',
        'reply',
        'replied_by',
        'replied_at',
    ];

    /**
     * Get the owning reviewable model (Shop for store reviews, Inventory for product reviews).
     */
    public function reviewable()
    {
        return $this->morphTo();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function replier()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function deleteRequests()
    {
        return $this->hasMany(ReviewDeleteRequest::class);
    }

    public function pendingDeleteRequest()
    {
        return $this->hasOne(ReviewDeleteRequest::class)
            ->where('status', ReviewDeleteRequest::STATUS_PENDING);
    }

    public function scopeProduct($query)
    {
        return $query->where('type', self::TYPE_PRODUCT);
    }

    public function scopeStore($query)
    {
        return $query->where('type', self::TYPE_STORE);
    }

    public function hasReply(): bool
    {
        return ! empty($this->reply);
    }

    public function hasPendingDeleteRequest(): bool
    {
        return $this->deleteRequests()
            ->where('status', ReviewDeleteRequest::STATUS_PENDING)
            ->exists();
    }

    public function setRatingAttribute($value)
    {
        $this->attributes['rating'] = $value ? (int) $value : 1;
    }
}
