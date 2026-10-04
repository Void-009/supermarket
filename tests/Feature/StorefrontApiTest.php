<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\DeliveryLocation;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StorefrontApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function makeItem(): Item
    {
        $category = Category::create([
            'name' => 'Drinks',
            'is_active' => true,
        ]);

        return Item::create([
            'pos_item_id' => (string) mt_rand(100000, 999999),
            'name' => 'Cola 50cl',
            'category_id' => $category->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => 10,
            'is_visible_online' => true,
        ]);
    }

    private function makeCustomer(): User
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);
        $user->markEmailAsVerified();

        return $user;
    }

    public function test_home_endpoint_returns_catalog(): void
    {
        $this->makeItem();

        $response = $this->getJson('/api/v1/home');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['slideshow', 'categories', 'deals', 'recent_items']);
    }

    public function test_register_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['email' => 'new@test.com']);
    }

    public function test_login_returns_token(): void
    {
        $user = $this->makeCustomer();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@test.com',
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $this->makeCustomer();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@test.com',
            'password' => 'WrongPass1',
        ])->assertStatus(401);
    }

    public function test_cart_flow(): void
    {
        $user = $this->makeCustomer();
        $item = $this->makeItem();

        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->postJson('/api/v1/cart', ['item_id' => $item->id, 'quantity' => 2], $headers)
            ->assertOk()
            ->assertJsonPath('cart_count', 2);

        $this->getJson('/api/v1/cart', $headers)
            ->assertOk()
            ->assertJsonPath('items.0.item_id', $item->id)
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('subtotal', 400);

        $this->patchJson("/api/v1/cart/{$user->cartItems->first()->id}", ['quantity' => 3], $headers)
            ->assertOk()
            ->assertJsonPath('cart_count', 3);
    }

    public function test_checkout_creates_order_and_clears_cart(): void
    {
        $user = $this->makeCustomer();
        $item = $this->makeItem();

        $user->cartItems()->create(['item_id' => $item->id, 'quantity' => 2]);

        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $response = $this->postJson('/api/v1/checkout', [
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $orderId = $response->json('order_id');
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $orderId, 'item_id' => $item->id]);
        $this->assertDatabaseHas('payments', ['order_id' => $orderId]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cart_add_enforces_stock(): void
    {
        $user = $this->makeCustomer();
        $item = $this->makeItem();
        $item->update(['quantity' => 2]);

        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->postJson('/api/v1/cart', ['item_id' => $item->id, 'quantity' => 5], $headers)
            ->assertStatus(422);

        $this->postJson('/api/v1/cart', ['item_id' => $item->id, 'quantity' => 2], $headers)
            ->assertOk();
    }

    public function test_orders_list_and_detail(): void
    {
        $user = $this->makeCustomer();
        $item = $this->makeItem();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
            'status' => 'new',
            'total_amount' => 400,
            'payment_status' => 'verified',
            'payment_reference' => 'ORD-ABC123',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'price_at_order' => 200,
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->getJson('/api/v1/orders', $headers)
            ->assertOk()
            ->assertJsonPath('orders.0.id', $order->id)
            ->assertJsonPath('orders.0.status', 'new');

        $this->getJson("/api/v1/orders/{$order->id}", $headers)
            ->assertOk()
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.total_amount', 400)
            ->assertJsonPath('order.status_steps.0', 'new');
    }

    public function test_customer_cannot_view_others_order(): void
    {
        $owner = $this->makeCustomer();
        $other = User::create([
            'name' => 'Other',
            'email' => 'other@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'user_id' => $owner->id,
            'customer_name' => 'Owner',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
            'status' => 'new',
            'total_amount' => 100,
            'payment_status' => 'verified',
            'payment_reference' => 'ORD-ABC123',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        $token = $other->createToken('test')->plainTextToken;

        $this->getJson("/api/v1/orders/{$order->id}", ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(403);
    }

    public function test_complaint_flow(): void
    {
        $user = $this->makeCustomer();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
            'status' => 'delivered',
            'total_amount' => 100,
            'payment_status' => 'verified',
            'payment_reference' => 'ORD-ABC123',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->postJson("/api/v1/orders/{$order->id}/complaints", [
            'subject' => 'Late delivery',
            'message' => 'The order arrived two days late.',
        ], $headers)->assertCreated()->assertJsonPath('complaint.status', 'open');

        $this->getJson("/api/v1/orders/{$order->id}/complaints", $headers)
            ->assertOk()
            ->assertJsonPath('complaints.0.subject', 'Late delivery');
    }

    public function test_chat_send_and_messages(): void
    {
        $user = $this->makeCustomer();
        $token = $user->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->postJson('/api/v1/chat/send', ['message' => 'Hello there'], $headers)
            ->assertCreated()
            ->assertJsonPath('message.message', 'Hello there');

        $this->getJson('/api/v1/chat/messages', $headers)
            ->assertOk()
            ->assertJsonPath('messages.0.message', 'Hello there');
    }

    public function test_customer_token_cannot_access_admin(): void
    {
        $user = $this->makeCustomer();
        $token = $user->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/admin/dashboard', ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(403);
    }

    public function test_items_in_inactive_category_are_hidden_even_when_individually_visible(): void
    {
        $item = $this->makeItem();
        $item->category->update(['is_active' => false]);
        $item->refresh();

        $this->assertTrue($item->is_visible_online);
        $this->assertFalse($item->visibleInStore());
        $this->assertFalse($item->isPurchasableOnline());

        $home = $this->getJson('/api/v1/home')->assertOk();
        $deals = collect($home->json('deals'));
        $recent = collect($home->json('recent_items'));
        $this->assertFalse($deals->contains('id', $item->id));
        $this->assertFalse($recent->contains('id', $item->id));
        $this->assertFalse(collect($home->json('categories'))->contains('id', $item->category_id));

        $list = $this->getJson('/api/v1/items')->assertOk();
        $this->assertEmpty(collect($list->json('items'))->where('id', $item->id));

        $this->getJson("/api/v1/items/{$item->slug}")
            ->assertStatus(404);
    }

    public function test_reactivating_category_restores_items(): void
    {
        $item = $this->makeItem();
        $item->category->update(['is_active' => false]);
        $item->category->update(['is_active' => true]);
        $item->refresh();

        $this->assertTrue($item->visibleInStore());

        $home = $this->getJson('/api/v1/home')->assertOk();
        $recent = collect($home->json('recent_items'));
        $this->assertTrue($recent->contains('id', $item->id));
    }

    public function test_inactive_parent_category_cascades_to_child_categories(): void
    {
        $parent = Category::create(['name' => 'Grocery', 'is_active' => true]);
        $child = Category::create(['name' => 'Cereal', 'parent_id' => $parent->id, 'is_active' => true]);

        $item = Item::create([
            'pos_item_id' => (string) mt_rand(100000, 999999),
            'name' => 'Oats 1kg',
            'category_id' => $child->id,
            'cost_price' => 300,
            'selling_price' => 500,
            'quantity' => 5,
            'is_visible_online' => true,
        ]);

        $this->assertTrue($item->visibleInStore());

        $parent->update(['is_active' => false]);
        $item->refresh();

        $this->assertFalse($item->visibleInStore());
        $this->assertFalse($item->isPurchasableOnline());

        $list = $this->getJson('/api/v1/items')->assertOk();
        $this->assertEmpty(collect($list->json('items'))->where('id', $item->id));
    }

    public function test_item_in_inactive_category_cannot_be_added_to_cart(): void
    {
        $user = $this->makeCustomer();
        $item = $this->makeItem();
        $item->category->update(['is_active' => false]);

        $token = $user->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/cart', ['item_id' => $item->id, 'quantity' => 1], ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(404);
    }

    public function test_category_with_show_on_home_false_is_hidden_from_home_but_items_remain(): void
    {
        $category = Category::create([
            'name' => 'Hidden Drinks',
            'is_active' => true,
            'show_on_home' => false,
        ]);

        $item = Item::create([
            'pos_item_id' => (string) mt_rand(100000, 999999),
            'name' => 'Cola 50cl',
            'category_id' => $category->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => 10,
            'is_visible_online' => true,
        ]);

        $this->assertTrue($item->visibleInStore());

        $home = $this->getJson('/api/v1/home')->assertOk();
        $this->assertFalse(collect($home->json('categories'))->contains('id', $category->id));

        $categories = $this->getJson('/api/v1/categories')->assertOk();
        $this->assertFalse(collect($categories->json('categories'))->contains('id', $category->id));

        $list = $this->getJson('/api/v1/items')->assertOk();
        $this->assertTrue(collect($list->json('items'))->contains('id', $item->id));

        $web = $this->get('/')->assertOk();
        $web->assertDontSee('alt="Hidden Drinks"');
    }

    public function test_category_with_show_on_home_true_appears_on_home(): void
    {
        $item = $this->makeItem();

        $home = $this->getJson('/api/v1/home')->assertOk();
        $this->assertTrue(collect($home->json('categories'))->contains('id', $item->category_id));
    }
}
