<!-- Create Character Modal -->
<div class="modal fade" id="createCharacterModal" tabindex="-1" aria-labelledby="createCharacterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content p-2 p-md-4">
      <div class="modal-body position-relative">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
        
        <!-- Header -->
        <div class="text-center mb-4">
          <div class="avatar avatar-xl mx-auto mb-3 bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px;">
            <i class="icon-base ti tabler-user-plus text-primary" style="font-size: 32px;"></i>
          </div>
          <h3 class="mb-1 fw-bold" id="createCharacterModalLabel">{{ __('Create New Character') }}</h3>
          <p class="text-body-secondary mb-0">{{ __('Choose the core attributes of your hero embarking on the world of Valiant Shade.') }}</p>
        </div>

<style>
.custom-class-select-box, .custom-race-select-box {
  cursor: pointer;
  user-select: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a1acb8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 0.85rem center;
  background-size: 16px 12px;
  padding-right: 2.5rem;
}
.custom-class-menu, .custom-race-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1070;
  max-height: 340px;
  overflow-y: auto;
  border-radius: 0.375rem;
  box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.35);
  background: var(--bs-paper-bg, #2f3349);
  border: 1px solid var(--bs-border-color, rgba(255, 255, 255, 0.15));
  margin-top: 4px;
}
.custom-class-menu::-webkit-scrollbar, .custom-race-menu::-webkit-scrollbar {
  width: 6px;
}
.custom-class-menu::-webkit-scrollbar-track, .custom-race-menu::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 3px;
}
.custom-class-menu::-webkit-scrollbar-thumb, .custom-race-menu::-webkit-scrollbar-thumb {
  background: rgba(130, 130, 160, 0.4);
  border-radius: 3px;
}
.custom-class-menu::-webkit-scrollbar-thumb:hover, .custom-race-menu::-webkit-scrollbar-thumb:hover {
  background: rgba(130, 130, 160, 0.7);
}
.custom-class-option, .custom-race-option {
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.custom-class-option:hover, .custom-race-option:hover {
  background-color: rgba(115, 103, 240, 0.12) !important;
  color: #7367f0 !important;
}
.custom-class-option.active, .custom-race-option.active {
  background-color: #7367f0 !important;
  color: #ffffff !important;
}
.custom-class-option.active .check-icon, .custom-race-option.active .check-icon {
  color: #ffffff !important;
}
.custom-class-option.active .text-muted, .custom-race-option.active .text-muted {
  color: rgba(255, 255, 255, 0.75) !important;
}
.menu-category-header {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.35rem 0.75rem;
  background: rgba(115, 103, 240, 0.08);
  color: #7367f0;
  border-radius: 0.25rem;
  margin: 0.25rem 0;
}
</style>

@php
  $loadEnumFile = function ($file) {
    $candidates = [
      config_path('enums/' . $file),
      config_path('Enums/' . $file),
      config_path('enums/' . str_replace('pathfinder_', '', $file)),
      config_path('Enums/' . str_replace('pathfinder_', '', $file)),
    ];
    foreach ($candidates as $p) {
      if (file_exists($p)) return require $p;
    }
    return [];
  };

  $pfRaces = $loadEnumFile('races.php');
  $pfClasses = $loadEnumFile('classes.php');

  // Categorize 64 races
  $raceCategories = [
    'Core Races' => ['human', 'elf', 'dwarf', 'gnome', 'half_elf', 'half_orc', 'halfling'],
    'Standard Races' => [
      'aasimar', 'catfolk', 'dhampir', 'drow', 'fetchling', 'goblin', 'hobgoblin', 'ifrit',
      'kobold', 'orc', 'oread', 'ratfolk', 'sylph', 'tengu', 'tiefling', 'undine'
    ],
    'Advanced Races' => [
      'android', 'aquatic_elf', 'cecaelia', 'changeling', 'deep_gnome', 'derro', 'drow_noble',
      'duergar', 'ghoran', 'gillman', 'grippli', 'kasatha', 'kitsune', 'lashunta', 'lizardfolk',
      'locathah', 'merfolk', 'monkey_goblin', 'nagaji', 'samsaran', 'strix', 'suli', 'svirfneblin',
      'vanara', 'vishkanya', 'wayang', 'wyrwood', 'wyvaran'
    ],
    'Monstrous & Other Races' => []
  ];

  $allCategorizedRaces = array_merge(...array_values($raceCategories));
  foreach (array_keys($pfRaces) as $rKey) {
    if (!in_array($rKey, $allCategorizedRaces, true)) {
      $raceCategories['Monstrous & Other Races'][] = $rKey;
    }
  }

  // Categorize 51 classes
  $classCategories = [
    'Core Classes' => ['barbarian', 'bard', 'cleric', 'druid', 'fighter', 'monk', 'paladin', 'ranger', 'rogue', 'sorcerer', 'wizard'],
    'Base Classes' => ['alchemist', 'cavalier', 'gunslinger', 'inquisitor', 'magus', 'omdura', 'oracle', 'shifter', 'summoner', 'vampire_hunter', 'vigilante', 'witch'],
    'Hybrid Classes' => ['arcanist', 'bloodrager', 'brawler', 'hunter', 'investigator', 'shaman', 'skald', 'slayer', 'swashbuckler', 'warpriest'],
    'Occult Classes' => ['kineticist', 'medium', 'mesmerist', 'occultist', 'psychic', 'spiritualist'],
    'Alternate & Unchained' => ['antipaladin', 'ninja', 'samurai', 'unchained_barbarian', 'unchained_monk', 'unchained_rogue', 'unchained_summoner'],
    'NPC Classes' => ['adept', 'aristocrat', 'commoner', 'expert', 'warrior_npc']
  ];

  $currentUser = Auth::user();
  $isAdmin = $currentUser && ($currentUser->id === 1 || strcasecmp($currentUser->email, 'admin@valiantshade.com') === 0 || (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin')));
  $userChars = $currentUser ? \App\Models\CharacterProfile::where('user_id', $currentUser->id)->get() : collect();
  $hasMain = $userChars->contains(fn($c) => $c->isMain());
  $hasClerk = $userChars->contains(fn($c) => $c->isClerk());
  $isFull = !$isAdmin && ($userChars->count() >= 2);
@endphp

        @if($isFull)
          <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="icon-base ti tabler-alert-triangle fs-4 me-2"></i>
            <div>
              <strong>{{ __('Character Limit Reached (2/2)') }}</strong>
              <div class="small">{{ __('Each player account is strictly limited to 2 characters: one Main and one Clerk. You already have both.') }}</div>
            </div>
          </div>
        @endif

        <!-- Form -->
        <form id="createCharacterForm" class="row g-4" onsubmit="return false;">
          <!-- 1. Rank / Slot Selection -->
          <div class="col-12">
            <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
              <span><i class="icon-base ti tabler-badge me-1 text-primary"></i> {{ __('Character Rank / Slot') }} <span class="text-danger">*</span></span>
              <span class="small text-body-secondary">{{ __('Max 2 characters per player') }}</span>
            </label>
            <div class="row g-3">
              <div class="col-6">
                <div class="form-check custom-option custom-option-basic p-0">
                  <label class="form-check-label custom-option-content p-3 w-100 border rounded cursor-pointer" for="rankMain">
                    <input class="form-check-input me-2" type="radio" name="charModalRank" id="rankMain" value="Main" {{ (!$hasMain || $isAdmin) ? 'checked' : '' }} required />
                    <span class="d-flex align-items-center justify-content-between">
                      <span class="fw-bold text-heading d-flex align-items-center gap-1">
                        <i class="icon-base ti tabler-crown text-primary"></i> {{ __('Main (Candidate)') }}
                      </span>
                      @if($hasMain && !$isAdmin)
                        <span class="badge bg-label-secondary font-monospace" style="font-size: 0.68rem;">{{ __('Existing Slot') }}</span>
                      @else
                        <span class="badge bg-label-primary font-monospace" style="font-size: 0.68rem;">{{ __('Primary Slot') }}</span>
                      @endif
                    </span>
                    <small class="text-body-secondary d-block mt-1">{{ __('Level 1 Candidate adventurer') }}</small>
                  </label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-check custom-option custom-option-basic p-0">
                  <label class="form-check-label custom-option-content p-3 w-100 border rounded cursor-pointer" for="rankClerk">
                    <input class="form-check-input me-2" type="radio" name="charModalRank" id="rankClerk" value="Clerk" {{ ($hasMain && !$hasClerk && !$isAdmin) ? 'checked' : '' }} required />
                    <span class="d-flex align-items-center justify-content-between">
                      <span class="fw-bold text-heading d-flex align-items-center gap-1">
                        <i class="icon-base ti tabler-feather text-info"></i> {{ __('Clerk') }}
                      </span>
                      @if($hasClerk && !$isAdmin)
                        <span class="badge bg-label-secondary font-monospace" style="font-size: 0.68rem;">{{ __('Existing Slot') }}</span>
                      @else
                        <span class="badge bg-label-info font-monospace" style="font-size: 0.68rem;">{{ __('Guild Scribe') }}</span>
                      @endif
                    </span>
                    <small class="text-body-secondary d-block mt-1">{{ __('Level 1 Guild Scribe') }}</small>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Character Name -->
          <div class="col-12">
            <label class="form-label fw-semibold" for="charModalName">
              <i class="icon-base ti tabler-id me-1 text-primary"></i> {{ __('Character Name') }} <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
              <input type="text" id="charModalName" class="form-control form-control-lg" placeholder="{{ __('Hero name (e.g. Caspian Thunderblade)') }}" maxlength="50" required autocomplete="off" />
            </div>
            <div class="invalid-feedback d-none" id="charNameFeedback">{{ __('Please enter a valid name with at least 2 characters.') }}</div>
          </div>

          <!-- 3. Character Race - Custom Dropdown with Live Search & All 64 Races -->
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="charModalRace">
              <i class="icon-base ti tabler-dna me-1 text-primary"></i> {{ __('Character Race') }} <span class="text-danger">*</span>
            </label>

            <div class="position-relative" id="customRaceDropdownWrapper">
              <!-- Hidden select for validation and form submission -->
              <select id="charModalRace" class="d-none" required>
                <option value="" disabled selected>{{ __('Select Pathfinder 1E Race...') }}</option>
                @if(!empty($pfRaces))
                  @foreach ($pfRaces as $rKey => $r)
                    <option value="{{ $rKey }}" data-name="{{ $r['name'] ?? ucfirst($rKey) }}" data-bonus="{{ $r['ability_bonus_summary'] ?? 'Standard Attributes' }}" data-category="{{ $r['category'] ?? '' }}">
                      {{ $r['name'] ?? ucfirst($rKey) }}
                    </option>
                  @endforeach
                @endif
              </select>

              <!-- Form-select trigger box -->
              <div id="customRaceSelectTrigger" class="form-select form-select-lg custom-race-select-box d-flex align-items-center justify-content-between" tabindex="0" role="combobox" aria-expanded="false">
                <span id="customRaceSelectText" class="text-body-secondary text-truncate">{{ __('Select Pathfinder 1E Race...') }}</span>
              </div>

              <!-- Searchable dropdown menu -->
              <div id="customRaceDropdownMenu" class="custom-race-menu p-2 d-none">
                <!-- Search input -->
                <div class="mb-2">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                    <input type="text" id="customRaceSearchInput" class="form-control" placeholder="{{ __('Search 64 races...') }}" autocomplete="off" />
                  </div>
                </div>

                <div id="customRaceOptionsList">
                  @if(!empty($pfRaces))
                    @foreach ($raceCategories as $catTitle => $rKeys)
                      @php
                        $availableInCat = array_filter($rKeys, fn($k) => isset($pfRaces[$k]));
                      @endphp
                      @if(!empty($availableInCat))
                        <div class="menu-category-header race-category-header">{{ $catTitle }} ({{ count($availableInCat) }})</div>
                        @foreach ($availableInCat as $rKey)
                          @php $r = $pfRaces[$rKey]; @endphp
                          <div class="custom-race-option d-flex align-items-center justify-content-between py-2 px-3 rounded-2"
                               data-value="{{ $rKey }}"
                               data-name="{{ $r['name'] ?? ucfirst($rKey) }}"
                               data-bonus="{{ $r['ability_bonus_summary'] ?? 'Standard Attributes' }}"
                               data-category="{{ $catTitle }}">
                            <div class="pe-2">
                              <span class="fw-semibold text-heading">{{ $r['name'] ?? ucfirst($rKey) }}</span>
                              <small class="text-muted d-block" style="font-size: 0.75rem;">{{ $r['ability_bonus_summary'] ?? 'Standard' }}</small>
                            </div>
                            <i class="icon-base ti tabler-check d-none text-primary fs-5 check-icon"></i>
                          </div>
                        @endforeach
                      @endif
                    @endforeach
                  @endif
                </div>
              </div>
            </div>
            <div class="form-text text-danger d-flex align-items-center gap-1 mt-1">
              <i class="icon-base ti tabler-lock fs-6"></i>
              <span class="small fw-semibold">{{ __('Permanent: Race cannot be modified after creation.') }}</span>
            </div>
          </div>

          <!-- 4. Character Class - Custom Dropdown with Live Search & All 51 Classes -->
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="charModalClass">
              <i class="icon-base ti tabler-swords me-1 text-primary"></i> {{ __('Character Class') }} <span class="text-danger">*</span>
            </label>

            <div class="position-relative" id="customClassDropdownWrapper">
              <!-- Hidden select for validation and form submission -->
              <select id="charModalClass" class="d-none" required>
                <option value="" disabled selected>{{ __('Select Pathfinder 1E Class...') }}</option>
                @if(!empty($pfClasses))
                  @foreach ($pfClasses as $cKey => $c)
                    <option value="{{ $cKey }}" data-name="{{ $c['name'] ?? ucfirst($cKey) }}" data-hd="{{ $c['hit_die'] ?? 'd10' }}">
                      {{ $c['name'] ?? ucfirst($cKey) }} ({{ $c['hit_die'] ?? 'd10' }})
                    </option>
                  @endforeach
                @endif
              </select>

              <!-- Form-select trigger box -->
              <div id="customClassSelectTrigger" class="form-select form-select-lg custom-class-select-box d-flex align-items-center justify-content-between" tabindex="0" role="combobox" aria-expanded="false">
                <span id="customClassSelectText" class="text-body-secondary text-truncate">{{ __('Select Pathfinder 1E Class...') }}</span>
              </div>

              <!-- Searchable dropdown menu -->
              <div id="customClassDropdownMenu" class="custom-class-menu p-2 d-none">
                <!-- Search input -->
                <div class="mb-2">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                    <input type="text" id="customClassSearchInput" class="form-control" placeholder="{{ __('Search 51 classes...') }}" autocomplete="off" />
                  </div>
                </div>

                <div id="customClassOptionsList">
                  @if(!empty($pfClasses))
                    @foreach ($classCategories as $catTitle => $cKeys)
                      @php
                        $availableInCat = array_filter($cKeys, fn($k) => isset($pfClasses[$k]));
                      @endphp
                      @if(!empty($availableInCat))
                        <div class="menu-category-header class-category-header">{{ $catTitle }} ({{ count($availableInCat) }})</div>
                        @foreach ($availableInCat as $cKey)
                          @php $c = $pfClasses[$cKey]; @endphp
                          <div class="custom-class-option d-flex align-items-center justify-content-between py-2 px-3 rounded-2"
                               data-value="{{ $cKey }}"
                               data-name="{{ $c['name'] ?? ucfirst($cKey) }}"
                               data-hd="{{ $c['hit_die'] ?? 'd10' }}"
                               data-category="{{ $catTitle }}">
                            <span>
                              <span class="fw-semibold text-heading">{{ $c['name'] ?? ucfirst($cKey) }}</span>
                              <small class="badge bg-label-primary font-monospace ms-1" style="font-size: 0.72rem;">{{ $c['hit_die'] ?? 'd10' }}</small>
                            </span>
                            <i class="icon-base ti tabler-check d-none text-primary fs-5 check-icon"></i>
                          </div>
                        @endforeach
                      @endif
                    @endforeach
                  @endif
                </div>
              </div>
            </div>
            <div class="form-text text-muted" id="classDescText">{{ __('Choose class to determine Hit Die, BAB, and saves.') }}</div>
          </div>

          <!-- 5. Ability Scores (Stats) Section with Quick Presets -->
          <div class="col-12">
            <div class="card border border-primary border-opacity-25 bg-label-secondary bg-opacity-10 p-3 shadow-none">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div>
                  <label class="form-label fw-bold text-heading mb-0 d-flex align-items-center gap-1">
                    <i class="icon-base ti tabler-chart-dots text-primary"></i> {{ __('Ability Scores (Stats)') }} <span class="text-danger">*</span>
                  </label>
                  <small class="text-body-secondary d-block">{{ __('All 6 Pathfinder ability scores (1 - 30). Modifiers are calculated automatically.') }}</small>
                </div>
                <!-- Quick Preset Buttons -->
                <div class="btn-group btn-group-sm" role="group" aria-label="Stat Presets">
                  <button type="button" class="btn btn-outline-primary btn-stat-preset" data-preset="15,14,13,12,10,8">
                    Standard (15,14,13...)
                  </button>
                  <button type="button" class="btn btn-outline-primary btn-stat-preset" data-preset="16,14,14,12,10,8">
                    High (16,14,14...)
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-stat-preset" data-preset="10,10,10,10,10,10">
                    {{ __('Reset (10)') }}
                  </button>
                </div>
              </div>

              <div class="row g-2 mt-1">
                <!-- STR -->
                <div class="col-4 col-md-2">
                  <div class="stat-input-box p-2 text-center border rounded bg-body shadow-xs">
                    <label class="fw-bold small text-uppercase text-danger d-block mb-1" for="charStatStr">STR</label>
                    <input type="number" id="charStatStr" class="form-control form-control-sm text-center fw-bold font-monospace stat-input" min="1" max="30" value="10" required />
                    <div class="small fw-semibold text-muted mt-1 stat-mod-text" id="modStr">+0 Mod</div>
                  </div>
                </div>
                <!-- DEX -->
                <div class="col-4 col-md-2">
                  <div class="stat-input-box p-2 text-center border rounded bg-body shadow-xs">
                    <label class="fw-bold small text-uppercase text-success d-block mb-1" for="charStatDex">DEX</label>
                    <input type="number" id="charStatDex" class="form-control form-control-sm text-center fw-bold font-monospace stat-input" min="1" max="30" value="10" required />
                    <div class="small fw-semibold text-muted mt-1 stat-mod-text" id="modDex">+0 Mod</div>
                  </div>
                </div>
                <!-- CON -->
                <div class="col-4 col-md-2">
                  <div class="stat-input-box p-2 text-center border rounded bg-body shadow-xs">
                    <label class="fw-bold small text-uppercase text-warning d-block mb-1" for="charStatCon">CON</label>
                    <input type="number" id="charStatCon" class="form-control form-control-sm text-center fw-bold font-monospace stat-input" min="1" max="30" value="10" required />
                    <div class="small fw-semibold text-muted mt-1 stat-mod-text" id="modCon">+0 Mod</div>
                  </div>
                </div>
                <!-- INT -->
                <div class="col-4 col-md-2">
                  <div class="stat-input-box p-2 text-center border rounded bg-body shadow-xs">
                    <label class="fw-bold small text-uppercase text-info d-block mb-1" for="charStatInt">INT</label>
                    <input type="number" id="charStatInt" class="form-control form-control-sm text-center fw-bold font-monospace stat-input" min="1" max="30" value="10" required />
                    <div class="small fw-semibold text-muted mt-1 stat-mod-text" id="modInt">+0 Mod</div>
                  </div>
                </div>
                <!-- WIS -->
                <div class="col-4 col-md-2">
                  <div class="stat-input-box p-2 text-center border rounded bg-body shadow-xs">
                    <label class="fw-bold small text-uppercase text-primary d-block mb-1" for="charStatWis">WIS</label>
                    <input type="number" id="charStatWis" class="form-control form-control-sm text-center fw-bold font-monospace stat-input" min="1" max="30" value="10" required />
                    <div class="small fw-semibold text-muted mt-1 stat-mod-text" id="modWis">+0 Mod</div>
                  </div>
                </div>
                <!-- CHA -->
                <div class="col-4 col-md-2">
                  <div class="stat-input-box p-2 text-center border rounded bg-body shadow-xs">
                    <label class="fw-bold small text-uppercase text-secondary d-block mb-1" for="charStatCha">CHA</label>
                    <input type="number" id="charStatCha" class="form-control form-control-sm text-center fw-bold font-monospace stat-input" min="1" max="30" value="10" required />
                    <div class="small fw-semibold text-muted mt-1 stat-mod-text" id="modCha">+0 Mod</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Live RPG Character Preview Card -->
          <div class="col-12">
            <div class="card border border-primary border-opacity-25 bg-label-primary shadow-none p-3">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar avatar-md bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" id="charPreviewIcon" style="width: 48px; height: 48px;">
                    <i class="icon-base ti tabler-user fs-3"></i>
                  </div>
                  <div>
                    <h5 class="mb-0 fw-bold" id="charPreviewName">{{ __('Awaiting Hero Name...') }}</h5>
                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                      <span class="badge bg-primary text-white" id="charPreviewRankBadge">{{ ($hasMain && !$hasClerk && !$isAdmin) ? 'Clerk' : 'Aday' }}</span>
                      <span class="badge bg-label-primary" id="charPreviewRaceBadge">{{ __('No Race Selected') }}</span>
                      <span class="badge bg-label-secondary" id="charPreviewClassBadge">{{ __('No Class Selected') }}</span>
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-danger px-2 py-1 fw-bold" id="charPreviewHpBadge">
                    <i class="icon-base ti tabler-heart me-1"></i> HP: 10
                  </span>
                  <span class="badge bg-label-primary px-2 py-1 fw-semibold">
                    <i class="icon-base ti tabler-shield-check me-1"></i> Level 1
                  </span>
                </div>
              </div>
              
              <!-- Detail Row (Racial and Class Features) -->
              <div class="mt-3 pt-2 border-top border-primary border-opacity-10 row g-2 small text-body-secondary" id="charPreviewDetails">
                <div class="col-sm-6" id="charPreviewBonusRow">
                  <span class="fw-semibold text-heading"><i class="icon-base ti tabler-sparkles text-warning me-1"></i> {{ __('Racial Bonus:') }}</span>
                  <span id="charPreviewBonus" class="text-body-secondary">{{ __('Not selected yet') }}</span>
                </div>
                <div class="col-sm-6" id="charPreviewRoleRow">
                  <span class="fw-semibold text-heading"><i class="icon-base ti tabler-swords text-info me-1"></i> {{ __('Class:') }}</span>
                  <span id="charPreviewRole" class="text-body-secondary">{{ __('Not selected yet') }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Alert Feedback -->
          <div class="col-12 d-none" id="charSuccessAlert">
            <div class="alert alert-success d-flex align-items-center mb-0" role="alert" id="charSuccessBox">
              <i class="icon-base ti tabler-circle-check fs-4 me-2" id="charSuccessIcon"></i>
              <div>
                <strong id="charSuccessTitle">{{ __('Character Created Successfully!') }}</strong>
                <div class="small" id="charSuccessMsg">{{ __('Your hero is ready for adventure in the Valiant Shade realm.') }}</div>
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="col-12 text-center mt-3">
            <button type="button" class="btn btn-label-secondary me-2 px-4 waves-effect" data-bs-dismiss="modal">
              <i class="icon-base ti tabler-x me-1"></i> {{ __('Cancel') }}
            </button>
            <button type="button" id="btnCreateCharacterSubmit" class="btn btn-primary px-5 waves-effect waves-light" {{ $isFull ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-user-plus me-1"></i> {{ __('Create Character') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const nameInput = document.getElementById('charModalName');
  const raceSelect = document.getElementById('charModalRace');
  const classSelect = document.getElementById('charModalClass');
  const submitBtn = document.getElementById('btnCreateCharacterSubmit');

  const previewName = document.getElementById('charPreviewName');
  const previewRankBadge = document.getElementById('charPreviewRankBadge');
  const previewRaceBadge = document.getElementById('charPreviewRaceBadge');
  const previewClassBadge = document.getElementById('charPreviewClassBadge');
  const previewHpBadge = document.getElementById('charPreviewHpBadge');
  const previewBonus = document.getElementById('charPreviewBonus');
  const previewRole = document.getElementById('charPreviewRole');
  const successAlert = document.getElementById('charSuccessAlert');
  const successTitle = document.getElementById('charSuccessTitle');
  const successMsg = document.getElementById('charSuccessMsg');
  const nameFeedback = document.getElementById('charNameFeedback');

  // --- 1. Class Custom Dropdown with Live Search ---
  const classDropdownTrigger = document.getElementById('customClassSelectTrigger');
  const classDropdownMenu = document.getElementById('customClassDropdownMenu');
  const classDropdownText = document.getElementById('customClassSelectText');
  const classSearchInput = document.getElementById('customClassSearchInput');
  const customClassOptions = document.querySelectorAll('.custom-class-option');

  function openClassDropdown() {
    classDropdownMenu.classList.remove('d-none');
    classDropdownTrigger.classList.add('border-primary');
    classDropdownTrigger.setAttribute('aria-expanded', 'true');
    if (classSearchInput) {
      setTimeout(() => classSearchInput.focus(), 50);
    }
  }

  function closeClassDropdown() {
    classDropdownMenu.classList.add('d-none');
    classDropdownTrigger.classList.remove('border-primary');
    classDropdownTrigger.setAttribute('aria-expanded', 'false');
  }

  if (classDropdownTrigger && classDropdownMenu) {
    classDropdownTrigger.addEventListener('click', function (e) {
      e.stopPropagation();
      e.preventDefault();
      closeRaceDropdown();
      classDropdownTrigger.classList.remove('is-invalid', 'border-danger');
      if (classDropdownMenu.classList.contains('d-none')) {
        openClassDropdown();
      } else {
        closeClassDropdown();
      }
    });

    document.addEventListener('click', function (e) {
      if (!classDropdownTrigger.contains(e.target) && !classDropdownMenu.contains(e.target)) {
        closeClassDropdown();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !classDropdownMenu.classList.contains('d-none')) {
        closeClassDropdown();
      }
    });

    // Real-time Class Search Filtering
    if (classSearchInput) {
      classSearchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const headers = classDropdownMenu.querySelectorAll('.class-category-header');
        
        customClassOptions.forEach(opt => {
          const name = (opt.getAttribute('data-name') || '').toLowerCase();
          const val = (opt.getAttribute('data-value') || '').toLowerCase();
          const match = name.includes(query) || val.includes(query);
          opt.style.display = match ? 'flex' : 'none';
        });

        // Hide empty category headers
        headers.forEach(h => {
          let next = h.nextElementSibling;
          let anyVisible = false;
          while (next && !next.classList.contains('class-category-header')) {
            if (next.style.display !== 'none') anyVisible = true;
            next = next.nextElementSibling;
          }
          h.style.display = anyVisible ? 'block' : 'none';
        });
      });
    }

    customClassOptions.forEach(function (opt) {
      opt.addEventListener('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        const val = this.getAttribute('data-value');
        const name = this.getAttribute('data-name');
        const hd = this.getAttribute('data-hd') || 'd10';

        customClassOptions.forEach(o => {
          o.classList.remove('active');
          const chk = o.querySelector('.check-icon');
          if (chk) chk.classList.add('d-none');
        });

        this.classList.add('active');
        const checkIcon = this.querySelector('.check-icon');
        if (checkIcon) checkIcon.classList.remove('d-none');

        classDropdownText.innerHTML = `<span class="fw-semibold text-heading">${name}</span> <span class="badge bg-label-primary font-monospace ms-1" style="font-size: 0.72rem;">${hd}</span>`;
        classDropdownText.classList.remove('text-body-secondary');

        if (classSelect) {
          classSelect.value = val;
          classSelect.dispatchEvent(new Event('change'));
        }
        closeClassDropdown();
      });
    });
  }

  // --- 2. Race Custom Dropdown with Live Search ---
  const raceDropdownTrigger = document.getElementById('customRaceSelectTrigger');
  const raceDropdownMenu = document.getElementById('customRaceDropdownMenu');
  const raceDropdownText = document.getElementById('customRaceSelectText');
  const raceSearchInput = document.getElementById('customRaceSearchInput');
  const customRaceOptions = document.querySelectorAll('.custom-race-option');

  function openRaceDropdown() {
    raceDropdownMenu.classList.remove('d-none');
    raceDropdownTrigger.classList.add('border-primary');
    raceDropdownTrigger.setAttribute('aria-expanded', 'true');
    if (raceSearchInput) {
      setTimeout(() => raceSearchInput.focus(), 50);
    }
  }

  function closeRaceDropdown() {
    raceDropdownMenu.classList.add('d-none');
    raceDropdownTrigger.classList.remove('border-primary');
    raceDropdownTrigger.setAttribute('aria-expanded', 'false');
  }

  if (raceDropdownTrigger && raceDropdownMenu) {
    raceDropdownTrigger.addEventListener('click', function (e) {
      e.stopPropagation();
      e.preventDefault();
      closeClassDropdown();
      raceDropdownTrigger.classList.remove('is-invalid', 'border-danger');
      if (raceDropdownMenu.classList.contains('d-none')) {
        openRaceDropdown();
      } else {
        closeRaceDropdown();
      }
    });

    document.addEventListener('click', function (e) {
      if (!raceDropdownTrigger.contains(e.target) && !raceDropdownMenu.contains(e.target)) {
        closeRaceDropdown();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !raceDropdownMenu.classList.contains('d-none')) {
        closeRaceDropdown();
      }
    });

    // Real-time Race Search Filtering
    if (raceSearchInput) {
      raceSearchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const headers = raceDropdownMenu.querySelectorAll('.race-category-header');
        
        customRaceOptions.forEach(opt => {
          const name = (opt.getAttribute('data-name') || '').toLowerCase();
          const val = (opt.getAttribute('data-value') || '').toLowerCase();
          const bonus = (opt.getAttribute('data-bonus') || '').toLowerCase();
          const match = name.includes(query) || val.includes(query) || bonus.includes(query);
          opt.style.display = match ? 'flex' : 'none';
        });

        // Hide empty category headers
        headers.forEach(h => {
          let next = h.nextElementSibling;
          let anyVisible = false;
          while (next && !next.classList.contains('race-category-header')) {
            if (next.style.display !== 'none') anyVisible = true;
            next = next.nextElementSibling;
          }
          h.style.display = anyVisible ? 'block' : 'none';
        });
      });
    }

    customRaceOptions.forEach(function (opt) {
      opt.addEventListener('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        const val = this.getAttribute('data-value');
        const name = this.getAttribute('data-name');
        const bonus = this.getAttribute('data-bonus') || '';

        customRaceOptions.forEach(o => {
          o.classList.remove('active');
          const chk = o.querySelector('.check-icon');
          if (chk) chk.classList.add('d-none');
        });

        this.classList.add('active');
        const checkIcon = this.querySelector('.check-icon');
        if (checkIcon) checkIcon.classList.remove('d-none');

        raceDropdownText.innerHTML = `<span class="fw-semibold text-heading">${name}</span> <small class="text-muted ms-1">(${bonus})</small>`;
        raceDropdownText.classList.remove('text-body-secondary');

        if (raceSelect) {
          raceSelect.value = val;
          raceSelect.dispatchEvent(new Event('change'));
        }
        closeRaceDropdown();
      });
    });
  }

  // --- 3. Ability Score Modifiers & Presets ---
  function calcMod(val) {
    const n = parseInt(val, 10);
    if (isNaN(n)) return '+0';
    const mod = Math.floor((n - 10) / 2);
    return (mod >= 0 ? '+' : '') + mod;
  }

  function updateModDisplay(inputEl, modEl) {
    if (!inputEl || !modEl) return;
    const val = parseInt(inputEl.value, 10);
    const mod = Math.floor((val - 10) / 2);
    const modStr = (mod >= 0 ? '+' : '') + mod + ' Mod';
    modEl.textContent = modStr;
    if (mod > 0) {
      modEl.className = 'small fw-bold text-success mt-1 stat-mod-text';
    } else if (mod < 0) {
      modEl.className = 'small fw-bold text-danger mt-1 stat-mod-text';
    } else {
      modEl.className = 'small fw-semibold text-muted mt-1 stat-mod-text';
    }
  }

  const statInputs = [
    { el: document.getElementById('charStatStr'), modEl: document.getElementById('modStr') },
    { el: document.getElementById('charStatDex'), modEl: document.getElementById('modDex') },
    { el: document.getElementById('charStatCon'), modEl: document.getElementById('modCon') },
    { el: document.getElementById('charStatInt'), modEl: document.getElementById('modInt') },
    { el: document.getElementById('charStatWis'), modEl: document.getElementById('modWis') },
    { el: document.getElementById('charStatCha'), modEl: document.getElementById('modCha') }
  ];

  statInputs.forEach(item => {
    if (item.el) {
      updateModDisplay(item.el, item.modEl);
      item.el.addEventListener('input', function () {
        updateModDisplay(this, item.modEl);
        updatePreview();
      });
    }
  });

  // Preset Buttons
  document.querySelectorAll('.btn-stat-preset').forEach(btn => {
    btn.addEventListener('click', function () {
      const raw = this.getAttribute('data-preset');
      if (!raw) return;
      const values = raw.split(',').map(v => parseInt(v.trim(), 10));
      if (values.length === 6) {
        statInputs.forEach((item, idx) => {
          if (item.el) {
            item.el.value = values[idx];
            updateModDisplay(item.el, item.modEl);
          }
        });
        updatePreview();
      }
    });
  });

  // --- 4. Live RPG Preview Card Update ---
  function updatePreview() {
    if (!nameInput || !raceSelect || !classSelect) return;
    const rawName = nameInput.value.trim();
    previewName.textContent = rawName.length > 0 ? rawName : 'Awaiting Hero Name...';

    // Rank Badge
    const rankRadio = document.querySelector('input[name="charModalRank"]:checked');
    const rankVal = rankRadio ? rankRadio.value : 'Main';
    if (previewRankBadge) {
      previewRankBadge.textContent = (rankVal === 'Main') ? 'Aday' : 'Clerk';
      previewRankBadge.className = (rankVal === 'Main') ? 'badge bg-primary text-white' : 'badge bg-info text-white';
    }

    // Race
    const selectedRaceOption = raceSelect.options[raceSelect.selectedIndex];
    if (raceSelect.value && selectedRaceOption) {
      const rName = selectedRaceOption.getAttribute('data-name') || selectedRaceOption.text;
      previewRaceBadge.textContent = rName;
      previewRaceBadge.className = 'badge bg-label-primary';
      previewBonus.textContent = selectedRaceOption.getAttribute('data-bonus') || '-';
    } else {
      previewRaceBadge.textContent = 'No Race Selected';
      previewRaceBadge.className = 'badge bg-label-secondary';
      previewBonus.textContent = 'Not selected yet';
    }

    // Class & Starting HP
    let hitDieVal = 10;
    const selectedClassOption = classSelect.options[classSelect.selectedIndex];
    if (classSelect.value && selectedClassOption) {
      const clsName = selectedClassOption.getAttribute('data-name') || selectedClassOption.text;
      const hdRaw = selectedClassOption.getAttribute('data-hd') || 'd10';
      hitDieVal = parseInt(hdRaw.replace(/\D/g, ''), 10) || 10;
      previewClassBadge.textContent = clsName;
      previewClassBadge.className = 'badge bg-label-info';
      previewRole.textContent = clsName + ' (' + hdRaw + ')';
    } else {
      previewClassBadge.textContent = 'No Class Selected';
      previewClassBadge.className = 'badge bg-label-secondary';
      previewRole.textContent = 'Not selected yet';
    }

    // Con Mod calculation for starting HP
    const conInput = document.getElementById('charStatCon');
    const conVal = conInput ? (parseInt(conInput.value, 10) || 10) : 10;
    const conMod = Math.floor((conVal - 10) / 2);
    const startHp = Math.max(1, hitDieVal + conMod);
    if (previewHpBadge) {
      previewHpBadge.innerHTML = `<i class="icon-base ti tabler-heart me-1"></i> HP: ${startHp}`;
    }
  }

  if (nameInput) nameInput.addEventListener('input', updatePreview);
  if (raceSelect) raceSelect.addEventListener('change', updatePreview);
  if (classSelect) classSelect.addEventListener('change', updatePreview);
  document.querySelectorAll('input[name="charModalRank"]').forEach(r => r.addEventListener('change', updatePreview));

  // Initial preview update
  updatePreview();

  // --- 5. Form Submission ---
  if (submitBtn) {
    submitBtn.addEventListener('click', function (e) {
      e.preventDefault();
      const charName = nameInput.value.trim();
      const charRace = raceSelect.value;
      const charClass = classSelect.value;

      const rankRadio = document.querySelector('input[name="charModalRank"]:checked');
      if (!rankRadio) {
        alert('Please select a Character Rank / Slot (Main or Clerk).');
        return;
      }
      const rankVal = rankRadio.value;

      if (!charName || charName.length < 2) {
        nameInput.classList.add('is-invalid');
        nameFeedback.classList.remove('d-none');
        nameInput.focus();
        return;
      }
      nameInput.classList.remove('is-invalid');
      nameFeedback.classList.add('d-none');

      if (!charRace) {
        if (raceDropdownTrigger) {
          raceDropdownTrigger.classList.add('is-invalid', 'border-danger');
          raceDropdownTrigger.focus();
        } else {
          raceSelect.classList.add('is-invalid');
          raceSelect.focus();
        }
        return;
      }
      if (raceDropdownTrigger) raceDropdownTrigger.classList.remove('is-invalid', 'border-danger');
      raceSelect.classList.remove('is-invalid');

      if (!charClass) {
        if (classDropdownTrigger) {
          classDropdownTrigger.classList.add('is-invalid', 'border-danger');
          classDropdownTrigger.focus();
        } else {
          classSelect.classList.add('is-invalid');
          classSelect.focus();
        }
        return;
      }
      if (classDropdownTrigger) classDropdownTrigger.classList.remove('is-invalid', 'border-danger');
      classSelect.classList.remove('is-invalid');

      // Validate all 6 mandatory stats (1 - 30)
      const stats = {
        str: parseInt(document.getElementById('charStatStr')?.value, 10),
        dex: parseInt(document.getElementById('charStatDex')?.value, 10),
        con: parseInt(document.getElementById('charStatCon')?.value, 10),
        int: parseInt(document.getElementById('charStatInt')?.value, 10),
        wis: parseInt(document.getElementById('charStatWis')?.value, 10),
        cha: parseInt(document.getElementById('charStatCha')?.value, 10)
      };

      for (const [sKey, sVal] of Object.entries(stats)) {
        if (isNaN(sVal) || sVal < 1 || sVal > 30) {
          alert(`Please enter a valid ability score for ${sKey.toUpperCase()} between 1 and 30.`);
          const sInp = document.getElementById('charStat' + sKey.charAt(0).toUpperCase() + sKey.slice(1));
          if (sInp) sInp.focus();
          return;
        }
      }

      const raceOpt = raceSelect.options[raceSelect.selectedIndex];
      const raceText = raceOpt ? (raceOpt.getAttribute('data-name') || raceOpt.text) : '';
      const classOpt = classSelect.options[classSelect.selectedIndex];
      const classText = classOpt ? (classOpt.getAttribute('data-name') || classOpt.text) : '';

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Creating hero...';

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      fetch('{{ url("/valiant-shade/character/create") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          character_name: charName,
          rank: rankVal,
          race: raceText,
          class_name: classText,
          str: stats.str,
          dex: stats.dex,
          con: stats.con,
          int: stats.int,
          wis: stats.wis,
          cha: stats.cha
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          successTitle.textContent = `${charName} (${rankVal === 'Main' ? 'Aday' : 'Clerk'}) Created Successfully!`;
          successMsg.textContent = `Your ${raceText} ${classText} hero is permanently registered in the world of Valiant Shade.`;
          successAlert.classList.remove('d-none');
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Hero Registered!',
              text: data.message || `${charName} has been created.`,
              timer: 1600,
              showConfirmButton: false
            }).then(() => window.location.reload());
          } else {
            setTimeout(() => window.location.reload(), 1200);
          }
        } else {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="icon-base ti tabler-user-plus me-1"></i> Create Character';
          alert(data.message || 'Could not create character.');
        }
      })
      .catch(err => {
        console.error('Character create error:', err);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="icon-base ti tabler-user-plus me-1"></i> Create Character';
        alert('Server connection error. Please try again.');
      });
    });
  }
});
</script>
