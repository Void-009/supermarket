@extends('layouts.main')

@section('title', __('messages.shop'))


@section('content')

{{-- ============================================ --}}
{{-- PAGE HEADER WITH BACKGROUND SLIDESHOW       --}}
{{-- ============================================ --}}
<section class="relative py-12 lg:py-16 overflow-hidden" x-data="bgSlider()" x-init="init()">
    <div class="absolute inset-0">
        <template x-for="(img, idx) in images" :key="idx">
            <div :style="'background-image: url(' + img.url + '); opacity: ' + (idx === active ? '1' : '0')"
                 class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000 ease-in-out"></div>
        </template>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/85 via-slate-900/70 to-slate-900/85"></div>
    </div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-400 mb-4">
            <a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors">{{ __('messages.home') }}</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-white font-medium">{{ __('messages.shop') }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white" style="font-family:'Poppins',sans-serif;">{{ __('messages.our_products') }}</h1>
        <p class="mt-2 text-gray-400">{{ __('messages.browse_selection') }}</p>
        <div class="mt-6 flex gap-2" x-show="images.length > 1" x-cloak>
            <template x-for="(img, idx) in images" :key="'dot-'+idx">
                <button @click="go(idx)" type="button" :class="idx === active ? 'w-6 bg-white' : 'w-2 bg-white/40'" class="h-2 rounded-full transition-all hover:bg-white/60"></button>
            </template>
        </div>
    </div>
</section>

<script>
function bgSlider() {
    return {
        images: @json($slideshowImages->map(fn($img) => ['url' => asset('storage/' . $img['path'])])),
        active: 0,
        timer: null,
        init() {
            if (this.images.length > 1) {
                this.timer = setInterval(() => this.next(), 5000);
            }
        },
        next() { this.active = (this.active + 1) % this.images.length; },
        go(i) { this.active = i; clearInterval(this.timer); this.timer = setInterval(() => this.next(), 5000); },
    };
}

function shopLiveSearch() {
    var params = new URLSearchParams(window.location.search);
    return {
        query: params.get('search') || '',
        results: [],
        open: false,
        loading: false,
        activeIndex: -1,
        exactMatch: false,
        scannerActive: false,
        category: params.get('category') || '',
        _abortController: null,

        search() {
            this.activeIndex = -1;
            var q = this.query.trim();
            if (q.length < 1) { this.results = []; this.open = false; this.exactMatch = false; return; }
            if (this._abortController) this._abortController.abort();
            this._abortController = new AbortController();
            this.loading = true;
            this.open = true;

            var url = '{{ route("api.search") }}?q=' + encodeURIComponent(q);
            if (this.category) url += '&category=' + encodeURIComponent(this.category);

            fetch(url, {
                signal: this._abortController.signal,
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                this.results = data.items || [];
                this.exactMatch = data.exact_match || false;
                this.loading = false;
            }.bind(this))
            .catch(function(err) {
                if (err.name !== 'AbortError') { this.loading = false; }
            }.bind(this));
        },

        handleEnter() {
            if (this.activeIndex >= 0 && this.results[this.activeIndex]) {
                this.selectItem(this.results[this.activeIndex]);
                return;
            }
            document.getElementById('filterForm').submit();
        },

        selectItem(item) {
            this.close();
            if (typeof openQuickViewData === 'function') {
                openQuickViewData(item);
            } else {
                window.location.href = '/shop?search=' + encodeURIComponent(item.display_name || item.name);
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
            this.exactMatch = false;
            this.$refs.searchInput.focus();
        },

        async startScanner() {
            if (this.scannerActive) { this.stopScanner(); return; }
            this.scannerActive = true;
            this.open = false;
                    try {
                        var text = await window.scanBarcode({ containerId: 'shop-search-scanner' });
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
                if (e !== 'Cancelled') console.error('[ShopSearch] Scanner error:', e);
                this.scannerActive = false;
            }
        },

        stopScanner() {
            if (window.stopScanBarcode) window.stopScanBarcode();
            else if (window._scanAbort) window._scanAbort.abort();
            this.scannerActive = false;
        },
    };
}
</script>

{{-- ============================================ --}}
{{-- CATEGORY IMAGE SLIDERS (uploaded in admin)  --}}
{{-- ============================================ --}}
@if($activeCategory && $categoryImages->count())
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
    <div x-data="{ active:0, images: {{ $categoryImages->toJson() }}, timer:null,
            init(){ if(this.images.length>1){ this.timer=setInterval(()=>this.next(),4000); } },
            next(){ this.active=(this.active+1)%this.images.length; },
            prev(){ this.active=(this.active-1+this.images.length)%this.images.length; },
            go(i){ this.active=i; if(this.timer){ clearInterval(this.timer); this.timer=setInterval(()=>this.next(),4000); } } }"
        x-init="init()"
        class="relative rounded-3xl overflow-hidden aspect-[16/7] sm:aspect-[21/9] bg-gray-100 shadow-lg">

        <template x-for="(img, idx) in images" :key="img.id">
            <img :src="'/storage/' + img.path" alt="{{ $activeCategory->name ?? '' }}"
                 :style="'opacity: ' + (idx === active ? '1' : '0')"
                 class="absolute inset-0 w-full h-full object-cover transition-opacity duration-700 ease-in-out">
        </template>

        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>

        <button @click="prev()" type="button" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/30 hover:bg-white/60 backdrop-blur text-white flex items-center justify-center transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="next()" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/30 hover:bg-white/60 backdrop-blur text-white flex items-center justify-center transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div class="absolute bottom-12 left-0 right-0 flex items-center justify-center gap-2">
            <template x-for="(img, idx) in images" :key="'dot-'+img.id">
                <button @click="go(idx)" type="button" :class="idx===active ? 'w-6 bg-white' : 'w-2 bg-white/50'" class="h-2 rounded-full transition-all"></button>
            </template>
        </div>

        <div class="absolute bottom-5 left-6 text-white">
            <span class="text-xs uppercase tracking-widest opacity-80">{{ __('messages.category') ?? 'Category' }}</span>
            <h2 class="text-2xl lg:text-3xl font-bold" style="font-family:'Poppins',sans-serif;">{{ $activeCategory->name }}</h2>
        </div>
    </div>
</section>

{{-- Second slider: marquee strip of the same category images --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
    <div x-data="categoryMarquee('left')" x-init="init()" class="relative">
        <div x-ref="track" class="flex gap-4 overflow-x-auto pb-2 cursor-grab active:cursor-grabbing select-none" style="scrollbar-width:none;"
             @mouseenter="paused=true" @mouseleave="paused=false"
             @pointerdown="startDrag($event)" @pointermove="onDrag($event)" @pointerup="endDrag($event)" @pointerleave="endDrag($event)" @click="onClick($event)">
            @foreach($categoryImages as $img)
                <div class="shrink-0 w-44 sm:w-60 h-28 sm:h-32 rounded-2xl overflow-hidden shadow-md">
                    <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $activeCategory->name }}" loading="lazy" class="w-full h-full object-cover">
                </div>
            @endforeach
            @foreach($categoryImages as $img)
                <div class="shrink-0 w-44 sm:w-60 h-28 sm:h-32 rounded-2xl overflow-hidden shadow-md">
                    <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $activeCategory->name }}" loading="lazy" class="w-full h-full object-cover">
                </div>
            @endforeach
        </div>
        <div class="pointer-events-none absolute inset-y-0 left-0 w-10 bg-gradient-to-r from-white to-transparent"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-white to-transparent"></div>
    </div>
