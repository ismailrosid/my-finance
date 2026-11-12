@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="page-header">
    {{-- Breadcrumb --}}
    @if (count($breadcrumbs))
        <nav class="breadcrumb">
            @foreach ($breadcrumbs as $crumb)
                @if (!$loop->last)
                    <a href="{{ $crumb['url'] }}" class="text-gray-500 hover:text-gray-700 transition-colors">{{ $crumb['label'] }}</a>
                    <svg class="breadcrumb-separator w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                @else
                    <span class="text-gray-900 font-medium">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    {{-- Title row --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="page-title">{{ $title }}</h1>
            @if ($subtitle)
                <p class="page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        @if (isset($actions))
            <div class="flex items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

