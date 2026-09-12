<?php

namespace App\Http\Middleware;

use App\Services\Api\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequest
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = ApiRequestLog::requestId($request);
        ApiRequestLog::bind($request, $requestId);
        $started = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            try {
                ApiRequestLog::exception($exception, $request);
                ApiRequestLog::error('API request aborted', $request, [
                    'status' => 500,
                    'duration_ms' => $this->durationMs($started),
                ]);
            } catch (Throwable $ignored) {
            }
            throw $exception;
        }

        try {
            $this->logCompleted($request, $response, $started);
        } catch (Throwable $ignored) {
            // Never replace a successful API payload with a logging failure.
        }

        if (method_exists($response, 'headers')) {
            $response->headers->set('X-Request-Id', $requestId);
        }

        return $response;
    }

    private function logCompleted(Request $request, $response, float $started): void
    {
        $status = $response instanceof Response ? $response->getStatusCode() : 0;
        $extra = [
            'status' => $status,
            'duration_ms' => $this->durationMs($started),
        ];

        $body = $this->safeResponseBody($response);
        $shape = ApiRequestLog::payloadShape($body);

        if ($status >= 500) {
            ApiRequestLog::error('API request failed', $request, $extra + [
                'response' => $body,
                'payload_shape' => $shape,
            ]);

            return;
        }

        if ($status >= 400) {
            ApiRequestLog::warning('API request client error', $request, $extra + [
                'response' => $body,
                'payload_shape' => $shape,
            ]);

            return;
        }

        if (ApiRequestLog::isUserScoped($request)) {
            ApiRequestLog::info('API user request', $request, $extra + [
                'payload_shape' => $shape,
            ]);

            return;
        }

        ApiRequestLog::debug('API request', $request, $extra);
    }

    private function durationMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    private function safeResponseBody($response): ?string
    {
        if (! method_exists($response, 'getContent')) {
            return null;
        }

        $content = (string) $response->getContent();
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $this->forgetSecrets($decoded);
            $content = json_encode($decoded) ?: $content;
        }

        return Str::limit($content, 2000);
    }

    private function forgetSecrets(array &$payload): void
    {
        foreach (['access_token', 'api_token', 'token', 'password', 'jwt_access_token'] as $key) {
            unset($payload[$key]);
            if (isset($payload['data']) && is_array($payload['data'])) {
                unset($payload['data'][$key]);
            }
        }
    }
}
