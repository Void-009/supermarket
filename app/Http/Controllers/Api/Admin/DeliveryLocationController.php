<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryLocationController extends Controller
{
    private function authorizeLocations(): void
    {
        abort_unless(auth()->user()->hasPermission('manage_delivery_locations'), 403, 'You do not have permission to manage delivery locations.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeLocations();

        $locations = DeliveryLocation::withCount('orders')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->input('search'));
                $q->where(fn ($b) => $b->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'delivery_locations' => $locations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeLocations();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'motorcycle_rate' => 'nullable|numeric|min:0',
            'truck_rate' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $location = DeliveryLocation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Delivery location created successfully.',
            'delivery_location' => $location,
        ], 201);
    }

    public function update(Request $request, DeliveryLocation $deliveryLocation): JsonResponse
    {
        $this->authorizeLocations();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'cost' => 'sometimes|required|numeric|min:0',
            'motorcycle_rate' => 'nullable|numeric|min:0',
            'truck_rate' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $deliveryLocation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Delivery location updated successfully.',
            'delivery_location' => $deliveryLocation->fresh(),
        ]);
    }

    public function destroy(DeliveryLocation $deliveryLocation): JsonResponse
    {
        $this->authorizeLocations();

        $deliveryLocation->orders()->update(['delivery_location_id' => null]);
        $deliveryLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery location deleted successfully.',
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $this->authorizeLocations();

        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:delivery_locations,id',
        ]);

        $locations = DeliveryLocation::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($locations as $location) {
            match ($validated['action']) {
                'delete' => $location->delete(),
                'activate' => $location->update(['is_active' => true]),
                'deactivate' => $location->update(['is_active' => false]),
            };
            $count++;
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];

        return response()->json([
            'success' => true,
            'message' => "{$count} location(s) {$labels[$validated['action']]} successfully.",
        ]);
    }
}
