<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\DeliveryLocation;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function makeAdmin(array $permissions = []): User
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'permissions' => $permissions,
        ]);
        $admin->markEmailAsVerified();

        return $admin;
    }

    private function makeSuperadmin(): User
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);
        $admin->markEmailAsVerified();

        return $admin;
    }

    private function adminHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    private function makeItem(int $stock = 10): Item
    {
        $category = Category::create(['name' => 'Drinks', 'is_active' => true]);

        return Item::create([
            'pos_item_id' => (string) mt_rand(100000, 999999),
            'name' => 'Cola 50cl',
            'category_id' => $category->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => $stock,
            'is_visible_online' => true,
        ]);
    }

    public function test_admin_login_returns_token_and_role(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonStructure(['token']);
    }

    public function test_dashboard_requires_admin(): void
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/admin/dashboard', $this->adminHeaders($customer))
            ->assertStatus(403);
    }

    public function test_dashboard_returns_stats(): void
    {
        $admin = $this->makeSuperadmin();

        $response = $this->getJson('/api/v1/admin/dashboard', $this->adminHeaders($admin));

        $response->assertOk()
            ->assertJsonStructure([
                'stats' => [
                    'total_items', 'total_orders', 'total_revenue', 'pending_orders',
                ],
            ]);
    }

    public function test_admin_item_crud(): void
    {
        $admin = $this->makeSuperadmin();
        $headers = $this->adminHeaders($admin);
        $category = Category::create(['name' => 'Snacks', 'is_active' => true]);

        $store = $this->postJson('/api/v1/admin/items', [
            'pos_item_id' => '777001',
            'name' => 'Chips 100g',
            'category_id' => $category->id,
            'cost_price' => 50,
            'selling_price' => 120,
            'quantity' => 20,
        ], $headers);

        $store->assertCreated()->assertJsonPath('item.name', 'Chips 100g');
        $itemId = $store->json('item.id');

        $this->getJson("/api/v1/admin/items/{$itemId}", $headers)
            ->assertOk()
            ->assertJsonPath('item.name', 'Chips 100g');

        $this->putJson("/api/v1/admin/items/{$itemId}", ['selling_price' => 150], $headers)
            ->assertOk()
            ->assertJsonPath('item.selling_price', 150);

        $this->deleteJson("/api/v1/admin/items/{$itemId}", [], $headers)
            ->assertOk();

        $this->assertDatabaseMissing('items', ['id' => $itemId]);
    }

    public function test_admin_item_partial_update_preserves_barcodes(): void
    {
        $admin = $this->makeSuperadmin();
        $headers = $this->adminHeaders($admin);
        $category = Category::create(['name' => 'Snacks', 'is_active' => true]);

        $store = $this->postJson('/api/v1/admin/items', [
            'pos_item_id' => '777002',
            'name' => 'Biscuit 200g',
            'category_id' => $category->id,
            'cost_price' => 50,
            'selling_price' => 120,
            'quantity' => 20,
            'barcodes_text' => '111111, 222222',
        ], $headers);

        $store->assertCreated();
        $itemId = $store->json('item.id');

        $this->assertDatabaseHas('item_barcodes', ['item_id' => $itemId, 'barcode_value' => '111111']);
        $this->assertDatabaseHas('item_barcodes', ['item_id' => $itemId, 'barcode_value' => '222222']);

        // A partial update that does not mention barcodes must NOT wipe them.
        $this->putJson("/api/v1/admin/items/{$itemId}", ['selling_price' => 150], $headers)
            ->assertOk();

        $this->assertDatabaseHas('item_barcodes', ['item_id' => $itemId, 'barcode_value' => '111111']);
        $this->assertDatabaseHas('item_barcodes', ['item_id' => $itemId, 'barcode_value' => '222222']);
        $this->assertDatabaseHas('items', ['id' => $itemId, 'selling_price' => '150.00']);

        $this->getJson("/api/v1/admin/items/{$itemId}", $headers)
            ->assertOk()
            ->assertJsonPath('item.barcodes', ['111111', '222222']);

        // An explicit barcodes update still replaces the set.
        $this->putJson("/api/v1/admin/items/{$itemId}", ['barcodes_text' => '333333'], $headers)
            ->assertOk();

        $this->assertDatabaseMissing('item_barcodes', ['item_id' => $itemId, 'barcode_value' => '111111']);
        $this->assertDatabaseHas('item_barcodes', ['item_id' => $itemId, 'barcode_value' => '333333']);
    }

    public function test_admin_categories_index(): void
    {
        $admin = $this->makeSuperadmin();
        Category::create(['name' => 'Drinks', 'is_active' => true]);
        Category::create(['name' => 'Snacks', 'is_active' => true]);

        $this->getJson('/api/v1/admin/categories', $this->adminHeaders($admin))
            ->assertOk()
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_admin_category_show_on_home_persists(): void
    {
        $admin = $this->makeSuperadmin();
        $headers = $this->adminHeaders($admin);

        $store = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Party Snacks',
            'is_active' => true,
            'show_on_home' => false,
        ], $headers);

        $store->assertCreated()
            ->assertJsonPath('category.show_on_home', false);

        $categoryId = $store->json('category.id');
        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'show_on_home' => 0]);

        $this->putJson("/api/v1/admin/categories/{$categoryId}", ['show_on_home' => true], $headers)
            ->assertOk()
            ->assertJsonPath('category.show_on_home', true);

        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'show_on_home' => 1]);

        // A partial update that does not mention show_on_home must preserve it.
        $this->putJson("/api/v1/admin/categories/{$categoryId}", ['description' => 'Updated'], $headers)
            ->assertOk()
            ->assertJsonPath('category.show_on_home', true);

        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'show_on_home' => 1]);
    }

    public function test_admin_users_list_requires_permission(): void
    {
        $restricted = $this->makeAdmin([]);

        $this->getJson('/api/v1/admin/users', $this->adminHeaders($restricted))
            ->assertStatus(403);
    }

    public function test_superadmin_can_list_users(): void
    {
        $super = $this->makeSuperadmin();

        $this->getJson('/api/v1/admin/users', $this->adminHeaders($super))
            ->assertOk()
            ->assertJsonStructure(['users', 'pagination']);
    }

    public function test_admin_order_status_flow(): void
    {
        $admin = $this->makeSuperadmin();
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
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

        $headers = $this->adminHeaders($admin);

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'processing'], $headers)
            ->assertOk()
            ->assertJsonPath('order.status', 'processing');

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'paid'], $headers)
            ->assertOk()
            ->assertJsonPath('order.status', 'paid');

        $this->getJson("/api/v1/admin/orders/{$order->id}", $headers)
            ->assertOk()
            ->assertJsonPath('order.status', 'paid');
    }

    public function test_admin_can_view_and_restore_cancelled_order(): void
    {
        $admin = $this->makeSuperadmin();
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
            'status' => 'cancelled',
            'total_amount' => 400,
            'payment_status' => 'pending',
            'payment_reference' => 'ORD-CANCELLED',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        $headers = $this->adminHeaders($admin);

        // Admin can still view a cancelled order and is offered a reverse path.
        $this->getJson("/api/v1/admin/orders/{$order->id}", $headers)
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled')
            ->assertJsonPath('prev_status', 'new');

        // Admin can un-cancel (restore) it back to new.
        $this->postJson("/api/v1/admin/orders/{$order->id}/reverse", ['status' => 'new'], $headers)
            ->assertOk()
            ->assertJsonPath('order.status', 'new');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'new']);
    }

    public function test_restore_cancelled_order_requires_permission(): void
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $makeCancelledOrder = function () use ($customer) {
            return Order::create([
                'user_id' => $customer->id,
                'customer_name' => 'Customer',
                'customer_phone' => '08012345678',
                'delivery_method' => 'pickup',
                'payment_method' => 'cash_on_delivery',
                'status' => 'cancelled',
                'total_amount' => 400,
                'payment_status' => 'pending',
                'payment_reference' => 'ORD-PERM',
                'delivery_fee' => 0,
                'delivery_fee_status' => 'standard',
            ]);
        };

        // Admin without the permission cannot restore.
        $withoutPermission = $this->makeAdmin([]);
        $order = $makeCancelledOrder();

        $this->postJson("/api/v1/admin/orders/{$order->id}/reverse", ['status' => 'new'], $this->adminHeaders($withoutPermission))
            ->assertStatus(403);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);

        // Admin granted the permission can restore.
        $withPermission = User::create([
            'name' => 'Granted Admin',
            'email' => 'granted@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'permissions' => ['restore_cancelled_orders' => true],
        ]);
        $withPermission->markEmailAsVerified();

        $order = $makeCancelledOrder();

        $this->app['auth']->forgetGuards();
        $this->postJson("/api/v1/admin/orders/{$order->id}/reverse", ['status' => 'new'], $this->adminHeaders($withPermission))
            ->assertOk()
            ->assertJsonPath('order.status', 'new');

        // Superadmin always has the permission, even without it stored explicitly.
        $super = $this->makeSuperadmin();
        $order = $makeCancelledOrder();

        $this->app['auth']->forgetGuards();
        $this->postJson("/api/v1/admin/orders/{$order->id}/reverse", ['status' => 'new'], $this->adminHeaders($super))
            ->assertOk()
            ->assertJsonPath('order.status', 'new');
    }

    public function test_cancelled_order_appears_in_admin_index(): void
    {
        $admin = $this->makeSuperadmin();
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
            'status' => 'cancelled',
            'total_amount' => 400,
            'payment_status' => 'pending',
            'payment_reference' => 'ORD-CANCELLED-LIST',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        $this->getJson('/api/v1/admin/orders?status=cancelled', $this->adminHeaders($admin))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('orders.0.status', 'cancelled');
    }

    public function test_admin_orders_index_scopes_for_agent(): void
    {
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'cash_on_delivery',
            'status' => 'new',
            'total_amount' => 100,
            'payment_status' => 'verified',
            'payment_reference' => 'ORD-1',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'delivery',
            'payment_method' => 'cash_on_delivery',
            'status' => 'delivered',
            'total_amount' => 200,
            'payment_status' => 'verified',
            'payment_reference' => 'ORD-2',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
            'processed_by' => $agent->id,
        ]);

        $this->getJson('/api/v1/admin/orders', $this->adminHeaders($agent))
            ->assertOk()
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_admin_revenue_endpoint(): void
    {
        $admin = $this->makeSuperadmin();
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'bank_transfer',
            'status' => 'delivered',
            'total_amount' => 5000,
            'payment_status' => 'verified',
            'payment_reference' => 'ORD-1',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Customer',
            'customer_phone' => '08012345678',
            'delivery_method' => 'pickup',
            'payment_method' => 'bank_transfer',
            'status' => 'new',
            'total_amount' => 3000,
            'payment_status' => 'pending',
            'payment_reference' => 'ORD-2',
            'delivery_fee' => 0,
            'delivery_fee_status' => 'standard',
        ]);

        $this->getJson('/api/v1/admin/revenue?range=all_time', $this->adminHeaders($admin))
            ->assertOk()
            ->assertJsonStructure(['total_revenue', 'total_orders', 'breakdown']);
    }

    public function test_admin_settings_endpoint(): void
    {
        $admin = $this->makeSuperadmin();

        $this->getJson('/api/v1/admin/settings', $this->adminHeaders($admin))
            ->assertOk()
            ->assertJsonStructure(['delivery', 'payment']);
    }

    public function test_admin_delivery_location_crud(): void
    {
        $admin = $this->makeSuperadmin();
        $headers = $this->adminHeaders($admin);

        $store = $this->postJson('/api/v1/admin/delivery-locations', [
            'name' => 'Downtown',
            'cost' => 500,
            'sort_order' => 1,
        ], $headers);

        $store->assertCreated()->assertJsonPath('delivery_location.name', 'Downtown');
        $id = $store->json('delivery_location.id');

        $this->putJson("/api/v1/admin/delivery-locations/{$id}", ['cost' => 600], $headers)
            ->assertOk()
            ->assertJsonPath('delivery_location.cost', '600.00');

        $this->deleteJson("/api/v1/admin/delivery-locations/{$id}", [], $headers)
            ->assertOk();
    }

    public function test_admin_bank_account_crud(): void
    {
        $admin = $this->makeSuperadmin();
        $headers = $this->adminHeaders($admin);

        $store = $this->postJson('/api/v1/admin/bank-accounts', [
            'bank_name' => 'Access Bank',
            'account_name' => 'M-DELIGHT LTD',
            'account_number' => '0123456789',
        ], $headers);

        $store->assertCreated()->assertJsonPath('bank_account.bank_name', 'Access Bank');
        $id = $store->json('bank_account.id');

        $this->deleteJson("/api/v1/admin/bank-accounts/{$id}", [], $headers)
            ->assertOk();
    }

    public function test_agent_without_permission_denied(): void
    {
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'agent',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/admin/users', $this->adminHeaders($agent))
            ->assertStatus(403);
    }
}
