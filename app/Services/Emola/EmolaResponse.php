<?php

namespace App\Services\Emola;

use Illuminate\Support\Facades\Log;

final class EmolaResponse
{
    /** Spec §C — push accepted, async USSD sent. */
    public const CODE_PUSH_SENT = '22';

    /** Spec §C — business operation successful. */
    public const CODE_SUCCESS = '0';

    /** Spec §B.2 — origin transaction successful. */
    public const ORG_SUCCESS = '01';

    public function __construct(
        public readonly string $gatewayError,
        public readonly ?string $gatewayDescription,
        public readonly ?string $gwtransid,
        public readonly ?string $originalXml,
        public readonly ?array $originalData,
    ) {
    }

    /** Spec §A.2 — Result.error = 0. */
    public function isGatewaySuccess(): bool
    {
        return $this->gatewayError === '0';
    }

    /** @deprecated Use isGatewaySuccess() */
    public function ok(): bool
    {
        return $this->isGatewaySuccess();
    }

    public function businessErrorCode(): ?string
    {
        if (! is_array($this->originalData)) {
            return null;
        }

        $code = $this->originalData['errorCode'] ?? null;

        return ($code !== null && $code !== '') ? (string) $code : null;
    }

    public function businessMessage(): ?string
    {
        if (! is_array($this->originalData)) {
            return $this->gatewayDescription;
        }

        return $this->originalData['message'] ?? $this->gatewayDescription;
    }

    public function requestId(): ?string
    {
        if (! is_array($this->originalData)) {
            return null;
        }

        return $this->originalData['reqeustId'] ?? null;
    }

    public function balance(): ?string
    {
        if (! is_array($this->originalData)) {
            return null;
        }

        return $this->originalData['balance'] ?? null;
    }

    /**
     * pushUssdMessage accepted (spec §B.1 + §C): gateway OK and errorCode 0 or 22.
     */
    public function isUssdPushAccepted(): bool
    {
        if (config('emola.fake')) {
            return false;
        }

        if (! $this->isGatewaySuccess()) {
            return false;
        }

        $code = $this->businessErrorCode();

        return in_array($code, [self::CODE_SUCCESS, self::CODE_PUSH_SENT], true);
    }

    /**
     * Callback / sync payment success (spec §B.4).
     */
    public function isPaymentSuccess(): bool
    {
        return EmolaSpec::isPaymentSuccessCode($this->businessErrorCode());
    }

    /**
     * Payment completed — Movitel business errorCode 0 only (not 22, not orgResponseCode alone).
     */
    public function isTransactionPaid(): bool
    {
        if (! $this->isGatewaySuccess()) {
            return false;
        }

        return $this->isPaymentSuccess();
    }

    /** Customer-facing message — always simple; technical detail goes to the log. */
    public function failureMessage(): string
    {
        Log::warning('eMola payment failed', ['detail' => $this->technicalMessage()]);

        return trans('theme.emola_payment_not_updated');
    }

    /** Raw gateway / business error detail for logs and admins. */
    public function technicalMessage(): string
    {
        if (config('emola.fake')) {
            return 'eMola test mode is enabled (EMOLA_FAKE=true).';
        }

        if (! $this->isGatewaySuccess()) {
            $detail = $this->gatewayDescription ?: EmolaSpec::gatewayErrorMessage($this->gatewayError);

            return trim('Gateway error '.$this->gatewayError.': '.($detail ?: 'no detail'));
        }

        $code = $this->businessErrorCode();
        if ($code !== null) {
            $detail = $this->businessMessage() ?: EmolaSpec::businessErrorMessage($code);
            $amount = EmolaSpec::lastTransAmountMzn();

            return trim('Business error '.$code.': '.($detail ?: 'no detail')
                .($amount !== null ? ' (amount '.$amount.' MZN)' : ''));
        }

        return 'USSD push not confirmed.';
    }
}
