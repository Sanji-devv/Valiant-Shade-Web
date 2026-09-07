<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $config['title'] }} — {{ config('app.name', 'Valiant Shade') }}</title>
    @vite(['resources/css/valiant-shade/image-map.css', 'resources/js/valiant-shade/image-map.js'])
</head>
<body class="vs-image-map-page">
    <header class="vs-image-map-header">
        <div class="vs-image-map-header__title">
            <h1>{{ $config['title'] }}</h1>
            @if (! empty($config['subtitle']))
                <p>{{ $config['subtitle'] }}</p>
            @endif
        </div>
        <button type="button" class="vs-menu-btn" data-drawer-toggle aria-label="Menüyü aç">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="vs-image-map-nav vs-image-map-nav--desktop">
            @if (! empty($config['back_route']))
                <a class="vs-link" href="{{ route($config['back_route']) }}">← Geri</a>
            @endif
            <a class="vs-link" href="{{ route('web.home') }}">Ana Sayfa</a>
        </nav>
    </header>

    <main class="vs-image-map-layout">
        <div
            class="vs-image-map-stage"
            data-image-map
            data-map-key="{{ $mapKey }}"
            @if (config('app.debug') && request()->boolean('pick'))
                data-pick-coords="1"
            @endif
        >
            <div class="vs-image-map-frame" data-image-map-zoom>
                <div class="vs-image-map-zoom-controls" aria-label="Yakınlaştırma">
                    <button type="button" class="vs-zoom-btn" data-zoom-out aria-label="Uzaklaştır">−</button>
                    <span class="vs-zoom-label" data-zoom-label>100%</span>
                    <button type="button" class="vs-zoom-btn" data-zoom-in aria-label="Yakınlaştır">+</button>
                    <button type="button" class="vs-zoom-btn vs-zoom-btn--reset" data-zoom-reset aria-label="Sıfırla">↺</button>
                </div>
                <div class="vs-image-map-viewport" data-image-map-viewport>
                    <div class="vs-image-map-canvas" data-image-map-canvas>
                        @php
                            $previewImage = $config['image_preview'] ?? $config['image'];
                            $hdImage = $config['image_hd'] ?? null;
                        @endphp
                        <img
                            src="{{ asset($previewImage) }}"
                            alt="{{ $config['title'] }}"
                            class="vs-image-map-art"
                            draggable="false"
                            @if ($hdImage)
                                data-image-preview="{{ asset($previewImage) }}"
                                data-image-hd="{{ asset($hdImage) }}"
                            @endif
                        >
                        @foreach ($config['hotspots'] as $hotspot)
                            <a
                                href="{{ $hotspot['href'] }}"
                                class="vs-image-hotspot{{ ! empty($hotspot['secret']) ? ' vs-image-hotspot--secret' : '' }}"
                                style="left: {{ $hotspot['x'] }}%; top: {{ $hotspot['y'] }}%; width: {{ $hotspot['w'] }}%; height: {{ $hotspot['h'] }}%;"
                                title="{{ $hotspot['label'] }}"
                                aria-label="{{ $hotspot['label'] }}"
                                data-hotspot-id="{{ $hotspot['id'] }}"
                                data-location-link
                            ></a>
                        @endforeach
                        <div class="vs-coord-picker-output" data-coord-picker-output hidden></div>
                    </div>
                </div>
            </div>
            <p class="vs-image-map-hint vs-image-map-hint--desktop">Yakınlaştırmak için tekerlek veya +/− kullanın</p>
            @if (config('app.debug') && request()->boolean('pick'))
                <p class="vs-image-map-hint vs-image-map-hint--debug">Koordinat modu: görsele tıklayın</p>
            @endif
        </div>

        @if (! empty($config['location_id']))
            <aside class="vs-image-map-sidebar vs-image-map-sidebar--desktop" aria-label="Bölgeler">
                @include('web.world.partials.world-location-sidebar', [
                    'currentLocationId' => $config['location_id'],
                    'destinations' => collect($config['hotspots'] ?? [])->map(fn ($hotspot) => [
                        'label' => $hotspot['label'],
                        'description' => $hotspot['description'] ?? null,
                        'href' => $hotspot['href'],
                    ])->all(),
                ])
            </aside>
        @endif
    </main>

    @include('web.world.partials.image-map-drawer', ['config' => $config])
</body>
</html>
