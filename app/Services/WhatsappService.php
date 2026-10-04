<?php

namespace App\Services;

/**
 * Builds wa.me links from loosely formatted phone numbers.
 *
 * Customer phones reach us in whatever shape the shopper typed at checkout
 * (0806..., +234806..., 234806..., 806...), so every link is normalised
 * before it is handed to WhatsApp or it silently opens the wrong chat.
 */
class WhatsappService
{
    /** Nigeria's country code. The store is NG-only, so this is the only prefix we prepend. */
    public const COUNTRY_CODE = '234';

    /**
     * Reduce a phone number to bare international digits, or null if it is
     * unusable. Returns null for empty input, values that are too short to be
     * a real number, and anything containing letters.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Keep digits only. This drops spaces, dashes, brackets and a leading +.
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        // 0023 / 023 international access prefixes -> 23
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $national = substr($digits, strlen(self::COUNTRY_CODE));
        } elseif (str_starts_with($digits, '0')) {
            // Local trunk form: 0806... -> 806...
            $national = substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            // Bare national form, e.g. 8061400220
            $national = $digits;
        } else {
            // Not a recognisable Nigerian shape; leave it alone and let
            // WhatsApp decide, but only if it is long enough to be a number.
            $national = strlen($digits) >= 10 ? $digits : null;
        }

        if ($national === null || strlen($national) < 10) {
            return null;
        }

        // wa.me needs the full international number, country code included.
        return self::COUNTRY_CODE . $national;
    }

    /**
     * Build a wa.me deep link, optionally pre-filling a message.
     *
     * Returns null when the number cannot be normalised, so callers can skip
     * rendering the button instead of linking to a broken chat.
     */
    public static function link(?string $phone, ?string $message = null): ?string
    {
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            return null;
        }

        $url = 'https://wa.me/' . $normalized;

        if ($message !== null && trim($message) !== '') {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }

    /**
     * A full order confirmation for WhatsApp.
     *
     * WhatsApp renders plain text, never HTML, so this mirrors the emailed
     * confirmation with box-drawing characters and fixed-width columns rather
     * than trying to pass markup through. Kept narrow (under ~40 chars of
     * content) so it stays readable on a phone without side-scrolling.
     */
    public static function orderMessage(\App\Models\Order $order, ?string $agentName = null): string
    {
        $isCancelled = $order->status === 'cancelled';
        $isCash = $order->payment_method === 'cash_on_delivery';

        // orders.payment_status is an enum of pending|verified|failed|refunded.
        // The paid value is 'verified' - 'success' belongs to the separate
        // payments table and does not exist on orders.
        $paymentStatus = (string) ($order->payment_status ?? '');
        $isPaid = !$isCash && $paymentStatus === 'verified';

        if ($isCancelled) {
            $statusLine = 'CANCELLED';
        } elseif ($paymentStatus === 'refunded') {
            $statusLine = 'REFUNDED';
        } elseif ($paymentStatus === 'failed') {
            $statusLine = 'PAYMENT FAILED';
        } elseif ($isPaid) {
            $statusLine = 'PAYMENT CONFIRMED';
        } elseif ($isCash) {
            $statusLine = 'UNPAID - CASH ON DELIVERY';
        } else {
            $statusLine = 'AWAITING PAYMENT';
        }

        $statusLabels = [
            'new' => 'Order received',
            'processing' => 'Being prepared',
            'paid' => 'Payment confirmed',
            'shipped' => 'Out for delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        $intro = $agentName && trim($agentName) !== ''
            ? "Hello {$order->customer_name}, this is {$agentName} from MDS Supermarket."
            : "Hello {$order->customer_name}, this is MDS Supermarket.";

        $lines = [
            '*ORDER CONFIRMATION*',
            '',
            $intro,
            '',
            "Order:      #{$order->id}",
            "Date:       " . $order->created_at->format('d M Y, g:i A'),
            "Status:     " . ($statusLabels[$order->status] ?? ucfirst((string) $order->status)),
            "Payment:    " . str_replace('_', ' ', ucfirst((string) $order->payment_method)),
        ];

        if (! empty($order->payment_reference)) {
            $lines[] = 'Ref:        ' . $order->payment_reference;
        }

        $lines[] = '';
        $lines[] = '*ITEMS*';

        foreach ($order->items as $item) {
            $name = $item->item->name ?? 'Deleted Item';
            $line = number_format((float) $item->price_at_order, 2);
            $qty = (int) $item->quantity;
            $amount = number_format((float) $item->price_at_order * $qty, 2);

            $lines[] = '';
            $lines[] = "{$qty} x {$name}";
            $lines[] = "    N{$line}  =  N{$amount}";
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 30);
        $lines[] = 'Subtotal:   N' . number_format((float) $order->total_amount - (float) $order->delivery_fee, 2);

        if ((float) $order->delivery_fee > 0) {
            $lines[] = 'Delivery:   N' . number_format((float) $order->delivery_fee, 2);
        }

        $lines[] = str_repeat('-', 30);
        $lines[] = 'TOTAL:      N' . number_format((float) $order->total_amount, 2);
        $lines[] = str_repeat('-', 30);
        $lines[] = '';
        $lines[] = "*STATUS: {$statusLine}*";

        if ($order->delivery_method === 'delivery' && ! empty($order->delivery_address)) {
            $lines[] = '';
            $lines[] = '*DELIVER TO*';
            $lines[] = $order->delivery_address;
        }

        $lines[] = '';
        $lines[] = 'Thank you for shopping with us!';
        $lines[] = 'Keep order #' . $order->id . ' for your records.';

        return implode("\n", $lines);
    }
}
