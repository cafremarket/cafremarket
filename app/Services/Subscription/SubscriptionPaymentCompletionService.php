<?php

namespace App\Services\Subscription;

use App\Jobs\SubscribeShopToNewPlan;
use App\Models\Shop;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentCompletionService
{
    /**
     * After a mobile-money wallet top-up that was started for subscription, activate the plan.
     */
    public function completeAfterDeposit(Shop $shop, string $planId): bool
    {
        if ($planId === '' || ! SubscriptionPlan::find($planId)) {
            return false;
        }

        $merchant = $shop->owner;

        if (! $merchant instanceof User) {
            return false;
        }

        try {
            $currentPlan = $merchant->getCurrentPlan();

            if ($currentPlan && $currentPlan->stripe_price === $planId) {
                return true;
            }

            if ($currentPlan) {
                $subscriptionPlan = SubscriptionPlan::findOrFail($planId);
                $currentPlan->swap($planId)->update(['type' => $subscriptionPlan->name]);
                $shop->forceFill(['current_billing_plan' => $planId])->save();
            } else {
                SubscribeShopToNewPlan::dispatchSync($merchant, $planId);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Subscription auto-complete after deposit failed: '.$e->getMessage(), [
                'shop_id' => $shop->id,
                'plan_id' => $planId,
            ]);

            return false;
        }
    }
}
