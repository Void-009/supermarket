<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PosItemAlias;
use App\Services\ItemVariationMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Review screen for variation identity problems: sibling groups that are
 * the same product listed twice (same parent + normalized name + cost).
 * Nothing is merged automatically — staff pick the survivor per group.
 */
class VariationDuplicatesController extends Controller
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

        return view('admin.variations.duplicates');
    }

    public function data(): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to view items.');
        }

        $variations = Item::whereNotNull('parent_item_number')
            ->whereNull('pos_missing_since')
            ->withCount(['favoritedBy', 'orderItems'])
            ->get();

        $groups = $variations
            ->groupBy(fn (Item $v) => $v->parent_item_number.'|'.Item::variationGroupKey($v))
            ->filter(fn (Collection $g) => $g->count() > 1)
            ->values()
            ->map(fn (Collection $group) => [
                'parent_item_number' => $group->first()->parent_item_number,
                'name' => $group->first()->name,
                'items' => $group->sortByDesc(fn (Item $v) => [
                    $v->last_modified?->timestamp ?? 0,
                    (int) $v->id,
                ])->map(fn (Item $v) => $this->presentRow($v))->values(),
            ]);

        $tombstoned = Item::whereNotNull('parent_item_number')
            ->whereNotNull('pos_missing_since')
            ->withCount(['favoritedBy', 'orderItems'])
            ->orderByDesc('pos_missing_since')
            ->limit(200)
            ->get()
            ->map(fn (Item $v) => $this->presentRow($v) + ['pos_missing_since' => $v->pos_missing_since?->format('M d, Y g:i A')]);

        $adminHidden = Item::whereNotNull('parent_item_number')
            ->where('hidden_by_admin', true)
            ->whereNull('pos_missing_since')
            ->withCount(['favoritedBy', 'orderItems'])
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->map(fn (Item $v) => $this->presentRow($v));

        return response()->json([
            'success' => true,
            'groups' => $groups,
            'tombstoned' => $tombstoned,
            'admin_hidden' => $adminHidden,
            'alias_count' => PosItemAlias::count(),
        ]);
    }

    private function presentRow(Item $item): array
    {
        return [
            'id' => $item->id,
            'pos_item_id' => $item->pos_item_id,
            'name' => $item->name,
            'selling_price' => (float) $item->selling_price,
            'cost_price' => (float) $item->cost_price,
            'is_visible_online' => (bool) $item->is_visible_online,
            'hidden_by_admin' => (bool) $item->hidden_by_admin,
            'pos_inactive' => (bool) $item->pos_inactive,
            'has_image' => ! empty($item->getRawOriginal('image_url')),
            'has_description' => ! empty($item->description),
            'favorites_count' => (int) ($item->favorited_by_count ?? 0),
            'orders_count' => (int) ($item->order_items_count ?? 0),
            'last_modified' => $item->last_modified?->format('M d, Y g:i A'),
            'last_synced_at' => $item->last_synced_at?->format('M d, Y g:i A'),
        ];
    }

    public function merge(Request $request, ItemVariationMergeService $merger): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to modify items.');
        }

        $validated = $request->validate([
            'survivor_id' => 'required|integer|exists:items,id',
            'loser_id' => 'required|integer|different:survivor_id|exists:items,id',
        ]);

        $survivor = Item::findOrFail($validated['survivor_id']);
        $loser = Item::findOrFail($validated['loser_id']);

        $merger->merge($survivor, $loser);

        return response()->json([
            'success' => true,
            'message' => "Merged {$loser->pos_item_id} into {$survivor->pos_item_id}. The merged item is hidden from the store and the sync will keep it hidden. Remember to also delete it in the POS when convenient.",
        ]);
    }

    public function hide(Request $request, Item $item): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to modify items.');
        }

        $item->update([
            'is_visible_online' => false,
            'hidden_by_admin' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$item->pos_item_id} hidden. The POS sync will not bring it back.",
        ]);
    }

    public function unhide(Request $request, Item $item): JsonResponse
    {
        if (! $this->canAccess()) {
            abort(403, 'You do not have permission to modify items.');
        }

        $item->update([
            'hidden_by_admin' => false,
            'pos_missing_since' => null,
            'is_visible_online' => ! $item->pos_inactive,
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$item->pos_item_id} is visible again.",
        ]);
    }
}
