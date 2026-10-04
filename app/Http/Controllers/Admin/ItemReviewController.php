<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Supervision page for items recently created or updated by the POS sync
 * agent. Lets staff review and correct prices, quantities and the
 * parent/child (variation) relationship.
 */
class ItemReviewController extends Controller
{
    protected function canAccess(): bool
    {
        return auth()->user()->hasPermission('modify_items');
    }

    public function index()
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to view items.');
        }

        $categories = Category::orderBy('name')->get();

        return view('admin.items.reviews', compact('categories'));
    }

    public function data(Request $request): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to view items.');
        }

        $days = min(max((int) $request->input('days', 7), 1), 60);
        $type = $request->input('type', '');
        $search = trim($request->input('search', ''));
        $categoryId = $request->input('category_id');

        $query = Item::with(['category', 'parentItem'])
            ->where('last_synced_at', '>=', now()->subDays($days));

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('display_name', 'like', $like)
                  ->orWhere('pos_item_id', 'like', $like)
                  ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode_value', 'like', $like));
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // An item is "new" when it was first synced around its creation time;
        // otherwise it was updated by a later sync.
        if ($type === 'new') {
            $query->whereRaw('TIMESTAMPDIFF(SECOND, created_at, last_synced_at) <= 120');
        } elseif ($type === 'updated') {
            $query->whereRaw('TIMESTAMPDIFF(SECOND, created_at, last_synced_at) > 120');
        }

        $items = $query->latest('last_synced_at')->paginate(25)->withQueryString();

        $rows = $items->getCollection()->map(function (Item $item) {
            $isNew = $item->created_at
                && $item->last_synced_at
                && $item->created_at->diffInSeconds($item->last_synced_at) <= 120;

            return [
                'id' => $item->id,
                'pos_item_id' => $item->pos_item_id,
                'parent_item_number' => $item->parent_item_number,
                'name' => $item->name,
                'display_name' => $item->display_name,
                'slug' => $item->slug,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name,
                'selling_price' => (float) $item->selling_price,
                'cost_price' => (float) $item->cost_price,
                'quantity' => (int) $item->quantity,
                'stock_status' => $item->stock_status,
                'is_visible_online' => (bool) $item->is_visible_online,
                'image_url' => $item->image_url,
                'own_image_url' => $item->own_image_url,
                'parent_name' => $item->parentItem ? $item->parentItem->display_name : null,
                'change_type' => $isNew ? 'new' : 'updated',
                'created_at' => $item->created_at?->toIso8601String(),
                'created_at_label' => $item->created_at?->format('M d, Y g:i A'),
                'last_synced_at' => $item->last_synced_at?->toIso8601String(),
                'last_synced_at_label' => $item->last_synced_at?->format('M d, Y g:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $rows,
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'per_page' => $items->perPage(),
            'last_page' => $items->lastPage(),
        ]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to modify items.');
        }

        $validated = $request->validate([
            'display_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'selling_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'parent_item_number' => 'nullable|string|max:100',
            'is_visible_online' => 'boolean',
        ]);

        if (array_key_exists('parent_item_number', $validated)) {
            $parent = trim((string) $validated['parent_item_number']);

            if ($parent !== '' && $parent === $item->pos_item_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'An item cannot be its own parent.',
                ], 422);
            }

            if ($parent !== '' && ! Item::where('pos_item_id', $parent)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent item does not exist. Pick a valid parent item.',
                ], 422);
            }

            $validated['parent_item_number'] = $parent !== '' ? $parent : null;
        }

        if (array_key_exists('quantity', $validated)) {
            $quantity = (int) $validated['quantity'];
            $validated['stock_status'] = match (true) {
                $quantity <= 0 => 'out_of_stock',
                $quantity < 5 => 'low_stock',
                default => 'in_stock',
            };
        }

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'item' => $item->fresh(['category', 'parentItem']),
        ]);
    }

    /**
     * Parent items for the "parent to child" selector in the review editor.
     */
    public function parents(Request $request): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to view items.');
        }

        $search = trim($request->input('search', ''));

        $query = Item::parents()
            ->select('id', 'pos_item_id', 'name', 'display_name');

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('pos_item_id', 'like', $like)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('display_name', 'like', $like);
            });
        }

        $items = $query->orderBy('name')->limit(30)->get()->map(fn (Item $i) => [
            'pos_item_id' => $i->pos_item_id,
            'name' => $i->name,
            'display_name' => $i->display_name,
        ]);

        return response()->json(['success' => true, 'items' => $items]);
    }
}
