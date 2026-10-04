<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->id }}</title>

    {{--
        Document shell only. The receipt body lives in
        <x-receipt-document /> so the emailed receipt, the PDF attachment and
        the on-screen receipt are literally the same markup.

        This view is rendered by two very different engines:
          - email clients, which need the full <html>/<head> wrapper
          - domPDF for the PDF attachment, which needs @page and the same CSS
    --}}
    <style>
        @page { margin: 0; }
        .rd-body { margin: 0; padding: 0; background: #eef2f6; -webkit-text-size-adjust: 100%; }
        .rd-page { width: 100%; background: #eef2f6; }
    </style>

    <x-receipt-styles />
</head>
<body class="rd-body">

<table class="rd rd-page" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
    <tr>
        <td align="center" style="padding: 24px 12px;">

            <x-receipt-document
                :order="$order"
                :barcode-base64="$barcodeBase64 ?? null"
                :barcode-string="$barcodeString ?? null"
                disclaimer
            />

        </td>
    </tr>
</table>

</body>
</html>
