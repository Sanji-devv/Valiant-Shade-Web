@php
    $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'valiantshade.com';
    $gazetteUrl = 'https://' . config('gazette.domain');
    $monsterDbUrl = 'https://md.' . $appHost;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'Valiant Shade') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --vs-primary: #7367f0;
            --vs-primary-rgb: 115, 103, 240;
            --vs-success: #28c76f;
            --vs-warning: #ff9f43;
            --vs-info: #00cfe8;
            --vs-danger: #ea5455;
            --vs-bg: #25293c;
            --vs-bg-deep: #1e2235;
            --vs-card: rgba(255, 255, 255, 0.04);
            --vs-border: rgba(255, 255, 255, 0.08);
            --vs-text: #cfd3ec;
            --vs-heading: #f8f9fd;
            --vs-muted: #7983bb;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: 'Public Sans', system-ui, -apple-system, sans-serif;
            color: var(--vs-text);
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, rgba(var(--vs-primary-rgb), 0.22), transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 100%, rgba(0, 207, 232, 0.12), transparent 50%),
                linear-gradient(165deg, var(--vs-bg-deep) 0%, var(--vs-bg) 45%, #2f3349 100%);
        }

        .vs-portal {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            padding: clamp(1rem, 3vw, 2rem);
            max-width: 1400px;
            margin: 0 auto;
        }

        .vs-portal__header {
            text-align: center;
            margin-bottom: clamp(1.25rem, 4vw, 2.5rem);
        }

        .vs-portal__logo {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.5rem;
        }

        .vs-portal__logo-mark {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--vs-primary), #9e95f5);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: #fff;
            font-size: 1.1rem;
            box-shadow: 0 8px 24px rgba(var(--vs-primary-rgb), 0.45);
        }

        .vs-portal__title {
            margin: 0;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            color: var(--vs-heading);
            letter-spacing: -0.02em;
        }

        .vs-portal__subtitle {
            margin: 0.35rem 0 0;
            font-size: clamp(0.875rem, 2.5vw, 1rem);
            color: var(--vs-muted);
        }

        .vs-portal__grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: auto;
            gap: clamp(0.75rem, 2.5vw, 1.25rem);
            align-content: stretch;
        }

        @media (min-width: 640px) {
            .vs-portal__grid {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(2, minmax(200px, 1fr));
                min-height: min(72vh, 720px);
            }
        }

        .vs-tile {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: clamp(1.25rem, 3vw, 1.75rem);
            border-radius: 16px;
            border: 1px solid var(--vs-border);
            background: var(--vs-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            overflow: hidden;
            min-height: 180px;
            text-decoration: none;
            color: inherit;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }

        a.vs-tile:hover {
            transform: translateY(-4px);
            border-color: rgba(var(--vs-primary-rgb), 0.45);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
        }

        a.vs-tile:focus-visible {
            outline: 2px solid var(--vs-primary);
            outline-offset: 3px;
        }

        .vs-tile--disabled {
            cursor: not-allowed;
            opacity: 0.72;
            pointer-events: none;
        }

        .vs-tile__glow {
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            filter: blur(48px);
            opacity: 0.55;
            top: -20px;
            right: -20px;
            pointer-events: none;
        }

        .vs-tile--gazette .vs-tile__glow { background: var(--vs-warning); }
        .vs-tile--monster .vs-tile__glow { background: var(--vs-danger); }
        .vs-tile--map .vs-tile__glow { background: var(--vs-info); }
        .vs-tile--excel .vs-tile__glow { background: var(--vs-success); }

        .vs-tile__icon {
            width: 3rem;
            height: 3rem;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            margin-bottom: auto;
            border: 1px solid var(--vs-border);
            background: rgba(255, 255, 255, 0.06);
        }

        .vs-tile--gazette .vs-tile__icon { color: var(--vs-warning); }
        .vs-tile--monster .vs-tile__icon { color: var(--vs-danger); }
        .vs-tile--map .vs-tile__icon { color: var(--vs-info); }
        .vs-tile--excel .vs-tile__icon { color: var(--vs-success); }

        .vs-tile__label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--vs-muted);
            margin-bottom: 0.25rem;
        }

        .vs-tile__name {
            margin: 0;
            font-size: clamp(1.15rem, 3vw, 1.45rem);
            font-weight: 700;
            color: var(--vs-heading);
            line-height: 1.25;
        }

        .vs-tile__desc {
            margin: 0.5rem 0 0;
            font-size: 0.85rem;
            color: var(--vs-muted);
            line-height: 1.45;
        }

        .vs-tile__badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px dashed rgba(255, 255, 255, 0.2);
            color: var(--vs-muted);
        }

        .vs-portal__footer {
            text-align: center;
            margin-top: clamp(1.5rem, 4vw, 2rem);
            font-size: 0.75rem;
            color: var(--vs-muted);
        }
    </style>
</head>
<body>
    <div class="vs-portal">
        <header class="vs-portal__header">
            <div class="vs-portal__logo">
                <span class="vs-portal__logo-mark" aria-hidden="true">VS</span>
            </div>
            <h1 class="vs-portal__title">{{ config('app.name', 'Valiant Shade') }}</h1>
            <p class="vs-portal__subtitle">{{ __('Choose a portal to continue your adventure.') }}</p>
        </header>

        <main class="vs-portal__grid">
            <a href="{{ $gazetteUrl }}" class="vs-tile vs-tile--gazette" rel="noopener">
                <span class="vs-tile__glow" aria-hidden="true"></span>
                <span class="vs-tile__icon" aria-hidden="true">📰</span>
                <span class="vs-tile__label">{{ __('Public') }}</span>
                <h2 class="vs-tile__name">{{ __('Gazette') }}</h2>
                <p class="vs-tile__desc">{{ __('Guild news, whispers, and chronicles for all visitors.') }}</p>
            </a>

            <a href="{{ $monsterDbUrl }}" class="vs-tile vs-tile--monster" rel="noopener">
                <span class="vs-tile__glow" aria-hidden="true"></span>
                <span class="vs-tile__icon" aria-hidden="true">🐉</span>
                <span class="vs-tile__label">PATHGM</span>
                <h2 class="vs-tile__name">{{ __('Monster Database') }}</h2>
                <p class="vs-tile__desc">{{ __('Bestiary, spells, and Pathfinder reference tools.') }}</p>
            </a>

            <div class="vs-tile vs-tile--map vs-tile--disabled" aria-disabled="true">
                <span class="vs-tile__badge">{{ __('Coming soon') }}</span>
                <span class="vs-tile__glow" aria-hidden="true"></span>
                <span class="vs-tile__icon" aria-hidden="true">🗺️</span>
                <span class="vs-tile__label">{{ __('World') }}</span>
                <h2 class="vs-tile__name">{{ __('Map') }}</h2>
                <p class="vs-tile__desc">{{ __('Interactive kingdom maps and territories.') }}</p>
            </div>

            <div class="vs-tile vs-tile--excel vs-tile--disabled" aria-disabled="true">
                <span class="vs-tile__badge">{{ __('Coming soon') }}</span>
                <span class="vs-tile__glow" aria-hidden="true"></span>
                <span class="vs-tile__icon" aria-hidden="true">📊</span>
                <span class="vs-tile__label">{{ __('Tools') }}</span>
                <h2 class="vs-tile__name">Excel</h2>
                <p class="vs-tile__desc">{{ __('Sheets, exports, and party utilities.') }}</p>
            </div>
        </main>

        <footer class="vs-portal__footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Valiant Shade') }}
        </footer>
    </div>
</body>
</html>
