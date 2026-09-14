<?php

use App\Http\Controllers\Admin\PolicyPageController;
use Illuminate\Support\Facades\Route;

Route::get('policy-page', [PolicyPageController::class, 'index'])->name('policyPage.index');
Route::post('policy-page/apply-all-defaults', [PolicyPageController::class, 'applyAllDefaults'])->name('policyPage.applyAllDefaults')->middleware('demoCheck');
Route::get('policy-page/{slug}/edit', [PolicyPageController::class, 'edit'])->name('policyPage.edit');
Route::post('policy-page/{slug}/apply-defaults', [PolicyPageController::class, 'applyDefaults'])->name('policyPage.applyDefaults')->middleware('demoCheck');
Route::put('policy-page/{slug}', [PolicyPageController::class, 'update'])->name('policyPage.update')->middleware('demoCheck');
