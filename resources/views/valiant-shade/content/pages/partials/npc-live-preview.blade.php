@php
  use App\ValiantShade\Support\NpcStatusCatalog;
  $previewEditing = isset($npc) && $npc !== null;
  $statusLabels = collect(NpcStatusCatalog::options())
      ->mapWithKeys(fn (array $meta, int $value): array => [(string) $value => __('npc_status.' . $meta['key'])])
      ->all();
  $statusBadges = collect(NpcStatusCatalog::options())
      ->mapWithKeys(fn (array $meta, int $value): array => [(string) $value => 'bg-label-' . ($meta['badge'] ?? 'secondary')])
      ->all();
  $npcPreviewConfig = [
      'statusLabels' => $statusLabels,
      'statusBadges' => $statusBadges,
      'factLabels' => [
          'titles' => __('Titles / Epithets'),
          'profession' => __('Profession / Role'),
          'race' => __('Race'),
          'faction' => __('Faction / Affiliation'),
          'location' => __('Location'),
          'status' => __('Status'),
      ],
      'namePlaceholder' => __('NPC name placeholder'),
      'excerptPlaceholder' => __('Summary preview will appear here.'),
      'existingImage' => $previewEditing ? ($npc->imageUrl() ?? '') : '',
  ];
@endphp
<aside class="npc-live-preview" data-npc-preview-panel aria-live="polite" aria-relevant="additions text">
  <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
    <span class="small text-body-secondary text-uppercase fw-semibold">{{ __('Live preview') }}</span>
    <span class="badge bg-label-secondary">{{ __('As on roster card') }}</span>
  </div>

  <article class="card overflow-hidden shadow-sm mb-3" id="npcPreviewListCard">
    <div id="npcPreviewPortraitWrap" class="position-relative bg-label-secondary" style="height: 220px;">
      <img id="npcPreviewPortrait" src="{{ $previewEditing ? ($npc->imageUrl() ?? '') : '' }}" alt="" class="card-img-top w-100 h-100" style="object-fit: cover; {{ $previewEditing && $npc->imageUrl() ? '' : 'display: none;' }}">
      <div id="npcPreviewPortraitPlaceholder" class="d-flex align-items-center justify-content-center w-100 h-100 {{ $previewEditing && $npc->imageUrl() ? 'd-none' : '' }}">
        <i class="icon-base ti tabler-user text-body-secondary" style="font-size: 4rem;"></i>
      </div>
    </div>
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
        <h5 class="mb-0" id="npcPreviewName">{{ $previewEditing ? $npc->displayName() : __('NPC name placeholder') }}</h5>
        <span id="npcPreviewStatusBadge" class="badge bg-label-secondary d-none"></span>
      </div>
      <p id="npcPreviewTitles" class="text-warning small fw-medium mb-2 {{ ($previewEditing && $npc->titles?->isNotEmpty()) ? '' : 'd-none' }}">
        {{ $previewEditing ? $npc->titles->pluck('name')->implode(', ') : '' }}
      </p>
      <div id="npcPreviewMetaBadges" class="d-flex flex-wrap gap-1 mb-2"></div>
      <p id="npcPreviewExcerpt" class="text-body-secondary small mb-0">{{ $previewEditing ? \Illuminate\Support\Str::limit($npc->summary ?: $npc->background, 120) : __('Summary preview will appear here.') }}</p>
    </div>
  </article>

  <div class="d-flex align-items-center justify-content-between gap-2 mb-2 mt-1">
    <span class="small text-body-secondary text-uppercase fw-semibold">{{ __('Detail page') }}</span>
  </div>
  <article class="card shadow-sm" id="npcPreviewDetailCard">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-4">
          <div id="npcPreviewDetailPortraitWrap" class="rounded border bg-label-secondary overflow-hidden" style="max-height: 200px;">
            <img id="npcPreviewDetailPortrait" src="{{ $previewEditing ? ($npc->imageUrl() ?? '') : '' }}" alt="" class="img-fluid w-100" style="object-fit: cover; max-height: 200px; {{ $previewEditing && $npc->imageUrl() ? '' : 'display: none;' }}">
            <div id="npcPreviewDetailPortraitPlaceholder" class="d-flex align-items-center justify-content-center {{ $previewEditing && $npc->imageUrl() ? 'd-none' : '' }}" style="height: 160px;">
              <i class="icon-base ti tabler-user text-body-secondary" style="font-size: 3rem;"></i>
            </div>
          </div>
          <dl class="mt-3 mb-0 small" id="npcPreviewDetailFacts"></dl>
        </div>
        <div class="col-sm-8">
          <h4 class="mb-2" id="npcPreviewDetailName">{{ $previewEditing ? $npc->displayName() : '—' }}</h4>
          <p id="npcPreviewDetailTitles" class="text-warning mb-2 {{ ($previewEditing && $npc->titles?->isNotEmpty()) ? '' : 'd-none' }}"></p>
          <section id="npcPreviewDetailSummary" class="mb-3 d-none">
            <h6 class="text-body-secondary">{{ __('Short summary / Appearance') }}</h6>
            <div class="small" style="white-space: pre-wrap" id="npcPreviewDetailSummaryText"></div>
          </section>
          <section id="npcPreviewDetailBackground" class="mb-3 d-none">
            <h6 class="text-body-secondary">{{ __('Background / History') }}</h6>
            <div class="small" style="white-space: pre-wrap" id="npcPreviewDetailBackgroundText"></div>
          </section>
          <section id="npcPreviewDetailNotes" class="d-none">
            <h6 class="text-body-secondary">{{ __('Notes & Relationships') }}</h6>
            <div class="small" style="white-space: pre-wrap" id="npcPreviewDetailNotesText"></div>
          </section>
        </div>
      </div>
    </div>
  </article>

  <script type="application/json" id="npcPreviewConfigJson" class="npc-preview-config-json">@json($npcPreviewConfig, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)</script>
