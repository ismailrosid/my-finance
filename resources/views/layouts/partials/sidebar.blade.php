{{-- Sidebar --}}
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-gray-200 transition-all duration-300 w-[260px] lg:translate-x-0 shadow-2xl lg:shadow-none"
    :class="{
        'lg:w-[260px]': sidebarOpen,
        'lg:w-[72px]': !sidebarOpen,
        'translate-x-0': mobileOpen,
        '-translate-x-full': !mobileOpen
    }">

    {{-- Logo / Brand Header --}}
    <div class="flex items-center h-16 border-b border-gray-100 shrink-0 transition-all duration-300"
         :class="isExpanded ? 'px-4 justify-between' : 'px-0 justify-center'">
        <a href="{{ route('dashboard') }}"
           class="flex items-center min-w-0 transition-all duration-300"
           :class="isExpanded ? 'gap-2.5' : 'justify-center w-full'">
            {{-- Logo Icon --}}
            <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center shrink-0 shadow-xs">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            {{-- Brand name (hide when collapsed on desktop) --}}
            <div class="overflow-hidden transition-all duration-300"
                 x-show="isExpanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">
                <span class="text-sm font-bold text-gray-900 whitespace-nowrap">MyFinance</span>
                <span class="block text-xs text-gray-400 whitespace-nowrap leading-none">Personal Finance</span>
            </div>
        </a>

        {{-- Desktop: Collapse button (visible when expanded) --}}
        <button @click="toggleSidebar()"
                type="button"
                x-show="sidebarOpen"
                aria-label="Collapse Sidebar"
                class="hidden lg:flex w-7 h-7 items-center justify-center rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors shrink-0 cursor-pointer">
            <svg class="w-4 h-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Mobile: Close button (✕) --}}
        <button @click="mobileOpen = false"
                type="button"
                aria-label="Close Sidebar"
                class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Floating Expand Button on Border (Desktop) - Visible when collapsed --}}
    <button @click="toggleSidebar()"
            type="button"
            x-show="!sidebarOpen"
            x-cloak
            aria-label="Expand Sidebar"
            title="Expand Sidebar"
            class="hidden lg:flex absolute -right-3 top-5 z-50 w-6 h-6 items-center justify-center rounded-full bg-white border border-gray-200 text-gray-400 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 shadow-xs transition-all duration-150 cursor-pointer">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-6">

        {{-- Overview --}}
        @if(auth()->check() && auth()->user()->hasPermission('dashboard'))
        <div>
            <p class="sidebar-section-label transition-all duration-300 overflow-hidden"
               :class="isExpanded ? 'opacity-100 h-auto mb-1' : 'opacity-0 h-0 mb-0'">
                Overview
            </p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('dashboard') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       x-tooltip.left="(!isExpanded && !isMobile) ? 'Dashboard' : false">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
        @endif

        {{-- Financial Management --}}
        @php
            $showFinancial = auth()->check() && (
                auth()->user()->hasPermission('transactions') ||
                auth()->user()->hasPermission('accounts') ||
                auth()->user()->hasPermission('debts') ||
                auth()->user()->hasPermission('budgets')
            );
        @endphp
        @if($showFinancial)
        <div>
            <p class="sidebar-section-label transition-all duration-300 overflow-hidden"
               :class="isExpanded ? 'opacity-100 h-auto mb-1' : 'opacity-0 h-0 mb-0'">
                Financial Management
            </p>
            <ul class="space-y-0.5">
                @if(auth()->user()->hasPermission('transactions'))
                <li>
                    <a href="{{ route('transactions.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Transactions</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('accounts'))
                <li>
                    <a href="{{ route('accounts.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Accounts</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('debts'))
                <li>
                    <a href="{{ route('debts.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('debts.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Debts & Installments</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('budgets'))
                <li>
                    <a href="{{ route('budgets.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Budgets</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
        @endif

        {{-- Analytics --}}
        @if(auth()->check() && auth()->user()->hasPermission('reports'))
        <div>
            <p class="sidebar-section-label transition-all duration-300 overflow-hidden"
               :class="isExpanded ? 'opacity-100 h-auto mb-1' : 'opacity-0 h-0 mb-0'">
                Analytics
            </p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('reports.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Financial Reports</span>
                    </a>
                </li>
            </ul>
        </div>
        @endif

        {{-- Preferences --}}
        @if(auth()->check() && auth()->user()->hasPermission('settings'))
        <div>
            <p class="sidebar-section-label transition-all duration-300 overflow-hidden"
               :class="isExpanded ? 'opacity-100 h-auto mb-1' : 'opacity-0 h-0 mb-0'">
                Preferences
            </p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('settings.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
        @endif

        {{-- Administration --}}
        @php
            $showAdmin = auth()->check() && (
                auth()->user()->hasPermission('roles') ||
                auth()->user()->hasPermission('audit')
            );
        @endphp
        @if($showAdmin)
        <div>
            <p class="sidebar-section-label transition-all duration-300 overflow-hidden"
               :class="isExpanded ? 'opacity-100 h-auto mb-1' : 'opacity-0 h-0 mb-0'">
                Administration
            </p>
            <ul class="space-y-0.5">
                @if(auth()->user()->hasPermission('roles'))
                <li>
                    <a href="{{ route('roles.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Roles & Access</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasPermission('audit'))
                <li>
                    <a href="{{ route('audit-reports.index') }}"
                       @click="if (isMobile) mobileOpen = false"
                       class="sidebar-link {{ request()->routeIs('audit-reports.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                              :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'">Audit Reports</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
        @endif

    </nav>
</aside>

