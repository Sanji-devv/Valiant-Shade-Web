@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Pathfinder 1e Character Sheet - Valiant Shade')

@section('page-style')
<style>
  :root {
    --pf-sheet-bg: #222338;
    --pf-card-bg: #2b2c40;
    --pf-card-inner: #252636;
    --pf-gold: #e2b96f;
    --pf-gold-dark: #b8860b;
    --pf-border: rgba(226, 185, 111, 0.28);
    --pf-border-subtle: rgba(255, 255, 255, 0.08);
    --pf-purple: #7367f0;
    --pf-text-heading: #dbdbe4;
    --pf-text-muted: #a8aaae;
    --pf-banner-bg: #181926;
  }

  .pf-sheet-container {
    max-width: 1560px;
    margin: 0 auto;
  }

  /* Sheet Top Action Header */
  .pf-header-bar {
    background: linear-gradient(135deg, rgba(43, 44, 64, 0.98), rgba(34, 35, 56, 0.99));
    border: 1px solid var(--pf-border);
    border-radius: 0.75rem;
  }

  /* Authentic Pathfinder Banner Titles */
  .pf-banner-header {
    background: var(--pf-banner-bg);
    color: var(--pf-gold);
    border: 1px solid var(--pf-border);
    border-radius: 0.4rem;
    font-weight: 800;
    font-size: 0.85rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 0.35rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.6rem;
    box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5);
  }

  .pf-banner-sub {
    font-size: 0.72rem;
    color: var(--pf-text-muted);
    font-weight: 600;
    letter-spacing: 0.5px;
  }

  /* Card Containers */
  .pf-card {
    background-color: var(--pf-card-bg);
    border: 1px solid var(--pf-border-subtle);
    border-radius: 0.55rem;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
    margin-bottom: 1rem;
    transition: border-color 0.2s;
  }
  .pf-card:hover {
    border-color: rgba(226, 185, 111, 0.35);
  }

  /* Stat & Formula Input Boxes */
  .pf-box-input {
    background-color: rgba(18, 19, 32, 0.85) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    color: #fff !important;
    font-weight: 700;
    text-align: center;
    border-radius: 0.35rem;
    padding: 0.3rem 0.35rem;
    font-size: 0.88rem;
    transition: all 0.2s ease;
  }
  .pf-box-input:focus {
    border-color: var(--pf-gold) !important;
    box-shadow: 0 0 10px rgba(226, 185, 111, 0.35) !important;
    background-color: rgba(18, 19, 32, 0.98) !important;
  }

  .pf-input-compact {
    padding: 0.2rem 0.3rem !important;
    font-size: 0.82rem !important;
    height: calc(1.5em + 0.5rem + 2px) !important;
  }

  /* Large Stat Modifiers & Rollable Buttons */
  .pf-mod-box {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.22), rgba(43, 44, 73, 0.65));
    border: 1.5px solid var(--pf-purple);
    border-radius: 0.45rem;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
    text-align: center;
    padding: 0.25rem 0.2rem;
  }
  .pf-mod-box:hover {
    transform: translateY(-2px);
    border-color: var(--pf-gold);
    box-shadow: 0 0 12px rgba(226, 185, 111, 0.4);
  }
  .pf-mod-box:active {
    transform: scale(0.96);
  }

  /* Formula Math Chains */
  .pf-math-chain {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex-wrap: wrap;
  }
  .pf-math-op {
    color: var(--pf-gold);
    font-weight: 700;
    font-size: 0.85rem;
    user-select: none;
  }

  /* AC Items Active & Inactive Row States */
  .row-ac-active {
    background: rgba(40, 199, 111, 0.08) !important;
    border-left: 3px solid #28c76f !important;
    transition: all 0.2s ease;
  }
  .row-ac-inactive {
    opacity: 0.55;
    background: rgba(255, 255, 255, 0.02) !important;
    border-left: 3px solid rgba(255, 255, 255, 0.1) !important;
    transition: all 0.2s ease;
  }
  .row-ac-inactive:hover {
    opacity: 0.9;
  }

  /* Saving Throw Items Active & Inactive Row States */
  .row-save-active {
    background: rgba(255, 159, 67, 0.08) !important;
    border-left: 3px solid #ff9f43 !important;
    transition: all 0.2s ease;
  }
  .row-save-inactive {
    opacity: 0.55;
    background: rgba(255, 255, 255, 0.02) !important;
    border-left: 3px solid rgba(255, 255, 255, 0.1) !important;
    transition: all 0.2s ease;
  }
  .row-save-inactive:hover {
    opacity: 0.9;
  }

  /* Skills Table */
  .pf-skills-table {
    font-size: 0.8rem;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 2px;
  }
  .pf-skills-table thead th {
    background-color: var(--pf-banner-bg);
    color: var(--pf-gold);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.35rem 0.4rem;
    border-bottom: 1px solid var(--pf-border);
    font-weight: 700;
  }
  .pf-skills-table tbody tr {
    background-color: rgba(255, 255, 255, 0.02);
    transition: background-color 0.15s;
  }
  .pf-skills-table tbody tr:hover {
    background-color: rgba(115, 103, 240, 0.12);
  }
  .pf-skills-table tbody tr.is-class-skill {
    border-left: 3px solid var(--pf-gold);
  }
  .pf-skills-table td {
    padding: 0.22rem 0.35rem;
    vertical-align: middle;
  }

  /* Weapons List Blocks */
  .pf-weapon-block {
    background: rgba(18, 19, 32, 0.65);
    border: 1px solid var(--pf-border);
    border-radius: 0.45rem;
    padding: 0.5rem 0.65rem;
    margin-bottom: 0.5rem;
    transition: border-color 0.2s;
  }
  .pf-weapon-block:hover {
    border-color: var(--pf-gold);
  }
  .wp-crit-mult {
    min-width: 78px !important;
    padding-left: 0.5rem !important;
    padding-right: 1.65rem !important;
    background-position: right 0.45rem center !important;
    text-align: center !important;
  }
  .wp-atk-type {
    padding-left: 0.6rem !important;
    padding-right: 1.85rem !important;
    background-position: right 0.5rem center !important;
  }

  /* Floating Dice Tray */
  .pf-dice-drawer {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 1060;
    width: 360px;
    max-width: calc(100vw - 2rem);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.75);
    border: 1px solid var(--pf-gold);
    border-radius: 0.75rem;
    background: #1e1f30;
  }
  .pf-dice-btn {
    width: 42px;
    height: 42px;
    font-weight: 700;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.45rem;
    transition: transform 0.15s;
  }
  .pf-dice-btn:hover {
    transform: translateY(-2px) scale(1.05);
  }

  /* Custom Scrollbar */
  .pf-scroll {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--pf-purple) #1a1b2b;
  }
  .pf-scroll::-webkit-scrollbar {
    width: 6px;
  }
  .pf-scroll::-webkit-scrollbar-thumb {
    background: var(--pf-purple);
    border-radius: 3px;
  }

  /* Section Tabs */
  .nav-pills .nav-link.active, .nav-pills .show > .nav-link {
    background-color: var(--pf-purple);
    box-shadow: 0 2px 6px rgba(115, 103, 240, 0.4);
  }

  @media print {
    .pf-dice-drawer, .btn-no-print, .layout-navbar, .layout-menu, .footer {
      display: none !important;
    }
    .content-wrapper {
      padding: 0 !important;
    }
    body {
      background: #fff !important;
      color: #000 !important;
    }
    .pf-card, .pf-banner-header {
      border: 1px solid #000 !important;
      background: transparent !important;
      color: #000 !important;
      box-shadow: none !important;
    }
    .pf-box-input {
      background: transparent !important;
      border: 1px solid #999 !important;
      color: #000 !important;
    }
    .page-break {
      page-break-before: always;
    }
  }

  /* Armor Autocomplete & Equipment Styles */
  .pf-armor-result-item {
    padding: 0.45rem 0.65rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }
  .pf-armor-result-item:last-child {
    border-bottom: none;
  }
  .pf-armor-result-item:hover, .pf-armor-result-item.active {
    background-color: rgba(115, 103, 240, 0.25) !important;
  }
  .equipped-armor-chip {
    background: rgba(37, 38, 54, 0.95);
    border: 1px solid rgba(226, 185, 111, 0.35);
    border-radius: 0.35rem;
    padding: 0.3rem 0.55rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    transition: all 0.2s ease;
  }
  .equipped-armor-chip:hover {
    border-color: var(--pf-gold);
  }
  .grid-ac-breakdown {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
  }
  @media (max-width: 576px) {
    .grid-ac-breakdown {
      grid-template-columns: repeat(4, 1fr) repeat(3, 1fr);
    }
  }

  /* Saving Throw Uniform Box Styles */
  .pf-save-box {
    width: 58px !important;
    height: 34px !important;
    min-height: 34px !important;
    max-height: 34px !important;
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    border-radius: 0.35rem !important;
    box-sizing: border-box !important;
    margin: 0 auto !important;
    text-align: center !important;
  }
  div.pf-save-box {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
  }
  input.pf-save-box {
    display: block !important;
    padding: 0.2rem 0.2rem !important;
  }
</style>
@endsection

