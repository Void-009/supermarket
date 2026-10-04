<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBarcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('modify_items') && !auth()->user()->isSuperadmin() && !auth()->user()->isAdmin()) {
            abort(403, 'You do not have permission to view items.');
        }

        $query = Item::with(['category', 'barcodes', 'parentItem']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhereHas('barcodes', function ($bq) use ($search) {
                      $bq->where('barcode_value', 'like', "%{$search}%");
                  });
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
        switch ($sort) {
            case 'oldest':      $query->oldest(); break;
            case 'name_asc':    $query->orderBy('name', 'asc'); break;
            case 'name_desc':   $query->orderBy('name', 'desc'); break;
            case 'price_asc':   $query->orderBy('selling_price', 'asc'); break;
            case 'price_desc':  $query->orderBy('selling_price', 'desc'); break;
            case 'stock_asc':   $query->orderBy('quantity', 'asc'); break;
            default:            $query->latest(); break;
        }

        $items = $query->paginate(25)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($items);
        }

        $categories = Category::orderBy('name')->get();

        $recentItems = Item::latest()->limit(8)->get();

        $mostPopular = Item::with('category')
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->limit(8)
            ->get();

        $adminFavoriteItems = Item::where('is_admin_favorite', true)
            ->with('category')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.items.index', compact('items', 'categories', 'recentItems', 'mostPopular', 'adminFavoriteItems'));
    }



    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to modify items.');
        }

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
            'is_visible_online' => 'boolean',
            'is_bulky' => 'boolean',
            'stock_status' => 'nullable|string|max:50',
            'pos_item_id' => 'nullable|string|max:100',
            'parent_item_number' => 'nullable|string|max:100',
            'barcodes_text' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('items', 'public');
        }

        $validated['slug'] = Str::slug($validated['name']);

        $barcodesText = $validated['barcodes_text'] ?? '';
        unset($validated['barcodes_text']);

        $item = Item::create($validated);

        try {
            $this->syncBarcodes($item, $barcodesText);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
        ], 201);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to modify items.');
        }

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
            'is_visible_online' => 'boolean',
            'is_bulky' => 'boolean',
            'stock_status' => 'nullable|string|max:50',
            'barcodes_text' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('items', 'public');
        }

        if (isset($validated['name']) && $item->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $barcodesText = $validated['barcodes_text'] ?? null;
        unset($validated['barcodes_text']);

        $item->update($validated);

        // Only touch barcodes when the client explicitly submitted the field.
        // Because empty strings are converted to null by the global middleware,
        // use the raw request so an intentionally-cleared field still resyncs.
        if (array_key_exists('barcodes_text', $request->all())) {
            try {
                $this->syncBarcodes($item, $barcodesText ?? '');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
        ]);
    }



    public function destroy(Item $item): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to modify items.');
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to modify items.');
        }

        $validated = $request->validate([
            'action' => 'required|in:change_category,bulk_hide,bulk_show',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:items,id',
            'category_id' => 'required_if:action,change_category|exists:categories,id',
        ]);

        $items = Item::whereIn('id', $validated['ids']);

        switch ($validated['action']) {
            case 'change_category':
                $items->update(['category_id' => $validated['category_id']]);
                break;
            case 'bulk_hide':
                $items->update(['is_visible_online' => false]);
                break;
            case 'bulk_show':
                $items->update(['is_visible_online' => true]);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk action completed.',
        ]);
    }

    public function variationsPage()
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to view items.');
        }

        return view('admin.variations.index');
    }

    public function variations(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to view items.');
        }

        $perPage = min((int) $request->input('per_page', 200), 200);
        $page = max((int) $request->input('page', 1), 1);
        $search = $request->input('search', '');

        $query = Item::where('pos_item_id', 'like', '%@%')
            ->select('id', 'pos_item_id', 'name', 'display_name', 'quantity', 'is_visible_online', 'selling_price', 'cost_price', 'stock_status');

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('display_name', 'like', $searchTerm)
                  ->orWhere('pos_item_id', 'like', $searchTerm)
                  ->orWhereHas('barcodes', function ($bq) use ($searchTerm) {
                      $bq->where('barcode_value', 'like', $searchTerm);
                  });
            });
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('pos_item_id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    public function bulkUpdateQuantities(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to modify items.');
        }

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

    public function uploadImage(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('modify_items')) {
            abort(403, 'You do not have permission to modify items.');
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $path = $file->store('items', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ]);
    }

    private function syncBarcodes(Item $item, string $barcodesText): void
    {
        $barcodes = array_filter(array_map('trim', explode(',', $barcodesText)));
        $barcodes = array_unique(array_map('strtolower', $barcodes));

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
}

