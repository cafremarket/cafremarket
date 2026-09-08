<?php

use App\Http\Controllers\Admin\DealOfTheDayController;
use Illuminate\Support\Facades\Route;

Route::get('deal-of-the-day', [DealOfTheDayController::class, 'index'])->name('dealOfTheDay');
Route::post('deal-of-the-day/assign', [DealOfTheDayController::class, 'assign'])->name('dealOfTheDay.assign');
Route::post('deal-of-the-day/clear', [DealOfTheDayController::class, 'clear'])->name('dealOfTheDay.clear');
