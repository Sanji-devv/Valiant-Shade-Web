function parseInitial(raw) {
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed.map((t) => String(t).trim()).filter(Boolean) : [];
  } catch {
    return [];
  }
}

function notifyTitlesChanged() {
  document.dispatchEvent(new CustomEvent('npc-titles-changed'));
}

function mountNpcTitleTags(root) {
  if (root.dataset.npcTitleTagsMounted === '1') return;
  root.dataset.npcTitleTagsMounted = '1';

  const suggestUrl = root.dataset.suggestUrl || '/api/public/npcs/title-suggestions';
  const chipsEl = root.querySelector('[data-npc-title-chips]');
  const inputEl = root.querySelector('[data-npc-title-input]');
  const hiddenEl = root.querySelector('[data-npc-title-hidden]');
  const listEl = root.querySelector('[data-npc-title-suggestions]');
  if (!chipsEl || !inputEl || !hiddenEl) return;

  let titles = parseInitial(root.dataset.initial || hiddenEl.value);
  let suggestTimer = null;
  let blurTimer = null;

  const syncHidden = () => {
    hiddenEl.value = JSON.stringify(titles);
  };

  const renderChips = () => {
    chipsEl.innerHTML = '';
    titles.forEach((title) => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'npc-title-tags__chip';
      chip.textContent = title;
      chip.addEventListener('click', () => {
        titles = titles.filter((t) => t !== title);
        syncHidden();
        renderChips();
      });
      chipsEl.appendChild(chip);
    });
    syncHidden();
    notifyTitlesChanged();
  };

  const hideSuggestions = () => {
    if (listEl) {
      listEl.hidden = true;
      listEl.innerHTML = '';
    }
  };

  const showSuggestions = (items) => {
    if (!listEl) return;
    const q = inputEl.value.trim().toLowerCase();
    const filtered = items.filter((name) => !titles.includes(name));
    if (filtered.length === 0) {
      hideSuggestions();
      return;
    }
    listEl.innerHTML = '';
    filtered.slice(0, 10).forEach((name) => {
      const li = document.createElement('li');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'npc-title-tags__suggestion';
      btn.textContent = name;
      btn.addEventListener('mousedown', (e) => {
        e.preventDefault();
        addTitle(name);
      });
      li.appendChild(btn);
      listEl.appendChild(li);
    });
    listEl.hidden = false;
  };

  const fetchSuggestions = async () => {
    const q = inputEl.value.trim();
    try {
      const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) return;
      const payload = await res.json();
      showSuggestions(Array.isArray(payload.data) ? payload.data : []);
    } catch {
      hideSuggestions();
    }
  };

  const addTitle = (raw) => {
    const value = String(raw || inputEl.value).trim();
    if (!value) return;
    if (!titles.includes(value)) {
      titles.push(value);
      renderChips();
    }
    inputEl.value = '';
    hideSuggestions();
    inputEl.focus();
  };

  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addTitle();
    }
    if (e.key === 'Backspace' && inputEl.value === '' && titles.length) {
      titles.pop();
      renderChips();
    }
  });

  inputEl.addEventListener('input', () => {
    window.clearTimeout(suggestTimer);
    suggestTimer = window.setTimeout(fetchSuggestions, 220);
  });

  inputEl.addEventListener('focus', () => {
    window.clearTimeout(suggestTimer);
    suggestTimer = window.setTimeout(fetchSuggestions, 120);
  });

  inputEl.addEventListener('blur', () => {
    blurTimer = window.setTimeout(hideSuggestions, 160);
  });

  listEl?.addEventListener('mousedown', () => {
    window.clearTimeout(blurTimer);
  });

  renderChips();
}

export { mountNpcTitleTags };