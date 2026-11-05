const state = {
  page: 1,
  type: 'all',
  search: '',
  perPage: 9,
  sliderIndex: 0,
  featured: [],
  sliderTimer: null,
  viewMode: 'grid',
  filterTypes: [],
  filterTypeCounts: {},
  lastFeedItems: [],
};

const els = {};

function qs(id) {
  return document.getElementById(id);
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
    const dateText = escapeHtml(item.date_text || '');
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
          <span class="vs-news-list__meta">${escapeHtml(item.date_text || '')}</span>
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
          <small style="color:#94a3b8">${escapeHtml(item.date_text || '')}${item.location_badge ? ' • ' + escapeHtml(item.location_badge) : ''}</small>
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
  els.pageSheet.textContent = `Gazete Sayfası ${meta.current_page} / ${meta.last_page}`;
  els.prevPage.disabled = meta.current_page <= 1;
  els.nextPage.disabled = meta.current_page >= meta.last_page;
}

async function loadFeed() {
  els.newsGrid.innerHTML = '<div class="vs-loading">Haberler yükleniyor…</div>';
  const payload = await fetchBootstrap();
  renderSlider(payload.featured);
  renderFilters(payload.types, payload.feed.type_counts);
  state.lastFeedItems = payload.feed.data || [];
  renderGrid(state.lastFeedItems);
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
    const when = item.published_date || item.date_text || '';
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
      if (!isMobileGazetteLayout()) {
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
    const openBtn = event.target.closest('[data-open-id]');
    if (openBtn) {
      openArticle(openBtn.dataset.openId).catch(console.error);
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

  initNewsTypePicker(els.rumorTypePicker, els.rumorNewsType);
  try {
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
  syncFilterPanelVisibility();
  bindEvents();
  loadFeed().catch((error) => {
    els.newsGrid.innerHTML = `<div class="vs-loading">${error.message}</div>`;
  });
});
