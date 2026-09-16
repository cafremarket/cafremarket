<?php

use Illuminate\Support\Facades\Route;

// Common
include 'Common.php';

// Front End routes
include 'Frontend.php';

// Backoffice routes
include 'Backoffice.php';

// Merchant panel (separate from admin)
include 'Merchant.php';

// Webhooks
Route::post('payment/callback/emola', \App\Http\Controllers\Api\EmolaCallbackController::class)
    ->name('payment.callback.emola');

// AJAX routes for get images
// Route::get('order/ajax/taxrate', [OrderController::class, 'ajaxTaxRate'])->name('ajax.taxrate');
