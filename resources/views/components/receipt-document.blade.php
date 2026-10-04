@props(['order', 'barcodeBase64' => null, 'barcodeString' => null, 'disclaimer' => false])

{{--
    The single source of truth for the customer-facing receipt body.

    Rendered by all four customer paths, so they can never drift:
      - resources/views/emails/orders/receipt-online.blade.php  (email + PDF)
      - resources/views/components/receipt-content.blade.php     (on screen)

    Pair it with <x-receipt-styles />.

    Table markup with inline styles only: flexbox/grid and CSS variables do not
    survive domPDF, and this same output is emailed, printed and viewed on a
    phone. The font stack leads with DejaVu Sans because it is the only face
    domPDF ships that contains the Naira sign (U+20A6).
--}}

@php
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
@endphp

<table class="rd rd-card" width="680" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="width: 680px; max-width: 680px; background: #ffffff; border-radius: 10px; overflow: hidden; font-family: 'Segoe UI', 'DejaVu Sans', Arial, sans-serif; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);">

    {{-- ================= BRANDED HEADER ================= --}}
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

    {{-- ================= REFERENCE + STAMP ================= --}}
    <tr>
        <td class="rd-pad" style="padding: 24px 32px 20px 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                    <td class="rd-stack" width="58%" valign="top" style="width: 58%; font-size: 12px; color: #64748b; line-height: 20px;">
                        <span style="display: block; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">Order number</span>
                        <span style="display: block; font-size: 24px; font-weight: bold; color: #0f172a; line-height: 30px;">
                            #{{ $order->id }}
                        </span>
                    </td>
                    <td class="rd-stack" width="42%" valign="top" align="right" style="width: 42%; text-align: right;">
                        <table class="rd" cellpadding="0" cellspacing="0" border="0" role="presentation"
                               style="margin-left: auto; background: {{ $stampBg }}; border-radius: 4px;">
                            <tr>
                                <td style="padding: 6px 12px; color: #ffffff; font-size: 10px; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase;">
                                    {{ $stampLabel }}
                                </td>
                            </tr>
                        </table>
                        <div style="padding-top: 8px; font-size: 12px; color: #64748b; line-height: 18px;">
                            {{ $order->created_at->format('d M Y') }}<br>
                            <span style="color: #94a3b8;">{{ $order->created_at->format('g:i A') }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ================= AMOUNT PAID ================= --}}
    <tr>
        <td class="rd-pad" style="padding: 0 32px 22px 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <tr>
                    <td class="rd-stack" width="55%" valign="middle" style="width: 55%; padding: 18px 20px; font-size: 12px; color: #475569; line-height: 19px;">
                        <strong style="display: block; color: #0f172a; font-size: 13px;">{{ $statusLabel }}</strong>
                        <span style="color: #64748b;">
                            {{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}
                            &bull; {{ str_replace('_', ' ', ucfirst((string) $order->payment_method)) }}
                        </span>
                    </td>
                    <td class="rd-stack" width="45%" valign="middle" align="right" style="width: 45%; padding: 18px 20px; text-align: right;">
                        <span style="display: block; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">
                            {{ $isPaid ? 'Amount paid' : 'Total due' }}
                        </span>
                        <span class="rd-amount" style="display: block; font-size: 26px; font-weight: bold; color: #059669; line-height: 32px;">
                            &#8358;{{ number_format((float) $order->total_amount, 2) }}
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ================= ITEMS ================= --}}
    <tr>
        <td class="rd-pad" style="padding: 0 32px;">
            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                    <td style="padding-bottom: 10px; font-size: 11px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #94a3b8; border-bottom: 2px solid #0f172a;">
                        Items purchased
                    </td>
                </tr>

                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                            <table class="rd" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                                <tr>
                                    <td class="rd-stack rd-item-name" width="70%" valign="top"
                                        style="width: 70%; font-size: 14px; font-weight: bold; color: #0f172a; line-height: 20px; padding-right: 8px;">
                                        {{ $item->item->name ?? 'Deleted Item' }}
                                        <span style="display: block; padding-top: 3px; font-size: 12px; font-weight: normal; color: #64748b;">
                                            &#8358;{{ number_format((float) $item->price_at_order, 2) }} each
                                        </span>
                                    </td>
                                    <td class="rd-stack rd-hide-sm" width="10%" valign="top" align="center"
                                        style="width: 10%; text-align: center; font-size: 13px; color: #475569; line-height: 20px;">
                                        &times;{{ $item->quantity }}
                                    </td>
                                    <td class="rd-stack" width="20%" valign="top" align="right"
                                        style="width: 20%; text-align: right; font-size: 14px; font-weight: bold; color: #0f172a; line-height: 20px; white-space: nowrap;">
                                        &#8358;{{ number_format((float) $item->price_at_order * $item->quantity, 2) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>

    {{-- ================= TOTALS ================= --}}
    <tr>
        <td class="rd-pad" align="right" style="padding: 18px 32px 24px 32px;">
            <table class="rd rd-tot-table" width="300" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="width: 300px; max-width: 100%;">
                <tr>
                    <td class="rd-tot-label" align="left" style="padding: 4px 0; font-size: 13px; color: #64748b;">Subtotal</td>
                    <td class="rd-tot-value" align="right" style="padding: 4px 0; font-size: 13px; color: #0f172a; white-space: nowrap;">
                        &#8358;{{ number_format((float) $order->total_amount - (float) $order->delivery_fee, 2) }}
                    </td>
                </tr>
                @if ((float) $order->delivery_fee > 0)
                    <tr>
                        <td class="rd-tot-label" align="left" style="padding: 4px 0; font-size: 13px; color: #64748b;">Delivery fee</td>
                        <td class="rd-tot-value" align="right" style="padding: 4px 0; font-size: 13px; color: #0f172a; white-space: nowrap;">
                            &#8358;{{ number_format((float) $order->delivery_fee, 2) }}
                        </td>
                    </tr>
                @endif
                <tr>
                    <td colspan="2" style="padding: 10px 0 0 0; border-top: 2px solid #0f172a;"></td>
                </tr>
                <tr>
                    <td class="rd-tot-label" align="left" style="padding: 10px 0 0 0; font-size: 15px; font-weight: bold; color: #0f172a;">Total</td>
                    <td class="rd-tot-value" align="right" style="padding: 10px 0 0 0; font-size: 17px; font-weight: bold; color: #059669; white-space: nowrap;">
                        &#8358;{{ number_format((float) $order->total_amount, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ================= PAYMENT + DELIVERY DETAILS ================= --}}
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
                                    <strong style="color: #0f172a;">{{ str_replace('_', ' ', ucfirst((string) $order->payment_method)) }}</strong>
                                    @if (! empty($order->payment_reference))
                                        <br><span style="color: #94a3b8;">Ref: {{ $order->payment_reference }}</span>
                                    @endif
                                </td>
                                <td class="rd-stack" width="50%" valign="top" style="width: 50%; font-size: 12px; color: #64748b; line-height: 21px; padding-left: 10px;">
                                    <span style="display: block; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8;">
                                        {{ $order->delivery_method === 'delivery' ? 'Delivery address' : 'Collection' }}
                                    </span>
                                    @if ($order->delivery_method === 'delivery')
                                        <strong style="color: #0f172a;">{{ $order->delivery_address }}</strong>
                                    @else
                                        <strong style="color: #0f172a;">Pick up at our store</strong>
                                    @endif
                                    <br><span style="color: #94a3b8;">{{ $order->customer_phone }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ================= QR ================= --}}
    @if (! empty($barcodeBase64))
        <tr>
            <td class="rd-pad" align="center" style="padding: 0 32px 24px 32px;">
                <table class="rd" cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin: 0 auto;">
                    <tr>
                        <td align="center" style="padding: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Order QR code" width="120" height="120" style="display: block; width: 120px; height: 120px; border: 0;">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top: 8px; font-size: 10px; color: #94a3b8; letter-spacing: 0.5px;">
                            Scan to verify this order &bull; {{ $barcodeString ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    {{-- ================= FOOTER ================= --}}
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
                        Keep this receipt for your records. Quote order #{{ $order->id }} for any enquiry.
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>

@if ($disclaimer)
    <table class="rd rd-no-print" width="680" cellpadding="0" cellspacing="0" border="0" role="presentation"
           style="width: 680px; max-width: 680px; font-family: 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;">
        <tr>
            <td align="center" style="padding-top: 14px; font-size: 11px; color: #94a3b8; line-height: 18px;">
                This is an automatically generated order confirmation.
            </td>
        </tr>
    </table>
@endif
