<?php

use Illuminate\Support\Facades\Route;

// eMola async callback (JSON) — Movitel calls this URL (CSRF excluded via api/* in VerifyCsrfToken).
Route::post('emola/callback', '\\App\\Http\\Controllers\\Api\\EmolaCallbackController');

// Initiate USSD push (optional standalone API; checkout uses EmolaPaymentService).
Route::post('emola/pay', '\\App\\Http\\Controllers\\Api\\EmolaGatewayController@pay');

// Diagnostic / support endpoints.
Route::get('emola/status/{transId}', '\\App\\Http\\Controllers\\Api\\EmolaGatewayController@status');
Route::get('emola/balance', '\\App\\Http\\Controllers\\Api\\EmolaGatewayController@balance');
Route::get('emola/beneficiary', '\\App\\Http\\Controllers\\Api\\EmolaGatewayController@beneficiary');
Route::get('emola/order/{transId}', '\\App\\Http\\Controllers\\Api\\EmolaGatewayController@orderByTransId');
