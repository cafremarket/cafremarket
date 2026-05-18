<?php

namespace App\Services;

use App\Services\Emola\EmolaClient;
use App\Services\Emola\EmolaResponse;

/**
 * Facade for tinker/docs — delegates to EmolaClient (SOAP gwOperation integration).
 */
class EmolaService
{
    public function __construct(private readonly EmolaClient $client)
    {
    }

    public function pushPayment(
        string $msisdn,
        string $amount,
        string $transId,
        string $refNo,
        string $smsContent = 'Pagamento CafreMarket',
    ): EmolaResponse {
        return $this->client->pushPayment($msisdn, $amount, $transId, $refNo, $smsContent);
    }

    public function queryTransaction(string $transId, string $transType = 'C2B'): EmolaResponse
    {
        return $this->client->queryTransaction($transId, $transType);
    }

    public function checkBalance(): EmolaResponse
    {
        return $this->client->checkBalance();
    }

    public function getBeneficiaryName(string $msisdn): EmolaResponse
    {
        return $this->client->getBeneficiaryName($msisdn);
    }

    public function generateTransId(): string
    {
        return $this->client->generateTransId();
    }
}
