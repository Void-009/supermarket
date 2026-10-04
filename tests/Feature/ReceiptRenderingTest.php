<?php

namespace Tests\Feature;

use App\Mail\OrderReceipt;
use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\BarcodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the promise that the customer's "View receipt" modal is the same
 * document as the one they were emailed.
 *
 * Both render <x-receipt-document />, but they are reached through different
 * code paths (a Blade component inside a hidden div vs. a Mailable view), so
 * nothing but a test stops them from drifting apart again.
 */
class ReceiptRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $attributes = [], array $lines = []): Order
    {
        // Unique per call: several tests build more than one order, and
        // users.email is unique.
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'receipt-' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $category = Category::firstOrCreate(['slug' => 'drinks'], ['name' => 'Drinks', 'is_active' => true]);

        // items.slug is auto-derived from the name and is unique, so the name
        // has to vary when a test builds several orders.
        $item = Item::create([
            'pos_item_id' => (string) mt_rand(100000, 999999),
            'name' => 'Cola 50cl ' . uniqid(),
            'category_id' => $category->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => 50,
            'is_visible_online' => true,
        ]);

        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'bank_transfer',
            'status' => 'new',
            'payment_status' => 'pending',
            'total_amount' => 0,
            'payment_reference' => 'ORD-TEST1234',
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => $lines['quantity'] ?? 2,
            'price_at_order' => $item->effectivePrice(),
        ]);

        $order->load('items.item');
        $order->update(['total_amount' => $order->items->sum(fn ($l) => $l->price_at_order * $l->quantity)]);

        return $order->fresh();
    }

    public function test_on_screen_receipt_is_not_the_thermal_printer_layout()
    {
        $order = $this->makeOrder();

        $html = view('components.receipt-content', ['order' => $order])->render();

        $this->assertStringContainsString('Order Confirmation', $html);
        $this->assertStringContainsString('M&#8209;DELIGHT SINGER STORES', $html);

        // The 80mm thermal markup that used to live here. If any of these come
        // back, the customer is looking at the printer roll again.
        $this->assertStringNotContainsString('receipt-pop', $html);
        $this->assertStringNotContainsString('rc-store', $html);
        $this->assertStringNotContainsString('ORDER RECEIPT', $html);
        $this->assertStringNotContainsString('width: 70mm', $html);
    }

    public function test_email_and_on_screen_receipts_render_the_same_body()
    {
        $order = $this->makeOrder();
        $service = app(BarcodeService::class);

        $onScreen = view('components.receipt-content', ['order' => $order])->render();
        $emailed = view('emails.orders.receipt-online', [
            'order' => $order,
            'barcodeString' => $service->generateBarcodeString($order),
            'barcodeBase64' => base64_encode($service->generateQrCodeImage($order)),
        ])->render();

        // Both must carry the same status stamp and total.
        $this->assertStringContainsString('AWAITING PAYMENT', $onScreen);
        $this->assertStringContainsString('AWAITING PAYMENT', $emailed);
        $this->assertStringContainsString(number_format((float) $order->total_amount, 2), $onScreen);
        $this->assertStringContainsString(number_format((float) $order->total_amount, 2), $emailed);
    }

    public function test_receipt_status_stamp_tracks_payment_not_order_status()
    {
        $render = fn (Order $o) => view('components.receipt-content', ['order' => $o, 'barcode' => false])->render();

        $cash = $this->makeOrder([
            'payment_method' => 'cash_on_delivery',
            'status' => 'shipped',
            'payment_status' => 'pending',
        ]);
        $this->assertStringContainsString('UNPAID - CASH ON DELIVERY', $render($cash));

        // 'verified' is the paid value on orders.payment_status.
        $paid = $this->makeOrder(['status' => 'paid', 'payment_status' => 'verified']);
        $this->assertStringContainsString('PAYMENT CONFIRMED', $render($paid));

        $cancelled = $this->makeOrder(['status' => 'cancelled', 'payment_status' => 'pending']);
        $this->assertStringContainsString('CANCELLED', $render($cancelled));

        $failed = $this->makeOrder(['payment_status' => 'failed']);
        $this->assertStringContainsString('PAYMENT FAILED', $render($failed));

        $refunded = $this->makeOrder(['payment_status' => 'refunded']);
        $this->assertStringContainsString('REFUNDED', $render($refunded));

        // A cash-on-delivery order must never claim to be paid, even if the
        // payment record was somehow set to verified.
        $codVerified = $this->makeOrder([
            'payment_method' => 'cash_on_delivery',
            'status' => 'shipped',
            'payment_status' => 'verified',
        ]);
        $this->assertStringContainsString('UNPAID - CASH ON DELIVERY', $render($codVerified));
    }

    public function test_printer_receipt_is_untouched()
    {
        // The 80mm thermal roll must keep working for the shop's own printer.
        $order = $this->makeOrder();

        $thermal = view('admin.orders.receipt', [
            'order' => $order,
            'barcodeString' => 'MDS-O1-1x2',
            'barcodeBase64' => 'x',
        ])->render();

        $this->assertStringContainsString('size: 80mm auto', $thermal);
        $this->assertStringContainsString('width: 70mm', $thermal);
        $this->assertStringContainsString('window.print()', $thermal);
    }

    public function test_receipt_mailable_attaches_the_online_confirmation()
    {
        $order = $this->makeOrder();

        $rendered = (new OrderReceipt($order))->render();

        $this->assertStringContainsString('Order Confirmation', $rendered);
        $this->assertStringNotContainsString('size: 80mm auto', $rendered);
    }

    public function test_customer_can_open_the_receipt_modal_from_their_order_page()
    {
        $order = $this->makeOrder();
        $customer = $order->user;

        $html = $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="receipt-' . $order->id . '"', $html);
        // The Blade tag itself is compiled away, so assert on the markup it
        // produces instead.
        $this->assertStringContainsString('rd-card', $html);
        $this->assertStringContainsString('openOrderReceipt(' . $order->id . ')', $html);
    }
}
