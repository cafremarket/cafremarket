<?php

namespace Incevio\Package\Wallet\Http\Controllers;

use App\Contracts\PaymentServiceContract;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaypalPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Incevio\Package\MPesa\Services\MPesaPaymentService;
use Incevio\Package\Wallet\Http\Requests\DepositRequest;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Deposit;
use Incevio\Package\Wallet\Traits\HasTransaction;

class DepositController extends Controller
{
    use HasTransaction;

    private $wallet;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show_form(Request $request)
    {
        $paymentMethods = PaymentMethod::find(get_from_option_table('wallet_payment_methods', []));

        // When the redirected from payment gateway with error
        if (Session::has('error')) {
            Session::flash('success', Session::get('error'));
        }

        // Check the owner type
        if (Auth::guard('customer')->check()) {
            $tab = 'wallet';
            $customer = Auth::guard('customer')->user();

            // View loaded from theme directory, need to do in better ways
            $content = view('wallet::customer.deposit', compact('paymentMethods', 'customer'))->render();

            return view('theme::dashboard', compact('tab', 'content'));
        }

        if (Auth::user()->isMerchant()) {
            $merchant = Auth::user()->shop;

            return view('wallet::deposit', compact('paymentMethods', 'merchant'));
        }

        return redirect()->back()->with('error', trans('packages.wallet.owner_invalid'));
    }

