@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Guild Quest Board - Valiant Shade'))

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* ================= MEDIEVAL TAVERN QUEST BOARD STYLES ================= */
  
  /* Dynamic CSS Variables for Real-time Customization */
  .medieval-board {
    --parchment-bg: #fbf5e6;
    --parchment-bg-gradient: linear-gradient(135deg, #fdf8eb 0%, #f4ebd2 100%);
    --parchment-text: #2c2011;
    --parchment-desc: #443220;
    --parchment-border: #d4c299;
    --parchment-meta: #4e3520;
    --uygunluk-label-color: #1a0e05;
    
    background: #231709;
    background-image: 
      radial-gradient(ellipse at 50% 0%, rgba(245, 180, 80, 0.15) 0%, transparent 70%),
      repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.15) 2px, transparent 2px, transparent 40px),
      linear-gradient(180deg, #2b1d0c 0%, #1c1206 100%);
    border: 12px solid #3d2914;
    border-radius: 16px;
    box-shadow: 
      inset 0 0 30px rgba(0, 0, 0, 0.8),
      0 12px 36px rgba(0, 0, 0, 0.5);
    position: relative;
    padding: 30px 24px;
    color: #e5d7ba;
    transition: all 0.3s ease;
  }

  /* Board iron corner brackets */
  .board-corner {
    position: absolute;
    width: 32px;
    height: 32px;
    border: 4px solid #7c5a31;
    z-index: 2;
    pointer-events: none;
  }
  .corner-tl { top: 6px; left: 6px; border-right: none; border-bottom: none; }
  .corner-tr { top: 6px; right: 6px; border-left: none; border-bottom: none; }
  .corner-bl { bottom: 6px; left: 6px; border-right: none; border-top: none; }
  .corner-br { bottom: 6px; right: 6px; border-left: none; border-top: none; }

  /* Board Header Banner */
  .medieval-banner {
    background: linear-gradient(180deg, #442a12 0%, #2f1b0a 100%);
    border: 2px solid #8c6333;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
    padding: 20px 24px;
    position: relative;
  }
  .medieval-banner::before, .medieval-banner::after {
    content: "⚔️";
    font-size: 1.6rem;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
  }
  .medieval-banner::before { left: 24px; }
  .medieval-banner::after { right: 24px; }

  /* 2. Authentic Parchment Paper Card */
  .parchment-card {
    background: var(--parchment-bg);
    background-image: 
      radial-gradient(circle at 50% 0%, rgba(220, 195, 145, 0.25) 0%, transparent 60%),
      var(--parchment-bg-gradient);
    color: var(--parchment-text);
    border: 1.5px solid var(--parchment-border);
    border-radius: 8px;
    box-shadow: 
      0 6px 18px rgba(0, 0, 0, 0.35),
      inset 0 0 16px rgba(180, 140, 80, 0.15);
    position: relative;
    padding: 24px 20px 18px 20px;
    transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.25s ease, background 0.3s ease, color 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
  }
  .parchment-card:hover {
    transform: translateY(-6px) rotate(-0.5deg);
    box-shadow: 
      0 14px 28px rgba(0, 0, 0, 0.5),
      inset 0 0 20px rgba(180, 140, 80, 0.25);
    z-index: 10;
  }

  .parchment-card .quest-title {
    color: var(--parchment-text) !important;
    font-size: 1.15rem;
    line-height: 1.35;
    margin-top: 4px;
    margin-bottom: 10px;
  }

  .parchment-card .quest-desc {
    color: var(--parchment-desc) !important;
    font-size: 0.87rem;
    line-height: 1.55;
    margin-bottom: 16px;
  }

  /* Iron Thumbtack / Pin */
  .iron-pin {
    width: 16px;
    height: 16px;
    background: radial-gradient(circle at 35% 35%, #9e9e9e, #3a3a3a 70%, #1a1a1a 100%);
    border-radius: 50%;
    box-shadow: 
      0 3px 6px rgba(0, 0, 0, 0.6),
      inset 0 1px 2px rgba(255, 255, 255, 0.6);
    position: absolute;
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 5;
  }
  .iron-pin::after {
    content: "";
    position: absolute;
    top: 14px;
    left: 6px;
    width: 4px;
    height: 6px;
    background: rgba(0, 0, 0, 0.4);
    filter: blur(1px);
    border-radius: 50%;
  }

  /* Wax Seal Badges */
  .wax-seal {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    position: absolute;
    top: 14px;
    right: 14px;
    border: 2px dashed rgba(255, 255, 255, 0.4);
  }
  .seal-red {
    background: radial-gradient(circle at 35% 35%, #d32f2f, #8b0000 85%);
    color: #ffcdd2;
  }
  .seal-blue {
    background: radial-gradient(circle at 35% 35%, #1976d2, #0d47a1 85%);
    color: #bbdefb;
  }
  .seal-gold {
    background: radial-gradient(circle at 35% 35%, #fbc02d, #b78103 85%);
    color: #fff9c4;
  }
  .seal-bronze {
    background: radial-gradient(circle at 35% 35%, #a1887f, #4e342e 85%);
    color: #efebe9;
  }

  /* Medieval Wax Stamp Labels */
  .stamp-tag {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 3px 10px;
    border-radius: 4px;
    border: 1px solid;
  }
  .stamp-main {
    color: #8b0000;
    border-color: #8b0000;
    background: rgba(139, 0, 0, 0.08);
  }
  .stamp-side {
    color: #5d4037;
    border-color: #5d4037;
    background: rgba(93, 64, 55, 0.08);
  }
  .stamp-recurring {
    color: #0d47a1;
    border-color: #0d47a1;
    background: rgba(13, 71, 161, 0.08);
  }
  .stamp-member {
    color: #4a148c;
    border-color: #4a148c;
    background: rgba(74, 20, 140, 0.08);
  }

  /* ================= 3. UNIFIED HIGH-CONTRAST METADATA STYLES ================= */
  .meta-label, .uygunluk-title {
    color: var(--uygunluk-label-color, #1a0e05) !important;
    font-weight: 800 !important;
    font-size: 0.82rem !important;
    letter-spacing: 0.3px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
  }

  /* Universal Meta Badge Base: Guarantees EXACT SAME HEIGHT, PADDING & TYPOGRAPHY */
  .badge-meta {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    font-family: var(--bs-font-monospace, monospace);
    font-size: 0.78rem;
    font-weight: 800;
    border-radius: 6px;
    letter-spacing: 0.4px;
    line-height: 1.25;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.22);
    border: 1px solid;
    transition: all 0.2s ease;
  }

  .badge-eligibility {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    font-family: var(--bs-font-monospace, monospace);
    font-size: 0.78rem;
    font-weight: 800;
    border-radius: 6px;
    letter-spacing: 0.4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(0, 0, 0, 0.3);
    line-height: 1.25;
    transition: all 0.2s ease;
  }

  /* Member Badge (Top Header & Metadata Strip) */
  .badge-member-tag, .badge-meta-character {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    font-family: var(--bs-font-monospace, monospace);
    font-size: 0.78rem;
    font-weight: 800;
    border-radius: 6px;
    letter-spacing: 0.4px;
    line-height: 1.25;
    background-color: #2b180d !important; /* Deep Warm Leather */
    color: #ffca28 !important;            /* Luminous Amber/Gold */
    border: 1px solid #8d6e63 !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  }

  /* Location Meta Badge */
  .badge-meta-location {
    background-color: #1a2a3a !important; /* Cartographer Midnight Slate */
    color: #e2e8f0 !important;            /* Clean Silvery Ice */
    border: 1px solid #3b597b !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  }

  /* Reward Meta Badge */
  .badge-meta-reward {
    background-color: #0f391b !important; /* Royal Bounty Forest Emerald */
    color: #86efac !important;            /* Luminous Mint Gold */
    border: 1px solid #166534 !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  }

  /* Rank-Specific High-Contrast Colors */
  .badge-elig-hepsi {
    background-color: #d84315 !important; /* Deep Vibrant Amber/Rust */
    color: #ffffff !important;
    border-color: #bf360c !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }

  .badge-elig-candidate {
    background-color: #00695c !important; /* Deep Forest Teal */
    color: #ffffff !important;
    border-color: #004d40 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }

  .badge-elig-member {
    background-color: #1565c0 !important; /* Royal Sapphire Blue */
    color: #ffffff !important;
    border-color: #0d47a1 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }

  .badge-elig-senior {
    background-color: #880e4f !important; /* Noble Dark Crimson */
    color: #ffffff !important;
    border-color: #4a148c !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }

  .badge-elig-expert {
    background-color: #4a148c !important; /* Imperial Amethyst */
    color: #ffffff !important;
    border: 1.5px solid #ffd54f !important; /* Gold trim */
    box-shadow: 0 2px 8px rgba(74, 20, 140, 0.5), 0 0 4px rgba(255, 213, 79, 0.6) !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  }

  .badge-elig-default {
    background-color: #37474f !important;
    color: #ffffff !important;
    border-color: #263238 !important;
  }

  /* Status Ink Badges */
  .badge-status-active {
    background: #2e7d32;
    color: #ffffff;
    font-size: 0.72rem;
    padding: 4px 10px;
    font-weight: 700;
  }
  .badge-status-onhold {
    background: #1565c0;
    color: #ffffff;
    font-size: 0.72rem;
    padding: 4px 10px;
    font-weight: 700;
  }
  .badge-status-completed {
    background: #c62828;
    color: #ffffff;
    font-size: 0.72rem;
    padding: 4px 10px;
    font-weight: 700;
  }
  .badge-status-cancelled {
    background: #616161;
    color: #ffffff;
    font-size: 0.72rem;
    padding: 4px 10px;
    font-weight: 700;
  }

  /* Parchment Torn Footer & Meta */
  .parchment-meta {
    border-top: 1px dashed rgba(140, 100, 50, 0.45);
    padding-top: 14px;
    margin-top: auto;
    font-size: 0.82rem;
    color: var(--parchment-meta);
  }

  /* Tavern Wooden Placards */
  .wooden-placard {
    background: linear-gradient(180deg, #442d17 0%, #2b1b0b 100%);
    border: 2px solid #6b4723;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    color: #f1e2c7;
    height: 100%;
  }

  /* Tab Navigation in Medieval Aesthetic */
  .medieval-tabs .nav-link {
    background: rgba(43, 27, 11, 0.75);
    border: 1px solid #6b4723;
    color: #dfceb1;
    border-radius: 30px;
    padding: 8px 18px;
    font-weight: 600;
    transition: all 0.2s ease;
  }
  .medieval-tabs .nav-link:hover {
    background: #533517;
    color: #fff;
    border-color: #9e6d38;
  }
  .medieval-tabs .nav-link.active {
    background: linear-gradient(180deg, #a66a2e 0%, #6e4014 100%);
    color: #ffffff;
    border-color: #d6984e;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
  }

  /* Search & Filter Inputs */
  .medieval-input {
    background: #1e1307 !important;
    border: 1px solid #6b4723 !important;
    color: #f4ebd2 !important;
  }
  .medieval-input:focus {
    border-color: #d6984e !important;
    box-shadow: 0 0 0 0.25rem rgba(166, 106, 46, 0.25) !important;
  }
  .medieval-input::placeholder {
    color: #947e62 !important;
  }

  /* Ledger Table View */
  .ledger-table {
    background: var(--parchment-bg);
    color: var(--parchment-text);
    border: 1px solid #c9b485;
    border-radius: 8px;
    overflow: hidden;
  }
  .ledger-table th {
    background: #442d17 !important;
    color: #f1e2c7 !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.78rem;
    letter-spacing: 0.5px;
    border-color: #5d3b1b !important;
  }
  .ledger-table td {
    border-color: #e5d5b3 !important;
    vertical-align: middle;
  }

  /* Theme preset cards */
  .preset-card {
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .preset-card:hover, .preset-card.active {
    border-color: #d6984e !important;
    transform: scale(1.02);
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'
], 'static/valiant-shade/build')
@endsection

@section('content')

<!-- ================= MEDIEVAL TAVERN NOTICE BOARD WRAPPER ================= -->
<div class="medieval-board mb-5" id="medievalBoardElement">
  <!-- Corner Brackets -->
  <div class="board-corner corner-tl"></div>
  <div class="board-corner corner-tr"></div>
  <div class="board-corner corner-bl"></div>
  <div class="board-corner corner-br"></div>

  <!-- Board Title Header Banner -->
  <div class="medieval-banner text-center mb-4">
    <div class="d-flex align-items-center justify-content-center gap-3">
      <i class="icon-base ti tabler-shield-half-filled fs-2 text-warning"></i>
      <div>
        <h2 class="mb-0 fw-bold font-monospace text-warning" style="letter-spacing: 1px; text-shadow: 0 2px 6px rgba(0,0,0,0.8);">
          {{ __('VALIANT SHADE GUILD QUEST BOARD') }}
        </h2>
        <p class="mb-0 small text-light-emphasis" style="color: #dfceb1 !important;">
        </p>
      </div>
      <i class="icon-base ti tabler-clipboard-list fs-2 text-warning"></i>
    </div>
  </div>

  <!-- Top Wooden Placards (Metrics & Demographics) -->
  <div class="row g-3 mb-4">
    <!-- Placard 1: Total Board Quests -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="wooden-placard d-flex align-items-center justify-content-between">
        <div>
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">{{ __('Notice Board Quests') }}</span>
          <h3 class="mb-0 fw-bold font-monospace text-warning mt-1" id="countTotalQuests">{{ $counts['total'] }}</h3>
          <small class="text-success" style="color: #74c374 !important;">{{ $counts['active'] }} {{ __('Active') }} &bull; {{ $counts['on_hold'] }} {{ __('On Hold') }}</small>
        </div>
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-label-warning shadow-sm">
            <i class="icon-base ti tabler-clipboard-text fs-3"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Placard 2: Main Quests -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="wooden-placard d-flex align-items-center justify-content-between">
        <div>
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">{{ __('Main Quest Decrees') }}</span>
          <h3 class="mb-0 fw-bold font-monospace text-danger mt-1" style="color: #ff8a80 !important;" id="countMainQuests">{{ $counts['main'] }}</h3>
          <small style="color: #cbb493 !important;">{{ __('Guild & Personal Quests') }}</small>
        </div>
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-label-danger shadow-sm">
            <i class="icon-base ti tabler-swords fs-3"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Placard 3: Side Quests -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="wooden-placard d-flex align-items-center justify-content-between">
        <div>
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">{{ __('Side Quests') }}</span>
          <h3 class="mb-0 fw-bold font-monospace text-info mt-1" style="color: #82b1ff !important;" id="countSideQuests">{{ $counts['side'] }}</h3>
          <small style="color: #cbb493 !important;">{{ __('Regional Notices & Hunts') }}</small>
        </div>
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-label-info shadow-sm">
            <i class="icon-base ti tabler-compass fs-3"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Placard 4: Recurring & Patrols -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="wooden-placard d-flex align-items-center justify-content-between">
        <div>
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">{{ __('Recurring Quests / Patrols') }}</span>
          <h3 class="mb-0 fw-bold font-monospace text-primary mt-1" style="color: #b388ff !important;" id="countRecurringQuests">{{ $counts['recurring'] }}</h3>
          <small style="color: #cbb493 !important;">{{ __('Dungeon & Highway Patrol') }}</small>
        </div>
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-label-primary shadow-sm">
            <i class="icon-base ti tabler-refresh fs-3"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Guild Ranks & Status Legend Bar (Columns K, L in Master Sheet) -->
  <div class="wooden-placard mb-4 py-3">
    <div class="row align-items-center g-3">
      <!-- Left: Ranks Breakdown -->
      <div class="col-12 col-lg-7">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-bold small text-warning me-1">
            <i class="icon-base ti tabler-users me-1"></i> {{ __('Guild Roster:') }}
          </span>
          @foreach($guildInfo['ranks'] as $r)
            <span class="badge {{ $r['badge'] }} rounded-pill font-monospace" title="{{ __($r['rank']) }} {{ __('Roster') }}">
              {{ __($r['rank']) }}: {{ $r['count'] }}
            </span>
          @endforeach
        </div>
      </div>
      <!-- Right: Master Sheet Color Legend -->
      <div class="col-12 col-lg-5">
        <div class="d-flex align-items-center justify-content-lg-end gap-3 flex-wrap small">
          <span class="fw-semibold text-light-emphasis" style="color: #cbb493 !important;">{{ __('Sign Legend:') }}</span>
          <span class="badge bg-danger rounded-pill">{{ __('Red: Completed') }}</span>
          <span class="badge bg-primary rounded-pill">{{ __('Blue: On Hold') }}</span>
          <span class="badge bg-secondary rounded-pill">{{ __('Gray: Cancelled') }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Board Controls & Filter Toolbar -->
  <div class="row g-3 align-items-center justify-content-between mb-4">
    <!-- Category Tabs -->
    <div class="col-12 col-xl-7">
      <ul class="nav nav-pills medieval-tabs flex-wrap gap-2" id="questCategoryTabs">
        <li class="nav-item">
          <a class="nav-link active cursor-pointer filter-category-btn" data-category="all">
            <i class="icon-base ti tabler-layout-grid me-1"></i> {{ __('All Notices') }} ({{ $counts['total'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="main">
            <i class="icon-base ti tabler-swords me-1"></i> {{ __('Main Quests') }} ({{ $counts['main'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="side">
            <i class="icon-base ti tabler-compass me-1"></i> {{ __('Side Quests') }} ({{ $counts['side'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="recurring">
            <i class="icon-base ti tabler-refresh me-1"></i> {{ __('Recurring Quests') }} ({{ $counts['recurring'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="character">
            <i class="icon-base ti tabler-user-check me-1"></i> {{ __('Member Requests') }} ({{ $counts['character'] }})
          </a>
        </li>
      </ul>
    </div>

    <!-- Actions, Appearance Settings & View Switcher -->
    <div class="col-12 col-xl-5">
      <div class="d-flex align-items-center justify-content-xl-end gap-2 flex-wrap">
        <!-- Appearance Customizer Button -->
        <button type="button" class="btn btn-outline-warning btn-sm rounded-pill shadow-xs" data-bs-toggle="modal" data-bs-target="#modalBoardAppearance" title="{{ __('Customize Parchment and Font Colors') }}">
          <i class="icon-base ti tabler-color-swatch me-1"></i> {{ __('Color Settings') }}
        </button>

        <!-- View Toggle Buttons -->
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-warning btn-sm active" id="btnViewParchment" title="{{ __('Board View') }}">
            <i class="icon-base ti tabler-pin me-1"></i> Pano
          </button>
          <button type="button" class="btn btn-outline-warning btn-sm" id="btnViewLedger" title="{{ __('Ledger Table') }}">
            <i class="icon-base ti tabler-table me-1"></i> Defter
          </button>
        </div>

        @if($isAdmin)
          <!-- Discord Settings Button -->
          <button type="button" class="btn btn-outline-warning btn-sm rounded-pill shadow-xs" data-bs-toggle="modal" data-bs-target="#modalDiscordSettings" title="{{ __('Discord Webhook Ferman Ayarları') }}">
            <i class="icon-base ti tabler-brand-discord me-1"></i> {{ __('Discord') }}
            @if(!empty($discordConfig['configured']) && !empty($discordConfig['enabled']))
              <span class="badge bg-success rounded-pill ms-1 p-1" style="font-size: 0.6rem;" title="{{ __('Discord Aktif') }}">●</span>
            @endif
          </button>

          <!-- Reset Defaults Button -->
          <button type="button" class="btn btn-outline-danger btn-sm rounded-pill shadow-xs" id="btnResetQuestDefaults" title="{{ __('Varsayılan Görev Listesine Sıfırla') }}">
            <i class="icon-base ti tabler-rotate me-1"></i> {{ __('Sıfırla') }}
          </button>

          <!-- Pin New Quest Button -->
          <button type="button" class="btn btn-warning btn-sm rounded-pill waves-effect shadow-xs fw-bold" data-bs-toggle="modal" data-bs-target="#modalPinQuest">
            <i class="icon-base ti tabler-plus me-1"></i> {{ __('Pin New Quest') }}
          </button>
        @endif
      </div>
    </div>
  </div>

  <!-- Search & Secondary Filters Bar -->
  <div class="wooden-placard mb-4 py-2 px-3">
    <div class="row g-2 align-items-center">
      <!-- Search Input -->
      <div class="col-12 col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text medieval-input border-end-0">
            <i class="icon-base ti tabler-search text-warning"></i>
          </span>
          <input type="text" class="form-control medieval-input border-start-0" id="questSearchInput" placeholder="{{ __('Search quest title, adventurer, or region...') }}">
        </div>
      </div>

      <!-- Eligibility Filter -->
      <div class="col-6 col-md-3">
        <select class="form-select form-select-sm medieval-input" id="filterEligibility">
          <option value="all">{{ __('All Eligibility Levels') }}</option>
          <option value="all_ranks">{{ __('All (All Ranks)') }}</option>
          <option value="candidate_member">{{ __('Candidate / Member') }}</option>
          <option value="member_senior">{{ __('Member / Senior') }}</option>
          <option value="senior_expert">{{ __('Senior / Expert') }}</option>
          <option value="expert">{{ __('Expert Only') }}</option>
        </select>
      </div>

      <!-- Status Filter -->
      <div class="col-6 col-md-3">
        <select class="form-select form-select-sm medieval-input" id="filterStatus">
          <option value="all">{{ __('All Statuses') }}</option>
          <option value="active">{{ __('Pinned on Board (Active)') }}</option>
          <option value="on_hold">{{ __('Suspended (On Hold)') }}</option>
          <option value="completed">{{ __('Quest Finished (Completed)') }}</option>
          <option value="cancelled">{{ __('Cancelled Quests') }}</option>
        </select>
      </div>

      <!-- Reset Filter Button -->
      <div class="col-12 col-md-1 text-end">
        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnResetFilters" title="Filtreleri Temizle">
          <i class="icon-base ti tabler-rotate"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- ================= VIEW 1: PINNED PARCHMENTS ON WOODEN BOARD ================= -->
  <div id="viewParchmentContainer">
    <div class="row g-4" id="questCardsGrid">
      @forelse($quests as $q)
        @php
          $isCharacterSpecific = !empty($q['character']) && $q['character'] !== 'Lonca';
          $sealClass = match($q['seal_color']) {
            'red' => 'seal-red',
            'blue' => 'seal-blue',
            'gold' => 'seal-gold',
            default => 'seal-bronze'
          };
          $sealIcon = match($q['category']) {
            'main' => '🐉',
            'side' => '📜',
            'recurring' => '🛡️',
            default => '⚔️'
          };
          $stampClass = match($q['category']) {
            'character' => 'stamp-member',
            'main' => 'stamp-main',
            'side' => 'stamp-side',
            default => 'stamp-recurring'
          };
          $statusBadge = match($q['status']) {
            'completed' => 'badge-status-completed',
            'on_hold' => 'badge-status-onhold',
            'cancelled' => 'badge-status-cancelled',
            default => 'badge-status-active'
          };
          $statusText = match($q['status']) {
            'completed' => __('Completed'),
            'on_hold' => __('On Hold'),
            'cancelled' => __('Cancelled'),
            default => __('Active')
          };

          // High Contrast Distinct Eligibility Badge Class
          $eligBadgeClass = match($q['eligibility_key'] ?? '') {
            'all_ranks' => 'badge-elig-hepsi',
            'candidate_member' => 'badge-elig-candidate',
            'member_senior' => 'badge-elig-member',
            'senior_expert' => 'badge-elig-senior',
            'expert' => 'badge-elig-expert',
            default => 'badge-elig-default'
          };
        @endphp

        <div class="col-12 col-md-6 col-xl-4 quest-card-item" 
             data-category="{{ $q['category'] }}" 
             data-eligibility="{{ $q['eligibility_key'] ?? $q['eligibility'] }}" 
             data-status="{{ $q['status'] }}"
             data-character="{{ $q['character'] ?? __('Guild') }}"
             data-title="{{ mb_strtolower($q['title']) }}">
          
          <div class="parchment-card">
            <!-- Iron Pushpin Tack -->
            <div class="iron-pin" title="{{ __('Pinned to Board') }}"></div>

            <!-- Top Stamp Header -->
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="stamp-tag {{ $stampClass }}">
                  {{ $q['category_label'] }}
                </span>
                @if($isCharacterSpecific)
                  <span class="badge-member-tag">
                    {{ $q['character'] }}
                  </span>
                @endif
              </div>
            </div>

            <!-- Quest Title -->
            <h5 class="fw-bold mb-2 quest-title">
              {{ $q['title'] }}
            </h5>

            <!-- Quest Lore / Short Description -->
            <p class="small mb-3 quest-desc">
              {{ $q['description'] }}
            </p>

            <!-- Metadata Strip -->
            <div class="parchment-meta">
              <div class="d-flex flex-column gap-2 mb-3">
                <!-- Row 1: High Contrast Eligibility & Target -->
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                  <div class="d-flex align-items-center gap-1.5">
                    <span class="meta-label">{{ __('Eligibility:') }}</span> 
                    <span class="badge-meta badge-eligibility {{ $eligBadgeClass }}">
                      {{ $q['eligibility'] }}
                    </span>
                  </div>
                  <div class="d-flex align-items-center gap-1.5">
                    <span class="meta-label">{{ __('Party:') }}</span> 
                    <span class="badge-meta badge-meta-character">
                      {{ $q['character'] ?? __('Guild') }}
                    </span>
                  </div>
                </div>

                <!-- Row 2: Location -->
                @if(!empty($q['location']))
                  <div class="d-flex align-items-center gap-1.5 text-truncate">
                    <span class="meta-label">{{ __('Location:') }}</span>
                    <span class="badge-meta badge-meta-location text-truncate" title="{{ $q['location'] }}">
                      <span>📍</span> <span class="text-truncate">{{ $q['location'] }}</span>
                    </span>
                  </div>
                @endif

                <!-- Row 3: Reward -->
                @if(!empty($q['reward']))
                  <div class="d-flex align-items-center gap-1.5 text-truncate">
                    <span class="meta-label">{{ __('Reward:') }}</span>
                    <span class="badge-meta badge-meta-reward text-truncate" title="{{ $q['reward'] }}">
                      <span>🏆</span> <span class="text-truncate">{{ $q['reward'] }}</span>
                    </span>
                  </div>
                @endif
              </div>

              <!-- Bottom Row: Status Badge & Admin / Inspect Actions -->
              <div class="d-flex justify-content-between align-items-center pt-2 mt-auto" style="border-top: 1px solid rgba(140, 100, 50, 0.25);">
                <div class="d-flex align-items-center gap-1.5">
                  <span class="badge {{ $statusBadge }} rounded-pill font-monospace">
                    {{ $statusText }}
                  </span>
                  @if($isAdmin)
                    <div class="dropdown d-inline-block">
                      <button class="btn btn-xs btn-outline-secondary rounded-pill p-1 lh-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Durumu Hızlı Değiştir') }}" style="font-size: 11px;">
                        <i class="icon-base ti tabler-chevron-down"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-dark shadow-sm py-1" style="font-size: 0.8rem; min-width: 130px;">
                        <li><h6 class="dropdown-header py-1 text-muted" style="font-size: 0.72rem;">{{ __('Durum Değiştir') }}</h6></li>
                        <li><a class="dropdown-item py-1 cursor-pointer btn-quick-status" data-id="{{ $q['id'] }}" data-status="active"><span class="badge bg-success me-1">●</span> {{ __('Aktif') }}</a></li>
                        <li><a class="dropdown-item py-1 cursor-pointer btn-quick-status" data-id="{{ $q['id'] }}" data-status="on_hold"><span class="badge bg-info me-1">●</span> {{ __('Askıda') }}</a></li>
                        <li><a class="dropdown-item py-1 cursor-pointer btn-quick-status" data-id="{{ $q['id'] }}" data-status="completed"><span class="badge bg-danger me-1">●</span> {{ __('Tamamlandı') }}</a></li>
                        <li><a class="dropdown-item py-1 cursor-pointer btn-quick-status" data-id="{{ $q['id'] }}" data-status="cancelled"><span class="badge bg-secondary me-1">●</span> {{ __('İptal') }}</a></li>
                      </ul>
                    </div>
                  @endif
                </div>

                <div class="d-flex align-items-center gap-1">
                  @if($isAdmin)
                    <!-- Discord Broadcast Button -->
                    <button type="button" class="btn btn-xs btn-outline-warning rounded-circle p-1 btn-broadcast-discord" 
                            data-id="{{ $q['id'] }}" data-title="{{ $q['title'] }}" 
                            title="{{ __('Discord\'a Ferman Duyur') }}" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                      <i class="icon-base ti tabler-brand-discord fs-6"></i>
                    </button>

                    <!-- Edit Quest Button -->
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-circle p-1 btn-edit-quest" 
                            data-id="{{ $q['id'] }}"
                            data-title="{{ $q['title'] }}"
                            data-category="{{ $q['category'] }}"
                            data-eligibility="{{ $q['eligibility_key'] ?? $q['eligibility'] }}"
                            data-character="{{ $q['character'] ?? '' }}"
                            data-location="{{ $q['location'] ?? '' }}"
                            data-reward="{{ $q['reward'] ?? '' }}"
                            data-status="{{ $q['status'] }}"
                            data-description="{{ $q['description'] }}"
                            title="{{ __('Fermanı Düzenle') }}" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                      <i class="icon-base ti tabler-pencil fs-6"></i>
                    </button>

                    <!-- Delete Quest Button -->
                    <button type="button" class="btn btn-xs btn-outline-danger rounded-circle p-1 btn-delete-quest" 
                            data-id="{{ $q['id'] }}" data-title="{{ $q['title'] }}"
                            title="{{ __('Panodan Kaldır') }}" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                      <i class="icon-base ti tabler-trash fs-6"></i>
                    </button>
                  @endif

                  <button type="button" class="btn btn-xs btn-outline-dark rounded-pill btn-inspect-quest fw-bold px-2.5 py-1"
                          data-id="{{ $q['id'] }}"
                          data-title="{{ $q['title'] }}"
                          data-category="{{ $q['category_label'] }}" data-category-key="{{ $q['category'] }}"
                          data-eligibility="{{ $q['eligibility_key'] ?? $q['eligibility'] }}"
                          data-character="{{ $q['character'] ?? __('Guild') }}"
                          data-location="{{ $q['location'] ?? __('Unknown') }}"
                          data-reward="{{ $q['reward'] ?? __('Standard Reward') }}"
                          data-status="{{ $q['status'] }}" data-status-label="{{ $statusText }}"
                          data-badge-class="{{ $eligBadgeClass }}"
                          data-description="{{ $q['description'] }}">
                    <i class="icon-base ti tabler-eye me-1"></i> {{ __('Inspect') }}
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>
      @empty
        <div class="col-12 text-center py-5">
          <i class="icon-base ti tabler-clipboard-off fs-1 text-warning mb-2 d-block"></i>
          <h5 class="text-warning font-monospace">{{ __('No Decrees Found Matching These Criteria') }}</h5>
          <p class="text-light-emphasis small">{{ __('Reset filters to view all guild quest notices.') }}</p>
        </div>
      @endforelse
    </div>
  </div>

  <!-- ================= VIEW 2: MEDIEVAL LEDGER TABLE VIEW ================= -->
  <div id="viewLedgerContainer" style="display: none;">
    <div class="table-responsive text-nowrap ledger-table shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 50px;" class="text-center">#</th>
            <th>{{ __('Quest Decree (Title)') }}</th>
            <th class="text-center">{{ __('Category') }}</th>
            <th class="text-center">{{ __('Eligible Rank') }}</th>
            <th>{{ __('Adventurer / Party') }}</th>
            <th>{{ __('Region / Location') }}</th>
            <th class="text-end">{{ __('Reward') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end">{{ __('Inspect') }}</th>
          </tr>
        </thead>
        <tbody id="ledgerTableBody">
          @foreach($quests as $idx => $q)
            @php
              $statusBadge = match($q['status']) {
                'completed' => 'badge-status-completed',
                'on_hold' => 'badge-status-onhold',
                'cancelled' => 'badge-status-cancelled',
                default => 'badge-status-active'
              };
              $statusText = match($q['status']) {
                'completed' => __('Completed'),
                'on_hold' => __('On Hold'),
                'cancelled' => __('Cancelled'),
                default => __('Active')
              };
              $eligBadgeClass = match($q['eligibility_key'] ?? '') {
                'all_ranks' => 'badge-elig-hepsi',
                'candidate_member' => 'badge-elig-candidate',
                'member_senior' => 'badge-elig-member',
                'senior_expert' => 'badge-elig-senior',
                'expert' => 'badge-elig-expert',
                default => 'badge-elig-default'
              };
            @endphp
            <tr class="ledger-row" 
                data-category="{{ $q['category'] }}" 
                data-eligibility="{{ $q['eligibility_key'] ?? $q['eligibility'] }}" 
                data-status="{{ $q['status'] }}"
                data-character="{{ $q['character'] ?? __('Guild') }}"
                data-title="{{ mb_strtolower($q['title']) }}">
              <td class="text-center font-monospace fw-bold text-body-secondary">
                {{ $idx + 1 }}
              </td>
              <td>
                <span class="fw-bold text-heading d-block">{{ $q['title'] }}</span>
                <small class="text-body-secondary text-truncate d-block" style="max-width: 250px;">{{ $q['description'] }}</small>
              </td>
              <td class="text-center">
                <span class="stamp-tag {{ match($q['category']) { 'character' => 'stamp-member', 'main' => 'stamp-main', 'side' => 'stamp-side', default => 'stamp-recurring' } }}">
                  {{ $q['category_label'] }}
                </span>
              </td>
              <td class="text-center font-monospace">
                <span class="badge-eligibility {{ $eligBadgeClass }}">{{ $q['eligibility'] }}</span>
              </td>
              <td>
                <span class="fw-semibold font-monospace">{{ $q['character'] ?? __('Guild') }}</span>
              </td>
              <td class="small text-body-secondary">
                📍 {{ $q['location'] ?? '—' }}
              </td>
              <td class="text-end font-monospace text-success fw-bold small">
                {{ $q['reward'] ?? '—' }}
              </td>
              <td class="text-center">
                <span class="badge {{ $statusBadge }} rounded-pill font-monospace">
                  {{ $statusText }}
                </span>
              </td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center gap-1">
                  @if($isAdmin)
                    <button type="button" class="btn btn-xs btn-outline-warning rounded-circle p-1 btn-broadcast-discord"
                            data-id="{{ $q['id'] }}" data-title="{{ $q['title'] }}" title="{{ __('Discord\'a Duyur') }}"
                            style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                      <i class="icon-base ti tabler-brand-discord fs-6"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-circle p-1 btn-edit-quest"
                            data-id="{{ $q['id'] }}"
                            data-title="{{ $q['title'] }}"
                            data-category="{{ $q['category'] }}"
                            data-eligibility="{{ $q['eligibility_key'] ?? $q['eligibility'] }}"
                            data-character="{{ $q['character'] ?? '' }}"
                            data-location="{{ $q['location'] ?? '' }}"
                            data-reward="{{ $q['reward'] ?? '' }}"
                            data-status="{{ $q['status'] }}"
                            data-description="{{ $q['description'] }}"
                            title="{{ __('Düzenle') }}" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                      <i class="icon-base ti tabler-pencil fs-6"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-danger rounded-circle p-1 btn-delete-quest"
                            data-id="{{ $q['id'] }}" data-title="{{ $q['title'] }}" title="{{ __('Kaldır') }}"
                            style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                      <i class="icon-base ti tabler-trash fs-6"></i>
                    </button>
                  @endif
                  <button type="button" class="btn btn-xs btn-outline-dark rounded-pill btn-inspect-quest"
                          data-id="{{ $q['id'] }}"
                          data-title="{{ $q['title'] }}"
                          data-category="{{ $q['category_label'] }}" data-category-key="{{ $q['category'] }}"
                          data-eligibility="{{ $q['eligibility_key'] ?? $q['eligibility'] }}"
                          data-character="{{ $q['character'] ?? __('Guild') }}"
                          data-location="{{ $q['location'] ?? __('Unknown') }}"
                          data-reward="{{ $q['reward'] ?? __('Standard Reward') }}"
                          data-status="{{ $q['status'] }}" data-status-label="{{ $statusText }}"
                          data-badge-class="{{ $eligBadgeClass }}"
                          data-description="{{ $q['description'] }}">
                    <i class="icon-base ti tabler-eye"></i>
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

<!-- ================= MODALS ================= -->

<!-- 1. Modal: Appearance & Colors Customizer -->
<div class="modal fade" id="modalBoardAppearance" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning shadow-lg">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2 mb-0">
          <i class="icon-base ti tabler-color-swatch text-warning fs-4"></i>
          <span>{{ __('Parchment & Font Color Settings') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Section 1: Ready-made Themes -->
        <h6 class="fw-bold mb-2">{{ __('Parchment Themes:') }}</h6>
        <div class="row g-2 mb-4">
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center active" id="presetClassic" data-theme="classic" style="background: #fbf5e6; color: #2c2011;">
              <small class="fw-bold d-block">📜 {{ __('Classic Parchment') }}</small>
              <span style="font-size: 0.72rem;" class="text-muted">{{ __('Warm Antique Tones') }}</span>
            </div>
          </div>
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center" id="presetIvory" data-theme="ivory" style="background: #ffffff; color: #111111;">
              <small class="fw-bold d-block">📜 {{ __('Light Ivory') }}</small>
              <span style="font-size: 0.72rem;" class="text-muted">{{ __('High Contrast & Crisp') }}</span>
            </div>
          </div>
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center" id="presetPapyrus" data-theme="papyrus" style="background: #f4e8c1; color: #1b1208;">
              <small class="fw-bold d-block">📜 {{ __('Ancient Papyrus') }}</small>
              <span style="font-size: 0.72rem;" class="text-muted">{{ __('Golden Desert Scroll') }}</span>
            </div>
          </div>
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center" id="presetDark" data-theme="dark" style="background: #24190e; color: #fdf5e2;">
              <small class="fw-bold d-block">📜 {{ __('Dark Dungeon') }}</small>
              <span style="font-size: 0.72rem; color: #d7c4a3;">{{ __('Night Leather Scroll') }}</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Live Custom Color Controls -->
        <h6 class="fw-bold mb-2">{{ __('Custom Color Pickers:') }}</h6>
        <div class="card border p-3 rounded bg-body mb-3">
          <!-- Parchment Background -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <span class="fw-semibold d-block small">{{ __('Parchment Background Color') }}</span>
              <small class="text-body-secondary" style="font-size: 0.74rem;">{{ __('Background of the quest parchment') }}</small>
            </div>
            <input type="color" class="form-control form-control-color" id="inputCustomBg" value="#fbf5e6" title="{{ __('Choose Background Color') }}">
          </div>

          <!-- General Text Color -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <span class="fw-semibold d-block small">{{ __('Heading and Text Color') }}</span>
              <small class="text-body-secondary" style="font-size: 0.74rem;">{{ __('Quest titles and lore descriptions') }}</small>
            </div>
            <input type="color" class="form-control form-control-color" id="inputCustomText" value="#2c2011" title="{{ __('Choose Text Color') }}">
          </div>

          <!-- "Uygunluk" Label Color -->
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="fw-semibold d-block small">"{{ __('Eligibility:') }}" {{ __('Text Color') }}</span>
              <small class="text-body-secondary" style="font-size: 0.74rem;">{{ __('Title to the left of the rank badge') }}</small>
            </div>
            <input type="color" class="form-control form-control-color" id="inputCustomUygunluk" value="#1a0e05" title="{{ __('Choose Eligibility Label Color') }}">
          </div>
        </div>

        <!-- Live Preview Box -->
        <div class="p-3 rounded border text-center" id="colorPreviewBox" style="background: #fbf5e6; color: #2c2011;">
          <small class="text-uppercase fw-bold text-muted d-block mb-1" style="font-size: 0.7rem;">{{ __('Live Preview') }}</small>
          <span class="uygunluk-title fw-bold" id="previewUygunlukLabel">{{ __('Eligibility:') }}</span>
          <span class="badge-eligibility badge-elig-senior ms-1">{{ __('Senior / Expert') }}</span>
          <p class="small mb-0 mt-1" id="previewDescText">{{ __('This quest decree will be rendered with your chosen color and contrast.') }}</p>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="btnResetToDefaults">
          <i class="icon-base ti tabler-rotate me-1"></i> {{ __('Reset to Defaults') }}
        </button>
        <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold" data-bs-dismiss="modal">
          <i class="icon-base ti tabler-check me-1"></i> {{ __('Keep Settings') }}
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 2. Modal: Inspect Quest Parchment -->
<div class="modal fade" id="modalInspectQuest" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--parchment-bg, #fbf5e6); border: 3px solid #7c5a31; color: var(--parchment-text, #2c2011);">
      <div class="modal-header border-bottom" style="border-color: rgba(140, 100, 50, 0.4) !important;">
        <div class="d-flex align-items-center gap-2">
          <span class="fs-4">📜</span>
          <h5 class="modal-title fw-bold mb-0" id="inspectQuestTitle" style="color: var(--parchment-text, #2c2011);">
            {{ __('Quest Decree') }}
          </h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Badge row -->
        <div class="d-flex align-items-center justify-content-between mb-3 pb-1">
          <span class="stamp-tag stamp-main" id="inspectQuestCategory">{{ __('Main Quest') }}</span>
          <span class="badge bg-success rounded-pill font-monospace px-3 py-1" id="inspectQuestStatus">{{ __('Pinned on Board') }}</span>
        </div>

        <!-- Description -->
        <div class="p-3 mb-3 rounded-3" style="background: rgba(140, 100, 50, 0.08); border: 1px dashed rgba(140, 100, 50, 0.4);">
          <h6 class="fw-bold mb-2 d-flex align-items-center gap-1" style="color: var(--parchment-text, #2c2011);">
            <span>📜</span> {{ __('Guild Notice:') }}
          </h6>
          <p class="mb-0 small" id="inspectQuestDesc" style="line-height: 1.65; color: var(--parchment-desc, #443220);">
            {{ __('Detailed quest notice will appear here.') }}
          </p>
        </div>

        <!-- Details Grid -->
        <div class="row g-3 small">
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">{{ __('Eligibility:') }}</span>
              <div>
                <span class="badge-eligibility badge-elig-senior" id="inspectQuestEligibility">{{ __('Senior / Expert') }}</span>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">{{ __('Assigned Adventurer:') }}</span>
              <div>
                <span class="badge bg-label-dark rounded-pill fw-bold font-monospace px-2.5 py-1" id="inspectQuestCharacter" style="font-size: 0.80rem; color: var(--parchment-text, #2c2011);">Lonca</span>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">{{ __('Region / Location:') }}</span>
              <span id="inspectQuestLocation" class="fw-medium" style="color: var(--parchment-desc, #443220);">{{ __('Unknown') }}</span>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">{{ __('Quest Reward:') }}</span>
              <span class="text-success fw-bold" id="inspectQuestReward">{{ __('Standard XP & Gold') }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-between gap-2 py-3 px-4" style="border-color: rgba(140, 100, 50, 0.35) !important;">
        <div>
          @if($isAdmin)
            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 shadow-xs" id="inspectBtnDiscord">
              <i class="icon-base ti tabler-brand-discord me-1"></i> {{ __('Discord\'a Duyur') }}
            </button>
          @endif
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3" data-bs-dismiss="modal">{{ __('Close') }}</button>
          <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 shadow-sm" onclick="Swal.fire('{{ __('Quest Claimed!') }}', '{{ __('You applied to the guild clerk to claim this quest.') }}', 'success');">
            <i class="icon-base ti tabler-send me-1"></i> {{ __('Claim Quest') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

@if($isAdmin)
  <!-- 3. Modal: Pin New Quest -->
  <div class="modal fade" id="modalPinQuest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-pin text-warning fs-4"></i>
            <span>{{ __('Pin New Quest Notice to Board') }}</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <form id="formPinQuest">
          @csrf
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-semibold" for="inputQuestTitle">{{ __('Quest Title') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputQuestTitle" required placeholder="{{ __('e.g., Mountain Dragon Lair Cleansing') }}">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestCategory">{{ __('Category') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="inputQuestCategory" required>
                  <option value="main">{{ __('Main Quest') }}</option>
                  <option value="side">{{ __('Side Quest') }}</option>
                  <option value="recurring">{{ __('Recurring Quests / Patrols') }}</option>
                  <option value="character">{{ __('Member Request') }}</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestEligibility">{{ __('Eligibility') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="inputQuestEligibility" required>
                  <option value="all_ranks">{{ __('All (All Ranks)') }}</option>
                  <option value="candidate_member">{{ __('Candidate / Member') }}</option>
                  <option value="member_senior">{{ __('Member / Senior') }}</option>
                  <option value="senior_expert">{{ __('Senior / Expert') }}</option>
                  <option value="expert">{{ __('Expert Only') }}</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestCharacter">{{ __('Assigned Adventurer') }}</label>
                <input type="text" class="form-control" id="inputQuestCharacter" value="{{ __('Guild') }}" placeholder="{{ __('Guild or Character Name') }}">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestLocation">{{ __('Location / Region') }}</label>
                <input type="text" class="form-control" id="inputQuestLocation" placeholder="{{ __('e.g., Northern Mist Mountains') }}">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="inputQuestReward">{{ __('Reward') }}</label>
              <input type="text" class="form-control" id="inputQuestReward" placeholder="{{ __('e.g., 10,000 XP & 500 SP') }}">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="inputQuestDesc">{{ __('Quest Notice & Details') }} <span class="text-danger">*</span></label>
              <textarea class="form-control" id="inputQuestDesc" rows="3" required placeholder="{{ __('Quest objectives, hazards, and expectations from adventurers...') }}"></textarea>
            </div>

            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="inputQuestSendDiscord" checked>
              <label class="form-check-label fw-semibold text-warning small" for="inputQuestSendDiscord">
                <i class="icon-base ti tabler-brand-discord me-1"></i> {{ __('Bu fermanı Discord lonca kanalına otomatik duyur') }}
              </label>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-warning rounded-pill fw-bold" id="btnSubmitPinQuest">
              <i class="icon-base ti tabler-pin me-1"></i> {{ __('Seal & Pin to Board') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 4. Modal: Edit Quest Decree -->
  <div class="modal fade" id="modalEditQuest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-pencil text-primary fs-4"></i>
            <span>{{ __('Edit Quest Decree') }}</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <form id="formEditQuest">
          @csrf
          <input type="hidden" id="editQuestId">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-semibold" for="editQuestTitle">{{ __('Quest Title') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="editQuestTitle" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="editQuestCategory">{{ __('Category') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="editQuestCategory" required>
                  <option value="main">{{ __('Main Quest') }}</option>
                  <option value="side">{{ __('Side Quest') }}</option>
                  <option value="recurring">{{ __('Recurring Quests / Patrols') }}</option>
                  <option value="character">{{ __('Member Request') }}</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="editQuestEligibility">{{ __('Eligibility') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="editQuestEligibility" required>
                  <option value="all_ranks">{{ __('All (All Ranks)') }}</option>
                  <option value="candidate_member">{{ __('Candidate / Member') }}</option>
                  <option value="member_senior">{{ __('Member / Senior') }}</option>
                  <option value="senior_expert">{{ __('Senior / Expert') }}</option>
                  <option value="expert">{{ __('Expert Only') }}</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="editQuestCharacter">{{ __('Assigned Adventurer') }}</label>
                <input type="text" class="form-control" id="editQuestCharacter">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="editQuestStatus">{{ __('Status') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="editQuestStatus" required>
                  <option value="active">{{ __('Pinned on Board (Active)') }}</option>
                  <option value="on_hold">{{ __('Suspended (On Hold)') }}</option>
                  <option value="completed">{{ __('Quest Finished (Completed)') }}</option>
                  <option value="cancelled">{{ __('Cancelled Quests') }}</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="editQuestLocation">{{ __('Location / Region') }}</label>
                <input type="text" class="form-control" id="editQuestLocation">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="editQuestReward">{{ __('Reward') }}</label>
                <input type="text" class="form-control" id="editQuestReward">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="editQuestDesc">{{ __('Quest Notice & Details') }} <span class="text-danger">*</span></label>
              <textarea class="form-control" id="editQuestDesc" rows="3" required></textarea>
            </div>

            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="editQuestSendDiscord">
              <label class="form-check-label fw-semibold text-warning small" for="editQuestSendDiscord">
                <i class="icon-base ti tabler-brand-discord me-1"></i> {{ __('Güncellemeyi Discord lonca kanalına duyur') }}
              </label>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-primary rounded-pill fw-bold" id="btnSubmitEditQuest">
              <i class="icon-base ti tabler-check me-1"></i> {{ __('Save Changes') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 5. Modal: Discord Webhook Settings -->
  <div class="modal fade" id="modalDiscordSettings" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-warning shadow-lg">
        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2 mb-0">
            <i class="icon-base ti tabler-brand-discord text-warning fs-3"></i>
            <span>{{ __('Discord Ferman Bildirim Ayarları') }}</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <form id="formDiscordSettings">
          @csrf
          <div class="modal-body p-4">
            <p class="text-body-secondary small mb-3">
              {{ __('Panoya yeni bir ferman asıldığında veya ferman güncellendiğinde, Discord sunucunuzun görev kanalına otomatik Orta Çağ mühürlü duyuru gönderilir.') }}
            </p>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="inputDiscordWebhookUrl">
                {{ __('Discord Webhook URL') }}
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="icon-base ti tabler-link"></i></span>
                <input type="url" class="form-control font-monospace" id="inputDiscordWebhookUrl" 
                       placeholder="https://discord.com/api/webhooks/..." 
                       value="{{ $discordConfig['webhook_url'] ?? '' }}">
              </div>
              <small class="text-muted d-block mt-1">
                {{ __('Discord Sunucu Ayarları > Entegrasyonlar > Webhooklar bölümünden oluşturduğunuz webhook adresini yapıştırın.') }}
              </small>
            </div>

            <div class="card p-3 bg-label-secondary border-0 rounded-3 mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="fw-bold d-block text-heading">{{ __('Otomatik Bildirimler') }}</span>
                  <small class="text-muted">{{ __('Yeni fermanlar asıldığında Discord\'a iletilsin mi?') }}</small>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" id="switchDiscordEnabled" {{ !empty($discordConfig['enabled']) ? 'checked' : '' }}>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2">
              <span class="text-muted small">
                {{ __('Bağlantıyı doğrulamak için test mesajı gönderebilirsiniz:') }}
              </span>
              <button type="button" class="btn btn-outline-warning btn-sm rounded-pill" id="btnTestDiscordWebhook">
                <i class="icon-base ti tabler-bell-ringing me-1"></i> {{ __('Test Gönder') }}
              </button>
            </div>
          </div>
          <div class="modal-footer border-top d-flex justify-content-between py-3">
            <button type="button" class="btn btn-label-secondary btn-sm rounded-pill" data-bs-dismiss="modal">{{ __('Close') }}</button>
            <button type="submit" class="btn btn-warning btn-sm rounded-pill fw-bold px-3" id="btnSaveDiscordSettings">
              <i class="icon-base ti tabler-check me-1"></i> {{ __('Ayarları Kaydet') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {

  // State
  let currentCategory = 'all';
  let currentEligibility = 'all';
  let currentStatus = 'all';
  let currentSearch = '';

  const boardEl = document.getElementById('medievalBoardElement');
  const inputBg = document.getElementById('inputCustomBg');
  const inputText = document.getElementById('inputCustomText');
  const inputUygunluk = document.getElementById('inputCustomUygunluk');
  const previewBox = document.getElementById('colorPreviewBox');
  const previewUygunluk = document.getElementById('previewUygunlukLabel');
  const previewDesc = document.getElementById('previewDescText');

  // ================= 1. THEMES & COLOR CUSTOMIZATION =================

  const THEMES = {
    classic: {
      bg: '#fbf5e6',
      text: '#2c2011',
      desc: '#443220',
      border: '#d4c299',
      uygunluk: '#1a0e05'
    },
    ivory: {
      bg: '#ffffff',
      text: '#111111',
      desc: '#222222',
      border: '#c0a77e',
      uygunluk: '#000000'
    },
    papyrus: {
      bg: '#f4e8c1',
      text: '#1b1208',
      desc: '#332314',
      border: '#c9b179',
      uygunluk: '#120a03'
    },
    dark: {
      bg: '#24190e',
      text: '#fdf5e2',
      desc: '#dfceb1',
      border: '#7c5730',
      uygunluk: '#ffca28'
    }
  };

  function applyCustomColors(colors) {
    if (!boardEl) return;
    boardEl.style.setProperty('--parchment-bg', colors.bg);
    boardEl.style.setProperty('--parchment-bg-gradient', colors.bg);
    boardEl.style.setProperty('--parchment-text', colors.text);
    boardEl.style.setProperty('--parchment-desc', colors.desc);
    boardEl.style.setProperty('--parchment-border', colors.border);
    boardEl.style.setProperty('--uygunluk-label-color', colors.uygunluk);

    // Update modal pickers if present
    if (inputBg) inputBg.value = colors.bg;
    if (inputText) inputText.value = colors.text;
    if (inputUygunluk) inputUygunluk.value = colors.uygunluk;

    // Update preview box
    if (previewBox) {
      previewBox.style.background = colors.bg;
      previewBox.style.color = colors.text;
    }
    if (previewUygunluk) previewUygunluk.style.color = colors.uygunluk;
    if (previewDesc) previewDesc.style.color = colors.desc;

    // Save to localStorage
    localStorage.setItem('valiant_questboard_colors', JSON.stringify(colors));
  }

  // Load saved colors on start
  const saved = localStorage.getItem('valiant_questboard_colors');
  if (saved) {
    try {
      applyCustomColors(JSON.parse(saved));
    } catch (e) {
      applyCustomColors(THEMES.classic);
    }
  } else {
    applyCustomColors(THEMES.classic);
  }

  // Preset Card Clicks
  document.querySelectorAll('.preset-card').forEach(card => {
    card.addEventListener('click', function() {
      document.querySelectorAll('.preset-card').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const themeName = this.dataset.theme;
      if (THEMES[themeName]) {
        applyCustomColors(THEMES[themeName]);
      }
    });
  });

  // Custom Color Picker Changes
  if (inputBg) {
    inputBg.addEventListener('input', function() {
      boardEl.style.setProperty('--parchment-bg', this.value);
      boardEl.style.setProperty('--parchment-bg-gradient', this.value);
      if (previewBox) previewBox.style.background = this.value;
      saveCurrentInputs();
    });
  }

  if (inputText) {
    inputText.addEventListener('input', function() {
      boardEl.style.setProperty('--parchment-text', this.value);
      boardEl.style.setProperty('--parchment-desc', this.value);
      if (previewBox) previewBox.style.color = this.value;
      saveCurrentInputs();
    });
  }

  if (inputUygunluk) {
    inputUygunluk.addEventListener('input', function() {
      boardEl.style.setProperty('--uygunluk-label-color', this.value);
      if (previewUygunluk) previewUygunluk.style.color = this.value;
      saveCurrentInputs();
    });
  }

  function saveCurrentInputs() {
    const current = {
      bg: inputBg ? inputBg.value : '#fbf5e6',
      text: inputText ? inputText.value : '#2c2011',
      desc: inputText ? inputText.value : '#443220',
      border: '#c0a77e',
      uygunluk: inputUygunluk ? inputUygunluk.value : '#1a0e05'
    };
    localStorage.setItem('valiant_questboard_colors', JSON.stringify(current));
  }

  // Reset to Defaults
  const btnResetDefaults = document.getElementById('btnResetToDefaults');
  if (btnResetDefaults) {
    btnResetDefaults.addEventListener('click', function() {
      document.querySelectorAll('.preset-card').forEach(c => c.classList.remove('active'));
      const classic = document.getElementById('presetClassic');
      if (classic) classic.classList.add('active');
      applyCustomColors(THEMES.classic);
    });
  }

  // ================= 2. FILTERING LOGIC =================

  function applyFilters() {
    const cards = document.querySelectorAll('.quest-card-item');
    const rows = document.querySelectorAll('.ledger-row');

    function matches(el) {
      const cat = el.dataset.category || '';
      const elig = el.dataset.eligibility || '';
      const stat = el.dataset.status || '';
      const char = el.dataset.character || '';
      const title = el.dataset.title || '';

      // Category check
      if (currentCategory !== 'all') {
        if (currentCategory === 'character') {
          if (!char || char === 'Lonca') return false;
        } else if (cat !== currentCategory) {
          return false;
        }
      }

      // Eligibility check
      if (currentEligibility !== 'all' && elig !== currentEligibility) {
        return false;
      }

      // Status check
      if (currentStatus !== 'all' && stat !== currentStatus) {
        return false;
      }

      // Search check
      if (currentSearch) {
        const match = title.includes(currentSearch) || char.toLowerCase().includes(currentSearch) || elig.toLowerCase().includes(currentSearch);
        if (!match) return false;
      }

      return true;
    }

    cards.forEach(card => {
      card.style.display = matches(card) ? '' : 'none';
    });

    rows.forEach(row => {
      row.style.display = matches(row) ? '' : 'none';
    });
  }

  // Category Tabs Click
  document.querySelectorAll('.filter-category-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelectorAll('.filter-category-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentCategory = this.dataset.category;
      applyFilters();
    });
  });

  // Eligibility & Status Selects
  const selectElig = document.getElementById('filterEligibility');
  if (selectElig) {
    selectElig.addEventListener('change', function() {
      currentEligibility = this.value;
      applyFilters();
    });
  }

  const selectStatus = document.getElementById('filterStatus');
  if (selectStatus) {
    selectStatus.addEventListener('change', function() {
      currentStatus = this.value;
      applyFilters();
    });
  }

  // Search Input
  const searchInput = document.getElementById('questSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      currentSearch = this.value.toLowerCase().trim();
      applyFilters();
    });
  }

  // Reset Filters
  const btnReset = document.getElementById('btnResetFilters');
  if (btnReset) {
    btnReset.addEventListener('click', function() {
      currentCategory = 'all';
      currentEligibility = 'all';
      currentStatus = 'all';
      currentSearch = '';

      document.querySelectorAll('.filter-category-btn').forEach(b => b.classList.remove('active'));
      document.querySelector('.filter-category-btn[data-category="all"]').classList.add('active');
      if (selectElig) selectElig.value = 'all';
      if (selectStatus) selectStatus.value = 'all';
      if (searchInput) searchInput.value = '';

      applyFilters();
    });
  }

  // View Switcher (Parchment Board vs Ledger Table)
  const btnViewParchment = document.getElementById('btnViewParchment');
  const btnViewLedger = document.getElementById('btnViewLedger');
  const viewParchment = document.getElementById('viewParchmentContainer');
  const viewLedger = document.getElementById('viewLedgerContainer');

  if (btnViewParchment && btnViewLedger) {
    btnViewParchment.addEventListener('click', function() {
      btnViewParchment.classList.add('active');
      btnViewLedger.classList.remove('active');
      viewParchment.style.display = '';
      viewLedger.style.display = 'none';
    });

    btnViewLedger.addEventListener('click', function() {
      btnViewLedger.classList.add('active');
      btnViewParchment.classList.remove('active');
      viewParchment.style.display = 'none';
      viewLedger.style.display = '';
    });
  }

  // Inspect Quest Modal Trigger & Current Active ID
  let currentInspectedQuestId = null;
  let currentInspectedQuestTitle = '';

  document.querySelectorAll('.btn-inspect-quest').forEach(btn => {
    btn.addEventListener('click', function() {
      currentInspectedQuestId = this.dataset.id || null;
      currentInspectedQuestTitle = this.dataset.title || '';

      document.getElementById('inspectQuestTitle').textContent = this.dataset.title;
      const catEl = document.getElementById('inspectQuestCategory');
      catEl.textContent = this.dataset.category;
      const catKey = this.dataset.categoryKey || '';
      catEl.className = 'stamp-tag ' + (
        catKey === 'character' ? 'stamp-member' :
        (catKey === 'side' ? 'stamp-side' :
        (catKey === 'recurring' ? 'stamp-recurring' : 'stamp-main'))
      );
      const statusEl = document.getElementById('inspectQuestStatus');
      statusEl.textContent = this.dataset.statusLabel || this.dataset.status;
      statusEl.className = 'badge rounded-pill font-monospace px-3 py-1 ' + (
        this.dataset.status === 'active' ? 'bg-success' :
        (this.dataset.status === 'on_hold' ? 'bg-info' :
        (this.dataset.status === 'completed' ? 'bg-danger' : 'bg-secondary'))
      );

      const eligEl = document.getElementById('inspectQuestEligibility');
      eligEl.textContent = this.dataset.eligibility;
      eligEl.className = 'badge-meta badge-eligibility ' + (this.dataset.badgeClass || 'badge-elig-senior');

      document.getElementById('inspectQuestCharacter').textContent = this.dataset.character;
      document.getElementById('inspectQuestLocation').textContent = '📍 ' + (this.dataset.location || '{{ __("Unknown") }}');
      document.getElementById('inspectQuestReward').textContent = '🏆 ' + (this.dataset.reward || '{{ __("Standard Reward") }}');
      document.getElementById('inspectQuestDesc').textContent = this.dataset.description;

      const modal = new bootstrap.Modal(document.getElementById('modalInspectQuest'));
      modal.show();
    });
  });

  // Helper: Broadcast decree to Discord
  function broadcastDecree(id, title) {
    if (!id) return;
    Swal.fire({
      title: '{{ __("Discord Ferman Duyurusu") }}',
      text: '"' + title + '" {{ __("fermanı Discord lonca kanalına gönderilsin mi?") }}',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '{{ __("Evet, Duyur!") }}',
      cancelButtonText: '{{ __("İptal") }}',
      confirmButtonColor: '#f59e0b',
    }).then(result => {
      if (result.isConfirmed) {
        Swal.fire({
          title: '{{ __("İletiliyor...") }}',
          text: '{{ __("Discord sunucusuna ferman iletiliyor.") }}',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        fetch("{{ url('/valiant-shade/pages/quest-board') }}/" + id + "/broadcast", {
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
              title: '{{ __("Duyuruldu!") }}',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: '{{ __("Duyuru Gönderilemedi") }}',
              text: data.message
            });
          }
        })
        .catch(() => {
          Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
        });
      }
    });
  }

  // Inspect Modal Discord Trigger
  const inspectBtnDiscord = document.getElementById('inspectBtnDiscord');
  if (inspectBtnDiscord) {
    inspectBtnDiscord.addEventListener('click', function() {
      if (currentInspectedQuestId) {
        broadcastDecree(currentInspectedQuestId, currentInspectedQuestTitle);
      }
    });
  }

  // Card / Table Discord Trigger
  document.querySelectorAll('.btn-broadcast-discord').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      broadcastDecree(this.dataset.id, this.dataset.title);
    });
  });

  // Admin Quick Status Change
  document.querySelectorAll('.btn-quick-status').forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const id = this.dataset.id;
      const status = this.dataset.status;

      fetch("{{ url('/valiant-shade/pages/quest-board') }}/" + id + "/status", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Durum Güncellendi!") }}',
            text: data.message,
            timer: 1200,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: data.message });
        }
      })
      .catch(() => {
        Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
      });
    });
  });

  // Admin Open Edit Quest Modal
  document.querySelectorAll('.btn-edit-quest').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('editQuestId').value = this.dataset.id;
      document.getElementById('editQuestTitle').value = this.dataset.title || '';
      document.getElementById('editQuestCategory').value = this.dataset.category || 'side';
      document.getElementById('editQuestEligibility').value = this.dataset.eligibility || 'all_ranks';
      document.getElementById('editQuestCharacter').value = this.dataset.character || '';
      document.getElementById('editQuestStatus').value = this.dataset.status || 'active';
      document.getElementById('editQuestLocation').value = this.dataset.location || '';
      document.getElementById('editQuestReward').value = this.dataset.reward || '';
      document.getElementById('editQuestDesc').value = this.dataset.description || '';
      document.getElementById('editQuestSendDiscord').checked = false;

      const modal = new bootstrap.Modal(document.getElementById('modalEditQuest'));
      modal.show();
    });
  });

  // Admin Submit Edit Quest Form
  const formEdit = document.getElementById('formEditQuest');
  if (formEdit) {
    formEdit.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = document.getElementById('editQuestId').value;
      const btn = document.getElementById('btnSubmitEditQuest');
      btn.disabled = true;

      const payload = {
        title: document.getElementById('editQuestTitle').value,
        category: document.getElementById('editQuestCategory').value,
        eligibility: document.getElementById('editQuestEligibility').value,
        character: document.getElementById('editQuestCharacter').value,
        status: document.getElementById('editQuestStatus').value,
        location: document.getElementById('editQuestLocation').value,
        reward: document.getElementById('editQuestReward').value,
        description: document.getElementById('editQuestDesc').value,
        send_discord: document.getElementById('editQuestSendDiscord').checked
      };

      fetch("{{ url('/valiant-shade/pages/quest-board') }}/" + id, {
        method: 'PUT',
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
            title: '{{ __("Ferman Güncellendi!") }}',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
      });
    });
  }

  // Admin Delete Quest Decree
  document.querySelectorAll('.btn-delete-quest').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const id = this.dataset.id;
      const title = this.dataset.title;

      Swal.fire({
        title: '{{ __("Fermanı Kaldır?") }}',
        text: '"' + title + '" {{ __("fermanı panodan kalıcı olarak sökülecektir.") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __("Evet, Sök!") }}',
        cancelButtonText: '{{ __("Vazgeç") }}'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch("{{ url('/valiant-shade/pages/quest-board') }}/" + id, {
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
                title: '{{ __("Ferman Söküldü!") }}',
                text: data.message,
                timer: 1400,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: data.message });
            }
          })
          .catch(() => {
            Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
          });
        }
      });
    });
  });

  // Admin Reset to Master Defaults
  const btnResetDefaults = document.getElementById('btnResetQuestDefaults');
  if (btnResetDefaults) {
    btnResetDefaults.addEventListener('click', function() {
      Swal.fire({
        title: '{{ __("Varsayılan Görevlere Sıfırla?") }}',
        text: '{{ __("Tüm panodaki fermanlar orijinal Görev Listesi verilerine geri döndürülecektir.") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __("Evet, Sıfırla!") }}',
        cancelButtonText: '{{ __("İptal") }}'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch("{{ route('valiant-shade.pages-quest-board.reset-defaults') }}", {
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
                title: '{{ __("Sıfırlandı!") }}',
                text: data.message,
                timer: 1600,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: data.message });
            }
          })
          .catch(() => {
            Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
          });
        }
      });
    });
  }

  // Admin Discord Settings Form Save
  const formDiscord = document.getElementById('formDiscordSettings');
  if (formDiscord) {
    formDiscord.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSaveDiscordSettings');
      btn.disabled = true;

      const payload = {
        webhook_url: document.getElementById('inputDiscordWebhookUrl').value.trim(),
        enabled: document.getElementById('switchDiscordEnabled').checked
      };

      fetch("{{ route('valiant-shade.pages-quest-board.discord-settings') }}", {
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
            title: '{{ __("Kaydedildi!") }}',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
      });
    });
  }

  // Admin Discord Test Message
  const btnTestDiscord = document.getElementById('btnTestDiscordWebhook');
  if (btnTestDiscord) {
    btnTestDiscord.addEventListener('click', function() {
      const url = document.getElementById('inputDiscordWebhookUrl').value.trim();
      if (!url) {
        Swal.fire({
          icon: 'warning',
          title: '{{ __("URL Gerekli") }}',
          text: '{{ __("Lütfen önce bir Discord Webhook URL adresi girin.") }}'
        });
        return;
      }

      btnTestDiscord.disabled = true;
      btnTestDiscord.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Gönderiliyor...") }}';

      fetch("{{ route('valiant-shade.pages-quest-board.discord-test') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ webhook_url: url })
      })
      .then(res => res.json())
      .then(data => {
        btnTestDiscord.disabled = false;
        btnTestDiscord.innerHTML = '<i class="icon-base ti tabler-bell-ringing me-1"></i> {{ __("Test Gönder") }}';
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Bağlantı Başarılı!") }}',
            text: data.message
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: '{{ __("Bağlantı Başarısız") }}',
            text: data.message
          });
        }
      })
      .catch(() => {
        btnTestDiscord.disabled = false;
        btnTestDiscord.innerHTML = '<i class="icon-base ti tabler-bell-ringing me-1"></i> {{ __("Test Gönder") }}';
        Swal.fire({ icon: 'error', title: '{{ __("Hata!") }}', text: '{{ __("Sunucuya erişilemedi.") }}' });
      });
    });
  }

  // Admin Form: Pin New Quest
  const formPin = document.getElementById('formPinQuest');
  if (formPin) {
    formPin.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitPinQuest');
      btn.disabled = true;

      const payload = {
        title: document.getElementById('inputQuestTitle').value,
        category: document.getElementById('inputQuestCategory').value,
        eligibility: document.getElementById('inputQuestEligibility').value,
        character: document.getElementById('inputQuestCharacter').value || '{{ __('Guild') }}',
        location: document.getElementById('inputQuestLocation').value,
        reward: document.getElementById('inputQuestReward').value,
        description: document.getElementById('inputQuestDesc').value,
        send_discord: document.getElementById('inputQuestSendDiscord') ? document.getElementById('inputQuestSendDiscord').checked : false
      };

      fetch("{{ route('valiant-shade.pages-quest-board.store') }}", {
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
            title: '{{ __('Notice Pinned!') }}',
            text: data.message + (data.discord_sent ? ' (Discord\'a iletildi)' : ''),
            timer: 1600,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: '{{ __('Error!') }}', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __('Error!') }}', text: '{{ __('Could not reach the server.') }}' });
      });
    });
  }

});
</script>
@endsection
