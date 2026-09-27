<?php

namespace App\Helpers;

use App\Services\Auth\VerificationHealth;
use GuzzleHttp\Client;

class ReCaptcha
{
    /**
     * Validation rules for web auth forms; empty when reCAPTCHA is not configured.
     */
    public static function rules(): array
    {
        if (! config('services.recaptcha.key')) {
            return [];
        }

        return ['g-recaptcha-response' => 'required|recaptcha'];
    }

    /**
     * Rules for mobile-app auth endpoints. Always verified when the app sends a
     * token; only required once services.recaptcha.app_enforce is on.
     */
    public static function appRules(): array
    {
        if (! config('services.recaptcha.key')) {
            return [];
        }

        if (config('services.recaptcha.app_enforce')) {
            return ['g-recaptcha-response' => 'required|recaptcha'];
        }

        return request()->filled('g-recaptcha-response')
            ? ['g-recaptcha-response' => 'recaptcha']
            : [];
    }

    public static function messages(): array
    {
        return ['g-recaptcha-response.required' => trans('validation.recaptcha')];
    }

    public function validate($attribute, $value, $parameters, $validator)
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        try {
            $response = (new Client(['timeout' => 8]))->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ],
            ]);

            $body = json_decode((string) $response->getBody());
        } catch (\Throwable $e) {
            // Google unreachable: don't lock real people out of signup. The
            // failure is shown on the admin verification report instead.
            VerificationHealth::recordRecaptchaFailure('Google unreachable: '.$e->getMessage());

            return true;
        }

        $errors = (array) ($body->{'error-codes'} ?? []);

        // A wrong secret/site key is a setup problem, not a bot.
        if (array_intersect($errors, ['missing-input-secret', 'invalid-input-secret'])) {
            VerificationHealth::recordRecaptchaFailure(implode(', ', $errors));

            return true;
        }

        return (bool) ($body->success ?? false);
    }
}
