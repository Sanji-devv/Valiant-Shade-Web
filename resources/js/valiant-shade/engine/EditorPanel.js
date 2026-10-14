import { VOID_TILE_DEF, VOID_TILE_ID, TERRAIN_PAN_TOOL } from './tiles.js';

export class EditorPanel {
    constructor(root, callbacks) {
        this.root = root;
        Object.assign(this, callbacks);

        this.mode = 'building';
        this.terrainTool = TERRAIN_PAN_TOOL;
        this.selectedTileTypeId = null;
        this.selectedBuildingTypeId = null;
        this.tileTypes = [];
        this.buildingTypes = [];
        this.buildings = [];
        this.portals = [];
        this.availableMaps = [];
        this.selectedPortalId = null;

        this.modeButtons = this.root.querySelectorAll('[data-editor-mode]');
        this.terrainPanel = this.root.querySelector('[data-panel="terrain"]');
        this.buildingPanel = this.root.querySelector('[data-panel="building"]');
        this.portalPanel = this.root.querySelector('[data-panel="portal"]');
        this.modeLabel = this.root.querySelector('[data-mode-label]');
        this.palette = this.root.querySelector('[data-tile-palette]');
        this.selectedTilePreview = this.root.querySelector('[data-selected-tile-preview]');
        this.selectedTileName = this.root.querySelector('[data-selected-tile-name]');
        this.colorForm = this.root.querySelector('[data-tile-color-form]');
        this.newTileForm = this.root.querySelector('[data-new-tile-form]');
        this.buildingList = this.root.querySelector('[data-building-list]');
        this.buildingTypeList = this.root.querySelector('[data-building-type-list]');
        this.openBuildingModalBtn = this.root.querySelector('[data-open-building-modal]');
        this.deleteBuildingBtn = this.root.querySelector('[data-delete-building]');
        this.portalForm = this.root.querySelector('[data-portal-form]');
        this.portalList = this.root.querySelector('[data-portal-list]');
        this.portalTargets = this.root.querySelector('[data-portal-targets]');
        this.portalSubmitBtn = this.root.querySelector('[data-portal-submit]');
        this.portalUpdateBtn = this.root.querySelector('[data-portal-update]');
        this.portalDeleteBtn = this.root.querySelector('[data-portal-delete]');

        this.bindEvents();
        this.bindCollapsibles();
        this.syncModeUI('building');
    }

