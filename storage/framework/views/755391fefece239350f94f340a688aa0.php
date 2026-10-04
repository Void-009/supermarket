<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['order', 'barcodeBase64' => null, 'barcodeString' => null, 'disclaimer' => false]));

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

foreach (array_filter((['order', 'barcodeBase64' => null, 'barcodeString' => null, 'disclaimer' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>



<?php
    $isCancelled = $order->status === 'cancelled';
    $isCash = $order->payment_method === 'cash_on_delivery';

    // "Paid" is driven by the payment record, not the order status: a
    // cash-on-delivery order sits in `shipped` while still genuinely unpaid.
    //
    // orders.payment_status is an enum of pending|verified|failed|refunded
    // (see 2026_07_20_221146_add_payment_fields_to_orders_table). The paid
    // value is 'verified' - NOT 'success', which is the separate payments
    // table's vocabulary and does not exist on orders.
    $paymentStatus = (string) ($order->payment_status ?? '');
    $isPaid = !$isCash && $paymentStatus === 'verified';

    if ($isCancelled) {
        $stampLabel = 'CANCELLED';
        $stampBg = '#dc2626';
    } elseif ($paymentStatus === 'refunded') {
        $stampLabel = 'REFUNDED';
        $stampBg = '#475569';
    } elseif ($paymentStatus === 'failed') {
        $stampLabel = 'PAYMENT FAILED';
        $stampBg = '#dc2626';
    } elseif ($isPaid) {
        $stampLabel = 'PAYMENT CONFIRMED';
        $stampBg = '#059669';
    } elseif ($isCash) {
        $stampLabel = 'UNPAID - CASH ON DELIVERY';
        $stampBg = '#b45309';
    } else {
        $stampLabel = 'AWAITING PAYMENT';
        $stampBg = '#b45309';
    }

    $statusLabels = [
        'new' => 'Order received',
        'processing' => 'Being prepared',
        'paid' => 'Payment confirmed',
        'shipped' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
    $statusLabel = $statusLabels[$order->status] ?? ucfirst((string) $order->status);
    $itemCount = $order->items->count();
?>

<table class="rd rd-card" width="680" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="width: 680px; max-width: 680px; background: #ffffff; border-radius: 10px; overflow: hidden; font-family: 'Segoe UI', 'DejaVu Sans', Arial, sans-serif; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);">

    
    <tr>
        <td class="rd-pad" align="center" style="padding: 26px 32px; background: #065f46; color: #ffffff;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                    <td align="center" style="font-size: 20px; font-weight: bold; letter-spacing: 1.5px; line-height: 26px;">
                        M&#8209;DELIGHT SINGER STORES
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 4px; font-size: 11px; letter-spacing: 2.5px; color: #a7f3d0; text-transform: uppercase;">
                        Order Confirmation
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    
    <tr>
        <td class="rd-pad" style="padding: 24px 32px 20px 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                    <td class="rd-stack" width="58%" valign="top" style="width: 58%; font-size: 12px; color: #64748b; line-height: 20px;">
                        <span style="display: block; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">Order number</span>
                        <span style="display: block; font-size: 24px; font-weight: bold; color: #0f172a; line-height: 30px;">
                            #<?php echo e($order->id); ?>

                        </span>
                    </td>
                    <td class="rd-stack" width="42%" valign="top" align="right" style="width: 42%; text-align: right;">
                        <table class="rd" cellpadding="0" cellspacing="0" border="0" role="presentation"
                               style="margin-left: auto; background: <?php echo e($stampBg); ?>; border-radius: 4px;">
                            <tr>
                                <td style="padding: 6px 12px; color: #ffffff; font-size: 10px; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase;">
                                    <?php echo e($stampLabel); ?>

                                </td>
                            </tr>
                        </table>
                        <div style="padding-top: 8px; font-size: 12px; color: #64748b; line-height: 18px;">
                            <?php echo e($order->created_at->format('d M Y')); ?><br>
                            <span style="color: #94a3b8;"><?php echo e($order->created_at->format('g:i A')); ?></span>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    
    <tr>
        <td class="rd-pad" style="padding: 0 32px 22px 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <tr>
                    <td class="rd-stack" width="55%" valign="middle" style="width: 55%; padding: 18px 20px; font-size: 12px; color: #475569; line-height: 19px;">
                        <strong style="display: block; color: #0f172a; font-size: 13px;"><?php echo e($statusLabel); ?></strong>
                        <span style="color: #64748b;">
                            <?php echo e($itemCount); ?> <?php echo e(\Illuminate\Support\Str::plural('item', $itemCount)); ?>

                            &bull; <?php echo e(str_replace('_', ' ', ucfirst((string) $order->payment_method))); ?>

                        </span>
                    </td>
                    <td class="rd-stack" width="45%" valign="middle" align="right" style="width: 45%; padding: 18px 20px; text-align: right;">
                        <span style="display: block; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">
                            <?php echo e($isPaid ? 'Amount paid' : 'Total due'); ?>

                        </span>
                        <span class="rd-amount" style="display: block; font-size: 26px; font-weight: bold; color: #059669; line-height: 32px;">
                            &#8358;<?php echo e(number_format((float) $order->total_amount, 2)); ?>

                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    
    <tr>
        <td class="rd-pad" style="padding: 0 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                    <td style="padding-bottom: 10px; font-size: 11px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #94a3b8; border-bottom: 2px solid #0f172a;">
                        Items purchased
                    </td>
                </tr>

                <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                                <tr>
                                    <td class="rd-stack rd-item-name" width="70%" valign="top"
                                        style="width: 70%; font-size: 14px; font-weight: bold; color: #0f172a; line-height: 20px; padding-right: 8px;">
                                        <?php echo e($item->item->name ?? 'Deleted Item'); ?>

                                        <span style="display: block; padding-top: 3px; font-size: 12px; font-weight: normal; color: #64748b;">
                                            &#8358;<?php echo e(number_format((float) $item->price_at_order, 2)); ?> each
                                        </span>
                                    </td>
                                    <td class="rd-stack rd-hide-sm" width="10%" valign="top" align="center"
                                        style="width: 10%; text-align: center; font-size: 13px; color: #475569; line-height: 20px;">
                                        &times;<?php echo e($item->quantity); ?>

                                    </td>
                                    <td class="rd-stack" width="20%" valign="top" align="right"
                                        style="width: 20%; text-align: right; font-size: 14px; font-weight: bold; color: #0f172a; line-height: 20px; white-space: nowrap;">
                                        &#8358;<?php echo e(number_format((float) $item->price_at_order * $item->quantity, 2)); ?>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
        </td>
    </tr>

    
    <tr>
        <td class="rd-pad" align="right" style="padding: 18px 32px 24px 32px;">
            <table class="rd rd-tot-table" width="300" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="width: 300px; max-width: 100%;">
                <tr>
                    <td class="rd-tot-label" align="left" style="padding: 4px 0; font-size: 13px; color: #64748b;">Subtotal</td>
                    <td class="rd-tot-value" align="right" style="padding: 4px 0; font-size: 13px; color: #0f172a; white-space: nowrap;">
                        &#8358;<?php echo e(number_format((float) $order->total_amount - (float) $order->delivery_fee, 2)); ?>

                    </td>
                </tr>
                <?php if((float) $order->delivery_fee > 0): ?>
                    <tr>
                        <td class="rd-tot-label" align="left" style="padding: 4px 0; font-size: 13px; color: #64748b;">Delivery fee</td>
                        <td class="rd-tot-value" align="right" style="padding: 4px 0; font-size: 13px; color: #0f172a; white-space: nowrap;">
                            &#8358;<?php echo e(number_format((float) $order->delivery_fee, 2)); ?>

                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" style="padding: 10px 0 0 0; border-top: 2px solid #0f172a;"></td>
                </tr>
                <tr>
                    <td class="rd-tot-label" align="left" style="padding: 10px 0 0 0; font-size: 15px; font-weight: bold; color: #0f172a;">Total</td>
                    <td class="rd-tot-value" align="right" style="padding: 10px 0 0 0; font-size: 17px; font-weight: bold; color: #059669; white-space: nowrap;">
                        &#8358;<?php echo e(number_format((float) $order->total_amount, 2)); ?>

                    </td>
                </tr>
            </table>
        </td>
    </tr>

    
    <tr>
        <td class="rd-pad" style="padding: 0 32px 24px 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <tr>
                    <td style="padding: 18px 20px;">
                        <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                            <tr>
                                <td class="rd-stack" width="50%" valign="top" style="width: 50%; font-size: 12px; color: #64748b; line-height: 21px; padding-right: 10px;">
                                    <span style="display: block; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">Payment method</span>
                                    <strong style="color: #0f172a;"><?php echo e(str_replace('_', ' ', ucfirst((string) $order->payment_method))); ?></strong>
                                    <?php if(! empty($order->payment_reference)): ?>
                                        <br><span style="color: #94a3b8;">Ref: <?php echo e($order->payment_reference); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="rd-stack" width="50%" valign="top" style="width: 50%; font-size: 12px; color: #64748b; line-height: 21px; padding-left: 10px;">
                                    <span style="display: block; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">
                                        <?php echo e($order->delivery_method === 'delivery' ? 'Delivery address' : 'Collection'); ?>

                                    </span>
                                    <?php if($order->delivery_method === 'delivery'): ?>
                                        <strong style="color: #0f172a;"><?php echo e($order->delivery_address); ?></strong>
                                    <?php else: ?>
                                        <strong style="color: #0f172a;">Pick up at our store</strong>
                                    <?php endif; ?>
                                    <br><span style="color: #94a3b8;"><?php echo e($order->customer_phone); ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    
    <?php if(! empty($barcodeBase64)): ?>
        <tr>
            <td class="rd-pad" align="center" style="padding: 0 32px 24px 32px;">
                <table class="rd" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin: 0 auto;">
                    <tr>
                        <td align="center" style="padding: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <img src="data:image/png;base64,<?php echo e($barcodeBase64); ?>" alt="Order QR code" width="120" height="120" style="display: block; width: 120px; height: 120px; border: 0;">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top: 8px; font-size: 10px; color: #94a3b8; letter-spacing: 0.5px;">
                            Scan to verify this order &bull; <?php echo e($barcodeString ?? ''); ?>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php endif; ?>

    
    <tr>
        <td class="rd-pad" align="center" style="padding: 22px 32px; background: #065f46; color: #ffffff;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                    <td align="center" style="font-size: 15px; font-weight: bold; line-height: 22px;">
                        Thank you for shopping with us!
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 6px; font-size: 11px; color: #a7f3d0; line-height: 18px;">
                        Keep this receipt for your records. Quote order #<?php echo e($order->id); ?> for any enquiry.
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>

<?php if($disclaimer): ?>
    <table class="rd rd-no-print" width="680" cellpadding="0" cellspacing="0" border="0" role="presentation"
           style="width: 680px; max-width: 680px; font-family: 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;">
        <tr>
            <td align="center" style="padding-top: 14px; font-size: 11px; color: #94a3b8; line-height: 18px;">
                This is an automatically generated order confirmation.
            </td>
        </tr>
    </table>
<?php endif; ?>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/receipt-document.blade.php ENDPATH**/ ?>