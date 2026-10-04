@extends('layouts.main')
@section('title', __('messages.checkout'))

@section('content')

<noscript>
    <style>[x-cloak] { display: block !important; }</style>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:24px;color:#991b1b;font-size:14px;">
        JavaScript is disabled in your browser. The checkout form will still work, but some features may be limited. Please enable JavaScript for the best experience.
    </div>
</noscript>

@if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:24px;color:#991b1b;font-size:14px;">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:24px;color:#991b1b;font-size:14px;">
        <strong>Please fix the following errors:</strong>
        <ul style="margin-top:8px;margin-left:20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Page Header --}}
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('home') }}" class="hover:text-emerald-600 transition-colors">{{ __('messages.home') }}</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <a href="{{ route('cart.index') }}" class="hover:text-emerald-600 transition-colors">{{ __('messages.cart') }}</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-gray-900 font-medium">{{ __('messages.checkout') }}</span>
        </nav>
        <h1 class="text-3xl lg:text-4xl font-extrabold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.checkout') }}</h1>
    </div>

    {{-- Progress Steps (Desktop) --}}
    <div class="hidden lg:flex items-center justify-center mb-6 sm:mb-10 max-w-lg mx-auto px-4">
        <div class="flex items-center w-full">
            <div class="flex flex-col items-center flex-1">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-green-500 text-white flex items-center justify-center text-xs sm:text-sm font-bold shadow-sm">
                    <i data-lucide="check" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                </div>
                <span class="text-[10px] sm:text-xs font-semibold text-green-600 mt-1.5 sm:mt-2">{{ __('messages.cart') }}</span>
            </div>
            <div class="flex-1 h-0.5 bg-emerald-500 -mx-1 sm:-mx-2"></div>
            <div class="flex flex-col items-center flex-1">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs sm:text-sm font-bold shadow-sm">2</div>
                <span class="text-[10px] sm:text-xs font-semibold text-emerald-600 mt-1.5 sm:mt-2">{{ __('messages.checkout') }}</span>
            </div>
            <div class="flex-1 h-0.5 bg-gray-200 -mx-1 sm:-mx-2"></div>
            <div class="flex flex-col items-center flex-1">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs sm:text-sm font-bold">3</div>
                <span class="text-[10px] sm:text-xs font-semibold text-gray-400 mt-1.5 sm:mt-2">{{ __('messages.confirm') }}</span>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
        @include('components.alert', ['type' => 'error', 'message' => session('error')])
    @endif

    @if($cartItems->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i data-lucide="shopping-cart" class="w-10 h-10 text-gray-300"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">{{ __('messages.cart_empty') }}</h2>
            <p class="text-gray-500 mb-6">{{ __('messages.add_items_before_checkout') }}</p>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl transition-colors">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                {{ __('messages.browse_shop') }}
            </a>
        </div>
    @else

        <form action="{{ route('checkout.place') }}" method="POST" x-data="checkoutPage()" @submit.prevent="submitOrder" novalidate class="flex flex-col lg:flex-row gap-8 pb-24 lg:pb-0">
            @csrf

            {{-- Mobile Stepper --}}
            <div class="lg:hidden mb-5">
                <div class="flex items-center gap-1.5">
                    <template x-for="i in [1, 2, 3]" :key="i">
                        <div class="flex-1 h-1.5 rounded-full transition-colors duration-300" :class="i <= step ? 'bg-emerald-500' : 'bg-gray-200'"></div>
                    </template>
                </div>
                <div class="flex items-center justify-between gap-3 mt-2.5">
                    <p class="text-xs font-bold text-emerald-600 truncate" x-text="steps[step - 1].label"></p>
                    <p class="text-[11px] font-medium text-gray-400 shrink-0" x-text="step + ' / 3'"></p>
                </div>
            </div>

            {{-- Left: Checkout Form --}}
            <div class="flex-1 lg:w-[60%]">

                {{-- Slide deck: on mobile one slide is shown at a time, on desktop all stack --}}
                {{-- touch-action must live in the :style binding: Alpine replaces the whole
                     inline style (cssText) when a :style value is a string. --}}
                <div x-ref="deck" class="relative overflow-hidden scroll-mt-28"
                     :style="'touch-action: pan-y; ' + (isMobile ? 'height:' + deckHeight + 'px;' : '')"
                     @touchstart.passive="startSwipe($event)"
                     @touchend.passive="endSwipe($event)">

                {{-- Slide 1: Contact Information --}}
                <div x-ref="slide1" :class="slideCls(1)">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.contact_info') }}</h2>
                    </div>

                    {{-- Auto-fill Suggestion Banner --}}
                    @if($user && !old('customer_name') && !old('customer_phone') && !old('customer_email'))
                    <div x-show="useAccountInfo === null" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="w-9 h-9 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="sparkles" class="w-4 h-4 text-emerald-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-800">Use your account details?</p>
                                    <p class="text-xs text-emerald-600 mt-0.5">Quick-fill your name, phone, and email from your account.</p>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2">
                                        @if($user->phone)
                                            <span class="text-xs text-emerald-700"><span class="font-medium">Phone:</span> {{ $user->phone }}</span>
                                        @endif
                                        @if($user->email)
                                            <span class="text-xs text-emerald-700"><span class="font-medium">Email:</span> {{ $user->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:flex-shrink-0">
                                <button type="button" @click="applyAccountInfo()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Yes, use my info
                                </button>
                                <button type="button" @click="useAccountInfo = false" class="px-3 py-2 text-sm font-medium text-emerald-700 hover:text-emerald-900 hover:bg-emerald-100 rounded-xl transition-colors">
                                    No thanks
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="customer_name" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.full_name') }} <span class="text-red-500">*</span></label>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="e.g. Amina Bello" x-ref="customerName" :class="fieldErrors.customer_name ? 'border-red-400 ring-2 ring-red-400' : ''">
                            @error('customer_name')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p x-show="fieldErrors.customer_name" x-cloak class="text-xs text-red-500 mt-1" x-text="fieldErrors.customer_name"></p>
                        </div>
                        <div>
                            <label for="customer_phone" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.phone_number') }} <span class="text-red-500">*</span></label>
                            <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="e.g. 0801 234 5678" x-ref="customerPhone" :class="fieldErrors.customer_phone ? 'border-red-400 ring-2 ring-red-400' : ''">
                            @error('customer_phone')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p x-show="fieldErrors.customer_phone" x-cloak class="text-xs text-red-500 mt-1" x-text="fieldErrors.customer_phone"></p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="customer_email" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.email') }} <span class="text-gray-400 font-normal">{{ __('messages.optional') }}</span></label>
                            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="e.g. amina@example.com" x-ref="customerEmail" :class="fieldErrors.customer_email ? 'border-red-400 ring-2 ring-red-400' : ''">
                            @error('customer_email')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p x-show="fieldErrors.customer_email" x-cloak class="text-xs text-red-500 mt-1" x-text="fieldErrors.customer_email"></p>
                        </div>
                    </div>
                </div>
                </div>

                {{-- Slide 2: Delivery Method + Order Notes --}}
                <div x-ref="slide2" class="space-y-6" :class="slideCls(2)">
                {{-- Delivery Method --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="truck" class="w-4 h-4 text-emerald-600"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.delivery_method') }}</h2>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all" :class="deliveryMethod === 'pickup' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="delivery_method" value="pickup" x-model="deliveryMethod" checked class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <i data-lucide="store" class="w-5 h-5 text-emerald-600"></i>
                                <div>
                                    <span class="font-semibold text-gray-900 text-sm">{{ __('messages.store_pickup') }}</span>
                                    <p class="text-xs text-gray-500">{{ __('messages.store_pickup_desc') }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-green-600">FREE</span>
                        </label>

                        <label class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all" :class="deliveryMethod === 'delivery' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="delivery_method" value="delivery" x-model="deliveryMethod" class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <i data-lucide="map-pin" class="w-5 h-5 text-emerald-600"></i>
                                <div>
                                    <span class="font-semibold text-gray-900 text-sm">{{ __('messages.home_delivery') }}</span>
                                    <p class="text-xs text-gray-500">{{ __('messages.home_delivery_desc') }}</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    @error('delivery_method')
                        <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                    @enderror

                    {{-- Delivery Address --}}
                    <div x-show="deliveryMethod === 'delivery'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-5 space-y-4">

                        @if($deliverySettings['show_fee_selector'])
                        {{-- Delivery Location Selector (standard mode) --}}
                        <div>
                            <label for="delivery_location_id" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.delivery_area') }} <span class="text-red-500">*</span></label>
                            @if($deliveryLocations->isEmpty())
                                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-700">
                                    {{ __('messages.no_delivery_locations') }}
                                </div>
                            @else
                                <select name="delivery_location_id" id="delivery_location_id" x-model="selectedLocationId" @change="onLocationChange()" :required="deliveryMethod === 'delivery'" :class="fieldErrors.delivery_location_id ? 'border-red-400 ring-2 ring-red-400' : ''" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                                    <option value="">{{ __('messages.select_delivery_area') }}</option>
                                    @foreach($deliveryLocations as $loc)
                                        <option value="{{ $loc->id }}" data-cost="{{ $loc->cost }}">{{ $loc->name }} — ₦{{ number_format($loc->cost, 2) }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('delivery_location_id')
                                <p class="text-xs text-red-500 mt-1">{{ $message}}</p>
                            @enderror
                            <p x-show="fieldErrors.delivery_location_id" x-cloak class="text-xs text-red-500 mt-1" x-text="fieldErrors.delivery_location_id"></p>
                        </div>
                        @else
                        {{-- Contact Agent Card (selector disabled mode) --}}
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="headphones" class="w-4.5 h-4.5 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-blue-900">Delivery Fee</p>
                                    <p class="text-xs text-blue-600 mt-1 leading-relaxed">{{ $deliverySettings['contact_message'] }}</p>
                                </div>
                            </div>

                            @php
                                $contactPhones = $deliverySettings['contact_phones'] ?? [];
                                $contactWhatsapps = $deliverySettings['contact_whatsapps'] ?? [];
                            @endphp

                            @if(count($contactPhones) || count($contactWhatsapps))
                            <div class="flex flex-wrap gap-2 mt-3" x-data="contactPopup()">
                                @if(count($contactWhatsapps))
                                @if(count($contactWhatsapps) === 1)
                                <a href="https://wa.me/{{ $contactWhatsapps[0] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    Chat on WhatsApp
                                </a>
                                @else
                                <button type="button" @click="open('whatsapp')" class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    Chat on WhatsApp
                                </button>
                                @endif
                                @endif

                                @if(count($contactPhones))
                                @if(count($contactPhones) === 1)
                                <a href="tel:{{ $contactPhones[0] }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    Call Us
                                </a>
                                <a href="sms:{{ $contactPhones[0] }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                    Send SMS
                                </a>
                                @else
                                <button type="button" @click="open('call')" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    Call Us
                                </button>
                                <button type="button" @click="open('sms')" class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm cursor-pointer">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                    Send SMS
                                </button>
                                @endif
                                @endif

                            {{-- Contact numbers popup --}}
                            <div x-show="visible" x-cloak style="position:fixed; inset:0; z-index:2147483646;" class="flex items-end sm:items-center justify-center p-4">
                                <div class="absolute inset-0 bg-black/50" @click="close()"></div>
                                <div x-show="visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
                                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                                        <h3 class="text-sm font-bold text-gray-900" style="font-family:'Poppins',sans-serif;" x-text="title"></h3>
                                        <button type="button" @click="close()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors cursor-pointer" title="Cancel">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div class="p-3 max-h-[60vh] overflow-y-auto">
                                        <template x-for="(number, i) in numbers" :key="i">
                                            <a :href="link(number)" :target="type === 'whatsapp' ? '_blank' : '_self'" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
                                                <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="type === 'whatsapp' ? 'bg-emerald-100 text-emerald-600' : type === 'sms' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-600'">
                                                    <svg x-show="type === 'whatsapp'" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                    <svg x-show="type === 'sms'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                                    <svg x-show="type === 'call'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                </span>
                                                <span class="text-sm font-semibold text-gray-800" x-text="number"></span>
                                            </a>
                                        </template>
                                        <template x-if="!numbers.length">
                                            <p class="text-sm text-gray-400 text-center py-6">No contact numbers available.</p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <div>
                            <label for="delivery_address" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.street_address') }} <span class="text-red-500">*</span></label>
                            <textarea id="delivery_address" name="delivery_address" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none" placeholder="{{ __('messages.street_address_placeholder') }}" x-ref="deliveryAddress" :class="fieldErrors.delivery_address ? 'border-red-400 ring-2 ring-red-400' : ''">{{ old('delivery_address') }}</textarea>
                            @error('delivery_address')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p x-show="fieldErrors.delivery_address" x-cloak class="text-xs text-red-500 mt-1" x-text="fieldErrors.delivery_address"></p>
                        </div>
                    </div>

                    {{-- Bulk Order Notice --}}
                    @if($isBulkOrder)
                    <div x-show="deliveryMethod === 'delivery'" x-cloak class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 mt-0.5 shrink-0"></i>
                            <div>
                                <p class="text-sm font-semibold text-amber-800">Bulk Order Detected</p>
                                <p class="text-xs text-amber-600 mt-1">Your order contains bulky items ({{ $totalQuantity }} total items). The exact delivery fee will be calculated and confirmed by our delivery team after your order is placed. You will be contacted to confirm the final delivery cost.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Order Notes --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="message-square" class="w-4 h-4 text-emerald-600"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.order_notes') }}</h2>
                        <span class="text-xs text-gray-400 font-normal">{{ __('messages.optional') }}</span>
                    </div>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none" placeholder="{{ __('messages.special_instructions') }}">{{ old('notes') }}</textarea>
                </div>
                </div>

                {{-- Slide 3: Payment Method --}}
                <div x-ref="slide3" :class="slideCls(3)">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.payment_method') }}</h2>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-4 p-4 border-2 rounded-xl transition-all" :class="deliveryMethod === 'pickup' ? (paymentMethod === 'cash_on_delivery' ? 'border-emerald-500 bg-emerald-50 cursor-pointer' : 'border-gray-200 hover:border-gray-300 cursor-pointer') : 'border-gray-100 bg-gray-50/50 opacity-60'">
                            <input type="radio" name="payment_method" value="cash_on_delivery" x-model="paymentMethod" :disabled="deliveryMethod !== 'pickup'" class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500 disabled:opacity-50">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <i data-lucide="banknote" class="w-5 h-5" :class="deliveryMethod === 'pickup' ? 'text-emerald-600' : 'text-gray-400'"></i>
                                <div>
                                    <span class="font-semibold text-sm" :class="deliveryMethod === 'pickup' ? 'text-gray-900' : 'text-gray-400'">{{ __('messages.cash_on_delivery') }}</span>
                                    <p class="text-xs" :class="deliveryMethod === 'pickup' ? 'text-gray-500' : 'text-gray-400'">
                                        @if(auth()->check())
                                            Pay with cash when you pick up your order at the store
                                        @else
                                            Pay with cash when you pick up your order at the store
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm font-bold" :class="deliveryMethod === 'pickup' ? 'text-green-600' : 'text-gray-400'">COD</span>
                        </label>

                        {{-- COD only available notice --}}
                        <div x-show="deliveryMethod === 'delivery'" x-cloak x-transition class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <i data-lucide="info" class="w-4 h-4 text-amber-500 mt-0.5 shrink-0"></i>
                            <p class="text-xs text-amber-700">Cash on Delivery is only available for store pickup orders. Please use Bank Transfer for delivery orders.</p>
                        </div>

                        <label class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all" :class="paymentMethod === 'bank_transfer' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod" class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <i data-lucide="building" class="w-5 h-5 text-emerald-600"></i>
                                <div>
                                    <span class="font-semibold text-gray-900 text-sm">{{ __('messages.bank_transfer') }}</span>
                                    <p class="text-xs text-gray-500">{{ __('messages.bank_transfer_desc') }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-purple-600">Bank Transfer</span>
                        </label>
                    </div>

                    @error('payment_method')
                        <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                    <p x-show="fieldErrors.payment_method" x-cloak class="text-xs text-red-500 mt-2" x-text="fieldErrors.payment_method"></p>

                    {{-- Bank Transfer Details --}}
                    <div x-show="paymentMethod === 'bank_transfer'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-5 p-5 bg-purple-50 border border-purple-200 rounded-xl">
                        <h3 class="text-sm font-semibold text-purple-800 mb-4">{{ __('messages.bank_transfer_details') }}</h3>
                        @if($bankAccounts->isNotEmpty())
                            <div class="space-y-4 text-sm">
                                <p class="text-purple-700">{{ __('messages.bank_transfer_instructions') }}</p>
                                @if($paymentSettings && $paymentSettings->bank_transfer_instructions)
                                    <p class="text-purple-800 whitespace-pre-line break-words">{{ $paymentSettings->bank_transfer_instructions }}</p>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-purple-700 font-medium">{{ __('messages.amount') }}</span>
                                    <span class="text-lg font-bold text-purple-900" x-text="'₦' + formatNumber(grandTotal)"></span>
                                </div>
                                <button type="button" @click="showBankAccounts = true" class="w-full flex items-center justify-center gap-2.5 py-4 bg-purple-600 hover:bg-purple-700 text-white font-bold text-base rounded-xl transition-colors shadow-lg shadow-purple-500/25">
                                    <i data-lucide="building" class="w-5 h-5"></i>
                                    {{ __('messages.view_accounts') }}
                                </button>
                            </div>
                        @else
                            <div class="text-purple-700 text-sm">{{ __('messages.bank_not_configured') }}</div>
                        @endif

                        {{-- Bank Receipt Upload --}}
                        <div class="mt-5">
                            <label class="block text-sm font-medium text-purple-700 mb-2">{{ __('messages.upload_receipt') }}</label>
                            <input type="file" name="bank_receipt" accept="image/*,.pdf" class="w-full px-4 py-3 text-sm border-2 border-dashed border-purple-300 rounded-xl focus:outline-none focus:border-purple-500 transition-colors bg-white">
                            <p class="text-xs text-purple-600 mt-1">{{ __('messages.upload_receipt_desc') }}</p>
                            @error('bank_receipt')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Payment Note --}}
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mt-6">
                        <div class="flex items-start gap-3">
                            <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 shrink-0"></i>
                            <div>
                                <p class="text-sm font-semibold text-blue-800">{{ __('messages.payment_info') }}</p>
                                <p class="text-sm text-blue-600 mt-1">{{ __('messages.payment_info_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                </div>{{-- /slide deck --}}

                {{-- Slide navigation (mobile only) --}}
                {{-- Steps 1-2 keep Back + Next on one row. On the last step the
                     Place Order button replaces Next, and the pair stacks
                     vertically instead: Back on top, primary action last. That
                     matches the desktop summary panel, where the two buttons are
                     already full-width and stacked. items-stretch makes Back
                     span the full width without touching its row-mode size. --}}
                <div class="lg:hidden mt-5 flex gap-3"
                    :class="step === 3 ? 'flex-col items-stretch' : 'flex-row items-center'">
                    <button type="button" x-show="step > 1" @click="prev()"
                        class="flex items-center justify-center gap-1.5 px-5 py-3.5 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 active:scale-[0.98] transition-all">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        {{ __('messages.back') }}
                    </button>

                    <button type="button" x-show="step < 3" @click="next()"
                        class="flex-1 flex items-center justify-center gap-2 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 active:scale-[0.98] transition-all">
                        {{ __('messages.next') }}
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>

                    <button type="submit" x-show="step === 3" :disabled="submitting"
                        class="flex items-center justify-center gap-2 py-3.5 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-70 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 active:scale-[0.98] transition-all">
                        <svg x-show="submitting" class="animate-spin w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span class="truncate" x-text="'{{ __('messages.place_order') }} — ₦' + formatNumber(grandTotal)"></span>
                    </button>
                </div>
            </div>

            {{-- Right: Order Summary (desktop only; mobile uses the sticky bar below) --}}
            <div class="hidden lg:block lg:w-[40%]">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">{{ __('messages.your_order') }}</h2>

                    @include('components.order-summary-body')

                    {{-- Place Order Button (Desktop) --}}
                    <button type="submit" class="hidden lg:flex mt-6 items-center justify-center gap-2 w-full py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-emerald-500/25" :disabled="submitting">
                        <svg x-show="submitting" class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="'{{ __('messages.place_order') }} — ₦' + formatNumber(grandTotal)"></span>
                    </button>

                    <a href="{{ route('cart.index') }}" class="mt-3 flex items-center justify-center gap-2 w-full py-3 border border-gray-200 text-gray-600 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        {{ __('messages.back_to_cart') }}
                    </a>
                </div>
            </div>

            {{-- Mobile Sticky Order Summary Bar --}}
            <div class="lg:hidden fixed bottom-0 inset-x-0 z-[60] bg-white border-t border-gray-200 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
                <button type="button" @click="summaryOpen = true" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left active:bg-gray-50 transition-colors">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-gray-500 truncate" x-text="totalQuantity + ' {{ __('messages.items_in_cart') }}'"></p>
                        <p class="text-sm font-extrabold text-gray-900 truncate" x-text="'{{ __('messages.total') }}: ₦' + formatNumber(grandTotal)"></p>
                    </div>
                    <div class="flex items-center gap-1 text-xs font-bold text-emerald-600 shrink-0">
                        {{ __('messages.view_order_summary') }}
                        <i data-lucide="chevron-up" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>

            {{-- Mobile Order Summary Sheet --}}
            <div x-show="summaryOpen" x-cloak @click="summaryOpen = false"
                class="lg:hidden fixed inset-0 z-[59] bg-black/50"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <div x-show="summaryOpen" x-cloak
                class="lg:hidden fixed bottom-0 inset-x-0 z-[61] flex flex-col justify-end pointer-events-none"
                style="max-height:85vh; max-height:85dvh;"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
                <div class="bg-white rounded-t-2xl shadow-2xl flex flex-col min-h-0 pointer-events-auto" style="max-height:85vh; max-height:85dvh;">
                    <div class="flex-none flex items-center justify-between gap-3 px-4 pt-3 pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-1 h-8 bg-gray-200 rounded-full flex-shrink-0"></div>
                            <h2 class="text-base font-bold text-gray-900 truncate" style="font-family:'Poppins',sans-serif;">{{ __('messages.your_order') }}</h2>
                        </div>
                        <button type="button" @click="summaryOpen = false" class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 py-4" @click.stop>
                        @include('components.order-summary-body')

                        <a href="{{ route('cart.index') }}" @click="summaryOpen = false" class="mt-4 flex items-center justify-center gap-2 w-full py-3 border border-gray-200 text-gray-600 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            {{ __('messages.back_to_cart') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Bank Accounts Popup --}}
            <div x-show="showBankAccounts" x-cloak
                 class="fixed inset-0 z-[2147483647] flex items-center justify-center p-4"
                 style="background:rgba(0,0,0,0.55);"
                 @click.self="showBankAccounts = false">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-hidden" @click.stop>
                    <div class="flex items-center justify-between p-5 border-b border-gray-100">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.bank_accounts_popup_title') }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('messages.bank_transfer_instructions') }}</p>
                        </div>
                        <button @click="showBankAccounts = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="p-5 space-y-4 overflow-y-auto max-h-[60vh]">
                        @foreach($bankAccounts as $account)
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">{{ $account->bank_name }}</h4>
                                </div>
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">{{ __('messages.transfer_to') }}</span>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('messages.account_name') }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $account->account_name }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('messages.account_number') }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-mono font-bold text-gray-900">{{ $account->account_number }}</span>
                                        <button type="button" @click="copyAccountNumber('{{ $account->account_number }}', $el)" class="p-1 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded transition-colors" title="Copy">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                                @if($account->bank_code)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ __('messages.bank_code') }}</span>
                                    <span class="text-sm font-mono text-gray-700">{{ $account->bank_code }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                        <button @click="showBankAccounts = false" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm rounded-xl transition-colors">
                            {{ __('messages.close') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Error Alert --}}
            <div x-show="error" x-cloak x-transition class="fixed top-6 left-4 right-4 sm:left-auto sm:right-6 z-[2147483647] max-w-sm w-full bg-red-50 border border-red-200 rounded-xl shadow-lg p-4">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5 shrink-0"></i>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-800">Error</p>
                        <p class="text-sm text-red-600 mt-1" x-text="error"></p>
                    </div>
                    <button @click="error = ''" class="text-red-400 hover:text-red-600 shrink-0">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            {{-- Success Popup (shown after a successful order, then redirects home) --}}
            <div x-show="showSuccess" x-cloak
                 class="fixed inset-0 z-[2147483647] flex items-center justify-center p-4"
                 style="background:rgba(0,0,0,0.55);">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="check" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2" style="font-family:'Poppins',sans-serif;">{{ __('messages.order_placed') }}</h3>
                    <p class="text-gray-500 text-sm">{!! __('messages.order_placed_desc') !!}.<br>{{ __('messages.redirecting_home') }}</p>
                    <div class="mt-6 flex justify-center">
                        <span class="w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></span>
                    </div>
                </div>
            </div>

        </form>

    @endif
</div>

@endsection

@push('scripts')
@php
    $contactPhones = $deliverySettings['contact_phones'] ?? [];
    $contactWhatsapps = $deliverySettings['contact_whatsapps'] ?? [];
    $contactPopupData = [
        'call' => $contactPhones,
        'sms' => $contactPhones,
        'whatsapp' => $contactWhatsapps,
    ];
@endphp
<script>
function checkoutPage() {
    // Kept outside the returned object so Alpine's reactivity never proxies them.
    var mq = null;
    var mqHandler = null;
    var deckObserver = null;

    var ERR = {
        name: @json(__('messages.required_field')),
        phone: @json(__('messages.required_field')),
        email: @json(__('messages.invalid_email')),
        area: @json(__('messages.select_delivery_area_error')),
        address: @json(__('messages.enter_delivery_address')),
        payment: @json(__('messages.select_payment_method_error')),
    };

    return {
        deliveryMethod: '{{ old("delivery_method", "pickup") }}',
        paymentMethod: 'cash_on_delivery',
        submitting: false,
        showSuccess: false,
        showBankAccounts: false,
        error: '',
        subtotal: {{ $total }},
        deliveryFee: 0,
        selectedLocationId: '',
        selectedLocationName: '',
        deliveryLocations: @json($deliveryLocations),
        useAccountInfo: null,
        accountUser: @json($user ? ['name' => $user->name, 'phone' => $user->phone ?? '', 'email' => $user->email ?? ''] : null),
        isBulkOrder: @json($isBulkOrder),
        showFeeSelector: @json($deliverySettings['show_fee_selector']),
        totalQuantity: {{ $totalQuantity }},

        // --- Mobile slide deck ---
        step: 1,
        deckHeight: 0,
        isMobile: false,
        summaryOpen: false,
        fieldErrors: {},
        steps: [
            { label: @json(__('messages.contact_info')) },
            { label: @json(__('messages.delivery_method')) },
            { label: @json(__('messages.payment_method')) },
        ],
        _startX: null,
        _startY: null,

        init() {
            if (window.matchMedia) {
                mq = window.matchMedia('(min-width: 1024px)');
                this.isMobile = !mq.matches;
                mqHandler = () => {
                    this.isMobile = !mq.matches;
                    this.$nextTick(() => this.refreshDeck());
                };
                if (mq.addEventListener) mq.addEventListener('change', mqHandler);
                else if (mq.addListener) mq.addListener(mqHandler);
            }

            this.$watch('deliveryMethod', (val) => {
                if (val === 'delivery' && this.paymentMethod === 'cash_on_delivery') {
                    this.paymentMethod = 'bank_transfer';
                }
                // the address block appearing/disappearing changes the active slide's height
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                this.$nextTick(() => this.refreshDeck());
            });
            if (this.deliveryMethod === 'delivery' && this.paymentMethod === 'cash_on_delivery') {
                this.paymentMethod = 'bank_transfer';
            }

            this.$nextTick(() => this.refreshDeck());
        },

        /** Classes for slide n: stacked blocks on desktop, one translated slide on mobile. */
        slideCls(n) {
            if (!this.isMobile) return n > 1 ? 'mt-6' : '';
            var base = 'absolute top-0 left-0 w-full transition-transform duration-300 ease-out';
            if (n === this.step) return base + ' translate-x-0';
            return base + (n < this.step ? ' -translate-x-full' : ' translate-x-full');
        },

        measureDeck() {
            if (!this.isMobile) { this.deckHeight = 0; return; }
            var el = this.$refs['slide' + this.step];
            if (!el) return;
            var h = Math.ceil(el.getBoundingClientRect().height);
            if (h > 0 && h !== this.deckHeight) this.deckHeight = h;
        },

        /** Re-measure and re-target the observer at the currently active slide. */
        refreshDeck() {
            this.measureDeck();
            if (typeof lucide !== 'undefined') lucide.createIcons();
            if (deckObserver) { deckObserver.disconnect(); deckObserver = null; }
            if (!this.isMobile || typeof ResizeObserver === 'undefined') return;
            var el = this.$refs['slide' + this.step];
            if (!el) return;
            var self = this;
            deckObserver = new ResizeObserver(function () { self.measureDeck(); });
            deckObserver.observe(el);
        },

        next() {
            if (this.step >= 3) return;
            if (!this.validateStep(this.step)) return;
            this.step++;
            this.$nextTick(() => this.refreshDeck());
            this.scrollDeckIntoView();
        },

        prev() {
            if (this.step <= 1) return;
            this.step--;
            this.$nextTick(() => this.refreshDeck());
            this.scrollDeckIntoView();
        },

        scrollDeckIntoView() {
            if (!this.isMobile) return;
            var deck = this.$refs.deck;
            if (deck) deck.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        /** Pure check of the fields visible on slide n. Does not mutate state. */
        collectErrors(n) {
            var errs = {};

            if (n === 1) {
                var name = (this.$refs.customerName && this.$refs.customerName.value || '').trim();
                var phone = (this.$refs.customerPhone && this.$refs.customerPhone.value || '').trim();
                var email = (this.$refs.customerEmail && this.$refs.customerEmail.value || '').trim();
                if (!name) errs.customer_name = ERR.name;
                if (!phone) errs.customer_phone = ERR.phone;
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errs.customer_email = ERR.email;
            }

            if (n === 2 && this.deliveryMethod === 'delivery') {
                if (this.showFeeSelector && !this.selectedLocationId) {
                    errs.delivery_location_id = ERR.area;
                }
                var addr = this.$refs.deliveryAddress;
                if (!addr || !addr.value.trim()) errs.delivery_address = ERR.address;
            }

            if (n === 3 && !this.paymentMethod) {
                errs.payment_method = ERR.payment;
            }

            return errs;
        },

        /** Validates the fields the user can see on slide n. Returns true when it passes. */
        validateStep(n) {
            var errs = this.collectErrors(n);
            this.fieldErrors = errs;

            var firstKey = Object.keys(errs)[0];
            if (firstKey) {
                var self = this;
                this.$nextTick(() => {
                    var target = self.$refs[firstKey] || document.getElementById(firstKey);
                    if (target && target.focus) target.focus();
                });
            }

            return !firstKey;
        },

        startSwipe(e) {
            if (!this.isMobile) return;
            var point = e.touches ? e.touches[0] : e;
            if (!point) return;
            this._startX = point.clientX;
            this._startY = point.clientY;
        },

        endSwipe(e) {
            if (!this.isMobile || this._startX === null) { this._startX = null; return; }
            var point = e.changedTouches ? e.changedTouches[0] : e;
            if (!point) { this._startX = null; return; }
            var dx = point.clientX - this._startX;
            var dy = point.clientY - this._startY;
            this._startX = null;
            // only act on a clearly horizontal swipe so vertical scrolling never flips slides
            if (Math.abs(dx) < 40 || Math.abs(dx) <= Math.abs(dy)) return;
            if (dx < 0) this.next(); else this.prev();
        },

        copyAccountNumber(number, el) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(number).then(() => {
                    el.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5 text-green-500"></i>';
                    setTimeout(() => {
                        el.innerHTML = '<i data-lucide="copy" class="w-3.5 h-3.5"></i>';
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 1500);
                }).catch(() => {});
            }
        },

        applyAccountInfo() {
            if (!this.accountUser) return;
            const nameField = document.getElementById('customer_name');
            const phoneField = document.getElementById('customer_phone');
            const emailField = document.getElementById('customer_email');
            if (nameField && this.accountUser.name) nameField.value = this.accountUser.name;
            if (phoneField && this.accountUser.phone) phoneField.value = this.accountUser.phone;
            if (emailField && this.accountUser.email) emailField.value = this.accountUser.email;
            this.useAccountInfo = true;
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        onLocationChange() {
            const loc = this.deliveryLocations.find(l => l.id == this.selectedLocationId);
            if (loc) {
                this.deliveryFee = parseFloat(loc.cost);
                this.selectedLocationName = loc.name;
            } else {
                this.deliveryFee = 0;
                this.selectedLocationName = '';
            }
            if (this.fieldErrors.delivery_location_id) this.fieldErrors = Object.assign({}, this.fieldErrors, { delivery_location_id: '' });
        },

        get grandTotal() {
            if (this.deliveryMethod !== 'delivery') return this.subtotal;
            if (this.isBulkOrder || !this.showFeeSelector) return this.subtotal;
            return this.subtotal + this.deliveryFee;
        },

        formatNumber(num) {
            return Number(num).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async submitOrder(e) {
            e.preventDefault();

            if (this.submitting) return;

            // Validate every slide, not just the visible one, so a desktop submit can't
            // skip a step. On mobile we jump to the earliest offending slide.
            var allErrs = {};
            var firstBad = 0;
            for (var i = 1; i <= 3; i++) {
                var stepErrs = this.collectErrors(i);
                var keys = Object.keys(stepErrs);
                if (!keys.length) continue;
                keys.forEach(function (k) { allErrs[k] = stepErrs[k]; });
                if (!firstBad) firstBad = i;
            }
            if (firstBad) {
                this.fieldErrors = allErrs;
                this.error = '';
                if (this.isMobile && firstBad !== this.step) {
                    this.step = firstBad;
                    this.$nextTick(() => this.refreshDeck());
                    this.scrollDeckIntoView();
                }
                return;
            }

            if (this.paymentMethod === 'bank_transfer') {
                const fileInput = document.querySelector('input[name="bank_receipt"]');
                if (!fileInput.files || fileInput.files.length === 0) {
                    showToast('{{ __('messages.upload_receipt_error') }}', 'error');
                    return;
                }
            }

            this.submitting = true;
            this.error = '';

            try {
                const form = e.target;
                const fd = new FormData(form);

                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    this.showSuccess = true;
                    setTimeout(() => { window.location.href = '{{ route('order.confirm', '__ID__') }}'.replace('__ID__', data.order_id); }, 2500);
                } else {
                    this.submitting = false;
                    this.error = data.message || '{{ __('messages.order_error') }}';
                }
            } catch (err) {
                this.submitting = false;
                this.error = '{{ __('messages.network_error') }}';
            }
        },
    }
}

function contactPopup() {
    return {
        visible: false,
        type: 'call',
        all: @json($contactPopupData),
        open(type) {
            this.type = type;
            this.visible = true;
        },
        close() {
            this.visible = false;
        },
        get numbers() {
            return this.all[this.type] || [];
        },
        get title() {
            if (this.type === 'whatsapp') return 'Chat on WhatsApp';
            if (this.type === 'sms') return 'Send SMS';
            return 'Call Us';
        },
        link(number) {
            if (this.type === 'whatsapp') return 'https://wa.me/' + number;
            if (this.type === 'sms') return 'sms:' + number;
            return 'tel:' + number;
        },
    }
}
</script>
@endpush
