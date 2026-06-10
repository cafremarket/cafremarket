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
        $this->assertWalletBillingEnabled();

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

    protected function assertWalletBillingEnabled(): void
    {
        $billing = (string) config('system.subscription.billing', 'stripe');

        if ($billing !== 'wallet') {
            Log::error('Wallet subscription blocked: billing mode is not wallet', [
                'billing' => $billing,
                'env' => app()->environment(),
            ]);

            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        if (! is_incevio_package_loaded(['wallet', 'subscription'])) {
            Log::error('Wallet subscription blocked: wallet or subscription package not loaded');

            throw new \RuntimeException(
                trans('messages.dependent_package_failed', ['dependency' => 'wallet,subscription'])
            );
        }
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

            $subscription->fill(array_merge(
                $this->planColumnPayload($plan, $planId),
                [
                    'ends_at' => Carbon::now()->addMonth(),
                    'trial_ends_at' => null,
                    'stripe_id' => null,
                    'stripe_status' => null,
                ]
            ))->save();

            $this->syncShopBilling($shop, $planId, null);

            return $this->verifySubscription($subscription->fresh(), $planId, $shop->id);
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
                $trialEndsAt = $this->resolveTrialEndsAt($shop);

                if ($trialEndsAt) {
                    $endsAt = null;
                } else {
                    // No active trial to apply — treat as paid activation.
                    $chargeNow = (float) $plan->cost > 0;
                    $endsAt = Carbon::now()->addMonth();
                }
            }

            $subscription = $shop->subscriptions()->create(array_merge(
                $this->planColumnPayload($plan, $planId),
                [
                    'stripe_price' => $planId,
                    'quantity' => 1,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => $endsAt,
                    'stripe_id' => null,
                    'stripe_status' => null,
                ]
            ));

            try {
                if ($chargeNow && (float) $plan->cost > 0) {
                    $this->chargeShop($shop, (float) $plan->cost, $plan->name);
                }
            } catch (\Throwable $e) {
                $subscription->delete();
                throw $e;
            }

            $this->syncShopBilling($shop, $planId, $trialEndsAt);

            return $this->verifySubscription($subscription->fresh(), $planId, $shop->id);
        });
    }

    /**
     * Support both legacy (name) and current (type) subscription table schemas.
     */
    protected function planColumnPayload(SubscriptionPlan $plan, string $planId): array
    {
        $payload = [];

        if (Schema::hasColumn('subscriptions', 'type')) {
            $payload['type'] = $plan->name;
        }

        if (Schema::hasColumn('subscriptions', 'name')) {
            $payload['name'] = $plan->name;
        }

        if (Schema::hasColumn('subscriptions', 'stripe_price')) {
            $payload['stripe_price'] = $planId;
        }

        return $payload;
    }

    /**
     * Trial end for a new subscription row (never reuse an expired shop trial date).
     */
    protected function resolveTrialEndsAt(Shop $shop): ?Carbon
    {
        if ($shop->onGenericTrial() && $shop->trial_ends_at && $shop->trial_ends_at->isFuture()) {
            return $shop->trial_ends_at->copy();
        }

        $trialDays = (int) config('system_settings.trial_days');

        if ($trialDays > 0) {
            return Carbon::now()->addDays($trialDays);
        }

        return null;
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

    protected function verifySubscription(?Subscription $subscription, string $planId, int $shopId): Subscription
    {
        if (! $subscription) {
            $this->logVerificationFailure(null, $planId, $shopId, 'subscription_row_missing');

            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        if ($subscription->stripe_price !== $planId) {
            $this->logVerificationFailure($subscription, $planId, $shopId, 'plan_mismatch');

            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        if (! $subscription->valid()) {
            $planCost = (float) (SubscriptionPlan::find($planId)?->cost ?? 0);
            $missingPaidPeriod = $subscription->ends_at === null
                && ($subscription->trial_ends_at === null || $subscription->trial_ends_at->isPast());

            // Paid row missing ends_at (or stuck on expired trial) — repair once before failing.
            if ($missingPaidPeriod && $planCost > 0) {
                $subscription->forceFill([
                    'ends_at' => Carbon::now()->addMonth(),
                    'trial_ends_at' => null,
                ])->save();
                $subscription = $subscription->fresh();
            }
        }

        if (! $subscription || ! $subscription->valid()) {
            $this->logVerificationFailure($subscription, $planId, $shopId, 'not_valid');

            throw new \RuntimeException(trans('messages.subscription_error'));
        }

        return $subscription;
    }

    protected function logVerificationFailure(?Subscription $subscription, string $planId, int $shopId, string $reason): void
    {
        Log::error('Wallet subscription activation verification failed', [
            'reason' => $reason,
            'shop_id' => $shopId,
            'plan_id' => $planId,
            'subscription_id' => $subscription?->id,
            'stripe_price' => $subscription?->stripe_price,
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
            'stripe_id' => $subscription?->stripe_id,
            'billing' => config('system.subscription.billing'),
            'environment' => app()->environment(),
        ]);
    }
}
