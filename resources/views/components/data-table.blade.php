@props([
    'headers' => [],
    'striped' => false,
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-gray-200']) }}>
    <table class="data-table">
        @if (count($headers))
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th class="{{ $header['class'] ?? '' }}">{{ $header['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>

