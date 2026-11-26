const API_BASE = document.body?.dataset?.apiBase || '/api/public/gods';
const NPC_IMAGE_MAX_BYTES = 5 * 1024 * 1024;
const IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

const state = {
  view: 'list',
  q: '',
  category: '',
  alignment: '',
  domain: '',
  page: 1,
  loading: false,
  items: [],
  facets: { categories: [], alignments: [], domains: [] },
  pagination: { current_page: 1, last_page: 1, total: 0 },
  detailGod: null,
};

const els = {};

function esc(text) {
  return String(text ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function qs(id) {
  return document.getElementById(id);
}

function setModalOpen(open) {
  document.body.classList.toggle('vs-rumor-modal-open', open);
}

function setStatus(message, isError = false) {
  const el = els.status;
  if (!el) return;
  el.textContent = message || '';
  el.hidden = !message;
  el.classList.toggle('vs-npc-status--error', Boolean(isError));
}

function readMessages(source) {
  const d = source?.dataset || {};
  return {
    sending: d.msgSending || 'Gönderiliyor…',
    imageTooLarge: d.msgImageTooLarge || 'Görsel en fazla 5 MB olmalı.',
    imageType: d.msgImageType || 'JPEG, PNG veya WebP seçin.',
    imageOk: d.msgImageOk || 'Dosya uygun.',
    nameRequired: d.msgNameRequired || 'Tanrı adı gerekli.',
    fixFields: d.msgFixFields || 'Lütfen işaretli alanları düzeltin.',
    success: d.msgSuccess || 'Kayıt arşivcilere ulaştı.',
    network: d.msgNetwork || 'Gönderilemedi.',
  };
}

function formatFileSize(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function setFormFeedback(el, message, variant = 'info', errors = null) {
  if (!el) return;
  if (!message && !errors) {
    el.hidden = true;
    el.innerHTML = '';
    return;
  }
  el.hidden = false;
  el.className = `vs-npc-form-feedback vs-rumor-form__span vs-npc-form-feedback--${variant}`;
  let html = message ? esc(message) : '';
  if (errors) {
    const lines = Object.values(errors).flat().filter(Boolean);
    if (lines.length) {
      html += `<ul class="vs-npc-form-feedback__list">${lines.map((l) => `<li>${esc(l)}</li>`).join('')}</ul>`;
    }
  }
  el.innerHTML = html;
  el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function clearFormFieldErrors(form) {
  form?.querySelectorAll('.vs-form-field--error').forEach((n) => n.classList.remove('vs-form-field--error'));
}

function applyFormErrors(form, errors, fieldIds) {
  Object.keys(errors || {}).forEach((key) => {
    const id = fieldIds[key];
    if (!id) return;
    form.querySelector(`#${CSS.escape(id)}`)?.closest('.vs-form-field')?.classList.add('vs-form-field--error');
  });
}

function bindImageMeta(inputId, metaId, messageSource) {
  const input = qs(inputId);
  const meta = qs(metaId);
  if (!input || !meta) return;
  const messages = readMessages(messageSource);
  input.addEventListener('change', () => {
    const file = input.files?.[0];
    meta.className = 'vs-npc-image-meta';
    if (!file) {
      meta.textContent = '';
      return;
    }
    let text = `${file.name} · ${formatFileSize(file.size)}`;
    if (!IMAGE_TYPES.has(file.type)) {
      text += ` — ${messages.imageType}`;
      meta.classList.add('vs-npc-image-meta--warn');
    } else if (file.size > NPC_IMAGE_MAX_BYTES) {
      text += ` — ${messages.imageTooLarge}`;
      meta.classList.add('vs-npc-image-meta--warn');
    } else {
      text += ` — ${messages.imageOk}`;
      meta.classList.add('vs-npc-image-meta--ok');
    }
    meta.textContent = text;
  });
}

function buildQuery() {
  const p = new URLSearchParams();
  if (state.q) p.set('q', state.q);
  if (state.category) p.set('category', state.category);
  if (state.alignment) p.set('alignment', state.alignment);
  if (state.domain) p.set('domain', state.domain);
  p.set('view', state.view);
  p.set('page', String(state.page));
  return p;
}

function renderItems() {
  const grid = els.grid;
  if (!grid) return;
  grid.classList.toggle('vs-npc-grid--list', state.view === 'list');
  grid.replaceChildren();
  if (!state.items.length) {
    const li = document.createElement('li');
    li.className = 'vs-npc-empty';
    li.textContent = document.body?.dataset?.msgEmpty || 'Kayıt bulunamadı.';
    grid.appendChild(li);
    return;
  }
  state.items.forEach((item) => {
    const li = document.createElement('li');
    li.className = 'vs-npc-grid__item';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `vs-npc-card${state.view === 'list' ? ' vs-npc-card--list' : ''}`;
    btn.dataset.slug = item.slug;
    const media = item.image_url
      ? `<img class="vs-npc-card__portrait" src="${esc(item.image_url)}" alt="" loading="lazy">`
      : '<div class="vs-npc-card__portrait vs-npc-card__portrait--empty"></div>';
    const meta = [item.alignment, item.category_label, ...(item.domains_preview || [])]
      .filter(Boolean)
      .map((v) => `<span class="vs-npc-badge">${esc(v)}</span>`)
      .join('');
    btn.innerHTML = `
      <div class="vs-npc-card__media">${media}</div>
      <div class="vs-npc-card__body">
        <h3 class="vs-npc-card__name">${esc(item.name)}</h3>
        ${item.epithet ? `<div class="vs-npc-card__titles">${esc(item.epithet)}</div>` : ''}
        <div class="vs-npc-card__meta">${meta}</div>
        <p class="vs-npc-card__excerpt">${esc(item.excerpt || '')}</p>
      </div>`;
    btn.addEventListener('click', () => openDetail(item.slug));
    li.appendChild(btn);
    grid.appendChild(li);
  });
}

function fillFilters() {
  if (els.alignmentFilter) {
    const cur = state.alignment;
    els.alignmentFilter.innerHTML = `<option value="">${esc(document.body?.dataset?.labelAllAlignments || 'Tüm hizalamalar')}</option>`;
    (state.facets.alignments || []).forEach((a) => {
      const opt = document.createElement('option');
      opt.value = a;
      opt.textContent = a;
      els.alignmentFilter.appendChild(opt);
    });
    els.alignmentFilter.value = cur;
  }
  if (els.domainFilter) {
    const cur = state.domain;
    els.domainFilter.innerHTML = `<option value="">${esc(document.body?.dataset?.labelAllDomains || 'Tüm alanlar')}</option>`;
    (state.facets.domains || []).forEach((d) => {
      const opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      els.domainFilter.appendChild(opt);
    });
    els.domainFilter.value = cur;
  }
}

async function loadBootstrap(reinit = true) {
  if (state.loading) return;
  state.loading = true;
  setStatus(document.body?.dataset?.msgLoading || 'Yükleniyor…');
  try {
    const res = await fetch(`${API_BASE}/bootstrap?${buildQuery()}`, { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'Yüklenemedi');
    state.items = payload.data || [];
    state.facets = payload.facets || state.facets;
    state.pagination = payload.pagination || state.pagination;
    state.page = state.pagination.current_page || 1;
    if (reinit) fillFilters();
    renderItems();
    if (els.total) els.total.textContent = String(state.pagination.total || 0);
    if (els.pageIndicator) {
      els.pageIndicator.textContent = `${state.pagination.current_page || 1} / ${state.pagination.last_page || 1}`;
    }
    setStatus('');
  } catch (err) {
    setStatus(err.message || 'Liste alınamadı.', true);
  } finally {
    state.loading = false;
  }
}

function renderDetail(god) {
  state.detailGod = god;
  if (els.detailTitle) els.detailTitle.textContent = god.name || '';
  const rows = [
    ['epithet', god.epithet],
    ['alignment', god.alignment],
    ['category', god.category_label],
    ['weapon', god.weapon],
    ['domains', (god.domains || []).join(', ')],
    ['subdomains', (god.subdomains || []).join(', ')],
  ];
  const d = els.detailModal?.dataset || {};
  const label = (key) => d[`label${key.charAt(0).toUpperCase()}${key.slice(1)}`] || key;
  if (els.detailFacts) {
    els.detailFacts.innerHTML = rows
      .filter(([, v]) => v)
      .map(
        ([key, val]) =>
          `<div class="vs-npc-detail-facts__row"><dt>${esc(label(key))}</dt><dd>${esc(val)}</dd></div>`,
      )
      .join('');
  }
  const sections = [];
  if (god.summary) sections.push(`<section class="vs-npc-detail-section"><h3>${esc(d.labelSummary || 'Özet')}</h3><p>${esc(god.summary)}</p></section>`);
  if (god.profile_text) sections.push(`<section class="vs-npc-detail-section"><h3>${esc(d.labelProfile || 'Profil')}</h3><p>${esc(god.profile_text)}</p></section>`);
  if (els.detailSections) els.detailSections.innerHTML = sections.join('');

  if (god.image_url) {
    els.detailPortrait.hidden = false;
    els.detailPortrait.src = god.image_url;
    els.detailPortraitWrap?.classList.remove('is-empty');
  } else {
    els.detailPortrait.hidden = true;
    els.detailPortraitWrap?.classList.add('is-empty');
  }
  showDetailView();
}

async function openDetail(slug) {
  try {
    const res = await fetch(`${API_BASE}/${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'Bulunamadı');
    renderDetail(payload.god);
    openModal(els.detailModal);
  } catch (err) {
    setStatus(err.message, true);
  }
}

function openModal(modal) {
  modal.hidden = false;
  setModalOpen(true);
}

function closeModal(modal) {
  modal.hidden = true;
  setModalOpen([els.detailModal, els.submitModal].some((m) => m && !m.hidden));
}

function showDetailView() {
  if (els.detailView) els.detailView.hidden = false;
  if (els.correctionPanel) els.correctionPanel.hidden = true;
}

function prefillCorrection(form, god) {
  qs('god-cor-slug').value = god.slug;
  qs('god-cor-name').value = god.name || '';
  qs('god-cor-epithet').value = god.epithet || '';
  qs('god-cor-alignment').value = god.alignment || '';
  qs('god-cor-category').value = god.category || 'minor';
  qs('god-cor-weapon').value = god.weapon || '';
  qs('god-cor-domains').value = (god.domains || []).join('\n');
  qs('god-cor-subdomains').value = (god.subdomains || []).join('\n');
  qs('god-cor-summary').value = god.summary || '';
  qs('god-cor-profile').value = god.profile_text || '';
}

const SUBMIT_FIELDS = {
  name: 'god-sub-name',
  epithet: 'god-sub-epithet',
  alignment: 'god-sub-alignment',
  category: 'god-sub-category',
  weapon: 'god-sub-weapon',
  domains: 'god-sub-domains',
  subdomains: 'god-sub-subdomains',
  summary: 'god-sub-summary',
  profile_text: 'god-sub-profile',
  image: 'god-sub-image',
};

const CORRECTION_FIELDS = {
  name: 'god-cor-name',
  epithet: 'god-cor-epithet',
  alignment: 'god-cor-alignment',
  category: 'god-cor-category',
  weapon: 'god-cor-weapon',
  domains: 'god-cor-domains',
  subdomains: 'god-cor-subdomains',
  summary: 'god-cor-summary',
  profile_text: 'god-cor-profile',
  image: 'god-cor-image',
};

async function postForm({ form, url, feedback, btn, messages, fieldIds, afterSuccess }) {
  clearFormFieldErrors(form);
  const name = form.querySelector('#god-sub-name, #god-cor-name')?.value?.trim();
  if (!name) {
    setFormFeedback(feedback, messages.fixFields, 'error', { name: [messages.nameRequired] });
    applyFormErrors(form, { name: [''] }, fieldIds);
    return;
  }
  const file = form.querySelector('[type=file][name=image]')?.files?.[0];
  if (file && (!IMAGE_TYPES.has(file.type) || file.size > NPC_IMAGE_MAX_BYTES)) {
    setFormFeedback(feedback, messages.fixFields, 'error', {
      image: [!IMAGE_TYPES.has(file.type) ? messages.imageType : messages.imageTooLarge],
    });
    return;
  }

  setFormFeedback(feedback, messages.sending, 'info');
  btn && (btn.disabled = true);
  try {
    const fd = new FormData(form);
    fd.delete('god_slug');
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(url, {
      method: 'POST',
      headers: token ? { 'X-CSRF-TOKEN': token, Accept: 'application/json' } : { Accept: 'application/json' },
      body: fd,
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || !payload.success) {
      setFormFeedback(feedback, payload.message || messages.fixFields, 'error', payload.errors);
      applyFormErrors(form, payload.errors || {}, fieldIds);
      return;
    }
    setFormFeedback(feedback, payload.message || messages.success, 'success');
    setStatus(payload.message || messages.success, false);
    await new Promise((r) => setTimeout(r, 1200));
    form.reset();
    setFormFeedback(feedback, '', 'info');
    afterSuccess?.();
  } catch {
    setFormFeedback(feedback, messages.network, 'error');
  } finally {
    btn && (btn.disabled = false);
  }
}

function init() {
  els.grid = qs('godGrid');
  els.search = qs('godSearchInput');
  els.categoryFilter = qs('godCategoryFilter');
  els.alignmentFilter = qs('godAlignmentFilter');
  els.domainFilter = qs('godDomainFilter');
  els.toggleFilters = qs('godToggleFiltersBtn');
  els.filterPanel = qs('godFilterPanel');
  els.clearFilters = qs('godClearFiltersBtn');
  els.total = qs('godTotal');
  els.status = qs('godStatusBanner');
  els.pageIndicator = qs('godPageIndicator');
  els.prev = qs('godPagePrev');
  els.next = qs('godPageNext');
  els.submitModal = qs('godSubmitModal');
  els.submitForm = qs('godSubmitForm');
  els.submitFeedback = qs('godSubmitFormFeedback');
  els.submitBtn = qs('godSubmitBtn');
  els.detailModal = qs('godDetailModal');
  els.detailTitle = qs('godDetailTitle');
  els.detailView = qs('godDetailView');
  els.detailFacts = qs('godDetailFacts');
  els.detailSections = qs('godDetailSections');
  els.detailPortrait = qs('godDetailPortrait');
  els.detailPortraitWrap = qs('godDetailPortraitWrap');
  els.correctionPanel = qs('godCorrectionPanel');
  els.correctionForm = qs('godCorrectionForm');
  els.correctionFeedback = qs('godCorrectionFormFeedback');
  els.openSubmit = qs('openGodSubmitModal');
  els.openCorrection = qs('godOpenCorrectionBtn');

  const reload = () => {
    state.page = 1;
    loadBootstrap(false);
  };

  els.search?.addEventListener('input', debounce(() => {
    state.q = els.search.value.trim();
    reload();
  }));

  [els.categoryFilter, els.alignmentFilter, els.domainFilter].forEach((el) => {
    el?.addEventListener('change', () => {
      state.category = els.categoryFilter?.value || '';
      state.alignment = els.alignmentFilter?.value || '';
      state.domain = els.domainFilter?.value || '';
      reload();
    });
  });

  els.toggleFilters?.addEventListener('click', () => {
    const open = els.filterPanel?.hidden !== false;
    els.filterPanel.hidden = !open;
    els.toggleFilters.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  els.clearFilters?.addEventListener('click', () => {
    state.category = state.alignment = state.domain = state.q = '';
    state.page = 1;
    if (els.search) els.search.value = '';
    [els.categoryFilter, els.alignmentFilter, els.domainFilter].forEach((el) => {
      if (el) el.value = '';
    });
    loadBootstrap(true);
  });

  els.prev?.addEventListener('click', () => {
    if (state.page > 1) {
      state.page -= 1;
      loadBootstrap(false);
    }
  });
  els.next?.addEventListener('click', () => {
    if (state.page < (state.pagination.last_page || 1)) {
      state.page += 1;
      loadBootstrap(false);
    }
  });

  els.openSubmit?.addEventListener('click', () => {
    setFormFeedback(els.submitFeedback, '', 'info');
    openModal(els.submitModal);
  });
  els.submitForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    postForm({
      form: els.submitForm,
      url: `${API_BASE}/submissions`,
      feedback: els.submitFeedback,
      btn: els.submitBtn,
      messages: readMessages(els.submitModal),
      fieldIds: SUBMIT_FIELDS,
      afterSuccess: () => closeModal(els.submitModal),
    });
  });

  els.openCorrection?.addEventListener('click', () => {
    if (!state.detailGod) return;
    els.detailView.hidden = true;
    els.correctionPanel.hidden = false;
    prefillCorrection(els.correctionForm, state.detailGod);
    setFormFeedback(els.correctionFeedback, '', 'info');
  });
  qs('godCorrectionBackBtn')?.addEventListener('click', showDetailView);
  els.correctionForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const slug = state.detailGod?.slug;
    if (!slug) return;
    postForm({
      form: els.correctionForm,
      url: `${API_BASE}/${encodeURIComponent(slug)}/corrections`,
      feedback: els.correctionFeedback,
      btn: qs('godCorrectionSubmitBtn'),
      messages: readMessages(els.correctionForm),
      fieldIds: CORRECTION_FIELDS,
      afterSuccess: () => {
        closeModal(els.detailModal);
        showDetailView();
      },
    });
  });

  document.querySelector('[data-close-god-detail]')?.addEventListener('click', () => closeModal(els.detailModal));
  document.querySelector('[data-close-god-submit]')?.addEventListener('click', () => closeModal(els.submitModal));
  qs('godDetailClose')?.addEventListener('click', () => closeModal(els.detailModal));
  qs('godSubmitClose')?.addEventListener('click', () => closeModal(els.submitModal));

  bindImageMeta('god-sub-image', 'godSubmitImageMeta', els.submitModal);
  bindImageMeta('god-cor-image', 'godCorrectionImageMeta', els.correctionForm);

  loadBootstrap(true);
}

function debounce(fn, ms = 280) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

document.addEventListener('DOMContentLoaded', init);