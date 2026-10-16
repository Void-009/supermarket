<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBarcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Barcode first, then item ID. The client sends `by` so we can skip the
     * expensive full-table LIKE fallbacks entirely for numeric input.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input("query", $request->input("q", "")));
        $mode = $this->resolveMode($request->input("by"), $query);

        // Barcode / item-ID lookups resolve to a single row, so a small cap is
        // harmless there. Text search must return every match: someone typing
        // "oil" expects the whole list (593 items in this catalogue), not the
        // first 10. Only apply a limit when the caller actually asked for one.
        $requested = $request->input("limit");
        $limit = $requested === null || $requested === ""
            ? null
            : max(1, min(500, (int) $requested));

        if (strlen($query) < 1) {
            return response()->json(["items" => [], "mode" => $mode, "total" => 0]);
        }

        // 1. Exact barcode match (highest priority for scanner)
        $barcodeMatch = ItemBarcode::where("barcode_value", mb_strtolower($query))
            ->whereHas("item", fn ($q) => $q->visible())
            ->with("item.category")
            ->first();

        if ($barcodeMatch && $barcodeMatch->item) {
            return response()->json([
                "items" => [$this->formatItem($barcodeMatch->item)],
                "exact_match" => true,
                "mode" => "barcode",
            ]);
        }

        // 2. Exact ID match (numeric query = item ID).
        //    pos_item_id is checked first on purpose: it is the number printed on
        //    the shelf label / receipt, so it is what a customer or cashier
        //    actually types. The auto-increment id is only a fallback, otherwise
        //    a query like "1070" would return whatever item happens to have that
        //    internal id instead of the one labelled 1070.
        if (ctype_digit($query)) {
            $idMatch = Item::visible()
                ->with("category")
                ->where("pos_item_id", $query)
                ->first();

            if (! $idMatch) {
                $idMatch = Item::visible()
                    ->with("category")
                    ->where("id", $query)
                    ->first();
            }

            if ($idMatch) {
                return response()->json([
                    "items" => [$this->formatItem($idMatch)],
                    "exact_match" => true,
                    "mode" => "id",
                ]);
            }
        }

        // The client told us this is a barcode or an item ID, so an exact lookup
        // is all it wants. Stop before the LIKE scans.
        if ($mode === "barcode" || $mode === "id") {
            return response()->json(["items" => [], "exact_match" => false, "mode" => $mode]);
        }

        // 3. Search - optimized query with join, selected columns, bounded
        $items = DB::table("items")
            ->leftJoin("categories", "items.category_id", "=", "categories.id")
            ->where("items.is_visible_online", true)
            ->where("items.hidden_by_admin", false)
            ->whereNull("items.pos_missing_since")
            ->whereIn("items.category_id", Category::activeCategoryTreeIds())
            ->where(function ($q) use ($query) {
                $needle = mb_strtolower($query);
                $q->where("items.name", "like", "%{$query}%")
                  ->orWhere("items.display_name", "like", "%{$query}%");

                // MariaDB rejects LIMIT inside an IN/ANY subquery
                // (error 1235), so this subquery must stay unbounded. It is also
                // only worth running for queries that contain a digit: a plain
                // word appears inside plenty of barcodes and only adds noise
                // and scan cost. The outer limit($limit) still caps the response.
                if (preg_match("/\d/", $needle) === 1 && mb_strlen($needle) >= 3) {
                    $q->orWhereIn("items.id", function ($bq) use ($needle) {
                        $bq->select("item_id")
                           ->from("item_barcodes")
                           ->where("barcode_value", "like", "%{$needle}%");
                    });
                }
            })
            ->select(
                "items.id",
                "items.name",
                "items.display_name",
                "items.slug",
                "items.pos_item_id",
                "items.parent_item_number",
                "items.selling_price",
                "items.discount_percentage",
                "items.discount_is_active",
                "items.discount_starts_at",
                "items.discount_ends_at",
                "items.image_url",
                "items.quantity",
                "items.stock_status",
                "items.is_bulky",
                "categories.name as category_name"
            )
            ->when($request->filled("category"), function ($q) use ($request) {
                $q->orderByRaw("CASE WHEN categories.slug = ? THEN 0 ELSE 1 END", [$request->category]);
            })
            ->orderBy("items.name")
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        $posIds = $items->pluck("pos_item_id")->filter()->values();
        $parentNumbers = $items->pluck("parent_item_number")->filter()->values();
        $variationsByParent = Item::whereIn("parent_item_number", $posIds)
            ->orderBy("display_name")
            ->get()
            ->groupBy("parent_item_number");
        $parentsById = Item::whereIn("pos_item_id", $parentNumbers)
            ->get()
            ->keyBy("pos_item_id");

        $results = $items->map(function ($item) use ($variationsByParent, $parentsById) {
            $isVariation = ! empty($item->parent_item_number);

            $variations = collect();
            $parent = null;
            if ($isVariation) {
                $variations = $variationsByParent->get($item->parent_item_number, collect());
                $parent = $parentsById->get($item->parent_item_number);
            } else {
                $variations = $variationsByParent->get($item->pos_item_id, collect());
            }

            $options = Item::dedupeVariations($variations)->map(fn ($v) => $this->variationOption($v))->values();
            if ($parent && $isVariation && $options->doesntContain("id", $parent->id)) {
                $options->prepend($this->parentOption($parent));
            }
            $stock = $parent && $isVariation ? (int) $parent->quantity : (int) $item->quantity;
            $isBulky = $parent && $isVariation ? (bool) $parent->is_bulky : (bool) $item->is_bulky;

            return [
                "id" => $item->id,
                "name" => $item->name,
                "display_name" => $item->display_name,
                "slug" => $item->slug,
                "selling_price" => (float) $item->selling_price,
                "discount_price" => $this->effectivePrice($item),
                "image_url" => $item->image_url ?: ($parent?->image_url ?? null),
                "stock" => $stock,
                "stock_status" => $item->stock_status,
                "quantity" => $stock,
                "category" => $item->category_name,
                "is_variation" => $isVariation,
                "is_bulky" => $isBulky,
                "parent" => $parent ? [
                    "id" => $parent->id,
                    "name" => $parent->name,
                    "display_name" => $parent->display_name,
                    "slug" => $parent->slug,
                ] : null,
                "variations" => $options->all(),
            ];
        });

        return response()->json(["items" => $results, "exact_match" => false, "mode" => $mode, "total" => count($results)]);
    }

    /**
     * Decide how a query should be interpreted.
     *
     * 12+ digits  -> barcode
     * 3-5 digits  -> item ID
     * anything else -> free text (name / partial barcode)
     */
    private function resolveMode(?string $by, string $query): string
    {
        if ($by === "barcode" || $by === "id") {
            return $by;
        }

        if (preg_match("/^\d{12,}$/", $query)) {
            return "barcode";
        }

        if (preg_match("/^\d{3,5}$/", $query)) {
            return "id";
        }

        return "text";
    }

    private function formatItem(Item $item): array
    {
        $variations = collect();
        $parent = null;

        if ($item->isVariation() && $item->parent_item_number) {
            $parent = Item::where("pos_item_id", $item->parent_item_number)->first();
            $variations = Item::dedupeVariations(
                Item::where("parent_item_number", $item->parent_item_number)
                    ->orderBy("display_name")
                    ->get()
            );
            if ($variations->doesntContain("id", $item->id)) {
                $variations->prepend($item);
            }
            if ($parent && $variations->doesntContain("id", $parent->id)) {
                $variations->prepend($parent);
            }
        } else {
            $variations = Item::dedupeVariations(
                Item::where("parent_item_number", $item->pos_item_id)
                    ->orderBy("display_name")
                    ->get()
            );
        }

        return [
            "id" => $item->id,
            "name" => $item->name,
            "display_name" => $item->display_name,
            "slug" => $item->slug,
            "selling_price" => (float) $item->selling_price,
            "discount_price" => $item->isDiscounted() ? (float) $item->discount_price : null,
            "image_url" => $item->image_url,
            "stock" => $item->effectiveQuantity(),
            "stock_status" => $item->stock_status,
            "quantity" => $item->effectiveQuantity(),
            "category" => $item->category?->name,
            "is_variation" => $item->isVariation(),
            "is_bulky" => (bool) $item->is_bulky,
            "parent" => $parent ? [
                "id" => $parent->id,
                "name" => $parent->name,
                "display_name" => $parent->display_name,
                "slug" => $parent->slug,
            ] : null,
            "variations" => $variations->map(fn ($v) => $v->isVariation()
                ? $this->variationOption($v)
                : $this->parentOption($v))->values()->all(),
        ];
    }

    private function variationOption(Item $item): array
    {
        return [
            "id" => $item->id,
            "name" => $item->name,
            "display_name" => $item->display_name,
            "slug" => $item->slug,
            "selling_price" => (float) $item->effectivePrice(),
            "compare_at_price" => $item->isDiscounted() ? (float) $item->selling_price : null,
            "image_url" => $item->image_url,
            "stock" => (int) $item->effectiveQuantity(),
            "is_variation" => true,
            "is_bulky" => (bool) $item->is_bulky,
        ];
    }

    private function parentOption(Item $item): array
    {
        return [
            "id" => $item->id,
            "name" => $item->name,
            "display_name" => $item->display_name,
            "slug" => $item->slug,
            "selling_price" => (float) $item->effectivePrice(),
            "compare_at_price" => $item->isDiscounted() ? (float) $item->selling_price : null,
            "image_url" => $item->image_url,
            "stock" => (int) $item->effectiveQuantity(),
            "is_variation" => false,
            "is_bulky" => (bool) $item->is_bulky,
        ];
    }

    private function effectivePrice(object $item): ?float
    {
        $now = now();
        $active = (bool) $item->discount_is_active;

        if ($item->discount_starts_at && $now < $item->discount_starts_at) {
            $active = false;
        }
        if ($item->discount_ends_at && $now > $item->discount_ends_at) {
            $active = false;
        }

        if (!$active || empty($item->discount_percentage)) {
            return null;
        }

        return round((float) $item->selling_price * (1 - (float) $item->discount_percentage / 100), 2);
    }
}
