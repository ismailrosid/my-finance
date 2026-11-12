@props([
    'label' => null,
    'name',
    'id' => null,
    'placeholder' => '',
    'value' => null,
    'rows' => 3,
    'hint' => null,
    'required' => false,
    'disabled' => false,
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
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required data-required=true' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->except(['class', 'rows']) }}
        class="w-full p-3.5 text-xs bg-white border rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm
               focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600
               disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed
               {{ $hasError ? 'border-red-400 focus:ring-red-500/10 focus:border-red-500' : 'border-gray-200 hover:border-gray-300' }}">{{ $val }}</textarea>
    @if ($hint && !$hasError)
        <p class="mt-1.5 text-[11px] text-gray-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[11px] text-red-500 font-medium">{{ $message }}</p>
    @enderror
</div>

