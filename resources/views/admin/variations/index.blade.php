@extends('layouts.admin')

@section('title', 'Variation Quantities')
@section('header', 'Variation Quantities')

@section('content')
<div x-data="variationsPage()" x-init="init()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Variation Quantities</h2>
            <p class="text-sm text-gray-500 mt-1">Manage quantities for items with variation IDs (e.g. <code class="bg-gray-100 px-1 rounded text-xs">1070@3467</code>).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.items.variations.duplicates') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 rounded-xl transition-colors shadow-sm">
                <i data-lucide="copy" class="w-4 h-4"></i> Review Duplicates
            </a>
            <a href="{{ route('admin.items.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Items
            </a>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="package" class="w-4 h-4 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-gray-500 font-medium">Total</p>
                    <p class="text-lg font-bold text-gray-900" x-text="totalItems">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="eye" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-emerald-600 font-medium">Visible</p>
                    <p class="text-lg font-bold text-emerald-700" x-text="visibleCount">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="eye-off" class="w-4 h-4 text-red-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-red-600 font-medium">Hidden</p>
                    <p class="text-lg font-bold text-red-700" x-text="hiddenCount">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="edit-3" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-amber-600 font-medium">Changed</p>
                    <p class="text-lg font-bold text-amber-700" x-text="dirtyCount">0</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar: Search + Bulk Actions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        {{-- Search Bar --}}
        <div class="mb-3">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
                <input type="text" x-model="search" @input.debounce.300ms="goToPage(1)"
                       placeholder="Search by name or POS ID..."
                       class="w-full pl-10 pr-16 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors bg-gray-50 focus:bg-white">
                <button x-show="!scannerActive && search.length === 0" @click="startScanner()" type="button"
                        class="absolute right-11 top-1/2 -translate-y-1/2 text-gray-400 hover:text-amber-500 transition-colors" title="Scan barcode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                </button>
                <button x-show="search.length > 0" @click="search = ''; goToPage(1)"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Scanner Viewfinder --}}
            <div x-show="scannerActive" x-cloak class="mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 p-4 max-w-md">
                <div class="flex items-center gap-3 mb-3">
                    <svg class="animate-spin h-5 w-5 text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="text-sm text-gray-600">Point camera at barcode...</p>
                </div>
                <div id="admin-variations-search-scanner" class="md-viewport" style="aspect-ratio: 4 / 3; min-height: 220px;"></div>
                <button @click="stopScanner()" class="mt-3 w-full text-center text-xs text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <template x-if="selectedIds.length > 0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-amber-700" x-text="selectedIds.length + ' selected on this page'"></span>
                        <button @click="selectedIds = []" class="text-xs text-amber-600 hover:text-amber-800 underline font-medium">Deselect</button>
                    </div>
                </template>
                <template x-if="selectedIds.length === 0">
                    <span class="text-sm text-gray-500" x-text="totalItems + ' item(s) total' + (search ? ' (filtered)' : '')"></span>
                </template>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-500 font-medium whitespace-nowrap">Set qty to:</label>
                    <input type="number" min="0" x-model.number="bulkQty" class="w-20 px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none" placeholder="0">
                </div>
                <button @click="applyBulkQty()" :disabled="bulkQty === null || bulkQty === '' || selectedIds.length === 0" class="px-3 py-1.5 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Apply to Selected</button>
                <button @click="selectAll()" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Select Page</button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <i data-lucide="loader-2" class="w-8 h-8 text-amber-500 animate-spin mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">Loading variation items...</p>
        </div>
    </template>

    {{-- Empty State --}}
    <template x-if="!loading && items.length === 0 && !search">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <i data-lucide="package-open" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No variation items found.</p>
            <p class="text-xs text-gray-400 mt-1">Items with @ in their pos_item_id will appear here.</p>
        </div>
    </template>

    <template x-if="!loading && items.length === 0 && search">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <i data-lucide="search-x" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No items match "<span x-text="search" class="font-medium"></span>"</p>
            <button @click="search = ''; goToPage(1)" class="mt-3 text-sm text-amber-600 hover:text-amber-800 font-medium underline">Clear search</button>
        </div>
    </template>

    {{-- Desktop Table --}}
    <template x-if="!loading && items.length > 0">
        <div>
            {{-- Desktop --}}
            <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[700px]">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 w-12">
                                    <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500 cursor-pointer">
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 text-xs uppercase tracking-wider">POS ID</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 text-xs uppercase tracking-wider w-32">Quantity</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 text-xs uppercase tracking-wider w-24">Status</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 text-xs uppercase tracking-wider w-32">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, idx) in items" :key="item.id">
                                <tr :class="[isDirty(item.id) ? 'bg-amber-50/60' : '', selectedIds.includes(item.id) ? 'bg-blue-50/30' : '']" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 w-12">
                                        <input type="checkbox" :value="item.id" x-model="selectedIds" class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-3 max-w-[200px]">
                                        <div class="font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></div>
                                        <div class="text-[11px] text-gray-400 truncate" x-show="item.display_name && item.display_name !== item.name" x-text="item.name"></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono text-gray-700" x-text="item.pos_item_id"></code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="decrementQty(idx)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors text-sm font-bold flex-shrink-0">&minus;</button>
                                            <input type="number" min="0" x-model.number="item.quantity" @input="trackDirty(item.id)" class="w-16 px-2 py-1.5 text-sm text-center border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors font-medium">
                                            <button @click="incrementQty(idx)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors text-sm font-bold flex-shrink-0">+</button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <template x-if="item.quantity > 0">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Visible</span>
                                        </template>
                                        <template x-if="item.quantity <= 0">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">Hidden</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 whitespace-nowrap" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-3">
                <template x-for="(item, idx) in items" :key="item.id">
                    <div :class="[isDirty(item.id) ? 'border-amber-300 bg-amber-50/40' : 'border-gray-200']" class="bg-white rounded-xl shadow-sm border p-4">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" :value="item.id" x-model="selectedIds" class="mt-1 w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-500 cursor-pointer flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900 text-sm truncate" x-text="item.display_name || item.name"></div>
                                        <code class="text-[11px] bg-gray-100 px-1.5 py-0.5 rounded font-mono text-gray-600 mt-1 inline-block max-w-full break-all" x-text="item.pos_item_id"></code>
                                    </div>
                                    <template x-if="item.quantity > 0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex-shrink-0">Visible</span>
                                    </template>
                                    <template x-if="item.quantity <= 0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200 flex-shrink-0">Hidden</span>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="decrementQty(idx)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors text-base font-bold">&minus;</button>
                                        <input type="number" min="0" x-model.number="item.quantity" @input="trackDirty(item.id)" class="w-16 px-2 py-1.5 text-sm text-center border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors font-medium">
                                        <button @click="incrementQty(idx)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors text-base font-bold">+</button>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 whitespace-nowrap" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
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
                                :class="p === currentPage ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100'"
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

    {{-- Fixed Save Bar --}}
    <div x-show="dirtyCount > 0" x-cloak x-transition
         class="fixed bottom-0 left-0 right-0 lg:left-64 z-40 bg-white border-t border-amber-200 shadow-lg">
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 max-w-7xl mx-auto">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="edit-3" class="w-4 h-4 text-amber-600"></i>
                    </div>
                    <div>
                        <span class="text-sm font-semibold text-gray-900" x-text="dirtyCount + ' item(s) modified'"></span>
                        <template x-if="saveSuccess">
                            <span class="text-sm text-emerald-600 font-medium ml-2">Saved!</span>
                        </template>
                        <template x-if="saveError">
                            <span class="text-sm text-red-600 font-medium ml-2" x-text="saveError"></span>
                        </template>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="resetAll()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Discard</button>
                    <button @click="saveAll()" :disabled="saving" class="px-6 py-2.5 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!saving" class="flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Save All Changes
                        </span>
                        <span x-show="saving" x-cloak class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom spacer when save bar is visible --}}
    <div x-show="dirtyCount > 0" x-cloak class="h-20"></div>
