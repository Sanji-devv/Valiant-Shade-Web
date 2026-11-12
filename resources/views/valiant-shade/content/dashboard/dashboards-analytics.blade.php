@php
$configData = \App\ValiantShade\Helpers\Helpers::appClasses();
@endphp

@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
@vite(['resources/valiant-shade/assets/vendor/libs/apex-charts/apex-charts.scss',
'resources/valiant-shade/assets/vendor/libs/swiper/swiper.scss',
'resources/valiant-shade/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/valiant-shade/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'], 'static/valiant-shade/build')
@endsection
@section('page-style')
@vite('resources/valiant-shade/assets/vendor/scss/pages/cards-advance.scss', 'static/valiant-shade/build')
<style>
  /* News & Chronicles Slider - Enlarged Hero Presentation */
  .guild-news-swiper {
    border-radius: var(--bs-border-radius);
    overflow: hidden;
    position: relative;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
    background-color: #0b1120;
    min-height: 380px;
  }
  .guild-news-swiper .swiper-slide {
    white-space: normal !important;
    position: relative;
    min-height: 380px;
    padding: 0 !important;
    overflow: hidden;
  }
  .guild-news-slide-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transform: scale(1);
    transition: transform 1.2s cubic-bezier(0.25, 0.8, 0.25, 1);
    z-index: 1;
  }
  .guild-news-swiper:hover .guild-news-slide-bg {
    transform: scale(1.04);
  }
  .guild-news-slide-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(11, 17, 32, 0.98) 0%, rgba(11, 17, 32, 0.88) 45%, rgba(11, 17, 32, 0.45) 100%),
                linear-gradient(to top, rgba(11, 17, 32, 0.95) 0%, rgba(11, 17, 32, 0.2) 60%, transparent 100%);
    z-index: 2;
  }
  .guild-news-slide-content {
    position: relative;
    z-index: 3;
    padding: 2.25rem 2.5rem;
    min-height: 380px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .guild-stat-card {
    background: rgba(15, 23, 42, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 12px;
    padding: 0.85rem 1.1rem;
    transition: all 0.25s ease;
  }
  .guild-stat-card:hover {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(255, 255, 255, 0.35);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
  }
  .guild-news-nav-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(8px);
    color: #ffffff !important;
    opacity: 0;
    transition: all 0.25s ease;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
  }
  .guild-news-swiper:hover .guild-news-nav-btn {
    opacity: 0.9;
  }
  .guild-news-nav-btn:hover {
    opacity: 1 !important;
    background: rgba(15, 23, 42, 0.95);
    border-color: rgba(255, 255, 255, 0.6);
    color: #fbbf24 !important;
    box-shadow: 0 0 16px rgba(251, 191, 36, 0.45);
  }
  .guild-news-nav-btn::after {
    font-size: 14px !important;
    font-weight: 800;
  }
  .guild-news-swiper .swiper-pagination-bullets {
    inset-block: auto 1.5rem;
    inset-inline: auto 2.5rem;
    text-align: end;
    z-index: 10;
  }
  .guild-news-swiper .swiper-pagination-bullet {
    background: rgba(255, 255, 255, 0.4) !important;
    width: 9px;
    height: 9px;
    opacity: 1 !important;
    transition: all 0.3s ease;
  }
  .guild-news-swiper .swiper-pagination-bullet-active {
    background: #7367f0 !important;
    width: 26px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(115, 103, 240, 0.7);
  }
  .guild-news-tag-badge {
    background: rgba(15, 23, 42, 0.85) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff !important;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.2px;
    padding: 0.32rem 0.75rem !important;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
  }
  .guild-news-tag-badge .guild-news-tag-text {
    color: #ffffff !important;
    font-weight: 600;
  }
</style>
@endsection
@section('vendor-script')
@vite(['resources/valiant-shade/assets/vendor/libs/apex-charts/apexcharts.js',
'resources/valiant-shade/assets/vendor/libs/swiper/swiper.js',
'resources/valiant-shade/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'], 'static/valiant-shade/build')
@endsection

@section('page-script')
@vite('resources/valiant-shade/assets/js/dashboards-analytics.js', 'static/valiant-shade/build')
@endsection

@section('content')
<div class="row g-6">
  <!-- Guild News & Chronicles Slider (Enlarged Hero Banner) -->
  <div class="col-12">
    <div class="swiper-container swiper-container-horizontal swiper guild-news-swiper card w-100 border-0"
      id="swiper-with-pagination-cards">

      <div class="swiper-wrapper">
        @forelse($news as $newsItem)
        <div class="swiper-slide" data-swiper-autoplay="10000">
          <div class="guild-news-slide-bg" style="background-image: url('{{ $newsItem->image_asset }}');"></div>
          <div class="guild-news-slide-overlay"></div>
          <div class="guild-news-slide-content">
            <!-- Header Badges Row -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-{{ $newsItem->badge_color ?? 'primary' }} text-white fw-bold px-3 py-1_5 rounded-pill shadow-sm" style="font-size: 0.82rem;">
                  <i class="{{ $newsItem->category_icon ?: 'icon-base ti tabler-trophy' }} me-1"></i> {{ __($newsItem->category) }}
                </span>
                @if($newsItem->tag)
                <span class="guild-news-tag-badge py-1 px-2_5">
                  <i class="{{ $newsItem->tag_icon ?: 'icon-base ti tabler-sparkles' }} me-1 text-{{ $newsItem->badge_color ?? 'warning' }}"></i>
                  <span class="guild-news-tag-text">{{ __($newsItem->tag) }}</span>
                </span>
                @endif
                @if(!empty($newsItem->location_badge))
                <span class="badge bg-dark bg-opacity-75 text-white-50 border border-secondary border-opacity-25 px-2_5 py-1" style="font-size: 0.75rem;">
                  <i class="icon-base ti tabler-map-pin me-1 text-danger"></i> {{ __($newsItem->location_badge) }}
                </span>
                @endif
              </div>

              @if(!empty($newsItem->date_text))
              <div class="text-white-50 small d-flex align-items-center gap-1 bg-dark bg-opacity-50 px-3 py-1 rounded-pill border border-secondary border-opacity-25" style="font-size: 0.78rem;">
                <i class="icon-base ti tabler-clock text-warning"></i>
                <span>{{ __($newsItem->date_text) }}</span>
              </div>
              @endif
            </div>

            <!-- Two-Column Hero Layout -->
            <div class="row g-4 align-items-center my-auto py-2">
              <!-- Left: Title, Description, Meta & Action CTA -->
              <div class="col-xl-7 col-lg-8 col-12">
                <h3 class="text-white fw-bold mb-3 fs-3" style="text-shadow: 0 2px 10px rgba(0,0,0,0.85); line-height: 1.3;">
                  {{ __($newsItem->title) }}
                </h3>
                <p class="text-white text-opacity-85 mb-4" style="line-height: 1.65; font-size: 0.95rem; max-width: 95%;">
                  {{ __($newsItem->description) }}
                </p>

                <div class="d-flex align-items-center gap-3 flex-wrap pt-1">
                  @if(!empty($newsItem->button_text))
                  <a href="{{ $newsItem->button_url ? url($newsItem->button_url) : 'javascript:void(0);' }}" class="btn btn-{{ $newsItem->button_color ?? 'primary' }} d-inline-flex align-items-center gap-2 shadow px-4 py-2 fw-semibold" style="font-size: 0.88rem; border-radius: 8px;">
                    <i class="{{ $newsItem->button_icon ?: 'icon-base ti tabler-file-text' }} fs-5"></i>
                    <span>{{ __($newsItem->button_text) }}</span>
                  </a>
                  @endif
                  @if(!empty($newsItem->author))
                  <span class="text-white-50 small d-inline-flex align-items-center gap-1_5" style="font-size: 0.8rem;">
                    <i class="icon-base ti tabler-feather text-info"></i> {{ __($newsItem->author) }}
                    @if(!empty($newsItem->author_role))
                    <span class="text-white-50">• {{ __($newsItem->author_role) }}</span>
                    @endif
                  </span>
                  @endif
                </div>
              </div>

              <!-- Right: Rich Stat Cards (Dossier Panel) -->
              <div class="col-xl-5 col-lg-4 col-12">
                @if(!empty($newsItem->stats) && is_array($newsItem->stats))
                <div class="row g-3">
                  @foreach(array_slice($newsItem->stats, 0, 4) as $stat)
                  <div class="col-sm-6 col-12">
                    <div class="guild-stat-card">
                      <div class="text-white-50 small text-truncate mb-1" style="font-size: 0.78rem;">{{ __($stat['label'] ?? '') }}</div>
                      <div class="{{ $stat['color'] ?? 'text-warning' }} fw-bold font-monospace" style="font-size: 1.15rem; letter-spacing: -0.3px;">{{ $stat['value'] ?? '' }}</div>
                    </div>
                  </div>
                  @endforeach
                </div>
                @endif
              </div>
            </div>

            <!-- Bottom Spacer / Subtle Meta -->
            <div class="d-flex align-items-center justify-content-between pt-2">
              <span class="text-white-50 small" style="font-size: 0.75rem;">
                <i class="icon-base ti tabler-shield-check text-success me-1"></i>{{ __('Valiant Shade Guild Archive & Intelligence Bulletin') }}
              </span>
            </div>
          </div>
        </div>
        @empty
        <!-- Fallback if no news items -->
        <div class="swiper-slide" data-swiper-autoplay="10000">
          <div class="guild-news-slide-bg" style="background-image: url('{{ asset('static/valiant-shade/assets/img/news/guild_dragon_quest.jpg') }}');"></div>
          <div class="guild-news-slide-overlay"></div>
          <div class="guild-news-slide-content">
            <div>
              <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 fs-6"><i class="icon-base ti tabler-trophy me-1"></i> {{ __('Expedition Victory') }}</span>
              </div>
              <h3 class="text-white fw-bold mb-2 fs-3">{{ __('Dragon Peak and Ancient Ruins Conquest Completed!') }}</h3>
              <p class="text-white-50 mb-3" style="font-size: 0.95rem;">{{ __('Our veteran scout unit brought down the Ancient Dragon Ignis.') }}</p>
            </div>
          </div>
        </div>
        @endforelse
      </div>
      <div class="swiper-pagination"></div>
      <div class="swiper-button-prev guild-news-nav-btn"></div>
      <div class="swiper-button-next guild-news-nav-btn"></div>
    </div>
  </div>
  <!--/ Guild News & Chronicles Slider -->

  <!-- Guild Inventory 30-Day Real Chart -->
  <div class="col-xxl-6 col-xl-6 col-12 d-flex flex-column">
    <div class="card h-100 w-100 shadow-sm border-0">
      <div class="card-header pb-2 border-bottom border-secondary border-opacity-10">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-primary p-2 rounded-3"><i class="icon-base ti tabler-package-import icon-md"></i></span>
            <div>
              <h5 class="mb-0 card-title fw-bold fs-6">{{ __('Guild Inventory') }}</h5>
              <small class="text-body-secondary">{{ __('Current Inventory Value') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-primary font-monospace px-3 py-1_5" style="font-size: 0.78rem;">
              <i class="icon-base ti tabler-boxes me-1"></i>{{ $currentInventoryCount ?? 0 }} {{ __('Items') }}
            </span>
            <span class="badge bg-label-info font-monospace px-3 py-1_5" style="font-size: 0.78rem;">
              <i class="icon-base ti tabler-calendar-stats me-1"></i>30 {{ __('Days') }}
            </span>
          </div>
        </div>

        <div class="d-flex align-items-baseline justify-content-between flex-wrap gap-2 pt-1">
          <div>
            <h2 class="mb-0 fw-bold text-heading font-monospace">{{ number_format($currentInventorySp ?? 0, 0) }} <span class="fs-6 text-body-secondary fw-normal">SP</span></h2>
            <div class="text-warning fw-semibold font-monospace small mt-0_5">
              <i class="icon-base ti tabler-coin me-1"></i>~{{ number_format($currentInventoryGp ?? 0, 1) }} GP {{ __('(Gold Value)') }}
            </div>
          </div>
          <div class="text-end">
            <small class="text-body-secondary d-block">{{ __('Market Sale (55%)') }}</small>
            <strong class="text-heading font-monospace fs-6">~{{ number_format($currentInventoryMarketGp ?? 0, 0) }} GP</strong>
          </div>
        </div>
      </div>

      <div class="card-body px-3 pt-3 pb-2 d-flex flex-column justify-content-between">
        <!-- 30-Day Real ApexChart -->
        <div id="guildInventory30DayChart"
          data-dates="{{ json_encode($chartDates ?? []) }}"
          data-daily-sp="{{ json_encode($inventoryDailySp ?? []) }}"
          data-daily-gp="{{ json_encode($inventoryDailyGp ?? []) }}"
          data-cumulative-sp="{{ json_encode($inventoryCumulativeSp ?? []) }}"
          data-series-name="{{ __('Inventory Inflow (SP)') }}">
        </div>

        <div class="px-3 pt-3 border-top mx-1 d-flex align-items-center justify-content-between flex-wrap gap-2 small text-body-secondary mt-auto">
          <span><i class="icon-base ti tabler-chart-line text-primary me-1"></i>{{ __('30-Day Total Inflow') }}: <strong class="text-heading font-monospace">{{ number_format($inventoryLast30Sp ?? 0, 0) }} SP</strong></span>
          <span><i class="icon-base ti tabler-scale text-warning me-1"></i>{{ __('Market Value (55%)') }}: <strong class="text-warning font-monospace">~{{ number_format($inventoryLast30MarketGp ?? 0, 0) }} GP</strong></span>
          <span><i class="icon-base ti tabler-package text-info me-1"></i>{{ __('Total Items') }}: <strong class="text-primary font-monospace">{{ $inventoryLast30Count ?? 0 }}</strong></span>
        </div>
      </div>
    </div>
  </div>
  <!--/ Guild Inventory 30-Day Real Chart -->

  <!-- Vault Cash 30-Day Real Chart -->
  <div class="col-xxl-6 col-xl-6 col-12 d-flex flex-column">
    <div class="card h-100 w-100 shadow-sm border-0">
      <div class="card-header pb-2 border-bottom border-secondary border-opacity-10">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-warning p-2 rounded-3"><i class="icon-base ti tabler-building-bank icon-md"></i></span>
            <div>
              <h5 class="fw-bold fs-6 text-heading mb-0">{{ __('Vault Cash') }}</h5>
              <small class="text-body-secondary">{{ __('Last 30-Day Vault Reserve & Cash Flow') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-success font-monospace px-3 py-1_5" style="font-size: 0.78rem;">
              <i class="icon-base ti tabler-shield-check me-1"></i>{{ __('100% Active Liquid') }}
            </span>
            <span class="badge bg-label-warning font-monospace px-3 py-1_5" style="font-size: 0.78rem;">
              <i class="icon-base ti tabler-building-bank me-1"></i>{{ __('Guild Vault') }}
            </span>
          </div>
        </div>

        <div class="d-flex align-items-baseline justify-content-between flex-wrap gap-2 pt-1">
          <div>
            <h2 class="card-title mb-0 fw-bold text-heading font-monospace">{{ number_format($vaultCashSp ?? 0, 2) }} <span class="fs-6 text-body-secondary fw-normal">SP</span></h2>
            <div class="text-warning fw-semibold font-monospace small mt-0_5">
              <i class="icon-base ti tabler-coins me-1"></i>~{{ number_format($vaultCashGp ?? 0, 1) }} GP {{ __('(Gold Equivalent)') }}
            </div>
          </div>
          <div class="text-end">
            <small class="text-body-secondary d-block">{{ __('Physical Silver (SP)') }}</small>
            <strong class="text-info font-monospace fs-6">{{ number_format($vaultCashSp ?? 0, 2) }} SP</strong>
          </div>
        </div>
      </div>

      <div class="card-body px-3 pt-3 pb-2 d-flex flex-column justify-content-between">
        <!-- 30-Day Real ApexChart -->
        <div id="vaultCash30DayChart"
          data-dates="{{ json_encode($chartDates ?? []) }}"
          data-daily-sp="{{ json_encode($vaultDailySp ?? []) }}"
          data-daily-gp="{{ json_encode($vaultDailyGp ?? []) }}"
          data-series-name="{{ __('Vault Reserve (SP)') }}">
        </div>

        <div class="px-3 pt-3 border-top mx-1 mt-auto">
          <div class="row align-items-center g-2 p-2_5 rounded-3 bg-label-secondary bg-opacity-10 border border-secondary border-opacity-10 mb-2">
            <div class="col-6">
              <div class="d-flex gap-1_5 align-items-center mb-0_5">
                <span class="badge bg-label-warning p-1 rounded"><i class="icon-base ti tabler-coin icon-xs"></i></span>
                <span class="small fw-semibold">{{ __('Gold (GP)') }}</span>
              </div>
              <h4 class="mb-0 font-monospace text-warning fw-bold fs-5">{{ number_format($vaultCashGp ?? 0, 0) }}</h4>
              <small class="text-body-secondary" style="font-size: 0.72rem;">1 GP = 10 SP {{ __('(Gold Equivalent)') }}</small>
            </div>
            <div class="col-6 text-end">
              <div class="d-flex gap-1_5 justify-content-end align-items-center mb-0_5">
                <span class="small fw-semibold">{{ __('Silver (SP)') }}</span>
                <span class="badge bg-label-info p-1 rounded"><i class="icon-base ti tabler-coins icon-xs"></i></span>
              </div>
              <h4 class="mb-0 font-monospace text-info fw-bold fs-5">{{ number_format($vaultCashSp ?? 0, 2) }}</h4>
              <small class="text-body-secondary" style="font-size: 0.72rem;">{{ __('Physical Liquid Vault') }}</small>
            </div>
          </div>

          <div class="d-flex justify-content-between text-body-secondary small mb-1" style="font-size: 0.74rem;">
            <span class="d-flex align-items-center gap-1"><i class="icon-base ti tabler-shield-check text-success icon-xs"></i>{{ __('Liquid Vault Reserve') }}</span>
            <span class="fw-bold text-success font-monospace">{{ __('100% Secure Reserve') }}</span>
          </div>
          <div class="progress w-100" style="height: 6px; border-radius: 4px;">
            <div class="progress-bar bg-warning" style="width: 50%" role="progressbar" title="{{ __('Gold Reserve') }}"></div>
            <div class="progress-bar bg-info" style="width: 50%" role="progressbar" title="{{ __('Silver Reserve') }}"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Vault Cash 30-Day Real Chart -->

  <!-- Earning Reports -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header pb-0 d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-1">{{ __('Earning Reports') }}</h5>
          <p class="card-subtitle">{{ __('Weekly Earnings Overview') }}</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1" type="button"
            id="earningReportsId" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsId">
            <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="row align-items-center g-md-8">
          <div class="col-12 col-md-5 d-flex flex-column">
            <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
              <h2 class="mb-0">$468</h2>
              <div class="badge rounded bg-label-success">+4.2%</div>
            </div>
            <small class="text-body">{{ __('You informed of this week compared to last week') }}</small>
          </div>
          <div class="col-12 col-md-7 ps-xl-8">
            <div id="weeklyEarningReports"></div>
          </div>
        </div>
        <div class="border rounded p-5 mt-5">
          <div class="row gap-4 gap-sm-0">
            <div class="col-12 col-sm-4">
              <div class="d-flex gap-2 align-items-center">
                <div class="badge rounded bg-label-primary p-1"><i
                    class="icon-base ti tabler-currency-dollar icon-18px"></i></div>
                <h6 class="mb-0 fw-normal">{{ __('Earnings') }}</h6>
              </div>
              <h4 class="my-2">$545.69</h4>
              <div class="progress w-75" style="height:4px">
                <div class="progress-bar" role="progressbar" style="width: 65%" aria-valuenow="65" aria-valuemin="0"
                  aria-valuemax="100"></div>
              </div>
            </div>
            <div class="col-12 col-sm-4">
              <div class="d-flex gap-2 align-items-center">
                <div class="badge rounded bg-label-info p-1"><i class="icon-base ti tabler-chart-pie-2 icon-18px"></i>
                </div>
                <h6 class="mb-0 fw-normal">{{ __('Profit') }}</h6>
              </div>
              <h4 class="my-2">$256.34</h4>
              <div class="progress w-75" style="height:4px">
                <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50"
                  aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
            <div class="col-12 col-sm-4">
              <div class="d-flex gap-2 align-items-center">
                <div class="badge rounded bg-label-danger p-1"><i
                    class="icon-base ti tabler-brand-paypal icon-18px"></i></div>
                <h6 class="mb-0 fw-normal">{{ __('Expense') }}</h6>
              </div>
              <h4 class="my-2">$74.19</h4>
              <div class="progress w-75" style="height:4px">
                <div class="progress-bar bg-danger" role="progressbar" style="width: 65%" aria-valuenow="65"
                  aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Earning Reports -->

  <!-- Support Tracker -->
  <div class="col-12 col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-1">{{ __('Support Tracker') }}</h5>
          <p class="card-subtitle">{{ __('Last 7 Days') }}</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1" type="button"
            id="supportTrackerMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
            <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
          </div>
        </div>
      </div>
      <div class="card-body row">
        <div class="col-12 col-sm-4">
          <div class="mt-lg-4 mt-lg-2 mb-lg-6 mb-2">
            <h2 class="mb-0">164</h2>
            <p class="mb-0">{{ __('Total Tickets') }}</p>
          </div>
          <ul class="p-0 m-0">
            <li class="d-flex gap-4 align-items-center mb-lg-3 pb-1">
              <div class="badge rounded bg-label-primary p-1_5"><i class="icon-base ti tabler-ticket icon-md"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">{{ __('New Tickets') }}</h6>
                <small class="text-body-secondary">142</small>
              </div>
            </li>
            <li class="d-flex gap-4 align-items-center mb-lg-3 pb-1">
              <div class="badge rounded bg-label-info p-1_5"><i class="icon-base ti tabler-circle-check icon-md"></i>
              </div>
              <div>
                <h6 class="mb-0 text-nowrap">{{ __('Open Tickets') }}</h6>
                <small class="text-body-secondary">28</small>
              </div>
            </li>
            <li class="d-flex gap-4 align-items-center pb-1">
              <div class="badge rounded bg-label-warning p-1_5"><i class="icon-base ti tabler-clock icon-md"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">{{ __('Response Time') }}</h6>
                <small class="text-body-secondary">{{ __('1 Day') }}</small>
              </div>
            </li>
          </ul>
        </div>
        <div class="col-12 col-md-8">
          <div id="supportTracker"></div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Support Tracker -->

  <!-- Sales By Country -->
  <div class="col-xxl-4 col-md-6 order-1 order-xl-0">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-1">{{ __('Sales by Countries') }}</h5>
          <p class="card-subtitle">{{ __('Monthly Sales Overview') }}</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-text-secondary btn-icon rounded-pill text-body-secondary border-0 me-n1" type="button"
            id="salesByCountry" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti tabler-dots-vertical icon-22px text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="salesByCountry">
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Download') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Refresh') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Share') }}</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0">
          <li class="d-flex align-items-center mb-4">
            <div class="avatar flex-shrink-0 me-4">
              <i class="fis fi fi-us rounded-circle fs-2"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-1">$8,567k</h6>
                </div>
                <small class="text-body">{{ __('United States') }}</small>
              </div>
              <div class="user-progress">
                <p class="text-success fw-medium mb-0 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-chevron-up"></i>
                  25.8%
                </p>
              </div>
            </div>
          </li>
          <li class="d-flex align-items-center mb-4">
            <div class="avatar flex-shrink-0 me-4">
              <i class="fis fi fi-br rounded-circle fs-2"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-1">$2,415k</h6>
                </div>
                <small class="text-body">{{ __('Brazil') }}</small>
              </div>
              <div class="user-progress">
                <p class="text-danger fw-medium mb-0 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-chevron-down"></i>
                  6.2%
                </p>
              </div>
            </div>
          </li>
          <li class="d-flex align-items-center mb-4">
            <div class="avatar flex-shrink-0 me-4">
              <i class="fis fi fi-in rounded-circle fs-2"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-1">$865k</h6>
                </div>
                <small class="text-body">{{ __('India') }}</small>
              </div>
              <div class="user-progress">
                <p class="text-success fw-medium mb-0 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-chevron-up"></i>
                  12.4%
                </p>
              </div>
            </div>
          </li>
          <li class="d-flex align-items-center mb-4">
            <div class="avatar flex-shrink-0 me-4">
              <i class="fis fi fi-au rounded-circle fs-2"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-1">$745k</h6>
                </div>
                <small class="text-body">{{ __('Australia') }}</small>
              </div>
              <div class="user-progress">
                <p class="text-danger fw-medium mb-0 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-chevron-down"></i>
                  11.9%
                </p>
              </div>
            </div>
          </li>
          <li class="d-flex align-items-center mb-4">
            <div class="avatar flex-shrink-0 me-4">
              <i class="fis fi fi-fr rounded-circle fs-2"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-1">$45</h6>
                </div>
                <small class="text-body">{{ __('France') }}</small>
              </div>
              <div class="user-progress">
                <p class="text-success fw-medium mb-0 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-chevron-up"></i>
                  16.2%
                </p>
              </div>
            </div>
          </li>
          <li class="d-flex align-items-center">
            <div class="avatar flex-shrink-0 me-4">
              <i class="fis fi fi-cn rounded-circle fs-2"></i>
            </div>
            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
              <div class="me-2">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-1">$12k</h6>
                </div>
                <small class="text-body">{{ __('China') }}</small>
              </div>
              <div class="user-progress">
                <p class="text-success fw-medium mb-0 d-flex align-items-center gap-1">
                  <i class="icon-base ti tabler-chevron-up"></i>
                  14.8%
                </p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Sales By Country -->

  <!-- Total Earning -->
  <div class="col-12 col-md-6 col-xxl-4 order-2 order-xl-0">
    <div class="card h-100">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0 card-title">{{ __('Total Earning') }}</h5>
          <div class="dropdown">
            <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1" type="button"
              id="totalEarning" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalEarning">
              <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
              <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center">
          <h2 class="mb-0 me-2">87%</h2>
          <i class="icon-base ti tabler-chevron-up text-success me-1"></i>
          <h6 class="text-success mb-0">25.8%</h6>
        </div>
      </div>
      <div class="card-body">
        <div id="totalEarningChart"></div>
        <div class="d-flex align-items-start my-4">
          <div class="badge rounded bg-label-primary p-2 me-4 rounded"><i
              class="icon-base ti tabler-brand-paypal icon-md"></i></div>
          <div class="d-flex justify-content-between w-100 gap-2 align-items-center">
            <div class="me-2">
              <h6 class="mb-0">{{ __('Total Revenue') }}</h6>
              <small class="text-body">{{ __('Client Payment') }}</small>
            </div>
            <h6 class="mb-0 text-success">+$126</h6>
          </div>
        </div>
        <div class="d-flex align-items-start">
          <div class="badge rounded bg-label-secondary p-2 me-4 rounded"><i
              class="icon-base ti tabler-currency-dollar icon-md"></i></div>
          <div class="d-flex justify-content-between w-100 gap-2 align-items-center">
            <div class="me-2">
              <h6 class="mb-0">{{ __('Total Sales') }}</h6>
              <small class="text-body">{{ __('Refund') }}</small>
            </div>
            <h6 class="mb-0 text-success">+$98</h6>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Total Earning -->

  <!-- Monthly Campaign State -->
  <div class="col-xxl-4 col-md-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-1">{{ __('Monthly Campaign State') }}</h5>
          <p class="card-subtitle">{{ __('8.52k Social Visitors') }}</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1" type="button"
            id="MonthlyCampaign" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="MonthlyCampaign">
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Refresh') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Download') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('View All') }}</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ul class="p-0 m-0">
          <li class="mb-6 d-flex justify-content-between align-items-center">
            <div class="badge bg-label-success rounded p-1_5"><i class="icon-base ti tabler-mail icon-md"></i></div>
            <div class="d-flex justify-content-between w-100 flex-wrap">
              <h6 class="mb-0 ms-4">{{ __('Emails') }}</h6>
              <div class="d-flex">
                <p class="mb-0">12,346</p>
                <p class="ms-4 text-success mb-0">0.3%</p>
              </div>
            </div>
          </li>
          <li class="mb-6 d-flex justify-content-between align-items-center">
            <div class="badge bg-label-info rounded p-1_5"><i class="icon-base ti tabler-link icon-md"></i></div>
            <div class="d-flex justify-content-between w-100 flex-wrap">
              <h6 class="mb-0 ms-4">{{ __('Opened') }}</h6>
              <div class="d-flex">
                <p class="mb-0">8,734</p>
                <p class="ms-4 text-success mb-0">2.1%</p>
              </div>
            </div>
          </li>
          <li class="mb-6 d-flex justify-content-between align-items-center">
            <div class="badge bg-label-warning rounded p-1_5"><i class="icon-base ti tabler-mouse icon-md"></i></div>
            <div class="d-flex justify-content-between w-100 flex-wrap">
              <h6 class="mb-0 ms-4">{{ __('Clicked') }}</h6>
              <div class="d-flex">
                <p class="mb-0">967</p>
                <p class="ms-4 text-danger mb-0">1.4%</p>
              </div>
            </div>
          </li>
          <li class="mb-6 d-flex justify-content-between align-items-center">
            <div class="badge bg-label-primary rounded p-1_5"><i class="icon-base ti tabler-users icon-md"></i></div>
            <div class="d-flex justify-content-between w-100 flex-wrap">
              <h6 class="mb-0 ms-4">{{ __('Subscribe') }}</h6>
              <div class="d-flex">
                <p class="mb-0">345</p>
                <p class="ms-4 text-success mb-0">8.5%</p>
              </div>
            </div>
          </li>
          <li class="mb-6 d-flex justify-content-between align-items-center">
            <div class="badge bg-label-secondary rounded p-1_5"><i
                class="icon-base ti tabler-alert-triangle icon-md"></i></div>
            <div class="d-flex justify-content-between w-100 flex-wrap">
              <h6 class="mb-0 ms-4">{{ __('Complaints') }}</h6>
              <div class="d-flex">
                <p class="mb-0">10</p>
                <p class="ms-4 text-danger mb-0">1.5%</p>
              </div>
            </div>
          </li>
          <li class="mb-3 d-flex justify-content-between align-items-center">
            <div class="badge bg-label-danger rounded p-1_5"><i class="icon-base ti tabler-ban icon-md"></i></div>
            <div class="d-flex justify-content-between w-100 flex-wrap">
              <h6 class="mb-0 ms-4">{{ __('Unsubscribe') }}</h6>
              <div class="d-flex">
                <p class="mb-0">86</p>
                <p class="ms-4 text-success mb-0">0.8%</p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Monthly Campaign State -->

  <!-- Source Visit -->
  <div class="col-xxl-4 col-md-6 col-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-1">{{ __('Source Visits') }}</h5>
          <p class="card-subtitle">{{ __('38.4k Visitors') }}</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1" type="button"
            id="sourceVisits" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="sourceVisits">
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Refresh') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('Download') }}</a>
            <a class="dropdown-item" href="javascript:void(0);">{{ __('View All') }}</a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="mb-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-secondary text-body p-2 me-4 rounded"><i
                  class="icon-base ti tabler-shadow icon-md"></i></div>
              <div class="d-flex justify-content-between w-100 flex-wrap gap-2">
                <div class="me-2">
                  <h6 class="mb-0">{{ __('Direct Source') }}</h6>
                  <small class="text-body">{{ __('Direct link click') }}</small>
                </div>
                <div class="d-flex align-items-center">
                  <p class="mb-0">1.2k</p>
                  <div class="ms-4 badge bg-label-success">+4.2%</div>
                </div>
              </div>
            </div>
          </li>
          <li class="mb-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-secondary text-body p-2 me-4 rounded"><i
                  class="icon-base ti tabler-globe icon-md"></i></div>
              <div class="d-flex justify-content-between w-100 flex-wrap gap-2">
                <div class="me-2">
                  <h6 class="mb-0">{{ __('Social Network') }}</h6>
                  <small class="text-body">{{ __('Social Channels') }}</small>
                </div>
                <div class="d-flex align-items-center">
                  <p class="mb-0">31.5k</p>
                  <div class="ms-4 badge bg-label-success">+8.2%</div>
                </div>
              </div>
            </div>
          </li>
          <li class="mb-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-secondary text-body p-2 me-4 rounded"><i
                  class="icon-base ti tabler-mail icon-md"></i></div>
              <div class="d-flex justify-content-between w-100 flex-wrap gap-2">
                <div class="me-2">
                  <h6 class="mb-0">{{ __('Email Newsletter') }}</h6>
                  <small class="text-body">{{ __('Mail Campaigns') }}</small>
                </div>
                <div class="d-flex align-items-center">
                  <p class="mb-0">893</p>
                  <div class="ms-4 badge bg-label-success">+2.4%</div>
                </div>
              </div>
            </div>
          </li>
          <li class="mb-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-secondary text-body p-2 me-4 rounded"><i
                  class="icon-base ti tabler-external-link icon-md"></i></div>
              <div class="d-flex justify-content-between w-100 flex-wrap gap-2">
                <div class="me-2">
                  <h6 class="mb-0">{{ __('Referrals') }}</h6>
                  <small class="text-body">{{ __('Impact Radius Visits') }}</small>
                </div>
                <div class="d-flex align-items-center">
                  <p class="mb-0">342</p>
                  <div class="ms-4 badge bg-label-danger">-0.4%</div>
                </div>
              </div>
            </div>
          </li>
          <li class="mb-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-secondary text-body p-2 me-4 rounded"><i
                  class="icon-base ti tabler-ad icon-md"></i></div>
              <div class="d-flex justify-content-between w-100 flex-wrap gap-2">
                <div class="me-2">
                  <h6 class="mb-0">{{ __('ADVT') }}</h6>
                  <small class="text-body">{{ __('Google ADVT') }}</small>
                </div>
                <div class="d-flex align-items-center">
                  <p class="mb-0">2.15k</p>
                  <div class="ms-4 badge bg-label-success">+9.1%</div>
                </div>
              </div>
            </div>
          </li>
          <li>
            <div class="d-flex align-items-center">
              <div class="badge bg-label-secondary text-body p-2 me-4 rounded"><i
                  class="icon-base ti tabler-star icon-md"></i></div>
              <div class="d-flex justify-content-between w-100 flex-wrap gap-2">
                <div class="me-2">
                  <h6 class="mb-0">{{ __('Other') }}</h6>
                  <small class="text-body">{{ __('Many Sources') }}</small>
                </div>
                <div class="d-flex align-items-center">
                  <p class="mb-0">12.5k</p>
                  <div class="ms-4 badge bg-label-success">+6.2%</div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Source Visit -->

  <!-- Projects table -->
  <div class="col-xxl-8">
    <div class="card">
      <div class="table-responsive mb-4">
        <table class="table datatable-project table-sm">
          <thead class="border-top">
            <tr>
              <th></th>
              <th></th>
              <th>{{ __('Project') }}</th>
              <th>{{ __('Leader') }}</th>
              <th>{{ __('Team') }}</th>
              <th class="w-px-200">{{ __('Progress') }}</th>
              <th>{{ __('Action') }}</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
  <!--/ Projects table -->
</div>
@endsection