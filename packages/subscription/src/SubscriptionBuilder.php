<?php

namespace Incevio\Package\Subscription;

use Carbon\Carbon;
// use DateTimeInterface;
use Laravel\Cashier\SubscriptionBuilder as CashierSubscriptionBuilder;

class SubscriptionBuilder extends CashierSubscriptionBuilder
{
    public $plan;

    /**
     * The subscription fee
     *
     * @var float|int
     */
    protected $subscriptionFee = 0;

    /**
     * @param  mixed  $owner
     * @param  string  $name
     * @param  string  $plan
     */
    public function __construct($owner, $name, $plan)
    {
        parent::__construct($owner, $name, $plan);

        $this->plan = $plan;
    }

    public function setSubscriptionFee($price)
    {
        $this->subscriptionFee = $price;
    }

    /**
     * Create a new Local subscription.
     *
     * @param  \Stripe\PaymentMethod|string|null  $paymentMethod
     * @param  array  $options
     * @return \Laravel\Cashier\Subscription
     */
    public function create($paymentMethod = null, array $customerOptions = [], array $subscriptionOptions = [])
    {
        $trialEndsAt = $this->skipTrial ? null : $this->trialExpires;

        try {
            $subscription = $this->owner->subscriptions()
                ->create([
                    'type' => $this->type,
                    'stripe_price' => $this->plan,
                    'quantity' => 1,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => $trialEndsAt ? null : Carbon::now()->addMonth(),
                ]);

            $trialActive = $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture();

            if (! $trialActive && $this->subscriptionFee > 0) {
                $meta = [
                    'type' => trans('app.subscription_fee'),
                    'description' => trans('packages.subscription.subscription_fee', ['subscription' => $this->type]),
                ];

                $this->owner->forceWithdraw($this->subscriptionFee, $meta);
            }
        } catch (\Throwable $e) {
            \Log::error($e);

            throw new \Exception($e->getMessage() ?: trans('messages.subscription_error'));
        }

        return $subscription;
    }
}
