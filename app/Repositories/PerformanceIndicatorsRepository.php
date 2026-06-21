<?php

namespace App\Repositories;

use App\Contracts\Repositories\PerformanceIndicatorsRepository as Contract;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PerformanceIndicatorsRepository implements Contract
{
    public function all($take = 60)
    {
        return DB::table('performance_indicators')->orderBy('created_at', 'desc')->take($take)->get();
    }

    public function forDate(Carbon $date)
    {
        return DB::table('performance_indicators')->where('created_at', $date)->first();
    }

    public function totalRevenueForUser($user)
    {
        return DB::table('invoices')->where('user_id', $user->id)->sum('total');
    }

    public function totalVolume()
    {
        return DB::table('invoices')->sum('total');
    }

    /**
     * Get the monthly recurring revenue from active paying subscribers.
     */
    public function monthlyRecurringRevenue()
    {
        $total = 0;

        foreach (SubscriptionPlan::orderBy('order')->get() as $plan) {
            $total += $this->subscribers($plan) * (float) $plan->cost;
        }

        return round($total, 2);
    }

    /**
     * Active paying subscribers on a plan (wallet + Stripe).
     */
    public function subscribers(SubscriptionPlan $plan)
    {
        $now = Carbon::now();

        $fromSubscriptions = $this->activeSubscriptionsForPlan($plan, $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '<=', $now);
            })
            ->count();

        $fromShops = $this->shopsOnPlanWithoutActiveSubscription($plan, $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '<=', $now);
            })
            ->count();

        return $fromSubscriptions + $fromShops;
    }

    /**
     * Vendors currently trialing a plan.
     */
    public function trialing(SubscriptionPlan $plan)
    {
        $now = Carbon::now();

        $subscriptionTrials = $this->subscriptionsForPlan($plan)
            ->where('trial_ends_at', '>', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->count();

        $shopTrials = DB::table('shops')
            ->where('current_billing_plan', $plan->plan_id)
            ->where('trial_ends_at', '>', $now)
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) use ($plan, $now) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.shop_id', 'shops.id')
                    ->where('subscriptions.stripe_price', $plan->plan_id)
                    ->where('subscriptions.trial_ends_at', '>', $now)
                    ->where(function ($inner) use ($now) {
                        $inner->whereNull('subscriptions.ends_at')
                            ->orWhere('subscriptions.ends_at', '>', $now);
                    });
            })
            ->count();

        return $subscriptionTrials + $shopTrials;
    }

    /**
     * Subscriptions that are not cancelled/expired (Stripe or wallet billing).
     */
    protected function activeSubscriptionsForPlan(SubscriptionPlan $plan, Carbon $now): Builder
    {
        return $this->subscriptionsForPlan($plan)
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            });
    }

    protected function subscriptionsForPlan(SubscriptionPlan $plan): Builder
    {
        return DB::table('subscriptions')
            ->where('stripe_price', $plan->plan_id);
    }

    /**
     * Shops assigned to a plan but without a matching active subscription row.
     */
    protected function shopsOnPlanWithoutActiveSubscription(SubscriptionPlan $plan, Carbon $now): Builder
    {
        return DB::table('shops')
            ->where('current_billing_plan', $plan->plan_id)
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) use ($plan, $now) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.shop_id', 'shops.id')
                    ->where('subscriptions.stripe_price', $plan->plan_id)
                    ->where(function ($inner) use ($now) {
                        $inner->whereNull('subscriptions.ends_at')
                            ->orWhere('subscriptions.ends_at', '>', $now);
                    });
            });
    }
}
