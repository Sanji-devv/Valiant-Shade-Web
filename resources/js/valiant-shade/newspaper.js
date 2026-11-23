import { createRumorWizard } from './newspaper-submission-wizard.js';
import { createGnomeCryController, PUBLICATION_GNOME, PUBLICATION_LAGIM } from './newspaper-gnome-cry.js';
import { findGazetteIssue, gazettePath, parseGazettePath } from './newspaper-urls.js';

const WEB_ITEMS_PER_PAGE = 9;
const STREAM_ITEMS_PER_PAGE = 12;

let html2canvasModulePromise;

async function loadHtml2Canvas() {
  if (!html2canvasModulePromise) {
    html2canvasModulePromise = import('html2canvas').then((mod) => mod.default || mod);
  }
  return html2canvasModulePromise;
}
const GAZETTE_SUMMARY_SLOTS = 5;

const state = {
  page: 1,
  type: 'all',
  search: '',
  perPage: WEB_ITEMS_PER_PAGE,
  issueId: null,
  pendingIssueNumber: null,
  issueArchive: [],
  currentIssue: null,
  sliderIndex: 0,
  featured: [],
  sliderTimer: null,
  viewMode: 'grid',
  layoutMode: 'gazette',
  filterTypes: [],
  filterTypeCounts: {},
  lastFeedItems: [],
  lastFeedMeta: null,
  pageTurnDir: 'fwd',
  gazetteArticleIds: [],
  gazetteInnerPerPage: 3,
  pendingArticleId: null,
  pendingArticleSlug: null,
  suppressUrlSync: false,
  articleBootstrapInFlight: false,
  articleOpenedFromDeepLink: false,
  openArticleId: null,
  coverMastIndex: 0,
  coverMastTimer: null,
  streamPage: 1,
  streamItems: [],
  streamHasMore: true,
  streamLoading: false,
  streamReturnLayout: null,
  streamObserver: null,
  publicationMode: PUBLICATION_LAGIM,
  gnomeCurrentId: null,
  gnomeEditionId: null,
  gnomeEditionNumberHint: null,
  gnomeEditions: [],
  lagimLayoutBeforeGnome: null,
};

let gnomeCryCtrl;

const els = {};

let rumorWizard;

/** Uzun tire (—/–) gazete yüzünde kullanılmaz; okunur Türkçe noktalama. */
function gazetteScrub(text) {
  if (text == null) {
    return '';
  }
  return String(text)
    .replace(/\u2014/g, ' · ')
    .replace(/\u2013/g, ' · ')
    .replace(/\s*·\s*/g, ' · ')
    .replace(/\s{2,}/g, ' ')
    .trim();
}

function qs(id) {
  return document.getElementById(id);
}

function displayDateText(item) {
  if (!item) {
    return '';
  }
  return item.world_date_text || item.date_text || '';
}

function escapeHtml(text) {
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

const MEDIA_FIT_CLASS = ['vs-media-fit--cover', 'vs-media-fit--contain', 'vs-media-fit--cover-top'];

/**
 * Kapak / kart / modal arka planlarını görsel oranına göre oturtur (geniş woodcut → contain).
 */
function applyAdaptiveNewsImage(el, imageUrl) {
  if (!el || !imageUrl) {
    return () => {};
  }
  const decide = () => {
    const probe = new Image();
    probe.decoding = 'async';
    probe.onload = () => {
      const w = el.clientWidth || el.offsetWidth;
      const h = el.clientHeight || el.offsetHeight;
      if (!w || !h || !probe.naturalWidth || !probe.naturalHeight) {
        return;
      }
      const containerAspect = w / h;
      const imageAspect = probe.naturalWidth / probe.naturalHeight;
      const relative = imageAspect / containerAspect;
      el.classList.remove(...MEDIA_FIT_CLASS);
      if (relative > 1.15) {
        el.classList.add('vs-media-fit--contain');
      } else if (relative < 0.82) {
        el.classList.add('vs-media-fit--cover-top');
      } else {
        el.classList.add('vs-media-fit--cover');
      }
    };
    probe.src = imageUrl;
  };
  decide();
  return decide;
}

function fitAllNewsMedia(root = document) {
  root.querySelectorAll('.vs-news-slide[data-image-url]').forEach((el) => {
    applyAdaptiveNewsImage(el, el.dataset.imageUrl);
  });
  root.querySelectorAll('.vs-news-card__thumb[data-image-url]').forEach((el) => {
    applyAdaptiveNewsImage(el, el.dataset.imageUrl);
  });
}

function badgeClass(color) {
  const map = {
    primary: '#6366f1',
    success: '#22c55e',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#06b6d4',
    dark: '#64748b',
    secondary: '#94a3b8',
  };
  return map[color] || map.primary;
}

function apiUrl(path, params = {}) {
  const url = new URL(path, window.location.origin);
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, value);
    }
  });
  return url.toString();
}

function issueAllowsSubmissions() {
  const issue = state.currentIssue;
  if (!issue) {
    return true;
  }
  return !issue.is_locked;
}

function sortedNavigableIssues() {
  return (state.issueArchive || [])
    .filter((row) => row.is_selectable !== false)
    .slice()
    .sort((a, b) => {
      const numDiff = (Number(a.issue_number) || 0) - (Number(b.issue_number) || 0);
      if (numDiff !== 0) {
        return numDiff;
      }
      return (Number(a.extra_sequence) || 0) - (Number(b.extra_sequence) || 0);
    });
}

function currentNavigableIssueIndex() {
  const list = sortedNavigableIssues();
  const currentId = Number(state.issueId || state.currentIssue?.id || 0);
  if (!currentId) {
    return -1;
  }
  return list.findIndex((row) => Number(row.id) === currentId);
}

function adjacentNavigableIssue(direction) {
  const list = sortedNavigableIssues();
  const idx = currentNavigableIssueIndex();
  if (idx < 0) {
    return null;
  }
  const nextIdx = direction === 'next' ? idx + 1 : idx - 1;
  if (nextIdx < 0 || nextIdx >= list.length) {
    return null;
  }
  return list[nextIdx];
}

async function goToAdjacentIssue(direction) {
  const row = adjacentNavigableIssue(direction);
  if (!row?.id) {
    return false;
  }
  state.issueId = Number(row.id);
  state.pageTurnDir = direction === 'next' ? 'fwd' : 'back';
  state.page = direction === 'prev' ? 9999 : 1;
  await loadFeed();
  return true;
}

function canCrossIssueNav(direction) {
  if (!isGazetteLayout() || isGlobalFeedMode()) {
    return false;
  }
  return Boolean(adjacentNavigableIssue(direction));
}

async function goGazettePrev() {
  if (gnomeCryCtrl?.isGnomePublication() && gnomeCryCtrl.isGnomeDesktopSheet()) {
    gnomeCryCtrl.stepEdition(-1);
    return;
  }
  if (!isGazetteLayout() || isGlobalFeedMode()) {
    goToFeedPage(state.page - 1, 'back');
    return;
  }
  if (state.page > 1) {
    goToFeedPage(state.page - 1, 'back');
    return;
  }
  await goToAdjacentIssue('prev');
}

async function goGazetteNext() {
  if (gnomeCryCtrl?.isGnomePublication() && gnomeCryCtrl.isGnomeDesktopSheet()) {
    gnomeCryCtrl.stepEdition(1);
    return;
  }
  if (!isGazetteLayout() || isGlobalFeedMode()) {
    goToFeedPage(state.page + 1, 'fwd');
    return;
  }
  const last = Number(state.lastFeedMeta?.last_page) || 1;
  if (state.page < last) {
    goToFeedPage(state.page + 1, 'fwd');
    return;
  }
  await goToAdjacentIssue('next');
}

function resolvePendingIssueNumber() {
  if (!state.pendingIssueNumber) {
    return false;
  }
  const row = findGazetteIssue(state.issueArchive || [], state.pendingIssueNumber);
  state.pendingIssueNumber = null;
  if (!row?.id) {
    return false;
  }
  if (Number(state.issueId) !== Number(row.id)) {
    state.issueId = row.id;
    return true;
  }
  return false;
}

function isGazettePublicHost() {
  return document.body?.dataset?.gazetteSubdomain === '1';
}

function articlePathFromSlug(slug) {
  const normalized = String(slug || '').trim().toLowerCase();
  if (!normalized) {
    return null;
  }
  return `/haber/${normalized}`;
}

function articleSlugFromPathname(pathname = window.location.pathname) {
  const pathMatch = String(pathname || '').match(/^\/haber\/([a-z0-9][a-z0-9-]*)$/i);
  return pathMatch ? pathMatch[1].toLowerCase() : '';
}

function resolveShareArticleSlug({ articleSlug = null, includeArticle = false } = {}) {
  const explicit = String(articleSlug || '').trim().toLowerCase();
  if (explicit) {
    return explicit;
  }
  if (includeArticle && state.openArticleSnapshot?.slug) {
    return String(state.openArticleSnapshot.slug).trim().toLowerCase();
  }
  if (state.pendingArticleSlug) {
    return String(state.pendingArticleSlug).trim().toLowerCase();
  }
  if (state.openArticleSnapshot?.slug) {
    return String(state.openArticleSnapshot.slug).trim().toLowerCase();
  }
  return articleSlugFromPathname();
}

function readUrlIntoState() {
  const params = new URLSearchParams(window.location.search);
  const gorunum = params.get('gorunum');
  const gazetteLocation = parseGazettePath(window.location.pathname);
  const pathSlugMatch = window.location.pathname.match(/^\/haber\/([a-z0-9][a-z0-9-]*)$/i);
  if (pathSlugMatch) {
    state.pendingArticleSlug = pathSlugMatch[1].toLowerCase();
  }
  if (gorunum === 'web' || gorunum === 'gazette' || gorunum === 'stream') {
    state.layoutMode = gorunum === 'stream' ? 'stream' : 'gazette';
  } else if (gazetteLocation || pathSlugMatch || params.has('yaprak') || params.has('sayi') || params.has('haber')) {
    state.layoutMode = 'gazette';
  }

  const yaprak = gazetteLocation?.sheet ?? Number.parseInt(params.get('yaprak') || '', 10);
  if (Number.isFinite(yaprak) && yaprak >= 1) {
    state.page = yaprak;
  }
  const sayfa = Number.parseInt(params.get('sayfa') || '', 10);
  if (!gazetteLocation && !params.has('yaprak') && Number.isFinite(sayfa) && sayfa >= 1) {
    state.page = sayfa;
  }

  const legacySayi = params.get('sayi') || '';
  const sayi = gazetteLocation?.issue
    ?? (/^[1-9]\d*\.[1-9]\d*$/.test(legacySayi) ? legacySayi : Number.parseInt(legacySayi, 10));
  if (sayi && (typeof sayi === 'string' || (Number.isFinite(sayi) && sayi >= 1))) {
    state.pendingIssueNumber = sayi;
  }

  const haber = Number.parseInt(params.get('haber') || '', 10);
  if (Number.isFinite(haber) && haber > 0) {
    state.pendingArticleId = haber;
    if (gorunum !== 'stream' && gorunum !== 'web') {
      state.layoutMode = 'gazette';
    }
  }

  const tur = params.get('tur');
  if (tur) {
    state.type = tur;
  }
  const q = params.get('q') || '';
  if (q) {
    state.search = q.trim();
  }

  if (params.get('flash') === '1') {
    state.pendingFlashModal = true;
  }

  const yayin = (params.get('yayin') || '').toLowerCase();
  if (yayin === 'gnome' || yayin === 'ciglisi' || yayin === PUBLICATION_GNOME) {
    state.publicationMode = PUBLICATION_GNOME;
  } else {
    state.publicationMode = PUBLICATION_LAGIM;
  }
  const parsomen = Number.parseInt(params.get('parsomen') || '', 10);
  if (Number.isFinite(parsomen) && parsomen > 0 && state.publicationMode === PUBLICATION_GNOME) {
    state.gnomeEditionNumberHint = parsomen;
  }
}

