<?php $__env->startSection('title', 'Order #' . $order->id); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="orderDetail()" x-init="init()">

    
    <div class="mb-6">
        <a href="<?php echo e(route('customer.orders')); ?>" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <?php echo e(__('messages.back_to_my_orders')); ?>

        </a>
    </div>

    
    <?php if(session('success')): ?>
        <?php echo $__env->make('components.alert', ['type' => 'success', 'message' => session('success')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.order')); ?> #<?php echo e($order->id); ?></h1>
                <p class="text-sm text-gray-500 mt-1"><?php echo e(__('messages.placed_on', ['date' => $order->created_at->format('M d, Y \a\t g:i A')])); ?></p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <?php if($order->status === 'cancelled'): ?>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-red-50 text-red-700 border border-red-200"><?php echo e(__('messages.status_cancelled')); ?></span>
                <?php endif; ?>
                <?php if($order->isEditableByCustomer()): ?>
                    <a href="<?php echo e(route('customer.orders.edit', $order)); ?>"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 rounded-xl text-sm font-semibold text-white transition-colors shadow-sm">
                        <i data-lucide="pencil" class="w-4 h-4"></i> <?php echo e(__('messages.edit_order')); ?>

                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if(! $order->isEditableByCustomer()): ?>
            <div class="mt-4 flex items-start gap-2 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
                <i data-lucide="lock" class="w-4 h-4 mt-0.5 shrink-0 text-gray-400"></i>
                <span><?php echo e($order->customerEditLockReason()); ?></span>
            </div>
        <?php endif; ?>
    </div>

    
    <?php if(!$isCancelled): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.order_status')); ?></h2>
        <div class="relative overflow-x-auto">
            <div class="min-w-[480px] relative">
                <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-200"></div>
                <div class="absolute top-4 left-0 h-0.5 bg-emerald-500 transition-all duration-500" style="width: <?php echo e($currentIndex !== false ? ($currentIndex / (count($statusSteps) - 1) * 100) : 0); ?>%"></div>
                <div class="relative flex justify-between">
                    <?php $__currentLoopData = $statusSteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold z-10 <?php echo e($index <= $currentIndex ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-500'); ?>">
                                <?php if($index < $currentIndex): ?>
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                <?php else: ?>
                                    <?php echo e($index + 1); ?>

                                <?php endif; ?>
                            </div>
                            <span class="text-xs font-medium text-gray-600 mt-2 capitalize"><?php echo e(__("messages.status_{$step}")); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.items_ordered')); ?></h2>
                <div class="space-y-3">
                    <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl">
                        <div class="shrink-0 w-14 h-14 bg-white rounded-lg overflow-hidden border border-gray-100">
                            <?php if($item->item->image_url): ?>
                                <img src="<?php echo e(asset('storage/' . $item->item->image_url)); ?>" alt="<?php echo e($item->item->name); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                                    <i data-lucide="image" class="w-5 h-5 text-emerald-300"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 line-clamp-1"><?php echo e($item->item->name); ?></p>
                            <p class="text-xs text-gray-400"><?php echo e(__('messages.qty')); ?>: <?php echo e($item->quantity); ?> × ₦<?php echo e(number_format($item->price_at_order, 2)); ?></p>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 shrink-0">₦<?php echo e(number_format($item->price_at_order * $item->quantity, 2)); ?></span>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>

        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.summary')); ?></h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500"><?php echo e(__('messages.subtotal')); ?></span>
                        <span class="font-medium text-gray-900">₦<?php echo e(number_format($order->total_amount - $order->delivery_fee, 2)); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 min-w-0 break-words"><?php echo e(__('messages.delivery')); ?><?php echo e($order->deliveryLocation ? ' (' . $order->deliveryLocation->name . ')' : ''); ?></span>
                        <span class="font-medium <?php echo e($order->delivery_fee > 0 ? 'text-gray-900' : 'text-green-600'); ?> shrink-0 ml-2">
                            <?php echo e($order->delivery_fee > 0 ? '₦' . number_format($order->delivery_fee, 2) : __('messages.free')); ?>

                        </span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <span class="font-bold text-gray-900"><?php echo e(__('messages.total')); ?></span>
                        <span class="text-lg font-extrabold text-emerald-600">₦<?php echo e(number_format($order->total_amount, 2)); ?></span>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500"><?php echo e(__('messages.payment_method')); ?></span>
                        <span class="font-medium text-gray-900 capitalize"><?php echo e(str_replace('_', ' ', $order->payment_method)); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500"><?php echo e(__('messages.payment_status')); ?></span>
                        <?php if($order->payment_status === 'verified'): ?>
                            <span class="font-medium text-green-600"><?php echo e(__('messages.payment_verified')); ?></span>
                        <?php elseif($order->payment_status === 'failed'): ?>
                            <span class="font-medium text-red-600"><?php echo e(__('messages.payment_failed')); ?></span>
                        <?php elseif($order->payment_status === 'refunded'): ?>
                            <span class="font-medium text-purple-600"><?php echo e(__('messages.payment_refunded')); ?></span>
                        <?php else: ?>
                            <span class="font-medium text-yellow-600"><?php echo e(__('messages.payment_pending')); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if($order->delivery_address): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500"><?php echo e(__('messages.address')); ?></span>
                        <span class="font-medium text-gray-900 text-right max-w-[200px] break-words"><?php echo e($order->delivery_address); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="border-t border-gray-100 mt-4 pt-4">
                    <button type="button" onclick="openOrderReceipt(<?php echo e($order->id); ?>)" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 border border-gray-100 rounded-xl transition-colors">
                        <i data-lucide="receipt" class="w-4 h-4"></i> <?php echo e(__('messages.view_receipt')); ?>

                    </button>
                </div>

                
                <?php if(!in_array($order->status, ['cancelled']) && $order->complaints->isEmpty()): ?>
                <div class="border-t border-gray-100 mt-4 pt-4">
                    <button @click="showComplaintModal = true" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-orange-700 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition-colors">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> <?php echo e(__('messages.log_complaint')); ?>

                    </button>
                </div>
                <?php endif; ?>
            </div>

            
            <?php if($order->complaints->isNotEmpty()): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-4">
                <h3 class="text-sm font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.complaints')); ?></h3>
                <div class="space-y-2">
                    <?php $__currentLoopData = $order->complaints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $complaint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('customer.complaints.show', $complaint->id)); ?>" class="block p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 line-clamp-1"><?php echo e($complaint->subject); ?></span>
                            <?php if($complaint->status === 'open'): ?>
                                <span class="shrink-0 ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700"><?php echo e(__('messages.complaint_open')); ?></span>
                            <?php elseif($complaint->status === 'in_progress'): ?>
                                <span class="shrink-0 ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700"><?php echo e(__('messages.complaint_in_progress')); ?></span>
                            <?php else: ?>
                                <span class="shrink-0 ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700"><?php echo e(__('messages.complaint_resolved')); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-1"><?php echo e($complaint->created_at->format('M d, Y')); ?></p>
                    </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div x-show="showComplaintModal" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="showComplaintModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-4 sm:p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;"><?php echo e(__('messages.log_complaint')); ?></h3>
                <button @click="showComplaintModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form @submit.prevent="submitComplaint()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5"><?php echo e(__('messages.subject')); ?> <span class="text-red-500">*</span></label>
                        <input x-model="complaintForm.subject" type="text" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors" placeholder="<?php echo e(__('messages.subject_placeholder')); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5"><?php echo e(__('messages.message')); ?> <span class="text-red-500">*</span></label>
                        <textarea x-model="complaintForm.message" rows="5" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none" placeholder="<?php echo e(__('messages.message_placeholder')); ?>"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" @click="showComplaintModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"><?php echo e(__('messages.cancel')); ?></button>
                    <button type="submit" :disabled="submittingComplaint" class="px-6 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm disabled:opacity-50">
                        <span x-show="!submittingComplaint"><?php echo e(__('messages.submit_complaint')); ?></span>
                        <span x-show="submittingComplaint" x-cloak><?php echo e(__('messages.submitting')); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function orderDetail() {
    return {
        showComplaintModal: false,
        submittingComplaint: false,
        complaintForm: {
            subject: '',
            message: '',
        },
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        async submitComplaint() {
            if (this.submittingComplaint) return;
            this.submittingComplaint = true;
            try {
                const res = await fetch('<?php echo e(route("customer.complaints.store")); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: <?php echo e($order->id); ?>,
                        subject: this.complaintForm.subject,
                        message: this.complaintForm.message,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to submit complaint.');
                }
            } catch (e) {
                alert('Network error. Please try again.');
            }
            this.submittingComplaint = false;
        },
    };
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>


<div id="receipt-<?php echo e($order->id); ?>" class="hidden">
    <?php if (isset($component)) { $__componentOriginal56b97838224495d2a079e45fa2e16f9b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56b97838224495d2a079e45fa2e16f9b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.receipt-content','data' => ['order' => $order]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('receipt-content'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['order' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56b97838224495d2a079e45fa2e16f9b)): ?>
<?php $attributes = $__attributesOriginal56b97838224495d2a079e45fa2e16f9b; ?>
<?php unset($__attributesOriginal56b97838224495d2a079e45fa2e16f9b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56b97838224495d2a079e45fa2e16f9b)): ?>
<?php $component = $__componentOriginal56b97838224495d2a079e45fa2e16f9b; ?>
<?php unset($__componentOriginal56b97838224495d2a079e45fa2e16f9b); ?>
<?php endif; ?>
</div>


<?php if (isset($component)) { $__componentOriginal418e8d39ffcbbfdc181085b734ddf5f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal418e8d39ffcbbfdc181085b734ddf5f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.receipt-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('receipt-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal418e8d39ffcbbfdc181085b734ddf5f5)): ?>
<?php $attributes = $__attributesOriginal418e8d39ffcbbfdc181085b734ddf5f5; ?>
<?php unset($__attributesOriginal418e8d39ffcbbfdc181085b734ddf5f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal418e8d39ffcbbfdc181085b734ddf5f5)): ?>
<?php $component = $__componentOriginal418e8d39ffcbbfdc181085b734ddf5f5; ?>
<?php unset($__componentOriginal418e8d39ffcbbfdc181085b734ddf5f5); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/customer/orders/show.blade.php ENDPATH**/ ?>