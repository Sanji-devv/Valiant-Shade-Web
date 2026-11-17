@php
    $entryName = is_array($entry) ? ($entry['name'] ?? '') : $entry;
    $entryUrl = is_array($entry) ? ($entry['url'] ?? '') : '';
    $entryId = $field.'-'.$godItem['slug'].'-'.$entryIndex;
@endphp
<div class="row g-2 align-items-end mb-2" data-entry-row>
    <div class="col-5">
        <label class="form-label small" for="{{ $entryId }}-name">{{ $settings['singular'] }} adı</label>
        <input class="form-control" id="{{ $entryId }}-name" name="{{ $field }}[{{ $entryIndex }}][name]" maxlength="255" value="{{ is_string($entryName) ? $entryName : '' }}">
    </div>
    <div class="col-5">
        <label class="form-label small" for="{{ $entryId }}-url">Bağlantı</label>
        <input class="form-control" id="{{ $entryId }}-url" name="{{ $field }}[{{ $entryIndex }}][url]" type="url" maxlength="2048" placeholder="https://..." value="{{ is_string($entryUrl) ? $entryUrl : '' }}">
    </div>
    <div class="col-2">
        <button type="button" class="btn btn-label-danger w-100 px-0" data-entry-remove aria-label="{{ $settings['singular'] }} satırını kaldır" title="Kaldır">&times;</button>
    </div>
</div>
