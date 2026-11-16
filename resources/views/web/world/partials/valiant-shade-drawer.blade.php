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
            @if ($artConfig['enable_tile_map'] ?? false)
                <button
                    type="button"
                    class="vs-drawer__nav-link vs-drawer__nav-btn"
                    data-toggle-city-map
                    data-tile-label="{{ $tileLabel }}"
                    data-art-label="{{ $artLabel }}"
                >{{ $tileLabel }}</button>
            @endif
            @auth
                @if (auth()->user()->isAdmin())
                    <a class="vs-drawer__nav-link" href="{{ route('web.valiant-shade.edit') }}">Düzen Modu</a>
                @endif
            @endauth
            @if (! empty($artConfig['back_route']))
                <a class="vs-drawer__nav-link" href="{{ route($artConfig['back_route']) }}">← Geri</a>
            @endif
            @php use App\Support\PublicSiteUrls; @endphp
            <a class="vs-drawer__nav-link" href="{{ PublicSiteUrls::gazette() }}">Gazete</a>
            <a class="vs-drawer__nav-link" href="{{ PublicSiteUrls::main() }}">Ana Sayfa</a>
        </nav>

        <div class="vs-drawer__section">
            @include('web.world.partials.world-location-sidebar', [
                'currentLocationId' => 'valiant-shade',
                'buildings' => $artConfig['buildings'] ?? [],
            ])
        </div>
    </aside>
</div>
