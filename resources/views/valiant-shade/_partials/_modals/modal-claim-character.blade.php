@php
  // Ensure Pathfinder enums are loaded even if not passed from controller
  if (empty($pfRaces) || empty($pfClasses)) {
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
    if (empty($pfRaces)) $pfRaces = $loadEnumFile('races.php');
    if (empty($pfClasses)) $pfClasses = $loadEnumFile('classes.php');
  }

  $currentUser = Auth::user();
  $userChars = $currentUser ? \App\Models\CharacterProfile::where('user_id', $currentUser->id)->get() : collect();
  $isFull = $currentUser && ($userChars->count() >= 2);
@endphp

<!-- Modal Claim Character from XP List -->
<div class="modal fade" id="claimCharacterModal" tabindex="-1" aria-labelledby="claimCharacterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-simple modal-claim-character">
    <div class="modal-content p-2 p-md-4">
      <div class="modal-body position-relative">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>

        <!-- Header (Vuexy Modal Hero) -->
        <div class="text-center mb-4">
          <div class="avatar avatar-xl mx-auto mb-3 bg-label-warning rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px;">
            <i class="icon-base ti tabler-user-check text-warning" style="font-size: 32px;"></i>
          </div>
          <h3 class="mb-1 fw-bold text-heading" id="claimCharacterModalLabel">{{ __('Claim Character from XP List') }}</h3>
          <p class="text-body-secondary mb-0">{{ __('Claim an existing hero from the Master Sheet XP records with their true level, rank and accumulated points.') }}</p>
        </div>

        @if($isFull)
          <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="icon-base ti tabler-alert-triangle fs-4 me-2 flex-shrink-0"></i>
            <div>
              <strong>{{ __('Character Limit Reached (2/2)') }}</strong>
              <div class="small">{{ __('Each player account is strictly limited to 2 characters: one Main and one Clerk. You already have both.') }}</div>
            </div>
          </div>
        @else
          <!-- Alert Note -->
          <div class="alert alert-warning alert-dismissible d-flex align-items-start mb-4" role="alert">
            <i class="icon-base ti tabler-shield-lock fs-4 me-2 flex-shrink-0 mt-0_5"></i>
            <div class="small">
              <strong class="d-block mb-1">{{ __('Important Rule & Notice:') }}</strong>
              <span>{{ __('Your chosen character will start at their true level from the XP list (e.g. Level 12 for Thoma Quickfire). Confirm class, race, and ability scores to claim.') }}</span>
            </div>
          </div>
        @endif

        <form id="formClaimCharacter" class="claim-form">
          @csrf

          <!-- Section 1: Character Selection (Autocomplete / Search & Full A-Z Dropdown) -->
          <div class="card border mb-4 shadow-none">
            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-xs bg-label-warning rounded d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-user-search fs-6"></i>
                </span>
                <h6 class="card-title mb-0 fw-bold text-heading">
                  {{ __('1. Select Character to Claim (XP Records)') }} <span class="text-danger">*</span>
                </h6>
              </div>
              <span class="badge bg-label-secondary font-monospace" id="claimCharCountBadge">
                <span class="spinner-border spinner-border-sm me-1" style="width: 0.8rem; height: 0.8rem;"></span>{{ __('Loading characters...') }}
              </span>
            </div>

            <div class="card-body p-3 p-md-4">
              <div class="row g-3">
                <div class="col-12 col-md-5">
                  <label class="form-label fw-medium mb-1" for="claimSearchInput">
                    <i class="icon-base ti tabler-filter me-1 text-warning"></i>{{ __('Quick Filter / Search') }}
                  </label>
                  <div class="input-group input-group-merge">
                    <span class="input-group-text"><i class="icon-base ti tabler-search text-body-secondary"></i></span>
                    <input type="text" class="form-control" id="claimSearchInput" placeholder="{{ __('Type name to filter (e.g. Thoma, Bart...)') }}" autocomplete="off" />
                    <button type="button" class="btn btn-outline-secondary px-2 d-none" id="btnClaimClearSearch" title="{{ __('Clear filter') }}">
                      <i class="icon-base ti tabler-x"></i>
                    </button>
                  </div>
                  <small class="text-body-secondary mt-1 d-block">{{ __('Filters the A-Z list in real time.') }}</small>
                </div>

                <div class="col-12 col-md-7">
                  <label class="form-label fw-medium mb-1" for="claimCharacterSelect">
                    <i class="icon-base ti tabler-user me-1 text-warning"></i>{{ __('Select Character (A - Z)') }} <span class="text-danger">*</span>
                  </label>
                  <select id="claimCharacterSelect" class="form-select font-monospace" required>
                    <option value="" disabled selected>{{ __('Loading characters from XP records...') }}</option>
                  </select>
                  <small class="text-body-secondary mt-1 d-block" id="claimSelectSubtext">{{ __('All characters in registry sorted alphabetically A to Z.') }}</small>
                </div>
              </div>

              <!-- Dynamic Selected Character Dossier Info Card (Vuexy Style) -->
              <div id="claimSelectedDossierCard" class="card border border-warning border-opacity-50 bg-label-warning mt-4 shadow-none d-none">
                <div class="card-body p-3 p-md-4">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                      <div class="avatar avatar-md bg-warning text-white rounded d-flex align-items-center justify-content-center shadow-sm">
                        <i class="icon-base ti tabler-shield-bolt fs-3"></i>
                      </div>
                      <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                          <h5 class="mb-0 fw-bold text-heading" id="claimCardCharName">-</h5>
                          <span class="badge bg-warning text-dark fw-bold font-monospace" id="claimCardType">MAIN</span>
                        </div>
                        <small class="text-body-secondary font-monospace" id="claimCardPlayerRank">-</small>
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge bg-warning text-dark font-monospace fs-6 px-3 py-1_5 shadow-xs" id="claimCardLevelBadge">Level 12</span>
                      <span class="badge bg-primary font-monospace fs-6 px-3 py-1_5 shadow-xs" id="claimCardXpBadge">161,506 XP</span>
                      <span class="badge bg-success font-monospace fs-6 px-3 py-1_5 shadow-xs" id="claimCardAllowanceBadge">0 SP</span>
                    </div>
                  </div>

                  <div class="row g-3 pt-3 border-top border-warning border-opacity-25 small font-monospace">
                    <div class="col-6 col-md-3">
                      <span class="text-body-secondary d-block">{{ __('Rank:') }}</span>
                      <span class="fw-bold text-heading fs-6" id="claimCardRank">-</span>
                    </div>
                    <div class="col-6 col-md-3">
                      <span class="text-body-secondary d-block">{{ __('Character Type:') }}</span>
                      <span class="fw-bold text-heading fs-6" id="claimCardTypeDetail">MAIN</span>
                    </div>
                    <div class="col-6 col-md-3">
                      <span class="text-body-secondary d-block">{{ __('Guild Allowance:') }}</span>
                      <span class="fw-bold text-success fs-6" id="claimCardAllowance">0 SP</span>
                    </div>
                    <div class="col-6 col-md-3">
                      <span class="text-body-secondary d-block">{{ __('Total XP Points:') }}</span>
                      <span class="fw-bold text-primary fs-6" id="claimCardXpRaw">0 XP</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Class & Race (Required) -->
          <div class="card border mb-4 shadow-none">
            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-xs bg-label-primary rounded d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-dna fs-6"></i>
                </span>
                <h6 class="card-title mb-0 fw-bold text-heading">
                  {{ __('2. Hero Race, Class & Archetype') }}
                </h6>
              </div>
            </div>

            <div class="card-body p-3 p-md-4">
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="form-label fw-medium" for="claimCharRace">
                    <i class="icon-base ti tabler-dna text-primary me-1"></i> {{ __('Character Race') }} <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="claimCharRace" required>
                    @if(!empty($pfRaces))
                      @foreach ($pfRaces as $rKey => $r)
                        <option value="{{ $r['name'] ?? ucfirst($rKey) }}" {{ strtolower($rKey) === 'human' ? 'selected' : '' }}>
                          {{ $r['name'] ?? ucfirst($rKey) }}
                        </option>
                      @endforeach
                    @else
                      <option value="Human" selected>Human</option>
                      <option value="Elf">Elf</option>
                      <option value="Dwarf">Dwarf</option>
                      <option value="Half-Elf">Half-Elf</option>
                      <option value="Half-Orc">Half-Orc</option>
                      <option value="Gnome">Gnome</option>
                      <option value="Halfling">Halfling</option>
                    @endif
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label fw-medium" for="claimCharClass">
                    <i class="icon-base ti tabler-swords text-primary me-1"></i> {{ __('Character Class') }} <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" id="claimCharClass" required>
                    @if(!empty($pfClasses))
                      @foreach ($pfClasses as $cKey => $c)
                        <option value="{{ $c['name'] ?? ucfirst($cKey) }}" {{ strtolower($cKey) === 'fighter' ? 'selected' : '' }}>
                          {{ $c['name'] ?? ucfirst($cKey) }} ({{ $c['hit_die'] ?? 'd10' }})
                        </option>
                      @endforeach
                    @else
                      <option value="Fighter" selected>Fighter (d10)</option>
                      <option value="Alchemist">Alchemist (d8)</option>
                      <option value="Rogue">Rogue (d8)</option>
                      <option value="Paladin">Paladin (d10)</option>
                      <option value="Wizard">Wizard (d6)</option>
                      <option value="Cleric">Cleric (d8)</option>
                      <option value="Ranger">Ranger (d10)</option>
                      <option value="Barbarian">Barbarian (d12)</option>
                      <option value="Bard">Bard (d8)</option>
                      <option value="Monk">Monk (d8)</option>
                      <option value="Sorcerer">Sorcerer (d6)</option>
                    @endif
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label fw-medium" for="claimCharArchetype">
                    <i class="icon-base ti tabler-crown text-warning me-1"></i> {{ __('Archetype (Subclass / Optional)') }}
                  </label>
                  <input type="text" class="form-control" id="claimCharArchetype" placeholder="{{ __('e.g. Grenadier, Mutagen Mauler...') }}" />
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Ability Scores (STR, DEX, CON, INT, WIS, CHA) -->
          <div class="card border mb-4 shadow-none">
            <div class="card-header bg-transparent border-bottom py-3 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-xs bg-label-info rounded d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-dice-6 fs-6"></i>
                </span>
                <h6 class="card-title mb-0 fw-bold text-heading">
                  {{ __('3. Ability Scores') }} <span class="text-danger">*</span>
                </h6>
              </div>
              <button type="button" class="btn btn-xs btn-label-warning fw-semibold waves-effect" id="btnClaimRollStats">
                <i class="icon-base ti tabler-dice me-1"></i> {{ __('Standard Heroic Array (16, 14, 14, 12, 12, 10)') }}
              </button>
            </div>

            <div class="card-body p-3 p-md-4">
              <div class="row g-2 text-center font-monospace">
                @php
                  $stats = ['str' => 'STR', 'dex' => 'DEX', 'con' => 'CON', 'int' => 'INT', 'wis' => 'WIS', 'cha' => 'CHA'];
                @endphp
                @foreach($stats as $k => $label)
                  <div class="col-4 col-md-2">
                    <div class="card border shadow-none bg-body p-2 h-100 text-center">
                      <span class="d-block fw-bold text-uppercase text-body-secondary small mb-1">{{ $label }}</span>
                      <input type="number" min="1" max="30" class="form-control form-control-sm text-center fw-bold claim-stat-input fs-5"
                             id="claimStat_{{ $k }}" data-stat="{{ $k }}" value="14" required />
                      <span class="badge bg-label-success mt-2 fw-bold claim-stat-mod" id="claimMod_{{ $k }}">+2</span>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <!-- Section 4: Live Combat & Progression Preview -->
          <div class="card border border-primary border-opacity-25 bg-label-primary shadow-none mb-4" id="claimLiveStatsPreview">
            <div class="card-body p-3 p-md-4">
              <div class="row g-3 text-center font-monospace align-items-center">
                <div class="col-6 col-md-3">
                  <span class="text-body-secondary small d-block mb-1">{{ __('Starting Level') }}</span>
                  <h4 class="mb-0 fw-bold text-primary" id="claimPreviewLevel">Level 12</h4>
                </div>
                <div class="col-6 col-md-3">
                  <span class="text-body-secondary small d-block mb-1">{{ __('Calculated HP') }}</span>
                  <h4 class="mb-0 fw-bold text-success" id="claimPreviewHp">100 HP</h4>
                </div>
                <div class="col-6 col-md-3">
                  <span class="text-body-secondary small d-block mb-1">{{ __('Base Attack (BAB)') }}</span>
                  <h4 class="mb-0 fw-bold text-warning" id="claimPreviewBab">+12/+7/+2</h4>
                </div>
                <div class="col-6 col-md-3">
                  <span class="text-body-secondary small d-block mb-1">{{ __('Fort / Ref / Will') }}</span>
                  <h4 class="mb-0 fw-bold text-info" id="claimPreviewSaves">+8 / +4 / +4</h4>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Actions Footer -->
          <div class="d-flex align-items-center justify-content-between pt-2">
            <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">
              <i class="icon-base ti tabler-x me-1"></i> {{ __('Cancel') }}
            </button>
            <button type="submit" class="btn btn-warning waves-effect waves-light shadow-sm fw-bold px-4" id="btnSubmitClaimCharacter" {{ $isFull ? 'disabled' : '' }}>
              <i class="icon-base ti tabler-user-check me-1_5"></i>
              <span id="btnSubmitClaimText">{{ __('Claim Character & Start at Level') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
/* Vuexy Claim Character Modal Enhancements */
#claimCharacterModal .modal-dialog {
  max-width: 980px;
}
#claimCharacterModal .modal-body {
  max-height: calc(88vh - 40px);
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
}
#claimCharacterModal .claim-stat-input {
  font-size: 1.15rem !important;
  font-weight: 700;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const claimModal = document.getElementById('claimCharacterModal');
  if (!claimModal) return;

  const claimSearchInput = document.getElementById('claimSearchInput');
  const btnClaimClearSearch = document.getElementById('btnClaimClearSearch');
  const claimCharacterSelect = document.getElementById('claimCharacterSelect');
  const claimCharCountBadge = document.getElementById('claimCharCountBadge');
  const claimSelectSubtext = document.getElementById('claimSelectSubtext');
  const claimSelectedDossierCard = document.getElementById('claimSelectedDossierCard');

  const claimCardCharName = document.getElementById('claimCardCharName');
  const claimCardPlayerRank = document.getElementById('claimCardPlayerRank');
  const claimCardLevelBadge = document.getElementById('claimCardLevelBadge');
  const claimCardXpBadge = document.getElementById('claimCardXpBadge');
  const claimCardAllowanceBadge = document.getElementById('claimCardAllowanceBadge');
  const claimCardRank = document.getElementById('claimCardRank');
  const claimCardType = document.getElementById('claimCardType');
  const claimCardTypeDetail = document.getElementById('claimCardTypeDetail');
  const claimCardAllowance = document.getElementById('claimCardAllowance');
  const claimCardXpRaw = document.getElementById('claimCardXpRaw');

  const claimCharRace = document.getElementById('claimCharRace');
  const claimCharClass = document.getElementById('claimCharClass');
  const claimCharArchetype = document.getElementById('claimCharArchetype');

  const btnClaimRollStats = document.getElementById('btnClaimRollStats');
  const btnSubmitClaimCharacter = document.getElementById('btnSubmitClaimCharacter');
  const btnSubmitClaimText = document.getElementById('btnSubmitClaimText');
  const formClaimCharacter = document.getElementById('formClaimCharacter');

  const claimPreviewHp = document.getElementById('claimPreviewHp');
  const claimPreviewBab = document.getElementById('claimPreviewBab');
  const claimPreviewSaves = document.getElementById('claimPreviewSaves');
  const claimPreviewLevel = document.getElementById('claimPreviewLevel');

  let activeClaimChar = null;
  let allCharactersList = [];
  let isClaimLoaded = false;

  function renderSelectOptions(list) {
    if (!claimCharacterSelect) return;
    claimCharacterSelect.innerHTML = '';
    const totalCount = allCharactersList.length;
    const currentCount = list.length;

    if (claimCharCountBadge) {
      claimCharCountBadge.textContent = `${currentCount} ${currentCount === 1 ? '{{ __("Character") }}' : '{{ __("Characters") }}'} (A-Z)`;
    }

    if (list.length === 0) {
      claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("No matching characters found") }}</option>';
      if (claimSelectSubtext) {
        claimSelectSubtext.textContent = '{{ __("No characters match your search filter.") }}';
      }
      return;
    }

    const placeholderText = list.length === totalCount
      ? `{{ __("Select a character (:count characters, A to Z)...", ["count" => ""]) }}${totalCount} {{ __("characters (A-Z)") }}`
      : `{{ __("Select from filtered matches (:count characters)...", ["count" => ""]) }}${currentCount} {{ __("matches") }}`;

    if (claimSelectSubtext) {
      claimSelectSubtext.textContent = list.length === totalCount
        ? `{{ __("All :count characters in registry sorted alphabetically A to Z.", ["count" => ""]) }}`.replace(':count', totalCount)
        : `{{ __("Showing :count matching characters.", ["count" => ""]) }}`.replace(':count', currentCount);
    }

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.disabled = true;
    defaultOpt.selected = !activeClaimChar;
    defaultOpt.textContent = placeholderText;
    claimCharacterSelect.appendChild(defaultOpt);

    list.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = `${c.name} (Lvl ${c.level} • ${Number(c.total_xp).toLocaleString()} XP • ${c.rank_name})`;
      if (activeClaimChar && String(activeClaimChar.id) === String(c.id)) {
        opt.selected = true;
      }
      claimCharacterSelect.appendChild(opt);
    });

    // Auto-select if exact match or single result during search
    if (list.length === 1 && claimSearchInput && claimSearchInput.value.trim().length > 0) {
      claimCharacterSelect.value = list[0].id;
      loadClaimDetails(list[0].id);
    }
  }

  function fetchClaimableCharacters() {
    if (claimCharCountBadge) {
      claimCharCountBadge.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 0.8rem; height: 0.8rem;"></span>{{ __("Loading characters...") }}';
    }
    claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("Loading all characters from XP records...") }}</option>';

    fetch('{{ route("valiant-shade.character.claimable-list") }}')
      .then(r => r.json())
      .then(data => {
        if (data.success && Array.isArray(data.characters)) {
          allCharactersList = data.characters;
          isClaimLoaded = true;
          renderSelectOptions(allCharactersList);
        } else {
          claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("No characters found") }}</option>';
          if (claimCharCountBadge) claimCharCountBadge.textContent = '0 {{ __("characters") }}';
        }
      })
      .catch(err => {
        console.error('Failed to load characters:', err);
        claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("Loading failed") }}</option>';
        if (claimCharCountBadge) claimCharCountBadge.textContent = '{{ __("Error loading") }}';
      });
  }

  // Pre-load characters when modal opens
  claimModal.addEventListener('show.bs.modal', function () {
    if (!isClaimLoaded) {
      fetchClaimableCharacters();
    }
  });

  // Client-side instant real-time filtering
  function handleFilter() {
    if (!claimSearchInput) return;
    const term = (claimSearchInput.value || '').trim().toLowerCase();
    if (term.length > 0) {
      if (btnClaimClearSearch) btnClaimClearSearch.classList.remove('d-none');
      const filtered = allCharactersList.filter(c => {
        const nameMatch = c.name && c.name.toLowerCase().includes(term);
        const rankMatch = c.rank_name && c.rank_name.toLowerCase().includes(term);
        const typeMatch = c.character_type && c.character_type.toLowerCase().includes(term);
        return nameMatch || rankMatch || typeMatch;
      });
      renderSelectOptions(filtered);
    } else {
      if (btnClaimClearSearch) btnClaimClearSearch.classList.add('d-none');
      renderSelectOptions(allCharactersList);
    }
  }

  if (claimSearchInput) {
    claimSearchInput.addEventListener('input', handleFilter);
    claimSearchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        claimSearchInput.value = '';
        handleFilter();
      }
    });
  }

  if (btnClaimClearSearch) {
    btnClaimClearSearch.addEventListener('click', function () {
      claimSearchInput.value = '';
      handleFilter();
      claimSearchInput.focus();
    });
  }

  // Load selected character details
  function loadClaimDetails(charId) {
    const detailsBase = '{{ url("/valiant-shade/character/claimable-details") }}';
    fetch(`${detailsBase}/${charId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success || !data.character) {
          alert(data.message || '{{ __("Could not retrieve character details.") }}');
          return;
        }
        renderClaimCharacter(data.character);
      })
      .catch(err => {
        console.error('Details error:', err);
        alert('{{ __("Server error while loading character details.") }}');
      });
  }

  claimCharacterSelect.addEventListener('change', function () {
    if (this.value) {
      loadClaimDetails(this.value);
    }
  });

  function renderClaimCharacter(char) {
    activeClaimChar = char;
    if (claimSelectedDossierCard) claimSelectedDossierCard.classList.remove('d-none');

    if (claimCardCharName) claimCardCharName.textContent = char.name;
    if (claimCardPlayerRank) claimCardPlayerRank.textContent = `${char.rank || 'Member'} • ${char.character_type || 'MAIN'}`;
    if (claimCardLevelBadge) claimCardLevelBadge.textContent = `Level ${char.level}`;
    if (claimCardXpBadge) claimCardXpBadge.textContent = `${Number(char.total_xp).toLocaleString()} XP`;
    if (claimCardXpRaw) claimCardXpRaw.textContent = `${Number(char.total_xp).toLocaleString()} XP`;
    if (claimCardRank) claimCardRank.textContent = char.rank || 'Member';
    if (claimCardType) claimCardType.textContent = char.character_type || 'MAIN';
    if (claimCardTypeDetail) claimCardTypeDetail.textContent = char.character_type || 'MAIN';
    if (claimCardAllowance) claimCardAllowance.textContent = `${Number(char.allowance_sp).toLocaleString()} SP`;
    if (claimCardAllowanceBadge) claimCardAllowanceBadge.textContent = `${Number(char.allowance_sp).toLocaleString()} SP`;

    // Set suggested race
    if (char.suggested_race && claimCharRace) {
      for (let i = 0; i < claimCharRace.options.length; i++) {
        if (claimCharRace.options[i].value.toLowerCase() === char.suggested_race.toLowerCase()) {
          claimCharRace.selectedIndex = i;
          break;
        }
      }
    }

    // Set suggested class
    if (char.suggested_class && claimCharClass) {
      for (let i = 0; i < claimCharClass.options.length; i++) {
        if (claimCharClass.options[i].value.toLowerCase().includes(char.suggested_class.toLowerCase()) ||
            char.suggested_class.toLowerCase().includes(claimCharClass.options[i].value.toLowerCase())) {
          claimCharClass.selectedIndex = i;
          break;
        }
      }
    }

    // Set suggested archetype
    if (claimCharArchetype) {
      claimCharArchetype.value = char.suggested_archetype || '';
    }

    // Set suggested stats
    if (char.suggested_stats) {
      ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(stat => {
        const inp = document.getElementById('claimStat_' + stat);
        if (inp && char.suggested_stats[stat] && char.suggested_stats[stat] > 0) {
          inp.value = char.suggested_stats[stat];
        }
      });
      updateAllStatMods();
    }

    if (btnSubmitClaimText) {
      btnSubmitClaimText.textContent = `{{ __('Claim') }} ${char.name} ({{ __('Level') }} ${char.level})`;
    }

    // Live preview
    updateClaimLivePreview();
  }

  function updateClaimLivePreview() {
    if (!activeClaimChar) return;
    const lvl = activeClaimChar.level || 1;
    const conVal = parseInt(document.getElementById('claimStat_con')?.value, 10) || 10;
    const conMod = Math.floor((conVal - 10) / 2);

    const clsName = claimCharClass ? (claimCharClass.value || 'Fighter') : 'Fighter';
    let hd = 10;
    let babProg = 'full';
    let goodFort = false, goodRef = false, goodWill = false;

    const cLower = clsName.toLowerCase();
    if (cLower.includes('barbarian')) {
      hd = 12; babProg = 'full'; goodFort = true;
    } else if (cLower.includes('fighter') || cLower.includes('paladin') || cLower.includes('ranger') || cLower.includes('slayer')) {
      hd = 10; babProg = 'full'; goodFort = true;
    } else if (cLower.includes('alchemist')) {
      hd = 8; babProg = '3/4'; goodFort = true; goodRef = true;
    } else if (cLower.includes('rogue')) {
      hd = 8; babProg = '3/4'; goodRef = true;
    } else if (cLower.includes('cleric') || cLower.includes('druid') || cLower.includes('inquisitor') || cLower.includes('oracle')) {
      hd = 8; babProg = '3/4'; goodFort = true; goodWill = true;
    } else if (cLower.includes('bard') || cLower.includes('monk')) {
      hd = 8; babProg = '3/4'; goodRef = true; goodWill = true;
      if (cLower.includes('monk')) goodFort = true;
    } else if (cLower.includes('wizard') || cLower.includes('sorcerer') || cLower.includes('witch')) {
      hd = 6; babProg = '1/2'; goodWill = true;
    } else {
      hd = 8; babProg = '3/4'; goodFort = true;
    }

    const firstLvlHp = Math.max(1, hd + conMod);
    const perLvlHp = Math.max(1, Math.ceil(hd / 2) + 1 + conMod);
    const totalHp = firstLvlHp + Math.max(0, lvl - 1) * perLvlHp;

    // BAB
    let babStr = '+1';
    if (babProg === 'full') {
      babStr = '+' + lvl;
      if (lvl >= 6) babStr += '/+' + (lvl - 5);
      if (lvl >= 11) babStr += '/+' + (lvl - 10);
      if (lvl >= 16) babStr += '/+' + (lvl - 15);
    } else if (babProg === '3/4') {
      const b = Math.floor(lvl * 0.75);
      babStr = '+' + b;
      if (b >= 6) babStr += '/+' + (b - 5);
      if (b >= 11) babStr += '/+' + (b - 10);
    } else {
      const b = Math.floor(lvl * 0.5);
      babStr = '+' + b;
      if (b >= 6) babStr += '/+' + (b - 5);
    }

    // Saves
    const calcSave = (isGood) => isGood ? Math.floor(2 + lvl / 2) : Math.floor(lvl / 3);
    const fort = calcSave(goodFort);
    const ref = calcSave(goodRef);
    const will = calcSave(goodWill);

    if (claimPreviewHp) claimPreviewHp.textContent = `${totalHp} HP`;
    if (claimPreviewBab) claimPreviewBab.textContent = babStr;
    if (claimPreviewSaves) claimPreviewSaves.textContent = `+${fort} / +${ref} / +${will}`;
    if (claimPreviewLevel) claimPreviewLevel.textContent = `Level ${lvl}`;
  }

  function updateAllStatMods() {
    ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(k => {
      const inp = document.getElementById('claimStat_' + k);
      const badge = document.getElementById('claimMod_' + k);
      if (inp && badge) {
        const val = parseInt(inp.value, 10) || 10;
        const mod = Math.floor((val - 10) / 2);
        badge.textContent = (mod >= 0 ? '+' : '') + mod;
      }
    });
  }

  document.querySelectorAll('.claim-stat-input').forEach(inp => {
    inp.addEventListener('input', function () {
      updateAllStatMods();
      updateClaimLivePreview();
    });
  });

  if (btnClaimRollStats) {
    btnClaimRollStats.addEventListener('click', function () {
      const array = { str: 16, dex: 14, con: 14, int: 12, wis: 12, cha: 10 };
      Object.keys(array).forEach(k => {
        const inp = document.getElementById('claimStat_' + k);
        if (inp) inp.value = array[k];
      });
      updateAllStatMods();
      updateClaimLivePreview();
    });
  }

  if (claimCharClass) {
    claimCharClass.addEventListener('change', updateClaimLivePreview);
  }

  // Form submission with strict validation
  formClaimCharacter.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!activeClaimChar || !claimCharacterSelect.value) {
      alert('{{ __("Please select a character to claim.") }}');
      claimCharacterSelect.focus();
      return;
    }

    const charRace = claimCharRace ? claimCharRace.value : '';
    const charClass = claimCharClass ? claimCharClass.value : '';
    if (!charRace) {
      alert('{{ __("Character Race is required.") }}');
      claimCharRace?.focus();
      return;
    }
    if (!charClass) {
      alert('{{ __("Character Class is required.") }}');
      claimCharClass?.focus();
      return;
    }

    // Stats validation
    const stats = {
      str: parseInt(document.getElementById('claimStat_str')?.value, 10),
      dex: parseInt(document.getElementById('claimStat_dex')?.value, 10),
      con: parseInt(document.getElementById('claimStat_con')?.value, 10),
      int: parseInt(document.getElementById('claimStat_int')?.value, 10),
      wis: parseInt(document.getElementById('claimStat_wis')?.value, 10),
      cha: parseInt(document.getElementById('claimStat_cha')?.value, 10),
    };

    for (const [k, v] of Object.entries(stats)) {
      if (isNaN(v) || v < 1 || v > 30) {
        alert(`{{ __('Please enter a valid') }} ${k.toUpperCase()} {{ __('value (1-30).') }}`);
        document.getElementById('claimStat_' + k)?.focus();
        return;
      }
    }

    const payload = {
      character_id: activeClaimChar.id,
      race: charRace,
      class_name: charClass,
      archetype: claimCharArchetype ? claimCharArchetype.value.trim() : '',
      str: stats.str,
      dex: stats.dex,
      con: stats.con,
      int: stats.int,
      wis: stats.wis,
      cha: stats.cha,
    };

    btnSubmitClaimCharacter.disabled = true;
    btnSubmitClaimText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Claiming Character...") }}';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      || document.querySelector('#formClaimCharacter input[name="_token"]')?.value
      || '';

    fetch('{{ route("valiant-shade.character.claim") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json().then(data => ({ status: res.status, body: data })))
    .then(({ status, body }) => {
      if (status >= 200 && status < 300 && body.success) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Character Claimed!") }}',
            text: body.message || `${activeClaimChar.name} {{ __("successfully claimed at level") }} ${activeClaimChar.level}!`,
            timer: 2000,
            showConfirmButton: false,
          }).then(() => window.location.reload());
        } else {
          alert(body.message || `${activeClaimChar.name} {{ __("successfully claimed!") }}`);
          window.location.reload();
        }
      } else {
        btnSubmitClaimCharacter.disabled = false;
        btnSubmitClaimText.textContent = `{{ __('Claim') }} ${activeClaimChar.name} ({{ __('Level') }} ${activeClaimChar.level})`;
        const errMsg = body.message || (body.errors ? Object.values(body.errors).flat().join('\n') : '{{ __("Failed to claim character.") }}');
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: errMsg });
        } else {
          alert(errMsg);
        }
      }
    })
    .catch(err => {
      console.error('Claim submit error:', err);
      btnSubmitClaimCharacter.disabled = false;
      btnSubmitClaimText.textContent = `{{ __('Claim') }} ${activeClaimChar.name} ({{ __('Level') }} ${activeClaimChar.level})`;
      alert('{{ __("Could not connect to server. Please try again.") }}');
    });
  });
});
</script>