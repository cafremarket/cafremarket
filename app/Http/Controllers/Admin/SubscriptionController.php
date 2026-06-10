<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Statistics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\UpdateTrialPeriodRequest;
use App\Jobs\SubscribeShopToNewPlan;
use App\Models\Shop;
use App\Models\SubscriptionPlan;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\Subscription\SubscriptionMobilePaymentService;
use App\Services\Subscription\WalletSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Display the subscription features.
     *
     * @return \Illuminate\View\View
     */
    public function features(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription_plan._show', compact('subscriptionPlan'));
    }

    /**
     * Subscribe Or Swap to the given subscription
     *
     * @param  string  $plan
     * @param  int  $merchant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function subscribe(Request $request, $plan, $merchant = null)
    {
        if (config('app.demo') == true && $request->user()->merchantId() <= config('system.demo.shops', 1)) {
            return redirect()->route('admin.account.billing')
                ->with('warning', trans('messages.demo_restriction'));
        }

        $merchant = $merchant ? User::findOrFail($merchant) : Auth::user();
        $paymentMethod = (string) $request->input('payment_method', 'wallet');

        if (requires_stripe_card_for_subscription() && ! $merchant->hasBillingToken()) {
            return redirect()->route('admin.account.billing')
                ->with('error', trans('messages.no_card_added'));
        }

        try {
            $subscription = SubscriptionPlan::findOrFail($plan);
            $currentPlan = $merchant->getCurrentPlan();

            if ($currentPlan && ! $this->validateSubscriptionSwap($subscription)) {
                return redirect()->route('admin.account.billing')->with(
                    'error',
                    trans('messages.using_more_resource', ['plan' => $subscription->name])
                );
            }

            if (
                $paymentMethod === 'wallet'
                && SystemConfig::isBillingThroughWallet()
                && subscription_charges_immediately($merchant, $subscription)
                && (float) (optional($merchant->merchantShop())->balance ?? 0) < (float) $subscription->cost
            ) {
                return redirect()->route('admin.account.billing')
                    ->with('error', trans('packages.wallet.insufficient_funds'));
            }

            if (
                in_array($paymentMethod, ['mpesa', 'emola'], true)
                && subscription_charges_immediately($merchant, $subscription)
            ) {
                $pending = app(SubscriptionMobilePaymentService::class)
                    ->initiate($merchant, $subscription, $paymentMethod, $request);

                if ($pending && ! empty($pending['ref'])) {
                    $path = $paymentMethod === 'emola'
                        ? 'wallet/deposit/emola/complete'
                        : 'wallet/deposit/mpesa/complete';

                    return redirect()->to(url($path.'?ref='.urlencode($pending['ref'])));
                }

                return redirect()->route('admin.account.billing')
                    ->with('error', trans('messages.subscription_payment_failed'));
            }

            if ($currentPlan && $currentPlan->stripe_price === $plan) {
                return redirect()->route('admin.account.billing')
                    ->with('success', trans('messages.subscribed'));
            }

            if (SystemConfig::isBillingThroughWallet()) {
                app(WalletSubscriptionService::class)->activate($merchant, $plan);
                $merchant->unsetRelation('shop');
                $merchant->unsetRelation('owns');
            } elseif ($currentPlan) {
                $currentPlan->swap($plan);

                if ($merchant->shop->current_billing_plan !== $plan) {
                    $merchant->shop->forceFill(['current_billing_plan' => $plan])->save();
                }

                $merchant->shop->unsetRelation('subscriptions');
                $merchant->shop->unsetRelation('currentSubscription');
            } else {
                SubscribeShopToNewPlan::dispatchSync($merchant, $plan);
                $merchant->shop->unsetRelation('subscriptions');
                $merchant->shop->unsetRelation('currentSubscription');
            }

        } catch (\Throwable $e) {
            Log::error('Subscription Failed: '.$e->getMessage(), [
                'exception' => $e,
                'merchant_id' => $merchant->id ?? null,
                'shop_id' => optional($merchant->merchantShop())->id,
                'plan' => $plan,
            ]);

            $message = $e instanceof \Incevio\Package\Wallet\Exceptions\InsufficientFunds
                ? trans('packages.wallet.insufficient_funds')
                : ($e->getMessage() ?: trans('messages.subscription_error'));

            return redirect()->route('admin.account.billing')
                ->with('error', $message);
        }

        return redirect()->route('admin.account.billing')
            ->with('success', trans('messages.subscribed'));
    }

    /**
     * Update the shop's card info
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCardInfo(Request $request)
    {
        if (config('app.demo') == true && $request->user()->merchantId() <= config('system.demo.shops', 1)) {
            return redirect()->route('admin.account.billing')
                ->with('warning', trans('messages.demo_restriction'));
        }

        // Create Stripe customer if not exist
        if (! $request->user()->hasBillingToken()) {
            $request->user()->shop->createAsStripeCustomer([
                'email' => $request->user()->email,
            ]);
        }

        if ($request->has('payment')) {
            $request->user()->shop->updateDefaultPaymentMethod($request->input('payment'));

            $request->user()->shop->forceFill(['card_holder_name' => $request->input('name')])->save();

            return redirect()->route('admin.account.billing')
                ->with('success', trans('messages.card_updated'));
        }

        return redirect()->route('admin.account.billing')
            ->with('error', trans('messages.trouble_validating_card'))->withInput();
    }

    /**
     * Resume subscription
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resumeSubscription(Request $request)
    {
        if (
            config('app.demo') == true &&
            $request->user()->merchantId() <= config('system.demo.shops', 1)
        ) {
            return redirect()->route('admin.account.billing')
                ->with('warning', trans('messages.demo_restriction'));
        }

        try {
            $request->user()->getCurrentPlan()->resume();
        } catch (\Stripe\Error\Card $e) {
            $response = $e->getJsonBody();

            return redirect()->route('admin.account.billing')
                ->with('error', $response['error']['message']);
        }

        return redirect()->route('admin.account.billing')
            ->with('success', trans('messages.subscription_resumed'));
    }

    /**
     * Cancel subscription
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelSubscription(Request $request)
    {
        if (config('app.demo') == true && $request->user()->merchantId() <= config('system.demo.shops', 1)) {
            return redirect()->route('admin.account.billing')
                ->with('warning', trans('messages.demo_restriction'));
        }

        $isWallet = false;

        try {
            $merchant = $request->user();
            $plan = $merchant->getCurrentPlan();

            if ($plan) {
                $isWallet = $plan->provider === 'wallet';

                $plan->cancel();

                if ($isWallet) {
                    $shop = $merchant->merchantShop();

                    if ($shop) {
                        $shop->forceFill(['current_billing_plan' => null])->saveQuietly();
                        $shop->unsetRelation('subscriptions');
                        $shop->unsetRelation('currentSubscription');
                    }
                }

                $merchant->unsetRelation('shop');
                $merchant->unsetRelation('owns');
            } else {
                throw new \Exception(trans('responses.subscription_404'));
            }
        } catch (\Stripe\Error\Card $e) {
            $response = $e->getJsonBody();

            return redirect()->route('admin.account.billing')
                ->with(['error' => $response['error']['message']]);
        } catch (\Exception $e) {
            return redirect()->route('admin.account.billing')
                ->with(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.account.billing')
            ->with('success', $isWallet
                ? trans('messages.subscription_removed')
                : trans('messages.subscription_cancelled'));
    }

    /**
     * Update subscription trial period
     *
     *
     * @return \Illuminate\View\View
     */
    public function editTrial(Request $request, Shop $shop)
    {
        return view('admin.shop._edit_trial', compact('shop'));
    }

    /**
     * Update subscription trial period
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTrial(UpdateTrialPeriodRequest $request, Shop $shop)
    {
        $new_end_time = Carbon::createFromFormat('Y-m-d h:i a', $request->trial_ends_at);

        try {
            $currentPlan = $shop->owner->getCurrentPlan();

            if ($currentPlan) {
                // Update the local plan
                $currentPlan->update(['trial_ends_at' => $new_end_time->getTimestamp()]);

                // Now update the plan on stripe
                if ($currentPlan->stripe_id || config('system.subscription.billing') == 'stripe') {
                    $currentPlan->extendTrial($new_end_time);
                }
            }

            if ($shop->onGenericTrial() || $shop->hasExpiredPlan()) {
                $shop->forceFill([
                    'trial_ends_at' => $new_end_time->getTimestamp(),
                    'hide_trial_notice' => $request->hide_trial_notice,
                ])->save();
            }
        } catch (\Exception $e) {
            Log::error('Subscription Trial Period Update Failed: '.$e->getMessage());

            return back()->with('error', trans('messages.subscription_update_failed'));
        }

        return back()->with('success', trans('messages.subscription_updated'));
    }

    /**
     * Validate new plan with the current plan
     *
     * @return bool
     */
    private function validateSubscriptionSwap(SubscriptionPlan $plan)
    {
        $resources = [
            'users' => Statistics::shop_user_count(),
            'inventories' => Statistics::shop_inventories_count(),
        ];

        return $resources['users'] <= $plan->team_size && $resources['inventories'] <= $plan->inventory_limit;
    }

    public function invoice(Request $request, $invoiceId)
    {
        return $request->user()->shop
            ->downloadInvoice($invoiceId, [
                'vendor' => get_platform_title(),
                'product' => trans('app.subscription_fee'),
            ]);
    }
}
