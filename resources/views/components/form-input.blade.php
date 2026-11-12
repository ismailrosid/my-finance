@props([
    'label' => null,
    'name',
    'type' => 'text',
    'placeholder' => '',
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
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->except('class') }}
        class="form-input {{ $errors->has($name) ? 'border-red-400 focus:ring-red-500 focus:border-red-400' : '' }}">
    @if ($hint && !$errors->has($name))
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

