@extends('layouts.admin')

@section('title', 'Revenue')
@section('header', 'Revenue')

@section('content')
<div class="space-y-8" x-data="{ showCustom: '{{ $range }}' === 'custom' }">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Revenue Overview</h2>
            <p class="text-sm text-gray-500 mt-1">
                @if($from && $to)
                    {{ $from->format('M d, Y') }} — {{ $to->format('M d, Y') }}
                @else
                    Showing all time data
                @endif
            </p>
        </div>
    </div>

    @include('components.admin-search-filter')

    {{-- Date Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-wrap items-center gap-2">
            @php
                $filters = [
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
                    'last_14_days' => 'Last 14 Days',
                    'last_30_days' => 'Last 30 Days',
                    'last_90_days' => 'Last 90 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'this_year' => 'This Year',
                    'last_year' => 'Last Year',
                ];
            @endphp
            @foreach($filters as $key => $label)
                <a href="{{ request()->url() }}?range={{ $key }}"
                   class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $range === $key ? 'bg-emerald-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ request()->url() }}?range=all_time"
               class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $range === 'all_time' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                All Time
            </a>
            <button @click="showCustom = !showCustom"
                    class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $range === 'custom' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Custom Range
            </button>
        </div>

        {{-- Custom Range Picker --}}
        <div x-show="showCustom" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">From</label>
                <input type="date" id="custom_from" value="{{ $from ? $from->format('Y-m-d') : '' }}"
                       class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">To</label>
                <input type="date" id="custom_to" value="{{ $to ? $to->format('Y-m-d') : '' }}"
                       class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
            </div>
            <button onclick="applyCustomRange()" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                Apply
            </button>
        </div>
    </div>

    {{-- Staff Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" action="{{ request()->url() }}" class="flex flex-wrap items-end gap-4">
            @if(request()->filled('range'))
                <input type="hidden" name="range" value="{{ request('range') }}">
            @endif
            @if(request()->filled('from_date'))
                <input type="hidden" name="from_date" value="{{ request('from_date') }}">
            @endif
            @if(request()->filled('to_date'))
                <input type="hidden" name="to_date" value="{{ request('to_date') }}">
            @endif
            @if(request()->filled('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            <div class="min-w-[240px] flex-1 sm:max-w-sm">
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Staff Member</label>
                <select name="user_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                    <option value="">All Users</option>
                    @foreach($staffUsers as $staff)
                        <option value="{{ $staff->id }}" {{ (string) $selectedUserId === (string) $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }} ({{ ucfirst($staff->role) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                Submit
            </button>
            @if($selectedUserId)
                <a href="{{ request()->url() }}?range={{ $range }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">₦{{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Paid Orders</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">{{ number_format($totalOrders) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="receipt" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Active Agents/Admins</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">{{ $agentRevenue->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="users" class="w-6 h-6 text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Breakdown by Payment Method --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">POP (Pay On Pickup)</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">₦{{ number_format($breakdown['pop'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shopping-bag" class="w-6 h-6 text-amber-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Bank Transfer</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">₦{{ number_format($breakdown['bank_transfer'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="building" class="w-6 h-6 text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Cash Sale</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tabular-nums leading-tight fit-value">₦{{ number_format($breakdown['cash_sale'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[110px]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-500">Verified Revenue</p>
                    <p class="text-xl sm:text-2xl font-bold text-emerald-700 mt-1 tabular-nums leading-tight fit-value">₦{{ number_format(array_sum($breakdown), 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6 text-emerald-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue by Agent / Admin --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">
                @if($selectedUserId)
                    Revenue for {{ $agentRevenue->first()?->name ?? 'Selected User' }}
                @else
                    Revenue by Agent / Admin
                @endif
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 text-left font-medium text-gray-500">#</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Role</th>
                        <th class="px-6 py-3 text-center font-medium text-gray-500">Paid Orders</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500">Revenue Generated</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500">% of Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($agentRevenue as $index => $agent)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs font-bold uppercase flex-shrink-0">
                                    {{ substr($agent->name, 0, 1) }}
                                </div>
                                <div class="font-medium text-gray-900 truncate">{{ $agent->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $roleColors = [
                                    'admin' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'agent' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $roleColors[$agent->role] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                {{ ucfirst($agent->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-medium text-gray-900 tabular-nums whitespace-nowrap">{{ number_format($agent->orders_count) }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-emerald-700 tabular-nums whitespace-nowrap">₦{{ number_format($agent->revenue, 2) }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $agent->percentage }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-500 w-10 text-right">{{ $agent->percentage }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="users" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No agents or admins have processed orders yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Paid Orders --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="revenueBulkManager()">
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Recent Paid Orders</h3>
        </div>

        {{-- Bulk Actions Bar --}}
        <div x-show="selectedIds.length > 0" x-cloak x-transition
             class="mx-6 mt-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' order(s) selected'"></span>
                <button @click="selectedIds = []; allSelected = false" class="text-xs text-emerald-600 hover:text-emerald-800 underline font-medium">Deselect All</button>
            </div>
            <div class="flex items-center gap-2">
                <button @click="exportSelected()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-lg transition-colors">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Export Selected
                </button>
                <button @click="bulkDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-2.5 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Order ID</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Customer</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Processed By</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-500">Amount</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Payment Method</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($recentPaidOrders as $order)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-2.5 py-4 w-10">
                            <input type="checkbox" :value="{{ $order->id }}" x-model="selectedIds" @change="allSelected = selectedIds.length === {{ $recentPaidOrders->total() }}" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">#{{ $order->id }}</td>
                        <td class="px-6 py-4 text-gray-700 max-w-[160px] break-words">{{ $order->customer_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            @if($order->processor)
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px] font-bold uppercase flex-shrink-0">
                                        {{ substr($order->processor->name, 0, 1) }}
                                    </div>
                                    <span class="text-gray-700 truncate max-w-[140px]">{{ $order->processor->name }}</span>
                                </div>
                            @else
                                <span class="text-gray-400">System</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-emerald-700 tabular-nums whitespace-nowrap">₦{{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</td>
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $order->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No paid orders yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentPaidOrders->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $recentPaidOrders->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    function applyCustomRange() {
        const from = document.getElementById('custom_from').value;
        const to = document.getElementById('custom_to').value;
        if (!from || !to) {
            showToast('Please select both start and end dates.', 'error');
            return;
        }
        window.location.href = '{{ request()->url() }}?range=custom&from_date=' + from + '&to_date=' + to;
    }

    function revenueBulkManager() {
        return {
            selectedIds: [],
            allSelected: false,
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,

            toggleAll(e) {
                if (e.target.checked) {
                    this.selectedIds = @js($recentPaidOrders->pluck('id')->toArray());
                    this.allSelected = true;
                } else {
                    this.selectedIds = [];
                    this.allSelected = false;
                }
            },

            exportSelected() {
                if (!this.selectedIds.length) return;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.revenue.export-bulk") }}';
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = this.csrfToken;
                form.appendChild(token);
                this.selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            },

            async bulkDelete() {
                if (!confirm('Are you sure you want to delete the selected order(s)? This cannot be undone.')) return;
                try {
                    const res = await fetch('{{ route("admin.revenue.bulk") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ action: 'delete', ids: this.selectedIds }),
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
    function fitCardValues() {
        document.querySelectorAll('.fit-value').forEach(function (el) {
            el.style.whiteSpace = 'nowrap';
            el.style.fontSize = '';
            var parent = el.parentElement;
            if (!parent) return;
            var max = parent.clientWidth;
            var size = parseFloat(window.getComputedStyle(el).fontSize) || 24;
            var min = 11;
            while (el.scrollWidth > max && size > min) {
                size -= 0.5;
                el.style.fontSize = size + 'px';
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function () {
        fitCardValues();
        window.addEventListener('resize', fitCardValues);
        setTimeout(fitCardValues, 300);
    });
</script>
@endsection
