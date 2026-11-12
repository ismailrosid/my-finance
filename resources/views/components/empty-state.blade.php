@props([
    'title' => 'No data yet',
    'description' => 'Get started by adding your first record.',
    'icon' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div class="empty-state">
    <div class="empty-state-icon">
        @if ($icon)
            {!! $icon !!}
        @else
            <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        @endif
    </div>
    <h3 class="empty-state-title">{{ $title }}</h3>
    <p class="empty-state-desc">{{ $description }}</p>
    @if ($actionLabel && $actionHref)
        <x-button-primary :href="$actionHref">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ $actionLabel }}
        </x-button-primary>
    @endif
    {{ $slot }}
</div>

