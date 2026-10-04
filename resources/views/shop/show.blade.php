@extends('layouts.main')

@section('title', $item->display_name)


@section('content')

{{-- ============================================ --}}
{{-- BREADCRUMB                                  --}}
{{-- ============================================ --}}
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition-colors">{{ __('messages.home') }}</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <a href="{{ route('shop.index') }}" class="hover:text-emerald-600 transition-colors">{{ __('messages.shop') }}</a>
            @if($item->category)
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('shop.index', ['category' => $item->category->slug]) }}" class="hover:text-emerald-600 transition-colors">{{ $item->category->name }}</a>
            @endif
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-gray-900 font-medium truncate max-w-[200px]">{{ $item->display_name }}</span>
        </nav>
    </div>
</div>


{{-- ============================================ --}}
{{-- PRODUCT DETAIL (2-column)                   --}}
{{-- ============================================ --}}
@php
    $galleryUrls = [];
    if ($item->image_url) {
        $galleryUrls[] = asset('storage/' . $item->image_url);
    }
    if ($item->images) {
        foreach ($item->images as $img) {
            $galleryUrls[] = asset('storage/' . $img->path);
        }
    }
@endphp
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12"
    x-data="productDetailPage(JSON.parse(atob('{{ base64_encode(json_encode($pickerProduct)) }}')), {{ Js::from($galleryUrls) }})">
    <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">

        {{-- LEFT COLUMN: Images --}}
        <div class="lg:w-[55%]">
            {{-- Main Image --}}
            <div class="relative bg-gray-50 rounded-2xl overflow-hidden aspect-square border border-gray-100">
                <template x-if="galleryImages.length">
                    <img
                        :src="galleryImages[activeImage]"
                        alt="{{ $item->name }}"
                        class="w-full h-full object-cover"
                    >
                </template>
                <template x-if="!galleryImages.length">
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-emerald-100">
                        <div class="text-center">
                            <i data-lucide="image" class="w-16 h-16 text-emerald-200 mx-auto mb-3"></i>
                            <span class="text-gray-400 text-sm">{{ __('messages.no_image') }}</span>
                        </div>
                    </div>
                </template>

                <div x-show="selected ? selected.stock < 1 : {{ $item->effectiveQuantity() < 1 ? 'true' : 'false' }}"
                    class="absolute top-4 left-4 bg-red-500 text-white px-3 py-1.5 rounded-lg text-sm font-semibold shadow-lg">
                    {{ __('messages.out_of_stock') }}
                </div>
            </div>

            {{-- Image Thumbnails --}}
            <div x-show="galleryImages.length > 1" class="mt-4 grid grid-cols-4 sm:grid-cols-5 gap-3">
                <template x-for="(url, idx) in galleryImages" :key="idx">
                    <button
                        @click="activeImage = idx"
                        :class="activeImage === idx ? 'ring-2 ring-emerald-500 ring-offset-2' : 'ring-1 ring-gray-200 hover:ring-emerald-300'"
                        class="aspect-square rounded-xl overflow-hidden transition-all"
                    >
                        <img :src="url" alt="{{ $item->name }}" class="w-full h-full object-cover">
                    </button>
                </template>
            </div>
        </div>

        {{-- RIGHT COLUMN: Details --}}
        <div class="lg:w-[45%]">
            {{-- Category Badge --}}
            @if($item->category)
                <a href="{{ route('shop.index', ['category' => $item->category->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold border border-emerald-200 hover:bg-emerald-100 transition-colors mb-4">
                    <i data-lucide="tag" class="w-3 h-3"></i>
                    {{ $item->category->name }}
                </a>
            @endif

            {{-- Product Name --}}
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 leading-tight" style="font-family:'Poppins',sans-serif;">
                {{ $item->display_name }}
            </h1>

            {{-- Price --}}
            <div class="mt-4 flex flex-wrap items-baseline gap-3">
                <template x-if="selected">
                    <span class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-emerald-600" x-text="fmt(price)"></span>
                </template>
                <template x-if="compareAt && selected">
                    <span class="text-lg text-gray-400 line-through" x-text="fmt(compareAt)"></span>
                    <span class="px-2.5 py-0.5 bg-red-500 text-white text-xs font-bold rounded-md" x-text="'-' + Math.round((1 - price / compareAt) * 100) + '%'"></span>
                </template>
            </div>

            {{-- Variation Selector --}}
            <template x-if="options.length > 1">
                <div class="mt-6">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('messages.variations') }}</span>
                    <div class="mt-3 flex flex-col gap-2">
                        <template x-for="o in options" :key="o.id">
                            <button @click="select(o.id)" type="button"
                                :class="selectedId === o.id ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50' : 'hover:border-gray-300'"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 border border-gray-200 rounded-xl bg-white transition-all cursor-pointer text-left">
                                <span class="text-sm font-semibold text-gray-900 min-w-0" x-text="o.display_name || o.name"></span>
                                <span class="flex items-center gap-3 shrink-0">
                                    <template x-if="o.stock < 1">
                                        <span class="text-xs font-semibold text-red-600">{{ __('messages.out_of_stock') }}</span>
                                    </template>
                                    <span class="text-sm font-bold text-emerald-600" x-text="fmt(o.selling_price)"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Description --}}
            @if($item->description)
                <div class="mt-6 text-gray-600 leading-relaxed text-sm lg:text-base">
                    {!! nl2br(e($item->description)) !!}
                </div>
            @endif

            <hr class="my-6 border-gray-100">

            {{-- Stock Status --}}
            <div class="flex items-center gap-2 mb-6">
                <template x-if="selected && selected.stock > 0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-sm font-semibold border border-green-200">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        In Stock
                        <template x-if="!selected.is_variation">
                            <span class="text-green-600 font-normal" x-text="'(' + selected.stock + ' {{ __('messages.available', ['count' => '']) }})'"></span>
                        </template>
                    </span>
                </template>
                <template x-if="!selected || selected.stock < 1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-sm font-semibold border border-red-200">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Out of Stock
                    </span>
                </template>
            </div>

                    {{-- Quantity & Add to Cart --}}
            <template x-if="selected && selected.stock > 0">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    {{-- Quantity Selector --}}
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                        <button @click="qty = Math.max(1, qty - 1)" class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>
                        <input type="number" x-model.number="qty" min="1" :max="maxStock || 99999" class="w-16 h-12 text-center border-x border-gray-200 text-sm font-semibold focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button @click="incQty()" class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>

                    {{-- Add to Cart Button --}}
                    <button @click="submit()" :disabled="adding"
                        class="flex-1 flex items-center justify-center gap-2 px-8 py-3.5 bg-emerald-500 hover:bg-emerald-600 disabled:bg-emerald-400 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-300 text-base">
                        <template x-if="!adding && !added">
                             <span class="flex items-center gap-2"><i data-lucide="shopping-cart" class="w-5 h-5"></i> {{ __('messages.add_to_cart') }}</span>
                        </template>
                        <template x-if="adding">
                            <span>{{ __('messages.adding') }}</span>
                        </template>
                        <template x-if="added">
                            <span class="flex items-center gap-2"><i data-lucide="check" class="w-5 h-5"></i> {{ __('messages.added') }}</span>
                        </template>
                    </button>

                    <template x-if="message">
                        <p class="text-xs font-medium sm:ml-2" :class="success ? 'text-green-600' : 'text-red-600'" x-text="message"></p>
                    </template>
                </div>
            </template>
            <template x-if="!selected || selected.stock < 1">
                <button disabled class="w-full flex items-center justify-center gap-2 px-8 py-3.5 bg-gray-200 text-gray-500 font-bold rounded-xl cursor-not-allowed text-base mt-2">
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                    {{ __('messages.out_of_stock') }}
                </button>
            </template>

            <hr class="my-8 border-gray-100">

            {{-- Product Details Accordion --}}
            <div x-data="{ open: true }" class="border border-gray-200 rounded-xl overflow-hidden">
                <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 bg-gray-50 hover:bg-gray-100 transition-colors">
                    <span class="font-semibold text-gray-900 text-sm">{{ __('messages.product_details') }}</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500 transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-cloak x-collapse>
                    <div class="px-5 py-4">
                        <dl class="space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-sm text-gray-500 shrink-0">{{ __('messages.item_id') }}</dt>
                                <dd class="text-sm text-gray-900 font-medium text-right">{{ $item->pos_item_id }}</dd>
                            </div>

                            @if($item->barcodes && $item->barcodes->count())
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-sm text-gray-500 shrink-0">{{ Str::plural('Barcode', $item->barcodes->count()) }}</dt>
                                    <dd class="text-sm text-gray-900 font-medium text-right">
                                        @foreach($item->barcodes as $barcode)
                                            <span class="inline-block px-2 py-0.5 bg-gray-100 rounded text-xs font-mono mb-1">{{ $barcode->barcode_value }}</span>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif

                            @if($item->category)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-sm text-gray-500 shrink-0">{{ __('messages.category') }}</dt>
                                    <dd class="text-sm text-gray-900 font-medium text-right">
                                        <a href="{{ route('shop.index', ['category' => $item->category->slug]) }}" class="text-emerald-600 hover:text-emerald-700 transition-colors">{{ $item->category->name }}</a>
                                    </dd>
                                </div>
                            @endif

                            @if($item->last_modified)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-sm text-gray-500 shrink-0">{{ __('messages.last_updated') }}</dt>
                                    <dd class="text-sm text-gray-900 font-medium text-right">{{ $item->last_modified->format('M d, Y') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ============================================ --}}
{{-- VARIATIONS / RELATED PRODUCTS               --}}
{{-- ============================================ --}}
@if($variations->count())
<section class="bg-gray-50 py-16 lg:py-20 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block text-emerald-600 font-semibold text-sm uppercase tracking-widest mb-3">{{ __('messages.you_may_also_like') }}</span>
            <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.related_products') }}</h2>
            <div class="mt-4 flex items-center justify-center gap-2">
                <span class="w-12 h-1 bg-emerald-400 rounded-full"></span>
                <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                <span class="w-12 h-1 bg-emerald-400 rounded-full"></span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            @foreach($variations as $variation)
                @if($variation->id !== $item->id)
                    @include('components.product-card', ['item' => $variation])
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
