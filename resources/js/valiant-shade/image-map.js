const MIN_SCALE = 1;
const MAX_SCALE = 3;
const ZOOM_STEP = 0.2;
const HD_ZOOM_THRESHOLD = 1.15;
const MOBILE_QUERY = window.matchMedia('(max-width: 768px)');

document.querySelectorAll('[data-image-map-zoom]').forEach((frame) => {
    initImageMapZoom(frame);
});

initImageMapDrawer();
initExclusiveKingdomAccordions();
initBuildingInfoPanel();
initPoiInfoPanel();

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

        iconEl.textContent = button.dataset.poiIcon ?? '📍';
        titleEl.textContent = button.dataset.poiName ?? '';
        descEl.textContent = button.dataset.poiDesc ?? '';
        panel.hidden = false;

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

    const progressive = initProgressiveImage(art);
    const useWidthZoom =
        frame.dataset.imageMapZoomMode === 'width' ||
        (frame.dataset.imageMapZoomMode !== 'transform' &&
            !frame.closest('.vs-dual-map-layer--art, .vs-city-art-layout'));

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
            canvas.style.transform = `translate(${panX}px, ${panY}px)`;
            return;
        }

        canvas.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
    };

    const applyTransform = () => {
        applyCanvasTransform();
        updateLabel();
        if (useWidthZoom) {
            progressive.updateResolution(scale, () => {
                clampPan();
                applyCanvasTransform();
            });
        } else {
            progressive.updateResolution(scale, layoutViewport);
        }
    };

    const setScale = (nextScale, originX, originY) => {
        const clamped = Math.min(MAX_SCALE, Math.max(getMinScale(), nextScale));

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

    const layoutViewport = () => {
        if (useWidthZoom && (Math.abs(scale - 1) < 0.001 || scale <= getMinScale())) {
            captureBaseDisplayWidth();
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
            captureBaseDisplayWidth();
        } else {
            clearArtInlineSize();
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

    viewport.addEventListener(
        'wheel',
        (event) => {
            event.preventDefault();
            const delta = event.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP;
            zoomBy(delta, event.clientX, event.clientY);
        },
        { passive: false },
    );

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
        }
    });

    if (art.complete) {
        initViewport();
        progressive.preloadHd();
    } else {
        art.addEventListener('load', () => {
            initViewport();
            progressive.preloadHd();
        });
    }

    window.addEventListener('resize', syncViewportHeight);
    clampPan();
    applyTransform();
}

function initProgressiveImage(art) {
    const previewSrc = art.dataset.imagePreview;
    const hdSrc = art.dataset.imageHd;

    if (!previewSrc || !hdSrc) {
        return {
            preloadHd: () => {},
            updateResolution: () => {},
        };
    }

    let hdReady = false;
    let hdLoading = false;
    let showingHd = false;
    let pendingHdSwap = false;

    const swapToHd = (onSwap) => {
        if (showingHd) {
            return;
        }

        showingHd = true;
        art.src = hdSrc;

        const done = () => onSwap?.();
        if (art.complete) {
            done();
        } else {
            art.addEventListener('load', done, { once: true });
        }
    };

    const swapToPreview = (onSwap) => {
        if (!showingHd) {
            return;
        }

        showingHd = false;
        art.src = previewSrc;

        const done = () => onSwap?.();
        if (art.complete) {
            done();
        } else {
            art.addEventListener('load', done, { once: true });
        }
    };

    const preloadHd = () => {
        if (hdReady || hdLoading) {
            return;
        }

        hdLoading = true;
        const loader = new Image();
        loader.onload = () => {
            hdReady = true;
            hdLoading = false;

            if (pendingHdSwap) {
                pendingHdSwap = false;
                swapToHd();
            }
        };
        loader.onerror = () => {
            hdLoading = false;
            pendingHdSwap = false;
        };
        loader.src = hdSrc;
    };

    const updateResolution = (currentScale, onSwap) => {
        const wantsHd = currentScale >= HD_ZOOM_THRESHOLD;

        if (wantsHd) {
            if (hdReady) {
                swapToHd(onSwap);
            } else {
                pendingHdSwap = true;
                preloadHd();
            }
            return;
        }

        pendingHdSwap = false;
        swapToPreview(onSwap);
    };

    return { preloadHd, updateResolution };
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
