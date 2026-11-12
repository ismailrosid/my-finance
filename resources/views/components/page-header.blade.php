@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="mb-6 flex flex-col gap-3.5 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">
            {{ $title }}
        </h2>
        @if ($subtitle)
            <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        @if (count($breadcrumbs))
            <nav class="breadcrumb mb-0">
                <ol class="flex items-center gap-1.5 text-sm">
                    @foreach ($breadcrumbs as $crumb)
                        @if (!$loop->last)
                            <li>
                                <a href="{{ $crumb['url'] }}" class="text-gray-500 hover:text-emerald-600 transition-colors">
                                    {{ $crumb['label'] }}
                                </a>
                            </li>
                            <li class="text-gray-300">/</li>
                        @else
                            <li class="font-medium text-emerald-600">
                                {{ $crumb['label'] }}
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif

        @if (isset($actions))
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

