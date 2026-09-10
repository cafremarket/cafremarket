<?php

namespace App\Models;

use App\Common\Addressable;
use App\Common\ApiAuthTokens;
use App\Common\Feedbackable;
use App\Common\HasHumanAttributes;
use App\Common\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DeliveryBoy extends Authenticatable
{
    // Deliberately no SoftDeletes: a deleted delivery boy is just gone —
    // no trash/restore state, so removing a rider can't leave a stale row
    // that email-uniqueness/password-copy checks would need to account for.
    use Addressable, ApiAuthTokens, Feedbackable, HasFactory, HasHumanAttributes, Imageable, Notifiable;

    protected $fillable = [
        'shop_id',
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

    /**
     * Every rider account (one per store) that shares this email, matched by
     * password — a rider who works for more than one store has a separate
     * row per store (email is only unique within a store, not system-wide).
     */
    public static function matchingAccounts(string $email, string $password)
    {
        return static::where('email', $email)
            ->with('shop:id,name')
            ->get()
            ->filter(fn (self $account) => Hash::check($password, $account->password))
            ->values();
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

    public function scopeMine($query)
    {
        return $query->where('shop_id', Auth::user()->merchantId());
    }
}
