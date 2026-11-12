@props([
    'type' => 'success', // success | error | warning | info
    'title' => null,
    'message' => '',
    'duration' => 4000,
])

@php
    $defaultTitles = [
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Information',
    ];
    $displayTitle = $title ?? ($defaultTitles[$type] ?? 'Notification');
@endphp

<div
    x-data="{
        show: true,
        progress: 100,
        interval: null,
        duration: {{ $duration }},
        init() {
            const step = 50;
            const decrement = (step / this.duration) * 100;
            this.interval = setInterval(() => {
                this.progress -= decrement;
                if (this.progress <= 0) {
                    this.dismiss();
                }
            }, step);
        },
        dismiss() {
            clearInterval(this.interval);
            this.show = false;
        },
        pause() {
            clearInterval(this.interval);
        },
        resume() {
            const step = 50;
            const decrement = (step / this.duration) * 100;
            this.interval = setInterval(() => {
                this.progress -= decrement;
                if (this.progress <= 0) {
                    this.dismiss();
                }
            }, step);
        }
    }"
    x-show="show"
    x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
    x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    @mouseenter="pause()"
    @mouseleave="resume()"
    class="pointer-events-auto w-full bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-900/10 p-4 flex items-start gap-3.5 relative overflow-hidden"
    role="alert">

    {{-- Type Icon --}}
    <div class="shrink-0 mt-0.5">
        @if ($type === 'success')
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 ring-2 ring-emerald-50 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        @elseif ($type === 'error' || $type === 'danger')
            <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 border border-red-100 ring-2 ring-red-50 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        @elseif ($type === 'warning')
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 ring-2 ring-amber-50 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        @else
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 ring-2 ring-blue-50 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0 pr-2">
        <h4 class="text-xs sm:text-sm font-bold text-gray-900 tracking-tight">{{ $displayTitle }}</h4>
        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed break-words">{{ $message }}</p>
    </div>

    {{-- Close Button --}}
    <button
        type="button"
        @click="dismiss()"
        class="shrink-0 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
        aria-label="Close notification">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    {{-- Bottom Progress Bar --}}
    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-100">
        <div
            class="h-full transition-all duration-75 {{ $type === 'success' ? 'bg-emerald-500' : ($type === 'error' || $type === 'danger' ? 'bg-red-500' : ($type === 'warning' ? 'bg-amber-500' : 'bg-blue-500')) }}"
            :style="`width: ${progress}%`">
        </div>
    </div>
</div>

