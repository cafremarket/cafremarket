<?php

use App\Http\Controllers\Storefront\AccountController;
use Illuminate\Support\Facades\Route;

Route::redirect('dashboard', '/my/dashboard');

Route::middleware('xssSanitizer')->group(function () {
    Route::get('my/account/password', function () {
        return app(AccountController::class)->index('password');
    })->name('account.password');

    Route::get('my/account/addresses', function () {
        return app(AccountController::class)->index('addresses');
    })->name('account.addresses');

    Route::get('my/account/delete', function () {
        return app(AccountController::class)->index('account_delete');
    })->name('account.delete');

    Route::get('my/{tab?}', [
        AccountController::class, 'index',
    ])->name('account');

    Route::put('my/password/update', [
        AccountController::class, 'password_update',
    ])->name('my.password.update');

    Route::put('my/account/update', [
        AccountController::class, 'update',
    ])->name('account.update');

    Route::delete('my/account/remove', [
        AccountController::class, 'delete_account',
    ])->name('my.account.remove');

    // Avatar
    Route::post('my/avatar/save', [
        AccountController::class, 'avatar',
    ])->name('my.avatar.save');

    Route::delete('my/avatar/remove', [
        AccountController::class, 'delete_avatar',
    ])->name('my.avatar.remove');

    // Address
    Route::get('my/address/select', [
        AccountController::class, 'select_address',
    ])->name('my.address.select');

    Route::post('my/address/{address}/use', [
        AccountController::class, 'use_address',
    ])->name('my.address.use');

    Route::get('my/address/create', [
        AccountController::class, 'create_address',
    ])->name('my.address.create');

    Route::post('my/address/save', [
        AccountController::class, 'save_address',
    ])->name('my.address.save');

    Route::get('my/address/{address}', [
        AccountController::class, 'address_edit',
    ])->name('my.address.edit');

    Route::put('my/address/{address}/update', [
        AccountController::class, 'address_update',
    ])->name('my.address.update');

    Route::get('my/address/{address}/delete', [
        AccountController::class, 'address_delete',
    ])->name('my.address.delete');
});
