@extends('layouts.main')

@section('title', __('messages.about_us'))

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight" style="font-family:'Poppins',sans-serif;">{{ __('messages.about_title') }}</h1>
            <p class="mt-6 text-emerald-100 text-lg leading-relaxed">{{ __('messages.about_hero_desc') }}</p>
        </div>
    </div>
</section>

{{-- Our Story --}}
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div>
                <span class="inline-block text-emerald-600 font-semibold text-sm uppercase tracking-widest mb-3">{{ __('messages.our_story') }}</span>
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">{{ __('messages.about_story_title') }}</h2>
                <div class="space-y-4 text-gray-600 leading-relaxed">
                    <p>{{ __('messages.about_story_p1') }}</p>
                    <p>{{ __('messages.about_story_p2') }}</p>
                    <p>{{ __('messages.about_story_p3') }}</p>
                </div>
            </div>
            <div class="relative">
                <div class="bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-3xl p-8 lg:p-12 shadow-2xl shadow-emerald-500/20">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-5 text-center">
                            <span class="text-3xl font-extrabold text-white block">5+</span>
                            <span class="text-emerald-100 text-sm mt-1 block">{{ __('messages.years_experience') }}</span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-5 text-center">
                            <span class="text-3xl font-extrabold text-white block">2,500+</span>
                            <span class="text-emerald-100 text-sm mt-1 block">{{ __('messages.happy_customers') }}</span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-5 text-center">
                            <span class="text-3xl font-extrabold text-white block">500+</span>
                            <span class="text-emerald-100 text-sm mt-1 block">{{ __('messages.products_available') }}</span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-5 text-center">
                            <span class="text-3xl font-extrabold text-white block">100%</span>
                            <span class="text-emerald-100 text-sm mt-1 block">{{ __('messages.quality_guaranteed') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Mission & Values --}}
<section class="py-16 lg:py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block text-emerald-600 font-semibold text-sm uppercase tracking-widest mb-3">{{ __('messages.our_mission') }}</span>
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900" style="font-family:'Poppins',sans-serif;">{{ __('messages.about_mission_title') }}</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="heart" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">{{ __('messages.value_quality_title') }}</h3>
                <p class="text-gray-600 text-sm leading-relaxed">{{ __('messages.value_quality_desc') }}</p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="truck" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">{{ __('messages.value_delivery_title') }}</h3>
                <p class="text-gray-600 text-sm leading-relaxed">{{ __('messages.value_delivery_desc') }}</p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-5">
                    <i data-lucide="users" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3" style="font-family:'Poppins',sans-serif;">{{ __('messages.value_community_title') }}</h3>
                <p class="text-gray-600 text-sm leading-relaxed">{{ __('messages.value_community_desc') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 lg:py-20 bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white" style="font-family:'Poppins',sans-serif;">{{ __('messages.about_cta_title') }}</h2>
        <p class="mt-4 text-gray-400 text-lg">{{ __('messages.about_cta_desc') }}</p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                {{ __('messages.shop_now') }}
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-8 py-3.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl border border-white/20 transition-all">
                <i data-lucide="mail" class="w-5 h-5"></i>
                {{ __('messages.contact_us') }}
            </a>
        </div>
    </div>
</section>

@endsection
