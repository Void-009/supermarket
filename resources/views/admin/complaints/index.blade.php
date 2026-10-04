@extends('layouts.admin')

@section('title', 'Complaints')
@section('header', 'Customer Complaints')

@section('content')
<div x-data="complaintsManager()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Customer Complaints</h2>
            <p class="text-sm text-gray-500 mt-1">View and manage customer support tickets.</p>
        </div>
    </div>

    @include('components.admin-search-filter')

    {{-- Status Filter Tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <a href="{{ route('admin.complaints.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition-colors {{ !request('status') ? 'bg-emerald-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            All
        </a>
        <a href="{{ route('admin.complaints.index', ['status' => 'open']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition-colors {{ request('status') === 'open' ? 'bg-red-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-red-400"></span> Open
        </a>
        <a href="{{ route('admin.complaints.index', ['status' => 'in_progress']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition-colors {{ request('status') === 'in_progress' ? 'bg-yellow-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-yellow-400"></span> In Progress
        </a>
        <a href="{{ route('admin.complaints.index', ['status' => 'resolved']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition-colors {{ request('status') === 'resolved' ? 'bg-green-500 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <span class="w-2 h-2 rounded-full bg-green-400"></span> Resolved
        </a>
    </div>

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' complaint(s) selected'"></span>
            <button @click="selectedIds = []; allSelected = false" class="text-xs text-emerald-600 hover:text-emerald-800 underline font-medium">Deselect All</button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="bulkAction('open')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                <i data-lucide="circle-dot" class="w-3.5 h-3.5"></i> Mark Open
            </button>
            <button @click="bulkAction('in_progress')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-yellow-700 bg-yellow-100 hover:bg-yellow-200 rounded-lg transition-colors">
                <i data-lucide="clock" class="w-3.5 h-3.5"></i> Mark In Progress
            </button>
            <button @click="bulkAction('resolved')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-green-700 bg-green-100 hover:bg-green-200 rounded-lg transition-colors">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Mark Resolved
            </button>
            <button @click="bulkAction('delete')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
            </button>
        </div>
    </div>

    {{-- Complaints Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2.5 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Order</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Subject</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($complaints as $complaint)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-2.5 py-4 w-10">
                            <input type="checkbox" :value="{{ $complaint->id }}" x-model="selectedIds" @change="allSelected = selectedIds.length === {{ $complaints->total() }}" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $complaint->order_id) }}" class="font-medium text-emerald-600 hover:text-emerald-700">#{{ $complaint->order_id }}</a>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 max-w-[200px] truncate">{{ $complaint->user->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-400 break-all">{{ $complaint->user->email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 line-clamp-1 max-w-[260px] break-words">{{ $complaint->subject }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($complaint->status === 'open')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">Open</span>
                            @elseif($complaint->status === 'in_progress')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">In Progress</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">Resolved</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs whitespace-nowrap">
                            {{ $complaint->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.complaints.show', $complaint->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="message-circle" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-500">No complaints found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complaints->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $complaints->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>

<script>
function complaintsManager() {
    return {
        selectedIds: [],
        allSelected: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = @js($complaints->pluck('id')->toArray());
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        async bulkAction(action) {
            const labels = { open: 'mark as open', in_progress: 'mark as in progress', resolved: 'mark as resolved', delete: 'delete' };
            if (!confirm('Are you sure you want to ' + labels[action] + ' the selected complaint(s)?')) return;
            try {
                const res = await fetch('{{ route("admin.complaints.bulk") }}', {
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

document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
@endsection
