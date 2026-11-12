@props([
    'label' => null,
    'name',
    'hint' => null,
    'required' => false,
    'min' => null,
    'max' => null,
])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <div class="relative">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <input
            type="date"
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $min ? "min={$min}" : '' }}
            {{ $max ? "max={$max}" : '' }}
            {{ $required ? 'required' : '' }}
            {{ $attributes->except(['class', 'min', 'max']) }}
            class="form-input pl-9 {{ $errors->has($name) ? 'border-red-400 focus:ring-red-500 focus:border-red-400' : '' }}">
    </div>
    @if ($hint && !$errors->has($name))
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

