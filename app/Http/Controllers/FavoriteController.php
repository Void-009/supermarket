<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Item $item): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $existing = Favorite::where('user_id', $user->id)->where('item_id', $item->id)->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            Favorite::create(['user_id' => $user->id, 'item_id' => $item->id]);
            $favorited = true;
        }

        return response()->json([
            'success' => true,
            'favorited' => $favorited,
        ]);
    }

    public function toggleAdminFavorite(Request $request, Item $item): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdminOrAgent()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $item->update(['is_admin_favorite' => !$item->is_admin_favorite]);

        return response()->json([
            'success' => true,
            'is_admin_favorite' => $item->fresh()->is_admin_favorite,
        ]);
    }
}
