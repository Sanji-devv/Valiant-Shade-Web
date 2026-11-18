import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const PUBLICATION_GNOME = 'gnome_ciglisi';
const PUBLICATION_LAGIM = 'lagimdan_sesler';

const GNOME_FIELD_ERROR_EL = {
  title: 'gnomeCryTitleFieldError',
  body: 'gnomeCryBodyError',
  rumuz: 'gnomeCryRumuzFieldError',
  image: 'gnomeCryImageFieldError',
};

const GNOME_SERVER_FIELD_TO_CLIENT = {
  title: 'title',
  full_story: 'body',
  rumuz: 'rumuz',
  custom_image: 'image',
  deck_line: 'title',
};

export function isGnomePublication(state) {
  return state?.publicationMode === PUBLICATION_GNOME;
}

function parseBrandFromDom() {
  const raw = document.body?.dataset?.gazetteGnomeBrand;
  if (!raw) {
    return {};
  }
  try {
    return JSON.parse(raw);
  } catch {
    return {};
  }
}

function plainTextFromHtml(html) {
  const el = document.createElement('div');
  el.innerHTML = html;
  return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
}

function isMobileGnomeFeed() {
  return window.matchMedia('(max-width: 767px)').matches;
}

export function createGnomeCryController({
  els,
  state,
  escapeHtml,
  showToast,
  loadFeed,
  applyLayoutMode,
  renderStreamCard,
  rememberLagimLayoutBeforeGnome,
  restoreLagimLayoutAfterGnome,
}) {
  const brand = parseBrandFromDom();
  const submissionCopy = brand.submission || {};

  function cornerRailHtml(item) {
    const railTitle = escapeHtml(brand.corner_title || 'Köşe notu');
    const storyPlain = plainTextFromHtml(String(item?.full_story || item?.description || ''));
    const deckPlain = plainTextFromHtml(String(item?.deck_line || ''));
    let railText = '';
    if (storyPlain.length > deckPlain.length + 120) {
      const tail = storyPlain.slice(Math.min(380, Math.floor(storyPlain.length * 0.45)));
      railText = tail.length > 40 ? tail.slice(0, 520) : '';
    }
    if (!railText && item?.description) {
      railText = plainTextFromHtml(String(item.description)).slice(0, 480);
    }
    if (!railText) {
      railText = brand.motto || 'Lonca duvarına asılacak tek yaprak.';
    }
    if (railText.length > 500) {
      railText = `${railText.slice(0, 497)}…`;
    }
    return `
      <aside class="vs-gnome-cry-sheet__rail" aria-label="Köşe sütunu">
        <p class="vs-gnome-cry-sheet__rail-kicker">${railTitle}</p>
        <div class="vs-gnome-cry-sheet__rail-body">${escapeHtml(railText).replace(/\n/g, '<br>')}</div>
      </aside>`;
  }
  let gnomeQuill = null;
  let gnomeImageObjectUrl = null;
  let lastPayload = null;

  function setGnomeFieldErrorById(id, message) {
    const el = document.getElementById(id);
    if (!el) {
      return;
    }
    const text = (message || '').trim();
    el.hidden = text === '';
    el.textContent = text;
  }

  function setGnomeFieldError(key, message) {
    const id = GNOME_FIELD_ERROR_EL[key];
    if (id) {
      setGnomeFieldErrorById(id, message);
    }
  }

  function clearGnomeFieldErrors() {
    Object.values(GNOME_FIELD_ERROR_EL).forEach((id) => setGnomeFieldErrorById(id, ''));
    document.querySelectorAll('#gnomeCryForm .vs-form-field--invalid').forEach((node) => {
      node.classList.remove('vs-form-field--invalid');
    });
    els.gnomeCryEditorHost?.classList.remove('vs-form-field--invalid');
  }

  function applyGnomeServerValidationErrors(payload) {
    const errors = payload?.errors;
    if (!errors || typeof errors !== 'object') {
      return false;
    }
    let applied = false;
    Object.entries(errors).forEach(([apiField, messages]) => {
      const clientKey = GNOME_SERVER_FIELD_TO_CLIENT[apiField];
      if (!clientKey) {
        return;
      }
      const msg = Array.isArray(messages) ? messages[0] : messages;
      if (!msg) {
        return;
      }
      setGnomeFieldError(clientKey, String(msg));
      applied = true;
    });
    return applied;
  }

  function scrollGnomeToField(el) {
    if (!el) {
      return;
    }
    if (el === els.gnomeCryEditor || el?.classList?.contains('vs-gnome-cry-editor')) {
      els.gnomeCryEditorHost?.classList.add('vs-form-field--invalid');
      els.gnomeCryEditorHost?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      gnomeQuill?.focus();
      return;
    }
    el.classList.add('vs-form-field--invalid');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (typeof el.focus === 'function') {
      el.focus({ preventScroll: true });
    }
  }

  function validateGnomeClientForm() {
    const errors = [];
    clearGnomeFieldErrors();

    const title = (els.gnomeCryTitle?.value || '').trim();
    if (title.length < 8) {
      const shortBy = 8 - title.length;
      errors.push({
        key: 'title',
        message: shortBy > 0
          ? `Başlık en az 8 karakter olmalı (${shortBy} karakter daha yazın).`
          : 'Başlık en az 8 karakter olmalı.',
        el: els.gnomeCryTitle,
      });
    }

    const rumuz = (els.gnomeCryRumuz?.value || '').trim();
    if (rumuz.length < 2) {
      errors.push({
        key: 'rumuz',
        message: 'Lütfen temsilci rumuzunu girin (en az 2 karakter).',
        el: els.gnomeCryRumuz,
      });
    }

    const storyHtml = syncQuillToHidden();
    const plainLen = plainTextFromHtml(storyHtml).length;
    const minBody = 30;
    if (plainLen < minBody) {
      const shortBy = minBody - plainLen;
      errors.push({
        key: 'body',
        message: `Duyuru metni en az ${minBody} karakter olmalı (${shortBy} karakter daha yazın).`,
        el: els.gnomeCryEditor,
      });
    }

    const file = els.gnomeCryCustomImage?.files?.[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        errors.push({
          key: 'image',
          message: 'Görsel 5 MB sınırını aşıyor.',
          el: els.gnomeCryCustomImage,
        });
      }
      const okTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
      if (!okTypes.includes(file.type)) {
        errors.push({
          key: 'image',
          message: 'Görsel JPEG, PNG veya WebP olmalı.',
          el: els.gnomeCryCustomImage,
        });
      }
    }

    errors.forEach((entry) => {
      if (entry.key) {
        setGnomeFieldError(entry.key, entry.message);
      }
    });

    if (errors.length) {
      const first = errors[0];
      scrollGnomeToField(first.el);
      const extra = errors.length > 1 ? ` (+${errors.length - 1} alan daha)` : '';
      showToast(`${first.message}${extra}`, 'warning');
      return false;
    }

    return true;
  }

  function ensureGnomeEditor() {
    if (gnomeQuill || !els.gnomeCryEditor || !els.gnomeCryEditorToolbar) {
      return gnomeQuill;
    }
    gnomeQuill = new Quill(els.gnomeCryEditor, {
      theme: 'snow',
      modules: {
        toolbar: els.gnomeCryEditorToolbar,
      },
      placeholder: submissionCopy.body_placeholder || 'Lonca üyelerine iletilecek yazı…',
    });
    gnomeQuill.on('text-change', () => {
      setGnomeFieldError('body', '');
      els.gnomeCryEditorHost?.classList.remove('vs-form-field--invalid');
    });
    return gnomeQuill;
  }

  function syncQuillToHidden() {
    const editor = ensureGnomeEditor();
    if (!editor || !els.gnomeCryFullStory) {
      return '';
    }
    const html = editor.root.innerHTML;
    const empty = html === '<p><br></p>' || !plainTextFromHtml(html);
    els.gnomeCryFullStory.value = empty ? '' : html;
    return els.gnomeCryFullStory.value;
  }

  function resetGnomeImagePreview() {
    if (gnomeImageObjectUrl) {
      URL.revokeObjectURL(gnomeImageObjectUrl);
      gnomeImageObjectUrl = null;
    }
    if (els.gnomeCryImagePreviewWrap) {
      els.gnomeCryImagePreviewWrap.hidden = true;
    }
    if (els.gnomeCryCustomImage) {
      els.gnomeCryCustomImage.value = '';
    }
  }

  function showGnomeImagePreview(file) {
    if (!file || !els.gnomeCryImagePreview || !els.gnomeCryImagePreviewWrap) {
      return;
    }
    resetGnomeImagePreview();
    gnomeImageObjectUrl = URL.createObjectURL(file);
    els.gnomeCryImagePreview.src = gnomeImageObjectUrl;
    els.gnomeCryImagePreviewWrap.hidden = false;
  }

  function buildParchmentHtml(item, { empty = false, printId = null } = {}) {
    const press = escapeHtml(brand.press_name || 'Gnome Basın Loncası — mühürlü tek nüsha');
    const brandTitle = escapeHtml(brand.title || "Gnome'un Çığlığı");
    const gnomeIconUrl = String(document.body?.dataset?.gazetteGnomeIcon || brand.icon || '').trim();
    const mastLogo = gnomeIconUrl
      ? `<img class="vs-gnome-cry-sheet__logo" src="${escapeHtml(gnomeIconUrl)}" width="44" height="44" alt="" decoding="async">`
      : '';
    const edition = item
      ? `<p class="vs-gnome-cry-sheet__edition">Sayı ${escapeHtml(String(item.gnome_edition_number || '—'))}</p>`
      : '<p class="vs-gnome-cry-sheet__edition">Sayı —</p>';

    if (empty || !item) {
      return `
        <article class="vs-gnome-cry-sheet vs-gnome-cry-sheet--folio vs-gnome-cry-sheet--blank" ${printId ? `id="${printId}"` : ''}>
          <div class="vs-gnome-cry-sheet__frame" aria-hidden="true"></div>
          <div class="vs-gnome-cry-sheet__wax" aria-hidden="true"></div>
          <header class="vs-gnome-cry-sheet__mast">
            <div class="vs-gnome-cry-sheet__mast-brand">
              ${mastLogo}
              <p class="vs-gnome-cry-sheet__brand">${brandTitle}</p>
              <p class="vs-gnome-cry-sheet__press">${press}</p>
              ${edition}
            </div>
          </header>
          <p class="vs-gnome-cry-sheet__placeholder">
            Henüz mühürlü parşömen basılmadı. Lonca temsilcisi temsilci sözü gönderebilir; onaylanınca burada tek sayfa olarak görünür.
          </p>
          <footer class="vs-gnome-cry-sheet__foot">
            <p class="vs-gnome-cry-sheet__seal">Mühürlü tek nüsha · lonca duyurusu · halka arz değildir</p>
          </footer>
        </article>`;
    }

    const image = item.image_asset
      ? `<figure class="vs-gnome-cry-sheet__figure"><img src="${escapeHtml(item.image_asset)}" alt="" loading="lazy" decoding="async"></figure>`
      : '';
    const kicker = escapeHtml(item.news_type_label || item.category || 'Lonca duyurusu');
    const deck = item.deck_line
      ? `<p class="vs-gnome-cry-sheet__deck">${escapeHtml(item.deck_line)}</p>`
      : '';
    const storyHtml = String(item.full_story || '').trim();
    const bodyBlock = storyHtml
      ? `<div class="vs-gnome-cry-sheet__body vs-gnome-cry-sheet__body--rich">${storyHtml}</div>`
      : `<div class="vs-gnome-cry-sheet__body">${escapeHtml(item.description || '').replace(/\n/g, '<br>')}</div>`;
    const stamp = escapeHtml(item.world_date_text || item.date_text || '');
    const author = escapeHtml(item.author || 'Lonca temsilcisi');
    const role = escapeHtml(item.author_role || '');
    const title = escapeHtml(item.title || '');

    return `
      <article class="vs-gnome-cry-sheet vs-gnome-cry-sheet--folio" ${printId ? `id="${printId}"` : ''} data-edition-id="${item.id}">
        <div class="vs-gnome-cry-sheet__frame" aria-hidden="true"></div>
        <div class="vs-gnome-cry-sheet__wax" aria-hidden="true"></div>
        <header class="vs-gnome-cry-sheet__mast">
          <div class="vs-gnome-cry-sheet__mast-brand">
            ${mastLogo}
            <p class="vs-gnome-cry-sheet__brand">${brandTitle}</p>
            <p class="vs-gnome-cry-sheet__press">${press}</p>
            <p class="vs-gnome-cry-sheet__stamp">${stamp}</p>
            ${edition}
          </div>
        </header>
        <div class="vs-gnome-cry-sheet__spread">
          <div class="vs-gnome-cry-sheet__main">
            <p class="vs-gnome-cry-sheet__kicker">${kicker}</p>
            <h1 class="vs-gnome-cry-sheet__title">${title}</h1>
            ${deck}
            ${image}
            ${bodyBlock}
          </div>
          ${cornerRailHtml(item)}
        </div>
        <footer class="vs-gnome-cry-sheet__foot">
          <p class="vs-gnome-cry-sheet__sign">${author}</p>
          ${role ? `<p class="vs-gnome-cry-sheet__role">${role}</p>` : ''}
          <p class="vs-gnome-cry-sheet__seal">Mühürlü tek nüsha · lonca duyurusu</p>
        </footer>
      </article>`;
  }

  function isGnomeDesktopSheet() {
    return isGnomePublication(state) && !isMobileGnomeFeed();
  }

  function syncGnomeViewChrome() {
    if (!isGnomePublication(state)) {
      return;
    }
    const mobileFeed = isMobileGnomeFeed();
    document.body.classList.toggle('vs-gnome-mobile-feed', mobileFeed);
    document.body.classList.toggle('vs-gnome-desktop-sheet', !mobileFeed);
    if (els.gnomeCryFlow) {
      els.gnomeCryFlow.hidden = !mobileFeed;
      els.gnomeCryFlow.classList.toggle('vs-gnome-cry-flow--cards', mobileFeed);
    }
    if (els.gnomeCryStage) {
      els.gnomeCryStage.hidden = mobileFeed;
    }
    if (els.pagination) {
      els.pagination.hidden = mobileFeed;
    }
    if (els.gazetteFlip) {
      els.gazetteFlip.hidden = mobileFeed;
    }
    if (els.pageHint) {
      els.pageHint.hidden = mobileFeed;
    }
  }

  function syncGnomeToolbarChrome() {
    if (!isGnomePublication(state)) {
      return;
    }
    if (els.newsToolbar) {
      els.newsToolbar.hidden = false;
      els.newsToolbar.removeAttribute('hidden');
    }
    if (els.toolbarFiltersRow) {
      els.toolbarFiltersRow.hidden = true;
    }
    if (els.toggleFiltersBtn) {
      els.toggleFiltersBtn.hidden = true;
    }
    if (els.toggleViewModeBtn) {
      els.toggleViewModeBtn.hidden = true;
    }
    if (els.toggleStreamModeBtn) {
      els.toggleStreamModeBtn.hidden = true;
    }
    if (els.searchInput) {
      els.searchInput.placeholder = 'Parşömen başlığı veya metin ara…';
    }
    syncGnomeViewChrome();
  }

  function filterEditionsBySearch(editions, query) {
    const q = String(query || '').trim().toLowerCase();
    if (!q) {
      return editions;
    }
    return editions.filter((row) => {
      const hay = [
        row.title,
        row.deck_line,
        row.description,
        row.author,
        row.full_story,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      return hay.includes(q);
    });
  }

  function renderGnomePagination(payload) {
    const nav = payload?.data?.navigation || {};
    const total = Number(payload?.data?.total_editions || 0);
    const currentNum = Number(payload?.data?.current_edition_number || 0);
    const label = total > 0 ? `Parşömen ${currentNum} / ${total}` : 'Parşömen —';
    if (els.pageSheet) {
      els.pageSheet.textContent = label;
    }
    if (els.pageSheetShort) {
      els.pageSheetShort.textContent = total > 0 ? `${currentNum}/${total}` : '—';
    }
    if (els.prevPage) {
      els.prevPage.disabled = !nav.prev_id;
      els.prevPage.setAttribute('aria-label', 'Önceki sayı');
    }
    if (els.nextPage) {
      els.nextPage.disabled = !nav.next_id;
      els.nextPage.setAttribute('aria-label', 'Sonraki sayı');
    }
    if (els.prevPageLabel) {
      els.prevPageLabel.textContent = 'Önceki sayı';
    }
    if (els.nextPageLabel) {
      els.nextPageLabel.textContent = 'Sonraki sayı';
    }
  }

  function renderGnomeFromPayload(payload, { searchQuery = null } = {}) {
    lastPayload = payload;
    const data = payload?.data || {};
    const allEditions = Array.isArray(data.editions) ? data.editions : [];
    const query = searchQuery ?? state.search ?? '';
    const editions = filterEditionsBySearch(allEditions, query);
    state.gnomeEditions = editions;
    const current = data.current || null;
    state.gnomeCurrentId = current?.id ?? null;
    state.gnomeEditionId = current?.id ?? null;

    renderGnomePagination(payload);
    syncGnomeViewChrome();

    if (isMobileGnomeFeed()) {
      if (els.gnomeCryStage) {
        els.gnomeCryStage.innerHTML = '';
      }
      if (els.gnomeCryFlow) {
        if (!allEditions.length) {
          els.gnomeCryFlow.innerHTML =
            '<div class="vs-stream-loading">Henüz yayımlanmış parşömen yok.</div>';
        } else if (!editions.length) {
          els.gnomeCryFlow.innerHTML =
            '<div class="vs-stream-loading">Aramanızla eşleşen parşömen yok.</div>';
        } else if (renderStreamCard) {
          const ordered = [...editions].reverse();
          els.gnomeCryFlow.innerHTML = ordered
            .map((row) => renderStreamCard(row, { richHtml: true }))
            .join('');
        } else {
          els.gnomeCryFlow.innerHTML = editions
            .map((row) => buildParchmentHtml(row))
            .join('');
        }
      }
      return;
    }

    if (els.gnomeCryFlow) {
      els.gnomeCryFlow.innerHTML = '';
    }

    if (els.gnomeCryStage) {
      if (!current) {
        els.gnomeCryStage.innerHTML = buildParchmentHtml(null, { empty: true, printId: 'gnomePrintSheet' });
      } else {
        els.gnomeCryStage.innerHTML = buildParchmentHtml(current, { printId: 'gnomePrintSheet' });
      }
    }
  }

  function syncPublicationToolbarUi() {
    const btn = els.togglePublicationToolbarBtn;
    if (!btn) {
      return;
    }
    const gnome = isGnomePublication(state);
    const gnomeFace = btn.querySelector('[data-face="gnome"]');
    const gazetteFace = btn.querySelector('[data-face="gazette"]');
    if (gnomeFace) {
      gnomeFace.hidden = gnome;
    }
    if (gazetteFace) {
      gazetteFace.hidden = !gnome;
    }
    const label = gnome ? "Lağımdan Sesler'e geç" : "Gnome'un Çığlığı'na geç";
    btn.setAttribute('aria-label', label);
    btn.setAttribute('title', label);
    const gnomeImg = gnomeFace?.querySelector('img');
    const iconUrl = document.body?.dataset?.gazetteGnomeIcon;
    if (gnomeImg && iconUrl && !gnomeImg.getAttribute('src')) {
      gnomeImg.setAttribute('src', iconUrl);
    }
  }

  function syncPublicationChrome() {
    const gnome = isGnomePublication(state);
    document.body.classList.toggle('vs-publication-gnome', gnome);
    document.body.classList.toggle('vs-publication-lagim', !gnome);

    if (els.publicationLagimBtn) {
      els.publicationLagimBtn.setAttribute('aria-pressed', gnome ? 'false' : 'true');
    }
    if (els.publicationGnomeBtn) {
      els.publicationGnomeBtn.setAttribute('aria-pressed', gnome ? 'true' : 'false');
    }

    syncPublicationToolbarUi();

    if (!gnome) {
      if (els.searchInput) {
        els.searchInput.placeholder = 'Haber, söylenti veya kahraman ara…';
      }
      if (els.gnomeCryFlow) {
        els.gnomeCryFlow.hidden = true;
      }
      if (els.gnomeCryStage) {
        els.gnomeCryStage.hidden = true;
      }
      document.body.classList.remove('vs-gnome-mobile-feed', 'vs-gnome-desktop-sheet');
    } else {
      syncGnomeToolbarChrome();
    }

    const rumorBtn = els.openRumorBtn || els.openRumorModal;
    if (rumorBtn) {
      if (gnome) {
        rumorBtn.setAttribute('hidden', '');
        rumorBtn.hidden = true;
      } else {
        rumorBtn.removeAttribute('hidden');
        rumorBtn.hidden = false;
      }
    }
    if (els.openGnomeCryModal) {
      if (gnome) {
        els.openGnomeCryModal.removeAttribute('hidden');
        els.openGnomeCryModal.hidden = false;
      } else {
        els.openGnomeCryModal.setAttribute('hidden', '');
        els.openGnomeCryModal.hidden = true;
      }
    }

    const title = gnome ? (brand.title || "Gnome'un Çığlığı") : (document.body.dataset.gazetteName || 'Lağımdan Sesler');
    const tagline = gnome
      ? (brand.motto || brand.tagline || '')
      : (document.body.dataset.gazetteTagline || '');
    const homeBtn = document.querySelector('[data-gazette-home]');
    if (homeBtn) {
      homeBtn.textContent = title;
    }
    const mobileNavTitle = document.getElementById('mobileNavPublicationTitle');
    if (mobileNavTitle) {
      mobileNavTitle.textContent = title;
    }
    const taglineEl = document.querySelector('.vs-newspaper-header__tagline');
    if (taglineEl) {
      taglineEl.textContent = tagline;
    }

    if (els.printGazetteBtn && gnome) {
      els.printGazetteBtn.removeAttribute('hidden');
      els.printGazetteBtn.hidden = false;
      els.printGazetteBtn.setAttribute('aria-label', 'Parşömeni yazdır');
    }
    if (els.downloadGazettePngBtn && gnome) {
      els.downloadGazettePngBtn.removeAttribute('hidden');
      els.downloadGazettePngBtn.hidden = false;
    }
  }

  async function loadGnomeCry(editionId) {
    if (editionId) {
      state.gnomeEditionId = editionId;
    }
    const loading = '<div class="vs-stream-loading">Parşömen yükleniyor…</div>';
    if (isMobileGnomeFeed() && els.gnomeCryFlow) {
      els.gnomeCryFlow.innerHTML = loading;
    } else if (els.gnomeCryStage) {
      els.gnomeCryStage.innerHTML = loading;
    }
    const params = new URLSearchParams();
    const id = editionId || state.gnomeEditionId;
    if (id) {
      params.set('edition_id', String(id));
    } else if (state.gnomeEditionNumberHint) {
      params.set('sayi', String(state.gnomeEditionNumberHint));
      state.gnomeEditionNumberHint = null;
    }
    const res = await fetch(`/api/public/news/gnome-cry?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) {
      throw new Error('Parşömen yüklenemedi.');
    }
    const payload = await res.json();
    renderGnomeFromPayload(payload);
  }

  function onViewportChange() {
    if (!isGnomePublication(state)) {
      return;
    }
    syncGnomeViewChrome();
    if (lastPayload) {
      renderGnomeFromPayload(lastPayload);
    }
  }

  function selectEdition(editionId) {
    loadGnomeCry(editionId).catch((error) => showToast(error.message));
  }

  function stepEdition(direction) {
    const nav = lastPayload?.data?.navigation || {};
    const targetId = direction < 0 ? nav.prev_id : nav.next_id;
    if (!targetId) {
      return;
    }
    selectEdition(targetId);
  }

  function setPublication(mode, { reload = true } = {}) {
    const wasGnome = isGnomePublication(state);
    const enteringGnome = mode === PUBLICATION_GNOME && !wasGnome;
    const leavingGnome = wasGnome && mode !== PUBLICATION_GNOME;
    if (enteringGnome) {
      rememberLagimLayoutBeforeGnome?.();
    }
    state.publicationMode = mode === PUBLICATION_GNOME ? PUBLICATION_GNOME : PUBLICATION_LAGIM;
    if (leavingGnome) {
      restoreLagimLayoutAfterGnome?.();
    }
    syncPublicationChrome();
    applyLayoutMode?.();
    const url = new URL(window.location.href);
    if (state.publicationMode === PUBLICATION_GNOME) {
      url.searchParams.set('yayin', 'gnome');
      url.searchParams.delete('gorunum');
      url.searchParams.delete('yaprak');
      url.searchParams.delete('sayi');
      url.searchParams.delete('sayfa');
    } else {
      url.searchParams.delete('yayin');
      if (state.layoutMode === 'stream') {
        url.searchParams.set('gorunum', 'stream');
      } else if (state.layoutMode === 'gazette') {
        url.searchParams.set('gorunum', 'gazette');
      } else {
        url.searchParams.delete('gorunum');
      }
    }
    state.suppressUrlSync = true;
    window.history.replaceState(null, '', `${url.pathname}${url.search}`);
    if (reload) {
      if (isGnomePublication(state)) {
        loadGnomeCry().catch((error) => {
          showToast(error.message);
        });
      } else {
        applyLayoutMode?.();
        loadFeed().catch(console.error);
      }
    }
    state.suppressUrlSync = false;
  }

  function openGnomeModal() {
    ensureGnomeEditor();
    clearGnomeFieldErrors();
    if (els.gnomeCryModalBackdrop) {
      els.gnomeCryModalBackdrop.classList.add('is-open');
      els.gnomeCryModalBackdrop.removeAttribute('hidden');
    }
  }

  function closeGnomeModal() {
    if (!els.gnomeCryModalBackdrop) {
      return;
    }
    els.gnomeCryModalBackdrop.classList.remove('is-open');
    els.gnomeCryModalBackdrop.setAttribute('hidden', '');
  }

  function bind() {
    if (els.gnomeCryEditorToolbar && !els.gnomeCryEditorToolbar.innerHTML) {
      els.gnomeCryEditorToolbar.innerHTML = `
        <span class="ql-formats">
          <select class="ql-header"><option selected></option><option value="2"></option><option value="3"></option></select>
        </span>
        <span class="ql-formats">
          <button type="button" class="ql-bold" aria-label="Kalın"></button>
          <button type="button" class="ql-italic" aria-label="İtalik"></button>
          <button type="button" class="ql-underline" aria-label="Altı çizili"></button>
        </span>
        <span class="ql-formats">
          <button type="button" class="ql-list" value="ordered" aria-label="Numaralı liste"></button>
          <button type="button" class="ql-list" value="bullet" aria-label="Madde işaretli liste"></button>
          <button type="button" class="ql-blockquote" aria-label="Alıntı"></button>
          <button type="button" class="ql-clean" aria-label="Biçimi temizle"></button>
        </span>`;
    }

    els.publicationLagimBtn?.addEventListener('click', () => setPublication(PUBLICATION_LAGIM));
    els.publicationGnomeBtn?.addEventListener('click', () => setPublication(PUBLICATION_GNOME));
    els.togglePublicationToolbarBtn?.addEventListener('click', () => {
      const next = isGnomePublication(state) ? PUBLICATION_LAGIM : PUBLICATION_GNOME;
      setPublication(next);
    });

    els.openGnomeCryModal?.addEventListener('click', openGnomeModal);
    els.gnomeCryModalClose?.addEventListener('click', closeGnomeModal);
    els.gnomeCryModalBackdrop?.addEventListener('click', (event) => {
      if (event.target === els.gnomeCryModalBackdrop) {
        closeGnomeModal();
      }
    });

    const titleInput = els.gnomeCryTitle;
    const deckInput = els.gnomeCryDeck;
    if (submissionCopy.title_placeholder && titleInput) {
      titleInput.placeholder = submissionCopy.title_placeholder;
    }
    if (submissionCopy.deck_placeholder && deckInput) {
      deckInput.placeholder = submissionCopy.deck_placeholder;
    }
    if (els.gnomeCryModalTitle && submissionCopy.modal_title) {
      els.gnomeCryModalTitle.textContent = submissionCopy.modal_title;
    }
    if (els.gnomeCryModalIntro && submissionCopy.modal_intro) {
      els.gnomeCryModalIntro.textContent = submissionCopy.modal_intro;
    }

    els.gnomeCryCustomImage?.addEventListener('change', () => {
      const file = els.gnomeCryCustomImage.files?.[0];
      if (file) {
        showGnomeImagePreview(file);
      }
    });
    els.gnomeCryRemoveImage?.addEventListener('click', resetGnomeImagePreview);

    const clearGnomeFieldOnInput = (key) => {
      setGnomeFieldError(key, '');
      const inputMap = {
        title: els.gnomeCryTitle,
        rumuz: els.gnomeCryRumuz,
        image: els.gnomeCryCustomImage,
      };
      inputMap[key]?.classList.remove('vs-form-field--invalid');
    };
    els.gnomeCryTitle?.addEventListener('input', () => clearGnomeFieldOnInput('title'));
    els.gnomeCryRumuz?.addEventListener('input', () => clearGnomeFieldOnInput('rumuz'));
    els.gnomeCryCustomImage?.addEventListener('change', () => clearGnomeFieldOnInput('image'));

    els.gnomeCryForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!validateGnomeClientForm()) {
        return;
      }
      const storyHtml = syncQuillToHidden();

      const formData = new FormData(els.gnomeCryForm);
      formData.set('full_story', storyHtml);
      const submitBtn = els.gnomeCrySubmit;
      if (submitBtn) {
        submitBtn.disabled = true;
      }
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch('/api/public/news/gnome-cry-submissions', {
          method: 'POST',
          headers: token ? { 'X-CSRF-TOKEN': token, Accept: 'application/json' } : { Accept: 'application/json' },
          body: formData,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          clearGnomeFieldErrors();
          const mapped = applyGnomeServerValidationErrors(data);
          if (mapped) {
            const firstField = Object.keys(data.errors || {})[0];
            const clientKey = GNOME_SERVER_FIELD_TO_CLIENT[firstField];
            const focusMap = {
              title: els.gnomeCryTitle,
              rumuz: els.gnomeCryRumuz,
              body: els.gnomeCryEditor,
              image: els.gnomeCryCustomImage,
            };
            if (clientKey && focusMap[clientKey]) {
              scrollGnomeToField(focusMap[clientKey]);
            }
            const msgs = Object.values(data.errors).flat().filter(Boolean);
            const extra = msgs.length > 1 ? ` (+${msgs.length - 1} alan daha)` : '';
            showToast(`${msgs[0] || data.message}${extra}`, 'warning');
          } else {
            showToast(data.message || 'Gönderilemedi.', 'error');
          }
          return;
        }
        showToast(data.message || 'Temsilci sözü editöre ulaştı.', 'success');
        els.gnomeCryForm.reset();
        gnomeQuill?.setText('');
        resetGnomeImagePreview();
        clearGnomeFieldErrors();
        closeGnomeModal();
      } catch (error) {
        showToast(error.message || 'Gönderilemedi.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
        }
      }
    });
  }

  function applySearch(query) {
    if (!lastPayload) {
      return;
    }
    renderGnomeFromPayload(lastPayload, { searchQuery: query });
  }

  return {
    bind,
    syncPublicationChrome,
    syncPublicationToolbarUi,
    syncGnomeToolbarChrome,
    loadGnomeCry,
    setPublication,
    selectEdition,
    stepEdition,
    applySearch,
    onViewportChange,
    isGnomePublication: () => isGnomePublication(state),
    isGnomeDesktopSheet: () => isGnomeDesktopSheet(),
  };
}

export { PUBLICATION_GNOME, PUBLICATION_LAGIM };