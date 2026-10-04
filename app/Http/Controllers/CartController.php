<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{

    public function index()
    {
        $cartItems = $this->getCartItems();

        $subtotal = $cartItems->sum(function ($cartItem) {
            return $cartItem->item->effectivePrice() * $cartItem->quantity;
        });

        $cartItemsJson = $cartItems->map(function ($cartItem) {
            return [
                'id' => $cartItem->id,
                'name' => $cartItem->item->name,
                'slug' => $cartItem->item->slug,
                'category' => $cartItem->item->category?->name,
                'image' => $cartItem->item->image_url ? asset('storage/' . $cartItem->item->image_url) : null,
                'price' => (float) $cartItem->item->effectivePrice(),
                'original_price' => $cartItem->item->isDiscounted() ? (float) $cartItem->item->selling_price : null,
                'quantity' => (int) $cartItem->quantity,
                'max' => $cartItem->item->isVariation() ? null : (int) $cartItem->item->quantity,
            ];
        })->values()->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return view('cart.index', compact('cartItems', 'subtotal', 'cartItemsJson'));
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);



        $item = Item::findOrFail($validated['item_id']);



        if (!$item->isPurchasableOnline()) {
            return response()->json(['success' => false, 'message' => 'Item not available.'], 404);
        }



        $existingQuantity = 0;
        $cartQuery = CartItem::query();
        if (Auth::check()) {
            $cartQuery->forUser(Auth::id());
        } else {
            $cartQuery->forSession($this->getSessionId());
        }
        if ($cartItem = $cartQuery->where('item_id', $validated['item_id'])->first()) {
            $existingQuantity = $cartItem->quantity;
        }

        // Variations have no real stock (the POS export always reports 0), so they
        // are sold without a quantity limit. Only stock-tracked parents are capped.
        if (!$item->isVariation() && $item->quantity < ($existingQuantity + $validated['quantity'])) {
            return response()->json(['success' => false, 'message' => 'Insufficient stock. Only ' . $item->quantity . ' items available.'], 422);
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $cartItem->quantity + $validated['quantity']]);
        } else {
            CartItem::create([
                'user_id' => Auth::check() ? Auth::id() : null,
                'session_id' => Auth::check() ? null : $this->getSessionId(),
                'item_id' => $validated['item_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart.',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::findOrFail($validated['item_id']);

        $this->authorizeCartAccess($cartItem);

        if (!$cartItem->item->isPurchasableOnline()) {
            return response()->json(['success' => false, 'message' => 'Item not available.'], 404);
        }

        if (!$cartItem->item->isVariation() && $cartItem->item->quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Only ' . $cartItem->item->quantity . ' items available in stock.',
            ], 422);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated.',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }



    public function remove($id): JsonResponse
    {
        $cartItem = CartItem::findOrFail($id);

        $this->authorizeCartAccess($cartItem);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => $this->getCartCount(),
            'cart_total' => $this->getCartTotal(),
        ]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->getCartCount(),
        ]);
    }

    private function getCartItems()
    {
        $query = CartItem::with('item');

        if (Auth::check()) {
            $query->forUser(Auth::id());
        } else {
            $query->forSession($this->getSessionId());
        }

        return $query->get();
    }

    private function getCartCount(): int
    {
        return (int) $this->getCartItems()->sum('quantity');
    }

    private function getCartTotal(): float
    {
        return $this->getCartItems()->sum(function ($cartItem) {
            return $cartItem->item->effectivePrice() * $cartItem->quantity;
        });
    }

    private function getSessionId(): string
    {
        if (!session()->has('cart_session_id')) {
            session()->put('cart_session_id', session()->getId());
        }

        return session()->get('cart_session_id');
    }

    private function authorizeCartAccess(CartItem $cartItem): void
    {
        if (Auth::check()) {
            if ($cartItem->user_id !== Auth::id()) {
                abort(403);
            }
        } else {
            if ($cartItem->session_id !== $this->getSessionId()) {
                abort(403);
            }
        }
    }
}

