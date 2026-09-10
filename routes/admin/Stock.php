<?php

use App\Http\Controllers\Admin\StockManagementController;
use Illuminate\Support\Facades\Route;

Route::get('overview', [StockManagementController::class, 'overview'])
    ->name('overview');

Route::get('movements', [StockManagementController::class, 'movements'])
    ->name('movements');

Route::get('low-stock', [StockManagementController::class, 'lowStock'])
    ->name('low');

Route::get('transfers', [StockManagementController::class, 'transfers'])
    ->name('transfers');

Route::get('transfer/create', [StockManagementController::class, 'transferForm'])
    ->name('transfer.create');

Route::post('transfer', [StockManagementController::class, 'transfer'])
    ->name('transfer.store');

Route::put('inventory/{inventory}/adjust-stock', [StockManagementController::class, 'adjust'])
    ->name('inventory.adjustStock');
