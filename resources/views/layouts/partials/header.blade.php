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

    {{-- Brand / Logo (mobile) --}}
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0 lg:hidden flex-1">
        <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center shrink-0 shadow-xs">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <span class="text-sm font-bold text-gray-900 tracking-tight">MyFinance</span>
    </a>

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
                   placeholder="{{ __('common.search') }}..."
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
            <span class="text-xs font-medium text-gray-600">{{ \App\Support\FormatHelper::date(now(), 'M Y') }}</span>
        </div>

        {{-- Language Selector Dropdown --}}
        <div class="relative" x-data="{ langOpen: false }" @click.outside="langOpen = false">
            <button
                type="button"
                @click="langOpen = !langOpen"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors border border-gray-200 cursor-pointer"
                title="{{ __('common.switch_language') }}"
                aria-label="{{ __('common.switch_language') }}">
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span class="font-semibold uppercase tracking-wider text-[11px]">{{ app()->getLocale() }}</span>
                <svg class="w-3 h-3 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': langOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                x-show="langOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                class="absolute right-0 mt-2 w-44 bg-white rounded-xl border border-gray-100 shadow-xl shadow-gray-900/10 py-1 z-50 overflow-hidden"
                style="display: none;"
                x-cloak>
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <button
                        type="submit"
                        name="locale"
                        value="en"
                        class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-left transition hover:bg-gray-50 cursor-pointer {{ app()->getLocale() === 'en' ? 'font-bold text-emerald-700 bg-emerald-50/50' : 'text-gray-700' }}">
                        <span>English</span>
                        @if(app()->getLocale() === 'en')
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </button>
                    <button
                        type="submit"
                        name="locale"
                        value="id"
                        class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-left transition hover:bg-gray-50 cursor-pointer {{ app()->getLocale() === 'id' ? 'font-bold text-emerald-700 bg-emerald-50/50' : 'text-gray-700' }}">
                        <span>Bahasa Indonesia</span>
                        @if(app()->getLocale() === 'id')
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </button>
                </form>
            </div>
        </div>

        {{-- Privacy Mode (Blur Balances) Toggle Button --}}
        <button
            type="button"
            @click="$store.privacy?.toggle()"
            :title="$store.privacy?.active ? '{{ __('common.privacy_mode') }}' : '{{ __('common.privacy_mode') }}'"
            aria-label="Toggle Privacy Mode"
            class="p-2 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors flex items-center justify-center cursor-pointer border border-transparent hover:border-gray-200"
            :class="{ 'bg-emerald-50 text-emerald-700 border-emerald-200': $store.privacy?.active }">
            {{-- Eye Off (Shown when active / blurred) --}}
            <svg x-show="$store.privacy?.active" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
            {{-- Eye (Shown when inactive / visible) --}}
            <svg x-show="!$store.privacy?.active" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>

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
                                title: @js(__('auth.sign_out_confirm_title')),
                                description: @js(__('auth.sign_out_confirm_desc')),
                                confirmText: @js(__('auth.sign_out')),
                                cancelText: @js(__('common.cancel')),
                                variant: 'primary',
                                icon: 'logout',
                                form: $el.closest('form')
                            })"
                            class="flex w-full items-center gap-2.5 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            {{ __('auth.sign_out') }}
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

