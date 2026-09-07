import { CATEGORY_COLORS } from './tiles.js';
import { canPlace, snapToGrid } from './collision.js';

const SVG_NS = 'http://www.w3.org/2000/svg';

function el(name, attrs = {}) {
    const node = document.createElementNS(SVG_NS, name);
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, String(value)));
    return node;
}

export class BuildingLayer {
    constructor({ tileSize, layoutMode, onSelect, onMove, onMoveStart, onMoveRejected, onContextMenu }) {
        this.tileSize = tileSize;
        this.layoutMode = layoutMode;
        this.silhouetteMode = false;
        this.onSelect = onSelect;
        this.onMove = onMove;
        this.onMoveStart = onMoveStart;
        this.onMoveRejected = onMoveRejected;
        this.onContextMenu = onContextMenu;
        this.buildings = [];
        this.mapWidth = 0;
        this.mapHeight = 0;
        this.terrainData = null;
        this.root = el('svg', { class: 'vs-building-layer' });
        this.dragState = null;
    }

    setSilhouetteMode(enabled) {
        this.silhouetteMode = enabled;
        this.root.classList.toggle('vs-building-layer--silhouette', enabled);
        this.render();
    }

    setMapSize(width, height) {
        this.mapWidth = width;
        this.mapHeight = height;
        this.root.setAttribute('viewBox', `0 0 ${width * this.tileSize} ${height * this.tileSize}`);
    }

    setTerrainData(data, mapWidth) {
        this.terrainData = data;
        this.mapWidth = mapWidth;
    }

    setBuildings(buildings) {
        this.buildings = buildings;
        this.render();
    }

    renderBuildingVisual(group, type, w, h) {
        if (this.silhouetteMode) {
            group.appendChild(el('rect', {
                x: 0.5,
                y: 0.5,
                width: w - 1,
                height: h - 1,
                rx: 3,
                fill: 'rgba(0, 0, 0, 0.45)',
                stroke: 'rgba(255, 255, 255, 0.35)',
                'stroke-width': 1.5,
                'stroke-dasharray': '4 3',
                class: 'vs-building-silhouette',
            }));

            if (type.sprite_url) {
                group.appendChild(el('image', {
                    href: type.sprite_url,
                    x: 0,
                    y: 0,
                    width: w,
                    height: h,
                    preserveAspectRatio: 'xMidYMid meet',
                    class: 'vs-building-sprite vs-building-sprite--silhouette',
                }));
            }
            return;
        }

        if (type.sprite_url) {
            group.appendChild(el('image', {
                href: type.sprite_url,
                x: 0,
                y: 0,
                width: w,
                height: h,
                preserveAspectRatio: 'xMidYMid meet',
                class: 'vs-building-sprite',
            }));
            group.appendChild(el('rect', {
                x: 0,
                y: 0,
                width: w,
                height: h,
                fill: 'transparent',
                stroke: 'transparent',
                class: 'vs-building-hitbox',
            }));
            return;
        }

        const fill = CATEGORY_COLORS[type.category] ?? '#8b7355';
        group.appendChild(el('rect', {
            x: 1,
            y: 1,
            width: w - 2,
            height: h - 2,
            rx: 6,
            fill,
            class: 'vs-building-body',
        }));

        group.appendChild(el('text', {
            x: w / 2,
            y: h / 2 + 6,
            'text-anchor': 'middle',
            class: 'vs-building-icon',
        })).textContent = type.icon ?? '🏠';
    }

