<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PosSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pos/sync/ping', [PosSyncController::class, 'ping'])
    ->middleware(['auth:sanctum', 'ability:pos:sync', 'throttle:pos-sync'])
    ->name('api.pos.sync.ping');

Route::post('/pos/sync', [PosSyncController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:pos:sync', 'throttle:pos-sync'])
    ->name('api.pos.sync');

Route::get('/pos/sync/batch/{sync_id}', [PosSyncController::class, 'batchStatus'])
    ->middleware(['auth:sanctum', 'ability:pos:sync', 'throttle:pos-sync'])
    ->name('api.pos.sync.batch');

Route::post('/pos/sync/reconcile', [PosSyncController::class, 'reconcile'])
    ->middleware(['auth:sanctum', 'ability:pos:sync', 'throttle:pos-sync'])
    ->name('api.pos.sync.reconcile');

Route::post('/pos/sync/rotate-token', [PosSyncController::class, 'rotateToken'])
    ->middleware(['auth:sanctum', 'ability:pos:sync', 'throttle:pos-sync'])
    ->name('api.pos.sync.rotate-token');

Route::prefix('v1')->name('api.v1.')->group(function () {
    // ---- Public storefront catalog ----
    Route::get('/home', [CatalogController::class, 'home']);
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/items', [CatalogController::class, 'items']);
    Route::get('/items/{item}', [CatalogController::class, 'show']);
    Route::get('/delivery-locations', [CatalogController::class, 'deliveryLocations']);
    Route::get('/delivery-settings', [CatalogController::class, 'deliverySettings']);
    Route::get('/payment-options', [CatalogController::class, 'paymentOptions']);

    // ---- Auth ----
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

    // ---- Authenticated storefront ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::delete('/auth/account', [AuthController::class, 'deleteAccount']);

        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'add']);
        Route::patch('/cart/{cartItem}', [CartController::class, 'update']);
        Route::delete('/cart/{cartItem}', [CartController::class, 'remove']);

        Route::get('/checkout', [CheckoutController::class, 'index']);
        Route::post('/checkout', [CheckoutController::class, 'placeOrder']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/complaints', [OrderController::class, 'storeComplaint']);
        Route::get('/orders/{order}/complaints', [OrderController::class, 'complaints']);
        Route::post('/complaints/{complaint}/replies', [OrderController::class, 'replyComplaint']);

        Route::get('/chat', [ChatController::class, 'active']);
        Route::get('/chat/messages', [ChatController::class, 'messages']);
        Route::post('/chat/send', [ChatController::class, 'send']);

        // ---- Admin API ----
        Route::middleware('api.admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
            Route::get('/pos-sync/status', [\App\Http\Controllers\Api\Admin\PosSyncController::class, 'status']);

            Route::get('/orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index']);
            Route::get('/orders/{order}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
            Route::patch('/orders/{order}/status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);
            Route::post('/orders/{order}/reverse', [\App\Http\Controllers\Api\Admin\OrderController::class, 'reverseStatus']);
            Route::post('/orders/{order}/verify-payment', [\App\Http\Controllers\Api\Admin\OrderController::class, 'verifyPayment']);
            Route::post('/orders/{order}/confirm-delivery-fee', [\App\Http\Controllers\Api\Admin\OrderController::class, 'confirmDeliveryFee']);
            Route::post('/orders/{order}/items/{orderItem}/toggle-picked', [\App\Http\Controllers\Api\Admin\OrderController::class, 'toggleItemPicked']);
            Route::post('/orders/bulk', [\App\Http\Controllers\Api\Admin\OrderController::class, 'bulk']);

            Route::get('/items', [\App\Http\Controllers\Api\Admin\ItemController::class, 'index']);
            Route::post('/items', [\App\Http\Controllers\Api\Admin\ItemController::class, 'store']);
            Route::get('/items/{item}', [\App\Http\Controllers\Api\Admin\ItemController::class, 'show']);
            Route::put('/items/{item}', [\App\Http\Controllers\Api\Admin\ItemController::class, 'update']);
            Route::delete('/items/{item}', [\App\Http\Controllers\Api\Admin\ItemController::class, 'destroy']);
            Route::post('/items/bulk', [\App\Http\Controllers\Api\Admin\ItemController::class, 'bulkAction']);
            Route::post('/items/bulk-update-quantities', [\App\Http\Controllers\Api\Admin\ItemController::class, 'bulkUpdateQuantities']);

            Route::get('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);
            Route::post('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'store']);
            Route::get('/categories/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'show']);
            Route::put('/categories/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'destroy']);
            Route::post('/categories/bulk', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'bulk']);

            Route::get('/revenue', [\App\Http\Controllers\Api\Admin\RevenueController::class, 'index']);

            Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
            Route::post('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
            Route::get('/users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
            Route::put('/users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
            Route::delete('/users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Api\Admin\UserController::class, 'sendResetLink']);
            Route::post('/users/{user}/recover', [\App\Http\Controllers\Api\Admin\UserController::class, 'recover']);
            Route::post('/users/bulk', [\App\Http\Controllers\Api\Admin\UserController::class, 'bulk']);

            Route::get('/complaints', [\App\Http\Controllers\Api\Admin\ComplaintController::class, 'index']);
            Route::get('/complaints/{complaint}', [\App\Http\Controllers\Api\Admin\ComplaintController::class, 'show']);
            Route::patch('/complaints/{complaint}/status', [\App\Http\Controllers\Api\Admin\ComplaintController::class, 'updateStatus']);
            Route::post('/complaints/{complaint}/replies', [\App\Http\Controllers\Api\Admin\ComplaintController::class, 'reply']);
            Route::post('/complaints/bulk', [\App\Http\Controllers\Api\Admin\ComplaintController::class, 'bulk']);

            Route::get('/sync', [\App\Http\Controllers\Api\Admin\SyncController::class, 'index']);
            Route::post('/sync/trigger', [\App\Http\Controllers\Api\Admin\SyncController::class, 'trigger']);
            Route::post('/sync/{log}/cancel', [\App\Http\Controllers\Api\Admin\SyncController::class, 'cancel']);
            Route::get('/sync/{log}', [\App\Http\Controllers\Api\Admin\SyncController::class, 'status']);

            Route::get('/delivery-locations', [\App\Http\Controllers\Api\Admin\DeliveryLocationController::class, 'index']);
            Route::post('/delivery-locations', [\App\Http\Controllers\Api\Admin\DeliveryLocationController::class, 'store']);
            Route::put('/delivery-locations/{deliveryLocation}', [\App\Http\Controllers\Api\Admin\DeliveryLocationController::class, 'update']);
            Route::delete('/delivery-locations/{deliveryLocation}', [\App\Http\Controllers\Api\Admin\DeliveryLocationController::class, 'destroy']);
            Route::post('/delivery-locations/bulk', [\App\Http\Controllers\Api\Admin\DeliveryLocationController::class, 'bulk']);

            Route::get('/bank-accounts', [\App\Http\Controllers\Api\Admin\BankAccountController::class, 'index']);
            Route::post('/bank-accounts', [\App\Http\Controllers\Api\Admin\BankAccountController::class, 'store']);
            Route::put('/bank-accounts/{bankAccount}', [\App\Http\Controllers\Api\Admin\BankAccountController::class, 'update']);
            Route::delete('/bank-accounts/{bankAccount}', [\App\Http\Controllers\Api\Admin\BankAccountController::class, 'destroy']);
            Route::post('/bank-accounts/bulk', [\App\Http\Controllers\Api\Admin\BankAccountController::class, 'bulk']);

            Route::get('/settings', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'index']);
            Route::put('/settings/payment', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'updatePayment']);
            Route::put('/settings/delivery', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'updateDelivery']);

            Route::get('/discount-campaigns', [\App\Http\Controllers\Api\Admin\DiscountCampaignController::class, 'index']);
            Route::get('/discount-campaigns/eligible-items', [\App\Http\Controllers\Api\Admin\DiscountCampaignController::class, 'eligibleItems']);
            Route::post('/discount-campaigns', [\App\Http\Controllers\Api\Admin\DiscountCampaignController::class, 'store']);
            Route::get('/discount-campaigns/{campaign}', [\App\Http\Controllers\Api\Admin\DiscountCampaignController::class, 'show']);
            Route::put('/discount-campaigns/{campaign}', [\App\Http\Controllers\Api\Admin\DiscountCampaignController::class, 'update']);
            Route::delete('/discount-campaigns/{campaign}', [\App\Http\Controllers\Api\Admin\DiscountCampaignController::class, 'destroy']);

            Route::get('/chat', [\App\Http\Controllers\Api\Admin\ChatController::class, 'index']);
            Route::get('/chat/quick-responses', [\App\Http\Controllers\Api\Admin\ChatController::class, 'index']);
            Route::post('/chat/quick-responses', [\App\Http\Controllers\Api\Admin\ChatController::class, 'storeQuickResponse']);
            Route::delete('/chat/quick-responses/{quickResponse}', [\App\Http\Controllers\Api\Admin\ChatController::class, 'destroyQuickResponse']);
            Route::get('/chat/{chat}', [\App\Http\Controllers\Api\Admin\ChatController::class, 'show']);
            Route::post('/chat/{chat}/send', [\App\Http\Controllers\Api\Admin\ChatController::class, 'reply']);
        });
    });
});
