<?php

namespace App\Jobs;

use App\Models\SystemConfig;
use App\Models\User;
use App\Services\Subscription\WalletSubscriptionService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

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
    }

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
