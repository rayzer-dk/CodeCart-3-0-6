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
        // Detection uses regular authenticated POST submit buttons. Works with JS blocked/WAF.
        // Selecting a discovered table prefills the column-inspection input; no settings are saved.
        $('#ccp-cart-table-chooser').on('change', function () {
            if (this.value) { $('#map-table').val(this.value); }
        });
        function checkoutMode() {
            var custom = $('#ccp-cart-checkout-mode').val() === 'custom';
            $('#ccp-checkout-custom-url').toggle(custom);
            $('#ccp-cart-checkout-url').prop('required', custom);
        }
        $('#ccp-cart-checkout-mode').on('change', checkoutMode); checkoutMode();
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
            if (action === 'send' && count > 10) { window.alert($root.attr('data-bulk-limit-message') || 'Maximum 10 carts.'); return false; }
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
