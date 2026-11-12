@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' — ' . config('app.name') : config('app.name') }}</title>
    <meta name="description" content="Personal Finance Management">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles & Scripts -->
    <link rel="stylesheet" href="{{ asset('css/myfinance.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js (local with fallback) -->
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <script>
        if (!window.Alpine) {
            document.write('<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"><\/script>');
        }
    </script>
</head>
<body class="h-full bg-gray-50 text-gray-900" x-data="layout()">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main area -->
        <div class="flex flex-col flex-1 min-w-0 transition-all duration-300 lg:ml-[260px]"
             :class="sidebarOpen ? 'lg:ml-[260px]' : 'lg:ml-[72px]'">

            <!-- Top Header -->
            @include('layouts.partials.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="px-4 sm:px-6 py-6 max-w-screen-xl mx-auto">

                    {{-- Flash Messages --}}
                    @if (session('success'))
                        <div class="alert-success mb-5" x-data="{ show: true }" x-show="show" x-transition>
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ session('success') }}</span>
                            <button @click="show = false" class="ml-auto -mr-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert-error mb-5" x-data="{ show: true }" x-show="show" x-transition>
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ session('error') }}</span>
                            <button @click="show = false" class="ml-auto -mr-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div x-show="mobileOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileOpen = false"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs z-30 lg:hidden"
         x-cloak>
    </div>

    <script>
        function layout() {
            return {
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                mobileOpen: false,
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    localStorage.setItem('sidebarOpen', this.sidebarOpen);
                },
                toggleMobile() {
                    this.mobileOpen = !this.mobileOpen;
                }
            }
        }
    </script>
</body>
</html>
