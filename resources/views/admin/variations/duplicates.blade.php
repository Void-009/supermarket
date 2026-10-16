@extends('layouts.admin')

@section('title', 'Duplicate Variations')
@section('header', 'Duplicate Variations')

@section('content')
<div x-data="duplicatesPage()" x-init="init()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Duplicate Variations</h2>
            <p class="text-sm text-gray-500 mt-1">Groups below are the same product listed twice by the POS (same name + same cost). Pick the row to keep; everything (images, favourites, orders) moves onto it.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.items.variations.manage') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Variation Quantities
            </a>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="copy" class="w-4 h-4 text-red-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-gray-500 font-medium">Duplicate groups</p>
                    <p class="text-lg font-bold text-gray-900" x-text="groups.length">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="archive" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-amber-600 font-medium">Deleted in POS</p>
                    <p class="text-lg font-bold text-amber-700" x-text="tombstoned.length">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="eye-off" class="w-4 h-4 text-gray-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-gray-600 font-medium">Hidden by admin</p>
                    <p class="text-lg font-bold text-gray-700" x-text="adminHidden.length">0</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="link-2" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-[11px] text-emerald-600 font-medium">Linked ids</p>
                    <p class="text-lg font-bold text-emerald-700" x-text="aliasCount">0</p>
                </div>
            </div>
        </div>
    </div>

    <div x-show="loading" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400 text-sm">Loading…</div>

    {{-- Duplicate groups --}}
    <div x-show="!loading && groups.length > 0" class="space-y-4 mb-8">
        <template x-for="group in groups" :key="group.parent_item_number + '|' + group.name">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="font-semibold text-gray-900" x-text="group.name"></span>
                        <span class="ml-2 text-xs text-gray-500">parent <code class="bg-gray-200 px-1 rounded" x-text="group.parent_item_number"></code></span>
                    </div>
                    <span class="text-xs font-medium text-red-600" x-text="group.items.length + ' copies'"></span>
                </div>
                <div class="divide-y divide-gray-50">
                    <template x-for="row in group.items" :key="row.id">
                        <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                            <label class="flex items-center gap-2 sm:w-64 shrink-0 cursor-pointer">
                                <input type="radio" :name="'survivor-' + group.parent_item_number + '-' + row.id + Math.random()" :checked="row.id === group.items[0].id" @change="survivorPick[groupKey(group)] = row.id" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm font-mono text-gray-800" x-text="row.pos_item_id"></span>
                            </label>
                            <div class="flex-1 text-xs text-gray-600 flex flex-wrap gap-x-4 gap-y-1">
                                <span>Price ₦<span x-text="formatMoney(row.selling_price)"></span></span>
                                <span>Cost ₦<span x-text="formatMoney(row.cost_price)"></span></span>
                                <span x-show="row.has_image" class="text-emerald-600">has image</span>
                                <span x-show="row.has_description" class="text-emerald-600">has description</span>
                                <span x-show="row.orders_count > 0" x-text="row.orders_count + ' order(s)'"></span>
                                <span x-show="row.favorites_count > 0" x-text="row.favorites_count + ' favourite(s)'"></span>
                                <span class="text-gray-400" x-text="'last edit: ' + (row.last_modified || 'n/a')"></span>
                                <span x-show="row.hidden_by_admin" class="text-red-500 font-medium">hidden by admin</span>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">Selected row keeps its photos, favourites and orders; the others are hidden and stay hidden.</p>
                    <button @click="mergeGroup(group)" :disabled="busy" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50">
                        Merge others into selected
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!loading && groups.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center mb-8">
        <i data-lucide="check-circle-2" class="w-10 h-10 text-emerald-500 mx-auto mb-3"></i>
        <p class="text-sm text-gray-600 font-medium">No duplicate variations found.</p>
        <p class="text-xs text-gray-400 mt-1">Every variation name + cost combination under a parent is unique.</p>
    </div>

    {{-- Deleted in POS (tombstoned) --}}
    <div x-show="!loading && tombstoned.length > 0" class="mb-8">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <i data-lucide="archive" class="w-4 h-4 text-amber-500"></i>
            Deleted in POS (hidden, kept for restore) <span class="text-gray-400 font-normal" x-text="'(' + tombstoned.length + ')'"></span>
        </h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-50">
            <template x-for="row in tombstoned" :key="row.id">
                <div class="px-4 py-2.5 flex flex-wrap items-center gap-3 text-xs">
                    <span class="font-mono text-gray-800 sm:w-48" x-text="row.pos_item_id"></span>
                    <span class="text-gray-600 flex-1" x-text="row.name"></span>
                    <span class="text-gray-400" x-text="'gone since ' + (row.pos_missing_since || 'unknown')"></span>
                    <span x-show="row.orders_count > 0" class="text-gray-400" x-text="row.orders_count + ' order(s)'"></span>
                    <button @click="unhide(row)" :disabled="busy" class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors disabled:opacity-50">Show again</button>
                </div>
            </template>
        </div>
    </div>

    {{-- Hidden by admin --}}
    <div x-show="!loading && adminHidden.length > 0">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <i data-lucide="eye-off" class="w-4 h-4 text-gray-400"></i>
            Hidden by admin <span class="text-gray-400 font-normal" x-text="'(' + adminHidden.length + ')'"></span>
        </h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-50">
            <template x-for="row in adminHidden" :key="row.id">
                <div class="px-4 py-2.5 flex flex-wrap items-center gap-3 text-xs">
                    <span class="font-mono text-gray-800 sm:w-48" x-text="row.pos_item_id"></span>
                    <span class="text-gray-600 flex-1" x-text="row.name"></span>
                    <button @click="unhide(row)" :disabled="busy" class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors disabled:opacity-50">Show again</button>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function duplicatesPage() {
    return {
        loading: true,
        busy: false,
        groups: [],
        tombstoned: [],
        adminHidden: [],
        aliasCount: 0,
        survivorPick: {},
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        init() {
            this.fetchData();
        },

        groupKey(group) {
            return group.parent_item_number + '|' + group.name;
        },

        formatMoney(value) {
            return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async fetchData() {
            this.loading = true;
            try {
                const res = await fetch('{{ route("admin.items.variations.duplicates.data") }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.groups = data.groups;
                    this.tombstoned = data.tombstoned;
                    this.adminHidden = data.admin_hidden;
                    this.aliasCount = data.alias_count;
                    this.survivorPick = {};
                    data.groups.forEach(g => { this.survivorPick[this.groupKey(g)] = g.items[0].id; });
                }
            } catch (e) {
                showToast('Could not load duplicates.', 'error');
            }
            this.loading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async mergeGroup(group) {
            const survivorId = this.survivorPick[this.groupKey(group)];
            const losers = group.items.filter(i => i.id !== survivorId);
            if (losers.length === 0) return;

            if (!confirm('Merge ' + losers.length + ' cop' + (losers.length === 1 ? 'y' : 'ies') + ' into the selected row? Their images, favourites and orders move across; the merged rows are hidden permanently.')) {
                return;
            }

            this.busy = true;
            for (const loser of losers) {
                try {
                    const res = await fetch('{{ route("admin.items.variations.duplicates.merge") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ survivor_id: survivorId, loser_id: loser.id }),
                    });
                    const data = await res.json();
                    if (!data.success) {
                        showToast(data.message || 'Merge failed.', 'error');
                    }
                } catch (e) {
                    showToast('Merge failed.', 'error');
                }
            }
            this.busy = false;
            showToast('Merged.', 'success');
            this.fetchData();
        },

        async unhide(row) {
            this.busy = true;
            try {
                const res = await fetch('{{ route("admin.items.variations.duplicates.unhide", ["item" => "__ID__"]) }}'.replace('__ID__', row.id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                showToast(data.message || (data.success ? 'Done.' : 'Failed.'), data.success ? 'success' : 'error');
            } catch (e) {
                showToast('Failed.', 'error');
            }
            this.busy = false;
            this.fetchData();
        },
    };
}
</script>
@endpush
