<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        $sort = $request->input('sort', 'newest');

        $categories = Category::withCount('items')
            ->withCount(['items as ordered_count' => function ($q) {
                $q->join('order_items', 'items.id', '=', 'order_items.item_id')
                  ->join('orders', 'order_items.order_id', '=', 'orders.id')
                  ->where('orders.status', 'paid');
            }]);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $categories->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($sort === 'popular') {
            $categories->orderByDesc('ordered_count');
        } else {
            $categories->latest();
        }

        $categories = $categories->paginate(25)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($categories);
        }

        return view('admin.categories.index', compact('categories', 'sort'));
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'sort_order' => 'integer|min:0',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if (array_key_exists('show_on_home', $request->all())) {
            $validated['show_on_home'] = $request->boolean('show_on_home');
        }

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'sort_order' => 'integer|min:0',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if (array_key_exists('show_on_home', $request->all())) {
            $validated['show_on_home'] = $request->boolean('show_on_home');
        }

        if (isset($validated['name']) && $category->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => $category,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        if ($category->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing items. Please remove or reassign items first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function uploadImage(Request $request, Category $category): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('categories', 'public');

        $isPrimary = $category->images()->count() === 0;

        $maxOrder = $category->images()->max('sort_order');
        $sortOrder = $maxOrder === null ? 0 : $maxOrder + 1;

        $image = $category->images()->create([
            'path' => $path,
            'sort_order' => $sortOrder,
            'is_primary' => $isPrimary,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'image' => $image,
        ]);
    }

    public function deleteImage(Request $request, CategoryImage $image): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
        ]);
    }

    public function setPrimaryImage(Request $request, CategoryImage $image): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        $category = $image->category;
        $category->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary image updated.',
        ]);
    }

    public function bulk(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_categories')) {
            abort(403, 'You do not have permission to manage categories.');
        }

        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:categories,id',
        ]);

        $categories = Category::whereIn('id', $validated['ids'])->get();
        $count = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            if ($validated['action'] === 'delete') {
                if ($category->items()->exists()) {
                    $skipped++;
                    continue;
                }
                $category->delete();
                $count++;
            } elseif ($validated['action'] === 'activate') {
                $category->update(['is_active' => true]);
                $count++;
            } elseif ($validated['action'] === 'deactivate') {
                $category->update(['is_active' => false]);
                $count++;
            }
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];
        $message = "{$count} category(s) {$labels[$validated['action']]} successfully.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (have items).";
        }

        return response()->json(['success' => true, 'message' => $message]);
    }
}


