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

    <!-- Fonts: Google Fonts (Primary) & Bunny Fonts (Fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Global Poppins Font Enforcement & Privacy Blur Mode -->
    <style>
        html, body, button, input, select, textarea, .form-input, .form-select, .form-label {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        /* Privacy Blur Mode */
        .privacy-blur {
            transition: filter 0.2s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
            display: inline-block;
            border-radius: 4px;
        }

        .privacy-mode .privacy-blur {
            filter: blur(7px);
            user-select: none;
            -webkit-user-select: none;
            cursor: pointer;
        }

        /* Hover or click/tap to reveal */
        .privacy-mode .privacy-blur:hover,
        .privacy-mode .privacy-blur:active,
        .privacy-mode .privacy-blur:focus,
        .privacy-mode .privacy-blur.privacy-revealed {
            filter: blur(0px) !important;
        }
    </style>

    <!-- Immediate execution to apply privacy mode before DOM render -->
    <script>
        if (localStorage.getItem('privacy_mode') === 'true') {
            document.documentElement.classList.add('privacy-mode');
        }
    </script>

    <!-- Styles & Scripts -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/myfinance.css') }}">
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script>
        if (typeof tailwind !== 'undefined') {
            tailwind.config = {
                theme: {
                    extend: {
                        spacing: {
                            '8.5': '2.125rem',
                            '10.5': '2.625rem',
                        },
                        borderRadius: {
                            'xl': '0.75rem',
                            '2xl': '1rem',
                        },
                        colors: {
                            stroke: '#E2E8F0',
                            strokedark: '#2E3A47',
                            boxdark: '#24303F',
                            body: '#64748B',
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
                            sans: ['Poppins', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                            poppins: ['Poppins', 'sans-serif'],
                        }
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

    <!-- Global UI Components: Confirmation Dialog & Toast Container -->
    <x-ui.confirmation-dialog />
    <x-ui.toast-container />

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('privacy', {
                active: localStorage.getItem('privacy_mode') === 'true',
                toggle() {
                    this.active = !this.active;
                    localStorage.setItem('privacy_mode', this.active);
                    if (this.active) {
                        document.documentElement.classList.add('privacy-mode');
                    } else {
                        document.documentElement.classList.remove('privacy-mode');
                        document.querySelectorAll('.privacy-revealed').forEach(el => el.classList.remove('privacy-revealed'));
                    }
                }
            });
        });

        // Click / Tap to toggle peek on specific blurred value
        document.addEventListener('click', (e) => {
            const el = e.target.closest('.privacy-blur');
            if (el && document.documentElement.classList.contains('privacy-mode')) {
                el.classList.toggle('privacy-revealed');
            }
        });

        function layout() {
            return {
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                mobileOpen: false,
                isMobile: window.innerWidth < 1024,
                get isExpanded() {
                    return this.isMobile ? true : this.sidebarOpen;
                },
                init() {
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 1024;
                        if (!this.isMobile) {
                            this.mobileOpen = false;
                        }
                    });
                },
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
