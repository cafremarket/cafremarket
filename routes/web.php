<?php

use App\Http\Controllers\Storefront\HomeController;
use Illuminate\Support\Facades\Route;

// Common
include 'Common.php';

// Front End routes
include 'Frontend.php';

// Backoffice routes
include 'Backoffice.php';

// Merchant panel (separate from admin)
include 'Merchant.php';

// Webhooks
Route::post('payment/callback/emola', \App\Http\Controllers\Api\EmolaCallbackController::class)
    ->name('payment.callback.emola');

// Two-segment catalog URLs: /{category}/{subcategory}. Must be last so
// prefixed routes (admin, merchant, api, shop, …) always win.
//
// Symfony strips leading ^ and trailing $ from where() patterns, so a
// lookahead like (?!admin$) does NOT exclude /admin/dashboard — $ means
// end of the whole URL, not the first segment. Use (?:/|$) instead.
Route::middleware(['storefront', 'hasCookie'])->group(function () {
    $reserved = implode('|', [
        'page', 'product', 'listing', 'shop', 'shops', 'categories', 'category',
        'categorygrp', 'search', 'blog', 'selling', 'customer', 'contact',
        'contact_us', 'message', 'newsletter', 'cart', 'checkout', 'order',
        // liveChat package: GET chat/{shop} must not be captured as category/subcategory
        'chat', 'my', 'password', 'register', 'login', 'logout', 'verify', 'socialite',
        'brand', 'deals', 'admin', 'api', 'merchant', 'vendor', 'test',
        'locale', 'account', 'wishlist', 'compare', 'storage', 'css', 'js',
        'fonts', 'images', 'assets', 'livewire', 'build', 'horizon', 'pulse',
        'telescope', 'image', 'address', 'helper', 'payment', 'auctions',
        'events', 'brands', 'switchToMerchant', 'a', 'aff', 'visit', 'affiliate',
    ]);

    Route::get('{category}/{subcategory}', [
        HomeController::class, 'browseCategory',
    ])->where('category', '(?!(?:'.$reserved.')(?:/|$)).+')
        ->name('category.browse');
});

// AJAX routes for get images
// Route::get('order/ajax/taxrate', [OrderController::class, 'ajaxTaxRate'])->name('ajax.taxrate');
