/* CodeCart PRO: sortable extension tables. Bind on document: list arrives by AJAX. */
(function ($) {
    'use strict';

    var stateByType = {};
    var keys = ['name', 'status', 'installed', 'upload', 'date'];

    function selectedType() {
        // The selection value is the target route URL, stable across AJAX refreshes.
        return $('select[name="type"]').val() || 'extensions';
    }

    function compareValues(a, b, key, direction, locale) {
        var x = a.getAttribute('data-sort-' + key) || '';
        var y = b.getAttribute('data-sort-' + key) || '';
        // Unknown dates always appear at the end, in both directions.
        if (key === 'date' || key === 'upload') {
            if (!x || !y) {
                return x ? -1 : (y ? 1 : 0);
            }
        }
        if (key === 'installed') {
            return (Number(x) - Number(y)) * direction;
        }
        return x.localeCompare(y, locale, {sensitivity: 'base', numeric: true}) * direction;
    }

    function apply() {
        var $table = $('#extension table.ccp-extension-table').first();
        var state = stateByType[selectedType()];
        if (!$table.length) { return; }
        var $body = $table.children('tbody').first();
        if (!$body.length) { return; }
        if (state) {
            var groups = [];
            var active = null;
            $body.children('tr').each(function () {
                if ($(this).hasClass('ccp-extension-main')) {
                    active = { head: this, rows: [this], position: groups.length };
                    groups.push(active);
                } else if (active && $(this).hasClass('ccp-extension-child')) {
                    active.rows.push(this);
                }
            });
            var locale = $('html').attr('lang') || undefined;
            groups.sort(function (a, b) {
                return compareValues(a.head, b.head, state.key, state.direction, locale) || (a.position - b.position);
            });
            var fragment = document.createDocumentFragment();
            groups.forEach(function (group) {
                group.rows.forEach(function (row) { fragment.appendChild(row); });
            });
            $body.get(0).appendChild(fragment);
        }
        $table.find('thead [data-ccp-sort]').each(function () {
            var $button = $(this);
            var activeKey = !!state && $button.attr('data-ccp-sort') === state.key;
            var ascending = activeKey && state.direction === 1;
            $button.find('i').attr('class', 'fa ' + (activeKey ? (ascending ? 'fa-sort-asc' : 'fa-sort-desc') : 'fa-sort'));
            $button.attr('aria-pressed', activeKey ? 'true' : 'false');
            $button.attr('title', activeKey && ascending ? $button.attr('data-sort-desc') : $button.attr('data-sort-asc'));
            $button.closest('th').attr('aria-sort', activeKey ? (ascending ? 'ascending' : 'descending') : 'none');
        });
    }

    // Delegating at document level is essential: #extension is injected AFTER
    // this script runs, and is replaced on every filter/install/uninstall request.
    $(document).on('click.ccpExtensionSort', '#extension .ccp-extension-sort', function (event) {
        event.preventDefault();
        var key = $(this).attr('data-ccp-sort');
        if ($.inArray(key, keys) === -1) { return; }
        var type = selectedType();
        var previous = stateByType[type];
        var initialDirection = (key === 'upload' || key === 'date' || key === 'installed') ? -1 : 1;
        stateByType[type] = {
            key: key,
            direction: previous && previous.key === key ? -previous.direction : initialDirection
        };
        apply();
    });

    window.CodeCartExtensionSort = { apply: apply };
})(jQuery);
