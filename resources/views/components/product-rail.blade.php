@props(['title', 'subtitle' => null, 'href' => null, 'viewAllLabel' => null, 'items' => [], 'limit' => 8])

@php
    $items = collect($items);
    $total = $items->count();
    $visible = min($total, max(1, (int) $limit));
    $hiddenCount = max(0, $total - $visible);
@endphp

<section {{ $attributes->merge(['class' => 'py-8 sm:py-10 bg-white']) }}>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2 mb-5">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>
            @if($href)
                <a href="{{ $href }}" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition-colors shrink-0">
                    {{ $viewAllLabel ?? __('messages.view_all_products') }}
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            @endif
        </div>

        <div class="relative">
            <div class="flex gap-3 sm:gap-4 overflow-x-auto pb-3 -mx-4 sm:mx-0 px-4 sm:px-0 snap-x"
                style="scrollbar-width:none;"
                x-data="{ shown: {{ $hiddenCount === 0 ? 'true' : 'false' }} }">
                @foreach($items->take($visible) as $item)
                    @include('components.product-card-compact', ['item' => $item])
                @endforeach

                @if($hiddenCount > 0)
                    <div class="contents" x-show="shown" x-cloak>
                        @foreach($items->slice($visible) as $item)
                            @include('components.product-card-compact', ['item' => $item])
                        @endforeach
                    </div>

                    <button @click="shown = true" x-show="!shown" x-cloak type="button"
                        class="w-40 sm:w-44 shrink-0 self-stretch rounded-xl border-2 border-dashed border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50 bg-white flex flex-col items-center justify-center gap-2 transition-all duration-300 cursor-pointer group">
                        <span class="w-9 h-9 rounded-full bg-emerald-50 group-hover:bg-emerald-500 group-hover:text-white text-emerald-600 flex items-center justify-center transition-all duration-300">
                            <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                        </span>
                        <span class="text-sm font-semibold text-emerald-600">{{ __('messages.show_more') }}</span>
                        <span class="text-[11px] text-gray-400">+{{ $hiddenCount }} {{ __('messages.more_items') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</section>
