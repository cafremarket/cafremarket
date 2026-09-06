<?php

use App\Http\Controllers\Admin\AppBannerController;
use Illuminate\Support\Facades\Route;

Route::post('app-banners/massDestroy', [AppBannerController::class, 'massDestroy'])
    ->name('app_banner.massDestroy')
    ->middleware('demoCheck');

Route::resource('app-banners', AppBannerController::class)
    ->except('show')
    ->names('app_banner')
    ->parameters(['app-banners' => 'banner']);
