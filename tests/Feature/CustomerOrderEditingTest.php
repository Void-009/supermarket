<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DeliveryLocation;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\CustomerUpdatedOrder;
use App\Notifications\NewOrderReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerOrderEditingTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(array $overrides = []): Item
    {
        // Reused within a test, recreated across tests: RefreshDatabase rolls
        // back between them and categories.slug is unique.
        $category = Category::firstOrCreate(
            ['slug' => 'drinks'],
            ['name' => 'Drinks', 'is_active' => true]
        );

        return Item::create(array_merge([
            'pos_item_id' => (string) mt_rand(100000, 999999),
            'name' => 'Cola 50cl',
            'category_id' => $category->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => 50,
            'is_visible_online' => true,
        ], $overrides));
    }

    private function makeCustomer(): User
    {
        return User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);
    }

    private function makeOrder(User $user, array $attributes = [], array $lines = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'bank_transfer',
            'status' => 'new',
            'payment_status' => 'pending',
            'total_amount' => 0,
            'payment_reference' => 'ORD-TEST1234',
        ], $attributes));

        foreach ($lines as $itemId => $quantity) {
            OrderItem::create([
                'order_id' => $order->id,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'price_at_order' => Item::find($itemId)->effectivePrice(),
            ]);
        }

        $subtotal = $order->items()->get()->sum(fn ($l) => $l->price_at_order * $l->quantity);
        $order->update(['total_amount' => $subtotal + (float) $order->delivery_fee]);

        return $order->fresh();
    }

    public function test_new_bank_transfer_order_is_editable()
    {
        $this->assertTrue(
            $this->makeOrder($this->makeCustomer())->isEditableByCustomer()
        );
    }

    public function test_cash_on_delivery_stays_editable_until_admin_marks_paid()
    {
        // Checkout marks COD verified up front; the order must still be editable.
        $order = $this->makeOrder($this->makeCustomer(), [
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'verified',
        ]);

        $this->assertTrue($order->isEditableByCustomer());
    }

    public function test_verified_bank_transfer_locks_the_order()
    {
        $order = $this->makeOrder($this->makeCustomer(), ['payment_status' => 'verified']);

        $this->assertFalse($order->isEditableByCustomer());
        $this->assertNotNull($order->customerEditLockReason());
    }

    public function test_paid_shipped_and_delivered_lock_the_order()
    {
        $customer = $this->makeCustomer();

        foreach (['paid', 'shipped', 'delivered'] as $status) {
            $order = $this->makeOrder($customer, [
                'status' => $status,
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'verified',
            ]);

            $this->assertFalse($order->isEditableByCustomer(), "status {$status} should lock");
        }
    }

    public function test_cancelled_order_locks()
    {
        $order = $this->makeOrder($this->makeCustomer(), ['status' => 'cancelled']);

        $this->assertFalse($order->isEditableByCustomer());
    }

    public function test_customer_cannot_edit_someone_elses_order()
    {
        $owner = $this->makeCustomer();
        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $order = $this->makeOrder($owner);

        $this->actingAs($intruder)
            ->get(route('customer.orders.edit', $order))
            ->assertForbidden();
    }

    public function test_update_changes_quantities_and_reprices_from_live_data()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200, 'name' => 'Cola']);
        $milo = $this->makeItem(['selling_price' => 500, 'name' => 'Milo']);

        $order = $this->makeOrder($customer, [], [$cola->id => 2]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [
                ['item_id' => $cola->id, 'quantity' => 3],
                ['item_id' => $milo->id, 'quantity' => 1],
            ],
        ])->assertRedirect(route('customer.orders.show', $order));

        $order->refresh();

        $this->assertCount(2, $order->items);
        $this->assertEquals(3, $order->items->firstWhere('item_id', $cola->id)->quantity);
        // 3 x 200 + 1 x 500
        $this->assertEquals(1100.00, (float) $order->total_amount);
    }

    public function test_update_removes_lines_that_were_dropped()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200, 'name' => 'Cola']);
        $milo = $this->makeItem(['selling_price' => 500, 'name' => 'Milo']);

        $order = $this->makeOrder($customer, [], [$cola->id => 1, $milo->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertCount(1, $order->refresh()->items);
    }
    public function test_total_cannot_be_forced_by_the_request()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            // A tampered form trying to set its own price.
            'price_at_order' => 1,
            'total_amount' => 1,
            'items' => [['item_id' => $cola->id, 'quantity' => 2]],
        ])->assertRedirect();

        $order->refresh();

        $this->assertEquals(400.00, (float) $order->total_amount);
        $this->assertEquals(200.00, (float) $order->items->first()->price_at_order);
    }

    public function test_quantity_beyond_available_stock_is_rejected()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200, 'quantity' => 2]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 99]],
        ])->assertRedirect();

        $this->assertSame('1', (string) $order->refresh()->items->first()->quantity);
    }

    public function test_out_of_stock_item_is_rejected()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200, 'quantity' => 0]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertCount(1, $order->refresh()->items);
    }

    public function test_order_must_keep_at_least_one_item()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [],
        ]);

        $this->assertCount(1, $order->refresh()->items);
    }

    public function test_duplicate_item_rows_are_rejected()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [
                ['item_id' => $cola->id, 'quantity' => 1],
                ['item_id' => $cola->id, 'quantity' => 2],
            ],
        ]);

        $this->assertCount(1, $order->refresh()->items);
    }

    public function test_locked_order_rejects_a_direct_update_post()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $order = $this->makeOrder($customer, [
            'status' => 'shipped',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'verified',
        ], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Hacker',
            'customer_phone' => '08000000000',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 50]],
        ])->assertRedirect(route('customer.orders.show', $order));

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame(1, $order->items->first()->quantity);
        $this->assertNotSame('Hacker', $order->customer_name);
    }

    public function test_payment_record_follows_the_new_total()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'bank_transfer',
            'reference' => $order->payment_reference,
            'amount' => 200,
            'status' => 'pending',
        ]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 4]],
        ])->assertRedirect();

        $this->assertEquals(800.00, (float) $order->refresh()->payment->amount);
    }

    public function test_delivery_fee_is_recalculated_for_a_new_location()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $location = DeliveryLocation::create([
            'name' => 'Ikeja',
            'cost' => 350,
            'is_active' => true,
        ]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'delivery',
            'delivery_address' => '12 Allen Avenue, Ikeja',
            'delivery_location_id' => $location->id,
            'items' => [['item_id' => $cola->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order->refresh();

        $this->assertEquals(350.00, (float) $order->delivery_fee);
        $this->assertEquals(550.00, (float) $order->total_amount);
        $this->assertSame($location->id, $order->delivery_location_id);
    }

    public function test_edit_page_renders_for_an_editable_order()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $html = $this->actingAs($customer)
            ->get(route('customer.orders.edit', $order))
            ->assertOk()
            ->assertSee('Cola')
            ->getContent();

        // The form must land inside <body>. A missing @endsection still
        // renders the markup, but flushes it after </html> instead.
        $bodyEnd = strpos($html, '</body>');
        $this->assertLessThan($bodyEnd, strpos($html, 'Delivery details'));
        $this->assertLessThanOrEqual(
            2,
            strlen($html) - strrpos($html, '</html>') - 7,
            'markup was flushed after </html>'
        );
    }

    public function test_show_page_offers_edit_only_when_editable()
    {
        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $editable = $this->makeOrder($customer, [], [$cola->id => 1]);
        $locked = $this->makeOrder($customer, ['status' => 'delivered'], [$cola->id => 1]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $editable))
            ->assertOk()
            ->assertSee(route('customer.orders.edit', $editable));

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $locked))
            ->assertOk()
            ->assertDontSee(route('customer.orders.edit', $locked));
    }

    public function test_staff_are_notified_when_a_customer_changes_an_order()
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'permissions' => ['process_orders' => true],
        ]);

        // Push is the only channel these alerts use, so an unsubscribed account
        // is skipped by design (see via()). Subscribe to prove the dispatch.
        PushSubscription::create([
            'user_id' => $admin->id,
            'endpoint' => 'https://push.example.com/admin-sub',
            'auth_key' => 'auth',
            'p256dh_key' => 'key',
        ]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 5]],
        ])->assertRedirect();

        Notification::assertSentTo($admin, CustomerUpdatedOrder::class);
    }

    public function test_unsubscribed_staff_are_skipped_rather_than_sent_to()
    {
        Notification::fake();

        $customer = $this->makeCustomer();
        $cola = $this->makeItem(['selling_price' => 200]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'permissions' => ['process_orders' => true],
        ]);

        $order = $this->makeOrder($customer, [], [$cola->id => 1]);

        $this->actingAs($customer)->put(route('customer.orders.update', $order), [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'items' => [['item_id' => $cola->id, 'quantity' => 5]],
        ])->assertRedirect();

        // The order still updated; only the push is skipped.
        $this->assertEquals(1000.00, (float) $order->refresh()->total_amount);
        Notification::assertNotSentTo($admin, CustomerUpdatedOrder::class);
    }
}

class OrderAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaff(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Staff',
            'email' => 'staff@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'agent',
            'is_active' => true,
        ], $overrides));
    }

    public function test_recipients_include_staff_with_process_orders_permission()
    {
        $withPermission = $this->makeStaff([
            'email' => 'yes@test.com',
            'permissions' => ['process_orders' => true],
        ]);

        $this->assertTrue(User::orderAlertRecipients()->contains($withPermission));
    }

    public function test_recipients_exclude_staff_without_the_permission()
    {
        $without = $this->makeStaff([
            'email' => 'no@test.com',
            'permissions' => ['process_orders' => false],
        ]);

        $this->assertFalse(User::orderAlertRecipients()->contains($without));
    }

    public function test_superadmin_is_always_a_recipient()
    {
        $super = $this->makeStaff([
            'email' => 'super@test.com',
            'role' => 'superadmin',
            'permissions' => [],
        ]);

        $this->assertTrue(User::orderAlertRecipients()->contains($super));
    }

    public function test_inactive_staff_are_not_recipients()
    {
        $inactive = $this->makeStaff([
            'email' => 'inactive@test.com',
            'is_active' => false,
            'permissions' => ['process_orders' => true],
        ]);

        $this->assertFalse(User::orderAlertRecipients()->contains($inactive));
    }

    public function test_customers_are_never_recipients()
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'cust@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
            'permissions' => ['process_orders' => true],
        ]);

        // Even holding the flag, a customer must not receive admin alerts.
        $this->assertFalse(User::orderAlertRecipients()->contains($customer));
    }

    public function test_new_order_notification_builds_a_push_payload()
    {
        $order = Order::create([
            'user_id' => null,
            'customer_name' => 'Ada',
            'customer_phone' => '08012345678',
            'payment_method' => 'bank_transfer',
            'status' => 'new',
            'payment_status' => 'pending',
            'total_amount' => 5000,
            'payment_reference' => 'ORD-ABC',
        ]);

        $cola = Item::create([
            'pos_item_id' => '111222',
            'name' => 'Cola',
            'category_id' => Category::create(['name' => 'D', 'is_active' => true])->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => 5,
            'is_visible_online' => true,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'item_id' => $cola->id,
            'quantity' => 3,
            'price_at_order' => 200,
        ]);

        $payload = (new NewOrderReceived($order))->toPush($order);

        $this->assertStringContainsString('#'.$order->id, $payload['title']);
        $this->assertStringContainsString('Ada', $payload['body']);
        $this->assertStringContainsString('5,000.00', $payload['body']);
        $this->assertSame(route('admin.orders.show', $order->id), $payload['data']['url']);
    }

    public function test_notification_is_skipped_when_no_push_subscription_exists()
    {
        $staff = $this->makeStaff(['permissions' => ['process_orders' => true]]);

        $order = Order::create([
            'customer_name' => 'Ada',
            'customer_phone' => '08012345678',
            'status' => 'new',
            'payment_status' => 'pending',
            'total_amount' => 100,
            'payment_reference' => 'ORD-X',
        ]);

        $this->assertSame([], (new NewOrderReceived($order))->via($staff));

        PushSubscription::create([
            'user_id' => $staff->id,
            'endpoint' => 'https://push.example.com/abc',
            'auth_key' => 'auth',
            'p256dh_key' => 'key',
        ]);

        $this->assertSame([\App\Notifications\PushChannel::class], (new NewOrderReceived($order))->via($staff));
    }
}
