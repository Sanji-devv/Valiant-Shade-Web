const RUMOR_DRAFT_KEY = 'vs.submission.rumor.draft';
const GNOME_DRAFT_KEY = 'vs.submission.gnome.draft';

function readJson(key) {
  try {
    const raw = sessionStorage.getItem(key);
    if (!raw) {
      return null;
    }
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function writeJson(key, data) {
  try {
    sessionStorage.setItem(key, JSON.stringify(data));
  } catch {
    /* ignore quota */
  }
}

export function clearRumorDraft() {
  try {
    sessionStorage.removeItem(RUMOR_DRAFT_KEY);
  } catch {
    /* ignore */
  }
}

export function clearGnomeDraft() {
  try {
    sessionStorage.removeItem(GNOME_DRAFT_KEY);
  } catch {
    /* ignore */
  }
}

export function saveRumorDraft(snapshot) {
  if (!snapshot || typeof snapshot !== 'object') {
    return;
  }
  writeJson(RUMOR_DRAFT_KEY, { ...snapshot, savedAt: Date.now() });
}

export function loadRumorDraft() {
  return readJson(RUMOR_DRAFT_KEY);
}

export function saveGnomeDraft(snapshot) {
  if (!snapshot || typeof snapshot !== 'object') {
    return;
  }
  writeJson(GNOME_DRAFT_KEY, { ...snapshot, savedAt: Date.now() });
}

export function loadGnomeDraft() {
  return readJson(GNOME_DRAFT_KEY);
}