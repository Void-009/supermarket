<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password — M-Delight Singer Stores</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-poppins { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="flex min-h-screen">

        {{-- Left Branding Panel --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-slate-900 items-center justify-center">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 via-transparent to-emerald-600/10"></div>
            <div class="absolute top-20 left-10 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-emerald-400/5 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] border border-emerald-500/10 rounded-full"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] h-[350px] border border-emerald-500/10 rounded-full"></div>

            <div class="relative z-10 text-center px-12">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl overflow-hidden shadow-lg shadow-emerald-500/25 mb-8 border-2 border-emerald-400">
                    <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-Delight Logo" class="w-full h-full object-cover">
                </div>

                <h1 class="font-poppins text-4xl font-extrabold text-white leading-tight mb-4">
                    M-DELIGHT<br>
                    <span class="text-emerald-400">SINGER STORES</span>
                </h1>

                <div class="w-16 h-1 bg-emerald-500 rounded-full mx-auto mb-6"></div>

                <p class="text-slate-300 text-lg leading-relaxed max-w-sm mx-auto">
                    {{ __('messages.trusted_supermarket') }}
                </p>

                <div class="mt-12 flex items-center justify-center gap-6 text-slate-400 text-sm">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ __('messages.secure_checkout_badge') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="truck" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ __('messages.fast_delivery_badge') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Form Panel --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center px-4 sm:px-8 py-12">
            <div class="w-full max-w-[400px]">

                {{-- Mobile logo --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    <img src="{{ asset('storage/logo/mdelight logo.jpg') }}" alt="M-Delight Logo" class="w-10 h-10 rounded-xl object-cover border border-emerald-400">
                    <span class="font-poppins font-bold text-slate-800">M-Delight</span>
                </div>

                {{-- Status Message --}}
                @if(session('status'))
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0"></i>
                        <p class="text-sm text-emerald-700">{{ session('status') }}</p>
                    </div>
                @endif

                {{-- Heading --}}
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-100 mb-4">
                        <i data-lucide="key-round" class="w-7 h-7 text-emerald-600"></i>
                    </div>
                    <h2 class="font-poppins text-2xl sm:text-3xl font-bold text-slate-900 mb-1">{{ __('messages.forgot_password_title') }}</h2>
                    <p class="text-slate-500">{{ __('messages.forgot_password_desc') }}</p>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('messages.email_address') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="w-4.5 h-4.5 text-slate-400"></i>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                class="block w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="you@example.com"
                            >
                        </div>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold text-sm rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 active:scale-[0.98]"
                    >
                        <i data-lucide="send" class="w-4.5 h-4.5"></i>
                        {{ __('messages.send_reset_link') }}
                    </button>
                </form>

                {{-- Back to Login --}}
                <div class="mt-8 text-center">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-600 transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        {{ __('messages.back_to_login') }}
                    </a>
                </div>

            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>

</body>
</html>
