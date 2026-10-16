<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Services\ItemVariationMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VariationMergeTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private ItemVariationMergeService $merger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->merger = new ItemVariationMergeService;
    }

    private function makeItem(array $attributes = []): Item
    {
        return Item::create(array_merge([
            'pos_item_id' => '17368',
            'parent_item_number' => null,
            'category_id' => $this->category->id,
            'name' => 'Gilaso Rice 50kg(Bag)',
            'slug' => 'gilaso-'.fake()->unique()->numberBetween(1, 99999),
            'cost_price' => 75500,
            'selling_price' => 75500,
            'quantity' => 10,
            'is_visible_online' => true,
            'pos_inactive' => false,
            'stock_status' => 'in_stock',
        ], $attributes));
    }

    private function makePair(): array
    {
        $survivor = $this->makeItem(['pos_item_id' => '17368@1176', 'parent_item_number' => '17368']);
        $loser = $this->makeItem(['pos_item_id' => '17368@1174', 'parent_item_number' => '17368']);

        return [$survivor, $loser];
    }

    public function test_barcodes_move_and_duplicates_are_dropped(): void
    {
        [$survivor, $loser] = $this->makePair();

        DB::table('item_barcodes')->insert([
            ['item_id' => $survivor->id, 'barcode_type' => 'item_number', 'barcode_value' => '111'],
            ['item_id' => $loser->id, 'barcode_type' => 'item_number', 'barcode_value' => '111'],
            ['item_id' => $loser->id, 'barcode_type' => 'item_number', 'barcode_value' => '222'],
        ]);

        $this->merger->merge($survivor, $loser);

        $this->assertSame(
            ['111', '222'],
            DB::table('item_barcodes')->where('item_id', $survivor->id)->orderBy('barcode_value')->pluck('barcode_value')->all()
        );
        $this->assertSame(0, DB::table('item_barcodes')->where('item_id', $loser->id)->count());
    }

    public function test_images_order_items_and_translations_move(): void
    {
        [$survivor, $loser] = $this->makePair();

        DB::table('item_images')->insert([
            ['item_id' => $loser->id, 'path' => 'items/loser.jpg', 'sort_order' => 0, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('item_translations')->insert([
            ['item_id' => $survivor->id, 'locale' => 'ha', 'name' => 'SURVIVOR HA', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $loser->id, 'locale' => 'ha', 'name' => 'LOSER HA', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['item_id' => $loser->id, 'locale' => 'yo', 'name' => 'LOSER YO', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => null,
            'customer_name' => 'Test Customer',
            'customer_phone' => '08000000000',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'verified',
            'status' => 'delivered',
            'total_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            ['order_id' => $orderId, 'item_id' => $loser->id, 'quantity' => 2, 'price_at_order' => 75500, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->merger->merge($survivor, $loser);

        $this->assertSame(1, DB::table('item_images')->where('item_id', $survivor->id)->count());
        $this->assertSame(1, DB::table('order_items')->where('item_id', $survivor->id)->count());
        $this->assertSame(0, DB::table('order_items')->where('item_id', $loser->id)->count());

        // The survivor's own Hausa wording wins; the loser's colliding
        // locale is dropped and its unique locale moves across.
        $translations = DB::table('item_translations')->where('item_id', $survivor->id)->orderBy('locale')->get();
        $this->assertSame(['ha', 'yo'], $translations->pluck('locale')->all());
        $this->assertSame('SURVIVOR HA', $translations->firstWhere('locale', 'ha')->name);
    }

    public function test_favorites_and_campaigns_dedupe_and_cart_lines_sum(): void
    {
        [$survivor, $loser] = $this->makePair();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        DB::table('favorites')->insert([
            ['user_id' => $user->id, 'item_id' => $survivor->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'item_id' => $loser->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $otherUser->id, 'item_id' => $loser->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $campaignId = DB::table('discount_campaigns')->insertGetId([
            'name' => 'Promo',
            'discount_percentage' => 10,
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('discount_campaign_items')->insert([
            ['discount_campaign_id' => $campaignId, 'item_id' => $survivor->id, 'created_at' => now(), 'updated_at' => now()],
            ['discount_campaign_id' => $campaignId, 'item_id' => $loser->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('cart_items')->insert([
            ['user_id' => $user->id, 'session_id' => null, 'item_id' => $survivor->id, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'session_id' => null, 'item_id' => $loser->id, 'quantity' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->merger->merge($survivor, $loser);

        // User favourited both: one favourite survives. Other user's moves.
        $this->assertSame(2, DB::table('favorites')->where('item_id', $survivor->id)->count());
        $this->assertSame(0, DB::table('favorites')->where('item_id', $loser->id)->count());

        $this->assertSame(1, DB::table('discount_campaign_items')->where('item_id', $survivor->id)->count());

        // Both cart lines folded into one summed line.
        $cart = DB::table('cart_items')->where('item_id', $survivor->id)->get();
        $this->assertCount(1, $cart);
        $this->assertSame(3, $cart->first()->quantity);
    }

    public function test_online_only_fields_fill_only_gaps_and_loser_is_hidden_not_deleted(): void
    {
        [$survivor, $loser] = $this->makePair();
        $loser->update(['description' => 'LOSER DESC', 'is_admin_favorite' => true]);

        DB::table('order_items')->insert([
            ['order_id' => DB::table('orders')->insertGetId([
                'user_id' => null,
                'customer_name' => 'Test Customer',
                'customer_phone' => '08000000000',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'verified',
                'status' => 'delivered',
                'total_amount' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]), 'item_id' => $loser->id, 'quantity' => 1, 'price_at_order' => 75500, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->merger->merge($survivor, $loser);

        $survivor = $survivor->fresh();
        $this->assertSame('LOSER DESC', $survivor->description);
        $this->assertTrue($survivor->is_admin_favorite);

        $loser = $loser->fresh();
        $this->assertNotNull($loser); // never deleted: order_items RESTRICT FK
        $this->assertFalse($loser->is_visible_online);
        $this->assertTrue($loser->hidden_by_admin);
    }

    public function test_merging_item_into_itself_is_rejected(): void
    {
        [$survivor] = $this->makePair();

        $this->expectException(\InvalidArgumentException::class);

        $this->merger->merge($survivor, $survivor);
    }
}
