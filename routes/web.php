<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ItemReviewController;
use App\Http\Controllers\Admin\VariationDuplicatesController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\PosSyncDashboardController;
use App\Http\Controllers\Admin\DeliveryLocationController;
use App\Http\Controllers\Admin\DeliverySettingsController;
use App\Http\Controllers\Admin\SlideshowImageController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\ComplaintController as AdminComplaintController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\DiscountCampaignController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ComplaintController as CustomerComplaintController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PriceCheckerController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Language switching
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Static pages
Route::view('/about', 'pages.about')->name('about');
Route::view('/privacy-policy', 'pages.privacy')->name('privacy-policy');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.send');
Route::view('/faqs', 'pages.faqs')->name('faqs');

// Shop routes
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');

// Cart routes
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf.token');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
Route::get('/cart/items', [CartController::class, 'items'])->name('cart.items');

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
Route::get('/order/{id}/confirmation', [CheckoutController::class, 'confirmation'])->name('order.confirm');

// Favorite routes (authenticated users)
Route::post('/favorites/{item}/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle')->middleware('auth');

// Delivery locations API (public for checkout)
Route::get('/api/delivery-locations', [DeliveryLocationController::class, 'activeLocations'])->name('api.delivery-locations');

// Live search API (public)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/api/search', [SearchController::class, 'search'])->name('api.search');

    // Price Checker
    Route::get('/price-checker', [PriceCheckerController::class, 'index'])->name('price-checker.index');
    Route::get('/api/price-checker/search', [PriceCheckerController::class, 'search'])->name('api.price-checker.search');
});

// Push subscription routes (authenticated)
Route::middleware(['auth', 'throttle:10,1'])->prefix('push')->name('push.')->group(function () {
    Route::post('/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::post('/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('unsubscribe');
});

Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey'])->name('push.public-key');

// Payment init routes (require an authenticated, verified customer account)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment/paystack/{order}', [PaymentController::class, 'initPaystack'])->name('payment.paystack.init');
    Route::get('/payment/flutterwave/{order}', [PaymentController::class, 'initFlutterwave'])->name('payment.flutterwave.init');
});

// Payment callback routes (public, hit by payment gateways)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/payment/paystack/callback', [PaymentController::class, 'paystackCallback'])->name('payment.paystack.callback');
    Route::get('/payment/flutterwave/callback', [PaymentController::class, 'flutterwaveCallback'])->name('payment.flutterwave.callback');
});

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:3,1');

// Email verification routes (must be logged in)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Password Reset routes
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:3,1');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:3,1');

