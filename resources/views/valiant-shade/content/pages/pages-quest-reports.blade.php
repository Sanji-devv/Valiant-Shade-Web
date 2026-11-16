@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Quest Reports') . ' - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* ====================================================
     VALIANT SHADE RPG MASTER SHEET DARK TABLE STYLES
     ==================================================== */
  
  .reports-hero-banner {
    background: linear-gradient(135deg, rgba(21, 24, 43, 0.96) 0%, rgba(32, 35, 62, 0.92) 50%, rgba(20, 22, 42, 0.97) 100%);
    border: 1px solid rgba(245, 158, 11, 0.35);
    border-radius: 16px;
    box-shadow: 0 10px 32px -6px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
  }
  .reports-hero-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #f59e0b 20%, #7367f0 80%, transparent);
  }

  .kpi-stat-card {
    background: rgba(16, 18, 32, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
  }
  .kpi-stat-card:hover {
    transform: translateY(-2px);
    border-color: rgba(115, 103, 240, 0.4);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    background: rgba(22, 25, 45, 0.88);
  }
  .kpi-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
  }
  .kpi-icon-gold {
    background: rgba(245, 158, 11, 0.16);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.35);
    box-shadow: 0 0 16px rgba(245, 158, 11, 0.2);
  }
  .kpi-icon-purple {
    background: rgba(115, 103, 240, 0.16);
    color: #7367f0;
    border: 1px solid rgba(115, 103, 240, 0.35);
    box-shadow: 0 0 16px rgba(115, 103, 240, 0.2);
  }
  .kpi-icon-cyan {
    background: rgba(0, 186, 209, 0.16);
    color: #00bad1;
    border: 1px solid rgba(0, 186, 209, 0.35);
    box-shadow: 0 0 16px rgba(0, 186, 209, 0.2);
  }
  .kpi-icon-green {
    background: rgba(40, 199, 111, 0.16);
    color: #28c76f;
    border: 1px solid rgba(40, 199, 111, 0.35);
    box-shadow: 0 0 16px rgba(40, 199, 111, 0.2);
  }
  .kpi-icon-red {
    background: rgba(234, 84, 85, 0.16);
    color: #ea5455;
    border: 1px solid rgba(234, 84, 85, 0.35);
    box-shadow: 0 0 16px rgba(234, 84, 85, 0.2);
  }

  /* Toolbar */
  .reports-toolbar {
    background: rgba(18, 20, 36, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 0.9rem 1.2rem;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(10px);
  }
  .filter-pill-btn {
    background: transparent;
    color: #9aa0c0;
    border: none;
    border-radius: 8px;
    padding: 0.42rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.18s ease;
    cursor: pointer;
  }
  .filter-pill-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.07);
  }
  .filter-pill-btn.active {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 12px rgba(115, 103, 240, 0.42);
  }

  /* Add Quest Report Button */
  .btn-add-quest-report {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    border: 1px solid rgba(115, 103, 240, 0.6) !important;
    border-radius: 10px !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    padding: 0.5rem 1.15rem !important;
    box-shadow: 0 4px 16px rgba(115, 103, 240, 0.4) !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .btn-add-quest-report:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(115, 103, 240, 0.6) !important;
    filter: brightness(1.1);
  }

  /* Table Dark Card Container */
  .card-table-dark-container {
    background: #181b2f !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 14px !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35) !important;
    overflow: hidden;
  }
  .card-table-dark-container .card-header {
    background: rgba(22, 25, 45, 0.92) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    padding: 1.1rem 1.35rem !important;
  }

  /* Master Sheet Table Layout */
  .table-responsive-master {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(115, 103, 240, 0.35) transparent;
  }
  .table-responsive-master::-webkit-scrollbar {
    height: 7px;
  }
  .table-responsive-master::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 4px;
  }
  .table-responsive-master::-webkit-scrollbar-thumb {
    background: rgba(115, 103, 240, 0.4);
    border-radius: 4px;
  }
  .table-responsive-master::-webkit-scrollbar-thumb:hover {
    background: rgba(115, 103, 240, 0.7);
  }

  .table-master-sheet {
    --bs-table-bg: transparent;
    --bs-table-color: #cbd0ec;
    border-color: rgba(255, 255, 255, 0.06);
    margin-bottom: 0;
    min-width: 1480px;
  }
  .table-master-sheet thead th {
    background: #1e223b !important;
    color: #8f95b5 !important;
    font-size: 0.71rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.6px !important;
    font-weight: 700 !important;
    padding: 0.85rem 0.95rem !important;
    border-bottom: 2px solid rgba(115, 103, 240, 0.35) !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
  }
  .table-master-sheet tbody tr.quest-row-item {
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }
  .table-master-sheet tbody tr.quest-row-item:hover {
    background: rgba(115, 103, 240, 0.1) !important;
  }
  .table-master-sheet tbody td {
    padding: 0.75rem 0.95rem !important;
    vertical-align: middle !important;
    white-space: nowrap;
    font-size: 0.86rem;
  }

  /* Specific Badges & Indicators */
  .badge-mq-epic {
    background: rgba(245, 158, 11, 0.18);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.45);
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
    font-weight: 800;
  }
  .badge-mq-main {
    background: rgba(115, 103, 240, 0.18);
    color: #7367f0;
    border: 1px solid rgba(115, 103, 240, 0.45);
    box-shadow: 0 0 10px rgba(115, 103, 240, 0.2);
    font-weight: 700;
  }
  .badge-mq-side {
    background: rgba(130, 134, 158, 0.16);
    color: #9ba0bc;
    border: 1px solid rgba(130, 134, 158, 0.3);
  }

  .badge-depo-ok {
    background: rgba(40, 199, 111, 0.16);
    color: #28c76f;
    border: 1px solid rgba(40, 199, 111, 0.35);
  }
  .badge-depo-fail {
    background: rgba(234, 84, 85, 0.16);
    color: #ea5455;
    border: 1px solid rgba(234, 84, 85, 0.35);
  }

  .badge-avg-xp {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.25) 0%, rgba(245, 158, 11, 0.25) 100%);
    border: 1px solid rgba(245, 158, 11, 0.45);
    color: #fcd34d;
    font-weight: 800;
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.2);
    font-size: 0.88rem;
    padding: 0.35rem 0.65rem;
  }

  .party-pill-tag {
    background: rgba(22, 25, 45, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 0.2rem 0.45rem;
    font-size: 0.75rem;
    color: #a5b4fc;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin: 2px;
  }

  /* Live Formula Card in Modals */
  .formula-engine-box {
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(31, 41, 55, 0.9) 100%);
    border: 1px solid rgba(115, 103, 240, 0.4);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    position: relative;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 4px 20px rgba(0, 0, 0, 0.4);
  }
  .formula-engine-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #7367f0, #f59e0b);
  }

  /* Player Average XP Distribution Cards & Calculator */
  .player-calc-badge {
    background: rgba(18, 22, 40, 0.85);
    border: 1px solid rgba(115, 103, 240, 0.28);
    border-radius: 8px;
    padding: 0.55rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
  }
  .player-calc-badge:hover {
    border-color: rgba(245, 158, 11, 0.45);
    background: rgba(26, 31, 56, 0.95);
    transform: translateY(-1px);
  }
  .player-calc-badge.reporter-badge {
    border-color: rgba(245, 158, 11, 0.5);
    background: rgba(245, 158, 11, 0.08);
  }
  .player-calc-xp {
    font-family: var(--bs-font-monospace);
    font-weight: 700;
    color: #f59e0b;
    font-size: 0.88rem;
    background: rgba(245, 158, 11, 0.15);
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    border: 1px solid rgba(245, 158, 11, 0.3);
  }
  .quick-adventurer-chip {
    cursor: pointer;
    background: rgba(255, 255, 255, 0.06);
    color: #cbd0ec;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    padding: 0.25rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.15s ease;
    user-select: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .quick-adventurer-chip:hover {
    background: rgba(115, 103, 240, 0.25);
    border-color: rgba(115, 103, 240, 0.5);
    color: #ffffff;
    transform: translateY(-1px);
  }
  .quick-adventurer-chip.active {
    background: rgba(115, 103, 240, 0.35);
    border-color: #7367f0;
    color: #ffffff;
    box-shadow: 0 0 10px rgba(115, 103, 240, 0.3);
  }
  .preset-btn-pill {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #cbd0ec;
    font-size: 0.72rem;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    transition: all 0.15s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
  }
  .preset-btn-pill:hover {
    background: rgba(245, 158, 11, 0.18);
    border-color: rgba(245, 158, 11, 0.45);
    color: #f59e0b;
  }
  .btn-calc-glow {
    position: relative;
    overflow: hidden;
  }
  .btn-calc-glow::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(60deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    transform: rotate(30deg);
    animation: glowSweep 3.5s infinite;
  }
  @keyframes glowSweep {
    0% { transform: translate(-100%, -100%) rotate(30deg); }
    30% { transform: translate(100%, 100%) rotate(30deg); }
    100% { transform: translate(100%, 100%) rotate(30deg); }
  }

  /* Modals */
  .modal-dossier {
    background: linear-gradient(135deg, #181a2e 0%, #1f223a 100%);
    border: 1px solid rgba(245, 158, 11, 0.35);
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85);
    color: #e2e5f5;
  }
  .modal-dialog-scrollable .modal-dossier.modal-content,
  .modal-dialog-scrollable form.modal-content.modal-dossier {
    max-height: calc(100vh - 3rem) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    border-radius: 16px !important;
  }
  .modal-dialog-scrollable form.modal-content.modal-dossier .modal-body {
    overflow-y: auto !important;
    flex: 1 1 auto !important;
    max-height: calc(100vh - 12.5rem) !important;
  }
  .modal-dossier .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.09);
    position: relative;
    background: rgba(24, 26, 46, 0.98);
  }
  .modal-dossier .modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #7367f0);
  }
  .modal-dossier .modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.12) !important;
    background: #17192d !important;
    position: sticky !important;
    bottom: 0 !important;
    z-index: 1055 !important;
    box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.45) !important;
  }
  .report-section-box {
    background: rgba(14, 16, 28, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
  }
  .party-roster-card {
    background: rgba(22, 25, 45, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 0.55rem 0.85rem;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .party-roster-card:hover {
    background: rgba(30, 34, 60, 0.95);
    border-color: rgba(115, 103, 240, 0.4);
    transform: translateY(-1px);
  }
  .party-roster-card.reporter-roster-card {
    background: rgba(245, 158, 11, 0.08);
    border-color: rgba(245, 158, 11, 0.35);
  }
  .btn-remove-roster-player {
    background: rgba(234, 84, 85, 0.14) !important;
    color: #ea5455 !important;
    border: 1px solid rgba(234, 84, 85, 0.35) !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 0.76rem !important;
    padding: 0.22rem 0.6rem !important;
    transition: all 0.18s ease !important;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
  }
  .btn-remove-roster-player:hover {
    background: #ea5455 !important;
    color: #ffffff !important;
    border-color: #ea5455 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(234, 84, 85, 0.4);
  }
  .quick-adv-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.3rem 0.65rem;
    border-radius: 20px;
    font-size: 0.75rem;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #c2c7e0;
    transition: all 0.2s ease;
    user-select: none;
  }
  .quick-adv-chip:hover {
    background: rgba(115, 103, 240, 0.2);
    border-color: rgba(115, 103, 240, 0.5);
    color: #ffffff;
    transform: translateY(-1px);
  }
  .quick-adv-chip.active {
    background: rgba(40, 199, 111, 0.18);
    border-color: rgba(40, 199, 111, 0.6);
    color: #28c76f;
    font-weight: 600;
  }
  .quick-adv-chip.active:hover {
    background: rgba(234, 84, 85, 0.2);
    border-color: rgba(234, 84, 85, 0.6);
    color: #ea5455;
  }

  /* Main Tab Switcher */
  .tab-nav-switch {
    background: transparent;
    color: #9aa0c0;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.05rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .tab-nav-switch:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
  }
  .tab-nav-switch.active {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 16px rgba(115, 103, 240, 0.45);
  }

  /* Guild Rank Badges */
  .badge-rank-bailiff {
    background: rgba(168, 85, 247, 0.2);
    color: #c084fc;
    border: 1px solid rgba(168, 85, 247, 0.45);
    box-shadow: 0 0 12px rgba(168, 85, 247, 0.25);
    font-weight: 700;
  }
  .badge-rank-uzman {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.5);
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.25);
    font-weight: 700;
  }
  .badge-rank-kidemli {
    background: rgba(115, 103, 240, 0.2);
    color: #a5b4fc;
    border: 1px solid rgba(115, 103, 240, 0.45);
    font-weight: 700;
  }
  .badge-rank-uye {
    background: rgba(0, 186, 209, 0.2);
    color: #67e8f9;
    border: 1px solid rgba(0, 186, 209, 0.4);
    font-weight: 600;
  }
  .badge-rank-clerk {
    background: rgba(40, 199, 111, 0.2);
    color: #6ee7b7;
    border: 1px solid rgba(40, 199, 111, 0.4);
    font-weight: 600;
  }
  .badge-rank-aday {
    background: rgba(148, 163, 184, 0.16);
    color: #cbd5e1;
    border: 1px solid rgba(148, 163, 184, 0.3);
    font-weight: 600;
  }
  .badge-rank-inaktif {
    background: rgba(100, 116, 139, 0.16);
    color: #94a3b8;
    border: 1px solid rgba(100, 116, 139, 0.3);
  }

  /* Pagination Styling */
  #reportsPaginationContainer {
    background: rgba(18, 20, 36, 0.95);
    backdrop-filter: blur(10px);
  }
  #reportsPaginationList .page-item .page-link {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #cbd5e1;
    min-width: 36px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
    font-size: 0.82rem;
    border-radius: 6px;
    margin: 0 2px;
    transition: all 0.2s ease;
    cursor: pointer;
  }
  #reportsPaginationList .page-item .page-link:hover {
    background: rgba(115, 103, 240, 0.25);
    border-color: #7367f0;
    color: #fff;
  }
  #reportsPaginationList .page-item.active .page-link {
    background: linear-gradient(135deg, #7367f0 0%, #5e50ee 100%) !important;
    border-color: #7367f0 !important;
    color: #fff !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(115, 103, 240, 0.4);
  }
  #reportsPaginationList .page-item.disabled .page-link {
    background: transparent;
    border-color: transparent;
    color: #64748b;
    cursor: default;
  }
  .btn-pagination-action {
    transition: all 0.2s ease;
  }
  .btn-pagination-action:hover:not(:disabled) {
    transform: translateY(-1px);
  }

  .quest-report-editor .formula-engine-box,
  .quest-report-editor .report-section-box {
    background: var(--bs-body-bg) !important;
    border: 1px solid var(--bs-border-color) !important;
    border-radius: 0.5rem;
    box-shadow: none;
  }
  .quest-report-editor .formula-engine-box::before {
    display: none;
  }
  .quest-report-editor .badge-avg-xp {
    background: rgba(var(--bs-primary-rgb), 0.16);
    border: 0;
    box-shadow: none;
    color: var(--bs-primary);
  }
  .quest-report-editor .bg-dark,
  .quest-report-editor .bg-black,
  .quest-report-editor .bg-light {
    background-color: var(--bs-body-bg) !important;
  }
  .quest-report-editor .form-control,
  .quest-report-editor .form-select,
  .quest-report-editor .input-group-text {
    border-color: var(--bs-border-color) !important;
    color: var(--bs-body-color);
  }
  .quest-report-editor .form-control:focus,
  .quest-report-editor .form-select:focus {
    border-color: var(--bs-primary) !important;
  }
  .quest-report-editor .form-control[readonly] {
    background-color: var(--bs-body-bg) !important;
  }
  .quest-report-editor .text-light {
    color: var(--bs-body-color) !important;
  }
  .quest-report-editor .party-roster-card,
  .quest-report-editor .party-roster-card.reporter-roster-card {
    background: var(--bs-body-bg);
    border-color: var(--bs-border-color);
    box-shadow: none;
    transform: none;
  }
  .quest-report-editor .party-roster-card:hover {
    background: var(--bs-body-bg);
    border-color: var(--bs-primary);
    transform: none;
  }
  .quest-report-editor .btn-remove-roster-player {
    background: transparent !important;
    color: var(--bs-danger) !important;
    border-color: var(--bs-danger) !important;
    box-shadow: none !important;
  }
  .quest-report-editor .btn-remove-roster-player:hover {
    background: var(--bs-danger) !important;
    color: #fff !important;
    transform: none;
  }
  .quest-report-editor .modal-body {
    min-height: 0;
  }
  .quest-form-grid .row > [class*="col-"] {
    flex: 0 0 auto;
    width: 100%;
  }
  .reports-hero-banner,
  .reports-toolbar,
  .card-table-dark-container {
    background: var(--bs-card-bg) !important;
    border: 1px solid var(--bs-border-color) !important;
    border-radius: 0.5rem !important;
    box-shadow: none !important;
    backdrop-filter: none;
  }
  .reports-hero-banner::before { display: none; }
  .card-table-dark-container .card-header {
    background: transparent !important;
    border-color: var(--bs-border-color) !important;
  }
  .kpi-stat-card {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.14), var(--bs-card-bg) 85%);
    border-color: rgba(115, 103, 240, 0.32);
    border-radius: 0.5rem;
    box-shadow: none;
    height: 100%;
  }
  .kpi-stat-card:hover {
    background: linear-gradient(135deg, rgba(115, 103, 240, 0.22), var(--bs-card-bg) 85%);
    box-shadow: none;
    transform: none;
  }
  .reports-toolbar { padding: 1rem; }
  .reports-toolbar-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(115, 103, 240, 0.4) transparent;
  }
  .reports-toolbar-search { flex: 1 0 180px; max-width: 360px; }
  .reports-toolbar-search .form-control,
  .reports-toolbar-search .input-group-text,
  .reports-toolbar-search .btn,
  .reports-toolbar-controls .form-select { min-height: 40px; }
  #reportsTypeFilters .filter-pill-btn { padding: 0.42rem 0.45rem; }
  #reportsTypeFilters,
  .reports-toolbar-controls {
    flex: 0 0 auto;
    flex-wrap: nowrap !important;
    white-space: nowrap;
  }
  .tab-nav-switch,
  .filter-pill-btn { color: var(--bs-body-color); }
  .tab-nav-switch:hover,
  .filter-pill-btn:hover { color: var(--bs-heading-color); background: var(--bs-body-bg); }
  .table-master-sheet { --bs-table-color: var(--bs-body-color); }
  #guildRanksTable thead th {
    background: var(--bs-body-bg) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--bs-border-color) !important;
  }
  .reports-action {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .reports-action-icon {
    width: 40px;
    height: 40px;
    padding: 0;
  }
  .reports-list-table {
    min-width: 1900px;
    width: 100%;
  }
  .reports-list-table thead th {
    background: rgba(115, 103, 240, 0.14) !important;
    color: var(--bs-body-color) !important;
    border-color: rgba(115, 103, 240, 0.35) !important;
  }
  .reports-list-table tbody td {
    vertical-align: middle !important;
    padding: 0.65rem 0.75rem !important;
  }
  .reports-list-table .badge,
  .reports-list-table .party-pill-tag {
    white-space: nowrap;
  }
  .reports-list-table .party-pill-tag {
    background: rgba(115, 103, 240, 0.12);
    border-color: rgba(115, 103, 240, 0.25);
    color: var(--bs-body-color);
  }
  .reports-list-table tbody td:nth-child(2) { min-width: 220px; }
  .reports-list-table tbody td:nth-child(15) { min-width: 200px; white-space: normal; }
  .reports-list-table tbody tr.quest-row-item > td:nth-child(16) > div {
    display: flex !important;
    flex-wrap: nowrap;
    justify-content: flex-end;
  }
  .reports-list-table tbody tr.quest-row-item > td:nth-child(16) .btn {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    padding: 0;
  }
  #reportsPaginationContainer { background: var(--bs-card-bg); backdrop-filter: none; }
  #reportsPaginationContainer .btn,
  #reportsPaginationList .page-link { min-height: 40px; }
  #guildRanksTable { min-width: 0; table-layout: fixed; width: 100%; }
  #guildRanksTable th,
  #guildRanksTable td {
    width: auto !important;
    min-width: 0 !important;
    white-space: normal !important;
    overflow-wrap: anywhere;
  }
  #guildRanksTable tfoot tr { background: var(--bs-body-bg) !important; }
  @media (max-width: 991.98px) {
    #guildRanksTable,
    #guildRanksTable tbody,
    #guildRanksTable tfoot { display: block; width: 100%; }
    #guildRanksTable thead { display: none; }
    #guildRanksTable tr {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      margin: 0.75rem;
      border: 1px solid var(--bs-border-color);
      border-radius: 0.5rem;
    }
    #guildRanksTable td,
    #guildRanksTable tfoot th { display: block; text-align: left !important; border: 0; }
    #guildRanksTable tbody td::before {
      display: block;
      color: var(--bs-secondary-color);
      font-size: 0.7rem;
      font-weight: normal;
    }
    #guildRanksTable tbody td:nth-child(1)::before { content: @json(__('Rank'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tbody td:nth-child(2)::before { content: @json(__('Capacity'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tbody td:nth-child(3)::before { content: @json(__('Players'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tbody td:nth-child(4)::before { content: @json(__('Capacity XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tbody td:nth-child(5)::before { content: @json(__('Current XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tbody td:nth-child(6)::before { content: @json(__('Net XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tbody td:nth-child(7)::before { content: @json(__('Spent XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot tr:first-child th:not(:first-child)::before {
      display: block;
      color: var(--bs-secondary-color);
      font-size: 0.7rem;
      font-weight: normal;
    }
    #guildRanksTable tfoot tr:first-child th:nth-child(2)::before { content: @json(__('Capacity'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot tr:first-child th:nth-child(3)::before { content: @json(__('Players'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot tr:first-child th:nth-child(4)::before { content: @json(__('Capacity XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot tr:first-child th:nth-child(5)::before { content: @json(__('Current XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot tr:first-child th:nth-child(6)::before { content: @json(__('Net XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot tr:first-child th:nth-child(7)::before { content: @json(__('Spent XP'), JSON_UNESCAPED_UNICODE); }
    #guildRanksTable tfoot th:first-child { grid-column: 1 / -1; }
  }
  @media (max-width: 390px) {
    #guildRanksTable tr { grid-template-columns: minmax(0, 1fr); }
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'
], 'static/valiant-shade/build')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- ====================================================
       TOP NAVIGATION TABS (QUEST REPORTS vs. GUILD XP POOL)
       ==================================================== -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div class="d-inline-flex align-items-center flex-wrap p-1 rounded-3 border" id="questReportsMainTabs">
      <button type="button" class="tab-nav-switch active" id="tabNavQuests" data-tab-target="#tabViewQuests">
        <i class="icon-base ti tabler-file-text fs-5"></i>
        <span>{{ __('Quest Reports') }}</span>
        <span class="badge bg-primary ms-1 font-monospace" id="navTabQuestCount">{{ count($reports) }}</span>
      </button>
      <button type="button" class="tab-nav-switch" id="tabNavGuildPool" data-tab-target="#tabViewGuildPool">
        <i class="icon-base ti tabler-shield-dollar fs-5"></i>
        <span>{{ __('Guild XP & Rank Pool') }}</span>
        <span class="badge bg-label-warning ms-1 font-monospace" id="navTabPoolGrandBadge">{{ number_format($guildPoolData['grand_total_xp'] ?? 3459587.50, 0) }} XP</span>
      </button>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-primary btn-sm reports-action btn-add-report-trigger" id="btnTopOpenAddReportModal" data-bs-toggle="modal" data-bs-target="#modalAddQuestReport">
        <i class="icon-base ti tabler-plus me-1"></i>
        <span>{{ __('Add Quest Report') }}</span>
      </button>
      <a href="{{ route('valiant-shade.pages-xp-tracker') }}" class="btn btn-label-secondary btn-sm reports-action">
        <i class="icon-base ti tabler-award me-1"></i> {{ __('XP Tracker') }}
      </a>
      <div class="dropdown">
        <button type="button" class="btn btn-label-secondary btn-sm reports-action dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="icon-base ti tabler-dots me-1"></i>{{ __('Actions') }}
        </button>
        <div class="dropdown-menu dropdown-menu-end">
          @if(auth()->user()?->isAdmin())
            <input type="file" id="questReportsJsonFile" accept=".json,application/json" class="d-none">
            <button type="button" class="dropdown-item" id="btnImportQuestReports">
              <i class="icon-base ti tabler-database-import me-2"></i>{{ __('Import Data') }}
            </button>
          @endif
          <button type="button" class="dropdown-item" id="btnResetGuildPoolDefaults">
            <i class="icon-base ti tabler-refresh me-2"></i>{{ __('Reset Guild Pool') }}
          </button>
          @if(auth()->user()?->isAdmin())
            <button type="button" class="dropdown-item text-danger" id="btnClearQuestReports">
              <i class="icon-base ti tabler-trash me-2"></i>{{ __('Delete All Reports') }}
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- ====================================================
       TAB VIEW 1: QUEST REPORTS TABLE (COLUMNS A..BG)
       ==================================================== -->
  <div id="tabViewQuests" class="tab-pane-view">

  <!-- ====================================================
       1. HERO KPI BANNER & METRIC CARDS
       ==================================================== -->
  <div class="card reports-hero-banner p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="avatar avatar-sm bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-file-text fs-4 text-warning"></i>
          </div>
          <h3 class="mb-0 fw-bold text-heading" style="font-size: 1.45rem; letter-spacing: 0.3px;">
            {{ __('Quest Reports') }}
          </h3>
          <span class="badge bg-primary font-monospace px-3 py-1 shadow-sm fs-6" id="kpiTotalReportsBadge">
            {{ $stats['total_quests'] }} {{ __('Recorded Expeditions') }}
          </span>
        </div>
        <p class="text-body-secondary small mb-0">
          {{ __('Quest Report Archive') }} &bull; {{ __('Automatic formula-calculated expedition reports, guild & depot shares and player average XP table.') }}
        </p>
      </div>

    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3">
      <!-- KPI 1: Toplam Sefer -->
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-purple shadow-sm">
            <i class="icon-base ti tabler-compass"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Toplam Sefer') }}</div>
            <div class="fw-bolder fs-5 text-heading font-monospace" id="kpiTotalQuestsVal">{{ $stats['total_quests'] }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 2: Main & Epic Quests -->
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-gold shadow-sm">
            <i class="icon-base ti tabler-crown"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Ana / Epik') }}</div>
            <div class="fw-bolder fs-5 text-warning font-monospace" id="kpiMainQuestsVal">{{ $stats['main_quests'] + $stats['epic_quests'] }} <span class="small text-muted font-normal" style="font-size: 0.72rem;">({{ $stats['epic_quests'] }} Epik)</span></div>
          </div>
        </div>
      </div>

      <!-- KPI 3: Side Quests -->
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-cyan shadow-sm">
            <i class="icon-base ti tabler-sparkles"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Side Quests') }}</div>
            <div class="fw-bolder fs-5 text-info font-monospace" id="kpiSideQuestsVal">{{ $stats['side_quests'] }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 4: Total Distributed XP -->
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-green shadow-sm">
            <i class="icon-base ti tabler-award"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Toplam XP (E)') }}</div>
            <div class="fw-bolder fs-5 text-success font-monospace" id="kpiTotalXpVal">+{{ number_format($stats['total_xp']) }}</div>
          </div>
        </div>
      </div>

      <!-- KPI 5: Depo Kesintisi (%20) -->
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-stat-card d-flex align-items-center gap-3">
          <div class="kpi-icon-wrapper kpi-icon-red shadow-sm">
            <i class="icon-base ti tabler-building-warehouse"></i>
          </div>
          <div>
            <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Depo Kesintisi (H)') }}</div>
            <div class="fw-bolder fs-5 text-danger font-monospace" id="kpiTotalDepoCutVal">{{ number_format($stats['total_depo_cut'], 1) }} SP</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ====================================================
       2. TOOLBAR & FORMULA ENGINE FILTERS
       ==================================================== -->
  <div class="card reports-toolbar mb-4">
    <div class="reports-toolbar-row">
      <!-- Search Input -->
      <div class="reports-toolbar-search">
        <div class="input-group input-group-merge">
          <span class="input-group-text bg-transparent border-secondary border-opacity-25"><i class="icon-base ti tabler-search text-muted"></i></span>
          <input type="text" class="form-control bg-transparent border-secondary border-opacity-25 text-heading" id="reportsSearchInput" placeholder="{{ __('Quest, GM, Reporter, Players...') }}" value="{{ $filters['search'] ?? '' }}" />
          <button type="button" class="btn btn-outline-secondary border-secondary border-opacity-25 d-none" id="btnReportsSearchClear" title="{{ __('Clear Search') }}">
            <i class="icon-base ti tabler-x fs-6"></i>
          </button>
        </div>
      </div>

      <!-- Quick Type Filter Buttons -->
      <div class="d-flex align-items-center gap-1 flex-wrap p-1 rounded-3 border" id="reportsTypeFilters">
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? 'all') === 'all' ? 'active' : '' }}" data-type="all">
          {{ __('All') }} (<span id="typeCountAll">{{ $stats['total_quests'] }}</span>)
        </button>
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? '') === 'main' ? 'active' : '' }}" data-type="main">
          {{ __('Main') }} (<span id="typeCountMain">{{ $stats['main_quests'] }}</span>)
        </button>
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? '') === 'epic' ? 'active' : '' }}" data-type="epic">
          {{ __('Epic') }} (<span id="typeCountEpic">{{ $stats['epic_quests'] }}</span>)
        </button>
        <button type="button" class="filter-pill-btn {{ ($filters['type'] ?? '') === 'side' ? 'active' : '' }}" data-type="side">
          {{ __('Side') }} (<span id="typeCountSide">{{ $stats['side_quests'] }}</span>)
        </button>
      </div>

      <!-- GM & Level Dropdowns -->
      <div class="d-flex align-items-center gap-2 reports-toolbar-controls">
        <!-- GM Filter -->
        <select class="form-select form-select-sm bg-transparent border-secondary border-opacity-25 text-heading" id="reportsGmFilter" style="width: auto;">
          <option value="all">{{ __("All GMs") }}</option>
          @foreach($gms as $gm)
            <option value="{{ $gm }}" {{ ($filters['gm'] ?? '') === $gm ? 'selected' : '' }}>{{ $gm }}</option>
          @endforeach
        </select>

        <!-- Level Filter -->
        <select class="form-select form-select-sm bg-transparent border-secondary border-opacity-25 text-heading" id="reportsLevelFilter" style="width: auto;">
          <option value="all">{{ __('All Levels') }}</option>
          <option value="1">Lvl 1</option>
          <option value="2">Lvl 2</option>
          <option value="3">Lvl 3</option>
          <option value="4">Lvl 4</option>
          <option value="5">Lvl 5+</option>
        </select>

        <!-- Sort Select -->
        <select class="form-select form-select-sm bg-transparent border-secondary border-opacity-25 text-heading" id="reportsSortSelect" style="width: auto;">
          <option value="date-asc">{{ __('Date: Oldest First') }}</option>
          <option value="date-desc">{{ __('Date: Newest First') }}</option>
          <option value="xp-desc">{{ __('Total XP: Highest') }}</option>
          <option value="avg-desc">{{ __('Average XP: Highest') }}</option>
          <option value="title-asc">{{ __('Quest Name: A-Z') }}</option>
        </select>

        <!-- Reset Button -->
        <button type="button" class="btn btn-sm btn-outline-secondary reports-action reports-action-icon" id="btnResetReportsFilters" title="{{ __('Reset Filters') }}" aria-label="{{ __('Reset Filters') }}">
          <i class="icon-base ti tabler-rotate-clockwise"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- ====================================================
       3. MASTER SPREADSHEET TABLE
       (Quest Title | Date | MQ | Level | XP | Vault | Guild | Vault | Report | Extra | Average | Player | GM | Reporter | Other Players | Actions)
       ==================================================== -->
  <div class="card card-table-dark-container mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-table text-primary fs-4"></i>
        <h5 class="mb-0 fw-bold text-heading" style="font-size: 1.08rem;">
          {{ __('Quest Reports') }}
        </h5>
        <span class="badge bg-primary font-monospace ms-2" id="currentFilteredCount">
          {{ count($reports) }}
        </span>
      </div>

    </div>

    <div class="table-responsive-master">
      <table class="table table-master-sheet reports-list-table">
        <thead>
          <tr>
            <th scope="col">{{ __('ID') }}</th>
            <th scope="col">{{ __('Quest Name') }}</th>
            <th scope="col">{{ __('Date') }}</th>
            <th scope="col">{{ __('Quest Type') }}</th>
            <th scope="col">{{ __('Level') }}</th>
            <th scope="col">{{ __('Total XP') }}</th>
            <th scope="col">{{ __('Guild Cut') }}</th>
            <th scope="col">{{ __('Depot Cut') }}</th>
            <th scope="col">{{ __('Report XP') }}</th>
            <th scope="col">{{ __('Extra Reward') }}</th>
            <th scope="col">{{ __('Average XP') }}</th>
            <th scope="col">{{ __('Players') }}</th>
            <th scope="col">GM</th>
            <th scope="col">{{ __('Reporter') }}</th>
            <th scope="col">{{ __('Other Players') }}</th>
            <th scope="col">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0" id="reportsTableBody">
          @php
            $formatReportXp = static fn ($value) => rtrim(rtrim(number_format((float) ($value ?? 0), 4, '.', ','), '0'), '.');
          @endphp
          @forelse(collect($reports)->take(50) as $idx => $q)
            <tr class="quest-row-item report-row-item"
                data-id="{{ $q['id'] }}"
                data-type="{{ $q['mq'] ?? ($q['is_main'] ? 'main' : 'side') }}"
                data-gm="{{ mb_strtolower($q['gm'] ?? '') }}"
                data-level="{{ $q['level'] ?? 1 }}"
                data-xp="{{ $q['total_xp'] ?? 0 }}"
                data-avg="{{ $q['avg_reward'] ?? 0 }}"
                data-date="{{ $q['date'] }}"
                data-title="{{ mb_strtolower($q['title'] ?? '') }}">
              
              <!-- 0. SIRA NO -->
              <td class="text-center text-body-secondary font-monospace" style="font-size: 0.78rem;">
                {{ $q['id'] }}
              </td>

              <!-- 1. GÖREV ADI (A) -->
              <td>
                <div class="d-flex align-items-center">
                  <div>
                    <span class="fw-bold text-heading quest-title-text d-block" style="cursor: pointer;" onclick="openReportDetail({{ $q['id'] }})">
                      {{ $q['title'] }}
                    </span>
                    <small class="text-body-secondary font-monospace" style="font-size: 0.75rem;">
                      {{ $q['location'] ?? 'Siwion' }}
                    </small>
                  </div>
                </div>
              </td>

              <!-- 2. TARİH (B) -->
              <td>
                <span class="font-monospace text-body-secondary fw-semibold">
                  {{ $q['date'] }}
                </span>
              </td>

              <!-- 3. MQ (C) -->
              <td class="text-center">
                @if(($q['mq'] ?? '') === 'epic')
                  <span class="badge badge-mq-epic font-monospace px-2 py-1">{{ __('Epic') }}</span>
                @elseif(!empty($q['is_main']) || ($q['mq'] ?? '') === 'main')
                  <span class="badge badge-mq-main font-monospace px-2 py-1">{{ __('Main') }}</span>
                @else
                  <span class="badge badge-mq-side font-monospace px-2 py-1">{{ __('Side') }}</span>
                @endif
              </td>

              <!-- 4. LEVEL (D) -->
              <td class="text-center font-monospace fw-bold text-info">
                {{ $q['level'] }}
              </td>

              <!-- 5. XP (E) -->
              <td class="text-end font-monospace fw-bold text-success">
                {{ $formatReportXp($q['total_xp']) }}
              </td>

              <!-- 7. LONCA KESİNTİSİ (G) -->
              <td class="text-end font-monospace text-warning fw-semibold">
                {{ $formatReportXp($q['guild_cut']) }}
              </td>

              <!-- 8. DEPO KESİNTİSİ (H) -->
              <td class="text-end font-monospace {{ (float)$q['depo_cut'] > 0 ? 'text-danger fw-bold' : 'text-body-secondary' }}">
                {{ $formatReportXp($q['depo_cut']) }}
              </td>

              <!-- 9. RAPOR KESİNTİSİ (I) -->
              <td class="text-end font-monospace text-heading">
                {{ $formatReportXp($q['rapor_cut'] ?? 0) }}
              </td>

              <!-- 10. EK ÖDÜL (J) -->
              <td class="text-end font-monospace text-info">
                {{ $formatReportXp($q['extra_reward'] ?? 0) }}
              </td>

              <!-- 11. ORTALAMA XP (K) -->
              <td class="text-end">
                <span class="badge badge-avg-xp font-monospace">
                  {{ $formatReportXp($q['avg_reward']) }}
                </span>
              </td>

              <!-- 12. OYUNCU SAYISI (AL) -->
              <td class="text-center font-monospace fw-bold text-heading">
                {{ $q['player_count'] }}
              </td>

              <!-- 13. GM (AM) -->
              <td>
                <span class="badge bg-label-warning font-monospace">
                  {{ $q['gm'] }}
                </span>
              </td>

              <!-- 14. RAPORLAYAN (AN) -->
              <td>
                <span class="badge bg-label-info font-monospace text-info fw-bold">
                  {{ $q['reporter'] }}
                </span>
              </td>

              <!-- 15. DİĞER OYUNCULAR (AO..BG) -->
              <td>
                <div class="d-flex align-items-center flex-wrap gap-1">
                  @foreach($q['other_players'] ?? [] as $other)
                    <span class="party-pill-tag">
                      <span>{{ $other }}</span>
                    </span>
                  @endforeach
                </div>
              </td>

              <!-- 16. İŞLEMLER (ACTIONS) -->
              <td class="text-end">
                <div class="d-inline-flex align-items-center gap-1">
                  <!-- Detay -->
                  <button type="button" class="btn btn-icon btn-sm btn-outline-primary btn-open-report" onclick="openReportDetail({{ $q['id'] }})" title="{{ __('Report Details') }}" aria-label="{{ __('Report Details') }}">
                    <i class="icon-base ti tabler-file-text fs-6"></i>
                  </button>

                  <!-- Düzenle -->
                  <button type="button" class="btn btn-icon btn-sm btn-outline-warning" onclick="openEditReportModal({{ $q['id'] }})" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                    <i class="icon-base ti tabler-edit fs-6"></i>
                  </button>

                  <!-- XP Tracker'a Dağıt -->
                  <button type="button" class="btn btn-icon btn-sm btn-outline-success" onclick="distributeReportXp({{ $q['id'] }})" title="{{ __('Distribute XP to Adventurers (XP Tracker)') }}" aria-label="{{ __('Distribute XP to Adventurers (XP Tracker)') }}">
                    <i class="icon-base ti tabler-bolt fs-6"></i>
                  </button>

                  <!-- Sil -->
                  <button type="button" class="btn btn-icon btn-sm btn-outline-danger" onclick="deleteReportItem({{ $q['id'] }})" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                    <i class="icon-base ti tabler-trash fs-6"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="16" class="text-center py-5">
                <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-file-search fs-1 text-muted"></i>
                </div>
                <h5 class="fw-bold text-heading">{{ __('No quest reports found matching the criteria.') }}</h5>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-3 py-3 border-top border-secondary border-opacity-20" id="reportsPaginationContainer">
      <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary small">
        <span class="badge bg-label-secondary font-monospace"><i class="icon-base ti tabler-files me-1"></i>{{ __('50 reports per page') }}</span>
        <span class="opacity-50">|</span>
        <span id="paginationInfoText" class="font-monospace text-heading">
          {{ __('Loading...') }}
        </span>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Quick First Page -->
        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 btn-pagination-action" id="btnPageFirst" title="{{ __('Go to First Page (Page 1)') }}">
          <i class="icon-base ti tabler-chevrons-left fs-6"></i>
          <span class="d-none d-sm-inline">{{ __('First') }}</span>
        </button>

        <!-- Previous Page -->
        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 btn-pagination-action" id="btnPagePrev" title="{{ __('Previous Page') }}">
          <i class="icon-base ti tabler-chevron-left fs-6"></i>
          <span class="d-none d-sm-inline">{{ __('Previous') }}</span>
        </button>

        <!-- Dynamic Numeric Pagination Buttons -->
        <ul class="pagination pagination-sm mb-0 flex-wrap" id="reportsPaginationList">
          <!-- Populated by JS -->
        </ul>

        <!-- Next Page -->
        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 btn-pagination-action" id="btnPageNext" title="{{ __('Next Page') }}">
          <span class="d-none d-sm-inline">{{ __('Next') }}</span>
          <i class="icon-base ti tabler-chevron-right fs-6"></i>
        </button>

        <!-- Sona Git Button (Prominent) -->
        <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm px-3 btn-pagination-action" id="btnPageLast" title="{{ __('Last Page') }}">
          <span class="fw-bold">{{ __('Last') }}</span>
          <i class="icon-base ti tabler-player-track-next fs-6"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Empty Search Result Container -->
  <div id="reportsEmptyState" class="card border border-secondary border-opacity-20 text-center py-5 d-none mb-4">
    <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
      <i class="icon-base ti tabler-file-search fs-1 text-muted"></i>
    </div>
    <h5 class="fw-bold text-heading">{{ __('No quest reports matching your filter criteria were found.') }}</h5>
    <p class="text-body-secondary small mb-3">{{ __('You can search again by clearing your filters or trying a different search term.') }}</p>
    <div>
      <button type="button" class="btn btn-outline-primary btn-sm" id="btnEmptyResetFilters">
        <i class="icon-base ti tabler-rotate-clockwise me-1"></i> {{ __('Reset Filters') }}
      </button>
    </div>
  </div>
  </div>
  <!-- /#tabViewQuests -->

  <!-- ====================================================
       TAB VIEW 2: GUILD XP & RANKS POOL (COLUMNS BU..CA)
       ==================================================== -->
  <div id="tabViewGuildPool" class="tab-pane-view d-none">
    
    <!-- Hero Banner for Guild Pool -->
    <div class="card reports-hero-banner p-3 p-md-4 mb-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <div class="avatar avatar-sm bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-shield-dollar fs-4 text-warning"></i>
            </div>
            <h3 class="mb-0 fw-bold text-heading" style="font-size: 1.45rem; letter-spacing: 0.3px;">
              {{ __('Guild XP & Rank Pool') }}
            </h3>
            <span class="badge bg-warning text-dark font-monospace px-3 py-1 shadow-sm fs-6" id="poolLevelBadge">
              {{ __('Guild Level :level', ['level' => $guildPoolData['guild_xp']['level'] ?? 12]) }}
            </span>
          </div>
          <p class="text-body-secondary small mb-0">
            {{ __('Guild XP, expert pool and rank allocations.') }}
          </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <!-- Edit Guild Pool Data -->
          <button type="button" class="btn btn-primary btn-sm reports-action" id="btnOpenEditGuildPoolModal">
            <i class="icon-base ti tabler-edit fs-5"></i>
            <span>{{ __('Edit Pool & Rank Data') }}</span>
          </button>
        </div>
      </div>

      <!-- 4 Hero Metric KPI Cards -->
      <div class="row g-3">
        <!-- KPI 1: Wiki XP Paneli -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="kpi-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="kpi-icon-wrapper kpi-icon-cyan" style="width: 38px; height: 38px; font-size: 1.15rem;">
                  <i class="icon-base ti tabler-book"></i>
                </div>
                <div>
                  <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Wiki XP (BW..BY)') }}</div>
                  <div class="fw-bolder fs-5 text-info font-monospace" id="kpiWikiRemainingVal">
                    {{ number_format($guildPoolData['wiki_xp']['remaining'] ?? 14253, 2) }} XP
                  </div>
                </div>
              </div>
              <span class="badge bg-label-info font-monospace small" title="=BW2-BX2">Kalan (BY2)</span>
            </div>
            <div class="d-flex justify-content-between text-body-secondary small font-monospace pt-1 border-top border-secondary border-opacity-10" style="font-size: 0.75rem;">
              <span>Total (BW): <b class="text-heading" id="kpiWikiTotalVal">{{ number_format($guildPoolData['wiki_xp']['total'] ?? 53800, 2) }}</b></span>
              <span>{{ __("Distributed (BX):") }} <b class="text-warning" id="kpiWikiDistVal">{{ number_format($guildPoolData['wiki_xp']['distributed'] ?? 39547, 2) }}</b></span>
            </div>
          </div>
        </div>

        <!-- KPI 2: Lonca XP & Seviye -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="kpi-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="kpi-icon-wrapper kpi-icon-gold" style="width: 38px; height: 38px; font-size: 1.15rem;">
                  <i class="icon-base ti tabler-shield"></i>
                </div>
                <div>
                  <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Lonca XP & Level') }}</div>
                  <div class="fw-bolder fs-5 text-warning font-monospace" id="kpiGuildXpTotalVal">
                    {{ number_format($guildPoolData['guild_xp']['total'] ?? 157150, 2) }} XP
                  </div>
                </div>
              </div>
              <span class="badge bg-label-warning font-monospace small" id="kpiGuildXpLevelVal">Lvl {{ $guildPoolData['guild_xp']['level'] ?? 12 }}</span>
            </div>
            <div class="d-flex justify-content-between text-body-secondary small font-monospace pt-1 border-top border-secondary border-opacity-10" style="font-size: 0.75rem;">
              <span>{{ __('Target') }}: <b class="text-heading" id="kpiGuildTargetVal">{{ number_format($guildPoolData['guild_xp']['next_level_threshold'] ?? 0, 2) }}</b></span>
              <span>{{ __('Base') }}: <b class="text-muted" id="kpiGuildBaseVal">{{ number_format($guildPoolData['guild_xp']['base'] ?? 0, 2) }}</b></span>
            </div>
            <div class="text-body-secondary small mt-1">
              {{ __('Remaining') }}: <b class="text-heading" id="kpiGuildRemainingVal">{{ number_format($guildPoolData['guild_xp']['remaining'] ?? 0, 2) }}</b> XP
            </div>
          </div>
        </div>

        <!-- KPI 3: Havuz & Uzman Payı -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="kpi-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="kpi-icon-wrapper kpi-icon-green" style="width: 38px; height: 38px; font-size: 1.15rem;">
                  <i class="icon-base ti tabler-coins"></i>
                </div>
                <div>
                  <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Expert Share (=BV5/BW10)') }}</div>
                  <div class="fw-bolder fs-5 text-success font-monospace" id="kpiPoolPayVal">
                    {{ number_format($guildPoolData['pool_share_uzman'] ?? 98295.22, 2) }} XP
                  </div>
                </div>
              </div>
              <span class="badge bg-label-success font-monospace small" id="kpiExpertCountVal">{{ collect($guildPoolData['ranks'])->firstWhere('rank', 'Uzman')['current_count'] ?? 0 }} {{ __('Experts') }}</span>
            </div>
            <div class="d-flex justify-content-between text-body-secondary small font-monospace pt-1 border-top border-secondary border-opacity-10" style="font-size: 0.75rem;">
              <span>Havuz (BV5): <b class="text-heading" id="kpiPoolTotalVal">{{ number_format($guildPoolData['pool_total'] ?? 2064199.61, 2) }}</b></span>
              <span class="text-success fw-semibold">{{ __("Net Share/Person") }}</span>
            </div>
          </div>
        </div>

        <!-- KPI 4: Mali Bakiye & Rütbeler Toplamı -->
        <div class="col-12 col-md-6 col-xl-3">
          <div class="kpi-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="kpi-icon-wrapper kpi-icon-purple" style="width: 38px; height: 38px; font-size: 1.15rem;">
                  <i class="icon-base ti tabler-scale"></i>
                </div>
                <div>
                  <div class="text-body-secondary small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">{{ __('Genel Toplam (=CA7+CA4)') }}</div>
                  <div class="fw-bolder fs-5 text-primary font-monospace" id="kpiGrandTotalVal">
                    {{ number_format($guildPoolData['grand_total_xp'] ?? 3459587.50, 2) }} XP
                  </div>
                </div>
              </div>
              <span class="badge bg-label-primary font-monospace small">Genel Bakiye</span>
            </div>
            <div class="d-flex justify-content-between text-body-secondary small font-monospace pt-1 border-top border-secondary border-opacity-10" style="font-size: 0.75rem;">
              <span>{{ __("Ranks (CA7):") }} <b class="text-heading" id="kpiRanksTotalVal">{{ number_format($guildPoolData['ranks_total_spent'] ?? 3439017.50, 2) }}</b></span>
              <span>Ek Fon (CA4): <b class="text-info" id="kpiExtraFundVal">+{{ number_format($guildPoolData['extra_fund'] ?? 20570, 2) }}</b></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rütbeler Tablosu (Columns BU..CA) -->
    <!-- ====================================================
         LIVE XP CALCULATION & FORMULA ENGINE (BU..CA)
         ==================================================== -->
    <details class="card card-table-dark-container mb-4">
      <summary class="card-header cursor-pointer fw-semibold">{{ __('Formulas and Simulator') }}</summary>
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 pb-2">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-sm bg-label-primary rounded-circle d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-calculator fs-4 text-primary"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-heading" style="font-size: 1.12rem;">
              {{ __('Guild XP & Ranks Live Calculation Engine') }}
            </h5>
            <small class="text-body-secondary font-monospace">{{ __('Master Sheet BU..CA Column Architecture & Step-by-Step Mathematical Solutions') }}</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-label-success font-monospace px-3 py-1">
            <i class="icon-base ti tabler-check me-1"></i> {{ __('Formulas Active & Verified') }}
          </span>
        </div>
      </div>

      <div class="card-body pt-2 pb-3">
        <!-- 4 Adımda Matematiksel Çözüm Grid -->
        <div class="row g-3 mb-4">
          <!-- 1. Lonca XP Kasası -->
          <div class="col-12 col-md-6 col-xl-3">
            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 position-relative">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-warning text-dark font-monospace fw-bold">{{ __("1. Guild Vault") }}</span>
                <code class="text-warning small">=SUM(G2:G)</code>
              </div>
              <h6 class="fw-bold text-heading mb-1" style="font-size: 0.92rem;">{{ __('Lonca XP Birikimi') }}</h6>
              <div class="fs-5 fw-bold text-warning font-monospace mb-2" id="calcCockpitGuildXp">
                {{ number_format($guildPoolData['guild_xp']['total'] ?? 157150, 2) }} XP
              </div>
              <div class="text-body-secondary small font-monospace" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Mevcut Seviye') }}:</span>
                  <b class="text-heading">Lvl {{ $guildPoolData['guild_xp']['level'] ?? 12 }}</b>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Base / Threshold') }}:</span>
                  <span>{{ number_format($guildPoolData['guild_xp']['base'] ?? 145000) }} / {{ number_format($guildPoolData['guild_xp']['next_level_threshold'] ?? 210000) }}</span>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                  <div class="progress-bar bg-warning" role="progressbar" style="width: 24.2%;" aria-valuenow="24.2" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-end text-muted mt-1" style="font-size: 0.72rem;">Kalan: 529 XP (%24.2)</div>
              </div>
            </div>
          </div>

          <!-- 2. Wiki XP Havuzu -->
          <div class="col-12 col-md-6 col-xl-3">
            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 position-relative">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-info text-dark font-monospace fw-bold">2. Wiki Havuzu</span>
                <code class="text-info small">=BW2 - BX2</code>
              </div>
              <h6 class="fw-bold text-heading mb-1" style="font-size: 0.92rem;">{{ __('Remaining Wiki Distribution Share') }}</h6>
              <div class="fs-5 fw-bold text-info font-monospace mb-2" id="calcCockpitWikiRem">
                {{ number_format($guildPoolData['wiki_xp']['remaining'] ?? 14253, 2) }} XP
              </div>
              <div class="text-body-secondary small font-monospace" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Toplam Wiki') }} (BW2):</span>
                  <b class="text-heading">{{ number_format($guildPoolData['wiki_xp']['total'] ?? 53800, 2) }}</b>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Distributed') }} (BX2):</span>
                  <b class="text-warning">{{ number_format($guildPoolData['wiki_xp']['distributed'] ?? 39547, 2) }}</b>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                  <div class="progress-bar bg-info" role="progressbar" style="width: 73.5%;" aria-valuenow="73.5" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-end text-muted mt-1" style="font-size: 0.72rem;">%73.5 {{ __('Distributed to Players') }}</div>
              </div>
            </div>
          </div>

          <!-- 3. Mali Havuz & Uzman Payı -->
          <div class="col-12 col-md-6 col-xl-3">
            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 position-relative">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-success text-dark font-monospace fw-bold">{{ __("3. Expert Share") }}</span>
                <code class="text-success small">=BV5 / 21</code>
              </div>
              <h6 class="fw-bold text-heading mb-1" style="font-size: 0.92rem;">{{ __('Net Share Per Expert') }}</h6>
              <div class="fs-5 fw-bold text-success font-monospace mb-2" id="calcCockpitUzmanPay">
                {{ number_format($guildPoolData['pool_share_uzman'] ?? 98295.22, 2) }} XP
              </div>
              <div class="text-body-secondary small font-monospace" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Pool Amount') }} (BV5):</span>
                  <b class="text-heading">{{ number_format($guildPoolData['pool_total'] ?? 2064199.61, 2) }}</b>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Expert Count') }} (BW11):</span>
                  <b class="text-success">21 {{ __('Uzman') }}</b>
                </div>
                <div class="p-1 rounded bg-black bg-opacity-40 text-center text-success mt-2 font-monospace" style="font-size: 0.72rem;">
                  Pay = 2,064,199.61 / 21 = 98,295.22 XP
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Rütbeler & Genel Bakiye -->
          <div class="col-12 col-md-6 col-xl-3">
            <div class="p-3 rounded-3 bg-dark bg-opacity-75 border border-secondary border-opacity-25 h-100 position-relative">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-primary text-white font-monospace fw-bold">4. Genel Bakiye</span>
                <code class="text-primary small">=CA7 + CA4</code>
              </div>
              <h6 class="fw-bold text-heading mb-1" style="font-size: 0.92rem;">{{ __('Toplam XP Hacmi') }}</h6>
              <div class="fs-5 fw-bold text-primary font-monospace mb-2" id="calcCockpitGrandTotal">
                {{ number_format($guildPoolData['grand_total_xp'] ?? 3459587.50, 2) }} XP
              </div>
              <div class="text-body-secondary small font-monospace" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Ranks Expenditure') }}:</span>
                  <b class="text-heading">{{ number_format($guildPoolData['ranks_total_spent'] ?? 3439017.50, 2) }}</b>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ __('Kasa Ek Fonu') }}:</span>
                  <b class="text-info">+{{ number_format($guildPoolData['extra_fund'] ?? 20570, 2) }}</b>
                </div>
                <div class="p-1 rounded bg-black bg-opacity-40 text-center text-primary mt-2 font-monospace" style="font-size: 0.72rem;">
                  CA2 = 3,439,017.50 + 20,570.00
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Canlı İnteraktif Simülatör Kutusu -->
        <div class="p-3 rounded-3 bg-black bg-opacity-40 border border-primary border-opacity-20">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-adjustments-alt text-warning fs-4"></i>
              <span class="fw-bold text-heading" style="font-size: 0.95rem;">{{ __('Live Rank & Share Simulator (Adjust Parameters)') }}</span>
            </div>
            <span class="badge bg-label-warning font-monospace small">{{ __('Instant Live Calculation') }}</span>
          </div>

          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label text-body-secondary small font-monospace mb-1">{{ __('Pool Amount (BV5 XP)') }}</label>
              <input type="number" step="1000" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace fw-bold" id="simPoolInput" value="{{ $guildPoolData['pool_total'] ?? 2064199.61 }}">
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label text-body-secondary small font-monospace mb-1">{{ __('Expert Count (BW11)') }}</label>
              <input type="number" min="1" max="100" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace text-center fw-bold" id="simUzmanInput" value="21">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label text-body-secondary small font-monospace mb-1">{{ __('Lonca XP (BW3)') }}</label>
              <input type="number" step="1000" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace fw-bold" id="simGuildXpInput" value="{{ $guildPoolData['guild_xp']['total'] ?? 157150 }}">
            </div>
            <div class="col-12 col-md-4">
              <div class="p-2 rounded bg-dark border border-success border-opacity-30 d-flex align-items-center justify-content-between">
                <div>
                  <small class="text-body-secondary d-block font-monospace" style="font-size: 0.72rem;">{{ __('Calculated Expert Share') }}:</small>
                  <span class="fs-6 fw-bold text-success font-monospace" id="simUzmanPayOutput">{{ number_format($guildPoolData['pool_share_uzman'] ?? 98295.22, 2) }} XP</span>
                </div>
                <div class="text-end">
                  <small class="text-body-secondary d-block font-monospace" style="font-size: 0.72rem;">{{ __('Uzman Kapasite XP (%60)') }}:</small>
                  <span class="fs-6 fw-bold text-warning font-monospace" id="simUzmanCapOutput">{{ number_format(($guildPoolData['guild_xp']['total'] ?? 157150) * 0.60, 2) }} XP</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </details>

    <!-- Rütbeler Tablosu (Columns BU..CA) -->
    <div class="card card-table-dark-container mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-shield-check text-warning fs-4"></i>
          <h5 class="mb-0 fw-bold text-heading" style="font-size: 1.08rem;">
            {{ __('Ranks & XP Allocation Table') }}
          </h5>
        </div>
      </div>

      <div class="table-responsive-master">
        <table class="table table-master-sheet" id="guildRanksTable">
          <thead>
            <tr>
              <th>{{ __('Rank') }}</th>
              <th class="text-end">{{ __('Capacity') }}</th>
              <th class="text-center">{{ __('Players') }}</th>
              <th class="text-end text-warning">{{ __('Capacity XP') }}</th>
              <th class="text-end text-info">{{ __('Current XP') }}</th>
              <th class="text-end text-success">{{ __('Net XP') }}</th>
              <th class="text-end text-primary">{{ __('Spent XP') }}</th>
            </tr>
          </thead>
          <tbody id="guildRanksTableBody">
            @foreach($guildPoolData['ranks'] as $rk)
              @php
                $rName = $rk['rank'];
                $badgeClass = match($rName) {
                  'Bailiff' => 'badge-rank-bailiff',
                  'Uzman' => 'badge-rank-uzman',
                  'Kıdemli' => 'badge-rank-kidemli',
                  'Üye' => 'badge-rank-uye',
                  'Clerk' => 'badge-rank-clerk',
                  'Aday' => 'badge-rank-aday',
                  default => 'badge-rank-inaktif'
                };
              @endphp
              <tr class="quest-row-item">
                <!-- Rütbe (BU) -->
                <td>
                  <span class="badge {{ $badgeClass }} px-2 py-1 font-monospace fs-6">
                    {{ $rName }}
                  </span>
                  <details class="small mt-1">
                    <summary>{{ __('Formula') }}</summary>
                    <span class="text-body-secondary">{{ $rk['formula_desc'] ?? '-' }}</span>
                  </details>
                </td>

                <!-- Kapasite (BV) -->
                <td class="text-end font-monospace text-heading fw-semibold" title="{{ __('Capacity Limit (BV)') }}">
                  {{ $rk['capacity'] !== null ? number_format($rk['capacity'], 2) : '-' }}
                </td>

                <!-- Olan (BW) -->
                <td class="text-center font-monospace fw-bold {{ (int)$rk['current_count'] > 0 ? 'text-warning' : 'text-body-secondary' }}" title="{{ __('Active Player Count (BW)') }}">
                  {{ number_format($rk['current_count']) }}
                </td>

                <!-- Kapasite XP (BX) -->
                <td class="text-end font-monospace text-warning fw-semibold" title="{{ __('Kademe Kapasite XP (BX)') }}">
                  {{ $rk['capacity_xp'] !== null ? number_format($rk['capacity_xp'], 2) : '-' }}
                </td>

                <!-- Olan XP (BY) -->
                <td class="text-end font-monospace text-info fw-semibold" title="{{ __('Actual Existing XP (BY)') }}">
                  {{ $rk['current_xp'] !== null ? number_format($rk['current_xp'], 2) : '-' }}
                </td>

                <!-- Net XP (BZ) -->
                <td class="text-end font-monospace text-success fw-bold" title="{{ __('Net Distribution XP Per Capita (BZ)') }}">
                  {{ $rk['net_xp'] !== null ? number_format($rk['net_xp'], 2) : '-' }}
                </td>

                <!-- Harcanan XP (CA) -->
                <td class="text-end font-monospace text-heading fw-bolder" style="font-size: 0.95rem;" title="{{ __('Total Rank Spent XP (CA)') }}">
                  {{ number_format($rk['spent_xp'], 2) }}
                </td>

              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr style="background: rgba(30, 34, 59, 0.95); border-top: 2px solid rgba(115, 103, 240, 0.5);">
              <th class="text-uppercase fw-bold text-heading font-monospace">{{ __('Total Ranks') }}</th>
              <th class="text-end font-monospace text-heading fw-bold" id="ranksTableTotalCapacity">
                {{ number_format($guildPoolData['total_capacity'] ?? 5900.90, 2) }}
              </th>
              <th class="text-center font-monospace fw-bold text-warning" id="ranksTableTotalMembers">
                {{ $guildPoolData['total_count'] ?? array_sum(array_column($guildPoolData['ranks'], 'current_count')) }}
              </th>
              <th class="text-end font-monospace text-warning fw-bold" id="ranksTableTotalCapacityXp">
                {{ number_format($guildPoolData['total_capacity_xp'] ?? 1149408.02, 2) }}
              </th>
              <th class="text-end font-monospace text-info fw-bold" id="ranksTableTotalCurrentXp">
                {{ number_format($guildPoolData['total_current_xp'] ?? 1086548.02, 2) }}
              </th>
              <th class="text-end font-monospace text-success fw-bold" id="ranksTableTotalNetXp">
                {{ number_format($guildPoolData['total_net_xp'] ?? 125649.04, 2) }}
              </th>
              <th class="text-end font-monospace fw-bolder text-warning fs-6" id="ranksTableTotalSpent">
                {{ number_format($guildPoolData['ranks_total_spent'], 2) }}
              </th>
            </tr>
            <tr style="background: rgba(22, 25, 45, 0.95);">
              <th colspan="6" class="text-end text-uppercase fw-bold text-info font-monospace" style="font-size: 0.8rem;">
                + {{ __('Extra Fund') }}:
              </th>
              <th class="text-end font-monospace fw-bold text-info fs-6" id="ranksTableExtraFund">
                +{{ number_format($guildPoolData['extra_fund'], 2) }}
              </th>
            </tr>
            <tr style="background: linear-gradient(90deg, rgba(115, 103, 240, 0.25) 0%, rgba(245, 158, 11, 0.25) 100%); border-top: 2px solid #7367f0;">
              <th colspan="6" class="text-end text-uppercase fw-bolder text-warning font-monospace" style="font-size: 0.88rem; letter-spacing: 0.5px;">
                {{ __('Grand Total XP') }}:
              </th>
              <th class="text-end font-monospace fw-bolder text-warning fs-5" id="ranksTableGrandTotal">
                {{ number_format($guildPoolData['grand_total_xp'], 2) }}
              </th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- 18 Google Sheet Formulas Documentation Box -->
    <details class="card card-table-dark-container mb-4">
      <summary class="card-header cursor-pointer fw-semibold">{{ __('Formula Documentation') }}</summary>
      <div class="card-header d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-math-symbols text-primary fs-4"></i>
          <h5 class="mb-0 fw-bold text-heading" style="font-size: 1.05rem;">
            {{ __('Google Sheet Master Formula Documentation (18 Formula Architecture)') }}
          </h5>
        </div>
        <span class="badge bg-label-primary font-monospace">{{ __("18/18 Active Matches") }}</span>
      </div>

      <div class="card-body p-3 p-md-4">
        <div class="row g-3">
          <!-- Grup 1: Wiki & Lonca Formülleri -->
          <div class="col-12 col-lg-4">
            <div class="p-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-20 h-100">
              <h6 class="fw-bold text-info mb-3 d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-book"></i> {{ __('Wiki & Guild Formulas') }}
              </h6>
              <ul class="list-unstyled mb-0 font-monospace small" style="line-height: 1.9;">
                <li><code class="text-warning">=BW2-BX2</code> <span class="text-body-secondary">&rarr; Wiki Kalan: 53,800 - 39,547 = <b>14,253</b></span></li>
                <li><code class="text-warning">=BW3*0.6</code> <span class="text-body-secondary">&rarr; Uzman Kapasite XP: 157,150 &times; 0.6 = <b>94,290</b></span></li>
                <li><code class="text-warning">=SUM(G2:G)</code> <span class="text-body-secondary">&rarr; {{ __("Expedition Guild Vault:") }} <b>{{ number_format($stats['total_guild_cut'], 1) }} SP</b></span></li>
                <li><code class="text-warning">=SUM(BT:BT)</code> <span class="text-body-secondary">&rarr; Sefer Lonca Kolonu Kesinti Hacmi</span></li>
                <li><code class="text-warning">=COUNTA('Görev Raporları'!A2:A)</code> <span class="text-body-secondary">&rarr; {{ __("Total Expeditions:") }} <b>{{ $stats['total_quests'] }}</b></span></li>
              </ul>
            </div>
          </div>

          <!-- Grup 2: Havuz & Net Pay Formülleri -->
          <div class="col-12 col-lg-4">
            <div class="p-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-20 h-100">
              <h6 class="fw-bold text-success mb-3 d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-coins"></i> {{ __('Pool & Net Share Formulas') }}
              </h6>
              <ul class="list-unstyled mb-0 font-monospace small" style="line-height: 1.9;">
                <li><code class="text-success">=BV5/(BW11+BW17)</code> <span class="text-body-secondary">&rarr; {{ __("Expert Net Share:") }} 2,064,199.61 / 21 = <b>98,295.22</b></span></li>
                <li><code class="text-success">=CA11/BW11</code> <span class="text-body-secondary">&rarr; {{ __("Senior Net XP:") }} 401,350.01 / 20 = <b>20,067.50</b></span></li>
                <li><code class="text-success">=CA12/BW12</code> <span class="text-body-secondary">&rarr; {{ __("Member Net XP:") }} 30,047.37 / 20 = <b>1,502.37</b></span></li>
                <li><code class="text-success">=CA13/BW13</code> <span class="text-body-secondary">&rarr; Clerk Net XP: 198,000.00 / 60 = <b>3,300.00</b></span></li>
                <li><code class="text-success">=SUMIF('Player XP Tracker'!E:E, BU_x, H:H)</code> <span class="text-body-secondary">&rarr; {{ __("Rank Spent XP") }}</span></li>
              </ul>
            </div>
          </div>

          <!-- Grup 3: Kapasite & Denklik Formülleri -->
          <div class="col-12 col-lg-4">
            <div class="p-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-20 h-100">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-scale"></i> {{ __('Capacity & Equivalence Formulas') }}
              </h6>
              <ul class="list-unstyled mb-0 font-monospace small" style="line-height: 1.9;">
                <li><code class="text-primary">=(BV10+BV13)/10</code> <span class="text-body-secondary">&rarr; {{ __("Expert & Clerk Capacity Ratio") }}</span></li>
                <li><code class="text-primary">=BV13/2</code> <span class="text-body-secondary">&rarr; {{ __("Clerk Capacity Coefficient:") }} <b>84.39</b></span></li>
                <li><code class="text-primary">=MIN(BX11,BX11*BV11/BW11)</code> <span class="text-body-secondary">&rarr; {{ __("Senior Min Ceiling") }}</span></li>
                <li><code class="text-primary">=SUM(BV9:BV10)</code> & <code class="text-primary">=SUM(BV9:BV13)</code> <span class="text-body-secondary">&rarr; {{ __("Tier Clusters") }}</span></li>
                <li><code class="text-primary">=SUM(J2:J)=(CA2-CA4-CA10-SUM(CA12:CA16))</code> <span class="text-body-secondary">&rarr; Ek & Havuz Dengeleme</span></li>
                <li><code class="text-primary">=SUM(E2:E)+SUM(G2:G)+SUM(H2:H)</code> <span class="text-body-secondary">&rarr; Toplam Sefer Hacmi</span></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </details>

  </div>
  <!-- /#tabViewGuildPool -->

