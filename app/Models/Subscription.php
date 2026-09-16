<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $with = [];

    protected $fillable = [
        'shop_id',
        'type',
        'name',
        'billing_plan',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function owner()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function shop()
    {
        return $this->owner();
    }

    public function swap($plan, $options = [])
    {
        $subscriptionPlan = SubscriptionPlan::findOrFail($plan);

        if (
            $this->billing_plan !== $plan
            && (float) $subscriptionPlan->cost > 0
            && ! $this->onTrial()
        ) {
            $this->owner->forceWithdraw(
                (float) $subscriptionPlan->cost,
                subscription_charge_meta($subscriptionPlan->name)
            );
        }

        $this->fill([
            'billing_plan' => $plan,
            'type' => $subscriptionPlan->name,
            'ends_at' => now()->addMonth(),
            'trial_ends_at' => null,
        ])->save();

        return $this;
    }

    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function valid()
    {
        return $this->active() || $this->onTrial();
    }

    public function canceled()
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function onGracePeriod()
    {
        return false;
    }

    public function active()
    {
        if ($this->onTrial()) {
            return true;
        }

        if ($this->ends_at === null) {
            return false;
        }

        return $this->ends_at->isFuture();
    }

    public function cancel()
    {
        $this->forceFill([
            'ends_at' => now(),
            'trial_ends_at' => null,
        ])->save();

        return $this;
    }

    public function cancelNow()
    {
        return $this->cancel();
    }

    public function resume()
    {
        return $this;
    }

    public function extendTrial($date)
    {
        $this->forceFill([
            'trial_ends_at' => $date instanceof Carbon ? $date : Carbon::parse($date),
        ])->save();

        return $this;
    }

    public function getProviderAttribute()
    {
        return 'wallet';
    }
}
