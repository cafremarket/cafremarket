<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Contracts\PaymentServiceContract;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\VendorWalletDepositRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\Shop;
use App\Services\Emola\EmolaWalletDepositService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Http\Controllers\DepositController as WebDepositController;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Deposit;

class WalletDepositController extends Controller
{
    use ResolvesVendorShop;

    public function getPaymentMethods()
    {
        $paymentMethods = PaymentMethod::find(get_from_option_table('wallet_payment_methods', []));

        return PaymentMethodResource::collection($paymentMethods);
    }

    public function platformFeePreview(Request $request)
    {
        return app(WebDepositController::class)->platformFeePreview($request);
    }

    public function deposit(VendorWalletDepositRequest $request, PaymentServiceContract $paymentService)
    {
        $shop = $this->shop();

        try {
            if ($request->input('payment_status') == 'paid' && $request->has('payment_meta')) {
                $paymentService->setPayee($shop, PaymentService::PAYEE_TYPE_SHOP);
                $response = $paymentService->verifyPaidPayment();
            } else {
                $paymentMethod = (string) $request->input('payment_method', '');
                $paymentBuilder = $paymentService
                    ->setPayee($shop, PaymentService::PAYEE_TYPE_SHOP)
                    ->setReceiver('platform');

                if (in_array($paymentMethod, ['mpesa', 'emola'], true)) {
                    $paymentBuilder->setAmountWithPlatformFee($request->amount, $paymentMethod);
                } else {
                    $paymentBuilder->setAmount($request->amount);
                }

                $response = $paymentBuilder
                    ->setDescription(trans('packages.wallet.deposit_description', [
                        'marketplace' => get_platform_title(),
                    ]))
                    ->setConfig()
                    ->charge();
            }
        } catch (\Exception $e) {
            Log::error('Vendor wallet deposit failed');
            Log::info($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        if ($response instanceof RedirectResponse) {
            $url = $response->getTargetUrl();
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query ?? '', $params);
            $ref = $params['ref'] ?? null;

            if ($ref) {
                $message = str_contains($url, 'emola')
                    ? trans('packages.wallet.emola_redirect_when_paid')
                    : trans('mpesa::lang.payment_confirmation');

                $paymentMethod = (string) $request->input('payment_method', '');
                $feeBreakdown = in_array($paymentMethod, ['mpesa', 'emola'], true)
                    ? get_platform_payment_fee($paymentMethod, $request->amount)
                    : ['base' => (float) $request->amount, 'fee' => 0, 'total' => (float) $request->amount, 'enabled' => false];

                return response()->json([
                    'pending' => true,
                    'ref' => $ref,
                    'message' => $message,
                    'amount' => $feeBreakdown['base'],
                    'platform_fee' => $feeBreakdown['fee'],
                    'total_charge' => $feeBreakdown['total'],
                ], 200);
            }
        }

        if ($response->status == PaymentService::STATUS_PAID) {
            $meta = [
                'type' => Transaction::TYPE_DEPOSIT,
                'description' => trans('packages.wallet.deposit_description', [
                    'marketplace' => get_platform_title(),
                    'payment' => $request->payment_method,
                ]),
            ];

            $trans = $shop->deposit($request->amount, $meta, true);

            SendNotificationJob::dispatch($trans, Deposit::class);

            return response()->json([
                'message' => trans('packages.wallet.payment_success'),
            ], 200);
        }

        return response()->json([
            'message' => trans('packages.wallet.payment_failed'),
        ], 400);
    }

    public function mpesaDepositStatus(Request $request)
    {
        return app(WebDepositController::class)->mpesaDepositStatus($request);
    }

    public function emolaDepositStatus(Request $request)
    {
        return app(WebDepositController::class)->emolaDepositStatus(
            $request,
            app(EmolaWalletDepositService::class)
        );
    }

    public function emolaResendDeposit(Request $request, EmolaWalletDepositService $emolaWallet)
    {
        $request->validate([
            'ref' => 'required|string',
            'emola_number' => ['required', 'string', 'regex:/^(86|87)\d{7}$/'],
        ], [
            'emola_number.required' => trans('theme.emola_number_required'),
            'emola_number.regex' => trans('theme.emola_number_invalid'),
        ]);

        $shop = $this->shop();

        try {
            $result = $emolaWallet->resendDeposit(
                $request->input('ref'),
                $request->input('emola_number'),
                $shop,
            );
        } catch (PaymentFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'ref' => $result['transId'],
            'message' => trans('theme.emola_resend_success'),
        ]);
    }
}
