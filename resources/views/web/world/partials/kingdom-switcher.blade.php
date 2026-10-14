@php
    $kingdoms = collect(config('world-navigation.locations', []))
        ->filter(fn (array $location) => ($location['parent'] ?? null) === null)
        ->all();
    $currentId = $currentLocationId ?? null;
@endphp
@if (count($kingdoms) > 1)
    <nav class="vs-kingdom-switcher" aria-label="Krallıklar">
        @foreach ($kingdoms as $id => $kingdom)
            @if ($id !== $currentId)
                <a class="vs-link vs-link--kingdom" href="{{ route($kingdom['route']) }}">{{ $kingdom['label'] }}</a>
            @endif
        @endforeach
    </nav>
@endif
