{{--
    MyFinance Header Notifications Component
    Real-time polling notification center with automatic alert generation and interactive dropdown.
--}}
<div
    class="relative"
    x-data="headerNotifications()"
    x-init="init()"
    @click.outside="open = false"
    @dropdown-opened.window="if ($event.detail.id !== 'notifications_menu') open = false">

    {{-- Bell Icon Button --}}
    <button
        type="button"
        @click="toggleDropdown()"
        aria-label="Notifications"
        class="relative p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors flex items-center justify-center cursor-pointer focus:outline-none"
        :class="{ 'bg-gray-100 text-gray-900': open }">
        <svg
            class="w-5 h-5 shrink-0 transition-colors"
            :class="{ 'animate-bell-swing text-gray-800': unreadCount > 0 }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        {{-- Unread Badge Counter (Sharp & Solid, no blur) --}}
        <template x-if="unreadCount > 0">
            <span class="absolute -top-0.5 -right-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white ring-2 ring-white shadow-xs">
                <span x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
            </span>
        </template>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-900/10 z-50 overflow-hidden transform"
        style="display: none;"
        x-cloak>

        {{-- Panel Header --}}
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-white">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-gray-900 tracking-tight">Notifications</h3>
                <span
                    x-show="unreadCount > 0"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-100"
                    x-text="`${unreadCount} unread`">
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-show="unreadCount > 0"
                    @click="markAllAsRead()"
                    :disabled="isMarkingAll"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition cursor-pointer disabled:opacity-50">
                    <template x-if="isMarkingAll">
                        <svg class="animate-spin w-3 h-3 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="isMarkingAll ? 'Marking...' : 'Mark all read'"></span>
                </button>
            </div>
        </div>

        {{-- Filter Tabs (All / Unread) --}}
        <div class="px-4 py-2 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2 text-xs">
            <button
                type="button"
                @click="activeFilter = 'all'"
                class="px-2.5 py-1 rounded-lg font-medium transition cursor-pointer"
                :class="activeFilter === 'all' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'">
                All (<span x-text="notifications.length"></span>)
            </button>
            <button
                type="button"
                @click="activeFilter = 'unread'"
                class="px-2.5 py-1 rounded-lg font-medium transition cursor-pointer"
                :class="activeFilter === 'unread' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-800'">
                Unread (<span x-text="unreadCount"></span>)
            </button>

            {{-- Polling Indicator Spinner --}}
            <div class="ml-auto flex items-center gap-1.5 text-[10px] text-gray-400">
                <span
                    class="w-1.5 h-1.5 rounded-full transition-colors"
                    :class="isPolling ? 'bg-emerald-500 animate-ping' : 'bg-gray-300'"></span>
                <span x-text="`${pollInterval}s poll`"></span>
            </div>
        </div>

        {{-- Notifications List --}}
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
            {{-- Loading State --}}
            <div x-show="isLoading && notifications.length === 0" class="py-8 px-4 text-center">
                <svg class="animate-spin w-5 h-5 text-emerald-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs text-gray-400">Checking notifications...</p>
            </div>

            {{-- Empty State --}}
            <div x-show="!isLoading && filteredNotifications.length === 0" class="py-8 px-4 text-center">
                <div class="w-10 h-10 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </div>
                <p class="text-xs font-semibold text-gray-700">No notifications</p>
                <p class="text-[11px] text-gray-400 mt-0.5">You're all caught up!</p>
            </div>

            {{-- List Items --}}
            <template x-for="item in filteredNotifications" :key="item.id">
                <div
                    @click="handleNotificationClick(item)"
                    class="p-3.5 hover:bg-gray-50 transition-colors flex items-start gap-3 cursor-pointer relative group"
                    :class="{ 'bg-emerald-50/20': !item.is_read }">

                    {{-- Icon Badge --}}
                    <div class="shrink-0 mt-0.5">
                        {{-- Danger (Debt Overdue / Budget Over) --}}
                        <template x-if="item.variant === 'danger'">
                            <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </template>

                        {{-- Warning (Debt Due Soon / Budget Warning / Low Balance) --}}
                        <template x-if="item.variant === 'warning'">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </template>

                        {{-- Info / Success --}}
                        <template x-if="item.variant !== 'danger' && item.variant !== 'warning'">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </template>
                    </div>

                    {{-- Text Content --}}
                    <div class="flex-1 min-w-0 pr-2">
                        <div class="flex items-center justify-between gap-1">
                            <h4 class="text-xs font-bold text-gray-900 truncate" x-text="item.title"></h4>
                            <span class="text-[10px] text-gray-400 shrink-0" x-text="item.time_ago"></span>
                        </div>
                        <p class="text-[11px] text-gray-600 mt-0.5 leading-relaxed" x-text="item.message"></p>

                        <div class="mt-2 flex items-center justify-between text-[10px]">
                            <span x-show="item.action_url" class="font-semibold text-emerald-600 group-hover:text-emerald-700 flex items-center gap-0.5">
                                View details &rarr;
                            </span>
                            <span x-show="!item.is_read" class="ml-auto inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Unread
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="p-2.5 border-t border-gray-100 bg-gray-50 text-center">
            <p class="text-[10px] text-gray-400">
                Live updates active &bull; Polling interval configured in properties
            </p>
        </div>
    </div>
