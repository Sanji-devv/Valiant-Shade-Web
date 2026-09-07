<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mapName ?? 'Valiant Shade Arazisi' }} — {{ config('app.name', 'Valiant Shade') }}</title>
    @vite(['resources/css/valiant-shade/map.css', 'resources/js/valiant-shade/map.js'])
</head>
<body>
    <div class="vs-page{{ $canEdit ? ' vs-page--editor' : '' }}">
        <header class="vs-header">
            <div>
                <h1>{{ $mapName ?? 'Valiant Shade Arazisi' }}</h1>
                @if ($canEdit)
                    <p>Harita editörü — Ctrl+Z geri al · Ctrl+Y yinele</p>
                @else
                    <p>Tiled arazi · Yapılara tıklayın · Yollardaki geçitlere tıklayın · Tekerlek ile zoom</p>
                @endif
            </div>
            <nav class="vs-header-actions">
                @if ($canEdit)
                    <button type="button" data-save class="vs-btn-save" disabled title="Kaydet (Ctrl+S)">Kaydet</button>
                    <div class="vs-history-controls">
                        <button type="button" data-undo class="vs-tool" title="Geri al (Ctrl+Z)" disabled>↶</button>
                        <button type="button" data-redo class="vs-tool" title="Yinele (Ctrl+Y)" disabled>↷</button>
                    </div>
                @endif
                <div class="vs-zoom-controls">
                    <button type="button" id="zoom-out" class="vs-tool" title="Uzaklaş">−</button>
                    <button type="button" id="zoom-fit" class="vs-tool" title="Sığdır">◎</button>
                    <button type="button" id="zoom-in" class="vs-tool" title="Yakınlaş">+</button>
                </div>
                @php
                    $slug = $mapSlug ?? 'valiant-shade';
                    $mapRoutes = [
                        'valiant-shade' => ['show' => 'web.valiant-shade', 'edit' => 'web.valiant-shade.edit'],
                        'isonburg' => ['show' => 'web.isonburg.terrain', 'edit' => 'web.isonburg.terrain.edit'],
                        'isonburg-sehir' => ['show' => 'web.isonburg.city', 'edit' => 'web.isonburg.city.edit'],
                    ];
                    $routes = $mapRoutes[$slug] ?? $mapRoutes['valiant-shade'];
                    $mapShowRoute = $routes['show'];
                    $mapEditRoute = $routes['edit'];
                @endphp
                @if ($canEdit)
                    <a class="vs-link vs-link--active" href="{{ route($mapShowRoute) }}">Normal Görünüm</a>
                    <form method="POST" action="{{ route('web.logout') }}" class="vs-inline-form">
                        @csrf
                        <button type="submit" class="vs-link vs-link--btn">Çıkış</button>
                    </form>
                @else
                    @auth
                        @if (auth()->user()->is_admin)
                            <a class="vs-link" href="{{ route($mapEditRoute) }}">Düzen Modu</a>
                        @endif
                    @else
                        <a class="vs-link" href="{{ route('web.login', ['redirect' => route($mapEditRoute)]) }}">Yönetici Girişi</a>
                    @endauth
                @endif
                <a class="vs-link" href="{{ route('web.home') }}">Ana Sayfa</a>
            </nav>
        </header>

        <div class="vs-body">
            @if ($canEdit)
                <aside id="map-editor" class="vs-editor">
                    <div class="vs-editor-head">
                        <h2>Harita Editörü</h2>
                        <p data-mode-label>Bina taşıma modu</p>
                    </div>

                    <div class="vs-editor-tabs">
                        <button type="button" class="vs-editor-tab vs-editor-tab--active" data-editor-mode="building">
                            <i class="vs-editor-tab-icon icon-base ti tabler-building-community"></i>
                            <span>Binalar</span>
                        </button>
                        <button type="button" class="vs-editor-tab" data-editor-mode="terrain">
                            <i class="vs-editor-tab-icon icon-base ti tabler-brush"></i>
                            <span>Zemin</span>
                        </button>
                        <button type="button" class="vs-editor-tab" data-editor-mode="portal">
                            <i class="vs-editor-tab-icon icon-base ti tabler-arrows-exchange"></i>
                            <span>Geçitler</span>
                        </button>
                    </div>

                    <div class="vs-editor-body">
                        <section class="vs-editor-panel" data-panel="building">
                            <p class="vs-editor-help">Sağ tık ile yapı düzenleyin. Değişiklikler <strong>Kaydet</strong> ile kalıcı olur.</p>

                            <div class="vs-collapse" data-collapse>
                                <button type="button" class="vs-collapse-toggle" data-collapse-toggle>
                                    <span>Bina tipleri</span>
                                    <span class="vs-collapse-icon"><i class="icon-base ti tabler-chevron-down"></i></span>
                                </button>
                                <div class="vs-collapse-body">
                                    <button type="button" class="vs-btn-flat" data-open-building-modal>
                                        <i class="icon-base ti tabler-plus"></i>
                                        Yeni bina tipi
                                    </button>
                                    <div data-building-type-list class="vs-type-grid"></div>
                                </div>
                            </div>

                            <div class="vs-collapse vs-collapse--closed" data-collapse>
                                <button type="button" class="vs-collapse-toggle" data-collapse-toggle>
                                    <span>Haritadaki yapılar</span>
                                    <span class="vs-collapse-icon"><i class="icon-base ti tabler-chevron-down"></i></span>
                                </button>
                                <div class="vs-collapse-body">
                                    <div data-building-list class="vs-building-list"></div>
                                    <button type="button" data-delete-building class="vs-btn vs-btn--danger">Seçili Binayı Kaldır</button>
                                </div>
                            </div>
                        </section>

                        <section class="vs-editor-panel" data-panel="terrain" hidden>
                            <div class="vs-selected-tile">
                                <span data-selected-tile-preview class="vs-palette-swatch vs-palette-swatch--lg"></span>
                                <div>
                                    <small>Seçili zemin</small>
                                    <strong data-selected-tile-name>—</strong>
                                </div>
                            </div>

                            <h3>Zemin paleti</h3>
                            <div data-tile-palette class="vs-palette-grid"></div>

                            <form data-tile-color-form class="vs-form-block" data-tile-type-id="">
                                <h3>Renk düzenle</h3>
                                <div class="vs-color-row">
                                    <label>Dolgu <input type="color" name="fill_color" value="#5a9448"></label>
                                    <label>Kenar <input type="color" name="stroke_color" value="#4a7a3a"></label>
                                </div>
                                <button type="submit" class="vs-btn">Renkleri Kaydet</button>
                            </form>

                            <form data-new-tile-form class="vs-form-block">
                                <h3>Yeni zemin tipi</h3>
                                <input type="text" name="name" placeholder="Örn. Kül rengi toprak" required maxlength="64">
                                <div class="vs-color-row">
                                    <label>Dolgu <input type="color" name="fill_color" value="#6b8f6b"></label>
                                    <label>Kenar <input type="color" name="stroke_color" value="#4a6b4a"></label>
                                </div>
                                <button type="submit" class="vs-btn vs-btn--ghost">+ Tip Ekle</button>
                            </form>
                        </section>

                        <section class="vs-editor-panel" data-panel="portal" hidden>
                            <p class="vs-editor-help">Kavşak ve yol uçlarına geçit yerleştirin. Ziyaretçiler tıklayınca hedef haritaya gider.</p>

                            <form data-portal-form class="vs-form-block">
                                <h3>Geçit</h3>
                                <input type="text" name="name" placeholder="Geçit adı (örn. Isonburg Yolu)" required maxlength="64">
                                <label class="vs-field-label">
                                    Hedef harita
                                    <select name="target_map_slug" required data-portal-targets></select>
                                </label>
                                <input type="text" name="description" placeholder="Açıklama (isteğe bağlı)" maxlength="500">
                                <div class="vs-size-row">
                                    <label>Genişlik (karo) <input type="number" name="width_tiles" min="1" max="12" value="2" required></label>
                                    <label>Yükseklik (karo) <input type="number" name="height_tiles" min="1" max="12" value="2" required></label>
                                </div>
                                <div class="vs-portal-form-actions">
                                    <button type="submit" class="vs-btn" data-portal-submit>Yeni geçit yerleştir</button>
                                    <button type="button" class="vs-btn vs-btn--ghost" data-portal-update hidden>Güncelle</button>
                                    <button type="button" class="vs-btn vs-btn--danger" data-portal-delete hidden>Kaldır</button>
                                </div>
                            </form>

                            <div class="vs-collapse" data-collapse>
                                <button type="button" class="vs-collapse-toggle" data-collapse-toggle>
                                    <span>Haritadaki geçitler</span>
                                    <span class="vs-collapse-icon"><i class="icon-base ti tabler-chevron-down"></i></span>
                                </button>
                                <div class="vs-collapse-body">
                                    <div data-portal-list class="vs-portal-list"></div>
                                </div>
                            </div>
                        </section>

                        <section class="vs-editor-panel vs-editor-panel--tools">
                            <div class="vs-collapse" data-collapse>
                                <button type="button" class="vs-collapse-toggle" data-collapse-toggle>
                                    <span>Harita boyutu</span>
                                    <span class="vs-collapse-icon"><i class="icon-base ti tabler-chevron-down"></i></span>
                                </button>
                                <div class="vs-collapse-body">
                                    <div class="vs-tool-group">
                                        <button type="button" data-expand-width class="vs-btn vs-btn--ghost">+5 Genişlik</button>
                                        <button type="button" data-expand-height class="vs-btn vs-btn--ghost">+5 Yükseklik</button>
                                        <button type="button" data-expand-both class="vs-btn vs-btn--ghost">+5 Her iki eksen</button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </aside>
            @endif

            <div class="vs-map-shell">
                <div
                    id="valiant-shade-viewport"
                    class="vs-viewport--buildings"
                    data-can-edit="{{ $canEdit ? '1' : '0' }}"
                    data-map-slug="{{ $mapSlug }}"
                >
                    <div id="valiant-shade-world" class="vs-world"></div>
                </div>

                <p id="map-status" class="vs-status">
                    @if ($canEdit)
                        Bina modu — sürükleyerek taşıyın
                    @else
                        Tekerlek veya +/- ile zoom · Yapılara tıklayın
                    @endif
                </p>

                <aside id="location-panel" class="vs-panel" hidden>
                    <div class="vs-panel-header">
                        <span id="panel-icon" class="vs-panel-icon"></span>
                        <div class="vs-panel-title-wrap">
                            <h2 id="panel-title" class="vs-panel-title"></h2>
                            <p id="panel-meta" class="vs-panel-subtitle"></p>
                        </div>
                        <button id="panel-close" type="button" class="vs-panel-close" aria-label="Kapat">&times;</button>
                    </div>
                    <p id="panel-description" class="vs-panel-description"></p>
                </aside>
            </div>
        </div>
    </div>
</body>
</html>
