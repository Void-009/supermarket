<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemReviewTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent', 'is_active' => true]);
    }

    private function makeItem(array $attributes = []): Item
    {
        $category = Category::firstOrCreate(
            ['name' => 'Test Category'],
            ['slug' => 'test-category', 'is_active' => true, 'sort_order' => 0]
        );

        $merged = array_merge([
            'pos_item_id' => '30877',
            'parent_item_number' => null,
            'category_id' => $category->id,
            'name' => 'Milo 500g',
            'cost_price' => 6500,
            'selling_price' => 7500,
            'quantity' => 10,
            'is_visible_online' => true,
            'pos_inactive' => false,
            'stock_status' => 'in_stock',
            'last_synced_at' => now(),
        ], $attributes);

        $merged['slug'] = 'milo-500g-' . str_replace('@', '-', strtolower((string) $merged['pos_item_id']));

        return Item::create($merged);
    }

    public function test_variation_inherits_parent_image_when_it_has_no_own_image(): void
    {
        $parent = $this->makeItem([
            'pos_item_id' => 'PARENT-1',
            'image_url' => 'items/parent.jpg',
        ]);

        $variation = $this->makeItem([
            'pos_item_id' => 'CHILD-1@PARENT-1',
            'parent_item_number' => 'PARENT-1',
            'image_url' => null,
        ]);

        $this->assertEquals('items/parent.jpg', $variation->image_url);
        $this->assertNull($variation->own_image_url);

        $owned = $this->makeItem([
            'pos_item_id' => 'CHILD-2@PARENT-1',
            'parent_item_number' => 'PARENT-1',
            'image_url' => 'items/child.jpg',
        ]);

        $this->assertEquals('items/child.jpg', $owned->image_url);
        $this->assertEquals('items/child.jpg', $owned->own_image_url);

        $this->assertNull($parent->parent_item_number);
        $this->assertEquals('items/parent.jpg', $parent->image_url);
    }

    public function test_agent_without_modify_items_permission_is_denied(): void
    {
        $this->actingAs($this->agent());

        $item = $this->makeItem();

        $this->get('/admin/items/reviews')->assertStatus(403);
        $this->getJson('/admin/items/reviews/data')->assertStatus(403);
        $this->putJson("/admin/items/reviews/{$item->id}", ['selling_price' => 100])->assertStatus(403);
    }

    public function test_superadmin_can_open_reviews_page(): void
    {
        $this->actingAs($this->superadmin());

        $this->get('/admin/items/reviews')->assertStatus(200);
    }

    public function test_reviews_data_classifies_new_and_updated_items(): void
    {
        $this->actingAs($this->superadmin());

        $newItem = $this->makeItem(['pos_item_id' => 'NEW-1', 'last_synced_at' => now()]);

        $updatedItem = $this->makeItem(['pos_item_id' => 'UPD-1']);
        $updatedItem->created_at = now()->subDays(10);
        $updatedItem->save();

        $response = $this->getJson('/admin/items/reviews/data')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $byId = collect($response->json('items'))->keyBy('pos_item_id');

        $this->assertEquals('new', $byId['NEW-1']['change_type']);
        $this->assertEquals('updated', $byId['UPD-1']['change_type']);
        $this->assertEquals(2, $response->json('total'));

        // "new" filter only returns the freshly created item.
        $filtered = $this->getJson('/admin/items/reviews/data?type=new')
            ->assertStatus(200)
            ->json('items');

        $this->assertCount(1, $filtered);
        $this->assertEquals('NEW-1', $filtered[0]['pos_item_id']);
    }

    public function test_review_update_recomputes_stock_status(): void
    {
        $this->actingAs($this->superadmin());

        $item = $this->makeItem(['quantity' => 10, 'stock_status' => 'in_stock']);

        $this->putJson("/admin/items/reviews/{$item->id}", [
            'selling_price' => 9000,
            'cost_price' => 8000,
            'quantity' => 2,
            'is_visible_online' => 1,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $item->refresh();
        $this->assertEquals(9000, (float) $item->selling_price);
        $this->assertEquals(8000, (float) $item->cost_price);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals('low_stock', $item->stock_status);
    }

    public function test_review_update_links_variation_to_parent(): void
    {
        $this->actingAs($this->superadmin());

        $parent = $this->makeItem(['pos_item_id' => 'PARENT-9']);
        $child = $this->makeItem(['pos_item_id' => 'CHILD-9']);

        $this->putJson("/admin/items/reviews/{$child->id}", [
            'parent_item_number' => 'PARENT-9',
        ])->assertStatus(200);

        $this->assertEquals('PARENT-9', $child->fresh()->parent_item_number);

        // An item cannot be its own parent.
        $this->putJson("/admin/items/reviews/{$child->id}", [
            'parent_item_number' => 'CHILD-9',
        ])->assertStatus(422);

        // The parent must actually exist.
        $this->putJson("/admin/items/reviews/{$child->id}", [
            'parent_item_number' => 'DOES-NOT-EXIST',
        ])->assertStatus(422);

        // Clearing the parent keeps the item standalone.
        $this->putJson("/admin/items/reviews/{$child->id}", [
            'parent_item_number' => '',
        ])->assertStatus(200);

        $this->assertNull($child->fresh()->parent_item_number);
    }
}
