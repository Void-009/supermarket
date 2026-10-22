<?php
    $statusColors = [
        'new' => 'bg-blue-50 text-blue-700 border-blue-200',
        'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'paid' => 'bg-green-50 text-green-700 border-green-200',
        'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
        'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-red-50 text-red-700 border-red-200',
    ];
?>

<?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php ($colorClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border-gray-200'); ?>
    <tr data-order-id="<?php echo e($order->id); ?>" class="hover:bg-gray-50/50 transition-colors">
        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">#<?php echo e($order->id); ?></td>
        <td class="px-6 py-4 text-gray-700 max-w-[160px] break-words"><?php echo e($order->customer_name ?? 'N/A'); ?></td>
        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">₦<?php echo e(number_format($order->total_amount, 2)); ?></td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo e($colorClass); ?>">
                <?php echo e(ucfirst($order->status)); ?>

            </span>
        </td>
        <td class="px-6 py-4 text-gray-500 whitespace-nowrap"><?php echo e($order->created_at->format('M d, Y')); ?></td>
        <td class="px-6 py-4 text-right whitespace-nowrap">
            <a href="<?php echo e(route('admin.orders.show', $order)); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
            </a>
        </td>
    </tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <tr id="recent-orders-empty">
        <td colspan="6" class="px-6 py-12 text-center">
            <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No orders yet.</p>
        </td>
    </tr>
<?php endif; ?>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/admin/partials/recent-orders-rows.blade.php ENDPATH**/ ?>