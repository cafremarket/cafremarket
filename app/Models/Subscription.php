<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    /**
     * Wallet subscriptions do not use Cashier subscription items.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    // protected $appends = ['provider_plan'];

    /**
     * Get the "provider_plan" attribute from the model.
     *
     * @return string
     */
    // public function getProviderPlanAttribute()
    // {
    //     return Spark::billsUsingStripe()
    //                     ? $this->stripe_price : $this->braintree_plan;
    // }

    /**
     * Get the model related to the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    /**
     * Swap the subscription to a new Stripe plan.
     *
     * @param  string  $plan
     * @param  array  $options
     * @return $this
     *
     * @throws \Laravel\Cashier\Exceptions\SubscriptionUpdateFailure
     */
    public function swap($plan, $options = [])
    {
        // Local subscription
        if (SystemConfig::isBillingThroughWallet()) {
            $subscriptionPlan = SubscriptionPlan::findOrFail($plan);

            if (
                $this->stripe_price !== $plan
                && (float) $subscriptionPlan->cost > 0
                && ! $this->onTrial()
            ) {
                $meta = [
                    'type' => trans('app.subscription_fee'),
                    'description' => trans('packages.subscription.subscription_fee', [
                        'subscription' => $subscriptionPlan->name,
                    ]),
                ];

                $this->owner->forceWithdraw((float) $subscriptionPlan->cost, $meta);
            }

            $this->fill([
                'stripe_price' => $plan,
                'type' => $subscriptionPlan->name,
                'ends_at' => now()->addMonth(),
                'trial_ends_at' => null,
            ])->save();

            return $this;
        }

        // Stripe
        return parent::swap($plan, $options);
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        // Local subscription
        if ($this->provider == 'wallet') {
            return $this->active() || $this->onTrial();
        }

        // Stripe
        return parent::valid();
    }

    /**
     * Wallet subs use ends_at as paid-through date, not Stripe cancellation.
     */
    public function canceled()
    {
        if ($this->provider == 'wallet') {
            return $this->ends_at !== null && $this->ends_at->isPast();
        }

        return ! is_null($this->ends_at);
    }

    /**
     * Wallet billing has no cancel grace period.
     */
    public function onGracePeriod()
    {
        if ($this->provider == 'wallet') {
            return false;
        }

        return parent::onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        if ($this->provider == 'wallet') {
            if ($this->onTrial()) {
                return true;
            }

            // Paid wallet subscriptions always set ends_at; null means trial-only (handled above).
            if ($this->ends_at === null) {
                return false;
            }

            return $this->ends_at->isFuture();
        }

        return parent::active();
    }

    public function getProviderAttribute()
    {
        return $this->stripe_id ? 'stripe' : 'wallet';
    }
}
