<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCampaign;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountCampaignController extends Controller
{
    private function authorizeCampaigns(): void
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403, 'You do not have permission to manage discount campaigns.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeCampaigns();

        $campaigns = DiscountCampaign::with('creator')
            ->withCount('items')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->input('search'));
                $q->where(fn ($b) => $b->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'campaigns' => collect($campaigns->items())->map(fn ($c) => $this->presentCampaign($c)),
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    public function show(DiscountCampaign $campaign): JsonResponse
    {
        $this->authorizeCampaigns();

        $campaign->load(['items', 'creator']);

        return response()->json([
            'success' => true,
            'campaign' => $this->presentCampaign($campaign, true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeCampaigns();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:items,id',
        ]);

        $campaign = DiscountCampaign::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'discount_percentage' => $validated['discount_percentage'],
            'is_active' => $validated['is_active'] ?? false,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'created_by' => $request->user()->id,
        ]);

        $campaign->items()->sync($validated['item_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully.',
            'campaign' => $this->presentCampaign($campaign->fresh(['creator']), true),
        ], 201);
    }

    public function update(Request $request, DiscountCampaign $campaign): JsonResponse
    {
        $this->authorizeCampaigns();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:items,id',
        ]);

        $campaign->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'discount_percentage' => $validated['discount_percentage'],
            'is_active' => $validated['is_active'] ?? false,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        $campaign->items()->sync($validated['item_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully.',
            'campaign' => $this->presentCampaign($campaign->fresh(['creator']), true),
        ]);
    }

    public function destroy(DiscountCampaign $campaign): JsonResponse
    {
        $this->authorizeCampaigns();

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted.',
        ]);
    }

    public function eligibleItems(): JsonResponse
    {
        $this->authorizeCampaigns();

        $items = Item::visible()
            ->with(['category', 'barcodes'])
            ->orderBy('name')
            ->get(['id', 'name', 'selling_price', 'category_id', 'is_visible_online']);

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    private function presentCampaign(DiscountCampaign $campaign, bool $withItems = false): array
    {
        $data = [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'discount_percentage' => (float) $campaign->discount_percentage,
            'is_active' => (bool) $campaign->is_active,
            'starts_at' => $campaign->starts_at?->toISOString(),
            'ends_at' => $campaign->ends_at?->toISOString(),
            'notified_at' => $campaign->notified_at?->toISOString(),
            'is_currently_active' => $campaign->isCurrentlyActive(),
            'created_by' => $campaign->creator?->name,
            'items_count' => $campaign->items_count ?? null,
            'created_at' => $campaign->created_at?->toISOString(),
        ];

        if ($withItems) {
            $data['items'] = $campaign->items->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'selling_price' => (float) $item->selling_price,
            ])->values();
        }

        return $data;
    }
}