// Admin routes (protected by admin middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

    // Live search for the current admin page context
    Route::get('/api/search', [AdminSearchController::class, 'search'])->name('search');

    // Denied page
    Route::get('/denied', function () {
        return view('admin.denied');
    })->name('denied');

    // Revenue
    Route::middleware('can:view_revenue')->group(function () {
        Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');
        Route::post('/revenue/export-bulk', [RevenueController::class, 'exportBulk'])->name('revenue.export-bulk');
        Route::post('/revenue/bulk', [RevenueController::class, 'bulk'])->name('revenue.bulk');
    });

    // Profile
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');

    // Items
    Route::middleware('can:modify_items')->group(function () {
        Route::get('/items', [ItemController::class, 'index'])->name('items.index');
        Route::get('/items/missing-images', [ItemController::class, 'missingImages'])->name('items.missing-images');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::post('/items/bulk', [ItemController::class, 'bulkAction'])->name('items.bulk');
        Route::get('/items/variations/manage', [ItemController::class, 'variationsPage'])->name('items.variations.manage');
        Route::get('/items/variations', [ItemController::class, 'variations'])->name('items.variations');
        Route::get('/items/variations/duplicates', [VariationDuplicatesController::class, 'index'])->name('items.variations.duplicates');
        Route::get('/items/variations/duplicates/data', [VariationDuplicatesController::class, 'data'])->name('items.variations.duplicates.data');
        Route::post('/items/variations/duplicates/merge', [VariationDuplicatesController::class, 'merge'])->name('items.variations.duplicates.merge');
        Route::post('/items/variations/duplicates/{item}/hide', [VariationDuplicatesController::class, 'hide'])->name('items.variations.duplicates.hide');
        Route::post('/items/variations/duplicates/{item}/unhide', [VariationDuplicatesController::class, 'unhide'])->name('items.variations.duplicates.unhide');
        Route::get('/items/reviews', [ItemReviewController::class, 'index'])->name('items.reviews');
        Route::get('/items/reviews/data', [ItemReviewController::class, 'data'])->name('items.reviews.data');
        Route::get('/items/reviews/parents', [ItemReviewController::class, 'parents'])->name('items.reviews.parents');
        Route::put('/items/reviews/{item}', [ItemReviewController::class, 'update'])->name('items.reviews.update');
        Route::post('/items/bulk-update-quantities', [ItemController::class, 'bulkUpdateQuantities'])->name('items.bulk-update-quantities');
        Route::post('/items/upload-image', [ItemController::class, 'uploadImage'])->name('items.upload-image');
        Route::post('/items/{item}/toggle-favorite', [FavoriteController::class, 'toggleAdminFavorite'])->name('items.toggle-favorite');
    });

    // Categories
    Route::middleware('can:manage_categories')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('/categories/bulk', [CategoryController::class, 'bulk'])->name('categories.bulk');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{category}/images', [CategoryController::class, 'uploadImage'])->name('categories.images.upload');
        Route::delete('/categories/images/{image}', [CategoryController::class, 'deleteImage'])->name('categories.images.delete');
        Route::put('/categories/images/{image}/primary', [CategoryController::class, 'setPrimaryImage'])->name('categories.images.primary');
    });

    // Shop Slideshow Images
    Route::middleware('can:manage_categories')->group(function () {
        Route::get('/slideshow-images', [SlideshowImageController::class, 'index'])->name('slideshow-images.index');
        Route::get('/slideshow-images/list', [SlideshowImageController::class, 'list'])->name('slideshow-images.list');
        Route::post('/slideshow-images/upload', [SlideshowImageController::class, 'upload'])->name('slideshow-images.upload');
        Route::post('/slideshow-images/{slideshowImage}/toggle', [SlideshowImageController::class, 'toggle'])->name('slideshow-images.toggle');
        Route::post('/slideshow-images/reorder', [SlideshowImageController::class, 'reorder'])->name('slideshow-images.reorder');
        Route::delete('/slideshow-images/{slideshowImage}', [SlideshowImageController::class, 'destroy'])->name('slideshow-images.destroy');
    });

    // Payment Settings
    Route::middleware('can:manage_payment_settings')->group(function () {
        Route::get('/payment-settings', [PaymentSettingController::class, 'index'])->name('payment-settings.index');
        Route::get('/payment-settings/edit', [PaymentSettingController::class, 'edit'])->name('payment-settings.edit');
        Route::put('/payment-settings/update', [PaymentSettingController::class, 'update'])->name('payment-settings.update');
    });

    // Orders
    Route::middleware('can:process_orders')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/live', [OrderController::class, 'live'])->name('orders.live');
        Route::post('/orders/bulk', [OrderController::class, 'bulk'])->name('orders.bulk');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::put('/orders/{order}/reverse-status', [OrderController::class, 'reverseStatus'])->name('orders.reverse-status');
        Route::put('/orders/{order}/verify-payment', [OrderController::class, 'verifyPayment'])->name('orders.verify-payment');
        Route::get('/orders/{order}/receipt/bank', [OrderController::class, 'viewBankReceipt'])->name('orders.receipt.bank');
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
        Route::get('/orders/{order}/receipt/download', [OrderController::class, 'downloadReceipt'])->name('orders.receipt.download');
        Route::post('/orders/{order}/email-receipt', [OrderController::class, 'emailReceipt'])->name('orders.email-receipt');
        Route::put('/orders/{order}/confirm-delivery-fee', [OrderController::class, 'confirmDeliveryFee'])->name('orders.confirm-delivery-fee');
        Route::post('/orders/{order}/items/{orderItem}/toggle-picked', [OrderController::class, 'toggleItemPicked'])->name('orders.items.toggle-picked');
    });

    // Sync
    Route::middleware('can:view_sync')->group(function () {
        Route::get('/sync', [SyncController::class, 'index'])->name('sync.index');
        Route::post('/sync/trigger', [SyncController::class, 'trigger'])->name('sync.trigger');
        Route::post('/sync/bulk', [SyncController::class, 'bulk'])->name('sync.bulk');
        Route::post('/sync/{log}/cancel', [SyncController::class, 'cancel'])->name('sync.cancel');
        Route::get('/sync/{log}/status', [SyncController::class, 'status'])->name('sync.status');
        Route::get('/sync/{log}/failures', [SyncController::class, 'failures'])->name('sync.failures');
    });

    // POS Sync (agent batches)
    Route::middleware('can:view_sync')->group(function () {
        Route::get('/pos-sync', [PosSyncDashboardController::class, 'index'])->name('pos-sync.index');
        Route::get('/pos-sync/status', [PosSyncDashboardController::class, 'status'])->name('pos-sync.status');
        Route::get('/pos-sync/{batch}', [PosSyncDashboardController::class, 'show'])->name('pos-sync.show');
        Route::get('/pos-sync/{batch}/items', [PosSyncDashboardController::class, 'items'])->name('pos-sync.items');
        Route::post('/pos-sync/{batch}/retry', [PosSyncDashboardController::class, 'retry'])->name('pos-sync.retry');
        Route::get('/pos-sync/{batch}/failed', [PosSyncDashboardController::class, 'failedItems'])->name('pos-sync.failed');
        Route::post('/pos-sync/token/create', [PosSyncDashboardController::class, 'createToken'])->name('pos-sync.token.create');
        Route::post('/pos-sync/token/{tokenId}/revoke', [PosSyncDashboardController::class, 'revokeToken'])->name('pos-sync.token.revoke');
    });

    // Delivery Locations
    Route::middleware('can:manage_delivery_locations')->group(function () {
        Route::get('/delivery-locations', [DeliveryLocationController::class, 'index'])->name('delivery-locations.index');
        Route::post('/delivery-locations', [DeliveryLocationController::class, 'store'])->name('delivery-locations.store');
        Route::post('/delivery-locations/bulk', [DeliveryLocationController::class, 'bulk'])->name('delivery-locations.bulk');
        Route::put('/delivery-locations/{deliveryLocation}', [DeliveryLocationController::class, 'update'])->name('delivery-locations.update');
        Route::delete('/delivery-locations/{deliveryLocation}', [DeliveryLocationController::class, 'destroy'])->name('delivery-locations.destroy');
        Route::get('/delivery-settings', [DeliverySettingsController::class, 'edit'])->name('delivery-settings.edit');
        Route::put('/delivery-settings', [DeliverySettingsController::class, 'update'])->name('delivery-settings.update');
    });

    // Bank Accounts
    Route::middleware('can:manage_bank_accounts')->group(function () {
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::post('/bank-accounts/bulk', [BankAccountController::class, 'bulk'])->name('bank-accounts.bulk');
        Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
        Route::post('/bank-accounts/{bankAccount}/toggle', [BankAccountController::class, 'toggleActive'])->name('bank-accounts.toggle');
    });

    // Complaints
    Route::middleware('can:manage_complaints')->group(function () {
        Route::get('/complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
        Route::post('/complaints/bulk', [AdminComplaintController::class, 'bulk'])->name('complaints.bulk');
        Route::get('/complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('complaints.show');
        Route::put('/complaints/{complaint}/status', [AdminComplaintController::class, 'updateStatus'])->name('complaints.update-status');
        Route::post('/complaints/{complaint}/reply', [AdminComplaintController::class, 'reply'])->name('complaints.reply');
    });

    // Users
    Route::middleware('can:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/send-reset-link', [UserController::class, 'sendPasswordReset'])->name('users.send-reset-link');
        Route::put('/users/{user}/recover', [UserController::class, 'recover'])->name('users.recover');
    });

    // Discount Campaigns
    Route::middleware('can:manage_discounts')->group(function () {
        Route::resource('discount-campaigns', DiscountCampaignController::class)->except(['show']);
        Route::get('/discount-campaigns/{discountCampaign}', [DiscountCampaignController::class, 'show'])->name('discount-campaigns.show');
        Route::post('/discount-campaigns/{discountCampaign}/toggle-active', [DiscountCampaignController::class, 'toggleActive'])->name('discount-campaigns.toggle-active');
        Route::post('/discount-campaigns/{discountCampaign}/notify', [DiscountCampaignController::class, 'notifySubscribers'])->name('discount-campaigns.notify');
    });

    // Chat
    Route::middleware('can:manage_chat')->group(function () {
        // Quick Responses (before wildcard {chat} routes)
        Route::get('/chat/quick-responses', [AdminChatController::class, 'quickResponsesPage'])->name('chat.quick-responses');
        Route::get('/chat/quick-responses/data', [AdminChatController::class, 'quickResponses'])->name('chat.quick-responses.data');
        Route::post('/chat/quick-responses', [AdminChatController::class, 'storeQuickResponse'])->name('chat.quick-responses.store');
        Route::delete('/chat/quick-responses/{quickResponse}', [AdminChatController::class, 'destroyQuickResponse'])->name('chat.quick-responses.destroy');

        Route::get('/chat', [AdminChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/{chat}', [AdminChatController::class, 'show'])->name('chat.show');
        Route::post('/chat/{chat}/reply', [AdminChatController::class, 'reply'])->name('chat.reply');
        Route::delete('/chat/{chat}', [AdminChatController::class, 'destroy'])->name('chat.destroy');
    });
});

// Customer routes (authenticated users)
Route::prefix('my')->name('customer.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [CustomerOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [CustomerOrderController::class, 'update'])->name('orders.update');
    Route::post('/complaints', [CustomerComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [CustomerComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/reply', [CustomerComplaintController::class, 'reply'])->name('complaints.reply');
});

// Customer chat (authenticated)
Route::middleware('auth')->prefix('chat')->name('chat.')->group(function () {
    Route::post('/send', [ChatController::class, 'send'])->name('send');
    Route::get('/messages', [ChatController::class, 'messages'])->name('messages');
    Route::get('/active', [ChatController::class, 'activeChat'])->name('active');
});
