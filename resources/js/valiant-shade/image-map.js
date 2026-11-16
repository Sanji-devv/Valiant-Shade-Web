const MIN_SCALE = 1;
const DEFAULT_MAX_SCALE = 3;
const ZOOM_STEP = 0.2;
const HD_ZOOM_THRESHOLD = 1.15;
const POI_FOCUS_SCALE = 2.15;
const MOBILE_QUERY = window.matchMedia('(max-width: 768px)');
const MAP_IMAGE_WARM_KEY = 'vs-map-image-warm';

function rememberWarmImageUrl(url) {
    if (!url) {
        return;
    }

    try {
        const existing = JSON.parse(sessionStorage.getItem(MAP_IMAGE_WARM_KEY) || '[]');
        if (!existing.includes(url)) {
            existing.push(url);
            if (existing.length > 24) {
                existing.splice(0, existing.length - 24);
            }
            sessionStorage.setItem(MAP_IMAGE_WARM_KEY, JSON.stringify(existing));
        }
    } catch {
        /* storage unavailable */
    }
}

function isWarmImageUrl(url) {
    if (!url) {
        return false;
    }

    try {
        return JSON.parse(sessionStorage.getItem(MAP_IMAGE_WARM_KEY) || '[]').includes(url);
    } catch {
        return false;
    }
}

function prefersLightMapAssets() {
    const connection = navigator.connection;
    if (connection?.saveData) {
        return true;
    }

    const type = connection?.effectiveType;
    return Boolean(type && (type === 'slow-2g' || type === '2g'));
}

function getMainMapFrame() {
    return document.querySelector('[data-image-map] [data-image-map-zoom]');
}

function focusPoiFromButton(button) {
    if (!button) {
        return;
    }

    const mapUrl = button.dataset.poiMapUrl;
    if (mapUrl) {
        window.location.assign(mapUrl);
        return;
    }

    const frame = getMainMapFrame();
    if (!frame?.vsFocusHotspot) {
        return;
    }

    const x = Number.parseFloat(button.dataset.poiX ?? '');
    const y = Number.parseFloat(button.dataset.poiY ?? '');
    if (!Number.isFinite(x) || !Number.isFinite(y)) {
        return;
    }

    frame.vsFocusHotspot({
        id: button.dataset.poiId || null,
        x,
        y,
        w: Number.parseFloat(button.dataset.poiW ?? '5'),
        h: Number.parseFloat(button.dataset.poiH ?? '5'),
        icon: button.dataset.poiIcon,
        label: button.dataset.poiName,
    });
}

function markActivePoiButton(button) {
    document.querySelectorAll('[data-poi-link].vs-poi-link--active').forEach((element) => {
        element.classList.remove('vs-poi-link--active');
    });
    button?.classList.add('vs-poi-link--active');
}

document.querySelectorAll('[data-image-map-zoom]').forEach((frame) => {
    initImageMapZoom(frame);
});

initImageMapDrawer();
initExclusiveKingdomAccordions();
initBuildingInfoPanel();
initPoiInfoPanel();
initMapLightbox();
initLocationSheet();
initMobileMapSwipe();
initPoiDeepLink();

