<?php

namespace App\Services\Emola;

use Illuminate\Http\Request;

/**
 * Normalizes Movitel callback payloads (JSON, form, or XML).
 */
final class EmolaCallbackPayload
{
    /**
     * @return array{reqeustId: string, transId: string, refNo: string, errorCode: string, message: string}|null
     */
    public static function fromRequest(Request $request): ?array
    {
        $data = self::normalizeKeys($request->all());

        $content = (string) $request->getContent();
        if ($content !== '') {
            $json = json_decode($content, true);
            if (is_array($json)) {
                $data = array_merge($data, self::normalizeKeys($json));
            }

            if (str_contains($content, '<')) {
                $xml = self::parseXml($content);
                if ($xml) {
                    $data = array_merge($data, $xml);
                }
            }
        }

        $required = ['reqeustId', 'transId', 'refNo', 'errorCode', 'message'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                return null;
            }
            $data[$field] = trim((string) $data[$field]);
        }

        if ($data['transId'] === '' || $data['refNo'] === '') {
            return null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private static function normalizeKeys(array $data): array
    {
        $aliases = [
            'requestId' => 'reqeustId',
            'request_id' => 'reqeustId',
            'RequestId' => 'reqeustId',
            'ReqeustId' => 'reqeustId',
            'trans_id' => 'transId',
            'TransId' => 'transId',
            'ref_no' => 'refNo',
            'RefNo' => 'refNo',
            'error_code' => 'errorCode',
            'ErrorCode' => 'errorCode',
            'Message' => 'message',
        ];

        $out = [];
        foreach ($data as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $name = $aliases[$key] ?? $key;
            $out[$name] = trim((string) $value);
        }

        return $out;
    }

    /**
     * @return array<string, string>|null
     */
    private static function parseXml(string $content): ?array
    {
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($content);
        if ($sxe === false) {
            return null;
        }

        $flat = [];
        foreach ($sxe->xpath('//*') ?: [] as $node) {
            $name = $node->getName();
            if (in_array($name, ['reqeustId', 'requestId', 'transId', 'refNo', 'errorCode', 'message'], true)) {
                $flat[$name] = trim((string) $node);
            }
        }

        return $flat ? self::normalizeKeys($flat) : null;
    }
}
