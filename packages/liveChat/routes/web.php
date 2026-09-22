<?php

use Illuminate\Support\Facades\Route;
use Incevio\Package\LiveChat\Http\Controllers\AdminChatController;
use Incevio\Package\LiveChat\Http\Controllers\ChatController;
use Incevio\Package\LiveChat\Http\Controllers\CustomerChatController;

Route::middleware(['web', 'xssSanitizer'])->group(function () {

    Route::middleware(['ajax', 'storefront'])->name('chat.')->group(function () {
        // /chat/{shop} — shop slug preferred (id also works).
        // "chat" is reserved in routes/web.php so /{category}/{subcategory} cannot steal this.
        Route::get('chat/{shop}', [
            ChatController::class, 'conversation',
        ])->name('conversation');

        Route::post('chat', [
            ChatController::class, 'save',
        ])->name('start');
    });

    // Customer dashboard multi-seller inbox (mirrors app MessagesScreen)
    Route::middleware(['auth:customer'])
        ->name('customer.chat.')
        ->prefix('my/chat')
        ->group(function () {
            Route::get('{chat}', [
                CustomerChatController::class, 'show',
            ])->name('show');

            Route::post('{chat}/reply', [
                CustomerChatController::class, 'reply',
            ])->name('reply');
        });

    //admin suport chat route
    Route::middleware(['auth', 'subscribed', 'checkBillingInfo'])
        ->name('admin.support.')->prefix('admin/support')->group(function () {
            Route::get('chat', [
                AdminChatController::class, 'index'
            ])->name('chat_conversation.index');

            Route::get('chat/{chat}', [
                AdminChatController::class, 'show'
            ])->name('chat_conversation.show');

            Route::post('chat/{chat}/reply', [
                AdminChatController::class, 'reply'
            ])->name('chat_conversation.reply');

            // Route::delete('chat/{chat}/trash', [AdminChatController::class, 'trash'])->name('chat_conversation.trash'); // Chat move to trash

            // Route::post('chat/{chat}/restore', [AdminChatController::class, 'restore'])->name('chat_conversation.restore');

            // Route::delete('chat/{chat}/destroy', [AdminChatController::class, 'destroy'])->name('chat_conversation.destroy');
        });
});
