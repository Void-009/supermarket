@extends('layouts.admin')

@section('title', 'Item Reviews')
@section('header', 'Item Reviews')

@section('content')
<div x-data="itemReviews()" x-init="init()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Item Reviews</h2>
            <p class="text-sm text-gray-500 mt-1">Items recently created or updated by the POS sync agent — review and correct prices, quantity and parent/child relationships.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.items.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Items
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-gray-500 font-medium">Synced (window)</p>
                    <p class="text-lg font-bold text-gray-900" x-text="totalItems">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="package-plus" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-emerald-600 font-medium">New</p>
                    <p class="text-lg font-bold text-emerald-700" x-text="newCount">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="edit-3" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-amber-600 font-medium">Updated</p>
                    <p class="text-lg font-bold text-amber-700" x-text="updatedCount">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-rose-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="parentheses" class="w-4 h-4 text-rose-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-rose-600 font-medium">Variations</p>
                    <p class="text-lg font-bold text-rose-700" x-text="variationCount">0</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-col lg:flex-row gap-3">
            <div class="flex-1 relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                <input type="text" x-model="search" @input.debounce.350ms="goToPage(1)"
                       placeholder="Search by name, POS ID or barcode..."
                       class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-gray-50 focus:bg-white">
            </div>
            <div class="sm:w-56">
                <select x-model="categoryId" @change="goToPage(1)" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-44">
                <select x-model="days" @change="goToPage(1)" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                    <option value="1">Last 24 hours</option>
                    <option value="3">Last 3 days</option>
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30">Last 30 days</option>
                </select>
            </div>
        </div>

        {{-- Type tabs --}}
        <div class="flex flex-wrap items-center gap-2 mt-4">
            <button @click="type = ''; goToPage(1)" class="px-4 py-2 text-xs font-semibold rounded-full transition-colors" :class="type === '' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">All</button>
            <button @click="type = 'new'; goToPage(1)" class="px-4 py-2 text-xs font-semibold rounded-full transition-colors" :class="type === 'new' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">New</button>
            <button @click="type = 'updated'; goToPage(1)" class="px-4 py-2 text-xs font-semibold rounded-full transition-colors" :class="type === 'updated' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Updated</button>
            <span class="ml-auto text-xs text-gray-400" x-text="totalItems + ' item(s)'"></span>
        </div>
    </div>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <i data-lucide="loader-2" class="w-8 h-8 text-emerald-500 animate-spin mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">Loading items...</p>
        </div>
    </template>

    {{-- Empty --}}
    <template x-if="!loading && items.length === 0">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <i data-lucide="check-circle-2" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No items in this window.</p>
            <p class="text-xs text-gray-400 mt-1">Items synced by the POS agent within the selected time range will appear here.</p>
        </div>
    </template>

    {{-- Table --}}
    <template x-if="!loading && items.length > 0">
        <div>
            <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-2 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider w-14">Type</th>
                                <th class="px-1 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider w-8"></th>
                                <th class="px-2 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider w-[140px]">Item</th>
                                <th class="px-2 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider hidden lg:table-cell w-20">Category</th>
                                <th class="px-2 py-3 text-center font-medium text-gray-500 text-xs uppercase tracking-wider w-20">Price</th>
                                <th class="px-2 py-3 text-center font-medium text-gray-500 text-xs uppercase tracking-wider w-16 hidden sm:table-cell">Cost</th>
                                <th class="px-2 py-3 text-center font-medium text-gray-500 text-xs uppercase tracking-wider w-12">Qty</th>
                                <th class="px-2 py-3 text-center font-medium text-gray-500 text-xs uppercase tracking-wider w-14 hidden lg:table-cell">Status</th>
                                <th class="px-2 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider w-20">Synced</th>
                                <th class="px-2 py-3 text-right font-medium text-gray-500 text-xs uppercase tracking-wider w-14">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="item in items" :key="item.id">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-2 py-2">
                                        <span x-show="item.change_type === 'new'" class="inline-flex items-center px-1 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">New</span>
                                        <span x-show="item.change_type === 'updated'" class="inline-flex items-center px-1 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200">Upd</span>
                                    </td>
                                    <td class="px-1 py-2">
                                        <template x-if="item.image_url">
                                            <img :src="'/storage/' + item.image_url" class="w-7 h-7 rounded-lg object-cover border border-gray-200" :alt="item.display_name || item.name">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                                                <i data-lucide="image" class="w-3 h-3 text-gray-400"></i>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-2 py-2 w-[140px]">
                                        <div class="font-medium text-gray-900 truncate text-xs leading-tight" x-text="item.display_name || item.name"></div>
                                        <div class="text-[10px] text-gray-400 truncate leading-tight">
                                            <code class="font-mono" x-text="item.pos_item_id"></code>
                                            <template x-if="item.parent_name">
                                                <span class="ml-0.5 text-emerald-600">→<span x-text="item.parent_name" class="truncate inline-block max-w-[60px] align-bottom"></span></span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 text-gray-600 text-xs truncate max-w-[80px] hidden lg:table-cell" x-text="item.category_name || 'N/A'"></td>
                                    <td class="px-2 py-2 text-center font-medium text-gray-900 text-xs whitespace-nowrap" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></td>
                                    <td class="px-2 py-2 text-center text-gray-500 text-xs whitespace-nowrap hidden sm:table-cell" x-text="'₦' + Number(item.cost_price).toLocaleString(undefined, {minimumFractionDigits:2})"></td>
                                    <td class="px-2 py-2 text-center">
                                        <span class="font-medium text-xs" :class="item.quantity > 0 ? 'text-emerald-700' : 'text-red-600'" x-text="Number(item.quantity).toLocaleString()"></span>
                                    </td>
                                    <td class="px-2 py-2 text-center hidden lg:table-cell">
                                        <span x-show="item.is_visible_online" class="inline-flex items-center px-1 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                        <span x-show="!item.is_visible_online" class="inline-flex items-center px-1 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">Hidden</span>
                                    </td>
                                    <td class="px-2 py-2 text-[10px] text-gray-500 whitespace-nowrap truncate max-w-[80px]" x-text="item.last_synced_at_label || ''"></td>
                                    <td class="px-2 py-2 text-right">
                                        <button @click="openEdit(item)" class="inline-flex items-center gap-0.5 px-1.5 py-1 text-[10px] font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
                                            <i data-lucide="pencil" class="w-2.5 h-2.5"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden space-y-3">
                <template x-for="item in items" :key="item.id">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-start gap-3">
                            <template x-if="item.image_url">
                                <img :src="'/storage/' + item.image_url" class="w-12 h-12 rounded-lg object-cover border border-gray-200 flex-shrink-0" :alt="item.display_name || item.name">
                            </template>
                            <template x-if="!item.image_url">
                                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 flex-shrink-0">
                                    <i data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                </div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 text-sm truncate" x-text="item.display_name || item.name"></div>
                                        <code class="text-[11px] bg-gray-100 px-1.5 py-0.5 rounded font-mono text-gray-600 mt-1 inline-block max-w-full break-all" x-text="item.pos_item_id"></code>
                                        <template x-if="item.parent_name">
                                            <span class="text-[11px] text-emerald-600 ml-1">→ <span x-text="item.parent_name"></span></span>
                                        </template>
                                    </div>
                                    <span x-show="item.change_type === 'new'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 flex-shrink-0">New</span>
                                    <span x-show="item.change_type === 'updated'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">Updated</span>
                                </div>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                        <span class="text-gray-400 text-xs ml-1" x-text="'cost ₦' + Number(item.cost_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                    </div>
                                    <span class="text-sm font-medium" :class="item.quantity > 0 ? 'text-emerald-700' : 'text-red-600'" x-text="Number(item.quantity).toLocaleString() + ' in stock'"></span>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-[11px] text-gray-400" x-text="item.last_synced_at_label || ''"></span>
                                    <button @click="openEdit(item)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
                                        <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Pagination --}}
            <div x-show="lastPage > 1" class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3">
                <p class="text-sm text-gray-500" x-text="'Showing ' + (((currentPage - 1) * perPage) + 1) + '–' + Math.min(currentPage * perPage, totalItems) + ' of ' + totalItems"></p>
                <div class="flex items-center gap-1">
                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="currentPage <= 1 ? 'text-gray-400 bg-gray-50' : 'text-gray-700 bg-gray-100 hover:bg-gray-200'">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                    <template x-for="p in paginationRange()" :key="p">
                        <button x-show="p !== '...'" @click="goToPage(p)"
                                class="w-9 h-9 text-sm font-medium rounded-lg transition-colors"
                                :class="p === currentPage ? 'bg-emerald-500 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100'"
                                x-text="p"></button>
                        <span x-show="p === '...'" class="px-1 text-gray-400" x-text="'...'"></span>
                    </template>
                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage"
                            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="currentPage >= lastPage ? 'text-gray-400 bg-gray-50' : 'text-gray-700 bg-gray-100 hover:bg-gray-200'">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Edit Modal --}}
    <div x-show="showEdit" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="closeEdit()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-4 sm:px-6 py-4 flex items-center justify-between gap-3 rounded-t-2xl z-10">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Review Item</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono" x-text="editingItem.pos_item_id"></p>
                </div>
                <button @click="closeEdit()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form @submit.prevent="saveItem()" class="p-4 sm:p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Display Name</label>
                    <input x-model="form.display_name" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="Custom display name (leave blank to use default)">
                    <p class="text-xs text-gray-400 mt-1">Default: <span class="font-medium text-gray-600" x-text="editingItem.name || ''"></span></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                    <select x-model="form.category_id" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">Select category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Selling Price (₦)</label>
                        <input x-model="form.selling_price" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Cost Price (₦)</label>
                        <input x-model="form.cost_price" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Quantity</label>
                        <input x-model="form.quantity" type="number" min="0" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Visible Online</label>
                        <label class="relative inline-flex items-center cursor-pointer mt-2">
                            <input type="checkbox" x-model="form.is_visible_online" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            <span class="ml-3 text-sm text-gray-600" x-text="form.is_visible_online ? 'Active' : 'Hidden'"></span>
                        </label>
                    </div>
                </div>

                {{-- Parent / child relationship --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Parent Item</label>
                    <p class="text-xs text-gray-400 mb-2">Link this item as a variation of a parent. Leave empty to keep it standalone.</p>
                    <div class="relative" @click.outside="parentOpen = false">
                        <input type="text"
                               x-model="parentSearch"
                               @focus="parentOpen = true"
                               @input.debounce.250ms="searchParents()"
                               @keydown.enter.prevent="applyParentFromSearch()"
                               placeholder="Search a parent item (name or POS ID)..."
                               class="w-full pl-4 pr-16 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-gray-50 focus:bg-white">
                        <button type="button" @click="clearParent()" x-show="form.parent_item_number" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-red-500 hover:text-red-700 underline font-medium">Remove</button>
                        <div x-show="parentOpen && parentResults.length > 0" x-cloak class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-gray-100 py-1 z-20 max-h-56 overflow-y-auto">
                            <template x-for="p in parentResults" :key="p.pos_item_id">
                                <button type="button" @click="selectParent(p)" class="w-full text-left px-4 py-2.5 text-sm hover:bg-emerald-50 transition-colors flex items-center justify-between gap-2">
                                    <span class="truncate" x-text="p.display_name || p.name"></span>
                                    <code class="font-mono text-[10px] text-gray-400 flex-shrink-0" x-text="p.pos_item_id"></code>
                                </button>
                            </template>
                        </div>
                        <div x-show="parentOpen && !loadingParents && parentResults.length === 0 && parentSearch.length > 0" x-cloak class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-gray-100 py-3 px-4 z-20">
                            <p class="text-xs text-gray-400">No parent items found.</p>
                        </div>
                    </div>
                    <p x-show="selectedParentLabel" class="text-xs text-emerald-700 mt-1.5 font-medium" x-text="'Parent: ' + selectedParentLabel"></p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeEdit()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!saving">Save Changes</span>
                        <span x-show="saving" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function itemReviews() {
    return {
        loading: true,
        saving: false,
        items: [],
        totalItems: 0,
        newCount: 0,
        updatedCount: 0,
        variationCount: 0,
        currentPage: 1,
        perPage: 25,
        lastPage: 1,

        search: '',
        categoryId: '',
        days: 7,
        type: '',

        showEdit: false,
        editingItem: { pos_item_id: '', name: '' },
        form: {
            display_name: '',
            category_id: '',
            selling_price: '',
            cost_price: '',
            quantity: 0,
            parent_item_number: '',
            is_visible_online: true,
        },
        parentSearch: '',
        parentResults: [],
        parentOpen: false,
        loadingParents: false,
        selectedParentLabel: '',

        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        init() {
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            this.fetchItems();
        },

        async fetchItems() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                days: this.days,
            });
            if (this.search) params.set('search', this.search);
            if (this.categoryId) params.set('category_id', this.categoryId);
            if (this.type) params.set('type', this.type);

            try {
                const res = await fetch('{{ route("admin.items.reviews.data") }}?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.items = data.items;
                    this.totalItems = data.total;
                    this.currentPage = data.current_page;
                    this.lastPage = data.last_page;
                    this.newCount = this.items.filter(i => i.change_type === 'new').length;
                    this.updatedCount = this.items.filter(i => i.change_type === 'updated').length;
                    this.variationCount = this.items.filter(i => i.parent_name).length;
                }
            } catch (e) {
                this.items = [];
                this.totalItems = 0;
            }
            this.loading = false;
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        goToPage(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.fetchItems();
        },

        paginationRange() {
            const total = this.lastPage;
            const current = this.currentPage;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const range = [];
            if (current <= 4) {
                for (let i = 1; i <= 5; i++) range.push(i);
                range.push('...', total);
            } else if (current >= total - 3) {
                range.push(1, '...');
                for (let i = total - 4; i <= total; i++) range.push(i);
            } else {
                range.push(1, '...');
                for (let i = current - 1; i <= current + 1; i++) range.push(i);
                range.push('...', total);
            }
            return range;
        },

        openEdit(item) {
            this.editingItem = item;
            this.form = {
                display_name: item.display_name && item.display_name !== item.name ? item.display_name : '',
                category_id: item.category_id || '',
                selling_price: item.selling_price,
                cost_price: item.cost_price,
                quantity: item.quantity,
                parent_item_number: item.parent_item_number || '',
                is_visible_online: item.is_visible_online,
            };
            this.selectedParentLabel = item.parent_name || '';
            this.parentSearch = item.parent_name || item.parent_item_number || '';
            this.parentResults = [];
            this.parentOpen = false;
            this.showEdit = true;
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        closeEdit() {
            this.showEdit = false;
            this.parentOpen = false;
            this.parentResults = [];
        },

        async searchParents() {
            this.loadingParents = true;
            this.parentOpen = true;
            try {
                const res = await fetch('{{ route("admin.items.reviews.parents") }}?search=' + encodeURIComponent(this.parentSearch), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.parentResults = data.items || [];
            } catch (e) {
                this.parentResults = [];
            }
            this.loadingParents = false;
        },

        selectParent(p) {
            this.form.parent_item_number = p.pos_item_id;
            this.parentSearch = p.display_name || p.name;
            this.selectedParentLabel = (p.display_name || p.name) + ' (' + p.pos_item_id + ')';
            this.parentOpen = false;
        },

        applyParentFromSearch() {
            if (this.parentResults.length > 0) {
                this.selectParent(this.parentResults[0]);
            }
        },

        clearParent() {
            this.form.parent_item_number = '';
            this.parentSearch = '';
            this.selectedParentLabel = '';
            this.parentResults = [];
        },

        async saveItem() {
            if (!this.editingItem || !this.editingItem.id) return;
            this.saving = true;
            try {
                const res = await fetch('{{ route("admin.items.reviews.update", "__ID__") }}'.replace('__ID__', this.editingItem.id), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        display_name: this.form.display_name || '',
                        category_id: this.form.category_id || null,
                        selling_price: this.form.selling_price,
                        cost_price: this.form.cost_price,
                        quantity: this.form.quantity,
                        parent_item_number: this.form.parent_item_number || '',
                        is_visible_online: this.form.is_visible_online ? 1 : 0,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Item updated.', 'success');
                    this.showEdit = false;
                    this.fetchItems();
                } else {
                    showToast(data.message || 'Could not update item.', 'error');
                }
            } catch (e) {
                showToast('An error occurred. Please try again.', 'error');
            }
            this.saving = false;
        },
    };
}
</script>
@endpush
