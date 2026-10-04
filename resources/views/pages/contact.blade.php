@extends('layouts.main')

@section('title', __('messages.contact_us'))

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-emerald-600 to-emerald-700 text-white py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight" style="font-family:'Poppins',sans-serif;">{{ __('messages.contact_title') }}</h1>
            <p class="mt-6 text-emerald-100 text-lg leading-relaxed">{{ __('messages.contact_hero_desc') }}</p>
        </div>
    </div>
</section>

{{-- Contact Info + Form --}}
<section class="py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-5 gap-12 lg:gap-16">

            {{-- Contact Details --}}
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">{{ __('messages.get_in_touch') }}</h2>
                <p class="text-gray-600 mb-8 leading-relaxed">{{ __('messages.contact_desc') }}</p>

                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <i data-lucide="map-pin" class="w-5 h-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm">{{ __('messages.address') }}</h4>
                            <p class="text-gray-600 text-sm mt-0.5">No 182 Dorayi Babba Kuntau Road, Kano State</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <i data-lucide="phone" class="w-5 h-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm">{{ __('messages.phone') }}</h4>
                            <p class="text-gray-600 text-sm mt-0.5">08061400220</p>
                            <p class="text-gray-600 text-sm">07038214833</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <i data-lucide="mail" class="w-5 h-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm">{{ __('messages.email') }}</h4>
                            <p class="text-gray-600 text-sm mt-0.5 break-words">mdelightsingerstores@gmail.com</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <i data-lucide="clock" class="w-5 h-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm">{{ __('messages.open_hours') }}</h4>
                            <p class="text-gray-600 text-sm mt-0.5">{{ __('messages.hours') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="mt-8">
                    <h4 class="font-semibold text-gray-900 text-sm mb-3">{{ __('messages.follow_us') }}</h4>
                    @php
                        $social = $activePaymentSettings ?? null;
                        $facebook = $social ? $social->facebook_url : null;
                        $instagram = $social ? $social->instagram_url : null;
                        $xUrl = $social ? $social->x_url : null;
                        $tiktok = $social ? $social->tiktok_url : null;
                        $whatsapp = $social ? $social->whatsapp_number : null;
                    @endphp
                    <div class="flex items-center gap-3">
                        @if($facebook)
                        <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-emerald-500 hover:text-white flex items-center justify-center text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        @endif
                        @if($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-emerald-500 hover:text-white flex items-center justify-center text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                        @endif
                        @if($instagram)
                        <a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-emerald-500 hover:text-white flex items-center justify-center text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        @endif
                        @if($xUrl)
                        <a href="{{ $xUrl }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-emerald-500 hover:text-white flex items-center justify-center text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        @endif
                        @if($tiktok)
                        <a href="{{ $tiktok }}" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-emerald-500 hover:text-white flex items-center justify-center text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48v-7.1a8.16 8.16 0 005.58 2.18V11.2a4.85 4.85 0 01-5.58-2.74V6.69h5.58z"/></svg>
                        </a>
                        @endif
                        @if(!($facebook || $instagram || $xUrl || $tiktok || $whatsapp))
                            <p class="text-sm text-gray-500">{{ __('messages.no_products') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Contact Form --}}
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6" style="font-family:'Poppins',sans-serif;">{{ __('messages.send_message') }}</h2>

                    @if(session('status'))
                        <div class="mb-6 inline-flex items-center gap-2 px-6 py-3 bg-green-50 text-green-700 font-semibold rounded-xl border border-green-200">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->has('form'))
                        <div class="mb-6 inline-flex items-center gap-2 px-6 py-3 bg-red-50 text-red-700 font-semibold rounded-xl border border-red-200">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            {{ $errors->first('form') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.send') }}" class="space-y-5">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label for="contact_name" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.full_name') }} <span class="text-red-500">*</span></label>
                                <input type="text" id="contact_name" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="{{ __('messages.full_name') }}">
                            </div>
                            <div>
                                <label for="contact_email" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.email') }} <span class="text-red-500">*</span></label>
                                <input type="email" id="contact_email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="{{ __('messages.email_address') }}">
                            </div>
                        </div>
                        <div>
                            <label for="contact_phone" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.phone_number') }}</label>
                            <input type="tel" id="contact_phone" name="phone" value="{{ old('phone') }}" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="0806 140 0220">
                        </div>
                        <div>
                            <label for="contact_subject" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.subject') }} <span class="text-red-500">*</span></label>
                            <input type="text" id="contact_subject" name="subject" value="{{ old('subject') }}" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="{{ __('messages.subject_placeholder') }}">
                        </div>
                        <div>
                            <label for="contact_message" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.message') }} <span class="text-red-500">*</span></label>
                            <textarea id="contact_message" name="message" rows="5" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none" placeholder="{{ __('messages.message_placeholder') }}">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 px-8 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition-all">
                            <i data-lucide="send" class="w-5 h-5"></i>
                            {{ __('messages.send_message') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
