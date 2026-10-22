@extends('layouts.admin')

@section('title', 'Items Without Images')
@section('header', 'Items Without Images')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">Items Without Images</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    {{ number_format($totalMissing) }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Add photos to these items so customers can see what they are buying.
                Variations whose parent already has an image are not listed.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.items.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Items
            </a>
            <a href="{{ route('admin.items.variations.manage') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-amber-500 rounded-xl hover:bg-amber-600 transition-colors shadow-sm">
                <i data-lucide="package-plus" class="w-4 h-4"></i> Manage Variations
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.items.missing-images') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, item number or barcode..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors">
                </div>
                <div class="sm:w-56">
                    <select name="category_id" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }} ({{ $cat->items_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:w-48">
                    <select name="sort" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price Low-High</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price High-Low</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Stock Low-High</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Stock High-Low</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="sm:w-44">
                    <select name="stock_status" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">All Stock</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div class="sm:w-44">
                    <select name="visibility" class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white">
                        <option value="">All Visibility</option>
                        <option value="visible" {{ request('visibility') === 'visible' ? 'selected' : '' }}>Visible</option>
                        <option value="hidden" {{ request('visibility') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                    </select>
                </div>
                <div class="flex gap-2 sm:ml-auto">
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors">Filter</button>
                    @if(request()->hasAny(['search', 'category_id', 'sort', 'stock_status', 'visibility']))
                        <a href="{{ route('admin.items.missing-images') }}" class="px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl transition-colors">Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Items Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="text-sm text-gray-500">
                Showing <span class="font-semibold text-gray-900">{{ number_format($items->firstItem() ?? 0) }}</span>
                &ndash; <span class="font-semibold text-gray-900">{{ number_format($items->lastItem() ?? 0) }}</span>
                of <span class="font-semibold text-gray-900">{{ number_format($items->total()) }}</span> matching items
            </p>
            @if($items->total() !== $totalMissing)
                <p class="text-xs text-gray-400">{{ number_format($totalMissing) }} items without images in total.</p>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Image</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Item</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Price</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Stock</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-500">Added</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($items as $item)
                        @php
                            $isVariation = $item->isVariation();
                            $stock = $isVariation ? $item->effectiveQuantity() : (int) $item->quantity;
                            $stockColor = $stock > 10 ? 'text-emerald-700' : ($stock > 0 ? 'text-yellow-700' : 'text-red-700');
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-3 py-4">
                                <div class="w-12 h-12 rounded-lg bg-amber-50 flex items-center justify-center border border-amber-200" title="No image">
                                    <i data-lucide="image-off" class="w-5 h-5 text-amber-500"></i>
                                </div>
                            </td>
                            <td class="px-3 py-4 max-w-[240px]">
                                <div class="font-medium text-gray-900 break-words">{{ $item->display_name }}</div>
                                @if($item->display_name !== $item->name)
                                    <div class="text-[11px] text-gray-400 break-words">Default: {{ $item->name }}</div>
                                @endif
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[11px] text-gray-400 break-all">{{ $item->pos_item_id ?: $item->slug }}</span>
                                    @if($isVariation)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">Variation</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4 text-gray-600 max-w-[140px] break-words">{{ $item->category->name ?? 'N/A' }}</td>
                            <td class="px-3 py-4 font-medium text-gray-900 whitespace-nowrap">
                                ₦{{ number_format($item->selling_price, 2) }}
                                @if($item->discount_is_active && $item->discount_percentage)
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">-{{ $item->discount_percentage }}%</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                <span class="font-medium {{ $stockColor }}">{{ number_format($stock) }}</span>
                                @if($isVariation)
                                    <div class="text-[10px] text-gray-400">from parent</div>
                                @endif
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                @if($item->is_visible_online)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Visible</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200">Hidden</span>
                                @endif
                                <div class="mt-1">
                                    @if($item->owned_by_pos)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">Till</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">Website</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-gray-500" title="{{ $item->created_at?->format('M d, Y g:i A') }}">
                                {{ $item->created_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-3 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                        data-upload-button
                                        onclick="document.getElementById('missing-image-input-{{ $item->id }}').click()"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors">
                                        <i data-lucide="upload" class="w-3 h-3"></i> Upload
                                    </button>
                                    <input type="file" id="missing-image-input-{{ $item->id }}" accept="image/*" class="hidden"
                                        onchange="uploadMissingImage({{ $item->id }}, this, '{{ route('admin.items.update', $item) }}')">
                                    <a href="{{ route('admin.items.index', ['search' => $item->display_name ?: $item->name]) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                                        title="Open this item in the items page to edit all its details">
                                        <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-14 text-center">
                                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                    <i data-lucide="check-circle" class="w-7 h-7 text-emerald-500"></i>
                                </div>
                                <p class="text-base font-semibold text-gray-900">No items without images</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    @if(request()->hasAny(['search', 'category_id', 'sort', 'stock_status', 'visibility']))
                                        Nothing matches these filters. Try clearing them.
                                    @else
                                        Every item has an image. Nice work!
                                    @endif
                                </p>
                                @if(request()->hasAny(['search', 'category_id', 'sort', 'stock_status', 'visibility']))
                                    <a href="{{ route('admin.items.missing-images') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors">
                                        Clear filters
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $items->links('components.pagination') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    const missingImageCsrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    async function uploadMissingImage(itemId, input, updateUrl) {
        const file = input.files && input.files[0];
        if (!file) return;

        const row = input.closest('tr');
        const button = row ? row.querySelector('[data-upload-button]') : null;
        const originalLabel = button ? button.innerHTML : '';

        if (button) {
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            button.innerHTML = '<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i> Uploading...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        try {
            const formData = new FormData();
            formData.append('image', file);

            const uploadResponse = await fetch('{{ route('admin.items.upload-image') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': missingImageCsrf,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const uploaded = await uploadResponse.json().catch(function () { return {}; });
            if (!uploadResponse.ok || !uploaded.path) {
                throw new Error(uploaded.message || 'The image could not be uploaded.');
            }

            const saveResponse = await fetch(updateUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': missingImageCsrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ image_url: uploaded.path }),
            });

            if (!saveResponse.ok) {
                const error = await saveResponse.json().catch(function () { return {}; });
                throw new Error(error.message || 'The image was uploaded but could not be saved to the item.');
            }

            if (typeof showToast === 'function') {
                showToast('Image added. Refreshing the list...', 'success');
            }
            setTimeout(function () { window.location.reload(); }, 600);
        } catch (error) {
            if (typeof showToast === 'function') {
                showToast(error.message || 'Something went wrong while uploading.', 'error');
            }
            if (button) {
                button.disabled = false;
                button.classList.remove('opacity-70', 'cursor-not-allowed');
                button.innerHTML = originalLabel;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
            input.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endpush
