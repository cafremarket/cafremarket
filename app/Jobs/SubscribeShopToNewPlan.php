<?php

namespace App\Jobs;

use App\Models\Shop;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscribeShopToNewPlan
{
    use Dispatchable;

    protected $merchant;

    protected $plan;

    protected $payment_method;

    /**
     * Create a new job instance.
     *
     * @param  string  $plan
     * @param  str/Null  $payment_method
     * @return void
     */
    public function __construct(User $merchant, $plan, $payment_method = null)
    {
        $this->merchant = $merchant;
        $this->plan = $plan;
        $this->payment_method = $payment_method;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shop = $this->merchant->shop;

        $this->endOpenWalletSubscriptions($shop);

        // Create subscription intance
        $subscriptionPlan = SubscriptionPlan::findOrFail($this->plan);

        $subscription = $shop->newSubscription($subscriptionPlan);

        // Subtract the used trial days with the new subscription
        if ($shop->onGenericTrial()) {
            $trialDays = Carbon::now()->lt($shop->trial_ends_at) ? Carbon::now()->diffInDays($shop->trial_ends_at) : null;
        } else {
            $trialDays = (bool) config('system_settings.trial_days') ? config('system_settings.trial_days') : null;
        }

        // Set trial days
        if ($trialDays) {
            $subscription->trialDays($trialDays);
        } else {
            $subscription->skipTrial();
        }

        // Create subscription
        try {
            $subscription = $subscription->create($this->payment_method, [
                'email' => $this->merchant->email,
            ]);

            $shop->unsetRelation('subscriptions');
            $shop->unsetRelation('currentSubscription');

            $previousPlan = $shop->current_billing_plan;
            $updates = [
                'trial_ends_at' => $subscription->trial_ends_at,
            ];

            if ($previousPlan !== $this->plan) {
                $updates['current_billing_plan'] = $this->plan;
                $shop->forceFill($updates)->save();
            } else {
                $shop->forceFill($updates)->saveQuietly();
            }
        } catch (IncompletePayment $e) {
            return redirect()->route('cashier.payment', [$e->payment->id, 'redirect' => route('home')]);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage() ?: trans('messages.subscription_error'));
        }
    }

    /**
     * Close any open local (wallet) subscriptions before starting a new one.
     */
    protected function endOpenWalletSubscriptions(Shop $shop): void
    {
        $shop->subscriptions()
            ->whereNull('stripe_id')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->update(['ends_at' => now()]);
    }
}
