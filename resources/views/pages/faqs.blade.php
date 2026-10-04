@extends('layouts.main')

@section('title', __('messages.faqs'))

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold leading-tight" style="font-family:'Poppins',sans-serif;">{{ __('messages.faqs_title') }}</h1>
            <p class="mt-6 text-emerald-100 text-lg leading-relaxed">{{ __('messages.faqs_hero_desc') }}</p>
        </div>
    </div>
</section>

{{-- FAQ Items --}}
<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-4" x-data="{ openFaq: null }">

            {{-- FAQ 1 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 1 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_1_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 1 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 1" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_1_a') }}</p>
                </div>
            </div>

            {{-- FAQ 2 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 2 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_2_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 2 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 2" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_2_a') }}</p>
                </div>
            </div>

            {{-- FAQ 3 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 3 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_3_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 3 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 3" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_3_a') }}</p>
                </div>
            </div>

            {{-- FAQ 4 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 4 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 4 ? null : 4" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_4_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 4 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 4" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_4_a') }}</p>
                </div>
            </div>

            {{-- FAQ 5 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 5 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 5 ? null : 5" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_5_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 5 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 5" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_5_a') }}</p>
                </div>
            </div>

            {{-- FAQ 6 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 6 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 6 ? null : 6" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_6_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 6 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 6" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_6_a') }}</p>
                </div>
            </div>

            {{-- FAQ 7 --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" :class="openFaq === 7 && 'ring-2 ring-emerald-500'">
                <button @click="openFaq = openFaq === 7 ? null : 7" class="w-full flex items-center justify-between px-6 py-5 text-left">
                    <span class="font-semibold text-gray-900 pr-4">{{ __('messages.faq_7_q') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="openFaq === 7 && 'rotate-180'"></i>
                </button>
                <div x-show="openFaq === 7" x-collapse x-cloak class="px-6 pb-5">
                    <p class="text-gray-600 leading-relaxed">{{ __('messages.faq_7_a') }}</p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- Still Have Questions --}}
<section class="py-16 lg:py-20 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.still_questions_title') }}</h2>
        <p class="mt-3 text-gray-600">{{ __('messages.still_questions_desc') }}</p>
        <a href="{{ route('contact') }}" class="mt-6 inline-flex items-center gap-2 px-8 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
            <i data-lucide="mail" class="w-5 h-5"></i>
            {{ __('messages.contact_us') }}
        </a>
    </div>
</section>

@endsection
