@php
    use App\Support\PublicSiteUrls;
    use App\ValiantShade\Support\NpcStatusCatalog;
    $nb = $npcBrand ?? config('npc', []);
    $npcTitle = $nb['title'] ?? __('NPC Roster');
    $npcMotto = $nb['motto'] ?? '';
    $npcTagline = $nb['tagline'] ?? '';
    $npcCrest = asset($nb['crest'] ?? 'static/valiant-shade/assets/img/news/guild_hall_council.jpg');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $npcTitle }} · {{ config('seo.site_name', 'Valiant Shade') }}</title>
    @include('web.partials.head-favicon')
    @include('web.partials.head-public-seo', [
        'title' => $npcTitle,
        'description' => $npcTagline ?: __('A parchment roster of notable people across Valiant Shade.'),
        'canonical' => $canonicalUrl ?? PublicSiteUrls::npcRoster('/'),
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=IM+Fell+English:ital@0;1&family=Newsreader:ital,opsz,wght@0,12..72,400;0,12..72,600;0,12..72,700;1,12..72,400&display=swap" rel="stylesheet">
    @vite(['resources/css/valiant-shade/npc-roster.css', 'resources/js/valiant-shade/npc-roster.js'], 'static/valiant-shade/build')
</head>
<body
    class="vs-newspaper-page vs-layout-gazette vs-npc-roster-page"
    data-api-base="/api/public/npcs"
    data-msg-empty="{{ __('No roster entries found.') }}"
    data-msg-loading="{{ __('Loading roster…') }}"
>
<header class="vs-newspaper-header">
    <div class="vs-newspaper-header__mast">
        <div class="vs-newspaper-header__brand vs-npc-roster-brand">
            <a href="{{ $npcUrl }}" class="vs-npc-roster-brand__crest" aria-hidden="true" tabindex="-1">
                <img src="{{ $npcCrest }}" alt="" width="56" height="56">
            </a>
            <div class="vs-npc-roster-brand__text">
                @if($npcMotto !== '')
                    <p class="vs-npc-roster-brand__motto">{{ $npcMotto }}</p>
                @endif
                <h1 class="vs-npc-roster-brand__title">{{ $npcTitle }}</h1>
                @if($npcTagline !== '')
                    <p class="vs-newspaper-header__tagline">{{ $npcTagline }}</p>
                @endif
            </div>
        </div>
    </div>
    <nav class="vs-newspaper-nav" aria-label="{{ __('NPC Roster') }}">
        @include('web.partials.public-portal-nav', ['activePortal' => 'npc'])
        <p class="vs-newspaper-nav__title">{{ $npcTitle }}</p>
        <div class="vs-newspaper-nav__cluster vs-newspaper-nav__cluster--actions">
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--primary" id="openNpcSubmitModal">
                {{ __('Submit NPC') }}
            </button>
        </div>
    </nav>
</header>

<main class="vs-newspaper-main vs-npc-roster-main">
    <p id="npcStatusBanner" class="vs-npc-status-banner" hidden role="status"></p>

    <section class="vs-toolbar vs-npc-toolbar" aria-label="{{ __('Search NPCs') }}">
        <div class="vs-toolbar__bar vs-npc-toolbar__bar">
            <input type="search" class="vs-search vs-npc-toolbar__search" id="npcSearchInput"
                   placeholder="{{ __('Search names, titles, professions, locations…') }}" enterkeyhint="search"
                   aria-label="{{ __('Search NPCs') }}">
            <button type="button" class="vs-toolbar__btn vs-toolbar__btn--filters" id="npcToggleFiltersBtn" aria-expanded="false" aria-controls="npcFilterPanel">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                <span>{{ __('Filters') }}</span>
            </button>
            <button
                type="button"
                class="vs-toolbar__btn vs-toolbar__btn--view vs-layout-toggle vs-layout-toggle--icon vs-npc-view-toggle"
                id="npcToggleView"
                aria-pressed="true"
                data-label-list="{{ __('List view') }}"
                data-label-grid="{{ __('Grid view') }}"
                title="{{ __('Grid view') }}"
                aria-label="{{ __('Grid view') }}"
            >
                <svg id="npcToggleViewIcon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/>
                </svg>
            </button>
            <span class="vs-toolbar__btn vs-npc-toolbar__count" aria-live="polite">
                <span id="npcTotal">0</span> {{ __('records') }}
            </span>
        </div>
        <div id="npcFilterPanel" class="vs-filter-panel vs-npc-filter-panel vs-npc-filter-panel--gazette" hidden>
            <div class="vs-npc-filter-grid">
                <label class="vs-form-field vs-npc-filter-status">
                    <span class="vs-form-field__label">{{ __('Life status') }}</span>
                    <select id="npcStatusFilter" class="vs-npc-gazette-input">
                        <option value="">{{ __('All life statuses') }}</option>
                        @foreach(NpcStatusCatalog::options() as $value => $meta)
                            <option value="{{ $value }}">{{ __('npc_status.' . $meta['key']) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="vs-form-field">
                    <span class="vs-form-field__label">{{ __('Race') }}</span>
                    <select id="npcRaceFilter" class="vs-npc-select2" data-placeholder="{{ __('All races') }}">
                        <option value="">{{ __('All races') }}</option>
                    </select>
                </label>
                <label class="vs-form-field">
                    <span class="vs-form-field__label">{{ __('Profession / Role') }}</span>
                    <select id="npcProfessionFilter" class="vs-npc-select2 vs-npc-select2--tags" data-placeholder="{{ __('Filter by profession…') }}"></select>
                </label>
                <label class="vs-form-field">
                    <span class="vs-form-field__label">{{ __('Location') }}</span>
                    <select id="npcLocationFilter" class="vs-npc-select2 vs-npc-select2--tags" data-placeholder="{{ __('Filter by location…') }}"></select>
                </label>
            </div>
            <div class="vs-npc-filter-footer">
                <button type="button" class="vs-npc-filter-clear" id="npcClearFiltersBtn">{{ __('Clear Filters') }}</button>
            </div>
        </div>
    </section>

    <ul class="vs-npc-grid" id="npcGrid" aria-live="polite"></ul>

    <nav class="vs-pagination vs-npc-pagination" aria-label="{{ __('Pagination') }}">
        <button type="button" class="vs-toolbar__btn" id="npcPagePrev">{{ __('Previous') }}</button>
        <span id="npcPageIndicator">1 / 1</span>
        <button type="button" class="vs-toolbar__btn" id="npcPageNext">{{ __('Next') }}</button>
    </nav>
</main>

@include('web.partials.npc-roster-modals', ['pfsrdRaceNames' => $pfsrdRaceNames])

</body>
</html>