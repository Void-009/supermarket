<?php
    $adminSearchContext = 'items';
    $adminSearchPlaceholder = 'Search items...';
    $adminSearchUrl = route('admin.items.index');

    if (request()->routeIs('admin.categories.*')) {
        $adminSearchContext = 'categories';
        $adminSearchPlaceholder = 'Search categories...';
        $adminSearchUrl = route('admin.categories.index');
    } elseif (request()->routeIs('admin.orders.*')) {
        $adminSearchContext = 'orders';
        $adminSearchPlaceholder = 'Search orders...';
        $adminSearchUrl = route('admin.orders.index');
    } elseif (request()->routeIs('admin.revenue.*')) {
        $adminSearchContext = 'revenue';
        $adminSearchPlaceholder = 'Search revenue...';
        $adminSearchUrl = route('admin.revenue.index');
    } elseif (request()->routeIs('admin.users.*')) {
        $adminSearchContext = 'users';
        $adminSearchPlaceholder = 'Search users...';
        $adminSearchUrl = route('admin.users.index');
    } elseif (request()->routeIs('admin.complaints.*')) {
        $adminSearchContext = 'complaints';
        $adminSearchPlaceholder = 'Search complaints...';
        $adminSearchUrl = route('admin.complaints.index');
    } elseif (request()->routeIs('admin.delivery-locations.*')) {
        $adminSearchContext = 'delivery-locations';
        $adminSearchPlaceholder = 'Search delivery locations...';
        $adminSearchUrl = route('admin.delivery-locations.index');
    } elseif (request()->routeIs('admin.bank-accounts.*')) {
        $adminSearchContext = 'bank-accounts';
        $adminSearchPlaceholder = 'Search bank accounts...';
        $adminSearchUrl = route('admin.bank-accounts.index');
    } elseif (request()->routeIs('admin.discount-campaigns.*')) {
        $adminSearchContext = 'discount-campaigns';
        $adminSearchPlaceholder = 'Search campaigns...';
        $adminSearchUrl = route('admin.discount-campaigns.index');
    } elseif (request()->routeIs('admin.sync.*')) {
        $adminSearchContext = 'sync';
        $adminSearchPlaceholder = 'Search sync logs...';
        $adminSearchUrl = route('admin.sync.index');
    } elseif (request()->routeIs('admin.pos-sync.*')) {
        $adminSearchContext = 'pos-sync';
        $adminSearchPlaceholder = 'Search POS sync...';
        $adminSearchUrl = route('admin.pos-sync.index');
    }

    $adminSearchInitial = $adminSearchContext === 'items' ? '' : trim(request('search', ''));
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php if (! empty(trim($__env->yieldContent('title')))): ?> <?php echo $__env->yieldContent('title'); ?> | <?php endif; ?> Admin — M-DELIGHT SINGER STORES</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    
    <script>
        window.__isLoggedIn = <?php echo e(Auth::check() ? 'true' : 'false'); ?>;
        window.__APP_URL = '<?php echo e(url('/')); ?>';
        window.VAPID_PUBLIC_KEY = '<?php echo e(config('services.vapid.public_key')); ?>';
    </script>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js', 'resources/js/price-checker.js']); ?>

    <script defer src="<?php echo e(asset('js/collapse.min.js')); ?>"></script>
    <script defer src="<?php echo e(asset('js/alpine.min.js')); ?>"></script>
    <script src="<?php echo e(asset('js/lucide.min.js')); ?>"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Poppins', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-responsive table { min-width: 700px; }
    </style>
