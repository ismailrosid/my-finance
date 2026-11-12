@props([
    'label' => null,
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'searchPlaceholder' => 'Search option...',
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'clearable' => false,
    'loading' => false,
    'searchable' => true,
    'autoSubmit' => false,
    'size' => 'md',
    'align' => 'left',
    'minWidth' => null,
    'native' => false,
])

@if ($native)
    @php
        $id = $id ?? $name;
        $hasError = isset($errors) && $errors->has($name);
    @endphp

    <div {{ $attributes->only('class') }}>
        @if ($label)
            <label for="{{ $id }}" class="form-label">
                {{ $label }}
                @if ($required) <span class="text-red-500">*</span> @endif
            </label>
        @endif
        <div class="relative">
            <select
                id="{{ $id }}"
                name="{{ $name }}"
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $attributes->except('class') }}
                class="form-input appearance-none pr-9 {{ $hasError ? 'border-red-400 focus:ring-red-500 focus:border-red-400' : '' }}">
                @if ($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach ($options as $optVal => $optLabel)
                    <option value="{{ $optVal }}" {{ old($name, $value) == $optVal ? 'selected' : '' }}>
                        {{ is_array($optLabel) ? ($optLabel['label'] ?? $optVal) : $optLabel }}
                    </option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
        @if ($hint && !$hasError)
            <p class="form-hint">{{ $hint }}</p>
        @endif
        @error($name)
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
@else
    <x-form-select-searchable
        :label="$label"
        :name="$name"
        :id="$id"
        :options="$options"
        :value="$value"
        :placeholder="$placeholder"
        :searchPlaceholder="$searchPlaceholder"
        :hint="$hint"
        :required="$required"
        :disabled="$disabled"
        :clearable="$clearable"
        :loading="$loading"
        :searchable="$searchable"
        :autoSubmit="$autoSubmit"
        :size="$size"
        :align="$align"
        :minWidth="$minWidth"
        {{ $attributes }}
    />
@endif
