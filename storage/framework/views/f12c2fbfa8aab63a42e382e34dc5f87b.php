<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([]));

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

foreach (array_filter(([]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<style id="receipt-style">
    table.rd { border-collapse: collapse; }
    .rd-card { width: 680px; max-width: 680px; }
    .rd-pad { padding-left: 32px; padding-right: 32px; }

    /* Phones: drop the card to full width, pad tighter, and stack the
       side-by-side cells so nothing is squeezed. */
    @media only screen and (max-width: 700px) {
        .rd-card { width: 100% !important; max-width: 100% !important; }
        .rd-pad { padding-left: 16px !important; padding-right: 16px !important; }
        .rd-stack { display: block !important; width: 100% !important; }
        .rd-stack-gap { height: 14px !important; line-height: 14px !important; font-size: 14px !important; }
        .rd-hide-sm { display: none !important; }
        .rd-amount { font-size: 26px !important; }
        .rd-item-name { font-size: 14px !important; }
        .rd-tot-label, .rd-tot-value { font-size: 14px !important; }
        .rd-tot-table { width: 100% !important; }
        .rd-tot-align { text-align: left !important; }
    }

    /* Printing the customer's own copy: drop the grey page, the shadow and
       the rounded corners so the ink lands on white paper. */
    @media print {
        .rd-page, .rd-body { background: #ffffff !important; }
        .rd-card { box-shadow: none !important; border-radius: 0 !important; width: 100% !important; max-width: 100% !important; }
        .rd-no-print { display: none !important; }
    }
</style>
<?php /**PATH C:\Users\Smk\Desktop\supermarket\resources\views/components/receipt-styles.blade.php ENDPATH**/ ?>