<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\PresentsItems;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBarcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    use PresentsItems;

    private function authorizeItems(): void
    {
        abort_unless(auth()->user()->hasPermission('modify_items'), 403, 'You do not have permission to manage items.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeItems();

        $query = Item::with(['category', 'barcodes', 'parentItem'])->withCount('orderItems');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode_value', 'like', "%{$search}%"));
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($stockStatus = $request->input('stock_status')) {
            $query->where('stock_status', $stockStatus);
        }

        if ($visibility = $request->input('visibility')) {
            $query->where('is_visible_online', $visibility === 'visible');
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'price_asc' => $query->orderBy('selling_price', 'asc'),
            'price_desc' => $query->orderBy('selling_price', 'desc'),
            'stock_asc' => $query->orderBy('quantity', 'asc'),
            default => $query->latest(),
        };

        $items = $query->paginate(min(max((int) $request->input('per_page', 25), 1), 200));

        return response()->json([
            'success' => true,
            'items' => collect($items->items())->map(fn (Item $item) => $this->presentAdminItem($item)),
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
        $this->authorizeItems();

        $item->load(['category', 'barcodes', 'images', 'parentItem']);

        return response()->json([
            'success' => true,
            'item' => $this->presentAdminItem($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeItems();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_is_active' => 'boolean',
            'discount_starts_at' => 'nullable|date',
            'discount_ends_at' => 'nullable|date|after_or_equal:discount_starts_at',
            'quantity' => 'required|integer|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_visible_online' => 'boolean',
            'is_bulky' => 'boolean',
            'stock_status' => 'nullable|string|max:50',
            'pos_item_id' => 'nullable|string|max:100',
            'parent_item_number' => 'nullable|string|max:100',
            'barcodes_text' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $barcodesText = $validated['barcodes_text'] ?? '';
        unset($validated['barcodes_text']);

        $item = Item::create($validated);

        $this->syncBarcodes($item, $barcodesText);

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
            'item' => $this->presentAdminItem($item->fresh(['category', 'barcodes'])),
        ], 201);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $this->authorizeItems();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'description' => 'nullable|string',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_is_active' => 'boolean',
            'discount_starts_at' => 'nullable|date',
            'discount_ends_at' => 'nullable|date|after_or_equal:discount_starts_at',
            'quantity' => 'sometimes|required|integer|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_visible_online' => 'boolean',
            'is_bulky' => 'boolean',
            'stock_status' => 'nullable|string|max:50',
            'barcodes_text' => 'nullable|string',
        ]);

        if (isset($validated['name']) && $item->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $barcodesText = $validated['barcodes_text'] ?? null;
        unset($validated['barcodes_text']);

        $item->update($validated);

        // Only touch barcodes when the client explicitly submitted the field.
        if (array_key_exists('barcodes_text', $request->all())) {
            $this->syncBarcodes($item, $barcodesText ?? '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'item' => $this->presentAdminItem($item->fresh(['category', 'barcodes'])),
        ]);
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->authorizeItems();

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorizeItems();

        $validated = $request->validate([
            'action' => 'required|in:change_category,bulk_hide,bulk_show',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:items,id',
            'category_id' => 'required_if:action,change_category|exists:categories,id',
        ]);

        $items = Item::whereIn('id', $validated['ids']);

        match ($validated['action']) {
            'change_category' => $items->update(['category_id' => $validated['category_id']]),
            'bulk_hide' => $items->update(['is_visible_online' => false]),
            'bulk_show' => $items->update(['is_visible_online' => true]),
        };

        return response()->json(['success' => true, 'message' => 'Bulk action completed.']);
    }

    public function bulkUpdateQuantities(Request $request): JsonResponse
    {
        $this->authorizeItems();

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:items,id',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        $updated = 0;
        $failed = 0;

        foreach ($validated['items'] as $entry) {
            try {
                $quantity = $entry['quantity'];
                $stockStatus = 'in_stock';
                if ($quantity <= 0) {
                    $stockStatus = 'out_of_stock';
                } elseif ($quantity < 5) {
                    $stockStatus = 'low_stock';
                }

                Item::where('id', $entry['id'])->update([
                    'quantity' => $quantity,
                    'is_visible_online' => $quantity > 0,
                    'stock_status' => $stockStatus,
                ]);
                $updated++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} item(s) updated successfully." . ($failed > 0 ? " {$failed} failed." : ''),
            'updated' => $updated,
            'failed' => $failed,
        ]);
    }

    private function syncBarcodes(Item $item, string $barcodesText): void
    {
        $barcodes = array_unique(array_map('strtolower', array_filter(array_map('trim', explode(',', $barcodesText)))));

        $item->barcodes()->delete();

        foreach ($barcodes as $barcode) {
            if ($barcode !== '') {
                ItemBarcode::create([
                    'item_id' => $item->id,
                    'barcode_type' => 'item_number',
                    'barcode_value' => $barcode,
                ]);
            }
        }
    }

    private function presentAdminItem(Item $item): array
    {
        return array_merge($this->presentItem($item), [
            'barcodes' => $item->barcodes->pluck('barcode_value')->values(),
            'own_image_url' => $item->own_image_url,
        ]);
    }
}
