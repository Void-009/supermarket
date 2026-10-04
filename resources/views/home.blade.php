@extends('layouts.main')

@section('content')

{{-- ============================================ --}}
{{-- HERO SECTION                                --}}
{{-- ============================================ --}}
<section id="hero-section" @if($slideshowImages->count()) x-data="heroSlideshow({{ $slideshowImages->count() }})"
    @touchstart.passive="startSwipe($event)" @touchend.passive="endSwipe($event)"
    @mousedown="startSwipe($event)" @mouseup="endSwipe($event)" @endif
    class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-emerald-50 to-emerald-100">

    @if($slideshowImages->count())
        {{-- Slideshow Backgrounds --}}
        @foreach($slideshowImages as $idx => $slide)
            <div :style="'opacity: ' + (current === {{ $idx }} ? '1' : '0')"
                 style="opacity: {{ $idx === 0 ? '1' : '0' }}"
                 class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                 aria-hidden="{{ $idx === 0 ? 'false' : 'true' }}">
                <img src="{{ Storage::disk('public')->url($slide->path) }}" alt="M-DELIGHT SINGER STORES"
                     class="w-full h-full object-cover" loading="{{ $idx === 0 ? 'eager' : 'lazy' }}">
            </div>
        @endforeach
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-900/80 via-emerald-900/60 to-gray-900/70"></div>

        {{-- Slideshow Dots --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
            @foreach($slideshowImages as $idx => $slide)
                <button @click="goTo({{ $idx }})" :class="current === {{ $idx }} ? 'w-8 bg-white' : 'w-3 bg-white/50'" class="h-2 rounded-full transition-all duration-300" :aria-label="'Go to slide {{ $idx + 1 }}'"></button>
            @endforeach
        </div>

        {{-- Prev / Next Arrows --}}
        @if($slideshowImages->count() > 1)
            <button type="button" @click="prev()" aria-label="Previous slide"
                class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-30 flex items-center justify-center w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/15 hover:bg-white/35 backdrop-blur-sm text-white shadow-lg transition-all duration-300 active:scale-90">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button type="button" @click="next()" aria-label="Next slide"
                class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-30 flex items-center justify-center w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/15 hover:bg-white/35 backdrop-blur-sm text-white shadow-lg transition-all duration-300 active:scale-90">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        @endif
    @endif

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-14">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 {{ $slideshowImages->count() ? 'bg-white/90 text-emerald-700' : 'bg-emerald-100 text-emerald-700' }} px-3 py-1 rounded-full text-xs sm:text-sm font-semibold mb-3">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                {{ __('messages.your_trusted') }}
            </div>

            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold {{ $slideshowImages->count() ? 'text-white' : 'text-gray-900' }} leading-tight tracking-tight">
                {{ __('messages.hero_title_1') }}<br>
                <span class="{{ $slideshowImages->count() ? 'text-emerald-300' : 'text-emerald-600' }}">{{ __('messages.hero_title_2') }}</span> {{ __('messages.hero_title_3') }}
            </h1>

            <p class="mt-3 text-sm sm:text-base {{ $slideshowImages->count() ? 'text-emerald-50' : 'text-gray-600' }} max-w-2xl mx-auto leading-relaxed">
                {{ __('messages.hero_desc') }}
            </p>

            <div class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-300">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    {{ __('messages.shop_now') }}
                </a>
                <a href="https://wa.me/{{ $activePaymentSettings->whatsapp_number ?? '2348000000000' }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-2.5 {{ $slideshowImages->count() ? 'bg-white/90 hover:bg-white text-gray-800 border-white/20' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200' }} font-semibold rounded-xl border transition-all duration-300">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    {{ __('messages.order_via_whatsapp') }}
                </a>
            </div>

            {{-- Trust row: happy customers paired with same-day delivery --}}
            <div class="mt-5 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm {{ $slideshowImages->count() ? 'text-emerald-50' : 'text-gray-500' }}">
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-1">
                        <div class="w-6 h-6 rounded-full bg-emerald-400 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">M</div>
                        <div class="w-6 h-6 rounded-full bg-sky-400 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">A</div>
                        <div class="w-6 h-6 rounded-full bg-amber-400 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">T</div>
                        <div class="w-6 h-6 rounded-full bg-emerald-600 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">+</div>
                    </div>
                    <span>{{ __('messages.happy_customers') }}</span>
                </div>
                <span class="hidden sm:block w-px h-6 bg-current opacity-30"></span>
                <div class="flex items-center gap-2">
                    <i data-lucide="truck" class="w-4 h-4 text-emerald-400"></i>
                    <span>{{ __('messages.same_day_delivery') }}</span>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ============================================ --}}
{{-- RAIL 1: TOP DEALS TODAY                      --}}
{{-- ============================================ --}}
@if($deals->count())
    <x-product-rail
        :title="__('messages.top_deals')"
        :items="$deals"
        :href="route('shop.index')"
    />
