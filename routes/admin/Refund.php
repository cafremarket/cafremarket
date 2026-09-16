<?php

use App\Http\Controllers\Admin\RefundController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy Support → Refunds URLs (admin.support.refund.*)
| Keep bookmarks, emails, and old forms working by redirecting to the
| top-level admin.refunds.* module. POST initiate stays on this path so
| old forms do not lose their request body on redirect.
|--------------------------------------------------------------------------
*/

Route::get('refund', function () {
    return redirect()->route('admin.refunds.index', request()->query());
})->name('refund.index');

Route::get('refund/initiate/{order?}', function ($order = null) {
    return redirect()->route('admin.refunds.form', array_filter([
        'order' => $order,
    ] + request()->query()));
})->name('refund.form');

Route::post('refund/initiate', [RefundController::class, 'initiate'])->name('refund.initiate');

Route::get('refund/{refund}/response', function ($refund) {
    return redirect()->route('admin.refunds.response', $refund);
})->name('refund.response');

Route::get('refund/{refund}/approve', function ($refund) {
    return redirect()->route('admin.refunds.approve', $refund);
})->name('refund.approve');

Route::match(['get', 'post'], 'refund/{refund}/decline', function ($refund) {
    if (request()->isMethod('post')) {
        return app(RefundController::class)->decline(request(), $refund);
    }

    return redirect()->route('admin.refunds.decline', $refund);
})->name('refund.decline');

Route::post('refund/{refund}/mark-issue', [RefundController::class, 'markIssue'])
    ->name('refund.markIssue');
