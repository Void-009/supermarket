<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\DeliverySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryLocationController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery locations.');
        }

        $locations = DeliveryLocation::withCount('orders')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->input('search'));
                $q->where(function ($b) use ($search) {
                    $b->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($locations);
        }

        $feeSelectorEnabled = DeliverySetting::showDeliveryFeeSelector();

        return view('admin.delivery-locations.index', compact('locations', 'feeSelectorEnabled'));
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery locations.');
        }

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
            'location' => $location,
        ], 201);
    }

    public function update(Request $request, DeliveryLocation $deliveryLocation): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery locations.');
        }

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
            'location' => $deliveryLocation,
        ]);
    }

    public function destroy(DeliveryLocation $deliveryLocation): JsonResponse
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery locations.');
        }

        $deliveryLocation->orders()->update(['delivery_location_id' => null]);
        $deliveryLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery location deleted successfully.',
        ]);
    }

    public function activeLocations(Request $request)
    {
        $locations = DeliveryLocation::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'cost', 'motorcycle_rate', 'truck_rate', 'description']);

        return response()->json($locations);
    }

    public function bulk(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_delivery_locations')) {
            abort(403, 'You do not have permission to manage delivery locations.');
        }

        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:delivery_locations,id',
        ]);

        $locations = DeliveryLocation::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($locations as $location) {
            if ($validated['action'] === 'delete') {
                $location->orders()->update(['delivery_location_id' => null]);
                $location->delete();
                $count++;
            } elseif ($validated['action'] === 'activate') {
                $location->update(['is_active' => true]);
                $count++;
            } elseif ($validated['action'] === 'deactivate') {
                $location->update(['is_active' => false]);
                $count++;
            }
        }

        $labels = ['delete' => 'deleted', 'activate' => 'activated', 'deactivate' => 'deactivated'];
        return response()->json([
            'success' => true,
            'message' => "{$count} location(s) {$labels[$validated['action']]} successfully.",
        ]);
    }
}
