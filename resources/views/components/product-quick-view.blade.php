<div x-data="productQuickView()" x-show="open" x-cloak
    class="fixed inset-0 z-[2147483647] flex items-center justify-center p-3 sm:p-4"
    style="display:none;">

    {{-- Backdrop --}}
    <div x-show="open" @click="closeModal()"
        class="absolute inset-0"
        style="background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    {{-- Modal: fixed-height flex column, only the variations list scrolls --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-lg flex flex-col overflow-hidden bg-white rounded-2xl max-h-[calc(100vh-1.5rem)]"
        style="max-height:calc(100dvh - 1.5rem); box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);"
        @click.outside="closeModal()">

        {{-- Close Button --}}
        <button @click="closeModal()" type="button"
            class="absolute top-2.5 right-2.5 z-20 w-8 h-8 flex items-center justify-center rounded-full border-none cursor-pointer"
            style="background:rgba(255,255,255,0.85); box-shadow:0 1px 3px rgba(0,0,0,0.12);">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <template x-if="product">
            <div class="flex flex-col min-h-0">

                {{-- Zone 1: Image --}}
                <div class="flex-none flex items-center justify-center overflow-hidden bg-gray-50 rounded-t-2xl h-[20vh] max-h-[180px] min-h-[104px] sm:h-[32vh] sm:max-h-[300px]">
                    <template x-if="displayImage">
                        <img :src="displayImage" :alt="product.display_name || product.name" class="block w-auto h-auto max-w-full max-h-full" style="object-fit:contain;">
                    </template>
                    <template x-if="!displayImage">
                        <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg, #ecfdf5, #d1fae5);">
                            <span class="text-4xl sm:text-5xl">&#x1F6D2;</span>
                        </div>
                    </template>
                </div>

                {{-- Zone 2: Name + Price --}}
                <div class="flex-none flex items-start justify-between gap-3 px-4 pt-3 pb-2">
                    <div class="min-w-0 flex-1">
                        <template x-if="product.category">
                            <span class="block text-[11px] font-semibold uppercase tracking-wide text-emerald-600 truncate" x-text="product.category"></span>
                        </template>
                        <h3 class="text-[15px] sm:text-base font-bold text-gray-900 leading-snug line-clamp-2 break-words" style="font-family:'Poppins',sans-serif;" x-text="picker.selected ? (picker.selected.display_name || picker.selected.name) : (product.display_name || product.name)"></h3>
                    </div>
                    <div class="shrink-0 text-right">
                        <template x-if="picker.selected">
                            <span class="block text-lg sm:text-2xl font-extrabold text-emerald-600 whitespace-nowrap" x-text="picker.fmt(picker.price)"></span>
                        </template>
                        <template x-if="picker.compareAt && picker.selected">
                            <span class="block text-[11px] sm:text-xs text-gray-400 line-through whitespace-nowrap" x-text="picker.fmt(picker.compareAt)"></span>
                        </template>
                    </div>
                </div>

                {{-- Zone 3: Stock Status --}}
                <div class="flex-none px-4 pb-2">
                    <template x-if="picker.selected && picker.selected.stock > 0">
                        <span class="inline-flex items-center gap-1.5 py-0.5 px-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-semibold">
                            {{ __('messages.in_stock') }}
                            <template x-if="!picker.selected.is_variation">
                                <span class="font-normal text-emerald-600" x-text="'(' + picker.selected.stock + ' {{ __("messages.available", ["count" => ""]) }})'"></span>
                            </template>
                        </span>
                    </template>
                    <template x-if="!picker.selected || picker.selected.stock < 1">
                        <span class="inline-flex items-center gap-1.5 py-0.5 px-2 bg-red-50 text-red-700 border border-red-200 rounded-lg text-[11px] font-semibold">
                            {{ __('messages.out_of_stock') }}
                        </span>
                    </template>
                </div>

                {{-- Zone 4: Variations (the only scrollable region; shrinks before any other zone) --}}
                <div class="min-h-0 overflow-y-auto overscroll-contain">
                    <template x-if="picker.options.length > 1">
                        <div class="px-4 pb-1">
                            <span class="sticky top-0 block bg-white pt-1 pb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.variations') }}</span>
                            <div class="flex flex-col gap-1.5 sm:gap-2">
                                <template x-for="o in picker.options" :key="o.id">
                                    <button @click="picker.select(o.id)" type="button"
                                        :class="picker.selectedId === o.id ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50' : 'hover:border-gray-300'"
                                        class="flex items-center justify-between gap-2 w-full px-3 py-2 sm:py-2.5 border border-gray-200 rounded-xl bg-white cursor-pointer text-left">
                                        <span class="text-[13px] font-semibold text-gray-900 flex-1 min-w-0 truncate" x-text="o.display_name || o.name"></span>
                                        <span class="flex items-center gap-2 shrink-0">
                                            <template x-if="o.stock < 1">
                                                <span class="text-[10px] font-semibold text-red-600">{{ __('messages.out_of_stock') }}</span>
                                            </template>
                                            <span class="text-[13px] font-bold text-emerald-600 whitespace-nowrap" x-text="picker.fmt(o.selling_price)"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Zone 5: Quantity, Add to Cart, View full details (always visible) --}}
                <div class="flex-none border-t border-gray-100 px-4 py-3">
                    <template x-if="picker.selected && picker.selected.stock > 0">
                        <div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center flex-shrink-0 border border-gray-200 rounded-xl overflow-hidden">
                                    <button @click="picker.qty = Math.max(1, picker.qty - 1)" type="button" class="w-9 sm:w-10 h-10 flex items-center justify-center text-gray-500 cursor-pointer border-none bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                    </button>
                                    <input type="number" x-model.number="picker.qty" min="1" :max="picker.maxStock || 99999" class="w-11 sm:w-12 h-10 text-center border-x border-gray-200 text-sm font-semibold text-gray-900 outline-none">
                                    <button @click="picker.incQty()" type="button" class="w-9 sm:w-10 h-10 flex items-center justify-center text-gray-500 cursor-pointer border-none bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>

                                <button @click="picker.submit()" :disabled="picker.adding" type="button"
                                    class="flex-1 min-w-0 flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-6 h-10 bg-emerald-500 hover:bg-emerald-600 text-white text-[13px] sm:text-sm font-bold rounded-xl cursor-pointer border-none whitespace-nowrap"
                                    style="box-shadow:0 4px 12px rgba(16,185,129,0.25); transition:all 0.2s;">
                                    <template x-if="!picker.adding && !picker.added">
                                        <span class="flex items-center gap-1.5 sm:gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a3 3 0 100 6 3 3 0 000-6zm-8 2a3 3 0 100 6 3 3 0 000-6z"/></svg>
                                            <span class="truncate">{{ __('messages.add_to_cart') }}</span>
                                        </span>
                                    </template>
                                    <template x-if="picker.adding">
                                        <span>{{ __('messages.adding') }}</span>
                                    </template>
                                    <template x-if="picker.added">
                                        <span class="flex items-center gap-1.5 sm:gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span class="truncate">{{ __('messages.added') }}</span>
                                        </span>
                                    </template>
                                </button>
                            </div>

                            {{-- Reserved slot so the footer never shifts when the message appears --}}
                            <p class="mt-1.5 min-h-[18px] text-[11px] font-medium" :class="picker.message ? (picker.success ? 'text-green-600' : 'text-red-600') : ''" x-text="picker.message || ''"></p>
                        </div>
                    </template>

                    <template x-if="!picker.selected || picker.selected.stock < 1">
                        <div>
                            <button disabled type="button" class="w-full flex items-center justify-center gap-2 py-3 bg-gray-200 text-gray-500 font-bold rounded-xl cursor-not-allowed text-sm border-none">
                                {{ __('messages.out_of_stock') }}
                            </button>
                        </div>
                    </template>

                    <a :href="'/shop/' + (picker.selected ? picker.selected.slug : product.slug)" class="block text-center text-[11px] sm:text-xs text-emerald-600 font-medium py-1.5 no-underline hover:underline">{{ __('messages.view_full_details') }}</a>
                </div>
            </div>
        </template>
    </div>
</div>
