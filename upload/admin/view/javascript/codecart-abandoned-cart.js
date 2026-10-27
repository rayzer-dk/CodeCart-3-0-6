(function ($) {
    'use strict';
    $(function () {
        var $root = $('.ccp-carts-page');
        if (!$root.length) { return; }
        function mapping() {
            var external = $('#abandoned-source').val() === 'external';
            $root.find('.ccp-external-fields').toggleClass('is-visible', external);
            // External mode is read-only and cannot send reminders or capture guest checkout contacts.
            var $mail = $('#abandoned-mail-auto'), $guest = $('#abandoned-guest-capture');
            if (external) { $mail.val('0'); $guest.val('0'); }
            $mail.prop('disabled', external);
            $guest.prop('disabled', external);
        }
        $('#abandoned-source').on('change', mapping); mapping();
        $('#ccp-find-cart-tables').on('click', function () {
            var $root = $(this).closest('.ccp-table-chooser');
            var $select = $('#ccp-cart-table-chooser'), $button = $(this);
            $button.prop('disabled', true);
            $.getJSON($root.attr('data-source-url')).done(function (result) {
                $select.find('option:not(:first)').remove();
                if (!result || !result.success || !(result.tables || []).length) {
                    $('#ccp-external-detect-result').text($root.attr('data-empty'));
                    $select.hide(); return;
                }
                $.each(result.tables, function (_, entry) {
                    $('<option>').val(entry.table).text(entry.table).appendTo($select);
                });
                $select.show();
            }).fail(function () { $('#ccp-external-detect-result').text($root.attr('data-empty')); })
              .always(function () { $button.prop('disabled', false); });
        });
        $('#ccp-cart-table-chooser').on('change', function () {
            if (this.value) { $('#map-table').val(this.value); $('#ccp-external-autodetect').trigger('click'); }
        });
        $('#ccp-external-autodetect').on('click', function () {
            var $section = $('#ccp-external-mapping'), table = $('#map-table').val().trim();
            var $status = $('#ccp-external-detect-result');
            var $button = $(this);
            if (!table) { $status.text($section.attr('data-detect-failed')); return; }
            $button.prop('disabled', true);
            $.ajax({url: $section.attr('data-schema-url'), method: 'GET', dataType: 'json',
                data: {table: table}, timeout: 12000, cache: false
            }).done(function (result) {
                if (!result || !result.success) {
                    $status.text((result && result.error) || $section.attr('data-detect-failed'));
                    return;
                }
                $.each(result.fields || {}, function (key, value) {
                    $('#map-' + key).val(value || '');
                });
                if ((result.missing || []).length) {
                    $section.find('.ccp-external-advanced').prop('open', true);
                    $status.text($section.attr('data-missing') + ': ' + result.missing.join(', '));
                } else { $status.text($section.attr('data-detect-ok')); }
            }).fail(function () { $status.text($section.attr('data-detect-failed')); })
              .always(function () { $button.prop('disabled', false); });
        });
        var $boxes = $root.find('.ccp-cart-select');
        var $all = $('#ccp-cart-select-all');
        function selected() {
            var count = $boxes.filter(':checked').length;
            $('#ccp-cart-selected-count').text(count);
            $('#ccp-cart-bulk-submit').prop('disabled', !count || !$('#ccp-bulk-action').val());
            $all.prop('indeterminate', count > 0 && count < $boxes.length);
            $all.prop('checked', !!count && count === $boxes.length);
        }
        $all.on('change', function () { $boxes.prop('checked', this.checked); selected(); });
        $boxes.on('change', selected);
        $('#ccp-bulk-action').on('change', selected); selected();
        $root.find('form[data-confirm]').on('submit', function () { return window.confirm($(this).attr('data-confirm')); });
        $('#ccp-cart-bulk-form').on('submit', function () {
            var $form = $(this), count = $boxes.filter(':checked').length;
            if (!count) { return false; }
            var action = $('#ccp-bulk-action').val();
            if (!action) { return false; }
            if (action === 'send' && count > 10) { window.alert('Select up to 10 carts for one send operation.'); return false; }
            var question = action === 'send' ? $form.attr('data-confirm-send') : $form.attr('data-confirm-delete');
            return window.confirm(question);
        });
        // Preserve the active tab only in this page's hash. No background saves.
        var hash = window.location.hash;
        if (!hash && /[?&]history_(status|search|page)=/.test(window.location.search)) { hash = '#ccp-tab-history'; }
        if (hash && /^#ccp-tab-(carts|history|settings)$/.test(hash)) {
            $root.find('.ccp-cart-tabs a[href="' + hash + '"]').tab('show');
        }
        $root.find('.ccp-cart-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname + window.location.search + $(e.target).attr('href'));
            }
        });
        $root.find('[data-toggle="tooltip"]').tooltip({container:'body'});
    });
})(jQuery);
