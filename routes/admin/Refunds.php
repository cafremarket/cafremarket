<?php

use App\Http\Controllers\Admin\RefundController;
use Illuminate\Support\Facades\Route;

// Top-level Refunds module
Route::get('refunds', [RefundController::class, 'index'])->name('index');

Route::get('refunds/initiate/{order?}', [RefundController::class, 'showRefundForm'])->name('form');

Route::post('refunds/initiate', [RefundController::class, 'initiate'])->name('initiate');

Route::get('refunds/{refund}/response', [RefundController::class, 'response'])->name('response');

Route::get('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('approve');

Route::match(['get', 'post'], 'refunds/{refund}/decline', [RefundController::class, 'decline'])->name('decline');

Route::post('refunds/{refund}/mark-issue', [RefundController::class, 'markIssue'])->name('markIssue');
