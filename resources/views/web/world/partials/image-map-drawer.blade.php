<div class="vs-drawer" data-drawer hidden>
    <div class="vs-drawer__backdrop" data-drawer-close tabindex="-1"></div>
    <aside class="vs-drawer__panel" aria-label="Menü">
        <div class="vs-drawer__header">
            <h2>{{ $config['title'] }}</h2>
            <button type="button" class="vs-drawer__close" data-drawer-close aria-label="Kapat">×</button>
        </div>

        @if (! empty($config['subtitle']))
            <p class="vs-drawer__subtitle">{{ $config['subtitle'] }}</p>
        @endif

        <nav class="vs-drawer__nav">
            @php use App\Support\PublicSiteUrls; @endphp
            <a class="vs-drawer__nav-link" href="{{ PublicSiteUrls::gazette() }}">Gazete</a>
            <a class="vs-drawer__nav-link" href="{{ PublicSiteUrls::main() }}">Ana Sayfa</a>
            @include('web.world.partials.kingdom-switcher', ['currentLocationId' => $config['location_id'] ?? null])
        </nav>

        @if (! empty($config['location_id']))
            <div class="vs-drawer__section">
                @include('web.world.partials.world-location-sidebar', [
                    'currentLocationId' => $config['location_id'],
                    'destinations' => \App\Support\WorldMap::hotspotDestinations($config['hotspots'] ?? []),
                    'buildings' => $config['buildings'] ?? [],
                ])
            </div>
        @endif
    </aside>
</div>
