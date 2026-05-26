<?php

namespace App\Services\Emola;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

/**
 * Movitel USSD Push API v1.5 — SOAP gwOperation client.
 *
 * @see MOVITEL USSD PUSH API SPECIFICATION v1.5
 */
class EmolaClient
{
    private ?SoapClient $soapClient = null;

    public function __construct(
        private ?string $wsdl = null,
        private ?string $endpoint = null,
        private ?string $username = null,
        private ?string $password = null,
        private ?string $partnerCode = null,
        private ?string $key = null,
        private ?string $language = null,
        private ?int $timeoutSeconds = null,
    ) {
        $this->wsdl = $this->wsdl ?? Config::get('emola.wsdl');
        $this->endpoint = $this->endpoint ?? Config::get('emola.endpoint');
        $this->username = $this->username ?? Config::get('emola.username');
        $this->password = $this->password ?? Config::get('emola.password');
        $this->partnerCode = $this->partnerCode ?? Config::get('emola.partner_code');
        $this->key = $this->key ?? Config::get('emola.key');
        $this->language = $this->language ?? Config::get('emola.language', 'pt');
        $this->timeoutSeconds = $this->timeoutSeconds ?? (int) Config::get('emola.timeout_seconds', 60);
    }

    /** C2B — pushUssdMessage (spec §B.1). */
    public function pushUssdMessage(array $input): EmolaResponse
    {
        return $this->pushPayment(
            (string) Arr::get($input, 'msisdn'),
            (string) Arr::get($input, 'transAmount'),
            (string) Arr::get($input, 'transId'),
            (string) Arr::get($input, 'refNo'),
            (string) Arr::get($input, 'smsContent', 'Pagamento CafreMarket'),
            (string) Arr::get($input, 'language', $this->language),
        );
    }

    public function pushPayment(
        string $msisdn,
        string $amount,
        string $transId,
        string $refNo,
        string $smsContent = 'Pagamento CafreMarket',
        ?string $language = null,
    ): EmolaResponse {
        $transAmount = EmolaSpec::transAmountForSoap($amount);
        EmolaSpec::rememberTransAmountAttempt($transAmount);

        return $this->gwOperation(config('emola.wscode.push', 'pushUssdMessage'), [
            'partnerCode' => $this->partnerCode,
            'msisdn' => EmolaSpec::normalizeMsisdn($msisdn),
            'smsContent' => EmolaSpec::sanitizeSmsContent($smsContent),
            'transAmount' => $transAmount,
            'transId' => EmolaSpec::sanitizeTransId($transId),
            'language' => EmolaSpec::sanitizeLanguage($language),
            'refNo' => EmolaSpec::sanitizeRefNo($refNo),
            'key' => $this->key,
        ]);
    }

    public function pushUssdQueryTrans(string $transId, string $transType = 'C2B'): EmolaResponse
    {
        return $this->queryTransaction($transId, $transType);
    }

    /** pushUssdQueryTrans (spec §B.2). */
    public function queryTransaction(string $transId, string $transType = 'C2B'): EmolaResponse
    {
        return $this->gwOperation(config('emola.wscode.query', 'pushUssdQueryTrans'), [
            'partnerCode' => $this->partnerCode,
            'transId' => EmolaSpec::sanitizeTransId($transId),
            'transType' => $transType,
            'key' => $this->key,
        ]);
    }

    /** pushUssdDisbursementB2C (spec §B.3). */
    public function pushUssdDisbursementB2C(array $input): EmolaResponse
    {
        return $this->gwOperation(config('emola.wscode.b2c', 'pushUssdDisbursementB2C'), [
            'partnerCode' => $this->partnerCode,
            'msisdn' => EmolaSpec::normalizeMsisdn((string) Arr::get($input, 'msisdn')),
            'smsContent' => EmolaSpec::sanitizeSmsContent((string) Arr::get($input, 'smsContent', '')),
            'transAmount' => EmolaSpec::transAmountForSoap(Arr::get($input, 'transAmount')),
            'transId' => EmolaSpec::sanitizeTransId((string) Arr::get($input, 'transId')),
            'key' => $this->key,
        ]);
    }

