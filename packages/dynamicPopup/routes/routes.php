<?php

use Illuminate\Support\Facades\Route;
use Incevio\Package\DynamicPopup\Http\Controllers\PopupController;

Route::middleware(['web', 'auth', 'xssSanitizer', 'userType:admin'])->name('admin.appearance.')
  ->prefix('admin/appearance')->group(function () {
    Route::get('popup', [
      PopupController::class, 'index'
    ])->name('popup');

    Route::post('update', [
      PopupController::class, 'update'
    ])->name('popup.update');
  });
