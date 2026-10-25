/* CodeCart: compact AJAX editor for base price, stock, visibility and noindex.
 * Works only on catalog/product and only when enabled by the store settings.
 */
(function ($) {
  'use strict';
  $(function () {
    var $cfg = $('#ccp-quick-price-config');
    var $table = $('#ccp-product-list table.table').first();
    if (!$cfg.length || !$table.length) { return; }
    var endpoint = String($cfg.attr('data-url') || '');
    if (!endpoint) { return; }
    var text = {
      edit: $cfg.attr('data-edit') || 'Edit price',
      quantity: $cfg.attr('data-quantity') || 'Edit quantity',
      save: $cfg.attr('data-save') || 'Save',
      cancel: $cfg.attr('data-cancel') || 'Cancel',
      saved: $cfg.attr('data-saved') || 'Saved',
      on: $cfg.attr('data-on') || 'On',
      off: $cfg.attr('data-off') || 'Off',
      loadError: $cfg.attr('data-load-error') || 'Unable to load',
      saveError: $cfg.attr('data-save-error') || 'Unable to save',
      invalid: $cfg.attr('data-invalid') || 'Invalid price',
      quantityInvalid: $cfg.attr('data-quantity-invalid') || 'Invalid quantity',
      conflict: $cfg.attr('data-conflict') || 'Data changed, reload page'
    };
    var $priceHead = $table.find('thead > tr:first > td').filter(function () {
      return $(this).find('a[href*="sort=p.price"]').length > 0;
    }).first();
    var priceCol = $priceHead.length ? $priceHead.index() : -1;
    var $qtyHead = $table.find('thead > tr:first > td').filter(function () {
      return $(this).find('a[href*="sort=p.quantity"]').length > 0;
    }).first();
    var qtyCol = $qtyHead.length ? $qtyHead.index() : -1;
    var legacyPricePlugin = !!window.CodeCartQuickPriceEditModifierLoaded;

    function note(message, bad) {
      $('.ccp-qpe-notice').remove();
      $('<div class="ccp-qpe-notice alert" role="status"></div>')
        .addClass(bad ? 'alert-danger' : 'alert-success')
        .text(String(message || '')).appendTo('body')
        .delay(2400).fadeOut(200, function () { $(this).remove(); });
    }
    function request(data) {
      return $.ajax({url: endpoint, type: 'POST', dataType: 'json', cache: false, data: data});
    }
    function errorFrom(xhr, fallback) {
      return xhr && xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : fallback;
    }
    function quantityLabel(value) {
      var qty = Number(value);
      var klass = qty <= 0 ? 'label-warning' : (qty <= 5 ? 'label-danger' : 'label-success');
      return $('<span></span>').addClass('label ' + klass).text(String(value));
    }
    function drawPrice($cell, response) {
      var $display = $('<span class="ccp-qpe-display"></span>');
      if (response && response.special_formatted) {
        $('<span class="ccp-qpe-old-price"></span>').text(response.price_formatted).appendTo($display);
        $('<span class="text-danger"></span>').text(response.special_formatted).appendTo($display);
      } else if (response) {
        $('<span></span>').text(response.price_formatted || '').appendTo($display);
      } else {
        $display.html($cell.data('ccp-qpe-original') || '');
      }
      $cell.empty().removeClass('ccp-qpe-editing ccp-qpe-loading').append($display);
      $cell.data('ccp-qpe-original', $display.html());
      $('<button type="button" class="ccp-qpe-edit-btn"><i class="fa fa-pencil" aria-hidden="true"></i></button>')
        .attr({title: text.edit, 'aria-label': text.edit, 'data-field': 'price'}).appendTo($cell);
    }
    function drawQuantity($cell, response) {
      $cell.removeClass('ccp-qpe-editing ccp-qpe-loading');
      var $value = $cell.find('.ccp-qpf-quantity-value').show();
      if (response && response.value !== undefined) {
        $value.empty().append(quantityLabel(response.value));
        $cell.attr('data-quantity', response.value);
      }
      $cell.find('.ccp-qpe-controls,.ccp-qpe-edit-btn').remove();
      if ($cell.find('.ccp-qpe-edit-btn').length === 0) {
        $('<button type="button" class="ccp-qpe-edit-btn"><i class="fa fa-pencil" aria-hidden="true"></i></button>')
          .attr({title: text.quantity, 'aria-label': text.quantity, 'data-field': 'quantity'})
          .insertAfter($value);
      }
    }
    $table.find('tbody > tr').each(function () {
      var $row = $(this), id = parseInt($row.find('input[name="selected[]"]').first().val(), 10);
      if (!id) { return; }
      $row.attr('data-ccp-quick-id', id);
      if (priceCol >= 0 && !legacyPricePlugin) {
        var $price = $row.children('td').eq(priceCol);
        if (!$price.find('.qpe-price-display,.qpe-edit-price').length) {
          $price.addClass('ccp-qpe-price-cell').data('ccp-qpe-original', $price.html());
          drawPrice($price);
        }
      }
      if (qtyCol >= 0) {
        var $qty = $row.children('td').eq(qtyCol).addClass('ccp-qpe-quantity-cell');
        if ($qty.find('.ccp-qpf-quantity-value').length) { drawQuantity($qty); }
      }
    });
    function revert($cell, field) {
      if (field === 'price') { drawPrice($cell); }
      else { drawQuantity($cell); }
    }
    function commit($cell, field) {
      if ($cell.hasClass('ccp-qpe-loading')) { return; }
      var $input = $cell.find('.ccp-qpe-input');
      var value = $.trim(String($input.val() || '')).replace(/[\s\u00a0]/g, '').replace(',', '.');
      var valid = field === 'price'
        ? /^(?:0|[1-9][0-9]{0,10})(?:\.[0-9]{1,4})?$/.test(value)
        : /^(?:0|[1-9][0-9]{0,9})$/.test(value) && Number(value) <= 2147483647;
      if (!valid) {
        $input.addClass('ccp-qpe-invalid').trigger('focus');
        note(field === 'price' ? text.invalid : text.quantityInvalid, true);
        return;
      }
      var $row = $cell.closest('tr');
      $cell.addClass('ccp-qpe-loading').find('button').prop('disabled', true);
      request({action: 'save', field: field, product_id: $row.attr('data-ccp-quick-id'),
        value: value, expected: $cell.data('ccp-expected')})
        .done(function (result) {
          if (!result || !result.success) {
            note(result && result.error ? result.error : text.saveError, true);
            $cell.removeClass('ccp-qpe-loading').find('button').prop('disabled', false);
            return;
          }
          if (field === 'price') { drawPrice($cell, result); }
          else { drawQuantity($cell, result); }
          $cell.addClass('ccp-qpe-saved');
          window.setTimeout(function () { $cell.removeClass('ccp-qpe-saved'); }, 950);
          note(text.saved, false);
        })
        .fail(function (xhr) {
          note(errorFrom(xhr, text.saveError), true);
          $cell.removeClass('ccp-qpe-loading').find('button').prop('disabled', false);
        });
    }
    $table.on('click', '.ccp-qpe-edit-btn', function (e) {
      e.preventDefault();
      var $cell = $(this).closest('td'), field = $(this).attr('data-field'), $row = $cell.closest('tr');
      if (!field || $cell.hasClass('ccp-qpe-editing') || $cell.hasClass('ccp-qpe-loading')) { return; }
      $cell.addClass('ccp-qpe-loading');
      request({action: 'get', field: field, product_id: $row.attr('data-ccp-quick-id')})
        .done(function (result) {
          if (!result || !result.success || !result.can_modify) {
            $cell.removeClass('ccp-qpe-loading');
            note(result && result.error ? result.error : text.loadError, true); return;
          }
          $cell.removeClass('ccp-qpe-loading').addClass('ccp-qpe-editing').data('ccp-expected', result.value);
          var $controls = $('<span class="ccp-qpe-controls"></span>');
          var $input = $('<input type="text" class="ccp-qpe-input" autocomplete="off" />')
            .attr('inputmode', field === 'price' ? 'decimal' : 'numeric')
            .val(field === 'price' ? result.price_value : result.value);
          var $save = $('<button type="button" class="ccp-qpe-save"><i class="fa fa-check" aria-hidden="true"></i></button>')
            .attr({title: text.save, 'aria-label': text.save});
          var $cancel = $('<button type="button" class="ccp-qpe-cancel"><i class="fa fa-times" aria-hidden="true"></i></button>')
            .attr({title: text.cancel, 'aria-label': text.cancel});
          $controls.append($input, $save, $cancel);
          if (field === 'price') { $cell.empty().append($controls); }
          else { $cell.find('.ccp-qpf-quantity-value,.ccp-qpe-edit-btn').hide(); $cell.append($controls); }
          $input.trigger('focus').select();
        })
        .fail(function (xhr) {
          $cell.removeClass('ccp-qpe-loading');
          note(errorFrom(xhr, text.loadError), true);
        });
    });
    $table.on('click', '.ccp-qpe-save', function () {
      var $cell = $(this).closest('td');
      commit($cell, $cell.hasClass('ccp-qpe-price-cell') ? 'price' : 'quantity');
    });
    $table.on('click', '.ccp-qpe-cancel', function () {
      var $cell = $(this).closest('td');
      if ($cell.hasClass('ccp-qpe-price-cell')) { revert($cell, 'price'); }
      else { $cell.find('.ccp-qpf-quantity-value').show(); drawQuantity($cell); }
    });
    $table.on('keydown', '.ccp-qpe-input', function (e) {
      if (e.which === 13) { e.preventDefault(); $(this).closest('td').find('.ccp-qpe-save').trigger('click'); }
      if (e.which === 27) { e.preventDefault(); $(this).closest('td').find('.ccp-qpe-cancel').trigger('click'); }
    });
    $table.on('click', '.ccp-qpf-toggle', function (e) {
      e.preventDefault();
      var $btn = $(this), $row = $btn.closest('tr'), field = $btn.attr('data-field');
      if ($btn.prop('disabled') || (field !== 'status' && field !== 'noindex')) { return; }
      var old = String($btn.attr('data-value'));
      var desired = old === '1' ? '0' : '1';
      $btn.prop('disabled', true).addClass('ccp-qpf-pending');
      request({action: 'save', field: field, product_id: $row.attr('data-ccp-quick-id'),
        value: desired, expected: old})
        .done(function (response) {
          if (!response || !response.success) {
            note(response && response.error ? response.error : text.saveError, true); return;
          }
          var value = String(response.value), enabled = field === 'status' ? value === '1' : value === '0';
          $btn.attr('data-value', value).attr('aria-pressed', enabled ? 'true' : 'false')
            .toggleClass('is-on', enabled).find('.ccp-qpf-toggle-label').text(enabled ? text.on : text.off);
        })
        .fail(function (xhr) { note(errorFrom(xhr, text.conflict), true); })
        .always(function () { $btn.prop('disabled', false).removeClass('ccp-qpf-pending'); });
    });
  });
})(jQuery);
