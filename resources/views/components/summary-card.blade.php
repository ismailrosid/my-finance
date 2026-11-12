@props([
    'value' => null,
    'label',
    'icon' => null,
    'iconBg' => 'bg-emerald-100',
    'iconColor' => 'text-emerald-600',
    'change' => null,
    'changeType' => 'up', // 'up' | 'down' | 'neutral'
    'prefix' => null,
    'suffix' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    {{ $href ? "href={$href}" : '' }}
    {{ $attributes->merge(['class' => 'summary-card' . ($href ? ' cursor-pointer hover:border-emerald-200' : '')]) }}>

    <div class="flex items-start justify-between mb-4">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
        </div>
        @if ($icon)
            <div class="w-10 h-10 rounded-xl {{ $iconBg }} flex items-center justify-center shrink-0">
                <span class="{{ $iconColor }}">
                    {!! $icon !!}
                </span>
            </div>
        @endif
    </div>

    <div class="flex items-end justify-between">
        <div>
            <p class="text-2xl font-bold text-gray-900 leading-none">
                @if ($prefix)<span class="text-base font-semibold text-gray-500 mr-0.5">{{ $prefix }}</span>@endif
                {{ $value ?? '—' }}
                @if ($suffix)<span class="text-base font-semibold text-gray-500 ml-0.5">{{ $suffix }}</span>@endif
            </p>
        </div>
        @if ($change !== null)
            <div class="{{ $changeType === 'up' ? 'stat-up' : ($changeType === 'down' ? 'stat-down' : 'text-gray-500 text-xs font-medium flex items-center gap-0.5') }}">
                @if ($changeType === 'up')
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                @elseif ($changeType === 'down')
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                @endif
                <span>{{ $change }}</span>
            </div>
        @endif
    </div>

    {{ $slot }}
</{{ $tag }}>

