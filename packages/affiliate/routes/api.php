<?php

use Illuminate\Support\Facades\Route;
use Incevio\Package\Affiliate\Http\Controllers\Api\AttributionController;

/*
| Customer app affiliate attribution only.
| Affiliate panel (login / dashboard / links / wallet) remains web-only.
*/
Route::middleware(['api', 'customerApp'])->prefix('api')->group(function () {
    Route::get('affiliate/track/{code}', [AttributionController::class, 'track'])
        ->where('code', '[A-Za-z0-9_-]+');
    Route::post('affiliate/track/{code}', [AttributionController::class, 'track'])
        ->where('code', '[A-Za-z0-9_-]+');
    Route::get('affiliate/attribution', [AttributionController::class, 'current']);
});
