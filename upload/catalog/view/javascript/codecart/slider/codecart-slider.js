/**
 * CodeCart PRO Slider 1.2.2
 * Lightweight dependency-free slider for the bundled default theme.
 * Legacy Swiper 3 remains available only for OpenCart 3 compatibility.
 */
(function (window, document) {
    'use strict';

    var registry = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
    var instances = [];

    function toInt(value, fallback) {
        var number = parseInt(value, 10);
        return isFinite(number) ? number : fallback;
    }

    function reducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function Slider(root, initialWidth) {
        if (!root || root.getAttribute('data-ccp-slider-ready') === '1') {
            return;
        }

        this.root = root;
        this.viewport = root.querySelector('.ccp-slider__viewport');
        this.track = root.querySelector('.ccp-slider__track');
        this.slides = this.track ? Array.prototype.slice.call(this.track.children) : [];
        this.prev = root.querySelector('.ccp-slider__prev');
        this.next = root.querySelector('.ccp-slider__next');
        this.pagination = root.querySelector('.ccp-slider__pagination');
        this.toggle = root.querySelector('.ccp-slider__autoplay-toggle');
        this.effect = root.getAttribute('data-effect') || 'slide';
        if (['slide', 'fade', 'zoom'].indexOf(this.effect) === -1) {
            this.effect = 'slide';
        }
        this.kind = root.getAttribute('data-kind') || 'slideshow';
        this.basePerView = Math.max(1, toInt(root.getAttribute('data-per-view'), 1));
        this.desktopPerView = Math.max(1, toInt(root.getAttribute('data-per-view-desktop'), this.basePerView));
        this.tabletPerView = Math.max(1, toInt(root.getAttribute('data-per-view-tablet'), Math.min(3, this.desktopPerView)));
        this.mobilePerView = Math.max(1, toInt(root.getAttribute('data-per-view-mobile'), Math.min(2, this.tabletPerView)));
        this.gap = Math.max(0, toInt(root.getAttribute('data-gap'), this.kind === 'carousel' ? 20 : 0));
        this.autoplay = Math.max(0, toInt(root.getAttribute('data-autoplay'), 0));
        this.loop = root.getAttribute('data-loop') !== '0';
        this.pauseOnHover = root.getAttribute('data-pause-on-hover') === '1';
        this.stepMode = root.getAttribute('data-step') === 'page' ? 'page' : 'item';
        this.index = 0;
        this.perView = 1;
        this.maxIndex = 0;
        this.timer = 0;
        this.pointerStartX = null;
        this.pointerStartY = null;
        this.resizeTimer = 0;
        this.userPaused = false;
        this.inViewport = true;
        this.destroyed = false;
        this.animationToken = 0;
        this.transitionFallbackTimer = 0;
        this.onWindowResize = null;
        this.onVisibilityChange = null;
        this.initialWidth = Math.max(0, Number(initialWidth) || 0);

        if (!this.viewport || !this.track || !this.slides.length) {
            root.setAttribute('data-ccp-slider-ready', '1');
            return;
        }

        root.setAttribute('data-ccp-slider-ready', '1');
        if (this.effect === 'fade' || this.effect === 'zoom') {
            root.classList.add('ccp-slider--layered');
            root.classList.add('ccp-slider--' + this.effect);
        }

        this.bind();
        this.layout(false);
        root.classList.add('ccp-slider--ready');
        this.start();
    }

    Slider.prototype.resolvePerView = function () {
        if (this.kind !== 'carousel') {
            return this.basePerView;
        }

        var width = this.initialWidth || this.viewport.clientWidth || this.root.clientWidth || window.innerWidth;
        if (width < 576) {
            return this.mobilePerView;
        }
        if (width < 992) {
            return this.tabletPerView;
        }
        return this.desktopPerView;
    };

    Slider.prototype.stepSize = function () {
        if (this.effect !== 'slide' || this.kind !== 'carousel' || this.stepMode !== 'page') {
            return 1;
        }
        return Math.max(1, this.perView);
    };

    Slider.prototype.positions = function () {
        if (this.effect !== 'slide') {
            var layeredPositions = [];
            for (var f = 0; f < this.slides.length; f++) { layeredPositions.push(f); }
            return layeredPositions;
        }
        if (this.infiniteItem) {
            var itemPositions = [];
            if (this.stepMode === 'page') {
                var pageStep = Math.max(1, this.perView);
                for (var pp = 0; pp < this.originalCount; pp += pageStep) { itemPositions.push(pp); }
            } else {
                for (var ip = 0; ip < this.originalCount; ip++) { itemPositions.push(ip); }
            }
            return itemPositions;
        }
        var max = this.maxIndex;
        var step = this.stepSize();
        var positions = [0];
        if (max <= 0) { return positions; }
        for (var pos = step; pos < max; pos += step) { positions.push(pos); }
        if (positions[positions.length - 1] !== max) { positions.push(max); }
        return positions;
    };

    Slider.prototype.layout = function (animate) {
        this.slides = Array.prototype.slice.call(this.track.children);
        if (!this.originalCount) { this.originalCount = this.slides.length; }
        this.perView = Math.max(1, Math.min(this.resolvePerView(), this.slides.length));
        this.infiniteItem = !!(this.loop && this.effect === 'slide' && this.originalCount > this.perView);
        this.maxIndex = this.infiniteItem ? Math.max(0, this.originalCount - 1) : Math.max(0, this.slides.length - this.perView);
        if (this.index > this.maxIndex) {
            this.index = this.maxIndex;
        }

        if (this.effect === 'slide') {
            var width = this.initialWidth || this.viewport.clientWidth;
            var slideWidth = Math.max(1, (width - (this.gap * (this.perView - 1))) / this.perView);
            this.slideWidth = slideWidth;
            this.track.style.gap = this.gap + 'px';
            for (var i = 0; i < this.slides.length; i++) {
                this.slides[i].style.width = slideWidth + 'px';
                this.slides[i].style.flexBasis = slideWidth + 'px';
            }
        }

        this.initialWidth = 0;
        this.render(animate !== false);
        this.buildPagination();
        this.updateControls();
    };

    Slider.prototype.render = function (animate) {
        if (this.effect !== 'slide') {
            for (var i = 0; i < this.slides.length; i++) {
                var active = i === this.index;
                this.slides[i].classList.toggle('is-active', active);
                this.slides[i].setAttribute('aria-hidden', active ? 'false' : 'true');
            }
        } else {
            var offset = this.infiniteItem ? 0 : this.index * ((this.slideWidth || 0) + this.gap);
            if (!animate || reducedMotion()) {
                this.track.classList.add('ccp-slider__track--instant');
            }
            this.track.style.transform = 'translate3d(' + (-offset) + 'px,0,0)';
            if (!animate || reducedMotion()) {
                var track = this.track;
                window.requestAnimationFrame(function () {
                    track.classList.remove('ccp-slider__track--instant');
                });
            }
        }

        this.updatePagination();
        this.updateControls();
    };

    Slider.prototype.move = function (direction, userAction) {
        direction = direction < 0 ? -1 : 1;
        if (!this.slides.length) { return; }
        if (this.animating) {
            // Never queue rapid clicks while a transform is still running. Queued
            // DOM rotations were the source of visible jumps in manufacturer carousels.
            if (userAction) { this.deferAfterInteraction(); }
            return;
        }
        if (this.infiniteItem && this.stepMode === 'page') {
            var positions = this.positions();
            var current = positions.indexOf(this.index);
            if (current < 0) {
                var best = 0, bestDistance = Infinity;
                for (var i = 0; i < positions.length; i++) {
                    var d = Math.abs(positions[i] - this.index);
                    if (d < bestDistance) { bestDistance = d; best = i; }
                }
                current = best;
            }
            var targetPos = positions[(current + direction + positions.length) % positions.length];
            var count = direction > 0 ? (targetPos - this.index + this.originalCount) % this.originalCount : (this.index - targetPos + this.originalCount) % this.originalCount;
            if (count === 0) { count = Math.min(this.perView, this.originalCount); }
            this.rotateInfinite(direction, count, userAction);
            return;
        }
        this.go(this.index + (direction * this.stepSize()), userAction);
    };

    Slider.prototype.go = function (index, userAction) {
        if (!this.slides.length || this.animating) {
            return;
        }

        if (this.infiniteItem) {
            var delta = index - this.index;
            if (Math.abs(delta) > 1 && index >= 0 && index < this.originalCount) {
                var forward = (index - this.index + this.originalCount) % this.originalCount;
                var backward = (this.index - index + this.originalCount) % this.originalCount;
                delta = forward <= backward ? forward : -backward;
            }
            if (delta === 0) { return; }
            this.rotateInfinite(delta > 0 ? 1 : -1, Math.max(1, Math.abs(delta)), userAction);
            return;
        }

        if (index > this.maxIndex) {
            index = this.loop ? 0 : this.maxIndex;
        }
        if (index < 0) {
            index = this.loop ? this.maxIndex : 0;
        }

        this.index = index;
        this.render(true);

        if (userAction) {
            this.deferAfterInteraction();
        }
    };

    Slider.prototype.rotateInfinite = function (direction, count, userAction) {
        var self = this;
        if (this.animating) { return; }
        if (userAction) { this.deferAfterInteraction(); }
        count = Math.max(1, Math.min(count, this.originalCount));
        var unit = (this.slideWidth || 0) + this.gap;
        if (!unit) { return; }

        this.animating = true;
        this.stop();
        this.updateControls();
        this.animationToken += 1;
        var token = this.animationToken;
        if (this.transitionFallbackTimer) {
            window.clearTimeout(this.transitionFallbackTimer);
            this.transitionFallbackTimer = 0;
        }

        function finish() {
            if (token !== self.animationToken || !self.animating) { return; }
            self.track.removeEventListener('transitionend', onEnd);
            if (self.transitionFallbackTimer) {
                window.clearTimeout(self.transitionFallbackTimer);
                self.transitionFallbackTimer = 0;
            }
            if (direction > 0) {
                for (var i = 0; i < count; i++) {
                    if (self.track.firstElementChild) { self.track.appendChild(self.track.firstElementChild); }
                }
            }
            self.track.classList.add('ccp-slider__track--instant');
            self.track.style.transform = 'translate3d(0,0,0)';
            self.slides = Array.prototype.slice.call(self.track.children);
            self.index = (self.index + (direction * count)) % self.originalCount;
            if (self.index < 0) { self.index += self.originalCount; }
            window.requestAnimationFrame(function () {
                if (token !== self.animationToken) { return; }
                self.track.classList.remove('ccp-slider__track--instant');
                self.animating = false;
                self.updatePagination();
                self.updateControls();
                self.start();
            });
        }
        function onEnd(event) {
            if (token !== self.animationToken) { return; }
            if (!event || (event.target === self.track && (!event.propertyName || event.propertyName === 'transform'))) { finish(); }
        }
        function armFallback() {
            self.transitionFallbackTimer = window.setTimeout(function () {
                if (token === self.animationToken && self.animating) { finish(); }
            }, 460);
        }

        if (direction < 0) {
            self.track.classList.add('ccp-slider__track--instant');
            for (var p = 0; p < count; p++) {
                if (self.track.lastElementChild) { self.track.insertBefore(self.track.lastElementChild, self.track.firstElementChild); }
            }
            self.track.style.transform = 'translate3d(' + (-(count * unit)) + 'px,0,0)';
            void self.track.offsetWidth;
            self.track.classList.remove('ccp-slider__track--instant');
            window.requestAnimationFrame(function () {
                if (token !== self.animationToken) { return; }
                self.track.addEventListener('transitionend', onEnd);
                self.track.style.transform = 'translate3d(0,0,0)';
                armFallback();
            });
        } else {
            self.track.addEventListener('transitionend', onEnd);
            self.track.style.transform = 'translate3d(' + (-(count * unit)) + 'px,0,0)';
            armFallback();
        }
    };

    Slider.prototype.buildPagination = function () {
        if (!this.pagination) {
            return;
        }

        var positions = this.positions();
        var count = positions.length;
        if (count <= 1) {
            this.pagination.innerHTML = '';
            this.pagination.hidden = true;
            return;
        }

        if (this.pagination.children.length !== count) {
            this.pagination.innerHTML = '';
            for (var i = 0; i < count; i++) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'ccp-slider__bullet';
                button.setAttribute('data-index', String(positions[i]));
                button.setAttribute('aria-label', (this.root.getAttribute('data-slide-label') || 'Slide') + ' ' + (i + 1));
                this.pagination.appendChild(button);
            }
        } else {
            for (var j = 0; j < count; j++) {
                this.pagination.children[j].setAttribute('data-index', String(positions[j]));
            }
        }
        this.pagination.hidden = false;
        this.updatePagination();
    };

    Slider.prototype.updatePagination = function () {
        if (!this.pagination) {
            return;
        }
        var bullets = this.pagination.querySelectorAll('.ccp-slider__bullet');
        for (var i = 0; i < bullets.length; i++) {
            var active = toInt(bullets[i].getAttribute('data-index'), -1) === this.index;
            bullets[i].classList.toggle('is-active', active);
            bullets[i].setAttribute('aria-current', active ? 'true' : 'false');
        }
    };

    Slider.prototype.updateControls = function () {
        var unavailable = this.infiniteItem ? this.originalCount <= this.perView : this.maxIndex <= 0;
        var disabled = !!this.animating || unavailable;
        if (this.prev) {
            this.prev.hidden = unavailable;
            this.prev.disabled = disabled || (!this.loop && this.index <= 0);
        }
        if (this.next) {
            this.next.hidden = unavailable;
            this.next.disabled = disabled || (!this.loop && this.index >= this.maxIndex);
        }
        if (this.toggle) {
            var autoplayUnavailable = !this.autoplay || unavailable || reducedMotion();
            this.toggle.hidden = autoplayUnavailable;
            if (!autoplayUnavailable) {
                var label = this.userPaused ? (this.root.getAttribute('data-play-label') || 'Resume autoplay') : (this.root.getAttribute('data-pause-label') || 'Pause autoplay');
                this.toggle.setAttribute('title', label);
                this.toggle.setAttribute('aria-label', label);
                this.toggle.setAttribute('aria-pressed', this.userPaused ? 'true' : 'false');
                var icon = this.toggle.querySelector('i');
                if (icon) { icon.className = this.userPaused ? 'fa fa-play' : 'fa fa-pause'; }
            }
        }
    };

    Slider.prototype.hasKeyboardFocus = function () {
        var active = document.activeElement;
        return !!(active && this.root.contains(active) && active.matches && active.matches(':focus-visible'));
    };

    Slider.prototype.start = function () {
        var self = this;
        if (this.timer) { window.clearInterval(this.timer); this.timer = 0; }
        if (this.destroyed || !this.inViewport || this.userPaused || !this.autoplay || this.maxIndex <= 0 || reducedMotion() || document.hidden || (this.pauseOnHover && this.root.matches && this.root.matches(':hover')) || this.hasKeyboardFocus()) { this.updateControls(); return; }
        this.timer = window.setInterval(function () { self.move(1, false); }, this.autoplay);
        this.updateControls();
    };

    Slider.prototype.stop = function () {
        if (this.timer) { window.clearInterval(this.timer); this.timer = 0; }
    };

    Slider.prototype.deferAfterInteraction = function () {
        // A deliberate manual navigation action pauses autoplay. This prevents
        // the timer from racing the transition (especially on fast repeated
        // clicks/swipes) and keeps the user's chosen slide stable until they
        // explicitly press Play again.
        this.stop();
        if (this.autoplay > 0) {
            this.userPaused = true;
        }
        this.updateControls();
    };

    Slider.prototype.restart = function () { this.stop(); this.start(); };

    Slider.prototype.toggleAutoplay = function () {
        this.userPaused = !this.userPaused;
        this.stop();
        this.updateControls();
        if (!this.userPaused) { this.start(); }
    };

    Slider.prototype.bind = function () {
        var self = this;

        if (this.prev) {
            this.prev.addEventListener('click', function (event) { self.move(-1, true); if (event && event.detail > 0 && this.blur) { this.blur(); } });
        }
        if (this.next) {
            this.next.addEventListener('click', function (event) { self.move(1, true); if (event && event.detail > 0 && this.blur) { this.blur(); } });
        }
        if (this.pagination) {
            this.pagination.addEventListener('click', function (event) {
                var target = event.target.closest ? event.target.closest('.ccp-slider__bullet') : event.target;
                if (target && target.hasAttribute('data-index')) {
                    self.go(toInt(target.getAttribute('data-index'), 0), true);
                    if (event && event.detail > 0 && target.blur) { target.blur(); }
                }
            });
        }

        if (this.toggle) {
            this.toggle.addEventListener('click', function (event) { self.toggleAutoplay(); if (event && event.detail > 0 && this.blur) { this.blur(); } });
        }

        if (this.pauseOnHover) {
            this.root.addEventListener('mouseenter', function () { self.stop(); });
            this.root.addEventListener('mouseleave', function () { self.start(); });
        }
        this.root.addEventListener('focusin', function (event) {
            if (event.target && event.target.matches && event.target.matches(':focus-visible')) { self.stop(); }
        });
        this.root.addEventListener('focusout', function () { window.setTimeout(function () { self.start(); }, 0); });

        this.viewport.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }
            self.pointerStartX = event.clientX;
            self.pointerStartY = event.clientY;
        });
        this.viewport.addEventListener('pointerup', function (event) {
            if (self.pointerStartX === null) {
                return;
            }
            var dx = event.clientX - self.pointerStartX;
            var dy = event.clientY - self.pointerStartY;
            self.pointerStartX = null;
            self.pointerStartY = null;
            if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.25) {
                self.move(dx < 0 ? 1 : -1, true);
            }
        });
        this.viewport.addEventListener('pointercancel', function () {
            self.pointerStartX = null;
            self.pointerStartY = null;
        });

        this.root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                self.move(-1, true);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                self.move(1, true);
            }
        });

        if (typeof ResizeObserver !== 'undefined') {
            this.resizeObserver = new ResizeObserver(function () { if (!self.destroyed) { self.layout(false); } });
            this.resizeObserver.observe(this.viewport);
        } else {
            this.onWindowResize = function () {
                if (self.destroyed) { return; }
                window.clearTimeout(self.resizeTimer);
                self.resizeTimer = window.setTimeout(function () { if (!self.destroyed) { self.layout(false); } }, 100);
            };
            window.addEventListener('resize', this.onWindowResize);
        }

        this.onVisibilityChange = function () {
            if (self.destroyed || document.hidden) {
                self.stop();
            } else {
                self.start();
            }
        };
        document.addEventListener('visibilitychange', this.onVisibilityChange);

        if (typeof IntersectionObserver !== 'undefined') {
            this.intersectionObserver = new IntersectionObserver(function (entries) {
                if (self.destroyed || !entries.length) { return; }
                self.inViewport = !!entries[0].isIntersecting;
                if (self.inViewport) { self.start(); } else { self.stop(); }
            }, {rootMargin: '160px 0px'});
            this.intersectionObserver.observe(this.root);
        }
    };

    Slider.prototype.destroy = function () {
        if (this.destroyed) { return; }
        this.destroyed = true;
        this.stop();
        if (this.resizeTimer) { window.clearTimeout(this.resizeTimer); this.resizeTimer = 0; }
        if (this.transitionFallbackTimer) { window.clearTimeout(this.transitionFallbackTimer); this.transitionFallbackTimer = 0; }
        this.animationToken += 1;
        if (this.resizeObserver) { this.resizeObserver.disconnect(); this.resizeObserver = null; }
        if (this.intersectionObserver) { this.intersectionObserver.disconnect(); this.intersectionObserver = null; }
        if (this.onWindowResize) { window.removeEventListener('resize', this.onWindowResize); this.onWindowResize = null; }
        if (this.onVisibilityChange) { document.removeEventListener('visibilitychange', this.onVisibilityChange); this.onVisibilityChange = null; }
    };

    function prune() {
        for (var i = instances.length - 1; i >= 0; i--) {
            var instance = instances[i];
            if (!instance || !instance.root || !document.documentElement.contains(instance.root)) {
                if (instance && instance.destroy) { instance.destroy(); }
                instances.splice(i, 1);
            }
        }
    }

    function init(root, initialWidth) {
        if (!root || root.getAttribute('data-ccp-slider-ready') === '1') {
            return null;
        }
        var instance = new Slider(root, initialWidth);
        if (instance) {
            instances.push(instance);
            if (registry) { registry.set(root, instance); }
        }
        return instance;
    }

    function scan(context) {
        context = context || document;
        var candidates = [];
        if (context.nodeType === 1 && context.matches && context.matches('[data-ccp-slider]') && context.getAttribute('data-ccp-slider-ready') !== '1') {
            candidates.push(context);
        }
        var nodes = context.querySelectorAll ? context.querySelectorAll('[data-ccp-slider]') : [];
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].getAttribute('data-ccp-slider-ready') !== '1' && candidates.indexOf(nodes[i]) === -1) {
                candidates.push(nodes[i]);
            }
        }

        // Measure every new slider before any of them mutates layout. This avoids
        // read-after-write forced reflows when several sliders are initialized together.
        var widths = [];
        for (var m = 0; m < candidates.length; m++) {
            var viewport = candidates[m].querySelector('.ccp-slider__viewport');
            widths[m] = viewport ? viewport.clientWidth : 0;
        }
        for (var n = 0; n < candidates.length; n++) {
            init(candidates[n], widths[n]);
        }
    }

    window.CodeCartSlider = {
        init: init,
        scan: scan,
        get: function (root) { return registry ? registry.get(root) : null; }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(document); });
    } else {
        scan(document);
    }

    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(function (mutations) {
            prune();
            for (var i = 0; i < mutations.length; i++) {
                for (var j = 0; j < mutations[i].addedNodes.length; j++) {
                    var node = mutations[i].addedNodes[j];
                    if (node && node.nodeType === 1) {
                        scan(node);
                    }
                }
            }
        }).observe(document.documentElement, {childList: true, subtree: true});
    }
})(window, document);
