const WEB_ITEMS_PER_PAGE = 9;
const GAZETTE_SUMMARY_SLOTS = 5;

const state = {
  page: 1,
  type: 'all',
  search: '',
  perPage: WEB_ITEMS_PER_PAGE,
  issueId: null,
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
};

const els = {};

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

async function fetchBootstrap() {
  const params = {
    type: state.type === 'all' ? '' : state.type,
    q: state.search,
  };
  if (isGazetteLayout()) {
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
    motto: body.dataset.gazetteMotto || 'Dedikodu burada doğrulanır — ya da en azından mürekkeple basılır.',
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
      body.dataset.gazetteIssueDate || 'Leos ayının 1. günü, Vonyas — 631',
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
  return {
    page,
    dateStr: issue.date_line || cal.dateLine,
    issueNumber: issue.issue_number || cal.issueNumber,
    issueLabel: issue.label || cal.issueLabel,
    schedule: issue.schedule || '',
    sheetKind: meta?.sheet_kind || (page === 1 ? 'cover' : 'inner'),
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
  const options = (state.issueArchive || []).map((issue) => {
    const selected =
      Number(state.issueId) === Number(issue.id) || (!state.issueId && issue.is_current);
    const disabled = !issue.is_selectable;
    return `<option value="${issue.id}"${selected ? ' selected' : ''}${disabled ? ' disabled' : ''}>${escapeHtml(issueArchiveOptionText(issue))}</option>`;
  });
  els.gazetteArchiveSelect.innerHTML = options.join('') || '<option value="">Sayı yok</option>';
  if (!state.issueId && state.currentIssue?.id) {
    state.issueId = state.currentIssue.id;
  }
}

function renderCornerSlot(slot) {
  const title = escapeHtml(slot.title || 'Köşe');
  const key = escapeHtml(slot.key || 'corner');
  const body = String(slot.body || '').trim();
  const rumuz = slot.rumuz ? escapeHtml(String(slot.rumuz)) : '';

  if (!body) {
    return `
      <button type="button" class="vs-gazette-corner vs-gazette-corner--empty vs-gazette-slot--fill" data-corner-key="${key}">
        <span class="vs-gazette-corner__title">${title}</span>
        <span class="vs-gazette-corner__rumuz vs-gazette-corner__rumuz--empty">${rumuz || '— rumuz —'}</span>
        <span class="vs-gazette-corner__fill">Burayı doldur</span>
      </button>
    `;
  }

  return `
    <div class="vs-gazette-corner">
      <span class="vs-gazette-corner__title">${title}</span>
      ${rumuz ? `<span class="vs-gazette-corner__rumuz">${rumuz}</span>` : ''}
      <button type="button" class="vs-gazette-corner__body vs-gazette-slot--fill" data-corner-key="${key}">${escapeHtml(body)}</button>
    </div>
  `;
}

function renderCoverSummary(item, index) {
  if (!item) {
    return `
      <button type="button" class="vs-gazette-brief vs-gazette-brief--empty vs-gazette-slot--fill" data-brief-slot="${index}">
        <span class="vs-gazette-brief__kicker">Boş sütun</span>
        <span class="vs-gazette-brief__title">Burayı doldur</span>
        <span class="vs-gazette-brief__excerpt">Okuyucu haberi veya söylentiyi buraya taşıyabilir — tıkla, gönder.</span>
      </button>
    `;
  }
  const title = escapeHtml(item.title || '');
  const type = escapeHtml(item.news_type_label || item.category || '');
  const excerpt = escapeHtml(truncateText(item.description || '', 140));
  return `
    <article class="vs-gazette-brief">
      <span class="vs-gazette-brief__kicker">${type}</span>
      <h3 class="vs-gazette-brief__title">${title}</h3>
      <p class="vs-gazette-brief__excerpt">${excerpt}</p>
      <span class="vs-gazette-brief__jump">İç sayfa ${Math.floor(index / 3) + 2} »</span>
    </article>
  `;
}

function renderGazetteCoverPage(items, brand, issueMeta) {
  const lead = items[0];
  const slots = state.currentIssue?.corner_slots || [];
  const summaries = [];
  for (let i = 0; i < GAZETTE_SUMMARY_SLOTS; i++) {
    summaries.push(renderCoverSummary(items[i + 1], i));
  }

  const leadBlock = lead
    ? (() => {
        const leadTitle = escapeHtml(lead.title || '');
        const leadType = escapeHtml(lead.news_type_label || lead.category || 'Manşet');
        const leadParas = splitParagraphs(articlePlainText(lead));
        const deck = leadParas[0] ? escapeHtml(truncateText(leadParas[0], 220)) : '';
        return `
        <div class="vs-gazette-cover__lead">
          <p class="vs-gazette-article__kicker">${leadType}</p>
          <h2 class="vs-gazette-cover__headline">
            <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
            ${leadTitle}
            <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
          </h2>
          <p class="vs-gazette-cover__deck">${deck}</p>
        </div>`;
      })()
    : `
        <button type="button" class="vs-gazette-cover__lead vs-gazette-brief--empty vs-gazette-slot--fill" data-brief-slot="lead">
          <span class="vs-gazette-brief__kicker">Manşet</span>
          <span class="vs-gazette-brief__title">Burayı doldur</span>
          <span class="vs-gazette-brief__excerpt">Haftanın ana haberini gönder — editör onayından sonra kapakta yer alır.</span>
        </button>`;

  const corners = slots.length
    ? slots.map((s) => renderCornerSlot(s)).join('')
    : renderCornerSlot({ key: 'default', title: 'Köşe', body: null, rumuz: null });

  return `
    <div class="vs-gazette-cover">
      <section class="vs-gazette-cover__mast">
        ${leadBlock}
      </section>
      <div class="vs-gazette-cover__grid">
        <div class="vs-gazette-cover__summaries">
          <h3 class="vs-gazette-cover__section-title">Özetler</h3>
          ${summaries.join('')}
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
  'Lağım Telgrafı: Hanın arka kapısından üç yeni söylenti çıktı — mürekkebi kurumadan yetişin.',
  'Bugünkü koku indeksi: Orta şiddette söylenti, hafif bira ve bir tutam gizem.',
  'İlan: Kayıp taç yok; ama taç biçiminde bir küllük bulundu. Sahibi aranıyor.',
  'Hava: Sisli sabah, öğleden sonra dedikodu — liman söylentileri rüzgârla yayıldı.',
];

function sidebarSnippet(page) {
  return GAZETTE_SIDEBAR_SNIPPETS[(page - 1) % GAZETTE_SIDEBAR_SNIPPETS.length];
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
    return '◆';
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
  return `
    <figure class="vs-gazette-figure">
      <img class="vs-gazette-figure__img" src="${src}" alt="" loading="lazy" decoding="async">
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

  return `
    <article class="vs-gazette-article${leadClass}">
      <div class="vs-gazette-article__rumuz" title="${authorTitle}">
        <span class="vs-gazette-rumuz" aria-label="Rumuz: ${authorTitle}">${rumuz}</span>
      </div>
      <div class="vs-gazette-article__main">
        ${renderGazetteFigure(item)}
        <p class="vs-gazette-article__kicker">${type}</p>
        <h2 class="vs-gazette-article__title">
          <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
          <span class="vs-gazette-article__title-text">${title}</span>
          <span class="vs-gazette-article__orn" aria-hidden="true">❧</span>
        </h2>
        ${deck ? `<p class="vs-gazette-article__deck">${deck}</p>` : ''}
        ${loc ? `<p class="vs-gazette-article__place">${loc}</p>` : ''}
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

  return `
    <div class="vs-gazette-inner">
      <p class="vs-gazette-inner__label">İç sayfa — tam metin</p>
      <div class="vs-gazette-articles">${articles}</div>
    </div>
  `;
}

function renderGazetteMasthead(brand, issueMeta, isFirstPage) {
  const { dateStr, issueNumber, issueLabel } = issueMeta;
  if (!isFirstPage) {
    return `
      <header class="vs-gazette-running-head">
        <span class="vs-gazette-running-head__title">${escapeHtml(brand.name)}</span>
        <span class="vs-gazette-running-head__rule" aria-hidden="true"></span>
        <span class="vs-gazette-running-head__sheet">Yaprak ${issueMeta.page}</span>
      </header>
    `;
  }

  return `
    <header class="vs-gazette-masthead">
      <div class="vs-gazette-masthead__ornament">☙ &nbsp; ${escapeHtml(brand.name)} &nbsp; ❧</div>
      <div class="vs-gazette-masthead__row">
        ${brand.logo ? `<img class="vs-gazette-masthead__crest" src="${escapeHtml(brand.logo)}" width="72" height="72" alt="">` : ''}
        <div class="vs-gazette-masthead__titles">
          <h2 class="vs-gazette-masthead__title">${escapeHtml(brand.name)}</h2>
          <p class="vs-gazette-masthead__subtitle">${escapeHtml(issueMeta.schedule || 'Her Vonyas yeni sayı basılır.')}</p>
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
  const isCover = sheetKind === 'cover';
  const body = isCover
    ? renderGazetteCoverPage(items, brand, issueMeta)
    : renderGazetteInnerPage(items, brand, issueMeta);

  if (!isCover && !items.length) {
    return '<div class="vs-loading">Bu iç sayfada metin yok.</div>';
  }

  const sheet = `
    <article class="vs-gazette-sheet${isCover ? ' vs-gazette-sheet--cover' : ' vs-gazette-sheet--inner'}" id="gazettePrintSheet">
      ${renderGazetteMasthead(brand, issueMeta, isCover)}
      <div class="vs-gazette-rule vs-gazette-rule--heavy" aria-hidden="true"></div>
      ${body}
      <aside class="vs-gazette-sidebar">
        <h3>Lağım Köşesi</h3>
        <p>${escapeHtml(sidebarSnippet(page))}</p>
      </aside>
      <footer class="vs-gazette-footer">
        <span>${escapeHtml(brand.press)}</span>
        <span>${isCover ? 'Kapak' : 'İç sayfa'} · Yaprak ${page} / ${meta?.last_page || 1}</span>
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
  els.gazetteStage.hidden = false;
  const sheet = els.gazetteStage.querySelector('.vs-gazette-sheet');
  if (sheet) {
    sheet.classList.add('is-turning');
    sheet.addEventListener(
      'animationend',
      () => {
        sheet.classList.remove('is-turning');
      },
      { once: true },
    );
  }
  if (isMobileGazetteLayout()) {
    return;
  }
  requestAnimationFrame(() => {
    els.gazetteStage.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

function applyLayoutMode() {
  const gazette = isGazetteLayout();
  document.body.classList.toggle('vs-layout-gazette', gazette);
  state.perPage = WEB_ITEMS_PER_PAGE;
  if (els.gazetteArchiveWrap) {
    els.gazetteArchiveWrap.hidden = !gazette;
  }

  if (els.gazetteStage) {
    els.gazetteStage.hidden = !gazette;
  }
  if (els.newsSliderSection) {
    els.newsSliderSection.hidden = gazette;
  }

  if (els.toggleGazetteLayoutBtn) {
    els.toggleGazetteLayoutBtn.setAttribute('aria-pressed', gazette ? 'true' : 'false');
  }
  if (els.toggleGazetteLayoutLabel) {
    els.toggleGazetteLayoutLabel.textContent = gazette ? 'Web görünümü' : 'Gazete görünümü';
  }
  if (els.printGazetteBtn) {
    els.printGazetteBtn.hidden = !gazette;
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
        <article class="vs-news-list__item" data-open-id="${item.id}">
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
      <article class="vs-news-card" data-open-id="${item.id}">
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
  els.toggleFiltersLabel.textContent = short;
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
}

function renderPagination(meta) {
  state.lastFeedMeta = meta;
  if (isGazetteLayout()) {
    const issue = state.currentIssue || {};
    const num = issue.issue_number || inWorldCalendar().issueNumber;
    const kind = meta.sheet_kind === 'cover' ? 'Kapak' : 'İç sayfa';
    const date = issue.date_short ? ` · ${issue.date_short}` : '';
    els.pageSheet.textContent = `Sayı ${num}${date} — ${kind} · yaprak ${meta.current_page} / ${meta.last_page}`;
  } else {
    els.pageSheet.textContent = `Gazete Sayfası ${meta.current_page} / ${meta.last_page}`;
  }
  els.prevPage.disabled = meta.current_page <= 1;
  els.nextPage.disabled = meta.current_page >= meta.last_page;
}

async function loadFeed() {
  if (isGazetteLayout()) {
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
  } else {
    els.newsGrid.innerHTML = '<div class="vs-loading">Haberler yükleniyor…</div>';
  }
  const payload = await fetchBootstrap();
  renderFilters(payload.types, payload.feed.type_counts);
  state.lastFeedItems = payload.feed.data || [];

  if (isGazetteLayout()) {
    state.currentIssue = payload.issue || state.currentIssue;
    state.issueArchive = payload.archive || state.issueArchive;
    renderIssueArchiveSelect();
  const isInner = payload.feed?.meta?.sheet_kind === 'inner';
    if (isInner) {
      state.lastFeedItems = await enrichGazetteItems(state.lastFeedItems);
    }
    renderGazette(state.lastFeedItems, payload.feed.meta);
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
    if (els.newsSliderSection) {
      els.newsSliderSection.hidden = false;
    }
  }
  renderPagination(payload.feed.meta);
}

function renderModalBadges(item) {
  const parts = [];
  const typeLabel = item.news_type_label || item.category;
  if (typeLabel) {
    parts.push(`<span class="vs-modal__badge vs-modal__badge--type">${escapeHtml(typeLabel)}</span>`);
  }
  if (item.tag) {
    parts.push(`<span class="vs-modal__badge">${escapeHtml(item.tag)}</span>`);
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

async function openArticle(id) {
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
  els.detailModal.classList.add('is-open');
}

function closeModal() {
  els.detailModal.classList.remove('is-open');
}

function showToast(message) {
  els.toast.textContent = message;
  els.toast.classList.add('is-visible');
  setTimeout(() => els.toast.classList.remove('is-visible'), 4200);
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
  groupEl.querySelectorAll('.vs-type-picker__btn').forEach((item) => {
    item.classList.remove('is-selected');
    item.setAttribute('aria-checked', 'false');
  });
  first.classList.add('is-selected');
  first.setAttribute('aria-checked', 'true');
  inputEl.value = first.dataset.type;
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
    }
    loadFeed().catch(console.error);
  });

  if (els.toggleFiltersBtn && els.filterPanel) {
    els.toggleFiltersBtn.addEventListener('click', () => {
      const expanded = els.toggleFiltersBtn.getAttribute('aria-expanded') === 'true';
      els.toggleFiltersBtn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      els.filterPanel.hidden = expanded;
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
      const value = Number(els.gazetteArchiveSelect.value);
      state.issueId = Number.isFinite(value) && value > 0 ? value : null;
      state.page = 1;
      loadFeed().catch(console.error);
    });
  }

  if (els.gazetteStage) {
    els.gazetteStage.addEventListener('click', (event) => {
      const fill = event.target.closest('.vs-gazette-slot--fill');
      if (!fill || !isGazetteLayout()) {
        return;
      }
      event.preventDefault();
      if (els.rumorModal) {
        els.rumorModal.classList.add('is-open');
      }
      showToast('Metni gönderin — editör onayından sonra bu sayıya işlenir.');
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

  let mediaFitTimer;
  window.addEventListener('resize', () => {
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
    if (state.page <= 1) return;
    state.page -= 1;
    loadFeed().catch(console.error);
  });

  els.nextPage.addEventListener('click', () => {
    state.page += 1;
    loadFeed().catch(console.error);
  });

  document.body.addEventListener('click', (event) => {
    if (isGazetteLayout()) {
      return;
    }
    const openBtn = event.target.closest('[data-open-id]');
    if (openBtn) {
      openArticle(openBtn.dataset.openId).catch(console.error);
    }
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
      state.page -= 1;
      loadFeed().catch(console.error);
    }
    if (event.key === 'ArrowRight' && !els.nextPage.disabled) {
      event.preventDefault();
      state.page += 1;
      loadFeed().catch(console.error);
    }
  });

  els.modalClose.addEventListener('click', closeModal);
  els.detailModal.addEventListener('click', (event) => {
    if (event.target === els.detailModal) {
      closeModal();
    }
  });

  els.openRumorBtn.addEventListener('click', () => els.rumorModal.classList.add('is-open'));
  els.rumorClose.addEventListener('click', () => els.rumorModal.classList.remove('is-open'));
  els.rumorModal.addEventListener('click', (event) => {
    if (event.target === els.rumorModal) {
      els.rumorModal.classList.remove('is-open');
    }
  });

  els.rumorForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    els.rumorSubmit.disabled = true;
    try {
      const form = new FormData(els.rumorForm);
      const payload = await submitRumor({
        title: form.get('title'),
        news_type: form.get('news_type'),
        description: form.get('description'),
        location_badge: form.get('location_badge') || null,
        author: form.get('author') || null,
        anonymous: form.get('anonymous') === 'on',
      });
      els.rumorModal.classList.remove('is-open');
      els.rumorForm.reset();
      resetNewsTypePicker(els.rumorTypePicker, els.rumorNewsType);
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
  els.toggleFiltersBtn = qs('toggleFiltersBtn');
  els.toggleFiltersLabel = qs('toggleFiltersLabel');
  els.toggleViewModeBtn = qs('toggleViewModeBtn');
  els.toggleViewModeLabel = qs('toggleViewModeLabel');
  els.searchInput = qs('newsSearchInput');
  els.newsGrid = qs('newsGrid');
  els.prevPage = qs('newsPrevPage');
  els.nextPage = qs('newsNextPage');
  els.pageSheet = qs('newsPageSheet');
  els.detailModal = qs('newsDetailModal');
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
  els.openRumorBtn = qs('openRumorModal');
  els.rumorModal = qs('rumorModal');
  els.rumorClose = qs('rumorModalClose');
  els.rumorForm = qs('rumorForm');
  els.rumorSubmit = qs('rumorSubmit');
  els.toast = qs('newsToast');
  els.rumorTypePicker = qs('rumorTypePicker');
  els.rumorNewsType = qs('rumorNewsType');
  els.gazetteStage = qs('gazetteStage');
  els.newsSliderSection = document.querySelector('.vs-news-slider');
  els.toggleGazetteLayoutBtn = qs('toggleGazetteLayoutBtn');
  els.toggleGazetteLayoutLabel = qs('toggleGazetteLayoutLabel');
  els.printGazetteBtn = qs('printGazetteBtn');
  els.pagination = qs('newsPagination');
  els.pageHint = qs('newsPageHint');
  els.prevPageLabel = qs('newsPrevPageLabel');
  els.nextPageLabel = qs('newsNextPageLabel');
  els.gazetteArchiveSelect = qs('gazetteArchiveSelect');
  els.gazetteArchiveWrap = qs('gazetteArchiveWrap');

  initNewsTypePicker(els.rumorTypePicker, els.rumorNewsType);
  try {
    const savedLayout = localStorage.getItem('vs.gazette.layoutMode');
    if (savedLayout === 'gazette' || savedLayout === 'web') {
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
  loadFeed().catch((error) => {
    els.newsGrid.innerHTML = `<div class="vs-loading">${error.message}</div>`;
  });
});
