@extends('valiant-shade.layouts/layoutMaster')

@section('title', $selectedGod['name'] ?? 'Tanrılar')

@section('page-style')
    <style>
        .gods-page {
            --gods-gold: #c9a227;
            --gods-muted: #a7a4af;
        }
        .gods-page__sidebar {
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
        }
        .gods-page__eyebrow {
            color: var(--gods-gold);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .gods-page__group-title {
            margin: 1.25rem 0 .5rem;
            color: var(--gods-muted);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .gods-page__link {
            border: 0;
            border-radius: .375rem !important;
            color: inherit;
            text-decoration: none;
        }
        .gods-page__link:hover,
        .gods-page__link.active {
            color: var(--gods-gold);
            background: rgba(201, 162, 39, .12);
        }
        .gods-page__lead {
            max-width: 62ch;
            color: var(--gods-muted);
            line-height: 1.8;
        }
        .gods-page__profile {
            border-color: rgba(201, 162, 39, .24);
        }
        .gods-page__section + .gods-page__section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--bs-border-color);
        }
        .gods-page__source {
            color: var(--gods-gold);
        }
        @media (max-width: 991.98px) {
            .gods-page__sidebar {
                position: static;
                max-height: none;
            }
        }
    </style>
@endsection

@section('content')
    <div class="gods-page">
        <div class="row g-4 align-items-start">
            <aside class="col-12 col-lg-3" aria-label="Tanrı listesi">
                <div class="card gods-page__sidebar">
                    <div class="card-body">
                        <div class="gods-page__eyebrow">Mineria mitolojisi</div>
                        <h2 class="h5 mt-2 mb-4">Tanrılar</h2>

                        @foreach ($godGroups as $group)
                            <div class="gods-page__group-title">{{ $group['label'] }}</div>
                            <nav class="nav flex-column gap-1" aria-label="{{ $group['label'] }}">
                                @forelse ($group['gods'] as $godItem)
                                    <a
                                        class="nav-link gods-page__link {{ ($selectedGod['slug'] ?? null) === $godItem['slug'] ? 'active' : '' }}"
                                        href="{{ route('valiant-shade.gods.show', ['slug' => $godItem['slug']]) }}"
                                        @if (($selectedGod['slug'] ?? null) === $godItem['slug']) aria-current="page" @endif
                                    >
                                        {{ $godItem['name'] }}
                                    </a>
                                @empty
                                    <span class="small text-body-secondary px-3 py-2">İçerik bekleniyor</span>
                                @endforelse
                            </nav>
                        @endforeach
                    </div>
                </div>
            </aside>

            <section class="col-12 col-lg-9">
                @if ($selectedGod)
                    <article class="card gods-page__profile">
                        <div class="card-body p-4 p-xl-5">
                            <div class="gods-page__eyebrow">Tanrı profili</div>
                            <h1 class="display-6 mt-2">{{ $selectedGod['name'] }}</h1>
                            @if (! empty($selectedGod['epithet']))
                                <p class="h5 text-body-secondary">{{ $selectedGod['epithet'] }}</p>
                            @endif
                            <p class="gods-page__lead mt-3">{{ $selectedGod['summary'] }}</p>

                            @foreach ($selectedGod['sections'] as $section)
                                <section class="gods-page__section">
                                    <h2 class="h5">{{ $section['title'] }}</h2>
                                    @foreach ($section['paragraphs'] as $paragraph)
                                        <p class="gods-page__lead">{{ $paragraph }}</p>
                                    @endforeach
                                </section>
                            @endforeach

                            <div class="small text-body-secondary mt-4">
                                Kaynak:
                                <a class="gods-page__source" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">
                                    Mineria Wiki — İnanç
                                </a>
                            </div>
                        </div>
                    </article>
                @else
                    <div class="card gods-page__profile">
                        <div class="card-body p-4 p-xl-5">
                            <div class="gods-page__eyebrow">Rehber</div>
                            <h1 class="display-6 mt-2">Tanrılar</h1>
                            <p class="gods-page__lead mt-3">
                                Büyük ve küçük tanrıları soldaki listeden seçerek profillerini görüntüleyebilirsin.
                            </p>

                            @if (collect($godGroups)->every(fn ($group) => empty($group['gods'])))
                                <div class="alert alert-secondary mt-4 mb-0" role="status">
                                    Tanrı listesi, kaynak bilgileri eklendiğinde burada görünecek.
                                    Kaynak:
                                    <a class="gods-page__source" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">Mineria Wiki — İnanç</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
