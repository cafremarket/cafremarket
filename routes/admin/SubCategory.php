<?php

use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\SubCategoryTranslationController;
use Illuminate\Support\Facades\Route;

Route::delete('subcategory/{subcategory}/trash', [SubCategoryController::class, 'trash'])
    ->name('subcategory.trash');

Route::post('subcategory/massTrash', [SubCategoryController::class, 'massTrash'])
    ->name('subcategory.massTrash');

Route::post('subcategory/massDestroy', [SubCategoryController::class, 'massDestroy'])
    ->name('subcategory.massDestroy');

Route::delete('subcategory/emptyTrash', [SubCategoryController::class, 'emptyTrash'])
    ->name('subcategory.emptyTrash');

Route::get('subcategory/{subcategory}/restore', [SubCategoryController::class, 'restore'])
    ->name('subcategory.restore');

Route::get('subcategory/{subcategory}/products', [SubCategoryController::class, 'show'])
    ->name('subcategory.show');

Route::resource('subcategory', SubCategoryController::class)->except('show');

// Translation routes
Route::get('subcategory/translate/{subCategory}/{language}', [
    SubCategoryTranslationController::class, 'showTranslationForm',
])->name('subcategory.translate.form');

Route::post('subcategory/translate/{subCategory}', [
    SubCategoryTranslationController::class, 'storeTranslation',
])->name('subcategory.translate.store');

Route::get('subcategory/translate/bulk', [
    SubCategoryTranslationController::class, 'showBulkUploadForm',
])->name('subcategory.translate.bulk')->middleware('ajax');

Route::post('subcategory/translate/bulk/upload', [
    SubCategoryTranslationController::class, 'uploadBulkTranslation',
])->name('subcategory.translate.bulk.upload');

Route::post('subcategory/translate/bulk/import', [
    SubCategoryTranslationController::class, 'importBulkTranslation',
])->name('subcategory.translate.bulk.import');

Route::get('subcategory/translation/download/failedRows', [
    SubCategoryTranslationController::class, 'downloadFailedRows',
])->name('subcategory.translate.download.failedRows');

Route::get('subcategory/translation/downloadTemplate', [
    SubCategoryTranslationController::class, 'downloadTemplate',
])->name('subcategory.translate.download.template');
