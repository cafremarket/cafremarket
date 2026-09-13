<?php

use App\Http\Controllers\Storefront\FeedbackController;
use App\Http\Controllers\Storefront\ReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['xssSanitizer'])->group(function () {
    Route::get('order/feedback/{order}', [
        FeedbackController::class, 'feedback_form',
    ])->name('order.feedback');

    Route::post('order/feedback/{order}', [
        FeedbackController::class, 'save_product_feedbacks',
    ])->name('save.feedback');

    Route::post('shop/feedback/{order}', [
        FeedbackController::class, 'save_shop_feedbacks',
    ])->name('shop.feedback');

    // Write/edit a review directly from the product/shop page (any past qualifying
    // purchase, not limited to a specific order).
    Route::post('listing/{slug}/review', [
        ReviewController::class, 'storeProductReview',
    ])->name('listing.review.store');

    Route::post('shop/{slug}/review', [
        ReviewController::class, 'storeShopReview',
    ])->name('shop.review.store');
});
