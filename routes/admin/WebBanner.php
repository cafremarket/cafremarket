<?php

use App\Http\Controllers\Admin\WebBannerController;
use Illuminate\Support\Facades\Route;

Route::post('web-banners/massDestroy', [WebBannerController::class, 'massDestroy'])
    ->name('web_banner.massDestroy')
    ->middleware('demoCheck');

Route::resource('web-banners', WebBannerController::class)->except('show');
