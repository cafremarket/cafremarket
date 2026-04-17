<?php

use Illuminate\Support\Facades\Route;
use Incevio\Package\Affiliate\Console\Commands\ReleaseCommissions;
use Incevio\Package\Affiliate\Http\Controllers\FrontController;
use Incevio\Package\Affiliate\Http\Controllers\LoginController;
use Incevio\Package\Wallet\Http\Controllers\WithdrawalController;
use Incevio\Package\Affiliate\Http\Controllers\RegisterController;
use Incevio\Package\Affiliate\Http\Controllers\AccountController;
use Incevio\Package\Affiliate\Http\Controllers\AffiliateController;
use Incevio\Package\Affiliate\Http\Controllers\DashboardController;
use Incevio\Package\Affiliate\Http\Controllers\AffiliateLinkController;
use Incevio\Package\Affiliate\Http\Controllers\ChartDataController;
use Incevio\Package\Affiliate\Http\Controllers\CommissionController;

// Admin Routes
Route::prefix('admin/affiliate')->name('admin.affiliate.')
    ->middleware(['web', 'auth:web', 'userType:admin'])
    ->group(function () {
        Route::get('/show/{affiliate}', [
            AffiliateController::class, 'show'
        ])->name('show')->middleware('ajax');
        
        Route::get('/', [
            AffiliateController::class, 'index'
        ])->name('index');

        Route::get('/affiliateLinks/{affiliate}', [
            AffiliateController::class, 'getAffiliateLinks'
        ])->name('links');
        
        Route::get('show/{affiliate}', [
            AffiliateController::class, 'show'
        ])->name('show')->middleware('ajax');

        Route::get('create',[
            AffiliateController::class, 'create'
        ])->name('create');

        Route::post('store',[
            AffiliateController::class, 'store'
        ])->name('store');

        Route::get('/edit/{affiliate}', [
            AffiliateController::class, 'edit'
        ])->name('edit')->middleware('ajax');

        Route::put('/update/{affiliate}',[
            AffiliateController::class, 'update'
        ])->name('update');
        
        Route::delete('/delete/{affiliate}', [
            AffiliateController::class, 'destroy'
        ])->name('destroy');

        Route::get('getAffiliates', [
            AffiliateController::class, 'getAffiliates'
        ])->name('getAffiliates')->middleware('ajax');

        Route::get('passwordForm/{affiliate}', [
            AccountController::class, 'showChangePasswordForm'
        ])->name('passwordForm')->middleware('ajax');

        Route::put('password/{affiliate}', [
            AccountController::class, 'updatePassword'
        ])->name('password.update');

        Route::put('release/{commission}', [
            CommissionController::class, 'releaseCommission'
        ])->name('commission.release');

        Route::get('commissions', [
            CommissionController::class, 'index'
        ])->name('commissions');
    });

// Web Routes
Route::prefix('affiliate')->name('affiliate.')->middleware('web')->group(function () {
    // Affiliate Authentication routes
    Route::get('register', [
        RegisterController::class, 'showRegistrationForm'
    ])->name('register.form');

    Route::post('register', [
        RegisterController::class, 'register'
    ])->name('register');

    Route::get('login', [
        LoginController::class, 'showLoginForm'
    ])->name('login.form');

    Route::post('login', [
        LoginController::class, 'login'
    ])->name('login');

    Route::get('form/validate', [
        AccountController::class, 'userNameExists'
    ])->name('form.validate')->middleware('ajax');

    Route::middleware('auth:affiliate')->group(function () {
        Route::get('dashboard', [
            DashboardController::class, 'index'
        ])->name('dashboard');
        
        Route::get('commissions', [
            DashboardController::class, 'showCommissions'
        ])->name('commissions');
        
        Route::get('profile', [
            AccountController::class, 'profile'
        ])->name('profile');

        Route::get('profile/passwordForm', [
            AccountController::class, 'showChangePasswordForm'
        ])->name('profile.passwordForm')->middleware('ajax');
        
        Route::put('profile/password/{affiliate}', [
            AccountController::class, 'updatePassword'
        ])->name('profile.password.update');

        Route::put('profile/update/{affiliate}', [
            AccountController::class, 'update'
        ])->name('profile.update');

        Route::get('wallet', [
            DashboardController::class, 'wallet'
        ])->name('wallet');

        Route::get('wallet/withdraw', [
            WithdrawalController::class, 'form'
        ])->name('wallet.withdrawal');

        Route::post('wallet/withdraw', [
            WithdrawalController::class, 'withdraw'
        ])->name('wallet.withdraw');

        Route::get('chartData/commission/link', [
            ChartDataController::class, 'getCommissionByLink'
        ])->name('chartData.commission.link')->middleware('ajax');
        
        Route::get('chartData/commission/shop', [
            ChartDataController::class, 'getCommissionByShop'
        ])->name('chartData.commission.shop')->middleware('ajax');

        Route::get('chartData/visitor/link', [
            ChartDataController::class, 'getVisitorByLink'
        ])->name('chartData.visitor.link')->middleware('ajax');

        // Affiliate Links Routes
        Route::resource('link', AffiliateLinkController::class)->except('show', 'create', 'store');
        
        Route::get('link/{link}/commissions', [
            AffiliateLinkController::class, 'showLinkCommissions'
        ])->name('link.commissions');

        Route::post('{inventory}/create', [
            AffiliateLinkController::class, 'store'
        ])->name('link.store');

        Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    });
});

// Public Affiliate Link Routes
Route::get('visit/{affiliate}/{slug}', [
    FrontController::class, 'visit'
])->name('affiliate.link')->middleware('web');
