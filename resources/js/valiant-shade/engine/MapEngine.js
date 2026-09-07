import Panzoom from '@panzoom/panzoom';
import { TileLayer } from './TileLayer.js';
import { BuildingLayer } from './BuildingLayer.js';
import { BuildingContextMenu } from './BuildingContextMenu.js';
import { HistoryManager } from './HistoryManager.js';
import {
    fetchMap,
    saveMap,
    createTileType,
    updateTileType,
    uploadBuildingSprite,
    clearBuildingSprite,
    createBuildingType as apiCreateBuildingType,
} from '../api.js';

export class MapEngine {
    constructor(options) {
        this.viewport = options.viewport;
        this.world = options.world;
        this.mapSlug = options.mapSlug;
        this.canEdit = options.canEdit;
        this.onSelect = options.onSelect;
        this.onStatus = options.onStatus;
        this.onReady = options.onReady;
        this.onHistoryChange = options.onHistoryChange;
        this.onDirtyChange = options.onDirtyChange;
        this.state = null;
        this.panzoom = null;
        this.editMode = 'building';
        this.selectedTileTypeId = null;
        this.placementTypeId = null;
        this.selectedBuildingId = null;
        this.paintSnapshotTaken = false;
        this.paintResetTimer = null;
        this.tempIdCounter = -1;
        this.typeSprites = {};
        this.blobFileMap = new Map();
        this.contextMenu = this.canEdit ? new BuildingContextMenu() : null;
        this.history = this.canEdit
            ? new HistoryManager(
                (snapshot) => this.applyLocalSnapshot(snapshot),
                () => this.captureTypeSprites(),
            )
            : null;
        this.history?.setOnChange((flags) => {
            this.onHistoryChange?.(flags);
            this.onDirtyChange?.(flags.isDirty);
        });
    }

    async init() {
        this.state = await fetchMap(this.mapSlug);
        this.initTypeSprites();
        this.setupLayers();
        this.setupPanzoom();
        this.setupContextMenu();

        if (this.canEdit) {
            this.setEditMode('building');
        }

        this.scheduleResetView();
        window.addEventListener('resize', () => this.scheduleResetView());
        this.onReady?.(this.state);
    }

    setupContextMenu() {
        if (!this.contextMenu) {
            return;
        }

        this.contextMenu.setActions({
            onRename: (building, label) => this.renameBuilding(building, label),
            onUploadSprite: (typeId, file) => this.uploadSpriteForBuildingType(typeId, file),
            onRemoveSprite: (typeId) => this.removeSpriteForBuildingType(typeId),
            onRemove: (building) => this.removeBuilding(building),
        });
    }

    initTypeSprites() {
        this.typeSprites = {};
        this.blobFileMap = new Map();
        this.state.building_types.forEach((type) => {
            this.typeSprites[type.id] = {
                persistedUrl: type.sprite_url ?? null,
                previewUrl: type.sprite_url ?? null,
                pendingFile: null,
                clearOnSave: false,
            };
        });
    }

    captureTypeSprites() {
        const out = {};
        Object.entries(this.typeSprites).forEach(([id, sprite]) => {
            out[id] = {
                persistedUrl: sprite.persistedUrl,
                previewUrl: sprite.previewUrl,
                clearOnSave: sprite.clearOnSave ?? false,
            };
        });
        return out;
    }

    revokeBlobUrl(url) {
        if (url?.startsWith('blob:')) {
            URL.revokeObjectURL(url);
            this.blobFileMap.delete(url);
        }
    }

    applyTypeSprites(snapshotSprites) {
        this.state.building_types.forEach((type) => {
            const id = type.id;
            const next = snapshotSprites[id] ?? {
                persistedUrl: null,
                previewUrl: null,
                clearOnSave: false,
            };

            const pendingFile = next.previewUrl?.startsWith('blob:')
                ? this.blobFileMap.get(next.previewUrl) ?? null
                : null;

            this.typeSprites[id] = {
                persistedUrl: next.persistedUrl ?? null,
                previewUrl: next.previewUrl ?? null,
                pendingFile,
                clearOnSave: next.clearOnSave ?? false,
            };
        });
        this.syncBuildingTypeSprites();
    }

