@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'User Profile - Profile')

<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/valiant-shade/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/valiant-shade/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'], 'static/valiant-shade/build')
@endsection

<!-- Page Styles -->
@section('page-style')
@vite(['resources/valiant-shade/assets/vendor/scss/pages/page-profile.scss'], 'static/valiant-shade/build')
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/valiant-shade/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'], 'static/valiant-shade/build')
@endsection

<!-- Page Scripts -->
@section('page-script')
@vite(['resources/valiant-shade/assets/js/app-user-view-account.js'], 'static/valiant-shade/build')
@endsection

@section('content')
<!-- Header -->
<div class="row">
  <div class="col-12">
    <div class="card mb-6">
      <div class="user-profile-header-banner">
        <img src="{{ asset('static/demo-vuexy/assets/img/pages/profile-banner.png') }}" alt="Banner image" class="rounded-top" />
      </div>
      <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-5">
        <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
          <img src="{{ Auth::user()->profile_photo_url ?? asset('static/valiant-shade/assets/img/avatars/user.png') }}" alt="user image"
            class="d-block h-auto ms-0 ms-sm-6 rounded user-profile-img" />
        </div>
        <div class="flex-grow-1 mt-3 mt-lg-5">
          <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
            <div class="user-profile-info">
              <h4 class="mb-1 mt-lg-6">{{ Auth::user()->name ?? 'Valiant Shade' }}</h4>
              @if (Auth::check())
              <div class="text-body-secondary small mb-2 d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-label-primary font-monospace fs-7 d-inline-flex align-items-center">UUID: {{ Auth::user()->uuid ?? ('VS-' . str_pad(Auth::user()->id, 8, '0', STR_PAD_LEFT)) }}</span>
                <span class="badge bg-label-primary font-monospace fs-7 d-inline-flex align-items-center">ID: #{{ Auth::user()->id }}</span>
              </div>
              @endif
              <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                @php
                  $userRole = Auth::user()->role ?? 'User';
                  $roleIcon = match($userRole) {
                    'Admin' => 'ti tabler-crown text-danger',
                    'Bailiff' => 'ti tabler-shield text-warning',
                    default => 'ti tabler-user text-primary',
                  };
                @endphp
                <li class="list-inline-item d-flex gap-2 align-items-center"><i class="icon-base {{ $roleIcon }} icon-lg"></i><span class="fw-medium">{{ $userRole }}</span></li>
                <li class="list-inline-item d-flex gap-2 align-items-center"><i class="icon-base ti tabler-map-pin  icon-lg"></i><span class="fw-medium">{{ Auth::user()->country ?? 'Vatican City' }}</span></li>
                <li class="list-inline-item d-flex gap-2 align-items-center"><i class="icon-base ti tabler-calendar  icon-lg"></i><span class="fw-medium"> Joined {{ Auth::user() && Auth::user()->created_at ? Auth::user()->created_at->translatedFormat('F Y') : 'April 2021' }}</span></li>
              </ul>
            </div>
            <a href="javascript:void(0)" class="btn btn-primary mb-1"> <i class="icon-base ti tabler-user-check icon-xs me-2"></i>Connected </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!--/ Header -->

<!-- Navbar pills -->
<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-sm-row mb-6 gap-sm-0 gap-2">
        <li class="nav-item">
          <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-user-check icon-sm me-1_5"></i> Profile</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/valiant-shade/pages/profile-teams') }}"><i class="icon-base ti tabler-users icon-sm me-1_5"></i> Teams</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/valiant-shade/pages/profile-projects') }}"><i class="icon-base ti tabler-layout-grid icon-sm me-1_5"></i> Projects</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/valiant-shade/pages/profile-connections') }}"><i class="icon-base ti tabler-link icon-sm me-1_5"></i> Connections</a>
        </li>
        <li class="nav-item">
          @if(count($characters ?? []) < 2)
            <button type="button" class="btn btn-primary waves-effect waves-light d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
              <i class="icon-base ti tabler-user-plus icon-sm me-1_5"></i> {{ __('Create New Character') }}
            </button>
          @else
            <button type="button" class="btn btn-secondary waves-effect d-flex align-items-center disabled" disabled title="{{ __('Maximum 2 characters reached (1 Main and 1 Clerk)') }}">
              <i class="icon-base ti tabler-lock icon-sm me-1_5"></i> {{ __('Character Limit Reached (2/2)') }}
            </button>
          @endif
        </li>
      </ul>
    </div>
  </div>
</div>
<!--/ Navbar pills -->