function buildShareUrl({ articleId = null, articleSlug = null, includeArticle = false, skipArticle = false } = {}) {
  const slug = skipArticle ? '' : resolveShareArticleSlug({ articleSlug, includeArticle });
  const snapshot = state.openArticleSnapshot;
  if (slug) {
    const articlePath = articlePathFromSlug(slug);
    if (articlePath) {
      const canonical = snapshot?.canonical_url;
      const url = canonical
        ? new URL(canonical)
        : new URL(window.location.origin);
      if (!canonical) {
        url.pathname = articlePath;
      }
      url.search = '';
      if (state.layoutMode === 'stream') {
        url.searchParams.set('gorunum', 'stream');
      }
      return url;
    }
  }

  const layoutPath = window.location.pathname.match(/^\/haber\//) || parseGazettePath(window.location.pathname)
    ? '/'
    : window.location.pathname;
  const url = new URL(layoutPath, window.location.origin);
  const layout = state.layoutMode;
  if (layout === 'stream') {
    url.searchParams.set('gorunum', 'stream');
  } else if (layout === 'gazette') {
    const issue = state.currentIssue || {};
    const issueNum = issue.display_issue_number || issue.issue_number || inWorldCalendar().issueNumber;
    url.pathname = gazettePath(issueNum, state.page);
  } else {
    url.searchParams.set('gorunum', 'web');
    url.searchParams.set('sayfa', String(state.page));
  }
  if (state.type && state.type !== 'all') {
    url.searchParams.set('tur', state.type);
  }
  if (state.search) {
    url.searchParams.set('q', state.search);
  }
  void articleId;
  return url;
}

function syncBrowserUrl(options = {}) {
  if (state.suppressUrlSync) {
    return;
  }
  const next = buildShareUrl(options);
  const nextHref = `${next.pathname}${next.search}`;
  const currentHref = `${window.location.pathname}${window.location.search}`;
  if (nextHref !== currentHref) {
    window.history.replaceState(null, '', nextHref);
  }
}

async function fetchIssueArchive() {
  const response = await fetch(apiUrl('/api/public/news/gazette-issues'), {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  if (!response.ok) {
    throw new Error('Sayı arşivi yüklenemedi.');
  }
  const payload = await response.json();
  state.issueArchive = payload.data || [];
  if (!state.issueId && payload.current_issue_id) {
    state.issueId = payload.current_issue_id;
  }
  renderIssueArchiveSelect();
}

function isSearchActive() {
  return Boolean(String(state.search || '').trim());
}

/** Arama veya tür filtresi: gazete yaprakları yerine tüm sayılarda liste */
function isGlobalFeedMode() {
  return isSearchActive() || (state.type && state.type !== 'all');
}

async function fetchBootstrap() {
  const params = {
    type: state.type === 'all' ? '' : state.type,
    q: state.search,
  };
  const useGazetteApi = isGazetteLayout() && !isGlobalFeedMode();
  if (useGazetteApi) {
    params.gazette_layout = '1';
    params.sheet = state.page;
    if (state.issueId) {
      params.issue_id = state.issueId;
    }
  } else {
    params.page = state.page;
    params.per_page = state.perPage;
  }

  const response = await fetch(apiUrl('/api/public/news/bootstrap', params), {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  if (!response.ok) {
    throw new Error('Gazete verisi yüklenemedi.');
  }
  return response.json();
}

function renderSlider(featured) {
  state.featured = featured || [];
  state.sliderIndex = 0;
  const track = els.sliderTrack;
  track.innerHTML = '';

  if (!state.featured.length) {
    track.innerHTML = `
      <div class="vs-news-slide vs-news-slide--empty">
        <div class="vs-news-slide__content vs-news-slide__content--mast-empty">
          <p class="vs-news-slide__empty-title">Manşet alanı boş</p>
          <p class="vs-news-slide__empty-text">Üst slayt yalnızca manşet haberlerini gösterir. İlk manşeti siz gönderin.</p>
          <button type="button" class="vs-filter-btn vs-news-slide__cta" id="sliderFillMastheadBtn">Manşet gönder</button>
        </div>
      </div>`;
    track.querySelector('#sliderFillMastheadBtn')?.addEventListener('click', () => {
      rumorWizard?.openFlow('lead');
    });
    els.sliderDots.innerHTML = '';
    return;
  }

  state.featured.forEach((item, index) => {
    const slide = document.createElement('article');
    slide.className = 'vs-news-slide vs-news-slide--mast';
    slide.dataset.index = String(index);
    const title = escapeHtml(item.title || '');
    const desc = escapeHtml(truncateText(item.description || '', 220));
    const dateText = escapeHtml(displayDateText(item));
    slide.innerHTML = `
      <div class="vs-news-slide__overlay vs-news-slide__overlay--mast"></div>
      <div class="vs-news-slide__content">
        <div class="vs-news-slide__meta">
          <span class="vs-badge vs-badge--mast">Manşet</span>
          ${dateText ? `<span class="vs-news-slide__date">${dateText}</span>` : ''}
        </div>
        <h2 class="vs-news-slide__title">${title}</h2>
        <p class="vs-news-slide__desc">${desc}</p>
      </div>
    `;
    track.appendChild(slide);
  });

  els.sliderDots.innerHTML = state.featured
    .map((_, i) => `<button type="button" class="vs-news-slider__dot${i === 0 ? ' is-active' : ''}" data-dot="${i}" aria-label="Slayt ${i + 1}"></button>`)
    .join('');

  updateSliderPosition();
  restartSliderAutoplay();
}

function updateSliderPosition() {
  els.sliderTrack.style.transform = `translateX(-${state.sliderIndex * 100}%)`;
  els.sliderDots.querySelectorAll('.vs-news-slider__dot').forEach((dot, i) => {
    dot.classList.toggle('is-active', i === state.sliderIndex);
  });
}

function restartSliderAutoplay() {
  if (state.sliderTimer) {
    clearInterval(state.sliderTimer);
  }
  if (state.featured.length <= 1) {
    return;
  }
  state.sliderTimer = setInterval(() => {
    state.sliderIndex = (state.sliderIndex + 1) % state.featured.length;
    updateSliderPosition();
  }, 7000);
}

function renderFilters(types, typeCounts) {
  state.filterTypes = types || [];
  state.filterTypeCounts = typeCounts || {};
  const allCount = Object.values(typeCounts || {}).reduce((sum, n) => sum + n, 0);
  const buttons = [
    `<button type="button" class="vs-filter-btn${state.type === 'all' ? ' is-active' : ''}" data-type="all">Tümü (${allCount})</button>`,
  ];

  (types || []).forEach((type) => {
    const count = typeCounts?.[type.key] ?? 0;
    buttons.push(
      `<button type="button" class="vs-filter-btn${state.type === type.key ? ' is-active' : ''}" data-type="${type.key}">${escapeHtml(type.label)} (${count})</button>`,
    );
  });

  els.filterGroup.innerHTML = buttons.join('');
  updateFilterToggleLabel();
}

function isMobileViewport() {
  return window.matchMedia('(max-width: 767px)').matches;
}

function mobileLagimLayoutFromPreference(fallback = 'stream') {
  if (!isMobileViewport()) {
    return fallback;
  }

  return 'stream';
}

function shouldForceMobileLagimStream() {
  return isMobileViewport() && state.publicationMode === PUBLICATION_LAGIM;
}

/** Mobilde Lağımdan Sesler her zaman akış modunda kalır (URL / sorgu parametrelerinden bağımsız). */
function enforceMobileLagimStreamMode({ syncUrl = false } = {}) {
  if (!shouldForceMobileLagimStream()) {
    document.body.classList.remove('vs-mobile-lagim-stream-lock');
    return false;
  }
  document.body.classList.add('vs-mobile-lagim-stream-lock');
  const changed = state.layoutMode !== 'stream';
  if (!state.streamReturnLayout) {
    state.streamReturnLayout = 'gazette';
  }
  state.layoutMode = 'stream';
  try {
    localStorage.setItem('vs.gazette.layoutMode', 'stream');
  } catch (e) {
    /* ignore */
  }
  if (syncUrl && !state.suppressUrlSync) {
    const shareSlug = resolveShareArticleSlug({ includeArticle: true });
    syncBrowserUrl(shareSlug ? { articleSlug: shareSlug } : {});
  }
  return changed;
}

function rememberLagimLayoutBeforeGnome() {
  state.lagimLayoutBeforeGnome = state.layoutMode;
}

function restoreLagimLayoutAfterGnome() {
  if (isMobileViewport()) {
    enforceMobileLagimStreamMode();
  } else if (state.lagimLayoutBeforeGnome) {
    state.layoutMode = state.lagimLayoutBeforeGnome;
  }
  if (state.layoutMode === 'stream' && !state.streamReturnLayout) {
    state.streamReturnLayout = 'gazette';
  }
  state.lagimLayoutBeforeGnome = null;
}

function isMobileGazetteLayout() {
  return isMobileViewport() && state.layoutMode === 'gazette';
}

function isGazetteLayout() {
  return state.layoutMode === 'gazette';
}

function isStreamLayout() {
  return state.layoutMode === 'stream';
}

function prefersGazetteArticleModal() {
  if (isStreamLayout()) {
    return state.streamReturnLayout === 'gazette' || document.body?.dataset?.gazetteSubdomain === '1';
  }

  return isGazetteLayout();
}

function streamItemById(id) {
  return state.streamItems.find((row) => Number(row.id) === Number(id));
}

function feedItemById(id) {
  return state.lastFeedItems.find((row) => Number(row.id) === Number(id));
}

function articleSlugForOpen(id, explicitSlug = '') {
  const slug = String(explicitSlug || feedItemById(id)?.slug || streamItemById(id)?.slug || '')
    .trim()
    .toLowerCase();
  return slug;
}

function openArticleFromUi(id, slug = '', extraOptions = {}) {
  if (!id && !slug) {
    return Promise.resolve();
  }

  return openArticle(id, {
    gazetteReader: prefersGazetteArticleModal(),
    slug,
    ...extraOptions,
  });
}

const STREAM_IMAGE_OPEN_ICON =
  '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 17-5.5-5.5a1.2 1.2 0 0 0-1.7 0L8 17"/></svg>';

function gazetteBranding() {
  const body = document.body;
  return {
    logo: body.dataset.gazetteLogo || '',
    name: body.dataset.gazetteName || 'Lağımdan Sesler',
    motto: gazetteScrub(body.dataset.gazetteMotto || 'Dedikodu burada doğrulanır; yoksa da en azından mürekkeple basılır.'),
    press: body.dataset.gazettePress || 'Lağımdan Sesler Basın Bürosu',
  };
}

function truncateText(text, maxLen) {
  const normalized = String(text || '').trim();
  if (normalized.length <= maxLen) {
    return normalized;
  }
  return `${normalized.slice(0, maxLen - 1).trim()}…`;
}

function splitParagraphs(text) {
  const normalized = String(text || '').trim();
  if (!normalized) {
    return [];
  }
  return normalized.includes('\n\n')
    ? normalized.split('\n\n').map((p) => p.trim()).filter(Boolean)
    : normalized.split('\n').map((p) => p.trim()).filter(Boolean);
}

function inWorldCalendar() {
  const body = document.body;
  return {
    dateLine:
      gazetteScrub(body.dataset.gazetteIssueDate || 'Leos ayının 1. günü, Vonyas · 652'),
    issueNumber: body.dataset.gazetteIssueNumber || '1',
    issueLabel: body.dataset.gazetteIssueLabel || 'İlk sayı',
    month: body.dataset.gazetteIssueMonth || 'Leos',
    day: body.dataset.gazetteIssueDay || '1',
    year: body.dataset.gazetteIssueYear || '652',
  };
}

function inWorldDateShort() {
  const cal = inWorldCalendar();
  return `${cal.month} ${cal.day}, ${cal.year}`;
}

function gazetteIssueLabel(meta) {
  const page = meta?.current_page || state.page || 1;
  const cal = inWorldCalendar();
  const issue = state.currentIssue || {};
  const displayNum = issue.display_issue_number || issue.issue_number || cal.issueNumber;
  return {
    page,
    dateStr: issue.date_line || cal.dateLine,
    issueNumber: displayNum,
    issueLabel: issue.label || cal.issueLabel,
    schedule: issue.schedule || '',
    sheetKind: meta?.sheet_kind || (issue.is_extra ? 'extra' : page === 1 ? 'cover' : 'inner'),
    isExtra: Boolean(issue.is_extra),
  };
}

function issueArchiveOptionText(issue) {
  if (issue.archive_label) {
    return issue.archive_label;
  }
  const date = issue.date_short || issue.date_line || '';
  return `Sayı ${issue.issue_number} · ${date}`;
}

function renderIssueArchiveSelect() {
  if (!els.gazetteArchiveSelect) {
    return;
  }
  const globalBrowse = isGlobalFeedMode();
  const placeholder = globalBrowse
    ? '<option value="" selected>Sayı seçin… (şu an tüm sayılarda liste)</option>'
    : '';
  const options = (state.issueArchive || []).map((issue) => {
    const selected =
      !globalBrowse &&
      (Number(state.issueId) === Number(issue.id) || (!state.issueId && issue.is_current));
    const disabled = !issue.is_selectable;
    return `<option value="${issue.id}"${selected ? ' selected' : ''}${disabled ? ' disabled' : ''}>${escapeHtml(issueArchiveOptionText(issue))}</option>`;
  });
  const list = placeholder + (options.join('') || '<option value="">Sayı yok</option>');
  els.gazetteArchiveSelect.innerHTML = list;
  if (!globalBrowse && !state.issueId && state.currentIssue?.id) {
    state.issueId = state.currentIssue.id;
  }
}

function cornerSlotFirstInnerPage(slotIndex) {
  return Math.max(2, Number(slotIndex) + 2);
}

function cornerArticlesForSlot(slot) {
  if (!slot) {
    return [];
  }
  const raw = Array.isArray(slot.articles) ? slot.articles.filter(Boolean) : [];
  if (raw.length) {
    return raw;
  }
  const body = String(slot.body || '').trim();
  if (!body && !slot.is_filled) {
    return [];
  }
  return [
    {
      news_id: slot.news_id,
      title: slot.title,
      body: slot.body,
      teaser: slot.teaser,
      rumuz: slot.rumuz,
      anonymous: slot.anonymous,
    },
  ];
}

function renderCornerSlot(slot, slotIndex = 0) {
  const title = escapeHtml(slot.title || 'Köşe');
  const key = escapeHtml(slot.key || 'corner');
  const body = String(slot.body || '').trim();
  const teaser = String(slot.teaser || '').trim() || (body ? truncateText(body, 120) : '');
  const rumuz = slot.rumuz ? escapeHtml(String(slot.rumuz)) : '';
  const filled = Boolean(slot.is_filled) || Boolean(body);
  const innerPage = cornerSlotFirstInnerPage(slotIndex);

  if (!filled) {
    if (!issueAllowsSubmissions()) {
      return `
        <div class="vs-gazette-corner vs-gazette-corner--empty vs-gazette-corner--archive">
          <span class="vs-gazette-corner__title">${title}</span>
          <span class="vs-gazette-corner__rumuz vs-gazette-corner__rumuz--empty">${rumuz || 'rumuz yok'}</span>
          <span class="vs-gazette-corner__fill">Bu köşe boş kaldı.</span>
        </div>
      `;
    }
    return `
      <button type="button" class="vs-gazette-corner vs-gazette-corner--empty vs-gazette-slot--fill" data-slot-kind="corner" data-corner-key="${key}">
        <span class="vs-gazette-corner__title">${title}</span>
        <span class="vs-gazette-corner__rumuz vs-gazette-corner__rumuz--empty">${rumuz || 'rumuz yok'}</span>
        <span class="vs-gazette-corner__fill">Burayı doldur</span>
      </button>
    `;
  }

  const articles = cornerArticlesForSlot(slot);
  const articleCount = Number(slot.article_count) || articles.length || (filled ? 1 : 0);
  const countBadge =
    articleCount > 1
      ? `<span class="vs-gazette-corner__count">${articleCount} yazı</span>`
      : '';
  const openAttrs = ` ${cornerOpenAttrs(slot, key)}`;

  const stackHtml =
    articles.length > 1
      ? `<div class="vs-gazette-corner__stack">${articles
          .map((art) => {
            const slice =
              String(art.teaser || '').trim() ||
              truncateText(String(art.body || art.title || ''), 100);
            const by = art.rumuz
              ? `<span class="vs-gazette-corner__stack-rumuz">${escapeHtml(String(art.rumuz))}</span>`
              : '';
            return `<div class="vs-gazette-corner__stack-item">
              ${by}
              <p class="vs-gazette-corner__stack-teaser">${escapeHtml(slice)}</p>
            </div>`;
          })
          .join('')}</div>`
      : `<p class="vs-gazette-corner__teaser">${escapeHtml(teaser)}</p>`;

  const byline =
    articles.length <= 1 && (rumuz || slot.anonymous)
      ? `<span class="vs-gazette-corner__rumuz">${escapeHtml(rumuz || (slot.anonymous ? 'Anonim Gözcü' : ''))}</span>`
      : '';

  return `
    <button type="button" class="vs-gazette-corner vs-gazette-corner--teaser vs-gazette-corner--open${articles.length > 1 ? ' vs-gazette-corner--multi' : ''}"${openAttrs}>
      <span class="vs-gazette-corner__title">${title}${countBadge}</span>
      ${byline}
      ${stackHtml}
      <span class="vs-gazette-corner__jump">Oku${articleCount > 1 ? ' · yazı seç' : ''} · ${innerPage}. yaprakta da var</span>
    </button>
  `;
}

function cornerSlotForInnerPage(page) {
  const slots = state.currentIssue?.corner_slots || [];
  if (!slots.length) {
    return null;
  }
  const innerIndex = Math.max(0, Number(page) - 2);
  if (innerIndex >= slots.length) {
    return null;
  }
  return slots[innerIndex];
}

function resolveCornerNewsId(slot) {
  if (!slot) {
    return null;
  }
  const articles = Array.isArray(slot.articles) ? slot.articles.filter(Boolean) : [];
  if (articles.length) {
    const last = articles[articles.length - 1];
    if (last?.news_id != null) {
      return Number(last.news_id);
    }
  }
  if (slot.news_id != null) {
    return Number(slot.news_id);
  }
  return null;
}

function cornerOpenAttrs(slot, key) {
  const newsId = resolveCornerNewsId(slot);
  const parts = [`data-corner-key="${escapeHtml(key)}"`];
  if (newsId) {
    parts.push(`data-open-id="${escapeHtml(String(newsId))}"`);
  }
  return parts.join(' ');
}

function renderInnerCornerRail(slot) {
  if (!slot) {
    return '';
  }
  const title = escapeHtml(slot.title || 'Köşe');
  const key = escapeHtml(slot.key || 'corner');
  const body = String(slot.body || '').trim();
  const rumuz = slot.rumuz ? escapeHtml(String(slot.rumuz)) : '';
  const filled = Boolean(slot.is_filled) || Boolean(body);

  if (!filled) {
    const fillAction = issueAllowsSubmissions()
      ? `<button type="button" class="vs-gazette-corner-rail__fill vs-gazette-slot--fill" data-slot-kind="corner" data-corner-key="${key}">
          Burayı doldur
        </button>`
      : '';
    return `
      <aside class="vs-gazette-corner-rail vs-gazette-corner-rail--empty" aria-label="${title}">
        <h3 class="vs-gazette-corner-rail__title">${title}</h3>
        <p class="vs-gazette-corner-rail__hint">Bu köşe henüz yazılmadı.</p>
        ${fillAction}
      </aside>
    `;
  }

  const articles = cornerArticlesForSlot(slot);
  const articleBlocks = articles
    .map((art, idx) => {
      const artBody = String(art.body || '').trim();
      const paras = splitParagraphs(artBody)
        .map((p) => `<p class="vs-gazette-corner-rail__p">${escapeHtml(p)}</p>`)
        .join('');
      const artRumuz = art.rumuz
        ? escapeHtml(String(art.rumuz))
        : art.anonymous
          ? 'Anonim Gözcü'
          : '';
      const artTitle = art.title ? escapeHtml(String(art.title)) : '';
      const openId =
        art.news_id != null && Number(art.news_id) > 0
          ? ` data-open-id="${escapeHtml(String(art.news_id))}"`
          : '';
      const sep = idx > 0 ? '<hr class="vs-gazette-corner-rail__sep" aria-hidden="true" />' : '';
      return `${sep}
        <div class="vs-gazette-corner-rail__article vs-gazette-corner-rail__article--open" data-corner-key="${key}"${openId} role="button" tabindex="0">
          ${artTitle ? `<h4 class="vs-gazette-corner-rail__article-title">${artTitle}</h4>` : ''}
          ${artRumuz ? `<p class="vs-gazette-corner-rail__rumuz">${artRumuz}</p>` : ''}
          <div class="vs-gazette-corner-rail__body">${paras || '<p class="vs-gazette-corner-rail__p vs-gazette-corner-rail__p--empty">Metin yok.</p>'}</div>
        </div>`;
    })
    .join('');

  return `
    <aside class="vs-gazette-corner-rail vs-gazette-corner-rail--open vs-gazette-corner-rail--multi" aria-label="${title}" data-corner-key="${key}">
      <h3 class="vs-gazette-corner-rail__title">${title}</h3>
      ${articleBlocks}
      <span class="vs-gazette-corner-rail__hint-open">Tam metin için bir yazıya tıklayın</span>
    </aside>
  `;
}

function innerSheetForArticleId(newsId) {
  const ids = state.gazetteArticleIds?.length
    ? state.gazetteArticleIds
    : state.lastFeedMeta?.all_article_ids || [];
  const perPage = state.gazetteInnerPerPage || state.lastFeedMeta?.inner_per_page || 3;
  const idx = ids.findIndex((id) => Number(id) === Number(newsId));
  if (idx < 0) {
    return 2;
  }
  return 2 + Math.floor(idx / perPage);
}

function sheetForArticleId(newsId) {
  const id = Number(newsId);
  const issue = state.currentIssue || {};
  if (Number(issue.cover_lead?.id) === id) {
    return 1;
  }
  const briefs = Array.isArray(issue.cover_briefs) ? issue.cover_briefs : [];
  if (briefs.some((brief) => brief && Number(brief.id) === id)) {
    return 1;
  }
  return innerSheetForArticleId(newsId);
}

function renderCoverSummary(item) {
  if (!item) {
    return `
      <div class="vs-gazette-brief vs-gazette-brief--empty" aria-hidden="true">
        <span class="vs-gazette-brief__kicker">Gündem</span>
        <span class="vs-gazette-brief__title">Henüz haber yok</span>
        <span class="vs-gazette-brief__excerpt">Onaylanan haberlerin kısa özeti burada otomatik görünür.</span>
      </div>
    `;
  }
  const title = escapeHtml(item.title || '');
  const type = escapeHtml(item.news_type_label || item.category || '');
  const excerpt = escapeHtml(truncateText(item.description || '', 140));
  const sheet = innerSheetForArticleId(item.id);
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';
  return `
    <article class="vs-gazette-brief vs-gazette-brief--open" data-open-id="${openId}">
      <span class="vs-gazette-brief__kicker">${type}</span>
      <h3 class="vs-gazette-brief__title">${title}</h3>
      <p class="vs-gazette-brief__excerpt">${excerpt}</p>
      <button type="button" class="vs-gazette-brief__jump" data-goto-page="${sheet}">Devamı ${sheet}. yaprakta</button>
    </article>
  `;
}

function renderCoverMastSlide(item) {
  if (!item) {
    return '';
  }
  const title = escapeHtml(item.title || '');
  const leadType = escapeHtml(item.news_type_label || item.category || 'Manşet');
  const leadParas = splitParagraphs(articlePlainText(item));
  const deck = leadParas[0] ? escapeHtml(truncateText(leadParas[0], 220)) : '';
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';
  return `
    <div class="vs-gazette-mast__slide" data-open-id="${openId}">
      <p class="vs-gazette-article__kicker">${leadType}</p>
      <h2 class="vs-gazette-cover__headline">
        <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
        ${title}
        <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
      </h2>
      <p class="vs-gazette-cover__deck">${deck}</p>
    </div>
  `;
}

function renderCoverMastheadCarousel(issue) {
  const mastheads = Array.isArray(issue.cover_mastheads) ? issue.cover_mastheads.filter(Boolean) : [];
  const fallback = issue.cover_lead ? [issue.cover_lead] : [];
  const slides = mastheads.length ? mastheads : fallback;
  if (!slides.length) {
    if (!issueAllowsSubmissions()) {
      return `
        <div class="vs-gazette-cover__lead vs-gazette-brief--empty vs-gazette-brief--archive" aria-hidden="true">
          <span class="vs-gazette-brief__kicker">Manşet</span>
          <span class="vs-gazette-brief__title">Manşet yok</span>
          <span class="vs-gazette-brief__excerpt">Bu sayı arşivlendi; boş manşet alanı doldurulamaz.</span>
        </div>`;
    }
    return `
      <button type="button" class="vs-gazette-cover__lead vs-gazette-brief--empty vs-gazette-slot--fill" data-slot-kind="lead" data-brief-slot="lead">
        <span class="vs-gazette-brief__kicker">Manşet</span>
        <span class="vs-gazette-brief__title">Burayı doldur</span>
        <span class="vs-gazette-brief__excerpt">Haftanın ana haberini gönder. Editör onayından sonra kapakta yer alır.</span>
      </button>`;
  }
  const slidesHtml = slides.map((item) => renderCoverMastSlide(item)).join('');
  const dots = slides
    .map((_, i) => `<button type="button" class="vs-gazette-mast__dot${i === 0 ? ' is-active' : ''}" data-mast-goto="${i}" aria-label="Manşet ${i + 1}"></button>`)
    .join('');
  return `
    <div class="vs-gazette-mast" data-mast-count="${slides.length}">
      <div class="vs-gazette-mast__glow" aria-hidden="true"></div>
      <div class="vs-gazette-mast__viewport">
        <div class="vs-gazette-mast__track">${slidesHtml}</div>
      </div>
      <div class="vs-gazette-mast__controls">
        <button type="button" class="vs-gazette-mast__arrow vs-gazette-mast__arrow--prev" data-mast-prev aria-label="Önceki manşet">‹</button>
        <div class="vs-gazette-mast__dots">${dots}</div>
        <button type="button" class="vs-gazette-mast__arrow vs-gazette-mast__arrow--next" data-mast-next aria-label="Sonraki manşet">›</button>
      </div>
    </div>`;
}

function setCoverMastIndex(index) {
  const root = els.gazetteStage?.querySelector('.vs-gazette-mast');
  if (!root) {
    return;
  }
  const count = Number(root.dataset.mastCount) || 1;
  const next = ((Number(index) % count) + count) % count;
  state.coverMastIndex = next;
  const track = root.querySelector('.vs-gazette-mast__track');
  if (track) {
    track.style.setProperty('--mast-index', String(next));
  }
  root.querySelectorAll('.vs-gazette-mast__dot').forEach((dot, i) => {
    dot.classList.toggle('is-active', i === next);
  });
}

function initCoverMastSlider() {
  if (state.coverMastTimer) {
    clearInterval(state.coverMastTimer);
    state.coverMastTimer = null;
  }
  const root = els.gazetteStage?.querySelector('.vs-gazette-mast');
  if (!root) {
    return;
  }
  const count = Number(root.dataset.mastCount) || 1;
  if (count <= 1) {
    return;
  }
  state.coverMastIndex = 0;
  setCoverMastIndex(0);
  root.querySelector('[data-mast-prev]')?.addEventListener('click', (event) => {
    event.stopPropagation();
    setCoverMastIndex(state.coverMastIndex - 1);
  });
  root.querySelector('[data-mast-next]')?.addEventListener('click', (event) => {
    event.stopPropagation();
    setCoverMastIndex(state.coverMastIndex + 1);
  });
  root.querySelectorAll('[data-mast-goto]').forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.stopPropagation();
      setCoverMastIndex(Number(btn.dataset.mastGoto));
    });
  });
  state.coverMastTimer = setInterval(() => {
    if (!document.body.contains(root) || !isGazetteLayout()) {
      return;
    }
    setCoverMastIndex(state.coverMastIndex + 1);
  }, 6500);
}

function renderGazetteExtraPage(item, brand, issueMeta) {
  if (!item) {
    return '<p class="vs-gazette-extra__empty">Bu ek sayıda henüz metin yok.</p>';
  }
  const title = escapeHtml(item.title || '');
  const loc = item.location_badge ? escapeHtml(item.location_badge) : '';
  const body = paragraphsToHtml(articlePlainText(item), 48);
  const img =
    item.image_asset
      ? `<figure class="vs-gazette-extra__figure">
          <img class="vs-gazette-extra__img" src="${escapeHtml(item.image_asset)}" alt="" loading="eager" decoding="async">
          ${loc ? `<figcaption class="vs-gazette-extra__cap">${loc}</figcaption>` : ''}
        </figure>`
      : '';
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';

  return `
    <div class="vs-gazette-extra" data-open-id="${openId}">
      <h1 class="vs-gazette-extra__title">${title}</h1>
      ${loc && !img ? `<p class="vs-gazette-extra__place">${loc}</p>` : ''}
      ${img}
      <div class="vs-gazette-extra__body vs-gazette-extra__body--columns">${body}</div>
      <footer class="vs-gazette-extra__foot">
        <span class="vs-gazette-extra__stamp">${escapeHtml(brand.press || 'Lağımdan Sesler Basın Bürosu')}</span>
        <span class="vs-gazette-extra__note">Tek yaprak · sokaklara fırlatıldı</span>
      </footer>
    </div>
  `;
}

function renderGazetteCoverPage(_items, brand, issueMeta) {
  const issue = state.currentIssue || {};
  const briefItems = Array.isArray(issue.cover_briefs)
    ? [...issue.cover_briefs].filter(Boolean).sort((a, b) => (Number(b.id) || 0) - (Number(a.id) || 0))
    : [];
  const slots = issue.corner_slots || [];
  const summaries = [];
  for (let i = 0; i < GAZETTE_SUMMARY_SLOTS; i++) {
    if (briefItems[i]) {
      summaries.push(renderCoverSummary(briefItems[i]));
    }
  }
  const summariesHtml =
    summaries.join('') ||
    '<p class="vs-gazette-cover__summaries-empty">Onaylanan haberlerin özeti kapak gündeminde görünür.</p>';

  const mastBlock = renderCoverMastheadCarousel(issue);

  const corners = slots.length
    ? slots.map((s, i) => renderCornerSlot(s, i)).join('')
    : renderCornerSlot({ key: 'default', title: 'Köşe', body: null, rumuz: null });

  return `
    <div class="vs-gazette-cover">
      <section class="vs-gazette-cover__mast">
        ${mastBlock}
      </section>
      <div class="vs-gazette-cover__grid">
        <div class="vs-gazette-cover__summaries">
          <h3 class="vs-gazette-cover__section-title">Gündem</h3>
          ${summariesHtml}
        </div>
        <aside class="vs-gazette-cover__corners" aria-label="Köşe yazıları">
          <h3 class="vs-gazette-cover__section-title">Köşe Yazıları</h3>
          ${corners}
        </aside>
      </div>
    </div>
  `;
}

const GAZETTE_SIDEBAR_SNIPPETS = [
  'Lağım Telgrafı: Hanın arka kapısından üç yeni söylenti çıktı. Mürekkebi kurumadan yetişin.',
  'Bugünkü koku indeksi: Orta şiddette söylenti, hafif bira ve bir tutam gizem.',
  'İlan: Kayıp taç yok; ama taç biçiminde bir küllük bulundu. Sahibi aranıyor.',
  'Hava: Sisli sabah, öğleden sonra dedikodu. Liman söylentileri rüzgârla yayıldı.',
];

function sidebarSnippet(page) {
  return gazetteScrub(GAZETTE_SIDEBAR_SNIPPETS[(page - 1) % GAZETTE_SIDEBAR_SNIPPETS.length]);
}

function articlePlainText(item) {
  const story = String(item.full_story || item.description || '').trim();
  return story;
}

function paragraphsToHtml(text, maxParas = 12) {
  return splitParagraphs(text)
    .slice(0, maxParas)
    .map((p) => `<p>${escapeHtml(p)}</p>`)
    .join('');
}

async function enrichGazetteItems(items) {
  return Promise.all(
    (items || []).map(async (item) => {
      if (item.full_story && String(item.full_story).trim()) {
        return item;
      }
      try {
        const response = await fetch(`/api/public/news/${item.id}`, {
          headers: { Accept: 'application/json' },
          cache: 'no-store',
        });
        if (!response.ok) {
          return item;
        }
        const payload = await response.json();
        return { ...item, ...payload.news };
      } catch {
        return item;
      }
    }),
  );
}

function formatRumuz(author) {
  const raw = String(author || '').trim();
  if (!raw || /anonim|gizli/i.test(raw)) {
    return 'Anonim Gözcü';
  }
  const parts = raw.split(/\s+/).filter(Boolean);
  if (parts.length >= 2) {
    return parts.map((p) => p.charAt(0).toUpperCase()).join('');
  }
  return raw.length > 10 ? raw.slice(0, 9) + '…' : raw;
}

function isGenericGazetteStockImage(item) {
  const path = String(item?.image_url || item?.image_asset || '').toLowerCase();
  return path.includes('guild_dragon_quest') || path.includes('guild_tavern_whispers');
}

/** Haberde atanmış görsel varsa bas — yalnızca varsayılan stok placeholder gizlenir. */
function shouldShowGazetteFigure(item) {
  if (!item?.image_asset) {
    return false;
  }

  return !isGenericGazetteStockImage(item);
}

function renderGazetteFigure(item) {
  if (!shouldShowGazetteFigure(item)) {
    return '';
  }
  const src = escapeHtml(item.image_asset);
  const cap = item.location_badge ? escapeHtml(item.location_badge) : '';
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';
  const altTitle = escapeHtml(item.title || 'Haberi oku');
  return `
    <figure class="vs-gazette-figure">
      <button type="button" class="vs-gazette-figure__zoom" data-open-id="${openId}" aria-label="${altTitle}. Büyüt ve oku">
        <img class="vs-gazette-figure__img" src="${src}" alt="" loading="lazy" decoding="async" onerror="this.closest('figure')?.remove()">
        <span class="vs-gazette-figure__hint" aria-hidden="true">Büyüt</span>
      </button>
      ${cap ? `<figcaption class="vs-gazette-figure__cap">${cap}</figcaption>` : ''}
    </figure>
  `;
}

function renderGazetteArticle(item, { lead = false } = {}) {
  const title = escapeHtml(item.title || '');
  const type = escapeHtml(item.news_type_label || item.category || '');
  const paras = splitParagraphs(articlePlainText(item));
  const deckLimit = lead ? 240 : 160;
  let deck = '';
  let bodySource = paras;
  if (paras.length > 1) {
    deck = escapeHtml(truncateText(paras[0], deckLimit));
    bodySource = paras.slice(1);
  }
  const body = paragraphsToHtml(bodySource.join('\n\n'), lead ? 16 : 9);
  const loc = item.location_badge ? escapeHtml(item.location_badge) : '';
  const rumuz = escapeHtml(formatRumuz(item.author));
  const authorTitle = escapeHtml(item.author || 'Anonim');
  const leadClass = lead ? ' vs-gazette-article--lead' : '';
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';

  const kickerRumuz = rumuz
    ? ` <span class="vs-gazette-article__kicker-sep" aria-hidden="true">·</span> <span class="vs-gazette-article__kicker-rumuz" title="${authorTitle}">${rumuz}</span>`
    : '';

  const figureHtml = renderGazetteFigure(item);
  const bodyBlock = figureHtml
    ? `<div class="vs-gazette-article__story vs-gazette-article__story--has-figure">${figureHtml}<div class="vs-gazette-article__body">${body}</div></div>`
    : `<div class="vs-gazette-article__body">${body}</div>`;

  return `
    <article class="vs-gazette-article vs-gazette-article--open${leadClass}" data-open-id="${openId}">
      <div class="vs-gazette-article__rumuz" title="${authorTitle}">
        <span class="vs-gazette-rumuz" aria-label="Rumuz: ${authorTitle}">${rumuz}</span>
      </div>
      <div class="vs-gazette-article__main">
        <p class="vs-gazette-article__kicker">${type}${kickerRumuz}</p>
        <h2 class="vs-gazette-article__title">
          <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
          <span class="vs-gazette-article__title-text">${title}</span>
          <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
        </h2>
        ${deck ? `<p class="vs-gazette-article__deck">${deck}</p>` : ''}
        ${loc ? `<p class="vs-gazette-article__place">${loc}</p>` : ''}
        ${bodyBlock}
      </div>
    </article>
  `;
}

function renderGazetteInnerPage(items, brand, issueMeta) {
  const articles = items
    .map((item, index) => {
      const block = renderGazetteArticle(item, { lead: index === 0 });
      const rule = index < items.length - 1 ? '<div class="vs-gazette-article-rule" aria-hidden="true"></div>' : '';
      return block + rule;
    })
    .join('');

  const cornerRail = renderInnerCornerRail(cornerSlotForInnerPage(issueMeta.page));

  return `
    <div class="vs-gazette-inner">
      <div class="vs-gazette-inner-layout">
        <div class="vs-gazette-articles">${articles || '<p class="vs-gazette-inner__empty">Bu yaprakta haber metni yok.</p>'}</div>
        ${cornerRail}
      </div>
    </div>
  `;
}

function renderGazetteMasthead(brand, issueMeta, isFirstPage) {
  const { dateStr, issueNumber, issueLabel } = issueMeta;
  const pageNum = escapeHtml(String(issueMeta.page));
  if (!isFirstPage) {
    return `
      <header class="vs-gazette-running-head">
        <button type="button" class="vs-gazette-home-link vs-gazette-running-head__title" data-gazette-home>${escapeHtml(brand.name)}</button>
        <span class="vs-gazette-page-num vs-gazette-page-num--top">${pageNum}</span>
      </header>
    `;
  }

  return `
    <header class="vs-gazette-masthead">
      <span class="vs-gazette-page-num vs-gazette-page-num--top">${pageNum}</span>
      <div class="vs-gazette-masthead__ornament">☙ &nbsp; <button type="button" class="vs-gazette-home-link" data-gazette-home>${escapeHtml(brand.name)}</button> &nbsp; ❧</div>
      <div class="vs-gazette-masthead__row">
        ${brand.logo ? `<img class="vs-gazette-masthead__crest" src="${escapeHtml(brand.logo)}" width="72" height="72" alt="">` : ''}
        <div class="vs-gazette-masthead__titles">
          <h2 class="vs-gazette-masthead__title">${escapeHtml(brand.name)}</h2>
          <p class="vs-gazette-masthead__subtitle">${escapeHtml(issueMeta.isExtra ? 'Fırlatma ek sayısı · sıcak haber' : issueMeta.schedule || 'Her Vonyas yeni sayı basılır.')}</p>
        </div>
      </div>
      <div class="vs-gazette-masthead__meta">
        <span class="vs-gazette-masthead__date">${escapeHtml(dateStr)}</span>
        <span class="vs-gazette-masthead__issue">Sayı ${escapeHtml(String(issueNumber))}</span>
        <span class="vs-gazette-masthead__motto">${escapeHtml(brand.motto)}</span>
      </div>
    </header>
  `;
}

function renderGazetteSheet(items, meta) {
  const brand = gazetteBranding();
  const issueMeta = gazetteIssueLabel(meta);
  const { page, sheetKind } = issueMeta;
  const isExtra = sheetKind === 'extra';
  const isCover = !isExtra && sheetKind === 'cover';
  const body = isExtra
    ? renderGazetteExtraPage(items[0] || state.currentIssue?.cover_lead, brand, issueMeta)
    : isCover
      ? renderGazetteCoverPage(items, brand, issueMeta)
      : renderGazetteInnerPage(items, brand, issueMeta);

  const cornerSlots = state.currentIssue?.corner_slots || [];
  const innerAllowed = isExtra || isCover || items.length > 0 || cornerSlots.length > 0;
  if (!innerAllowed) {
    return '<div class="vs-loading">Bu iç sayfada metin yok.</div>';
  }

  const footerSidebar = isCover && !isExtra
    ? `
      <aside class="vs-gazette-sidebar vs-gazette-sidebar--cover">
        <h3>Lağım Köşesi</h3>
        <p>${escapeHtml(sidebarSnippet(page))}</p>
      </aside>`
    : '';

  const sheet = `
    <article class="vs-gazette-sheet${isExtra ? ' vs-gazette-sheet--extra' : isCover ? ' vs-gazette-sheet--cover' : ' vs-gazette-sheet--inner'}" id="gazettePrintSheet">
      ${renderGazetteMasthead(brand, issueMeta, isCover || isExtra)}
      <div class="vs-gazette-rule vs-gazette-rule--heavy" aria-hidden="true"></div>
      ${body}
      ${footerSidebar}
      <footer class="vs-gazette-footer">
        <span class="vs-gazette-page-num vs-gazette-page-num--bottom">${page}</span>
      </footer>
    </article>
  `;

  return sheet;
}

function renderGazette(items, meta) {
  if (!els.gazetteStage) {
    return;
  }
  els.gazetteStage.innerHTML = renderGazetteSheet(items, meta);
  const sheet = els.gazetteStage.querySelector('.vs-gazette-sheet');
  if (sheet) {
    sheet.classList.remove('is-turning', 'is-turning--back');
    sheet.classList.add('is-turning');
    if (state.pageTurnDir === 'back') {
      sheet.classList.add('is-turning--back');
    }
    sheet.addEventListener(
      'animationend',
      () => {
        sheet.classList.remove('is-turning', 'is-turning--back');
      },
      { once: true },
    );
  }
  initCoverMastSlider();
  if (isMobileGazetteLayout()) {
    return;
  }
  requestAnimationFrame(() => {
    els.gazetteStage.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

async function downloadGazettePagePng() {
  const gnomeSheet = gnomeCryCtrl?.isGnomePublication()
    ? (els.gnomeCryStage?.querySelector('#gnomePrintSheet')
      || els.gnomeCryFlow?.querySelector('.vs-gnome-cry-sheet'))
    : null;
  const sheet = gnomeSheet || els.gazetteStage?.querySelector('.vs-gazette-sheet');
  if (!sheet) {
    showToast('İndirilecek gazete yaprağı yüklenemedi.');
    return;
  }

  const button = els.downloadGazettePngBtn;
  if (button) {
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
  }

  try {
    await document.fonts.ready;
    await Promise.all(Array.from(sheet.querySelectorAll('img')).map((image) => {
      image.loading = 'eager';
      return new Promise((resolve, reject) => {
        const fail = () => reject(new Error(`Gazete görseli yüklenemedi: ${image.currentSrc || image.src}`));
        const loaded = async () => {
          if (!image.naturalWidth) {
            fail();
            return;
          }
          try {
            await image.decode();
            resolve();
          } catch (error) {
            reject(error);
          }
        };

        if (image.complete) {
          loaded();
          return;
        }

        image.addEventListener('load', loaded, { once: true });
        image.addEventListener('error', fail, { once: true });
        if (image.complete) {
          image.removeEventListener('load', loaded);
          image.removeEventListener('error', fail);
          loaded();
        }
      });
    }));

    const html2canvas = await loadHtml2Canvas();
    const canvas = await html2canvas(sheet, {
      backgroundColor: null,
      scale: 2,
      useCORS: true,
      onclone: (clonedDocument) => {
        clonedDocument.querySelector('#gazettePrintSheet')?.classList.remove('is-turning', 'is-turning--back');
      },
    });
    const blob = await new Promise((resolve, reject) => {
      canvas.toBlob((result) => {
        if (result) {
          resolve(result);
        } else {
          reject(new Error('PNG dosyası oluşturulamadı.'));
        }
      }, 'image/png');
    });

    const issueNumber = state.currentIssue?.issue_number || inWorldCalendar().issueNumber;
    const pageNumber = state.lastFeedMeta?.current_page || state.page || 1;
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = gnomeSheet
      ? `gnome-ciglisi-${state.gnomeCurrentId || 'parsomen'}.png`
      : `lagimdan-sesler-sayi-${issueNumber}-yaprak-${pageNumber}.png`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
  } catch (error) {
    console.error('Gazete yaprağı PNG olarak indirilemedi.', error);
    showToast('PNG indirilemedi. Lütfen tekrar deneyin.');
  } finally {
    if (button) {
      button.disabled = false;
      button.removeAttribute('aria-busy');
    }
  }
}

function placeIssueArchiveSelect() {
  if (!els.gazetteArchiveWrap) {
    return;
  }
  const desktop = window.matchMedia('(min-width: 768px)').matches;
  const mobileGazette = !desktop && isGazetteLayout();
  let target = els.headerIssueSlot;
  if (desktop && els.toolbarBarEnd) {
    target = els.toolbarBarEnd;
  } else if (mobileGazette && els.mobileIssueDock) {
    target = els.mobileIssueDock;
  }
  if (target) {
    if (
      desktop &&
      els.toolbarBarEnd &&
      els.togglePublicationToolbarBtn?.parentElement === els.toolbarBarEnd
    ) {
      els.toolbarBarEnd.insertBefore(els.gazetteArchiveWrap, els.togglePublicationToolbarBtn);
    } else if (els.gazetteArchiveWrap.parentElement !== target) {
      target.appendChild(els.gazetteArchiveWrap);
    }
  }
  els.gazetteArchiveWrap.classList.toggle('vs-gazette-archive-wrap--toolbar', desktop);
  els.gazetteArchiveWrap.classList.toggle('vs-gazette-archive-wrap--mobile-dock', mobileGazette);
  if (els.mobileIssueDock) {
    els.mobileIssueDock.hidden = !mobileGazette;
    els.mobileIssueDock.setAttribute('aria-hidden', mobileGazette ? 'false' : 'true');
  }
}

function syncSearchResultsLayout() {
  const gazette = isGazetteLayout();
  const active = gazette && isGlobalFeedMode();
  document.body.classList.toggle('vs-search-active', active);
  if (els.gazetteArchiveWrap) {
    els.gazetteArchiveWrap.hidden = !gazette;
    if (els.gazetteArchiveSelect) {
      els.gazetteArchiveSelect.disabled = false;
      els.gazetteArchiveSelect.removeAttribute('aria-disabled');
    }
  }
  renderIssueArchiveSelect();
}

function applyLayoutMode() {
  if (gnomeCryCtrl?.isGnomePublication()) {
    document.body.classList.remove('vs-layout-stream', 'vs-mobile-lagim-stream-lock');
    document.body.classList.add('vs-layout-gazette');
    gnomeCryCtrl?.syncGnomeToolbarChrome?.();
    return;
  }
  enforceMobileLagimStreamMode();
  const gazette = isGazetteLayout();
  const stream = isStreamLayout();
  const parchmentChrome = gazette || stream;
  document.body.classList.toggle('vs-layout-gazette', parchmentChrome);
  document.body.classList.toggle('vs-layout-stream', stream);
  syncStreamToggleUi();
  state.perPage = WEB_ITEMS_PER_PAGE;
  if (els.gazetteArchiveWrap) {
    els.gazetteArchiveWrap.hidden = !gazette || stream;
  }
  placeIssueArchiveSelect();
  syncSearchResultsLayout();

  if (els.gazetteFlip) {
    els.gazetteFlip.hidden = !gazette || stream;
  }
  if (els.newsStream) {
    if (stream) {
      els.newsStream.hidden = false;
      els.newsStream.removeAttribute('hidden');
    } else {
      els.newsStream.hidden = true;
      els.newsStream.setAttribute('hidden', '');
    }
  }
  if (els.newsSliderSection) {
    els.newsSliderSection.hidden = gazette || stream;
  }

  gnomeCryCtrl?.syncPublicationToolbarUi?.();
  if (els.printGazetteBtn) {
    els.printGazetteBtn.hidden = !gazette || stream;
  }
  if (els.downloadGazettePngBtn) {
    els.downloadGazettePngBtn.hidden = !gazette || stream;
  }
  if (els.pagination) {
    els.pagination.hidden = stream;
    els.pagination.classList.toggle('vs-pagination--gazette', gazette);
  }
  if (els.newsToolbar) {
    els.newsToolbar.classList.toggle('vs-toolbar--stream-mode', stream);
  }
  syncFilterPanelVisibility();
  if (els.pageHint) {
    els.pageHint.hidden = !gazette;
  }
  if (els.prevPageLabel) {
    els.prevPageLabel.textContent = gazette ? 'Önceki sayfa' : 'Önceki Sayfa';
  }
  if (els.nextPageLabel) {
    els.nextPageLabel.textContent = gazette ? 'Sonraki sayfa' : 'Sonraki Sayfa';
  }
}

function applyViewModeClass() {
  const isList = state.viewMode === 'list' && isMobileGazetteLayout();
  els.newsGrid.classList.toggle('vs-news-list', isList);
  els.newsGrid.classList.toggle('vs-news-grid', !isList);
  if (els.toggleViewModeBtn) {
    els.toggleViewModeBtn.setAttribute('aria-pressed', isList ? 'true' : 'false');
  }
  if (els.toggleViewModeLabel) {
    els.toggleViewModeLabel.textContent = isList ? 'Kart' : 'Liste';
  }
}

function renderGrid(items) {
  applyViewModeClass();

  if (!items.length) {
    els.newsGrid.innerHTML = '<div class="vs-loading">Bu sayfada haber bulunamadı.</div>';
    return;
  }

  if (state.viewMode === 'list' && isMobileGazetteLayout()) {
    els.newsGrid.innerHTML = items
      .map(
        (item) => `
        <article class="vs-news-list__item vs-news-list__item--open" data-open-id="${item.id}" data-article-slug="${escapeHtml(item.slug || '')}" role="button" tabindex="0">
          <span class="vs-news-list__type">${escapeHtml(item.news_type_label || item.category || '')}</span>
          <h3 class="vs-news-list__title">${escapeHtml(item.title || '')}</h3>
          <span class="vs-news-list__meta">${escapeHtml(displayDateText(item))}</span>
        </article>
      `,
      )
      .join('');
    return;
  }

  els.newsGrid.innerHTML = items
    .map(
      (item) => `
      <article class="vs-news-card vs-news-card--open" data-open-id="${item.id}" data-article-slug="${escapeHtml(item.slug || '')}" role="button" tabindex="0">
        <div class="vs-news-card__thumb" data-image-url="${escapeHtml(item.image_asset || '')}" style="background-image:url('${escapeHtml(item.image_asset || '')}')"></div>
        <div class="vs-news-card__body">
          <span class="vs-badge">${escapeHtml(item.news_type_label || item.category || '')}</span>
          <h3 class="vs-news-card__title">${escapeHtml(item.title || '')}</h3>
          <p class="vs-news-card__desc">${escapeHtml(item.description || '')}</p>
          <small style="color:#94a3b8">${escapeHtml(displayDateText(item))}${item.location_badge ? ' • ' + escapeHtml(item.location_badge) : ''}</small>
        </div>
      </article>
    `,
    )
    .join('');
  requestAnimationFrame(() => fitAllNewsMedia(els.newsGrid));
}

function contentLooksLikeHtml(text) {
  const s = String(text || '').trim();
  if (!s) {
    return false;
  }
  return /<\/[a-z][\w:-]*>/i.test(s) || /<(?:p|br|div|ul|ol|li|h[1-6]|blockquote|strong|em|span)\b/i.test(s);
}

function streamCardBodyText(item) {
  const full = String(item.full_story || '').trim();
  const desc = String(item.description || '').trim();
  const text = full || desc;
  if (text.length <= 2400) {
    return text;
  }
  return `${text.slice(0, 2397)}…`;
}

function streamCardBodyContent(item, options = {}) {
  const full = String(item.full_story || '').trim();
  const desc = String(item.description || '').trim();
  const rich = Boolean(options.richHtml) || (full && contentLooksLikeHtml(full));

  if (rich) {
    if (full) {
      return full;
    }
    if (desc) {
      return escapeHtml(desc).replace(/\n/g, '<br>');
    }
    return '';
  }

  return escapeHtml(streamCardBodyText(item));
}

function streamCardBodyUsesRichHtml(item, options = {}) {
  const full = String(item.full_story || '').trim();
  return Boolean(options.richHtml) || (full && contentLooksLikeHtml(full));
}

function renderStreamReactionButtons(item) {
  const reactions = item.reactions || {};
  const picked = storedReaderReaction(item.id);
  return ARTICLE_REACTIONS.map((cfg) => {
    const count = Number(reactions[cfg.key] ?? 0);
    const countHtml =
      count > 0 ? `<span class="vs-stream-post__reaction-count vs-stream-card__reaction-count">${count}</span>` : '';
    const active = picked === cfg.key ? ' is-active' : '';
    const disabled = picked && picked !== cfg.key ? ' disabled' : '';
    return `<button type="button" class="vs-stream-post__reaction vs-stream-card__reaction${active}" data-stream-reaction="${cfg.key}" data-stream-id="${item.id}" aria-pressed="${picked === cfg.key ? 'true' : 'false'}" title="${escapeHtml(cfg.label)}"${disabled}>
      ${cfg.icon}
      ${countHtml}
    </button>`;
  }).join('');
}

function renderStreamCard(item, options = {}) {
  const placementRaw = String(item.gazette_placement_label || '').trim();
  const placement =
    placementRaw && placementRaw !== 'Haber'
      ? `<span class="vs-stream-post__badge">${escapeHtml(placementRaw)}</span>`
      : '';
  const typeLabel = escapeHtml(item.news_type_label || item.category || 'Haber');
  const meta = escapeHtml(displayDateText(item));
  const author = escapeHtml(item.author || 'Gazete Muhabiri');
  const role = escapeHtml(String(item.author_role || '').trim());
  const loc = item.location_badge
    ? `<span class="vs-stream-post__meta-dot" aria-hidden="true">·</span><span class="vs-stream-post__location">${escapeHtml(item.location_badge)}</span>`
    : '';
  const hasImage = Boolean(item.image_asset);
  const richBody = streamCardBodyUsesRichHtml(item, options);
  const body = streamCardBodyContent(item, options);
  const slugAttr = escapeHtml(item.slug || '');
  const views = Number(item.views_count ?? item.engagement?.views ?? 0);
  const viewsHtml =
    views > 0
      ? `<span class="vs-stream-post__stat" title="Okunma">${views.toLocaleString('tr-TR')} okunma</span>`
      : '';
  const imageBtn = hasImage
    ? `<button type="button" class="vs-stream-post__image-open" data-stream-image-open="${item.id}" data-article-slug="${slugAttr}" aria-label="Görseli modalda aç" title="Görseli gör">${STREAM_IMAGE_OPEN_ICON}</button>`
    : '';
  return `<article class="vs-stream-post" data-stream-id="${item.id}" data-open-id="${item.id}" data-article-slug="${slugAttr}" tabindex="0" role="article">
    <header class="vs-stream-post__head">
      <div class="vs-stream-post__names">
        <span class="vs-stream-post__author">${author}</span>
        ${role ? `<span class="vs-stream-post__role">${role}</span>` : ''}
      </div>
      <div class="vs-stream-post__meta">
        <span class="vs-stream-post__type">${typeLabel}</span>
        <span class="vs-stream-post__meta-dot" aria-hidden="true">·</span>
        <time class="vs-stream-post__time">${meta}</time>
        ${loc}
      </div>
    </header>
    ${placement}
    <h2 class="vs-stream-post__title">${escapeHtml(item.title || '')}</h2>
    <div class="vs-stream-post__body${richBody ? ' vs-stream-post__body--rich' : ''}">${body}</div>
    <footer class="vs-stream-post__footer">
      <div class="vs-stream-post__reactions" data-stream-reactions="${item.id}">
        ${renderStreamReactionButtons(item)}
      </div>
      <div class="vs-stream-post__footer-end">
        ${viewsHtml}
        <div class="vs-stream-post__read-actions">
          ${imageBtn}
          <button type="button" class="vs-stream-post__open" data-open-id="${item.id}" data-article-slug="${slugAttr}">Tam metin</button>
        </div>
      </div>
    </footer>
  </article>`;
}

function renderStreamFeed() {
  if (!els.newsStream) {
    return;
  }
  if (!state.streamItems.length) {
    els.newsStream.innerHTML = '<div class="vs-stream-loading">Henüz akışta haber yok.</div>';
    return;
  }
  const cards = state.streamItems.map((item) => renderStreamCard(item)).join('');
  const tail = state.streamHasMore
    ? '<div class="vs-stream-sentinel" id="streamSentinel" aria-hidden="true"></div><div class="vs-stream-loading" id="streamLoadingTail">Daha fazla yükleniyor…</div>'
  : '<div class="vs-stream-loading">Akışın sonu</div>';
  els.newsStream.innerHTML = cards + tail;
  setupStreamInfiniteScroll();
}

function updateStreamCardReactions(articleId, reactions) {
  const wrap = els.newsStream?.querySelector(`[data-stream-reactions="${articleId}"]`);
  if (!wrap) {
    return;
  }
  const picked = storedReaderReaction(articleId);
  wrap.innerHTML = ARTICLE_REACTIONS.map((cfg) => {
    const count = Number(reactions?.[cfg.key] ?? 0);
    const countHtml =
      count > 0 ? `<span class="vs-stream-post__reaction-count vs-stream-card__reaction-count">${count}</span>` : '';
    const active = picked === cfg.key ? ' is-active' : '';
    const disabled = picked && picked !== cfg.key ? ' disabled' : '';
    return `<button type="button" class="vs-stream-post__reaction vs-stream-card__reaction${active}" data-stream-reaction="${cfg.key}" data-stream-id="${articleId}" aria-pressed="${picked === cfg.key ? 'true' : 'false'}" title="${escapeHtml(cfg.label)}"${disabled}>
      ${cfg.icon}
      ${countHtml}
    </button>`;
  }).join('');
}

function setupStreamInfiniteScroll() {
  if (!els.newsStream || !state.streamHasMore) {
    return;
  }
  const sentinel = document.getElementById('streamSentinel');
  if (!sentinel) {
    return;
  }
  if (state.streamObserver) {
    state.streamObserver.disconnect();
  }
  state.streamObserver = new IntersectionObserver(
    (entries) => {
      if (entries.some((e) => e.isIntersecting)) {
        loadStream(false).catch(console.error);
      }
    },
    { rootMargin: '240px 0px' },
  );
  state.streamObserver.observe(sentinel);
}

async function ensureStreamFilters() {
  if ((state.filterTypes || []).length > 0) {
    return;
  }
  try {
    const response = await fetch(apiUrl('/api/public/news/bootstrap', { page: 1, per_page: 1 }), {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    if (!response.ok) {
      return;
    }
    const payload = await response.json();
    renderFilters(payload.types, payload.feed?.type_counts);
  } catch (error) {
    console.warn(error);
  }
}

async function loadStream(reset = true) {
  if (!isStreamLayout() || state.streamLoading) {
    return;
  }
  if (!reset && !state.streamHasMore) {
    return;
  }
  state.streamLoading = true;
  if (reset) {
    state.streamPage = 1;
    state.streamItems = [];
    state.streamHasMore = true;
    if (els.newsStream) {
      els.newsStream.innerHTML = '<div class="vs-stream-loading">Akış yükleniyor…</div>';
    }
    await ensureStreamFilters();
  }
  const page = reset ? 1 : state.streamPage;
  try {
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(STREAM_ITEMS_PER_PAGE),
      stream: '1',
    });
    const q = String(state.search || '').trim();
    if (q) {
      params.set('q', q);
    }
    if (state.type && state.type !== 'all') {
      params.set('type', state.type);
    }
    const response = await fetch(apiUrl(`/api/public/news?${params.toString()}`), {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    if (!response.ok) {
      throw new Error('Akış yüklenemedi.');
    }
    const payload = await response.json();
    const rows = Array.isArray(payload.data) ? payload.data : [];
    const meta = payload.meta || {};
    if (reset) {
      state.streamItems = rows;
    } else {
      state.streamItems = state.streamItems.concat(rows);
    }
    const lastPage = Number(meta.last_page) || page;
    state.streamHasMore = page < lastPage;
    state.streamPage = page + 1;
    renderStreamFeed();
    if (!state.suppressUrlSync && !state.pendingArticleId && !state.pendingArticleSlug) {
      syncBrowserUrl();
    }
  } catch (error) {
    if (els.newsStream) {
      els.newsStream.innerHTML = `<div class="vs-stream-loading">${escapeHtml(error.message || 'Hata')}</div>`;
    }
  } finally {
    state.streamLoading = false;
    const tail = document.getElementById('streamLoadingTail');
    if (tail && !state.streamHasMore) {
      tail.remove();
    }
  }
}

function toggleStreamLayoutMode() {
  if (gnomeCryCtrl?.isGnomePublication()) {
    return;
  }
  if (shouldForceMobileLagimStream()) {
    return;
  }
  if (isStreamLayout()) {
    leaveStreamMode();
    state.layoutMode = state.streamReturnLayout || 'gazette';
    state.streamReturnLayout = null;
    state.page = 1;
  } else {
    state.streamReturnLayout = state.layoutMode;
    state.layoutMode = 'stream';
    state.page = 1;
    state.type = 'all';
  }
  try {
    localStorage.setItem('vs.gazette.layoutMode', state.layoutMode);
  } catch (e) {
    /* ignore */
  }
  applyLayoutMode();
  loadFeed().catch(console.error);
}

function syncStreamToggleUi() {
  const active = isStreamLayout();
  const title = active ? 'Gazete görünümüne dön' : 'Yeniden eskiye, görselsiz akış okuma';
  const label = active ? 'Kapat' : 'Akış';
  if (els.toggleStreamModeBtn) {
    els.toggleStreamModeBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
    els.toggleStreamModeBtn.setAttribute('title', title);
  }
  if (els.toggleStreamModeLabel) {
    els.toggleStreamModeLabel.textContent = label;
  }
  if (els.toggleStreamNavBtn) {
    els.toggleStreamNavBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
    els.toggleStreamNavBtn.setAttribute('title', title);
    els.toggleStreamNavBtn.setAttribute('aria-label', active ? 'Akışı kapat' : 'Akış okuma');
  }
  if (els.toggleStreamNavLabel) {
    els.toggleStreamNavLabel.textContent = label;
  }
}

function leaveStreamMode() {
  if (state.streamObserver) {
    state.streamObserver.disconnect();
    state.streamObserver = null;
  }
  state.streamItems = [];
  state.streamPage = 1;
  state.streamHasMore = true;
  state.streamLoading = false;
  if (els.newsStream) {
    els.newsStream.innerHTML = '';
    els.newsStream.hidden = true;
    els.newsStream.setAttribute('hidden', '');
  }
}

function updateFilterToggleLabel() {
  if (!els.toggleFiltersLabel) {
    return;
  }
  if (state.type === 'all') {
    els.toggleFiltersLabel.textContent = 'Filtreler';
    return;
  }
  const active = (state.filterTypes || []).find((t) => t.key === state.type);
  const short = active?.label ? active.label.split(' ')[0] : state.type;
  els.toggleFiltersLabel.textContent = isMobileGazetteLayout() ? 'Filtre' : short;
}

function syncFilterPanelVisibility() {
  if (!els.filterPanel || !els.toggleFiltersBtn) {
    return;
  }
  if (isStreamLayout()) {
    els.filterPanel.hidden = false;
    els.toggleFiltersBtn.hidden = true;
    els.toggleFiltersBtn.setAttribute('aria-expanded', 'true');
    if (els.newsToolbar) {
      els.newsToolbar.classList.remove('vs-toolbar--filters-open');
    }
    return;
  }
  els.toggleFiltersBtn.hidden = false;
  if (!isMobileGazetteLayout() && state.viewMode === 'list') {
    state.viewMode = 'grid';
    applyViewModeClass();
    if (state.lastFeedItems.length) {
      renderGrid(state.lastFeedItems);
    }
  }
  const desktop = !isMobileGazetteLayout();
  if (desktop) {
    els.filterPanel.hidden = false;
    els.toggleFiltersBtn.setAttribute('aria-expanded', 'true');
    return;
  }
  const expanded = els.toggleFiltersBtn.getAttribute('aria-expanded') === 'true';
  els.filterPanel.hidden = !expanded;
  if (els.newsToolbar) {
    els.newsToolbar.classList.toggle('vs-toolbar--filters-open', !desktop && expanded);
  }
}

function syncPageNavButtons(meta) {
  const atStart = meta.current_page <= 1;
  const atEnd = meta.current_page >= meta.last_page;
  const crossPrev = canCrossIssueNav('prev');
  const crossNext = canCrossIssueNav('next');
  els.prevPage.disabled = atStart && !crossPrev;
  els.nextPage.disabled = atEnd && !crossNext;
  if (els.gazetteFlipPrev) {
    els.gazetteFlipPrev.disabled = atStart && !crossPrev;
    els.gazetteFlipPrev.dataset.crossIssue = atStart && crossPrev ? '1' : '0';
  }
  if (els.gazetteFlipNext) {
    els.gazetteFlipNext.disabled = atEnd && !crossNext;
    els.gazetteFlipNext.dataset.crossIssue = atEnd && crossNext ? '1' : '0';
  }
}

function enterMobileStreamAfterDeepLinkClose() {
  state.layoutMode = 'stream';
  if (!state.streamReturnLayout) {
    state.streamReturnLayout = 'gazette';
  }
  try {
    localStorage.setItem('vs.gazette.layoutMode', 'stream');
  } catch (e) {
    /* ignore */
  }
  applyLayoutMode();
  state.suppressUrlSync = true;
  const url = new URL('/', window.location.origin);
  url.searchParams.set('gorunum', 'stream');
  if (state.type && state.type !== 'all') {
    url.searchParams.set('tur', state.type);
  }
  if (state.search) {
    url.searchParams.set('q', state.search);
  }
  window.history.replaceState(null, '', `${url.pathname}${url.search}`);
  loadFeed()
    .catch(console.error)
    .finally(() => {
      state.suppressUrlSync = false;
    });
}

function goGazetteHome() {
  closeModal();
  state.pendingArticleId = null;
  state.pendingArticleSlug = null;
  state.articleOpenedFromDeepLink = false;
  state.openArticleId = null;
  state.page = 1;
  state.type = 'all';
  state.search = '';
  if (els.searchInput) {
    els.searchInput.value = '';
  }
  document.querySelectorAll('.vs-type-picker__btn.is-selected').forEach((btn) => {
    btn.classList.remove('is-selected');
  });
  const allBtn = document.querySelector('.vs-type-picker__btn[data-type="all"]');
  if (allBtn) {
    allBtn.classList.add('is-selected');
  }
  state.pageTurnDir = 'back';
  enforceMobileLagimStreamMode();
  applyLayoutMode();
  const url = new URL('/', window.location.origin);
  if (state.layoutMode === 'stream') {
    url.searchParams.set('gorunum', 'stream');
    url.searchParams.delete('yaprak');
    url.searchParams.delete('sayi');
    url.searchParams.delete('sayfa');
  } else if (state.layoutMode === 'gazette') {
    const issue = state.currentIssue || {};
    const issueNum = issue.display_issue_number || issue.issue_number || inWorldCalendar().issueNumber;
    url.pathname = gazettePath(issueNum, 1);
  } else {
    url.searchParams.set('gorunum', 'web');
    url.searchParams.set('sayfa', '1');
  }
  state.suppressUrlSync = true;
  window.history.replaceState(null, '', `${url.pathname}${url.search}`);
  loadFeed()
    .catch(console.error)
    .finally(() => {
      state.suppressUrlSync = false;
    });
}

function goToFeedPage(targetPage, turnDir = 'fwd') {
  const last = state.lastFeedMeta?.last_page;
  const page = Math.max(1, Number(targetPage) || 1);
  if (last && page > last) {
    return;
  }
  if (page === state.page) {
    return;
  }
  state.pageTurnDir = turnDir === 'back' ? 'back' : 'fwd';
  state.page = page;
  loadFeed().catch(console.error);
}

async function bootstrapPendingArticle() {
  const articleSlug = state.pendingArticleSlug;
  const articleId = state.pendingArticleId;
  if ((!articleSlug && !articleId) || state.articleBootstrapInFlight) {
    return;
  }
  const entryLayout = state.layoutMode;
  state.articleOpenedFromDeepLink = true;
  state.pendingArticleSlug = null;
  state.pendingArticleId = null;
  state.articleBootstrapInFlight = true;
  try {
    const endpoint = articleSlug
      ? apiUrl(`/api/public/news/by-slug/${encodeURIComponent(articleSlug)}`)
      : apiUrl(`/api/public/news/${articleId}`);
    const response = await fetch(endpoint, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    if (!response.ok) {
      return;
    }
    const payload = await response.json();
    const item = payload.news;
    const resolvedId = Number(item?.id) || articleId;
    const issueId = item?.gazette_issue_id;
    const streamEntry = entryLayout === 'stream';
    if (issueId && Number(state.issueId) !== Number(issueId)) {
      state.issueId = issueId;
      if (!streamEntry && !shouldForceMobileLagimStream()) {
        state.layoutMode = 'gazette';
        applyLayoutMode();
        await loadFeed();
      }
    }
    const targetSheet = sheetForArticleId(resolvedId);
    if (
      !streamEntry &&
      Number.isFinite(targetSheet) &&
      targetSheet >= 1 &&
      targetSheet !== state.page
    ) {
      state.page = targetSheet;
      await loadFeed();
    }
    const slugForOpen = articleSlug || String(item?.slug || '').trim().toLowerCase();
    await openArticle(resolvedId, {
      gazetteReader: streamEntry ? prefersGazetteArticleModal() : isGazetteLayout(),
      slug: slugForOpen || undefined,
    });
  } catch (error) {
    console.warn(error);
  } finally {
    state.articleBootstrapInFlight = false;
  }
}

function renderPagination(meta) {
  state.lastFeedMeta = meta;
  if (Number.isFinite(Number(meta.current_page))) {
    state.page = Number(meta.current_page);
  }
  const pages = `${meta.current_page} / ${meta.last_page}`;
  const pagesCompact = `${meta.current_page}/${meta.last_page}`;
  if (isGazetteLayout()) {
    const issue = state.currentIssue || {};
    const num = issue.display_issue_number || issue.issue_number || inWorldCalendar().issueNumber;
    const kind =
      meta.sheet_kind === 'extra' ? 'Sıcak haber' : meta.sheet_kind === 'cover' ? 'Kapak' : 'İç sayfa';
    const kindShort =
      meta.sheet_kind === 'extra' ? 'Sıcak' : meta.sheet_kind === 'cover' ? 'Kapak' : 'İç';
    const date = issue.date_short ? ` · ${issue.date_short}` : '';
    els.pageSheet.textContent = gazetteScrub(`Sayı ${num}${date} · ${kind} · yaprak ${pages}`);
    if (els.pageSheetShort) {
      els.pageSheetShort.textContent = gazetteScrub(`${num} · ${kindShort} ${pagesCompact}`);
    }
  } else {
    els.pageSheet.textContent = `Gazete Sayfası ${pages}`;
    if (els.pageSheetShort) {
      els.pageSheetShort.textContent = pagesCompact;
    }
  }
  const crossPrev = canCrossIssueNav('prev');
  const crossNext = canCrossIssueNav('next');
  const atStart = meta.current_page <= 1;
  const atEnd = meta.current_page >= meta.last_page;
  if (els.prevPage) {
    els.prevPage.setAttribute(
      'aria-label',
      isGazetteLayout() && atStart && crossPrev ? 'Önceki sayı' : isGazetteLayout() ? 'Önceki yaprak' : 'Önceki sayfa',
    );
  }
  if (els.nextPage) {
    els.nextPage.setAttribute(
      'aria-label',
      isGazetteLayout() && atEnd && crossNext ? 'Sonraki sayı' : isGazetteLayout() ? 'Sonraki yaprak' : 'Sonraki sayfa',
    );
  }
  syncPageNavButtons(meta);
}

async function loadFeed() {
  if (gnomeCryCtrl?.isGnomePublication()) {
    await gnomeCryCtrl.loadGnomeCry();
    return;
  }
  if (enforceMobileLagimStreamMode()) {
    applyLayoutMode();
  }
  if (isStreamLayout()) {
    await loadStream(true);
    if ((state.pendingArticleId || state.pendingArticleSlug) && !state.articleBootstrapInFlight) {
      await bootstrapPendingArticle();
    } else if (!state.suppressUrlSync) {
      const shareSlug = resolveShareArticleSlug({ includeArticle: true });
      syncBrowserUrl(shareSlug ? { articleSlug: shareSlug } : {});
    }
    return;
  }
  const gazette = isGazetteLayout();
  const globalFeed = gazette && isGlobalFeedMode();

  if (gazette && !globalFeed) {
    if (els.gazetteStage) {
      els.gazetteStage.innerHTML = '<div class="vs-loading">Gazete sayfası diziliyor…</div>';
    }
    if (!state.issueArchive.length) {
      try {
        await fetchIssueArchive();
      } catch (archiveError) {
        console.warn(archiveError);
      }
    }
    const issueSwitched = resolvePendingIssueNumber();
    if (issueSwitched && !state.pendingArticleId && !state.pendingArticleSlug) {
      state.page = Math.max(1, state.page);
    }
  } else {
    els.newsGrid.innerHTML = '<div class="vs-loading">Haberler yükleniyor…</div>';
  }
  const payload = await fetchBootstrap();
  renderFilters(payload.types, payload.feed.type_counts);
  state.lastFeedItems = payload.feed.data || [];
  syncSearchResultsLayout();

  if (globalFeed) {
    if (!state.issueArchive.length) {
      try {
        await fetchIssueArchive();
      } catch (archiveError) {
        console.warn(archiveError);
      }
    }
    renderGrid(state.lastFeedItems);
    if (els.gazetteFlip) {
      els.gazetteFlip.hidden = true;
    }
    if (els.gazetteStage) {
      els.gazetteStage.hidden = true;
      els.gazetteStage.innerHTML = '';
    }
    if (els.newsSliderSection) {
      els.newsSliderSection.hidden = true;
    }
    renderIssueArchiveSelect();
  } else if (gazette) {
    state.currentIssue = payload.issue || state.currentIssue;
    state.issueArchive = payload.archive || state.issueArchive;
    state.gazetteArticleIds = payload.feed?.all_article_ids || [];
    state.gazetteInnerPerPage = payload.feed?.meta?.inner_per_page || 3;
    renderIssueArchiveSelect();
    const sheetKind = payload.feed?.meta?.sheet_kind;
    const needsFullText = sheetKind === 'inner' || sheetKind === 'extra';
    if (needsFullText) {
      state.lastFeedItems = await enrichGazetteItems(state.lastFeedItems);
    }
    renderGazette(state.lastFeedItems, payload.feed.meta);
    if (els.newsGrid) {
      els.newsGrid.innerHTML = '';
    }
    if (els.gazetteFlip) {
      els.gazetteFlip.hidden = false;
    }
    if (els.newsSliderSection) {
      els.newsSliderSection.hidden = true;
    }
  } else {
    renderSlider(payload.featured);
    renderGrid(state.lastFeedItems);
    if (els.gazetteStage) {
      els.gazetteStage.hidden = true;
      els.gazetteStage.innerHTML = '';
    }
    if (els.gazetteFlip) {
      els.gazetteFlip.hidden = true;
    }
    if (els.newsSliderSection) {
      els.newsSliderSection.hidden = false;
    }
  }
  renderPagination(payload.feed.meta);
  if (!state.suppressUrlSync) {
    syncBrowserUrl(
      state.openArticleSnapshot?.slug
        ? { articleSlug: state.openArticleSnapshot.slug }
        : {},
    );
  }
  if ((state.pendingArticleId || state.pendingArticleSlug) && !state.articleBootstrapInFlight) {
    await bootstrapPendingArticle();
  }
}

function renderModalBadges(item) {
  const parts = [];
  const typeLabel = item.news_type_label || item.category;
  if (typeLabel) {
    parts.push(`<span class="vs-modal__badge vs-modal__badge--type">${escapeHtml(typeLabel)}</span>`);
  }
  return parts.join('');
}

const MODAL_LOCATION_ICON = `<svg class="vs-modal__loc-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>`;

const MODAL_LOCATION_ICON_SM = `<svg class="vs-modal__loc-icon vs-modal__loc-icon--sm" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>`;

function articleModalDateLine(item) {
  const when = item.world_date_text || item.published_date || item.date_text || '';
  const loc = String(item.location_badge || '').trim();
  if (!when && !loc) {
    return { html: '', hasContent: false };
  }
  const chunks = [];
  if (when) {
    chunks.push(`<time class="vs-modal__date-when">${escapeHtml(when)}</time>`);
  }
  if (loc) {
    if (chunks.length) {
      chunks.push('<span class="vs-modal__date-loc-sep" aria-hidden="true"> · </span>');
    }
    chunks.push(
      `<span class="vs-modal__date-loc">${MODAL_LOCATION_ICON_SM}<span class="vs-modal__date-loc-text">${escapeHtml(loc)}</span></span>`,
    );
  }
  return {
    html: chunks.join(''),
    hasContent: true,
  };
}

function renderModalLocationMarkup(loc) {
  const label = String(loc || '').trim();
  if (!label) {
    return '';
  }
  return `${MODAL_LOCATION_ICON}<span>${escapeHtml(label)}</span>`;
}

function renderStoryHtml(storyText) {
  const normalized = String(storyText || '').trim();
  if (!normalized) {
    return '';
  }
  if (contentLooksLikeHtml(normalized)) {
    return `<div class="vs-article-story vs-article-story--rich">${normalized}</div>`;
  }
  const paragraphs = normalized.includes('\n\n')
    ? normalized.split('\n\n')
    : normalized.split('\n').filter((line) => line.trim() !== '');
  return paragraphs
    .map((p) => p.trim())
    .filter(Boolean)
    .map((p) => `<p class="vs-article-p">${escapeHtml(p)}</p>`)
    .join('');
}

function innerPageForCornerKey(slotKey) {
  const slots = state.currentIssue?.corner_slots || [];
  const idx = slots.findIndex((row) => String(row.key) === String(slotKey));
  if (idx < 0) {
    return 2;
  }
  return cornerSlotFirstInnerPage(idx);
}

async function openCornerReader(slotKey) {
  let slots = state.currentIssue?.corner_slots || [];
  let slot = slots.find((row) => String(row.key) === String(slotKey));
  if (!slot) {
    return;
  }

  const targetPage = innerPageForCornerKey(slotKey);
  const prevPage = Number(state.page) || 1;
  const onCoverOrOtherPage = !isGazetteLayout() || prevPage !== targetPage;
  if (onCoverOrOtherPage && !shouldForceMobileLagimStream()) {
    state.layoutMode = 'gazette';
    state.pageTurnDir = targetPage >= prevPage ? 'fwd' : 'back';
    state.page = targetPage;
    applyLayoutMode();
    state.suppressUrlSync = true;
    try {
      await loadFeed();
    } finally {
      state.suppressUrlSync = false;
    }
    slots = state.currentIssue?.corner_slots || [];
    slot = slots.find((row) => String(row.key) === String(slotKey)) || slot;
  }

  const articles = Array.isArray(slot.articles) ? slot.articles.filter(Boolean) : [];
  if (articles.length > 1) {
    openCornerArticlePicker(slot, articles);
    return;
  }
  const singleId = resolveCornerNewsId(slot);
  if (singleId) {
    await openArticle(singleId, { gazetteReader: true });
    return;
  }
  if (!String(slot.body || '').trim()) {
    return;
  }
  const rumuz = slot.rumuz || (slot.anonymous ? 'Anonim Gözcü' : 'Köşe Yazarı');
  if (els.modalHero) {
    els.modalHero.style.backgroundImage = '';
    els.modalHero.hidden = true;
  }
  if (els.modalBadges) {
    els.modalBadges.innerHTML = '';
  }
  if (els.detailModalPanel) {
    els.detailModalPanel.classList.remove('vs-modal--article-split');
  }
  els.modalTitle.textContent = slot.title || 'Köşe yazısı';
  if (els.modalDate) {
    els.modalDate.hidden = true;
  }
  if (els.modalAuthor) {
    els.modalAuthor.textContent = rumuz;
  }
  if (els.modalAuthorRole) {
    els.modalAuthorRole.hidden = true;
  }
  if (els.modalLocation) {
    els.modalLocation.hidden = true;
  }
  if (els.modalReadTime) {
    els.modalReadTime.hidden = true;
  }
  if (els.modalReactions) {
    els.modalReactions.innerHTML = '';
  }
  els.modalStory.innerHTML = renderStoryHtml(slot.body || '');
  if (els.modalQuote) {
    els.modalQuote.hidden = true;
  }
  if (els.detailModal) {
    els.detailModal.classList.add('vs-modal-backdrop--gazette-read');
    els.detailModal.classList.remove('vs-modal-backdrop--web-read');
  }
  document.body.classList.add('vs-article-modal-open');
  els.detailModal.classList.add('is-open');
  resetArticleModalDrag();
  resetArticleModalHeroPeek();
  initArticleModalSwipe();
  initArticleModalHeroScroll();
  state.openArticleId = null;
  syncBrowserUrl();
}

function openCornerArticlePicker(slot, articles) {
  const title = slot.title || 'Köşe yazıları';
  if (els.modalHero) {
    els.modalHero.style.backgroundImage = '';
    els.modalHero.hidden = true;
  }
  if (els.modalBadges) {
    els.modalBadges.innerHTML = '';
  }
  if (els.modalEngagement) {
    els.modalEngagement.hidden = true;
    els.modalEngagement.innerHTML = '';
  }
  if (els.modalReactions) {
    els.modalReactions.innerHTML = '';
  }
  els.modalTitle.textContent = title;
  if (els.modalDate) {
    els.modalDate.hidden = true;
  }
  if (els.modalAuthor) {
    els.modalAuthor.textContent = `${articles.length} yazı`;
  }
  if (els.modalAuthorRole) {
    els.modalAuthorRole.hidden = true;
  }
  if (els.modalLocation) {
    els.modalLocation.hidden = true;
  }
  if (els.modalReadTime) {
    els.modalReadTime.hidden = true;
  }
  const list = articles
    .slice()
    .reverse()
    .map((row) => {
      const headline = escapeHtml(row.title || 'Başlıksız yazı');
      const excerpt = escapeHtml(truncateText(row.teaser || row.body || '', 120));
      const id = row.news_id != null ? escapeHtml(String(row.news_id)) : '';
      return `<button type="button" class="vs-corner-article-pick" data-open-id="${id}">
        <span class="vs-corner-article-pick__title">${headline}</span>
        <span class="vs-corner-article-pick__excerpt">${excerpt}</span>
      </button>`;
    })
    .join('');
  els.modalStory.innerHTML = `<div class="vs-corner-article-list">${list}</div>`;
  if (els.modalQuote) {
    els.modalQuote.hidden = true;
  }
  if (els.detailModal) {
    els.detailModal.classList.add('vs-modal-backdrop--gazette-read');
    els.detailModal.classList.remove('vs-modal-backdrop--web-read');
  }
  document.body.classList.add('vs-article-modal-open');
  els.detailModal.classList.add('is-open');
  resetArticleModalDrag();
  state.openArticleId = null;
  syncBrowserUrl();
}

function articleModalShowsHero(item, gazetteReader) {
  const heroUrl = item?.image_asset || '';
  if (!heroUrl) {
    return false;
  }
  const slotKind = item?.stats?.submission?.gazette_slot_kind;
  if (slotKind === 'lead' || slotKind === 'corner') {
    return Boolean(item.has_custom_gazette_image);
  }
  if (gazetteReader) {
    return Boolean(item.has_custom_gazette_image);
  }
  return true;
}

const ARTICLE_REACTIONS = [
  {
    key: 'victory',
    label: 'Zafer kutla',
    toast: 'Birakın fıçılar patlasın! Zaferiniz gazetede yankılanıyor.',
    icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10l1 5a4 4 0 0 1-8 0Z"/><path d="M5.5 9H7l1 3"/><path d="M18.5 9H17l-1 3"/></svg>',
  },
  {
    key: 'mourning',
    label: 'Yas tut',
    toast: 'Şehitlerin anısına bir mum yaktınız. İsimleri Onur Kitabı\'na yazıldı.',
    icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 22c4-3 6-7 6-11a6 6 0 1 0-12 0c0 4 2 8 6 11Z"/><path d="M12 13V9"/><path d="M10 3h4"/></svg>',
  },
  {
    key: 'whisper',
    label: 'Han fısıltısı',
    toast: 'Kırmızı Han\'da bir kadeh ısmarladınız; söylenti kulaktan kulağa yayılıyor.',
    icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M17 11h1a3 3 0 0 1 0 6h-1"/><path d="M9 12v6"/><path d="M13 12v6"/><path d="M14 7.5c-1 0-2-.5-2-2s1-2 2-2 2 1 2 2-.9 2-2 2Z"/></svg>',
  },
  {
    key: 'bounty',
    label: 'Ferman kaydı',
    toast: 'Fermana imza attınız. Hedef teslim edilince payınız lonca kasasında.',
    icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>',
  },
];

function readerReactionStorageKey(articleId) {
  return `vs_article_reaction_${articleId}`;
}

function storedReaderReaction(articleId) {
  try {
    return localStorage.getItem(readerReactionStorageKey(articleId)) || '';
  } catch {
    return '';
  }
}

function publicAuthorRole(item) {
  const role = String(item?.author_role || '').trim();
  if (!role || /onay[ıi]\s*bekliyor|editör onayı/i.test(role)) {
    return '';
  }
  const submission = item?.stats?.submission;
  const slotKind = typeof submission === 'object' && submission ? submission.gazette_slot_kind : '';
  if (slotKind === 'flash' || item?.is_breaking) {
    return '';
  }
  return role;
}

function renderArticleEngagement(item) {
  if (!els.modalEngagement) {
    return;
  }
  const engagement = item.engagement || {};
  const reactions = item.reactions || {};
  const views = Number(item.views_count ?? engagement.views ?? 0);
  const shares = Number(engagement.shares ?? 0);
  const pngs = Number(engagement.png_downloads ?? 0);
  const chips = [];
  if (views > 0) {
    chips.push(`<span class="vs-modal__engagement-chip">${views} okunma</span>`);
  }
  if (shares > 0) {
    chips.push(`<span class="vs-modal__engagement-chip">${shares} paylaşım</span>`);
  }
  if (pngs > 0) {
    chips.push(`<span class="vs-modal__engagement-chip">${pngs} PNG</span>`);
  }
  ARTICLE_REACTIONS.forEach((cfg) => {
    const count = Number(reactions[cfg.key] ?? 0);
    if (count > 0) {
      chips.push(`<span class="vs-modal__engagement-chip vs-modal__engagement-chip--reaction vs-modal__engagement-chip--${cfg.key}">${count} ${escapeHtml(cfg.label.toLowerCase())}</span>`);
    }
  });
  if (!chips.length) {
    els.modalEngagement.hidden = true;
    els.modalEngagement.innerHTML = '';
    return;
  }
  els.modalEngagement.hidden = false;
  els.modalEngagement.innerHTML = chips.join('');
}

function renderArticleReactions(item, articleId) {
  if (!els.modalReactions) {
    return;
  }
  const reactions = item.reactions || {};
  const picked = storedReaderReaction(articleId);
  const buttons = ARTICLE_REACTIONS.map((cfg) => {
    const count = Number(reactions[cfg.key] ?? 0);
    const countHtml =
      count > 0 ? `<span class="vs-modal__reaction-count" aria-hidden="true">${count}</span>` : '';
    const active = picked === cfg.key ? ' is-active' : '';
    const disabled = picked && picked !== cfg.key ? ' disabled' : '';
    return `<button type="button" class="vs-modal__reaction vs-modal__reaction--${cfg.key}${active}" data-reaction="${cfg.key}" aria-pressed="${picked === cfg.key ? 'true' : 'false'}"${disabled} title="${escapeHtml(cfg.label)}">
      <span class="vs-modal__reaction-icon">${cfg.icon}</span>
      <span class="vs-modal__reaction-label">${escapeHtml(cfg.label)}</span>
      ${countHtml}
    </button>`;
  }).join('');
  els.modalReactions.innerHTML = buttons;
}

async function sendArticleReaction(articleId, reaction, itemSnapshot = {}) {
  const reactionKey = String(reaction || '')
    .trim()
    .toLowerCase();
  if (!articleId || !reactionKey) {
    return;
  }
  const cfg = ARTICLE_REACTIONS.find((row) => row.key === reactionKey);
  const prior = storedReaderReaction(articleId);
  if (prior) {
    showToast('Bu habere zaten bir tepki bıraktınız.', 'warning');
    return;
  }
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  try {
    let response = await fetch(`/api/public/news/${articleId}/reactions`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token || '',
      },
      body: JSON.stringify({ reaction: reactionKey }),
    });
    let payload = {};
    try {
      payload = await response.json();
    } catch {
      payload = {};
    }
    if (response.status === 404 || response.status === 405) {
      response = await fetch(`/api/public/news/${articleId}/engagement`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token || '',
        },
        body: JSON.stringify({ metric: reactionKey }),
      });
      try {
        payload = await response.json();
      } catch {
        payload = {};
      }
    }
    if (!response.ok || !payload.success) {
      const hint =
        response.status === 419
          ? 'Oturum süresi doldu; sayfayı yenileyip tekrar deneyin.'
          : payload.message || 'Tepki kaydedilemedi.';
      throw new Error(hint);
    }
    try {
      localStorage.setItem(readerReactionStorageKey(articleId), reactionKey);
    } catch {
      /* depolama yoksa yine de devam */
    }
    if (state.openArticleId === Number(articleId)) {
      const merged = {
        ...itemSnapshot,
        reactions: payload.reactions || {},
        engagement: { ...(itemSnapshot.engagement || {}), ...(payload.engagement || {}) },
      };
      renderArticleReactions(merged, articleId);
      renderArticleEngagement(merged);
    }
    updateStreamCardReactions(articleId, payload.reactions || {});
    showToast(cfg?.toast || 'Tepkiniz kaydedildi.');
  } catch (error) {
    showToast(error.message || 'Tepki kaydedilemedi.', 'error');
  }
}

async function openArticle(id, options = {}) {
  const gazetteReader =
    typeof options.gazetteReader === 'boolean' ? options.gazetteReader : isGazetteLayout();
  const slug = String(options.slug || articleSlugForOpen(id)).trim().toLowerCase();
  const slugOk = slug !== '' && /^[a-z0-9][a-z0-9-]*$/.test(slug);
  const fetchOpts = { headers: { Accept: 'application/json' }, cache: 'no-store' };
  let response = await fetch(
    slugOk ? apiUrl(`/api/public/news/by-slug/${encodeURIComponent(slug)}`) : apiUrl(`/api/public/news/${id}`),
    fetchOpts,
  );
  if (!response.ok && slugOk) {
    response = await fetch(apiUrl(`/api/public/news/${id}`), fetchOpts);
  } else if (!response.ok && !slugOk) {
    const fallbackSlug = articleSlugForOpen(id);
    if (fallbackSlug !== '') {
      response = await fetch(
        apiUrl(`/api/public/news/by-slug/${encodeURIComponent(fallbackSlug)}`),
        fetchOpts,
      );
    }
  }
  if (!response.ok) {
    showToast('Haber yüklenemedi.', 'error');
    return;
  }
  const payload = await response.json();
  const item = payload.news;
  const heroUrl = item.image_asset || '';
  const showHero =
    options.showHero === true
      ? Boolean(item.image_asset || item.image_url)
      : articleModalShowsHero(item, gazetteReader);
  if (showHero) {
    els.modalHero.style.backgroundImage = `url('${heroUrl}')`;
    els.modalHero.dataset.imageUrl = heroUrl;
    applyAdaptiveNewsImage(els.modalHero, heroUrl);
  } else {
    els.modalHero.style.backgroundImage = '';
    els.modalHero.dataset.imageUrl = '';
    els.modalHero.classList.remove('vs-media-fit--contain', 'vs-media-fit--cover-top');
  }
  els.modalTitle.textContent = item.title;

  if (els.modalBadges) {
    els.modalBadges.innerHTML = renderModalBadges(item);
  }
  const loc = String(item.location_badge || '').trim();
  const dateLine = articleModalDateLine(item);

  if (els.modalDate) {
    if (dateLine.html) {
      els.modalDate.innerHTML = dateLine.html;
    } else {
      els.modalDate.textContent = '';
    }
    els.modalDate.hidden = !dateLine.hasContent;
    els.modalDate.classList.toggle('vs-modal__date--has-location', Boolean(loc));
  }

  if (els.modalAuthor) {
    els.modalAuthor.textContent = item.author || 'Gazete Muhabiri';
  }
  if (els.modalAuthorRole) {
    const role = publicAuthorRole(item);
    els.modalAuthorRole.textContent = role;
    els.modalAuthorRole.hidden = !role;
  }

  if (els.modalLocation) {
    els.modalLocation.innerHTML = '';
    els.modalLocation.hidden = true;
    els.modalLocation.classList.remove('vs-modal__meta-item--location');
  }
  if (els.modalReadTime) {
    const readTime = item.read_time || '3 dk okuma';
    els.modalReadTime.textContent = readTime;
    els.modalReadTime.classList.add('vs-modal__meta-item--read');
  }

  const story = item.full_story || item.description;
  els.modalStory.innerHTML = renderStoryHtml(story);
  renderArticleReactions(item, id);
  renderArticleEngagement(item);

  if (item.testimony) {
    els.modalQuote.hidden = false;
    els.modalQuote.textContent = item.testimony;
  } else {
    els.modalQuote.hidden = true;
    els.modalQuote.textContent = '';
  }
  if (els.detailModal) {
    els.detailModal.classList.toggle('vs-modal-backdrop--gazette-read', gazetteReader);
    els.detailModal.classList.toggle('vs-modal-backdrop--web-read', !gazetteReader);
  }
  if (els.detailModalPanel) {
    els.detailModalPanel.classList.toggle('vs-modal--article-split', gazetteReader && showHero);
    els.detailModalPanel.classList.toggle('vs-modal--article-no-hero', !showHero);
  }
  if (els.modalHero) {
    els.modalHero.classList.toggle('vs-modal__hero--gazette-photo', gazetteReader && showHero);
    els.modalHero.hidden = !showHero;
  }
  if (els.modalAuthorRole) {
    els.modalAuthorRole.hidden = !els.modalAuthorRole.textContent;
  }
  if (els.modalReadTime) {
    els.modalReadTime.hidden = false;
  }
  document.body.classList.add('vs-article-modal-open');
  els.detailModal.classList.add('is-open');
  resetArticleModalDrag();
  resetArticleModalHeroPeek();
  initArticleModalSwipe();
  initArticleModalHeroScroll();
  state.openArticleId = Number(id) || null;
  state.openArticleSnapshot = item;
  if (!options.skipUrlSync) {
    syncBrowserUrl(item?.slug ? { articleSlug: item.slug } : {});
  }
}

function closeModal() {
  els.detailModal.classList.remove('is-open');
  resetArticleModalDrag();
  resetArticleModalHeroPeek();
  if (els.detailModal) {
    els.detailModal.classList.remove('vs-modal-backdrop--gazette-read', 'vs-modal-backdrop--web-read');
  }
  if (els.modalHero) {
    els.modalHero.classList.remove('vs-modal__hero--gazette-photo');
    els.modalHero.hidden = false;
  }
  if (els.detailModalPanel) {
    els.detailModalPanel.classList.remove('vs-modal--article-split', 'vs-modal--article-no-hero');
  }
  document.body.classList.remove('vs-article-modal-open');
  state.openArticleId = null;
  state.openArticleSnapshot = null;

  const mobileDeepLinkExit =
    isMobileViewport()
    && !gnomeCryCtrl?.isGnomePublication()
    && state.articleOpenedFromDeepLink;

  if (mobileDeepLinkExit) {
    state.articleOpenedFromDeepLink = false;
    enterMobileStreamAfterDeepLinkClose();
    return;
  }

  syncBrowserUrl({ skipArticle: true });
}

function showToast(message, variant = 'success') {
  if (!els.toast) {
    return;
  }
  const variants = ['success', 'warning', 'error'];
  const tone = variants.includes(variant) ? variant : 'success';
  els.toast.textContent = message;
  els.toast.classList.remove('vs-toast--success', 'vs-toast--warning', 'vs-toast--error', 'vs-toast--top');
  els.toast.classList.add(`vs-toast--${tone}`);
  if (tone === 'error') {
    els.toast.classList.add('vs-toast--top');
  }
  els.toast.classList.add('is-visible');
  const hideAfter = tone === 'error' ? 5200 : 4200;
  window.clearTimeout(showToast._hideTimer);
  showToast._hideTimer = window.setTimeout(() => {
    els.toast.classList.remove('is-visible');
  }, hideAfter);
}

async function copyArticleShareLink(articleId) {
  const id = articleId || state.openArticleId;
  if (!id) {
    return;
  }
  const url = buildShareUrl({
    articleSlug: state.openArticleSnapshot?.slug,
    includeArticle: true,
  }).href;
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(url);
    } else {
      const input = document.createElement('input');
      input.value = url;
      document.body.appendChild(input);
      input.select();
      document.execCommand('copy');
      input.remove();
    }
    showToast('Haber bağlantısı kopyalandı.');
  } catch {
    showToast('Bağlantı kopyalanamadı; adres çubuğundaki URL’yi kullanın.');
  }
}

async function recordArticleEngagement(articleId, metric) {
  if (!articleId) {
    return;
  }
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  try {
    await fetch(`/api/public/news/${articleId}/engagement`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token || '',
      },
      body: JSON.stringify({ metric }),
    });
  } catch {
    /* ölçüm başarısız olsa da kullanıcı akışı sürsün */
  }
}

