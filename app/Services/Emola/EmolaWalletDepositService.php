<?php

namespace App\Services\Emola;

use App\Exceptions\PaymentFailedException;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Jobs\SendNotificationJob;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Notifications\Deposit;

/**
 * eMola USSD push for wallet top-ups (web + API), confirmed via Movitel callback or status query.
 */
class EmolaWalletDepositService
{
    public const CACHE_KEY_WALLET_DEPOSIT = 'emola_wallet_deposit_';

    public const CACHE_KEY_WALLET_PAID = 'emola_wallet_paid_';

    private const CACHE_KEY_WALLET_REF = 'emola_wallet_ref_';

    public function __construct(private readonly EmolaClient $client)
    {
    }

    /**
     * Initiate USSD push for a wallet deposit.
     *
     * @return array{transId: string, refNo: string, response: EmolaResponse}
     */
    public function pushDeposit(
        object $payee,
        float|int|string $amount,
        string $msisdn,
        ?string $smsContent = null,
        ?int $chargeMzn = null,
    ): array {
        $msisdn = EmolaSpec::normalizeMsisdn($msisdn);
        $baseMzn = EmolaSpec::parseMeticalAmount($amount);

        if ($chargeMzn !== null) {
            EmolaSpec::assertDepositChargeAllowed($chargeMzn);
            $transAmount = EmolaSpec::formatTransAmount($chargeMzn, EmolaSpec::CONTEXT_DEPOSIT);
            $platformFee = max(0.0, (float) ((int) $transAmount - $baseMzn));
        } else {
            $feeBreakdown = \App\Services\PlatformGatewayFeeService::paymentFee('emola', $baseMzn);
            $transAmount = EmolaSpec::formatTransAmount($feeBreakdown['total'], EmolaSpec::CONTEXT_DEPOSIT);
            $platformFee = (float) $feeBreakdown['fee'];
        }

        $amountMzn = $baseMzn;
        $transId = $this->client->generateTransId();
        $refNo = self::refNoForPayee($payee);

        $res = $this->client->pushUssdMessage([
            'msisdn' => $msisdn,
            'transId' => $transId,
            'transAmount' => $transAmount,
            'smsContent' => $smsContent ?: trans('packages.wallet.emola_deposit_sms', [
                'marketplace' => get_platform_title(),
            ]),
            'language' => EmolaSpec::sanitizeLanguage(app()->getLocale() === 'en' ? 'en' : 'pt'),
            'refNo' => $refNo,
        ]);

        Log::info('eMola USSD push for wallet deposit', [
            'holder_type' => $payee::class,
            'holder_id' => $payee->id ?? null,
            'trans_id' => $transId,
            'ref_no' => $refNo,
            'base_mzn' => $baseMzn,
            'trans_amount' => $transAmount,
            'gateway_error' => $res->gatewayError,
            'business_code' => $res->businessErrorCode(),
            'business_message' => $res->businessMessage(),
            'ussd_push_accepted' => $res->isUssdPushAccepted(),
            'movitel_issue' => ! $res->isUssdPushAccepted(),
        ]);

        if ($res->isUssdPushAccepted()) {
            EmolaDailyLimit::recordAcceptedPush($msisdn, (int) $transAmount);
            $this->rememberPendingDeposit(
                $transId,
                $payee,
                (float) $amountMzn,
                $refNo,
                $platformFee,
                (int) $transAmount,
            );
        }

        return [
            'transId' => $transId,
            'refNo' => $refNo,
            'response' => $res,
        ];
    }

    /**
     * Movitel async callback for wallet deposits. Returns true when payload matches a wallet ref (handled).
     *
     * @param  array{reqeustId: string, transId: string, refNo: string, errorCode: string, message: string}  $data
     */
    public function processCallbackPayload(array $data): bool
    {
        if (! self::isWalletRefNo($data['refNo'] ?? '')) {
            return false;
        }

        $deposit = $this->findPendingDeposit($data['transId'], $data['refNo']);

        if (! $deposit) {
            Log::warning('eMola wallet callback: pending deposit not found', $data);

            return true;
        }

        if (EmolaSpec::isPaymentSuccessCode($data['errorCode'])) {
            $this->creditWalletDeposit($data['transId'], $deposit);
        }

        return true;
    }

    /**
     * Poll Movitel and credit wallet when payment is confirmed.
     */
    public function syncAndCreditDeposit(string $transId): bool
    {
        if (Cache::has(self::CACHE_KEY_WALLET_PAID.$transId)) {
            return true;
        }

        $deposit = Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        if (! $deposit || ! isset($deposit['holder_type'], $deposit['holder_id'], $deposit['amount'])) {
            return false;
        }

        $res = $this->client->pushUssdQueryTrans(
            $transId,
            (string) config('emola.trans_types.c2b', 'C2B')
        );

        Log::info('eMola status query for wallet deposit', [
            'trans_id' => $transId,
            'gateway_error' => $res->gatewayError,
            'paid' => $res->isTransactionPaid(),
        ]);

        if ($res->isTransactionPaid()) {
            return $this->creditWalletDeposit($transId, $deposit);
        }

        return false;
    }

