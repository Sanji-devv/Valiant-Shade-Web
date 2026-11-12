@php
    $mainSiteUrl = rtrim((string) config('app.url'), '/');
    $gb = $gazetteBrand ?? [];
    $gazetteTitle = $gb['title'] ?? 'Lağımdan Sesler';
    $gazetteMotto = $gb['motto'] ?? '';
    $gazetteTagline = $gb['tagline'] ?? '';
    $gazetteCrest = $gb['crest'] ?? asset('static/valiant-shade/assets/img/gazette/lagimdan-sesler-crest.jpg');
    $gazetteIssue = $gb['issue'] ?? [];
    $share = $shareMeta ?? [
        'title' => $gazetteTitle,
        'description' => $gazetteTagline,
        'image' => \App\ValiantShade\Support\GazetteShareMeta::absolutePublicUrl((string) config('gazette.crest')),
        'url' => \App\ValiantShade\Support\GazetteShareMeta::siteRoot().'/',
        'type' => 'website',
        'image_alt' => $gazetteTitle,
    ];
    $isGazetteView = request()->query('gorunum') === 'gazette'
        || request()->hasAny(['yaprak', 'sayi', 'haber']);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $share['title'] }}</title>
    <meta name="description" content="{{ $share['description'] }}">
    <link rel="canonical" href="{{ $share['url'] }}">
    <meta property="og:site_name" content="{{ $gazetteTitle }}">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:title" content="{{ $share['title'] }}">
    <meta property="og:description" content="{{ $share['description'] }}">
    <meta property="og:url" content="{{ $share['url'] }}">
    <meta property="og:type" content="{{ $share['type'] }}">
    <meta property="og:image" content="{{ $share['image'] }}">
    <meta property="og:image:secure_url" content="{{ $share['image'] }}">
    <meta property="og:image:alt" content="{{ $share['image_alt'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $share['title'] }}">
    <meta name="twitter:description" content="{{ $share['description'] }}">
    <meta name="twitter:image" content="{{ $share['image'] }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=IM+Fell+English:ital@0;1&family=Newsreader:ital,opsz,wght@0,12..72,400;0,12..72,600;0,12..72,700;1,12..72,400&display=swap" rel="stylesheet">
    @vite(['resources/css/valiant-shade/newspaper.css', 'resources/js/valiant-shade/newspaper.js'], 'static/valiant-shade/build')
</head>
<body
    class="vs-newspaper-page{{ $isGazetteView ? ' vs-layout-gazette' : '' }}"
    data-gazette-logo="{{ $gazetteCrest }}"
    data-gazette-name="{{ $gazetteTitle }}"
    data-gazette-motto="{{ $gazetteMotto }}"
    data-gazette-press="{{ $gb['press_name'] ?? 'Lağımdan Sesler Basın Bürosu' }}"
    data-gazette-issue-date="{{ $gazetteIssue['date_line'] ?? 'Leos ayının 1. günü, Vonyas · 631' }}"
    data-gazette-issue-number="{{ $gazetteIssue['number'] ?? 1 }}"
    data-gazette-issue-label="{{ $gazetteIssue['label'] ?? 'İlk sayı' }}"
    data-gazette-issue-month="{{ $gazetteIssue['month'] ?? 'Leos' }}"
    data-gazette-issue-day="{{ $gazetteIssue['day'] ?? 1 }}"
    data-gazette-issue-year="{{ $gazetteIssue['year'] ?? 631 }}"
    data-gazette-calendar='@json($gb["calendar"] ?? [])'
    data-gazette-submission-slots='@json(config("gazette.submission_slots", []))'
    data-gazette-corner-slots='@json(config("gazette.corner_slots", []))'