function initExclusiveKingdomAccordions() {
    document.querySelectorAll('[data-exclusive-accordions]').forEach((root) => {
        const items = [...root.querySelectorAll('[data-kingdom-accordion]')];

        items.forEach((details) => {
            details.addEventListener('toggle', () => {
                if (!details.open) {
                    return;
                }

                items.forEach((other) => {
                    if (other !== details) {
                        other.open = false;
                    }
                });
            });
        });

        root.querySelectorAll('[data-kingdom-route-link]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });
    });
}

function initPoiInfoPanel() {
    const panel = document.querySelector('[data-building-info]');
    if (!panel) {
        return;
    }

    const iconEl = panel.querySelector('[data-building-info-icon]');
    const titleEl = panel.querySelector('[data-building-info-title]');
    const descEl = panel.querySelector('[data-building-info-desc]');
    const closeBtn = panel.querySelector('[data-building-info-close]');

    const closePanel = () => {
        panel.hidden = true;
    };

    const openPanel = (button) => {
        if (!button) {
            return;
        }

        if (button.dataset.poiMapUrl) {
            window.location.assign(button.dataset.poiMapUrl);
            return;
        }

        iconEl.textContent = button.dataset.poiIcon ?? '📍';
        titleEl.textContent = button.dataset.poiName ?? '';
        descEl.textContent = button.dataset.poiDesc ?? '';
        panel.hidden = false;

        focusPoiFromButton(button);
        markActivePoiButton(button);

        const locationSheet = document.querySelector('[data-location-sheet]');
        locationSheet?.classList.remove('vs-location-sheet--open');
        if (locationSheet && !locationSheet.classList.contains('vs-location-sheet--open')) {
            window.setTimeout(() => {
                if (!locationSheet.classList.contains('vs-location-sheet--open')) {
                    locationSheet.hidden = true;
                }
            }, 280);
        }

        const drawer = document.querySelector('[data-drawer]');
        drawer?.classList.remove('vs-drawer--open');
        window.setTimeout(() => {
            if (drawer && !drawer.classList.contains('vs-drawer--open')) {
                drawer.hidden = true;
            }
        }, 260);
    };

    document.querySelectorAll('[data-poi-link]').forEach((button) => {
        button.addEventListener('click', () => openPanel(button));
    });

    closeBtn?.addEventListener('click', closePanel);
}

function initBuildingInfoPanel() {
    const panel = document.querySelector('[data-building-info]');
    if (!panel) {
        return;
    }

    const iconEl = panel.querySelector('[data-building-info-icon]');
    const titleEl = panel.querySelector('[data-building-info-title]');
    const descEl = panel.querySelector('[data-building-info-desc]');
    const closeBtn = panel.querySelector('[data-building-info-close]');

    const closePanel = () => {
        panel.hidden = true;
    };

    const openPanel = (button) => {
        if (!button) {
            return;
        }

        iconEl.textContent = button.dataset.buildingIcon ?? '🏠';
        titleEl.textContent = button.dataset.buildingName ?? '';
        descEl.textContent = button.dataset.buildingDesc ?? '';
        panel.hidden = false;

        const drawer = document.querySelector('[data-drawer]');
        drawer?.classList.remove('vs-drawer--open');
        window.setTimeout(() => {
            if (drawer && !drawer.classList.contains('vs-drawer--open')) {
                drawer.hidden = true;
            }
        }, 260);
    };

    document.querySelectorAll('[data-building-link]').forEach((button) => {
        button.addEventListener('click', () => openPanel(button));
    });

    closeBtn?.addEventListener('click', closePanel);
}

function isMobileLayout() {
    return MOBILE_QUERY.matches;
}

function initImageMapDrawer() {
    const drawer = document.querySelector('[data-drawer]');
    if (!drawer) {
        return;
    }

    const openDrawer = () => {
        drawer.hidden = false;
        requestAnimationFrame(() => drawer.classList.add('vs-drawer--open'));
    };

    const closeDrawer = () => {
        drawer.classList.remove('vs-drawer--open');
        window.setTimeout(() => {
            if (!drawer.classList.contains('vs-drawer--open')) {
                drawer.hidden = true;
            }
        }, 260);
    };

    document.querySelectorAll('[data-drawer-toggle]').forEach((button) => {
        button.addEventListener('click', openDrawer);
    });

    document.querySelectorAll('[data-drawer-close]').forEach((element) => {
        element.addEventListener('click', closeDrawer);
    });

    drawer.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!link.hasAttribute('data-location-link')) {
                closeDrawer();
            }
        });
    });
}

function frameMaxScale(frame) {
    const parsed = Number.parseFloat(frame.dataset.maxScale ?? '');

    return Number.isFinite(parsed) && parsed > 1 ? parsed : DEFAULT_MAX_SCALE;
}