async function shareArticle(articleId) {
  const id = articleId || state.openArticleId;
  if (!id) {
    return;
  }
  const url = buildShareUrl({
    articleSlug: state.openArticleSnapshot?.slug,
    includeArticle: true,
  }).href;
  const title = (els.modalTitle?.textContent || '').trim();
  try {
    if (navigator.share) {
      await navigator.share({
        title: title || 'Lağımdan Sesler',
        text: title || undefined,
        url,
      });
      await recordArticleEngagement(id, 'shares');
      return;
    }
  } catch (error) {
    if (error?.name === 'AbortError') {
      return;
    }
  }
  await copyArticleShareLink(id);
  await recordArticleEngagement(id, 'shares');
}

async function downloadArticleModalPng() {
  const panel = els.detailModalPanel;
  if (!panel) {
    showToast('PNG için haber yüklenemedi.');
    return;
  }
  if (els.modalPng) {
    els.modalPng.disabled = true;
    els.modalPng.setAttribute('aria-busy', 'true');
  }
  try {
    await document.fonts.ready;
    const html2canvas = await loadHtml2Canvas();
    const panelBounds = panel.getBoundingClientRect();
    const exportWidth = Math.ceil(panelBounds.width);
    const exportHeight = Math.ceil(panelBounds.height);
    const articleScrollTop = panel.querySelector('.vs-modal__body--article')?.scrollTop || 0;
    const canvas = await html2canvas(panel, {
      backgroundColor: null,
      scale: Math.min(2, window.devicePixelRatio || 1.5),
      useCORS: true,
      onclone: (clonedDocument) => {
        const clonedPanel = clonedDocument.querySelector('#newsDetailModalPanel');
        if (!clonedPanel) {
          return;
        }

        // Reader controls belong to the live UI, not the downloaded article.
        clonedPanel.querySelector('.vs-modal__chrome')?.remove();
        clonedPanel.querySelector('.vs-modal__grabber')?.remove();

        const clonedBackdrop = clonedDocument.querySelector('#newsDetailModal');
        if (clonedBackdrop) {
          clonedBackdrop.style.background = 'transparent';
          clonedBackdrop.style.backdropFilter = 'none';
          clonedBackdrop.style.webkitBackdropFilter = 'none';
        }

        // The gazette reader uses a transparent scrolling column over a
        // gradient panel. Give the export its own opaque parchment surface so
        // the full-screen backdrop cannot show through during rasterization.
        Object.assign(clonedPanel.style, {
          position: 'relative',
          top: '0',
          left: '0',
          margin: '0',
          transform: 'none',
          width: `${exportWidth}px`,
          height: `${exportHeight}px`,
          maxHeight: 'none',
          backgroundColor: '#e8d5b0',
          backgroundImage: 'none',
          opacity: '1',
          filter: 'none',
          mixBlendMode: 'normal',
        });
        clonedPanel.style.removeProperty('--vs-modal-drag-y');

        const clonedArticleBody = clonedPanel.querySelector('.vs-modal__body--article');
        if (clonedArticleBody) {
          clonedArticleBody.scrollTop = articleScrollTop;
          Object.assign(clonedArticleBody.style, {
            position: 'relative',
            zIndex: '1',
            backgroundColor: '#e8d5b0',
            backgroundImage: 'none',
            opacity: '1',
            filter: 'none',
            mixBlendMode: 'normal',
          });
        }
      },
    });
    const blob = await new Promise((resolve, reject) => {
      canvas.toBlob((result) => (result ? resolve(result) : reject(new Error('PNG oluşturulamadı'))), 'image/png');
    });
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    const safeTitle = (els.modalTitle?.textContent || 'haber').replace(/\s+/g, '-').slice(0, 48);
    link.href = objectUrl;
    link.download = `lagimdan-sesler-${safeTitle}.png`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
    await recordArticleEngagement(state.openArticleId, 'png_downloads');
  } catch (error) {
    console.error(error);
    showToast('PNG indirilemedi.');
  } finally {
    if (els.modalPng) {
      els.modalPng.disabled = false;
      els.modalPng.removeAttribute('aria-busy');
    }
  }
}

