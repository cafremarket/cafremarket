<?php

namespace App\Jobs;

use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\Subscription\WalletSubscriptionService;
use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscribeShopToNewPlan
{
    use Dispatchable;

    protected $merchant;

    protected $plan;

    protected $payment_method;

    public function __construct(User $merchant, $plan, $payment_method = null)
    {
        $this->merchant = $merchant;
        $this->plan = $plan;
        $this->payment_method = $payment_method;
    }

    public function handle()
    {
        if (SystemConfig::isBillingThroughWallet()) {
            app(WalletSubscriptionService::class)->activate($this->merchant, $this->plan);

            return;
        }

        $shop = $this->merchant->shop;
        $subscriptionPlan = SubscriptionPlan::findOrFail($this->plan);
        $subscription = $shop->newSubscription($subscriptionPlan);

        if ($shop->onGenericTrial()) {
            $trialDays = \Carbon\Carbon::now()->lt($shop->trial_ends_at)
                ? \Carbon\Carbon::now()->diffInDays($shop->trial_ends_at)
                : null;
        } else {
            $trialDays = (bool) config('system_settings.trial_days') ? config('system_settings.trial_days') : null;
        }

        if ($trialDays) {
            $subscription->trialDays($trialDays);
        } else {
            $subscription->skipTrial();
        }

        try {
            $subscription = $subscription->create($this->payment_method, [
                'email' => $this->merchant->email,
            ]);

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
}
