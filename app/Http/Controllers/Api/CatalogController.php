<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\PresentsItems;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\DeliveryLocation;
use App\Models\DeliverySetting;
use App\Models\Item;
use App\Models\PaymentSettings;
use App\Models\Setting;
use App\Models\SlideshowImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use PresentsItems;

    public function home(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->where('show_on_home', true)
            ->with('images')
            ->withCount(['items' => fn ($q) => $q->whereNull('parent_item_number')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->title_name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image_url' => $c->displayImage(),
                'items_count' => $c->items_count,
                'sort_order' => $c->sort_order,
                'children' => $c->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->title_name,
                    'slug' => $child->slug,
                ])->values(),
            ]);

        $deals = Item::visible()
            ->parents()
            ->with(['category', 'variations.parentItem'])
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->limit(12)
            ->get();

        if ($deals->count() === 0 || $deals->max('order_items_count') === 0) {
            $deals = Item::visible()
                ->parents()
                ->with(['category', 'variations.parentItem'])
                ->withCount('orderItems')
                ->inRandomOrder()
                ->limit(12)
                ->get();
        }

        $recentItems = Item::visible()
            ->parents()
            ->with(['category', 'variations.parentItem'])
            ->withCount('orderItems')
            ->latest()
            ->limit(12)
            ->get();

        $slideshow = SlideshowImage::getActive()
            ->map(fn ($s) => [
                'id' => $s->id,
                'image_url' => $this->fullUrl($s->path),
                'sort_order' => $s->sort_order,
            ]);

        $settings = Setting::pluck('value', 'key')->toArray();

        return response()->json([
            'success' => true,
            'slideshow' => $slideshow,
            'categories' => $categories,
            'deals' => $deals->map(fn ($i) => $this->presentItemCard($i))->values(),
            'recent_items' => $recentItems->map(fn ($i) => $this->presentItemCard($i))->values(),
            'settings' => $settings,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->where('show_on_home', true)
            ->with('images', 'children')
            ->withCount(['items' => fn ($q) => $q->whereNull('parent_item_number')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->title_name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image_url' => $c->displayImage(),
                'items_count' => $c->items_count,
                'children' => $c->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->title_name,
                    'slug' => $child->slug,
                ])->values(),
            ]);

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    public function items(Request $request): JsonResponse
    {
        $query = Item::visible()
            ->parents()
            ->with(['category', 'images', 'variations.parentItem'])
            ->withCount('orderItems');

        if ($request->filled('category')) {
            $category = Category::where('slug', $request->input('category'))->first();
            if ($category) {
                $childIds = $category->children()->pluck('id');
                $query->whereIn('category_id', $childIds->push($category->id));
            }
        }

        if ($request->filled('search')) {
            $query->searchable(trim($request->input('search')));
        }

        if ($request->filled('sort')) {
            match ($request->input('sort')) {
                'price_asc' => $query->orderBy('selling_price', 'asc'),
                'price_desc' => $query->orderBy('selling_price', 'desc'),
                'name_asc' => $query->orderBy('name', 'asc'),
                'discount' => $query->discounted(),
                default => $query->latest(),
            };
        } else {
            $query->latest();
        }

        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);
        $items = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'items' => collect($items->items())->map(fn ($i) => $this->presentItemCard($i))->values(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(Item $item): JsonResponse
    {
        if (! $item->is_visible_online) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $item->load([
            'category',
            'images',
            'barcodes',
            'variations.parentItem',
        ]);

        $variations = Item::dedupeVariations(
            $item->variations->filter(fn ($v) => $v->visibleInStore())
        )
            ->map(fn ($v) => [
                'id' => $v->id,
                'pos_item_id' => $v->pos_item_id,
                'name' => $v->name,
                'display_name' => $v->display_name,
                'selling_price' => (float) $v->selling_price,
                'discount_price' => $v->discount_price,
                'effective_price' => $v->effectivePrice(),
                'is_discounted' => $v->isDiscounted(),
                'image_url' => $this->fullUrl($v->image_url),
                'is_purchasable_online' => $v->isPurchasableOnline(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'item' => array_merge($this->presentItem($item), [
                'variations' => $variations,
                'barcodes' => $item->barcodes->pluck('barcode_value')->values(),
            ]),
        ]);
    }

    public function deliveryLocations(): JsonResponse
    {
        $locations = DeliveryLocation::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'cost', 'motorcycle_rate', 'truck_rate', 'description']);

        return response()->json([
            'success' => true,
            'delivery_locations' => $locations,
        ]);
    }

    public function deliverySettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'settings' => [
                'show_fee_selector' => DeliverySetting::showDeliveryFeeSelector(),
                'contact_message' => DeliverySetting::getContactAgentMessage(),
                'contact_phones' => DeliverySetting::getContactAgentPhones(),
                'contact_whatsapps' => DeliverySetting::getContactAgentWhatsapps(),
                'bulk_quantity_threshold' => DeliverySetting::getBulkQuantityThreshold(),
            ],
        ]);
    }

    public function paymentOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'payment_settings' => PaymentSettings::getActive(),
            'bank_accounts' => BankAccount::getActive(),
        ]);
    }
}
