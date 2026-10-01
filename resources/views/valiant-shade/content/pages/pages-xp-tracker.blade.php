@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Player XP Tracker - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* Custom enhancements for the Optimal XP Tracker */
  .adventurer-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .adventurer-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
  }
  .floating-batch-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(120%);
    z-index: 1060;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .floating-batch-bar.show {
    transform: translateX(-50%) translateY(0);
  }
  .progress-bar-animated-smooth {
    transition: width 0.6s ease-in-out;
  }
  .cursor-pointer {
    cursor: pointer;
  }

  /* Enhanced XP Tracker Table & Layout */
  .xp-tracker-table {
    table-layout: auto;
    width: 100%;
    margin-bottom: 0;
  }
  .xp-tracker-table thead th {
    font-size: 0.74rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
  }
  .xp-tracker-table tbody td {
    padding-top: 0.65rem !important;
    padding-bottom: 0.65rem !important;
    vertical-align: middle !important;
  }
  .xp-tracker-table tbody tr {
    transition: background-color 0.15s ease;
  }
  .xp-tracker-table tbody tr:hover {
    background-color: rgba(115, 103, 240, 0.04) !important;
  }
  .table-responsive-xp {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(115, 103, 240, 0.35) transparent;
  }
  .table-responsive-xp::-webkit-scrollbar {
    height: 6px;
  }
  .table-responsive-xp::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.04);
    border-radius: 4px;
  }
  .table-responsive-xp::-webkit-scrollbar-thumb {
    background: rgba(115, 103, 240, 0.35);
    border-radius: 4px;
  }
  .table-responsive-xp::-webkit-scrollbar-thumb:hover {
    background: rgba(115, 103, 240, 0.65);
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'
], 'static/valiant-shade/build')
@endsection

@section('content')
<!-- Header & Action Toolbar (Vuexy Demo Style) -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h4 class="mb-1 fw-bold text-heading d-flex align-items-center gap-2">
      <span class="avatar avatar-sm rounded-3 bg-label-primary d-flex align-items-center justify-content-center shadow-xs">
        <i class="icon-base ti tabler-award fs-4"></i>
      </span>
      <span>Player XP Tracker</span>
    </h4>
    <p class="text-body-secondary small mb-0">{{ __('Valiant Shade guild adventurer experience points, level progression and Hall of Fame.') }}</p>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- View Mode Switcher: Table vs Cards -->
    <div class="btn-group btn-group-sm p-1 bg-body border rounded-pill shadow-xs" role="group" aria-label="{{ __('Detailed Table View') }}">
      <button type="button" class="btn btn-xs rounded-pill px-3 py-1_5 active fw-semibold" id="btnViewTable" title="{{ __('Detailed Table View') }}">
        <i class="icon-base ti tabler-table me-1"></i> {{ __('Table') }}
      </button>
      <button type="button" class="btn btn-xs rounded-pill px-3 py-1_5 fw-semibold" id="btnViewCards" title="{{ __('RPG Character Cards View') }}">
        <i class="icon-base ti tabler-layout-grid me-1"></i> {{ __('Cards') }}
      </button>
    </div>

    <!-- Export Dropdown (Demo-Vuexy Style) -->
    <div class="dropdown">
      <button type="button" class="btn btn-label-secondary btn-sm rounded-pill dropdown-toggle waves-effect shadow-xs" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="icon-base ti tabler-upload me-1"></i> {{ __('Export') }}
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
          <a class="dropdown-item d-flex align-items-center" href="{{ route('valiant-shade.pages-xp-tracker', ['export' => 'csv']) }}">
            <i class="icon-base ti tabler-file-text me-2 text-primary"></i> {{ __('Download CSV') }}
          </a>
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center" href="javascript:window.print();">
            <i class="icon-base ti tabler-printer me-2 text-info"></i> {{ __('Print Page') }}
          </a>
        </li>
      </ul>
    </div>

    <!-- Group Actions / Toplu İşlemler -->
    @if($isAdmin)
      <div class="dropdown">
        <button type="button" class="btn btn-label-primary btn-sm rounded-pill dropdown-toggle waves-effect shadow-xs" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="icon-base ti tabler-users-group me-1"></i> {{ __('Group / Batch') }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li>
            <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);" id="btnTriggerPartyXp">
              <i class="icon-base ti tabler-sparkles me-2 text-success"></i> {{ __('Distribute Party Quest XP') }}
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);" id="btnSelectAllRows">
              <i class="icon-base ti tabler-checks me-2 text-primary"></i> {{ __('Select All Adventurers') }}
            </a>
          </li>
        </ul>
      </div>

      <!-- Reset Defaults -->
      <button type="button" class="btn btn-label-warning btn-sm rounded-pill waves-effect shadow-xs" id="btnResetDefaults" title="{{ __('Reset to Original Master Sheet Data') }}">
        <i class="icon-base ti tabler-rotate-clockwise-2 me-1"></i> {{ __('Reset Defaults') }}
      </button>

      <!-- Add Adventurer Button (Opens Offcanvas Drawer like Demo-Vuexy) -->
      <button type="button" class="btn btn-primary btn-sm rounded-pill waves-effect waves-light shadow-xs" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddAdventurer">
        <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add New Adventurer') }}
      </button>
    @else
      <span class="badge bg-label-secondary rounded-pill d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
        <i class="icon-base ti tabler-lock fs-6 text-warning"></i>
        <span>{{ __('Read Only') }}</span>
      </span>
    @endif
  </div>
</div>

<!-- Statistical Metric Cards (Vuexy KPI Cards) -->
<div class="row g-4 mb-4">
  <!-- Card 1: Guild Members (Aktif Kadro) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border cursor-pointer filter-card" data-filter="guild" title="{{ __('Filter Active Guild Roster') }}">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Active Guild Roster') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-primary" id="statGuildCount">
                {{ $statistics['guild_count'] ?? $guildMembers->count() }}
              </h3>
              <span class="badge bg-label-primary font-monospace">{{ __('Members') }}</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ti tabler-shield-check icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">{{ __('Active guild members excluding candidates and inactives') }}</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: Candidates (Adaylar) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border cursor-pointer filter-card" data-filter="candidate" title="{{ __('Filter Candidate Adventurers') }}">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Candidates (New List)') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-info" id="statCandidateCount">
                {{ $statistics['candidate_count'] ?? $candidates->count() }}
              </h3>
              <span class="badge bg-label-info font-monospace">{{ __('Candidate') }}</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ti tabler-user-plus icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">{{ __('Candidates being evaluated for guild membership') }}</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 3: Total Guild XP & Level -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Total Distributed XP') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-warning" id="statTotalXp">
                {{ number_format($statistics['total_active_xp'] ?? 0) }}
              </h3>
              <span class="badge bg-label-warning font-monospace">XP</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ti tabler-sparkles icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
          <small class="text-truncate text-body-secondary" style="max-width: 220px;" title="Ortalama Seviye: Lv {{ $statistics['avg_level'] ?? 1 }}">
            Ort. Seviye: <strong class="text-heading">Lv {{ $statistics['avg_level'] ?? 1 }}</strong> &bull; Lider: {{ $statistics['top_adventurer']['name'] ?? '—' }}
          </small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 4: Hall of Fallen (KIA / MIA) -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border cursor-pointer filter-card" data-filter="fallen" title="{{ __('Hall of Fame (KIA / MIA)') }}">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Hall of Fame (KIA / MIA)') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-danger" id="statFallenCount">
                {{ $statistics['total_fallen_count'] ?? 0 }}
              </h3>
              <span class="badge bg-label-danger font-monospace">Fallen</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-danger">
              <i class="icon-base ti tabler-tombstone icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">Kayıp ve ebediyete intikal edenler</small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Pathfinder Fast Progression Interactive Reference Bar -->
