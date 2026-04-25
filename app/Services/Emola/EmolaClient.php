<?php

namespace App\Services\Emola;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class EmolaClient
{
    public function __construct(
        private ?string $wsdl = null,
        private ?string $username = null,
        private ?string $password = null,
        private ?string $partnerCode = null,
        private ?string $key = null,
        private ?int $timeoutSeconds = null,
    ) {
        $this->wsdl = $this->wsdl ?? Config::get('services.emola.wsdl');
        $this->username = $this->username ?? Config::get('services.emola.username');
        $this->password = $this->password ?? Config::get('services.emola.password');
        $this->partnerCode = $this->partnerCode ?? Config::get('services.emola.partner_code');
        $this->key = $this->key ?? Config::get('services.emola.key');
        $this->timeoutSeconds = $this->timeoutSeconds ?? (int) Config::get('services.emola.timeout_seconds', 70);
    }

    public function pushUssdMessage(array $input): EmolaResponse
    {
        $params = [
            ['name' => 'partnerCode', 'value' => $this->partnerCode],
            ['name' => 'msisdn', 'value' => (string) Arr::get($input, 'msisdn')],
            ['name' => 'smsContent', 'value' => (string) Arr::get($input, 'smsContent')],
            ['name' => 'transAmount', 'value' => (string) Arr::get($input, 'transAmount')],
            ['name' => 'transId', 'value' => (string) Arr::get($input, 'transId')],
            ['name' => 'language', 'value' => (string) Arr::get($input, 'language', 'pt')],
            ['name' => 'refNo', 'value' => (string) Arr::get($input, 'refNo')],
            ['name' => 'key', 'value' => $this->key],
        ];

        return $this->gwOperation('pushUssdMessage', $params);
    }

    public function pushUssdQueryTrans(string $transId, string $transType): EmolaResponse
    {
        $params = [
            ['name' => 'partnerCode', 'value' => $this->partnerCode],
            ['name' => 'transId', 'value' => $transId],
            ['name' => 'key', 'value' => $this->key],
            ['name' => 'transType', 'value' => $transType],
        ];

        return $this->gwOperation('pushUssdQueryTrans', $params);
    }

    public function pushUssdDisbursementB2C(array $input): EmolaResponse
    {
        $params = [
            ['name' => 'partnerCode', 'value' => $this->partnerCode],
            ['name' => 'msisdn', 'value' => (string) Arr::get($input, 'msisdn')],
            ['name' => 'smsContent', 'value' => (string) Arr::get($input, 'smsContent', '')],
            ['name' => 'transAmount', 'value' => (string) Arr::get($input, 'transAmount')],
            ['name' => 'transId', 'value' => (string) Arr::get($input, 'transId')],
            ['name' => 'key', 'value' => $this->key],
        ];

        return $this->gwOperation('pushUssdDisbursementB2C', $params);
    }

    public function queryBeneficiaryName(string $transId, string $msisdn): EmolaResponse
    {
        $params = [
            ['name' => 'transId', 'value' => $transId],
            ['name' => 'partnerCode', 'value' => $this->partnerCode],
            ['name' => 'msisdn', 'value' => $msisdn],
            ['name' => 'key', 'value' => $this->key],
        ];

        return $this->gwOperation('queryBeneficiaryName', $params);
    }

    public function queryAccountBalance(string $transId): EmolaResponse
    {
        $params = [
            ['name' => 'partnerCode', 'value' => $this->partnerCode],
            ['name' => 'transId', 'value' => $transId],
            ['name' => 'key', 'value' => $this->key],
        ];

        return $this->gwOperation('queryAccountBalance', $params);
    }

    private function gwOperation(string $wscode, array $params): EmolaResponse
    {
        $this->assertConfigured();

        $payload = [
            'Input' => [
                'username' => $this->username,
                'password' => $this->password,
                'wscode' => $wscode,
                'param' => array_values($params),
                // Spec marks rawData mandatory; in practice gateway accepts empty string.
                'rawData' => '',
            ],
        ];

        try {
            $client = $this->soapClient();
            $result = $client->__soapCall('gwOperation', [$payload]);

            $res = $result->Result ?? $result ?? null;
            $gatewayError = (string) ($res->error ?? '');
            $gatewayDescription = isset($res->description) ? (string) $res->description : null;
            $gwtransid = isset($res->gwtransid) ? (string) $res->gwtransid : null;
            $original = isset($res->original) ? (string) $res->original : null;

            return new EmolaResponse(
                gatewayError: $gatewayError,
                gatewayDescription: $gatewayDescription,
                gwtransid: $gwtransid,
                originalXml: $this->extractXmlFromCdata($original),
                originalData: $this->parseOriginalData($original),
            );
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

    private function soapClient(): SoapClient
    {
        $opts = [
            'exceptions' => true,
            'trace' => false,
            'connection_timeout' => $this->timeoutSeconds,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ];

        // Also set stream timeout for the underlying HTTP call.
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $this->timeoutSeconds,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $opts['stream_context'] = $ctx;

        return new SoapClient($this->wsdl, $opts);
    }

    private function extractXmlFromCdata(?string $original): ?string
    {
        if (! $original) {
            return null;
        }

        if (preg_match('/<\\?xml[\\s\\S]*$/', $original, $m)) {
            return trim($m[0]);
        }

        $trim = trim($original);
        if (str_starts_with($trim, '<')) {
            return $trim;
        }

        return null;
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

            // Navigate: Envelope -> Body -> *Response -> return
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
        foreach (['wsdl' => $this->wsdl, 'username' => $this->username, 'password' => $this->password, 'partnerCode' => $this->partnerCode, 'key' => $this->key] as $k => $v) {
            if (! is_string($v) || trim($v) === '') {
                throw new \RuntimeException("eMola is not configured: missing {$k}");
            }
        }
    }
}

