@extends('layouts.admin')

@section('title', 'Order #' . $order->id)
@section('header', 'Order #' . $order->id)

@section('content')
<div id="order-detail" x-data="{ status: '{{ $order->status }}', showFeeEditor: false, editingFee: {{ $order->delivery_fee }}, deliveryType: 'truck', editingFeeLocationId: '{{ $order->delivery_location_id }}' }">
    @php
        // Built once and reused by both WhatsApp buttons on this page. Returns
        // null when the customer typed an unusable number, so we hide the
        // buttons rather than linking to a dead chat.
        $waLink = \App\Services\WhatsappService::link(
            $order->customer_phone,
            \App\Services\WhatsappService::orderMessage($order, auth()->user()?->name)
        );
    @endphp
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Order #{{ $order->id }}</h2>
            <p class="text-sm text-gray-500 mt-1">Placed {{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
            @if($order->is_bulk_order)
                <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                    <i data-lucide="package" class="w-3 h-3"></i> Bulk Order
                    @if($order->delivery_fee_status === 'pending_agent')
                        <span class="ml-1 text-amber-600">(Fee Pending)</span>
                    @elseif($order->delivery_fee_status === 'confirmed')
                        <span class="ml-1 text-emerald-600">(Fee Confirmed)</span>
                    @endif
                </span>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(!in_array($order->status, ['new', 'cancelled']))
            <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </a>
            @if($order->customer_email)
            <form method="POST" action="{{ route('admin.orders.email-receipt', $order) }}" class="inline" onsubmit="return confirm('Send receipt to {{ $order->customer_email }}?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email Receipt
                </button>
            </form>
            @endif
            @endif
            @if($waLink)
            {{-- Opens WhatsApp with the customer, message pre-filled with the
                 order number, status and total. Deliberately outside the
                 status gate above: a "new" order is exactly when you want to
                 confirm details with the shopper. --}}
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Chat on WhatsApp
            </a>
            @endif
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
        </div>
    </div>

    {{-- Order Status Bar --}}
    @php
        $steps = ['new', 'processing', 'paid', 'shipped', 'delivered'];
        $currentIndex = array_search($order->status, $steps);
        if ($currentIndex === false) $currentIndex = -1;
        $isCancelled = $order->status === 'cancelled';
        $unpickedCount = $order->items->whereNull('picked_at')->count();
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        @if($isCancelled)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-red-600">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold">Order Cancelled</p>
                        <p class="text-sm text-gray-500">This order has been cancelled.</p>
                    </div>
                </div>
                @if($prev && Auth::user()->hasPermission('restore_cancelled_orders'))
                <button type="button" onclick="reverseOrder('{{ $prev }}', 'restore')" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Restore Order
                </button>
                @endif
            </div>
        @else
            <div class="flex items-center justify-between">
                @foreach($steps as $index => $step)
                    <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center">
                            <div class="w-6 h-6 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold transition-colors {{ $index <= $currentIndex ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400' }}">
                                @if($index < $currentIndex)
                                    <i data-lucide="check" class="w-5 h-5"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <span class="text-[9px] sm:text-xs font-medium mt-2 {{ $index <= $currentIndex ? 'text-emerald-600' : 'text-gray-400' }}">{{ ucfirst($step) }}</span>
                        </div>
                        @if($index < count($steps) - 1)
                            <div class="flex-1 h-0.5 mx-0.5 sm:mx-3 rounded-full {{ $index < $currentIndex ? 'bg-emerald-500' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Advance Status Button --}}
            @if($next)
            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-gray-500">Advance order to <strong class="text-emerald-600">{{ ucfirst($next) }}</strong></p>
                <button type="button" onclick="advanceOrder('{{ $next }}')" id="advance-btn" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Advance to {{ ucfirst($next) }}
                </button>
            </div>
            @if(in_array($next, ['paid', 'shipped']) && $unpickedCount > 0)
            <p id="unpicked-warning" class="mt-2 text-xs text-amber-600"><i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline"></i> <span id="unpicked-count">{{ $unpickedCount }}</span> item(s) still unpicked — you must verify all items before advancing to {{ ucfirst($next) }}.</p>
            @else
            <p id="unpicked-warning" class="mt-2 text-xs text-amber-600 hidden"></p>
            @endif
            @endif

            {{-- Reverse Status Button --}}
            @if($prev)
                @php
                    $reverseRestricted = in_array($order->status, ['paid', 'shipped', 'delivered'])
                        && !Auth::user()->hasPermission('reverse_orders');
                @endphp
                @if(!$reverseRestricted)
                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-gray-500">Reverse order to <strong class="text-amber-600">{{ ucfirst($prev) }}</strong></p>
                    <button type="button" onclick="reverseOrder('{{ $prev }}')" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                        Reverse to {{ ucfirst($prev) }}
                    </button>
                </div>
                @endif
            @endif

            {{-- Cancel Button --}}
            @if(!in_array($order->status, ['delivered', 'cancelled']))
            <div class="mt-3 flex justify-end">
                <button type="button" onclick="advanceOrder('cancelled')" class="px-4 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors">
                    Cancel Order
                </button>
            </div>
            @endif
        @endif
    </div>

    @php
        $payStatusColors = [
            'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'failed' => 'bg-red-50 text-red-700 border-red-200',
            'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Order Items --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Order Items</h3>
                    <div class="flex items-center gap-3">
                        <span id="picked-badge" class="text-xs font-medium px-2.5 py-1 rounded-full {{ $unpickedCount === 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $order->items->whereNotNull('picked_at')->count() }} / {{ $order->items->count() }} picked
                        </span>
                        <button type="button" onclick="document.getElementById('picking-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors shadow-sm">
                            <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i> Verify / Pick Items
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[480px]">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Item</th>
                                <th class="px-6 py-3 text-center font-medium text-gray-500">Qty</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-500">Unit Price</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-500">Total</th>
                                <th class="px-6 py-3 text-center font-medium text-gray-500">Picked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($order->items as $orderItem)
                            @php $picked = !is_null($orderItem->picked_at); @endphp
                            <tr class="hover:bg-gray-50/50 {{ $picked ? 'opacity-50' : '' }}" id="row-{{ $orderItem->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="shrink-0 w-12 h-12 bg-gray-50 rounded-lg overflow-hidden border border-gray-100 js-view-image cursor-pointer" data-src="{{ $orderItem->item && $orderItem->item->image_url ? asset('storage/' . $orderItem->item->image_url) : '' }}" data-name="{{ $orderItem->item->name ?? 'Deleted Item' }}" data-qty="{{ $orderItem->quantity }}" data-price="{{ number_format($orderItem->price_at_order, 2) }}" data-total="{{ number_format($orderItem->price_at_order * $orderItem->quantity, 2) }}">
                                            @if($orderItem->item && $orderItem->item->image_url)
                                                <img src="{{ asset('storage/' . $orderItem->item->image_url) }}" alt="{{ $orderItem->item->name }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                                                    <i data-lucide="image" class="w-4 h-4 text-emerald-300"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <button type="button" class="js-pick-target js-view-image font-medium text-gray-900 break-words text-left hover:text-emerald-600 hover:underline {{ $picked ? 'line-through' : '' }}" data-src="{{ $orderItem->item && $orderItem->item->image_url ? asset('storage/' . $orderItem->item->image_url) : '' }}" data-name="{{ $orderItem->item->name ?? 'Deleted Item' }}" data-qty="{{ $orderItem->quantity }}" data-price="{{ number_format($orderItem->price_at_order, 2) }}" data-total="{{ number_format($orderItem->price_at_order * $orderItem->quantity, 2) }}">
                                            {{ $orderItem->item->name ?? 'Deleted Item' }}
                                        </button>
                                    </div>
                                </td>
                                <td class="js-pick-target px-6 py-4 text-center text-gray-700 whitespace-nowrap {{ $picked ? 'line-through' : '' }}">{{ $orderItem->quantity }}</td>
                                <td class="js-pick-target px-6 py-4 text-right text-gray-700 whitespace-nowrap {{ $picked ? 'line-through' : '' }}">₦{{ number_format($orderItem->price_at_order, 2) }}</td>
                                <td class="js-pick-target px-6 py-4 text-right font-medium text-gray-900 whitespace-nowrap {{ $picked ? 'line-through' : '' }}">₦{{ number_format($orderItem->price_at_order * $orderItem->quantity, 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" data-order-item="{{ $orderItem->id }}" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer"
                                        {{ $picked ? 'checked' : '' }}
                                        onchange="togglePick({{ $orderItem->id }}, this)">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="px-6 py-4 border-t border-gray-100 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-700">₦{{ number_format($order->total_amount - $order->delivery_fee, 2) }}</span>
                    </div>
                    @if($order->delivery_method === 'delivery')
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-gray-500 flex items-center gap-1.5">
                            @if($order->is_bulk_order)
                                Bulk Delivery Fee
                            @else
                                Delivery
                            @endif
                            @if($order->delivery_fee_status === 'pending_agent')
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700">Pending</span>
                            @elseif($order->delivery_fee_status === 'confirmed')
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-700">Confirmed</span>
                            @endif
                        </span>
                        @if($order->delivery_fee > 0)
                            <span class="text-gray-700">₦{{ number_format($order->delivery_fee, 2) }}</span>
                        @else
                            <span class="text-amber-600 font-medium text-xs">To be confirmed by agent</span>
                        @endif
                    </div>
                    @endif
                    <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-2">
                        <span class="text-gray-900">Grand Total</span>
                        <span class="text-gray-900">₦{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Info --}}
            @if($order->payment)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Payment Information</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Gateway</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ ucfirst(str_replace('_', ' ', $order->payment->gateway ?? 'N/A')) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Reference</p>
                            <p class="text-sm font-medium text-gray-900 mt-1 font-mono break-all">{{ $order->payment->reference ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">₦{{ number_format($order->payment->amount ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</p>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $payStatusColors[$order->payment->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                    {{ ucfirst($order->payment->status ?? 'N/A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Customer Information</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Name</p>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $order->customer_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Phone</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <p class="text-sm font-medium text-gray-900">{{ $order->customer_phone ?? 'N/A' }}</p>
                            @if($waLink)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Chat
                            </a>
                            @endif
                        </div>
                        @if(!$waLink && $order->customer_phone)
                        <p class="text-xs text-amber-600 mt-1">Number looks incomplete — cannot open WhatsApp.</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</p>
                        <p class="text-sm font-medium text-gray-900 mt-1 break-all">{{ $order->customer_email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Delivery Method</p>
                        <p class="text-sm font-medium text-gray-900 mt-1 capitalize">{{ str_replace('_', ' ', $order->delivery_method ?? 'N/A') }}</p>
                    </div>
                    @if($order->delivery_address)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Address</p>
                        <p class="text-sm text-gray-700 mt-1">{{ $order->delivery_address }}</p>
                    </div>
                    @endif
                    @if($order->notes)
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Notes</p>
                        <p class="text-sm text-gray-700 mt-1">{{ $order->notes }}</p>
                    </div>
                    @endif
                    @if($order->user)
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Account</p>
                        <p class="text-sm text-gray-700 mt-1 break-words">{{ $order->user->name }} ({{ $order->user->email }})</p>
                    </div>
                    @endif
                    @if($order->processor)
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Processed By</p>
                        <p class="text-sm text-gray-700 mt-1">{{ $order->processor->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

           {{-- Payment Action --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Payment Actions</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Payment Method</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $order->payment_method === 'bank_transfer' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Payment Status</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $payStatusColors[$order->payment_status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                        @if($order->payment_reference)
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Payment Reference</p>
                            <p class="text-sm font-mono text-gray-900 break-all">{{ $order->payment_reference }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Delivery Fee</p>
                            <p class="text-sm font-semibold text-gray-900">₦{{ number_format($order->delivery_fee, 2) }}</p>
                        </div>

                        @if($order->delivery_method === 'delivery' && $order->delivery_fee_status !== 'confirmed')
                        <div class="sm:col-span-2">
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                                <h4 class="text-sm font-semibold text-amber-800 mb-3">Confirm Delivery Fee</h4>
                                <form onsubmit="confirmDeliveryFee(event)" class="space-y-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-2 gap-3">
                                        @if($order->is_bulk_order)
                                        <div>
                                            <label class="block text-xs font-medium text-amber-700 mb-1">Delivery Type</label>
                                            <select x-model="deliveryType" class="w-full px-3 py-2 text-sm border border-amber-200 rounded-lg bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                                <option value="motorcycle">Motorcycle</option>
                                                <option value="truck">Truck</option>
                                            </select>
                                        </div>
                                        @endif
                                        <div>
                                            <label class="block text-xs font-medium text-amber-700 mb-1">Fee (₦)</label>
                                            <input x-model="editingFee" type="number" step="0.01" min="0" required class="w-full px-3 py-2 text-sm border border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                                            Confirm Fee
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                        @if($order->bank_receipt_path)
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Bank Receipt</p>
                            <a href="{{ route('admin.orders.receipt.bank', $order) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 10 2.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.243 8.243L20.587 10H15.172z"/></svg>
                                View Receipt
                            </a>
                        </div>
                        @endif
                    </div>

                    @if($order->payment_method === 'bank_transfer')
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Verify Payment</h4>
                        <form method="POST" action="{{ route('admin.orders.verify-payment', $order) }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
                            @csrf
                            @method('PUT')
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Payment Status</label>
                                <select name="payment_status" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                                    <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="verified" {{ $order->payment_status === 'verified' ? 'selected' : '' }}>Verified</option>
                                    <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                    <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                                </select>
                            </div>
                            <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                                Update Status
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Picking / Verification Modal --}}
<div id="picking-modal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Picking Verification</h3>
                <p class="text-xs text-gray-500 mt-0.5">Tick each item after taking it from the shelf.</p>
            </div>
            <button type="button" onclick="document.getElementById('picking-modal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
            @foreach($order->items as $orderItem)
            @php $picked = !is_null($orderItem->picked_at); @endphp
            <label id="pick-row-{{ $orderItem->id }}" class="flex items-center gap-4 px-6 py-4 cursor-pointer hover:bg-gray-50 {{ $picked ? 'opacity-50' : '' }}">
                <input type="checkbox" data-order-item="{{ $orderItem->id }}" class="w-5 h-5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer shrink-0"
                    {{ $picked ? 'checked' : '' }}
                    onchange="togglePick({{ $orderItem->id }}, this)">
                <div class="shrink-0 w-14 h-14 bg-gray-50 rounded-lg overflow-hidden border border-gray-100">
                    @if($orderItem->item && $orderItem->item->image_url)
                        <img src="{{ asset('storage/' . $orderItem->item->image_url) }}" alt="{{ $orderItem->item->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                            <i data-lucide="image" class="w-5 h-5 text-emerald-300"></i>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="js-pick-target text-sm font-medium text-gray-900 {{ $picked ? 'line-through' : '' }}">{{ $orderItem->item->name ?? 'Deleted Item' }}</p>
                    <p class="text-xs text-gray-400">Qty: {{ $orderItem->quantity }} &times; ₦{{ number_format($orderItem->price_at_order, 2) }}</p>
                </div>
                <span class="js-pick-target text-sm font-semibold text-gray-900 shrink-0 {{ $picked ? 'line-through' : '' }}">₦{{ number_format($orderItem->price_at_order * $orderItem->quantity, 2) }}</span>
            </label>
            @endforeach
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-600"><span id="modal-picked-count">{{ $order->items->whereNotNull('picked_at')->count() }}</span> of {{ $order->items->count() }} items picked</p>
            <button type="button" onclick="document.getElementById('picking-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">Done</button>
        </div>
    </div>
</div>

{{-- Item Image Modal --}}
<div id="item-image-modal" class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl p-4 max-w-lg w-full" onclick="event.stopPropagation()">
        <h3 id="item-image-title" class="text-base font-semibold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;"></h3>
        <div id="item-image-wrap" class="w-full rounded-xl border border-gray-100 overflow-hidden bg-gray-50 flex items-center justify-center min-h-[200px]">
            <img id="item-image-preview" src="" alt="" class="w-full object-cover">
            <div id="item-image-empty" class="hidden w-full h-48 flex items-center justify-center text-gray-300">
                <i data-lucide="image" class="w-12 h-12"></i>
            </div>
        </div>
        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="bg-gray-50 rounded-lg py-2">
                <dt class="text-[10px] uppercase tracking-wide text-gray-400">Qty</dt>
                <dd id="item-info-qty" class="text-sm font-semibold text-gray-900"></dd>
            </div>
            <div class="bg-gray-50 rounded-lg py-2">
                <dt class="text-[10px] uppercase tracking-wide text-gray-400">Unit Price</dt>
                <dd id="item-info-price" class="text-sm font-semibold text-gray-900"></dd>
            </div>
            <div class="bg-gray-50 rounded-lg py-2">
                <dt class="text-[10px] uppercase tracking-wide text-gray-400">Total</dt>
                <dd id="item-info-total" class="text-sm font-semibold text-gray-900"></dd>
            </div>
        </dl>
        <button type="button" onclick="document.getElementById('item-image-modal').classList.add('hidden')" class="mt-4 w-full px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">Close</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    function openItemImage(src, name, qty, price, total) {
        const img = document.getElementById('item-image-preview');
        const empty = document.getElementById('item-image-empty');
        document.getElementById('item-image-title').textContent = name || '';
        document.getElementById('item-info-qty').textContent = qty || '';
        document.getElementById('item-info-price').textContent = price ? '₦' + price : '';
        document.getElementById('item-info-total').textContent = total ? '₦' + total : '';
        if (src) {
            img.src = src;
            img.classList.remove('hidden');
            empty.classList.add('hidden');
            empty.classList.remove('flex');
        } else {
            img.src = '';
            img.classList.add('hidden');
            empty.classList.remove('hidden');
            empty.classList.add('flex');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        document.getElementById('item-image-modal').classList.remove('hidden');
    }

    document.addEventListener('click', function (e) {
        const el = e.target.closest('.js-view-image');
        if (!el) return;
        openItemImage(el.dataset.src, el.dataset.name, el.dataset.qty, el.dataset.price, el.dataset.total);
    });

    function togglePick(orderItemId, checkbox) {
        fetch('{{ url('admin/orders/' . $order->id . '/items') }}/' + orderItemId + '/toggle-picked', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                checkbox.checked = !checkbox.checked;
                showToast(data.message || 'Failed to update item.', 'error');
                return;
            }
            showToast(data.picked ? 'Item marked as picked.' : 'Item unmarked.', 'success');
            syncPickedUI(orderItemId, data.picked, data.picked_count, data.total_count);
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
            showToast('Network error. Please try again.', 'error');
        });
    }

    function syncPickedUI(orderItemId, picked, pickedCount, totalCount) {
        // Sync every checkbox for this item (table + picking modal)
        document.querySelectorAll('input[data-order-item="' + orderItemId + '"]').forEach(function (cb) {
            cb.checked = picked;
        });

        // Main table row
        const row = document.getElementById('row-' + orderItemId);
        if (row) {
            row.classList.toggle('opacity-50', picked);
            row.querySelectorAll('.js-pick-target').forEach(function (el) {
                el.classList.toggle('line-through', picked);
            });
        }

        // Picking modal row
        const pickRow = document.getElementById('pick-row-' + orderItemId);
        if (pickRow) {
            pickRow.classList.toggle('opacity-50', picked);
            pickRow.querySelectorAll('.js-pick-target').forEach(function (el) {
                el.classList.toggle('line-through', picked);
            });
        }

        // Counters
        const badge = document.getElementById('picked-badge');
        if (badge) {
            badge.textContent = pickedCount + ' / ' + totalCount + ' picked';
            badge.classList.toggle('bg-emerald-100', pickedCount === totalCount && totalCount > 0);
            badge.classList.toggle('text-emerald-700', pickedCount === totalCount && totalCount > 0);
            badge.classList.toggle('bg-gray-100', pickedCount !== totalCount || totalCount === 0);
            badge.classList.toggle('text-gray-600', pickedCount !== totalCount || totalCount === 0);
        }
        const modalCount = document.getElementById('modal-picked-count');
        if (modalCount) modalCount.textContent = pickedCount;

        // Advance warning
        const warning = document.getElementById('unpicked-warning');
        if (warning) {
            const remaining = totalCount - pickedCount;
            if (remaining > 0) {
                warning.classList.remove('hidden');
                const countEl = document.getElementById('unpicked-count');
                if (countEl) {
                    countEl.textContent = remaining;
                } else {
                    warning.innerHTML = '<i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline"></i> <span id="unpicked-count">' + remaining + '</span> item(s) still unpicked.';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            } else {
                warning.classList.add('hidden');
            }
        }
    }

    function advanceOrder(newStatus) {
        const btn = document.getElementById('advance-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Processing...'; }

        fetch('{{ route('admin.orders.update-status', $order) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                showToast(data.message || 'Failed to update order.', 'error');
                if (btn) { btn.disabled = false; btn.textContent = 'Advance to Processing'; }
                return;
            }
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
            showToast('Network error. Please try again.', 'error');
            if (btn) { btn.disabled = false; btn.textContent = 'Advance to Processing'; }
        });
    }

    function reverseOrder(newStatus, mode) {
        const verb = mode === 'restore' ? 'restore' : 'reverse';
        if (!confirm('Are you sure you want to ' + verb + ' this order to {{ ucfirst($prev) }}?')) return;

        fetch('{{ route('admin.orders.reverse-status', $order) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                showToast(data.message || 'Failed to reverse order.', 'error');
                return;
            }
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
            showToast('Network error. Please try again.', 'error');
        });
    }

    function confirmDeliveryFee(e) {
        e.preventDefault();
        const el = document.getElementById('order-detail');
        const data = window.Alpine.$data(el);

        fetch('{{ route('admin.orders.confirm-delivery-fee', $order) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                delivery_fee: data.editingFee,
                delivery_type: data.deliveryType,
            })
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                showToast(data.message || 'Failed to confirm fee.', 'error');
                return;
            }
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        })
        .catch(() => {
            showToast('Network error. Please try again.', 'error');
        });
    }
</script>
@endsection
