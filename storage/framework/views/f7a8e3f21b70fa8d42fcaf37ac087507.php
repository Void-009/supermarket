<?php $__env->startSection('content'); ?>




<section id="hero-section" <?php if($slideshowImages->count()): ?> x-data="heroSlideshow(<?php echo e($slideshowImages->count()); ?>)"
    @touchstart.passive="startSwipe($event)" @touchend.passive="endSwipe($event)"
    @mousedown="startSwipe($event)" @mouseup="endSwipe($event)" <?php endif; ?>
    class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-emerald-50 to-emerald-100">

    <?php if($slideshowImages->count()): ?>
        
        <?php $__currentLoopData = $slideshowImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div :style="'opacity: ' + (current === <?php echo e($idx); ?> ? '1' : '0')"
                 style="opacity: <?php echo e($idx === 0 ? '1' : '0'); ?>"
                 class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                 aria-hidden="<?php echo e($idx === 0 ? 'false' : 'true'); ?>">
                <img src="<?php echo e(Storage::disk('public')->url($slide->path)); ?>" alt="M-DELIGHT SINGER STORES"
                     class="w-full h-full object-cover" loading="<?php echo e($idx === 0 ? 'eager' : 'lazy'); ?>">
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-900/80 via-emerald-900/60 to-gray-900/70"></div>

        
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
            <?php $__currentLoopData = $slideshowImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button @click="goTo(<?php echo e($idx); ?>)" :class="current === <?php echo e($idx); ?> ? 'w-8 bg-white' : 'w-3 bg-white/50'" class="h-2 rounded-full transition-all duration-300" :aria-label="'Go to slide <?php echo e($idx + 1); ?>'"></button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <?php if($slideshowImages->count() > 1): ?>
            <button type="button" @click="prev()" aria-label="Previous slide"
                class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-30 flex items-center justify-center w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/15 hover:bg-white/35 backdrop-blur-sm text-white shadow-lg transition-all duration-300 active:scale-90">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button type="button" @click="next()" aria-label="Next slide"
                class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-30 flex items-center justify-center w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white/15 hover:bg-white/35 backdrop-blur-sm text-white shadow-lg transition-all duration-300 active:scale-90">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        <?php endif; ?>
    <?php endif; ?>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-14">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 <?php echo e($slideshowImages->count() ? 'bg-white/90 text-emerald-700' : 'bg-emerald-100 text-emerald-700'); ?> px-3 py-1 rounded-full text-xs sm:text-sm font-semibold mb-3">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <?php echo e(__('messages.your_trusted')); ?>

            </div>

            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold <?php echo e($slideshowImages->count() ? 'text-white' : 'text-gray-900'); ?> leading-tight tracking-tight">
                <?php echo e(__('messages.hero_title_1')); ?><br>
                <span class="<?php echo e($slideshowImages->count() ? 'text-emerald-300' : 'text-emerald-600'); ?>"><?php echo e(__('messages.hero_title_2')); ?></span> <?php echo e(__('messages.hero_title_3')); ?>

            </h1>

            <p class="mt-3 text-sm sm:text-base <?php echo e($slideshowImages->count() ? 'text-emerald-50' : 'text-gray-600'); ?> max-w-2xl mx-auto leading-relaxed">
                <?php echo e(__('messages.hero_desc')); ?>

            </p>

            <div class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="<?php echo e(route('shop.index')); ?>" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-300">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    <?php echo e(__('messages.shop_now')); ?>

                </a>
                <a href="https://wa.me/<?php echo e($activePaymentSettings->whatsapp_number ?? '2348000000000'); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-2.5 <?php echo e($slideshowImages->count() ? 'bg-white/90 hover:bg-white text-gray-800 border-white/20' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200'); ?> font-semibold rounded-xl border transition-all duration-300">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <?php echo e(__('messages.order_via_whatsapp')); ?>

                </a>
            </div>

            
            <div class="mt-5 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm <?php echo e($slideshowImages->count() ? 'text-emerald-50' : 'text-gray-500'); ?>">
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-1">
                        <div class="w-6 h-6 rounded-full bg-emerald-400 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">M</div>
                        <div class="w-6 h-6 rounded-full bg-sky-400 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">A</div>
                        <div class="w-6 h-6 rounded-full bg-amber-400 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">T</div>
                        <div class="w-6 h-6 rounded-full bg-emerald-600 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">+</div>
                    </div>
                    <span><?php echo e(__('messages.happy_customers')); ?></span>
                </div>
                <span class="hidden sm:block w-px h-6 bg-current opacity-30"></span>
                <div class="flex items-center gap-2">
                    <i data-lucide="truck" class="w-4 h-4 text-emerald-400"></i>
                    <span><?php echo e(__('messages.same_day_delivery')); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>





