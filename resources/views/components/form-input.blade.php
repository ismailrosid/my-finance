@props([
    'label' => null,
    'name',
    'id' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'prefix' => null,
    'suffix' => null,
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
        @if ($prefix)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                {!! $prefix !!}
            </div>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $val }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required data-required=true' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->except(['class', 'value']) }}
            class="w-full h-10.5 text-xs bg-white border rounded-xl text-gray-900 placeholder-gray-400 transition-all duration-150 shadow-sm
                   focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600
                   disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed
                   {{ $prefix ? 'pl-9' : 'px-3.5' }}
                   {{ $suffix ? 'pr-9' : 'px-3.5' }}
                   {{ $hasError ? 'border-red-400 focus:ring-red-500/10 focus:border-red-500' : 'border-gray-200 hover:border-gray-300' }}">

        @if ($suffix)
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400 text-xs font-medium">
                {!! $suffix !!}
            </div>
        @endif
    </div>

    @if ($hint && !$hasError)
        <p class="mt-1.5 text-[11px] text-gray-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[11px] text-red-500 font-medium">{{ $message }}</p>
    @enderror
</div>
