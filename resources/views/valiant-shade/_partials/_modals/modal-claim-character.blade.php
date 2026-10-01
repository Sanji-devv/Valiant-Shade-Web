<!-- Modal Claim Character from XP List -->
<div class="modal fade" id="claimCharacterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form id="formClaimCharacter" class="modal-content modal-dossier claim-modal-content">
      @csrf

        <!-- Modal Header -->
        <div class="modal-header claim-modal-header d-flex align-items-center justify-content-between p-3 p-md-4">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
              <i class="icon-base ti tabler-user-check fs-2 text-dark"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-bold text-white d-flex align-items-center gap-2">
                {{ __('XP Listesinden Karakter Sahiplen (Claim Character)') }}
                <span class="badge bg-warning text-dark font-monospace fs-6 px-2 py-0_5">{{ __('Mevcut XP & Seviye') }}</span>
              </h4>
              <small class="claim-subtitle font-monospace">{{ __('Master Sheet XP listesindeki karakterinizi hak ettiği gerçek seviyesi, rütbesi ve birikmiş puanlarıyla başlatın.') }}</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body claim-modal-body p-3 p-md-4">

          <!-- Alert Note -->
          <div class="alert claim-alert border d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="icon-base ti tabler-shield-lock fs-3 text-warning flex-shrink-0"></i>
            <div class="small claim-alert-text">
              <strong class="text-warning">{{ __('Önemli Kural & Bilgilendirme:') }}</strong>
              {{ __('Seçtiğiniz karakter sıfırdan değil, XP listesindeki gerçek seviyesinden başlatılacaktır (Örn. Thoma Quickfire için doğrudan 12. Seviye). Karakterin Sınıf ve nitelik puanlarını onaylayarak profilinize ekleyebilirsiniz.') }}
            </div>
          </div>

          <!-- Section 1: Karakter Seçimi (Autocomplete / Search) -->
          <div class="claim-panel-card p-3 mb-4">
            <label class="form-label fw-bold text-white fs-6 mb-2">
              <i class="icon-base ti tabler-user-search text-warning me-1"></i>
              {{ __('1. Sahiplenilecek Karakteri Seçin (XP Kayıtları)') }} <span class="text-danger">*</span>
            </label>

            <div class="row g-2 align-items-center">
              <div class="col-12 col-md-8">
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="icon-base ti tabler-search text-light"></i></span>
                  <input type="text" class="form-control font-monospace"
                         id="claimSearchInput" placeholder="{{ __('Karakter adı yazın (Örn: Thoma Quickfire, Bart, Almira Blade, Kerrik...)') }}" autocomplete="off" />
                  <button type="button" class="btn btn-warning btn-sm fw-bold px-3" id="btnClaimSearch">{{ __('Ara') }}</button>
                </div>
              </div>
              <div class="col-12 col-md-4">
                <select id="claimCharacterSelect" class="form-select font-monospace" required>
                  <option value="" disabled selected>{{ __('Karakter seçiniz...') }}</option>
                </select>
              </div>
            </div>

            <!-- Dynamic Selected Character Dossier Info Card -->
            <div id="claimSelectedDossierCard" class="claim-dossier-card mt-3 p-3 rounded-3 d-none">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5">
                    <i class="icon-base ti tabler-flame"></i>
                  </div>
                  <div>
                    <h5 class="mb-0 fw-bold text-white" id="claimCardCharName">-</h5>
                    <small class="claim-subtitle font-monospace" id="claimCardPlayerRank">-</small>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-warning text-dark font-monospace fs-6 px-3 py-1" id="claimCardLevelBadge">Level 12</span>
                  <span class="badge bg-info text-white font-monospace fs-6 px-3 py-1" id="claimCardXpBadge">161,506.57 XP</span>
                </div>
              </div>
              <div class="row g-2 font-monospace small pt-2 border-top border-secondary border-opacity-30" style="font-size: 0.82rem;">
                <div class="col-12 col-md-4">
                  <span class="claim-label-dim d-block">{{ __('Rütbe:') }}</span>
                  <strong class="text-warning fs-6" id="claimCardRank">-</strong>
                </div>
                <div class="col-12 col-md-4">
                  <span class="claim-label-dim d-block">{{ __('Karakter Tipi:') }}</span>
                  <strong class="text-white fs-6" id="claimCardType">MAIN</strong>
                </div>
                <div class="col-12 col-md-4">
                  <span class="claim-label-dim d-block">{{ __('Lonca Ödeneği:') }}</span>
                  <strong class="text-info fs-6" id="claimCardAllowance">0 SP</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Sınıf & Irk (Zorunlu) -->
          <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
              <label class="form-label fw-bold text-white">
                <i class="icon-base ti tabler-dna text-info me-1"></i> {{ __('Karakter Irkı (Race)') }} <span class="text-danger">*</span>
              </label>
              <select class="form-select text-white" id="claimCharRace" required>
                @if(!empty($pfRaces))
                  @foreach ($pfRaces as $rKey => $r)
                    <option value="{{ $r['name'] ?? $rKey }}">{{ $r['name'] ?? $rKey }}</option>
                  @endforeach
                @else
                  <option value="Human">Human</option>
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
              <label class="form-label fw-bold text-white">
                <i class="icon-base ti tabler-swords text-info me-1"></i> {{ __('Karakter Sınıfı (Class)') }} <span class="text-danger">*</span>
              </label>
              <select class="form-select text-white" id="claimCharClass" required>
                @if(!empty($pfClasses))
                  @foreach ($pfClasses as $cKey => $c)
                    <option value="{{ $c['name'] ?? $cKey }}">{{ $c['name'] ?? $cKey }} ({{ $c['hit_die'] ?? 'd10' }})</option>
                  @endforeach
                @else
                  <option value="Fighter">Fighter (d10)</option>
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
              <label class="form-label fw-semibold text-white">
                <i class="icon-base ti tabler-crown text-warning me-1"></i> {{ __('Archetype (Alt Sınıf / Varsa)') }}
              </label>
              <input type="text" class="form-control text-white" id="claimCharArchetype" placeholder="{{ __('Örn: Grenadier, Mutagen Mauler...') }}" />
            </div>
          </div>

          <!-- Section 3: Ability Scores (STR, DEX, CON, INT, WIS, CHA) -->
          <div class="claim-panel-card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="fw-bold text-white mb-0 d-flex align-items-center gap-2 fs-5">
                <i class="icon-base ti tabler-dice-6 text-warning fs-4"></i>
                <span>{{ __('Nitelik Puanları (Ability Scores)') }} <span class="text-danger">*</span></span>
              </h6>
              <button type="button" class="btn btn-warning btn-sm fw-bold px-3" id="btnClaimRollStats">
                <i class="icon-base ti tabler-dice me-1"></i> {{ __('Standard Heroic Array (16, 14, 14, 12, 12, 10)') }}
              </button>
            </div>

            <div class="row g-2 text-center font-monospace">
              @php
                $stats = ['str' => 'STR', 'dex' => 'DEX', 'con' => 'CON', 'int' => 'INT', 'wis' => 'WIS', 'cha' => 'CHA'];
              @endphp
              @foreach($stats as $k => $label)
                <div class="col-4 col-md-2">
                  <div class="claim-stat-box p-2 rounded">
                    <span class="d-block fw-bold text-info small mb-1">{{ $label }}</span>
                    <input type="number" min="1" max="30" class="form-control form-control-sm text-center fw-bold claim-stat-input text-white"
                           id="claimStat_{{ $k }}" data-stat="{{ $k }}" value="14" required />
                    <span class="small fw-bold text-success claim-stat-mod mt-1 d-block" id="claimMod_{{ $k }}">+2</span>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- Section 4: Canlı Savaş Önizlemesi (Seviye Hesabı) -->
          <div class="claim-preview-box p-3 rounded-3 text-center font-monospace" id="claimLiveStatsPreview">
            <div class="row g-2 small">
              <div class="col-3">
                <span class="d-block claim-label-dim">{{ __('Başlangıç HP:') }}</span>
                <strong class="text-success fs-5" id="claimPreviewHp">100 HP</strong>
              </div>
              <div class="col-3">
                <span class="d-block claim-label-dim">{{ __('BAB:') }}</span>
                <strong class="text-warning fs-5" id="claimPreviewBab">+12/+7/+2</strong>
              </div>
              <div class="col-3">
                <span class="d-block claim-label-dim">{{ __('Fort / Ref / Will:') }}</span>
                <strong class="text-info fs-5" id="claimPreviewSaves">+8 / +4 / +4</strong>
              </div>
              <div class="col-3">
                <span class="d-block claim-label-dim">{{ __('Başlangıç Seviyesi:') }}</span>
                <strong class="text-warning fs-5" id="claimPreviewLevel">Level 12</strong>
              </div>
            </div>
          </div>

        </div>

        <!-- Modal Footer -->
        <div class="modal-footer claim-modal-footer p-3 p-md-4 d-flex align-items-center justify-content-between">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3 text-light" data-bs-dismiss="modal">
            {{ __('Vazgeç') }}
          </button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold px-4 shadow-md" id="btnSubmitClaimCharacter">
            <i class="icon-base ti tabler-user-check me-1 fs-5"></i>
            <span id="btnSubmitClaimText">{{ __('Karakteri Sahiplen & Seviye ile Başlat') }}</span>
          </button>
        </div>
      </form>
  </div>
