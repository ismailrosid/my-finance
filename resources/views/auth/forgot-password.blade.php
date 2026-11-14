<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('auth.forgot_password_title') }} &mdash; {{ __('common.app_name') }}</title>

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

    <div class="w-full max-w-md space-y-6">

        {{-- Logo & Heading --}}
        <div class="text-center">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-emerald-600 items-center justify-center shadow-lg shadow-emerald-500/20 mb-3">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('auth.forgot_password_title') }}</h1>
            <p class="text-xs text-gray-500 mt-1">{{ __('auth.forgot_password_subtitle') }}</p>
        </div>

        {{-- Status Notification --}}
        @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-start gap-2.5">
                <svg class="w-4 h-4 shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-semibold text-emerald-900">{{ __('common.success') }}</p>
                    <p class="mt-0.5">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        {{-- Card --}}
        <div class="bg-white py-8 px-6 sm:px-8 rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100">
            <form action="{{ route('password.email') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                {{-- Email Address --}}
                <div data-form-component="true">
                    <label for="email" class="block text-xs font-semibold text-gray-700 mb-1.5">
                        {{ __('auth.email_address') }} <span class="text-red-500">*</span>
                    </label>
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
                            value="{{ old('email') }}"
                            placeholder="name@company.com"
                            class="form-input pl-10 text-xs w-full py-2.5 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 {{ $errors->has('email') ? 'border-red-400 ring-4 ring-red-500/10' : '' }}">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-[11px] text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-md shadow-emerald-500/20 transition-all cursor-pointer">
                    <span>{{ __('auth.send_reset_link') }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                </button>
            </form>

            {{-- Back to login --}}
            <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-emerald-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>{{ __('auth.back_to_login') }}</span>
                </a>
            </div>
        </div>

        {{-- Footer note --}}
        <p class="text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} {{ __('common.app_name') }} &bull; {{ __('common.tagline') }}
        </p>
    </div>

</body>
</html>
