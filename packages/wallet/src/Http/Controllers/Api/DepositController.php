<?php

namespace Incevio\Package\Wallet\Http\Controllers\Api;

use App\Contracts\PaymentServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Http\Requests\DepositRequest;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Deposit;

class DepositController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse|RedirectResponse
     */
    public function deposit(DepositRequest $request, PaymentServiceContract $paymentService)
    {
        try {
            // When the order has been paid on the app end
            if ($request->input('payment_status') == 'paid' && $request->has('payment_meta')) {
                $response = $paymentService->verifyPaidPayment();
            } else {
                $response = $paymentService->setReceiver('platform')
                    ->setAmount($request->amount)
                    ->setDescription(trans('packages.wallet.deposit_description', [
                        'marketplace' => get_platform_title(),
                    ]))
                    ->setConfig()
                    ->charge();
            }
        } catch (\Exception $e) {
            Log::error('Payment failed:: ');
            Log::info($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        // M-Pesa wallet deposit: charge() returns redirect with ref in URL – return pending + ref for app polling (same as order flow)
        if ($response instanceof RedirectResponse) {
            $url = $response->getTargetUrl();
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query ?? '', $params);
            $ref = $params['ref'] ?? null;
            if ($ref) {
                return response()->json([
                    'pending' => true,
                    'ref' => $ref,
                    'message' => trans('mpesa::lang.payment_confirmation'),
                ], 200);
            }
        }

        // Payment succeed
        if ($response->status == PaymentService::STATUS_PAID) {
            $meta = [
                'type' => Transaction::TYPE_DEPOSIT,
                'description' => trans('packages.wallet.deposit_description', ['marketplace' => get_platform_title(), 'payment' => $request->payment_method]),
            ];

            $wallet = Auth::guard('api')->user();

            $trans = $wallet->deposit($request->amount, $meta, true);

            SendNotificationJob::dispatch($trans, Deposit::class);

            return response()->json([
                'message' => trans('packages.wallet.payment_success'),
            ], 200);
        }

        return response()->json([
            'message' => trans('packages.wallet.payment_failed'),
        ], 400);
    }

    /**
     * M-Pesa wallet deposit: JSON status for polling (same as web wallet/deposit/mpesa/status).
     */
    public function mpesaDepositStatus(Request $request)
    {
        return app(\Incevio\Package\Wallet\Http\Controllers\DepositController::class)
            ->mpesaDepositStatus($request);
    }

    /**
     * Geting active payment methods for customer
     *
     * @return \Illuminate\Http\Response
     */
    public function getPaymentMethods()
    {
        $paymentMethods = PaymentMethod::find(get_from_option_table('wallet_payment_methods', []));

        return PaymentMethodResource::collection($paymentMethods);
    }
}
