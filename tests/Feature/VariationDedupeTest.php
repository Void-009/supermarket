<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariationDedupeTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function makeItem(array $attributes = []): Item
    {
        return Item::create(array_merge([
            'pos_item_id' => '1000',
            'parent_item_number' => null,
            'category_id' => $this->category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.fake()->unique()->numberBetween(1, 99999),
            'cost_price' => 100,
            'selling_price' => 150,
            'quantity' => 10,
            'is_visible_online' => true,
            'pos_inactive' => false,
            'stock_status' => 'in_stock',
        ], $attributes));
    }

    public function test_parent_and_child_with_same_name_are_both_kept(): void
    {
        // A parent ("Sprite can 33cl" single can) and its child (the carton)
        // may share a name but are genuinely different products. The dedupe
        // only ever receives the siblings, so the parent must survive.
        $parent = $this->makeItem(['pos_item_id' => '1009', 'name' => 'Sprite can 33cl', 'selling_price' => 450]);
        $child = $this->makeItem([
            'pos_item_id' => '1009@4147',
            'parent_item_number' => '1009',
            'name' => 'Sprite can 33cl',
            'cost_price' => 10000,
            'selling_price' => 10200,
        ]);

        $siblings = Item::where('parent_item_number', '1009')->get();
        $deduped = Item::dedupeVariations($siblings);
        $deduped->prepend($parent);

        $this->assertCount(2, $deduped);
        $this->assertTrue($deduped->contains('id', $parent->id));
        $this->assertTrue($deduped->contains('id', $child->id));
    }

    public function test_genuinely_different_variations_all_survive(): void
    {
        $this->makeItem(['pos_item_id' => '12313', 'name' => 'Sonia Tomato Mix 70g', 'selling_price' => 142]);
        $this->makeItem(['pos_item_id' => '12313@2218', 'parent_item_number' => '12313', 'name' => 'Sonia Tomato Mix 70g (pcs(', 'cost_price' => 142]);
        $this->makeItem(['pos_item_id' => '12313@2221', 'parent_item_number' => '12313', 'name' => 'Sonia Tomato Mix 70g (roll)', 'cost_price' => 710]);
        $this->makeItem(['pos_item_id' => '12313@2223', 'parent_item_number' => '12313', 'name' => 'Sonia Tomato Mix 70g (carton)', 'cost_price' => 6950]);

        $deduped = Item::dedupeVariations(Item::where('parent_item_number', '12313')->get());

        $this->assertCount(3, $deduped);
    }

    public function test_same_name_different_cost_are_different_products(): void
    {
        // Two pack sizes sharing a name but with different costs must both
        // stay: hiding one would remove a real product.
        $this->makeItem(['pos_item_id' => '28240', 'name' => 'Bright Milk Powder']);
        $this->makeItem(['pos_item_id' => '28240@5586', 'parent_item_number' => '28240', 'name' => 'Bright Milk Powder Madara Awo*56', 'cost_price' => 5089.29]);
        $this->makeItem(['pos_item_id' => '28240@5587', 'parent_item_number' => '28240', 'name' => 'Bright Milk Powder Madara Awo*56', 'cost_price' => 10178.58]);

        $deduped = Item::dedupeVariations(Item::where('parent_item_number', '28240')->get());

        $this->assertCount(2, $deduped);
    }

    public function test_duplicate_collapses_to_newest_last_modified(): void
    {
        $this->makeItem(['pos_item_id' => '901', 'name' => 'Nescafe 3in1']);
        $older = $this->makeItem([
            'pos_item_id' => '901@6461',
            'parent_item_number' => '901',
            'name' => 'Nescafe 3in1 25g*48(carton)',
            'cost_price' => 27910,
            'last_modified' => '2026-09-01 20:28:06',
        ]);
        $newer = $this->makeItem([
            'pos_item_id' => '901@6462',
            'parent_item_number' => '901',
            'name' => 'Nescafe 3in1 25g*48(carton)',
            'cost_price' => 27910,
            'last_modified' => '2026-09-01 21:13:42',
        ]);

        $deduped = Item::dedupeVariations(Item::where('parent_item_number', '901')->get());

        $this->assertCount(1, $deduped);
        $this->assertSame($newer->id, $deduped->first()->id);
        $this->assertFalse($deduped->contains('id', $older->id));
    }

    public function test_duplicate_with_blank_last_modified_keeps_higher_id(): void
    {
        // The POS assigns ascending ids, so when both rows carry no
        // last_modified the higher id is the re-created one.
        $this->makeItem(['pos_item_id' => '25500', 'name' => 'Falari Colander']);
        $older = $this->makeItem([
            'pos_item_id' => '25500@6512',
            'parent_item_number' => '25500',
            'name' => 'Falari Colander {pack}',
            'cost_price' => 8333.33,
            'last_modified' => null,
        ]);
        $newer = $this->makeItem([
            'pos_item_id' => '25500@6513',
            'parent_item_number' => '25500',
            'name' => 'Falari Colander {pack}',
            'cost_price' => 8333.33,
            'last_modified' => null,
        ]);

        $deduped = Item::dedupeVariations(Item::where('parent_item_number', '25500')->get());

        $this->assertCount(1, $deduped);
        $this->assertSame($newer->id, $deduped->first()->id);
    }

    public function test_tombstoned_variations_are_dropped(): void
    {
        $this->makeItem(['pos_item_id' => '1070', 'name' => 'Sugar awo']);
        $this->makeItem(['pos_item_id' => '1070@4907', 'parent_item_number' => '1070', 'name' => 'Sugar awo(Kwano)', 'cost_price' => 4411.76]);
        $tombstoned = $this->makeItem([
            'pos_item_id' => '1070@4908',
            'parent_item_number' => '1070',
            'name' => 'Sugar awo(Kwano)',
            'cost_price' => 4411.76,
            'is_visible_online' => false,
            'pos_inactive' => true,
            'pos_missing_since' => now(),
        ]);

        $deduped = Item::dedupeVariations(Item::where('parent_item_number', '1070')->get());

        $this->assertCount(1, $deduped);
        $this->assertFalse($deduped->contains('id', $tombstoned->id));
    }
}
