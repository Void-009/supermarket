<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'subtitle' => null, 'href' => null, 'viewAllLabel' => null, 'items' => [], 'limit' => 8]));

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

foreach (array_filter((['title', 'subtitle' => null, 'href' => null, 'viewAllLabel' => null, 'items' => [], 'limit' => 8]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $items = collect($items);
    $total = $items->count();
    $visible = min($total, max(1, (int) $limit));
    $hiddenCount = max(0, $total - $visible);
?>

<section <?php echo e($attributes->merge(['class' => 'py-8 sm:py-10 bg-white'])); ?>>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2 mb-5">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;"><?php echo e($title); ?></h2>
                <?php if($subtitle): ?>
                    <p class="mt-1 text-sm text-gray-500"><?php echo e($subtitle); ?></p>
                <?php endif; ?>
            </div>
            <?php if($href): ?>
                <a href="<?php echo e($href); ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors shrink-0">
                    <?php echo e($viewAllLabel ?? __('messages.view_all_products')); ?>

                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="relative">
            <div class="flex gap-3 sm:gap-4 overflow-x-auto pb-3 -mx-4 sm:mx-0 px-4 sm:px-0 snap-x"
                style="scrollbar-width:none;"
                x-data="{ shown: <?php echo e($hiddenCount === 0 ? 'true' : 'false'); ?> }">
                <?php $__currentLoopData = $items->take($visible); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo $__env->make('components.product-card-compact', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($hiddenCount > 0): ?>
                    <div class="contents" x-show="shown" x-cloak>
                        <?php $__currentLoopData = $items->slice($visible); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('components.product-card-compact', ['item' => $item], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <button @click="shown = true" x-show="!shown" x-cloak type="button"
                        class="w-40 sm:w-44 shrink-0 self-stretch rounded-xl border-2 border-dashed border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50 bg-white flex flex-col items-center justify-center gap-2 transition-all duration-300 cursor-pointer group">
                        <span class="w-9 h-9 rounded-full bg-emerald-50 group-hover:bg-emerald-500 group-hover:text-white text-emerald-600 flex items-center justify-center transition-all duration-300">
                            <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                        </span>
                        <span class="text-sm font-semibold text-emerald-600"><?php echo e(__('messages.show_more')); ?></span>
                        <span class="text-[11px] text-gray-400">+<?php echo e($hiddenCount); ?> <?php echo e(__('messages.more_items')); ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/product-rail.blade.php ENDPATH**/ ?>