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
              <!-- Discord-style User Roles (Ordered from Highest Authority to Least) -->
              @php
                $profileUser = Auth::user() ?? ($user ?? null);
                $profileRoles = $profileUser ? $profileUser->ordered_roles : collect();
              @endphp
              <div class="d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2 my-2" id="userRolesContainer">
                @forelse($profileRoles as $role)
                  @php
                    $roleColor = $role->color ?: '#7367f0';
                    $roleIcon = $role->icon ?: 'ti tabler-shield';
                  @endphp
                  <span class="badge rounded-pill d-inline-flex align-items-center gap-1_5 px-3 py-1_5 shadow-xs"
                        style="background-color: {{ $roleColor }}20; color: {{ $roleColor }}; border: 1px solid {{ $roleColor }}55; font-size: 0.82rem; font-weight: 600; letter-spacing: 0.3px;"
                        title="{{ $role->description ? __($role->description) : '' }} ({{ __('Authority') }}: {{ $role->priority }})">
                    <span class="badge-dot-indicator rounded-circle" style="width: 8px; height: 8px; background-color: {{ $roleColor }}; display: inline-block;"></span>
                    <span>{{ __($role->name) }}</span>
                  </span>
                @empty
                  <span class="badge rounded-pill bg-label-secondary d-inline-flex align-items-center gap-1_5 px-3 py-1">
                    <span class="badge-dot-indicator rounded-circle" style="width: 8px; height: 8px; background-color: #82868b; display: inline-block;"></span>
                    <span>{{ __('Adventurer') }}</span>
                  </span>
                @endforelse
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!--/ Header -->