>
    <div class="vs-mobile-issue-dock" id="mobileIssueDock" hidden></div>
    <header class="vs-newspaper-header">
        <div class="vs-newspaper-header__mast">
            <div class="vs-newspaper-header__brand">
                <h1><button type="button" class="vs-gazette-home-link" data-gazette-home aria-label="Kapak sayfasına dön">{{ $gazetteTitle }}</button></h1>
                <p class="vs-newspaper-header__tagline">{{ $gazetteTagline }}</p>
            </div>
            <div class="vs-newspaper-header__issue" id="gazetteHeaderIssue">
                <label class="vs-gazette-archive-wrap" id="gazetteArchiveWrap" hidden>
                    <span class="vs-gazette-archive-wrap__label">Sayı</span>
                    <select class="vs-gazette-archive" id="gazetteArchiveSelect" aria-label="Gazete sayısı arşivi"></select>
                </label>
            </div>
        </div>
        <nav class="vs-newspaper-nav" aria-label="Gazete menüsü">
            <a href="{{ $mainSiteUrl }}" class="vs-newspaper-nav__item" aria-label="Ana Sayfa">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Ana Sayfa</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Ana Sayfa</span>
            </a>
            <span class="vs-newspaper-nav__item vs-newspaper-nav__soon" tabindex="0" aria-disabled="true" aria-label="Lonca Arazisi · {{ __('Coming soon') }}">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 9v12h6v-7h4v7h6V9l-8-6z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Lonca Arazisi</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Lonca</span>
                <span class="vs-newspaper-nav__tooltip" role="tooltip">{{ __('Coming soon') }}</span>
            </span>
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--print" id="printGazetteBtn" @if (! $isGazetteView) hidden @endif aria-label="PDF İndir">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">PDF İndir</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">PDF</span>
            </button>
            <button
                type="button"
                class="vs-newspaper-nav__item vs-newspaper-nav__item--print"
                id="downloadGazettePngBtn"
                @if (! $isGazetteView) hidden @endif
                aria-label="Açık gazete yaprağını PNG olarak indir"
            >
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">PNG İndir</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">PNG</span>
            </button>
            <button
                type="button"
                class="vs-newspaper-nav__item vs-newspaper-nav__item--hot"
                id="openHotExtraModal"
                aria-label="Flaş haber gönder"
                title="Flaş haber gönder"
            >
                <svg class="vs-newspaper-nav__icon vs-newspaper-nav__icon--flame" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22c4.5 0 8-3.25 8-7.5 0-2.65-1.6-4.9-3.85-6.15C15.4 6.9 13.35 3.5 12 2c-1.35 1.5-3.4 4.9-4.15 6.35C5.6 9.6 4 11.85 4 14.5 4 18.75 7.5 22 12 22z"/>
                    <path d="M12 22c-1.8 0-3.25-1.35-3.25-3.25S10.2 15.5 12 15.5s3.25 1.35 3.25 3.25S13.8 22 12 22z"/>
                </svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Flaş haber</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Flaş</span>
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

        <section class="vs-toolbar" id="newsToolbar" aria-label="Haber filtreleri">
            <div class="vs-toolbar__filters-row" id="toolbarFiltersRow">
                <div class="vs-filter-strip">
                    <div class="vs-filter-panel" id="newsFilterPanel" hidden>
                        <div class="vs-filter-group" id="newsFilterGroup"></div>
                    </div>
                </div>
            </div>
            <div class="vs-toolbar__bar">
                <div class="vs-toolbar__actions">
                    <button type="button" class="vs-toolbar__btn vs-toolbar__btn--filters" id="toggleFiltersBtn" aria-expanded="false" aria-controls="newsFilterPanel">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        <span id="toggleFiltersLabel">Filtreler</span>
                    </button>
                    <button type="button" class="vs-toolbar__btn vs-toolbar__btn--view" id="toggleViewModeBtn" aria-pressed="false" title="Liste görünümü">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        <span id="toggleViewModeLabel">Liste</span>
                    </button>
                </div>
                <input type="search" class="vs-search vs-toolbar__search" id="newsSearchInput" placeholder="Haber, söylenti veya kahraman ara…" aria-label="Haber ara" enterkeyhint="search">
                <div class="vs-toolbar__bar-end" id="toolbarBarEnd">
                    <button type="button" class="vs-layout-toggle vs-layout-toggle--icon vs-toolbar__layout-toggle" id="toggleGazetteLayoutBtn" aria-pressed="false" title="Basılı gazete sayfası. Sayfa sayfa gezin." aria-label="Gazete görünümü">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 4v16M16 4v16M4 8h16M4 16h16"/></svg>
                        <span id="toggleGazetteLayoutLabel" hidden>Gazete görünümü</span>
                    </button>
                </div>
            </div>
        </section>

        <section class="vs-news-feed vs-news-grid" id="newsGrid" aria-live="polite"></section>

        <div class="vs-gazette-flip" id="gazetteFlip" hidden>
            <button type="button" class="vs-gazette-flip__arrow vs-gazette-flip__arrow--prev" id="gazetteFlipPrev" aria-label="Önceki yaprak" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <div class="vs-gazette-flip__sheet">
                <section class="vs-gazette-stage" id="gazetteStage" aria-label="Gazete baskı sayfası"></section>
            </div>
            <button type="button" class="vs-gazette-flip__arrow vs-gazette-flip__arrow--next" id="gazetteFlipNext" aria-label="Sonraki yaprak">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>

        <nav class="vs-pagination" id="newsPagination" aria-label="Gazete sayfaları">
            <button type="button" class="vs-page-nav-btn" id="newsPrevPage" aria-label="Önceki sayfa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                <span class="vs-page-nav-btn__label vs-page-nav-btn__label--full" id="newsPrevPageLabel">Önceki Sayfa</span>
            </button>
            <span class="vs-page-sheet vs-page-sheet--full" id="newsPageSheet">Gazete Sayfası 1 / 1</span>
            <span class="vs-page-sheet vs-page-sheet--short" id="newsPageSheetShort" aria-hidden="true">1 / 1</span>
            <button type="button" class="vs-page-nav-btn" id="newsNextPage" aria-label="Sonraki sayfa">
                <span class="vs-page-nav-btn__label vs-page-nav-btn__label--full" id="newsNextPageLabel">Sonraki Sayfa</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </nav>
        <p class="vs-page-hint" id="newsPageHint" hidden>Yaprak çevirmek için yan okları, alttaki düğmeleri veya klavyede ← → tuşlarını kullanın.</p>
    </main>

    <div class="vs-modal-backdrop vs-modal-backdrop--article" id="newsDetailModal" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
        <article class="vs-modal vs-modal--article" id="newsDetailModalPanel">
            <div class="vs-modal__grabber" aria-hidden="true"></div>
            <div class="vs-modal__chrome">
                <div class="vs-modal__chrome-actions" role="group" aria-label="Haber işlemleri">
                    <button type="button" class="vs-modal__chrome-btn" id="newsModalPdf" aria-label="PDF indir" title="PDF indir">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    </button>
                    <button type="button" class="vs-modal__chrome-btn" id="newsModalPng" aria-label="PNG indir" title="PNG indir">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                    </button>
                    <button type="button" class="vs-modal__chrome-btn" id="newsModalShare" aria-label="Paylaş" title="Paylaş">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/></svg>
                    </button>
                </div>
                <button type="button" class="vs-modal__close" id="newsModalClose" aria-label="Kapat">×</button>
            </div>
            <div class="vs-modal__hero" id="newsModalHero">
                <div class="vs-modal__hero-overlay"></div>
            </div>
            <div class="vs-modal__body vs-modal__body--article">
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

    <div class="vs-modal-backdrop vs-modal-backdrop--rumor" id="rumorModal" role="dialog" aria-modal="true" aria-labelledby="rumorModalTitle">
        <section class="vs-modal vs-modal--rumor-gazette" id="rumorModalPanel">
            <button type="button" class="vs-modal__close vs-modal__close--gazette" id="rumorModalClose" aria-label="Kapat">×</button>
            <div class="vs-modal__body vs-modal__body--rumor">
                <header class="vs-rumor-modal__head">
                    <div class="vs-rumor-modal__title-row">
                        <h2 id="rumorModalTitle">Söylenti gönder</h2>
                        <div class="vs-rumor-issue-pick" id="rumorIssuePickWrap">
                            <span class="vs-rumor-issue-pick__label" id="rumorIssuePickLabel">Sayı</span>
                            <select class="vs-rumor-issue-pick__select" id="rumorIssueSelect" aria-labelledby="rumorModalTitle rumorIssuePickLabel"></select>
                        </div>
                    </div>
                    <p id="rumorModalIntro" class="vs-rumor-modal__intro">Editör onayından sonra gazetede yayınlanır. Kayıt gerekmez.</p>
                </header>
                <div class="vs-rumor-placement" id="rumorPlacementWrap">
                    <span class="vs-rumor-placement__label">Gazetede yeri</span>
                    <div class="vs-rumor-placement__kinds" id="rumorPlacementKinds" role="radiogroup" aria-label="Gönderi yeri"></div>
                    <div class="vs-rumor-corner-pick" id="rumorCornerPicker" hidden>
                        <span class="vs-rumor-corner-pick__label">Hangi köşe?</span>
                        <div class="vs-rumor-corner-pick__grid" id="rumorCornerPickerGrid"></div>
                    </div>
                </div>
                <form id="rumorForm" class="vs-form-grid vs-form-grid--rumor">
                    <input type="hidden" name="gazette_slot_kind" id="rumorGazetteSlotKind" value="">
                    <input type="hidden" name="gazette_slot_key" id="rumorGazetteSlotKey" value="">
                    <input type="hidden" name="gazette_slot_label" id="rumorGazetteSlotLabelHidden" value="">
                    <input type="hidden" name="gazette_issue_id" id="rumorGazetteIssueId" value="">
                    <div class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">Tür</span>
                        @include('web.partials.news-type-picker', [
                            'variant' => 'public',
                            'groupId' => 'rumorTypePicker',
                            'inputId' => 'rumorNewsType',
                            'inputName' => 'news_type',
                        ])
                    </div>
                    <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--three vs-rumor-form__span" id="rumorMetaGrid">
                        <label class="vs-rumor-form__meta-cell">
                            Konum (isteğe bağlı)
                            <input type="text" name="location_badge" maxlength="150" placeholder="Örn. Isonburg - Alt Şehir">
                        </label>
                        <label id="rumorRumuzWrap" class="vs-rumor-form__meta-cell" hidden>
                            Rumuz (köşe imzası)
                            <input type="text" name="rumuz" id="rumorRumuzInput" maxlength="80" placeholder="Basılacak rumuz, örn. G.T.">
                        </label>
                        <label id="rumorAuthorWrap" class="vs-rumor-form__meta-cell">
                            Muhabir adı (isteğe bağlı)
                            <input type="text" name="author" id="rumorAuthorInput" maxlength="120" placeholder="Anonim kalabilirsiniz">
                        </label>
                        <div class="vs-rumor-form__meta-cell vs-rumor-form__meta-cell--check">
                            <input type="checkbox" name="anonymous" id="rumorAnonymousInput" class="vs-rumor-form__check-input" tabindex="-1" aria-hidden="true">
                            <button type="button" class="vs-rumor-anon-toggle" id="rumorAnonymousToggle" aria-pressed="false">
                                Kimliğimi gizli tut
                            </button>
                        </div>
                    </div>
                    <label class="vs-rumor-form__span">
                        <span id="rumorTitleLabel">Başlık</span>
                        <input type="text" name="title" id="rumorTitleInput" required minlength="8" maxlength="255" placeholder="Örn. Kızıl Han'da gizli buluşma…">
                    </label>
                    <label class="vs-rumor-form__span">
                        <span id="rumorBodyLabel">Söylenti / ihbar metni</span>
                        <textarea name="description" id="rumorBodyInput" rows="6" required minlength="30" maxlength="5000" placeholder="Gördüklerinizi veya duyduklarınızı yazın…"></textarea>
                    </label>
                    <div class="vs-rumor-form__actions vs-rumor-form__span">
                        <button type="submit" class="vs-rumor-submit" id="rumorSubmit">Gönder</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="vs-modal-backdrop vs-modal-backdrop--rumor vs-modal-backdrop--gazette-form" id="hotExtraModal" role="dialog" aria-modal="true" aria-labelledby="hotExtraModalTitle">
        <section class="vs-modal vs-modal--rumor-gazette vs-modal--rumor-gazette--active vs-modal--rumor-slot-flash" id="hotExtraModalPanel">
            <button type="button" class="vs-modal__close vs-modal__close--gazette" id="hotExtraModalClose" aria-label="Kapat">×</button>
            <div class="vs-modal__body vs-modal__body--rumor">
                <header class="vs-rumor-modal__head">
                    <h2 id="hotExtraModalTitle">Flash haber gönder</h2>
                    <p class="vs-rumor-modal__intro">Tek haber, tek yaprak. Editör onayından sonra ek sayı olarak basılır (ör. 2.1). Günün takvim tarihi o yaprakta görünür. Köşe yazısı veya özet sütunu yoktur.</p>
                </header>
                <form id="hotExtraForm" class="vs-form-grid vs-form-grid--rumor" enctype="multipart/form-data">
                    <div class="vs-rumor-slot-banner">
                        <span class="vs-rumor-slot-banner__eyebrow">Fırlatma ek sayısı</span>
                        <span class="vs-rumor-slot-banner__label">Sıcak haber · yazıyor yazıyor</span>
                        <span class="vs-rumor-slot-banner__issue">Onay sonrası haftanın ek numarası otomatik verilir</span>
                    </div>
                    <div class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">Tür</span>
                        @include('web.partials.news-type-picker', [
                            'variant' => 'public',
                            'groupId' => 'hotExtraTypePicker',
                            'inputId' => 'hotExtraNewsType',
                            'inputName' => 'news_type',
                        ])
                    </div>
                    <label class="vs-rumor-form__span">
                        Manşet başlığı
                        <input type="text" name="title" id="hotExtraTitleInput" required minlength="8" maxlength="255" placeholder="Örn. Limanda yangın: iskele kapatıldı">
                    </label>
                    <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--three vs-rumor-form__span">
                        <label class="vs-rumor-form__meta-cell">
                            Konum (isteğe bağlı)
                            <input type="text" name="location_badge" maxlength="150" placeholder="Örn. Isonburg limanı">
                        </label>
                        <label class="vs-rumor-form__meta-cell">
                            Muhabir imzası (isteğe bağlı)
                            <input type="text" name="author" id="flashAuthorInput" maxlength="120" placeholder="Basılacak isim">
                        </label>
                        <div class="vs-rumor-form__meta-cell vs-rumor-form__meta-cell--check">
                            <input type="checkbox" name="anonymous" id="flashAnonymousInput" class="vs-rumor-form__check-input" tabindex="-1" aria-hidden="true">
                            <button type="button" class="vs-rumor-anon-toggle" id="flashAnonymousToggle" aria-pressed="false">
                                Kimliğimi gizli tut
                            </button>
                        </div>
                    </div>
                    <label class="vs-rumor-form__span">
                        Görsel
                        <select name="image_preset" id="hotExtraImagePreset">
                            <option value="__gazette_auto__">Metinden otomatik woodcut</option>
                            @foreach (($gazetteImagePresets ?? []) as $presetPath => $presetLabel)
                            <option value="{{ $presetPath }}">{{ $presetLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="vs-flash-image-preview-wrap" id="flashImagePreviewWrap" hidden>
                        <span class="vs-flash-image-preview__label">Woodcut önizleme</span>
                        <div class="vs-flash-image-preview">
                            <img id="flashImagePreview" src="" alt="" width="200" height="120">
                        </div>
                        <p class="vs-flash-image-preview__hint" id="flashImagePreviewHint"></p>
                    </div>
                    <label class="vs-rumor-form__span">
                        Veya dosya yükle
                        <input type="file" name="custom_image" id="flashCustomImageInput" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <label class="vs-rumor-form__span">
                        Haber metni
                        <textarea name="description" id="hotExtraBodyInput" rows="10" required minlength="80" maxlength="12000" placeholder="Tek sayfalık akıcı anlatım. Köşe yok, özet yok; yazıyor yazıyor."></textarea>
                    </label>
                    <div class="vs-rumor-form__actions vs-rumor-form__span">
                        <button type="submit" class="vs-rumor-submit vs-rumor-submit--flash" id="hotExtraSubmit">Editöre gönder</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="vs-toast" id="newsToast" role="status" aria-live="polite"></div>
</body>
</html>
