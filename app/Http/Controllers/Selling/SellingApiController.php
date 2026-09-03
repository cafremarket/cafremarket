<?php

namespace App\Http\Controllers\Selling;

use App\Http\Controllers\Controller;
use App\Models\FaqTopic;
use App\Models\SubscriptionPlan;

class SellingApiController extends Controller
{
    /**
     * Public selling-page configuration.
     */
    public function config()
    {
        $minPlanCost = null;

        if (is_subscription_enabled()) {
            $minPlanCost = SubscriptionPlan::orderBy('cost', 'asc')->value('cost');
        }

        return response()->json([
            'data' => [
                'platform' => get_platform_title(),
                'brand_label' => get_platform_brand_label(),
                'subscription_enabled' => is_subscription_enabled(),
                'trial_days' => (int) config('system_settings.trial_days', 0),
                'currency_symbol' => config('system_settings.currency.symbol'),
                'currency_code' => config('system_settings.currency.code'),
                'min_plan_cost' => $minPlanCost !== null ? (float) $minPlanCost : null,
                'min_plan_cost_formatted' => $minPlanCost !== null
                    ? get_formated_currency($minPlanCost)
                    : null,
                'support_email' => config('system_settings.support_email'),
                'show_vendor_terms' => (bool) config('system_settings.show_vendor_terms_and_conditions'),
                'recaptcha_enabled' => (bool) config('services.recaptcha.key'),
            ],
        ]);
    }

    /**
     * Merchant FAQ topics for the selling page.
     */
    public function faqs()
    {
        $topics = FaqTopic::merchant()
            ->with(['faqs' => function ($query) {
                $query->orderBy('id');
            }])
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'faqs' => $topic->faqs->map(function ($faq) {
                        return [
                            'id' => $faq->id,
                            'question' => $faq->question,
                            'answer' => $faq->answer,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Subscription plans for pricing section and registration.
     */
    public function subscriptionPlans()
    {
        if (! is_subscription_enabled()) {
            return response()->json([
                'enabled' => false,
                'data' => [],
            ]);
        }

        $plans = SubscriptionPlan::orderBy('order', 'asc')->get();

        return response()->json([
            'enabled' => true,
            'data' => $plans->map(function (SubscriptionPlan $plan) {
                $features = [
                    __('theme.plan.team_size', ['size' => $plan->team_size]),
                    __('theme.plan.inventory_limit', ['limit' => $plan->inventory_limit]),
                ];

                if ($plan->transaction_fee > 0 && $plan->marketplace_commission > 0) {
                    $features[] = __('theme.plan.transaction_and_commission', [
                        'commission' => $plan->formattedMarketplaceCommission(),
                        'fee' => $plan->formattedTransactionFee(),
                    ]);
                } else {
                    if ($plan->transaction_fee > 0) {
                        $features[] = __('theme.plan.transaction_fee', ['fee' => $plan->formattedTransactionFee()]);
                    } else {
                        $features[] = __('theme.plan.no_transaction_fee');
                    }

                    if ($plan->marketplace_commission > 0) {
                        $features[] = __('theme.plan.marketplace_commission', ['commission' => $plan->formattedMarketplaceCommission()]);
                    } else {
                        $features[] = __('theme.plan.no_marketplace_commission');
                    }
                }

                return [
                    'plan_id' => $plan->plan_id,
                    'name' => $plan->name,
                    'cost' => (float) $plan->cost,
                    'cost_formatted' => $plan->cost == 0
                        ? __('theme.free')
                        : get_formated_currency($plan->cost),
                    'featured' => (bool) $plan->featured,
                    'best_for' => $plan->best_for,
                    'team_size' => (int) $plan->team_size,
                    'inventory_limit' => (int) $plan->inventory_limit,
                    'features' => $features,
                    'register_url' => route('selling.register', $plan->plan_id),
                ];
            })->values(),
        ]);
    }
}
