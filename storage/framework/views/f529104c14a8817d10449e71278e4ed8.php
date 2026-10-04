<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #<?php echo e($order->id); ?></title>

    
    <style>
        @page { margin: 0; }
        .rd-body { margin: 0; padding: 0; background: #eef2f6; -webkit-text-size-adjust: 100%; }
        .rd-page { width: 100%; background: #eef2f6; }
    </style>

    <?php if (isset($component)) { $__componentOriginal13f4eeea782c92c363d1bb71bab8b935 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal13f4eeea782c92c363d1bb71bab8b935 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.receipt-styles','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('receipt-styles'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal13f4eeea782c92c363d1bb71bab8b935)): ?>
<?php $attributes = $__attributesOriginal13f4eeea782c92c363d1bb71bab8b935; ?>
<?php unset($__attributesOriginal13f4eeea782c92c363d1bb71bab8b935); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal13f4eeea782c92c363d1bb71bab8b935)): ?>
<?php $component = $__componentOriginal13f4eeea782c92c363d1bb71bab8b935; ?>
<?php unset($__componentOriginal13f4eeea782c92c363d1bb71bab8b935); ?>
<?php endif; ?>
</head>
<body class="rd-body">

<table class="rd rd-page" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
    <tr>
        <td align="center" style="padding: 24px 12px;">

            <?php if (isset($component)) { $__componentOriginal58d24639dc36849acf723c42be1c276b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal58d24639dc36849acf723c42be1c276b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.receipt-document','data' => ['order' => $order,'barcodeBase64' => $barcodeBase64 ?? null,'barcodeString' => $barcodeString ?? null,'disclaimer' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('receipt-document'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['order' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order),'barcode-base64' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($barcodeBase64 ?? null),'barcode-string' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($barcodeString ?? null),'disclaimer' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal58d24639dc36849acf723c42be1c276b)): ?>
<?php $attributes = $__attributesOriginal58d24639dc36849acf723c42be1c276b; ?>
<?php unset($__attributesOriginal58d24639dc36849acf723c42be1c276b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal58d24639dc36849acf723c42be1c276b)): ?>
<?php $component = $__componentOriginal58d24639dc36849acf723c42be1c276b; ?>
<?php unset($__componentOriginal58d24639dc36849acf723c42be1c276b); ?>
<?php endif; ?>

        </td>
    </tr>
</table>

</body>
</html>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/emails/orders/receipt-online.blade.php ENDPATH**/ ?>