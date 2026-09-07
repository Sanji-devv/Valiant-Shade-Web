import { MapEngine } from './engine/MapEngine.js';
import { EditorPanel } from './engine/EditorPanel.js';
import { BuildingTypeModal } from './engine/BuildingTypeModal.js';

const viewport = document.getElementById('valiant-shade-viewport');
const world = document.getElementById('valiant-shade-world');
const canEdit = viewport?.dataset.canEdit === '1';
const mapSlug = viewport?.dataset.mapSlug ?? 'valiant-shade';

const panel = document.getElementById('location-panel');
const panelIcon = document.getElementById('panel-icon');
const panelTitle = document.getElementById('panel-title');
const panelDescription = document.getElementById('panel-description');
const panelMeta = document.getElementById('panel-meta');
const panelClose = document.getElementById('panel-close');
const statusBar = document.getElementById('map-status');
const editorRoot = document.getElementById('map-editor');

function openPanel(building) {
    if (!panel || canEdit) {
        return;
    }

    const { type } = building;
    if (type.sprite_url) {
        panelIcon.innerHTML = `<img src="${type.sprite_url}" alt="" class="vs-panel-sprite">`;
    } else {
        panelIcon.textContent = type.icon ?? '🏠';
    }
    panelTitle.textContent = building.label ?? type.name;
    panelDescription.textContent = type.description ?? '';
    panelMeta.textContent = `${type.width_tiles}×${type.height_tiles} karo · (${building.grid_x}, ${building.grid_y})`;
    panel.hidden = false;
}

function closePanel() {
    panel.hidden = true;
}

panelClose?.addEventListener('click', closePanel);

if (!viewport || !world) {
    throw new Error('Harita konteyneri bulunamadı.');
}

let editorPanel = null;
let buildingModal = null;

const engine = new MapEngine({
    viewport,
    world,
    mapSlug,
    canEdit,
    onSelect: openPanel,
    onStatus: (message) => {
        if (statusBar) {
            statusBar.textContent = message;
            statusBar.classList.remove('vs-status--error');
        }
    },
    onHistoryChange: (flags) => {
        editorPanel?.setHistoryState(flags);
    },
    onDirtyChange: (isDirty) => {
        document.body.dataset.mapDirty = isDirty ? '1' : '0';
    },
    onReady: (state) => {
        if (editorPanel) {
            editorPanel.setTileTypes(state.tile_types, state.tile_types[0]?.id ?? null);
            editorPanel.setBuildingTypes(state.building_types);
            editorPanel.setBuildings(state.buildings);
            editorPanel.setAvailableMaps(state.available_maps ?? []);
            editorPanel.setPortals(state.portals ?? []);
        }
    },
    onBuildingsChange: (buildings) => {
        editorPanel?.setBuildingTypes(engine.state.building_types);
        editorPanel?.setBuildings(buildings);
    },
    onPortalsChange: (portals) => {
        editorPanel?.setAvailableMaps(engine.state.available_maps ?? []);
        editorPanel?.setPortals(portals);
    },
});

engine.onBuildingSelected = () => {
    editorPanel?.renderBuildingList();
};

engine.onTerrainToolChange = (tool, tileId) => {
    editorPanel?.setTerrainTool(tool, tileId);
};

engine.onPortalSelected = (portal) => {
    editorPanel?.selectPortal(portal);
};