</div>

<!-- ====================================================
     4. MODAL: ADD QUEST REPORT (LIVE FORMULA ENGINE)
     ==================================================== -->
<div class="modal fade" id="modalAddQuestReport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form id="formAddQuestReport" class="modal-content quest-report-editor">
      @csrf
      <!-- Modal Header -->
      <div class="modal-header border-bottom d-flex align-items-center justify-content-between p-3 p-md-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-primary rounded-3 d-flex align-items-center justify-content-center shadow-md">
            <i class="icon-base ti tabler-calculator fs-3 text-primary"></i>
          </div>
          <div>
            <h4 class="mb-0 fw-bold text-heading">{{ __('New Quest Report & Live Average XP Calculator') }}</h4>
            <small class="text-body-secondary font-monospace">{{ __('Live average calculation for players using Master Sheet formula: (E - G - H - I + J) / AL') }}</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-md-4">
        
        <!-- FORMÜL CANLI HESAPLAMA MOTORU KARTI (HEADER BANNER) -->
        <div class="formula-engine-box mb-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-cpu text-primary fs-4"></i>
              <span class="fw-bold text-heading" style="font-size: 0.95rem;">{{ __('Live Formula & Player Average XP Engine') }}</span>
            </div>
            <span class="badge badge-avg-xp font-monospace fs-6 px-3 py-1 shadow-sm" id="liveAddAvgXpBadge">
              Ortalama: 175.63 XP
            </span>
          </div>

          <div class="p-2 rounded bg-black bg-opacity-40 font-monospace small mb-2 text-warning" id="liveAddFormulaEquation">
            =(XP [750] - Lonca [37.5] - Depo [0] - Rapor [75] + Ek [0]) / Oyuncu [4] = 159.38 XP
          </div>

          <div class="row g-2 text-center text-body-secondary font-monospace" style="font-size: 0.76rem;">
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Toplam XP (E)</span>
              <span class="fw-bold text-success" id="liveAddXpText">750 XP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Lonca %5 (G)</span>
              <span class="fw-bold text-warning" id="liveAddGuildCutText">37.5 SP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Depo %20 (H)</span>
              <span class="fw-bold text-danger" id="liveAddDepoCutText">0 SP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Rapor %10 (I)</span>
              <span class="fw-bold text-info" id="liveAddCutsText">75 XP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Net Havuz</span>
              <span class="fw-bold text-light" id="liveAddNetPoolText">637.50 XP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Oyuncu (AL)</span>
              <span class="fw-bold text-info" id="liveAddPlayerCountText">4 Oyuncu</span>
            </div>
          </div>
        </div>

        <!-- 2-COLUMN RESPONSIVE FORM GRID -->
        <div class="row g-3 quest-form-grid">
          
          <!-- LEFT COLUMN: GÖREV PARAMETRELERİ & BİLGİLERİ -->
          <div class="col-12">
            <div class="report-section-box mb-3">
              <h6 class="mb-3 fw-bold text-heading d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-file-text text-primary fs-5"></i>
                <span>{{ __('Sefer Parametreleri & Bilgileri') }}</span>
              </h6>

              <!-- Row 1: Görev Adı & Tarih (Gün/Ay/Yıl) -->
              <div class="row g-2 mb-3">
                <div class="col-12 col-sm-7">
                  <label class="form-label fw-semibold text-heading small">{{ __('Quest Name (A)') }} <span class="text-danger">*</span></label>
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-sword text-primary"></i></span>
                    <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="title" id="addReportTitle" placeholder="{{ __("e.g. Vault Funds") }}" required />
                  </div>
                </div>

                <div class="col-12 col-sm-5">
                  <label class="form-label fw-semibold text-heading small">{{ __('Date (B) [Day/Month/Year]') }} <span class="text-danger">*</span></label>
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-calendar text-info"></i></span>
                    <input type="date" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace" name="date" id="addReportDate" required />
                  </div>
                </div>
              </div>

              <!-- Row 2: MQ Tipi, Seviye (Otomatik), Görev Sonucu & Depo Durumu (Otomatik) -->
              <div class="row g-2 mb-3">
                <!-- MQ -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small">{{ __('MQ (C) - Quest Type') }}</label>
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading" name="mq" id="addReportMq" required>
                    <option value="" selected disabled>{{ __('Görev tipi seçin') }}</option>
                    <option value="none">{{ __("Side Quest (1x)") }}</option>
                    <option value="main">{{ __("Main Quest (1x)") }}</option>
                    <option value="epic">{{ __("Epic Main Quest (3x)") }}</option>
                  </select>
                </div>

                <!-- Level (D) - Kadroya Göre Otomatik Hesaplanır -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small d-flex align-items-center justify-content-between mb-1">
                    <span>{{ __('Level (D) - Quest Level') }}</span>
                    <span class="badge bg-label-info font-monospace py-0 px-1" id="addReportLevelBadge" style="font-size: 0.65rem;">Kadro seçilmedi</span>
                  </label>
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-chart-bar text-info"></i></span>
                    <input type="number" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace fw-bold" name="level" id="addReportLevel" min="1" max="20" readonly style="background-color: rgba(0,0,0,0.45) !important; cursor: not-allowed;" title="{{ __('Automatically calculated based on party (Cannot be changed)') }}" required />
                  </div>
                  <small class="text-body-secondary d-block mt-1" style="font-size: 0.68rem;">
                    <i class="icon-base ti tabler-lock text-warning me-1"></i>{{ __("Calculated automatically based on party average.") }}
                  </small>
                </div>
              </div>

              <!-- Row 3: Görev Sonucu, Depo Durumu (Otomatik), Rapor Kesintisi (I - Fix %10), Ek Ödül, GM -->
              <div class="row g-2 mb-3">
                <!-- Görev Sonucu (Başarılı / Başarısız) -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small">{{ __('Quest Result (F)') }} <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading fw-semibold" name="status" id="addReportStatus">
                    <option value="success" selected>{{ __("Successful (Completed)") }}</option>
                    <option value="failed">{{ __("Failed (Defeat / Cancelled)") }}</option>
                  </select>
                </div>

                <!-- Depo Durumu (F) (Otomatik & Değiştirilemez) -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small d-flex align-items-center justify-content-between mb-1">
                    <span>{{ __('Depo Durumu (F)') }}</span>
                    <span class="badge bg-label-secondary font-monospace py-0 px-1" style="font-size: 0.65rem;">Otomatik</span>
                  </label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-success font-monospace fw-bold" id="addReportDepoDisplay" value="{{ __('Depot delivered (no penalty)') }}" readonly style="background-color: rgba(0,0,0,0.45) !important; cursor: not-allowed;" title="{{ __('Automatically determined based on quest success/failure') }}" />
                  <input type="hidden" name="depo_status" id="addReportDepoStatus" value="1" />
                  <small class="text-body-secondary d-block mt-1" style="font-size: 0.68rem;" id="addReportDepoHint">
                    <i class="icon-base ti tabler-circle-check text-success me-1"></i>{{ __("Since quest was successful, no vault penalty is applied.") }}
                  </small>
                  <label class="form-label small mt-2" for="addReportDepoOverride">Depo kesintisi (isteğe bağlı manuel tutar)</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="addReportDepoOverride" placeholder="Otomatik hesapla" />
                  <small class="text-warning">Manuel tutar oyuncuların XP payını değiştirir.</small>
                </div>
              </div>

              <!-- Row 4: Rapor Kesintisi, Ek Ödül, Game Master -->
              <div class="row g-2 mb-2">
                <!-- Rapor Kesintisi (I) - Sabit XP * 0.10 -->
                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-heading small d-flex align-items-center justify-content-between mb-1">
                    <span>{{ __('Rapor (I)') }}</span>
                    <span class="badge bg-label-info font-monospace py-0 px-1" style="font-size: 0.65rem;">%10 Fix (XP &times; 0.1)</span>
                  </label>
                  <input type="number" step="0.5" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-info font-monospace fw-bold" name="rapor_cut" id="addReportRaporCut" value="75" readonly style="background-color: rgba(0,0,0,0.45) !important; cursor: not-allowed;" title="{{ __('Calculated fixed at 10% of Total XP (Cannot be changed)') }}" />
                  <small class="text-body-secondary d-block mt-1" style="font-size: 0.65rem;">
                    <i class="icon-base ti tabler-lock text-warning me-1"></i>Toplam XP'nin %10'u sabittir.
                  </small>
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-heading small">{{ __('Ek (J)') }}</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" name="extra_reward" id="addReportExtraReward" placeholder="0" />
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-heading small">{{ __('Game Master (GM)') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="gm" id="addReportGm" list="knownGmsList" placeholder="Shalvaz, Bart..." required />
                  <datalist id="knownGmsList">
                    @foreach($gms as $gm)
                      <option value="{{ $gm }}"></option>
                    @endforeach
                  </datalist>
                </div>
              </div>
            </div>

            <!-- Konum & Brifing Kartı -->
            <div class="report-section-box mb-3">
              <h6 class="mb-2 fw-bold text-heading d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-map-pin text-warning fs-5"></i>
                <span>{{ __('Konum & Hikaye Brifingi') }}</span>
              </h6>

              <div class="row g-2 mb-2">
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small mb-1" for="addReportLocation">{{ __('Lokasyon') }}</label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="location" id="addReportLocation" placeholder="{{ __("Siwion, Harken Valley...") }}" />
                </div>

                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small mb-1" for="addReportEnemies">{{ __('Enemies') }}</label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="enemies" id="addReportEnemies" placeholder="{{ __("Enemy types...") }}" />
                </div>
              </div>

              <div>
                <label class="form-label fw-semibold text-heading small mb-1" for="addReportFullText">{{ __('Detailed Expedition Report / Briefing') }}</label>
                <textarea class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="full_report" id="addReportFullText" rows="2" placeholder="{{ __("Write quest narrative here...") }}"></textarea>
              </div>
            </div>

            <!-- Auto Distribute XP Checkbox Switch -->
            <div class="p-2 rounded bg-black bg-opacity-30 border border-secondary border-opacity-20">
              <div class="form-check form-switch mb-0">
                <input class="form-check-input cursor-pointer" type="checkbox" id="addReportDistributeXp" name="distribute_xp_auto" value="1" checked>
                <label class="form-check-label text-heading fw-semibold small cursor-pointer" for="addReportDistributeXp">
                  {{ __('Automatically transfer XP to adventurer profiles (XP Tracker & Characters)') }}
                </label>
              </div>
              <small class="text-body-secondary d-block mt-1 ps-4" id="liveAddDistributeHint" style="font-size: 0.72rem;">
                <i class="icon-base ti tabler-info-circle text-info me-1"></i>{{ __("When saved, +175.63 XP will be automatically awarded to 4 adventurers in the party.") }}
              </small>
              <small class="text-body-secondary d-block mt-1 ps-4">{{ __('Sahibi bilinmeyen maceracılar için girişe kapalı Unknown kullanıcı ve karakter kaydı oluşturulur.') }}</small>
            </div>
          </div>

          <!-- RIGHT COLUMN: SEFER KADROSU & EKLE / ÇIKAR BÖLÜMÜ -->
          <div class="col-12">
            <div class="report-section-box mb-3 border-primary border-opacity-35" style="background: rgba(18, 22, 42, 0.85);">
              
              <!-- Header with Party Count -->
              <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-users-group text-primary fs-5"></i>
                  <span>{{ __('Attending Party & Adventurers') }}</span>
                </h6>
                <span class="badge bg-primary font-monospace fs-6 px-3 py-1 shadow-sm" id="addReportPartyCountLabel">
                  3 {{ __("Other Players") }} (+1 {{ __("Reporter") }} = 4 {{ __("Total") }})
                </span>
              </div>

              <!-- Raporlayan Maceracı (Leader) -->
              <div class="p-2 rounded bg-black bg-opacity-40 border border-warning border-opacity-30 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fw-semibold text-warning small mb-0 d-flex align-items-center gap-1">
                    <span>{{ __('Reporting Adventurer (AN - Leader)') }}</span>
                    <span class="text-danger">*</span>
                  </label>
                  <span class="badge bg-label-warning font-monospace" style="font-size: 0.68rem;">{{ __('Report Reward Included') }}</span>
                </div>
                <div class="input-group input-group-merge input-group-sm">
                  <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-feather text-warning"></i></span>
                  <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading fw-bold" name="reporter" id="addReportReporter" list="knownAdventurersList" placeholder="{{ __('e.g., Tolrun, Leothar...') }}" required />
                </div>
              </div>

              <!-- EKLE / ÇIKAR KONTROL PANELİ -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fw-semibold text-heading small mb-0 d-flex align-items-center gap-1">
                    <i class="icon-base ti tabler-user-plus text-primary"></i>
                    <span>{{ __('Add Other Players to Party (AO..BG)') }}</span>
                  </label>
                  <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2" id="btnClearAllOtherPlayersBtn" title="{{ __('Kadroyu Temizle') }}" style="font-size: 0.72rem;">
                    <i class="icon-base ti tabler-trash me-1"></i>{{ __('Kadroyu Temizle') }}
                  </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <div class="input-group input-group-merge flex-grow-1">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-user text-body-secondary"></i></span>
                    <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" id="inputAddOtherPlayer" list="knownAdventurersList" placeholder="{{ __("Type adventurer name or select from list...") }}" />
                  </div>
                  <button type="button" class="btn btn-primary d-flex align-items-center gap-1 px-3 shadow-sm" id="btnAddOtherPlayerBtn">
                    <i class="icon-base ti tabler-plus fs-5"></i>
                    <span class="fw-bold">{{ __('Ekle') }}</span>
                  </button>
                </div>
              </div>

              <!-- AKTİF KADRO & CANLI XP DAĞILIMI LİSTESİ -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-body-secondary fw-semibold small text-uppercase" style="letter-spacing: 0.5px;">
                    <i class="icon-base ti tabler-list-check text-info me-1"></i>{{ __('Current Party List & Live XP Share:') }}
                  </small>
                  <span class="badge bg-label-warning font-monospace" id="liveAddDistributionSummaryBadge" style="font-size: 0.72rem;">
                    4 {{ __("Players") }} &bull; {{ __("Per Person:") }} 175.63 XP
                  </span>
                </div>

                <!-- Unified Party List (Raporlayan + {{ __('Other Players') }} with Clear [Çıkar] buttons) -->
                <div id="liveAddPlayersDistributionList" class="d-flex flex-column gap-2 p-1 rounded bg-black bg-opacity-35 border border-secondary border-opacity-20 mb-2" style="max-height: 220px; overflow-y: auto;">
                  <!-- Populated in real-time by updateAddLiveCalculator() -->
                </div>

                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-black bg-opacity-40 font-monospace text-body-secondary" style="font-size: 0.73rem;">
                  <span id="liveAddSumCheckText">{{ __("Total Distributed:") }} 4 &times; 175.63 = 702.52 XP</span>
                  <span class="text-success"><i class="icon-base ti tabler-check me-1"></i>{{ __('Equal Average Distribution') }}</span>
                </div>
              </div>


              <!-- Hidden container keeping DOM tags synced for payload serialization -->
              <div id="addOtherPlayersTagsContainer" class="d-none"></div>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer (Pinned at Bottom, Always Visible) -->
      <div class="modal-footer border-top p-3 p-md-4 d-flex align-items-center justify-content-between">
        <button type="button" class="btn btn-label-secondary px-3" data-bs-dismiss="modal">
          <i class="icon-base ti tabler-x me-1"></i> {{ __('Cancel') }}
        </button>
        <button type="submit" class="btn btn-primary px-4" id="btnSubmitAddQuestReport">
          <i class="icon-base ti tabler-circle-check fs-5 me-2"></i>
          <span class="fw-bold">{{ __('Save & Distribute Quest Report') }}</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ====================================================
     5. MODAL: EDIT QUEST REPORT
     ==================================================== -->
