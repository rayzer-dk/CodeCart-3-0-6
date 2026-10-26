/* CodeCart product list: in-cell editing (no popup and no table reflow).
 * Status/indexing switches continue to use the same permission-checked API.
 */
(function ($) {
  'use strict';
  $(function () {
    var $cfg = $('#ccp-quick-price-config');
    var $table = $('#ccp-product-list table.table').first();
    if (!$cfg.length || !$table.length || window.CodeCartProductQuickEditLoaded) { return; }
    var endpoint = String($cfg.attr('data-url') || '');
    if (!endpoint) { return; }
    window.CodeCartProductQuickEditLoaded = true;
    var labels = {
      price: $cfg.attr('data-edit') || 'Edit price',
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
      conflict: $cfg.attr('data-conflict') || 'Data changed, reload'
    };
    var $headers = $table.find('thead > tr:first > td');
    function columnIndex(sort) {
      var $header = $headers.filter(function () {
        return $(this).find('a[href*="sort=' + sort + '"]').length > 0;
      }).first();
      return $header.length ? $header.index() : -1;
    }
    var priceCol = columnIndex('p.price');
    var quantityCol = columnIndex('p.quantity');
    var legacyPricePlugin = !!window.CodeCartQuickPriceEditModifierLoaded;
    var active = null, generation = 0;

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
    function errorText(xhr, fallback) {
      return xhr && xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : fallback;
    }
    function quantityBadge(value) {
      var n = Number(value);
      return $('<span class="label"></span>')
        .addClass(n <= 0 ? 'label-warning' : n <= 5 ? 'label-danger' : 'label-success')
        .text(String(value));
    }
    function pencil(field) {
      return $('<button type="button" class="ccp-qpe-edit-btn"><i class="fa fa-pencil" aria-hidden="true"></i></button>')
        .attr({title: labels[field], 'aria-label': labels[field], 'data-field': field});
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
      $cell.empty().append($display, pencil('price'));
      $cell.data('ccp-qpe-original', $display.html());
    }
    function drawQuantity($cell, response) {
      var $label = $cell.find('.ccp-qpf-quantity-value');
      if (response && response.value !== undefined) {
        $label.empty().append(quantityBadge(response.value));
        $cell.attr('data-quantity', response.value);
      }
      $cell.find('.ccp-qpe-edit-btn').remove();
      if ($label.length) { pencil('quantity').insertAfter($label); }
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
      if (quantityCol >= 0) {
        var $qty = $row.children('td').eq(quantityCol).addClass('ccp-qpe-quantity-cell');
        if ($qty.find('.ccp-qpf-quantity-value').length) { drawQuantity($qty); }
      }
    });

    function closeEditor() {
      if (!active) { return; }
      var previous = active;
      active = null;
      previous.$cell.removeClass('ccp-qpe-editing ccp-qpe-loading');
      previous.$cell.find('.ccp-qpe-inline-editor').remove();
    }
    function showInput(current, result) {
      if (active !== current) { return; }
      current.expected = String(result.value);
      current.$cell.removeClass('ccp-qpe-loading').addClass('ccp-qpe-editing');
      var $editor = $('<span class="ccp-qpe-inline-editor" role="group"></span>')
        .attr('aria-label', labels[current.field]);
      var $input = $('<input type="text" class="ccp-qpe-inline-input" autocomplete="off" />')
        .attr({inputmode: current.field === 'price' ? 'decimal' : 'numeric',
          'aria-label': labels[current.field]})
        .val(current.field === 'price' ? result.price_value : result.value);
      var $save = $('<button type="button" class="ccp-qpe-inline-save"><i class="fa fa-check" aria-hidden="true"></i></button>')
        .attr({title: labels.save, 'aria-label': labels.save});
      var $cancel = $('<button type="button" class="ccp-qpe-inline-cancel"><i class="fa fa-times" aria-hidden="true"></i></button>')
        .attr({title: labels.cancel, 'aria-label': labels.cancel});
      current.$input = $input;
      $editor.append($input, $save, $cancel).appendTo(current.$cell);
      $input.trigger('focus').select();
    }
    function openEditor($cell, field, productId) {
      if (active && active.busy) { return; }
      closeEditor();
      var current = {generation: ++generation, $cell: $cell, field: field,
        productId: productId, expected: '', busy: false, $input: null};
      active = current;
      $cell.addClass('ccp-qpe-loading');
      request({action: 'get', field: field, product_id: productId})
        .done(function (result) {
          if (active !== current) { return; }
          if (!result || !result.success || !result.can_modify) {
            closeEditor();
            note(result && result.error ? result.error : labels.loadError, true);
            return;
          }
          showInput(current, result);
        })
        .fail(function (xhr) {
          if (active !== current) { return; }
          closeEditor();
          note(errorText(xhr, labels.loadError), true);
        });
    }
    function saveEditor() {
      var current = active;
      if (!current || current.busy || !current.$input) { return; }
      var value = $.trim(String(current.$input.val() || '')).replace(/[\s\u00a0]/g, '').replace(',', '.');
      var valid = current.field === 'price'
        ? /^(?:0|[1-9][0-9]{0,10})(?:\.[0-9]{1,4})?$/.test(value)
        : /^(?:0|[1-9][0-9]{0,9})$/.test(value) && Number(value) <= 2147483647;
      if (!valid) {
        current.$input.addClass('ccp-qpe-invalid').trigger('focus');
        note(current.field === 'price' ? labels.invalid : labels.quantityInvalid, true);
        return;
      }
      current.busy = true;
      current.$cell.find('.ccp-qpe-inline-editor').addClass('ccp-qpe-saving')
        .find('button,input').prop('disabled', true);
      request({action: 'save', field: current.field, product_id: current.productId,
        value: value, expected: current.expected})
        .done(function (result) {
          if (active !== current) { return; }
          if (!result || !result.success) {
            note(result && result.error ? result.error : labels.saveError, true);
            return;
          }
          closeEditor();
          if (current.field === 'price') { drawPrice(current.$cell, result); }
          else { drawQuantity(current.$cell, result); }
          current.$cell.addClass('ccp-qpe-saved');
          window.setTimeout(function () { current.$cell.removeClass('ccp-qpe-saved'); }, 850);
          note(labels.saved, false);
        })
        .fail(function (xhr) {
          if (active === current) { note(errorText(xhr, labels.conflict), true); }
        })
        .always(function () {
          current.busy = false;
          if (active === current) {
            current.$cell.find('.ccp-qpe-inline-editor').removeClass('ccp-qpe-saving')
              .find('button,input').prop('disabled', false);
            current.$input.trigger('focus').select();
          }
        });
    }

    $table.on('click', '.ccp-qpe-edit-btn', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var $cell = $(this).closest('td');
      var field = $(this).attr('data-field');
      var productId = parseInt($cell.closest('tr').attr('data-ccp-quick-id'), 10);
      if ((field !== 'price' && field !== 'quantity') || !productId) { return; }
      openEditor($cell, field, productId);
    });
    $table.on('click', '.ccp-qpe-inline-save', function () { saveEditor(); });
    $table.on('click', '.ccp-qpe-inline-cancel', function () { if (active && !active.busy) { closeEditor(); } });
    $table.on('keydown', '.ccp-qpe-inline-input', function (event) {
      if (event.which === 13) { event.preventDefault(); saveEditor(); }
      if (event.which === 27) { event.preventDefault(); if (active && !active.busy) { closeEditor(); } }
    });
    $(document).on('keydown.ccpQuickCell', function (event) {
      if (event.which === 27 && active && !active.busy) { closeEditor(); }
    });
    $(document).on('mousedown.ccpQuickCell', function (event) {
      if (!active || active.busy) { return; }
      if (!$(event.target).closest('.ccp-qpe-inline-editor,.ccp-qpe-edit-btn').length) { closeEditor(); }
    });

    // Product status and indexability switches retain the previously validated
    // CodeCart/ocStore polarity: 1=enabled, 0=disabled.
    $table.on('click', '.ccp-qpf-toggle', function (event) {
      event.preventDefault();
      var $button = $(this), $row = $button.closest('tr'), field = $button.attr('data-field');
      if ($button.prop('disabled') || (field !== 'status' && field !== 'noindex')) { return; }
      var previous = String($button.attr('data-value')), next = previous === '1' ? '0' : '1';
      $button.prop('disabled', true).addClass('ccp-qpf-pending');
      request({action: 'save', field: field, product_id: $row.attr('data-ccp-quick-id'),
        value: next, expected: previous})
        .done(function (result) {
          if (!result || !result.success) {
            note(result && result.error ? result.error : labels.saveError, true); return;
          }
          var value = String(result.value), enabled = value === '1';
          $button.attr('data-value', value)
            .attr('aria-checked', enabled ? 'true' : 'false')
            .attr('aria-label', String($button.attr('data-label') || '') + ': ' + (enabled ? labels.on : labels.off))
            .toggleClass('is-on', enabled);
        })
        .fail(function (xhr) { note(errorText(xhr, labels.conflict), true); })
        .always(function () { $button.prop('disabled', false).removeClass('ccp-qpf-pending'); });
    });
  });
})(jQuery);