<!-- Navbar pills -->
<div class="row mb-4">
  <div class="col-12">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <ul class="nav nav-pills flex-wrap gap-2 mb-0" role="tablist" id="profilePageTabList">
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link active d-flex align-items-center gap-1_5 px-3 py-2" role="tab" data-bs-toggle="tab" data-bs-target="#tab-profile" aria-controls="tab-profile" aria-selected="true" id="tab-profile-btn">
            <i class="icon-base ti tabler-user-check icon-sm"></i>
            <span class="fw-semibold">{{ __('Profile & Hero Sheet') }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link d-flex align-items-center gap-1_5 px-3 py-2" role="tab" data-bs-toggle="tab" data-bs-target="#tab-past-characters" aria-controls="tab-past-characters" aria-selected="false" id="tab-past-characters-btn">
            <i class="icon-base ti tabler-grave icon-sm text-danger"></i>
            <span class="fw-semibold">{{ __('Fallen & Retired Heroes') }}</span>
            <span class="badge rounded-pill bg-label-danger ms-1 px-2 py-0_5 font-monospace">{{ count($pastCharacters ?? []) }}</span>
          </button>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        @if(count($characters ?? []) < 2)
          <button type="button" class="btn btn-warning waves-effect waves-light d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#claimCharacterModal">
            <i class="icon-base ti tabler-user-check icon-sm me-1_5"></i> {{ __('Claim Character') }}
          </button>
          <button type="button" class="btn btn-outline-primary waves-effect d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
            <i class="icon-base ti tabler-user-plus icon-sm me-1_5"></i> {{ __('Create New') }}
          </button>
        @else
          <button type="button" class="btn btn-secondary waves-effect d-flex align-items-center disabled" disabled title="{{ __('Maximum 2 characters reached (1 Main and 1 Clerk)') }}">
            <i class="icon-base ti tabler-lock icon-sm me-1_5"></i> {{ __('Character Limit Reached (2/2)') }}
          </button>
        @endif
      </div>
    </div>
  </div>
</div>
<!--/ Navbar pills -->

<!-- Tab Content Container -->
<div class="tab-content p-0 border-0 shadow-none bg-transparent">
  <!-- Tab 1: Profile & Hero Sheet -->
  <div class="tab-pane fade show active" id="tab-profile" role="tabpanel" aria-labelledby="tab-profile-btn">
    <!-- User Profile Content -->
    <div class="row">
      <div class="col-xl-4 col-lg-5 col-md-5">
        <!-- Player Characters Card -->
        <div class="card mb-6" id="playerCharactersCard">
          <div class="card-header d-flex align-items-center justify-content-between pb-3 border-bottom">
            <div class="d-flex align-items-center min-w-0 me-2">
              <div class="avatar avatar-sm bg-label-primary rounded me-2 flex-shrink-0 d-flex align-items-center justify-content-center">
                <i class="icon-base ti tabler-swords text-primary"></i>
              </div>
              <div class="overflow-hidden">
                <h5 class="card-title mb-0 fw-bold text-nowrap">{{ __('My Characters') }}</h5>
                <small class="text-body-secondary text-nowrap">{{ Auth::user()->name ?? 'Player' }} &bull; {{ __('Active Heroes') }}</small>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <span class="badge bg-label-primary rounded-pill font-monospace" id="charListCountBadge">{{ count($characters ?? []) }}/2</span>
              @if(count($characters ?? []) < 2)
                <div class="dropdown">
                  <button class="btn btn-sm btn-icon btn-primary dropdown-toggle hide-arrow waves-effect shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Add / Claim Hero') }}">
                    <i class="icon-base ti tabler-plus"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#claimCharacterModal">
                        <i class="icon-base ti tabler-user-check text-warning icon-sm"></i>
                        <span>{{ __('Claim Character') }}</span>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
                        <i class="icon-base ti tabler-user-plus text-primary icon-sm"></i>
                        <span>{{ __('Create New') }}</span>
                      </a>
                    </li>
                  </ul>
                </div>
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
                $sData = $c->sheet_data['sheetData'] ?? $c->sheet_data ?? [];
                $gold = $sData['moneyGp'] ?? ($c->sheet_data['money']['gp'] ?? 150);
                $cTr = $sData['traits'] ?? ($c->sheet_data['traits'] ?? []);
                $tCnt = 0;
                if (!empty($cTr['uye']['name'])) $tCnt++;
                if (!empty($cTr['kidemli']['name'])) $tCnt++;
                if (!empty($cTr['uzman']['name'])) $tCnt++;
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
                        @if($tCnt > 0)
                          <span class="badge bg-label-warning px-2 py-0_5" style="font-size: 0.7rem;" title="{{ __('Rank Traits') }}"><i class="icon-base ti tabler-sparkles icon-xs me-1"></i>{{ $tCnt }}/3 Trait</span>
                        @endif
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

        @if(count($characters ?? []) < 2)
          <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top border-secondary border-opacity-10">
            <button type="button" class="btn btn-sm btn-outline-warning w-50 waves-effect d-flex align-items-center justify-content-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#claimCharacterModal">
              <i class="icon-base ti tabler-user-check icon-xs"></i>
              <span>{{ __('Claim Character') }}</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary w-50 waves-effect d-flex align-items-center justify-content-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
              <i class="icon-base ti tabler-plus icon-xs"></i>
              <span>{{ __('Create New') }}</span>
            </button>
          </div>
        @endif
      </div>
    </div>
    <!--/ Player Characters Card -->
  </div>
  <div class="col-xl-8 col-lg-7 col-md-7">
    <!-- Activity Timeline & Class Progression Card -->
    <div class="card card-action mb-6" id="timelineAndProgressionCard">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3 py-3 px-4 border-bottom border-secondary border-opacity-10">
        <ul class="nav nav-pills custom-profile-tabs gap-2 mb-0" role="tablist">
          <li class="nav-item">
            <button type="button" class="nav-link active d-flex align-items-center gap-2 py-2 px-3 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-timeline" aria-controls="navs-timeline" aria-selected="true" id="tab-btn-timeline">
              <i class="icon-base ti tabler-history icon-md"></i>
              <span>{{ __('Activity Timeline') }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-progression" aria-controls="navs-progression" aria-selected="false" id="tab-btn-progression">
              <i class="icon-base ti tabler-swords icon-md"></i>
              <span id="tabProgressionTitle">{{ __('Class') }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-race" aria-controls="navs-race" aria-selected="false" id="tab-btn-race">
              <i class="icon-base ti tabler-dna icon-md"></i>
              <span id="tabRaceTitle">{{ __('Race') }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link d-flex align-items-center gap-2 py-2 px-3 fw-semibold" role="tab" data-bs-toggle="tab" data-bs-target="#navs-traits" aria-controls="navs-traits" aria-selected="false" id="tab-btn-traits">
              <i class="icon-base ti tabler-sparkles icon-md text-warning"></i>
              <span id="tabTraitsTitle">{{ __('Traits') }}</span>
            </button>
          </li>
        </ul>
        <div class="card-action-element d-flex align-items-center gap-2">
          <span class="badge bg-label-primary px-3 py-2 font-monospace d-flex align-items-center gap-1_5" id="timelineActiveCharBadge" style="font-size: 0.78rem;">
            <i class="icon-base ti tabler-user icon-xs"></i>
            <span id="timelineActiveCharName">{{ __('No Hero Selected') }}</span>
          </span>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="tab-content p-0">
          <!-- Pane 1: Activity Timeline (Character Financial & Events Ledger) -->
          <div class="tab-pane fade show active" id="navs-timeline" role="tabpanel">

            <!-- Filter Toolbar & Event Counter -->
            <div class="timeline-toolbar d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 p-2_5 rounded-3" id="timelineToolbarContainer">
              <div class="timeline-filter-bar d-inline-flex align-items-center" id="timelineFilterGroup">
                <button type="button" class="timeline-filter-btn active" data-filter="all">
                  <i class="icon-base ti tabler-list me-1"></i> {{ __('All Activities') }}
                </button>
                <button type="button" class="timeline-filter-btn" data-filter="transfers">
                  <i class="icon-base ti tabler-arrows-exchange me-1"></i> {{ __('Transfers') }}
                </button>
                <button type="button" class="timeline-filter-btn" data-filter="vault">
                  <i class="icon-base ti tabler-building-bank me-1"></i> {{ __('Guild Vault') }}
                </button>
                <button type="button" class="timeline-filter-btn" data-filter="milestone">
                  <i class="icon-base ti tabler-sparkles me-1"></i> {{ __('Quests & Deeds') }}
                </button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-secondary font-monospace px-3 py-2 border border-secondary border-opacity-25 d-flex align-items-center gap-1" id="timelineActivityCountBadge">
                  <i class="icon-base ti tabler-activity icon-xs text-primary"></i>
                  <span>0 {{ __('Events') }}</span>
                </span>
                <button type="button" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 px-3 py-2 waves-effect" data-bs-toggle="modal" data-bs-target="#transferMoneyModal" id="btnQuickTransferMoney">
                  <i class="icon-base ti tabler-arrows-exchange icon-sm"></i>
                  <span>{{ __('Transfer Money') }}</span>
                </button>
              </div>
            </div>

            <!-- Dynamic Timeline List -->
            <ul class="timeline mb-0 ps-2" id="characterActivityTimelineList">
              <!-- Dynamically populated via JS -->
            </ul>

            <!-- Empty State -->
            <div id="timelineEmptyState" class="text-center py-5 d-none">
              <div class="timeline-empty-icon-wrap mx-auto mb-3 shadow-lg">
                <i class="icon-base ti tabler-scroll fs-1 text-primary" id="timelineEmptyStateIcon"></i>
              </div>
              <h5 class="fw-bold mb-2 text-heading" id="timelineEmptyStateTitle">{{ __('Chronicles of Valiant Shade') }}</h5>
              <p class="text-body-secondary small mb-4" id="timelineEmptyStateDesc" style="max-width: 440px; margin: 0 auto;">
                {{ __('No hero has stepped forward yet. Create your first character to begin recording quest deeds, milestones, and treasury records.') }}
              </p>
              <div id="timelineEmptyStateAction">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
                  <i class="icon-base ti tabler-user-plus fs-5"></i>
                  <span>{{ __('Create First Hero') }}</span>
                </button>
              </div>
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
                  <span class="text-muted">{{ __('Spells:') }}</span>
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

          <!-- Pane 4: Character Traits (Rank Traits: Member, Senior, Expert) -->
          <div class="tab-pane fade" id="navs-traits" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-3 mb-4 rounded-3 border border-warning border-opacity-25 bg-label-warning bg-opacity-10 shadow-sm" id="traitsHeaderCard">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md bg-warning text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                  <i class="icon-base ti tabler-sparkles fs-4"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="mb-0 fw-bold text-heading" id="traitsCharName">{{ __('Character Name') }}</h5>
                    <span class="badge bg-label-warning font-monospace">{{ __('Rank Traits (3 Slots)') }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mt-1 flex-wrap text-body-secondary" style="font-size: 0.82rem;">
                    <span>{{ __('Special guild traits unlocked at Member, Senior and Expert ranks.') }}</span>
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2">
                <a href="#" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 px-3 py-2 waves-effect" id="btnEditTraitsInSheet">
                  <i class="icon-base ti tabler-edit icon-sm"></i>
                  <span>{{ __('Edit in Character Sheet') }}</span>
                </a>
              </div>
            </div>

            <!-- Dynamic Traits Cards Row -->
            <div class="row g-3" id="traitsContentCardsRow">
              <!-- Populated dynamically via renderTraitsTab -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--/ Activity Timeline & Class Progression Card -->
  </div>
</div>
<!--/ User Profile Content -->
  </div>
  <!--/ Tab 1: Profile & Hero Sheet -->

  <!-- Tab 2: Fallen & Retired Heroes -->
  <div class="tab-pane fade" id="tab-past-characters" role="tabpanel" aria-labelledby="tab-past-characters-btn">
    <!-- Memorial / Header Banner -->
    <div class="card mb-6 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(23, 25, 45, 0.98) 0%, rgba(38, 22, 30, 0.95) 50%, rgba(18, 19, 32, 0.98) 100%); border: 1px solid rgba(234, 84, 85, 0.35) !important; border-radius: 12px;">
      <div class="card-body p-4">
        <div class="row align-items-center g-3">
          <div class="col-lg-6 col-12">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-lg rounded-3 d-flex align-items-center justify-content-center shadow" style="width: 52px; height: 52px; background: linear-gradient(135deg, #ea5455 0%, #b02a37 100%);">
                <i class="icon-base ti tabler-grave fs-2 text-white"></i>
              </div>
              <div>
                <h4 class="mb-1 text-white fw-bold d-flex align-items-center gap-2">
                  <span>{{ __('Fallen & Retired Heroes') }}</span>
                  <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 font-monospace fs-6 px-2 py-0_5">
                    {{ count($pastCharacters ?? []) }} {{ __('Characters') }}
                  </span>
                </h4>
                <p class="mb-0 text-body-secondary" style="font-size: 0.88rem;">
                  {{ __('Archive of all past heroes fallen in battle (KIA), missing in action (MIA), or retired from guild adventures.') }}
                </p>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-12">
            <div class="row g-2 justify-content-lg-end">
              <div class="col-auto">
                <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-25 text-center">
                  <div class="text-body-secondary small fw-medium">{{ __('Toplam') }}</div>
                  <div class="h5 mb-0 fw-bold text-primary">{{ count($pastCharacters ?? []) }}</div>
                </div>
              </div>
              <div class="col-auto">
                <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-60 border border-danger border-opacity-25 text-center">
                  <div class="text-body-secondary small fw-medium">{{ __('(KIA)') }}</div>
                  <div class="h5 mb-0 fw-bold text-danger">{{ $pastCharacters->where('status', 'KIA')->count() }}</div>
                </div>
              </div>
              <div class="col-auto">
                <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-60 border border-warning border-opacity-25 text-center">
                  <div class="text-body-secondary small fw-medium">{{ __('(MIA)') }}</div>
                  <div class="h5 mb-0 fw-bold text-warning">{{ $pastCharacters->where('status', 'MIA')->count() }}</div>
                </div>
              </div>
              <div class="col-auto">
                <div class="p-2 px-3 rounded-3 bg-dark bg-opacity-60 border border-info border-opacity-25 text-center">
                  <div class="text-body-secondary small fw-medium">{{ __('Emekli') }}</div>
                  <div class="h5 mb-0 fw-bold text-info">{{ $pastCharacters->whereIn('status', ['INACTIVE', 'TRAVELER'])->count() }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card mb-6 border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="row g-3 align-items-center">
          <!-- Search Input -->
          <div class="col-lg-5 col-md-6 col-12">
            <div class="input-group input-group-merge shadow-sm">
              <span class="input-group-text"><i class="icon-base ti tabler-search text-secondary"></i></span>
              <input type="text" id="pastCharsSearchInput" class="form-control" placeholder="{{ __('Search past heroes...') }}" autocomplete="off" />
              <button class="btn btn-outline-secondary d-none" type="button" id="btnClearPastSearch"><i class="icon-base ti tabler-x"></i></button>
            </div>
          </div>

          <!-- Status Filter (No Emojis) -->
          <div class="col-lg-3 col-md-3 col-6">
            <select id="pastCharsStatusFilter" class="form-select shadow-sm">
              <option value="">{{ __('All Statuses') }}</option>
              <option value="kia">(KIA)</option>
              <option value="mia">(MIA)</option>
              <option value="inactive">{{ __('Retired (Inactive)') }}</option>
              <option value="traveler">{{ __('Wanderer') }}</option>
            </select>
          </div>

          <!-- Type Filter -->
          <div class="col-lg-2 col-md-3 col-6">
            <select id="pastCharsTypeFilter" class="form-select shadow-sm">
              <option value="">{{ __('All Types') }}</option>
              <option value="main">Main</option>
              <option value="clerk">Clerk</option>
            </select>
          </div>

          <!-- View Switch & Reset -->
          <div class="col-lg-2 col-md-12 col-12 d-flex align-items-center justify-content-end gap-2">
            <div class="btn-group shadow-sm" role="group" aria-label="{{ __('View') }}">
              <button type="button" class="btn btn-outline-primary active btn-icon waves-effect" id="btnPastCharsGridView" title="{{ __('Grid View') }}">
                <i class="icon-base ti tabler-layout-grid"></i>
              </button>
              <button type="button" class="btn btn-outline-primary btn-icon waves-effect" id="btnPastCharsTableView" title="{{ __('Table View') }}">
                <i class="icon-base ti tabler-table"></i>
              </button>
            </div>
            <button type="button" class="btn btn-label-secondary btn-icon waves-effect shadow-sm" id="btnPastCharsResetFilters" title="{{ __('Reset Filters') }}">
              <i class="icon-base ti tabler-refresh"></i>
            </button>
          </div>
        </div>

        <!-- Filter Count Summary -->
        <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top text-body-secondary small">
          <span id="pastCharsFilterSummary" class="fw-semibold">
            {{ __('Total :count past heroes listed', ['count' => count($pastCharacters ?? [])]) }}
          </span>
          <span class="text-body-secondary">
            <i class="icon-base ti tabler-info-circle icon-xs me-1"></i> {{ __('Only fallen, missing, or retired heroes belonging to your account are displayed.') }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div class="card border-0 shadow-sm {{ count($pastCharacters ?? []) > 0 ? 'd-none' : '' }} text-center p-5 mb-6" id="pastCharsEmptyState">
      <div class="py-5">
        <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
          <i class="icon-base ti tabler-shield-heart fs-1 text-secondary"></i>
        </div>
        <h5 class="fw-bold mb-1">{{ __('No Fallen or Retired Characters Found') }}</h5>
        <p class="text-body-secondary mb-3">{{ __('Currently, all your characters are active or no results matched your search filter.') }}</p>
        <button type="button" class="btn btn-outline-primary waves-effect" id="btnEmptyResetPastFilters">
          <i class="icon-base ti tabler-refresh me-1"></i> {{ __('Clear Filters') }}
        </button>
      </div>
    </div>

    <!-- Grid View Container (3 Cards per row on desktop for spacious, uncrowded layout) -->
    <div class="row g-4 mb-6" id="pastCharsGridContainer">
      @foreach($pastCharacters as $c)
        @php
          $qCount = $questCounts[$c->id] ?? 0;
          $prof = $profilesByName->get($c->name);
          $cClass = ($prof?->class_name && $prof->class_name !== 'Maceracı') ? $prof->class_name : null;
          $cRace = $prof?->race ?: 'Human';
          $statusUpper = strtoupper($c->status ?: 'KIA');
          
          $statusBadgeClass = match($statusUpper) {
            'KIA' => 'bg-label-danger border border-danger border-opacity-25',
            'MIA' => 'bg-label-warning border border-warning border-opacity-25',
            'INACTIVE' => 'bg-label-secondary border border-secondary border-opacity-25',
            'TRAVELER' => 'bg-label-info border border-info border-opacity-25',
            default => 'bg-label-primary',
          };
          $statusLabel = match($statusUpper) {
            'KIA' => '(KIA)',
            'MIA' => '(MIA)',
            'INACTIVE' => 'Emekli',
            'TRAVELER' => 'Gezgin',
            default => $statusUpper,
          };
          $statusIcon = match($statusUpper) {
            'KIA' => 'ti tabler-skull text-danger',
            'MIA' => 'ti tabler-help text-warning',
            'INACTIVE' => 'ti tabler-shield-check text-secondary',
            'TRAVELER' => 'ti tabler-compass text-info',
            default => 'ti tabler-user',
          };
        @endphp
        <div class="col-xxl-4 col-xl-4 col-md-6 col-12 past-char-card-item"
             data-name="{{ strtolower($c->name) }}"
             data-status="{{ strtolower($statusUpper) }}"
             data-type="{{ strtolower($c->character_type ?: 'main') }}"
             data-id="{{ $c->id }}"
             data-level="{{ (int)($c->current_level ?: 1) }}">
          <div class="card h-100 character-directory-card border {{ $statusUpper === 'KIA' ? 'border-danger border-opacity-25' : 'border-secondary border-opacity-25' }} shadow-sm">
            <div class="card-body p-4 d-flex flex-column">

              <!-- Top Row: Avatar & Level on left, Status on right -->
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center"
                       style="background: {{ $statusUpper === 'KIA' ? 'rgba(234, 84, 85, 0.16)' : 'rgba(115, 103, 240, 0.16)' }}; border: 1px solid {{ $statusUpper === 'KIA' ? 'rgba(234, 84, 85, 0.4)' : 'rgba(115, 103, 240, 0.3)' }};">
                    <i class="icon-base {{ $statusIcon }} fs-6"></i>
                  </div>
                  <span class="badge bg-primary fs-6 fw-bold px-2 py-0_5 shadow-sm">
                    Lv. {{ $c->current_level ?: 1 }}
                  </span>
                </div>
                <span class="badge {{ $statusBadgeClass }} font-monospace fw-bold px-2_5 py-1" style="font-size: 0.75rem;">
                  {{ $statusLabel }}
                </span>
              </div>

              <!-- Full-Width Character Name (Accommodates Long Names without cut-off) -->
              <div class="mb-3">
                <h5 class="card-title mb-1 fw-bold text-heading" style="word-break: break-word; line-height: 1.35; font-size: 1.15rem;" title="{{ $c->name }}">
                  @if($prof)
                    <a href="{{ route('valiant-shade.character-sheet', ['name' => $c->name]) }}" class="text-heading text-hover-primary text-decoration-none">
                      {{ $c->name }}
                    </a>
                  @else
                    {{ $c->name }}
                  @endif
                </h5>
                <small class="text-body-secondary font-monospace" style="font-size: 0.76rem;">{{ $cClass ? ($cClass . ' • ' . $cRace) : $cRace }}</small>
              </div>

              <!-- XP & Quest Metrics Strip (Clean 3-metric overview) -->
              <div class="p-2_5 rounded-3 bg-label-secondary bg-opacity-10 border border-secondary border-opacity-10 mb-3">
                <div class="row g-2 text-center small">
                  <div class="col-4">
                    <div class="text-body-secondary fw-semibold" style="font-size: 0.68rem;">{{ __('TOTAL XP') }}</div>
                    <div class="fw-bold text-warning font-monospace">{{ number_format($c->total_xp, 0) }}</div>
                  </div>
                  <div class="col-4 border-start">
                    <div class="text-body-secondary fw-semibold" style="font-size: 0.68rem;">{{ __('QUEST XP') }}</div>
                    <div class="fw-bold text-primary font-monospace">{{ number_format($c->quest_xp ?? 0, 0) }}</div>
                  </div>
                  <div class="col-4 border-start">
                    <div class="text-body-secondary fw-semibold" style="font-size: 0.68rem;">{{ __('QUESTS') }}</div>
                    <div class="fw-bold text-info font-monospace">{{ $qCount }} {{ __('Quests') }}</div>
                  </div>
                </div>
              </div>

              <!-- Footer with Sheet Link or XP Record -->
              <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between gap-2">
                @if($prof)
                  <a href="{{ route('valiant-shade.character-sheet', ['name' => $c->name]) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 w-100 justify-content-center waves-effect shadow-sm">
                    <i class="icon-base ti tabler-file-description icon-xs"></i>
                    <span>{{ __('Character Sheet') }}</span>
                  </a>
                @else
                  <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 w-100 justify-content-center waves-effect" disabled title="{{ __('This hero is recorded in guild XP registry') }}">
                    <i class="icon-base ti tabler-award icon-xs"></i>
                    <span>{{ __('XP Record • :xp XP', ['xp' => number_format($c->total_xp, 0)]) }}</span>
                  </button>
                @endif
              </div>

            </div>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Table View Container (hidden by default) -->
    <div class="card border-0 shadow-sm d-none mb-6" id="pastCharsTableContainer">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th>{{ __('Hero Name') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Level') }}</th>
              <th>{{ __('Type') }}</th>
              <th>{{ __('Quests Attended') }}</th>
              <th>{{ __('Quest XP') }}</th>
              <th>{{ __('Total XP') }}</th>
              <th class="text-end">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0" id="pastCharsTableBody">
            @foreach($pastCharacters as $c)
              @php
                $qCount = $questCounts[$c->id] ?? 0;
                $prof = $profilesByName->get($c->name);
                $statusUpper = strtoupper($c->status ?: 'KIA');
                $statusBadgeClass = match($statusUpper) {
                  'KIA' => 'bg-label-danger',
                  'MIA' => 'bg-label-warning',
                  'INACTIVE' => 'bg-label-secondary',
                  'TRAVELER' => 'bg-label-info',
                  default => 'bg-label-primary',
                };
                $statusLabel = match($statusUpper) {
                  'KIA' => '(KIA)',
                  'MIA' => '(MIA)',
                  'INACTIVE' => 'Emekli',
                  'TRAVELER' => 'Gezgin',
                  default => $statusUpper,
                };
                $statusIcon = match($statusUpper) {
                  'KIA' => 'ti tabler-skull text-danger',
                  'MIA' => 'ti tabler-help text-warning',
                  'INACTIVE' => 'ti tabler-shield-check text-secondary',
                  'TRAVELER' => 'ti tabler-compass text-info',
                  default => 'ti tabler-user',
                };
              @endphp
              <tr class="past-char-table-row"
                  data-name="{{ strtolower($c->name) }}"
                  data-status="{{ strtolower($statusUpper) }}"
                  data-type="{{ strtolower($c->character_type ?: 'main') }}"
                  data-id="{{ $c->id }}"
                  data-level="{{ (int)($c->current_level ?: 1) }}">
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-sm rounded-circle bg-label-secondary d-flex align-items-center justify-content-center">
                      <i class="icon-base {{ $statusIcon }}"></i>
                    </div>
                    <div>
                      <div class="fw-bold text-heading">
                        @if($prof)
                          <a href="{{ route('valiant-shade.character-sheet', ['name' => $c->name]) }}" class="text-heading text-hover-primary text-decoration-none">
                            {{ $c->name }}
                          </a>
                        @else
                          {{ $c->name }}
                        @endif
                      </div>
                      <small class="text-body-secondary">{{ Auth::user()->name ?? 'Player' }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge {{ $statusBadgeClass }} font-monospace">
                    {{ $statusLabel }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-label-primary font-monospace fw-bold">Lv. {{ $c->current_level ?: 1 }}</span>
                </td>
                <td>
                  <span class="badge bg-label-info font-monospace">
                    {{ $c->character_type ?: 'MAIN' }}
                  </span>
                </td>
                <td>
                  <span class="text-info fw-bold font-monospace">{{ $qCount }}</span> <small class="text-body-secondary">{{ __('Quests') }}</small>
                </td>
                <td>
                  <span class="text-heading fw-bold font-monospace">{{ number_format($c->quest_xp ?? 0, 0) }}</span>
                </td>
                <td>
                  <span class="text-warning fw-bold font-monospace">{{ number_format($c->total_xp, 0) }}</span> <small class="text-body-secondary">XP</small>
                </td>
                <td class="text-end">
                  @if($prof)
                    <a href="{{ route('valiant-shade.character-sheet', ['name' => $c->name]) }}" class="btn btn-sm btn-icon btn-outline-primary waves-effect" title="{{ __('Character Sheet') }}">
                      <i class="icon-base ti tabler-file-description"></i>
                    </a>
                  @else
                    <span class="badge bg-label-secondary font-monospace" title="{{ __('XP Registry') }}">
                      <i class="icon-base ti tabler-award icon-xs"></i>
                    </span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!--/ Tab 2: Fallen & Retired Heroes -->
</div>
<!--/ Tab Content Container -->

@include('valiant-shade._partials._modals.modal-create-character')
@include('valiant-shade._partials._modals.modal-claim-character')
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
  /* Custom Profile & Timeline Segmented Nav Pills */
  .custom-profile-tabs {
    border-bottom: none !important;
  }
  .custom-profile-tabs .nav-link {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 8px !important;
    color: #9ba0bc !important;
    background: rgba(255, 255, 255, 0.03) !important;
    font-size: 0.88rem !important;
    font-weight: 600 !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    padding: 0.52rem 1.05rem !important;
  }
  .custom-profile-tabs .nav-link:hover {
    color: #ffffff !important;
    background: rgba(115, 103, 240, 0.12) !important;
    border-color: rgba(115, 103, 240, 0.35) !important;
    transform: translateY(-1px);
  }
  .custom-profile-tabs .nav-link.active {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    border-color: rgba(115, 103, 240, 0.6) !important;
    box-shadow: 0 4px 14px rgba(115, 103, 240, 0.38) !important;
  }

  /* Timeline Toolbar & Filter Bar */
  .timeline-toolbar {
    background: rgba(23, 25, 45, 0.6) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    backdrop-filter: blur(8px);
  }
  .timeline-filter-bar {
    background: rgba(15, 17, 30, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 3px;
    gap: 3px;
  }
  .timeline-filter-btn {
    background: transparent;
    color: #9ba0bc;
    border: none;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.42rem 0.82rem;
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

  /* Timeline Empty State Styling */
  .timeline-empty-icon-wrap {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(115, 103, 240, 0.22) 0%, rgba(115, 103, 240, 0.04) 70%, transparent 100%);
    border: 1px solid rgba(115, 103, 240, 0.35);
    box-shadow: 0 0 24px rgba(115, 103, 240, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
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

  /* All Characters Directory Styles */
  .character-directory-card {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    background: rgba(30, 32, 54, 0.65) !important;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 12px;
  }
  .character-directory-card:hover {
    transform: translateY(-4px);
    border-color: rgba(115, 103, 240, 0.45) !important;
    box-shadow: 0 12px 28px -5px rgba(0, 0, 0, 0.45), 0 0 15px rgba(115, 103, 240, 0.2) !important;
  }
  .all-char-table-row {
    transition: background-color 0.15s ease;
  }
  .all-char-table-row:hover {
    background-color: rgba(115, 103, 240, 0.08) !important;
  }
</style>

<script id="serverCharactersData" type="application/json">
  {!! json_encode($charactersData ?? []) !!}
</script>

<script>
window.assetsPath = window.assetsPath || document.documentElement.getAttribute('data-assets-path') || '/static/valiant-shade/assets/';
var assetsPath = window.assetsPath;

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

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

  function renderTraitsTab(char) {
    const tabTraitsTitle = document.getElementById('tabTraitsTitle');
    const nameEl = document.getElementById('traitsCharName');
    const editBtn = document.getElementById('btnEditTraitsInSheet');
    const container = document.getElementById('traitsContentCardsRow');
    if (!container) return;

    if (!char) {
      if (tabTraitsTitle) tabTraitsTitle.textContent = 'Traits';
      if (nameEl) nameEl.textContent = '{{ __("No Hero Selected") }}';
      if (editBtn) editBtn.classList.add('d-none');
      container.innerHTML = `
        <div class="col-12 text-center py-5">
          <i class="icon-base ti tabler-sparkles text-muted fs-1 mb-2 d-block opacity-50"></i>
          <h6 class="text-heading fw-bold mb-1">{{ __("No Character Selected") }}</h6>
          <p class="text-body-secondary small mb-0">{{ __("Select a character to view traits.") }}</p>
        </div>
      `;
      return;
    }

    if (nameEl) nameEl.textContent = char.name;
    const charSheetUrl = `{{ url('/valiant-shade/character/sheet') }}?id=${encodeURIComponent(char.id || '')}`;
    if (editBtn) {
      editBtn.classList.remove('d-none');
      editBtn.href = charSheetUrl;
    }

    const sDetails = (char.sheetData && char.sheetData.sheetData) ? char.sheetData.sheetData : (char.sheetData || {});
    const traits = sDetails.traits || char.traits || {};

    let filledCount = 0;
    ['uye', 'kidemli', 'uzman'].forEach(k => {
      if (traits[k] && traits[k].name) filledCount++;
    });

    if (tabTraitsTitle) {
      tabTraitsTitle.textContent = filledCount > 0 ? `Traits (${filledCount}/3)` : 'Traits';
    }

    const slots = [
      {
        key: 'uye',
        title: '{{ __("Member Trait") }}',
        rankLabel: '{{ __("Member") }} &bull; Slot 1',
        badgeClass: 'bg-label-info',
        borderStyle: 'border-color: rgba(115, 103, 240, 0.35) !important;',
        icon: 'ti tabler-user-check text-info',
        data: traits.uye || {}
      },
      {
        key: 'kidemli',
        title: '{{ __("Senior Trait") }}',
        rankLabel: '{{ __("Senior") }} &bull; Slot 2',
        badgeClass: 'bg-label-warning',
        borderStyle: 'border-color: rgba(255, 171, 0, 0.35) !important;',
        icon: 'ti tabler-award text-warning',
        data: traits.kidemli || {}
      },
      {
        key: 'uzman',
        title: '{{ __("Expert Trait") }}',
        rankLabel: '{{ __("Expert") }} &bull; Slot 3',
        badgeClass: 'bg-label-success',
        borderStyle: 'border-color: rgba(40, 199, 111, 0.35) !important;',
        icon: 'ti tabler-shield-star text-success',
        data: traits.uzman || {}
      }
    ];

    container.innerHTML = slots.map(s => {
      const name = s.data.name ? escapeHtml(s.data.name) : '';
      const desc = s.data.desc ? escapeHtml(s.data.desc) : '';
      const hasContent = name.length > 0 || desc.length > 0;

      return `
        <div class="col-12 col-md-4">
          <div class="card h-100 p-3 shadow-xs border" style="${s.borderStyle} background: rgba(255, 255, 255, 0.02);">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="badge ${s.badgeClass} font-monospace d-flex align-items-center gap-1 py-1 px-2" style="font-size: 0.72rem;">
                <i class="icon-base ${s.icon} icon-xs"></i> ${s.title}
              </span>
              <small class="text-body-secondary font-monospace" style="font-size: 0.68rem;">${s.rankLabel}</small>
            </div>
            ${hasContent ? `
              <h6 class="fw-bold text-heading mb-1 text-primary">${name || '—'}</h6>
              <p class="text-body-secondary small mb-0" style="white-space: pre-wrap; font-size: 0.8rem; line-height: 1.45;">${desc || '—'}</p>
            ` : `
              <div class="text-center py-4 text-body-secondary">
                <i class="icon-base ti tabler-plus-circle text-muted fs-3 mb-1 d-block opacity-50"></i>
                <small class="d-block text-muted mb-2">{{ __("Not defined yet") }}</small>
                <a href="${charSheetUrl}" class="btn btn-xs btn-label-secondary rounded-pill">
                  <i class="icon-base ti tabler-edit me-1"></i> {{ __("Add Trait") }}
                </a>
              </div>
            `}
          </div>
        </div>
      `;
    }).join('');
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
    const activeCharNameEl = document.getElementById('timelineActiveCharName');
    if (activeCharNameEl) {
      activeCharNameEl.textContent = '{{ __("No Hero Selected") }}';
    }
    renderTraitsTab(null);
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
    renderTraitsTab(found);
  }

  // ----------------------------------------------------
  // ACTIVITY TIMELINE & FINANCIAL LEDGER ENGINE
  // ----------------------------------------------------
  const activityStorageKey = 'valiant_shade_activities_v7_' + userUuid;
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

  function isMockActivity(item) {
    if (!item || typeof item !== 'object') return true;
    const title = String(item.title || '');
    const desc = String(item.desc || '');
    const meta = String(item.meta || '');
    // Mock titles / quests / characters
    if (title.includes('Goblin Ambush') || title.includes('Goblin Pususu')) return true;
    if (title.includes('Dungeon Escape') || title.includes('Zindandan Kaçış')) return true;
    if (title.includes('Successful Quest Payout') || title.includes('Başarılı Görev Ödemesi')) return true;
    if (title.includes('Lyra Shadowmend') && (title.includes('100 Gold') || title.includes('para gönderdi') || title.includes('sent you 100'))) return true;
    if (title.includes('Valen Ironforge') && (title.includes('200 Gold') || title.includes('gönderdi') || title.includes('sent 200'))) return true;
    // Mock descriptions
    if (desc.includes('Goblin Pususu') || desc.includes('Goblin Ambush')) return true;
    if (desc.includes('Zindandan Kaçış') || desc.includes('Dungeon Escape')) return true;
    if (desc.includes('Demirci ustasından zırh siparişi') || desc.includes('Blacksmith armor order')) return true;
    if (desc.includes('Zindan keşif seferi ganimet payı') || desc.includes('Dungeon expedition loot share')) return true;
    // Mock metadata
    if (meta.includes('Goblin Ambush') || meta.includes('Dungeon Escape')) return true;
    if (meta.includes('Valen Ironforge')) return true;
    return false;
  }

  function getAllActivities() {
    try {
      let raw = localStorage.getItem(activityStorageKey);
      if (!raw) {
        raw = localStorage.getItem('valiant_shade_activities_v6_' + userUuid) ||
              localStorage.getItem('valiant_shade_activities_v5_' + userUuid) ||
              localStorage.getItem('valiant_shade_activities_v4_' + userUuid) ||
              localStorage.getItem('valiant_shade_activities_v3_' + userUuid);
      }
      if (raw) {
        const parsed = JSON.parse(raw) || {};
        let modified = false;

        Object.keys(parsed).forEach(charId => {
          if (Array.isArray(parsed[charId])) {
            const originalCount = parsed[charId].length;
            parsed[charId] = parsed[charId].filter(item => !isMockActivity(item));
            if (parsed[charId].length !== originalCount) {
              modified = true;
            }
          }
        });

        // Purge legacy storage keys so they never reload old mock activities
        ['v6', 'v5', 'v4', 'v3'].forEach(v => {
          try { localStorage.removeItem('valiant_shade_activities_' + v + '_' + userUuid); } catch (e) {}
        });

        if (modified || !localStorage.getItem(activityStorageKey)) {
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

  function resetTimelineFilter() {
    currentTimelineFilter = 'all';
    document.querySelectorAll('.timeline-filter-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-filter') === 'all');
    });
    const chars = getCharacters();
    const char = chars.find(c => c.id === selectedCharId) || chars[0];
    if (char) renderActivityTimeline(char);
  }

  function renderActivityTimeline(char) {
    const goldValEl = document.getElementById('timelineCharGoldVal');
    const spValEl = document.getElementById('timelineCharSpVal');
    const timelineList = document.getElementById('characterActivityTimelineList');
    const emptyEl = document.getElementById('timelineEmptyState');
    const countEl = document.getElementById('timelineActivityCountBadge');
    const activeCharNameEl = document.getElementById('timelineActiveCharName');
    const emptyIconEl = document.getElementById('timelineEmptyStateIcon');
    const emptyTitleEl = document.getElementById('timelineEmptyStateTitle');
    const emptyDescEl = document.getElementById('timelineEmptyStateDesc');
    const emptyActionEl = document.getElementById('timelineEmptyStateAction');
    const quickTransferBtn = document.getElementById('btnQuickTransferMoney');

    if (activeCharNameEl) {
      activeCharNameEl.textContent = char ? char.name : '{{ __("No Hero Selected") }}';
    }

    if (!char) {
      if (goldValEl) goldValEl.textContent = '0';
      if (spValEl) spValEl.textContent = '(0 SP)';
      if (timelineList) timelineList.innerHTML = '';
      if (countEl) countEl.innerHTML = '<i class="icon-base ti tabler-activity icon-xs text-muted me-1"></i><span>0 {{ __("Events") }}</span>';
      if (quickTransferBtn) quickTransferBtn.classList.add('d-none');

      if (emptyEl) {
        emptyEl.classList.remove('d-none');
        if (emptyIconEl) emptyIconEl.className = 'icon-base ti tabler-scroll fs-1 text-primary';
        if (emptyTitleEl) emptyTitleEl.textContent = '{{ __("Chronicles of Valiant Shade") }}';
        if (emptyDescEl) emptyDescEl.textContent = '{{ __("No hero has stepped forward yet. Create your first character to begin recording quest deeds, milestones, and treasury records.") }}';
        if (emptyActionEl) {
          emptyActionEl.innerHTML = `
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCharacterModal">
              <i class="icon-base ti tabler-user-plus fs-5"></i>
              <span>{{ __("Create First Hero") }}</span>
            </button>
          `;
        }
      }
      return;
    }

    if (quickTransferBtn) quickTransferBtn.classList.remove('d-none');

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

    if (countEl) {
      countEl.innerHTML = `<i class="icon-base ti tabler-activity icon-xs text-primary me-1"></i><span>${filtered.length} {{ __("Events") }}</span>`;
    }

    if (filtered.length === 0) {
      if (timelineList) timelineList.innerHTML = '';
      if (emptyEl) {
        emptyEl.classList.remove('d-none');
        if (allItems.length === 0) {
          if (emptyIconEl) emptyIconEl.className = 'icon-base ti tabler-clock-pause fs-1 text-muted';
          if (emptyTitleEl) emptyTitleEl.textContent = '{{ __("No Activities Found") }}';
          if (emptyDescEl) emptyDescEl.textContent = '{{ __("No activity records found for this character yet.") }}';
          if (emptyActionEl) {
            emptyActionEl.innerHTML = `
              <button type="button" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1 px-3 py-2 waves-effect" data-bs-toggle="modal" data-bs-target="#transferMoneyModal">
                <i class="icon-base ti tabler-arrows-exchange me-1"></i>
                <span>{{ __("Transfer Money") }}</span>
              </button>
            `;
          }
        } else {
          if (emptyIconEl) emptyIconEl.className = 'icon-base ti tabler-notes-off fs-1 text-muted';
          if (emptyTitleEl) emptyTitleEl.textContent = '{{ __("No Activities in this Category") }}';
          if (emptyDescEl) emptyDescEl.textContent = `{{ __("No recorded activities found for this filter for") }} ${char.name}.`;
          if (emptyActionEl) {
            emptyActionEl.innerHTML = `
              <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-3 py-2" id="btnResetTimelineFilter">
                <i class="icon-base ti tabler-filter-off me-1"></i>
                <span>{{ __("Show All Activities") }}</span>
              </button>
            `;
            document.getElementById('btnResetTimelineFilter')?.addEventListener('click', resetTimelineFilter);
          }
        }
      }
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
      countBadge.textContent = `${chars.length}/2`;
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
                  ${(() => {
                    const cTr = c.traits || ((c.sheetData && c.sheetData.sheetData) ? (c.sheetData.sheetData.traits || {}) : (c.sheetData ? (c.sheetData.traits || {}) : {}));
                    let tCnt = 0;
                    if (cTr.uye && cTr.uye.name) tCnt++;
                    if (cTr.kidemli && cTr.kidemli.name) tCnt++;
                    if (cTr.uzman && cTr.uzman.name) tCnt++;
                    return tCnt > 0 ? `<span class="badge bg-label-warning px-2 py-0_5" style="font-size: 0.7rem;" title="{{ __('Rank Traits') }}"><i class="icon-base ti tabler-sparkles icon-xs me-1"></i>${tCnt}/3 Trait</span>` : '';
                  })()}
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
    renderTraitsTab(initChar);
  } else {
    selectedCharId = null;
    renderEmptyProgressionState();
    renderActivityTimeline(null);
    renderTraitsTab(null);
  }

  // Listen for new characters from modal
  window.addEventListener('characterCreated', function (e) {
    const created = e.detail;
    renderCharacters();
    if (created && created.id) {
      selectCharacter(created.id);
    }
  });

  // ----------------------------------------------------
  // PAST / FALLEN & RETIRED CHARACTERS DIRECTORY LOGIC
  // ----------------------------------------------------
  const pastSearchInput = document.getElementById('pastCharsSearchInput');
  const pastStatusFilter = document.getElementById('pastCharsStatusFilter');
  const pastTypeFilter = document.getElementById('pastCharsTypeFilter');
  const btnClearPastSearch = document.getElementById('btnClearPastSearch');
  const pastSummaryEl = document.getElementById('pastCharsFilterSummary');
  const pastEmptyStateEl = document.getElementById('pastCharsEmptyState');
  const pastGridContainer = document.getElementById('pastCharsGridContainer');
  const pastTableContainer = document.getElementById('pastCharsTableContainer');
  const btnPastGridView = document.getElementById('btnPastCharsGridView');
  const btnPastTableView = document.getElementById('btnPastCharsTableView');
  const btnPastReset = document.getElementById('btnPastCharsResetFilters');
  const btnEmptyResetPast = document.getElementById('btnEmptyResetPastFilters');

  function filterPastCharacters() {
    const query = (pastSearchInput?.value || '').toLowerCase().trim();
    const sFilter = (pastStatusFilter?.value || '').toLowerCase();
    const tFilter = (pastTypeFilter?.value || '').toLowerCase();

    if (btnClearPastSearch) {
      if (query.length > 0) {
        btnClearPastSearch.classList.remove('d-none');
      } else {
        btnClearPastSearch.classList.add('d-none');
      }
    }

    const cardItems = document.querySelectorAll('.past-char-card-item');
    const tableRows = document.querySelectorAll('.past-char-table-row');
    let visibleCount = 0;

    cardItems.forEach((card, idx) => {
      const name = card.getAttribute('data-name') || '';
      const status = card.getAttribute('data-status') || '';
      const type = card.getAttribute('data-type') || '';

      const matchSearch = !query || name.includes(query);
      const matchStatus = !sFilter || status.includes(sFilter);
      const matchType = !tFilter || type === tFilter;

      const isMatch = matchSearch && matchStatus && matchType;

      if (isMatch) {
        card.classList.remove('d-none');
        if (tableRows[idx]) tableRows[idx].classList.remove('d-none');
        visibleCount++;
      } else {
        card.classList.add('d-none');
        if (tableRows[idx]) tableRows[idx].classList.add('d-none');
      }
    });

    if (pastSummaryEl) {
      pastSummaryEl.textContent = `${visibleCount} {{ __('past heroes listed') }}`;
    }

    if (pastEmptyStateEl) {
      if (visibleCount === 0) {
        pastEmptyStateEl.classList.remove('d-none');
        pastGridContainer?.classList.add('d-none');
        pastTableContainer?.classList.add('d-none');
      } else {
        pastEmptyStateEl.classList.add('d-none');
        if (btnPastGridView?.classList.contains('active')) {
          pastGridContainer?.classList.remove('d-none');
          pastTableContainer?.classList.add('d-none');
        } else {
          pastGridContainer?.classList.add('d-none');
          pastTableContainer?.classList.remove('d-none');
        }
      }
    }
  }

  if (pastSearchInput) {
    pastSearchInput.addEventListener('input', filterPastCharacters);
  }
  if (pastStatusFilter) {
    pastStatusFilter.addEventListener('change', filterPastCharacters);
  }
  if (pastTypeFilter) {
    pastTypeFilter.addEventListener('change', filterPastCharacters);
  }
  if (btnClearPastSearch) {
    btnClearPastSearch.addEventListener('click', function() {
      pastSearchInput.value = '';
      filterPastCharacters();
      pastSearchInput.focus();
    });
  }

  function resetPastFilters() {
    if (pastSearchInput) pastSearchInput.value = '';
    if (pastStatusFilter) pastStatusFilter.value = '';
    if (pastTypeFilter) pastTypeFilter.value = '';
    filterPastCharacters();
  }

  if (btnPastReset) btnPastReset.addEventListener('click', resetPastFilters);
  if (btnEmptyResetPast) btnEmptyResetPast.addEventListener('click', resetPastFilters);

  // View toggle
  if (btnPastGridView && btnPastTableView) {
    btnPastGridView.addEventListener('click', function() {
      btnPastGridView.classList.add('active');
      btnPastTableView.classList.remove('active');
      if (pastEmptyStateEl && pastEmptyStateEl.classList.contains('d-none')) {
        pastGridContainer?.classList.remove('d-none');
        pastTableContainer?.classList.add('d-none');
      }
    });
    btnPastTableView.addEventListener('click', function() {
      btnPastTableView.classList.add('active');
      btnPastGridView.classList.remove('active');
      if (pastEmptyStateEl && pastEmptyStateEl.classList.contains('d-none')) {
        pastGridContainer?.classList.add('d-none');
        pastTableContainer?.classList.remove('d-none');
      }
    });
  }

  // Handle URL hash or query param to auto-switch tab
  const urlParams = new URLSearchParams(window.location.search);
  if (window.location.hash === '#tab-past-characters' || window.location.hash === '#tab-all-characters' || urlParams.get('tab') === 'past' || urlParams.get('tab') === 'all') {
    const pastCharsTabBtn = document.getElementById('tab-past-characters-btn');
    if (pastCharsTabBtn && window.bootstrap && bootstrap.Tab) {
      const tabTrigger = new bootstrap.Tab(pastCharsTabBtn);
      tabTrigger.show();
    }
  }
});
</script>
@endsection