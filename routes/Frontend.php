<?php

use App\Http\Controllers\Selling\LoginController as SellingLoginController;
use App\Http\Controllers\Selling\RegisterController as SellingRegisterController;
use App\Http\Controllers\Selling\SellingApiController;
use App\Http\Controllers\Selling\SellingController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\ConversationController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\LocationController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

// Route for storefront
Route::middleware(['storefront', 'hasCookie'])->namespace('Storefront')->group(function () {
    // Public: newsletter + customer auth
    Route::post('newsletter', [
        NewsletterController::class, 'subscribe',
    ])->name('newsletter.subscribe')->middleware('xssSanitizer');

    include 'storefront/Auth.php';

    // Homepage is public so guests can see the login modal
    Route::get('/', [
        HomeController::class, 'index',
    ])->name('homepage');

    // Public browsing (must NOT require customer login — otherwise store merchants
    // redirected from homepage to /shop/{slug} bounce back to /?login=1 forever).
    Route::get('page/{page}', [
        HomeController::class, 'openPage',
    ])->name('page.open');

    Route::get('product/{slug}', function ($slug) {
        $item = \App\Models\Inventory::query()->where('slug', $slug)->first(['id', 'slug', 'shop_id']);
        if ($item) {
            return redirect()->to(storefront_product_url($item), 301);
        }

        $product = \App\Models\Product::query()->where('slug', $slug)->first(['id', 'slug', 'shop_id']);
        if ($product) {
            $listing = \App\Models\Inventory::query()->where('product_id', $product->id)->first(['id', 'slug', 'shop_id']);
            if ($listing) {
                return redirect()->to(storefront_product_url($listing), 301);
            }
        }

        abort(404);
    });

    Route::get('listing/{slug}', function ($slug) {
        $item = \App\Models\Inventory::query()->where('slug', $slug)->first(['id', 'slug', 'shop_id']);

        return $item
            ? redirect()->to(storefront_product_url($item), 301)
            : redirect()->to(url('product/'.$slug));
    });

    Route::get('shop/{shop}/{slug}/quickView', [
        HomeController::class, 'quickViewItem',
    ])->name('quickView.product')->middleware('ajax');

    Route::get('shop/{shop}/{slug}/offers', [
        HomeController::class, 'offers',
    ])->name('show.offers');

    Route::get('shop/{slug}/products', [
        ShopController::class, 'products',
    ])->name('shop.products');

    Route::get('shop/{slug}/category/{category}', [
        ShopController::class, 'category',
    ])->name('shop.category.browse');

    Route::get('shop/{slug}/reviews', [
        ShopController::class, 'reviews',
    ])->name('shop.reviews');

    Route::get('shop/{shop}/{slug}', [
        HomeController::class, 'product',
    ])->where('slug', '^(?!products$|reviews$|category$).*$')->name('show.product');

    Route::get('categories', [
        HomeController::class, 'categories',
    ])->name('categories');

    Route::get('category/{slug}', [
        HomeController::class, 'browseCategory',
    ])->name('category.browse');

    Route::get('categories/{slug}', [
        HomeController::class, 'browseCategorySubGrp',
    ])->name('categories.browse');

    Route::get('categorygrp/{slug}', [
        HomeController::class, 'browseCategoryGroup',
    ])->name('categoryGrp.browse');

    Route::get('search', [
        HomeController::class, 'search',
    ])->name('inCategoriesSearch')->middleware('xssSanitizer');

    Route::get('blog', [
        BlogController::class, 'index',
    ])->name('blog');

    Route::any('blog/search', [
        BlogController::class, 'search',
    ])->name('blog.search')->middleware('xssSanitizer');

    Route::get('blog/{slug}', [
        BlogController::class, 'show',
    ])->name('blog.show');

    Route::get('blog/author/{author}', [
        BlogController::class, 'author',
    ])->name('blog.author');

    Route::get('blog/tag/{tag}', [
        BlogController::class, 'tag',
    ])->name('blog.tag');

    Route::get('shops', [
        ShopController::class, 'index',
    ])->name('shops');

    Route::get('shop/{slug?}', [
        ShopController::class, 'show',
    ])->name('show.store');

    Route::get('test/nearby-stores', [
        \App\Http\Controllers\Storefront\NearbyShopDiagnosticController::class, 'index',
    ])->name('test.nearby_stores');

    // Customer-only actions (account, cart, checkout, messages)
    Route::middleware(['auth:customer'])->group(function () {
        Route::post('customer/location', [
            LocationController::class, 'store',
        ])->name('customer.location.save');

        Route::post('customer/location/reverse-geocode', [
            LocationController::class, 'reverseGeocode',
        ])->name('customer.location.reverse');

        Route::get('customer/location/search', [
            LocationController::class, 'searchAddress',
        ])->name('customer.location.search');

        include 'storefront/Chat.php';
        include 'storefront/Cart.php';
        include 'storefront/Order.php';
        include 'storefront/GiftCard.php';
        include 'storefront/Account.php';
        include 'storefront/Feedback.php';

        Route::post('contact/{slug}', [
            ConversationController::class, 'contact',
        ])->name('seller.contact');

        Route::get('message/{message}/archive', [
            ConversationController::class, 'archive',
        ])->name('message.archive');

        Route::get('my/message/{message}', [
            ConversationController::class, 'show',
        ])->name('message.show');

        Route::post('message/{message}', [
            ConversationController::class, 'reply',
        ])->name('message.reply');
    });
});

Route::get('switchToMerchant', [
    AccountController::class, 'switchToMerchant',
])->middleware(['auth:customer'])->name('customer.switchToMerchant');

// Route for merchant landing theme
Route::middleware('selling')
    ->namespace('Selling')->group(function () {
        Route::get('selling', [
            SellingController::class, 'index',
        ])->name('selling');

        Route::prefix('selling/api')->group(function () {
            Route::get('config', [SellingApiController::class, 'config'])->name('selling.api.config');
            Route::get('faqs', [SellingApiController::class, 'faqs'])->name('selling.api.faqs');
            Route::get('subscription-plans', [SellingApiController::class, 'subscriptionPlans'])->name('selling.api.subscription_plans');
        });

        Route::middleware(['guest', 'xssSanitizer'])->group(function () {
            Route::get('selling/login', [
                SellingLoginController::class, 'showLoginForm',
            ])->name('selling.login');

            Route::get('selling/register/{plan?}', [
                SellingRegisterController::class, 'showRegistrationForm',
            ])->name('selling.register');

            Route::post('selling/register', [
                SellingRegisterController::class, 'register',
            ])->name('selling.register.submit');
        });
    });
