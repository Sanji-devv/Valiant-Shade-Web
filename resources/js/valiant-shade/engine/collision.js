export function rectsIntersect(a, b) {
    return a.x < b.x + b.w
        && a.x + a.w > b.x
        && a.y < b.y + b.h
        && a.y + a.h > b.y;
}

export function canPlace(buildings, mapWidth, mapHeight, gridX, gridY, width, height, ignoreId = null) {
    if (gridX < 0 || gridY < 0 || gridX + width > mapWidth || gridY + height > mapHeight) {
        return false;
    }

    const candidate = { x: gridX, y: gridY, w: width, h: height };

    return !buildings.some((building) => {
        if (ignoreId !== null && building.id === ignoreId) {
            return false;
        }

        const other = {
            x: building.grid_x,
            y: building.grid_y,
            w: building.type.width_tiles,
            h: building.type.height_tiles,
        };

        return rectsIntersect(candidate, other);
    });
}

export function snapToGrid(pixelX, pixelY, tileSize) {
    return {
        grid_x: Math.floor(pixelX / tileSize),
        grid_y: Math.floor(pixelY / tileSize),
    };
}
