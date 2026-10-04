<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['containerId', 'title' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['containerId', 'title' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<div x-show="scannerActive" x-cloak class="fixed inset-0 z-[70]" role="dialog" aria-modal="true"
     @keydown.escape.window="stopScanner()">
    <div class="absolute inset-0 bg-slate-900/70" @click="stopScanner()"></div>

    <div class="relative flex min-h-full items-start justify-center px-4 py-10 sm:py-16">
        <div class="w-full max-w-[22rem]">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-white"><?php echo e($title ?? __('messages.barcode_scanner')); ?></h3>
                <button type="button" @click="stopScanner()"
                    class="rounded-lg p-1.5 text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                    :title="'<?php echo e(__('messages.stop_scanner')); ?>'"
                    :aria-label="'<?php echo e(__('messages.stop_scanner')); ?>'">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div id="<?php echo e($containerId); ?>" class="h-52 w-full overflow-hidden rounded-xl border-2 border-emerald-400 bg-black sm:h-64"></div>

            <p class="mt-2 text-center text-xs text-white/70"><?php echo e(__('messages.point_camera_barcode')); ?></p>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/scan-overlay.blade.php ENDPATH**/ ?>