<!-- User Profile Content -->
<div class="row">
  <div class="col-xl-4 col-lg-5 col-md-5">
    <!-- Player Characters Card (Replaced About) -->
    <div class="card mb-6" id="playerCharactersCard">
      <div class="card-header d-flex align-items-center justify-content-between pb-3 border-bottom">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-sm bg-label-primary rounded me-2 d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-swords text-primary"></i>
          </div>
          <div>
            <h5 class="card-title mb-0 fw-bold">{{ __('My Characters') }}</h5>
            <small class="text-body-secondary">{{ Auth::user()->name ?? 'Player' }} &bull; {{ __('Active Heroes') }}</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-label-primary rounded-pill" id="charListCountBadge">{{ count($characters ?? []) }}/2 Characters</span>
          @if(count($characters ?? []) < 2)
            <button type="button" class="btn btn-sm btn-primary waves-effect waves-light d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
              <i class="icon-base ti tabler-plus icon-xs me-1"></i> {{ __('New') }}
            </button>
          @else
            <button type="button" class="btn btn-sm btn-secondary waves-effect disabled" disabled title="{{ __('Maximum 2 characters reached (1 Main and 1 Clerk)') }}">
              <i class="icon-base ti tabler-lock icon-xs me-1"></i> {{ __('Full (2/2)') }}
            </button>
          @endif
        </div>
      </div>
      <div class="card-body pt-4">
        <!-- Container for character cards -->
        <div id="characterCardsContainer" class="d-flex flex-column gap-3">
          @if(isset($characters) && $characters->count() > 0)
            @foreach($characters as $c)
              @php
                $isMain = ($c->rank === 'Main' || $c->character_type === 'Main');
                $isActive = (Auth::user()?->current_character === $c->character_name);
                $hd = $c->hit_die ?? 'd10';
                $curHp = $c->hp ?? 10;
                $mHp = $c->max_hp ?? $curHp;
                $gold = $c->sheet_data['money']['gp'] ?? 150;
              @endphp
              <div class="card border {{ $isActive ? 'border-primary border-opacity-50 is-selected' : 'border-secondary border-opacity-25' }} bg-label-secondary bg-opacity-10 shadow-none p-3 character-item-card" data-id="{{ $c->character_id ?: $c->id }}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md {{ $isActive ? 'bg-primary text-white' : 'bg-label-primary text-primary' }} rounded-3 d-flex align-items-center justify-content-center fs-4 shadow-sm overflow-hidden" style="width: 44px; height: 44px;">
                      <i class="icon-base ti {{ strtolower($c->class_name) === 'paladin' ? 'tabler-shield' : 'tabler-sword' }}"></i>
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                        <h6 class="mb-0 fw-bold text-heading me-1">{{ $c->character_name }}</h6>
                        <span class="badge {{ $isMain ? 'bg-primary' : 'bg-info' }} text-white px-2 py-0_5" style="font-size: 0.7rem;">
                          <i class="icon-base ti {{ $isMain ? 'tabler-crown' : 'tabler-feather' }} me-1"></i>{{ $c->rank ?: 'Main' }}
                        </span>
                        @if($isActive)
                          <span class="badge bg-label-success px-2 py-0_5" style="font-size: 0.7rem;">{{ __('Active') }}</span>
                          <span class="badge bg-label-primary px-2 py-0_5" style="font-size: 0.7rem;"><i class="icon-base ti tabler-eye icon-xs me-1"></i>{{ __('Selected') }}</span>
                        @endif
                      </div>
                      <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                        <span class="badge bg-label-primary px-2 py-0_5" style="font-size: 0.7rem;" title="Permanent Race"><i class="icon-base ti tabler-lock icon-xs me-1 text-muted"></i>{{ $c->race }}</span>
                        <span class="badge bg-label-info px-2 py-0_5" style="font-size: 0.7rem;">{{ $c->class_name }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="d-flex flex-column align-items-end gap-1">
                    <span class="badge {{ $isActive ? 'bg-primary text-white' : 'bg-label-secondary' }} rounded-pill fw-bold">Lvl {{ $c->level ?? 1 }}</span>
                    <span class="badge bg-label-secondary font-monospace" style="font-size: 0.68rem;" title="Hit Die">HD: {{ $hd }}</span>
                  </div>
                </div>

                <!-- HP Bar -->
                <div class="row g-2 mt-1 pt-2 border-top border-secondary border-opacity-10">
                  <div class="col-12">
                    <div class="d-flex justify-content-between mb-1 text-body-secondary" style="font-size: 0.72rem;">
                      <span><i class="icon-base ti tabler-heart text-danger me-1"></i>HP</span>
                      <span class="fw-semibold text-heading">{{ $curHp }}/{{ $mHp }} HP</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                      <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min(100, round(($curHp / max(1, $mHp)) * 100)) }}%"></div>
                    </div>
                  </div>
                </div>

                <!-- Purse / Balance -->
                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top border-secondary border-opacity-10">
                  <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-label-warning px-2 py-1 font-monospace" style="font-size: 0.75rem;">
                      <i class="icon-base ti tabler-coin me-1"></i>{{ $gold }} GP ({{ $gold * 10 }} SP)
                    </span>
                  </div>
                  <div class="d-flex align-items-center gap-1">
                    <a href="{{ url('/valiant-shade/character/sheet') }}?id={{ urlencode($c->character_id ?: $c->id) }}" class="btn btn-xs btn-outline-warning" title="Edit Character Sheet">
                      <i class="icon-base ti tabler-edit me-1"></i>{{ __('Edit Sheet') }}
                    </a>
                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete-char" data-id="{{ $c->character_id ?: $c->id }}" title="Delete Character">
                      <i class="icon-base ti tabler-trash me-1"></i>{{ __('Delete') }}
                    </button>
                  </div>
                </div>
              </div>
            @endforeach
          @endif
        </div>

        <!-- Empty State -->
        <div id="characterEmptyState" class="text-center py-5 {{ (isset($characters) && $characters->count() > 0) ? 'd-none' : '' }}">
          <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-ghost fs-1 text-muted"></i>
          </div>
          <h6 class="fw-bold mb-1">No Character Created Yet</h6>
          <p class="text-body-secondary small mb-3">Create your first hero to join the world of Valiant Shade.</p>
          <button type="button" class="btn btn-sm btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
            <i class="icon-base ti tabler-user-plus me-1"></i> Create New Character
          </button>
        </div>
      </div>
    </div>
    <!--/ Player Characters Card -->
    <!-- Profile Overview -->
    <div class="card mb-6">
      <div class="card-body">
        <p class="card-text text-uppercase text-body-secondary small">Overview</p>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-center mb-4"><i class="icon-base ti tabler-check icon-lg"></i><span class="fw-medium mx-2">Task Compiled:</span> <span>13.5k</span></li>
          <li class="d-flex align-items-center mb-4"><i class="icon-base ti tabler-layout-grid icon-lg"></i><span class="fw-medium mx-2">Projects Compiled:</span> <span>146</span></li>
          <li class="d-flex align-items-center"><i class="icon-base ti tabler-users icon-lg"></i><span class="fw-medium mx-2">Connections:</span> <span>897</span></li>
        </ul>
      </div>
    </div>
    <!--/ Profile Overview -->
  </div>
  <div class="col-xl-8 col-lg-7 col-md-7">
    <!-- Activity Timeline & Class Progression Card -->
    <div class="card card-action mb-6" id="timelineAndProgressionCard">
      <div class="card-header align-items-center justify-content-between pb-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item">
            <button type="button" class="nav-link active d-flex align-items-center gap-2 py-3 px-4 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-timeline" aria-controls="navs-timeline" aria-selected="true" id="tab-btn-timeline">
              <i class="icon-base ti tabler-chart-bar-popular icon-lg"></i>
              <span>Activity Timeline</span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link d-flex align-items-center gap-2 py-3 px-4 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-progression" aria-controls="navs-progression" aria-selected="false" id="tab-btn-progression">
              <i class="icon-base ti tabler-table icon-lg text-primary"></i>
              <span id="tabProgressionTitle">Class</span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link d-flex align-items-center gap-2 py-3 px-4 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-race" aria-controls="navs-race" aria-selected="false" id="tab-btn-race">
              <i class="icon-base ti tabler-dna icon-lg text-success"></i>
              <span id="tabRaceTitle">Irk</span>
            </button>
          </li>
        </ul>
        <div class="card-action-element d-flex align-items-center gap-2 pb-2">
          <div class="dropdown">
            <button type="button" class="btn dropdown-toggle hide-arrow p-0 text-body-secondary" data-bs-toggle="dropdown" aria-expanded="false"><i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="javascript:void(0);">Share timeline</a></li>
              <li><a class="dropdown-item" href="javascript:void(0);">Suggest edits</a></li>
              <li>
                <hr class="dropdown-divider" />
              </li>
              <li><a class="dropdown-item" href="javascript:void(0);">Report bug</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="tab-content p-0">
          <!-- Pane 1: Activity Timeline (Character Financial & Events Ledger) -->
          <div class="tab-pane fade show active" id="navs-timeline" role="tabpanel">
            <!-- Top Ledger Summary & Action Bar -->
            <div class="ledger-hero-banner p-3 p-md-4 mb-4 rounded-3 shadow-lg">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2 gap-sm-3 flex-wrap">
                  <!-- Character Purse Stat Chip -->
                  <div class="ledger-stat-chip d-flex align-items-center gap-2">
                    <div class="stat-chip-icon gold-glow">
                      <i class="icon-base ti tabler-coins text-warning"></i>
                    </div>
                    <div>
                      <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Character Purse') }}</div>
                      <div class="d-flex align-items-baseline gap-1" id="timelineSelectedCharPurse">
                        <span class="fw-bolder fs-5 text-warning font-monospace" id="timelineCharGoldVal">150</span>
                        <span class="badge bg-warning text-dark px-1_5 py-0 fw-bold" style="font-size: 0.65rem;">GP</span>
                        <span class="text-muted small font-monospace" id="timelineCharSpVal">(1.500 SP)</span>
                      </div>
                    </div>
                  </div>

                  <!-- Vault Reserve Stat Chip -->
                  <div class="ledger-stat-chip d-flex align-items-center gap-2">
                    <div class="stat-chip-icon vault-glow">
                      <i class="icon-base ti tabler-building-bank text-info"></i>
                    </div>
                    <div>
                      <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Guild Vault Reserve') }}</div>
                      <div class="d-flex align-items-baseline gap-1">
                        <span class="fw-bold fs-6 text-heading font-monospace" id="timelineVaultReserveBadge">{{ number_format((float) ($vault->balance_sp ?? 358989.50), 0, ',', '.') }}</span>
                        <span class="badge bg-label-info px-1_5 py-0 fw-semibold" style="font-size: 0.65rem;">SP</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Transfer Action Button -->
                <div class="d-flex align-items-center">
                  <button type="button" class="btn btn-transfer-funds d-flex align-items-center gap-2 waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#transferMoneyModal" id="btnOpenTransferMoney">
                    <i class="icon-base ti tabler-arrows-exchange fs-4"></i>
                    <span>{{ __('Transfer Money') }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Filter Toolbar & Event Counter -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-2 border-bottom border-secondary border-opacity-10">
              <div class="timeline-filter-bar d-inline-flex align-items-center" id="timelineFilterGroup">
                <button type="button" class="timeline-filter-btn active" data-filter="all">
                  <i class="icon-base ti tabler-list me-1"></i> {{ __('All Activities') }}
                </button>
                <button type="button" class="timeline-filter-btn" data-filter="transfers">
                  <i class="icon-base ti tabler-arrows-exchange me-1"></i> {{ __('Money Transfers') }}
                </button>
                <button type="button" class="timeline-filter-btn" data-filter="vault">
                  <i class="icon-base ti tabler-building-bank me-1"></i> {{ __('Guild Vault') }}
                </button>
                <button type="button" class="timeline-filter-btn" data-filter="milestone">
                  <i class="icon-base ti tabler-sparkles me-1"></i> {{ __('Milestones & Quests') }}
                </button>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-secondary font-monospace px-3 py-1_5 border border-secondary border-opacity-25" id="timelineActivityCountBadge">
                  0 {{ __('Events') }}
                </span>
              </div>
            </div>

            <!-- Dynamic Timeline List -->
            <ul class="timeline mb-0 ps-2" id="characterActivityTimelineList">
              <!-- Dynamically populated via JS -->
            </ul>

            <!-- Empty State -->
            <div id="timelineEmptyState" class="text-center py-5 d-none">
              <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm">
                <i class="icon-base ti tabler-coin-off fs-1 text-muted"></i>
              </div>
              <h5 class="fw-bold mb-1 text-heading">{{ __('No Financial Activity Yet') }}</h5>
              <p class="text-body-secondary small mb-3" style="max-width: 420px; margin: 0 auto;">{{ __('Use the Transfer Money button to transfer gold between heroes or withdraw/deposit with the Guild Vault.') }}</p>
              <button type="button" class="btn btn-sm btn-transfer-funds fw-bold mt-1" data-bs-toggle="modal" data-bs-target="#transferMoneyModal">
                <i class="icon-base ti tabler-arrows-exchange me-1"></i> {{ __('Make First Transfer') }}
              </button>
            </div>
          </div>

          <!-- Pane 2: Character Class Progression Table (Image 2) -->
          <div class="tab-pane fade" id="navs-progression" role="tabpanel">
            <!-- Selected Character Info Card -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-3 mb-3 rounded-3 border border-primary border-opacity-25 bg-label-primary bg-opacity-10 shadow-sm">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" id="progSelectedCharIcon" style="width: 44px; height: 44px;">
                  <i class="icon-base ti tabler-user fs-4"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold text-heading" id="progSelectedCharName">Caspian Thunderblade</h5>
                    <span class="badge bg-label-success" id="progSelectedCharActiveBadge">Active Character</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mt-1 flex-wrap" style="font-size: 0.82rem;">
                    <span class="text-body-secondary" id="progSelectedCharRace">Human</span>
                    <span>&bull;</span>
                    <span class="fw-semibold text-primary" id="progSelectedCharClass">Paladin</span>
                    <span>&bull;</span>
                    <span class="badge bg-primary rounded-pill" id="progSelectedCharLvlBadge">Seviye 1</span>
                  </div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-body border text-body d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
                  <i class="icon-base ti tabler-dice-6 text-primary"></i>
                  <span class="text-muted">Hit Die:</span>
                  <strong class="text-heading" id="chipHitDie">d10</strong>
                </span>
                <span class="badge bg-body border text-body d-flex align-items-center gap-1 py-2 px-3 shadow-xs" id="chipSpellWrapper">
                  <i class="icon-base ti tabler-sparkles text-info"></i>
                  <span class="text-muted">Büyü:</span>
                  <strong class="text-info" id="chipSpellType">Divine</strong>
                </span>
              </div>
            </div>

            <!-- Table Caption & Badges -->
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
              <h5 class="fw-bold mb-0 text-heading d-flex align-items-center gap-2" id="classTableCaption">
                <i class="icon-base ti tabler-shield text-primary"></i> Table: Paladin
              </h5>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-primary font-monospace" style="font-size: 0.75rem;">
                  <span class="d-inline-block rounded-circle bg-primary me-1" style="width: 8px; height: 8px;"></span>
                  Mevcut Seviye Vurgulu
                </span>
              </div>
            </div>

            <!-- Table Responsive Container (Compact Pathfinder Format) -->
            <div class="table-responsive table-progression-wrapper border rounded-3 shadow-sm mb-2">
              <table class="table table-bordered table-hover mb-0 align-middle table-progression" id="classProgressionTable">
                <thead>
                  <tr id="tableMainHeaderRow">
                    <th rowspan="2" class="text-center align-middle th-lvl">Level</th>
                    <th rowspan="2" class="text-center align-middle th-bab">Base Attack Bonus</th>
                    <th rowspan="2" class="text-center align-middle th-save">Fort Save</th>
                    <th rowspan="2" class="text-center align-middle th-save">Ref Save</th>
                    <th rowspan="2" class="text-center align-middle th-save">Will Save</th>
                    <th rowspan="2" class="align-middle th-special">Special</th>
                    <th colspan="4" class="text-center align-middle th-spells-group" id="spellsColGroupHeader">Spells per Day</th>
                  </tr>
                  <tr id="tableSpellsSubHeaderRow">
                    <th class="text-center th-spell-col">1st</th>
                    <th class="text-center th-spell-col">2nd</th>
                    <th class="text-center th-spell-col">3rd</th>
                    <th class="text-center th-spell-col">4th</th>
                  </tr>
                </thead>
                <tbody id="classProgressionTableBody">
                  <!-- Dynamically populated -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Pane 3: Race Details -->
          <div class="tab-pane fade" id="navs-race" role="tabpanel">
            <!-- Selected Character Race Info Bar -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-3 mb-4 rounded-3 border border-success border-opacity-25 bg-label-success bg-opacity-10 shadow-sm" id="raceHeaderCard">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-success text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                  <i class="icon-base ti tabler-dna fs-4"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="mb-0 fw-bold text-heading" id="raceDetailsName">Human</h5>
                    <span class="badge bg-label-primary" id="raceCategoryBadge">Core Races</span>
                    <span class="badge bg-label-secondary font-monospace" id="racePointsBadge">10 RP</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mt-1 flex-wrap text-body-secondary" style="font-size: 0.82rem;">
                    <span class="fw-semibold text-success" id="raceAbilitySummary">+2 to One Ability Score</span>
                  </div>
                </div>
              </div>

              <!-- Quick stats pills -->
              <div class="d-flex align-items-center gap-2 flex-wrap" id="raceQuickPills">
                <span class="badge bg-body border text-body d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
                  <i class="icon-base ti tabler-eye text-primary"></i>
                  <span class="fw-semibold" id="raceSensesBadge">Normal Vision</span>
                </span>
                <span class="badge bg-body border text-body d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
                  <i class="icon-base ti tabler-ruler-2 text-info"></i>
                  <span class="fw-semibold" id="raceSizeBadge">Medium</span>
                </span>
                <span class="badge bg-body border text-body d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
                  <i class="icon-base ti tabler-run text-warning"></i>
                  <span class="fw-semibold" id="raceSpeedBadge">30 ft.</span>
                </span>
                <span class="badge bg-body border text-body d-flex align-items-center gap-1 py-2 px-3 shadow-xs">
                  <i class="icon-base ti tabler-message-language text-secondary"></i>
                  <span class="fw-semibold" id="raceLangBadge">Common</span>
                </span>
              </div>
            </div>

            <!-- Race Details Dynamic Content -->
            <div id="raceContentArea">
              <!-- Dynamically populated -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--/ Activity Timeline & Class Progression Card -->
    <div class="row">
      <!-- Connections -->
      <div class="col-lg-12 col-xl-6">
        <div class="card card-action mb-6">
          <div class="card-header align-items-center">
            <h5 class="card-action-title mb-0">Connections</h5>
            <div class="card-action-element">
              <div class="dropdown">
                <button type="button" class="btn btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow p-0 text-body-secondary" data-bs-toggle="dropdown" aria-expanded="false"><i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="javascript:void(0);">Share connections</a></li>
                  <li><a class="dropdown-item" href="javascript:void(0);">Suggest edits</a></li>
                  <li>
                    <hr class="dropdown-divider" />
                  </li>
                  <li><a class="dropdown-item" href="javascript:void(0);">Report bug</a></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/avatars/2.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Cecilia Payne</h6>
                      <small>45 Connections</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <button class="btn btn-label-primary btn-icon"><i class="icon-base ti tabler-user-check icon-22px"></i></button>
                  </div>
                </div>
              </li>
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Curtis Fletcher</h6>
                      <small>1.32k Connections</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <button class="btn btn-primary btn-icon"><i class="icon-base ti tabler-user-x icon-22px"></i></button>
                  </div>
                </div>
              </li>
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/avatars/10.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Alice Stone</h6>
                      <small>125 Connections</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <button class="btn btn-primary btn-icon"><i class="icon-base ti tabler-user-x icon-22px"></i></button>
                  </div>
                </div>
              </li>
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/avatars/7.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Darrell Barnes</h6>
                      <small>456 Connections</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <button class="btn btn-label-primary btn-icon"><i class="icon-base ti tabler-user-check icon-22px"></i></button>
                  </div>
                </div>
              </li>

              <li class="mb-6">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/avatars/12.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Eugenia Moore</h6>
                      <small>1.2k Connections</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <button class="btn btn-label-primary btn-icon"><i class="icon-base ti tabler-user-check icon-22px"></i></button>
                  </div>
                </div>
              </li>
              <li class="text-center">
                <a href="javascript:;">View all connections</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!--/ Connections -->
      <!-- Teams -->
      <div class="col-lg-12 col-xl-6">
        <div class="card card-action mb-6">
          <div class="card-header align-items-center">
            <h5 class="card-action-title mb-0">Teams</h5>
            <div class="card-action-element">
              <div class="dropdown">
                <button type="button" class="btn btn-icon btn-text-secondary dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown" aria-expanded="false"><i class="icon-base ti tabler-dots-vertical  icon-md text-body-secondary"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="javascript:void(0);">Share teams</a></li>
                  <li><a class="dropdown-item" href="javascript:void(0);">Suggest edits</a></li>
                  <li>
                    <hr class="dropdown-divider" />
                  </li>
                  <li><a class="dropdown-item" href="javascript:void(0);">Report bug</a></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/icons/brands/react-label.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">React Developers</h6>
                      <small>72 Members</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <a href="javascript:;"><span class="badge bg-label-danger">Developer</span></a>
                  </div>
                </div>
              </li>
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/icons/brands/support-label.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Support Team</h6>
                      <small>122 Members</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <a href="javascript:;"><span class="badge bg-label-primary">Support</span></a>
                  </div>
                </div>
              </li>
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/icons/brands/figma-label.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">UI Designers</h6>
                      <small>7 Members</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <a href="javascript:;"><span class="badge bg-label-info">Designer</span></a>
                  </div>
                </div>
              </li>
              <li class="mb-4">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/icons/brands/vue-label.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-2">
                      <h6 class="mb-0">Vue.js Developers</h6>
                      <small>289 Members</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <a href="javascript:;"><span class="badge bg-label-danger">Developer</span></a>
                  </div>
                </div>
              </li>
              <li class="mb-6">
                <div class="d-flex align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-2">
                      <img src="{{ asset('static/demo-vuexy/assets/img/icons/brands/twitter-label.png') }}" alt="Avatar" class="rounded-circle" />
                    </div>
                    <div class="me-w">
                      <h6 class="mb-0">Digital Marketing</h6>
                      <small>24 Members</small>
                    </div>
                  </div>
                  <div class="ms-auto">
                    <a href="javascript:;"><span class="badge bg-label-secondary">Marketing</span></a>
                  </div>
                </div>
              </li>
              <li class="text-center">
                <a href="javascript:;">View all teams</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!--/ Teams -->
    </div>
    <!-- Projects table -->
    <div class="card mb-6">
      <div class="mb-4">
        <table class="table datatable-project">
          <thead class="border-top">
            <tr>
              <th></th>
              <th></th>
              <th>Project</th>
              <th>Leader</th>
              <th>Team</th>
              <th class="w-px-200">Progress</th>
              <th>Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
    <!--/ Projects table -->
  </div>
</div>
<!--/ User Profile Content -->

@include('valiant-shade._partials._modals.modal-create-character')
@include('valiant-shade._partials._modals.modal-transfer-money')



<script id="pathfinderClassData" type="application/json">
  {!! json_encode([
    'classes' => $pfClasses ?? [],
    'races' => $pfRaces ?? [],
    'bab' => $bab ?? [],
    'saves' => $saves ?? [],
    'progressions' => $progressions ?? [],
  ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

<style>
  /* ====================================================
     PREMIUM RPG FINANCES & ACTIVITY LEDGER STYLES
     ==================================================== */
  .ledger-hero-banner {
    background: linear-gradient(135deg, rgba(23, 25, 45, 0.96) 0%, rgba(32, 35, 60, 0.88) 50%, rgba(22, 24, 44, 0.96) 100%) !important;
    border: 1px solid rgba(245, 158, 11, 0.32) !important;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
  }
  .ledger-hero-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #f59e0b 25%, #7367f0 75%, transparent);
  }
  .ledger-char-avatar {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.3), rgba(245, 158, 11, 0.25));
    border: 2px solid rgba(245, 158, 11, 0.5);
    box-shadow: 0 0 18px rgba(245, 158, 11, 0.25);
    overflow: hidden;
  }
  .ledger-stat-chip {
    background: rgba(16, 18, 30, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    padding: 0.55rem 1rem;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }
  .ledger-stat-chip:hover {
    border-color: rgba(245, 158, 11, 0.4);
    background: rgba(22, 25, 42, 0.95);
    transform: translateY(-1px);
  }
  .stat-chip-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
  }
  .stat-chip-icon.gold-glow {
    background: rgba(245, 158, 11, 0.18);
    border: 1px solid rgba(245, 158, 11, 0.4);
    box-shadow: 0 0 14px rgba(245, 158, 11, 0.25);
  }
  .stat-chip-icon.vault-glow {
    background: rgba(0, 186, 209, 0.18);
    border: 1px solid rgba(0, 186, 209, 0.4);
    box-shadow: 0 0 14px rgba(0, 186, 209, 0.25);
  }
  .btn-transfer-funds {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #12131c !important;
    border: 1px solid rgba(251, 191, 36, 0.75) !important;
    border-radius: 10px !important;
    padding: 0.68rem 1.35rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 18px rgba(245, 158, 11, 0.38) !important;
    transition: all 0.2s ease !important;
  }
  .btn-transfer-funds:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.55) !important;
    filter: brightness(1.08);
    color: #0b0c12 !important;
  }
  .timeline-filter-bar {
    background: rgba(16, 18, 30, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 4px;
    gap: 3px;
  }
  .timeline-filter-btn {
    background: transparent;
    color: #9ba0bc;
    border: none;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.44rem 0.9rem;
    transition: all 0.18s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
  }
  .timeline-filter-btn:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
  }
  .timeline-filter-btn.active {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 10px rgba(115, 103, 240, 0.45) !important;
  }

  /* Custom RPG Ledger Timeline Track & Nodes */
  #characterActivityTimelineList.timeline {
    position: relative;
    padding-left: 1.25rem !important;
  }
  #characterActivityTimelineList .ledger-timeline-item {
    position: relative !important;
    padding-left: 2.2rem !important;
    border-left: 2px dashed rgba(115, 103, 240, 0.25) !important;
    padding-bottom: 1.5rem !important;
    margin-bottom: 0 !important;
  }
  #characterActivityTimelineList .ledger-timeline-item:last-child {
    border-left-color: transparent !important;
    padding-bottom: 0.5rem !important;
  }
  #characterActivityTimelineList .timeline-point {
    position: absolute !important;
    left: -16px !important;
    top: 6px !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 3 !important;
    outline: none !important;
    border: 2px solid #20233b !important;
    box-shadow: 0 0 0 3px rgba(32, 35, 59, 0.8), 0 0 12px rgba(115, 103, 240, 0.35) !important;
  }
  #characterActivityTimelineList .timeline-event {
    top: 0 !important;
    padding: 0 !important;
  }

  .ledger-point-primary {
    background: linear-gradient(135deg, #7367f0, #5b50d6) !important;
    color: #fff !important;
  }
  .ledger-point-success {
    background: linear-gradient(135deg, #28c76f, #1f9d55) !important;
    color: #fff !important;
  }
  .ledger-point-danger {
    background: linear-gradient(135deg, #ea5455, #c5393a) !important;
    color: #fff !important;
  }
  .ledger-point-warning {
    background: linear-gradient(135deg, #ff9f43, #d97706) !important;
    color: #fff !important;
  }
  .ledger-point-info {
    background: linear-gradient(135deg, #00bad1, #008ba3) !important;
    color: #fff !important;
  }

  .ledger-event-card {
    background: linear-gradient(135deg, rgba(29, 32, 54, 0.88) 0%, rgba(38, 42, 70, 0.72) 100%) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 12px !important;
    padding: 1.15rem 1.35rem !important;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.28) !important;
    transition: all 0.24s cubic-bezier(0.16, 1, 0.3, 1) !important;
    position: relative;
    backdrop-filter: blur(8px);
  }
  .ledger-event-card:hover {
    transform: translateX(4px);
    border-color: rgba(115, 103, 240, 0.42) !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.38) !important;
    background: linear-gradient(135deg, rgba(34, 38, 65, 0.95) 0%, rgba(45, 50, 82, 0.8) 100%) !important;
  }
  .ledger-amount-pill {
    font-family: var(--bs-font-monospace);
    font-size: 0.88rem;
    font-weight: 800;
    padding: 0.35rem 0.85rem;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    letter-spacing: 0.2px;
  }
  .ledger-amount-pill.amount-positive {
    background: rgba(40, 199, 111, 0.16) !important;
    color: #28c76f !important;
    border: 1px solid rgba(40, 199, 111, 0.4) !important;
    box-shadow: 0 0 12px rgba(40, 199, 111, 0.2) !important;
  }
  .ledger-amount-pill.amount-negative {
    background: rgba(234, 84, 85, 0.16) !important;
    color: #ff6b6b !important;
    border: 1px solid rgba(234, 84, 85, 0.4) !important;
    box-shadow: 0 0 12px rgba(234, 84, 85, 0.2) !important;
  }
  .ledger-amount-pill.amount-neutral {
    background: rgba(245, 158, 11, 0.16) !important;
    color: #f59e0b !important;
    border: 1px solid rgba(245, 158, 11, 0.4) !important;
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.2) !important;
  }

  .character-item-card {
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    border-width: 2px !important;
  }
  .character-item-card:hover {
    transform: translateY(-2px);
    border-color: #7367f0 !important;
    box-shadow: 0 4px 16px rgba(115, 103, 240, 0.25) !important;
  }
  .character-item-card.is-selected {
    border-color: #7367f0 !important;
    background-color: rgba(115, 103, 240, 0.12) !important;
    box-shadow: 0 4px 20px rgba(115, 103, 240, 0.3) !important;
  }

  /* Table Progression Container & Custom Scrollbar */
  .table-progression-wrapper {
    overflow-x: auto !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(115, 103, 240, 0.5) rgba(43, 44, 73, 0.3);
  }
  .table-progression-wrapper::-webkit-scrollbar {
    height: 6px;
  }
  .table-progression-wrapper::-webkit-scrollbar-track {
    background: rgba(43, 44, 73, 0.3);
    border-radius: 4px;
  }
  .table-progression-wrapper::-webkit-scrollbar-thumb {
    background: rgba(115, 103, 240, 0.5);
    border-radius: 4px;
  }
  .table-progression-wrapper::-webkit-scrollbar-thumb:hover {
    background: rgba(115, 103, 240, 0.8);
  }

  /* Compact Table Styles */
  .table-progression {
    font-size: 0.77rem !important;
    border-collapse: collapse !important;
    width: 100%;
    margin-bottom: 0 !important;
  }
  .table-progression th,
  .table-progression td {
    padding: 0.32rem 0.38rem !important;
    text-transform: none !important; /* Fixes Turkish locale uppercase 'İ' bug (SPECİAL / WİLL) */
    letter-spacing: normal !important;
    vertical-align: middle !important;
  }
  .table-progression thead th {
    background-color: rgba(43, 44, 73, 0.96) !important;
    color: #e1def5 !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    font-weight: 600 !important;
    font-size: 0.75rem !important;
    white-space: nowrap !important;
  }
  .table-progression tbody td {
    border-color: rgba(255, 255, 255, 0.08) !important;
  }
  .table-progression .th-lvl {
    width: 52px;
    min-width: 50px;
  }
  .table-progression .th-bab {
    width: 98px;
    min-width: 90px;
  }
  .table-progression .th-save {
    width: 52px;
    min-width: 48px;
  }
  .table-progression .th-special {
    min-width: 165px;
    max-width: 230px;
    white-space: normal !important;
  }
  .table-progression .th-spells-group {
    min-width: 130px;
    letter-spacing: 0.3px;
  }
  .table-progression .th-spell-col {
    width: 36px;
    min-width: 34px;
    max-width: 42px;
    font-size: 0.74rem !important;
    padding: 0.25rem 0.2rem !important;
  }
  .table-progression .td-special {
    font-size: 0.74rem;
    line-height: 1.25;
    white-space: normal !important;
    word-break: normal;
  }
  .progression-current-level {
    background-color: rgba(115, 103, 240, 0.2) !important;
    border-left: 3px solid #7367f0 !important;
    font-weight: 600;
  }
  .progression-current-level td {
    color: #fff !important;
    background-color: rgba(115, 103, 240, 0.16) !important;
  }
  .btn-xs {
    padding: 0.22rem 0.45rem !important;
    font-size: 0.72rem !important;
    line-height: 1.2;
    border-radius: 0.25rem;
  }
</style>

<script id="serverCharactersData" type="application/json">
  {!! json_encode($charactersData ?? []) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const container = document.getElementById('characterCardsContainer');
  const emptyState = document.getElementById('characterEmptyState');
  const countBadge = document.getElementById('charListCountBadge');

  // Parse embedded data
  let pfData = { classes: {}, races: {}, bab: {}, saves: {}, progressions: {} };
  try {
    const rawDataEl = document.getElementById('pathfinderClassData');
    if (rawDataEl) {
      pfData = JSON.parse(rawDataEl.textContent || '{}');
      if (!pfData.races) pfData.races = {};
    }
  } catch (e) {
    console.error('Error parsing pfData:', e);
  }

  let selectedCharId = null;

  function getClassIcon(className) {
    if (!className) return '⚔️';
    const lower = className.toLowerCase();
    if (lower.includes('büyücü') || lower.includes('mage') || lower.includes('wizard')) return '🔮';
    if (lower.includes('hırsız') || lower.includes('rogue') || lower.includes('suikast')) return '🗡️';
    if (lower.includes('okçu') || lower.includes('ranger') || lower.includes('avcı')) return '🏹';
    if (lower.includes('paladin')) return '🛡️';
    if (lower.includes('rahip') || lower.includes('cleric')) return '✨';
    if (lower.includes('barbar') || lower.includes('barbarian')) return '🪓';
    if (lower.includes('keşiş') || lower.includes('monk')) return '🥋';
    if (lower.includes('ozan') || lower.includes('bard')) return '🎵';
    if (lower.includes('druid')) return '🌿';
    if (lower.includes('nekromansır') || lower.includes('necromancer')) return '💀';
    if (lower.includes('savaşçı') || lower.includes('warrior') || lower.includes('fighter')) return '⚔️';
    return '⚔️';
  }

  function getClassHitDie(classKey) {
    if (!classKey) return 'd10';
    const cInfo = pfData.classes[classKey] || pfData.progressions[classKey] || {};
    if (cInfo.hit_die) return cInfo.hit_die;
    const k = classKey.toLowerCase();
    if (k.includes('barbarian')) return 'd12';
    if (k.includes('fighter') || k.includes('paladin') || k.includes('ranger') || k.includes('cavalier') || k.includes('gunslinger') || k.includes('slayer') || k.includes('swashbuckler') || k.includes('brawler') || k.includes('bloodrager')) return 'd10';
    if (k.includes('wizard') || k.includes('sorcerer') || k.includes('witch') || k.includes('arcanist') || k.includes('psychic')) return 'd6';
    return 'd8';
  }

  function getHitDieSides(hdString) {
    if (!hdString) return 10;
    const match = hdString.match(/d(\d+)/i);
    return match ? parseInt(match[1], 10) : 10;
  }

  function cleanRaceName(str) {
    if (!str) return 'Human';
    return String(str).replace(/[\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/gu, '').trim() || 'Human';
  }

  function findRaceData(raceNameOrKey) {
    if (!raceNameOrKey || !pfData.races) return null;
    const clean = cleanRaceName(raceNameOrKey).toLowerCase().trim();
    
    // Direct key match
    if (pfData.races[clean]) return pfData.races[clean];

    // Normalized key match
    const normKey = clean.replace(/[\s\-\.]+/g, '_');
    if (pfData.races[normKey]) return pfData.races[normKey];

    const parenKey = clean.replace(/\s+/g, '_');
    if (pfData.races[parenKey]) return pfData.races[parenKey];

    // Search by key or name
    for (const k in pfData.races) {
      const r = pfData.races[k];
      if (k.toLowerCase() === clean || k.toLowerCase() === normKey) return r;
      if (r.name && r.name.toLowerCase() === clean) return r;
    }

    // Substring match
    for (const k in pfData.races) {
      const r = pfData.races[k];
      const rName = (r.name || '').toLowerCase();
      if (clean.includes(k.toLowerCase()) || (rName && clean.includes(rName))) return r;
      if (k.toLowerCase().includes(clean) || (rName && rName.includes(clean))) return r;
    }

    return null;
  }

  function detectSenses(raceData) {
    if (!raceData) return { label: 'Normal Vision', badge: 'Normal Vision', hasDarkvision: false, hasLowLight: false, details: 'Standard sight in normal light conditions.' };

    const summary = raceData.table_summary || {};
    const traitsSummary = raceData.standard_traits?.summary || {};
    const traitsList = raceData.standard_traits?.traits || [];

    const dvTrait = traitsList.find(t => {
      const n = (t.name || '').toLowerCase();
      const d = (t.description || t.raw || '').toLowerCase();
      return n.includes('darkvision') || d.includes('darkvision') || d.includes('see in the dark') || d.includes('perfectly in the dark');
    });

    const llTrait = traitsList.find(t => {
      const n = (t.name || '').toLowerCase();
      const d = (t.description || t.raw || '').toLowerCase();
      return n.includes('low-light') || d.includes('low-light') || d.includes('dim light');
    });

    const rawSense = summary.senses || traitsSummary.senses || '';
    const rawLower = rawSense.toLowerCase();

    const hasDarkvision = Boolean(dvTrait) || rawLower.includes('darkvision') || rawLower.includes('in the dark') || rawLower.includes('perfectly in the dark');
    const hasLowLight = Boolean(llTrait) || rawLower.includes('low-light') || rawLower.includes('dim light');

    let dvDistance = '60 ft.';
    const combinedText = `${rawSense} ${dvTrait ? (dvTrait.name + ' ' + (dvTrait.description || '')) : ''}`;
    if (combinedText.includes('120')) {
      dvDistance = '120 ft.';
    } else if (combinedText.includes('90')) {
      dvDistance = '90 ft.';
    } else if (combinedText.includes('60')) {
      dvDistance = '60 ft.';
    }

    const isSuperior = combinedText.toLowerCase().includes('superior darkvision');

    if (hasDarkvision && hasLowLight) {
      return {
        label: `${isSuperior ? 'Superior ' : ''}Darkvision (${dvDistance}) & Low-Light`,
        badge: `${isSuperior ? 'Superior ' : ''}Darkvision ${dvDistance}`,
        hasDarkvision: true,
        hasLowLight: true,
        details: `Provides perfect sight in dark conditions up to ${dvDistance} and sees twice as far as humans in dim light.`
      };
    }

    if (hasDarkvision) {
      return {
        label: `${isSuperior ? 'Superior ' : ''}Darkvision (${dvDistance})`,
        badge: `${isSuperior ? 'Superior ' : ''}Darkvision ${dvDistance}`,
        hasDarkvision: true,
        hasLowLight: false,
        details: `Provides perfect black-and-white vision in total darkness up to ${dvDistance}.`
      };
    }

    if (hasLowLight) {
      return {
        label: 'Low-Light Vision',
        badge: 'Low-Light Vision',
        hasDarkvision: false,
        hasLowLight: true,
        details: 'Sees twice as far as humans in dim light conditions.'
      };
    }

    return {
      label: 'Normal Vision',
      badge: 'Normal Vision',
      hasDarkvision: false,
      hasLowLight: false,
      details: 'Standard sight in normal light conditions.'
    };
  }

  function renderRaceDetails(char) {
    const tabRaceTitle = document.getElementById('tabRaceTitle');
    const raceNameEl = document.getElementById('raceDetailsName');
    const raceCategoryEl = document.getElementById('raceCategoryBadge');
    const racePointsEl = document.getElementById('racePointsBadge');
    const raceAbilitySummaryEl = document.getElementById('raceAbilitySummary');
    const raceSensesEl = document.getElementById('raceSensesBadge');
    const raceSizeEl = document.getElementById('raceSizeBadge');
    const raceSpeedEl = document.getElementById('raceSpeedBadge');
    const raceLangEl = document.getElementById('raceLangBadge');
    const raceContentEl = document.getElementById('raceContentArea');

    if (!char) {
      if (tabRaceTitle) tabRaceTitle.textContent = 'Race';
      if (raceContentEl) {
        raceContentEl.innerHTML = `
          <div class="text-center py-5 text-body-secondary">
            <i class="icon-base ti tabler-dna fs-1 mb-2 d-block text-muted"></i>
            <h6 class="fw-bold mb-1">No Character Selected</h6>
            <p class="text-body-secondary small mb-0">Select a character from the list on the left to view their racial traits and features.</p>
          </div>
        `;
      }
      return;
    }

    const rawRace = char.race || char.raceKey || 'Human';
    const cleanRace = cleanRaceName(rawRace);
    const raceData = findRaceData(char.raceKey || char.race || 'human');

    const displayRaceName = raceData ? raceData.name : cleanRace;
    if (tabRaceTitle) {
      tabRaceTitle.textContent = `Race (${displayRaceName})`;
    }

    if (!raceData) {
      if (raceNameEl) raceNameEl.textContent = cleanRace;
      if (raceCategoryEl) raceCategoryEl.textContent = 'Unknown Race';
      if (racePointsEl) racePointsEl.textContent = '—';
      if (raceAbilitySummaryEl) raceAbilitySummaryEl.textContent = 'No attribute information available';
      if (raceContentEl) {
        raceContentEl.innerHTML = `
          <div class="alert alert-info d-flex align-items-center mb-0">
            <i class="icon-base ti tabler-info-circle me-2 fs-4"></i>
            <div>Detailed rules not found for "${cleanRace}".</div>
          </div>
        `;
      }
      return;
    }

    // Header values
    if (raceNameEl) raceNameEl.textContent = raceData.name;
    if (raceCategoryEl) raceCategoryEl.textContent = raceData.category || 'Standard Race';
    if (racePointsEl) racePointsEl.textContent = raceData.race_points ? `${raceData.race_points} RP` : 'Standard RP';
    if (raceAbilitySummaryEl) raceAbilitySummaryEl.textContent = `Ability Score Bonus: ${raceData.ability_bonus_summary || 'Standard'}`;

    const summary = raceData.table_summary || {};
    const traitsSummary = raceData.standard_traits?.summary || {};
    const traitsList = raceData.standard_traits?.traits || [];

    // Detect Senses / Darkvision
    const sensesInfo = detectSenses(raceData);

    if (raceSensesEl) {
      if (sensesInfo.hasDarkvision) {
        raceSensesEl.innerHTML = `<i class="icon-base ti tabler-moon text-primary me-1"></i><span class="text-primary fw-bold">${sensesInfo.badge}</span>`;
      } else if (sensesInfo.hasLowLight) {
        raceSensesEl.innerHTML = `<i class="icon-base ti tabler-sun-moon text-warning me-1"></i><span class="text-warning fw-semibold">${sensesInfo.badge}</span>`;
      } else {
        raceSensesEl.innerHTML = `<i class="icon-base ti tabler-eye text-body-secondary me-1"></i>${sensesInfo.badge}`;
      }
      raceSensesEl.parentElement.title = `Senses: ${sensesInfo.label}`;
    }

    // Size
    const size = raceData.size || summary.size || traitsSummary.size || 'Medium';
    if (raceSizeEl) {
      raceSizeEl.textContent = size;
      raceSizeEl.parentElement.title = `Size: ${size}`;
    }

    // Speed
    const speed = raceData.speed || summary.speed || traitsSummary.base_speed || '30 ft.';
    if (raceSpeedEl) {
      raceSpeedEl.textContent = speed;
      raceSpeedEl.parentElement.title = `Speed: ${speed}`;
    }

    // Languages
    const langs = summary.starting_languages || traitsSummary.languages || 'Common';
    if (raceLangEl) {
      const shortLangs = langs.length > 25 ? langs.substring(0, 22) + '...' : langs;
      raceLangEl.textContent = shortLangs;
      raceLangEl.parentElement.title = `Languages: ${langs}`;
    }

    if (!raceContentEl) return;

    // 1. Overview Badges Card (Ability Modifiers, Darkvision, Size & Speed, Languages)
    const abilityDesc = traitsSummary.ability_score_modifiers || raceData.ability_bonus_summary || 'Standard Pathfinder attribute rules apply.';
    const sizeDesc = traitsSummary.size || `Creatures of size ${size} do not receive size bonuses or penalties.`;
    const speedDesc = traitsSummary.base_speed || `Base movement speed: ${speed}.`;
    const langDesc = traitsSummary.languages || `Starting languages: ${langs}.`;

    const summaryCardsHtml = `
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card h-100 border border-primary border-opacity-20 bg-label-primary bg-opacity-10 shadow-none">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar avatar-xs bg-primary text-white rounded p-1">
                  <i class="icon-base ti tabler-sparkles"></i>
                </div>
                <h6 class="mb-0 fw-bold text-heading">Ability Bonus</h6>
              </div>
              <div class="fw-bold text-primary mb-1">${raceData.ability_bonus_summary || 'Balanced'}</div>
              <small class="text-body-secondary" style="font-size: 0.76rem; line-height: 1.35; display: block;">${abilityDesc}</small>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="card h-100 border ${sensesInfo.hasDarkvision ? 'border-primary border-opacity-30 bg-label-primary bg-opacity-15' : (sensesInfo.hasLowLight ? 'border-warning border-opacity-30 bg-label-warning bg-opacity-10' : 'border-secondary border-opacity-20 bg-label-secondary bg-opacity-10')} shadow-none">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar avatar-xs ${sensesInfo.hasDarkvision ? 'bg-primary text-white' : (sensesInfo.hasLowLight ? 'bg-warning text-white' : 'bg-secondary text-white')} rounded p-1">
                  <i class="icon-base ti ${sensesInfo.hasDarkvision ? 'tabler-moon' : (sensesInfo.hasLowLight ? 'tabler-sun-moon' : 'tabler-eye')}"></i>
                </div>
                <h6 class="mb-0 fw-bold text-heading">Senses</h6>
              </div>
              <div class="fw-bold ${sensesInfo.hasDarkvision ? 'text-primary' : (sensesInfo.hasLowLight ? 'text-warning' : 'text-heading')} mb-1">${sensesInfo.label}</div>
              <small class="text-body-secondary" style="font-size: 0.76rem; line-height: 1.35; display: block;">
                ${sensesInfo.details}
              </small>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="card h-100 border border-info border-opacity-20 bg-label-info bg-opacity-10 shadow-none">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar avatar-xs bg-info text-white rounded p-1">
                  <i class="icon-base ti tabler-run"></i>
                </div>
                <h6 class="mb-0 fw-bold text-heading">Size & Speed</h6>
              </div>
              <div class="fw-bold text-info mb-1">${size} &bull; ${speed}</div>
              <small class="text-body-secondary" style="font-size: 0.76rem; line-height: 1.35; display: block;">${speedDesc}</small>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="card h-100 border border-warning border-opacity-20 bg-label-warning bg-opacity-10 shadow-none">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar avatar-xs bg-warning text-white rounded p-1">
                  <i class="icon-base ti tabler-language"></i>
                </div>
                <h6 class="mb-0 fw-bold text-heading">Languages</h6>
              </div>
              <div class="fw-bold text-warning mb-1 text-truncate">${langs}</div>
              <small class="text-body-secondary" style="font-size: 0.76rem; line-height: 1.35; display: block;">${langDesc}</small>
            </div>
          </div>
        </div>
      </div>
    `;

    // 2. Standard Racial Traits Cards
    let traitsHtml = '';
    traitsList.forEach(t => {
      const tName = t.name || 'Racial Trait';
      const tSub = t.subcategory || 'General';
      const tDesc = t.description || t.raw || '';

      let badgeColor = 'bg-label-primary';
      let icon = 'tabler-sparkles';
      const subLower = tSub.toLowerCase();
      const nameLower = tName.toLowerCase();

      if (nameLower.includes('darkvision') || subLower.includes('sense')) {
        badgeColor = 'bg-label-dark';
        icon = 'tabler-moon';
      } else if (subLower.includes('defense') || nameLower.includes('immunity') || nameLower.includes('hardy') || nameLower.includes('stability')) {
        badgeColor = 'bg-label-success';
        icon = 'tabler-shield-check';
      } else if (subLower.includes('offense') || nameLower.includes('weapon') || nameLower.includes('hatred') || nameLower.includes('ferocity')) {
        badgeColor = 'bg-label-danger';
        icon = 'tabler-swords';
      } else if (subLower.includes('magic') || nameLower.includes('spell')) {
        badgeColor = 'bg-label-info';
        icon = 'tabler-wand';
      } else if (subLower.includes('skill') || subLower.includes('feat') || nameLower.includes('bonus feat') || nameLower.includes('stonecunning')) {
        badgeColor = 'bg-label-warning';
        icon = 'tabler-award';
      }

      traitsHtml += `
        <div class="col-12 col-md-6 mb-3">
          <div class="card h-100 border border-secondary border-opacity-15 bg-body shadow-none">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <span class="fw-bold text-heading d-flex align-items-center gap-2">
                  <span class="badge ${badgeColor} p-1 rounded"><i class="icon-base ti ${icon} fs-6"></i></span>
                  <span>${tName}</span>
                </span>
                <span class="badge ${badgeColor} px-2 py-0_5" style="font-size: 0.68rem;">${tSub}</span>
              </div>
              <p class="mb-0 text-body-secondary small" style="line-height: 1.45;">
                ${tDesc}
              </p>
            </div>
          </div>
        </div>
      `;
    });

    // 3. Alternate Traits Section (if available)
    const altTraits = raceData.alternate_traits || [];
    let altTraitsHtml = '';
    if (altTraits.length > 0) {
      let altItems = '';
      altTraits.forEach((at, idx) => {
        const atName = at.name || `Alternate #${idx + 1}`;
        const atReplaces = at.replaces ? `<span class="badge bg-label-warning px-2 py-0_5 ms-auto" style="font-size: 0.7rem;"><i class="icon-base ti tabler-arrows-exchange me-1"></i>Replaces: ${at.replaces}</span>` : '';
        const atDesc = at.description || at.raw || '';
        altItems += `
          <div class="col-12 col-lg-6 mb-2">
            <div class="p-3 rounded-2 border border-secondary border-opacity-10 bg-label-secondary bg-opacity-10 h-100">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                <strong class="text-heading">${atName}</strong>
                ${atReplaces}
              </div>
              <div class="text-body-secondary small" style="line-height: 1.4;">${atDesc}</div>
            </div>
          </div>
        `;
      });

      altTraitsHtml = `
        <div class="col-12 mt-2">
          <div class="accordion" id="accordionAltTraits">
            <div class="accordion-item border border-warning border-opacity-25 shadow-none">
              <h2 class="accordion-header" id="headingAltTraits">
                <button class="accordion-button collapsed py-2 px-3 fw-semibold text-heading bg-label-warning bg-opacity-10" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAltTraits" aria-expanded="false" aria-controls="collapseAltTraits">
                  <i class="icon-base ti tabler-arrows-exchange me-2 text-warning fs-5"></i> Alternate Racial Traits
                  <span class="badge bg-warning text-dark ms-2 font-monospace" style="font-size: 0.7rem;">${altTraits.length} Options</span>
                </button>
              </h2>
              <div id="collapseAltTraits" class="accordion-collapse collapse" aria-labelledby="headingAltTraits" data-bs-parent="#accordionAltTraits">
                <div class="accordion-body p-3 row g-2">
                  ${altItems}
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    // 4. Lore Section (if available)
    const lore = raceData.lore || {};
    let loreHtml = '';
    if (lore.overview || lore.society || lore.physical_description) {
      let loreText = '';
      if (Array.isArray(lore.overview)) {
        loreText = lore.overview.join('</p><p class="mb-2">');
      } else if (typeof lore.overview === 'string') {
        loreText = lore.overview;
      }
      if (lore.physical_description) {
        loreText += `</p><h6 class="fw-bold text-heading mt-3 mb-1"><i class="icon-base ti tabler-user me-1 text-primary"></i> Physical Description</h6><p class="mb-2">${lore.physical_description}`;
      }
      if (lore.society) {
        loreText += `</p><h6 class="fw-bold text-heading mt-3 mb-1"><i class="icon-base ti tabler-building-community me-1 text-info"></i> Society</h6><p class="mb-2">${lore.society}`;
      }
      if (lore.relations) {
        loreText += `</p><h6 class="fw-bold text-heading mt-3 mb-1"><i class="icon-base ti tabler-users me-1 text-success"></i> Relations</h6><p class="mb-2">${lore.relations}`;
      }

      loreHtml = `
        <div class="col-12 mt-2">
          <div class="accordion" id="accordionLore">
            <div class="accordion-item border border-info border-opacity-25 shadow-none">
              <h2 class="accordion-header" id="headingLore">
                <button class="accordion-button collapsed py-2 px-3 fw-semibold text-heading bg-label-info bg-opacity-10" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLore" aria-expanded="false" aria-controls="collapseLore">
                  <i class="icon-base ti tabler-book me-2 text-info fs-5"></i> Lore & Background
                </button>
              </h2>
              <div id="collapseLore" class="accordion-collapse collapse" aria-labelledby="headingLore" data-bs-parent="#accordionLore">
                <div class="accordion-body p-3 text-body-secondary small" style="line-height: 1.55;">
                  <p class="mb-2">${loreText}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    raceContentEl.innerHTML = `
      <!-- Specifications Cards -->
      ${summaryCardsHtml}

      <!-- Standard Traits Header -->
      <div class="row g-3">
        <div class="col-12 d-flex align-items-center justify-content-between mb-1">
          <h6 class="fw-bold text-heading mb-0 d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-shield-check text-primary"></i> Standard Racial Traits
            <span class="badge bg-label-primary font-monospace">${traitsList.length} Traits</span>
          </h6>
          <span class="small text-muted font-monospace">Pathfinder 1E Official</span>
        </div>

        <!-- Trait Cards List -->
        ${traitsHtml || '<div class="col-12 text-muted">Standard traits list not found.</div>'}

        <!-- Alternate Traits Accordion -->
        ${altTraitsHtml}

        <!-- Lore Accordion -->
        ${loreHtml}
      </div>
    `;
  }

  const userUuid = "{{ Auth::user()->uuid ?? Auth::id() ?? 'guest' }}";
  const charStorageKey = 'valiant_shade_characters_' + userUuid;

  function syncCharacterWithBackend(characterName) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('{{ url("/valiant-shade/app/user/sync-character") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ character: characterName || null })
    }).catch(err => console.warn('Could not sync character with backend:', err));
  }

  function getCharacters() {
    // Purge legacy global key that leaked dummy characters like VALERUS
    try {
      localStorage.removeItem('valiant_shade_characters');
    } catch (e) {}

    // Server-rendered characters from PostgreSQL are the definitive source of truth
    const serverEl = document.getElementById('serverCharactersData');
    if (serverEl) {
      try {
        const serverList = JSON.parse(serverEl.textContent || '[]');
        if (Array.isArray(serverList)) {
          try {
            localStorage.setItem(charStorageKey, JSON.stringify(serverList));
          } catch (e) {}
          return serverList;
        }
      } catch (e) {
        console.error('Error parsing server characters:', e);
      }
    }

    let raw = localStorage.getItem(charStorageKey);
    if (!raw) return [];

    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function saveCharacters(chars) {
    try {
      const list = chars || [];
      localStorage.setItem(charStorageKey, JSON.stringify(list));
      localStorage.setItem('valiant_shade_characters', JSON.stringify(list));
      const activeChar = list.find(c => c.active) || (list.length > 0 ? list[0] : null);
      syncCharacterWithBackend(activeChar ? activeChar.name : null);
    } catch (e) {
      console.error(e);
    }
  }

  function deleteAllCharacters() {
    const chars = getCharacters();
    if (chars.length === 0) return;

    if (!confirm(`Are you sure you want to permanently delete all characters for this user (${chars.length} characters)?`)) {
      return;
    }

    saveCharacters([]);
    try {
      localStorage.setItem(charStorageKey, JSON.stringify([]));
      localStorage.setItem('valiant_shade_characters', JSON.stringify([]));
    } catch (e) {}

    selectedCharId = null;
    renderCharacters();
    renderEmptyProgressionState();
  }

  function renderEmptyProgressionState() {
    const tableCaption = document.getElementById('classTableCaption');
    if (tableCaption) {
      tableCaption.innerHTML = `<i class="icon-base ti tabler-shield-off text-muted"></i> Table: No Character`;
    }
    const tabTitle = document.getElementById('tabProgressionTitle');
    if (tabTitle) {
      tabTitle.textContent = `Class`;
    }
    const tabRaceTitle = document.getElementById('tabRaceTitle');
    if (tabRaceTitle) {
      tabRaceTitle.textContent = `Race`;
    }
    const nameEl = document.getElementById('progSelectedCharName');
    if (nameEl) nameEl.textContent = 'Character Not Found';
    const iconEl = document.getElementById('progSelectedCharIcon');
    if (iconEl) iconEl.innerHTML = '<i class="icon-base ti tabler-user fs-4"></i>';
    const raceEl = document.getElementById('progSelectedCharRace');
    if (raceEl) raceEl.textContent = '—';
    const classEl = document.getElementById('progSelectedCharClass');
    if (classEl) classEl.textContent = '—';
    const lvlEl = document.getElementById('progSelectedCharLvlBadge');
    if (lvlEl) lvlEl.textContent = 'Level —';
    const activeBadge = document.getElementById('progSelectedCharActiveBadge');
    if (activeBadge) {
      activeBadge.className = 'badge bg-label-secondary';
      activeBadge.textContent = 'No Character';
    }
    const chipHd = document.getElementById('chipHitDie');
    if (chipHd) chipHd.textContent = '—';
    const chipSpellWrap = document.getElementById('chipSpellWrapper');
    if (chipSpellWrap) chipSpellWrap.classList.add('d-none');

    const tbody = document.getElementById('classProgressionTableBody');
    if (tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="12" class="text-center py-5 text-body-secondary">
            <i class="icon-base ti tabler-ghost fs-1 mb-2 d-block text-muted"></i>
            <h6 class="fw-bold mb-1">You do not have a character yet</h6>
            <p class="text-body-secondary small mb-3">Create a character to view their class progression table.</p>
            <button type="button" class="btn btn-sm btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
              <i class="icon-base ti tabler-user-plus me-1"></i> Create New Character
            </button>
          </td>
        </tr>
      `;
    }

    renderRaceDetails(null);
  }

  function normalizeClassKey(c) {
    if (c && c.classKey && (pfData.progressions[c.classKey] || pfData.classes[c.classKey])) {
      return c.classKey;
    }
    const txt = ((c && c.class) ? c.class : '').toLowerCase();
    if (txt.includes('paladin')) return 'paladin';
    if (txt.includes('barbar')) return 'barbarian';
    if (txt.includes('savaşçı') || txt.includes('warrior') || txt.includes('fighter')) return 'fighter';
    if (txt.includes('okçu') || txt.includes('ranger') || txt.includes('avcı')) return 'ranger';
    if (txt.includes('hırsız') || txt.includes('rogue') || txt.includes('suikast')) return 'rogue';
    if (txt.includes('rahip') || txt.includes('cleric')) return 'cleric';
    if (txt.includes('büyücü') || txt.includes('wizard') || txt.includes('mage')) return 'wizard';
    if (txt.includes('keşiş') || txt.includes('monk')) return 'monk';
    if (txt.includes('ozan') || txt.includes('bard')) return 'bard';
    if (txt.includes('druid')) return 'druid';
    if (txt.includes('sorcerer')) return 'sorcerer';
    return 'paladin';
  }

  function getLevelOrdinal(lvl) {
    if (lvl === 1) return '1st';
    if (lvl === 2) return '2nd';
    if (lvl === 3) return '3rd';
    return lvl + 'th';
  }

  function renderClassProgressionTable(classKey, char) {
    classKey = classKey || 'paladin';
    const clsInfo = pfData.classes[classKey] || {};
    const clsProg = pfData.progressions[classKey] || {};
    const babProg = pfData.bab[clsInfo.bab_table || clsProg.bab_table || 'bab_section_1'] || {};
    const savesProg = pfData.saves[classKey] || {};

    const className = clsProg.name || clsInfo.name || classKey.charAt(0).toUpperCase() + classKey.slice(1);
    const hitDie = clsProg.hit_die || clsInfo.hit_die || 'd10';
    const spellcasting = clsProg.spellcasting || clsInfo.spellcasting || 'None';
    const hasSpells = Boolean(clsProg.has_spells);
    const spellLevels = clsProg.spell_levels || [];
    const specials = clsProg.specials || {};
    const spells = clsProg.spells || {};
    const charLevel = char ? (parseInt(char.level, 10) || 1) : 1;

    // Update Header and Caption
    const tableCaption = document.getElementById('classTableCaption');
    if (tableCaption) {
      tableCaption.innerHTML = `<i class="icon-base ti tabler-shield text-primary"></i> Table: ${className}`;
    }
    const tabTitle = document.getElementById('tabProgressionTitle');
    if (tabTitle) {
      tabTitle.textContent = `Class (${className})`;
    }

    // Update Hit Die & Spell Type badges
    const chipHd = document.getElementById('chipHitDie');
    if (chipHd) chipHd.textContent = hitDie;

    const chipSpell = document.getElementById('chipSpellType');
    const chipSpellWrap = document.getElementById('chipSpellWrapper');
    if (chipSpell) {
      if (hasSpells) {
        if (chipSpellWrap) chipSpellWrap.classList.remove('d-none');
        chipSpell.textContent = spellcasting.split('(')[0].trim();
        chipSpell.title = spellcasting;
      } else {
        if (chipSpellWrap) chipSpellWrap.classList.add('d-none');
      }
    }

    // Update Character Bar if char provided
    if (char) {
      const nameEl = document.getElementById('progSelectedCharName');
      if (nameEl) nameEl.textContent = char.name;
      const iconEl = document.getElementById('progSelectedCharIcon');
      if (iconEl) iconEl.textContent = char.icon || getClassIcon(char.class);
      const raceEl = document.getElementById('progSelectedCharRace');
      if (raceEl) raceEl.textContent = cleanRaceName(char.race);
      const classEl = document.getElementById('progSelectedCharClass');
      if (classEl) classEl.textContent = className;
      const lvlEl = document.getElementById('progSelectedCharLvlBadge');
      if (lvlEl) lvlEl.textContent = `Level ${charLevel}`;
      const activeBadge = document.getElementById('progSelectedCharActiveBadge');
      if (activeBadge) {
        activeBadge.className = char.active ? 'badge bg-label-success' : 'badge bg-label-secondary';
        activeBadge.textContent = char.active ? 'Active Character' : 'Selected Character';
      }
    }

    // Build Table Header (Handle Spells Colspan)
    const spellsHeaderCell = document.getElementById('spellsColGroupHeader');
    const spellsSubRow = document.getElementById('tableSpellsSubHeaderRow');
    if (hasSpells && spellLevels.length > 0) {
      if (spellsHeaderCell) {
        spellsHeaderCell.classList.remove('d-none');
        spellsHeaderCell.setAttribute('colspan', spellLevels.length);
      }
      if (spellsSubRow) {
        spellsSubRow.classList.remove('d-none');
        spellsSubRow.innerHTML = spellLevels.map(sl => `<th class="text-center th-spell-col">${sl}</th>`).join('');
      }
    } else {
      if (spellsHeaderCell) spellsHeaderCell.classList.add('d-none');
      if (spellsSubRow) spellsSubRow.classList.add('d-none');
    }

    // Build Table Rows
    const tbody = document.getElementById('classProgressionTableBody');
    if (!tbody) return;

    let rowsHtml = '';
    for (let lvl = 1; lvl <= 20; lvl++) {
      const isCurrentLevel = (lvl === charLevel);
      const ord = getLevelOrdinal(lvl);
      const babVal = babProg[lvl] || '+0';
      const fortVal = savesProg[lvl]?.fort || '+0';
      const refVal = savesProg[lvl]?.ref || '+0';
      const willVal = savesProg[lvl]?.will || '+0';
      const specialText = specials[lvl] || '—';

      let spellsCells = '';
      if (hasSpells && spellLevels.length > 0) {
        const lvlSpells = spells[lvl] || [];
        for (let sIdx = 0; sIdx < spellLevels.length; sIdx++) {
          const sVal = lvlSpells[sIdx] !== undefined ? lvlSpells[sIdx] : '—';
          spellsCells += `<td class="text-center font-monospace">${sVal}</td>`;
        }
      }

      rowsHtml += `
        <tr class="${isCurrentLevel ? 'progression-current-level' : ''}">
          <td class="text-center fw-bold">
            ${isCurrentLevel ? `<span class="badge bg-primary px-1 py-0 me-1" style="font-size: 0.62rem; vertical-align: baseline;">Lvl ${charLevel}</span>` : ''}
            ${ord}
          </td>
          <td class="text-center font-monospace fw-semibold">${babVal}</td>
          <td class="text-center font-monospace">${fortVal}</td>
          <td class="text-center font-monospace">${refVal}</td>
          <td class="text-center font-monospace">${willVal}</td>
          <td class="td-special">${specialText}</td>
          ${spellsCells}
        </tr>
      `;
    }

    tbody.innerHTML = rowsHtml;
  }

  function selectCharacter(id) {
    selectedCharId = id;
    const chars = getCharacters();
    const found = chars.find(c => c.id === id);
    if (!found) {
      if (chars.length > 0) {
        selectCharacter(chars[0].id);
      } else {
        selectedCharId = null;
        renderEmptyProgressionState();
      }
      return;
    }

    renderCharacters();

    const classKey = normalizeClassKey(found);
    renderClassProgressionTable(classKey, found);
    renderRaceDetails(found);
    renderActivityTimeline(found);
  }

  // ----------------------------------------------------
  // ACTIVITY TIMELINE & FINANCIAL LEDGER ENGINE
  // ----------------------------------------------------
  const activityStorageKey = 'valiant_shade_activities_v6_' + userUuid;
  let currentTimelineFilter = 'all';

  function formatActivityDateTime(dateInput) {
    const d = (dateInput instanceof Date && !isNaN(dateInput)) ? dateInput : (dateInput ? new Date(dateInput) : new Date());
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const isTr = '{{ app()->getLocale() }}' === 'tr';
    if (isTr) {
      return `${day}.${month}.${year} saat ${hours}.${minutes} tarihinde gönderildi`;
    } else {
      return `Sent on ${day}.${month}.${year} at ${hours}:${minutes}`;
    }
  }

  function getAllActivities() {
    try {
      let raw = localStorage.getItem(activityStorageKey);
      if (!raw) {
        raw = localStorage.getItem('valiant_shade_activities_v5_' + userUuid) ||
              localStorage.getItem('valiant_shade_activities_v4_' + userUuid) ||
              localStorage.getItem('valiant_shade_activities_v3_' + userUuid);
      }
      if (raw) {
        const parsed = JSON.parse(raw) || {};
        const isTr = '{{ app()->getLocale() }}' === 'tr';
        let modified = false;

        Object.keys(parsed).forEach(charId => {
          if (Array.isArray(parsed[charId])) {
            parsed[charId].forEach((item, index) => {
              // Rule 1: Failed Quest gives +20 Gold, but quest itself is a FAILURE so badge & node are RED (danger)
              if (item.amount === '-20 Gold' || (item.title && (item.title.includes('Failed') || item.title.includes('Başarısız') || item.title.includes('Penalty') || item.title.includes('Cezası')))) {
                item.title = '{{ __("Failed Quest") }}';
                item.amount = '+20 Gold';
                item.point = 'danger';
                item.badge = '{{ __("Failed Quest") }}';
                item.icon = 'ti tabler-circle-x';
                item.desc = isTr
                  ? 'Zindandan Kaçış görevi tamamlanamadı. Lonca keşif ve teselli ödeneği aktarıldı.'
                  : 'Dungeon Escape quest failed. Guild consolation and hazard allowance credited.';
                modified = true;
              }

              // Rule 2: Dates must be in format "16.09.2026 saat 11.11 tarihinde gönderildi"
              if (!item.time || item.time === '{{ __("Recent") }}' || item.time === 'Recent' || item.time === 'Son' || item.time === '{{ __("Just now") }}' || !item.time.includes('tarihinde')) {
                const itemDate = item.timestamp ? new Date(item.timestamp) : new Date(Date.now() - (index * 3600 * 1000));
                item.time = formatActivityDateTime(itemDate);
                item.timestamp = itemDate.getTime();
                modified = true;
              }
            });
          }
        });

        if (modified) {
          saveAllActivities(parsed);
        }
        return parsed;
      }
    } catch (e) {}
    return {};
  }

  function saveAllActivities(acts) {
    try {
      localStorage.setItem(activityStorageKey, JSON.stringify(acts || {}));
    } catch (e) {}
  }

  function getCharacterActivities(char) {
    if (!char) return [];
    const allActs = getAllActivities();
    if (!allActs[char.id] || allActs[char.id].length === 0) {
      const isTr = '{{ app()->getLocale() }}' === 'tr';

      allActs[char.id] = [
        {
          id: 'act_' + Date.now() + '_1',
          category: 'milestone',
          title: '{{ __("Successful Quest Payout") }}',
          timestamp: new Date('2026-09-16T11:11:00').getTime(),
          time: isTr ? '16.09.2026 saat 11.11 tarihinde gönderildi' : 'Sent on 16.09.2026 at 11:11',
          desc: isTr ? 'Goblin Pususu görevi başarıyla tamamlandı. Lonca ödülü hesaba aktarıldı.' : 'Goblin Ambush bounty quest completed successfully. Guild reward credited.',
          amount: '+50 Gold',
          point: 'success',
          badge: '{{ __("Quest Payout") }}',
          icon: 'ti tabler-circle-check',
          meta: '{{ __("Quest") }}: {{ __("Goblin Ambush") }}'
        },
        {
          id: 'act_' + Date.now() + '_2',
          category: 'milestone',
          title: '{{ __("Failed Quest") }}',
          timestamp: new Date('2026-09-16T10:45:00').getTime(),
          time: isTr ? '16.09.2026 saat 10.45 tarihinde gönderildi' : 'Sent on 16.09.2026 at 10:45',
          desc: isTr ? 'Zindandan Kaçış görevi tamamlanamadı. Lonca keşif ve teselli ödeneği aktarıldı.' : 'Dungeon Escape quest failed. Guild consolation and hazard allowance credited.',
          amount: '+20 Gold',
          point: 'danger',
          badge: '{{ __("Failed Quest") }}',
          icon: 'ti tabler-circle-x',
          meta: '{{ __("Quest") }}: {{ __("Dungeon Escape") }}'
        },
        {
          id: 'act_' + Date.now() + '_3',
          category: 'transfers',
          title: isTr ? 'Lyra Shadowmend adlı karakter size 100 Gold para gönderdi' : 'Lyra Shadowmend sent you 100 Gold',
          timestamp: new Date('2026-09-16T09:30:00').getTime(),
          time: isTr ? '16.09.2026 saat 09.30 tarihinde gönderildi' : 'Sent on 16.09.2026 at 09:30',
          desc: isTr ? 'Zindan keşif seferi ganimet payı ve ortak ekipman desteği.' : 'Dungeon expedition loot share and party supplies fund.',
          amount: '+100 Gold',
          point: 'success',
          badge: '{{ __("Incoming Transfer") }}',
          icon: 'ti tabler-arrow-down-left',
          meta: '{{ __("Sender") }}: Lyra Shadowmend'
        },
        {
          id: 'act_' + Date.now() + '_4',
          category: 'transfers',
          title: isTr ? `${char.name}, Valen Ironforge adlı oyuncuya 200 Gold gönderdi` : `${char.name} sent 200 Gold to Valen Ironforge`,
          timestamp: new Date('2026-09-15T16:20:00').getTime(),
          time: isTr ? '15.09.2026 saat 16.20 tarihinde gönderildi' : 'Sent on 15.09.2026 at 16:20',
          desc: isTr ? 'Demirci ustasından zırh siparişi ve malzeme tedarik ödemesi.' : 'Blacksmith armor order and material supply payment.',
          amount: '-200 Gold',
          point: 'danger',
          badge: '{{ __("Outgoing Transfer") }}',
          icon: 'ti tabler-arrow-up-right',
          meta: '{{ __("Recipient") }}: Valen Ironforge'
        }
      ];
      saveAllActivities(allActs);
    }
    return allActs[char.id] || [];
  }

  function addCharacterActivity(charId, activity) {
    const allActs = getAllActivities();
    if (!allActs[charId]) allActs[charId] = [];
    const now = new Date();
    allActs[charId].unshift({
      id: 'act_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
      timestamp: now.getTime(),
      time: formatActivityDateTime(now),
      ...activity
    });
    saveAllActivities(allActs);
  }

  function renderActivityTimeline(char) {
    const goldValEl = document.getElementById('timelineCharGoldVal');
    const spValEl = document.getElementById('timelineCharSpVal');
    const timelineList = document.getElementById('characterActivityTimelineList');
    const emptyEl = document.getElementById('timelineEmptyState');
    const countEl = document.getElementById('timelineActivityCountBadge');

    if (!char) {
      if (goldValEl) goldValEl.textContent = '0';
      if (spValEl) spValEl.textContent = '(0 SP)';
      if (timelineList) timelineList.innerHTML = '';
      if (emptyEl) emptyEl.classList.remove('d-none');
      if (countEl) countEl.textContent = '0 {{ __("Events") }}';
      return;
    }

    const gp = Number(char.gold || 0);
    if (goldValEl) goldValEl.textContent = gp.toLocaleString();
    if (spValEl) spValEl.textContent = `(${(gp * 10).toLocaleString()} SP)`;

    const allItems = getCharacterActivities(char);
    const filtered = allItems.filter(item => {
      if (currentTimelineFilter === 'all') return true;
      if (currentTimelineFilter === 'transfers') return item.category === 'transfers';
      if (currentTimelineFilter === 'vault') return item.category === 'vault';
      if (currentTimelineFilter === 'milestone') return item.category === 'milestone';
      return true;
    });

    if (countEl) countEl.textContent = `${filtered.length} {{ __("Events") }}`;

    if (filtered.length === 0) {
      if (timelineList) timelineList.innerHTML = '';
      if (emptyEl) emptyEl.classList.remove('d-none');
      return;
    }

    if (emptyEl) emptyEl.classList.add('d-none');

    let html = '';
    filtered.forEach(item => {
      const isFailedQuest = Boolean(
        (item.badge && (item.badge.includes('Failed') || item.badge.includes('Başarısız') || item.badge.includes('Penalty') || item.badge.includes('Ceza'))) ||
        (item.title && (item.title.includes('Failed') || item.title.includes('Başarısız') || item.title.includes('Penalty') || item.title.includes('Ceza')))
      );

      const pointColor = isFailedQuest ? 'danger' : (item.point || 'primary');
      const pointIcon = isFailedQuest ? 'ti tabler-circle-x' : (item.icon || 'ti tabler-coin');

      const isPositive = item.amount && item.amount.startsWith('+');
      const isNegative = item.amount && item.amount.startsWith('-');
      const amountClass = isPositive
        ? 'amount-positive'
        : (isNegative ? 'amount-negative' : 'amount-neutral');
      const amountIcon = isPositive
        ? 'ti tabler-arrow-down-left'
        : (isNegative ? 'ti tabler-arrow-up-right' : 'ti tabler-coin');

      let badgeBg = '';
      if (isFailedQuest) {
        badgeBg = 'bg-label-danger text-danger border border-danger border-opacity-25';
      } else if (item.category === 'vault') {
        badgeBg = 'bg-label-info text-info border border-info border-opacity-25';
      } else if (item.category === 'transfers') {
        badgeBg = isPositive
          ? 'bg-label-success text-success border border-success border-opacity-25'
          : 'bg-label-warning text-warning border border-warning border-opacity-25';
      } else {
        badgeBg = isPositive
          ? 'bg-label-success text-success border border-success border-opacity-25'
          : 'bg-label-danger text-danger border border-danger border-opacity-25';
      }

      html += `
        <li class="timeline-item timeline-item-transparent ledger-timeline-item">
          <span class="timeline-point ledger-point-${pointColor}">
            <i class="icon-base ${pointIcon} fs-6"></i>
          </span>
          <div class="timeline-event">
            <div class="ledger-event-card">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge ${badgeBg} font-monospace fw-semibold px-2 py-1" style="font-size: 0.72rem;">${item.badge || '{{ __("Activity") }}'}</span>
                  <h6 class="mb-0 fw-bold text-heading" style="font-size: 0.96rem;">${item.title}</h6>
                </div>
                <div class="d-flex align-items-center gap-1 text-body-secondary font-monospace" style="font-size: 0.78rem; opacity: 0.88;">
                  <i class="icon-base ti tabler-calendar-time fs-6 text-primary opacity-75"></i>
                  <span>${item.time || formatActivityDateTime()}</span>
                </div>
              </div>
              <p class="mb-3 text-body-secondary" style="font-size: 0.88rem; line-height: 1.5;">${item.desc || ''}</p>
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top border-secondary border-opacity-10">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  ${item.meta ? `<span class="badge bg-label-secondary font-monospace text-body border border-secondary border-opacity-20"><i class="icon-base ti tabler-info-circle me-1 text-primary"></i>${item.meta}</span>` : ''}
                </div>
                <div>
                  ${item.amount ? `<span class="ledger-amount-pill ${amountClass}"><i class="icon-base ${amountIcon} me-1"></i>${item.amount}</span>` : ''}
                </div>
              </div>
            </div>
          </div>
        </li>
      `;
    });

    if (timelineList) timelineList.innerHTML = html;
  }

  // Filter Group Click Listener
  document.querySelectorAll('.timeline-filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.timeline-filter-btn').forEach(b => {
        b.classList.remove('active');
      });
      this.classList.add('active');
      currentTimelineFilter = this.getAttribute('data-filter') || 'all';
      const chars = getCharacters();
      const char = chars.find(c => c.id === selectedCharId) || chars[0];
      renderActivityTimeline(char);
    });
  });

  function renderCharacters() {
    if (!container) return;
    const chars = getCharacters();

    if (countBadge) {
      countBadge.textContent = `${chars.length} Characters`;
    }

    if (chars.length === 0) {
      container.innerHTML = '';
      if (emptyState) emptyState.classList.remove('d-none');
      selectedCharId = null;
      renderEmptyProgressionState();
      renderActivityTimeline(null);
      return;
    }

    if (emptyState) emptyState.classList.add('d-none');

    // Ensure there is a selected character
    if (!selectedCharId || !chars.some(c => c.id === selectedCharId)) {
      const activeChar = chars.find(c => c.active) || chars[0];
      selectedCharId = activeChar.id;
    }

    let html = '';
    chars.forEach((c) => {
      const portraitSrc = c.portrait || c.avatar;
      const hasImg = portraitSrc && (portraitSrc.startsWith('http') || portraitSrc.startsWith('data:image'));
      const icon = hasImg
        ? `<img src="${portraitSrc}" alt="${c.name}" class="rounded-3 w-100 h-100" style="object-fit: cover; object-position: top center;" />`
        : (c.icon || getClassIcon(c.class));
      const isActive = Boolean(c.active);
      const isSelected = (c.id === selectedCharId);
      const hd = c.hitDie || getClassHitDie(c.classKey);
      const currentHp = (c.maxHp || (typeof c.hp === 'string' ? parseInt(c.hp.split('/')[0], 10) : c.hp)) || 10;
      const maxHp = (c.maxHp || (typeof c.hp === 'string' ? (parseInt(c.hp.split('/')[1], 10) || currentHp) : c.hp)) || currentHp;
      const gold = c.gold || 0;

      html += `
        <div class="card border ${isSelected ? 'is-selected' : (isActive ? 'border-primary border-opacity-50' : 'border-secondary border-opacity-25')} bg-label-secondary bg-opacity-10 shadow-none p-3 character-item-card" data-id="${c.id}">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-md ${isActive ? 'bg-primary text-white' : 'bg-label-primary text-primary'} rounded-3 d-flex align-items-center justify-content-center fs-4 shadow-sm overflow-hidden" style="width: 44px; height: 44px;">
                ${icon}
              </div>
              <div>
                @php
                @endphp
                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                  <h6 class="mb-0 fw-bold text-heading me-1">${c.name}</h6>
                  ${(c.rank && c.rank.toLowerCase() === 'clerk') 
                    ? '<span class="badge bg-info text-white px-2 py-0_5" style="font-size: 0.7rem;"><i class="icon-base ti tabler-feather me-1"></i>Clerk</span>'
                    : '<span class="badge bg-primary text-white px-2 py-0_5" style="font-size: 0.7rem;"><i class="icon-base ti tabler-crown me-1"></i>Main</span>'}
                  ${isActive ? '<span class="badge bg-label-success px-2 py-0_5" style="font-size: 0.7rem;">Active</span>' : ''}
                  ${isSelected ? '<span class="badge bg-label-primary px-2 py-0_5" style="font-size: 0.7rem;"><i class="icon-base ti tabler-eye icon-xs me-1"></i>Selected</span>' : ''}
                </div>
                <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                  <span class="badge bg-label-primary px-2 py-0_5" style="font-size: 0.7rem;" title="Permanent Race"><i class="icon-base ti tabler-lock icon-xs me-1 text-muted"></i>${cleanRaceName(c.race)}</span>
                  <span class="badge bg-label-info px-2 py-0_5" style="font-size: 0.7rem;">${c.class}</span>
                </div>
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="badge ${isActive ? 'bg-primary text-white' : 'bg-label-secondary'} rounded-pill fw-bold">Lvl ${c.level || 1}</span>
              <span class="badge bg-label-secondary font-monospace" style="font-size: 0.68rem;" title="Hit Die">HD: ${hd}</span>
            </div>
          </div>

          <!-- HP Bar -->
          <div class="row g-2 mt-1 pt-2 border-top border-secondary border-opacity-10">
            <div class="col-12">
              <div class="d-flex justify-content-between mb-1 text-body-secondary" style="font-size: 0.72rem;">
                <span><i class="icon-base ti tabler-heart text-danger me-1"></i>HP</span>
                <span class="fw-semibold text-heading">${currentHp}/${maxHp} HP</span>
              </div>
              <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-danger" role="progressbar" style="width: ${Math.min(100, Math.round((currentHp / maxHp) * 100))}%"></div>
              </div>
            </div>
          </div>

          <!-- Purse / Balance -->
          <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top border-secondary border-opacity-10">
            <span class="text-body-secondary" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-coin text-warning me-1"></i>{{ __('Purse') }}
            </span>
            <span class="badge bg-label-warning font-monospace fw-bold" style="font-size: 0.74rem;">
              ${gold} GP <small class="text-body-secondary">(${gold * 10} SP)</small>
            </span>
          </div>

          <!-- Buttons: Set Active, Edit, Delete -->
          <div class="d-flex align-items-center justify-content-between gap-1 mt-3 pt-2 border-top border-secondary border-opacity-10 flex-wrap">
            <div class="d-flex align-items-center gap-1">
              ${!isActive ? `
                <button type="button" class="btn btn-xs btn-outline-success btn-make-active" data-id="${c.id}" title="Set as Active Character">
                  <i class="icon-base ti tabler-check me-1"></i>Set Active
                </button>
              ` : `
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 0.72rem;">
                  <i class="icon-base ti tabler-circle-check me-1"></i>Active
                </span>
              `}
            </div>
            <div class="d-flex align-items-center gap-1">
              <a href="{{ url('/valiant-shade/character/sheet') }}?id=${encodeURIComponent(c.id)}" class="btn btn-xs btn-outline-warning" title="Edit Character Sheet">
                <i class="icon-base ti tabler-edit me-1"></i>Edit Sheet
              </a>
              <button type="button" class="btn btn-xs btn-outline-danger btn-delete-char" data-id="${c.id}" title="Delete Character">
                <i class="icon-base ti tabler-trash me-1"></i>Delete
              </button>
            </div>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;

    // Attach card click to select character
    container.querySelectorAll('.character-item-card').forEach((card) => {
      card.addEventListener('click', function (e) {
        if (e.target.closest('button') || e.target.closest('a')) {
          return;
        }
        const id = this.getAttribute('data-id');
        selectCharacter(id);
      });
    });

    // Attach make active
    container.querySelectorAll('.btn-make-active').forEach((btn) => {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const id = this.getAttribute('data-id');
        makeActive(id);
      });
    });

    // Attach delete
    container.querySelectorAll('.btn-delete-char').forEach((btn) => {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const id = this.getAttribute('data-id');
        deleteCharacter(id);
      });
    });
  }

  function makeActive(id) {
    const chars = getCharacters();
    const c = chars.find(ch => ch.id === id || ch.db_id == id);
    if (!c) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('{{ url("/valiant-shade/character/set-active") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ character: c.name })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        let updated = chars.map(ch => ({ ...ch, active: (ch.name === c.name) }));
        saveCharacters(updated);
        selectCharacter(id);
        renderCharacters();
      }
    })
    .catch(e => console.warn(e));
  }

  function deleteCharacter(id) {
    const chars = getCharacters();
    const c = chars.find(ch => ch.id === id || ch.db_id == id) || { id: id, name: 'Character' };

    if (!confirm(`Are you sure you want to permanently delete "${c.name}"? This cannot be undone.`)) {
      return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('{{ url("/valiant-shade/character") }}/' + encodeURIComponent(c.id || id), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      }
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Deleted',
            text: data.message,
            timer: 1200,
            showConfirmButton: false
          }).then(() => window.location.reload());
        } else {
          window.location.reload();
        }
      } else {
        alert(data.message || 'Failed to delete character.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error connecting to server.');
    });
  }



  // ----------------------------------------------------
  // TRANSFER MONEY MODAL LOGIC
  // ----------------------------------------------------
  const transferModalEl = document.getElementById('transferMoneyModal');
  const fromSelect = document.getElementById('transferFromCharSelect');
  const toSelect = document.getElementById('transferToCharSelect');
  const charAmountInput = document.getElementById('transferCharAmountInput');
  const charCurrencySelect = document.getElementById('transferCharCurrencySelect');
  const charNotesInput = document.getElementById('transferCharNotesInput');
  const charPreviewText = document.getElementById('transferCharPreviewText');
  const fromBalanceHint = document.getElementById('transferFromBalanceHint');
  const toBalanceHint = document.getElementById('transferToBalanceHint');
  const needMoreAlert = document.getElementById('transferCharNeedMoreAlert');
  const btnSubmitChar = document.getElementById('btnSubmitCharTransfer');

  const vaultCharSelect = document.getElementById('transferVaultCharSelect');
  const vaultAmountInput = document.getElementById('transferVaultAmountInput');
  const vaultCurrencySelect = document.getElementById('transferVaultCurrencySelect');
  const vaultNotesInput = document.getElementById('transferVaultNotesInput');
  const vaultPreviewText = document.getElementById('transferVaultPreviewText');
  const vaultCharBalanceHint = document.getElementById('transferVaultCharBalanceHint');
  const modalVaultBalanceSp = document.getElementById('modalVaultBalanceSp');
  const modalVaultBalanceGp = document.getElementById('modalVaultBalanceGp');
  const timelineVaultReserveBadge = document.getElementById('timelineVaultReserveBadge');

  let currentVaultSp = {{ (float) ($vault->balance_sp ?? 358989.50) }};

  function updateTransferCharPreview() {
    if (!fromSelect || !toSelect || !charAmountInput) return;
    const chars = getCharacters();
    const fromChar = chars.find(c => c.id === fromSelect.value);
    const toChar = chars.find(c => c.id === toSelect.value);
    const amt = parseFloat(charAmountInput.value) || 0;
    const cur = charCurrencySelect ? charCurrencySelect.value : 'GP';

    if (fromChar && fromBalanceHint) {
      fromBalanceHint.textContent = `{{ __('Current Balance') }}: ${fromChar.gold || 0} GP (${(fromChar.gold || 0) * 10} SP)`;
    }
    if (toChar && toBalanceHint) {
      toBalanceHint.textContent = `{{ __('Current Balance') }}: ${toChar.gold || 0} GP (${(toChar.gold || 0) * 10} SP)`;
    }

    if (!fromChar || !toChar || fromChar.id === toChar.id) {
      if (charPreviewText) charPreviewText.textContent = `{{ __('Please select two different characters') }}`;
      return;
    }

    const fromGold = fromChar.gold || 0;
    const amtInGold = (cur === 'SP') ? amt / 10 : amt;

    if (amt <= 0) {
      if (charPreviewText) charPreviewText.textContent = `{{ __('Enter a valid amount') }}`;
      return;
    }

    if (fromGold < amtInGold) {
      if (charPreviewText) {
        charPreviewText.innerHTML = `<span class="text-danger"><i class="icon-base ti tabler-alert-triangle me-1"></i>{{ __('Insufficient funds!') }} (${fromChar.name} has ${fromGold} GP)</span>`;
      }
      return;
    }

    const fromNewGold = Math.round((fromGold - amtInGold) * 10) / 10;
    const toNewGold = Math.round(((toChar.gold || 0) + amtInGold) * 10) / 10;
    if (charPreviewText) {
      charPreviewText.innerHTML = `
        <span class="text-danger">${fromChar.name}: ${fromNewGold} GP</span>
        <i class="icon-base ti tabler-arrow-right mx-1 text-muted"></i>
        <span class="text-success">${toChar.name}: ${toNewGold} GP</span>
        <span class="badge bg-primary ms-1">${amt} ${cur}</span>
      `;
    }
  }

  function updateTransferVaultPreview() {
    if (!vaultCharSelect || !vaultAmountInput) return;
    const chars = getCharacters();
    const char = chars.find(c => c.id === vaultCharSelect.value);
    const amt = parseFloat(vaultAmountInput.value) || 0;
    const cur = vaultCurrencySelect ? vaultCurrencySelect.value : 'GP';
    const isWithdraw = document.getElementById('optVaultWithdraw')?.checked;

    if (char && vaultCharBalanceHint) {
      vaultCharBalanceHint.textContent = `{{ __('Current Balance') }}: ${char.gold || 0} GP (${(char.gold || 0) * 10} SP)`;
    }

    if (!char) {
      if (vaultPreviewText) vaultPreviewText.textContent = `{{ __('Select character to preview') }}`;
      return;
    }

    const amtInGold = (cur === 'SP') ? amt / 10 : amt;
    const amtInSp = (cur === 'GP') ? amt * 10 : amt;
    const charGold = char.gold || 0;

    if (amt <= 0) {
      if (vaultPreviewText) vaultPreviewText.textContent = `{{ __('Enter a valid amount') }}`;
      return;
    }

    if (isWithdraw) {
      if (currentVaultSp < amtInSp) {
        if (vaultPreviewText) {
          vaultPreviewText.innerHTML = `<span class="text-danger"><i class="icon-base ti tabler-alert-triangle me-1"></i>{{ __('Vault has insufficient funds!') }}</span>`;
        }
        return;
      }
      const newCharGold = Math.round((charGold + amtInGold) * 10) / 10;
      const newVaultSp = Math.round(currentVaultSp - amtInSp);
      if (vaultPreviewText) {
        vaultPreviewText.innerHTML = `
          <span class="text-success">${char.name}: +${amt} ${cur} (&rarr; ${newCharGold} GP)</span> | 
          <span class="text-muted">{{ __('Vault') }}: ${newVaultSp.toLocaleString()} SP</span>
        `;
      }
    } else {
      if (charGold < amtInGold) {
        if (vaultPreviewText) {
          vaultPreviewText.innerHTML = `<span class="text-danger"><i class="icon-base ti tabler-alert-triangle me-1"></i>${char.name} {{ __('has insufficient gold!') }} (${charGold} GP)</span>`;
        }
        return;
      }
      const newCharGold = Math.round((charGold - amtInGold) * 10) / 10;
      const newVaultSp = Math.round(currentVaultSp + amtInSp);
      if (vaultPreviewText) {
        vaultPreviewText.innerHTML = `
          <span class="text-warning">${char.name}: -${amt} ${cur} (&rarr; ${newCharGold} GP)</span> | 
          <span class="text-success">{{ __('Vault') }}: +${amtInSp} SP (&rarr; ${newVaultSp.toLocaleString()} SP)</span>
        `;
      }
    }
  }

  function populateTransferModal() {
    const chars = getCharacters();
    if (!fromSelect || !toSelect || !vaultCharSelect) return;

    fromSelect.innerHTML = '';
    toSelect.innerHTML = '';
    vaultCharSelect.innerHTML = '';

    chars.forEach((c, idx) => {
      const optFrom = document.createElement('option');
      optFrom.value = c.id;
      optFrom.textContent = `${c.name} (${c.class}) - ${c.gold || 0} GP`;
      if (c.id === selectedCharId) optFrom.selected = true;
      fromSelect.appendChild(optFrom);

      const optTo = document.createElement('option');
      optTo.value = c.id;
      optTo.textContent = `${c.name} (${c.class}) - ${c.gold || 0} GP`;
      if (chars.length > 1 && c.id !== selectedCharId && idx === 1) optTo.selected = true;
      toSelect.appendChild(optTo);

      const optVault = document.createElement('option');
      optVault.value = c.id;
      optVault.textContent = `${c.name} (${c.class}) - ${c.gold || 0} GP`;
      if (c.id === selectedCharId) optVault.selected = true;
      vaultCharSelect.appendChild(optVault);
    });

    if (chars.length < 2) {
      if (needMoreAlert) needMoreAlert.classList.remove('d-none');
      if (btnSubmitChar) btnSubmitChar.disabled = true;
    } else {
      if (needMoreAlert) needMoreAlert.classList.add('d-none');
      if (btnSubmitChar) btnSubmitChar.disabled = false;
      if (fromSelect.value === toSelect.value && chars.length > 1) {
        const alt = chars.find(c => c.id !== fromSelect.value);
        if (alt) toSelect.value = alt.id;
      }
    }

    updateTransferCharPreview();
    updateTransferVaultPreview();
  }

  if (transferModalEl) {
    transferModalEl.addEventListener('show.bs.modal', populateTransferModal);
  }

  if (fromSelect) fromSelect.addEventListener('change', updateTransferCharPreview);
  if (toSelect) toSelect.addEventListener('change', updateTransferCharPreview);
  if (charAmountInput) charAmountInput.addEventListener('input', updateTransferCharPreview);
  if (charCurrencySelect) charCurrencySelect.addEventListener('change', updateTransferCharPreview);

  if (vaultCharSelect) vaultCharSelect.addEventListener('change', updateTransferVaultPreview);
  if (vaultAmountInput) vaultAmountInput.addEventListener('input', updateTransferVaultPreview);
  if (vaultCurrencySelect) vaultCurrencySelect.addEventListener('change', updateTransferVaultPreview);
  document.querySelectorAll('input[name="vaultOperationType"]').forEach(r => {
    r.addEventListener('change', updateTransferVaultPreview);
  });

  // Quick Companion Creator Button
  const btnQuickComp = document.getElementById('btnQuickCreateCompanion');
  if (btnQuickComp) {
    btnQuickComp.addEventListener('click', function () {
      const compChar = {
        id: 'char_' + Date.now(),
        name: 'Lyra Shadowmend',
        race: 'Elf',
        raceKey: 'elf',
        class: 'Rogue',
        classKey: 'rogue',
        icon: 'user',
        level: 1,
        hp: 10,
        maxHp: 10,
        hitDie: 'd8',
        gold: 80,
        active: false,
        createdAt: new Date().toLocaleDateString()
      };
      let chars = getCharacters();
      chars.push(compChar);
      saveCharacters(chars);
      renderCharacters();
      populateTransferModal();
      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: '{{ __("Companion Created!") }}',
          text: 'Lyra Shadowmend (Rogue, 80 GP) {{ __("joined your party.") }}',
          timer: 1500,
          showConfirmButton: false
        });
      }
    });
  }

  // Submit Character-to-Character Transfer
  const charTransferForm = document.getElementById('transferBetweenCharactersForm');
  if (charTransferForm) {
    charTransferForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const chars = getCharacters();
      const fromId = fromSelect.value;
      const toId = toSelect.value;
      const amt = parseFloat(charAmountInput.value) || 0;
      const cur = charCurrencySelect.value || 'GP';
      const notes = (charNotesInput.value || '').trim();

      if (fromId === toId) {
        alert('{{ __("Please select two different characters.") }}');
        return;
      }

      const fromChar = chars.find(c => c.id === fromId);
      const toChar = chars.find(c => c.id === toId);
      if (!fromChar || !toChar || amt <= 0) {
        alert('{{ __("Invalid character or amount.") }}');
        return;
      }

      const amtInGold = (cur === 'SP') ? amt / 10 : amt;
      if ((fromChar.gold || 0) < amtInGold) {
        alert('{{ __("Sender character does not have enough gold.") }}');
        return;
      }

      // Process transfer
      fromChar.gold = Math.round(((fromChar.gold || 0) - amtInGold) * 10) / 10;
      toChar.gold = Math.round(((toChar.gold || 0) + amtInGold) * 10) / 10;
      saveCharacters(chars);

      const isTr = '{{ app()->getLocale() }}' === 'tr';

      // Log activity to sender
      const senderTitle = isTr
        ? `${fromChar.name}, ${toChar.name} adlı oyuncuya ${amt} ${cur} gönderdi`
        : `${fromChar.name} sent ${amt} ${cur} to ${toChar.name}`;

      addCharacterActivity(fromChar.id, {
        category: 'transfers',
        point: 'danger',
        icon: 'ti tabler-arrow-up-right',
        title: senderTitle,
        desc: notes ? `"${notes}"` : (isTr ? 'Kahramanlar arası doğrudan altın aktarımı.' : 'Direct gold transfer to fellow hero.'),
        amount: `-${amt} ${cur}`,
        badge: '{{ __("Outgoing Transfer") }}',
        meta: `{{ __("Recipient") }}: ${toChar.name}`
      });

      // Log activity to recipient
      const recipientTitle = isTr
        ? `${fromChar.name} adlı karakter size ${amt} ${cur} para gönderdi`
        : `${fromChar.name} sent you ${amt} ${cur}`;

      addCharacterActivity(toChar.id, {
        category: 'transfers',
        point: 'success',
        icon: 'ti tabler-arrow-down-left',
        title: recipientTitle,
        desc: notes ? `"${notes}"` : (isTr ? 'Grup üyesinden altın transferi alındı.' : 'Received gold transfer from party member.'),
        amount: `+${amt} ${cur}`,
        badge: '{{ __("Incoming Transfer") }}',
        meta: `{{ __("Sender") }}: ${fromChar.name}`
      });

      // Update UI
      renderCharacters();
      const currentSelected = chars.find(c => c.id === selectedCharId) || fromChar;
      renderActivityTimeline(currentSelected);

      const bsModal = bootstrap.Modal.getInstance(transferModalEl);
      if (bsModal) bsModal.hide();
      charTransferForm.reset();

      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: '{{ __("Transfer Completed!") }}',
          html: `<strong>${amt} ${cur}</strong> {{ __("successfully transferred from") }} <b>${fromChar.name}</b> {{ __("to") }} <b>${toChar.name}</b>.`,
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  }

  // Submit Vault Transfer
  const vaultTransferForm = document.getElementById('transferVaultForm');
  if (vaultTransferForm) {
    vaultTransferForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const chars = getCharacters();
      const charId = vaultCharSelect.value;
      const char = chars.find(c => c.id === charId);
      const isWithdraw = document.getElementById('optVaultWithdraw')?.checked;
      const operation = isWithdraw ? 'withdraw' : 'deposit';
      const amt = parseFloat(vaultAmountInput.value) || 0;
      const cur = vaultCurrencySelect.value || 'GP';
      const notes = (vaultNotesInput.value || '').trim();

      if (!char || amt <= 0) {
        alert('{{ __("Invalid character or amount.") }}');
        return;
      }

      const amtInGold = (cur === 'SP') ? amt / 10 : amt;
      const amtInSp = (cur === 'GP') ? amt * 10 : amt;

      if (!isWithdraw && (char.gold || 0) < amtInGold) {
        alert('{{ __("Character does not have enough gold to deposit into the vault.") }}');
        return;
      }

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const submitBtn = document.getElementById('btnSubmitVaultTransfer');
      if (submitBtn) submitBtn.disabled = true;

      fetch('{{ route("valiant-shade.pages-profile-user.vault-transfer") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          operation: operation,
          amount_sp: amtInSp,
          character_name: char.name,
          notes: notes
        })
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (!data.success) {
          alert(data.message || '{{ __("Vault transaction failed.") }}');
          return;
        }

        // Update local vault balance
        currentVaultSp = data.vault.balance_sp;
        if (modalVaultBalanceSp) modalVaultBalanceSp.textContent = `${Math.round(currentVaultSp).toLocaleString()} SP`;
        if (modalVaultBalanceGp) modalVaultBalanceGp.textContent = `~${(Math.round(currentVaultSp) / 10).toLocaleString()} GP`;
        if (timelineVaultReserveBadge) timelineVaultReserveBadge.textContent = `${Math.round(currentVaultSp).toLocaleString()} SP`;

        // Update character gold
        if (isWithdraw) {
          char.gold = Math.round(((char.gold || 0) + amtInGold) * 10) / 10;
          addCharacterActivity(char.id, {
            category: 'vault',
            point: 'info',
            icon: 'ti tabler-building-bank',
            title: '{{ __("Withdrawn from Guild Vault") }}',
            desc: notes ? `"${notes}"` : '{{ __("Guild treasury expedition fund advance.") }}',
            amount: `+${amt} ${cur}`,
            badge: '{{ __("Guild Vault") }}',
            meta: `{{ __("Vault Reserve") }}: ${Math.round(currentVaultSp).toLocaleString()} SP`
          });
        } else {
          char.gold = Math.round(((char.gold || 0) - amtInGold) * 10) / 10;
          addCharacterActivity(char.id, {
            category: 'vault',
            point: 'warning',
            icon: 'ti tabler-vault',
            title: '{{ __("Deposited to Guild Vault") }}',
            desc: notes ? `"${notes}"` : '{{ __("Contribution to guild treasury reserves.") }}',
            amount: `-${amt} ${cur}`,
            badge: '{{ __("Vault Deposit") }}',
            meta: `{{ __("Vault Reserve") }}: ${Math.round(currentVaultSp).toLocaleString()} SP`
          });
        }

        saveCharacters(chars);
        renderCharacters();
        const currentSelected = chars.find(c => c.id === selectedCharId) || char;
        renderActivityTimeline(currentSelected);

        const bsModal = bootstrap.Modal.getInstance(transferModalEl);
        if (bsModal) bsModal.hide();
        vaultTransferForm.reset();

        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Vault Transaction Successful!") }}',
            html: isWithdraw
              ? `<strong>${amt} ${cur}</strong> {{ __("withdrawn from Guild Vault to") }} <b>${char.name}</b>.`
              : `<strong>${amt} ${cur}</strong> {{ __("deposited from") }} <b>${char.name}</b> {{ __("into Guild Vault") }}.`,
            timer: 2000,
            showConfirmButton: false
          });
        }
      })
      .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        console.error('Vault transfer error:', err);
        alert('{{ __("Server communication error occurred.") }}');
      });
    });
  }

  // Initial render
  renderCharacters();

  const allChars = getCharacters();
  if (allChars.length > 0) {
    const initChar = allChars.find(c => c.active) || allChars[0];
    selectedCharId = initChar.id;
    const initClass = normalizeClassKey(initChar);
    renderClassProgressionTable(initClass, initChar);
    renderRaceDetails(initChar);
    renderActivityTimeline(initChar);
  } else {
    selectedCharId = null;
    renderEmptyProgressionState();
    renderActivityTimeline(null);
  }

  // Listen for new characters from modal
  window.addEventListener('characterCreated', function (e) {
    const created = e.detail;
    renderCharacters();
    if (created && created.id) {
      selectCharacter(created.id);
    }
  });
});
</script>
@endsection