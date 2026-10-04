<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\DeliverySetting;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\CustomerUpdatedOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['items.item', 'deliveryLocation'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('customer.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorizeOwnership($order);

        $order->load(['items.item', 'payment', 'deliveryLocation', 'complaints']);

        $statusSteps = ['new', 'processing', 'paid', 'shipped', 'delivered'];
        $currentIndex = array_search($order->status, $statusSteps);
        $isCancelled = $order->status === 'cancelled';

        return view('customer.orders.show', compact('order', 'statusSteps', 'currentIndex', 'isCancelled'));
    }

    /**
     * Form for amending an order the customer still owns the right to change.
     */
    public function edit(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);

        if (! $order->isEditableByCustomer()) {
            return redirect()
                ->route('customer.orders.show', $order)
                ->with('error', $order->customerEditLockReason());
        }

        $order->load(['items.item', 'deliveryLocation']);

        $deliveryLocations = DeliveryLocation::active()->orderBy('name')->get();
        $showFeeSelector = DeliverySetting::showDeliveryFeeSelector();

        return view('customer.orders.edit', [
            'order' => $order,
            'deliveryLocations' => $deliveryLocations,
            'showFeeSelector' => $showFeeSelector,
            'orderItems' => $this->linesForForm($order),
            'locationsJson' => $deliveryLocations->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'cost' => (float) $location->cost,
            ])->values(),
        ]);
    }

    /**
     * Current order lines shaped for the Alpine basket in the edit view.
     *
     * @return array<int, array<string, mixed>>
     */
    private function linesForForm(Order $order): array
    {
        return $order->items->map(fn (OrderItem $line) => [
            'item_id' => (int) $line->item_id,
            'name' => $line->item?->display_name ?? $line->item?->name ?? 'Item #'.$line->item_id,
            'price' => (float) $line->item?->effectivePrice(),
            'quantity' => (int) $line->quantity,
            'stock' => $line->item ? $line->item->effectiveQuantity() : 999,
            'is_bulky' => (bool) $line->item?->is_bulky,
            'old_qty' => (int) $line->quantity,
        ])->values()->all();
    }

    /**
     * Persist a customer amendment: swap quantities, add or remove products and
     * correct the delivery details, then re-price the order from live data.
     *
     * Prices are never taken from the request. Every line is re-costed from the
     * item's current effective price so a tampered form cannot set its own
     * total, and stock is re-checked because it may have moved since checkout.
     */
    public function update(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);

        if (! $order->isEditableByCustomer()) {
            return redirect()
                ->route('customer.orders.show', $order)
                ->with('error', $order->customerEditLockReason());
        }

        $showFeeSelector = DeliverySetting::showDeliveryFeeSelector();

        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'delivery_method' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_method,delivery|nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|distinct|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
        ];

        if ($showFeeSelector) {
            $rules['delivery_location_id'] = 'required_if:delivery_method,delivery|nullable|exists:delivery_locations,id';
        }

        $validator = Validator::make($request->all(), $rules, [
            'items.required' => 'Your order needs at least one item.',
            'items.min' => 'Your order needs at least one item.',
            'items.*.item_id.distinct' => 'The same product was listed more than once.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('order_edit_lines', $this->linesFromRequest($request, $order));
        }

        $validated = $validator->validated();

        $order->load('items');

        $requested = collect($validated['items'])
            ->mapWithKeys(fn (array $row) => [(int) $row['item_id'] => (int) $row['quantity']]);

        $items = Item::whereIn('id', $requested->keys())->get()->keyBy('id');

        // Reject anything we could not load before doing any work.
        if ($items->count() !== $requested->count()) {
            return $this->rejectEdit($request, $order, 'One of the products you selected is no longer available.');
        }

        // Stock can have moved since the order was placed, including for lines
        // the customer did not touch, so validate the whole basket.
        foreach ($requested as $itemId => $quantity) {
            /** @var Item $item */
            $item = $items->get($itemId);

            if (! $item->isPurchasableOnline()) {
                return $this->rejectEdit(
                    $request,
                    $order,
                    "\"{$item->display_name}\" is no longer available. Please remove it to continue."
                );
            }

            if ($quantity > $item->effectiveQuantity()) {
                return $this->rejectEdit(
                    $request,
                    $order,
                    "Only {$item->effectiveQuantity()} of \"{$item->display_name}\" remain in stock."
                );
            }
        }

        $isBulkOrder = $items->contains(fn (Item $item) => (bool) $item->is_bulky);

        $deliveryFee = 0;
        $deliveryLocationId = null;
        $deliveryFeeStatus = 'standard';

        if ($validated['delivery_method'] === 'delivery') {
            if ($showFeeSelector && ! empty($validated['delivery_location_id'])) {
                $location = DeliveryLocation::active()->findOrFail($validated['delivery_location_id']);

                if ($isBulkOrder) {
                    $deliveryFee = 0;
                    $deliveryFeeStatus = 'pending_agent';
                } else {
                    $deliveryFee = (float) $location->cost;
                    $deliveryFeeStatus = 'standard';
                }

                $deliveryLocationId = $location->id;
            } else {
                $deliveryFee = 0;
                $deliveryFeeStatus = 'pending_agent';
            }
        }

        // map() is given (value, key) by the collection, which is what we want
        // here; sum()'s callback receives (carry, item) instead.
        $subtotal = $requested
            ->map(fn (int $quantity, int $itemId) => $items->get($itemId)->effectivePrice() * $quantity)
            ->sum();

        $newTotal = round($subtotal + $deliveryFee, 2);

        $previousTotal = (float) $order->total_amount;
        $previousItemCount = (int) $order->items->sum('quantity');
        $previousItemIds = $order->items->pluck('item_id')->map(fn ($id) => (int) $id);

        $removedCount = $previousItemIds->diff($requested->keys())->count();
        $addedCount = $requested->keys()->diff($previousItemIds)->count();
        $newItemCount = $requested->sum();

        $changes = [];

        if ($addedCount > 0) {
            $changes[] = $addedCount === 1 ? '1 product added' : "{$addedCount} products added";
        }
        if ($removedCount > 0) {
            $changes[] = $removedCount === 1 ? '1 product removed' : "{$removedCount} products removed";
        }
        if ($newItemCount !== $previousItemCount) {
            $changes[] = "{$previousItemCount} to {$newItemCount} items";
        }
        if (abs($newTotal - $previousTotal) >= 0.01) {
            $changes[] = 'total ' . '₦' . number_format($newTotal, 2);
        }

        $attributes = [
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'] ?? null,
            'delivery_method' => $validated['delivery_method'],
            'delivery_address' => $validated['delivery_address'] ?? null,
            'delivery_location_id' => $deliveryLocationId,
            'delivery_fee' => $deliveryFee,
            'delivery_fee_status' => $deliveryFeeStatus,
            'is_bulk_order' => $isBulkOrder,
            'notes' => $validated['notes'] ?? null,
            'total_amount' => $newTotal,
        ];

        DB::transaction(function () use ($order, $requested, $items, $attributes) {
            $order->update($attributes);

            // Reconcile lines: update survivors, create additions, drop removals.
            $keptIds = [];

            foreach ($requested as $itemId => $quantity) {
                $keptIds[] = $itemId;

                $line = $order->items()->where('item_id', $itemId)->first();

                if ($line) {
                    $line->update([
                        'quantity' => $quantity,
                        'price_at_order' => $items->get($itemId)->effectivePrice(),
                    ]);
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'price_at_order' => $items->get($itemId)->effectivePrice(),
                    ]);
                }
            }

            $order->items()->whereNotIn('item_id', $keptIds)->delete();

            // Keep the payment record in step with the new total, otherwise a
            // verified bank transfer would still read against the old amount.
            $order->load('payment');

            if ($order->payment) {
                $order->payment->update(['amount' => $order->total_amount]);
            }
        });

        $this->alertStaffAboutUpdate($order, $changes);

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Your order has been updated.');
    }

    /**
     * Rebuild the Alpine basket from the submitted rows so a rejected save does
     * not silently reset quantities the customer already typed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function linesFromRequest(Request $request, Order $order): array
    {
        $submitted = collect($request->input('items', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['item_id']))
            ->mapWithKeys(fn (array $row) => [
                (int) $row['item_id'] => max(1, (int) ($row['quantity'] ?? 1)),
            ]);

        if ($submitted->isEmpty()) {
            return $this->linesForForm($order);
        }

        $items = Item::whereIn('id', $submitted->keys())->get()->keyBy('id');

        return $submitted->map(function (int $quantity, int $itemId) use ($items) {
            $item = $items->get($itemId);

            return [
                'item_id' => $itemId,
                'name' => $item?->display_name ?? $item?->name ?? 'Item #'.$itemId,
                'price' => (float) $item?->effectivePrice(),
                'quantity' => $quantity,
                'stock' => $item ? $item->effectiveQuantity() : 999,
                'is_bulky' => (bool) $item?->is_bulky,
                'old_qty' => null,
            ];
        })->values()->all();
    }

    /**
     * Bounce the customer back to the form with their basket intact.
     */
    private function rejectEdit(Request $request, Order $order, string $message)
    {
        return back()
            ->withInput()
            ->with('error', $message)
            ->with('order_edit_lines', $this->linesFromRequest($request, $order));
    }

    /**
     * A customer amending an order may invalidate work already under way, so
     * the staff who can action it get told what moved.
     */
    private function alertStaffAboutUpdate(Order $order, array $changes): void
    {
        try {
            $recipients = User::orderAlertRecipients();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new CustomerUpdatedOrder($order, $changes));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function authorizeOwnership(Order $order): void
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'This is not your order.');
        }
    }
}
