<?php

namespace App\Common;

use App\Models\LocalInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Incevio\Package\Subscription\SubscriptionBuilder;

trait Billable
{
    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
            ->orderByDesc('id')
            ->get()
            ->first(function (Subscription $subscription) {
                return $subscription->valid();
            });
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->orderBy('created_at', 'desc');
    }

    public function localInvoices()
    {
        return $this->hasMany(LocalInvoice::class)->orderBy('id', 'desc');
    }

    public function subscriptionFeeTransactions()
    {
        if (! method_exists($this, 'transactions')) {
            return collect();
        }

        try {
            return $this->transactions()
                ->where('type', 'withdraw')
                ->where(function ($query) {
                    $query->where('confirmed', 1)->orWhere('confirmed', true);
                })
                ->get()
                ->filter(function ($transaction) {
                    $meta = is_array($transaction->meta ?? null) ? $transaction->meta : [];

                    if (($meta['purpose'] ?? null) === 'subscription') {
                        return true;
                    }

                    $haystack = strtolower(trim(($meta['type'] ?? '').' '.($meta['description'] ?? '')));

                    return str_contains($haystack, 'subscription')
                        || str_contains($haystack, 'subscri')
                        || str_contains($haystack, 'assinatura');
                })
                ->values();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public function newSubscription(SubscriptionPlan $subscriptionPlan)
    {
        $subscription = new SubscriptionBuilder($this, $subscriptionPlan->name, $subscriptionPlan->plan_id);
        $subscription->setSubscriptionFee($subscriptionPlan->cost);

        return $subscription;
    }

    public function hasActiveSubscription()
    {
        return (bool) $this->activeSubscription();
    }

    /**
     * Shop-level trial (trial_ends_at on shops table) without requiring a subscription row.
     */
    public function onGenericTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * True when the shop is on a generic trial or its active/current subscription is on trial.
     */
    public function onTrial(): bool
    {
        if ($this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->activeSubscription() ?? $this->currentSubscription;

        return $subscription && $subscription->onTrial();
    }
}
