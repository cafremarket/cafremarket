<?php

use App\Http\Controllers\Admin\FeaturedProductsController;
use Illuminate\Support\Facades\Route;

Route::get('featured-products', [FeaturedProductsController::class, 'index'])->name('featuredProducts');
Route::post('featured-products', [FeaturedProductsController::class, 'update'])->name('featuredProducts.update');
