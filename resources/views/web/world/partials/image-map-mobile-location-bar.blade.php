@php
    use App\Support\WorldMap;
    use App\Support\WorldNavigation;

    $locationId = $config['location_id'] ?? null;
    $mapRoutes = WorldNavigation::mapSwipeSequence($locationId);
    $poiItems = WorldMap::hotspotDestinations($config['hotspots'] ?? []);
@endphp

<nav class="vs-mobile-location-bar" aria-label="Bölge seçimi" data-mobile-location-bar>
    <button
        type="button"
        class="vs-mobile-location-bar__menu"
        data-drawer-toggle
        aria-label="Menü"
    >
        <span aria-hidden="true">☰</span>
    </button>

    <div class="vs-mobile-location-bar__track" data-mobile-location-track>
        @foreach ($mapRoutes as $entry)
            <a
                href="{{ $entry['url'] }}"
                class="vs-mobile-location-chip{{ $entry['id'] === $locationId ? ' vs-mobile-location-chip--active' : '' }}"
                data-location-link
            >{{ $entry['label'] }}</a>
        @endforeach

        @foreach ($poiItems as $poi)
            @if (! empty($poi['href']))
                <a
                    href="{{ $poi['href'] }}"
                    class="vs-mobile-location-chip vs-mobile-location-chip--poi"
                    data-location-link
                >{{ $poi['icon'] }} {{ $poi['label'] }}</a>
            @else
                <button
                    type="button"
                    class="vs-mobile-location-chip vs-mobile-location-chip--poi"
                    data-poi-link
                    data-poi-name="{{ $poi['label'] }}"
                    data-poi-desc="{{ $poi['description'] ?? '' }}"
                    data-poi-icon="{{ $poi['icon'] }}"
                    @include('web.world.partials.hotspot-poi-attributes', ['destination' => $poi])
                >{{ $poi['icon'] }} {{ $poi['label'] }}</button>
            @endif
        @endforeach
    </div>

    <button
        type="button"
        class="vs-mobile-location-bar__sheet-toggle"
        data-location-sheet-toggle
        aria-expanded="false"
        aria-controls="vs-location-sheet"
    >
        Bölgeler
    </button>
</nav>

<div class="vs-location-sheet" id="vs-location-sheet" data-location-sheet hidden>
    <div class="vs-location-sheet__backdrop" data-location-sheet-close tabindex="-1"></div>
    <div class="vs-location-sheet__panel" role="dialog" aria-label="Tüm bölgeler">
        <header class="vs-location-sheet__header">
            <h2 class="vs-location-sheet__title">Bölgeler</h2>
            <button type="button" class="vs-location-sheet__close" data-location-sheet-close aria-label="Kapat">×</button>
        </header>
        <div class="vs-location-sheet__body">
            @include('web.world.partials.world-location-sidebar', [
                'currentLocationId' => $locationId,
                'destinations' => $poiItems,
                'buildings' => $config['buildings'] ?? [],
            ])
        </div>
    </div>
</div>