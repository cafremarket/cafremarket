<?php

use Illuminate\Support\Facades\Route;

// eMola async callback (JSON) — Movitel calls this URL (CSRF excluded via api/* in VerifyCsrfToken).
Route::post('emola/callback', '\\App\\Http\\Controllers\\Api\\EmolaCallbackController');

// The standalone EmolaGatewayController tools (pay, status, balance, beneficiary, order lookup)
// are intentionally not routed: they were unauthenticated and let anyone rewrite an order's
// payment fields, trigger USSD pushes, and read the partner balance. Checkout uses
// EmolaPaymentService; use `php artisan tinker` for support lookups.
