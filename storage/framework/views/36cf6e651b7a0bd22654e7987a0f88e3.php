<?php $__env->startSection('title', '403 - Forbidden'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-6xl sm:text-8xl font-black text-red-500 mb-4" style="font-family:'Poppins',sans-serif;">403</div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">Access Denied</h1>
        <p class="text-gray-500 mb-8 break-words">You do not have permission to access this page.</p>
        <a href="<?php echo e(url()->previous() !== url()->current() ? url()->previous() : route('home')); ?>" class="inline-flex w-full sm:w-auto items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Go Back
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.' . (auth()->check() && auth()->user()->isAdminOrAgent() ? 'admin' : 'main'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/errors/403.blade.php ENDPATH**/ ?>