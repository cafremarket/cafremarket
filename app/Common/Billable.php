<?php

namespace App\Common;

use App\Models\LocalInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use Laravel\Cashier\Billable as CashierBillable;
use Laravel\Cashier\SubscriptionBuilder;

trait Billable
{
    use CashierBillable;

    /**
     * Get all of the subscription for the user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * First valid subscription for this shop (newest first).
     */
    public function activeSubscription()
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->valid()) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * Get all of the subscriptions for the user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all of the local invoices.
     */
    public function localInvoices()
    {
        return $this->hasMany(LocalInvoice::class)->orderBy('id', 'desc');
    }

    /**
     * Begin creating a new subscription.
     *
     * @return \Laravel\Cashier\SubscriptionBuilder
     */
    public function newSubscription(SubscriptionPlan $subscriptionPlan)
    {
        if (SystemConfig::isBillingThroughWallet()) {
            $subscription = new \Incevio\Package\Subscription\SubscriptionBuilder($this, $subscriptionPlan->name, $subscriptionPlan->plan_id);

            $subscription->setSubscriptionFee($subscriptionPlan->cost);

            return $subscription;
        }

        return new SubscriptionBuilder($this, $subscriptionPlan->name, $subscriptionPlan->plan_id);
    }

    /**
     * Check if the billable model has an active subscription.
     *
     * @return bool
     */
    public function hasActiveSubscription()
    {
        return (bool) $this->activeSubscription();
    }
}
