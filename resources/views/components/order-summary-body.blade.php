{{--
    Shared, read-only order summary body.

    Expects from the including scope:
      $cartItems (Collection), $total, $isBulkOrder (bool), $deliverySettings (array)

    Must be rendered INSIDE the element carrying x-data="checkoutPage()" because it
    binds to Alpine state: grandTotal, deliveryMethod, deliveryFee, selectedLocationName.
--}}

<div class="space-y-4 mb-6 max-h-64 overflow-y-auto overscroll-contain pr-1">
    @foreach($cartItems as $cartItem)
        <div class="flex items-center gap-3">
            <div class="shrink-0 w-12 h-12 bg-gray-50 rounded-lg overflow-hidden border border-gray-100">
                @if($cartItem->item->image_url)
                    <img src="{{ asset('storage/' . $cartItem->item->image_url) }}" alt="{{ $cartItem->item->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                        <i data-lucide="image" class="w-4 h-4 text-emerald-300"></i>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ $cartItem->item->name }}</p>
                <p class="text-xs text-gray-400">{{ __('messages.quantity') }}: {{ $cartItem->quantity }}</p>
            </div>
            <span class="text-sm font-semibold text-gray-900 shrink-0">₦{{ number_format($cartItem->item->effectivePrice() * $cartItem->quantity, 2) }}</span>
        </div>
    @endforeach
</div>

<div class="border-t border-gray-100 pt-4 space-y-3">
    <div class="flex items-center justify-between text-sm">
        <span class="text-gray-500">{{ __('messages.subtotal') }}</span>
        <span class="font-medium text-gray-900">₦{{ number_format($total, 2) }}</span>
    </div>
    <div class="flex items-center justify-between text-sm">
        <span class="text-gray-500">{{ __('messages.delivery_method') }}<span x-show="selectedLocationName" x-cloak class="text-xs" x-text="' (' + selectedLocationName + ')'"></span></span>
        @if($isBulkOrder || !$deliverySettings['show_fee_selector'])
            <span class="font-medium text-blue-600" x-show="deliveryMethod === 'delivery'">{{ __('messages.contact_agent') }}</span>
            <span class="font-medium text-green-600" x-show="deliveryMethod !== 'delivery'">{{ __('messages.free') }}</span>
        @else
            <span class="font-medium" :class="deliveryMethod === 'delivery' && deliveryFee > 0 ? 'text-gray-900' : 'text-green-600'" x-text="deliveryMethod === 'delivery' && deliveryFee > 0 ? '₦' + formatNumber(deliveryFee) : '{{ __('messages.free') }}'"></span>
        @endif
    </div>
</div>

<div class="border-t border-gray-100 mt-4 pt-4">
    <div class="flex items-center justify-between">
        <span class="text-base font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.total') }}</span>
        <span class="text-xl font-extrabold text-emerald-600" x-text="'₦' + formatNumber(grandTotal)"></span>
    </div>
</div>
