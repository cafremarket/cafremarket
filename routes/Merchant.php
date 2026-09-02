<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\MerchantSwitchToCustomer;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Merchant\VerificationController as MerchantVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'merchantPanel'])->name('merchant.')->prefix('merchant')->group(function () {
    Route::get('switchToCustomer', [
        MerchantSwitchToCustomer::class,
        'switchToCustomer',
    ])->name('switchToCustomer');

    Route::get('createCustomer', [
        MerchantSwitchToCustomer::class,
        'createCustomer',
    ])->name('createCustomer');

    Route::name('account.')->prefix('account')->group(function () {
        Route::get('profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('update', [AccountController::class, 'update'])->name('update');
        Route::get('changePasswordForm', [AccountController::class, 'ShowChangePasswordForm'])->name('showChangePasswordForm');
        Route::post('updatePassword', [AccountController::class, 'updatePassword'])->name('updatePassword');
        Route::post('updatePhoto', [AccountController::class, 'updatePhoto'])->name('updatePhoto');
        Route::get('deletePhoto', [AccountController::class, 'deletePhoto'])->name('deletePhoto');
    });

    Route::middleware(['subscribed', 'checkBillingInfo', 'requireMerchantVerification'])->group(function () {
        Route::get('dashboard', [MerchantDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('dashboard');

        Route::name('admin.')->prefix('admin')->group(function () {
            include 'admin/User.php';
            include 'admin/DeliveryBoy.php';
        });

        Route::namespace('Admin\\Report')->group(function () {
            include 'admin/ShopReport.php';
        });

        Route::name('catalog.')->prefix('catalog')->group(function () {
            include 'admin/Product.php';
        });

        Route::name('stock.')->prefix('stock')->group(function () {
            include 'admin/Inventory.php';
            include 'admin/Warehouse.php';
            include 'admin/InventoryProduct.php';
        });

        Route::name('setting.')->prefix('setting')->group(function () {
            include 'admin/UserRole.php';

            Route::put('config/maintenanceMode/{shop}/toggle', [ConfigController::class, 'toggleMaintenanceMode'])
                ->name('config.maintenanceMode.toggle')->middleware('ajax');

            Route::put('config/updateBasicConfig/{shop}', [ConfigController::class, 'updateBasicConfig'])
                ->name('basic.config.update');

            Route::get('general', [ConfigController::class, 'viewGeneralSetting'])
                ->name('config.general');

            include 'admin/PaymentConfig.php';
        });

        Route::name('appearance.')->prefix('appearance')->group(function () {
            include 'admin/Banner.php';
        });

        Route::name('support.')->prefix('support')->group(function () {
            if (class_exists(\Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class)) {
                Route::get('chat', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'index',
                ])->name('chat.index');

                Route::get('chat/{chat}', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'show',
                ])->name('chat.show');

                Route::post('chat/{chat}/reply', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'reply',
                ])->name('chat.reply');
            }
        });
    });

    Route::get('verify', [MerchantVerificationController::class, 'index'])->name('verify');
    Route::post('verify', [MerchantVerificationController::class, 'submit'])->name('verify.submit');
    Route::post('verify/location', [MerchantVerificationController::class, 'saveLocation'])->name('verify.location');
    Route::post('verify/phone', [MerchantVerificationController::class, 'savePhone'])->name('verify.phone');
    Route::post('verify/email', [MerchantVerificationController::class, 'saveEmail'])->name('verify.email');
    Route::post('verify/documents', [MerchantVerificationController::class, 'storeDocuments'])->name('verify.documents.store');
    Route::post('verify/documents/{attachment}', [MerchantVerificationController::class, 'replaceDocument'])->name('verify.documents.replace');
    Route::delete('verify/documents/{attachment}', [MerchantVerificationController::class, 'deleteDocument'])->name('verify.documents.delete');
});
