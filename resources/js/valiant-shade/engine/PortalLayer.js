const SVG_NS = 'http://www.w3.org/2000/svg';

function el(name, attrs = {}) {
    const node = document.createElementNS(SVG_NS, name);
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, String(value)));
    return node;
}

export class PortalLayer {
    constructor({
        tileSize,
        editable = false,
        onSelect,
        onMove,
        onMoveStart,
        onMoveRejected,
        onTravel,
    }) {
        this.tileSize = tileSize;
        this.editable = editable;
        this.onSelect = onSelect;
        this.onMove = onMove;
        this.onMoveStart = onMoveStart;
        this.onMoveRejected = onMoveRejected;
        this.onTravel = onTravel;
        this.portals = [];
        this.selectedPortalId = null;
        this.mapWidth = 0;
        this.mapHeight = 0;
        this.dragState = null;
        this.root = el('svg', { class: 'vs-portal-layer' });
    }

    setPortals(portals) {
        this.portals = portals ?? [];
        this.render();
    }

    setSelectedPortalId(id) {
        this.selectedPortalId = id;
        this.render();
    }

    resize(width, height) {
        this.mapWidth = width;
        this.mapHeight = height;
        this.root.setAttribute('viewBox', `0 0 ${width * this.tileSize} ${height * this.tileSize}`);
    }

    getScale() {
        const rect = this.root.getBoundingClientRect();
        if (rect.width < 1) {
            return 1;
        }
        return (this.mapWidth * this.tileSize) / rect.width;
    }

    render() {
        this.root.replaceChildren();

        this.portals.forEach((portal) => {
            const group = el('g', {
                class: `vs-portal${portal.id === this.selectedPortalId ? ' vs-portal--active' : ''}`,
                'data-portal-id': portal.id,
            });

            const x = portal.grid_x * this.tileSize;
            const y = portal.grid_y * this.tileSize;
            const w = portal.width_tiles * this.tileSize;
            const h = portal.height_tiles * this.tileSize;

            const hit = el('rect', {
                x,
                y,
                width: w,
                height: h,
                class: 'vs-portal-hit',
                rx: 4,
            });

            if (this.editable) {
                hit.classList.add('vs-portal-hit--editable');
                this.bindDrag(group, hit, portal);
                hit.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.onSelect?.(portal);
                });
            } else {
                hit.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.onTravel?.(portal);
                });
            }

            group.appendChild(hit);

            const label = el('text', {
                x: x + (w / 2),
                y: y - 6,
                class: 'vs-portal-label',
                'text-anchor': 'middle',
            });
            label.textContent = portal.name;
            group.appendChild(label);

            if (portal.description && !this.editable) {
                const hint = el('text', {
                    x: x + (w / 2),
                    y: y + h + 14,
                    class: 'vs-portal-hint',
                    'text-anchor': 'middle',
                });
                hint.textContent = portal.description;
                group.appendChild(hint);
            }

            this.root.appendChild(group);
        });
    }

    bindDrag(group, hit, portal) {
        hit.addEventListener('pointerdown', (event) => {
            event.stopPropagation();
            this.onSelect?.(portal);
            this.onMoveStart?.();
            this.dragState = {
                portal,
                group,
                startX: event.clientX,
                startY: event.clientY,
                originX: portal.grid_x,
                originY: portal.grid_y,
            };
            hit.setPointerCapture(event.pointerId);
            group.classList.add('vs-portal--dragging');
        });

        hit.addEventListener('pointermove', (event) => {
            if (!this.dragState || this.dragState.portal.id !== portal.id) {
                return;
            }
            event.stopPropagation();

            const scale = this.getScale();
            const dx = (event.clientX - this.dragState.startX) / scale;
            const dy = (event.clientY - this.dragState.startY) / scale;

            group.setAttribute('transform', `translate(${dx}, ${dy})`);
            group.classList.toggle(
                'vs-portal--invalid',
                !this.canPlaceAt(
                    this.dragState.originX + Math.floor(dx / this.tileSize),
                    this.dragState.originY + Math.floor(dy / this.tileSize),
                    portal,
                ),
            );
        });

        hit.addEventListener('pointerup', (event) => {
            if (!this.dragState || this.dragState.portal.id !== portal.id) {
                return;
            }

            hit.releasePointerCapture(event.pointerId);
            group.classList.remove('vs-portal--dragging', 'vs-portal--invalid');
            group.removeAttribute('transform');

            const scale = this.getScale();
            const dx = (event.clientX - this.dragState.startX) / scale;
            const dy = (event.clientY - this.dragState.startY) / scale;
            const nextX = Math.max(0, Math.min(
                this.mapWidth - portal.width_tiles,
                this.dragState.originX + Math.floor(dx / this.tileSize),
            ));
            const nextY = Math.max(0, Math.min(
                this.mapHeight - portal.height_tiles,
                this.dragState.originY + Math.floor(dy / this.tileSize),
            ));

            if (!this.canPlaceAt(nextX, nextY, portal)) {
                this.onMoveRejected?.(portal);
            } else {
                portal.grid_x = nextX;
                portal.grid_y = nextY;
                this.onMove?.(portal);
            }

            this.dragState = null;
            this.render();
        });
    }

    canPlaceAt(gridX, gridY, portal) {
        if (gridX < 0 || gridY < 0
            || gridX + portal.width_tiles > this.mapWidth
            || gridY + portal.height_tiles > this.mapHeight) {
            return false;
        }

        return !this.portals.some((other) => {
            if (other.id === portal.id) {
                return false;
            }
            return gridX < other.grid_x + other.width_tiles
                && gridX + portal.width_tiles > other.grid_x
                && gridY < other.grid_y + other.height_tiles
                && gridY + portal.height_tiles > other.grid_y;
        });
    }

    isWithinBounds(portal) {
        return this.canPlaceAt(portal.grid_x, portal.grid_y, portal);
    }

    mount(container) {
        container.appendChild(this.root);
    }
}
