@props([
    'label' => null,
    'name',
    'id' => null,
    'value' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'min' => null,
    'max' => null,
])

@php
    $id = $id ?? $name;
    $hasError = isset($errors) && $errors->has($name);
    $val = old($name, $value ?? '');
@endphp

<div data-form-component="true" {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-xs font-semibold text-gray-700 mb-1.5">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <div class="relative rounded-xl shadow-sm">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <input
            type="date"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $val }}"
            {{ $min ? "min={$min}" : '' }}
            {{ $max ? "max={$max}" : '' }}
            {{ $required ? 'required data-required=true' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->except(['class', 'min', 'max', 'value']) }}
            class="w-full h-10.5 pl-10 pr-3.5 text-xs bg-white border rounded-xl text-gray-900 transition-all duration-150 shadow-sm
                   focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600
                   disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed
                   {{ $hasError ? 'border-red-400 focus:ring-red-500/10 focus:border-red-500' : 'border-gray-200 hover:border-gray-300' }}">
    </div>
    @if ($hint && !$hasError)
        <p class="mt-1.5 text-[11px] text-gray-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[11px] text-red-500 font-medium">{{ $message }}</p>
    @enderror
</div>
