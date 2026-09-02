<?php

namespace App\Models;

use App\Common\Addressable;
use App\Common\ApiAuthTokens;
use App\Common\Feedbackable;
use App\Common\HasHumanAttributes;
use App\Common\Imageable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DeliveryBoy extends Authenticatable
{
    use Addressable, ApiAuthTokens, Feedbackable, HasFactory, HasHumanAttributes, Imageable, Notifiable, SoftDeletes;

    const TYPE_SHOP = 'shop';

    const TYPE_PLATFORM = 'platform';

    protected $fillable = [
        'shop_id',
        'type',
        'first_name',
        'last_name',
        'nice_name',
        'email',
        'phone_number',
        'password',
        'status',
        'is_online',
        'current_latitude',
        'current_longitude',
        'last_location_at',
        'dob',
        'sex',
        'remember_token',
        'verification_token',
        'fcm_token',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_online' => 'boolean',
        'last_location_at' => 'datetime',
    ];

    protected $table = 'delivery_boys';

    protected $guard = 'delivery_boy';

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'api_token',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function isPlatform(): bool
    {
        return $this->type === self::TYPE_PLATFORM;
    }

    public function isShopRider(): bool
    {
        return $this->type === self::TYPE_SHOP;
    }

    public function setPasswordAttribute($password)
    {
        if ($password === null || $password === '') {
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($password)
            ? Hash::make($password)
            : $password;
    }

    public function scopeActive($query)
    {
        return $query->where('status', BaseModel::ACTIVE);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeShopRiders(Builder $query)
    {
        return $query->where('type', self::TYPE_SHOP);
    }

    public function scopePlatformRiders(Builder $query)
    {
        return $query->where('type', self::TYPE_PLATFORM);
    }

    public function scopeMine($query)
    {
        return $query->where('shop_id', Auth::user()->merchantId());
    }
}
