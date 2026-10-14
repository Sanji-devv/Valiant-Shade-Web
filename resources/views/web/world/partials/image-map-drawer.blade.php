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
            <a class="vs-drawer__nav-link" href="{{ route('web.home') }}">Ana Sayfa</a>
            @include('web.world.partials.kingdom-switcher', ['currentLocationId' => $config['location_id'] ?? null])
        </nav>

        @if (! empty($config['location_id']))
            @php
                $locationMeta = config('world-navigation.locations.'.$config['location_id'], []);
                $useKingdomAccordions = ($locationMeta['parent'] ?? null) === null;
            @endphp
            <div class="vs-drawer__section">
                @include('web.world.partials.world-location-sidebar', [
                    'currentLocationId' => $config['location_id'],
                    'useKingdomAccordions' => $useKingdomAccordions,
                    'destinations' => collect($config['hotspots'] ?? [])->map(fn ($hotspot) => [
                        'label' => $hotspot['label'],
                        'description' => $hotspot['description'] ?? null,
                        'href' => $hotspot['href'] ?? null,
                        'category' => $hotspot['category'] ?? null,
                        'icon' => $hotspot['icon'] ?? '📍',
                    ])->all(),
                ])
            </div>
        @endif
    </aside>
</div>
