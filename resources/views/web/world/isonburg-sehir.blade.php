<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Isonburg — {{ config('app.name', 'Valiant Shade') }}</title>
    @vite([
        'resources/css/valiant-shade/image-map.css',
        'resources/css/valiant-shade/map.css',
        'resources/js/valiant-shade/image-map.js',
        'resources/js/valiant-shade/map.js',
    ], 'static/valiant-shade/build')
</head>
<body class="vs-image-map-page vs-image-map-page--dual">
    @php
        $artConfig = config('world-maps.isonburg-city-art');
        $tileLabel = $artConfig['tile_layer_label'] ?? 'İnteraktif harita';
        $artLabel = $artConfig['art_layer_label'] ?? 'Görsel harita';
    @endphp

    @include('web.world.partials.world-map-header', [
        'title' => $artConfig['title'],
        'subtitle' => $artConfig['subtitle'] ?? null,
        'showTileToggle' => true,
        'tileLabel' => $tileLabel,
        'artLabel' => $artLabel,
        'editRoute' => auth()->check() && auth()->user()->isAdmin() ? 'web.isonburg.city.edit' : null,
        'backRoute' => $artConfig['back_route'] ?? null,
    ])

    <main class="vs-image-map-layout">
        <div class="vs-image-map-stage vs-image-map-stage--dual">
            <div class="vs-dual-map-layer vs-dual-map-layer--tile" data-city-map-layer>
                <div class="vs-map-shell vs-map-shell--embedded">
                    <div
                        id="valiant-shade-viewport"
                        class="vs-viewport--buildings"
                        data-can-edit="0"
                        data-map-slug="{{ $mapSlug }}"
                    >
                        <div id="valiant-shade-world" class="vs-world"></div>
                    </div>
                </div>
            </div>

            <div class="vs-dual-map-layer vs-dual-map-layer--art" data-city-art-layer>
                <div class="vs-image-map-frame" data-image-map-zoom data-image-map-zoom-mode="transform">
                    <div class="vs-image-map-zoom-controls" aria-label="Yakınlaştırma">
                        <button type="button" class="vs-zoom-btn" data-zoom-out aria-label="Uzaklaştır">−</button>
                        <span class="vs-zoom-label" data-zoom-label>100%</span>
                        <button type="button" class="vs-zoom-btn" data-zoom-in aria-label="Yakınlaştır">+</button>
                        <button type="button" class="vs-zoom-btn vs-zoom-btn--reset" data-zoom-reset aria-label="Sıfırla">↺</button>
                    </div>
                    <div class="vs-image-map-viewport" data-image-map-viewport>
                        <div class="vs-image-map-canvas" data-image-map-canvas>
                            <img
                                src="{{ asset($artConfig['image']) }}"
                                alt="Isonburg şehir haritası"
                                class="vs-image-map-art"
                                draggable="false"
                            >
                            @foreach ($artConfig['hotspots'] as $hotspot)
                                <a
                                    href="{{ $hotspot['href'] }}"
                                    class="vs-image-hotspot{{ ! empty($hotspot['secret']) ? ' vs-image-hotspot--secret' : '' }}"
                                    style="left: {{ $hotspot['x'] }}%; top: {{ $hotspot['y'] }}%; width: {{ $hotspot['w'] }}%; height: {{ $hotspot['h'] }}%;"
                                    title="{{ $hotspot['label'] }}"
                                    aria-label="{{ $hotspot['label'] }}"
                                    data-location-link
                                ></a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (! empty($artConfig['location_id']))
            <aside class="vs-image-map-sidebar vs-image-map-sidebar--desktop" aria-label="Bölgeler">
                @include('web.world.partials.world-location-sidebar', [
                    'currentLocationId' => $artConfig['location_id'],
                    'destinations' => collect($artConfig['hotspots'] ?? [])->map(fn ($hotspot) => [
                        'label' => $hotspot['label'],
                        'description' => $hotspot['description'] ?? null,
                        'href' => $hotspot['href'],
                    ])->all(),
                ])
            </aside>
        @endif
    </main>

    @include('web.world.partials.city-map-drawer', ['artConfig' => $artConfig])
</body>
</html>