@section('content')
<div class="pf-sheet-container pb-5">
  <!-- Top Action Bar (Save Status Indicator & Save Button) -->
  <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 btn-no-print">
    <div class="d-flex align-items-center gap-2">
      <span id="saveStatusIndicator" class="text-success small me-1 d-none d-sm-inline">
        <i class="icon-base ti tabler-database-check fs-6 me-1"></i>{{ __('Changes Saved to Database') }}
      </span>
      <button type="button" class="btn btn-primary btn-sm rounded-pill waves-effect waves-light d-flex align-items-center gap-1 shadow-xs px-3 py-1_5" id="btnSaveSheet">
        <i class="icon-base ti tabler-device-floppy"></i> {{ __('Save Changes') }}
      </button>
    </div>
  </div>

  <!-- Character Identity Card (Top Block on Official Sheet - Character Name, Level, Alignment, Class, Race, etc.) -->
  <div class="pf-card p-3 p-md-4 mb-4">
    <div class="row g-2 align-items-center">
      <!-- Character Name -->
      <div class="col-12 col-md-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-0">{{ __('Character Name') }}</label>
          @php
            $charRank = $dbCharacter->rank ?? $dbCharacter->character_type ?? 'Main';
            $isMain = strtolower($charRank) === 'main';
          @endphp
          <span class="badge {{ $isMain ? 'bg-primary' : 'bg-info' }} px-2 py-0_5 font-monospace" style="font-size: 0.7rem;">
            <i class="icon-base ti {{ $isMain ? 'tabler-crown' : 'tabler-feather' }} me-1"></i>{{ $charRank }}
          </span>
        </div>
        <input type="text" id="sheetCharName" class="form-control form-control-sm fw-bold text-heading pf-box-input text-start px-2_5" placeholder="{{ __('Character Name') }}" style="font-size: 1.05rem;" />
      </div>

      <!-- Alignment -->
      <div class="col-6 col-md-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Alignment') }}</label>
        <select id="sheetAlignmentSelect" class="form-select form-select-sm pf-box-input text-start px-2 font-monospace">
          <option value="Lawful Good (LG)">Lawful Good (LG)</option>
          <option value="Neutral Good (NG)">Neutral Good (NG)</option>
          <option value="Chaotic Good (CG)">Chaotic Good (CG)</option>
          <option value="Lawful Neutral (LN)">Lawful Neutral (LN)</option>
          <option value="True Neutral (N)">True Neutral (N)</option>
          <option value="Chaotic Neutral (CN)">Chaotic Neutral (CN)</option>
          <option value="Lawful Evil (LE)">Lawful Evil (LE)</option>
          <option value="Neutral Evil (NE)">Neutral Evil (NE)</option>
          <option value="Chaotic Evil (CE)">Chaotic Evil (CE)</option>
        </select>
      </div>

      <!-- Player Name -->
      <div class="col-6 col-md-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Player') }}</label>
        <input type="text" id="sheetPlayerName" class="form-control form-control-sm pf-box-input text-start px-2" placeholder="{{ __('Player Name') }}" />
      </div>

      <!-- Character Level -->
      <div class="col-6 col-md-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1 d-flex justify-content-between">
          <span>{{ __('Level') }}</span>
          <span class="badge bg-label-primary font-monospace py-0 px-1" id="sheetLvlCounter">1</span>
        </label>
        <div class="input-group input-group-sm">
          <button class="btn btn-outline-secondary px-2" type="button" id="btnLvlDown"><i class="icon-base ti tabler-minus"></i></button>
          <input type="number" id="sheetLevelInput" class="form-control form-control-sm text-center font-monospace fw-bold pf-box-input" min="1" max="20" value="1" />
          <button class="btn btn-outline-secondary px-2" type="button" id="btnLvlUp"><i class="icon-base ti tabler-plus"></i></button>
        </div>
      </div>

      <!-- Deity -->
      <div class="col-6 col-md-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Deity') }}</label>
        <input type="text" id="sheetDeityInput" class="form-control form-control-sm pf-box-input text-start px-2" placeholder="{{ __('Deity') }}" />
      </div>

      <!-- Class Selection (Automated Core Trigger) -->
      <div class="col-12 col-md-3 mt-2">
        <label class="form-label text-uppercase text-warning fw-bold small mb-1 d-flex align-items-center justify-content-between">
          <span>{{ __('Class') }} <span class="text-warning">*</span></span>
          <span class="badge bg-label-warning font-monospace py-0 px-1_5" id="sheetHitDieBadge">HD: d10</span>
        </label>
        <select id="sheetClassSelect" class="form-select form-select-sm fw-bold pf-box-input text-start px-2">
          @if(!empty($pfClasses))
            @foreach($pfClasses as $key => $cl)
              <option value="{{ $key }}" data-hd="{{ $cl['hit_die'] ?? 'd8' }}" data-ranks="{{ $cl['skill_ranks'] ?? 2 }}">
                {{ $cl['name'] ?? ucfirst($key) }} ({{ $cl['hit_die'] ?? 'd8' }} - {{ $cl['skill_ranks'] ?? 2 }}+INT)
              </option>
            @endforeach
          @else
            <option value="paladin" data-hd="d10" data-ranks="2">Paladin (d10 - 2+INT)</option>
            <option value="fighter" data-hd="d10" data-ranks="2">Fighter (d10 - 2+INT)</option>
            <option value="rogue" data-hd="d8" data-ranks="8">Rogue (d8 - 8+INT)</option>
            <option value="wizard" data-hd="d6" data-ranks="2">Wizard (d6 - 2+INT)</option>
          @endif
        </select>
        <input type="hidden" id="sheetHitDie" value="d10" />
      </div>

      <!-- Race (Permanent & Locked) -->
      <div class="col-6 col-md-2 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1 d-flex justify-content-between align-items-center">
          <span>{{ __('Race') }}</span>
          <span class="badge bg-label-secondary py-0 px-1 font-monospace" style="font-size: 0.65rem;" title="{{ __('Permanent: Cannot be modified after creation') }}">
            <i class="icon-base ti tabler-lock icon-xs me-0_5"></i>{{ __('Locked') }}
          </span>
        </label>
        <input type="text" id="sheetRaceInput" class="form-control form-control-sm pf-box-input text-start px-2 bg-label-secondary bg-opacity-10 text-heading fw-semibold" readonly title="{{ __('Race is locked and cannot be changed after character creation.') }}" value="{{ $dbCharacter->race ?? 'Human' }}" />
      </div>

      <!-- Homeland -->
      <div class="col-6 col-md-2 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Homeland') }}</label>
        <input type="text" id="sheetHomelandInput" class="form-control form-control-sm pf-box-input text-start px-2" placeholder="Homeland" />
      </div>

      <!-- Size -->
      <div class="col-4 col-md-1 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Size') }}</label>
        <select id="sheetSizeSelect" class="form-select form-select-sm pf-box-input px-1 text-center font-monospace">
          <option value="Colossal">Colossal (-8)</option>
          <option value="Gargantuan">Gargantuan (-4)</option>
          <option value="Huge">Huge (-2)</option>
          <option value="Large">Large (-1)</option>
          <option value="Medium" selected>Medium (0)</option>
          <option value="Small">Small (+1)</option>
          <option value="Tiny">Tiny (+2)</option>
          <option value="Diminutive">Diminutive (+4)</option>
          <option value="Fine">Fine (+8)</option>
        </select>
      </div>

      <!-- Gender -->
      <div class="col-4 col-md-1 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Gender') }}</label>
        <input type="text" id="sheetGenderInput" class="form-control form-control-sm pf-box-input text-center px-1" placeholder="Gender" />
      </div>

      <!-- Age -->
      <div class="col-4 col-md-1 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Age') }}</label>
        <input type="text" id="sheetAgeInput" class="form-control form-control-sm pf-box-input text-center px-1" placeholder="25" />
      </div>

      <!-- Height & Weight -->
      <div class="col-6 col-md-1 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Height / Weight') }}</label>
        <input type="text" id="sheetHeightInput" class="form-control form-control-sm pf-box-input text-center px-1" placeholder="6' 1\"" />
      </div>
      <div class="col-6 col-md-1 mt-2">
        <label class="form-label text-uppercase text-body-secondary fw-semibold small mb-1">{{ __('Hair / Eyes') }}</label>
        <input type="text" id="sheetEyesHairInput" class="form-control form-control-sm pf-box-input text-center px-1" placeholder="Brown" />
      </div>
    </div>
  </div>

  <!-- Sheet Content Sections -->
  <div id="sheetContentWrapper">

    <!-- ========================================================================= -->
    <!-- PAGE 1: CORE, ATTRIBUTES & COMBAT (CORE & COMBAT) -->
    <!-- ========================================================================= -->
    <div id="tab-page1" class="mb-5">
      <div class="row g-4">

        <!-- LEFT COLUMN: Ability Scores, Combat, Vitals, Weapons (8 Cols) -->
        <div class="col-12 col-xl-8">

          <!-- 1. ABILITY SCORES -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Ability Scores') }}</span>
            </div>

            @php
              $abilities = [
                'str' => ['name' => 'STR', 'full' => 'Strength'],
                'dex' => ['name' => 'DEX', 'full' => 'Dexterity'],
                'con' => ['name' => 'CON', 'full' => 'Constitution'],
                'int' => ['name' => 'INT', 'full' => 'Intelligence'],
                'wis' => ['name' => 'WIS', 'full' => 'Wisdom'],
                'cha' => ['name' => 'CHA', 'full' => 'Charisma']
              ];
            @endphp

            <div class="row g-2_5 g-md-3" id="abilityScoresGrid">
              @foreach($abilities as $abKey => $ab)
                <div class="col-12 col-sm-6 col-md-4">
                  <div class="p-3 rounded border h-100 d-flex align-items-center justify-content-between" style="background: rgba(18, 19, 32, 0.55); border-color: rgba(255, 255, 255, 0.1) !important;">
                    <div>
                      <div class="fw-bold text-heading font-monospace fs-4 lh-1 mb-1">{{ $ab['name'] }}</div>
                      <div class="text-body-secondary font-monospace small">{{ __($ab['full']) }}</div>
                    </div>
                    <div style="width: 105px;">
                      <label class="form-label text-uppercase text-body-secondary fw-semibold mb-1 small d-block text-center font-monospace" style="font-size: 0.68rem;">{{ __('Score') }}</label>
                      <input type="number" id="score_{{ $abKey }}" class="form-control pf-box-input font-monospace text-center fw-bold fs-3 ability-score-input w-100" min="1" max="50" value="10" data-ab="{{ $abKey }}" style="height: 48px; font-size: 1.45rem !important;" />
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- 2. HP, SPEED & INITIATIVE ROW -->
          <div class="row g-3 mb-3">
            <!-- HP -->
            <div class="col-12 col-md-6">
              <div class="pf-card p-3 h-100">
                <div class="pf-banner-header">
                  <span>{{ __('Hit Points (HP)') }}</span>
                  <button type="button" class="btn btn-xs btn-label-warning rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#modalHpBreakdown">
                    <i class="icon-base ti tabler-calculator me-1"></i> {{ __('Level Die Breakdown') }}
                  </button>
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6 text-center">
                    <label class="form-label small text-body-secondary fw-semibold mb-1">{{ __('Total Max HP') }}</label>
                    <div class="pf-box-input bg-label-primary text-primary fw-bold fs-5 py-1" id="sheetMaxHpDisplay">12</div>
                    <input type="hidden" id="sheetMaxHp" value="12" />
                  </div>
                  <div class="col-6 text-center">
                    <label class="form-label small text-body-secondary fw-semibold mb-1">{{ __('Current HP') }}</label>
                    <input type="number" id="sheetCurrentHp" class="form-control form-control-sm pf-box-input text-success font-monospace fw-bold fs-5 py-1" value="12" />
                  </div>
                  <div class="col-6">
                    <label class="form-label small text-body-secondary fw-semibold mb-1">{{ __('Wounds / Nonlethal') }}</label>
                    <input type="number" id="sheetNonlethalInput" class="form-control form-control-sm pf-box-input text-danger font-monospace" placeholder="Wounds / Nonlethal" />
                  </div>
                  <div class="col-6">
                    <label class="form-label small text-body-secondary fw-semibold mb-1">{{ __('DR (Damage Reduction)') }}</label>
                    <input type="text" id="sheetDrInput" class="form-control form-control-sm pf-box-input" placeholder="e.g. 5/Evil" />
                  </div>
                </div>
                <div class="text-center pt-1 border-top border-secondary border-opacity-10">
                  <small class="text-body-secondary font-monospace" id="sheetHpTotalFormulaSummary">Level 1: 10 (Die) + 2 (Con) = 12 Max HP</small>
                </div>
              </div>
            </div>

            <!-- Speed & Initiative -->
            <div class="col-12 col-md-6">
              <div class="pf-card p-3 h-100 d-flex flex-column justify-content-between">
                <div>
                  <div class="pf-banner-header">
                    <span>{{ __('Initiative & Speed') }}</span>
                    <span class="pf-banner-sub">{{ __('Speed & Initiative') }}</span>
                  </div>
                  <!-- Initiative Block -->
                  <div class="d-flex align-items-center justify-content-between p-2 rounded bg-body mb-2 border">
                    <div class="d-flex align-items-center gap-2">
                      <span class="avatar avatar-xs rounded bg-label-primary d-flex align-items-center justify-content-center">
                        <i class="icon-base ti tabler-bolt fs-6"></i>
                      </span>
                      <div>
                        <strong class="text-heading d-block text-uppercase" style="font-size: 0.75rem;">{{ __('INITIATIVE') }}</strong>
                        <small class="text-body-secondary font-monospace">DEX (<span id="lblInitDexMod">+0</span>) + Misc</small>
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-1_5">
                      <input type="number" id="sheetInitMisc" class="form-control form-control-sm pf-box-input pf-input-compact font-monospace" style="width: 50px;" value="0" title="Misc Modifier" />
                      <div class="pf-mod-box px-3 py-1 font-monospace fw-bold text-primary fs-5 cursor-pointer" id="btnRollInit" title="Roll Initiative (d20)">
                        <span id="sheetInitTotal">+0</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Speed Grid -->
                <div class="row g-1 text-center font-monospace">
                  <div class="col-4">
                    <label class="small text-body-secondary d-block" style="font-size: 0.65rem;">{{ __('Land Base') }}</label>
                    <input type="number" id="sheetSpeedBase" class="form-control form-control-sm pf-box-input pf-input-compact" value="30" />
                  </div>
                  <div class="col-4">
                    <label class="small text-body-secondary d-block" style="font-size: 0.65rem;">{{ __('With Armor') }}</label>
                    <input type="number" id="sheetSpeedArmor" class="form-control form-control-sm pf-box-input pf-input-compact" value="20" />
                  </div>
                  <div class="col-4">
                    <label class="small text-body-secondary d-block" style="font-size: 0.65rem;">{{ __('Fly') }}</label>
                    <input type="text" id="sheetSpeedFly" class="form-control form-control-sm pf-box-input pf-input-compact" placeholder="—" />
                  </div>
                  <div class="col-4 mt-1">
                    <label class="small text-body-secondary d-block" style="font-size: 0.65rem;">{{ __('Swim') }}</label>
                    <input type="text" id="sheetSpeedSwim" class="form-control form-control-sm pf-box-input pf-input-compact" placeholder="—" />
                  </div>
                  <div class="col-4 mt-1">
                    <label class="small text-body-secondary d-block" style="font-size: 0.65rem;">{{ __('Climb') }}</label>
                    <input type="text" id="sheetSpeedClimb" class="form-control form-control-sm pf-box-input pf-input-compact" placeholder="—" />
                  </div>
                  <div class="col-4 mt-1">
                    <label class="small text-body-secondary d-block" style="font-size: 0.65rem;">{{ __('Burrow') }}</label>
                    <input type="text" id="sheetSpeedBurrow" class="form-control form-control-sm pf-box-input pf-input-compact" placeholder="—" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. ARMOR CLASS (AC) BLOCK -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Armor Class (AC)') }}</span>
              <span class="pf-banner-sub">10 + Armor + Shield + DEX + Size + Natural + Deflect + Misc</span>
            </div>

            <!-- AC Major Totals Row -->
            <div class="row g-2 align-items-center text-center mb-2">
              <div class="col-12 col-md-4">
                <div class="p-2 rounded bg-label-primary border border-primary border-opacity-25">
                  <span class="small text-uppercase text-body-secondary d-block font-monospace" style="font-size: 0.7rem;">{{ __('TOTAL AC') }}</span>
                  <span class="fs-4 fw-bold font-monospace text-primary" id="sheetAcTotal">10</span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="p-2 rounded bg-body border">
                  <span class="small text-uppercase text-body-secondary d-block font-monospace" style="font-size: 0.7rem;">{{ __('Touch AC') }}</span>
                  <span class="fs-5 fw-bold font-monospace text-warning" id="sheetAcTouch">10</span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="p-2 rounded bg-body border">
                  <span class="small text-uppercase text-body-secondary d-block font-monospace" style="font-size: 0.7rem;">{{ __('Flat-Footed AC') }}</span>
                  <span class="fs-5 fw-bold font-monospace text-info" id="sheetAcFlat">10</span>
                </div>
              </div>
            </div>

            <!-- AC Formula Breakdown Inputs (7 Equal Columns) -->
            <div class="grid-ac-breakdown text-center font-monospace small mb-2">
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Armor Bonus">ARMOR</label>
                <input type="number" id="sheetAcArmor" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" />
              </div>
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Shield Bonus">SHIELD</label>
                <input type="number" id="sheetAcShield" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" />
              </div>
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Dexterity Modifier (limited by Armor Max Dex)">DEX MOD</label>
                <input type="number" id="sheetAcDex" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" readonly />
              </div>
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Size Modifier">SIZE</label>
                <input type="number" id="sheetAcSize" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" readonly />
              </div>
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Natural Armor">NATURAL</label>
                <input type="number" id="sheetAcNatural" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" />
              </div>
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Deflection Bonus">DEFLECT</label>
                <input type="number" id="sheetAcDeflect" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" />
              </div>
              <div>
                <label class="text-body-secondary d-block text-truncate" style="font-size: 0.65rem;" title="Misc / Dodge Bonus">MISC</label>
                <input type="number" id="sheetAcMisc" class="form-control form-control-sm pf-box-input pf-input-compact text-center w-100" value="0" />
              </div>
            </div>

            <!-- Max Dex Capping Alert Badge (shows when Dex is limited) -->
            <div id="dexCapNotice" class="d-none text-center mt-2">
              <span id="badgeDexCapInfo" class="badge bg-label-warning font-monospace py-1 px-2" style="font-size: 0.72rem;"></span>
            </div>

            <!-- ====================================================
                 INTEGRATED AC ITEMS (ARMOR & SHIELDS / ACTIVE ARMOR & SHIELD)
                 ==================================================== -->
            <div class="pt-3 mt-3 border-top border-secondary border-opacity-20" id="sectionIntegratedAcItems">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.75rem;">
                  <span class="badge bg-label-primary font-monospace py-1 px-2 d-inline-flex align-items-center gap-1 shadow-xs" id="badgeActiveArmor">
                    <i class="icon-base ti tabler-shield-filled fs-6"></i>
                    <span>{{ __('Active Armor') }}:</span>
                    <strong id="lblActiveArmorName" class="text-heading ms-1">{{ __('None (Unarmored)') }}</strong>
                  </span>
                  <span class="badge bg-label-info font-monospace py-1 px-2 d-inline-flex align-items-center gap-1 shadow-xs" id="badgeActiveShield">
                    <i class="icon-base ti tabler-shield fs-6"></i>
                    <span>{{ __('Active Shield') }}:</span>
                    <strong id="lblActiveShieldName" class="text-heading ms-1">{{ __('None') }}</strong>
                  </span>
                </div>
                <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2" id="btnAddAcItemRow">
                  <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add Item') }}
                </button>
              </div>

              <div class="table-responsive" style="overflow: visible;">
                <table class="table table-sm align-middle text-center mb-0" id="tableAcItems" style="font-size: 0.76rem;">
                  <thead>
                    <tr class="text-body-secondary font-monospace text-uppercase" style="font-size: 0.68rem;">
                      <th style="width: 58px;" title="{{ __('Currently Equipped Armor & Shield') }}">{{ __('Active') }}</th>
                      <th class="text-start">{{ __('Item Name') }}</th>
                      <th style="width: 82px;">{{ __('Type') }}</th>
                      <th style="width: 50px;">{{ __('Bonus') }}</th>
                      <th style="width: 46px;" title="{{ __('Armor Check Penalty') }}">Pen</th>
                      <th style="width: 46px;" title="{{ __('Weight') }}">Wt</th>
                      <th style="width: 26px;"></th>
                    </tr>
                  </thead>
                  <tbody id="acItemsTbody">
                  </tbody>
                  <tfoot>
                    <tr class="fw-bold font-monospace bg-body border-top">
                      <td class="text-center"><span class="badge bg-label-success py-0 px-1" style="font-size: 0.62rem;">{{ __('ACTIVE') }}</span></td>
                      <td class="text-start" colspan="2">{{ __('EQUIPPED TOTAL') }}:</td>
                      <td id="acItemTotalBonus" class="text-primary">+0</td>
                      <td id="acItemTotalPen" class="text-danger">+0</td>
                      <td id="acItemTotalWeight">0 lbs</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <small class="text-body-secondary d-block mt-1 font-monospace" style="font-size: 0.67rem;">
                * {{ __('Birden fazla zırh veya kalkan varsa, sadece "Aktif" seçili olanın bonusu ve ceza puanı AC\'ye yansıtılır. Ağırlıklar toplam yüke dahil edilir.') }}
              </small>
            </div>
          </div>

          <!-- 4. SAVING THROWS -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Saving Throws') }}</span>
              <span class="pf-banner-sub">{{ __('Click: Roll Save') }}</span>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle text-center mb-0">
                <thead>
                  <tr class="text-body-secondary small text-uppercase font-monospace">
                    <th class="text-start">{{ __('Save Type') }}</th>
                    <th style="width: 68px; min-width: 68px; max-width: 68px;">{{ __('Total') }}</th>
                    <th style="width: 68px; min-width: 68px; max-width: 68px;">{{ __('Base') }}</th>
                    <th style="width: 68px; min-width: 68px; max-width: 68px;">{{ __('Ability') }}</th>
                    <th style="width: 68px; min-width: 68px; max-width: 68px;">{{ __('Magic') }}</th>
                    <th style="width: 68px; min-width: 68px; max-width: 68px;">{{ __('Misc') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Fortitude -->
                  <tr>
                    <td class="text-start">
                      <div class="d-flex align-items-center gap-1_5">
                        <span class="badge bg-label-danger font-monospace">FORT</span>
                        <strong class="text-heading small">{{ __('Fortitude') }}</strong>
                      </div>
                      <small class="text-body-secondary font-monospace" style="font-size: 0.68rem;">(Constitution)</small>
                    </td>
                    <td style="width: 68px;">
                      <div class="pf-mod-box font-monospace fw-bold text-danger btn-roll-save pf-save-box" data-save="fort" id="saveTotal_fort">+2</div>
                    </td>
                    <td style="width: 68px;"><input type="number" id="saveBase_fort" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="2" /></td>
                    <td style="width: 68px;"><input type="number" id="saveAbility_fort" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveMagic_fort" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveMisc_fort" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                  </tr>

                  <!-- Reflex -->
                  <tr>
                    <td class="text-start">
                      <div class="d-flex align-items-center gap-1_5">
                        <span class="badge bg-label-success font-monospace">REF</span>
                        <strong class="text-heading small">{{ __('Reflex') }}</strong>
                      </div>
                      <small class="text-body-secondary font-monospace" style="font-size: 0.68rem;">(Dexterity)</small>
                    </td>
                    <td style="width: 68px;">
                      <div class="pf-mod-box font-monospace fw-bold text-success btn-roll-save pf-save-box" data-save="ref" id="saveTotal_ref">+0</div>
                    </td>
                    <td style="width: 68px;"><input type="number" id="saveBase_ref" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveAbility_ref" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveMagic_ref" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveMisc_ref" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                  </tr>

                  <!-- Will -->
                  <tr>
                    <td class="text-start">
                      <div class="d-flex align-items-center gap-1_5">
                        <span class="badge bg-label-info font-monospace">WILL</span>
                        <strong class="text-heading small">{{ __('Will') }}</strong>
                      </div>
                      <small class="text-body-secondary font-monospace" style="font-size: 0.68rem;">(Wisdom)</small>
                    </td>
                    <td style="width: 68px;">
                      <div class="pf-mod-box font-monospace fw-bold text-info btn-roll-save pf-save-box" data-save="will" id="saveTotal_will">+2</div>
                    </td>
                    <td style="width: 68px;"><input type="number" id="saveBase_will" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="2" /></td>
                    <td style="width: 68px;"><input type="number" id="saveAbility_will" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveMagic_will" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                    <td style="width: 68px;"><input type="number" id="saveMisc_will" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" /></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- ====================================================
                 INTEGRATED SAVING THROW ITEMS (RESISTANCE & PROTECTIVE ITEMS)
                 ==================================================== -->
            <div class="pt-3 mt-3 border-top border-secondary border-opacity-20" id="sectionIntegratedSaveItems">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.75rem;">
                  <span class="badge bg-label-warning font-monospace py-1 px-2 d-inline-flex align-items-center gap-1 shadow-xs" id="badgeActiveResistance">
                    <i class="icon-base ti tabler-shield-spark fs-6"></i>
                    <span>{{ __('Active Protection / Resistance Item') }}:</span>
                    <strong id="lblActiveResistanceName" class="text-heading ms-1">{{ __('None') }}</strong>
                  </span>
                  <span class="badge bg-label-primary font-monospace py-1 px-2 d-inline-flex align-items-center gap-1 shadow-xs" id="badgeActiveSaveBonuses">
                    <span>{{ __('Applied Bonus') }}:</span>
                    <strong id="lblActiveSaveBonusesText" class="text-heading ms-1">Fort +0 | Ref +0 | Will +0</strong>
                  </span>
                </div>
                <button type="button" class="btn btn-xs btn-label-warning rounded-pill px-2" id="btnAddSaveItemRow">
                  <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add Save Item') }}
                </button>
              </div>

              <div class="table-responsive" style="overflow: visible;">
                <table class="table table-sm align-middle text-center mb-0" id="tableSaveItems" style="font-size: 0.76rem;">
                  <thead>
                    <tr class="text-body-secondary font-monospace text-uppercase" style="font-size: 0.68rem;">
                      <th style="width: 58px;" title="{{ __('Currently Equipped Protection Item') }}">{{ __('Active') }}</th>
                      <th class="text-start">{{ __('Item Name') }}</th>
                      <th style="width: 100px;">{{ __('Save Type') }}</th>
                      <th style="width: 100px;">{{ __('Bonus Type') }}</th>
                      <th style="width: 52px;">{{ __('Bonus') }}</th>
                      <th style="width: 48px;" title="{{ __('Weight') }}">Wt</th>
                      <th style="width: 26px;"></th>
                    </tr>
                  </thead>
                  <tbody id="saveItemsTbody">
                  </tbody>
                  <tfoot>
                    <tr class="fw-bold font-monospace bg-body border-top">
                      <td class="text-center"><span class="badge bg-label-success py-0 px-1" style="font-size: 0.62rem;">{{ __('ACTIVE') }}</span></td>
                      <td class="text-start" colspan="3">{{ __('EQUIPPED RESISTANCE TOTAL') }}:</td>
                      <td id="saveItemTotalBonus" class="text-warning">+0</td>
                      <td id="saveItemTotalWeight">0 lbs</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <small class="text-body-secondary d-block mt-1 font-monospace" style="font-size: 0.67rem;">
                * {{ __('Aktif olan koruma eşyalarının (örn. Cloak of Resistance +1..+5) bonusları otomatik olarak yukarıdaki Saving Throws tablosunun "Magic" (Büyülü Direnç) sütununa işlenir ve toplamları günceller. Ağırlıklar toplam yüke eklenir.') }}
              </small>
            </div>
          </div>

          <!-- 5. BAB, CMB & CMD -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('BAB & Combat Maneuvers (CMB / CMD)') }}</span>
              <span class="pf-banner-sub">{{ __('Attack & Combat Maneuvers') }}</span>
            </div>

            <div class="row g-3">
              <!-- BAB -->
              <div class="col-6 col-md-3 text-center">
                <label class="form-label small text-body-secondary fw-semibold mb-1 d-block text-truncate">{{ __('Base Attack Bonus') }}</label>
                <div class="d-flex align-items-center justify-content-center">
                  <input type="text" id="sheetBabInput" class="form-control pf-box-input font-monospace text-center fw-bold fs-5 text-primary w-100" style="height: 48px;" value="+1" />
                </div>
                <small class="text-body-secondary font-monospace d-block mt-1" style="font-size: 0.68rem; min-height: 18px;">{{ __('Class Progression (BAB)') }}</small>
              </div>

              <!-- Spell Resistance -->
              <div class="col-6 col-md-3 text-center">
                <label class="form-label small text-body-secondary fw-semibold mb-1 d-block text-truncate">{{ __('Spell Resistance (SR)') }}</label>
                <div class="d-flex align-items-center justify-content-center">
                  <input type="text" id="sheetSrInput" class="form-control pf-box-input font-monospace text-center fs-5 text-warning w-100" style="height: 48px;" placeholder="—" />
                </div>
                <small class="text-body-secondary font-monospace d-block mt-1" style="font-size: 0.68rem; min-height: 18px;">{{ __('Race / Item / Spell') }}</small>
              </div>

              <!-- CMB -->
              <div class="col-6 col-md-3 text-center">
                <label class="form-label small text-body-secondary fw-semibold mb-1 d-block text-truncate">{{ __('CMB (Attack)') }}</label>
                <div class="pf-mod-box font-monospace fw-bold fs-5 text-heading cursor-pointer d-flex align-items-center justify-content-center w-100" style="height: 48px;" id="btnRollCmb" title="{{ __('Roll CMB (d20)') }}">
                  <span id="sheetCmbTotal">+1</span>
                </div>
                <small class="text-body-secondary font-monospace d-block mt-1" style="font-size: 0.68rem; min-height: 18px;">{{ __('BAB + STR + Size') }}</small>
              </div>

              <!-- CMD -->
              <div class="col-6 col-md-3 text-center">
                <label class="form-label small text-body-secondary fw-semibold mb-1 d-block text-truncate">{{ __('CMD (Defense)') }}</label>
                <div class="pf-box-input font-monospace fw-bold fs-5 text-info d-flex align-items-center justify-content-center w-100" style="height: 48px;" id="sheetCmdTotal">11</div>
                <small class="text-body-secondary font-monospace d-block mt-1" style="font-size: 0.68rem; min-height: 18px;">10 + BAB + STR + DEX</small>
              </div>
            </div>
          </div>

          <!-- 6. WEAPONS SECTION (Weapons - Auto-calculated) -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Weapons & Attacks') }}</span>
              <div class="d-flex align-items-center gap-2">
                <span class="pf-banner-sub">Atk = BAB + STR/DEX + Enh + Misc</span>
                <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2" id="btnAddWeaponRow">
                  <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add Weapon') }}
                </button>
              </div>
            </div>

            <div id="weaponsContainer">
              @php
                $isAlchemistChar = $dbCharacter && strtolower($dbCharacter->class_name) === 'alchemist';
              @endphp
              @if($isAlchemistChar)
                @php
                  $alchLvl = $dbCharacter->level ?? 1;
                  $alchDice = ceil($alchLvl / 2);
                  $alchIntMod = (int) floor((($dbCharacter->int ?? 10) - 10) / 2);
                  $alchBombsPerDay = max(1, $alchLvl + $alchIntMod);
                @endphp
                <div class="pf-weapon-block weapon-row" data-idx="1">
                  <div class="row g-2 align-items-center">
                    <!-- Row 1: Name, Type Select, Range, Delete -->
                    <div class="col-12 col-md-4">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Weapon Name') }} ({{ __('Weapon') }} 1)</label>
                      <input type="text" class="form-control form-control-sm pf-box-input text-start px-2 wp-name" placeholder="e.g. Bomb" value="Bomb" />
                    </div>
                    <div class="col-4 col-md-2">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Attack Type') }}</label>
                      <select class="form-select form-select-sm pf-box-input px-1 wp-atk-type">
                        <option value="melee">Melee (STR)</option>
                        <option value="ranged" selected>Ranged (DEX)</option>
                      </select>
                    </div>
                    <div class="col-4 col-md-2">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Damage Type') }}</label>
                      <input type="text" class="form-control form-control-sm pf-box-input wp-type" placeholder="Fire / Splash" value="Fire" />
                    </div>
                    <div class="col-4 col-md-2">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Range') }}</label>
                      <input type="text" class="form-control form-control-sm pf-box-input wp-range" placeholder="20 ft." value="20 ft." />
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end justify-content-end">
                      <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-weapon" title="{{ __('Delete Weapon') }}">
                        <i class="icon-base ti tabler-trash fs-5"></i>
                      </button>
                    </div>

                    <!-- Row 2: Base Die, Enhancement, Crit Mult, Improved Crit -->
                    <div class="col-6 col-md-2 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Base Die') }}</label>
                      <input type="text" class="form-control form-control-sm pf-box-input font-monospace wp-base-die" placeholder="1d6" value="{{ $alchDice }}d6" />
                    </div>
                    <div class="col-3 col-md-1 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;" title="{{ __('Enhancement Bonus') }}">Ench(+)</label>
                      <input type="number" class="form-control form-control-sm pf-box-input font-monospace wp-enh text-center px-1" min="0" max="5" value="0" />
                    </div>
                    <div class="col-3 col-md-1 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;">Misc Atk</label>
                      <input type="number" class="form-control form-control-sm pf-box-input font-monospace wp-misc-atk text-center px-1" value="0" />
                    </div>
                    <div class="col-4 col-md-2 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;">{{ __('Critical Range / Mult') }}</label>
                      <div class="d-flex align-items-center gap-1">
                        <input type="text" class="form-control form-control-sm pf-box-input font-monospace wp-crit-range text-center px-1" style="width: 50%; min-width: 48px;" placeholder="20" value="20" />
                        <span class="text-body-secondary fw-bold px-1 user-select-none">/</span>
                        <select class="form-select form-select-sm pf-box-input font-monospace wp-crit-mult text-center px-1" style="width: 50%; min-width: 48px;">
                          <option value="2" selected>x2</option>
                          <option value="3">x3</option>
                          <option value="4">x4</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-4 col-md-2 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0 d-flex align-items-center justify-content-between" style="font-size: 0.68rem;">
                        <span>{{ __('Critical Threat') }}</span>
                        <span class="wp-feat-crit-indicator badge bg-label-info py-0 px-1 d-none" style="font-size: 0.58rem;">Imp. Crit</span>
                      </label>
                      <div class="pf-box-input font-monospace small text-info py-1 wp-crit-display">20/x2</div>
                    </div>
                    <div class="col-4 col-md-2 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Ammunition') }}</label>
                      <input type="text" class="form-control form-control-sm pf-box-input wp-ammo" placeholder="—" value="{{ $alchBombsPerDay }}/day" />
                    </div>

                    <!-- Row 3: Auto-Calculated Results -->
                    <div class="col-12 col-md-4 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Attack Bonus (Auto)') }}</label>
                      <div class="pf-mod-box font-monospace fw-bold text-primary py-1 wp-atk-total" title="BAB + DEX (Touch)">+0</div>
                      <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-atk-formula">BAB+DEX</span></small>
                    </div>
                    <div class="col-12 col-md-4 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Damage (Auto)') }}</label>
                      <div class="pf-box-input font-monospace fw-bold text-warning py-1 wp-dmg-total">{{ $alchDice }}d6+0</div>
                      <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-dmg-formula">{{ $alchDice }}d6 + INT</span></small>
                    </div>
                    <div class="col-12 col-md-4 mt-1">
                      <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Critical Damage (Auto)') }}</label>
                      <div class="pf-box-input font-monospace fw-bold text-danger py-1 wp-crit-dmg-total">{{ $alchDice + 1 }}d6+0</div>
                      <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-crit-dmg-formula">x2</span></small>
                    </div>
                  </div>
                </div>
              @else
                @for($i = 1; $i <= 3; $i++)
                  <div class="pf-weapon-block weapon-row" data-idx="{{ $i }}">
                    <div class="row g-2 align-items-center">
                      <!-- Row 1: Name, Type Select, Range, Delete -->
                      <div class="col-12 col-md-4">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Weapon Name') }} ({{ __('Weapon') }} {{ $i }})</label>
                        <input type="text" class="form-control form-control-sm pf-box-input text-start px-2 wp-name" placeholder="e.g. Longsword +1" value="{{ $i === 1 ? 'Longsword +1' : ($i === 2 ? 'Heavy Crossbow' : '') }}" />
                      </div>
                      <div class="col-4 col-md-2">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Attack Type') }}</label>
                        <select class="form-select form-select-sm pf-box-input px-1 wp-atk-type">
                          <option value="melee" {{ $i === 2 ? '' : 'selected' }}>Melee (STR)</option>
                          <option value="ranged" {{ $i === 2 ? 'selected' : '' }}>Ranged (DEX)</option>
                        </select>
                      </div>
                      <div class="col-4 col-md-2">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Damage Type') }}</label>
                        <input type="text" class="form-control form-control-sm pf-box-input wp-type" placeholder="S / P / B" value="{{ $i === 1 ? 'Slashing' : 'Piercing' }}" />
                      </div>
                      <div class="col-4 col-md-2">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Range') }}</label>
                        <input type="text" class="form-control form-control-sm pf-box-input wp-range" placeholder="—" value="{{ $i === 2 ? '80 ft.' : '—' }}" />
                      </div>
                      <div class="col-12 col-md-2 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-weapon" title="{{ __('Delete Weapon') }}">
                          <i class="icon-base ti tabler-trash fs-5"></i>
                        </button>
                      </div>

                      <!-- Row 2: Base Die, Enhancement, Crit Mult, Improved Crit -->
                      <div class="col-6 col-md-2 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Base Die') }}</label>
                        <input type="text" class="form-control form-control-sm pf-box-input font-monospace wp-base-die" placeholder="1d8" value="{{ $i === 1 ? '1d8' : ($i === 2 ? '1d10' : '1d8') }}" />
                      </div>
                      <div class="col-3 col-md-1 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;" title="{{ __('Enhancement Bonus') }}">Ench(+)</label>
                        <input type="number" class="form-control form-control-sm pf-box-input font-monospace wp-enh text-center px-1" min="0" max="5" value="{{ $i === 1 ? '1' : '0' }}" />
                      </div>
                      <div class="col-3 col-md-1 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;">Misc Atk</label>
                        <input type="number" class="form-control form-control-sm pf-box-input font-monospace wp-misc-atk text-center px-1" value="0" />
                      </div>
                      <div class="col-4 col-md-2 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;">{{ __('Critical Range / Mult') }}</label>
                        <div class="d-flex align-items-center gap-1">
                          <input type="text" class="form-control form-control-sm pf-box-input font-monospace wp-crit-range text-center px-1" style="width: 50%; min-width: 48px;" placeholder="19-20" value="{{ $i === 1 ? '19-20' : ($i === 2 ? '19-20' : '20') }}" />
                          <span class="text-body-secondary fw-bold px-1 user-select-none">/</span>
                          <select class="form-select form-select-sm pf-box-input font-monospace wp-crit-mult text-center px-1" style="width: 50%; min-width: 48px;">
                            <option value="2" {{ ($i === 1 || $i === 2) ? 'selected' : '' }}>x2</option>
                            <option value="3" {{ $i === 3 ? 'selected' : '' }}>x3</option>
                            <option value="4">x4</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-4 col-md-2 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0 d-flex align-items-center justify-content-between" style="font-size: 0.68rem;">
                          <span>{{ __('Critical Threat') }}</span>
                          <span class="wp-feat-crit-indicator badge bg-label-info py-0 px-1 d-none" style="font-size: 0.58rem;">Imp. Crit</span>
                        </label>
                        <div class="pf-box-input font-monospace small text-info py-1 wp-crit-display">19-20/x2</div>
                      </div>
                      <div class="col-4 col-md-2 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Ammunition') }}</label>
                        <input type="text" class="form-control form-control-sm pf-box-input wp-ammo" placeholder="—" value="{{ $i === 2 ? '30 bolts' : '' }}" />
                      </div>

                      <!-- Row 3: Auto-Calculated Results (No roll buttons, clean 4-col layout) -->
                      <div class="col-12 col-md-4 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Attack Bonus (Auto)') }}</label>
                        <div class="pf-mod-box font-monospace fw-bold text-primary py-1 wp-atk-total" title="BAB + STR/DEX + Enhancement + Misc">+0</div>
                        <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-atk-formula">BAB+STR+Enh</span></small>
                      </div>
                      <div class="col-12 col-md-4 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Damage (Auto)') }}</label>
                        <div class="pf-box-input font-monospace fw-bold text-warning py-1 wp-dmg-total">1d8+0</div>
                        <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-dmg-formula">Die+STR+Enh</span></small>
                      </div>
                      <div class="col-12 col-md-4 mt-1">
                        <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">{{ __('Critical Damage (Auto)') }}</label>
                        <div class="pf-box-input font-monospace fw-bold text-danger py-1 wp-crit-dmg-total">2d8+0</div>
                        <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-crit-dmg-formula">x2</span></small>
                      </div>
                    </div>
                  </div>
                @endfor
              @endif
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: Official Skills Table (4 Cols) -->
        <div class="col-12 col-xl-4">
          <div class="pf-card p-3">
            <!-- Header with Skill Rank Automation Summary -->
            <div class="pf-banner-header">
              <span>{{ __('Skills') }}</span>
              <div class="d-flex align-items-center gap-1_5">
                <span class="badge bg-primary text-white font-monospace" id="skillRanksPoolBadge">
                  <span id="spentSkillRanks">0</span> / <span id="totalAvailableSkillRanks">0</span> Ranks
                </span>
              </div>
            </div>

            <!-- Automation explanation alert -->
            <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded bg-body border small">
              <div class="d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-info-circle text-primary fs-5"></i>
                <span>
                  <strong class="text-heading">{{ __('Class Skills:') }}</strong> {{ __('Auto-checked for class skills. Grants +3 bonus with 1+ rank.') }}
                </span>
              </div>
              <span class="badge bg-label-info font-monospace" id="lblClassSkillRankFormula">2+INT x Lvl</span>
            </div>

            <!-- Skills Table -->
            <div class="table-responsive pf-scroll" style="max-height: 820px;">
              <table class="pf-skills-table" id="tableSkills">
                <thead>
                  <tr>
                    <th style="width: 32px;" class="text-center" title="{{ __('Class Skill') }}">CS</th>
                    <th>{{ __('Skill Name') }}</th>
                    <th style="width: 65px;" class="text-center" title="{{ __('Total') }}">{{ __('Total') }}</th>
                    <th style="width: 60px;" class="text-center">{{ __('Mod') }}</th>
                    <th style="width: 55px;" class="text-center">{{ __('Ranks') }}</th>
                    <th style="width: 55px;" class="text-center">Misc</th>
                    <th style="width: 38px;" class="text-center">{{ __('Roll') }}</th>
                  </tr>
                </thead>
                <tbody id="skillsTableBody">
                  <!-- Generated dynamically from PF_SKILLS array in JS -->
                </tbody>
              </table>
            </div>

            <!-- Conditional Modifiers & Languages below skills -->
            <div class="mt-3 pt-2 border-top">
              <label class="form-label small text-body-secondary fw-semibold mb-1">{{ __('Conditional Skill Modifiers') }}</label>
              <textarea id="sheetSkillConditional" class="form-control form-control-sm pf-box-input text-start px-2 mb-2" rows="2" placeholder="{{ __('e.g. +2 Perception to find traps, +1 Diplomacy against Elves...') }}"></textarea>

              <label class="form-label small text-body-secondary fw-semibold mb-1">{{ __('Languages') }}</label>
              <input type="text" id="sheetLanguages" class="form-control form-control-sm pf-box-input text-start px-2 font-monospace" placeholder="Common, Elven, Celestial..." value="Common" />
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PAGE 2: EQUIPMENT, SPELLS & SPECIAL ABILITIES                             -->
    <!-- ========================================================================= -->
    <div class="pf-banner-header mt-5 mb-4 py-2 px-3">
      <div class="d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-archive fs-5"></i>
        <span>{{ __('Page 2: Equipment, Spells & Special Abilities') }}</span>
      </div>
    </div>

    <div id="tab-page2" class="page-break mb-4">
      <div class="row g-4">

        <!-- Column 1: Gear, Carrying Capacity, Money (4 Cols) -->
        <div class="col-12 col-lg-4">

          <!-- Gear & Equipment List -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Gear & Equipment') }}</span>
              <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2" id="btnAddGearRow">
                <i class="icon-base ti tabler-plus"></i> {{ __('Add Item') }}
              </button>
            </div>

            <div class="pf-scroll" style="max-height: 280px;" id="gearItemsContainer">
              <!-- Gear items rows -->
              <div class="d-flex align-items-center gap-1 mb-1">
                <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start gear-name" placeholder="Item Name" value="Backpack, Masterwork" />
                <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center gear-wt font-monospace" style="width: 60px;" placeholder="Wt" value="2" />
                <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-gear"><i class="icon-base ti tabler-x"></i></button>
              </div>
              <div class="d-flex align-items-center gap-1 mb-1">
                <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start gear-name" placeholder="Item Name" value="Bedroll & Blanket" />
                <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center gear-wt font-monospace" style="width: 60px;" placeholder="Wt" value="5" />
                <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-gear"><i class="icon-base ti tabler-x"></i></button>
              </div>
              <div class="d-flex align-items-center gap-1 mb-1">
                <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start gear-name" placeholder="Item Name" value="Rations, Trail (5 days)" />
                <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center gear-wt font-monospace" style="width: 60px;" placeholder="Wt" value="5" />
                <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-gear"><i class="icon-base ti tabler-x"></i></button>
              </div>
              <div class="d-flex align-items-center gap-1 mb-1">
                <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start gear-name" placeholder="Item Name" value="Waterskin & Torches (3)" />
                <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center gear-wt font-monospace" style="width: 60px;" placeholder="Wt" value="7" />
                <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-gear"><i class="icon-base ti tabler-x"></i></button>
              </div>
            </div>

            @if($purchasedItemsAmbiguous)
              <div class="alert alert-warning mt-3 mb-0">Karakter eşleşmesi belirsiz olduğu için loncadan alınan eşyalar gösterilemiyor.</div>
            @elseif($purchasedItems->isNotEmpty())
              <div class="mt-3 pt-2 border-top">
                <strong class="small text-heading">Karakter Envanteri (Lonca)</strong>
                @foreach($purchasedItems as $purchasedItem)
                  <div class="small text-body">{{ $purchasedItem->name }} @if($purchasedItem->quality) ({{ $purchasedItem->quality }}) @endif &times; {{ $purchasedItem->quantity }}</div>
                @endforeach
              </div>
            @endif

            <div class="d-flex align-items-center justify-content-between p-2 rounded bg-body border mt-2 font-monospace">
              <strong class="text-heading small">{{ __('TOTAL WEIGHT') }}:</strong>
              <span class="badge bg-label-primary fs-6" id="lblTotalGearWeight">74 lbs</span>
            </div>
          </div>

          <!-- Carrying Capacity (Automated based on STR) -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Carrying Capacity') }}</span>
              <span class="badge bg-label-warning font-monospace" id="lblCarryingStrScore">STR 16</span>
            </div>

            <div class="row g-2 text-center font-monospace mb-2">
              <div class="col-4">
                <div class="p-2 rounded bg-body border">
                  <span class="d-block small text-success fw-bold" style="font-size: 0.65rem;">{{ __('LIGHT LOAD') }}</span>
                  <span class="fw-bold" id="capLight">≤ 76 lbs</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 rounded bg-body border">
                  <span class="d-block small text-warning fw-bold" style="font-size: 0.65rem;">{{ __('MEDIUM LOAD') }}</span>
                  <span class="fw-bold" id="capMedium">77-153 lbs</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 rounded bg-body border">
                  <span class="d-block small text-danger fw-bold" style="font-size: 0.65rem;">{{ __('HEAVY LOAD') }}</span>
                  <span class="fw-bold" id="capHeavy">154-230 lbs</span>
                </div>
              </div>
            </div>

            <div class="row g-2 text-center font-monospace" style="font-size: 0.72rem;">
              <div class="col-4">
                <div class="p-1 rounded bg-body border">
                  <span class="text-body-secondary d-block" style="font-size: 0.62rem;">{{ __('LIFT OVER HEAD') }}</span>
                  <strong id="capLiftHead">230 lbs</strong>
                </div>
              </div>
              <div class="col-4">
                <div class="p-1 rounded bg-body border">
                  <span class="text-body-secondary d-block" style="font-size: 0.62rem;">{{ __('LIFT OFF GROUND') }}</span>
                  <strong id="capLiftGround">460 lbs</strong>
                </div>
              </div>
              <div class="col-4">
                <div class="p-1 rounded bg-body border">
                  <span class="text-body-secondary d-block" style="font-size: 0.62rem;">{{ __('DRAG OR PUSH') }}</span>
                  <strong id="capDrag">1,150 lbs</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Money (Para) -->
          <div class="pf-card p-3">
            <div class="pf-banner-header">
              <span>{{ __('Money & Wealth') }}</span>
              <span class="pf-banner-sub">{{ __('Coins & Wealth') }}</span>
            </div>

            <div class="row g-2 font-monospace text-center">
              <div class="col-3">
                <label class="form-label small text-warning mb-1 fw-bold">CP</label>
                <input type="number" id="sheetMoneyCp" class="form-control form-control-sm pf-box-input" value="15" />
              </div>
              <div class="col-3">
                <label class="form-label small text-body-secondary mb-1 fw-bold">SP</label>
                <input type="number" id="sheetMoneySp" class="form-control form-control-sm pf-box-input" value="48" />
              </div>
              <div class="col-3">
                <label class="form-label small text-warning mb-1 fw-bold">GP</label>
                <input type="number" id="sheetMoneyGp" class="form-control form-control-sm pf-box-input text-warning fw-bold" value="120" />
              </div>
              <div class="col-3">
                <label class="form-label small text-info mb-1 fw-bold">PP</label>
                <input type="number" id="sheetMoneyPp" class="form-control form-control-sm pf-box-input text-info fw-bold" value="5" />
              </div>
            </div>
          </div>

        </div>

        <!-- Column 2: Feats, Traits & Special Abilities (4 Cols) -->
        <div class="col-12 col-lg-4">

          <!-- Character Traits (Rank Traits: Member, Senior, Expert) -->
          @php
            $existingTraits = [];
            if ($dbCharacter) {
              if ($dbCharacter->relationLoaded('traits') && $dbCharacter->traits->isNotEmpty()) {
                foreach ($dbCharacter->traits as $t) {
                  if (!empty($t->slot_key)) {
                    $existingTraits[$t->slot_key] = [
                      'name'       => $t->trait_name ?? '',
                      'desc'       => $t->description ?? '',
                      'category'   => $t->category ?? '',
                      'source_url' => $t->source_url ?? '',
                    ];
                  }
                }
              }
              if (empty($existingTraits['uye']['name']) && isset($dbCharacter->sheet_data['traits']['uye'])) {
                $existingTraits['uye'] = $dbCharacter->sheet_data['traits']['uye'];
              }
              if (empty($existingTraits['kidemli']['name']) && isset($dbCharacter->sheet_data['traits']['kidemli'])) {
                $existingTraits['kidemli'] = $dbCharacter->sheet_data['traits']['kidemli'];
              }
              if (empty($existingTraits['uzman']['name']) && isset($dbCharacter->sheet_data['traits']['uzman'])) {
                $existingTraits['uzman'] = $dbCharacter->sheet_data['traits']['uzman'];
              }
              if (empty($existingTraits['uye']['name']) && isset($dbCharacter->sheet_data['sheetData']['traits']['uye'])) {
                $existingTraits['uye'] = $dbCharacter->sheet_data['sheetData']['traits']['uye'];
              }
              if (empty($existingTraits['kidemli']['name']) && isset($dbCharacter->sheet_data['sheetData']['traits']['kidemli'])) {
                $existingTraits['kidemli'] = $dbCharacter->sheet_data['sheetData']['traits']['kidemli'];
              }
              if (empty($existingTraits['uzman']['name']) && isset($dbCharacter->sheet_data['sheetData']['traits']['uzman'])) {
                $existingTraits['uzman'] = $dbCharacter->sheet_data['sheetData']['traits']['uzman'];
              }
            }

            $slotsMeta = [
              'uye'     => ['label' => __('Member Trait'), 'slot' => 'Slot 1 • ' . __('Member'), 'badge' => 'bg-label-info', 'icon' => 'tabler-user-check', 'border' => 'rgba(115, 103, 240, 0.28)'],
              'kidemli' => ['label' => __('Senior Trait'), 'slot' => 'Slot 2 • ' . __('Senior'), 'badge' => 'bg-label-warning', 'icon' => 'tabler-award', 'border' => 'rgba(255, 171, 0, 0.28)'],
              'uzman'   => ['label' => __('Expert Trait'), 'slot' => 'Slot 3 • ' . __('Expert'), 'badge' => 'bg-label-success', 'icon' => 'tabler-shield-star', 'border' => 'rgba(40, 199, 111, 0.28)'],
            ];
          @endphp
          <div class="pf-card p-3 mb-3" id="characterTraitsCard">
            <div class="pf-banner-header d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-1_5">
                <i class="icon-base ti tabler-sparkles text-warning fs-5"></i>
                <span>{{ __('Character Traits') }} ({{ __('Rank Traits') }})</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-warning font-monospace py-0 px-2" style="font-size: 0.68rem;">3 {{ __('Slots') }}</span>
                <a href="{{ route('valiant-shade.pages-featntrait', ['character' => $dbCharacter->character_name ?? '']) }}" class="btn btn-xs btn-label-primary rounded-pill px-2 d-inline-flex align-items-center gap-1" title="{{ __('Edit in Feat & Traits Page') }}">
                  <i class="icon-base ti tabler-edit fs-6"></i> {{ __('Edit') }}
                </a>
              </div>
            </div>

            <div class="d-flex flex-column gap-2" id="traitsListContainer">
              @foreach(['uye', 'kidemli', 'uzman'] as $slotKey)
                @php
                  $meta = $slotsMeta[$slotKey];
                  $tData = $existingTraits[$slotKey] ?? [];
                  $tName = trim((string)($tData['name'] ?? ''));
                  if ($tName === '-') { $tName = ''; }
                  $tUrl = $tData['source_url'] ?? $tData['url'] ?? '';
                  if (empty($tUrl) && !empty($tName)) {
                    $tUrl = 'https://www.d20pfsrd.com/?s=' . urlencode($tName);
                  }
                  $tCat = $tData['category'] ?? '';
                @endphp
                <div class="p-2_5 rounded border bg-body d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-color: {{ $meta['border'] }} !important;">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $meta['badge'] }} font-monospace py-1 px-2 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                      <i class="icon-base ti {{ $meta['icon'] }} icon-xs"></i> {{ $meta['label'] }}
                    </span>
                    <small class="text-body-secondary font-monospace d-none d-sm-inline" style="font-size: 0.68rem;">{{ $meta['slot'] }}</small>
                  </div>

                  <div class="d-flex align-items-center gap-2 ms-auto">
                    <!-- Trait Link / Display -->
                    <a href="{{ $tUrl ?: '#' }}" target="_blank" rel="noopener noreferrer" id="sheetTraitLink_{{ $slotKey }}" class="fw-bold text-primary font-monospace fs-6 text-decoration-none d-inline-flex align-items-center gap-1 hover-underline {{ empty($tName) ? 'd-none' : '' }}" title="{{ __('Open Source') }}">
                      <span id="sheetTraitDisplay_{{ $slotKey }}">{{ $tName }}</span>
                      <i class="icon-base ti tabler-external-link fs-6 text-body-secondary"></i>
                    </a>

                    <span class="badge bg-label-secondary font-monospace py-0 px-1 {{ (empty($tName) || empty($tCat)) ? 'd-none' : '' }}" id="sheetTraitCat_{{ $slotKey }}" style="font-size: 0.65rem;">{{ $tCat }}</span>

                    <span class="text-body-secondary fst-italic small {{ !empty($tName) ? 'd-none' : '' }}" id="sheetTraitEmpty_{{ $slotKey }}">
                      {{ __('Not assigned yet') }}
                    </span>

                    <!-- Hidden inputs so JS save/state remains fully intact -->
                    <input type="hidden" class="trait-name-input" id="sheetTraitName_{{ $slotKey }}" value="{{ $tName }}" />
                    <input type="hidden" class="trait-desc-input" id="sheetTraitDesc_{{ $slotKey }}" value="{{ $tData['desc'] ?? '' }}" />
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- Feats List -->
          <div class="pf-card p-3 mb-3">
            <div class="pf-banner-header">
              <span>{{ __('Feats') }}</span>
              <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2" id="btnAddFeatRow">
                <i class="icon-base ti tabler-plus"></i> {{ __('Add Feat') }}
              </button>
            </div>

            <div class="pf-scroll" style="max-height: 350px;" id="featsContainer">
              @php
                $existingFeats = $dbCharacter ? ($dbCharacter->feats ?? []) : [];
                if ((empty($existingFeats) || count($existingFeats) === 0) && isset($dbCharacter->sheet_data['feats'])) {
                  $existingFeats = $dbCharacter->sheet_data['feats'];
                }
                if ((empty($existingFeats) || count($existingFeats) === 0) && isset($dbCharacter->sheet_data['sheetData']['feats'])) {
                  $existingFeats = $dbCharacter->sheet_data['sheetData']['feats'];
                }
              @endphp
              @if(!empty($existingFeats) && count($existingFeats) > 0)
                @foreach($existingFeats as $f)
                  @php
                    $fTitle = is_array($f) ? ($f['title'] ?? $f['feat_name'] ?? '') : (is_object($f) ? ($f->feat_name ?? $f->title ?? '') : (string) $f);
                    $fDesc = is_array($f) ? ($f['desc'] ?? $f['notes'] ?? '—') : (is_object($f) ? ($f->notes ?? $f->desc ?? '—') : '—');
                  @endphp
                  @if(!empty(trim($fTitle)))
                    <div class="p-2 mb-1 rounded bg-body border d-flex justify-content-between align-items-center feat-item">
                      <div>
                        <strong class="text-heading d-block small feat-title">{{ $fTitle }}</strong>
                        <small class="text-body-secondary feat-desc">{{ $fDesc }}</small>
                      </div>
                      <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-feat"><i class="icon-base ti tabler-x"></i></button>
                    </div>
                  @endif
                @endforeach
              @endif
            </div>
          </div>

          <!-- Special Abilities & Class Features -->
          <div class="pf-card p-3">
            <div class="pf-banner-header">
              <span>{{ __('Special Abilities') }}</span>
              <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2" id="btnAddSpecialRow">
                <i class="icon-base ti tabler-plus"></i> {{ __('Add Special Ability') }}
              </button>
            </div>

            <div class="pf-scroll" style="max-height: 520px;" id="specialsContainer"></div>
          </div>

        </div>

        <!-- Column 3: Spells & Magic System (4 Cols) -->
        <div class="col-12 col-lg-4">
          <div class="pf-card p-3">
            <div class="pf-banner-header">
              <span>{{ __('Spells & Spell Slots') }}</span>
              <span class="pf-banner-sub" id="lblSpellCastingSub">{{ __('Slots & DC') }}</span>
            </div>

            <!-- Spellcasting Parameters -->
            <div class="row g-2 mb-3 align-items-center font-monospace small">
              <div class="col-6">
                <label class="text-body-secondary" style="font-size: 0.68rem;">{{ __('CASTING STAT') }}</label>
                <select id="sheetCastingStat" class="form-select form-select-sm pf-box-input px-2 text-start">
                  <option value="int">Intelligence (INT)</option>
                  <option value="wis">Wisdom (WIS)</option>
                  <option value="cha">Charisma (CHA)</option>
                  <option value="none">{{ __('None (No Spells)') }}</option>
                </select>
              </div>
              <div class="col-6 text-center">
                <label class="text-body-secondary" style="font-size: 0.68rem;">{{ __('BASE SPELL DC') }}</label>
                <div class="pf-box-input text-warning fw-bold py-1" id="lblBaseSpellDc">—</div>
              </div>
            </div>

            <!-- Spells Slots 0 to 9th Level Table -->
            <div class="table-responsive">
              <table class="table table-sm align-middle text-center mb-2" style="font-size: 0.78rem; width: 100%; table-layout: fixed;">
                <thead>
                  <tr class="text-body-secondary font-monospace text-uppercase" style="font-size: 0.70rem;">
                    <th style="width: 15%;">{{ __('Lvl') }}</th>
                    <th style="width: 22%;" title="{{ __('Spells Known') }}">{{ __('Known') }}</th>
                    <th style="width: 21%;" title="{{ __('Spell Save DC') }}">{{ __('Save DC') }}</th>
                    <th style="width: 22%;" title="{{ __('Spells Per Day') }}">{{ __('Per Day') }}</th>
                    <th style="width: 20%;" title="{{ __('Bonus Spells from Stat') }}">{{ __('Bonus') }}</th>
                  </tr>
                </thead>
                <tbody class="font-monospace">
                  @for($lvl = 0; $lvl <= 9; $lvl++)
                    <tr id="spellRow_{{ $lvl }}">
                      <td class="fw-bold text-primary">{{ $lvl === 0 ? '0' : $lvl . ($lvl === 1 ? 'st' : ($lvl === 2 ? 'nd' : ($lvl === 3 ? 'rd' : 'th'))) }}</td>
                      <td>
                        <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact spell-known text-center mx-auto" style="width: 52px; padding: 0.15rem !important;" min="0" value="0" />
                      </td>
                      <td class="text-warning spell-dc-val fw-bold" id="spellDc_{{ $lvl }}" style="font-size: 0.85rem;">—</td>
                      <td>
                        <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact spell-per-day text-center mx-auto" style="width: 52px; padding: 0.15rem !important;" value="—" />
                      </td>
                      <td class="text-body-secondary spell-bonus-val fw-semibold" id="spellBonus_{{ $lvl }}">—</td>
                    </tr>
                  @endfor
                </tbody>
              </table>
            </div>

            <!-- Domains & Specialty School Notes -->
            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
              <label class="form-label small text-body-secondary fw-semibold mb-1" style="font-size: 0.72rem;">{{ __('Domains / Specialty School & Notes') }}</label>
              <textarea id="sheetSpellNotes" class="form-control form-control-sm pf-box-input text-start px-2" rows="3" placeholder="Domains, Bloodline Powers, School focus or prepared spells list..."></textarea>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div id="bothPagesContainer" class="d-none"></div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: LEVEL-BY-LEVEL HIT POINTS DETAIL                                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalHpBreakdown" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content pf-card border shadow-lg">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-heart-rate-monitor text-danger"></i>
          <span>{{ __('Hit Points (HP) Level Breakdown') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <div class="modal-body p-4">
        <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded bg-body border font-monospace">
          <span class="text-body-secondary small">{{ __('Class Hit Die:') }}</span>
          <strong class="text-warning fs-6" id="modalHpHitDie">d10</strong>
        </div>
        <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded bg-body border font-monospace">
          <span class="text-body-secondary small">{{ __('Constitution Modifier:') }}</span>
          <strong class="text-primary fs-6" id="modalHpConMod">+2</strong>
        </div>

        <div class="table-responsive pf-scroll mb-3" style="max-height: 260px;">
          <table class="table table-sm text-center align-middle font-monospace mb-0" id="tableLevelHpBreakdown">
            <thead>
              <tr class="text-body-secondary small text-uppercase">
                <th>{{ __('Level') }}</th>
                <th>{{ __('Die Result') }}</th>
                <th>{{ __('CON Bonus') }}</th>
                <th>{{ __('Total') }}</th>
              </tr>
            </thead>
            <tbody id="tbodyLevelHpBreakdown">
              <!-- Dynamically populated per level -->
            </tbody>
          </table>
        </div>

        <div class="mb-3">
          <label class="form-label small text-body-secondary fw-semibold">{{ __('Extra HP / Feat Bonus (Misc)') }}</label>
          @php
            $initialExtraHp = 0;
            if (isset($dbCharacter)) {
              $initialExtraHp = $dbCharacter->sheet_data['hpExtraMisc'] ?? ($dbCharacter->sheet_data['sheetData']['hpExtraMisc'] ?? 0);
            }
          @endphp
          <input type="number" id="sheetHpExtraMisc" class="form-control form-control-sm pf-box-input font-monospace text-center" value="{{ $initialExtraHp }}" />
        </div>

        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
          <button type="button" class="btn btn-xs btn-label-secondary rounded-pill px-3" id="btnModalSetAverage">
            {{ __('Take Average') }}
          </button>
          <button type="button" class="btn btn-xs btn-label-warning rounded-pill px-3" id="btnModalRollHp">
            <i class="icon-base ti tabler-dice me-1"></i> {{ __('Reroll Dice') }}
          </button>
          <div class="text-end">
            <small class="text-body-secondary d-block" style="font-size: 0.68rem;">{{ __('TOTAL MAX HP') }}</small>
            <strong class="text-primary fs-5 font-monospace" id="modalHpTotalMax">12 HP</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- FLOATING DICE DRAWER (DICE TRAY)                                          -->
<!-- ========================================================================= -->
<div class="pf-dice-drawer p-3 animate__animated animate__fadeInUp" id="diceDrawer" style="display: none;">
  <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom border-secondary border-opacity-25">
    <div class="d-flex align-items-center gap-1_5">
      <i class="icon-base ti tabler-dice-6 text-warning fs-5"></i>
      <strong class="text-heading small font-monospace">{{ __('Valiant Shade Dice Tray') }}</strong>
    </div>
    <button type="button" class="btn-close btn-close-white small" id="btnCloseDiceDrawer" aria-label="{{ __('Close') }}"></button>
  </div>

  <!-- Dice Selector Buttons -->
  <div class="d-flex align-items-center justify-content-between gap-1 mb-3">
    <button type="button" class="btn btn-outline-secondary pf-dice-btn p-0" onclick="rollDice(4)">d4</button>
    <button type="button" class="btn btn-outline-secondary pf-dice-btn p-0" onclick="rollDice(6)">d6</button>
    <button type="button" class="btn btn-outline-secondary pf-dice-btn p-0" onclick="rollDice(8)">d8</button>
    <button type="button" class="btn btn-outline-secondary pf-dice-btn p-0" onclick="rollDice(10)">d10</button>
    <button type="button" class="btn btn-outline-secondary pf-dice-btn p-0" onclick="rollDice(12)">d12</button>
    <button type="button" class="btn btn-outline-primary pf-dice-btn p-0 fw-bold" onclick="rollDice(20)">d20</button>
    <button type="button" class="btn btn-outline-warning pf-dice-btn p-0" onclick="rollDice(100)">%</button>
  </div>

  <!-- Active Roll Display -->
  <div class="p-3 mb-2 rounded-3 text-center border" style="background: rgba(18, 19, 32, 0.9); border-color: rgba(226, 185, 111, 0.3) !important;">
    <small class="text-body-secondary font-monospace d-block" id="diceRollLabel">{{ __('Last Roll Result') }}</small>
    <h1 class="mb-0 fw-bold font-monospace text-warning display-4" id="diceRollValue">—</h1>
    <small class="text-body-secondary font-monospace" id="diceRollBreakdown">{{ __('Click any die above or any stat on the sheet to roll.') }}</small>
  </div>

  <!-- Roll History Log -->
  <div class="pf-scroll" style="max-height: 120px;" id="diceRollHistory">
    <!-- Log rows injected here -->
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // -------------------------------------------------------------
  // Internationalization Dictionary for Dynamic Elements
  // -------------------------------------------------------------
  const i18n = {
    weaponName: "{{ __('Weapon Name') }}",
    weapon: "{{ __('Weapon') }}",
    attackType: "{{ __('Attack Type') }}",
    damageType: "{{ __('Damage Type') }}",
    range: "{{ __('Range') }}",
    deleteWeapon: "{{ __('Delete Weapon') }}",
    baseDie: "{{ __('Base Die') }}",
    enhancement: "{{ __('Enhancement') }}",
    critRangeMult: "{{ __('Critical Range / Mult') }}",
    critThreat: "{{ __('Critical Threat') }}",
    ammunition: "{{ __('Ammunition') }}",
    attackBonusAuto: "{{ __('Attack Bonus (Auto)') }}",
    damageAuto: "{{ __('Damage (Auto)') }}",
    critDamageAuto: "{{ __('Critical Damage (Auto)') }}",
    savedToDb: "{{ __('Saved to Database') }}",
    saveError: "{{ __('Save Error') }}",
    savedLocally: "{{ __('Saved Locally (DB Offline)') }}",
    saved: "{{ __('Saved!') }}",
    saveChanges: "{{ __('Save Changes') }}",
    savingThrow: "{{ __('Saving Throw') }}",
    roll: "{{ __('Roll') }}",
    check: "{{ __('Check') }}",
    lastRoll: "{{ __('Last Roll Result') }}"
  };

  // -------------------------------------------------------------
  // Data Payload from Controller Enums
  // -------------------------------------------------------------
  const pfData = {
    classes: @json($pfClasses ?? []),
    classSkills: @json($classSkills ?? []),
    bab: @json($bab ?? []),
    saves: @json($saves ?? []),
    progressions: @json($progressions ?? []),
    armors: @json($armors ?? []),
    saveItems: @json($saveItems ?? [])
  };

  // Official Pathfinder 1E Protective & Resistance Items Catalog for Saving Throws
  const PATHFINDER_SAVE_ITEMS = [
    { name: 'Cloak of Resistance +1', target: 'all', type: 'resistance', bonus: 1, weight: 1, slot: 'Shoulders', desc: '+1 resistance bonus on all saving throws' },
    { name: 'Cloak of Resistance +2', target: 'all', type: 'resistance', bonus: 2, weight: 1, slot: 'Shoulders', desc: '+2 resistance bonus on all saving throws' },
    { name: 'Cloak of Resistance +3', target: 'all', type: 'resistance', bonus: 3, weight: 1, slot: 'Shoulders', desc: '+3 resistance bonus on all saving throws' },
    { name: 'Cloak of Resistance +4', target: 'all', type: 'resistance', bonus: 4, weight: 1, slot: 'Shoulders', desc: '+4 resistance bonus on all saving throws' },
    { name: 'Cloak of Resistance +5', target: 'all', type: 'resistance', bonus: 5, weight: 1, slot: 'Shoulders', desc: '+5 resistance bonus on all saving throws' },
    { name: 'Vest of Resistance +1', target: 'all', type: 'resistance', bonus: 1, weight: 1, slot: 'Chest', desc: '+1 resistance bonus on all saving throws' },
    { name: 'Vest of Resistance +2', target: 'all', type: 'resistance', bonus: 2, weight: 1, slot: 'Chest', desc: '+2 resistance bonus on all saving throws' },
    { name: 'Vest of Resistance +3', target: 'all', type: 'resistance', bonus: 3, weight: 1, slot: 'Chest', desc: '+3 resistance bonus on all saving throws' },
    { name: 'Vest of Resistance +4', target: 'all', type: 'resistance', bonus: 4, weight: 1, slot: 'Chest', desc: '+4 resistance bonus on all saving throws' },
    { name: 'Vest of Resistance +5', target: 'all', type: 'resistance', bonus: 5, weight: 1, slot: 'Chest', desc: '+5 resistance bonus on all saving throws' },
    { name: 'Ring of Resistance +1', target: 'all', type: 'resistance', bonus: 1, weight: 0, slot: 'Ring', desc: '+1 resistance bonus on all saving throws' },
    { name: 'Ring of Resistance +2', target: 'all', type: 'resistance', bonus: 2, weight: 0, slot: 'Ring', desc: '+2 resistance bonus on all saving throws' },
    { name: 'Ring of Resistance +3', target: 'all', type: 'resistance', bonus: 3, weight: 0, slot: 'Ring', desc: '+3 resistance bonus on all saving throws' },
    { name: 'Ring of Resistance +4', target: 'all', type: 'resistance', bonus: 4, weight: 0, slot: 'Ring', desc: '+4 resistance bonus on all saving throws' },
    { name: 'Ring of Resistance +5', target: 'all', type: 'resistance', bonus: 5, weight: 0, slot: 'Ring', desc: '+5 resistance bonus on all saving throws' },
    { name: 'Stone of Good Luck (Luckstone)', target: 'all', type: 'luck', bonus: 1, weight: 0, slot: 'None', desc: '+1 luck bonus on saving throws, ability checks, and skill checks' },
    { name: 'Four-Leaf Clover', target: 'all', type: 'luck', bonus: 2, weight: 0, slot: 'None', desc: '+2 luck bonus on saving throws, ability checks, or skill checks (3/day)' },
    { name: 'Fortifying Stone', target: 'all', type: 'resistance', bonus: 1, weight: 0, slot: 'None', desc: '+1 resistance bonus on saving throws' },
    { name: 'Shawl of Life-Keeping', target: 'fort', type: 'resistance', bonus: 2, weight: 1, slot: 'Shoulders', desc: '+2 resistance bonus on Fortitude saves against death effects' },
    { name: 'Periapt of Proof against Poison', target: 'fort', type: 'misc', bonus: 4, weight: 0, slot: 'Neck', desc: '+4 bonus on Fortitude saves against poison' },
    { name: 'Periapt of Health', target: 'fort', type: 'misc', bonus: 4, weight: 0, slot: 'Neck', desc: 'Immunity to all disease effects and +4 Fortitude checks' },
    { name: 'Boots of the Cat', target: 'ref', type: 'misc', bonus: 2, weight: 1, slot: 'Feet', desc: '+2 bonus on Acrobatics and Reflex saving throws against falls/trips' },
    { name: 'Boots of Friendly Terrain', target: 'ref', type: 'insight', bonus: 2, weight: 1, slot: 'Feet', desc: '+2 insight bonus on initiative and Reflex saves in favored terrain' },
    { name: 'Cap of the Free Thinker', target: 'will', type: 'misc', bonus: 2, weight: 0, slot: 'Head', desc: '+2 bonus and roll-twice advantage on Will saves against mind-affecting effects' },
    { name: 'Amulet of the Planes', target: 'will', type: 'misc', bonus: 2, weight: 0, slot: 'Neck', desc: '+2 on Will saving throws against extraplanar powers' },
    { name: 'Bracers of Steadfast Resilience', target: 'fort', type: 'resistance', bonus: 2, weight: 1, slot: 'Wrists', desc: '+2 resistance bonus on Fortitude saves' },
    { name: 'Band of the Stalwart Warrior', target: 'fort', type: 'morale', bonus: 2, weight: 0, slot: 'Headband', desc: '+2 morale bonus on Fortitude saves against fatigue, exhaustion, and fear' },
    { name: 'Brooch of Shielding', target: 'all', type: 'misc', bonus: 1, weight: 0, slot: 'Neck', desc: '+1 bonus on saving throws against harmful spells' },
    { name: 'Medallion of Courage', target: 'will', type: 'morale', bonus: 2, weight: 0, slot: 'Neck', desc: '+2 morale bonus on Will saving throws against fear' },
    { name: 'All-Tool (Alchemical)', target: 'all', type: 'misc', bonus: 1, weight: 1, slot: 'None', desc: '+1 alchemical bonus assisting saving throws' }
  ];

  // Official Pathfinder 1E All 35 Skills with their core ability & armor check penalty flag
  const PF_SKILLS = [
    { key: 'acrobatics', name: 'Acrobatics', ab: 'dex', armorPen: true },
    { key: 'appraise', name: 'Appraise', ab: 'int', armorPen: false },
    { key: 'bluff', name: 'Bluff', ab: 'cha', armorPen: false },
    { key: 'climb', name: 'Climb', ab: 'str', armorPen: true },
    { key: 'craft1', name: 'Craft', ab: 'int', armorPen: false, isCraft: true },
    { key: 'craft2', name: 'Craft', ab: 'int', armorPen: false, isCraft: true },
    { key: 'diplomacy', name: 'Diplomacy', ab: 'cha', armorPen: false },
    { key: 'disable_device', name: 'Disable Device *', ab: 'dex', armorPen: true },
    { key: 'disguise', name: 'Disguise', ab: 'cha', armorPen: false },
    { key: 'escape_artist', name: 'Escape Artist', ab: 'dex', armorPen: true },
    { key: 'fly', name: 'Fly', ab: 'dex', armorPen: true },
    { key: 'handle_animal', name: 'Handle Animal *', ab: 'cha', armorPen: false },
    { key: 'heal', name: 'Heal', ab: 'wis', armorPen: false },
    { key: 'intimidate', name: 'Intimidate', ab: 'cha', armorPen: false },
    { key: 'know_arcana', name: 'Knowledge (Arcana) *', ab: 'int', armorPen: false },
    { key: 'know_dungeon', name: 'Knowledge (Dungeoneering) *', ab: 'int', armorPen: false },
    { key: 'know_engineer', name: 'Knowledge (Engineering) *', ab: 'int', armorPen: false },
    { key: 'know_geography', name: 'Knowledge (Geography) *', ab: 'int', armorPen: false },
    { key: 'know_history', name: 'Knowledge (History) *', ab: 'int', armorPen: false },
    { key: 'know_local', name: 'Knowledge (Local) *', ab: 'int', armorPen: false },
    { key: 'know_nature', name: 'Knowledge (Nature) *', ab: 'int', armorPen: false },
    { key: 'know_nobility', name: 'Knowledge (Nobility) *', ab: 'int', armorPen: false },
    { key: 'know_planes', name: 'Knowledge (Planes) *', ab: 'int', armorPen: false },
    { key: 'know_religion', name: 'Knowledge (Religion) *', ab: 'int', armorPen: false },
    { key: 'linguistics', name: 'Linguistics *', ab: 'int', armorPen: false },
    { key: 'perception', name: 'Perception', ab: 'wis', armorPen: false },
    { key: 'perform1', name: 'Perform (Oratory/Song)', ab: 'cha', armorPen: false },
    { key: 'perform2', name: 'Perform (Act/Dance)', ab: 'cha', armorPen: false },
    { key: 'profession1', name: 'Profession (Sailor/Medic) *', ab: 'wis', armorPen: false },
    { key: 'profession2', name: 'Profession (Other) *', ab: 'wis', armorPen: false },
    { key: 'ride', name: 'Ride', ab: 'dex', armorPen: true },
    { key: 'sense_motive', name: 'Sense Motive', ab: 'wis', armorPen: false },
    { key: 'sleight_of_hand', name: 'Sleight of Hand *', ab: 'dex', armorPen: true },
    { key: 'spellcraft', name: 'Spellcraft *', ab: 'int', armorPen: false },
    { key: 'stealth', name: 'Stealth', ab: 'dex', armorPen: true },
    { key: 'survival', name: 'Survival', ab: 'wis', armorPen: false },
    { key: 'swim', name: 'Swim', ab: 'str', armorPen: true },
    { key: 'use_magic_device', name: 'Use Magic Device *', ab: 'cha', armorPen: false }
  ];

  // Official Pathfinder 1E Carrying Capacity Table for STR 1 to 30 (Light / Medium / Heavy max lbs)
  const STR_CAPACITY = {
    1: [3, 6, 10], 2: [6, 13, 20], 3: [10, 20, 30], 4: [13, 26, 40], 5: [16, 33, 50],
    6: [20, 40, 60], 7: [23, 46, 70], 8: [26, 53, 80], 9: [30, 60, 90], 10: [33, 66, 100],
    11: [38, 76, 115], 12: [43, 86, 130], 13: [50, 100, 150], 14: [58, 116, 175], 15: [66, 133, 200],
    16: [76, 153, 230], 17: [86, 173, 260], 18: [100, 200, 300], 19: [116, 233, 350], 20: [133, 266, 400],
    21: [153, 306, 460], 22: [173, 346, 520], 23: [200, 400, 600], 24: [233, 466, 700], 25: [266, 533, 800],
    26: [306, 613, 920], 27: [346, 693, 1040], 28: [400, 800, 1200], 29: [466, 933, 1400], 30: [533, 1066, 1600]
  };

  // -------------------------------------------------------------
  // Storage & Active Character Identification
  // -------------------------------------------------------------
  const userStorageId = @json(Auth::user()->isAdmin() ? (Auth::user()->uuid ?? Auth::id()) : ('user-' . Auth::id()));
  const charStorageKey = 'valiant_shade_characters_' + userStorageId;
  const serverDbChar = @json($dbCharacter ?? null);

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getCharacters() {
    let raw = localStorage.getItem(charStorageKey);
    if (raw === null) {
      const legacy = localStorage.getItem('valiant_shade_characters');
      if (legacy) {
        try {
          const parsed = JSON.parse(legacy);
          if (Array.isArray(parsed)) {
            localStorage.setItem(charStorageKey, legacy);
            raw = legacy;
          }
        } catch (e) {}
      }
    }
    try {
      return JSON.parse(raw || '[]');
    } catch (e) {
      return [];
    }
  }

  function saveCharacters(chars) {
    try {
      const list = chars || [];
      localStorage.setItem(charStorageKey, JSON.stringify(list));
      localStorage.setItem('valiant_shade_characters', JSON.stringify(list));
    } catch (e) {
      console.error(e);
    }
  }

  const urlParams = new URLSearchParams(window.location.search);
  let charId = urlParams.get('id');
  let charNameParam = urlParams.get('name');
  let chars = getCharacters();
  let currentChar = null;

  // 1. If backend resolved a DB character, use it
  if (serverDbChar) {
    let parsed = serverDbChar.sheet_data || {};
    if (typeof parsed === 'string') {
      try { parsed = JSON.parse(parsed); } catch (e) { parsed = {}; }
    }
    const sDetails = (parsed && parsed.sheetData) ? parsed.sheetData : (parsed || {});

    // Synchronize fresh DB traits into sDetails.traits
    if (!sDetails.traits) sDetails.traits = {};
    if (serverDbChar.traits && Array.isArray(serverDbChar.traits)) {
      serverDbChar.traits.forEach(t => {
        if (t.slot_key) {
          sDetails.traits[t.slot_key] = {
            name: t.trait_name || '',
            desc: t.description || '',
            category: t.category || '',
            source_url: t.source_url || ''
          };
        }
      });
    }
    if (parsed.traits && typeof parsed.traits === 'object') {
      ['uye', 'kidemli', 'uzman'].forEach(k => {
        if (parsed.traits[k] && (!sDetails.traits[k] || !sDetails.traits[k].name)) {
          sDetails.traits[k] = parsed.traits[k];
        }
      });
    }

    // Synchronize fresh DB feats into sDetails.feats
    if (serverDbChar.feats && Array.isArray(serverDbChar.feats) && serverDbChar.feats.length > 0) {
      sDetails.feats = serverDbChar.feats.map(f => ({
        title: f.feat_name || f.title || '',
        desc: f.notes || f.desc || ''
      }));
    } else if (parsed.feats && Array.isArray(parsed.feats)) {
      sDetails.feats = parsed.feats;
    }

    // Synchronize stats & attributes into sDetails
    if (!sDetails.abilityScores) sDetails.abilityScores = {};
    ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(attr => {
      if (serverDbChar[attr] !== undefined && serverDbChar[attr] !== null) {
        sDetails.abilityScores[attr] = serverDbChar[attr];
      }
    });
    if (serverDbChar.level) {
      sDetails.level = serverDbChar.level;
    }
    if (serverDbChar.hp !== undefined && serverDbChar.hp !== null) {
      sDetails.currentHp = serverDbChar.hp;
      sDetails.maxHp = serverDbChar.max_hp || serverDbChar.hp;
    }
    if (serverDbChar.class_name) {
      sDetails.class = serverDbChar.class_name;
      sDetails.className = serverDbChar.class_name;
    }
    if (serverDbChar.race) {
      sDetails.race = serverDbChar.race;
    }

    currentChar = {
      id: serverDbChar.character_id || ('char_' + serverDbChar.id),
      name: serverDbChar.character_name,
      race: serverDbChar.race || 'Human',
      class: serverDbChar.class_name || 'Paladin',
      classKey: parsed.classKey || (serverDbChar.class_name || 'paladin').toLowerCase(),
      level: serverDbChar.level || 1,
      hp: serverDbChar.hp !== null && serverDbChar.hp !== undefined ? serverDbChar.hp : (sDetails.currentHp || 12),
      maxHp: serverDbChar.max_hp !== null && serverDbChar.max_hp !== undefined ? serverDbChar.max_hp : (sDetails.maxHp || 12),
      hitDie: serverDbChar.hit_die || sDetails.hitDie || 'd10',
      active: true,
      sheetData: sDetails
    };

    // Update localStorage cache immediately with fresh server data
    const existingIdx = chars.findIndex(c => c.id === currentChar.id || c.name === currentChar.name);
    if (existingIdx !== -1) {
      chars[existingIdx] = currentChar;
    } else {
      chars.unshift(currentChar);
    }
    saveCharacters(chars);
  }

  // 2. Fallback to localStorage if no server DB character loaded
  if (!currentChar && charId) {
    currentChar = chars.find(c => c.id === charId || c.name === charNameParam);
  }
  if (!currentChar && charNameParam) {
    currentChar = chars.find(c => c.name === charNameParam);
  }
  if (!currentChar && chars.length > 0) {
    currentChar = chars.find(c => c.active) || chars[0];
  }

  // 3. Default starter character
  if (!currentChar) {
    currentChar = {
      id: charId || ('char_' + Date.now()),
      name: charNameParam || 'Valiant Adventurer',
      race: 'Human',
      class: 'Paladin',
      classKey: 'paladin',
      level: 1,
      hp: 12,
      maxHp: 12,
      hitDie: 'd10',
      active: true,
      sheetData: {}
    };
  }

  let sData = currentChar.sheetData || {};

  // Update top header character name display
  const topNameEl = document.getElementById('topCharNameDisplay');
  if (topNameEl) topNameEl.textContent = currentChar.name;

  // -------------------------------------------------------------
  // Helper Math & Formatting Functions
  // -------------------------------------------------------------
  function calcMod(score) {
    score = parseInt(score, 10);
    if (isNaN(score)) score = 10;
    return Math.floor((score - 10) / 2);
  }

  function formatMod(val) {
    val = parseInt(val, 10);
    if (isNaN(val)) val = 0;
    return val >= 0 ? '+' + val : '' + val;
  }

  function cleanText(val) {
    if (!val) return '';
    return String(val).replace(/[\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/gu, '').trim();
  }

  // -------------------------------------------------------------
  // Populate Initial Header & Identity
  // -------------------------------------------------------------
  document.getElementById('sheetCharName').value = currentChar.name || '';
  document.getElementById('sheetLevelInput').value = currentChar.level || 1;
  document.getElementById('sheetClassSelect').value = currentChar.classKey || 'paladin';
  document.getElementById('sheetAlignmentSelect').value = sData.alignment || 'Lawful Good (LG)';
  document.getElementById('sheetPlayerName').value = sData.player || @json(Auth::user()->display_name ?: (Auth::user()->username ?: 'Adventurer'));
  document.getElementById('sheetRaceInput').value = cleanText(currentChar.race) || 'Human';
  document.getElementById('sheetDeityInput').value = sData.deity || 'Iomedae';
  document.getElementById('sheetHomelandInput').value = sData.homeland || 'Korvosa';
  document.getElementById('sheetSizeSelect').value = sData.size || 'Medium';
  document.getElementById('sheetGenderInput').value = sData.gender || 'Male';
  document.getElementById('sheetAgeInput').value = sData.age || '25';
  document.getElementById('sheetHeightInput').value = sData.height || '6 ft. 1 in.';
  document.getElementById('sheetEyesHairInput').value = sData.eyesHair || 'Brown / Hazel';

  // Core Vitals
  document.getElementById('sheetCurrentHp').value = sData.currentHp !== undefined ? sData.currentHp : (currentChar.hp || 12);
  document.getElementById('sheetDrInput').value = sData.dr || '';
  document.getElementById('sheetNonlethalInput').value = sData.nonlethal || '';
  document.getElementById('sheetSpeedBase').value = sData.speedBase || 30;
  document.getElementById('sheetSpeedArmor').value = sData.speedArmor || 20;
  document.getElementById('sheetSpeedFly').value = sData.speedFly || '';
  document.getElementById('sheetSpeedSwim').value = sData.speedSwim || '';
  document.getElementById('sheetSpeedClimb').value = sData.speedClimb || '';
  document.getElementById('sheetSpeedBurrow').value = sData.speedBurrow || '';
  document.getElementById('sheetInitMisc').value = sData.initMisc || 0;

  const hpExtraElInit = document.getElementById('sheetHpExtraMisc');
  if (hpExtraElInit) {
    const savedExtra = sData.hpExtraMisc !== undefined ? sData.hpExtraMisc : (parseInt(hpExtraElInit.value, 10) || 0);
    hpExtraElInit.value = savedExtra;
    sData.hpExtraMisc = savedExtra;
  }

  // Populate Traits (Üye, Kıdemli, Uzman)
  const traitsInit = sData.traits || {};
  ['uye', 'kidemli', 'uzman'].forEach(r => {
    const tData = traitsInit[r] || {};
    const rawName = tData.name || '';
    const cleanName = (rawName && rawName !== '-') ? rawName.trim() : '';
    const rawUrl = tData.source_url || tData.url || (cleanName ? `https://www.d20pfsrd.com/?s=${encodeURIComponent(cleanName)}` : '');
    const rawCat = tData.category || '';

    const nameInp = document.getElementById(`sheetTraitName_${r}`);
    const descInp = document.getElementById(`sheetTraitDesc_${r}`);
    if (nameInp) nameInp.value = cleanName;
    if (descInp && tData.desc !== undefined) descInp.value = tData.desc || '';

    const linkEl = document.getElementById(`sheetTraitLink_${r}`);
    const displayEl = document.getElementById(`sheetTraitDisplay_${r}`);
    const emptyEl = document.getElementById(`sheetTraitEmpty_${r}`);
    const catEl = document.getElementById(`sheetTraitCat_${r}`);

    if (cleanName) {
      if (displayEl) displayEl.textContent = cleanName;
      if (linkEl) {
        linkEl.href = rawUrl;
        linkEl.classList.remove('d-none');
      }
      if (emptyEl) emptyEl.classList.add('d-none');
      if (catEl) {
        if (rawCat) {
          catEl.textContent = rawCat;
          catEl.classList.remove('d-none');
        } else {
          catEl.classList.add('d-none');
        }
      }
    } else {
      if (linkEl) linkEl.classList.add('d-none');
      if (emptyEl) emptyEl.classList.remove('d-none');
      if (catEl) catEl.classList.add('d-none');
    }
  });

  // Ability Scores
  const defaultScores = { str: 16, dex: 12, con: 14, int: 10, wis: 12, cha: 14 };
  const abScores = sData.abilityScores || defaultScores;
  ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(k => {
    const input = document.getElementById(`score_${k}`);
    if (input) input.value = abScores[k] !== undefined ? abScores[k] : defaultScores[k];
  });

  // AC Breakdown Inputs
  document.getElementById('sheetAcNatural').value = sData.acNatural || 0;
  document.getElementById('sheetAcDeflect').value = sData.acDeflect || 0;
  const initAcMiscEl = document.getElementById('sheetAcMisc');
  if (initAcMiscEl) initAcMiscEl.value = sData.acMisc || 0;

  // Saves base, ability, magic & misc
  ['fort', 'ref', 'will'].forEach(k => {
    const baseInp = document.getElementById(`saveBase_${k}`);
    if (baseInp && sData[`saveBase_${k}`] !== undefined) baseInp.value = sData[`saveBase_${k}`];
    const abInp = document.getElementById(`saveAbility_${k}`);
    if (abInp) {
      if (sData[`saveAbility_${k}`] !== undefined) {
        abInp.value = sData[`saveAbility_${k}`];
      } else {
        const fallbackMod = (k === 'fort') ? calcMod(abScores.con) : ((k === 'ref') ? calcMod(abScores.dex) : calcMod(abScores.wis));
        abInp.value = fallbackMod;
      }
    }
    const magicInp = document.getElementById(`saveMagic_${k}`);
    if (magicInp) magicInp.value = sData[`saveMagic_${k}`] !== undefined ? sData[`saveMagic_${k}`] : 0;
    const miscInp = document.getElementById(`saveMisc_${k}`);
    if (miscInp) miscInp.value = sData[`saveMisc_${k}`] !== undefined ? sData[`saveMisc_${k}`] : 0;
  });
  const saveCondEl = document.getElementById('sheetSaveConditional');
  if (saveCondEl) saveCondEl.value = sData.saveConditional || '';
  document.getElementById('sheetSrInput').value = sData.sr || '';

  // Money
  document.getElementById('sheetMoneyCp').value = sData.moneyCp !== undefined ? sData.moneyCp : 15;
  document.getElementById('sheetMoneySp').value = sData.moneySp !== undefined ? sData.moneySp : 48;
  document.getElementById('sheetMoneyGp').value = sData.moneyGp !== undefined ? sData.moneyGp : 120;
  document.getElementById('sheetMoneyPp').value = sData.moneyPp !== undefined ? sData.moneyPp : 5;

  // Skills conditionals & languages
  document.getElementById('sheetSkillConditional').value = sData.skillConditional || '';
  document.getElementById('sheetLanguages').value = sData.languages || 'Common';

  // Spells
  const defaultCastingStat = getCastingStatForClass(currentChar.classKey || document.getElementById('sheetClassSelect')?.value);
  document.getElementById('sheetCastingStat').value = sData.castingStat || defaultCastingStat;
  document.getElementById('sheetSpellNotes').value = sData.spellNotes || '';

  // -------------------------------------------------------------
  // Data Population Helpers for Lists (Weapons, AC Items, Gear, Feats, Specials, Spells)
  // -------------------------------------------------------------
  function createWeaponRowHtml(idx, w = {}) {
    const isAlchemist = (currentChar.classKey || document.getElementById('sheetClassSelect')?.value || '').toLowerCase() === 'alchemist';
    let defaultName = '';
    let defaultAtkType = 'melee';
    let defaultType = '';
    let defaultRange = '—';
    let defaultBaseDie = '1d8';
    let defaultEnh = 0;
    let defaultCritRange = '20';
    let defaultCritMult = 2;
    let defaultAmmo = '';

    if (idx === 1 && isAlchemist) {
      defaultName = 'Bomb';
      defaultAtkType = 'ranged';
      defaultType = 'Fire';
      defaultRange = '20 ft.';
      const curLvl = parseInt(document.getElementById('sheetLevelInput')?.value, 10) || currentChar.level || 1;
      defaultBaseDie = getBombDamageDie(curLvl);
      const intScore = parseInt(document.getElementById('score_int')?.value, 10) || 10;
      defaultAmmo = `${Math.max(1, curLvl + calcMod(intScore))}/day`;
    } else if (idx === 1) {
      defaultName = 'Longsword +1';
      defaultAtkType = 'melee';
      defaultType = 'Slashing';
      defaultRange = '—';
      defaultBaseDie = '1d8';
      defaultEnh = 1;
      defaultCritRange = '19-20';
      defaultCritMult = 2;
    } else if (idx === 2 && !isAlchemist) {
      defaultName = 'Heavy Crossbow';
      defaultAtkType = 'ranged';
      defaultType = 'Piercing';
      defaultRange = '80 ft.';
      defaultBaseDie = '1d10';
      defaultEnh = 0;
      defaultCritRange = '19-20';
      defaultCritMult = 2;
      defaultAmmo = '30 bolts';
    }

    const name = w.name !== undefined ? w.name : defaultName;
    const atkType = w.atkType || defaultAtkType;
    const type = w.type !== undefined ? w.type : defaultType;
    const range = w.range !== undefined ? w.range : defaultRange;
    const baseDie = w.baseDie || defaultBaseDie;
    const enh = w.enh !== undefined ? w.enh : defaultEnh;
    const miscAtk = w.miscAtk !== undefined ? w.miscAtk : 0;
    const critRange = w.critRange || defaultCritRange;
    const critMult = w.critMult !== undefined ? w.critMult : defaultCritMult;
    const ammo = w.ammo !== undefined ? w.ammo : defaultAmmo;

    return `
      <div class="pf-weapon-block weapon-row" data-idx="${idx}">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md-4">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.weaponName} (${i18n.weapon} ${idx})</label>
            <input type="text" class="form-control form-control-sm pf-box-input text-start px-2 wp-name" placeholder="e.g. Dagger, Light Crossbow" value="${escapeHtml(name)}" />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.attackType}</label>
            <select class="form-select form-select-sm pf-box-input wp-atk-type">
              <option value="melee" ${atkType === 'melee' ? 'selected' : ''}>Melee (STR)</option>
              <option value="ranged" ${atkType === 'ranged' ? 'selected' : ''}>Ranged (DEX)</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.damageType}</label>
            <input type="text" class="form-control form-control-sm pf-box-input wp-type" placeholder="S / P / B" value="${escapeHtml(type)}" />
          </div>
          <div class="col-8 col-md-2">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.range}</label>
            <input type="text" class="form-control form-control-sm pf-box-input wp-range" placeholder="—" value="${escapeHtml(range)}" />
          </div>
          <div class="col-4 col-md-1 d-flex align-items-end justify-content-end">
            <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-weapon" title="${i18n.deleteWeapon}">
              <i class="icon-base ti tabler-trash fs-5"></i>
            </button>
          </div>

          <div class="col-6 col-md-2 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.baseDie}</label>
            <input type="text" class="form-control form-control-sm pf-box-input font-monospace wp-base-die" placeholder="1d8" value="${escapeHtml(baseDie)}" />
          </div>
          <div class="col-3 col-md-1 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;" title="Enhancement Bonus">Ench(+)</label>
            <input type="number" class="form-control form-control-sm pf-box-input font-monospace wp-enh text-center px-1" min="0" max="5" value="${enh}" />
          </div>
          <div class="col-3 col-md-1 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0 text-truncate" style="font-size: 0.68rem;">Misc Atk</label>
            <input type="number" class="form-control form-control-sm pf-box-input font-monospace wp-misc-atk text-center px-1" value="${miscAtk}" />
          </div>
          <div class="col-6 col-md-4 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0 d-flex align-items-center justify-content-between text-truncate" style="font-size: 0.68rem;">
              <span>${i18n.critRangeMult}</span>
              <span class="wp-feat-crit-indicator badge bg-label-info py-0 px-1 d-none" style="font-size: 0.58rem;">Imp. Crit</span>
            </label>
            <div class="d-flex align-items-center gap-1">
              <input type="text" class="form-control form-control-sm pf-box-input font-monospace wp-crit-range text-center px-1" style="width: 50%; min-width: 60px;" placeholder="20" title="Threat Range (e.g. 20, 19-20, 18-20)" value="${escapeHtml(critRange || '20')}" />
              <span class="text-body-secondary fw-bold px-1 user-select-none">/</span>
              <select class="form-select form-select-sm pf-box-input font-monospace wp-crit-mult text-center px-1" style="width: 50%; min-width: 60px;">
                <option value="2" ${critMult == 2 ? 'selected' : ''}>x2</option>
                <option value="3" ${critMult == 3 ? 'selected' : ''}>x3</option>
                <option value="4" ${critMult == 4 ? 'selected' : ''}>x4</option>
              </select>
            </div>
          </div>
          <div class="col-6 col-md-4 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.ammunition}</label>
            <input type="text" class="form-control form-control-sm pf-box-input wp-ammo" placeholder="—" value="${escapeHtml(ammo)}" />
          </div>

          <div class="col-12 col-md-4 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.attackBonusAuto}</label>
            <div class="pf-mod-box font-monospace fw-bold text-primary py-1 wp-atk-total" title="BAB + STR/DEX + Enhancement + Misc">+0</div>
            <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-atk-formula">BAB+STR+Enh</span></small>
          </div>
          <div class="col-12 col-md-4 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.damageAuto}</label>
            <div class="pf-box-input font-monospace fw-bold text-warning py-1 wp-dmg-total">1d8+0</div>
            <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-dmg-formula">Die+STR+Enh</span></small>
          </div>
          <div class="col-12 col-md-4 mt-1">
            <label class="form-label small text-body-secondary fw-semibold mb-0" style="font-size: 0.68rem;">${i18n.critDamageAuto}</label>
            <div class="pf-box-input font-monospace fw-bold text-danger py-1 wp-crit-dmg-total">2d8+0</div>
            <small class="text-body-secondary font-monospace d-block mt-0" style="font-size: 0.62rem;"><span class="wp-crit-dmg-formula">x2</span></small>
          </div>
        </div>
      </div>
    `;
  }

  // Official Pathfinder 1E Alchemist Bomb Damage Progression (1d6 at 1st, +1d6 per 2 levels: 1, 3, 5, 7...)
  function getBombDamageDie(level) {
    const lvl = Math.min(20, Math.max(1, parseInt(level, 10) || 1));
    const dice = Math.ceil(lvl / 2); // 1-2 => 1d6, 3-4 => 2d6, 5-6 => 3d6, etc.
    return `${dice}d6`;
  }

  function syncAlchemistBomb(level, intMod) {
    const container = document.getElementById('weaponsContainer');
    if (!container) return;
    const lvl = Math.min(20, Math.max(1, parseInt(level, 10) || 1));
    const baseDie = getBombDamageDie(lvl);
    const ammoCount = Math.max(1, lvl + (intMod !== undefined ? intMod : 0));

    // Look for existing Bomb row
    const rows = container.querySelectorAll('.weapon-row');
    let bombRow = null;
    rows.forEach(row => {
      const name = (row.querySelector('.wp-name')?.value || '').toLowerCase();
      if (name.includes('bomb') || name.includes('bomba')) {
        bombRow = row;
      }
    });

    if (bombRow) {
      const baseDieEl = bombRow.querySelector('.wp-base-die');
      if (baseDieEl) baseDieEl.value = baseDie;
      const ammoEl = bombRow.querySelector('.wp-ammo');
      if (ammoEl && (!ammoEl.value || ammoEl.value.includes('/day'))) {
        ammoEl.value = `${ammoCount}/day`;
      }
    } else {
      const bombData = {
        name: 'Bomb',
        atkType: 'ranged',
        type: 'Fire',
        range: '20 ft.',
        baseDie: baseDie,
        enh: 0,
        miscAtk: 0,
        critRange: '20',
        critMult: 2,
        ammo: `${ammoCount}/day`
      };
      container.insertAdjacentHTML('afterbegin', createWeaponRowHtml(1, bombData));
      attachWeaponEvents();
    }
  }

  function setupAlchemistWeapons(level, intMod) {
    syncAlchemistBomb(level, intMod);
  }

  function populateWeaponsFromData(weapons) {
    if (!weapons || !Array.isArray(weapons) || weapons.length === 0) return;
    const container = document.getElementById('weaponsContainer');
    if (!container) return;
    container.innerHTML = '';
    weapons.forEach((w, idx) => {
      container.insertAdjacentHTML('beforeend', createWeaponRowHtml(idx + 1, w));
    });
  }

  function findArmorByName(name) {
    if (!name || !pfData.armors || !Array.isArray(pfData.armors)) return null;
    const q = String(name).trim().toLowerCase();
    let found = pfData.armors.find(a => (a.name || '').trim().toLowerCase() === q);
    if (found) return found;
    const qClean = q.replace(/,\s*/g, ' ');
    found = pfData.armors.find(a => {
      const an = (a.name || '').toLowerCase().replace(/,\s*/g, ' ');
      return an === qClean;
    });
    return found || null;
  }

  function isShieldItem(item) {
    if (!item) return false;
    if (item.bonus_type === 'shield') return true;
    if (item.is_shield === true) return true;
    const cat = (item.category || '').toLowerCase();
    if (cat.includes('shield')) return true;
    const name = (item.name || '').toLowerCase();
    if (name.includes('shield') || name.includes('buckler') || name.includes('klar') || name.includes('madu')) return true;
    return false;
  }

  function attachAcItemAutocomplete(tr) {
    const input = tr.querySelector('.ac-item-name');
    const dropdown = tr.querySelector('.ac-item-autocomplete');
    const typeSelect = tr.querySelector('.ac-item-type');
    const bonusInput = tr.querySelector('.ac-item-bonus');
    const penInput = tr.querySelector('.ac-item-pen');
    const wtInput = tr.querySelector('.ac-item-weight');
    if (!input || !dropdown) return;

    function doSearch() {
      const q = (input.value || '').trim().toLowerCase();
      if (!q || q.length < 1) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
      }

      const armors = pfData.armors || [];
      const qTokens = q.split(/\s+/).filter(Boolean);
      const matches = armors.filter(a => {
        const name = (a.name || '').toLowerCase();
        const cat = (a.category || '').toLowerCase();
        return qTokens.every(tok => name.includes(tok) || cat.includes(tok));
      }).slice(0, 15);

      if (matches.length === 0) {
        dropdown.innerHTML = '<div class="p-2 text-body-secondary small text-center font-monospace">{{ __("No matching item found") }}</div>';
        dropdown.style.display = 'block';
        return;
      }

      dropdown.innerHTML = '';
      matches.forEach(item => {
        const div = document.createElement('div');
        div.className = 'pf-armor-result-item d-flex align-items-center justify-content-between font-monospace p-1 rounded mb-1';
        div.style.cursor = 'pointer';
        div.style.transition = 'background-color 0.15s ease';

        const isShield = item.bonus_type === 'shield' || isShieldItem(item);
        const isDeflect = item.bonus_type === 'deflection';
        const isNat = item.bonus_type === 'natural';
        const isMisc = item.bonus_type === 'misc' || item.bonus_type === 'dodge';

        let icon = 'tabler-shield-filled';
        if (isShield) icon = 'tabler-shield';
        else if (isDeflect) icon = 'tabler-ring';
        else if (isNat) icon = 'tabler-sparkles';
        else if (isMisc) icon = 'tabler-gem';

        const typeLabel = item.bonus_type ? (item.bonus_type.charAt(0).toUpperCase() + item.bonus_type.slice(1)) : (isShield ? 'Shield' : 'Armor');
        const bonusStr = formatMod(item.bonus || 0);
        const maxDexStr = (item.max_dex !== null && item.max_dex !== undefined) ? `Max Dex +${item.max_dex}` : '';
        const penStr = (item.check_penalty !== 0 && item.check_penalty !== null) ? `ACP ${item.check_penalty}` : '';
        const wtStr = item.weight ? `${item.weight} lbs` : '0 lbs';

        div.innerHTML = `
          <div class="d-flex align-items-center gap-2 text-start">
            <i class="icon-base ti ${icon} text-warning fs-5"></i>
            <div>
              <span class="fw-bold text-heading d-block" style="font-size: 0.78rem;">${escapeHtml(item.name)}</span>
              <span class="badge bg-label-secondary py-0 px-1" style="font-size: 0.62rem;">${escapeHtml(item.category || typeLabel)}</span>
            </div>
          </div>
          <div class="text-end font-monospace" style="font-size: 0.7rem;">
            <div class="fw-bold text-primary">${bonusStr} ${typeLabel}</div>
            <div class="text-body-secondary" style="font-size: 0.62rem;">${[maxDexStr, penStr, wtStr].filter(Boolean).join(' | ')}</div>
          </div>
        `;

        div.onmouseenter = () => div.style.backgroundColor = 'rgba(226, 185, 111, 0.15)';
        div.onmouseleave = () => div.style.backgroundColor = 'transparent';

        div.onmousedown = function (e) {
          e.preventDefault();
          input.value = item.name;

          let targetType = item.bonus_type || (isShield ? 'shield' : 'armor');
          if (typeSelect) typeSelect.value = targetType;
          if (bonusInput) bonusInput.value = item.bonus !== undefined ? item.bonus : 0;
          if (penInput) penInput.value = item.check_penalty !== undefined ? item.check_penalty : 0;
          if (wtInput) wtInput.value = item.weight !== undefined ? item.weight : 0;

          if (item.max_dex !== null && item.max_dex !== undefined) {
            tr.setAttribute('data-max-dex', item.max_dex);
          } else {
            tr.removeAttribute('data-max-dex');
          }
          if (item.category) tr.setAttribute('data-category', item.category);

          // If active and targetType is armor/shield, ensure other armors/shields are deactivated
          const activeChk = tr.querySelector('.ac-item-active');
          if (activeChk && activeChk.checked && (targetType === 'armor' || targetType === 'shield')) {
            document.querySelectorAll('#acItemsTbody tr').forEach(otherTr => {
              if (otherTr !== tr) {
                const otherType = otherTr.querySelector('.ac-item-type')?.value || 'armor';
                if (otherType === targetType) {
                  const otherChk = otherTr.querySelector('.ac-item-active');
                  if (otherChk) otherChk.checked = false;
                }
              }
            });
          }

          dropdown.style.display = 'none';
          dropdown.innerHTML = '';

          recalculateAll();
          triggerAutoSave();
        };

        dropdown.appendChild(div);
      });
      dropdown.style.display = 'block';
    }

    input.addEventListener('input', doSearch);
    input.addEventListener('focus', () => {
      if (input.value.trim().length > 0) doSearch();
    });
    input.addEventListener('blur', () => {
      setTimeout(() => {
        dropdown.style.display = 'none';
      }, 200);
    });
  }

  function createAcItemRow(data = {}) {
    const tr = document.createElement('tr');
    const name = data.name || '';
    const type = (data.type || 'armor').toLowerCase();
    const bonus = data.bonus !== undefined && data.bonus !== null ? data.bonus : 0;
    const pen = data.pen !== undefined && data.pen !== null ? data.pen : 0;
    const weight = data.weight !== undefined && data.weight !== null ? data.weight : 0;
    const maxDex = data.maxDex !== undefined && data.maxDex !== null ? data.maxDex : (data.max_dex || '');
    const category = data.category || '';
    const isActive = data.is_active !== undefined ? !!data.is_active : (data.isActive !== undefined ? !!data.isActive : true);

    if (maxDex !== '') tr.setAttribute('data-max-dex', maxDex);
    if (category) tr.setAttribute('data-category', category);

    tr.className = isActive ? 'row-ac-active' : 'row-ac-inactive';

    tr.innerHTML = `
      <td class="p-1 text-center align-middle" style="width: 58px;">
        <div class="form-check form-switch d-flex justify-content-center m-0 p-0" title="Active / Equipped">
          <input type="checkbox" class="form-check-input ac-item-active m-0 cursor-pointer" ${isActive ? 'checked' : ''} style="width: 2.1rem; height: 1.15rem;" />
        </div>
      </td>
      <td class="position-relative p-1">
        <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start ac-item-name" placeholder="Item Name (e.g. Mithral Shirt)" value="${escapeHtml(name)}" autocomplete="off" />
        <div class="ac-item-autocomplete dropdown-menu shadow-lg pf-scroll p-1" style="display: none; position: absolute; top: 100%; left: 0; min-width: 280px; max-width: 360px; max-height: 240px; overflow-y: auto; z-index: 1050; background: #1b1c2d; border: 1px solid rgba(226, 185, 111, 0.35);"></div>
      </td>
      <td class="p-1">
        <select class="form-select form-select-sm pf-box-input pf-input-compact ac-item-type font-monospace p-1 text-center" style="font-size: 0.68rem;">
          <option value="armor" ${type === 'armor' ? 'selected' : ''}>Armor</option>
          <option value="shield" ${type === 'shield' ? 'selected' : ''}>Shield</option>
          <option value="deflection" ${type === 'deflection' ? 'selected' : ''}>Deflect</option>
          <option value="natural" ${type === 'natural' ? 'selected' : ''}>Natural</option>
          <option value="dodge" ${type === 'dodge' ? 'selected' : ''}>Dodge</option>
          <option value="misc" ${type === 'misc' ? 'selected' : ''}>Misc</option>
        </select>
      </td>
      <td class="p-1"><input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center ac-item-bonus font-monospace" value="${bonus}" /></td>
      <td class="p-1"><input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center ac-item-pen font-monospace" value="${pen}" /></td>
      <td class="p-1"><input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center ac-item-weight font-monospace" value="${weight}" /></td>
      <td class="p-1"><button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-row" title="Delete Item"><i class="icon-base ti tabler-x"></i></button></td>
    `;

    attachAcItemAutocomplete(tr);
    return tr;
  }

  function renderEquippedArmorChips() {
    const container = document.getElementById('equippedArmorList');
    if (!container) return;
    container.innerHTML = '';
  }

  function equipArmor(item) {
    if (!item) return;
    const tbody = document.getElementById('acItemsTbody');
    if (!tbody) return;
    const isShield = item.bonus_type === 'shield' || isShieldItem(item);
    const targetType = item.bonus_type || (isShield ? 'shield' : 'armor');

    // Deactivate existing active items of this type if equip is called
    if (targetType === 'armor' || targetType === 'shield') {
      document.querySelectorAll('#acItemsTbody tr').forEach(otherTr => {
        const otherType = otherTr.querySelector('.ac-item-type')?.value || 'armor';
        if (otherType === targetType) {
          const otherChk = otherTr.querySelector('.ac-item-active');
          if (otherChk) otherChk.checked = false;
        }
      });
    }

    const tr = createAcItemRow({
      name: item.name,
      type: targetType,
      bonus: item.bonus || 0,
      pen: item.check_penalty || 0,
      weight: parseInt(item.weight, 10) || 0,
      maxDex: item.max_dex,
      category: item.category,
      is_active: true
    });
    tbody.appendChild(tr);
    attachDynamicRowEvents();
    recalculateAll();
    triggerAutoSave();
  }

  function setupArmorSearch() {
    // No-op (search bar integrated into AC item row autocomplete)
  }

  function populateAcItemsFromData(acItems) {
    const tbody = document.getElementById('acItemsTbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (Array.isArray(acItems) && acItems.length > 0) {
      let hasActiveArmor = false;
      let hasActiveShield = false;

      acItems.forEach(item => {
        const found = findArmorByName(item.name);
        const maxDex = item.max_dex !== undefined && item.max_dex !== null && item.max_dex !== ''
          ? item.max_dex
          : (found && found.max_dex !== undefined && found.max_dex !== null ? found.max_dex : '');
        const category = item.category || (found ? found.category : '');
        let type = item.type;
        if (!type) {
          if (found && found.bonus_type) {
            type = found.bonus_type;
          } else if (isShieldItem({ name: item.name, category })) {
            type = 'shield';
          } else {
            type = 'armor';
          }
        }

        let isActive = true;
        if (item.is_active !== undefined) {
          isActive = !!item.is_active;
        } else if (item.isActive !== undefined) {
          isActive = !!item.isActive;
        } else {
          // Default rule for legacy data: only first armor and first shield are active
          if (type === 'armor') {
            isActive = !hasActiveArmor;
          } else if (type === 'shield') {
            isActive = !hasActiveShield;
          }
        }

        if (isActive) {
          if (type === 'armor') hasActiveArmor = true;
          if (type === 'shield') hasActiveShield = true;
        }

        const tr = createAcItemRow({
          name: item.name || '',
          type: type,
          bonus: item.bonus !== undefined ? item.bonus : 0,
          pen: item.pen !== undefined ? item.pen : 0,
          weight: item.weight !== undefined ? item.weight : 0,
          maxDex: maxDex,
          category: category,
          is_active: isActive
        });
        tbody.appendChild(tr);
      });
    }
    if (typeof attachDynamicRowEvents === 'function') {
      attachDynamicRowEvents();
    }
  }

  function findSaveItemByName(name) {
    if (!name) return null;
    const q = name.trim().toLowerCase();
    const catalog = (pfData && pfData.saveItems && Array.isArray(pfData.saveItems) && pfData.saveItems.length > 0)
      ? pfData.saveItems
      : PATHFINDER_SAVE_ITEMS;
    return catalog.find(a => (a.name || '').trim().toLowerCase() === q) || null;
  }

  function attachSaveItemAutocomplete(tr) {
    const input = tr.querySelector('.save-item-name');
    const dropdown = tr.querySelector('.save-item-autocomplete');
    const targetSelect = tr.querySelector('.save-item-target');
    const typeSelect = tr.querySelector('.save-item-type');
    const bonusInput = tr.querySelector('.save-item-bonus');
    const wtInput = tr.querySelector('.save-item-weight');

    if (!input || !dropdown) return;

    function doSearch() {
      const q = (input.value || '').trim().toLowerCase();
      if (!q || q.length < 1) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
      }

      const items = (pfData && pfData.saveItems && Array.isArray(pfData.saveItems) && pfData.saveItems.length > 0)
        ? pfData.saveItems
        : PATHFINDER_SAVE_ITEMS;

      const qTokens = q.split(/\s+/).filter(Boolean);
      const matches = items.filter(it => {
        const name = (it.name || '').toLowerCase();
        const desc = (it.desc || it.description || '').toLowerCase();
        const target = (it.target || '').toLowerCase();
        const type = (it.type || '').toLowerCase();
        return qTokens.every(tok => name.includes(tok) || desc.includes(tok) || target.includes(tok) || type.includes(tok));
      }).slice(0, 15);

      if (matches.length === 0) {
        dropdown.innerHTML = '<div class="p-2 text-body-secondary small text-center font-monospace">{{ __("No matching protection item found") }}</div>';
        dropdown.style.display = 'block';
        return;
      }

      dropdown.innerHTML = '';
      matches.forEach(item => {
        const div = document.createElement('div');
        div.className = 'pf-save-result-item d-flex align-items-center justify-content-between font-monospace p-1 rounded mb-1';
        div.style.cursor = 'pointer';
        div.style.transition = 'background-color 0.15s ease';

        const nameLower = (item.name || '').toLowerCase();
        let icon = 'tabler-shield-spark';
        if (nameLower.includes('cloak') || nameLower.includes('cape')) icon = 'tabler-hanger';
        else if (nameLower.includes('ring')) icon = 'tabler-ring';
        else if (nameLower.includes('vest') || nameLower.includes('shirt')) icon = 'tabler-shirt';
        else if (nameLower.includes('stone') || nameLower.includes('luck') || nameLower.includes('clover')) icon = 'tabler-sparkles';
        else if (nameLower.includes('periapt') || nameLower.includes('amulet') || nameLower.includes('medallion')) icon = 'tabler-medal';
        else if (nameLower.includes('boot')) icon = 'tabler-shoe';

        let targetBadgeClass = 'bg-label-primary';
        let targetLabel = 'All Saves';
        if (item.target === 'fort') {
          targetBadgeClass = 'bg-label-danger';
          targetLabel = 'Fortitude';
        } else if (item.target === 'ref') {
          targetBadgeClass = 'bg-label-success';
          targetLabel = 'Reflex';
        } else if (item.target === 'will') {
          targetBadgeClass = 'bg-label-info';
          targetLabel = 'Will';
        }

        const bonusStr = formatMod(item.bonus || 0);
        const typeStr = item.type ? (item.type.charAt(0).toUpperCase() + item.type.slice(1)) : 'Resistance';
        const wtStr = item.weight !== undefined ? `${item.weight} lbs` : '1 lbs';
        const slotStr = item.slot || '';

        div.innerHTML = `
          <div class="d-flex align-items-center gap-2 text-start">
            <i class="icon-base ti ${icon} text-warning fs-5"></i>
            <div>
              <span class="fw-bold text-heading d-block" style="font-size: 0.78rem;">${escapeHtml(item.name)}</span>
              <div class="d-flex align-items-center gap-1">
                <span class="badge ${targetBadgeClass} py-0 px-1" style="font-size: 0.62rem;">${targetLabel}</span>
                <span class="badge bg-label-secondary py-0 px-1" style="font-size: 0.62rem;">${typeStr}</span>
              </div>
            </div>
          </div>
          <div class="text-end font-monospace" style="font-size: 0.7rem;">
            <div class="fw-bold text-warning">${bonusStr} ${typeStr}</div>
            <div class="text-body-secondary" style="font-size: 0.62rem;">${[slotStr, wtStr].filter(Boolean).join(' | ')}</div>
          </div>
        `;

        div.onmouseenter = () => div.style.backgroundColor = 'rgba(226, 185, 111, 0.15)';
        div.onmouseleave = () => div.style.backgroundColor = 'transparent';

        div.onmousedown = function (e) {
          e.preventDefault();
          input.value = item.name;
          if (targetSelect) targetSelect.value = item.target || 'all';
          if (typeSelect) typeSelect.value = item.type || 'resistance';
          if (bonusInput) bonusInput.value = item.bonus !== undefined ? item.bonus : 1;
          if (wtInput) wtInput.value = item.weight !== undefined ? item.weight : 1;

          dropdown.style.display = 'none';
          dropdown.innerHTML = '';

          recalculateAll();
          triggerAutoSave();
        };

        dropdown.appendChild(div);
      });
      dropdown.style.display = 'block';
    }

    input.addEventListener('input', doSearch);
    input.addEventListener('focus', () => {
      if (input.value.trim().length > 0) doSearch();
    });
    input.addEventListener('blur', () => {
      setTimeout(() => {
        dropdown.style.display = 'none';
      }, 200);
    });
  }

  function createSaveItemRow(data = {}) {
    const tr = document.createElement('tr');
    const name = data.name || '';
    const target = (data.target || 'all').toLowerCase();
    const type = (data.type || 'resistance').toLowerCase();
    const bonus = data.bonus !== undefined && data.bonus !== null ? data.bonus : 1;
    const weight = data.weight !== undefined && data.weight !== null ? data.weight : 1;
    const isActive = data.is_active !== undefined ? !!data.is_active : (data.isActive !== undefined ? !!data.isActive : true);

    tr.className = isActive ? 'row-save-active' : 'row-save-inactive';

    tr.innerHTML = `
      <td class="p-1 text-center align-middle" style="width: 58px;">
        <div class="form-check form-switch d-flex justify-content-center m-0 p-0" title="Active / Equipped">
          <input type="checkbox" class="form-check-input save-item-active m-0 cursor-pointer" ${isActive ? 'checked' : ''} style="width: 2.1rem; height: 1.15rem;" />
        </div>
      </td>
      <td class="position-relative p-1">
        <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start save-item-name" placeholder="Item Name (e.g. Cloak of Resistance +2)" value="${escapeHtml(name)}" autocomplete="off" />
        <div class="save-item-autocomplete dropdown-menu shadow-lg pf-scroll p-1" style="display: none; position: absolute; top: 100%; left: 0; min-width: 290px; max-width: 380px; max-height: 240px; overflow-y: auto; z-index: 1050; background: #1b1c2d; border: 1px solid rgba(226, 185, 111, 0.35);"></div>
      </td>
      <td class="p-1">
        <select class="form-select form-select-sm pf-box-input pf-input-compact save-item-target font-monospace p-1 text-center" style="font-size: 0.68rem;">
          <option value="all" ${target === 'all' ? 'selected' : ''}>All</option>
          <option value="fort" ${target === 'fort' ? 'selected' : ''}>Fortitude</option>
          <option value="ref" ${target === 'ref' ? 'selected' : ''}>Reflex</option>
          <option value="will" ${target === 'will' ? 'selected' : ''}>Will</option>
        </select>
      </td>
      <td class="p-1">
        <select class="form-select form-select-sm pf-box-input pf-input-compact save-item-type font-monospace p-1 text-center" style="font-size: 0.68rem;">
          <option value="resistance" ${type === 'resistance' ? 'selected' : ''}>Resistance</option>
          <option value="luck" ${type === 'luck' ? 'selected' : ''}>Luck</option>
          <option value="morale" ${type === 'morale' ? 'selected' : ''}>Morale</option>
          <option value="insight" ${type === 'insight' ? 'selected' : ''}>Insight</option>
          <option value="sacred" ${type === 'sacred' ? 'selected' : ''}>Sacred</option>
          <option value="misc" ${type === 'misc' ? 'selected' : ''}>Misc</option>
        </select>
      </td>
      <td class="p-1"><input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center save-item-bonus font-monospace" value="${bonus}" /></td>
      <td class="p-1"><input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center save-item-weight font-monospace" value="${weight}" /></td>
      <td class="p-1"><button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-save-row" title="Delete Item"><i class="icon-base ti tabler-x"></i></button></td>
    `;

    attachSaveItemAutocomplete(tr);
    return tr;
  }

  function equipSaveItem(item) {
    if (!item) return;
    const tbody = document.getElementById('saveItemsTbody');
    if (!tbody) return;

    const tr = createSaveItemRow({
      name: item.name,
      target: item.target || 'all',
      type: item.type || 'resistance',
      bonus: item.bonus || 1,
      weight: parseInt(item.weight, 10) || 1,
      is_active: true
    });
    tbody.appendChild(tr);
    attachDynamicRowEvents();
    recalculateAll();
    triggerAutoSave();
  }

  function populateSaveItemsFromData(saveItems) {
    const tbody = document.getElementById('saveItemsTbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (Array.isArray(saveItems) && saveItems.length > 0) {
      saveItems.forEach(item => {
        const tr = createSaveItemRow({
          name: item.name || '',
          target: item.target || 'all',
          type: item.type || 'resistance',
          bonus: item.bonus !== undefined ? item.bonus : 1,
          weight: item.weight !== undefined ? item.weight : 1,
          is_active: item.is_active !== undefined ? !!item.is_active : (item.isActive !== undefined ? !!item.isActive : true)
        });
        tbody.appendChild(tr);
      });
    }
    if (typeof attachDynamicRowEvents === 'function') {
      attachDynamicRowEvents();
    }
  }

  function populateGearFromData(gear) {
    if (!gear || !Array.isArray(gear) || gear.length === 0) return;
    const container = document.getElementById('gearItemsContainer');
    if (!container) return;
    container.innerHTML = '';
    gear.forEach(item => {
      const div = document.createElement('div');
      div.className = 'd-flex align-items-center gap-1 mb-1';
      div.innerHTML = `
        <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start gear-name" placeholder="Item Name" value="${escapeHtml(item.name || '')}" />
        <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center gear-wt font-monospace" style="width: 60px;" placeholder="Wt" value="${item.weight !== undefined ? item.weight : 0}" />
        <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-gear"><i class="icon-base ti tabler-x"></i></button>
      `;
      container.appendChild(div);
    });
  }

  function populateFeatsFromData(feats) {
    const container = document.getElementById('featsContainer');
    if (!container) return;
    container.innerHTML = '';
    if (!feats || !Array.isArray(feats) || feats.length === 0) return;
    feats.forEach(f => {
      const title = typeof f === 'string' ? f : (f.title || f.feat_name || '');
      const desc = typeof f === 'string' ? '' : (f.desc || f.notes || '—');
      if (!title) return;
      const div = document.createElement('div');
      div.className = 'p-2 mb-1 rounded bg-body border d-flex justify-content-between align-items-center feat-item';
      div.innerHTML = `
        <div>
          <strong class="text-heading d-block small feat-title">${escapeHtml(title)}</strong>
          <small class="text-body-secondary feat-desc">${escapeHtml(desc)}</small>
        </div>
        <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-feat"><i class="icon-base ti tabler-x"></i></button>
      `;
      container.appendChild(div);
    });
  }

  const REPEATABLE_CHOICE_SPECIALS = new Set([
    'discovery',
    'bonus feat',
    'rage power',
    'rogue talent',
    'mercy',
    'combat style feat',
    'order ability',
    'teamwork feat',
    'magus arcana',
    'revelation',
    'hex',
    'major hex',
    'grand hex',
    'bloodline feat',
    'bloodline spell',
    'bloodline power',
    'mystery spell',
    'deeds',
    'versatile performance'
  ]);

  function getSpecialScalingKey(title, badge) {
    if (!title || typeof title !== 'string') return null;
    if (badge && badge !== 'Class' && !badge.startsWith('Lvl ')) {
      return null;
    }
    const t = title.trim().toLowerCase();

    // Repeatable choices must never be collapsed
    for (const rep of REPEATABLE_CHOICE_SPECIALS) {
      if (t === rep || t.startsWith(rep + ' ')) {
        return null;
      }
    }

    // Trailing dice e.g. "bomb 1d6", "sneak attack +1d6", "channel energy 1d6"
    let m = t.match(/^(.+?)\s+(\+?\d+d\d+)$/i);
    if (m) return m[1].trim();

    // Trailing per-day frequency e.g. "smite evil 1/day", "challenge 1/day", "judgment 1/day", "wild shape 1/day"
    m = t.match(/^(.+?)\s+(\d+\/day)$/i);
    if (m) return m[1].trim();

    // Trailing plus bonus e.g. "poison resistance +2", "trap sense +1", "bravery +1", "nimble +1", "inspire courage +1", "inspire competence +2"
    m = t.match(/^(.+?)\s+(\+\d+)$/i);
    if (m) return m[1].trim();

    // Trailing DR e.g. "damage reduction 1/—" or "damage reduction 1/-"
    m = t.match(/^(.+?)\s+(\d+\/[—\-])$/);
    if (m) return m[1].trim();

    // Trailing slow fall distance e.g. "slow fall 20 ft." or "slow fall any distance"
    if (t.startsWith('slow fall ')) return 'slow fall';

    // Trailing roman numeral e.g. "summon monster I"
    m = t.match(/^(.+?)\s+(i|ii|iii|iv|v|vi|vii|viii|ix|x)$/i);
    if (m) return m[1].trim();

    // Trailing number/tier e.g. "armor training 1", "weapon training 1", "gun training 1"
    m = t.match(/^(.+?)\s+(\d+)$/i);
    if (m) return m[1].trim();

    return null;
  }

  function getSpecialPowerValue(title) {
    if (!title || typeof title !== 'string') return 0;
    let m = title.match(/(\d+)d\d+/i);
    if (m) return parseInt(m[1], 10);
    m = title.match(/(\d+)\/day/i);
    if (m) return parseInt(m[1], 10);
    m = title.match(/\+(\d+)/);
    if (m) return parseInt(m[1], 10);
    m = title.match(/(\d+)\/[—\-]/);
    if (m) return parseInt(m[1], 10);
    m = title.match(/(\d+)$/);
    if (m) return parseInt(m[1], 10);
    return 0;
  }

  function filterScalingSpecials(list) {
    if (!Array.isArray(list) || list.length === 0) return [];
    const highestMap = new Map();

    list.forEach((item, index) => {
      const key = getSpecialScalingKey(item.title, item.badge);
      if (key) {
        const lvl = parseInt((item.badge || '').replace(/\D+/g, ''), 10) || 0;
        const power = getSpecialPowerValue(item.title);
        const existing = highestMap.get(key);
        if (!existing || lvl > existing.lvl || (lvl === existing.lvl && (power > existing.power || index > existing.index))) {
          highestMap.set(key, { item, lvl, power, index });
        }
      }
    });

    return list.filter((item, index) => {
      const key = getSpecialScalingKey(item.title, item.badge);
      if (!key) return true;
      const highest = highestMap.get(key);
      return highest && highest.index === index;
    });
  }

  function getClassSpecialsList(classKey, level) {
    if (!classKey) return [];
    classKey = classKey.toLowerCase();
    const prog = pfData.progressions[classKey];
    if (!prog || !prog.specials) return [];

    const specials = [];
    const className = prog.name || (classKey.charAt(0).toUpperCase() + classKey.slice(1));

    for (let lvl = 1; lvl <= level; lvl++) {
      const specStr = prog.specials[lvl];
      if (!specStr || specStr.trim() === '—' || specStr.trim() === '-') continue;

      const parts = specStr.split(',').map(s => s.trim()).filter(Boolean);
      parts.forEach(part => {
        const title = part.charAt(0).toUpperCase() + part.slice(1);
        specials.push({
          badge: `Lvl ${lvl}`,
          title: title,
          desc: `${className} Lvl ${lvl} Class Feature`
        });
      });
    }
    return filterScalingSpecials(specials);
  }

  function renderSpecialRow(s, container) {
    const badge = s.badge || 'Class';
    const title = s.title || '';
    const desc = s.desc || '—';
    if (!title) return;
    const div = document.createElement('div');
    div.className = 'p-2 mb-1 rounded bg-body border d-flex justify-content-between align-items-center special-item';
    div.innerHTML = `
      <div>
        <span class="badge bg-label-warning font-monospace py-0 me-1">${escapeHtml(badge)}</span>
        <strong class="text-heading small">${escapeHtml(title)}</strong>
        <small class="text-body-secondary d-block">${escapeHtml(desc)}</small>
      </div>
      <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-special"><i class="icon-base ti tabler-x"></i></button>
    `;
    container.appendChild(div);
  }

  function populateSpecialsFromData(specials) {
    const container = document.getElementById('specialsContainer');
    if (!container) return;
    container.innerHTML = '';

    const classKey = (currentChar.classKey || document.getElementById('sheetClassSelect')?.value || '').toLowerCase();
    const level = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput')?.value, 10) || currentChar.level || 1));

    let listToRender = specials;
    const isOldDummyPaladinData = Array.isArray(specials) && specials.length > 0 && classKey !== 'paladin' &&
      specials.every(s => (s.title || '').includes('Smite Evil') || (s.title || '').includes('Aura of Good'));

    if (!listToRender || !Array.isArray(listToRender) || listToRender.length === 0 || isOldDummyPaladinData) {
      listToRender = getClassSpecialsList(classKey, level);
    } else {
      listToRender = filterScalingSpecials(listToRender);
    }

    listToRender.forEach(s => renderSpecialRow(s, container));
  }

  function updateClassSpecials(classKey, level) {
    const container = document.getElementById('specialsContainer');
    if (!container) return;

    // Preserve any custom user-added specials
    const customSpecials = [];
    container.querySelectorAll('.special-item').forEach(div => {
      const badge = div.querySelector('.badge')?.textContent.trim() || '';
      const title = div.querySelector('strong')?.textContent.trim() || '';
      const desc = div.querySelector('small')?.textContent.trim() || '';
      if (badge === 'Special' || (!badge.startsWith('Lvl ') && !badge.startsWith('Class'))) {
        if (title) customSpecials.push({ badge, title, desc });
      }
    });

    const classSpecials = getClassSpecialsList(classKey, level);
    const combined = filterScalingSpecials([...classSpecials, ...customSpecials]);

    container.innerHTML = '';
    combined.forEach(s => renderSpecialRow(s, container));
    if (typeof attachDynamicRowEvents === 'function') {
      attachDynamicRowEvents();
    }
  }

  function populateSpellsFromData(spells) {
    if (!spells) return;
    if (spells.spellNotes) document.getElementById('sheetSpellNotes').value = spells.spellNotes;
    if (Array.isArray(spells.slots)) {
      const rows = document.querySelectorAll('#tab-page2 table tbody tr');
      spells.slots.forEach(slot => {
        const lvl = slot.lvl;
        if (rows[lvl]) {
          const knownInp = rows[lvl].querySelector('.spell-known');
          const perDayInp = rows[lvl].querySelector('.spell-per-day');
          const usedInp = rows[lvl].querySelector('.spell-used');
          if (knownInp && slot.known !== undefined) knownInp.value = slot.known;
          if (perDayInp && slot.perDay !== undefined && slot.perDay !== null) perDayInp.value = slot.perDay;
          if (usedInp && slot.used !== undefined) usedInp.value = slot.used;
        }
      });
    }
  }

  // Populate dynamic lists from saved sheetData
  const activeClassKey = (currentChar.classKey || document.getElementById('sheetClassSelect')?.value || '').toLowerCase();
  if (sData.weapons && Array.isArray(sData.weapons) && sData.weapons.length > 0) {
    populateWeaponsFromData(sData.weapons);
    if (activeClassKey === 'alchemist') {
      const initInt = parseInt(document.getElementById('score_int')?.value, 10) || (currentChar.sheetData?.abilityScores?.int ?? 10);
      const initIntMod = calcMod(initInt);
      syncAlchemistBomb(currentChar.level || 1, initIntMod);
    }
  } else if (activeClassKey === 'alchemist') {
    const initInt = parseInt(document.getElementById('score_int')?.value, 10) || (currentChar.sheetData?.abilityScores?.int ?? 10);
    const initIntMod = calcMod(initInt);
    syncAlchemistBomb(currentChar.level || 1, initIntMod);
  } else {
    populateWeaponsFromData(sData.weapons);
  }
  attachWeaponEvents();
  populateAcItemsFromData(sData.acItems);
  populateSaveItemsFromData(sData.saveItems);
  populateGearFromData(sData.gear);
  populateFeatsFromData(sData.feats);
  populateSpecialsFromData(sData.specials);
  populateSpellsFromData(sData.spells);

  // -------------------------------------------------------------
  // AUTOMATION 1: Build Skills Table with Class Skill Automation
  // -------------------------------------------------------------
  function getSelectedClassSkills(classKey) {
    if (!classKey) classKey = document.getElementById('sheetClassSelect').value;
    classKey = (classKey || 'paladin').toLowerCase();
    return pfData.classSkills[classKey] || [];
  }

  function renderSkillsTable() {
    const tbody = document.getElementById('skillsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const currentClassKey = document.getElementById('sheetClassSelect').value;
    const officialClassSkills = getSelectedClassSkills(currentClassKey);
    const savedSkillState = sData.skills || {};

    PF_SKILLS.forEach(sk => {
      const saved = savedSkillState[sk.key] || {};
      // If user hasn't explicitly customized isClassSkill, use official class skills!
      const isClassSkill = (saved.isClassSkill !== undefined) ? saved.isClassSkill : officialClassSkills.includes(sk.key);
      const ranks = parseInt(saved.ranks, 10) || 0;
      const misc = parseInt(saved.misc, 10) || 0;

      const tr = document.createElement('tr');
      tr.className = isClassSkill ? 'is-class-skill' : '';
      tr.setAttribute('data-skill-key', sk.key);
      tr.setAttribute('data-ab', sk.ab);
      tr.setAttribute('data-pen', sk.armorPen ? '1' : '0');

      let skillNameHtml = `<span class="fw-semibold text-heading skill-name-label cursor-pointer" title="${i18n.roll}: 1d20 + ${sk.name}">${sk.name}</span>`;
      if (sk.isCraft) {
        const subName = saved.customSubname || '';
        skillNameHtml = `
          <span class="fw-semibold text-heading skill-name-label cursor-pointer" title="${i18n.roll}: 1d20 + ${sk.name}">${sk.name}</span>
          <span class="text-body-secondary small">(</span><input type="text" class="form-control form-control-sm d-inline-block p-0 px-1 border-0 border-bottom text-heading font-monospace skill-custom-subname" style="width: 80px; height: 18px; font-size: 0.72rem; background: transparent;" placeholder="________" value="${escapeHtml(subName)}" title="Craft type" /><span class="text-body-secondary small">)</span>
        `;
      }

      tr.innerHTML = `
        <td class="text-center">
          <input type="checkbox" class="form-check-input chk-class-skill" style="cursor: pointer;" ${isClassSkill ? 'checked' : ''} title="Class Skill (+3 bonus with ≥1 rank)" />
        </td>
        <td>
          ${skillNameHtml}
          ${sk.armorPen ? '<span class="badge bg-label-secondary font-monospace py-0 px-1 ms-1" style="font-size: 0.6rem;" title="Armor Check Penalty applies">ACP</span>' : ''}
        </td>
        <td class="text-center">
          <span class="badge bg-label-primary font-monospace fw-bold fs-6 skill-total-badge" id="skTotal_${sk.key}">+0</span>
        </td>
        <td class="text-center font-monospace text-body-secondary small skill-ab-mod-text">
          =${sk.ab.toUpperCase()} <span class="skill-ab-val">+0</span>
        </td>
        <td class="text-center">
          <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact font-monospace text-center skill-ranks-input" min="0" max="20" value="${ranks}" />
        </td>
        <td class="text-center">
          <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact font-monospace text-center skill-misc-input" value="${misc}" />
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-icon btn-text-warning btn-xs btn-roll-skill" title="${i18n.roll}: 1d20 + ${sk.name}">
            <i class="icon-base ti tabler-dice-5"></i>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    attachSkillEvents();
  }

  function attachSkillEvents() {
    // Checkbox toggle
    document.querySelectorAll('.chk-class-skill').forEach(chk => {
      chk.addEventListener('change', function () {
        const tr = this.closest('tr');
        if (this.checked) {
          tr.classList.add('is-class-skill');
        } else {
          tr.classList.remove('is-class-skill');
        }
        recalculateSkills();
        triggerAutoSave();
      });
    });

    // Ranks & Misc input changes
    document.querySelectorAll('.skill-ranks-input, .skill-misc-input').forEach(inp => {
      inp.addEventListener('input', function () {
        recalculateSkills();
        triggerAutoSave();
      });
    });

    // Custom craft subname changes
    document.querySelectorAll('.skill-custom-subname').forEach(inp => {
      inp.addEventListener('input', function () {
        triggerAutoSave();
      });
    });

    // Click skill name or dice button to roll
    document.querySelectorAll('.skill-name-label, .btn-roll-skill').forEach(el => {
      el.addEventListener('click', function () {
        const tr = this.closest('tr');
        const name = tr.querySelector('.skill-name-label').textContent.trim();
        const customSub = tr.querySelector('.skill-custom-subname')?.value;
        const displayName = customSub ? `${name} (${customSub})` : name;
        const total = parseInt(tr.querySelector('.skill-total-badge').textContent, 10) || 0;
        rollD20WithMod(total, `${displayName} ${i18n.check}`);
      });
    });
  }

  // -------------------------------------------------------------
  // AUTOMATION 2: Sınıf & Seviye Seçimi Otomasyonu (Class & Level Change)
  // -------------------------------------------------------------
  function onClassOrLevelChange() {
    const classSelect = document.getElementById('sheetClassSelect');
    const classKey = classSelect.value;
    const lvl = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput').value, 10) || 1));
    const race = cleanText(document.getElementById('sheetRaceInput').value) || 'Human';

    const topBadge = document.getElementById('sheetTopLevelBadge');
    if (topBadge) topBadge.textContent = `Level ${lvl}`;
    document.getElementById('sheetLvlCounter').textContent = lvl;

    // 1. Hit Die
    const classMeta = pfData.classes[classKey] || {};
    const hd = classMeta.hit_die || 'd10';
    document.getElementById('sheetHitDie').value = hd;
    document.getElementById('sheetHitDieBadge').textContent = `HD: ${hd}`;
    document.getElementById('modalHpHitDie').textContent = hd;

    // 2. BAB (Base Attack Bonus)
    const babTableKey = classMeta.bab_table || 'bab_section_1';
    const babTable = pfData.bab[babTableKey] || {};
    const babVal = babTable[lvl] || `+${lvl}`;
    document.getElementById('sheetBabInput').value = babVal;

    // 3. Saves Progression
    const savesTable = pfData.saves[classKey] || {};
    const curSave = savesTable[lvl] || { fort: '+0', ref: '+0', will: '+0' };
    document.getElementById('saveBase_fort').value = parseInt(curSave.fort, 10) || 0;
    document.getElementById('saveBase_ref').value = parseInt(curSave.ref, 10) || 0;
    document.getElementById('saveBase_will').value = parseInt(curSave.will, 10) || 0;

    const conMod = calcMod(document.getElementById('score_con')?.value);
    const dexMod = calcMod(document.getElementById('score_dex')?.value);
    const wisMod = calcMod(document.getElementById('score_wis')?.value);
    const fortAb = document.getElementById('saveAbility_fort');
    const refAb = document.getElementById('saveAbility_ref');
    const willAb = document.getElementById('saveAbility_will');
    if (fortAb && (!fortAb.value || fortAb.value === '0')) fortAb.value = conMod;
    if (refAb && (!refAb.value || refAb.value === '0')) refAb.value = dexMod;
    if (willAb && (!willAb.value || willAb.value === '0')) willAb.value = wisMod;

    // 4. Update Official Class Skills Checkboxes
    const officialSkills = getSelectedClassSkills(classKey);
    document.querySelectorAll('#skillsTableBody tr').forEach(tr => {
      const skKey = tr.getAttribute('data-skill-key');
      const chk = tr.querySelector('.chk-class-skill');
      if (chk) {
        const isOfficial = officialSkills.includes(skKey);
        chk.checked = isOfficial;
        if (isOfficial) {
          tr.classList.add('is-class-skill');
        } else {
          tr.classList.remove('is-class-skill');
        }
      }
    });

    // 5. Calculate Skill Points Pool Formula Label
    const baseRanks = classMeta.skill_ranks || 2;
    const isHuman = race.toLowerCase().includes('human');
    const humanBonus = isHuman ? 1 : 0;
    const formulaLbl = document.getElementById('lblClassSkillRankFormula');
    if (formulaLbl) {
      formulaLbl.textContent = `${baseRanks}${isHuman ? '+1(Human)' : ''}+INT x ${lvl} Lvl`;
    }

    // 6. Alchemist Weapon Automation: Scale bomb damage & ammo without wiping user-added weapons
    const isAlchemist = (classKey || '').toLowerCase() === 'alchemist';
    if (isAlchemist) {
      const intScore = parseInt(document.getElementById('score_int')?.value, 10) || 10;
      const intMod = calcMod(intScore);
      syncAlchemistBomb(lvl, intMod);
    }

    // 7. Update Class Special Abilities for current class & level
    updateClassSpecials(classKey, lvl);

    // 8. Update Spells & Spell Slots from class progression
    updateClassSpellSlots(classKey, lvl, null, true);

    recalculateAll();
    triggerAutoSave();
  }

  // -------------------------------------------------------------
  // AUTOMATION 3: Recalculate All Values in Real-Time
  // -------------------------------------------------------------
  function recalculateAll() {
    const lvl = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput').value, 10) || 1));
    const race = cleanText(document.getElementById('sheetRaceInput').value) || 'Human';
    const classKey = document.getElementById('sheetClassSelect').value;
    const classMeta = pfData.classes[classKey] || {};
    const baseClassRanks = classMeta.skill_ranks || 2;

    // 1. Ability Modifiers
    const mods = {};
    ['str', 'dex', 'con', 'int', 'wis', 'cha'].forEach(k => {
      const scoreEl = document.getElementById(`score_${k}`);
      const baseScore = parseInt(scoreEl ? scoreEl.value : 10, 10) || 10;
      const mod = calcMod(baseScore);
      mods[k] = mod;

      const modValEl = document.getElementById(`mod_val_${k}`);
      const modEl = document.getElementById(`mod_${k}`);
      if (modValEl) {
        modValEl.textContent = formatMod(mod);
      } else if (modEl) {
        modEl.textContent = formatMod(mod);
      }
    });

    // 2. Initiative
    const initMisc = parseInt(document.getElementById('sheetInitMisc').value, 10) || 0;
    const initTotal = mods.dex + initMisc;
    document.getElementById('lblInitDexMod').textContent = formatMod(mods.dex);
    document.getElementById('sheetInitTotal').textContent = formatMod(initTotal);

    // 3. Armor Class (AC) & Pathfinder 1E Max Dex Rule
    updateAcItemsTotals();

    // Determine if any equipped armor/shield caps Dexterity bonus
    let minMaxDex = null;
    document.querySelectorAll('#acItemsTbody tr').forEach(tr => {
      const isActive = tr.querySelector('.ac-item-active')?.checked ?? true;
      if (!isActive) return;

      const type = tr.querySelector('.ac-item-type')?.value || 'armor';
      if (type !== 'armor' && type !== 'shield') return;

      let maxDex = tr.getAttribute('data-max-dex');
      const name = tr.querySelector('.ac-item-name')?.value;
      if ((maxDex === null || maxDex === undefined || maxDex === '') && name) {
        const found = findArmorByName(name);
        if (found && found.max_dex !== null && found.max_dex !== undefined) {
          maxDex = found.max_dex;
        }
      }
      if (maxDex !== null && maxDex !== undefined && maxDex !== '' && !isNaN(parseInt(maxDex, 10))) {
        const val = parseInt(maxDex, 10);
        if (minMaxDex === null || val < minMaxDex) {
          minMaxDex = val;
        }
      }
    });

    // Pathfinder 1E Rule:
    // Armor's Max Dex restricts character's Dex bonus.
    // It does NOT restrict a negative Dexterity penalty (penalties still fully apply).
    const rawDexMod = mods.dex;
    let effectiveDex = rawDexMod;
    const badgeDexCapInfo = document.getElementById('badgeDexCapInfo');
    const dexCapNotice = document.getElementById('dexCapNotice');

    if (minMaxDex !== null && rawDexMod > minMaxDex) {
      effectiveDex = minMaxDex;
      if (badgeDexCapInfo) {
        badgeDexCapInfo.className = 'badge bg-label-warning font-monospace py-1 px-2';
        badgeDexCapInfo.innerHTML = `<i class="icon-base ti tabler-alert-triangle me-1"></i>Max Dex: +${minMaxDex} (Dex mod ${formatMod(rawDexMod)} capped)`;
      }
      if (dexCapNotice) dexCapNotice.classList.remove('d-none');
    } else if (minMaxDex !== null) {
      effectiveDex = rawDexMod;
      if (badgeDexCapInfo) {
        badgeDexCapInfo.className = 'badge bg-label-secondary font-monospace py-1 px-2';
        badgeDexCapInfo.textContent = `Max Dex: +${minMaxDex} (Applied Dex: ${formatMod(rawDexMod)})`;
      }
      if (dexCapNotice) dexCapNotice.classList.remove('d-none');
    } else {
      effectiveDex = rawDexMod;
      if (dexCapNotice) dexCapNotice.classList.add('d-none');
    }

    const acArmor = parseInt(document.getElementById('sheetAcArmor').value, 10) || 0;
    const acShield = parseInt(document.getElementById('sheetAcShield').value, 10) || 0;
    const acNatural = parseInt(document.getElementById('sheetAcNatural').value, 10) || 0;
    const acDeflect = parseInt(document.getElementById('sheetAcDeflect').value, 10) || 0;
    const acMisc = parseInt(document.getElementById('sheetAcMisc')?.value, 10) || 0;
    const sizeSelect = document.getElementById('sheetSizeSelect');
    const sizeMod = getSizeModifier(sizeSelect.value);

    document.getElementById('sheetAcDex').value = effectiveDex;
    document.getElementById('sheetAcSize').value = sizeMod;

    // Pathfinder 1E Formulas:
    // Total AC = 10 + Armor + Shield + Dex(effective) + Size + Natural + Deflect + Misc
    const totalAc = 10 + acArmor + acShield + effectiveDex + sizeMod + acNatural + acDeflect + acMisc;
    // Touch AC = 10 + Dex(effective) + Size + Deflect + Misc (ignores armor, shield, natural)
    const touchAc = 10 + effectiveDex + sizeMod + acDeflect + acMisc;
    // Flat-Footed AC = 10 + Armor + Shield + Size + Natural + Deflect + Misc + min(0, Dex) (loses positive Dex bonus)
    const flatAc = 10 + acArmor + acShield + sizeMod + acNatural + acDeflect + acMisc + Math.min(0, effectiveDex);

    document.getElementById('sheetAcTotal').textContent = totalAc;
    document.getElementById('sheetAcTouch').textContent = touchAc;
    document.getElementById('sheetAcFlat').textContent = flatAc;

    // 4. Saves & Protective Items
    updateSaveItemsTotals();
    ['fort', 'ref', 'will'].forEach(k => {
      recalculateSaveTotal(k);
    });

    // 5. BAB, CMB & CMD
    const babStr = document.getElementById('sheetBabInput').value || '+1';
    const mainBab = parseInt(babStr.replace('+', ''), 10) || 0;
    const cmbTotal = mainBab + mods.str + sizeMod;
    const cmdTotal = 10 + mainBab + mods.str + mods.dex + sizeMod;
    document.getElementById('sheetCmbTotal').textContent = formatMod(cmbTotal);
    document.getElementById('sheetCmdTotal').textContent = cmdTotal;

    // 6. Hit Points
    recalculateHp(mods.con, lvl);

    // 7. Carrying Capacity from STR
    const strScore = parseInt(document.getElementById('score_str').value, 10) || 10;
    updateCarryingCapacity(strScore);

    // 8. Skill Ranks Calculation
    recalculateSkills(mods);

    // 9. Spells DC & Slots
    updateClassSpellSlots(classKey, lvl, mods, false);

    // 10. Weapons Auto-Calculation
    recalculateWeapons(mods, mainBab);
  }

  function getSizeModifier(sizeName) {
    const map = {
      'Colossal': -8, 'Gargantuan': -4, 'Huge': -2, 'Large': -1,
      'Medium': 0, 'Small': 1, 'Tiny': 2, 'Diminutive': 4, 'Fine': 8
    };
    return map[sizeName] !== undefined ? map[sizeName] : 0;
  }

  // -------------------------------------------------------------
  // AUTOMATION 4: Skills Calculations (Trained +3 & Rank Pools)
  // -------------------------------------------------------------
  function recalculateSkills(mods) {
    if (!mods) {
      mods = {
        str: calcMod(document.getElementById('score_str').value),
        dex: calcMod(document.getElementById('score_dex').value),
        con: calcMod(document.getElementById('score_con').value),
        int: calcMod(document.getElementById('score_int').value),
        wis: calcMod(document.getElementById('score_wis').value),
        cha: calcMod(document.getElementById('score_cha').value)
      };
    }

    const lvl = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput').value, 10) || 1));
    const race = cleanText(document.getElementById('sheetRaceInput').value) || 'Human';
    const classKey = document.getElementById('sheetClassSelect').value;
    const baseClassRanks = (pfData.classes[classKey] && pfData.classes[classKey].skill_ranks) || 2;
    const humanBonus = race.toLowerCase().includes('human') ? 1 : 0;

    // Pathfinder 1E Rule: Minimum 1 rank per level even if INT is heavily negative!
    const ranksPerLevel = Math.max(1, baseClassRanks + mods.int + humanBonus);
    const totalAvailableRanks = ranksPerLevel * lvl;

    let totalSpentRanks = 0;
    const totalArmorPen = parseInt(document.getElementById('acItemTotalPen')?.textContent, 10) || 0;

    document.querySelectorAll('#skillsTableBody tr').forEach(tr => {
      const skKey = tr.getAttribute('data-skill-key');
      const abKey = tr.getAttribute('data-ab') || 'int';
      const armorPen = tr.getAttribute('data-pen') === '1';

      const isClassSkill = tr.querySelector('.chk-class-skill').checked;
      const ranks = parseInt(tr.querySelector('.skill-ranks-input').value, 10) || 0;
      const misc = parseInt(tr.querySelector('.skill-misc-input').value, 10) || 0;
      totalSpentRanks += ranks;

      const abMod = mods[abKey] || 0;
      tr.querySelector('.skill-ab-val').textContent = formatMod(abMod);

      // Pathfinder rule: +3 trained bonus if class skill AND at least 1 rank assigned!
      const classSkillBonus = (isClassSkill && ranks > 0) ? 3 : 0;
      const penVal = armorPen ? totalArmorPen : 0;
      const totalBonus = abMod + ranks + classSkillBonus + misc + penVal;

      const badge = tr.querySelector('.skill-total-badge');
      badge.textContent = formatMod(totalBonus);
      if (ranks > 0) {
        badge.className = 'badge bg-label-success font-monospace fw-bold fs-6 skill-total-badge';
      } else {
        badge.className = 'badge bg-label-primary font-monospace fw-bold fs-6 skill-total-badge';
      }
    });

    // Update Skill Ranks Pool Badge
    const spentEl = document.getElementById('spentSkillRanks');
    const totalEl = document.getElementById('totalAvailableSkillRanks');
    const poolBadge = document.getElementById('skillRanksPoolBadge');

    if (spentEl) spentEl.textContent = totalSpentRanks;
    if (totalEl) totalEl.textContent = totalAvailableRanks;

    if (poolBadge) {
      if (totalSpentRanks > totalAvailableRanks) {
        poolBadge.className = 'badge bg-danger text-white font-monospace animate__animated animate__shakeX';
      } else if (totalSpentRanks === totalAvailableRanks) {
        poolBadge.className = 'badge bg-success text-white font-monospace';
      } else {
        poolBadge.className = 'badge bg-primary text-white font-monospace';
      }
    }
  }

  // -------------------------------------------------------------
  // AUTOMATION 5: HP Calculation Logic
  // -------------------------------------------------------------
  function recalculateHp(conMod, lvl) {
    const classKey = document.getElementById('sheetClassSelect').value;
    const hdStr = (pfData.classes[classKey] && pfData.classes[classKey].hit_die) || 'd10';
    const sides = parseInt(hdStr.replace('d', ''), 10) || 10;
    const hpExtraInp = document.getElementById('sheetHpExtraMisc');
    const extraMisc = hpExtraInp ? (parseInt(hpExtraInp.value, 10) || 0) : (parseInt(sData.hpExtraMisc, 10) || 0);
    sData.hpExtraMisc = extraMisc;

    if (!sData.hpRolls) sData.hpRolls = {};
    if (sData.hpRolls[1] === undefined) sData.hpRolls[1] = sides; // 1st level is max

    let rollsSum = 0;
    for (let i = 1; i <= lvl; i++) {
      if (sData.hpRolls[i] === undefined) {
        sData.hpRolls[i] = (i === 1) ? sides : (Math.floor(sides / 2) + 1);
      }
      rollsSum += parseInt(sData.hpRolls[i], 10) || 1;
    }

    const conTotal = lvl * conMod;
    const totalMaxHp = Math.max(1, rollsSum + conTotal + extraMisc);

    const oldMaxHp = parseInt(document.getElementById('sheetMaxHp')?.value, 10) || totalMaxHp;
    const curHpEl = document.getElementById('sheetCurrentHp');
    if (curHpEl) {
      const curHp = parseInt(curHpEl.value, 10);
      if (curHp === oldMaxHp || isNaN(curHp) || curHp > totalMaxHp) {
        curHpEl.value = totalMaxHp;
      }
    }

    document.getElementById('sheetMaxHp').value = totalMaxHp;
    document.getElementById('sheetMaxHpDisplay').textContent = totalMaxHp;
    
    let extraMiscStr = '';
    if (extraMisc !== 0) {
      extraMiscStr = extraMisc > 0 ? ` + ${extraMisc} (Misc/Feat)` : ` - ${Math.abs(extraMisc)} (Misc/Feat)`;
    }
    document.getElementById('sheetHpTotalFormulaSummary').textContent =
      `Seviye ${lvl}: ${rollsSum} (Zarlar) + ${conTotal} (Con${conMod >= 0 ? '+' : ''}${conMod}x${lvl})${extraMiscStr} = ${totalMaxHp} Max HP`;

    document.getElementById('modalHpConMod').textContent = formatMod(conMod);
    document.getElementById('modalHpTotalMax').textContent = `${totalMaxHp} HP`;

    renderLevelHpTable(lvl, sides, conMod);
  }

  function renderLevelHpTable(lvl, sides, conMod) {
    const tbody = document.getElementById('tbodyLevelHpBreakdown');
    if (!tbody) return;

    // In-place update if row count matches level so input focus and DOM elements are preserved
    const existingRows = tbody.querySelectorAll('tr');
    if (existingRows.length === lvl) {
      existingRows.forEach((tr, idx) => {
        const i = idx + 1;
        const inp = tr.querySelector('.hp-level-roll');
        if (inp && document.activeElement !== inp) {
          inp.value = sData.hpRolls[i] !== undefined ? sData.hpRolls[i] : (i === 1 ? sides : Math.floor(sides / 2) + 1);
        }
        const roll = parseInt(inp ? inp.value : sData.hpRolls[i], 10) || (i === 1 ? sides : Math.floor(sides / 2) + 1);
        const conTd = tr.children[2];
        const totalTd = tr.children[3];
        if (conTd) conTd.textContent = formatMod(conMod);
        if (totalTd) totalTd.textContent = roll + conMod;
      });
      return;
    }

    tbody.innerHTML = '';
    for (let i = 1; i <= lvl; i++) {
      const roll = parseInt(sData.hpRolls[i], 10) || (i === 1 ? sides : Math.floor(sides / 2) + 1);
      const rowTotal = roll + conMod;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="fw-bold text-primary">Lvl ${i}</td>
        <td>
          <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center hp-level-roll" min="1" max="${sides}" value="${roll}" data-level="${i}" />
        </td>
        <td class="text-body-secondary">${formatMod(conMod)}</td>
        <td class="fw-bold text-heading">${rowTotal}</td>
      `;
      tbody.appendChild(tr);
    }

    tbody.querySelectorAll('.hp-level-roll').forEach(inp => {
      inp.addEventListener('input', function () {
        const lvlIdx = parseInt(this.getAttribute('data-level'), 10);
        const val = parseInt(this.value, 10) || 1;
        if (!sData.hpRolls) sData.hpRolls = {};
        sData.hpRolls[lvlIdx] = val;
        recalculateAll();
        triggerAutoSave();
      });
    });
  }

  // -------------------------------------------------------------
  // AUTOMATION 6: Carrying Capacity (STR Encumbrance)
  // -------------------------------------------------------------
  function updateCarryingCapacity(str) {
    str = Math.min(30, Math.max(1, parseInt(str, 10) || 10));
    const cap = STR_CAPACITY[str] || [33, 66, 100];

    document.getElementById('lblCarryingStrScore').textContent = `STR ${str}`;
    document.getElementById('capLight').textContent = `≤ ${cap[0]} lbs`;
    document.getElementById('capMedium').textContent = `${cap[0] + 1} - ${cap[1]} lbs`;
    document.getElementById('capHeavy').textContent = `${cap[1] + 1} - ${cap[2]} lbs`;

    document.getElementById('capLiftHead').textContent = `${cap[2]} lbs`;
    document.getElementById('capLiftGround').textContent = `${cap[2] * 2} lbs`;
    document.getElementById('capDrag').textContent = `${cap[2] * 5} lbs`;
  }

  // -------------------------------------------------------------
  // AUTOMATION 7: AC Items & Gear Totals
  // -------------------------------------------------------------
  function updateAcItemsTotals() {
    let totBonus = 0;
    let totPen = 0;
    let totWeight = 0;
    let armorBonus = 0;
    let shieldBonus = 0;
    let deflectBonus = 0;
    let naturalBonus = 0;
    let miscBonus = 0;
    let activeArmorName = '';
    let activeShieldName = '';
    const rows = document.querySelectorAll('#acItemsTbody tr');

    rows.forEach(tr => {
      const name = (tr.querySelector('.ac-item-name')?.value || '').trim();
      const type = tr.querySelector('.ac-item-type')?.value || 'armor';
      const bonus = parseInt(tr.querySelector('.ac-item-bonus')?.value, 10) || 0;
      const pen = parseInt(tr.querySelector('.ac-item-pen')?.value, 10) || 0;
      const wt = parseInt(tr.querySelector('.ac-item-weight')?.value, 10) || 0;
      const isActive = tr.querySelector('.ac-item-active')?.checked ?? true;

      // Update row visual styling
      if (isActive) {
        tr.classList.add('row-ac-active');
        tr.classList.remove('row-ac-inactive');
      } else {
        tr.classList.add('row-ac-inactive');
        tr.classList.remove('row-ac-active');
      }

      // Weight is always counted in carrying capacity
      totWeight += wt;

      // Only ACTIVE items contribute to AC bonuses, ACP and active status
      if (isActive) {
        if (type === 'shield') {
          shieldBonus += bonus;
          activeShieldName = name ? `${name} (+${bonus})` : `Kalkan (+${bonus})`;
        } else if (type === 'deflection') {
          deflectBonus += bonus;
        } else if (type === 'natural') {
          naturalBonus += bonus;
        } else if (type === 'dodge' || type === 'misc') {
          miscBonus += bonus;
        } else {
          // armor
          armorBonus += bonus;
          activeArmorName = name ? `${name} (+${bonus})` : `Armor (+${bonus})`;
        }

        totBonus += bonus;
        totPen += pen;
      }
    });

    const bEl = document.getElementById('acItemTotalBonus');
    const pEl = document.getElementById('acItemTotalPen');
    const wEl = document.getElementById('acItemTotalWeight');

    if (bEl) bEl.textContent = formatMod(totBonus);
    if (pEl) pEl.textContent = formatMod(totPen);
    if (wEl) wEl.textContent = `${totWeight} lbs`;

    // Update Quick status labels in AC header
    const lblArmor = document.getElementById('lblActiveArmorName');
    const lblShield = document.getElementById('lblActiveShieldName');
    if (lblArmor) lblArmor.textContent = activeArmorName || 'None (Unarmored)';
    if (lblShield) lblShield.textContent = activeShieldName || 'None';

    // Feed separated Armor, Shield, Deflect, Natural, Misc into Sheet 1 AC
    const sheetArmorInput = document.getElementById('sheetAcArmor');
    const sheetShieldInput = document.getElementById('sheetAcShield');
    const sheetDeflectInput = document.getElementById('sheetAcDeflect');
    const sheetNaturalInput = document.getElementById('sheetAcNatural');
    const sheetMiscInput = document.getElementById('sheetAcMisc');

    if (sheetArmorInput) sheetArmorInput.value = armorBonus;
    if (sheetShieldInput) sheetShieldInput.value = shieldBonus;
    if (sheetDeflectInput) sheetDeflectInput.value = deflectBonus;
    if (sheetNaturalInput) sheetNaturalInput.value = naturalBonus;
    if (sheetMiscInput) sheetMiscInput.value = miscBonus;

    // Update gear total weight
    let gearTotWt = 0;
    document.querySelectorAll('.gear-wt').forEach(inp => {
      gearTotWt += parseInt(inp.value, 10) || 0;
    });
    let saveTotWt = 0;
    document.querySelectorAll('.save-item-weight').forEach(inp => {
      saveTotWt += parseInt(inp.value, 10) || 0;
    });
    const totalAllWeight = totWeight + gearTotWt + saveTotWt;
    const gearWtBadge = document.getElementById('lblTotalGearWeight');
    if (gearWtBadge) gearWtBadge.textContent = `${totalAllWeight} lbs`;
  }

  // -------------------------------------------------------------
  // AUTOMATION 7.5: Saving Throw Items Totals (Resistance & Protection)
  // -------------------------------------------------------------
  function updateSaveItemsTotals() {
    let fortBonus = 0;
    let refBonus = 0;
    let willBonus = 0;
    let totWeight = 0;
    let activeItemNames = [];
    const rows = document.querySelectorAll('#saveItemsTbody tr');

    rows.forEach(tr => {
      const name = (tr.querySelector('.save-item-name')?.value || '').trim();
      const target = tr.querySelector('.save-item-target')?.value || 'all';
      const type = tr.querySelector('.save-item-type')?.value || 'resistance';
      const bonus = parseInt(tr.querySelector('.save-item-bonus')?.value, 10) || 0;
      const wt = parseInt(tr.querySelector('.save-item-weight')?.value, 10) || 0;
      const isActive = tr.querySelector('.save-item-active')?.checked ?? true;

      // Visual styling
      if (isActive) {
        tr.classList.add('row-save-active');
        tr.classList.remove('row-save-inactive');
      } else {
        tr.classList.add('row-save-inactive');
        tr.classList.remove('row-save-active');
      }

      totWeight += wt;

      if (isActive && bonus !== 0) {
        if (target === 'all') {
          fortBonus += bonus;
          refBonus += bonus;
          willBonus += bonus;
        } else if (target === 'fort') {
          fortBonus += bonus;
        } else if (target === 'ref') {
          refBonus += bonus;
        } else if (target === 'will') {
          willBonus += bonus;
        }

        if (name) {
          activeItemNames.push(`${name} (+${bonus})`);
        }
      }
    });

    // Update Magic bonus inputs in the Saves table if save items table has rows
    const fInp = document.getElementById('saveMagic_fort');
    const rInp = document.getElementById('saveMagic_ref');
    const wInp = document.getElementById('saveMagic_will');

    if (rows.length > 0) {
      if (fInp) fInp.value = fortBonus;
      if (rInp) rInp.value = refBonus;
      if (wInp) wInp.value = willBonus;
    }

    // Recalculate Fort, Ref, Will totals
    recalculateSaveTotal('fort');
    recalculateSaveTotal('ref');
    recalculateSaveTotal('will');

    // Update Footer Totals in Save Items table
    const bEl = document.getElementById('saveItemTotalBonus');
    const wEl = document.getElementById('saveItemTotalWeight');
    if (bEl) {
      if (fortBonus === refBonus && refBonus === willBonus) {
        bEl.textContent = formatMod(fortBonus);
      } else {
        bEl.textContent = `F:${formatMod(fortBonus)} R:${formatMod(refBonus)} W:${formatMod(willBonus)}`;
      }
    }
    if (wEl) wEl.textContent = `${totWeight} lbs`;

    // Update Badges at top of Save items block
    const lblResName = document.getElementById('lblActiveResistanceName');
    const lblSaveBonuses = document.getElementById('lblActiveSaveBonusesText');
    if (lblResName) {
      lblResName.textContent = activeItemNames.length > 0 ? activeItemNames.join(', ') : 'Yok';
    }
    if (lblSaveBonuses) {
      lblSaveBonuses.textContent = `Fort ${formatMod(fortBonus)} | Ref ${formatMod(refBonus)} | Will ${formatMod(willBonus)}`;
    }

    // Refresh carrying capacity / total gear weight badge
    let armorTotWt = 0;
    document.querySelectorAll('.ac-item-weight').forEach(inp => {
      armorTotWt += parseInt(inp.value, 10) || 0;
    });
    let gearTotWt = 0;
    document.querySelectorAll('.gear-wt').forEach(inp => {
      gearTotWt += parseInt(inp.value, 10) || 0;
    });
    const totalAllWeight = armorTotWt + gearTotWt + totWeight;
    const gearWtBadge = document.getElementById('lblTotalGearWeight');
    if (gearWtBadge) gearWtBadge.textContent = `${totalAllWeight} lbs`;

    return { fortBonus, refBonus, willBonus, totWeight };
  }

  // -------------------------------------------------------------
  // AUTOMATION 8: Spells DC & Spell Slots Progression Automation
  // -------------------------------------------------------------
  const CLASS_CASTING_STATS = {
    wizard: 'int', alchemist: 'int', magus: 'int', witch: 'int', arcanist: 'int', investigator: 'int',
    cleric: 'wis', druid: 'wis', ranger: 'wis', inquisitor: 'wis', shaman: 'wis', hunter: 'wis',
    paladin: 'cha', sorcerer: 'cha', bard: 'cha', oracle: 'cha', summoner: 'cha', bloodrager: 'cha', skald: 'cha',
    fighter: 'none', barbarian: 'none', rogue: 'none', monk: 'none', cavalier: 'none', gunslinger: 'none',
    brawler: 'none', slayer: 'none', swashbuckler: 'none'
  };

  function getCastingStatForClass(classKey) {
    if (!classKey) return 'none';
    classKey = classKey.toLowerCase();
    const prog = pfData.progressions[classKey];
    if (prog && prog.casting_stat) return prog.casting_stat;
    if (CLASS_CASTING_STATS[classKey]) return CLASS_CASTING_STATS[classKey];
    if (prog && prog.has_spells === false) return 'none';
    return 'none';
  }

  function getOrdinal(lvl) {
    if (lvl === 1) return '1st';
    if (lvl === 2) return '2nd';
    if (lvl === 3) return '3rd';
    return `${lvl}th`;
  }

  function updateClassSpellSlots(classKey, charLevel, mods, autoSetStat = false) {
    if (!classKey) classKey = document.getElementById('sheetClassSelect')?.value || 'paladin';
    classKey = classKey.toLowerCase();
    if (!charLevel) charLevel = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput')?.value, 10) || 1));

    const prog = pfData.progressions[classKey] || {};
    const defaultStat = getCastingStatForClass(classKey);
    const statSelect = document.getElementById('sheetCastingStat');

    if (autoSetStat && statSelect) {
      statSelect.value = defaultStat;
    }

    const currentStat = statSelect?.value || defaultStat;
    const hasCasting = (currentStat && currentStat !== 'none');

    // Ability modifier for casting stat
    let castMod = 0;
    if (hasCasting) {
      if (mods && mods[currentStat] !== undefined) {
        castMod = mods[currentStat];
      } else {
        const scoreEl = document.getElementById(`score_${currentStat}`);
        const scoreVal = parseInt(scoreEl ? scoreEl.value : 10, 10) || 10;
        castMod = calcMod(scoreVal);
      }
    }

    // 1. Base Spell DC Label
    const baseDcEl = document.getElementById('lblBaseSpellDc');
    if (baseDcEl) {
      if (hasCasting) {
        baseDcEl.textContent = `10 + ${currentStat.toUpperCase()} (${formatMod(castMod)}) = ${10 + castMod}`;
      } else {
        baseDcEl.textContent = 'None';
      }
    }

    // 2. Sub Header
    const subBanner = document.getElementById('lblSpellCastingSub');
    if (subBanner) {
      if (hasCasting) {
        const spellType = (prog.spellcasting || currentStat.toUpperCase()).split(',')[0].trim();
        subBanner.textContent = `${spellType} (Lvl ${charLevel})`;
      } else {
        subBanner.textContent = 'No Spells';
      }
    }

    // 3. Update Spell Slots Table (0 to 9)
    const spellLevels = prog.spell_levels || [];
    const charSpells = prog.spells?.[charLevel] || [];

    for (let lvl = 0; lvl <= 9; lvl++) {
      const tr = document.getElementById(`spellRow_${lvl}`);
      const dcEl = document.getElementById(`spellDc_${lvl}`);
      const bonusEl = document.getElementById(`spellBonus_${lvl}`);
      const perDayInp = tr ? tr.querySelector('.spell-per-day') : null;
      const knownInp = tr ? tr.querySelector('.spell-known') : null;

      if (!hasCasting) {
        if (tr) tr.classList.add('opacity-50');
        if (dcEl) dcEl.textContent = '—';
        if (bonusEl) bonusEl.textContent = '—';
        if (perDayInp) { perDayInp.value = '—'; perDayInp.disabled = true; }
        if (knownInp) { knownInp.disabled = true; }
        continue;
      }

      let isLevelAccessible = false;
      let slotsVal = '—';

      if (lvl === 0) {
        if (spellLevels.includes('0')) {
          const idx = spellLevels.indexOf('0');
          const raw = charSpells[idx];
          if (raw !== undefined && raw !== null) {
            slotsVal = raw;
            isLevelAccessible = true;
          }
        }
      } else {
        const ord = getOrdinal(lvl);
        if (spellLevels.includes(ord)) {
          const idx = spellLevels.indexOf(ord);
          const raw = charSpells[idx];
          if (raw !== undefined && raw !== '—') {
            slotsVal = raw;
            isLevelAccessible = true;
          }
        }
      }

      if (isLevelAccessible) {
        if (tr) tr.classList.remove('opacity-50');
        if (dcEl) dcEl.textContent = 10 + lvl + castMod;

        if (bonusEl) {
          if (lvl === 0 || castMod <= 0) {
            bonusEl.textContent = '0';
          } else if (castMod >= lvl) {
            const b = Math.floor((castMod - lvl) / 4) + 1;
            bonusEl.textContent = `+${b}`;
          } else {
            bonusEl.textContent = '0';
          }
        }

        if (perDayInp) {
          perDayInp.value = slotsVal;
          perDayInp.disabled = false;
        }
        if (knownInp) {
          knownInp.disabled = false;
        }
      } else {
        if (tr) tr.classList.add('opacity-50');
        if (dcEl) dcEl.textContent = '—';
        if (bonusEl) bonusEl.textContent = '—';
        if (perDayInp) {
          perDayInp.value = '—';
          perDayInp.disabled = true;
        }
        if (knownInp) {
          knownInp.disabled = true;
        }
      }
    }
  }

  // -------------------------------------------------------------
  // Dynamic Row Addition / Deletion (AC Items, Gear, Feats, Weapons)
  // -------------------------------------------------------------
  const btnAddAcItem = document.getElementById('btnAddAcItemRow');
  if (btnAddAcItem) {
    btnAddAcItem.addEventListener('click', () => {
      const tbody = document.getElementById('acItemsTbody');
      // If there is already an active armor, default new item to inactive
      const hasActiveArmor = !!Array.from(document.querySelectorAll('#acItemsTbody tr')).find(r => {
        const type = r.querySelector('.ac-item-type')?.value || 'armor';
        const active = r.querySelector('.ac-item-active')?.checked;
        return type === 'armor' && active;
      });
      const tr = createAcItemRow({ name: '', type: 'armor', bonus: 0, pen: 0, weight: 0, is_active: !hasActiveArmor });
      tbody.appendChild(tr);
      attachDynamicRowEvents();
      const inp = tr.querySelector('.ac-item-name');
      if (inp) inp.focus();
      recalculateAll();
      triggerAutoSave();
    });
  }

  const btnAddSaveItem = document.getElementById('btnAddSaveItemRow');
  if (btnAddSaveItem) {
    btnAddSaveItem.addEventListener('click', () => {
      const tbody = document.getElementById('saveItemsTbody');
      const tr = createSaveItemRow({ name: '', target: 'all', type: 'resistance', bonus: 1, weight: 1, is_active: true });
      tbody.appendChild(tr);
      attachDynamicRowEvents();
      const inp = tr.querySelector('.save-item-name');
      if (inp) inp.focus();
      recalculateAll();
      triggerAutoSave();
    });
  }

  const btnAddGear = document.getElementById('btnAddGearRow');
  if (btnAddGear) {
    btnAddGear.addEventListener('click', () => {
      const container = document.getElementById('gearItemsContainer');
      const div = document.createElement('div');
      div.className = 'd-flex align-items-center gap-1 mb-1';
      div.innerHTML = `
        <input type="text" class="form-control form-control-sm pf-box-input pf-input-compact text-start gear-name" placeholder="New Item Name" />
        <input type="number" class="form-control form-control-sm pf-box-input pf-input-compact text-center gear-wt font-monospace" style="width: 60px;" placeholder="Wt" value="1" />
        <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-gear"><i class="icon-base ti tabler-x"></i></button>
      `;
      container.appendChild(div);
      attachDynamicRowEvents();
      recalculateAll();
      triggerAutoSave();
    });
  }

  const btnAddFeat = document.getElementById('btnAddFeatRow');
  if (btnAddFeat) {
    btnAddFeat.addEventListener('click', () => {
      const title = prompt('Feat Name:');
      if (!title) return;
      const desc = prompt('Description / Bonus:');
      const container = document.getElementById('featsContainer');
      const div = document.createElement('div');
      div.className = 'p-2 mb-1 rounded bg-body border d-flex justify-content-between align-items-center';
      div.innerHTML = `
        <div>
          <strong class="text-heading d-block small feat-title">${title}</strong>
          <small class="text-body-secondary feat-desc">${desc || '—'}</small>
        </div>
        <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-feat"><i class="icon-base ti tabler-x"></i></button>
      `;
      container.appendChild(div);
      attachDynamicRowEvents();
      triggerAutoSave();
    });
  }

  const btnAddSpecial = document.getElementById('btnAddSpecialRow');
  if (btnAddSpecial) {
    btnAddSpecial.addEventListener('click', () => {
      const title = prompt('Special Ability / Class Feature:');
      if (!title) return;
      const desc = prompt('Description:');
      const container = document.getElementById('specialsContainer');
      const div = document.createElement('div');
      div.className = 'p-2 mb-1 rounded bg-body border d-flex justify-content-between align-items-center special-item';
      div.innerHTML = `
        <div>
          <span class="badge bg-label-warning font-monospace py-0 me-1">Special</span>
          <strong class="text-heading small">${escapeHtml(title)}</strong>
          <small class="text-body-secondary d-block">${escapeHtml(desc || '—')}</small>
        </div>
        <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-del-special"><i class="icon-base ti tabler-x"></i></button>
      `;
      container.appendChild(div);
      attachDynamicRowEvents();
      triggerAutoSave();
    });
  }

  const btnAddWeapon = document.getElementById('btnAddWeaponRow');
  if (btnAddWeapon) {
    btnAddWeapon.addEventListener('click', () => {
      const container = document.getElementById('weaponsContainer');
      if (!container) return;
      const idx = container.querySelectorAll('.weapon-row').length + 1;
      container.insertAdjacentHTML('beforeend', createWeaponRowHtml(idx));
      attachWeaponEvents();
      recalculateAll();
      triggerAutoSave();
    });
  }

  function attachDynamicRowEvents() {
    document.querySelectorAll('.btn-del-row').forEach(btn => {
      btn.onclick = function () {
        this.closest('tr').remove();
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.btn-del-gear').forEach(btn => {
      btn.onclick = function () {
        this.closest('div').remove();
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.btn-del-feat, .btn-del-special').forEach(btn => {
      btn.onclick = function () {
        this.closest('div.rounded, div.feat-item, div.special-item')?.remove();
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.ac-item-bonus, .ac-item-pen, .ac-item-weight, .gear-wt').forEach(inp => {
      inp.oninput = function () {
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.ac-item-active').forEach(chk => {
      chk.onchange = function () {
        const tr = this.closest('tr');
        const isChecked = this.checked;
        const currentType = tr?.querySelector('.ac-item-type')?.value || 'armor';
        if (isChecked && (currentType === 'armor' || currentType === 'shield')) {
          document.querySelectorAll('#acItemsTbody tr').forEach(otherTr => {
            if (otherTr !== tr) {
              const otherType = otherTr.querySelector('.ac-item-type')?.value || 'armor';
              if (otherType === currentType) {
                const otherChk = otherTr.querySelector('.ac-item-active');
                if (otherChk) otherChk.checked = false;
              }
            }
          });
        }
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.ac-item-type').forEach(sel => {
      sel.onchange = function () {
        const tr = this.closest('tr');
        const activeChk = tr?.querySelector('.ac-item-active');
        const targetType = this.value;
        if (activeChk && activeChk.checked && (targetType === 'armor' || targetType === 'shield')) {
          document.querySelectorAll('#acItemsTbody tr').forEach(otherTr => {
            if (otherTr !== tr) {
              const otherType = otherTr.querySelector('.ac-item-type')?.value || 'armor';
              if (otherType === targetType) {
                const otherChk = otherTr.querySelector('.ac-item-active');
                if (otherChk) otherChk.checked = false;
              }
            }
          });
        }
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.ac-item-name').forEach(inp => {
      inp.onchange = function () {
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.btn-del-save-row').forEach(btn => {
      btn.onclick = function () {
        this.closest('tr').remove();
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.save-item-bonus, .save-item-weight').forEach(inp => {
      inp.oninput = function () {
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.save-item-active').forEach(chk => {
      chk.onchange = function () {
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.save-item-target, .save-item-type').forEach(sel => {
      sel.onchange = function () {
        recalculateAll();
        triggerAutoSave();
      };
    });
    document.querySelectorAll('.save-item-name').forEach(inp => {
      inp.onchange = function () {
        recalculateAll();
        triggerAutoSave();
      };
    });
  }

  // -------------------------------------------------------------
  // Interactive Dice Roller Logic
  // -------------------------------------------------------------
  const diceDrawer = document.getElementById('diceDrawer');
  const btnToggleDrawer = document.getElementById('btnToggleDiceDrawer');
  const btnCloseDrawer = document.getElementById('btnCloseDiceDrawer');
  const rollValEl = document.getElementById('diceRollValue');
  const rollLabelEl = document.getElementById('diceRollLabel');
  const rollBreakdownEl = document.getElementById('diceRollBreakdown');
  const rollHistoryEl = document.getElementById('diceRollHistory');

  if (btnToggleDrawer) {
    btnToggleDrawer.addEventListener('click', () => {
      if (diceDrawer) {
        diceDrawer.style.display = (diceDrawer.style.display === 'none' || !diceDrawer.style.display) ? 'block' : 'none';
      }
    });
  }
  if (btnCloseDrawer) {
    btnCloseDrawer.addEventListener('click', () => {
      if (diceDrawer) diceDrawer.style.display = 'none';
    });
  }

  function rollDice(sides) {
    if (diceDrawer) diceDrawer.style.display = 'block';
    const roll = Math.floor(Math.random() * sides) + 1;
    if (rollLabelEl) rollLabelEl.textContent = `d${sides} ${i18n.roll}`;
    if (rollValEl) {
      rollValEl.textContent = roll;
      rollValEl.className = 'mb-0 fw-bold font-monospace text-primary animate__animated animate__rubberBand';
    }
    if (rollBreakdownEl) rollBreakdownEl.textContent = `1d${sides} result: ${roll}`;
    logRollResult(`d${sides} ${i18n.roll}`, roll, `[1d${sides}]`);
    return roll;
  }

  function rollD20WithMod(mod, label) {
    if (diceDrawer) diceDrawer.style.display = 'block';
    const d20 = Math.floor(Math.random() * 20) + 1;
    const total = d20 + mod;
    if (rollLabelEl) rollLabelEl.textContent = label || `d20 ${i18n.check}`;
    if (rollValEl) {
      rollValEl.textContent = total;
      if (d20 === 20) {
        rollValEl.className = 'mb-0 fw-bold font-monospace text-success animate__animated animate__tada';
      } else if (d20 === 1) {
        rollValEl.className = 'mb-0 fw-bold font-monospace text-danger animate__animated animate__shakeX';
      } else {
        rollValEl.className = 'mb-0 fw-bold font-monospace text-primary animate__animated animate__bounceIn';
      }
    }
    const breakdown = `[d20 (${d20}) + Bonus (${formatMod(mod)})] = ${total}`;
    if (rollBreakdownEl) {
      rollBreakdownEl.textContent = d20 === 20 ? `${breakdown} (NATURAL 20 - CRITICAL!)` : (d20 === 1 ? `${breakdown} (NATURAL 1 - FUMBLE!)` : breakdown);
    }
    logRollResult(label || 'd20', total, `[1d20 (${d20}) ${formatMod(mod)}]`);
    return total;
  }

  function logRollResult(title, total, breakdown) {
    if (!rollHistoryEl) return;
    const time = new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const logItem = `
      <div class="d-flex align-items-center justify-content-between border-bottom border-secondary border-opacity-10 py-1">
        <span class="text-truncate me-2" style="max-width: 140px;">${title}</span>
        <span class="text-body-secondary small me-2">${breakdown}</span>
        <span class="badge bg-label-warning font-monospace fw-bold">${total}</span>
      </div>
    `;
    rollHistoryEl.insertAdjacentHTML('afterbegin', logItem);
  }

  // Weapon row events (delete & change inputs)
  function attachWeaponEvents() {
    // Weapon delete buttons
    document.querySelectorAll('.btn-del-weapon').forEach(btn => {
      btn.onclick = function () {
        this.closest('.weapon-row').remove();
        recalculateAll();
        triggerAutoSave();
      };
    });

    // Auto-recalculate on weapon input changes
    document.querySelectorAll('.wp-name, .wp-atk-type, .wp-base-die, .wp-enh, .wp-misc-atk, .wp-crit-range, .wp-crit-mult, .wp-ammo').forEach(inp => {
      const evtType = (inp.tagName === 'SELECT' || inp.type === 'checkbox') ? 'change' : 'input';
      inp.addEventListener(evtType, () => {
        recalculateAll();
        triggerAutoSave();
      });
    });
  }

  // Helper to read character's active feats
  function getCharacterFeats() {
    const feats = [];
    document.querySelectorAll('#featsContainer .feat-title').forEach(el => {
      const t = el.textContent.trim();
      if (t) feats.push(t);
    });
    return feats;
  }

  // Automated Feat Check for Improved Critical
  function hasImprovedCriticalForWeapon(weaponName) {
    const wName = (weaponName || '').toLowerCase().trim();
    const feats = getCharacterFeats();
    for (const feat of feats) {
      const fLower = feat.toLowerCase().trim();
      if (fLower.includes('improved critical') || fLower.includes('imp. critical') || fLower.includes('imp critical') || fLower.includes('gelişmiş kritik')) {
        // If weapon is specified inside parenthesis, e.g. "Improved Critical (Longsword)"
        const match = fLower.match(/\(([^)]+)\)/);
        if (match) {
          const targetWeapon = match[1].trim();
          if (wName.includes(targetWeapon) || targetWeapon.includes(wName)) {
            return true;
          }
        } else {
          // If general, check if any word of the weapon is mentioned or if it's general
          const words = wName.split(/[\s+]+/).filter(w => w.length > 2);
          if (words.some(w => fLower.includes(w)) || fLower === 'improved critical' || fLower === 'imp. critical' || fLower === 'gelişmiş kritik') {
            return true;
          }
        }
      }
    }
    return false;
  }

  // Weapon auto-calculation function
  function recalculateWeapons(mods, mainBab) {
    document.querySelectorAll('.weapon-row').forEach(row => {
      const wpName = row.querySelector('.wp-name')?.value || '';
      const atkType = row.querySelector('.wp-atk-type')?.value || 'melee';
      const baseDie = row.querySelector('.wp-base-die')?.value || '1d8';
      const enh = parseInt(row.querySelector('.wp-enh')?.value, 10) || 0;
      const miscAtk = parseInt(row.querySelector('.wp-misc-atk')?.value, 10) || 0;
      const critRangeStr = row.querySelector('.wp-crit-range')?.value || '20';
      const critMult = parseInt(row.querySelector('.wp-crit-mult')?.value, 10) || 2;

      // AUTOMATED FEAT CHECK: Improved Critical
      const hasImprovedCrit = hasImprovedCriticalForWeapon(wpName);
      const featIndicator = row.querySelector('.wp-feat-crit-indicator');

      const isBomb = wpName.toLowerCase().includes('bomb') || wpName.toLowerCase().includes('bomba');

      // Attack Bonus = BAB + STR(melee)/DEX(ranged/bomb) + Enhancement + Misc
      // Bomb is a thrown splash weapon made as a ranged touch attack (vs touch AC)
      const abilityMod = (atkType === 'ranged' || isBomb) ? (mods.dex || 0) : (mods.str || 0);
      const atkTotal = mainBab + abilityMod + enh + miscAtk;
      const atkTotalEl = row.querySelector('.wp-atk-total');
      if (atkTotalEl) atkTotalEl.textContent = formatMod(atkTotal);

      // Attack formula display
      const atkFormulaEl = row.querySelector('.wp-atk-formula');
      const abLabel = (atkType === 'ranged' || isBomb) ? 'DEX' : 'STR';
      if (atkFormulaEl) {
        if (isBomb) {
          atkFormulaEl.textContent = `${formatMod(mainBab)}(BAB) ${formatMod(abilityMod)}(DEX Touch) ${enh ? formatMod(enh)+'(Enh)' : ''} ${miscAtk ? formatMod(miscAtk)+'(Misc)' : ''}`.replace(/\s+/g, ' ').trim();
        } else {
          atkFormulaEl.textContent = `${formatMod(mainBab)}(BAB) ${formatMod(abilityMod)}(${abLabel}) ${enh ? formatMod(enh)+'(Enh)' : ''} ${miscAtk ? formatMod(miscAtk)+'(Misc)' : ''}`.replace(/\s+/g, ' ').trim();
        }
      }

      // Damage = base die + INT mod (Bomb) or STR mod (melee) or Enh (ranged)
      // Official Pathfinder 1E: On a direct hit, an alchemist's bomb inflicts base die fire damage + INT modifier
      const dmgMod = isBomb ? ((mods.int || 0) + enh) : (atkType === 'melee' ? ((mods.str || 0) + enh) : enh);
      const dmgTotalEl = row.querySelector('.wp-dmg-total');
      if (dmgTotalEl) {
        dmgTotalEl.textContent = dmgMod !== 0 ? `${baseDie}${formatMod(dmgMod)}` : baseDie;
      }

      // Damage formula display
      const dmgFormulaEl = row.querySelector('.wp-dmg-formula');
      if (dmgFormulaEl) {
        if (isBomb) {
          dmgFormulaEl.textContent = `${baseDie} ${formatMod(mods.int || 0)}(INT) ${enh ? formatMod(enh)+'(Enh)' : ''} [Fire/Splash]`.replace(/\s+/g, ' ').trim();
        } else if (atkType === 'melee') {
          dmgFormulaEl.textContent = `${baseDie} ${formatMod(mods.str || 0)}(STR) ${enh ? formatMod(enh)+'(Enh)' : ''}`.replace(/\s+/g, ' ').trim();
        } else {
          dmgFormulaEl.textContent = `${baseDie} ${enh ? formatMod(enh)+'(Enh)' : 'no STR'}`.replace(/\s+/g, ' ').trim();
        }
      }

      // Crit Range calculation with Improved Critical
      let critLow = 20, critHigh = 20;
      const critMatch = critRangeStr.match(/(\d+)(?:-(\d+))?/);
      if (critMatch) {
        critLow = parseInt(critMatch[1], 10) || 20;
        critHigh = critMatch[2] ? parseInt(critMatch[2], 10) : critLow;
        if (critHigh < critLow) critHigh = 20;
      }
      // Base threat range = 21 - critLow (e.g. 19-20 = 2, 20 = 1, 18-20 = 3)
      let threatRange = 21 - critLow;
      if (hasImprovedCrit) threatRange *= 2; // Improved Critical doubles the range
      const finalCritLow = 21 - threatRange;
      const critRangeDisplay = finalCritLow < 20 ? `${finalCritLow}-20/x${critMult}` : `20/x${critMult}`;

      if (featIndicator) {
        if (hasImprovedCrit) {
          featIndicator.classList.remove('d-none');
          featIndicator.title = `Feat: Improved Critical aktif (${critRangeDisplay})!`;
        } else {
          featIndicator.classList.add('d-none');
        }
      }

      // Crit Damage = dice * multiplier + (mod * multiplier)
      // Official Pathfinder 1E rule for Bomb: extra odd-level damage dice are NOT multiplied on a critical hit.
      // Only the base 1d6 and INT modifier are multiplied.
      const dieMatch = baseDie.match(/(\d+)d(\d+)/i);
      const diceCount = dieMatch ? parseInt(dieMatch[1], 10) : 1;
      const diceSides = dieMatch ? parseInt(dieMatch[2], 10) : (isBomb ? 6 : 8);

      let critDiceCount = diceCount * critMult;
      let critDmgMod = dmgMod * critMult;

      if (isBomb) {
        // Pathfinder 1E: Only 1d6 is multiplied (1d6 * critMult) plus unmultiplied extra dice (diceCount - 1)d6
        critDiceCount = (1 * critMult) + (diceCount - 1);
        critDmgMod = ((mods.int || 0) * critMult) + (enh * critMult);
      }

      const critDmgTotalEl = row.querySelector('.wp-crit-dmg-total');
      if (critDmgTotalEl) {
        critDmgTotalEl.textContent = critDmgMod !== 0 ? `${critDiceCount}d${diceSides}${formatMod(critDmgMod)}` : `${critDiceCount}d${diceSides}`;
      }

      // Crit damage formula display
      const critDmgFormulaEl = row.querySelector('.wp-crit-dmg-formula');
      if (critDmgFormulaEl) {
        if (isBomb) {
          critDmgFormulaEl.textContent = `1d6x${critMult} + ${diceCount - 1}d6 + INTx${critMult}`;
        } else {
          critDmgFormulaEl.textContent = `x${critMult} | ${critRangeDisplay}`;
        }
      }

      // Sync Bomb ammunition with current level + INT modifier if set to standard per-day
      if (isBomb) {
        const ammoEl = row.querySelector('.wp-ammo');
        if (ammoEl && (!ammoEl.value || ammoEl.value.includes('/day'))) {
          const lvl = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput')?.value, 10) || 1));
          ammoEl.value = `${Math.max(1, lvl + (mods.int || 0))}/day`;
        }
      }
    });
  }

  // -------------------------------------------------------------
  // Event Bindings
  // -------------------------------------------------------------
  document.getElementById('sheetClassSelect').addEventListener('change', onClassOrLevelChange);
  document.getElementById('sheetLevelInput').addEventListener('input', onClassOrLevelChange);
  document.getElementById('sheetRaceInput').addEventListener('change', onClassOrLevelChange);

  document.getElementById('btnLvlUp').addEventListener('click', () => {
    const el = document.getElementById('sheetLevelInput');
    const cur = parseInt(el.value, 10) || 1;
    if (cur < 20) {
      el.value = cur + 1;
      onClassOrLevelChange();
    }
  });
  document.getElementById('btnLvlDown').addEventListener('click', () => {
    const el = document.getElementById('sheetLevelInput');
    const cur = parseInt(el.value, 10) || 1;
    if (cur > 1) {
      el.value = cur - 1;
      onClassOrLevelChange();
    }
  });

  // Ability score inputs
  document.querySelectorAll('.ability-score-input, .ability-temp-adj').forEach(inp => {
    inp.addEventListener('input', () => {
      const conMod = calcMod(document.getElementById('score_con')?.value);
      const dexMod = calcMod(document.getElementById('score_dex')?.value);
      const wisMod = calcMod(document.getElementById('score_wis')?.value);
      const fortAb = document.getElementById('saveAbility_fort');
      const refAb = document.getElementById('saveAbility_ref');
      const willAb = document.getElementById('saveAbility_will');
      if (fortAb && (inp.id.includes('con') || !inp.id)) fortAb.value = conMod;
      if (refAb && (inp.id.includes('dex') || !inp.id)) refAb.value = dexMod;
      if (willAb && (inp.id.includes('wis') || !inp.id)) willAb.value = wisMod;

      recalculateAll();
      triggerAutoSave();
    });
  });

  // Saving Throw Inputs (Base, Ability, Magic, Misc) real-time calculation
  ['fort', 'ref', 'will'].forEach(k => {
    ['Base', 'Ability', 'Magic', 'Misc'].forEach(field => {
      const el = document.getElementById(`save${field}_${k}`);
      if (el) {
        el.addEventListener('input', () => {
          recalculateSaveTotal(k);
          triggerAutoSave();
        });
        el.addEventListener('change', () => {
          recalculateSaveTotal(k);
          triggerAutoSave();
        });
      }
    });
  });

  function recalculateSaveTotal(k) {
    const base = parseInt(document.getElementById(`saveBase_${k}`)?.value, 10) || 0;
    const ability = parseInt(document.getElementById(`saveAbility_${k}`)?.value, 10) || 0;
    const magic = parseInt(document.getElementById(`saveMagic_${k}`)?.value, 10) || 0;
    const misc = parseInt(document.getElementById(`saveMisc_${k}`)?.value, 10) || 0;
    const total = base + ability + magic + misc;
    const totalEl = document.getElementById(`saveTotal_${k}`);
    if (totalEl) totalEl.textContent = formatMod(total);
  }

  // Casting stat change
  const castingStatSelect = document.getElementById('sheetCastingStat');
  if (castingStatSelect) {
    castingStatSelect.addEventListener('change', () => {
      recalculateAll();
      triggerAutoSave();
    });
  }

  // Roll ability check on mod box click
  document.querySelectorAll('.ability-mod-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const abKey = this.getAttribute('data-ab');
      const valSpan = document.getElementById(`mod_val_${abKey}`) || this.querySelector('span') || this;
      const mod = parseInt(valSpan.textContent, 10) || 0;
      rollD20WithMod(mod, `${abKey.toUpperCase()} ${i18n.check}`);
    });
  });

  // Saves roll buttons
  document.querySelectorAll('.btn-roll-save').forEach(btn => {
    btn.addEventListener('click', function () {
      const saveType = this.getAttribute('data-save');
      const val = parseInt(this.textContent, 10) || 0;
      rollD20WithMod(val, `${saveType.toUpperCase()} ${i18n.savingThrow}`);
    });
  });

  // CMB roll button
  const btnCmb = document.getElementById('btnRollCmb');
  if (btnCmb) {
    btnCmb.addEventListener('click', () => {
      const cmb = parseInt(document.getElementById('sheetCmbTotal').textContent, 10) || 0;
      rollD20WithMod(cmb, `CMB ${i18n.check}`);
    });
  }

  // Initiative roll button
  const btnInit = document.getElementById('btnRollInit');
  if (btnInit) {
    btnInit.addEventListener('click', () => {
      const init = parseInt(document.getElementById('sheetInitTotal').textContent, 10) || 0;
      rollD20WithMod(init, `Initiative ${i18n.check}`);
    });
  }

  // Modal HP buttons
  const btnRollHp = document.getElementById('btnModalRollHp');
  if (btnRollHp) {
    btnRollHp.addEventListener('click', () => {
      const lvl = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput').value, 10) || 1));
      const classKey = document.getElementById('sheetClassSelect').value;
      const hdStr = (pfData.classes[classKey] && pfData.classes[classKey].hit_die) || 'd10';
      const sides = parseInt(hdStr.replace('d', ''), 10) || 10;

      for (let i = 2; i <= lvl; i++) {
        sData.hpRolls[i] = Math.floor(Math.random() * sides) + 1;
      }
      recalculateAll();
      triggerAutoSave();
    });
  }

  const btnAvgHp = document.getElementById('btnModalSetAverage');
  if (btnAvgHp) {
    btnAvgHp.addEventListener('click', () => {
      const lvl = Math.min(20, Math.max(1, parseInt(document.getElementById('sheetLevelInput').value, 10) || 1));
      const classKey = document.getElementById('sheetClassSelect').value;
      const hdStr = (pfData.classes[classKey] && pfData.classes[classKey].hit_die) || 'd10';
      const sides = parseInt(hdStr.replace('d', ''), 10) || 10;
      const avg = Math.floor(sides / 2) + 1;

      for (let i = 2; i <= lvl; i++) {
        sData.hpRolls[i] = avg;
      }
      recalculateAll();
      triggerAutoSave();
    });
  }

  // Dual Page Print Tab View Injection
  const tabBothBtn = document.getElementById('tab-both-btn');
  if (tabBothBtn) {
    tabBothBtn.addEventListener('click', () => {
      const container = document.getElementById('bothPagesContainer');
      const p1 = document.getElementById('tab-page1').cloneNode(true);
      const p2 = document.getElementById('tab-page2').cloneNode(true);

      p1.classList.add('show', 'active', 'mb-5');
      p2.classList.add('show', 'active', 'page-break');

      container.innerHTML = '';
      container.appendChild(p1);
      container.appendChild(p2);
    });
  }

  // -------------------------------------------------------------
  // SAVE / PERSISTENCE LOGIC
  // -------------------------------------------------------------
  let autoSaveTimeout = null;
  function triggerAutoSave() {
    const indicator = document.getElementById('saveStatusIndicator');
    if (indicator) {
      indicator.innerHTML = '<span class="spinner-border spinner-border-sm me-1 text-warning"></span>Kaydediliyor...';
    }
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
      saveAllData();
    }, 600);
  }

  function saveAllData() {
    const sheetData = {
      alignment: document.getElementById('sheetAlignmentSelect').value,
      player: document.getElementById('sheetPlayerName').value,
      deity: document.getElementById('sheetDeityInput').value,
      homeland: document.getElementById('sheetHomelandInput').value,
      size: document.getElementById('sheetSizeSelect').value,
      gender: document.getElementById('sheetGenderInput').value,
      age: document.getElementById('sheetAgeInput').value,
      height: document.getElementById('sheetHeightInput').value,
      eyesHair: document.getElementById('sheetEyesHairInput').value,
      currentHp: parseInt(document.getElementById('sheetCurrentHp').value, 10) || 12,
      maxHp: parseInt(document.getElementById('sheetMaxHp').value, 10) || 12,
      hitDie: document.getElementById('sheetHitDie').value || 'd10',
      dr: document.getElementById('sheetDrInput').value,
      nonlethal: document.getElementById('sheetNonlethalInput').value,
      speedBase: parseInt(document.getElementById('sheetSpeedBase').value, 10) || 30,
      speedArmor: parseInt(document.getElementById('sheetSpeedArmor').value, 10) || 20,
      speedFly: document.getElementById('sheetSpeedFly').value,
      speedSwim: document.getElementById('sheetSpeedSwim').value,
      speedClimb: document.getElementById('sheetSpeedClimb').value,
      speedBurrow: document.getElementById('sheetSpeedBurrow').value,
      initMisc: parseInt(document.getElementById('sheetInitMisc').value, 10) || 0,
      acNatural: parseInt(document.getElementById('sheetAcNatural')?.value, 10) || 0,
      acDeflect: parseInt(document.getElementById('sheetAcDeflect')?.value, 10) || 0,
      acMisc: parseInt(document.getElementById('sheetAcMisc')?.value, 10) || 0,
      sr: document.getElementById('sheetSrInput').value,
      saveConditional: document.getElementById('sheetSaveConditional')?.value || '',
      saveBase_fort: parseInt(document.getElementById('saveBase_fort')?.value, 10) || 0,
      saveAbility_fort: parseInt(document.getElementById('saveAbility_fort')?.value, 10) || 0,
      saveMagic_fort: parseInt(document.getElementById('saveMagic_fort')?.value, 10) || 0,
      saveMisc_fort: parseInt(document.getElementById('saveMisc_fort')?.value, 10) || 0,
      saveBase_ref: parseInt(document.getElementById('saveBase_ref')?.value, 10) || 0,
      saveAbility_ref: parseInt(document.getElementById('saveAbility_ref')?.value, 10) || 0,
      saveMagic_ref: parseInt(document.getElementById('saveMagic_ref')?.value, 10) || 0,
      saveMisc_ref: parseInt(document.getElementById('saveMisc_ref')?.value, 10) || 0,
      saveBase_will: parseInt(document.getElementById('saveBase_will')?.value, 10) || 0,
      saveAbility_will: parseInt(document.getElementById('saveAbility_will')?.value, 10) || 0,
      saveMagic_will: parseInt(document.getElementById('saveMagic_will')?.value, 10) || 0,
      saveMisc_will: parseInt(document.getElementById('saveMisc_will')?.value, 10) || 0,
      abilityScores: {
        str: parseInt(document.getElementById('score_str')?.value, 10) || 10,
        dex: parseInt(document.getElementById('score_dex')?.value, 10) || 10,
        con: parseInt(document.getElementById('score_con')?.value, 10) || 10,
        int: parseInt(document.getElementById('score_int')?.value, 10) || 10,
        wis: parseInt(document.getElementById('score_wis')?.value, 10) || 10,
        cha: parseInt(document.getElementById('score_cha')?.value, 10) || 10
      },
      skills: {},
      weapons: [],
      acItems: [],
      saveItems: [],
      gear: [],
      feats: [],
      specials: [],
      spells: {
        castingStat: document.getElementById('sheetCastingStat')?.value || 'cha',
        spellNotes: document.getElementById('sheetSpellNotes')?.value || '',
        slots: []
      },
      hpRolls: sData.hpRolls || {},
      hpExtraMisc: parseInt(document.getElementById('sheetHpExtraMisc')?.value, 10) || 0,
      traits: {
        uye: {
          name: document.getElementById('sheetTraitName_uye')?.value.trim() || '',
          desc: document.getElementById('sheetTraitDesc_uye')?.value.trim() || ''
        },
        kidemli: {
          name: document.getElementById('sheetTraitName_kidemli')?.value.trim() || '',
          desc: document.getElementById('sheetTraitDesc_kidemli')?.value.trim() || ''
        },
        uzman: {
          name: document.getElementById('sheetTraitName_uzman')?.value.trim() || '',
          desc: document.getElementById('sheetTraitDesc_uzman')?.value.trim() || ''
        }
      },
      moneyCp: parseInt(document.getElementById('sheetMoneyCp').value, 10) || 0,
      moneySp: parseInt(document.getElementById('sheetMoneySp').value, 10) || 0,
      moneyGp: parseInt(document.getElementById('sheetMoneyGp').value, 10) || 0,
      moneyPp: parseInt(document.getElementById('sheetMoneyPp').value, 10) || 0,
      currentXp: parseInt(document.getElementById('sheetCurrentXp')?.value, 10) || 0,
      nextLevelXp: parseInt(document.getElementById('sheetNextLevelXp')?.value, 10) || 1300,
      skillConditional: document.getElementById('sheetSkillConditional').value,
      languages: document.getElementById('sheetLanguages').value
    };

    // 1. Skills
    document.querySelectorAll('#skillsTableBody tr').forEach(tr => {
      const k = tr.getAttribute('data-skill-key');
      if (k) {
        sheetData.skills[k] = {
          isClassSkill: tr.querySelector('.chk-class-skill')?.checked || false,
          ranks: parseInt(tr.querySelector('.skill-ranks-input')?.value, 10) || 0,
          misc: parseInt(tr.querySelector('.skill-misc-input')?.value, 10) || 0,
          customSubname: tr.querySelector('.skill-custom-subname')?.value || ''
        };
      }
    });

    // 2. Weapons
    document.querySelectorAll('.weapon-row').forEach(row => {
      sheetData.weapons.push({
        name: row.querySelector('.wp-name')?.value || '',
        atkType: row.querySelector('.wp-atk-type')?.value || 'melee',
        type: row.querySelector('.wp-type')?.value || '',
        range: row.querySelector('.wp-range')?.value || '',
        baseDie: row.querySelector('.wp-base-die')?.value || '1d8',
        enh: parseInt(row.querySelector('.wp-enh')?.value, 10) || 0,
        miscAtk: parseInt(row.querySelector('.wp-misc-atk')?.value, 10) || 0,
        critRange: row.querySelector('.wp-crit-range')?.value || '20',
        critMult: parseInt(row.querySelector('.wp-crit-mult')?.value, 10) || 2,
        ammo: row.querySelector('.wp-ammo')?.value || '',
        atkBonus: row.querySelector('.wp-atk-total')?.textContent || '+0',
        dmg: row.querySelector('.wp-dmg-total')?.textContent || '1d8',
        critDmg: row.querySelector('.wp-crit-dmg-total')?.textContent || '2d8'
      });
    });

    // 3. AC Items
    document.querySelectorAll('#acItemsTbody tr').forEach(tr => {
      const name = tr.querySelector('.ac-item-name')?.value;
      const type = tr.querySelector('.ac-item-type')?.value || 'armor';
      const isActive = tr.querySelector('.ac-item-active')?.checked ?? true;
      if (name !== undefined) {
        sheetData.acItems.push({
          name: name,
          type: type,
          bonus: parseInt(tr.querySelector('.ac-item-bonus')?.value, 10) || 0,
          pen: parseInt(tr.querySelector('.ac-item-pen')?.value, 10) || 0,
          weight: parseInt(tr.querySelector('.ac-item-weight')?.value, 10) || 0,
          max_dex: tr.getAttribute('data-max-dex') || null,
          category: tr.getAttribute('data-category') || null,
          is_active: isActive
        });
      }
    });

    // 3.5 Save Items (Resistance & Protection)
    sheetData.saveItems = [];
    document.querySelectorAll('#saveItemsTbody tr').forEach(tr => {
      const name = tr.querySelector('.save-item-name')?.value;
      const target = tr.querySelector('.save-item-target')?.value || 'all';
      const type = tr.querySelector('.save-item-type')?.value || 'resistance';
      const isActive = tr.querySelector('.save-item-active')?.checked ?? true;
      if (name !== undefined) {
        sheetData.saveItems.push({
          name: name,
          target: target,
          type: type,
          bonus: parseInt(tr.querySelector('.save-item-bonus')?.value, 10) || 0,
          weight: parseInt(tr.querySelector('.save-item-weight')?.value, 10) || 0,
          is_active: isActive
        });
      }
    });

    // 4. Gear
    document.querySelectorAll('#gearItemsContainer > div').forEach(div => {
      const nameInp = div.querySelector('.gear-name');
      const wtInp = div.querySelector('.gear-wt');
      if (nameInp) {
        sheetData.gear.push({
          name: nameInp.value,
          weight: parseFloat(wtInp?.value) || 0
        });
      }
    });

    // 5. Feats
    document.querySelectorAll('#featsContainer .feat-title').forEach(tEl => {
      const parent = tEl.closest('div.feat-item') || tEl.closest('div.p-2') || tEl.parentElement;
      const descEl = parent ? parent.querySelector('.feat-desc') : null;
      sheetData.feats.push({
        title: tEl.textContent.trim(),
        desc: descEl ? descEl.textContent.trim() : ''
      });
    });

    // 6. Special Abilities
    document.querySelectorAll('#specialsContainer > div').forEach(div => {
      const badge = div.querySelector('.badge')?.textContent.trim() || 'Special';
      const strong = div.querySelector('strong')?.textContent.trim() || '';
      const desc = div.querySelector('small')?.textContent.trim() || '';
      if (strong) {
        sheetData.specials.push({ badge, title: strong, desc });
      }
    });

    // 7. Spells Slots
    document.querySelectorAll('#tab-page2 table tbody tr').forEach((tr, lvl) => {
      const known = parseInt(tr.querySelector('.spell-known')?.value, 10) || 0;
      const perDay = parseInt(tr.querySelector('.spell-per-day')?.value, 10) || 0;
      const used = parseInt(tr.querySelector('.spell-used')?.value, 10) || 0;
      sheetData.spells.slots.push({ lvl, known, perDay, used });
    });

    // Core character identity
    currentChar.name = document.getElementById('sheetCharName').value.trim() || 'Valiant Adventurer';
    currentChar.level = parseInt(document.getElementById('sheetLevelInput').value, 10) || 1;
    currentChar.classKey = document.getElementById('sheetClassSelect').value;
    const classSel = document.getElementById('sheetClassSelect');
    currentChar.class = classSel.options[classSel.selectedIndex]?.text || currentChar.classKey;
    currentChar.className = currentChar.class.split('(')[0].trim();
    currentChar.race = cleanText(document.getElementById('sheetRaceInput').value) || 'Human';
    currentChar.hp = sheetData.currentHp;
    currentChar.maxHp = sheetData.maxHp;
    currentChar.hitDie = sheetData.hitDie;
    currentChar.sheetData = sheetData;
    sData.hpExtraMisc = sheetData.hpExtraMisc;
    sData.traits = sheetData.traits;

    // Update top header character name
    const topName = document.getElementById('topCharNameDisplay');
    if (topName) topName.textContent = currentChar.name;
    const topLvl = document.getElementById('sheetTopLevelBadge');
    if (topLvl) topLvl.textContent = 'Lvl ' + currentChar.level;

    // A. Save to localStorage list (offline cache)
    const allChars = getCharacters();
    const idx = allChars.findIndex(c => c.id === currentChar.id || c.name === currentChar.name);
    if (idx !== -1) {
      allChars[idx] = currentChar;
    } else {
      allChars.push(currentChar);
    }
    saveCharacters(allChars);

    // B. Save to PostgreSQL Database
    const indicator = document.getElementById('saveStatusIndicator');
    if (indicator) {
      indicator.className = 'text-warning small me-1 d-none d-sm-inline animate__animated animate__fadeIn';
      indicator.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving to database...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    fetch('{{ route("valiant-shade.character-sheet.save") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(currentChar)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        if (indicator) {
          indicator.className = 'text-success small me-1 d-none d-sm-inline animate__animated animate__fadeIn';
          indicator.innerHTML = '<i class="icon-base ti tabler-database-check fs-6 me-1"></i>' + i18n.savedToDb;
        }
      } else {
        if (indicator) {
          indicator.className = 'text-danger small me-1 d-none d-sm-inline';
          indicator.innerHTML = '<i class="icon-base ti tabler-alert-circle fs-6 me-1"></i>' + i18n.saveError + ': ' + (res.message || '');
        }
      }
    })
    .catch(err => {
      console.warn('DB Save Error:', err);
      if (indicator) {
        indicator.className = 'text-warning small me-1 d-none d-sm-inline';
        indicator.innerHTML = '<i class="icon-base ti tabler-check fs-6 me-1"></i>' + i18n.savedLocally;
      }
    });
  }

  const saveBtn = document.getElementById('btnSaveSheet');
  if (saveBtn) {
    saveBtn.addEventListener('click', () => {
      saveAllData();
      saveBtn.classList.add('btn-success');
      saveBtn.classList.remove('btn-primary');
      saveBtn.innerHTML = '<i class="icon-base ti tabler-check"></i> ' + i18n.saved;
      setTimeout(() => {
        saveBtn.classList.remove('btn-success');
        saveBtn.classList.add('btn-primary');
        saveBtn.innerHTML = '<i class="icon-base ti tabler-device-floppy"></i> ' + i18n.saveChanges;
      }, 1500);
    });
  }

  // AC Input change listeners
  ['sheetAcArmor', 'sheetAcShield', 'sheetAcNatural', 'sheetAcDeflect', 'sheetAcMisc', 'sheetSizeSelect'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', () => { recalculateAll(); triggerAutoSave(); });
      el.addEventListener('change', () => { recalculateAll(); triggerAutoSave(); });
    }
  });

  // HP Extra / Feat Bonus & Current HP real-time change listeners
  const hpExtraEl = document.getElementById('sheetHpExtraMisc');
  if (hpExtraEl) {
    const onHpExtraChange = () => {
      sData.hpExtraMisc = parseInt(hpExtraEl.value, 10) || 0;
      recalculateAll();
      triggerAutoSave();
    };
    hpExtraEl.addEventListener('input', onHpExtraChange);
    hpExtraEl.addEventListener('change', onHpExtraChange);
  }

  const currentHpEl = document.getElementById('sheetCurrentHp');
  if (currentHpEl) {
    currentHpEl.addEventListener('input', () => { triggerAutoSave(); });
    currentHpEl.addEventListener('change', () => { triggerAutoSave(); });
  }

  // Traits real-time auto-save listeners
  ['sheetTraitName_uye', 'sheetTraitDesc_uye', 'sheetTraitName_kidemli', 'sheetTraitDesc_kidemli', 'sheetTraitName_uzman', 'sheetTraitDesc_uzman'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', () => { triggerAutoSave(); });
      el.addEventListener('change', () => { triggerAutoSave(); });
    }
  });

  // -------------------------------------------------------------
  // Initial Boot Sequence
  // -------------------------------------------------------------
  setupArmorSearch();
  renderSkillsTable();
  attachDynamicRowEvents();
  attachWeaponEvents();
  renderEquippedArmorChips();
  onClassOrLevelChange();
});
</script>
@endsection
