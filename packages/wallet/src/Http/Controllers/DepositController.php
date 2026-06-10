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
use App\Exceptions\PaymentFailedException;
use App\Services\Emola\EmolaWalletDepositService;
use Incevio\Package\MPesa\Services\MPesaPaymentService;
use Incevio\Package\Wallet\Http\Requests\DepositRequest;
use App\Models\Shop;
use App\Services\OrderCheckoutFeeService;
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
            $paymentMethod = (string) $request->input('payment_method', '');
            $amountSetter = in_array($paymentMethod, ['mpesa', 'emola'], true)
                ? fn ($p) => $p->setAmountWithPlatformFee($request->amount, $paymentMethod)
                : fn ($p) => $p->setAmount($request->amount);

            $result = $amountSetter($paymentService)
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

        return view('wallet::deposit_mpesa_complete', [
            'ref' => $ref,
            'depositSummary' => $this->pendingDepositSummary(MPesaPaymentService::CACHE_KEY_WALLET_DEPOSIT.$ref),
        ]);
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
                            if (! empty($data['platform_fee'])) {
                                $meta['platform_fee'] = $data['platform_fee'];
                            }
                            if (! empty($data['charge_amount'])) {
                                $meta['charge_amount'] = $data['charge_amount'];
                            }
                            $trans = $holder->deposit($data['amount'], $meta, true);
                            SendNotificationJob::dispatch($trans, Deposit::class);
                            Cache::forget($cacheKey);

                            if (! empty($data['subscription_plan_id']) && $holder instanceof \App\Models\Shop) {
                                app(\App\Services\Subscription\SubscriptionPaymentCompletionService::class)
                                    ->completeAfterDeposit($holder, (string) $data['subscription_plan_id']);
                            }

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
     * eMola wallet deposit: waiting page (USSD on phone).
     */
    public function emolaDepositComplete(Request $request, EmolaWalletDepositService $emolaWallet)
    {
        $ref = $request->query('ref');
        if (! $ref) {
            return redirect()->route(self::getRouteName())
                ->with('error', trans('packages.wallet.payment_failed'));
        }

        $holder = null;
        if (Auth::guard('customer')->check() || (Auth::guard('web')->check() && Auth::user()->isMerchant())) {
            $holder = self::getWallet();
        }

        $canResend = $holder && $emolaWallet->canResendDeposit($ref, $holder);

        return view('wallet::deposit_emola_complete', [
            'depositSummary' => $this->pendingDepositSummary(EmolaWalletDepositService::CACHE_KEY_WALLET_DEPOSIT.$ref),
            'ref' => $ref,
            'canResend' => $canResend,
        ]);
    }

    /**
     * Resend eMola USSD for a pending wallet deposit.
     */
    public function emolaResendDeposit(Request $request, EmolaWalletDepositService $emolaWallet)
    {
        if (! Auth::guard('customer')->check() && ! (Auth::guard('web')->check() && Auth::user()->isMerchant())) {
            abort(403);
        }

        $request->validate([
            'ref' => 'required|string',
            'emola_number' => ['required', 'string', 'regex:/^(86|87)\d{7}$/'],
        ], [
            'emola_number.required' => trans('theme.emola_number_required'),
            'emola_number.regex' => trans('theme.emola_number_invalid'),
        ]);

        try {
            $result = $emolaWallet->resendDeposit(
                $request->input('ref'),
                $request->input('emola_number'),
                self::getWallet(),
            );
        } catch (PaymentFailedException $e) {
            return redirect()->route('wallet.deposit.emola.complete', ['ref' => $request->input('ref')])
                ->with('error', $e->getMessage());
        }

        return redirect()->route('wallet.deposit.emola.complete', ['ref' => $result['transId']])
            ->with('warning', trans('theme.emola_resend_success'));
    }

    /**
     * eMola wallet deposit: JSON status for polling (callback or Movitel query).
     */
    public function emolaDepositStatus(Request $request, EmolaWalletDepositService $emolaWallet)
    {
        $ref = $request->query('ref');
        if (! $ref) {
            return response()->json(['paid' => false]);
        }

        if (Cache::has(EmolaWalletDepositService::CACHE_KEY_WALLET_PAID.$ref)) {
            return response()->json(['paid' => true]);
        }

        $cacheKey = EmolaWalletDepositService::CACHE_KEY_WALLET_DEPOSIT.$ref;
        if (! Cache::has($cacheKey)) {
            return response()->json(['paid' => false]);
        }

        $statusCheckKey = 'emola_wallet_status_check_'.$ref;
        $forceCheck = $request->query('force') === '1' || $request->query('force') === 'true';

        if (! $forceCheck && Cache::has($statusCheckKey)) {
            return response()->json(['paid' => false]);
        }

        if ($emolaWallet->syncAndCreditDeposit($ref)) {
            return response()->json(['paid' => true]);
        }

        if (Cache::has(EmolaWalletDepositService::CACHE_KEY_WALLET_PAID.$ref)) {
            return response()->json(['paid' => true]);
        }

        if (! $forceCheck) {
            Cache::put($statusCheckKey, 1, now()->addSeconds(10));
        }

        return response()->json(['paid' => false]);
    }

    /**
     * Preview transaction fee (checkout) or gateway fee (wallet top-up) for M-Pesa / eMola.
     */
    public function platformFeePreview(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:mpesa,emola',
            'shop_id' => 'nullable|integer|exists:shops,id',
        ]);

        $method = (string) $request->input('payment_method');
        $amount = $request->input('amount');
        $shopId = $request->input('shop_id');

        if ($shopId) {
            $customer = get_customer_transaction_fee($method, $amount, (int) $shopId);
            $shop = Shop::find((int) $shopId);
            $settlement = OrderCheckoutFeeService::vendorSettlementPreview(
                (float) $amount,
                $shop
            );
            $marketplaceCommission = (float) ($settlement['marketplace_commission'] ?? 0);
            $vendorNet = (float) ($settlement['net'] ?? 0);

            return response()->json([
                'base' => $customer['base'],
                'fee' => $customer['fee'],
                'subscription_fee' => $customer['subscription_fee'],
                'total' => $customer['total'],
                'marketplace_commission' => $marketplaceCommission,
                'vendor_net' => $vendorNet,
                'enabled' => true,
                'formatted' => [
                    'base' => get_formated_currency($customer['base']),
                    'fee' => get_formated_currency($customer['fee']),
                    'subscription_fee' => get_formated_currency($customer['subscription_fee']),
                    'total' => get_formated_currency($customer['total']),
                    'marketplace_commission' => get_formated_currency($marketplaceCommission),
                    'vendor_net' => get_formated_currency($vendorNet),
                ],
            ]);
        }

        $breakdown = get_platform_payment_fee($method, $amount);
        $vendorNet = max(0, round((float) $breakdown['base'] - (float) $breakdown['fee'], 2));
        $chargeTotal = (int) round((float) $breakdown['total']);
        $maxCharge = $method === 'emola'
            ? \App\Services\Emola\EmolaSpec::depositChargeMaxMzn()
            : \App\Services\Emola\EmolaSpec::movitelUssdMaxMzn();
        $exceedsLimit = $method === 'emola' && $chargeTotal > $maxCharge;
        $maxBase = $method === 'emola'
            ? \App\Services\Emola\EmolaSpec::maxWalletDepositBaseMzn('emola')
            : null;
        $exceedsMessage = $exceedsLimit
            ? trans('theme.emola_deposit_charge_exceeds_partner', [
                'amount' => number_format($chargeTotal, 0, '.', ','),
                'max' => number_format($maxCharge, 0, '.', ','),
                'max_base' => number_format((int) $maxBase, 0, '.', ','),
            ])
            : null;

        return response()->json([
            'base' => $breakdown['base'],
            'fee' => $breakdown['fee'],
            'total' => $breakdown['total'],
            'charge_total' => $chargeTotal,
            'max_charge_mzn' => $maxCharge,
            'max_base_mzn' => $maxBase,
            'exceeds_emola_limit' => $exceedsLimit,
            'exceeds_message' => $exceedsMessage,
            'vendor_net' => $vendorNet,
            'enabled' => $breakdown['enabled'],
            'formatted' => [
                'base' => get_formated_currency($breakdown['base']),
                'fee' => get_formated_currency($breakdown['fee']),
                'total' => get_formated_currency($breakdown['total']),
                'vendor_net' => get_formated_currency($vendorNet),
                'max_charge_mzn' => get_formated_currency($maxCharge),
                'max_base_mzn' => $maxBase !== null ? get_formated_currency($maxBase) : null,
            ],
        ]);
    }

    /**
     * @return array{base: float, fee: float, total: float}|null
     */
    private function pendingDepositSummary(string $cacheKey): ?array
    {
        $data = Cache::get($cacheKey);

        if (! is_array($data) || ! isset($data['amount'])) {
            return null;
        }

        $base = (float) $data['amount'];
        $fee = (float) ($data['platform_fee'] ?? 0);
        $total = (float) ($data['charge_amount'] ?? ($base + $fee));

        return [
            'base' => $base,
            'fee' => $fee,
            'total' => $total,
        ];
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
