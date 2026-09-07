@php
    $tileLabel = $artConfig['tile_layer_label'] ?? 'İnteraktif harita';
    $artLabel = $artConfig['art_layer_label'] ?? 'Görsel harita';
@endphp

<div class="vs-drawer" data-drawer hidden>
    <div class="vs-drawer__backdrop" data-drawer-close tabindex="-1"></div>
    <aside class="vs-drawer__panel" aria-label="Menü">
        <div class="vs-drawer__header">
            <h2>{{ $artConfig['title'] }}</h2>
            <button type="button" class="vs-drawer__close" data-drawer-close aria-label="Kapat">×</button>
        </div>

        <nav class="vs-drawer__nav">
            <button
                type="button"
                class="vs-drawer__nav-link vs-drawer__nav-btn"
                data-toggle-city-map
                data-tile-label="{{ $tileLabel }}"
                data-art-label="{{ $artLabel }}"
            >{{ $tileLabel }}</button>
            @auth
                @if (auth()->user()->is_admin)
                    <a class="vs-drawer__nav-link" href="{{ route('web.isonburg.city.edit') }}">Düzen Modu</a>
                @endif
            @endauth
            @if (! empty($artConfig['back_route']))
                <a class="vs-drawer__nav-link" href="{{ route($artConfig['back_route']) }}">← Geri</a>
            @endif
            <a class="vs-drawer__nav-link" href="{{ route('web.home') }}">Ana Sayfa</a>
        </nav>

        @if (! empty($artConfig['location_id']))
            <div class="vs-drawer__section">
                @include('web.world.partials.world-location-sidebar', [
                    'currentLocationId' => $artConfig['location_id'],
                    'destinations' => collect($artConfig['hotspots'] ?? [])->map(fn ($hotspot) => [
                        'label' => $hotspot['label'],
                        'description' => $hotspot['description'] ?? null,
                        'href' => $hotspot['href'],
                    ])->all(),
                ])
            </div>
        @endif
    </aside>
</div>
