<?php

namespace App\Services\Emola;

final class EmolaResponse
{
    /** Movitel: USSD push dispatched to handset (async). */
    public const CODE_PUSH_SENT = '22';

    /** Movitel: operation accepted / completed at business layer. */
    public const CODE_SUCCESS = '0';

    public function __construct(
        public readonly string $gatewayError,
        public readonly ?string $gatewayDescription,
        public readonly ?string $gwtransid,
        public readonly ?string $originalXml,
        public readonly ?array $originalData,
    ) {
    }

    /**
     * Gateway transport layer OK (BCCS gateway error field = 0).
     */
    public function ok(): bool
    {
        return $this->gatewayError === '0';
    }

    public function businessErrorCode(): ?string
    {
        if (! is_array($this->originalData)) {
            return null;
        }

        $code = $this->originalData['errorCode'] ?? $this->originalData['error'] ?? null;

        return ($code !== null && $code !== '') ? (string) $code : null;
    }

    public function businessMessage(): ?string
    {
        if (! is_array($this->originalData)) {
            return $this->gatewayDescription;
        }

        return $this->originalData['message']
            ?? $this->originalData['description']
            ?? $this->gatewayDescription;
    }

    /**
     * USSD push was actually accepted by Movitel (not just gateway HTTP/SOAP OK).
     */
    public function isUssdPushAccepted(): bool
    {
        if (config('emola.fake')) {
            return false;
        }

        if ($this->gatewayError !== '0' && $this->gatewayError !== '') {
            return false;
        }

        $code = $this->businessErrorCode();
        if ($code === null) {
            return false;
        }

        return in_array($code, [self::CODE_SUCCESS, self::CODE_PUSH_SENT], true);
    }

    public function failureMessage(): string
    {
        if (config('emola.fake')) {
            return 'eMola test mode is enabled (EMOLA_FAKE=true). Disable it on production to send real USSD requests.';
        }

        if ($this->gatewayError === 'SOAP_FAULT') {
            return $this->gatewayDescription ?: 'eMola SOAP connection failed.';
        }

        if ($this->gatewayError !== '0') {
            return $this->gatewayDescription
                ?: ('eMola gateway error: '.$this->gatewayError);
        }

        $code = $this->businessErrorCode();
        $message = $this->businessMessage();

        if ($code !== null) {
            return $message
                ? ($message.' (code: '.$code.')')
                : ('eMola rejected the request (code: '.$code.')');
        }

        return 'eMola did not confirm the USSD push. Check gateway logs or try again.';
    }
}
