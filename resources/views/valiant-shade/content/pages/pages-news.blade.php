@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Haberler, Fısıltılar & Sefer Bülteni - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* Fantasy News Styling */
  .news-hero-card {
    border-radius: 14px;
    position: relative;
    overflow: hidden;
    min-height: 380px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
    background-size: cover;
    background-position: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .news-hero-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6);
  }
  .news-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.3) 0%, rgba(15, 23, 42, 0.85) 60%, rgba(15, 23, 42, 0.98) 100%),
                radial-gradient(circle at 80% 20%, rgba(220, 38, 38, 0.25), transparent 50%);
  }
  .news-hero-content {
    position: relative;
    z-index: 2;
    padding: 2.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
  }

  /* News Grid Cards */
  .news-feed-card {
    border-radius: 12px;
    background: #182234;
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.25s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  .news-feed-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 255, 255, 0.22);
  }
  .news-thumb-box {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
    overflow: hidden;
  }
  .news-thumb-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #182234 0%, rgba(24, 34, 52, 0.3) 65%, transparent 100%);
  }
  .news-tag-badge {
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #ffffff;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 0.22rem 0.6rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
  }
  .news-stat-pill {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 6px;
    padding: 0.35rem 0.6rem;
  }
  .category-filter-btn {
    transition: all 0.2s ease;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.45rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
  }
  .category-filter-btn.active {
    background-color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.35);
  }

  /* Detail Reader Modal */
  #newsDetailModal .modal-dialog {
    max-width: 860px;
  }
  #newsDetailModal .modal-content {
    background-color: #111a28;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    overflow: hidden;
  }
  .modal-hero-cover {
    height: 280px;
    background-size: cover;
    background-position: center;
    position: relative;
  }
  .modal-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #111a28 0%, rgba(17, 26, 40, 0.5) 60%, rgba(17, 26, 40, 0.2) 100%);
  }
  .fantasy-quote-box {
    border-left: 4px solid #f59e0b;
    background: rgba(245, 158, 11, 0.08);
    padding: 1rem 1.25rem;
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: #e2e8f0;
    margin: 1.5rem 0;
    position: relative;
  }
  .fantasy-quote-box.danger-quote {
    border-left-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
  }
  .fantasy-quote-box.dark-quote {
    border-left-color: #a855f7;
    background: rgba(168, 85, 247, 0.08);
  }
  .article-body-p {
    font-size: 1.02rem;
    line-height: 1.75;
    color: #cbd5e1;
    margin-bottom: 1.25rem;
  }
  .article-body-p:first-of-type::first-letter {
    font-size: 2.6rem;
    float: left;
    line-height: 1;
    margin-right: 0.5rem;
    font-weight: 700;
    color: #f59e0b;
    font-family: serif;
  }
  .guild-seal-stamp {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    border: 1px dashed rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.04);
    font-size: 0.78rem;
    color: #94a3b8;
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

  <!-- Page Header & Action Bar -->
  <div class="row align-items-center mb-4 g-3">
    <div class="col-lg-7 col-md-6">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-label-primary px-2 py-1"><i class="icon-base ti tabler-scroll me-1"></i> Valiant Shade Gazetesi</span>
        <span class="badge bg-label-danger px-2 py-1"><i class="icon-base ti tabler-flame me-1"></i> Canlı Bülten</span>
      </div>
      <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-news fs-2 text-warning"></i>
        <span>{{ __('Haberler, Fısıltılar & Sefer Bülteni') }}</span>
      </h3>
      <p class="text-body-secondary mb-0">
        {{ __('Meyhane fısıltıları, tekinsiz dedikodular, şehit düşen kahramanlarımızın anma bildirileri, zindan sefer raporları ve Yüksek Divan fermanları.') }}
      </p>
    </div>
    <div class="col-lg-5 col-md-6 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
      <a href="{{ route('valiant-shade.dashboard-analytics') }}" class="btn btn-label-secondary d-inline-flex align-items-center gap-1">
        <i class="icon-base ti tabler-home"></i>
        <span>{{ __('Ana Panel') }}</span>
      </a>
      <button type="button" class="btn btn-outline-warning d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#whisperTipModal">
        <i class="icon-base ti tabler-message-2-share"></i>
        <span>{{ __('Meyhane Fısıltısı Gönder') }}</span>
      </button>
      <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" onclick="window.scrollTo({ top: document.getElementById('newsFeedGrid').offsetTop - 80, behavior: 'smooth' })">
        <i class="icon-base ti tabler-book-2"></i>
        <span>{{ __('Haberleri Keşfet') }}</span>
      </button>
    </div>
  </div>

  <!-- Featured Manşet / Breaking News Banner -->
  @if($featuredNews)
  <div class="card border-0 mb-5 overflow-hidden">
    <div class="news-hero-card" style="background-image: url('{{ $featuredNews->image_asset }}');">
      <div class="news-hero-overlay"></div>
      <div class="news-hero-content">
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
          <span class="badge bg-{{ $featuredNews->badge_color ?? 'danger' }} text-white px-3 py-2 fs-6 fw-bold">
            <i class="{{ $featuredNews->category_icon ?: 'icon-base ti tabler-skull' }} me-1"></i> {{ $featuredNews->category }}
          </span>
          @if($featuredNews->tag)
          <span class="news-tag-badge">
            <i class="{{ $featuredNews->tag_icon ?: 'icon-base ti tabler-candle' }} me-1 text-{{ $featuredNews->badge_color ?? 'danger' }}"></i>
            {{ $featuredNews->tag }}
          </span>
          @endif
          <span class="badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-25 px-2 py-1">
            <i class="icon-base ti tabler-flame me-1"></i> {{ __('GÜNÜN MANŞETİ') }}
          </span>
          <span class="text-white-50 ms-auto d-none d-md-inline-block small">
            <i class="icon-base ti tabler-clock me-1"></i> {{ $featuredNews->date_text }} &bull; {{ $featuredNews->read_time }}
          </span>
        </div>

        <h2 class="text-white fw-bold mb-2 fs-2" style="text-shadow: 0 3px 12px rgba(0,0,0,0.85); line-height: 1.3;">
          {{ $featuredNews->title }}
        </h2>
        <p class="text-white-50 fs-6 mb-4 d-none d-sm-block" style="max-width: 85%; line-height: 1.6;">
          {{ $featuredNews->description }}
        </p>

        <!-- Stats on Hero -->
        @if(!empty($featuredNews->stats))
        <div class="row g-2 mb-4 d-none d-md-flex" style="max-width: 720px;">
          @foreach($featuredNews->stats as $st)
          <div class="col-sm-3 col-6">
            <div class="p-2 rounded" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(6px);">
              <div class="{{ $st['color'] ?? 'text-warning' }} fw-bold fs-6">{{ $st['value'] }}</div>
              <div class="text-white-50" style="font-size: 0.75rem;">{{ $st['label'] }}</div>
            </div>
          </div>
          @endforeach
        </div>
        @endif

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top border-white border-opacity-10">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar avatar-sm bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-feather text-warning"></i>
            </div>
            <div>
              <span class="text-white fw-semibold small">{{ $featuredNews->author }}</span>
              <span class="text-white-50 d-none d-sm-inline small"> &bull; {{ $featuredNews->author_role }}</span>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-{{ $featuredNews->button_color ?? 'danger' }} px-4 py-2 fw-medium d-inline-flex align-items-center gap-2 shadow" data-bs-toggle="modal" data-bs-target="#newsDetailModal" onclick="openNewsDetail({{ $featuredNews->id }})">
              <i class="{{ $featuredNews->button_icon ?: 'icon-base ti tabler-book-2' }}"></i>
              <span>{{ __('Haberi Detaylıca Oku') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Quick Category Counter Badges -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-danger rounded p-2">
            <i class="icon-base ti tabler-skull fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-danger">{{ $statsSummary['deaths'] ?? 2 }}</h5>
            <small class="text-body-secondary">{{ __('Ölüm & Şehit Bildirisi') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-warning rounded p-2">
            <i class="icon-base ti tabler-flame fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-warning">{{ $statsSummary['rumors'] ?? 2 }}</h5>
            <small class="text-body-secondary">{{ __('Meyhane & Şehir Dedikodusu') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-success rounded p-2">
            <i class="icon-base ti tabler-trophy fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-success">{{ $statsSummary['victories'] ?? 1 }}</h5>
            <small class="text-body-secondary">{{ __('Sefer Zaferi & Ganimet') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-primary rounded p-2">
            <i class="icon-base ti tabler-gavel fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-primary">{{ $statsSummary['decrees'] ?? 1 }}</h5>
            <small class="text-body-secondary">{{ __('Divan Kararı & Kelle Ödülü') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card border-0 shadow-sm mb-4" id="newsFeedGrid">
    <div class="card-body py-3">
      <div class="row align-items-center g-3">
        <!-- Categories Tabs -->
        <div class="col-lg-8 col-12">
          <div class="d-flex align-items-center gap-2 flex-wrap" id="categoryFilterGroup">
            <button type="button" class="btn btn-sm category-filter-btn active" data-bs-toggle="tab" data-cat="all">
              <i class="icon-base ti tabler-layout-grid me-1"></i> Tüm Bülten ({{ $allNews->count() }})
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-danger" data-bs-toggle="tab" data-cat="ölüm">
              <i class="icon-base ti tabler-skull me-1"></i> Ölüm & Şehitler
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-warning" data-bs-toggle="tab" data-cat="dedikodu">
              <i class="icon-base ti tabler-flame me-1"></i> Dedikodular & Fısıltı
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-dark" data-bs-toggle="tab" data-cat="suikast">
              <i class="icon-base ti tabler-dagger me-1"></i> Suikast & İhanet
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-success" data-bs-toggle="tab" data-cat="zafer">
              <i class="icon-base ti tabler-trophy me-1"></i> Sefer Raporları
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-primary" data-bs-toggle="tab" data-cat="meclis">
              <i class="icon-base ti tabler-gavel me-1"></i> Lonca Meclisi
            </button>
          </div>
        </div>

        <!-- Live Search Input -->
        <div class="col-lg-4 col-12">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="icon-base ti tabler-search text-body-secondary"></i>
            </span>
            <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="Haber, dedikodu veya kahraman ara..." value="{{ $searchQuery }}">
            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Temizle">
              <i class="icon-base ti tabler-x"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- News Cards Grid -->
  <div class="row g-4" id="newsCardsContainer">
    @forelse($news as $item)
    <div class="col-lg-4 col-md-6 news-item-col"
         data-id="{{ $item->id }}"
         data-category="{{ mb_strtolower($item->category) }}"
         data-title="{{ mb_strtolower($item->title) }}"
         data-desc="{{ mb_strtolower($item->description) }}"
         data-location="{{ mb_strtolower($item->location_badge ?? '') }}"
         data-author="{{ mb_strtolower($item->author ?? '') }}">

      <div class="card news-feed-card h-100">
        <!-- Thumbnail with Badges -->
        <div class="news-thumb-box" style="background-image: url('{{ $item->image_asset }}');">
          <div class="news-thumb-overlay"></div>

          <!-- Top Category & Tag Badges -->
          <div class="position-absolute top-0 start-0 p-3 d-flex align-items-center gap-1 flex-wrap" style="z-index: 2;">
            <span class="badge bg-{{ $item->badge_color ?? 'primary' }} text-white fw-bold">
              <i class="{{ $item->category_icon ?: 'icon-base ti tabler-shield' }} me-1"></i> {{ $item->category }}
            </span>
            @if($item->tag)
            <span class="news-tag-badge">
              <i class="{{ $item->tag_icon ?: 'icon-base ti tabler-sparkles' }} me-1 text-{{ $item->badge_color ?? 'warning' }}"></i>
              {{ $item->tag }}
            </span>
            @endif
          </div>

          <!-- Top Right Read Time -->
          <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
            <span class="badge bg-dark bg-opacity-75 text-white-50 border border-secondary border-opacity-25" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-book me-1"></i> {{ $item->read_time }}
            </span>
          </div>

          <!-- Bottom Location -->
          @if(!empty($item->location_badge))
          <div class="position-absolute bottom-0 start-0 p-3" style="z-index: 2;">
            <span class="text-white-50 small d-flex align-items-center">
              <i class="icon-base ti tabler-map-pin me-1 text-{{ $item->badge_color ?? 'warning' }}"></i>
              {{ $item->location_badge }}
            </span>
          </div>
          @endif
        </div>

        <!-- Card Body -->
        <div class="card-body d-flex flex-column justify-content-between p-4">
          <div>
            <!-- Date & Author Row -->
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 text-body-secondary small">
              <span class="d-flex align-items-center">
                <i class="icon-base ti tabler-calendar-event me-1"></i> {{ $item->date_text }}
              </span>
              <span class="d-flex align-items-center">
                <i class="icon-base ti tabler-feather me-1 text-warning"></i> {{ $item->author }}
              </span>
            </div>

            <!-- Title -->
            <h5 class="fw-bold mb-2 text-white" style="line-height: 1.4; min-height: 48px;">
              {{ $item->title }}
            </h5>

            <!-- Description -->
            <p class="text-body-secondary small mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
              {{ $item->description }}
            </p>

            <!-- Micro Stats Preview -->
            @if(!empty($item->stats) && is_array($item->stats))
            <div class="row g-2 mb-3">
              @foreach(array_slice($item->stats, 0, 2) as $st)
              <div class="col-6">
                <div class="news-stat-pill">
                  <div class="{{ $st['color'] ?? 'text-warning' }} fw-bold" style="font-size: 0.85rem;">{{ $st['value'] }}</div>
                  <div class="text-body-secondary" style="font-size: 0.72rem;">{{ $st['label'] }}</div>
                </div>
              </div>
              @endforeach
            </div>
            @endif
          </div>

          <!-- Card Footer Action -->
          <div class="pt-3 border-top border-white border-opacity-10 d-flex align-items-center justify-content-between">
            <span class="guild-seal-stamp">
              <i class="icon-base ti tabler-shield-check text-{{ $item->badge_color ?? 'primary' }}"></i>
              <span>Doğrulandı</span>
            </span>

            <button type="button" class="btn btn-outline-{{ $item->button_color ?? 'primary' }} btn-sm px-3 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newsDetailModal" onclick="openNewsDetail({{ $item->id }})">
              <i class="{{ $item->button_icon ?: 'icon-base ti tabler-book-2' }}"></i>
              <span>{{ __('Detaylı Oku') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
      <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3">
        <i class="icon-base ti tabler-file-search fs-1"></i>
      </div>
      <h5>{{ __('Eşleşen Haber Bulunamadı') }}</h5>
      <p class="text-body-secondary">{{ __('Aradığınız kriterlere uygun haber veya fısıltı kaydı bulunmuyor.') }}</p>
    </div>
    @endforelse
  </div>

</div>

<!-- Full Story Reader Modal -->
<div class="modal fade" id="newsDetailModal" tabindex="-1" aria-labelledby="newsDetailModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg">
      <!-- Modal Hero Cover -->
      <div class="modal-hero-cover" id="modalHeroCover">
        <div class="modal-hero-overlay"></div>
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="position-absolute bottom-0 start-0 p-4 z-2 w-100">
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <span class="badge" id="modalCategoryBadge">Kategori</span>
            <span class="news-tag-badge" id="modalTagBadge">Etiket</span>
            <span class="text-white-50 ms-auto small" id="modalDateText">Tarih</span>
          </div>
          <h3 class="text-white fw-bold mb-0" id="modalTitle" style="text-shadow: 0 2px 8px rgba(0,0,0,0.8); line-height: 1.3;">
            Haber Başlığı
          </h3>
        </div>
      </div>

      <!-- Modal Body (Narrative & Breakdown) -->
      <div class="modal-body p-4 p-md-5">
        <!-- Byline & Meta Info -->
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom border-white border-opacity-10 flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-feather text-warning fs-4"></i>
            </div>
            <div>
              <h6 class="mb-0 text-white fw-bold" id="modalAuthor">Yazar</h6>
              <small class="text-body-secondary" id="modalAuthorRole">Makam / Görev</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3 text-body-secondary small">
            <div><i class="icon-base ti tabler-map-pin me-1 text-warning"></i> <span id="modalLocation">Konum</span></div>
            <div><i class="icon-base ti tabler-book me-1 text-info"></i> <span id="modalReadTime">Okuma Süresi</span></div>
          </div>
        </div>

        <!-- Full Story Narrative -->
        <div id="modalStoryContent">
          <!-- Dynamic paragraphs injected by JS -->
        </div>

        <!-- Testimonial / Quote Box -->
        <div class="fantasy-quote-box" id="modalQuoteBox" style="display: none;">
          <i class="icon-base ti tabler-quote fs-3 text-warning opacity-50 position-absolute top-0 end-0 m-2"></i>
          <p class="mb-0 fw-medium" id="modalQuoteText"></p>
        </div>

        <!-- Mission / Incident Stats Summary -->
        <div class="mt-4 pt-3 border-top border-white border-opacity-10">
          <h6 class="text-white fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-chart-pie text-warning"></i>
            <span>{{ __('Harekat & Olay Özeti') }}</span>
          </h6>
          <div class="row g-3" id="modalStatsGrid">
            <!-- Dynamic stats injected by JS -->
          </div>
        </div>
      </div>

      <!-- Modal Footer with RPG Actions -->
      <div class="modal-footer border-top border-white border-opacity-10 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2" id="modalDynamicActions">
          <!-- Action buttons injected based on category (taziye, rüşvet/meyhane, ferman) -->
        </div>
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">{{ __('Kapat') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Send Whisper Tip Modal (Roleplay Feature) -->
<div class="modal fade" id="whisperTipModal" tabindex="-1" aria-labelledby="whisperTipModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #182234; border: 1px solid rgba(255,255,255,0.15);">
      <div class="modal-header border-bottom border-white border-opacity-10">
        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="whisperTipModalTitle">
          <i class="icon-base ti tabler-message-2-question text-warning"></i>
          <span>{{ __('Meyhane Fısıltısı & İhbar Gönder') }}</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-body-secondary small mb-3">
          {{ __('Lonca sokaklarında duyduğunuz şüpheli bir olayı, zindan kaybını veya meyhane dedikodusunu Valiant Shade İstihbarat Teşkilatı\'na gizlice iletin.') }}
        </p>
        <div class="mb-3">
          <label class="form-label text-white small fw-medium">{{ __('Fısıltı Başlığı veya Konu') }}</label>
          <input type="text" class="form-control" id="whisperSubject" placeholder="Örn: Kızıl Han'da şüpheli elf buluşması...">
        </div>
        <div class="mb-3">
          <label class="form-label text-white small fw-medium">{{ __('Kategori') }}</label>
          <select class="form-select" id="whisperCategory">
            <option value="Dedikodu & Fısıltı">{{ __('Meyhane Dedikodusu') }}</option>
            <option value="Ölüm & Kayıp">{{ __('Zindan Kaybı & Şehit İhbarı') }}</option>
            <option value="Suikast & İhanet">{{ __('Şüpheli Komplo / İhanet') }}</option>
            <option value="Ganimet & Keşif">{{ __('Gizli Hazine / Harita İhbarı') }}</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label text-white small fw-medium">{{ __('Fısıltının Detayları & Görgü Tanıklığı') }}</label>
          <textarea class="form-control" id="whisperMessage" rows="4" placeholder="Gördüğünüz veya duyduğunuz detayları yazın..."></textarea>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="anonymousWhisperCheck" checked>
          <label class="form-check-label text-body-secondary small" for="anonymousWhisperCheck">
            {{ __('Kimliğim gizli kalsın (Gölge Mührü ile korunsun)') }}
          </label>
        </div>
      </div>
      <div class="modal-footer border-top border-white border-opacity-10">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Vazgeç') }}</button>
        <button type="button" class="btn btn-warning d-inline-flex align-items-center gap-1" id="btnSubmitWhisper">
          <i class="icon-base ti tabler-send"></i>
          <span>{{ __('Fısıltıyı Ulaştır') }}</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Complete Static News Store (Mirroring Controller)
  const masterNewsStore = @json($allNews);

  // Open Full Story Reader Modal
  function openNewsDetail(newsId) {
    const item = masterNewsStore.find(n => n.id === newsId);
    if (!item) return;

    // Cover & Badges
    document.getElementById('modalHeroCover').style.backgroundImage = `url('${item.image_asset}')`;

    const catBadge = document.getElementById('modalCategoryBadge');
    catBadge.className = `badge bg-${item.badge_color || 'primary'} text-white fw-bold px-3 py-2`;
    catBadge.innerHTML = `<i class="${item.category_icon || 'icon-base ti tabler-shield'} me-1"></i> ${item.category}`;

    const tagBadge = document.getElementById('modalTagBadge');
    if (item.tag) {
      tagBadge.style.display = 'inline-flex';
      tagBadge.innerHTML = `<i class="${item.tag_icon || 'icon-base ti tabler-sparkles'} me-1 text-${item.badge_color || 'warning'}"></i> ${item.tag}`;
    } else {
      tagBadge.style.display = 'none';
    }

    document.getElementById('modalDateText').innerHTML = `<i class="icon-base ti tabler-clock me-1"></i> ${item.published_date || item.date_text}`;
    document.getElementById('modalTitle').textContent = item.title;
    document.getElementById('modalAuthor').textContent = item.author;
    document.getElementById('modalAuthorRole').textContent = item.author_role || 'Lonca Yetkilisi';
    document.getElementById('modalLocation').textContent = item.location_badge || 'Valiant Shade Kalesi';
    document.getElementById('modalReadTime').textContent = item.read_time || '3 dk okuma';

    // Narrative Body (Format paragraphs)
    const storyBox = document.getElementById('modalStoryContent');
    const paragraphs = (item.full_story || item.description).split('\n\n');
    storyBox.innerHTML = paragraphs.map(p => `<p class="article-body-p">${p.trim()}</p>`).join('');

    // Quote Box
    const quoteBox = document.getElementById('modalQuoteBox');
    const quoteText = document.getElementById('modalQuoteText');
    if (item.testimony) {
      quoteBox.style.display = 'block';
      quoteBox.className = 'fantasy-quote-box ' + (item.badge_color === 'danger' ? 'danger-quote' : (item.badge_color === 'dark' ? 'dark-quote' : ''));
      quoteText.textContent = item.testimony;
    } else {
      quoteBox.style.display = 'none';
    }

    // Stats Grid
    const statsGrid = document.getElementById('modalStatsGrid');
    if (item.stats && item.stats.length > 0) {
      statsGrid.innerHTML = item.stats.map(st => `
        <div class="col-sm-3 col-6">
          <div class="p-3 rounded" style="background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(255,255,255,0.08);">
            <div class="${st.color || 'text-warning'} fw-bold fs-5">${st.value}</div>
            <div class="text-body-secondary small">${st.label}</div>
          </div>
        </div>
      `).join('');
    } else {
      statsGrid.innerHTML = '<div class="col-12 text-body-secondary small">Özel istatistik bulunmuyor.</div>';
    }

    // Dynamic RPG Action Buttons in Modal
    const dynamicActions = document.getElementById('modalDynamicActions');
    if (item.category.includes('Ölüm') || item.category.includes('Anma')) {
      dynamicActions.innerHTML = `
        <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" onclick="taziyeBirak('${item.title}')">
          <i class="icon-base ti tabler-candle"></i>
          <span>Taziye Mumı Yak & Saygı Duruşu</span>
        </button>
      `;
    } else if (item.category.includes('Dedikodu') || item.category.includes('Skandal')) {
      dynamicActions.innerHTML = `
        <button type="button" class="btn btn-outline-warning d-inline-flex align-items-center gap-1" onclick="fisiltiYay('${item.title}')">
          <i class="icon-base ti tabler-beer"></i>
          <span>Kızıl Han'da Bir Kadeh Kaldır & Dedikoduyu Yay</span>
        </button>
      `;
    } else if (item.category.includes('Meclis') || item.category.includes('Ferman')) {
      dynamicActions.innerHTML = `
        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" onclick="fermanKayit('${item.title}')">
          <i class="icon-base ti tabler-scroll"></i>
          <span>Ödül Avına Adını Yazdır</span>
        </button>
      `;
    } else {
      dynamicActions.innerHTML = `
        <button type="button" class="btn btn-outline-success d-inline-flex align-items-center gap-1" onclick="zaferKutla('${item.title}')">
          <i class="icon-base ti tabler-trophy"></i>
          <span>Zaferi Kutla & Şerefe</span>
        </button>
      `;
    }

    const modal = new bootstrap.Modal(document.getElementById('newsDetailModal'));
    modal.show();
  }

  // Interactive RPG Actions with SweetAlert
  function taziyeBirak(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '🕯️ Saygı Duruşu & Taziye',
        text: 'Şehit kahramanlarımızın anısına bir mum yaktınız. Adları Valiant Shade Onur Kitabı\'na kaydedildi.',
        icon: 'info',
        confirmButtonText: 'Ruhları Şad Olsun',
        customClass: { confirmButton: 'btn btn-danger' },
        buttonsStyling: false
      });
    } else {
      alert('Şehitlerimizin anısına taziye sunuldu.');
    }
  }

  function fisiltiYay(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '🍺 Meyhane Fısıltısı',
        text: 'Kızıl Han\'daki masalara birer kadeh bira ısmarlayıp dedikoduyu kulağa fısıldadınız. Şehirdeki şüphe seviyesi arttı!',
        icon: 'warning',
        confirmButtonText: 'Aramızda Kalsın',
        customClass: { confirmButton: 'btn btn-warning' },
        buttonsStyling: false
      });
    } else {
      alert('Dedikodu meyhaneye yayıldı.');
    }
  }

  function fermanKayit(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '📜 Ferman İmzalandı',
        text: 'Kelle ödülü seferine kaydınız yapıldı. Hedef haydutlar canlı ya da ölü getirildiğinde payınız lonca sandığında sizi bekliyor olacak!',
        icon: 'success',
        confirmButtonText: 'Kılıcım Hazır',
        customClass: { confirmButton: 'btn btn-primary' },
        buttonsStyling: false
      });
    } else {
      alert('Fermana katılımınız onaylandı.');
    }
  }

  function zaferKutla(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '🏆 Zafer Kutlaması!',
        text: 'Büyük zafer şerefine kadehler kaldırıldı. Lonca Meclisi sana ekstra +50 XP onur puanı takdim etti!',
        icon: 'success',
        confirmButtonText: 'Valiant Shade İçin!',
        customClass: { confirmButton: 'btn btn-success' },
        buttonsStyling: false
      });
    } else {
      alert('Zafer kutlandı!');
    }
  }

  // DOM Loaded Event Bindings
  document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.news-item-col');
    const filterButtons = document.querySelectorAll('#categoryFilterGroup .category-filter-btn');
    const searchInput = document.getElementById('liveSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');

    let currentFilter = 'all';

    function filterCards() {
      const query = (searchInput.value || '').trim().toLowerCase();

      cards.forEach(card => {
        const cat = card.getAttribute('data-category') || '';
        const title = card.getAttribute('data-title') || '';
        const desc = card.getAttribute('data-desc') || '';
        const location = card.getAttribute('data-location') || '';
        const author = card.getAttribute('data-author') || '';

        // Category check
        const matchCategory = (currentFilter === 'all') || cat.includes(currentFilter);

        // Search check
        const matchQuery = !query
          || title.includes(query)
          || desc.includes(query)
          || location.includes(query)
          || author.includes(query);

        if (matchCategory && matchQuery) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Category Button Clicks
    filterButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.getAttribute('data-cat') || 'all';
        filterCards();
      });
    });

    // Search Input
    searchInput.addEventListener('input', filterCards);

    // Clear Search
    clearBtn.addEventListener('click', function () {
      searchInput.value = '';
      filterCards();
      searchInput.focus();
    });

    // Auto-open modal if hash exists, e.g. #news-1
    if (window.location.hash && window.location.hash.startsWith('#news-')) {
      const idFromHash = parseInt(window.location.hash.replace('#news-', ''), 10);
      if (!isNaN(idFromHash)) {
        setTimeout(() => openNewsDetail(idFromHash), 300);
      }
    }

    // Submit Whisper Modal Handling
    const btnSubmitWhisper = document.getElementById('btnSubmitWhisper');
    if (btnSubmitWhisper) {
      btnSubmitWhisper.addEventListener('click', function () {
        const subject = document.getElementById('whisperSubject').value.trim();
        const msg = document.getElementById('whisperMessage').value.trim();

        if (!subject || !msg) {
          alert('Lütfen fısıltı konusu ve mesajını doldurun.');
          return;
        }

        const modalEl = document.getElementById('whisperTipModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        document.getElementById('whisperSubject').value = '';
        document.getElementById('whisperMessage').value = '';

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: '🤫 Fısıltınız Alındı!',
            text: 'İhbarınız Valiant Shade İstihbarat Teşkilatı\'na gizlice ulaştırıldı. Kıdemli gözcülerimiz olayı derhal araştırmaya başladı.',
            icon: 'success',
            confirmButtonText: 'Anlaşıldı',
            customClass: { confirmButton: 'btn btn-warning' },
            buttonsStyling: false
          });
        } else {
          alert('Fısıltınız başarıyla iletildi.');
        }
      });
    }
  });
</script>
@endsection
