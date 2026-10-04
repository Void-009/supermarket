<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - Order #{{ $order->id }}</title>
    <style>
        body { font-family: 'Courier New', 'Lucida Console', monospace; color: #000; line-height: 1.5; background: #fff; }
        .receipt-box { max-width: 320px; margin: 0 auto; padding: 10px; }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .divider-thick { border-top: 2px solid #000; margin: 5px 0; }
        .header h1 { font-size: 16px; margin: 0 0 2px; }
        .order-id { font-size: 18px; font-weight: bold; }
        table { width: 100%; font-size: 11px; border-collapse: collapse; }
        td { padding: 2px 0; }
        .right { text-align: right; }
        .center { text-align: center; }
        .grand td { font-weight: bold; font-size: 14px; }
        .barcode-section { text-align: center; margin-top: 10px; }
        .barcode-text { font-size: 9px; letter-spacing: 0.5px; }
        .footer { text-align: center; font-size: 9px; margin-top: 10px; }
        a { color: #059669; }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="header center">
            <h1>M-DELIGHT SINGER STORES</h1>
            <div style="font-size:10px;">Receipt &bull; Order #{{ $order->id }}</div>
            <div style="font-size:9px;">{{ $order->created_at->format('d/m/Y') }} &bull; {{ $order->created_at->format('g:i A') }}</div>        </div>

        <div class="divider"></div>

        <table>
            <tr><td>Customer:</td><td class="right"><strong>{{ $order->customer_name }}</strong></td></tr>
            <tr><td>Phone:</td><td class="right">{{ $order->customer_phone }}</td></tr>
            @if($order->customer_email)<tr><td>Email:</td><td class="right">{{ $order->customer_email }}</td></tr>@endif
            <tr><td>Payment:</td><td class="right">{{ str_replace('_', ' ', ucfirst($order->payment_method)) }}</td></tr>
            <tr><td>Ref:</td><td class="right" style="font-size:10px;">{{ $order->payment_reference }}</td></tr>
        </table>

        <div class="divider"></div>

        <table>
            <tr style="font-weight:bold;">
                <td>ITEM</td>
                <td class="right">QTY</td>
                <td class="right">PRICE</td>
                <td class="right">TOTAL</td>
            </tr>
            <tr><td colspan="4" style="padding:0;"><div class="divider"></div></td></tr>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->item->name ?? 'Deleted' }}</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">₦{{ number_format($item->price_at_order, 0) }}</td>
                <td class="right">₦{{ number_format($item->price_at_order * $item->quantity, 0) }}</td>
            </tr>
            @endforeach
        </table>

        <div class="divider"></div>

        <table>
            <tr><td>Subtotal</td><td class="right">₦{{ number_format($order->total_amount - $order->delivery_fee, 0) }}</td></tr>
            @if($order->delivery_fee > 0)<tr><td>Delivery Fee</td><td class="right">₦{{ number_format($order->delivery_fee, 0) }}</td></tr>@endif
        </table>
        <div class="divider-thick"></div>
        <table>
            <tr class="grand"><td>TOTAL</td><td class="right">₦{{ number_format($order->total_amount, 0) }}</td></tr>
        </table>

        <div class="barcode-section">
            @if(isset($barcodeBase64))
                <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="QR Code" style="max-width:150px; height:auto;">
            @endif
            <div class="barcode-text">{{ $barcodeString ?? '' }}</div>
            <p style="font-size:8px; margin-top:2px;">Scan QR code to record the sale</p>
        </div>

        <div class="footer">
            Thank you for your patronage!<br>
            M-Delight Singer Stores
        </div>
    </div>
</body>
</html>