function printArticleModal() {
  document.body.classList.add('vs-printing-article');
  const cleanup = () => document.body.classList.remove('vs-printing-article');
  window.addEventListener('afterprint', cleanup, { once: true });
  window.print();
}

function resetArticleModalDrag() {
  if (els.detailModal) {
    els.detailModal.classList.remove('vs-modal-backdrop--dragging');
  }
  if (els.detailModalPanel) {
    els.detailModalPanel.style.removeProperty('--vs-modal-drag-y');
    els.detailModalPanel.style.transform = '';
    els.detailModalPanel.classList.remove('vs-modal--sheet-drag');
  }
}

function articleModalScrollRoot() {
  const panel = els.detailModalPanel;
  if (!panel) {
    return null;
  }
  if (isMobileGazetteLayout()) {
    return panel;
  }
  return panel.querySelector('.vs-modal__body--article');
}

function resetArticleModalHeroPeek() {
  const scrollRoot = articleModalScrollRoot();
  if (scrollRoot) {
    scrollRoot.scrollTop = 0;
  }
  els.modalHero?.classList.remove('vs-modal__hero--peek-hidden');
  els.detailModalPanel?.classList.remove('vs-modal--hero-hidden');
}

function initArticleModalHeroScroll() {
  const hero = els.modalHero;
  const panel = els.detailModalPanel;
  if (!hero || !panel || panel.dataset.heroScrollInit === '1') {
    return;
  }
  panel.dataset.heroScrollInit = '1';

  if (isMobileGazetteLayout()) {
    return;
  }

  const scrollRoot = panel.querySelector('.vs-modal__body--article');
  if (!scrollRoot) {
    return;
  }

  const onScroll = () => {
    if (!els.detailModal?.classList.contains('is-open') || hero.hidden) {
      return;
    }
    const hide = scrollRoot.scrollTop > 48;
    hero.classList.toggle('vs-modal__hero--peek-hidden', hide);
    panel.classList.toggle('vs-modal--hero-hidden', hide);
  };

  scrollRoot.addEventListener('scroll', onScroll, { passive: true });
}

