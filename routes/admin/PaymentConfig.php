<?php

use App\Http\Controllers\Admin\ConfigPaypalController;
use App\Http\Controllers\Admin\ConfigStripeController;
use App\Http\Controllers\Admin\PaymentMethodController;
use Illuminate\Support\Facades\Route;

// General
Route::get('paymentMethod', [PaymentMethodController::class, 'index'])->name('config.paymentMethod.index');

Route::get('paymentMethod/{paymentMethod}/activate', [PaymentMethodController::class, 'activate'])->name('paymentMethod.activate');

Route::get('paymentMethod/{paymentMethod}/deactivate', [PaymentMethodController::class, 'deactivate'])->name('paymentMethod.deactivate');

// Manual
Route::get('manualPaymentMethod/{code}/activate', [PaymentMethodController::class, 'activateManualPaymentMethod'])->name('manualPaymentMethod.activate');

Route::put('manualPaymentMethod/{code}/update', [PaymentMethodController::class, 'updateManualPaymentMethod'])->name('manualPaymentMethod.update');

Route::get('manualPaymentMethod/{code}/deactivate', [PaymentMethodController::class, 'deactivateManualPaymentMethod'])->name('manualPaymentMethod.deactivate');

// Stripe
Route::get('stripe/connect', [ConfigStripeController::class, 'connect'])->name('stripe.connect');

Route::get('stripe/redirect', [ConfigStripeController::class, 'redirect'])->name('stripe.redirect');

Route::get('stripe/disconnect', [ConfigStripeController::class, 'disconnect'])->name('stripe.disconnect');

// PayPal
Route::get('paypal/activate', [ConfigPaypalController::class, 'activate'])->name('paypal.activate');

Route::put('paypal/{paypal}/update', [ConfigPaypalController::class, 'update'])->name('paypal.update');

Route::get('paypal/deactivate', [ConfigPaypalController::class, 'deactivate'])->name('paypal.deactivate');
