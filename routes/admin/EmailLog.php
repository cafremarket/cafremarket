<?php

use App\Http\Controllers\Admin\EmailLogController;
use Illuminate\Support\Facades\Route;

Route::get('emailLog', [EmailLogController::class, 'index'])->name('emailLog.index');
Route::delete('emailLog/clear', [EmailLogController::class, 'clear'])->name('emailLog.clear');
Route::get('emailLog/{emailLog}', [EmailLogController::class, 'show'])->name('emailLog.show');
Route::delete('emailLog/{emailLog}', [EmailLogController::class, 'destroy'])->name('emailLog.destroy');