</head>
<body class="bg-slate-100 text-gray-900 antialiased min-h-screen" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-screen">

        
        
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-slate-900 text-white z-40">
            <div class="px-4 pt-5 pb-3">
                <div class="flex items-center gap-3 px-4 py-3 bg-white/5 rounded-xl">
                    <img src="<?php echo e(asset('storage/logo/mdelight logo.jpg')); ?>" alt="Logo" class="w-9 h-9 rounded-lg object-cover border border-emerald-400">
                    <div class="leading-tight">
                        <span class="text-lg font-extrabold block" style="font-family:'Poppins',sans-serif;">M-DELIGHT</span>
                        <span class="text-[10px] text-emerald-400 font-medium tracking-widest uppercase">Admin Panel</span>
                    </div>
                </div>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto hide-scrollbar">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.dashboard') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <?php if(Auth::user()->hasPermission('modify_items')): ?>
                <a href="<?php echo e(route('admin.items.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.items.*') && !request()->routeIs('admin.items.reviews*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="package" class="w-5 h-5"></i> Items
                </a>
                <a href="<?php echo e(route('admin.items.reviews')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.items.reviews*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i> Item Reviews
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_categories')): ?>
                <a href="<?php echo e(route('admin.categories.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.categories.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="grid-3x3" class="w-5 h-5"></i> Categories
                </a>
                <a href="<?php echo e(route('admin.slideshow-images.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.slideshow-images.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="images" class="w-5 h-5"></i> Shop Slideshow
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('process_orders')): ?>
                <a href="<?php echo e(route('admin.orders.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.orders.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="receipt" class="w-5 h-5"></i> Orders
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('view_revenue')): ?>
                <a href="<?php echo e(route('admin.revenue.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.revenue.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="banknote" class="w-5 h-5"></i> Revenue
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_delivery_locations')): ?>
                <a href="<?php echo e(route('admin.delivery-locations.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.delivery-locations.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="map-pin" class="w-5 h-5"></i> Delivery Locations
                </a>
                <a href="<?php echo e(route('admin.delivery-settings.edit')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.delivery-settings.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="truck" class="w-5 h-5"></i> Delivery Settings
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_payment_settings')): ?>
                <a href="<?php echo e(route('admin.payment-settings.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.payment-settings.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="settings" class="w-5 h-5"></i> Payment Settings
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_bank_accounts')): ?>
                <a href="<?php echo e(route('admin.bank-accounts.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.bank-accounts.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="landmark" class="w-5 h-5"></i> Bank Accounts
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_complaints')): ?>
                <a href="<?php echo e(route('admin.complaints.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.complaints.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="message-circle" class="w-5 h-5"></i> Complaints
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('view_sync')): ?>
                <a href="<?php echo e(route('admin.sync.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.sync.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i> Sync
                </a>
                <a href="<?php echo e(route('admin.pos-sync.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.pos-sync.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="radio" class="w-5 h-5"></i> POS Sync
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_discounts')): ?>
                <a href="<?php echo e(route('admin.discount-campaigns.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.discount-campaigns.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="tag" class="w-5 h-5"></i> Discount Campaigns
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_chat')): ?>
                <a href="<?php echo e(route('admin.chat.quick-responses')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.chat.quick-responses*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="zap" class="w-5 h-5"></i> Quick Responses
                </a>
                <?php endif; ?>
                <?php if(Auth::user()->hasPermission('manage_users')): ?>
                <a href="<?php echo e(route('admin.users.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.users.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="users" class="w-5 h-5"></i> Users
                </a>
                <?php endif; ?>
                <a href="<?php echo e(route('admin.profile')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.profile') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                    <i data-lucide="user-circle" class="w-5 h-5"></i> Profile
                </a>
            </nav>
            <div class="px-4 py-4 border-t border-white/10">
                <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-400 hover:bg-white/10 hover:text-white rounded-xl transition-colors bg-white/5">
                    <i data-lucide="external-link" class="w-5 h-5"></i> View Store
                </a>
            </div>
        </aside>

        
        <div x-show="sidebarOpen" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[100] lg:hidden" style="display:none;">
            <div class="absolute inset-0 bg-black/50" @click="sidebarOpen = false"></div>
            <div class="absolute inset-y-0 left-0 w-72 bg-slate-900 shadow-2xl overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
                <div class="flex items-center justify-between px-6 h-20 border-b border-white/10">
                    <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-xl">
                        <img src="<?php echo e(asset('storage/logo/mdelight logo.jpg')); ?>" alt="Logo" class="w-8 h-8 rounded-lg object-cover border border-emerald-400">
                        <span class="text-lg font-extrabold text-white" style="font-family:'Poppins',sans-serif;">M-DELIGHT</span>
                    </div>
                    <button @click="sidebarOpen = false" class="p-2 text-gray-400 hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <nav class="px-4 py-6 space-y-1 overflow-y-auto hide-scrollbar">
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.dashboard') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                    </a>
                    <?php if(Auth::user()->hasPermission('modify_items')): ?>
                    <a href="<?php echo e(route('admin.items.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.items.*') && !request()->routeIs('admin.items.reviews*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="package" class="w-5 h-5"></i> Items
                    </a>
                    <a href="<?php echo e(route('admin.items.reviews')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.items.reviews*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i> Item Reviews
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_categories')): ?>
                    <a href="<?php echo e(route('admin.categories.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.categories.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="grid-3x3" class="w-5 h-5"></i> Categories
                    </a>
                    <a href="<?php echo e(route('admin.slideshow-images.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.slideshow-images.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="images" class="w-5 h-5"></i> Shop Slideshow
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('process_orders')): ?>
                    <a href="<?php echo e(route('admin.orders.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.orders.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="receipt" class="w-5 h-5"></i> Orders
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('view_revenue')): ?>
                    <a href="<?php echo e(route('admin.revenue.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.revenue.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="banknote" class="w-5 h-5"></i> Revenue
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_delivery_locations')): ?>
                    <a href="<?php echo e(route('admin.delivery-locations.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.delivery-locations.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="map-pin" class="w-5 h-5"></i> Delivery Locations
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_payment_settings')): ?>
                    <a href="<?php echo e(route('admin.payment-settings.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.payment-settings.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="settings" class="w-5 h-5"></i> Payment Settings
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_bank_accounts')): ?>
                    <a href="<?php echo e(route('admin.bank-accounts.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.bank-accounts.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="landmark" class="w-5 h-5"></i> Bank Accounts
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_complaints')): ?>
                    <a href="<?php echo e(route('admin.complaints.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.complaints.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="message-circle" class="w-5 h-5"></i> Complaints
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('view_sync')): ?>
                    <a href="<?php echo e(route('admin.sync.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.sync.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="refresh-cw" class="w-5 h-5"></i> Sync
                    </a>
                    <a href="<?php echo e(route('admin.pos-sync.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.pos-sync.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="radio" class="w-5 h-5"></i> POS Sync
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_discounts')): ?>
                    <a href="<?php echo e(route('admin.discount-campaigns.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.discount-campaigns.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="tag" class="w-5 h-5"></i> Discount Campaigns
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_chat')): ?>
                    <a href="<?php echo e(route('admin.chat.quick-responses')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.chat.quick-responses*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="zap" class="w-5 h-5"></i> Quick Responses
                    </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->hasPermission('manage_users')): ?>
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.users.*') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="users" class="w-5 h-5"></i> Users
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('admin.profile')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors <?php echo e(request()->routeIs('admin.profile') ? 'bg-emerald-500 text-white' : 'text-gray-400 hover:bg-white/10 hover:text-white'); ?>">
                        <i data-lucide="user-circle" class="w-5 h-5"></i> Profile
                    </a>
                </nav>
                <div class="px-4 py-4 border-t border-white/10">
                    <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-400 hover:bg-white/10 hover:text-white rounded-xl transition-colors bg-white/5">
                        <i data-lucide="external-link" class="w-5 h-5"></i> View Store
                    </a>
                </div>
            </div>
        </div>

        
        <div class="flex-1 w-full lg:ml-64 flex flex-col min-h-screen">

            
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = true" class="lg:hidden p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>
                        <h1 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;"><?php echo $__env->yieldContent('header', 'Dashboard'); ?></h1>
                    </div>
                    <div class="flex items-center gap-3">
                        
                        <div class="hidden sm:block relative" x-data="adminLiveSearch('<?php echo e($adminSearchContext); ?>', '<?php echo e($adminSearchUrl); ?>', '<?php echo e($adminSearchPlaceholder); ?>', <?php echo \Illuminate\Support\Js::from($adminSearchInitial)->toHtml() ?>)" @click.outside="close()">
                            <div class="relative">
                                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                <input type="text"
                                    x-ref="searchInput"
                                    x-model="query"
                                    :placeholder="placeholder"
                                    @input.debounce.300ms="search()"
                                    @keydown.enter.prevent="handleEnter()"
                                    @keydown.arrow-up.prevent="navigate(-1)"
                                    @keydown.arrow-down.prevent="navigate(1)"
                                    @keydown.escape="close()"
                                    class="w-48 lg:w-64 pl-9 pr-16 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all focus:w-72 lg:focus:w-80">
                                <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                </div>
                                <button x-show="!loading && !scannerActive && query.length === 0" @click="startScanner()" x-cloak class="absolute right-11 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors" title="Scan barcode">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                                </button>
                                <button x-show="query.length > 0" @click="clear()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            
                            <div x-show="scannerActive" x-cloak class="absolute top-full right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-[60] p-4 w-80 lg:w-96">
                                <div class="flex items-center gap-3 mb-3">
                                    <svg class="animate-spin h-5 w-5 text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <p class="text-sm text-gray-600">Point camera at barcode...</p>
                                </div>
                                <div id="admin-search-scanner" class="md-viewport" style="aspect-ratio: 4 / 3; min-height: 220px;"></div>
                                <button @click="stopScanner()" class="mt-3 w-full text-center text-xs text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                            </div>

                            
                            <div x-show="open" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute top-full right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-[60] max-h-[60vh] overflow-y-auto overscroll-contain w-80 lg:w-96"
                                x-ref="resultsDropdown">
                                
                                <template x-if="loading">
                                    <div class="px-4 py-3 text-sm text-gray-500 flex items-center gap-2">
                                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Searching...
                                    </div>
                                </template>

                                
                                <template x-if="isItems">
                                    <div>
                                        <template x-for="(item, index) in results" :key="item.id">
                                            <button type="button"
                                                @click="selectItem(item)"
                                                @mouseenter="activeIndex = index"
                                                class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                                :class="activeIndex === index ? 'bg-emerald-50 text-emerald-700' : 'hover:bg-gray-50'">
                                                <template x-if="item.image_url">
                                                    <img :src="'/storage/' + item.image_url" class="w-10 h-10 rounded-lg object-cover border border-gray-200 flex-shrink-0">
                                                </template>
                                                <template x-if="!item.image_url">
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 flex-shrink-0">
                                                        <i data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                                    </div>
                                                </template>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></div>
                                                    <div class="text-xs text-gray-500">₦<span x-text="Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span></div>
                                                </div>
                                                <template x-if="item.stock_status === 'in_stock' || item.quantity > 0">
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 flex-shrink-0">In Stock</span>
                                                </template>
                                                <template x-if="item.stock_status === 'out_of_stock' || (item.quantity <= 0 && item.stock_status !== 'low_stock')">
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200 flex-shrink-0">Out</span>
                                                </template>
                                                <template x-if="item.stock_status === 'low_stock'">
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">Low</span>
                                                </template>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                
                                <template x-if="!isItems">
                                    <div>
                                        <template x-for="(item, index) in results" :key="index">
                                            <button type="button"
                                                @click="selectItem(item)"
                                                @mouseenter="activeIndex = index"
                                                class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                                :class="activeIndex === index ? 'bg-emerald-50 text-emerald-700' : 'hover:bg-gray-50'">
                                                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center border border-emerald-100 flex-shrink-0">
                                                    <i data-lucide="search" class="w-5 h-5 text-emerald-500"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="item.name"></div>
                                                    <div class="text-xs text-gray-500 truncate" x-text="item.subtitle"></div>
                                                </div>
                                                <span class="text-xs font-semibold text-emerald-600 flex-shrink-0">Filter</span>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                
                                <template x-if="!loading && results.length === 0">
                                    <div class="px-4 py-3 text-sm text-gray-500">
                                        <template x-if="isItems">
                                            <span>No results found for "<span x-text="query" class="font-medium"></span>"</span>
                                        </template>
                                        <template x-if="!isItems">
                                            <span>No results for "<span x-text="query" class="font-medium"></span>". Press Enter to filter this page.</span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <div x-data="pushNotifyBtn()" x-init="init()" class="flex items-center">
                            <button type="button" @click="togglePushNotifications()"
                                :title="subscribed ? 'Notifications on — click to turn off' : 'Turn on new order notifications'"
                                :aria-label="subscribed ? 'Notifications on' : 'Turn on new order notifications'"
                                class="relative p-2 rounded-lg transition-colors"
                                :class="subscribed ? 'text-emerald-600 hover:bg-emerald-50' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'">
                                <i data-lucide="bell" class="w-5 h-5"></i>
                                <span x-show="subscribed" x-cloak
                                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                <span x-show="checking" x-cloak
                                    class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-gray-300 ring-2 ring-white"></span>
                            </button>
                        </div>
                        <div class="hidden sm:flex items-center gap-2.5 text-sm text-gray-500">
                        <a href="<?php echo e(route('admin.profile')); ?>" class="flex items-center gap-2.5 hover:opacity-80 transition-opacity">
                            <?php if(Auth::user()->profile_image): ?>
                                <img src="<?php echo e(asset('storage/' . Auth::user()->profile_image)); ?>" alt="<?php echo e(Auth::user()->name); ?>" class="w-8 h-8 rounded-full object-cover border border-gray-200">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold uppercase">
                                    <?php echo e(substr(Auth::user()->name ?? 'A', 0, 1)); ?>

                                </div>
                            <?php endif; ?>
                            <span class="font-medium text-gray-700"><?php echo e(Auth::user()->name ?? 'Admin'); ?></span>
                        </a>
                        </div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            
            <main class="flex-1 w-full p-4 sm:p-6 lg:p-8">
                <?php if(session('success')): ?>
                    <?php if (isset($component)) { $__componentOriginal5194778a3a7b899dcee5619d0610f5cf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alert','data' => ['type' => 'success','message' => session('success')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'success','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('success'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $attributes = $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $component = $__componentOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <?php if (isset($component)) { $__componentOriginal5194778a3a7b899dcee5619d0610f5cf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alert','data' => ['type' => 'error','message' => session('error')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('error'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $attributes = $__attributesOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__attributesOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf)): ?>
<?php $component = $__componentOriginal5194778a3a7b899dcee5619d0610f5cf; ?>
<?php unset($__componentOriginal5194778a3a7b899dcee5619d0610f5cf); ?>
<?php endif; ?>
                <?php endif; ?>

                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        function adminLiveSearch(context, searchUrl, placeholder, initialQuery) {
            return {
                context: context || 'items',
                searchUrl: searchUrl || '<?php echo e(route("admin.items.index")); ?>',
                placeholder: placeholder || 'Search items...',
                query: initialQuery || '',
                results: [],
                open: false,
                loading: false,
                activeIndex: -1,
                scannerActive: false,
                _abortController: null,

                get isItems() {
                    return this.context === 'items';
                },

                search() {
                    this.activeIndex = -1;
                    var q = this.query.trim();
                    if (q.length < 1) {
                        this.results = [];
                        this.open = false;
                        return;
                    }
                    if (this._abortController) this._abortController.abort();
                    this._abortController = new AbortController();
                    this.loading = true;
                    this.open = true;

                    var url = this.isItems
                        ? '<?php echo e(route("api.search")); ?>?q=' + encodeURIComponent(q)
                        : '<?php echo e(route("admin.search")); ?>?q=' + encodeURIComponent(q) + '&type=' + encodeURIComponent(this.context);

                    fetch(url, {
                        signal: this._abortController.signal,
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        this.results = this.isItems ? (data.items || []) : (data.results || []);
                        this.loading = false;
                    }.bind(this))
                    .catch(function(err) {
                        if (err.name !== 'AbortError') this.loading = false;
                    }.bind(this));
                },

                handleEnter() {
                    if (this.activeIndex >= 0 && this.results[this.activeIndex]) {
                        this.selectItem(this.results[this.activeIndex]);
                        return;
                    }
                    var q = this.query.trim();
                    if (q.length < 1) return;
                    if (this.isItems) return;
                    window.location.href = this.searchUrl + '?search=' + encodeURIComponent(q);
                },

                selectItem(item) {
                    if (!item) return;
                    if (this.isItems) {
                        if (!item.id) return;
                        window.location.href = '<?php echo e(route("admin.items.index")); ?>?search=' + encodeURIComponent(item.display_name || item.name || '');
                        return;
                    }
                    if (item.url) {
                        window.location.href = item.url;
                    } else {
                        window.location.href = this.searchUrl + '?search=' + encodeURIComponent(this.query.trim());
                    }
                },

                navigate(dir) {
                    if (!this.results.length) return;
                    this.activeIndex += dir;
                    if (this.activeIndex < 0) this.activeIndex = this.results.length - 1;
                    if (this.activeIndex >= this.results.length) this.activeIndex = 0;
                    var self = this;
                    this.$nextTick(function() {
                        var dd = self.$refs.resultsDropdown;
                        if (!dd) return;
                        var btn = dd.querySelector('.bg-emerald-50');
                        if (btn) btn.scrollIntoView({ block: 'nearest' });
                    });
                },

                close() {
                    this.open = false;
                    this.activeIndex = -1;
                },

                clear() {
                    this.query = '';
                    this.results = [];
                    this.open = false;
                    this.$refs.searchInput.focus();
                },

                async startScanner() {
                    if (this.scannerActive) { this.stopScanner(); return; }
                    this.scannerActive = true;
                    this.open = false;
                    try {
                        var text = await window.scanBarcode({ containerId: 'admin-search-scanner' });
                        var scanText = window.normalizeScanText ? window.normalizeScanText(text) : '';
                        scanText = (typeof scanText === 'string') ? scanText.trim() : '';
                        if (scanText) {
                            this.query = scanText;
                            this.scannerActive = false;
                            this.handleEnter();
                        } else {
                            this.scannerActive = false;
                        }
                    } catch (e) {
                        if (e !== 'Cancelled') console.error('[AdminSearch] Scanner error:', e);
                        this.scannerActive = false;
                    }
                },

                stopScanner() {
                    if (window.stopScanBarcode) window.stopScanBarcode();
                    else if (window._scanAbort) window._scanAbort.abort();
                    this.scannerActive = false;
                }
            }
        }

        function showToast(message, type) {
            const container = document.getElementById('toast-container');
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };
            const colors = {
                success: 'bg-emerald-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            const toast = document.createElement('div');
            toast.className = 'flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-lg text-white text-sm font-medium ' + colors[type] + ' pointer-events-auto';
            toast.style.cssText = 'transform: translateX(120%); transition: transform 0.4s cubic-bezier(0.16,1,0.3,1), opacity 0.3s ease; opacity: 0;';
            toast.innerHTML = icons[type];
            const span = document.createElement('span');
            span.textContent = message;
            toast.appendChild(span);
            container.appendChild(toast);
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toast.style.transform = 'translateX(0)';
                    toast.style.opacity = '1';
                });
            });
            setTimeout(() => {
                toast.style.transform = 'translateX(120%)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 400);
            }, 3500);
        }
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <div id="toast-container" class="fixed top-6 right-4 left-4 sm:left-auto sm:right-6 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage_chat')): ?>
        <?php if (isset($component)) { $__componentOriginal906b43c916fbecd8d40d5cedadd6a5ab = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal906b43c916fbecd8d40d5cedadd6a5ab = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-chat-widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-chat-widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal906b43c916fbecd8d40d5cedadd6a5ab)): ?>
<?php $attributes = $__attributesOriginal906b43c916fbecd8d40d5cedadd6a5ab; ?>
<?php unset($__attributesOriginal906b43c916fbecd8d40d5cedadd6a5ab); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal906b43c916fbecd8d40d5cedadd6a5ab)): ?>
<?php $component = $__componentOriginal906b43c916fbecd8d40d5cedadd6a5ab; ?>
<?php unset($__componentOriginal906b43c916fbecd8d40d5cedadd6a5ab); ?>
<?php endif; ?>
    <?php endif; ?>
</body>
</html>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/layouts/admin.blade.php ENDPATH**/ ?>