    syncBuildingTypeSprites() {
        this.state.building_types.forEach((type) => {
            const sprite = this.typeSprites[type.id];
            type.sprite_url = sprite?.previewUrl ?? null;
        });
        this.state.buildings.forEach((building) => {
            const type = this.state.building_types.find((entry) => entry.id === building.type.id);
            if (type) {
                building.type = type;
            }
        });
    }

    scheduleResetView() {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => this.resetView());
        });
    }

    setupLayers() {
        const { map, buildings, tile_types, terrain_data } = this.state;

        this.world.style.width = `${map.width_tiles * map.tile_size}px`;
        this.world.style.height = `${map.height_tiles * map.tile_size}px`;
        this.selectedTileTypeId = tile_types[0]?.id ?? null;

        this.tileLayer = new TileLayer({
            width: map.width_tiles,
            height: map.height_tiles,
            tileSize: map.tile_size,
        });
        this.tileLayer.setTileTypes(tile_types);
        this.tileLayer.setData(terrain_data);
        this.tileLayer.onCellClick = (cell) => this.handleCellClick(cell);
        this.tileLayer.mount(this.world);

        this.buildingLayer = new BuildingLayer({
            tileSize: map.tile_size,
            layoutMode: false,
            onSelect: (building) => {
                if (this.canEdit) {
                    this.selectedBuildingId = building.id;
                    this.onBuildingSelected?.(building);
                } else {
                    this.onSelect?.(building);
                }
            },
            onMove: async (building) => {
                this.onStatus?.(`${building.label ?? building.type.name} taşındı (${building.grid_x}, ${building.grid_y})`);
                this.onBuildingsChange?.(this.state.buildings);
            },
            onMoveStart: () => this.recordHistory(),
            onMoveRejected: (building) => {
                this.onStatus?.(`${building.label ?? building.type.name} bu konuma yerleştirilemez`);
            },
            onContextMenu: (building, x, y) => {
                this.selectedBuildingId = building.id;
                this.contextMenu?.show(building, x, y);
            },
        });

        this.buildingLayer.setMapSize(map.width_tiles, map.height_tiles);
        this.buildingLayer.setBuildings(buildings);
        this.buildingLayer.mount(this.world);
    }

    setupPanzoom() {
        this.panzoom = Panzoom(this.world, {
            maxScale: this.canEdit ? 4 : 2.5,
            minScale: 0.06,
            contain: false,
            cursor: 'grab',
            handleStartEvent: (event) => this.shouldPan(event),
        });

        this.viewport.addEventListener('wheel', this.panzoom.zoomWithWheel, { passive: false });
    }

    shouldPan(event) {
        if (!this.canEdit) {
            return true;
        }
        if (this.editMode === 'terrain') {
            return !event.target.closest('.vs-tile-interaction');
        }
        if (this.editMode === 'place-building') {
            return !event.target.closest('.vs-tile-interaction');
        }
        return !event.target.closest('.vs-building--draggable');
    }

    setEditMode(mode) {
        this.editMode = mode;
        this.placementTypeId = mode === 'place-building' ? this.placementTypeId : null;
        this.applyEditMode();
        const labels = {
            terrain: 'Zemin boyama — yapılar silüet olarak görünür',
            building: 'Bina taşıma — sağ tık menüsü · sürükleyerek taşıyın',
            'place-building': 'Haritaya tıklayarak bina yerleştirin',
        };
        this.onStatus?.(labels[mode] ?? '');
    }

    startPlacement(buildingTypeId) {
        this.placementTypeId = buildingTypeId;
        this.setEditMode('place-building');
    }

    applyEditMode() {
        if (!this.tileLayer || !this.buildingLayer) {
            return;
        }

        if (!this.canEdit) {
            this.buildingLayer.layoutMode = false;
            this.buildingLayer.setSilhouetteMode(false);
            this.tileLayer.disablePaintMode();
            this.tileLayer.disableCellClick();
            this.buildingLayer.render();
            return;
        }

        this.viewport.classList.remove('vs-viewport--paint', 'vs-viewport--buildings', 'vs-viewport--place');

        if (this.editMode === 'terrain') {
            this.buildingLayer.layoutMode = false;
            this.buildingLayer.setSilhouetteMode(true);
            this.buildingLayer.root.style.pointerEvents = 'none';
            this.tileLayer.enablePaintMode({
                selectedTileTypeId: this.selectedTileTypeId,
                onPaint: (tile) => this.queuePaint(tile),
            });
            this.tileLayer.disableCellClick();
            this.viewport.classList.add('vs-viewport--paint');
        } else if (this.editMode === 'place-building') {
            this.buildingLayer.setSilhouetteMode(false);
            this.tileLayer.disablePaintMode();
            this.buildingLayer.layoutMode = false;
            this.buildingLayer.root.style.pointerEvents = 'none';
            this.buildingLayer.render();
            this.tileLayer.enableCellClick();
            this.viewport.classList.add('vs-viewport--place');
        } else {
            this.buildingLayer.setSilhouetteMode(false);
            this.tileLayer.disablePaintMode();
            this.tileLayer.disableCellClick();
            this.buildingLayer.layoutMode = true;
            this.buildingLayer.root.style.pointerEvents = 'auto';
            this.buildingLayer.render();
            this.viewport.classList.add('vs-viewport--buildings');
        }

        this.panzoom?.setOptions({ handleStartEvent: (event) => this.shouldPan(event) });
    }

    handleCellClick({ x, y }) {
        if (!this.canEdit || this.editMode !== 'place-building' || !this.placementTypeId) {
            return;
        }

        const type = this.state.building_types.find((item) => item.id === this.placementTypeId);
        if (!type) {
            return;
        }

        if (type.is_unique && this.findBuildingByTypeId(type.id)) {
            this.onStatus?.('Bu yapı türünden yalnızca bir tane olabilir.');
            return;
        }

        this.recordHistory();

        const building = this.createLocalBuilding(type, x, y);
        if (!this.canPlaceBuilding(building)) {
            this.onStatus?.('Bu konuma yerleştirilemez.');
            return;
        }

        this.state.buildings.push(building);
        this.syncLayers();
        this.onBuildingsChange?.(this.state.buildings);
        this.onStatus?.(`${type.name} eklendi (${x}, ${y}) — kaydetmeyi unutmayın`);
        this.setEditMode('building');
    }

    createLocalBuilding(type, grid_x, grid_y) {
        return {
            id: this.tempIdCounter--,
            grid_x,
            grid_y,
            label: null,
            type,
        };
    }

    findBuildingByTypeId(typeId) {
        return this.state.buildings.find((building) => building.type.id === typeId);
    }

    canPlaceBuilding(building) {
        const { type } = building;
        return this.checkPlacement(
            building.grid_x,
            building.grid_y,
            type.width_tiles,
            type.height_tiles,
            building.id,
        );
    }

    checkPlacement(grid_x, grid_y, width, height, excludeId = null) {
        const { map, buildings } = this.state;
        if (grid_x < 0 || grid_y < 0 || grid_x + width > map.width_tiles || grid_y + height > map.height_tiles) {
            return false;
        }

        for (const other of buildings) {
            if (other.id === excludeId) {
                continue;
            }
            const ox2 = other.grid_x + other.type.width_tiles;
            const oy2 = other.grid_y + other.type.height_tiles;
            const nx2 = grid_x + width;
            const ny2 = grid_y + height;
            const overlap = grid_x < ox2 && nx2 > other.grid_x && grid_y < oy2 && ny2 > other.grid_y;
            if (overlap) {
                return false;
            }
        }
        return true;
    }

    renameBuilding(building, label) {
        this.recordHistory();
        building.label = label;
        this.syncLayers();
        this.onBuildingsChange?.(this.state.buildings);
        this.onStatus?.(`İsim güncellendi: ${label}`);
    }

    removeBuilding(building) {
        this.recordHistory();
        this.state.buildings = this.state.buildings.filter((item) => item.id !== building.id);
        if (this.selectedBuildingId === building.id) {
            this.selectedBuildingId = null;
        }
        this.syncLayers();
        this.onBuildingsChange?.(this.state.buildings);
        this.onStatus?.(`${building.label ?? building.type.name} kaldırıldı`);
    }

    removeBuildingByTypeId(typeId) {
        const building = this.findBuildingByTypeId(typeId);
        if (building) {
            this.removeBuilding(building);
        }
    }

    recordHistory() {
        if (this.history && this.state) {
            this.history.push(this.state);
        }
    }

    async undo() {
        if (!this.history) {
            return;
        }
        await this.history.undo(this.state);
        this.onStatus?.('Geri alındı');
    }

    async redo() {
        if (!this.history) {
            return;
        }
        await this.history.redo(this.state);
        this.onStatus?.('Yinelendi');
    }

    rebuildBuilding(item) {
        const type = this.state.building_types.find((entry) => entry.id === item.building_type_id);
        if (!type) {
            return null;
        }
        return {
            id: item.id,
            grid_x: item.grid_x,
            grid_y: item.grid_y,
            label: item.label,
            type,
        };
    }

    applyLocalSnapshot(snapshot) {
        this.state.map.width_tiles = snapshot.map.width_tiles;
        this.state.map.height_tiles = snapshot.map.height_tiles;
        this.state.terrain_data = [...snapshot.terrain_data];
        this.state.buildings = snapshot.buildings
            .map((item) => this.rebuildBuilding(item))
            .filter(Boolean);

        if (snapshot.type_sprites) {
            this.applyTypeSprites(snapshot.type_sprites);
        }

        this.world.style.width = `${snapshot.map.width_tiles * this.state.map.tile_size}px`;
        this.world.style.height = `${snapshot.map.height_tiles * this.state.map.tile_size}px`;
        this.tileLayer.resize(snapshot.map.width_tiles, snapshot.map.height_tiles);
        this.tileLayer.setData(this.state.terrain_data);
        this.buildingLayer.setMapSize(snapshot.map.width_tiles, snapshot.map.height_tiles);
        this.buildingLayer.setBuildings(this.state.buildings);
        this.applyEditMode();
        this.onBuildingsChange?.(this.state.buildings);
    }

    syncLayers() {
        this.buildingLayer.setBuildings(this.state.buildings);
        this.tileLayer.setData(this.state.terrain_data);
        this.applyEditMode();
    }

    buildSavePayload() {
        return {
            terrain_data: this.state.terrain_data,
            width_tiles: this.state.map.width_tiles,
            height_tiles: this.state.map.height_tiles,
            buildings: this.state.buildings.map((building) => ({
                id: building.id > 0 ? building.id : null,
                building_type_id: building.type.id,
                grid_x: building.grid_x,
                grid_y: building.grid_y,
                label: building.label,
            })),
        };
    }

    isDirty() {
        return this.history?.isDirty() ?? false;
    }

    async save() {
        if (!this.canEdit) {
            return;
        }

        this.onStatus?.('Kaydediliyor…');

        for (const [typeId, sprite] of Object.entries(this.typeSprites)) {
            const id = Number(typeId);
            if (sprite.clearOnSave && !sprite.pendingFile) {
                const result = await clearBuildingSprite(id);
                sprite.persistedUrl = null;
                sprite.previewUrl = null;
                sprite.clearOnSave = false;
                const index = this.state.building_types.findIndex((type) => type.id === id);
                if (index >= 0) {
                    this.state.building_types[index] = result.building_type;
                }
            } else if (sprite.pendingFile) {
                const result = await uploadBuildingSprite(id, sprite.pendingFile);
                this.revokeBlobUrl(sprite.previewUrl);
                sprite.persistedUrl = result.building_type.sprite_url;
                sprite.previewUrl = result.building_type.sprite_url;
                sprite.pendingFile = null;
                sprite.clearOnSave = false;
                const index = this.state.building_types.findIndex((type) => type.id === id);
                if (index >= 0) {
                    this.state.building_types[index] = result.building_type;
                }
            }
        }

        this.syncBuildingTypeSprites();

        const result = await saveMap(this.mapSlug, this.buildSavePayload());

        this.state.map.width_tiles = result.map.width_tiles;
        this.state.map.height_tiles = result.map.height_tiles;
        this.state.terrain_data = result.terrain_data;
        this.state.buildings = result.buildings;

        this.world.style.width = `${result.map.width_tiles * this.state.map.tile_size}px`;
        this.world.style.height = `${result.map.height_tiles * this.state.map.tile_size}px`;
        this.tileLayer.resize(result.map.width_tiles, result.map.height_tiles);
        this.tileLayer.setData(result.terrain_data);
        this.buildingLayer.setMapSize(result.map.width_tiles, result.map.height_tiles);
        this.buildingLayer.setBuildings(result.buildings);
        this.applyEditMode();

        this.initTypeSprites();
        this.history?.clear();
        this.onBuildingsChange?.(result.buildings);
        this.onStatus?.('Harita kaydedildi');
    }

    selectTileType(id) {
        this.selectedTileTypeId = id;
        this.tileLayer?.setSelectedTileType(id);
    }

    queuePaint(tile) {
        if (!this.paintSnapshotTaken) {
            this.recordHistory();
            this.paintSnapshotTaken = true;
        }

        const index = (tile.y * this.state.map.width_tiles) + tile.x;
        this.state.terrain_data[index] = tile.tile_type_id;

        clearTimeout(this.paintResetTimer);
        this.paintResetTimer = setTimeout(() => {
            this.paintSnapshotTaken = false;
        }, 250);
    }

    expandMap(deltaWidth = 5, deltaHeight = 5) {
        this.recordHistory();
        const { map, terrain_data, tile_types } = this.state;
        const newW = map.width_tiles + deltaWidth;
        const newH = map.height_tiles + deltaHeight;
        const defaultTile = tile_types[0]?.id ?? 1;
        const newData = [];

        for (let y = 0; y < newH; y += 1) {
            for (let x = 0; x < newW; x += 1) {
                if (x < map.width_tiles && y < map.height_tiles) {
                    newData.push(terrain_data[(y * map.width_tiles) + x]);
                } else {
                    newData.push(defaultTile);
                }
            }
        }

        map.width_tiles = newW;
        map.height_tiles = newH;
        this.state.terrain_data = newData;

        this.world.style.width = `${newW * map.tile_size}px`;
        this.world.style.height = `${newH * map.tile_size}px`;
        this.tileLayer.resize(newW, newH);
        this.tileLayer.setData(newData);
        this.buildingLayer.setMapSize(newW, newH);
        this.applyEditMode();
        this.scheduleResetView();
        this.onStatus?.(`Harita ${newW}×${newH} karoya genişletildi`);
    }

    async addTileType(payload) {
        this.recordHistory();
        const result = await createTileType(this.mapSlug, payload);
        this.state.tile_types.push(result.tile_type);
        this.tileLayer.setTileTypes(this.state.tile_types);
        this.tileLayer.render();
        this.selectTileType(result.tile_type.id);
        this.onStatus?.(`"${result.tile_type.name}" zemin tipi eklendi`);
        return result.tile_type;
    }

    async changeTileTypeColors(id, fill_color, stroke_color) {
        this.recordHistory();
        const result = await updateTileType(id, { fill_color, stroke_color });
        const index = this.state.tile_types.findIndex((type) => type.id === id);
        if (index >= 0) {
            this.state.tile_types[index] = result.tile_type;
        }
        this.tileLayer.setTileTypes(this.state.tile_types);
        this.tileLayer.render();
        this.onStatus?.(`"${result.tile_type.name}" renkleri güncellendi`);
        return result.tile_type;
    }

    uploadSpriteForBuildingType(typeId, file) {
        this.recordHistory();
        const entry = this.typeSprites[typeId] ?? {
            persistedUrl: null,
            previewUrl: null,
            pendingFile: null,
            clearOnSave: false,
        };

        if (entry.previewUrl?.startsWith('blob:')) {
            this.revokeBlobUrl(entry.previewUrl);
        }

        const previewUrl = URL.createObjectURL(file);
        this.blobFileMap.set(previewUrl, file);

        this.typeSprites[typeId] = {
            persistedUrl: entry.persistedUrl,
            previewUrl,
            pendingFile: file,
            clearOnSave: false,
        };

        this.syncBuildingTypeSprites();
        this.syncLayers();
        this.onBuildingsChange?.(this.state.buildings);
        this.onStatus?.('Görsel eklendi — Ctrl+Z ile geri al · Kaydet ile kalıcı olur');
        return this.state.building_types.find((type) => type.id === typeId);
    }

    removeSpriteForBuildingType(typeId) {
        this.recordHistory();
        const entry = this.typeSprites[typeId];
        if (!entry?.previewUrl && !entry?.persistedUrl) {
            return;
        }

        this.typeSprites[typeId] = {
            persistedUrl: entry.persistedUrl,
            previewUrl: null,
            pendingFile: null,
            clearOnSave: Boolean(entry.persistedUrl && !entry.previewUrl?.startsWith('blob:')),
        };

        this.syncBuildingTypeSprites();
        this.syncLayers();
        this.onBuildingsChange?.(this.state.buildings);
        this.onStatus?.('Görsel kaldırıldı — Kaydet ile kalıcı olur');
    }

    async createBuildingType(payload) {
        this.recordHistory();
        const result = await apiCreateBuildingType(payload);
        this.state.building_types.push(result.building_type);
        this.typeSprites[result.building_type.id] = {
            persistedUrl: null,
            previewUrl: null,
            pendingFile: null,
            clearOnSave: false,
        };
        this.onBuildingsChange?.(this.state.buildings);
        this.onStatus?.(`"${result.building_type.name}" bina tipi oluşturuldu`);
        return result.building_type;
    }

    async removeSelectedBuilding() {
        if (!this.selectedBuildingId) {
            return;
        }
        const building = this.state.buildings.find((item) => item.id === this.selectedBuildingId);
        if (building) {
            this.removeBuilding(building);
        }
    }

    zoomIn() {
        this.panzoom?.zoomIn();
    }

    zoomOut() {
        this.panzoom?.zoomOut();
    }

    resetView() {
        if (!this.state || !this.panzoom) {
            return;
        }

        const { map } = this.state;
        const worldWidth = map.width_tiles * map.tile_size;
        const worldHeight = map.height_tiles * map.tile_size;
        const padding = this.canEdit ? 48 : 24;
        const scaleX = (this.viewport.clientWidth - padding) / worldWidth;
        const scaleY = (this.viewport.clientHeight - padding) / worldHeight;

        if (this.viewport.clientWidth < 1 || this.viewport.clientHeight < 1) {
            return;
        }

        const fitScale = Math.min(scaleX, scaleY) * (this.canEdit ? 0.92 : 0.98);
        const scale = Math.max(0.06, fitScale);

        this.panzoom.zoom(scale, { animate: false });
        this.panzoom.pan(
            (this.viewport.clientWidth - worldWidth * scale) / 2,
            (this.viewport.clientHeight - worldHeight * scale) / 2,
            { animate: false },
        );
    }
}
