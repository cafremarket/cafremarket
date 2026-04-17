<?php

namespace Incevio\Package\MPesa\Http\Requests;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PaymentConfigInvalid;

/**
 * M-Pesa Mozambique (Vodacom) – aligned with mpesa-mz-nodejs-lib.
 * C2B: Bearer = encrypted API key, Origin header required. Base URL /ipg/v1x.
 */
class HttpRequest
{
    /** @var string Base URL (e.g. https://api.sandbox.vm.co.mz:18352) */
    private $base_url;

    /** @var string API Key from M-Pesa Mozambique developer portal */
    public $api_key;

    /** @var string Platform public key (PEM) for encrypting API key */
    public $public_key;

    /** @var string Service provider / till number */
    public $service_provider_code;

    /** @var string Origin (e.g. developer.mpesa.vm.co.mz) – required by API/WAF, same as Node lib */
    private $origin;

    private $callback_url;

    /** @var string|null Cached Bearer token (encrypted API key) */
    private $bearer_token;

    public $reference;

    public $sandbox;

    /** @var string Path prefix (e.g. /ipg/v1x) */
    private $path_prefix;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->sandbox = config('mpesa.api.sandbox', true);
        $this->base_url = rtrim(config('mpesa.base_url', 'https://api.sandbox.vm.co.mz:18352'), '/');
        $this->path_prefix = '/' . trim(config('mpesa.path_prefix', '/ipg/v1x'), '/');
        $this->origin = config('mpesa.origin', 'developer.mpesa.vm.co.mz');

        $this->api_key = config('mpesa.api.api_key');
        $this->public_key = config('mpesa.api.public_key');
        $this->service_provider_code = config('mpesa.service_provider_code');

