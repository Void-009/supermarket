<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderReceipt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    private array $validStatuses = ['new', 'processing', 'paid', 'shipped', 'delivered'];

    private array $nextStatus = [
        'new' => 'processing',
        'processing' => 'paid',
        'paid' => 'shipped',
        'shipped' => 'delivered',
    ];

    private array $prevStatus = [
        'delivered' => 'shipped',
        'shipped' => 'paid',
        'paid' => 'processing',
        'processing' => 'new',
    ];

    private function authorizeOrderAccess(Order $order): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isAgent()
            && ($order->processed_by === $user->id || $order->status === 'new')) {
            return;
        }

        abort(403, 'You do not have access to this order.');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items', 'processor', 'payment']);

        $user = auth()->user();
        if ($user->isAgent()) {
            $query->where(fn ($q) => $q->where('processed_by', $user->id)->orWhere('status', 'new'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('processor', fn ($p) => $p->where('name', 'like', "%{$search}%"));
                $id = preg_replace('/[^0-9]/', '', $search);
                if ($id !== '') {
                    $q->orWhere('id', (int) $id);
                }
            });
        }

        $orders = $query->latest()->paginate(15);

        $counts = [];
        foreach (array_merge($this->validStatuses, ['cancelled']) as $status) {
            $counts[$status] = Order::where('status', $status)->count();
        }

        return response()->json([
            'success' => true,
            'orders' => collect($orders->items())->map(fn (Order $order) => $this->presentOrder($order)),
            'counts' => $counts,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorizeOrderAccess($order);

        $order->load(['items.item', 'processor', 'payment']);

        return response()->json([
            'success' => true,
            'order' => $this->presentOrder($order),
            'next_status' => $this->nextStatus[$order->status] ?? null,
            'prev_status' => $this->prevStatus[$order->status] ?? null,
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'status' => 'required|in:processing,paid,shipped,delivered,cancelled',
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === 'cancelled') {
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                return response()->json(['success' => false, 'message' => 'Cannot cancel an already completed order.'], 422);
            }

            $oldStatus = $order->status;
            $order->update(['status' => 'cancelled']);

            if ($order->user) {
                $order->user->notify(new OrderStatusUpdated($order, $oldStatus, 'cancelled'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled.',
                'order' => $this->presentOrder($order->fresh(['items', 'processor', 'payment'])),
            ]);
        }

        $expectedNext = $this->nextStatus[$order->status] ?? null;
        if (! $expectedNext) {
            return response()->json(['success' => false, 'message' => 'Order has already reached its final status.'], 422);
        }

        if ($newStatus !== $expectedNext) {
            return response()->json([
                'success' => false,
                'message' => "Order must advance sequentially. Next step is: {$expectedNext}.",
            ], 422);
        }

        if (in_array($newStatus, ['paid', 'shipped'])
            && $order->delivery_method === 'delivery'
            && $order->delivery_fee_status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm the delivery fee before marking this order as paid or shipped.',
            ], 422);
        }

        if (in_array($newStatus, ['paid', 'shipped'])) {
            $unpicked = $order->items()->whereNull('picked_at')->count();
            if ($unpicked > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Please pick/verify all items before advancing. {$unpicked} item(s) still unpicked.",
                ], 422);
            }
        }

        if ($order->status === 'new' && ! $order->processed_by) {
            $order->update(['processed_by' => auth()->id()]);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);

        if ($order->user) {
            $order->user->notify(new OrderStatusUpdated($order, $oldStatus, $newStatus));
        }

        if ($newStatus === 'paid') {
            $payment = $order->payment;
            if ($payment) {
                $payment->update(['status' => 'success']);
            } else {
                Payment::create([
                    'order_id' => $order->id,
                    'gateway' => 'cash',
                    'reference' => $order->payment_reference,
                    'amount' => $order->total_amount,
                    'status' => 'success',
                ]);
            }

            if ($order->customer_email) {
                try {
                    Mail::to($order->customer_email)->send(new OrderReceipt($order));
                } catch (\Throwable $e) {
                    logger()->error("Failed to email receipt for order #{$order->id}: {$e->getMessage()}");
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Order status updated to {$newStatus}.",
            'order' => $this->presentOrder($order->fresh(['items', 'processor', 'payment'])),
        ]);
    }

    public function reverseStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderAccess($order);

        if (in_array($order->status, ['paid', 'shipped', 'delivered'])
            && ! auth()->user()->hasPermission('reverse_orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Only a superadmin or a user with the Reverse Paid Orders permission can reverse this order.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:new,processing,paid,shipped',
        ]);

        $newStatus = $validated['status'];
        $expectedPrev = $this->prevStatus[$order->status] ?? null;

        if (! $expectedPrev) {
            return response()->json(['success' => false, 'message' => 'Order cannot be reversed from its current status.'], 422);
        }

        if ($newStatus !== $expectedPrev) {
            return response()->json([
                'success' => false,
                'message' => "Order must reverse sequentially. Previous step is: {$expectedPrev}.",
            ], 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);

        if ($order->user) {
            $order->user->notify(new OrderStatusUpdated($order, $oldStatus, $newStatus));
        }

        return response()->json([
            'success' => true,
            'message' => "Order status reversed to {$newStatus}.",
            'order' => $this->presentOrder($order->fresh(['items', 'processor', 'payment'])),
        ]);
    }

    public function verifyPayment(Request $request, Order $order): JsonResponse
    {
        if ($order->payment_method !== 'bank_transfer') {
            return response()->json(['success' => false, 'message' => 'Not a bank transfer order.'], 404);
        }

        $validated = $request->validate([
            'payment_status' => 'required|in:pending,verified,failed,refunded',
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        $payment = $order->payment;
        if ($payment) {
            if ($validated['payment_status'] === 'verified') {
                $payment->update(['status' => 'success']);
            } elseif ($validated['payment_status'] === 'failed') {
                $payment->update(['status' => 'failed']);
            }
        } elseif ($validated['payment_status'] === 'verified') {
            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'cash',
                'reference' => $order->payment_reference,
                'amount' => $order->total_amount,
                'status' => 'success',
            ]);
        }

        if ($validated['payment_status'] === 'verified') {
            if ($order->status === 'new') {
                $oldStatus = $order->status;
                $order->update(['status' => 'processing']);
                if ($order->user) {
                    $order->user->notify(new OrderStatusUpdated($order, $oldStatus, 'processing'));
                }
                if (! $order->processed_by) {
                    $order->update(['processed_by' => auth()->id()]);
                }
            }
        } elseif ($validated['payment_status'] === 'failed') {
            if (! in_array($order->status, ['delivered', 'cancelled'])) {
                $order->update(['status' => 'cancelled']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated.',
            'order' => $this->presentOrder($order->fresh(['items', 'processor', 'payment'])),
        ]);
    }

    public function confirmDeliveryFee(Request $request, Order $order): JsonResponse
    {
        if ($order->delivery_method !== 'delivery') {
            return response()->json(['success' => false, 'message' => 'This order is not a delivery order.'], 422);
        }

        $rules = ['delivery_fee' => 'required|numeric|min:0'];

        if ($order->is_bulk_order) {
            $rules['delivery_type'] = 'required|in:motorcycle,truck';
        }

        $validated = $request->validate($rules);

        $newFee = (float) $validated['delivery_fee'];
        $subtotal = $order->total_amount - $order->delivery_fee;

        $order->update([
            'delivery_fee' => $newFee,
            'delivery_fee_status' => 'confirmed',
            'total_amount' => $subtotal + $newFee,
        ]);

        $payment = $order->payment;
        if ($payment) {
            $payment->update(['amount' => $order->total_amount]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery fee confirmed. Order total updated.',
            'order' => $this->presentOrder($order->fresh(['items', 'processor', 'payment'])),
        ]);
    }

    public function toggleItemPicked(Request $request, Order $order, OrderItem $orderItem): JsonResponse
    {
        $this->authorizeOrderAccess($order);

        if ((int) $orderItem->order_id !== (int) $order->id) {
            abort(404);
        }

        if ($orderItem->picked_at) {
            $orderItem->update(['picked_at' => null, 'picked_by' => null]);
            $picked = false;
        } else {
            $orderItem->update(['picked_at' => now(), 'picked_by' => auth()->id()]);
            $picked = true;
        }

        return response()->json([
            'success' => true,
            'picked' => $picked,
            'picked_count' => $order->items()->whereNotNull('picked_at')->count(),
            'total_count' => $order->items()->count(),
            'picked_at' => $orderItem->picked_at?->toDateTimeString(),
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,cancel',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($orders as $order) {
            if ($validated['action'] === 'delete') {
                $order->items()->delete();
                $order->payment()->delete();
                $order->delete();
                $count++;
            } elseif ($validated['action'] === 'cancel') {
                if (! in_array($order->status, ['delivered', 'cancelled'])) {
                    $order->update(['status' => 'cancelled']);
                    $count++;
                }
            }
        }

        $labels = ['delete' => 'deleted', 'cancel' => 'cancelled'];

        return response()->json([
            'success' => true,
            'message' => "{$count} order(s) {$labels[$validated['action']]} successfully.",
        ]);
    }

    private function presentOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'delivery_method' => $order->delivery_method,
            'delivery_address' => $order->delivery_address,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_reference' => $order->payment_reference,
            'delivery_fee' => (float) $order->delivery_fee,
            'is_bulk_order' => (bool) $order->is_bulk_order,
            'delivery_fee_status' => $order->delivery_fee_status,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'notes' => $order->notes,
            'processed_by' => $order->processor?->name,
            'user_id' => $order->user_id,
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'item_id' => $item->item_id,
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
        ];
    }
}