    /**
     * @return RedirectResponse
     */
    public function deposit(DepositRequest $request, PaymentServiceContract $paymentService)
    {
        try {
            $result = $paymentService
                ->setAmount($request->amount)
                ->setDescription(trans('packages.wallet.deposit_description', [
                    'marketplace' => get_platform_title(),
                    'payment_method' => $request->payment_method,
                ]))
                ->setConfig()
                ->charge();
        } catch (\Exception $e) {
            Log::error('Payment failed:: ');
            Log::error($e);

            return redirect()->route(self::getRouteName())
                ->with('error', $e->getMessage())->withInput();
        }

        // Check if the result is a RedirectResponse of Paypal and some other gateways
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        // Payment succeed
        if ($result->status == PaymentService::STATUS_PAID) {
            return $this->depositCompleted($request->amount, $this->getMetaInfo($request->payment_method));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * @param  PaypalExpressPaymentService  $paymentService
     * @return RedirectResponse
     */
    public function paypalPaymentSuccess(Request $request, PaypalPaymentService $paymentService)
    {
        // Log::info($request);

        if (! $request->has('token') || ! $request->has('PayerID')) {
            return redirect()->route('wallet.deposit.failed');
        }

        try {
            $paymentService = $paymentService->setConfig()->paymentExecution($request);
        } catch (\Exception $e) {
            Log::error('Payment failed:: ');
            Log::info($e->getMessage());

            return redirect()->route(self::getRouteName())
                ->with('error', $e->getMessage())->withInput();
        }

        // Payment succeed
        if ($paymentService->status == PaymentService::STATUS_PAID && $paymentService->response) {
            // $amount = $paymentService->response->transactions[0]->amount->total;
            $amount = $paymentService->response['purchase_units'][0]['payments']['captures'][0]['amount']['value'];

            return $this->depositCompleted($amount, $this->getMetaInfo('Paypal'));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * @return RedirectResponse
     */
    public function paymentFailed(Request $request)
    {
        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * Complete the deposit fund
     *
     * @param  array  $meta
     * @param  bool  $confirm
     * @return RedirectResponse
     */
    private function depositCompleted($amount, $meta = [], $confirm = true)
    {
        try {
            $meta = array_merge([
                'type' => Transaction::TYPE_DEPOSIT,
            ], $meta);

            $wallet = $this->wallet ?? self::getWallet();

            $trans = $wallet->deposit($amount, $meta, $confirm);
        } catch (\Exception $e) {
            return redirect()->route(self::getRouteName())
                ->with('error', trans('packages.wallet.payment_failed'))->withInput();
        }

        SendNotificationJob::dispatch($trans, Deposit::class);

        return redirect()->route(self::getRouteName('wallet'))
            ->with('success', trans('packages.wallet.payment_success'));
    }

    /**
     * Paystack success:
     *
     * @return RedirectResponse
     */
    public function paystackPaymentSuccess(Request $request)
    {
        if ($request->has('trxref') && $request->has('reference')) {
            $paymentService = new \Incevio\Package\Paystack\Services\PaystackPaymentService($request);

            $response = $paymentService->setConfig()->verifyPaidPayment();

            // If the payment failed
            if ($response->status == PaymentService::STATUS_PAID) {
                return $this->depositCompleted($response->amount, $this->getMetaInfo('Paystack'));
            }
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * Sslcommerz success:
     *
     * @return RedirectResponse
     */
    public function sslcommerzPaymentSuccess(Request $request)
    {
        $paymentService = new \Incevio\Package\SslCommerz\Services\SslCommerzPaymentService($request);

        if ($paymentService->verifyPaidPayment()) {
            return $this->depositCompleted($request->amount, $this->getMetaInfo('sslcommerz'));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * flutterwave success:
     *
     * @return RedirectResponse
     */
    public function flutterwavePaymentRedirect(Request $request)
    {
        if ($request->status == 'successful') {
            $flutter = new \Incevio\Package\FlutterWave\Services\FlutterWavePaymentService($request);
            $response = $flutter->verifyPaidPayment();

            if ($response->status == PaymentService::STATUS_PAID) {
                return $this->depositCompleted($response->amount, $this->getMetaInfo('flutterwave'));
            }
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * mollie success:
     *
     * @return RedirectResponse
     */
    public function molliePaymentRedirect(Request $request)
    {
        $mollie = new \Incevio\Package\Mollie\Services\MolliePaymentService($request);
        $mollie->setConfig();
        $response = $mollie->verifyPaidPayment();

        if ($response->status == PaymentService::STATUS_PAID) {
            return $this->depositCompleted($response->amount, $this->getMetaInfo('mollie'));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * bkash success:
     *
     * @return RedirectResponse
     */
    public function bkashPaymentRedirect(Request $request)
    {
        $bkash = new \Incevio\Package\Bkash\Services\BkashPaymentService($request);
        $bkash->setConfig();
        $response = $bkash->verifyPaidPayment();

        if ($response->status == PaymentService::STATUS_PAID) {
            return $this->depositCompleted($response->amount, $this->getMetaInfo('bkash'));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * paytm success:
     *
     * @return RedirectResponse
     */
    public function paytmPaymentRedirect(Request $request)
    {
        $paytm = new \Incevio\Package\Paytm\Services\PaytmPaymentService($request);
        $paytm->setConfig();
        $response = $paytm->verifyPaidPayment();

        if ($response->status == PaymentService::STATUS_PAID) {
            return $this->depositCompleted($response->amount, $this->getMetaInfo('paytm'));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * Receive payfast payment notification callback
     *
     * @return RedirectResponse
     */
    public function payfastPaymentNotify(Request $request)
    {
        $pfData = $request->all();

        $payfast = new \Incevio\Package\Payfast\Services\PayfastPaymentService($request);

        $response = $payfast->verifyPaidPayment($pfData);

        if (
            $response->status == PaymentService::STATUS_PAID &&
            $pfData['custom_str2'] && $pfData['email_address']
        ) {
            // Set the wallet for the deposit
            $this->wallet = self::getWallet($pfData['custom_str2'], $pfData['email_address']);

            $this->depositCompleted($response->amount, $this->getMetaInfo('payfast'));
        }

        return response('success!', 200);
    }

    /**
     * Instamojo success:
     *
     * @return RedirectResponse
     */
    public function instamojoPaymentSuccess(Request $request)
    {
        $instamojo = new \Incevio\Package\Instamojo\Services\InstamojoPaymentService($request);
        $response = $instamojo->setConfig()->verifyPaidPayment();

        if ($response->status == PaymentService::STATUS_PAID) {
            return $this->depositCompleted($response->amount, $this->getMetaInfo('instamojo'));
        }

        return redirect()->route(self::getRouteName())
            ->with('error', trans('packages.wallet.payment_failed'))->withInput();
    }

    /**
     * M-Pesa wallet deposit: show waiting page (same flow as order complete).
     */
    public function mpesaDepositComplete(Request $request)
    {
        $ref = $request->query('ref');
        if (! $ref) {
            return redirect()->route(self::getRouteName())
                ->with('error', trans('packages.wallet.payment_failed'));
        }

        return view('wallet::deposit_mpesa_complete', ['ref' => $ref]);
    }

    /**
     * M-Pesa wallet deposit: JSON status for polling. If payment success, credit wallet and return paid.
     */
    public function mpesaDepositStatus(Request $request)
    {
        $ref = $request->query('ref');
        if (! $ref) {
            return response()->json(['paid' => false]);
        }

        $paidKey = MPesaPaymentService::CACHE_KEY_WALLET_PAID . $ref;
        if (Cache::has($paidKey)) {
            return response()->json(['paid' => true]);
        }

        $cacheKey = MPesaPaymentService::CACHE_KEY_WALLET_DEPOSIT . $ref;
        $data = Cache::get($cacheKey);
        if (! $data || ! isset($data['holder_type'], $data['holder_id'], $data['amount'])) {
            return response()->json(['paid' => false]);
        }

        $statusCheckKey = 'mpesa_wallet_status_check_' . $ref;
        $forceCheck = $request->query('force') === '1' || $request->query('force') === 'true';
        if (! $forceCheck && Cache::has($statusCheckKey)) {
            return response()->json(['paid' => false]);
        }

        if (config('mpesa.query_enabled', true)) {
            try {
                $mpesa = new MPesaPaymentService($request);
                $response = $mpesa->verifyPayment($ref);
                if ($response === null) {
                    if (! $forceCheck) {
                        Cache::put($statusCheckKey, 1, now()->addSeconds(10));
                    }
                    return response()->json(['paid' => false]);
                }
                $json = json_decode($response);

                if ($json) {
                    $success = isset($json->output_ResponseCode)
                        ? (($json->output_ResponseCode === 'INS-0') || ($json->output_ResponseCode === '0'))
                        : ((int) ($json->ResultCode ?? 1) === 0);

                    if ($success) {
                        if (Cache::has($paidKey)) {
                            return response()->json(['paid' => true]);
                        }
                        Cache::put($paidKey, 1, now()->addHours(24));
                        $holder = $data['holder_type']::find($data['holder_id']);
                        if ($holder && method_exists($holder, 'deposit')) {
                            $meta = [
                                'type' => Transaction::TYPE_DEPOSIT,
                                'description' => trans('packages.wallet.deposit_description', [
                                    'marketplace' => get_platform_title(),
                                    'payment_method' => 'M-Pesa',
                                ]),
                            ];
                            $trans = $holder->deposit($data['amount'], $meta, true);
                            SendNotificationJob::dispatch($trans, Deposit::class);
                            Cache::forget($cacheKey);

                            return response()->json(['paid' => true]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('M-Pesa wallet status check failed', [
                    'ref' => $ref,
                    'message' => $e->getMessage(),
                    'hint' => 'On production, ensure APP_URL (or MPESA_CALLBACK_BASE_URL) is your public URL so M-Pesa callback can reach the server. If query API returns 403, ask Vodacom to whitelist your server IP.',
                ]);
            }
        }

        // Another request or callback may have credited the wallet (e.g. first poll got 200, second got 403)
        if (Cache::has($paidKey)) {
            return response()->json(['paid' => true]);
        }

        if (! $forceCheck) {
            Cache::put($statusCheckKey, 1, now()->addSeconds(10));
        }

        return response()->json(['paid' => false]);
    }

    /**
     * return formated meta info for the transaction
     *
     * @return string
     */
    private function getMetaInfo($payment)
    {
        return [
            'description' => trans('packages.wallet.deposit_description', [
                'marketplace' => get_platform_title(),
                'payment_method' => $payment,
            ]),
        ];
    }
}
