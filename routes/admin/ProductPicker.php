<?php

use App\Http\Controllers\Admin\ProductPickerController;
use Illuminate\Support\Facades\Route;

Route::get('product-picker/shops', [ProductPickerController::class, 'shops'])->name('productPicker.shops');
Route::get('product-picker/products', [ProductPickerController::class, 'products'])->name('productPicker.products');