function initImageMapZoom(frame) {
    const viewport = frame.querySelector('[data-image-map-viewport]');
    const canvas = frame.querySelector('[data-image-map-canvas]');
    const art = frame.querySelector('.vs-image-map-art');
    const label = frame.querySelector('[data-zoom-label]');
    const zoomInBtn = frame.querySelector('[data-zoom-in]');
    const zoomOutBtn = frame.querySelector('[data-zoom-out]');
    const resetBtn = frame.querySelector('[data-zoom-reset]');

    if (!viewport || !canvas || !art) {
        return;
    }

    const maxScale = frameMaxScale(frame);
    frame.vsMapPanState = { atMinZoom: true };

    const isMainMap = !frame.closest('[data-map-lightbox]');
    const fillLightbox = frame.dataset.imageMapFit === 'lightbox';
    const fillPanel =
        frame.dataset.imageMapFit === 'panel' && !isMobileLayout() && isMainMap;
    const useWidthZoom =
        frame.dataset.imageMapZoomMode === 'width' ||
        fillLightbox ||
        (frame.dataset.imageMapZoomMode !== 'transform' &&
            !frame.closest('.vs-dual-map-layer--art, .vs-city-art-layout'));

    let layoutViewport = () => {};

    const progressive = initProgressiveImage(art, {
        allowDesktopUpgrade: isMainMap && fillPanel,
        onSourceChange: () => {
            if (useWidthZoom && fillPanel && viewport.clientWidth > 0) {
                baseDisplayWidth = viewport.clientWidth;
                layoutViewport();
            }
        },
    });

    let scale = 1;
    let baseDisplayWidth = 0;
    let panX = 0;
    let panY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let panStartX = 0;
    let panStartY = 0;
    let didDrag = false;

    const updateLabel = () => {
        if (label) {
            label.textContent = `${Math.round(scale * 100)}%`;
        }
    };

    const clearArtInlineSize = () => {
        art.style.width = '';
        art.style.maxWidth = '';
        art.style.maxHeight = '';
        art.style.height = '';
    };

    const artHeightAtScale = (currentScale) => {
        if (!useWidthZoom) {
            return canvas.offsetHeight * currentScale;
        }

        if (!baseDisplayWidth || !art.naturalWidth) {
            return canvas.offsetHeight;
        }

        const width = baseDisplayWidth * currentScale;
        return (width / art.naturalWidth) * art.naturalHeight;
    };

    const contentWidthAtScale = (currentScale) => {
        if (!useWidthZoom) {
            return canvas.offsetWidth * currentScale;
        }

        return baseDisplayWidth ? baseDisplayWidth * currentScale : canvas.offsetWidth;
    };

    const captureBaseDisplayWidth = () => {
        clearArtInlineSize();
        baseDisplayWidth = art.offsetWidth;

        if (!baseDisplayWidth && art.naturalWidth && viewport.clientWidth) {
            baseDisplayWidth = Math.min(art.naturalWidth, viewport.clientWidth);
        }
    };

    /** Lightbox: viewport’a sığdır; kaynak çözünürlüğün üstüne CSS ile büyütme. */
    const captureLightboxBaseWidth = () => {
        clearArtInlineSize();

        const nw = art.naturalWidth;
        const nh = art.naturalHeight;
        const vpW = viewport.clientWidth;
        const vpH = viewport.clientHeight;

        if (!nw || !nh || !vpW || !vpH) {
            captureBaseDisplayWidth();
            return;
        }

        let fitW = Math.min(vpW, nw);
        let fitH = (fitW / nw) * nh;

        if (fitH > vpH) {
            fitH = vpH;
            fitW = (fitH / nh) * nw;
        }

        baseDisplayWidth = fitW;
    };

    const applyArtDimensions = () => {
        if (!useWidthZoom || !baseDisplayWidth) {
            return;
        }

        const width = baseDisplayWidth * scale;
        art.style.width = `${width}px`;
        art.style.maxWidth = 'none';
        art.style.maxHeight = 'none';
        art.style.height = 'auto';
    };

    const getMinScale = () => {
        const cw = useWidthZoom ? baseDisplayWidth || canvas.offsetWidth : canvas.offsetWidth;
        const ch = useWidthZoom ? artHeightAtScale(1) : canvas.offsetHeight;
        const vpW = viewport.clientWidth;
        const vpH = viewport.clientHeight;

        if (!cw || !ch || !vpW || !vpH) {
            return MIN_SCALE;
        }

        if (isMobileLayout()) {
            return Math.min(MIN_SCALE, vpW / cw, vpH / ch);
        }

        return MIN_SCALE;
    };

    const applyMobileCoverFit = () => {
        if (!isMobileLayout()) {
            return false;
        }

        const vpW = viewport.clientWidth;
        const vpH = viewport.clientHeight;
        const cw = useWidthZoom ? baseDisplayWidth || canvas.offsetWidth : canvas.offsetWidth;
        const ch = useWidthZoom ? artHeightAtScale(1) : canvas.offsetHeight;

        if (!vpW || !vpH || !cw || !ch) {
            return false;
        }

        scale = Math.max(vpW / cw, vpH / ch);
        panX = (vpW - cw * scale) / 2;
        panY = (vpH - ch * scale) / 2;

        return true;
    };

    const clampPan = () => {
        const vpW = viewport.clientWidth;
        const vpH = viewport.clientHeight;
        const contentW = contentWidthAtScale(scale);
        const contentH = artHeightAtScale(scale);

        if (contentW <= vpW) {
            panX = (vpW - contentW) / 2;
        } else {
            panX = Math.min(0, Math.max(vpW - contentW, panX));
        }

        if (contentH <= vpH) {
            panY = (vpH - contentH) / 2;
        } else {
            panY = Math.min(0, Math.max(vpH - contentH, panY));
        }
    };

    const applyCanvasTransform = () => {
        if (useWidthZoom) {
            applyArtDimensions();
            canvas.style.transform = `translate(${Math.round(panX)}px, ${Math.round(panY)}px)`;
            return;
        }

        canvas.style.transform = `translate(${Math.round(panX)}px, ${Math.round(panY)}px) scale(${scale})`;
    };

    const syncPanState = () => {
        const min = getMinScale();
        frame.vsMapPanState.atMinZoom = Math.abs(scale - min) < 0.025;
    };

    const applyTransform = () => {
        applyCanvasTransform();
        updateLabel();
        syncPanState();
        if (useWidthZoom) {
            progressive.updateResolution(scale, () => {
                clampPan();
                applyCanvasTransform();
                syncPanState();
            });
        } else {
            progressive.updateResolution(scale, layoutViewport);
        }
    };

    const setScale = (nextScale, originX, originY) => {
        const clamped = Math.min(maxScale, Math.max(getMinScale(), nextScale));

        if (clamped === scale) {
            return;
        }

        const ratio = clamped / scale;
        panX = originX - ratio * (originX - panX);
        panY = originY - ratio * (originY - panY);
        scale = clamped;
        clampPan();
        applyTransform();
    };

    const zoomBy = (delta, clientX, clientY) => {
        const rect = viewport.getBoundingClientRect();
        const originX = clientX - rect.left;
        const originY = clientY - rect.top;
        setScale(scale + delta, originX, originY);
    };

    const resetZoom = () => {
        if (isMobileLayout() && applyMobileCoverFit()) {
            clampPan();
            applyTransform();
            return;
        }

        scale = 1;
        panX = 0;
        panY = 0;
        clampPan();
        applyTransform();
    };

    layoutViewport = () => {
        if (useWidthZoom && (Math.abs(scale - 1) < 0.001 || scale <= getMinScale())) {
            if (fillLightbox) {
                captureLightboxBaseWidth();
            } else {
                captureBaseDisplayWidth();
            }
        }

        if (fillPanel) {
            viewport.style.height = '100%';
            if (useWidthZoom && viewport.clientWidth > 0) {
                baseDisplayWidth = viewport.clientWidth;
                applyArtDimensions();
            }
            clampPan();
            applyCanvasTransform();
            syncPanState();
            return;
        }

        if (fillLightbox && !isMobileLayout()) {
            viewport.style.height = '100%';
            if (Math.abs(scale - 1) < 0.001) {
                captureLightboxBaseWidth();
            }
            applyArtDimensions();
            clampPan();
            applyCanvasTransform();
            syncPanState();
            return;
        }

        if (isMobileLayout()) {
            viewport.style.height = '100%';
        } else if (useWidthZoom) {
            const artHeight = artHeightAtScale(scale);
            if (artHeight > 0) {
                viewport.style.height = `${Math.min(artHeight, window.innerHeight - 112)}px`;
            }
        } else {
            const artHeight = art.offsetHeight;
            if (artHeight > 0) {
                viewport.style.height = `${Math.min(artHeight, window.innerHeight - 112)}px`;
            }
        }

        clampPan();
        applyCanvasTransform();
    };

    const syncViewportHeight = () => {
        layoutViewport();
    };

    const initViewport = () => {
        if (useWidthZoom) {
            if (fillLightbox) {
                captureLightboxBaseWidth();
            } else {
                captureBaseDisplayWidth();
            }
        } else {
            clearArtInlineSize();
        }

        if (fillPanel) {
            viewport.style.height = '100%';
            if (useWidthZoom && viewport.clientWidth > 0) {
                baseDisplayWidth = viewport.clientWidth;
            }
            scale = 1;
            panX = 0;
            panY = 0;
            clampPan();
            applyTransform();
            return;
        }

        if (fillLightbox && !isMobileLayout()) {
            viewport.style.height = '100%';
            captureLightboxBaseWidth();
            scale = 1;
            panX = 0;
            panY = 0;
            clampPan();
            applyTransform();
            return;
        }

        if (isMobileLayout()) {
            viewport.style.height = '100%';
            applyMobileCoverFit();
        } else if (useWidthZoom) {
            const artHeight = artHeightAtScale(scale);
            if (artHeight > 0) {
                viewport.style.height = `${Math.min(artHeight, window.innerHeight - 112)}px`;
            }
        } else {
            const artHeight = art.offsetHeight;
            if (artHeight > 0) {
                viewport.style.height = `${Math.min(artHeight, window.innerHeight - 112)}px`;
            }
        }

        clampPan();
        applyTransform();
    };

    zoomInBtn?.addEventListener('click', () => {
        const rect = viewport.getBoundingClientRect();
        zoomBy(ZOOM_STEP, rect.left + rect.width / 2, rect.top + rect.height / 2);
    });

    zoomOutBtn?.addEventListener('click', () => {
        const rect = viewport.getBoundingClientRect();
        zoomBy(-ZOOM_STEP, rect.left + rect.width / 2, rect.top + rect.height / 2);
    });

    resetBtn?.addEventListener('click', resetZoom);

    const zoomFromWheel = (event) => {
        const modZoom = event.ctrlKey || event.metaKey;
        if (fillLightbox && !modZoom) {
            return;
        }

        event.preventDefault();

        const magnitude = Math.min(0.35, Math.max(0.04, Math.abs(event.deltaY) * 0.002));
        const delta = event.deltaY < 0 ? magnitude : -magnitude;
        zoomBy(delta, event.clientX, event.clientY);
    };

    viewport.addEventListener('wheel', zoomFromWheel, { passive: false });

    if (fillLightbox) {
        const lightboxRoot = frame.closest('[data-map-lightbox]');
        lightboxRoot?.addEventListener('wheel', zoomFromWheel, { passive: false, capture: true });
    }

    const canPan = () => {
        if (isMobileLayout()) {
            return true;
        }

        const contentW = contentWidthAtScale(scale);
        const contentH = artHeightAtScale(scale);

        return contentW > viewport.clientWidth + 1 || contentH > viewport.clientHeight + 1;
    };

    viewport.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || !canPan()) {
            return;
        }

        isDragging = true;
        didDrag = false;
        dragStartX = event.clientX;
        dragStartY = event.clientY;
        panStartX = panX;
        panStartY = panY;
        viewport.classList.add('is-dragging');
        viewport.setPointerCapture(event.pointerId);
    });

    viewport.addEventListener('pointermove', (event) => {
        if (!isDragging) {
            return;
        }

        const dx = event.clientX - dragStartX;
        const dy = event.clientY - dragStartY;

        if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
            didDrag = true;
        }

        panX = panStartX + dx;
        panY = panStartY + dy;
        clampPan();
        applyTransform();
    });

    const endDrag = (event) => {
        if (!isDragging) {
            return;
        }

        isDragging = false;
        viewport.classList.remove('is-dragging');

        if (viewport.hasPointerCapture(event.pointerId)) {
            viewport.releasePointerCapture(event.pointerId);
        }
    };

    viewport.addEventListener('pointerup', endDrag);
    viewport.addEventListener('pointercancel', endDrag);

    viewport.addEventListener('click', (event) => {
        if (didDrag) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        if (
            event.target.closest('.vs-image-hotspot') ||
            frame.closest('[data-map-lightbox]')
        ) {
            return;
        }

        if (frame.closest('.vs-map-lightbox')) {
            return;
        }

        const canOpenHd = !isMobileLayout() || frame.vsMapPanState.atMinZoom;
        if (canOpenHd && typeof window.openMapLightbox === 'function') {
            window.openMapLightbox();
        }
    });

    if (isMainMap) {
        frame.vsFocusHotspot = ({ id, x, y, w, h, icon, label }) => {
            const hotspotEl = id ? canvas.querySelector(`[data-hotspot-id="${CSS.escape(id)}"]`) : null;
            if (id && !hotspotEl) {
                return false;
            }

            if (useWidthZoom && fillPanel && viewport.clientWidth > 0) {
                baseDisplayWidth = viewport.clientWidth;
            } else if (useWidthZoom && (Math.abs(scale - 1) < 0.001 || !baseDisplayWidth)) {
                captureBaseDisplayWidth();
            }

            const boxW = Number.isFinite(w) ? w : 5;
            const boxH = Number.isFinite(h) ? h : 5;
            const centerX = (Number.isFinite(x) ? x : 0) + boxW / 2;
            const centerY = (Number.isFinite(y) ? y : 0) + boxH / 2;

            scale = Math.min(maxScale, Math.max(getMinScale(), POI_FOCUS_SCALE));

            const contentW = contentWidthAtScale(scale);
            const contentH = artHeightAtScale(scale);
            const pointX = (centerX / 100) * contentW;
            const pointY = (centerY / 100) * contentH;
            const vpW = viewport.clientWidth;
            const vpH = viewport.clientHeight;

            panX = vpW / 2 - pointX;
            panY = vpH / 2 - pointY;
            clampPan();
            applyTransform();

            canvas.querySelectorAll('.vs-map-location-pin').forEach((pin) => pin.remove());
            canvas.querySelectorAll('.vs-image-hotspot--active').forEach((hotspot) => {
                hotspot.classList.remove('vs-image-hotspot--active');
            });

            hotspotEl?.classList.add('vs-image-hotspot--active');

            const pin = document.createElement('div');
            pin.className = 'vs-map-location-pin';
            pin.style.left = `${centerX}%`;
            pin.style.top = `${centerY}%`;
            pin.setAttribute('role', 'img');
            pin.setAttribute(
                'aria-label',
                label ? `${label} konumu` : 'Seçili konum',
            );
            pin.innerHTML = `<span class="vs-map-location-pin__pulse" aria-hidden="true"></span><span class="vs-map-location-pin__icon">${icon ?? '📍'}</span>`;
            canvas.appendChild(pin);

            return true;
        };
    }

    if (art.complete) {
        initViewport();
        progressive.scheduleDesktopUpgrade();
    } else {
        art.addEventListener('load', () => {
            initViewport();
            progressive.scheduleDesktopUpgrade();
        });
    }

    window.addEventListener('resize', syncViewportHeight);
    clampPan();
    applyTransform();

    if (fillLightbox) {
        frame.vsMapZoom = {
            zoomIn() {
                const rect = viewport.getBoundingClientRect();
                zoomBy(ZOOM_STEP, rect.left + rect.width / 2, rect.top + rect.height / 2);
            },
            zoomOut() {
                const rect = viewport.getBoundingClientRect();
                zoomBy(-ZOOM_STEP, rect.left + rect.width / 2, rect.top + rect.height / 2);
            },
            reset: resetZoom,
        };
    }
}

