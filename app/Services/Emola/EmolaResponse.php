<?php

namespace App\Services\Emola;

final class EmolaResponse
{
    public function __construct(
        public readonly string $gatewayError,
        public readonly ?string $gatewayDescription,
        public readonly ?string $gwtransid,
        public readonly ?string $originalXml,
        public readonly ?array $originalData,
    ) {
    }

    public function ok(): bool
    {
        return $this->gatewayError === '0';
    }
}

