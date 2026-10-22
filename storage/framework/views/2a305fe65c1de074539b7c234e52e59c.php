<?php if($paginator->hasPages()): ?>
<nav class="flex items-center justify-center gap-1.5 sm:gap-2 mt-8 flex-wrap">
    <?php if($paginator->onFirstPage()): ?>
        <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-300 bg-gray-50 rounded-lg cursor-not-allowed">&laquo; <?php echo e(__('messages.prev')); ?></span>
    <?php else: ?>
        <a href="<?php echo e($paginator->previousPageUrl()); ?>" class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition-colors">&laquo; <?php echo e(__('messages.prev')); ?></a>
    <?php endif; ?>

    <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(is_string($element)): ?>
            <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-400"><?php echo e($element); ?></span>
        <?php endif; ?>
        <?php if(is_array($element)): ?>
            <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($page == $paginator->currentPage()): ?>
                    <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-white bg-emerald-500 rounded-lg font-medium"><?php echo e($page); ?></span>
                <?php else: ?>
                    <a href="<?php echo e($url); ?>" class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition-colors"><?php echo e($page); ?></a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if($paginator->hasMorePages()): ?>
        <a href="<?php echo e($paginator->nextPageUrl()); ?>" class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 transition-colors"><?php echo e(__('messages.next')); ?> &raquo;</a>
    <?php else: ?>
        <span class="px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm text-gray-300 bg-gray-50 rounded-lg cursor-not-allowed"><?php echo e(__('messages.next')); ?> &raquo;</span>
    <?php endif; ?>
</nav>
<?php endif; ?>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/pagination.blade.php ENDPATH**/ ?>