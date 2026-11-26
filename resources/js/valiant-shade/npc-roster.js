import $ from 'jquery';
import select2 from 'select2/dist/js/select2.full';
import 'select2/dist/css/select2.css';
import { mountNpcTitleTags } from './npc-title-tags.js';
import { mountNpcSubmitPreview } from './npc-submit-preview.js';

window.jQuery = window.$ = $;
select2($);

const API_BASE = document.body?.dataset?.apiBase || '/api/public/npcs';

const state = {
  view: 'list',
  status: '',
  race: '',
  profession: '',
  location: '',
  q: '',
  page: 1,
  loading: false,
  items: [],
  pagination: { current_page: 1, last_page: 1, total: 0 },
  filtersReady: false,
  detailNpc: null,
};

const els = {};
let submitSelectMounted = false;
let correctionSelectMounted = false;
let ignoreFacetChange = false;

const mqMobile = window.matchMedia('(max-width: 767px)');
const mqDesktop = window.matchMedia('(min-width: 768px)');

const select2Lang = {
  noResults: () => 'Sonuç yok',
  searching: () => 'Aranıyor…',
};

function esc(text) {
  return String(text ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function qs(id) {
  return document.getElementById(id);
}

function setModalOpen(open) {
  document.body.classList.toggle('vs-rumor-modal-open', open);
}

const SVG_VIEW_GRID =
  '<path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/>';
const SVG_VIEW_LIST =
  '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>';

function syncLayoutForViewport() {
  /* görünüm kullanıcı tercihi; varsayılan liste */
}

function setStatus(message, isError = false) {
  const el = els.status;
  if (!el) return;
  el.textContent = message || '';
  el.hidden = !message;
  el.classList.toggle('vs-npc-status--error', Boolean(isError));
}

const NPC_IMAGE_MAX_BYTES = 5 * 1024 * 1024;
const NPC_IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

const NPC_SUBMIT_FIELD_IDS = {
  first_name: 'npc-sub-first',
  mid_name: 'npc-sub-mid',
  last_name: 'npc-sub-last',
  status: 'npc-sub-status',
  profession: 'npc-sub-profession',
  race: 'npc-sub-race',
  faction: 'npc-sub-faction',
  location: 'npc-sub-location',
  summary: 'npc-sub-summary',
  background: 'npc-sub-background',
  image: 'npc-sub-image',
};

const NPC_CORRECTION_FIELD_IDS = {
  first_name: 'npc-cor-first',
  mid_name: 'npc-cor-mid',
  last_name: 'npc-cor-last',
  status: 'npc-cor-status',
  profession: 'npc-cor-profession',
  race: 'npc-cor-race',
  faction: 'npc-cor-faction',
  location: 'npc-cor-location',
  summary: 'npc-cor-summary',
  background: 'npc-cor-background',
  notes: 'npc-cor-notes',
  image: 'npc-cor-image',
};

function readNpcFormMessages(source) {
  const d = source?.dataset || {};
  return {
    sending: d.msgSending || 'Gönderiliyor…',
    imageTooLarge: d.msgImageTooLarge || 'Portre en fazla 5 MB olmalı.',
    imageType: d.msgImageType || 'JPEG, PNG veya WebP seçin.',
    imageOk: d.msgImageOk || 'Dosya uygun.',
    nameRequired: d.msgNameRequired || 'En az ad veya soyad gereklidir.',
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

function clearFormFieldErrors(form) {
  if (!form) return;
  form.querySelectorAll('.vs-form-field--error').forEach((node) => {
    node.classList.remove('vs-form-field--error');
  });
}

function applyFormErrors(form, errors, fieldIds) {
  if (!form || !errors) return;
  Object.keys(errors).forEach((key) => {
    const fieldId = fieldIds[key];
    if (!fieldId) return;
    const input = form.querySelector(`#${CSS.escape(fieldId)}`);
    const wrap = input?.closest('.vs-form-field');
    wrap?.classList.add('vs-form-field--error');
  });
}

function setFormFeedback(el, message, variant = 'info', errors = null) {
  if (!el) return;
  if (!message && !errors) {
    el.hidden = true;
    el.innerHTML = '';
    el.className = 'vs-npc-form-feedback vs-rumor-form__span';
    return;
  }
  el.hidden = false;
  el.className = `vs-npc-form-feedback vs-rumor-form__span vs-npc-form-feedback--${variant}`;
  let html = message ? esc(message) : '';
  if (errors) {
    const lines = Object.values(errors)
      .flat()
      .map((line) => String(line ?? '').trim())
      .filter(Boolean);
    if (lines.length) {
      html += `<ul class="vs-npc-form-feedback__list">${lines.map((line) => `<li>${esc(line)}</li>`).join('')}</ul>`;
    }
  }
  el.innerHTML = html;
  el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function displayNameFromForm(form, fieldIds) {
  return [
    form.querySelector(`#${fieldIds.first_name}`)?.value,
    form.querySelector(`#${fieldIds.mid_name}`)?.value,
    form.querySelector(`#${fieldIds.last_name}`)?.value,
  ]
    .map((v) => String(v ?? '').trim())
    .filter(Boolean)
    .join(' ');
}

function validateNpcNameAndImage(form, fieldIds, messages) {
  const errors = {};
  if (!displayNameFromForm(form, fieldIds)) {
    errors.first_name = [messages.nameRequired];
  }
  const imageId = fieldIds.image;
  const file = imageId ? form.querySelector(`#${CSS.escape(imageId)}`)?.files?.[0] : null;
  if (file) {
    if (!NPC_IMAGE_TYPES.has(file.type)) {
      errors.image = [messages.imageType];
    } else if (file.size > NPC_IMAGE_MAX_BYTES) {
      errors.image = [messages.imageTooLarge];
    }
  }
  return errors;
}

function bindImageFileMeta(inputId, metaId, messageSource) {
  const input = qs(inputId);
  const meta = qs(metaId);
  if (!input || !meta) return;
  const messages = readNpcFormMessages(messageSource);

  const refresh = () => {
    const file = input.files?.[0];
    meta.className = 'vs-npc-image-meta';
    if (!file) {
      meta.textContent = '';
      return;
    }
    let text = `${file.name} · ${formatFileSize(file.size)}`;
    if (!NPC_IMAGE_TYPES.has(file.type)) {
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
  };

  input.addEventListener('change', refresh);
  refresh();
}

function setSubmitBusy(btn, busy) {
  if (!btn) return;
  btn.disabled = busy;
  btn.setAttribute('aria-busy', busy ? 'true' : 'false');
}

async function postNpcForm({
  form,
  url,
  feedbackEl,
  submitBtn,
  messages,
  fieldIds,
  beforeReset,
  afterSuccess,
}) {
  clearFormFieldErrors(form);
  const clientErrors = validateNpcNameAndImage(form, fieldIds, messages);
  if (Object.keys(clientErrors).length) {
    setFormFeedback(feedbackEl, messages.fixFields, 'error', clientErrors);
    applyFormErrors(form, clientErrors, fieldIds);
    return;
  }

  const fd = new FormData(form);
  const titlesRaw = form.querySelector('[data-npc-title-hidden]')?.value;
  if (titlesRaw) fd.set('titles', titlesRaw);
  fd.delete('npc_id');

  setSubmitBusy(submitBtn, true);
  setFormFeedback(feedbackEl, messages.sending, 'info');

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(url, {
      method: 'POST',
      headers: token ? { 'X-CSRF-TOKEN': token, Accept: 'application/json' } : { Accept: 'application/json' },
      body: fd,
    });
    let payload = {};
    try {
      payload = await res.json();
    } catch {
      payload = {};
    }

    if (!res.ok || !payload.success) {
      if (payload.errors && typeof payload.errors === 'object') {
        setFormFeedback(feedbackEl, payload.message || messages.fixFields, 'error', payload.errors);
        applyFormErrors(form, payload.errors, fieldIds);
        return;
      }
      if (res.status === 429) {
        setFormFeedback(feedbackEl, payload.message || 'Çok fazla deneme. Lütfen bekleyin.', 'error');
        return;
      }
      setFormFeedback(feedbackEl, payload.message || messages.network, 'error');
      return;
    }

    const successMsg = payload.message || messages.success;
    setFormFeedback(feedbackEl, successMsg, 'success');
    setStatus(successMsg, false);
    await new Promise((r) => setTimeout(r, 1500));
    beforeReset?.();
    form.reset();
    setFormFeedback(feedbackEl, '', 'info');
    afterSuccess?.();
    return payload;
  } catch {
    setFormFeedback(feedbackEl, messages.network, 'error');
    setStatus(messages.network, true);
    return null;
  } finally {
    setSubmitBusy(submitBtn, false);
  }
}

function updateViewToggle() {
  const listMode = state.view === 'list';
  const listLabel = els.toggleView?.dataset.labelList || 'Liste görünümü';
  const gridLabel = els.toggleView?.dataset.labelGrid || 'Kutu görünümü';
  const nextLabel = listMode ? gridLabel : listLabel;

  els.toggleView?.setAttribute('aria-pressed', listMode ? 'true' : 'false');
  els.toggleView?.setAttribute('title', nextLabel);
  els.toggleView?.setAttribute('aria-label', nextLabel);

  if (els.toggleViewIcon) {
    els.toggleViewIcon.innerHTML = listMode ? SVG_VIEW_GRID : SVG_VIEW_LIST;
  }
}

function renderItems() {
  const grid = els.grid;
  if (!grid) return;
  grid.classList.toggle('vs-npc-grid--list', state.view === 'list');
  grid.replaceChildren();

  if (!state.items.length) {
    const empty = document.createElement('li');
    empty.className = 'vs-npc-empty';
    empty.textContent = document.body?.dataset?.msgEmpty || 'Kayıtlı kişi bulunamadı.';
    grid.appendChild(empty);
    return;
  }

  state.items.forEach((item) => {
    const li = document.createElement('li');
    li.className = 'vs-npc-grid__item';
    const card = document.createElement('button');
    card.type = 'button';
    card.className = `vs-npc-card${state.view === 'list' ? ' vs-npc-card--list' : ''}`;
    card.dataset.id = String(item.id);

    const media = item.image_url
      ? `<img class="vs-npc-card__portrait" src="${esc(item.image_url)}" alt="" loading="lazy" decoding="async">`
      : '<div class="vs-npc-card__portrait vs-npc-card__portrait--empty" aria-hidden="true"></div>';
    const titles = item.titles?.length ? `<div class="vs-npc-card__titles">${esc(item.titles.join(', '))}</div>` : '';
    const status = item.status_label ? `<span class="vs-npc-badge">${esc(item.status_label)}</span>` : '';
    const meta = [item.profession, item.race, item.location]
      .filter(Boolean)
      .map((v) => `<span class="vs-npc-badge">${esc(v)}</span>`)
      .join('');

    card.innerHTML = `
      <div class="vs-npc-card__media">${media}</div>
      <div class="vs-npc-card__body">
        <div class="vs-npc-card__head">
          <h3 class="vs-npc-card__name">${esc(item.display_name)}</h3>
        </div>
        ${titles}
        <div class="vs-npc-card__meta">${status}${meta}</div>
        <p class="vs-npc-card__excerpt">${esc(item.excerpt || '')}</p>
      </div>`;

    card.addEventListener('click', () => openDetail(item.id));
    li.appendChild(card);
    grid.appendChild(li);
  });
}

function buildQueryParams() {
  const params = new URLSearchParams();
  params.set('view', state.view);
  params.set('page', String(state.page));
  if (state.q) params.set('q', state.q);
  if (state.status) params.set('status', state.status);
  if (state.race) params.set('race', state.race);
  if (state.profession) params.set('profession', state.profession);
  if (state.location) params.set('location', state.location);

  return params;
}

function updatePager() {
  if (els.pageIndicator) {
    els.pageIndicator.textContent = `${state.pagination.current_page} / ${state.pagination.last_page || 1}`;
  }
  if (els.total) {
    els.total.textContent = String(state.pagination.total || 0);
  }
  if (els.prev) els.prev.disabled = state.page <= 1;
  if (els.next) els.next.disabled = state.page >= (state.pagination.last_page || 1);
}

async function fetchFacetOptions(field, term = '') {
  const params = new URLSearchParams({ field, q: term });
  const res = await fetch(`${API_BASE}/facet-suggestions?${params.toString()}`, {
    headers: { Accept: 'application/json' },
  });
  const payload = await res.json();
  if (!payload.success) {
    return [];
  }
  return payload.data || [];
}

function baseSelect2Options() {
  return {
    width: '100%',
    allowClear: true,
    dropdownParent: $(document.body),
    language: select2Lang,
  };
}

function mountRaceFilter() {
  if (!els.raceFilter || $(els.raceFilter).data('select2')) {
    return;
  }

  const $el = $(els.raceFilter);
  $el.select2({
    ...baseSelect2Options(),
    placeholder: $el.data('placeholder') || '',
    minimumInputLength: 0,
    ajax: {
      delay: 200,
      transport: (params, success, failure) => {
        fetchFacetOptions('race', params.data.q || '')
          .then((data) => success({ data }))
          .catch(failure);
      },
      data: (params) => ({ q: params.term || '' }),
      processResults: (payload) => ({
        results: (payload.data || []).map((text) => ({ id: text, text })),
      }),
    },
  });

  $el.on('change', () => {
    if (ignoreFacetChange) return;
    state.race = $el.val() || '';
    state.page = 1;
    loadBootstrap(false);
  });
}

function mountTaggedFacetFilter(el, field) {
  if (!el || $(el).data('select2')) {
    return;
  }

  const $el = $(el);
  $el.select2({
    ...baseSelect2Options(),
    placeholder: $el.data('placeholder') || '',
    tags: true,
    minimumInputLength: 0,
    createTag: (params) => {
      const term = (params.term || '').trim();
      if (term === '') {
        return null;
      }
      return { id: term, text: term, newTag: true };
    },
    ajax: {
      delay: 200,
      transport: (params, success, failure) => {
        fetchFacetOptions(field, params.data.q || '')
          .then((data) => success({ data }))
          .catch(failure);
      },
      data: (params) => ({ q: params.term || '' }),
      processResults: (payload) => ({
        results: (payload.data || []).map((text) => ({ id: text, text })),
      }),
    },
  });

  $el.on('change', () => {
    if (ignoreFacetChange) return;
    state[field] = $el.val() || '';
    state.page = 1;
    loadBootstrap(false);
  });
}

function mountSubmitRaceSelect() {
  const el = qs('npc-sub-race');
  if (!el || submitSelectMounted) {
    return;
  }
  const $el = $(el);
  $el.select2({
    ...baseSelect2Options(),
    placeholder: $el.data('placeholder') || '',
    dropdownParent: $('#npcSubmitModal'),
    minimumResultsForSearch: 0,
  });
  submitSelectMounted = true;
}

function mountCorrectionRaceSelect() {
  const el = qs('npc-cor-race');
  if (!el || correctionSelectMounted) {
    return;
  }
  const $el = $(el);
  $el.select2({
    ...baseSelect2Options(),
    placeholder: $el.data('placeholder') || '',
    dropdownParent: $('#npcDetailModal'),
    minimumResultsForSearch: 0,
  });
  correctionSelectMounted = true;
}

function detailLabels() {
  const modal = els.detailModal;
  if (!modal) {
    return {};
  }
  return {
    titles: modal.dataset.labelTitles || 'Unvanlar',
    status: modal.dataset.labelStatus || 'Durum',
    profession: modal.dataset.labelProfession || 'Meslek',
    race: modal.dataset.labelRace || 'Irk',
    faction: modal.dataset.labelFaction || 'Taraf',
    location: modal.dataset.labelLocation || 'Konum',
    summary: modal.dataset.labelSummary || 'Özet',
    background: modal.dataset.labelBackground || 'Geçmiş',
    notes: modal.dataset.labelNotes || 'Notlar',
  };
}

function detailFactRow(label, value) {
  if (!value) return '';
  return `<div class="vs-npc-detail-facts__row"><dt>${esc(label)}</dt><dd>${esc(value)}</dd></div>`;
}

function showDetailView() {
  if (els.detailView) els.detailView.hidden = false;
  if (els.correctionPanel) els.correctionPanel.hidden = true;
}

function renderDetail(npc) {
  state.detailNpc = npc;
  const labels = detailLabels();
  if (els.detailTitle) els.detailTitle.textContent = npc.display_name || '';

  let factsHtml = '';
  if (npc.titles?.length) {
    factsHtml += `<div class="vs-npc-detail-facts__row vs-npc-detail-facts__row--titles"><dt>${esc(labels.titles)}</dt><dd>${esc(npc.titles.join(', '))}</dd></div>`;
  }
  factsHtml += detailFactRow(labels.status, npc.status_label);
  factsHtml += detailFactRow(labels.profession, npc.profession);
  factsHtml += detailFactRow(labels.race, npc.race);
  factsHtml += detailFactRow(labels.faction, npc.faction);
  factsHtml += detailFactRow(labels.location, npc.location);
  if (els.detailFacts) els.detailFacts.innerHTML = factsHtml;

  const sections = [];
  if (npc.summary) {
    sections.push(
      `<section class="vs-npc-detail-section"><h3>${esc(labels.summary)}</h3><p>${esc(npc.summary)}</p></section>`,
    );
  }
  if (npc.background) {
    sections.push(
      `<section class="vs-npc-detail-section"><h3>${esc(labels.background)}</h3><p>${esc(npc.background)}</p></section>`,
    );
  }
  if (npc.notes) {
    sections.push(`<section class="vs-npc-detail-section"><h3>${esc(labels.notes)}</h3><p>${esc(npc.notes)}</p></section>`);
  }
  if (els.detailSections) els.detailSections.innerHTML = sections.join('');

  if (npc.image_url) {
    els.detailPortraitWrap?.classList.remove('is-empty');
    els.detailPortrait.src = npc.image_url;
    els.detailPortrait.alt = npc.display_name || '';
    els.detailPortrait.hidden = false;
  } else {
    els.detailPortraitWrap?.classList.add('is-empty');
    els.detailPortrait.hidden = true;
    els.detailPortrait.removeAttribute('src');
  }

  showDetailView();
}

function prefillCorrectionForm(npc) {
  const idField = qs('npc-cor-id');
  if (idField) idField.value = String(npc.id);
  if (els.corFirst) els.corFirst.value = npc.first_name || '';
  if (els.corMid) els.corMid.value = npc.mid_name || '';
  if (els.corLast) els.corLast.value = npc.last_name || '';
  if (els.corStatus) els.corStatus.value = npc.status != null && npc.status !== '' ? String(npc.status) : '';
  if (els.corProfession) els.corProfession.value = npc.profession || '';
  if (els.corFaction) els.corFaction.value = npc.faction || '';
  if (els.corLocation) els.corLocation.value = npc.location || '';
  if (els.corSummary) els.corSummary.value = npc.summary || '';
  if (els.corBackground) els.corBackground.value = npc.background || '';
  if (els.corNotes) els.corNotes.value = '';
  if (els.corImage) els.corImage.value = '';
  const raceEl = els.corRace;
  if (raceEl) {
    if (correctionSelectMounted) {
      $(raceEl).val(npc.race || null).trigger('change');
    } else {
      raceEl.value = npc.race || '';
    }
  }
}

function openCorrectionPanel() {
  if (!state.detailNpc) return;
  if (els.detailView) els.detailView.hidden = true;
  if (els.correctionPanel) els.correctionPanel.hidden = false;
  setFormFeedback(els.correctionFeedback, '', 'info');
  clearFormFieldErrors(els.correctionForm);
  prefillCorrectionForm(state.detailNpc);
  mountCorrectionRaceSelect();
  const tagsRoot = document.querySelector('[data-npc-correction-titles]');
  if (tagsRoot) {
    delete tagsRoot.dataset.npcTitleTagsMounted;
    tagsRoot.dataset.initial = JSON.stringify(state.detailNpc.titles || []);
    mountNpcTitleTags(tagsRoot);
  }
  els.correctionPanel?.scrollIntoView?.({ block: 'start', behavior: 'smooth' });
}

function initFacetFilters() {
  if (state.filtersReady) {
    return;
  }
  mountRaceFilter();
  mountTaggedFacetFilter(els.professionFilter, 'profession');
  mountTaggedFacetFilter(els.locationFilter, 'location');
  state.filtersReady = true;
}

async function loadBootstrap(reinitFilters = true) {
  if (state.loading) return;
  state.loading = true;
  setStatus(document.body?.dataset?.msgLoading || 'Kayıtlar yükleniyor…');
  try {
    const res = await fetch(`${API_BASE}/bootstrap?${buildQueryParams().toString()}`, {
      headers: { Accept: 'application/json' },
    });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'Yüklenemedi');
    state.items = payload.data || [];
    state.pagination = payload.pagination || state.pagination;
    state.page = state.pagination.current_page || 1;
    if (reinitFilters) {
      initFacetFilters();
    }
    renderItems();
    updatePager();
    setStatus('');
  } catch (err) {
    setStatus(err.message || 'Kayıtlar alınamadı.', true);
  } finally {
    state.loading = false;
  }
}

function openModal(modal) {
  if (!modal) return;
  modal.hidden = false;
  modal.setAttribute('aria-hidden', 'false');
  setModalOpen(true);
}

function closeModal(modal) {
  if (!modal) return;
  modal.hidden = true;
  modal.setAttribute('aria-hidden', 'true');
  const anyOpen = [els.detailModal, els.submitModal].some((m) => m && !m.hidden);
  setModalOpen(anyOpen);
}

async function openDetail(id) {
  if (!els.detailModal) return;
  try {
    const res = await fetch(`${API_BASE}/${id}`, { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'Kayıt bulunamadı');
    renderDetail(payload.npc);
    openModal(els.detailModal);
  } catch (err) {
    setStatus(err.message || 'Detay açılamadı.', true);
  }
}

function openSubmitModal() {
  if (!els.submitModal) return;
  setFormFeedback(els.submitFeedback, '', 'info');
  if (els.submitImageMeta) els.submitImageMeta.textContent = '';
  clearFormFieldErrors(els.submitForm);
  openModal(els.submitModal);
  document.querySelectorAll('[data-npc-title-tags]').forEach(mountNpcTitleTags);
  mountSubmitRaceSelect();
  if (els.submitForm) {
    els.submitForm.removeAttribute('data-submit-preview-mounted');
    const refresh = mountNpcSubmitPreview(els.submitForm);
    $('#npc-sub-race').off('change.npcPreview').on('change.npcPreview', () => refresh?.());
  }
}

function closeSubmitModal() {
  closeModal(els.submitModal);
}

function closeDetail() {
  showDetailView();
  state.detailNpc = null;
  closeModal(els.detailModal);
}

async function submitCorrection(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const npcId = state.detailNpc?.id || qs('npc-cor-id')?.value;
  if (!npcId) return;
  const messages = readNpcFormMessages(form);
  const payload = await postNpcForm({
    form,
    url: `${API_BASE}/${npcId}/corrections`,
    feedbackEl: els.correctionFeedback,
    submitBtn: els.correctionSubmitBtn,
    messages,
    fieldIds: NPC_CORRECTION_FIELD_IDS,
    afterSuccess: () => {
      if (els.correctionImageMeta) els.correctionImageMeta.textContent = '';
      closeDetail();
    },
  });
}

async function submitNpc(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const messages = readNpcFormMessages(els.submitModal);
  const payload = await postNpcForm({
    form,
    url: `${API_BASE}/submissions`,
    feedbackEl: els.submitFeedback,
    submitBtn: els.submitBtn,
    messages,
    fieldIds: NPC_SUBMIT_FIELD_IDS,
    beforeReset: () => {
      form.removeAttribute('data-submit-preview-mounted');
    },
    afterSuccess: () => {
      $('#npc-sub-race').val(null).trigger('change');
      if (els.submitImageMeta) els.submitImageMeta.textContent = '';
      closeSubmitModal();
    },
  });
}

function clearFilters() {
  ignoreFacetChange = true;
  state.status = '';
  state.race = '';
  state.profession = '';
  state.location = '';
  state.q = '';
  state.page = 1;
  if (els.search) els.search.value = '';
  if (els.statusFilter) els.statusFilter.value = '';
  $(els.raceFilter).val(null).trigger('change');
  $(els.professionFilter).val(null).trigger('change');
  $(els.locationFilter).val(null).trigger('change');
  ignoreFacetChange = false;
  loadBootstrap(false);
}

function init() {
  els.grid = qs('npcGrid');
  els.search = qs('npcSearchInput');
  els.statusFilter = qs('npcStatusFilter');
  els.raceFilter = qs('npcRaceFilter');
  els.professionFilter = qs('npcProfessionFilter');
  els.locationFilter = qs('npcLocationFilter');
  els.toggleFilters = qs('npcToggleFiltersBtn');
  els.filterPanel = qs('npcFilterPanel');
  els.clearFilters = qs('npcClearFiltersBtn');
  els.toggleView = qs('npcToggleView');
  els.toggleViewIcon = qs('npcToggleViewIcon');
  els.prev = qs('npcPagePrev');
  els.next = qs('npcPageNext');
  els.pageIndicator = qs('npcPageIndicator');
  els.total = qs('npcTotal');
  els.status = qs('npcStatusBanner');
  els.openSubmit = qs('openNpcSubmitModal');
  els.submitModal = qs('npcSubmitModal');
  els.submitForm = qs('npcSubmitForm');
  els.submitFeedback = qs('npcSubmitFormFeedback');
  els.submitBtn = qs('npcSubmitBtn');
  els.submitImageMeta = qs('npcSubmitImageMeta');
  els.closeSubmit = qs('npcSubmitClose');
  els.detailModal = qs('npcDetailModal');
  els.detailTitle = qs('npcDetailTitle');
  els.detailView = qs('npcDetailView');
  els.detailFacts = qs('npcDetailFacts');
  els.detailSections = qs('npcDetailSections');
  els.detailPortrait = qs('npcDetailPortrait');
  els.detailPortraitWrap = qs('npcDetailPortraitWrap');
  els.correctionPanel = qs('npcCorrectionPanel');
  els.correctionForm = qs('npcCorrectionForm');
  els.correctionFeedback = qs('npcCorrectionFormFeedback');
  els.correctionSubmitBtn = qs('npcCorrectionSubmitBtn');
  els.correctionImageMeta = qs('npcCorrectionImageMeta');
  els.openCorrection = qs('npcOpenCorrectionBtn');
  els.correctionBack = qs('npcCorrectionBackBtn');
  els.corFirst = qs('npc-cor-first');
  els.corMid = qs('npc-cor-mid');
  els.corLast = qs('npc-cor-last');
  els.corStatus = qs('npc-cor-status');
  els.corProfession = qs('npc-cor-profession');
  els.corRace = qs('npc-cor-race');
  els.corFaction = qs('npc-cor-faction');
  els.corLocation = qs('npc-cor-location');
  els.corSummary = qs('npc-cor-summary');
  els.corBackground = qs('npc-cor-background');
  els.corNotes = qs('npc-cor-notes');
  els.corImage = qs('npc-cor-image');
  els.closeDetail = qs('npcDetailClose');

  syncLayoutForViewport();
  updateViewToggle();

  window.addEventListener('resize', () => {
    syncLayoutForViewport();
    updateViewToggle();
    renderItems();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (els.submitModal && !els.submitModal.hidden) closeSubmitModal();
    else if (els.detailModal && !els.detailModal.hidden) closeDetail();
  });

  els.toggleView?.addEventListener('click', () => {
    state.view = state.view === 'grid' ? 'list' : 'grid';
    updateViewToggle();
    renderItems();
  });

  els.toggleFilters?.addEventListener('click', () => {
    const open = els.filterPanel?.hidden !== false;
    if (els.filterPanel) els.filterPanel.hidden = !open;
    els.toggleFilters.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  els.clearFilters?.addEventListener('click', clearFilters);

  const debounce = (fn, ms = 280) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  const reload = () => {
    state.page = 1;
    loadBootstrap(false);
  };

  els.search?.addEventListener(
    'input',
    debounce(() => {
      state.q = els.search.value.trim();
      reload();
    }),
  );

  els.statusFilter?.addEventListener('change', () => {
    state.status = els.statusFilter.value;
    reload();
  });

  els.openSubmit?.addEventListener('click', openSubmitModal);
  els.closeSubmit?.addEventListener('click', closeSubmitModal);
  els.submitForm?.addEventListener('submit', submitNpc);
  els.closeDetail?.addEventListener('click', closeDetail);
  document.querySelector('[data-close-npc-detail]')?.addEventListener('click', closeDetail);
  els.openCorrection?.addEventListener('click', openCorrectionPanel);
  els.correctionBack?.addEventListener('click', showDetailView);
  els.correctionForm?.addEventListener('submit', submitCorrection);
  document.querySelector('[data-close-npc-submit]')?.addEventListener('click', closeSubmitModal);

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

  bindImageFileMeta('npc-sub-image', 'npcSubmitImageMeta', els.submitModal);
  bindImageFileMeta('npc-cor-image', 'npcCorrectionImageMeta', els.correctionForm);

  loadBootstrap(true);
}

document.addEventListener('DOMContentLoaded', init);