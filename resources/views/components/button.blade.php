@props([
    'variant' => 'primary', // primary | secondary | outline | ghost | danger | icon
    'size' => 'md',        // sm | md | lg
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'disabled' => false,
    'loading' => false,
    'fullWidth' => false,
])

@php
    // Base classes
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 select-none whitespace-nowrap cursor-pointer shrink-0';

    // Variant classes
    $variantClasses = match($variant) {
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 shadow-xs focus:ring-emerald-500/40 border border-transparent',
        'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:text-gray-900 active:bg-gray-100 shadow-xs focus:ring-gray-300/50',
        'outline' => 'bg-transparent text-emerald-600 border border-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 active:bg-emerald-100 focus:ring-emerald-500/30',
        'ghost' => 'bg-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900 active:bg-gray-200 focus:ring-gray-300/40 border border-transparent',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 shadow-xs focus:ring-red-500/40 border border-transparent',
        'icon' => 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100 active:bg-gray-200 border border-transparent focus:ring-gray-300/40',
        default => 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 shadow-xs focus:ring-emerald-500/40 border border-transparent',
    };

    // Sizing classes
    if ($iconOnly || $variant === 'icon') {
        $sizeClasses = match($size) {
            'sm' => 'w-8 h-8 p-1.5 text-xs',
            'lg' => 'w-12 h-12 p-2.5 text-base',
            default => 'w-10 h-10 p-2 text-sm',
        };
    } else {
        $sizeClasses = match($size) {
            'sm' => 'h-8 px-3 py-1.5 text-xs gap-1.5',
            'lg' => 'h-12 px-6 py-3 text-base gap-2.5',
            default => 'h-10 px-4 py-2.5 text-sm gap-2',
        };
    }

    // Icon size classes
    $iconSizeClasses = match($size) {
        'sm' => 'w-3.5 h-3.5',
        'lg' => 'w-5 h-5',
        default => 'w-4 h-4',
    };

    // State classes
    $stateClasses = ($disabled || $loading)
        ? 'opacity-50 cursor-not-allowed pointer-events-none'
        : 'active:scale-[0.99]';

    // Width classes
    $widthClasses = $fullWidth ? 'w-full' : '';

    $classes = trim("{$baseClasses} {$variantClasses} {$sizeClasses} {$stateClasses} {$widthClasses}");
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $href ? "href={$href}" : "type={$type}" }}
    @if($type === 'submit') data-btn-submit="true" @endif
    data-button-component="true"
    @if($disabled || $loading) disabled @endif
    {{ $attributes->merge(['class' => $classes]) }}>

    @if ($loading)
        <svg class="animate-spin {{ $iconSizeClasses }} shrink-0 text-current" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif ($icon)
        <span class="shrink-0 inline-flex items-center justify-center [&>svg]:{{ $iconSizeClasses }} [&>svg]:shrink-0">
            {!! $icon !!}
        </span>
    @endif

    @if (!$slot->isEmpty())
        <span class="inline-flex items-center leading-none {{ $iconOnly ? 'sr-only' : '' }} [&>svg]:{{ $iconSizeClasses }} [&>svg]:shrink-0 [&>svg]:mr-1.5">
            {{ $slot }}
        </span>
    @endif

    @if ($iconRight && !$loading)
        <span class="shrink-0 inline-flex items-center justify-center [&>svg]:{{ $iconSizeClasses }} [&>svg]:shrink-0">
            {!! $iconRight !!}
        </span>
    @endif
</{{ $tag }}>

