<?php

namespace App\Services\Subscription;

use App\Contracts\PaymentServiceContract;
use App\Models\Shop;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionMobilePaymentService
{
    public function __construct(private readonly PaymentServiceContract $paymentService)
    {
    }

    /**
     * Charge plan fee via M-Pesa or eMola; wallet is credited then subscription activates on confirm.
     *
     * @return array{pending: bool, ref: string, message: string, amount: float, platform_fee: float, total_charge: float}|null
     */
    public function initiate(User $merchant, SubscriptionPlan $plan, string $paymentMethod, Request $request): ?array
    {
        if (! in_array($paymentMethod, ['mpesa', 'emola'], true)) {
            return null;
        }

        $shop = $merchant->shop;

        if (! $shop instanceof Shop) {
            return null;
        }

        $amount = (float) $plan->cost;

        if ($amount <= 0) {
            return null;
        }

        $request->merge([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'subscription_plan_id' => $plan->plan_id,
        ]);

        try {
            $paymentBuilder = $this->paymentService
                ->setPayee($shop, PaymentService::PAYEE_TYPE_SHOP)
                ->setReceiver('platform')
                ->setAmountWithPlatformFee($amount, $paymentMethod)
                ->setDescription(trans('app.subscription_fee').': '.$plan->name)
                ->setConfig();

            $response = $paymentBuilder->charge();
        } catch (\Exception $e) {
            Log::error('Subscription mobile payment failed: '.$e->getMessage());

            throw $e;
        }

        if ($response instanceof RedirectResponse) {
            $url = $response->getTargetUrl();
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query ?? '', $params);
            $ref = $params['ref'] ?? null;

            if ($ref) {
                $feeBreakdown = get_platform_payment_fee($paymentMethod, $amount);

                return [
                    'pending' => true,
                    'ref' => $ref,
                    'message' => str_contains($url, 'emola')
                        ? trans('packages.wallet.emola_redirect_when_paid')
                        : trans('mpesa::lang.payment_confirmation'),
                    'amount' => $feeBreakdown['base'],
                    'platform_fee' => $feeBreakdown['fee'],
                    'total_charge' => $feeBreakdown['total'],
                    'payment_method' => $paymentMethod,
                    'subscription_plan_id' => $plan->plan_id,
                ];
            }
        }

        return null;
    }
}
