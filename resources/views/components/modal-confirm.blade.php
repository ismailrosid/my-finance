@props([
    'id' => 'confirmModal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'confirmClass' => null,
    'variant' => 'danger', // danger | primary | warning | info
    'formAction' => null,
    'formMethod' => 'POST',
])

@php
    $confirmVariant = $variant ?? ($confirmClass && str_contains($confirmClass, 'danger') ? 'danger' : 'primary');
@endphp

<div
    x-data="{ open: false, loading: false, formAction: '{{ $formAction }}' }"
    x-on:open-modal.window="if ($event.detail.id === '{{ $id }}') { open = true; loading = false; if ($event.detail.action) formAction = $event.detail.action }"
    x-show="open"
    class="fixed inset-0 z-[9990] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    x-cloak>

    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity"
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="if (!loading) open = false">
    </div>

    {{-- Modal Card --}}
    <div
        class="relative w-full max-w-sm sm:max-w-md bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-900/10 p-6 sm:p-7 z-10 overflow-hidden transform"
        x-show="open"
        x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-250"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2 sm:translate-y-0"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-180"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        {{-- Close Icon Button --}}
        <button
            @click="if (!loading) open = false"
            type="button"
            :disabled="loading"
            aria-label="Close"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-xl transition-colors disabled:opacity-40 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Icon --}}
        <div class="w-12 h-12 rounded-2xl {{ $confirmVariant === 'danger' ? 'bg-red-50 text-red-600 border border-red-100 ring-4 ring-red-50/60' : ($confirmVariant === 'warning' ? 'bg-amber-50 text-amber-600 border border-amber-100 ring-4 ring-amber-50/60' : 'bg-emerald-50 text-emerald-600 border border-emerald-100 ring-4 ring-emerald-50/60') }} flex items-center justify-center mx-auto mb-4">
            @if ($confirmVariant === 'danger')
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            @elseif ($confirmVariant === 'warning')
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @else
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
        </div>

        {{-- Title & Message --}}
        <div class="text-center">
            <h3 class="text-base sm:text-lg font-bold text-gray-900 tracking-tight">{{ $title }}</h3>
            <p class="text-xs sm:text-sm text-gray-500 mt-2 leading-relaxed">{{ $message }}</p>
        </div>

        {{ $slot }}

        {{-- Action Buttons --}}
        <div class="mt-6 flex flex-col-reverse sm:grid sm:grid-cols-2 gap-2.5 sm:gap-3">
            <button
                type="button"
                @click="if (!loading) open = false"
                :disabled="loading"
                class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl border border-gray-200 text-xs sm:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                {{ $cancelLabel }}
            </button>

            @if ($formAction || $slot->isEmpty())
                <form :action="formAction" method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}" @submit="loading = true" class="w-full">
                    @csrf
                    @if (!in_array($formMethod, ['GET', 'POST']))
                        @method($formMethod)
                    @endif
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-semibold text-white focus:outline-none transition cursor-pointer disabled:opacity-75 disabled:cursor-wait {{ $confirmVariant === 'danger' ? 'bg-red-600 hover:bg-red-700 active:bg-red-800 shadow-sm shadow-red-600/20' : ($confirmVariant === 'warning' ? 'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 shadow-sm shadow-amber-600/20' : 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-sm shadow-emerald-600/20') }}">
                        <svg x-show="loading" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Processing...' : '{{ $confirmLabel }}'"></span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