    public function creditWalletDeposit(string $transId, ?array $deposit = null): bool
    {
        $paidKey = self::CACHE_KEY_WALLET_PAID.$transId;

        if (Cache::has($paidKey)) {
            return true;
        }

        $deposit ??= Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        if (! $deposit || ! isset($deposit['holder_type'], $deposit['holder_id'], $deposit['amount'])) {
            return false;
        }

        $holder = $deposit['holder_type']::find($deposit['holder_id']);

        if (! $holder || ! method_exists($holder, 'deposit')) {
            return false;
        }

        Cache::put($paidKey, 1, now()->addHours(24));

        $meta = [
            'type' => Transaction::TYPE_DEPOSIT,
            'description' => trans('packages.wallet.deposit_description', [
                'marketplace' => get_platform_title(),
                'payment_method' => 'eMola',
            ]),
        ];
        if (! empty($deposit['platform_fee'])) {
            $meta['platform_fee'] = $deposit['platform_fee'];
        }
        if (! empty($deposit['charge_amount'])) {
            $meta['charge_amount'] = $deposit['charge_amount'];
        }

        $trans = $holder->deposit($deposit['amount'], $meta, true);
        SendNotificationJob::dispatch($trans, Deposit::class);
        Cache::forget(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        return true;
    }

    public static function refNoForPayee(object $payee): string
    {
        if ($payee instanceof Customer) {
            return EmolaSpec::sanitizeRefNo('WLTc'.(string) $payee->id);
        }

        if ($payee instanceof Shop) {
            return EmolaSpec::sanitizeRefNo('WLTm'.(string) $payee->id);
        }

        throw new PaymentFailedException('Unsupported wallet holder for eMola deposit.');
    }

    public static function isWalletRefNo(string $refNo): bool
    {
        return str_starts_with($refNo, 'WLT');
    }

    public function getPendingDeposit(string $transId): ?array
    {
        if (Cache::has(self::CACHE_KEY_WALLET_PAID.$transId)) {
            return null;
        }

        $deposit = Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        return is_array($deposit) ? $deposit : null;
    }

    public function canResendDeposit(string $transId, object $holder): bool
    {
        try {
            $this->assertDepositHolder($transId, $holder);

            return true;
        } catch (PaymentFailedException) {
            return false;
        }
    }

    /**
     * Send a new USSD push for an existing pending wallet deposit (new transId).
     *
     * @return array{transId: string, refNo: string, response: EmolaResponse}
     */
    public function resendDeposit(string $transId, string $msisdn, object $holder): array
    {
        [$deposit, $payee] = $this->assertDepositHolder($transId, $holder);

        $this->forgetPendingDeposit($transId);

        $chargeMzn = isset($deposit['charge_amount'])
            ? (int) round((float) $deposit['charge_amount'])
            : null;

        $result = $this->pushDeposit($payee, $deposit['amount'], $msisdn, null, $chargeMzn);

        if (! $result['response']->isUssdPushAccepted()) {
            throw new PaymentFailedException($result['response']->failureMessage());
        }

        return $result;
    }

    /**
     * @return array{0: array, 1: object}
     */
    private function assertDepositHolder(string $transId, object $holder): array
    {
        if (Cache::has(self::CACHE_KEY_WALLET_PAID.$transId)) {
            throw new PaymentFailedException(trans('packages.wallet.emola_resend_not_allowed'));
        }

        $deposit = Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        if (! $deposit || ! isset($deposit['holder_type'], $deposit['holder_id'], $deposit['amount'])) {
            throw new PaymentFailedException(trans('packages.wallet.emola_resend_not_allowed'));
        }

        $payee = $deposit['holder_type']::find($deposit['holder_id']);

        if (! $payee || (int) $payee->id !== (int) $holder->id || $payee::class !== $holder::class) {
            throw new PaymentFailedException(trans('packages.wallet.emola_resend_not_allowed'));
        }

        return [$deposit, $payee];
    }

    private function forgetPendingDeposit(string $transId): void
    {
        $deposit = Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        if (is_array($deposit) && ! empty($deposit['ref_no'])) {
            Cache::forget(self::CACHE_KEY_WALLET_REF.$deposit['ref_no']);
        }

        Cache::forget(self::CACHE_KEY_WALLET_DEPOSIT.$transId);
        Cache::forget('emola_wallet_status_check_'.$transId);
    }

    private function rememberPendingDeposit(
        string $transId,
        object $payee,
        float $amount,
        string $refNo,
        float $platformFee = 0,
        ?int $chargeMzn = null,
    ): void {
        $ttl = now()->addHours(24);

        Cache::put(self::CACHE_KEY_WALLET_DEPOSIT.$transId, [
            'holder_type' => $payee::class,
            'holder_id' => $payee->id,
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'charge_amount' => $chargeMzn ?? (int) round($amount + $platformFee),
            'ref_no' => $refNo,
        ], $ttl);

        Cache::put(self::CACHE_KEY_WALLET_REF.$refNo, $transId, $ttl);
    }

    private function findPendingDeposit(string $transId, string $refNo): ?array
    {
        $deposit = Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$transId);

        if ($deposit) {
            return $deposit;
        }

        if (self::isWalletRefNo($refNo)) {
            $mappedTransId = Cache::get(self::CACHE_KEY_WALLET_REF.$refNo);

            if ($mappedTransId) {
                return Cache::get(self::CACHE_KEY_WALLET_DEPOSIT.$mappedTransId);
            }
        }

        return null;
    }
}
