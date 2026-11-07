const GAZETTE_ITEMS_PER_SHEET = 4;
const WEB_ITEMS_PER_PAGE = 9;

const state = {
  page: 1,
  type: 'all',
  search: '',
  perPage: WEB_ITEMS_PER_PAGE,
  sliderIndex: 0,
  featured: [],
  sliderTimer: null,
  viewMode: 'grid',
  layoutMode: 'web',
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

async function fetchBootstrap() {
  const response = await fetch(
    apiUrl('/api/public/news/bootstrap', {
      page: state.page,
      per_page: state.perPage,
      type: state.type === 'all' ? '' : state.type,
      q: state.search,
    }),
    { headers: { Accept: 'application/json' }, cache: 'no-store' },
  );
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
    slide.style.backgroundImage = `url('${item.image_asset}')`;
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
      body.dataset.gazetteIssueDate || 'Leos ayının 1. günü, Vonyas (Pazar) — 631',
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
  return {
    page,
    dateStr: cal.dateLine,
    issueNumber: cal.issueNumber,
    issueLabel: cal.issueLabel,
  };
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
          <p class="vs-gazette-masthead__subtitle">Haftalık dedikodu yaprakları — tek mühür, tek tarih</p>
        </div>
      </div>
      <div class="vs-gazette-masthead__meta">
        <span class="vs-gazette-masthead__date">${escapeHtml(dateStr)}</span>
        <span class="vs-gazette-masthead__issue">${escapeHtml(issueLabel)} · Sayı ${escapeHtml(String(issueNumber))}</span>
        <span class="vs-gazette-masthead__motto">${escapeHtml(brand.motto)}</span>
      </div>
    </header>
  `;
}

function renderGazetteSheet(items, meta) {
  if (!items.length) {
    return '<div class="vs-loading">Bu sayfada basılabilir haber yok.</div>';
  }

  const brand = gazetteBranding();
  const issueMeta = gazetteIssueLabel(meta);
  const { page } = issueMeta;
  const isFirstPage = page === 1;

  const articles = items
    .map((item, index) => {
      const block = renderGazetteArticle(item, { lead: isFirstPage && index === 0 });
      const rule = index < items.length - 1 ? '<div class="vs-gazette-article-rule" aria-hidden="true"></div>' : '';
      return block + rule;
    })
    .join('');

  const sheet = `
    <article class="vs-gazette-sheet${isFirstPage ? '' : ' vs-gazette-sheet--continued'}" id="gazettePrintSheet">
      ${renderGazetteMasthead(brand, issueMeta, isFirstPage)}
      <div class="vs-gazette-rule vs-gazette-rule--heavy" aria-hidden="true"></div>
      <div class="vs-gazette-layout">
        <div class="vs-gazette-articles">
          ${articles}
        </div>
        <aside class="vs-gazette-sidebar">
          <h3>Lağım Köşesi</h3>
          <p>${escapeHtml(sidebarSnippet(page))}</p>
        </aside>
      </div>
      <footer class="vs-gazette-footer">
        <span>${escapeHtml(brand.press)}</span>
        <span>Yaprak ${page} / ${meta?.last_page || 1}</span>
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
  requestAnimationFrame(() => {
    els.gazetteStage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });
}

function applyLayoutMode() {
  const gazette = isGazetteLayout();
  document.body.classList.toggle('vs-layout-gazette', gazette);
  state.perPage = gazette ? GAZETTE_ITEMS_PER_SHEET : WEB_ITEMS_PER_PAGE;

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
        <div class="vs-news-card__thumb" style="background-image:url('${escapeHtml(item.image_asset || '')}')"></div>
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
    const cal = inWorldCalendar();
    els.pageSheet.textContent = `${cal.issueLabel} — yaprak ${meta.current_page} / ${meta.last_page}`;
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
  } else {
    els.newsGrid.innerHTML = '<div class="vs-loading">Haberler yükleniyor…</div>';
  }
  const payload = await fetchBootstrap();
  renderFilters(payload.types, payload.feed.type_counts);
  state.lastFeedItems = payload.feed.data || [];

  if (isGazetteLayout()) {
    state.lastFeedItems = await enrichGazetteItems(state.lastFeedItems);
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
  els.modalHero.style.backgroundImage = `url('${item.image_asset}')`;
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

  window.addEventListener('resize', syncFilterPanelVisibility);

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
