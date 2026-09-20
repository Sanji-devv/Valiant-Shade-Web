@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Lonca Görev Panosu - Valiant Shade')

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
          VALIANT SHADE LONCA GÖREV PANOSU
        </h2>
        <p class="mb-0 small text-light-emphasis" style="color: #dfceb1 !important;">
        </p>
      </div>
      <i class="icon-base ti tabler-scroll fs-2 text-warning"></i>
    </div>
  </div>

  <!-- Top Wooden Placards (Metrics & Demographics) -->
  <div class="row g-3 mb-4">
    <!-- Placard 1: Total Board Quests -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="wooden-placard d-flex align-items-center justify-content-between">
        <div>
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">Panoda Asılı Görevler</span>
          <h3 class="mb-0 fw-bold font-monospace text-warning mt-1" id="countTotalQuests">{{ $counts['total'] }}</h3>
          <small class="text-success" style="color: #74c374 !important;">{{ $counts['active'] }} Aktif &bull; {{ $counts['on_hold'] }} Askıda</small>
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
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">Ana Görev Fermanları</span>
          <h3 class="mb-0 fw-bold font-monospace text-danger mt-1" style="color: #ff8a80 !important;" id="countMainQuests">{{ $counts['main'] }}</h3>
          <small style="color: #cbb493 !important;">Lonca ve Şahsi Görevler</small>
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
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">Yan Görevler</span>
          <h3 class="mb-0 fw-bold font-monospace text-info mt-1" style="color: #82b1ff !important;" id="countSideQuests">{{ $counts['side'] }}</h3>
          <small style="color: #cbb493 !important;">Bölgesel İlanlar & Avlar</small>
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
          <span class="text-body-secondary small d-block" style="color: #cbb493 !important;">Sürekli Görev / Devriye</span>
          <h3 class="mb-0 fw-bold font-monospace text-primary mt-1" style="color: #b388ff !important;" id="countRecurringQuests">{{ $counts['recurring'] }}</h3>
          <small style="color: #cbb493 !important;">Zindan & Yol Güvenliği</small>
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
            <i class="icon-base ti tabler-users me-1"></i> Lonca Kadrosu:
          </span>
          @foreach($guildInfo['ranks'] as $r)
            <span class="badge {{ $r['badge'] }} rounded-pill font-monospace" title="{{ $r['rank'] }} Kadrosu">
              {{ $r['rank'] }}: {{ $r['count'] }}
            </span>
          @endforeach
        </div>
      </div>
      <!-- Right: Master Sheet Color Legend -->
      <div class="col-12 col-lg-5">
        <div class="d-flex align-items-center justify-content-lg-end gap-3 flex-wrap small">
          <span class="fw-semibold text-light-emphasis" style="color: #cbb493 !important;">İşaret Lejantı:</span>
          <span class="badge bg-danger rounded-pill">Kırmızı: Görev Bitti</span>
          <span class="badge bg-primary rounded-pill">Mavi: Görev Askıda</span>
          <span class="badge bg-secondary rounded-pill">Gri: İptal</span>
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
            <i class="icon-base ti tabler-layout-grid me-1"></i> Tüm İlanlar ({{ $counts['total'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="main">
            <i class="icon-base ti tabler-swords me-1"></i> Ana Görevler ({{ $counts['main'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="side">
            <i class="icon-base ti tabler-compass me-1"></i> Yan Görevler ({{ $counts['side'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="recurring">
            <i class="icon-base ti tabler-refresh me-1"></i> Sürekli Görevler ({{ $counts['recurring'] }})
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link cursor-pointer filter-category-btn" data-category="character">
            <i class="icon-base ti tabler-user-check me-1"></i> Üye Talepleri ({{ $counts['character'] }})
          </a>
        </li>
      </ul>
    </div>

    <!-- Actions, Appearance Settings & View Switcher -->
    <div class="col-12 col-xl-5">
      <div class="d-flex align-items-center justify-content-xl-end gap-2 flex-wrap">
        <!-- Appearance Customizer Button -->
        <button type="button" class="btn btn-outline-warning btn-sm rounded-pill shadow-xs" data-bs-toggle="modal" data-bs-target="#modalBoardAppearance" title="Parşömen ve Yazı Renklerini Değiştir">
          <i class="icon-base ti tabler-color-swatch me-1"></i> Renk Ayarları
        </button>

        <!-- View Toggle Buttons -->
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-warning btn-sm active" id="btnViewParchment" title="Pano Görünümü">
            <i class="icon-base ti tabler-pin me-1"></i> Pano
          </button>
          <button type="button" class="btn btn-outline-warning btn-sm" id="btnViewLedger" title="Kayıt Defteri Tablosu">
            <i class="icon-base ti tabler-table me-1"></i> Defter
          </button>
        </div>

        @if($isAdmin)
          <!-- Pin New Quest Button -->
          <button type="button" class="btn btn-warning btn-sm rounded-pill waves-effect shadow-xs fw-bold" data-bs-toggle="modal" data-bs-target="#modalPinQuest">
            <i class="icon-base ti tabler-plus me-1"></i> Yeni Görev As
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
          <input type="text" class="form-control medieval-input border-start-0" id="questSearchInput" placeholder="Görev başlığı, maceracı veya bölge ara...">
        </div>
      </div>

      <!-- Eligibility Filter -->
      <div class="col-6 col-md-3">
        <select class="form-select form-select-sm medieval-input" id="filterEligibility">
          <option value="all">Tüm Uygunluk Seviyeleri</option>
          <option value="Hepsi">Hepsi (Tüm Rütbeler)</option>
          <option value="Aday/Üye">Aday / Üye</option>
          <option value="Üye/Kıdemli">Üye / Kıdemli</option>
          <option value="Kıdemli/Uzman">Kıdemli / Uzman</option>
          <option value="Uzman">Sadece Uzman</option>
        </select>
      </div>

      <!-- Status Filter -->
      <div class="col-6 col-md-3">
        <select class="form-select form-select-sm medieval-input" id="filterStatus">
          <option value="all">Tüm Durumlar</option>
          <option value="active">Panoda Asılı (Aktif)</option>
          <option value="on_hold">Askıya Alındı (Beklemede)</option>
          <option value="completed">Görev Bitti (Tamamlandı)</option>
          <option value="cancelled">İptal Edilenler</option>
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
          $stampClass = match($q['category_label']) {
            'Üye Talebi' => 'stamp-member',
            'Ana Görev' => 'stamp-main',
            'Yan Görev' => 'stamp-side',
            default => 'stamp-recurring'
          };
          $statusBadge = match($q['status']) {
            'completed' => 'badge-status-completed',
            'on_hold' => 'badge-status-onhold',
            'cancelled' => 'badge-status-cancelled',
            default => 'badge-status-active'
          };
          $statusText = match($q['status']) {
            'completed' => 'Tamamlandı',
            'on_hold' => 'Askıya Alındı',
            'cancelled' => 'İptal',
            default => 'Aktif'
          };

          // High Contrast Distinct Eligibility Badge Class
          $eligBadgeClass = match($q['eligibility']) {
            'Hepsi' => 'badge-elig-hepsi',
            'Aday/Üye' => 'badge-elig-candidate',
            'Üye/Kıdemli' => 'badge-elig-member',
            'Kıdemli/Uzman' => 'badge-elig-senior',
            'Uzman' => 'badge-elig-expert',
            default => 'badge-elig-default'
          };
        @endphp

        <div class="col-12 col-md-6 col-xl-4 quest-card-item" 
             data-category="{{ $q['category'] }}" 
             data-eligibility="{{ $q['eligibility'] }}" 
             data-status="{{ $q['status'] }}"
             data-character="{{ $q['character'] ?? 'Lonca' }}"
             data-title="{{ mb_strtolower($q['title']) }}">
          
          <div class="parchment-card">
            <!-- Iron Pushpin Tack -->
            <div class="iron-pin" title="Panoya Çivilenmiş"></div>

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
                    <span class="meta-label">Uygunluk:</span> 
                    <span class="badge-meta badge-eligibility {{ $eligBadgeClass }}">
                      {{ $q['eligibility'] }}
                    </span>
                  </div>
                  <div class="d-flex align-items-center gap-1.5">
                    <span class="meta-label">İlgili:</span> 
                    <span class="badge-meta badge-meta-character">
                      {{ $q['character'] ?? 'Lonca' }}
                    </span>
                  </div>
                </div>

                <!-- Row 2: Location -->
                @if(!empty($q['location']))
                  <div class="d-flex align-items-center gap-1.5 text-truncate">
                    <span class="meta-label">Konum:</span>
                    <span class="badge-meta badge-meta-location text-truncate" title="{{ $q['location'] }}">
                      <span>📍</span> <span class="text-truncate">{{ $q['location'] }}</span>
                    </span>
                  </div>
                @endif

                <!-- Row 3: Reward -->
                @if(!empty($q['reward']))
                  <div class="d-flex align-items-center gap-1.5 text-truncate">
                    <span class="meta-label">Ödül:</span>
                    <span class="badge-meta badge-meta-reward text-truncate" title="{{ $q['reward'] }}">
                      <span>🏆</span> <span class="text-truncate">{{ $q['reward'] }}</span>
                    </span>
                  </div>
                @endif
              </div>

              <!-- Bottom Row: Status Badge & Inspect Trigger -->
              <div class="d-flex justify-content-between align-items-center pt-2 mt-auto" style="border-top: 1px solid rgba(140, 100, 50, 0.25);">
                <span class="badge {{ $statusBadge }} rounded-pill font-monospace">
                  {{ $statusText }}
                </span>

                <button type="button" class="btn btn-xs btn-outline-dark rounded-pill btn-inspect-quest fw-bold px-3 py-1"
                        data-title="{{ $q['title'] }}"
                        data-category="{{ $q['category_label'] }}"
                        data-eligibility="{{ $q['eligibility'] }}"
                        data-character="{{ $q['character'] ?? 'Lonca' }}"
                        data-location="{{ $q['location'] ?? 'Bilinmiyor' }}"
                        data-reward="{{ $q['reward'] ?? 'Standart Ödül' }}"
                        data-status="{{ $statusText }}"
                        data-badge-class="{{ $eligBadgeClass }}"
                        data-description="{{ $q['description'] }}">
                  <i class="icon-base ti tabler-eye me-1"></i> Fermanı İncele
                </button>
              </div>
            </div>

          </div>
        </div>
      @empty
        <div class="col-12 text-center py-5">
          <i class="icon-base ti tabler-scroll-off fs-1 text-warning mb-2 d-block"></i>
          <h5 class="text-warning font-monospace">Panoda Bu Kriterlere Uygun Ferman Bulunamadı</h5>
          <p class="text-light-emphasis small">Filtreleri sıfırlayarak tüm görev ilanlarını görebilirsiniz.</p>
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
            <th>Görev Fermanı (Title)</th>
            <th class="text-center">Kategori</th>
            <th class="text-center">Uygunluk Rütbesi</th>
            <th>İlgili Maceracı / Ekip</th>
            <th>Bölge / Konum</th>
            <th class="text-end">Ödül</th>
            <th class="text-center">Durum</th>
            <th class="text-end">İncele</th>
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
                'completed' => 'Tamamlandı',
                'on_hold' => 'Askıya Alındı',
                'cancelled' => 'İptal',
                default => 'Aktif'
              };
              $eligBadgeClass = match($q['eligibility']) {
                'Hepsi' => 'badge-elig-hepsi',
                'Aday/Üye' => 'badge-elig-candidate',
                'Üye/Kıdemli' => 'badge-elig-member',
                'Kıdemli/Uzman' => 'badge-elig-senior',
                'Uzman' => 'badge-elig-expert',
                default => 'badge-elig-default'
              };
            @endphp
            <tr class="ledger-row" 
                data-category="{{ $q['category'] }}" 
                data-eligibility="{{ $q['eligibility'] }}" 
                data-status="{{ $q['status'] }}"
                data-character="{{ $q['character'] ?? 'Lonca' }}"
                data-title="{{ mb_strtolower($q['title']) }}">
              <td class="text-center font-monospace fw-bold text-body-secondary">
                {{ $idx + 1 }}
              </td>
              <td>
                <span class="fw-bold text-heading d-block">{{ $q['title'] }}</span>
                <small class="text-body-secondary text-truncate d-block" style="max-width: 250px;">{{ $q['description'] }}</small>
              </td>
              <td class="text-center">
                <span class="stamp-tag {{ match($q['category_label']) { 'Üye Talebi' => 'stamp-member', 'Ana Görev' => 'stamp-main', 'Yan Görev' => 'stamp-side', default => 'stamp-recurring' } }}">
                  {{ $q['category_label'] }}
                </span>
              </td>
              <td class="text-center font-monospace">
                <span class="badge-eligibility {{ $eligBadgeClass }}">{{ $q['eligibility'] }}</span>
              </td>
              <td>
                <span class="fw-semibold font-monospace">{{ $q['character'] ?? 'Lonca' }}</span>
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
                <button type="button" class="btn btn-xs btn-outline-dark rounded-pill btn-inspect-quest"
                        data-title="{{ $q['title'] }}"
                        data-category="{{ $q['category_label'] }}"
                        data-eligibility="{{ $q['eligibility'] }}"
                        data-character="{{ $q['character'] ?? 'Lonca' }}"
                        data-location="{{ $q['location'] ?? 'Bilinmiyor' }}"
                        data-reward="{{ $q['reward'] ?? 'Standart Ödül' }}"
                        data-status="{{ $statusText }}"
                        data-badge-class="{{ $eligBadgeClass }}"
                        data-description="{{ $q['description'] }}">
                  <i class="icon-base ti tabler-eye"></i>
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ================= MODALS ================= -->

<!-- 1. Modal: Renk & Görünüm Ayarları (Appearance & Colors Customizer) -->
<div class="modal fade" id="modalBoardAppearance" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning shadow-lg">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2 mb-0">
          <i class="icon-base ti tabler-color-swatch text-warning fs-4"></i>
          <span>Parşömen & Yazı Renk Ayarları</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Section 1: Ready-made Themes -->
        <h6 class="fw-bold mb-2">Hazır Parşömen Temaları:</h6>
        <div class="row g-2 mb-4">
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center active" id="presetClassic" data-theme="classic" style="background: #fbf5e6; color: #2c2011;">
              <small class="fw-bold d-block">📜 Klasik Parşömen</small>
              <span style="font-size: 0.72rem;" class="text-muted">Sıcak Antik Tonlar</span>
            </div>
          </div>
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center" id="presetIvory" data-theme="ivory" style="background: #ffffff; color: #111111;">
              <small class="fw-bold d-block">📜 Açık Fildişi</small>
              <span style="font-size: 0.72rem;" class="text-muted">Yüksek Kontrast & Net</span>
            </div>
          </div>
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center" id="presetPapyrus" data-theme="papyrus" style="background: #f4e8c1; color: #1b1208;">
              <small class="fw-bold d-block">📜 Antik Papirüs</small>
              <span style="font-size: 0.72rem;" class="text-muted">Altın Çöl Rulosu</span>
            </div>
          </div>
          <div class="col-6">
            <div class="preset-card card border p-2 rounded text-center" id="presetDark" data-theme="dark" style="background: #24190e; color: #fdf5e2;">
              <small class="fw-bold d-block">📜 Koyu Zindan</small>
              <span style="font-size: 0.72rem; color: #d7c4a3;">Gece Deri Fermanı</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Live Custom Color Controls -->
        <h6 class="fw-bold mb-2">Özel Renk Seçicileri:</h6>
        <div class="card border p-3 rounded bg-body mb-3">
          <!-- Parchment Background -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <span class="fw-semibold d-block small">Parşömen Zemin Rengi</span>
              <small class="text-body-secondary" style="font-size: 0.74rem;">Ferman kağıdının arka planı</small>
            </div>
            <input type="color" class="form-control form-control-color" id="inputCustomBg" value="#fbf5e6" title="Zemin Rengi Seç">
          </div>

          <!-- General Text Color -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <span class="fw-semibold d-block small">Başlık ve Metin Rengi</span>
              <small class="text-body-secondary" style="font-size: 0.74rem;">Görev başlığı ve lore açıklamaları</small>
            </div>
            <input type="color" class="form-control form-control-color" id="inputCustomText" value="#2c2011" title="Metin Rengi Seç">
          </div>

          <!-- "Uygunluk" Label Color -->
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="fw-semibold d-block small">"Uygunluk:" Yazı Rengi</span>
              <small class="text-body-secondary" style="font-size: 0.74rem;">Rütbe etiketinin solundaki başlık</small>
            </div>
            <input type="color" class="form-control form-control-color" id="inputCustomUygunluk" value="#1a0e05" title="Uygunluk Başlık Rengi Seç">
          </div>
        </div>

        <!-- Live Preview Box -->
        <div class="p-3 rounded border text-center" id="colorPreviewBox" style="background: #fbf5e6; color: #2c2011;">
          <small class="text-uppercase fw-bold text-muted d-block mb-1" style="font-size: 0.7rem;">Canlı Önizleme</small>
          <span class="uygunluk-title fw-bold" id="previewUygunlukLabel">Uygunluk:</span>
          <span class="badge-eligibility badge-elig-senior ms-1">Kıdemli/Uzman</span>
          <p class="small mb-0 mt-1" id="previewDescText">Bu görev fermanı seçtiğiniz renk ve kontrast ile render edilecektir.</p>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="btnResetToDefaults">
          <i class="icon-base ti tabler-rotate me-1"></i> Varsayılana Sıfırla
        </button>
        <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold" data-bs-dismiss="modal">
          <i class="icon-base ti tabler-check me-1"></i> Ayarları Koru
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 2. Modal: Inspect Quest Parchment (Resmi Ferman İnceleme) -->
<div class="modal fade" id="modalInspectQuest" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: var(--parchment-bg, #fbf5e6); border: 3px solid #7c5a31; color: var(--parchment-text, #2c2011);">
      <div class="modal-header border-bottom" style="border-color: rgba(140, 100, 50, 0.4) !important;">
        <div class="d-flex align-items-center gap-2">
          <span class="fs-4">📜</span>
          <h5 class="modal-title fw-bold mb-0" id="inspectQuestTitle" style="color: var(--parchment-text, #2c2011);">
            Görev Fermanı
          </h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Badge row -->
        <div class="d-flex align-items-center justify-content-between mb-3 pb-1">
          <span class="stamp-tag stamp-main" id="inspectQuestCategory">Ana Görev</span>
          <span class="badge bg-success rounded-pill font-monospace px-3 py-1" id="inspectQuestStatus">Panoda Asılı</span>
        </div>

        <!-- Description -->
        <div class="p-3 mb-3 rounded-3" style="background: rgba(140, 100, 50, 0.08); border: 1px dashed rgba(140, 100, 50, 0.4);">
          <h6 class="fw-bold mb-2 d-flex align-items-center gap-1" style="color: var(--parchment-text, #2c2011);">
            <span>📜</span> Lonca Bildirisi:
          </h6>
          <p class="mb-0 small" id="inspectQuestDesc" style="line-height: 1.65; color: var(--parchment-desc, #443220);">
            Detaylı görev açıklaması burada yer alacaktır.
          </p>
        </div>

        <!-- Details Grid -->
        <div class="row g-3 small">
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">Uygunluk:</span>
              <div>
                <span class="badge-eligibility badge-elig-senior" id="inspectQuestEligibility">Kıdemli/Uzman</span>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">İlgili Maceracı:</span>
              <div>
                <span class="badge bg-label-dark rounded-pill fw-bold font-monospace px-2.5 py-1" id="inspectQuestCharacter" style="font-size: 0.80rem; color: var(--parchment-text, #2c2011);">Lonca</span>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">Bölge / Konum:</span>
              <span id="inspectQuestLocation" class="fw-medium" style="color: var(--parchment-desc, #443220);">Bilinmiyor</span>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded h-100 d-flex flex-column justify-content-center" style="background: rgba(140, 100, 50, 0.06); border: 1px solid rgba(140, 100, 50, 0.2); min-height: 60px;">
              <span class="uygunluk-title d-block mb-1">Görev Ödülü:</span>
              <span class="text-success fw-bold" id="inspectQuestReward">Standart XP & Altın</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-end gap-2 py-3 px-4" style="border-color: rgba(140, 100, 50, 0.35) !important;">
        <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold px-3 shadow-sm" onclick="Swal.fire('Göreve Talip Olundu!', 'Görevi talep etmek için lonca kâtibine başvurdunuz.', 'success');">
          <i class="icon-base ti tabler-send me-1"></i> Görevi Talep Et
        </button>
      </div>
    </div>
  </div>
</div>

@if($isAdmin)
  <!-- 3. Modal: Pin New Quest (Panoya Yeni Görev As) -->
  <div class="modal fade" id="modalPinQuest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-pin text-warning fs-4"></i>
            <span>Panoya Yeni Görev Fermanı As</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
        </div>
        <form id="formPinQuest">
          @csrf
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-semibold" for="inputQuestTitle">Görev Başlığı <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inputQuestTitle" required placeholder="Örn: Dağdaki Ejderha İninin Temizliği">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestCategory">Kategori <span class="text-danger">*</span></label>
                <select class="form-select" id="inputQuestCategory" required>
                  <option value="main">Ana Görev (Main Quest)</option>
                  <option value="side">Yan Görev (Side Quest)</option>
                  <option value="recurring">Sürekli Görev / Devriye</option>
                  <option value="character">Üye Talebi</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestEligibility">Uygunluk <span class="text-danger">*</span></label>
                <select class="form-select" id="inputQuestEligibility" required>
                  <option value="Hepsi">Hepsi (Tüm Rütbeler)</option>
                  <option value="Aday/Üye">Aday / Üye</option>
                  <option value="Üye/Kıdemli">Üye / Kıdemli</option>
                  <option value="Kıdemli/Uzman">Kıdemli / Uzman</option>
                  <option value="Uzman">Sadece Uzman</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestCharacter">İlgili Maceracı</label>
                <input type="text" class="form-control" id="inputQuestCharacter" value="Lonca" placeholder="Lonca veya Karakter adı">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" for="inputQuestLocation">Konum / Bölge</label>
                <input type="text" class="form-control" id="inputQuestLocation" placeholder="Örn: Kuzey Sisli Dağları">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" for="inputQuestReward">Ödül</label>
              <input type="text" class="form-control" id="inputQuestReward" placeholder="Örn: 10,000 XP & 500 SP">
            </div>

            <div class="mb-0">
              <label class="form-label fw-semibold" for="inputQuestDesc">Görev Bildirisi & Detaylar <span class="text-danger">*</span></label>
              <textarea class="form-control" id="inputQuestDesc" rows="3" required placeholder="Görevin amacı, tehlikeler ve maceracılardan beklenenler..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">Vazgeç</button>
            <button type="submit" class="btn btn-warning rounded-pill fw-bold" id="btnSubmitPinQuest">
              <i class="icon-base ti tabler-pin me-1"></i> Mühürle ve Panoya As
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

  // Inspect Quest Modal Trigger
  document.querySelectorAll('.btn-inspect-quest').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('inspectQuestTitle').textContent = this.dataset.title;
      const catEl = document.getElementById('inspectQuestCategory');
      catEl.textContent = this.dataset.category;
      catEl.className = 'stamp-tag ' + (
        this.dataset.category === 'Üye Talebi' ? 'stamp-member' :
        (this.dataset.category === 'Yan Görev' ? 'stamp-side' :
        (this.dataset.category === 'Sürekli Görev' ? 'stamp-recurring' : 'stamp-main'))
      );
      const statusEl = document.getElementById('inspectQuestStatus');
      statusEl.textContent = this.dataset.status;
      statusEl.className = 'badge rounded-pill font-monospace px-3 py-1 ' + (
        this.dataset.status === 'Aktif' ? 'bg-success' :
        (this.dataset.status === 'Askıya Alındı' ? 'bg-info' :
        (this.dataset.status === 'Tamamlandı' ? 'bg-danger' : 'bg-secondary'))
      );

      const eligEl = document.getElementById('inspectQuestEligibility');
      eligEl.textContent = this.dataset.eligibility;
      eligEl.className = 'badge-meta badge-eligibility ' + (this.dataset.badgeClass || 'badge-elig-senior');

      document.getElementById('inspectQuestCharacter').textContent = this.dataset.character;
      document.getElementById('inspectQuestLocation').textContent = '📍 ' + (this.dataset.location || 'Bilinmiyor');
      document.getElementById('inspectQuestReward').textContent = '🏆 ' + (this.dataset.reward || 'Standart Ödül');
      document.getElementById('inspectQuestDesc').textContent = this.dataset.description;

      const modal = new bootstrap.Modal(document.getElementById('modalInspectQuest'));
      modal.show();
    });
  });

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
        character: document.getElementById('inputQuestCharacter').value || 'Lonca',
        location: document.getElementById('inputQuestLocation').value,
        reward: document.getElementById('inputQuestReward').value,
        description: document.getElementById('inputQuestDesc').value
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
            title: 'Ferman Asıldı!',
            text: data.message,
            timer: 1600,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Hata!', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Hata!', text: 'Sunucuya ulaşılamadı.' });
      });
    });
  }

});
</script>
@endsection