</aside>

<style>
  @media (min-width: 992px) {
    .npc-live-preview {
      position: sticky;
      top: 5rem;
      max-height: calc(100vh - 5.5rem);
      overflow: auto;
      padding-bottom: 0.5rem;
    }
  }
</style>

<script>
(function () {
  function bootNpcLivePreview() {
    var form = document.querySelector('form[data-npc-admin-form]');
    if (!form || form.getAttribute('data-npc-preview-live') === '1') return;
    var panel = form.querySelector('[data-npc-preview-panel]');
    var cfgEl = panel && panel.querySelector('.npc-preview-config-json');
    if (!panel || !cfgEl) return;

    var config;
    try { config = JSON.parse(cfgEl.textContent); } catch (e) { return; }

    form.setAttribute('data-npc-preview-live', '1');
    var objectUrl = null;

    function q(id) { return panel.querySelector('#' + CSS.escape(id)); }
    function val(id) {
      var el = form.querySelector('#' + CSS.escape(id));
      return el && el.value ? String(el.value).trim() : '';
    }
    function titles() {
      var list = [];
      var hidden = form.querySelector('[data-npc-title-hidden]');
      if (hidden && hidden.value) {
        try {
          var parsed = JSON.parse(hidden.value);
          if (Array.isArray(parsed)) list = parsed.filter(Boolean);
        } catch (e) {}
      }
      var pending = form.querySelector('[data-npc-title-input]');
      var p = pending && pending.value ? String(pending.value).trim() : '';
      if (p && list.indexOf(p) === -1) list.push(p);
      return list;
    }
    function refresh() {
      var name = [val('npc-first-name'), val('npc-mid-name'), val('npc-last-name')].filter(Boolean).join(' ');
      var displayName = name || config.namePlaceholder || '';
      var titleLine = titles().join(', ');
      var profession = val('npc-profession');
      var race = val('npc-race');
      var faction = val('npc-faction');
      var location = val('npc-location');
      var status = val('npc-status');
      var summary = val('npc-summary');
      var background = val('npc-background');
      var notes = val('npc-notes');
      var removeImage = form.querySelector('#npc-remove-image') && form.querySelector('#npc-remove-image').checked;
      var fileInput = form.querySelector('#npc-image');
      var imageUrl = config.existingImage || '';
      if (fileInput && fileInput.files && fileInput.files.length) {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(fileInput.files[0]);
        imageUrl = objectUrl;
      }
      [['npcPreviewPortrait', 'npcPreviewPortraitPlaceholder'], ['npcPreviewDetailPortrait', 'npcPreviewDetailPortraitPlaceholder']].forEach(function (pair) {
        var img = q(pair[0]);
        var ph = q(pair[1]);
        if (!img || !ph) return;
        if (imageUrl && !removeImage) {
          img.src = imageUrl;
          img.style.display = '';
          ph.classList.add('d-none');
        } else {
          img.removeAttribute('src');
          img.style.display = 'none';
          ph.classList.remove('d-none');
        }
      });
      var nameEl = q('npcPreviewName');
      var detailName = q('npcPreviewDetailName');
      if (nameEl) nameEl.textContent = displayName;
      if (detailName) detailName.textContent = name || '—';
      ['npcPreviewTitles', 'npcPreviewDetailTitles'].forEach(function (id) {
        var el = q(id);
        if (!el) return;
        if (titleLine) { el.textContent = titleLine; el.classList.remove('d-none'); }
        else { el.textContent = ''; el.classList.add('d-none'); }
      });
      var statusBadge = q('npcPreviewStatusBadge');
      if (statusBadge) {
        if (status && config.statusLabels && config.statusLabels[status]) {
          statusBadge.textContent = config.statusLabels[status];
          statusBadge.className = 'badge ' + (config.statusBadges[status] || 'bg-label-secondary');
          statusBadge.classList.remove('d-none');
        } else statusBadge.classList.add('d-none');
      }
      var meta = q('npcPreviewMetaBadges');
      if (meta) {
        meta.replaceChildren();
        [profession, race, location].filter(Boolean).forEach(function (label) {
          var span = document.createElement('span');
          span.className = 'badge bg-label-primary';
          span.textContent = label;
          meta.appendChild(span);
        });
      }
      var excerpt = q('npcPreviewExcerpt');
      if (excerpt) {
        var text = (summary || background || '').trim();
        if (text.length > 120) text = text.slice(0, 119) + '…';
        excerpt.textContent = text || config.excerptPlaceholder || '';
      }
      var facts = q('npcPreviewDetailFacts');
      if (facts) {
        var L = config.factLabels || {};
        var rows = [
          [L.titles, titleLine], [L.profession, profession], [L.race, race],
          [L.faction, faction], [L.location, location],
          [L.status, status && config.statusLabels ? config.statusLabels[status] : '']
        ];
        facts.replaceChildren();
        rows.forEach(function (row) {
          if (!row[0] || !row[1]) return;
          var dt = document.createElement('dt');
          dt.className = 'text-body-secondary';
          dt.textContent = row[0];
          var dd = document.createElement('dd');
          dd.className = 'mb-1 fw-medium';
          dd.textContent = row[1];
          facts.appendChild(dt);
          facts.appendChild(dd);
        });
      }
      function section(sectionId, textId, content) {
        var section = q(sectionId);
        var node = q(textId);
        if (!section || !node) return;
        var v = String(content || '').trim();
        if (!v) { section.classList.add('d-none'); node.textContent = ''; return; }
        section.classList.remove('d-none');
        node.textContent = v;
      }
      section('npcPreviewDetailSummary', 'npcPreviewDetailSummaryText', summary);
      section('npcPreviewDetailBackground', 'npcPreviewDetailBackgroundText', background);
      section('npcPreviewDetailNotes', 'npcPreviewDetailNotesText', notes);
    }

    form.addEventListener('input', refresh, true);
    form.addEventListener('change', refresh, true);
    document.addEventListener('npc-titles-changed', refresh);
    refresh();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootNpcLivePreview);
  else bootNpcLivePreview();
})();
</script>