@endif


{{-- ============================================ --}}
{{-- CATEGORY SLIDE MARQUEE                       --}}
{{-- ============================================ --}}
@if($categories->count())
<section class="py-10 lg:py-12 bg-gray-50 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
        <div>
            <span class="inline-block text-emerald-600 font-semibold text-sm uppercase tracking-widest mb-1">{{ __('messages.browse') }}</span>
            <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.shop_by_category') }}</h2>
        </div>
        <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors shrink-0">
            {{ __('messages.view_all_products') }}
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>

    <div class="relative"
        x-data="marqueeManual()" x-init="boot()"
        @pointerdown="down($event)"
        @pointermove.passive="move($event)"
        @pointerup="up()"
        @pointercancel="up()">
        <button type="button" @click="nudge(-1)" aria-label="Scroll categories left"
            class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-20 flex items-center justify-center w-10 h-10 rounded-full bg-white/90 hover:bg-white text-gray-700 hover:text-emerald-600 shadow-lg shadow-emerald-900/10 backdrop-blur-sm transition-all duration-300 active:scale-90">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </button>
        <button type="button" @click="nudge(1)" aria-label="Scroll categories right"
            class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-20 flex items-center justify-center w-10 h-10 rounded-full bg-white/90 hover:bg-white text-gray-700 hover:text-emerald-600 shadow-lg shadow-emerald-900/10 backdrop-blur-sm transition-all duration-300 active:scale-90">
            <i data-lucide="chevron-right" class="w-5 h-5"></i>
        </button>

        <div class="marquee select-none" :class="grabbing ? 'cursor-grabbing' : 'cursor-grab'">
            <div class="marquee-track" x-ref="track">
                <div class="marquee-set">
                    @foreach($categories as $idx => $category)
                        <x-category-slide :category="$category" :index="$idx" />
                    @endforeach
                </div>
                <div class="marquee-set" aria-hidden="true">
                    @foreach($categories as $idx => $category)
                        <x-category-slide :category="$category" :index="$idx" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif


{{-- ============================================ --}}
{{-- RAIL 2: NEW ARRIVALS                         --}}
{{-- ============================================ --}}
@if($recentItems->count())
    <x-product-rail
        :title="__('messages.new_arrivals')"
        :items="$recentItems"
        :href="route('shop.index')"
    />
@endif

@endsection

