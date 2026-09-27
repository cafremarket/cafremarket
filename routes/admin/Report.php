<?php

use App\Http\Controllers\Admin\Report\CronJobController;
use App\Http\Controllers\Admin\Report\MarketplaceReportController;
use App\Http\Controllers\Admin\Report\PerformanceIndicatorsController;
use App\Http\Controllers\Admin\Report\SalesReportController;
use App\Http\Controllers\Admin\Report\VerificationReportController;
use Illuminate\Support\Facades\Route;

// Report hub: overview, commission, vendors, customers, refunds (+ CSV export)
Route::get('report/overview', [MarketplaceReportController::class, 'overview'])->name('report.overview');

Route::get('report/commission', [MarketplaceReportController::class, 'commission'])->name('report.commission');

Route::get('report/vendors', [MarketplaceReportController::class, 'vendors'])->name('report.vendors');

Route::get('report/customers', [MarketplaceReportController::class, 'customers'])->name('report.customers');

Route::get('report/refunds', [MarketplaceReportController::class, 'refunds'])->name('report.refunds');

Route::get('report/export/{type}', [MarketplaceReportController::class, 'export'])->name('report.export');

// Metrics / Key Performance Indicators...
Route::get('report/kpi', [PerformanceIndicatorsController::class, 'all'])->name('kpi');

Route::get('report/kpi/revenue', [PerformanceIndicatorsController::class, 'revenue'])->name('kpi.revenue');

Route::get('report/kpi/plans', [PerformanceIndicatorsController::class, 'subscribers'])->name('kpi.plans');

Route::get('report/kpi/trialing', [PerformanceIndicatorsController::class, 'trialUsers'])->name('kpi.trialing');

// Sales report
// Order Wise Report
Route::get('report/sales/orders', [SalesReportController::class, 'orders'])->name('sales.orders');

Route::get('report/sales/getMore', [SalesReportController::class, 'getMoreOrder'])->name('sales.getMore')->middleware('ajax');

Route::get('report/sales/getMoreForChart', [SalesReportController::class, 'getMoreForChart'])->name('sales.getMoreForChart')->middleware('ajax');

// #Paymnet Wise Report
Route::get('report/sales/payments', [SalesReportController::class, 'payments'])->name('sales.payments');

Route::get('report/sales/payments/getMethod', [SalesReportController::class, 'getMoreByMethod'])->name('sales.payments.getMethod');

Route::get('report/sales/payments/getStatus', [SalesReportController::class, 'getMoreByStatus'])->name('sales.payments.getStatus');

Route::get('report/sales/payments/getMoreForChart', [SalesReportController::class, 'getMorePaymentForChart'])->name('sales.payments.getMoreForChart');

Route::get('report/sales/payments/getMore', [SalesReportController::class, 'getMorePayments'])->name('sales.payments.getMore')->middleware('ajax');

// #Product Wise Report
Route::get('report/sales/products', [SalesReportController::class, 'products'])->name('sales.products');

Route::get('report/sales/products/getMore', [SalesReportController::class, 'productsSearch'])->name('sales.products.getMore');

// Scheduler health and the log of every cron (scheduled task) run
Route::get('report/cron', [CronJobController::class, 'index'])->name('report.cron');

Route::post('report/cron/run', [CronJobController::class, 'run'])->name('report.cron.run');

// Anti-fake-account checks: email/phone verification, reCAPTCHA, mail health
Route::get('report/verification', [VerificationReportController::class, 'index'])->name('report.verification');
