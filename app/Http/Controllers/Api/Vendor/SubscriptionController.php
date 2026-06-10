<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Helpers\Statistics;
use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Controllers\Controller;
use App\Jobs\SubscribeShopToNewPlan;
use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use App\Services\Subscription\SubscriptionMobilePaymentService;
use App\Services\Subscription\WalletSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    use ResolvesVendorShop;

    public function index(Request $request)
    {
        if (! is_subscription_enabled()) {
            return response()->json([
                'data' => [
                    'enabled' => false,
                    'message' => trans('messages.subscription_disabled'),
                ],
            ]);
        }

        $user = $request->user();
        $shop = $this->shop();
        $subscription = $user->getCurrentPlan();
        $currentPlanId = $shop->current_billing_plan;

        $plans = DB::table('subscription_plans')
            ->whereNull('deleted_at')
            ->orderBy('order', 'asc')
            ->get([
                'plan_id',
                'name',
                'cost',
                'featured',
                'team_size',
                'inventory_limit',
                'transaction_fee',
                'transaction_fee_type',
                'marketplace_commission',
                'marketplace_commission_type',
            ])
            ->map(function ($plan) use ($currentPlanId, $subscription) {
                return [
                    'plan_id' => $plan->plan_id,
                    'name' => $plan->name,
                    'cost' => (float) $plan->cost,
                    'cost_formatted' => get_formated_currency(
                        $plan->cost,
                        2,
                        config('system_settings.currency.id')
                    ).trans('app.per_month'),
                    'featured' => (bool) $plan->featured,
                    'team_size' => (int) $plan->team_size,
                    'inventory_limit' => (int) $plan->inventory_limit,
                    'transaction_fee' => format_subscription_plan_fee(
                        (float) $plan->transaction_fee,
                        $plan->transaction_fee_type ?? 'flat'
                    ),
                    'marketplace_commission' => format_subscription_plan_fee(
                        (float) $plan->marketplace_commission,
                        $plan->marketplace_commission_type ?? 'percent'
                    ),
                    'is_current' => $currentPlanId === $plan->plan_id
                        || ($subscription && $subscription->stripe_price === $plan->plan_id),
                ];
            })
            ->values();

        $billingMethod = config('system.subscription.billing', 'stripe');
        $walletBilling = SystemConfig::isBillingThroughWallet();

        $status = 'none';
        $trialEndsAt = null;
        $endsAt = null;
        $onGracePeriod = false;
        $canCancel = false;
        $canResume = false;

        if ($subscription) {
            if ($user->isOnTrial()) {
                $status = 'trial';
                $trialEndsAt = optional($subscription->trial_ends_at)->toIso8601String();
            } elseif ($user->isOnGracePeriod()) {
                $status = 'grace';
                $endsAt = optional($subscription->ends_at)->toIso8601String();
                $onGracePeriod = true;
                $canResume = true;
            } elseif ($subscription->valid()) {
                $status = 'active';
                $endsAt = optional($subscription->ends_at)->toIso8601String();
                $canCancel = $subscription->provider === 'stripe';
            } else {
                $status = 'expired';
            }
        } elseif ($user->isOnGenericTrial()) {
            $status = 'generic_trial';
            $trialEndsAt = optional($shop->trial_ends_at)->toIso8601String();
        } elseif ($user->hasExpiredPlan()) {
            $status = 'expired';
        }

        $notice = null;
        if ($status === 'generic_trial' && $shop->trial_ends_at) {
            $days = now()->diffInDays($shop->trial_ends_at, false);
            $notice = trans('messages.generic_trial_ends_at', ['ends' => max(0, $days)]);
        } elseif ($status === 'trial' && $subscription?->trial_ends_at) {
            $days = now()->diffInDays($subscription->trial_ends_at, false);
            $notice = trans('messages.trial_ends_at', ['ends' => max(0, $days)]);
        } elseif ($status === 'grace' && $subscription?->ends_at) {
            $days = now()->diffInDays($subscription->ends_at, false);
            $notice = trans('messages.resume_subscription', ['ends' => max(0, $days)]);
        } elseif ($status === 'expired') {
            $notice = trans('messages.trial_expired');
        } elseif ($status === 'none') {
            $notice = trans('messages.choose_subscription');
        }

        $walletBalance = 0;
        try {
            $walletBalance = (float) ($shop->balance ?? 0);
        } catch (\Throwable $e) {
            $walletBalance = 0;
        }

        return response()->json([
            'data' => [
                'enabled' => true,
                'billing_method' => $billingMethod,
                'wallet_billing' => $walletBilling,
                'requires_stripe_card' => requires_stripe_card_for_subscription(),
                'has_billing_info' => $user->hasBillingInfo()
                    || ($shop->stripe_id && $shop->pm_last_four),
                'wallet_balance' => $walletBalance,
                'wallet_balance_formatted' => get_formated_currency(
                    $walletBalance,
                    2,
                    config('system_settings.currency.id')
                ),
                'payment_methods' => get_subscription_payment_methods(),
                'trial_days' => (int) config('system_settings.trial_days', 0),
                'current_plan' => $currentPlanId ? [
                    'plan_id' => $currentPlanId,
                    'name' => optional(
                        SubscriptionPlan::find($currentPlanId)
                    )->name,
                    'status' => $status,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => $endsAt,
                    'on_grace_period' => $onGracePeriod,
                    'can_cancel' => $canCancel && $status === 'active',
                    'can_resume' => $canResume,
                ] : [
                    'plan_id' => null,
                    'name' => null,
                    'status' => $status,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => $endsAt,
                    'on_grace_period' => $onGracePeriod,
                    'can_cancel' => false,
                    'can_resume' => $canResume,
                ],
                'plans' => $plans,
                'notice' => $notice,
            ],
        ]);
    }

    public function subscribe(Request $request, string $plan, SubscriptionMobilePaymentService $mobilePayment)
    {
        if (! is_subscription_enabled()) {
            return response()->json(['message' => trans('messages.subscription_disabled')], 403);
        }

        $merchant = Auth::user();
        $paymentMethod = (string) $request->input('payment_method', 'wallet');

        if (! in_array($paymentMethod, ['wallet', 'mpesa', 'emola'], true)) {
            return response()->json([
                'message' => trans('messages.subscription_invalid_payment_method'),
            ], 422);
        }

        if (requires_stripe_card_for_subscription() && ! $merchant->hasBillingToken()) {
            return response()->json([
                'message' => trans('messages.no_card_added'),
                'billing_required' => true,
            ], 402);
        }

        try {
            $subscriptionPlan = SubscriptionPlan::findOrFail($plan);
            $currentPlan = $merchant->getCurrentPlan();

            if ($currentPlan) {
                if (! $this->validateSubscriptionSwap($subscriptionPlan)) {
                    return response()->json([
                        'message' => trans('messages.using_more_resource', [
                            'plan' => $subscriptionPlan->name,
                        ]),
                    ], 422);
                }
            }

            if (
                in_array($paymentMethod, ['mpesa', 'emola'], true)
                && subscription_charges_immediately($merchant, $subscriptionPlan)
            ) {
                $pending = $mobilePayment->initiate(
                    $merchant,
                    $subscriptionPlan,
                    $paymentMethod,
                    $request
                );

                if ($pending) {
                    return response()->json($pending, 200);
                }

                return response()->json([
                    'message' => trans('messages.subscription_payment_failed'),
                ], 400);
            }

            if ($currentPlan && $currentPlan->stripe_price === $plan) {
                return response()->json([
                    'message' => trans('messages.subscribed'),
                ]);
            }

            if (SystemConfig::isBillingThroughWallet()) {
                app(WalletSubscriptionService::class)->activate($merchant, $plan);
                $merchant->unsetRelation('shop');
                $merchant->unsetRelation('owns');
            } elseif ($currentPlan) {
                $currentPlan->swap($plan);

                if ($merchant->shop->current_billing_plan !== $plan) {
                    $merchant->shop->forceFill([
                        'current_billing_plan' => $plan,
                    ])->save();
                }

                $merchant->shop->unsetRelation('subscriptions');
                $merchant->shop->unsetRelation('currentSubscription');
            } else {
                SubscribeShopToNewPlan::dispatchSync($merchant, $plan);
                $merchant->shop->unsetRelation('subscriptions');
                $merchant->shop->unsetRelation('currentSubscription');
            }

        } catch (\Throwable $e) {
            Log::error('Vendor API subscription failed: '.$e->getMessage(), [
                'exception' => $e,
                'merchant_id' => $merchant->id ?? null,
                'shop_id' => optional($merchant->merchantShop())->id,
                'plan' => $plan,
            ]);

            $message = $e instanceof \Incevio\Package\Wallet\Exceptions\InsufficientFunds
                ? trans('packages.wallet.insufficient_funds')
                : ($e->getMessage() ?: trans('messages.subscription_error'));

            return response()->json([
                'message' => $message,
            ], 400);
        }

        return response()->json([
            'message' => trans('messages.subscribed'),
        ]);
    }

    public function paymentStatus(Request $request)
    {
        $method = (string) $request->query('method', 'mpesa');
        $ref = (string) $request->query('ref', '');

        if ($ref === '') {
            return response()->json(['paid' => false, 'subscribed' => false]);
        }

        if ($method === 'emola') {
            return app(\App\Http\Controllers\Api\Vendor\WalletDepositController::class)
                ->emolaDepositStatus($request);
        }

        return app(\App\Http\Controllers\Api\Vendor\WalletDepositController::class)
            ->mpesaDepositStatus($request);
    }

    public function cancel(Request $request)
    {
        if (! is_subscription_enabled()) {
            return response()->json(['message' => trans('messages.subscription_disabled')], 403);
        }

        try {
            $plan = $request->user()->getCurrentPlan();

            if (! $plan) {
                return response()->json([
                    'message' => trans('responses.subscription_404'),
                ], 404);
            }

            $plan->cancel();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => trans('messages.subscription_cancelled'),
        ]);
    }

    public function resume(Request $request)
    {
        if (! is_subscription_enabled()) {
            return response()->json(['message' => trans('messages.subscription_disabled')], 403);
        }

        try {
            $plan = $request->user()->getCurrentPlan();

            if (! $plan) {
                return response()->json([
                    'message' => trans('responses.subscription_404'),
                ], 404);
            }

            $plan->resume();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => trans('messages.subscription_resumed'),
        ]);
    }

    private function validateSubscriptionSwap(SubscriptionPlan $plan): bool
    {
        $resources = [
            'users' => Statistics::shop_user_count(),
            'inventories' => Statistics::shop_inventories_count(),
        ];

        return $resources['users'] <= $plan->team_size
            && $resources['inventories'] <= $plan->inventory_limit;
    }
}
