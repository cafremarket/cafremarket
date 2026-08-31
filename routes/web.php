<?php

use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

// Common
include 'Common.php';

// Front End routes
include 'Frontend.php';

// Backoffice routes
include 'Backoffice.php';

// Merchant panel (separate from admin)
include 'Merchant.php';

// Webhooks
// Route::post('webhook/stripe', [WebhookController::class, 'handleStripeCallback']); 		// Stripe
Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// eMola / Movitel payment callback (alternate URL — also available at POST /api/emola/callback)
Route::post('payment/callback/emola', \App\Http\Controllers\Api\EmolaCallbackController::class)
    ->name('payment.callback.emola');

// AJAX routes for get images
// Route::get('order/ajax/taxrate', [OrderController::class, 'ajaxTaxRate'])->name('ajax.taxrate');
