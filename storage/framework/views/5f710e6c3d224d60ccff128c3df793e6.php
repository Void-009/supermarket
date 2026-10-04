<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['category', 'index' => null]));

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

foreach (array_filter((['category', 'index' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $img = $category->displayImage();
    $gradients = [
        'from-green-400 to-emerald-600',
        'from-emerald-400 to-emerald-600',
        'from-blue-400 to-indigo-600',
        'from-rose-400 to-pink-600',
        'from-violet-400 to-purple-600',
        'from-teal-400 to-cyan-600',
        'from-red-400 to-rose-600',
        'from-yellow-400 to-emerald-600',
    ];
    $g = $gradients[($index ?? $loop->index ?? 0) % count($gradients)];
?>

<a href="<?php echo e(route('shop.index', ['category' => $category->slug])); ?>" draggable="false"
   class="group relative shrink-0 w-40 sm:w-48 lg:w-56 h-60 lg:h-72 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br <?php echo e($g); ?>">
    <?php if($img): ?>
        <img src="<?php echo e($img); ?>" alt="<?php echo e($category->name); ?>" loading="lazy"
             class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
    <?php else: ?>
        <div class="absolute inset-0 bg-gradient-to-br <?php echo e($g); ?>"></div>
    <?php endif; ?>

    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>

    <div class="absolute top-3 right-3 w-9 h-9 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center md:opacity-0 md:group-hover:opacity-100 transition-all duration-300 translate-x-2 md:group-hover:translate-x-0">
        <i data-lucide="arrow-right" class="w-4 h-4 text-white"></i>
    </div>

    <div class="absolute bottom-0 left-0 right-0 p-3 sm:p-4 text-white">
        <span class="text-[10px] sm:text-xs font-medium opacity-80"><?php echo e(__('messages.items_count', ['count' => $category->items_count ?? 0])); ?></span>
        <h3 class="category-name text-sm sm:text-base lg:text-lg mt-0.5 leading-tight drop-shadow line-clamp-2"><?php echo e($category->title_name); ?></h3>
    </div>
</a>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/category-slide.blade.php ENDPATH**/ ?>