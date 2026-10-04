<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — M-Delight Singer Stores</title>
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

    {{-- Cancel --}}
    <a href="{{ route('home') }}" class="fixed top-4 right-4 z-50 inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-slate-600 bg-white/90 backdrop-blur-sm border border-slate-200 rounded-xl shadow-sm hover:text-emerald-700 hover:border-emerald-300 transition-colors">
        <i data-lucide="x" class="w-4 h-4"></i>
        {{ __('messages.cancel') }}
    </a>

    <div class="flex min-h-screen">

        {{-- Left Branding Panel --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-slate-900 items-center justify-center">
            {{-- Gradient overlay --}}
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 via-transparent to-emerald-600/10"></div>

            {{-- Decorative shapes --}}
            <div class="absolute top-20 left-10 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-emerald-400/5 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] border border-emerald-500/10 rounded-full"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] h-[350px] border border-emerald-500/10 rounded-full"></div>

            {{-- Content --}}
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

                {{-- Session Error --}}
                @if(session('error'))
                    @include('components.alert', ['type' => 'error', 'message' => session('error')])
                @endif

                {{-- Heading --}}
                <h2 class="font-poppins text-2xl sm:text-3xl font-bold text-slate-900 mb-1">{{ __('messages.welcome_back') }}</h2>
                <p class="text-slate-500 mb-8">{{ __('messages.sign_in_account') }}</p>

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
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
                                autocomplete="email"
                                class="block w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="you@example.com"
                            >
                        </div>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('messages.password') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-4.5 h-4.5 text-slate-400"></i>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="block w-full pl-11 pr-12 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="Enter your password"
                            >
                            <button
                                type="button"
                                onclick="togglePassword('password', this)"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <i data-lucide="eye" class="w-4.5 h-4.5 toggle-icon"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember Me & Forgot Password --}}
                    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                class="w-4 h-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500/20 cursor-pointer"
                            >
                            <span class="text-sm text-slate-600">{{ __('messages.remember_me') }}</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                            {{ __('messages.forgot_password') }}
                        </a>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold text-sm rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 active:scale-[0.98]"
                    >
                        <i data-lucide="log-in" class="w-4.5 h-4.5"></i>
                        {{ __('messages.sign_in') }}
                    </button>
                </form>

                {{-- Divider --}}
                <div class="mt-8 text-center">
                    <p class="text-sm text-slate-500">
                        {{ __('messages.no_account') }}
                        <a href="{{ route('register') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">{{ __('messages.register') }}</a>
                    </p>
                </div>

            </div>
        </div>

    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('.toggle-icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        lucide.createIcons();
    </script>

</body>
</html>
