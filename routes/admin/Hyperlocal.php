<?php

use App\Http\Controllers\Admin\HyperlocalDispatchController;
use App\Http\Controllers\Admin\PlatformDeliveryBoyController;
use Illuminate\Support\Facades\Route;

Route::name('platform_rider.')->prefix('delivery/platform-riders')->group(function () {
    Route::get('/', [PlatformDeliveryBoyController::class, 'index'])->name('index');
    Route::get('create', [PlatformDeliveryBoyController::class, 'create'])->name('create');
    Route::post('/', [PlatformDeliveryBoyController::class, 'store'])->name('store');
    Route::get('{platform_rider}/edit', [PlatformDeliveryBoyController::class, 'edit'])->name('edit');
    Route::put('{platform_rider}', [PlatformDeliveryBoyController::class, 'update'])->name('update');
    Route::delete('{platform_rider}/trash', [PlatformDeliveryBoyController::class, 'trash'])->name('trash');
    Route::get('{platform_rider_id}/restore', [PlatformDeliveryBoyController::class, 'restore'])->name('restore');
    Route::delete('{platform_rider}', [PlatformDeliveryBoyController::class, 'destroy'])->name('destroy');
});

Route::get('hyperlocal/dispatch', [HyperlocalDispatchController::class, 'index'])->name('hyperlocal.dispatch');
Route::get('hyperlocal/dispatch/data', [HyperlocalDispatchController::class, 'data'])->name('hyperlocal.dispatch.data');
