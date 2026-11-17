@php
    use App\Support\PublicSiteUrls;

    $mainSiteUrl = PublicSiteUrls::main();
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
    $onGazetteHost = PublicSiteUrls::isGazetteSubdomainRequest();
    $isGazetteView = request()->query('gorunum') === 'gazette'
        || request()->hasAny(['yaprak', 'sayi', 'haber'])
        || ($onGazetteHost && request()->query('gorunum') !== 'web');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $share['title'] }}</title>
    @include('web.partials.head-favicon')
    @include('web.partials.head-public-seo', [
        'title' => $share['title'],
        'description' => $share['description'],
        'canonical' => $share['url'],
        'ogSiteName' => $gazetteTitle,
        'ogType' => $share['type'],
        'ogImage' => $share['image'],
        'ogImageAlt' => $share['image_alt'],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=IM+Fell+English:ital@0;1&family=Newsreader:ital,opsz,wght@0,12..72,400;0,12..72,600;0,12..72,700;1,12..72,400&display=swap" rel="stylesheet">
    @vite(['resources/css/valiant-shade/newspaper.css', 'resources/js/valiant-shade/newspaper.js'], 'static/valiant-shade/build')
</head>
<body
    class="vs-newspaper-page{{ $isGazetteView ? ' vs-layout-gazette' : '' }}"
    data-gazette-subdomain="{{ $onGazetteHost ? '1' : '0' }}"
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
            <a href="{{ PublicSiteUrls::map('/feldan') }}" class="vs-newspaper-nav__item" aria-label="Harita — Feldan Krallığı">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2V6z"/><path d="M9 4v14M15 6v14"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Harita</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">Harita</span>
            </a>
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
                class="vs-newspaper-nav__item vs-newspaper-nav__item--print"
                id="downloadIssueZipBtn"
                @if (! $isGazetteView) hidden @endif
                aria-label="Açık sayıdaki haberleri ayrı PNG dosyaları olarak ZIP indir"
            >
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M9 4v5h6V4M9 20v-6h6v6M4 9h5m6 0h5"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">Haberleri ZIP indir</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">ZIP</span>
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
                <footer class="vs-modal__article-foot">
                    <div class="vs-modal__reactions" id="newsModalReactions" role="group" aria-label="Olay tepkileri"></div>
                    <div class="vs-modal__engagement" id="newsModalEngagement" hidden aria-label="Okuma istatistikleri"></div>
                </footer>
            </div>
        </article>
    </div>

    <div class="vs-modal-backdrop vs-modal-backdrop--rumor" id="rumorModal" role="dialog" aria-modal="true" aria-labelledby="rumorModalTitle">
        <section class="vs-modal vs-modal--rumor-gazette" id="rumorModalPanel">
            <header class="vs-rumor-toolbar" aria-label="Söylenti gönder">
                <button type="button" class="vs-rumor-toolbar__back" id="rumorWizardBack" hidden aria-label="Gönderi türüne dön">
                    <span aria-hidden="true">←</span> Geri
                </button>
                <h2 class="vs-rumor-toolbar__title" id="rumorModalTitle">Söylenti gönder</h2>
                <button type="button" class="vs-rumor-toolbar__close vs-modal__close vs-modal__close--gazette" id="rumorModalClose" aria-label="Kapat">×</button>
            </header>
            <div class="vs-modal__body vs-modal__body--rumor">
                <p id="rumorModalIntro" class="vs-rumor-modal__intro">Ne göndermek istediğinizi seçin. Editör onayından sonra basılır; kayıt gerekmez.</p>

                <div class="vs-rumor-wizard__pick" id="rumorWizardPick">
                    <p class="vs-rumor-wizard__pick-label">Gönderi türü</p>
                    <div class="vs-rumor-wizard__kind-grid" id="rumorWizardKinds" role="listbox" aria-label="Gönderi türü seçimi"></div>
                </div>

                <div class="vs-rumor-wizard__form" id="rumorWizardForm" hidden>
                    <aside class="vs-rumor-demo" id="rumorDemoPreview" aria-label="Gazetede nasıl görüneceğine dair önizleme"></aside>
                    <form id="rumorForm" class="vs-form-grid vs-form-grid--rumor" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="gazette_slot_kind" id="rumorGazetteSlotKind" value="">
                            <input type="hidden" name="gazette_slot_key" id="rumorGazetteSlotKey" value="">
                            <input type="hidden" name="gazette_slot_label" id="rumorGazetteSlotLabelHidden" value="">
                            <input type="hidden" name="gazette_issue_id" id="rumorGazetteIssueId" value="">

                            <div class="vs-rumor-issue-pick vs-rumor-form__span" id="rumorIssuePickWrap" hidden>
                                <span class="vs-form-field__label" id="rumorIssuePickLabel">Hangi sayı? <span class="vs-form-field__req">*</span></span>
                                <div class="vs-issue-picker" id="rumorIssuePicker" role="radiogroup" aria-labelledby="rumorIssuePickLabel"></div>
                                <p class="vs-field-inline-error" id="rumorIssueFieldError" hidden role="alert"></p>
                                <p class="vs-rumor-issue-pick__hint" id="rumorIssuePickHint">Yalnızca mevcut veya gelecek sayıya gönderebilirsiniz; geçmiş sayılar kapalıdır.</p>
                            </div>

                            <div class="vs-form-field vs-rumor-form__span" id="rumorTypeFieldWrap">
                                <span class="vs-form-field__label">Haber türü <span class="vs-form-field__req">*</span></span>
                                @include('web.partials.news-type-picker', [
                                    'variant' => 'public',
                                    'groupId' => 'rumorTypePicker',
                                    'inputId' => 'rumorNewsType',
                                    'inputName' => 'news_type',
                                    'noDefault' => true,
                                ])
                                <p class="vs-field-inline-error" id="rumorTypeFieldError" hidden role="alert"></p>
                            </div>

                            <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--three vs-rumor-form__span" id="rumorMetaGrid">
                                <label class="vs-rumor-form__meta-cell" id="rumorLocationWrap">
                                    Konum (isteğe bağlı)
                                    <input type="text" name="location_badge" id="rumorLocationInput" maxlength="150" placeholder="Örn. Isonburg - Alt Şehir">
                                </label>
                                <label id="rumorAuthorWrap" class="vs-rumor-form__meta-cell">
                                    Muhabir imzası (isteğe bağlı)
                                    <input type="text" name="author" id="rumorAuthorInput" maxlength="120" placeholder="Boş bırakılabilir">
                                </label>
                                <div class="vs-rumor-form__meta-cell vs-rumor-form__meta-cell--check" id="rumorAnonymousWrap">
                                    <input type="checkbox" name="anonymous" id="rumorAnonymousInput" class="vs-rumor-form__check-input" tabindex="-1" aria-hidden="true">
                                    <button type="button" class="vs-rumor-anon-toggle" id="rumorAnonymousToggle" aria-pressed="false">
                                        Kimliğimi gizli tut
                                    </button>
                                </div>
                            </div>

                            <div class="vs-rumor-corner-pick vs-rumor-form__span" id="rumorCornerPicker" hidden>
                                <span class="vs-rumor-corner-pick__label">Köşe sütunu</span>
                                <p class="vs-rumor-corner-pick__summary" id="rumorCornerCountSummary"></p>
                                <div class="vs-rumor-corner-pick__grid" id="rumorCornerPickerGrid"></div>
                                <p class="vs-field-inline-error" id="rumorCornerFieldError" hidden role="alert"></p>
                            </div>

                            <label class="vs-form-field vs-rumor-form__span" id="rumorRumuzFieldWrap" hidden>
                                <span class="vs-form-field__label">Rumuz <span class="vs-form-field__req">*</span></span>
                                <input type="text" name="rumuz" id="rumorRumuzInput" maxlength="80" placeholder="Basılacak rumuz, örn. G.T." required>
                                <p class="vs-field-inline-error" id="rumorRumuzFieldError" hidden role="alert"></p>
                            </label>

                            <label class="vs-rumor-form__span" id="rumorTitleWrap">
                                <span id="rumorTitleLabel">Başlık <span class="vs-form-field__req">*</span></span>
                                <input type="text" name="title" id="rumorTitleInput" minlength="8" maxlength="255" placeholder="Örn. Kızıl Han'da gizli buluşma…">
                                <p class="vs-field-inline-error" id="rumorTitleFieldError" hidden role="alert"></p>
                            </label>

                            <div class="vs-rumor-form__span" id="rumorImageSection">
                                <label>
                                    Görsel
                                    <select name="image_preset" id="rumorImagePreset">
                                        <option value="__gazette_auto__">Metinden otomatik woodcut</option>
                                        @foreach (($gazetteImagePresets ?? []) as $presetPath => $presetLabel)
                                        <option value="{{ $presetPath }}">{{ $presetLabel }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="vs-submission-image-preview-wrap" id="rumorImagePreviewWrap" hidden>
                                    <span class="vs-submission-image-preview__label">Görsel önizleme</span>
                                    <div class="vs-submission-image-preview">
                                        <img id="rumorImagePreview" alt="Seçilen görsel önizlemesi" width="200" height="120">
                                    </div>
                                    <p class="vs-submission-image-preview__hint" id="rumorImagePreviewHint"></p>
                                    <button type="button" class="vs-rumor-remove-image" id="rumorRemoveImage">Görseli kaldır</button>
                                </div>
                                <label class="vs-rumor-upload-label">
                                    Veya dosya yükle
                                    <input type="file" name="custom_image" id="rumorCustomImageInput" accept="image/jpeg,image/png,image/webp">
                                </label>
                                <p class="vs-rumor-upload-hint" id="rumorUploadHint">JPEG, PNG veya WebP · en fazla 5 MB</p>
                                <p class="vs-field-inline-error" id="rumorImageFieldError" hidden role="alert"></p>
                            </div>

                            <label class="vs-rumor-form__span" id="rumorBodyWrap">
                                <span id="rumorBodyLabel">Haber metni <span class="vs-form-field__req">*</span></span>
                                <textarea name="description" id="rumorBodyInput" rows="6" minlength="30" maxlength="12000" placeholder="Gördüklerinizi veya duyduklarınızı yazın…"></textarea>
                                <p class="vs-field-inline-error" id="rumorBodyFieldError" hidden role="alert"></p>
                            </label>
                            <div class="vs-rumor-form__actions vs-rumor-form__span">
                                <button type="submit" class="vs-rumor-submit" id="rumorSubmit">Editöre gönder</button>
                            </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div class="vs-toast" id="newsToast" role="status" aria-live="polite"></div>
</body>
</html>
