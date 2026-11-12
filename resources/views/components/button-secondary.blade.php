@props([
    'type' => 'button',
    'href' => null,
    'size' => 'md',
    'icon' => null,
])

@php
    $sizeClass = match($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $href ? "href={$href}" : "type={$type}" }}
    {{ $attributes->merge(['class' => "btn-secondary {$sizeClass}"]) }}>
    @if ($icon)
        {!! $icon !!}
    @endif
    {{ $slot }}
</{{ $tag }}>

