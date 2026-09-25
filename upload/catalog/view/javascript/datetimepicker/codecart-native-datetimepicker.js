/*
 * CodeCart PRO Hybrid Date/Time Picker compatibility layer
 * CodeCart PRO Hybrid DateTimePicker v1.8.7
 * CodeCart PRO 3.0.6.0
 *
 * Replaces the legacy Bootstrap DateTimePicker + Moment.js runtime in core
 * pages while preserving OpenCart 3's $.fn.datetimepicker() entry point.
 * The actual picker is the browser's native HTML5 date/time control.
 */
(function (window, document) {
    'use strict';

    var STATE_KEY = '__codecartNativeDateTimePicker';
    var VERSION = '1.8.7';
    var LEGACY_COMPATIBILITY = '3.1.3';

    function getInput(element) {
        if (!element) {
            return null;
        }

        if (element.tagName && element.tagName.toLowerCase() === 'input') {
            return element;
        }

        return element.querySelector('input');
    }

    function detectMode(element, input, options) {
        options = options || {};

        if (options.pickDate === false) {
            return 'time';
        }

        if (options.pickTime === false) {
            return 'date';
        }

        if (element.classList) {
            if (element.classList.contains('time')) {
                return 'time';
            }
            if (element.classList.contains('datetime')) {
                return 'datetime-local';
            }
            if (element.classList.contains('date')) {
                return 'date';
            }
        }

        var format = input.getAttribute('data-date-format') || options.format || '';
        if (/H|h|m|s/.test(format) && /Y|M|D/.test(format)) {
            return 'datetime-local';
        }
        if (/H|h|m|s/.test(format)) {
            return 'time';
        }

        return 'date';
    }

    function normalizeForNative(value, mode) {
        value = String(value || '').trim();
        if (!value || value === '0000-00-00' || value === '0000-00-00 00:00:00') {
            return '';
        }

        if (mode === 'date') {
            var dateMatch = value.match(/^(\d{4}-\d{2}-\d{2})/);
            return dateMatch ? dateMatch[1] : '';
        }

        if (mode === 'time') {
            var timeMatch = value.match(/^(\d{2}:\d{2})(:\d{2})?/);
            return timeMatch ? timeMatch[1] + (timeMatch[2] || '') : '';
        }

        var dateTimeMatch = value.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2})?/);
        return dateTimeMatch ? dateTimeMatch[1] + 'T' + dateTimeMatch[2] + (dateTimeMatch[3] || '') : '';
    }

    function toCanonical(value, mode) {
        value = String(value || '');
        return mode === 'datetime-local' ? value.replace('T', ' ') : value;
    }

    function fromDate(value, mode, withSeconds) {
        if (!(value instanceof Date) || isNaN(value.getTime())) {
            return String(value == null ? '' : value);
        }

        function pad(number) {
            return String(number).padStart(2, '0');
        }

        var date = value.getFullYear() + '-' + pad(value.getMonth() + 1) + '-' + pad(value.getDate());
        var time = pad(value.getHours()) + ':' + pad(value.getMinutes()) + (withSeconds ? ':' + pad(value.getSeconds()) : '');

        if (mode === 'date') {
            return date;
        }
        if (mode === 'time') {
            return time;
        }
        return date + ' ' + time;
    }

    function normalizeLanguage(language) {
        var lang = String(language || document.documentElement.getAttribute('lang') || 'en').toLowerCase().replace('_', '-');
        if (lang === 'ua' || lang.indexOf('uk') === 0) { return 'uk'; }
        if (lang.indexOf('ru') === 0) { return 'ru'; }
        if (lang.indexOf('en') === 0) { return 'en'; }
        return lang.split('-')[0] || 'en';
    }

    function pickerTitle(mode, language) {
        var lang = normalizeLanguage(language);
        var texts = {
            uk: {date: 'Вибрати дату', time: 'Вибрати час', datetime: 'Вибрати дату й час'},
            ru: {date: 'Выбрать дату', time: 'Выбрать время', datetime: 'Выбрать дату и время'},
            en: {date: 'Choose date', time: 'Choose time', datetime: 'Choose date and time'}
        };
        var locale = lang.indexOf('uk') === 0 ? texts.uk : (lang.indexOf('ru') === 0 ? texts.ru : texts.en);
        return mode === 'date' ? locale.date : (mode === 'time' ? locale.time : locale.datetime);
    }

    function pickerHint(mode, language, withSeconds) {
        if (mode !== 'datetime-local') {
            return '';
        }

        var lang = normalizeLanguage(language);
        if (lang === 'uk') {
            return withSeconds ? 'Дата • Час (год:хв:сек)' : 'Дата • Час (год:хв)';
        }
        if (lang === 'ru') {
            return withSeconds ? 'Дата • Время (ч:мин:сек)' : 'Дата • Время (ч:мин)';
        }
        return withSeconds ? 'Date • Time (hh:mm:ss)' : 'Date • Time (hh:mm)';
    }

    function openPicker(input) {
        if (!input || input.disabled) {
            return;
        }

        try {
            if (typeof input.showPicker === 'function') {
                input.showPicker();
                return;
            }
        } catch (error) {
            // Browsers may reject showPicker() when it is not triggered by a user gesture.
        }

        input.focus();
        input.click();
    }

    function init(element, options) {
        if (!element) {
            return null;
        }

        if (element[STATE_KEY]) {
            return element[STATE_KEY].api;
        }

        var source = getInput(element);
        if (!source) {
            return null;
        }

        var mode = detectMode(element, source, options);
        var format = source.getAttribute('data-date-format') || (options && options.format) || '';
        var withSeconds = /ss/.test(format) || /:\d{2}$/.test(String(source.value || ''));
        var visible = document.createElement('input');
        var originalType = source.getAttribute('type') || 'text';
        var originalDisplay = source.style.display;

        visible.type = mode;
        visible.className = source.className || 'form-control';
        visible.lang = normalizeLanguage(options && options.language);
        visible.classList.add('codecart-native-datetimepicker');
        visible.value = normalizeForNative(source.value, mode);
        visible.disabled = source.disabled;
        visible.readOnly = source.readOnly;
        visible.required = source.required;
        visible.autocomplete = 'off';

        if (source.getAttribute('placeholder')) {
            visible.setAttribute('placeholder', source.getAttribute('placeholder'));
        }

        if (source.id) {
            var labels = document.getElementsByTagName('label');
            for (var labelIndex = 0; labelIndex < labels.length; labelIndex++) {
                if (labels[labelIndex].htmlFor !== source.id) {
                    continue;
                }
                if (!labels[labelIndex].id) {
                    labels[labelIndex].id = source.id + '-label';
                }
                visible.setAttribute('aria-labelledby', labels[labelIndex].id);
                labels[labelIndex].addEventListener('click', function (event) {
                    event.preventDefault();
                    visible.focus();
                });
                break;
            }
        }
        if (source.getAttribute('min')) {
            visible.setAttribute('min', normalizeForNative(source.getAttribute('min'), mode));
        }
        if (source.getAttribute('max')) {
            visible.setAttribute('max', normalizeForNative(source.getAttribute('max'), mode));
        }
        if ((mode === 'time' || mode === 'datetime-local') && withSeconds) {
            visible.step = '1';
        } else if (mode === 'time' || mode === 'datetime-local') {
            visible.step = '60';
        }

        source.type = 'hidden';
        source.style.display = 'none';
        source.classList.add('codecart-datetimepicker-source');
        source.parentNode.insertBefore(visible, source);

        var title = pickerTitle(mode, options && options.language);
        visible.setAttribute('title', title);
        visible.setAttribute('aria-label', title);

        var hint = null;
        var hintText = pickerHint(mode, options && options.language, withSeconds);
        if (hintText && element.parentNode) {
            hint = document.createElement('div');
            hint.className = 'codecart-datetime-hint';
            hint.textContent = hintText;
            element.parentNode.insertBefore(hint, element.nextSibling);
        }

        function syncToSource(triggerChange) {
            source.value = toCanonical(visible.value, mode);
            if (triggerChange) {
                source.dispatchEvent(new Event('input', {bubbles: true}));
                source.dispatchEvent(new Event('change', {bubbles: true}));
            }
        }

        function syncFromSource() {
            visible.value = normalizeForNative(source.value, mode);
        }

        visible.addEventListener('input', function () {
            syncToSource(false);
        });
        visible.addEventListener('change', function () {
            syncToSource(true);
        });

        var button = element.querySelector ? element.querySelector('.input-group-btn button, .datepickerbutton, button') : null;
        if (button) {
            if (!button.getAttribute('title')) {
                button.setAttribute('title', title);
            }
            if (!button.getAttribute('aria-label')) {
                button.setAttribute('aria-label', title);
            }
            button.addEventListener('click', function (event) {
                event.preventDefault();
                openPicker(visible);
            });
        }

        if (source.form) {
            source.form.addEventListener('reset', function () {
                window.setTimeout(syncFromSource, 0);
            });
        }

        var api = {
            show: function () {
                openPicker(visible);
                return api;
            },
            hide: function () {
                visible.blur();
                return api;
            },
            enable: function () {
                source.disabled = false;
                visible.disabled = false;
                return api;
            },
            disable: function () {
                source.disabled = true;
                visible.disabled = true;
                return api;
            },
            getDate: function () {
                return source.value || null;
            },
            setDate: function (value) {
                source.value = fromDate(value, mode, withSeconds);
                syncFromSource();
                return api;
            },
            date: function (value) {
                if (arguments.length === 0) {
                    return source.value || null;
                }
                return api.setDate(value);
            },
            setMinDate: function (value) {
                var normalized = normalizeForNative(fromDate(value, mode, withSeconds), mode);
                if (normalized) {
                    visible.min = normalized;
                } else {
                    visible.removeAttribute('min');
                }
                return api;
            },
            setMaxDate: function (value) {
                var normalized = normalizeForNative(fromDate(value, mode, withSeconds), mode);
                if (normalized) {
                    visible.max = normalized;
                } else {
                    visible.removeAttribute('max');
                }
                return api;
            },
            destroy: function () {
                source.type = originalType;
                source.style.display = originalDisplay;
                source.classList.remove('codecart-datetimepicker-source');
                if (visible.parentNode) {
                    visible.parentNode.removeChild(visible);
                }
                if (hint && hint.parentNode) {
                    hint.parentNode.removeChild(hint);
                }
                delete element[STATE_KEY];
                if (window.jQuery) {
                    window.jQuery(element).removeData('DateTimePicker');
                }
            },
            input: visible,
            source: source
        };

        element[STATE_KEY] = {
            api: api,
            mode: mode,
            source: source,
            visible: visible,
            hint: hint
        };

        if (window.jQuery) {
            window.jQuery(element).data('DateTimePicker', api);
        }

        return api;
    }

    function supportsNativeType(type) {
        var input = document.createElement('input');
        input.setAttribute('type', type);
        return input.type === type;
    }

    function prefersNativePicker() {
        return false;
    }

    function initAll(root) {
        root = root || document;
        if (!prefersNativePicker()) {
            return;
        }
        if (!root.querySelectorAll) {
            return;
        }

        var elements = root.querySelectorAll('.input-group.date, .input-group.time, .input-group.datetime');
        for (var i = 0; i < elements.length; i++) {
            init(elements[i], {});
        }
    }

    var legacyState = {loading: false, loaded: false, callbacks: [], plugin: null, defaults: null};

    function assetBase() {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            var src = scripts[i].getAttribute('src') || '';
            if (src.indexOf('codecart-native-datetimepicker.js') !== -1) {
                return src.substring(0, src.lastIndexOf('/') + 1);
            }
        }
        return 'view/javascript/datetimepicker/';
    }

    function addStyle(href) {
        var links = document.getElementsByTagName('link');
        for (var i = 0; i < links.length; i++) {
            if ((links[i].getAttribute('href') || '').indexOf(href) !== -1) {
                return;
            }
        }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    }

    function addScript(src, callback) {
        var script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = function () { callback(null); };
        script.onerror = function () { callback(new Error('Unable to load ' + src)); };
        document.head.appendChild(script);
    }

    function flushLegacyCallbacks(error) {
        var callbacks = legacyState.callbacks.slice();
        legacyState.callbacks = [];
        for (var i = 0; i < callbacks.length; i++) {
            try { callbacks[i](error); } catch (callbackError) { window.setTimeout(function () { throw callbackError; }, 0); }
        }
    }

    function captureLegacyPlugin(plugin) {
        if (typeof plugin !== 'function' || plugin === bridgePlugin) {
            return false;
        }

        legacyState.plugin = plugin;
        legacyState.loaded = true;

        // Bootstrap DateTimePicker 3.1.3.1 reads $.fn.datetimepicker.defaults
        // dynamically inside its constructor. Preserve the complete original
        // defaults object before the CodeCart PRO bridge takes ownership of the
        // public jQuery entry point; otherwise keys such as disabledDates and
        // enabledDates become undefined and the legacy constructor aborts.
        if (plugin.defaults && typeof plugin.defaults === 'object') {
            legacyState.defaults = plugin.defaults;
        }

        return true;
    }

    function getLegacyDefaults() {
        if (legacyState.defaults && typeof legacyState.defaults === 'object') {
            return legacyState.defaults;
        }

        if (legacyState.plugin && legacyState.plugin.defaults && typeof legacyState.plugin.defaults === 'object') {
            legacyState.defaults = legacyState.plugin.defaults;
            return legacyState.defaults;
        }

        // Safe compatibility fallback. These are the complete default keys
        // expected by Bootstrap DateTimePicker 3.1.3.1. Moment values are
        // created only when Moment is available.
        var defaults = {
            format: false,
            pickDate: true,
            pickTime: true,
            useMinutes: true,
            useSeconds: false,
            useCurrent: true,
            calendarWeeks: false,
            minuteStepping: 1,
            minDate: false,
            maxDate: false,
            showToday: true,
            collapse: false,
            language: (typeof window.moment === 'function' && window.moment.locale) ? window.moment.locale() : 'en',
            defaultDate: '',
            disabledDates: false,
            enabledDates: false,
            icons: {},
            useStrict: false,
            direction: 'auto',
            sideBySide: false,
            daysOfWeekDisabled: [],
            widgetParent: false
        };

        if (typeof window.moment === 'function') {
            defaults.minDate = window.moment({y: 1900});
            defaults.maxDate = window.moment().add(100, 'y');
        }

        legacyState.defaults = defaults;
        return defaults;
    }

    function loadLegacy(callback) {
        if (!window.jQuery || !window.jQuery.fn) {
            callback(new Error('jQuery is required by the legacy DateTimePicker.'));
            return;
        }

        var base = assetBase() + '../jquery/datetimepicker/';
        addStyle(base + 'bootstrap-datetimepicker.min.css');
        addStyle(assetBase() + 'codecart-datetimepicker-modern.css?v=1.8.7');

        // Preserve one deterministic legacy implementation behind the CodeCart PRO bridge.
        // bootstrap-datetimepicker replaces $.fn.datetimepicker when it loads; without
        // capturing it and restoring the bridge, later fields bypass CodeCart PRO styling,
        // locale labels and positioning depending on load order.
        if (legacyState.loaded && typeof legacyState.plugin === 'function') {
            installJqueryCompatibility();
            callback(null);
            return;
        }

        var existing = window.jQuery.fn.datetimepicker;
        if (captureLegacyPlugin(existing)) {
            installJqueryCompatibility();
            callback(null);
            return;
        }

        legacyState.callbacks.push(callback);
        if (legacyState.loading) {
            return;
        }
        legacyState.loading = true;

        function loadPlugin() {
            addScript(base + 'bootstrap-datetimepicker.min.js', function (error) {
                legacyState.loading = false;
                var plugin = !error && window.jQuery && window.jQuery.fn &&
                    typeof window.jQuery.fn.datetimepicker === 'function' &&
                    window.jQuery.fn.datetimepicker !== bridgePlugin
                    ? window.jQuery.fn.datetimepicker
                    : null;

                if (plugin) {
                    captureLegacyPlugin(plugin);
                    installJqueryCompatibility();
                    flushLegacyCallbacks(null);
                    return;
                }

                legacyState.loaded = false;
                legacyState.plugin = null;
                installJqueryCompatibility();
                flushLegacyCallbacks(error || new Error('Legacy DateTimePicker did not register.'));
            });
        }

        if (typeof window.moment === 'function') {
            loadPlugin();
        } else {
            addScript(base + 'moment/moment-with-locales.min.js', function (error) {
                if (error) {
                    legacyState.loading = false;
                    installJqueryCompatibility();
                    flushLegacyCallbacks(error);
                    return;
                }
                loadPlugin();
            });
        }
    }

    function requiresLegacy(option) {
        if (typeof option === 'string') {
            return ['show','hide','enable','disable','getDate','setDate','date','setMinDate','setMaxDate','destroy'].indexOf(option) === -1;
        }
        if (!option || typeof option !== 'object') {
            return false;
        }
        var nativeKeys = {
            pickDate: true, pickTime: true, language: true, format: true,
            useMinutes: true, useSeconds: true, minuteStepping: true
        };
        for (var key in option) {
            if (Object.prototype.hasOwnProperty.call(option, key) && !nativeKeys[key]) {
                return true;
            }
        }
        return false;
    }

    function destroyNative(element) {
        if (element && element[STATE_KEY] && element[STATE_KEY].api) {
            element[STATE_KEY].api.destroy();
        }
    }

    function nativeFallback(collection, option, args) {
        var returnValue = collection;
        collection.each(function () {
            var api = init(this, typeof option === 'object' ? option : {});
            if (!api || typeof option !== 'string') {
                return;
            }
            if (typeof api[option] === 'function') {
                var result = api[option].apply(api, args || []);
                if (result !== api && result !== undefined) {
                    returnValue = result;
                }
            }
        });
        return returnValue;
    }

    function invokeLegacyCommand(collection, command, args) {
        var returnValue = collection;
        args = args || [];

        collection.each(function () {
            var $element = window.jQuery(this);
            var api = $element.data('DateTimePicker');
            if (!api) {
                return;
            }

            var method = command;
            if (command === 'date') {
                method = args.length ? 'setDate' : 'getDate';
            }

            if (typeof api[method] !== 'function') {
                return;
            }

            // Bootstrap DateTimePicker 3.1.3.1 implements show() as a toggle.
            // Keep compatibility commands idempotent so duplicate integrations
            // cannot open and immediately close the widget in one user action.
            if (command === 'show' && api.widget && api.widget.hasClass && api.widget.hasClass('picker-open')) {
                return;
            }
            if (command === 'hide' && api.widget && api.widget.hasClass && !api.widget.hasClass('picker-open')) {
                return;
            }

            var result = api[method].apply(api, args);
            if ((command === 'getDate' || (command === 'date' && !args.length)) && result !== undefined) {
                returnValue = result;
            }
        });

        return returnValue;
    }

    function positionLegacyPicker($source, $widget) {
        if (!$source || !$source.length || !$widget || !$widget.length || window.innerWidth <= 767) { return; }

        var source = $source.get(0);
        if (!source || !source.getBoundingClientRect) { return; }

        var rect = source.getBoundingClientRect();
        var width = Math.max(210, Math.ceil($widget.outerWidth() || 258));
        var height = Math.max(100, Math.ceil($widget.outerHeight() || 260));
        var margin = 12;
        var gap = 6;
        var left = rect.right - width;
        var maxLeft = Math.max(margin, window.innerWidth - width - margin);
        left = Math.max(margin, Math.min(left, maxLeft));

        var below = rect.bottom + gap;
        var above = rect.top - height - gap;
        var top = (below + height <= window.innerHeight - margin || above < margin) ? below : above;
        top = Math.max(margin, Math.min(top, Math.max(margin, window.innerHeight - height - margin)));

        $widget.css({
            position: 'fixed',
            top: top + 'px',
            left: left + 'px',
            right: 'auto',
            bottom: 'auto',
            transform: 'none',
            zIndex: 20050
        });
    }

    function decorateLegacyPicker($source) {
        if (!window.jQuery) { return; }
        var $widget = window.jQuery('.bootstrap-datetimepicker-widget:visible').last();
        if (!$widget.length) { return; }

        var lang = normalizeLanguage(document.documentElement.getAttribute('lang') || 'en');
        var labels = lang === 'uk'
            ? {hours: 'Години', minutes: 'Хвилини', seconds: 'Секунди'}
            : (lang === 'ru'
                ? {hours: 'Часы', minutes: 'Минуты', seconds: 'Секунды'}
                : {hours: 'Hours', minutes: 'Minutes', seconds: 'Seconds'});

        var timeOnly = $source && $source.length && $source.hasClass('time') && !$source.hasClass('date') && !$source.hasClass('datetime');
        if (!timeOnly && $source && $source.length) {
            var input = $source.find('input').get(0);
            var type = input ? String(input.getAttribute('data-date-format') || input.getAttribute('type') || '').toLowerCase() : '';
            timeOnly = type === 'time' || (type.indexOf('hh') !== -1 && type.indexOf('yy') === -1 && type.indexOf('dd') === -1);
        }
        $widget.toggleClass('codecart-time-only', !!timeOnly);

        var $picker = $widget.find('.timepicker-picker').first();
        if ($picker.length) {
            // Labels live in the same cells as their values. This keeps Hours/Minutes/Seconds
            // perfectly aligned in time-only, date+time and responsive layouts.
            $picker.children('.ccp-time-headings').remove();
            $picker.find('.ccp-time-label').remove();
            $picker.find('.ccp-time-value-cell').removeClass('ccp-time-value-cell');

            [
                {selector: '.timepicker-hour', label: labels.hours},
                {selector: '.timepicker-minute', label: labels.minutes},
                {selector: '.timepicker-second', label: labels.seconds}
            ].forEach(function (item) {
                var $value = $picker.find(item.selector).first();
                if (!$value.length) { return; }
                var $cell = $value.closest('td');
                if (!$cell.length) { return; }
                $cell.addClass('ccp-time-value-cell');
                window.jQuery('<span class="ccp-time-label" aria-hidden="true"></span>').text(item.label).prependTo($cell);
            });

            $picker.find('td.separator').addClass('ccp-time-separator');
        }

        positionLegacyPicker($source, $widget);
    }

    function bridgePlugin(option) {
        var args = Array.prototype.slice.call(arguments, 1);
        var collection = this;

        var desktopLegacy = !prefersNativePicker();

        if (desktopLegacy || requiresLegacy(option)) {
            collection.each(function () { destroyNative(this); });
            loadLegacy(function (error) {
                if (error) {
                    if (window.console && console.warn) { console.warn('CodeCart PRO DateTimePicker legacy UI unavailable; using native control.', error); }
                    nativeFallback(collection, option, args);
                    return;
                }
                var legacyOption = option;
                if (legacyOption && typeof legacyOption === 'object') {
                    legacyOption = window.jQuery.extend({}, legacyOption);
                    var locale = String(legacyOption.language || document.documentElement.getAttribute('lang') || 'en-gb').toLowerCase().replace('_','-');
                    if (locale.indexOf('uk') === 0 || locale === 'ua') { locale = 'uk'; }
                    else if (locale.indexOf('ru') === 0) { locale = 'ru'; }
                    else if (locale.indexOf('en') === 0) { locale = 'en-gb'; }
                    legacyOption.language = locale;
                    if (legacyOption.pickTime !== false && typeof legacyOption.useMinutes === 'undefined') { legacyOption.useMinutes = true; }
                    if (legacyOption.pickDate !== false && legacyOption.pickTime !== false && typeof legacyOption.sideBySide === 'undefined') { legacyOption.sideBySide = window.innerWidth >= 768; }
                    if (typeof window.moment === 'function' && window.moment.locale) { window.moment.locale(locale); }
                }
                var legacyPlugin = legacyState.plugin;
                if (typeof legacyPlugin !== 'function') {
                    nativeFallback(collection, option, args);
                    return;
                }

                if (typeof legacyOption === 'string') {
                    invokeLegacyCommand(collection, legacyOption, args);
                    installJqueryCompatibility();

                    if (legacyOption === 'show') {
                        window.setTimeout(function () {
                            collection.each(function () { decorateLegacyPicker(window.jQuery(this)); });
                        }, 0);
                        window.setTimeout(function () {
                            collection.each(function () { decorateLegacyPicker(window.jQuery(this)); });
                        }, 60);
                    }
                    return;
                }

                try {
                    legacyPlugin.apply(collection, [legacyOption].concat(args));
                } catch (legacyError) {
                    if (window.console && console.warn) {
                        console.warn('CodeCart PRO DateTimePicker legacy initialization failed; using native control.', legacyError);
                    }
                    collection.each(function () {
                        window.jQuery(this).removeData('DateTimePicker');
                    });
                    nativeFallback(collection, legacyOption, args);
                    installJqueryCompatibility();
                    return;
                }
                // Keep $.fn.datetimepicker owned by the CodeCart PRO bridge after every call.
                installJqueryCompatibility();

                collection.off('.codecartModernPicker').on('dp.show.codecartModernPicker dp.update.codecartModernPicker dp.change.codecartModernPicker', function () {
                    var $source = window.jQuery(this);
                    decorateLegacyPicker($source);
                    window.setTimeout(function () { decorateLegacyPicker($source); }, 0);
                    window.setTimeout(function () { decorateLegacyPicker($source); }, 60);
                });
                window.setTimeout(function () {
                    collection.each(function () { decorateLegacyPicker(window.jQuery(this)); });
                }, 0);
            });
            return collection;
        }

        return nativeFallback(collection, option, args);
    }

    function installJqueryCompatibility() {
        if (!window.jQuery || !window.jQuery.fn) {
            return;
        }

        var currentPlugin = window.jQuery.fn.datetimepicker;
        captureLegacyPlugin(currentPlugin);

        window.jQuery.fn.datetimepicker = bridgePlugin;
        // Keep the original complete defaults on the bridge. The legacy
        // constructor dereferences $.fn.datetimepicker.defaults at runtime,
        // so replacing it with a partial object breaks initialization.
        window.jQuery.fn.datetimepicker.defaults = getLegacyDefaults();
    }

    installJqueryCompatibility();

    function prewarmLegacyPicker() {
        if (prefersNativePicker() || !document.querySelector('.input-group.date, .input-group.time, .input-group.datetime')) {
            return;
        }
        loadLegacy(function (error) {
            if (error && window.console && console.warn) {
                console.warn('CodeCart PRO DateTimePicker prewarm failed; native fallback remains available.', error);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', prewarmLegacyPicker);
    } else {
        prewarmLegacyPicker();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll(document);
        });
    } else {
        initAll(document);
    }

    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                for (var j = 0; j < mutations[i].addedNodes.length; j++) {
                    var node = mutations[i].addedNodes[j];
                    if (node.nodeType !== 1) {
                        continue;
                    }
                    if (prefersNativePicker() && node.matches && node.matches('.input-group.date, .input-group.time, .input-group.datetime')) {
                        init(node, {});
                    }
                    initAll(node);
                    if (node.matches && (node.matches('.bootstrap-datetimepicker-widget') || node.querySelector('.bootstrap-datetimepicker-widget'))) {
                        window.setTimeout(function () { decorateLegacyPicker(null); }, 0);
                    }
                }
            }
        });

        var startObserver = function () {
            if (document.body) {
                observer.observe(document.body, {childList: true, subtree: true});
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startObserver);
        } else {
            startObserver();
        }
    }

    window.CodeCartNativeDateTimePicker = {
        version: VERSION,
        legacyCompatibility: LEGACY_COMPATIBILITY,
        init: init,
        initAll: initAll,
        loadLegacy: loadLegacy
    };
})(window, document);
