<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.item', 'deliveryLocation'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $items = collect($orders->items())->map(fn (Order $order) => $this->presentOrderSummary($order));

        return response()->json([
            'success' => true,
            'orders' => $items,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'This is not your order.'], 403);
        }

        $order->load(['items.item', 'payment', 'deliveryLocation', 'complaints']);

        $statusSteps = ['new', 'processing', 'paid', 'shipped', 'delivered'];
        $currentIndex = array_search($order->status, $statusSteps);
        $isCancelled = $order->status === 'cancelled';

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_email' => $order->customer_email,
                'delivery_method' => $order->delivery_method,
                'delivery_address' => $order->delivery_address,
                'delivery_location' => $order->deliveryLocation?->name,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'payment_reference' => $order->payment_reference,
                'delivery_fee' => (float) $order->delivery_fee,
                'is_bulk_order' => (bool) $order->is_bulk_order,
                'delivery_fee_status' => $order->delivery_fee_status,
                'status' => $order->status,
                'total_amount' => (float) $order->total_amount,
                'notes' => $order->notes,
                'created_at' => $order->created_at?->toISOString(),
                'status_steps' => $statusSteps,
                'current_status_index' => $currentIndex === false ? null : $currentIndex,
                'is_cancelled' => $isCancelled,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->item?->name,
                    'image' => $item->item?->image_url ? asset('storage/' . $item->item->image_url) : null,
                    'quantity' => (int) $item->quantity,
                    'price_at_order' => (float) $item->price_at_order,
                    'line_total' => round((float) $item->price_at_order * $item->quantity, 2),
                ])->values(),
                'payment' => $order->payment ? [
                    'gateway' => $order->payment->gateway,
                    'reference' => $order->payment->reference,
                    'amount' => (float) $order->payment->amount,
                    'status' => $order->payment->status,
                ] : null,
            ],
        ]);
    }

    public function storeComplaint(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'This is not your order.'], 403);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $complaint = OrderComplaint::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Complaint submitted successfully.',
            'complaint' => $this->presentComplaint($complaint->load('replies.user')),
        ], 201);
    }

    public function complaints(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'This is not your order.'], 403);
        }

        $complaints = $order->complaints()
            ->with('replies.user')
            ->latest()
            ->get()
            ->map(fn ($c) => $this->presentComplaint($c))
            ->values();

        return response()->json([
            'success' => true,
            'complaints' => $complaints,
        ]);
    }

    public function replyComplaint(Request $request, OrderComplaint $complaint): JsonResponse
    {
        if ($complaint->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'This is not your complaint.'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $complaint->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent.',
            'complaint' => $this->presentComplaint($complaint->fresh(['replies.user'])),
        ]);
    }

    private function presentOrderSummary(Order $order): array
    {
        $subtotal = $order->items->sum(fn ($item) => (float) $item->price_at_order * $item->quantity);

        return [
            'id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_amount' => (float) $order->total_amount,
            'delivery_method' => $order->delivery_method,
            'delivery_location' => $order->deliveryLocation?->name,
            'items_count' => $order->items->sum('quantity'),
            'subtotal' => round((float) $subtotal, 2),
            'created_at' => $order->created_at?->toISOString(),
            'item_names' => $order->items->take(3)->map(fn ($item) => $item->item?->name)->values(),
        ];
    }

    private function presentComplaint(OrderComplaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'order_id' => $complaint->order_id,
            'subject' => $complaint->subject,
            'message' => $complaint->message,
            'status' => $complaint->status,
            'created_at' => $complaint->created_at?->toISOString(),
            'replies' => $complaint->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'sender_name' => $reply->user?->name,
                'is_admin' => $reply->user && $reply->user->isAdminOrAgent(),
                'created_at' => $reply->created_at?->toISOString(),
            ])->values(),
        ];
    }
}
