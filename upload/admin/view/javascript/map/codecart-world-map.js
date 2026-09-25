/* CodeCart PRO World Map v1.1.0
 * Dependency-free SVG renderer for the admin dashboard.
 * Supports zoom, pan, region selection and keyboard navigation.
 */
(function (window, document) {
    'use strict';

    var SVG_NS = 'http://www.w3.org/2000/svg';
    var MIN_SCALE = 1;
    var MAX_SCALE = 6;
    var ZOOM_STEP = 1.35;

    function createSvg(name) {
        return document.createElementNS(SVG_NS, name);
    }

    function safeInteger(value) {
        value = parseInt(value, 10);
        return isFinite(value) && value > 0 ? value : 0;
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function createButton(text, title, className) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ccp-world-map-control ' + className;
        button.textContent = text;
        button.title = title;
        button.setAttribute('aria-label', title);
        return button;
    }

    function render(element, options) {
        if (!element || !window.CodeCartWorldMapData) {
            return false;
        }

        options = options || {};
        var data = window.CodeCartWorldMapData;
        var values = options.values || {};
        var paths = data.paths || {};
        var baseWidth = safeInteger(data.width) || 950;
        var baseHeight = safeInteger(data.height) || 550;
        var max = 0;
        var selectedCode = '';
        var state = {
            scale: 1,
            x: 0,
            y: 0,
            width: baseWidth,
            height: baseHeight,
            dragging: false,
            dragMoved: false,
            dragStartX: 0,
            dragStartY: 0,
            dragViewX: 0,
            dragViewY: 0
        };

        Object.keys(values).forEach(function (key) {
            var value = safeInteger(values[key]);
            if (value > max) {
                max = value;
            }
        });

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }

        var svg = createSvg('svg');
        svg.setAttribute('viewBox', '0 0 ' + baseWidth + ' ' + baseHeight);
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', options.label || element.getAttribute('aria-label') || 'World map');
        svg.classList.add('ccp-world-map-svg');

        var mapLayer = createSvg('g');
        mapLayer.classList.add('ccp-world-map-layer');
        svg.appendChild(mapLayer);

        var tooltip = document.createElement('div');
        tooltip.className = 'ccp-world-map-tooltip';
        tooltip.setAttribute('role', 'status');
        tooltip.setAttribute('aria-live', 'polite');
        tooltip.hidden = true;

        var controls = document.createElement('div');
        controls.className = 'ccp-world-map-controls';
        controls.setAttribute('role', 'group');
        controls.setAttribute('aria-label', options.controlsLabel || options.label || 'Map controls');
        var zoomIn = createButton('+', options.zoomInLabel || 'Zoom in', 'ccp-world-map-zoom-in');
        var zoomOut = createButton('−', options.zoomOutLabel || 'Zoom out', 'ccp-world-map-zoom-out');
        var reset = createButton('↺', options.resetLabel || 'Reset map', 'ccp-world-map-reset');
        controls.appendChild(zoomIn);
        controls.appendChild(zoomOut);
        controls.appendChild(reset);

        element.appendChild(svg);
        element.appendChild(controls);
        element.appendChild(tooltip);

        function updateViewBox() {
            svg.setAttribute('viewBox', [state.x, state.y, state.width, state.height].join(' '));
            svg.classList.toggle('is-zoomed', state.scale > 1.001);
            zoomOut.disabled = state.scale <= MIN_SCALE + 0.001;
            reset.disabled = state.scale <= MIN_SCALE + 0.001 && !selectedCode;
        }

        function normalizeView() {
            state.width = baseWidth / state.scale;
            state.height = baseHeight / state.scale;
            state.x = clamp(state.x, 0, Math.max(0, baseWidth - state.width));
            state.y = clamp(state.y, 0, Math.max(0, baseHeight - state.height));
        }

        function svgPoint(clientX, clientY) {
            if (svg.createSVGPoint && svg.getScreenCTM()) {
                var point = svg.createSVGPoint();
                point.x = clientX;
                point.y = clientY;
                try {
                    return point.matrixTransform(svg.getScreenCTM().inverse());
                } catch (error) {
                    // Fall back to element-relative calculations below.
                }
            }
            var rect = svg.getBoundingClientRect();
            var fx = rect.width ? clamp((clientX - rect.left) / rect.width, 0, 1) : 0.5;
            var fy = rect.height ? clamp((clientY - rect.top) / rect.height, 0, 1) : 0.5;
            return {x: state.x + fx * state.width, y: state.y + fy * state.height};
        }

        function setScale(nextScale, clientX, clientY) {
            nextScale = clamp(nextScale, MIN_SCALE, MAX_SCALE);
            if (Math.abs(nextScale - state.scale) < 0.001) {
                return;
            }

            var rect = svg.getBoundingClientRect();
            if (!isFinite(clientX) || !isFinite(clientY)) {
                clientX = rect.left + rect.width / 2;
                clientY = rect.top + rect.height / 2;
            }

            var anchor = svgPoint(clientX, clientY);
            var fx = state.width ? clamp((anchor.x - state.x) / state.width, 0, 1) : 0.5;
            var fy = state.height ? clamp((anchor.y - state.y) / state.height, 0, 1) : 0.5;
            var nextWidth = baseWidth / nextScale;
            var nextHeight = baseHeight / nextScale;

            state.scale = nextScale;
            state.width = nextWidth;
            state.height = nextHeight;
            state.x = anchor.x - fx * nextWidth;
            state.y = anchor.y - fy * nextHeight;
            normalizeView();
            updateViewBox();
        }

        function resetView(clearSelection) {
            state.scale = 1;
            state.x = 0;
            state.y = 0;
            state.width = baseWidth;
            state.height = baseHeight;
            if (clearSelection !== false) {
                selectRegion('', null, false);
            }
            updateViewBox();
        }

        function hideTooltip() {
            tooltip.hidden = true;
        }

        function showTooltip(event, name, value) {
            tooltip.textContent = name + (value > 0 ? ' — ' + value : '');
            tooltip.hidden = false;
            var rect = element.getBoundingClientRect();
            var clientX = event && isFinite(event.clientX) ? event.clientX : rect.left + rect.width / 2;
            var clientY = event && isFinite(event.clientY) ? event.clientY : rect.top + rect.height / 2;
            var left = clientX - rect.left + 10;
            var top = clientY - rect.top + 10;
            tooltip.style.left = Math.max(4, Math.min(left, rect.width - tooltip.offsetWidth - 4)) + 'px';
            tooltip.style.top = Math.max(4, Math.min(top, rect.height - tooltip.offsetHeight - 4)) + 'px';
        }

        function selectRegion(code, event, emit) {
            selectedCode = code || '';
            var regions = mapLayer.querySelectorAll('.ccp-world-map-region');
            for (var i = 0; i < regions.length; i++) {
                var active = regions[i].getAttribute('data-code') === selectedCode;
                regions[i].classList.toggle('is-selected', active);
                regions[i].setAttribute('aria-pressed', active ? 'true' : 'false');
            }
            reset.disabled = state.scale <= MIN_SCALE + 0.001 && !selectedCode;

            if (emit && selectedCode) {
                var region = paths[selectedCode] || {};
                var detail = {
                    code: selectedCode,
                    name: String(region.name || selectedCode.toUpperCase()),
                    value: safeInteger(values[selectedCode])
                };
                var customEvent;
                if (typeof window.CustomEvent === 'function') {
                    customEvent = new CustomEvent('codecart:mapselect', {detail: detail});
                } else {
                    customEvent = document.createEvent('CustomEvent');
                    customEvent.initCustomEvent('codecart:mapselect', false, false, detail);
                }
                element.dispatchEvent(customEvent);
            }
        }

        Object.keys(paths).forEach(function (key) {
            var code = String(key).toLowerCase();
            var region = paths[key] || {};
            if (!region.path) {
                return;
            }

            var value = safeInteger(values[code]);
            var ratio = max > 0 ? value / max : 0;
            var opacity = value > 0 ? (0.28 + ratio * 0.64) : 1;
            var path = createSvg('path');
            path.setAttribute('d', region.path);
            path.setAttribute('data-code', code);
            path.setAttribute('vector-effect', 'non-scaling-stroke');
            path.setAttribute('fill', value > 0 ? '#0b6fd3' : '#e8eef5');
            path.setAttribute('fill-opacity', value > 0 ? opacity.toFixed(2) : '1');
            path.setAttribute('stroke', '#d0d9e4');
            path.setAttribute('stroke-width', '0.8');
            path.setAttribute('tabindex', '0');
            path.setAttribute('role', 'button');
            path.setAttribute('aria-label', String(region.name || code.toUpperCase()) + (value > 0 ? ' — ' + value : ''));
            path.setAttribute('aria-pressed', 'false');
            path.classList.add('ccp-world-map-region');

            var title = createSvg('title');
            title.textContent = String(region.name || code.toUpperCase()) + (value > 0 ? ' — ' + value : '');
            path.appendChild(title);

            path.addEventListener('mouseenter', function (event) {
                showTooltip(event, String(region.name || code.toUpperCase()), value);
            });
            path.addEventListener('mousemove', function (event) {
                if (!tooltip.hidden) {
                    showTooltip(event, String(region.name || code.toUpperCase()), value);
                }
            });
            path.addEventListener('mouseleave', function () {
                hideTooltip();
            });
            path.addEventListener('click', function (event) {
                if (state.dragMoved) {
                    state.dragMoved = false;
                    return;
                }
                selectRegion(code, event, true);
                showTooltip(event, String(region.name || code.toUpperCase()), value);
            });
            path.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectRegion(code, event, true);
                }
            });

            mapLayer.appendChild(path);
        });

        zoomIn.addEventListener('click', function () {
            setScale(state.scale * ZOOM_STEP);
        });
        zoomOut.addEventListener('click', function () {
            setScale(state.scale / ZOOM_STEP);
        });
        reset.addEventListener('click', function () {
            resetView(true);
        });

        svg.addEventListener('dblclick', function (event) {
            event.preventDefault();
            setScale(state.scale * ZOOM_STEP, event.clientX, event.clientY);
        });

        svg.addEventListener('wheel', function (event) {
            // Do not trap normal page scrolling. Ctrl/Cmd + wheel zooms the map.
            if (!event.ctrlKey && !event.metaKey) {
                return;
            }
            event.preventDefault();
            var factor = event.deltaY < 0 ? ZOOM_STEP : (1 / ZOOM_STEP);
            setScale(state.scale * factor, event.clientX, event.clientY);
        }, {passive: false});

        svg.addEventListener('pointerdown', function (event) {
            if (state.scale <= MIN_SCALE + 0.001 || event.button !== 0) {
                return;
            }
            state.dragging = true;
            state.dragMoved = false;
            state.dragStartX = event.clientX;
            state.dragStartY = event.clientY;
            state.dragViewX = state.x;
            state.dragViewY = state.y;
            svg.classList.add('is-dragging');
            if (svg.setPointerCapture) {
                try { svg.setPointerCapture(event.pointerId); } catch (error) {}
            }
        });

        svg.addEventListener('pointermove', function (event) {
            if (!state.dragging) {
                return;
            }
            var rect = svg.getBoundingClientRect();
            if (!rect.width || !rect.height) {
                return;
            }
            var dx = event.clientX - state.dragStartX;
            var dy = event.clientY - state.dragStartY;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
                state.dragMoved = true;
            }
            state.x = state.dragViewX - (dx / rect.width) * state.width;
            state.y = state.dragViewY - (dy / rect.height) * state.height;
            normalizeView();
            updateViewBox();
        });

        function endDrag(event) {
            if (!state.dragging) {
                return;
            }
            state.dragging = false;
            svg.classList.remove('is-dragging');
            if (svg.releasePointerCapture && event && typeof event.pointerId !== 'undefined') {
                try { svg.releasePointerCapture(event.pointerId); } catch (error) {}
            }
            window.setTimeout(function () { state.dragMoved = false; }, 0);
        }

        svg.addEventListener('pointerup', endDrag);
        svg.addEventListener('pointercancel', endDrag);
        svg.addEventListener('pointerleave', function (event) {
            if (state.dragging) {
                endDrag(event);
            }
        });

        updateViewBox();

        element.codeCartMap = {
            zoomIn: function () { setScale(state.scale * ZOOM_STEP); },
            zoomOut: function () { setScale(state.scale / ZOOM_STEP); },
            reset: function () { resetView(true); },
            select: function (code) { selectRegion(String(code || '').toLowerCase(), null, true); },
            getScale: function () { return state.scale; }
        };

        return true;
    }

    window.CodeCartWorldMap = {
        render: render,
        version: '1.1.0'
    };
}(window, document));
