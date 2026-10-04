@extends('layouts.admin')

@section('title', 'POS Sync')
@section('header', 'POS Sync')

@section('content')
<div x-data="posSyncPage()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">POS Synchronization</h2>
            <p class="text-sm text-gray-500 mt-1">Batches uploaded by the POS sync agent.</p>
        </div>
    </div>

    @include('components.admin-search-filter')

    {{-- Status Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Total Batches</span>
                <i data-lucide="layers" class="w-4 h-4 text-gray-400"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Completed</span>
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600 tabular-nums">{{ number_format($stats['completed']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Failed</span>
                <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-red-600 tabular-nums">{{ number_format($stats['failed']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">In Progress</span>
                <i data-lucide="loader-2" class="w-4 h-4 text-amber-500"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-amber-600 tabular-nums">{{ number_format($stats['processing']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500">Items Synced (24h)</span>
                <i data-lucide="package-check" class="w-4 h-4 text-blue-500"></i>
            </div>
            <p class="text-xl sm:text-2xl font-bold text-blue-600 tabular-nums">{{ number_format($stats['items_processed_24h']) }}</p>
        </div>
    </div>

    {{-- Agent Status --}}
    @php
        $agentOnline = $agentStatus && $agentStatus->last_seen_at && $agentStatus->last_seen_at->gte(now()->subMinutes(2));
    @endphp
    <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $agentOnline ? 'bg-emerald-400' : 'bg-red-400' }} opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 {{ $agentOnline ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
            </span>
            <div>
                <p class="text-sm font-semibold {{ $agentOnline ? 'text-emerald-700' : 'text-red-600' }}">POS Agent: {{ $agentOnline ? 'ONLINE' : 'OFFLINE' }}</p>
                <p class="text-xs text-gray-500">
                    Last seen:
                    @if($agentStatus && $agentStatus->last_seen_at)
                        {{ $agentStatus->last_seen_at->diffForHumans() }}{{ $agentStatus->version ? ' (v' . $agentStatus->version . ')' : '' }}
                    @else
                        Never
                    @endif
                    &middot; Last batch:
                    {{ $stats['last_batch'] ? $stats['last_batch']->created_at->format('M d, Y g:i A') : 'Never received' }}
                </p>
            </div>
        </div>
        @if($stats['failed'] > 0)
        <div class="flex items-center gap-2 text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            {{ number_format($stats['failed']) }} batch(es) with failures — check the batch details.
        </div>
        @endif
    </div>

    {{-- API Token --}}
    <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-100 p-5" x-data="apiTokenCard()">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">POS Sync API</h3>
                <p class="text-xs text-gray-500 mt-0.5 break-words">Endpoint: <code class="font-mono break-all">{{ config('app.url') }}/api/pos/sync</code> (agent user: <code class="font-mono break-all">{{ $agentUser?->email ?? 'pos-sync@mdelight.local' }}</code>)</p>
            </div>
            <button @click="generate()" :disabled="loading"
                    class="px-3 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                Generate new token
            </button>
        </div>

        <template x-if="newToken">
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                <p class="text-xs font-semibold text-emerald-800 mb-2">New API token — copy it now, it is shown only once:</p>
                <div class="flex items-center gap-2">
                    <code x-text="newToken" class="flex-1 block font-mono text-xs text-emerald-900 bg-white border border-emerald-200 rounded-lg px-3 py-2 break-all"></code>
                    <button @click="copy(newToken)" class="px-3 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Copy</button>
                </div>
                <p class="text-xs text-amber-700 mt-2">Update the agent's <code>config.json</code> → <code>api.token</code> with this value, then restart the agent. The old token keeps working until then.</p>
            </div>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[480px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-2 text-left font-medium text-gray-500">ID</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Created</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Last used</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($tokens as $token)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs text-gray-600">{{ $token->id }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $token->created_at->format('M d, Y g:i A') }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $token->last_used_at ? $token->last_used_at->format('M d, Y g:i A') : 'Never used' }}</td>
                        <td class="px-3 py-2 text-right">
                            <button @click="revoke({{ $token->id }})" class="text-xs text-red-600 hover:underline">Revoke</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-xs text-gray-400 text-center">No API tokens yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Batches Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-4 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Recent synchronization</h3>
            <span class="text-xs text-gray-400">{{ $batches->total() }} batch(es)</span>
        </div>

        @if($batches->count())
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-14">ID</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Sync ID</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Source</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-20">Items</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-20">Processed</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-16">Failed</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-28">Status</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-32">Received</th>
                        <th class="px-3 py-3 w-28"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($batches as $batch)
                    @php
                        $statusColors = [
                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'failed' => 'bg-red-50 text-red-700 border-red-200',
                            'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            'pending' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'duplicate' => 'bg-gray-50 text-gray-600 border-gray-200',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-3 py-4 font-mono text-gray-500">{{ $batch->id }}</td>
                        <td class="px-3 py-3">
                            <span class="font-mono text-xs text-gray-600 truncate max-w-[180px] block">{{ $batch->sync_id }}</span>
                        </td>
                        <td class="px-3 py-3 text-gray-700">{{ $batch->source ?? 'pos-agent' }}</td>
                        <td class="px-3 py-3 text-center text-gray-700">{{ number_format($batch->total_items) }}</td>
                        <td class="px-3 py-3 text-center text-emerald-700 font-medium">{{ number_format($batch->processed_count) }}</td>
                        <td class="px-3 py-3 text-center">
                            <button @click="$dispatch('show-failures', { id: {{ $batch->id }} })"
                                    class="{{ $batch->failed_count > 0 ? 'text-red-600 font-semibold hover:underline cursor-pointer' : 'text-gray-400 cursor-default' }}">
                                {{ number_format($batch->failed_count) }}
                            </button>
                        </td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$batch->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                @if(in_array($batch->status, ['pending', 'processing']))
                                    <i data-lucide="loader-2" class="w-3 h-3 animate-spin mr-1"></i>
                                @endif
                                {{ ucfirst($batch->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-gray-500 text-xs whitespace-nowrap">
                            {{ $batch->created_at ? $batch->created_at->format('M d, g:i A') : 'N/A' }}
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="toggleDiff({{ $batch->id }})"
                                        class="p-2 text-gray-400 hover:text-blue-600 transition-colors"
                                        :title="diffMap[{{ $batch->id }}] ? 'Hide changes' : 'View changes'">
                                    <i data-lucide="chevron-down" class="w-4 h-4"
                                       :class="diffMap[{{ $batch->id }}] ? 'rotate-180' : ''"></i>
                                </button>
                                <a href="{{ route('admin.pos-sync.show', $batch) }}"
                                   class="p-2 text-gray-400 hover:text-emerald-600 transition-colors" title="View details">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                @if($batch->failed_count > 0)
                                <button @click="retryBatch({{ $batch->id }})"
                                        class="p-2 text-gray-400 hover:text-amber-600 transition-colors" title="Retry failed items">
                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr x-show="diffMap[{{ $batch->id }}]" x-cloak class="bg-gray-50/60">
                        <td colspan="9" class="px-3 py-3">
                            <template x-if="diffData[{{ $batch->id }}] && diffData[{{ $batch->id }}].loading">
                                <div class="flex items-center gap-2 text-xs text-gray-500 py-2">
                                    <i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i> Loading changes...
                                </div>
                            </template>
                            <template x-if="diffData[{{ $batch->id }}] && diffData[{{ $batch->id }}].error">
                                <p class="text-xs text-red-600 py-2" x-text="diffData[{{ $batch->id }}].error"></p>
                            </template>
                            <template x-if="diffData[{{ $batch->id }}] && !diffData[{{ $batch->id }}].loading && !diffData[{{ $batch->id }}].error">
                                <div>
                                    <p class="text-xs text-gray-500 mb-2" x-text="'Showing ' + diffData[{{ $batch->id }}].items.length + ' of ' + diffData[{{ $batch->id }}].total + ' item(s)'"></p>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs min-w-[560px]">
                                            <thead>
                                                <tr class="border-b border-gray-100 text-gray-400">
                                                    <th class="px-3 py-2 text-left font-medium">Item</th>
                                                    <th class="px-3 py-2 text-left font-medium w-24">Action</th>
                                                    <th class="px-3 py-2 text-left font-medium w-28">Status</th>
                                                    <th class="px-3 py-2 text-left font-medium">What changed</th>
                                                    <th class="px-3 py-2 text-left font-medium">Error</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                <template x-if="diffData[{{ $batch->id }}].items.length === 0">
                                                    <tr><td colspan="5" class="px-3 py-4 text-gray-400">No items in this batch.</td></tr>
                                                </template>
                                                <template x-for="it in diffData[{{ $batch->id }}].items" :key="it.pos_item_id">
                                                    <tr>
                                                        <td class="px-3 py-2 font-mono text-gray-600" x-text="it.pos_item_id"></td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-700" x-text="it.action"></span>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                                  :class="it.status === 'processed' ? 'bg-emerald-50 text-emerald-700' : (it.status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600')"
                                                                  x-text="it.status"></span>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <template x-if="it.changed_fields.length">
                                                                <div class="flex flex-wrap gap-1">
                                                                    <template x-for="(f, i) in it.changed_fields" :key="i">
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                                            <span x-text="f.field"></span><span class="opacity-50 mx-0.5">=</span><span class="font-semibold" x-text="f.value"></span>
                                                                        </span>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <template x-if="!it.changed_fields.length">
                                                                <span class="text-gray-400">—</span>
                                                            </template>
                                                        </td>
                                                        <td class="px-3 py-2 text-red-600" x-text="it.error_message || ''"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="lg:hidden divide-y divide-gray-100">
            @foreach($batches as $batch)
            @php
                $statusColors = [
                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'failed' => 'bg-red-50 text-red-700 border-red-200',
                    'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                    'pending' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'duplicate' => 'bg-gray-50 text-gray-600 border-gray-200',
                ];
            @endphp
            <div class="p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs text-gray-400">#{{ $batch->id }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusColors[$batch->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                            {{ ucfirst($batch->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.pos-sync.show', $batch) }}" class="p-1.5 text-gray-400 hover:text-emerald-600">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                        @if($batch->failed_count > 0)
                        <button @click="retryBatch({{ $batch->id }})" class="p-1.5 text-gray-400 hover:text-amber-600">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <p class="font-mono text-[10px] text-gray-400 truncate">{{ $batch->sync_id }}</p>
                <div class="grid grid-cols-4 gap-1.5 text-xs">
                    <div class="text-center bg-gray-50 rounded-lg py-1.5">
                        <span class="block text-gray-400 text-[10px]">Items</span>
                        <span class="font-medium text-gray-900">{{ number_format($batch->total_items) }}</span>
                    </div>
                    <div class="text-center bg-emerald-50 rounded-lg py-1.5">
                        <span class="block text-emerald-500 text-[10px]">Processed</span>
                        <span class="font-medium text-emerald-700">{{ number_format($batch->processed_count) }}</span>
                    </div>
                    <div class="text-center bg-red-50 rounded-lg py-1.5">
                        <span class="block text-red-500 text-[10px]">Failed</span>
                        <button @click="$dispatch('show-failures', { id: {{ $batch->id }} })"
                                class="font-medium {{ $batch->failed_count > 0 ? 'text-red-700 underline' : 'text-gray-500 cursor-default' }}">
                            {{ number_format($batch->failed_count) }}
                        </button>
                    </div>
                    <div class="text-center bg-gray-50 rounded-lg py-1.5">
                        <span class="block text-gray-400 text-[10px]">Time</span>
                        <span class="font-medium text-gray-900">{{ $batch->created_at ? $batch->created_at->format('g:i A') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($batches->hasPages())
        <div class="border-t border-gray-100 px-4 py-4">
            {{ $batches->links('components.pagination') }}
        </div>
        @endif
        @else
        <div class="px-6 py-12 text-center">
            <i data-lucide="radio" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500 mb-1">No POS sync batches yet.</p>
            <p class="text-xs text-gray-400">Once the agent uploads, batches will appear here.</p>
        </div>
        @endif
    </div>

    {{-- Failure Details Modal --}}
    <div x-data="failureModal()" x-on:show-failures.window="openModal($event.detail.id)"
         x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-black/50" @click="show = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="font-semibold text-gray-900">POS Batch #<span x-text="batchId"></span> — Failed Items</h3>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <template x-if="loading">
                    <div class="flex items-center justify-center py-8">
                        <i data-lucide="loader-2" class="w-6 h-6 text-gray-400 animate-spin"></i>
                    </div>
                </template>
                <template x-if="!loading && items.length === 0">
                    <div class="text-center py-8">
                        <i data-lucide="check-circle" class="w-10 h-10 text-emerald-400 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500">No failed items in this batch.</p>
                    </div>
                </template>
                <template x-if="!loading && items.length > 0">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 mb-3" x-text="items.length + ' failed item(s)'"></p>
                        <template x-for="(err, i) in items" :key="i">
                            <div class="bg-red-50 border border-red-100 rounded-lg p-3 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-700" x-text="err.action"></span>
                                            <span class="text-gray-500 text-xs font-mono" x-text="err.pos_item_id"></span>
                                        </div>
                                        <p class="text-xs text-red-600 mt-1" x-text="err.error_message || 'Unknown error'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function failureModal() {
    return {
        show: false,
        batchId: null,
        loading: false,
        items: [],
        async openModal(id) {
            this.batchId = id;
            this.show = true;
            this.loading = true;
            this.items = [];
            try {
                const res = await fetch('{{ route("admin.pos-sync.failed", "__ID__") }}'.replace('__ID__', id));
                const data = await res.json();
                this.items = data.items || [];
            } catch (e) {
                this.items = [];
            }
            this.loading = false;
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        }
    };
}
</script>

<script>
function posSyncPage() {
    return {
        diffMap: {},
        diffData: {},
        async toggleDiff(id) {
            if (this.diffMap[id]) {
                this.diffMap[id] = false;
                return;
            }
            this.diffMap[id] = true;
            if (!this.diffData[id]) {
                await this.loadDiff(id);
            }
        },
        async loadDiff(id) {
            this.diffData[id] = { loading: true, items: [], total: 0, error: null };
            try {
                const res = await fetch('{{ route("admin.pos-sync.items", "__ID__") }}'.replace('__ID__', id));
                const data = await res.json();
                this.diffData[id] = { loading: false, items: data.items || [], total: data.total || 0, error: null };
            } catch (e) {
                this.diffData[id] = { loading: false, items: [], total: 0, error: 'Failed to load items for this batch.' };
            }
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
        async retryBatch(id) {
            if (!confirm('Re-queue all failed items in this batch?')) return;
            try {
                const res = await fetch('{{ route("admin.pos-sync.retry", "__ID__") }}'.replace('__ID__', id), {
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
        }
    };
}
</script>

<script>
function apiTokenCard() {
    return {
        loading: false,
        newToken: '',
        async generate() {
            if (!confirm('Create a new POS sync API token? The current one keeps working until you update the agent.')) return;
            this.loading = true;
            try {
                const res = await fetch('{{ route("admin.pos-sync.token.create") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.newToken = data.plain_text_token;
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || 'Could not create token.', 'error');
                }
            } catch (e) {
                showToast('Failed to create token.', 'error');
            }
            this.loading = false;
        },
        async revoke(id) {
            if (!confirm('Revoke this token? The agent will stop working until it is given a new token.')) return;
            try {
                const res = await fetch('{{ route("admin.pos-sync.token.revoke", "__ID__") }}'.replace('__ID__', id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                showToast(data.message || (data.success ? 'Token revoked.' : 'Could not revoke token.'), data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                showToast('Failed to revoke token.', 'error');
            }
        },
        async copy(value) {
            try {
                await navigator.clipboard.writeText(value);
                showToast('Token copied to clipboard.', 'success');
            } catch (e) {
                showToast('Could not copy. Select and copy manually.', 'info');
            }
        }
    };
}
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection
