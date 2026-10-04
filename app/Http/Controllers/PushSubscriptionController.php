<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function publicKey(): JsonResponse
    {
        return response()->json([
            'public_key' => config('services.vapid.public_key'),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'auth_key' => 'required|string',
            'p256dh_key' => 'required|string',
        ]);

        $existing = PushSubscription::where('endpoint', $validated['endpoint'])->first();

        if ($existing) {
            $existing->update([
                'auth_key' => $validated['auth_key'],
                'p256dh_key' => $validated['p256dh_key'],
                'user_id' => $request->user()->id,
            ]);

            return response()->json(['success' => true, 'message' => 'Subscription updated']);
        }

        PushSubscription::create([
            'user_id' => $request->user()->id,
            'endpoint' => $validated['endpoint'],
            'auth_key' => $validated['auth_key'],
            'p256dh_key' => $validated['p256dh_key'],
        ]);

        return response()->json(['success' => true, 'message' => 'Subscribed successfully']);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Unsubscribed successfully']);
    }
}
