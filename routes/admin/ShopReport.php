<?php

use App\Http\Controllers\Admin\Report\ShopEarningsReportController;
use App\Http\Controllers\Admin\Report\ShopPerformanceIndicatorsController;
use Illuminate\Support\Facades\Route;

// Metrics / Key Performance Indicators...
Route::get('shop/report/kpi', [ShopPerformanceIndicatorsController::class, 'all'])->name('shop-kpi');

Route::get('shop/report/kpi/revenue', [ShopPerformanceIndicatorsController::class, 'revenue'])->name('shop-kpi.revenue');

// Earnings & marketplace commission for the merchant's own shop
Route::get('shop/report/earnings', [ShopEarningsReportController::class, 'index'])->name('shop-earnings');

Route::get('shop/report/earnings/export/{type}', [ShopEarningsReportController::class, 'export'])->name('shop-earnings.export');