</div>

<style>
/* ==========================================================================
   Claim Character Modal - High Contrast, Smooth Scrolling & Modern Dark Design
   ========================================================================== */
#claimCharacterModal .modal-dialog {
  max-height: 90vh !important;
}

#claimCharacterModal .claim-modal-content {
  background: #111424 !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.85) !important;
  border-radius: 0.75rem;
  max-height: 90vh !important;
  display: flex !important;
  flex-direction: column !important;
  overflow: hidden !important;
}

#claimCharacterModal .claim-modal-header {
  background: #161a30 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
  flex-shrink: 0 !important;
}

#claimCharacterModal .claim-modal-footer {
  background: #161a30 !important;
  border-top: 1px solid rgba(255, 255, 255, 0.12) !important;
  flex-shrink: 0 !important;
}

#claimCharacterModal .claim-modal-body {
  overflow-y: auto !important;
  overflow-x: hidden !important;
  flex: 1 1 auto !important;
  max-height: calc(90vh - 160px) !important;
  scroll-behavior: smooth;
  scrollbar-width: thin;
  scrollbar-color: rgba(245, 158, 11, 0.45) #0d101e;
}

/* Custom Amber/Dark Scrollbar */
#claimCharacterModal .claim-modal-body::-webkit-scrollbar {
  width: 10px;
}

