{{--
    MyFinance Toast Container Component
    Fixed top-right notification stack supporting session flashes & dynamic JavaScript triggers.
--}}
<div
    x-data="toastManager()"
    x-on:toast-notify.window="addToast($event.detail)"
    x-on:notify.window="addToast($event.detail)"
    class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 max-w-sm w-full pointer-events-none px-4 sm:px-0"
    role="region"
    aria-live="polite"
    aria-label="Notifications"
    x-cloak>

    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto w-full bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-900/10 p-3.5 sm:p-4 flex items-start gap-3 relative overflow-hidden"
            @mouseenter="pauseToast(toast)"
            @mouseleave="resumeToast(toast)">

            {{-- Type Icon --}}
            <div class="shrink-0 mt-0.5">
                {{-- Success --}}
                <template x-if="toast.type === 'success'">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 ring-2 ring-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="toast.type === 'error' || toast.type === 'danger'">
                    <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 border border-red-100 ring-2 ring-red-50 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </template>

                {{-- Warning --}}
                <template x-if="toast.type === 'warning'">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 ring-2 ring-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </template>

                {{-- Info --}}
                <template x-if="toast.type === 'info'">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 ring-2 ring-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </template>
            </div>

            {{-- Text Content --}}
            <div class="flex-1 min-w-0 pr-2">
                <h4 class="text-xs sm:text-sm font-bold text-gray-900 tracking-tight" x-text="toast.title"></h4>
                <p class="text-xs text-gray-500 mt-0.5 leading-relaxed break-words" x-text="toast.message"></p>
            </div>

            {{-- Close Button --}}
            <button
                type="button"
                @click="removeToast(toast.id)"
                class="shrink-0 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
                aria-label="Close notification">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Bottom Progress Bar --}}
            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-100">
                <div
                    class="h-full transition-all duration-75"
                    :class="{
                        'bg-emerald-500': toast.type === 'success',
                        'bg-red-500': toast.type === 'error' || toast.type === 'danger',
                        'bg-amber-500': toast.type === 'warning',
                        'bg-blue-500': toast.type === 'info'
                    }"
                    :style="`width: ${toast.progress}%`">
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    function toastManager() {
        return {
            toasts: [],

            init() {
                // Read Server Session Flashes
                @if (session('success'))
                    this.addToast({
                        type: 'success',
                        title: 'Success',
                        message: @js(session('success')),
                        duration: 4000
                    });
                @endif

                @if (session('error'))
                    this.addToast({
                        type: 'error',
                        title: 'Error',
                        message: @js(session('error')),
                        duration: 6000
                    });
                @endif

                @if (session('warning'))
                    this.addToast({
                        type: 'warning',
                        title: 'Warning',
                        message: @js(session('warning')),
                        duration: 5000
                    });
                @endif

                @if (session('info'))
                    this.addToast({
                        type: 'info',
                        title: 'Information',
                        message: @js(session('info')),
                        duration: 4000
                    });
                @endif

                @if (session('status'))
                    this.addToast({
                        type: 'info',
                        title: 'Notification',
                        message: @js(session('status')),
                        duration: 4500
                    });
                @endif

                @if ($errors->any())
                    @foreach ($errors->all() as $err)
                        this.addToast({
                            type: 'error',
                            title: 'Validation Error',
                            message: @js($err),
                            duration: 6000
                        });
                    @endforeach
                @endif
            },

            addToast(detail) {
                const message = detail.message || '';
                // Deduplicate: ignore if an active toast with the same message is already displayed
                if (message && this.toasts.some(t => t.message === message && t.visible)) {
                    return;
                }

                const id = Date.now() + Math.random().toString(36).substring(2, 7);
                const type = detail.type || 'info';
                const defaultTitles = {
                    success: 'Success',
                    error: 'Error',
                    danger: 'Error',
                    warning: 'Warning',
                    info: 'Information'
                };
                const title = detail.title || defaultTitles[type] || 'Notification';
                const duration = detail.duration || (type === 'error' || type === 'danger' ? 6000 : (type === 'warning' ? 5000 : 4000));

                const toast = {
                    id: id,
                    type: type,
                    title: title,
                    message: message,
                    duration: duration,
                    progress: 100,
                    visible: true,
                    interval: null
                };

                this.startTimer(toast);
                this.toasts.push(toast);

                // Keep max 5 toasts visible simultaneously
                if (this.toasts.length > 5) {
                    const oldest = this.toasts[0];
                    this.removeToast(oldest.id);
                }
            },

            startTimer(toast) {
                const step = 50;
                const decrement = (step / toast.duration) * 100;
                toast.interval = setInterval(() => {
                    toast.progress -= decrement;
                    if (toast.progress <= 0) {
                        this.removeToast(toast.id);
                    }
                }, step);
            },

            pauseToast(toast) {
                if (toast.interval) {
                    clearInterval(toast.interval);
                    toast.interval = null;
                }
            },

            resumeToast(toast) {
                if (!toast.interval && toast.visible) {
                    this.startTimer(toast);
                }
            },

            removeToast(id) {
                const idx = this.toasts.findIndex(t => t.id === id);
                if (idx !== -1) {
                    const toast = this.toasts[idx];
                    if (toast.interval) clearInterval(toast.interval);
                    toast.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 250);
                }
            }
        };
    }

    // Global helper accessible anywhere: window.$toast({ type: 'success', title: '...', message: '...' })
    window.$toast = function(options) {
        if (typeof options === 'string') {
            options = { message: options, type: 'info' };
        }
        window.dispatchEvent(new CustomEvent('toast-notify', { detail: options }));
    };
</script>

