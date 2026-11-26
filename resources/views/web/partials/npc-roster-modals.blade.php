@php
    use App\ValiantShade\Support\NpcStatusCatalog;
    $npcSubmitPreviewConfig = [
        'namePlaceholder' => __('NPC name placeholder'),
        'excerptPlaceholder' => __('Summary preview will appear here.'),
        'statusLabels' => collect(NpcStatusCatalog::options())
            ->mapWithKeys(fn (array $meta, int $value): array => [(string) $value => __('npc_status.' . $meta['key'])])
            ->all(),
    ];
@endphp
<div
    id="npcDetailModal"
    class="vs-rumor-modal"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="npcDetailTitle"
    data-label-titles="{{ __('Titles / Epithets') }}"
    data-label-status="{{ __('Status') }}"
    data-label-profession="{{ __('Profession / Role') }}"
    data-label-race="{{ __('Race') }}"
    data-label-faction="{{ __('Faction / Affiliation') }}"
    data-label-location="{{ __('Location') }}"
    data-label-summary="{{ __('Short summary / Appearance') }}"
    data-label-background="{{ __('Background / History') }}"
    data-label-notes="{{ __('Notes') }}"
>
    <div class="vs-rumor-modal__backdrop" data-close-npc-detail tabindex="-1" aria-hidden="true"></div>
    <section class="vs-rumor-modal__panel vs-modal vs-modal--rumor-gazette vs-modal--rumor-gazette--active vs-npc-modal__panel vs-npc-modal__panel--detail">
        <header class="vs-rumor-toolbar" aria-label="{{ __('Detail page') }}">
            <h2 class="vs-rumor-toolbar__title" id="npcDetailTitle"></h2>
            <button type="button" class="vs-rumor-toolbar__close vs-modal__close vs-modal__close--gazette" id="npcDetailClose" aria-label="{{ __('Close') }}">×</button>
        </header>
        <div class="vs-modal__body vs-modal__body--rumor vs-npc-detail-body">
            <div class="vs-npc-detail-layout" id="npcDetailView">
                <aside class="vs-npc-detail-aside" aria-label="{{ __('Portrait') }}">
                    <figure class="vs-npc-detail-portrait-wrap is-empty" id="npcDetailPortraitWrap">
                        <img class="vs-npc-detail-portrait" id="npcDetailPortrait" alt="" decoding="async" hidden>
                    </figure>
                </aside>
                <div class="vs-npc-detail-main">
                    <dl class="vs-npc-detail-facts" id="npcDetailFacts"></dl>
                    <div class="vs-npc-detail-sections" id="npcDetailSections"></div>
                    <div class="vs-npc-detail-actions">
                        <button type="button" class="vs-rumor-submit vs-rumor-submit--ghost" id="npcOpenCorrectionBtn">{{ __('Suggest a correction') }}</button>
                    </div>
                </div>
            </div>

            <div id="npcCorrectionPanel" class="vs-npc-correction-panel" hidden>
                <div class="vs-npc-correction-panel__header">
                    <button type="button" class="vs-npc-correction-back" id="npcCorrectionBackBtn" aria-label="{{ __('Back') }}">← {{ __('Back to detail') }}</button>
                    <p class="vs-npc-correction-panel__lead">{{ __('Propose changes to this roster entry. Archivists will review before anything goes live.') }}</p>
                </div>
                <form
                    id="npcCorrectionForm"
                    class="vs-form-grid vs-form-grid--rumor vs-npc-correction-form"
                    enctype="multipart/form-data"
                    novalidate
                    data-msg-sending="{{ __('Sending correction…') }}"
                    data-msg-image-too-large="{{ __('Portrait must be 5 MB or smaller (JPEG, PNG, or WebP).') }}"
                    data-msg-image-type="{{ __('Use a JPEG, PNG, or WebP image.') }}"
                    data-msg-image-ok="{{ __('File looks good for upload.') }}"
                    data-msg-name-required="{{ __('At least a first or last name is required.') }}"
                    data-msg-fix-fields="{{ __('Please fix the highlighted fields.') }}"
                    data-msg-success="{{ __('Your correction reached the archivists. It will apply after approval.') }}"
                    data-msg-network="{{ __('Could not send. Check your connection and try again.') }}"
                >
                    <div id="npcCorrectionFormFeedback" class="vs-npc-form-feedback vs-rumor-form__span" role="alert" hidden></div>
                    <input type="hidden" name="npc_id" id="npc-cor-id" value="">
                    <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--three vs-rumor-form__span">
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('First name') }}</span>
                            <input id="npc-cor-first" name="first_name" type="text" maxlength="255" autocomplete="given-name">
                        </label>
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('Middle name') }}</span>
                            <input id="npc-cor-mid" name="mid_name" type="text" maxlength="255" autocomplete="additional-name">
                        </label>
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('Last name') }}</span>
                            <input id="npc-cor-last" name="last_name" type="text" maxlength="255" autocomplete="family-name">
                        </label>
                    </div>

                    <label class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Status') }}</span>
                        <select id="npc-cor-status" name="status">
                            <option value="">{{ __('Not specified') }}</option>
                            @foreach(NpcStatusCatalog::options() as $value => $meta)
                                <option value="{{ $value }}">{{ __('npc_status.' . $meta['key']) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Titles / Epithets') }}</span>
                        <div data-npc-title-tags data-suggest-url="/api/public/npcs/title-suggestions" data-initial="[]" data-npc-correction-titles>
                            <span data-npc-title-chips></span>
                            <input class="npc-title-tags__input vs-npc-gazette-input" type="text" data-npc-title-input placeholder="{{ __('Type a title and press Enter') }}">
                            <input type="hidden" name="titles" value="[]" data-npc-title-hidden>
                            <ul data-npc-title-suggestions hidden></ul>
                        </div>
                    </label>

                    <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--four vs-rumor-form__span">
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('Profession / Role') }}</span>
                            <input id="npc-cor-profession" name="profession" type="text" maxlength="255">
                        </label>
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('Race') }}</span>
                            <select id="npc-cor-race" name="race" class="vs-npc-select2" data-placeholder="{{ __('e.g. Elf, Human, Dwarf') }}">
                                <option value=""></option>
                                @foreach($pfsrdRaceNames as $raceName)
                                    <option value="{{ $raceName }}">{{ $raceName }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('Faction / Affiliation') }}</span>
                            <input id="npc-cor-faction" name="faction" type="text" maxlength="255">
                        </label>
                        <label class="vs-form-field">
                            <span class="vs-form-field__label">{{ __('Location') }}</span>
                            <input id="npc-cor-location" name="location" type="text" maxlength="255">
                        </label>
                    </div>

                    <label class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Short summary / Appearance') }}</span>
                        <textarea id="npc-cor-summary" name="summary" rows="3" maxlength="5000"></textarea>
                    </label>

                    <label class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Background / History') }}</span>
                        <textarea id="npc-cor-background" name="background" rows="5" maxlength="100000"></textarea>
                    </label>

                    <label class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Correction notes for archivists') }}</span>
                        <textarea id="npc-cor-notes" name="notes" rows="2" maxlength="10000" placeholder="{{ __('Briefly explain what you changed and why (optional).') }}"></textarea>
                    </label>

                    <label class="vs-form-field vs-rumor-form__span">
                        <span class="vs-form-field__label">{{ __('Portrait image') }}</span>
                        <input id="npc-cor-image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        <p class="vs-rumor-upload-hint">{{ __('JPEG, PNG or WebP · max 5 MB') }}. {{ __('Leave empty to keep the current portrait.') }}</p>
                        <p id="npcCorrectionImageMeta" class="vs-npc-image-meta" aria-live="polite"></p>
                    </label>

                    <div class="vs-rumor-form__actions vs-rumor-form__span">
                        <button type="submit" class="vs-rumor-submit" id="npcCorrectionSubmitBtn">{{ __('Send correction for approval') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<div
    id="npcSubmitModal"
    class="vs-rumor-modal"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="npcSubmitTitle"
    data-msg-sending="{{ __('Sending…') }}"
    data-msg-image-too-large="{{ __('Portrait must be 5 MB or smaller (JPEG, PNG, or WebP).') }}"
    data-msg-image-type="{{ __('Use a JPEG, PNG, or WebP image.') }}"
    data-msg-image-ok="{{ __('File looks good for upload.') }}"
    data-msg-name-required="{{ __('At least a first or last name is required.') }}"
    data-msg-fix-fields="{{ __('Please fix the highlighted fields.') }}"
    data-msg-success="{{ __('Your NPC record reached the archivists. It will appear after approval.') }}"
    data-msg-network="{{ __('Could not send. Check your connection and try again.') }}"
>
    <div class="vs-rumor-modal__backdrop" data-close-npc-submit tabindex="-1" aria-hidden="true"></div>
    <section class="vs-rumor-modal__panel vs-modal vs-modal--rumor-gazette vs-modal--rumor-gazette--active vs-npc-modal__panel vs-npc-modal__panel--submit">
        <header class="vs-rumor-toolbar" aria-label="{{ __('Submit NPC') }}">
            <h2 class="vs-rumor-toolbar__title" id="npcSubmitTitle">{{ __('Submit NPC') }}</h2>
            <button type="button" class="vs-rumor-toolbar__close vs-modal__close vs-modal__close--gazette" id="npcSubmitClose" aria-label="{{ __('Close') }}">×</button>
        </header>
        <div class="vs-modal__body vs-modal__body--rumor">
            <div class="vs-npc-submit-layout">
            <form id="npcSubmitForm" class="vs-form-grid vs-form-grid--rumor vs-npc-submit-form" enctype="multipart/form-data" novalidate>
                <div id="npcSubmitFormFeedback" class="vs-npc-form-feedback vs-rumor-form__span" role="alert" hidden></div>
                <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--three vs-rumor-form__span">
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('First name') }}</span>
                        <input id="npc-sub-first" name="first_name" type="text" maxlength="255" autocomplete="given-name">
                    </label>
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('Middle name') }}</span>
                        <input id="npc-sub-mid" name="mid_name" type="text" maxlength="255" autocomplete="additional-name">
                    </label>
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('Last name') }}</span>
                        <input id="npc-sub-last" name="last_name" type="text" maxlength="255" autocomplete="family-name">
                    </label>
                </div>

                <label class="vs-form-field vs-rumor-form__span">
                    <span class="vs-form-field__label">{{ __('Status') }}</span>
                    <select id="npc-sub-status" name="status">
                        <option value="">{{ __('Not specified') }}</option>
                        @foreach(NpcStatusCatalog::options() as $value => $meta)
                            <option value="{{ $value }}">{{ __('npc_status.' . $meta['key']) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="vs-form-field vs-rumor-form__span">
                    <span class="vs-form-field__label">{{ __('Titles / Epithets') }}</span>
                    <div data-npc-title-tags data-suggest-url="/api/public/npcs/title-suggestions" data-initial="[]">
                        <span data-npc-title-chips></span>
                        <input class="npc-title-tags__input vs-npc-gazette-input" type="text" data-npc-title-input placeholder="{{ __('Type a title and press Enter') }}">
                        <input type="hidden" name="titles" value="[]" data-npc-title-hidden>
                        <ul data-npc-title-suggestions hidden></ul>
                    </div>
                </label>

                <div class="vs-rumor-form__meta-grid vs-rumor-form__meta-grid--four vs-rumor-form__span">
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('Profession / Role') }}</span>
                        <input id="npc-sub-profession" name="profession" type="text" maxlength="255">
                    </label>
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('Race') }}</span>
                        <select id="npc-sub-race" name="race" class="vs-npc-select2" data-placeholder="{{ __('e.g. Elf, Human, Dwarf') }}">
                            <option value=""></option>
                            @foreach($pfsrdRaceNames as $raceName)
                                <option value="{{ $raceName }}">{{ $raceName }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('Faction / Affiliation') }}</span>
                        <input id="npc-sub-faction" name="faction" type="text" maxlength="255">
                    </label>
                    <label class="vs-form-field">
                        <span class="vs-form-field__label">{{ __('Location') }}</span>
                        <input id="npc-sub-location" name="location" type="text" maxlength="255">
                    </label>
                </div>

                <label class="vs-form-field vs-rumor-form__span">
                    <span class="vs-form-field__label">{{ __('Short summary / Appearance') }}</span>
                    <textarea id="npc-sub-summary" name="summary" rows="3" maxlength="5000"></textarea>
                </label>

                <label class="vs-form-field vs-rumor-form__span">
                    <span class="vs-form-field__label">{{ __('Background / History') }}</span>
                    <textarea id="npc-sub-background" name="background" rows="5" maxlength="100000"></textarea>
                </label>

                <label class="vs-form-field vs-rumor-form__span">
                    <span class="vs-form-field__label">{{ __('Portrait image') }}</span>
                    <input id="npc-sub-image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <p class="vs-rumor-upload-hint">{{ __('JPEG, PNG or WebP · max 5 MB') }}</p>
                    <p id="npcSubmitImageMeta" class="vs-npc-image-meta" aria-live="polite"></p>
                </label>

                <div class="vs-rumor-form__actions vs-rumor-form__span">
                    <button type="submit" class="vs-rumor-submit" id="npcSubmitBtn">{{ __('Send to archivists') }}</button>
                </div>
            </form>

            <aside class="vs-npc-submit-preview" data-npc-submit-preview aria-live="polite">
                <script type="application/json" data-npc-submit-preview-config">@json($npcSubmitPreviewConfig)</script>
                <p class="vs-npc-submit-preview__kicker">{{ __('Live preview') }}</p>
                <p class="vs-npc-submit-preview__hint">{{ __('As on roster card') }}</p>
                <div class="vs-npc-card vs-npc-card--list vs-npc-submit-preview__card" aria-hidden="false">
                    <div class="vs-npc-card__media">
                        <img class="vs-npc-card__portrait" data-preview-portrait alt="" hidden>
                        <div class="vs-npc-card__portrait vs-npc-card__portrait--empty" data-preview-portrait-empty aria-hidden="true"></div>
                    </div>
                    <div class="vs-npc-card__body">
                        <h3 class="vs-npc-card__name" data-preview-name>{{ __('NPC name placeholder') }}</h3>
                        <div class="vs-npc-card__titles" data-preview-titles hidden></div>
                        <div class="vs-npc-card__meta" data-preview-meta></div>
                        <p class="vs-npc-card__excerpt" data-preview-excerpt>{{ __('Summary preview will appear here.') }}</p>
                    </div>
                </div>
            </aside>
            </div>
        </div>
    </section>
</div>