@php
  $editing = isset($npc) && $npc !== null;
  $currentStatus = old('status', $editing ? $npc->status : '');
@endphp

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="npc-name">{{ __('NPC name') }} *</label>
    <input id="npc-name" class="form-control" name="name" value="{{ old('name', $editing ? $npc->name : '') }}" maxlength="255" required>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="npc-title">{{ __('Title / Epithet') }}</label>
    <input id="npc-title" class="form-control" name="title" value="{{ old('title', $editing ? $npc->title : '') }}" maxlength="255" placeholder="{{ __('e.g. Archmage of the Red Tower') }}">
  </div>

  <div class="col-md-4">
    <label class="form-label" for="npc-profession">{{ __('Profession / Role') }}</label>
    <input id="npc-profession" class="form-control" name="profession" value="{{ old('profession', $editing ? $npc->profession : '') }}" maxlength="255" placeholder="{{ __('e.g. Blacksmith, Guildmaster') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="npc-race">{{ __('Race') }}</label>
    <input id="npc-race" class="form-control" name="race" value="{{ old('race', $editing ? $npc->race : '') }}" maxlength="120" placeholder="{{ __('e.g. Elf, Human, Dwarf') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="npc-status">{{ __('Status') }}</label>
    <select id="npc-status" class="form-select" name="status">
      <option value="">{{ __('Not specified') }}</option>
      @foreach(\App\Models\Npc::STATUSES as $statusOption)
        <option value="{{ $statusOption }}" @selected($currentStatus === $statusOption)>
          {{ __('npc_status.' . $statusOption) }}
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
    <small class="text-body-secondary d-block mt-1">{{ __('Accepted formats: JPG, PNG, WEBP (max 5 MB).') }}</small>
    @if($editing && $npc->imageUrl())
      <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
        <img src="{{ $npc->imageUrl() }}" alt="{{ $npc->name }}" class="rounded border" style="width: 72px; height: 72px; object-fit: cover;">
        <div class="form-check">
          <input id="npc-remove-image" class="form-check-input" type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
          <label class="form-check-label" for="npc-remove-image">{{ __('Remove current image') }}</label>
        </div>
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
