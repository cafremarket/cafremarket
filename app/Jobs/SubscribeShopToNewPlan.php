<?php

namespace App\Jobs;

use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\Subscription\WalletSubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
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
        if (! $this->plan) {
            return;
        }

        if (config('system.subscription.billing') === 'wallet') {
            if (SystemConfig::isBillingThroughWallet()) {
                try {
                    app(WalletSubscriptionService::class)->activate($this->merchant, $this->plan);

                    return;
                } catch (\Throwable $e) {
                    Log::warning('Wallet subscription activation failed during registration; assigning plan on shop only.', [
                        'merchant_id' => $this->merchant->id,
                        'plan' => $this->plan,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('Wallet billing configured but packages inactive; assigning plan on shop only.', [
                    'merchant_id' => $this->merchant->id,
                    'plan' => $this->plan,
                ]);
            }

            $this->assignPlanOnShopOnly($this->merchant, $this->plan);

            return;
        }

        $shop = $this->merchant->shop;

        if (! $shop) {
            return;
        }

        $subscriptionPlan = SubscriptionPlan::find($this->plan);

        if (! $subscriptionPlan) {
            Log::warning('Subscription plan not found during registration.', ['plan' => $this->plan]);

            return;
        }

        if ($shop->onGenericTrial()) {
            $trialDays = Carbon::now()->lt($shop->trial_ends_at)
                ? Carbon::now()->diffInDays($shop->trial_ends_at)
                : null;
        } else {
            $trialDays = (bool) config('system_settings.trial_days') ? config('system_settings.trial_days') : null;
        }

        try {
            $subscription = $shop->newSubscription($subscriptionPlan);

            if ($trialDays) {
                $subscription->trialDays($trialDays);
            } else {
                $subscription->skipTrial();
            }

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
            Log::warning('Stripe subscription setup failed during registration; assigning plan on shop only.', [
                'merchant_id' => $this->merchant->id,
                'plan' => $this->plan,
                'error' => $e->getMessage(),
            ]);

            $this->assignPlanOnShopOnly($this->merchant, $this->plan);
        }
    }

    /**
     * Persist the selected plan on the shop when full billing integration is unavailable.
     */
    protected function assignPlanOnShopOnly(User $merchant, string $planId): void
    {
        $shop = $merchant->shop;

        if (! $shop) {
            return;
        }

        $updates = [
            'current_billing_plan' => $planId,
        ];

        if ((bool) config('system_settings.trial_days')) {
            $updates['trial_ends_at'] = now()->addDays((int) config('system_settings.trial_days'));
        }

        $shop->forceFill($updates)->save();
    }
}
