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
        $this->authorizeMerchantBilling($merchant);

        if (config('app.demo') == true && $request->user()->merchantId() <= config('system.demo.shops', 1)) {
            return redirect()->to(mp_route('admin.account.billing'))
                ->with('warning', trans('messages.demo_restriction'));
        }

        $merchant = $merchant ? User::findOrFail($merchant) : Auth::user();
        $paymentMethod = (string) $request->input('payment_method', 'wallet');

        try {
            $subscription = SubscriptionPlan::findOrFail($plan);
            $currentPlan = $merchant->getCurrentPlan();

            if ($currentPlan && ! $this->validateSubscriptionSwap($subscription)) {
                return redirect()->to(mp_route('admin.account.billing'))->with(
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
                return redirect()->to(mp_route('admin.account.billing'))
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

                return redirect()->to(mp_route('admin.account.billing'))
                    ->with('error', trans('messages.subscription_payment_failed'));
            }

            if ($currentPlan && $currentPlan->billing_plan === $plan) {
                return redirect()->to(mp_route('admin.account.billing'))
                    ->with('success', trans('messages.subscribed'));
            }

            app(WalletSubscriptionService::class)->activate($merchant, $plan);
            $merchant->unsetRelation('shop');
            $merchant->unsetRelation('owns');

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

            return redirect()->to(mp_route('admin.account.billing'))
                ->with('error', $message);
        }

        return redirect()->to(mp_route('admin.account.billing'))
            ->with('success', trans('messages.subscribed'));
    }

    /**
     * Update the shop's card info
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCardInfo(Request $request)
    {
        $this->authorizeMerchantBilling();

        return redirect()->to(mp_route('admin.account.billing'))
            ->with('error', trans('messages.billing_setup_unavailable'));
    }

    /**
     * Resume subscription
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resumeSubscription(Request $request)
    {
        $this->authorizeMerchantBilling();

        if (
            config('app.demo') == true &&
            $request->user()->merchantId() <= config('system.demo.shops', 1)
        ) {
            return redirect()->to(mp_route('admin.account.billing'))
                ->with('warning', trans('messages.demo_restriction'));
        }

        try {
            $request->user()->getCurrentPlan()?->resume();
        } catch (\Throwable $e) {
            return redirect()->to(mp_route('admin.account.billing'))
                ->with('error', $e->getMessage() ?: trans('messages.subscription_error'));
        }

        return redirect()->to(mp_route('admin.account.billing'))
            ->with('success', trans('messages.subscription_resumed'));
    }

    /**
     * Cancel subscription
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelSubscription(Request $request)
    {
        $this->authorizeMerchantBilling();

        if (config('app.demo') == true && $request->user()->merchantId() <= config('system.demo.shops', 1)) {
            return redirect()->to(mp_route('admin.account.billing'))
                ->with('warning', trans('messages.demo_restriction'));
        }

        try {
            $merchant = $request->user();
            $plan = $merchant->getCurrentPlan();

            if ($plan) {
                $plan->cancel();

                $shop = $merchant->merchantShop();

                if ($shop) {
                    $shop->forceFill(['current_billing_plan' => null])->saveQuietly();
                    $shop->unsetRelation('subscriptions');
                    $shop->unsetRelation('currentSubscription');
                }

                $merchant->unsetRelation('shop');
                $merchant->unsetRelation('owns');
            } else {
                throw new \Exception(trans('responses.subscription_404'));
            }
        } catch (\Exception $e) {
            return redirect()->to(mp_route('admin.account.billing'))
                ->with(['error' => $e->getMessage()]);
        }

        return redirect()->to(mp_route('admin.account.billing'))
            ->with('success', trans('messages.subscription_removed'));
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
                $currentPlan->extendTrial($new_end_time);
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
        $this->authorizeMerchantBilling();

        $shop = $request->user()->shop;
        $transaction = $shop->transactions()->whereKey($invoiceId)->firstOrFail();

        return $transaction->invoice('download');
    }

    /**
     * Billing is for shop owners, or platform admins acting for a merchant.
     */
    private function authorizeMerchantBilling($merchantId = null): void
    {
        $user = Auth::user();

        if ($user->isFromPlatform()) {
            return;
        }

        if ($merchantId && (int) $merchantId !== (int) $user->id) {
            abort_unless($user->isFromPlatform(), 403);
        }

        abort_unless($user->isMerchant(), 403);
    }
}