    public function queryBeneficiaryName(string $transId, string $msisdn): EmolaResponse
    {
        return $this->getBeneficiaryName($msisdn, $transId);
    }

    /** queryBeneficiaryName (spec §B.5). */
    public function getBeneficiaryName(string $msisdn, ?string $transId = null): EmolaResponse
    {
        return $this->gwOperation(config('emola.wscode.beneficiary', 'queryBeneficiaryName'), [
            'transId' => EmolaSpec::sanitizeTransId($transId ?? $this->generateTransId()),
            'partnerCode' => $this->partnerCode,
            'msisdn' => EmolaSpec::normalizeMsisdn($msisdn),
            'key' => $this->key,
        ]);
    }

    public function queryAccountBalance(?string $transId = null): EmolaResponse
    {
        return $this->checkBalance($transId);
    }

    /** queryAccountBalance (spec §B.6). */
    public function checkBalance(?string $transId = null): EmolaResponse
    {
        return $this->gwOperation(config('emola.wscode.balance', 'queryAccountBalance'), [
            'partnerCode' => $this->partnerCode,
            'transId' => EmolaSpec::sanitizeTransId($transId ?? $this->generateTransId()),
            'key' => $this->key,
        ]);
    }

    /** Unique transId — spec §B.1: 15–30 chars. */
    public function generateTransId(): string
    {
        return EmolaSpec::sanitizeTransId('CAFRE'.date('YmdHis').rand(100, 999));
    }

    /**
     * Inner Input XML (spec §A.1): username, password, wscode, param@name/@value, rawData.
     *
     * @param  array<string, string>  $params
     */
    protected function buildInputXml(string $wscode, array $params): string
    {
        $xml = '<username>'.htmlspecialchars($this->username, ENT_XML1).'</username>';
        $xml .= '<password>'.htmlspecialchars($this->password, ENT_XML1).'</password>';
        $xml .= '<wscode>'.htmlspecialchars($wscode, ENT_XML1).'</wscode>';

        foreach ($params as $name => $value) {
            $xml .= '<param name="'.htmlspecialchars((string) $name, ENT_XML1).'" value="'.htmlspecialchars((string) $value, ENT_XML1).'"/>';
        }

        // Spec §A.1 / partner SDK — rawData is mandatory (placeholder when unused).
        $xml .= '<rawData>?</rawData>';

        return $xml;
    }

    /**
     * Full SOAP envelope per spec §A.1 / §B.1.
     */
    protected function buildSoapEnvelope(string $innerInputXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="'.config('emola.soap_namespace').'">'
            .'<soapenv:Header/>'
            .'<soapenv:Body>'
            .'<web:gwOperation>'
            .'<Input>'.$innerInputXml.'</Input>'
            .'</web:gwOperation>'
            .'</soapenv:Body>'
            .'</soapenv:Envelope>';
    }

    /**
     * @param  array<string, string>  $params
     */
    private function gwOperation(string $wscode, array $params): EmolaResponse
    {
        $this->assertConfigured();

        if (Config::get('emola.fake')) {
            Log::warning('eMola gwOperation skipped (EMOLA_FAKE=true)', ['wscode' => $wscode]);

            return new EmolaResponse(
                gatewayError: 'FAKE_MODE',
                gatewayDescription: 'EMOLA_FAKE=true — no SOAP call made',
                gwtransid: null,
                originalXml: null,
                originalData: null,
            );
        }

        $innerXml = $this->buildInputXml($wscode, $params);

        try {
            return $this->dispatchGwOperation($innerXml, $wscode);
        } catch (\Throwable $e) {
            Log::error('eMola gwOperation failed', [
                'wscode' => $wscode,
                'error' => $e->getMessage(),
            ]);

            return new EmolaResponse(
                gatewayError: 'SOAP_FAULT',
                gatewayDescription: $e->getMessage(),
                gwtransid: null,
                originalXml: null,
                originalData: null,
            );
        }
    }

