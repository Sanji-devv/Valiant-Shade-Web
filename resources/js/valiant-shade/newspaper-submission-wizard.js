import { clearRumorDraft, loadRumorDraft, saveRumorDraft } from './submission-form-draft.js';

const WIZARD_KINDS = [
  {
    id: 'flash',
    slot: 'flash',
    label: 'Sıcak haber',
    hint: 'Günlük ek baskı (ör. 3.2). Tek yaprak, sıcak haber.',
  },
  {
    id: 'lead',
    slot: 'lead',
    label: 'Manşet',
    hint: 'Kısa başlık ve metin. Yalnızca üst slaytta döner.',
  },
  {
    id: 'general',
    slot: 'general',
    label: 'Haftalık haber',
    hint: 'Sayıya işlenen tam haber: tür, konum, görsel ve metin.',
  },
  {
    id: 'corner',
    slot: 'corner',
    label: 'Köşe yazısı',
    hint: 'Seçtiğiniz köşe sütununda; aynı köşeye birden fazla yazı eklenebilir.',
  },
];

const ANON_LABEL = 'Anonim Gözcü';

const FIELD_ERROR_EL = {
  issue: 'rumorIssueFieldError',
  type: 'rumorTypeFieldError',
  title: 'rumorTitleFieldError',
  body: 'rumorBodyFieldError',
  corner: 'rumorCornerFieldError',
  rumuz: 'rumorRumuzFieldError',
  image: 'rumorImageFieldError',
};

const SERVER_FIELD_TO_CLIENT = {
  gazette_issue_id: 'issue',
  news_type: 'type',
  title: 'title',
  description: 'body',
  gazette_slot_key: 'corner',
  rumuz: 'rumuz',
  custom_image: 'image',
  image_preset: 'image',
};

