@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Lonca Haberleri & Duyuru Yönetimi - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  .news-card {
    transition: all 0.25s ease;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }
  .news-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25) !important;
    border-color: rgba(255, 255, 255, 0.2);
  }
  .news-cover-thumb {
    height: 180px;
    background-size: cover;
    background-position: center;
    position: relative;
    border-top-left-radius: var(--bs-border-radius);
    border-top-right-radius: var(--bs-border-radius);
  }
  .news-cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.4) 60%, transparent 100%);
  }
  .preset-card {
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    border-radius: 8px;
    overflow: hidden;
  }
  .preset-card:hover {
    border-color: var(--bs-primary);
    transform: scale(1.02);
  }
  .preset-card.active {
    border-color: var(--bs-primary);
    box-shadow: 0 0 12px rgba(var(--bs-primary-rgb), 0.5);
  }
  .preset-thumb {
    height: 80px;
    background-size: cover;
    background-position: center;
  }
  .live-preview-box {
    background-color: #0f172a;
    border-radius: var(--bs-border-radius);
    min-height: 280px;
    position: relative;
    overflow: hidden;
  }
  .preview-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transition: all 0.5s ease;
  }
  .preview-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.82) 50%, rgba(15, 23, 42, 0.45) 100%),
                linear-gradient(to top, rgba(15, 23, 42, 0.96) 0%, rgba(15, 23, 42, 0.3) 55%, transparent 100%);
  }
  .preview-content {
    position: relative;
    z-index: 2;
    padding: 1.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .preview-stat-card {
    background: rgba(15, 23, 42, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(6px);
    border-radius: 6px;
    padding: 0.4rem 0.6rem;
  }
  .guild-news-tag-badge {
    background: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff !important;
    font-size: 0.76rem;
    font-weight: 600;
    letter-spacing: 0.2px;
    padding: 0.28rem 0.65rem !important;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
  }
  .guild-news-tag-badge .guild-news-tag-text {
    color: #ffffff !important;
    font-weight: 600;
  }
  #guildNewsModal .modal-dialog {
    max-height: calc(100vh - 3.5rem);
    margin: 1.75rem auto;
  }
  #guildNewsModal form.modal-content {
    max-height: calc(100vh - 3.5rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  #guildNewsModal .modal-body {
    overflow-y: auto !important;
    overflow-x: hidden;
    scrollbar-width: thin;
    padding: 1.5rem;
  }
  #guildNewsModal .modal-body::-webkit-scrollbar {
    width: 6px;
  }
  #guildNewsModal .modal-body::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.45);
    border-radius: 4px;
  }
  #guildNewsModal .modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.75);
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'
], 'static/valiant-shade/build')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- Header & Actions -->
  <div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
      <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-news fs-2 text-primary"></i>
        <span>{{ __('Lonca Haberleri & Duyuru Yönetimi') }}</span>
      </h4>
      <p class="text-body-secondary mb-0">
        {{ __('Ana paneldeki kayan görsel haberleri, sefer zaferlerini, lonca hazinesi duyurularını ve akın çağrılarını düzenleyin.') }}
      </p>
    </div>
    <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
      <a href="{{ route('valiant-shade.dashboard-analytics') }}" target="_blank" class="btn btn-label-secondary d-inline-flex align-items-center gap-1">
        <i class="icon-base ti tabler-external-link"></i>
        <span>{{ __('Paneli Aç') }}</span>
      </a>
      <button type="button" class="btn btn-label-warning d-inline-flex align-items-center gap-1" id="btnResetDefaults">
        <i class="icon-base ti tabler-rotate-clockwise"></i>
        <span>{{ __('Varsayılanları Yükle') }}</span>
      </button>
      <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnOpenCreateModal">
        <i class="icon-base ti tabler-plus"></i>
        <span>{{ __('Yeni Haber Ekle') }}</span>
      </button>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible d-flex align-items-center mb-4" role="alert">
    <i class="icon-base ti tabler-check fs-4 me-2"></i>
    <div class="flex-grow-1">{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger alert-dismissible mb-4" role="alert">
    <div class="fw-bold mb-1"><i class="icon-base ti tabler-alert-circle me-1"></i> Formda hatalar oluştu:</div>
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <!-- Quick Stats Counter -->
  <div class="row g-4 mb-4">
    <div class="col-sm-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-primary rounded p-2">
            <i class="icon-base ti tabler-slideshow fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">{{ $news->count() }}</h5>
            <small class="text-body-secondary">{{ __('Toplam Haber / Slayt') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-success rounded p-2">
            <i class="icon-base ti tabler-eye fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-success">{{ $news->where('is_active', true)->count() }}</h5>
            <small class="text-body-secondary">{{ __('Yayında Olanlar (Aktif)') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-secondary rounded p-2">
            <i class="icon-base ti tabler-eye-off fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-muted">{{ $news->where('is_active', false)->count() }}</h5>
            <small class="text-body-secondary">{{ __('Taslak / Gizli') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- News Items Grid -->
  <div class="row g-4" id="newsCardsContainer">
    @forelse($news as $item)
    <div class="col-lg-4 col-md-6" id="news-card-{{ $item->id }}">
      <div class="card news-card h-100 shadow-sm">
        <!-- Cover Image with Badges -->
        <div class="news-cover-thumb" style="background-image: url('{{ $item->image_asset }}');">
          <div class="news-cover-overlay"></div>
          <div class="position-absolute top-0 start-0 p-3 d-flex align-items-center gap-2 flex-wrap" style="z-index: 2;">
            <span class="badge bg-{{ $item->badge_color ?? 'primary' }} text-white fw-bold">
              <i class="{{ $item->category_icon ?: 'icon-base ti tabler-trophy' }} me-1"></i> {{ $item->category }}
            </span>
            @if($item->tag)
            <span class="guild-news-tag-badge">
              <i class="{{ $item->tag_icon ?: 'icon-base ti tabler-sparkles' }} me-1"></i>
              <span class="guild-news-tag-text">{{ $item->tag }}</span>
            </span>
            @endif
          </div>
          <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-50">
              #{{ $item->order }}
            </span>
          </div>
          @if($item->date_text)
          <div class="position-absolute bottom-0 start-0 p-3" style="z-index: 2;">
            <small class="text-white-50"><i class="icon-base ti tabler-clock me-1"></i> {{ $item->date_text }}</small>
          </div>
          @endif
        </div>

        <!-- Card Body -->
        <div class="card-body d-flex flex-column justify-content-between">
          <div>
            <h5 class="card-title fw-bold text-truncate-2 mb-2" title="{{ $item->title }}" style="line-height: 1.35;">
              {{ $item->title }}
            </h5>
            <p class="card-text text-body-secondary small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">
              {{ $item->description }}
            </p>

            <!-- Stats Preview -->
            @if(!empty($item->stats) && is_array($item->stats))
            <div class="row g-2 mb-3">
              @foreach($item->stats as $st)
              <div class="col-6">
                <div class="p-2 rounded bg-label-secondary border border-secondary-subtle">
                  <div class="{{ $st['color'] ?? 'text-warning' }} fw-bold small">{{ $st['value'] ?? '-' }}</div>
                  <div class="text-muted" style="font-size: 0.7rem;">{{ $st['label'] ?? '' }}</div>
                </div>
              </div>
              @endforeach
            </div>
            @endif
          </div>

          <!-- Card Footer Controls -->
          <div class="pt-3 border-top d-flex align-items-center justify-content-between">
            <div class="form-check form-switch mb-0" title="{{ __('Yayında / Taslak') }}">
              <input class="form-check-input btn-toggle-active" type="checkbox" id="switch-{{ $item->id }}" data-id="{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
              <label class="form-check-label small fw-medium {{ $item->is_active ? 'text-success' : 'text-muted' }}" id="status-label-{{ $item->id }}" for="switch-{{ $item->id }}">
                {{ $item->is_active ? __('Yayında') : __('Taslak') }}
              </label>
            </div>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-icon btn-label-primary btn-edit-news" data-news='@json($item)' title="{{ __('Düzenle') }}">
                <i class="icon-base ti tabler-edit"></i>
              </button>
              <button type="button" class="btn btn-sm btn-icon btn-label-danger btn-delete-news" data-id="{{ $item->id }}" data-title="{{ $item->title }}" title="{{ __('Sil') }}">
                <i class="icon-base ti tabler-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
      <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3">
        <i class="icon-base ti tabler-slideshow-off fs-1"></i>
      </div>
      <h5>{{ __('Henüz bir lonca haberi eklenmemiş.') }}</h5>
      <p class="text-body-secondary mb-3">{{ __('Panelde gösterilecek yeni bir sefer zaferi veya lonca bülteni ekleyin.') }}</p>
      <button type="button" class="btn btn-primary" id="btnEmptyCreate">
        <i class="icon-base ti tabler-plus me-1"></i> {{ __('İlk Haberi Ekle') }}
      </button>
    </div>
    @endforelse
  </div>

</div>

<!-- Modal: Add / Edit News -->
<div class="modal fade" id="guildNewsModal" tabindex="-1" aria-labelledby="guildNewsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form id="guildNewsForm" class="modal-content" method="POST" action="{{ route('valiant-shade.pages-guild-news.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" id="news_id" name="news_id" value="">
      <input type="hidden" id="form_method" name="_method" value="POST">

      <div class="modal-header bg-label-primary border-bottom">
        <h5 class="modal-title fw-bold" id="guildNewsModalTitle">
          <i class="icon-base ti tabler-plus me-1"></i> <span>{{ __('Yeni Haber / Bülten Ekle') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
          <div class="row g-4">

            <!-- Left Form Column -->
            <div class="col-lg-7">
              <h6 class="fw-bold text-uppercase text-primary mb-3">
                <i class="icon-base ti tabler-info-circle me-1"></i> 1. {{ __('Haber & Başlık Bilgileri') }}
              </h6>

              <!-- Title -->
              <div class="mb-3">
                <label class="form-label fw-bold" for="input_title">{{ __('Haber Başlığı') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="input_title" name="title" required placeholder="örn: Ejderha Zirvesi ve Antik Harabeler Fethi Tamamlandı!">
              </div>

              <!-- Category & Badge Color & Icon -->
              <div class="row g-2 mb-3">
                <div class="col-md-5">
                  <label class="form-label fw-bold" for="input_category">{{ __('Kategori / Rozet') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="input_category" name="category" required placeholder="örn: Sefer Zaferi">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold" for="input_badge_color">{{ __('Rozet Rengi') }}</label>
                  <select class="form-select" id="input_badge_color" name="badge_color">
                    <option value="warning" selected>Sarı (Warning)</option>
                    <option value="success">Yeşil (Success)</option>
                    <option value="danger">Kırmızı (Danger)</option>
                    <option value="primary">Mavi (Primary)</option>
                    <option value="info">Açık Mavi (Info)</option>
                    <option value="secondary">Gri (Secondary)</option>
                    <option value="dark">Koyu (Dark)</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold" for="input_category_icon">{{ __('İkon') }}</label>
                  <select class="form-select" id="input_category_icon" name="category_icon">
                    <option value="icon-base ti tabler-trophy">Kupa / Zafer</option>
                    <option value="icon-base ti tabler-coins">Hazine / Altın</option>
                    <option value="icon-base ti tabler-flame">Alev / Tehlike</option>
                    <option value="icon-base ti tabler-swords">Kılıçlar / Sefer</option>
                    <option value="icon-base ti tabler-shield-check">Kalkan / Güvenlik</option>
                    <option value="icon-base ti tabler-building-vault">Mahzen / Kasa</option>
                    <option value="icon-base ti tabler-sparkles">Parıltı / Kalıntı</option>
                    <option value="icon-base ti tabler-speakerphone">Duyuru</option>
                    <option value="icon-base ti tabler-scroll">Parşömen</option>
                    <option value="icon-base ti tabler-users-group">Grup / Takım</option>
                    <option value="icon-base ti tabler-candle">Mum / Anma</option>
                    <option value="icon-base ti tabler-coffin">Tabut / Cenaze</option>
                    <option value="icon-base ti tabler-cross">Anıt / Mezar</option>
                  </select>
                </div>
              </div>

              <!-- Tag & Date -->
              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_tag">{{ __('Alt Etiket (Opsiyonel)') }}</label>
                  <input type="text" class="form-control" id="input_tag" name="tag" placeholder="örn: Son Keşif Raporu">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_date_text">{{ __('Tarih / Süre Metni') }}</label>
                  <input type="text" class="form-control" id="input_date_text" name="date_text" placeholder="örn: Bugün, 04:30 veya Aylık Dönem">
                </div>
              </div>

              <!-- Description -->
              <div class="mb-4">
                <label class="form-label fw-bold" for="input_description">{{ __('Haber Açıklaması') }} <span class="text-danger">*</span></label>
                <textarea class="form-control" id="input_description" name="description" rows="3" required placeholder="Haberin detaylarını veya lonca bültenini yazın..."></textarea>
              </div>

              <!-- Cover Image Selection -->
              <h6 class="fw-bold text-uppercase text-primary mb-3">
                <i class="icon-base ti tabler-photo me-1"></i> 2. {{ __('Görsel Seçimi') }}
              </h6>

              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <label class="form-label fw-bold mb-0">{{ __('Hazır Fantastik Görsellerden Seçin') }}</label>
                  <span class="badge bg-label-primary rounded-pill">{{ count($presets) }} {{ __('Seçenek') }}</span>
                </div>
                <div class="row g-2 border rounded p-2 bg-label-secondary bg-opacity-25" id="presetsContainer" style="max-height: 310px; overflow-y: auto;">
                  @foreach($presets as $idx => $preset)
                  <div class="col-md-3 col-sm-4 col-6">
                    <div class="preset-card h-100 {{ $idx === 0 ? 'active' : '' }}" data-path="{{ $preset['path'] }}" data-url="{{ $preset['url'] }}" title="{{ $preset['name'] }}">
                      <div class="preset-thumb" style="background-image: url('{{ $preset['url'] }}');"></div>
                      <div class="p-1 text-center bg-dark text-white text-truncate" style="font-size: 0.68rem;">
                        {{ $preset['name'] }}
                      </div>
                    </div>
                  </div>
                  @endforeach
                </div>
                <input type="hidden" id="input_image_preset" name="image_preset" value="{{ $presets[0]['path'] }}">
              </div>

              <div class="mb-4">
                <label class="form-label fw-bold" for="input_custom_image">{{ __('Veya Kendi Görselinizi Yükleyin (JPG, PNG, WebP - Max 5MB)') }}</label>
                <input type="file" class="form-control" id="input_custom_image" name="custom_image" accept="image/jpeg,image/png,image/webp">
                <small class="text-body-secondary">{{ __('Yeni bir görsel seçerseniz hazır seçimin yerine bu kullanılır.') }}</small>
              </div>

              <!-- 4 RPG Stats -->
              <h6 class="fw-bold text-uppercase text-primary mb-3">
                <i class="icon-base ti tabler-chart-bar me-1"></i> 3. {{ __('RPG İstatistik Kutuları (4 Adet)') }}
              </h6>

              <div class="row g-2 mb-4">
                <!-- Stat 1 -->
                <div class="col-md-6">
                  <div class="border rounded p-2 bg-label-secondary">
                    <small class="fw-bold d-block mb-1 text-primary">{{ __('İstatistik 1') }}</small>
                    <div class="row g-1">
                      <div class="col-5">
                        <input type="text" class="form-control form-control-sm input-stat-val" name="stats[0][value]" id="stat_0_val" value="+12.5k XP" placeholder="Değer">
                      </div>
                      <div class="col-7">
                        <input type="text" class="form-control form-control-sm input-stat-lbl" name="stats[0][label]" id="stat_0_lbl" value="Kazanılan Tecrübe" placeholder="Etiket">
                      </div>
                    </div>
                    <select class="form-select form-select-sm mt-1 input-stat-color" name="stats[0][color]" id="stat_0_clr">
                      <option value="text-warning" selected>Sarı (Warning)</option>
                      <option value="text-info">Açık Mavi (Info)</option>
                      <option value="text-success">Yeşil (Success)</option>
                      <option value="text-danger">Kırmızı (Danger)</option>
                      <option value="text-white">Beyaz</option>
                    </select>
                  </div>
                </div>

                <!-- Stat 2 -->
                <div class="col-md-6">
                  <div class="border rounded p-2 bg-label-secondary">
                    <small class="fw-bold d-block mb-1 text-info">{{ __('İstatistik 2') }}</small>
                    <div class="row g-1">
                      <div class="col-5">
                        <input type="text" class="form-control form-control-sm input-stat-val" name="stats[1][value]" id="stat_1_val" value="3 Kalıntı" placeholder="Değer">
                      </div>
                      <div class="col-7">
                        <input type="text" class="form-control form-control-sm input-stat-lbl" name="stats[1][label]" id="stat_1_lbl" value="Efsanevi Eşya" placeholder="Etiket">
                      </div>
                    </div>
                    <select class="form-select form-select-sm mt-1 input-stat-color" name="stats[1][color]" id="stat_1_clr">
                      <option value="text-warning">Sarı (Warning)</option>
                      <option value="text-info" selected>Açık Mavi (Info)</option>
                      <option value="text-success">Yeşil (Success)</option>
                      <option value="text-danger">Kırmızı (Danger)</option>
                      <option value="text-white">Beyaz</option>
                    </select>
                  </div>
                </div>

                <!-- Stat 3 -->
                <div class="col-md-6">
                  <div class="border rounded p-2 bg-label-secondary">
                    <small class="fw-bold d-block mb-1 text-success">{{ __('İstatistik 3') }}</small>
                    <div class="row g-1">
                      <div class="col-5">
                        <input type="text" class="form-control form-control-sm input-stat-val" name="stats[2][value]" id="stat_2_val" value="%100" placeholder="Değer">
                      </div>
                      <div class="col-7">
                        <input type="text" class="form-control form-control-sm input-stat-lbl" name="stats[2][label]" id="stat_2_lbl" value="Bölge Güvenliği" placeholder="Etiket">
                      </div>
                    </div>
                    <select class="form-select form-select-sm mt-1 input-stat-color" name="stats[2][color]" id="stat_2_clr">
                      <option value="text-warning">Sarı (Warning)</option>
                      <option value="text-info">Açık Mavi (Info)</option>
                      <option value="text-success" selected>Yeşil (Success)</option>
                      <option value="text-danger">Kırmızı (Danger)</option>
                      <option value="text-white">Beyaz</option>
                    </select>
                  </div>
                </div>

                <!-- Stat 4 -->
                <div class="col-md-6">
                  <div class="border rounded p-2 bg-label-secondary">
                    <small class="fw-bold d-block mb-1 text-white">{{ __('İstatistik 4') }}</small>
                    <div class="row g-1">
                      <div class="col-5">
                        <input type="text" class="form-control form-control-sm input-stat-val" name="stats[3][value]" id="stat_3_val" value="0 Kayıp" placeholder="Değer">
                      </div>
                      <div class="col-7">
                        <input type="text" class="form-control form-control-sm input-stat-lbl" name="stats[3][label]" id="stat_3_lbl" value="Birlik Durumu" placeholder="Etiket">
                      </div>
                    </div>
                    <select class="form-select form-select-sm mt-1 input-stat-color" name="stats[3][color]" id="stat_3_clr">
                      <option value="text-warning">Sarı (Warning)</option>
                      <option value="text-info">Açık Mavi (Info)</option>
                      <option value="text-success">Yeşil (Success)</option>
                      <option value="text-danger">Kırmızı (Danger)</option>
                      <option value="text-white" selected>Beyaz</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Button & Ordering -->
              <h6 class="fw-bold text-uppercase text-primary mb-3">
                <i class="icon-base ti tabler-link me-1"></i> 4. {{ __('Aksiyon Butonu & Sıralama') }}
              </h6>

              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_button_text">{{ __('Buton Metni') }}</label>
                  <input type="text" class="form-control" id="input_button_text" name="button_text" placeholder="örn: Karakter Kütüğü & Rapor">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_button_url">{{ __('Buton Linki (URL)') }}</label>
                  <input type="text" class="form-control" id="input_button_url" name="button_url" placeholder="örn: /valiant-shade/character/sheet">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_button_color">{{ __('Buton Rengi') }}</label>
                  <select class="form-select" id="input_button_color" name="button_color">
                    <option value="primary" selected>Mavi (Primary)</option>
                    <option value="success">Yeşil (Success)</option>
                    <option value="danger">Kırmızı (Danger)</option>
                    <option value="warning">Sarı (Warning)</option>
                    <option value="info">Açık Mavi (Info)</option>
                    <option value="dark">Koyu (Dark)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_location_badge">{{ __('Konum / Alt Bilgi') }}</label>
                  <input type="text" class="form-control" id="input_location_badge" name="location_badge" placeholder="örn: Ejderha Zirvesi - Harabeler">
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold" for="input_order">{{ __('Slayt Sırası') }}</label>
                  <input type="number" class="form-control" id="input_order" name="order" value="1" min="0">
                </div>
                <div class="col-md-6 d-flex align-items-center pt-md-4">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="input_is_active" name="is_active" value="1" checked>
                    <label class="form-check-label fw-bold" for="input_is_active">{{ __('Slaytı Hemen Yayına Al') }}</label>
                  </div>
                </div>
              </div>

            </div>

            <!-- Right Preview Column -->
            <div class="col-lg-5">
              <div class="sticky-top" style="top: 1rem;">
                <h6 class="fw-bold text-uppercase text-warning mb-2 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-device-desktop"></i>
                  <span>{{ __('Canlı Slayt Önizlemesi') }}</span>
                </h6>
                <small class="text-body-secondary d-block mb-3">{{ __('Formu doldurdukça paneldeki görünüm anlık güncellenir:') }}</small>

                <div class="live-preview-box shadow">
                  <div class="preview-bg" id="previewBg" style="background-image: url('{{ $presets[0]['url'] }}');"></div>
                  <div class="preview-overlay"></div>
                  <div class="preview-content">
                    <div>
                      <div class="d-flex align-items-center gap-2 mb-2 flex-wrap" style="padding-right: 40px;">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" id="prevBadge">
                          <i class="icon-base ti tabler-trophy me-1" id="prevIcon"></i> <span id="prevCategory">Sefer Zaferi</span>
                        </span>
                        <span class="guild-news-tag-badge" id="prevTagBadge">
                          <i class="icon-base ti tabler-sparkles me-1 text-warning"></i> <span id="prevTag" class="guild-news-tag-text">Son Keşif Raporu</span>
                        </span>
                      </div>
                      <h5 class="text-white fw-bold mb-2" id="prevTitle" style="text-shadow: 0 2px 6px rgba(0,0,0,0.6); line-height: 1.3;">
                        Ejderha Zirvesi ve Antik Harabeler Fethi Tamamlandı!
                      </h5>
                      <p class="text-white-50 mb-3 small" id="prevDesc" style="line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        Kıdemli keşif birliğimiz Kadim Ejderha Ignis'i dize getirerek kutsal kalıntıları kurtardı...
                      </p>
                    </div>

                    <div>
                      <div class="row g-1 mb-2">
                        <div class="col-6">
                          <div class="preview-stat-card">
                            <div class="text-warning fw-bold small" id="prevStat0Val">+12.5k XP</div>
                            <div class="text-white-50" style="font-size: 0.65rem;" id="prevStat0Lbl">Kazanılan Tecrübe</div>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="preview-stat-card">
                            <div class="text-info fw-bold small" id="prevStat1Val">3 Kalıntı</div>
                            <div class="text-white-50" style="font-size: 0.65rem;" id="prevStat1Lbl">Efsanevi Eşya</div>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="preview-stat-card">
                            <div class="text-success fw-bold small" id="prevStat2Val">%100</div>
                            <div class="text-white-50" style="font-size: 0.65rem;" id="prevStat2Lbl">Bölge Güvenliği</div>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="preview-stat-card">
                            <div class="text-white fw-bold small" id="prevStat3Val">0 Kayıp</div>
                            <div class="text-white-50" style="font-size: 0.65rem;" id="prevStat3Lbl">Birlik Durumu</div>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <button type="button" class="btn btn-xs btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="prevBtn">
                          <i class="icon-base ti tabler-shield-check"></i>
                          <span id="prevBtnText">Karakter Kütüğü & Rapor</span>
                        </button>
                        <span class="text-white-50" style="font-size: 0.7rem;" id="prevLoc">
                          <i class="icon-base ti tabler-map-pin me-1 text-warning"></i> <span id="prevLocText">Ejderha Zirvesi</span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer bg-label-secondary border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Vazgeç') }}</button>
          <button type="submit" class="btn btn-primary" id="btnSaveNews">
            <i class="icon-base ti tabler-check me-1"></i> <span>{{ __('Slaytı Kaydet') }}</span>
          </button>
        </div>
      </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('guildNewsModal');
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('guildNewsForm');
  const formMethod = document.getElementById('form_method');
  const newsIdInput = document.getElementById('news_id');
  const modalTitle = document.getElementById('guildNewsModalTitle');

  // Preview elements
  const prevTitle = document.getElementById('prevTitle');
  const prevDesc = document.getElementById('prevDesc');
  const prevCategory = document.getElementById('prevCategory');
  const prevBadge = document.getElementById('prevBadge');
  const prevIcon = document.getElementById('prevIcon');
  const prevTag = document.getElementById('prevTag');
  const prevTagBadge = document.getElementById('prevTagBadge');
  const prevBtn = document.getElementById('prevBtn');
  const prevBtnText = document.getElementById('prevBtnText');
  const prevLocText = document.getElementById('prevLocText');
  const previewBg = document.getElementById('previewBg');

  // Input elements
  const inputTitle = document.getElementById('input_title');
  const inputCategory = document.getElementById('input_category');
  const inputBadgeColor = document.getElementById('input_badge_color');
  const inputCategoryIcon = document.getElementById('input_category_icon');
  const inputTag = document.getElementById('input_tag');
  const inputDescription = document.getElementById('input_description');
  const inputButtonText = document.getElementById('input_button_text');
  const inputButtonColor = document.getElementById('input_button_color');
  const inputLocationBadge = document.getElementById('input_location_badge');
  const inputImagePreset = document.getElementById('input_image_preset');
  const inputCustomImage = document.getElementById('input_custom_image');

  // Update Live Preview Function
  function updatePreview() {
    prevTitle.textContent = inputTitle.value.trim() || 'Haber Başlığı';
    prevDesc.textContent = inputDescription.value.trim() || 'Haber açıklaması burada görünecek...';
    prevCategory.textContent = inputCategory.value.trim() || 'Duyuru';

    // Badge color
    const color = inputBadgeColor.value || 'primary';
    prevBadge.className = `badge bg-${color} text-${color === 'warning' ? 'dark' : 'white'} fw-bold px-2 py-1`;

    // Category icon
    prevIcon.className = (inputCategoryIcon.value || 'icon-base ti tabler-trophy') + ' me-1';

    // Tag
    if (inputTag.value.trim()) {
      prevTag.textContent = inputTag.value.trim();
      prevTagBadge.style.display = 'inline-block';
    } else {
      prevTagBadge.style.display = 'none';
    }

    // Button
    prevBtnText.textContent = inputButtonText.value.trim() || 'İncele';
    const btnColor = inputButtonColor.value || 'primary';
    prevBtn.className = `btn btn-xs btn-${btnColor} d-inline-flex align-items-center gap-1 shadow-sm`;

    // Location
    prevLocText.textContent = inputLocationBadge.value.trim() || 'Lonca Merkezi';

    // Stats
    for (let i = 0; i < 4; i++) {
      const valInput = document.getElementById(`stat_${i}_val`);
      const lblInput = document.getElementById(`stat_${i}_lbl`);
      const clrInput = document.getElementById(`stat_${i}_clr`);
      const valEl = document.getElementById(`prevStat${i}Val`);
      const lblEl = document.getElementById(`prevStat${i}Lbl`);

      if (valInput && valEl) {
        valEl.textContent = valInput.value.trim() || '-';
        valEl.className = `${clrInput.value || 'text-warning'} fw-bold small`;
      }
      if (lblInput && lblEl) {
        lblEl.textContent = lblInput.value.trim() || '';
      }
    }
  }

  // Attach live update listeners
  [inputTitle, inputCategory, inputBadgeColor, inputCategoryIcon, inputTag, inputDescription, inputButtonText, inputButtonColor, inputLocationBadge].forEach(el => {
    if (el) {
      el.addEventListener('input', updatePreview);
      el.addEventListener('change', updatePreview);
    }
  });

  for (let i = 0; i < 4; i++) {
    const valInput = document.getElementById(`stat_${i}_val`);
    const lblInput = document.getElementById(`stat_${i}_lbl`);
    const clrInput = document.getElementById(`stat_${i}_clr`);
    if (valInput) valInput.addEventListener('input', updatePreview);
    if (lblInput) lblInput.addEventListener('input', updatePreview);
    if (clrInput) clrInput.addEventListener('change', updatePreview);
  }

  // Preset Image selection click
  document.querySelectorAll('.preset-card').forEach(card => {
    card.addEventListener('click', function () {
      document.querySelectorAll('.preset-card').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const path = this.getAttribute('data-path');
      const url = this.getAttribute('data-url');
      inputImagePreset.value = path;
      previewBg.style.backgroundImage = `url('${url}')`;
      inputCustomImage.value = ''; // clear file input
    });
  });

  // Custom Image file change preview
  inputCustomImage.addEventListener('change', function () {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewBg.style.backgroundImage = `url('${e.target.result}')`;
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  // Open Create Modal
  function resetModalForCreate() {
    form.reset();
    form.action = "{{ route('valiant-shade.pages-guild-news.store') }}";
    formMethod.value = "POST";
    newsIdInput.value = "";
    modalTitle.innerHTML = '<i class="icon-base ti tabler-plus me-1"></i> {{ __("Yeni Haber / Bülten Ekle") }}';

    // Set first preset active
    const firstPreset = document.querySelector('.preset-card');
    if (firstPreset) {
      document.querySelectorAll('.preset-card').forEach(c => c.classList.remove('active'));
      firstPreset.classList.add('active');
      inputImagePreset.value = firstPreset.getAttribute('data-path');
      previewBg.style.backgroundImage = `url('${firstPreset.getAttribute('data-url')}')`;
    }
    updatePreview();
  }

  document.getElementById('btnOpenCreateModal').addEventListener('click', function () {
    resetModalForCreate();
    modal.show();
  });

  const btnEmptyCreate = document.getElementById('btnEmptyCreate');
  if (btnEmptyCreate) {
    btnEmptyCreate.addEventListener('click', function () {
      resetModalForCreate();
      modal.show();
    });
  }

  // Open Edit Modal
  document.querySelectorAll('.btn-edit-news').forEach(btn => {
    btn.addEventListener('click', function () {
      const data = JSON.parse(this.getAttribute('data-news'));
      modalTitle.innerHTML = '<i class="icon-base ti tabler-edit me-1"></i> {{ __("Haberi Düzenle") }}';
      form.action = `/valiant-shade/pages/guild-news/${data.id}`;
      formMethod.value = "POST";
      newsIdInput.value = data.id;

      inputTitle.value = data.title || '';
      inputCategory.value = data.category || '';
      inputBadgeColor.value = data.badge_color || 'primary';
      inputCategoryIcon.value = data.category_icon || 'icon-base ti tabler-trophy';
      inputTag.value = data.tag || '';
      document.getElementById('input_date_text').value = data.date_text || '';
      inputDescription.value = data.description || '';

      inputButtonText.value = data.button_text || '';
      document.getElementById('input_button_url').value = data.button_url || '';
      inputButtonColor.value = data.button_color || 'primary';
      inputLocationBadge.value = data.location_badge || '';
      document.getElementById('input_order').value = data.order || 1;
      document.getElementById('input_is_active').checked = !!data.is_active;

      // Stats
      if (Array.isArray(data.stats)) {
        for (let i = 0; i < 4; i++) {
          const st = data.stats[i] || {};
          const valInput = document.getElementById(`stat_${i}_val`);
          const lblInput = document.getElementById(`stat_${i}_lbl`);
          const clrInput = document.getElementById(`stat_${i}_clr`);
          if (valInput) valInput.value = st.value || '';
          if (lblInput) lblInput.value = st.label || '';
          if (clrInput) clrInput.value = st.color || 'text-warning';
        }
      }

      // Image
      let matchedPreset = false;
      document.querySelectorAll('.preset-card').forEach(card => {
        card.classList.remove('active');
        if (card.getAttribute('data-path') === data.image_url) {
          card.classList.add('active');
          matchedPreset = true;
        }
      });
      inputImagePreset.value = data.image_url || '';
      const imgUrl = (data.image_url && data.image_url.startsWith('http')) ? data.image_url : `/${data.image_url}`;
      previewBg.style.backgroundImage = `url('${imgUrl}')`;

      updatePreview();
      modal.show();
    });
  });

  // Toggle Active switch
  document.querySelectorAll('.btn-toggle-active').forEach(sw => {
    sw.addEventListener('change', function () {
      const id = this.getAttribute('data-id');
      const isChecked = this.checked;
      const label = document.getElementById(`status-label-${id}`);

      fetch(`/valiant-shade/pages/guild-news/${id}/toggle`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          label.textContent = data.is_active ? '{{ __("Yayında") }}' : '{{ __("Taslak") }}';
          label.className = `form-check-label small fw-medium ${data.is_active ? 'text-success' : 'text-muted'}`;
          Swal.fire({
            icon: 'success',
            title: data.is_active ? 'Yayına Alındı' : 'Taslağa Çekildi',
            text: data.message,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
        }
      })
      .catch(err => {
        this.checked = !isChecked; // revert
        Swal.fire({ icon: 'error', title: 'Hata', text: 'Durum güncellenemedi.' });
      });
    });
  });

  // Delete News Item
  document.querySelectorAll('.btn-delete-news').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');

      Swal.fire({
        title: 'Haberi Silmek İstediğinize Emin misiniz?',
        text: `"${title}" başlıklı haber kalıcı olarak silinecektir.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Sil!',
        cancelButtonText: 'Vazgeç'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`/valiant-shade/pages/guild-news/${id}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            }
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              const card = document.getElementById(`news-card-${id}`);
              if (card) {
                card.remove();
              }
              Swal.fire({
                icon: 'success',
                title: 'Silindi!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                window.location.reload();
              });
            }
          })
          .catch(() => {
            Swal.fire({ icon: 'error', title: 'Hata', text: 'Haber silinirken hata oluştu.' });
          });
        }
      });
    });
  });

  // Reset to Defaults
  document.getElementById('btnResetDefaults').addEventListener('click', function () {
    Swal.fire({
      title: 'Varsayılan Haberleri Geri Yükle?',
      text: 'Mevcut haberler sıfırlanıp 3 adet orijinal fantastik sefer ve hazine slaytı geri yüklenecektir.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Evet, Geri Yükle',
      cancelButtonText: 'Vazgeç'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch("{{ route('valiant-shade.pages-guild-news.reset-defaults') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          }
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Başarılı!',
              text: data.message,
              timer: 1500,
              showConfirmButton: false
            }).then(() => {
              window.location.reload();
            });
          }
        })
        .catch(() => {
          Swal.fire({ icon: 'error', title: 'Hata', text: 'Geri yükleme sırasında hata oluştu.' });
        });
      }
    });
  });
});
</script>
@endsection