function initProgressiveImage(art, options = {}) {
    const { allowDesktopUpgrade = false, onSourceChange } = options;
    const previewSrc = art.dataset.imagePreview || art.getAttribute('src') || '';
    const displaySrc = art.dataset.imageDisplay || '';
    const hdSrc = art.dataset.imageHd || '';
    const alwaysHd = art.dataset.imageResolution === 'hd';

    if (!hdSrc && !displaySrc) {
        return {
            scheduleDesktopUpgrade: () => {},
            updateResolution: () => {},
        };
    }

    const ready = { preview: true, display: false, hd: alwaysHd };
    const loading = { display: false, hd: false };
    let currentTier = alwaysHd ? 'hd' : 'preview';
    let desktopDisplayPinned = false;
    let pendingTier = null;

    const tierSrc = (tier) => {
        if (tier === 'hd') {
            return hdSrc;
        }

        if (tier === 'display') {
            return displaySrc || hdSrc;
        }

        return previewSrc;
    };

    const markTierReady = (tier) => {
        ready[tier] = true;
        const url = tierSrc(tier);
        if (url) {
            rememberWarmImageUrl(url);
        }
    };

    const applyTier = (tier, onSwap) => {
        const nextSrc = tierSrc(tier);
        if (!nextSrc || currentTier === tier) {
            onSwap?.();
            return;
        }

        currentTier = tier;
        art.classList.add('vs-image-map-art--loading');
        art.src = nextSrc;

        const done = () => {
            art.classList.remove('vs-image-map-art--loading');
            onSwap?.();
            onSourceChange?.();
        };

        if (art.complete) {
            done();
        } else {
            art.addEventListener('load', done, { once: true });
            art.addEventListener(
                'error',
                () => {
                    art.classList.remove('vs-image-map-art--loading');
                },
                { once: true },
            );
        }
    };

    const preloadTier = (tier, onReady) => {
        const url = tierSrc(tier);
        if (!url || ready[tier] || loading[tier]) {
            if (ready[tier]) {
                onReady?.();
            }

            return;
        }

        loading[tier] = true;
        const loader = new Image();
        loader.onload = () => {
            loading[tier] = false;
            markTierReady(tier);
            onReady?.();

            if (pendingTier === tier) {
                pendingTier = null;
                applyTier(tier);
            }
        };
        loader.onerror = () => {
            loading[tier] = false;
            if (pendingTier === tier) {
                pendingTier = null;
            }
        };
        loader.src = url;
    };

    const requestTier = (tier, onSwap) => {
        if (ready[tier] || tier === 'preview') {
            applyTier(tier, onSwap);
            return;
        }

        pendingTier = tier;
        preloadTier(tier, () => applyTier(tier, onSwap));
    };

    const scheduleDesktopUpgrade = () => {
        if (!allowDesktopUpgrade || alwaysHd || isMobileLayout() || prefersLightMapAssets()) {
            return;
        }

        const displayUrl = tierSrc('display');
        if (!displayUrl) {
            return;
        }

        const applyDisplay = () => {
            desktopDisplayPinned = true;
            requestTier('display');
        };

        const run = () => {
            if (isWarmImageUrl(displayUrl)) {
                applyDisplay();
                return;
            }

            preloadTier('display', applyDisplay);
        };

        if (typeof requestIdleCallback === 'function') {
            requestIdleCallback(run, { timeout: 2200 });
        } else {
            window.setTimeout(run, 350);
        }
    };

    const updateResolution = (currentScale, onSwap) => {
        if (alwaysHd) {
            requestTier('hd', onSwap);
            return;
        }

        if (desktopDisplayPinned && currentScale < HD_ZOOM_THRESHOLD) {
            if (currentTier !== 'display') {
                requestTier('display', onSwap);
            } else {
                onSwap?.();
            }

            preloadTier('hd');
            return;
        }

        if (currentScale >= HD_ZOOM_THRESHOLD) {
            requestTier('hd', onSwap);
            return;
        }

        if (desktopDisplayPinned) {
            onSwap?.();
            preloadTier('hd');
            return;
        }

        if (!isMobileLayout() && allowDesktopUpgrade && displaySrc) {
            onSwap?.();
            return;
        }

        if (currentTier !== 'preview') {
            requestTier('preview', onSwap);
        } else {
            onSwap?.();
        }
    };

    return { scheduleDesktopUpgrade, updateResolution };
}

