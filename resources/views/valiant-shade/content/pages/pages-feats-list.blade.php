@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Feat and Traits') . ' - Pathfinder 1E')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('page-style')
<style>
  /* ----------------------------------------------------
     Vuexy Demo Theme - Pathfinder 1E Feats & Progression
     ---------------------------------------------------- */
  :root {
    --pf-primary: #7367f0;
    --pf-primary-rgb: 115, 103, 240;
    --pf-warning: #ff9f43;
    --pf-warning-rgb: 255, 159, 67;
    --pf-card-bg: #2f3349;
    --pf-body-dark: #25293c;
    --pf-border-color: rgba(255, 255, 255, 0.12);
  }

  /* Hero Banner matching Vuexy Demo */
  .feats-hero-banner {
    background: radial-gradient(circle at 10% 50%, rgba(var(--pf-primary-rgb), 0.16) 0%, transparent 60%),
                radial-gradient(circle at 90% 20%, rgba(var(--pf-warning-rgb), 0.12) 0%, transparent 50%),
                linear-gradient(135deg, rgba(47, 51, 73, 0.95) 0%, rgba(37, 41, 60, 0.98) 100%);
    border: 1px solid var(--pf-border-color);
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
    position: relative;
    overflow: hidden;
  }

  /* Metric KPI Stat Cards matching Vuexy */
  .feat-stat-card {
    background: rgba(37, 41, 60, 0.85);
    border: 1px solid var(--pf-border-color);
    border-radius: 12px;
    padding: 0.9rem 1.15rem;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .feat-stat-card:hover {
    transform: translateY(-2px);
    border-color: rgba(var(--pf-primary-rgb), 0.5);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
  }

  /* View switcher pill buttons */
  .view-pill-btn {
    background: transparent;
    color: #a3a9cb;
    border: none;
    border-radius: 8px;
    padding: 0.48rem 1.05rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.18s ease;
    cursor: pointer;
  }
  .view-pill-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
  }
  .view-pill-btn.active {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 14px rgba(115, 103, 240, 0.45);
  }

  /* Filter pill inside progression */
  .prog-filter-pill {
    background: transparent;
    color: #a3a9cb;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 0.3rem 0.8rem;
    font-size: 0.78rem;
    font-weight: 600;
    transition: all 0.15s ease;
    cursor: pointer;
  }
  .prog-filter-pill:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.2);
  }
  .prog-filter-pill.active {
    background: rgba(115, 103, 240, 0.2) !important;
    color: #7367f0 !important;
    border-color: #7367f0 !important;
  }

  /* Character Dossier Card */
  .char-dossier-card {
    background: #2f3349;
    border: 1px solid var(--pf-border-color);
    border-radius: 14px;
    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.28);
    overflow: hidden;
  }
  .char-dossier-header {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.18) 0%, rgba(255, 159, 67, 0.14) 100%);
    border-bottom: 1px solid var(--pf-border-color);
    padding: 1.15rem 1.45rem;
  }

  /* Attribute Stat Boxes */
  .stat-pill-box {
    background: rgba(37, 41, 60, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 0.6rem 0.8rem;
    text-align: center;
    transition: all 0.2s ease;
  }
  .stat-pill-box:hover {
    border-color: rgba(var(--pf-primary-rgb), 0.4);
    background: rgba(var(--pf-primary-rgb), 0.06);
  }

  /* Progression Table (Vuexy Demo Style) */
  .table-progression {
    --bs-table-bg: transparent;
    --bs-table-color: #cbd0ec;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
  }
  .table-progression thead th {
    background: rgba(37, 41, 60, 0.98) !important;
    color: #e2e5f5 !important;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-top: none !important;
  }
  .table-progression tbody tr {
    transition: background-color 0.15s ease;
  }
  .table-progression tbody tr:hover {
    background: rgba(115, 103, 240, 0.06) !important;
  }
  .table-progression tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    border-top: none !important;
    vertical-align: middle;
  }

  /* Level indicator cell */
  .level-cell {
    font-weight: 700;
    font-size: 1rem;
    color: #7367f0;
    font-family: monospace;
    text-align: center;
    width: 130px;
  }

  /* Active Levels (Level 1 up to Character's Current Level - Radiant Extra Brightness) */
  .table-progression tbody tr.row-active-level {
    background: linear-gradient(90deg, rgba(115, 103, 240, 0.18) 0%, rgba(47, 51, 73, 0.97) 18%, rgba(37, 41, 60, 0.95) 100%) !important;
    border-left: 4px solid #7367f0 !important;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
  }
  .table-progression tbody tr.row-active-level:hover {
    background: linear-gradient(90deg, rgba(115, 103, 240, 0.28) 0%, rgba(47, 51, 73, 0.99) 25%, rgba(47, 51, 73, 0.97) 100%) !important;
    box-shadow: inset 0 0 20px rgba(115, 103, 240, 0.22);
  }
  .table-progression tbody tr.row-active-level td {
    color: #ffffff !important;
  }
  .table-progression tbody tr.row-active-level .lvl-badge {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    font-weight: 700;
    box-shadow: 0 0 14px rgba(115, 103, 240, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
  }

  /* Dimmed Future Levels (Above Character's Current Level) */
  .table-progression tbody tr.row-future-level {
    opacity: 0.32;
    background: rgba(25, 28, 42, 0.25) !important;
    border-left: 4px solid transparent !important;
    transition: opacity 0.2s ease, background 0.2s ease;
  }
  .table-progression tbody tr.row-future-level:hover {
    opacity: 0.85;
    background: rgba(37, 41, 60, 0.6) !important;
  }
  .table-progression tbody tr.row-future-level .lvl-badge {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #7b809e !important;
    border: 1px dashed rgba(255, 255, 255, 0.15) !important;
    box-shadow: none !important;
  }

  /* Action button in row */
  .btn-row-action {
    opacity: 0.35;
    transition: opacity 0.15s ease, transform 0.15s ease;
  }
  tr:hover .btn-row-action {
    opacity: 1;
  }
  .btn-row-action:hover {
    transform: scale(1.15);
  }

  /* Sortable column header */
  .th-sortable {
    cursor: pointer;
    user-select: none;
    transition: color 0.15s ease;
  }
  .th-sortable:hover {
    color: #7367f0 !important;
  }

  /* Master Sheet Table Container */
  .card-master-table-container {
    background: #2f3349;
    border: 1px solid var(--pf-border-color);
    border-radius: 14px;
    overflow: hidden;
  }

  /* ====================================================
     TRAIT CATALOG MODAL - ULTRA HIGH-CONTRAST THEME
     (No muddy purple, clean obsidian dark with pure white text)
     ==================================================== */
  #modalTraitCatalog .modal-content {
    background-color: #0d1117 !important;
    border: 1px solid #30363d !important;
    border-radius: 12px !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85) !important;
    color: #f0f6fc !important;
  }
  #modalTraitCatalog .modal-header {
    background-color: #161b22 !important;
    border-bottom: 1px solid #30363d !important;
  }
  #modalTraitCatalog .modal-title {
    color: #ffffff !important;
    font-size: 1.15rem !important;
    font-weight: 700 !important;
    letter-spacing: -0.2px !important;
  }
  #modalTraitCatalog .modal-body {
    background-color: #0d1117 !important;
  }
  #modalTraitCatalog .modal-footer {
    background-color: #161b22 !important;
    border-top: 1px solid #30363d !important;
  }

  /* Search input group */
  #modalTraitCatalog .trait-search-wrapper {
    background-color: #161b22 !important;
    border: 1px solid #30363d !important;
    border-radius: 8px !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }
  #modalTraitCatalog .trait-search-wrapper:focus-within {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
  }
  #modalTraitCatalog .trait-search-addon {
    background: transparent !important;
    border: none !important;
    color: #f59e0b !important;
    font-size: 1.1rem !important;
  }
  #modalTraitCatalog #traitCatalogSearchInput {
    background: transparent !important;
    border: none !important;
    color: #ffffff !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
  }
  #modalTraitCatalog #traitCatalogSearchInput::placeholder {
    color: #8b949e !important;
    opacity: 1 !important;
  }
  #modalTraitCatalog .trait-search-btn {
    background: #f59e0b !important;
    color: #000000 !important;
    font-weight: 700 !important;
    border: none !important;
    padding: 0.5rem 1.25rem !important;
    border-radius: 6px !important;
    transition: background 0.15s ease !important;
  }
  #modalTraitCatalog .trait-search-btn:hover {
    background: #d97706 !important;
    color: #000000 !important;
  }

  /* Category Filter Pills */
  #modalTraitCatalog .btn-trait-cat-filter {
    background-color: #161b22 !important;
    color: #c9d1d9 !important;
    border: 1px solid #30363d !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    padding: 0.3rem 0.8rem !important;
    border-radius: 6px !important;
    transition: all 0.15s ease !important;
  }
  #modalTraitCatalog .btn-trait-cat-filter:hover {
    background-color: #21262d !important;
    color: #ffffff !important;
    border-color: #8b949e !important;
  }
  #modalTraitCatalog .btn-trait-cat-filter.active {
    background: #6366f1 !important;
    color: #ffffff !important;
    border-color: #818cf8 !important;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4) !important;
  }

  /* Trait Result Cards */
  .trait-card-item {
    background-color: #161b22 !important;
    border: 1px solid #30363d !important;
    transition: all 0.15s ease !important;
  }
  .trait-card-item:hover {
    background-color: #1f242c !important;
    border-color: #6366f1 !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5) !important;
    transform: translateY(-1px);
  }
  .trait-card-title {
    color: #ffffff !important;
    font-size: 1.05rem !important;
    font-weight: 700 !important;
    letter-spacing: -0.2px !important;
  }
  .trait-card-desc {
    color: #e6edf3 !important; /* Pure legible high-contrast light silver */
    font-size: 0.88rem !important;
    line-height: 1.5 !important;
    margin-top: 0.25rem !important;
  }
  .trait-card-urllink {
    display: inline-block;
    color: #58a6ff !important;
    font-size: 0.82rem !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    margin-top: 0.25rem !important;
    transition: color 0.15s ease !important;
  }
  .trait-card-urllink:hover {
    color: #79c0ff !important;
    text-decoration: underline !important;
  }
  .trait-card-extlink {
    color: #58a6ff !important;
    font-size: 0.85rem !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    background: rgba(88, 166, 255, 0.12) !important;
    border: 1px solid rgba(88, 166, 255, 0.25) !important;
    transition: all 0.15s ease !important;
  }
  .trait-card-extlink:hover {
    color: #ffffff !important;
    background: #58a6ff !important;
  }
  .trait-btn-select {
    background: #238636 !important;
    color: #ffffff !important;
    border: 1px solid rgba(240, 246, 252, 0.1) !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    padding: 0.45rem 1.15rem !important;
    border-radius: 6px !important;
    box-shadow: 0 2px 6px rgba(35, 134, 54, 0.35) !important;
    transition: all 0.15s ease !important;
  }
  .trait-btn-select:hover {
    background: #2ea043 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(46, 160, 67, 0.45) !important;
    transform: translateY(-1px);
  }
  .trait-footer-note {
    color: #8b949e !important;
    font-size: 0.8rem !important;
  }
  .trait-btn-close {
    background: #21262d !important;
    color: #c9d1d9 !important;
    border: 1px solid #30363d !important;
    font-weight: 600 !important;
    padding: 0.4rem 1.1rem !important;
    border-radius: 6px !important;
    transition: all 0.15s ease !important;
  }
  .trait-btn-close:hover {
    background: #30363d !important;
    color: #ffffff !important;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- ====================================================
       1. HERO KPI BANNER & METRICS (VUEXY DEMO STYLE)
       ==================================================== -->
  <div class="feats-hero-banner p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="avatar avatar-sm bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs">
            <i class="icon-base ti tabler-swords fs-4"></i>
          </div>
          <h3 class="mb-0 fw-bold text-heading" style="font-size: 1.45rem; letter-spacing: 0.3px;">
            {{ __('Feat and Traits') }}
          </h3>
          <span class="badge bg-label-warning fw-bold font-monospace px-3 py-1 shadow-xs">
            {{ __('Feats List') }} &bull; Pathfinder 1E
          </span>
        </div>
        <p class="text-body-secondary small mb-0">
          {{ __('Pathfinder 1E Character Feats & Abilities') }} &bull; {{ __('Level 1–20 general feats, racial bonus feats, Rogue talents, Magus arcana, and class combat feats.') }}
        </p>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Add Feat Button -->
        <button type="button" class="btn btn-primary fw-bold d-flex align-items-center gap-1 shadow-xs" id="btnOpenAddFeatModal">
          <i class="icon-base ti tabler-plus fs-5"></i>
          <span>{{ __('Add Feat') }}</span>
        </button>

        <!-- Reset Defaults Button -->
        <button type="button" class="btn btn-outline-warning btn-sm" id="btnResetFeatsExcel" title="{{ __('Reset to Original Master Sheet Data') }}">
          <i class="icon-base ti tabler-rotate-clockwise me-1"></i> {{ __('Reset') }}
        </button>

        <a href="{{ route('valiant-shade.pages-xp-tracker') }}" class="btn btn-outline-primary btn-sm">
          <i class="icon-base ti tabler-award me-1"></i> {{ __('Player XP Tracker') }}
        </a>
      </div>
    </div>

    <!-- 4 KPI Metrics matching Vuexy Demo -->
    <div class="row g-3">
      <div class="col-6 col-md-3">
        <div class="feat-stat-card d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-primary rounded-3 d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-users fs-4"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.7rem;">Toplam Karakter</div>
            <div class="fw-bolder fs-5 text-heading font-monospace">{{ $characters->count() }}</div>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="feat-stat-card d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-info rounded-3 d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-award fs-4"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.7rem;">{{ __('Registered Feats') }}</div>
            <div class="fw-bolder fs-5 text-info font-monospace">{{ $dossier['total_feats_count'] ?? 0 }} ({{ __('This Character') }})</div>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="feat-stat-card d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-success rounded-3 d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-sparkles fs-4"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.7rem;">{{ __('Class Talents / Abilities') }}</div>
            <div class="fw-bolder fs-5 text-success font-monospace">{{ $dossier['class_count'] ?? 0 }}</div>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="feat-stat-card d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-warning rounded-3 d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-dna fs-4"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.7rem;">{{ __('Racial & Standard Feats') }}</div>
            <div class="fw-bolder fs-5 text-warning font-monospace">{{ ($dossier['standard_count'] ?? 0) + ($dossier['racial_count'] ?? 0) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ====================================================
       2. TOOLBAR: CHARACTER SELECTOR, SORTING & VIEW TOGGLE
       ==================================================== -->
  <div class="card mb-4 bg-dark bg-opacity-75 border-secondary border-opacity-25 shadow-sm">
    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
      
      <!-- Character Selection Form & Quick Search -->
      <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap" style="min-width: 280px; max-width: 520px;">
        <label for="charSelectDropdown" class="text-body-secondary small fw-bold text-nowrap">
          <i class="icon-base ti tabler-user me-1 text-primary"></i> {{ __("Character:") }}
        </label>
        <select class="form-select text-heading bg-dark border-secondary border-opacity-50" id="charSelectDropdown" onchange="switchCharacter(this.value)">
          @foreach($characters as $c)
            <option value="{{ $c->character_name }}" {{ $c->character_name === $selectedChar ? 'selected' : '' }}>
              {{ $c->character_name }} ({{ $c->class_name }} Lvl {{ $c->level }} &bull; {{ $c->feats_count ?? 0 }} Feat)
            </option>
          @endforeach
        </select>
      </div>

      <!-- Sorting Dropdown -->
      <div class="d-flex align-items-center gap-2">
        <label for="toolbarSortSelect" class="text-body-secondary small fw-bold text-nowrap">
          <i class="icon-base ti tabler-arrows-sort me-1 text-primary"></i> {{ __("Sort By:") }}
        </label>
        <select class="form-select form-select-sm text-heading bg-dark border-secondary border-opacity-50" id="toolbarSortSelect" onchange="changeSorting(this.value)" style="width: auto;">
          <option value="name_asc" {{ ($sortBy ?? 'name_asc') === 'name_asc' ? 'selected' : '' }}>{{ __('Name (A &rarr; Z)') }}</option>
          <option value="name_desc" {{ ($sortBy ?? '') === 'name_desc' ? 'selected' : '' }}>{{ __('Name (Z &rarr; A)') }}</option>
          <option value="level_desc" {{ ($sortBy ?? '') === 'level_desc' ? 'selected' : '' }}>{{ __('Level (Highest &darr;)') }}</option>
          <option value="level_asc" {{ ($sortBy ?? '') === 'level_asc' ? 'selected' : '' }}>{{ __('Level (Lowest &uarr;)') }}</option>
          <option value="feats_desc" {{ ($sortBy ?? '') === 'feats_desc' ? 'selected' : '' }}>{{ __('Feats Count (Most &darr;)') }}</option>
          <option value="feats_asc" {{ ($sortBy ?? '') === 'feats_asc' ? 'selected' : '' }}>{{ __('Feats Count (Least &uarr;)') }}</option>
        </select>
      </div>

      <!-- View Switcher Tabs (Dossier vs Master Table) -->
      <div class="d-flex align-items-center gap-1 bg-dark bg-opacity-75 p-1 rounded-3 border border-secondary border-opacity-25 ms-auto" id="viewModeToggleGroup">
        <button type="button" class="view-pill-btn {{ $activeView === 'dossier' ? 'active' : '' }}" id="btnViewDossier" onclick="switchViewMode('dossier')">
          <i class="icon-base ti tabler-id me-1"></i> {{ __('Character Dossier View') }}
        </button>
        <button type="button" class="view-pill-btn {{ $activeView === 'table' ? 'active' : '' }}" id="btnViewTable" onclick="switchViewMode('table')">
          <i class="icon-base ti tabler-table me-1"></i> {{ __('Master Feats Archive Table') }}
        </button>
      </div>
    </div>
  </div>

  <!-- ====================================================
       3. VIEW 1: CHARACTER DOSSIER (VUEXY DEMO THEMED)
       ==================================================== -->
  <div id="viewDossierContainer" class="{{ $activeView === 'table' ? 'd-none' : '' }}">
    @if($dossier && $dossier['profile'])
      @php
        $prof = $dossier['profile'];
      @endphp

      <!-- Character Info Card (Top Dossier Banner) -->
      <div class="card mb-4 border-secondary border-opacity-25 bg-dark bg-opacity-75 shadow-sm">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-xl bg-label-primary rounded-circle d-flex align-items-center justify-content-center fs-3 shadow-xs">
                <i class="icon-base ti tabler-shield-half"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h3 class="mb-0 fw-bold text-heading font-monospace">{{ $prof->character_name }}</h3>
                  <span class="badge bg-label-primary fw-bold">{{ __('Level') }} {{ $prof->level }}</span>
                  <span class="badge bg-label-warning">{{ __($prof->rank ?? 'Member') }}</span>
                </div>
                <div class="text-body-secondary small mt-1">
                  <span class="text-heading fw-semibold">{{ $prof->race }}</span> &bull; 
                  <span class="text-primary fw-semibold">{{ $prof->class_name }}</span> 
                  @if($prof->archetype)
                    <span class="text-info">({{ $prof->archetype }})</span>
                  @endif
                  &bull; Oyuncu: <span class="text-heading">{{ $prof->player_name ?? 'Bilinmiyor' }}</span>
                </div>
              </div>
            </div>

            <!-- Action Buttons: Edit Stats & Delete Character -->
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenEditStatsModal">
                <i class="icon-base ti tabler-edit me-1"></i> {{ __('Edit Character Stats') }}
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCharacterConfirm('{{ addslashes($prof->character_name) }}')">
                <i class="icon-base ti tabler-trash me-1"></i> Karakteri Sil
              </button>
            </div>
          </div>

          <!-- Attributes Grid (STR, DEX, CON, INT, WIS, CHA) + Combat Core -->
          <div class="row g-2 text-center pt-2 border-top border-secondary border-opacity-20">
            <div class="col-4 col-sm-2 col-md-1">
              <div class="stat-pill-box">
                <div class="text-muted text-uppercase small fw-bold" style="font-size: 0.65rem;">STR</div>
                <div class="fw-bolder fs-5 text-heading">{{ $prof->str }}</div>
                <div class="badge bg-label-primary small px-1 py-0">{{ $prof->getModStr('str') }}</div>
              </div>
            </div>
            <div class="col-4 col-sm-2 col-md-1">
              <div class="stat-pill-box">
                <div class="text-muted text-uppercase small fw-bold" style="font-size: 0.65rem;">DEX</div>
                <div class="fw-bolder fs-5 text-heading">{{ $prof->dex }}</div>
                <div class="badge bg-label-primary small px-1 py-0">{{ $prof->getModStr('dex') }}</div>
              </div>
            </div>
            <div class="col-4 col-sm-2 col-md-1">
              <div class="stat-pill-box">
                <div class="text-muted text-uppercase small fw-bold" style="font-size: 0.65rem;">CON</div>
                <div class="fw-bolder fs-5 text-heading">{{ $prof->con }}</div>
                <div class="badge bg-label-primary small px-1 py-0">{{ $prof->getModStr('con') }}</div>
              </div>
            </div>
            <div class="col-4 col-sm-2 col-md-1">
              <div class="stat-pill-box">
                <div class="text-muted text-uppercase small fw-bold" style="font-size: 0.65rem;">INT</div>
                <div class="fw-bolder fs-5 text-heading">{{ $prof->int }}</div>
                <div class="badge bg-label-primary small px-1 py-0">{{ $prof->getModStr('int') }}</div>
              </div>
            </div>
            <div class="col-4 col-sm-2 col-md-1">
              <div class="stat-pill-box">
                <div class="text-muted text-uppercase small fw-bold" style="font-size: 0.65rem;">WIS</div>
                <div class="fw-bolder fs-5 text-heading">{{ $prof->wis }}</div>
                <div class="badge bg-label-primary small px-1 py-0">{{ $prof->getModStr('wis') }}</div>
              </div>
            </div>
            <div class="col-4 col-sm-2 col-md-1">
              <div class="stat-pill-box">
                <div class="text-muted text-uppercase small fw-bold" style="font-size: 0.65rem;">CHA</div>
                <div class="fw-bolder fs-5 text-heading">{{ $prof->cha }}</div>
                <div class="badge bg-label-primary small px-1 py-0">{{ $prof->getModStr('cha') }}</div>
              </div>
            </div>

            <!-- Combat Stats: BAB, FORT, REF, WILL, HP -->
            <div class="col-6 col-sm-4 col-md-2">
              <div class="stat-pill-box border-primary border-opacity-35">
                <div class="text-primary text-uppercase small fw-bold" style="font-size: 0.65rem;">BAB</div>
                <div class="fw-bolder fs-5 text-primary font-monospace">{{ $dossier['calculated_bab'] }}</div>
                <small class="text-muted" style="font-size: 0.7rem;">Base Attack</small>
              </div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
              <div class="stat-pill-box border-success border-opacity-35">
                <div class="text-success text-uppercase small fw-bold" style="font-size: 0.65rem;">SAVES (F / R / W)</div>
                <div class="fw-bolder fs-6 text-success font-monospace">
                  {{ $dossier['calculated_fort'] }} / {{ $dossier['calculated_ref'] }} / {{ $dossier['calculated_will'] }}
                </div>
                <small class="text-muted" style="font-size: 0.7rem;">Fort &bull; Ref &bull; Will</small>
              </div>
            </div>
            <div class="col-12 col-sm-4 col-md-2">
              <div class="stat-pill-box border-danger border-opacity-35">
                <div class="text-danger text-uppercase small fw-bold" style="font-size: 0.65rem;">HP / CAN ({{ $dossier['hit_die'] }})</div>
                <div class="fw-bolder fs-5 text-danger font-monospace">{{ $prof->hp ?? 60 }} HP</div>
                <small class="text-muted" style="font-size: 0.7rem;">Hit Die: {{ $dossier['hit_die'] }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TRAITS MINI TABLE (GUILD RANK PROGRESSION: MEMBER, SENIOR, EXPERT)
           ==================================================== -->
      <div class="card mb-4 shadow-sm" id="cardGuildTraits" style="background: #2f3349; border: 1px solid rgba(255, 171, 0, 0.35);">
        <div class="card-header d-flex align-items-center justify-content-between p-3 border-bottom border-secondary border-opacity-20 flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="avatar avatar-sm bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-sparkles fs-4"></i>
            </span>
            <div>
              <h5 class="mb-0 fw-bold text-heading font-monospace">Karakter Traitleri (Lonca Rütbe Gelişimi)</h5>
              <small class="text-body-secondary font-monospace">
                {{ __('Special character traits unlocked according to guild hierarchy (Member • Senior • Expert)') }}
              </small>
            </div>
          </div>
          <span class="badge bg-label-warning font-monospace py-1 px-3">
            {{ __($prof->rank ?? 'Member') }} &bull; {{ __('Rank Traits') }}
          </span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="traitsTable" style="font-size: 0.84rem;">
            <thead class="table-dark">
              <tr class="font-monospace text-uppercase" style="font-size: 0.72rem;">
                <th style="width: 190px;">{{ __('Slot / Rank') }}</th>
                <th style="width: 110px;">{{ __('Status') }}</th>
                <th>{{ __('Trait Name') }}</th>
                <th style="width: 120px;">{{ __('Category') }}</th>
                <th>{{ __('Description / Bonus') }}</th>
                <th style="width: 140px;" class="text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach(['uye', 'kidemli', 'uzman'] as $sKey)
                @php
                  $sData = $traitsProgression[$sKey] ?? [];
                  $isUnlocked = $sData['is_unlocked'] ?? false;
                  $tObj = $sData['trait'] ?? null;
                  $tName = is_object($tObj) ? ($tObj->trait_name ?? '') : ($tObj['trait_name'] ?? '');
                  $tCat = is_object($tObj) ? ($tObj->category ?? '') : ($tObj['category'] ?? '');
                  $tDesc = is_object($tObj) ? ($tObj->description ?? '') : ($tObj['description'] ?? '');
                  $tUrl = is_object($tObj) ? ($tObj->source_url ?? '') : ($tObj['source_url'] ?? '');
                @endphp
                <tr class="{{ $isUnlocked ? '' : 'opacity-50 bg-light-subtle' }}">
                  <td>
                    <div class="fw-bold font-monospace text-heading">{{ $sData['slot_label'] ?? $sKey }}</div>
                    <small class="text-body-secondary">{{ __('Requires:') }} <span class="badge bg-label-secondary py-0 px-1">{{ __($sData['required_rank'] ?? $sKey) }}</span></small>
                  </td>
                  <td>
                    @if($isUnlocked)
                      <span class="badge bg-label-success font-monospace py-1 px-2 d-inline-flex align-items-center gap-1">
                        <i class="icon-base ti tabler-lock-open fs-6"></i> {{ __('Unlocked') }}
                      </span>
                    @else
                      <span class="badge bg-label-secondary font-monospace py-1 px-2 d-inline-flex align-items-center gap-1">
                        <i class="icon-base ti tabler-lock fs-6"></i> {{ __('Locked') }}
                      </span>
                    @endif
                  </td>
                  <td>
                    @if(!empty($tName))
                      <span class="fw-bold text-primary font-monospace">{{ $tName }}</span>
                      @if(!empty($tUrl))
                        <a href="{{ $tUrl }}" target="_blank" class="ms-1 text-muted small" title="{{ __('d20pfsrd source') }}"><i class="icon-base ti tabler-external-link"></i></a>
                      @endif
                    @elseif($isUnlocked)
                      <span class="text-body-secondary fst-italic">{{ __('No trait assigned yet') }}</span>
                    @else
                      <span class="text-muted fst-italic">{{ __('Unlocks upon reaching :rank rank', ['rank' => __($sData['required_rank'])]) }}</span>
                    @endif
                  </td>
                  <td>
                    @if(!empty($tCat))
                      <span class="badge bg-label-info font-monospace py-0 px-2">{{ $tCat }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    <div class="text-body-secondary small" style="max-width: 420px; white-space: normal; line-height: 1.4;">
                      {{ !empty($tDesc) ? $tDesc : '—' }}
                    </div>
                  </td>
                  <td class="text-end">
                    @if($isUnlocked)
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <button type="button" class="btn btn-xs btn-label-warning rounded-pill px-2 btn-open-trait-modal"
                                data-slot="{{ $sKey }}"
                                data-slot-label="{{ $sData['slot_label'] ?? $sKey }}"
                                data-char="{{ $prof->character_name }}">
                          <i class="icon-base ti tabler-sparkles me-1"></i> {{ !empty($tName) ? __('Change') : __('Select Trait') }}
                        </button>
                        @if(!empty($tName))
                          <button type="button" class="btn btn-icon btn-text-danger btn-xs btn-clear-trait"
                                  data-slot="{{ $sKey }}"
                                  data-char="{{ $prof->character_name }}"
                                  title="{{ __('Remove Trait') }}">
                            <i class="icon-base ti tabler-x"></i>
                          </button>
                        @endif
                      </div>
                    @else
                      <span class="text-muted small font-monospace"><i class="icon-base ti tabler-lock fs-6 me-1"></i>Kilitli</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- Progression Table Card with Sorting & Filters -->
      <div class="char-dossier-card mb-4">
        <div class="char-dossier-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-scroll fs-3 text-primary"></i>
            <h4 class="mb-0 fw-bold text-heading font-monospace" style="letter-spacing: 0.3px;">
              {{ $prof->character_name }} &mdash; Feats & Class Progression
            </h4>
          </div>
          
          <!-- Progression Filter & Sort Toolbar -->
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Filter Pills inside Progression -->
            <div class="d-flex align-items-center gap-1 flex-wrap">
              <button type="button" class="prog-filter-pill active" data-filter="all" onclick="filterProgression('all', this)">{{ __('All (1-20)') }}</button>
              <button type="button" class="prog-filter-pill" data-filter="populated" onclick="filterProgression('populated', this)">Dolu Seviyeler</button>
              <button type="button" class="prog-filter-pill" data-filter="current" onclick="filterProgression('current', this)">Aktif Seviyeler (1-{{ $prof->level }})</button>
            </div>

            <button type="button" class="btn btn-xs btn-outline-secondary" id="btnToggleLevelSort" onclick="toggleProgressionSort()">
              <i class="icon-base ti tabler-arrows-sort me-1"></i> <span id="sortDirectionLabel">1 &rarr; 20</span>
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-progression" id="progressionTable">
            <thead>
              <tr>
                <th style="width: 140px;">{{ __('FEAT SEVİYESİ') }}</th>
                <th>FEAT ADI</th>
                <th class="text-end" style="width: 120px;">EKLE</th>
              </tr>
            </thead>
            <tbody id="progressionTableBody">
              @foreach($dossier['progression'] as $lvl => $slot)
                @php
                  $hasAnyFeat = $slot['all_feats']->isNotEmpty();
                  $hasClassFeat = $slot['class_feats']->isNotEmpty() || $slot['has_class_talent_slot'] || $slot['has_class_bonus_slot'];
                  $isCurrentLevelOrPast = ($lvl <= $prof->level);
                @endphp
                <tr class="prog-row {{ $isCurrentLevelOrPast ? 'row-active-level' : 'row-future-level' }}" 
                    data-level="{{ $lvl }}" 
                    data-has-feats="{{ $hasAnyFeat ? '1' : '0' }}"
                    data-has-class="{{ $hasClassFeat ? '1' : '0' }}">
                  
                  <!-- FEAT LEVEL Column (No emojis, clean level badges, Level 1 guidance) -->
                  <td class="level-cell">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                      <span class="badge lvl-badge font-monospace px-2 py-1">
                        Lvl {{ $lvl }}
                      </span>
                      @if(($lvl === 1 && ($slot['is_racial_slot'] ?? false)) || ($lvl === 1 && str_contains(strtolower($prof->race ?? ''), 'human')))
                        <div class="mt-1">
                          <span class="badge bg-label-info fw-bold" style="font-size: 0.65rem; padding: 2px 6px;">+Human</span>
                        </div>
                      @endif
                      @if(!empty($slot['has_class_bonus_slot']))
                        <div class="mt-1">
                          <span class="badge bg-label-warning fw-bold" style="font-size: 0.65rem; padding: 2px 6px;">+Bonus Feat</span>
                        </div>
                      @endif
                    </div>
                  </td>

                  <!-- FEAT ADI Column (Standard, Class Talents, Bonus Feats, Traits, Reserve) -->
                  <td>
                    @if($slot['all_active_feats']->isNotEmpty() || $slot['reserve_feats']->isNotEmpty())
                      <div class="d-flex flex-column gap-1">
                        @foreach($slot['all_active_feats'] as $feat)
                          <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded bg-dark bg-opacity-60 border {{ in_array($feat->slot_type, ['class_talent', 'class_bonus']) || $feat->is_class_feat ? 'border-success border-opacity-30' : 'border-secondary border-opacity-25' }}">
                            <div>
                              <span class="fw-bold {{ in_array($feat->slot_type, ['class_talent', 'class_bonus']) || $feat->is_class_feat ? 'text-success' : 'text-heading' }}" style="font-size: 0.94rem;">{{ $feat->feat_name }}</span>
                              @if($feat->slot_type === 'class_talent')
                                <span class="badge bg-label-success ms-1">Sınıf Yeteneği ({{ $feat->category ?: 'Talent' }})</span>
                              @elseif($feat->slot_type === 'class_bonus')
                                <span class="badge bg-label-warning ms-1">Sınıf Bonusu</span>
                              @elseif($feat->slot_type === 'racial_bonus')
                                <span class="badge bg-label-info ms-1">İnsan Bonusu</span>
                              @elseif($feat->slot_type === 'trait')
                                <span class="badge bg-label-secondary ms-1">Trait</span>
                              @else
                                <span class="badge bg-label-primary ms-1">Normal Feat</span>
                              @endif
                              @if($feat->prerequisites)
                                <small class="text-body-secondary d-block mt-0" style="font-size: 0.75rem;">
                                  {{ __("Prerequisite:") }} {{ $feat->prerequisites }}
                                </small>
                              @endif
                              @if($feat->notes)
                                <small class="text-body-secondary d-block fst-italic" style="font-size: 0.73rem;">
                                  {{ $feat->notes }}
                                </small>
                              @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                              <button type="button" class="btn btn-xs btn-icon btn-text-secondary btn-row-action" title="{{ __("Edit") }}" onclick="openEditFeatModal({{ $feat->id }}, '{{ addslashes($feat->feat_name) }}', {{ $feat->level }}, '{{ $feat->slot_type }}', '{{ addslashes($feat->category ?? '') }}', '{{ addslashes($feat->prerequisites ?? '') }}', '{{ addslashes($feat->notes ?? '') }}', {{ $feat->is_class_feat ? 1 : 0 }})">
                                <i class="icon-base ti tabler-edit {{ in_array($feat->slot_type, ['class_talent', 'class_bonus']) || $feat->is_class_feat ? 'text-success' : 'text-primary' }}"></i>
                              </button>
                              <button type="button" class="btn btn-xs btn-icon btn-text-danger btn-row-action" title="Sil" onclick="deleteFeatConfirm({{ $feat->id }}, '{{ addslashes($feat->feat_name) }}')">
                                <i class="icon-base ti tabler-trash"></i>
                              </button>
                            </div>
                          </div>
                        @endforeach

                        @foreach($slot['reserve_feats'] as $feat)
                          <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded bg-dark bg-opacity-40 border border-secondary border-opacity-20">
                            <div>
                              <span class="text-warning fst-italic" style="font-size: 0.88rem;">{{ $feat->feat_name }}</span>
                              <span class="badge bg-label-secondary ms-1">{{ __('Planned / Prerequisite') }}</span>
                              @if($feat->notes)
                                <small class="text-muted d-block" style="font-size: 0.73rem;">{{ $feat->notes }}</small>
                              @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                              <button type="button" class="btn btn-xs btn-icon btn-text-secondary btn-row-action" title="{{ __("Edit") }}" onclick="openEditFeatModal({{ $feat->id }}, '{{ addslashes($feat->feat_name) }}', {{ $feat->level }}, '{{ $feat->slot_type }}', '{{ addslashes($feat->category ?? '') }}', '{{ addslashes($feat->prerequisites ?? '') }}', '{{ addslashes($feat->notes ?? '') }}', 0)">
                                <i class="icon-base ti tabler-edit text-warning"></i>
                              </button>
                              <button type="button" class="btn btn-xs btn-icon btn-text-danger btn-row-action" title="Sil" onclick="deleteFeatConfirm({{ $feat->id }}, '{{ addslashes($feat->feat_name) }}')">
                                <i class="icon-base ti tabler-trash"></i>
                              </button>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @else
                      <span class="text-body-secondary opacity-40">&mdash;</span>
                    @endif
                  </td>

                  <!-- EKLE Column -->
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary px-3" title="Feat Ekle (Seviye {{ $lvl }})" onclick="quickAddFeatToLevel({{ $lvl }})">
                      <i class="icon-base ti tabler-plus me-1"></i> Ekle
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>

  <!-- ====================================================
       4. VIEW 2: MASTER FEATS ARCHIVE TABLE (IMAGE 1 STYLE)
       ==================================================== -->
  <div id="viewTableContainer" class="{{ $activeView === 'table' ? '' : 'd-none' }}">
    <div class="card card-master-table-container shadow-sm mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3 py-3 px-4 bg-dark bg-opacity-50 border-bottom border-secondary border-opacity-25">
        <div>
          <h5 class="mb-0 fw-bold text-heading">
            <i class="icon-base ti tabler-table me-2 text-primary"></i>{{ __('Master Feats Archive Table') }}
          </h5>
          <small class="text-body-secondary">{{ __('Level-based feat breakdown for all guild heroes (Google Sheet format).') }}</small>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <!-- Quick Filter in Master Table -->
          <div class="input-group input-group-merge input-group-sm" style="width: 240px;">
            <span class="input-group-text"><i class="icon-base ti tabler-search text-muted"></i></span>
            <input type="text" class="form-control" id="masterTableSearchInput" placeholder="Tabloda ara..." onkeyup="filterMasterTable(this.value)" />
          </div>

          <span class="badge bg-label-primary font-monospace">{{ count($masterTable) }} Kahraman</span>
        </div>
      </div>

      <div class="table-responsive text-nowrap">
        <table class="table table-dark table-hover mb-0" id="masterSheetTable" style="font-size: 0.85rem;">
          <thead>
            <tr style="background: rgba(37, 41, 60, 0.95);">
              <th class="th-sortable" onclick="sortMasterTableByColumn(0)">{{ __("Rank") }} <i class="icon-base ti tabler-arrows-sort small text-muted"></i></th>
              <th class="th-sortable" onclick="sortMasterTableByColumn(1)">{{ __("Character Name") }} <i class="icon-base ti tabler-arrows-sort small text-muted"></i></th>
              <th class="th-sortable" onclick="sortMasterTableByColumn(2)">Oyuncu <i class="icon-base ti tabler-arrows-sort small text-muted"></i></th>
              <th class="th-sortable" onclick="sortMasterTableByColumn(3)">Irk <i class="icon-base ti tabler-arrows-sort small text-muted"></i></th>
              <th class="th-sortable" onclick="sortMasterTableByColumn(4)">{{ __("Class & Archetype") }} <i class="icon-base ti tabler-arrows-sort small text-muted"></i></th>
              <th class="th-sortable" onclick="sortMasterTableByColumn(5)">Seviye <i class="icon-base ti tabler-arrows-sort small text-muted"></i></th>
              <th>1. Level</th>
              <th>1. Level Class Bonus</th>
              <th>1. Level Racial Bonus</th>
              <th>2. Level Class Bonus / Ability</th>
              <th>3. Level</th>
              <th class="text-end">{{ __("Actions") }}</th>
            </tr>
          </thead>
          <tbody id="masterSheetTableBody">
            @foreach($masterTable as $row)
              <tr class="master-row" style="cursor: pointer;" onclick="switchCharacter('{{ addslashes($row['character_name']) }}')">
                <td>
                  <span class="badge {{ $row['rank'] === 'Uzman' ? 'bg-label-warning' : ($row['rank'] === 'Kıdemli' ? 'bg-label-primary' : 'bg-label-secondary') }}">
                    {{ $row['rank'] }}
                  </span>
                </td>
                <td class="fw-bold text-primary">{{ $row['character_name'] }}</td>
                <td>{{ $row['player_name'] }}</td>
                <td>{{ $row['race'] }}</td>
                <td class="text-info">{{ $row['class'] }}</td>
                <td><span class="badge bg-dark text-warning border border-warning">{{ $row['level'] }}</span></td>
                <td>{{ $row['lvl_1_standard'] }}</td>
                <td>{{ $row['lvl_1_class_bonus'] }}</td>
                <td>{{ $row['lvl_1_racial_bonus'] }}</td>
                <td>{{ $row['lvl_2_class_bonus'] }}</td>
                <td>{{ $row['lvl_3_standard'] }}</td>
                <td class="text-end" onclick="event.stopPropagation()">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="switchCharacter('{{ addslashes($row['character_name']) }}');" title="{{ __("View") }}">
                      <i class="icon-base ti tabler-eye"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-danger btn-icon" title="Karakteri Sil" onclick="deleteCharacterConfirm('{{ addslashes($row['character_name']) }}')">
                      <i class="icon-base ti tabler-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ====================================================
     5. MODAL: ADD / EDIT FEAT
     ==================================================== -->
<div class="modal fade" id="modalFeatAction" tabindex="-1" aria-labelledby="modalFeatActionTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border border-secondary border-opacity-50 shadow-lg">
      <div class="modal-header border-bottom border-secondary border-opacity-25 bg-dark">
        <h5 class="modal-title text-heading fw-bold" id="modalFeatActionTitle">
          <i class="icon-base ti tabler-award me-1 text-primary"></i> <span id="featModalModeText">{{ __('Add Feat') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formFeatAction" onsubmit="return false;">
        <input type="hidden" id="editFeatId" value="" />
        <div class="modal-body p-3 p-md-4">

          <!-- Character Name -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="featInputCharName">Karakter <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="featInputCharName" value="{{ $selectedChar }}" readonly />
          </div>

          <!-- Level & Slot Type -->
          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label fw-semibold" for="featInputLevel">{{ __('Feat Level') }} <span class="text-danger">*</span></label>
              <select class="form-select font-monospace" id="featInputLevel" required>
                @for($l = 1; $l <= 20; $l++)
                  <option value="{{ $l }}">Seviye {{ $l }}</option>
                @endfor
              </select>
            </div>
            <div class="col-8">
              <label class="form-label fw-semibold" for="featInputSlotType">{{ __('Slot / Rule Type') }} <span class="text-danger">*</span></label>
              <select class="form-select" id="featInputSlotType" required>
                <option value="standard">{{ __('Standard Feat') }} (Normal Feat: 1, 3, 5..)</option>
                <option value="racial_bonus">{{ __('Racial Bonus Feat') }} ({{ __('Human Bonus') }} vb.)</option>
                <option value="class_bonus">{{ __('Class Bonus Feat') }} (Fighter, Wizard, Monk, Warpriest)</option>
                <option value="class_talent">{{ __('Class Feats & Talents') }} (Rogue Talent, Magus Arcana, Discovery, Hex)</option>
                <option value="trait">Trait ({{ __('Character Trait - Additional Traits') }})</option>
                <option value="reserve">{{ __('Reserve / Planned') }} ({{ __('Planned / Prerequisite') }})</option>
              </select>
            </div>
          </div>

          <!-- Level 1 Special Slot Selector (Normal Feat + {{ __('Human Bonus') }} + Fighter/{{ __('Class Bonus') }}) -->
          <div id="modalLevel1Helper" class="mb-3 p-2 rounded bg-dark border border-primary border-opacity-35" style="display: none;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-primary small fw-bold text-uppercase" style="font-size: 0.75rem;">{{ __('Level 1 Slot Type:') }}</span>
              <span class="text-body-secondary small" style="font-size: 0.72rem;">Normal Feat + {{ __('Human Bonus') }} + {{ __('Class Bonus') }}</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button type="button" class="btn btn-xs btn-outline-primary" onclick="setModalSlotType('standard')">Normal Feat</button>
              <button type="button" class="btn btn-xs btn-outline-info" onclick="setModalSlotType('racial_bonus')">+ {{ __('Human Bonus') }}</button>
              <button type="button" class="btn btn-xs btn-outline-warning" onclick="setModalSlotType('class_bonus')">+ {{ __('Class Bonus') }} (Fighter / vb.)</button>
            </div>
          </div>

          <!-- Feat Name -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="featInputName">{{ __('Feat Name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="featInputName" placeholder="{{ __("e.g., Power Attack, Weapon Focus (Rapier)...") }}" list="commonFeatsList" required />
            <datalist id="commonFeatsList">
              @foreach($library as $lf)
                <option value="{{ $lf['name'] }}">{{ $lf['category'] }} &bull; {{ $lf['preq'] }}</option>
              @endforeach
            </datalist>
          </div>

          <!-- Category -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="featInputCategory">Kategori</label>
            <select class="form-select" id="featInputCategory">
              <option value="Combat">Combat</option>
              <option value="General">General (Genel)</option>
              <option value="Magic">Magic</option>
              <option value="Metamagic">Metamagic</option>
              <option value="Rogue Talent">Rogue Talent</option>
              <option value="Magus Arcana">Magus Arcana</option>
              <option value="Discovery">Discovery</option>
              <option value="Witch Hex">Witch Hex</option>
              <option value="Style">Style Feat</option>
              <option value="Teamwork">Teamwork</option>
              <option value="Item Creation">Item Creation</option>
              <option value="Trait">Trait</option>
            </select>
          </div>

          <!-- Prerequisites & Notes -->
          <div class="row g-2 mb-2">
            <div class="col-12">
              <label class="form-label fw-semibold" for="featInputPreq">{{ __('Prerequisites') }}</label>
              <input type="text" class="form-control" id="featInputPreq" placeholder="{{ __("e.g., Preq(Spell Focus(evocation), -) or Str 13, BAB +1") }}" />
            </div>
            <div class="col-12 mt-2">
              <label class="form-label fw-semibold" for="featInputNotes">{{ __('Description / Notes') }}</label>
              <input type="text" class="form-control" id="featInputNotes" placeholder="{{ __("Optional additional notes...") }}" />
            </div>
          </div>
        </div>

        <div class="modal-footer border-top border-secondary border-opacity-25 bg-dark">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="button" class="btn btn-primary fw-bold" id="btnSaveFeatSubmit">
            <i class="icon-base ti tabler-check me-1"></i> {{ __('Save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ====================================================
     6. MODAL: EDIT CHARACTER STATS
     ==================================================== -->
<div class="modal fade" id="modalEditStats" tabindex="-1" aria-labelledby="modalEditStatsTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border border-secondary border-opacity-50 shadow-lg">
      <div class="modal-header border-bottom border-secondary border-opacity-25 bg-dark">
        <h5 class="modal-title text-heading fw-bold" id="modalEditStatsTitle">
          <i class="icon-base ti tabler-user-cog me-1 text-primary"></i> {{ __('Edit Character Stats') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formEditStats" onsubmit="return false;">
        <div class="modal-body p-3 p-md-4">
          @if($dossier && $dossier['profile'])
            <input type="hidden" id="statsCharName" value="{{ $dossier['profile']->character_name }}" />

            <!-- Row 1: Race, Class, Archetype, Level, Rank -->
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label fw-semibold" for="statsRace">Irk</label>
                <input type="text" class="form-control" id="statsRace" value="{{ $dossier['profile']->race }}" required />
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold" for="statsClass">{{ __("Class") }}</label>
                <input type="text" class="form-control" id="statsClass" value="{{ $dossier['profile']->class_name }}" required />
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold" for="statsArchetype">Archetype</label>
                <input type="text" class="form-control" id="statsArchetype" value="{{ $dossier['profile']->archetype }}" />
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold" for="statsLevel">Seviye (1-20)</label>
                <input type="number" class="form-control" id="statsLevel" min="1" max="20" value="{{ $dossier['profile']->level }}" required />
              </div>
            </div>

            <!-- Row 2: Ability Scores -->
            <h6 class="text-primary fw-bold mb-2 border-bottom border-secondary border-opacity-25 pb-1">Temel Nitelikler (Ability Scores)</h6>
            <div class="row g-2 mb-3">
              <div class="col-4 col-md-2">
                <label class="form-label small fw-semibold" for="statsStr">STR</label>
                <input type="number" class="form-control text-center font-monospace" id="statsStr" min="1" max="40" value="{{ $dossier['profile']->str }}" />
              </div>
              <div class="col-4 col-md-2">
                <label class="form-label small fw-semibold" for="statsDex">DEX</label>
                <input type="number" class="form-control text-center font-monospace" id="statsDex" min="1" max="40" value="{{ $dossier['profile']->dex }}" />
              </div>
              <div class="col-4 col-md-2">
                <label class="form-label small fw-semibold" for="statsCon">CON</label>
                <input type="number" class="form-control text-center font-monospace" id="statsCon" min="1" max="40" value="{{ $dossier['profile']->con }}" />
              </div>
              <div class="col-4 col-md-2">
                <label class="form-label small fw-semibold" for="statsInt">INT</label>
                <input type="number" class="form-control text-center font-monospace" id="statsInt" min="1" max="40" value="{{ $dossier['profile']->int }}" />
              </div>
              <div class="col-4 col-md-2">
                <label class="form-label small fw-semibold" for="statsWis">WIS</label>
                <input type="number" class="form-control text-center font-monospace" id="statsWis" min="1" max="40" value="{{ $dossier['profile']->wis }}" />
              </div>
              <div class="col-4 col-md-2">
                <label class="form-label small fw-semibold" for="statsCha">CHA</label>
                <input type="number" class="form-control text-center font-monospace" id="statsCha" min="1" max="40" value="{{ $dossier['profile']->cha }}" />
              </div>
            </div>

            <!-- Row 3: HP & Hit Die -->
            <div class="row g-3 mb-2">
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="statsHp">{{ __("Hit Points (HP)") }}</label>
                <input type="number" class="form-control font-monospace" id="statsHp" min="1" max="1000" value="{{ $dossier['profile']->hp ?? 60 }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="statsNotes">Karakter Notu</label>
                <input type="text" class="form-control" id="statsNotes" value="{{ $dossier['profile']->notes }}" />
              </div>
            </div>
          @endif
        </div>

        <div class="modal-footer border-top border-secondary border-opacity-25 bg-dark">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="button" class="btn btn-primary fw-bold" id="btnSaveStatsSubmit">
            <i class="icon-base ti tabler-check me-1"></i> Nitelikleri Kaydet
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ====================================================
     7. MODAL: TRAIT CATALOG (1,346 TRAIT)
     ==================================================== -->
<div class="modal fade" id="modalTraitCatalog" tabindex="-1" aria-labelledby="modalTraitCatalogTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center gap-2">
          <span class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4);">
            <i class="icon-base ti tabler-sparkles fs-4" style="color: #f59e0b;"></i>
          </span>
          <div>
            <h5 class="modal-title mb-0" id="modalTraitCatalogTitle">
              {{ __('Search from Pathfinder Trait Catalog (1,346 Traits)') }}
            </h5>
            <div class="d-flex align-items-center gap-2 mt-1" id="traitModalSlotInfo">
              <span class="small" style="color: #8b949e;">Hedef Yuva:</span>
              <span class="badge font-monospace px-2 py-0" id="traitModalSlotBadge" style="background: #f59e0b; color: #000; font-weight: 700; font-size: 0.72rem;">{{ __("Member Trait") }}</span>
            </div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3 p-md-4">
        <!-- Live Search Input -->
        <div class="input-group input-group-merge mb-3 shadow-sm trait-search-wrapper">
          <span class="input-group-text trait-search-addon"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control font-monospace"
                 id="traitCatalogSearchInput"
                 placeholder="{{ __("Search by trait name, category or description (e.g. Reactionary, Anatomist, Magical Lineage...)") }}"
                 autocomplete="off" />
          <button type="button" class="btn trait-search-btn" id="btnTriggerTraitSearch">
            <i class="icon-base ti tabler-search me-1"></i> Ara
          </button>
        </div>

        <!-- Category Filter Pills -->
        <div class="d-flex align-items-center gap-1 flex-wrap mb-3" id="traitCategoryPills">
          <button type="button" class="btn btn-xs btn-trait-cat-filter active" data-cat="all">Hepsi</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Combat">Combat</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Magic">Magic</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Faith">Faith</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Social">Social</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Regional">Regional</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Race">Race</button>
          <button type="button" class="btn btn-xs btn-trait-cat-filter" data-cat="Equipment">Equipment</button>
        </div>

        <!-- Search Results List -->
        <div class="pf-scroll" style="max-height: 520px; overflow-y: auto; padding-right: 4px;" id="traitSearchResultsContainer">
          <div class="text-center py-4 font-monospace" style="color: #8b949e;">
            <span class="spinner-border spinner-border-sm me-2" style="color: #f59e0b;"></span>{{ __('Loading catalog...') }}
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex align-items-center justify-content-between p-3">
        <span class="trait-footer-note font-monospace">
          {{ __('* Traits are subject to Pathfinder 1E d20pfsrd rules.') }}
        </span>
        <button type="button" class="btn btn-sm trait-btn-close" data-bs-dismiss="modal">{{ __('Kapat') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // Switch Character in dropdown
  window.switchCharacter = function(charName) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('character', charName);
    window.location.href = currentUrl.toString();
  };

  // Change Toolbar Sorting
  window.changeSorting = function(sortBy) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', sortBy);
    window.location.href = currentUrl.toString();
  };

  // Switch View Mode (Dossier vs Table)
  window.switchViewMode = function(mode) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('view', mode);
    window.history.pushState({}, '', currentUrl);

    if (mode === 'dossier') {
      document.getElementById('viewDossierContainer').classList.remove('d-none');
      document.getElementById('viewTableContainer').classList.add('d-none');
      document.getElementById('btnViewDossier').classList.add('active');
      document.getElementById('btnViewTable').classList.remove('active');
    } else {
      document.getElementById('viewDossierContainer').classList.add('d-none');
      document.getElementById('viewTableContainer').classList.remove('d-none');
      document.getElementById('btnViewDossier').classList.remove('active');
      document.getElementById('btnViewTable').classList.add('active');
    }
  };

  // Filter Level Progression in Dossier Table
  // Filter Level Progression in Dossier Table
  window.filterProgression = function(type, pillEl) {
    document.querySelectorAll('.prog-filter-pill').forEach(p => p.classList.remove('active'));
    pillEl.classList.add('active');

    const rows = document.querySelectorAll('.prog-row');
    rows.forEach(row => {
      const hasFeats = row.dataset.hasFeats === '1';
      const isActiveLvl = row.classList.contains('row-active-level');

      if (type === 'all') {
        row.style.display = '';
      } else if (type === 'populated') {
        row.style.display = hasFeats ? '' : 'none';
      } else if (type === 'current') {
        row.style.display = isActiveLvl ? '' : 'none';
      }
    });
  };

  // Toggle Level Progression Sort Order (1->20 vs 20->1)
  let levelAsc = true;
  window.toggleProgressionSort = function() {
    levelAsc = !levelAsc;
    const label = document.getElementById('sortDirectionLabel');
    if (label) {
      label.textContent = levelAsc ? '1 → 20' : '20 → 1';
    }

    const tbody = document.getElementById('progressionTableBody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('.prog-row'));
    rows.sort((a, b) => {
      const lvlA = parseInt(a.dataset.level);
      const lvlB = parseInt(b.dataset.level);
      return levelAsc ? (lvlA - lvlB) : (lvlB - lvlA);
    });

    rows.forEach(r => tbody.appendChild(r));
  };

  // Live Filter for Master Table
  window.filterMasterTable = function(query) {
    const term = query.toLowerCase().trim();
    const rows = document.querySelectorAll('.master-row');
    rows.forEach(row => {
      const text = row.innerText.toLowerCase();
      row.style.display = (!term || text.includes(term)) ? '' : 'none';
    });
  };

  // Sort Master Table by Column Header Click
  let currentSortCol = -1;
  let currentSortAsc = true;
  window.sortMasterTableByColumn = function(colIndex) {
    const table = document.getElementById('masterSheetTable');
    if (!table) return;
    const tbody = document.getElementById('masterSheetTableBody');
    const rows = Array.from(tbody.querySelectorAll('.master-row'));

    if (currentSortCol === colIndex) {
      currentSortAsc = !currentSortAsc;
    } else {
      currentSortCol = colIndex;
      currentSortAsc = true;
    }

    rows.sort((a, b) => {
      const cellA = a.cells[colIndex]?.innerText.trim() || '';
      const cellB = b.cells[colIndex]?.innerText.trim() || '';

      const numA = parseFloat(cellA.replace(/[^0-9.-]+/g, ''));
      const numB = parseFloat(cellB.replace(/[^0-9.-]+/g, ''));

      if (!isNaN(numA) && !isNaN(numB) && cellA.match(/^[0-9]+$/)) {
        return currentSortAsc ? (numA - numB) : (numB - numA);
      }
      return currentSortAsc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
    });

    rows.forEach(r => tbody.appendChild(r));
  };

  // Delete Character Confirm
  window.deleteCharacterConfirm = function(charName) {
    Swal.fire({
      title: 'Karakteri Sil?',
      text: `"${charName}" {{ __("and all registered feats for this character will be permanently deleted!") }}`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Evet, Karakteri Sil',
      cancelButtonText: '{{ __('Cancel') }}',
      customClass: {
        confirmButton: 'btn btn-danger me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        fetch(`{{ url('/valiant-shade/pages/featntrait/character/delete') }}`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ character_name: charName })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Silindi!',
              text: data.message,
              timer: 1400,
              showConfirmButton: false
            }).then(() => {
              if (data.next_character) {
                switchCharacter(data.next_character);
              } else {
                location.reload();
              }
            });
          } else {
            Swal.fire({ icon: 'error', title: 'Hata', text: data.message });
          }
        })
        .catch(() => {
          Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: '{{ __('A network error occurred.') }}' });
        });
      }
    });
  };

  // Modals
  const featModalEl = document.getElementById('modalFeatAction');
  const featModal = featModalEl ? new bootstrap.Modal(featModalEl) : null;

  const statsModalEl = document.getElementById('modalEditStats');
  const statsModal = statsModalEl ? new bootstrap.Modal(statsModalEl) : null;

  function updateLevel1Helper(lvl) {
    const helper = document.getElementById('modalLevel1Helper');
    if (helper) {
      helper.style.display = (parseInt(lvl) === 1) ? 'block' : 'none';
    }
  }

  window.setModalSlotType = function(slot) {
    const slotEl = document.getElementById('featInputSlotType');
    if (slotEl) slotEl.value = slot;
  };

  const featInputLevelEl = document.getElementById('featInputLevel');
  if (featInputLevelEl) {
    featInputLevelEl.addEventListener('change', function () {
      updateLevel1Helper(this.value);
    });
  }

  // Open Add Feat Modal
  const btnOpenAddFeat = document.getElementById('btnOpenAddFeatModal');
  if (btnOpenAddFeat) {
    btnOpenAddFeat.addEventListener('click', function () {
      document.getElementById('editFeatId').value = '';
      document.getElementById('featModalModeText').textContent = "{{ __('Add Feat') }}";
      document.getElementById('featInputName').value = '';
      document.getElementById('featInputPreq').value = '';
      document.getElementById('featInputNotes').value = '';
      document.getElementById('featInputSlotType').value = 'standard';
      document.getElementById('featInputCategory').value = 'Combat';
      updateLevel1Helper(document.getElementById('featInputLevel').value);
      featModal.show();
    });
  }

  // Quick Add for specific level
  window.quickAddFeatToLevel = function(level) {
    document.getElementById('editFeatId').value = '';
    document.getElementById('featModalModeText').textContent = `{{ __("Add Feat for Level") }} ${level}`;
    document.getElementById('featInputLevel').value = level;
    document.getElementById('featInputName').value = '';
    document.getElementById('featInputPreq').value = '';
    document.getElementById('featInputNotes').value = '';
    document.getElementById('featInputSlotType').value = 'standard';
    updateLevel1Helper(level);
    featModal.show();
  };

  // Open Edit Feat Modal
  window.openEditFeatModal = function(id, name, level, slotType, category, preq, notes, isClassFeat) {
    document.getElementById('editFeatId').value = id;
    document.getElementById('featModalModeText').textContent = "{{ __('Edit Feat') }}";
    document.getElementById('featInputLevel').value = level;
    document.getElementById('featInputSlotType').value = slotType;
    document.getElementById('featInputName').value = name;
    document.getElementById('featInputCategory').value = category || 'General';
    document.getElementById('featInputPreq').value = preq || '';
    document.getElementById('featInputNotes').value = notes || '';
    updateLevel1Helper(level);
    featModal.show();
  };

  // Save Feat Submit
  const btnSaveFeat = document.getElementById('btnSaveFeatSubmit');
  if (btnSaveFeat) {
    btnSaveFeat.addEventListener('click', function () {
      const featId = document.getElementById('editFeatId').value;
      const charName = document.getElementById('featInputCharName').value;
      const level = parseInt(document.getElementById('featInputLevel').value);
      const slotType = document.getElementById('featInputSlotType').value;
      const featName = document.getElementById('featInputName').value.trim();
      const category = document.getElementById('featInputCategory').value;
      const preq = document.getElementById('featInputPreq').value.trim();
      const notes = document.getElementById('featInputNotes').value.trim();

      if (!featName) {
        Swal.fire({ icon: 'warning', title: '{{ __('Warning') }}', text: '{{ __('Please enter a Feat name.') }}' });
        return;
      }

      btnSaveFeat.disabled = true;

      const payload = {
        character_name: charName,
        level: level,
        slot_type: slotType,
        feat_name: featName,
        category: category,
        prerequisites: preq,
        notes: notes,
        is_class_feat: ['class_bonus', 'class_talent'].includes(slotType),
      };

      const url = featId 
        ? `{{ url('/valiant-shade/pages/featntrait') }}/${featId}`
        : `{{ url('/valiant-shade/pages/featntrait') }}`;
      const method = featId ? 'PUT' : 'POST';

      fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btnSaveFeat.disabled = false;
        if (data.success) {
          featModal.hide();
          Swal.fire({
            icon: 'success',
            title: '{{ __('Success!') }}',
            text: data.message,
            timer: 1200,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: data.message || '{{ __('An error occurred during save.') }}' });
        }
      })
      .catch(err => {
        btnSaveFeat.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __('Connection Error') }}', text: '{{ __('Could not reach the server.') }}' });
      });
    });
  }

  // Delete Feat Confirm
  window.deleteFeatConfirm = function(id, name) {
    Swal.fire({
      title: 'Feat Silinsin mi?',
      text: `"${name}" {{ __("will be removed from this character's progression!") }}`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Evet, Sil',
      cancelButtonText: '{{ __('Cancel') }}',
      customClass: {
        confirmButton: 'btn btn-danger me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        fetch(`{{ url('/valiant-shade/pages/featntrait') }}/${id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
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
              timer: 1200,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            Swal.fire({ icon: 'error', title: 'Hata', text: data.message });
          }
        })
        .catch(() => {
          Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: '{{ __('A network error occurred.') }}' });
        });
      }
    });
  };

  // Open Edit Stats Modal
  const btnOpenEditStats = document.getElementById('btnOpenEditStatsModal');
  if (btnOpenEditStats && statsModal) {
    btnOpenEditStats.addEventListener('click', function () {
      statsModal.show();
    });
  }

  // Save Stats Submit
  const btnSaveStats = document.getElementById('btnSaveStatsSubmit');
  if (btnSaveStats) {
    btnSaveStats.addEventListener('click', function () {
      const charName = document.getElementById('statsCharName').value;
      const race = document.getElementById('statsRace').value.trim();
      const className = document.getElementById('statsClass').value.trim();
      const archetype = document.getElementById('statsArchetype').value.trim();
      const level = parseInt(document.getElementById('statsLevel').value);
      const str = parseInt(document.getElementById('statsStr').value);
      const dex = parseInt(document.getElementById('statsDex').value);
      const con = parseInt(document.getElementById('statsCon').value);
      const int = parseInt(document.getElementById('statsInt').value);
      const wis = parseInt(document.getElementById('statsWis').value);
      const cha = parseInt(document.getElementById('statsCha').value);
      const hp = parseInt(document.getElementById('statsHp').value);
      const notes = document.getElementById('statsNotes').value.trim();

      btnSaveStats.disabled = true;

      const payload = {
        character_name: charName,
        race: race,
        class_name: className,
        archetype: archetype,
        level: level,
        str: str,
        dex: dex,
        con: con,
        int: int,
        wis: wis,
        cha: cha,
        hp: hp,
        notes: notes,
      };

      fetch(`{{ url('/valiant-shade/pages/featntrait/stats') }}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btnSaveStats.disabled = false;
        if (data.success) {
          statsModal.hide();
          Swal.fire({
            icon: 'success',
            title: '{{ __('Attributes Updated!') }}',
            text: data.message,
            timer: 1200,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Hata', text: data.message });
        }
      })
      .catch(() => {
        btnSaveStats.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: '{{ __('Could not reach the server.') }}' });
      });
    });
  }

  // Reset to Excel / Master Sheet Defaults
  const btnResetFeats = document.getElementById('btnResetFeatsExcel');
  if (btnResetFeats) {
    btnResetFeats.addEventListener('click', function () {
      Swal.fire({
        title: '{{ __('Reset to Master Sheet Data?') }}',
        text: '{{ __('All character feats and "Magus Buckler" will be restored to original template data!') }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '{{ __('Yes, Reset') }}',
        cancelButtonText: '{{ __('Cancel') }}',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          fetch(`{{ url('/valiant-shade/pages/featntrait/reset-excel') }}`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            }
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: '{{ __('Reset Complete!') }}',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: 'Hata', text: data.message });
            }
          })
          .catch(() => {
            Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: '{{ __('A network error occurred.') }}' });
          });
        }
      });
    });
  }

  // -------------------------------------------------------------
  // Trait Catalog & Guild Rank Trait Progression Modal Logic
  // -------------------------------------------------------------
  const traitModalEl = document.getElementById('modalTraitCatalog');
  const traitModal = traitModalEl ? new bootstrap.Modal(traitModalEl) : null;
  const traitSearchInput = document.getElementById('traitCatalogSearchInput');
  const traitResultsContainer = document.getElementById('traitSearchResultsContainer');
  let activeTraitSlot = null;
  let activeTraitChar = null;
  let activeCategoryFilter = 'all';

  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Open Trait Modal
  document.querySelectorAll('.btn-open-trait-modal').forEach(btn => {
    btn.addEventListener('click', function () {
      activeTraitSlot = this.dataset.slot;
      activeTraitChar = this.dataset.char;
      const slotLabel = this.dataset.slotLabel || activeTraitSlot;
      const titleSpan = document.getElementById('traitModalSlotBadge');
      if (titleSpan) titleSpan.textContent = slotLabel;
      if (traitSearchInput) traitSearchInput.value = '';
      activeCategoryFilter = 'all';
      document.querySelectorAll('.btn-trait-cat-filter').forEach(b => b.classList.toggle('active', b.dataset.cat === 'all'));
      fetchTraits('');
      if (traitModal) traitModal.show();
    });
  });

  // Clear Trait
  document.querySelectorAll('.btn-clear-trait').forEach(btn => {
    btn.addEventListener('click', function () {
      const slot = this.dataset.slot;
      const charName = this.dataset.char;
      Swal.fire({
        title: '{{ __('Remove Trait?') }}',
        text: 'Bu slottaki trait temizlenecektir.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '{{ __('Yes, Remove') }}',
        cancelButtonText: '{{ __('Cancel') }}',
        customClass: {
          confirmButton: 'btn btn-danger me-2',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          fetch('{{ url("/valiant-shade/pages/traits/clear") }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ character_name: charName, slot_key: slot })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({ icon: 'success', title: 'Temizlendi', timer: 1000, showConfirmButton: false })
                .then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: 'Hata', text: data.message });
            }
          })
          .catch(() => {
            Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: '{{ __('A network error occurred.') }}' });
          });
        }
      });
    });
  });

  function fetchTraits(query = '') {
    if (!traitResultsContainer) return;
    traitResultsContainer.innerHTML = '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>{{ __('Searching catalog...') }}</div>';
    fetch(`{{ url("/valiant-shade/pages/traits/search") }}?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.traits) || data.traits.length === 0) {
          traitResultsContainer.innerHTML = '<div class="text-center py-4 text-muted">{{ __('No matching traits found.') }}</div>';
          return;
        }
        renderTraitResults(data.traits);
      })
      .catch(() => {
        traitResultsContainer.innerHTML = '<div class="text-center py-4 text-danger">{{ __('Error loading traits.') }}</div>';
      });
  }

  function renderTraitResults(traits) {
    let filtered = traits;
    if (activeCategoryFilter !== 'all') {
      filtered = traits.filter(t => (t.category || '').toLowerCase() === activeCategoryFilter.toLowerCase());
    }

    if (filtered.length === 0) {
      traitResultsContainer.innerHTML = '<div class="text-center py-4 font-monospace" style="color: #8b949e;">{{ __('No matching traits in this category.') }}</div>';
      return;
    }

    const catColorMap = {
      'combat': { bg: 'rgba(239, 68, 68, 0.18)', text: '#fca5a5', border: 'rgba(239, 68, 68, 0.45)' },
      'magic': { bg: 'rgba(99, 102, 241, 0.18)', text: '#c7d2fe', border: 'rgba(99, 102, 241, 0.45)' },
      'faith': { bg: 'rgba(245, 158, 11, 0.18)', text: '#fde68a', border: 'rgba(245, 158, 11, 0.45)' },
      'social': { bg: 'rgba(6, 182, 212, 0.18)', text: '#a5f3fc', border: 'rgba(6, 182, 212, 0.45)' },
      'regional': { bg: 'rgba(16, 185, 129, 0.18)', text: '#a7f3d0', border: 'rgba(16, 185, 129, 0.45)' },
      'race': { bg: 'rgba(251, 146, 60, 0.18)', text: '#fed7aa', border: 'rgba(251, 146, 60, 0.45)' },
      'equipment': { bg: 'rgba(148, 163, 184, 0.18)', text: '#e2e8f0', border: 'rgba(148, 163, 184, 0.45)' },
      'campaign': { bg: 'rgba(168, 85, 247, 0.18)', text: '#e9d5ff', border: 'rgba(168, 85, 247, 0.45)' },
    };

    traitResultsContainer.innerHTML = filtered.map(t => {
      const name = escapeHtml(t.name);
      const cat = escapeHtml(t.category || 'General');
      const desc = escapeHtml(t.description || '');
      const url = t.url ? escapeHtml(t.url) : '';
      const catStyle = catColorMap[cat.toLowerCase()] || { bg: 'rgba(148, 163, 184, 0.18)', text: '#e2e8f0', border: 'rgba(148, 163, 184, 0.45)' };

      return `
        <div class="trait-card-item p-3 mb-2 rounded-3 d-flex align-items-center justify-content-between gap-3 flex-wrap flex-sm-nowrap">
          <div class="flex-grow-1" style="min-width: 0;">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span class="trait-card-title">${name}</span>
              <span class="badge py-1 px-2 font-monospace" style="background: ${catStyle.bg}; color: ${catStyle.text}; border: 1px solid ${catStyle.border}; font-size: 0.72rem; font-weight: 600;">
                ${cat}
              </span>
              ${url ? `<a href="${url}" target="_blank" class="trait-card-extlink" title="{{ __("d20pfsrd Rules & Source Page") }}"><i class="icon-base ti tabler-external-link"></i></a>` : ''}
            </div>
            ${desc ? `<p class="trait-card-desc mb-0">${desc}</p>` : (url ? `<a href="${url}" target="_blank" class="trait-card-urllink"><i class="icon-base ti tabler-book-2 me-1"></i>{{ __('inspect d20pfsrd rule & details page &rarr;') }}</a>` : '')}
          </div>
          <button type="button" class="btn btn-sm btn-select-trait-item trait-btn-select flex-shrink-0"
                  data-name="${name}"
                  data-category="${cat}"
                  data-desc="${desc}"
                  data-url="${url}">
            <i class="icon-base ti tabler-check me-1"></i> {{ __("Select") }}
          </button>
        </div>
      `;
    }).join('');

    // Bind item selection
    document.querySelectorAll('.btn-select-trait-item').forEach(btn => {
      btn.onclick = function () {
        const payload = {
          character_name: activeTraitChar,
          slot_key: activeTraitSlot,
          trait_name: this.dataset.name,
          category: this.dataset.category,
          description: this.dataset.desc,
          source_url: this.dataset.url,
        };

        this.disabled = true;
        fetch('{{ url("/valiant-shade/pages/traits/save") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            if (traitModal) traitModal.hide();
            Swal.fire({
              icon: 'success',
              title: 'Trait Eklendi!',
              text: data.message,
              timer: 1200,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            this.disabled = false;
            Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Trait kaydedilemedi.' });
          }
        })
        .catch(() => {
          this.disabled = false;
          Swal.fire({ icon: 'error', title: '{{ __('Error') }}', text: '{{ __('Could not reach the server.') }}' });
        });
      };
    });
  }

  if (traitSearchInput) {
    let searchTimeout;
    traitSearchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchTraits(this.value.trim());
      }, 300);
    });
  }

  const btnTriggerTraitSearch = document.getElementById('btnTriggerTraitSearch');
  if (btnTriggerTraitSearch && traitSearchInput) {
    btnTriggerTraitSearch.addEventListener('click', function () {
      fetchTraits(traitSearchInput.value.trim());
    });
  }

  document.querySelectorAll('.btn-trait-cat-filter').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.btn-trait-cat-filter').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      activeCategoryFilter = this.dataset.cat;
      fetchTraits(traitSearchInput ? traitSearchInput.value.trim() : '');
    });
  });
});
</script>
@endsection