export function createRumorWizard(deps) {
  const {
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
  } = deps;

  let wizardFlow = null;
  let authorDraft = '';
  let placementLocked = false;
  let rumorDraftSaveTimer = null;

  function collectRumorDraftSnapshot() {
    if (!els.rumorForm) {
      return null;
    }
    const get = (name) => {
      const field = els.rumorForm.elements.namedItem(name);
      if (!field) {
        return '';
      }
      if (field instanceof RadioNodeList) {
        return field.value ?? '';
      }
      return field.value ?? '';
    };
    return {
      wizardFlow,
      wizardStep: els.rumorWizardForm?.hidden ? 'pick' : 'form',
      placementLocked,
      fields: {
        gazette_slot_kind: get('gazette_slot_kind'),
        gazette_slot_key: get('gazette_slot_key'),
        gazette_slot_label: get('gazette_slot_label'),
        gazette_issue_id: get('gazette_issue_id'),
        news_type: get('news_type'),
        title: get('title'),
        description: get('description'),
        author: get('author'),
        location_badge: get('location_badge'),
        rumuz: get('rumuz'),
        image_preset: get('image_preset'),
        anonymous: Boolean(els.rumorAnonymousInput?.checked),
      },
    };
  }

  function scheduleRumorDraftSave() {
    if (!wizardFlow && els.rumorWizardForm?.hidden) {
      return;
    }
    window.clearTimeout(rumorDraftSaveTimer);
    rumorDraftSaveTimer = window.setTimeout(() => {
      const snapshot = collectRumorDraftSnapshot();
      if (snapshot) {
        saveRumorDraft(snapshot);
      }
    }, 280);
  }

  function restoreRumorDraft() {
    const draft = loadRumorDraft();
    if (!draft) {
      return false;
    }
    wizardFlow = draft.wizardFlow || null;
    placementLocked = Boolean(draft.placementLocked);
    if (els.rumorForm && draft.fields) {
      Object.entries(draft.fields).forEach(([name, value]) => {
        const field = els.rumorForm.elements.namedItem(name);
        if (!field) {
          return;
        }
        if (field instanceof RadioNodeList) {
          field.value = value;
        } else {
          field.value = value;
        }
      });
    }
    if (els.rumorAnonymousInput) {
      els.rumorAnonymousInput.checked = Boolean(draft.fields?.anonymous);
    }
    syncAnonymousAuthorField();
    if (draft.wizardStep === 'form' && wizardFlow) {
      showWizardStep('form');
      applyFlowUi();
      if (wizardFlow === 'corner' && draft.fields?.gazette_slot_key) {
        renderCornerPicker(draft.fields.gazette_slot_key);
      }
    } else {
      wizardFlow = null;
      showWizardStep('pick');
      renderWizardKinds();
    }
    deps.updateRumorImagePreview?.();
    renderDemoPreview();
    return true;
  }

  function resetRumorFormState() {
    if (els.rumorForm) {
      els.rumorForm.reset();
    }
    authorDraft = '';
    clearNewsTypePicker();
    clearFieldErrors();
    deps.updateRumorImagePreview?.();
    syncAnonymousAuthorField();
  }

  function predictNextHotExtraLabel() {
    const weekly = (state.issueArchive || []).find((row) => row.is_current && !row.is_extra);
    const parentId = weekly?.id;
    const baseNum = weekly?.issue_number ?? state.currentIssue?.issue_number ?? 1;
    if (!parentId) {
      return `${baseNum}.1`;
    }
    const extras = (state.issueArchive || []).filter(
      (row) => Number(row.parent_issue_id) === Number(parentId) && row.is_extra,
    );
    const maxSeq = extras.reduce((max, row) => Math.max(max, Number(row.extra_sequence) || 0), 0);

    return `${baseNum}.${maxSeq + 1}`;
  }

  function clearNewsTypePicker() {
    if (!els.rumorTypePicker || !els.rumorNewsType) {
      return;
    }
    els.rumorTypePicker.querySelectorAll('.vs-type-picker__btn').forEach((item) => {
      item.classList.remove('is-selected');
      item.setAttribute('aria-checked', 'false');
    });
    els.rumorNewsType.value = '';
  }

  function syncAnonymousAuthorField() {
    if (!els.rumorAuthorInput || !els.rumorAnonymousInput) {
      return;
    }
    const on = Boolean(els.rumorAnonymousInput.checked);
    if (on) {
      if (els.rumorAuthorInput.value && els.rumorAuthorInput.value !== ANON_LABEL) {
        authorDraft = els.rumorAuthorInput.value;
      }
      els.rumorAuthorInput.value = ANON_LABEL;
      els.rumorAuthorInput.readOnly = true;
    } else {
      els.rumorAuthorInput.readOnly = false;
      els.rumorAuthorInput.value = authorDraft;
    }
    if (els.rumorAnonymousToggle) {
      els.rumorAnonymousToggle.classList.toggle('is-active', on);
      els.rumorAnonymousToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
    }
  }

  function renderWizardKinds() {
    if (!els.rumorWizardKinds) {
      return;
    }
    els.rumorWizardKinds.innerHTML = WIZARD_KINDS.map(
      (item) => `
      <button type="button" class="vs-rumor-wizard__kind" data-wizard-flow="${item.id}">
        <span class="vs-rumor-wizard__kind-title">${escapeHtml(item.label)}</span>
        <span class="vs-rumor-wizard__kind-hint">${escapeHtml(item.hint)}</span>
      </button>`,
    ).join('');
  }

  function editableWeeklyIssues() {
    return [...(state.issueArchive || [])]
      .filter((issue) => !issue.is_extra && issue.is_editable)
      .sort((a, b) => Number(a.issue_number) - Number(b.issue_number));
  }

  function setIssueSelection(issueId, { silent = false } = {}) {
    const id = issueId ? String(issueId) : '';
    if (els.rumorGazetteIssueId) {
      els.rumorGazetteIssueId.value = id;
    }
    if (!els.rumorIssuePicker) {
      return;
    }
    els.rumorIssuePicker.querySelectorAll('.vs-issue-picker__btn').forEach((btn) => {
      const on = btn.dataset.issueId === id;
      btn.classList.toggle('is-selected', on);
      btn.setAttribute('aria-checked', on ? 'true' : 'false');
    });
    if (!silent) {
      setFieldError('issue', '');
      renderDemoPreview();
    }
  }

  function renderIssuePicker({ preselectId = null, requireSelection = true } = {}) {
    if (!els.rumorIssuePicker) {
      return;
    }
    const issues = editableWeeklyIssues();
    if (!issues.length) {
      els.rumorIssuePicker.innerHTML =
        '<p class="vs-rumor-issue-pick__empty">Açık sayı yüklenemedi; sayfayı yenileyin.</p>';
      setIssueSelection('', { silent: true });
      return;
    }

    els.rumorIssuePicker.innerHTML = issues
      .map((issue) => {
        const num = escapeHtml(String(issue.display_issue_number || issue.issue_number));
        const tag = issue.is_upcoming ? 'Gelecek sayı' : issue.is_current ? 'Mevcut sayı' : 'Açık sayı';
        return `<button type="button" class="vs-issue-picker__btn" data-issue-id="${issue.id}" role="radio" aria-checked="false">
          <span class="vs-issue-picker__num">Sayı ${num}</span>
          <span class="vs-issue-picker__tag">${escapeHtml(tag)}</span>
        </button>`;
      })
      .join('');

    let selected = '';
    if (preselectId && issues.some((row) => Number(row.id) === Number(preselectId))) {
      selected = String(preselectId);
    } else if (!requireSelection) {
      selected = '';
    } else if (issues.length === 1) {
      selected = String(issues[0].id);
    }
    setIssueSelection(selected, { silent: true });
  }

  function clearFieldErrors() {
    Object.values(FIELD_ERROR_EL).forEach((id) => setFieldErrorById(id, ''));
    document.querySelectorAll('.vs-form-field--invalid').forEach((node) => {
      node.classList.remove('vs-form-field--invalid');
    });
  }

  function applyServerValidationErrors(payload) {
    const errors = payload?.errors;
    if (!errors || typeof errors !== 'object') {
      return false;
    }
    let applied = false;
    Object.entries(errors).forEach(([apiField, messages]) => {
      const clientKey = SERVER_FIELD_TO_CLIENT[apiField];
      if (!clientKey) {
        return;
      }
      const msg = Array.isArray(messages) ? messages[0] : messages;
      if (!msg) {
        return;
      }
      setFieldError(clientKey, String(msg));
      applied = true;
    });
    return applied;
  }

  function setFieldErrorById(id, message) {
    const el = document.getElementById(id);
    if (!el) {
      return;
    }
    const text = (message || '').trim();
    el.hidden = text === '';
    el.textContent = text;
  }

  function setFieldError(key, message) {
    const id = FIELD_ERROR_EL[key];
    if (id) {
      setFieldErrorById(id, message);
    }
  }

  function cornerSlotsForIssue() {
    const issueId = Number(els.rumorGazetteIssueId?.value || 0);
    const fromArchive = (state.issueArchive || []).find((row) => Number(row.id) === issueId);
    if (fromArchive?.corner_slots?.length) {
      return fromArchive.corner_slots;
    }
    if (Number(state.currentIssue?.id) === issueId && state.currentIssue?.corner_slots?.length) {
      return state.currentIssue.corner_slots;
    }

    return defaultCornerSlotsFromDom();
  }

  function updateCornerSummary() {
    if (!els.rumorCornerCountSummary) {
      return;
    }
    const slots = cornerSlotsForIssue();
    const pieceTotal = slots.reduce(
      (sum, s) => sum + (Number(s.article_count) || (s.is_filled ? 1 : 0)),
      0,
    );
    const filledCorners = slots.filter((s) => s.is_filled || Number(s.article_count) > 0).length;
    els.rumorCornerCountSummary.textContent = `Bu sayıda ${filledCorners} köşe dolu, toplam ${pieceTotal} köşe yazısı var. Aynı köşeye yeni yazı ekleyebilirsiniz.`;
  }

  function renderCornerPicker(selectedKey) {
    if (!els.rumorCornerPickerGrid) {
      return;
    }
    const slots = cornerSlotsForIssue();
    els.rumorCornerPickerGrid.innerHTML = slots
      .map((s) => {
        const key = escapeHtml(s.key || '');
        const title = escapeHtml(s.title || s.key || 'Köşe');
        const selected = s.key === selectedKey;
        const count = Number(s.article_count) || (s.is_filled ? 1 : 0);
        const filled = count > 0;
        const stateLabel = count > 0 ? `${count} yazı · ekle` : 'Boş';
        return `<button type="button" class="vs-rumor-corner-pick__btn ${
          filled ? 'vs-rumor-corner-pick__btn--filled' : 'vs-rumor-corner-pick__btn--empty'
        }${selected ? ' is-selected' : ''}" data-corner-key="${key}" aria-pressed="${selected ? 'true' : 'false'}">
          <span class="vs-rumor-corner-pick__title">${title}</span>
          <span class="vs-rumor-corner-pick__state">${stateLabel}</span>
        </button>`;
      })
      .join('');
    updateCornerSummary();
  }

  function selectedNewsTypeLabel() {
    const typeKey = els.rumorNewsType?.value?.trim() || '';
    if (!typeKey || !els.rumorTypePicker) {
      return '';
    }
    const btn = els.rumorTypePicker.querySelector(`[data-type="${CSS.escape(typeKey)}"]`);
    if (!btn) {
      return '';
    }
    return (
      btn.getAttribute('aria-label') ||
      btn.querySelector('.vs-type-picker__label')?.textContent?.trim() ||
      ''
    );
  }

  function selectedIssueLine() {
    const id = Number(els.rumorGazetteIssueId?.value || 0);
    if (!id) {
      return 'Önce gazete sayısını seçin.';
    }
    const issue = (state.issueArchive || []).find((row) => Number(row.id) === id);
    if (!issue) {
      return 'Önce gazete sayısını seçin.';
    }
    const num = issue.display_issue_number || issue.issue_number;
    const tag = issue.is_upcoming ? ' · Gelecek sayı' : issue.is_current ? ' · Mevcut sayı' : '';
    return `Sayı ${num}${tag}`;
  }

  function placementTextForFlow(flow) {
    if (flow === 'flash') {
      const extra = predictNextHotExtraLabel();
      return `Günlük ek baskı (sıcak haber). Onay sonrası ek sayı ${extra}; tek yaprak, listede arşivde görünür.`;
    }
    if (flow === 'lead') {
      return `Üst manşet slaytı — ${selectedIssueLine()}. Haber akışına girmez.`;
    }
    if (flow === 'corner') {
      const cornerKey = els.rumorGazetteSlotKey?.value;
      const corner = cornerSlotsForIssue().find((s) => s.key === cornerKey);
      const cornerName = corner?.title || 'köşe sütunu';
      return `${cornerName} — ${selectedIssueLine()}. Kapak köşesinde, aynı sütuna ek yazı eklenebilir.`;
    }
    return `Haftalık sayı içi haber — ${selectedIssueLine()}. Gazete yapraklarında sütun haberi olarak basılır.`;
  }

  function demoField(value, placeholder) {
    if (value) {
      return escapeHtml(value);
    }
    return `<span class="vs-rumor-demo__ph">${escapeHtml(placeholder)}</span>`;
  }

  function renderDemoPreview() {
    if (!els.rumorDemoPreview || !wizardFlow) {
      return;
    }

    const configs = getSubmissionSlotConfig();
    const cfg =
      configs[wizardFlow === 'general' ? 'general' : wizardFlow] ||
      configs.general ||
      {};

    const titleRaw = els.rumorTitleInput?.value?.trim() || '';
    const bodyRaw = els.rumorBodyInput?.value?.trim() || '';
    const authorRaw = els.rumorAnonymousInput?.checked
      ? ANON_LABEL
      : els.rumorAuthorInput?.value?.trim() || '';
    const locationRaw = els.rumorLocationInput?.value?.trim() || '';
    const typeLabel = selectedNewsTypeLabel();

    const titlePh = cfg.title_placeholder || 'Başlığınız…';
    const bodyPh = cfg.body_placeholder || 'Metniniz…';
    const bodyLimit = wizardFlow === 'lead' ? 180 : wizardFlow === 'corner' ? 200 : 220;
    const bodyShown = bodyRaw ? bodyRaw.slice(0, bodyLimit) + (bodyRaw.length > bodyLimit ? '…' : '') : '';

    const showType = wizardFlow === 'general' || wizardFlow === 'flash' || wizardFlow === 'corner';
    const typeChip =
      showType && typeLabel
        ? `<span class="vs-rumor-demo__type">${escapeHtml(typeLabel)}</span>`
        : showType
          ? `<span class="vs-rumor-demo__type vs-rumor-demo__ph">Haber türü seçin</span>`
          : '';

    const metaParts = [];
    if (wizardFlow !== 'lead') {
      if (wizardFlow === 'corner') {
        const rumuzRaw = els.rumorRumuzInput?.value?.trim() || '';
        metaParts.push(rumuzRaw ? escapeHtml(rumuzRaw) : '<span class="vs-rumor-demo__ph">Rumuz</span>');
      } else if (authorRaw) {
        metaParts.push(escapeHtml(authorRaw));
      } else {
        metaParts.push('<span class="vs-rumor-demo__ph">İmza (isteğe bağlı)</span>');
      }
      if (wizardFlow !== 'corner' && locationRaw) {
        metaParts.push(escapeHtml(locationRaw));
      } else if (wizardFlow !== 'corner' && (wizardFlow === 'general' || wizardFlow === 'flash')) {
        metaParts.push('<span class="vs-rumor-demo__ph">Konum (isteğe bağlı)</span>');
      }
    }

    const flowKicker = {
      flash: 'Sıcak haber taslağı',
      lead: 'Manşet taslağı',
      general: 'Haftalık haber taslağı',
      corner: 'Köşe yazısı taslağı',
    }[wizardFlow];

    els.rumorDemoPreview.innerHTML = `
      <div class="vs-rumor-demo__placement" role="note">
        <span class="vs-rumor-demo__placement-label">Nerede basılır?</span>
        <p class="vs-rumor-demo__placement-text">${escapeHtml(placementTextForFlow(wizardFlow))}</p>
      </div>
      <div class="vs-rumor-demo__mock" aria-label="Önizleme">
        <p class="vs-rumor-demo__kicker">${escapeHtml(flowKicker || '')}</p>
        ${typeChip}
        <h3 class="vs-rumor-demo__title">${demoField(titleRaw, titlePh)}</h3>
        ${
          wizardFlow !== 'lead' && metaParts.length
            ? `<p class="vs-rumor-demo__meta">${metaParts.join(' · ')}</p>`
            : ''
        }
        <p class="vs-rumor-demo__body">${
          bodyRaw ? escapeHtml(bodyShown) : demoField('', bodyPh)
        }</p>
      </div>`;
  }

  function applyFlowUi() {
    const flow = wizardFlow;
    const configs = getSubmissionSlotConfig();
    const slotKey = flow === 'lead' ? 'lead' : flow === 'corner' ? 'corner' : flow === 'general' ? 'general' : 'flash';
    const cfg = configs[slotKey] || configs.general || {};

    if (els.rumorGazetteSlotKind) {
      els.rumorGazetteSlotKind.value = flow === 'flash' ? '' : flow === 'general' ? '' : flow;
    }

    if (els.rumorModalPanel) {
      els.rumorModalPanel.classList.remove(
        'vs-modal--rumor-slot-lead',
        'vs-modal--rumor-slot-corner',
        'vs-modal--rumor-slot-general',
        'vs-modal--rumor-slot-flash',
      );
      if (flow && flow !== 'general') {
        els.rumorModalPanel.classList.add(`vs-modal--rumor-slot-${flow === 'flash' ? 'flash' : flow}`);
      } else if (flow === 'general') {
        els.rumorModalPanel.classList.add('vs-modal--rumor-slot-general');
      }
    }

    const needsIssue = flow === 'lead' || flow === 'general' || flow === 'corner';
    if (els.rumorIssuePickWrap) {
      els.rumorIssuePickWrap.hidden = !needsIssue;
    }
    if (needsIssue) {
      renderIssuePicker({ requireSelection: true });
    }

    const showType = flow === 'general' || flow === 'flash' || flow === 'corner';
    if (els.rumorTypeFieldWrap) {
      els.rumorTypeFieldWrap.hidden = !showType;
    }

    const showMeta = flow === 'general' || flow === 'flash';
    if (els.rumorMetaGrid) {
      els.rumorMetaGrid.hidden = !showMeta;
    }
    if (els.rumorLocationWrap) {
      els.rumorLocationWrap.hidden = !showMeta;
    }
    if (els.rumorRumuzFieldWrap) {
      els.rumorRumuzFieldWrap.hidden = flow !== 'corner';
    }
    if (els.rumorAnonymousWrap) {
      els.rumorAnonymousWrap.hidden = flow === 'lead' || flow === 'corner';
    }
    if (els.rumorAuthorWrap) {
      els.rumorAuthorWrap.hidden = flow === 'lead' || flow === 'corner';
    }

    if (els.rumorImageSection) {
      els.rumorImageSection.hidden = flow === 'lead' || flow === 'corner';
    }
    if (els.rumorCornerPicker) {
      els.rumorCornerPicker.hidden = flow !== 'corner';
      if (flow === 'corner') {
        if (!placementLocked) {
          if (els.rumorGazetteSlotKey) {
            els.rumorGazetteSlotKey.value = '';
          }
          if (els.rumorGazetteSlotLabelHidden) {
            els.rumorGazetteSlotLabelHidden.value = '';
          }
          renderCornerPicker(null);
        } else {
          renderCornerPicker(els.rumorGazetteSlotKey?.value || null);
        }
      }
    }

    if (els.rumorModalTitle) {
      const titles = {
        flash: 'Sıcak haber gönder',
        lead: 'Manşet gönder',
        general: 'Haftalık haber gönder',
        corner: 'Köşe yazısı gönder',
      };
      els.rumorModalTitle.textContent = titles[flow] || 'Söylenti gönder';
    }
    if (els.rumorModalIntro) {
      els.rumorModalIntro.textContent = cfg.modal_intro || 'Alanları doldurun; üstteki önizleme yazdıkça güncellenir.';
    }
    if (els.rumorBodyInput) {
      els.rumorBodyInput.minLength = flow === 'lead' ? 20 : flow === 'flash' ? 80 : 30;
      els.rumorBodyInput.maxLength = flow === 'flash' ? 12000 : flow === 'lead' ? 1200 : 5000;
    }
    if (els.rumorBodyLabel) {
      els.rumorBodyLabel.textContent =
        flow === 'lead' ? 'Manşet metni' : flow === 'corner' ? 'Köşe metni' : 'Haber metni';
    }

    renderDemoPreview();
  }

  function scrollRumorModalToTop() {
    const body =
      els.rumorModalBody ||
      els.rumorModalPanel?.querySelector('.vs-modal__body--rumor');
    if (body) {
      body.scrollTop = 0;
    }
    window.requestAnimationFrame(() => {
      if (body) {
        body.scrollTop = 0;
      }
    });
  }

  function showWizardStep(step) {
    const isPick = step === 'pick';
    if (els.rumorWizardPick) {
      els.rumorWizardPick.hidden = !isPick;
    }
    if (els.rumorWizardForm) {
      els.rumorWizardForm.hidden = isPick;
    }
    if (els.rumorWizardBack) {
      els.rumorWizardBack.hidden = isPick || placementLocked;
    }
    if (els.rumorModalIntro) {
      els.rumorModalIntro.hidden = !isPick;
    }
    if (isPick && els.rumorModalTitle) {
      els.rumorModalTitle.textContent = 'Söylenti gönder';
    }
    scrollRumorModalToTop();
  }

  function scrollToField(el) {
    if (!el) {
      return;
    }
    el.classList.add('vs-form-field--invalid');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (typeof el.focus === 'function') {
      el.focus({ preventScroll: true });
    }
  }

  function validateClientForm() {
    const errors = [];
    clearFieldErrors();
    document.querySelectorAll('.vs-form-field--invalid').forEach((node) => {
      node.classList.remove('vs-form-field--invalid');
    });

    const flow = wizardFlow;
    if (flow !== 'flash') {
      const issueVal = Number(els.rumorGazetteIssueId?.value || 0);
      if (!issueVal) {
        errors.push({
          key: 'issue',
          message: 'Lütfen mevcut veya gelecek sayıyı seçin.',
          el: els.rumorIssuePicker,
        });
      }
    }

    if (flow === 'general' || flow === 'flash' || flow === 'corner') {
      if (!els.rumorNewsType?.value) {
        errors.push({
          key: 'type',
          message: 'Lütfen haber türünü seçin.',
          el: els.rumorTypePicker,
        });
      }
    }

    if (flow === 'corner' && !els.rumorGazetteSlotKey?.value) {
      errors.push({
        key: 'corner',
        message: 'Lütfen köşe sütununu seçin.',
        el: els.rumorCornerPickerGrid,
      });
    }

    if (flow === 'corner') {
      const rumuz = els.rumorRumuzInput?.value?.trim() || '';
      if (!rumuz) {
        errors.push({
          key: 'rumuz',
          message: 'Lütfen köşe rumuzunu girin.',
          el: els.rumorRumuzInput,
        });
      }
    }

    const title = els.rumorTitleInput?.value?.trim() || '';
    if (title.length < 8) {
      errors.push({
        key: 'title',
        message: 'Başlık en az 8 karakter olmalı.',
        el: els.rumorTitleInput,
      });
    }

    const body = els.rumorBodyInput?.value?.trim() || '';
    const minBody = flow === 'lead' ? 20 : flow === 'flash' ? 80 : 30;
    if (body.length < minBody) {
      const shortBy = minBody - body.length;
      errors.push({
        key: 'body',
        message: `Haber metni en az ${minBody} karakter olmalı (${shortBy} karakter daha yazın).`,
        el: els.rumorBodyInput,
      });
    }

    const file = els.rumorCustomImageInput?.files?.[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        errors.push({
          key: 'image',
          message: 'Görsel 5 MB sınırını aşıyor.',
          el: els.rumorCustomImageInput,
        });
      }
      const okTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
      if (!okTypes.includes(file.type)) {
        errors.push({
          key: 'image',
          message: 'Görsel JPEG, PNG veya WebP olmalı.',
          el: els.rumorCustomImageInput,
        });
      }
    }

    errors.forEach((entry) => {
      if (entry.key) {
        setFieldError(entry.key, entry.message);
      }
    });

    if (errors.length) {
      const first = errors[0];
      scrollToField(first.el);
      const extra = errors.length > 1 ? ` (+${errors.length - 1} alan daha)` : '';
      showToast(`${first.message}${extra}`, 'warning');
      return false;
    }

    return true;
  }

  async function openWizard(options = {}) {
    placementLocked = Boolean(options.fromSlot);
    if (!state.issueArchive.length) {
      try {
        await fetchIssueArchive();
      } catch (e) {
        console.warn(e);
      }
    }

    const restored = !options.fromSlot && !options.flow && !options.slot && restoreRumorDraft();
    if (!restored) {
      resetRumorFormState();
    }

    if (options.flow) {
      wizardFlow = options.flow;
      showWizardStep('form');
      applyFlowUi();
    } else if (options.slot?.kind && options.slot.kind !== 'general') {
      wizardFlow = options.slot.kind === 'lead' ? 'lead' : 'corner';
      showWizardStep('form');
      applyFlowUi();
      if (wizardFlow === 'corner' && options.slot.key) {
        els.rumorGazetteSlotKey.value = options.slot.key;
        renderCornerPicker(options.slot.key);
      }
      placementLocked = true;
    } else {
      wizardFlow = null;
      showWizardStep('pick');
      renderWizardKinds();
    }

    if (els.rumorModal) {
      els.rumorModal.classList.add('is-open');
    }
    document.body.classList.add('vs-rumor-modal-open');
    if (els.rumorModalPanel) {
      els.rumorModalPanel.classList.add('vs-modal--rumor-gazette--active');
    }
    scrollRumorModalToTop();
  }

  function closeWizard({ clear = false } = {}) {
    if (!clear) {
      scheduleRumorDraftSave();
      const snapshot = collectRumorDraftSnapshot();
      if (snapshot) {
        saveRumorDraft(snapshot);
      }
    } else {
      clearRumorDraft();
      resetRumorFormState();
      wizardFlow = null;
      placementLocked = false;
      showWizardStep('pick');
    }
    if (els.rumorModal) {
      els.rumorModal.classList.remove('is-open');
    }
    document.body.classList.remove('vs-rumor-modal-open');
  }

  function startFlow(flowId) {
    wizardFlow = flowId;
    showWizardStep('form');
    applyFlowUi();
    scrollRumorModalToTop();
    scheduleRumorDraftSave();
  }

  function bind() {
    renderWizardKinds();

    els.openRumorBtn?.addEventListener('click', () => openWizard());
    els.rumorClose?.addEventListener('click', () => closeWizard({ clear: false }));
    els.rumorModal?.addEventListener('click', (event) => {
      if (event.target === els.rumorModal) {
        closeWizard({ clear: false });
      }
    });

    els.rumorWizardKinds?.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-wizard-flow]');
      if (!btn) {
        return;
      }
      startFlow(btn.dataset.wizardFlow);
    });

    els.rumorWizardBack?.addEventListener('click', () => {
      if (placementLocked) {
        return;
      }
      wizardFlow = null;
      showWizardStep('pick');
    });

    els.rumorIssuePicker?.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-issue-id]');
      if (!btn?.dataset.issueId) {
        return;
      }
      setIssueSelection(btn.dataset.issueId);
      if (wizardFlow === 'corner' && !placementLocked) {
        if (els.rumorGazetteSlotKey) {
          els.rumorGazetteSlotKey.value = '';
        }
        if (els.rumorGazetteSlotLabelHidden) {
          els.rumorGazetteSlotLabelHidden.value = '';
        }
        renderCornerPicker(null);
      }
    });

    els.rumorCornerPickerGrid?.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-corner-key]');
      if (!btn?.dataset.cornerKey) {
        return;
      }
      if (els.rumorGazetteSlotKind) {
        els.rumorGazetteSlotKind.value = 'corner';
      }
      els.rumorGazetteSlotKey.value = btn.dataset.cornerKey;
      const slot = cornerSlotsForIssue().find((s) => s.key === btn.dataset.cornerKey);
      if (els.rumorGazetteSlotLabelHidden) {
        els.rumorGazetteSlotLabelHidden.value = slot?.title || btn.dataset.cornerKey;
      }
      renderCornerPicker(btn.dataset.cornerKey);
      setFieldError('corner', '');
      renderDemoPreview();
    });

    els.rumorAnonymousToggle?.addEventListener('click', () => {
      if (!els.rumorAnonymousInput) {
        return;
      }
      els.rumorAnonymousInput.checked = !els.rumorAnonymousInput.checked;
      syncAnonymousAuthorField();
      renderDemoPreview();
    });

    ['input', 'change'].forEach((evt) => {
      els.rumorTitleInput?.addEventListener(evt, () => {
        setFieldError('title', '');
        renderDemoPreview();
        scheduleRumorDraftSave();
      });
      els.rumorBodyInput?.addEventListener(evt, () => {
        setFieldError('body', '');
        renderDemoPreview();
        scheduleRumorDraftSave();
      });
      els.rumorAuthorInput?.addEventListener(evt, () => {
        renderDemoPreview();
        scheduleRumorDraftSave();
      });
      els.rumorLocationInput?.addEventListener(evt, () => {
        renderDemoPreview();
        scheduleRumorDraftSave();
      });
      els.rumorRumuzInput?.addEventListener(evt, () => {
        setFieldError('rumuz', '');
        renderDemoPreview();
        scheduleRumorDraftSave();
      });
    });

    els.rumorTypePicker?.addEventListener('click', (event) => {
      if (!event.target.closest('[data-type]')) {
        return;
      }
      setFieldError('type', '');
      window.requestAnimationFrame(renderDemoPreview);
    });

    if (els.rumorImagePreset && els.rumorCustomImageInput) {
      els.rumorImagePreset.addEventListener('change', () => {
        els.rumorCustomImageInput.value = '';
        deps.updateRumorImagePreview?.();
        scheduleRumorDraftSave();
      });
      els.rumorCustomImageInput.addEventListener('change', () => {
        if (els.rumorCustomImageInput.files?.length) {
          els.rumorImagePreset.value = '__gazette_auto__';
        }
        deps.updateRumorImagePreview?.();
        scheduleRumorDraftSave();
      });
    }

    els.rumorRemoveImage?.addEventListener('click', () => {
      if (els.rumorCustomImageInput) {
        els.rumorCustomImageInput.value = '';
      }
      if (els.rumorImagePreset) {
        els.rumorImagePreset.value = '__gazette_auto__';
      }
      deps.updateRumorImagePreview?.();
    });

    els.rumorForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      clearFieldErrors();
      if (!validateClientForm()) {
        return;
      }
      els.rumorSubmit.disabled = true;
      try {
        const formData = new FormData(els.rumorForm);
        if (wizardFlow === 'flash') {
          formData.delete('gazette_slot_kind');
          formData.delete('gazette_issue_id');
        } else if (wizardFlow === 'general') {
          formData.delete('gazette_slot_kind');
          formData.delete('gazette_slot_key');
        } else if (wizardFlow === 'corner') {
          formData.set('gazette_slot_kind', 'corner');
          const rumuz = String(formData.get('rumuz') || '').trim();
          formData.delete('location_badge');
          formData.delete('anonymous');
          formData.delete('custom_image');
          formData.delete('image_preset');
          if (rumuz) {
            formData.set('author', rumuz);
          } else {
            formData.delete('author');
          }
        } else if (wizardFlow === 'lead') {
          formData.set('gazette_slot_kind', 'lead');
          formData.delete('news_type');
          formData.delete('location_badge');
          formData.delete('author');
          formData.delete('anonymous');
          formData.delete('custom_image');
          formData.delete('image_preset');
        }
        if (els.rumorAnonymousInput?.checked) {
          formData.set('anonymous', '1');
        } else {
          formData.delete('anonymous');
        }

        const payload =
          wizardFlow === 'flash' ? await submitHotExtra(formData) : await submitRumor(formData);
        closeWizard({ clear: true });
        showToast(payload.message || 'Gönderiniz editöre ulaştı.', 'success');
      } catch (error) {
        clearFieldErrors();
        const mapped = error.payload ? applyServerValidationErrors(error.payload) : false;
        if (!mapped) {
          showToast(error.message || 'Gönderim başarısız.', 'error');
        } else {
          showToast(error.message || 'Lütfen işaretli alanları düzeltin.', 'warning');
        }
      } finally {
        els.rumorSubmit.disabled = false;
      }
    });
  }

  return {
    bind,
    openWizard,
    closeWizard,
    openFromSlot(slot) {
      return openWizard({ slot, fromSlot: true });
    },
    openFlow(flow) {
      return openWizard({ flow });
    },
  };
}