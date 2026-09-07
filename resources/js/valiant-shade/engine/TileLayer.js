const SVG_NS = 'http://www.w3.org/2000/svg';

function el(name, attrs = {}) {
    const node = document.createElementNS(SVG_NS, name);
    Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, String(value)));
    return node;
}

export class TileLayer {
    constructor({ width, height, tileSize }) {
        this.width = width;
        this.height = height;
        this.tileSize = tileSize;
        this.data = [];
        this.tileDefs = {};
        this.paintMode = false;
        this.selectedTileTypeId = null;
        this.onPaint = null;
        this.isPainting = false;
        this.onCellClick = null;
        this.cellClickEnabled = false;
        this.root = el('svg', {
            class: 'vs-tile-layer',
            viewBox: `0 0 ${width * tileSize} ${height * tileSize}`,
            preserveAspectRatio: 'xMidYMid meet',
        });
        this.interactionLayer = el('rect', {
            class: 'vs-tile-interaction',
            x: 0,
            y: 0,
            width: width * tileSize,
            height: height * tileSize,
            fill: 'transparent',
        });
    }

    setTileTypes(tileTypes) {
        this.tileDefs = {};
        tileTypes.forEach((type) => {
            this.tileDefs[type.id] = {
                name: type.slug,
                fill: type.fill_color,
                stroke: type.stroke_color,
                label: type.name,
            };
        });
    }

    getTileDef(tileId) {
        return this.tileDefs[tileId] ?? this.tileDefs[Number(tileId)] ?? {
            fill: '#5a9448',
            stroke: '#4a7a3a',
            name: 'grass',
            label: 'Çim',
        };
    }

    setData(data) {
        this.data = data;
        this.render();
    }

    resize(width, height) {
        this.width = width;
        this.height = height;
        this.root.setAttribute('viewBox', `0 0 ${width * this.tileSize} ${height * this.tileSize}`);
        this.interactionLayer.setAttribute('width', width * this.tileSize);
        this.interactionLayer.setAttribute('height', height * this.tileSize);
    }

    enableCellClick() {
        this.cellClickEnabled = true;
        this.root.style.pointerEvents = 'auto';
        this.root.classList.add('vs-tile-layer--click');
        this.bindInteractionHandlers();
    }

    disableCellClick() {
        this.cellClickEnabled = false;
        if (!this.paintMode) {
            this.root.style.pointerEvents = 'none';
        }
        this.root.classList.remove('vs-tile-layer--click');
    }

    bindInteractionHandlers() {
        this.interactionLayer.onpointerdown = (event) => {
            if (this.cellClickEnabled && !this.paintMode) {
                event.stopPropagation();
                const cell = this.handleCellEvent(event);
                if (cell) {
                    this.onCellClick?.(cell);
                }
                return;
            }
            if (!this.paintMode || !this.selectedTileTypeId) {
                return;
            }
            event.stopPropagation();
            this.isPainting = true;
            this.paintAtEvent(event);
        };

        this.interactionLayer.onpointermove = (event) => {
            if (!this.isPainting) {
                return;
            }
            this.paintAtEvent(event);
        };

        this.interactionLayer.onpointerup = () => {
            this.isPainting = false;
        };

        this.interactionLayer.onpointerleave = () => {
            this.isPainting = false;
        };
    }

    handleCellEvent(event) {
        const rect = this.root.getBoundingClientRect();
        const scaleX = (this.width * this.tileSize) / rect.width;
        const scaleY = (this.height * this.tileSize) / rect.height;
        const px = (event.clientX - rect.left) * scaleX;
        const py = (event.clientY - rect.top) * scaleY;
        const x = Math.floor(px / this.tileSize);
        const y = Math.floor(py / this.tileSize);
        if (x < 0 || y < 0 || x >= this.width || y >= this.height) {
            return null;
        }
        return { x, y };
    }

    enablePaintMode({ selectedTileTypeId, onPaint }) {
        this.paintMode = true;
        this.selectedTileTypeId = selectedTileTypeId;
        this.onPaint = onPaint;
        this.root.style.pointerEvents = 'auto';
        this.root.classList.add('vs-tile-layer--paint');
        this.bindInteractionHandlers();
    }

    disablePaintMode() {
        this.paintMode = false;
        this.isPainting = false;
        if (!this.cellClickEnabled) {
            this.root.style.pointerEvents = 'none';
        }
        this.root.classList.remove('vs-tile-layer--paint');
    }

    setSelectedTileType(id) {
        this.selectedTileTypeId = id;
    }

    paintAtEvent(event) {
        const rect = this.root.getBoundingClientRect();
        const scaleX = (this.width * this.tileSize) / rect.width;
        const scaleY = (this.height * this.tileSize) / rect.height;
        const px = (event.clientX - rect.left) * scaleX;
        const py = (event.clientY - rect.top) * scaleY;
        const x = Math.floor(px / this.tileSize);
        const y = Math.floor(py / this.tileSize);

        if (x < 0 || y < 0 || x >= this.width || y >= this.height) {
            return;
        }

        const index = (y * this.width) + x;
        if (this.data[index] === this.selectedTileTypeId) {
            return;
        }

        this.data[index] = this.selectedTileTypeId;
        this.render();
        this.onPaint?.({ x, y, tile_type_id: this.selectedTileTypeId });
    }

    render() {
        this.root.replaceChildren();

        for (let y = 0; y < this.height; y += 1) {
            for (let x = 0; x < this.width; x += 1) {
                const tileId = this.data[(y * this.width) + x];
                const def = this.getTileDef(tileId);
                this.root.appendChild(el('rect', {
                    x: x * this.tileSize,
                    y: y * this.tileSize,
                    width: this.tileSize,
                    height: this.tileSize,
                    fill: def.fill,
                    stroke: def.stroke,
                    'stroke-width': 0.5,
                    class: `vs-tile vs-tile-${def.name}`,
                    'data-x': x,
                    'data-y': y,
                }));
            }
        }

        this.renderGridOverlay();
        this.root.appendChild(this.interactionLayer);
    }

    renderGridOverlay() {
        const grid = el('g', { class: 'vs-grid-overlay' });
        for (let x = 0; x <= this.width; x += 1) {
            grid.appendChild(el('line', {
                x1: x * this.tileSize,
                y1: 0,
                x2: x * this.tileSize,
                y2: this.height * this.tileSize,
                class: 'vs-grid-line',
            }));
        }
        for (let y = 0; y <= this.height; y += 1) {
            grid.appendChild(el('line', {
                x1: 0,
                y1: y * this.tileSize,
                x2: this.width * this.tileSize,
                y2: y * this.tileSize,
                class: 'vs-grid-line',
            }));
        }
        this.root.appendChild(grid);
    }

    mount(container) {
        container.appendChild(this.root);
    }
}
