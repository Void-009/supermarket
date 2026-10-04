<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cartItems = CartItem::with('item.category')
            ->forUser($request->user()->id)
            ->get();

        $items = $cartItems->map(fn (CartItem $cartItem) => [
            'id' => $cartItem->id,
            'item_id' => $cartItem->item_id,
            'name' => $cartItem->item->name,
            'slug' => $cartItem->item->slug,
            'category' => $cartItem->item->category?->name,
            'image' => $cartItem->item->image_url ? asset('storage/' . $cartItem->item->image_url) : null,
            'price' => (float) $cartItem->item->effectivePrice(),
            'original_price' => $cartItem->item->isDiscounted() ? (float) $cartItem->item->selling_price : null,
            'quantity' => (int) $cartItem->quantity,
            'max' => $cartItem->item->isVariation() ? null : (int) $cartItem->item->quantity,
            'is_purchasable' => $cartItem->item->isPurchasableOnline(),
            'subtotal' => round((float) $cartItem->item->effectivePrice() * $cartItem->quantity, 2),
        ])->values();

        $subtotal = $cartItems->sum(fn ($cartItem) => $cartItem->item->effectivePrice() * $cartItem->quantity);
        $totalQuantity = $cartItems->sum('quantity');

        return response()->json([
            'success' => true,
            'items' => $items,
            'subtotal' => round((float) $subtotal, 2),
            'total_quantity' => $totalQuantity,
            'count' => $totalQuantity,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = Item::findOrFail($validated['item_id']);

        if (! $item->isPurchasableOnline()) {
            return response()->json(['success' => false, 'message' => 'Item not available.'], 404);
        }

        $cartQuery = CartItem::forUser($request->user()->id);
        $cartItem = $cartQuery->where('item_id', $validated['item_id'])->first();
        $existingQuantity = $cartItem?->quantity ?? 0;

        if (! $item->isVariation() && $item->quantity < ($existingQuantity + $validated['quantity'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Only ' . $item->quantity . ' items available.',
            ], 422);
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $cartItem->quantity + $validated['quantity']]);
        } else {
            $cartItem = CartItem::create([
                'user_id' => $request->user()->id,
                'item_id' => $validated['item_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart.',
            'cart_count' => $this->cartCount($request),
            'cart_total' => $this->cartTotal($request),
            'cart_item' => $cartItem->fresh(),
        ]);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeCartAccess($request, $cartItem);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if (! $cartItem->item->isPurchasableOnline()) {
            return response()->json(['success' => false, 'message' => 'Item not available.'], 404);
        }

        if (! $cartItem->item->isVariation() && $cartItem->item->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Only ' . $cartItem->item->quantity . ' items available in stock.',
            ], 422);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated.',
            'cart_count' => $this->cartCount($request),
            'cart_total' => $this->cartTotal($request),
        ]);
    }

    public function remove(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeCartAccess($request, $cartItem);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => $this->cartCount($request),
            'cart_total' => $this->cartTotal($request),
        ]);
    }

    private function authorizeCartAccess(Request $request, CartItem $cartItem): void
    {
        if ($cartItem->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function cartCount(Request $request): int
    {
        return CartItem::forUser($request->user()->id)->sum('quantity');
    }

    private function cartTotal(Request $request): float
    {
        return round((float) CartItem::with('item')
            ->forUser($request->user()->id)
            ->get()
            ->sum(fn ($cartItem) => $cartItem->item->effectivePrice() * $cartItem->quantity), 2);
    }
}