if (canEdit && editorRoot) {
    buildingModal = new BuildingTypeModal({
        onSubmit: async (payload) => {
            const type = await engine.createBuildingType(payload);
            editorPanel?.setBuildingTypes(engine.state.building_types);
            editorPanel?.onPlaceBuilding?.(type.id);
            return type;
        },
    });

    editorPanel = new EditorPanel(editorRoot, {
        onModeChange: (mode) => engine.setEditMode(mode),
        onTerrainPanSelect: () => engine.selectTerrainPan(),
        onTileSelect: (id) => engine.selectTileType(id),
        onTileColorsSave: async (id, fill, stroke) => {
            await engine.changeTileTypeColors(id, fill, stroke);
            editorPanel.setTileTypes(engine.state.tile_types, id);
        },
        onTileTypeCreate: async (payload) => {
            const type = await engine.addTileType(payload);
            editorPanel.setTileTypes(engine.state.tile_types, type.id);
        },
        onExpand: (w, h) => engine.expandMap(w, h),
        onUndo: () => engine.undo(),
        onRedo: () => engine.redo(),
        onPlaceBuilding: (typeId) => engine.startPlacement(typeId),
        onRemoveBuildingType: (typeId) => engine.removeBuildingByTypeId(typeId),
        onCreateBuildingType: (payload) => engine.createBuildingType(payload),
        onUploadSprite: (typeId, file) => engine.uploadSpriteForBuildingType(typeId, file),
        onDeleteBuilding: () => engine.removeSelectedBuilding(),
        onOpenBuildingModal: () => buildingModal.open(),
        onCreatePortal: (payload) => engine.startPortalPlacement(payload),
        onUpdatePortal: (id, payload) => {
            const portal = engine.updatePortal(id, payload);
            if (portal) {
                editorPanel.selectPortal(portal);
            }
        },
        onDeletePortal: (id) => {
            engine.removePortal(id);
            editorPanel.clearPortalSelection();
        },
        onPortalSelect: (portal) => {
            engine.selectedPortalId = portal.id;
            engine.syncPortals();
            editorPanel.selectPortal(portal);
            engine.setEditMode('portal');
        },
    });
    editorPanel.onBuildingSelect = (building) => {
        engine.selectedBuildingId = building.id;
    };
}

engine.init().catch((error) => {
    if (statusBar) {
        statusBar.textContent = error.message;
        statusBar.classList.add('vs-status--error');
    }
    console.error(error);
});

window.addEventListener('vs-tile-map-show', () => {
    requestAnimationFrame(() => {
        engine.resetView();
    });
});

document.getElementById('zoom-in')?.addEventListener('click', () => engine.zoomIn());
document.getElementById('zoom-out')?.addEventListener('click', () => engine.zoomOut());
document.getElementById('zoom-fit')?.addEventListener('click', () => engine.resetView());

document.querySelector('[data-undo]')?.addEventListener('click', () => engine.undo());
document.querySelector('[data-redo]')?.addEventListener('click', () => engine.redo());

document.querySelector('[data-save]')?.addEventListener('click', async () => {
    const saveBtn = document.querySelector('[data-save]');
    if (saveBtn) {
        saveBtn.disabled = true;
    }
    try {
        await engine.save();
    } catch (error) {
        statusBar.textContent = error.message;
        statusBar.classList.add('vs-status--error');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = !engine.isDirty();
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closePanel();
    }
    if (canEdit && (event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault();
        document.querySelector('[data-save]')?.click();
    }
    if (canEdit && (event.ctrlKey || event.metaKey) && event.key === 'z' && !event.shiftKey) {
        event.preventDefault();
        engine.undo();
    }
    if (canEdit && (event.ctrlKey || event.metaKey) && (event.key === 'y' || (event.key === 'z' && event.shiftKey))) {
        event.preventDefault();
        engine.redo();
    }
});

if (canEdit) {
    window.addEventListener('beforeunload', (event) => {
        if (engine.isDirty()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    document.querySelectorAll('a.vs-link[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!engine.isDirty()) {
                return;
            }
            const leave = window.confirm('Kaydedilmemiş değişiklikler kaybolacak. Çıkmak istiyor musunuz?');
            if (!leave) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form.vs-inline-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!engine.isDirty()) {
                return;
            }
            const leave = window.confirm('Kaydedilmemiş değişiklikler kaybolacak. Çıkmak istiyor musunuz?');
            if (!leave) {
                event.preventDefault();
            }
        });
    });
}

if (!canEdit) {
    viewport.addEventListener('click', (event) => {
        if (!event.target.closest('.vs-building')) {
            closePanel();
        }
    });
}
