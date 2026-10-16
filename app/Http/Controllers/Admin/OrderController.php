<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderReceipt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Notifications\OrderStatusUpdated;
use App\Services\BarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        'cancelled' => 'new',
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

    public function index(Request $request)
    {
        $query = Order::with(['items', 'processor', 'payment']);

        $user = auth()->user();
        if ($user->isAgent()) {
            $query->where(function ($q) use ($user) {
                $q->where('processed_by', $user->id)
                  ->orWhere('status', 'new');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        $orders = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'new' => Order::where('status', 'new')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'paid' => Order::where('status', 'paid')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];
        $totalAll = Order::count();

        return view('admin.orders.index', compact('orders', 'counts', 'totalAll'));
    }

    public function show(Order $order)
    {
        $this->authorizeOrderAccess($order);

        $order->load(['items.item', 'processor', 'payment']);

        $next = null;
        if (isset($this->nextStatus[$order->status])) {
            $next = $this->nextStatus[$order->status];
        }

        $prev = null;
        if (isset($this->prevStatus[$order->status])) {
            $prev = $this->prevStatus[$order->status];
        }

        return view('admin.orders.show', compact('order', 'next', 'prev'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'status' => 'required|in:processing,paid,shipped,delivered,cancelled',
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === 'cancelled') {
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                return redirect()->back()->with('error', 'Cannot cancel an already completed order.');
            }

            $oldStatus = $order->status;
            $order->update(['status' => 'cancelled']);

            if ($order->user) {
                $order->user->notify(new OrderStatusUpdated($order, $oldStatus, 'cancelled'));
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Order cancelled.', 'order' => $order]);
            }
            return redirect()->back()->with('success', 'Order cancelled.');
        }

        $expectedNext = $this->nextStatus[$order->status] ?? null;

        if (!$expectedNext) {
            return redirect()->back()->with('error', 'Order has already reached its final status.');
        }

        if ($newStatus !== $expectedNext) {
            return redirect()->back()->with('error', "Order must advance sequentially. Next step is: {$expectedNext}.");
        }

        if (in_array($newStatus, ['paid', 'shipped'])
            && $order->delivery_method === 'delivery'
            && $order->delivery_fee_status !== 'confirmed') {
            $message = 'Please confirm the delivery fee before marking this order as paid or shipped.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        if (in_array($newStatus, ['paid', 'shipped'])) {
            $unpicked = $order->items()->whereNull('picked_at')->count();
            if ($unpicked > 0) {
                $message = "Please pick/verify all items before advancing. {$unpicked} item(s) still unpicked.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->back()->with('error', $message);
            }
        }

        if ($order->status === 'new' && !$order->processed_by) {
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$newStatus}.",
                'order' => $order,
            ]);
        }

        return redirect()->back()->with('success', "Order status updated to {$newStatus}.");
    }

    public function reverseStatus(Request $request, Order $order)
    {
        $this->authorizeOrderAccess($order);

        if (in_array($order->status, ['paid', 'shipped', 'delivered'])
            && !auth()->user()->hasPermission('reverse_orders')) {
            abort(403, 'Only a superadmin or a user with the Reverse Paid Orders permission can reverse this order.');
        }

        if ($order->status === 'cancelled'
            && !auth()->user()->hasPermission('restore_cancelled_orders')) {
            abort(403, 'Only a superadmin or a user with the Restore Cancelled Orders permission can restore this order.');
        }

        $validated = $request->validate([
            'status' => 'required|in:new,processing,paid,shipped',
        ]);

        $newStatus = $validated['status'];
        $expectedPrev = $this->prevStatus[$order->status] ?? null;

        if (!$expectedPrev) {
            return redirect()->back()->with('error', 'Order cannot be reversed from its current status.');
        }

        if ($newStatus !== $expectedPrev) {
            return redirect()->back()->with('error', "Order must reverse sequentially. Previous step is: {$expectedPrev}.");
        }

        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);

        if ($order->user) {
            $order->user->notify(new OrderStatusUpdated($order, $oldStatus, $newStatus));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Order status reversed to {$newStatus}.",
                'order' => $order,
            ]);
        }

        return redirect()->back()->with('success', "Order status reversed to {$newStatus}.");
    }

    public function verifyPayment(Request $request, Order $order)
    {
        if ($order->payment_method !== 'bank_transfer') {
            abort(404);
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
                if (!$order->processed_by) {
                    $order->update(['processed_by' => auth()->id()]);
                }
            }
        } elseif ($validated['payment_status'] === 'failed') {
            if (!in_array($order->status, ['delivered', 'cancelled'])) {
                $order->update(['status' => 'cancelled']);
            }
        }

        return redirect()->back()->with('success', 'Payment status updated.');
    }

    public function receipt(Order $order)
    {
        $this->authorizeOrderAccess($order);

        $order->load(['items.item', 'payment']);

        $barcodeService = app(BarcodeService::class);
        $barcodeString = $barcodeService->generateBarcodeString($order);
        $barcodeImage = $barcodeService->generateQrCodeImage($order);
        $barcodeBase64 = base64_encode($barcodeImage);

        return view('admin.orders.receipt', compact('order', 'barcodeString', 'barcodeBase64'));
    }

    public function viewBankReceipt(Order $order)
    {
        $this->authorizeOrderAccess($order);

        if (!$order->bank_receipt_path) {
            abort(404, 'No bank receipt uploaded for this order.');
        }

        if (!Storage::disk('local')->exists($order->bank_receipt_path)) {
            abort(404, 'Bank receipt file not found.');
        }

        return Storage::disk('local')->response($order->bank_receipt_path);
    }

    public function downloadReceipt(Order $order)
    {
        $order->load(['items.item', 'payment']);

        $barcodeService = app(BarcodeService::class);
        $barcodeString = $barcodeService->generateBarcodeString($order);
        $barcodeImage = $barcodeService->generateQrCodeImage($order);
        $barcodeBase64 = base64_encode($barcodeImage);

        $pdf = Pdf::loadView('admin.orders.receipt', compact('order', 'barcodeString', 'barcodeBase64'));

        return $pdf->download("receipt-order-{$order->id}.pdf");
    }

    public function emailReceipt(Request $request, Order $order)
    {
        if (!$order->customer_email) {
            return redirect()->back()->with('error', 'Customer has no email address on file.');
        }

        try {
            Mail::to($order->customer_email)->send(new OrderReceipt($order));
            return redirect()->back()->with('success', 'Receipt emailed to customer successfully.');
        } catch (\Throwable $e) {
            logger()->error("Failed to email receipt for order #{$order->id}: {$e->getMessage()}");
            return redirect()->back()->with('error', 'Failed to send email. Please try again.');
        }
    }

    public function bulk(Request $request)
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
                if (!in_array($order->status, ['delivered', 'cancelled'])) {
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

    public function toggleItemPicked(Request $request, Order $order, OrderItem $orderItem)
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

        $pickedCount = $order->items()->whereNotNull('picked_at')->count();
        $totalCount = $order->items()->count();

        return response()->json([
            'success' => true,
            'picked' => $picked,
            'picked_count' => $pickedCount,
            'total_count' => $totalCount,
            'picked_at' => $orderItem->picked_at?->toDateTimeString(),
        ]);
    }

    public function confirmDeliveryFee(Request $request, Order $order)
    {
        if ($order->delivery_method !== 'delivery') {
            return response()->json(['success' => false, 'message' => 'This order is not a delivery order.'], 422);
        }

        $rules = [
            'delivery_fee' => 'required|numeric|min:0',
        ];

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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Delivery fee confirmed. Order total updated.',
                'order' => $order,
            ]);
        }

        return redirect()->back()->with('success', 'Delivery fee confirmed.');
    }
}
