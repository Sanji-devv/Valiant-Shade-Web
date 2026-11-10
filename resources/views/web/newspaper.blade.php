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
    <title>{{ $gazetteTitle }} · {{ config('app.name', 'Valiant Shade') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=IM+Fell+English:ital@0;1&family=Newsreader:ital,opsz,wght@0,12..72,400;0,12..72,600;0,12..72,700;1,12..72,400&display=swap" rel="stylesheet">
    @vite(['resources/css/valiant-shade/newspaper.css', 'resources/js/valiant-shade/newspaper.js'], 'static/valiant-shade/build')
</head>
<body
    class="vs-newspaper-page{{ request()->query('gorunum') === 'gazette' ? ' vs-layout-gazette' : '' }}"
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
    data-msg-png-success="{{ __('Gazette sheet downloaded as PNG.') }}"
    data-msg-pdf-success="{{ __('Gazette sheet downloaded as PDF.') }}"
    data-msg-download-error="{{ __('Download failed. Please try again.') }}"
    data-msg-preparing-pdf="{{ __('Generating PDF document...') }}"
    data-msg-preparing-png="{{ __('Generating high-resolution PNG...') }}"
    data-msg-no-sheet="{{ __('No gazette sheet available to download.') }}"
>
    <header class="vs-newspaper-header">
        <div class="vs-newspaper-header__brand">
            <h1><button type="button" class="vs-gazette-home-link" data-gazette-home aria-label="{{ __('Return to cover page') }}">{{ $gazetteTitle }}</button></h1>
            <p class="vs-newspaper-header__tagline">{{ $gazetteTagline }}</p>
        </div>
        <nav class="vs-newspaper-nav" aria-label="{{ __('Gazette menu') }}">
            <a href="{{ $mainSiteUrl }}" class="vs-newspaper-nav__item" aria-label="{{ __('Home') }}">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Home') }}</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Home') }}</span>
            </a>
            <span class="vs-newspaper-nav__item vs-newspaper-nav__soon" tabindex="0" aria-disabled="true" aria-label="{{ __('Guild Grounds') }} · {{ __('Coming soon') }}">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 9v12h6v-7h4v7h6V9l-8-6z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Guild Grounds') }}</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Guild') }}</span>
                <span class="vs-newspaper-nav__tooltip" role="tooltip">{{ __('Coming soon') }}</span>
            </span>
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--print" id="downloadGazetteBtn" aria-label="{{ __('Gazette download options') }}">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Download') }}</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Download') }}</span>
            </button>
            <button type="button" class="vs-newspaper-nav__item vs-newspaper-nav__item--primary" id="openRumorModal" aria-label="{{ __('Submit Rumor') }}">
                <svg class="vs-newspaper-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"/></svg>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--full">{{ __('Submit Rumor') }}</span>
                <span class="vs-newspaper-nav__label vs-newspaper-nav__label--short">{{ __('Rumor') }}</span>
            </button>
        </nav>
    </header>

    <dialog class="vs-download-dialog" id="downloadGazetteDialog" aria-labelledby="downloadGazetteTitle">
        <form method="dialog">
            <button type="submit" class="vs-download-dialog__close" aria-label="{{ __('Close') }}">×</button>
        </form>
        <div class="vs-download-dialog__header">
            <h2 id="downloadGazetteTitle">{{ __('Download Gazette') }}</h2>
            <p>{{ __('Download the currently active gazette sheet in your preferred format.') }}</p>
        </div>
        <div class="vs-download-dialog__options" id="downloadGazetteOptions">
            <button type="button" class="vs-download-dialog__option vs-download-dialog__option--pdf" id="downloadGazettePdfOption">
                <div class="vs-download-dialog__option-badge">PDF</div>
                <div class="vs-download-dialog__option-content">
                    <strong>{{ __('Download as PDF') }}</strong>
                    <span>{{ __('High-resolution document ready for printing (.pdf)') }}</span>
                </div>
                <svg class="vs-download-dialog__option-arrow" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
            <button type="button" class="vs-download-dialog__option vs-download-dialog__option--png" id="downloadGazettePngOption">
                <div class="vs-download-dialog__option-badge">PNG</div>
                <div class="vs-download-dialog__option-content">
                    <strong>{{ __('Download as PNG') }}</strong>
                    <span>{{ __('Crystal clear digital image (.png)') }}</span>
                </div>
                <svg class="vs-download-dialog__option-arrow" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
            <button type="button" class="vs-download-dialog__option vs-download-dialog__option--print" id="downloadGazettePrintOption">
                <div class="vs-download-dialog__option-badge">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                </div>
                <div class="vs-download-dialog__option-content">
                    <strong>{{ __('Print Directly') }}</strong>
                    <span>{{ __('Open browser print dialog for physical printing') }}</span>
                </div>
                <svg class="vs-download-dialog__option-arrow" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>
        <div class="vs-download-dialog__status" id="downloadGazetteStatus" hidden aria-live="polite">
            <div class="vs-download-dialog__spinner"></div>
            <span id="downloadGazetteStatusMsg">{{ __('Generating document, please wait...') }}</span>
        </div>
    </dialog>

    <main class="vs-newspaper-main">
        <section class="vs-news-slider" aria-label="{{ __('Headline news') }}">
            <div class="vs-news-slider__viewport">
                <button type="button" class="vs-news-slider__arrow vs-news-slider__arrow--prev" id="newsSliderPrev" aria-label="{{ __('Previous headline') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div class="vs-news-slider__track" id="newsSliderTrack"></div>
                <button type="button" class="vs-news-slider__arrow vs-news-slider__arrow--next" id="newsSliderNext" aria-label="{{ __('Next headline') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
            <div class="vs-news-slider__controls">
                <div class="vs-news-slider__dots" id="newsSliderDots"></div>
            </div>
        </section>

        <section class="vs-toolbar" aria-label="{{ __('News filters') }}">
            <div class="vs-toolbar__bar">
                <div class="vs-toolbar__actions">
                    <button type="button" class="vs-toolbar__btn" id="toggleFiltersBtn" aria-expanded="false" aria-controls="newsFilterPanel">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                        <span id="toggleFiltersLabel">{{ __('Filters') }}</span>
                    </button>
                    <button type="button" class="vs-toolbar__btn" id="toggleViewModeBtn" aria-pressed="false" title="{{ __('List view') }}">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        <span id="toggleViewModeLabel">{{ __('List') }}</span>
                    </button>
                </div>
                <input type="search" class="vs-search vs-toolbar__search" id="newsSearchInput" placeholder="{{ __('Search news, rumors or heroes...') }}" aria-label="{{ __('Search news') }}">
                <div class="vs-toolbar__gazette" id="gazetteToolbarExtras">
                    <label class="vs-gazette-archive-wrap" id="gazetteArchiveWrap" hidden>
                        <span class="vs-gazette-archive-wrap__label">{{ __('Issue') }}</span>
                        <select class="vs-gazette-archive" id="gazetteArchiveSelect" aria-label="{{ __('Gazette issue archive') }}"></select>
                    </label>
                    <button type="button" class="vs-toolbar__btn vs-toolbar__btn--download" id="gazetteToolbarDownloadBtn" aria-label="{{ __('Download Gazette') }}" title="{{ __('Download current page as PDF or PNG') }}" hidden>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                        <span>{{ __('Download') }}</span>
                    </button>
                </div>
            </div>
            <div class="vs-toolbar__filters-row">
                <div class="vs-filter-strip">
                    <div class="vs-filter-panel" id="newsFilterPanel" hidden>
                        <div class="vs-filter-group" id="newsFilterGroup"></div>
                    </div>
                    <button type="button" class="vs-layout-toggle vs-layout-toggle--icon" id="toggleGazetteLayoutBtn" aria-pressed="false" title="{{ __('Printed gazette page. Flip page by page.') }}" aria-label="{{ __('Gazette view') }}">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 4v16M16 4v16M4 8h16M4 16h16"/></svg>
                        <span id="toggleGazetteLayoutLabel" hidden>{{ __('Gazette view') }}</span>
                    </button>
                </div>
            </div>
        </section>

        <section class="vs-news-feed vs-news-grid" id="newsGrid" aria-live="polite"></section>

        <div class="vs-gazette-flip" id="gazetteFlip" hidden>
            <button type="button" class="vs-gazette-flip__arrow vs-gazette-flip__arrow--prev" id="gazetteFlipPrev" aria-label="{{ __('Previous sheet') }}" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <div class="vs-gazette-flip__sheet">
                <section class="vs-gazette-stage" id="gazetteStage" aria-label="{{ __('Gazette print sheet') }}"></section>
            </div>
            <button type="button" class="vs-gazette-flip__arrow vs-gazette-flip__arrow--next" id="gazetteFlipNext" aria-label="{{ __('Next sheet') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>

        <nav class="vs-pagination" id="newsPagination" aria-label="{{ __('Gazette pages') }}">
            <button type="button" class="vs-page-nav-btn" id="newsPrevPage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                <span id="newsPrevPageLabel">{{ __('Previous Page') }}</span>
            </button>
            <span class="vs-page-sheet" id="newsPageSheet">{{ __('Gazette Page 1 / 1') }}</span>
            <button type="button" class="vs-page-nav-btn vs-page-nav-btn--download" id="gazettePaginationDownloadBtn" title="{{ __('Download current page') }}" aria-label="{{ __('Download current page as PDF or PNG') }}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                <span>{{ __('Download') }}</span>
            </button>
            <button type="button" class="vs-page-nav-btn" id="newsNextPage">
                <span id="newsNextPageLabel">{{ __('Next Page') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </nav>
        <p class="vs-page-hint" id="newsPageHint" hidden>{{ __('Use the side arrows, bottom buttons or arrow keys to flip pages.') }}</p>
    </main>

    <div class="vs-modal-backdrop vs-modal-backdrop--article" id="newsDetailModal" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
        <article class="vs-modal vs-modal--article" id="newsDetailModalPanel">
            <div class="vs-modal__chrome">
                <button type="button" class="vs-modal__share" id="newsModalShare" title="{{ __('Copy link to this article') }}">{{ __('Copy link') }}</button>
                <button type="button" class="vs-modal__close" id="newsModalClose" aria-label="{{ __('Close') }}">×</button>
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
            <button type="button" class="vs-modal__close vs-modal__close--gazette" id="rumorModalClose" aria-label="{{ __('Close') }}">×</button>
            <div class="vs-modal__body vs-modal__body--rumor">
                <header class="vs-rumor-modal__head">
                    <div class="vs-rumor-modal__title-row">
                        <h2 id="rumorModalTitle">{{ __('Submit Rumor') }}</h2>
                        <div class="vs-rumor-issue-pick" id="rumorIssuePickWrap">
                            <span class="vs-rumor-issue-pick__label" id="rumorIssuePickLabel">{{ __('Issue') }}</span>
                            <select class="vs-rumor-issue-pick__select" id="rumorIssueSelect" aria-labelledby="rumorModalTitle rumorIssuePickLabel"></select>
                        </div>
                    </div>
                    <p id="rumorModalIntro" class="vs-rumor-modal__intro">{{ __('Published in the newspaper after editorial approval. No registration required.') }}</p>
                </header>
                <div class="vs-rumor-placement" id="rumorPlacementWrap">
                    <span class="vs-rumor-placement__label">{{ __('Placement in Gazette') }}</span>
                    <div class="vs-rumor-placement__kinds" id="rumorPlacementKinds" role="radiogroup" aria-label="{{ __('Submission placement') }}"></div>
                    <div class="vs-rumor-corner-pick" id="rumorCornerPicker" hidden>
                        <span class="vs-rumor-corner-pick__label">{{ __('Which column?') }}</span>
                        <div class="vs-rumor-corner-pick__grid" id="rumorCornerPickerGrid"></div>
                    </div>
                </div>
                <form id="rumorForm" class="vs-form-grid vs-form-grid--rumor">
                    <input type="hidden" name="gazette_slot_kind" id="rumorGazetteSlotKind" value="">
                    <input type="hidden" name="gazette_slot_key" id="rumorGazetteSlotKey" value="">
                    <input type="hidden" name="gazette_slot_label" id="rumorGazetteSlotLabelHidden" value="">
                    <input type="hidden" name="gazette_issue_id" id="rumorGazetteIssueId" value="">
                    <div class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Type') }}</span>
                        @include('web.partials.news-type-picker', [
                            'variant' => 'public',
                            'groupId' => 'rumorTypePicker',
                            'inputId' => 'rumorNewsType',
                            'inputName' => 'news_type',
                        ])
                    </div>
                    <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--three vs-rumor-form__span" id="rumorMetaGrid">
                        <label class="vs-rumor-form__meta-cell">
                            {{ __('Location (optional)') }}
                            <input type="text" name="location_badge" maxlength="150" placeholder="{{ __('e.g. Isonburg - Lower City') }}">
                        </label>
                        <label id="rumorRumuzWrap" class="vs-rumor-form__meta-cell" hidden>
                            {{ __('Pen name (column signature)') }}
                            <input type="text" name="rumuz" id="rumorRumuzInput" maxlength="80" placeholder="{{ __('e.g. G.T.') }}">
                        </label>
                        <label id="rumorAuthorWrap" class="vs-rumor-form__meta-cell">
                            {{ __('Reporter name (optional)') }}
                            <input type="text" name="author" id="rumorAuthorInput" maxlength="120" placeholder="{{ __('You may remain anonymous') }}">
                        </label>
                        <div class="vs-rumor-form__meta-cell vs-rumor-form__meta-cell--check">
                            <input type="checkbox" name="anonymous" id="rumorAnonymousInput" class="vs-rumor-form__check-input" tabindex="-1" aria-hidden="true">
                            <button type="button" class="vs-rumor-anon-toggle" id="rumorAnonymousToggle" aria-pressed="false">
                                {{ __('Keep my identity secret') }}
                            </button>
                        </div>
                    </div>
                    <label class="vs-rumor-form__span">
                        <span id="rumorTitleLabel">{{ __('Title') }}</span>
                        <input type="text" name="title" id="rumorTitleInput" required minlength="8" maxlength="255" placeholder="{{ __('e.g. Secret meeting at the Red Inn...') }}">
                    </label>
                    <label class="vs-rumor-form__span">
                        <span id="rumorBodyLabel">{{ __('Rumor / tip text') }}</span>
                        <textarea name="description" id="rumorBodyInput" rows="6" required minlength="30" maxlength="5000" placeholder="{{ __('Write what you saw or heard...') }}"></textarea>
                    </label>
                    <div class="vs-rumor-form__actions vs-rumor-form__span">
                        <button type="submit" class="vs-rumor-submit" id="rumorSubmit">{{ __('Send') }}</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="vs-toast" id="newsToast" role="status" aria-live="polite"></div>
</body>
</html>
