<?php

use App\Http\Controllers\Admin\DeliveryBoyController;
use Illuminate\Support\Facades\Route;

Route::name('deliveryboy.')->group(function () {
    Route::get('deliveryboy/checkEmail', [
        DeliveryBoyController::class, 'checkEmail',
    ])->name('checkEmail');

    // No trash/restore step for delivery boys — delete is immediate and permanent.
    Route::post('deliveryboy/massDestroy', [
        DeliveryBoyController::class, 'massDestroy',
    ])->name('massDestroy')->middleware('demoCheck');

    Route::get('deliveryboy/{deliveryboy}/resetPassword', [
        DeliveryBoyController::class, 'resetPasswordForm',
    ])->name('resetPasswordForm');

    Route::put('deliveryboy/{deliveryboy}/resetPassword', [
        DeliveryBoyController::class, 'resetPassword',
    ])->name('resetPassword');
});

Route::resource('deliveryboy', DeliveryBoyController::class);