function initArticleModalSwipe() {
  const panel = els.detailModalPanel;
  const backdrop = els.detailModal;
  const grabber = panel?.querySelector('.vs-modal__grabber');
  if (!panel || !backdrop || !grabber || panel.dataset.swipeInit === '1') {
    return;
  }
  panel.dataset.swipeInit = '1';
  let startY = 0;
  let dragging = false;

  const scrollRoot = () => articleModalScrollRoot();

  const onStart = (event) => {
    if (!isMobileGazetteLayout() || !backdrop.classList.contains('is-open')) {
      return;
    }
    const root = scrollRoot();
    if (root && root.scrollTop > 4) {
      return;
    }
    startY = event.touches[0].clientY;
    dragging = true;
    backdrop.classList.add('vs-modal-backdrop--dragging');
    panel.classList.add('vs-modal--sheet-drag');
  };

  const onMove = (event) => {
    if (!dragging) {
      return;
    }
    const dy = Math.max(0, event.touches[0].clientY - startY);
    panel.style.setProperty('--vs-modal-drag-y', `${dy}px`);
  };

  const onEnd = (event) => {
    if (!dragging) {
      return;
    }
    dragging = false;
    backdrop.classList.remove('vs-modal-backdrop--dragging');
    panel.classList.remove('vs-modal--sheet-drag');
    const endY = event.changedTouches?.[0]?.clientY ?? startY;
    const dy = Math.max(0, endY - startY);
    if (dy > 110) {
      closeModal();
    } else {
      resetArticleModalDrag();
    }
  };

  grabber.addEventListener('touchstart', onStart, { passive: true });
  grabber.addEventListener('touchmove', onMove, { passive: true });
  grabber.addEventListener('touchend', onEnd, { passive: true });
  grabber.addEventListener('touchcancel', onEnd, { passive: true });
}

