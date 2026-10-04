<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account — M-Delight Singer Stores</title>
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
            <div class="absolute top-20 right-16 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 left-10 w-96 h-96 bg-emerald-400/5 rounded-full blur-3xl"></div>
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
                    {{ __('messages.join_customers') }}
                </p>

                <div class="mt-12 flex items-center justify-center gap-6 text-slate-400 text-sm">
                    <div class="flex items-center gap-2">
                        <i data-lucide="star" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ __('messages.quality_products_badge') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="percent" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ __('messages.great_deals_badge') }}</span>
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

                {{-- Cancel --}}
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-emerald-700 transition-colors mb-6">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    {{ __('messages.cancel') }}
                </a>

                {{-- Heading --}}
                <h2 class="font-poppins text-2xl sm:text-3xl font-bold text-slate-900 mb-1">{{ __('messages.create_account') }}</h2>
                <p class="text-slate-500 mb-8">{{ __('messages.start_shopping') }}</p>

                {{-- Form --}}
                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    {{-- Full Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('messages.full_name_label') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-4.5 h-4.5 text-slate-400"></i>
                            </div>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autocomplete="name"
                                class="block w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="John Doe"
                            >
                        </div>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

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

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">
                            {{ __('messages.phone_number_label') }} <span class="text-slate-400 font-normal">{{ __('messages.optional') }}</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="phone" class="w-4.5 h-4.5 text-slate-400"></i>
                            </div>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                autocomplete="tel"
                                class="block w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="+1 (555) 000-0000"
                            >
                        </div>
                        @error('phone')
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
                                minlength="8"
                                autocomplete="new-password"
                                class="block w-full pl-11 pr-12 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="Minimum 8 characters"
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

                    {{-- Confirm Password --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('messages.confirm_password') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-4.5 h-4.5 text-slate-400"></i>
                            </div>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                class="block w-full pl-11 pr-12 py-3 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 bg-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="Re-enter your password"
                            >
                            <button
                                type="button"
                                onclick="togglePassword('password_confirmation', this)"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <i data-lucide="eye" class="w-4.5 h-4.5 toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Terms Checkbox --}}
                    <div class="pt-1">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                name="terms"
                                required
                                class="w-4 h-4 mt-0.5 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500/20 cursor-pointer"
                            >
                            <span class="text-sm text-slate-600 leading-snug">
                                {{ __('messages.agree_terms') }}
                                <a href="#" class="font-medium text-emerald-600 hover:text-emerald-700 transition-colors">{{ __('messages.terms_conditions') }}</a>
                                {{ __('messages.and') }}
                                <a href="#" class="font-medium text-emerald-600 hover:text-emerald-700 transition-colors">{{ __('messages.privacy_policy') }}</a>
                            </span>
                        </label>
                        @error('terms')
                            <p class="text-red-500 text-sm mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold text-sm rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200 active:scale-[0.98]"
                    >
                        <i data-lucide="user-plus" class="w-4.5 h-4.5"></i>
                        {{ __('messages.create_account') }}
                    </button>
                </form>

                {{-- Divider --}}
                <div class="mt-8 text-center">
                    <p class="text-sm text-slate-500">
                        {{ __('messages.has_account') }}
                        <a href="{{ route('login') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">{{ __('messages.sign_in') }}</a>
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