</section>
@endif


{{-- ============================================ --}}
{{-- FILTERS BAR (Alpine.js for interactivity)   --}}
{{-- ============================================ --}}
<section class="bg-white border-b border-gray-200 sticky top-16 lg:top-20 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <form action="{{ route('shop.index') }}" method="GET" id="filterForm">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">

                {{-- Search --}}
                <div class="relative flex-1 lg:max-w-sm" x-data="shopLiveSearch()" @click.outside="close()">
                    <div class="w-full relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                            x-ref="searchInput" x-model="query"
                            @input.debounce.300ms="search()"
                            @keydown.enter.prevent="handleEnter()"
                            @keydown.arrow-down.prevent="navigate(1)"
                            @keydown.arrow-up.prevent="navigate(-1)"
                            @keydown.escape="close()"
                            @focus="if(results.length) open = true"
                            placeholder="{{ __('messages.filter_search') }}"
                            class="w-full pl-10 pr-16 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                        >
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        <div x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                        <button x-show="!loading && !scannerActive && query.length === 0" @click="startScanner()" x-cloak class="absolute right-11 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors" title="Scan barcode">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                        </button>
                        <button x-show="query.length > 0" @click="clear()" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        {{-- Scanner Viewfinder --}}
                        <div x-show="scannerActive" x-cloak class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-[60] p-4 w-full">
                            <div class="flex items-center gap-3 mb-3">
                                <svg class="animate-spin h-5 w-5 text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <p class="text-sm text-gray-600">Point camera at barcode...</p>
                            </div>
                            <div id="shop-search-scanner" class="md-viewport" style="aspect-ratio: 4 / 3; min-height: 220px;"></div>
                            <button @click="stopScanner()" class="mt-3 w-full text-center text-xs text-gray-500 hover:text-gray-700 transition-colors">Cancel</button>
                        </div>

                        {{-- Dropdown Results --}}
                        <div x-show="open && results.length > 0" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-[60] max-h-[60vh] overflow-y-auto overscroll-contain"
                            x-ref="resultsDropdown">
                            <template x-for="(item, index) in results" :key="item.id">
                                <button type="button" @click="selectItem(item)" @mouseenter="activeIndex = index"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                    :class="activeIndex === index ? 'bg-emerald-50' : 'hover:bg-gray-50'">
                                    <template x-if="item.image_url">
                                        <img :src="'/storage/' + item.image_url" :alt="item.name" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0">
                                    </template>
                                    <template x-if="!item.image_url">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200 shrink-0">
                                            <i data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                        </div>
                                    </template>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate" x-text="item.display_name || item.name"></div>
                                        <div class="text-xs text-gray-500">₦<span x-text="Number(item.selling_price).toLocaleString(undefined, {minimumFractionDigits:2})"></span> <template x-if="item.category"><span class="text-gray-400">&middot; <span x-text="item.category"></span></span></template></div>
                                    </div>
                                </button>
                            </template>
                            <template x-if="!loading && results.length === 0 && query.length > 0">
                                <div class="px-4 py-3 text-sm text-gray-500">No results found</div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Category Filter --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:border-emerald-300 transition-all w-full lg:w-auto justify-between">
                        <span class="flex items-center gap-2">
                            <i data-lucide="tag" class="w-4 h-4 text-gray-400"></i>
                            {{ request('category') ? $categories->firstWhere('slug', request('category'))?->name ?? 'Category' : 'All Categories' }}
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute left-0 mt-2 w-52 sm:w-60 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 max-h-72 overflow-y-auto">
                        <a href="{{ route('shop.index', array_merge(request()->except('category', 'page'), ['sort' => request('sort', 'newest')])) }}" class="block px-4 py-2.5 text-sm hover:bg-emerald-50 hover:text-emerald-600 transition-colors {{ !request('category') ? 'bg-emerald-50 text-emerald-600 font-semibold' : 'text-gray-700' }}">
                            {{ __('messages.all_categories') }}
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ route('shop.index', array_merge(request()->except('category', 'page'), ['category' => $cat->slug, 'sort' => request('sort', 'newest')])) }}" class="block px-4 py-2.5 text-sm hover:bg-emerald-50 hover:text-emerald-600 transition-colors {{ request('category') == $cat->slug ? 'bg-emerald-50 text-emerald-600 font-semibold' : 'text-gray-700' }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Sort --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:border-emerald-300 transition-all w-full lg:w-auto justify-between">
                        <span class="flex items-center gap-2">
                            <i data-lucide="arrow-up-down" class="w-4 h-4 text-gray-400"></i>
                            @switch(request('sort', 'newest'))
                                @case('price_asc')
                                    {{ __('messages.price_low_high') }}
                                    @break
                                @case('price_desc')
                                    {{ __('messages.price_high_low') }}
                                    @break
                                @case('name_asc')
                                    {{ __('messages.name_az') }}
                                    @break
                                @default
                                    {{ __('messages.sort_newest') }}
                            @endswitch
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute left-0 mt-2 w-44 sm:w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                        @php $sorts = ['newest' => __('messages.sort_newest'), 'price_asc' => __('messages.price_low_high'), 'price_desc' => __('messages.price_high_low'), 'name_asc' => __('messages.name_az')]; @endphp
                        @foreach($sorts as $val => $label)
                            <a href="{{ route('shop.index', array_merge(request()->except('sort', 'page'), ['sort' => $val])) }}" class="block px-4 py-2.5 text-sm hover:bg-emerald-50 hover:text-emerald-600 transition-colors {{ request('sort', 'newest') == $val ? 'bg-emerald-50 text-emerald-600 font-semibold' : 'text-gray-700' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>

        {{-- Active Filter Pills --}}
        @if(request()->hasAny(['search', 'category']))
            <div class="mt-3 flex items-center flex-wrap gap-2">
                <span class="text-xs text-gray-500 font-medium">{{ __('messages.active_filters') }}</span>

                @if(request('search'))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-medium border border-emerald-200">
                        Search: "{{ request('search') }}"
                        <a href="{{ route('shop.index', request()->except('search', 'page')) }}" class="hover:text-emerald-900 transition-colors">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </a>
                    </span>
                @endif

                @if(request('category'))
                    @php $activeCat = $categories->firstWhere('slug', request('category')); @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-medium border border-emerald-200">
                        Category: {{ $activeCat->name ?? request('category') }}
                        <a href="{{ route('shop.index', request()->except('category', 'page')) }}" class="hover:text-emerald-900 transition-colors">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </a>
                    </span>
                @endif

                <a href="{{ route('shop.index') }}" class="text-xs text-red-500 hover:text-red-700 font-medium ml-1 transition-colors">{{ __('messages.clear_all') }}</a>
            </div>
        @endif
    </div>
</section>


{{-- ============================================ --}}
{{-- RESULTS INFO                                --}}
{{-- ============================================ --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-2">
    <p class="text-xs sm:text-sm text-gray-500">
        @if(request('search') || request('category'))
            <span class="font-semibold text-gray-900">{{ $items->total() }}</span> {{ Str::plural('result', $items->total()) }}
            @if(request('search'))
                for "<span class="font-medium text-emerald-600">{{ request('search') }}</span>"
            @endif
        @else
            Showing <span class="font-semibold text-gray-900">{{ $items->total() }}</span> {{ Str::plural('product', $items->total()) }}
        @endif
    </p>
</section>


{{-- ============================================ --}}
{{-- PRODUCTS GRID                               --}}
{{-- ============================================ --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 lg:pb-24">
    @if($items->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6 mt-6">
            @foreach($items as $item)
                @include('components.product-card', ['item' => $item])
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-12">
            {{ $items->links('components.pagination') }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i data-lucide="package-search" class="w-12 h-12 text-gray-300"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">{{ __('messages.no_products') }}</h3>
            <p class="text-gray-500 max-w-md mb-8">
                {{ __('messages.no_products_desc') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl transition-colors w-full sm:w-auto">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    {{ __('messages.reset_filters') }}
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors w-full sm:w-auto">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    {{ __('messages.back_to_home') }}
                </a>
            </div>
        </div>
    @endif
</section>

@if(isset($recommendations) && $recommendations->count())
{{-- ============================================ --}}
{{-- RECOMMENDED FOR YOU                         --}}
{{-- ============================================ --}}
<section class="bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                <i data-lucide="sparkles" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.recommended_for_you') }}</h2>
                <p class="text-xs text-gray-500">{{ __('messages.based_on_history') }}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            @foreach($recommendations as $recItem)
                @include('components.product-card', ['item' => $recItem])
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
