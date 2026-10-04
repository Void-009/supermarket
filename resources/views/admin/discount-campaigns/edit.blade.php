@extends('layouts.admin')

@section('title', 'Edit Campaign')
@section('header', 'Edit Discount Campaign')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.discount-campaigns.update', $discountCampaign) }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6 space-y-5">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Campaign Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $discountCampaign->name) }}" required
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description (optional)</label>
                <textarea id="description" name="description" rows="2"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all resize-none">{{ old('description', $discountCampaign->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="discount_percentage" class="block text-sm font-medium text-gray-700 mb-1.5">Discount %</label>
                    <div class="relative">
                        <input type="number" id="discount_percentage" name="discount_percentage" value="{{ old('discount_percentage', $discountCampaign->discount_percentage) }}" required min="0" max="100" step="0.01"
                            class="w-full pl-4 pr-8 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                    @error('discount_percentage') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Active</label>
                    <label class="relative inline-flex items-center cursor-pointer mt-2">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $discountCampaign->is_active) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="ml-3 text-sm text-gray-600">Active</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1.5">Start Date (optional)</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $discountCampaign->starts_at ? $discountCampaign->starts_at->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1.5">End Date (optional)</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $discountCampaign->ends_at ? $discountCampaign->ends_at->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6" x-data="discountItemPicker()">
            @php $selectedIds = old('item_ids', $discountCampaign->items->pluck('id')->toArray()); @endphp
            <label class="block text-sm font-medium text-gray-700 mb-3">Select Items for Discount</label>

            <div class="flex flex-col sm:flex-row gap-3 mb-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="search" @input="refreshCounts()" placeholder="Search by name, item ID or barcode..."
                        class="w-full pl-9 pr-9 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    <button type="button" x-show="search" @click="search = ''; refreshCounts()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-full">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <select x-model="category" @change="refreshCounts()"
                    class="w-full sm:w-56 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all bg-white text-gray-700">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-between mb-2 text-xs text-gray-500">
                <span x-text="countLabel"></span>
                <span x-text="selectedLabel"></span>
            </div>

            <div class="max-h-80 overflow-y-auto border border-gray-200 rounded-xl divide-y divide-gray-100">
                @forelse($items as $item)
                @php
                    $searchText = strtolower(trim(implode(' ', array_filter([
                        $item->name,
                        $item->display_name,
                        $item->pos_item_id,
                        ...$item->barcodes->pluck('barcode_value')->all(),
                    ]))));
                    $itemCategory = $item->category?->name ?? 'Uncategorized';
                @endphp
                <label x-show="matchesItem($el)" data-item-row data-search="{{ e($searchText) }}" data-category="{{ e($itemCategory) }}"
                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer">
                    <input type="checkbox" name="item_ids[]" value="{{ $item->id }}"
                        class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500"
                        @change="refreshCounts()"
                        {{ in_array($item->id, $selectedIds) ? 'checked' : '' }}>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item->display_name }}</p>
                        <p class="text-xs text-gray-500">₦{{ number_format($item->selling_price, 2) }}</p>
                    </div>
                    @if($item->isDiscounted())
                        <span class="shrink-0 whitespace-nowrap text-xs text-emerald-600 font-medium bg-emerald-50 px-2 py-1 rounded-lg">Already on discount</span>
                    @endif
                </label>
                @empty
                <p class="px-4 py-8 text-center text-gray-400">No visible items found.</p>
                @endforelse
                <p x-show="noResults" class="px-4 py-8 text-center text-gray-400">No items match your search.</p>
            </div>
            @error('item_ids') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            @error('item_ids.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition-colors whitespace-nowrap">Update Campaign</button>
            <a href="{{ route('admin.discount-campaigns.index') }}" class="px-6 py-2.5 text-sm text-gray-600 hover:text-gray-900 font-medium transition-colors whitespace-nowrap">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function discountItemPicker() {
    return {
        search: '',
        category: '',
        totalCount: 0,
        shownCount: 0,
        selectedCount: 0,
        hasItems: false,

        init() {
            this.totalCount = document.querySelectorAll('[data-item-row]').length;
            this.hasItems = this.totalCount > 0;
            this.refreshCounts();
        },

        matchesItem(el) {
            if (this.category && (el.dataset.category || 'Uncategorized') !== this.category) return false;
            const q = this.search.trim().toLowerCase();
            if (!q) return true;
            return (el.dataset.search || '').indexOf(q) !== -1;
        },

        refreshCounts() {
            const rows = document.querySelectorAll('[data-item-row]');
            let shown = 0;
            for (let i = 0; i < rows.length; i++) {
                if (this.matchesItem(rows[i])) shown++;
            }
            this.shownCount = shown;
            this.selectedCount = document.querySelectorAll('input[name="item_ids[]"]:checked').length;
        },

        get countLabel() {
            return this.shownCount + ' of ' + this.totalCount + ' items';
        },

        get selectedLabel() {
            return this.selectedCount + ' selected';
        },

        get noResults() {
            return this.hasItems && this.shownCount === 0;
        }
    }
}
</script>
@endpush
