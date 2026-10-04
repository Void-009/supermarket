<div x-data="priceCheckerModal()" x-init="init()" x-cloak>
    {{-- Floating Button --}}
    <button @click="open = true" class="w-12 h-12 bg-emerald-500 hover:bg-emerald-600 rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 group"
        style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);"
        title="{{ __('messages.price_checker') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
        </svg>
        <span class="absolute -top-10 right-0 bg-gray-900 text-white text-xs font-medium px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">{{ __('messages.price_checker') }}</span>
    </button>

    {{-- Modal --}}
    <div x-show="open" @keydown.escape.window="open = false" class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-3xl max-h-[85vh] flex flex-col overflow-hidden z-10">
            {{-- Header --}}
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <i data-lucide="search" class="w-5 h-5 text-emerald-500"></i>
                    <h3 class="font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.price_checker') }}</h3>
                </div>
                <button @click="open = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Search --}}
            <div class="px-4 sm:px-6 py-3 border-b border-gray-100 shrink-0">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="query" @input.debounce.300ms="search()" @keydown.enter.prevent="search()" @focus="openSearch = true"
                        placeholder="{{ __('messages.search_by_name_id_barcode') }}"
                        class="w-full pl-9 pr-14 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                    <button x-show="!loading && !scannerActive && query.length === 0" @click="scanBarcode()" x-cloak class="absolute right-10 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors" :title="'{{ __('messages.scan_barcode') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                    </button>
                    <button x-show="query.length > 0" @click="clearAll()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Scanner --}}
            <div x-show="scannerActive" x-cloak class="px-4 sm:px-6 py-3 border-b border-gray-100">
                <div id="modal-price-scanner" class="md-viewport max-w-sm mx-auto" style="aspect-ratio: 4 / 3; min-height: 220px;"></div>
                <button @click="stopScanner()" class="mt-2 text-xs text-red-500 hover:text-red-700 font-medium">{{ __('messages.stop_scanner') }}</button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto">
                {{-- Loading --}}
                <div x-show="loading" class="text-center py-12">
                    <i data-lucide="loader-2" class="w-6 h-6 mx-auto text-emerald-500 animate-spin mb-2"></i>
                    <p class="text-xs text-gray-500">{{ __('messages.searching') }}</p>
                </div>

                {{-- Empty --}}
                <div x-show="!loading && searched && results.length === 0" class="text-center py-12">
                    <i data-lucide="search-x" class="w-10 h-10 mx-auto text-gray-300 mb-2"></i>
                    <p class="text-sm text-gray-500">{{ __('messages.no_items_found') }}</p>
                </div>

                {{-- Results --}}
                <div x-show="results.length > 0" x-cloak class="divide-y divide-gray-100">
                    <template x-for="item in results" :key="item.id">
                        <div class="px-4 sm:px-6 py-3 flex flex-wrap items-center gap-x-3 gap-y-2 hover:bg-gray-50 transition-colors">
                            <input type="checkbox" :checked="!!selectedItems[item.id]"
                                @change="toggleItem(item)"
                                class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500 shrink-0">
                            <template x-if="item.image_url">
                                <img :src="'/storage/' + item.image_url" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0">
                            </template>
                            <template x-if="!item.image_url">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 shrink-0">
                                    <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
                                </div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></p>
                                <p class="text-xs text-gray-400 truncate" x-text="item.category"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <template x-if="item.discount_price">
                                    <div>
                                        <span class="text-sm font-semibold text-emerald-600" x-text="'₦' + Number(item.discount_price).toLocaleString()"></span>
                                        <span class="text-xs text-gray-400 line-through ml-1" x-text="'₦' + Number(item.selling_price).toLocaleString()"></span>
                                    </div>
                                </template>
                                <template x-if="!item.discount_price">
                                    <span class="text-sm font-semibold text-gray-900" x-text="'₦' + Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                </template>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button @click="decrement(item)" :disabled="!selectedItems[item.id]"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors disabled:opacity-30 text-xs">−</button>
                                <span class="w-8 text-center text-xs font-medium" x-text="selectedItems[item.id] || 0"></span>
                                <button @click="increment(item)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors text-xs">+</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Initial state --}}
                <div x-show="!loading && !searched && results.length === 0" class="text-center py-12">
                    <i data-lucide="search" class="w-10 h-10 mx-auto text-gray-200 mb-2"></i>
                    <p class="text-sm text-gray-400">{{ __('messages.search_for_item') }}</p>
                </div>
            </div>

            {{-- Footer --}}
            <div x-show="results.length > 0 || Object.keys(selectedItems).length > 0" class="px-4 sm:px-6 py-3 border-t border-gray-100 bg-gray-50 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <p class="text-xs text-gray-500 min-w-0 break-words">
                    <span x-text="Object.keys(selectedItems).length"></span> {{ __('messages.selected') }}
                    <span x-show="Object.keys(selectedItems).length > 0">— ₦<span x-text="totalSelected().toLocaleString(undefined, {minimumFractionDigits:2})"></span></span>
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <button @click="clearAll()" class="px-3 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg transition-colors whitespace-nowrap">{{ __('messages.clear') }}</button>
                    <button @click="addToCart()" :disabled="Object.keys(selectedItems).length === 0 || addingToCart"
                        class="px-4 py-2 text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                        <span x-show="!addingToCart">{{ __('messages.add_to_cart') }}</span>
                        <span x-show="addingToCart">{{ __('messages.adding') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('priceCheckerModal', () => ({
                open: false,
                query: '',
                results: [],
                loading: false,
                searched: false,
                scannerActive: false,
                selectedItems: {},
                addingToCart: false,
                csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',

                init() {
                    if (window.lucide) lucide.createIcons();
                },

                search() {
                    const q = this.query.trim();
                    if (q.length < 1) { this.results = []; this.searched = false; return; }
                    this.loading = true;
                    this.searched = true;
                    fetch('/api/price-checker/search?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.results = data.items || [];
                        this.loading = false;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    })
                    .catch(() => { this.loading = false; });
                },

                async scanBarcode() {
                    if (this.scannerActive) { this.stopScanner(); return; }
                    this.scannerActive = true;
                    try {
                        const text = await window.scanBarcode({ containerId: 'modal-price-scanner' });
                        const scanText = window.normalizeScanText ? window.normalizeScanText(text) : '';
                        const s = (typeof scanText === 'string') ? scanText.trim() : '';
                        if (s) { this.query = s; this.search(); }
                        this.scannerActive = false;
                    } catch(e) {
                        if (e !== 'Cancelled') console.error('[PriceCheckerModal] Scanner error:', e);
                        this.scannerActive = false;
                    }
                },

                stopScanner() {
                    if (window.stopScanBarcode) window.stopScanBarcode();
                    else if (window._scanAbort) window._scanAbort.abort();
                    this.scannerActive = false;
                },

                toggleItem(item) {
                    if (this.selectedItems[item.id]) { delete this.selectedItems[item.id]; }
                    else { this.selectedItems[item.id] = 1; }
                },

                increment(item) {
                    if (!this.selectedItems[item.id]) { this.selectedItems[item.id] = 1; return; }
                    this.selectedItems[item.id] = Math.min(this.selectedItems[item.id] + 1, 999);
                },

                decrement(item) {
                    if (!this.selectedItems[item.id]) return;
                    if (this.selectedItems[item.id] <= 1) { delete this.selectedItems[item.id]; }
                    else { this.selectedItems[item.id]--; }
                },

                clearAll() { this.selectedItems = {}; this.results = []; this.query = ''; this.searched = false; },

                totalSelected() {
                    let total = 0;
                    this.results.forEach(item => {
                        const qty = this.selectedItems[item.id];
                        if (qty) total += (item.discount_price || item.selling_price) * qty;
                    });
                    return total;
                },

                addToCart() {
                    const entries = Object.entries(this.selectedItems);
                    if (entries.length === 0) return;
                    this.addingToCart = true;
                    let idx = 0;
                    let lastCount = null;
                    const next = () => {
                        if (idx >= entries.length) {
                            this.addingToCart = false;
                            if (window.showToast) showToast('Items added to cart!', 'success');
                            window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: lastCount } }));
                            return;
                        }
                        const [itemId, qty] = entries[idx];
                        fetch('/cart/add', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                            body: JSON.stringify({ item_id: parseInt(itemId), quantity: qty }),
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.cart_count !== undefined) {
                                lastCount = data.cart_count;
                                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: lastCount } }));
                            }
                        })
                        .catch(() => {})
                        .finally(() => { idx++; next(); });
                    };
                    next();
                },
            }));
        });
    </script>
</div>
