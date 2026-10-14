@php
    use App\Support\WorldNavigation;

    $kingdoms = WorldNavigation::rootKingdoms();
    $mapKeys = config('world-navigation.map_keys', []);
@endphp

<div class="vs-world-nav vs-kingdom-accordions" data-exclusive-accordions>
    <h2 class="vs-image-map-sidebar__title">{{ $sidebarTitle ?? 'Bölgeler' }}</h2>
    <div class="vs-kingdom-accordions__list">
        @foreach ($kingdoms as $kingdomId => $kingdom)
            @php
                $isCurrent = $kingdomId === $currentLocationId;
                $mapConfigKey = $mapKeys[$kingdomId] ?? null;
                $mapConfig = $mapConfigKey ? config("world-maps.{$mapConfigKey}") : null;
                $kingdomDestinations = collect($mapConfig['hotspots'] ?? [])->map(fn ($hotspot) => [
                    'label' => $hotspot['label'],
                    'description' => $hotspot['description'] ?? null,
                    'href' => $hotspot['href'] ?? null,
                    'category' => $hotspot['category'] ?? null,
                    'icon' => $hotspot['icon'] ?? '📍',
                ])->all();
                $kingdomBuildings = $mapConfig['buildings'] ?? [];
            @endphp
            <details
                class="vs-kingdom-accordion{{ $isCurrent ? ' vs-kingdom-accordion--current' : '' }}"
                data-kingdom-accordion
                @if ($isCurrent) open @endif
            >
                <summary class="vs-kingdom-accordion__summary">
                    <span class="vs-kingdom-accordion__marker" aria-hidden="true"></span>
                    <span class="vs-kingdom-accordion__head">
                        <span class="vs-image-map-sidebar__label">{{ $kingdom['label'] }}</span>
                        @if (! empty($kingdom['description']))
                            <span class="vs-image-map-sidebar__desc">{{ $kingdom['description'] }}</span>
                        @endif
                    </span>
                    @if (! $isCurrent)
                        <a
                            href="{{ route($kingdom['route']) }}"
                            class="vs-kingdom-accordion__goto"
                            data-kingdom-route-link
                            title="Haritayı aç"
                        >Harita</a>
                    @endif
                </summary>
                <div class="vs-kingdom-accordion__body">
                    @if (! empty($kingdomDestinations) || ! empty($kingdomBuildings))
                        @include('web.world.partials.world-location-destinations-list', [
                            'destinations' => $kingdomDestinations,
                            'buildings' => $kingdomBuildings,
                        ])
                    @else
                        <p class="vs-kingdom-accordion__empty">Alt bölge tanımlı değil.</p>
                    @endif
                </div>
            </details>
        @endforeach
    </div>
</div>
