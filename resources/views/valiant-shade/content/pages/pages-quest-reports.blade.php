@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Quest Reports') . ' - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* ====================================================
     PREMIUM RPG TABLE DARK & MISSION DOSSIER STYLES
     ==================================================== */
  
  .reports-hero-banner {
    background: linear-gradient(135deg, rgba(21, 24, 43, 0.96) 0%, rgba(32, 35, 62, 0.92) 50%, rgba(20, 22, 42, 0.97) 100%);
    border: 1px solid rgba(245, 158, 11, 0.35);
    border-radius: 16px;
    box-shadow: 0 10px 32px -6px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
  }
  .reports-hero-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #f59e0b 20%, #7367f0 80%, transparent);
  }

  .kpi-stat-card {
    background: rgba(16, 18, 32, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
  }
  .kpi-stat-card:hover {
    transform: translateY(-2px);
    border-color: rgba(115, 103, 240, 0.4);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    background: rgba(22, 25, 45, 0.88);
  }
  .kpi-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }
  .kpi-icon-gold {
    background: rgba(245, 158, 11, 0.16);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.35);
    box-shadow: 0 0 16px rgba(245, 158, 11, 0.2);
  }
  .kpi-icon-purple {
    background: rgba(115, 103, 240, 0.16);
    color: #7367f0;
    border: 1px solid rgba(115, 103, 240, 0.35);
    box-shadow: 0 0 16px rgba(115, 103, 240, 0.2);
  }
  .kpi-icon-cyan {
    background: rgba(0, 186, 209, 0.16);
    color: #00bad1;
    border: 1px solid rgba(0, 186, 209, 0.35);
    box-shadow: 0 0 16px rgba(0, 186, 209, 0.2);
  }
  .kpi-icon-green {
    background: rgba(40, 199, 111, 0.16);
    color: #28c76f;
    border: 1px solid rgba(40, 199, 111, 0.35);
    box-shadow: 0 0 16px rgba(40, 199, 111, 0.2);
  }

  /* Toolbar */
  .reports-toolbar {
    background: rgba(18, 20, 36, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 0.9rem 1.2rem;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(10px);
  }
  .filter-pill-btn {
    background: transparent;
    color: #9aa0c0;
    border: none;
    border-radius: 8px;
    padding: 0.45rem 0.95rem;
    font-size: 0.82rem;
    font-weight: 600;
    transition: all 0.18s ease;
    cursor: pointer;
  }
  .filter-pill-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.07);
  }
  .filter-pill-btn.active {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 12px rgba(115, 103, 240, 0.42);
  }

  /* Add Quest Report Button */
  .btn-add-quest-report {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    border: 1px solid rgba(115, 103, 240, 0.6) !important;
    border-radius: 10px !important;
    font-weight: 700 !important;
    font-size: 0.88rem !important;
    padding: 0.55rem 1.25rem !important;
    box-shadow: 0 4px 16px rgba(115, 103, 240, 0.4) !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-add-quest-report:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(115, 103, 240, 0.6) !important;
    filter: brightness(1.1);
  }

  /* Table Dark Card Container */
  .card-table-dark-container {
    background: #181b2f !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 14px !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35) !important;
    overflow: hidden;
  }
  .card-table-dark-container .card-header {
    background: rgba(22, 25, 45, 0.9) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    padding: 1.15rem 1.45rem !important;
  }

  /* Table Dark custom styling matching Vuexy */
  .table-quest-dark {
    --bs-table-bg: transparent;
    --bs-table-color: #cbd0ec;
    border-color: rgba(255, 255, 255, 0.06);
    margin-bottom: 0;
  }
  .table-quest-dark thead th {
    background: #1e223b !important;
    color: #8f95b5 !important;
    font-size: 0.74rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.8px !important;
    font-weight: 700 !important;
    padding: 0.95rem 1.35rem !important;
    border-bottom: 2px solid rgba(115, 103, 240, 0.3) !important;
  }
  .table-quest-dark tbody tr.quest-row-clickable {
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }
  .table-quest-dark tbody tr.quest-row-clickable:hover {
    background: rgba(115, 103, 240, 0.12) !important;
    transform: scale(1.002);
  }
  .table-quest-dark tbody td {
    padding: 1.1rem 1.35rem;
    vertical-align: middle;
  }

  /* Badges */
  .badge-epic {
    background: rgba(245, 158, 11, 0.18);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.45);
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
  }
  .badge-main {
    background: rgba(115, 103, 240, 0.18);
    color: #7367f0;
    border: 1px solid rgba(115, 103, 240, 0.45);
    box-shadow: 0 0 10px rgba(115, 103, 240, 0.2);
  }
  .badge-side {
    background: rgba(130, 134, 158, 0.16);
    color: #9ba0bc;
    border: 1px solid rgba(130, 134, 158, 0.35);
  }

  /* Modal styling */
  .modal-dossier {
    background: linear-gradient(135deg, #181a2e 0%, #1f223a 100%);
    border: 1px solid rgba(245, 158, 11, 0.35);
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85);
    color: #e2e5f5;
  }
  .modal-dossier .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.09);
    position: relative;
  }
  .modal-dossier .modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #7367f0);
  }
  .modal-dossier .modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.09);
  }
  .report-section-box {
    background: rgba(14, 16, 28, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 1.1rem;
    margin-bottom: 1rem;
  }

  /* Add player row inside modal */
  .modal-party-row {
    background: rgba(22, 25, 45, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 0.65rem 0.85rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
  }
  .modal-party-row:hover {
    border-color: rgba(115, 103, 240, 0.3);
    background: rgba(26, 30, 55, 0.8);
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- ====================================================
       1. HERO KPI CARDS ("TOPLAMDA KAÇ RAPOR VAR")
       ==================================================== -->
  <div class="reports-hero-banner p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="avatar avatar-sm bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-scroll fs-4 text-warning"></i>
          </div>
          <h3 class="mb-0 fw-bold text-heading" style="font-size: 1.45rem; letter-spacing: 0.3px;">
            {{ __('Quest Reports') }}
          </h3>
          <span class="badge bg-primary font-monospace px-3 py-1 shadow-sm fs-6" id="kpiTotalReportsBadge">
            {{ $stats['total_quests'] }} {{ __('Reports Listed') }}
          </span>
        </div>
        <p class="text-body-secondary small mb-0">
          {{ __('Guild Mission Intelligence & Debriefing Archives') }} &bull; {{ __('Chronicles of all completed quests, threat assessments, and battle reports.') }}
        </p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <!-- New Quest Report Button (Prominent) -->
        <button type="button" class="btn btn-add-quest-report" id="btnOpenAddReportModal">
          <i class="icon-base ti tabler-plus fs-5"></i>
          <span>{{ __('Add Quest Report') }}</span>
        </button>

        <a href="{{ route('valiant-shade.pages-quest-board') }}" class="btn btn-outline-secondary btn-sm waves-effect">
          <i class="icon-base ti tabler-clipboard-list me-1"></i> {{ __('Quest Board') }}
        </a>
      </div>
    </div>

    <!-- 6 KPI Metric Cards Grid -->
    <div class="row g-3">
      <!-- KPI 1: Total Expeditions -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-purple shadow-sm">
            <i class="icon-base ti tabler-compass"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Total Expeditions') }}</div>
            <div class="fw-bolder fs-5 text-heading font-monospace" id="kpiTotalQuestsVal">{{ $stats['total_quests'] }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 2: Main Quests -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-gold shadow-sm">
            <i class="icon-base ti tabler-crown"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Main Quests') }}</div>
            <div class="fw-bolder fs-5 text-warning font-monospace" id="kpiMainQuestsVal">{{ $stats['main_quests'] }} <span class="small text-muted font-normal" style="font-size: 0.72rem;">({{ round(($stats['main_quests'] / max($stats['total_quests'], 1)) * 100) }}%)</span></div>
          </div>
        </div>
      </div>

      <!-- KPI 3: Side Quests -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-cyan shadow-sm">
            <i class="icon-base ti tabler-sparkles"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Side Quests') }}</div>
            <div class="fw-bolder fs-5 text-info font-monospace" id="kpiSideQuestsVal">{{ $stats['side_quests'] }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 4: Total Experience -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-green shadow-sm">
            <i class="icon-base ti tabler-award"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Total Experience') }}</div>
            <div class="fw-bolder fs-5 text-success font-monospace" id="kpiTotalXpVal">+{{ number_format($stats['total_xp']) }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 5: Average Party Level -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-cyan shadow-sm">
            <i class="icon-base ti tabler-sword"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Average Level') }}</div>
            <div class="fw-bolder fs-5 text-info font-monospace" id="kpiAvgLevelVal">{{ number_format($stats['avg_level'] ?? $stats['overall_avg_level'] ?? 1.25, 2) }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 6: Top Game Master -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-purple shadow-sm">
            <i class="icon-base ti tabler-dice"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Top Game Master') }}</div>
            <div class="fw-bolder fs-5 text-heading font-monospace">{{ $stats['top_gm'] ?? 'Shalvaz' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ====================================================
       2. REFINED TOOLBAR (GM & LEVEL FILTER REMOVED)
       ==================================================== -->
  <div class="reports-toolbar mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <!-- Search Input -->
      <div class="flex-grow-1" style="min-width: 240px; max-width: 420px;">
        <div class="input-group input-group-merge">
          <span class="input-group-text bg-transparent border-secondary border-opacity-25"><i class="icon-base ti tabler-search text-muted"></i></span>
          <input type="text" class="form-control bg-transparent border-secondary border-opacity-25 text-heading" id="reportsSearchInput" placeholder="{{ __('Search quests, heroes, GMs, locations...') }}" value="{{ $filters['search'] ?? '' }}" />
          <button type="button" class="btn btn-outline-secondary border-secondary border-opacity-25 d-none" id="btnReportsSearchClear" title="{{ __('Clear Search') }}">
            <i class="icon-base ti tabler-x fs-6"></i>
          </button>
        </div>
      </div>

      <!-- Quick Type Filter Buttons -->
      <div class="d-flex align-items-center gap-1 bg-dark bg-opacity-50 p-1 rounded-3 border border-secondary border-opacity-20" id="reportsTypeFilters">
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? 'all') === 'all' ? 'active' : '' }}" data-type="all">
          {{ __('All Quests') }} (<span id="typeCountAll">{{ $stats['total_quests'] }}</span>)
        </button>
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? '') === 'main' ? 'active' : '' }}" data-type="main">
          <i class="icon-base ti tabler-crown me-1 text-warning"></i> {{ __('Main Quests') }} (<span id="typeCountMain">{{ $stats['main_quests'] }}</span>)
        </button>
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? '') === 'side' ? 'active' : '' }}" data-type="side">
          {{ __('Side Quests') }} (<span id="typeCountSide">{{ $stats['side_quests'] }}</span>)
        </button>
      </div>

      <!-- Sort & Action Buttons -->
      <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
        <!-- Sort Select -->
        <select class="form-select form-select-sm bg-transparent border-secondary border-opacity-25 text-heading" id="reportsSortSelect" style="width: auto;">
          <option value="date-desc">{{ __('Date: Newest') }}</option>
          <option value="date-asc">{{ __('Date: Oldest') }}</option>
          <option value="xp-desc">{{ __('XP: Highest') }}</option>
        </select>

        <!-- Reset Button -->
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetReportsFilters" title="{{ __('Reset Filters') }}">
          <i class="icon-base ti tabler-rotate-clockwise"></i>
        </button>

        <!-- Görev Raporu Ekle (Toolbar Quick Button) -->
        <button type="button" class="btn btn-sm btn-add-quest-report" id="btnToolbarAddReport">
          <i class="icon-base ti tabler-plus fs-6"></i>
          <span>{{ __('Add Quest Report') }}</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ====================================================
       3. TABLE DARK ("DATA'DA BULUNANLAR GİBİ UZUNLAMASINA")
       ==================================================== -->
  <div class="card card-table-dark-container mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-table text-primary fs-4"></i>
        <h5 class="mb-0 fw-bold text-heading" style="font-size: 1.1rem;">
          {{ __('Quest Reports') }}
        </h5>
        <span class="badge bg-primary font-monospace ms-2" id="currentFilteredCount">
          {{ count($reports) }}
        </span>
        <span class="text-body-secondary small">/ <span id="totalReportsSpan">{{ $stats['total_quests'] }}</span> {{ __('Reports Listed') }}</span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-label-info font-monospace small">
          <i class="icon-base ti tabler-pointer me-1"></i>{{ __('Click row for full mission report') }}
        </span>
      </div>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-dark table-quest-dark">
        <thead>
          <tr>
            <th>{{ __('Mission Name') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Quest Type') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="text-end">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0" id="reportsTableBody">
          @forelse($reports as $q)
            <tr class="quest-row-clickable report-row-item"
                data-id="{{ $q['id'] }}"
                data-type="{{ $q['is_main'] ? 'main' : 'side' }}"
                data-gm="{{ mb_strtolower($q['gm'] ?? '') }}"
                data-level="{{ $q['level'] ?? 1 }}"
                data-xp="{{ $q['total_xp'] ?? 0 }}"
                data-date="{{ $q['date'] }}"
                title="{{ __('Click row for full mission report') }}">
              
              <!-- 1. GÖREVİN İSMİ -->
              <td>
                <div class="d-flex align-items-center">
                  @if($q['tier_type'] === 'epic')
                    <div class="avatar avatar-sm bg-label-warning rounded-3 me-3 d-flex align-items-center justify-content-center">
                      <i class="icon-base ti tabler-crown fs-5 text-warning"></i>
                    </div>
                  @elseif($q['is_main'])
                    <div class="avatar avatar-sm bg-label-primary rounded-3 me-3 d-flex align-items-center justify-content-center">
                      <i class="icon-base ti tabler-shield-check fs-5 text-primary"></i>
                    </div>
                  @else
                    <div class="avatar avatar-sm bg-label-secondary rounded-3 me-3 d-flex align-items-center justify-content-center">
                      <i class="icon-base ti tabler-scroll fs-5 text-muted"></i>
                    </div>
                  @endif

                  <div>
                    <span class="fw-bold text-heading d-block" style="font-size: 0.96rem;">{{ $q['title'] }}</span>
                    <small class="text-body-secondary font-monospace"><i class="icon-base ti tabler-map-pin text-danger opacity-75 me-1"></i>{{ $q['location'] }}</small>
                  </div>
                </div>
              </td>

              <!-- 2. TARİHİ -->
              <td>
                <span class="font-monospace text-body-secondary fw-semibold" style="font-size: 0.9rem;">
                  <i class="icon-base ti tabler-calendar-time text-primary me-1 fs-6"></i>{{ $q['date'] }}
                </span>
              </td>

              <!-- 3. MAIN QUEST MI? -->
              <td>
                @if($q['tier_type'] === 'epic')
                  <span class="badge badge-epic font-monospace fw-bold px-3 py-1">
                    <i class="icon-base ti tabler-crown me-1"></i>{{ __('Epic Main Quest') }} (✓✓)
                  </span>
                @elseif($q['is_main'])
                  <span class="badge badge-main font-monospace fw-bold px-3 py-1">
                    <i class="icon-base ti tabler-shield-check me-1"></i>{{ __('Main Quest') }} (✓)
                  </span>
                @else
                  <span class="badge badge-side font-monospace px-3 py-1">
                    <i class="icon-base ti tabler-point me-1"></i>{{ __('Side Quest') }} (x)
                  </span>
                @endif
              </td>

              <!-- 4. GÖREV BAŞARILI MI? -->
              <td>
                @if(($q['status'] ?? 'success') === 'failed')
                  <span class="badge bg-label-danger text-danger border border-danger border-opacity-25 px-3 py-1 font-monospace fw-semibold">
                    <i class="icon-base ti tabler-circle-x me-1"></i>{{ __('Failed') }}
                  </span>
                @else
                  <span class="badge bg-label-success text-success border border-success border-opacity-25 px-3 py-1 font-monospace fw-semibold">
                    <i class="icon-base ti tabler-circle-check me-1"></i>{{ __('Successful') }}
                  </span>
                @endif
              </td>

              <!-- 5. DETAYLAR TUŞU -->
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary btn-open-report" data-id="{{ $q['id'] }}">
                  <i class="icon-base ti tabler-file-text me-1 fs-6"></i>
                  <span>{{ __('Details') }}</span>
                  <i class="icon-base ti tabler-chevron-right ms-1 fs-6"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5">
                <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-file-search fs-1 text-muted"></i>
                </div>
                <h5 class="fw-bold text-heading">{{ __('No quest reports found matching your criteria.') }}</h5>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Empty Search Result Container -->
  <div id="reportsEmptyState" class="card border border-secondary border-opacity-20 text-center py-5 d-none mb-4" style="background: rgba(18, 20, 36, 0.85);">
    <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
      <i class="icon-base ti tabler-file-search fs-1 text-muted"></i>
    </div>
    <h5 class="fw-bold text-heading">{{ __('No quest reports found matching your criteria.') }}</h5>
    <p class="text-body-secondary small mb-3">Filtrelerinizi temizleyerek veya farklı bir arama terimi deneyerek tekrar arayabilirsiniz.</p>
    <div>
      <button type="button" class="btn btn-outline-primary btn-sm" id="btnEmptyResetFilters">
        <i class="icon-base ti tabler-rotate-clockwise me-1"></i> {{ __('Reset Filters') }}
      </button>
    </div>
  </div>

</div>

<!-- ====================================================
     4. MODAL: GÖREV RAPORU EKLE (NEW QUEST REPORT)
     ==================================================== -->
<div class="modal fade" id="modalAddQuestReport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-dossier">
      <form id="formAddQuestReport">
        @csrf
        <!-- Modal Header -->
        <div class="modal-header d-flex align-items-center justify-content-between p-3 p-md-4">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-label-primary rounded-3 d-flex align-items-center justify-content-center shadow-md">
              <i class="icon-base ti tabler-scroll fs-3 text-primary"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-bold text-heading">{{ __('Create Quest Report') }}</h4>
              <small class="text-body-secondary font-monospace">{{ __('Record new expedition debrief & calculate party level') }}</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-3 p-md-4">
          <!-- Row 1: Görev İsmi & Lokasyon -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-heading">{{ __('Mission Name') }} <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-sword text-primary"></i></span>
                <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="title" id="addReportTitle" placeholder="Örn: Gölge Diyar Kuşatması" required />
              </div>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-heading">{{ __('Quest Location') }} <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-map-pin text-danger"></i></span>
                <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="location" id="addReportLocation" placeholder="Örn: Gölge Diyar, Siwion Ormanı" required />
              </div>
            </div>
          </div>

          <!-- Row 2: Tarih, Quest Tipi & Status -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-heading">{{ __('Date') }} <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-calendar text-info"></i></span>
                <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace" name="date" id="addReportDate" value="{{ date('d.m.Y') }}" required />
              </div>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-heading">{{ __('Quest Type') }} <span class="text-danger">*</span></label>
              <select class="form-select bg-dark border-secondary border-opacity-25 text-heading" name="tier_type" id="addReportTierType" required>
                <option value="main">👑 {{ __('Main Quest') }} (✓)</option>
                <option value="epic">👑👑 {{ __('Epic Main Quest') }} (✓✓)</option>
                <option value="side" selected>📜 {{ __('Side Quest') }} (x)</option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-heading">{{ __('Status') }} <span class="text-danger">*</span></label>
              <select class="form-select bg-dark border-secondary border-opacity-25 text-heading" name="status" id="addReportStatus" required>
                <option value="success" selected>✓ {{ __('Successful') }}</option>
                <option value="failed">x {{ __('Failed') }}</option>
              </select>
            </div>
          </div>

          <!-- LIVE CALCULATED PARTY LEVEL BANNER -->
          <div class="alert alert-primary bg-label-primary border border-primary border-opacity-30 d-flex align-items-center justify-content-between p-3 rounded-3 mb-3 shadow-sm">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar avatar-xs bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                <i class="icon-base ti tabler-sword fs-6"></i>
              </div>
              <div>
                <span class="fw-bold text-heading d-block" style="font-size: 0.92rem;">{{ __('Calculated Game Level') }}</span>
                <small class="text-body-secondary">{{ __('Bütün oyuncuların seviyelerinin dinamik ortalaması') }}</small>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-primary fs-6 font-monospace px-3 py-2 shadow-sm" id="liveAddAvgLevelBadge">
                Ortalama Seviye: 1.00
              </span>
            </div>
          </div>

          <!-- Row 3: Katılan Oyuncular & Seviyeleri (Dynamic Roster) -->
          <div class="report-section-box mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div>
                <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-users-group text-primary"></i>
                  <span>{{ __('Party Members & Levels') }}</span>
                </h6>
                <small class="text-body-secondary">Oyuncuların seviyelerini girdikçe ortalama parti seviyesi otomatik hesaplanır.</small>
              </div>
              <button type="button" class="btn btn-xs btn-outline-primary" id="btnAddPartyRow">
                <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add Player') }}
              </button>
            </div>

            <!-- Dynamic Party Rows Container -->
            <div id="addReportPartyContainer">
              <!-- Initial 4 Default Rows -->
              <div class="modal-party-row d-flex align-items-center gap-2 flex-wrap" data-row="0">
                <div class="flex-grow-1" style="min-width: 140px;">
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading party-input-name" placeholder="Karakter Adı" value="Caspian" required />
                </div>
                <div style="width: 130px;">
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-class">
                    <option value="Paladin" selected>Paladin</option>
                    <option value="Fighter">Fighter</option>
                    <option value="Wizard">Wizard</option>
                    <option value="Cleric">Cleric</option>
                    <option value="Rogue">Rogue</option>
                    <option value="Ranger">Ranger</option>
                    <option value="Barbarian">Barbarian</option>
                    <option value="Bard">Bard</option>
                    <option value="Sorcerer">Sorcerer</option>
                    <option value="Warlock">Warlock</option>
                    <option value="Druid">Druid</option>
                    <option value="Monk">Monk</option>
                  </select>
                </div>
                <div style="width: 100px;">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted px-2">Lvl</span>
                    <input type="number" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace party-input-level" min="1" max="20" value="1" required />
                  </div>
                </div>
                <div style="width: 110px;">
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-role">
                    <option value="Reporter" selected>Lider / Rep.</option>
                    <option value="Member">Üye</option>
                  </select>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-label-danger border-0 btn-remove-party-row" title="Kaldır">
                  <i class="icon-base ti tabler-trash fs-6"></i>
                </button>
              </div>

              <div class="modal-party-row d-flex align-items-center gap-2 flex-wrap" data-row="1">
                <div class="flex-grow-1" style="min-width: 140px;">
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading party-input-name" placeholder="Karakter Adı" value="Lyra" required />
                </div>
                <div style="width: 130px;">
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-class">
                    <option value="Wizard">Wizard</option>
                    <option value="Rogue" selected>Rogue</option>
                    <option value="Fighter">Fighter</option>
                    <option value="Cleric">Cleric</option>
                    <option value="Paladin">Paladin</option>
                    <option value="Ranger">Ranger</option>
                  </select>
                </div>
                <div style="width: 100px;">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted px-2">Lvl</span>
                    <input type="number" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace party-input-level" min="1" max="20" value="1" required />
                  </div>
                </div>
                <div style="width: 110px;">
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-role">
                    <option value="Member" selected>Üye</option>
                    <option value="Reporter">Lider / Rep.</option>
                  </select>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-label-danger border-0 btn-remove-party-row" title="Kaldır">
                  <i class="icon-base ti tabler-trash fs-6"></i>
                </button>
              </div>

              <div class="modal-party-row d-flex align-items-center gap-2 flex-wrap" data-row="2">
                <div class="flex-grow-1" style="min-width: 140px;">
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading party-input-name" placeholder="Karakter Adı" value="Valen" required />
                </div>
                <div style="width: 130px;">
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-class">
                    <option value="Fighter" selected>Fighter</option>
                    <option value="Paladin">Paladin</option>
                    <option value="Cleric">Cleric</option>
                    <option value="Barbarian">Barbarian</option>
                  </select>
                </div>
                <div style="width: 100px;">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted px-2">Lvl</span>
                    <input type="number" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace party-input-level" min="1" max="20" value="2" required />
                  </div>
                </div>
                <div style="width: 110px;">
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-role">
                    <option value="Member" selected>Üye</option>
                    <option value="Reporter">Lider / Rep.</option>
                  </select>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-label-danger border-0 btn-remove-party-row" title="Kaldır">
                  <i class="icon-base ti tabler-trash fs-6"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Row 4: GM & Ödüller -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-heading">{{ __('Game Master') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="gm" id="addReportGm" placeholder="Örn: Shalvaz, Bart, Vain" value="Shalvaz" required />
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-heading">{{ __('Total Experience') }} (XP)</label>
              <input type="number" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace" name="total_xp" id="addReportTotalXp" value="750" />
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold text-heading">{{ __('Encountered Enemies') }}</label>
              <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="enemies" id="addReportEnemies" placeholder="Örn: Gölge Kurtları, Haydutlar" />
            </div>
          </div>

          <!-- Row 5: Detaylar / Sefer Raporu Metni -->
          <div class="mb-0">
            <label class="form-label fw-semibold text-heading">{{ __('Full Debriefing Report') }} (Details) <span class="text-danger">*</span></label>
            <textarea class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="full_report" id="addReportFullText" rows="3" placeholder="Görevin operasyon detaylarını, yaşanan olayları ve sonuç brifingini buraya yazın..." required>Gölge Diyar sınırında yürütülen keşif operasyonu başarıyla tamamlandı. Düşman sızma girişimi engellenerek lonca mülkleri güvenceye alındı.</textarea>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer p-3 p-md-4 d-flex align-items-center justify-content-between">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            {{ __('Close Dossier') }}
          </button>
          <button type="submit" class="btn btn-primary btn-sm btn-add-quest-report" id="btnSubmitAddQuestReport">
            <i class="icon-base ti tabler-check me-1 fs-5"></i>
            <span>{{ __('Save Quest Report') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ====================================================
     5. MISSION REPORT DOSSIER MODAL (DETAYLAR OKUYUCU)
     ==================================================== -->
<div class="modal fade" id="questReportDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-dossier">
      <!-- Modal Header -->
      <div class="modal-header d-flex align-items-center justify-content-between p-3 p-md-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-warning rounded-3 d-flex align-items-center justify-content-center shadow-md">
            <i class="icon-base ti tabler-shield-lock fs-3 text-warning"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <span class="badge" id="modalReportTierBadge">Main Quest</span>
              <span class="text-body-secondary font-monospace small" id="modalReportDate">15.09.2026</span>
              <span class="badge bg-label-success" id="modalReportStatusBadge">✓ {{ __('Successful') }}</span>
            </div>
            <h4 class="mb-0 fw-bold text-heading" id="modalReportTitle">Görev Başlığı</h4>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-md-4">
        <!-- Metadata Overview Ribbon -->
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Location') }}</span>
              <span class="fw-bold font-monospace text-heading" id="modalReportLocation" style="font-size: 0.85rem;">Siwion</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Game Master') }}</span>
              <span class="fw-bold font-monospace text-warning" id="modalReportGm" style="font-size: 0.85rem;">Bart</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Reporter') }}</span>
              <span class="fw-bold font-monospace text-info" id="modalReportReporter" style="font-size: 0.85rem;">Tolrun</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Average Level') }}</span>
              <span class="fw-bold font-monospace text-success" id="modalReportAvgLevel" style="font-size: 0.85rem;">Ort. Lvl 1.25</span>
            </div>
          </div>
        </div>

        <!-- Debriefing / Story Narrative -->
        <div class="report-section-box mb-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-feather text-warning"></i>
              <span>{{ __('Full Debriefing Report') }}</span>
            </h6>
            <span class="badge bg-label-danger font-monospace" id="modalReportEnemies" style="font-size: 0.75rem;">
              Düşmanlar
            </span>
          </div>
          <p class="text-body-secondary mb-0" id="modalReportFullText" style="line-height: 1.68; font-size: 0.92rem; white-space: pre-line;">
            Detaylı sefer raporu yükleniyor...
          </p>
        </div>

        <!-- Party Members & Individual XP Breakdown -->
        <div class="report-section-box mb-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-users-group text-primary"></i>
              <span>{{ __('Party Roster & Individual XP') }}</span>
            </h6>
            <span class="badge bg-label-secondary font-monospace" id="modalReportPartyCount">
              4 Oyuncu
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm text-nowrap mb-0" style="font-size: 0.85rem;">
              <thead>
                <tr class="text-body-secondary border-bottom border-secondary border-opacity-20">
                  <th>{{ __('Character') }}</th>
                  <th>{{ __('Class') }}</th>
                  <th>{{ __('Level') }}</th>
                  <th>{{ __('Role') }}</th>
                  <th class="text-end">{{ __('XP Awarded') }}</th>
                </tr>
              </thead>
              <tbody id="modalReportPartyTableBody">
                <!-- Populated via JS -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Financial & Vault Ledger Cuts -->
        <div class="report-section-box mb-0">
          <h6 class="mb-3 fw-bold text-heading d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-coins text-warning"></i>
            <span>{{ __('Financial & Guild Ledger') }}</span>
          </h6>
          <div class="row g-2">
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Total XP') }}</span>
                <span class="fw-bold font-monospace text-success" id="modalReportTotalXp">+750 XP</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Guild Cut') }}</span>
                <span class="fw-bold font-monospace text-body" id="modalReportGuildCut">37.5 SP</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Vault Cut') }}</span>
                <span class="fw-bold font-monospace text-info" id="modalReportVaultCut">0 SP</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Average Reward') }}</span>
                <span class="fw-bold font-monospace text-warning" id="modalReportAvgReward">175.63 XP</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer p-3 p-md-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-1 text-body-secondary font-monospace small">
          <i class="icon-base ti tabler-certificate text-warning"></i>
          <span>{{ __('Verified Guild Archives Record') }}</span>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          {{ __('Close Dossier') }}
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  let reportsData = @json($reports);

  // Filter Elements
  const searchInput = document.getElementById('reportsSearchInput');
  const btnSearchClear = document.getElementById('btnReportsSearchClear');
  const typeButtons = document.querySelectorAll('#reportsTypeFilters .filter-pill-btn');
  const sortSelect = document.getElementById('reportsSortSelect');
  const btnResetFilters = document.getElementById('btnResetReportsFilters');
  const btnEmptyResetFilters = document.getElementById('btnEmptyResetFilters');

  // Open Add Report Modal Buttons
  const btnOpenAddReportModal = document.getElementById('btnOpenAddReportModal');
  const btnToolbarAddReport = document.getElementById('btnToolbarAddReport');
  const modalAddEl = document.getElementById('modalAddQuestReport');
  const formAddReport = document.getElementById('formAddQuestReport');

  // Containers
  const tableBody = document.getElementById('reportsTableBody');
  const emptyState = document.getElementById('reportsEmptyState');
  const currentFilteredCountEl = document.getElementById('currentFilteredCount');
  const totalReportsSpan = document.getElementById('totalReportsSpan');

  let currentTypeFilter = 'all';

  // Open Add Modal
  function showAddModal() {
    if (modalAddEl && window.bootstrap) {
      const bsModal = new bootstrap.Modal(modalAddEl);
      bsModal.show();
      calculateLiveAvgLevel();
    }
  }

  if (btnOpenAddReportModal) btnOpenAddReportModal.addEventListener('click', showAddModal);
  if (btnToolbarAddReport) btnToolbarAddReport.addEventListener('click', showAddModal);

  // Dynamic Party Rows & Average Level Calculation
  const partyContainer = document.getElementById('addReportPartyContainer');
  const btnAddPartyRow = document.getElementById('btnAddPartyRow');
  const liveAvgLevelBadge = document.getElementById('liveAddAvgLevelBadge');

  function calculateLiveAvgLevel() {
    if (!partyContainer) return 1.0;
    const levelInputs = partyContainer.querySelectorAll('.party-input-level');
    let sum = 0;
    let count = 0;

    levelInputs.forEach(input => {
      const val = parseFloat(input.value) || 1;
      sum += val;
      count++;
    });

    const avg = count > 0 ? (sum / count).toFixed(2) : '1.00';
    if (liveAvgLevelBadge) {
      liveAvgLevelBadge.textContent = `Ortalama Seviye: ${avg}`;
    }
    return parseFloat(avg);
  }

  if (partyContainer) {
    partyContainer.addEventListener('input', function (e) {
      if (e.target.classList.contains('party-input-level')) {
        calculateLiveAvgLevel();
      }
    });

    partyContainer.addEventListener('click', function (e) {
      const btnRemove = e.target.closest('.btn-remove-party-row');
      if (btnRemove) {
        const row = btnRemove.closest('.modal-party-row');
        const allRows = partyContainer.querySelectorAll('.modal-party-row');
        if (allRows.length > 1) {
          row.remove();
          calculateLiveAvgLevel();
        } else {
          alert('En az bir parti üyesi gereklidir.');
        }
      }
    });
  }

  if (btnAddPartyRow) {
    btnAddPartyRow.addEventListener('click', function () {
      if (!partyContainer) return;
      const count = partyContainer.querySelectorAll('.modal-party-row').length;
      const div = document.createElement('div');
      div.className = 'modal-party-row d-flex align-items-center gap-2 flex-wrap';
      div.setAttribute('data-row', count);
      div.innerHTML = `
        <div class="flex-grow-1" style="min-width: 140px;">
          <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading party-input-name" placeholder="Karakter Adı" value="Kahraman ${count + 1}" required />
        </div>
        <div style="width: 130px;">
          <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-class">
            <option value="Fighter" selected>Fighter</option>
            <option value="Wizard">Wizard</option>
            <option value="Rogue">Rogue</option>
            <option value="Cleric">Cleric</option>
            <option value="Paladin">Paladin</option>
            <option value="Ranger">Ranger</option>
            <option value="Barbarian">Barbarian</option>
            <option value="Bard">Bard</option>
            <option value="Sorcerer">Sorcerer</option>
            <option value="Warlock">Warlock</option>
            <option value="Druid">Druid</option>
            <option value="Monk">Monk</option>
          </select>
        </div>
        <div style="width: 100px;">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted px-2">Lvl</span>
            <input type="number" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace party-input-level" min="1" max="20" value="1" required />
          </div>
        </div>
        <div style="width: 110px;">
          <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading party-input-role">
            <option value="Member" selected>Üye</option>
            <option value="Reporter">Lider / Rep.</option>
          </select>
        </div>
        <button type="button" class="btn btn-icon btn-sm btn-label-danger border-0 btn-remove-party-row" title="Kaldır">
          <i class="icon-base ti tabler-trash fs-6"></i>
        </button>
      `;
      partyContainer.appendChild(div);
      calculateLiveAvgLevel();
    });
  }

  // Submit Add Quest Report
  if (formAddReport) {
    formAddReport.addEventListener('submit', function (e) {
      e.preventDefault();

      const title = document.getElementById('addReportTitle').value.trim();
      const location = document.getElementById('addReportLocation').value.trim();
      const date = document.getElementById('addReportDate').value.trim();
      const tierType = document.getElementById('addReportTierType').value;
      const status = document.getElementById('addReportStatus').value;
      const gm = document.getElementById('addReportGm').value.trim();
      const totalXp = parseFloat(document.getElementById('addReportTotalXp').value) || 750;
      const enemies = document.getElementById('addReportEnemies').value.trim();
      const fullText = document.getElementById('addReportFullText').value.trim();

      // Collect party members
      const party = [];
      const partyRows = partyContainer.querySelectorAll('.modal-party-row');
      let reporterName = 'Kahraman';

      partyRows.forEach(row => {
        const name = row.querySelector('.party-input-name')?.value.trim() || 'Kahraman';
        const cls = row.querySelector('.party-input-class')?.value || 'Adventurer';
        const lvl = parseInt(row.querySelector('.party-input-level')?.value, 10) || 1;
        const role = row.querySelector('.party-input-role')?.value || 'Member';
        if (role === 'Reporter') reporterName = name;

        party.push({
          name: name,
          class: cls,
          level: lvl,
          role: role,
          xp: Math.round(totalXp / Math.max(partyRows.length, 1))
        });
      });

      const payload = {
        title: title,
        location: location,
        date: date,
        tier_type: tierType,
        is_main: tierType !== 'side',
        status: status,
        gm: gm,
        reporter: reporterName,
        total_xp: totalXp,
        enemies: enemies,
        report_summary: fullText,
        full_report: fullText,
        party: party,
        _token: document.querySelector('input[name="_token"]')?.value
      };

      const submitBtn = document.getElementById('btnSubmitAddQuestReport');
      if (submitBtn) submitBtn.disabled = true;

      fetch('{{ route("valiant-shade.pages-quest-reports.store") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (!data.success) {
          alert(data.message || 'Görev raporu kaydedilemedi.');
          return;
        }

        const newRep = data.report;
        reportsData.unshift(newRep);

        // Hide modal
        const bsModal = bootstrap.Modal.getInstance(modalAddEl);
        if (bsModal) bsModal.hide();
        formAddReport.reset();

        // Update stats
        if (data.stats) {
          const s = data.stats;
          const kpiTotalBadge = document.getElementById('kpiTotalReportsBadge');
          const kpiTotalVal = document.getElementById('kpiTotalQuestsVal');
          const kpiMainVal = document.getElementById('kpiMainQuestsVal');
          const kpiSideVal = document.getElementById('kpiSideQuestsVal');
          const kpiXpVal = document.getElementById('kpiTotalXpVal');
          const kpiAvgVal = document.getElementById('kpiAvgLevelVal');
          const totalSpan = document.getElementById('totalReportsSpan');

          if (kpiTotalBadge) kpiTotalBadge.textContent = `${s.total_quests} {{ __('Reports Listed') }}`;
          if (kpiTotalVal) kpiTotalVal.textContent = s.total_quests;
          if (kpiMainVal) kpiMainVal.innerHTML = `${s.main_quests} <span class="small text-muted font-normal" style="font-size: 0.72rem;">(${Math.round((s.main_quests / s.total_quests) * 100)}%)</span>`;
          if (kpiSideVal) kpiSideVal.textContent = s.side_quests;
          if (kpiXpVal) kpiXpVal.textContent = `+${s.total_xp.toLocaleString()}`;
          if (kpiAvgVal) kpiAvgVal.textContent = parseFloat(s.avg_level || s.overall_avg_level || 1.25).toFixed(2);
          if (totalSpan) totalSpan.textContent = s.total_quests;

          const typeAll = document.getElementById('typeCountAll');
          const typeMain = document.getElementById('typeCountMain');
          const typeSide = document.getElementById('typeCountSide');
          if (typeAll) typeAll.textContent = s.total_quests;
          if (typeMain) typeMain.textContent = s.main_quests;
          if (typeSide) typeSide.textContent = s.side_quests;
        }

        // Insert new row into table
        prependReportToTable(newRep);
        applyFiltersAndSort();

        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Görev Raporu Eklendi!") }}',
            text: `"${newRep.title}" görevi ve hesaplanan seviyesi (${newRep.calculated_avg_level}) başarıyla arşive işlendi.`,
            timer: 2200,
            showConfirmButton: false
          });
        }
      })
      .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        alert('Sunucu iletişim hatası oluştu.');
      });
    });
  }

  function prependReportToTable(q) {
    if (!tableBody) return;

    let tierBadgeHtml = '';
    if (q.tier_type === 'epic') {
      tierBadgeHtml = `<span class="badge badge-epic font-monospace fw-bold px-3 py-1"><i class="icon-base ti tabler-crown me-1"></i>{{ __('Epic Main Quest') }} (✓✓)</span>`;
    } else if (q.is_main) {
      tierBadgeHtml = `<span class="badge badge-main font-monospace fw-bold px-3 py-1"><i class="icon-base ti tabler-shield-check me-1"></i>{{ __('Main Quest') }} (✓)</span>`;
    } else {
      tierBadgeHtml = `<span class="badge badge-side font-monospace px-3 py-1"><i class="icon-base ti tabler-point me-1"></i>{{ __('Side Quest') }} (x)</span>`;
    }

    let statusHtml = (q.status === 'failed')
      ? `<span class="badge bg-label-danger text-danger border border-danger border-opacity-25 px-3 py-1 font-monospace fw-semibold"><i class="icon-base ti tabler-circle-x me-1"></i>{{ __('Failed') }}</span>`
      : `<span class="badge bg-label-success text-success border border-success border-opacity-25 px-3 py-1 font-monospace fw-semibold"><i class="icon-base ti tabler-circle-check me-1"></i>{{ __('Successful') }}</span>`;

    let iconHtml = (q.tier_type === 'epic')
      ? `<div class="avatar avatar-sm bg-label-warning rounded-3 me-3 d-flex align-items-center justify-content-center"><i class="icon-base ti tabler-crown fs-5 text-warning"></i></div>`
      : (q.is_main
        ? `<div class="avatar avatar-sm bg-label-primary rounded-3 me-3 d-flex align-items-center justify-content-center"><i class="icon-base ti tabler-shield-check fs-5 text-primary"></i></div>`
        : `<div class="avatar avatar-sm bg-label-secondary rounded-3 me-3 d-flex align-items-center justify-content-center"><i class="icon-base ti tabler-scroll fs-5 text-muted"></i></div>`);

    const tr = document.createElement('tr');
    tr.className = 'quest-row-clickable report-row-item';
    tr.setAttribute('data-id', q.id);
    tr.setAttribute('data-type', q.is_main ? 'main' : 'side');
    tr.setAttribute('data-gm', (q.gm || '').toLowerCase());
    tr.setAttribute('data-level', q.level || 1);
    tr.setAttribute('data-xp', q.total_xp || 0);
    tr.setAttribute('data-date', q.date);
    tr.setAttribute('title', '{{ __("Click row for full mission report") }}');

    tr.innerHTML = `
      <td>
        <div class="d-flex align-items-center">
          ${iconHtml}
          <div>
            <span class="fw-bold text-heading d-block" style="font-size: 0.96rem;">${q.title}</span>
            <small class="text-body-secondary font-monospace"><i class="icon-base ti tabler-map-pin text-danger opacity-75 me-1"></i>${q.location}</small>
          </div>
        </div>
      </td>
      <td>
        <span class="font-monospace text-body-secondary fw-semibold" style="font-size: 0.9rem;">
          <i class="icon-base ti tabler-calendar-time text-primary me-1 fs-6"></i>${q.date}
        </span>
      </td>
      <td>${tierBadgeHtml}</td>
      <td>${statusHtml}</td>
      <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-primary btn-open-report" data-id="${q.id}">
          <i class="icon-base ti tabler-file-text me-1 fs-6"></i>
          <span>{{ __('Details') }}</span>
          <i class="icon-base ti tabler-chevron-right ms-1 fs-6"></i>
        </button>
      </td>
    `;

    tableBody.insertBefore(tr, tableBody.firstChild);
  }

  // Type Filters
  typeButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      typeButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentTypeFilter = this.getAttribute('data-type') || 'all';
      applyFiltersAndSort();
    });
  });

  // Search Input Listeners
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      if (btnSearchClear) {
        if (this.value.trim().length > 0) {
          btnSearchClear.classList.remove('d-none');
        } else {
          btnSearchClear.classList.add('d-none');
        }
      }
      applyFiltersAndSort();
    });
  }

  if (btnSearchClear) {
    btnSearchClear.addEventListener('click', function () {
      if (searchInput) {
        searchInput.value = '';
        btnSearchClear.classList.add('d-none');
        applyFiltersAndSort();
      }
    });
  }

  if (sortSelect) sortSelect.addEventListener('change', applyFiltersAndSort);

  // Reset Filters
  function resetAllFilters() {
    if (searchInput) searchInput.value = '';
    if (btnSearchClear) btnSearchClear.classList.add('d-none');
    if (sortSelect) sortSelect.value = 'date-desc';
    currentTypeFilter = 'all';
    typeButtons.forEach(b => {
      b.classList.toggle('active', b.getAttribute('data-type') === 'all');
    });
    applyFiltersAndSort();
  }

  if (btnResetFilters) btnResetFilters.addEventListener('click', resetAllFilters);
  if (btnEmptyResetFilters) btnEmptyResetFilters.addEventListener('click', resetAllFilters);

  // Core Filter & Sorting
  function applyFiltersAndSort() {
    const term = (searchInput?.value || '').trim().toLowerCase();
    const sortVal = sortSelect?.value || 'date-desc';

    const rows = Array.from(document.querySelectorAll('.report-row-item'));
    let visibleCount = 0;

    function itemMatches(el) {
      const id = parseInt(el.getAttribute('data-id'), 10);
      const rep = reportsData.find(r => r.id === id);
      if (!rep) return false;

      // Type filter
      if (currentTypeFilter === 'main' && !rep.is_main) return false;
      if (currentTypeFilter === 'side' && rep.is_main) return false;

      // Text Search
      if (term) {
        let match = rep.title.toLowerCase().includes(term) ||
                    (rep.gm || '').toLowerCase().includes(term) ||
                    (rep.reporter || '').toLowerCase().includes(term) ||
                    (rep.location || '').toLowerCase().includes(term) ||
                    (rep.report_summary || '').toLowerCase().includes(term);

        if (!match && rep.party) {
          match = rep.party.some(p => p.name.toLowerCase().includes(term));
        }
        if (!match) return false;
      }

      return true;
    }

    function parseReportDate(dateStr) {
      if (!dateStr) return 0;
      const parts = dateStr.split('.');
      if (parts.length === 3) {
        return new Date(parts[2], parts[1] - 1, parts[0]).getTime();
      }
      return 0;
    }

    function compareElements(a, b) {
      if (sortVal === 'date-desc') {
        return parseReportDate(b.getAttribute('data-date')) - parseReportDate(a.getAttribute('data-date'));
      } else if (sortVal === 'date-asc') {
        return parseReportDate(a.getAttribute('data-date')) - parseReportDate(b.getAttribute('data-date'));
      } else if (sortVal === 'xp-desc') {
        return (parseFloat(b.getAttribute('data-xp')) || 0) - (parseFloat(a.getAttribute('data-xp')) || 0);
      }
      return 0;
    }

    rows.forEach(item => {
      const show = itemMatches(item);
      item.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    if (tableBody) {
      rows.sort(compareElements).forEach(node => tableBody.appendChild(node));
    }

    if (currentFilteredCountEl) {
      currentFilteredCountEl.textContent = visibleCount;
    }

    if (visibleCount === 0) {
      if (emptyState) emptyState.classList.remove('d-none');
    } else {
      if (emptyState) emptyState.classList.add('d-none');
    }
  }

  // Row Click -> Open Full Mission Report Details Modal
  document.addEventListener('click', function (e) {
    const row = e.target.closest('.quest-row-clickable');
    if (!row) return;

    const reportId = parseInt(row.getAttribute('data-id'), 10);
    const report = reportsData.find(r => r.id === reportId);
    if (!report) return;

    populateAndOpenModal(report);
  });

  function populateAndOpenModal(r) {
    // Header
    const titleEl = document.getElementById('modalReportTitle');
    const dateEl = document.getElementById('modalReportDate');
    const tierBadge = document.getElementById('modalReportTierBadge');
    const statusBadge = document.getElementById('modalReportStatusBadge');
    const locationEl = document.getElementById('modalReportLocation');
    const gmEl = document.getElementById('modalReportGm');
    const reporterEl = document.getElementById('modalReportReporter');
    const avgLevelEl = document.getElementById('modalReportAvgLevel');

    if (titleEl) titleEl.textContent = r.title;
    if (dateEl) dateEl.textContent = r.date;
    if (locationEl) locationEl.textContent = r.location;
    if (gmEl) gmEl.textContent = `GM ${r.gm || 'Shalvaz'}`;
    if (reporterEl) reporterEl.textContent = r.reporter || 'Tolrun';

    const avgLvl = parseFloat(r.calculated_avg_level || r.level || 1).toFixed(2);
    if (avgLevelEl) avgLevelEl.textContent = `{{ __('Average Level') }}: ${avgLvl}`;

    if (tierBadge) {
      if (r.tier_type === 'epic') {
        tierBadge.className = 'badge badge-epic font-monospace';
        tierBadge.textContent = '✓✓ {{ __("Epic Main Quest") }}';
      } else if (r.is_main) {
        tierBadge.className = 'badge badge-main font-monospace';
        tierBadge.textContent = '✓ {{ __("Main Quest") }}';
      } else {
        tierBadge.className = 'badge badge-side font-monospace';
        tierBadge.textContent = '{{ __("Side Quest") }}';
      }
    }

    if (statusBadge) {
      if ((r.status || 'success') === 'failed') {
        statusBadge.className = 'badge bg-label-danger text-danger border border-danger border-opacity-25';
        statusBadge.textContent = 'x {{ __("Failed") }}';
      } else {
        statusBadge.className = 'badge bg-label-success text-success border border-success border-opacity-25';
        statusBadge.textContent = '✓ {{ __("Successful") }}';
      }
    }

    // Debrief narrative
    const fullTextEl = document.getElementById('modalReportFullText');
    const enemiesEl = document.getElementById('modalReportEnemies');
    if (fullTextEl) fullTextEl.textContent = r.full_report || r.report_summary;
    if (enemiesEl) enemiesEl.textContent = r.enemies || 'Gizemli Tehditler';

    // Party Roster
    const partyCountEl = document.getElementById('modalReportPartyCount');
    const partyBody = document.getElementById('modalReportPartyTableBody');

    if (partyCountEl) partyCountEl.textContent = `${r.party ? r.party.length : (r.player_count || 1)} {{ __('Players') }}`;

    if (partyBody) {
      partyBody.innerHTML = '';
      if (r.party && r.party.length > 0) {
        r.party.forEach(m => {
          const isReporter = m.role === 'Reporter';
          const tr = document.createElement('tr');
          tr.className = 'border-bottom border-secondary border-opacity-10';
          tr.innerHTML = `
            <td class="fw-semibold text-heading d-flex align-items-center gap-1">
              ${isReporter ? '<i class="icon-base ti tabler-feather text-warning" title="{{ __("Reporter") }}"></i>' : '<i class="icon-base ti tabler-user text-primary opacity-50"></i>'}
              <span>${m.name}</span>
            </td>
            <td><span class="badge bg-label-info font-monospace" style="font-size: 0.72rem;">${m.class || 'Adventurer'}</span></td>
            <td class="font-monospace fw-bold text-info">Lvl ${m.level || 1}</td>
            <td><span class="badge ${isReporter ? 'bg-label-warning text-warning' : 'bg-label-secondary text-body'} font-monospace" style="font-size: 0.7rem;">${m.role}</span></td>
            <td class="text-end font-monospace text-success fw-bold">+${m.xp || Math.round(r.total_xp / Math.max(r.party.length, 1))} XP</td>
          `;
          partyBody.appendChild(tr);
        });
      }
    }

    // Financial Breakdown
    const totalXpEl = document.getElementById('modalReportTotalXp');
    const guildCutEl = document.getElementById('modalReportGuildCut');
    const vaultCutEl = document.getElementById('modalReportVaultCut');
    const avgRewardEl = document.getElementById('modalReportAvgReward');

    if (totalXpEl) totalXpEl.textContent = `+${r.total_xp} XP`;
    if (guildCutEl) guildCutEl.textContent = `${r.guild_cut || 0} SP`;
    if (vaultCutEl) vaultCutEl.textContent = `${r.vault_cut || 0} SP`;
    if (avgRewardEl) avgRewardEl.textContent = `${r.avg_reward || 0} XP`;

    // Show modal
    const modalDetailEl = document.getElementById('questReportDetailModal');
    if (modalDetailEl && window.bootstrap) {
      const bsModal = new bootstrap.Modal(modalDetailEl);
      bsModal.show();
    }
  }
});
</script>
@endsection
