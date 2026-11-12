{{--
    MyFinance Custom Confirmation Dialog Component
    Replaces browser native window.confirm() with modern, accessible, and responsive dialog.
--}}
<div
    x-data="confirmationDialog()"
    x-on:open-confirmation.window="openDialog($event.detail)"
    x-on:open-confirm.window="openDialog($event.detail)"
    x-on:keydown.escape.window="if (isOpen && !isLoading) closeDialog()"
    x-cloak>

    {{-- Backdrop & Modal Container --}}
    <div
        x-show="isOpen"
        class="fixed inset-0 z-[9990] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
        aria-labelledby="confirm-dialog-title"
        role="dialog"
        aria-modal="true">

        {{-- Backdrop Overlay --}}
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="if (!isLoading) closeDialog()"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity"
            aria-hidden="true"></div>

        {{-- Dialog Card --}}
        <div
            x-show="isOpen"
            x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-250"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-180"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-sm sm:max-w-md bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-900/10 p-6 sm:p-7 z-10 overflow-hidden transform">

            {{-- Close Button (X) --}}
            <button
                type="button"
                @click="closeDialog()"
                :disabled="isLoading"
                class="absolute top-4 right-4 p-1.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                aria-label="Close dialog">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Icon Container with Variant Styles --}}
            <div class="flex items-center justify-center mx-auto mb-4">
                {{-- Danger Icon (Red) --}}
                <div
                    x-show="config.variant === 'danger'"
                    class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 border border-red-100 ring-4 ring-red-50/60 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                {{-- Warning Icon (Amber) --}}
                <div
                    x-show="config.variant === 'warning'"
                    class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 ring-4 ring-amber-50/60 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Primary / Success Icon (Emerald) --}}
                <div
                    x-show="config.variant === 'primary' || config.variant === 'success' || config.variant === 'default'"
                    class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 ring-4 ring-emerald-50/60 flex items-center justify-center">
                    <template x-if="config.icon === 'logout'">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </template>
                    <template x-if="config.icon !== 'logout'">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>

                {{-- Info Icon (Blue) --}}
                <div
                    x-show="config.variant === 'info'"
                    class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 ring-4 ring-blue-50/60 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            {{-- Title & Description --}}
            <div class="text-center">
                <h3
                    id="confirm-dialog-title"
                    class="text-base sm:text-lg font-bold text-gray-900 tracking-tight"
                    x-text="config.title">
                </h3>
                <p
                    class="text-xs sm:text-sm text-gray-500 mt-2 leading-relaxed"
                    x-text="config.description">
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="mt-6 flex flex-col-reverse sm:grid sm:grid-cols-2 gap-2.5 sm:gap-3">
                {{-- Cancel Button --}}
                <button
                    type="button"
                    @click="closeDialog()"
                    :disabled="isLoading"
                    class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl border border-gray-200 text-xs sm:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                    x-text="config.cancelText">
                </button>

                {{-- Confirm Button --}}
                <button
                    type="button"
                    @click="confirmAction()"
                    :disabled="isLoading"
                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-semibold text-white focus:outline-none focus:ring-2 transition cursor-pointer disabled:opacity-75 disabled:cursor-wait"
                    :class="{
                        'bg-red-600 hover:bg-red-700 active:bg-red-800 shadow-sm shadow-red-600/20 focus:ring-red-500/40': config.variant === 'danger',
                        'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 shadow-sm shadow-amber-600/20 focus:ring-amber-500/40': config.variant === 'warning',
                        'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-600/20 focus:ring-blue-500/40': config.variant === 'info',
                        'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-sm shadow-emerald-600/20 focus:ring-emerald-500/40': config.variant === 'primary' || config.variant === 'success' || config.variant === 'default'
                    }">
                    {{-- Spinner on Loading --}}
                    <svg
                        x-show="isLoading"
                        class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white"
                        fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isLoading ? 'Processing...' : config.confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmationDialog() {
        return {
            isOpen: false,
            isLoading: false,
            config: {
                title: 'Confirm Action',
                description: 'Are you sure you want to proceed?',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                variant: 'primary', // 'primary' | 'danger' | 'warning' | 'info'
                icon: null,
                form: null,
                onConfirm: null,
                onCancel: null
            },

            openDialog(detail) {
                this.config = {
                    title: detail.title || 'Confirm Action',
                    description: detail.description || 'Are you sure you want to proceed?',
                    confirmText: detail.confirmText || 'Confirm',
                    cancelText: detail.cancelText || 'Cancel',
                    variant: detail.variant || 'primary',
                    icon: detail.icon || null,
                    form: detail.form || null,
                    onConfirm: detail.onConfirm || null,
                    onCancel: detail.onCancel || null
                };
                this.isLoading = false;
                this.isOpen = true;
            },

            closeDialog() {
                if (this.isLoading) return;
                this.isOpen = false;
                if (typeof this.config.onCancel === 'function') {
                    this.config.onCancel();
                }
            },

            async confirmAction() {
                if (this.isLoading) return;
                this.isLoading = true;
                if (window.startTopProgress) window.startTopProgress();

                // Scenario 1: Form submission
                if (this.config.form) {
                    let formEl = this.config.form;
                    if (typeof formEl === 'string') {
                        formEl = document.querySelector(formEl);
                    }
                    if (formEl && typeof formEl.submit === 'function') {
                        // Submit form natively
                        formEl.submit();
                        return;
                    }
                }

                // Scenario 2: Async or Sync Callback
                if (typeof this.config.onConfirm === 'function') {
                    try {
                        await this.config.onConfirm();
                        this.isLoading = false;
                        this.isOpen = false;
                        if (window.finishTopProgress) window.finishTopProgress();
                    } catch (err) {
                        this.isLoading = false;
                        if (window.finishTopProgress) window.finishTopProgress();
                        if (window.$toast) {
                            window.$toast({
                                type: 'error',
                                title: 'Error',
                                message: err.message || 'Action failed. Please try again.'
                            });
                        }
                    }
                    return;
                }

                // Fallback: close after small delay
                this.isLoading = false;
                this.isOpen = false;
                if (window.finishTopProgress) window.finishTopProgress();
            }
        };
    }

    // Global helper accessible everywhere
    window.$confirm = function(options) {
        window.dispatchEvent(new CustomEvent('open-confirmation', { detail: options }));
    };
</script>

