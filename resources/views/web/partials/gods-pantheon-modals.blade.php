<div
    id="godDetailModal"
    class="vs-rumor-modal"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="godDetailTitle"
    data-label-epithet="{{ __('Epithet') }}"
    data-label-alignment="{{ __('Alignment') }}"
    data-label-category="{{ __('Category') }}"
    data-label-weapon="{{ __('Weapon') }}"
    data-label-domains="{{ __('Domains') }}"
    data-label-subdomains="{{ __('Subdomains') }}"
    data-label-summary="{{ __('Summary') }}"
    data-label-profile="{{ __('Profile text') }}"
>
    <div class="vs-rumor-modal__backdrop" data-close-god-detail tabindex="-1" aria-hidden="true"></div>
    <section class="vs-rumor-modal__panel vs-modal vs-modal--rumor-gazette vs-modal--rumor-gazette--active vs-npc-modal__panel vs-npc-modal__panel--detail">
        <header class="vs-rumor-toolbar">
            <h2 class="vs-rumor-toolbar__title" id="godDetailTitle"></h2>
            <button type="button" class="vs-rumor-toolbar__close vs-modal__close vs-modal__close--gazette" id="godDetailClose" aria-label="{{ __('Close') }}">×</button>
        </header>
        <div class="vs-modal__body vs-modal__body--rumor vs-npc-detail-body">
            <div class="vs-npc-detail-layout" id="godDetailView">
                <aside class="vs-npc-detail-aside" aria-label="{{ __('Portrait') }}">
                    <figure class="vs-npc-detail-portrait-wrap is-empty" id="godDetailPortraitWrap">
                        <img class="vs-npc-detail-portrait" id="godDetailPortrait" alt="" hidden>
                    </figure>
                </aside>
                <div class="vs-npc-detail-main">
                    <dl class="vs-npc-detail-facts" id="godDetailFacts"></dl>
                    <div class="vs-npc-detail-sections" id="godDetailSections"></div>
                    <div class="vs-npc-detail-actions">
                        <button type="button" class="vs-rumor-submit vs-rumor-submit--ghost" id="godOpenCorrectionBtn">{{ __('Suggest a correction') }}</button>
                    </div>
                </div>
            </div>
            <div id="godCorrectionPanel" class="vs-npc-correction-panel" hidden>
                <div class="vs-npc-correction-panel__header">
                    <button type="button" class="vs-npc-correction-back" id="godCorrectionBackBtn">← {{ __('Back to detail') }}</button>
                    <p class="vs-npc-correction-panel__lead">{{ __('Propose changes to this pantheon entry. Archivists will review before anything goes live.') }}</p>
                </div>
                <form
                    id="godCorrectionForm"
                    class="vs-form-grid vs-form-grid--rumor"
                    enctype="multipart/form-data"
                    novalidate
                    data-msg-sending="{{ __('Sending correction…') }}"
                    data-msg-image-too-large="{{ __('Portrait must be 5 MB or smaller (JPEG, PNG, or WebP).') }}"
                    data-msg-image-type="{{ __('Use a JPEG, PNG, or WebP image.') }}"
                    data-msg-image-ok="{{ __('File looks good for upload.') }}"
                    data-msg-name-required="{{ __('God name is required.') }}"
                    data-msg-fix-fields="{{ __('Please fix the highlighted fields.') }}"
                    data-msg-success="{{ __('Your correction reached the archivists. It will apply after approval.') }}"
                    data-msg-network="{{ __('Could not send. Check your connection and try again.') }}"
                >
                    <input type="hidden" id="god-cor-slug" name="god_slug" value="">
                    <div id="godCorrectionFormFeedback" class="vs-npc-form-feedback vs-rumor-form__span" role="alert" hidden></div>
                    @include('web.partials.gods-pantheon-form-fields', ['prefix' => 'god-cor'])
                    <div class="vs-rumor-form__actions vs-rumor-form__span">
                        <button type="submit" class="vs-rumor-submit" id="godCorrectionSubmitBtn">{{ __('Send correction for approval') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<div
    id="godSubmitModal"
    class="vs-rumor-modal"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="godSubmitTitle"
    data-msg-sending="{{ __('Sending…') }}"
    data-msg-image-too-large="{{ __('Portrait must be 5 MB or smaller (JPEG, PNG, or WebP).') }}"
    data-msg-image-type="{{ __('Use a JPEG, PNG, or WebP image.') }}"
    data-msg-image-ok="{{ __('File looks good for upload.') }}"
    data-msg-name-required="{{ __('God name is required.') }}"
    data-msg-fix-fields="{{ __('Please fix the highlighted fields.') }}"
    data-msg-success="{{ __('Your god record reached the archivists. It will appear after approval.') }}"
    data-msg-network="{{ __('Could not send. Check your connection and try again.') }}"
>
    <div class="vs-rumor-modal__backdrop" data-close-god-submit tabindex="-1" aria-hidden="true"></div>
    <section class="vs-rumor-modal__panel vs-modal vs-modal--rumor-gazette vs-modal--rumor-gazette--active vs-npc-modal__panel vs-npc-modal__panel--submit">
        <header class="vs-rumor-toolbar">
            <h2 class="vs-rumor-toolbar__title" id="godSubmitTitle">{{ __('Submit god') }}</h2>
            <button type="button" class="vs-rumor-toolbar__close vs-modal__close vs-modal__close--gazette" id="godSubmitClose" aria-label="{{ __('Close') }}">×</button>
        </header>
        <div class="vs-modal__body vs-modal__body--rumor">
            <form id="godSubmitForm" class="vs-form-grid vs-form-grid--rumor" enctype="multipart/form-data" novalidate>
                <div id="godSubmitFormFeedback" class="vs-npc-form-feedback vs-rumor-form__span" role="alert" hidden></div>
                @include('web.partials.gods-pantheon-form-fields', ['prefix' => 'god-sub'])
                <div class="vs-rumor-form__actions vs-rumor-form__span">
                    <button type="submit" class="vs-rumor-submit" id="godSubmitBtn">{{ __('Send to archivists') }}</button>
                </div>
            </form>
        </div>
    </section>
</div>