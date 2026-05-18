<?php

namespace App\Services\Emola;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

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

    /**
     * C2B — collect payment from customer (pushUssdMessage).
     */
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

    /**
     * C2B — collect payment from customer (alias matching Movitel docs).
     */
    public function pushPayment(
        string $msisdn,
        string $amount,
        string $transId,
        string $refNo,
        string $smsContent = 'Pagamento CafreMarket',
        ?string $language = null,
    ): EmolaResponse {
        return $this->gwOperation('pushUssdMessage', [
            'partnerCode' => $this->partnerCode,
            'msisdn' => $msisdn,
            'smsContent' => $smsContent,
            'transAmount' => $amount,
            'transId' => $transId,
            'language' => $language ?? $this->language,
            'refNo' => $refNo,
            'key' => $this->key,
        ]);
    }

    public function pushUssdQueryTrans(string $transId, string $transType = 'C2B'): EmolaResponse
    {
        return $this->queryTransaction($transId, $transType);
    }

    /**
     * Query transaction status (pushUssdQueryTrans).
     */
    public function queryTransaction(string $transId, string $transType = 'C2B'): EmolaResponse
    {
        return $this->gwOperation('pushUssdQueryTrans', [
            'partnerCode' => $this->partnerCode,
            'transId' => $transId,
            'transType' => $transType,
            'key' => $this->key,
        ]);
    }

    public function pushUssdDisbursementB2C(array $input): EmolaResponse
    {
        return $this->gwOperation('pushUssdDisbursementB2C', [
            'partnerCode' => $this->partnerCode,
            'msisdn' => (string) Arr::get($input, 'msisdn'),
            'smsContent' => (string) Arr::get($input, 'smsContent', ''),
            'transAmount' => (string) Arr::get($input, 'transAmount'),
            'transId' => (string) Arr::get($input, 'transId'),
            'key' => $this->key,
        ]);
    }

    public function queryBeneficiaryName(string $transId, string $msisdn): EmolaResponse
    {
        return $this->getBeneficiaryName($msisdn, $transId);
    }

    /**
     * Get beneficiary name by MSISDN (queryBeneficiaryName).
     */
    public function getBeneficiaryName(string $msisdn, ?string $transId = null): EmolaResponse
    {
        return $this->gwOperation('queryBeneficiaryName', [
            'partnerCode' => $this->partnerCode,
            'transId' => $transId ?? $this->generateTransId(),
            'msisdn' => $msisdn,
            'key' => $this->key,
        ]);
    }

    public function queryAccountBalance(?string $transId = null): EmolaResponse
    {
        return $this->checkBalance($transId);
    }

    /**
     * Check partner account balance (queryAccountBalance).
     */
    public function checkBalance(?string $transId = null): EmolaResponse
    {
        return $this->gwOperation('queryAccountBalance', [
            'partnerCode' => $this->partnerCode,
            'transId' => $transId ?? $this->generateTransId(),
            'key' => $this->key,
        ]);
    }

    public function generateTransId(): string
    {
        return 'CAFRE'.date('YmdHis').rand(100, 999);
    }

    /**
     * Build gwOperation Input payload (WSDL complex type: username, password, wscode, param, rawData).
     *
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    protected function buildInput(string $wscode, array $params): array
    {
        $paramItems = [];
        foreach ($params as $name => $value) {
            $paramItems[] = [
                'name' => (string) $name,
                'value' => (string) $value,
            ];
        }

        return [
            'username' => $this->username,
            'password' => $this->password,
            'wscode' => $wscode,
            'param' => $paramItems,
            'rawData' => '',
        ];
    }

    /**
     * Inner XML string for gateways that expect literal Input (RPC/Literal).
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

        $xml .= '<rawData></rawData>';

        return $xml;
    }

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

        try {
            $response = $this->callGwOperationStructured($wscode, $params);

            if ($wscode === 'pushUssdMessage' && ! $response->isUssdPushAccepted()) {
                Log::info('eMola push: retrying with XML Input encoding', [
                    'gateway_error' => $response->gatewayError,
                    'business_code' => $response->businessErrorCode(),
                ]);

                $xmlResponse = $this->callGwOperationXml($wscode, $params);

                if ($xmlResponse->isUssdPushAccepted()) {
                    return $xmlResponse;
                }

                if ($xmlResponse->businessErrorCode() !== null) {
                    return $xmlResponse;
                }
            }

            return $response;
        } catch (SoapFault $e) {
            Log::error('eMola SOAP fault', [
                'wscode' => $wscode,
                'faultcode' => $e->faultcode ?? null,
                'faultstring' => $e->faultstring ?? null,
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

    /**
     * @param  array<string, string>  $params
     */
    private function callGwOperationStructured(string $wscode, array $params): EmolaResponse
    {
        $client = $this->soapClient();
        $result = $client->gwOperation(['Input' => $this->buildInput($wscode, $params)]);

        return $this->parseGwOperationResult($result, $wscode);
    }

    /**
     * @param  array<string, string>  $params
     */
    private function callGwOperationXml(string $wscode, array $params): EmolaResponse
    {
        $client = $this->soapClient();
        $xml = $this->buildInputXml($wscode, $params);
        $input = new \SoapVar($xml, XSD_ANYXML);
        $result = $client->__soapCall('gwOperation', [['Input' => $input]]);

        return $this->parseGwOperationResult($result, $wscode);
    }

    private function parseGwOperationResult(mixed $result, string $wscode = ''): EmolaResponse
    {
        $res = null;

        if (is_object($result)) {
            $res = $result->Result ?? $result->return ?? $result->Output ?? $result;
        } elseif (is_array($result)) {
            $res = $result['Result'] ?? $result['return'] ?? $result['Output'] ?? $result;
        }

        if (is_string($res)) {
            $originalXml = $this->extractXmlFromCdata($res);
            $originalData = $this->parseOriginalData($res) ?? $this->parseLooseOriginalData($res);

            return new EmolaResponse(
                gatewayError: $this->resolveGatewayError($originalData, null),
                gatewayDescription: is_array($originalData)
                    ? ($originalData['description'] ?? $originalData['message'] ?? null)
                    : null,
                gwtransid: is_array($originalData) ? ($originalData['gwtransid'] ?? null) : null,
                originalXml: $originalXml,
                originalData: $originalData,
            );
        }

        if (is_object($res)) {
            $gatewayError = (string) ($res->error ?? '');
            $gatewayDescription = isset($res->description) ? (string) $res->description : null;
            $gwtransid = isset($res->gwtransid) ? (string) $res->gwtransid : null;
            $original = isset($res->original) ? (string) $res->original : null;
            $originalData = $this->parseOriginalData($original) ?? $this->parseLooseOriginalData($original ?? '');

            return new EmolaResponse(
                gatewayError: $this->resolveGatewayError($originalData, $gatewayError !== '' ? $gatewayError : null),
                gatewayDescription: $gatewayDescription,
                gwtransid: $gwtransid,
                originalXml: $this->extractXmlFromCdata($original),
                originalData: $originalData,
            );
        }

        Log::warning('eMola unexpected gwOperation response', [
            'wscode' => $wscode,
            'result_type' => get_debug_type($result),
        ]);

        return new EmolaResponse(
            gatewayError: 'UNKNOWN_RESPONSE',
            gatewayDescription: 'Unexpected gwOperation response shape',
            gwtransid: null,
            originalXml: null,
            originalData: null,
        );
    }

    /**
     * @param  array<string, string>|null  $originalData
     */
    private function resolveGatewayError(?array $originalData, ?string $gatewayError): string
    {
        if ($gatewayError !== null && $gatewayError !== '') {
            return $gatewayError;
        }

        if (is_array($originalData) && isset($originalData['error']) && $originalData['error'] !== '') {
            return (string) $originalData['error'];
        }

        return '';
    }

    /**
     * Fallback parser when SOAP envelope shape differs.
     *
     * @return array<string, string>|null
     */
    private function parseLooseOriginalData(string $payload): ?array
    {
        $data = [];

        foreach (['errorCode', 'error', 'message', 'description', 'gwtransid', 'reqeustId'] as $field) {
            if (preg_match('/<'.$field.'>([^<]*)<\/'.$field.'>/i', $payload, $m)) {
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
            'uri' => 'http://webservice.bccsgw.viettel.com/',
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
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
            $body = $sxe->children($namespaces['S'] ?? null)->Body ?? $sxe->Body ?? null;
            if (! $body) {
                return null;
            }

            $returnNode = null;
            foreach ($body->children() as $child) {
                if (isset($child->return)) {
                    $returnNode = $child->return;
                    break;
                }
            }
            if (! $returnNode) {
                return null;
            }

            $data = [];
            foreach ($returnNode->children() as $k => $v) {
                $data[$k] = trim((string) $v);
            }

            return $data ?: null;
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