document.querySelectorAll('[data-image-map]').forEach((stage) => {
    if (stage.dataset.pickCoords !== '1') {
        return;
    }

    const frame = stage.querySelector('[data-image-map-zoom]');
    const viewport = frame?.querySelector('[data-image-map-viewport]');
    const art = frame?.querySelector('.vs-image-map-art');
    const canvas = frame?.querySelector('[data-image-map-canvas]');
    const output = stage.querySelector('[data-coord-picker-output]');

    if (!viewport || !art || !canvas) {
        return;
    }

    viewport.addEventListener('click', (event) => {
        if (event.target.closest('.vs-image-hotspot')) {
            return;
        }

        const rect = art.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width) * 100;
        const y = ((event.clientY - rect.top) / rect.height) * 100;
        const xRounded = Math.round(x * 10) / 10;
        const yRounded = Math.round(y * 10) / 10;

        canvas.querySelectorAll('.vs-coord-picker-marker').forEach((marker) => marker.remove());

        const marker = document.createElement('span');
        marker.className = 'vs-coord-picker-marker';
        marker.style.left = `${xRounded}%`;
        marker.style.top = `${yRounded}%`;
        canvas.appendChild(marker);

        if (output) {
            output.hidden = false;
            output.style.left = `${xRounded}%`;
            output.style.top = `${yRounded}%`;
            output.textContent = `x: ${xRounded}, y: ${yRounded}`;
        }

        console.info(`Hotspot koordinatı → x: ${xRounded}, y: ${yRounded}`);
    });
});

