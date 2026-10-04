@extends('layouts.admin')

@section('title', 'POS Sync #' . $batch->id)
@section('header', 'POS Sync Details')

@section('content')
<div x-data="posSyncDetailPage()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.pos-sync.index') }}" class="text-xs font-medium text-gray-400 hover:text-emerald-600 inline-flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to POS Sync
            </a>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">
                POS Batch #{{ $batch->id }}
            </h2>
            <p class="text-sm text-gray-500 mt-1 font-mono text-xs break-all">{{ $batch->sync_id }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($batch->failed_items > 0)
            <button @click="retryBatch()" :disabled="retrying"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 transition-colors shadow-sm disabled:opacity-50">
                <i data-lucide="rotate-ccw" class="w-4 h-4" :class="retrying && 'animate-spin'"></i>
                <span x-text="retrying ? 'Retrying...' : 'Retry Failed ({{ $batch->failed_items }})'"></span>
            </button>
            @endif
        </div>
    </div>

    @php
        $statusColors = [
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'failed' => 'bg-red-50 text-red-700 border-red-200',
            'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
            'pending' => 'bg-blue-50 text-blue-700 border-blue-200',
            'duplicate' => 'bg-gray-50 text-gray-600 border-gray-200',
        ];
        $itemStatusColors = [
            'processed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'failed' => 'bg-red-50 text-red-700 border-red-200',
            'pending' => 'bg-blue-50 text-blue-700 border-blue-200',
            'skipped' => 'bg-gray-50 text-gray-600 border-gray-200',
        ];
    @endphp

    {{-- Batch Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Status</span>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$batch->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                {{ ucfirst($batch->status) }}
            </span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Total Items</span>
                <i data-lucide="package" class="w-4 h-4 text-gray-400"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($batch->total_items) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Processed</span>
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600 tabular-nums">{{ number_format($batch->processed_items) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Failed</span>
                <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-red-600 tabular-nums">{{ number_format($batch->failed_items) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Received</span>
                <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900 mt-1">{{ $batch->created_at ? $batch->created_at->format('M d, Y g:i A') : 'N/A' }}</p>
            <p class="text-xs text-gray-400">Source: {{ $batch->source ?? 'pos-agent' }}</p>
        </div>
    </div>

    @if($batch->error_message)
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        <strong>Batch error:</strong> {{ $batch->error_message }}
    </div>
    @endif

    {{-- Items Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-4 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Items</h3>
            <form method="GET" action="{{ route('admin.pos-sync.show', $batch) }}" class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    <option value="">All statuses</option>
                    @foreach(['pending', 'processed', 'failed', 'skipped'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($items->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[800px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left font-medium text-gray-500">POS Item ID</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Action</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-24">Price</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-20">Qty</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-28">Status</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-32">Processed At</th>
                        <th class="px-3 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($items as $item)
                    @php
                        $payload = is_array($item->payload) ? $item->payload : [];
                        $changedFields = array_keys(array_diff_key($payload, array_flip(['pos_item_id', 'action', 'name', 'barcodes'])));
                        $actionColors = [
                            'create' => 'bg-purple-50 text-purple-700 border-purple-200',
                            'update' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'deactivate' => 'bg-orange-50 text-orange-700 border-orange-200',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors" x-data="{ open: false }">
                        <td class="px-3 py-4 font-mono text-xs text-gray-600 whitespace-nowrap">{{ $item->pos_item_id }}</td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $actionColors[$item->action] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                {{ ucfirst($item->action) }}
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <button @click="open = !open" class="text-left font-medium text-gray-900 hover:text-emerald-600 inline-flex items-center gap-1 max-w-[260px] break-words">
                                <span class="break-words">{{ $payload['name'] ?? '—' }}</span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" :class="open && 'rotate-180'"></i>
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center text-gray-700 whitespace-nowrap">
                            @if(array_key_exists('selling_price', $payload))
                                ₦{{ number_format((float) $payload['selling_price'], 2) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center text-gray-700 whitespace-nowrap">
                            {{ array_key_exists('quantity', $payload) ? (int) $payload['quantity'] : '—' }}
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $itemStatusColors[$item->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-gray-500 text-xs whitespace-nowrap">
                            {{ $item->processed_at ? $item->processed_at->format('M d, g:i A') : '—' }}
                        </td>
                        <td class="px-3 py-3">
                            @if($item->status === 'failed')
                            <button @click="open = !open" class="text-red-500 hover:text-red-700" title="View error">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                            </button>
                            @endif
                        </td>
                        <template x-if="open">
                            <td colspan="8" class="px-3 py-3 bg-gray-50/70">
                                <div class="text-xs space-y-1">
                                    @if($item->error_message)
                                    <p class="text-red-600"><strong>Error:</strong> {{ $item->error_message }}</p>
                                    @endif
                                    @if(count($changedFields))
                                    <div>
                                        <p class="text-gray-500 font-medium mb-1">Fields updated</p>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($changedFields as $field)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $field }}
                                                @if($field === 'selling_price' && array_key_exists('selling_price', $payload))
                                                    ₦{{ number_format((float) $payload['selling_price'], 2) }}
                                                @elseif($field === 'quantity' && array_key_exists('quantity', $payload))
                                                    x{{ (int) $payload['quantity'] }}
                                                @endif
                                            </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    @if(is_array($item->payload) && count($item->payload))
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-gray-500 font-medium">Payload</summary>
                                        <pre class="mt-2 bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto font-mono text-[10px] whitespace-pre-wrap">{{ json_encode($item->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                    @endif
                                </div>
                            </td>
                        </template>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="border-t border-gray-100 px-4 py-4">
            {{ $items->links('components.pagination') }}
        </div>
        @endif
        @else
        <div class="px-6 py-12 text-center">
            <i data-lucide="package" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500">No items found for this filter.</p>
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<script>
function posSyncDetailPage() {
    return {
        retrying: false,
        async retryBatch() {
            this.retrying = true;
            try {
                const res = await fetch('{{ route("admin.pos-sync.retry", $batch) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message || 'No failed items to retry.', 'info');
                }
            } catch (e) {
                alert('Failed to retry batch.');
            }
            this.retrying = false;
        }
    };
}
</script>
@endsection
