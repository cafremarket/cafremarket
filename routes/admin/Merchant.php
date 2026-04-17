<?php

use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\MerchantUploadController;
use Illuminate\Support\Facades\Route;

// Bulk upload routes
Route::get('merchant/upload', [
    MerchantUploadController::class,
    'showForm',
])->name('merchant.bulk');

Route::post('merchant/upload', [
    MerchantUploadController::class,
    'upload',
])->name('merchant.upload');

Route::post('merchant/import', [
    MerchantUploadController::class,
    'import',
])->name('merchant.import');

Route::get('merchant/upload/downloadTemplate', [
    MerchantUploadController::class,
    'downloadTemplate',
])->name('merchant.template');

Route::post('merchant/downloadFailedRows', [
    MerchantUploadController::class,
    'downloadFailedRows',
])->name('merchant.downloadFailedRows');

// Crud routes
Route::get('changePasswordForm/{merchant}', [MerchantController::class, 'ShowChangePasswordForm'])->name('merchant.changePassword');

Route::put('updatePassword/{merchant}', [MerchantController::class, 'updatePassword'])->name('merchant.updatePassword');

Route::resource('merchant', MerchantController::class)->except('delete');
