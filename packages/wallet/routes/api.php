<?php

use Illuminate\Support\Facades\Route;
use Incevio\Package\Wallet\Http\Controllers\Api\DepositController;
use Incevio\Package\Wallet\Http\Controllers\Api\TransferController;
use Incevio\Package\Wallet\Http\Controllers\Api\WalletController;

Route::namespace('Api')->middleware(['api', 'auth:api'])->prefix('api')->group(function () {
    Route::get('wallet', [WalletController::class, 'index']);
    Route::get('wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('wallet/transaction/{transaction}/invoice', [WalletController::class, 'invoice']);
    Route::post('wallet/transfer', [TransferController::class, 'transfer']);
    Route::get('wallet/get_payment_methods', [DepositController::class, 'getPaymentMethods']);
    Route::post('wallet/deposit', [DepositController::class, 'deposit']);
    // M-Pesa wallet deposit: poll until paid (same as order mpesa/order/{order}/status)
    Route::get('wallet/deposit/mpesa/status', [DepositController::class, 'mpesaDepositStatus']);
});
