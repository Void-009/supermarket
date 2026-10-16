<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="M-DELIGHT SINGER STORES — Your trusted Nigerian supermarket for fresh groceries, household essentials, and more.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="M-DELIGHT">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <title>@hasSection('title') @yield('title') | @endif M-DELIGHT SINGER STORES</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ md5_file(public_path('favicon-32x32.png')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ md5_file(public_path('favicon-16x16.png')) }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ md5_file(public_path('favicon.ico')) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ md5_file(public_path('apple-touch-icon.png')) }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}?v={{ md5_file(public_path('android-chrome-192x192.png')) }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('android-chrome-512x512.png') }}?v={{ md5_file(public_path('android-chrome-512x512.png')) }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js', 'resources/js/price-checker.js'])

    <script defer src="{{ asset('js/collapse.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <script defer src="{{ asset('js/lucide.min.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; overflow-x: hidden; }
        html { overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Poppins', sans-serif; }

        .category-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        @media print {
            .no-print { display: none !important; }
            .no-print * { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col overflow-x-hidden">

    <script>
        window.__isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
        window.__APP_URL = '{{ url('/') }}';
        window.VAPID_PUBLIC_KEY = '{{ config('services.vapid.public_key') }}';
    </script>

    {{-- Top Announcement Bar --}}
    <div class="bg-emerald-500 text-white text-center text-xs sm:text-sm py-2 px-3 sm:px-4 font-medium tracking-wide">
        <span class="inline-flex items-center justify-center gap-1.5 sm:gap-2">
            <i data-lucide="truck" class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0"></i>
            <span class="truncate">{{ __('messages.free_delivery') }}</span>
        </span>
        <span class="mx-2 sm:mx-3 opacity-50">|</span>
        <span class="truncate">{{ __('messages.open_hours') }}</span>
    </div>

    {{-- Top Menu --}}
    <nav class="hidden lg:block bg-slate-900 text-white border-b border-white/10" x-data="{ topMenuOpen: false, catMaxH: 0 }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center gap-5 sm:gap-8 h-10">
                <a href="{{ route('home') }}" class="text-xs sm:text-sm font-semibold text-emerald-400 hover:text-emerald-300 transition-colors whitespace-nowrap">{{ __('messages.home') }}</a>
                <a href="{{ route('shop.index') }}" class="text-xs sm:text-sm font-medium text-gray-300 hover:text-white transition-colors whitespace-nowrap">{{ __('messages.shop') }}</a>
                <a href="{{ route('price-checker.index') }}" class="text-xs sm:text-sm font-medium text-gray-300 hover:text-white transition-colors whitespace-nowrap">{{ __('messages.price_checker') }}</a>
                <div class="relative" @mouseenter="catMaxH = (() => { const hero = document.getElementById('hero-section'); if (!hero) return 320; const rect = hero.getBoundingClientRect(); const navBottom = $el.parentElement.getBoundingClientRect().bottom; return Math.max(80, Math.floor(rect.bottom - navBottom - 12)); })(); topMenuOpen = true" @mouseleave="topMenuOpen = false">
                    <button @click="topMenuOpen = !topMenuOpen" class="inline-flex items-center gap-1 text-xs sm:text-sm font-medium text-gray-300 hover:text-white transition-colors whitespace-nowrap">
                        {{ __('messages.categories') }}
                        <i data-lucide="chevron-down" class="w-3 h-3 transition-transform" :class="topMenuOpen && 'rotate-180'"></i>
                    </button>
                    <div x-show="topMenuOpen" @click.outside="topMenuOpen = false" @wheel.stop x-cloak x-transition :style="'max-height:' + (catMaxH || 320) + 'px'" class="absolute left-1/2 -translate-x-1/2 top-full mt-1 w-52 bg-slate-900 border border-white/10 rounded-xl shadow-2xl py-2 z-[60] overflow-y-auto overscroll-contain scroll-smooth">
                        <a href="{{ route('shop.index') }}" class="block px-4 py-2 text-sm text-gray-300 hover:text-emerald-400 hover:bg-white/5 transition-colors">{{ __('messages.all_categories') }}</a>
                        @foreach($navCategories as $cat)
                            <a href="{{ route('shop.index', ['category' => $cat->slug]) }}" @click="topMenuOpen = false" class="category-name block px-4 py-2 text-sm text-gray-300 hover:text-emerald-400 hover:bg-white/5 transition-colors">{{ $cat->title_name }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Header / Navbar --}}
    <header class="bg-slate-900 text-white sticky top-0 z-50 shadow-lg" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Desktop Row: Logo | Search | Actions --}}
            <div class="hidden lg:flex items-center justify-between h-20 gap-6">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                    <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-DELIGHT" class="w-12 h-12 rounded-full object-cover border-2 border-emerald-400">
                    <div class="leading-tight">
                        <span class="text-xl font-extrabold tracking-tight text-white block" style="font-family:'Poppins',sans-serif;">M-DELIGHT</span>
                        <span class="text-[10px] text-emerald-400 font-medium tracking-widest uppercase">Singer Stores</span>
                    </div>
                </a>

                {{-- Desktop Search (with category selector) --}}
                <div class="relative flex-1 max-w-2xl" x-data="liveSearch({ scannerContainerId: 'headerScanTarget' })" @click.outside="close()">
                    <div class="w-full flex bg-white rounded-xl overflow-hidden shadow-sm focus-within:ring-2 focus-within:ring-emerald-500">
                        <select x-model="category" @change="search()" class="shrink-0 bg-gray-100 border-r border-gray-200 text-sm text-gray-700 px-2.5 py-2 focus:outline-none max-w-[9rem] cursor-pointer">
                            <option value="">{{ __('messages.all_categories') }}</option>
                            @foreach($navCategories as $cat)
                                <option value="{{ $cat->slug }}">{{ $cat->title_name }}</option>
                            @endforeach
                        </select>
                        <div class="relative flex-1">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <input
                                type="text"
                                x-ref="searchInput"
                                x-model="query"
                                @input.debounce.300ms="search()"
                                @keydown.enter.prevent="handleEnter()"
                                @keydown.arrow-down.prevent="navigate(1)"
                                @keydown.arrow-up.prevent="navigate(-1)"
                                @keydown.escape="close()"
                                @focus="if (results.length) open = true"
                                :placeholder="mode === 'barcode' ? @json(__('messages.search_placeholder_barcode')) : (mode === 'id' ? @json(__('messages.search_placeholder_id')) : @json(__('messages.search_placeholder')))"
                                class="w-full pl-9 pr-9 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none"
                            >
                            <div x-show="loading" x-cloak class="absolute right-9 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </div>
                            <button type="button" x-show="query.length > 0" @click="clear()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="startScanner()"
                            :title="scanTitle"
                            :aria-label="scanTitle"
                            class="shrink-0 px-2.5 border-l border-gray-200 flex items-center transition-colors"
                            :class="scannerActive ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-emerald-700 hover:bg-gray-50'"
                        >
                            <i data-lucide="scan-line" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- Camera opens in a fixed overlay, so the page never reflows --}}
                    <x-scan-overlay container-id="headerScanTarget" />

                    {{-- Dropdown Results --}}
                    <div
                        x-show="open && results.length > 0"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-[60] max-h-[60vh] overflow-y-auto overscroll-contain"
                        x-ref="resultsDropdown"
                    >
                        <template x-for="(item, index) in visibleResults" :key="item.id">
                            <button
                                type="button"
                                @click="selectItem(item)"
                                @mouseenter="activeIndex = index"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                :class="activeIndex === index ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                            >
                                <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 flex items-center justify-center">
                                    <template x-if="item.image_url">
                                        <img :src="'/storage/' + item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!item.image_url">
                                        <span class="text-lg">&#x1F6D2;</span>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></p>
                                    <p class="text-xs text-gray-500">
                                        <span x-text="'\u20A6' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                        <template x-if="item.category">
                                            <span x-text="' &middot; ' + item.category" class="text-emerald-600"></span>
                                        </template>
                                    </p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </template>
                        <div class="px-4 py-2 border-t border-gray-100 flex items-center gap-2">
                            <span x-show="modeLabel" x-cloak
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700"
                                x-text="modeLabel"></span>
                            <p class="text-xs text-gray-400">
                                <span x-show="!exactMatch && mode === 'id'" x-cloak>@json(__('messages.search_hint_id'))</span>
                                <span x-show="!exactMatch && mode === 'barcode'" x-cloak>@json(__('messages.search_hint_barcode'))</span>
                                <span x-show="!exactMatch && mode === 'text'">Type an item ID and press Enter</span>
                                <span x-show="exactMatch" class="text-emerald-600 font-medium">Exact match found</span>
                            </p>
                        </div>
                        <button type="button" x-show="hasMore" x-cloak @click="handleEnter()"
                            class="w-full px-4 py-2.5 border-t border-gray-100 text-xs font-semibold text-emerald-700 bg-emerald-50/50 hover:bg-emerald-50 transition-colors"
                            x-text="'@json(__('messages.view_all_results'))' + ' (' + results.length + ')'">
                        </button>
                    </div>

                    {{-- No Results --}}
                    <div
                        x-show="open && query.length >= 2 && results.length === 0 && !loading"
                        x-cloak
                        class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-6 z-[60] text-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <p class="text-sm text-gray-500">No items found for "<span x-text="query" class="font-medium"></span>"</p>
                    </div>
                </div>

                {{-- Right Actions --}}
                <div class="flex items-center gap-2 shrink-0">

                    {{-- Language Switcher --}}
                    <div class="flex items-center gap-1.5 text-xs font-medium mr-1">
                        <a href="{{ route('lang.switch', 'en') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'text-emerald-400 font-bold' : 'text-gray-400 hover:text-white' }} transition-colors">EN</a>
                        <span class="text-gray-600">|</span>
                        <a href="{{ route('lang.switch', 'ha') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'ha' ? 'text-emerald-400 font-bold' : 'text-gray-400 hover:text-white' }} transition-colors">HA</a>
                    </div>

                    {{-- Cart --}}
                    <a href="{{ route('cart.index') }}" class="relative p-2 text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" x-data="{ count: {{ $cartCount ?? 0 }} }" @cart-updated.window="typeof $event.detail.count === 'number' ? count = $event.detail.count : refreshCartCount()">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        <span class="absolute -top-0.5 -right-0.5 bg-emerald-500 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full">
                            <span x-text="count"></span>
                        </span>
                    </a>

                    {{-- Auth --}}
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                <span class="max-w-[7rem] truncate">{{ Auth::user()->name }}</span>
                                <i data-lucide="chevron-down" class="w-3 h-3"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                                @if(Auth::user()->isAdminOrAgent())
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> {{ __('messages.dashboard') }}
                                    </a>
                                @endif
                                <a href="{{ route('customer.orders') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                                    <i data-lucide="package" class="w-4 h-4"></i> {{ __('messages.my_orders') }}
                                </a>
                                <button @click="togglePushNotifications()" class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-emerald-50 hover:text-emerald-600 transition-colors" x-data="pushNotifyBtn()" x-init="init()">
                                    <template x-if="subscribed">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                                    </template>
                                    <template x-if="!subscribed">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68c-.24.06-.47.15-.69.23L18 14.18V16zm-2.27-8.73L18 9.9V16H5.58L16.9 4.68l.83.83zM2.1 3.51l2.84 2.84C4.35 7.34 4 8.62 4 10v5l-2 2v1h13.73l2 2L21 21.73 22.49 20.1 3.51 2.1 2.1 3.51z"/></svg>
                                    </template>
                                    <span x-text="subscribed ? 'Notifications On' : 'Enable Notifications'"></span>
                                </button>
                                <hr class="my-1 border-gray-100">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <i data-lucide="log-out" class="w-4 h-4"></i> {{ __('messages.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}" class="px-3 py-2 text-sm text-gray-300 hover:text-white transition-colors font-medium">{{ __('messages.login') }}</a>
                            <a href="{{ route('register') }}" class="px-4 py-2 text-sm bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-lg transition-colors">{{ __('messages.register') }}</a>
                        </div>
                    @endauth
                </div>
            </div>

            {{-- Mobile Row 1: Logo | Actions --}}
            <div class="lg:hidden flex items-center justify-between h-14">
                <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                    <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-DELIGHT" class="w-9 h-9 rounded-full object-cover border-2 border-emerald-400">
                    <span class="text-lg font-extrabold tracking-tight text-white" style="font-family:'Poppins',sans-serif;">M-DELIGHT</span>
                </a>
                <div class="flex items-center gap-1">
                    <div class="flex items-center gap-1 text-xs font-medium mr-0.5">
                        <a href="{{ route('lang.switch', 'en') }}" class="px-1.5 py-1 rounded {{ app()->getLocale() === 'en' ? 'text-emerald-400 font-bold' : 'text-gray-400 hover:text-white' }} transition-colors">EN</a>
                        <span class="text-gray-600">|</span>
                        <a href="{{ route('lang.switch', 'ha') }}" class="px-1.5 py-1 rounded {{ app()->getLocale() === 'ha' ? 'text-emerald-400 font-bold' : 'text-gray-400 hover:text-white' }} transition-colors">HA</a>
                    </div>
                    <a href="{{ route('cart.index') }}" class="relative p-2 text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors" x-data="{ count: {{ $cartCount ?? 0 }} }" @cart-updated.window="typeof $event.detail.count === 'number' ? count = $event.detail.count : refreshCartCount()">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        <span class="absolute -top-0.5 -right-0.5 bg-emerald-500 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full">
                            <span x-text="count"></span>
                        </span>
                    </a>
                    <button @click="mobileOpen = !mobileOpen" class="p-2 text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                        <i x-show="!mobileOpen" data-lucide="menu" class="w-5 h-5"></i>
                        <i x-show="mobileOpen" x-cloak data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            {{-- Mobile Row 2: Search (with category selector) --}}
            <div class="relative lg:hidden pb-3" x-data="liveSearch({ scannerContainerId: 'mobileHeaderScanTarget' })" @click.outside="close()">
                <div class="w-full flex bg-white rounded-xl overflow-hidden shadow-sm focus-within:ring-2 focus-within:ring-emerald-500">
                    <select x-model="category" @change="search()" class="shrink-0 bg-gray-100 border-r border-gray-200 text-xs sm:text-sm text-gray-700 px-2 py-2 focus:outline-none max-w-[6.5rem] cursor-pointer">
                        <option value="">{{ __('messages.all_categories') }}</option>
                        @foreach($navCategories as $cat)
                            <option value="{{ $cat->slug }}">{{ $cat->title_name }}</option>
                        @endforeach
                    </select>
                    <div class="relative flex-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        <input
                            type="text"
                            x-ref="searchInput"
                            x-model="query"
                            @input.debounce.300ms="search()"
                            @keydown.enter.prevent="handleEnter()"
                            @keydown.arrow-down.prevent="navigate(1)"
                            @keydown.arrow-up.prevent="navigate(-1)"
                            @keydown.escape="close()"
                            @focus="if (results.length) open = true"
                            :placeholder="mode === 'barcode' ? @json(__('messages.search_placeholder_barcode')) : (mode === 'id' ? @json(__('messages.search_placeholder_id')) : @json(__('messages.search_placeholder')))"
                            class="w-full pl-9 pr-9 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none"
                        >
                        <div x-show="loading" x-cloak class="absolute right-9 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                        <button type="button" x-show="query.length > 0" @click="clear()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="startScanner()"
                        :title="scanTitle"
                        :aria-label="scanTitle"
                        class="shrink-0 px-2.5 border-l border-gray-200 flex items-center transition-colors"
                        :class="scannerActive ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-emerald-700 hover:bg-gray-50'"
                    >
                        <i data-lucide="scan-line" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Camera opens in a fixed overlay, so the page never reflows --}}
                <x-scan-overlay container-id="mobileHeaderScanTarget" />

                {{-- Dropdown Results --}}
                <div
                    x-show="open && results.length > 0"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-[60] max-h-[60vh] overflow-y-auto overscroll-contain"
                    x-ref="resultsDropdown"
                >
                    <template x-for="(item, index) in results" :key="item.id">
                        <button
                            type="button"
                            @click="selectItem(item)"
                            @mouseenter="activeIndex = index"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                            :class="activeIndex === index ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                        >
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 flex items-center justify-center">
                                <template x-if="item.image_url">
                                    <img :src="'/storage/' + item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!item.image_url">
                                    <span class="text-lg">&#x1F6D2;</span>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></p>
                                <p class="text-xs text-gray-500">
                                    <span x-text="'\u20A6' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                    <template x-if="item.category">
                                        <span x-text="' &middot; ' + item.category" class="text-emerald-600"></span>
                                    </template>
                                </p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </template>
                    <div class="px-4 py-2 border-t border-gray-100 flex items-center gap-2">
                        <span x-show="modeLabel" x-cloak
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700"
                            x-text="modeLabel"></span>
                        <p class="text-xs text-gray-400">
                            <span x-show="!exactMatch && mode === 'id'" x-cloak>@json(__('messages.search_hint_id'))</span>
                            <span x-show="!exactMatch && mode === 'barcode'" x-cloak>@json(__('messages.search_hint_barcode'))</span>
                            <span x-show="!exactMatch && mode === 'text'">Type an item ID and press Enter</span>
                            <span x-show="exactMatch" class="text-emerald-600 font-medium">Exact match found</span>
                        </p>
                    </div>
                    <button type="button" x-show="hasMore" x-cloak @click="handleEnter()"
                        class="w-full px-4 py-2.5 border-t border-gray-100 text-xs font-semibold text-emerald-700 bg-emerald-50/50 hover:bg-emerald-50 transition-colors"
                        x-text="'@json(__('messages.view_all_results'))' + ' (' + results.length + ')'">
                    </button>
                </div>

                {{-- No Results --}}
                <div
                    x-show="open && query.length >= 2 && results.length === 0 && !loading"
                    x-cloak
                    class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-6 z-[60] text-center"
                >
                    <p class="text-sm text-gray-500">No items found</p>
                </div>
            </div>
        </div>

        {{-- Category Bar: horizontal quick links --}}
        <div class="border-t border-white/10 bg-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-1 overflow-x-auto py-2" data-hscroll style="scrollbar-width:none;">
                    <a href="{{ route('shop.index') }}" class="shrink-0 px-3 py-1.5 text-xs sm:text-sm font-semibold text-emerald-400 hover:bg-white/10 hover:text-emerald-300 rounded-full whitespace-nowrap transition-colors">{{ __('messages.all_categories') }}</a>
                    @foreach($navCategories as $cat)
                        <a href="{{ route('shop.index', ['category' => $cat->slug]) }}" class="category-name shrink-0 px-3 py-1.5 text-xs sm:text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-full whitespace-nowrap transition-colors">{{ $cat->title_name }}</a>
                    @endforeach
                    <span class="w-px h-4 bg-white/10 mx-1 shrink-0"></span>
                    <a href="{{ route('price-checker.index') }}" class="inline-flex items-center gap-1 shrink-0 px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10 rounded-full whitespace-nowrap transition-colors">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i> Price Checker
                    </a>
                </div>
            </div>
        </div>

        {{-- Mobile Menu Drawer --}}
        <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-0 z-50 lg:hidden" style="display:none;">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="mobileOpen = false"></div>
            {{-- Drawer Panel --}}
            <div class="absolute inset-y-0 left-0 w-80 max-w-[85vw] bg-slate-900 shadow-2xl overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-8">
                        <a href="{{ route('home') }}" class="flex items-center gap-2">
                            <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-DELIGHT" class="w-8 h-8 rounded-full object-cover border border-emerald-400">
                            <span class="text-lg font-extrabold text-white" style="font-family:'Poppins',sans-serif;">M-DELIGHT</span>
                        </a>
                        <button @click="mobileOpen = false" class="p-2 text-gray-400 hover:text-white">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- Mobile Nav Links --}}
                    <nav class="space-y-1">
                        <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors font-medium">
                            <i data-lucide="home" class="w-5 h-5"></i> {{ __('messages.home') }}
                        </a>
                        <a href="{{ route('shop.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors font-medium">
                            <i data-lucide="shopping-bag" class="w-5 h-5"></i> {{ __('messages.shop') }}
                        </a>
                        <a href="{{ route('price-checker.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors font-medium">
                            <i data-lucide="search" class="w-5 h-5"></i> Price Checker
                        </a>
                        <div x-data="{ showCats: false }">
                            <button @click="showCats = !showCats" class="flex items-center justify-between w-full px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors font-medium">
                                <span class="flex items-center gap-3"><i data-lucide="grid-3x3" class="w-5 h-5"></i> {{ __('messages.categories') }}</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="showCats && 'rotate-180'"></i>
                            </button>
                            <div x-show="showCats" x-cloak x-transition class="ml-6 mt-1 space-y-0.5 max-h-48 overflow-y-auto">
                                @foreach($navCategories as $cat)
                                    <a href="{{ route('shop.index', ['category' => $cat->slug]) }}" class="category-name block px-4 py-2 text-sm text-gray-400 hover:text-emerald-400 hover:bg-white/5 rounded-lg transition-colors">
                                        {{ $cat->title_name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </nav>

                    <hr class="my-6 border-white/10">

                    {{-- Mobile Language Switcher --}}
                    <div class="px-4 mb-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-2">{{ __('messages.language') }}</div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('lang.switch', 'en') }}" class="flex-1 text-center px-4 py-2.5 text-sm font-medium rounded-xl transition-colors {{ app()->getLocale() === 'en' ? 'bg-emerald-500 text-white' : 'bg-white/10 text-gray-400 hover:text-white hover:bg-white/15' }}">EN</a>
                            <a href="{{ route('lang.switch', 'ha') }}" class="flex-1 text-center px-4 py-2.5 text-sm font-medium rounded-xl transition-colors {{ app()->getLocale() === 'ha' ? 'bg-emerald-500 text-white' : 'bg-white/10 text-gray-400 hover:text-white hover:bg-white/15' }}">HA</a>
                        </div>
                    </div>

                    <hr class="my-6 border-white/10">

                    {{-- Mobile Auth --}}
                    @auth
                        <div class="space-y-1">
                            <div class="px-4 py-2 text-xs text-gray-500 uppercase tracking-wider font-semibold">{{ __('messages.account') }}</div>
                            @if(Auth::user()->isAdminOrAgent())
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors">
                                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i> {{ __('messages.dashboard') }}
                                </a>
                            @endif
                            <a href="{{ route('customer.orders') }}" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors">
                                <i data-lucide="package" class="w-5 h-5"></i> {{ __('messages.my_orders') }}
                            </a>
                            <button @click="togglePushNotifications()" class="w-full flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-white/10 hover:text-white rounded-xl transition-colors" x-data="pushNotifyBtn()" x-init="init()">
                                <template x-if="subscribed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                                </template>
                                <template x-if="!subscribed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68c-.24.06-.47.15-.69.23L18 14.18V16zm-2.27-8.73L18 9.9V16H5.58L16.9 4.68l.83.83zM2.1 3.51l2.84 2.84C4.35 7.34 4 8.62 4 10v5l-2 2v1h13.73l2 2L21 21.73 22.49 20.1 3.51 2.1 2.1 3.51z"/></svg>
                                </template>
                                <span x-text="subscribed ? 'Notifications On' : 'Enable Notifications'"></span>
                            </button>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-xl transition-colors">
                                    <i data-lucide="log-out" class="w-5 h-5"></i> {{ __('messages.logout') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-y-3">
                            <a href="{{ route('login') }}" class="block w-full text-center px-4 py-3 border border-white/20 text-gray-300 hover:bg-white/10 rounded-xl transition-colors font-medium">{{ __('messages.login') }}</a>
                            <a href="{{ route('register') }}" class="block w-full text-center px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-colors font-semibold">{{ __('messages.register') }}</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Page Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-gray-400 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">

                {{-- Customer Care --}}
                <div>
                    <h4 class="text-white font-semibold text-sm uppercase tracking-wider mb-4" style="font-family:'Poppins',sans-serif;">{{ __('messages.customer_care') }}</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm">
                            <i data-lucide="map-pin" class="w-4 h-4 mt-0.5 text-emerald-500 shrink-0"></i>
                            <span>182 Dorayi Babba Kuntau Road, Kano State</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm">
                            <i data-lucide="phone" class="w-4 h-4 mt-0.5 text-emerald-500 shrink-0"></i>
                            <span>08061400220 / 07038214833</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm">
                            <i data-lucide="mail" class="w-4 h-4 mt-0.5 text-emerald-500 shrink-0"></i>
                            <span>mdelightsingerstores@gmail.com</span>
                        </li>
                        <li class="flex items-start gap-3 text-sm">
                            <i data-lucide="clock" class="w-4 h-4 mt-0.5 text-emerald-500 shrink-0"></i>
                            <span>{{ __('messages.hours') }}</span>
                        </li>
                    </ul>
                    <ul class="mt-5 space-y-2.5">
                        <li><a href="{{ route('faqs') }}" class="text-sm hover:text-emerald-400 transition-colors inline-flex items-center gap-2"><i data-lucide="chevron-right" class="w-3 h-3"></i> {{ __('messages.faqs') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="text-sm hover:text-emerald-400 transition-colors inline-flex items-center gap-2"><i data-lucide="chevron-right" class="w-3 h-3"></i> {{ __('messages.contact_us') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="text-sm hover:text-emerald-400 transition-colors inline-flex items-center gap-2"><i data-lucide="chevron-right" class="w-3 h-3"></i> {{ __('messages.returns') }}</a></li>
                    </ul>
                </div>

                {{-- About --}}
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-DELIGHT" class="w-10 h-10 rounded-full object-cover border-2 border-emerald-400">
                        <div class="leading-tight">
                            <span class="text-lg font-extrabold text-white block" style="font-family:'Poppins',sans-serif;">M-DELIGHT</span>
                            <span class="text-[10px] text-emerald-400 font-medium tracking-widest uppercase">Singer Stores</span>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed break-words">{{ __('messages.footer_desc') }}</p>
                    <ul class="mt-5 space-y-2.5">
                        <li><a href="{{ route('about') }}" class="text-sm hover:text-emerald-400 transition-colors inline-flex items-center gap-2"><i data-lucide="chevron-right" class="w-3 h-3"></i> {{ __('messages.about_us') }}</a></li>
                        <li><a href="{{ route('shop.index') }}" class="text-sm hover:text-emerald-400 transition-colors inline-flex items-center gap-2"><i data-lucide="chevron-right" class="w-3 h-3"></i> {{ __('messages.shop') }}</a></li>
                        <li>
                            <button type="button" onclick="window.dispatchEvent(new CustomEvent('show-pwa-install'))" class="text-sm hover:text-emerald-400 transition-colors inline-flex items-center gap-2 cursor-pointer">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Install App
                            </button>
                        </li>
                    </ul>
                    @php
                        $social = $activePaymentSettings ?? null;
                        $facebook = $social ? $social->facebook_url : null;
                        $instagram = $social ? $social->instagram_url : null;
                        $xUrl = $social ? $social->x_url : null;
                        $tiktok = $social ? $social->tiktok_url : null;
                        $whatsapp = $social ? $social->whatsapp_number : null;
                    @endphp
                    @if($facebook || $instagram || $xUrl || $tiktok || $whatsapp)
                    <div class="flex items-center gap-3 mt-5">
                        @if($facebook)
                        <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        @endif
                        @if($instagram)
                        <a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        @endif
                        @if($xUrl)
                        <a href="{{ $xUrl }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        @endif
                        @if($tiktok)
                        <a href="{{ $tiktok }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                        </a>
                        @endif
                        @if($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Payment & Delivery --}}
                <div>
                    <h4 class="text-white font-semibold text-sm uppercase tracking-wider mb-4" style="font-family:'Poppins',sans-serif;">{{ __('messages.payment_delivery') }}</h4>
                    <p class="text-sm font-semibold text-white mb-2">{{ __('messages.payment_methods') }}</p>
                    <ul class="space-y-2.5">
                        <li class="flex items-center gap-2 text-sm"><i data-lucide="banknote" class="w-4 h-4 text-emerald-500 shrink-0"></i> {{ __('messages.cash_on_delivery') }}</li>
                        <li class="flex items-center gap-2 text-sm"><i data-lucide="landmark" class="w-4 h-4 text-emerald-500 shrink-0"></i> {{ __('messages.bank_transfer') }}</li>
                    </ul>
                    <p class="text-sm font-semibold text-white mt-6 mb-2">{{ __('messages.delivery_info') }}</p>
                    <ul class="space-y-2.5">
                        <li class="flex items-center gap-2 text-sm"><i data-lucide="truck" class="w-4 h-4 text-emerald-500 shrink-0"></i> {{ __('messages.delivery_locations') }}</li>
                        <li class="flex items-center gap-2 text-sm"><i data-lucide="clock" class="w-4 h-4 text-emerald-500 shrink-0"></i> {{ __('messages.hours') }}</li>
                    </ul>
                    @if(isset($activePaymentSettings) && $activePaymentSettings && $activePaymentSettings->whatsapp_number)
                        <a href="https://wa.me/{{ $activePaymentSettings->whatsapp_number }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            {{ __('messages.order_via_whatsapp') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                    <p class="text-xs text-gray-500 break-words">&copy; {{ date('Y') }} M-DELIGHT SINGER STORES. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">{{ __('messages.privacy_policy') }}</a>
                        <a href="#" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">{{ __('messages.terms_service') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- Product Quick View Modal --}}
    @include('components.product-quick-view')

    {{-- Scripts --}}
    <script>
        function openQuickView(el) {
            var data = JSON.parse(el.getAttribute('data-item'));
            window.dispatchEvent(new CustomEvent('open-quick-view', { detail: data }));
        }

        function openQuickViewData(data) {
            window.dispatchEvent(new CustomEvent('open-quick-view', { detail: data }));
        }

        function liveSearch(options) {
            // One instance per search bar, so each needs its own scanner target
            // and its own result cache.
            var opts = options || {};
            var containerId = opts.scannerContainerId || null;
            var cache = new Map();
            var CACHE_MAX = 40;
            var RENDER_MAX = 40;

            return {
                query: '',
                category: '',
                results: [],
                total: 0,
                open: false,
                loading: false,
                activeIndex: -1,
                exactMatch: false,
                mode: 'text',
                scannerActive: false,
                _abortController: null,

                /**
                 * Barcode first, then item ID:
                 *   12+ digits -> barcode
                 *   3-5 digits -> item ID
                 *   anything else -> name / partial text
                 */
                computeMode(q) {
                    q = (q === undefined ? this.query : q) || '';
                    q = q.trim();
                    if (/^\d{12,}$/.test(q)) return 'barcode';
                    if (/^\d{3,5}$/.test(q)) return 'id';
                    return 'text';
                },

                get modeLabel() {
                    if (this.mode === 'barcode') return @json(__('messages.search_mode_barcode'));
                    if (this.mode === 'id') return @json(__('messages.search_mode_id'));
                    return '';
                },

                get scanTitle() {
                    if (this.scannerActive) return @json(__('messages.stop_scanner'));
                    return @json(__('messages.scan_barcode'));
                },

                search() {
                    this.activeIndex = -1;
                    var q = this.query.trim();
                    var mode = this.computeMode(q);
                    this.mode = mode;

                    if (q.length < 2) {
                        this.results = [];
                        this.open = false;
                        this.exactMatch = false;
                        this.loading = false;
                        return;
                    }

                    var key = mode + '|' + q + '|' + this.category;

                    // Re-typing or deleting characters used to re-hit the server
                    // for text we had already fetched.
                    if (cache.has(key)) {
                        var hit = cache.get(key);
                        this.results = hit.items;
                        this.total = hit.total;
                        this.exactMatch = hit.exactMatch;
                        this.loading = false;
                        this.open = true;
                        return;
                    }

                    if (this._abortController) this._abortController.abort();
                    this._abortController = new AbortController();
                    this.loading = true;
                    this.open = true;

                    // No limit for text: the server returns every match so
                    // nothing is hidden. Barcode / id lookups resolve to one
                    // row anyway, so they ask for 1.
                    var url = '{{ route("api.search") }}?q=' + encodeURIComponent(q);
                    if (mode !== 'text') url += '&by=' + mode + '&limit=1';
                    if (this.category) url += '&category=' + encodeURIComponent(this.category);

                    fetch(url, {
                        signal: this._abortController.signal,
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        this.results = data.items || [];
                        this.total = typeof data.total === 'number' ? data.total : this.results.length;
                        this.exactMatch = data.exact_match || false;
                        this.loading = false;
                        if (cache.size >= CACHE_MAX) cache.clear();
                        cache.set(key, { items: this.results, exactMatch: this.exactMatch, total: this.total });
                    }.bind(this))
                    .catch(function(err) {
                        if (err.name !== 'AbortError') this.loading = false;
                    }.bind(this));
                },

                /** Rendered slice. Rendering 500 rows in the dropdown is what
                 *  makes typing feel laggy, so we show RENDER_MAX and give the
                 *  rest away via the "view all" footer and the results page. */
                get visibleResults() {
                    return this.results.length > RENDER_MAX
                        ? this.results.slice(0, RENDER_MAX)
                        : this.results;
                },

                get hasMore() {
                    return this.results.length > RENDER_MAX;
                },

                get resultCountLabel() {
                    var n = this.results.length;
                    return n + (n === 1 ? ' result' : ' results');
                },

                async startScanner() {
                    if (!containerId || typeof window.scanBarcode !== 'function') return;
                    if (this.scannerActive) { this.stopScanner(); return; }
                    this.scannerActive = true;
                    try {
                        const text = await window.scanBarcode({ containerId: containerId });
                        const s = (typeof window.normalizeScanText === 'function' ? window.normalizeScanText(text) : '').trim();
                        if (s) {
                            this.query = s;
                            this.$nextTick(() => this.search());
                        }
                    } catch (e) {
                        if (e !== 'Cancelled') console.error('[Search] scanner error:', e);
                    } finally {
                        this.scannerActive = false;
                    }
                },

                stopScanner() {
                    if (window.stopScanBarcode) window.stopScanBarcode();
                    else if (window._scanAbort) window._scanAbort.abort();
                    this.scannerActive = false;
                },

                handleEnter() {
                    // Barcode / item ID: the server already narrowed this to a
                    // single item, so open it in the quick-view popup. That was
                    // the point of typing the number in the first place.
                    if (this.mode !== 'text' && this.results.length) {
                        this.selectItem(this.results[0]);
                        return;
                    }

                    // Free text: go to the results page. Opening a popup here
                    // would hide every other match ("oil" has 593).
                    var params = new URLSearchParams();
                    var q = this.query.trim();
                    if (q.length >= 1) params.set('search', q);
                    if (this.category) params.set('category', this.category);
                    this.close();
                    window.location.href = '{{ route("shop.index") }}' + (params.toString() ? '?' + params.toString() : '');
                },

                selectItem(item) {
                    this.close();
                    this.query = '';
                    this.results = [];
                    this.exactMatch = false;
                    this.mode = 'text';
                    if (this._abortController) {
                        this._abortController.abort();
                        this._abortController = null;
                    }
                    if (typeof openQuickViewData === 'function') {
                        openQuickViewData(item);
                    } else {
                        window.location.href = '/shop?search=' + encodeURIComponent(item.display_name || item.name);
                    }
                },

                navigate(dir) {
                    // Bound against the rendered slice, not the full result set,
                    // or the keyboard can highlight a row that is not on screen.
                    var n = this.visibleResults.length;
                    if (!n) return;
                    this.activeIndex += dir;
                    if (this.activeIndex < 0) this.activeIndex = n - 1;
                    if (this.activeIndex >= n) this.activeIndex = 0;
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
                    this.exactMatch = false;
                    this.mode = 'text';
                    this.$refs.searchInput.focus();
                }
            }
        }

        function showToast(message, type) {
            type = type || 'success';
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }

        function toastNotification() {
            return {
                show: false,
                message: '',
                type: 'success',
                timeout: null,

                init() {
                    window.addEventListener('show-toast', (e) => {
                        this.message = e.detail.message;
                        this.type = e.detail.type || 'success';
                        this.show = true;
                        if (this.timeout) clearTimeout(this.timeout);
                        this.timeout = setTimeout(() => { this.show = false; }, 3500);
                    });
                }
            }
        }

        // Shared JSON POST helper with automatic CSRF retry. If the session
        // token goes stale (page opened too long, session regenerated, etc.)
        // Laravel returns 419 - refresh the token once and retry.
        async function postJson(url, payload) {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var token = csrfMeta ? csrfMeta.content : '';

            async function send(tok) {
                return await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tok,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
            }

            var response = await send(token);
            if (response.status === 419) {
                try {
                    var refresh = await fetch('{{ route("csrf.token") }}', { headers: { 'Accept': 'application/json' } });
                    var fresh = await refresh.json();
                    if (fresh.token) {
                        token = fresh.token;
                        if (csrfMeta) csrfMeta.content = fresh.token;
                    }
                } catch (e) {}
                response = await send(token);
            }
            return response;
        }

        function variationPicker(options, initialId) {
            return {
                options: options || [],
                selectedId: initialId || null,
                qty: 1,
                adding: false,
                added: false,
                message: '',
                success: false,

                get selected() {
                    var self = this;
                    return this.options.find(function (o) { return o.id === self.selectedId; }) || null;
                },

                get price() {
                    return this.selected ? this.selected.selling_price : 0;
                },

                get compareAt() {
                    return this.selected ? this.selected.compare_at_price : null;
                },

                get unlimited() {
                    return this.selected ? !!this.selected.is_variation : false;
                },

                get maxStock() {
                    return this.selected && !this.selected.is_variation ? this.selected.stock : null;
                },

                get available() {
                    return this.selected ? this.selected.stock > 0 : false;
                },

                fmt(n) {
                    return '\u20A6' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                select(id) {
                    this.selectedId = id;
                    this.qty = 1;
                    this.message = '';
                    this.success = false;
                    this.added = false;
                },

                incQty() {
                    if (this.unlimited || !this.maxStock) { this.qty++; return; }
                    this.qty = Math.min(this.maxStock, this.qty + 1);
                },

                async submit() {
                    if (this.adding || !this.selectedId || !this.available) return;
                    this.adding = true;
                    this.message = '';
                    this.success = false;

                    try {
                        var response = await postJson('{{ route("cart.add") }}', {
                            item_id: this.selectedId,
                            quantity: this.qty,
                        });

                        var data = await response.json();

                        if (response.ok && data.success) {
                            this.added = true;
                            this.success = true;
                            this.message = data.message || 'Added to cart!';
                            window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                            var self = this;
                            setTimeout(function () { self.added = false; self.message = ''; }, 2000);
                        } else {
                            this.success = false;
                            this.message = data.message || 'Something went wrong.';
                        }
                    } catch (e) {
                        this.success = false;
                        this.message = 'Network error. Please try again.';
                    } finally {
                        this.adding = false;
                    }
                }
            }
        }

        function buildVariationPicker(product) {
            var options = [];
            var initialId = null;

            if (product.is_variation) {
                options = (product.variations && product.variations.length ? product.variations : []).slice();
                initialId = product.id;
            } else {
                if (product.stock > 0) {
                    options.push({
                        id: product.id,
                        name: product.name,
                        display_name: product.display_name,
                        slug: product.slug,
                        selling_price: product.selling_price,
                        compare_at_price: product.compare_at_price || null,
                        image_url: product.image_url || null,
                        stock: product.stock,
                        is_variation: false,
                    });
                    initialId = product.id;
                }
                (product.variations || []).forEach(function (v) { options.push(v); });
                if (!initialId) {
                    var first = options.find(function (o) { return o.stock > 0; });
                    initialId = first ? first.id : null;
                }
            }

            return variationPicker(options, initialId);
        }

        function productQuickView() {
            return {
                open: false,
                product: null,
                picker: null,

                init() {
                    var self = this;
                    window.addEventListener('open-quick-view', (e) => {
                        self.product = e.detail;
                        self.picker = buildVariationPicker(e.detail);
                        self.open = true;
                        document.body.style.overflow = 'hidden';
                    });
                },

                get displayImage() {
                    if (!this.product) return null;
                    var sel = this.picker ? this.picker.selected : null;
                    var img = sel && sel.image_url ? sel.image_url : this.product.image_url;
                    return imgUrl(img);
                },

                closeModal() {
                    this.open = false;
                    this.product = null;
                    this.picker = null;
                    document.body.style.overflow = '';
                }
            }
        }

        function imgUrl(path) {
            if (!path) return null;
            if (/^(https?:)?\/\//i.test(path) || path.indexOf('/') === 0) return path;
            return '/storage/' + path;
        }

        function storageKey(url) {
            if (!url) return '';
            var i = url.indexOf('/storage/');
            return i === -1 ? url : url.substring(i + 9);
        }

        function productDetailPage(payload, baseImages) {
            var state = buildVariationPicker(payload);
            state.activeImage = 0;
            state.baseImages = Array.isArray(baseImages) ? baseImages : [];
            Object.defineProperty(state, 'galleryImages', {
                enumerable: true,
                configurable: true,
                get: function () {
                    var sel = this.selected;
                    var own = sel && sel.image_url ? imgUrl(sel.image_url) : null;
                    var base = this.baseImages.slice();
                    if (!own) return base;
                    var ownKey = storageKey(own);
                    return [own].concat(base.filter(function (u) { return storageKey(u) !== ownKey; }));
                }
            });
            state.init = function () {
                var self = this;
                this.$watch('selectedId', function () { self.activeImage = 0; });
            };
            return state;
        }

        function addToCartForm(itemId) {
            return {
                itemId: itemId,
                qty: 1,
                adding: false,
                added: false,
                message: '',
                success: false,

                async submit() {
                    if (this.adding) return;
                    this.adding = true;
                    this.message = '';

                    try {
                        var response = await postJson('{{ route("cart.add") }}', {
                            item_id: this.itemId,
                            quantity: this.qty,
                        });

                        var data = await response.json();

                        if (response.ok && data.success) {
                            this.added = true;
                            this.success = true;
                            this.message = data.message || 'Added to cart!';
                            window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                            setTimeout(() => {
                                this.added = false;
                                this.message = '';
                            }, 2000);
                        } else {
                            this.success = false;
                            this.message = data.message || 'Something went wrong.';
                        }
                    } catch (e) {
                        this.success = false;
                        this.message = 'Network error. Please try again.';
                    } finally {
                        this.adding = false;
                    }
                }
            }
        }

        function pwaInstall() {
            return {
                show: false,
                installing: false,
                deferredPrompt: null,
                dismissed: false,
                isIOS: false,
                scrolled: false,

                init() {
                    var self = this;
                    this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                    var onScroll = function () { self.scrolled = window.scrollY > 300; };
                    window.addEventListener('scroll', onScroll, { passive: true });
                    onScroll();
                    if (localStorage.getItem('pwa_install_dismissed')) {
                        this.dismissed = true;
                    }
                    window.addEventListener('beforeinstallprompt', function (e) {
                        e.preventDefault();
                        self.deferredPrompt = e;
                        self.show = true;
                    });
                    window.addEventListener('appinstalled', function () {
                        self.show = false;
                        self.installing = false;
                        localStorage.setItem('pwa_install_dismissed', 'true');
                    });
                    window.addEventListener('show-pwa-install', function () {
                        localStorage.removeItem('pwa_install_dismissed');
                        self.dismissed = false;
                        self.show = true;
                    });
                    setTimeout(function () {
                        if (!self.dismissed && !self.show) {
                            self.show = true;
                        }
                    }, 4000);
                },

                install() {
                    if (this.isIOS || !this.deferredPrompt) return;
                    this.installing = true;
                    this.deferredPrompt.prompt();
                    var self = this;
                    this.deferredPrompt.userChoice.then(function (choice) {
                        self.installing = false;
                        self.show = false;
                        if (choice.outcome === 'accepted') {
                            localStorage.setItem('pwa_install_dismissed', 'true');
                        }
                        self.deferredPrompt = null;
                    });
                },

                dismiss() {
                    this.show = false;
                    localStorage.setItem('pwa_install_dismissed', 'true');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            refreshCartCount();

            document.querySelectorAll('[data-hscroll]').forEach(function (el) {
                el.addEventListener('wheel', function (e) {
                    if (el.scrollWidth > el.clientWidth) {
                        e.preventDefault();
                        el.scrollLeft += e.deltaY || e.deltaX;
                    }
                }, { passive: false });
            });
        });

        function refreshCartCount() {
            fetch('{{ route("cart.count") }}', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (typeof data.count === 'number') {
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count } }));
                    }
                })
                .catch(function () {});
        }

        document.addEventListener('sw-ready', function () {
            if (window.__isLoggedIn && 'Notification' in window && Notification.permission === 'granted') {
                window.getPushSubscriptionStatus().then(function (subscribed) {
                    if (!subscribed) {
                        window.subscribeToPush().catch(function () {});
                    }
                });
            }
        });
    </script>

    {{-- Guest Account Prompt Banner --}}
    {{-- Suppressed on checkout: it is pinned to the bottom of the viewport, where the
         mobile checkout order-summary bar lives, and it would cover the form. --}}
    @guest
    @if(!request()->routeIs('checkout.index'))
    <div x-data="{ show: false }" x-init="setTimeout(() => { if (!sessionStorage.getItem('guestBannerDismissed')) { show = true; lucide.createIcons(); } }, 3000)"
        x-show="show" x-cloak
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-50">
        <div class="max-w-3xl mx-auto px-2.5 pb-2.5 sm:px-4 sm:pb-4">
            <div class="bg-emerald-600 rounded-xl shadow-2xl shadow-emerald-900/20">
                <div class="relative flex items-center gap-2 sm:gap-3 px-2.5 py-2 sm:px-4 sm:py-3">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-white"></i>
                        </div>
                        <p class="text-white text-xs sm:text-sm font-medium truncate">
                            Create an account for faster checkout & exclusive deals.
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                        <a href="{{ route('register') }}" class="px-2.5 py-1.5 sm:px-3.5 sm:py-2 bg-white text-emerald-700 font-bold text-xs sm:text-sm rounded-lg hover:bg-emerald-50 transition-colors whitespace-nowrap">
                            Sign up
                        </a>
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex px-3.5 py-2 bg-white/15 text-white font-semibold text-sm rounded-lg hover:bg-white/25 transition-colors border border-white/20 whitespace-nowrap">
                            Log in
                        </a>
                        <button @click="show = false; sessionStorage.setItem('guestBannerDismissed', '1')" class="flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 bg-black/10 hover:bg-black/20 text-white/80 hover:text-white rounded-full transition-colors" title="Cancel">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endguest

    {{-- PWA Install Prompt --}}
    <div x-data="pwaInstall()" x-init="init()" x-show="show" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed right-4 left-auto w-max max-w-[50vw] sm:max-w-xs sm:right-4 z-[90] bg-slate-900 text-white rounded-xl shadow-2xl border border-white/10 p-3"
        :class="scrolled ? 'bottom-52' : 'bottom-6'">
        <div class="flex items-start gap-2">
            <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-DELIGHT" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-emerald-400">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold whitespace-nowrap">Install M-DELIGHT</p>
                <p class="text-[11px] text-gray-400 mt-0.5 leading-snug">Install for the best experience.</p>
            </div>
            <button @click="dismiss()" class="text-gray-500 hover:text-white p-1 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- iOS instructions (Safari has no install prompt) --}}
        <div x-show="isIOS" class="mt-2 rounded-lg bg-white/5 border border-white/10 p-2.5 text-[11px] text-gray-300 leading-relaxed">
            On iPhone/iPad: tap the <span class="text-white font-semibold">Share</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline-block align-[-2px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
            button in your browser, then choose <span class="text-white font-semibold">"Add to Home Screen"</span>.
        </div>

        <button @click="install()" x-show="!isIOS && !installing" class="mt-2 w-full px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors">
            Install App
        </button>
        <button x-show="installing" disabled class="mt-2 w-full px-3 py-1.5 bg-gray-700 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed">
            Installing...
        </button>
        <button @click="dismiss()" x-show="isIOS" class="mt-2 w-full px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-lg transition-colors border border-white/20">
            Got it
        </button>
    </div>

    {{-- Toast Notification --}}
    <div x-data="toastNotification()" x-init="init()"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed top-20 right-4 sm:right-6 z-[100] max-w-sm"
        style="pointer-events:none;"
    >
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border"
            :class="type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-blue-50 border-blue-200 text-blue-800'"
            style="pointer-events:auto;"
        >
            <template x-if="type === 'success'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </template>
            <template x-if="type === 'error'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </template>
            <p class="text-sm font-medium" x-text="message"></p>
        </div>
    </div>

    {{-- Floating Action Buttons (Cart + WhatsApp + Live Chat) --}}
    {{-- On mobile checkout the summary bar owns the bottom of the viewport, so lift them.
         The safe-area padding pushes the stack up on notched phones without
         changing the desktop position, matching the sticky summary bar. --}}
    <div x-data="{ show: false }" @scroll.window="show = window.scrollY > 300"
         class="fixed right-4 sm:right-6 z-[55] flex flex-col gap-3 {{ request()->routeIs('checkout.index') ? 'bottom-32 lg:bottom-6' : 'bottom-6' }}"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        {{-- Floating Cart --}}
        <a href="{{ route('cart.index') }}"
           x-show="show"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-4"
           x-transition:enter-end="opacity-100 translate-y-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="opacity-100 translate-y-0"
           x-transition:leave-end="opacity-0 translate-y-4"
           class="w-14 h-14 bg-green-500 hover:bg-green-600 rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 group no-print relative"
           style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);"
           title="View Cart"
           x-data="{ count: {{ $cartCount ?? 0 }} }" @cart-updated.window="typeof $event.detail.count === 'number' ? count = $event.detail.count : refreshCartCount()">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
            </svg>
            <span class="absolute -top-1 -right-1 bg-white text-green-600 text-[11px] font-bold h-5 w-5 flex items-center justify-center rounded-full shadow">
                <span x-text="count"></span>
            </span>
            <span class="absolute -top-10 right-0 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                View Cart
            </span>
        </a>

        {{-- Floating WhatsApp Button --}}
        {{-- Always visible. It used to share the cart button's scroll-reveal
             flag, so it was missing on both desktop and mobile until the user
             scrolled 300px. --}}
        @if(isset($activePaymentSettings) && $activePaymentSettings && $activePaymentSettings->whatsapp_number)
        <a href="https://wa.me/{{ $activePaymentSettings->whatsapp_number }}" target="_blank" rel="noopener noreferrer"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-4"
           x-transition:enter-end="opacity-100 translate-y-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="opacity-100 translate-y-0"
           x-transition:leave-end="opacity-0 translate-y-4"
           class="w-14 h-14 bg-green-500 hover:bg-green-600 rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 group no-print"
           style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);"
           title="Chat on WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <span class="absolute -top-10 right-0 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                Chat on WhatsApp
            </span>
        </a>
        @endif

        {{-- Floating Live Chat (logged-in users) --}}
        @auth
            <x-chat-widget />
        @endauth
    </div>

    @stack('scripts')
</body>
</html>
