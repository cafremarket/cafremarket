<?php

use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ReviewDeleteRequestController;
use Illuminate\Support\Facades\Route;

// Delete-request routes must be registered before review/{review} so "delete-requests"
// isn't swallowed by the {review} wildcard.
Route::get('review/delete-requests', [ReviewDeleteRequestController::class, 'index'])->name('review.deleteRequests');
Route::get('review/delete-requests/{deleteRequest}', [ReviewDeleteRequestController::class, 'show'])->name('review.deleteRequests.show');
Route::post('review/delete-requests/{deleteRequest}/approve', [ReviewDeleteRequestController::class, 'approve'])->name('review.deleteRequests.approve');
Route::post('review/delete-requests/{deleteRequest}/reject', [ReviewDeleteRequestController::class, 'reject'])->name('review.deleteRequests.reject');

Route::get('review', [ReviewController::class, 'index'])->name('review.index');
Route::get('review/{review}', [ReviewController::class, 'show'])->name('review.show');
Route::delete('review/{review}', [ReviewController::class, 'destroy'])->name('review.destroy');
