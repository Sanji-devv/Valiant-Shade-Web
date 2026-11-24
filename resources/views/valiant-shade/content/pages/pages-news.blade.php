@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('News, Whispers & Expedition Bulletin - Valiant Shade'))

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* Fantasy News Styling */
  .news-hero-card {
    border-radius: 14px;
    position: relative;
    overflow: hidden;
    min-height: 400px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
    background-size: cover;
    background-position: center;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
  }
  .news-hero-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.65);
    border-color: rgba(255, 255, 255, 0.25);
  }
  .news-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.35) 0%, rgba(15, 23, 42, 0.85) 60%, rgba(15, 23, 42, 0.98) 100%),
                radial-gradient(circle at 85% 20%, rgba(220, 38, 38, 0.28), transparent 55%);
    pointer-events: none;
  }
  .news-hero-content {
    position: relative;
    z-index: 2;
    padding: 2.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
  }

  /* Quick Category Metric Cards (Interactive) */
  .news-stat-counter-card {
    cursor: pointer;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(22, 32, 49, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
  }
  .news-stat-counter-card:hover {
    transform: translateY(-4px);
    border-color: rgba(var(--bs-primary-rgb), 0.45);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
  }
  .news-stat-counter-card.is-active-filter {
    border-color: var(--bs-primary) !important;
    background: rgba(var(--bs-primary-rgb), 0.12) !important;
    box-shadow: 0 0 15px rgba(var(--bs-primary-rgb), 0.3);
  }

  /* News Grid Cards */
  .news-feed-card {
    border-radius: 12px;
    background: rgba(22, 32, 49, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  .news-feed-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.45);
    border-color: rgba(var(--bs-primary-rgb), 0.45);
  }
  .news-thumb-box {
    height: 210px;
    background-size: cover;
    background-position: center;
    position: relative;
    overflow: hidden;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .news-feed-card:hover .news-thumb-box {
    transform: scale(1.03);
  }
  .news-thumb-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(22, 32, 49, 0.98) 0%, rgba(22, 32, 49, 0.4) 60%, transparent 100%);
    pointer-events: none;
  }
  .news-tag-badge {
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #ffffff;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 0.22rem 0.6rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
  }
  .news-stat-pill {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 0.4rem 0.65rem;
    transition: border-color 0.2s ease;
  }
  .news-feed-card:hover .news-stat-pill {
    border-color: rgba(255, 255, 255, 0.15);
  }
  .category-filter-btn {
    transition: all 0.2s ease;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.45rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
  }
  .category-filter-btn.active {
    background-color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(var(--bs-primary-rgb), 0.4);
  }
  .gazette-filter-btn.active {
    background-color: rgba(var(--bs-primary-rgb), 0.22) !important;
    border-color: var(--bs-primary) !important;
    color: #f1f5f9 !important;
    box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.25);
  }
  .news-drag-handle {
    cursor: grab;
    opacity: 0.55;
    touch-action: none;
    transition: opacity 0.2s ease;
  }
  .news-drag-handle:hover {
    opacity: 0.95;
  }
  .news-drag-handle:active {
    cursor: grabbing;
  }
  .news-item-col--dragging {
    opacity: 0.45;
  }
  .news-item-col--drag-over {
    outline: 2px dashed rgba(var(--bs-primary-rgb), 0.65);
    outline-offset: 4px;
  }

  /* Detail Reader Modal */
  #newsDetailModal .modal-dialog {
    max-width: 880px;
  }
  #newsDetailModal .modal-content {
    background-color: #111a28;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
  }
  .modal-hero-cover {
    height: 300px;
    background-size: cover;
    background-position: center;
    position: relative;
  }
  .modal-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #111a28 0%, rgba(17, 26, 40, 0.55) 60%, rgba(17, 26, 40, 0.25) 100%);
    pointer-events: none;
  }
  .fantasy-quote-box {
    border-left: 4px solid #f59e0b;
    background: rgba(245, 158, 11, 0.08);
    padding: 1.1rem 1.35rem;
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: #e2e8f0;
    margin: 1.5rem 0;
    position: relative;
  }
  .fantasy-quote-box.danger-quote {
    border-left-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
  }
  .fantasy-quote-box.dark-quote {
    border-left-color: #a855f7;
    background: rgba(168, 85, 247, 0.08);
  }
  .article-body-p {
    font-size: 1.02rem;
    line-height: 1.8;
    color: #cbd5e1;
    margin-bottom: 1.25rem;
  }
  .article-body-p:first-of-type::first-letter {
    font-size: 2.7rem;
    float: left;
    line-height: 1;
    margin-right: 0.6rem;
    font-weight: 700;
    color: #f59e0b;
    font-family: serif;
  }
  .guild-seal-stamp {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    border: 1px dashed rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.04);
    font-size: 0.78rem;
    color: #94a3b8;
  }
  .vs-type-picker { display: flex; flex-wrap: wrap; gap: 0.5rem; }
  .vs-type-picker__btn {
    flex: 1 1 calc(16.666% - 0.5rem);
    min-width: 4.5rem;
    max-width: 5.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 0.55rem 0.35rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(15, 23, 42, 0.55);
    color: #94a3b8;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
  }
  .vs-type-picker__btn:hover { border-color: rgba(168, 85, 247, 0.45); color: #e2e8f0; }
  .vs-type-picker__btn.is-selected {
    border-color: rgba(168, 85, 247, 0.85);
    background: rgba(168, 85, 247, 0.18);
    color: #f5f3ff;
    box-shadow: 0 0 0 1px rgba(168, 85, 247, 0.35);
  }
  .vs-type-picker__icon { display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; }
  .vs-type-picker--admin .vs-type-picker__icon i { font-size: 1.35rem; }
  .vs-type-picker__label { font-size: 0.62rem; line-height: 1.15; text-align: center; font-weight: 600; }

  /* News desk: consistent alignment, responsive controls, lighter rendering. */
  .news-page-header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(460px, 0.78fr);
    align-items: center;
    gap: clamp(1rem, 2vw, 2rem);
  }
  .news-page-intro,
  .news-page-actions { min-width: 0; }
  .news-page-eyebrow {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .news-page-eyebrow .badge,
  .news-tag-badge,
  .news-hero-badges > .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.38rem;
    line-height: 1.2;
  }
  .news-page-title {
    font-size: clamp(1.45rem, 2vw, 2rem);
    line-height: 1.2;
    letter-spacing: -0.025em;
  }
  .news-page-title > i { flex: 0 0 auto; line-height: 1; }
  .news-page-description { max-width: 58rem; line-height: 1.55; }
  .news-page-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.55rem;
  }
  .news-page-actions > :first-child { grid-column: span 2; }
  .news-page-actions .btn {
    min-width: 0;
    min-height: 46px;
    padding: 0.58rem 0.7rem;
    border-radius: 12px;
    text-align: center;
    line-height: 1.2;
    font-size: 0.86rem;
    font-weight: 600;
    white-space: normal;
    box-shadow: none !important;
  }
  .container-xxl.flex-grow-1 .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.42rem;
    min-height: 42px;
    line-height: 1.2;
  }
  .container-xxl.flex-grow-1 .btn.btn-sm { min-height: 38px; }
  .container-xxl.flex-grow-1 .btn > i { margin-inline: 0 !important; }
  .container-xxl.flex-grow-1 .btn > i,
  .news-page-eyebrow i,
  .news-tag-badge i,
  .news-hero-badges i,
  .news-stat-counter-card i,
  .news-card-meta i,
  .news-card-actions i {
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    line-height: 1;
    vertical-align: middle;
  }
  .news-hero-card {
    min-height: 380px;
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.32);
    transition: box-shadow 0.18s ease, border-color 0.18s ease;
  }
  .news-hero-card:hover {
    transform: none;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.38);
  }
  .news-hero-content { padding: clamp(1.15rem, 2.5vw, 2.35rem); }
  .news-hero-badges { row-gap: 0.55rem !important; }
  .news-stat-counter-card,
  .news-feed-card {
    -webkit-backdrop-filter: none !important;
    backdrop-filter: none !important;
    background: rgba(26, 32, 50, 0.96);
    border-color: rgba(255, 255, 255, 0.1);
  }
  .news-stat-counter-card {
    min-height: 92px;
    border-radius: 14px;
    transition: border-color 0.16s ease, background-color 0.16s ease;
  }
  .news-stat-counter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
  }
  .news-stat-counter-card .card-body { min-height: 90px; }
  .news-stat-counter-card .avatar {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    padding: 0 !important;
    flex: 0 0 48px;
  }
  .news-stat-counter-card .avatar i { font-size: 1.35rem; }
  .news-stat-counter-card h5 { font-size: 1.25rem; }
  .news-stat-counter-card small { display: block; line-height: 1.35; }
  .news-filter-toolbar {
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 16px;
    background: rgba(30, 35, 54, 0.96);
  }
  .news-filter-toolbar > .card-body { padding: 1rem; }
  .news-filter-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(270px, 0.42fr);
    align-items: center;
    gap: 1rem;
  }
  .news-filter-categories {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
    min-width: 0;
  }
  .category-filter-btn {
    min-width: 0;
    min-height: 42px;
    padding: 0.52rem 0.7rem;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    text-align: center;
    line-height: 1.2;
    white-space: normal;
    transition: color 0.14s ease, background-color 0.14s ease, border-color 0.14s ease;
  }
  .news-filter-search {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
  }
  .news-filter-search .input-group {
    min-width: 0;
    min-height: 44px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(17, 22, 38, 0.62);
  }
  .news-filter-search .input-group-text,
  .news-filter-search .form-control,
  .news-filter-search .btn {
    min-height: 42px;
    border: 0;
    box-shadow: none;
  }
  .news-filter-search .form-control { min-width: 0; background: transparent; }
  .news-filter-search .input-group-text { padding-inline: 0.8rem; }
  .news-filter-search #clearSearchBtn { width: 42px; padding: 0; }
  #gazetteSlotFilterGroup {
    align-items: center;
    row-gap: 0.55rem;
  }
  #gazetteSlotFilterGroup > .category-filter-btn { min-width: 126px; }
  .news-feed-card {
    width: 100%;
    min-height: 500px;
    border-radius: 16px;
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.16);
    transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
  }
  .news-feed-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25);
  }
  .news-feed-card:hover .news-thumb-box { transform: none; }
  .news-thumb-box { height: 205px; background: #151a29; }
  .news-thumb-badges {
    right: auto;
    display: flex;
    align-content: flex-start;
    flex-wrap: wrap;
    gap: 0.4rem !important;
  }
  .news-thumb-icon-badge {
    width: 36px;
    height: 36px;
    min-width: 36px;
    min-height: 36px;
    padding: 0 !important;
    display: inline-grid !important;
    place-items: center;
    border-radius: 50% !important;
  }
  .news-thumb-icon-badge > i { margin: 0 !important; font-size: 1rem; }
  .news-thumb-location { display: none !important; }
  .news-thumb-read-time { width: max-content; max-width: none; }
  .news-thumb-read-time .badge { display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem; white-space: nowrap; }
  .news-thumb-image {
    position: absolute;
    inset: 0;
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.2s ease;
  }
  .news-feed-card:hover .news-thumb-image { transform: scale(1.025); }
  .news-tag-badge {
    min-height: 28px;
    padding: 0.3rem 0.62rem;
    border-radius: 999px;
    -webkit-backdrop-filter: none;
    backdrop-filter: none;
  }
  .news-feed-card .news-thumb-box .badge { min-height: 28px; display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 999px; }
  .news-feed-card .card-body { gap: 1rem; padding: 1.15rem !important; }
  .news-card-meta { gap: 0.75rem; min-height: 24px; }
  .news-card-meta > span { min-width: 0; gap: 0.35rem; }
  .news-card-meta > span:last-child { justify-content: flex-end; text-align: right; }
  .news-card-title {
    display: -webkit-box;
    min-height: 2.8rem;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-height: 1.35;
    font-size: 1.08rem;
  }
  .news-card-description {
    display: -webkit-box;
    min-height: 4.45rem;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    line-height: 1.5;
  }
  .news-stat-pill { min-height: 58px; padding: 0.5rem 0.65rem; }
  .news-card-footer { align-items: center; gap: 0.75rem; }
  .guild-seal-stamp { min-height: 36px; align-items: center; justify-content: center; gap: 0.42rem; white-space: nowrap; }
  .news-card-actions { min-width: 0; gap: 0.45rem !important; }
  .news-card-actions .btn { min-height: 40px; border-radius: 10px; padding: 0.45rem 0.65rem; }
  .news-card-actions .news-card-icon-btn { width: 40px; min-width: 40px; padding: 0; }
  .news-card-actions .news-card-read-btn { flex: 1 1 auto; min-width: 96px; white-space: nowrap; }
  .news-card-actions .news-card-read-btn > i { font-size: 1rem; }
  .news-card-actions .news-card-icon-btn > i { width: 1.1em; height: 1.1em; }
  .news-item-col { display: flex; }
  .news-item-col--dragging { opacity: 0.55; }
  .news-item-col--drag-over { outline-offset: 3px; border-radius: 14px; }
  .guild-seal-stamp i { font-size: 1rem; }
  .vs-type-picker__btn { display: flex; align-items: center; justify-content: center; text-align: center; }
  .vs-type-picker__icon { display: grid; place-items: center; flex: 0 0 2rem; }
  .vs-type-picker__label { width: 100%; }
  .container-xxl.flex-grow-1 .btn:focus-visible,
  .news-stat-counter-card:focus-visible {
    outline: 2px solid rgba(var(--bs-primary-rgb), 0.9);
    outline-offset: 3px;
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.18);
  }
  @media (max-width: 1399.98px) {
    .news-page-header { grid-template-columns: minmax(0, 1fr); gap: 1.15rem; }
    .news-page-actions { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .news-filter-row { grid-template-columns: minmax(0, 1fr) minmax(250px, 0.45fr); }
  }
  @media (max-width: 991.98px) {
    .news-filter-row { grid-template-columns: minmax(0, 1fr); }
    .news-filter-search { max-width: none; }
    .news-filter-categories { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .news-card-footer { align-items: stretch; flex-direction: column; }
    .guild-seal-stamp { align-self: flex-start; }
    .news-card-actions { width: 100%; margin-left: 0 !important; }
  }
  @media (max-width: 767.98px) {
    .container-xxl.flex-grow-1.container-p-y { padding-top: 1.1rem; }
    .news-page-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .news-page-actions > :first-child { grid-column: 1 / -1; }
    .news-page-actions .btn { min-height: 48px; font-size: 0.82rem; }
    .news-hero-card { min-height: 350px; }
    .news-hero-content { padding: 1.2rem; }
    .news-stat-counter-card { min-height: 84px; }
    .news-stat-counter-card .card-body { min-height: 82px; gap: 0.7rem !important; padding: 0.8rem !important; }
    .news-stat-counter-card .avatar { width: 42px; height: 42px; flex-basis: 42px; }
    .news-filter-toolbar > .card-body { padding: 0.85rem; }
    .news-filter-categories { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .category-filter-btn { min-height: 46px; padding-inline: 0.45rem; font-size: 0.78rem; }
    #gazetteSlotFilterGroup > .category-filter-btn { flex: 1 1 calc(50% - 0.5rem); min-width: 0; }
    #gazetteSlotFilterGroup > .text-body-secondary { flex: 1 0 100%; margin-left: 0 !important; }
    .news-thumb-box { height: 190px; }
    .news-thumb-badges { gap: 0.35rem !important; padding: 0.65rem !important; }
    .news-thumb-icon-badge { width: 34px; height: 34px; min-width: 34px; min-height: 34px; }
    .news-thumb-read-time { padding: 0.65rem !important; }
    .news-feed-card { min-height: 0; }
  }
  @media (max-width: 479.98px) {
    .news-page-eyebrow .badge { font-size: 0.68rem; }
    .news-page-actions { grid-template-columns: minmax(0, 1fr); }
    .news-page-actions > :first-child { grid-column: auto; }
    .news-filter-categories { grid-template-columns: minmax(0, 1fr); }
    .category-filter-btn { justify-content: center; }
    .news-card-meta { align-items: flex-start !important; flex-direction: column; gap: 0.25rem; }
    .news-card-meta > span:last-child { justify-content: flex-start; text-align: left; }
    .news-card-actions .news-card-read-btn { min-width: 0; }
  }
  @media (prefers-reduced-motion: reduce) {
    .news-hero-card,
    .news-stat-counter-card,
    .news-feed-card,
    .news-thumb-image,
    .category-filter-btn { transition-duration: 0.01ms !important; }
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

  <!-- Page Header & Action Bar -->
  <div class="news-page-header mb-4">
    <div class="news-page-intro">
      <div class="news-page-eyebrow mb-2">
        <span class="badge bg-label-primary px-2 py-1"><i class="icon-base ti tabler-file-text me-1"></i> {{ __('Valiant Shade Gazette') }}</span>
        <span class="badge bg-label-info px-2 py-1"><i class="icon-base ti tabler-speakerphone me-1"></i> Lonca Haberleri &amp; Duyuru Yönetimi</span>
        <span class="badge bg-label-danger px-2 py-1"><i class="icon-base ti tabler-flame me-1"></i> {{ __('Live Bulletin') }}</span>
      </div>
      <h3 class="news-page-title fw-bold mb-2 d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-news fs-2 text-warning"></i>
        <span>{{ __('News, Whispers & Expedition Bulletin') }}</span>
      </h3>
      <p class="news-page-description text-body-secondary mb-0">
        {{ __('Tavern whispers, eerie rumors, memorial notices for our fallen heroes, dungeon expedition reports, and High Court decrees.') }}
      </p>
    </div>
    <div class="news-page-actions">
      <a href="{{ \App\Support\PublicSiteUrls::gazette('/son-sayi') }}" class="btn btn-warning">
        <i class="icon-base ti tabler-news"></i>
        <span>{{ __('Read Latest Newspaper Issue') }}</span>
      </a>
      @if($canManageNews ?? false)
      <button type="button" class="btn btn-success" onclick="openEditNewsModal(null)">
        <i class="icon-base ti tabler-plus"></i>
        <span>{{ __('Add News') }}</span>
      </button>
      @endif
      <a href="{{ route('valiant-shade.dashboard-analytics') }}" class="btn btn-label-secondary">
        <i class="icon-base ti tabler-home"></i>
        <span>{{ __('Main Dashboard') }}</span>
      </a>
      <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#whisperTipModal">
        <i class="icon-base ti tabler-message-2-share"></i>
        <span>{{ __('Send Tavern Whisper') }}</span>
      </button>
      <button type="button" class="btn btn-primary" onclick="window.scrollTo({ top: document.getElementById('newsFeedGrid').offsetTop - 80, behavior: 'smooth' })">
        <i class="icon-base ti tabler-book-2"></i>
        <span>{{ __('Explore News') }}</span>
      </button>
    </div>
  </div>

  @if(($canManageNews ?? false) && !($usesDatabaseNews ?? false))
  <div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 shadow-sm">
    <div>
      <strong>{{ __('Demo bulletin content') }}</strong>
      <span class="d-block small">{{ __('Articles are shown from built-in samples. Load them into the database to enable edit and delete on each card.') }}</span>
    </div>
    <form method="post" action="{{ route('valiant-shade.pages-news.reset-defaults') }}" class="flex-shrink-0" onsubmit="return confirm(@json(__('Load default bulletins into the database? Existing news rows will be replaced.')));">
      @csrf
      <button type="submit" class="btn btn-warning btn-sm">
        <i class="icon-base ti tabler-database-import me-1"></i> {{ __('Import to database') }}
      </button>
    </form>
  </div>
  @endif

  @if(isset($pendingNews) && $pendingNews->isNotEmpty())
  <div class="card border border-warning border-opacity-50 mb-4 shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0 text-warning d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-hourglass"></i>
          <span>{{ __('Pending Rumors & Submissions') }} ({{ $pendingNews->count() }})</span>
        </h5>
        <small class="text-body-secondary">{{ __('Review public submissions before publishing to the gazette.') }}</small>
      </div>
      @php
        $gazettePublicUrl = \App\Support\PublicSiteUrls::gazette();
      @endphp
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ $gazettePublicUrl }}?flash=1" class="btn btn-sm btn-danger" target="_blank" rel="noopener" title="{{ __('Public hot news submission form') }}">
          <i class="icon-base ti tabler-flame me-1"></i> {{ __('Submit Hot News') }}
        </a>
        <a href="{{ $gazettePublicUrl }}" class="btn btn-sm btn-label-primary" target="_blank" rel="noopener">
          <i class="icon-base ti tabler-world me-1"></i> {{ __('Open Public Newspaper') }}
        </a>
      </div>
    </div>
    <div class="list-group list-group-flush">
      @foreach($pendingNews as $pending)
      <div class="list-group-item bg-transparent">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span class="badge bg-label-warning">{{ $pending->news_type ? (config('news.types.'.$pending->news_type.'.label') ?? $pending->category) : $pending->category }}</span>
              @php
                $submissionBadge = \App\ValiantShade\Support\NewsStats::submissionBadge($pending->stats);
                $publicationBadge = \App\ValiantShade\Support\NewsStats::publicationBadge($pending);
              @endphp
              @if($publicationBadge)
              <span class="badge bg-danger">{{ $publicationBadge }}</span>
              @endif
              @if($submissionBadge)
              <span class="badge bg-label-info">{{ $submissionBadge }}</span>
              @elseif($pending->tag && str_contains($pending->tag, 'Gönderisi'))
              <span class="badge bg-label-secondary">{{ $pending->tag }}</span>
              @endif
              <span class="text-body-secondary small">{{ $pending->date_text }}</span>
            </div>
            <h6 class="mb-1 text-white">{{ $pending->title }}</h6>
            <p class="mb-0 text-body-secondary small">{{ \Illuminate\Support\Str::limit($pending->description, 180) }}</p>
          </div>
          <div class="d-flex gap-2 flex-shrink-0">
            @if($canApproveNews ?? false)
            <form method="post" action="{{ route('valiant-shade.pages-news.approve', $pending->id) }}">
              @csrf
              <button type="submit" class="btn btn-sm btn-success">
                <i class="icon-base ti tabler-check me-1"></i> {{ __('Publish') }}
              </button>
            </form>
            @endif
            <form method="post" action="{{ route('valiant-shade.pages-news.reject', $pending->id) }}">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="icon-base ti tabler-x me-1"></i> {{ __('Reject') }}
              </button>
            </form>
            @if(($canDeleteNews ?? false) || ($canManageNews ?? false))
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="openEditNewsModal({{ $pending->id }})">
              <i class="icon-base ti tabler-edit me-1"></i> {{ __('Edit') }}
            </button>
            <button type="button" class="btn btn-sm btn-danger js-delete-news-btn" data-news-id="{{ $pending->id }}">
              <i class="icon-base ti tabler-trash me-1"></i> {{ __('Delete') }}
            </button>
            @endif
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  <!-- Featured Headlines / Breaking News Banner -->
  @if($featuredNews)
  <div class="card border-0 mb-5 overflow-hidden shadow-lg">
    <div class="news-hero-card" style="background-image: url('{{ $featuredNews->image_asset }}');">
      <div class="news-hero-overlay"></div>
      <div class="news-hero-content">
        <div class="news-hero-badges d-flex align-items-center gap-2 mb-3 flex-wrap">
          <span class="badge bg-{{ $featuredNews->badge_color ?? 'danger' }} text-white px-3 py-2 fs-6 fw-bold">
            <i class="{{ str_replace('tabler-dagger', 'tabler-swords', $featuredNews->category_icon ?: 'icon-base ti tabler-skull') }} me-1"></i> {{ $featuredNews->category }}
          </span>
          @if($featuredNews->tag)
          <span class="news-tag-badge">
            <i class="{{ $featuredNews->tag_icon ?: 'icon-base ti tabler-candle' }} me-1 text-{{ $featuredNews->badge_color ?? 'danger' }}"></i>
            {{ $featuredNews->tag }}
          </span>
          @endif
          <span class="badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-25 px-2 py-1">
            <i class="icon-base ti tabler-flame me-1"></i> {{ __('HEADLINE OF THE DAY') }}
          </span>
          <span class="text-white-50 ms-auto d-none d-md-inline-block small">
            <i class="icon-base ti tabler-clock me-1"></i> {{ $featuredNews->date_text }} &bull; {{ $featuredNews->read_time }}
          </span>
        </div>

        <h2 class="text-white fw-bold mb-2 fs-2" style="text-shadow: 0 3px 12px rgba(0,0,0,0.85); line-height: 1.3;">
          {{ $featuredNews->title }}
        </h2>
        <p class="text-white-50 fs-6 mb-4 d-none d-sm-block" style="max-width: 85%; line-height: 1.6;">
          {{ $featuredNews->description }}
        </p>

        <!-- Stats on Hero -->
        @php
          $featuredStatPills = \App\ValiantShade\Support\NewsStats::pills($featuredNews->stats);
        @endphp
        @if(!empty($featuredStatPills))
        <div class="row g-2 mb-4 d-none d-md-flex" style="max-width: 720px;">
          @foreach($featuredStatPills as $st)
          <div class="col-sm-3 col-6">
            <div class="p-2 rounded" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(6px);">
              <div class="{{ $st['color'] ?? 'text-warning' }} fw-bold fs-6">{{ $st['value'] }}</div>
              <div class="text-white-50" style="font-size: 0.75rem;">{{ $st['label'] }}</div>
            </div>
          </div>
          @endforeach
        </div>
        @endif

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top border-white border-opacity-10">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar avatar-sm bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-feather text-warning"></i>
            </div>
            <div>
              <span class="text-white fw-semibold small">{{ $featuredNews->author }}</span>
              <span class="text-white-50 d-none d-sm-inline small"> &bull; {{ $featuredNews->author_role }}</span>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-{{ $featuredNews->button_color ?? 'danger' }} px-4 py-2 fw-medium d-inline-flex align-items-center gap-2 shadow" data-bs-toggle="modal" data-bs-target="#newsDetailModal" onclick="openNewsDetail({{ $featuredNews->id }})">
              <i class="{{ str_replace('tabler-shield-alert', 'tabler-alert-triangle', $featuredNews->button_icon ?: 'icon-base ti tabler-book-2') }}"></i>
              <span>{{ __('Read Full Story') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Quick Category Counter Badges (Interactive Filter Cards) -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
      <div class="card news-stat-counter-card h-100 js-category-stat-card" data-filter-cat="casualties" title="{{ __('Click to filter casualty notices') }}">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-danger rounded p-2 flex-shrink-0">
            <i class="icon-base ti tabler-skull fs-3"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-baseline justify-content-between">
              <h5 class="mb-0 fw-bold text-danger">{{ $statsSummary['deaths'] ?? 2 }}</h5>
              <i class="icon-base ti tabler-arrow-narrow-right text-danger opacity-50 small"></i>
            </div>
            <small class="text-body-secondary">{{ __('Casualty & Memorial Notice') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card news-stat-counter-card h-100 js-category-stat-card" data-filter-cat="rumors" title="{{ __('Click to filter rumors and whispers') }}">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-warning rounded p-2 flex-shrink-0">
            <i class="icon-base ti tabler-flame fs-3"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-baseline justify-content-between">
              <h5 class="mb-0 fw-bold text-warning">{{ $statsSummary['rumors'] ?? 2 }}</h5>
              <i class="icon-base ti tabler-arrow-narrow-right text-warning opacity-50 small"></i>
            </div>
            <small class="text-body-secondary">{{ __('Tavern & City Gossip') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card news-stat-counter-card h-100 js-category-stat-card" data-filter-cat="expedition" title="{{ __('Click to filter expedition victories') }}">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-success rounded p-2 flex-shrink-0">
            <i class="icon-base ti tabler-trophy fs-3"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-baseline justify-content-between">
              <h5 class="mb-0 fw-bold text-success">{{ $statsSummary['victories'] ?? 1 }}</h5>
              <i class="icon-base ti tabler-arrow-narrow-right text-success opacity-50 small"></i>
            </div>
            <small class="text-body-secondary">{{ __('Expedition Victory & Loot') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card news-stat-counter-card h-100 js-category-stat-card" data-filter-cat="council" title="{{ __('Click to filter council decrees') }}">
        <div class="card-body d-flex align-items-center gap-3 py-3">
          <div class="avatar avatar-md bg-label-primary rounded p-2 flex-shrink-0">
            <i class="icon-base ti tabler-gavel fs-3"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-baseline justify-content-between">
              <h5 class="mb-0 fw-bold text-primary">{{ $statsSummary['decrees'] ?? 1 }}</h5>
              <i class="icon-base ti tabler-arrow-narrow-right text-primary opacity-50 small"></i>
            </div>
            <small class="text-body-secondary">{{ __('Court Decree & Bounty') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card news-filter-toolbar border-0 shadow-sm mb-4" id="newsFeedGrid">
    <div class="card-body py-3">
      <div class="news-filter-row">
        <!-- Categories Tabs -->
        <div class="news-filter-categories" id="categoryFilterGroup">
            <button type="button" class="btn btn-sm category-filter-btn active" data-bs-toggle="tab" data-cat="all">
              <i class="icon-base ti tabler-layout-grid me-1"></i> {{ __('All Bulletins') }} ({{ $allNews->count() }})
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-danger" data-bs-toggle="tab" data-cat="casualties">
              <i class="icon-base ti tabler-skull me-1"></i> {{ __('Fallen & Casualties') }}
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-warning" data-bs-toggle="tab" data-cat="rumors">
              <i class="icon-base ti tabler-flame me-1"></i> {{ __('Rumors & Whispers') }}
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-dark" data-bs-toggle="tab" data-cat="assassination">
              <i class="icon-base ti tabler-swords me-1"></i> {{ __('Assassination & Treason') }}
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-success" data-bs-toggle="tab" data-cat="expedition">
              <i class="icon-base ti tabler-trophy me-1"></i> {{ __('Expedition Reports') }}
            </button>
            <button type="button" class="btn btn-sm category-filter-btn btn-outline-primary" data-bs-toggle="tab" data-cat="council">
              <i class="icon-base ti tabler-gavel me-1"></i> {{ __('Guild Council') }}
            </button>
          </div>
        <!-- Live Search Input & Result Pill -->
        <div class="news-filter-search">
            <div class="input-group">
              <span class="input-group-text bg-transparent border-end-0">
                <i class="icon-base ti tabler-search text-body-secondary"></i>
              </span>
              <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="{{ __('Search news, rumors, or heroes...') }}" value="{{ $searchQuery }}">
              <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="{{ __('Clear') }}">
                <i class="icon-base ti tabler-x"></i>
              </button>
            </div>
            <span id="newsResultsCount" class="badge bg-label-secondary small flex-shrink-0" style="display: none;"></span>
        </div>
      </div>
      @if($usesDatabaseNews ?? false)
      <div class="row mt-3 pt-3 border-top border-secondary border-opacity-25">
        <div class="col-12">
          <div class="d-flex align-items-center gap-2 flex-wrap" id="gazetteSlotFilterGroup">
            <span class="text-body-secondary small text-uppercase fw-semibold">{{ __('Gazette slot') }}</span>
            @php $gCounts = $gazetteSlotCounts ?? ['all' => 0, 'lead' => 0, 'corner' => 0, 'article' => 0, 'flash' => 0]; @endphp
            <button type="button" class="btn btn-sm category-filter-btn gazette-filter-btn active" data-gazette-slot="all">
              {{ __('All Bulletins') }} ({{ (int) ($gCounts['all'] ?? 0) }})
            </button>
            <button type="button" class="btn btn-sm category-filter-btn gazette-filter-btn btn-outline-warning" data-gazette-slot="lead">
              {{ __('Headline') }} ({{ (int) ($gCounts['lead'] ?? 0) }})
            </button>
            <button type="button" class="btn btn-sm category-filter-btn gazette-filter-btn btn-outline-info" data-gazette-slot="corner">
              {{ __('Corner article') }} ({{ (int) ($gCounts['corner'] ?? 0) }})
            </button>
            <button type="button" class="btn btn-sm category-filter-btn gazette-filter-btn btn-outline-secondary" data-gazette-slot="article">
              {{ __('News article') }} ({{ (int) ($gCounts['article'] ?? 0) }})
            </button>
            <button type="button" class="btn btn-sm category-filter-btn gazette-filter-btn btn-outline-danger" data-gazette-slot="flash">
              {{ __('Hot news') }} ({{ (int) ($gCounts['flash'] ?? 0) }})
            </button>
            @if($canUpdateNews ?? false)
            <span class="text-body-secondary small ms-auto"><i class="icon-base ti tabler-grip-vertical me-1"></i>{{ __('Drag cards to set gazette order') }}</span>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>

  <!-- News Cards Grid -->
  <div class="row g-4" id="newsCardsContainer" @if($canUpdateNews ?? false) data-reorder-url="{{ route('valiant-shade.pages-news.reorder') }}" @endif>
    @forelse($news as $item)
    @php
      $catType = match((int)$item->id) {
        1, 6 => 'casualties',
        2 => 'council',
        3, 5 => 'expedition',
        4 => 'rumors',
        7 => 'assassination',
        default => 'general'
      };
      $gazetteSlot = \App\ValiantShade\Support\NewsStats::gazetteSlotKind(is_array($item->stats ?? null) ? $item->stats : null);
      $readerReactions = \App\ValiantShade\Support\NewsEngagement::reactionMetrics(is_array($item->stats ?? null) ? $item->stats : null);
      $readerReactionTotal = array_sum($readerReactions);
      $gazetteBadge = \App\ValiantShade\Support\NewsStats::submissionBadge(is_array($item->stats ?? null) ? $item->stats : null);
    @endphp
    <div class="col-lg-4 col-md-6 news-item-col"
         data-id="{{ $item->id }}"
         data-gazette-slot="{{ $gazetteSlot }}"
         @if(($canUpdateNews ?? false) && ($item instanceof \App\Models\News)) draggable="true" @endif
         data-cat-type="{{ $catType }}"
         data-category="{{ mb_strtolower($item->category) }}"
         data-title="{{ mb_strtolower($item->title) }}"
         data-desc="{{ mb_strtolower($item->description) }}"
         data-location="{{ mb_strtolower($item->location_badge ?? '') }}"
         data-author="{{ mb_strtolower($item->author ?? '') }}">

      <div class="card news-feed-card h-100 position-relative shadow-sm">
        @if(($canUpdateNews ?? false) && ($item instanceof \App\Models\News))
        <div class="position-absolute top-0 end-0 p-2 news-drag-handle" style="z-index: 4;" title="{{ __('Drag to reorder') }}" aria-hidden="true">
          <i class="icon-base ti tabler-grip-vertical text-white"></i>
        </div>
        @endif
        <!-- Thumbnail with Badges -->
        <div class="news-thumb-box">
          <img class="news-thumb-image" src="{{ $item->image_asset }}" alt="" loading="lazy" decoding="async" aria-hidden="true">
          <div class="news-thumb-overlay"></div>

          <!-- Top Category & Tag Badges -->
          <div class="news-thumb-badges position-absolute top-0 start-0 p-3" style="z-index: 2;">
            <span class="badge news-thumb-icon-badge bg-{{ $item->badge_color ?? 'primary' }} text-white fw-bold shadow-sm" aria-label="{{ $item->category }}">
              <i class="{{ str_replace('tabler-dagger', 'tabler-swords', $item->category_icon ?: 'icon-base ti tabler-shield') }}"></i>
            </span>
            @if($item->tag)
            <span class="news-tag-badge news-thumb-icon-badge shadow-sm" aria-label="{{ $item->tag }}">
              <i class="{{ $item->tag_icon ?: 'icon-base ti tabler-sparkles' }} text-{{ $item->badge_color ?? 'warning' }}"></i>
            </span>
            @endif
            @if($gazetteBadge)
            <span class="news-tag-badge news-thumb-icon-badge border-warning border-opacity-50 shadow-sm" aria-label="{{ $gazetteBadge }}">
              <i class="icon-base ti tabler-news text-warning"></i>
            </span>
            @endif
          </div>

          <!-- Top Right Read Time -->
          <div class="news-thumb-read-time position-absolute top-0 end-0 p-3" style="z-index: 2;">
            <span class="badge bg-dark bg-opacity-75 text-white-50 border border-secondary border-opacity-25" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-book me-1"></i> {{ $item->read_time }}
            </span>
          </div>

          <!-- Bottom Location -->
          @if(!empty($item->location_badge))
          <div class="news-thumb-location position-absolute bottom-0 start-0 p-3" style="z-index: 2;" aria-hidden="true">
            <span class="text-white-50 small d-flex align-items-center">
              <i class="icon-base ti tabler-map-pin me-1 text-{{ $item->badge_color ?? 'warning' }}"></i>
              {{ $item->location_badge }}
            </span>
          </div>
          @endif
        </div>

        <!-- Card Body -->
        <div class="card-body d-flex flex-column justify-content-between p-4">
          <div>
            <!-- Date & Author Row -->
            <div class="news-card-meta d-flex align-items-center justify-content-between mb-2 pb-1 text-body-secondary small">
              <span class="d-flex align-items-center">
                <i class="icon-base ti tabler-calendar-event me-1"></i> {{ $item->date_text }}
              </span>
              <span class="d-flex align-items-center">
                <i class="icon-base ti tabler-feather me-1 text-warning"></i> {{ $item->author }}
              </span>
            </div>

            <!-- Title -->
            <h5 class="news-card-title fw-bold mb-2 text-white">
              {{ $item->title }}
            </h5>

            <!-- Description -->
            <p class="news-card-description text-body-secondary small mb-3">
              {{ $item->description }}
            </p>

            <!-- Micro Stats Preview -->
            @php
              $cardStatPills = \App\ValiantShade\Support\NewsStats::pills($item->stats ?? null);
            @endphp
            @if(!empty($cardStatPills) || $readerReactionTotal > 0)
            <div class="row g-2 mb-3">
              @foreach(array_slice($cardStatPills, 0, 2) as $st)
              <div class="col-6">
                <div class="news-stat-pill">
                  <div class="{{ $st['color'] ?? 'text-warning' }} fw-bold" style="font-size: 0.85rem;">{{ $st['value'] }}</div>
                  <div class="text-body-secondary" style="font-size: 0.72rem;">{{ $st['label'] }}</div>
                </div>
              </div>
              @endforeach
              @if($readerReactionTotal > 0)
              <div class="col-6">
                <div class="news-stat-pill">
                  <div class="text-info fw-bold" style="font-size: 0.85rem;">{{ $readerReactionTotal }}</div>
                  <div class="text-body-secondary" style="font-size: 0.72rem;">{{ __('Reader reactions') }}</div>
                </div>
              </div>
              @endif
            </div>
            @endif
          </div>

          <!-- Card Footer Action -->
          <div class="news-card-footer pt-3 border-top border-white border-opacity-10 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="guild-seal-stamp">
              <i class="icon-base ti tabler-shield-check text-{{ $item->badge_color ?? 'primary' }}"></i>
              <span>{{ __('Verified') }}</span>
            </span>

            <div class="news-card-actions d-flex align-items-center gap-2 ms-auto">
              @if(($canUpdateNews ?? false) && ($item instanceof \App\Models\News))
              <button type="button" class="btn btn-outline-warning btn-sm news-card-icon-btn" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" onclick="openEditNewsModal({{ $item->id }})">
                <i class="icon-base ti tabler-edit"></i>
              </button>
              @endif
              @if(($canDeleteNews ?? false) && ($item instanceof \App\Models\News))
              <button type="button" class="btn btn-outline-danger btn-sm news-card-icon-btn js-delete-news-btn" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}" data-news-id="{{ $item->id }}">
                <i class="icon-base ti tabler-trash"></i>
              </button>
              @endif
              <button type="button" class="btn btn-outline-{{ $item->button_color ?? 'primary' }} btn-sm news-card-read-btn" data-bs-toggle="modal" data-bs-target="#newsDetailModal" onclick="openNewsDetail({{ $item->id }})">
                <i class="{{ str_replace('tabler-shield-alert', 'tabler-alert-triangle', $item->button_icon ?: 'icon-base ti tabler-book-2') }}"></i>
                <span>{{ __('Read More') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
      <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3">
        <i class="icon-base ti tabler-file-search fs-1"></i>
      </div>
      <h5>{{ __('No Matching News Found') }}</h5>
      <p class="text-body-secondary">{{ __('No news or whisper records match your search criteria.') }}</p>
    </div>
    @endforelse
  </div>

  <!-- Empty state placeholder for JS live filter -->
  <div id="noJsResultsPlaceholder" class="col-12 text-center py-5 d-none">
    <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3">
      <i class="icon-base ti tabler-search-off fs-1"></i>
    </div>
    <h5 class="text-white">{{ __('No Matching News Found') }}</h5>
    <p class="text-body-secondary">{{ __('No bulletins match the current filter or search criteria.') }}</p>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="resetAllNewsFilters()">
      <i class="icon-base ti tabler-refresh me-1"></i> {{ __('Reset Filters') }}
    </button>
  </div>

</div>

<!-- Full Story Reader Modal -->
<div class="modal fade" id="newsDetailModal" tabindex="-1" aria-labelledby="newsDetailModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg">
      <!-- Modal Hero Cover -->
      <div class="modal-hero-cover" id="modalHeroCover">
        <div class="modal-hero-overlay"></div>
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>

        <div class="position-absolute bottom-0 start-0 p-4 z-2 w-100">
          <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            <span class="badge" id="modalCategoryBadge">{{ __('Category') }}</span>
            <span class="news-tag-badge" id="modalTagBadge">{{ __('Tag') }}</span>
            <span class="text-white-50 ms-auto small" id="modalDateText">{{ __('Date') }}</span>
          </div>
          <h3 class="text-white fw-bold mb-0" id="modalTitle" style="text-shadow: 0 2px 8px rgba(0,0,0,0.8); line-height: 1.3;">
            {{ __('News Title') }}
          </h3>
        </div>
      </div>

      <!-- Modal Body (Narrative & Breakdown) -->
      <div class="modal-body p-4 p-md-5">
        <!-- Byline & Meta Info -->
        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom border-white border-opacity-10 flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-md bg-label-warning rounded-circle d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-feather text-warning fs-4"></i>
            </div>
            <div>
              <h6 class="mb-0 text-white fw-bold" id="modalAuthor">{{ __('Author') }}</h6>
              <small class="text-body-secondary" id="modalAuthorRole">{{ __('Role / Title') }}</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3 text-body-secondary small">
            <div><i class="icon-base ti tabler-map-pin me-1 text-warning"></i> <span id="modalLocation">{{ __('Location') }}</span></div>
            <div><i class="icon-base ti tabler-book me-1 text-info"></i> <span id="modalReadTime">{{ __('Read Time') }}</span></div>
          </div>
        </div>

        <!-- Full Story Narrative -->
        <div id="modalStoryContent">
          <!-- Dynamic paragraphs injected by JS -->
        </div>

        <!-- Testimonial / Quote Box -->
        <div class="fantasy-quote-box" id="modalQuoteBox" style="display: none;">
          <i class="icon-base ti tabler-quote fs-3 text-warning opacity-50 position-absolute top-0 end-0 m-2"></i>
          <p class="mb-0 fw-medium" id="modalQuoteText"></p>
        </div>

        <!-- Mission / Incident Stats Summary -->
        <div class="mt-4 pt-3 border-top border-white border-opacity-10">
          <h6 class="text-white fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-chart-pie text-warning"></i>
            <span>{{ __('Operation & Incident Summary') }}</span>
          </h6>
          <div class="row g-3" id="modalStatsGrid">
            <!-- Dynamic stats injected by JS -->
          </div>
        </div>
      </div>

      <!-- Modal Footer with RPG Actions -->
      <div class="modal-footer border-top border-white border-opacity-10 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap" id="modalDynamicActions">
          <!-- Action buttons injected based on category -->
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto">
          @if($canUpdateNews ?? false)
          <button type="button" class="btn btn-outline-warning d-none d-inline-flex align-items-center gap-1" id="modalEditNewsBtn">
            <i class="icon-base ti tabler-edit"></i>
            <span>{{ __('Edit') }}</span>
          </button>
          @endif
          @if($canDeleteNews ?? false)
          <button type="button" class="btn btn-outline-danger d-none d-inline-flex align-items-center gap-1" id="modalDeleteNewsBtn">
            <i class="icon-base ti tabler-trash"></i>
            <span>{{ __('Delete') }}</span>
          </button>
          @endif
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">{{ __('Close') }}</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create / Edit News Modal -->
@if($canManageNews ?? false)
<div class="modal fade" id="newsEditModal" tabindex="-1" aria-labelledby="newsEditModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="post" id="newsEditForm" enctype="multipart/form-data" class="modal-content" style="background: #111a28; border: 1px solid rgba(255,255,255,0.15);">
      @csrf
      <div class="modal-header border-bottom border-white border-opacity-10">
        <h5 class="modal-title fw-bold text-white" id="newsEditModalTitle">{{ __('Edit News') }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label text-white small fw-medium" for="newsEditTitle">{{ __('Title') }}</label>
            <input type="text" class="form-control" id="newsEditTitle" name="title" required maxlength="255">
          </div>
          <div class="col-md-6">
            <label class="form-label text-white small fw-medium" for="newsEditCategory">{{ __('Category') }}</label>
            <input type="text" class="form-control" id="newsEditCategory" name="category" required maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label text-white small fw-medium" for="newsEditDateText">{{ __('Date label') }}</label>
            <input type="text" class="form-control" id="newsEditDateText" name="date_text" maxlength="100" placeholder="Leos 1, 652 — Vonyas">
          </div>
          <div class="col-12">
            <label class="form-label text-white small fw-medium" for="newsEditDescription">{{ __('Summary / description') }}</label>
            <textarea class="form-control" id="newsEditDescription" name="description" rows="4" required></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label text-white small fw-medium" for="newsEditLocation">{{ __('Location') }}</label>
            <input type="text" class="form-control" id="newsEditLocation" name="location_badge" maxlength="150">
          </div>
          <div class="col-md-6">
            <label class="form-label text-white small fw-medium" for="newsEditImagePreset">{{ __('Cover image') }}</label>
            <select class="form-select" id="newsEditImagePreset" name="image_preset">
              @php
                $gazetteImagePresets = [];
                foreach (\App\ValiantShade\Support\GazetteImageLibrary::catalog() as $entry) {
                    $path = (string) ($entry['path'] ?? '');
                    if ($path === '' || isset($gazetteImagePresets[$path])) {
                        continue;
                    }
                    $gazetteImagePresets[$path] = (string) ($entry['label'] ?? basename($path, '.jpg'));
                }
              @endphp
              <option value="__gazette_auto__" selected>{{ __('Automatic from title and summary') }}</option>
              @foreach ($gazetteImagePresets as $preset => $presetLabel)
              <option value="{{ $preset }}">{{ $presetLabel }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label text-white small fw-medium" for="newsEditCustomImage">{{ __('Custom cover upload') }}</label>
            <input type="file" class="form-control" id="newsEditCustomImage" name="custom_image" accept="image/png,image/jpeg,image/webp">
            <div class="form-text text-body-secondary">{{ __('Optional. Uploading a file overrides the selected preset.') }}</div>
            <div class="mt-2 d-none" id="newsEditImagePreviewWrap">
              <img id="newsEditImagePreview" alt="{{ __('Cover preview') }}" class="img-fluid rounded border border-white border-opacity-10" style="max-height: 180px;">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-white small fw-medium" for="newsEditOrder">{{ __('Sort order') }}</label>
            <input type="number" class="form-control" id="newsEditOrder" name="order" min="0" step="1">
          </div>
          <div class="col-md-8 d-flex align-items-end">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" value="1" id="newsEditIsActive" name="is_active" checked>
              <label class="form-check-label text-body-secondary" for="newsEditIsActive">{{ __('Published / visible on gazette') }}</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top border-white border-opacity-10">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary" id="newsEditSubmitBtn">{{ __('Save') }}</button>
      </div>
    </form>
  </div>
</div>
@endif

<!-- Send Whisper Tip Modal (Roleplay Feature) -->
<div class="modal fade" id="whisperTipModal" tabindex="-1" aria-labelledby="whisperTipModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #111a28; border: 1px solid rgba(255,255,255,0.15);">
      <div class="modal-header border-bottom border-white border-opacity-10">
        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="whisperTipModalTitle">
          <i class="icon-base ti tabler-message-2-question text-warning"></i>
          <span>{{ __('Submit Tavern Whisper & Report') }}</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-body-secondary small mb-3">
          {{ __('Secretly report suspicious incidents, dungeon losses, or tavern rumors to the Valiant Shade Intelligence Division.') }}
        </p>
        <div class="mb-3">
          <label class="form-label text-white small fw-medium">{{ __('Whisper Subject or Title') }}</label>
          <input type="text" class="form-control" id="whisperSubject" placeholder="{{ __('e.g. Suspicious elf meeting at Red Inn...') }}">
        </div>
        <div class="mb-3">
          <label class="form-label text-white small fw-medium d-block mb-2">{{ __('Category') }}</label>
          @include('web.partials.news-type-picker', [
              'variant' => 'admin',
              'groupId' => 'whisperTypePicker',
              'inputId' => 'whisperNewsType',
              'inputName' => 'news_type',
              'ariaLabel' => __('Category'),
          ])
        </div>
        <div class="mb-3">
          <label class="form-label text-white small fw-medium">{{ __('Whisper Details & Eyewitness Account') }}</label>
          <textarea class="form-control" id="whisperMessage" rows="4" placeholder="{{ __('Describe what you saw or heard...') }}"></textarea>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="anonymousWhisperCheck" checked>
          <label class="form-check-label text-body-secondary small" for="anonymousWhisperCheck">
            {{ __('Keep my identity anonymous (Protected by Shadow Seal)') }}
          </label>
        </div>
      </div>
      <div class="modal-footer border-top border-white border-opacity-10">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-warning d-inline-flex align-items-center gap-1" id="btnSubmitWhisper">
          <i class="icon-base ti tabler-send"></i>
          <span>{{ __('Send Whisper') }}</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Complete Static News Store (Mirroring Controller)
  const masterNewsStore = @json($allNewsJson ?? $allNews);
  const canManageNews = @json($canManageNews ?? false);
  const canUpdateNews = @json($canUpdateNews ?? false);
  const canDeleteNews = @json($canDeleteNews ?? false);
  const newsDeleteUrlBase = @json(url('/valiant-shade/pages/news'));
  const newsStoreUrl = @json(route('valiant-shade.pages-news.store'));
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const currentImageLabel = @json(__('Current image'));

  function isManageableNewsItem(item) {
    return Boolean(item && item.is_database);
  }

  window.openEditNewsModal = function (newsId) {
    if (!canUpdateNews) return;
    const form = document.getElementById('newsEditForm');
    const modalEl = document.getElementById('newsEditModal');
    if (!form || !modalEl) return;

    const titleEl = document.getElementById('newsEditModalTitle');
    const submitBtn = document.getElementById('newsEditSubmitBtn');
    const isCreate = newsId === null || newsId === undefined;

    if (isCreate) {
      form.action = newsStoreUrl;
      form.querySelector('input[name="_method"]')?.remove();
      if (titleEl) titleEl.textContent = @json(__('Add News'));
      if (submitBtn) submitBtn.textContent = @json(__('Create'));
      form.reset();
      document.getElementById('newsEditIsActive').checked = true;

      const preset = document.getElementById('newsEditImagePreset');
      if (preset) {
        preset.querySelectorAll('option[data-current="1"]').forEach(o => o.remove());
        preset.value = preset.options[0]?.value;
      }
      const previewWrap = document.getElementById('newsEditImagePreviewWrap');
      const previewImg = document.getElementById('newsEditImagePreview');
      if (previewWrap) previewWrap.classList.add('d-none');
      if (previewImg) previewImg.removeAttribute('src');
      const fileInput = document.getElementById('newsEditCustomImage');
      if (fileInput) fileInput.value = '';
    } else {
      const item = masterNewsStore.find(n => Number(n.id) === Number(newsId));
      if (!item || !isManageableNewsItem(item)) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'info',
            title: @json(__('Not editable')),
            text: @json(__('Import demo bulletins to the database first, or create a new article.')),
            confirmButtonText: @json(__('OK')),
            customClass: { confirmButton: 'btn btn-primary' },
            buttonsStyling: false,
          });
        }
        return;
      }
      form.action = `${newsDeleteUrlBase}/${item.id}`;
      form.querySelector('input[name="_method"]')?.remove();
      if (titleEl) titleEl.textContent = @json(__('Edit News'));
      if (submitBtn) submitBtn.textContent = @json(__('Save'));
      document.getElementById('newsEditTitle').value = item.title || '';
      document.getElementById('newsEditCategory').value = item.category || '';
      document.getElementById('newsEditDateText').value = item.date_text || '';
      document.getElementById('newsEditDescription').value = item.description || '';
      document.getElementById('newsEditLocation').value = item.location_badge || '';
      document.getElementById('newsEditOrder').value = item.order ?? '';
      document.getElementById('newsEditIsActive').checked = item.is_active !== false && item.is_active !== 0;
      const preset = document.getElementById('newsEditImagePreset');
      const previewWrap = document.getElementById('newsEditImagePreviewWrap');
      const previewImg = document.getElementById('newsEditImagePreview');
      const fileInput = document.getElementById('newsEditCustomImage');

      if (preset) {
        preset.querySelectorAll('option[data-current="1"]').forEach(o => o.remove());
      }
      if (fileInput) fileInput.value = '';
      if (previewWrap) previewWrap.classList.add('d-none');
      if (previewImg) previewImg.removeAttribute('src');

      if (preset && item.image_url) {
        const match = Array.from(preset.options).find(o => o.value === item.image_url);
        if (match) {
          preset.value = item.image_url;
        } else {
          const opt = document.createElement('option');
          opt.value = item.image_url;
          opt.dataset.current = '1';
          const filename = String(item.image_url).split('/').pop() || item.image_url;
          opt.textContent = `${currentImageLabel}: ${filename}`;
          preset.insertBefore(opt, preset.firstChild);
          preset.value = item.image_url;
        }
      }

      if (previewImg && item.image_asset) {
        previewImg.src = item.image_asset;
        if (previewWrap) previewWrap.classList.remove('d-none');
      }
    }

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  };

  document.addEventListener('change', (event) => {
    const input = event.target;
    if (!input || input.id !== 'newsEditCustomImage') {
      return;
    }

    const previewWrap = document.getElementById('newsEditImagePreviewWrap');
    const previewImg = document.getElementById('newsEditImagePreview');
    if (!previewWrap || !previewImg) {
      return;
    }

    const file = input.files && input.files[0];
    if (!file) {
      previewImg.removeAttribute('src');
      previewWrap.classList.add('d-none');
      return;
    }

    const url = URL.createObjectURL(file);
    previewImg.src = url;
    previewWrap.classList.remove('d-none');
    previewImg.onload = () => URL.revokeObjectURL(url);
  });

  function submitNewsDelete(newsId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${newsDeleteUrlBase}/${newsId}`;
    form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
  }

  function newsTitleForId(newsId) {
    const item = masterNewsStore.find(n => Number(n.id) === Number(newsId));
    return item?.title ?? '';
  }

  function confirmDeleteNews(newsId, title) {
    const headline = title ? String(title) : @json(__('this article'));
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: @json(__('Delete news?')),
        text: headline,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: @json(__('Yes, delete')),
        cancelButtonText: @json(__('Cancel')),
        customClass: {
          confirmButton: 'btn btn-danger me-2',
          cancelButton: 'btn btn-label-secondary',
        },
        buttonsStyling: false,
      }).then((result) => {
        if (result.isConfirmed) {
          submitNewsDelete(newsId);
        }
      });
      return;
    }
    if (window.confirm(@json(__('Delete this news item?')) + '\n' + headline)) {
      submitNewsDelete(newsId);
    }
  }

  document.addEventListener('click', (event) => {
    const deleteBtn = event.target.closest('.js-delete-news-btn');
    if (!deleteBtn) {
      return;
    }
    event.preventDefault();
    const newsId = parseInt(deleteBtn.dataset.newsId, 10);
    if (!Number.isFinite(newsId)) {
      return;
    }
    confirmDeleteNews(newsId, newsTitleForId(newsId));
  });

  // Open Full Story Reader Modal
  function openNewsDetail(newsId) {
    const item = masterNewsStore.find(n => n.id === newsId);
    if (!item) return;

    // Cover & Badges
    document.getElementById('modalHeroCover').style.backgroundImage = `url('${item.image_asset}')`;

    const catBadge = document.getElementById('modalCategoryBadge');
    catBadge.className = `badge bg-${item.badge_color || 'primary'} text-white fw-bold px-3 py-2`;
    const categoryIcon = (item.category_icon || 'icon-base ti tabler-shield').replace('tabler-dagger', 'tabler-swords');
    catBadge.innerHTML = `<i class="${categoryIcon} me-1"></i> ${item.category}`;

    const tagBadge = document.getElementById('modalTagBadge');
    if (item.tag) {
      tagBadge.style.display = 'inline-flex';
      tagBadge.innerHTML = `<i class="${item.tag_icon || 'icon-base ti tabler-sparkles'} me-1 text-${item.badge_color || 'warning'}"></i> ${item.tag}`;
    } else {
      tagBadge.style.display = 'none';
    }

    document.getElementById('modalDateText').innerHTML = `<i class="icon-base ti tabler-clock me-1"></i> ${item.published_date || item.date_text}`;
    document.getElementById('modalTitle').textContent = item.title;
    document.getElementById('modalAuthor').textContent = item.author;
    document.getElementById('modalAuthorRole').textContent = item.author_role || '{{ __("Guild Official") }}';
    document.getElementById('modalLocation').textContent = item.location_badge || '{{ __("Valiant Shade Citadel") }}';
    document.getElementById('modalReadTime').textContent = item.read_time || '{{ __("3 min read") }}';

    // Narrative Body (Format paragraphs)
    const storyBox = document.getElementById('modalStoryContent');
    const paragraphs = (item.full_story || item.description).split('\n\n');
    storyBox.innerHTML = paragraphs.map(p => `<p class="article-body-p">${p.trim()}</p>`).join('');

    // Quote Box
    const quoteBox = document.getElementById('modalQuoteBox');
    const quoteText = document.getElementById('modalQuoteText');
    if (item.testimony) {
      quoteBox.style.display = 'block';
      quoteBox.className = 'fantasy-quote-box ' + (item.badge_color === 'danger' ? 'danger-quote' : (item.badge_color === 'dark' ? 'dark-quote' : ''));
      quoteText.textContent = item.testimony;
    } else {
      quoteBox.style.display = 'none';
    }

    // Stats Grid
    const statsGrid = document.getElementById('modalStatsGrid');
    const statPills = Array.isArray(item.stats?.pills)
      ? item.stats.pills
      : Array.isArray(item.stats)
        ? item.stats.filter(st => st && typeof st === 'object' && 'value' in st && 'label' in st)
        : [];
    if (typeof item.views_count === 'number' && item.views_count > 0) {
      statPills.unshift({ value: item.views_count, label: @json(__('Views')), color: 'text-secondary' });
    }
    const reactionLabels = {
      victory: @json(__('Victory')),
      mourning: @json(__('Mourning')),
      whisper: @json(__('Tavern whisper')),
      bounty: @json(__('Decree'))
    };
    const reactionMetrics = item.reaction_metrics || {};
    Object.keys(reactionLabels).forEach((key) => {
      const count = Number(reactionMetrics[key] || 0);
      if (count > 0) {
        statPills.push({ value: count, label: reactionLabels[key] + ' (' + @json(__('readers')) + ')', color: 'text-info' });
      }
    });
    if (statPills.length > 0) {
      statsGrid.innerHTML = statPills.map(st => `
        <div class="col-sm-3 col-6">
          <div class="p-3 rounded" style="background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(255,255,255,0.08);">
            <div class="${st.color || 'text-warning'} fw-bold fs-5">${st.value}</div>
            <div class="text-body-secondary small">${st.label}</div>
          </div>
        </div>
      `).join('');
    } else {
      statsGrid.innerHTML = '<div class="col-12 text-body-secondary small">{{ __("No special statistics available.") }}</div>';
    }

    const dynamicActions = document.getElementById('modalDynamicActions');
    const itemId = parseInt(item.id, 10);
    if (canUpdateNews && isManageableNewsItem(item)) {
      dynamicActions.innerHTML = `
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1" onclick="sendEditorialReaction(${itemId}, 'victory', '${String(item.title).replace(/'/g, "\\'")}')">
            <i class="icon-base ti tabler-trophy"></i><span>{{ __('Celebrate Victory') }}</span>
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" onclick="sendEditorialReaction(${itemId}, 'mourning', '${String(item.title).replace(/'/g, "\\'")}')">
            <i class="icon-base ti tabler-candle"></i><span>{{ __('Mourn') }}</span>
          </button>
          <button type="button" class="btn btn-outline-warning btn-sm d-inline-flex align-items-center gap-1" onclick="sendEditorialReaction(${itemId}, 'whisper', '${String(item.title).replace(/'/g, "\\'")}')">
            <i class="icon-base ti tabler-beer"></i><span>{{ __('Tavern whisper') }}</span>
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" onclick="sendEditorialReaction(${itemId}, 'bounty', '${String(item.title).replace(/'/g, "\\'")}')">
            <i class="icon-base ti tabler-file-text"></i><span>{{ __('Decree record') }}</span>
          </button>
        </div>`;
    } else {
      dynamicActions.innerHTML = '';
    }

    const modalEditBtn = document.getElementById('modalEditNewsBtn');
    if (modalEditBtn) {
      if (canUpdateNews && isManageableNewsItem(item)) {
        modalEditBtn.classList.remove('d-none');
        modalEditBtn.onclick = () => {
          bootstrap.Modal.getInstance(document.getElementById('newsDetailModal'))?.hide();
          openEditNewsModal(item.id);
        };
      } else {
        modalEditBtn.classList.add('d-none');
        modalEditBtn.onclick = null;
      }
    }

    const modalDeleteBtn = document.getElementById('modalDeleteNewsBtn');
    if (modalDeleteBtn) {
      if (canDeleteNews && isManageableNewsItem(item)) {
        modalDeleteBtn.classList.remove('d-none');
        modalDeleteBtn.onclick = () => confirmDeleteNews(item.id, item.title);
      } else {
        modalDeleteBtn.classList.add('d-none');
        modalDeleteBtn.onclick = null;
      }
    }

    const modal = new bootstrap.Modal(document.getElementById('newsDetailModal'));
    modal.show();
  }

  async function sendEditorialReaction(newsId, reaction, title) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
      const response = await fetch(`${newsDeleteUrlBase}/${newsId}/editorial-reaction`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token || '',
        },
        body: JSON.stringify({ reaction }),
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Tepki kaydedilemedi.');
      }
    } catch (error) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({ title: @json(__('Error')), text: error.message || @json(__('Reaction could not be recorded.')), icon: 'error' });
      } else {
        alert(error.message || 'Tepki kaydedilemedi.');
      }
      return;
    }
    const handlers = {
      mourning: taziyeBirak,
      whisper: fisiltiYay,
      bounty: fermanKayit,
      victory: zaferKutla,
    };
    (handlers[reaction] || zaferKutla)(title);
  }

  function taziyeBirak(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '🕯️ ' + @json(__('Memorial & Moment of Silence')),
        text: @json(__('You lit a candle in memory of our fallen heroes. Their names have been inscribed into the Valiant Shade Book of Honor.')),
        icon: 'info',
        confirmButtonText: @json(__('May Their Souls Rest in Glory')),
        customClass: { confirmButton: 'btn btn-danger' },
        buttonsStyling: false
      });
    } else {
      alert(@json(__('Tribute paid to fallen heroes.')));
    }
  }

  function fisiltiYay(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '🍺 ' + @json(__('Tavern Whisper')),
        text: @json(__('You bought a round of ales at the Red Inn and whispered the rumor into eager ears. Suspicion in the city has increased!')),
        icon: 'warning',
        confirmButtonText: @json(__('Keep it Between Us')),
        customClass: { confirmButton: 'btn btn-warning' },
        buttonsStyling: false
      });
    } else {
      alert(@json(__('Rumor spread through the tavern.')));
    }
  }

  function fermanKayit(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '📜 ' + @json(__('Decree Signed')),
        text: @json(__('You have enlisted for the bounty expedition. Your share will await you in the guild coffers once the targets are delivered alive or dead!')),
        icon: 'success',
        confirmButtonText: @json(__('My Blade is Ready')),
        customClass: { confirmButton: 'btn btn-primary' },
        buttonsStyling: false
      });
    } else {
      alert(@json(__('Enlistment confirmed.')));
    }
  }

  function zaferKutla(title) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: '🏆 ' + @json(__('Victory Celebration!')),
        text: @json(__('Steins were raised in honor of the grand victory. The Guild Council granted you +50 XP bonus honor points!')),
        icon: 'success',
        confirmButtonText: @json(__('For Valiant Shade!')),
        customClass: { confirmButton: 'btn btn-success' },
        buttonsStyling: false
      });
    } else {
      alert(@json(__('Victory celebrated!')));
    }
  }

  function initNewsTypePicker(groupEl, inputEl) {
    if (!groupEl || !inputEl) return;
    groupEl.addEventListener('click', function (event) {
      const btn = event.target.closest('[data-type]');
      if (!btn || !groupEl.contains(btn)) return;
      groupEl.querySelectorAll('.vs-type-picker__btn').forEach(function (item) {
        item.classList.remove('is-selected');
        item.setAttribute('aria-checked', 'false');
      });
      btn.classList.add('is-selected');
      btn.setAttribute('aria-checked', 'true');
      inputEl.value = btn.dataset.type;
    });
  }

  window.resetAllNewsFilters = function () {
    const searchInput = document.getElementById('liveSearchInput');
    if (searchInput) searchInput.value = '';

    const filterButtons = document.querySelectorAll('#categoryFilterGroup .category-filter-btn');
    filterButtons.forEach(b => b.classList.remove('active'));
    document.querySelector('#categoryFilterGroup [data-cat="all"]')?.classList.add('active');

    const gazetteFilterButtons = document.querySelectorAll('#gazetteSlotFilterGroup .gazette-filter-btn');
    gazetteFilterButtons.forEach(b => b.classList.remove('active'));
    document.querySelector('#gazetteSlotFilterGroup [data-gazette-slot="all"]')?.classList.add('active');

    const statCards = document.querySelectorAll('.js-category-stat-card');
    statCards.forEach(c => c.classList.remove('is-active-filter'));

    if (window.runNewsCardFilter) {
      window.runNewsCardFilter('all', 'all');
    }
  };

  // DOM Loaded Event Bindings
  document.addEventListener('DOMContentLoaded', function () {
    initNewsTypePicker(document.getElementById('whisperTypePicker'), document.getElementById('whisperNewsType'));
    const cards = document.querySelectorAll('.news-item-col');
    const filterButtons = document.querySelectorAll('#categoryFilterGroup .category-filter-btn');
    const gazetteFilterButtons = document.querySelectorAll('#gazetteSlotFilterGroup .gazette-filter-btn');
    const statCards = document.querySelectorAll('.js-category-stat-card');
    const searchInput = document.getElementById('liveSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const resultsCountBadge = document.getElementById('newsResultsCount');
    const noResultsPlaceholder = document.getElementById('noJsResultsPlaceholder');
    const newsGrid = document.getElementById('newsCardsContainer');
    const reorderUrl = newsGrid?.getAttribute('data-reorder-url') || '';

    let currentFilter = 'all';
    let currentGazetteSlot = 'all';

    function filterCards() {
      const query = (searchInput.value || '').trim().toLowerCase();
      let visibleCount = 0;

      cards.forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const desc = card.getAttribute('data-desc') || '';
        const location = card.getAttribute('data-location') || '';
        const author = card.getAttribute('data-author') || '';

        // Category check
        const cardCatType = card.getAttribute('data-cat-type') || '';
        const matchCategory = (currentFilter === 'all') || (cardCatType === currentFilter);

        const cardGazetteSlot = card.getAttribute('data-gazette-slot') || 'article';
        const matchGazette = (currentGazetteSlot === 'all') || (cardGazetteSlot === currentGazetteSlot);

        // Search check
        const matchQuery = !query
          || title.includes(query)
          || desc.includes(query)
          || location.includes(query)
          || author.includes(query);

        if (matchCategory && matchGazette && matchQuery) {
          card.style.display = '';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // Update Result Count Badge
      if (resultsCountBadge) {
        if (query || currentFilter !== 'all' || currentGazetteSlot !== 'all') {
          resultsCountBadge.style.display = 'inline-block';
          resultsCountBadge.textContent = `${visibleCount} / ${cards.length}`;
        } else {
          resultsCountBadge.style.display = 'none';
        }
      }

      // Show/hide empty placeholder
      if (noResultsPlaceholder) {
        if (visibleCount === 0 && cards.length > 0) {
          noResultsPlaceholder.classList.remove('d-none');
        } else {
          noResultsPlaceholder.classList.add('d-none');
        }
      }
    }

    window.runNewsCardFilter = function(cat, slot) {
      if (cat !== undefined) currentFilter = cat;
      if (slot !== undefined) currentGazetteSlot = slot;
      filterCards();
    };

    // Category Button Clicks
    filterButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        filterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.getAttribute('data-cat') || 'all';

        // Sync with stat cards highlight
        statCards.forEach(c => {
          c.classList.toggle('is-active-filter', c.getAttribute('data-filter-cat') === currentFilter);
        });

        filterCards();
      });
    });

    // Interactive Quick Stat Cards
    statCards.forEach(card => {
      card.addEventListener('click', function () {
        const cat = this.getAttribute('data-filter-cat');
        if (!cat) return;

        // If already active, toggle back to 'all'
        if (currentFilter === cat) {
          currentFilter = 'all';
          this.classList.remove('is-active-filter');
          filterButtons.forEach(b => b.classList.toggle('active', b.getAttribute('data-cat') === 'all'));
        } else {
          currentFilter = cat;
          statCards.forEach(c => c.classList.remove('is-active-filter'));
          this.classList.add('is-active-filter');

          filterButtons.forEach(b => {
            b.classList.toggle('active', b.getAttribute('data-cat') === cat);
          });
        }

        filterCards();

        // Smooth scroll to feed
        const target = document.getElementById('newsFeedGrid');
        if (target) {
          window.scrollTo({ top: target.offsetTop - 75, behavior: 'smooth' });
        }
      });
    });

    gazetteFilterButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        gazetteFilterButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentGazetteSlot = this.getAttribute('data-gazette-slot') || 'all';
        filterCards();
      });
    });

    function newsIdsInDomOrder() {
      return Array.from(newsGrid.querySelectorAll('.news-item-col[draggable="true"]'))
        .map((card) => parseInt(card.getAttribute('data-id') || '0', 10))
        .filter((id) => Number.isFinite(id) && id > 0);
    }

    let dragSource = null;

    async function saveNewsOrder() {
      if (!reorderUrl || !canUpdateNews) return;
      const ids = newsIdsInDomOrder();
      if (!ids.length) return;
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      try {
        const response = await fetch(reorderUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token || '',
          },
          body: JSON.stringify({ ids }),
        });
        if (!response.ok) {
          throw new Error(@json(__('News order could not be saved.')));
        }
      } catch (error) {
        console.error(error);
        alert(@json(__('News order could not be saved.')));
      }
    }

    if (reorderUrl && canUpdateNews && newsGrid) {
      cards.forEach((card) => {
        card.addEventListener('dragstart', (event) => {
          dragSource = card;
          card.classList.add('news-item-col--dragging');
          event.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => {
          card.classList.remove('news-item-col--dragging');
          cards.forEach((c) => c.classList.remove('news-item-col--drag-over'));
          dragSource = null;
        });
        card.addEventListener('dragover', (event) => {
          if (!dragSource || dragSource === card) return;
          event.preventDefault();
          card.classList.add('news-item-col--drag-over');
        });
        card.addEventListener('dragleave', () => {
          card.classList.remove('news-item-col--drag-over');
        });
        card.addEventListener('drop', (event) => {
          event.preventDefault();
          card.classList.remove('news-item-col--drag-over');
          if (!dragSource || dragSource === card) return;
          const all = Array.from(newsGrid.querySelectorAll('.news-item-col'));
          const targetIdx = all.indexOf(card);
          const sourceIdx = all.indexOf(dragSource);
          if (targetIdx < 0 || sourceIdx < 0) return;
          if (sourceIdx < targetIdx) {
            card.after(dragSource);
          } else {
            card.before(dragSource);
          }
          saveNewsOrder();
        });
      });
    }

    // Search Input
    searchInput.addEventListener('input', filterCards);

    // Clear Search
    clearBtn.addEventListener('click', function () {
      searchInput.value = '';
      filterCards();
      searchInput.focus();
    });

    // Auto-open modal if hash exists, e.g. #news-1
    if (window.location.hash && window.location.hash.startsWith('#news-')) {
      const idFromHash = parseInt(window.location.hash.replace('#news-', ''), 10);
      if (!isNaN(idFromHash)) {
        setTimeout(() => openNewsDetail(idFromHash), 300);
      }
    }

    // Submit Whisper Modal Handling
    const btnSubmitWhisper = document.getElementById('btnSubmitWhisper');
    if (btnSubmitWhisper) {
      btnSubmitWhisper.addEventListener('click', async function () {
        const subject = document.getElementById('whisperSubject').value.trim();
        const msg = document.getElementById('whisperMessage').value.trim();
        const newsType = document.getElementById('whisperNewsType').value;
        const anonymous = document.getElementById('anonymousWhisperCheck').checked;

        if (!subject || !msg) {
          alert('{{ __("Please fill in both whisper subject and message.") }}');
          return;
        }

        btnSubmitWhisper.disabled = true;
        try {
          const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const response = await fetch('{{ route('api.public.news.submit') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({
              title: subject,
              news_type: newsType,
              description: msg,
              anonymous: anonymous,
            }),
          });
          const result = await response.json();
          if (!response.ok || !result.success) {
            throw new Error(result.message || '{{ __("Submission failed.") }}');
          }

          const modalEl = document.getElementById('whisperTipModal');
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();

          document.getElementById('whisperSubject').value = '';
          document.getElementById('whisperMessage').value = '';

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: '🤫 ' + @json(__('Whisper Received!')),
              text: result.message,
              icon: 'success',
              confirmButtonText: @json(__('Understood')),
              customClass: { confirmButton: 'btn btn-warning' },
              buttonsStyling: false
            }).then(() => window.location.reload());
          } else {
            alert(result.message);
            window.location.reload();
          }
        } catch (error) {
          alert(error.message || '{{ __("Submission failed.") }}');
        } finally {
          btnSubmitWhisper.disabled = false;
        }
      });
    }
  });
</script>
@endsection
