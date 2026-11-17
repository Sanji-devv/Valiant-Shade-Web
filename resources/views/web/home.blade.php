@php
    use App\Support\PublicSiteUrls;

    $gazetteUrl = PublicSiteUrls::gazette();
    $mapUrl = PublicSiteUrls::map('/feldan');
    $monsterDbUrl = PublicSiteUrls::monsterDatabase();

    $imgGazette = asset('static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg');
    $imgMonster = asset('static/valiant-shade/assets/img/news/guild_dragon_quest.jpg');
    $imgMap = asset('static/valiant-shade/world/feldan-kralligi-preview.webp');
    $imgRecords = asset('static/valiant-shade/assets/img/news/guild_treasury_vault.jpg');
    $imgMineria = asset('static/valiant-shade/assets/img/news/guild_mystic_forest.jpg');
    $imgRules = asset('static/valiant-shade/assets/img/news/guild_arcane_academy.jpg');
    $imgGods = asset('static/valiant-shade/assets/img/news/guild_celestial_sanctuary.jpg');

    $mineriaUrl = 'https://mineria.fandom.com/tr/wiki/Mineria_Wiki';
    $rulesUrl = 'https://www.d20pfsrd.com/';

    $monsterTitleParts = explode(' ', (string) __('Monster Database'), 2);

    $pageTitle = (string) config('seo.home.title');
    $pageDescription = (string) config('seo.home.description');
    $canonicalUrl = PublicSiteUrls::main('/');
    $ogImage = asset((string) config('seo.home.og_image'));
    $siteName = (string) config('seo.site_name');
    $logoUrl = url(asset((string) config('seo.home.og_image')));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1a1520">
    <title>{{ $pageTitle }}</title>
    @include('web.partials.head-favicon')
    @include('web.partials.head-public-seo', [
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => config('seo.home.keywords'),
        'canonical' => $canonicalUrl,
        'ogImage' => $ogImage,
        'ogImageAlt' => config('seo.site_name'),
    ])
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $canonicalUrl.'#organization',
                    'name' => $siteName,
                    'url' => $canonicalUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $logoUrl,
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $canonicalUrl.'#website',
                    'name' => $siteName,
                    'url' => $canonicalUrl,
                    'description' => $pageDescription,
                    'inLanguage' => 'tr-TR',
                    'publisher' => ['@id' => $canonicalUrl.'#organization'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Public+Sans:wght@500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        @media (max-width: 639px) {
            html, body {
                height: auto;
                min-height: 100dvh;
                overflow-x: hidden;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        body {
            font-family: 'Public Sans', system-ui, sans-serif;
            background: #0f0d14;
        }

        .vs-quad {
            display: grid;
            width: 100%;
            height: 100vh;
            height: 100dvh;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            grid-template-rows: repeat(3, minmax(0, 1fr));
            gap: 3px;
            background: #1a1520;
        }

        @media (max-width: 639px) {
            .vs-quad {
                grid-template-columns: 1fr;
                grid-template-rows: none;
                grid-auto-rows: minmax(28vh, auto);
                overflow: visible;
                height: auto;
                min-height: 100dvh;
            }
        }

        .vs-quad__cell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 0;
            background-color: #1e1a24;
            background-size: cover;
            background-position: center;
            text-decoration: none;
            color: #fff;
            overflow: hidden;
            isolation: isolate;
        }

        @media (max-width: 639px) {
            .vs-quad__cell { min-height: 28vh; }
        }

        .vs-quad__cell::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(8, 6, 12, 0.35) 0%,
                rgba(8, 6, 12, 0.55) 45%,
                rgba(8, 6, 12, 0.82) 100%
            );
            z-index: 1;
            transition: opacity 0.35s ease;
        }

        a.vs-quad__cell:hover::before {
            opacity: 0.75;
        }

        a.vs-quad__cell:hover .vs-quad__title {
            transform: scale(1.03);
            letter-spacing: 0.06em;
        }

        a.vs-quad__cell:focus-visible {
            outline: 3px solid #c9a227;
            outline-offset: -6px;
            z-index: 2;
        }

        .vs-quad__title {
            position: relative;
            z-index: 2;
            font-family: 'Cinzel', 'Times New Roman', serif;
            font-weight: 700;
            font-size: clamp(1.75rem, 5.5vw, 3.75rem);
            text-align: center;
            line-height: 1.15;
            padding: 1rem 1.25rem;
            text-shadow:
                0 0 40px rgba(0, 0, 0, 0.9),
                0 4px 24px rgba(0, 0, 0, 0.85),
                0 2px 4px rgba(0, 0, 0, 1);
            letter-spacing: 0.04em;
            transition: transform 0.35s ease, letter-spacing 0.35s ease;
        }

        .vs-quad__cell--gazette .vs-quad__title { color: #f5e6c8; }
        .vs-quad__cell--map .vs-quad__title { color: #b8d4e8; }
        .vs-quad__cell--mineria .vs-quad__title { color: #c8e8f5; }
        .vs-quad__cell--rules .vs-quad__title { color: #e8dcc8; }
        .vs-quad__cell--monster .vs-quad__title { color: #ffd4d4; }
        .vs-quad__cell--records .vs-quad__title { color: #d4e8d0; }
        .vs-quad__cell--gods .vs-quad__title { color: #e7d0ff; }

        .vs-quad__title--long {
            font-size: clamp(1.35rem, 4.2vw, 2.65rem);
            line-height: 1.2;
        }

        .vs-quad__title--stacked {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.1em;
            font-size: clamp(1.25rem, 3.6vw, 2.35rem);
            line-height: 1.12;
        }

        .vs-quad__cell--monster {
            background-color: #1c1410;
        }

        .vs-quad__cell--monster .vs-quad__bg {
            background-size: cover;
            background-position: 50% 16%;
            background-repeat: no-repeat;
            transform-origin: 50% 16%;
        }

        @media (max-width: 639px) {
            .vs-quad__cell--monster .vs-quad__bg {
                background-position: 52% 20%;
                transform-origin: 52% 20%;
            }
        }

        /* Disabled quadrants */
        .vs-quad__cell--locked {
            cursor: not-allowed;
            pointer-events: none;
        }

        .vs-quad__cell--locked::after,
        .vs-quad__cell--dimmed::after {
            content: '';
            position: absolute;
            inset: 10px;
            border: 3px dashed rgba(201, 162, 39, 0.55);
            border-radius: 4px;
            z-index: 3;
            pointer-events: none;
            box-shadow: inset 0 0 60px rgba(0, 0, 0, 0.65);
        }

        .vs-quad__cell--locked::before,
        .vs-quad__cell--dimmed::before {
            background: linear-gradient(
                180deg,
                rgba(12, 10, 16, 0.72) 0%,
                rgba(12, 10, 16, 0.88) 100%
            );
        }

        .vs-quad__cell--locked .vs-quad__bg,
        .vs-quad__cell--dimmed .vs-quad__bg {
            filter: grayscale(0.85) brightness(0.45) contrast(0.9);
        }

        .vs-quad__lock-overlay {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 12px,
                rgba(0, 0, 0, 0.12) 12px,
                rgba(0, 0, 0, 0.12) 24px
            );
        }

        .vs-quad__bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            z-index: 0;
            transition: transform 0.6s ease, filter 0.4s ease;
        }

        a.vs-quad__cell:hover .vs-quad__bg {
            transform: scale(1.06);
        }

        .vs-quad__badge {
            position: absolute;
            top: clamp(0.75rem, 2.5vw, 1.25rem);
            left: 50%;
            transform: translateX(-50%);
            z-index: 4;
            font-family: 'Public Sans', sans-serif;
            font-size: clamp(0.65rem, 1.8vw, 0.8rem);
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            padding: 0.45rem 1rem;
            border-radius: 2px;
            background: rgba(20, 16, 10, 0.92);
            border: 1px solid rgba(201, 162, 39, 0.65);
            color: #e8c547;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .vs-quad__cell--locked .vs-quad__title,
        .vs-quad__cell--dimmed .vs-quad__title {
            opacity: 0.72;
            font-size: clamp(1.5rem, 4.5vw, 3rem);
        }

        .vs-quad__corner {
            position: absolute;
            width: 48px;
            height: 48px;
            z-index: 2;
            opacity: 0.35;
            border-color: rgba(201, 162, 39, 0.5);
            border-style: solid;
            pointer-events: none;
        }
        .vs-quad__corner--tl { top: 12px; left: 12px; border-width: 2px 0 0 2px; }
        .vs-quad__corner--tr { top: 12px; right: 12px; border-width: 2px 2px 0 0; }
        .vs-quad__corner--bl { bottom: 12px; left: 12px; border-width: 0 0 2px 2px; }
        .vs-quad__corner--br { bottom: 12px; right: 12px; border-width: 0 2px 2px 0; }

        .vs-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <h1 class="vs-sr-only">{{ $pageTitle }}</h1>
    <p class="vs-sr-only">{{ $pageDescription }}</p>
    <main class="vs-quad" role="navigation" aria-label="{{ __('Portals') }}">
        <a href="{{ $gazetteUrl }}" class="vs-quad__cell vs-quad__cell--gazette" rel="noopener">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgGazette }}');" aria-hidden="true"></div>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h2 class="vs-quad__title">{{ __('Gazette') }}</h2>
        </a>

        <a href="{{ $mapUrl }}" class="vs-quad__cell vs-quad__cell--map" rel="noopener">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgMap }}');" aria-hidden="true"></div>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h2 class="vs-quad__title">{{ __('Map') }}</h2>
        </a>

        <a href="{{ $mineriaUrl }}" class="vs-quad__cell vs-quad__cell--mineria" rel="noopener noreferrer" target="_blank">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgMineria }}');" aria-hidden="true"></div>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h2 class="vs-quad__title vs-quad__title--long">{{ __('Mineria Fandom') }}</h2>
        </a>

        <a href="{{ $rulesUrl }}" class="vs-quad__cell vs-quad__cell--rules" rel="noopener noreferrer" target="_blank">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgRules }}');" aria-hidden="true"></div>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h2 class="vs-quad__title">{{ __('Rulebook') }}</h2>
        </a>

        <a href="{{ $monsterDbUrl }}" class="vs-quad__cell vs-quad__cell--monster" rel="noopener">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgMonster }}');" aria-hidden="true"></div>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h2 class="vs-quad__title vs-quad__title--stacked" aria-label="{{ __('Monster Database') }}">
                <span>{{ $monsterTitleParts[0] ?? __('Monster Database') }}</span>
                @if (! empty($monsterTitleParts[1]))
                    <span>{{ $monsterTitleParts[1] }}</span>
                @endif
            </h2>
        </a>

        <a href="/valiant-shade" class="vs-quad__cell vs-quad__cell--records vs-quad__cell--dimmed">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgRecords }}');" aria-hidden="true"></div>
            <div class="vs-quad__lock-overlay" aria-hidden="true"></div>
            <span class="vs-quad__badge">{{ __('BETA') }}</span>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h2 class="vs-quad__title">{{ __('Guild Records') }}</h2>
        </a>

        <a href="{{ route('valiant-shade.gods.index') }}" class="vs-quad__cell vs-quad__cell--gods">
            <div class="vs-quad__bg" style="background-image: url('{{ $imgGods }}');" aria-hidden="true"></div>
            <span class="vs-quad__corner vs-quad__corner--tl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--tr" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--bl" aria-hidden="true"></span>
            <span class="vs-quad__corner vs-quad__corner--br" aria-hidden="true"></span>
            <h1 class="vs-quad__title">{{ __('Tanrılar') }}</h1>
        </a>

        <div class="vs-quad__cell vs-quad__cell--locked" aria-hidden="true"></div>
        <div class="vs-quad__cell vs-quad__cell--locked" aria-hidden="true"></div>
    </main>
</body>
</html>
