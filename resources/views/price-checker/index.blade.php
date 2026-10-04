@extends('layouts.main')

@section('title', __('messages.price_checker'))

@section('content')
<div class="min-h-screen bg-gray-50" x-data="priceChecker()" x-init="init()">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.price_checker') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('messages.price_checker_desc') }}</p>
        </div>

        {{-- Search Bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6">
            <div class="relative">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                <input x-ref="searchInput" type="text" x-model="query" @input.debounce.300ms="search()" @keydown.enter.prevent="onEnter()"
                    :placeholder="mode === 'barcode' ? @json(__('messages.search_placeholder_barcode')) : (mode === 'id' ? @json(__('messages.search_placeholder_id')) : @json(__('messages.search_by_name_id_barcode')))"
                    class="w-full pl-12 pr-16 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                <span x-show="modeLabel" x-cloak class="absolute left-12 top-1/2 -translate-y-1/2 pointer-events-none">
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700" x-text="modeLabel"></span>
                </span>
                <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <button x-show="!loading && !scannerActive && query.length === 0" @click="scanBarcode()" x-cloak class="absolute right-12 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors" :title="'{{ __('messages.scan_barcode') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                </button>
                <button x-show="query.length > 0" @click="clearResults()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Suggestions --}}
            <div x-show="!loading && searched && results.length > 0" x-cloak class="mt-3 rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden">
                <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                    <template x-for="item in results" :key="item.id">
                        <button @click="addItem(item)" type="button"
                            class="w-full text-left px-4 py-2.5 hover:bg-emerald-50 transition-colors flex items-center gap-3">
                            <template x-if="item.image_url">
                                <img :src="'/storage/' + item.image_url" class="w-9 h-9 rounded-lg object-cover border border-gray-200 shrink-0">
                            </template>
                            <template x-if="!item.image_url">
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 shrink-0">
                                    <i data-lucide="package" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></p>
                                <p class="text-xs text-gray-400 truncate" x-text="item.category"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <template x-if="item.discount_price">
                                    <div>
                                        <span class="text-sm font-semibold text-emerald-600" x-text="'₦' + Number(item.discount_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                        <span class="text-xs text-gray-400 line-through ml-1" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                    </div>
                                </template>
                                <template x-if="!item.discount_price">
                                    <span class="text-sm font-semibold text-gray-900" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                </template>
                            </div>
                            <i data-lucide="plus" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                        </button>
                    </template>
                </div>
                <div class="px-4 py-2 text-xs text-gray-400 border-t border-gray-100 flex items-center gap-1.5">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">Enter</kbd>
                    {{ __('messages.press_enter_to_add') }}
                </div>
            </div>

            {{-- No results --}}
            <div x-show="!loading && searched && results.length === 0" x-cloak class="mt-3 text-sm text-gray-500">
                {{ __('messages.no_items_found') }} "<span x-text="query" class="font-medium"></span>"
            </div>
        </div>

        {{-- Scanner --}}
        <div x-show="scannerActive" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">{{ __('messages.barcode_scanner') }}</h3>
                <button @click="stopScanner()" class="text-xs text-red-500 hover:text-red-700 font-medium">{{ __('messages.stop_scanner') }}</button>
            </div>
            <div id="price-checker-scanner" class="md-viewport w-full max-w-[20rem] h-[8rem] sm:max-w-[26rem] sm:h-[11rem] lg:max-w-[34rem] lg:h-[15rem] mx-auto rounded-xl overflow-hidden"></div>
            <p class="text-xs text-gray-400 text-center mt-2">{{ __('messages.point_camera_barcode') }}</p>
        </div>

        {{-- Added Items Table --}}
        <div x-show="Object.keys(selected).length > 0" x-cloak class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <p class="text-sm text-gray-500">
                    <span x-text="Object.keys(selected).length"></span> {{ __('messages.items_selected') }}
                    <span class="text-gray-900 font-medium"> — ₦<span x-text="totalSelected().toLocaleString(undefined, {minimumFractionDigits:2})"></span></span>
                </p>
                <div class="flex items-center flex-wrap gap-3">
                    <button @click="markAll()" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">{{ __('messages.select_all') }}</button>
                    <button @click="unmarkAll()" class="text-xs text-gray-500 hover:text-gray-700 font-medium">{{ __('messages.deselect') }}</button>
                    <button x-show="Object.keys(marked).length > 0" @click="removeMarked()" class="text-xs text-red-500 hover:text-red-700 font-medium">{{ __('messages.remove_selected') }}</button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 sm:px-6 py-3 w-10"></th>
                            <th class="px-4 sm:px-6 py-3">{{ __('messages.item') }}</th>
                            <th class="px-4 sm:px-6 py-3 hidden sm:table-cell">{{ __('messages.barcode') }}</th>
                            <th class="px-4 sm:px-6 py-3 text-right">{{ __('messages.price') }}</th>
                            <th class="px-4 sm:px-6 py-3 text-center w-40">{{ __('messages.qty') }}</th>
                            <th class="px-4 sm:px-6 py-3 text-center w-20">{{ __('messages.remove') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="entry in selectedList" :key="entry.item.id">
                            <tr class="hover:bg-gray-50 transition-colors" :class="{'bg-red-50/50': marked[entry.item.id]}">
                                <td class="px-4 sm:px-6 py-3">
                                    <input type="checkbox" :checked="!!marked[entry.item.id]"
                                        @change="toggleMark(entry.item.id)"
                                        class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <template x-if="entry.item.image_url">
                                            <img :src="'/storage/' + entry.item.image_url" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0">
                                        </template>
                                        <template x-if="!entry.item.image_url">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 shrink-0">
                                                <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
                                            </div>
                                        </template>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 truncate max-w-[200px]" x-text="entry.item.display_name || entry.item.name"></p>
                                            <p class="text-xs text-gray-400" x-text="entry.item.category"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 hidden sm:table-cell">
                                    <span class="text-xs text-gray-500 font-mono" x-text="entry.item.barcodes?.join(', ') || '—'"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <template x-if="entry.item.discount_price">
                                        <div>
                                            <span class="font-semibold text-emerald-600" x-text="'₦' + Number(entry.item.discount_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                            <span class="text-xs text-gray-400 line-through ml-1" x-text="'₦' + Number(entry.item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                        </div>
                                    </template>
                                    <template x-if="!entry.item.discount_price">
                                        <span class="font-semibold text-gray-900" x-text="'₦' + Number(entry.item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                    </template>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="decrement(entry.item.id)"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 transition-colors text-sm font-medium">−</button>
                                        <input type="number" min="1" max="999"
                                            x-model.number="entry.qty"
                                            @input="validateQty(entry.item.id)"
                                            class="w-14 text-center text-sm border border-gray-200 rounded-lg py-1.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                                        <button @click="increment(entry.item.id)"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 transition-colors text-sm font-medium">+</button>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-center">
                                    <button @click="removeItem(entry.item.id)"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-red-500 hover:bg-red-50 transition-colors" :title="'{{ __('messages.remove') }}'">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Bulk Actions --}}
            <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <p class="text-sm text-gray-600">
                    <span x-text="Object.keys(selected).length"></span> {{ __('messages.items_selected') }}
                    <span class="text-gray-400">— {{ __('messages.total_label') }} ₦<span x-text="totalSelected().toLocaleString(undefined, {minimumFractionDigits:2})"></span></span>
                </p>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button @click="clearAll()" class="flex-1 sm:flex-none px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                        {{ __('messages.clear_all') }}
                    </button>
                    <button @click="addToCart()" :disabled="Object.keys(selected).length === 0 || addingToCart"
                        class="flex-1 sm:flex-none px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                        <span x-show="!addingToCart">
                            {{ __('messages.add_to_cart') }}
                        </span>
                        <span x-show="addingToCart" class="flex items-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> {{ __('messages.adding') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && !searched && Object.keys(selected).length === 0" class="text-center py-20">
            <i data-lucide="search" class="w-16 h-16 mx-auto text-gray-200 mb-4"></i>
            <h2 class="text-lg font-semibold text-gray-400 mb-1">{{ __('messages.search_for_item') }}</h2>
            <p class="text-sm text-gray-400">{{ __('messages.search_for_item_desc') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('priceChecker', () => ({
            query: '',
            results: [],
            exactMatch: false,
            loading: false,
            searched: false,
            scannerActive: false,
            selected: {},
            marked: {},
            addingToCart: false,
            lastCartCount: null,
            mode: 'text',
            _cache: new Map(),
            _abortController: null,
            csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

            init() {
                if (window.lucide) lucide.createIcons();
            },

            get selectedList() {
                return Object.values(this.selected);
            },

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

            search() {
                const q = this.query.trim();
                this.mode = this.computeMode(q);
                if (q.length < 1) { this.results = []; this.searched = false; this.exactMatch = false; return; }

                const key = this.mode + '|' + q;
                if (this._cache.has(key)) {
                    const hit = this._cache.get(key);
                    this.results = hit.items;
                    this.exactMatch = hit.exactMatch;
                    this.loading = false;
                    this.searched = true;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    return;
                }

                if (this._abortController) this._abortController.abort();
                this._abortController = new AbortController();
                this.loading = true;
                this.searched = true;

                let url = '/api/price-checker/search?q=' + encodeURIComponent(q) + '&limit=20';
                if (this.mode !== 'text') url += '&by=' + this.mode;

                return fetch(url, {
                    signal: this._abortController.signal,
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.results = data.items || [];
                    this.exactMatch = data.exact_match || false;
                    this.loading = false;
                    if (this._cache.size >= 40) this._cache.clear();
                    this._cache.set(key, { items: this.results, exactMatch: this.exactMatch });
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                })
                .catch(err => {
                    if (err.name !== 'AbortError') this.loading = false;
                });
            },

            onEnter() {
                if (this.loading) return;
                if (this.results.length) this.addItem(this.results[0]);
                else this.search();
            },

            async scanBarcode() {
                if (this.scannerActive) { this.stopScanner(); return; }
                this.scannerActive = true;
                try {
                    const text = await window.scanBarcode({ containerId: 'price-checker-scanner' });
                    const scanText = window.normalizeScanText ? window.normalizeScanText(text) : '';
                    const s = (typeof scanText === 'string') ? scanText.trim() : '';
                    if (s) {
                        this.query = s;
                        await this.search();
                        if (this.results.length) this.addItem(this.results[0]);
                    }
                } catch(e) {
                    if (e !== 'Cancelled') console.error('[PriceChecker] Scanner error:', e);
                } finally {
                    this.scannerActive = false;
                }
            },

            stopScanner() {
                if (window.stopScanBarcode) window.stopScanBarcode();
                else if (window._scanAbort) window._scanAbort.abort();
                this.scannerActive = false;
            },

            addItem(item) {
                if (this.selected[item.id]) {
                    this.selected[item.id].qty += 1;
                } else {
                    this.selected[item.id] = { item: item, qty: 1 };
                }
                this.clearResults();
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                    if (this.$refs.searchInput) this.$refs.searchInput.focus();
                });
            },

            clearResults() {
                this.query = '';
                this.results = [];
                this.searched = false;
                this.exactMatch = false;
            },

            increment(id) {
                if (!this.selected[id]) return;
                this.selected[id].qty = Math.min(this.selected[id].qty + 1, 999);
            },

            decrement(id) {
                if (!this.selected[id]) return;
                if (this.selected[id].qty <= 1) { this.removeItem(id); }
                else { this.selected[id].qty--; }
            },

            validateQty(id) {
                if (!this.selected[id]) return;
                let v = parseInt(this.selected[id].qty);
                if (isNaN(v) || v < 1) v = 1;
                if (v > 999) v = 999;
                this.selected[id].qty = v;
            },

            toggleMark(id) {
                if (this.marked[id]) delete this.marked[id];
                else this.marked[id] = true;
            },

            markAll() { Object.keys(this.selected).forEach(id => { this.marked[id] = true; }); },
            unmarkAll() { this.marked = {}; },

            removeMarked() {
                Object.keys(this.marked).forEach(id => { delete this.selected[id]; });
                this.marked = {};
            },

            removeItem(id) {
                delete this.selected[id];
                delete this.marked[id];
            },

            clearAll() {
                this.clearResults();
                this.selected = {};
                this.marked = {};
            },

            totalSelected() {
                let total = 0;
                Object.values(this.selected).forEach(entry => {
                    total += (entry.item.discount_price || entry.item.selling_price) * entry.qty;
                });
                return total;
            },

            addToCart() {
                const entries = Object.values(this.selected);
                if (entries.length === 0) return;
                this.addingToCart = true;
                let idx = 0;
                const next = () => {
                    if (idx >= entries.length) {
                        this.addingToCart = false;
                        if (window.showToast) showToast('Items added to cart!', 'success');
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: this.lastCartCount } }));
                        setTimeout(() => window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: this.lastCartCount } })), 500);
                        this.clearAll();
                        return;
                    }
                    const entry = entries[idx];
                    this._addToCart(entry.item.id, entry.qty, () => { idx++; next(); });
                };
                next();
            },

            _addToCart(itemId, qty, cb) {
                fetch('/cart/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ item_id: parseInt(itemId), quantity: qty }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.cart_count !== undefined) {
                        this.lastCartCount = data.cart_count;
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: this.lastCartCount } }));
                    }
                })
                .catch(() => {})
                .finally(() => cb());
            },
        }));
    });
</script>
@endpush
