@extends('layouts.main')
@section('title', 'Order Confirmed')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-16">

    {{-- Success Section --}}
    <div class="text-center mb-12">
        {{-- Animated Checkmark --}}
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6 success-pulse">
            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center shadow-lg shadow-green-500/30">
                <i data-lucide="check" class="w-8 h-8 text-white" stroke-width="3"></i>
            </div>
        </div>

        <h1 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">{{ __('messages.order_placed_success') }}</h1>
        <p class="text-gray-500 text-lg">{{ __('messages.thank_you_for_order', ['name' => $order->customer_name]) }}</p>

        <div class="mt-5 inline-flex items-center gap-4 bg-white border border-gray-200 rounded-xl px-6 py-3 shadow-sm">
            <div class="text-sm">
                <span class="text-gray-400">{{ __('messages.order') }}</span>
                <span class="font-bold text-gray-900 ml-1">#{{ $order->id }}</span>
            </div>
            <div class="w-px h-4 bg-gray-200"></div>
            <div class="text-sm">
                <span class="text-gray-400">{{ __('messages.date') }}</span>
                <span class="font-semibold text-gray-700 ml-1">{{ $order->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Order Details Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">

        {{-- Status Badge --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.order_details') }}</h2>
            @php
                $statusColors = [
                    'new' => 'bg-blue-100 text-blue-700',
                    'processing' => 'bg-emerald-100 text-emerald-700',
                    'paid' => 'bg-green-100 text-green-700',
                    'completed' => 'bg-green-100 text-green-700',
                    'cancelled' => 'bg-red-100 text-red-700',
                ];
                $color = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $color }}">
                {{ ucfirst($order->status) }}
            </span>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                {{-- Order Items --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ __('messages.items_ordered') }}</h3>
                    <div class="space-y-4">
                        @foreach($order->items as $orderItem)
                            <div class="flex items-center gap-3">
                                <div class="shrink-0 w-12 h-12 bg-gray-50 rounded-lg overflow-hidden border border-gray-100">
                                    @if($orderItem->item && $orderItem->item->image_url)
                                        <img src="{{ asset('storage/' . $orderItem->item->image_url) }}" alt="{{ $orderItem->item->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                                            <i data-lucide="image" class="w-4 h-4 text-emerald-300"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ $orderItem->item->name ?? __('messages.item') }}</p>
                                    <p class="text-xs text-gray-400">{{ $orderItem->quantity }} &times; ₦{{ number_format($orderItem->price_at_order, 2) }}</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 shrink-0">₦{{ number_format($orderItem->price_at_order * $orderItem->quantity, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-100 mt-4 pt-4 flex items-center justify-between">
                        <span class="font-bold text-gray-900">{{ __('messages.total') }}</span>
                        <span class="text-lg font-extrabold text-emerald-600">₦{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                {{-- Delivery Info --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ __('messages.delivery_info') }}</h3>
                    <div class="space-y-4">

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $order->delivery_method === 'delivery' ? 'truck' : 'store' }}" class="w-4 h-4 text-gray-500"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">{{ __('messages.method') }}</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $order->delivery_method === 'delivery' ? __('messages.home_delivery') : __('messages.store_pickup') }}</p>
                            </div>
                        </div>

                        @if($order->delivery_method === 'delivery' && $order->delivery_address)
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">{{ __('messages.address') }}</p>
                                    <p class="text-sm text-gray-700 mt-0.5">{{ $order->delivery_address }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-500"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">{{ __('messages.phone') }}</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $order->customer_phone }}</p>
                            </div>
                        </div>

                        @if($order->customer_email)
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="mail" class="w-4 h-4 text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Email</p>
                                    <p class="text-sm text-gray-700 mt-0.5 break-words">{{ $order->customer_email }}</p>
                                </div>
                            </div>
                        @endif

                        @if($order->notes)
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="message-square" class="w-4 h-4 text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">{{ __('messages.notes') }}</p>
                                    <p class="text-sm text-gray-700 mt-0.5">{{ $order->notes }}</p>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Section --}}
    @if($order->payment && $order->payment->status === 'success')
        <div class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-8 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <p class="font-bold text-green-800">{{ __('messages.payment_confirmed') }}</p>
                <p class="text-sm text-green-600 break-words">{{ __('messages.paid_via', ['gateway' => ucfirst(str_replace('_', ' ', $order->payment->gateway))]) }} — {{ __('messages.reference') }}: {{ $order->payment->reference }}</p>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.payment_method') }}</h3>
            </div>

            @if($order->payment_method === 'bank_transfer')
                <p class="text-sm text-gray-500 mb-3">{{ __('messages.payment_pending_bank') }}</p>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-sm text-amber-800 font-medium break-words">{{ __('messages.payment_reference', ['reference' => $order->payment_reference]) }}</p>
                    <p class="text-xs text-amber-700 mt-1">{{ __('messages.payment_reference_hint') }}</p>
                </div>
            @else
                <p class="text-sm text-gray-500">{{ __('messages.no_payment_required') }}</p>
            @endif
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
        <a href="{{ route('shop.index') }}" class="flex items-center justify-center gap-2 px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-emerald-500/25 w-full sm:w-auto">
            <i data-lucide="shopping-bag" class="w-5 h-5"></i>
            {{ __('messages.back_to_shop') }}
        </a>
        <button type="button" onclick="openOrderReceipt({{ $order->id }})" class="flex items-center justify-center gap-2 px-6 py-3 border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors w-full sm:w-auto">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            {{ __('messages.view_order') }}
        </button>
    </div>
</div>

{{-- Hidden receipt template --}}
<div id="receipt-{{ $order->id }}" class="hidden">
    <x-receipt-content :order="$order" />
</div>

{{-- Receipt popup --}}
<x-receipt-modal />

<style>
    @keyframes successPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .success-pulse {
        animation: successPulse 2s ease-in-out infinite;
    }
</style>

@endsection
