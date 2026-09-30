<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\SocialiteBaseController;
use App\Http\Requests\Validations\SpcialLoginRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthSocialController extends SocialiteBaseController
{
    /**
     * Social auth request handler.
     *
     * @return \Illuminate\Http\Response
     */
    public function socialLogin(SpcialLoginRequest $request, $provider)
    {
        // An access token proves who the user is to *some* app. Only accept tokens issued to ours,
        // otherwise any third-party site the user signed into could log in as them here.
        if (! $this->tokenIssuedToUs($provider, (string) $request->get('access_token'))) {
            return response()->json(['message' => trans('api.auth_failed')], 401);
        }

        try {
            $socialUser = $this->getSocialUser($provider, $request->get('access_token'));
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::info($e);

            $response = json_decode($e->getResponse()->getBody()->getContents(), true);

            return response()->json([
                'message' => trans('api.auth_failed'),
                'errors' => $response['error'] ?? 'Error',
                'error_description' => $response['error_description'] ?? '',
            ], 401);
        }

        $customer = $this->getLocalUser($socialUser);

        $customer->generateToken();

        return new CustomerResource($customer);
    }

    private function tokenIssuedToUs(string $provider, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            if ($provider === 'facebook') {
                $appId = (string) config('services.facebook.client_id');
                $secret = (string) config('services.facebook.client_secret');
                if ($appId === '' || $secret === '') {
                    Log::warning('Facebook login: FB_CLIENT_ID/FB_CLIENT_SECRET missing, cannot verify token audience');

                    return false;
                }

                $data = Http::timeout(10)->get('https://graph.facebook.com/debug_token', [
                    'input_token' => $token,
                    'access_token' => $appId.'|'.$secret,
                ])->json('data', []);

                return ! empty($data['is_valid']) && (string) ($data['app_id'] ?? '') === $appId;
            }

            if ($provider === 'google') {
                $allowed = array_values(array_filter(array_map('trim', explode(',', (string) config('services.google.app_client_ids')))));
                if (! $allowed) {
                    Log::warning('Google login: GOOGLE_APP_CLIENT_IDS not set, token audience not verified');

                    return true;
                }
                $allowed[] = (string) config('services.google.client_id');

                $info = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['access_token' => $token])->json();

                return in_array((string) ($info['azp'] ?? $info['aud'] ?? ''), $allowed, true);
            }
        } catch (\Throwable $e) {
            Log::warning('Social token audience check failed: '.$e->getMessage());

            return false;
        }

        // Apple sends a signed identity token that the Socialite provider verifies.
        return true;
    }
}