</div>

<script>
    function headerNotifications() {
        return {
            open: false,
            isLoading: false,
            isPolling: false,
            isMarkingAll: false,
            unreadCount: 0,
            notifications: [],
            activeFilter: 'all', // 'all' | 'unread'
            pollInterval: {{ config('myfinance.notification_poll_interval', 15) }},
            timer: null,

            get filteredNotifications() {
                if (this.activeFilter === 'unread') {
                    return this.notifications.filter(n => !n.is_read);
                }
                return this.notifications;
            },

            init() {
                this.poll(true);
                // Start background polling
                this.timer = setInterval(() => {
                    this.poll(false);
                }, this.pollInterval * 1000);
            },

            toggleDropdown() {
                this.open = !this.open;
                if (this.open) {
                    window.dispatchEvent(new CustomEvent('dropdown-opened', { detail: { id: 'notifications_menu' } }));
                }
            },

            async poll(isInitial = false) {
                if (isInitial) this.isLoading = true;
                this.isPolling = true;

                try {
                    const res = await fetch('{{ route('notifications.poll') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        const previousCount = this.unreadCount;
                        this.unreadCount = data.unread_count || 0;
                        this.notifications = data.notifications || [];
                        if (data.poll_interval) {
                            this.pollInterval = data.poll_interval;
                        }

                        // If new unread notification detected during background polling, trigger a toast
                        if (!isInitial && this.unreadCount > previousCount && this.notifications.length > 0) {
                            const latest = this.notifications[0];
                            if (window.$toast && !latest.is_read) {
                                window.$toast({
                                    type: latest.variant || 'warning',
                                    title: latest.title,
                                    message: latest.message,
                                    duration: 5000
                                });
                            }
                        }
                    }
                } catch (err) {
                    console.error('Notification polling error:', err);
                } finally {
                    this.isLoading = false;
                    setTimeout(() => { this.isPolling = false; }, 800);
                }
            },

            async handleNotificationClick(item) {
                if (!item.is_read) {
                    await this.markAsRead(item.id);
                    item.is_read = true;
                }
                if (item.action_url) {
                    window.location.href = item.action_url;
                }
            },

            async markAsRead(id) {
                try {
                    const res = await fetch(`/notifications/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.unreadCount = data.unread_count ?? Math.max(0, this.unreadCount - 1);
                    }
                } catch (err) {
                    console.error('Error marking notification as read:', err);
                }
            },

            async markAllAsRead() {
                if (this.isMarkingAll) return;
                this.isMarkingAll = true;
                try {
                    const res = await fetch('{{ route('notifications.read-all') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        this.notifications.forEach(n => n.is_read = true);
                        this.unreadCount = 0;
                    }
                } catch (err) {
                    console.error('Error marking all as read:', err);
                } finally {
                    this.isMarkingAll = false;
                }
            }
        };
    }
</script>

<style>
    @keyframes bell-swing {
        0%, 100% { transform: rotate(0deg); }
        10% { transform: rotate(14deg); }
        20% { transform: rotate(-12deg); }
        30% { transform: rotate(10deg); }
        40% { transform: rotate(-8deg); }
        50% { transform: rotate(4deg); }
        60% { transform: rotate(0deg); }
    }

    .animate-bell-swing {
        display: inline-block;
        transform-origin: top center;
        animation: bell-swing 2.5s ease-in-out infinite;
    }
</style>