function initGazetteFlipSwipe() {
  const root = els.gazetteFlip;
  if (!root || root.dataset.flipSwipeInit === '1') {
    return;
  }
  root.dataset.flipSwipeInit = '1';
  let startX = 0;
  let startY = 0;
  let tracking = false;

  const canSwipe = () =>
    isGazetteLayout() &&
    isMobileGazetteLayout() &&
    !isGlobalFeedMode() &&
    root &&
    !root.hidden;

  const onStart = (event) => {
    if (!canSwipe()) {
      return;
    }
    if (event.target.closest('input, textarea, select, button, a, [contenteditable="true"]')) {
      return;
    }
    if (els.detailModal?.classList.contains('is-open')) {
      return;
    }
    startX = event.touches[0].clientX;
    startY = event.touches[0].clientY;
    tracking = true;
  };

  const onEnd = (event) => {
    if (!tracking) {
      return;
    }
    tracking = false;
    if (!canSwipe()) {
      return;
    }
    const touch = event.changedTouches?.[0];
    if (!touch) {
      return;
    }
    const dx = touch.clientX - startX;
    const dy = touch.clientY - startY;
    const threshold = 52;
    if (Math.abs(dx) < threshold || Math.abs(dx) < Math.abs(dy) * 1.2) {
      return;
    }
    if (dx < 0 && !els.nextPage?.disabled) {
      goGazetteNext().catch(console.error);
    } else if (dx > 0 && !els.prevPage?.disabled) {
      goGazettePrev().catch(console.error);
    }
  };

  root.addEventListener('touchstart', onStart, { passive: true });
  root.addEventListener('touchend', onEnd, { passive: true });
  root.addEventListener('touchcancel', () => {
    tracking = false;
  });
}

