import html2canvas from 'html2canvas';

const WEB_ITEMS_PER_PAGE = 9;
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
  suppressUrlSync: false,
  articleBootstrapInFlight: false,
  openArticleId: null,
  coverMastIndex: 0,
  coverMastTimer: null,
};

const els = {};

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

function resolvePendingIssueNumber() {
  if (!state.pendingIssueNumber) {
    return false;
  }
  const target = Number(state.pendingIssueNumber);
  const row = (state.issueArchive || []).find((issue) => Number(issue.issue_number) === target);
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

function readUrlIntoState() {
  const params = new URLSearchParams(window.location.search);
  const gorunum = params.get('gorunum');
  if (gorunum === 'web' || gorunum === 'gazette') {
    state.layoutMode = gorunum === 'gazette' ? 'gazette' : 'web';
  } else if (params.has('yaprak') || params.has('sayi') || params.has('haber')) {
    state.layoutMode = 'gazette';
  }

  const yaprak = Number.parseInt(params.get('yaprak') || '', 10);
  if (Number.isFinite(yaprak) && yaprak >= 1) {
    state.page = yaprak;
  }
  const sayfa = Number.parseInt(params.get('sayfa') || '', 10);
  if (!params.has('yaprak') && Number.isFinite(sayfa) && sayfa >= 1) {
    state.page = sayfa;
  }

  const sayi = Number.parseInt(params.get('sayi') || '', 10);
  if (Number.isFinite(sayi) && sayi >= 1) {
    state.pendingIssueNumber = sayi;
  }

  const haber = Number.parseInt(params.get('haber') || '', 10);
  if (Number.isFinite(haber) && haber > 0) {
    state.pendingArticleId = haber;
    state.layoutMode = 'gazette';
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
}

function buildShareUrl({ articleId = null, includeArticle = false } = {}) {
  const url = new URL(window.location.pathname, window.location.origin);
  const layout = state.layoutMode;
  if (layout === 'gazette') {
    url.searchParams.set('gorunum', 'gazette');
    const issue = state.currentIssue || {};
    const issueNum = issue.issue_number ?? inWorldCalendar().issueNumber;
    url.searchParams.set('sayi', String(issueNum));
    url.searchParams.set('yaprak', String(state.page));
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
  const id = articleId ?? (includeArticle ? state.openArticleId : null);
  if (id) {
    url.searchParams.set('haber', String(id));
  }
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

async function fetchBootstrap() {
  const params = {
    type: state.type === 'all' ? '' : state.type,
    q: state.search,
  };
  const useGazetteApi = isGazetteLayout() && !isSearchActive();
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
    track.innerHTML = '<div class="vs-loading">Henüz manşet haberi yok.</div>';
    els.sliderDots.innerHTML = '';
    return;
  }

  state.featured.forEach((item, index) => {
    const slide = document.createElement('article');
    slide.className = 'vs-news-slide';
    const imageUrl = item.image_asset || '';
    slide.style.backgroundImage = imageUrl ? `url('${imageUrl}')` : '';
    slide.dataset.imageUrl = imageUrl;
    slide.dataset.index = String(index);
    const typeLabel = escapeHtml(item.news_type_label || item.category || '');
    const title = escapeHtml(item.title || '');
    const desc = escapeHtml(item.description || '');
    const dateText = escapeHtml(displayDateText(item));
    slide.innerHTML = `
      <div class="vs-news-slide__overlay"></div>
      <div class="vs-news-slide__content">
        <div class="vs-news-slide__meta">
          <span class="vs-badge" style="border-color:${badgeClass(item.badge_color)}">${typeLabel}</span>
          ${dateText ? `<span class="vs-news-slide__date">${dateText}</span>` : ''}
        </div>
        <h2 class="vs-news-slide__title">${title}</h2>
        <p class="vs-news-slide__desc">${desc}</p>
        <button type="button" class="vs-filter-btn vs-news-slide__cta" data-open-id="${item.id}">Haberi Oku</button>
      </div>
    `;
    track.appendChild(slide);
    applyAdaptiveNewsImage(slide, imageUrl);
  });

  els.sliderDots.innerHTML = state.featured
    .map((_, i) => `<button type="button" class="vs-news-slider__dot${i === 0 ? ' is-active' : ''}" data-dot="${i}" aria-label="Slayt ${i + 1}"></button>`)
    .join('');

  updateSliderPosition();
  restartSliderAutoplay();
  requestAnimationFrame(() => fitAllNewsMedia(track));
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

function isMobileGazetteLayout() {
  return window.matchMedia('(max-width: 767px)').matches;
}

function isGazetteLayout() {
  return state.layoutMode === 'gazette';
}

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
      gazetteScrub(body.dataset.gazetteIssueDate || 'Leos ayının 1. günü, Vonyas · 631'),
    issueNumber: body.dataset.gazetteIssueNumber || '1',
    issueLabel: body.dataset.gazetteIssueLabel || 'İlk sayı',
    month: body.dataset.gazetteIssueMonth || 'Leos',
    day: body.dataset.gazetteIssueDay || '1',
    year: body.dataset.gazetteIssueYear || '631',
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
  const searchActive = isSearchActive();
  const placeholder = searchActive
    ? '<option value="" selected>Sayı seçin… (şu an tüm sayılarda arama)</option>'
    : '';
  const options = (state.issueArchive || []).map((issue) => {
    const selected =
      !searchActive &&
      (Number(state.issueId) === Number(issue.id) || (!state.issueId && issue.is_current));
    const disabled = !issue.is_selectable;
    return `<option value="${issue.id}"${selected ? ' selected' : ''}${disabled ? ' disabled' : ''}>${escapeHtml(issueArchiveOptionText(issue))}</option>`;
  });
  const list = placeholder + (options.join('') || '<option value="">Sayı yok</option>');
  els.gazetteArchiveSelect.innerHTML = list;
  if (!searchActive && !state.issueId && state.currentIssue?.id) {
    state.issueId = state.currentIssue.id;
  }
}

function cornerSlotFirstInnerPage(slotIndex) {
  return Math.max(2, Number(slotIndex) + 2);
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

  const byline = rumuz || (slot.anonymous ? 'Anonim Gözcü' : '');
  const newsId = slot.news_id != null ? escapeHtml(String(slot.news_id)) : '';
  const openAttrs = newsId ? ` data-open-id="${newsId}"` : ` data-corner-key="${key}"`;

  return `
    <button type="button" class="vs-gazette-corner vs-gazette-corner--teaser vs-gazette-corner--open"${openAttrs}>
      <span class="vs-gazette-corner__title">${title}</span>
      ${byline ? `<span class="vs-gazette-corner__rumuz">${escapeHtml(byline)}</span>` : ''}
      <p class="vs-gazette-corner__teaser">${escapeHtml(teaser)}</p>
      <span class="vs-gazette-corner__jump">Oku · ${innerPage}. yaprakta da var</span>
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

  const paras = splitParagraphs(body)
    .map((p) => `<p class="vs-gazette-corner-rail__p">${escapeHtml(p)}</p>`)
    .join('');

  const newsId = slot.news_id != null ? escapeHtml(String(slot.news_id)) : '';
  const openAttrs = newsId ? ` data-open-id="${newsId}"` : ` data-corner-key="${key}"`;

  return `
    <aside class="vs-gazette-corner-rail vs-gazette-corner-rail--open" aria-label="${title}"${openAttrs} role="button" tabindex="0">
      <h3 class="vs-gazette-corner-rail__title">${title}</h3>
      ${rumuz || slot.anonymous ? `<p class="vs-gazette-corner-rail__rumuz">${escapeHtml(rumuz || (slot.anonymous ? 'Anonim Gözcü' : ''))}</p>` : ''}
      <div class="vs-gazette-corner-rail__body">${paras}</div>
      <span class="vs-gazette-corner-rail__hint-open">Tam metin için tıklayın</span>
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
  const type = escapeHtml(item.news_type_label || item.category || '');
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
  const ticker = 'Yazıyor · Yazıyor · Yazıyor · Yazıyor · Yazıyor · Yazıyor';

  return `
    <div class="vs-gazette-extra" data-open-id="${openId}">
      <div class="vs-gazette-extra__banner" aria-hidden="true">${escapeHtml(ticker)}</div>
      <p class="vs-gazette-extra__kicker">${type} · Sıcak haber ek sayısı</p>
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

function renderGazetteFigure(item) {
  if (!item?.has_custom_gazette_image || !item.image_asset) {
    return '';
  }
  const src = escapeHtml(item.image_asset);
  const cap = item.location_badge ? escapeHtml(item.location_badge) : '';
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';
  const altTitle = escapeHtml(item.title || 'Haberi oku');
  return `
    <figure class="vs-gazette-figure">
      <button type="button" class="vs-gazette-figure__zoom" data-open-id="${openId}" aria-label="${altTitle}. Büyüt ve oku">
        <img class="vs-gazette-figure__img" src="${src}" alt="" loading="lazy" decoding="async">
        <span class="vs-gazette-figure__hint" aria-hidden="true">Büyüt</span>
      </button>
      ${cap ? `<figcaption class="vs-gazette-figure__cap">${cap}</figcaption>` : ''}
    </figure>
  `;
}

function renderGazetteArticle(item, { lead = false } = {}) {
  const title = escapeHtml(item.title || '');
  const type = escapeHtml(item.news_type_label || item.category || '');
  const body = paragraphsToHtml(articlePlainText(item), lead ? 16 : 9);
  const paras = splitParagraphs(articlePlainText(item));
  const deck = paras[0] ? escapeHtml(truncateText(paras[0], lead ? 240 : 160)) : '';
  const loc = item.location_badge ? escapeHtml(item.location_badge) : '';
  const rumuz = escapeHtml(formatRumuz(item.author));
  const authorTitle = escapeHtml(item.author || 'Anonim');
  const leadClass = lead ? ' vs-gazette-article--lead' : '';
  const openId = item.id != null ? escapeHtml(String(item.id)) : '';

  const kickerRumuz = rumuz
    ? ` <span class="vs-gazette-article__kicker-sep" aria-hidden="true">·</span> <span class="vs-gazette-article__kicker-rumuz" title="${authorTitle}">${rumuz}</span>`
    : '';

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
        ${renderGazetteFigure(item)}
        <div class="vs-gazette-article__body">${body}</div>
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
  const sheet = els.gazetteStage?.querySelector('.vs-gazette-sheet');
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
    link.download = `lagimdan-sesler-sayi-${issueNumber}-yaprak-${pageNumber}.png`;
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
      els.toggleGazetteLayoutBtn?.parentElement === els.toolbarBarEnd
    ) {
      els.toolbarBarEnd.insertBefore(els.gazetteArchiveWrap, els.toggleGazetteLayoutBtn);
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
  const active = gazette && isSearchActive();
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
  const gazette = isGazetteLayout();
  document.body.classList.toggle('vs-layout-gazette', gazette);
  state.perPage = WEB_ITEMS_PER_PAGE;
  if (els.gazetteArchiveWrap) {
    els.gazetteArchiveWrap.hidden = !gazette;
  }
  placeIssueArchiveSelect();
  syncSearchResultsLayout();

  if (els.gazetteFlip) {
    els.gazetteFlip.hidden = !gazette;
  }
  if (els.newsSliderSection) {
    els.newsSliderSection.hidden = gazette;
  }

  if (els.toggleGazetteLayoutBtn) {
    els.toggleGazetteLayoutBtn.setAttribute('aria-pressed', gazette ? 'true' : 'false');
    const layoutLabel = gazette ? 'Web görünümü' : 'Gazete görünümü';
    els.toggleGazetteLayoutBtn.setAttribute('aria-label', layoutLabel);
    els.toggleGazetteLayoutBtn.setAttribute('title', gazette ? 'Web kart listesine geç' : 'Basılı gazete sayfası. Sayfa sayfa gezin.');
  }
  if (els.toggleGazetteLayoutLabel) {
    els.toggleGazetteLayoutLabel.textContent = gazette ? 'Web görünümü' : 'Gazete görünümü';
  }
  if (els.printGazetteBtn) {
    els.printGazetteBtn.hidden = !gazette;
  }
  if (els.downloadGazettePngBtn) {
    els.downloadGazettePngBtn.hidden = !gazette;
  }
  if (els.pagination) {
    els.pagination.classList.toggle('vs-pagination--gazette', gazette);
  }
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
        <article class="vs-news-list__item vs-news-list__item--open" data-open-id="${item.id}" role="button" tabindex="0">
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
      <article class="vs-news-card vs-news-card--open" data-open-id="${item.id}" role="button" tabindex="0">
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
  els.prevPage.disabled = atStart;
  els.nextPage.disabled = atEnd;
  if (els.gazetteFlipPrev) {
    els.gazetteFlipPrev.disabled = atStart;
  }
  if (els.gazetteFlipNext) {
    els.gazetteFlipNext.disabled = atEnd;
  }
}

function goGazetteHome() {
  closeModal();
  state.pendingArticleId = null;
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
  applyLayoutMode();
  const url = new URL(window.location.pathname, window.location.origin);
  if (state.layoutMode === 'gazette') {
    url.searchParams.set('gorunum', 'gazette');
    const issue = state.currentIssue || {};
    if (issue.issue_number) {
      url.searchParams.set('sayi', String(issue.issue_number));
    }
    url.searchParams.set('yaprak', '1');
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
  const articleId = state.pendingArticleId;
  if (!articleId || state.articleBootstrapInFlight) {
    return;
  }
  state.pendingArticleId = null;
  state.articleBootstrapInFlight = true;
  try {
    const response = await fetch(`/api/public/news/${articleId}`, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    if (!response.ok) {
      return;
    }
    const payload = await response.json();
    const item = payload.news;
    const issueId = item?.gazette_issue_id;
    if (issueId && Number(state.issueId) !== Number(issueId)) {
      state.issueId = issueId;
      state.layoutMode = 'gazette';
      applyLayoutMode();
      await loadFeed();
    }
    const targetSheet = sheetForArticleId(articleId);
    if (Number.isFinite(targetSheet) && targetSheet >= 1 && targetSheet !== state.page) {
      state.page = targetSheet;
      await loadFeed();
    }
    await openArticle(articleId, { gazetteReader: isGazetteLayout() });
  } catch (error) {
    console.warn(error);
  } finally {
    state.articleBootstrapInFlight = false;
  }
}

function renderPagination(meta) {
  state.lastFeedMeta = meta;
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
  if (els.prevPage) {
    els.prevPage.setAttribute('aria-label', isGazetteLayout() ? 'Önceki yaprak' : 'Önceki sayfa');
  }
  if (els.nextPage) {
    els.nextPage.setAttribute('aria-label', isGazetteLayout() ? 'Sonraki yaprak' : 'Sonraki sayfa');
  }
  syncPageNavButtons(meta);
}

async function loadFeed() {
  const gazette = isGazetteLayout();
  const searchResults = gazette && isSearchActive();

  if (gazette && !searchResults) {
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
    if (issueSwitched && !state.pendingArticleId) {
      state.page = Math.max(1, state.page);
    }
  } else {
    els.newsGrid.innerHTML = '<div class="vs-loading">Haberler yükleniyor…</div>';
  }
  const payload = await fetchBootstrap();
  renderFilters(payload.types, payload.feed.type_counts);
  state.lastFeedItems = payload.feed.data || [];
  syncSearchResultsLayout();

  if (searchResults) {
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
    syncBrowserUrl(state.openArticleId ? { articleId: state.openArticleId } : {});
  }
  if (state.pendingArticleId && !state.articleBootstrapInFlight) {
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

function renderStoryHtml(storyText) {
  const normalized = String(storyText || '').trim();
  if (!normalized) {
    return '';
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

function openCornerReader(slotKey) {
  const slots = state.currentIssue?.corner_slots || [];
  const slot = slots.find((row) => String(row.key) === String(slotKey));
  if (!slot || !String(slot.body || '').trim()) {
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

async function openArticle(id, options = {}) {
  const gazetteReader =
    typeof options.gazetteReader === 'boolean' ? options.gazetteReader : isGazetteLayout();
  const response = await fetch(`/api/public/news/${id}`, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  if (!response.ok) {
    return;
  }
  const payload = await response.json();
  const item = payload.news;
  const heroUrl = item.image_asset || '';
  els.modalHero.style.backgroundImage = heroUrl ? `url('${heroUrl}')` : '';
  els.modalHero.dataset.imageUrl = heroUrl;
  applyAdaptiveNewsImage(els.modalHero, heroUrl);
  els.modalTitle.textContent = item.title;

  if (els.modalBadges) {
    els.modalBadges.innerHTML = renderModalBadges(item);
  }
  if (els.modalDate) {
    const when = item.world_date_text || item.published_date || item.date_text || '';
    els.modalDate.textContent = when;
    els.modalDate.hidden = !when;
  }

  if (els.modalAuthor) {
    els.modalAuthor.textContent = item.author || 'Gazete Muhabiri';
  }
  if (els.modalAuthorRole) {
    const role = item.author_role || '';
    els.modalAuthorRole.textContent = role;
    els.modalAuthorRole.hidden = !role;
  }

  if (els.modalLocation) {
    const loc = item.location_badge || '';
    els.modalLocation.textContent = loc ? `📍 ${loc}` : '';
    els.modalLocation.classList.toggle('vs-modal__meta-item--location', Boolean(loc));
    els.modalLocation.hidden = !loc;
  }
  if (els.modalReadTime) {
    const readTime = item.read_time || '3 dk okuma';
    els.modalReadTime.textContent = `📖 ${readTime}`;
    els.modalReadTime.classList.add('vs-modal__meta-item--read');
  }

  const story = item.full_story || item.description;
  els.modalStory.innerHTML = renderStoryHtml(story);

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
    els.detailModalPanel.classList.toggle('vs-modal--article-split', gazetteReader && Boolean(heroUrl));
  }
  if (els.modalHero) {
    els.modalHero.classList.toggle('vs-modal__hero--gazette-photo', gazetteReader && Boolean(heroUrl));
    els.modalHero.hidden = !heroUrl;
  }
  if (els.modalDate) {
    els.modalDate.hidden = false;
  }
  if (els.modalAuthorRole) {
    els.modalAuthorRole.hidden = !els.modalAuthorRole.textContent;
  }
  if (els.modalLocation) {
    els.modalLocation.hidden = !els.modalLocation.textContent;
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
  if (!options.skipUrlSync) {
    syncBrowserUrl({ articleId: id });
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
    els.detailModalPanel.classList.remove('vs-modal--article-split');
  }
  document.body.classList.remove('vs-article-modal-open');
  state.openArticleId = null;
  syncBrowserUrl();
}

function showToast(message) {
  els.toast.textContent = message;
  els.toast.classList.add('is-visible');
  setTimeout(() => els.toast.classList.remove('is-visible'), 4200);
}

async function copyArticleShareLink(articleId) {
  const id = articleId || state.openArticleId;
  if (!id) {
    return;
  }
  const url = buildShareUrl({ articleId: id }).href;
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

async function shareArticle(articleId) {
  const id = articleId || state.openArticleId;
  if (!id) {
    return;
  }
  const url = buildShareUrl({ articleId: id }).href;
  const title = (els.modalTitle?.textContent || '').trim();
  try {
    if (navigator.share) {
      await navigator.share({
        title: title || 'Lağımdan Sesler',
        text: title || undefined,
        url,
      });
      return;
    }
  } catch (error) {
    if (error?.name === 'AbortError') {
      return;
    }
  }
  await copyArticleShareLink(id);
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
    const canvas = await html2canvas(panel, {
      backgroundColor: null,
      scale: Math.min(2, window.devicePixelRatio || 1.5),
      useCORS: true,
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
  }
}

function resetArticleModalHeroPeek() {
  const scrollRoot = els.detailModalPanel?.querySelector('.vs-modal__body--article');
  if (scrollRoot) {
    scrollRoot.scrollTop = 0;
  }
  els.modalHero?.classList.remove('vs-modal__hero--peek-hidden');
  els.detailModalPanel?.classList.remove('vs-modal--hero-hidden');
}

function initArticleModalHeroScroll() {
  const scrollRoot = els.detailModalPanel?.querySelector('.vs-modal__body--article');
  const hero = els.modalHero;
  const panel = els.detailModalPanel;
  if (!scrollRoot || !hero || !panel || scrollRoot.dataset.heroScrollInit === '1') {
    return;
  }
  scrollRoot.dataset.heroScrollInit = '1';

  const onScroll = () => {
    if (!isMobileGazetteLayout() || !els.detailModal?.classList.contains('is-open') || hero.hidden) {
      return;
    }
    const hide = scrollRoot.scrollTop > 10;
    hero.classList.toggle('vs-modal__hero--peek-hidden', hide);
    panel.classList.toggle('vs-modal--hero-hidden', hide);
  };

  scrollRoot.addEventListener('scroll', onScroll, { passive: true });
}

function initArticleModalSwipe() {
  const panel = els.detailModalPanel;
  const backdrop = els.detailModal;
  const scrollRoot = panel?.querySelector('.vs-modal__body--article');
  if (!panel || !backdrop || panel.dataset.swipeInit === '1') {
    return;
  }
  panel.dataset.swipeInit = '1';
  let startY = 0;
  let dragging = false;

  const onStart = (event) => {
    if (!isMobileGazetteLayout() || !backdrop.classList.contains('is-open')) {
      return;
    }
    if (scrollRoot && scrollRoot.scrollTop > 6) {
      return;
    }
    startY = event.touches[0].clientY;
    dragging = true;
    backdrop.classList.add('vs-modal-backdrop--dragging');
  };

  const onMove = (event) => {
    if (!dragging) {
      return;
    }
    const dy = Math.max(0, event.touches[0].clientY - startY);
    panel.style.setProperty('--vs-modal-drag-y', `${dy}px`);
    panel.style.transform = `translateY(${dy}px)`;
  };

  const onEnd = (event) => {
    if (!dragging) {
      return;
    }
    dragging = false;
    backdrop.classList.remove('vs-modal-backdrop--dragging');
    const dy = Math.max(0, (event.changedTouches?.[0]?.clientY || startY) - startY);
    if (dy > 110) {
      closeModal();
    } else {
      resetArticleModalDrag();
    }
  };

  panel.addEventListener('touchstart', onStart, { passive: true });
  panel.addEventListener('touchmove', onMove, { passive: true });
  panel.addEventListener('touchend', onEnd, { passive: true });
  panel.addEventListener('touchcancel', onEnd, { passive: true });
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
    !isSearchActive() &&
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
      goToFeedPage(state.page + 1, 'fwd');
    } else if (dx > 0 && !els.prevPage?.disabled) {
      goToFeedPage(state.page - 1, 'back');
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
  const payload = await response.json();
  if (!response.ok || !payload.success) {
    throw new Error(payload.message || 'Ek sayı basılamadı.');
  }
  return payload;
}

function openHotExtraModal() {
  if (!els.hotExtraModal) {
    return;
  }
  els.hotExtraModal.classList.add('is-open');
  document.body.classList.add('vs-rumor-modal-open');
  resetNewsTypePicker(els.hotExtraTypePicker, els.hotExtraNewsType);
  els.hotExtraTitleInput?.focus();
}

function closeHotExtraModal() {
  if (!els.hotExtraModal) {
    return;
  }
  els.hotExtraModal.classList.remove('is-open');
  document.body.classList.remove('vs-rumor-modal-open');
  els.hotExtraForm?.reset();
  resetNewsTypePicker(els.hotExtraTypePicker, els.hotExtraNewsType);
  if (els.flashAnonymousInput) {
    els.flashAnonymousInput.checked = false;
    syncFlashAnonymousToggle();
  }
  revokeFlashImageObjectUrl();
  updateFlashImagePreview();
}

function syncFlashAnonymousToggle() {
  const on = Boolean(els.flashAnonymousInput?.checked);
  if (els.flashAnonymousToggle) {
    els.flashAnonymousToggle.classList.toggle('is-active', on);
    els.flashAnonymousToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
  }
}

let flashImageObjectUrl = null;

function revokeFlashImageObjectUrl() {
  if (flashImageObjectUrl) {
    URL.revokeObjectURL(flashImageObjectUrl);
    flashImageObjectUrl = null;
  }
}

function flashPresetAssetUrl(path) {
  const clean = String(path || '').replace(/^\//, '');
  if (!clean || clean === '__gazette_auto__') {
    return '';
  }
  return `/${clean}`;
}

function updateFlashImagePreview() {
  if (!els.flashImagePreviewWrap || !els.flashImagePreview) {
    return;
  }

  const fileInput = els.flashCustomImageInput;
  const file = fileInput?.files?.[0];
  const preset = els.hotExtraImagePreset?.value || '__gazette_auto__';

  revokeFlashImageObjectUrl();

  if (file && file.type.startsWith('image/')) {
    flashImageObjectUrl = URL.createObjectURL(file);
    els.flashImagePreview.src = flashImageObjectUrl;
    els.flashImagePreviewWrap.hidden = false;
    if (els.flashImagePreviewHint) {
      els.flashImagePreviewHint.textContent = 'Yüklediğiniz dosya';
      els.flashImagePreviewHint.hidden = false;
    }
    return;
  }

  if (preset && preset !== '__gazette_auto__') {
    els.flashImagePreview.src = flashPresetAssetUrl(preset);
    els.flashImagePreviewWrap.hidden = false;
    if (els.flashImagePreviewHint) {
      const label = els.hotExtraImagePreset?.selectedOptions?.[0]?.textContent?.trim() || '';
      els.flashImagePreviewHint.textContent = label || 'Seçilen woodcut';
      els.flashImagePreviewHint.hidden = !label;
    }
    return;
  }

  els.flashImagePreview.removeAttribute('src');
  els.flashImagePreviewWrap.hidden = true;
  if (els.flashImagePreviewHint) {
    els.flashImagePreviewHint.textContent = '';
    els.flashImagePreviewHint.hidden = true;
  }
}

async function submitRumor(formData) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const response = await fetch('/api/public/news/submissions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': token || '',
    },
    body: JSON.stringify(formData),
  });
  const payload = await response.json();
  if (!response.ok || !payload.success) {
    throw new Error(payload.message || 'Gönderim başarısız.');
  }
  return payload;
}

function resetNewsTypePicker(groupEl, inputEl) {
  if (!groupEl || !inputEl) {
    return;
  }
  const first = groupEl.querySelector('[data-type]');
  if (!first) {
    return;
  }
  selectNewsTypeInPicker(groupEl, inputEl, first.dataset.type);
}

function selectNewsTypeInPicker(groupEl, inputEl, typeKey) {
  if (!groupEl || !inputEl) {
    return;
  }
  const btn = typeKey ? groupEl.querySelector(`[data-type="${typeKey}"]`) : null;
  const target = btn || groupEl.querySelector('[data-type]');
  if (!target) {
    return;
  }
  groupEl.querySelectorAll('.vs-type-picker__btn').forEach((item) => {
    item.classList.remove('is-selected');
    item.setAttribute('aria-checked', 'false');
  });
  target.classList.add('is-selected');
  target.setAttribute('aria-checked', 'true');
  inputEl.value = target.dataset.type;
}

function getSubmissionSlotConfig() {
  try {
    const raw = document.body.dataset.gazetteSubmissionSlots;
    return raw ? JSON.parse(raw) : {};
  } catch (e) {
    return {};
  }
}

const RUMOR_PLACEMENT_KINDS = [
  { id: 'lead', label: 'Manşet' },
  { id: 'corner', label: 'Köşe yazısı' },
  { id: 'general', label: 'Haber / söylenti' },
];

let rumorPlacementLocked = false;

function renderRumorPlacementKinds(selectedKind, { locked = false } = {}) {
  if (!els.rumorPlacementKinds) {
    return;
  }
  rumorPlacementLocked = locked;
  els.rumorPlacementKinds.innerHTML = RUMOR_PLACEMENT_KINDS.map((item) => {
    const selected = item.id === selectedKind;
    const disabled = locked && !selected;
    return `<button type="button" class="vs-rumor-placement__btn${selected ? ' is-selected' : ''}" data-placement-kind="${item.id}" role="radio" aria-checked="${selected ? 'true' : 'false'}"${disabled ? ' disabled' : ''}>${escapeHtml(item.label)}</button>`;
  }).join('');
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

function submissionTargetIssueId() {
  const fromSelect = Number(els.rumorIssueSelect?.value || '');
  if (Number.isFinite(fromSelect) && fromSelect > 0) {
    return fromSelect;
  }
  const fromHidden = Number(els.rumorGazetteIssueId?.value || '');
  if (Number.isFinite(fromHidden) && fromHidden > 0) {
    return fromHidden;
  }
  return activeIssueMeta().id;
}

function submissionTargetIssue() {
  const id = submissionTargetIssueId();
  if (!id) {
    return state.currentIssue;
  }
  const archiveRow = (state.issueArchive || []).find((row) => Number(row.id) === Number(id));
  if (archiveRow) {
    return archiveRow;
  }
  if (Number(state.currentIssue?.id) === Number(id)) {
    return state.currentIssue;
  }
  return archiveRow || state.currentIssue;
}

function rumorIssueSelectOptionText(issue) {
  const base = issueArchiveOptionText(issue);
  if (issue.is_editable) {
    return `${base} · Açık sayı`;
  }
  if (issue.is_locked || issue.is_published) {
    return `${base} · Yayımlandı`;
  }
  if (!issue.is_current) {
    return `${base} · Yayımlandı`;
  }
  return base;
}

function defaultRumorSubmissionIssueId() {
  const editable = (state.issueArchive || []).find((row) => row.is_editable);
  if (editable?.id) {
    return Number(editable.id);
  }
  const current = (state.issueArchive || []).find((row) => row.is_current && !row.is_locked);
  if (current?.id) {
    return Number(current.id);
  }
  return activeIssueMeta().id;
}

function renderRumorIssueSelect(selectedId = null) {
  if (!els.rumorIssueSelect) {
    return;
  }
  const issues = [...(state.issueArchive || [])].sort(
    (a, b) => Number(b.issue_number) - Number(a.issue_number),
  );
  if (!issues.length) {
    els.rumorIssueSelect.innerHTML = '<option value="">Sayı yükleniyor…</option>';
    els.rumorIssueSelect.disabled = true;
    return;
  }
  const targetId = selectedId || defaultRumorSubmissionIssueId();
  els.rumorIssueSelect.disabled = false;
  els.rumorIssueSelect.innerHTML = issues
    .map((issue) => {
      const editable = Boolean(issue.is_editable);
      const selected = Number(targetId) === Number(issue.id);
      return `<option value="${issue.id}"${selected ? ' selected' : ''}${editable ? '' : ' disabled'}>${escapeHtml(rumorIssueSelectOptionText(issue))}</option>`;
    })
    .join('');
  const applied = Number(els.rumorIssueSelect.value) || targetId;
  if (els.rumorGazetteIssueId) {
    els.rumorGazetteIssueId.value = applied ? String(applied) : '';
  }
}

function getRumorCornerSlots() {
  const issue = submissionTargetIssue();
  const live = issue?.corner_slots;
  if (Array.isArray(live) && live.length) {
    return live;
  }
  if (Number(state.currentIssue?.id) === Number(issue?.id) && Array.isArray(state.currentIssue?.corner_slots)) {
    return state.currentIssue.corner_slots;
  }
  return defaultCornerSlotsFromDom();
}

function renderRumorCornerPicker(selectedKey) {
  if (!els.rumorCornerPicker || !els.rumorCornerPickerGrid) {
    return;
  }
  const slots = getRumorCornerSlots();
  els.rumorCornerPicker.hidden = false;
  els.rumorCornerPickerGrid.innerHTML = slots
    .map((s) => {
      const key = escapeHtml(s.key || '');
      const title = escapeHtml(s.title || s.key || 'Köşe');
      const selected = s.key === selectedKey;
      const filled = Boolean(s.is_filled);
      const stateLabel = filled ? 'Dolu' : 'Boş';
      const fillClass = filled ? 'vs-rumor-corner-pick__btn--filled' : 'vs-rumor-corner-pick__btn--empty';
      return `<button type="button" class="vs-rumor-corner-pick__btn ${fillClass}${selected ? ' is-selected' : ''}" data-corner-key="${key}" aria-pressed="${selected ? 'true' : 'false'}"><span class="vs-rumor-corner-pick__title">${title}</span><span class="vs-rumor-corner-pick__state">${stateLabel}</span></button>`;
    })
    .join('');
}

function setRumorCornerSelection(key) {
  const slots = getRumorCornerSlots();
  const slot = slots.find((s) => s.key === key);
  const label = slot?.title || key;
  if (els.rumorGazetteSlotKey) {
    els.rumorGazetteSlotKey.value = key || '';
  }
  if (els.rumorGazetteSlotLabelHidden) {
    els.rumorGazetteSlotLabelHidden.value = label || '';
  }
  renderRumorCornerPicker(key);
}

function applyRumorPlacementKind(nextKind, slotPatch = {}) {
  const kind = nextKind || 'general';
  const slot = {
    kind,
    key: slotPatch.key ?? (kind === 'corner' ? getRumorCornerSlots()[0]?.key : ''),
    label: slotPatch.label ?? '',
  };
  if (kind === 'lead') {
    slot.key = 'lead';
    slot.label = 'Manşet';
  } else if (kind === 'corner') {
    const corner = getRumorCornerSlots().find((s) => s.key === slot.key);
    slot.label = corner?.title || slot.label || 'Köşe yazısı';
  } else if (kind === 'general') {
    slot.key = '';
    slot.label = '';
  }
  applyRumorSlotUI(slot, { skipPlacementRender: false });
}

function activeIssueMeta() {
  const viewingId = state.issueId || state.currentIssue?.id;
  const viewingIssue =
    (state.issueArchive || []).find((row) => Number(row.id) === Number(viewingId)) || state.currentIssue;
  const editable =
    (state.issueArchive || []).find((row) => row.is_current && row.is_editable !== false) ||
    (state.issueArchive || []).find((row) => row.is_current) ||
    viewingIssue;
  const id = editable?.id || viewingId || null;
  return { id, issue: editable || viewingIssue, viewingIssue };
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

function rumorModalUsesGazetteChrome() {
  return document.body.classList.contains('vs-newspaper-page');
}

function applyRumorSlotUI(slot = { kind: 'general' }, options = {}) {
  const kind = slot?.kind || 'general';
  const configs = getSubmissionSlotConfig();
  const cfg = configs[kind] || configs.general || {};
  const issueId = defaultRumorSubmissionIssueId();
  const slotLocked = Boolean(options.placementLocked);
  const gazetteChrome = rumorModalUsesGazetteChrome();

  if (els.rumorModalPanel) {
    els.rumorModalPanel.classList.remove(
      'vs-modal--rumor-slot-lead',
      'vs-modal--rumor-slot-brief',
      'vs-modal--rumor-slot-corner',
      'vs-modal--rumor-slot-general',
    );
    els.rumorModalPanel.classList.add(`vs-modal--rumor-slot-${kind}`);
  }
  if (els.rumorModal) {
    els.rumorModal.classList.toggle('vs-modal-backdrop--gazette-form', gazetteChrome);
    els.rumorModal.classList.toggle('vs-modal-backdrop--rumor-standard', !gazetteChrome);
  }
  if (els.rumorModalPanel) {
    els.rumorModalPanel.classList.toggle('vs-modal--rumor-gazette--active', gazetteChrome);
  }
  if (els.rumorPlacementWrap) {
    els.rumorPlacementWrap.hidden = !isGazetteLayout();
  }
  if (!options.skipPlacementRender) {
    renderRumorPlacementKinds(kind, { locked: slotLocked });
  }

  if (els.rumorModalTitle) {
    els.rumorModalTitle.textContent = cfg.modal_title || 'Söylenti gönder';
  }
  if (els.rumorModalIntro) {
    els.rumorModalIntro.textContent =
      cfg.modal_intro || 'Editör onayından sonra gazetede yayınlanır. Kayıt gerekmez.';
  }
  if (els.rumorTitleInput) {
    els.rumorTitleInput.placeholder = cfg.title_placeholder || '';
  }
  if (els.rumorBodyLabel) {
    els.rumorBodyLabel.textContent = cfg.body_label || 'Söylenti / ihbar metni';
  }
  if (els.rumorBodyInput) {
    els.rumorBodyInput.placeholder = cfg.body_placeholder || '';
  }
  if (els.rumorGazetteSlotKind) {
    els.rumorGazetteSlotKind.value = kind === 'general' ? '' : kind;
  }
  if (els.rumorGazetteSlotKey) {
    els.rumorGazetteSlotKey.value = slot?.key || '';
  }
  if (els.rumorGazetteSlotLabelHidden) {
    els.rumorGazetteSlotLabelHidden.value = slot?.label || '';
  }
  if (els.rumorIssuePickWrap) {
    els.rumorIssuePickWrap.hidden = !gazetteChrome;
  }
  if (gazetteChrome) {
    renderRumorIssueSelect(submissionTargetIssueId() || issueId);
  } else if (els.rumorGazetteIssueId) {
    els.rumorGazetteIssueId.value = issueId ? String(issueId) : '';
  }
  if (els.rumorRumuzWrap) {
    const showRumuz = kind === 'corner';
    els.rumorRumuzWrap.hidden = !showRumuz;
    els.rumorRumuzWrap.classList.toggle('vs-rumor-field--hidden', !showRumuz);
  }
  if (els.rumorMetaGrid) {
    els.rumorMetaGrid.classList.toggle('vs-rumor-form__meta-grid--four', kind === 'corner');
    els.rumorMetaGrid.classList.toggle('vs-rumor-form__meta-grid--three', kind !== 'corner');
  }
  if (els.rumorRumuzInput && kind !== 'corner') {
    els.rumorRumuzInput.value = '';
  }
  if (kind === 'corner') {
    const cornerKey = slot.key || getRumorCornerSlots()[0]?.key;
    if (!els.rumorGazetteSlotKey?.value && cornerKey) {
      els.rumorGazetteSlotKey.value = cornerKey;
    }
    renderRumorCornerPicker(cornerKey);
  } else if (els.rumorCornerPicker) {
    els.rumorCornerPicker.hidden = true;
  }
  if (els.rumorTypePicker?.closest('.vs-form-field')) {
    els.rumorTypePicker.closest('.vs-form-field').hidden = false;
  }
  selectNewsTypeInPicker(els.rumorTypePicker, els.rumorNewsType, cfg.default_news_type);
}

function syncRumorAnonymousToggle() {
  const on = Boolean(els.rumorAnonymousInput?.checked);
  if (els.rumorAnonymousToggle) {
    els.rumorAnonymousToggle.classList.toggle('is-active', on);
    els.rumorAnonymousToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
  }
}

async function openRumorModal(slot = { kind: 'general' }, options = {}) {
  const fromSlot = Boolean(options.fromSlot);
  if (!state.issueArchive.length) {
    try {
      await fetchIssueArchive();
    } catch (archiveError) {
      console.warn(archiveError);
    }
  }
  applyRumorSlotUI(slot, { placementLocked: fromSlot && slot.kind !== 'general' });
  if (els.rumorModal) {
    els.rumorModal.classList.add('is-open');
  }
  document.body.classList.add('vs-rumor-modal-open');
}

function closeRumorModal() {
  if (els.rumorModal) {
    els.rumorModal.classList.remove(
      'is-open',
      'vs-modal-backdrop--gazette-form',
      'vs-modal-backdrop--rumor-standard',
    );
  }
  if (els.rumorModalPanel) {
    els.rumorModalPanel.classList.remove('vs-modal--rumor-gazette--active');
    els.rumorModalPanel.classList.remove(
      'vs-modal--rumor-slot-lead',
      'vs-modal--rumor-slot-brief',
      'vs-modal--rumor-slot-corner',
      'vs-modal--rumor-slot-general',
    );
  }
  document.body.classList.remove('vs-rumor-modal-open');
  if (els.rumorForm) {
    els.rumorForm.reset();
  }
  resetNewsTypePicker(els.rumorTypePicker, els.rumorNewsType);
  applyRumorSlotUI({ kind: 'general' });
  syncRumorAnonymousToggle();
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

  if (els.toggleGazetteLayoutBtn) {
    els.toggleGazetteLayoutBtn.addEventListener('click', () => {
      const next = isGazetteLayout() ? 'web' : 'gazette';
      state.layoutMode = next;
      state.page = 1;
      if (next === 'web' && isMobileGazetteLayout()) {
        state.viewMode = 'grid';
      }
      try {
        localStorage.setItem('vs.gazette.layoutMode', state.layoutMode);
      } catch (e) {
        /* ignore */
      }
      applyLayoutMode();
      loadFeed().catch(console.error);
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
      if (isSearchActive()) {
        state.search = '';
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
      openRumorModal(slot, { fromSlot: true });
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
      if (!isGazetteLayout()) {
        return;
      }
      document.body.classList.add('vs-printing');
      const cleanup = () => document.body.classList.remove('vs-printing');
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
    clearTimeout(mediaFitTimer);
    mediaFitTimer = setTimeout(() => fitAllNewsMedia(document), 120);
  });

  let searchTimer;
  els.searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = els.searchInput.value.trim();
      state.page = 1;
      loadFeed().catch(console.error);
    }, 350);
  });

  els.prevPage.addEventListener('click', () => {
    if (state.page <= 1) {
      return;
    }
    goToFeedPage(state.page - 1, 'back');
  });

  els.nextPage.addEventListener('click', () => {
    goToFeedPage(state.page + 1, 'fwd');
  });

  if (els.gazetteFlipPrev) {
    els.gazetteFlipPrev.addEventListener('click', () => {
      if (state.page <= 1) {
        return;
      }
      goToFeedPage(state.page - 1, 'back');
    });
  }
  if (els.gazetteFlipNext) {
    els.gazetteFlipNext.addEventListener('click', () => {
      goToFeedPage(state.page + 1, 'fwd');
    });
  }

  document.body.addEventListener('click', (event) => {
    const homeBtn = event.target.closest('[data-gazette-home]');
    if (homeBtn) {
      event.preventDefault();
      goGazetteHome();
      return;
    }
    const cornerBtn = event.target.closest('[data-corner-key]');
    if (cornerBtn && !cornerBtn.dataset.openId) {
      const inGazette = isGazetteLayout();
      if (!inGazette || els.gazetteStage?.contains(cornerBtn)) {
        event.preventDefault();
        openCornerReader(cornerBtn.dataset.cornerKey);
      }
      return;
    }
    const openBtn = event.target.closest('[data-open-id]');
    if (!openBtn) {
      return;
    }
    const inGazette = isGazetteLayout();
    if (inGazette) {
      const fromSearchGrid = isSearchActive() && els.newsGrid?.contains(openBtn);
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
    openArticle(openBtn.dataset.openId, { gazetteReader: inGazette }).catch(console.error);
  });

  document.body.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }
    const cornerRail = event.target.closest('.vs-gazette-corner-rail--open[data-corner-key]');
    if (!cornerRail) {
      return;
    }
    event.preventDefault();
    openCornerReader(cornerRail.dataset.cornerKey);
  });

  document.addEventListener('keydown', (event) => {
    if (!isGazetteLayout()) {
      return;
    }
    if (event.target.closest('input, textarea, select, [contenteditable="true"]')) {
      return;
    }
    if (event.key === 'ArrowLeft' && !els.prevPage.disabled) {
      event.preventDefault();
      goToFeedPage(state.page - 1, 'back');
    }
    if (event.key === 'ArrowRight' && !els.nextPage.disabled) {
      event.preventDefault();
      goToFeedPage(state.page + 1, 'fwd');
    }
  });

  window.addEventListener('popstate', () => {
    state.suppressUrlSync = true;
    readUrlIntoState();
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
  els.detailModal.addEventListener('click', (event) => {
    if (event.target === els.detailModal) {
      closeModal();
    }
  });

  if (els.openHotExtraBtn) {
    els.openHotExtraBtn.addEventListener('click', () => openHotExtraModal());
  }
  if (els.hotExtraClose) {
    els.hotExtraClose.addEventListener('click', closeHotExtraModal);
  }
  if (els.hotExtraModal) {
    els.hotExtraModal.addEventListener('click', (event) => {
      if (event.target === els.hotExtraModal) {
        closeHotExtraModal();
      }
    });
  }
  if (els.flashAnonymousToggle && els.flashAnonymousInput) {
    els.flashAnonymousToggle.addEventListener('click', () => {
      els.flashAnonymousInput.checked = !els.flashAnonymousInput.checked;
      syncFlashAnonymousToggle();
    });
    syncFlashAnonymousToggle();
  }

  if (els.hotExtraImagePreset) {
    els.hotExtraImagePreset.addEventListener('change', () => {
      if (els.flashCustomImageInput) {
        els.flashCustomImageInput.value = '';
      }
      updateFlashImagePreview();
    });
  }
  if (els.flashCustomImageInput) {
    els.flashCustomImageInput.addEventListener('change', updateFlashImagePreview);
  }

  if (els.hotExtraForm) {
    els.hotExtraForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (els.hotExtraSubmit) {
        els.hotExtraSubmit.disabled = true;
      }
      try {
        const formData = new FormData(els.hotExtraForm);
        const payload = await submitHotExtra(formData);
        closeHotExtraModal();
        showToast(payload.message || 'Flash haber editöre gönderildi.');
      } catch (error) {
        showToast(error.message || 'Ek sayı basılamadı.');
      } finally {
        if (els.hotExtraSubmit) {
          els.hotExtraSubmit.disabled = false;
        }
      }
    });
  }

  if (els.openRumorBtn) {
    els.openRumorBtn.addEventListener('click', () => openRumorModal({ kind: 'general' }));
  }
  if (els.rumorClose) {
    els.rumorClose.addEventListener('click', closeRumorModal);
  }
  if (els.rumorModal) {
    els.rumorModal.addEventListener('click', (event) => {
      if (event.target === els.rumorModal) {
        closeRumorModal();
      }
    });
  }

  if (els.rumorPlacementKinds) {
    els.rumorPlacementKinds.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-placement-kind]');
      if (!btn || rumorPlacementLocked) {
        return;
      }
      const nextKind = btn.dataset.placementKind;
      if (!nextKind) {
        return;
      }
      applyRumorPlacementKind(nextKind);
    });
  }

  if (els.rumorAnonymousToggle && els.rumorAnonymousInput) {
    els.rumorAnonymousToggle.addEventListener('click', () => {
      els.rumorAnonymousInput.checked = !els.rumorAnonymousInput.checked;
      syncRumorAnonymousToggle();
    });
    syncRumorAnonymousToggle();
  }

  if (els.rumorIssueSelect) {
    els.rumorIssueSelect.addEventListener('change', () => {
      const value = Number(els.rumorIssueSelect.value);
      if (els.rumorGazetteIssueId) {
        els.rumorGazetteIssueId.value = Number.isFinite(value) && value > 0 ? String(value) : '';
      }
      const kind = els.rumorGazetteSlotKind?.value || 'general';
      if (kind === 'corner') {
        renderRumorCornerPicker(els.rumorGazetteSlotKey?.value || getRumorCornerSlots()[0]?.key);
      }
    });
  }

  if (els.rumorCornerPickerGrid) {
    els.rumorCornerPickerGrid.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-corner-key]');
      if (!btn) {
        return;
      }
      const key = btn.dataset.cornerKey;
      if (!key) {
        return;
      }
      if (els.rumorGazetteSlotKind) {
        els.rumorGazetteSlotKind.value = 'corner';
      }
      setRumorCornerSelection(key);
    });
  }

  els.rumorForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    els.rumorSubmit.disabled = true;
    try {
      const form = new FormData(els.rumorForm);
      const slotKind = form.get('gazette_slot_kind') || null;
      const rumuz = (form.get('rumuz') || '').toString().trim() || null;
      const payload = await submitRumor({
        title: form.get('title'),
        news_type: form.get('news_type'),
        description: form.get('description'),
        location_badge: form.get('location_badge') || null,
        author: form.get('author') || null,
        anonymous: form.get('anonymous') === 'on',
        gazette_slot_kind: slotKind || null,
        gazette_slot_key: form.get('gazette_slot_key') || null,
        gazette_slot_label: form.get('gazette_slot_label') || null,
        gazette_issue_id: form.get('gazette_issue_id') ? Number(form.get('gazette_issue_id')) : null,
        rumuz,
      });
      closeRumorModal();
      showToast(payload.message);
    } catch (error) {
      showToast(error.message || 'Gönderim başarısız.');
    } finally {
      els.rumorSubmit.disabled = false;
    }
  });
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
  els.openHotExtraBtn = qs('openHotExtraModal');
  els.hotExtraModal = qs('hotExtraModal');
  els.hotExtraClose = qs('hotExtraModalClose');
  els.hotExtraForm = qs('hotExtraForm');
  els.hotExtraSubmit = qs('hotExtraSubmit');
  els.hotExtraTypePicker = qs('hotExtraTypePicker');
  els.hotExtraNewsType = qs('hotExtraNewsType');
  els.hotExtraTitleInput = qs('hotExtraTitleInput');
  els.flashAnonymousInput = qs('flashAnonymousInput');
  els.flashAnonymousToggle = qs('flashAnonymousToggle');
  els.hotExtraImagePreset = qs('hotExtraImagePreset');
  els.flashCustomImageInput = qs('flashCustomImageInput');
  els.flashImagePreviewWrap = qs('flashImagePreviewWrap');
  els.flashImagePreview = qs('flashImagePreview');
  els.flashImagePreviewHint = qs('flashImagePreviewHint');
  els.openRumorBtn = qs('openRumorModal');
  els.rumorModal = qs('rumorModal');
  els.rumorModalPanel = qs('rumorModalPanel');
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
  els.rumorIssueSelect = qs('rumorIssueSelect');
  els.rumorIssuePickWrap = qs('rumorIssuePickWrap');
  els.rumorTitleInput = qs('rumorTitleInput');
  els.rumorBodyLabel = qs('rumorBodyLabel');
  els.rumorBodyInput = qs('rumorBodyInput');
  els.rumorRumuzWrap = qs('rumorRumuzWrap');
  els.rumorMetaGrid = qs('rumorMetaGrid');
  els.rumorRumuzInput = qs('rumorRumuzInput');
  els.rumorPlacementWrap = qs('rumorPlacementWrap');
  els.rumorPlacementKinds = qs('rumorPlacementKinds');
  els.rumorCornerPicker = qs('rumorCornerPicker');
  els.rumorCornerPickerGrid = qs('rumorCornerPickerGrid');
  els.rumorAnonymousInput = qs('rumorAnonymousInput');
  els.rumorAnonymousToggle = qs('rumorAnonymousToggle');
  els.gazetteFlip = qs('gazetteFlip');
  els.gazetteFlipPrev = qs('gazetteFlipPrev');
  els.gazetteFlipNext = qs('gazetteFlipNext');
  els.gazetteStage = qs('gazetteStage');
  els.newsSliderSection = document.querySelector('.vs-news-slider');
  els.toggleGazetteLayoutBtn = qs('toggleGazetteLayoutBtn');
  els.toggleGazetteLayoutLabel = qs('toggleGazetteLayoutLabel');
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

  initNewsTypePicker(els.rumorTypePicker, els.rumorNewsType);
  if (els.hotExtraTypePicker && els.hotExtraNewsType) {
    initNewsTypePicker(els.hotExtraTypePicker, els.hotExtraNewsType);
  }
  readUrlIntoState();
  if (els.searchInput && state.search) {
    els.searchInput.value = state.search;
  }
  const urlHasGazetteHints =
    new URLSearchParams(window.location.search).has('gorunum') ||
    new URLSearchParams(window.location.search).has('yaprak') ||
    new URLSearchParams(window.location.search).has('sayi') ||
    new URLSearchParams(window.location.search).has('haber');
  try {
    const savedLayout = localStorage.getItem('vs.gazette.layoutMode');
    if (!urlHasGazetteHints && (savedLayout === 'gazette' || savedLayout === 'web')) {
      state.layoutMode = savedLayout;
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
  applyLayoutMode();
  syncFilterPanelVisibility();
  bindEvents();
  initGazetteFlipSwipe();
  initArticleModalHeroScroll();
  if (state.pendingFlashModal) {
    state.pendingFlashModal = false;
    openHotExtraModal();
  }
  loadFeed().catch((error) => {
    els.newsGrid.innerHTML = `<div class="vs-loading">${error.message}</div>`;
  });
});
