<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\MerchantSwitchToCustomer;
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

    include 'admin/FlashDeal.php';

    Route::middleware(['subscribed', 'checkBillingInfo', 'requireMerchantVerification'])->group(function () {
        Route::get('dashboard', [MerchantDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('dashboard');

        Route::put('dashboard/config/{node}/toggle', [
            Admin\DashboardController::class,
            'toggleConfig',
        ])->name('dashboard.config.toggle')->middleware('ajax');

        include 'admin/Notification.php';

        Route::name('admin.')->prefix('admin')->group(function () {
            include 'admin/User.php';
            include 'admin/Customer.php';
            include 'admin/DeliveryBoy.php';
        });

        Route::namespace('Admin\\Report')->group(function () {
            include 'admin/ShopReport.php';
        });

        Route::name('catalog.')->prefix('catalog')->group(function () {
            include 'admin/CategoryGroup.php';
            include 'admin/CategorySubGroup.php';
            include 'admin/Category.php';
            include 'admin/Product.php';
            include 'admin/Attribute.php';
            include 'admin/AttributeValues.php';
            include 'admin/Manufacturer.php';
        });

        Route::name('stock.')->prefix('stock')->group(function () {
            include 'admin/Inventory.php';
            include 'admin/Warehouse.php';
            include 'admin/InventoryProduct.php';
            include 'admin/Supplier.php';
        });

        Route::name('order.')->prefix('order')->group(function () {
            include 'admin/Order.php';
            include 'admin/Cart.php';
        });

        Route::name('utility.')->prefix('utility')->group(function () {
            include 'admin/EmailTemplate.php';
            include 'admin/EmailLog.php';
            include 'admin/PdfTemplate.php';
            include 'admin/Faq.php';
            include 'admin/Page.php';
            include 'admin/Blog.php';
        });

        Route::name('setting.')->prefix('setting')->group(function () {
            include 'admin/UserRole.php';
            include 'admin/Tax.php';
            include 'admin/Config.php';
            include 'admin/PaymentConfig.php';
        });

        Route::name('appearance.')->prefix('appearance')->group(function () {
            include 'admin/Banner.php';
            include 'admin/Slider.php';
        });

        Route::name('promotion.')->prefix('promotion')->group(function () {
            include 'admin/Coupon.php';
            include 'admin/GiftCard.php';
            include 'admin/PushCampaign.php';
        });

        Route::name('support.')->prefix('support')->group(function () {
            include 'admin/Message.php';
            include 'admin/Ticket.php';
            include 'admin/Dispute.php';
            include 'admin/Refund.php';

            if (class_exists(\Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class)) {
                Route::get('chat', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'index',
                ])->name('chat.index');

                Route::get('chat/{chat}', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'show',
                ])->name('chat.show');

                Route::post('chat/{chat}/reply', [
                    \Incevio\Package\LiveChat\Http\Controllers\AdminChatController::class,
                    'reply',
                ])->name('chat.reply');
            }
        });

        Route::middleware('ajax')->group(function () {
            Route::get('catalog/ajax/getParentAttributeType', [
                Admin\AttributeController::class,
                'ajaxGetParentAttributeType',
            ])->name('ajax.getParentAttributeType');
        });
    });

    // Verification lives outside the verification gate so new sellers can complete onboarding.
    Route::get('verify', [MerchantVerificationController::class, 'index'])->name('verify');
    Route::post('verify', [MerchantVerificationController::class, 'submit'])->name('verify.submit');
    Route::post('verify/location', [MerchantVerificationController::class, 'saveLocation'])->name('verify.location');
    Route::post('verify/phone', [MerchantVerificationController::class, 'savePhone'])->name('verify.phone');
    Route::post('verify/email', [MerchantVerificationController::class, 'saveEmail'])->name('verify.email');
    Route::post('verify/documents', [MerchantVerificationController::class, 'storeDocuments'])->name('verify.documents.store');
    Route::post('verify/documents/{attachment}', [MerchantVerificationController::class, 'replaceDocument'])->name('verify.documents.replace');
    Route::delete('verify/documents/{attachment}', [MerchantVerificationController::class, 'deleteDocument'])->name('verify.documents.delete');
});
