@extends('layouts.main')
@section('title', __('messages.shopping_cart'))

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12" x-data="cartPage()">

    {{-- Page Header --}}
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition-colors">{{ __('messages.home') }}</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-gray-900 font-medium">{{ __('messages.cart') }}</span>
        </nav>
        <h1 class="text-3xl lg:text-4xl font-extrabold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.shopping_cart') }}</h1>
        <p class="mt-2 text-gray-500">
            <span x-text="itemCount + ' ' + (itemCount === 1 ? itemLabel : itemsLabel)"></span> {{ __('messages.in_your_cart') }}
        </p>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
        @include('components.alert', ['type' => 'error', 'message' => session('error')])
    @endif

    @if(session('success'))
        @include('components.alert', ['type' => 'success', 'message' => session('success')])
    @endif

    @if($cartItems->isEmpty())

        {{-- Empty Cart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 lg:p-20 text-center" x-show="items.length === 0">
            <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i data-lucide="shopping-cart" class="w-12 h-12 text-gray-300"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">{{ __('messages.cart_empty') }}</h2>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">{{ __('messages.cart_empty_desc') }}</p>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl transition-colors shadow-lg shadow-emerald-500/25">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                {{ __('messages.start_shopping') }}
            </a>
        </div>

    @else

        <div class="flex flex-col lg:flex-row gap-8" x-show="items.length > 0">

            {{-- Left: Cart Items --}}
            <div class="flex-1 lg:w-[65%]">

                {{-- Desktop Table --}}
                <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-6 py-4">{{ __('messages.product') }}</th>
                                <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-4">{{ __('messages.price') }}</th>
                                <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-4">{{ __('messages.quantity') }}</th>
                                <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider px-4 py-4">{{ __('messages.total') }}</th>
                                <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wider px-6 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in items" :key="item.id">
                                <tr class="border-b border-gray-50 last:border-0">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <a :href="'/shop/' + item.slug" class="shrink-0 w-16 h-16 bg-gray-50 rounded-xl overflow-hidden border border-gray-100">
                                                <template x-if="item.image">
                                                    <img :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!item.image">
                                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                                                        <i data-lucide="image" class="w-6 h-6 text-emerald-300"></i>
                                                    </div>
                                                </template>
                                            </a>
                                            <div>
                                                <a :href="'/shop/' + item.slug" class="font-semibold text-gray-900 hover:text-emerald-600 transition-colors line-clamp-1" x-text="item.name"></a>
                                                <template x-if="item.category">
                                                    <span class="text-xs text-gray-400 mt-0.5 block" x-text="item.category"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5">
                                        <template x-if="item.original_price">
                                            <div>
                                                <span class="font-medium text-emerald-600" x-text="formatPrice(item.price)"></span>
                                                <span class="block text-xs text-gray-400 line-through" x-text="formatPrice(item.original_price)"></span>
                                            </div>
                                        </template>
                                        <template x-if="!item.original_price">
                                            <span class="font-medium text-gray-700" x-text="formatPrice(item.price)"></span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-5">
                                        <div class="flex items-center justify-center">
                                            <div class="inline-flex items-center border border-gray-200 rounded-xl overflow-hidden">
                                                <button type="button" @click="updateQuantity(item.id, item.quantity - 1)" class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors" :disabled="updating === item.id">
                                                    <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                                </button>
                                                <input type="number" min="1" readonly :max="item.max" :value="item.quantity" class="w-12 h-9 text-center text-sm font-semibold text-gray-900 border-x border-gray-200 bg-transparent focus:outline-none">
                                                <button type="button" @click="updateQuantity(item.id, item.quantity + 1)" class="w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors" :disabled="updating === item.id">
                                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-right">
                                        <span class="font-bold text-gray-900" x-text="formatPrice(lineTotal(item))"></span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex justify-center">
                                            <button type="button" @click="requestRemove(item)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" :disabled="updating === item.id">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden space-y-4">
                    <template x-for="item in items" :key="item.id">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                            <div class="flex gap-4">
                                <a :href="'/shop/' + item.slug" class="shrink-0 w-20 h-20 bg-gray-50 rounded-xl overflow-hidden border border-gray-100">
                                    <template x-if="item.image">
                                        <img :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!item.image">
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                                            <i data-lucide="image" class="w-6 h-6 text-emerald-300"></i>
                                        </div>
                                    </template>
                                </a>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <a :href="'/shop/' + item.slug" class="font-semibold text-gray-900 text-sm line-clamp-2" x-text="item.name"></a>
                                            <template x-if="item.category">
                                                <span class="text-xs text-gray-400" x-text="item.category"></span>
                                            </template>
                                        </div>
                                        <button type="button" @click="requestRemove(item)" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors shrink-0" :disabled="updating === item.id">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="inline-flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                            <button type="button" @click="updateQuantity(item.id, item.quantity - 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors" :disabled="updating === item.id">
                                                <i data-lucide="minus" class="w-3 h-3"></i>
                                            </button>
                                            <span class="w-10 h-8 flex items-center justify-center text-sm font-semibold text-gray-900 border-x border-gray-200" x-text="item.quantity"></span>
                                            <button type="button" @click="updateQuantity(item.id, item.quantity + 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors" :disabled="updating === item.id">
                                                <i data-lucide="plus" class="w-3 h-3"></i>
                                            </button>
                                        </div>
                                        <span class="font-bold text-emerald-600" x-text="formatPrice(lineTotal(item))"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- The mobile "Continue Shopping" button that used to sit here
                     was a second copy of the one in the order summary panel,
                     so mobile users saw it twice. Only the summary copy remains,
                     and it now sits above the checkout button so the order
                     reads correctly in both layouts: keep browsing, then commit.
                     On mobile the panel falls below the items, so the primary
                     action ends up last on the page, which is what you want. --}}
            </div>

            {{-- Right: Order Summary --}}
            <div class="lg:w-[35%]">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">{{ __('messages.order_summary') }}</h2>

                    {{-- The per-item list that used to live here duplicated the
                         cart table/cards directly above it, so it is gone. The
                         panel now only carries what those rows cannot show:
                         the money and the checkout action. --}}

                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ __('messages.subtotal') }}</span>
                            <span class="font-medium text-gray-900" x-text="formatPrice(subtotal)"></span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                            <span>{{ __('messages.delivery_fee_note') }}</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 mt-4 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-base font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.total') }}</span>
                            <span class="text-xl font-extrabold text-emerald-600" x-text="formatPrice(subtotal)"></span>
                        </div>
                    </div>

                    <a href="{{ route('shop.index') }}" class="mt-6 flex items-center justify-center gap-2 w-full py-3 border border-gray-200 text-gray-600 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        {{ __('messages.continue_shopping') }}
                    </a>

                    <a href="{{ route('checkout.index') }}" class="mt-3 flex items-center justify-center gap-2 w-full py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-emerald-500/25">
                        {{ __('messages.proceed_checkout') }}
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>

    @endif

    {{-- Remove Confirmation Modal --}}
    <div x-show="pendingRemove" x-cloak
        style="display:none; position:fixed; inset:0; z-index:2147483647;">

        {{-- Backdrop --}}
        <div x-show="pendingRemove" @click="cancelRemove()"
            style="position:absolute; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        {{-- Dialog --}}
        <div x-show="pendingRemove"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="w-[calc(100vw-2rem)] max-w-sm mx-auto"
            style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; border-radius:1rem; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); z-index:2147483647; padding:24px; text-align:center;"
            @keydown.escape.window="cancelRemove()">

            <div style="width:56px; height:56px; margin:0 auto; border-radius:50%; background:#fef2f2; display:flex; align-items:center; justify-content:center;">
                <i data-lucide="trash-2" class="w-6 h-6 text-red-500"></i>
            </div>

            <h3 style="font-size:18px; font-weight:700; color:#111827; margin-top:16px; font-family:'Poppins',sans-serif;">{{ __('messages.remove_item_confirm') }}</h3>

            <template x-if="pendingRemove">
                <p style="font-size:13px; color:#6b7280; margin-top:6px; line-height:1.5;" x-text="pendingRemove.name"></p>
            </template>

            <div style="margin-top:20px; display:flex; gap:10px;">
                <button @click="cancelRemove()" type="button"
                    style="flex:1; padding:11px 0; background:#f3f4f6; color:#374151; font-weight:600; font-size:14px; border-radius:10px; cursor:pointer; border:none;">
                    {{ __('messages.cancel') }}
                </button>
                <button @click="confirmRemove()" :disabled="updating === pendingRemove?.id" type="button"
                    style="flex:1; padding:11px 0; background:#ef4444; color:white; font-weight:700; font-size:14px; border-radius:10px; cursor:pointer; border:none; transition:all 0.2s;">
                    <template x-if="updating !== pendingRemove?.id">
                        <span>{{ __('messages.remove') }}</span>
                    </template>
                    <template x-if="updating === pendingRemove?.id">
                        <span>{{ __('messages.removing') }}</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function cartPage() {
    return {
        updating: null,
        items: {!! $cartItemsJson !!},
        itemLabel: '{{ __("messages.items", ["count" => 1]) }}',
        itemsLabel: '{{ __("messages.items", ["count" => 2]) }}',

        get itemCount() {
            return this.items.reduce((sum, i) => sum + i.quantity, 0);
        },

        get subtotal() {
            return this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
        },

        lineTotal(item) {
            return item.price * item.quantity;
        },

        formatNumber(num) {
            return Number(num).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatPrice(num) {
            return '₦' + this.formatNumber(num);
        },

        async updateQuantity(cartItemId, newQty) {
            if (newQty < 1) return;
            this.updating = cartItemId;

            try {
                const response = await fetch('{{ route("cart.update") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ item_id: cartItemId, quantity: newQty }),
                });

                const data = await response.json();

                if (data.success) {
                    const item = this.items.find(i => i.id === cartItemId);
                    if (item) item.quantity = newQty;
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                } else {
                    alert(data.message || 'Failed to update cart.');
                }
            } catch (err) {
                alert('An error occurred. Please try again.');
            } finally {
                this.updating = null;
            }
        },

        pendingRemove: null,

        requestRemove(item) {
            this.pendingRemove = item;
        },

        cancelRemove() {
            this.pendingRemove = null;
        },

        async confirmRemove() {
            if (!this.pendingRemove) return;
            const cartItemId = this.pendingRemove.id;
            this.updating = cartItemId;

            try {
                const response = await fetch(`/cart/remove/${cartItemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.items = this.items.filter(i => i.id !== cartItemId);
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                } else {
                    alert(data.message || 'Failed to remove item.');
                }
            } catch (err) {
                alert('An error occurred. Please try again.');
            } finally {
                this.updating = null;
                this.pendingRemove = null;
            }
        },
    }
}
</script>
@endpush
