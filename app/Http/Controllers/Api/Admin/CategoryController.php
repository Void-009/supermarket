<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    private function authorizeCategories(): void
    {
        abort_unless(auth()->user()->hasPermission('manage_categories'), 403, 'You do not have permission to manage categories.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeCategories();

        $query = Category::withCount('items')
            ->withCount(['items as ordered_count' => function ($q) {
                $q->join('order_items', 'items.id', '=', 'order_items.item_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.status', 'paid');
            }]);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->when($request->input('sort') === 'popular', fn ($q) => $q->orderByDesc('ordered_count'), fn ($q) => $q->latest());

        $categories = $query->paginate(min(max((int) $request->input('per_page', 25), 1), 100));

        return response()->json([
            'success' => true,
            'categories' => collect($categories->items())->map(fn (Category $c) => $this->presentCategory($c)),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorizeCategories();

        $category->load('images', 'children', 'parent');

        return response()->json([
            'success' => true,
            'category' => $this->presentCategory($category),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeCategories();

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
            'category' => $this->presentCategory($category),
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->authorizeCategories();

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
            'category' => $this->presentCategory($category->fresh()),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorizeCategories();

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

    public function bulk(Request $request): JsonResponse
    {
        $this->authorizeCategories();

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

    private function presentCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'title_name' => $category->title_name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'image_url' => $category->displayImage(),
            'is_active' => (bool) $category->is_active,
            'show_on_home' => (bool) $category->show_on_home,
            'sort_order' => (int) $category->sort_order,
            'parent_id' => $category->parent_id,
            'items_count' => $category->items_count ?? null,
            'ordered_count' => $category->ordered_count ?? null,
            'created_at' => $category->created_at?->toISOString(),
        ];
    }
}
