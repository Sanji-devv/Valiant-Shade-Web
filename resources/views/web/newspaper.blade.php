@php
    $mainSiteUrl = rtrim((string) config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Valiant Shade Gazetesi — {{ config('app.name', 'Valiant Shade') }}</title>
    @vite(['resources/css/valiant-shade/newspaper.css', 'resources/js/valiant-shade/newspaper.js'], 'static/valiant-shade/build')
</head>
<body class="vs-newspaper-page">
    <header class="vs-newspaper-header">
        <div>
            <h1>Valiant Shade Gazetesi</h1>
            <p>Kraliyet ve lonca haberleri, söylentiler ve yerel duyurular — giriş yapmadan okuyun.</p>
        </div>
        <nav class="vs-newspaper-nav">
            <a href="{{ $mainSiteUrl }}">Ana Sayfa</a>
            <span class="vs-newspaper-nav__soon" tabindex="0" aria-disabled="true">
                Lonca Arazisi
                <span class="vs-newspaper-nav__tooltip" role="tooltip">{{ __('Coming soon') }}</span>
            </span>
            <button type="button" class="primary" id="openRumorModal">Söylenti Gönder</button>
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
            <div class="vs-filter-group" id="newsFilterGroup"></div>
            <input type="search" class="vs-search" id="newsSearchInput" placeholder="Haber, söylenti veya kahraman ara…">
        </section>

        <section class="vs-news-grid" id="newsGrid" aria-live="polite"></section>

        <nav class="vs-pagination" aria-label="Gazete sayfaları">
            <button type="button" class="vs-page-nav-btn" id="newsPrevPage">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                <span>Önceki Sayfa</span>
            </button>
            <span class="vs-page-sheet" id="newsPageSheet">Gazete Sayfası 1 / 1</span>
            <button type="button" class="vs-page-nav-btn" id="newsNextPage">
                <span>Sonraki Sayfa</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </nav>
    </main>

    <div class="vs-modal-backdrop" id="newsDetailModal" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
        <article class="vs-modal">
            <button type="button" class="vs-modal__close" id="newsModalClose" aria-label="Kapat">×</button>
            <div class="vs-modal__hero" id="newsModalHero">
                <div class="vs-modal__hero-overlay"></div>
            </div>
            <div class="vs-modal__body">
                <h2 id="newsModalTitle"></h2>
                <p id="newsModalMeta" style="color:#94a3b8;font-size:.9rem"></p>
                <blockquote id="newsModalQuote" style="display:none;border-left:3px solid #f59e0b;padding-left:.75rem;color:#e2e8f0;font-style:italic"></blockquote>
                <div id="newsModalStory" style="line-height:1.7;color:#cbd5e1"></div>
            </div>
        </article>
    </div>

    <div class="vs-modal-backdrop" id="rumorModal" role="dialog" aria-modal="true" aria-labelledby="rumorModalTitle">
        <section class="vs-modal" style="width:min(560px,100%)">
            <button type="button" class="vs-modal__close" id="rumorModalClose" aria-label="Kapat">×</button>
            <div class="vs-modal__body">
                <h2 id="rumorModalTitle">Söylenti Gönder</h2>
                <p style="color:#94a3b8;font-size:.9rem">Editör onayından sonra gazetede yayınlanır. Kayıt gerekmez.</p>
                <form id="rumorForm" class="vs-form-grid">
                    <label>
                        Başlık
                        <input type="text" name="title" required minlength="8" maxlength="255" placeholder="Örn. Kızıl Han'da gizli buluşma…">
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
                    <label>
                        Muhabir adı (isteğe bağlı)
                        <input type="text" name="author" maxlength="120" placeholder="Anonim kalabilirsiniz">
                    </label>
                    <label>
                        <span style="display:flex;gap:.5rem;align-items:center">
                            <input type="checkbox" name="anonymous" checked>
                            Kimliğimi gizli tut
                        </span>
                    </label>
                    <label>
                        Söylenti / ihbar metni
                        <textarea name="description" rows="5" required minlength="30" maxlength="5000" placeholder="Gördüklerinizi veya duyduklarınızı yazın…"></textarea>
                    </label>
                    <button type="submit" class="primary" id="rumorSubmit" style="justify-self:start;border:0;border-radius:10px;padding:.65rem 1rem">Gönder</button>
                </form>
            </div>
        </section>
    </div>

    <div class="vs-toast" id="newsToast" role="status" aria-live="polite"></div>
</body>
</html>
