<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\MerchantSwitchToCustomer;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Merchant\VerificationController as MerchantVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'merchantPanel'])->name('merchant.')->prefix('merchant')->group(function () {
    Route::get('switchToCustomer', [
        MerchantSwitchToCustomer::class,
        'switchToCustomer',
    ])->name('switchToCustomer');

    Route::get('createCustomer', [
        MerchantSwitchToCustomer::class,
        'createCustomer',
    ])->name('createCustomer');

    Route::name('account.')->prefix('account')->group(function () {
        include 'admin/Account.php';
        include 'admin/Billing.php';
    });

    Route::middleware(['subscribed', 'checkBillingInfo', 'requireMerchantVerification'])->group(function () {
        Route::get('dashboard', [MerchantDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('dashboard');

        Route::name('admin.')->prefix('admin')->group(function () {
            include 'admin/User.php';
            include 'admin/DeliveryBoy.php';
        });

        Route::namespace('Admin\\Report')->group(function () {
            include 'admin/ShopReport.php';
        });

        Route::name('catalog.')->prefix('catalog')->group(function () {
            include 'admin/Category.php';
            include 'admin/Attribute.php';
            include 'admin/AttributeValues.php';
            include 'admin/Product.php';
            include 'admin/Manufacturer.php';
        });

        Route::middleware('ajax')->group(function () {
            Route::get('catalog/ajax/getParentAttributeType', [
                \App\Http\Controllers\Admin\AttributeController::class,
                'ajaxGetParentAttributeType',
            ])->name('ajax.getParentAttributeType');
        });

        Route::name('stock.')->prefix('stock')->group(function () {
            include 'admin/Inventory.php';
            include 'admin/Warehouse.php';
            include 'admin/InventoryProduct.php';
            include 'admin/Stock.php';
        });

        // Order Routes for Merchant panel (mirrors admin.order.*)
        // Cart routes intentionally omitted — abandoned carts are admin-only
        // to avoid exposing customer cart data to stores.
        Route::name('order.')->prefix('order')->group(function () {
            include 'admin/Order.php';
        });

        // Top-level Refunds module (mirrors admin.refunds.*)
        Route::name('refunds.')->group(function () {
            include 'admin/Refunds.php';
        });

        Route::name('setting.')->prefix('setting')->group(function () {
            include 'admin/UserRole.php';

            Route::put('config/maintenanceMode/{shop}/toggle', [ConfigController::class, 'toggleMaintenanceMode'])
                ->name('config.maintenanceMode.toggle')->middleware('ajax');

            Route::put('config/updateBasicConfig/{shop}', [ConfigController::class, 'updateBasicConfig'])
                ->name('basic.config.update');

            Route::get('general', [ConfigController::class, 'viewGeneralSetting'])
                ->name('config.general');

            // Configurations hub (Order/Storefront/Support/Notifications, incl.
            // the pickup toggle) — mirrors the admin.setting.config.* routes in
            // routes/admin/Config.php, which this group never included.
            Route::get('config', [ConfigController::class, 'view'])
                ->name('config.view');

            Route::get('config/{page}', [ConfigController::class, 'page'])
                ->where('page', 'inventory|order|views|support|websocket|notifications|storefront')
                ->name('config.page');

            Route::put('config/updateConfig/{config}', [ConfigController::class, 'updateConfig'])
                ->name('config.update')->middleware('ajax');

            Route::put('config/notification/{node}/toggle', [ConfigController::class, 'toggleNotification'])
                ->name('config.notification.toggle')->middleware('ajax');

            Route::get('config/updateBankInfo/{config}', [ConfigController::class, 'editBankInfo'])
                ->name('bankInfo.edit')->middleware('ajax');

            Route::put('config/updateBankInfo/{config}', [ConfigController::class, 'updateBankInfo'])
                ->name('bankInfo.update');

            include 'admin/PaymentConfig.php';
        });

        Route::name('support.')->prefix('support')->group(function () {
            // Order conversations — the minimal slice of the admin support-ticket
            // system a seller actually needs (view/reply/start a conversation
            // tied to one of their own orders). Ownership is enforced in
            // MessageController (abortUnlessMine) and CreateMessageRequest, not
            // just by hiding the link — never widen this to the full admin
            // inbox (labels, mass actions, cross-shop message browsing).
            Route::get('message/{order}/conversation', [MessageController::class, 'orderConversation'])
                ->name('orderConversation.create');

            Route::post('message', [MessageController::class, 'store'])
                ->name('message.store');

            Route::get('message/{message}', [MessageController::class, 'show'])
                ->name('message.show');

            Route::get('message/{message}/reply/{template?}', [MessageController::class, 'reply'])
                ->name('message.reply');

            Route::post('message/{message}/storeReply', [MessageController::class, 'storeReply'])
                ->name('message.storeReply');

            if (class_exists(\Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class)) {
                Route::get('chat', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'index',
                ])->name('chat_conversation.index');

                Route::get('chat/{chat}', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'show',
                ])->name('chat_conversation.show');

                Route::post('chat/{chat}/reply', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'reply',
                ])->name('chat_conversation.reply');
            }

            // Dispute tickets (ticket workflow — not chat)
            Route::get('dispute', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'index',
            ])->name('dispute.index');

            Route::get('dispute/create', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'create',
            ])->name('dispute.create');

            Route::post('dispute/order/{order}', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'store',
            ])->name('dispute.store');

            Route::get('dispute/{dispute}', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'show',
            ])->name('dispute.show');

            Route::post('dispute/{dispute}/response', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'response',
            ])->name('dispute.response');

            Route::post('dispute/{dispute}/resolved', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'markResolved',
            ])->name('dispute.resolved');

            Route::post('dispute/{dispute}/request-close', [
                \App\Http\Controllers\Merchant\DisputeController::class,
                'requestClose',
            ])->name('dispute.requestClose');

            // Legacy Support → Refunds redirects (bookmarks / old forms)
            include 'admin/Refund.php';
        });

        Route::name('review.')->prefix('review')->group(function () {
            Route::get('/', [
                \App\Http\Controllers\Merchant\ReviewController::class,
                'index',
            ])->name('index');

            Route::get('{review}', [
                \App\Http\Controllers\Merchant\ReviewController::class,
                'show',
            ])->name('show');

            Route::post('{review}/reply', [
                \App\Http\Controllers\Merchant\ReviewController::class,
                'reply',
            ])->name('reply');

            Route::post('{review}/request-delete', [
                \App\Http\Controllers\Merchant\ReviewController::class,
                'requestDelete',
            ])->name('requestDelete');
        });
    });

    Route::get('verify', [MerchantVerificationController::class, 'index'])->name('verify');
    Route::post('verify', [MerchantVerificationController::class, 'submit'])->name('verify.submit');
    Route::post('verify/location', [MerchantVerificationController::class, 'saveLocation'])->name('verify.location');
    Route::post('verify/phone', [MerchantVerificationController::class, 'savePhone'])->name('verify.phone');
    Route::post('verify/email', [MerchantVerificationController::class, 'saveEmail'])->name('verify.email');
    Route::post('verify/documents', [MerchantVerificationController::class, 'storeDocuments'])->name('verify.documents.store');
    Route::post('verify/documents/{attachment}', [MerchantVerificationController::class, 'replaceDocument'])->name('verify.documents.replace');
    Route::delete('verify/documents/{attachment}', [MerchantVerificationController::class, 'deleteDocument'])->name('verify.documents.delete');
});
