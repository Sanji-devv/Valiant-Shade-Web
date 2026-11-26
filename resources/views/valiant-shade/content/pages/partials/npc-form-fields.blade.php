@php
  use App\ValiantShade\Support\NpcRaceCatalog;
  use App\ValiantShade\Support\NpcStatusCatalog;
  $pfsrdRaceNames = NpcRaceCatalog::names();
  $editing = isset($npc) && $npc !== null;
  $currentStatus = old('status', $editing ? $npc->status : '');
  $titleNames = old('titles', $editing ? $npc->titles->pluck('name')->all() : []);
  if (is_string($titleNames)) {
    $decoded = json_decode($titleNames, true);
    $titleNames = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $titleNames)));
  }
@endphp
<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label" for="npc-first-name">{{ __('First name') }}</label>
    <input id="npc-first-name" class="form-control" name="first_name" value="{{ old('first_name', $editing ? $npc->first_name : '') }}" maxlength="255">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="npc-mid-name">{{ __('Middle name') }}</label>
    <input id="npc-mid-name" class="form-control" name="mid_name" value="{{ old('mid_name', $editing ? $npc->mid_name : '') }}" maxlength="255">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="npc-last-name">{{ __('Last name') }}</label>
    <input id="npc-last-name" class="form-control" name="last_name" value="{{ old('last_name', $editing ? $npc->last_name : '') }}" maxlength="255">
  </div>

  <div class="col-12">
    <label class="form-label" for="npc-titles-input">{{ __('Titles / Epithets') }}</label>
    <div
      class="npc-title-tags"
      data-npc-title-tags
      data-suggest-url="{{ route('valiant-shade.pages-npcs.title-suggestions') }}"
      data-initial='@json($titleNames)'
    >
      <span class="npc-title-tags__chips" data-npc-title-chips></span>
      <input id="npc-titles-input" class="form-control npc-title-tags__input" type="text" autocomplete="off"
             placeholder="{{ __('Type a title and press Enter') }}" data-npc-title-input aria-describedby="npc-titles-help">
      <input type="hidden" name="titles" value="{{ e(json_encode($titleNames, JSON_UNESCAPED_UNICODE)) }}" data-npc-title-hidden>
      <ul class="npc-title-tags__suggestions list-unstyled mb-0" data-npc-title-suggestions hidden></ul>
    </div>
    <small id="npc-titles-help" class="text-body-secondary">{{ __('Select existing titles or add new ones.') }}</small>
  </div>

  <div class="col-md-6">
    <label class="form-label" for="npc-profession">{{ __('Profession / Role') }}</label>
    <input id="npc-profession" class="form-control" name="profession" value="{{ old('profession', $editing ? $npc->profession : '') }}" maxlength="255" placeholder="{{ __('e.g. Blacksmith, Guildmaster') }}">
  </div>
  <div class="col-md-6">
    <label class="form-label" for="npc-race">{{ __('Race') }}</label>
    <input id="npc-race" class="form-control" name="race" list="npc-pfsrd-races"
           value="{{ old('race', $editing ? $npc->race : '') }}" maxlength="120"
           placeholder="{{ __('e.g. Elf, Human, Dwarf') }}" autocomplete="off">
    <datalist id="npc-pfsrd-races">
      @foreach($pfsrdRaceNames as $raceName)
        <option value="{{ $raceName }}"></option>
      @endforeach
    </datalist>
    <small class="text-body-secondary">{{ __('Suggestions from Pathfinder SRD races (d20PFSRD). You may type a custom race.') }}</small>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="npc-status">{{ __('Status') }}</label>
    <select id="npc-status" class="form-select" name="status">
      <option value="">{{ __('Not specified') }}</option>
      @foreach(NpcStatusCatalog::options() as $value => $meta)
        <option value="{{ $value }}" @selected((string) $currentStatus === (string) $value)>
          {{ __('npc_status.' . $meta['key']) }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="npc-faction">{{ __('Faction / Affiliation') }}</label>
    <input id="npc-faction" class="form-control" name="faction" value="{{ old('faction', $editing ? $npc->faction : '') }}" maxlength="255">
  </div>
  <div class="col-md-6">
    <label class="form-label" for="npc-location">{{ __('Location') }}</label>
    <input id="npc-location" class="form-control" name="location" value="{{ old('location', $editing ? $npc->location : '') }}" maxlength="255">
  </div>

  <div class="col-12">
    <label class="form-label" for="npc-image">{{ __('Portrait image') }}</label>
    <input id="npc-image" class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <small class="text-body-secondary">{{ __('Max 5 MB. JPEG, PNG, or WebP.') }}</small>
    @if($editing && $npc->imageUrl())
      <div class="d-flex align-items-center gap-3 mt-2">
        <img src="{{ $npc->imageUrl() }}" alt="{{ $npc->displayName() }}" class="rounded border" style="width: 72px; height: 72px; object-fit: cover;">
        <label class="form-check mb-0">
          <input id="npc-remove-image" class="form-check-input" type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
          <span class="form-check-label">{{ __('Remove current image') }}</span>
        </label>
      </div>
    @endif
  </div>

  <div class="col-12">
    <label class="form-label" for="npc-summary">{{ __('Short summary / Appearance') }}</label>
    <textarea id="npc-summary" class="form-control" name="summary" rows="3" maxlength="5000">{{ old('summary', $editing ? $npc->summary : '') }}</textarea>
  </div>
  <div class="col-12">
    <label class="form-label" for="npc-background">{{ __('Background / History') }}</label>
    <textarea id="npc-background" class="form-control" name="background" rows="7" maxlength="100000">{{ old('background', $editing ? $npc->background : '') }}</textarea>
  </div>
  <div class="col-12">
    <label class="form-label" for="npc-notes">{{ __('Notes & Relationships') }}</label>
    <textarea id="npc-notes" class="form-control" name="notes" rows="3" maxlength="10000">{{ old('notes', $editing ? $npc->notes : '') }}</textarea>
  </div>
</div>
