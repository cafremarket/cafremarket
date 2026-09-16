<?php

namespace App\Services\Subscription;

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
            app(WalletSubscriptionService::class)->activate($merchant, $planId);

            $merchant->unsetRelation('shop');
            $merchant->load('shop');

            return $merchant->isSubscribed();
        } catch (\Exception $e) {
            Log::error('Subscription auto-complete after deposit failed: '.$e->getMessage(), [
                'shop_id' => $shop->id,
                'plan_id' => $planId,
            ]);

            return false;
        }
    }
}
