<?php

use App\Http\Controllers\Admin\FeaturedShopsController;
use Illuminate\Support\Facades\Route;

Route::get('featured-shops', [FeaturedShopsController::class, 'index'])->name('featuredShops');
Route::post('featured-shops', [FeaturedShopsController::class, 'update'])->name('featuredShops.update');
