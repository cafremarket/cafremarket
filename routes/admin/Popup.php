<?php

use App\Http\Controllers\Admin\PopupController;
use Illuminate\Support\Facades\Route;

Route::post('popups/massDestroy', [PopupController::class, 'massDestroy'])
    ->name('popup.massDestroy')
    ->middleware('demoCheck');

Route::resource('popups', PopupController::class)
    ->except('show')
    ->names('popup');