async function submitHotExtra(formData) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const response = await fetch('/api/public/news/flash-submissions', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': token || '',
    },
    body: formData,
  });
  let payload = {};
  try {
    payload = await response.json();
  } catch {
    payload = {};
  }
  if (!response.ok || !payload.success) {
    const detail = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
    const err = new Error([payload.message, detail].filter(Boolean).join(' ') || 'Ek sayı basılamadı.');
    err.payload = payload;
    throw err;
  }
  return payload;
}

let rumorImageObjectUrl = null;

function flashPresetAssetUrl(path) {
  const clean = String(path || '').replace(/^\//, '');
  if (!clean || clean === '__gazette_auto__') {
    return '';
  }
  return `/${clean}`;
}

function updateSubmissionImagePreview(fileInput, presetInput, previewWrap, previewImage, previewHint, objectUrl) {
  if (objectUrl) {
    URL.revokeObjectURL(objectUrl);
  }
  if (!previewWrap || !previewImage) {
    return null;
  }

  const file = fileInput?.files?.[0];
  const preset = presetInput?.value || '__gazette_auto__';

  if (file && file.type.startsWith('image/')) {
    const nextObjectUrl = URL.createObjectURL(file);
    previewImage.src = nextObjectUrl;
    previewWrap.hidden = false;
    if (previewHint) {
      previewHint.textContent = 'Yüklediğiniz özel görsel';
      previewHint.hidden = false;
    }
    return nextObjectUrl;
  }

  if (preset && preset !== '__gazette_auto__') {
    previewImage.src = flashPresetAssetUrl(preset);
    previewWrap.hidden = false;
    if (previewHint) {
      const label = presetInput?.selectedOptions?.[0]?.textContent?.trim() || '';
      previewHint.textContent = label || 'Seçilen görsel';
      previewHint.hidden = !label;
    }
    return null;
  }

  previewImage.removeAttribute('src');
  previewWrap.hidden = true;
  if (previewHint) {
    previewHint.textContent = '';
    previewHint.hidden = true;
  }
  return null;
}

function updateRumorImagePreview() {
  rumorImageObjectUrl = updateSubmissionImagePreview(
    els.rumorCustomImageInput,
    els.rumorImagePreset,
    els.rumorImagePreviewWrap,
    els.rumorImagePreview,
    els.rumorImagePreviewHint,
    rumorImageObjectUrl,
  );
}

let gnomeImageObjectUrl = null;

function updateGnomeImagePreview() {
  gnomeImageObjectUrl = updateSubmissionImagePreview(
    els.gnomeCryCustomImage,
    els.gnomeCryImagePreset,
    els.gnomeCryImagePreviewWrap,
    els.gnomeCryImagePreview,
    els.gnomeCryImagePreviewHint,
    gnomeImageObjectUrl,
  );
}

async function submitRumor(formData) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const response = await fetch('/api/public/news/submissions', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': token || '',
    },
    body: formData,
  });
  let payload = {};
  try {
    payload = await response.json();
  } catch {
    payload = {};
  }
  if (!response.ok || !payload.success) {
    const detail = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
    const err = new Error([payload.message, detail].filter(Boolean).join(' ') || 'Gönderim başarısız.');
    err.payload = payload;
    throw err;
  }
  return payload;
}

function getSubmissionSlotConfig() {
  try {
    const raw = document.body.dataset.gazetteSubmissionSlots;
    return raw ? JSON.parse(raw) : {};
  } catch (e) {
    return {};
  }
}

function defaultCornerSlotsFromDom() {
  try {
    const raw = document.body.dataset.gazetteCornerSlots;
    const defs = raw ? JSON.parse(raw) : [];
    if (!Array.isArray(defs)) {
      return [];
    }
    return defs.map((s) => ({
      key: s.key || 'corner',
      title: s.title || s.key || 'Köşe',
      is_filled: false,
    }));
  } catch (e) {
    return [];
  }
}

function resolveGazetteSlotFromEl(fillEl) {
  const cornerKey = fillEl.dataset.cornerKey;
  if (cornerKey) {
    const slot = (state.currentIssue?.corner_slots || []).find((s) => s.key === cornerKey);
    const fromDom = fillEl.querySelector('.vs-gazette-corner__title')?.textContent?.trim();
    return {
      kind: 'corner',
      key: cornerKey,
      label: slot?.title || fromDom || cornerKey,
    };
  }
  const briefSlot = fillEl.dataset.briefSlot;
  if (briefSlot === 'lead' || fillEl.dataset.slotKind === 'lead') {
    return { kind: 'lead', key: 'lead', label: 'Manşet' };
  }
  return { kind: 'general', key: '', label: '' };
}

function initNewsTypePicker(groupEl, inputEl) {
  if (!groupEl || !inputEl) {
    return;
  }
  groupEl.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-type]');
    if (!btn || !groupEl.contains(btn)) {
      return;
    }
    groupEl.querySelectorAll('.vs-type-picker__btn').forEach((item) => {
      item.classList.remove('is-selected');
      item.setAttribute('aria-checked', 'false');
    });
    btn.classList.add('is-selected');
    btn.setAttribute('aria-checked', 'true');
    inputEl.value = btn.dataset.type;
  });
}

function bindEvents() {
  els.prevSlide.addEventListener('click', () => {
    if (!state.featured.length) return;
    state.sliderIndex = (state.sliderIndex - 1 + state.featured.length) % state.featured.length;
    updateSliderPosition();
    restartSliderAutoplay();
  });

  els.nextSlide.addEventListener('click', () => {
    if (!state.featured.length) return;
    state.sliderIndex = (state.sliderIndex + 1) % state.featured.length;
    updateSliderPosition();
    restartSliderAutoplay();
  });

  els.sliderDots.addEventListener('click', (event) => {
    const dot = event.target.closest('[data-dot]');
    if (!dot) return;
    state.sliderIndex = Number(dot.dataset.dot);
    updateSliderPosition();
    restartSliderAutoplay();
  });

  els.filterGroup.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-type]');
    if (!btn) return;
    state.type = btn.dataset.type;
    state.page = 1;
    if (window.matchMedia('(max-width: 767px)').matches && els.filterPanel && els.toggleFiltersBtn) {
      els.filterPanel.hidden = true;
      els.toggleFiltersBtn.setAttribute('aria-expanded', 'false');
      els.newsToolbar?.classList.remove('vs-toolbar--filters-open');
    }
    loadFeed().catch(console.error);
  });

  if (els.toggleFiltersBtn && els.filterPanel) {
    els.toggleFiltersBtn.addEventListener('click', () => {
      const expanded = els.toggleFiltersBtn.getAttribute('aria-expanded') === 'true';
      const next = !expanded;
      els.toggleFiltersBtn.setAttribute('aria-expanded', next ? 'true' : 'false');
      els.filterPanel.hidden = !next;
      if (isMobileGazetteLayout()) {
        els.newsToolbar?.classList.toggle('vs-toolbar--filters-open', next);
      }
    });
  }

  if (els.toggleViewModeBtn) {
    els.toggleViewModeBtn.addEventListener('click', () => {
      if (!isMobileGazetteLayout() || isGazetteLayout()) {
        return;
      }
      state.viewMode = state.viewMode === 'grid' ? 'list' : 'grid';
      try {
        localStorage.setItem('vs.gazette.viewMode', state.viewMode);
      } catch (e) {
        /* ignore */
      }
      loadFeed().catch(console.error);
    });
  }

  if (els.toggleStreamModeBtn) {
    els.toggleStreamModeBtn.addEventListener('click', () => {
      toggleStreamLayoutMode();
    });
  }
  if (els.toggleStreamNavBtn) {
    els.toggleStreamNavBtn.addEventListener('click', () => {
      toggleStreamLayoutMode();
    });
  }

  if (els.newsStream) {
    els.newsStream.addEventListener('click', (event) => {
      const reactBtn = event.target.closest('[data-stream-reaction]');
      if (reactBtn && !reactBtn.disabled) {
        event.preventDefault();
        const id = reactBtn.dataset.streamId;
        const reaction = reactBtn.dataset.streamReaction;
        const item = state.streamItems.find((row) => Number(row.id) === Number(id));
        sendArticleReaction(id, reaction, item || {}).catch(console.error);
        return;
      }
      const imgOpen = event.target.closest('[data-stream-image-open]');
      if (imgOpen) {
        event.preventDefault();
        openArticleFromUi(imgOpen.dataset.streamImageOpen, imgOpen.dataset.articleSlug, {
          showHero: true,
        }).catch(console.error);
        return;
      }
      const openBtn = event.target.closest('[data-open-id]');
      if (openBtn?.dataset.openId) {
        event.preventDefault();
        openArticleFromUi(openBtn.dataset.openId, openBtn.dataset.articleSlug).catch(console.error);
      }
    });
  }

  if (els.gazetteArchiveSelect) {
    els.gazetteArchiveSelect.addEventListener('change', () => {
      const raw = els.gazetteArchiveSelect.value;
      if (raw === '') {
        return;
      }
      const value = Number(raw);
      if (!Number.isFinite(value) || value <= 0) {
        return;
      }
      if (isGlobalFeedMode()) {
        state.search = '';
        state.type = 'all';
        if (els.searchInput) {
          els.searchInput.value = '';
        }
      }
      state.issueId = value;
      state.page = 1;
      loadFeed().catch(console.error);
    });
  }

  if (els.gazetteStage) {
    els.gazetteStage.addEventListener('click', (event) => {
      const gotoBtn = event.target.closest('[data-goto-page]');
      if (gotoBtn && isGazetteLayout()) {
        event.preventDefault();
        event.stopPropagation();
        const target = Number(gotoBtn.dataset.gotoPage);
        if (Number.isFinite(target) && target >= 1) {
          const dir = target < state.page ? 'back' : 'fwd';
          goToFeedPage(target, dir);
        }
        return;
      }
      const fill = event.target.closest('.vs-gazette-slot--fill');
      if (!fill || !isGazetteLayout() || !issueAllowsSubmissions()) {
        return;
      }
      event.preventDefault();
      const slot = resolveGazetteSlotFromEl(fill);
      rumorWizard?.openFromSlot(slot);
      const toastByKind = {
        lead: 'Manşet metnini gönderin. Onaydan sonra kapakta yer alır.',
        general: 'Haberi gönderin. Onaydan sonra kapak gündeminde özet olarak yer alır.',
        corner: 'Köşe yazınızı gönderin. Onaydan sonra seçtiğiniz köşede basılır.',
      };
      showToast(toastByKind[slot.kind] || 'Metni gönderin. Editör onayından sonra bu sayıya işlenir.');
    });
  }

  if (els.printGazetteBtn) {
    els.printGazetteBtn.addEventListener('click', () => {
      const gnome = gnomeCryCtrl?.isGnomePublication();
      if (!isGazetteLayout() && !gnome) {
        return;
      }
      document.body.classList.add(gnome ? 'vs-printing-gnome' : 'vs-printing');
      const cleanup = () => document.body.classList.remove('vs-printing', 'vs-printing-gnome');
      window.addEventListener('afterprint', cleanup, { once: true });
      window.print();
    });
  }

  if (els.downloadGazettePngBtn) {
    els.downloadGazettePngBtn.addEventListener('click', downloadGazettePagePng);
  }

  let mediaFitTimer;
  window.addEventListener('resize', () => {
    placeIssueArchiveSelect();
    syncSearchResultsLayout();
    syncFilterPanelVisibility();
    if (enforceMobileLagimStreamMode({ syncUrl: true })) {
      applyLayoutMode();
      loadFeed().catch(console.error);
    }
    clearTimeout(mediaFitTimer);
    mediaFitTimer = setTimeout(() => fitAllNewsMedia(document), 120);
  });

  let searchTimer;
  els.searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = els.searchInput.value.trim();
      state.page = 1;
      if (gnomeCryCtrl?.isGnomePublication()) {
        gnomeCryCtrl.applySearch(state.search);
        return;
      }
      loadFeed().catch(console.error);
    }, 350);
  });

  els.prevPage.addEventListener('click', () => {
    goGazettePrev().catch(console.error);
  });

  els.nextPage.addEventListener('click', () => {
    goGazetteNext().catch(console.error);
  });

  if (els.gazetteFlipPrev) {
    els.gazetteFlipPrev.addEventListener('click', () => {
      goGazettePrev().catch(console.error);
    });
  }
  if (els.gazetteFlipNext) {
    els.gazetteFlipNext.addEventListener('click', () => {
      goGazetteNext().catch(console.error);
    });
  }

  document.body.addEventListener('click', (event) => {
    const homeBtn = event.target.closest('[data-gazette-home]');
    if (homeBtn) {
      event.preventDefault();
      goGazetteHome();
      return;
    }
    const cornerArticle = event.target.closest('.vs-gazette-corner-rail__article--open');
    if (cornerArticle?.dataset.openId && isGazetteLayout() && els.gazetteStage?.contains(cornerArticle)) {
      event.preventDefault();
      openArticle(cornerArticle.dataset.openId, { gazetteReader: true }).catch(console.error);
      return;
    }
    const cornerOpen = event.target.closest('.vs-gazette-corner--open, .vs-gazette-corner-rail--open');
    if (cornerOpen && isGazetteLayout() && els.gazetteStage?.contains(cornerOpen)) {
      event.preventDefault();
      const articleId = cornerOpen.dataset.openId;
      if (articleId) {
        openArticle(articleId, { gazetteReader: true }).catch(console.error);
        return;
      }
      const cornerKey = cornerOpen.dataset.cornerKey;
      if (cornerKey) {
        openCornerReader(cornerKey).catch(console.error);
        return;
      }
    }
    const streamPost = event.target.closest('.vs-stream-post');
  const streamInLagim = streamPost && isStreamLayout() && els.newsStream?.contains(streamPost);
  const streamInGnome =
    streamPost
    && gnomeCryCtrl?.isGnomePublication()
    && isMobileViewport()
    && els.gnomeCryFlow?.contains(streamPost);
    if (
      streamPost &&
      (streamInLagim || streamInGnome) &&
      !event.target.closest(
        '.vs-stream-post__reactions, .vs-stream-post__reaction, .vs-stream-post__read-actions, [data-stream-image-open]',
      )
    ) {
      event.preventDefault();
      openArticleFromUi(streamPost.dataset.openId, streamPost.dataset.articleSlug).catch(console.error);
      return;
    }
    const openBtn = event.target.closest('[data-open-id]');
    if (!openBtn) {
      return;
    }
    if (
      gnomeCryCtrl?.isGnomePublication()
      && (els.gnomeCryStage?.contains(openBtn) || els.gnomeCryFlow?.contains(openBtn))
    ) {
      event.preventDefault();
      openArticleFromUi(openBtn.dataset.openId, openBtn.dataset.articleSlug).catch(console.error);
      return;
    }
    if (
      (isStreamLayout() && els.newsStream?.contains(openBtn))
      || (gnomeCryCtrl?.isGnomePublication() && isMobileViewport() && els.gnomeCryFlow?.contains(openBtn))
    ) {
      event.preventDefault();
      openArticleFromUi(openBtn.dataset.openId, openBtn.dataset.articleSlug).catch(console.error);
      return;
    }
    const inGazette = isGazetteLayout();
    if (inGazette) {
      const fromSearchGrid = isGlobalFeedMode() && els.newsGrid?.contains(openBtn);
      if (!els.gazetteStage?.contains(openBtn) && !fromSearchGrid) {
        return;
      }
      if (event.target.closest('.vs-gazette-slot--fill')) {
        return;
      }
      if (event.target.closest('[data-goto-page]')) {
        return;
      }
    }
    event.preventDefault();
    openArticleFromUi(openBtn.dataset.openId, openBtn.dataset.articleSlug).catch(console.error);
  });

  document.body.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }
    const cornerArticle = event.target.closest('.vs-gazette-corner-rail__article--open');
    if (cornerArticle?.dataset.openId && isGazetteLayout()) {
      event.preventDefault();
      openArticle(cornerArticle.dataset.openId, { gazetteReader: true }).catch(console.error);
      return;
    }
    const cornerRail = event.target.closest('.vs-gazette-corner-rail--open, .vs-gazette-corner--open');
    if (!cornerRail || !isGazetteLayout()) {
      return;
    }
    event.preventDefault();
    if (cornerRail.dataset.openId) {
      openArticle(cornerRail.dataset.openId, { gazetteReader: true }).catch(console.error);
      return;
    }
    if (cornerRail.dataset.cornerKey) {
      openCornerReader(cornerRail.dataset.cornerKey).catch(console.error);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (!isGazetteLayout() && !gnomeCryCtrl?.isGnomeDesktopSheet?.()) {
      return;
    }
    if (gnomeCryCtrl?.isGnomePublication() && !gnomeCryCtrl.isGnomeDesktopSheet()) {
      return;
    }
    if (event.target.closest('input, textarea, select, [contenteditable="true"]')) {
      return;
    }
    if (event.key === 'ArrowLeft' && !els.prevPage.disabled) {
      event.preventDefault();
      goGazettePrev().catch(console.error);
    }
    if (event.key === 'ArrowRight' && !els.nextPage.disabled) {
      event.preventDefault();
      goGazetteNext().catch(console.error);
    }
  });

  let gnomeViewportTimer;
  window.addEventListener('resize', () => {
    if (!gnomeCryCtrl?.isGnomePublication()) {
      return;
    }
    clearTimeout(gnomeViewportTimer);
    gnomeViewportTimer = setTimeout(() => {
      gnomeCryCtrl.onViewportChange?.();
    }, 160);
  });

  window.addEventListener('popstate', () => {
    state.suppressUrlSync = true;
    readUrlIntoState();
    enforceMobileLagimStreamMode({ syncUrl: true });
    if (els.searchInput && state.search !== els.searchInput.value.trim()) {
      els.searchInput.value = state.search;
    }
    applyLayoutMode();
    loadFeed()
      .catch(console.error)
      .finally(() => {
        state.suppressUrlSync = false;
      });
  });

  els.modalClose.addEventListener('click', closeModal);
  if (els.modalShare) {
    els.modalShare.addEventListener('click', () => {
      shareArticle(state.openArticleId).catch(console.error);
    });
  }
  if (els.modalPdf) {
    els.modalPdf.addEventListener('click', printArticleModal);
  }
  if (els.modalPng) {
    els.modalPng.addEventListener('click', () => {
      downloadArticleModalPng().catch(console.error);
    });
  }
  els.modalReactions?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-reaction]');
    if (!btn || btn.disabled) {
      return;
    }
    const id = state.openArticleId;
    if (!id) {
      return;
    }
    sendArticleReaction(id, btn.dataset.reaction, state.openArticleSnapshot || {}).catch(console.error);
  });
  els.detailModal.addEventListener('click', (event) => {
    if (event.target === els.detailModal) {
      closeModal();
    }
  });

  if (els.rumorImagePreset && els.rumorCustomImageInput) {
    els.rumorImagePreset.addEventListener('change', () => {
      els.rumorCustomImageInput.value = '';
      updateRumorImagePreview();
    });
    els.rumorCustomImageInput.addEventListener('change', () => {
      if (els.rumorCustomImageInput.files?.length) {
        els.rumorImagePreset.value = '__gazette_auto__';
      }
      updateRumorImagePreview();
    });
  }

}

