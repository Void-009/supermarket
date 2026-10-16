<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\PosItemAlias;
use App\Models\Setting;
use App\Services\PosSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariationAliasTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private PosSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->service = new PosSyncService;
    }

    private function makeItem(array $attributes = []): Item
    {
        $attributes = array_merge([
            'pos_item_id' => '17368',
            'parent_item_number' => null,
            'category_id' => $this->category->id,
            'name' => 'Gilaso Rice',
            'slug' => 'gilaso-rice-'.fake()->unique()->numberBetween(1, 99999),
            'cost_price' => 37750,
            'selling_price' => 75500,
            'quantity' => 20,
            'is_visible_online' => true,
            'pos_inactive' => false,
            'stock_status' => 'in_stock',
        ], $attributes);

        // Mirror the migration's backfill: the row is till-owned exactly when
        // it carries a sync timestamp. Tests keep expressing intent through
        // last_synced_at, the field they were already setting.
        if (! array_key_exists('owned_by_pos', $attributes)) {
            $attributes['owned_by_pos'] = isset($attributes['last_synced_at']);
        }

        return Item::create($attributes);
    }

    /**
     * Stand-in for a realistically sized till catalog.
     *
     * reconcileCatalog() refuses to hide anything unless the export covers
     * COVERAGE_MIN of every id the till ever confirmed, so a test that
     * expects a hide has to model an export that is missing a small slice of
     * a large catalog - not half of a four item one.
     *
     * @return array<int, string>
     */
    private function seedTillCatalog(int $count = 40): array
    {
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $id = 'seed'.$i;
            $this->makeItem([
                'pos_item_id' => $id,
                'name' => 'Seed Product '.$i,
                'last_synced_at' => now()->subHour(),
            ]);
            $ids[] = $id;
        }

        return $ids;
    }

    public function test_create_of_variation_with_matching_tombstone_restores_instead_of_duplicating(): void
    {
        $this->makeItem(['pos_item_id' => '17368', 'quantity' => 50]);

        // The POS deleted the variation on runout: tombstoned, image kept.
        $tombstone = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'cost_price' => 75500,
            'selling_price' => 75500,
            'quantity' => 0,
            'is_visible_online' => false,
            'pos_inactive' => true,
            'pos_missing_since' => now()->subDay(),
            'image_url' => 'items/gilaso-bag.jpg',
            'description' => 'ADMIN DESCRIPTION',
        ]);

        // Restock: the POS mints a fresh id and often reprices.
        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '17368@1176',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'category' => 'Test Category',
            'cost_price' => 75500,
            'selling_price' => 76000,
            'inactive' => false,
            'last_modified' => '2026-10-05 10:00:00',
        ]);

        $this->assertSame('restored', $outcome['result']);
        $this->assertSame($tombstone->id, $outcome['item_id']);

        // No duplicate was created.
        $this->assertSame(2, Item::count());

        // The new id is permanently aliased to the original row.
        $this->assertDatabaseHas('pos_item_aliases', [
            'pos_item_id' => '17368@1176',
            'item_id' => $tombstone->id,
            'reason' => 'restored',
        ]);

        $revived = $tombstone->fresh();
        $this->assertNull($revived->pos_missing_since);
        $this->assertFalse($revived->pos_inactive);
        $this->assertTrue($revived->is_visible_online);
        // Fresh POS price applied; online-only fields preserved.
        $this->assertSame('76000.00', $revived->selling_price);
        $this->assertSame('items/gilaso-bag.jpg', $revived->image_url);
        $this->assertSame('ADMIN DESCRIPTION', $revived->description);
    }

    public function test_aliased_id_updates_the_canonical_row(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);
        $canonical = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'cost_price' => 75500,
        ]);
        PosItemAlias::create(['pos_item_id' => '17368@1176', 'item_id' => $canonical->id, 'reason' => 'restored']);

        $outcome = $this->service->process([
            'action' => 'update',
            'pos_item_id' => '17368@1176',
            'selling_price' => 77000,
            'inactive' => false,
        ]);

        $this->assertSame('updated', $outcome['result']);
        $this->assertSame('77000.00', $canonical->fresh()->selling_price);
    }

    public function test_aliased_id_deactivate_tombstones_the_canonical_row(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);
        $canonical = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
        ]);
        PosItemAlias::create(['pos_item_id' => '17368@1176', 'item_id' => $canonical->id, 'reason' => 'restored']);

        $outcome = $this->service->process([
            'action' => 'deactivate',
            'pos_item_id' => '17368@1176',
        ]);

        $this->assertSame('deactivated', $outcome['result']);

        $revived = $canonical->fresh();
        $this->assertTrue($revived->pos_inactive);
        $this->assertFalse($revived->is_visible_online);
        $this->assertNotNull($revived->pos_missing_since);
    }

    public function test_ambiguous_fingerprint_creates_normally_without_alias(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);

        foreach (['17368@1173', '17368@1175'] as $oldId) {
            $this->makeItem([
                'pos_item_id' => $oldId,
                'parent_item_number' => '17368',
                'name' => 'Gilaso Rice 50kg(Bag)',
                'cost_price' => 75500,
                'is_visible_online' => false,
                'pos_inactive' => true,
                'pos_missing_since' => now()->subDay(),
            ]);
        }

        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '17368@1176',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'category' => 'Test Category',
            'cost_price' => 75500,
            'selling_price' => 75500,
            'inactive' => false,
        ]);

        // Two matching tombstones = cannot be sure which one is the same
        // product, so nothing is aliased and a fresh row is created.
        $this->assertSame('created', $outcome['result']);
        $this->assertSame(4, Item::count());
        $this->assertDatabaseMissing('pos_item_aliases', ['pos_item_id' => '17368@1176']);
    }

    public function test_restored_row_takes_the_new_cost_when_a_restock_is_repriced(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);
        $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'cost_price' => 75500,
            'selling_price' => 77000,
            'is_visible_online' => false,
            'pos_inactive' => true,
            'pos_missing_since' => now()->subDay(),
        ]);

        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '17368@1176',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'category' => 'Test Category',
            'cost_price' => 80000, // restocked at a new cost
            'selling_price' => 82000,
            'inactive' => false,
        ]);

        $this->assertSame('restored', $outcome['result']);
        $this->assertSame(2, Item::count());
        $this->assertDatabaseHas('pos_item_aliases', [
            'pos_item_id' => '17368@1176',
            'item_id' => $outcome['item_id'],
        ]);
        $this->assertDatabaseHas('items', [
            'pos_item_id' => '17368@1173',
            'cost_price' => 80000,
            'selling_price' => 82000,
            'pos_missing_since' => null,
            'is_visible_online' => true,
        ]);
    }

    public function test_two_name_matches_with_different_costs_stay_ambiguous(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);
        foreach (['1173', '1174'] as $id) {
            $this->makeItem([
                'pos_item_id' => '17368@'.$id,
                'parent_item_number' => '17368',
                'name' => 'Gilaso Rice 50kg(Bag)',
                'cost_price' => $id === '1173' ? 75500 : 70000,
                'is_visible_online' => false,
                'pos_inactive' => true,
                'pos_missing_since' => now()->subDay(),
            ]);
        }

        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '17368@1176',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'category' => 'Test Category',
            'cost_price' => 99000,
            'selling_price' => 100000,
            'inactive' => false,
        ]);

        $this->assertSame('created', $outcome['result']);
        $this->assertSame(4, Item::count());
        $this->assertDatabaseMissing('pos_item_aliases', ['pos_item_id' => '17368@1176']);
    }

    public function test_different_name_never_restores_even_at_the_same_cost(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);
        $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'cost_price' => 75500,
            'is_visible_online' => false,
            'pos_inactive' => true,
            'pos_missing_since' => now()->subDay(),
        ]);

        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '17368@1176',
            'name' => 'Gilaso Rice 50kg(Carton)',
            'category' => 'Test Category',
            'cost_price' => 75500,
            'selling_price' => 77000,
            'inactive' => false,
        ]);

        $this->assertSame('created', $outcome['result']);
        $this->assertSame(3, Item::count());
        $this->assertDatabaseMissing('pos_item_aliases', ['pos_item_id' => '17368@1176']);
    }

    public function test_update_for_tombstoned_same_id_revives_it(): void
    {
        $this->makeItem(['pos_item_id' => '17368']);
        $tombstone = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'is_visible_online' => false,
            'pos_inactive' => true,
            'pos_missing_since' => now()->subDay(),
        ]);

        $outcome = $this->service->process([
            'action' => 'update',
            'pos_item_id' => '17368@1173',
            'inactive' => false,
        ]);

        $this->assertSame('updated', $outcome['result']);
        $this->assertNull($tombstone->fresh()->pos_missing_since);
        $this->assertTrue($tombstone->fresh()->is_visible_online);
    }

    public function test_sync_never_unhides_admin_hidden_item(): void
    {
        $item = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'is_visible_online' => false,
            'hidden_by_admin' => true,
        ]);

        $outcome = $this->service->process([
            'action' => 'update',
            'pos_item_id' => '17368@1173',
            'inactive' => false,
        ]);

        $this->assertFalse($item->fresh()->is_visible_online);
        $this->assertTrue($item->fresh()->hidden_by_admin);
    }

    public function test_deactivating_parent_cascades_to_variations(): void
    {
        $parent = $this->makeItem(['pos_item_id' => '17368']);
        $child = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
        ]);

        $outcome = $this->service->process([
            'action' => 'deactivate',
            'pos_item_id' => '17368',
        ]);

        $this->assertSame('deactivated', $outcome['result']);
        $this->assertNotNull($parent->fresh()->pos_missing_since);

        $child = $child->fresh();
        $this->assertTrue($child->pos_inactive);
        $this->assertFalse($child->is_visible_online);
        $this->assertNotNull($child->pos_missing_since);
    }

    public function test_reconcile_hides_missing_variations_whose_parent_is_listed(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '17368', 'last_synced_at' => now()->subHour()]);
        $stale = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'last_synced_at' => now()->subHour(),
        ]);
        $live = $this->makeItem([
            'pos_item_id' => '17368@1174',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Half Bag)',
            'cost_price' => 37750,
            'last_synced_at' => now()->subHour(),
        ]);
        $otherParentChild = $this->makeItem([
            'pos_item_id' => '9999@1',
            'parent_item_number' => '9999',
            'name' => 'Other Product',
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog(
            array_merge($catalog, ['17368', '17368@1174']),
            [],
            now()
        );

        $this->assertTrue($result['applied']);
        $this->assertSame(1, $result['tombstoned']);
        $this->assertSame(1, $result['missing_variations']);
        $this->assertNotNull($stale->fresh()->pos_missing_since);
        $this->assertTrue($stale->fresh()->pos_inactive);

        // Seen child untouched; child of an unseen parent untouched.
        $this->assertNull($live->fresh()->pos_missing_since);
        $this->assertNull($otherParentChild->fresh()->pos_missing_since);
    }

    public function test_reconcile_dry_run_changes_nothing(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '17368', 'last_synced_at' => now()->subHour()]);
        $stale = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog(
            array_merge($catalog, ['17368']),
            [],
            now(),
            dryRun: true
        );

        $this->assertSame(1, $result['tombstoned']);
        $this->assertNull($stale->fresh()->pos_missing_since);
    }

    public function test_reconcile_hides_hand_typed_variation_absent_from_the_till(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '17368', 'last_synced_at' => now()->subHour()]);

        // typed in by hand on the variations page: last_synced_at is NULL
        $handTyped = $this->makeItem([
            'pos_item_id' => '17368@4340',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(carton)',
            'last_synced_at' => null,
        ]);

        // the live replacement the till actually has today
        $live = $this->makeItem([
            'pos_item_id' => '17368@6572',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg( Carton )',
            'cost_price' => 75500,
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog(
            array_merge($catalog, ['17368', '17368@6572']),
            [],
            now()
        );

        $this->assertSame(1, $result['tombstoned']);
        $this->assertSame(['17368@4340'], $result['ids']);
        $this->assertNotNull($handTyped->fresh()->pos_missing_since);
        $this->assertFalse((bool) $handTyped->fresh()->is_visible_online);
        $this->assertNull($live->fresh()->pos_missing_since);
    }

    public function test_reconcile_keeps_hand_typed_variation_still_listed_in_the_till(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '17368', 'last_synced_at' => now()->subHour()]);
        $live = $this->makeItem([
            'pos_item_id' => '17368@1516',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(roll)',
            'last_synced_at' => null,
        ]);

        $result = $this->service->reconcileCatalog(
            array_merge($catalog, ['17368', '17368@1516']),
            [],
            now()
        );

        $this->assertSame(0, $result['tombstoned']);
        $this->assertNull($live->fresh()->pos_missing_since);
    }

    public function test_reconcile_hides_products_the_till_deleted(): void
    {
        $catalog = $this->seedTillCatalog();
        $gone = $this->makeItem([
            'pos_item_id' => '4500',
            'name' => 'Retired Product',
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog($catalog, [], now());

        $this->assertSame(1, $result['missing_products']);
        $this->assertSame(1, $result['tombstoned']);
        $this->assertNotNull($gone->fresh()->pos_missing_since);
        $this->assertFalse((bool) $gone->fresh()->is_visible_online);
        $this->assertSame('out_of_stock', $gone->fresh()->stock_status);
    }

    public function test_reconcile_never_hides_products_typed_in_on_the_website(): void
    {
        $catalog = $this->seedTillCatalog();

        // Online-only product: no sync timestamp, so the till never owned it.
        $siteOwned = $this->makeItem([
            'pos_item_id' => '9001',
            'name' => 'Online Only Gift Hamper',
            'last_synced_at' => null,
        ]);

        $result = $this->service->reconcileCatalog($catalog, [], now());

        $this->assertSame(0, $result['missing_products']);
        $this->assertSame(0, $result['tombstoned']);
        $this->assertNull($siteOwned->fresh()->pos_missing_since);
        $this->assertTrue((bool) $siteOwned->fresh()->is_visible_online);
    }

    public function test_reconcile_hides_items_the_till_flags_inactive(): void
    {
        $catalog = $this->seedTillCatalog();
        $paused = $this->makeItem([
            'pos_item_id' => '4501',
            'name' => 'Paused In The Till',
            'last_synced_at' => now()->subHour(),
        ]);
        $pausedVariation = $this->makeItem([
            'pos_item_id' => '4501@900',
            'parent_item_number' => '4501',
            'name' => 'Paused In The Till (Carton)',
            'last_synced_at' => now()->subHour(),
        ]);
        $running = $this->makeItem([
            'pos_item_id' => '4502',
            'name' => 'Still For Sale',
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog(
            array_merge($catalog, ['4501', '4501@900', '4502']),
            ['4501', '4501@900'],
            now()
        );

        $this->assertSame(2, $result['inactive_hidden']);
        $this->assertNotNull($paused->fresh()->pos_missing_since);
        $this->assertNotNull($pausedVariation->fresh()->pos_missing_since);
        $this->assertNull($running->fresh()->pos_missing_since);
        $this->assertTrue((bool) $running->fresh()->is_visible_online);
    }

    public function test_reconcile_hides_variations_of_a_product_the_till_deleted(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '4503', 'last_synced_at' => now()->subHour()]);
        $child = $this->makeItem([
            'pos_item_id' => '4503@901',
            'parent_item_number' => '4503',
            'name' => 'Gone Product (Carton)',
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog($catalog, [], now());

        $this->assertSame(1, $result['missing_products']);
        $this->assertSame(1, $result['missing_variations']);
        $this->assertNotNull($child->fresh()->pos_missing_since);
    }

    public function test_reconcile_refuses_to_act_on_a_partial_export(): void
    {
        $catalog = $this->seedTillCatalog(40);
        $this->makeItem(['pos_item_id' => '4504', 'last_synced_at' => now()->subHour()]);
        $stale = $this->makeItem([
            'pos_item_id' => '4504@902',
            'parent_item_number' => '4504',
            'name' => 'Paused Product (Carton)',
            'last_synced_at' => now()->subHour(),
        ]);

        // Only a fifth of the till arrived. Defence in depth: the first runs
        // are stopped by the coverage gate, and once the small size has been
        // "confirmed" the ceiling still refuses it. Nothing is ever hidden.
        $reasons = [];
        foreach (range(1, 6) as $ignored) {
            $result = $this->service->reconcileCatalog(array_slice($catalog, 0, 8), [], now());

            $this->assertTrue($result['skipped']);
            $this->assertFalse($result['applied']);
            $this->assertSame(0, $result['tombstoned']);

            $reasons[] = (string) $result['reason'];
        }

        $this->assertStringContainsString('partial', implode(' | ', $reasons));
        $this->assertStringContainsString('more than the', implode(' | ', $reasons));
        $this->assertNull($stale->fresh()->pos_missing_since);
    }

    public function test_reconcile_never_hides_more_than_the_per_run_ceiling(): void
    {
        $this->seedTillCatalog(60);
        $survivor = Item::where('pos_item_id', 'seed1')->firstOrFail();

        // Reference size low enough that the coverage gate is satisfied, so
        // this run is stopped by the CEILING instead: the export is a lie and
        // it wants to take out 59 of the 60 items the till has confirmed.
        Setting::set('pos_reconcile_ref_size', 1, 'sync');

        foreach (range(1, 6) as $ignored) {
            $result = $this->service->reconcileCatalog(['seed1'], [], now());

            $this->assertTrue($result['skipped']);
            $this->assertStringContainsString('more than the', (string) $result['reason']);
        }

        $this->assertSame(60, Item::count());
        $this->assertNull($survivor->fresh()->pos_missing_since);
        $this->assertTrue((bool) $survivor->fresh()->is_visible_online);
    }

    public function test_reconcile_allows_a_bulk_cleanup_that_stays_under_the_ceiling(): void
    {
        $catalog = $this->seedTillCatalog(100);
        $gone = $this->makeItem([
            'pos_item_id' => '4506',
            'name' => 'Bulk Deleted Product',
            'last_synced_at' => now()->subHour(),
        ]);

        // 5% of the catalog is a real cleanup, not a broken export.
        $result = $this->service->reconcileCatalog($catalog, [], now());

        $this->assertTrue($result['applied']);
        $this->assertSame(1, $result['missing_products']);
        $this->assertNotNull($gone->fresh()->pos_missing_since);
    }

    public function test_reconcile_eventually_trusts_a_genuinely_smaller_catalog(): void
    {
        $catalog = $this->seedTillCatalog(40);
        $this->makeItem(['pos_item_id' => '17368', 'last_synced_at' => now()->subHour()]);
        $stale = $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'last_synced_at' => now()->subHour(),
        ]);

        // 6 of 42 ids gone: a real cleanup, but too big a drop to trust on
        // the strength of a single export.
        $smaller = array_merge(array_slice($catalog, 0, 35), ['17368']);

        $this->assertTrue($this->service->reconcileCatalog($smaller, [], now())['skipped']);
        $this->assertNull($stale->fresh()->pos_missing_since);

        $this->assertTrue($this->service->reconcileCatalog($smaller, [], now())['skipped']);
        $this->assertNull($stale->fresh()->pos_missing_since);

        // Third identical export: the smaller catalog is the new normal.
        $result = $this->service->reconcileCatalog($smaller, [], now());

        $this->assertTrue($result['applied']);
        $this->assertNotNull($stale->fresh()->pos_missing_since);
    }

    public function test_reconcile_is_idempotent(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '17368', 'last_synced_at' => now()->subHour()]);
        $this->makeItem([
            'pos_item_id' => '17368@1173',
            'parent_item_number' => '17368',
            'name' => 'Gilaso Rice 50kg(Bag)',
            'last_synced_at' => now()->subHour(),
        ]);

        $first = $this->service->reconcileCatalog(array_merge($catalog, ['17368']), [], now());
        $second = $this->service->reconcileCatalog(array_merge($catalog, ['17368']), [], now());

        $this->assertSame(1, $first['tombstoned']);
        $this->assertSame(0, $second['tombstoned']);
    }

    public function test_item_hidden_as_inactive_comes_back_when_the_till_reactivates_it(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '4505', 'last_synced_at' => now()->subHour()]);
        $paused = $this->makeItem([
            'pos_item_id' => '4505@903',
            'parent_item_number' => '4505',
            'name' => 'Back On Sale (Carton)',
            'last_synced_at' => now()->subHour(),
        ]);

        $this->service->reconcileCatalog(
            array_merge($catalog, ['4505', '4505@903']),
            ['4505@903'],
            now()
        );
        $this->assertFalse((bool) $paused->fresh()->is_visible_online);

        $this->service->process([
            'action' => 'update',
            'pos_item_id' => '4505@903',
            'name' => 'Back On Sale (Carton)',
            'cost_price' => 900,
            'selling_price' => 1000,
            'inactive' => false,
            'quantity' => 5,
        ]);

        $this->assertTrue((bool) $paused->fresh()->is_visible_online);
        $this->assertNull($paused->fresh()->pos_missing_since);
    }

    public function test_reconcile_reads_the_ownership_marker_not_the_sync_timestamp(): void
    {
        $catalog = $this->seedTillCatalog();

        // Website-owned despite carrying a timestamp: must survive.
        $website = $this->makeItem([
            'pos_item_id' => '9100',
            'name' => 'Website Row With A Stale Timestamp',
            'last_synced_at' => now()->subHour(),
            'owned_by_pos' => false,
        ]);

        // Till-owned despite never having synced: must be hidden.
        $till = $this->makeItem([
            'pos_item_id' => '9101',
            'name' => 'Till Row That Was Never Synced',
            'last_synced_at' => null,
            'owned_by_pos' => true,
        ]);

        $result = $this->service->reconcileCatalog($catalog, [], now());

        $this->assertSame(1, $result['missing_products']);
        $this->assertNull($website->fresh()->pos_missing_since);
        $this->assertNotNull($till->fresh()->pos_missing_since);
    }

    public function test_reconcile_does_not_fake_a_sync_timestamp(): void
    {
        $catalog = $this->seedTillCatalog();

        // Parent is in the export, the hand-typed child is not: hidden.
        $this->makeItem(['pos_item_id' => '9200', 'name' => 'Website Made Parent', 'last_synced_at' => null]);
        $child = $this->makeItem([
            'pos_item_id' => '9200@1',
            'parent_item_number' => '9200',
            'name' => 'Website Made Carton',
            'last_synced_at' => null,
        ]);

        // A till row that stops being listed: hidden, timestamp untouched.
        $gone = $this->makeItem([
            'pos_item_id' => '9300',
            'name' => 'Till Deleted Product',
            'last_synced_at' => now()->subHour(),
        ]);

        $result = $this->service->reconcileCatalog(
            array_merge($catalog, ['9200']),
            [],
            now()
        );

        $this->assertSame(2, $result['tombstoned']);
        $this->assertNotNull($child->fresh()->pos_missing_since);
        $this->assertNotNull($gone->fresh()->pos_missing_since);

        // Hiding is not syncing: never confirmed rows keep NULL, and rows the
        // till did confirm keep their real timestamp instead of the reconcile
        // run's clock. That is what keeps owned_by_pos mirror last_synced_at.
        $this->assertNull($child->fresh()->last_synced_at);
        $this->assertEqualsWithDelta(
            now()->subHour()->timestamp,
            $gone->fresh()->last_synced_at->timestamp,
            5
        );

        $mismatch = Item::where('owned_by_pos', true)->whereNull('last_synced_at')->count()
            + Item::where('owned_by_pos', false)->whereNotNull('last_synced_at')->count();
        $this->assertSame(0, $mismatch);
    }

    public function test_sync_create_marks_the_row_till_owned(): void
    {
        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '7777',
            'name' => 'Fresh From The Till',
            'category' => 'Test Category',
            'cost_price' => 100,
            'selling_price' => 200,
            'inactive' => false,
            'last_modified' => '2026-10-06 10:00:00',
        ]);

        $this->assertSame('created', $outcome['result']);
        $this->assertTrue((bool) Item::where('pos_item_id', '7777')->first()->owned_by_pos);

        // A row created in the admin keeps the column default, so reconcile
        // will never treat it as the till's to delete.
        $typed = Item::create([
            'pos_item_id' => '7778',
            'name' => 'Typed On The Website',
            'slug' => 'typed-on-the-website',
            'category_id' => $this->category->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'quantity' => 0,
            'is_visible_online' => true,
            'stock_status' => 'in_stock',
        ]);

        $this->assertFalse((bool) $typed->owned_by_pos);
    }

    public function test_first_sync_write_makes_a_website_row_till_owned(): void
    {
        $site = $this->makeItem([
            'pos_item_id' => '9001',
            'name' => 'Online Only Gift Hamper',
            'last_synced_at' => null,
        ]);
        $this->assertFalse((bool) $site->owned_by_pos);

        $outcome = $this->service->process([
            'action' => 'update',
            'pos_item_id' => '9001',
            'name' => 'Online Only Gift Hamper',
            'cost_price' => 37750,
            'selling_price' => 76000,
            'inactive' => false,
            'last_modified' => '2026-10-06 10:00:00',
        ]);

        $this->assertSame('updated', $outcome['result']);
        $this->assertTrue((bool) $site->fresh()->owned_by_pos);
    }

    public function test_new_id_for_the_same_pack_hides_the_older_live_sibling(): void
    {
        $this->makeItem(['pos_item_id' => '7897', 'name' => 'Oba Scrool Macroni 500g', 'last_synced_at' => now()->subHour()]);
        $older = $this->makeItem([
            'pos_item_id' => '7897@4340',
            'parent_item_number' => '7897',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'last_synced_at' => now()->subDay(),
        ]);

        // Both ids are in the export, so reconcile hides neither: only the
        // retire pass can stop the customer seeing two cartons.
        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '7897@6572',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'category' => 'Test Category',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'inactive' => false,
            'last_modified' => '2026-10-06 10:00:00',
        ]);

        $this->assertSame('created', $outcome['result']);

        $retired = $older->fresh();
        $this->assertNotNull($retired->pos_missing_since);
        $this->assertFalse((bool) $retired->is_visible_online);
        $this->assertTrue((bool) Item::where('pos_item_id', '7897@6572')->first()->is_visible_online);

        $this->assertSame(1, Item::where('parent_item_number', '7897')
            ->where('is_visible_online', true)
            ->count());
    }

    public function test_new_id_does_not_hide_a_sibling_whose_name_differs(): void
    {
        $this->makeItem(['pos_item_id' => '7897', 'name' => 'Oba Scrool Macroni 500g', 'last_synced_at' => now()->subHour()]);
        $carton = $this->makeItem([
            'pos_item_id' => '7897@4340',
            'parent_item_number' => '7897',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'last_synced_at' => now()->subDay(),
        ]);

        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '7897@6572',
            'name' => 'Oba Scrool Macroni 500g(pcs)',
            'category' => 'Test Category',
            'cost_price' => 200,
            'selling_price' => 350,
            'inactive' => false,
            'last_modified' => '2026-10-06 10:00:00',
        ]);

        $this->assertSame('created', $outcome['result']);
        $this->assertNull($carton->fresh()->pos_missing_since);
        $this->assertTrue((bool) $carton->fresh()->is_visible_online);

        $this->assertSame(2, Item::where('parent_item_number', '7897')
            ->where('is_visible_online', true)
            ->count());
    }

    public function test_retire_leaves_a_row_the_admin_already_hid_alone(): void
    {
        $this->makeItem(['pos_item_id' => '7897', 'name' => 'Oba Scrool Macroni 500g', 'last_synced_at' => now()->subHour()]);
        $adminHidden = $this->makeItem([
            'pos_item_id' => '7897@4340',
            'parent_item_number' => '7897',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'is_visible_online' => false,
            'hidden_by_admin' => true,
        ]);

        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '7897@6572',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'category' => 'Test Category',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'inactive' => false,
            'last_modified' => '2026-10-06 10:00:00',
        ]);

        $this->assertSame('created', $outcome['result']);

        $fresh = $adminHidden->fresh();
        $this->assertNull($fresh->pos_missing_since);
        $this->assertTrue((bool) $fresh->hidden_by_admin);
    }

    public function test_retire_runs_even_when_the_update_changed_nothing(): void
    {
        $this->makeItem(['pos_item_id' => '7897', 'name' => 'Oba Scrool Macroni 500g', 'last_synced_at' => now()->subHour()]);
        $older = $this->makeItem([
            'pos_item_id' => '7897@4340',
            'parent_item_number' => '7897',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'owned_by_pos' => true,
        ]);
        $newer = $this->makeItem([
            'pos_item_id' => '7897@6572',
            'parent_item_number' => '7897',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'owned_by_pos' => true,
        ]);

        // The till repeats an update that changes nothing, so a duplicate
        // pair that predates this code still gets cleaned up.
        $outcome = $this->service->process([
            'action' => 'update',
            'pos_item_id' => '7897@6572',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'category' => 'Test Category',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'inactive' => false,
        ]);

        $this->assertSame('skipped', $outcome['result']);
        $this->assertNotNull($older->fresh()->pos_missing_since);
        $this->assertTrue((bool) $newer->fresh()->is_visible_online);
    }

    public function test_hiding_before_the_create_keeps_one_row_and_records_the_alias(): void
    {
        $catalog = $this->seedTillCatalog();
        $this->makeItem(['pos_item_id' => '7897', 'name' => 'Oba Scrool Macroni 500g', 'last_synced_at' => now()->subHour()]);
        $stale = $this->makeItem([
            'pos_item_id' => '7897@4340',
            'parent_item_number' => '7897',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'cost_price' => 22400,
            'selling_price' => 22550,
            'last_synced_at' => null,
            'image_url' => 'items/macaroni.jpg',
            'description' => 'KEPT DESCRIPTION',
        ]);

        // The agent reconciles first: the export lists the parent, not @4340.
        $reconciled = $this->service->reconcileCatalog(
            array_merge($catalog, ['7897']),
            [],
            now()
        );

        $this->assertSame(1, $reconciled['tombstoned']);
        $this->assertNotNull($stale->fresh()->pos_missing_since);

        // The replacement id lands in the same cycle and finds the tombstone.
        $outcome = $this->service->process([
            'action' => 'create',
            'pos_item_id' => '7897@6572',
            'name' => 'Oba Scrool Macroni 500g(carton)',
            'category' => 'Test Category',
            'cost_price' => 23000,
            'selling_price' => 23500,
            'inactive' => false,
            'last_modified' => '2026-10-06 10:00:00',
        ]);

        $this->assertSame('restored', $outcome['result']);
        $this->assertSame($stale->id, $outcome['item_id']);
        $this->assertDatabaseHas('pos_item_aliases', [
            'pos_item_id' => '7897@6572',
            'item_id' => $stale->id,
            'reason' => 'restored',
        ]);

        // One variation row, not two - and the original URL, image and copy.
        $this->assertSame(1, Item::where('parent_item_number', '7897')->count());
        $revived = $stale->fresh();
        $this->assertTrue((bool) $revived->is_visible_online);
        $this->assertSame('items/macaroni.jpg', $revived->image_url);
        $this->assertSame('KEPT DESCRIPTION', $revived->description);
        $this->assertSame('23000.00', $revived->cost_price);
        $this->assertSame('23500.00', $revived->selling_price);
    }
}