    bindCollapsibles() {
        this.root.querySelectorAll('[data-collapse-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.closest('[data-collapse]');
                target?.classList.toggle('vs-collapse--closed');
            });
        });
    }

    bindEvents() {
        this.modeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                this.setMode(button.dataset.editorMode);
            });
        });

        this.openBuildingModalBtn?.addEventListener('click', () => {
            this.onOpenBuildingModal?.();
        });

        this.colorForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = Number(this.colorForm.dataset.tileTypeId);
            if (!id) {
                return;
            }
            await this.onTileColorsSave?.(
                id,
                this.colorForm.fill_color.value,
                this.colorForm.stroke_color.value,
            );
        });

        this.newTileForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await this.onTileTypeCreate?.({
                name: this.newTileForm.name.value,
                fill_color: this.newTileForm.fill_color.value,
                stroke_color: this.newTileForm.stroke_color.value,
            });
            this.newTileForm.reset();
            this.newTileForm.fill_color.value = '#6b8f6b';
            this.newTileForm.stroke_color.value = '#4a6b4a';
        });

        this.deleteBuildingBtn?.addEventListener('click', () => this.onDeleteBuilding?.());

        this.root.querySelector('[data-expand-width]')?.addEventListener('click', () => this.onExpand?.(5, 0));
        this.root.querySelector('[data-expand-height]')?.addEventListener('click', () => this.onExpand?.(0, 5));
        this.root.querySelector('[data-expand-both]')?.addEventListener('click', () => this.onExpand?.(5, 5));

        this.portalForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const payload = this.readPortalForm();
            if (this.selectedPortalId) {
                this.onUpdatePortal?.(this.selectedPortalId, payload);
            } else {
                this.onCreatePortal?.(payload);
            }
        });

        this.portalUpdateBtn?.addEventListener('click', () => {
            if (!this.selectedPortalId) {
                return;
            }
            this.onUpdatePortal?.(this.selectedPortalId, this.readPortalForm());
        });

        this.portalDeleteBtn?.addEventListener('click', () => {
            if (this.selectedPortalId) {
                this.onDeletePortal?.(this.selectedPortalId);
            }
        });
    }

    readPortalForm() {
        return {
            name: this.portalForm.name.value.trim(),
            target_map_slug: this.portalForm.target_map_slug.value,
            description: this.portalForm.description.value.trim() || null,
            width_tiles: Number(this.portalForm.width_tiles.value),
            height_tiles: Number(this.portalForm.height_tiles.value),
        };
    }

    setMode(mode) {
        this.syncModeUI(mode);
        this.onModeChange?.(mode);
    }

    syncModeUI(mode) {
        this.mode = mode;
        if (mode === 'terrain') {
            this.terrainTool = TERRAIN_PAN_TOOL;
        }
        this.modeButtons.forEach((button) => {
            button.classList.toggle('vs-editor-tab--active', button.dataset.editorMode === mode);
        });
        this.terrainPanel?.toggleAttribute('hidden', mode !== 'terrain');
        this.buildingPanel?.toggleAttribute('hidden', mode !== 'building');
        this.portalPanel?.toggleAttribute('hidden', mode !== 'portal');
        if (this.modeLabel) {
            const labels = {
                terrain: this.terrainTool === TERRAIN_PAN_TOOL
                    ? 'Haritayı kaydırmak için sürükleyin — zemin boyamak için paletten seçin'
                    : 'Zemin boyama — yapılar silüet olarak görünür',
                building: 'Bina taşıma — sağ tık menüsü · sürükleyerek taşıyın',
                portal: 'Geçit yerleştirme — haritaya tıklayın veya sürükleyerek taşıyın',
            };
            this.modeLabel.textContent = labels[mode] ?? 'Haritada düzenleyin';
        }
        this.root.dataset.activeMode = mode;
        if (mode === 'terrain') {
            this.renderPalette();
            this.updateSelectedTilePreview();
        }
    }

    setHistoryState({ canUndo, canRedo, isDirty }) {
        const undoBtn = document.querySelector('[data-undo]');
        const redoBtn = document.querySelector('[data-redo]');
        const saveBtn = document.querySelector('[data-save]');
        if (undoBtn) {
            undoBtn.disabled = !canUndo;
        }
        if (redoBtn) {
            redoBtn.disabled = !canRedo;
        }
        if (saveBtn) {
            saveBtn.disabled = !isDirty;
            saveBtn.classList.toggle('vs-btn-save--dirty', Boolean(isDirty));
        }
    }

    setTileTypes(tileTypes, selectedId) {
        this.tileTypes = tileTypes;
        this.selectedTileTypeId = selectedId;
        this.renderPalette();
        this.updateSelectedTilePreview();
    }

    setTerrainTool(tool, tileId = null) {
        this.terrainTool = tool;
        if (tool === 'paint') {
            this.selectedTileTypeId = tileId;
        }
        this.renderPalette();
        this.updateSelectedTilePreview();
        if (this.mode === 'terrain' && this.modeLabel) {
            const labels = {
                [TERRAIN_PAN_TOOL]: 'Haritayı kaydırmak için sürükleyin — zemin boyamak için paletten seçin',
                paint: 'Zemin boyama — yapılar silüet olarak görünür',
            };
            this.modeLabel.textContent = labels[tool] ?? labels[TERRAIN_PAN_TOOL];
        }
    }

    setBuildingTypes(buildingTypes) {
        this.buildingTypes = buildingTypes;
        this.renderBuildingTypes();
    }

    setBuildings(buildings) {
        this.buildings = buildings;
        this.renderBuildingList();
        this.renderBuildingTypes();
    }

    setAvailableMaps(maps) {
        this.availableMaps = maps ?? [];
        if (!this.portalTargets) {
            return;
        }
        this.portalTargets.replaceChildren();
        this.availableMaps.forEach((map) => {
            const option = document.createElement('option');
            option.value = map.slug;
            option.textContent = map.name;
            this.portalTargets.appendChild(option);
        });
    }

    setPortals(portals) {
        this.portals = (portals ?? []).map((portal) => ({
            ...portal,
            target_map_name: portal.target_map_name
                ?? this.availableMaps.find((map) => map.slug === portal.target_map_slug)?.name,
        }));
        this.renderPortalList();
    }

    selectPortal(portal) {
        this.selectedPortalId = portal?.id ?? null;
        this.renderPortalList();
        this.fillPortalForm(portal);
    }

    clearPortalSelection() {
        this.selectedPortalId = null;
        this.portalForm?.reset();
        if (this.portalForm) {
            this.portalForm.width_tiles.value = 2;
            this.portalForm.height_tiles.value = 2;
        }
        this.portalSubmitBtn?.toggleAttribute('hidden', false);
        this.portalUpdateBtn?.toggleAttribute('hidden', true);
        this.portalDeleteBtn?.toggleAttribute('hidden', true);
        this.renderPortalList();
    }

    fillPortalForm(portal) {
        if (!portal || !this.portalForm) {
            this.clearPortalSelection();
            return;
        }

        this.portalForm.name.value = portal.name;
        this.portalForm.target_map_slug.value = portal.target_map_slug;
        this.portalForm.description.value = portal.description ?? '';
        this.portalForm.width_tiles.value = portal.width_tiles;
        this.portalForm.height_tiles.value = portal.height_tiles;
        this.portalSubmitBtn?.toggleAttribute('hidden', true);
        this.portalUpdateBtn?.toggleAttribute('hidden', false);
        this.portalDeleteBtn?.toggleAttribute('hidden', false);
    }

    renderPortalList() {
        if (!this.portalList) {
            return;
        }

        this.portalList.replaceChildren();

        if (this.portals.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'vs-empty-note';
            empty.textContent = 'Henüz geçit yok.';
            this.portalList.appendChild(empty);
            return;
        }

        this.portals.forEach((portal) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = `vs-portal-list-item${portal.id === this.selectedPortalId ? ' vs-portal-list-item--active' : ''}`;
            item.innerHTML = `
                <strong>${portal.name}</strong>
                <small>→ ${portal.target_map_name ?? portal.target_map_slug} · (${portal.grid_x}, ${portal.grid_y}) · ${portal.width_tiles}×${portal.height_tiles}</small>
            `;
            item.addEventListener('click', () => {
                this.onPortalSelect?.(portal);
            });
            this.portalList.appendChild(item);
        });
    }

    findPlacedBuilding(typeId) {
        return this.buildings.find((building) => building.type.id === typeId);
    }

    renderBuildingList() {
        if (!this.buildingList) {
            return;
        }

        this.buildingList.replaceChildren();

        if (this.buildings.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'vs-empty-note';
            empty.textContent = 'Henüz yapı yok.';
            this.buildingList.appendChild(empty);
            return;
        }

        this.buildings.forEach((building) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'vs-building-list-item';
            const thumb = building.type.sprite_url
                ? `<img src="${building.type.sprite_url}" alt="" class="vs-building-list-thumb">`
                : `<span class="vs-building-list-icon">${building.type.icon ?? '🏠'}</span>`;
            item.innerHTML = `
                ${thumb}
                <div>
                    <strong>${building.label ?? building.type.name}</strong>
                    <small>${building.type.width_tiles}×${building.type.height_tiles} · (${building.grid_x}, ${building.grid_y})</small>
                </div>
            `;
            item.addEventListener('click', () => {
                this.buildingList.querySelectorAll('.vs-building-list-item').forEach((el) => {
                    el.classList.remove('vs-building-list-item--active');
                });
                item.classList.add('vs-building-list-item--active');
                this.onBuildingSelect?.(building);
            });
            this.buildingList.appendChild(item);
        });
    }

    renderBuildingTypes() {
        if (!this.buildingTypeList) {
            return;
        }

        this.buildingTypeList.replaceChildren();

        this.buildingTypes.forEach((type) => {
            const placed = type.is_unique ? this.findPlacedBuilding(type.id) : null;
            const card = document.createElement('div');
            card.className = `vs-type-card${type.id === this.selectedBuildingTypeId ? ' vs-type-card--active' : ''}${placed ? ' vs-type-card--placed' : ''}`;

            const previewContent = type.sprite_url
                ? `<img src="${type.sprite_url}" alt="" class="vs-type-card-sprite">`
                : `<span class="vs-type-card-icon">${type.icon ?? '🏠'}</span>`;

            const toggleBtn = placed
                ? `<button type="button" class="btn-icon btn-icon--danger" data-toggle-type="${type.id}" title="Haritadan kaldır"><i class="icon-base ti tabler-trash icon-sm"></i></button>`
                : `<button type="button" class="btn-icon btn-icon--success" data-toggle-type="${type.id}" title="Haritaya ekle"><i class="icon-base ti tabler-map-pin-plus icon-sm"></i></button>`;

            card.innerHTML = `
                <div class="vs-type-card-top">
                    <div class="vs-type-card-shape" style="--shape-w:${type.width_tiles};--shape-h:${type.height_tiles}">
                        <div class="vs-type-card-shape-inner">${previewContent}</div>
                    </div>
                    <div class="vs-type-card-toolbar">
                        ${toggleBtn}
                        <label class="btn-icon btn-icon--label" title="Görsel yükle">
                            <i class="icon-base ti tabler-photo-up icon-sm"></i>
                            <input type="file" accept="image/*" hidden data-sprite-upload="${type.id}">
                        </label>
                    </div>
                </div>
                <div class="vs-type-card-meta">
                    <strong title="${type.name}">${type.name}</strong>
                    <small>${type.width_tiles}×${type.height_tiles}</small>
                </div>
            `;

            card.querySelector('[data-toggle-type]')?.addEventListener('click', () => {
                if (placed) {
                    this.onRemoveBuildingType?.(type.id);
                } else {
                    this.selectedBuildingTypeId = type.id;
                    this.renderBuildingTypes();
                    this.onPlaceBuilding?.(type.id);
                }
            });

            card.querySelector('[data-sprite-upload]')?.addEventListener('change', async (event) => {
                const file = event.target.files?.[0];
                if (file) {
                    await this.onUploadSprite?.(type.id, file);
                    event.target.value = '';
                }
            });

            this.buildingTypeList.appendChild(card);
        });
    }

    renderPalette() {
        if (!this.palette) {
            return;
        }

        this.palette.replaceChildren();

        const panButton = document.createElement('button');
        panButton.type = 'button';
        panButton.className = `vs-palette-card${this.terrainTool === TERRAIN_PAN_TOOL ? ' vs-palette-card--active' : ''}`;
        panButton.innerHTML = `
            <span class="vs-palette-swatch vs-palette-swatch--pan">
                <i class="icon-base ti tabler-hand-move icon-sm"></i>
            </span>
            <span class="vs-palette-card-name">Kaydır</span>
        `;
        panButton.addEventListener('click', () => {
            this.terrainTool = TERRAIN_PAN_TOOL;
            this.onTerrainPanSelect?.();
            this.renderPalette();
            this.updateSelectedTilePreview();
        });
        this.palette.appendChild(panButton);

        const voidButton = document.createElement('button');
        voidButton.type = 'button';
        voidButton.className = `vs-palette-card${this.terrainTool === 'paint' && this.selectedTileTypeId === VOID_TILE_ID ? ' vs-palette-card--active' : ''}`;
        voidButton.innerHTML = `
            <span class="vs-palette-swatch vs-palette-swatch--void"></span>
            <span class="vs-palette-card-name">${VOID_TILE_DEF.label}</span>
        `;
        voidButton.addEventListener('click', () => {
            this.terrainTool = 'paint';
            this.selectedTileTypeId = VOID_TILE_ID;
            this.onTileSelect?.(VOID_TILE_ID);
            this.renderPalette();
            this.updateSelectedTilePreview();
        });
        this.palette.appendChild(voidButton);

        this.tileTypes.forEach((type) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `vs-palette-card${this.terrainTool === 'paint' && type.id === this.selectedTileTypeId ? ' vs-palette-card--active' : ''}`;
            button.innerHTML = `
                <span class="vs-palette-swatch" style="background:${type.fill_color};border-color:${type.stroke_color}"></span>
                <span class="vs-palette-card-name">${type.name}</span>
            `;
            button.addEventListener('click', () => {
                this.terrainTool = 'paint';
                this.selectedTileTypeId = type.id;
                this.onTileSelect?.(type.id);
                this.renderPalette();
                this.updateSelectedTilePreview();
            });
            this.palette.appendChild(button);
        });
    }

    updateSelectedTilePreview() {
        if (!this.selectedTilePreview || !this.selectedTileName) {
            return;
        }

        if (this.terrainTool === TERRAIN_PAN_TOOL) {
            this.selectedTilePreview.className = 'vs-palette-swatch vs-palette-swatch--lg vs-palette-swatch--pan';
            this.selectedTilePreview.style.background = '';
            this.selectedTilePreview.style.borderColor = '';
            this.selectedTilePreview.innerHTML = '<i class="icon-base ti tabler-hand-move icon-sm"></i>';
            this.selectedTileName.textContent = 'Kaydır';
            if (this.colorForm) {
                this.colorForm.hidden = true;
            }
            return;
        }

        this.selectedTilePreview.innerHTML = '';

        if (this.selectedTileTypeId === VOID_TILE_ID) {
            this.selectedTilePreview.className = 'vs-palette-swatch vs-palette-swatch--lg vs-palette-swatch--void';
            this.selectedTilePreview.style.background = '';
            this.selectedTilePreview.style.borderColor = '';
            this.selectedTileName.textContent = VOID_TILE_DEF.label;
            if (this.colorForm) {
                this.colorForm.hidden = true;
            }
            return;
        }

        const type = this.tileTypes.find((item) => item.id === this.selectedTileTypeId);
        if (!type) {
            return;
        }

        this.selectedTilePreview.className = 'vs-palette-swatch vs-palette-swatch--lg';
        this.selectedTilePreview.style.background = type.fill_color;
        this.selectedTilePreview.style.borderColor = type.stroke_color;
        this.selectedTileName.textContent = type.name;

        if (this.colorForm) {
            this.colorForm.hidden = false;
            this.colorForm.dataset.tileTypeId = type.id;
            this.colorForm.fill_color.value = type.fill_color;
            this.colorForm.stroke_color.value = type.stroke_color;
        }
    }
}
