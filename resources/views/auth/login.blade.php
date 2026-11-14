<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('auth.sign_in') }} &mdash; {{ __('common.app_name') }}</title>

    <!-- Fonts: Google Fonts (Primary) & Bunny Fonts (Fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        html, body, button, input, select, textarea {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
    </style>

    @php
        $manifestPath = public_path('build/manifest.json');
        $hasViteManifest = file_exists($manifestPath);
    @endphp

    @if ($hasViteManifest)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="{{ asset('css/myfinance.css') }}">
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
</head>
<body class="h-full antialiased text-gray-800 font-sans flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-gray-50 via-gray-100 to-emerald-50/30">

    {{-- Floating Language Switcher --}}
    <div class="fixed top-4 right-4 z-50">
        <div class="relative" x-data="{ langOpen: false }" @click.outside="langOpen = false">
            <button
                type="button"
                @click="langOpen = !langOpen"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-white/90 backdrop-blur-sm rounded-xl text-xs font-semibold text-gray-700 shadow-sm border border-gray-200 hover:bg-white cursor-pointer">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span class="uppercase tracking-wide">{{ app()->getLocale() }}</span>
                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div
                x-show="langOpen"
                x-transition
                class="absolute right-0 mt-1 w-40 bg-white rounded-xl shadow-lg border border-gray-100 py-1 overflow-hidden"
                style="display: none;"
                x-cloak>
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <button type="submit" name="locale" value="en" class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between hover:bg-gray-50 cursor-pointer {{ app()->getLocale() === 'en' ? 'font-bold text-emerald-600 bg-emerald-50/50' : 'text-gray-700' }}">
                        <span>English</span>
                        @if(app()->getLocale() === 'en')
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </button>
                    <button type="submit" name="locale" value="id" class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between hover:bg-gray-50 cursor-pointer {{ app()->getLocale() === 'id' ? 'font-bold text-emerald-600 bg-emerald-50/50' : 'text-gray-700' }}">
                        <span>Bahasa Indonesia</span>
                        @if(app()->getLocale() === 'id')
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="w-full max-w-md space-y-8" x-data="{
        email: '{{ old('email') }}',
        password: '',
        showPassword: false
    }">

        {{-- Logo & Heading --}}
        <div class="text-center">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-emerald-600 items-center justify-center shadow-lg shadow-emerald-500/20 mb-3">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('auth.sign_in_title') }}</h1>
            <p class="text-xs text-gray-500 mt-1">{{ __('auth.sign_in_subtitle') }}</p>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-800 flex items-start gap-2">
                <svg class="w-4 h-4 shrink-0 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    @foreach($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Card --}}
        <div class="bg-white py-8 px-6 sm:px-8 rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100">
            <form action="{{ route('login') }}" method="POST" class="space-y-5" novalidate>
                @csrf

                {{-- Email --}}
                <div data-form-component="true">
                    <label for="email" class="block text-xs font-semibold text-gray-700 mb-1.5">{{ __('auth.email_address') }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            data-required="true"
                            x-model="email"
                            placeholder="name@company.com"
                            class="form-input pl-10 text-xs w-full py-2.5 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                {{-- Password --}}
                <div data-form-component="true">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-gray-700">{{ __('auth.password_label') }}</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition">{{ __('auth.forgot_password') }}</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input
                            id="password"
                            name="password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            data-required="true"
                            x-model="password"
                            placeholder="••••••••"
                            class="form-input pl-10 pr-10 text-xs w-full py-2.5 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500">
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                            <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" style="display:none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="remember" class="ml-2 block text-xs text-gray-700">{{ __('auth.remember_me') }}</label>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-md shadow-emerald-500/20 transition-all cursor-pointer">
                    <span>{{ __('auth.sign_in') }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </button>
            </form>

            {{-- Link to Register --}}
            <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-500">
                    {{ __('auth.dont_have_account') }}
                    <a href="{{ route('register') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 transition">{{ __('auth.sign_up') }}</a>
                </p>
            </div>
        </div>

        {{-- Footer note --}}
        <p class="text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} {{ __('common.app_name') }} &bull; {{ __('common.tagline') }}
        </p>
    </div>

</body>
</html>