    render() {
        this.root.replaceChildren();

        this.buildings.forEach((building) => {
            const { type } = building;
            const w = type.width_tiles * this.tileSize;
            const h = type.height_tiles * this.tileSize;
            const x = building.grid_x * this.tileSize;
            const y = building.grid_y * this.tileSize;

            const group = el('g', {
                class: `vs-building${this.layoutMode && type.is_draggable && !this.silhouetteMode ? ' vs-building--draggable' : ''}${this.silhouetteMode ? ' vs-building--silhouette' : ''}`,
                'data-id': building.id,
                transform: `translate(${x}, ${y})`,
                tabindex: this.layoutMode && !this.silhouetteMode ? '-1' : '0',
                role: 'button',
            });

            this.renderBuildingVisual(group, type, w, h);

            if (!this.silhouetteMode) {
                const label = building.label ?? type.name;
                group.appendChild(el('text', {
                    x: w / 2,
                    y: -6,
                    'text-anchor': 'middle',
                    class: 'vs-building-label',
                })).textContent = label;
            }

            group.appendChild(el('title')).textContent = `${building.label ?? type.name} (${type.width_tiles}×${type.height_tiles})`;

            if (this.layoutMode && type.is_draggable && !this.silhouetteMode) {
                this.bindDrag(group, building);
            } else if (!this.layoutMode && !this.silhouetteMode) {
                group.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.onSelect?.(building);
                });
            }

            if (this.layoutMode && !this.silhouetteMode) {
                group.addEventListener('contextmenu', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.onContextMenu?.(building, event.clientX, event.clientY);
                });
            }

            this.root.appendChild(group);
        });
    }

    bindDrag(group, building) {
        group.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }
            event.stopPropagation();
            this.onMoveStart?.();
            this.dragState = {
                building,
                group,
                startX: event.clientX,
                startY: event.clientY,
                originX: building.grid_x,
                originY: building.grid_y,
            };
            group.setPointerCapture(event.pointerId);
            group.classList.add('vs-building--active');
        });

        group.addEventListener('pointermove', (event) => {
            if (!this.dragState || this.dragState.building.id !== building.id) {
                return;
            }
            event.stopPropagation();

            const scale = this.getScale();
            const dx = (event.clientX - this.dragState.startX) / scale;
            const dy = (event.clientY - this.dragState.startY) / scale;
            const px = (this.dragState.originX * this.tileSize) + dx;
            const py = (this.dragState.originY * this.tileSize) + dy;
            const snapped = snapToGrid(px, py, this.tileSize);

            building.grid_x = snapped.grid_x;
            building.grid_y = snapped.grid_y;

            const valid = canPlace(
                this.buildings,
                this.mapWidth,
                this.mapHeight,
                building.grid_x,
                building.grid_y,
                building.type.width_tiles,
                building.type.height_tiles,
                building.id,
                this.terrainData,
            );

            group.classList.toggle('vs-building--invalid', !valid);
            group.setAttribute('transform', `translate(${building.grid_x * this.tileSize}, ${building.grid_y * this.tileSize})`);
        });

        group.addEventListener('pointerup', async (event) => {
            if (!this.dragState || this.dragState.building.id !== building.id) {
                return;
            }

            group.releasePointerCapture(event.pointerId);
            group.classList.remove('vs-building--active');

            const valid = canPlace(
                this.buildings,
                this.mapWidth,
                this.mapHeight,
                building.grid_x,
                building.grid_y,
                building.type.width_tiles,
                building.type.height_tiles,
                building.id,
                this.terrainData,
            );

            if (!valid) {
                building.grid_x = this.dragState.originX;
                building.grid_y = this.dragState.originY;
                group.classList.remove('vs-building--invalid');
                this.render();
                this.onMoveRejected?.(building);
            } else if (building.grid_x !== this.dragState.originX || building.grid_y !== this.dragState.originY) {
                await this.onMove?.(building);
            }

            group.classList.remove('vs-building--invalid');
            this.dragState = null;
        });
    }

    getScale() {
        const world = this.root.closest('.vs-world');
        if (!world) {
            return 1;
        }
        const matrix = window.getComputedStyle(world).transform;
        if (!matrix || matrix === 'none') {
            return 1;
        }
        const values = matrix.match(/matrix\(([^)]+)\)/);
        return values ? parseFloat(values[1].split(',')[0]) || 1 : 1;
    }

    mount(container) {
        container.appendChild(this.root);
    }
}
