@php
    $pfx = $prefix ?? 'god-sub';
@endphp
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('God name') }}</span>
    <input id="{{ $pfx }}-name" name="name" type="text" maxlength="120" required>
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Epithet') }}</span>
    <input id="{{ $pfx }}-epithet" name="epithet" type="text" maxlength="255">
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Category') }}</span>
    <select id="{{ $pfx }}-category" name="category" required>
        <option value="major">{{ __('Major gods') }}</option>
        <option value="minor" selected>{{ __('Minor gods') }}</option>
    </select>
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Alignment') }}</span>
    <input id="{{ $pfx }}-alignment" name="alignment" type="text" maxlength="100" placeholder="{{ __('e.g. Neutral Good') }}">
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Weapon') }}</span>
    <input id="{{ $pfx }}-weapon" name="weapon" type="text" maxlength="255">
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Domains') }}</span>
    <textarea id="{{ $pfx }}-domains" name="domains" rows="2" placeholder="{{ __('One per line or comma-separated') }}"></textarea>
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Subdomains') }}</span>
    <textarea id="{{ $pfx }}-subdomains" name="subdomains" rows="2" placeholder="{{ __('One per line or comma-separated') }}"></textarea>
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Summary') }}</span>
    <textarea id="{{ $pfx }}-summary" name="summary" rows="3" maxlength="10000"></textarea>
</label>
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Profile text') }}</span>
    <textarea id="{{ $pfx }}-profile" name="profile_text" rows="5" maxlength="30000"></textarea>
</label>
@if($pfx === 'god-cor')
    <label class="vs-form-field vs-rumor-form__span">
        <span class="vs-form-field__label">{{ __('Correction notes for archivists') }}</span>
        <textarea name="submitter_notes" rows="2" maxlength="10000"></textarea>
    </label>
@endif
<label class="vs-form-field vs-rumor-form__span">
    <span class="vs-form-field__label">{{ __('Portrait image') }}</span>
    <input id="{{ $pfx }}-image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <p class="vs-rumor-upload-hint">{{ __('JPEG, PNG or WebP · max 5 MB') }}</p>
    <p id="{{ $pfx === 'god-sub' ? 'godSubmitImageMeta' : 'godCorrectionImageMeta' }}" class="vs-npc-image-meta" aria-live="polite"></p>
</label>