</div>
@endsection

@push('scripts')
<script>
function variationsPage() {
    return {
        loading: true,
        saving: false,
        items: [],
        selectedIds: [],
        originalQuantities: {},
        dirtyMap: {},
        bulkQty: '',
        saveSuccess: false,
        saveError: '',
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        search: '',
        scannerActive: false,
        currentPage: 1,
        perPage: 200,
        totalItems: 0,
        lastPage: 1,

        get allSelected() {
            return this.items.length > 0 && this.selectedIds.length === this.items.length;
        },

        get dirtyCount() {
            return Object.keys(this.dirtyMap).length;
        },

        get visibleCount() {
            return this.items.filter(i => (this.dirtyMap[i.id] !== undefined ? this.dirtyMap[i.id] : i.quantity) > 0).length;
        },

        get hiddenCount() {
            return this.items.filter(i => (this.dirtyMap[i.id] !== undefined ? this.dirtyMap[i.id] : i.quantity) <= 0).length;
        },

        async init() {
            this.$nextTick(() => lucide.createIcons());
            await this.fetchItems();
        },

        async fetchItems() {
            this.loading = true;
            this.selectedIds = [];
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
            });
            if (this.search) params.set('search', this.search);

            try {
                const res = await fetch('{{ route("admin.items.variations") }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.totalItems = data.total;
                    this.currentPage = data.current_page;
                    this.lastPage = data.last_page;
                    this.items = data.items.map(i => {
                        const dirty = this.dirtyMap[i.id];
                        if (dirty !== undefined) {
                            i.quantity = dirty;
                        } else {
                            this.originalQuantities[i.id] = i.quantity;
                        }
                        return i;
                    });
                }
            } catch (e) {
                this.saveError = 'Failed to load variation items.';
            }
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
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

        selectAll() {
            this.selectedIds = this.items.map(i => i.id);
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = this.items.map(i => i.id);
            } else {
                this.selectedIds = [];
            }
        },

        trackDirty(id) {
            const item = this.items.find(i => i.id === id);
            if (!item) return;
            if (item.quantity !== this.originalQuantities[id]) {
                this.dirtyMap[id] = item.quantity;
            } else {
                delete this.dirtyMap[id];
            }
            this.$nextTick(() => lucide.createIcons());
        },

        isDirty(id) {
            return this.dirtyMap[id] !== undefined;
        },

        incrementQty(idx) {
            this.items[idx].quantity = (this.items[idx].quantity || 0) + 1;
            this.trackDirty(this.items[idx].id);
        },

        decrementQty(idx) {
            if (this.items[idx].quantity > 0) {
                this.items[idx].quantity--;
                this.trackDirty(this.items[idx].id);
            }
        },

        applyBulkQty() {
            if (this.bulkQty === null || this.bulkQty === '') return;
            this.items.forEach(item => {
                if (this.selectedIds.includes(item.id)) {
                    item.quantity = parseInt(this.bulkQty);
                    this.dirtyMap[item.id] = item.quantity;
                }
            });
        },

        resetAll() {
            this.dirtyMap = {};
            this.selectedIds = [];
            this.fetchItems();
        },

        async startScanner() {
            if (this.scannerActive) { this.stopScanner(); return; }
            this.scannerActive = true;
            try {
                var text = await window.scanBarcode({ containerId: 'admin-variations-search-scanner' });
                var scanText = window.normalizeScanText ? window.normalizeScanText(text) : '';
                scanText = (typeof scanText === 'string') ? scanText.trim() : '';
                if (scanText) {
                    this.search = scanText;
                    this.scannerActive = false;
                    this.goToPage(1);
                } else {
                    this.scannerActive = false;
                }
            } catch (e) {
                if (e !== 'Cancelled') console.error('[VariationsSearch] Scanner error:', e);
                this.scannerActive = false;
            }
        },

        stopScanner() {
            if (window.stopScanBarcode) window.stopScanBarcode();
            else if (window._scanAbort) window._scanAbort.abort();
            this.scannerActive = false;
        },

        async saveAll() {
            if (this.dirtyCount === 0) return;
            this.saving = true;
            this.saveSuccess = false;
            this.saveError = '';

            const changedItems = Object.entries(this.dirtyMap).map(([id, quantity]) => ({
                id: parseInt(id),
                quantity: quantity,
            }));

            try {
                const res = await fetch('{{ route("admin.items.bulk-update-quantities") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ items: changedItems }),
                });
                const data = await res.json();
                if (data.success) {
                    this.saveSuccess = true;
                    this.saveError = '';
                    changedItems.forEach(ci => {
                        this.originalQuantities[ci.id] = ci.quantity;
                    });
                    this.dirtyMap = {};
                    this.selectedIds = [];
                    setTimeout(() => { this.saveSuccess = false; }, 3000);
                    showToast(data.message, 'success');
                    this.fetchItems();
                } else {
                    this.saveError = data.message || 'Save failed.';
                    showToast(this.saveError, 'error');
                }
            } catch (e) {
                this.saveError = 'An error occurred. Please try again.';
                showToast(this.saveError, 'error');
            }
            this.saving = false;
        },
    };
}
</script>
@endpush
