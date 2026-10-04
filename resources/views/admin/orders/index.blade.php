@extends('layouts.admin')

@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
<div x-data="bulkManager()">
    {{-- Page Header --}}
    <div class="mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Orders</h2>
        <p class="text-sm text-gray-500 mt-1">Manage customer orders.</p>
    </div>

    @include('components.admin-search-filter')

    {{-- Status Filter Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $tabs = [
                '' => 'All',
                'new' => 'New',
                'processing' => 'Processing',
                'paid' => 'Paid',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            @php
                $active = request('status') === $key;
                $count = $key === '' ? $totalAll : ($counts[$key] ?? 0);
            @endphp
            <a href="{{ route('admin.orders.index', $key ? ['status' => $key] : []) }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-xl transition-colors {{ $active ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                {{ $label }}
                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-semibold rounded-full {{ $active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' order(s) selected'"></span>
            <button @click="selectedIds = []; allSelected = false" class="text-xs text-emerald-600 hover:text-emerald-800 underline font-medium">Deselect All</button>
        </div>
        <div class="flex items-center gap-2">
            <button @click="bulkAction('cancel')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-lg transition-colors">
                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Cancel Selected
            </button>
            <button @click="bulkAction('delete')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Selected
            </button>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2.5 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Order</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Phone</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Items</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Total</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Processed By</th>
                        <th class="px-2.5 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-2.5 py-3 text-center font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-2.5 py-4 w-10">
                            <input type="checkbox" :value="{{ $order->id }}" x-model="selectedIds" @change="allSelected = selectedIds.length === {{ $orders->total() }}" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-2.5 py-4 font-semibold text-gray-900 whitespace-nowrap">#{{ $order->id }}</td>
                        <td class="px-2.5 py-4 text-gray-700 max-w-[140px] break-words">{{ $order->customer_name ?? 'N/A' }}</td>
                        <td class="px-2.5 py-4 text-gray-500 max-w-[120px] break-words">{{ $order->customer_phone ?? 'N/A' }}</td>
                        <td class="px-2.5 py-4 text-gray-500 whitespace-nowrap">{{ $order->items->count() }}</td>
                        <td class="px-2.5 py-4 font-medium text-gray-900 whitespace-nowrap">₦{{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-2.5 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'new' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                    'paid' => 'bg-green-50 text-green-700 border-green-200',
                                    'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                                ];
                                $colorClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $colorClass }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-2.5 py-4 text-gray-600 max-w-[120px] break-words">{{ $order->processor?->name ?? '—' }}</td>
                        <td class="px-2.5 py-4 text-gray-500 whitespace-nowrap">{{ $order->created_at->format('M d, Y') }}</td>
                        <td class="px-2.5 py-4 text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                @php
                                    // Row-level shortcut so staff can answer a
                                    // customer without opening the order first.
                                    $rowWa = \App\Services\WhatsappService::link(
                                        $order->customer_phone,
                                        \App\Services\WhatsappService::orderMessage($order, auth()->user()?->name)
                                    );
                                @endphp
                                @if($rowWa)
                                <a href="{{ $rowWa }}" target="_blank" rel="noopener noreferrer" title="Chat on WhatsApp"
                                    class="inline-flex items-center gap-1 px-2 py-1.5 text-[11px] font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors shadow-sm">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    WhatsApp
                                </a>
                                @endif
                                <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1 px-2 py-1.5 text-[11px] font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
                                    <i data-lucide="eye" class="w-3 h-3"></i> View
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <i data-lucide="receipt" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No orders found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $orders->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>

<script>
function bulkManager() {
    return {
        selectedIds: [],
        allSelected: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = @js($orders->pluck('id')->toArray());
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        async bulkAction(action) {
            if (!confirm(action === 'delete' ? 'Delete selected orders? This cannot be undone.' : 'Cancel selected orders?')) return;
            try {
                const res = await fetch('{{ route("admin.orders.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ action, ids: this.selectedIds }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred.');
                }
            } catch (e) {
                alert('An error occurred. Please try again.');
            }
        },
    };
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