const cityArtLayer = document.querySelector('[data-city-art-layer]');
const cityMapLayer = document.querySelector('[data-city-map-layer]');

const toggleCityMap = () => {
    const showMap = cityMapLayer?.classList.toggle('vs-city-map-layer--visible');
    cityArtLayer?.classList.toggle('vs-city-art-layer--hidden', Boolean(showMap));

    document.querySelectorAll('[data-toggle-city-map]').forEach((button) => {
        button.classList.toggle('vs-btn-ghost--active', Boolean(showMap));
        const tileLabel = button.dataset.tileLabel ?? 'İnteraktif harita';
        const artLabel = button.dataset.artLabel ?? 'Görsel harita';
        button.textContent = showMap ? artLabel : tileLabel;
    });

    if (showMap) {
        window.dispatchEvent(new CustomEvent('vs-tile-map-show'));
    }

    const drawer = document.querySelector('[data-drawer]');
    drawer?.classList.remove('vs-drawer--open');
    window.setTimeout(() => {
        if (drawer && !drawer.classList.contains('vs-drawer--open')) {
            drawer.hidden = true;
        }
    }, 260);
};

document.querySelectorAll('[data-toggle-city-map]').forEach((button) => {
    button.addEventListener('click', toggleCityMap);
});

function initMapLightbox() {
    const lightbox = document.querySelector('[data-map-lightbox]');
    if (!lightbox) {
        return;
    }

    const open = () => {
        const lightboxFrame = lightbox.querySelector('[data-image-map-zoom]');
        const hdArt = lightbox.querySelector('.vs-image-map-art');
        const hdSrc = hdArt?.dataset.imageHd;

        if (hdArt && hdSrc) {
            hdArt.src = hdSrc;
            hdArt.removeAttribute('width');
            hdArt.removeAttribute('height');
        }

        lightbox.hidden = false;
        requestAnimationFrame(() => {
            lightbox.classList.add('vs-map-lightbox--open');
            document.body.classList.add('vs-image-map-page--lightbox-open');
            window.dispatchEvent(new Event('resize'));
            lightboxFrame?.querySelector('[data-zoom-reset]')?.click();
        });
    };

    const close = () => {
        lightbox.classList.remove('vs-map-lightbox--open');
        document.body.classList.remove('vs-image-map-page--lightbox-open');
        window.setTimeout(() => {
            if (!lightbox.classList.contains('vs-map-lightbox--open')) {
                lightbox.hidden = true;
            }
        }, 220);
    };

    window.openMapLightbox = open;

    document.querySelectorAll('[data-map-lightbox-open]').forEach((button) => {
        button.addEventListener('click', open);
    });

    lightbox.querySelectorAll('[data-map-lightbox-close]').forEach((element) => {
        element.addEventListener('click', close);
    });

    window.addEventListener('keydown', (event) => {
        if (!lightbox.classList.contains('vs-map-lightbox--open')) {
            return;
        }

        if (event.key === 'Escape') {
            close();
            return;
        }

        if (!(event.ctrlKey || event.metaKey)) {
            return;
        }

        const lightboxFrame = lightbox.querySelector('[data-image-map-zoom]');
        const zoomApi = lightboxFrame?.vsMapZoom;
        if (!zoomApi) {
            return;
        }

        if (event.key === '=' || event.key === '+' || event.code === 'Equal') {
            event.preventDefault();
            zoomApi.zoomIn();
        } else if (event.key === '-' || event.key === '_' || event.code === 'Minus') {
            event.preventDefault();
            zoomApi.zoomOut();
        } else if (event.key === '0' || event.code === 'Digit0' || event.code === 'Numpad0') {
            event.preventDefault();
            zoomApi.reset();
        }
    });
}

