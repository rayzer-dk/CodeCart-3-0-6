/* CodeCart 3.0.6: built-in, opt-out quick base-price editor on catalog/product only.
 * AJAX is read/write permission checked in the controller. No global admin hooks.
 */
(function ($) {
  'use strict';
  $(function () {
    var $config = $('#ccp-quick-price-config'), $table = $('#ccp-product-list table.table').first();
    if (!$config.length || !$table.length || window.CodeCartQuickPriceEditModifierLoaded) { return; }
    var copy = {
      edit: $config.attr('data-edit') || 'Edit price',
      save: $config.attr('data-save') || 'Save',
      cancel: $config.attr('data-cancel') || 'Cancel',
      saved: $config.attr('data-saved') || 'Price saved.',
      loadError: $config.attr('data-load-error') || 'Failed to load price.',
      saveError: $config.attr('data-save-error') || 'Failed to save price.',
      invalid: $config.attr('data-invalid') || 'Invalid price.'
    };
    var endpoint = String($config.attr('data-url') || '');
    if (!endpoint) { return; }
    var $headers = $table.find('thead > tr:first > td');
    var $priceHeader = $headers.filter(function () {
      return $(this).find('a[href*="sort=p.price"]').length > 0;
    }).first();
    if (!$priceHeader.length) { return; }
    var priceIndex = $priceHeader.index();

    function notice(message, isError) {
      $('.ccp-qpe-notice').remove();
      var $node = $('<div class="ccp-qpe-notice alert" role="status"></div>')
        .addClass(isError ? 'alert-danger' : 'alert-success')
        .text(String(message || '')).appendTo('body');
      window.setTimeout(function () { $node.fadeOut(180, function () { $node.remove(); }); }, 2600);
    }

    function draw($cell, html, response) {
      $cell.empty().removeClass('ccp-qpe-editing ccp-qpe-loading');
      var $content = $('<span class="ccp-qpe-display"></span>');
      if (response) {
        if (response.special_formatted) {
          $('<span class="ccp-qpe-old-price"></span>').text(response.price_formatted).appendTo($content);
          $('<span class="text-danger"></span>').text(response.special_formatted).appendTo($content);
        } else {
          $('<span></span>').text(response.price_formatted || '').appendTo($content);
        }
        $cell.data('ccp-price-markup', $content.html());
      } else {
        $content.html(html || '');
      }
      $cell.append($content);
      $('<button type="button" class="ccp-qpe-edit-btn" aria-label="' + copy.edit.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '"><i class="fa fa-pencil" aria-hidden="true"></i></button>')
        .attr('title', copy.edit).appendTo($cell);
    }

    $table.find('tbody > tr').each(function () {
      var $row = $(this);
      var id = parseInt($row.find('input[name="selected[]"]').first().val(), 10);
      if (!id) { return; }
      var $cell = $row.children('td').eq(priceIndex);
      if (!$cell.length || $cell.find('.qpe-price-display,.qpe-edit-price').length) { return; }
      var original = $cell.html();
      $cell.addClass('ccp-qpe-price-cell').data('ccp-product-id', id).data('ccp-price-markup', original);
      draw($cell, original, null);
    });

    $table.on('click', '.ccp-qpe-edit-btn', function (event) {
      event.preventDefault();
      var $cell = $(this).closest('.ccp-qpe-price-cell');
      if ($cell.hasClass('ccp-qpe-loading') || $cell.hasClass('ccp-qpe-editing')) { return; }
      $cell.addClass('ccp-qpe-loading');
      $.ajax({url: endpoint, type: 'POST', dataType: 'json', data: {action: 'get', product_id: $cell.data('ccp-product-id')}})
        .done(function (data) {
          if (!data || !data.success || !data.can_modify) {
            draw($cell, $cell.data('ccp-price-markup'), null);
            notice(data && data.error ? data.error : copy.loadError, true);
            return;
          }
          $cell.empty().removeClass('ccp-qpe-loading').addClass('ccp-qpe-editing');
          var $controls = $('<span class="ccp-qpe-controls"></span>');
          var $input = $('<input type="text" inputmode="decimal" class="ccp-qpe-input" autocomplete="off" />').val(data.price_value);
          var $save = $('<button type="button" class="ccp-qpe-save"><i class="fa fa-check"></i></button>').attr('title', copy.save).attr('aria-label', copy.save);
          var $cancel = $('<button type="button" class="ccp-qpe-cancel"><i class="fa fa-times"></i></button>').attr('title', copy.cancel).attr('aria-label', copy.cancel);
          $controls.append($input, $save, $cancel).appendTo($cell);
          $input.trigger('focus').select();
        })
        .fail(function (xhr) {
          draw($cell, $cell.data('ccp-price-markup'), null);
          notice(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : copy.loadError, true);
        });
    });

    function cancel($cell) { draw($cell, $cell.data('ccp-price-markup'), null); }
    function save($cell) {
      if ($cell.hasClass('ccp-qpe-loading')) { return; }
      var $input = $cell.find('.ccp-qpe-input');
      var raw = $.trim($input.val()).replace(/[\s\u00a0]/g, '').replace(',', '.');
      if (!/^(?:0|[1-9][0-9]{0,10})(?:\.[0-9]{1,4})?$/.test(raw)) {
        $input.addClass('ccp-qpe-invalid').trigger('focus');
        notice(copy.invalid, true);
        return;
      }
      $cell.addClass('ccp-qpe-loading');
      $cell.find('button').prop('disabled', true);
      $.ajax({url: endpoint, type: 'POST', dataType: 'json', data: {action: 'save', product_id: $cell.data('ccp-product-id'), price: raw}})
        .done(function (data) {
          if (data && data.success) {
            draw($cell, '', data);
            $cell.addClass('ccp-qpe-saved');
            window.setTimeout(function () { $cell.removeClass('ccp-qpe-saved'); }, 1000);
            notice(copy.saved, false);
          } else {
            $cell.removeClass('ccp-qpe-loading').find('button').prop('disabled', false);
            notice(data && data.error ? data.error : copy.saveError, true);
          }
        })
        .fail(function (xhr) {
          $cell.removeClass('ccp-qpe-loading').find('button').prop('disabled', false);
          notice(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : copy.saveError, true);
        });
    }
    $table.on('click', '.ccp-qpe-save', function () { save($(this).closest('.ccp-qpe-price-cell')); });
    $table.on('click', '.ccp-qpe-cancel', function () { cancel($(this).closest('.ccp-qpe-price-cell')); });
    $table.on('keydown', '.ccp-qpe-input', function (e) {
      if (e.which === 13) { e.preventDefault(); save($(this).closest('.ccp-qpe-price-cell')); }
      if (e.which === 27) { e.preventDefault(); cancel($(this).closest('.ccp-qpe-price-cell')); }
    });
  });
})(jQuery);
