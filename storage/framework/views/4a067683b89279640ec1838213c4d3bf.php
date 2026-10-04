<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['order', 'barcode' => true]));

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

foreach (array_filter((['order', 'barcode' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>



<?php
    $barcodeBase64 = null;
    $barcodeString = null;

    // The QR is generated here because the modal markup is rendered per order
    // on the page, with no controller in between to pass the image in.
    // BarcodeService only reads the already-loaded $order->items relation, so
    // this costs no extra queries. Pass :barcode="false" on a page that
    // renders many receipts at once to skip it.
    if ($barcode) {
        $service = app(\App\Services\BarcodeService::class);
        $barcodeString = $service->generateBarcodeString($order);
        $barcodeBase64 = base64_encode($service->generateQrCodeImage($order));
    }
?>

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width: 100%;">
    <tr>
        <td align="center" style="padding: 4px 0;">
            <?php if (isset($component)) { $__componentOriginal58d24639dc36849acf723c42be1c276b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal58d24639dc36849acf723c42be1c276b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.receipt-document','data' => ['order' => $order,'barcodeBase64' => $barcodeBase64,'barcodeString' => $barcodeString]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('receipt-document'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['order' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order),'barcode-base64' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($barcodeBase64),'barcode-string' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($barcodeString)]); ?>
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
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/receipt-content.blade.php ENDPATH**/ ?>