#claimCharacterModal .claim-modal-body::-webkit-scrollbar-track {
  background: #0d101e;
  border-radius: 5px;
}

#claimCharacterModal .claim-modal-body::-webkit-scrollbar-thumb {
  background: rgba(245, 158, 11, 0.45);
  border-radius: 5px;
  border: 2px solid #0d101e;
}

#claimCharacterModal .claim-modal-body::-webkit-scrollbar-thumb:hover {
  background: #f59e0b;
}

#claimCharacterModal .claim-subtitle {
  color: #cbd5e1 !important;
  font-size: 0.85rem;
  line-height: 1.4;
}

#claimCharacterModal .claim-label-dim {
  color: #94a3b8 !important;
  font-weight: 500;
}

#claimCharacterModal .claim-alert {
  background: rgba(245, 158, 11, 0.14) !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
  border-radius: 0.5rem;
}

#claimCharacterModal .claim-alert-text {
  color: #fef3c7 !important;
  line-height: 1.5;
}

/* Panel Cards */
#claimCharacterModal .claim-panel-card {
  background: #181c33 !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  border-radius: 0.625rem;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35);
}

/* Inner Slots & Boxes */
#claimCharacterModal .claim-stat-box,
#claimCharacterModal .claim-preview-box {
  background: #0d101e !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
}

#claimCharacterModal .claim-dossier-card {
  background: rgba(245, 158, 11, 0.09) !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
}

/* Form Controls & Inputs */
#claimCharacterModal .form-label {
  color: #f8fafc !important;
}

#claimCharacterModal .form-control,
#claimCharacterModal .form-select,
#claimCharacterModal .input-group-text {
  background-color: #0a0d18 !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.22) !important;
}

#claimCharacterModal .form-control:focus,
#claimCharacterModal .form-select:focus {
  background-color: #0e1222 !important;
  color: #ffffff !important;
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25) !important;
}