function initLocationSheet() {
    const sheet = document.querySelector('[data-location-sheet]');
    if (!sheet) {
        return;
    }

    const toggleBtn = document.querySelector('[data-location-sheet-toggle]');

    const open = () => {
        sheet.hidden = false;
        toggleBtn?.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(() => {
            sheet.classList.add('vs-location-sheet--open');
            document.body.classList.add('vs-image-map-page--sheet-open');
        });
    };

    const close = () => {
        sheet.classList.remove('vs-location-sheet--open');
        toggleBtn?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('vs-image-map-page--sheet-open');
        window.setTimeout(() => {
            if (!sheet.classList.contains('vs-location-sheet--open')) {
                sheet.hidden = true;
            }
        }, 280);
    };

    toggleBtn?.addEventListener('click', () => {
        if (sheet.classList.contains('vs-location-sheet--open')) {
            close();
        } else {
            open();
        }
    });

    sheet.querySelectorAll('[data-location-sheet-close]').forEach((element) => {
        element.addEventListener('click', close);
    });

    sheet.querySelectorAll('[data-location-link]').forEach((link) => {
        link.addEventListener('click', close);
    });
}

function initMobileMapSwipe() {
    const stage = document.querySelector('[data-image-map][data-swipe-nav]');
    if (!stage) {
        return;
    }

    let targets = [];
    try {
        targets = JSON.parse(stage.dataset.swipeNav ?? '[]');
    } catch {
        return;
    }

    const currentId = stage.dataset.locationId;
    const frame = stage.querySelector('[data-image-map-zoom]');
    const SWIPE_THRESHOLD = 72;
    let startX = 0;
    let startY = 0;
    let tracking = false;

    stage.addEventListener(
        'touchstart',
        (event) => {
            if (!isMobileLayout() || event.touches.length !== 1) {
                return;
            }

            startX = event.touches[0].clientX;
            startY = event.touches[0].clientY;
            tracking = true;
        },
        { passive: true },
    );

    stage.addEventListener(
        'touchend',
        (event) => {
            if (!tracking || !isMobileLayout()) {
                return;
            }

            tracking = false;
            const touch = event.changedTouches[0];
            const dx = touch.clientX - startX;
            const dy = touch.clientY - startY;

            if (Math.abs(dx) < SWIPE_THRESHOLD || Math.abs(dx) < Math.abs(dy) * 1.3) {
                return;
            }

            if (frame?.vsMapPanState && !frame.vsMapPanState.atMinZoom) {
                return;
            }

            const index = targets.findIndex((item) => item.id === currentId);
            if (index < 0) {
                return;
            }

            if (dx < 0 && index < targets.length - 1) {
                window.location.assign(targets[index + 1].url);
            } else if (dx > 0 && index > 0) {
                window.location.assign(targets[index - 1].url);
            }
        },
        { passive: true },
    );

    const activeChip = document.querySelector('.vs-mobile-location-chip--active');
    activeChip?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'instant' });
}

function initPoiDeepLink() {
    const poiId = new URLSearchParams(window.location.search).get('poi');
    if (!poiId) {
        return;
    }

    const tryFocus = () => {
        const hotspotButton = document.querySelector(
            `[data-hotspot-id="${CSS.escape(poiId)}"][data-poi-link]`,
        );

        if (hotspotButton) {
            hotspotButton.click();
            return true;
        }

        const sidebarButton = document.querySelector(
            `[data-poi-id="${CSS.escape(poiId)}"][data-poi-link]:not([data-poi-map-url])`,
        );
        if (sidebarButton) {
            sidebarButton.click();
            return true;
        }

        return false;
    };

    if (tryFocus()) {
        return;
    }

    const art = getMainMapFrame()?.querySelector('.vs-image-map-art');
    if (art && !art.complete) {
        art.addEventListener('load', () => tryFocus(), { once: true });
    } else {
        window.setTimeout(tryFocus, 120);
    }
}