document.addEventListener('DOMContentLoaded', () => {
  els.sliderTrack = qs('newsSliderTrack');
  els.sliderDots = qs('newsSliderDots');
  els.prevSlide = qs('newsSliderPrev');
  els.nextSlide = qs('newsSliderNext');
  els.filterGroup = qs('newsFilterGroup');
  els.filterPanel = qs('newsFilterPanel');
  els.newsToolbar = qs('newsToolbar');
  els.toggleFiltersBtn = qs('toggleFiltersBtn');
  els.toggleFiltersLabel = qs('toggleFiltersLabel');
  els.toggleViewModeBtn = qs('toggleViewModeBtn');
  els.toggleViewModeLabel = qs('toggleViewModeLabel');
  els.searchInput = qs('newsSearchInput');
  els.newsGrid = qs('newsGrid');
  els.prevPage = qs('newsPrevPage');
  els.nextPage = qs('newsNextPage');
  els.pageSheet = qs('newsPageSheet');
  els.pageSheetShort = qs('newsPageSheetShort');
  els.detailModal = qs('newsDetailModal');
  els.detailModalPanel = qs('newsDetailModalPanel');
  els.modalHero = qs('newsModalHero');
  els.modalTitle = qs('newsModalTitle');
  els.modalBadges = qs('newsModalBadges');
  els.modalDate = qs('newsModalDate');
  els.modalAuthor = qs('newsModalAuthor');
  els.modalAuthorRole = qs('newsModalAuthorRole');
  els.modalLocation = qs('newsModalLocation');
  els.modalReadTime = qs('newsModalReadTime');
  els.modalStory = qs('newsModalStory');
  els.modalQuote = qs('newsModalQuote');
  els.modalClose = qs('newsModalClose');
  els.modalShare = qs('newsModalShare');
  els.modalPdf = qs('newsModalPdf');
  els.modalPng = qs('newsModalPng');
  els.modalEngagement = qs('newsModalEngagement');
  els.modalReactions = qs('newsModalReactions');
  els.openRumorBtn = qs('openRumorModal');
  els.rumorWizardPick = qs('rumorWizardPick');
  els.rumorWizardForm = qs('rumorWizardForm');
  els.rumorWizardKinds = qs('rumorWizardKinds');
  els.rumorWizardBack = qs('rumorWizardBack');
  els.rumorDemoPreview = qs('rumorDemoPreview');
  els.rumorTypeFieldWrap = qs('rumorTypeFieldWrap');
  els.rumorLocationWrap = qs('rumorLocationWrap');
  els.rumorLocationInput = qs('rumorLocationInput');
  els.rumorImageSection = qs('rumorImageSection');
  els.rumorRemoveImage = qs('rumorRemoveImage');
  els.rumorAnonymousWrap = qs('rumorAnonymousWrap');
  els.rumorAuthorWrap = qs('rumorAuthorWrap');
  els.rumorAuthorInput = qs('rumorAuthorInput');
  els.rumorTitleWrap = qs('rumorTitleWrap');
  els.rumorBodyWrap = qs('rumorBodyWrap');
  els.rumorCornerCountSummary = qs('rumorCornerCountSummary');
  els.rumorModal = qs('rumorModal');
  els.rumorModalPanel = qs('rumorModalPanel');
  els.rumorModalBody = els.rumorModalPanel?.querySelector('.vs-modal__body--rumor') || null;
  els.rumorClose = qs('rumorModalClose');
  els.rumorForm = qs('rumorForm');
  els.rumorSubmit = qs('rumorSubmit');
  els.toast = qs('newsToast');
  els.rumorTypePicker = qs('rumorTypePicker');
  els.rumorNewsType = qs('rumorNewsType');
  els.rumorModalTitle = qs('rumorModalTitle');
  els.rumorModalIntro = qs('rumorModalIntro');
  els.rumorGazetteSlotKind = qs('rumorGazetteSlotKind');
  els.rumorGazetteSlotKey = qs('rumorGazetteSlotKey');
  els.rumorGazetteSlotLabelHidden = qs('rumorGazetteSlotLabelHidden');
  els.rumorGazetteIssueId = qs('rumorGazetteIssueId');
  els.rumorIssuePicker = qs('rumorIssuePicker');
  els.rumorIssuePickWrap = qs('rumorIssuePickWrap');
  els.rumorTitleInput = qs('rumorTitleInput');
  els.rumorBodyLabel = qs('rumorBodyLabel');
  els.rumorBodyInput = qs('rumorBodyInput');
  els.rumorImagePreset = qs('rumorImagePreset');
  els.rumorCustomImageInput = qs('rumorCustomImageInput');
  els.rumorImagePreviewWrap = qs('rumorImagePreviewWrap');
  els.rumorImagePreview = qs('rumorImagePreview');
  els.rumorImagePreviewHint = qs('rumorImagePreviewHint');
  els.rumorRumuzFieldWrap = qs('rumorRumuzFieldWrap');
  els.rumorMetaGrid = qs('rumorMetaGrid');
  els.rumorRumuzInput = qs('rumorRumuzInput');
  els.rumorCornerPicker = qs('rumorCornerPicker');
  els.rumorCornerPickerGrid = qs('rumorCornerPickerGrid');
  els.rumorAnonymousInput = qs('rumorAnonymousInput');
  els.rumorAnonymousToggle = qs('rumorAnonymousToggle');
  els.gazetteFlip = qs('gazetteFlip');
  els.gazetteFlipPrev = qs('gazetteFlipPrev');
  els.gazetteFlipNext = qs('gazetteFlipNext');
  els.newsStream = qs('newsStream');
  els.toggleStreamModeBtn = qs('toggleStreamModeBtn');
  els.toggleStreamModeLabel = qs('toggleStreamModeLabel');
  els.toggleStreamNavBtn = qs('toggleStreamNavBtn');
  els.toggleStreamNavLabel = qs('toggleStreamNavLabel');
  els.gazetteStage = qs('gazetteStage');
  els.newsSliderSection = document.querySelector('.vs-news-slider');
  els.togglePublicationToolbarBtn = qs('togglePublicationToolbarBtn');
  els.printGazetteBtn = qs('printGazetteBtn');
  els.downloadGazettePngBtn = qs('downloadGazettePngBtn');
  els.pagination = qs('newsPagination');
  els.pageHint = qs('newsPageHint');
  els.prevPageLabel = qs('newsPrevPageLabel');
  els.nextPageLabel = qs('newsNextPageLabel');
  els.gazetteArchiveSelect = qs('gazetteArchiveSelect');
  els.gazetteArchiveWrap = qs('gazetteArchiveWrap');
  els.headerIssueSlot = qs('gazetteHeaderIssue');
  els.toolbarFiltersRow = qs('toolbarFiltersRow');
  els.toolbarBarEnd = qs('toolbarBarEnd');
  els.mobileIssueDock = qs('mobileIssueDock');
  els.publicationLagimBtn = qs('publicationLagimBtn');
  els.publicationGnomeBtn = qs('publicationGnomeBtn');
  els.gnomeCryStage = qs('gnomeCryStage');
  els.gnomeCryFlow = qs('gnomeCryFlow');
  els.openGnomeCryModal = qs('openGnomeCryModal');
  els.gnomeCryModalBackdrop = qs('gnomeCryModalBackdrop');
  els.gnomeCryModalClose = qs('gnomeCryModalClose');
  els.gnomeCryForm = qs('gnomeCryForm');
  els.gnomeCryModalTitle = qs('gnomeCryModalTitle');
  els.gnomeCryModalIntro = qs('gnomeCryModalIntro');
  els.gnomeCryTitle = qs('gnomeCryTitle');
  els.gnomeCryDeck = qs('gnomeCryDeck');
  els.gnomeCryRumuz = qs('gnomeCryRumuz');
  els.gnomeCryEditor = qs('gnomeCryEditor');
  els.gnomeCryEditorToolbar = qs('gnomeCryEditorToolbar');
  els.gnomeCryEditorHost = document.querySelector('#gnomeCryForm .vs-gnome-cry-editor-host');
  els.gnomeCryFullStory = qs('gnomeCryFullStory');
  els.gnomeCryBodyError = qs('gnomeCryBodyError');
  els.gnomeCryCustomImage = qs('gnomeCryCustomImage');
  els.gnomeCryImagePreset = qs('gnomeCryImagePreset');
  els.gnomeCryImagePreviewHint = qs('gnomeCryImagePreviewHint');
  els.gnomeCrySlotKind = qs('gnomeCrySlotKind');
  els.gnomeCrySlotMainBtn = qs('gnomeCrySlotMainBtn');
  els.gnomeCrySlotCornerBtn = qs('gnomeCrySlotCornerBtn');
  els.gnomeCryImagePreviewWrap = qs('gnomeCryImagePreviewWrap');
  els.gnomeCryImagePreview = qs('gnomeCryImagePreview');
  els.gnomeCryRemoveImage = qs('gnomeCryRemoveImage');
  els.gnomeCrySubmit = qs('gnomeCrySubmit');
  els.gnomeSealLightbox = qs('gnomeSealLightbox');
  els.gnomeSealLightboxImg = qs('gnomeSealLightboxImg');
  els.gnomeSealLightboxClose = qs('gnomeSealLightboxClose');

  initNewsTypePicker(els.rumorTypePicker, els.rumorNewsType);
  gnomeCryCtrl = createGnomeCryController({
    els,
    state,
    escapeHtml,
    showToast,
    loadFeed,
    applyLayoutMode,
    renderStreamCard,
    rememberLagimLayoutBeforeGnome,
    restoreLagimLayoutAfterGnome,
    updateGnomeImagePreview,
    openArticleFromUi,
  });
  gnomeCryCtrl.bind();
  rumorWizard = createRumorWizard({
    els,
    state,
    escapeHtml,
    showToast,
    submitRumor,
    submitHotExtra,
    fetchIssueArchive,
    getSubmissionSlotConfig,
    defaultCornerSlotsFromDom,
    issueAllowsSubmissions,
    isGazetteLayout,
    updateRumorImagePreview,
    updateGnomeImagePreview,
  });
  rumorWizard.bind();
  readUrlIntoState();
  if (els.searchInput && state.search) {
    els.searchInput.value = state.search;
  }
  const urlParams = new URLSearchParams(window.location.search);
  const articleDeepLink =
    Boolean(state.pendingArticleSlug || state.pendingArticleId)
    || Boolean(articleSlugFromPathname());
  const urlHasGazetteHints =
    urlParams.has('gorunum')
    || Boolean(parseGazettePath(window.location.pathname))
    || urlParams.has('yaprak')
    || urlParams.has('sayi')
    || urlParams.has('haber')
    || articleDeepLink;
  const onGazetteHost = document.body?.dataset?.gazetteSubdomain === '1';
  const layoutParam = urlParams.get('gorunum');
  const explicitLayout = layoutParam === 'web' || layoutParam === 'gazette' || layoutParam === 'stream';
  const normalizeLayout = (mode) => (mode === 'web' ? 'gazette' : mode);
  const mobileDefaultStream =
    !explicitLayout
    && !urlHasGazetteHints
    && !articleDeepLink
    && state.publicationMode !== PUBLICATION_GNOME
    && isMobileViewport();

  if (shouldForceMobileLagimStream()) {
    enforceMobileLagimStreamMode();
  } else if (layoutParam === 'stream') {
    state.layoutMode = 'stream';
    if (!state.streamReturnLayout) {
      state.streamReturnLayout = 'gazette';
    }
  } else if (articleDeepLink && layoutParam !== 'stream') {
    state.layoutMode = 'gazette';
  } else if (mobileDefaultStream) {
    state.layoutMode = mobileLagimLayoutFromPreference('stream');
    if (state.layoutMode === 'stream' && !state.streamReturnLayout) {
      state.streamReturnLayout = 'gazette';
    }
  } else if (onGazetteHost && layoutParam !== 'stream' && !isMobileViewport()) {
    state.layoutMode = 'gazette';
  } else if (layoutParam === 'web' || layoutParam === 'gazette') {
    state.layoutMode = 'gazette';
  }
  try {
    const savedLayout = localStorage.getItem('vs.gazette.layoutMode');
    if (
      !shouldForceMobileLagimStream()
      && !onGazetteHost
      && !urlHasGazetteHints
      && !articleDeepLink
      && (savedLayout === 'gazette' || savedLayout === 'web' || savedLayout === 'stream')
    ) {
      state.layoutMode = normalizeLayout(savedLayout);
    }
    if (isMobileGazetteLayout()) {
      const savedView = localStorage.getItem('vs.gazette.viewMode');
      if (savedView === 'list' || savedView === 'grid') {
        state.viewMode = savedView;
      }
    } else {
      state.viewMode = 'grid';
    }
  } catch (e) {
    /* ignore */
  }
  if (state.layoutMode === 'stream' && !state.streamReturnLayout) {
    state.streamReturnLayout = 'gazette';
  }
  state.layoutMode = normalizeLayout(state.layoutMode);
  if (state.streamReturnLayout) {
    state.streamReturnLayout = normalizeLayout(state.streamReturnLayout);
  }
  enforceMobileLagimStreamMode({ syncUrl: true });
  gnomeCryCtrl.syncPublicationChrome();
  applyLayoutMode();
  syncFilterPanelVisibility();
  bindEvents();
  initGazetteFlipSwipe();
  initArticleModalHeroScroll();
  if (state.pendingFlashModal) {
    state.pendingFlashModal = false;
    rumorWizard?.openFlow('flash');
  }
  loadFeed().catch((error) => {
    els.newsGrid.innerHTML = `<div class="vs-loading">${error.message}</div>`;
  });
});
