<?php

namespace Incevio\Package\Subscription;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionBuilder
{
    public $plan;

    protected $owner;

    protected $type;

    protected $subscriptionFee = 0;

    protected $skipTrial = false;

    protected $trialExpires = null;

    public function __construct($owner, $name, $plan)
    {
        $this->owner = $owner;
        $this->type = $name;
        $this->plan = $plan;
    }

    public function setSubscriptionFee($price)
    {
        $this->subscriptionFee = $price;

        return $this;
    }

    public function trialDays($days)
    {
        $this->trialExpires = Carbon::now()->addDays((int) $days);

        return $this;
    }

    public function skipTrial()
    {
        $this->skipTrial = true;
        $this->trialExpires = null;

        return $this;
    }

    public function create($paymentMethod = null, array $customerOptions = [], array $subscriptionOptions = [])
    {
        $trialEndsAt = $this->skipTrial ? null : $this->trialExpires;

        try {
            $payload = [
                'billing_plan' => $this->plan,
                'quantity' => 1,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => $trialEndsAt ? null : Carbon::now()->addMonth(),
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'type')) {
                $payload['type'] = $this->type;
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'name')) {
                $payload['name'] = $this->type;
            }

            $subscription = $this->owner->subscriptions()->create($payload);

            $trialActive = $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture();

            if (! $trialActive && $this->subscriptionFee > 0) {
                $this->owner->forceWithdraw(
                    $this->subscriptionFee,
                    subscription_charge_meta((string) $this->type)
                );
            }
        } catch (\Throwable $e) {
            Log::error($e);

            throw new \RuntimeException($e->getMessage() ?: trans('messages.subscription_error'));
        }

        return $subscription;
    }
}