<?php if($deals->count()): ?>
    <?php if (isset($component)) { $__componentOriginal88068b1af0d1fb0f719bd2201d823842 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88068b1af0d1fb0f719bd2201d823842 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-rail','data' => ['title' => __('messages.top_deals'),'items' => $deals,'href' => route('shop.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-rail'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('messages.top_deals')),'items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($deals),'href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('shop.index'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal88068b1af0d1fb0f719bd2201d823842)): ?>
<?php $attributes = $__attributesOriginal88068b1af0d1fb0f719bd2201d823842; ?>
<?php unset($__attributesOriginal88068b1af0d1fb0f719bd2201d823842); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal88068b1af0d1fb0f719bd2201d823842)): ?>
<?php $component = $__componentOriginal88068b1af0d1fb0f719bd2201d823842; ?>
<?php unset($__componentOriginal88068b1af0d1fb0f719bd2201d823842); ?>
<?php endif; ?>
<?php endif; ?>





<?php if($categories->count()): ?>
<section class="py-10 lg:py-12 bg-gray-50 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
        <div>
            <span class="inline-block text-emerald-600 font-semibold text-sm uppercase tracking-widest mb-1"><?php echo e(__('messages.browse')); ?></span>
            <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.shop_by_category')); ?></h2>
        </div>
        <a href="<?php echo e(route('shop.index')); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors shrink-0">
            <?php echo e(__('messages.view_all_products')); ?>

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
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if (isset($component)) { $__componentOriginal1eced2aaf02f53fdffa0f966dee47a76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.category-slide','data' => ['category' => $category,'index' => $idx]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('category-slide'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['category' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($category),'index' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($idx)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76)): ?>
<?php $attributes = $__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76; ?>
<?php unset($__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1eced2aaf02f53fdffa0f966dee47a76)): ?>
<?php $component = $__componentOriginal1eced2aaf02f53fdffa0f966dee47a76; ?>
<?php unset($__componentOriginal1eced2aaf02f53fdffa0f966dee47a76); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="marquee-set" aria-hidden="true">
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if (isset($component)) { $__componentOriginal1eced2aaf02f53fdffa0f966dee47a76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.category-slide','data' => ['category' => $category,'index' => $idx]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('category-slide'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['category' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($category),'index' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($idx)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76)): ?>
<?php $attributes = $__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76; ?>
<?php unset($__attributesOriginal1eced2aaf02f53fdffa0f966dee47a76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1eced2aaf02f53fdffa0f966dee47a76)): ?>
<?php $component = $__componentOriginal1eced2aaf02f53fdffa0f966dee47a76; ?>
<?php unset($__componentOriginal1eced2aaf02f53fdffa0f966dee47a76); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>





<?php if($recentItems->count()): ?>
    <?php if (isset($component)) { $__componentOriginal88068b1af0d1fb0f719bd2201d823842 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88068b1af0d1fb0f719bd2201d823842 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-rail','data' => ['title' => __('messages.new_arrivals'),'items' => $recentItems,'href' => route('shop.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-rail'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('messages.new_arrivals')),'items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($recentItems),'href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('shop.index'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal88068b1af0d1fb0f719bd2201d823842)): ?>
<?php $attributes = $__attributesOriginal88068b1af0d1fb0f719bd2201d823842; ?>
<?php unset($__attributesOriginal88068b1af0d1fb0f719bd2201d823842); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal88068b1af0d1fb0f719bd2201d823842)): ?>
<?php $component = $__componentOriginal88068b1af0d1fb0f719bd2201d823842; ?>
<?php unset($__componentOriginal88068b1af0d1fb0f719bd2201d823842); ?>
<?php endif; ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/home.blade.php ENDPATH**/ ?>