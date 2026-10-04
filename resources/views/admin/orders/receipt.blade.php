<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - Order #{{ $order->id }}</title>
    <style>
        @page { margin: 0; size: 80mm auto; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lucida Console', 'Courier New', monospace;
            font-size: 11px;
            width: 70mm;
            padding: 3mm 4mm;
            color: #000;
            background: #fff;
            line-height: 1.5;
            font-weight: bold;
        }
        .c { text-align: center; }
        .r { text-align: right; }

        .store-name { font-size: 16px; letter-spacing: 2px; font-weight: bold; text-align: center; }
        .store-sub { font-size: 10px; text-align: center; font-weight: bold; }

        .hr4 { border: none; border-top: 4px solid #000; margin: 4px 0; }
        .hr2 { border: none; border-top: 2px solid #000; margin: 3px 0; }
        .hr1 { border: none; border-top: 1.5px solid #000; margin: 3px 0; }

        .order-num { font-size: 18px; text-align: center; letter-spacing: 1px; font-weight: bold; }

        .info { width: 100%; font-size: 11px; font-weight: bold; }
        .info td { padding: 1px 0; white-space: nowrap; }
        .info .l { text-align: left; width: 30%; }
        .info .v { text-align: right; width: 70%; white-space: normal; word-break: break-all; }

        .items { width: 100%; font-size: 11px; font-weight: bold; }
        .items th { text-align: left; padding: 2px 0; font-size: 11px; border-bottom: 2px solid #000; }
        .items th.qty { text-align: center; }
        .items th.total { text-align: right; }

        .items td { padding: 2px 0; vertical-align: top; }
        .items tr.item-row td { padding-bottom: 6px; border-bottom: 2px solid #000; }
        .items tr.item-row:last-child td { border-bottom: none; }
        .items .in { text-align: left; word-break: break-all; }
        .items .iq { text-align: center; }
        .items .it { text-align: right; white-space: nowrap; }

        .totals { width: 100%; font-size: 11px; font-weight: bold; }
        .totals td { padding: 2px 0; }
        .totals .l { text-align: left; }
        .totals .r { text-align: right; }

        .grand { font-size: 15px; font-weight: bold; }
        .grand td { padding: 4px 0; }

        .qr-wrap { text-align: center; margin-top: 5px; }
        .qr-wrap img { max-width: 120px; height: auto; }
        .barcode-text { font-size: 8px; text-align: center; letter-spacing: 0.5px; font-weight: bold; margin-top: 2px; word-break: break-all; }

        .ftr { text-align: center; font-size: 10px; font-weight: bold; margin-top: 6px; }

        @media print { body { width: auto; } .np { display: none; } }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="store-name">M-DELIGHT SINGER STORES</div>
    <div class="store-sub">RECEIPT &bull; ORDER RECEIPT</div>
    <div class="hr4"></div>

    <div class="order-num">#{{ $order->id }}</div>
    <div class="hr2"></div>

    {{-- CUSTOMER INFO --}}
    <table class="info">
        <tr><td class="l">Date:</td><td class="v">{{ $order->created_at->format('d/m/Y') }}</td></tr>
        <tr><td class="l">Time:</td><td class="v">{{ $order->created_at->format('g:i A') }}</td></tr>
        <tr><td class="l">Customer:</td><td class="v">{{ $order->customer_name }}</td></tr>
        <tr><td class="l">Phone:</td><td class="v">{{ $order->customer_phone }}</td></tr>
        @if($order->customer_email)
        <tr><td class="l">Email:</td><td class="v">{{ $order->customer_email }}</td></tr>
        @endif
        <tr><td class="l">Payment:</td><td class="v">{{ str_replace('_', ' ', ucfirst($order->payment_method)) }}</td></tr>
        <tr><td class="l">Ref No:</td><td class="v">{{ $order->payment_reference }}</td></tr>
        @if($order->delivery_method === 'delivery')
        <tr><td class="l">Address:</td><td class="v">{{ $order->delivery_address }}</td></tr>
        @endif
    </table>

    <div class="hr2"></div>

    {{-- ITEMS TABLE --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:54%;">ITEM</th>
                <th class="qty" style="width:12%;">QTY</th>
                <th class="total" style="width:34%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr class="item-row">
                <td class="in">{{ $item->item->name ?? 'Deleted Item' }}<br>₦{{ number_format($item->price_at_order, 0) }}</td>
                <td class="iq">{{ $item->quantity }}</td>
                <td class="it">₦{{ number_format($item->price_at_order * $item->quantity, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="hr2"></div>

    {{-- TOTALS --}}
    <table class="totals">
        <tr>
            <td class="l">Subtotal</td>
            <td class="r">₦{{ number_format($order->total_amount - $order->delivery_fee, 0) }}</td>
        </tr>
        @if($order->delivery_fee > 0)
        <tr>
            <td class="l">Delivery Fee</td>
            <td class="r">₦{{ number_format($order->delivery_fee, 0) }}</td>
        </tr>
        @endif
    </table>

    <div class="hr4"></div>

    <table class="totals grand">
        <tr>
            <td class="l">TOTAL</td>
            <td class="r">₦{{ number_format($order->total_amount, 0) }}</td>
        </tr>
    </table>

    <div class="hr2"></div>

    <div class="c" style="font-size:11px;">
        @if($order->payment_method === 'cash_on_delivery')
            ** UNPAID - CASH ON DELIVERY **
        @else
            PAYMENT: {{ strtoupper($order->payment_status) }}
        @endif
    </div>

    <div class="hr4"></div>

    {{-- QR CODE --}}
    <div class="qr-wrap">
        @if(isset($barcodeBase64))
            <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="QR Code">
        @endif
        @if(isset($barcodeString))
            <div class="barcode-text">{{ $barcodeString }}</div>
        @endif
    </div>

    <div class="hr1"></div>

    {{-- FOOTER --}}
    <div class="ftr">
        THANK YOU FOR YOUR PATRONAGE!<br>
        M-DELIGHT SINGER STORES<br>
        <span style="font-size:8px;">POWERED BY MDELIGHT</span>
    </div>

    {{-- CONTROLS --}}
    <div class="np c" style="margin-top:12px;">
        <button onclick="window.print()" style="padding:8px 30px; font-size:14px; font-weight:bold; cursor:pointer; background:#059669; color:white; border:none; border-radius:5px;">PRINT RECEIPT</button>
    </div>

    <script>
        window.onload = function() { setTimeout(function() { window.print(); }, 300); }
    </script>
</body>
</html>