@push('scripts')
<script>
function marqueeManual() {
    return {
        grabbing: false,
        dragging: false,
        tween: null,
        startX: 0,
        startO: 0,
        moved: 0,
        w: 0,
        dx: 0,
        rafMove: null,

        boot() {
            window.addEventListener('resize', () => { this.w = 0; });
        },

        track() { return this.$refs.track; },

        measure() {
            const set = this.track() ? this.track().querySelector('.marquee-set') : null;
            this.w = set ? set.offsetWidth : 0;
            return this.w;
        },

        readTx(el) {
            const tr = getComputedStyle(el).transform;
            if (!tr || tr === 'none') return 0;
            if (typeof DOMMatrixReadOnly !== 'undefined') {
                try { return new DOMMatrixReadOnly(tr).m41; } catch (e) {}
            }
            const m = tr.match(/matrix\(([^)]+)\)/);
            return m ? parseFloat(m[1].split(',')[4]) || 0 : 0;
        },

        currentO() {
            const el = this.track();
            const W = this.w || this.measure();
            if (!el || !W) return 0;
            const o = -this.readTx(el);
            return ((o % W) + W) % W;
        },

        freezeAt(O) {
            const el = this.track();
            if (!el) return;
            if (this.tween) { cancelAnimationFrame(this.tween); this.tween = null; }
            el.style.animation = 'none';
            el.style.transform = 'translateX(' + (-O) + 'px)';
        },

        resumeFrom(O) {
            const el = this.track();
            const W = this.w || this.measure();
            if (!el || !W) return;
            const d = ((O % W) + W) % W;
            el.style.transform = '';
            el.style.animation = 'marquee-scroll 45s linear ' + (-(d / W) * 45).toFixed(4) + 's infinite';
        },

        down(e) {
            if (e.button !== undefined && e.button !== 0) return;
            const el = this.track();
            if (!el) return;
            e.preventDefault();
            if (this.w === 0) this.measure();
            this.grabbing = true;
            this.dragging = true;
            this.moved = 0;
            this.dx = 0;
            this.startX = e.clientX;
            this.startO = this.currentO();
            this.freezeAt(this.startO);
            if (this.$root.setPointerCapture) {
                try { this.$root.setPointerCapture(e.pointerId); } catch (err) {}
            }
        },

        move(e) {
            if (!this.dragging) return;
            this.dx = e.clientX - this.startX;
            this.moved = Math.max(this.moved, Math.abs(this.dx));
            if (this.rafMove) return;
            this.rafMove = requestAnimationFrame(() => {
                this.rafMove = null;
                if (!this.dragging) return;
                const W = this.w || this.measure();
                let O = this.startO - this.dx;
                if (W > 0) O = ((O % W) + W) % W;
                const el = this.track();
                if (el) el.style.transform = 'translateX(' + (-O) + 'px)';
            });
        },

        up() {
            if (!this.dragging) return;
            this.dragging = false;
            this.grabbing = false;
            if (this.rafMove) { cancelAnimationFrame(this.rafMove); this.rafMove = null; }
            const suppress = this.moved > 6;
            const W = this.w || this.measure();
            const O = W > 0 ? ((((this.startO - this.dx) % W) + W) % W) : 0;
            this.resumeFrom(O);
            if (suppress) {
                const root = this.$root;
                const kill = function (ev) { ev.preventDefault(); ev.stopPropagation(); };
                root.addEventListener('click', kill, { capture: true, once: true });
                setTimeout(function () { root.removeEventListener('click', kill, true); }, 350);
            }
        },

        nudge(dir) {
            const el = this.track();
            const W = this.w || this.measure();
            if (!el || !W) return;
            const vw = this.$root.clientWidth || 600;
            const step = Math.max(240, vw * 0.75);
            const from = this.currentO();
            let delta = dir * step;
            if (Math.abs(delta) > W / 2) delta -= Math.sign(delta) * W;
            this.freezeAt(from);
            const dur = 420;
            const t0 = performance.now();
            const self = this;
            const stepFn = function (t) {
                const p = Math.min(1, (t - t0) / dur);
                const ease = 1 - Math.pow(1 - p, 3);
                const O = (((from + delta * ease) % W) + W) % W;
                const el2 = self.track();
                if (el2) el2.style.transform = 'translateX(' + (-O) + 'px)';
                if (p < 1) {
                    self.tween = requestAnimationFrame(stepFn);
                } else {
                    self.tween = null;
                    self.resumeFrom(O);
                }
            };
            this.tween = requestAnimationFrame(stepFn);
        },
    };
}

function heroSlideshow(total) {
    return {
        current: 0,
        total: total,
        timer: null,
        startX: null,
        init() {
            this.restart();
        },
        restart() {
            if (this.timer) clearInterval(this.timer);
            if (this.total > 1) {
                this.timer = setInterval(() => {
                    this.current = (this.current + 1) % this.total;
                }, 5000);
            }
        },
        next() {
            if (this.total < 2) return;
            this.current = (this.current + 1) % this.total;
            this.restart();
        },
        prev() {
            if (this.total < 2) return;
            this.current = (this.current - 1 + this.total) % this.total;
            this.restart();
        },
        goTo(i) {
            this.current = i;
            this.restart();
        },
        startSwipe(e) {
            if (this.total < 2) return;
            const point = e.touches ? e.touches[0] : e;
            this.startX = point.clientX;
        },
        endSwipe(e) {
            if (this.startX === null || this.total < 2) { this.startX = null; return; }
            const point = e.changedTouches ? e.changedTouches[0] : e;
            const dx = point.clientX - this.startX;
            this.startX = null;
            if (Math.abs(dx) < 40) return;
            if (dx < 0) { this.next(); } else { this.prev(); }
        },
    };
}
</script>
@endpush
