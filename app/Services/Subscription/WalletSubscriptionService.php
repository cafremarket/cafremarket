<?php

namespace App\Services\Subscription;

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        $shop = $merchant->merchantShop();

        if (! $shop) {
            throw new \RuntimeException(trans('packages.wallet.owner_invalid'));
        }

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

            return $this->verifySubscription($subscription->fresh(), $planId);
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

            $subscription = $shop->subscriptions()->create($this->subscriptionPayload($plan, $planId, $trialEndsAt, $endsAt));

            try {
                if ($chargeNow && (float) $plan->cost > 0) {
                    $this->chargeShop($shop, (float) $plan->cost, $plan->name);
                }
            } catch (\Throwable $e) {
                $subscription->delete();
                throw $e;
            }

            $this->syncShopBilling($shop, $planId, $trialEndsAt);

            return $this->verifySubscription($subscription->fresh(), $planId);
        });
    }

    protected function subscriptionPayload(SubscriptionPlan $plan, string $planId, $trialEndsAt, $endsAt): array
    {
        $payload = [
            'type' => $plan->name,
            'stripe_price' => $planId,
            'quantity' => 1,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $endsAt,
            'stripe_id' => null,
            'stripe_status' => null,
        ];

        if (Schema::hasColumn('subscriptions', 'name')) {
            $payload['name'] = $plan->name;
        }

        return $payload;
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

    protected function verifySubscription(?Subscription $subscription, string $planId): Subscription
    {
        if (! $subscription || ! $subscription->valid()) {
            Log::error('Wallet subscription activation verification failed', [
                'subscription_id' => $subscription?->id,
                'shop_id' => $subscription?->shop_id,
                'plan_id' => $planId,
                'stripe_price' => $subscription?->stripe_price,
                'ends_at' => $subscription?->ends_at,
                'trial_ends_at' => $subscription?->trial_ends_at,
                'stripe_id' => $subscription?->stripe_id,
            ]);

            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        if ($subscription->stripe_price !== $planId) {
            Log::error('Wallet subscription plan mismatch after activation', [
                'subscription_id' => $subscription->id,
                'expected_plan' => $planId,
                'actual_plan' => $subscription->stripe_price,
            ]);

            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        return $subscription;
    }
}
