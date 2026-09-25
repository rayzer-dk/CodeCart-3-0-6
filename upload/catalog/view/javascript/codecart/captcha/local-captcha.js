(function () {
    'use strict';

    function encodeForm(data) {
        var pairs = [];
        Object.keys(data).forEach(function (key) {
            pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(String(data[key])));
        });
        return pairs.join('&');
    }

    function withVisual(url, forceVisual, oldNonce) {
        if (!url) { return ''; }
        var extra = 'visual=' + (forceVisual ? '1' : '0');
        if (oldNonce) { extra += '&old_nonce=' + encodeURIComponent(oldNonce); }
        return url + (url.indexOf('?') === -1 ? '?' : '&') + extra + '&_=' + Date.now();
    }

    function init(root) {
        if (!root || root.dataset.ccpCaptchaReady === '1') { return; }
        root.dataset.ccpCaptchaReady = '1';

        var hidden = root.querySelector('input[name="captcha"]');
        var hiddenNonce = root.querySelector('input[name="captcha_nonce"]');
        var honeypot = root.querySelector('.ccp-captcha-honeypot input');
        var human = root.querySelector('.ccp-human-check');
        var slider = root.querySelector('.ccp-human-slider');
        var handle = root.querySelector('.ccp-human-slider__handle');
        var fill = root.querySelector('.ccp-human-slider__fill');
        var sliderText = root.querySelector('.ccp-human-slider__text');
        var visual = root.querySelector('.ccp-visual-fallback');
        var image = root.querySelector('.ccp-local-captcha__image');
        var prompt = root.querySelector('.ccp-local-captcha__prompt-text');
        var choices = root.querySelectorAll('.ccp-local-captcha__choice');
        var refresh = root.querySelector('.ccp-local-captcha__refresh');
        var visualSwitch = root.querySelector('.ccp-human-check__visual-switch');
        var visualStatus = root.querySelector('.ccp-local-captcha__status');
        var humanStatus = root.querySelector('.ccp-human-check__status');
        var mode = root.getAttribute('data-mode') || 'adaptive';
        var nonce = root.getAttribute('data-nonce') || '';
        var expiresAt = parseInt(root.getAttribute('data-expires-at') || '0', 10) || 0;
        var dragging = false;
        var startX = 0;
        var startAt = 0;
        var steps = 0;
        var busy = false;
        var expiryTimer = null;
        var incompleteDrags = 0;
        var maxAttempts = Math.max(1, parseInt(root.getAttribute('data-max-attempts') || '5', 10) || 5);
        var originalSliderText = sliderText ? sliderText.textContent : '';
        var originalHandleHtml = handle ? handle.innerHTML : '';

        function setStatus(text, ok) {
            if (!humanStatus) { return; }
            humanStatus.textContent = text || '';
            humanStatus.classList.toggle('is-ok', !!ok);
            humanStatus.classList.toggle('is-error', !!text && !ok);
        }

        function resetSliderVisuals() {
            dragging = false;
            root.classList.remove('is-verified');
            if (hidden) { hidden.value = ''; }
            if (handle) { handle.style.transform = ''; handle.innerHTML = originalHandleHtml; }
            if (fill) { fill.style.width = ''; }
            if (sliderText) { sliderText.textContent = originalSliderText; }
            if (slider) {
                slider.classList.remove('is-dragging', 'is-checking', 'is-verified');
                slider.removeAttribute('aria-disabled');
            }
            setStatus('', false);
        }

        function showAdaptive() {
            if (mode === 'visual') { return; }
            if (human) { human.classList.remove('is-hidden'); }
            if (visual) { visual.classList.add('is-hidden'); }
            root.classList.remove('is-visual-mode');
            resetSliderVisuals();
        }

        function showVisual(message) {
            if (human) { human.classList.add('is-hidden'); }
            if (visual) { visual.classList.remove('is-hidden'); }
            root.classList.add('is-visual-mode');
            if (image && !image.getAttribute('src') && !busy) {
                refreshChallenge(true, true);
            }
            if (visualStatus) { visualStatus.textContent = message || ''; }
            resetSliderVisuals();
        }

        function selectedIndexes() {
            var selected = [];
            for (var i = 0; i < choices.length; i++) {
                var box = choices[i].querySelector('input[type="checkbox"]');
                if ((box && box.checked) || choices[i].classList.contains('is-selected')) {
                    selected.push(parseInt(choices[i].getAttribute('data-index'), 10));
                }
            }
            selected.sort(function (a, b) { return a - b; });
            return selected;
        }

        function syncSelection() {
            var selected = selectedIndexes();
            if (hidden) { hidden.value = selected.length ? 'v:' + nonce + ':' + selected.join(',') : ''; }
            if (visualStatus) {
                var template = root.getAttribute('data-selected-text') || '';
                visualStatus.textContent = selected.length ? template.replace('%d', String(selected.length)) : '';
            }
        }

        function clearSelection() {
            for (var i = 0; i < choices.length; i++) {
                var box = choices[i].querySelector('input[type="checkbox"]');
                if (box) { box.checked = false; }
                choices[i].classList.remove('is-selected');
            }
            syncSelection();
        }

        function scheduleExpiry() {
            if (expiryTimer) { window.clearTimeout(expiryTimer); expiryTimer = null; }
            if (!expiresAt) { return; }
            var delay = Math.max(250, expiresAt * 1000 - Date.now() + 250);
            expiryTimer = window.setTimeout(function () {
                resetSliderVisuals();
                clearSelection();
                refreshChallenge(mode === 'visual', true);
            }, delay);
        }

        function applyFreshChallenge(data, forceVisual) {
            if (!data || !data.nonce) { throw new Error('Invalid CAPTCHA response'); }
            nonce = String(data.nonce);
            expiresAt = parseInt(data.expires_at || '0', 10) || 0;
            root.setAttribute('data-nonce', nonce);
            if (hiddenNonce) { hiddenNonce.value = nonce; }
            root.setAttribute('data-expires-at', String(expiresAt));
            var imageUrl = data.image_url || data.image || '';
            if (imageUrl && image) {
                if (forceVisual || mode === 'visual') { image.src = String(imageUrl); }
                else { image.removeAttribute('src'); }
            }
            if (prompt && data.prompt) { prompt.textContent = String(data.prompt); }
            if (honeypot && data.honeypot) { honeypot.name = String(data.honeypot); honeypot.value = ''; }
            incompleteDrags = 0;
            clearSelection();
            resetSliderVisuals();
            if (forceVisual || mode === 'visual') { showVisual(''); } else { showAdaptive(); }
            scheduleExpiry();
        }

        function refreshChallenge(forceVisual, silent) {
            var url = root.getAttribute('data-refresh-url') || '';
            if (!url || busy) { return Promise.resolve(false); }
            busy = true;
            if (refresh) { refresh.disabled = true; }
            return fetch(withVisual(url, !!forceVisual, nonce), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                return response.json();
            }).then(function (data) {
                applyFreshChallenge(data, !!forceVisual);
                return true;
            }).catch(function () {
                if (!silent) {
                    var message = root.getAttribute('data-refresh-error') || '';
                    if (forceVisual && visualStatus) { visualStatus.textContent = message; }
                    else { setStatus(message, false); }
                }
                return false;
            }).then(function (result) {
                busy = false;
                if (refresh) { refresh.disabled = false; }
                return result;
            });
        }

        function completeAdaptive(method, duration, eventSteps) {
            if (busy || !nonce) { return; }
            busy = true;
            if (slider) { slider.classList.add('is-checking'); }
            var url = root.getAttribute('data-verify-url') || '';
            var body = encodeForm({
                nonce: nonce,
                honeypot: honeypot ? honeypot.value : '',
                method: method,
                duration: Math.max(0, duration || 0),
                steps: Math.max(0, eventSteps || 0)
            });
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest'},
                body: body
            }).then(function (response) {
                return response.json().catch(function () { return null; }).then(function (data) { return {ok: response.ok, data: data}; });
            }).then(function (result) {
                var data = result.data || {};
                if (result.ok && data.success && data.token) {
                    if (hidden) { hidden.value = String(data.token); }
                    if (data.expires_at) {
                        expiresAt = parseInt(data.expires_at, 10) || expiresAt;
                        root.setAttribute('data-expires-at', String(expiresAt));
                        scheduleExpiry();
                    }
                    root.classList.add('is-verified');
                    if (slider) {
                        slider.classList.remove('is-checking');
                        slider.classList.add('is-verified');
                        slider.setAttribute('aria-disabled', 'true');
                    }
                    if (handle) {
                        handle.style.transform = 'translateX(var(--ccp-captcha-slide-max, 0px))';
                        handle.innerHTML = '<i class="fa-solid fa-check"></i>';
                        incompleteDrags = 0;
                    }
                    if (fill) { fill.style.width = '100%'; }
                    if (sliderText) { sliderText.textContent = data.message || root.getAttribute('data-verified-text') || ''; }
                    setStatus('', true);
                    return;
                }
                var message = data.message || root.getAttribute('data-verify-error') || '';
                if (data.fallback !== false) { showVisual(message); }
                else { setStatus(message, false); resetSliderVisuals(); }
            }).catch(function () {
                var message = root.getAttribute('data-verify-error') || '';
                setStatus(message, false);
                resetSliderVisuals();
                return refreshChallenge(true, true).then(function (ok) {
                    if (!ok) { showVisual(message); }
                });
            }).then(function () {
                busy = false;
                if (slider) { slider.classList.remove('is-checking'); }
            });
        }

        function sliderMax() {
            if (!slider || !handle) { return 0; }
            return Math.max(0, slider.clientWidth - handle.offsetWidth - 6);
        }

        function moveSlider(clientX) {
            if (!dragging || !slider || !handle || root.classList.contains('is-verified')) { return; }
            steps++;
            var max = sliderMax();
            var x = Math.max(0, Math.min(max, clientX - startX));
            handle.style.transform = 'translateX(' + x + 'px)';
            if (fill) { fill.style.width = (x + handle.offsetWidth) + 'px'; }
        }

        function endSlider(clientX, method) {
            if (!dragging) { return; }
            moveSlider(clientX);
            dragging = false;
            if (slider) { slider.classList.remove('is-dragging'); }
            var max = sliderMax();
            var matrix = window.getComputedStyle(handle).transform;
            var x = 0;
            if (matrix && matrix !== 'none') {
                var parts = matrix.match(/matrix\([^,]+,[^,]+,[^,]+,[^,]+,\s*([^,]+)/);
                if (parts) { x = parseFloat(parts[1]) || 0; }
            }
            var duration = Date.now() - startAt;
            if (max > 0 && x >= max * 0.9) {
                root.style.setProperty('--ccp-captcha-slide-max', max + 'px');
                completeAdaptive(method, duration, steps);
            } else {
                incompleteDrags++;
                resetSliderVisuals();
                if (incompleteDrags >= maxAttempts) {
                    incompleteDrags = 0;
                    refreshChallenge(true, true);
                }
            }
        }

        if (slider && !root.classList.contains('is-visual-mode')) {
            slider.addEventListener('pointerdown', function (e) {
                if (busy || root.classList.contains('is-verified')) { return; }
                dragging = true;
                startX = e.clientX;
                startAt = Date.now();
                steps = 0;
                slider.classList.add('is-dragging');
                if (slider.setPointerCapture && e.pointerId !== undefined) {
                    try { slider.setPointerCapture(e.pointerId); } catch (ignore) {}
                }
                e.preventDefault();
            });
            slider.addEventListener('pointermove', function (e) { moveSlider(e.clientX); });
            slider.addEventListener('pointerup', function (e) { endSlider(e.clientX, e.pointerType === 'touch' ? 'touch' : 'pointer'); });
            slider.addEventListener('pointercancel', function () {
                if (dragging) {
                    incompleteDrags++;
                    resetSliderVisuals();
                    if (incompleteDrags >= maxAttempts) { incompleteDrags = 0; refreshChallenge(true, true); }
                }
            });
            slider.addEventListener('keydown', function (e) {
                if ((e.key === 'Enter' || e.key === ' ') && !busy && !root.classList.contains('is-verified')) {
                    e.preventDefault();
                    completeAdaptive('keyboard', 0, 0);
                }
            });
        }

        for (var i = 0; i < choices.length; i++) {
            choices[i].addEventListener('change', function () {
                var box = this.querySelector('input[type="checkbox"]');
                this.classList.toggle('is-selected', !!(box && box.checked));
                syncSelection();
            });
        }

        if (visualSwitch) {
            visualSwitch.addEventListener('click', function () {
                refreshChallenge(true, false).then(function (ok) {
                    if (!ok) { showVisual(root.getAttribute('data-refresh-error') || ''); }
                });
            });
        }

        if (refresh) {
            refresh.addEventListener('click', function () { refreshChallenge(true, false); });
        }

        root._ccpCaptchaReset = function (forceVisual) { resetSliderVisuals(); clearSelection(); incompleteDrags = 0; return refreshChallenge(!!forceVisual || mode === 'visual', true); };
        scheduleExpiry();
    }

    function initAll(scope) {
        var nodes = (scope || document).querySelectorAll('.ccp-local-captcha');
        for (var i = 0; i < nodes.length; i++) { init(nodes[i]); }
    }

    window.CodeCartCaptcha = window.CodeCartCaptcha || {};
    window.CodeCartCaptcha.resetAll = function (scope, forceVisual) {
        var nodes = (scope || document).querySelectorAll('.ccp-local-captcha');
        for (var i = 0; i < nodes.length; i++) {
            init(nodes[i]);
            if (typeof nodes[i]._ccpCaptchaReset === 'function') { nodes[i]._ccpCaptchaReset(!!forceVisual); }
        }
    };
    window.CodeCartCaptcha.initAll = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else {
        initAll(document);
    }
}());
