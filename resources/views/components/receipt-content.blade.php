@props(['order', 'barcode' => true])

{{--
    Customer-facing receipt for the on-screen modal.

    Wraps the same <x-receipt-document /> the customer receives by email, so
    "View receipt" in the browser looks exactly like the emailed confirmation
    rather than the 80mm thermal roll.

    The 80mm printer version is untouched and still lives at
    admin/orders.receipt.

    This is cloned into the modal by receipt-modal.blade.php via x-html, and
    re-injected into the print window, so it must not include <html> or a
    second copy of the stylesheet.
--}}

@php
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
@endphp

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width: 100%;">
    <tr>
        <td align="center" style="padding: 4px 0;">
            <x-receipt-document
                :order="$order"
                :barcode-base64="$barcodeBase64"
                :barcode-string="$barcodeString"
            />
        </td>
    </tr>
</table>
