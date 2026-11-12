@props([
    'size' => 'md', // xs | sm | md | lg | xl
    'color' => 'current', // current | emerald | white | gray | red | amber | blue
    'class' => '',
])

@php
    $sizeClasses = match($size) {
        'xs' => 'w-3 h-3',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-6 h-6',
        'xl' => 'w-8 h-8',
        default => 'w-4 h-4',
    };

    $colorClasses = match($color) {
        'current' => 'text-current',
        'emerald' => 'text-emerald-600',
        'white' => 'text-white',
        'gray' => 'text-gray-400',
        'red' => 'text-red-600',
        'amber' => 'text-amber-600',
        'blue' => 'text-blue-600',
        default => 'text-current',
    };
@endphp

<svg {{ $attributes->merge(['class' => "animate-spin {$sizeClasses} {$colorClasses} shrink-0 {$class}"]) }} fill="none" viewBox="0 0 24 24" aria-hidden="true">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle>
    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>

