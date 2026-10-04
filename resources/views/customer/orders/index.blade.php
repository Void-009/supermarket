@extends('layouts.main')
@section('title', __('messages.my_orders'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Page Header --}}
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition-colors">{{ __('messages.home') }}</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-gray-900 font-medium">{{ __('messages.my_orders') }}</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.my_orders') }}</h1>
        <p class="text-gray-500 mt-1">{{ __('messages.track_orders_desc') }}</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        @include('components.alert', ['type' => 'success', 'message' => session('success')])
    @endif

    @if($orders->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i data-lucide="package" class="w-10 h-10 text-gray-300"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">{{ __('messages.no_orders_yet') }}</h2>
            <p class="text-gray-500 mb-6">{{ __('messages.start_shopping_orders_desc') }}</p>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl transition-colors">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                {{ __('messages.browse_shop') }}
            </a>
        </div>
    @else
        {{-- Orders List --}}
        <div class="space-y-4">
            @foreach($orders as $order)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <a href="{{ route('customer.orders.show', $order->id) }}" class="text-lg font-bold text-gray-900 hover:text-emerald-600 transition-colors" style="font-family:'Poppins',sans-serif;">
                            {{ __('messages.order') }} #{{ $order->id }}
                        </a>
                        @if($order->status === 'new')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">{{ __('messages.status_new') }}</span>
                        @elseif($order->status === 'processing')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">{{ __('messages.status_processing') }}</span>
                        @elseif($order->status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">{{ __('messages.status_paid') }}</span>
                        @elseif($order->status === 'shipped')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">{{ __('messages.status_shipped') }}</span>
                        @elseif($order->status === 'delivered')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">{{ __('messages.status_delivered') }}</span>
                        @elseif($order->status === 'cancelled')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">{{ __('messages.status_cancelled') }}</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $order->created_at->format('M d, Y \a\t g:i A') }}
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                        <div class="min-w-0">
                            <span class="text-gray-500">{{ __('messages.items') }}:</span>
                            <span class="font-medium text-gray-900 ml-1">{{ $order->items->sum('quantity') }}</span>
                        </div>
                        <div class="min-w-0">
                            <span class="text-gray-500">{{ __('messages.total') }}:</span>
                            <span class="font-semibold text-gray-900 ml-1">₦{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        @if($order->deliveryLocation)
                            <div class="min-w-0">
                                <span class="text-gray-500">{{ __('messages.delivery') }}:</span>
                                <span class="font-medium text-gray-900 ml-1 break-words">{{ $order->deliveryLocation->name }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="openOrderReceipt({{ $order->id }})" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors whitespace-nowrap">
                            <i data-lucide="receipt" class="w-4 h-4 shrink-0"></i> {{ __('messages.view_receipt') }}
                        </button>
                        <a href="{{ route('customer.orders.show', $order->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors whitespace-nowrap">
                            <i data-lucide="eye" class="w-4 h-4"></i> {{ __('messages.view_details') }}
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($orders->hasPages())
        <div class="mt-8">
            {{ $orders->links('components.pagination') }}
        </div>
        @endif
    @endif
</div>

{{-- Hidden receipt templates --}}
@foreach($orders as $order)
    <div id="receipt-{{ $order->id }}" class="hidden">
        {{-- :barcode="false" because this page renders one of these per order
             in the list, and each QR is a PNG built for every row even though
             only one modal is ever opened. --}}
        <x-receipt-content :order="$order" :barcode="false" />
    </div>
@endforeach

{{-- Receipt popup --}}
<x-receipt-modal />

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
