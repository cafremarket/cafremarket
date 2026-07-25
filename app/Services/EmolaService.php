<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmolaService
{
    protected string $endpoint;

    protected string $username;

    protected string $password;

    protected string $partnerCode;

    protected string $key;

    public function __construct()
    {
        $this->endpoint = (string) config('emola.endpoint');
        $this->username = (string) config('emola.username');
        $this->password = (string) config('emola.password');
        $this->partnerCode = (string) config('emola.partner_code');
        $this->key = (string) config('emola.key');
    }

    /**
     * C2B - Collect payment from customer via USSD
     */
    public function pushPayment(
        string $msisdn,
        string $amount,
        string $transId,
        string $refNo,
        string $smsContent = 'Pagamento CafreMarket'
    ): array {
        return $this->call('pushUssdMessage', [
            'partnerCode' => $this->partnerCode,
            'msisdn' => $msisdn,
            'smsContent' => $smsContent,
            'transAmount' => $amount,
            'transId' => $transId,
            'language' => 'pt',
            'refNo' => $refNo,
            'key' => $this->key,
        ]);
    }

    /**
     * Query transaction status
     */
    public function queryTransaction(string $transId): array
    {
        return $this->call('pushUssdQueryTrans', [
            'partnerCode' => $this->partnerCode,
            'transId' => $transId,
            'transType' => 'C2B',
            'key' => $this->key,
        ]);
    }

    /**
     * B2C - Send money to customer
     */
    public function disbursement(
        string $msisdn,
        string $amount,
        string $transId,
        string $remark = 'CafreMarket'
    ): array {
        return $this->call('pushUssdDisbursementB2C', [
            'partnerCode' => $this->partnerCode,
            'msisdn' => $msisdn,
            'transAmount' => $amount,
            'transId' => $transId,
            'smsContent' => $remark,
            'key' => $this->key,
        ]);
    }

    /**
     * Generate unique transaction ID
     */
    public function generateTransId(): string
    {
        return 'CAFRE'.date('YmdHis').rand(100, 999);
    }

    /**
     * Build and send SOAP request via cURL
     */
    protected function call(string $wscode, array $params): array
    {
        $paramsXml = '';
        foreach ($params as $name => $value) {
            $paramsXml .= '<param name="'.htmlspecialchars((string) $name, ENT_XML1).'" value="'.htmlspecialchars((string) $value, ENT_XML1).'"/>'."\n";
        }

        $soapBody = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:web="http://webservice.bccsgw.viettel.com/">
            <soapenv:Header/>
            <soapenv:Body>
                <web:gwOperation>
                    <Input>
                        <username>'.htmlspecialchars($this->username, ENT_XML1).'</username>
                        <password>'.htmlspecialchars($this->password, ENT_XML1).'</password>
                        <wscode>'.htmlspecialchars($wscode, ENT_XML1).'</wscode>
                        '.$paramsXml.'
                        <rawData></rawData>
                    </Input>
                </web:gwOperation>
            </soapenv:Body>
        </soapenv:Envelope>';

        Log::info('eMola request', [
            'wscode' => $wscode,
            'endpoint' => $this->endpoint,
            'params' => $params,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml;charset=UTF-8',
            'SOAPAction: ""',
            'Content-Length: '.strlen($soapBody),
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) config('emola.timeout_seconds', 60));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('eMola cURL error', ['wscode' => $wscode, 'error' => $curlError]);

            return ['success' => false, 'error' => $curlError];
        }

        Log::info('eMola raw response', ['wscode' => $wscode, 'response' => $response]);

        return $this->parseResponse((string) $response);
    }

    /**
     * Parse SOAP XML response
     */
    protected function parseResponse(string $xml): array
    {
        try {
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($xml);

            if (! $doc) {
                return ['success' => false, 'error' => 'Invalid XML response'];
            }

            $result = $doc->xpath('//*[local-name()="Result"]');

            if (empty($result)) {
                return ['success' => false, 'error' => 'No result found'];
            }

            $error = (string) $result[0]->error;
            $description = (string) $result[0]->description;
            $gwtransid = (string) $result[0]->gwtransid;
            $original = (string) $result[0]->original;

            $originalDecoded = html_entity_decode($original);

            $innerResult = [
                'errorCode' => null,
                'message' => null,
                'requestId' => null,
                'orgResponseCode' => null,
                'balance' => null,
            ];

            if ($originalDecoded !== '') {
                $innerXml = simplexml_load_string($originalDecoded);
                if ($innerXml) {
                    $returnNode = $innerXml->xpath('//*[local-name()="return"]');
                    if (! empty($returnNode)) {
                        $innerResult['errorCode'] = (string) $returnNode[0]->errorCode;
                        $innerResult['message'] = (string) $returnNode[0]->message;
                        $innerResult['requestId'] = (string) $returnNode[0]->reqeustId;
                        $innerResult['orgResponseCode'] = (string) $returnNode[0]->orgResponseCode;
                        $innerResult['balance'] = (string) $returnNode[0]->balance;
                    }
                }
            }

            $success = ($error === '0' && $innerResult['errorCode'] === '0');

            return [
                'success' => $success,
                'gatewayError' => $error,
                'description' => $description,
                'gwtransid' => $gwtransid,
                'errorCode' => $innerResult['errorCode'],
                'message' => $innerResult['message'],
                'requestId' => $innerResult['requestId'],
                'orgResponseCode' => $innerResult['orgResponseCode'],
                'balance' => $innerResult['balance'],
            ];
        } catch (\Exception $e) {
            Log::error('eMola parse error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
