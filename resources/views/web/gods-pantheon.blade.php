@php
    use App\Support\PublicSiteUrls;
    $gb = $godsBrand ?? config('gods_public', []);
    $godsTitle = $gb['title'] ?? __('Tanrılar');
    $godsMotto = $gb['motto'] ?? '';
    $godsTagline = $gb['tagline'] ?? '';
    $godsCrest = asset($gb['crest'] ?? 'static/valiant-shade/assets/img/news/guild_celestial_sanctuary.jpg');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $godsTitle }} · {{ config('seo.site_name', 'Valiant Shade') }}</title>
    @include('web.partials.head-favicon')
    @include('web.partials.head-public-seo', [
        'title' => $godsTitle,
        'description' => $godsTagline ?: __('A parchment guide to gods, domains, and faith across Valiant Shade.'),
        'canonical' => $canonicalUrl ?? PublicSiteUrls::godsPantheon('/'),
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=IM+Fell+English:ital@0;1&family=Newsreader:ital,opsz,wght@0,12..72,400;0,12..72,600;0,12..72,700;1,12..72,400&display=swap" rel="stylesheet">
    @vite(['resources/css/valiant-shade/npc-roster.css', 'resources/js/valiant-shade/gods-pantheon.js'], 'static/valiant-shade/build')
</head>
<body
    class="vs-newspaper-page vs-layout-gazette vs-npc-roster-page vs-gods-pantheon-page"
    data-api-base="/api/public/gods"
    data-msg-empty="{{ __('No gods matched your search.') }}"
    data-msg-loading="{{ __('Loading pantheon…') }}"
    data-label-all-alignments="{{ __('All alignments') }}"
    data-label-all-domains="{{ __('All domains') }}"
>
<header class="vs-newspaper-header">
    <div class="vs-newspaper-header__mast">
        <div class="vs-newspaper-header__brand vs-npc-roster-brand">
            <a href="{{ $godsUrl }}" class="vs-npc-roster-brand__crest" aria-hidden="true" tabindex="-1">
                <img src="{{ $godsCrest }}" alt="" width="56" height="56">
            </a>
            <div class="vs-npc-roster-brand__text">
                @if($godsMotto !== '')
                    <p class="vs-npc-roster-brand__motto">{{ $godsMotto }}</p>
                @endif
                <h1 class="vs-npc-roster-brand__title">{{ $godsTitle }}</h1>
                @if($godsTagline !== '')
                    <p class="vs-newspaper-header__tagline">{{ $godsTagline }}</p>
                @endif
            </div>
        </div>
    </div>
    <nav class="vs-newspaper-nav" aria-label="{{ $godsTitle }}">
        @include('web.partials.public-portal-nav', ['activePortal' => 'gods'])
        <p class="vs-newspaper-nav__title">{{ $godsTitle }}</p>
        <div class="vs-newspaper-nav__cluster vs-newspaper-nav__cluster--actions">
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--primary" id="openGodSubmitModal">
                {{ __('Submit god') }}
            </button>
        </div>
    </nav>
</header>

<main class="vs-newspaper-main vs-npc-roster-main">
    <p id="godStatusBanner" class="vs-npc-status-banner" hidden role="status"></p>

    <section class="vs-toolbar vs-npc-toolbar" aria-label="{{ __('Search gods') }}">
        <div class="vs-toolbar__bar vs-npc-toolbar__bar">
            <input type="search" class="vs-search vs-npc-toolbar__search" id="godSearchInput"
                   placeholder="{{ __('Search names, epithets, domains…') }}" enterkeyhint="search"
                   aria-label="{{ __('Search gods') }}">
            <button type="button" class="vs-toolbar__btn vs-toolbar__btn--filters" id="godToggleFiltersBtn" aria-expanded="false" aria-controls="godFilterPanel">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                <span>{{ __('Filters') }}</span>
            </button>
            <span class="vs-toolbar__btn vs-npc-toolbar__count" aria-live="polite">
                <span id="godTotal">0</span> {{ __('records') }}
            </span>
        </div>
        <div id="godFilterPanel" class="vs-filter-panel vs-npc-filter-panel vs-npc-filter-panel--gazette" hidden>
            <div class="vs-npc-filter-grid">
                <label class="vs-form-field">
                    <span class="vs-form-field__label">{{ __('Category') }}</span>
                    <select id="godCategoryFilter" class="vs-npc-gazette-input">
                        <option value="">{{ __('All categories') }}</option>
                        <option value="major">{{ __('Major gods') }}</option>
                        <option value="minor">{{ __('Minor gods') }}</option>
                    </select>
                </label>
                <label class="vs-form-field">
                    <span class="vs-form-field__label">{{ __('Alignment') }}</span>
                    <select id="godAlignmentFilter" class="vs-npc-gazette-input"></select>
                </label>
                <label class="vs-form-field">
                    <span class="vs-form-field__label">{{ __('Domain') }}</span>
                    <select id="godDomainFilter" class="vs-npc-gazette-input"></select>
                </label>
            </div>
            <div class="vs-npc-filter-footer">
                <button type="button" class="vs-npc-filter-clear" id="godClearFiltersBtn">{{ __('Clear Filters') }}</button>
            </div>
        </div>
    </section>

    <ul class="vs-npc-grid" id="godGrid" aria-live="polite"></ul>

    <nav class="vs-pagination vs-npc-pagination" aria-label="{{ __('Pagination') }}">
        <button type="button" class="vs-god-page-nav__button" id="godPagePrev" aria-label="{{ __('Previous Page') }}" title="{{ __('Previous Page') }}">←</button>
        <span id="godPageIndicator" aria-live="polite">1 / 1</span>
        <button type="button" class="vs-god-page-nav__button" id="godPageNext" aria-label="{{ __('Next Page') }}" title="{{ __('Next Page') }}">→</button>
    </nav>
</main>

@include('web.partials.gods-pantheon-modals')

</body>
</html>
