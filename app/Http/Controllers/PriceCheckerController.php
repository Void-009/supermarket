<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemBarcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceCheckerController extends Controller
{
    public function index(): View
    {
        return view('price-checker.index');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|max:100']);
        $query = trim($request->input('q'));
        $mode = $this->resolveMode($request->input('by'), $query);
        $limit = (int) $request->input('limit', 20);
        $limit = max(1, min(50, $limit));

        if ($query === '') {
            return response()->json(['items' => [], 'exact_match' => false, 'mode' => $mode]);
        }

        $queryLower = mb_strtolower($query);
        $items = collect();
        $exactMatch = false;

        // 1. Try exact barcode match (highest priority)
        $barcode = ItemBarcode::where('barcode_value', $queryLower)
            ->with('item.category')
            ->first();

        if ($barcode?->item?->visibleInStore()) {
            $exactMatch = true;
            $mode = 'barcode';
            $items = collect([$barcode->item]);
        }

        // 2. Try exact item ID. pos_item_id wins over the internal id, because
        //    it is the number printed on the shelf label and therefore the one
        //    the user types. A plain OR would return whichever row MySQL
        //    happened to return first (e.g. "1070" matching both the id 1070
        //    and the item labelled 1070).
        if ($items->isEmpty() && ctype_digit($query)) {
            $idItem = Item::visible()->where('pos_item_id', $query)->first();
            if (! $idItem) {
                $idItem = Item::visible()->where('id', $query)->first();
            }
            if ($idItem) {
                $exactMatch = true;
                $mode = 'id';
                $items = collect([$idItem]);
            }
        }

        // The client asked for an exact barcode / item ID lookup. Stop here
        // rather than falling through to the full-table LIKE scans.
        if ($items->isEmpty() && ($mode === 'barcode' || $mode === 'id')) {
            return response()->json(['items' => [], 'exact_match' => false, 'mode' => $mode]);
        }

        // 3. Partial barcode match. A 1-2 character needle cannot use the index
        //    and matches almost every row, so it is skipped entirely. The old
        //    unbounded get() here was the main cause of the page feeling laggy.
        //
        //    It also only runs when the query contains a digit. A letters-only
        //    word like "rice" appears inside plenty of barcodes, so running
        //    this first made a name search return a random unrelated item
        //    instead of the rice. Name search handles plain words; barcodes are
        //    for numeric input.
        $hasDigit = preg_match('/\d/', $query) === 1;
        if ($items->isEmpty() && $hasDigit && mb_strlen($queryLower) >= 3) {
            $partialBarcodes = ItemBarcode::where('barcode_value', 'like', '%' . $queryLower . '%')
                ->limit(200)
                ->pluck('item_id');

            if ($partialBarcodes->isNotEmpty()) {
                $items = $this->hydrateVisible($partialBarcodes, $limit);
            }
        }

        // 4. Try name / display name partial match
        if ($items->isEmpty()) {
            $nameItems = Item::visible()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('display_name', 'like', '%' . $query . '%');
                })
                ->with('category')
                ->limit($limit)
                ->get();

            if ($nameItems->isNotEmpty()) {
                $items = $nameItems;
            }
        }

        // 5. Try category name match
        if ($items->isEmpty()) {
            $categoryItems = Item::visible()
                ->whereHas('category', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                })
                ->with('category')
                ->limit($limit)
                ->get();

            if ($categoryItems->isNotEmpty()) {
                $items = $categoryItems;
            }
        }

        // 6. Fallback to general search
        if ($items->isEmpty()) {
            $fallback = Item::visible()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('display_name', 'like', '%' . $query . '%')
                      ->orWhereHas('barcodes', fn($q) => $q->where('barcode_value', 'like', '%' . mb_strtolower($query) . '%'));
                })
                ->with('category')
                ->limit($limit)
                ->get();

            if ($fallback->isNotEmpty()) {
                $items = $fallback;
            }
        }

        $formatted = $items->map(function ($item) {
            $item->loadMissing('barcodes');
            $discountPrice = $item->isDiscounted() ? $item->discount_price : null;
            return [
                'id' => $item->id,
                'name' => $item->name,
                'display_name' => $item->display_name,
                'slug' => $item->slug,
                'selling_price' => (float) $item->selling_price,
                'discount_price' => $discountPrice ? (float) $discountPrice : null,
                'image_url' => $item->image_url,
                'stock' => $item->effectiveQuantity(),
                'stock_status' => $item->stock_status,
                'category' => $item->category?->name ?? '',
                'barcodes' => $item->barcodes->pluck('barcode_value'),
            ];
        });

        return response()->json([
            'items' => $formatted,
            'exact_match' => $exactMatch,
            'mode' => $mode,
        ]);
    }

    /**
     * 12+ digits  -> barcode
     * 3-5 digits  -> item ID
     * anything else -> free text
     */
    private function resolveMode(?string $by, string $query): string
    {
        if ($by === 'barcode' || $by === 'id') {
            return $by;
        }

        if (preg_match('/^\d{12,}$/', $query)) {
            return 'barcode';
        }

        if (preg_match('/^\d{3,5}$/', $query)) {
            return 'id';
        }

        return 'text';
    }

    /**
     * Load the given item ids as models in one query, keeping only items that
     * are actually sellable, and cap the result set.
     */
    private function hydrateVisible($ids, int $limit)
    {
        return Item::visible()
            ->whereIn('id', $ids)
            ->with('category')
            ->limit($limit)
            ->get();
    }
}
