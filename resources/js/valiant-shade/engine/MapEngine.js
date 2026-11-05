import { PortalLayer } from './PortalLayer.js';
import Panzoom from '@panzoom/panzoom';
import { TileLayer } from './TileLayer.js';
import { BuildingLayer } from './BuildingLayer.js';
import { BuildingContextMenu } from './BuildingContextMenu.js';
import { HistoryManager } from './HistoryManager.js';
import { TERRAIN_PAN_TOOL, VOID_TILE_ID } from './tiles.js';
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
        this.terrainTool = TERRAIN_PAN_TOOL;
        this.selectedTileTypeId = null;
        this.placementTypeId = null;
        this.selectedBuildingId = null;
        this.selectedPortalId = null;
        this.pendingPortalDraft = null;
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
        this.state.portals = this.state.portals ?? [];
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
        this.buildingLayer.setTerrainData(terrain_data, map.width_tiles);
        this.buildingLayer.setBuildings(buildings);
        this.buildingLayer.mount(this.world);

        this.portalLayer = new PortalLayer({
            tileSize: map.tile_size,
            editable: this.canEdit,
            onSelect: (portal) => {
                if (this.canEdit) {
                    this.selectedPortalId = portal.id;
                    this.onPortalSelected?.(portal);
                }
            },
            onMoveStart: () => this.recordHistory(),
            onMove: (portal) => {
                this.onPortalsChange?.(this.state.portals);
                this.onStatus?.(`"${portal.name}" taşındı (${portal.grid_x}, ${portal.grid_y})`);
            },
            onMoveRejected: (portal) => {
                this.onStatus?.(`"${portal.name}" bu konuma yerleştirilemez`);
            },
            onTravel: (portal) => this.travelToMap(portal),
        });
        this.portalLayer.resize(map.width_tiles, map.height_tiles);
        this.portalLayer.setPortals(this.state.portals ?? []);
        this.portalLayer.mount(this.world);
    }

    travelToMap(portal) {
        const slug = portal.target_map_slug;
        if (!slug) {
            return;
        }

        const urls = {
            'valiant-shade': '/world/valiant-shade',
            'isonburg': '/isonburg/arazi',
            'isonburg-sehir': '/isonburg/sehir',
        };

        const target = urls[slug] ?? `/${slug}`;
        window.location.href = target;
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
            if (this.terrainTool === TERRAIN_PAN_TOOL) {
                return true;
            }
            return !event.target.closest('.vs-tile-interaction');
        }
        if (this.editMode === 'place-building') {
            return !event.target.closest('.vs-tile-interaction');
        }
        if (this.editMode === 'portal') {
            return !event.target.closest('.vs-portal-hit');
        }
        return !event.target.closest('.vs-building--draggable');
    }

    setEditMode(mode) {
        this.editMode = mode;
        if (mode === 'terrain') {
            this.terrainTool = TERRAIN_PAN_TOOL;
        }
        this.placementTypeId = mode === 'place-building' ? this.placementTypeId : null;
        this.applyEditMode();
        const labels = {
            terrain: this.terrainTool === TERRAIN_PAN_TOOL
                ? 'Haritayı kaydırmak için sürükleyin — zemin boyamak için paletten seçin'
                : 'Zemin boyama — yapılar silüet olarak görünür',
            building: 'Bina taşıma — sağ tık menüsü · sürükleyerek taşıyın',
            portal: 'Geçit yerleştirme — haritaya tıklayın veya sürükleyerek taşıyın',
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

        this.viewport.classList.remove('vs-viewport--paint', 'vs-viewport--pan', 'vs-viewport--buildings', 'vs-viewport--place', 'vs-viewport--portals');

        if (this.editMode === 'terrain') {
            this.buildingLayer.layoutMode = false;
            this.buildingLayer.setSilhouetteMode(true);
            this.buildingLayer.root.style.pointerEvents = 'none';

            if (this.terrainTool === TERRAIN_PAN_TOOL) {
                this.tileLayer.disablePaintMode();
                this.viewport.classList.add('vs-viewport--pan');
            } else {
                this.tileLayer.enablePaintMode({
                    selectedTileTypeId: this.selectedTileTypeId,
                    onPaint: (tile) => this.queuePaint(tile),
                });
                this.viewport.classList.add('vs-viewport--paint');
            }

            this.tileLayer.disableCellClick();
            this.portalLayer.editable = false;
        } else if (this.editMode === 'place-building') {
            this.buildingLayer.setSilhouetteMode(false);
            this.tileLayer.disablePaintMode();
            this.buildingLayer.layoutMode = false;
            this.buildingLayer.root.style.pointerEvents = 'none';
            this.buildingLayer.render();
            this.tileLayer.enableCellClick();
            this.portalLayer.editable = false;
            this.viewport.classList.add('vs-viewport--place');
        } else if (this.editMode === 'portal') {
            this.buildingLayer.setSilhouetteMode(false);
            this.tileLayer.disablePaintMode();
            this.buildingLayer.layoutMode = false;
            this.buildingLayer.root.style.pointerEvents = 'none';
            this.buildingLayer.render();
            this.tileLayer.enableCellClick();
            this.portalLayer.editable = true;
            this.portalLayer.setSelectedPortalId(this.selectedPortalId);
            this.portalLayer.render();
            this.viewport.classList.add('vs-viewport--portals');
        } else {
            this.portalLayer.editable = false;
            this.portalLayer.setSelectedPortalId(null);
            this.buildingLayer.setSilhouetteMode(false);
            this.tileLayer.disablePaintMode();
            this.tileLayer.disableCellClick();
            this.buildingLayer.layoutMode = true;
            this.buildingLayer.root.style.pointerEvents = 'auto';
            this.buildingLayer.render();
            this.viewport.classList.add('vs-viewport--buildings');
        }

        if (this.portalLayer) {
            this.portalLayer.setPortals(this.state.portals ?? []);
        }

        this.panzoom?.setOptions({ handleStartEvent: (event) => this.shouldPan(event) });
    }

    handleCellClick({ x, y }) {
        if (!this.canEdit) {
            return;
        }

        if (this.editMode === 'portal' && this.pendingPortalDraft) {
            this.placePendingPortal(x, y);
            return;
        }

        if (this.editMode !== 'place-building' || !this.placementTypeId) {
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

    placePendingPortal(grid_x, grid_y) {
        const draft = this.pendingPortalDraft;
        if (!draft) {
            return;
        }

        const portal = {
            id: this.tempIdCounter--,
            name: draft.name,
            target_map_slug: draft.target_map_slug,
            grid_x,
            grid_y,
            width_tiles: draft.width_tiles,
            height_tiles: draft.height_tiles,
            description: draft.description ?? null,
        };

        if (!this.canPlacePortal(portal)) {
            this.onStatus?.('Geçit bu konuma yerleştirilemez.');
            return;
        }

        this.recordHistory();
        this.state.portals.push(portal);
        this.pendingPortalDraft = null;
        this.selectedPortalId = portal.id;
        this.syncPortals();
        this.onPortalsChange?.(this.state.portals);
        this.onPortalSelected?.(portal);
        this.onStatus?.(`"${portal.name}" eklendi (${grid_x}, ${grid_y}) — kaydetmeyi unutmayın`);
    }

    canPlacePortal(portal) {
        const { map } = this.state;
        if (portal.grid_x < 0 || portal.grid_y < 0
            || portal.grid_x + portal.width_tiles > map.width_tiles
            || portal.grid_y + portal.height_tiles > map.height_tiles) {
            return false;
        }

        return !this.state.portals.some((other) => {
            if (other.id === portal.id) {
                return false;
            }
            return portal.grid_x < other.grid_x + other.width_tiles
                && portal.grid_x + portal.width_tiles > other.grid_x
                && portal.grid_y < other.grid_y + other.height_tiles
                && portal.grid_y + portal.height_tiles > other.grid_y;
        });
    }

    startPortalPlacement(draft) {
        this.pendingPortalDraft = draft;
        this.setEditMode('portal');
        this.onStatus?.('Haritada geçit konumuna tıklayın');
    }

    updatePortal(portalId, fields) {
        const portal = this.state.portals.find((item) => item.id === portalId);
        if (!portal) {
            return null;
        }

        this.recordHistory();
        Object.assign(portal, fields);

        if (!this.canPlacePortal(portal)) {
            this.onStatus?.('Geçit bu boyut/konuma sığmıyor.');
        }

        this.syncPortals();
        this.onPortalsChange?.(this.state.portals);
        return portal;
    }

    removePortal(portalId) {
        const portal = this.state.portals.find((item) => item.id === portalId);
        if (!portal) {
            return;
        }

        this.recordHistory();
        this.state.portals = this.state.portals.filter((item) => item.id !== portalId);
        if (this.selectedPortalId === portalId) {
            this.selectedPortalId = null;
        }
        this.syncPortals();
        this.onPortalsChange?.(this.state.portals);
        this.onStatus?.(`"${portal.name}" kaldırıldı`);
    }

    syncPortals() {
        this.portalLayer?.setPortals(this.state.portals ?? []);
        this.portalLayer?.setSelectedPortalId(this.selectedPortalId);
        this.applyEditMode();
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

        const { terrain_data } = this.state;
        for (let dy = 0; dy < height; dy += 1) {
            for (let dx = 0; dx < width; dx += 1) {
                const index = ((grid_y + dy) * map.width_tiles) + (grid_x + dx);
                if (terrain_data[index] === VOID_TILE_ID) {
                    return false;
                }
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
        this.state.portals = (snapshot.portals ?? []).map((item) => ({ ...item }));

        if (snapshot.type_sprites) {
            this.applyTypeSprites(snapshot.type_sprites);
        }

        this.world.style.width = `${snapshot.map.width_tiles * this.state.map.tile_size}px`;
        this.world.style.height = `${snapshot.map.height_tiles * this.state.map.tile_size}px`;
        this.tileLayer.resize(snapshot.map.width_tiles, snapshot.map.height_tiles);
        this.tileLayer.setData(this.state.terrain_data);
        this.portalLayer?.resize(snapshot.map.width_tiles, snapshot.map.height_tiles);
        this.buildingLayer.setMapSize(snapshot.map.width_tiles, snapshot.map.height_tiles);
        this.buildingLayer.setTerrainData(this.state.terrain_data, snapshot.map.width_tiles);
        this.buildingLayer.setBuildings(this.state.buildings);
        this.syncPortals();
        this.applyEditMode();
        this.onBuildingsChange?.(this.state.buildings);
        this.onPortalsChange?.(this.state.portals);
    }

    syncLayers() {
        this.buildingLayer.setBuildings(this.state.buildings);
        this.buildingLayer.setTerrainData(this.state.terrain_data, this.state.map.width_tiles);
        this.tileLayer.setData(this.state.terrain_data);
        this.syncPortals();
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
            portals: (this.state.portals ?? []).map((portal) => ({
                id: portal.id > 0 ? portal.id : null,
                name: portal.name,
                target_map_slug: portal.target_map_slug,
                grid_x: portal.grid_x,
                grid_y: portal.grid_y,
                width_tiles: portal.width_tiles,
                height_tiles: portal.height_tiles,
                description: portal.description ?? null,
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
        this.state.portals = result.portals ?? [];

        this.world.style.width = `${result.map.width_tiles * this.state.map.tile_size}px`;
        this.world.style.height = `${result.map.height_tiles * this.state.map.tile_size}px`;
        this.tileLayer.resize(result.map.width_tiles, result.map.height_tiles);
        this.tileLayer.setData(result.terrain_data);
        this.portalLayer?.resize(result.map.width_tiles, result.map.height_tiles);
        this.buildingLayer.setMapSize(result.map.width_tiles, result.map.height_tiles);
        this.buildingLayer.setTerrainData(result.terrain_data, result.map.width_tiles);
        this.buildingLayer.setBuildings(result.buildings);
        this.syncPortals();

        this.initTypeSprites();
        this.history?.clear();
        this.onBuildingsChange?.(result.buildings);
        this.onPortalsChange?.(this.state.portals);
        this.onStatus?.('Harita kaydedildi');
    }

    selectTerrainPan() {
        this.terrainTool = TERRAIN_PAN_TOOL;
        if (this.editMode === 'terrain') {
            this.applyEditMode();
            this.onStatus?.('Haritayı kaydırmak için sürükleyin — zemin boyamak için paletten seçin');
        }
        this.onTerrainToolChange?.(TERRAIN_PAN_TOOL);
    }

    selectTileType(id) {
        this.terrainTool = 'paint';
        this.selectedTileTypeId = id;
        this.tileLayer?.setSelectedTileType(id);
        if (this.editMode === 'terrain') {
            this.applyEditMode();
            this.onStatus?.('Zemin boyama — yapılar silüet olarak görünür');
        }
        this.onTerrainToolChange?.('paint', id);
    }

    queuePaint(tile) {
        if (!this.paintSnapshotTaken) {
            this.recordHistory();
            this.paintSnapshotTaken = true;
        }

        const index = (tile.y * this.state.map.width_tiles) + tile.x;
        this.state.terrain_data[index] = tile.tile_type_id;
        this.buildingLayer.setTerrainData(this.state.terrain_data, this.state.map.width_tiles);

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
        this.buildingLayer.setTerrainData(newData, newW);
        this.buildingLayer.setBuildings(this.state.buildings);
        this.portalLayer?.resize(newW, newH);
        this.syncPortals();
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
