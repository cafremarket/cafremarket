<?php

use App\Http\Controllers\Admin\FeaturedCategoriesController;
use Illuminate\Support\Facades\Route;

Route::get('featured-categories', [FeaturedCategoriesController::class, 'index'])->name('featuredCategories');
Route::post('featured-categories', [FeaturedCategoriesController::class, 'update'])->name('featuredCategories.update');
