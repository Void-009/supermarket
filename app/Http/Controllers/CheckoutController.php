<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\DeliveryLocation;
use App\Models\DeliverySetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentSettings;
use App\Models\BankAccount;
use App\Models\User;
use App\Events\OrderCreated;
use App\Notifications\NewOrderReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{

    public function index()
    {
        $cartItems = $this->getCartItems();
        $total = $cartItems->sum(function ($cartItem) {
            return $cartItem->item->effectivePrice() * $cartItem->quantity;
        });

        $paymentSettings = PaymentSettings::getActive();
        $bankAccounts = BankAccount::getActive();
        $deliveryLocations = DeliveryLocation::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $user = Auth::user();

        $totalQuantity = $cartItems->sum('quantity');
        $hasBulkyItem = $cartItems->contains(function ($cartItem) {
            return $cartItem->item->is_bulky;
        });
        $isBulkOrder = $hasBulkyItem;

        $deliverySettings = [
            'show_fee_selector' => DeliverySetting::showDeliveryFeeSelector(),
            'contact_message' => DeliverySetting::getContactAgentMessage(),
            'contact_phone' => DeliverySetting::getContactAgentPhone(),
            'contact_phones' => DeliverySetting::getContactAgentPhones(),
            'contact_whatsapp' => DeliverySetting::getContactAgentWhatsapp(),
            'contact_whatsapps' => DeliverySetting::getContactAgentWhatsapps(),
        ];

        return view('checkout.index', compact(
            'cartItems', 'total', 'paymentSettings', 'bankAccounts', 'deliveryLocations', 'user',
            'isBulkOrder', 'totalQuantity', 'deliverySettings'
        ));
    }

    public function placeOrder(Request $request)
    {
        $showFeeSelector = DeliverySetting::showDeliveryFeeSelector();

        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|nullable|string',
            'payment_method' => 'required|in:cash_on_delivery,bank_transfer',
            'bank_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($showFeeSelector) {
            $rules['delivery_location_id'] = 'required_if:delivery_method,delivery|nullable|exists:delivery_locations,id';
        }

        $validated = $request->validate($rules);

        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $deliveryFee = 0;
        $deliveryLocationId = null;
        $isBulkOrder = false;
        $deliveryFeeStatus = 'standard';

        $hasBulkyItem = $cartItems->contains(function ($cartItem) {
            return $cartItem->item->is_bulky;
        });
        $isBulkOrder = $hasBulkyItem;

        $showFeeSelector = DeliverySetting::showDeliveryFeeSelector();

        if ($validated['delivery_method'] === 'delivery' && !empty($validated['delivery_location_id'])) {
            $location = DeliveryLocation::active()->findOrFail($validated['delivery_location_id']);
            if ($isBulkOrder || !$showFeeSelector) {
                $deliveryFee = 0;
                $deliveryFeeStatus = 'pending_agent';
            } else {
                $deliveryFee = $location->cost;
                $deliveryFeeStatus = 'standard';
            }
            $deliveryLocationId = $location->id;
        } elseif ($validated['delivery_method'] === 'delivery' && !$showFeeSelector) {
            $deliveryFee = 0;
            $deliveryFeeStatus = 'pending_agent';
        }

        $subtotal = $cartItems->sum(function ($cartItem) {
            return $cartItem->item->effectivePrice() * $cartItem->quantity;
        });

        $order = DB::transaction(function () use ($request, $validated, $cartItems, $subtotal, $deliveryFee, $deliveryLocationId, $isBulkOrder, $deliveryFeeStatus) {
            $paymentReference = 'ORD-' . strtoupper(Str::random(10));

            $receiptPath = null;
            if ($request->hasFile('bank_receipt')) {
                $receiptPath = $request->file('bank_receipt')->store('receipts', 'local');
            }

            $paymentStatus = $validated['payment_method'] === 'bank_transfer' ? 'pending' : 'verified';

            $order = Order::create([
                'user_id' => Auth::id(),
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
                'bank_receipt_path' => $receiptPath,
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

        $this->clearCart();

        session()->push('guest_orders', $order->id);

        $this->alertStaffAboutNewOrder($order);

        try {
            broadcast(new OrderCreated($order));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_id' => $order->id,
            ]);
        }

        return redirect()->route('order.confirm', $order)->with('success', 'Order placed successfully!');
    }

    public function confirmation($id)
    {
        $order = Order::with(['items.item', 'payment', 'deliveryLocation'])->findOrFail($id);

        $isOwner = Auth::check() && $order->user_id === Auth::id();
        $isGuestOrder = in_array($order->id, session('guest_orders', []));

        if (!$isOwner && !$isGuestOrder) {
            abort(403);
        }

        $paymentSettings = PaymentSettings::getActive();

        return view('checkout.confirmation', compact('order', 'paymentSettings'));
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

    private function clearCart(): void
    {
        $query = CartItem::query();

        if (Auth::check()) {
            $query->forUser(Auth::id());
        } else {
            $query->forSession($this->getSessionId());
        }

        $query->delete();
    }

    /**
     * Push a browser notification to every staff member who can action orders,
     * so a new order is seen immediately instead of on the next dashboard visit.
     *
     * The notification is queued (QUEUE_CONNECTION=database), so it needs a
     * worker running. Failures here must never break a completed checkout, so
     * recipient resolution and dispatch are both guarded.
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

    private function getSessionId(): string
    {
        if (!session()->has('cart_session_id')) {
            session()->put('cart_session_id', session()->getId());
        }

        return session()->get('cart_session_id');
    }
}
