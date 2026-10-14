function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function parseError(response) {
    const payload = await response.json().catch(() => ({}));
    throw new Error(payload.message ?? 'İstek başarısız.');
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers,
        },
        ...options,
    });

    if (!response.ok) {
        await parseError(response);
    }

    return response.json();
}

export async function fetchMap(slug) {
    return request(`/api/maps/${slug}`);
}

export async function saveMap(slug, payload) {
    return request(`/api/maps/${slug}/save`, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function applyEditorState(slug, state) {
    return request(`/api/maps/${slug}/editor-state`, {
        method: 'POST',
        body: JSON.stringify(state),
    });
}

export async function updateBuildingPosition(id, gridX, gridY) {
    return request(`/api/map-buildings/${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ grid_x: gridX, grid_y: gridY }),
    });
}

export async function createBuilding(slug, payload) {
    return request(`/api/maps/${slug}/buildings`, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function deleteBuilding(id) {
    return request(`/api/map-buildings/${id}`, { method: 'DELETE' });
}

export async function paintTerrainTiles(slug, tiles) {
    return request(`/api/maps/${slug}/terrain`, {
        method: 'PATCH',
        body: JSON.stringify({ tiles }),
    });
}

export async function resizeMap(slug, widthTiles, heightTiles) {
    return request(`/api/maps/${slug}/dimensions`, {
        method: 'PATCH',
        body: JSON.stringify({ width_tiles: widthTiles, height_tiles: heightTiles }),
    });
}

export async function createTileType(slug, payload) {
    return request(`/api/maps/${slug}/tile-types`, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function updateTileType(id, payload) {
    return request(`/api/map-tile-types/${id}`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
    });
}

export async function createBuildingType(payload) {
    return request('/api/building-types', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function clearBuildingSprite(typeId) {
    return request(`/api/building-types/${typeId}/sprite`, { method: 'DELETE' });
}

export async function uploadBuildingSprite(typeId, file) {
    const form = new FormData();
    form.append('sprite', file);

    const response = await fetch(`/api/building-types/${typeId}/sprite`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: form,
    });

    if (!response.ok) {
        await parseError(response);
    }

    return response.json();
}