    private function dispatchGwOperation(string $innerXml, string $wscode): EmolaResponse
    {
        // 1) Spec-exact HTTP SOAP (avoids PHP SoapClient "username property" encoding errors).
        try {
            $httpResponse = $this->callGwOperationHttp($innerXml, $wscode);
            if ($httpResponse->gatewayError !== 'SOAP_FAULT' && $httpResponse->gatewayError !== 'HTTP_ERROR') {
                return $httpResponse;
            }
        } catch (\Throwable $e) {
            Log::warning('eMola HTTP SOAP failed', ['wscode' => $wscode, 'error' => $e->getMessage()]);
        }

        // 2) SoapParam with inner XML (documented partner integration style).
        try {
            return $this->callGwOperationSoapParam($innerXml, $wscode);
        } catch (SoapFault $e) {
            Log::warning('eMola SoapParam failed', ['wscode' => $wscode, 'faultstring' => $e->faultstring ?? null]);
            throw $e;
        }
    }

    private function callGwOperationHttp(string $innerXml, string $wscode): EmolaResponse
    {
        $envelope = $this->buildSoapEnvelope($innerXml);

        $response = Http::timeout($this->timeoutSeconds)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=UTF-8',
                'SOAPAction' => '',
            ])
            ->withBody($envelope, 'text/xml')
            ->post($this->endpoint);

        if (! $response->successful()) {
            return new EmolaResponse(
                gatewayError: 'HTTP_ERROR',
                gatewayDescription: 'HTTP '.$response->status().': '.$response->body(),
                gwtransid: null,
                originalXml: null,
                originalData: null,
            );
        }

        Log::info('eMola gwOperation via HTTP', ['wscode' => $wscode]);

        return $this->parseSoapResponseBody($response->body(), $wscode);
    }

    private function callGwOperationSoapParam(string $innerXml, string $wscode): EmolaResponse
    {
        $client = $this->soapClient();
        $result = $client->__soapCall('gwOperation', [new \SoapParam($innerXml, 'Input')]);

        Log::info('eMola gwOperation via SoapParam', ['wscode' => $wscode]);

        return $this->parseGwOperationResult($result, $wscode);
    }

    private function parseSoapResponseBody(string $body, string $wscode): EmolaResponse
    {
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($body);

        if ($sxe === false) {
            return new EmolaResponse(
                gatewayError: 'UNKNOWN_RESPONSE',
                gatewayDescription: 'Invalid SOAP response XML',
                gwtransid: null,
                originalXml: null,
                originalData: null,
            );
        }

        $namespaces = $sxe->getNamespaces(true);
        $soapNs = $namespaces['S'] ?? $namespaces['soapenv'] ?? 'http://schemas.xmlsoap.org/soap/envelope/';
        $webNs = $namespaces['ns2'] ?? config('emola.soap_namespace');

        $bodyNode = $sxe->children($soapNs)->Body ?? null;
        if (! $bodyNode) {
            return new EmolaResponse('UNKNOWN_RESPONSE', 'Missing SOAP Body', null, null, null);
        }

        $gwResponse = $bodyNode->children($webNs)->gwOperationResponse ?? $bodyNode->gwOperationResponse ?? null;
        $result = $gwResponse?->Result ?? $gwResponse?->children()->Result ?? null;

        if ($result === null) {
            return $this->parseGwOperationResult(simplexml_load_string($body), $wscode);
        }

        $gatewayError = trim((string) ($result->error ?? ''));
        $gatewayDescription = trim((string) ($result->description ?? '')) ?: null;
        $gwtransid = trim((string) ($result->gwtransid ?? '')) ?: null;
        $original = (string) ($result->original ?? '');

        $originalData = $this->parseOriginalData($original) ?? $this->parseLooseOriginalData($original);

        return new EmolaResponse(
            gatewayError: $gatewayError,
            gatewayDescription: $gatewayDescription,
            gwtransid: $gwtransid,
            originalXml: $this->extractXmlFromCdata($original),
            originalData: $originalData,
        );
    }

    private function parseGwOperationResult(mixed $result, string $wscode = ''): EmolaResponse
    {
        $res = is_object($result) ? ($result->Result ?? $result) : null;

        if (is_object($res)) {
            $gatewayError = trim((string) ($res->error ?? ''));
            $original = isset($res->original) ? (string) $res->original : '';
            $originalData = $this->parseOriginalData($original) ?? $this->parseLooseOriginalData($original);

            return new EmolaResponse(
                gatewayError: $gatewayError,
                gatewayDescription: isset($res->description) ? trim((string) $res->description) : null,
                gwtransid: isset($res->gwtransid) ? trim((string) $res->gwtransid) : null,
                originalXml: $this->extractXmlFromCdata($original),
                originalData: $originalData,
            );
        }

        Log::warning('eMola unexpected gwOperation response', ['wscode' => $wscode]);

        return new EmolaResponse('UNKNOWN_RESPONSE', 'Unexpected gwOperation response', null, null, null);
    }

    /**
     * @return array<string, string>|null
     */
    private function parseLooseOriginalData(string $payload): ?array
    {
        $data = [];
        $fields = ['errorCode', 'message', 'reqeustId', 'orgResponseCode', 'orgResponseMessage', 'balance', 'gwtransid'];

        foreach ($fields as $field) {
            if (preg_match('/<\s*'.preg_quote($field, '/').'\s*>([^<]*)<\s*\/\s*'.preg_quote($field, '/').'\s*>/i', $payload, $m)) {
                $data[$field] = trim($m[1]);
            }
        }

        return $data ?: null;
    }

    private function soapClient(): SoapClient
    {
        if ($this->soapClient instanceof SoapClient) {
            return $this->soapClient;
        }

        $this->soapClient = new SoapClient($this->wsdl, [
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => $this->timeoutSeconds,
            'location' => $this->endpoint,
            'uri' => config('emola.soap_namespace'),
            'style' => SOAP_RPC,
            'use' => SOAP_LITERAL,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ]);

        return $this->soapClient;
    }

    private function extractXmlFromCdata(?string $original): ?string
    {
        if (! $original) {
            return null;
        }

        if (preg_match('/<\?xml[\s\S]*$/', $original, $m)) {
            return trim($m[0]);
        }

        $trim = trim($original);

        return str_starts_with($trim, '<') ? $trim : null;
    }

    /**
     * @return array<string, string>|null
     */
    private function parseOriginalData(?string $original): ?array
    {
        $xml = $this->extractXmlFromCdata($original);
        if (! $xml) {
            return null;
        }

        libxml_use_internal_errors(true);
        try {
            $sxe = simplexml_load_string($xml);
            if (! $sxe) {
                return null;
            }

            $namespaces = $sxe->getNamespaces(true);
            $soapNs = $namespaces['S'] ?? 'http://schemas.xmlsoap.org/soap/envelope/';
            $body = $sxe->children($soapNs)->Body ?? $sxe->Body ?? null;
            if (! $body) {
                return null;
            }

            foreach ($body->children() as $child) {
                if (! isset($child->return)) {
                    continue;
                }

                $data = [];
                foreach ($child->return->children() as $k => $v) {
                    $data[(string) $k] = trim((string) $v);
                }

                return $data ?: null;
            }

            return null;
        } finally {
            libxml_clear_errors();
        }
    }

    private function assertConfigured(): void
    {
        foreach ([
            'wsdl' => $this->wsdl,
            'endpoint' => $this->endpoint,
            'username' => $this->username,
            'password' => $this->password,
            'partnerCode' => $this->partnerCode,
            'key' => $this->key,
        ] as $k => $v) {
            if (! is_string($v) || trim($v) === '') {
                throw new \RuntimeException("eMola is not configured: missing {$k}");
            }
        }
    }
}
