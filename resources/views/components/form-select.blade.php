@props([
    'label' => null,
    'name',
    'options' => [],
    'placeholder' => 'Select an option',
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    <div class="relative">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->except('class') }}
            class="form-input appearance-none pr-9 {{ $errors->has($name) ? 'border-red-400 focus:ring-red-500 focus:border-red-400' : '' }}">
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $optLabel)
                <option value="{{ $value }}" {{ old($name) == $value ? 'selected' : '' }}>{{ $optLabel }}</option>
            @endforeach
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>
    @if ($hint && !$errors->has($name))
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

