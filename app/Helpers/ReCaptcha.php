<?php

namespace App\Helpers;

use App\Services\Auth\VerificationHealth;
use GuzzleHttp\Client;

class ReCaptcha
{
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
