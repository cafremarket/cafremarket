<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Public reCAPTCHA site key for the seller/delivery apps' login screens
 * (their system_configs endpoints need a signed-in user).
 */
class RecaptchaConfigController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'data' => ['recaptcha_site_key' => config('services.recaptcha.key')],
        ]);
    }
}