<div class="modal fade" id="modalEditQuestReport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form id="formEditQuestReport" class="modal-content quest-report-editor">
      @csrf
      <input type="hidden" id="editReportId" name="id" />

      <!-- Modal Header -->
      <div class="modal-header border-bottom d-flex align-items-center justify-content-between p-3 p-md-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-primary rounded-3 d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-edit fs-3 text-primary"></i>
          </div>
          <div>
            <h4 class="mb-0 fw-bold text-heading">{{ __('Edit Quest Report') }}</h4>
            <small class="text-body-secondary font-monospace" id="editReportSubtitle">{{ __("Report #1 (Editing)") }}</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-md-4">
        
        <!-- FORMÜL CANLI HESAPLAMA MOTORU KARTI -->
        <div class="formula-engine-box mb-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-cpu text-warning fs-4"></i>
              <span class="fw-bold text-heading" style="font-size: 0.95rem;">{{ __('Live Formula Calculator') }}</span>
            </div>
            <span class="badge badge-avg-xp font-monospace fs-6 px-3 py-1 shadow-sm" id="liveEditAvgXpBadge">
              Ortalama: 175.63 XP
            </span>
          </div>
          <div class="p-2 rounded bg-black bg-opacity-40 font-monospace small mb-2 text-warning" id="liveEditFormulaEquation">
            =(XP - Lonca - Depo - Rapor + Ek) / Oyuncu
          </div>
          <div class="row g-2 text-center text-body-secondary font-monospace" style="font-size: 0.76rem;">
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Toplam XP (E)</span>
              <span class="fw-bold text-success" id="liveEditXpText">750 XP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Lonca %5 (G)</span>
              <span class="fw-bold text-warning" id="liveEditGuildCutText">37.5 SP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Depo %20 (H)</span>
              <span class="fw-bold text-danger" id="liveEditDepoCutText">0 SP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Rapor/Ek (I/J)</span>
              <span class="fw-bold text-info" id="liveEditCutsText">-0 / +0</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Net Havuz</span>
              <span class="fw-bold text-light" id="liveEditNetPoolText">712.50 XP</span>
            </div>
            <div class="col-4 col-sm-2">
              <span class="d-block text-muted">Oyuncu (AL)</span>
              <span class="fw-bold text-info" id="liveEditPlayerCountText">4 Oyuncu</span>
            </div>
          </div>
        </div>

        <!-- 2-COLUMN RESPONSIVE FORM GRID -->
        <div class="row g-3 quest-form-grid">
          <!-- LEFT COLUMN: SEFER BİLGİLERİ -->
          <div class="col-12">
            <div class="report-section-box mb-3">
              <h6 class="mb-3 fw-bold text-heading d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-file-text text-warning fs-5"></i>
                <span>{{ __('Sefer Bilgileri & Parametreleri') }}</span>
              </h6>

              <!-- Row 1: Görev Adı & Tarih (Gün/Ay/Yıl) -->
              <div class="row g-2 mb-3">
                <div class="col-12 col-sm-7">
                  <label class="form-label fw-semibold text-heading small">{{ __('Quest Name (A)') }} <span class="text-danger">*</span></label>
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-sword text-warning"></i></span>
                    <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" name="title" id="editReportTitle" required />
                  </div>
                </div>

                <div class="col-12 col-sm-5">
                  <label class="form-label fw-semibold text-heading small">{{ __('Date (B) [Day/Month/Year]') }} <span class="text-danger">*</span></label>
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-calendar text-info"></i></span>
                    <input type="date" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace" name="date" id="editReportDate" required />
                  </div>
                </div>
              </div>

              <!-- Row 2: MQ Tipi, Seviye (Otomatik) -->
              <div class="row g-2 mb-3">
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small">{{ __('MQ (C) - Quest Type') }}</label>
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading" name="mq" id="editReportMq">
                    <option value="none">{{ __("Side Quest (1x)") }}</option>
                    <option value="main">{{ __("Main Quest (1x)") }}</option>
                    <option value="epic">{{ __("Epic Main Quest (3x)") }}</option>
                  </select>
                </div>

                <!-- Level (D) - Kadroya Göre Otomatik Hesaplanır -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small d-flex align-items-center justify-content-between mb-1">
                    <span>{{ __('Level (D) - Quest Level') }}</span>
                    <span class="badge bg-label-warning font-monospace py-0 px-1" id="editReportLevelBadge" style="font-size: 0.65rem;">Kadro Ort: 1.00</span>
                  </label>
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-chart-bar text-warning"></i></span>
                    <input type="number" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace fw-bold" name="level" id="editReportLevel" value="1" min="1" max="20" readonly style="background-color: rgba(0,0,0,0.45) !important; cursor: not-allowed;" title="{{ __('Automatically calculated based on party (Cannot be changed)') }}" required />
                  </div>
                  <small class="text-body-secondary d-block mt-1" style="font-size: 0.68rem;">
                    <i class="icon-base ti tabler-lock text-warning me-1"></i>{{ __("Calculated automatically based on party average.") }}
                  </small>
                </div>
              </div>

              <!-- Row 3: Görev Sonucu (F) & Depo Durumu (F) (Otomatik) -->
              <div class="row g-2 mb-3">
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small">{{ __('Quest Result (F)') }} <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm bg-dark border-secondary border-opacity-25 text-heading fw-semibold" name="status" id="editReportStatus">
                    <option value="success">{{ __("Successful (Completed)") }}</option>
                    <option value="failed">{{ __("Failed (Defeat / Cancelled)") }}</option>
                  </select>
                </div>

                <!-- Depo Durumu (F) (Otomatik & Değiştirilemez) -->
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small d-flex align-items-center justify-content-between mb-1">
                    <span>{{ __('Depo Durumu (F)') }}</span>
                    <span class="badge bg-label-secondary font-monospace py-0 px-1" style="font-size: 0.65rem;">Otomatik</span>
                  </label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-success font-monospace fw-bold" id="editReportDepoDisplay" value="{{ __('Depot delivered (no penalty)') }}" readonly style="background-color: rgba(0,0,0,0.45) !important; cursor: not-allowed;" title="{{ __('Automatically determined based on quest success/failure') }}" />
                  <input type="hidden" name="depo_status" id="editReportDepoStatus" value="1" />
                  <small class="text-body-secondary d-block mt-1" style="font-size: 0.68rem;" id="editReportDepoHint">
                    <i class="icon-base ti tabler-circle-check text-success me-1"></i>{{ __("Since quest was successful, no vault penalty is applied.") }}
                  </small>
                  <label class="form-label small mt-2" for="editReportDepoOverride">Depo kesintisi (isteğe bağlı manuel tutar)</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="editReportDepoOverride" placeholder="Otomatik hesapla" />
                  <small class="text-warning">Manuel tutar oyuncuların XP payını değiştirir.</small>
                </div>
              </div>

              <!-- Row 4: Rapor Kesintisi (I - Fix %10), Ek Ödül, Game Master -->
              <div class="row g-2 mb-2">
                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-heading small d-flex align-items-center justify-content-between mb-1">
                    <span>{{ __('Rapor (I)') }}</span>
                    <span class="badge bg-label-info font-monospace py-0 px-1" style="font-size: 0.65rem;">%10 Fix (XP &times; 0.1)</span>
                  </label>
                  <input type="number" step="0.5" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-info font-monospace fw-bold" name="rapor_cut" id="editReportRaporCut" readonly style="background-color: rgba(0,0,0,0.45) !important; cursor: not-allowed;" title="{{ __('Calculated fixed at 10% of Total XP (Cannot be changed)') }}" />
                  <small class="text-body-secondary d-block mt-1" style="font-size: 0.68rem;">
                    <i class="icon-base ti tabler-lock text-warning me-1"></i>Toplam XP'nin %10'u sabittir.
                  </small>
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-heading small">{{ __('Ek (J)') }}</label>
                  <input type="number" min="0" step="0.01" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" name="extra_reward" id="editReportExtraReward" />
                </div>

                <div class="col-12 col-sm-4">
                  <label class="form-label fw-semibold text-heading small">{{ __('Game Master (GM)') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="gm" id="editReportGm" list="knownGmsList" required />
                </div>
              </div>
            </div>

            <!-- Konum & Brifing Kartı -->
            <div class="report-section-box mb-3">
              <h6 class="mb-2 fw-bold text-heading d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-map-pin text-warning fs-5"></i>
                <span>{{ __('Konum & Hikaye Brifingi') }}</span>
              </h6>

              <div class="row g-2 mb-2">
                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small mb-1" for="editReportLocation">{{ __('Lokasyon') }}</label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="location" id="editReportLocation" />
                </div>

                <div class="col-12 col-sm-6">
                  <label class="form-label fw-semibold text-heading small mb-1" for="editReportEnemies">{{ __('Enemies') }}</label>
                  <input type="text" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="enemies" id="editReportEnemies" />
                </div>
              </div>

              <div>
                <label class="form-label fw-semibold text-heading small mb-1" for="editReportFullText">{{ __('Detailed Expedition Report / Briefing') }}</label>
                <textarea class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading" name="full_report" id="editReportFullText" rows="2"></textarea>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: KADRO YÖNETİMİ & EKLE / ÇIKAR -->
          <div class="col-12">
            <div class="report-section-box mb-3 border-warning border-opacity-35" style="background: rgba(18, 22, 42, 0.85);">
              
              <!-- Header with Party Count -->
              <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-users-group text-warning fs-5"></i>
                  <span>{{ __('Attending Party & Adventurers') }}</span>
                </h6>
                <span class="badge bg-label-primary font-monospace fs-6 px-3 py-1" id="editReportPartyCountLabel">
                  {{ __('Other Players') }}
                </span>
              </div>

              <!-- Raporlayan (Leader) -->
              <div class="p-2 rounded bg-black bg-opacity-40 border border-warning border-opacity-30 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fw-semibold text-warning small mb-0 d-flex align-items-center gap-1">
                    <span>{{ __('Reporting Adventurer (AN - Leader)') }}</span>
                    <span class="text-danger">*</span>
                  </label>
                  <span class="badge bg-label-warning font-monospace" style="font-size: 0.68rem;">{{ __('Report Reward Included') }}</span>
                </div>
                <div class="input-group input-group-merge input-group-sm">
                  <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-feather text-warning"></i></span>
                  <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading fw-bold" name="reporter" id="editReportReporter" list="knownAdventurersList" required />
                </div>
              </div>

              <!-- EKLE / ÇIKAR KONTROL PANELİ -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fw-semibold text-heading small mb-0 d-flex align-items-center gap-1">
                    <i class="icon-base ti tabler-user-plus text-warning"></i>
                    <span>{{ __('Add Other Players to Party (AO..BG)') }}</span>
                  </label>
                  <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2" id="btnClearAllEditOtherPlayersBtn" title="{{ __('Kadroyu Temizle') }}" style="font-size: 0.72rem;">
                    <i class="icon-base ti tabler-trash me-1"></i>{{ __('Kadroyu Temizle') }}
                  </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <div class="input-group input-group-merge flex-grow-1">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-user text-body-secondary"></i></span>
                    <input type="text" class="form-control bg-dark border-secondary border-opacity-25 text-heading" id="inputEditOtherPlayer" list="knownAdventurersList" placeholder="{{ __("Type or select character name...") }}" />
                  </div>
                  <button type="button" class="btn btn-primary d-flex align-items-center gap-1 px-3" id="btnEditOtherPlayerBtn">
                    <i class="icon-base ti tabler-plus fs-5"></i>
                    <span>{{ __('Ekle') }}</span>
                  </button>
                </div>
              </div>

              <!-- AKTİF KADRO & CANLI XP DAĞILIMI LİSTESİ -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-body-secondary fw-semibold small text-uppercase" style="letter-spacing: 0.5px;">
                    <i class="icon-base ti tabler-list-check text-warning me-1"></i>{{ __('Current Party & Average XP Per Capita:') }}
                  </small>
                  <span class="badge bg-label-warning font-monospace" id="liveEditDistributionSummaryBadge" style="font-size: 0.72rem;">
                    4 {{ __("Players") }} &bull; {{ __("Per Person:") }} 175.63 XP
                  </span>
                </div>

                <div id="liveEditPlayersDistributionList" class="d-flex flex-column gap-2 p-1 rounded bg-black bg-opacity-35 border border-secondary border-opacity-20 mb-2" style="max-height: 220px; overflow-y: auto;">
                  <!-- Dynamically populated via JS with remove buttons -->
                </div>

                <div class="d-flex align-items-center justify-content-between p-2 rounded bg-black bg-opacity-40 font-monospace text-body-secondary" style="font-size: 0.73rem;">
                  <span id="liveEditSumCheckText">{{ __("Total Distributed:") }} 4 &times; 175.63 = 702.52 XP</span>
                  <span class="text-success"><i class="icon-base ti tabler-check me-1"></i>{{ __('Equal Average Distribution') }}</span>
                </div>
              </div>


              <!-- Hidden container keeping DOM tags synced for edit payload -->
              <div id="editOtherPlayersTagsContainer" class="d-none"></div>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer (Pinned at Bottom, Always Visible) -->
      <div class="modal-footer border-top p-3 p-md-4 d-flex align-items-center justify-content-between">
        <button type="button" class="btn btn-label-secondary px-3" data-bs-dismiss="modal">
          <i class="icon-base ti tabler-x me-1"></i> {{ __('Cancel') }}
        </button>
        <button type="submit" class="btn btn-primary px-4" id="btnSubmitEditQuestReport">
          <i class="icon-base ti tabler-check me-1 fs-5"></i>
          <span>{{ __('Save Changes') }}</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ====================================================
     6. MISSION REPORT DOSSIER MODAL (DETAYLAR OKUYUCU)
     ==================================================== -->
<div class="modal fade" id="questReportDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-dossier">
      <!-- Modal Header -->
      <div class="modal-header d-flex align-items-center justify-content-between p-3 p-md-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md bg-label-warning rounded-3 d-flex align-items-center justify-content-center shadow-md">
            <i class="icon-base ti tabler-shield-lock fs-3 text-warning"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <span class="badge" id="modalReportTierBadge">Main Quest</span>
              <span class="text-body-secondary font-monospace small" id="modalReportDate">15.09.2026</span>
              <span class="badge bg-label-success" id="modalReportStatusBadge">{{ __('Successful') }}</span>
            </div>
            <h4 class="mb-0 fw-bold text-heading" id="modalReportTitle">{{ __("Quest Title") }}</h4>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-md-4">
        
        <!-- Formula Breakdown Card -->
        <div class="formula-engine-box mb-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold text-warning font-monospace small"><i class="icon-base ti tabler-calculator me-1"></i>Master Sheet Hesaplama Brifingi</span>
            <span class="badge badge-avg-xp font-monospace" id="modalReportAvgBadge">175.63 XP</span>
          </div>
          <div class="p-2 rounded bg-black bg-opacity-40 font-monospace small text-heading" id="modalReportFormulaText">
            =(E2 - G2 - H2 - I2 + J2) / AL2
          </div>
        </div>

        <!-- Metadata Ribbon -->
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Lokasyon') }}</span>
              <span class="fw-bold font-monospace text-heading" id="modalReportLocation" style="font-size: 0.85rem;">Siwion</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Game Master') }}</span>
              <span class="fw-bold font-monospace text-warning" id="modalReportGm" style="font-size: 0.85rem;">Bart</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Raporlayan') }}</span>
              <span class="fw-bold font-monospace text-info" id="modalReportReporter" style="font-size: 0.85rem;">Tolrun</span>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-10 text-center">
              <span class="text-body-secondary small d-block" style="font-size: 0.72rem;">{{ __('Seviye') }}</span>
              <span class="fw-bold font-monospace text-success" id="modalReportLevel" style="font-size: 0.85rem;">Lvl 1</span>
            </div>
          </div>
        </div>

        <!-- Debriefing / Story Narrative -->
        <div class="report-section-box mb-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-feather text-warning"></i>
              <span>{{ __('Detailed Expedition Report') }}</span>
            </h6>
            <span class="badge bg-label-danger font-monospace" id="modalReportEnemies" style="font-size: 0.75rem;">
              {{ __('Enemies') }}
            </span>
          </div>
          <p class="text-body-secondary mb-0" id="modalReportFullText" style="line-height: 1.68; font-size: 0.92rem; white-space: pre-line;">
            {{ __('Loading detailed expedition report...') }}
          </p>
        </div>

        <!-- Party Members & Individual XP Breakdown -->
        <div class="report-section-box mb-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-users-group text-primary"></i>
              <span>{{ __('Attending Adventurers & Average Share') }}</span>
            </h6>
            <span class="badge bg-label-secondary font-monospace" id="modalReportPartyCount">
              4 Oyuncu
            </span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm text-nowrap mb-0" style="font-size: 0.85rem;">
              <thead>
                <tr class="text-body-secondary border-bottom border-secondary border-opacity-20">
                  <th>{{ __('Adventurer') }}</th>
                  <th>{{ __('Rol') }}</th>
                  <th>{{ __('Seviye') }}</th>
                  <th class="text-end">{{ __('XP Earned') }}</th>
                </tr>
              </thead>
              <tbody id="modalReportPartyTableBody">
                <!-- Populated via JS -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Financial & Vault Ledger Cuts -->
        <div class="report-section-box mb-0">
          <h6 class="mb-3 fw-bold text-heading d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-coins text-warning"></i>
            <span>{{ __('Financial Table & Deductions (Google Sheet Columns)') }}</span>
          </h6>
          <div class="row g-2">
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Toplam XP (E)') }}</span>
                <span class="fw-bold font-monospace text-success" id="modalReportTotalXp">+750 XP</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Lonca %5 (G)') }}</span>
                <span class="fw-bold font-monospace text-warning" id="modalReportGuildCut">37.5 SP</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Depo Kes. (H)') }}</span>
                <span class="fw-bold font-monospace text-danger" id="modalReportDepoCut">0 SP</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="p-2 rounded-2 bg-black bg-opacity-30 border border-secondary border-opacity-10 text-center">
                <span class="text-body-secondary small d-block" style="font-size: 0.7rem;">{{ __('Per Capita Average (K)') }}</span>
                <span class="fw-bold font-monospace text-warning" id="modalReportAvgReward">175.63 XP</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer p-3 p-md-4 d-flex align-items-center justify-content-between">
        <button type="button" class="btn btn-outline-success btn-sm" id="btnModalDistributeXp">
          <i class="icon-base ti tabler-bolt me-1"></i> {{ __('XP Tracker\'a Aktar') }}
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          {{ __('Kapat') }}
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ====================================================
     7. MODAL: EDIT GUILD XP & RANKS POOL
     ==================================================== -->
<div class="modal fade" id="modalEditGuildPool" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-dossier">
      <form id="formEditGuildPool">
        @csrf
        <!-- Modal Header -->
        <div class="modal-header d-flex align-items-center justify-content-between p-3 p-md-4">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-label-warning rounded-3 d-flex align-items-center justify-content-center shadow-md">
              <i class="icon-base ti tabler-shield-dollar fs-3 text-warning"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-bold text-heading">{{ __('Edit Guild XP & Rank Pool') }}</h4>
              <small class="text-body-secondary font-monospace">{{ __('Master Sheet BU..CA parameters and instant formula calculation') }}</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-3 p-md-4">

          <!-- Canlı Formül Hesaplama Kartı -->
          <div class="formula-engine-box mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-2">
                <i class="icon-base ti tabler-cpu text-warning fs-4"></i>
                <span class="fw-bold text-heading" style="font-size: 0.95rem;">{{ __('Live Pool & Share Calculator') }}</span>
              </div>
              <span class="badge badge-avg-xp font-monospace" id="livePoolPayBadge">
                {{ __('Expert Share:') }} 98,295.22 XP
              </span>
            </div>
            <div class="p-2 rounded bg-black bg-opacity-40 font-monospace small mb-2 text-warning" id="livePoolEquationsText">
              {{ __('Expert Share = Pool / Expert Count | Grand Total = Ranks Total + Extra Fund') }}
            </div>
            <div class="row g-2 text-center text-body-secondary font-monospace" style="font-size: 0.78rem;">
              <div class="col-6 col-md-3">
                <span class="d-block text-muted">Wiki Kalan (BY2)</span>
                <span class="fw-bold text-info" id="livePoolWikiRemText">14,253.00 XP</span>
              </div>
              <div class="col-6 col-md-3">
                <span class="d-block text-muted">Uzman Kapasite (BX10)</span>
                <span class="fw-bold text-warning" id="livePoolUzmanCapText">94,290.00 XP</span>
              </div>
              <div class="col-6 col-md-3">
                <span class="d-block text-muted">{{ __("Ranks Total (CA7)") }}</span>
                <span class="fw-bold text-success" id="livePoolRanksTotalText">3,439,017.50 XP</span>
              </div>
              <div class="col-6 col-md-3">
                <span class="d-block text-muted">Genel Bakiye (CA2)</span>
                <span class="fw-bold text-primary" id="livePoolGrandTotalText">3,459,587.50 XP</span>
              </div>
            </div>
          </div>

          <!-- Section 1: Wiki XP & Lonca XP Parametreleri -->
          <div class="row g-3 mb-4">
            <!-- Wiki XP Card -->
            <div class="col-12 col-md-6">
              <div class="p-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-20 h-100">
                <h6 class="fw-bold text-info mb-3 d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-book"></i> {{ __('Wiki XP Parametreleri') }}
                </h6>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label text-heading small fw-semibold">{{ __('Wiki Total (BW2)') }}</label>
                    <input type="number" step="0.5" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" id="editPoolWikiTotal" value="{{ $guildPoolData['wiki_xp']['total'] ?? 53800 }}" />
                  </div>
                  <div class="col-6">
                    <label class="form-label text-heading small fw-semibold">{{ __('Wiki Distributed (BX2)') }}</label>
                    <input type="number" step="0.5" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" id="editPoolWikiDist" value="{{ $guildPoolData['wiki_xp']['distributed'] ?? 39547 }}" />
                  </div>
                </div>
                <small class="text-body-secondary font-monospace d-block mt-2">
                  {{ __("Formula:") }} <code>{{ __("Remaining") }} (BY2) = BW2 - BX2</code>
                </small>
              </div>
            </div>

            <!-- Lonca XP & Seviye Card -->
            <div class="col-12 col-md-6">
              <div class="p-3 rounded-3 bg-dark bg-opacity-60 border border-secondary border-opacity-20 h-100">
                <h6 class="fw-bold text-warning mb-3 d-flex align-items-center gap-2">
                  <i class="icon-base ti tabler-shield"></i> {{ __('Lonca XP & Seviye') }}
                </h6>
                <div class="row g-2">
                  <div class="col-4">
                    <label class="form-label text-heading small fw-semibold">{{ __('Lonca Toplam XP (BW3)') }}</label>
                    <input type="number" step="0.5" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" id="editPoolGuildTotal" value="{{ $guildPoolData['guild_xp']['total'] ?? 157150 }}" />
                  </div>
                  <div class="col-4">
                    <label class="form-label text-heading small fw-semibold">{{ __('Next Threshold (BX3)') }}</label>
                    <input type="number" step="0.5" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" id="editPoolGuildNext" value="{{ $guildPoolData['guild_xp']['next_level_threshold'] ?? 210000 }}" />
                  </div>
                  <div class="col-4">
                    <label class="form-label text-heading small fw-semibold">{{ __('Lonca Seviyesi') }}</label>
                    <input type="number" min="1" max="50" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace" id="editPoolGuildLevel" value="{{ $guildPoolData['guild_xp']['level'] ?? 12 }}" />
                  </div>
                </div>
                <small class="text-body-secondary font-monospace d-block mt-2">
                  Uzman Kapasite XP: <code>=BW3 * 0.60</code>
                </small>
              </div>
            </div>
          </div>

          <!-- Section 2: Havuz (BV5) & Ek Fon (CA4) -->
          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label class="form-label fw-bold text-heading">{{ __('Havuz XP Bakiyesi (BV5)') }} <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-coins text-warning"></i></span>
                <input type="number" step="0.01" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace fw-bold" id="editPoolTotalVal" value="{{ $guildPoolData['pool_total'] ?? 2064199.61 }}" required />
              </div>
              <small class="text-body-secondary font-monospace">{{ __("Expert Share:") }} <code>=BV5 / {{ __("Expert Count") }}</code></small>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label fw-bold text-heading">{{ __('Ek Fon / Kasa (CA4)') }}</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text bg-dark border-secondary border-opacity-25"><i class="icon-base ti tabler-plus text-info"></i></span>
                <input type="number" step="0.01" class="form-control bg-dark border-secondary border-opacity-25 text-heading font-monospace fw-bold" id="editPoolExtraFund" value="{{ $guildPoolData['extra_fund'] ?? 20570 }}" />
              </div>
              <small class="text-body-secondary font-monospace">Genel Toplam: <code>=SUM(CA9:CA15) + Ek Fon</code></small>
            </div>
          </div>

          <!-- Section 3: 7 Rütbe Detay Tablosu (Olan & Harcanan XP) -->
          <div class="report-section-box mb-2">
            <h6 class="fw-bold text-heading mb-3 d-flex align-items-center gap-2">
              <i class="icon-base ti tabler-users-group text-primary"></i>
              <span>{{ __('Rank Rosters & Spent XP Values (Rows 9..15)') }}</span>
            </h6>

            <div class="table-responsive">
              <table class="table table-sm table-dark text-nowrap mb-0" style="font-size: 0.85rem;">
                <thead>
                  <tr class="text-body-secondary border-bottom border-secondary border-opacity-20">
                    <th style="width: 160px;">{{ __('Rank (BU)') }}</th>
                    <th style="width: 140px;" class="text-end">{{ __('Kapasite (BV)') }}</th>
                    <th style="width: 130px;" class="text-center">{{ __('Current Count (BW)') }}</th>
                    <th style="width: 180px;" class="text-end">{{ __('Harcanan XP (CA)') }}</th>
                    <th style="width: 160px;" class="text-end text-success">{{ __('Hesaplanan Net XP (BZ)') }}</th>
                  </tr>
                </thead>
                <tbody id="editPoolRanksTableBody">
                  @foreach($guildPoolData['ranks'] as $idx => $rk)
                    @php
                      $rName = $rk['rank'];
                    @endphp
                    <tr class="edit-rank-row" data-rank="{{ $rName }}">
                      <td>
                        <span class="fw-bold text-heading">{{ $rName }}</span>
                      </td>
                      <td class="text-end font-monospace text-body-secondary">
                        {{ $rk['capacity'] !== null ? number_format($rk['capacity'], 2) : '-' }}
                      </td>
                      <td>
                        <input type="number" min="0" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace text-center rank-input-count"
                               data-rank="{{ $rName }}" value="{{ $rk['current_count'] ?? 0 }}" />
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-heading font-monospace text-end rank-input-spent"
                               data-rank="{{ $rName }}" value="{{ $rk['spent_xp'] ?? 0 }}" />
                      </td>
                      <td class="text-end font-monospace text-success fw-bold rank-display-net" data-rank="{{ $rName }}">
                        {{ $rk['net_xp'] !== null ? number_format($rk['net_xp'], 2) : '-' }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- Modal Footer -->
        <div class="modal-footer p-3 p-md-4 d-flex align-items-center justify-content-between">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            {{ __('Cancel') }}
          </button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold" id="btnSubmitEditGuildPool">
            <i class="icon-base ti tabler-check me-1 fs-5"></i>
            <span>{{ __('Update Pool Values') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Autocomplete Datalist for Adventurers -->
<datalist id="knownAdventurersList">
  @foreach($adventurers as $advName)
    <option value="{{ $advName }}"></option>
  @endforeach
</datalist>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  let reportsData = @json($reports);
  let guildPoolData = @json($guildPoolData);
  let currentDetailReportId = null;

  // Filter Elements
  const searchInput = document.getElementById('reportsSearchInput');
  const btnSearchClear = document.getElementById('btnReportsSearchClear');
  const typeButtons = document.querySelectorAll('#reportsTypeFilters .filter-pill-btn');
  const gmFilter = document.getElementById('reportsGmFilter');
  const levelFilter = document.getElementById('reportsLevelFilter');
  const sortSelect = document.getElementById('reportsSortSelect');
  const btnResetFilters = document.getElementById('btnResetReportsFilters');
  const btnEmptyResetFilters = document.getElementById('btnEmptyResetFilters');
  const btnImportQuestReports = document.getElementById('btnImportQuestReports');
  const btnClearQuestReports = document.getElementById('btnClearQuestReports');
  const questReportsJsonFile = document.getElementById('questReportsJsonFile');

  if (btnClearQuestReports) {
    btnClearQuestReports.addEventListener('click', async function () {
      const message = @json(__('All reports, quests and participant records will be deleted; player XP will be reset and levels set to 1. Quest board data may be affected. This cannot be undone.'));
      let confirmed;
      if (window.Swal) {
        const result = await Swal.fire({
          title: @json(__('Delete all reports?')),
          text: message,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#ea5455',
          confirmButtonText: @json(__('Yes, delete all')),
          cancelButtonText: '{{ __("Cancel") }}'
        });
        confirmed = result.isConfirmed;
      } else {
        confirmed = confirm(message);
      }
      if (!confirmed) return;

      btnClearQuestReports.disabled = true;
      try {
        const response = await fetch('{{ route("valiant-shade.pages-quest-reports.clear") }}', {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
          }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || @json(__('Reports could not be deleted.')));
        }
        window.location.reload();
      } catch (error) {
        if (window.Swal) {
          Swal.fire({ icon: 'error', title: @json(__('Operation failed')), text: error.message });
        } else {
          alert(error.message);
        }
        btnClearQuestReports.disabled = false;
      }
    });
  }

  if (btnImportQuestReports && questReportsJsonFile) {
    btnImportQuestReports.addEventListener('click', () => questReportsJsonFile.click());
    questReportsJsonFile.addEventListener('change', function () {
      const file = this.files && this.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('file', file);
      btnImportQuestReports.disabled = true;

      fetch('{{ route("valiant-shade.pages-quest-reports.import") }}', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
        },
        body: formData
      })
      .then(async response => {
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || @json(__('Data could not be imported.')));
        }
        return data;
      })
      .then(data => {
        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: @json(__('Data Imported')),
            text: data.message,
            confirmButtonText: '{{ __("OK") }}'
          }).then(() => window.location.reload());
        } else {
          alert(data.message);
          window.location.reload();
        }
      })
      .catch(error => {
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            title: @json(__('Data import failed')),
            text: error.message
          });
        } else {
          alert(error.message);
        }
      })
      .finally(() => {
        btnImportQuestReports.disabled = false;
        questReportsJsonFile.value = '';
      });
    });
  }

  // Table Body & Counter
  const tableBody = document.getElementById('reportsTableBody');
  const emptyState = document.getElementById('reportsEmptyState');
  const currentFilteredCountEl = document.getElementById('currentFilteredCount');

  let currentTypeFilter = 'all';

  // ====================================================
  // ====================================================
  // 1. LIVE FORMULA ENGINE CALCULATOR FUNCTION
  // ====================================================
  // ====================================================
  // 1. LIVE FORMULA ENGINE CALCULATOR FUNCTION
  // Master Sheet Formulas:
  // - Toplam XP: =(Level * 250 + 500) * IF(MQ=="🗸🗸", 3, 1)
  // - Lonca (G): Toplam XP * 0.05
  // - Depo (H): IF(Depo=="✗", Toplam XP * 0.20, 0)
  // - Rapor (I): Toplam XP * 0.10 (Fix %10)
  // - Ortalama: (Toplam XP - Lonca - Depo - Rapor + Ek) / Oyuncu Sayısı
  // ====================================================
  const characterLevelsMap = @json($characterLevels ?? []);
  window.characterLevelsMap = characterLevelsMap;

  function getCharacterLevel(name) {
    if (!name) return 1;
    const clean = String(name).trim().toLowerCase();
    for (const [charName, lvl] of Object.entries(characterLevelsMap)) {
      if (charName.toLowerCase() === clean) {
        return Math.max(1, parseInt(lvl, 10) || 1);
      }
    }
    return 1;
  }

  function escapeHtml(value) {
    const element = document.createElement('span');
    element.textContent = value;
    return element.innerHTML.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function confirmDepotOverride(input, update) {
    input.addEventListener('focus', () => { input.dataset.previousValue = input.value; });
    input.addEventListener('change', () => {
      if (input.value !== input.dataset.previousValue &&
          !confirm('Depo kesintisini elle değiştirmek oyuncuların alacağı XP miktarını değiştirir. Devam edilsin mi?')) {
        input.value = input.dataset.previousValue;
      }
      update();
    });
  }

  function updateCharacterLevels(levels) {
    if (!levels) return;
    Object.keys(characterLevelsMap).forEach(name => delete characterLevelsMap[name]);
    Object.assign(characterLevelsMap, levels);
  }

  function calculateFormula(levelVal, mqVal, depoStatusVal, extraRewardVal, playerCountVal, depoOverride) {
    if (!playerCountVal) {
      return { totalXp: 0, guildCut: 0, depoCut: 0, raporCut: 0, extraReward: 0, netPool: 0, playerCount: 0, avgReward: 0 };
    }
    const level = Math.max(parseInt(levelVal, 10) || 1, 1);
    const isEpic = (mqVal === 'epic' || mqVal === '✓✓' || mqVal === '🗸🗸');
    const multiplier = isEpic ? 3 : 1;

    // XP = (Level * 250 + 500) * multiplier
    const totalXp = (level * 250 + 500) * multiplier;

    // Lonca = XP * 0.05 (5% Guild SP)
    const guildCut = Math.round((totalXp * 0.05) * 100) / 100;

    // Depo = DepoStatus === '0' ? XP * 0.20 : 0 (%20 ceza if failed/undelivered)
    const isDepoOk = (depoStatusVal === '1' || depoStatusVal === true || depoStatusVal === 'true');
    const depoCut = depoOverride !== '' ? Number(depoOverride) : (isDepoOk ? 0 : Math.round((totalXp * 0.20) * 100) / 100);

    // Rapor Kesintisi (I) = XP * 0.10 (Fix %10)
    const raporCut = Math.round((totalXp * 0.10) * 100) / 100;
    const extraReward = parseFloat(extraRewardVal) || 0;
    const playerCount = Math.max(parseInt(playerCountVal, 10) || 1, 1);

    // Ortalama = (XP - Lonca - Depo - Rapor + Ek) / Oyuncu
    const netPool = Math.round((totalXp - guildCut - depoCut - raporCut + extraReward) * 100) / 100;
    const avgReward = Math.round((netPool / playerCount) * 100) / 100;

    return {
      level,
      totalXp,
      guildCut,
      depoCut,
      isDepoOk,
      raporCut,
      extraReward,
      netPool,
      playerCount,
      avgReward
    };
  }

  // ====================================================
  // 2. LIVE CALCULATOR FOR "ADD REPORT" MODAL
  // ====================================================
  const addLevel = document.getElementById('addReportLevel');
  const addLevelBadge = document.getElementById('addReportLevelBadge');
  const addMq = document.getElementById('addReportMq');
  const addStatus = document.getElementById('addReportStatus');
  const addDepoStatus = document.getElementById('addReportDepoStatus');
  const addDepoDisplay = document.getElementById('addReportDepoDisplay');
  const addDepoOverride = document.getElementById('addReportDepoOverride');
  const addRaporCut = document.getElementById('addReportRaporCut');
  const addExtraReward = document.getElementById('addReportExtraReward');
  const addReporter = document.getElementById('addReportReporter');
  const addOtherTagsContainer = document.getElementById('addOtherPlayersTagsContainer');
  const addPartyCountLabel = document.getElementById('addReportPartyCountLabel');
  confirmDepotOverride(addDepoOverride, updateAddLiveCalculator);

  function updateAddLiveCalculator() {
    const otherTags = addOtherTagsContainer ? Array.from(addOtherTagsContainer.querySelectorAll('.other-player-item')) : [];
    const otherPlayers = otherTags.map(el => el.getAttribute('data-name')).filter(Boolean);
    const reporterName = addReporter ? addReporter.value.trim() : '';
    const totalPlayers = (reporterName ? 1 : 0) + otherPlayers.length;

    // 1. Calculate Quest Level automatically based on party members' levels
    const partyRoster = [...(reporterName ? [reporterName] : []), ...otherPlayers];
    const partyLevels = partyRoster.map(pName => getCharacterLevel(pName));
    const avgPartyLevel = partyLevels.length > 0
      ? Math.max(1, Math.round(partyLevels.reduce((a, b) => a + b, 0) / partyLevels.length))
      : 1;

    if (addLevel) {
      addLevel.value = partyLevels.length ? avgPartyLevel : '';
    }
    if (addLevelBadge) {
      const sumLvl = partyLevels.reduce((a, b) => a + b, 0);
      const exactAvg = partyLevels.length ? (sumLvl / partyLevels.length).toFixed(1) : '—';
      addLevelBadge.textContent = partyLevels.length ? `Oto: Lvl ${avgPartyLevel} (Ort: ${exactAvg})` : 'Kadro seçilmedi';
    }

    // 2. Depo Status sync based on Görev Sonucu (Başarılı / Başarısız)
    const isSuccess = addStatus ? (addStatus.value === 'success') : true;
    if (addDepoStatus) {
      addDepoStatus.value = isSuccess ? '1' : '0';
    }
    if (addDepoDisplay) {
      if (isSuccess) {
        addDepoDisplay.value = @json(__('Depot delivered (no penalty)'));
        addDepoDisplay.className = 'form-control form-control-sm border-success text-success fw-semibold';
      } else {
        addDepoDisplay.value = @json(__('Depot not delivered (20% penalty)'));
        addDepoDisplay.className = 'form-control form-control-sm border-danger text-danger fw-semibold';
      }
    }

    if (addPartyCountLabel) {
      addPartyCountLabel.textContent = `${totalPlayers} {{ __("Players") }}`;
    }

    const calc = calculateFormula(
      avgPartyLevel,
      addMq ? addMq.value : 'none',
      addDepoStatus ? addDepoStatus.value : '1',
      addExtraReward ? addExtraReward.value : 0,
      totalPlayers,
      addDepoOverride.value
    );

    // Fixed Rapor Kesintisi (I): XP * 0.10
    if (addRaporCut) {
      addRaporCut.value = calc.raporCut;
    }

    const badge = document.getElementById('liveAddAvgXpBadge');
    const equation = document.getElementById('liveAddFormulaEquation');
    const xpText = document.getElementById('liveAddXpText');
    const guildText = document.getElementById('liveAddGuildCutText');
    const depoText = document.getElementById('liveAddDepoCutText');
    const cutsText = document.getElementById('liveAddCutsText');
    const netPoolText = document.getElementById('liveAddNetPoolText');
    const playerText = document.getElementById('liveAddPlayerCountText');
    const distSummaryBadge = document.getElementById('liveAddDistributionSummaryBadge');
    const distHint = document.getElementById('liveAddDistributeHint');
    const sumCheckText = document.getElementById('liveAddSumCheckText');
    const playersListContainer = document.getElementById('liveAddPlayersDistributionList');

    if (badge) badge.textContent = `Ortalama: ${calc.avgReward.toFixed(2)} XP`;
    if (xpText) xpText.textContent = `${calc.totalXp} XP`;
    if (guildText) guildText.textContent = `${calc.guildCut} SP`;
    if (depoText) depoText.textContent = `${calc.depoCut} SP`;
    if (cutsText) cutsText.textContent = `-${calc.raporCut} / +${calc.extraReward}`;
    if (netPoolText) netPoolText.textContent = `${calc.netPool.toFixed(2)} XP`;
    if (playerText) playerText.textContent = `${calc.playerCount} Oyuncu`;

    if (equation) {
      equation.innerHTML = `=(XP [<span class="text-success fw-bold">${calc.totalXp}</span>] - Lonca [<span class="text-warning fw-bold">${calc.guildCut}</span>] - Depo [<span class="text-danger fw-bold">${calc.depoCut}</span>] - Rapor [<span class="text-info fw-bold">${calc.raporCut}</span>] + Ek [<span class="text-primary fw-bold">${calc.extraReward}</span>]) / Oyuncu [<span class="text-info fw-bold">${calc.playerCount}</span>] = <span class="text-warning fw-bold">${calc.avgReward.toFixed(2)} XP / Oyuncu</span>`;
    }

    if (distSummaryBadge) {
      distSummaryBadge.textContent = `${totalPlayers} {{ __("Players") }} • {{ __("Per Person:") }} ${Math.round(calc.avgReward)} XP`;
    }

    if (distHint) {
      distHint.textContent = `Kaydedildiğinde ${totalPlayers} oyuncuya kişi başı ${Math.round(calc.avgReward)} XP, raporlayana ayrıca ${Math.round(calc.raporCut)} rapor XP’si aktarılır.`;
    }

    if (sumCheckText) {
      const sumTotal = totalPlayers * Math.round(calc.avgReward);
      sumCheckText.textContent = `{{ __("Total Distributed:") }} ${totalPlayers} × ${Math.round(calc.avgReward)} = ${sumTotal} XP ({{ __("Net Pool:") }} ${calc.netPool.toFixed(2)} XP)`;
    }

    // Render Per-Player Interactive Roster in Add modal
    if (playersListContainer) {
      let html = '';
      const repLevel = getCharacterLevel(reporterName);
      // 1. Reporter Card (Leader)
      if (reporterName) html += `
        <div class="party-roster-card reporter-roster-card d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div>
              <div class="fw-bold text-heading" style="font-size: 0.88rem;">${escapeHtml(reporterName)}</div>
              <div class="d-flex align-items-center gap-1">
                <span class="badge bg-label-warning py-0 px-1" style="font-size: 0.65rem;">Lider & Raporlayan</span>
                <span class="badge bg-label-info py-0 px-1" style="font-size: 0.65rem;">Lvl ${repLevel}</span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="text-end">
              <div class="fw-bold text-warning font-monospace" style="font-size: 0.85rem;">+${Math.round(calc.avgReward)} XP + ${Math.round(calc.raporCut)} {{ __("Rapor") }}</div>
              <small class="text-muted" style="font-size: 0.65rem;">{{ __("Share Per Person") }}</small>
            </div>
            <span class="badge bg-label-secondary py-1 px-2" style="font-size: 0.7rem;" title="{{ __("Leader cannot be removed from party") }}">
              <i class="icon-base ti tabler-lock"></i>
            </span>
          </div>
        </div>
      `;

      // 2. Other Players Roster Cards with direct [Çıkar] button
      if (otherPlayers.length > 0) {
        otherPlayers.forEach((pName, pIdx) => {
          const charLvl = getCharacterLevel(pName);
          html += `
            <div class="party-roster-card d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-xs bg-label-primary text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:28px; height:28px; font-size:12px;">#${pIdx + 1}</div>
                <div>
                  <div class="fw-bold text-heading" style="font-size: 0.88rem;">${escapeHtml(pName)}</div>
                  <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-label-secondary py-0 px-1" style="font-size: 0.65rem;">{{ __("Party Member") }}</span>
                    <span class="badge bg-label-info py-0 px-1" style="font-size: 0.65rem;">Lvl ${charLvl}</span>
                  </div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <div class="text-end">
                  <div class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;">+${Math.round(calc.avgReward)} XP</div>
                  <small class="text-muted" style="font-size: 0.65rem;">{{ __("Share Per Person") }}</small>
                </div>
                <button type="button" class="btn btn-remove-roster-player btn-add-modal-remove" data-name="${escapeHtml(pName)}" title="{{ __("Remove adventurer from party") }}">
                  <i class="icon-base ti tabler-trash" style="font-size: 0.85rem;"></i>
                  <span>{{ __("Remove") }}</span>
                </button>
              </div>
            </div>
          `;
        });
      } else {
        html += `
          <div class="text-center py-2 text-muted small fst-italic">
            <i class="icon-base ti tabler-info-circle me-1"></i>{{ __("No other adventurers added yet. Type a name above and click Add.") }}
          </div>
        `;
      }

      playersListContainer.innerHTML = html;
    }
  }

  // Remove player directly from active roster in Add Modal
  const liveAddPlayersList = document.getElementById('liveAddPlayersDistributionList');
  if (liveAddPlayersList) {
    liveAddPlayersList.addEventListener('click', function(e) {
      const removeBtn = e.target.closest('.btn-remove-roster-player');
      if (!removeBtn) return;
      const name = removeBtn.getAttribute('data-name');
      if (!name) return;

      if (addOtherTagsContainer) {
        const item = Array.from(addOtherTagsContainer.querySelectorAll('.other-player-item'))
          .find(el => el.getAttribute('data-name').toLowerCase() === name.toLowerCase());
        if (item) item.remove();
      }
      updateAddLiveCalculator();
    });
  }

  // Clear all other players in Add Modal
  const btnClearAllOtherPlayers = document.getElementById('btnClearAllOtherPlayersBtn');
  if (btnClearAllOtherPlayers) {
    btnClearAllOtherPlayers.addEventListener('click', function() {
      if (addOtherTagsContainer) {
        addOtherTagsContainer.innerHTML = '';
        updateAddLiveCalculator();
      }
    });
  }

  [addMq, addStatus, addExtraReward, addReporter].forEach(el => {
    if (el) {
      el.addEventListener('input', updateAddLiveCalculator);
      el.addEventListener('change', updateAddLiveCalculator);
    }
  });

  // Add tag button in Add Modal
  const inputAddOther = document.getElementById('inputAddOtherPlayer');
  const btnAddOther = document.getElementById('btnAddOtherPlayerBtn');

  function addOtherPlayerTag() {
    if (!inputAddOther) return;
    const name = inputAddOther.value.trim();
    if (!name) {
      inputAddOther.focus();
      return;
    }

    const reporterName = addReporter ? addReporter.value.trim().toLowerCase() : '';
    if (name.toLowerCase() === reporterName) {
      inputAddOther.value = '';
      return;
    }

    // Check duplicate
    const existing = Array.from(addOtherTagsContainer.querySelectorAll('.other-player-item'))
      .map(tag => tag.getAttribute('data-name').toLowerCase());

    if (existing.includes(name.toLowerCase())) {
      inputAddOther.value = '';
      return;
    }

    const span = document.createElement('span');
    span.className = 'party-pill-tag other-player-item';
    span.setAttribute('data-name', name);
    span.textContent = name;
    addOtherTagsContainer.appendChild(span);
    inputAddOther.value = '';
    inputAddOther.focus();
    updateAddLiveCalculator();
  }

  if (btnAddOther) btnAddOther.addEventListener('click', addOtherPlayerTag);
  if (inputAddOther) {
    inputAddOther.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addOtherPlayerTag();
      }
    });
  }

  if (addOtherTagsContainer) {
    addOtherTagsContainer.addEventListener('click', function (e) {
      if (e.target.classList.contains('btn-remove-other') || e.target.closest('.btn-remove-other')) {
        const item = e.target.closest('.other-player-item');
        if (item) {
          item.remove();
          updateAddLiveCalculator();
        }
      }
    });
  }

  // Open Add Modal from any trigger button
  const modalAddEl = document.getElementById('modalAddQuestReport');
  const addTriggerButtons = document.querySelectorAll('.btn-add-report-trigger, #btnOpenAddReportModal, #btnTopOpenAddReportModal, #btnTableOpenAddReportModal, #btnGuildPoolOpenAddReportModal');
  addTriggerButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      if (modalAddEl) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalAddEl);
        bsModal.show();
        updateAddLiveCalculator();
      }
    });
  });

  // Submit Add Quest Report
  const formAddReport = document.getElementById('formAddQuestReport');
  if (formAddReport) {
    modalAddEl.addEventListener('hidden.bs.modal', function () {
      formAddReport.reset();
      addOtherTagsContainer.replaceChildren();
      updateAddLiveCalculator();
    });

    formAddReport.addEventListener('submit', function (e) {
      e.preventDefault();

      const otherPlayers = Array.from(addOtherTagsContainer.querySelectorAll('.other-player-item'))
        .map(el => el.getAttribute('data-name'));

      const payload = {
        title: document.getElementById('addReportTitle').value.trim(),
        date: document.getElementById('addReportDate').value.trim(),
        mq: addMq.value,
        auto_party_level: 1,
        status: addStatus.value,
        level: parseInt(addLevel.value, 10),
        depo_status: addDepoStatus.value === '1',
        depo_cut_override: addDepoOverride.value === '' ? null : Number(addDepoOverride.value),
        rapor_cut: parseFloat(addRaporCut.value) || 0,
        extra_reward: parseFloat(addExtraReward.value) || 0,
        gm: document.getElementById('addReportGm').value.trim(),
        reporter: document.getElementById('addReportReporter').value.trim(),
        other_players: otherPlayers,
        location: document.getElementById('addReportLocation').value.trim(),
        enemies: document.getElementById('addReportEnemies').value.trim(),
        full_report: document.getElementById('addReportFullText').value.trim(),
        distribute_xp_auto: document.getElementById('addReportDistributeXp')?.checked ? 1 : 0,
        _token: document.querySelector('input[name="_token"]')?.value
      };

      const submitBtn = document.getElementById('btnSubmitAddQuestReport');
      if (submitBtn) submitBtn.disabled = true;

      fetch('{{ route("valiant-shade.pages-quest-reports.store") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (!data.success) {
          alert(data.message || '{{ __("Could not save quest report.") }}');
          return;
        }

        const newRep = data.report;
        updateCharacterLevels(data.characterLevels);
        reportsData.unshift(newRep);

        // Hide modal
        const bsModal = bootstrap.Modal.getInstance(modalAddEl);
        if (bsModal) bsModal.hide();
        formAddReport.reset();
        addOtherTagsContainer.replaceChildren();
        updateAddLiveCalculator();

        updateStatsDom(data.stats);
        currentPage = 1;
        renderTable();

        const unlinked = data.distribution?.players_awarded.filter(player => !player.character_id).map(player => player.name) || [];
        if (window.Swal) {
          let distributeMsg = '';
          if (data.distribution && data.distribution.total_awarded_count > 0) {
            distributeMsg = ` and +${data.distribution.xp_per_player} XP awarded to ${data.distribution.total_awarded_count} adventurers`;
          }

          const unlinkedMsg = unlinked.length ? ` Karakter kaydı olmayanlar için yalnızca XP Tracker kullanıldı: ${unlinked.join(', ')}.` : '';
          Swal.fire({
            icon: unlinked.length ? 'warning' : 'success',
            title: '{{ __("Quest Report Added!") }}',
            text: `"${newRep.title}" {{ __("quest report (Average per person: :avg XP) was successfully archived.", ["avg" => ""]) }}${newRep.avg_reward} XP)${distributeMsg}${unlinkedMsg}`,
            ...(unlinked.length ? {} : { timer: 2400, showConfirmButton: false })
          });
        } else if (unlinked.length) {
          alert(`Karakter kaydı olmayanlar için yalnızca XP Tracker kullanıldı: ${unlinked.join(', ')}.`);
        }
      })
      .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        alert('{{ __("Server communication error occurred.") }}');
      });
    });
  }

  // ====================================================
  // 3. EDIT REPORT MODAL ENGINE
  // ====================================================
  const modalEditEl = document.getElementById('modalEditQuestReport');
  const formEditReport = document.getElementById('formEditQuestReport');
  const editId = document.getElementById('editReportId');
  const editTitle = document.getElementById('editReportTitle');
  const editDate = document.getElementById('editReportDate');
  const editMq = document.getElementById('editReportMq');
  const editLevel = document.getElementById('editReportLevel');
  const editLevelBadge = document.getElementById('editReportLevelBadge');
  const editStatus = document.getElementById('editReportStatus');
  const editDepoStatus = document.getElementById('editReportDepoStatus');
  const editDepoDisplay = document.getElementById('editReportDepoDisplay');
  const editDepoOverride = document.getElementById('editReportDepoOverride');
  const editRaporCut = document.getElementById('editReportRaporCut');
  const editExtraReward = document.getElementById('editReportExtraReward');
  const editGm = document.getElementById('editReportGm');
  const editReporter = document.getElementById('editReportReporter');
  const editOtherTagsContainer = document.getElementById('editOtherPlayersTagsContainer');
  const editPartyCountLabel = document.getElementById('editReportPartyCountLabel');
  const editLocation = document.getElementById('editReportLocation');
  const editEnemies = document.getElementById('editReportEnemies');
  const editFullText = document.getElementById('editReportFullText');
  const inputEditOther = document.getElementById('inputEditOtherPlayer');
  const btnEditOther = document.getElementById('btnEditOtherPlayerBtn');
  confirmDepotOverride(editDepoOverride, updateEditLiveCalculator);

  function updateEditLiveCalculator() {
    const otherTags = editOtherTagsContainer ? Array.from(editOtherTagsContainer.querySelectorAll('.other-player-item')) : [];
    const otherPlayers = otherTags.map(el => el.getAttribute('data-name')).filter(Boolean);
    const reporterName = editReporter ? editReporter.value.trim() : '';
    const totalPlayers = (reporterName ? 1 : 0) + otherPlayers.length;

    // 1. Calculate Quest Level automatically based on party members' levels
    const partyRoster = [...(reporterName ? [reporterName] : []), ...otherPlayers];
    const partyLevels = partyRoster.map(pName => getCharacterLevel(pName));
    const avgPartyLevel = partyLevels.length > 0
      ? Math.max(1, Math.round(partyLevels.reduce((a, b) => a + b, 0) / partyLevels.length))
      : 1;

    if (editLevel) {
      editLevel.value = partyLevels.length ? avgPartyLevel : '';
    }
    if (editLevelBadge) {
      const sumLvl = partyLevels.reduce((a, b) => a + b, 0);
      const exactAvg = partyLevels.length ? (sumLvl / partyLevels.length).toFixed(1) : '—';
      editLevelBadge.textContent = partyLevels.length ? `Oto: Lvl ${avgPartyLevel} (Ort: ${exactAvg})` : 'Kadro seçilmedi';
    }

    // 2. Depo Status sync based on Görev Sonucu (Başarılı / Başarısız)
    const isSuccess = editStatus ? (editStatus.value === 'success') : true;
    if (editDepoStatus) {
      editDepoStatus.value = isSuccess ? '1' : '0';
    }
    if (editDepoDisplay) {
      if (isSuccess) {
        editDepoDisplay.value = @json(__('Depot delivered (no penalty)'));
        editDepoDisplay.className = 'form-control form-control-sm border-success text-success fw-semibold';
      } else {
        editDepoDisplay.value = @json(__('Depot not delivered (20% penalty)'));
        editDepoDisplay.className = 'form-control form-control-sm border-danger text-danger fw-semibold';
      }
    }

    if (editPartyCountLabel) {
      editPartyCountLabel.textContent = `${totalPlayers} {{ __("Players") }}`;
    }

    const calc = calculateFormula(
      avgPartyLevel,
      editMq ? editMq.value : 'none',
      editDepoStatus ? editDepoStatus.value : '1',
      editExtraReward ? editExtraReward.value : 0,
      totalPlayers,
      editDepoOverride.value
    );

    // Fixed Rapor Kesintisi (I): XP * 0.10
    if (editRaporCut) {
      editRaporCut.value = calc.raporCut;
    }

    const badge = document.getElementById('liveEditAvgXpBadge');
    const equation = document.getElementById('liveEditFormulaEquation');
    const xpText = document.getElementById('liveEditXpText');
    const guildText = document.getElementById('liveEditGuildCutText');
    const depoText = document.getElementById('liveEditDepoCutText');
    const cutsText = document.getElementById('liveEditCutsText');
    const netPoolText = document.getElementById('liveEditNetPoolText');
    const playerText = document.getElementById('liveEditPlayerCountText');
    const distSummaryBadge = document.getElementById('liveEditDistributionSummaryBadge');
    const sumCheckText = document.getElementById('liveEditSumCheckText');
    const playersListContainer = document.getElementById('liveEditPlayersDistributionList');

    if (badge) badge.textContent = `Ortalama: ${calc.avgReward.toFixed(2)} XP`;
    if (xpText) xpText.textContent = `${calc.totalXp} XP`;
    if (guildText) guildText.textContent = `${calc.guildCut} SP`;
    if (depoText) depoText.textContent = `${calc.depoCut} SP`;
    if (cutsText) cutsText.textContent = `-${calc.raporCut} / +${calc.extraReward}`;
    if (netPoolText) netPoolText.textContent = `${calc.netPool.toFixed(2)} XP`;
    if (playerText) playerText.textContent = `${calc.playerCount} Oyuncu`;

    if (equation) {
      equation.innerHTML = `=(XP [<span class="text-success fw-bold">${calc.totalXp}</span>] - Lonca [<span class="text-warning fw-bold">${calc.guildCut}</span>] - Depo [<span class="text-danger fw-bold">${calc.depoCut}</span>] - Rapor [<span class="text-info fw-bold">${calc.raporCut}</span>] + Ek [<span class="text-primary fw-bold">${calc.extraReward}</span>]) / Oyuncu [<span class="text-info fw-bold">${calc.playerCount}</span>] = <span class="text-warning fw-bold">${calc.avgReward.toFixed(2)} XP / Oyuncu</span>`;
    }

    if (distSummaryBadge) {
      distSummaryBadge.textContent = `${totalPlayers} {{ __("Players") }} • {{ __("Per Person:") }} ${Math.round(calc.avgReward)} XP`;
    }

    if (sumCheckText) {
      const sumTotal = totalPlayers * Math.round(calc.avgReward);
      sumCheckText.textContent = `{{ __("Total Distributed:") }} ${totalPlayers} × ${Math.round(calc.avgReward)} = ${sumTotal} XP ({{ __("Net Pool:") }} ${calc.netPool.toFixed(2)} XP)`;
    }

    // Render Per-Player Interactive Roster in Edit modal
    if (playersListContainer) {
      let html = '';
      const repLevel = getCharacterLevel(reporterName);
      if (reporterName) html += `
        <div class="party-roster-card reporter-roster-card d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div>
              <div class="fw-bold text-heading" style="font-size: 0.88rem;">${escapeHtml(reporterName)}</div>
              <div class="d-flex align-items-center gap-1">
                <span class="badge bg-label-warning py-0 px-1" style="font-size: 0.65rem;">Lider & Raporlayan</span>
                <span class="badge bg-label-info py-0 px-1" style="font-size: 0.65rem;">Lvl ${repLevel}</span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="text-end">
              <div class="fw-bold text-warning font-monospace" style="font-size: 0.85rem;">+${Math.round(calc.avgReward)} XP + ${Math.round(calc.raporCut)} {{ __("Rapor") }}</div>
              <small class="text-muted" style="font-size: 0.65rem;">{{ __("Share Per Person") }}</small>
            </div>
            <span class="badge bg-label-secondary py-1 px-2" style="font-size: 0.7rem;" title="{{ __("Leader cannot be removed from party") }}">
              <i class="icon-base ti tabler-lock"></i>
            </span>
          </div>
        </div>
      `;

      if (otherPlayers.length > 0) {
        otherPlayers.forEach((pName, pIdx) => {
          const charLvl = getCharacterLevel(pName);
          html += `
            <div class="party-roster-card d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-xs bg-label-primary text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:28px; height:28px; font-size:12px;">#${pIdx + 1}</div>
                <div>
                  <div class="fw-bold text-heading" style="font-size: 0.88rem;">${escapeHtml(pName)}</div>
                  <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-label-secondary py-0 px-1" style="font-size: 0.65rem;">{{ __("Party Member") }}</span>
                    <span class="badge bg-label-info py-0 px-1" style="font-size: 0.65rem;">Lvl ${charLvl}</span>
                  </div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <div class="text-end">
                  <div class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;">+${Math.round(calc.avgReward)} XP</div>
                  <small class="text-muted" style="font-size: 0.65rem;">{{ __("Share Per Person") }}</small>
                </div>
                <button type="button" class="btn btn-remove-roster-player btn-edit-modal-remove" data-name="${escapeHtml(pName)}" title="{{ __("Remove adventurer from party") }}">
                  <i class="icon-base ti tabler-trash" style="font-size: 0.85rem;"></i>
                  <span>{{ __("Remove") }}</span>
                </button>
              </div>
            </div>
          `;
        });
      } else {
        html += `
          <div class="text-center py-2 text-muted small fst-italic">
            <i class="icon-base ti tabler-info-circle me-1"></i>{{ __("No other adventurers added yet. Type a name above and click Add.") }}
          </div>
        `;
      }

      playersListContainer.innerHTML = html;
    }
  }

  // Remove player directly from active roster in Edit Modal
  const liveEditPlayersList = document.getElementById('liveEditPlayersDistributionList');
  if (liveEditPlayersList) {
    liveEditPlayersList.addEventListener('click', function(e) {
      const removeBtn = e.target.closest('.btn-remove-roster-player');
      if (!removeBtn) return;
      const name = removeBtn.getAttribute('data-name');
      if (!name) return;

      if (editOtherTagsContainer) {
        const item = Array.from(editOtherTagsContainer.querySelectorAll('.other-player-item'))
          .find(el => el.getAttribute('data-name').toLowerCase() === name.toLowerCase());
        if (item) item.remove();
      }
      updateEditLiveCalculator();
    });
  }

  // Clear all other players in Edit Modal
  const btnClearAllEditOtherPlayers = document.getElementById('btnClearAllEditOtherPlayersBtn');
  if (btnClearAllEditOtherPlayers) {
    btnClearAllEditOtherPlayers.addEventListener('click', function() {
      if (editOtherTagsContainer) {
        editOtherTagsContainer.innerHTML = '';
        updateEditLiveCalculator();
      }
    });
  }

  [editMq, editStatus, editExtraReward, editReporter].forEach(el => {
    if (el) {
      el.addEventListener('input', updateEditLiveCalculator);
      el.addEventListener('change', updateEditLiveCalculator);
    }
  });

  function addEditOtherPlayerTag() {
    if (!inputEditOther) return;
    const name = inputEditOther.value.trim();
    if (!name) {
      inputEditOther.focus();
      return;
    }

    const reporterName = editReporter ? editReporter.value.trim().toLowerCase() : '';
    if (name.toLowerCase() === reporterName) {
      inputEditOther.value = '';
      return;
    }

    const existing = Array.from(editOtherTagsContainer.querySelectorAll('.other-player-item'))
      .map(tag => tag.getAttribute('data-name').toLowerCase());

    if (existing.includes(name.toLowerCase())) {
      inputEditOther.value = '';
      return;
    }

    const span = document.createElement('span');
    span.className = 'party-pill-tag other-player-item';
    span.setAttribute('data-name', name);
    span.textContent = name;
    editOtherTagsContainer.appendChild(span);
    inputEditOther.value = '';
    inputEditOther.focus();
    updateEditLiveCalculator();
  }

  if (btnEditOther) btnEditOther.addEventListener('click', addEditOtherPlayerTag);
  if (inputEditOther) {
    inputEditOther.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addEditOtherPlayerTag();
      }
    });
  }

  if (editOtherTagsContainer) {
    editOtherTagsContainer.addEventListener('click', function (e) {
      if (e.target.classList.contains('btn-remove-other') || e.target.closest('.btn-remove-other')) {
        const item = e.target.closest('.other-player-item');
        if (item) {
          item.remove();
          updateEditLiveCalculator();
        }
      }
    });
  }

  function formatDateForInput(dateStr) {
    if (!dateStr) return '';
    const dmyMatch = String(dateStr).match(/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/);
    if (dmyMatch) {
      const d = dmyMatch[1].padStart(2, '0');
      const m = dmyMatch[2].padStart(2, '0');
      const y = dmyMatch[3];
      return `${y}-${m}-${d}`;
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
    return '';
  }

  window.openEditReportModal = function (id) {
    const report = reportsData.find(r => r.id === parseInt(id, 10));
    if (!report) return;

    document.getElementById('editReportSubtitle').textContent = `{{ __("Report") }} #${report.id} - ${report.title} ({{ __("Editing") }})`;
    editId.value = report.id;
    editTitle.value = report.title;
    editDate.value = formatDateForInput(report.date);
    editMq.value = report.mq || (report.tier_type === 'epic' ? 'epic' : (report.is_main ? 'main' : 'none'));

    const isSuccess = report.status
      ? report.status !== 'failed'
      : (report.depo_status !== false && report.depo_status !== 0 && report.depo_status !== '0' && (parseFloat(report.depo_cut) || 0) === 0);
    if (editStatus) {
      editStatus.value = isSuccess ? 'success' : 'failed';
    }

    editExtraReward.value = report.extra_reward ?? 0;
    editGm.value = report.gm ?? '';
    editReporter.value = report.reporter ?? '';
    editDepoOverride.value = report.depo_cut_override ?? '';
    editLocation.value = report.location ?? '';
    editEnemies.value = report.enemies ?? '';
    editFullText.value = report.full_report ?? '';

    // Populate other player tags
    if (editOtherTagsContainer) {
      editOtherTagsContainer.innerHTML = '';
      (report.other_players || []).forEach(name => {
        const span = document.createElement('span');
        span.className = 'party-pill-tag other-player-item';
        span.setAttribute('data-name', name);
        span.textContent = name;
        editOtherTagsContainer.appendChild(span);
      });
    }

    updateEditLiveCalculator();

    if (modalEditEl && window.bootstrap) {
      const bsModal = new bootstrap.Modal(modalEditEl);
      bsModal.show();
    }
  };

  if (formEditReport) {
    formEditReport.addEventListener('submit', function (e) {
      e.preventDefault();
      const repId = parseInt(editId.value, 10);
      const otherPlayers = Array.from(editOtherTagsContainer.querySelectorAll('.other-player-item'))
        .map(el => el.getAttribute('data-name'));

      const payload = {
        title: editTitle.value.trim(),
        date: editDate.value.trim(),
        mq: editMq.value,
        auto_party_level: 1,
        status: editStatus.value,
        level: parseInt(editLevel.value, 10),
        depo_status: editDepoStatus.value === '1',
        depo_cut_override: editDepoOverride.value === '' ? null : Number(editDepoOverride.value),
        rapor_cut: parseFloat(editRaporCut.value) || 0,
        extra_reward: parseFloat(editExtraReward.value) || 0,
        gm: editGm.value.trim(),
        reporter: editReporter.value.trim(),
        other_players: otherPlayers,
        location: editLocation.value.trim(),
        enemies: editEnemies.value.trim(),
        full_report: editFullText.value.trim(),
        _token: document.querySelector('input[name="_token"]')?.value
      };

      const submitBtn = document.getElementById('btnSubmitEditQuestReport');
      if (submitBtn) submitBtn.disabled = true;

      fetch(`{{ url('/valiant-shade/pages/quest-reports') }}/${repId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (!data.success) {
          alert(data.message || '{{ __("Update failed.") }}');
          return;
        }

        const idx = reportsData.findIndex(r => r.id === repId);
        updateCharacterLevels(data.characterLevels);
        if (idx !== -1) {
          reportsData[idx] = data.report;
        }

        const bsModal = bootstrap.Modal.getInstance(modalEditEl);
        if (bsModal) bsModal.hide();

        updateStatsDom(data.stats);
        renderTable();

        if (data.xp_warning || data.xp_unlinked?.length) {
          alert(data.xp_warning || `Karakter kaydı olmayanlar için yalnızca XP Tracker kullanıldı: ${data.xp_unlinked.join(', ')}.`);
        } else if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Quest Report Updated!") }}',
            text: `"${data.report.title}" {{ __("quest report and new average (:avg XP) were successfully updated.", ["avg" => ""]) }}${data.report.avg_reward} XP)`,
            timer: 2000,
            showConfirmButton: false
          });
        }
      })
      .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        alert('{{ __("Server communication error occurred.") }}');
      });
    });
  }

  // ====================================================
  // 4. DELETE REPORT
  // ====================================================
  window.deleteReportItem = function (id) {
    const report = reportsData.find(r => r.id === parseInt(id, 10));
    if (!report) return;

    if (!window.Swal) {
      if (!confirm(`{{ __("Are you sure you want to delete this quest report?") }} (${report.title})`)) return;
      executeDelete(id);
      return;
    }

    Swal.fire({
      title: '{{ __("Rapor Silinsin mi?") }}',
      text: `"${report.title}" silinecek. Kayıtlı XP dağıtımı varsa karakterlerden geri alınacak.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ea5455',
      cancelButtonColor: '#82868b',
      confirmButtonText: 'Evet, Sil!',
      cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
      if (result.isConfirmed) {
        executeDelete(id);
      }
    });

    function executeDelete(repId) {
      fetch(`{{ url('/valiant-shade/pages/quest-reports') }}/${repId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
        }
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          alert(data.message || '{{ __("Deletion failed.") }}');
          return;
        }

        reportsData = reportsData.filter(r => r.id !== parseInt(repId, 10));
        updateCharacterLevels(data.characterLevels);
        updateStatsDom(data.stats);
        renderTable();

        if (data.xp_warning) {
          alert(data.xp_warning);
        } else if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Silindi!") }}',
            text: '{{ __("Quest report successfully deleted.") }}',
            timer: 1600,
            showConfirmButton: false
          });
        }
      })
      .catch(() => alert('{{ __("Server communication error occurred.") }}'));
    }
  };

  // ====================================================
  // 5. DISTRIBUTE XP TO PLAYER TRACKER
  // ====================================================
  window.distributeReportXp = function (id) {
    const report = reportsData.find(r => r.id === parseInt(id, 10));
    if (!report) return;

    if (!window.Swal) {
      executeDistribute(id);
      return;
    }

    Swal.fire({
      title: '{{ __("Transfer XP to Adventurers?") }}',
      html: `{{ __("Average XP (:avg XP) from this quest will be transferred to party adventurers:", ["avg" => ""]) }}${report.avg_reward} XP (<i>${[report.reporter, ...(report.other_players || [])].join(', ')}</i>)`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28c76f',
      cancelButtonColor: '#82868b',
      confirmButtonText: '{{ __("Yes, Distribute XP!") }}',
      cancelButtonText: '{{ __("Cancel") }}'
    }).then((res) => {
      if (res.isConfirmed) {
        executeDistribute(id);
      }
    });

    function executeDistribute(repId) {
      fetch(`{{ url('/valiant-shade/pages/quest-reports') }}/${repId}/distribute-xp`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
        }
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          alert(data.message || '{{ __("Could not distribute XP.") }}');
          return;
        }
        updateCharacterLevels(data.characterLevels);

        if (window.Swal) {
          let listHtml = '';
          if (data.data && data.data.players_awarded) {
            listHtml = '<ul class="list-unstyled text-start small font-monospace mt-2 mb-0">' +
              data.data.players_awarded.map(p => `<li><b>${escapeHtml(p.name)}</b>: +${p.quest_xp + p.report_xp} XP${p.character_id ? '' : ' (yalnızca XP Tracker)'}</li>`).join('') +
              '</ul>';
          }

          Swal.fire({
            icon: 'success',
            title: '{{ __("XP Successfully Distributed!") }}',
            html: `+${data.data.xp_per_player} XP {{ __("awarded to :count adventurers from quest:", ["count" => ""]) }}${data.data.total_awarded_count}</b>:${listHtml}`,
            confirmButtonColor: '#7367f0'
          });
        } else {
          alert(`XP dağıtıldı: ${data.data.players_awarded.map(p => `${p.name} +${p.quest_xp + p.report_xp} XP${p.character_id ? '' : ' (yalnızca XP Tracker)'}`).join(', ')}`);
        }
      })
      .catch(() => alert('{{ __("Server communication error occurred.") }}'));
    }
  };

  const btnModalDistributeXp = document.getElementById('btnModalDistributeXp');
  if (btnModalDistributeXp) {
    btnModalDistributeXp.addEventListener('click', function () {
      if (currentDetailReportId) {
        distributeReportXp(currentDetailReportId);
      }
    });
  }

  // ====================================================
  // 7. DETAIL DOSSIER MODAL
  // ====================================================
  window.openReportDetail = function (id) {
    const r = reportsData.find(item => item.id === parseInt(id, 10));
    if (!r) return;

    currentDetailReportId = r.id;

    // Header
    const titleEl = document.getElementById('modalReportTitle');
    const dateEl = document.getElementById('modalReportDate');
    const tierBadge = document.getElementById('modalReportTierBadge');
    const statusBadge = document.getElementById('modalReportStatusBadge');
    const locationEl = document.getElementById('modalReportLocation');
    const gmEl = document.getElementById('modalReportGm');
    const reporterEl = document.getElementById('modalReportReporter');
    const levelEl = document.getElementById('modalReportLevel');
    const avgBadge = document.getElementById('modalReportAvgBadge');
    const formulaText = document.getElementById('modalReportFormulaText');

    if (titleEl) titleEl.textContent = r.title;
    if (dateEl) dateEl.textContent = r.date;
    if (locationEl) locationEl.textContent = r.location || '—';
    if (gmEl) gmEl.textContent = `GM ${r.gm || 'Shalvaz'}`;
    if (reporterEl) reporterEl.textContent = r.reporter || 'Tolrun';
    if (levelEl) levelEl.textContent = `Level ${r.level || 1}`;

    const avgVal = parseFloat(r.avg_reward || 0).toFixed(2);
    if (avgBadge) avgBadge.textContent = `${avgVal} XP / Oyuncu`;

    if (formulaText) {
      formulaText.textContent = `= (XP [${r.total_xp}] - Lonca [${r.guild_cut}] - Depo [${r.depo_cut}] - Rapor [${r.rapor_cut ?? r.vault_cut ?? 0}] + Ek [${r.extra_reward ?? 0}]) / Oyuncu [${r.player_count}] = ${avgVal} XP`;
    }

    if (tierBadge) {
      if (r.mq === 'epic' || r.tier_type === 'epic') {
        tierBadge.className = 'badge badge-mq-epic font-monospace';
        tierBadge.textContent = '{{ __("Epic Main Quest") }} (3x)';
      } else if (r.is_main || r.mq === 'main') {
        tierBadge.className = 'badge badge-mq-main font-monospace';
        tierBadge.textContent = '{{ __("Main Quest") }} (1x)';
      } else {
        tierBadge.className = 'badge badge-mq-side font-monospace';
        tierBadge.textContent = '{{ __("Side Quest") }}';
      }
    }

    if (statusBadge) {
      if ((r.status || 'success') === 'failed') {
        statusBadge.className = 'badge bg-label-danger text-danger border border-danger border-opacity-25';
        statusBadge.textContent = '{{ __("Failed") }}';
      } else {
        statusBadge.className = 'badge bg-label-success text-success border border-success border-opacity-25';
        statusBadge.textContent = '{{ __("Successful") }}';
      }
    }

    // Debrief narrative
    const fullTextEl = document.getElementById('modalReportFullText');
    const enemiesEl = document.getElementById('modalReportEnemies');
    if (fullTextEl) fullTextEl.textContent = r.full_report || r.report_summary || '—';
    if (enemiesEl) enemiesEl.textContent = r.enemies || '—';

    // Party Roster
    const partyCountEl = document.getElementById('modalReportPartyCount');
    const partyBody = document.getElementById('modalReportPartyTableBody');

    if (partyCountEl) partyCountEl.textContent = `${r.player_count || 1} Oyuncu`;

    if (partyBody) {
      partyBody.innerHTML = '';
      const allMembers = [];
      allMembers.push({
        name: r.reporter,
        role: 'Raporlayan (AN)',
        level: r.level || 1,
        xp: avgVal
      });

      (r.other_players || []).forEach(name => {
        allMembers.push({
          name: name,
          role: '{{ __("Other Player") }}',
          level: r.level || 1,
          xp: avgVal
        });
      });

      allMembers.forEach(m => {
        const isReporter = m.role.includes('Raporlayan');
        const tr = document.createElement('tr');
        tr.className = 'border-bottom border-secondary border-opacity-10';
        tr.innerHTML = `
          <td class="fw-semibold text-heading d-flex align-items-center gap-1">
            ${isReporter ? '<i class="icon-base ti tabler-feather text-warning" title="Raporlayan"></i>' : '<i class="icon-base ti tabler-user text-primary opacity-50"></i>'}
            <span>${m.name}</span>
          </td>
          <td><span class="badge ${isReporter ? 'bg-label-warning text-warning' : 'bg-label-secondary text-body'} font-monospace" style="font-size: 0.72rem;">${m.role}</span></td>
          <td class="font-monospace fw-bold text-info">Lvl ${m.level}</td>
          <td class="text-end font-monospace text-success fw-bold">+${m.xp} XP</td>
        `;
        partyBody.appendChild(tr);
      });
    }

    // Financial Breakdown
    const totalXpEl = document.getElementById('modalReportTotalXp');
    const guildCutEl = document.getElementById('modalReportGuildCut');
    const depoCutEl = document.getElementById('modalReportDepoCut');
    const avgRewardEl = document.getElementById('modalReportAvgReward');

    if (totalXpEl) totalXpEl.textContent = `+${r.total_xp} XP`;
    if (guildCutEl) guildCutEl.textContent = `${r.guild_cut || 0} SP`;
    if (depoCutEl) depoCutEl.textContent = `${r.depo_cut || 0} SP`;
    if (avgRewardEl) avgRewardEl.textContent = `${avgVal} XP`;

    // Show modal
    const modalDetailEl = document.getElementById('questReportDetailModal');
    if (modalDetailEl && window.bootstrap) {
      const bsModal = new bootstrap.Modal(modalDetailEl);
      bsModal.show();
    }
  };

  // ====================================================
  // 8. TABLE RENDERING, FILTERING & SORTING
  // ====================================================
  function updateStatsDom(s) {
    if (!s) return;
    const kpiTotalBadge = document.getElementById('kpiTotalReportsBadge');
    const kpiTotalVal = document.getElementById('kpiTotalQuestsVal');
    const kpiMainVal = document.getElementById('kpiMainQuestsVal');
    const kpiSideVal = document.getElementById('kpiSideQuestsVal');
    const kpiXpVal = document.getElementById('kpiTotalXpVal');
    const kpiDepoVal = document.getElementById('kpiTotalDepoCutVal');

    if (kpiTotalBadge) kpiTotalBadge.textContent = `${s.total_quests} {{ __('Recorded Expeditions') }}`;
    if (kpiTotalVal) kpiTotalVal.textContent = s.total_quests;
    if (kpiMainVal) kpiMainVal.innerHTML = `${s.main_quests + s.epic_quests} <span class="small text-muted font-normal" style="font-size: 0.72rem;">(${s.epic_quests} Epik)</span>`;
    if (kpiSideVal) kpiSideVal.textContent = s.side_quests;
    if (kpiXpVal) kpiXpVal.textContent = `+${Number(s.total_xp).toLocaleString()}`;
    if (kpiDepoVal) kpiDepoVal.textContent = `${Number(s.total_depo_cut).toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1})} SP`;

    const typeAll = document.getElementById('typeCountAll');
    const typeMain = document.getElementById('typeCountMain');
    const typeEpic = document.getElementById('typeCountEpic');
    const typeSide = document.getElementById('typeCountSide');
    if (typeAll) typeAll.textContent = s.total_quests;
    if (typeMain) typeMain.textContent = s.main_quests;
    if (typeEpic) typeEpic.textContent = s.epic_quests;
    if (typeSide) typeSide.textContent = s.side_quests;
  }

  function getFilteredAndSortedReports() {
    const term = (searchInput?.value || '').trim().toLowerCase();
    const gmVal = (gmFilter?.value || 'all').toLowerCase();
    const levelVal = levelFilter?.value || 'all';
    const sortVal = sortSelect?.value || 'date-asc';

    let list = reportsData.filter(r => {
      // Type Filter
      if (currentTypeFilter === 'main') {
        const isM = r.mq === 'main' || (r.is_main && r.mq !== 'epic');
        if (!isM) return false;
      } else if (currentTypeFilter === 'epic') {
        if (r.mq !== 'epic') return false;
      } else if (currentTypeFilter === 'side') {
        const isS = r.mq === 'none' || (!r.is_main);
        if (!isS) return false;
      }

      // GM Filter
      if (gmVal !== 'all') {
        if ((r.gm || '').toLowerCase() !== gmVal) return false;
      }

      // Level Filter
      if (levelVal !== 'all') {
        const reqLvl = parseInt(levelVal, 10);
        if (levelVal === '5') {
          if ((r.level || 1) < 5) return false;
        } else {
          if ((r.level || 1) !== reqLvl) return false;
        }
      }

      // Search Term
      if (term) {
        let match = (r.title || '').toLowerCase().includes(term) ||
                    (r.gm || '').toLowerCase().includes(term) ||
                    (r.reporter || '').toLowerCase().includes(term) ||
                    (r.location || '').toLowerCase().includes(term);

        if (!match && r.other_players) {
          match = r.other_players.some(p => p.toLowerCase().includes(term));
        }
        if (!match) return false;
      }

      return true;
    });

    // Sort
    function parseDate(dStr) {
      const match = /^(\d{1,2})[/.](\d{1,2})[/.](\d{4})$/.exec(dStr || '');
      if (!match) return null;
      const day = Number(match[1]), month = Number(match[2]), year = Number(match[3]);
      const date = new Date(Date.UTC(year, month - 1, day));
      if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) return null;
      return date.getTime();
    }

    list.sort((a, b) => {
      if (sortVal === 'date-desc' || sortVal === 'date-asc') {
        const dateA = parseDate(a.date), dateB = parseDate(b.date);
        if (dateA === null && dateB !== null) return 1;
        if (dateB === null && dateA !== null) return -1;
        if (dateA === null && dateB === null) return a.id - b.id;
        return sortVal === 'date-asc' ? dateA - dateB || a.id - b.id : dateB - dateA || b.id - a.id;
      } else if (sortVal === 'xp-desc') {
        return (b.total_xp || 0) - (a.total_xp || 0);
      } else if (sortVal === 'avg-desc') {
        return (b.avg_reward || 0) - (a.avg_reward || 0);
      } else if (sortVal === 'title-asc') {
        return (a.title || '').localeCompare(b.title || '');
      }
      return 0;
    });

    return list;
  }

  // ====================================================
  // 8. TABLE PAGINATION & RENDERING ENGINE (50/Sayfa + Sona Git)
  // ====================================================
  const pageSize = 50;
  let currentPage = 1;

  function scrollToTableTop() {
    const tableContainer = document.getElementById('tabViewQuests');
    if (tableContainer) {
      tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  window.goToPage = function(page) {
    const list = getFilteredAndSortedReports();
    const totalPages = Math.max(1, Math.ceil(list.length / pageSize));
    const target = Math.max(1, Math.min(page, totalPages));
    currentPage = target;
    renderTable();
    scrollToTableTop();
  };

  window.goToPrevPage = function() {
    if (currentPage > 1) {
      window.goToPage(currentPage - 1);
    }
  };

  window.goToNextPage = function() {
    const list = getFilteredAndSortedReports();
    const totalPages = Math.max(1, Math.ceil(list.length / pageSize));
    if (currentPage < totalPages) {
      window.goToPage(currentPage + 1);
    }
  };

  window.goToLastPage = function() {
    const list = getFilteredAndSortedReports();
    const totalPages = Math.max(1, Math.ceil(list.length / pageSize));
    window.goToPage(totalPages);
  };

  function renderPagination(totalItems, totalPages, currPage, startIndex, endIndex) {
    const paginationContainer = document.getElementById('reportsPaginationContainer');
    const infoText = document.getElementById('paginationInfoText');
    const paginationList = document.getElementById('reportsPaginationList');
    const btnFirst = document.getElementById('btnPageFirst');
    const btnPrev = document.getElementById('btnPagePrev');
    const btnNext = document.getElementById('btnPageNext');
    const btnLast = document.getElementById('btnPageLast');
    const btnHeaderLast = document.getElementById('btnHeaderGoToLastPage');

    if (btnHeaderLast) {
      btnHeaderLast.disabled = (currPage === totalPages || totalItems === 0);
      btnHeaderLast.title = `En Son Sayfaya Git (Sayfa ${totalPages})`;
    }

    if (!paginationContainer) return;

    if (totalItems === 0) {
      paginationContainer.classList.add('d-none');
      return;
    }
    paginationContainer.classList.remove('d-none');

    if (infoText) {
      infoText.innerHTML = `{{ __("Showing:") }} <span class="text-primary fw-bold">${(startIndex + 1).toLocaleString()}</span> - <span class="text-primary fw-bold">${endIndex.toLocaleString()}</span> / {{ __("Total") }} <span class="fw-bold">${totalItems.toLocaleString()}</span> {{ __("Reports") }} <span class="badge bg-label-primary font-monospace ms-1">{{ __("Page") }} ${currPage} / ${totalPages}</span>`;
    }

    if (btnFirst) {
      btnFirst.disabled = (currPage === 1);
      btnFirst.classList.toggle('disabled', currPage === 1);
    }
    if (btnPrev) {
      btnPrev.disabled = (currPage === 1);
      btnPrev.classList.toggle('disabled', currPage === 1);
    }
    if (btnNext) {
      btnNext.disabled = (currPage === totalPages);
      btnNext.classList.toggle('disabled', currPage === totalPages);
    }
    if (btnLast) {
      btnLast.disabled = (currPage === totalPages);
      btnLast.classList.toggle('disabled', currPage === totalPages);
      btnLast.title = `${@json(__('Last Page'))} (${totalPages})`;
      const textSpan = btnLast.querySelector('span');
      if (textSpan) {
        textSpan.textContent = `${@json(__('Last'))} (${totalPages})`;
      }
    }

    if (paginationList) {
      let pageRange = [];
      if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pageRange.push(i);
      } else {
        if (currPage <= 4) {
          pageRange = [1, 2, 3, 4, 5, '...', totalPages];
        } else if (currPage >= totalPages - 3) {
          pageRange = [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
        } else {
          pageRange = [1, '...', currPage - 1, currPage, currPage + 1, '...', totalPages];
        }
      }

      let html = '';
      pageRange.forEach(p => {
        if (p === '...') {
          html += `<li class="page-item disabled"><span class="page-link border-0 text-muted px-2">...</span></li>`;
        } else {
          const isActive = (p === currPage);
          html += `
            <li class="page-item ${isActive ? 'active' : ''}">
              <button type="button" class="page-link font-monospace ${isActive ? 'bg-primary text-white border-primary shadow-sm' : ''}" onclick="goToPage(${p})">
                ${p}
              </button>
            </li>
          `;
        }
      });
      paginationList.innerHTML = html;
    }
  }

  function renderTable() {
    if (!tableBody) return;
    const list = getFilteredAndSortedReports();

    if (currentFilteredCountEl) {
      currentFilteredCountEl.textContent = list.length;
    }

    const totalItems = list.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = Math.min(startIndex + pageSize, totalItems);

    renderPagination(totalItems, totalPages, currentPage, startIndex, endIndex);

    if (list.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="16" class="text-center py-5">
            <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-file-search fs-1 text-muted"></i>
            </div>
            <h5 class="fw-bold text-heading">{{ __('No quest reports found matching the criteria.') }}</h5>
          </td>
        </tr>
      `;
      if (emptyState) emptyState.classList.remove('d-none');
      return;
    }

    if (emptyState) emptyState.classList.add('d-none');

    const pageItems = list.slice(startIndex, endIndex);

    tableBody.innerHTML = pageItems.map(q => {
      // MQ Badge
      let mqBadge = '';
      if (q.mq === 'epic' || q.tier_type === 'epic') {
        mqBadge = `<span class="badge badge-mq-epic font-monospace px-2 py-1">{{ __('Epic') }}</span>`;
      } else if (q.is_main || q.mq === 'main') {
        mqBadge = `<span class="badge badge-mq-main font-monospace px-2 py-1">{{ __('Main') }}</span>`;
      } else {
        mqBadge = `<span class="badge badge-mq-side font-monospace px-2 py-1">{{ __('Side') }}</span>`;
      }

      // Other players tags
      const otherTags = (q.other_players || []).map(p => `
        <span class="party-pill-tag">
          <span>${escapeHtml(p)}</span>
        </span>
      `).join('');

      const formatXp = value => Number(value ?? 0).toLocaleString(undefined, {maximumFractionDigits: 4});
      const raporDisplay = formatXp(q.rapor_cut ?? q.vault_cut);
      const ekDisplay = formatXp(q.extra_reward);

      const depoCutClass = (parseFloat(q.depo_cut) || 0) > 0 ? 'text-danger fw-bold' : 'text-body-secondary';

      return `
        <tr class="quest-row-item report-row-item"
            data-id="${q.id}"
            data-type="${q.mq || (q.is_main ? 'main' : 'side')}"
            data-gm="${escapeHtml((q.gm || '').toLowerCase())}"
            data-level="${q.level || 1}"
            data-xp="${q.total_xp || 0}"
            data-avg="${q.avg_reward || 0}"
            data-date="${escapeHtml(q.date || '')}">
          
          <td class="text-center text-body-secondary font-monospace" style="font-size: 0.78rem;">
            ${q.id}
          </td>

          <td>
            <div class="d-flex align-items-center">
              <div>
                <span class="fw-bold text-heading quest-title-text d-block" style="cursor: pointer;" onclick="openReportDetail(${q.id})">
                  ${escapeHtml(q.title || '')}
                </span>
                <small class="text-body-secondary font-monospace" style="font-size: 0.75rem;">
                  ${escapeHtml(q.location || 'Siwion')}
                </small>
              </div>
            </div>
          </td>

          <td>
            <span class="font-monospace text-body-secondary fw-semibold">
              ${escapeHtml(q.date || '')}
            </span>
          </td>

          <td class="text-center">${mqBadge}</td>

          <td class="text-center font-monospace fw-bold text-info">
            ${q.level || 1}
          </td>

          <td class="text-end font-monospace fw-bold text-success">
            ${formatXp(q.total_xp)}
          </td>

          <td class="text-end font-monospace text-warning fw-semibold">
            ${formatXp(q.guild_cut)}
          </td>

          <td class="text-end font-monospace ${depoCutClass}">
            ${formatXp(q.depo_cut)}
          </td>

          <td class="text-end font-monospace text-heading">
            ${raporDisplay}
          </td>

          <td class="text-end font-monospace text-info">
            ${ekDisplay}
          </td>

          <td class="text-end">
            <span class="badge badge-avg-xp font-monospace">
              ${formatXp(q.avg_reward)}
            </span>
          </td>

          <td class="text-center font-monospace fw-bold text-heading">
            ${q.player_count || 1}
          </td>

          <td>
            <span class="badge bg-label-warning font-monospace">
              ${escapeHtml(q.gm || 'Shalvaz')}
            </span>
          </td>

          <td>
            <span class="badge bg-label-info font-monospace text-info fw-bold">
              ${escapeHtml(q.reporter || 'Tolrun')}
            </span>
          </td>

          <td>
            <div class="d-flex align-items-center flex-wrap gap-1">
              ${otherTags}
            </div>
          </td>

          <td class="text-end">
            <div class="d-inline-flex align-items-center gap-1">
              <button type="button" class="btn btn-icon btn-sm btn-outline-primary" onclick="openReportDetail(${q.id})" title="{{ __('Report Details') }}" aria-label="{{ __('Report Details') }}">
                <i class="icon-base ti tabler-file-text fs-6"></i>
              </button>
              <button type="button" class="btn btn-icon btn-sm btn-outline-warning" onclick="openEditReportModal(${q.id})" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                <i class="icon-base ti tabler-edit fs-6"></i>
              </button>
              <button type="button" class="btn btn-icon btn-sm btn-outline-success" onclick="distributeReportXp(${q.id})" title="{{ __('Distribute XP to Adventurers') }}" aria-label="{{ __('Distribute XP to Adventurers') }}">
                <i class="icon-base ti tabler-bolt fs-6"></i>
              </button>
              <button type="button" class="btn btn-icon btn-sm btn-outline-danger" onclick="deleteReportItem(${q.id})" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                <i class="icon-base ti tabler-trash fs-6"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // Type Filters Click
  typeButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      typeButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentTypeFilter = this.getAttribute('data-type') || 'all';
      currentPage = 1;
      renderTable();
    });
  });

  // Search Input Listeners
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      if (btnSearchClear) {
        if (this.value.trim().length > 0) {
          btnSearchClear.classList.remove('d-none');
        } else {
          btnSearchClear.classList.add('d-none');
        }
      }
      currentPage = 1;
      renderTable();
    });
  }

  if (btnSearchClear) {
    btnSearchClear.addEventListener('click', function () {
      if (searchInput) {
        searchInput.value = '';
        btnSearchClear.classList.add('d-none');
        currentPage = 1;
        renderTable();
      }
    });
  }

  if (gmFilter) gmFilter.addEventListener('change', function () { currentPage = 1; renderTable(); });
  if (levelFilter) levelFilter.addEventListener('change', function () { currentPage = 1; renderTable(); });
  if (sortSelect) sortSelect.addEventListener('change', function () { currentPage = 1; renderTable(); });

  // Reset Filters
  function resetAllFilters() {
    if (searchInput) searchInput.value = '';
    if (btnSearchClear) btnSearchClear.classList.add('d-none');
    if (gmFilter) gmFilter.value = 'all';
    if (levelFilter) levelFilter.value = 'all';
    if (sortSelect) sortSelect.value = 'date-asc';
    currentTypeFilter = 'all';
    typeButtons.forEach(b => {
      b.classList.toggle('active', b.getAttribute('data-type') === 'all');
    });
    currentPage = 1;
    renderTable();
  }

  if (btnResetFilters) btnResetFilters.addEventListener('click', resetAllFilters);
  if (btnEmptyResetFilters) btnEmptyResetFilters.addEventListener('click', resetAllFilters);

  // Pagination Action Listeners
  const btnHeaderLastEl = document.getElementById('btnHeaderGoToLastPage');
  const btnPageLastEl = document.getElementById('btnPageLast');
  const btnPageFirstEl = document.getElementById('btnPageFirst');
  const btnPagePrevEl = document.getElementById('btnPagePrev');
  const btnPageNextEl = document.getElementById('btnPageNext');

  if (btnHeaderLastEl) {
    btnHeaderLastEl.addEventListener('click', function(e) {
      e.preventDefault();
      window.goToLastPage();
    });
  }
  if (btnPageLastEl) {
    btnPageLastEl.addEventListener('click', function(e) {
      e.preventDefault();
      window.goToLastPage();
    });
  }
  if (btnPageFirstEl) {
    btnPageFirstEl.addEventListener('click', function(e) {
      e.preventDefault();
      window.goToPage(1);
    });
  }
  if (btnPagePrevEl) {
    btnPagePrevEl.addEventListener('click', function(e) {
      e.preventDefault();
      window.goToPrevPage();
    });
  }
  if (btnPageNextEl) {
    btnPageNextEl.addEventListener('click', function(e) {
      e.preventDefault();
      window.goToNextPage();
    });
  }

  // ====================================================
  // 9. TAB SWITCHING (QUESTS vs. GUILD POOL)
  // ====================================================
  const tabNavQuests = document.getElementById('tabNavQuests');
  const tabNavGuildPool = document.getElementById('tabNavGuildPool');
  const tabViewQuests = document.getElementById('tabViewQuests');
  const tabViewGuildPool = document.getElementById('tabViewGuildPool');

  function switchMainTab(target) {
    if (target === 'guild-pool') {
      if (tabNavQuests) tabNavQuests.classList.remove('active');
      if (tabNavGuildPool) tabNavGuildPool.classList.add('active');
      if (tabViewQuests) tabViewQuests.classList.add('d-none');
      if (tabViewGuildPool) tabViewGuildPool.classList.remove('d-none');
      history.replaceState(null, null, '#guild-pool');
    } else {
      if (tabNavGuildPool) tabNavGuildPool.classList.remove('active');
      if (tabNavQuests) tabNavQuests.classList.add('active');
      if (tabViewGuildPool) tabViewGuildPool.classList.add('d-none');
      if (tabViewQuests) tabViewQuests.classList.remove('d-none');
      history.replaceState(null, null, '#quests');
    }
  }

  if (tabNavQuests) {
    tabNavQuests.addEventListener('click', () => switchMainTab('quests'));
  }
  if (tabNavGuildPool) {
    tabNavGuildPool.addEventListener('click', () => switchMainTab('guild-pool'));
  }

  if (window.location.hash === '#guild-pool') {
    switchMainTab('guild-pool');
  }

  // ====================================================
  // 10. GUILD POOL DOM UPDATE ENGINE
  // ====================================================
  function updateGuildPoolDom(data) {
    if (!data) return;
    guildPoolData = data;

    const levelBadge = document.getElementById('poolLevelBadge');
    if (levelBadge) levelBadge.textContent = @json(__('Guild Level :level')).replace(':level', data.guild_xp.level);

    const navBadge = document.getElementById('navTabPoolGrandBadge');
    if (navBadge) navBadge.textContent = `${Number(data.grand_total_xp).toLocaleString(undefined, {maximumFractionDigits: 0})} XP`;

    // 4 KPI Cards
    const wikiRem = document.getElementById('kpiWikiRemainingVal');
    const wikiTotal = document.getElementById('kpiWikiTotalVal');
    const wikiDist = document.getElementById('kpiWikiDistVal');
    if (wikiRem) wikiRem.textContent = `${Number(data.wiki_xp.remaining).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (wikiTotal) wikiTotal.textContent = Number(data.wiki_xp.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (wikiDist) wikiDist.textContent = Number(data.wiki_xp.distributed).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const guildTotal = document.getElementById('kpiGuildXpTotalVal');
    if (guildTotal) guildTotal.textContent = `${Number(data.guild_xp.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    const guildLevel = document.getElementById('kpiGuildXpLevelVal');
    const guildTarget = document.getElementById('kpiGuildTargetVal');
    const guildBase = document.getElementById('kpiGuildBaseVal');
    const guildRemaining = document.getElementById('kpiGuildRemainingVal');
    if (guildLevel) guildLevel.textContent = `Lvl ${data.guild_xp.level}`;
    if (guildTarget) guildTarget.textContent = Number(data.guild_xp.next_level_threshold).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (guildBase) guildBase.textContent = Number(data.guild_xp.base).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (guildRemaining) guildRemaining.textContent = Number(data.guild_xp.remaining).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const expertCount = document.getElementById('kpiExpertCountVal');
    if (expertCount) expertCount.textContent = `${data.ranks.find(rank => rank.rank === 'Uzman')?.current_count ?? 0} {{ __('Experts') }}`;

    const poolPay = document.getElementById('kpiPoolPayVal');
    const poolTotal = document.getElementById('kpiPoolTotalVal');
    if (poolPay) poolPay.textContent = `${Number(data.pool_share_uzman).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (poolTotal) poolTotal.textContent = Number(data.pool_total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const grandTotal = document.getElementById('kpiGrandTotalVal');
    const ranksTotal = document.getElementById('kpiRanksTotalVal');
    const extraFund = document.getElementById('kpiExtraFundVal');
    if (grandTotal) grandTotal.textContent = `${Number(data.grand_total_xp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (ranksTotal) ranksTotal.textContent = Number(data.ranks_total_spent).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (extraFund) extraFund.textContent = `+${Number(data.extra_fund).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    // Table rows
    const tbody = document.getElementById('guildRanksTableBody');
    if (tbody && data.ranks) {
      tbody.innerHTML = data.ranks.map(rk => {
        const badgeMap = {
          'Bailiff': 'badge-rank-bailiff',
          'Uzman': 'badge-rank-uzman',
          'Kıdemli': 'badge-rank-kidemli',
          'Üye': 'badge-rank-uye',
          'Clerk': 'badge-rank-clerk',
          'Aday': 'badge-rank-aday',
        };
        const badgeClass = badgeMap[rk.rank] || 'badge-rank-inaktif';
        return `
          <tr class="quest-row-item">
            <td>
              <span class="badge ${badgeClass} px-2 py-1 font-monospace fs-6">
                ${escapeHtml(rk.rank)}
              </span>
              <details class="small mt-1">
                <summary>{{ __('Formula') }}</summary>
                <span class="text-body-secondary">${escapeHtml(rk.formula_desc || '-')}</span>
              </details>
            </td>
            <td class="text-end font-monospace text-heading fw-semibold">
              ${rk.capacity !== null ? Number(rk.capacity).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}
            </td>
            <td class="text-center font-monospace fw-bold ${(rk.current_count || 0) > 0 ? 'text-warning' : 'text-body-secondary'}">
              ${Number(rk.current_count || 0).toLocaleString()}
            </td>
            <td class="text-end font-monospace text-warning fw-semibold">
              ${rk.capacity_xp !== null ? Number(rk.capacity_xp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}
            </td>
            <td class="text-end font-monospace text-info fw-semibold">
              ${rk.current_xp !== null ? Number(rk.current_xp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}
            </td>
            <td class="text-end font-monospace text-success fw-bold">
              ${rk.net_xp !== null ? Number(rk.net_xp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}
            </td>
            <td class="text-end font-monospace text-heading fw-bolder" style="font-size: 0.95rem;">
              ${Number(rk.spent_xp || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
            </td>
          </tr>
        `;
      }).join('');
    }

    // Footers
    const totalCapEl = document.getElementById('ranksTableTotalCapacity');
    const totalMembersEl = document.getElementById('ranksTableTotalMembers');
    const totalCapXpEl = document.getElementById('ranksTableTotalCapacityXp');
    const totalCurrXpEl = document.getElementById('ranksTableTotalCurrentXp');
    const totalNetXpEl = document.getElementById('ranksTableTotalNetXp');
    const totalSpentEl = document.getElementById('ranksTableTotalSpent');
    const extraFundEl = document.getElementById('ranksTableExtraFund');
    const grandTotalEl = document.getElementById('ranksTableGrandTotal');

    if (totalCapEl) totalCapEl.textContent = Number(data.total_capacity ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (totalMembersEl && data.ranks) {
      const sumMembers = data.ranks.reduce((acc, curr) => acc + (parseInt(curr.current_count, 10) || 0), 0);
      totalMembersEl.textContent = sumMembers;
    }
    if (totalCapXpEl) totalCapXpEl.textContent = Number(data.total_capacity_xp ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (totalCurrXpEl) totalCurrXpEl.textContent = Number(data.total_current_xp ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (totalNetXpEl) totalNetXpEl.textContent = Number(data.total_net_xp ?? 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (totalSpentEl) totalSpentEl.textContent = Number(data.ranks_total_spent).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (extraFundEl) extraFundEl.textContent = `+${Number(data.extra_fund).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    if (grandTotalEl) grandTotalEl.textContent = Number(data.grand_total_xp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Cockpit Elements
    const cockpitGuild = document.getElementById('calcCockpitGuildXp');
    const cockpitWiki = document.getElementById('calcCockpitWikiRem');
    const cockpitUzman = document.getElementById('calcCockpitUzmanPay');
    const cockpitGrand = document.getElementById('calcCockpitGrandTotal');
    if (cockpitGuild) cockpitGuild.textContent = `${Number(data.guild_xp.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (cockpitWiki) cockpitWiki.textContent = `${Number(data.wiki_xp.remaining).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (cockpitUzman) cockpitUzman.textContent = `${Number(data.pool_share_uzman).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (cockpitGrand) cockpitGrand.textContent = `${Number(data.grand_total_xp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;

    // Update Simulator Outputs
    const simPoolInput = document.getElementById('simPoolInput');
    const simUzmanInput = document.getElementById('simUzmanInput');
    const simGuildXpInput = document.getElementById('simGuildXpInput');
    if (simPoolInput) simPoolInput.value = data.pool_total ?? 0;
    if (simUzmanInput) simUzmanInput.value = (data.ranks && data.ranks.find(r => r.rank === 'Uzman')) ? data.ranks.find(r => r.rank === 'Uzman').current_count : 21;
    if (simGuildXpInput) simGuildXpInput.value = data.guild_xp.total ?? 0;
    if (typeof updateSimulation === 'function') updateSimulation();
  }

  // Live Simulator Handlers
  const simPoolInput = document.getElementById('simPoolInput');
  const simUzmanInput = document.getElementById('simUzmanInput');
  const simGuildXpInput = document.getElementById('simGuildXpInput');
  const simUzmanPayOutput = document.getElementById('simUzmanPayOutput');
  const simUzmanCapOutput = document.getElementById('simUzmanCapOutput');

  function updateSimulation() {
    if (!simPoolInput || !simUzmanInput || !simGuildXpInput) return;
    const pool = parseFloat(simPoolInput.value) || 0;
    const uzmanCount = Math.max(parseInt(simUzmanInput.value, 10) || 1, 1);
    const guildXp = parseFloat(simGuildXpInput.value) || 0;

    const pay = pool / uzmanCount;
    const capXp = guildXp * 0.60;

    if (simUzmanPayOutput) simUzmanPayOutput.textContent = `${Number(pay).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (simUzmanCapOutput) simUzmanCapOutput.textContent = `${Number(capXp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
  }

  if (simPoolInput) simPoolInput.addEventListener('input', updateSimulation);
  if (simUzmanInput) simUzmanInput.addEventListener('input', updateSimulation);
  if (simGuildXpInput) simGuildXpInput.addEventListener('input', updateSimulation);

  // ====================================================
  // 11. EDIT GUILD POOL MODAL & LIVE CALCULATOR
  // ====================================================
  const modalEditPoolEl = document.getElementById('modalEditGuildPool');
  const formEditPool = document.getElementById('formEditGuildPool');
  const editPoolWikiTotal = document.getElementById('editPoolWikiTotal');
  const editPoolWikiDist = document.getElementById('editPoolWikiDist');
  const editPoolGuildTotal = document.getElementById('editPoolGuildTotal');
  const editPoolGuildNext = document.getElementById('editPoolGuildNext');
  const editPoolGuildLevel = document.getElementById('editPoolGuildLevel');
  const editPoolTotalVal = document.getElementById('editPoolTotalVal');
  const editPoolExtraFund = document.getElementById('editPoolExtraFund');

  function updateEditPoolLiveCalculator() {
    if (!editPoolWikiTotal || !editPoolWikiDist) return;

    const wikiTotal = parseFloat(editPoolWikiTotal.value) || 0;
    const wikiDist = parseFloat(editPoolWikiDist.value) || 0;
    const wikiRem = wikiTotal - wikiDist;

    const guildTotal = parseFloat(editPoolGuildTotal.value) || 0;
    const poolTotal = parseFloat(editPoolTotalVal.value) || 0;
    const extraFund = parseFloat(editPoolExtraFund.value) || 0;

    const rankRows = document.querySelectorAll('.edit-rank-row');
    let uzmanCount = 21;
    let totalSpent = 0;

    rankRows.forEach(row => {
      const rank = row.getAttribute('data-rank');
      const countInput = row.querySelector('.rank-input-count');
      const spentInput = row.querySelector('.rank-input-spent');
      const count = parseInt(countInput?.value, 10) || 0;
      const spent = parseFloat(spentInput?.value) || 0;

      if (rank === 'Uzman') {
        uzmanCount = Math.max(count, 1);
      }
      totalSpent += spent;
    });

    const uzmanPay = poolTotal / uzmanCount;

    // Update net displays
    rankRows.forEach(row => {
      const rank = row.getAttribute('data-rank');
      const countInput = row.querySelector('.rank-input-count');
      const spentInput = row.querySelector('.rank-input-spent');
      const netDisplay = row.querySelector('.rank-display-net');
      const count = parseInt(countInput?.value, 10) || 0;
      const spent = parseFloat(spentInput?.value) || 0;

      let netXp = 0;
      if (rank === 'Uzman') {
        netXp = uzmanPay;
      } else if (count > 0 && rank !== 'Aday') {
        netXp = spent / count;
      }

      if (netDisplay) {
        if (rank === 'Inaktif / Gezgin') {
          netDisplay.textContent = '-';
        } else {
          netDisplay.textContent = Number(netXp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
      }
    });

    const grandTotal = totalSpent + extraFund;
    const uzmanCap = guildTotal * 0.60;

    // Header badge & overview
    const payBadge = document.getElementById('livePoolPayBadge');
    const wikiRemText = document.getElementById('livePoolWikiRemText');
    const uzmanCapText = document.getElementById('livePoolUzmanCapText');
    const ranksTotalText = document.getElementById('livePoolRanksTotalText');
    const grandTotalText = document.getElementById('livePoolGrandTotalText');
    const equationsText = document.getElementById('livePoolEquationsText');

    if (payBadge) payBadge.textContent = `{{ __("Expert Share:") }} ${Number(uzmanPay).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (wikiRemText) wikiRemText.textContent = `${Number(wikiRem).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (uzmanCapText) uzmanCapText.textContent = `${Number(uzmanCap).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (ranksTotalText) ranksTotalText.textContent = `${Number(totalSpent).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;
    if (grandTotalText) grandTotalText.textContent = `${Number(grandTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} XP`;

    if (equationsText) {
      equationsText.textContent = `{{ __("Expert Share") }} = {{ __("Pool") }} [${Number(poolTotal).toLocaleString(undefined, {maximumFractionDigits: 2})}] / {{ __("Expert Count") }} [${uzmanCount}] = ${Number(uzmanPay).toLocaleString(undefined, {maximumFractionDigits: 2})} XP | Grand Total = {{ __("Ranks") }} [${Number(totalSpent).toLocaleString(undefined, {maximumFractionDigits: 2})}] + {{ __("Extra") }} [${Number(extraFund).toLocaleString(undefined, {maximumFractionDigits: 2})}] = ${Number(grandTotal).toLocaleString(undefined, {maximumFractionDigits: 2})} XP`;
    }
  }

  [editPoolWikiTotal, editPoolWikiDist, editPoolGuildTotal, editPoolTotalVal, editPoolExtraFund].forEach(el => {
    if (el) {
      el.addEventListener('input', updateEditPoolLiveCalculator);
      el.addEventListener('change', updateEditPoolLiveCalculator);
    }
  });

  document.querySelectorAll('.rank-input-count, .rank-input-spent').forEach(el => {
    el.addEventListener('input', updateEditPoolLiveCalculator);
    el.addEventListener('change', updateEditPoolLiveCalculator);
  });

  // Open Edit Modal
  const btnOpenEditPool = document.getElementById('btnOpenEditGuildPoolModal');
  if (btnOpenEditPool && modalEditPoolEl) {
    btnOpenEditPool.addEventListener('click', function () {
      if (editPoolWikiTotal) editPoolWikiTotal.value = guildPoolData.wiki_xp.total;
      if (editPoolWikiDist) editPoolWikiDist.value = guildPoolData.wiki_xp.distributed;
      if (editPoolGuildTotal) editPoolGuildTotal.value = guildPoolData.guild_xp.total;
      if (editPoolGuildNext) editPoolGuildNext.value = guildPoolData.guild_xp.next_level_threshold;
      if (editPoolGuildLevel) editPoolGuildLevel.value = guildPoolData.guild_xp.level;
      if (editPoolTotalVal) editPoolTotalVal.value = guildPoolData.pool_total;
      if (editPoolExtraFund) editPoolExtraFund.value = guildPoolData.extra_fund;

      document.querySelectorAll('.edit-rank-row').forEach(row => {
        const rankName = row.getAttribute('data-rank');
        const rk = (guildPoolData.ranks || []).find(r => r.rank === rankName);
        if (rk) {
          const countInput = row.querySelector('.rank-input-count');
          const spentInput = row.querySelector('.rank-input-spent');
          if (countInput) countInput.value = rk.current_count ?? 0;
          if (spentInput) spentInput.value = rk.spent_xp ?? 0;
        }
      });

      updateEditPoolLiveCalculator();
      const bsModal = new bootstrap.Modal(modalEditPoolEl);
      bsModal.show();
    });
  }

  // Submit Edit Modal
  if (formEditPool) {
    formEditPool.addEventListener('submit', function (e) {
      e.preventDefault();

      const ranks = [];
      document.querySelectorAll('.edit-rank-row').forEach(row => {
        const rankName = row.getAttribute('data-rank');
        const origRk = (guildPoolData.ranks || []).find(r => r.rank === rankName) || {};
        const count = parseInt(row.querySelector('.rank-input-count')?.value, 10) || 0;
        const spent = parseFloat(row.querySelector('.rank-input-spent')?.value) || 0;

        ranks.push({
          rank: rankName,
          capacity: origRk.capacity ?? null,
          current_count: count,
          capacity_xp: origRk.capacity_xp ?? null,
          current_xp: origRk.current_xp ?? null,
          net_xp: origRk.net_xp ?? null,
          spent_xp: spent,
        });
      });

      const payload = {
        wiki_xp: {
          total: parseFloat(editPoolWikiTotal.value) || 0,
          distributed: parseFloat(editPoolWikiDist.value) || 0,
        },
        guild_xp: {
          base: guildPoolData.guild_xp.base ?? 145000,
          total: parseFloat(editPoolGuildTotal.value) || 0,
          next_level_threshold: editPoolGuildNext.value === '' ? guildPoolData.guild_xp.next_level_threshold : Number(editPoolGuildNext.value),
          remaining: guildPoolData.guild_xp.remaining ?? 529,
          level: parseInt(editPoolGuildLevel.value, 10) || 12,
        },
        extra_fund: parseFloat(editPoolExtraFund.value) || 0,
        pool_total: parseFloat(editPoolTotalVal.value) || 0,
        ranks: ranks,
        _token: document.querySelector('input[name="_token"]')?.value
      };

      const submitBtn = document.getElementById('btnSubmitEditGuildPool');
      if (submitBtn) submitBtn.disabled = true;

      fetch('{{ route("valiant-shade.pages-quest-reports.guild-pool.update") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': payload._token
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (!data.success) {
          alert(data.message || '{{ __("Update failed.") }}');
          return;
        }

        updateGuildPoolDom(data.guildPoolData);

        const bsModal = bootstrap.Modal.getInstance(modalEditPoolEl);
        if (bsModal) bsModal.hide();

        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Pool Updated!") }}',
            text: '{{ __("Guild XP Pool and Rank Distribution data was successfully saved.") }}',
            timer: 2000,
            showConfirmButton: false
          });
        }
      })
      .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        alert('{{ __("Server communication error occurred.") }}');
      });
    });
  }

  // Reset Guild Pool Defaults
  const btnResetPool = document.getElementById('btnResetGuildPoolDefaults');
  if (btnResetPool) {
    btnResetPool.addEventListener('click', function () {
      if (window.Swal) {
        Swal.fire({
          title: '{{ __("Reset Pool Data?") }}',
          text: '{{ __("Guild XP Pool and Ranks table will be reset to Google Sheet original values.") }}',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#82868b',
          confirmButtonText: '{{ __("Yes, Reset!") }}',
          cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
          if (result.isConfirmed) {
            executeResetPool();
          }
        });
      } else {
        if (confirm('{{ __("Reset pool data to Google Sheet original values?") }}')) {
          executeResetPool();
        }
      }

      function executeResetPool() {
        fetch('{{ route("valiant-shade.pages-quest-reports.guild-pool.reset") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
          }
        })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            alert('{{ __("Reset failed.") }}');
            return;
          }
          updateGuildPoolDom(data.guildPoolData);

          if (window.Swal) {
            Swal.fire({
              icon: 'success',
              title: '{{ __("Pool Reset!") }}',
              text: '{{ __("Guild XP Pool and Ranks table was restored to Master Sheet values.") }}',
              timer: 2000,
              showConfirmButton: false
            });
          }
        });
      }
    });
  }

  // Initialize
  renderTable();
});
</script>
@endsection
