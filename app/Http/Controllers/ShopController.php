<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\SlideshowImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{

    public function index(Request $request)
    {

        $query = Item::visible()->parents();

        if ($search = $request->input('search')) {
            $query->searchable($search);
        }



        if ($categorySlug = $request->input('category')) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }



        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('selling_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('selling_price', 'desc');
                break;

            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }



        $items = $query->with(['category', 'images', 'variations.parentItem'])->paginate(24)->withQueryString();
        $categories = Category::where('is_active', true)->where('show_on_home', true)->with('images')->orderBy('sort_order')->get();

        $activeCategory = null;
        $categoryImages = collect();
        if ($categorySlug = $request->input('category')) {
            $activeCategory = $categories->firstWhere('slug', $categorySlug);
            if ($activeCategory) {
                $categoryImages = $activeCategory->images;
            }
        }

        $recommendations = collect();
        $user = $request->user();
        if ($user) {
            $purchasedItemIds = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.user_id', $user->id)
                ->where('orders.status', '!=', 'cancelled')
                ->pluck('order_items.item_id')
                ->unique();

            if ($purchasedItemIds->isNotEmpty()) {
                $recommendations = Item::visible()
                    ->parents()
                    ->whereIn('id', function ($q) use ($purchasedItemIds) {
                        $q->select('order_items.item_id')
                          ->from('order_items')
                          ->join('orders', 'order_items.order_id', '=', 'orders.id')
                          ->where('orders.status', '!=', 'cancelled')
                          ->whereIn('order_items.item_id', $purchasedItemIds)
                          ->orWhereIn('orders.id', function ($q2) use ($purchasedItemIds) {
                              $q2->select('order_items.order_id')
                                ->from('order_items')
                                ->whereIn('order_items.item_id', $purchasedItemIds);
                          });
                    })
                    ->with(['category', 'variations.parentItem'])
                    ->withCount('orderItems')
                    ->orderByDesc('order_items_count')
                    ->limit(8)
                    ->get()
                    ->reject(function ($item) use ($purchasedItemIds) {
                        return $purchasedItemIds->contains($item->id);
                    })
                    ->values();
            }
        }

        $slideshowImages = SlideshowImage::getActive();

        return view('shop.index', compact('items', 'categories', 'activeCategory', 'categoryImages', 'recommendations', 'slideshowImages'));
    }



    public function show($slug)
    {
        $item = Item::where('slug', $slug)
            ->visible()
            ->with(['category', 'barcodes', 'images'])
            ->firstOrFail();

        $isVariation = $item->isVariation();
        $parent = null;

        if ($isVariation && $item->parent_item_number) {
            $parent = Item::where('pos_item_id', $item->parent_item_number)
                ->visible()
                ->first();

            $variations = Item::where('parent_item_number', $item->parent_item_number)
                ->where('id', '!=', $item->id)
                ->orderBy('display_name')
                ->get();
        } else {
            $variations = Item::where('parent_item_number', $item->pos_item_id)
                ->where('id', '!=', $item->id)
                ->orderBy('display_name')
                ->get();
        }

        // The current item is always part of the selector so it can be pre-selected.
        if ($isVariation && $variations->doesntContain('id', $item->id)) {
            $variations->prepend($item);
        }

        // The picker must show the whole group (parent + children) so a child
        // can always be switched back to the parent. The parent is NOT added to
        // $variations because that collection also drives the "more variations"
        // list further down the page.
        $pickerVariations = $variations;
        if ($isVariation && $parent && $pickerVariations->doesntContain('id', $parent->id)) {
            $pickerVariations->prepend($parent);
        }

        $pickerProduct = [
            'id' => $item->id,
            'name' => $item->name,
            'display_name' => $item->display_name,
            'slug' => $item->slug,
            'selling_price' => (float) $item->effectivePrice(),
            'compare_at_price' => $item->isDiscounted() ? (float) $item->selling_price : null,
            'image_url' => $item->image_url,
            'stock' => (int) $item->effectiveQuantity(),
            'is_variation' => (bool) $isVariation,
            'variations' => $pickerVariations->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'display_name' => $v->display_name,
                    'slug' => $v->slug,
                    'selling_price' => (float) $v->effectivePrice(),
                    'compare_at_price' => $v->isDiscounted() ? (float) $v->selling_price : null,
                    'image_url' => $v->image_url,
                    'stock' => (int) $v->effectiveQuantity(),
                    'is_variation' => (bool) $v->isVariation(),
                ];
            })->values()->all(),
        ];

        return view('shop.show', compact('item', 'variations', 'parent', 'isVariation', 'pickerProduct'));
    }
}

