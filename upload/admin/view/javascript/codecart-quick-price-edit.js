/* CodeCart: product list quick editor, opt-in via Settings > Options > Products.
 * Price and quantity are edited in one body-mounted popover, never inside table cells.
 * This prevents table column shifts and preserves OpenCart/OCMOD column compatibility.
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
      conflict: $cfg.attr('data-conflict') || 'The product was changed elsewhere'
    };
    var $head = $table.find('thead > tr:first > td');
    function columnIndex(sort) {
      var $header = $head.filter(function () {
        return $(this).find('a[href*="sort=' + sort + '"]').length > 0;
      }).first();
      return $header.length ? $header.index() : -1;
    }
    var priceCol = columnIndex('p.price');
    var quantityCol = columnIndex('p.quantity');
    var legacyPricePlugin = !!window.CodeCartQuickPriceEditModifierLoaded;
    var active = null, nextId = 0;

    function notice(message, isError) {
      $('.ccp-qpe-notice').remove();
      $('<div class="ccp-qpe-notice alert" role="status"></div>')
        .addClass(isError ? 'alert-danger' : 'alert-success')
        .text(String(message || ''))
        .appendTo('body')
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
    function addPencil($cell, field) {
      $('<button type="button" class="ccp-qpe-edit-btn"><i class="fa fa-pencil" aria-hidden="true"></i></button>')
        .attr({title: labels[field], 'aria-label': labels[field], 'data-field': field})
        .appendTo($cell);
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
      $cell.empty().append($display);
      $cell.data('ccp-qpe-original', $display.html());
      addPencil($cell, 'price');
    }
    function drawQuantity($cell, response) {
      var $value = $cell.find('.ccp-qpf-quantity-value');
      if (response && response.value !== undefined) {
        $value.empty().append(quantityBadge(response.value));
        $cell.attr('data-quantity', response.value);
      }
      $cell.find('.ccp-qpe-edit-btn').remove();
      if ($value.length) {
        // Keep the badge and the notify-count badge in their original cells.
        $('<button type="button" class="ccp-qpe-edit-btn"><i class="fa fa-pencil" aria-hidden="true"></i></button>')
          .attr({title: labels.quantity, 'aria-label': labels.quantity, 'data-field': 'quantity'})
          .insertAfter($value);
      }
    }

    $table.find('tbody > tr').each(function () {
      var $row = $(this);
      var id = parseInt($row.find('input[name="selected[]"]').first().val(), 10);
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

    function closeEditor(restoreFocus) {
      if (!active) { return; }
      var current = active;
      active = null;
      current.$popup.remove();
      current.$cell.removeClass('ccp-qpe-active');
      if (restoreFocus && current.$button && current.$button.closest('body').length) {
        current.$button.trigger('focus');
      }
    }
    function placeEditor(current) {
      if (!current || !current.$popup.length || !current.$cell[0]) { return; }
      var rect = current.$cell[0].getBoundingClientRect();
      var width = current.$popup.outerWidth();
      var height = current.$popup.outerHeight();
      var viewWidth = document.documentElement.clientWidth;
      var viewHeight = document.documentElement.clientHeight;
      var left = Math.max(8, Math.min(rect.right - width, viewWidth - width - 8));
      // Prefer the compact editor directly ABOVE the source cell. Near the top
      // of the screen, clamp it to the viewport rather than opening below.
      var top = Math.max(6, rect.top - height - 5);
      top = Math.min(top, Math.max(6, viewHeight - height - 6));
      current.$popup.addClass('ccp-qpe-popover--above');
      current.$popup.css({left: Math.round(left) + 'px', top: Math.round(top) + 'px'});
    }
    function popupInput(current, result) {
      if (active !== current) { return; }
      current.expected = String(result.value);
      current.$popup.empty().removeClass('ccp-qpe-popover--loading');
      var $controls = $('<div class="ccp-qpe-popover-controls"></div>');
      var $input = $('<input type="text" class="ccp-qpe-popover-input" autocomplete="off" />')
        .attr({'inputmode': current.field === 'price' ? 'decimal' : 'numeric',
          'aria-label': labels[current.field]})
        .val(current.field === 'price' ? result.price_value : result.value);
      var $save = $('<button type="button" class="ccp-qpe-popover-save"><i class="fa fa-check" aria-hidden="true"></i></button>')
        .attr({title: labels.save, 'aria-label': labels.save});
      var $cancel = $('<button type="button" class="ccp-qpe-popover-cancel"><i class="fa fa-times" aria-hidden="true"></i></button>')
        .attr({title: labels.cancel, 'aria-label': labels.cancel});
      current.$input = $input;
      $controls.append($input, $save, $cancel);
      current.$popup.append($controls);
      placeEditor(current);
      $input.trigger('focus').select();
    }
    function showEditor($cell, $button, field, productId) {
      closeEditor(false);
      var id = ++nextId;
      var $popup = $('<div id="ccp-qpe-popover" class="ccp-qpe-popover ccp-qpe-popover--loading" role="dialog"></div>')
        .attr('aria-label', labels[field])
        .append($('<div class="ccp-qpe-popover-progress" aria-hidden="true"></div>').text('…'))
        .appendTo('body');
      var current = {id: id, $cell: $cell, $button: $button, $popup: $popup,
        field: field, productId: productId, expected: null, busy: false, $input: null};
      active = current;
      $cell.addClass('ccp-qpe-active');
      placeEditor(current);
      request({action: 'get', field: field, product_id: productId})
        .done(function (result) {
          if (active !== current) { return; }
          if (!result || !result.success || !result.can_modify) {
            closeEditor(true);
            notice(result && result.error ? result.error : labels.loadError, true);
            return;
          }
          popupInput(current, result);
        })
        .fail(function (xhr) {
          if (active !== current) { return; }
          closeEditor(true);
          notice(errorText(xhr, labels.loadError), true);
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
        notice(current.field === 'price' ? labels.invalid : labels.quantityInvalid, true);
        return;
      }
      current.busy = true;
      current.$popup.addClass('ccp-qpe-popover--saving').find('button,input').prop('disabled', true);
      request({action: 'save', field: current.field, product_id: current.productId,
        value: value, expected: current.expected})
        .done(function (result) {
          if (active !== current) { return; }
          if (!result || !result.success) {
            notice(result && result.error ? result.error : labels.saveError, true);
            return;
          }
          if (current.field === 'price') { drawPrice(current.$cell, result); }
          else { drawQuantity(current.$cell, result); }
          current.$cell.addClass('ccp-qpe-saved');
          window.setTimeout(function () { current.$cell.removeClass('ccp-qpe-saved'); }, 850);
          closeEditor(false);
          notice(labels.saved, false);
        })
        .fail(function (xhr) {
          if (active === current) { notice(errorText(xhr, labels.conflict), true); }
        })
        .always(function () {
          current.busy = false;
          if (active === current) {
            current.$popup.removeClass('ccp-qpe-popover--saving').find('button,input').prop('disabled', false);
            current.$input.trigger('focus').select();
          }
        });
    }

    $table.on('click', '.ccp-qpe-edit-btn', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var $button = $(this);
      var $cell = $button.closest('td');
      var field = $button.attr('data-field');
      var productId = parseInt($button.closest('tr').attr('data-ccp-quick-id'), 10);
      if ((field !== 'price' && field !== 'quantity') || !productId) { return; }
      if (active && active.busy) { return; }
      showEditor($cell, $button, field, productId);
    });
    $(document).on('click.ccpQpe', '#ccp-qpe-popover .ccp-qpe-popover-save', function () { saveEditor(); });
    $(document).on('click.ccpQpe', '#ccp-qpe-popover .ccp-qpe-popover-cancel', function () { closeEditor(true); });
    $(document).on('keydown.ccpQpe', '#ccp-qpe-popover .ccp-qpe-popover-input', function (event) {
      if (event.which === 13) { event.preventDefault(); saveEditor(); }
      if (event.which === 27) { event.preventDefault(); if (active && !active.busy) { closeEditor(true); } }
    });
    $(document).on('keydown.ccpQpe', function (event) {
      if (event.which === 27 && active && !active.busy) { closeEditor(true); }
    });
    $(document).on('mousedown.ccpQpe', function (event) {
      if (!active || active.busy) { return; }
      if (!$(event.target).closest('#ccp-qpe-popover, .ccp-qpe-edit-btn').length) { closeEditor(false); }
    });
    $(window).on('resize.ccpQpe', function () {
      if (active && !active.busy) { placeEditor(active); }
    });
    // On responsive screens the browser may horizontally scroll to reveal
    // the pencil before opening the popover. Reposition instead of cancelling.
    $(window).on('scroll.ccpQpe', function () {
      if (active && !active.busy) { placeEditor(active); }
    });
    $('#ccp-product-list .table-responsive').on('scroll.ccpQpe', function () {
      if (active && !active.busy) { placeEditor(active); }
    });

    // Status and indexing switches remain AJAX-only and keep their saved states.
    $table.on('click', '.ccp-qpf-toggle', function (event) {
      event.preventDefault();
      var $button = $(this);
      var $row = $button.closest('tr');
      var field = $button.attr('data-field');
      if ($button.prop('disabled') || (field !== 'status' && field !== 'noindex')) { return; }
      var old = String($button.attr('data-value'));
      var next = old === '1' ? '0' : '1';
      $button.prop('disabled', true).addClass('ccp-qpf-pending');
      request({action: 'save', field: field, product_id: $row.attr('data-ccp-quick-id'),
        value: next, expected: old})
        .done(function (result) {
          if (!result || !result.success) {
            notice(result && result.error ? result.error : labels.saveError, true);
            return;
          }
          // Both CodeCart status and ocStore noindex use 1 for "enabled".
          var value = String(result.value), enabled = value === '1';
          $button.attr('data-value', value)
            .attr('aria-checked', enabled ? 'true' : 'false')
            .attr('aria-label', String($button.attr('data-label') || '') + ': ' + (enabled ? labels.on : labels.off))
            .toggleClass('is-on', enabled);
        })
        .fail(function (xhr) { notice(errorText(xhr, labels.conflict), true); })
        .always(function () { $button.prop('disabled', false).removeClass('ccp-qpf-pending'); });
    });
  });
})(jQuery);
