import { mountNpcTitleTags } from './npc-title-tags.js';
import { mountNpcFormPreview } from './npc-form-preview.js';

function bootNpcAdminForms() {
  document.querySelectorAll('[data-npc-title-tags]').forEach(mountNpcTitleTags);
  document.querySelectorAll('[data-npc-admin-form]').forEach(mountNpcFormPreview);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootNpcAdminForms);
} else {
  bootNpcAdminForms();
}