@extends('layouts.main')
@section('title', 'Edit Order #' . $order->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ route('customer.orders.show', $order) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('messages.back_to_my_orders') }}
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        @include('components.alert', ['type' => 'success', 'message' => session('success')])
    @endif
    @if(session('error'))
        @include('components.alert', ['type' => 'error', 'message' => session('error')])
    @endif
    @if($errors->any())
        @include('components.alert', ['type' => 'error', 'message' => $errors->first()])
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h1 class="text-2xl font-extrabold text-gray-900" style="font-family:'Poppins',sans-serif;">
            {{ __('messages.edit') }} {{ __('messages.order') }} #{{ $order->id }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            You can change your items and delivery details until this order is paid for or shipped.
        </p>
    </div>

    {{-- Prices and stock are re-read from the server when this form is saved, so
         the figures below are for feedback only. --}}
    <form method="POST" action="{{ route('customer.orders.update', $order) }}"
          x-data="orderEdit(@js($orderItems), @js($locationsJson))"
          x-init="init()">
        @csrf
        @method('PUT')

        {{-- Items --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">{{ __('messages.items') }}</h2>

            {{-- Add products search (moved to top) --}}
            <div class="mb-5 pb-5 border-b border-gray-100">
                <label for="item-search" class="block text-sm font-semibold text-gray-900 mb-2">
                    {{ __('messages.add_items') }}
                </label>

                <div class="relative">
                    <input type="search" id="item-search" x-model="query" @input.debounce.350ms="search()"
                           @focus="search()"
                           placeholder="{{ __('messages.search_to_add_items') }}"
                           class="w-full px-4 py-3 pl-10 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>

                <div x-show="searching" x-cloak class="mt-3 text-sm text-gray-500">
                    {{ __('messages.searching') }}&hellip;
                </div>

                <div x-show="searchError" x-cloak class="mt-3 text-sm text-red-600" x-text="searchError"></div>

                <div x-show="results.length > 0" x-cloak class="mt-3 space-y-2 max-h-72 overflow-y-auto">
                    <template x-for="result in results" :key="result.id">
                        <div class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50"
                             @click="add(result)"
                             :class="{ 'opacity-50': result.stock < 1 }">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 truncate" x-text="result.display_name"></p>
                                <p class="text-xs text-gray-500">
                                    <span x-text="money(result.price)"></span>
                                    <span class="text-gray-400">&middot;</span>
                                    <span x-text="result.stock > 0
                                        ? `${result.stock} {{ __('messages.in_stock') }}`
                                        : `@js(__('messages.out_of_stock'))`"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="searched && results.length === 0 && !searching" x-cloak
                     class="mt-3 text-sm text-gray-500">
                    {{ __('messages.no_items_found') }}
                </div>
            </div>

            {{-- Validation summary for the item rows --}}
            <div x-show="lines.length === 0" x-cloak
                 class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                {{ __('messages.order_needs_an_item') }}
            </div>

            <div class="space-y-3">
                <template x-for="(line, index) in lines" :key="line.item_id">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-xl">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="line.name"></p>
                            <p class="text-xs text-gray-500">
                                <template x-if="line.old_qty && line.old_qty !== line.quantity">
                                    <span x-text="`@js(__('messages.qty_was')) ${line.old_qty} &middot; `"></span>
                                </template>
                                <span x-text="money(line.price)"></span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="decrement(index)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-40"
                                    :disabled="line.quantity <= 1" aria-label="{{ __('messages.decrease') }}">
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>

                            <input type="number" min="1" max="999"
                                   x-model.number="line.quantity"
                                   @change="clamp(index)"
                                   :name="`items[${index}][quantity]`"
                                   class="w-16 px-2 py-1.5 text-center text-sm font-semibold bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                   :aria-label="__('messages.quantity')">

                            <button type="button" @click="increment(index)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-40"
                                    :disabled="line.quantity >= line.stock" aria-label="{{ __('messages.increase') }}">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>

                            <button type="button" @click="remove(index)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 ml-1"
                                    aria-label="{{ __('messages.remove') }}">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>

                        {{-- Server needs the item id paired with each quantity. --}}
                        <input type="hidden" :name="`items[${index}][item_id]`" :value="line.item_id">
                    </div>
                </template>
            </div>
        </div>

        {{-- Delivery details --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">
                {{ __('messages.delivery_details') }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="customer_name" class="block text-sm font-semibold text-gray-900 mb-2">{{ __('messages.full_name') }}</label>
                    <input type="text" id="customer_name" name="customer_name" required
                           value="{{ old('customer_name', $order->customer_name) }}"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div>
                    <label for="customer_phone" class="block text-sm font-semibold text-gray-900 mb-2">{{ __('messages.phone_number') }}</label>
                    <input type="tel" id="customer_phone" name="customer_phone" required
                           value="{{ old('customer_phone', $order->customer_phone) }}"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <div class="sm:col-span-2">
                    <label for="customer_email" class="block text-sm font-semibold text-gray-900 mb-2">{{ __('messages.email') }}</label>
                    <input type="email" id="customer_email" name="customer_email"
                           value="{{ old('customer_email', $order->customer_email) }}"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>
            </div>

            <div class="mt-4">
                <span class="block text-sm font-semibold text-gray-900 mb-2">{{ __('messages.delivery_method') }}</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/40">
                        <input type="radio" name="delivery_method" value="pickup"
                               x-model="deliveryMethod"
                               @checked(old('delivery_method', $order->delivery_method) === 'pickup')
                               class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-gray-900">{{ __('messages.pickup') }}</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/40">
                        <input type="radio" name="delivery_method" value="delivery"
                               x-model="deliveryMethod"
                               @checked(old('delivery_method', $order->delivery_method) === 'delivery')
                               class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-gray-900">{{ __('messages.delivery') }}</span>
                    </label>
                </div>
            </div>

            @if($showFeeSelector)
                <div class="mt-4" x-show="deliveryMethod === 'delivery'" x-cloak>
                    <label for="delivery_location_id" class="block text-sm font-semibold text-gray-900 mb-2">
                        {{ __('messages.delivery_location') }}
                    </label>
                    <select id="delivery_location_id" name="delivery_location_id" x-model="locationId"
                            :required="deliveryMethod === 'delivery'"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        <option value="">{{ __('messages.select_location') }}</option>
                        @foreach($deliveryLocations as $location)
                            <option value="{{ $location->id }}" @selected(old('delivery_location_id', $order->delivery_location_id) == $location->id)>
                                {{ $location->name }} &mdash; &#8358;{{ number_format((float) $location->cost, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mt-4" x-show="deliveryMethod === 'delivery'" x-cloak>
                <label for="delivery_address" class="block text-sm font-semibold text-gray-900 mb-2">
                    {{ __('messages.delivery_address') }}
                </label>
                <textarea id="delivery_address" name="delivery_address" rows="3"
                          x-model="address"
                          :required="deliveryMethod === 'delivery'"
                          class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none"
                          placeholder="{{ __('messages.street_address_placeholder') }}">{{ old('delivery_address', $order->delivery_address) }}</textarea>
            </div>

            <div class="mt-4">
                <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">
                    {{ __('messages.special_instructions') }}
                </label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none"
                          placeholder="{{ __('messages.special_instructions') }}">{{ old('notes', $order->notes) }}</textarea>
            </div>
        </div>

        {{-- Summary + submit. The figures here are estimates for feedback; the
             server recomputes every price before saving. --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4" style="font-family:'Poppins',sans-serif;">
                {{ __('messages.order_summary') }}
            </h2>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>{{ __('messages.subtotal') }}</span>
                    <span x-text="money(subtotal)"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>{{ __('messages.delivery_fee') }}</span>
                    <span x-text="deliveryFeeLabel()"></span>
                </div>
                <div class="flex justify-between text-lg font-extrabold text-gray-900 pt-3 border-t border-gray-100">
                    <span>{{ __('messages.total') }}</span>
                    <span x-text="money(total)"></span>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                {{ __('messages.prices_rechecked_on_save') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('customer.orders.show', $order) }}"
               class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 rounded-xl text-sm font-semibold text-white transition-colors shadow-sm">
                <i data-lucide="check" class="w-4 h-4"></i> {{ __('messages.save_changes') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});

function orderEdit(initialLines, locations) {
    return {
        lines: initialLines,
        locations: locations,
        query: '',
        results: [],
        searching: false,
        searched: false,
        searchError: '',

        deliveryMethod: '{{ old('delivery_method', $order->delivery_method) }}',
        locationId: '{{ old('delivery_location_id', $order->delivery_location_id) }}' || '',
        address: @json(old('delivery_address', $order->delivery_address)),

        init() {
            // Re-add anything the server rejected so a failed save loses no input.
            var restored = @json(session('order_edit_lines'));
            if (Array.isArray(restored) && restored.length > 0) {
                this.lines = restored;
                this.$nextTick(function () {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        get subtotal() {
            return this.lines.reduce((sum, line) => sum + (line.price * line.quantity), 0);
        },

        isBulk() {
            return this.lines.some(function (line) { return line.is_bulky; });
        },

        get deliveryFee() {
            if (this.deliveryMethod !== 'delivery') return 0;

            if (this.isBulk()) return null; // quoted by the agent

            if (!@js($showFeeSelector)) return null;

            var location = this.locations.find(function (l) {
                return String(l.id) === String(this.locationId);
            }, this);

            return location ? location.cost : null;
        },

        get total() {
            var fee = this.deliveryFee;
            return this.subtotal + (fee === null ? 0 : fee);
        },

        deliveryFeeLabel() {
            var fee = this.deliveryFee;

            if (fee === null) {
                return @json(__('messages.quoted_later'));
            }

            return this.money(fee);
        },

        money(value) {
            return '\u20A6' + Number(value || 0).toLocaleString('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        clamp(index) {
            var line = this.lines[index];
            var qty = parseInt(line.quantity, 10);

            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > 999) qty = 999;
            if (line.stock && qty > line.stock) qty = line.stock;

            line.quantity = qty;
        },

        increment(index) {
            this.lines[index].quantity = Math.min(
                (this.lines[index].quantity || 0) + 1,
                this.lines[index].stock || 999
            );
        },

        decrement(index) {
            this.lines[index].quantity = Math.max((this.lines[index].quantity || 1) - 1, 1);
        },

        remove(index) {
            this.lines.splice(index, 1);
        },

        add(result) {
            if (result.stock < 1) return;

            var existing = this.lines.find(function (line) {
                return line.item_id === result.id;
            });

            if (existing) {
                existing.quantity = Math.min(existing.quantity + 1, result.stock);
            } else {
                this.lines.push({
                    item_id: result.id,
                    name: result.display_name || result.name,
                    price: result.price,
                    quantity: 1,
                    stock: result.stock,
                    is_bulky: !!result.is_bulky,
                    old_qty: null
                });
            }

            // Clear search after adding
            this.query = '';
            this.results = [];
            this.searched = false;

            // Newly added rows contain raw data-lucide icons; re-render them.
            this.$nextTick(function () {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        search() {
            var self = this;
            var term = this.query.trim();

            if (term.length < 2) {
                this.results = [];
                this.searched = false;
                this.searchError = '';
                return;
            }

            this.searching = true;
            this.searchError = '';

            fetch('/api/search?query=' + encodeURIComponent(term), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    var inOrder = {};
                    self.lines.forEach(function (line) { inOrder[line.item_id] = true; });

                    self.results = (data.items || []).map(function (item) {
                        return {
                            id: item.id,
                            name: item.name,
                            display_name: item.display_name || item.name,
                            price: item.discount_price || item.selling_price,
                            stock: item.stock || 0,
                            is_bulky: !!item.is_bulky
                        };
                    }).filter(function (item) {
                        return !inOrder[item.id];
                    });

                    self.searched = true;
                })
                .catch(function () {
                    self.searchError = @json(__('messages.search_failed'));
                    self.results = [];
                    self.searched = true;
                })
                .finally(function () {
                    self.searching = false;
                });
        }
    };
}
</script>
@endpush
@endsection

