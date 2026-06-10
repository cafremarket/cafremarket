<?php

namespace App\Services\Subscription;

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WalletSubscriptionService
{
    /**
     * Activate or change a wallet-billed subscription for a merchant shop.
     */
    public function activate(User $merchant, string $planId): Subscription
    {
        if (! SystemConfig::isBillingThroughWallet()) {
            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        $shop = $merchant->shop;
        $plan = SubscriptionPlan::findOrFail($planId);
        $existing = $merchant->getCurrentPlan();

        if ($existing && $existing->stripe_price === $planId && $existing->valid()) {
            return $existing;
        }

        if ($existing && $existing->valid()) {
            return $this->swapPlan($shop, $merchant, $existing, $plan, $planId);
        }

        return $this->createPlan($shop, $merchant, $plan, $planId);
    }

    protected function swapPlan(Shop $shop, User $merchant, Subscription $subscription, SubscriptionPlan $plan, string $planId): Subscription
    {
        return DB::transaction(function () use ($shop, $merchant, $subscription, $plan, $planId) {
            if (
                $subscription->stripe_price !== $planId
                && subscription_charges_immediately($merchant, $plan)
                && (float) $plan->cost > 0
            ) {
                $this->chargeShop($shop, (float) $plan->cost, $plan->name);
            }

            $subscription->fill([
                'stripe_price' => $planId,
                'type' => $plan->name,
                'ends_at' => Carbon::now()->addMonth(),
                'trial_ends_at' => null,
                'stripe_id' => null,
                'stripe_status' => null,
            ])->save();

            $this->syncShopBilling($shop, $planId, null);

            return $this->assertActivated($shop, $planId);
        });
    }

    protected function createPlan(Shop $shop, User $merchant, SubscriptionPlan $plan, string $planId): Subscription
    {
        return DB::transaction(function () use ($shop, $merchant, $plan, $planId) {
            $this->closeOpenWalletSubscriptions($shop);

            $chargeNow = subscription_charges_immediately($merchant, $plan);
            $trialEndsAt = null;
            $endsAt = Carbon::now()->addMonth();

            if (! $chargeNow) {
                if ($shop->onGenericTrial() && $shop->trial_ends_at && $shop->trial_ends_at->isFuture()) {
                    $trialEndsAt = $shop->trial_ends_at;
                    $endsAt = null;
                } elseif ($trialDays = (int) config('system_settings.trial_days')) {
                    $trialEndsAt = Carbon::now()->addDays($trialDays);
                    $endsAt = null;
                }
            }

            $subscription = $shop->subscriptions()->create([
                'type' => $plan->name,
                'stripe_price' => $planId,
                'quantity' => 1,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => $endsAt,
                'stripe_id' => null,
                'stripe_status' => null,
            ]);

            try {
                if ($chargeNow && (float) $plan->cost > 0) {
                    $this->chargeShop($shop, (float) $plan->cost, $plan->name);
                }
            } catch (\Throwable $e) {
                $subscription->delete();
                throw $e;
            }

            $this->syncShopBilling($shop, $planId, $trialEndsAt);

            return $this->assertActivated($shop, $planId);
        });
    }

    protected function chargeShop(Shop $shop, float $amount, string $planName): void
    {
        $meta = [
            'type' => trans('app.subscription_fee'),
            'description' => trans('packages.subscription.subscription_fee', [
                'subscription' => $planName,
            ]),
        ];

        $shop->forceWithdraw($amount, $meta);
    }

    protected function syncShopBilling(Shop $shop, string $planId, $trialEndsAt): void
    {
        $updates = [
            'trial_ends_at' => $trialEndsAt,
        ];

        if ($shop->current_billing_plan !== $planId) {
            $updates['current_billing_plan'] = $planId;
            $shop->forceFill($updates)->save();
        } else {
            $shop->forceFill($updates)->saveQuietly();
        }

        $shop->unsetRelation('subscriptions');
        $shop->unsetRelation('currentSubscription');
    }

    protected function closeOpenWalletSubscriptions(Shop $shop): void
    {
        $shop->subscriptions()
            ->whereNull('stripe_id')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->update(['ends_at' => now()]);
    }

    protected function assertActivated(Shop $shop, string $planId): Subscription
    {
        $subscription = $shop->activeSubscription();

        if (! $subscription || ! $subscription->valid()) {
            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        if ($subscription->stripe_price !== $planId) {
            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        return $subscription;
    }
}