<div class="card mb-4 border-0 shadow-none" style="background: linear-gradient(72.47deg, rgba(115, 103, 240, 0.12) 0%, rgba(115, 103, 240, 0.03) 100%);">
  <div class="card-body py-2 px-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-2">
        <span class="avatar avatar-xs rounded-2 bg-label-primary d-flex align-items-center justify-content-center">
          <i class="icon-base ti tabler-chart-arrows fs-6"></i>
        </span>
        <span class="small fw-semibold text-heading">Pathfinder 1e Hızlı Seviye İlerleme Tablosu (Fast Progression):</span>
      </div>
      <button class="btn btn-xs btn-label-primary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProgression" aria-expanded="false" aria-controls="collapseProgression">
        <i class="icon-base ti tabler-eye me-1"></i> Seviye Eşiklerini Göster
      </button>
    </div>

    <div class="collapse mt-2 pt-2 border-top" id="collapseProgression">
      <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        @foreach($fastThresholds as $lvl => $thresh)
          @if($lvl >= 1 && $lvl <= 12)
            <span class="badge bg-body border text-heading font-monospace px-2 py-1 shadow-xs">
              <strong class="text-primary">Lv {{ $lvl }}:</strong> {{ number_format($thresh) }} XP
            </span>
          @endif
        @endforeach
        <span class="badge bg-body border text-body-secondary font-monospace px-2 py-1 shadow-xs">
          Lv 13 - 20: 210k - 2.4M XP
        </span>
      </div>

      <!-- Exact Level / XP / Failsafe Enum Table -->
      <div class="table-responsive rounded border bg-body p-2" style="max-height: 280px; overflow-y: auto;">
        <table class="table table-sm table-striped text-center font-monospace mb-0" style="font-size: 0.8rem;">
          <thead class="table-light">
            <tr>
              <th class="fw-bold">Level</th>
              <th class="fw-bold">XP (Eşik Değeri)</th>
              <th class="fw-bold">Failsafe</th>
            </tr>
          </thead>
          <tbody>
            @foreach($fastThresholds as $lvl => $thresh)
              <tr>
                <td class="fw-bold text-primary">{{ $lvl }}</td>
                <td>{{ number_format($thresh) }}</td>
                <td class="text-body-secondary">{{ $xpFailsafes[$lvl] ?? $lvl }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <small class="text-body-secondary mt-1 d-block text-end" style="font-size: 0.72rem;">
        <i class="icon-base ti tabler-file-code me-1"></i>Kaynak Enum: <code>config/enums/xp_levels.php</code>
      </small>
    </div>
  </div>
</div>

<!-- Main Content Area: Interactive Tabs & Filtering Bar -->
<div class="card border shadow-none mb-4">
  <!-- Card Header with Filters (Organized 2-Row Clean Layout) -->
  <div class="card-header border-bottom p-0">
    <!-- Top Row: Filter Pills Navigation -->
    <div class="p-3 pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <ul class="nav nav-pills card-header-pills flex-wrap gap-1" id="xpTrackerTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active d-flex align-items-center gap-2 rounded-pill px-3 py-2 tab-filter-btn" data-filter="guild" id="tab-guild-btn" type="button">
            <i class="icon-base ti tabler-shield-check fs-5"></i>
            <span class="fw-semibold">{{ __('Active Roster (Guild Members)') }}</span>
            <span class="badge rounded-pill bg-primary ms-1" id="badgeGuildCount">{{ $guildMembers->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2 tab-filter-btn" data-filter="candidate" id="tab-candidates-btn" type="button">
            <i class="icon-base ti tabler-user-plus fs-5"></i>
            <span class="fw-semibold">{{ __('Candidates') }}</span>
            <span class="badge rounded-pill bg-label-info ms-1" id="badgeCandidateCount">{{ $candidates->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2 tab-filter-btn" data-filter="inactive" id="tab-inactives-btn" type="button">
            <i class="icon-base ti tabler-user-off fs-5"></i>
            <span class="fw-semibold">{{ __('Inactives') }}</span>
            <span class="badge rounded-pill bg-label-dark ms-1" id="badgeInactiveCount">{{ $inactives->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2 text-danger tab-filter-btn" data-filter="fallen" id="tab-fallen-btn" type="button">
            <i class="icon-base ti tabler-tombstone fs-5"></i>
            <span class="fw-semibold">{{ __('Hall of Fame (KIA / MIA)') }}</span>
            <span class="badge rounded-pill bg-label-danger ms-1" id="badgeFallenCount">{{ $fallenAdventurers->count() }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2 tab-filter-btn" data-filter="all" id="tab-all-btn" type="button">
            <i class="icon-base ti tabler-users fs-5"></i>
            <span class="fw-semibold">{{ __('All Records') }}</span>
            <span class="badge rounded-pill bg-label-secondary ms-1" id="badgeAllCount">{{ $allAdventurers->count() }}</span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Bottom Row: Filter Controls Bar -->
    <div class="px-3 py-2_5 border-top d-flex flex-wrap justify-content-between align-items-center gap-3 bg-label-secondary bg-opacity-10">
      <!-- Left: Switches -->
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="form-check form-switch mb-0 d-flex align-items-center" title="{{ __('Hide Inactives') }}">
          <input class="form-check-input me-1_5 cursor-pointer" type="checkbox" role="switch" id="chkHideInactive">
          <label class="form-check-label small fw-semibold cursor-pointer user-select-none" for="chkHideInactive">{{ __('Hide Inactives') }}</label>
        </div>
        <div class="vr my-1 opacity-25 d-none d-sm-block"></div>
        <div class="form-check form-switch mb-0 d-flex align-items-center" title="{{ __('Hide Candidates') }}">
          <input class="form-check-input me-1_5 cursor-pointer" type="checkbox" role="switch" id="chkHideCandidates">
          <label class="form-check-label small fw-semibold cursor-pointer user-select-none" for="chkHideCandidates">{{ __('Hide Candidates') }}</label>
        </div>
      </div>

      <!-- Right: Search Input, Ranks, Levels, Clear -->
      <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
        <!-- Search Input -->
        <div class="input-group input-group-merge input-group-sm" style="min-width: 170px; max-width: 220px;">
          <span class="input-group-text"><i class="icon-base ti tabler-search fs-6"></i></span>
          <input type="text" class="form-control" id="searchTable" placeholder="{{ __('Search character...') }}" value="{{ $filters['search'] ?? '' }}">
        </div>

        <!-- Rank Filter -->
        <select class="form-select form-select-sm w-auto" id="filterRank" aria-label="Rütbe Filtresi">
          <option value="">{{ __('All Ranks') }}</option>
          @foreach($ranks as $rk)
            <option value="{{ $rk }}" {{ ($filters['rank'] ?? '') === $rk ? 'selected' : '' }}>{{ $rk }}</option>
          @endforeach
        </select>

        <!-- Level Filter -->
        <select class="form-select form-select-sm w-auto" id="filterLevel" aria-label="Seviye Filtresi">
          <option value="">Tüm Seviyeler</option>
          @for($i = 1; $i <= 20; $i++)
            <option value="{{ $i }}" {{ ($filters['level'] ?? '') == $i ? 'selected' : '' }}>Seviye {{ $i }}</option>
          @endfor
        </select>

        <!-- Clear Filters Button -->
        <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-pill" id="btnClearFilters" title="Filtreleri Temizle">
          <i class="icon-base ti tabler-refresh fs-6"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- VIEW 1: TABLE VIEW (DEFAULT) -->
  <div id="viewContainerTable">
    <div class="table-responsive-xp">
      <table class="table table-hover align-middle mb-0 xp-tracker-table" id="tableMainAdventurers">
        <thead class="table-light">
          <tr>
            <th style="width: 44px; min-width: 44px;" class="text-center ps-3">
              <input class="form-check-input cursor-pointer" type="checkbox" id="checkSelectAll" title="Tümünü Seç">
            </th>
            <th style="min-width: 175px;">Maceracı & Oyuncu</th>
            <th class="text-center" style="min-width: 170px; width: 185px;">Seviye & İlerleme</th>
            <th class="text-center" style="min-width: 100px; width: 110px;">Rütbe / Durum</th>
            <th class="text-end" style="min-width: 105px; width: 115px;">Toplam XP</th>
            <th class="text-center" style="min-width: 85px; width: 95px;">Görev</th>
            <th style="min-width: 145px; width: 175px;">
              <div class="d-flex align-items-center gap-1">
                <span>XP Dökümü</span>
                <i class="icon-base ti tabler-info-circle text-muted fs-6 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="Görev / Rapor / Rütbe / Ekstra Kazanımları"></i>
              </div>
            </th>
            <th class="text-end pe-3" style="min-width: 135px; width: 140px;">İşlemler</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($allAdventurers as $adv)
            @php
              $isFallen = in_array($adv->status, ['kia', 'mia']);
              $isKia = $adv->status === 'kia' || strtoupper($adv->reason ?? '') === 'KIA';
              $isMia = $adv->status === 'mia' || strtoupper($adv->reason ?? '') === 'MIA';
              $effectiveLevel = $adv->effective_level;
              $progressPercent = $adv->getProgressPercentage();
              $nextLevelXp = $adv->getNextLevelXp();
              $xpProgressLabel = $adv->getXpProgressLabel();
              $remainingXp = $adv->getRemainingXpToNextLevel();

              // Clean Rank / Status text & colors (NO EMOJIS, NO ICONS)
              if ($isKia) {
                $rankColor = 'bg-label-danger';
                $rankText = 'KIA';
              } elseif ($isMia) {
                $rankColor = 'bg-label-warning';
                $rankText = 'MIA';
              } else {
                $rankColor = match($adv->rank) {
                  'Clerk' => 'bg-label-info',
                  'Aday' => 'bg-label-secondary',
                  'Üye' => 'bg-label-primary',
                  'Uzman' => 'bg-label-success',
                  'Kıdemli' => 'bg-label-warning',
                  'Gezgin' => 'bg-label-info',
                  'Inaktif' => 'bg-label-dark',
                  default => 'bg-label-secondary'
                };
                $rankText = $adv->rank;
              }

              $levelBadgeColor = match(true) {
                $effectiveLevel >= 10 => 'bg-label-danger',
                $effectiveLevel >= 6 => 'bg-label-warning',
                $effectiveLevel >= 3 => 'bg-label-primary',
                default => 'bg-label-secondary'
              };
            @endphp
            <tr class="adventurer-row" 
                data-id="{{ $adv->id }}" 
                data-category="{{ $adv->category }}"
                data-name="{{ strtolower($adv->character_name) }}" 
                data-player="{{ strtolower($adv->player_name ?? '') }}" 
                data-rank="{{ $adv->rank }}" 
                data-level="{{ $effectiveLevel }}"
                data-status="{{ $adv->status }}">
              
              <!-- Checkbox -->
              <td class="text-center ps-3" style="width: 44px;">
                <input class="form-check-input row-select-check cursor-pointer" type="checkbox" value="{{ $adv->id }}" data-name="{{ $adv->character_name }}">
              </td>

              <!-- Character & Player -->
              <td style="min-width: 175px;">
                <div class="d-flex align-items-center gap-2_5">
                  <div class="avatar avatar-sm cursor-pointer btn-open-profile flex-shrink-0" data-adventurer="{{ json_encode($adv) }}" title="Profili İncele">
                    <span class="avatar-initial rounded-circle {{ $isFallen ? ($isKia ? 'bg-label-danger' : 'bg-label-warning') : 'bg-label-primary' }} fw-bold text-uppercase" style="font-size: 0.76rem;">
                      {{ mb_substr($adv->character_name, 0, 2) }}
                    </span>
                  </div>
                  <div class="text-truncate">
                    <h6 class="mb-0 text-heading fw-bold cursor-pointer btn-open-profile text-truncate" data-adventurer="{{ json_encode($adv) }}" style="font-size: 0.88rem;">
                      {{ $adv->character_name }}
                    </h6>
                    <small class="text-body-secondary d-flex align-items-center gap-1 text-truncate" style="font-size: 0.75rem;">
                      <i class="icon-base ti tabler-user fs-7"></i>
                      <span class="text-truncate">{{ $adv->player_name ?: 'Bilinmiyor / Sahipsiz' }}</span>
                    </small>
                  </div>
                </div>
              </td>

              <!-- Level & XP Progress Bar -->
              <td class="text-center" style="min-width: 170px; width: 185px;">
                <div class="d-flex flex-column mx-auto" style="max-width: 185px;">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="badge {{ $levelBadgeColor }} font-monospace fw-bold px-1_5 py-0_5" style="font-size: 0.72rem;">Lv {{ $effectiveLevel }}</span>
                    <span class="fw-bold font-monospace text-heading" style="font-size: 0.78rem;">
                      {{ $xpProgressLabel }}
                    </span>
                  </div>
                  <div class="progress w-100 rounded-pill shadow-xs" style="height: 6px;" title="{{ $xpProgressLabel }} (%{{ $progressPercent }})">
                    <div class="progress-bar bg-primary progress-bar-animated-smooth" role="progressbar" style="width: {{ $progressPercent }}%;" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-body-secondary font-monospace" style="font-size: 0.7rem;">%{{ $progressPercent }}</small>
                    <small class="text-body-secondary font-monospace" style="font-size: 0.7rem;">
                      @if($effectiveLevel >= 20)
                        Maksimum
                      @else
                        Kalan: <strong>{{ number_format($remainingXp) }} XP</strong>
                      @endif
                    </small>
                  </div>
                </div>
              </td>

              <!-- Rank / Status (Clean Text, No Emojis/Icons) -->
              <td class="text-center">
                <span class="badge {{ $rankColor }} rounded-pill px-3 py-1 fw-semibold font-monospace">
                  {{ $rankText }}
                </span>
              </td>

              <!-- Total XP -->
              <td class="text-end font-monospace" style="min-width: 105px; width: 115px;">
                <span class="fw-bold text-heading fs-6">
                  {{ number_format($adv->total_xp) }}
                </span>
                <span class="text-body-secondary small ms-1">XP</span>
              </td>

              <!-- Sessions Count -->
              <td class="text-center" style="min-width: 85px; width: 95px;">
                <span class="badge bg-label-secondary font-monospace rounded-pill px-2 py-1" style="font-size: 0.74rem;">
                  {{ $adv->sessions_count }} Görev
                </span>
              </td>

              <!-- XP Breakdown Pills -->
              <td style="min-width: 145px; width: 175px;">
                <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 175px;">
                  @if($adv->mission_xp > 0)
                    <span class="badge bg-label-success rounded-pill font-monospace" style="font-size: 0.68rem;" title="Görev XP">
                      G: {{ number_format($adv->mission_xp) }}
                    </span>
                  @endif
                  @if($adv->report_xp > 0)
                    <span class="badge bg-label-info rounded-pill font-monospace" style="font-size: 0.68rem;" title="Rapor XP">
                      R: {{ number_format($adv->report_xp) }}
                    </span>
                  @endif
                  @if($adv->rank_xp > 0)
                    <span class="badge bg-label-primary rounded-pill font-monospace" style="font-size: 0.68rem;" title="Rütbe Bonusu XP">
                      Rütbe: {{ number_format($adv->rank_xp) }}
                    </span>
                  @endif
                  @if($adv->extra_xp > 0)
                    <span class="badge bg-label-warning rounded-pill font-monospace" style="font-size: 0.68rem;" title="Ekstra (EX) XP">
                      EX: {{ number_format($adv->extra_xp) }}
                    </span>
                  @endif
                  @if($adv->bailiff_xp > 0)
                    <span class="badge bg-label-danger rounded-pill font-monospace" style="font-size: 0.68rem;" title="Bailiff XP">
                      B: {{ number_format($adv->bailiff_xp) }}
                    </span>
                  @endif
                  @if($adv->bonus_xp > 0)
                    <span class="badge bg-label-secondary rounded-pill font-monospace" style="font-size: 0.68rem;" title="Diğer Bonus XP">
                      +{{ number_format($adv->bonus_xp) }}
                    </span>
                  @endif
                  @if(($adv->mission_xp ?? 0) == 0 && ($adv->report_xp ?? 0) == 0 && ($adv->rank_xp ?? 0) == 0 && ($adv->extra_xp ?? 0) == 0 && ($adv->bailiff_xp ?? 0) == 0 && ($adv->bonus_xp ?? 0) == 0)
                    <span class="text-body-secondary small font-monospace ps-1">—</span>
                  @endif
                </div>
              </td>

              <!-- Actions -->
              <td class="text-end pe-3" style="min-width: 135px; width: 140px;">
                <div class="d-flex align-items-center justify-content-end gap-1">
                  @if($isAdmin)
                    <!-- Quick XP Button -->
                    <button type="button" class="btn btn-xs btn-label-success rounded-pill px-2 py-1 btn-quick-xp d-inline-flex align-items-center" 
                            data-id="{{ $adv->id }}" 
                            data-name="{{ $adv->character_name }}" 
                            data-xp="{{ $adv->total_xp }}"
                            title="Hızlı XP Ver">
                      <i class="icon-base ti tabler-plus fs-7 me-0_5"></i><span>XP</span>
                    </button>

                    <!-- Edit Button (Opens Offcanvas) -->
                    <button type="button" class="btn btn-xs btn-icon btn-label-primary rounded-circle btn-edit-adv" 
                            data-adventurer="{{ json_encode($adv) }}"
                            title="Düzenle">
                      <i class="icon-base ti tabler-pencil fs-6"></i>
                    </button>

                    <!-- More Actions Dropdown -->
                    <div class="dropdown">
                      <button class="btn btn-xs btn-icon btn-label-secondary rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Diğer İşlemler">
                        <i class="icon-base ti tabler-dots-vertical fs-6"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                          <a class="dropdown-item btn-open-profile" href="javascript:void(0);" data-adventurer="{{ json_encode($adv) }}">
                            <i class="icon-base ti tabler-id me-2 text-primary"></i> Maceracı Profil Kartı
                          </a>
                        </li>
                        @if($isFallen)
                          <li>
                            <a class="dropdown-item text-success btn-status-change" href="javascript:void(0);" data-id="{{ $adv->id }}" data-status="active" data-name="{{ $adv->character_name }}">
                              <i class="icon-base ti tabler-heart-rate-monitor me-2"></i> Yeniden Canlandır (Aktif)
                            </a>
                          </li>
                        @else
                          <li>
                            <a class="dropdown-item text-danger btn-status-change" href="javascript:void(0);" data-id="{{ $adv->id }}" data-status="kia" data-name="{{ $adv->character_name }}">
                              <i class="icon-base ti tabler-skull me-2"></i> Şehit Düştü (KIA)
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item text-warning btn-status-change" href="javascript:void(0);" data-id="{{ $adv->id }}" data-status="mia" data-name="{{ $adv->character_name }}">
                              <i class="icon-base ti tabler-compass me-2"></i> Kayıp İlan Et (MIA)
                            </a>
                          </li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <a class="dropdown-item text-danger btn-delete-adv" href="javascript:void(0);" data-id="{{ $adv->id }}" data-name="{{ $adv->character_name }}">
                            <i class="icon-base ti tabler-trash me-2"></i> Maceracıyı Sil
                          </a>
                        </li>
                      </ul>
                    </div>
                  @else
                    <button type="button" class="btn btn-xs btn-label-secondary rounded-pill btn-open-profile" data-adventurer="{{ json_encode($adv) }}">
                      <i class="icon-base ti tabler-eye me-1"></i> İncele
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-body-secondary">
                <i class="icon-base ti tabler-info-circle fs-3 d-block mb-1"></i>
                Kayıt bulunamadı.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- VIEW 2: CARDS / GRID VIEW (RPG CHARACTER CARDS) -->
  <div id="viewContainerCards" class="p-4 d-none">
    <div class="row g-4" id="cardsGridContainer">
      @foreach($allAdventurers as $adv)
        @php
          $isFallen = in_array($adv->status, ['kia', 'mia']);
          $isKia = $adv->status === 'kia' || strtoupper($adv->reason ?? '') === 'KIA';
          $isMia = $adv->status === 'mia' || strtoupper($adv->reason ?? '') === 'MIA';
          $effectiveLevel = $adv->effective_level;
          $progressPercent = $adv->getProgressPercentage();
          $nextLevelXp = $adv->getNextLevelXp();
          $xpProgressLabel = $adv->getXpProgressLabel();
          $remainingXp = $adv->getRemainingXpToNextLevel();

          if ($isKia) {
            $rankColor = 'bg-label-danger';
            $rankText = 'KIA';
          } elseif ($isMia) {
            $rankColor = 'bg-label-warning';
            $rankText = 'MIA';
          } else {
            $rankColor = match($adv->rank) {
              'Clerk' => 'bg-label-info',
              'Aday' => 'bg-label-secondary',
              'Üye' => 'bg-label-primary',
              'Uzman' => 'bg-label-success',
              'Kıdemli' => 'bg-label-warning',
              'Gezgin' => 'bg-label-info',
              'Inaktif' => 'bg-label-dark',
              default => 'bg-label-secondary'
            };
            $rankText = $adv->rank;
          }

          $levelBadgeColor = match(true) {
            $effectiveLevel >= 10 => 'bg-label-danger',
            $effectiveLevel >= 6 => 'bg-label-warning',
            $effectiveLevel >= 3 => 'bg-label-primary',
            default => 'bg-label-secondary'
          };
        @endphp
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 adventurer-card-col"
             data-id="{{ $adv->id }}"
             data-category="{{ $adv->category }}"
             data-name="{{ strtolower($adv->character_name) }}"
             data-player="{{ strtolower($adv->player_name ?? '') }}"
             data-rank="{{ $adv->rank }}"
             data-level="{{ $effectiveLevel }}"
             data-status="{{ $adv->status }}">
          
          <div class="card h-100 border shadow-xs adventurer-card">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
              <!-- Top Row: Avatar, Name, Rank Badge (Clean, No Emojis) -->
              <div>
                <div class="d-flex align-items-start justify-content-between mb-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md cursor-pointer btn-open-profile" data-adventurer="{{ json_encode($adv) }}">
                      <span class="avatar-initial rounded-circle {{ $isFallen ? 'bg-label-danger' : 'bg-label-primary' }} fw-bold fs-5 text-uppercase">
                        {{ mb_substr($adv->character_name, 0, 2) }}
                      </span>
                    </div>
                    <div>
                      <h6 class="mb-0 text-heading fw-bold cursor-pointer btn-open-profile" data-adventurer="{{ json_encode($adv) }}">
                        {{ $adv->character_name }}
                      </h6>
                      <small class="text-body-secondary">{{ $adv->player_name ?: 'Sahipsiz' }}</small>
                    </div>
                  </div>
                  <span class="badge {{ $rankColor }} rounded-pill font-monospace" style="font-size: 0.72rem;">
                    {{ $rankText }}
                  </span>
                </div>

                <!-- XP & Level Display (129,141 / 145,000 XP) -->
                <div class="bg-body border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge {{ $levelBadgeColor }} font-monospace fw-bold">Lv {{ $effectiveLevel }}</span>
                    <span class="fw-bold font-monospace text-heading" style="font-size: 0.85rem;">{{ $xpProgressLabel }}</span>
                  </div>
                  <div class="progress rounded-pill shadow-xs" style="height: 7px;" title="{{ $xpProgressLabel }} (%{{ $progressPercent }})">
                    <div class="progress-bar bg-primary progress-bar-animated-smooth" role="progressbar" style="width: {{ $progressPercent }}%;"></div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-body-secondary font-monospace" style="font-size: 0.72rem;">%{{ $progressPercent }}</small>
                    <small class="text-body-secondary font-monospace" style="font-size: 0.72rem;">
                      @if($effectiveLevel >= 20)
                        Maksimum
                      @else
                        Kalan: <strong>{{ number_format($remainingXp) }} XP</strong>
                      @endif
                    </small>
                  </div>
                </div>

                <!-- XP Breakdown mini pills -->
                <div class="d-flex flex-wrap gap-1 mb-3">
                  @if($adv->mission_xp > 0)
                    <span class="badge bg-label-success rounded-pill font-monospace" style="font-size: 0.7rem;">Görev: {{ number_format($adv->mission_xp) }}</span>
                  @endif
                  @if($adv->report_xp > 0)
                    <span class="badge bg-label-info rounded-pill font-monospace" style="font-size: 0.7rem;">Rapor: {{ number_format($adv->report_xp) }}</span>
                  @endif
                  @if($adv->extra_xp > 0)
                    <span class="badge bg-label-warning rounded-pill font-monospace" style="font-size: 0.7rem;">EX: {{ number_format($adv->extra_xp) }}</span>
                  @endif
                  @if($adv->sessions_count > 0)
                    <span class="badge bg-body border font-monospace text-heading" style="font-size: 0.7rem;">{{ $adv->sessions_count }} Oyun</span>
                  @endif
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                <button type="button" class="btn btn-xs btn-label-secondary rounded-pill flex-grow-1 btn-open-profile" data-adventurer="{{ json_encode($adv) }}">
                  <i class="icon-base ti tabler-id me-1"></i> Profil
                </button>
                @if($isAdmin)
                  <button type="button" class="btn btn-xs btn-primary rounded-pill flex-grow-1 btn-quick-xp" 
                          data-id="{{ $adv->id }}" 
                          data-name="{{ $adv->character_name }}" 
                          data-xp="{{ $adv->total_xp }}">
                    <i class="icon-base ti tabler-plus me-1"></i> XP Ver
                  </button>
                @endif
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<!-- Floating Multi-Select Action Bar (Shows when 1+ adventurers are checked) -->
<div class="floating-batch-bar card border shadow-lg px-4 py-2 bg-body" id="floatingBatchBar">
  <div class="d-flex align-items-center gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar avatar-xs rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" id="floatingSelectedCount">0</span>
      <span class="text-heading fw-semibold small">Maceracı Seçildi</span>
    </div>
    <div class="vr my-1"></div>
    <button type="button" class="btn btn-sm btn-success rounded-pill waves-effect shadow-xs" id="btnBatchAwardXpModal">
      <i class="icon-base ti tabler-gift me-1"></i> Toplu XP Dağıt
    </button>
    <button type="button" class="btn btn-sm btn-label-danger rounded-pill waves-effect shadow-xs" id="btnBatchStatusChangeModal">
      <i class="icon-base ti tabler-shield-x me-1"></i> Durum Güncelle
    </button>
    <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-circle" id="btnFloatingCancelSelection" title="Seçimi Temizle">
      <i class="icon-base ti tabler-x fs-6"></i>
    </button>
  </div>
</div>

<!-- ================= OFFCANVAS DRAWERS (VUEXY DEMO ARCHITECTURE) ================= -->

<!-- 1. Offcanvas: Add New Adventurer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdventurer" aria-labelledby="offcanvasAddAdventurerLabel" style="width: 480px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddAdventurerLabel" class="offcanvas-title fw-bold text-heading d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-user-plus text-primary"></i>
      <span>Yeni Maceracı Ekle</span>
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
  </div>
  <div class="offcanvas-body flex-grow-1 p-4">
    <form id="formAddAdventurer">
      @csrf
      <div class="mb-3">
        <label class="form-label fw-semibold" for="addCharacterName">Karakter Adı <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="addCharacterName" name="character_name" placeholder="Örn: Aki, Argus..." required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="addPlayerName">Oyuncu Adı</label>
        <input type="text" class="form-control" id="addPlayerName" name="player_name" placeholder="Örn: Barış Şimşek...">
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold" for="addRank">Rütbe</label>
          <select class="form-select" id="addRank" name="rank">
            @foreach($ranks as $rk)
              <option value="{{ $rk }}" {{ $rk === 'Aday' ? 'selected' : '' }}>{{ $rk }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold" for="addStatus">Durum</label>
          <select class="form-select" id="addStatus" name="status">
            <option value="active" selected>Aktif</option>
            <option value="inactive">Inaktif</option>
            <option value="kia">KIA (Şehit)</option>
            <option value="mia">MIA (Kayıp)</option>
            <option value="retired">Emekli</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="addSessionsCount">Oynanan Görev / Oturum Sayısı</label>
        <input type="number" class="form-control font-monospace" id="addSessionsCount" name="sessions_count" value="1" min="0">
      </div>

      <div class="border-top pt-3 mb-3">
        <h6 class="fw-bold text-heading mb-1 d-flex align-items-center gap-1">
          <i class="icon-base ti tabler-chart-pie text-primary fs-5"></i> XP Bileşenleri
        </h6>
        <small class="text-body-secondary">Bileşenler girildikçe Toplam XP ve Pathfinder Seviyesi anında hesaplanır.</small>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small" for="addMissionXp">Görev XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addMissionXp" name="mission_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addReportXp">Rapor / RP XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addReportXp" name="report_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addRankXp">Rütbe Bonusu XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addRankXp" name="rank_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addExtraXp">Ekstra XP (EX)</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addExtraXp" name="extra_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addBailiffXp">Bailiff Bonusu</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addBailiffXp" name="bailiff_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addBonusXp">Diğer Bonuslar</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addBonusXp" name="bonus_xp" value="0" min="0">
        </div>
      </div>

      <!-- Real-time Live Calculation Box -->
      <div class="card bg-body border shadow-none p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-body-secondary small">Hesaplanan Toplam XP:</span>
            <h4 class="mb-0 fw-bold font-monospace text-primary" id="addComputedTotalXp">0 XP</h4>
          </div>
          <div class="text-end">
            <span class="text-body-secondary small">Otomatik Seviye:</span>
            <h4 class="mb-0 fw-bold font-monospace text-success" id="addComputedLevel">Seviye 1</h4>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small" for="addNotes">Notlar / Açıklama</label>
        <textarea class="form-control" id="addNotes" name="notes" rows="2" placeholder="Karakter notları..."></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" id="btnSubmitAddAdventurer">Maceracıyı Kaydet</button>
        <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="offcanvas">Vazgeç</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Offcanvas: Edit Adventurer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditAdventurer" aria-labelledby="offcanvasEditAdventurerLabel" style="width: 480px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditAdventurerLabel" class="offcanvas-title fw-bold text-heading d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-pencil text-primary"></i>
      <span>Maceracıyı Düzenle</span>
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
  </div>
  <div class="offcanvas-body flex-grow-1 p-4">
    <form id="formEditAdventurer">
      @csrf
      @method('PUT')
      <input type="hidden" id="editAdventurerId" name="id">

      <div class="mb-3">
        <label class="form-label fw-semibold" for="editCharacterName">Karakter Adı <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="editCharacterName" name="character_name" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="editPlayerName">Oyuncu Adı</label>
        <input type="text" class="form-control" id="editPlayerName" name="player_name">
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold" for="editRank">Rütbe</label>
          <select class="form-select" id="editRank" name="rank">
            @foreach($ranks as $rk)
              <option value="{{ $rk }}">{{ $rk }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold" for="editStatus">Durum</label>
          <select class="form-select" id="editStatus" name="status">
            <option value="active">Aktif</option>
            <option value="inactive">Inaktif</option>
            <option value="kia">KIA (Şehit)</option>
            <option value="mia">MIA (Kayıp)</option>
            <option value="retired">Emekli</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="editSessionsCount">Oynanan Görev Sayısı</label>
        <input type="number" class="form-control font-monospace" id="editSessionsCount" name="sessions_count" min="0">
      </div>

      <div class="border-top pt-3 mb-3">
        <h6 class="fw-bold text-heading mb-1 d-flex align-items-center gap-1">
          <i class="icon-base ti tabler-chart-pie text-primary fs-5"></i> XP Bileşenleri
        </h6>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small" for="editMissionXp">Görev XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editMissionXp" name="mission_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editReportXp">Rapor XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editReportXp" name="report_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editRankXp">Rütbe XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editRankXp" name="rank_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editExtraXp">Ekstra XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editExtraXp" name="extra_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editBailiffXp">Bailiff XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editBailiffXp" name="bailiff_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editBonusXp">Bonus XP</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editBonusXp" name="bonus_xp" min="0">
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-bold text-heading" for="editTotalXp">Toplam XP (Değer)</label>
          <input type="number" class="form-control form-control-sm font-monospace" id="editTotalXp" name="total_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small fw-bold text-heading" for="editLevel">Seviye (Level)</label>
          <input type="number" class="form-control form-control-sm font-monospace" id="editLevel" name="level" min="1" max="20">
        </div>
      </div>

      <div class="card bg-body border shadow-none p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-body-secondary small">Hesaplanan Toplam XP:</span>
            <h4 class="mb-0 fw-bold font-monospace text-primary" id="editComputedTotalXp">0 XP</h4>
          </div>
          <div class="text-end">
            <span class="text-body-secondary small">Otomatik Seviye:</span>
            <h4 class="mb-0 fw-bold font-monospace text-success" id="editComputedLevel">Seviye 1</h4>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small" for="editNotes">Notlar</label>
        <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" id="btnSubmitEditAdventurer">Değişiklikleri Kaydet</button>
        <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="offcanvas">Vazgeç</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Offcanvas: Hero Adventurer Profile Sheet -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdventurerProfile" aria-labelledby="offcanvasAdventurerProfileLabel" style="width: 500px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAdventurerProfileLabel" class="offcanvas-title fw-bold text-heading d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-id text-primary"></i>
      <span>Maceracı Detay Kartı</span>
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
  </div>
  <div class="offcanvas-body flex-grow-1 p-4" id="profileDrawerContent">
    <!-- Content injected dynamically via JS -->
  </div>
</div>

<!-- 4. Modal: Quick XP Award (Single) -->
<div class="modal fade" id="modalQuickXp" tabindex="-1" aria-labelledby="modalQuickXpLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom pb-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2" id="modalQuickXpLabel">
          <i class="icon-base ti tabler-plus text-success"></i>
          <span>Hızlı XP Ödülü Ekle</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <form id="formQuickXp">
        @csrf
        <input type="hidden" id="quickXpAdventurerId" name="id">

        <div class="modal-body py-4">
          <div class="d-flex align-items-center justify-content-between bg-body border rounded p-3 mb-3">
            <div>
              <span class="text-body-secondary small">Maceracı:</span>
              <h6 class="mb-0 fw-bold text-heading" id="quickXpTargetName">—</h6>
            </div>
            <div class="text-end">
              <span class="text-body-secondary small">Mevcut XP:</span>
              <h6 class="mb-0 fw-bold font-monospace text-primary" id="quickXpCurrentXp">0 XP</h6>
            </div>
          </div>

          <label class="form-label small fw-semibold">Hızlı Miktar Seç:</label>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="100">+100</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="250">+250</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="500">+500</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="1000">+1,000</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="2500">+2,500</button>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="quickXpAmount">Eklenecek XP Miktarı <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-lg font-monospace text-primary fw-bold" id="quickXpAmount" name="amount" placeholder="Örn: 500" required min="1" max="500000">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="quickXpCategory">XP Kategorisi</label>
            <select class="form-select" id="quickXpCategory" name="category">
              <option value="mission" selected>Görev XP (+1 Görev Sayısı Ekler)</option>
              <option value="report">Rapor / RP Yazımı XP</option>
              <option value="rank">Rütbe Terfi Bonusu</option>
              <option value="extra">Ekstra (EX) XP</option>
              <option value="bailiff">Bailiff Bonusu</option>
            </select>
          </div>

          <div>
            <label class="form-label small" for="quickXpNote">Açıklama / Oturum Notu</label>
            <input type="text" class="form-control" id="quickXpNote" name="note" placeholder="Örn: Oturum #14 Ejderha İni Temizliği">
          </div>
        </div>

        <div class="modal-footer border-top pt-3">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-success rounded-pill" id="btnSubmitQuickXp">XP'yi Ver</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 5. Modal: Batch XP Award (Party Splitter / Multiple Adventurers) -->
<div class="modal fade" id="modalBatchXp" tabindex="-1" aria-labelledby="modalBatchXpLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom pb-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2" id="modalBatchXpLabel">
          <i class="icon-base ti tabler-users-group text-primary"></i>
          <span>Gruba / Seçilenlere Toplu XP Dağıtımı</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <form id="formBatchXp">
        @csrf
        <div class="modal-body py-4">
          <div class="alert alert-primary d-flex align-items-center gap-2 py-2 px-3 mb-3">
            <i class="icon-base ti tabler-info-circle fs-5"></i>
            <span class="small" id="batchXpRecipientsSummary">Seçilen maceracılar listelenecek...</span>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="batchXpAmount">Her Maceracıya Eklenecek XP <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-lg font-monospace text-primary fw-bold" id="batchXpAmount" name="amount" placeholder="Örn: 750" required min="1" max="500000">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="batchXpCategory">XP Kategorisi</label>
            <select class="form-select" id="batchXpCategory" name="category">
              <option value="mission" selected>Görev XP (+1 Görev Sayısı Ekler)</option>
              <option value="report">Rapor / RP XP</option>
              <option value="rank">Rütbe Bonusu</option>
              <option value="extra">Ekstra XP</option>
            </select>
          </div>

          <div>
            <label class="form-label small" for="batchXpNote">Oturum / Görev Başlığı</label>
            <input type="text" class="form-control" id="batchXpNote" name="note" placeholder="Örn: Oturum #15 Goblin Zindanları">
          </div>
        </div>

        <div class="modal-footer border-top pt-3">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">Vazgeç</button>
          <button type="submit" class="btn btn-primary rounded-pill" id="btnSubmitBatchXp">Tüm Gruba XP'yi Dağıt</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const fastXpThresholds = @json($fastThresholds);
  let activeTabFilter = 'guild'; // Default tab: Aktif Kadro (Lonca Üyeleri)
  let selectedAdventurerIds = [];

  // Helper: Fast Progression Level calculation
  function calculateLevel(xp) {
    let lvl = 1;
    for (const [level, threshold] of Object.entries(fastXpThresholds)) {
      if (xp >= threshold) {
        lvl = parseInt(level);
      } else {
        break;
      }
    }
    return lvl;
  }

  // View Switcher (Table vs Cards)
  const btnViewTable = document.getElementById('btnViewTable');
  const btnViewCards = document.getElementById('btnViewCards');
  const viewTable = document.getElementById('viewContainerTable');
  const viewCards = document.getElementById('viewContainerCards');

  btnViewTable.addEventListener('click', function() {
    btnViewTable.classList.add('active');
    btnViewCards.classList.remove('active');
    viewTable.classList.remove('d-none');
    viewCards.classList.add('d-none');
  });

  btnViewCards.addEventListener('click', function() {
    btnViewCards.classList.add('active');
    btnViewTable.classList.remove('active');
    viewCards.classList.remove('d-none');
    viewTable.classList.add('d-none');
  });

  // Top KPI Card Clicks to Filter
  document.querySelectorAll('.filter-card').forEach(card => {
    card.addEventListener('click', function() {
      const filter = this.dataset.filter;
      if (filter === 'guild') {
        document.getElementById('tab-guild-btn').click();
      } else if (filter === 'candidate') {
        document.getElementById('tab-candidates-btn').click();
      } else if (filter === 'inactive') {
        document.getElementById('tab-inactives-btn').click();
      } else if (filter === 'fallen') {
        document.getElementById('tab-fallen-btn').click();
      } else if (filter === 'all') {
        document.getElementById('tab-all-btn').click();
      }
    });
  });

  // Tab Filtering Buttons
  document.querySelectorAll('.tab-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.tab-filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      activeTabFilter = this.dataset.filter;
      applyFilters();
    });
  });

  // Filtering Logic
  const searchInput = document.getElementById('searchTable');
  const filterRank = document.getElementById('filterRank');
  const filterLevel = document.getElementById('filterLevel');
  const chkHideInactive = document.getElementById('chkHideInactive');
  const chkHideCandidates = document.getElementById('chkHideCandidates');
  const btnClear = document.getElementById('btnClearFilters');

  function applyFilters() {
    const term = searchInput.value.toLowerCase().trim();
    const rankVal = filterRank.value;
    const levelVal = filterLevel.value;
    const hideInactive = chkHideInactive ? chkHideInactive.checked : false;
    const hideCandidates = chkHideCandidates ? chkHideCandidates.checked : false;

    // Filter Table Rows
    document.querySelectorAll('.adventurer-row').forEach(row => {
      const category = row.dataset.category;
      const name = row.dataset.name || '';
      const player = row.dataset.player || '';
      const rank = row.dataset.rank || '';
      const level = row.dataset.level || '';

      let matchCategory = false;
      if (activeTabFilter === 'all') {
        matchCategory = true;
      } else if (activeTabFilter === 'guild') {
        matchCategory = (category === 'guild');
      } else if (activeTabFilter === 'candidate') {
        matchCategory = (category === 'candidate');
      } else if (activeTabFilter === 'inactive') {
        matchCategory = (category === 'inactive');
      } else if (activeTabFilter === 'fallen') {
        matchCategory = (category === 'fallen');
      }

      // Hide Inactive / Hide Candidates switches
      if (hideInactive && category === 'inactive') {
        matchCategory = false;
      }
      if (hideCandidates && category === 'candidate') {
        matchCategory = false;
      }

      const matchSearch = !term || name.includes(term) || player.includes(term);
      const matchRank = !rankVal || rank === rankVal;
      const matchLevel = !levelVal || level === levelVal;

      if (matchCategory && matchSearch && matchRank && matchLevel) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });

    // Filter Cards
    document.querySelectorAll('.adventurer-card-col').forEach(cardCol => {
      const category = cardCol.dataset.category;
      const name = cardCol.dataset.name || '';
      const player = cardCol.dataset.player || '';
      const rank = cardCol.dataset.rank || '';
      const level = cardCol.dataset.level || '';

      let matchCategory = false;
      if (activeTabFilter === 'all') {
        matchCategory = true;
      } else if (activeTabFilter === 'guild') {
        matchCategory = (category === 'guild');
      } else if (activeTabFilter === 'candidate') {
        matchCategory = (category === 'candidate');
      } else if (activeTabFilter === 'inactive') {
        matchCategory = (category === 'inactive');
      } else if (activeTabFilter === 'fallen') {
        matchCategory = (category === 'fallen');
      }

      if (hideInactive && category === 'inactive') {
        matchCategory = false;
      }
      if (hideCandidates && category === 'candidate') {
        matchCategory = false;
      }

      const matchSearch = !term || name.includes(term) || player.includes(term);
      const matchRank = !rankVal || rank === rankVal;
      const matchLevel = !levelVal || level === levelVal;

      if (matchCategory && matchSearch && matchRank && matchLevel) {
        cardCol.style.display = '';
      } else {
        cardCol.style.display = 'none';
      }
    });
  }

  if (searchInput) searchInput.addEventListener('input', applyFilters);
  if (filterRank) filterRank.addEventListener('change', applyFilters);
  if (filterLevel) filterLevel.addEventListener('change', applyFilters);
  if (chkHideInactive) chkHideInactive.addEventListener('change', applyFilters);
  if (chkHideCandidates) chkHideCandidates.addEventListener('change', applyFilters);

  if (btnClear) {
    btnClear.addEventListener('click', function() {
      searchInput.value = '';
      filterRank.value = '';
      filterLevel.value = '';
      if (chkHideInactive) chkHideInactive.checked = false;
      if (chkHideCandidates) chkHideCandidates.checked = false;
      document.getElementById('tab-guild-btn').click();
    });
  }

  // Initialize initial filter (Guild active members tab)
  applyFilters();

  // Multi-Select Checkboxes & Floating Batch Bar
  const checkSelectAll = document.getElementById('checkSelectAll');
  const floatingBatchBar = document.getElementById('floatingBatchBar');
  const floatingSelectedCount = document.getElementById('floatingSelectedCount');

  function updateBatchBar() {
    selectedAdventurerIds = [];
    document.querySelectorAll('.row-select-check:checked').forEach(chk => {
      selectedAdventurerIds.push(chk.value);
    });

    const count = selectedAdventurerIds.length;
    floatingSelectedCount.textContent = count;

    if (count > 0) {
      floatingBatchBar.classList.add('show');
    } else {
      floatingBatchBar.classList.remove('show');
    }
  }

  document.querySelectorAll('.row-select-check').forEach(chk => {
    chk.addEventListener('change', updateBatchBar);
  });

  if (checkSelectAll) {
    checkSelectAll.addEventListener('change', function() {
      const checked = this.checked;
      document.querySelectorAll('.adventurer-row').forEach(row => {
        if (row.style.display !== 'none') {
          const chk = row.querySelector('.row-select-check');
          if (chk) chk.checked = checked;
        }
      });
      updateBatchBar();
    });
  }

  document.getElementById('btnFloatingCancelSelection').addEventListener('click', function() {
    document.querySelectorAll('.row-select-check').forEach(chk => chk.checked = false);
    if (checkSelectAll) checkSelectAll.checked = false;
    updateBatchBar();
  });

  // Group / Party XP Button trigger
  const btnTriggerPartyXp = document.getElementById('btnTriggerPartyXp');
  if (btnTriggerPartyXp) {
    btnTriggerPartyXp.addEventListener('click', function() {
      if (selectedAdventurerIds.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'Maceracı Seçin',
          text: 'Lütfen tablodan XP dağıtmak istediğiniz maceracıların kutucuklarını işaretleyin.',
          confirmButtonText: 'Tamam',
          customClass: { confirmButton: 'btn btn-primary rounded-pill' },
          buttonsStyling: false
        });
        return;
      }
      openBatchXpModal();
    });
  }

  document.getElementById('btnBatchAwardXpModal').addEventListener('click', openBatchXpModal);

  function openBatchXpModal() {
    document.getElementById('batchXpRecipientsSummary').textContent = `${selectedAdventurerIds.length} maceracıya eşit miktarda XP eklenecektir.`;
    document.getElementById('batchXpAmount').value = '';
    document.getElementById('batchXpNote').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modalBatchXp'));
    modal.show();
  }

  // Submit Batch XP
  const formBatchXp = document.getElementById('formBatchXp');
  if (formBatchXp) {
    formBatchXp.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitBatchXp');
      btn.disabled = true;

      const payload = {
        adventurer_ids: selectedAdventurerIds,
        amount: parseInt(document.getElementById('batchXpAmount').value),
        category: document.getElementById('batchXpCategory').value,
        note: document.getElementById('batchXpNote').value || null
      };

      fetch("{{ route('valiant-shade.pages-xp-tracker.batch-xp') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Toplu XP Dağıtıldı!',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Hata!', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Ağ hatası oluştu.' });
      });
    });
  }

  // Hero Adventurer Profile Offcanvas Drawer
  document.querySelectorAll('.btn-open-profile').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const adv = JSON.parse(this.dataset.adventurer);
      openProfileDrawer(adv);
    });
  });

  function openProfileDrawer(adv) {
    const isFallen = adv.status === 'kia' || adv.status === 'mia';
    const isKia = adv.status === 'kia' || (adv.reason && adv.reason.toUpperCase() === 'KIA');
    const container = document.getElementById('profileDrawerContent');

    const effLevel = calculateLevel(adv.total_xp);
    const nextLevel = Math.min(20, effLevel + 1);
    const nextLevelXp = fastXpThresholds[nextLevel] || 2400000;
    const currentBaseXp = fastXpThresholds[effLevel] || 0;
    const progressPercent = effLevel >= 20 ? 100 : (nextLevelXp > currentBaseXp 
      ? Math.round(Math.max(0, Math.min(100, ((adv.total_xp - currentBaseXp) / (nextLevelXp - currentBaseXp)) * 100))) 
      : 100);
    const remainingXp = Math.max(0, nextLevelXp - adv.total_xp);
    const progressLabel = effLevel >= 20 
      ? `${adv.total_xp.toLocaleString()} / ${nextLevelXp.toLocaleString()} XP (MAX)`
      : `${adv.total_xp.toLocaleString()} / ${nextLevelXp.toLocaleString()} XP`;

    container.innerHTML = `
      <div class="d-flex align-items-center gap-3 p-3 bg-body border rounded mb-4">
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-circle ${isFallen ? 'bg-label-danger' : 'bg-label-primary'} fw-bold fs-3 text-uppercase">
            ${adv.character_name.substring(0, 2)}
          </span>
        </div>
        <div>
          <h5 class="mb-0 fw-bold text-heading">${adv.character_name}</h5>
          <span class="text-body-secondary small d-flex align-items-center gap-1">
            <i class="icon-base ti tabler-user fs-6"></i> Oyuncu: <strong>${adv.player_name || 'Bilinmiyor'}</strong>
          </span>
          <div class="mt-1">
            <span class="badge ${isFallen ? 'bg-label-danger' : 'bg-label-primary'} rounded-pill font-monospace">${adv.rank}</span>
          </div>
        </div>
      </div>

      <div class="card bg-body border shadow-none p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-heading fw-bold fs-5 font-monospace text-primary">Lv ${effLevel}</span>
          <span class="text-heading fw-bold fs-5 font-monospace">${progressLabel}</span>
        </div>
        <div class="progress rounded-pill shadow-xs mb-2" style="height: 8px;">
          <div class="progress-bar bg-primary" role="progressbar" style="width: ${progressPercent}%;"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center small font-monospace text-body-secondary">
          <span>İlerleme: %${progressPercent}</span>
          <span>${effLevel >= 20 ? 'Maksimum Seviye' : `Sonraki Seviyeye: ${remainingXp.toLocaleString()} XP`}</span>
        </div>
      </div>

      <h6 class="fw-bold text-heading mb-3 d-flex align-items-center gap-1">
        <i class="icon-base ti tabler-chart-pie text-primary fs-5"></i> XP Bileşen Dağılımı
      </h6>
      <div class="row g-2 mb-4 font-monospace">
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">Görev XP</small>
            <strong class="text-success">${(adv.mission_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">Rapor XP</small>
            <strong class="text-info">${(adv.report_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">Rütbe XP</small>
            <strong class="text-primary">${(adv.rank_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">Ekstra (EX)</small>
            <strong class="text-warning">${(adv.extra_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">Bailiff</small>
            <strong class="text-danger">${(adv.bailiff_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">Görev Sayısı</small>
            <strong class="text-heading">${adv.sessions_count || 1} Oturum</strong>
          </div>
        </div>
      </div>

      ${adv.notes ? `
        <div class="mb-4">
          <label class="form-label small fw-semibold text-heading">Maceracı Geçmişi & Notlar:</label>
          <div class="p-3 bg-body border rounded font-monospace small" style="white-space: pre-wrap;">${adv.notes}</div>
        </div>
      ` : ''}

      <div class="border-top pt-3 d-flex gap-2">
        <button type="button" class="btn btn-label-success rounded-pill flex-grow-1 btn-profile-quick-xp" data-id="${adv.id}" data-name="${adv.character_name}" data-xp="${adv.total_xp}">
          <i class="icon-base ti tabler-plus me-1"></i> Hızlı XP Ekle
        </button>
        <button type="button" class="btn btn-label-primary rounded-pill flex-grow-1 btn-profile-edit" data-adventurer='${JSON.stringify(adv)}'>
          <i class="icon-base ti tabler-pencil me-1"></i> Düzenle
        </button>
      </div>
    `;

    // Hook quick XP inside profile drawer
    container.querySelector('.btn-profile-quick-xp').addEventListener('click', function() {
      bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAdventurerProfile')).hide();
      openQuickXpModal(this.dataset.id, this.dataset.name, this.dataset.xp);
    });

    // Hook edit inside profile drawer
    container.querySelector('.btn-profile-edit').addEventListener('click', function() {
      bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAdventurerProfile')).hide();
      openEditDrawer(JSON.parse(this.dataset.adventurer));
    });

    const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasAdventurerProfile'));
    offcanvas.show();
  }

  // Quick XP Functionality
  function openQuickXpModal(id, name, xp) {
    document.getElementById('quickXpAdventurerId').value = id;
    document.getElementById('quickXpTargetName').textContent = name;
    document.getElementById('quickXpCurrentXp').textContent = parseInt(xp).toLocaleString() + ' XP';
    document.getElementById('quickXpAmount').value = '';
    document.getElementById('quickXpNote').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modalQuickXp'));
    modal.show();
  }

  document.querySelectorAll('.btn-quick-xp').forEach(btn => {
    btn.addEventListener('click', function() {
      openQuickXpModal(this.dataset.id, this.dataset.name, this.dataset.xp);
    });
  });

  document.querySelectorAll('.btn-xp-preset').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('quickXpAmount').value = this.dataset.amount;
    });
  });

  // Edit Offcanvas Drawer
  function openEditDrawer(adv) {
    document.getElementById('editAdventurerId').value = adv.id;
    document.getElementById('editCharacterName').value = adv.character_name;
    document.getElementById('editPlayerName').value = adv.player_name || '';
    document.getElementById('editRank').value = adv.rank;
    document.getElementById('editStatus').value = adv.status;
    document.getElementById('editSessionsCount').value = adv.sessions_count;

    document.getElementById('editMissionXp').value = adv.mission_xp;
    document.getElementById('editReportXp').value = adv.report_xp;
    document.getElementById('editRankXp').value = adv.rank_xp;
    document.getElementById('editExtraXp').value = adv.extra_xp;
    document.getElementById('editBailiffXp').value = adv.bailiff_xp;
    document.getElementById('editBonusXp').value = adv.bonus_xp;
    document.getElementById('editTotalXp').value = adv.total_xp;
    document.getElementById('editLevel').value = adv.level;
    document.getElementById('editNotes').value = adv.notes || '';

    updateEditPreview();
    const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEditAdventurer'));
    offcanvas.show();
  }

  document.querySelectorAll('.btn-edit-adv').forEach(btn => {
    btn.addEventListener('click', function() {
      const adv = JSON.parse(this.dataset.adventurer);
      openEditDrawer(adv);
    });
  });

  // Live previews for Add and Edit Drawers
  function updateAddPreview() {
    const m = parseInt(document.getElementById('addMissionXp').value) || 0;
    const r = parseInt(document.getElementById('addReportXp').value) || 0;
    const rk = parseInt(document.getElementById('addRankXp').value) || 0;
    const ex = parseInt(document.getElementById('addExtraXp').value) || 0;
    const b = parseInt(document.getElementById('addBailiffXp').value) || 0;
    const bo = parseInt(document.getElementById('addBonusXp').value) || 0;

    const total = m + r + rk + ex + b + bo;
    const lvl = calculateLevel(total);

    document.getElementById('addComputedTotalXp').textContent = total.toLocaleString() + ' XP';
    document.getElementById('addComputedLevel').textContent = 'Seviye ' + lvl;
  }

  document.querySelectorAll('.xp-input-add').forEach(input => {
    input.addEventListener('input', updateAddPreview);
  });

  function updateEditPreview() {
    const m = parseInt(document.getElementById('editMissionXp').value) || 0;
    const r = parseInt(document.getElementById('editReportXp').value) || 0;
    const rk = parseInt(document.getElementById('editRankXp').value) || 0;
    const ex = parseInt(document.getElementById('editExtraXp').value) || 0;
    const b = parseInt(document.getElementById('editBailiffXp').value) || 0;
    const bo = parseInt(document.getElementById('editBonusXp').value) || 0;

    const computed = m + r + rk + ex + b + bo;
    document.getElementById('editComputedTotalXp').textContent = computed.toLocaleString() + ' XP';
    document.getElementById('editTotalXp').value = computed;
    const lvl = calculateLevel(computed);
    document.getElementById('editLevel').value = lvl;
    document.getElementById('editComputedLevel').textContent = 'Seviye ' + lvl;
  }

  document.querySelectorAll('.xp-input-edit').forEach(input => {
    input.addEventListener('input', updateEditPreview);
  });

  // Form Submit: Add Adventurer (Offcanvas)
  const formAdd = document.getElementById('formAddAdventurer');
  if (formAdd) {
    formAdd.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitAddAdventurer');
      btn.disabled = true;

      const formData = new FormData(formAdd);
      fetch("{{ route('valiant-shade.pages-xp-tracker.store') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Başarılı!',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Eklenemedi.' });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Ağ hatası oluştu.' });
      });
    });
  }

  // Form Submit: Edit Adventurer (Offcanvas)
  const formEdit = document.getElementById('formEditAdventurer');
  if (formEdit) {
    formEdit.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = document.getElementById('editAdventurerId').value;
      const btn = document.getElementById('btnSubmitEditAdventurer');
      btn.disabled = true;

      const formData = new FormData(formEdit);
      fetch("{{ url('/valiant-shade/pages/xp-tracker') }}/" + id, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Güncellendi!',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'Güncellenemedi.' });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Ağ hatası oluştu.' });
      });
    });
  }

  // Form Submit: Single Quick XP
  const formQuickXp = document.getElementById('formQuickXp');
  if (formQuickXp) {
    formQuickXp.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = document.getElementById('quickXpAdventurerId').value;
      const btn = document.getElementById('btnSubmitQuickXp');
      btn.disabled = true;

      const formData = new FormData(formQuickXp);
      fetch("{{ url('/valiant-shade/pages/xp-tracker') }}/" + id + "/quick-xp", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'XP Eklendi!',
            text: data.message,
            timer: 1800,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Hata!', text: data.message || 'XP eklenemedi.' });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Ağ hatası oluştu.' });
      });
    });
  }

  // Status Change (KIA, MIA, Active)
  document.querySelectorAll('.btn-status-change').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const status = this.dataset.status;
      const name = this.dataset.name;

      let promptTitle = 'Durum Güncelleme';
      let promptText = `"${name}" adlı maceracının durumu değiştirilecek. Onaylıyor musunuz?`;

      if (status === 'kia') {
        promptTitle = 'Şehit İlan Et (KIA)';
        promptText = `"${name}" savaş meydanında şehit düştü olarak işaretlenecek ve Onur Köşesine taşınacak.`;
      } else if (status === 'mia') {
        promptTitle = 'Kayıp İlan Et (MIA)';
        promptText = `"${name}" sislerin ardında kayıp olarak işaretlenecek ve Onur Köşesine taşınacak.`;
      } else if (status === 'active') {
        promptTitle = 'Yeniden Saflara Çağır';
        promptText = `"${name}" yeniden canlandırılarak Aktif Maceracılar tablosuna döndürülecek.`;
      }

      Swal.fire({
        title: promptTitle,
        text: promptText,
        icon: status === 'active' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Onayla',
        cancelButtonText: 'Vazgeç',
        customClass: {
          confirmButton: 'btn btn-primary rounded-pill me-2',
          cancelButton: 'btn btn-label-secondary rounded-pill'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          fetch("{{ url('/valiant-shade/pages/xp-tracker') }}/" + id + "/status", {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({ status: status, reason: status.toUpperCase() })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Güncellendi!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: 'Hata!', text: data.message });
            }
          });
        }
      });
    });
  });

  // Delete Adventurer
  document.querySelectorAll('.btn-delete-adv').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const name = this.dataset.name;

      Swal.fire({
        title: 'Maceracıyı Sil?',
        text: `"${name}" adlı maceracı kalıcı olarak silinecektir. Bu işlem geri alınamaz!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal',
        customClass: {
          confirmButton: 'btn btn-danger rounded-pill me-2',
          cancelButton: 'btn btn-label-secondary rounded-pill'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          fetch("{{ url('/valiant-shade/pages/xp-tracker') }}/" + id, {
            method: 'DELETE',
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
                title: 'Silindi!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: 'Hata!', text: data.message });
            }
          });
        }
      });
    });
  });

  // Reset to Defaults Button
  const btnReset = document.getElementById('btnResetDefaults');
  if (btnReset) {
    btnReset.addEventListener('click', function() {
      Swal.fire({
        title: 'Orijinal Master Tablosuna Sıfırla?',
        text: 'Tüm mevcut kayıtlar silinecek ve ekran görüntüsündeki 64 maceracının orijinal verileri yeniden yüklenecektir!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Sıfırla',
        cancelButtonText: 'Vazgeç',
        customClass: {
          confirmButton: 'btn btn-warning rounded-pill me-2',
          cancelButton: 'btn btn-label-secondary rounded-pill'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          fetch("{{ route('valiant-shade.pages-xp-tracker.reset-defaults') }}", {
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
                title: 'Sıfırlandı!',
                text: data.message,
                timer: 1800,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: 'Hata!', text: data.message });
            }
          });
        }
      });
    });
  }
});
</script>
@endsection
