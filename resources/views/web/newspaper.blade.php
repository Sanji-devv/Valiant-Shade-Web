@php
    $mainSiteUrl = rtrim((string) config('app.url'), '/');
    $gb = $gazetteBrand ?? [];
    $gazetteTitle = $gb['title'] ?? 'Lağımdan Sesler';
    $gazetteMotto = $gb['motto'] ?? '';
    $gazetteTagline = $gb['tagline'] ?? '';
    $gazetteCrest = $gb['crest'] ?? asset('static/valiant-shade/assets/img/gazette/lagimdan-sesler-crest.jpg');
    $gazetteIssue = $gb['issue'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $gazetteTitle }} — {{ config('app.name', 'Valiant Shade') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=IM+Fell+English:ital@0;1&family=Newsreader:ital,opsz,wght@0,12..72,400;0,12..72,600;0,12..72,700;1,12..72,400&display=swap" rel="stylesheet">
    @vite(['resources/css/valiant-shade/newspaper.css', 'resources/js/valiant-shade/newspaper.js'], 'static/valiant-shade/build')
</head>
<body
    class="vs-newspaper-page"
    data-gazette-logo="{{ $gazetteCrest }}"
    data-gazette-name="{{ $gazetteTitle }}"
    data-gazette-motto="{{ $gazetteMotto }}"
    data-gazette-press="{{ $gb['press_name'] ?? 'Lağımdan Sesler Basın Bürosu' }}"
    data-gazette-issue-date="{{ $gazetteIssue['date_line'] ?? 'Leos ayının 1. günü, Vonyas — 631' }}"
    data-gazette-issue-number="{{ $gazetteIssue['number'] ?? 1 }}"
    data-gazette-issue-label="{{ $gazetteIssue['label'] ?? 'İlk sayı' }}"
    data-gazette-issue-month="{{ $gazetteIssue['month'] ?? 'Leos' }}"
    data-gazette-issue-day="{{ $gazetteIssue['day'] ?? 1 }}"
    data-gazette-issue-year="{{ $gazetteIssue['year'] ?? 631 }}"
    data-gazette-calendar='@json($gb["calendar"] ?? [])'
    data-gazette-submission-slots='@json(config("gazette.submission_slots", []))'
>
    <header class="vs-newspaper-header">
        <div class="vs-newspaper-header__brand">
            <h1>{{ $gazetteTitle }}</h1>
            <p class="vs-newspaper-header__tagline">{{ $gazetteTagline }}</p>
        </div>
        <nav class="vs-newspaper-nav" aria-label="Gazete menüsü">
            <a href="{{ $mainSiteUrl }}" class="vs-newspaper-nav__item" aria-label="Ana Sayfa">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Ana Sayfa</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Ana Sayfa</span>
            </a>
            <span class="vs-newspaper-nav__item vs-newspaper-nav__soon" tabindex="0" aria-disabled="true" aria-label="Lonca Arazisi — {{ __('Coming soon') }}">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 9v12h6v-7h4v7h6V9l-8-6z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Lonca Arazisi</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Lonca</span>
                <span class="vs-newspaper-nav__tooltip" role="tooltip">{{ __('Coming soon') }}</span>
            </span>
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--print" id="printGazetteBtn" hidden aria-label="PDF İndir">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">PDF İndir</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">PDF</span>
            </button>
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--primary" id="openRumorModal" aria-label="Söylenti Gönder">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Söylenti Gönder</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Söylenti</span>
            </button>
        </nav>
    </header>

    <main class="vs-newspaper-main">
        <section class="vs-news-slider" aria-label="Manşet haberleri">
            <div class="vs-news-slider__viewport">
                <button type="button" class="vs-news-slider__arrow vs-news-slider__arrow--prev" id="newsSliderPrev" aria-label="Önceki manşet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div class="vs-news-slider__track" id="newsSliderTrack"></div>
                <button type="button" class="vs-news-slider__arrow vs-news-slider__arrow--next" id="newsSliderNext" aria-label="Sonraki manşet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
            <div class="vs-news-slider__controls">
                <div class="vs-news-slider__dots" id="newsSliderDots"></div>
            </div>
        </section>

        <section class="vs-toolbar" aria-label="Haber filtreleri">
            <div class="vs-toolbar__bar">
                <div class="vs-toolbar__actions">
                    <button type="button" class="vs-toolbar__btn" id="toggleFiltersBtn" aria-expanded="false" aria-controls="newsFilterPanel">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        <span id="toggleFiltersLabel">Filtreler</span>
                    </button>
                    <button type="button" class="vs-toolbar__btn" id="toggleViewModeBtn" aria-pressed="false" title="Liste görünümü">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        <span id="toggleViewModeLabel">Liste</span>
                    </button>
                </div>
                <input type="search" class="vs-search vs-toolbar__search" id="newsSearchInput" placeholder="Haber, söylenti veya kahraman ara…" aria-label="Haber ara">
                <div class="vs-toolbar__gazette" id="gazetteToolbarExtras">
                    <label class="vs-gazette-archive-wrap" id="gazetteArchiveWrap" hidden>
                        <span class="vs-gazette-archive-wrap__label">Sayı</span>
                        <select class="vs-gazette-archive" id="gazetteArchiveSelect" aria-label="Gazete sayısı arşivi"></select>
                    </label>
                    <button type="button" class="vs-layout-toggle" id="toggleGazetteLayoutBtn" aria-pressed="false" title="Basılı gazete sayfası — sayfa sayfa gezin">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 4v16M16 4v16M4 8h16M4 16h16"/></svg>
                        <span id="toggleGazetteLayoutLabel">Gazete görünümü</span>
                    </button>
                </div>
            </div>
            <div class="vs-filter-panel" id="newsFilterPanel" hidden>
                <div class="vs-filter-group" id="newsFilterGroup"></div>
            </div>
        </section>

        <section class="vs-news-feed vs-news-grid" id="newsGrid" aria-live="polite"></section>

        <section class="vs-gazette-stage" id="gazetteStage" hidden aria-label="Gazete baskı sayfası"></section>

        <nav class="vs-pagination" id="newsPagination" aria-label="Gazete sayfaları">
            <button type="button" class="vs-page-nav-btn" id="newsPrevPage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                <span id="newsPrevPageLabel">Önceki Sayfa</span>
            </button>
            <span class="vs-page-sheet" id="newsPageSheet">Gazete Sayfası 1 / 1</span>
            <button type="button" class="vs-page-nav-btn" id="newsNextPage">
                <span id="newsNextPageLabel">Sonraki Sayfa</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </nav>
        <p class="vs-page-hint" id="newsPageHint" hidden>Sayfayı çevirmek için alttaki düğmeleri veya klavyede ← → tuşlarını kullanın.</p>
    </main>

    <div class="vs-modal-backdrop" id="newsDetailModal" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
        <article class="vs-modal">
            <button type="button" class="vs-modal__close" id="newsModalClose" aria-label="Kapat">×</button>
            <div class="vs-modal__hero" id="newsModalHero">
                <div class="vs-modal__hero-overlay"></div>
            </div>
            <div class="vs-modal__body">
                <div class="vs-modal__head">
                    <div class="vs-modal__badges" id="newsModalBadges"></div>
                    <time class="vs-modal__date" id="newsModalDate"></time>
                </div>
                <h2 class="vs-modal__title" id="newsModalTitle"></h2>
                <div class="vs-modal__byline">
                    <div class="vs-modal__author">
                        <span class="vs-modal__author-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </span>
                        <div>
                            <strong class="vs-modal__author-name" id="newsModalAuthor"></strong>
                            <span class="vs-modal__author-role" id="newsModalAuthorRole"></span>
                        </div>
                    </div>
                    <div class="vs-modal__article-meta">
                        <span class="vs-modal__meta-item" id="newsModalLocation"></span>
                        <span class="vs-modal__meta-item" id="newsModalReadTime"></span>
                    </div>
                </div>
                <hr class="vs-modal__divider">
                <div class="vs-modal-story" id="newsModalStory"></div>
                <blockquote class="vs-modal-quote" id="newsModalQuote" hidden></blockquote>
            </div>
        </article>
    </div>

    <div class="vs-modal-backdrop" id="rumorModal" role="dialog" aria-modal="true" aria-labelledby="rumorModalTitle">
        <section class="vs-modal" style="width:min(560px,100%)">
            <button type="button" class="vs-modal__close" id="rumorModalClose" aria-label="Kapat">×</button>
            <div class="vs-modal__body">
                <h2 id="rumorModalTitle">Söylenti gönder</h2>
                <p id="rumorModalIntro" class="vs-rumor-modal__intro">Editör onayından sonra gazetede yayınlanır. Kayıt gerekmez.</p>
                <div id="rumorModalSlotBanner" class="vs-rumor-slot-banner" hidden>
                    <span class="vs-rumor-slot-banner__eyebrow" id="rumorModalSlotEyebrow">Gazete yeri</span>
                    <strong class="vs-rumor-slot-banner__label" id="rumorModalSlotLabel">—</strong>
                    <span class="vs-rumor-slot-banner__issue" id="rumorModalSlotIssue"></span>
                </div>
                <form id="rumorForm" class="vs-form-grid">
                    <input type="hidden" name="gazette_slot_kind" id="rumorGazetteSlotKind" value="">
                    <input type="hidden" name="gazette_slot_key" id="rumorGazetteSlotKey" value="">
                    <input type="hidden" name="gazette_slot_label" id="rumorGazetteSlotLabelHidden" value="">
                    <input type="hidden" name="gazette_issue_id" id="rumorGazetteIssueId" value="">
                    <label>
                        <span id="rumorTitleLabel">Başlık</span>
                        <input type="text" name="title" id="rumorTitleInput" required minlength="8" maxlength="255" placeholder="Örn. Kızıl Han'da gizli buluşma…">
                    </label>
                    <div class="vs-form-field">
                        <span class="vs-form-field__label">Tür</span>
                        @include('web.partials.news-type-picker', [
                            'variant' => 'public',
                            'groupId' => 'rumorTypePicker',
                            'inputId' => 'rumorNewsType',
                            'inputName' => 'news_type',
                        ])
                    </div>
                    <label>
                        Konum (isteğe bağlı)
                        <input type="text" name="location_badge" maxlength="150" placeholder="Örn. Isonburg - Alt Şehir">
                    </label>
                    <label id="rumorRumuzWrap" hidden>
                        Rumuz (köşe imzası)
                        <input type="text" name="rumuz" id="rumorRumuzInput" maxlength="80" placeholder="Basılacak rumuz — örn. G.T.">
                    </label>
                    <label id="rumorAuthorWrap">
                        Muhabir adı (isteğe bağlı)
                        <input type="text" name="author" id="rumorAuthorInput" maxlength="120" placeholder="Anonim kalabilirsiniz">
                    </label>
                    <label>
                        <span style="display:flex;gap:.5rem;align-items:center">
                            <input type="checkbox" name="anonymous" checked>
                            Kimliğimi gizli tut
                        </span>
                    </label>
                    <label>
                        <span id="rumorBodyLabel">Söylenti / ihbar metni</span>
                        <textarea name="description" id="rumorBodyInput" rows="5" required minlength="30" maxlength="5000" placeholder="Gördüklerinizi veya duyduklarınızı yazın…"></textarea>
                    </label>
                    <button type="submit" class="primary" id="rumorSubmit" style="justify-self:start;border:0;border-radius:10px;padding:.65rem 1rem">Gönder</button>
                </form>
            </div>
        </section>
    </div>

    <div class="vs-toast" id="newsToast" role="status" aria-live="polite"></div>
</body>
</html>
