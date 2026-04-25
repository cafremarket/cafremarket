<?php

use Illuminate\Support\Facades\Route;

// eMola async callback (JSON)
// Note: routes/api.php wraps routes in Route::namespace('Api'), so we must use an absolute class name.
Route::post('emola/callback', '\\App\\Http\\Controllers\\Api\\EmolaCallbackController');