        // Use explicit callback base URL on server so M-Pesa can reach this app (APP_URL or MPESA_CALLBACK_BASE_URL).
        $base = rtrim(config('mpesa.callback_base_url', config('app.callback_base_url', config('app.url'))), '/');
        $this->callback_url = $base . '/payment/response/callback';
    }

    public function setReference($reference = 'order')
    {
        $this->reference = $reference;

        return $this;
    }

    public function setVendorAPIKey(Shop $shop)
    {
        $config = $shop->config->mpesa ?? null;

        if ($config) {
            $this->api_key = $config->api_key ?? $config->consumer_key;
            $this->public_key = $config->public_key ?? $config->consumer_secret;
            $this->service_provider_code = $config->service_provider_code ?? $config->short_code ?? $config->lipa_na_mpesa;
            $this->sandbox = (bool) ($config->sandbox ?? true);
            $this->base_url = rtrim(config('mpesa.base_url', $this->base_url), '/');
            $this->path_prefix = '/' . trim(config('mpesa.path_prefix', $this->path_prefix), '/');
            $this->origin = config('mpesa.origin', $this->origin);
        }

        return $this;
    }

    /**
     * Get Bearer token: encrypted API key (used as Authorization: Bearer {encrypted}).
     */
    private function getBearerToken()
    {
        if ($this->bearer_token !== null) {
            return $this->bearer_token;
        }

        $encrypted = $this->encryptApiKey();
        if (!$encrypted) {
            throw new PaymentConfigInvalid("Failed to encrypt API key. Check your public key.");
        }

        $this->bearer_token = $encrypted;
        Log::info('M-Pesa Mozambique: Bearer token (encrypted API key) generated');

        return $this->bearer_token;
    }

    /**
     * Encrypt API key with platform public key (RSA).
     */
    private function encryptApiKey()
    {
        $publicKey = $this->public_key;
        if (empty($publicKey)) {
            Log::error('M-Pesa Mozambique: Public key not set');
            return null;
        }
        if (strpos($publicKey, '-----BEGIN PUBLIC KEY-----') === false) {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(trim($publicKey), 64, "\n") . "-----END PUBLIC KEY-----";
        }
        $key = openssl_pkey_get_public($publicKey);
        if (!$key) {
            Log::error('M-Pesa Mozambique: Invalid public key');
            return null;
        }
        $encrypted = '';
        $ok = openssl_public_encrypt($this->api_key, $encrypted, $key, OPENSSL_PKCS1_PADDING);
        openssl_pkey_free($key);

        return $ok ? base64_encode($encrypted) : null;
    }

    /**
     * Process C2B payment – body aligned with mpesa-mz-nodejs-lib (input_ThirdPartyReference, amount 2 decimals).
     */
    public function processTransaction($amount, $description = '')
    {
        if (!$this->reference) {
            $this->setReference();
        }

        $phone = $this->get_phone_number();
        $thirdPartyRef = $this->generateConversationId();
        $transactionRef = $this->reference . '_' . substr(uniqid(), -6);

        // Same payload shape as mpesa-mz-nodejs-lib: input_Amount as decimal string, input_ThirdPartyReference
        $body = [
            'input_ServiceProviderCode' => $this->service_provider_code,
            'input_CustomerMSISDN' => $phone,
            'input_Amount' => number_format((float) $amount, 2, '.', ''),
            'input_TransactionReference' => $transactionRef,
            'input_ThirdPartyReference' => $thirdPartyRef,
        ];

        $path = $this->path_prefix . '/c2bPayment/singleStage/';

        $response = $this->makeHttpCall($path, $body, true);

        return $response;
    }

    /**
     * Verify / query transaction status – same as mpesa-mz-nodejs-lib: GET on query port (18353).
     */
    public function verifyTransaction($transactionRef)
    {
        if (! config('mpesa.query_enabled', true)) {
            return null;
        }

        $thirdPartyRef = $this->generateConversationId();

        $queryBase = config('mpesa.query_base_url');
        if (!$queryBase) {
            $queryBase = preg_replace('/:18352$/', ':18353', $this->base_url);
        }
        $queryBase = rtrim($queryBase, '/');

        $path = $this->path_prefix . '/queryTransactionStatus/';
        $params = http_build_query([
            'input_ServiceProviderCode' => $this->service_provider_code,
            'input_QueryReference' => $transactionRef,
            'input_ThirdPartyReference' => $thirdPartyRef,
        ]);
        $url = $queryBase . $path . '?' . $params;

        Log::info('M-Pesa Query Transaction Status: calling', [
            'ref' => $transactionRef,
            'url_host' => parse_url($url, PHP_URL_HOST),
            'query_base' => $queryBase,
        ]);

        return $this->makeHttpCallGet($url);
    }

    private function generateConversationId()
    {
        return str_replace('.', '', uniqid('', true));
    }

    /**
     * Make HTTP call – Authorization: Bearer {encrypted API key}.
     */
    public function makeHttpCall($path, $body, $useBearer = true)
    {
        $url = $this->base_url . $path;

        Log::info('M-Pesa Mozambique request: ' . $url);

        // Same headers as mpesa-mz-nodejs-lib: Content-Type, Origin (required by API/WAF), Authorization
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: ' . (strpos($this->origin, '://') !== false ? $this->origin : 'https://' . $this->origin),
            'User-Agent: Cafrepay-Mpesa-MZ/1.0 (+https://developer.mpesa.vm.co.mz)',
        ];
        if ($useBearer) {
            $headers[] = 'Authorization: Bearer ' . $this->getBearerToken();
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);

        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response_code == 200 || $response_code == 201) {
            return $response;
        }

        Log::error('M-Pesa Mozambique error: ' . $response);

        // 403 with HTML body = WAF/firewall (e.g. Incapsula) blocking the request
        if ($response_code == 403 && (strpos($response, '<html') !== false || strpos($response, 'Forbidden') !== false)) {
            throw new PaymentConfigInvalid(
                'Request blocked (403). The M-Pesa sandbox may require your server IP to be whitelisted, or a firewall is blocking outbound requests. Contact Vodacom M-Pesa support or check developer.mpesa.vm.co.mz for IP whitelist.',
                $response_code
            );
        }

        $data = json_decode($response);
        $message = ($data && is_object($data))
            ? ($data->output_ResponseDesc ?? $data->output_ResponseCode ?? $data->error ?? null)
            : null;
        if (!$message) {
            $message = trans('mpesa::lang.error_response') . ' (HTTP ' . $response_code . ')';
        }
        throw new PaymentConfigInvalid($message, $response_code);
    }

    /**
     * GET request for queryTransactionStatus (mpesa-mz-nodejs-lib uses GET).
     */
    private function makeHttpCallGet($url)
    {
        Log::info('M-Pesa Mozambique request (GET): ' . $url);

        $originValue = strpos($this->origin, '://') !== false ? $this->origin : 'https://' . $this->origin;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: ' . $originValue,
            'User-Agent: Cafrepay-Mpesa-MZ/1.0 (+https://developer.mpesa.vm.co.mz)',
            'Authorization: Bearer ' . $this->getBearerToken(),
        ];

        $sslVerify = config('mpesa.ssl_verify', true);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        ]);

        $response = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($curl);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_errno) {
            Log::error('M-Pesa Query Transaction Status: cURL failed', [
                'errno' => $curl_errno,
                'error' => $curl_error,
                'url' => $url,
            ]);
            throw new PaymentConfigInvalid(
                'Query Transaction Status request failed: ' . $curl_error . ' (code ' . $curl_errno . '). Check server outbound access to M-Pesa API (port 18353).',
                $curl_errno
            );
        }

        if ($response_code == 0) {
            Log::error('M-Pesa Query Transaction Status: no HTTP response (connection failed)', [
                'url' => $url,
                'curl_errno' => $curl_errno,
                'curl_error' => $curl_error,
            ]);
            throw new PaymentConfigInvalid(
                'Could not connect to M-Pesa Query API (no response). Ensure your server can reach ' . parse_url($url, PHP_URL_HOST) . ' on port 18353 (outbound). If you use SSL, try adding MPESA_MZ_SSL_VERIFY=false in .env to test.',
                0
            );
        }

        if ($response_code == 200 || $response_code == 201) {
            Log::info('M-Pesa Query Transaction Status: success', ['code' => $response_code]);
            return $response;
        }

        Log::error('M-Pesa Mozambique error', ['code' => $response_code, 'response' => $response]);

        if ($response_code == 403 && (strpos($response, '<html') !== false || strpos($response, 'Forbidden') !== false)) {
            throw new PaymentConfigInvalid(
                'Request blocked (403). Check IP whitelist and Origin. Contact Vodacom M-Pesa support.',
                $response_code
            );
        }

        $data = json_decode($response);
        $message = ($data && is_object($data))
            ? ($data->output_ResponseDesc ?? $data->output_ResponseCode ?? $data->error ?? null)
            : null;
        throw new PaymentConfigInvalid($message ?: trans('mpesa::lang.error_response') . ' (HTTP ' . $response_code . ')', $response_code);
    }

    /**
     * Normalize Mozambique phone to 258XXXXXXXXX.
     */
    private function get_phone_number()
    {
        $phone = $this->request->mpesa_number ?? '';

        $phone = preg_replace('/\D/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '258' . substr($phone, 1);
        }
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '8') {
            $phone = '258' . $phone;
        }
        if (strpos($phone, '258') !== 0) {
            $phone = '258' . $phone;
        }

        return $phone;
    }
}
