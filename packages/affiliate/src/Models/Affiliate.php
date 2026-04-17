<?php

namespace Incevio\Package\Affiliate\Models;

use App\Common\Billable;
use App\Common\Imageable;
use App\Common\Addressable;
use App\Common\ApiAuthTokens;
use App\Common\HasHumanAttributes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use \Incevio\Package\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Affiliate extends Authenticatable
{
    use HasFactory, SoftDeletes, HasWallet, ApiAuthTokens, HasHumanAttributes, Notifiable, Imageable, Billable, Addressable;

    protected $table = 'affiliates';

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'last_visited_at',
        'last_visited_from',
        'read_announcements_at',
        'remember_token',
        'verification_token',
    ];

    public function affiliateLinks()
    {
        return $this->hasMany(AffiliateLink::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * Setters
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::needsRehash($password) ? Hash::make($password) : $password;
    }

    public function getAffiliateUserName()
    {
        return $this->username ?? Auth::guard('affiliate')->user()->username;
    }
}
