@php
    use App\Support\PublicSiteUrls;

    $tileLabel = $tileLabel ?? 'İnteraktif harita';
    $artLabel = $artLabel ?? 'Görsel harita';
@endphp

<header class="vs-image-map-header">
    <div class="vs-image-map-header__title">
        <h1>{{ $title }}</h1>
        @if (! empty($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    <button type="button" class="vs-menu-btn vs-menu-btn--header" data-drawer-toggle aria-label="Menüyü aç">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <nav class="vs-image-map-nav vs-image-map-nav--desktop">
        @if (! empty($showTileToggle))
            <button
                type="button"
                class="vs-btn-ghost"
                data-toggle-city-map
                data-tile-label="{{ $tileLabel }}"
                data-art-label="{{ $artLabel }}"
            >{{ $tileLabel }}</button>
        @endif
        @if (! empty($editRoute))
            <a class="vs-link" href="{{ route($editRoute) }}">Düzen Modu</a>
        @endif
        @if (! empty($backRoute))
            <a class="vs-link" href="{{ route($backRoute) }}">← Geri</a>
        @endif
        <a class="vs-link" href="{{ PublicSiteUrls::gazette() }}">Gazete</a>
        <a class="vs-link" href="{{ PublicSiteUrls::main() }}">Ana Sayfa</a>
    </nav>
</header>
