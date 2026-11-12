@props([
    'type' => 'button',
    'href' => null,
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'disabled' => false,
    'loading' => false,
    'fullWidth' => false,
])

<x-button
    variant="ghost"
    :type="$type"
    :href="$href"
    :size="$size"
    :icon="$icon"
    :iconRight="$iconRight"
    :iconOnly="$iconOnly"
    :disabled="$disabled"
    :loading="$loading"
    :fullWidth="$fullWidth"
    {{ $attributes }}>
    {{ $slot }}
</x-button>

