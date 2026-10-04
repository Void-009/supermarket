<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\CartItem;
use App\Models\DeliveryLocation;
use App\Models\DeliverySetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentSettings;
use App\Models\User;
use App\Events\OrderCreated;
use App\Notifications\NewOrderReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cartItems = CartItem::with('item.category')
            ->forUser($request->user()->id)
            ->get();

        $total = $cartItems->sum(fn ($cartItem) => $cartItem->item->effectivePrice() * $cartItem->quantity);
        $totalQuantity = $cartItems->sum('quantity');
        $isBulkOrder = $cartItems->contains(fn ($cartItem) => $cartItem->item->is_bulky);

        return response()->json([
            'success' => true,
            'cart' => [
                'items_count' => $cartItems->count(),
                'total_quantity' => $totalQuantity,
                'subtotal' => round((float) $total, 2),
                'is_bulk_order' => $isBulkOrder,
            ],
            'delivery_settings' => [
                'show_fee_selector' => DeliverySetting::showDeliveryFeeSelector(),
                'contact_message' => DeliverySetting::getContactAgentMessage(),
                'contact_phones' => DeliverySetting::getContactAgentPhones(),
                'contact_whatsapps' => DeliverySetting::getContactAgentWhatsapps(),
            ],
            'delivery_locations' => DeliveryLocation::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'cost', 'motorcycle_rate', 'truck_rate', 'description']),
            'payment_settings' => PaymentSettings::getActive(),
            'bank_accounts' => BankAccount::getActive(),
            'user' => [
                'name' => $request->user()->name,
                'phone' => $request->user()->phone,
                'email' => $request->user()->email,
                'address' => $request->user()->address,
            ],
        ]);
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $showFeeSelector = DeliverySetting::showDeliveryFeeSelector();

        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|nullable|string',
            'payment_method' => 'required|in:cash_on_delivery,bank_transfer',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($showFeeSelector) {
            $rules['delivery_location_id'] = 'required_if:delivery_method,delivery|nullable|exists:delivery_locations,id';
        }

        $validated = $request->validate($rules);

        $cartItems = CartItem::with('item')
            ->forUser($request->user()->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your cart is empty.'], 422);
        }

        foreach ($cartItems as $cartItem) {
            if (! $cartItem->item->isPurchasableOnline()) {
                return response()->json([
                    'success' => false,
                    'message' => "Item '{$cartItem->item->name}' is no longer available.",
                ], 422);
            }
        }

        $isBulkOrder = $cartItems->contains(fn ($cartItem) => $cartItem->item->is_bulky);
        $deliveryFee = 0;
        $deliveryLocationId = null;
        $deliveryFeeStatus = 'standard';

        if ($validated['delivery_method'] === 'delivery' && ! empty($validated['delivery_location_id'])) {
            $location = DeliveryLocation::active()->findOrFail($validated['delivery_location_id']);
            if ($isBulkOrder || ! $showFeeSelector) {
                $deliveryFee = 0;
                $deliveryFeeStatus = 'pending_agent';
            } else {
                $deliveryFee = $location->cost;
                $deliveryFeeStatus = 'standard';
            }
            $deliveryLocationId = $location->id;
        } elseif ($validated['delivery_method'] === 'delivery' && ! $showFeeSelector) {
            $deliveryFee = 0;
            $deliveryFeeStatus = 'pending_agent';
        }

        $subtotal = $cartItems->sum(fn ($cartItem) => $cartItem->item->effectivePrice() * $cartItem->quantity);

        $order = DB::transaction(function () use ($request, $validated, $cartItems, $subtotal, $deliveryFee, $deliveryLocationId, $isBulkOrder, $deliveryFeeStatus) {
            $paymentReference = 'ORD-' . strtoupper(Str::random(10));
            $paymentStatus = $validated['payment_method'] === 'bank_transfer' ? 'pending' : 'verified';

            $order = Order::create([
                'user_id' => $request->user()->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'delivery_method' => $validated['delivery_method'],
                'delivery_address' => $validated['delivery_address'] ?? null,
                'delivery_location_id' => $deliveryLocationId,
                'payment_method' => $validated['payment_method'],
                'delivery_fee' => $deliveryFee,
                'is_bulk_order' => $isBulkOrder,
                'delivery_fee_status' => $deliveryFeeStatus,
                'status' => 'new',
                'total_amount' => $subtotal + $deliveryFee,
                'payment_status' => $paymentStatus,
                'payment_reference' => $paymentReference,
                'notes' => $validated['notes'] ?? null,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'gateway' => $validated['payment_method'],
                'reference' => $paymentReference,
                'amount' => $order->total_amount,
                'status' => $paymentStatus === 'verified' ? 'success' : 'pending',
            ]);

            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $cartItem->item_id,
                    'quantity' => $cartItem->quantity,
                    'price_at_order' => $cartItem->item->effectivePrice(),
                ]);
            }

            return $order;
        });

        CartItem::forUser($request->user()->id)->delete();

        $this->alertStaffAboutNewOrder($order);

        try {
            broadcast(new OrderCreated($order));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
        ], 201);
    }

    /**
     * Push a browser notification to every staff member who can action orders.
     * Queued, so it needs a worker running; guarded so a notification problem
     * can never fail an order that was already persisted.
     */
    private function alertStaffAboutNewOrder(Order $order): void
    {
        try {
            $recipients = User::orderAlertRecipients();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new NewOrderReceived($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
