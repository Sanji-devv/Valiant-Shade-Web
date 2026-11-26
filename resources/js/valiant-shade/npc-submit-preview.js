let previewObjectUrl = null;

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

function displayName(form) {
  return ['npc-sub-first', 'npc-sub-mid', 'npc-sub-last']
    .map((id) => form.querySelector(`#${id}`)?.value?.trim() || '')
    .filter(Boolean)
    .join(' ');
}

function truncate(text, max = 160) {
  const value = String(text || '').trim();
  if (value.length <= max) return value;
  return `${value.slice(0, max - 1)}…`;
}

export function updateNpcSubmitPreview(form, panel, config) {
  if (!form || !panel) return;

  const name = displayName(form) || config.namePlaceholder || '—';
  const titles = parseTitles(form);
  const profession = form.querySelector('#npc-sub-profession')?.value?.trim() || '';
  const race = form.querySelector('#npc-sub-race')?.value?.trim() || '';
  const faction = form.querySelector('#npc-sub-faction')?.value?.trim() || '';
  const location = form.querySelector('#npc-sub-location')?.value?.trim() || '';
  const status = form.querySelector('#npc-sub-status')?.value || '';
  const summary = form.querySelector('#npc-sub-summary')?.value?.trim() || '';
  const background = form.querySelector('#npc-sub-background')?.value?.trim() || '';
  const fileInput = form.querySelector('#npc-sub-image');

  const nameEl = panel.querySelector('[data-preview-name]');
  if (nameEl) nameEl.textContent = name;

  const titlesEl = panel.querySelector('[data-preview-titles]');
  if (titlesEl) {
    const line = titles.join(', ');
    titlesEl.textContent = line;
    titlesEl.hidden = !line;
  }

  const metaEl = panel.querySelector('[data-preview-meta]');
  if (metaEl) {
    metaEl.replaceChildren();
    const statusLabel = status && config.statusLabels?.[status];
    if (statusLabel) {
      const span = document.createElement('span');
      span.className = 'vs-npc-badge';
      span.textContent = statusLabel;
      metaEl.appendChild(span);
    }
    [profession, race, faction, location].filter(Boolean).forEach((label) => {
      const span = document.createElement('span');
      span.className = 'vs-npc-badge';
      span.textContent = label;
      metaEl.appendChild(span);
    });
  }

  const excerptEl = panel.querySelector('[data-preview-excerpt]');
  if (excerptEl) {
    excerptEl.textContent = truncate(summary || background) || config.excerptPlaceholder || '';
  }

  const img = panel.querySelector('[data-preview-portrait]');
  const ph = panel.querySelector('[data-preview-portrait-empty]');
  let url = '';
  if (fileInput?.files?.length) {
    if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = URL.createObjectURL(fileInput.files[0]);
    url = previewObjectUrl;
  }
  if (img && ph) {
    if (url) {
      img.src = url;
      img.hidden = false;
      ph.hidden = true;
    } else {
      img.removeAttribute('src');
      img.hidden = true;
      ph.hidden = false;
    }
  }
}

export function mountNpcSubmitPreview(form) {
  const layout = form.closest('.vs-npc-submit-layout');
  const panel = layout?.querySelector('[data-npc-submit-preview]');
  if (!panel || form.dataset.submitPreviewMounted === '1') return;

  const raw = panel.querySelector('[data-npc-submit-preview-config]')?.textContent;
  let config = {};
  try {
    config = raw ? JSON.parse(raw) : {};
  } catch {
    config = {};
  }

  form.dataset.submitPreviewMounted = '1';

  const refresh = () => updateNpcSubmitPreview(form, panel, config);
  form.addEventListener('input', refresh, true);
  form.addEventListener('change', refresh, true);
  document.addEventListener('npc-titles-changed', refresh);
  refresh();

  return refresh;
}