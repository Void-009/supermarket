<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['item']));

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

foreach (array_filter((['item']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $displayName = $item->display_name;
    $discounted = $item->isDiscounted();
    $parentItem = $item->parent_item_number ? $item->parentItem : null;
    $variations = $item->relationLoaded('variations')
        ? $item->variations
        : collect();
    if ($item->isVariation() && $parentItem && $variations->doesntContain('id', $parentItem->id)) {
        $variations->prepend($parentItem);
    }
    $itemData = json_encode([
        'id' => $item->id,
        'name' => $item->name,
        'display_name' => $displayName,
        'slug' => $item->slug,
        'selling_price' => $item->effectivePrice(),
        'compare_at_price' => $discounted ? $item->selling_price : null,
        'image_url' => $item->image_url,
        'stock' => $item->effectiveQuantity(),
        'category' => $item->category?->name,
        'is_variation' => $item->isVariation(),
        'parent' => $parentItem ? [
            'id' => $parentItem->id,
            'name' => $parentItem->name,
            'display_name' => $parentItem->display_name,
            'slug' => $parentItem->slug,
        ] : null,
        'variations' => $variations->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'display_name' => $v->display_name,
            'slug' => $v->slug,
            'selling_price' => $v->effectivePrice(),
            'compare_at_price' => $v->isDiscounted() ? $v->selling_price : null,
            'image_url' => $v->image_url,
            'stock' => (int) $v->effectiveQuantity(),
            'is_variation' => $v->isVariation(),
        ])->values(),
    ], JSON_HEX_APOS | JSON_HEX_TAG);
    $rating = $item->pseudoRating();
    $ratingFull = $rating ? (int) floor($rating) : 0;
    $ratingHalf = $rating ? (($rating - floor($rating)) >= 0.5) : false;
    $orderCount = (int) ($item->order_items_count ?? 0);
?>

<div x-data="{}" class="group block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-emerald-200 cursor-pointer w-40 sm:w-44 shrink-0 min-w-0" data-item='<?php echo e($itemData); ?>' @click="window.openQuickView($el)" @keydown.enter="window.openQuickView($el)" role="button" tabindex="0">
    <div class="relative aspect-square bg-gray-50 overflow-hidden">
        <?php if($item->image_url): ?>
            <img src="<?php echo e(asset('storage/' . $item->image_url)); ?>" alt="<?php echo e($item->name); ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                <span class="text-3xl">🛒</span>
            </div>
        <?php endif; ?>
        <?php if($discounted): ?>
            <span class="absolute top-2 left-2 bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">-<?php echo e(rtrim(rtrim(number_format($item->discount_percentage, 0), '0'), '.')); ?>%</span>
        <?php endif; ?>
        <?php if($item->effectiveQuantity() < 1): ?>
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-[10px] font-medium"><?php echo e(__('messages.out_of_stock')); ?></span>
            </div>
        <?php endif; ?>
    </div>
    <div class="p-2.5">
        <h3 class="text-xs font-semibold text-gray-900 line-clamp-2 group-hover:text-emerald-600 transition-colors overflow-hidden text-ellipsis min-h-[2rem]"><?php echo e($displayName); ?></h3>

        <?php if($rating): ?>
            <div class="mt-1 flex items-center gap-0.5 text-amber-400" title="<?php echo e(number_format($rating, 1)); ?> / 5">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <?php
                        $fill = 0;
                        if ($i <= $ratingFull) { $fill = 100; }
                        elseif ($ratingHalf && $i === $ratingFull + 1) { $fill = 50; }
                    ?>
                    <?php if($fill === 100): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                    <?php else: ?>
                        <span class="relative inline-flex">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-300" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                            </svg>
                            <?php if($fill === 50): ?>
                                <span class="absolute inset-0 overflow-hidden text-amber-400" style="width:50%;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if($orderCount > 0): ?>
                    <span class="text-[10px] text-gray-400">(<?php echo e($orderCount); ?>)</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="mt-1 flex items-center justify-between gap-1 overflow-hidden">
            <div class="flex items-baseline gap-1 min-w-0">
                <span class="text-sm font-bold text-emerald-600 truncate">₦<?php echo e(number_format($item->effectivePrice(), 0)); ?></span>
                <?php if($discounted): ?>
                    <span class="text-[10px] text-gray-400 line-through truncate">₦<?php echo e(number_format($item->selling_price, 0)); ?></span>
                <?php endif; ?>
            </div>
            <?php if($item->effectiveQuantity() > 0): ?>
                <button
                    type="button"
                    @click.stop="submit()"
                    :disabled="adding"
                    x-data="addToCartForm(<?php echo e($item->id); ?>)"
                    class="shrink-0 w-7 h-7 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center transition-all hover:scale-110 disabled:opacity-60"
                    :class="added && 'bg-emerald-600'"
                    :title="added ? '<?php echo e(__('messages.added')); ?>' : '<?php echo e(__('messages.add_to_cart')); ?>'"
                >
                    <svg x-show="!adding && !added" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <svg x-show="adding" class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg x-show="!adding && added" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/product-card-compact.blade.php ENDPATH**/ ?>