let previewObjectUrl = null;

function readConfig(panel) {
  const el = panel?.querySelector('.npc-preview-config-json') || document.getElementById('npcPreviewConfigJson');
  if (!el?.textContent) return null;
  try {
    return JSON.parse(el.textContent);
  } catch {
    return null;
  }
}

function q(panel, id) {
  return panel.querySelector(`#${id}`) || document.getElementById(id);
}

function displayName(form) {
  return ['npc-first-name', 'npc-mid-name', 'npc-last-name']
    .map((id) => form.querySelector(`#${id}`)?.value?.trim() || '')
    .filter(Boolean)
    .join(' ');
}

function parseTitles(form) {
  const hidden = form.querySelector('[data-npc-title-hidden]');
  const pending = form.querySelector('[data-npc-title-input]')?.value?.trim() || '';
  let titles = [];
  if (hidden?.value) {
    try {
      const parsed = JSON.parse(hidden.value);
      if (Array.isArray(parsed)) titles = parsed.filter(Boolean);
    } catch {
      titles = [];
    }
  }
  if (pending && !titles.includes(pending)) {
    titles = [...titles, pending];
  }

  return titles;
}

function truncate(text, max = 120) {
  const value = String(text || '').trim();
  if (value.length <= max) return value;

  return `${value.slice(0, max - 1)}…`;
}

function setPortrait(panel, url, removeChecked) {
  const pairs = [
    ['npcPreviewPortrait', 'npcPreviewPortraitPlaceholder'],
    ['npcPreviewDetailPortrait', 'npcPreviewDetailPortraitPlaceholder'],
  ];
  const show = Boolean(url) && !removeChecked;
  pairs.forEach(([imgId, phId]) => {
    const img = q(panel, imgId);
    const ph = q(panel, phId);
    if (!img || !ph) return;
    if (show) {
      img.src = url;
      img.style.display = '';
      ph.classList.add('d-none');
    } else {
      img.removeAttribute('src');
      img.style.display = 'none';
      ph.classList.remove('d-none');
    }
  });
}

function setTextSection(panel, sectionId, textId, text) {
  const section = q(panel, sectionId);
  const node = q(panel, textId);
  if (!section || !node) return;
  const value = String(text || '').trim();
  if (!value) {
    section.classList.add('d-none');
    node.textContent = '';

    return;
  }
  section.classList.remove('d-none');
  node.textContent = value;
}

export function updatePreview(form, panel) {
  const root = panel || form.closest('.row')?.querySelector('[data-npc-preview-panel]');
  if (!root) return;

  const config = readConfig(root);
  if (!config) return;

  const typedName = displayName(form);
  const name = typedName || config.namePlaceholder;
  const titles = parseTitles(form);
  const profession = form.querySelector('#npc-profession')?.value?.trim() || '';
  const race = form.querySelector('#npc-race')?.value?.trim() || '';
  const faction = form.querySelector('#npc-faction')?.value?.trim() || '';
  const location = form.querySelector('#npc-location')?.value?.trim() || '';
  const status = form.querySelector('#npc-status')?.value || '';
  const summary = form.querySelector('#npc-summary')?.value?.trim() || '';
  const background = form.querySelector('#npc-background')?.value?.trim() || '';
  const notes = form.querySelector('#npc-notes')?.value?.trim() || '';
  const removeImage = form.querySelector('#npc-remove-image')?.checked || false;
  const fileInput = form.querySelector('#npc-image');

  let imageUrl = config.existingImage || '';
  if (fileInput?.files?.length) {
    if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = URL.createObjectURL(fileInput.files[0]);
    imageUrl = previewObjectUrl;
  }
  setPortrait(root, imageUrl, removeImage);

  const nameEl = q(root, 'npcPreviewName');
  const detailNameEl = q(root, 'npcPreviewDetailName');
  if (nameEl) nameEl.textContent = name;
  if (detailNameEl) detailNameEl.textContent = typedName || '—';

  const titlesLine = titles.join(', ');
  ['npcPreviewTitles', 'npcPreviewDetailTitles'].forEach((id) => {
    const el = q(root, id);
    if (!el) return;
    if (titlesLine) {
      el.textContent = titlesLine;
      el.classList.remove('d-none');
    } else {
      el.textContent = '';
      el.classList.add('d-none');
    }
  });

  const statusBadge = q(root, 'npcPreviewStatusBadge');
  if (statusBadge) {
    if (status && config.statusLabels?.[status]) {
      statusBadge.textContent = config.statusLabels[status];
      statusBadge.className = `badge ${config.statusBadges?.[status] || 'bg-label-secondary'}`;
      statusBadge.classList.remove('d-none');
    } else {
      statusBadge.classList.add('d-none');
    }
  }

  const metaWrap = q(root, 'npcPreviewMetaBadges');
  if (metaWrap) {
    metaWrap.replaceChildren();
    [profession, race, location].filter(Boolean).forEach((label) => {
      const span = document.createElement('span');
      span.className = 'badge bg-label-primary';
      span.textContent = label;
      metaWrap.appendChild(span);
    });
  }

  const excerptEl = q(root, 'npcPreviewExcerpt');
  if (excerptEl) {
    const excerpt = truncate(summary || background);
    excerptEl.textContent = excerpt || config.excerptPlaceholder || '';
    excerptEl.classList.toggle('text-body-secondary', !excerpt);
  }

  const facts = q(root, 'npcPreviewDetailFacts');
  if (facts) {
    const L = config.factLabels || {};
    const rows = [
      [L.titles, titlesLine],
      [L.profession, profession],
      [L.race, race],
      [L.faction, faction],
      [L.location, location],
      [L.status, status ? config.statusLabels?.[status] : ''],
    ];
    facts.replaceChildren();
    rows.forEach(([label, value]) => {
      if (!value || !label) return;
      const dt = document.createElement('dt');
      dt.className = 'text-body-secondary';
      dt.textContent = label;
      const dd = document.createElement('dd');
      dd.className = 'mb-1 fw-medium';
      dd.textContent = value;
      facts.appendChild(dt);
      facts.appendChild(dd);
    });
  }

  setTextSection(root, 'npcPreviewDetailSummary', 'npcPreviewDetailSummaryText', summary);
  setTextSection(root, 'npcPreviewDetailBackground', 'npcPreviewDetailBackgroundText', background);
  setTextSection(root, 'npcPreviewDetailNotes', 'npcPreviewDetailNotesText', notes);
}

export function mountNpcFormPreview(form) {
  if (!form || form.dataset.npcPreviewMounted === '1' || form.getAttribute('data-npc-preview-live') === '1') return;

  const panel = form.querySelector('[data-npc-preview-panel]')
    || document.querySelector('[data-npc-preview-panel]');
  if (!panel) return;

  form.dataset.npcPreviewMounted = '1';

  const refresh = () => updatePreview(form, panel);
  form.addEventListener('input', refresh, true);
  form.addEventListener('change', refresh, true);
  document.addEventListener('npc-titles-changed', refresh);
  refresh();
}