{{-- Top Header Bar --}}
<header class="sticky top-0 z-20 flex items-center h-16 px-4 lg:px-6 bg-white/90 backdrop-blur-sm border-b border-gray-200 shrink-0">

    {{-- Mobile: Hamburger --}}
    <button @click="toggleMobile()"
            type="button"
            aria-label="Toggle Menu"
            class="lg:hidden mr-3 p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors flex items-center justify-center cursor-pointer">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Page Title (mobile) --}}
    <div class="flex-1 min-w-0 lg:hidden">
        <span class="text-sm font-semibold text-gray-900">{{ $headerTitle ?? config('app.name') }}</span>
    </div>

    {{-- Spacer (desktop) --}}
    <div class="hidden lg:flex flex-1 items-center gap-3">
        {{-- Search (placeholder for future use) --}}
        <div class="relative max-w-xs">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="search"
                   placeholder="Search..."
                   class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
        </div>
    </div>

    {{-- Right side actions --}}
    <div class="flex items-center gap-2">

        {{-- Current Period Badge --}}
        <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 rounded-lg">
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-xs font-medium text-gray-600">{{ now()->format('M Y') }}</span>
        </div>

        {{-- Interactive Real-time Notifications Center --}}
        @include('layouts.partials.header-notifications')

        {{-- User avatar + dropdown --}}
        <div class="relative"
             x-data="{ open: false }"
             @click.outside="open = false"
             @dropdown-opened.window="if ($event.detail?.id !== 'user_header_menu') open = false">
            <button @click="open = !open; if (open) window.dispatchEvent(new CustomEvent('dropdown-opened', { detail: { id: 'user_header_menu' } }))"
                    type="button"
                    class="flex items-center gap-2.5 p-1 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer border border-transparent hover:border-gray-200"
                    :class="{ 'bg-gray-100 border-gray-200': open }">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <span class="text-xs font-bold text-emerald-700">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </span>
                </div>
                <div class="hidden sm:block text-left min-w-0 max-w-[130px]">
                    <p class="text-xs font-bold text-gray-900 truncate leading-tight">{{ auth()->user()?->name ?? 'User' }}</p>
                    <p class="text-[10px] text-gray-400 truncate leading-tight">{{ auth()->user()?->roles->first()?->role_name ?? 'User' }}</p>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Dropdown --}}
            <div x-show="open"
                 x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-900/10 py-1.5 z-50 overflow-hidden"
                 style="display: none;"
                 x-cloak>
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                    <p class="text-xs font-bold text-gray-900 leading-tight">{{ auth()->user()?->name ?? 'User' }}</p>
                    <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ auth()->user()?->email ?? 'user@example.com' }}</p>
                </div>

                <div class="py-1">
                    @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="button"
                            @click="$confirm({
                                title: 'Sign out from MyFinance?',
                                description: 'Are you sure you want to sign out of your account? You will need to sign in again to access your financial data.',
                                confirmText: 'Sign Out',
                                cancelText: 'Cancel',
                                variant: 'primary',
                                icon: 'logout',
                                form: $el.closest('form')
                            })"
                            class="flex w-full items-center gap-2.5 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

