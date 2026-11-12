@props([
    'type' => 'button',
    'href' => null,
    'size' => 'md', // 'sm' | 'md' | 'lg'
    'icon' => null,
    'iconRight' => null,
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
    {{ $attributes->merge(['class' => "btn-primary {$sizeClass}"]) }}>
    @if ($icon)
        {!! $icon !!}
    @endif
    {{ $slot }}
    @if ($iconRight)
        {!! $iconRight !!}
    @endif
</{{ $tag }}>

