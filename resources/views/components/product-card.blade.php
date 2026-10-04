@props(['item'])

@php
    $displayName = $item->display_name;
    $discounted = $item->isDiscounted();
    $parentItem = $item->parent_item_number ? $item->parentItem : null;
    $variations = $item->relationLoaded('variations')
        ? $item->variations
        : ($item->isVariation()
            ? \App\Models\Item::where('parent_item_number', $item->parent_item_number)
                ->where('id', '!=', $item->id)
                ->orderBy('display_name')
                ->get()
            : collect());
    if ($item->isVariation() && $variations->doesntContain('id', $item->id)) {
        $variations->prepend($item);
    }
    if ($item->isVariation() && $parentItem && $variations->doesntContain('id', $parentItem->id)) {
        $variations->prepend($parentItem);
    }
    $itemData = json_encode([
        'id' => $item->id,
        'name' => $item->name,
        'display_name' => $displayName,
        'slug' => $item->slug,
        'selling_price' => $item->effectivePrice(),
        'compare_at_price' => $discounted ? $item->selling_price : null,
        'image_url' => $item->image_url,
        'stock' => $item->effectiveQuantity(),
        'category' => $item->category?->name,
        'is_variation' => $item->isVariation(),
        'parent' => $parentItem ? [
            'id' => $parentItem->id,
            'name' => $parentItem->name,
            'display_name' => $parentItem->display_name,
            'slug' => $parentItem->slug,
        ] : null,
        'variations' => $variations->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'display_name' => $v->display_name,
            'slug' => $v->slug,
            'selling_price' => $v->effectivePrice(),
            'compare_at_price' => $v->isDiscounted() ? $v->selling_price : null,
            'image_url' => $v->image_url,
            'stock' => (int) $v->effectiveQuantity(),
            'is_variation' => $v->isVariation(),
        ])->values(),
    ], JSON_HEX_APOS | JSON_HEX_TAG);
    $isFavorited = auth()->check() ? $item->isFavoritedBy(auth()->user()) : false;
@endphp

<div x-data="{}" class="group block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-emerald-200 cursor-pointer min-w-0 product-card" data-item='{{ $itemData }}' @click="window.openQuickView($el)" @keydown.enter="window.openQuickView($el)" role="button" tabindex="0">
    <div class="aspect-square bg-gray-50 overflow-hidden relative">
        @if($item->image_url)
            <img src="{{ asset('storage/' . $item->image_url) }}" alt="{{ $item->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                <span class="text-4xl">🛒</span>
            </div>
        @endif
        @if($item->effectiveQuantity() < 1)
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">{{ __('messages.out_of_stock') }}</span>
            </div>
        @endif
        {{-- Favorite Button --}}
        <button
            @click.stop="toggleFavorite({{ $item->id }}, $el)"
            class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300 {{ $isFavorited ? 'bg-rose-500 text-white shadow-lg' : 'bg-white/80 backdrop-blur-sm text-gray-400 hover:text-rose-500 hover:bg-white' }}"
            data-favorited="{{ $isFavorited ? 'true' : 'false' }}"
            title="{{ $isFavorited ? __('messages.remove_from_favorites') : __('messages.add_to_favorites') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
        <div class="absolute bottom-3 right-3 md:opacity-0 md:group-hover:opacity-100 transition-all duration-300 md:translate-y-2 md:group-hover:translate-y-0">
            <div class="w-8 h-8 md:w-9 md:h-9 bg-emerald-500 text-white rounded-full flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            </div>
        </div>
    </div>
    <div class="p-4">
        @if($item->category)
            <span class="text-[10px] sm:text-xs font-medium text-emerald-600 uppercase tracking-wider line-clamp-1">{{ $item->category->title_name }}</span>
        @endif
        <h3 class="font-semibold text-gray-900 mt-1 line-clamp-2 group-hover:text-emerald-600 transition-colors overflow-hidden text-ellipsis">{{ $displayName }}</h3>
        <div class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 overflow-hidden">
            <span class="text-base sm:text-lg font-bold text-emerald-600 break-words">₦{{ number_format($item->effectivePrice(), 2) }}</span>
            @if($item->isDiscounted())
                <span class="text-xs sm:text-sm text-gray-400 line-through break-words">₦{{ number_format($item->selling_price, 2) }}</span>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full whitespace-nowrap shrink-0">-{{ rtrim(rtrim(number_format($item->discount_percentage, 0), '0'), '.') }}%</span>
            @endif
        </div>
    </div>
</div>

<script>
    if (!window._favoriteHandlerDefined) {
        window._favoriteHandlerDefined = true;
        window.toggleFavorite = async function(itemId, btn) {
            if (!window.__isLoggedIn) {
                window.location.href = '/login';
                return;
            }
            try {
                const res = await fetch('/favorites/' + itemId + '/toggle', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    const svg = btn.querySelector('svg');
                    if (data.favorited) {
                        btn.setAttribute('data-favorited', 'true');
                        btn.classList.remove('bg-white/80', 'backdrop-blur-sm', 'text-gray-400', 'hover:text-rose-500', 'hover:bg-white');
                        btn.classList.add('bg-rose-500', 'text-white', 'shadow-lg');
                        svg.setAttribute('fill', 'currentColor');
                    } else {
                        btn.setAttribute('data-favorited', 'false');
                        btn.classList.remove('bg-rose-500', 'text-white', 'shadow-lg');
                        btn.classList.add('bg-white/80', 'backdrop-blur-sm', 'text-gray-400', 'hover:text-rose-500', 'hover:bg-white');
                        svg.setAttribute('fill', 'none');
                    }
                }
            } catch (err) {
                console.error('Favorite toggle failed:', err);
            }
        };
    }
</script>
