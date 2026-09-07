export const TILE_PRESETS = [
    { label: '1×1', w: 1, h: 1 },
    { label: '2×2', w: 2, h: 2 },
    { label: '3×2', w: 3, h: 2 },
    { label: '3×3', w: 3, h: 3 },
    { label: '4×2', w: 4, h: 2 },
    { label: '4×4', w: 4, h: 4 },
    { label: '5×5', w: 5, h: 5 },
    { label: '6×2', w: 6, h: 2 },
    { label: '6×4', w: 6, h: 4 },
];

export class BuildingTypeModal {
    constructor({ onSubmit }) {
        this.onSubmit = onSubmit;
        this.selectedW = 2;
        this.selectedH = 2;
        this.mount();
        this.bindEvents();
    }

    mount() {
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'vs-modal-backdrop';
        this.backdrop.hidden = true;

        this.backdrop.innerHTML = `
            <div class="vs-modal vs-modal-simple" role="dialog" aria-modal="true" aria-labelledby="vs-building-modal-title">
                <div class="vs-modal-content">
                    <button type="button" class="vs-modal-close btn-pinned" data-modal-close aria-label="Kapat">
                        <i class="icon-base ti tabler-x"></i>
                    </button>
                    <div class="vs-modal-body">
                        <div class="vs-modal-head">
                            <h3 id="vs-building-modal-title">Yeni bina tipi</h3>
                            <p>Karo boyutunu seçin, ardından bina bilgilerini girin.</p>
                        </div>

                        <section class="vs-modal-section">
                            <h4>Hazır karo şekilleri</h4>
                            <div class="vs-preset-grid" data-preset-grid></div>
                        </section>

                        <section class="vs-modal-section">
                            <h4>Özel boyut</h4>
                            <div class="vs-custom-size">
                                <label>Genişlik
                                    <input type="number" name="custom_w" min="1" max="20" value="2">
                                </label>
                                <span class="vs-custom-size-x">×</span>
                                <label>Yükseklik
                                    <input type="number" name="custom_h" min="1" max="20" value="2">
                                </label>
                            </div>
                            <div class="vs-shape-preview" data-shape-preview>
                                <div class="vs-shape-preview-box" data-preview-box></div>
                                <span data-preview-label>2×2 karo</span>
                            </div>
                        </section>

                        <form class="vs-modal-form" data-building-form>
                            <section class="vs-modal-section">
                                <h4>Bina bilgileri</h4>
                                <div class="vs-form-row">
                                    <label class="vs-form-field vs-form-field--required">
                                        <span>Ad</span>
                                        <input type="text" name="name" required maxlength="64" placeholder="Örn. Gözetleme kulesi">
                                    </label>
                                    <label class="vs-form-field">
                                        <span>Slug</span>
                                        <input type="text" name="slug" maxlength="64" placeholder="otomatik oluşturulur">
                                    </label>
                                </div>
                                <label class="vs-form-field">
                                    <span>Açıklama</span>
                                    <textarea name="description" rows="2" maxlength="500" placeholder="İsteğe bağlı"></textarea>
                                </label>
                                <div class="vs-form-row">
                                    <label class="vs-form-field">
                                        <span>Kategori</span>
                                        <select name="category">
                                            <option value="core">Merkez</option>
                                            <option value="training">Eğitim</option>
                                            <option value="residential">Konut</option>
                                            <option value="utility">Yardımcı</option>
                                            <option value="defense">Savunma</option>
                                        </select>
                                    </label>
                                    <label class="vs-form-field">
                                        <span>İkon (emoji)</span>
                                        <input type="text" name="icon" maxlength="8" placeholder="🏠">
                                    </label>
                                </div>
                                <div class="vs-form-checks">
                                    <label class="vs-form-check">
                                        <input type="checkbox" name="is_unique" value="1">
                                        <span>Haritada yalnızca bir tane</span>
                                    </label>
                                </div>
                            </section>

                            <div class="vs-modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-modal-close>İptal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="icon-base ti tabler-plus icon-sm"></i>
                                    Oluştur ve yerleştir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(this.backdrop);
        this.presetGrid = this.backdrop.querySelector('[data-preset-grid]');
        this.form = this.backdrop.querySelector('[data-building-form]');
        this.previewBox = this.backdrop.querySelector('[data-preview-box]');
        this.previewLabel = this.backdrop.querySelector('[data-preview-label]');
        this.customW = this.backdrop.querySelector('[name="custom_w"]');
        this.customH = this.backdrop.querySelector('[name="custom_h"]');

        this.renderPresets();
        this.updatePreview();
    }

    bindEvents() {
        this.backdrop.addEventListener('click', (event) => {
            if (event.target === this.backdrop) {
                this.close();
            }
        });

        this.backdrop.querySelectorAll('[data-modal-close]').forEach((btn) => {
            btn.addEventListener('click', () => this.close());
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.backdrop.hidden) {
                this.close();
            }
        });

        this.customW.addEventListener('input', () => {
            this.selectSize(Number(this.customW.value) || 1, Number(this.customH.value) || 1, true);
        });
        this.customH.addEventListener('input', () => {
            this.selectSize(Number(this.customW.value) || 1, Number(this.customH.value) || 1, true);
        });

        this.form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = new FormData(this.form);
            const payload = {
                name: data.get('name'),
                slug: data.get('slug') || undefined,
                description: data.get('description') || undefined,
                width_tiles: this.selectedW,
                height_tiles: this.selectedH,
                category: data.get('category') || 'utility',
                icon: data.get('icon') || undefined,
                is_unique: data.get('is_unique') === '1',
            };

            const submitBtn = this.form.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            try {
                await this.onSubmit?.(payload);
                this.close();
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    renderPresets() {
        this.presetGrid.replaceChildren();
        TILE_PRESETS.forEach((preset) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'vs-preset-card';
            button.dataset.w = preset.w;
            button.dataset.h = preset.h;
            button.innerHTML = `
                <span class="vs-preset-shape" style="--pw:${preset.w};--ph:${preset.h}"></span>
                <span>${preset.label}</span>
            `;
            button.addEventListener('click', () => {
                this.selectSize(preset.w, preset.h);
            });
            this.presetGrid.appendChild(button);
        });
        this.highlightPreset();
    }

    selectSize(w, h, fromCustom = false) {
        this.selectedW = Math.min(20, Math.max(1, w));
        this.selectedH = Math.min(20, Math.max(1, h));
        if (!fromCustom) {
            this.customW.value = this.selectedW;
            this.customH.value = this.selectedH;
        }
        this.updatePreview();
        this.highlightPreset();
    }

    highlightPreset() {
        this.presetGrid.querySelectorAll('.vs-preset-card').forEach((card) => {
            const active = Number(card.dataset.w) === this.selectedW
                && Number(card.dataset.h) === this.selectedH;
            card.classList.toggle('vs-preset-card--active', active);
        });
    }

    updatePreview() {
        this.previewBox.style.setProperty('--shape-w', this.selectedW);
        this.previewBox.style.setProperty('--shape-h', this.selectedH);
        this.previewLabel.textContent = `${this.selectedW}×${this.selectedH} karo`;
    }

    open() {
        this.form.reset();
        this.selectSize(2, 2);
        this.backdrop.hidden = false;
        document.body.classList.add('vs-modal-open');
        this.form.querySelector('[name="name"]')?.focus();
    }

    close() {
        this.backdrop.hidden = true;
        document.body.classList.remove('vs-modal-open');
    }
}
