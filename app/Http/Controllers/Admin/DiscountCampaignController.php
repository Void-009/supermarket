<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCampaign;
use App\Models\Item;
use App\Notifications\DiscountAlert;
use App\Services\PersonalizedRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class DiscountCampaignController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403);

        $campaigns = DiscountCampaign::with('creator')
            ->withCount('items')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->input('search'));
                $q->where(function ($b) use ($search) {
                    $b->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('admin.discount-campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403);

        $items = Item::visible()->with(['category', 'barcodes'])->orderBy('name')->get();
        $categories = $items->pluck('category.name')->filter()->unique()->sort()->values();
        return view('admin.discount-campaigns.create', compact('items', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('manage_discounts'), 403);

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

        return redirect()->route('admin.discount-campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    public function show(DiscountCampaign $discountCampaign)
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403);

        $discountCampaign->load(['items', 'creator']);
        return view('admin.discount-campaigns.show', compact('discountCampaign'));
    }

    public function edit(DiscountCampaign $discountCampaign)
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403);

        $discountCampaign->load('items');
        $items = Item::visible()->with(['category', 'barcodes'])->orderBy('name')->get();
        $categories = $items->pluck('category.name')->filter()->unique()->sort()->values();
        return view('admin.discount-campaigns.edit', compact('discountCampaign', 'items', 'categories'));
    }

    public function update(Request $request, DiscountCampaign $discountCampaign)
    {
        abort_unless($request->user()->hasPermission('manage_discounts'), 403);

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

        $discountCampaign->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'discount_percentage' => $validated['discount_percentage'],
            'is_active' => $validated['is_active'] ?? false,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        $discountCampaign->items()->sync($validated['item_ids']);

        return redirect()->route('admin.discount-campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(DiscountCampaign $discountCampaign)
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403);

        $discountCampaign->delete();
        return redirect()->route('admin.discount-campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    public function toggleActive(DiscountCampaign $discountCampaign)
    {
        abort_unless(auth()->user()->hasPermission('manage_discounts'), 403);

        $discountCampaign->update([
            'is_active' => !$discountCampaign->is_active,
        ]);

        return redirect()->back()->with('success',
            $discountCampaign->is_active ? 'Campaign activated.' : 'Campaign deactivated.'
        );
    }

    public function notifySubscribers(
        Request $request,
        DiscountCampaign $discountCampaign,
        PersonalizedRecommendationService $recommendationService
    ): JsonResponse {
        abort_unless($request->user()->hasPermission('manage_discounts'), 403);

        if (!$discountCampaign->isCurrentlyActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is not active. Activate it and check start/end dates first.',
            ], 422);
        }

        $users = \App\Models\User::whereHas('pushSubscriptions')->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users with push notifications enabled.',
            ], 422);
        }

        $sent = 0;
        foreach ($users as $user) {
            $recommendations = $recommendationService->getRecommendationsForCampaign($discountCampaign, $user);
            if ($recommendations->isEmpty()) continue;

            $message = $recommendationService->buildPersonalizedMessage(
                $user, $recommendations, $discountCampaign->name
            );

            $user->notify(new DiscountAlert(
                $message['title'],
                $message['body'],
                url('/shop'),
                null,
                "campaign-{$discountCampaign->id}"
            ));
            $sent++;
        }

        $discountCampaign->update(['notified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Personalized notifications sent to {$sent} user(s).",
        ]);
    }
}
