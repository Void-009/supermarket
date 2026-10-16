<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\PosSyncBatch;
use App\Models\PosSyncItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosSyncApiTest extends TestCase
{
    use RefreshDatabase;

    private function posUser(): User
    {
        return User::factory()->create(['role' => 'agent']);
    }

    private function makeItem(array $attributes = []): Item
    {
        $category = Category::firstOrCreate(
            ['name' => 'Test Category'],
            ['slug' => 'test-category', 'is_active' => true, 'sort_order' => 0]
        );

        return Item::create(array_merge([
            'pos_item_id' => '30877',
            'parent_item_number' => null,
            'category_id' => $category->id,
            'name' => 'Milo 500g',
            'slug' => 'milo-500g',
            'cost_price' => 6500,
            'selling_price' => 7500,
            'quantity' => 10,
            'is_visible_online' => true,
            'pos_inactive' => false,
            'stock_status' => 'in_stock',
        ], $attributes));
    }

    private function createPayload(string $syncId, array $items): array
    {
        return [
            'sync_id' => $syncId,
            'source' => 'pos-agent',
            'items' => $items,
        ];
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/pos/sync', $this->createPayload(fake()->uuid(), []))
            ->assertStatus(401);
    }

    public function test_token_requires_pos_sync_ability(): void
    {
        Sanctum::actingAs($this->posUser(), []);

        $this->postJson('/api/pos/sync', $this->createPayload(fake()->uuid(), []))
            ->assertStatus(403);
    }

    public function test_accepts_and_creates_new_product(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'create',
                'pos_item_id' => '45021',
                'name' => 'Golden Penny Spaghetti 500g',
                'category' => 'Pasta',
                'selling_price' => 1200,
                'cost_price' => 1050,
                'quantity' => 35,
                'barcodes' => ['item_number|123456789'],
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        $batch = PosSyncBatch::where('sync_id', $payload['sync_id'])->first();
        $this->assertNotNull($batch);
        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->processed_items);
        $this->assertSame(0, $batch->failed_items);

        $item = Item::where('pos_item_id', '45021')->first();
        $this->assertNotNull($item);
        $this->assertSame('Golden Penny Spaghetti 500g', $item->name);
        $this->assertSame('1200.00', $item->selling_price);
        $this->assertSame('1050.00', $item->cost_price);
        $this->assertSame(35, $item->quantity);
        $this->assertTrue($item->is_visible_online);
        $this->assertSame('Pasta', $item->category->name);
        $this->assertDatabaseHas('item_barcodes', [
            'item_id' => $item->id,
            'barcode_value' => '123456789',
        ]);
    }

    public function test_update_changes_prices_and_preserves_online_only_fields(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $item = $this->makeItem([
            'description' => 'ADMIN DESCRIPTION',
            'image_url' => 'https://example.com/milo.jpg',
            'is_admin_favorite' => true,
        ]);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'update',
                'pos_item_id' => '30877',
                'selling_price' => 7800,
                'quantity' => 17,
                'last_modified' => '2026-08-12 07:31:04',
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)
            ->assertStatus(202);

        $item->refresh();
        $this->assertSame('7800.00', $item->selling_price);
        $this->assertSame('6500.00', $item->cost_price);
        $this->assertSame(17, $item->quantity);
        $this->assertSame('ADMIN DESCRIPTION', $item->description);
        $this->assertSame('https://example.com/milo.jpg', $item->image_url);
        $this->assertTrue($item->is_admin_favorite);
        $this->assertNotNull($item->last_synced_at);
    }

    public function test_update_for_unknown_item_becomes_create(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'update',
                'pos_item_id' => '99999',
                'name' => 'New Product via Upsert',
                'category' => 'Snacks',
                'selling_price' => 500,
                'quantity' => 3,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $this->assertDatabaseHas('items', ['pos_item_id' => '99999', 'name' => 'New Product via Upsert']);
    }

    public function test_deactivate_hides_item_without_deleting(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $item = $this->makeItem();

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'deactivate',
                'pos_item_id' => '30877',
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $this->assertDatabaseHas('items', ['pos_item_id' => '30877']);
        $this->assertDatabaseHas('items', ['pos_item_id' => '30877', 'is_visible_online' => 0, 'pos_inactive' => 1]);
    }

    public function test_sync_does_not_reactivate_manually_deactivated_category(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $item = $this->makeItem();
        $item->category->update(['is_active' => false]);

        // The POS still reports this item as active. The sync must apply the
        // price/quantity change but leave the category deactivated.
        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'update',
                'pos_item_id' => '30877',
                'selling_price' => 8000,
                'quantity' => 12,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $item->refresh();
        $this->assertSame('8000.00', $item->selling_price);
        $this->assertSame(12, $item->quantity);
        $this->assertTrue($item->is_visible_online);
        $this->assertDatabaseHas('categories', ['id' => $item->category_id, 'is_active' => 0]);
        $this->assertFalse($item->visibleInStore());
        $this->assertFalse($item->isPurchasableOnline());
    }

    public function test_sync_does_not_restore_deleted_category(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $item = $this->makeItem();
        $item->category->delete();
        $this->assertSoftDeleted('categories', ['id' => $item->category_id]);

        // A later sync re-sends an item for that same category name. The
        // category must NOT be resurrected; the item is re-attached to the
        // existing (hidden) category instead.
        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'update',
                'pos_item_id' => '30877',
                'selling_price' => 8200,
                'quantity' => 9,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $item->refresh();
        $this->assertSame('8200.00', $item->selling_price);
        $this->assertSame(9, $item->quantity);
        $this->assertSoftDeleted('categories', ['id' => $item->category_id]);
        $this->assertFalse($item->visibleInStore());
    }

    public function test_duplicate_sync_id_is_acknowledged_but_not_reprocessed(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $payload = $this->createPayload('11111111-1111-1111-1111-111111111111', [
            [
                'action' => 'create',
                'pos_item_id' => '45021',
                'name' => 'Golden Penny Spaghetti 500g',
                'selling_price' => 1200,
                'quantity' => 35,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $this->postJson('/api/pos/sync', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, PosSyncBatch::count());
        $this->assertSame(1, Item::where('pos_item_id', '45021')->count());
        $this->assertSame(1, PosSyncItem::count());
    }

    public function test_create_requires_name(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'create',
                'pos_item_id' => '45021',
                'selling_price' => 1200,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)
            ->assertStatus(422);
    }

    public function test_negative_price_is_rejected(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'create',
                'pos_item_id' => '45021',
                'name' => 'Bad Price',
                'selling_price' => -5,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)
            ->assertStatus(422);
    }

    public function test_stale_update_is_skipped(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $this->makeItem(['last_modified' => '2026-08-12 08:00:00']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'update',
                'pos_item_id' => '30877',
                'selling_price' => 9999,
                'last_modified' => '2026-08-12 07:00:00',
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $this->assertDatabaseHas('items', ['pos_item_id' => '30877', 'selling_price' => '7500.00']);
    }

    public function test_slug_is_deduplicated_for_same_name(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $this->makeItem(['name' => 'Dup Item', 'slug' => 'dup-item', 'pos_item_id' => '111']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'create',
                'pos_item_id' => '222',
                'name' => 'Dup Item',
                'selling_price' => 100,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        $item = Item::where('pos_item_id', '222')->first();
        $this->assertNotNull($item);
        $this->assertSame('dup-item-2', $item->slug);
    }

    public function test_rotate_token_requires_pos_sync_ability(): void
    {
        Sanctum::actingAs($this->posUser(), []);

        $this->postJson('/api/pos/sync/rotate-token')->assertStatus(403);
    }

    public function test_batch_status_reports_processed_batch(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $payload = $this->createPayload(fake()->uuid(), [
            [
                'action' => 'create',
                'pos_item_id' => '45021',
                'name' => 'Golden Penny Spaghetti 500g',
                'selling_price' => 1200,
                'quantity' => 35,
            ],
        ]);

        $this->postJson('/api/pos/sync', $payload)->assertStatus(202);

        // The agent polls this after sending so it only marks its local
        // changes as uploaded once the queue has actually run.
        $this->getJson('/api/pos/sync/batch/'.$payload['sync_id'])
            ->assertOk()
            ->assertJson([
                'status' => 'completed',
                'total_items' => 1,
                'processed_items' => 1,
                'failed_items' => 0,
            ]);
    }

    public function test_batch_status_returns_404_for_unknown_batch(): void
    {
        Sanctum::actingAs($this->posUser(), ['pos:sync']);

        $this->getJson('/api/pos/sync/batch/'.fake()->uuid())
            ->assertStatus(404)
            ->assertJson(['status' => 'not_found']);
    }

    public function test_batch_status_requires_pos_sync_ability(): void
    {
        Sanctum::actingAs($this->posUser(), []);

        $this->getJson('/api/pos/sync/batch/'.fake()->uuid())->assertStatus(403);
    }

    public function test_rotate_token_issues_fresh_token_and_revokes_old(): void
    {
        $user = $this->posUser();
        $old = $user->createToken('pos-sync', ['pos:sync']);

        $this->withToken($old->plainTextToken)
            ->postJson('/api/pos/sync/rotate-token')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'plain_text_token',
                'token_id',
                'expires_at',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $old->accessToken->id,
        ]);

        // The Sanctum guard caches the resolved user per app instance, which in
        // tests is reused across requests. Forget the cached guards so the old
        // token is re-validated against the DB on the next request.
        $this->app['auth']->forgetGuards();

        $this->withToken($old->plainTextToken)
            ->postJson('/api/pos/sync/rotate-token')
            ->assertStatus(401);
    }
}
