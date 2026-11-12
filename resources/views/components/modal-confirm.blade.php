@props([
    'id' => 'confirmModal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'confirmClass' => 'btn-primary',
    'formAction' => null,
    'formMethod' => 'POST',
])

<div
    x-data="{ open: false, formAction: '{{ $formAction }}' }"
    x-on:open-modal.window="if ($event.detail.id === '{{ $id }}') { open = true; if ($event.detail.action) formAction = $event.detail.action }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-cloak>

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-xs" @click="open = false"></div>

    {{-- Modal --}}
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        {{-- Icon --}}
        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <h3 class="text-base font-semibold text-gray-900 text-center mb-1">{{ $title }}</h3>
        <p class="text-sm text-gray-500 text-center mb-6">{{ $message }}</p>

        {{ $slot }}

        <div class="flex gap-3">
            <button @click="open = false"
                    class="btn-secondary flex-1">
                {{ $cancelLabel }}
            </button>
            @if ($formAction || $slot->isEmpty())
                <form :action="formAction" method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}" class="flex-1">
                    @csrf
                    @if (!in_array($formMethod, ['GET', 'POST']))
                        @method($formMethod)
                    @endif
                    <button type="submit" class="{{ $confirmClass }} w-full justify-center">
                        {{ $confirmLabel }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

