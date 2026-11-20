/* Unified storefront feedback. No changes to the underlying OpenCart AJAX requests. */
(function (window, document, $) {
    'use strict';
    if (!window.CodeCartUI || !$) { return; }
    var ui = window.CodeCartUI;
    var current = null;
    var closeTimer = 0;
    var removeTimer = 0;
    var lastKey = '';
    var lastAt = 0;
    var sequence = 0;
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function label(key, fallback) {
        var values = window.CodeCartI18n || {};
        return typeof values[key] === 'string' && values[key] ? values[key] : fallback;
    }
    function closeLabel() {
        return label('ui_close', 'Close');
    }
    function messageText(input) {
        // Parse server-provided OpenCart markup in an inert document; never inject it.
        var markup = String(input == null ? '' : input);
        var parsed = new window.DOMParser().parseFromString(markup, 'text/html');
        var text = parsed.body ? parsed.body.textContent || '' : '';
        return text.replace(/\s+/g, ' ').trim();
    }
    function host() {
        var el = document.getElementById('ccp-toast-host');
        if (!el) {
            el = document.createElement('div');
            el.id = 'ccp-toast-host';
            document.body.appendChild(el);
        }
        el.setAttribute('aria-live', 'off');
        el.setAttribute('aria-atomic', 'true');
        return el;
    }
    function dismiss(node) {
        if (!node || node !== current) { return; }
        window.clearTimeout(closeTimer);
        window.clearTimeout(removeTimer);
        node.classList.remove('is-visible');
        current = null;
        removeTimer = window.setTimeout(function () {
            if (node.parentNode) { node.parentNode.removeChild(node); }
        }, reducedMotion ? 0 : 170);
    }
    function show(type, html, options) {
        if (!document.body) { return; }
        options = options || {};
        var kind = /^(success|info|warning|danger)$/.test(type) ? type : 'info';
        var content = messageText(html);
        if (!content) { return; }
        var key = kind + ':' + content;
        var now = Date.now();
        if (key === lastKey && now - lastAt < 700) { return current; }
        lastKey = key;
        lastAt = now;
        sequence += 1;
        window.clearTimeout(closeTimer);
        window.clearTimeout(removeTimer);
        // Also discard a toast that is still in its exit animation after Escape.
        var surface = host();
        while (surface.children.length) {
            surface.removeChild(surface.children[0]);
        }

        var node = document.createElement('div');
        node.className = 'ccp-toast ccp-toast-' + kind;
        node.setAttribute('role', kind === 'danger' || kind === 'warning' ? 'alert' : 'status');
        node.setAttribute('aria-live', kind === 'danger' || kind === 'warning' ? 'assertive' : 'polite');
        node.setAttribute('aria-atomic', 'true');

        var symbol = document.createElement('span');
        symbol.className = 'ccp-feedback-symbol';
        symbol.setAttribute('aria-hidden', 'true');
        var icon = document.createElement('i');
        icon.className = 'fa ' + ({success:'fa-check',info:'fa-info',warning:'fa-exclamation',danger:'fa-exclamation'}[kind]);
        symbol.appendChild(icon);
        node.appendChild(symbol);

        var body = document.createElement('div');
        body.className = 'ccp-toast-text';
        body.textContent = content;
        node.appendChild(body);

        var close = document.createElement('button');
        close.className = 'ccp-toast-close';
        close.type = 'button';
        close.setAttribute('aria-label', closeLabel());
        close.innerHTML = '&times;';
        close.addEventListener('click', function () { dismiss(node); });
        node.appendChild(close);

        current = node;
        host().appendChild(node);
        window.requestAnimationFrame(function () {
            if (node === current) { node.classList.add('is-visible'); }
        });
        var duration = Number(options.duration);
        if (!isFinite(duration) || duration < 0) {
            duration = kind === 'success' ? 1700 : kind === 'info' ? 3000 : 7000;
        }
        // Keep warnings and errors readable. Successes stay deliberately brief.
        if (kind === 'warning' || kind === 'danger') { duration = Math.max(6000, duration); }
        if (duration > 0) {
            var ticket = sequence;
            closeTimer = window.setTimeout(function () {
                if (ticket === sequence) { dismiss(node); }
            }, duration);
        }
        return node;
    }
    function error(xhr) {
        // Preserve OpenCart's button recovery after an AJAX failure.
        $('.btn:disabled').each(function () {
            var button = $(this);
            if (button.data('resetText') !== undefined) { button.button('reset'); }
        });
        var json = xhr && xhr.responseJSON;
        if (!json && xhr && xhr.responseText) {
            try { json = JSON.parse(xhr.responseText); } catch (ignored) { json = null; }
        }
        var value = json && (json.error || json.message || json.warning);
        if (value && typeof value === 'object') {
            value = typeof value.warning === 'string' ? value.warning : Object.keys(value).map(function (key) { return value[key]; }).filter(function (item) { return typeof item === 'string'; })[0];
        }
        show('danger', typeof value === 'string' && value ? value :
            label('ui_request_failed', 'The request could not be completed. Please try again.'));
    }
    function convertAlerts(scope) {
        var $scope = scope ? $(scope) : $(document);
        var $alerts = $scope.find('.alert-dismissible');
        if ($scope.is && $scope.is('.alert-dismissible')) { $alerts = $alerts.add($scope); }
        $alerts.each(function () {
            var $alert = $(this);
            // Inline validation and modal/checkout instructions must remain visible.
            if ($alert.closest('.modal, form, #accordion .panel-body, #checkout-checkout, .checkout-container').length ||
                $alert.attr('data-no-toast') === '1') { return; }
            var $copy = $alert.clone();
            $copy.find('.close, button[data-dismiss="alert"]').remove();
            var msg = $.trim($copy.text());
            if (!msg) { return; }
            var kind = $alert.hasClass('alert-danger') ? 'danger' :
                $alert.hasClass('alert-warning') ? 'warning' :
                $alert.hasClass('alert-success') ? 'success' : 'info';
            show(kind, msg);
            $alert.remove();
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && current) { dismiss(current); }
    });
    // Keep legacy integrations that invoke CodeCartUI.toast working without duplicate alerts.
    ui.toast = show;
    ui.error = error;
    ui.convertAlerts = convertAlerts;
    $(function () { convertAlerts(document); });
})(window, document, window.jQuery);