#claimCharacterModal .form-control::placeholder {
  color: #94a3b8 !important;
  opacity: 1 !important;
}

#claimCharacterModal .form-select option {
  background-color: #111424 !important;
  color: #ffffff !important;
}

#claimCharacterModal .claim-stat-input {
  font-size: 1.15rem !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const claimModal = document.getElementById('claimCharacterModal');
  if (!claimModal) return;

  const claimSearchInput = document.getElementById('claimSearchInput');
  const btnClaimSearch = document.getElementById('btnClaimSearch');
  const claimCharacterSelect = document.getElementById('claimCharacterSelect');
  const claimSelectedDossierCard = document.getElementById('claimSelectedDossierCard');

  const claimCardCharName = document.getElementById('claimCardCharName');
  const claimCardPlayerRank = document.getElementById('claimCardPlayerRank');
  const claimCardLevelBadge = document.getElementById('claimCardLevelBadge');
  const claimCardXpBadge = document.getElementById('claimCardXpBadge');
  const claimCardRank = document.getElementById('claimCardRank');
  const claimCardType = document.getElementById('claimCardType');
  const claimCardAllowance = document.getElementById('claimCardAllowance');

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
  let isClaimLoaded = false;

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function fetchClaimableCharacters(search = '') {
    const baseUrl = '{{ route("valiant-shade.character.claimable-list") }}';
    const url = baseUrl + (search ? '?q=' + encodeURIComponent(search) : '');
    claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("Karakterler aranıyor...") }}</option>';

    fetch(url)
      .then(r => r.json())
      .then(data => {
        if (data.success && Array.isArray(data.characters)) {
          if (data.characters.length === 0) {
            claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("Karakter bulunamadı") }}</option>';
            return;
          }
          claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("Karakter seçiniz (Toplam: ") }}' + data.characters.length + ')...</option>';
          data.characters.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.name} (Lvl ${c.level} • ${Number(c.total_xp).toLocaleString()} XP • ${c.rank_name})`;
            claimCharacterSelect.appendChild(opt);
          });

          // If search matched single character, auto-select and load details
          if (search && data.characters.length === 1) {
            claimCharacterSelect.value = data.characters[0].id;
            loadClaimDetails(data.characters[0].id);
          }
        }
      })
      .catch(err => {
        console.error('Failed to load characters:', err);
        claimCharacterSelect.innerHTML = '<option value="" disabled selected>{{ __("Yükleme başarısız") }}</option>';
      });
  }

  // Pre-load characters on modal open
  claimModal.addEventListener('show.bs.modal', function () {
    if (!isClaimLoaded) {
      fetchClaimableCharacters();
      isClaimLoaded = true;
    }
  });

  // Debounced search
  let searchTimeout = null;
  if (claimSearchInput) {
    claimSearchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchClaimableCharacters(claimSearchInput.value.trim());
      }, 350);
    });

    claimSearchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        fetchClaimableCharacters(claimSearchInput.value.trim());
      }
    });
  }

  if (btnClaimSearch) {
    btnClaimSearch.addEventListener('click', function () {
      fetchClaimableCharacters(claimSearchInput.value.trim());
    });
  }

  // Load selected character details
  function loadClaimDetails(charId) {
    const detailsBase = '{{ url("/valiant-shade/character/claimable-details") }}';
    fetch(`${detailsBase}/${charId}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success || !data.character) {
          alert(data.message || 'Karakter detayları alınamadı.');
          return;
        }
        renderClaimCharacter(data.character);
      })
      .catch(err => {
        console.error('Details error:', err);
        alert('Karakter detayları yüklenirken sunucu hatası oluştu.');
      });
  }

  claimCharacterSelect.addEventListener('change', function () {
    if (this.value) {
      loadClaimDetails(this.value);
    }
  });

  function renderClaimCharacter(char) {
    activeClaimChar = char;
    claimSelectedDossierCard.classList.remove('d-none');

    claimCardCharName.textContent = char.name;
    claimCardPlayerRank.textContent = `${char.rank || 'Üye'} • ${char.character_type || 'MAIN'}`;
    claimCardLevelBadge.textContent = `Level ${char.level}`;
    claimCardXpBadge.textContent = `${Number(char.total_xp).toLocaleString()} XP`;
    claimCardRank.textContent = char.rank || 'Üye';
    claimCardType.textContent = char.character_type || 'MAIN';
    claimCardAllowance.textContent = `${Number(char.allowance_sp).toLocaleString()} SP`;

    // Set suggested race
    if (char.suggested_race) {
      for (let i = 0; i < claimCharRace.options.length; i++) {
        if (claimCharRace.options[i].value.toLowerCase() === char.suggested_race.toLowerCase()) {
          claimCharRace.selectedIndex = i;
          break;
        }
      }
    }

    // Set suggested class
    if (char.suggested_class) {
      for (let i = 0; i < claimCharClass.options.length; i++) {
        if (claimCharClass.options[i].value.toLowerCase().includes(char.suggested_class.toLowerCase()) ||
            char.suggested_class.toLowerCase().includes(claimCharClass.options[i].value.toLowerCase())) {
          claimCharClass.selectedIndex = i;
          break;
        }
      }
    }

    // Set suggested archetype
    claimCharArchetype.value = char.suggested_archetype || '';

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

    // Live preview
    updateClaimLivePreview();
  }

  function updateClaimLivePreview() {
    if (!activeClaimChar) return;
    const lvl = activeClaimChar.level || 1;
    const conVal = parseInt(document.getElementById('claimStat_con')?.value, 10) || 10;
    const conMod = Math.floor((conVal - 10) / 2);

    const clsName = claimCharClass.value || 'Fighter';
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

    claimPreviewHp.textContent = `${totalHp} HP`;
    claimPreviewBab.textContent = babStr;
    claimPreviewSaves.textContent = `+${fort} / +${ref} / +${will}`;
    claimPreviewLevel.textContent = `Level ${lvl}`;
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

  claimCharClass.addEventListener('change', updateClaimLivePreview);

  // Form submission with strict validation
  formClaimCharacter.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!activeClaimChar || !claimCharacterSelect.value) {
      alert('Lütfen sahiplenmek istediğiniz bir karakteri seçiniz.');
      claimCharacterSelect.focus();
      return;
    }

    const charRace = claimCharRace.value;
    const charClass = claimCharClass.value;
    if (!charRace) {
      alert('Karakter Irkı (Race) seçilmesi zorunludur.');
      claimCharRace.focus();
      return;
    }
    if (!charClass) {
      alert('Karakter Sınıfı (Class) seçilmesi zorunludur.');
      claimCharClass.focus();
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
        alert(`Lütfen geçerli bir ${k.toUpperCase()} değeri giriniz (1-30).`);
        document.getElementById('claimStat_' + k)?.focus();
        return;
      }
    }

    const payload = {
      character_id: activeClaimChar.id,
      race: charRace,
      class_name: charClass,
      archetype: claimCharArchetype.value.trim(),
      str: stats.str,
      dex: stats.dex,
      con: stats.con,
      int: stats.int,
      wis: stats.wis,
      cha: stats.cha,
    };

    btnSubmitClaimCharacter.disabled = true;
    btnSubmitClaimText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Karakter Sahipleniliyor...';

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
            title: 'Karakter Sahiplenildi!',
            text: body.message || `${activeClaimChar.name} başarıyla ${activeClaimChar.level}. seviyede profilinize eklendi!`,
            timer: 2000,
            showConfirmButton: false,
          }).then(() => window.location.reload());
        } else {
          alert(body.message || `${activeClaimChar.name} başarıyla sahiplenildi!`);
          window.location.reload();
        }
      } else {
        btnSubmitClaimCharacter.disabled = false;
        btnSubmitClaimText.textContent = '{{ __("Karakteri Sahiplen & Seviye ile Başlat") }}';
        const errMsg = body.message || (body.errors ? Object.values(body.errors).flat().join('\n') : 'Karakter sahiplenilemedi.');
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Hata', text: errMsg });
        } else {
          alert(errMsg);
        }
      }
    })
    .catch(err => {
      console.error('Claim submit error:', err);
      btnSubmitClaimCharacter.disabled = false;
      btnSubmitClaimText.textContent = '{{ __("Karakteri Sahiplen & Seviye ile Başlat") }}';
      alert('Sunucu ile bağlantı kurulamadı. Lütfen tekrar deneyiniz.');
    });
  });
});
</script>
