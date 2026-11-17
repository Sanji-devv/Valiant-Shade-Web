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

  .xp-toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
  }
  .xp-toolbar-btn {
    display: inline-flex;
    width: 9rem;
    min-width: 9rem;
    height: 2.25rem;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    padding-right: 0.5rem;
    padding-left: 0.5rem;
  }
  .xp-view-toggle .xp-toolbar-btn {
    width: 6.5rem;
    min-width: 6.5rem;
  }
  .xp-toolbar-btn-icon {
    width: 2.25rem;
    min-width: 2.25rem;
    padding: 0;
  }
  .xp-row-actions > .btn,
  .xp-row-actions .dropdown > .btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .xp-row-actions .btn-quick-xp {
    width: 3.25rem;
  }
  .xp-row-actions > .btn-open-profile {
    width: auto;
    min-width: 4.5rem;
    padding: 0 0.65rem;
  }
  .xp-filter-menu {
    min-width: 13rem;
    max-height: 18rem;
    overflow-y: auto;
  }
  .xp-filter-controls .xp-toolbar-btn:not(.xp-toolbar-btn-icon) {
    width: 8rem;
    min-width: 8rem;
  }
  .xp-filter-controls .xp-toolbar-btn-icon {
    flex: 0 0 2.25rem;
  }
  .xp-filter-controls .input-group {
    width: 10rem;
    min-width: 10rem !important;
    max-width: 10rem !important;
  }
  .xp-fallen-highlight #tab-fallen-btn.active {
    background-color: var(--bs-danger);
    border-color: var(--bs-danger);
    color: #fff !important;
  }
  .xp-fallen-highlight #tab-fallen-btn.active .badge {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
  }
  .xp-fallen-highlight .fallen-row td:first-child {
    box-shadow: inset 3px 0 0 var(--bs-danger);
  }
  .xp-fallen-highlight .fallen-row:hover {
    background-color: rgba(234, 84, 85, 0.08) !important;
  }
  .xp-fallen-highlight .fallen-row .card {
    border-color: rgba(234, 84, 85, 0.45) !important;
  }
  @media (max-width: 575.98px) {
    .xp-toolbar-actions > * {
      flex: 1 1 auto;
    }
    .xp-toolbar-btn {
      width: 100%;
      min-width: 0;
    }
    .xp-view-toggle .xp-toolbar-btn {
      width: 50%;
      min-width: 0;
    }
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
    <p class="text-body-secondary small mb-0">{{ __('Valiant Shade character experience points, levels and fallen / retired records.') }}</p>
  </div>

  <div class="xp-toolbar-actions">
    <!-- View Mode Switcher: Table vs Cards -->
    <div class="btn-group btn-group-sm p-0 bg-body border rounded-pill shadow-xs xp-view-toggle" role="group" aria-label="{{ __('Detailed Table View') }}">
      <button type="button" class="btn btn-sm rounded-pill px-3 active fw-semibold xp-toolbar-btn" id="btnViewTable" title="{{ __('Detailed Table View') }}">
        <i class="icon-base ti tabler-table me-1"></i> {{ __('Table') }}
      </button>
      <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold xp-toolbar-btn" id="btnViewCards" title="{{ __('RPG Character Cards View') }}">
        <i class="icon-base ti tabler-layout-grid me-1"></i> {{ __('Cards') }}
      </button>
    </div>

    @if($isAdmin)
      @if(auth()->user()?->isAdmin())
        <input type="file" id="xpTrackerJsonFile" accept=".json,application/json" class="d-none">
        <div class="dropdown">
          <button type="button" class="btn btn-label-secondary btn-sm rounded-pill dropdown-toggle xp-toolbar-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-base ti tabler-settings me-1"></i> {{ __('Data Tools') }}
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><button type="button" class="dropdown-item" id="btnImportXp"><i class="icon-base ti tabler-database-import me-2 text-info"></i>{{ __('Import Data') }}</button></li>
            <li><hr class="dropdown-divider"></li>
            <li><button type="button" class="dropdown-item text-danger" id="btnClearXp"><i class="icon-base ti tabler-trash me-2"></i>{{ __('Delete All XP Records') }}</button></li>
          </ul>
        </div>
      @endif

    @else
      <span class="badge bg-label-secondary rounded-pill d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
        <i class="icon-base ti tabler-lock fs-6 text-warning"></i>
        <span>{{ __('Read Only') }}</span>
      </span>
    @endif
  </div>
</div>

<!-- Statistical Metric Cards (Vuexy KPI Cards) -->
@php
  $fallenCount = $allAdventurers->filter(fn ($adv) => in_array($adv->status, ['kia', 'mia', 'retired'], true))->count();
@endphp
<div class="row g-4 mb-4">
  <!-- Card 1: Character Roster -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border cursor-pointer filter-card" data-filter="roster" title="{{ __('Filter Character Roster') }}">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Characters in Roster') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-primary" id="statRosterCount">
                {{ $statistics['total_active_count'] ?? 0 }}
              </h3>
              <span class="badge bg-label-primary font-monospace">{{ __('Characters') }}</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ti tabler-shield-check icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">{{ __('Candidates and inactive characters are included') }}</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: Total Characters -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Total Characters') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-info" id="statTotalCharacters">
                {{ $allAdventurers->count() }}
              </h3>
              <span class="badge bg-label-info font-monospace">{{ __('Records') }}</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ti tabler-users icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">{{ __('All tracked player characters') }}</small>
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
          <small class="text-truncate text-body-secondary" style="max-width: 220px;" title="{{ __('Average Level: Lv :lvl', ['lvl' => $statistics['avg_level'] ?? 1]) }}">
            {{ __('Avg. Level:') }} <strong class="text-heading">Lv {{ $statistics['avg_level'] ?? 1 }}</strong> &bull; {{ __('Leader:') }} {{ $statistics['top_adventurer']['name'] ?? '—' }}
          </small>
        </div>
      </div>
    </div>
  </div>

  <!-- Card 4: Fallen and Retired -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 shadow-none border cursor-pointer filter-card" data-filter="fallen" title="{{ __('Fallen / Retired') }}">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="content-left">
            <span class="text-heading fw-semibold">{{ __('Fallen / Retired') }}</span>
            <div class="d-flex align-items-baseline gap-2 my-1">
              <h3 class="mb-0 fw-bold text-heading font-monospace text-danger" id="statFallenCount">
                {{ $fallenCount }}
              </h3>
              <span class="badge bg-label-danger font-monospace">{{ __('Records') }}</span>
            </div>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-danger">
              <i class="icon-base ti tabler-skull icon-26px"></i>
            </span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-top">
          <small class="text-body-secondary">{{ __('Honoring fallen and lost adventurers') }}</small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content Area: Interactive Tabs & Filtering Bar -->
<div class="card border shadow-none mb-4" id="xpTrackerCard">
  <div class="card-header border-bottom p-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <ul class="nav nav-pills card-header-pills flex-wrap gap-1" id="xpTrackerTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active d-flex align-items-center gap-2 rounded-pill px-3 py-2 tab-filter-btn" data-filter="roster" id="tab-guild-btn" type="button">
            <i class="icon-base ti tabler-shield-check fs-5"></i>
            <span class="fw-semibold">{{ __('Characters') }}</span>
            <span class="badge rounded-pill bg-primary ms-1" id="badgeGuildCount">{{ $statistics['total_active_count'] ?? 0 }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-2 rounded-pill px-3 py-2 text-danger tab-filter-btn" data-filter="fallen" id="tab-fallen-btn" type="button">
            <i class="icon-base ti tabler-skull fs-5"></i>
            <span class="fw-semibold">{{ __('Fallen / Retired') }}</span>
            <span class="badge rounded-pill bg-label-danger ms-1" id="badgeFallenCount">{{ $fallenCount }}</span>
          </button>
        </li>
      </ul>
      @if($isAdmin)
        <button type="button" class="btn btn-primary btn-sm rounded-pill waves-effect waves-light shadow-xs xp-toolbar-btn" id="btnAddPlayerCharacter" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddAdventurer">
          <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add Player / Character') }}
        </button>
      @endif
      <div class="d-flex align-items-center gap-2 flex-wrap ms-auto xp-filter-controls">
        <!-- Search Input -->
        <div class="input-group input-group-merge input-group-sm" style="min-width: 170px; max-width: 220px;">
          <span class="input-group-text"><i class="icon-base ti tabler-search fs-6"></i></span>
          <input type="text" class="form-control" id="searchTable" placeholder="{{ __('Search character...') }}" value="{{ $filters['search'] ?? '' }}">
        </div>

        <div class="dropdown">
          <button type="button" class="btn btn-sm btn-label-secondary dropdown-toggle xp-toolbar-btn" id="btnRankFilter" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="icon-base ti tabler-filter me-1"></i> <span id="rankFilterLabel">{{ __('All Ranks') }}</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-2 xp-filter-menu" aria-labelledby="btnRankFilter" id="rankFilterMenu">
            @foreach($ranks as $rk)
              <label class="dropdown-item rounded d-flex align-items-center gap-2 py-2">
                <input class="form-check-input m-0 rank-filter-option" type="checkbox" value="{{ $rk }}">
                <span>{{ __($rk) }}</span>
              </label>
            @endforeach
            <div class="dropdown-divider"></div>
            <button type="button" class="dropdown-item text-primary" id="btnClearRanks">{{ __('Clear Rank Filters') }}</button>
          </div>
        </div>

        <select class="form-select form-select-sm w-auto xp-toolbar-btn" id="filterLevel" aria-label="{{ __('Level Filter') }}">
          <option value="">{{ __('All Levels') }}</option>
          @for($i = 1; $i <= 20; $i++)
            <option value="{{ $i }}" {{ ($filters['level'] ?? '') == $i ? 'selected' : '' }}>{{ __('Level') }} {{ $i }}</option>
          @endfor
        </select>

        <div class="dropdown">
          <button type="button" class="btn btn-sm btn-label-secondary dropdown-toggle xp-toolbar-btn" id="btnStatusFilter" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="icon-base ti tabler-adjustments me-1"></i> <span id="statusFilterLabel">{{ __('All Statuses') }}</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-2 xp-filter-menu" aria-labelledby="btnStatusFilter" id="statusFilterMenu">
            @foreach(['kia' => __('KIA'), 'mia' => __('MIA'), 'retired' => __('Retired')] as $statusValue => $statusLabel)
              <label class="dropdown-item rounded d-flex align-items-center gap-2 py-2">
                <input class="form-check-input m-0 status-filter-option" type="checkbox" value="{{ $statusValue }}" checked>
                <span>{{ $statusLabel }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="dropdown">
          <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-pill xp-toolbar-btn xp-toolbar-btn-icon" id="btnXpSettings" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="{{ __('Settings') }}">
            <i class="icon-base ti tabler-settings fs-6"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 250px;">
            <div class="fw-semibold text-heading mb-2">{{ __('Settings') }}</div>
            <label class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch" id="chkFallenRed">
              <span class="form-check-label small">{{ __('Use red color for fallen / retired tab') }}</span>
            </label>
          </div>
        </div>

        <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-pill xp-toolbar-btn xp-toolbar-btn-icon" id="btnClearFilters" title="{{ __('Clear Filters') }}">
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
            <th style="min-width: 160px;">{{ __('Adventurer & Player') }}</th>
            <th class="text-center" style="min-width: 150px; width: 165px;">{{ __('Level & Progress') }}</th>
            <th class="text-center" style="min-width: 100px; width: 110px;">{{ __('Rank / Status') }}</th>
            <th class="text-end" style="min-width: 105px; width: 115px;">{{ __('Total XP') }}</th>
            <th class="text-center" style="min-width: 85px; width: 95px;">{{ __('Quests') }}</th>
            <th style="min-width: 145px; width: 175px;">
              <div class="d-flex align-items-center gap-1">
                <span>{{ __('XP Breakdown') }}</span>
                <i class="icon-base ti tabler-info-circle text-muted fs-6 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Quest / Report / Rank / Extra Gains') }}"></i>
              </div>
            </th>
            <th class="text-end pe-3" style="min-width: 135px; width: 140px;">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($allAdventurers as $adv)
            @php
              $isFallen = in_array($adv->status, ['kia', 'mia', 'retired'], true);
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
              } elseif ($adv->status === 'retired') {
                $rankColor = 'bg-label-danger';
                $rankText = __('Retired');
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
            <tr class="adventurer-row {{ $isFallen ? 'fallen-row' : '' }}" 
                data-id="{{ $adv->id }}" 
                data-category="{{ $isFallen ? 'fallen' : $adv->category }}"
                data-name="{{ strtolower($adv->character_name) }}" 
                data-player="{{ strtolower($adv->player_name ?? '') }}" 
                data-rank="{{ $adv->rank }}" 
                data-level="{{ $effectiveLevel }}"
                data-status="{{ $adv->status }}">
              
              <!-- Character & Player -->
              <td style="min-width: 175px;">
                <div class="d-flex align-items-center gap-2_5">
                  <div class="avatar avatar-sm cursor-pointer btn-open-profile flex-shrink-0" data-id="{{ $adv->id }}" title="{{ __('View Profile') }}">
                    <span class="avatar-initial rounded-circle {{ $isFallen ? ($isKia ? 'bg-label-danger' : 'bg-label-warning') : 'bg-label-primary' }} fw-bold text-uppercase" style="font-size: 0.76rem;">
                      {{ mb_substr($adv->character_name, 0, 2) }}
                    </span>
                  </div>
                  <div class="text-truncate">
                    <h6 class="mb-0 text-heading fw-bold cursor-pointer btn-open-profile text-truncate" data-id="{{ $adv->id }}" style="font-size: 0.88rem;">
                      {{ $adv->character_name }}
                    </h6>
                    <small class="text-body-secondary d-flex align-items-center gap-1 text-truncate" style="font-size: 0.75rem;">
                      <i class="icon-base ti tabler-user fs-7"></i>
                      <span class="text-truncate">{{ $adv->player_name ?: __('Unknown / Unclaimed') }}</span>
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
                        {{ __('Maximum') }}
                      @else
                        {{ __('Remaining:') }} <strong>{{ number_format($remainingXp) }} XP</strong>
                      @endif
                    </small>
                  </div>
                </div>
              </td>

              <!-- Rank / Status (Clean Text, No Emojis/Icons) -->
              <td class="text-center">
                <span class="badge {{ $rankColor }} rounded-pill px-3 py-1 fw-semibold font-monospace">
                  {{ __($rankText) }}
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
                  {{ $adv->sessions_count }} {{ __('Quests') }}
                </span>
              </td>

              <!-- XP Breakdown Pills -->
              <td style="min-width: 145px; width: 175px;">
                <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 175px;">
                  @if($adv->mission_xp > 0)
                    <span class="badge bg-label-success rounded-pill font-monospace" style="font-size: 0.68rem;" title="{{ __('Quest XP') }}">
                      Q: {{ number_format($adv->mission_xp) }}
                    </span>
                  @endif
                  @if($adv->report_xp > 0)
                    <span class="badge bg-label-info rounded-pill font-monospace" style="font-size: 0.68rem;" title="{{ __('Report XP') }}">
                      R: {{ number_format($adv->report_xp) }}
                    </span>
                  @endif
                  @if($adv->rank_xp > 0)
                    <span class="badge bg-label-primary rounded-pill font-monospace" style="font-size: 0.68rem;" title="{{ __('Rank Bonus XP') }}">
                      Rank: {{ number_format($adv->rank_xp) }}
                    </span>
                  @endif
                  @if($adv->extra_xp > 0)
                    <span class="badge bg-label-warning rounded-pill font-monospace" style="font-size: 0.68rem;" title="{{ __('Extra (EX) XP') }}">
                      EX: {{ number_format($adv->extra_xp) }}
                    </span>
                  @endif
                  @if($adv->bailiff_xp > 0)
                    <span class="badge bg-label-danger rounded-pill font-monospace" style="font-size: 0.68rem;" title="{{ __('Bailiff XP') }}">
                      B: {{ number_format($adv->bailiff_xp) }}
                    </span>
                  @endif
                  @if($adv->bonus_xp > 0)
                    <span class="badge bg-label-secondary rounded-pill font-monospace" style="font-size: 0.68rem;" title="{{ __('Other Bonus XP') }}">
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
                <div class="d-flex align-items-center justify-content-end gap-1 xp-row-actions">
                  @if($isAdmin)
                    <!-- Quick XP Button -->
                    <button type="button" class="btn btn-xs btn-label-success rounded-pill btn-quick-xp d-inline-flex align-items-center xp-row-action" 
                            data-id="{{ $adv->id }}" 
                            data-name="{{ $adv->character_name }}" 
                            data-xp="{{ $adv->total_xp }}"
                            title="{{ __('Quick Award XP') }}">
                      <i class="icon-base ti tabler-plus fs-7 me-0_5"></i><span>XP</span>
                    </button>

                    <!-- Edit Button (Opens Offcanvas) -->
                    <button type="button" class="btn btn-xs btn-icon btn-label-primary rounded-circle btn-edit-adv xp-row-action" 
                            data-id="{{ $adv->id }}"
                            title="{{ __('Edit') }}">
                      <i class="icon-base ti tabler-pencil fs-6"></i>
                    </button>

                    <!-- More Actions Dropdown -->
                    <div class="dropdown">
                      <button class="btn btn-xs btn-icon btn-label-secondary rounded-circle xp-row-action" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('More Actions') }}">
                        <i class="icon-base ti tabler-dots-vertical fs-6"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                          <a class="dropdown-item btn-open-profile" href="javascript:void(0);" data-id="{{ $adv->id }}">
                            <i class="icon-base ti tabler-id me-2 text-primary"></i> {{ __('Adventurer Profile Sheet') }}
                          </a>
                        </li>
                        @if($isFallen)
                          <li>
                            <a class="dropdown-item text-success btn-status-change" href="javascript:void(0);" data-id="{{ $adv->id }}" data-status="active" data-name="{{ $adv->character_name }}">
                              <i class="icon-base ti tabler-heart-rate-monitor me-2"></i> {{ __('Revive Adventurer (Active)') }}
                            </a>
                          </li>
                        @else
                          <li>
                            <a class="dropdown-item text-danger btn-status-change" href="javascript:void(0);" data-id="{{ $adv->id }}" data-status="kia" data-name="{{ $adv->character_name }}">
                              <i class="icon-base ti tabler-skull me-2"></i> {{ __('Fallen in Battle (KIA)') }}
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item text-warning btn-status-change" href="javascript:void(0);" data-id="{{ $adv->id }}" data-status="mia" data-name="{{ $adv->character_name }}">
                              <i class="icon-base ti tabler-compass me-2"></i> {{ __('Declare Missing (MIA)') }}
                            </a>
                          </li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <a class="dropdown-item text-danger btn-delete-adv" href="javascript:void(0);" data-id="{{ $adv->id }}" data-name="{{ $adv->character_name }}">
                            <i class="icon-base ti tabler-trash me-2"></i> {{ __('Delete Adventurer') }}
                          </a>
                        </li>
                      </ul>
                    </div>
                  @else
                    <button type="button" class="btn btn-xs btn-label-secondary rounded-pill btn-open-profile xp-row-action" data-id="{{ $adv->id }}">
                      <i class="icon-base ti tabler-eye me-1"></i> {{ __('View') }}
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-body-secondary">
                <i class="icon-base ti tabler-info-circle fs-3 d-block mb-1"></i>
                {{ __('No records found.') }}
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
      <div class="col-12 text-center py-5 text-body-secondary" id="cardsLoadingPlaceholder">
        <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
        <span>{{ __('Loading character cards...') }}</span>
      </div>
    </div>
  </div>
</div>

<!-- ================= OFFCANVAS DRAWERS (VUEXY DEMO ARCHITECTURE) ================= -->

<!-- 1. Offcanvas: Add New Adventurer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdventurer" aria-labelledby="offcanvasAddAdventurerLabel" style="width: 480px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddAdventurerLabel" class="offcanvas-title fw-bold text-heading d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-user-plus text-primary"></i>
      <span>{{ __('Add New Adventurer') }}</span>
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body flex-grow-1 p-4">
    <form id="formAddAdventurer">
      @csrf
      <div class="mb-3">
        <label class="form-label fw-semibold d-block">{{ __('Player Entry') }}</label>
        <div class="btn-group w-100" role="group" aria-label="{{ __('Player Entry') }}">
          <input type="radio" class="btn-check" name="addPlayerMode" id="addPlayerModeNew" value="new" checked>
          <label class="btn btn-outline-primary" for="addPlayerModeNew">{{ __('New Player') }}</label>
          <input type="radio" class="btn-check" name="addPlayerMode" id="addPlayerModeExisting" value="existing" {{ $xpPlayers->isEmpty() ? 'disabled' : '' }}>
          <label class="btn btn-outline-primary" for="addPlayerModeExisting">{{ __('Existing Player') }}</label>
        </div>
      </div>

      <input type="hidden" id="addPlayerName" name="player_name">

      <div class="mb-3" id="addNewPlayerField">
        <label class="form-label fw-semibold" for="addNewPlayerName">{{ __('Player Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="addNewPlayerName" placeholder="{{ __('e.g. Player Name...') }}" required>
      </div>

      <div class="mb-3 d-none" id="addExistingPlayerField">
        <label class="form-label fw-semibold" for="addExistingPlayerName">{{ __('Select Player') }} <span class="text-danger">*</span></label>
        <select class="form-select" id="addExistingPlayerName" disabled>
          <option value="">{{ __('Select a player...') }}</option>
          @foreach($xpPlayers as $xpPlayer)
            <option value="{{ $xpPlayer }}">{{ $xpPlayer }}</option>
          @endforeach
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="addCharacterName">{{ __('Character Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="addCharacterName" name="character_name" placeholder="{{ __('e.g. Aki, Argus...') }}" required>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold" for="addRank">{{ __('Rank') }}</label>
          <select class="form-select" id="addRank" name="rank">
            @foreach($ranks as $rk)
              <option value="{{ $rk }}" {{ $rk === 'Aday' ? 'selected' : '' }}>{{ __($rk) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold" for="addStatus">{{ __('Status') }}</label>
          <select class="form-select" id="addStatus" name="status">
            <option value="active" selected>{{ __('Active') }}</option>
            <option value="inactive">{{ __('Inactive') }}</option>
            <option value="kia">{{ __('KIA (Fallen)') }}</option>
            <option value="mia">{{ __('MIA (Missing)') }}</option>
            <option value="retired">{{ __('Retired') }}</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="addSessionsCount">{{ __('Quests / Sessions Played') }}</label>
        <input type="number" class="form-control font-monospace" id="addSessionsCount" name="sessions_count" value="1" min="0">
      </div>

      <div class="border-top pt-3 mb-3">
        <h6 class="fw-bold text-heading mb-1 d-flex align-items-center gap-1">
          <i class="icon-base ti tabler-chart-pie text-primary fs-5"></i> {{ __('XP Components') }}
        </h6>
        <small class="text-body-secondary">{{ __('Total XP and Pathfinder Level update automatically as values are entered.') }}</small>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small" for="addMissionXp">{{ __('Quest XP') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addMissionXp" name="mission_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addReportXp">{{ __('Report / RP XP') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addReportXp" name="report_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addRankXp">{{ __('Rank Bonus XP') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addRankXp" name="rank_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addExtraXp">{{ __('Extra XP (EX)') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addExtraXp" name="extra_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addBailiffXp">{{ __('Bailiff Bonus') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addBailiffXp" name="bailiff_xp" value="0" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="addBonusXp">{{ __('Other Bonuses') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-add" id="addBonusXp" name="bonus_xp" value="0" min="0">
        </div>
      </div>

      <!-- Real-time Live Calculation Box -->
      <div class="card bg-body border shadow-none p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-body-secondary small">{{ __('Calculated Total XP:') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-primary" id="addComputedTotalXp">0 XP</h4>
          </div>
          <div class="text-end">
            <span class="text-body-secondary small">{{ __('Automatic Level:') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-success" id="addComputedLevel">{{ __('Level 1') }}</h4>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small" for="addNotes">{{ __('Notes / Description') }}</label>
        <textarea class="form-control" id="addNotes" name="notes" rows="2" placeholder="{{ __('Character notes...') }}"></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" id="btnSubmitAddAdventurer">{{ __('Save Adventurer') }}</button>
        <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Offcanvas: Edit Adventurer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditAdventurer" aria-labelledby="offcanvasEditAdventurerLabel" style="width: 480px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditAdventurerLabel" class="offcanvas-title fw-bold text-heading d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-pencil text-primary"></i>
      <span>{{ __('Edit Adventurer') }}</span>
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body flex-grow-1 p-4">
    <form id="formEditAdventurer">
      @csrf
      @method('PUT')
      <input type="hidden" id="editAdventurerId" name="id">

      <div class="mb-3">
        <label class="form-label fw-semibold" for="editCharacterName">{{ __('Character Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="editCharacterName" name="character_name" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="editPlayerName">{{ __('Player Name') }}</label>
        <input type="text" class="form-control" id="editPlayerName" name="player_name">
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold" for="editRank">{{ __('Rank') }}</label>
          <select class="form-select" id="editRank" name="rank">
            @foreach($ranks as $rk)
              <option value="{{ $rk }}">{{ __($rk) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold" for="editStatus">{{ __('Status') }}</label>
          <select class="form-select" id="editStatus" name="status">
            <option value="active">{{ __('Active') }}</option>
            <option value="inactive">{{ __('Inactive') }}</option>
            <option value="kia">{{ __('KIA (Fallen)') }}</option>
            <option value="mia">{{ __('MIA (Missing)') }}</option>
            <option value="retired">{{ __('Retired') }}</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="editSessionsCount">{{ __('Quests / Sessions Played') }}</label>
        <input type="number" class="form-control font-monospace" id="editSessionsCount" name="sessions_count" min="0">
      </div>

      <div class="border-top pt-3 mb-3">
        <h6 class="fw-bold text-heading mb-1 d-flex align-items-center gap-1">
          <i class="icon-base ti tabler-chart-pie text-primary fs-5"></i> {{ __('XP Components') }}
        </h6>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small" for="editMissionXp">{{ __('Quest XP') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editMissionXp" name="mission_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editReportXp">{{ __('Report / RP XP') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editReportXp" name="report_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editRankXp">{{ __('Rank Bonus XP') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editRankXp" name="rank_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editExtraXp">{{ __('Extra XP (EX)') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editExtraXp" name="extra_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editBailiffXp">{{ __('Bailiff Bonus') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editBailiffXp" name="bailiff_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small" for="editBonusXp">{{ __('Other Bonuses') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace xp-input-edit" id="editBonusXp" name="bonus_xp" min="0">
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-6">
          <label class="form-label small fw-bold text-heading" for="editTotalXp">{{ __('Total XP (Value)') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace" id="editTotalXp" name="total_xp" min="0">
        </div>
        <div class="col-6">
          <label class="form-label small fw-bold text-heading" for="editLevel">{{ __('Level') }}</label>
          <input type="number" class="form-control form-control-sm font-monospace" id="editLevel" name="level" min="1" max="20">
        </div>
      </div>

      <div class="card bg-body border shadow-none p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-body-secondary small">{{ __('Calculated Total XP:') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-primary" id="editComputedTotalXp">0 XP</h4>
          </div>
          <div class="text-end">
            <span class="text-body-secondary small">{{ __('Automatic Level:') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-success" id="editComputedLevel">{{ __('Level 1') }}</h4>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small" for="editNotes">{{ __('Notes / Description') }}</label>
        <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" id="btnSubmitEditAdventurer">{{ __('Save Changes') }}</button>
        <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Offcanvas: Hero Adventurer Profile Sheet -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdventurerProfile" aria-labelledby="offcanvasAdventurerProfileLabel" style="width: 500px;">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAdventurerProfileLabel" class="offcanvas-title fw-bold text-heading d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-id text-primary"></i>
      <span>{{ __('Adventurer Profile Sheet') }}</span>
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
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
          <span>{{ __('Add Quick XP Award') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formQuickXp">
        @csrf
        <input type="hidden" id="quickXpAdventurerId" name="id">

        <div class="modal-body py-4">
          <div class="d-flex align-items-center justify-content-between bg-body border rounded p-3 mb-3">
            <div>
              <span class="text-body-secondary small">{{ __('Adventurer:') }}</span>
              <h6 class="mb-0 fw-bold text-heading" id="quickXpTargetName">—</h6>
            </div>
            <div class="text-end">
              <span class="text-body-secondary small">{{ __('Current XP:') }}</span>
              <h6 class="mb-0 fw-bold font-monospace text-primary" id="quickXpCurrentXp">0 XP</h6>
            </div>
          </div>

          <label class="form-label small fw-semibold">{{ __('Select Quick Amount:') }}</label>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="100">+100</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="250">+250</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="500">+500</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="1000">+1,000</button>
            <button type="button" class="btn btn-xs btn-label-primary rounded-pill font-monospace btn-xp-preset" data-amount="2500">+2,500</button>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="quickXpAmount">{{ __('XP Amount to Add') }} <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-lg font-monospace text-primary fw-bold" id="quickXpAmount" name="amount" placeholder="{{ __('e.g. 500') }}" required min="1" max="500000">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="quickXpCategory">{{ __('XP Category') }}</label>
            <select class="form-select" id="quickXpCategory" name="category">
              <option value="mission" selected>{{ __('Quest XP (+1 Quest Count)') }}</option>
              <option value="report">{{ __('Report / RP Writing XP') }}</option>
              <option value="rank">{{ __('Rank Promotion Bonus') }}</option>
              <option value="extra">{{ __('Extra (EX) XP') }}</option>
              <option value="bailiff">{{ __('Bailiff Bonus') }}</option>
            </select>
          </div>

          <div>
            <label class="form-label small" for="quickXpNote">{{ __('Note / Session Title') }}</label>
            <input type="text" class="form-control" id="quickXpNote" name="note" placeholder="{{ __('e.g. Session #14 Dragon Den') }}">
          </div>
        </div>

        <div class="modal-footer border-top pt-3">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-success rounded-pill" id="btnSubmitQuickXp">{{ __('Award XP') }}</button>
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
  window.adventurersData = @json($adventurersData);
  window.isAdmin = {{ $isAdmin ? 'true' : 'false' }};
  let activeTabFilter = 'roster';

  const i18n = {
    quickXpSuccessTitle: @json(__('XP Awarded!')),
    updatedTitle: @json(__('Updated!')),
    successTitle: @json(__('Success!')),
    errorTitle: @json(__('Error!')),
    networkError: @json(__('A network error occurred.')),
    confirm: @json(__('Yes, Confirm')),
    cancel: @json(__('Cancel')),
    statusUpdateTitle: @json(__('Update Status')),
    statusUpdateText: @json(__('Status will be changed for adventurer ":name". Do you confirm?')),
    kiaTitle: @json(__('Declare KIA (Fallen)')),
    kiaText: @json(__('":name" will be marked as fallen in battle and moved to the Hall of Fame.')),
    miaTitle: @json(__('Declare MIA (Missing)')),
    miaText: @json(__('":name" will be marked as missing in action and moved to the Hall of Fame.')),
    reviveTitle: @json(__('Recall to Ranks (Revive)')),
    reviveText: @json(__('":name" will be revived and restored to the active roster.')),
    deleteTitle: @json(__('Delete Adventurer?')),
    deleteText: @json(__('":name" will be permanently deleted. This action cannot be undone!')),
    deleteConfirm: @json(__('Yes, Delete')),
    clearTitle: @json(__('Delete all XP records?')),
    clearText: @json(__('All XP Tracker records will be removed, including records hidden by filters. Characters and quest reports will not be deleted. Records linked to distributed quest XP cannot be deleted.')),
    importTitle: @json(__('Import XP data?')),
    importText: @json(__('Matching XP records will be updated. Other records, characters and quest reports will not be deleted.')),
    importSuccess: @json(__('Data Imported')),
    importFailure: @json(__('Data import failed')),
    serverError: @json(__('The server could not process the request. Check the application logs.')),
    deletedTitle: @json(__('Deleted!')),
    levelPrefix: @json(__('Level ')),
    maximum: @json(__('Maximum')),
    maximumLevel: @json(__('Maximum Level')),
    remainingLabel: @json(__('Remaining: ')),
    toNextLevelLabel: @json(__('To Next Level: ')),
    progressLabel: @json(__('Progress: %')),
    unclaimed: @json(__('Unclaimed')),
    unknown: @json(__('Unknown')),
    playerLabel: @json(__('Player: ')),
    questsLabel: @json(__('Quests')),
    sessionsCountLabel: @json(__('Sessions')),
    questXpShort: @json(__('Quest: ')),
    reportXpShort: @json(__('Report: ')),
    extraXpShort: @json(__('EX: ')),
    profileBtn: @json(__('Profile')),
    awardXpBtn: @json(__('Award XP')),
    quickXpBtn: @json(__('Add Quick XP')),
    editBtn: @json(__('Edit')),
    allRanks: @json(__('All Ranks')),
    ranksSelected: @json(__(':count selected', ['count' => '__COUNT__'])),
    allStatuses: @json(__('All Statuses')),
    statusesSelected: @json(__(':count statuses', ['count' => '__COUNT__'])),
    noRecords: @json(__('No records found.')),
    historyNotes: @json(__('Adventurer History & Notes:')),
    xpDistribution: @json(__('XP Component Distribution')),
    questXp: @json(__('Quest XP')),
    reportXp: @json(__('Report XP')),
    rankXp: @json(__('Rank XP')),
    extraXp: @json(__('Extra (EX)')),
    bailiffXp: @json(__('Bailiff')),
    questCount: @json(__('Quest Count')),
    ranks: {
      'Aday': @json(__('Candidate')),
      'Üye': @json(__('Member')),
      'Kıdemli': @json(__('Veteran')),
      'Uzman': @json(__('Expert')),
      'Clerk': @json(__('Clerk')),
      'Gezgin': @json(__('Wanderer')),
      'Inaktif': @json(__('Inactive'))
    }
  };

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
    renderCardsView();
  });

  // Top KPI Card Clicks to Filter
  document.querySelectorAll('.filter-card').forEach(card => {
    card.addEventListener('click', function() {
      const filter = this.dataset.filter;
      if (filter === 'roster') {
        document.getElementById('tab-guild-btn').click();
      } else if (filter === 'fallen') {
        document.getElementById('tab-fallen-btn').click();
      }
    });
  });

  // Tab Filtering Buttons
  document.querySelectorAll('.tab-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.tab-filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      activeTabFilter = this.dataset.filter;
      updateStatusFilterVisibility();
      applyFilters();
    });
  });

  // Filtering Logic
  const searchInput = document.getElementById('searchTable');
  const rankFilterOptions = [...document.querySelectorAll('.rank-filter-option')];
  const statusFilterOptions = [...document.querySelectorAll('.status-filter-option')];
  const rankFilterLabel = document.getElementById('rankFilterLabel');
  const statusFilterLabel = document.getElementById('statusFilterLabel');
  const statusFilterDropdown = document.getElementById('btnStatusFilter').closest('.dropdown');
  const filterLevel = document.getElementById('filterLevel');
  const btnClear = document.getElementById('btnClearFilters');
  const xpTrackerCard = document.getElementById('xpTrackerCard');
  const chkFallenRed = document.getElementById('chkFallenRed');
  const fallenRedSetting = 'xpTrackerFallenRed';

  function updateStatusFilterVisibility() {
    statusFilterDropdown.classList.toggle('d-none', activeTabFilter !== 'fallen');
  }

  function getSelectedRanks() {
    return rankFilterOptions.filter(option => option.checked).map(option => option.value);
  }

  function getSelectedStatuses() {
    return statusFilterOptions.filter(option => option.checked).map(option => option.value);
  }

  function updateFilterLabels() {
    const selectedRanks = getSelectedRanks();
    const selectedStatuses = getSelectedStatuses();
    rankFilterLabel.textContent = selectedRanks.length
      ? i18n.ranksSelected.replace('__COUNT__', selectedRanks.length)
      : i18n.allRanks;
    statusFilterLabel.textContent = selectedStatuses.length === statusFilterOptions.length
      ? i18n.allStatuses
      : i18n.statusesSelected.replace('__COUNT__', selectedStatuses.length);
  }

  if (localStorage.getItem(fallenRedSetting) === 'true') {
    chkFallenRed.checked = true;
    xpTrackerCard.classList.add('xp-fallen-highlight');
  }
  chkFallenRed.addEventListener('change', function() {
    localStorage.setItem(fallenRedSetting, this.checked ? 'true' : 'false');
    xpTrackerCard.classList.toggle('xp-fallen-highlight', this.checked);
  });

  function applyFilters() {
    const term = searchInput.value.toLowerCase().trim();
    const selectedRanks = getSelectedRanks();
    const selectedStatuses = getSelectedStatuses();
    const levelVal = filterLevel.value;
    updateFilterLabels();

    // Filter Table Rows
    document.querySelectorAll('.adventurer-row').forEach(row => {
      const category = row.dataset.category;
      const name = row.dataset.name || '';
      const player = row.dataset.player || '';
      const rank = row.dataset.rank || '';
      const level = row.dataset.level || '';
      const status = row.dataset.status || '';
      const matchCategory = activeTabFilter === 'fallen'
        ? category === 'fallen' && selectedStatuses.includes(status)
        : category !== 'fallen';

      const matchSearch = !term || name.includes(term) || player.includes(term);
      const matchRank = selectedRanks.length === 0 || selectedRanks.includes(rank);
      const matchLevel = !levelVal || level === levelVal;

      if (matchCategory && matchSearch && matchRank && matchLevel) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });

    if (!viewCards.classList.contains('d-none')) {
      renderCardsView();
    }
  }

  // Dynamic Cards View Renderer
  function renderCardsView() {
    const container = document.getElementById('cardsGridContainer');
    if (!container) return;

    const term = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedRanks = getSelectedRanks();
    const selectedStatuses = getSelectedStatuses();
    const levelVal = filterLevel ? filterLevel.value : '';

    let html = '';
    let count = 0;

    for (const [id, adv] of Object.entries(window.adventurersData)) {
      const category = ['kia', 'mia', 'retired'].includes(adv.status) ? 'fallen' : adv.category;
      const matchCategory = activeTabFilter === 'fallen'
        ? category === 'fallen' && selectedStatuses.includes(adv.status)
        : category !== 'fallen';

      const name = (adv.character_name || '').toLowerCase();
      const player = (adv.player_name || '').toLowerCase();
      const matchSearch = !term || name.includes(term) || player.includes(term);
      const matchRank = selectedRanks.length === 0 || selectedRanks.includes(adv.rank);
      const effLevel = calculateLevel(adv.total_xp);
      const matchLevel = !levelVal || String(effLevel) === levelVal;

      if (matchCategory && matchSearch && matchRank && matchLevel) {
        count++;
        const isFallen = ['kia', 'mia', 'retired'].includes(adv.status);
        const isKia = adv.status === 'kia' || (adv.reason && adv.reason.toUpperCase() === 'KIA');
        const isMia = adv.status === 'mia' || (adv.reason && adv.reason.toUpperCase() === 'MIA');

        let rankColor = 'bg-label-secondary';
        let rawRank = adv.rank || 'Aday';
        let rankText = i18n.ranks[rawRank] || rawRank;
        if (isKia) { rankColor = 'bg-label-danger'; rankText = 'KIA'; }
        else if (isMia) { rankColor = 'bg-label-warning'; rankText = 'MIA'; }
        else if (adv.status === 'retired') { rankColor = 'bg-label-danger'; rankText = 'Retired'; }
        else {
          rankColor = {
            'Clerk': 'bg-label-info',
            'Aday': 'bg-label-secondary',
            'Üye': 'bg-label-primary',
            'Uzman': 'bg-label-success',
            'Kıdemli': 'bg-label-warning',
            'Gezgin': 'bg-label-info',
            'Inaktif': 'bg-label-dark',
          }[adv.rank] || 'bg-label-secondary';
        }

        const levelBadgeColor = effLevel >= 10 ? 'bg-label-danger' : (effLevel >= 6 ? 'bg-label-warning' : (effLevel >= 3 ? 'bg-label-primary' : 'bg-label-secondary'));
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

        html += `
          <div class="col-12 col-sm-6 col-lg-4 col-xl-3 adventurer-card-col ${isFallen ? 'fallen-row' : ''}" data-id="${adv.id}">
            <div class="card h-100 border shadow-xs adventurer-card">
              <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-3">
                      <div class="avatar avatar-md cursor-pointer btn-open-profile" data-id="${adv.id}">
                        <span class="avatar-initial rounded-circle ${isFallen ? 'bg-label-danger' : 'bg-label-primary'} fw-bold fs-5 text-uppercase">
                          ${adv.character_name.substring(0, 2)}
                        </span>
                      </div>
                      <div>
                        <h6 class="mb-0 text-heading fw-bold cursor-pointer btn-open-profile" data-id="${adv.id}">
                          ${adv.character_name}
                        </h6>
                        <small class="text-body-secondary">${adv.player_name || i18n.unclaimed}</small>
                      </div>
                    </div>
                    <span class="badge ${rankColor} rounded-pill font-monospace" style="font-size: 0.72rem;">
                      ${rankText}
                    </span>
                  </div>

                  <div class="bg-body border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge ${levelBadgeColor} font-monospace fw-bold">Lv ${effLevel}</span>
                      <span class="fw-bold font-monospace text-heading" style="font-size: 0.85rem;">${progressLabel}</span>
                    </div>
                    <div class="progress rounded-pill shadow-xs" style="height: 7px;">
                      <div class="progress-bar bg-primary progress-bar-animated-smooth" role="progressbar" style="width: ${progressPercent}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                      <small class="text-body-secondary font-monospace" style="font-size: 0.72rem;">%${progressPercent}</small>
                      <small class="text-body-secondary font-monospace" style="font-size: 0.72rem;">
                        ${effLevel >= 20 ? i18n.maximum : `${i18n.remainingLabel}<strong>${remainingXp.toLocaleString()} XP</strong>`}
                      </small>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-1 mb-3">
                    ${adv.mission_xp > 0 ? `<span class="badge bg-label-success rounded-pill font-monospace" style="font-size: 0.7rem;">${i18n.questXpShort}${adv.mission_xp.toLocaleString()}</span>` : ''}
                    ${adv.report_xp > 0 ? `<span class="badge bg-label-info rounded-pill font-monospace" style="font-size: 0.7rem;">${i18n.reportXpShort}${adv.report_xp.toLocaleString()}</span>` : ''}
                    ${adv.extra_xp > 0 ? `<span class="badge bg-label-warning rounded-pill font-monospace" style="font-size: 0.7rem;">${i18n.extraXpShort}${adv.extra_xp.toLocaleString()}</span>` : ''}
                    ${adv.sessions_count > 0 ? `<span class="badge bg-body border font-monospace text-heading" style="font-size: 0.7rem;">${adv.sessions_count} ${i18n.questsLabel}</span>` : ''}
                  </div>
                </div>

                <div class="pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                  <button type="button" class="btn btn-xs btn-label-secondary rounded-pill flex-grow-1 btn-open-profile" data-id="${adv.id}">
                    <i class="icon-base ti tabler-id me-1"></i> ${i18n.profileBtn}
                  </button>
                  ${window.isAdmin ? `
                    <button type="button" class="btn btn-xs btn-primary rounded-pill flex-grow-1 btn-quick-xp" 
                            data-id="${adv.id}" 
                            data-name="${adv.character_name}" 
                            data-xp="${adv.total_xp}">
                      <i class="icon-base ti tabler-plus me-1"></i> ${i18n.awardXpBtn}
                    </button>
                    <button type="button" class="btn btn-xs btn-icon btn-label-primary rounded-circle btn-edit-adv" data-id="${adv.id}" title="${i18n.editBtn}">
                      <i class="icon-base ti tabler-pencil fs-6"></i>
                    </button>
                  ` : ''}
                </div>
              </div>
            </div>
          </div>
        `;
      }
    }

    if (count === 0) {
      html = `<div class="col-12 text-center py-5 text-body-secondary"><i class="icon-base ti tabler-info-circle fs-2 d-block mb-2"></i>${i18n.noRecords}</div>`;
    }

    container.innerHTML = html;
  }

  if (searchInput) searchInput.addEventListener('input', applyFilters);
  rankFilterOptions.forEach(option => option.addEventListener('change', applyFilters));
  statusFilterOptions.forEach(option => option.addEventListener('change', applyFilters));
  if (filterLevel) filterLevel.addEventListener('change', applyFilters);

  if (btnClear) {
    btnClear.addEventListener('click', function() {
      searchInput.value = '';
      rankFilterOptions.forEach(option => option.checked = false);
      statusFilterOptions.forEach(option => option.checked = true);
      filterLevel.value = '';
      document.getElementById('tab-guild-btn').click();
    });
  }
  document.getElementById('btnClearRanks').addEventListener('click', function() {
    rankFilterOptions.forEach(option => option.checked = false);
    applyFilters();
  });

  // Initialize initial filter (Guild active members tab)
  updateStatusFilterVisibility();
  applyFilters();

  // Open Adventurer Profile & Edit Drawers via Event Delegation
  document.addEventListener('click', function(e) {
    const profileBtn = e.target.closest('.btn-open-profile');
    if (profileBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = profileBtn.dataset.id || profileBtn.closest('[data-id]')?.dataset.id;
      const adv = window.adventurersData[id];
      if (adv) openProfileDrawer(adv);
      return;
    }

    const editBtn = e.target.closest('.btn-edit-adv');
    if (editBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = editBtn.dataset.id || editBtn.closest('[data-id]')?.dataset.id;
      const adv = window.adventurersData[id];
      if (adv) openEditDrawer(adv);
      return;
    }
  });

  function openProfileDrawer(adv) {
    const isFallen = adv.status === 'kia' || adv.status === 'mia';
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

    const displayRank = i18n.ranks[adv.rank] || adv.rank;

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
            <i class="icon-base ti tabler-user fs-6"></i> ${i18n.playerLabel} <strong>${adv.player_name || i18n.unknown}</strong>
          </span>
          <div class="mt-1">
            <span class="badge ${isFallen ? 'bg-label-danger' : 'bg-label-primary'} rounded-pill font-monospace">${displayRank}</span>
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
          <span>${i18n.progressLabel}${progressPercent}</span>
          <span>${effLevel >= 20 ? i18n.maximumLevel : `${i18n.toNextLevelLabel}${remainingXp.toLocaleString()} XP`}</span>
        </div>
      </div>

      <h6 class="fw-bold text-heading mb-3 d-flex align-items-center gap-1">
        <i class="icon-base ti tabler-chart-pie text-primary fs-5"></i> ${i18n.xpDistribution}
      </h6>
      <div class="row g-2 mb-4 font-monospace">
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">${i18n.questXp}</small>
            <strong class="text-success">${(adv.mission_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">${i18n.reportXp}</small>
            <strong class="text-info">${(adv.report_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">${i18n.rankXp}</small>
            <strong class="text-primary">${(adv.rank_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">${i18n.extraXp}</small>
            <strong class="text-warning">${(adv.extra_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">${i18n.bailiffXp}</small>
            <strong class="text-danger">${(adv.bailiff_xp || 0).toLocaleString()}</strong>
          </div>
        </div>
        <div class="col-6">
          <div class="border rounded p-2 text-center bg-body">
            <small class="text-body-secondary d-block">${i18n.questCount}</small>
            <strong class="text-heading">${adv.sessions_count || 1} ${i18n.sessionsCountLabel}</strong>
          </div>
        </div>
      </div>

      ${adv.notes ? `
        <div class="mb-4">
          <label class="form-label small fw-semibold text-heading">${i18n.historyNotes}</label>
          <div class="p-3 bg-body border rounded font-monospace small" style="white-space: pre-wrap;">${adv.notes}</div>
        </div>
      ` : ''}

      <div class="border-top pt-3 d-flex gap-2">
        <button type="button" class="btn btn-label-success rounded-pill flex-grow-1 btn-profile-quick-xp" data-id="${adv.id}" data-name="${adv.character_name}" data-xp="${adv.total_xp}">
          <i class="icon-base ti tabler-plus me-1"></i> ${i18n.quickXpBtn}
        </button>
        <button type="button" class="btn btn-label-primary rounded-pill flex-grow-1 btn-profile-edit" data-id="${adv.id}">
          <i class="icon-base ti tabler-pencil me-1"></i> ${i18n.editBtn}
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
      openEditDrawer(window.adventurersData[this.dataset.id]);
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
    document.getElementById('addComputedLevel').textContent = i18n.levelPrefix + lvl;
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
    document.getElementById('editComputedLevel').textContent = i18n.levelPrefix + lvl;
  }

  document.querySelectorAll('.xp-input-edit').forEach(input => {
    input.addEventListener('input', updateEditPreview);
  });

  // Form Submit: Add Adventurer (Offcanvas)
  const formAdd = document.getElementById('formAddAdventurer');
  const addPlayerName = document.getElementById('addPlayerName');
  const addNewPlayerName = document.getElementById('addNewPlayerName');
  const addExistingPlayerName = document.getElementById('addExistingPlayerName');
  const addNewPlayerField = document.getElementById('addNewPlayerField');
  const addExistingPlayerField = document.getElementById('addExistingPlayerField');
  const addPlayerModeOptions = document.querySelectorAll('input[name="addPlayerMode"]');
  const updateAddPlayerMode = function() {
    if (!addPlayerName || !addNewPlayerName || !addExistingPlayerName || !addNewPlayerField || !addExistingPlayerField) {
      return;
    }

    const isExistingPlayer = document.getElementById('addPlayerModeExisting').checked;
    addNewPlayerField.classList.toggle('d-none', isExistingPlayer);
    addExistingPlayerField.classList.toggle('d-none', !isExistingPlayer);
    addNewPlayerName.required = !isExistingPlayer;
    addExistingPlayerName.required = isExistingPlayer;
    addExistingPlayerName.disabled = !isExistingPlayer;
    addPlayerName.value = isExistingPlayer ? addExistingPlayerName.value : addNewPlayerName.value;
  };
  addPlayerModeOptions.forEach(option => option.addEventListener('change', updateAddPlayerMode));
  if (addNewPlayerName) {
    addNewPlayerName.addEventListener('input', updateAddPlayerMode);
  }
  if (addExistingPlayerName) {
    addExistingPlayerName.addEventListener('change', updateAddPlayerMode);
  }
  updateAddPlayerMode();

  if (formAdd) {
    formAdd.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitAddAdventurer');
      btn.disabled = true;

      updateAddPlayerMode();
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
            title: i18n.successTitle,
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: i18n.errorTitle, text: data.message || 'Error' });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: i18n.errorTitle, text: i18n.networkError });
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
            title: i18n.updatedTitle,
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: i18n.errorTitle, text: data.message || 'Error' });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: i18n.errorTitle, text: i18n.networkError });
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
            title: i18n.quickXpSuccessTitle,
            text: data.message,
            timer: 1800,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: i18n.errorTitle, text: data.message || 'Error' });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: i18n.errorTitle, text: i18n.networkError });
      });
    });
  }

  // Status Change (KIA, MIA, Active)
  document.querySelectorAll('.btn-status-change').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const status = this.dataset.status;
      const name = this.dataset.name;

      let promptTitle = i18n.statusUpdateTitle;
      let promptText = i18n.statusUpdateText.replace(':name', name);

      if (status === 'kia') {
        promptTitle = i18n.kiaTitle;
        promptText = i18n.kiaText.replace(':name', name);
      } else if (status === 'mia') {
        promptTitle = i18n.miaTitle;
        promptText = i18n.miaText.replace(':name', name);
      } else if (status === 'active') {
        promptTitle = i18n.reviveTitle;
        promptText = i18n.reviveText.replace(':name', name);
      }

      Swal.fire({
        title: promptTitle,
        text: promptText,
        icon: status === 'active' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: i18n.confirm,
        cancelButtonText: i18n.cancel,
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
                title: i18n.updatedTitle,
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: i18n.errorTitle, text: data.message });
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
        title: i18n.deleteTitle,
        text: i18n.deleteText.replace(':name', name),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: i18n.deleteConfirm,
        cancelButtonText: i18n.cancel,
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
                title: i18n.deletedTitle,
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: i18n.errorTitle, text: data.message });
            }
          });
        }
      });
    });
  });

  async function readXpResponse(response) {
    if (!(response.headers.get('content-type') || '').includes('application/json')) {
      throw new Error(i18n.serverError);
    }
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(response.status >= 500 ? i18n.serverError : (data.message || i18n.serverError));
    }
    return data;
  }

  const btnImportXp = document.getElementById('btnImportXp');
  const xpTrackerJsonFile = document.getElementById('xpTrackerJsonFile');
  if (btnImportXp && xpTrackerJsonFile) {
    btnImportXp.addEventListener('click', async function() {
      const result = await Swal.fire({
        title: i18n.importTitle,
        text: i18n.importText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: i18n.confirm,
        cancelButtonText: i18n.cancel
      });
      if (result.isConfirmed) xpTrackerJsonFile.click();
    });

    xpTrackerJsonFile.addEventListener('change', async function() {
      const file = this.files && this.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append('file', file);
      btnImportXp.disabled = true;
      try {
        const response = await fetch("{{ route('valiant-shade.pages-xp-tracker.import') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          body: formData
        });
        const data = await readXpResponse(response);
        await Swal.fire({ icon: data.unmatched.length ? 'warning' : 'success', title: i18n.importSuccess, text: data.message });
        location.reload();
      } catch (error) {
        await Swal.fire({ icon: 'error', title: i18n.importFailure, text: error.message || i18n.networkError });
      } finally {
        btnImportXp.disabled = false;
        xpTrackerJsonFile.value = '';
      }
    });
  }

  const btnClearXp = document.getElementById('btnClearXp');
  if (btnClearXp) {
    btnClearXp.addEventListener('click', function() {
      Swal.fire({
        title: i18n.clearTitle,
        text: i18n.clearText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: i18n.deleteConfirm,
        cancelButtonText: i18n.cancel,
        customClass: {
          confirmButton: 'btn btn-danger rounded-pill me-2',
          cancelButton: 'btn btn-label-secondary rounded-pill'
        },
        buttonsStyling: false
      }).then(result => {
        if (!result.isConfirmed) return;

        fetch("{{ route('valiant-shade.pages-xp-tracker.clear') }}", {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          }
        })
        .then(readXpResponse)
        .then(data => {
          Swal.fire({ icon: 'success', title: i18n.deletedTitle, text: data.message })
            .then(() => location.reload());
        })
        .catch(error => Swal.fire({ icon: 'error', title: i18n.errorTitle, text: error.message || i18n.networkError }));
      });
    });
  }

});
</script>
@endsection
