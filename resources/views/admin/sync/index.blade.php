@extends('layouts.admin')

@section('title', 'Data Sync')
@section('header', 'Data Sync')

@section('content')
<div x-data="syncBulkManager()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Data Sync</h2>
            <p class="text-sm text-gray-500 mt-1">Synchronize data from your POS system.</p>
        </div>
    </div>

    @include('components.admin-search-filter')

    {{-- Live Progress Panel --}}
    @if($inProgressLog)
    <div x-data="syncProgress({{ $inProgressLog->id }})" x-init="startPolling()" class="mb-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                     :class="status === 'cancelling' ? 'bg-red-100' : 'bg-amber-100'">
                    <i :data-lucide="status === 'cancelling' ? 'x-circle' : 'loader-2'"
                       class="w-4 h-4 animate-spin"
                       :class="status === 'cancelling' ? 'text-red-600' : 'text-amber-600'"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900" x-text="modeLabel + ' — In Progress'"></p>
                    <p class="text-xs text-gray-500" x-text="rowsProcessed.toLocaleString() + ' / ' + rowsTotal.toLocaleString() + ' rows (' + progress + '%)'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="cancelSync()" x-show="status === 'in_progress'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    Cancel
                </button>
                <span x-show="status === 'cancelling'" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                    Cancelling...
                </span>
            </div>
        </div>
        <div class="px-5 py-4">
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 ease-out"
                     :class="status === 'cancelling' ? 'bg-red-500' : 'bg-emerald-500'"
                     :style="'width: ' + progress + '%'"></div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 mt-3 text-xs text-gray-500">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Created: <strong x-text="rowsCreated.toLocaleString()" class="text-emerald-700"></strong>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Updated: <strong x-text="rowsUpdated.toLocaleString()" class="text-blue-700"></strong>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Failed: <strong x-text="rowsFailed.toLocaleString()" :class="rowsFailed > 0 ? 'text-red-700' : 'text-gray-700'"></strong>
                    </span>
                </div>
                <span x-text="elapsed" class="font-mono"></span>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk Actions Bar --}}
    <div x-show="selectedIds.length > 0" x-cloak x-transition
         class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-emerald-700" x-text="selectedIds.length + ' log(s) selected'"></span>
            <button @click="selectedIds = []; allSelected = false" class="text-xs text-emerald-600 hover:text-emerald-800 underline font-medium">Deselect All</button>
        </div>
        <div class="flex items-center gap-2">
            <button @click="bulkDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Selected
            </button>
        </div>
    </div>

    {{-- Sync Buttons --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8"
         x-data="{ disabled: {{ $inProgressLog ? 'true' : 'false' }} }">

        {{-- Button 1: Price Update --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col">
            <form method="POST" action="{{ route('admin.sync.trigger') }}" x-data="{ syncing: false }" @submit="syncing = true; disabled = true" class="flex flex-col h-full">
                @csrf
                <input type="hidden" name="mode" value="price">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-500/10">
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-blue-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Price Update</h3>
                        <p class="text-xs text-gray-500">Cost &amp; selling prices only</p>
                    </div>
                </div>
                <button type="submit" :disabled="syncing || disabled" class="mt-auto w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="syncing && 'animate-spin'"></i>
                    <span x-text="syncing ? 'Syncing...' : 'Run Sync'">Run Sync</span>
                </button>
            </form>
        </div>

        {{-- Button 2: Quantity Update --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col">
            <form method="POST" action="{{ route('admin.sync.trigger') }}" x-data="{ syncing: false }" @submit="syncing = true; disabled = true" class="flex flex-col h-full">
                @csrf
                <input type="hidden" name="mode" value="qty">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-500/10">
                        <i data-lucide="package" class="w-5 h-5 text-amber-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Quantity Update</h3>
                        <p class="text-xs text-gray-500">Stock quantities only</p>
                    </div>
                </div>
                <button type="submit" :disabled="syncing || disabled" class="mt-auto w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-amber-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="syncing && 'animate-spin'"></i>
                    <span x-text="syncing ? 'Syncing...' : 'Run Sync'">Run Sync</span>
                </button>
            </form>
        </div>

        {{-- Button 3: New Items --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col">
            <form method="POST" action="{{ route('admin.sync.trigger') }}" x-data="{ syncing: false }" @submit="syncing = true; disabled = true" class="flex flex-col h-full">
                @csrf
                <input type="hidden" name="mode" value="new">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-purple-500/10">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-purple-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">New Items</h3>
                        <p class="text-xs text-gray-500">Import new items only</p>
                    </div>
                </div>
                <button type="submit" :disabled="syncing || disabled" class="mt-auto w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-purple-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="syncing && 'animate-spin'"></i>
                    <span x-text="syncing ? 'Syncing...' : 'Run Sync'">Run Sync</span>
                </button>
            </form>
        </div>

        {{-- Button 4: Sync All --}}
        <div class="bg-white rounded-xl shadow-sm border-2 border-emerald-200 p-6 flex flex-col">
            <form method="POST" action="{{ route('admin.sync.trigger') }}" x-data="{ syncing: false }" @submit="syncing = true; disabled = true" class="flex flex-col h-full">
                @csrf
                <input type="hidden" name="mode" value="all">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-emerald-500/10">
                        <i data-lucide="zap" class="w-6 h-6 text-emerald-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 text-base">Sync All</h3>
                        <p class="text-xs text-gray-500">Prices + quantities + new items</p>
                    </div>
                </div>
                <button type="submit" :disabled="syncing || disabled" class="mt-auto w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-emerald-600">
                    <i data-lucide="refresh-cw" class="w-4 h-4" :class="syncing && 'animate-spin'"></i>
                    <span x-text="syncing ? 'Syncing...' : 'Run Sync'">Run Sync</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Manage Variation Quantities --}}
    <div class="mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-amber-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-500/10">
                        <i data-lucide="package-plus" class="w-5 h-5 text-amber-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Manage Variation Quantities</h3>
                        <p class="text-xs text-gray-500">Edit quantities for items with variation IDs (e.g. 1070@3467)</p>
                    </div>
                </div>
                <a href="{{ route('admin.items.variations.manage') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 transition-colors shadow-sm">
                    <i data-lucide="edit-3" class="w-4 h-4"></i> Manage Variations
                </a>
            </div>
        </div>
    </div>

    {{-- Sync History --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-4 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900" style="font-family:'Poppins',sans-serif;">Sync History</h3>
        </div>

        @if($logs->count())

        {{-- Desktop Table --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-12">ID</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Source</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-20">Mode</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-20">Progress</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-16">Processed</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-14">Created</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-14">Updated</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-500 w-14">Failed</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-24">Status</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500 w-28">Date</th>
                        <th class="px-3 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($logs as $log)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-3 py-4 w-10">
                            <input type="checkbox" :value="{{ $log->id }}" x-model="selectedIds" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </td>
                        <td class="px-3 py-3 font-mono text-gray-500">{{ $log->id }}</td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                                <span class="font-medium text-gray-900 truncate max-w-[180px]">{{ $log->file_name }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            @php
                                $modeLabels = [
                                    'price' => ['label' => 'Price', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                    'qty' => ['label' => 'Qty', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                    'new' => ['label' => 'New', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
                                    'all' => ['label' => 'All', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                ];
                                $modeData = $modeLabels[$log->mode ?? 'all'] ?? ['label' => ucfirst($log->mode ?? 'N/A'), 'class' => 'bg-gray-50 text-gray-700 border-gray-200'];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $modeData['class'] }}">{{ $modeData['label'] }}</span>
                        </td>
                        <td class="px-3 py-3">
                            @php $pct = $log->rows_total > 0 ? min(100, round(($log->rows_processed / $log->rows_total) * 100)) : 0; @endphp
                            <div class="w-full bg-gray-100 rounded-full h-1.5 min-w-[60px]">
                                <div class="h-full rounded-full {{ $log->status === 'success' ? 'bg-emerald-500' : ($log->status === 'failed' || $log->status === 'cancelled' ? 'bg-red-500' : 'bg-amber-500') }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 mt-0.5 block text-center">{{ $pct }}%</span>
                        </td>
                        <td class="px-3 py-3 text-center text-gray-700">{{ number_format($log->rows_processed ?? 0) }}</td>
                        <td class="px-3 py-3 text-center"><span class="text-emerald-700 font-medium">{{ number_format($log->rows_created ?? 0) }}</span></td>
                        <td class="px-3 py-3 text-center"><span class="text-blue-700 font-medium">{{ number_format($log->rows_updated ?? 0) }}</span></td>
                        <td class="px-3 py-3 text-center">
                            @php $fc = ($log->rows_failed ?? 0); @endphp
                            <button
                                @click="$dispatch('show-failures', { id: {{ $log->id }} })"
                                class="{{ $fc > 0 ? 'text-red-600 font-semibold hover:underline cursor-pointer' : 'text-gray-400' }}">
                                {{ $fc }}
                            </button>
                        </td>
                        <td class="px-3 py-3">
                            @php
                                $logStatusColors = [
                                    'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'failed' => 'bg-red-50 text-red-700 border-red-200',
                                    'in_progress' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                    'cancelling' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    'cancelled' => 'bg-gray-50 text-gray-600 border-gray-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $logStatusColors[$log->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                @if($log->status === 'in_progress')
                                    <i data-lucide="loader-2" class="w-3 h-3 animate-spin mr-1"></i>
                                @endif
                                {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-gray-500 text-xs whitespace-nowrap">
                            {{ $log->created_at ? $log->created_at->format('M d, g:i A') : 'N/A' }}
                        </td>
                        <td class="px-3 py-3">
                            @if(!in_array($log->status, ['in_progress', 'cancelling']))
                            <button @click="if(confirm('Delete this sync log?')) deleteSingle({{ $log->id }})"
                                class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card Layout --}}
        <div class="lg:hidden divide-y divide-gray-100">
            @foreach($logs as $log)
            @php $pct = $log->rows_total > 0 ? min(100, round(($log->rows_processed / $log->rows_total) * 100)) : 0; @endphp
            <div class="p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" :value="{{ $log->id }}" x-model="selectedIds" class="w-4 h-4 text-emerald-500 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer">
                        @php
                            $modeLabels = [
                                'price' => ['label' => 'Price', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                'qty' => ['label' => 'Qty', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                'new' => ['label' => 'New', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
                                'all' => ['label' => 'All', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            ];
                            $modeData = $modeLabels[$log->mode ?? 'all'] ?? ['label' => ucfirst($log->mode ?? 'N/A'), 'class' => 'bg-gray-50 text-gray-700 border-gray-200'];
                            $logStatusColors = [
                                'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'failed' => 'bg-red-50 text-red-700 border-red-200',
                                'in_progress' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                'cancelling' => 'bg-orange-50 text-orange-700 border-orange-200',
                                'cancelled' => 'bg-gray-50 text-gray-600 border-gray-200',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $modeData['class'] }}">{{ $modeData['label'] }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $logStatusColors[$log->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                            {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">#{{ $log->id }}</span>
                </div>

                {{-- Mini progress bar --}}
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-full rounded-full {{ $log->status === 'success' ? 'bg-emerald-500' : ($log->status === 'failed' || $log->status === 'cancelled' ? 'bg-red-500' : 'bg-amber-500') }}" style="width: {{ $pct }}%"></div>
                </div>

                <div class="grid grid-cols-4 gap-1.5 text-xs">
                    <div class="text-center bg-gray-50 rounded-lg py-1.5">
                        <span class="block text-gray-400 text-[10px]">Total</span>
                        <span class="font-medium text-gray-900">{{ number_format($log->rows_total ?? 0) }}</span>
                    </div>
                    <div class="text-center bg-emerald-50 rounded-lg py-1.5">
                        <span class="block text-emerald-500 text-[10px]">Created</span>
                        <span class="font-medium text-emerald-700">{{ number_format($log->rows_created ?? 0) }}</span>
                    </div>
                    <div class="text-center bg-blue-50 rounded-lg py-1.5">
                        <span class="block text-blue-500 text-[10px]">Updated</span>
                        <span class="font-medium text-blue-700">{{ number_format($log->rows_updated ?? 0) }}</span>
                    </div>
                    <div class="text-center bg-red-50 rounded-lg py-1.5">
                        <span class="block text-red-500 text-[10px]">Failed</span>
                        <button @click="$dispatch('show-failures', { id: {{ $log->id }} })"
                                class="font-medium {{ ($log->rows_failed ?? 0) > 0 ? 'text-red-700 underline' : 'text-gray-500' }}">
                            {{ number_format($log->rows_failed ?? 0) }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>{{ $log->created_at ? $log->created_at->format('M d, Y g:i A') : 'N/A' }}</span>
                    @if(!in_array($log->status, ['in_progress', 'cancelling']))
                    <button @click="if(confirm('Delete this sync log?')) deleteSingle({{ $log->id }})"
                        class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if($logs->hasPages())
        <div class="border-t border-gray-100 px-4 py-4">
            {{ $logs->links('components.pagination') }}
        </div>
        @endif
        @else
        <div class="px-6 py-12 text-center">
            <i data-lucide="refresh-cw" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-sm text-gray-500 mb-1">No sync logs yet.</p>
            <p class="text-xs text-gray-400">Trigger a sync to get started.</p>
        </div>
        @endif
    </div>

    {{-- Failure Details Modal --}}
    <div x-data="failureModal()" x-on:show-failures.window="openModal($event.detail.id)"
         x-show="show" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="fixed inset-0 bg-black/50" @click="show = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="font-semibold text-gray-900">Sync #<span x-text="logId"></span> — Failure Details</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="errorMessage || 'No error message'"></p>
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
                <template x-if="!loading && errors.length === 0">
                    <div class="text-center py-8">
                        <i data-lucide="check-circle" class="w-10 h-10 text-emerald-400 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500">No individual row failures recorded.</p>
                    </div>
                </template>
                <template x-if="!loading && errors.length > 0">
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 mb-3" x-text="errors.length + ' failure(s) recorded'"></p>
                        <template x-for="(err, i) in errors" :key="i">
                            <div class="bg-red-50 border border-red-100 rounded-lg p-3 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                                                  :class="err.action === 'create' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                  x-text="err.action"></span>
                                            <span class="text-gray-500 text-xs">Row <span x-text="err.row"></span></span>
                                        </div>
                                        <p class="font-medium text-gray-900 truncate" x-text="err.item"></p>
                                        <p class="text-xs text-red-600 mt-1" x-text="err.error"></p>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-mono whitespace-nowrap" x-text="'POS-' + err.pos_id"></span>
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
function syncProgress(logId) {
    return {
        logId,
        status: 'in_progress',
        mode: '',
        modeLabel: '',
        rowsTotal: 0,
        rowsProcessed: 0,
        rowsCreated: 0,
        rowsUpdated: 0,
        rowsFailed: 0,
        progress: 0,
        elapsed: '0s',
        errorMessage: null,
        startTime: Date.now(),
        timer: null,
        pollTimer: null,

        startPolling() {
            this.startTime = Date.now();
            this.timer = setInterval(() => {
                const secs = Math.floor((Date.now() - this.startTime) / 1000);
                const m = Math.floor(secs / 60);
                const s = secs % 60;
                this.elapsed = m > 0 ? m + 'm ' + s + 's' : s + 's';
            }, 1000);

            this.poll();
            this.pollTimer = setInterval(() => this.poll(), 2000);
        },

        async poll() {
            try {
                const res = await fetch('{{ route("admin.sync.status", "__ID__") }}'.replace('__ID__', this.logId));
                if (!res.ok) return;
                const data = await res.json();

                this.status = data.status;
                this.mode = data.mode;
                this.rowsTotal = data.rows_total;
                this.rowsProcessed = data.rows_processed;
                this.rowsCreated = data.rows_created;
                this.rowsUpdated = data.rows_updated;
                this.rowsFailed = data.rows_failed;
                this.progress = data.progress;
                this.errorMessage = data.error_message;

                const modeLabels = { all: 'Full Sync', price: 'Price Sync', qty: 'Quantity Sync', new: 'New Items Sync' };
                this.modeLabel = modeLabels[data.mode] || data.mode;

                if (['success', 'failed', 'cancelled'].includes(data.status)) {
                    clearInterval(this.pollTimer);
                    clearInterval(this.timer);
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (e) {}
        },

        async cancelSync() {
            if (!confirm('Are you sure you want to cancel this sync?')) return;
            try {
                await fetch('{{ route("admin.sync.cancel", "__ID__") }}'.replace('__ID__', this.logId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.status = 'cancelling';
            } catch (e) {
                alert('Failed to cancel sync.');
            }
        },

        destroy() {
            clearInterval(this.timer);
            clearInterval(this.pollTimer);
        }
    }
}

function failureModal() {
    return {
        show: false,
        logId: null,
        loading: false,
        errorMessage: null,
        errors: [],

        async openModal(id) {
            this.logId = id;
            this.show = true;
            this.loading = true;
            this.errors = [];
            this.errorMessage = null;

            try {
                const res = await fetch('{{ route("admin.sync.failures", "__ID__") }}'.replace('__ID__', id));
                const data = await res.json();
                this.errorMessage = data.error_message;
                this.errors = data.error_details || [];
            } catch (e) {
                this.errorMessage = 'Failed to load failure details.';
            }
            this.loading = false;

            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        }
    }
}
</script>

<script>
function syncBulkManager() {
    return {
        selectedIds: [],
        allSelected: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,

        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = @js($logs->pluck('id')->toArray());
                this.allSelected = true;
            } else {
                this.selectedIds = [];
                this.allSelected = false;
            }
        },

        async deleteSingle(id) {
            try {
                const res = await fetch('{{ route("admin.sync.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ action: 'delete', ids: [id] }),
                });
                const data = await res.json();
                if (data.success) window.location.reload();
                else alert(data.message || 'An error occurred.');
            } catch (e) {
                alert('An error occurred. Please try again.');
            }
        },

        async bulkDelete() {
            if (!confirm('Delete selected sync logs? In-progress syncs will be skipped.')) return;
            try {
                const res = await fetch('{{ route("admin.sync.bulk") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ action: 'delete', ids: this.selectedIds }),
                });
                const data = await res.json();
                if (data.success) window.location.reload();
                else alert(data.message || 'An error occurred.');
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
