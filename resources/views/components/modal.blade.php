@props([
    'name' => null,
    'show' => null,
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'maxWidth' => 'lg',
    'resetOnClose' => true,
])

@php
    $maxWidthClass = match ($maxWidth) {
        'sm'  => 'max-w-sm',
        'md'  => 'max-w-md',
        'lg'  => 'max-w-lg',
        'xl'  => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        default => 'max-w-lg',
    };

    $showCondition = $show ?? ($name ? $name : 'open');
@endphp

<div
    x-data="{
        resetModal() {
            this.$nextTick(() => {
                const forms = this.$el.querySelectorAll('form');
                forms.forEach(form => {
                    form.reset();
                    form.dispatchEvent(new CustomEvent('reset'));
                    window.dispatchEvent(new CustomEvent('reset-select', { detail: { form: form } }));
                });
                this.$dispatch('modal-closed');
            });
        },
        closeModal() {
            {{ $showCondition }} = false;
            if (@js($resetOnClose)) {
                this.resetModal();
            }
        }
    }"
    x-init="
        if (@js($resetOnClose)) {
            $watch('{{ $showCondition }}', (value) => {
                if (!value) {
                    resetModal();
                }
            });
        }
    "
    x-show="{{ $showCondition }}"
    @keydown.escape.window="if ({{ $showCondition }}) closeModal()"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    x-cloak
    {{ $attributes->only('class') }}>

    {{-- Backdrop --}}
    <div
        x-show="{{ $showCondition }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
        @click="closeModal()"></div>

    {{-- Modal Dialog Container --}}
    <div class="min-h-full px-4 py-6 sm:py-10 text-center flex items-center justify-center"
         @click.self="closeModal()">
        <div
            x-show="{{ $showCondition }}"
            x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-3"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="inline-block w-full {{ $maxWidthClass }} p-6 sm:p-7 text-left align-middle bg-white rounded-2xl shadow-2xl border border-gray-100 transform transition-all relative z-10">

            {{-- Header --}}
            <div class="flex items-start justify-between pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    @if ($icon)
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            {!! $icon !!}
                        </div>
                    @endif
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ $title }}</h3>
                        @if ($subtitle)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                {{-- Close Button --}}
                <button
                    type="button"
                    @click="closeModal()"
                    aria-label="Close modal"
                    class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex items-center justify-center cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content Slot --}}
            <div class="mt-5">
                {{ $slot }}
            </div>

            {{-- Optional Footer Slot --}}
            @if (isset($footer))
                <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    {{ $footer }}
                </div>
            @endif

        </div>
    </div>
</div>

