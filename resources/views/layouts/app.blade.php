<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — ' . config('app.name') : config('app.name') }}</title>
    <meta name="description" content="Personal Finance Management">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $styles ?? '' }}
</head>
<body class="h-full bg-gray-50" x-data="layout()" x-cloak>

    <div class="flex h-full">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main area -->
        <div class="flex flex-col flex-1 min-w-0 transition-all duration-300"
             :class="sidebarOpen ? 'lg:ml-[260px]' : 'lg:ml-[72px]'">

            <!-- Top Header -->
            @include('layouts.partials.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="px-6 py-6 max-w-screen-xl mx-auto">
                    {{ $slot }}

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

    {{ $scripts ?? '' }}

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

