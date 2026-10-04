<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use App\Models\SlideshowImage;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->where('show_on_home', true)
            ->with('images')
            ->withCount(['items' => fn ($q) => $q->whereNull('parent_item_number')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

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
            ->inRandomOrder()
            ->limit(12)
            ->get();

        $settings = Setting::pluck('value', 'key')->toArray();

        $slideshowImages = SlideshowImage::getActive();

        return view('home', compact('categories', 'deals', 'recentItems', 'settings', 'slideshowImages'));
    }
}
