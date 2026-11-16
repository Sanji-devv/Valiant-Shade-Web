@php
    use App\Support\PublicSiteUrls;
    use App\Support\WorldMap;
    use App\Support\WorldNavigation;

    $swipeNav = ! empty($config['location_id'])
        ? WorldNavigation::mapSwipeSequence($config['location_id'])
        : [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $config['title'] }} — {{ config('app.name', 'Valiant Shade') }}</title>
    @include('web.world.partials.image-map-asset-hints', ['config' => $config])
    @vite(['resources/css/valiant-shade/image-map.css', 'resources/js/valiant-shade/image-map.js'], 'static/valiant-shade/build')
</head>
<body class="vs-image-map-page">
    <header class="vs-image-map-header">
        <div class="vs-image-map-header__title">
            <h1>{{ $config['title'] }}</h1>
            @if (! empty($config['subtitle']))
                <p>{{ $config['subtitle'] }}</p>
            @endif
        </div>
        <button type="button" class="vs-menu-btn vs-menu-btn--header" data-drawer-toggle aria-label="Menüyü aç">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="vs-image-map-nav vs-image-map-nav--desktop">
            @if (! empty($config['back_route']))
                <a class="vs-link" href="{{ route($config['back_route']) }}">← Geri</a>
            @endif
            @include('web.world.partials.kingdom-switcher', ['currentLocationId' => $config['location_id'] ?? null])
            <a class="vs-link" href="{{ PublicSiteUrls::gazette() }}">Gazete</a>
            <a class="vs-link" href="{{ PublicSiteUrls::main() }}">Ana Sayfa</a>
        </nav>
    </header>

    <main class="vs-image-map-layout">
        <div
            class="vs-image-map-stage"
            data-image-map
            data-map-key="{{ $mapKey }}"
            @if (! empty($swipeNav))
                data-swipe-nav='@json($swipeNav)'
                data-location-id="{{ $config['location_id'] }}"
            @endif
            @if (config('app.debug') && request()->boolean('pick'))
                data-pick-coords="1"
            @endif
        >
            <div class="vs-image-map-frame" data-image-map-zoom data-image-map-fit="panel">
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
                            $displayImage = $config['image_display'] ?? null;
                            $hdImage = $config['image_hd'] ?? null;
                        @endphp
                        <img
                            src="{{ asset($previewImage) }}"
                            alt="{{ $config['title'] }}"
                            class="vs-image-map-art"
                            draggable="false"
                            decoding="async"
                            @if ($displayImage || $hdImage)
                                data-image-preview="{{ asset($previewImage) }}"
                                @if ($displayImage)
                                    data-image-display="{{ asset($displayImage) }}"
                                @endif
                                @if ($hdImage)
                                    data-image-hd="{{ asset($hdImage) }}"
                                @endif
                            @endif
                        >
                        @include('web.world.partials.image-map-hotspots', ['hotspots' => $config['hotspots'] ?? []])
                        <div class="vs-coord-picker-output" data-coord-picker-output hidden></div>
                    </div>
                </div>
            </div>
            <p class="vs-image-map-hint vs-image-map-hint--desktop">Yakınlaştırmak için tekerlek veya +/−; tam çözünürlük için görsele tıklayın</p>
            <p class="vs-image-map-hint vs-image-map-hint--mobile">Haritayı kaydırarak krallıklar arası geçin; tam boyut için tek dokunun</p>
            <button type="button" class="vs-image-map-open-hd" data-map-lightbox-open aria-label="Tam boyut harita">
                Tam boyut
            </button>
            @if (config('app.debug') && request()->boolean('pick'))
                <p class="vs-image-map-hint vs-image-map-hint--debug">Koordinat modu: görsele tıklayın</p>
            @endif
        </div>

        @if (! empty($config['location_id']))
            <aside class="vs-image-map-sidebar vs-image-map-sidebar--desktop" aria-label="Bölgeler">
                @include('web.world.partials.world-location-sidebar', [
                    'currentLocationId' => $config['location_id'],
                    'destinations' => WorldMap::hotspotDestinations($config['hotspots'] ?? []),
                    'buildings' => $config['buildings'] ?? [],
                ])
            </aside>
        @endif
    </main>

    <aside class="vs-building-info" data-building-info hidden>
        <div class="vs-building-info__header">
            <span class="vs-building-info__icon" data-building-info-icon></span>
            <h2 class="vs-building-info__title" data-building-info-title></h2>
            <button type="button" class="vs-building-info__close" data-building-info-close aria-label="Kapat">×</button>
        </div>
        <p class="vs-building-info__desc" data-building-info-desc></p>
    </aside>

    @include('web.world.partials.image-map-drawer', ['config' => $config])

    @if (! empty($config['location_id']))
        @include('web.world.partials.image-map-mobile-location-bar', ['config' => $config])
    @endif

    @php
        $hdImage = $config['image_hd'] ?? ($config['image_preview'] ?? $config['image']);
        $previewImage = $config['image_preview'] ?? $config['image'];
    @endphp
    <div class="vs-map-lightbox" data-map-lightbox hidden>
        <div class="vs-map-lightbox__backdrop" data-map-lightbox-close tabindex="-1"></div>
        <div class="vs-map-lightbox__dialog" role="dialog" aria-modal="true" aria-label="{{ $config['title'] }} tam boyut">
            <header class="vs-map-lightbox__toolbar">
                <span class="vs-map-lightbox__title">{{ $config['title'] }}</span>
                <button type="button" class="vs-map-lightbox__close" data-map-lightbox-close aria-label="Kapat">×</button>
            </header>
            <div class="vs-image-map-frame vs-map-lightbox__frame" data-image-map-zoom data-image-map-zoom-mode="width" data-image-map-fit="lightbox" data-max-scale="8">
                <div class="vs-image-map-zoom-controls" aria-label="Yakınlaştırma">
                    <button type="button" class="vs-zoom-btn" data-zoom-out aria-label="Uzaklaştır">−</button>
                    <span class="vs-zoom-label" data-zoom-label>100%</span>
                    <button type="button" class="vs-zoom-btn" data-zoom-in aria-label="Yakınlaştır">+</button>
                    <button type="button" class="vs-zoom-btn vs-zoom-btn--reset" data-zoom-reset aria-label="Sıfırla">↺</button>
                </div>
                <div class="vs-image-map-viewport" data-image-map-viewport>
                    <div class="vs-image-map-canvas" data-image-map-canvas>
                        <img
                            src="{{ asset($hdImage) }}"
                            alt="{{ $config['title'] }}"
                            class="vs-image-map-art"
                            draggable="false"
                            data-image-resolution="hd"
                            data-image-hd="{{ asset($hdImage) }}"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
