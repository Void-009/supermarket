@extends('layouts.admin')

@section('title', 'Items')
@section('header', 'Items')

@section('content')
<div x-data="itemsManager()" x-init="init()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Items</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your product inventory.</p>
        </div>
        <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Item
        </button>
        <a href="{{ route('admin.items.variations.manage') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 transition-colors shadow-sm">
            <i data-lucide="package-plus" class="w-4 h-4"></i> Manage Variations
        </a>
        <a href="{{ route('admin.items.missing-images') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-500 text-white text-sm font-semibold rounded-xl hover:bg-rose-600 transition-colors shadow-sm">
            <i data-lucide="image-off" class="w-4 h-4"></i> Missing Images
            <span class="inline-flex items-center justify-center min-w-5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20">{{ number_format($missingImageCount ?? 0) }}</span>
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

        {{-- Recently Created --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Newly Created</h3>
                    <p class="text-xs text-gray-500">Latest items added</p>
                </div>
            </div>
            @forelse($recentItems as $ri)
            <div class="flex items-center gap-3 py-2 border-t border-gray-50 first:border-0">
                @if($ri->image_url)
                    <img src="{{ asset('storage/' . $ri->image_url) }}" class="w-8 h-8 rounded-lg object-cover border border-gray-200">
                @else
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                        <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $ri->display_name }}</div>
                    <div class="text-xs text-gray-500">₦{{ number_format($ri->selling_price, 2) }}</div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 py-2">No items yet.</p>
            @endforelse
        </div>

        {{-- Most Demanded --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Most Demanded</h3>
                    <p class="text-xs text-gray-500">By order count</p>
                </div>
            </div>
            @forelse($mostPopular as $mp)
            <div class="flex items-center gap-3 py-2 border-t border-gray-50 first:border-0">
                @if($mp->image_url)
                    <img src="{{ asset('storage/' . $mp->image_url) }}" class="w-8 h-8 rounded-lg object-cover border border-gray-200">
                @else
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                        <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $mp->display_name }}</div>
                    <div class="text-xs text-gray-500">₦{{ number_format($mp->selling_price, 2) }} · {{ $mp->order_items_count }} orders</div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 py-2">No orders yet.</p>
            @endforelse
        </div>

        {{-- Admin Favorites --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="heart" class="w-5 h-5 text-rose-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Admin Favorites</h3>
                    <p class="text-xs text-gray-500">Starred items</p>
                </div>
            </div>
            @forelse($adminFavoriteItems as $af)
            <div class="flex items-center gap-3 py-2 border-t border-gray-50 first:border-0">
                @if($af->image_url)
                    <img src="{{ asset('storage/' . $af->image_url) }}" class="w-8 h-8 rounded-lg object-cover border border-gray-200">
                @else
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                        <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $af->display_name }}</div>
                    <div class="text-xs text-gray-500">₦{{ number_format($af->selling_price, 2) }}</div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 py-2">No favorites yet. Click the star icon in the table below.</p>
            @endforelse
        </div>

    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.items.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative" x-data="itemsLiveSearch()" @click.outside="close()">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search items..."
                            x-ref="searchInput"
                            x-model="query"
                            @input.debounce.300ms="search()"
                            @keydown.enter.prevent="handleEnter()"
                            @keydown.arrow-up.prevent="navigate(-1)"
                            @keydown.arrow-down.prevent="navigate(1)"
                            @keydown.escape="close()"
                            class="w-full pl-10 pr-16 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                        <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                        <button x-show="!loading && !scannerActive && query.length === 0" @click="startScanner()" type="button" x-cloak class="absolute right-11 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors" title="Scan barcode">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                        </button>
                        <button x-show="query.length > 0" @click.prevent="clear()" type="button" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Scanner Viewfinder --}}
                    <div x-show="scannerActive" x-cloak class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-[60] p-4 w-full">
                        <div class="flex items-center gap-3 mb-3">
                            <svg class="animate-spin h-5 w-5 text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <p class="text-sm text-gray-600">Point camera at barcode...</p>
                        </div>
                        <div id="admin-items-search-scanner" class="md-viewport" style="aspect-ratio: 4 / 3; min-height: 220px;"></div>
                        <button @click="stopScanner()" class="mt-3 w-full text-center text-xs text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                    </div>

                    {{-- Search Results Dropdown --}}
                    <div x-show="open && (results.length > 0 || loading)" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-[60] max-h-[60vh] overflow-y-auto overscroll-contain"
                        x-ref="resultsDropdown">
                        <template x-if="loading">
                            <div class="px-4 py-3 text-sm text-gray-500 flex items-center gap-2">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Searching...
                            </div>
                        </template>
                        <template x-for="(item, index) in results" :key="item.id">
                            <a :href="'/admin/items?search=' + encodeURIComponent(item.display_name || item.name)"
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
                                    <div class="text-xs text-gray-500">₦<span x-text="Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span> <template x-if="item.discount_is_active && item.discount_percentage"><span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700" x-text="'-' + item.discount_percentage + '%'"></span></template> &middot; <span x-text="item.category || 'Uncategorized'" class="text-gray-400"></span></div>
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
                            </a>
                        </template>
                        <template x-if="!loading && results.length === 0">
                            <div class="px-4 py-3 text-sm text-gray-500">
                                No results for "<span x-text="query" class="font-medium"></span>"
                            </div>
                        </template>
                    </div>
                </div>
                <div class="sm:w-56">
                    <select name="category_id" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:w-44">
                    <select name="sort" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price Low-High</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price High-Low</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Stock Low-High</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="sm:w-44">
                    <select name="stock_status" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">All Stock</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div class="sm:w-44">
                    <select name="visibility" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">All Visibility</option>
                        <option value="visible" {{ request('visibility') === 'visible' ? 'selected' : '' }}>Visible</option>
                        <option value="hidden" {{ request('visibility') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                    </select>
                </div>
                <div class="flex gap-2 sm:ml-auto">
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Filter</button>
                    @if(request('search') || request('category_id') || request('sort') || request('stock_status') || request('visibility'))
                        <a href="{{ route('admin.items.index') }}" class="px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl transition-colors">Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
        class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
        <span class="text-sm font-medium text-emerald-800"><span x-text="selectedIds.length"></span> item(s) selected</span>
        <div class="flex flex-wrap items-center gap-2">
            <select x-model="bulkCategory" class="px-3 py-1.5 text-sm border border-emerald-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none">
                <option value="">Move to category...</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <button @click="bulkAction('change_category')" :disabled="!bulkCategory" class="px-3 py-1.5 text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors disabled:opacity-50">Move</button>
            <button @click="bulkAction('bulk_hide')" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors">Hide</button>
            <button @click="bulkAction('bulk_show')" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors">Show</button>
        </div>
        <button @click="selectedIds = []; document.querySelectorAll('.item-checkbox').forEach(c => c.checked = false)" class="ml-auto text-sm text-emerald-700 hover:text-emerald-900 underline">Deselect all</button>
    </div>

    {{-- Items Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
<table class="w-full text-sm min-w-[900px]">
                            <thead>
                                <tr class="border-b border-gray-100">
                        <th class="px-2.5 py-3 text-left">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                        </th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Image</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Price</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Stock</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-2.5 py-3 text-center font-medium text-gray-500">Fav</th>
                        <th class="px-2.5 py-3 text-center font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($items as $item)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-2.5 py-4">
                            <input type="checkbox" value="{{ $item->id }}" class="item-checkbox rounded border-gray-300 text-emerald-500 focus:ring-emerald-500" @change="toggleSelect({{ $item->id }}, $event)">
                        </td>
                        <td class="px-2.5 py-4">
                            @if($item->image_url)
                                <img src="{{ asset('storage/' . $item->image_url) }}" alt="{{ $item->name }}" class="w-9 h-9 rounded-lg object-cover border border-gray-200">
                            @else
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                                    <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            @endif
                        </td>
                        <td class="px-2.5 py-4 max-w-[180px]">
                            <div class="font-medium text-gray-900 break-words">{{ $item->display_name }}</div>
                            @if($item->display_name !== $item->name)
                                <div class="text-[11px] text-gray-400 break-words">Default: {{ $item->name }}</div>
                            @endif
                            <div class="text-[11px] text-gray-400 break-words">{{ $item->slug }}</div>
                        </td>
                        <td class="px-2.5 py-4 text-gray-600 max-w-[120px] break-words">{{ $item->category->name ?? 'N/A' }}</td>
                        <td class="px-2.5 py-4 font-medium text-gray-900 whitespace-nowrap">
                            ₦{{ number_format($item->selling_price, 2) }}
                            @if($item->discount_is_active && $item->discount_percentage)
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">-{{ $item->discount_percentage }}%</span>
                            @endif
                        </td>
                        <td class="px-2.5 py-4 whitespace-nowrap">
                            @php
                                $stockColor = $item->quantity > 10 ? 'text-emerald-700' : ($item->quantity > 0 ? 'text-yellow-700' : 'text-red-700');
                            @endphp
                            <span class="font-medium {{ $stockColor }}">{{ number_format($item->quantity) }}</span>
                        </td>
                        <td class="px-2.5 py-4 whitespace-nowrap">
                            @if($item->is_visible_online)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200">Hidden</span>
                            @endif
                            <div class="mt-1" title="{{ $item->owned_by_pos ? 'Created by the POS sync. The till may hide it when it stops listing the id.' : 'Created on the website. The POS sync never hides it.' }}">
                                @if($item->owned_by_pos)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">Till</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">Website</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-2.5 py-4 text-center">
                            <button @click="toggleAdminFavorite({{ $item->id }}, $event)" class="inline-flex items-center justify-center w-7 h-7 rounded-full transition-colors {{ $item->is_admin_favorite ? 'text-rose-500 bg-rose-50 hover:bg-rose-100' : 'text-gray-300 hover:text-rose-400 hover:bg-gray-100' }}" title="Toggle admin favorite">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="{{ $item->is_admin_favorite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            </button>
                        </td>
                        <td class="px-2.5 py-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                <button @click="openEditModal(@js($item))" class="inline-flex items-center gap-1 px-2 py-1.5 text-[11px] font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                    <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                                </button>
                                <button @click="deleteItem({{ $item->id }})" class="inline-flex items-center gap-1 px-2 py-1.5 text-[11px] font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Del
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <i data-lucide="package" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No items found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $items->links('components.pagination') }}
        </div>
        @endif
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-4 sm:px-6 py-4 flex items-center justify-between gap-3 rounded-t-2xl z-10">
                <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;" x-text="editingItem ? 'Edit Item' : 'Add Item'"></h3>
                <button @click="closeModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form @submit.prevent="saveItem()" class="p-4 sm:p-6 space-y-5">
                @csrf
                <input type="hidden" name="_token" :value="csrfToken">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2" x-show="editingItem" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">POS Item ID</label>
                        <input type="text" disabled readonly :value="editingItem ? editingItem.pos_item_id : ''" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-100 text-gray-500 cursor-not-allowed outline-none" title="The unique POS item ID (e.g. 1070 or 1070@ for a variation). Cannot be changed.">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input x-model="form.name" type="text" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Item name">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Display Name <span class="text-gray-400 text-xs font-normal">(optional — shown instead of name if filled)</span></label>
                        <input x-model="form.display_name" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Custom display name (leave blank to use default)">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Category <span class="text-red-500">*</span></label>
                        <select x-model="form.category_id" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Stock Status</label>
                        <select x-model="form.stock_status" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                            <option value="in_stock">In Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                            <option value="low_stock">Low Stock</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Cost Price <span class="text-red-500">*</span></label>
                        <input x-model="form.cost_price" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Selling Price <span class="text-red-500">*</span></label>
                        <input x-model="form.selling_price" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0.00">
                    </div>

                    {{-- Discount Fields --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Discount %</label>
                        <input x-model="form.discount_percentage" type="number" step="0.01" min="0" max="100" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="e.g. 20">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Discount Active</label>
                        <label class="relative inline-flex items-center cursor-pointer mt-2">
                            <input type="checkbox" x-model="form.discount_is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            <span class="ml-3 text-sm text-gray-600" x-text="form.discount_is_active ? 'Active' : 'Inactive'"></span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Discount Start</label>
                        <input x-model="form.discount_starts_at" type="datetime-local" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Discount End</label>
                        <input x-model="form.discount_ends_at" type="datetime-local" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                        <input x-model="form.quantity" type="number" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="0">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Image</label>
                        <div class="flex items-start gap-4">
                            {{-- Preview --}}
                            <div class="w-20 h-20 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 flex-shrink-0 flex items-center justify-center">
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!imagePreview && form.image_url">
                                    <img :src="'/storage/' + form.image_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!imagePreview && !form.image_url && imageInherited && editingItem && editingItem.image_url">
                                    <img :src="'/storage/' + editingItem.image_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!imagePreview && !form.image_url && (!imageInherited || !editingItem || !editingItem.image_url)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5z"/></svg>
                                </template>
                            </div>
                            {{-- File input --}}
                            <div class="flex-1">
                                <input type="file" accept="image/*" @change="handleImageUpload($event)" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 file:cursor-pointer">
                                <p x-show="uploading" class="text-xs text-gray-500 mt-1">Uploading...</p>
                                <p x-show="uploadError" x-cloak class="text-xs text-red-500 mt-1" x-text="uploadError"></p>
                                <p x-show="imageInherited" x-cloak class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                    <i data-lucide="link-2" class="w-3 h-3"></i> Sharing parent image — upload one here to use its own.
                                </p>
                                <button x-show="form.image_url && !imagePreview" @click="removeImage()" type="button" class="text-xs text-red-500 hover:text-red-700 mt-1 underline">Remove image</button>
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea x-model="form.description" rows="3" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="Optional description..."></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" x-model="form.is_visible_online" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Visible Online</span>
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" x-model="form.is_bulky" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-amber-500 transition-colors"></div>
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Bulky Item</span>
                                <p class="text-xs text-gray-400">Mark this item as bulky for tiered delivery fee calculation</p>
                            </div>
                        </label>
                    </div>

                    <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Barcodes</label>
                        <p class="text-xs text-gray-400 mb-3">Enter barcodes separated by commas (e.g. 1234567890, ABC-12345)</p>
                        <textarea x-model="form.barcodes_text" rows="2" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none font-mono" placeholder="Barcode 1, Barcode 2, ..."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50">
                        <span x-show="!saving" x-text="editingItem ? 'Update Item' : 'Save Item'"></span>
                        <span x-show="saving" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation --}}
    <div x-show="showDeleteConfirm" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteConfirm = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Item?</h3>
            <p class="text-sm text-gray-500 mb-6">This action cannot be undone. The item will be permanently removed.</p>
            <div class="flex items-center gap-3">
                <button @click="showDeleteConfirm = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                <button @click="confirmDelete()" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    function itemsLiveSearch() {
        return {
            query: '',
            results: [],
            open: false,
            loading: false,
            activeIndex: -1,
            scannerActive: false,
            _abortController: null,

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

                fetch('{{ route("api.search") }}?q=' + encodeURIComponent(q), {
                    signal: this._abortController.signal,
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    this.results = data.items || [];
                    this.loading = false;
                }.bind(this))
                .catch(function(err) {
                    if (err.name !== 'AbortError') this.loading = false;
                }.bind(this));
            },

            handleEnter() {
                if (this.activeIndex >= 0 && this.results[this.activeIndex]) {
                    var item = this.results[this.activeIndex];
                    window.location.href = '/admin/items?search=' + encodeURIComponent(item.display_name || item.name);
                } else if (this.query.trim().length >= 1) {
                    this.$refs.searchInput.closest('form').submit();
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
                    var text = await window.scanBarcode({ containerId: 'admin-items-search-scanner' });
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
                    if (e !== 'Cancelled') console.error('[AdminItemsSearch] Scanner error:', e);
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

    function itemsManager() {
        return {
            showModal: false,
            showDeleteConfirm: false,
            editingItem: null,
            deleteId: null,
            saving: false,
            selectedIds: [],
            bulkCategory: '',
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            form: {
                name: '',
                display_name: '',
                category_id: '',
                description: '',
                cost_price: '',
                selling_price: '',
                discount_percentage: '',
                discount_is_active: false,
                discount_starts_at: '',
                discount_ends_at: '',
                quantity: 0,
                image_url: '',
                is_visible_online: true,
                is_bulky: false,
                stock_status: 'in_stock',
                barcodes_text: '',
            },
            imagePreview: null,
            imageInherited: false,
            imageChanged: false,
            uploading: false,
            uploadError: '',

            init() {
                this.$nextTick(() => lucide.createIcons());
            },

            openCreateModal() {
                this.editingItem = null;
                this.imagePreview = null;
                this.imageInherited = false;
                this.imageChanged = false;
                this.uploadError = '';
                this.form = {
                    name: '', display_name: '', category_id: '', description: '', cost_price: '',
                    selling_price: '', discount_percentage: '', discount_is_active: false,
                    discount_starts_at: '', discount_ends_at: '',
                    quantity: 0, image_url: '',
                    is_visible_online: true, is_bulky: false, stock_status: 'in_stock', barcodes_text: '',
                };
                this.showModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            openEditModal(item) {
                this.editingItem = item;
                this.imagePreview = null;
                this.imageChanged = false;
                const ownImage = item.own_image_url || '';
                this.imageInherited = !ownImage && !!item.image_url && (!!item.parent_item_number || String(item.pos_item_id || '').includes('@'));
                this.uploadError = '';
                this.form = {
                    name: item.name,
                    display_name: item.display_name || '',
                    category_id: item.category_id,
                    description: item.description || '',
                    cost_price: item.cost_price,
                    selling_price: item.selling_price,
                    discount_percentage: item.discount_percentage || '',
                    discount_is_active: item.discount_is_active || false,
                    discount_starts_at: item.discount_starts_at || '',
                    discount_ends_at: item.discount_ends_at || '',
                    quantity: item.quantity,
                    image_url: ownImage,
                    is_visible_online: item.is_visible_online,
                    is_bulky: item.is_bulky || false,
                    stock_status: item.stock_status || 'in_stock',
                    barcodes_text: (item.barcodes || []).map(b => b.barcode_value || b).join(', '),
                };
                this.showModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            closeModal() {
                this.showModal = false;
                this.editingItem = null;
                this.imagePreview = null;
            },

            describeError(response, data) {
                const parts = [];
                if (data && data.message) parts.push(data.message);
                if (response && response.status >= 500) {
                    parts.push('(HTTP ' + response.status + ' — see storage/logs/laravel.log)');
                }
                if (data && data.errors) {
                    Object.values(data.errors).forEach(list => {
                        (list || []).forEach(m => parts.push(m));
                    });
                }
                if (!parts.length) {
                    if (response.status === 413) {
                        parts.push('The file exceeds the server upload limit (upload_max_filesize / post_max_size).');
                    } else if (response.status === 419) {
                        parts.push('Session expired. Reload the page and try again.');
                    } else if (response.status === 403) {
                        parts.push('You do not have permission for this action.');
                    } else if (response.status >= 500) {
                        parts.push('Server error (HTTP ' + response.status + '). Check storage/logs/laravel.log on the server for details.');
                    } else {
                        parts.push('Request failed (HTTP ' + response.status + ').');
                    }
                }
                return parts.join('\n');
            },

            handleImageUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.uploadError = '';
                this.imagePreview = URL.createObjectURL(file);
                const formData = new FormData();
                formData.append('image', file);
                this.uploading = true;
                fetch('/admin/items/upload-image', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData,
                })
                .then(async r => {
                    let data = null;
                    try { data = await r.json(); } catch (e) {}
                    if (r.ok && data && data.success) {
                        this.form.image_url = data.path;
                        this.imageChanged = true;
                        this.imageInherited = false;
                        this.imagePreview = null;
                    } else {
                        this.uploadError = this.describeError(r, data);
                        this.imagePreview = null;
                    }
                    this.uploading = false;
                })
                .catch(() => {
                    this.uploadError = 'Upload failed. Please try again.';
                    this.imagePreview = null;
                    this.uploading = false;
                });
            },

            removeImage() {
                this.form.image_url = '';
                this.imageChanged = true;
                this.imageInherited = false;
                this.imagePreview = null;
                this.uploadError = '';
            },

            async saveItem() {
                this.saving = true;
                try {
                    const url = this.editingItem
                        ? `/admin/items/${this.editingItem.id}`
                        : '/admin/items';
                    const method = this.editingItem ? 'PUT' : 'POST';

                    const formData = new FormData();
                    formData.append('_token', this.csrfToken);
                    formData.append('_method', method);

                    // Only send the fields that actually changed, so untouched
                    // values (barcodes, prices, etc.) are never overwritten.
                    const appendChanged = (name, value, original) => {
                        if (!this.editingItem || this.changed(name, value, original)) {
                            formData.append(name, value === null || value === undefined ? '' : value);
                        }
                    };

                    appendChanged('name', this.form.name, this.originalValue('name'));
                    appendChanged('display_name', this.form.display_name, this.originalValue('display_name'));
                    appendChanged('category_id', this.form.category_id, this.originalValue('category_id'));
                    appendChanged('description', this.form.description, this.originalValue('description'));
                    appendChanged('cost_price', this.form.cost_price, this.originalValue('cost_price'));
                    appendChanged('selling_price', this.form.selling_price, this.originalValue('selling_price'));
                    appendChanged('discount_percentage', this.form.discount_percentage, this.originalValue('discount_percentage'));
                    appendChanged('discount_is_active', this.form.discount_is_active ? '1' : '0', this.originalValue('discount_is_active') ? '1' : '0');
                    appendChanged('discount_starts_at', this.form.discount_starts_at, this.originalValue('discount_starts_at'));
                    appendChanged('discount_ends_at', this.form.discount_ends_at, this.originalValue('discount_ends_at'));
                    appendChanged('quantity', this.form.quantity, this.originalValue('quantity'));
                    appendChanged('is_visible_online', this.form.is_visible_online ? '1' : '0', this.originalValue('is_visible_online') ? '1' : '0');
                    appendChanged('is_bulky', this.form.is_bulky ? '1' : '0', this.originalValue('is_bulky') ? '1' : '0');
                    appendChanged('stock_status', this.form.stock_status, this.originalValue('stock_status'));
                    appendChanged('barcodes_text', this.form.barcodes_text, this.originalBarcodesText());

                    // Image: only ever sent when the admin actually changed it.
                    if (!this.editingItem || this.imageChanged) {
                        formData.append('image_url', this.form.image_url || '');
                    }

                    // Edit mode with no actual changes: nothing to save.
                    if (this.editingItem && !this.imageChanged
                        && [...formData.keys()].filter(k => ['_token', '_method'].indexOf(k) === -1).length === 0) {
                        this.closeModal();
                        return;
                    }

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData,
                    });

                    let data = null;
                    try { data = await response.json(); } catch (e) {}

                    if (response.ok && data && data.success) {
                        window.location.reload();
                    } else {
                        alert(this.describeError(response, data));
                    }
                } catch (err) {
                    alert('Network error. Please try again.');
                }
                this.saving = false;
            },

            originalValue(key) {
                if (!this.editingItem) return null;
                return this.editingItem[key];
            },

            originalBarcodesText() {
                if (!this.editingItem) return '';
                return (this.editingItem.barcodes || []).map(b => b.barcode_value || b).join(', ');
            },

            changed(name, value, original) {
                if (!this.editingItem) return true;

                if (/_(starts_at|ends_at|_at)$/.test(name)) {
                    return this.normalizeDateTime(value) !== this.normalizeDateTime(original);
                }

                const NUMERIC_FIELDS = ['category_id', 'cost_price', 'selling_price', 'quantity', 'discount_percentage'];
                const a = (value === null || value === undefined) ? '' : String(value).trim();
                const b = (original === null || original === undefined) ? '' : String(original).trim();
                if (NUMERIC_FIELDS.includes(name) && a !== '' && b !== '') {
                    const na = Number(a), nb = Number(b);
                    if (!isNaN(na) && !isNaN(nb)) return na !== nb;
                }
                return a !== b;
            },

            normalizeDateTime(v) {
                if (!v) return '';
                const d = new Date(v);
                if (isNaN(d.getTime())) return String(v).trim();
                const pad = n => String(n).padStart(2, '0');
                return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            },

            deleteItem(id) {
                this.deleteId = id;
                this.showDeleteConfirm = true;
                this.$nextTick(() => lucide.createIcons());
            },

            async confirmDelete() {
                try {
                    const response = await fetch(`/admin/items/${this.deleteId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (data.success) window.location.reload();
                } catch (err) {
                    alert('An error occurred.');
                }
                this.showDeleteConfirm = false;
            },

            toggleSelect(id, event) {
                if (event.target.checked) {
                    if (!this.selectedIds.includes(id)) this.selectedIds.push(id);
                } else {
                    this.selectedIds = this.selectedIds.filter(i => i !== id);
                }
            },

            toggleAll(event) {
                const checked = event.target.checked;
                document.querySelectorAll('.item-checkbox').forEach(cb => {
                    cb.checked = checked;
                    const val = parseInt(cb.value);
                    if (checked && !this.selectedIds.includes(val)) {
                        this.selectedIds.push(val);
                    }
                });
                if (!checked) this.selectedIds = [];
            },

            async bulkAction(action) {
                if (!this.selectedIds.length) return;
                const payload = { action, ids: this.selectedIds };
                if (action === 'change_category') {
                    if (!this.bulkCategory) return alert('Please select a category.');
                    payload.category_id = this.bulkCategory;
                }
                try {
                    const response = await fetch('/admin/items/bulk', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json();
                    if (data.success) window.location.reload();
                } catch (err) {
                    alert('An error occurred.');
                }
            },

            async toggleAdminFavorite(itemId, event) {
                event.stopPropagation();
                try {
                    const response = await fetch(`/admin/items/${itemId}/toggle-favorite`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (data.success) window.location.reload();
                } catch (err) {
                    alert('An error occurred.');
                }
            },
        };
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
