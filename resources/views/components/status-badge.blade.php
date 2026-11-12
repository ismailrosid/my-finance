@props([
    'type' => 'gray', // gray | green | red | yellow | blue
])

@php
    $classes = match($type) {
        'green'  => 'badge-green',
        'red'    => 'badge-red',
        'yellow' => 'badge-yellow',
        'blue'   => 'badge-blue',
        default  => 'badge-gray',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>

