/* Scheduler task editor: inline, directly below the selected row.
 * Uses the original OpenCart POST form, never saves entity data via AJAX.
 * AJAX only loads task details; GET protected by user_token and permissions.
 */
(function ($) {
  'use strict';
  $(function () {
    var $panel = $('#scheduler-form');
    if (!$panel.length) { return; }

    var $home = $('<div id="ccp-scheduler-form-home" aria-hidden="true"></div>').insertBefore($panel);
    var $form = $panel.find('form').first();
    var $heading = $panel.find('.panel-title').first();
    var $add = $('#button-scheduler-add');
    var url = $panel.attr('data-info-url') || '';
    var editorRow = null;
    var pendingRequest = null;
    var $frequency = $('#input-scheduler-frequency');
    var $interval = $('#input-scheduler-interval');
    var $customInterval = $('#scheduler-custom-interval');

    function setFrequency(seconds) {
      var value = String(parseInt(seconds, 10) || 3600);
      $interval.val(value);
      $frequency.val($frequency.find('option[value="' + value + '"]').length ? value : 'custom');
      $customInterval.toggle($frequency.val() === 'custom');
    }

    $frequency.on('change', function () {
      if ($(this).val() === 'custom') {
        $customInterval.show();
        $interval.trigger('focus');
      } else {
        $interval.val($(this).val());
        $customInterval.hide();
      }
    });
    setFrequency($interval.val());

    function resetForm() {
      $form.find('input[name="scheduler_id"]').val('0');
      $form.find('input[name="code"]').val('').prop('readOnly', false);
      $form.find('input[name="route"]').val('');
      $('#input-scheduler-available').val('');
      setFrequency(3600);
      $form.find('textarea[name="args"]').val('{}');
      $form.find('select[name="status"]').val('1');
      $panel.find('.ccp-scheduler-form-error').hide();
      $heading.empty().append($('<i class="fa fa-plus" aria-hidden="true"></i>'))
        .append(document.createTextNode(' ' + ($panel.attr('data-add-title') || 'Add task')));
    }

    function restoreEditor(shouldReset) {
      if (pendingRequest) { pendingRequest.abort(); pendingRequest = null; }
      $panel.detach().show().insertAfter($home);
      if (editorRow) {
        editorRow.remove();
        editorRow = null;
      }
      if (shouldReset) { resetForm(); }
    }

    function populateForm(task) {
      $form.find('input[name="scheduler_id"]').val(task.scheduler_id);
      $form.find('input[name="code"]').val(task.code).prop('readOnly', !!task.is_core);
      $form.find('input[name="route"]').val(task.route);
      setFrequency(task.interval_seconds);
      $form.find('textarea[name="args"]').val(task.args || '{}');
      $form.find('select[name="status"]').val(String(task.status));
      $panel.find('.ccp-scheduler-form-error').hide();
      $heading.empty().append($('<i class="fa fa-pencil" aria-hidden="true"></i>'))
        .append(document.createTextNode(' ' + ($panel.attr('data-edit-title') || 'Edit task')));
    }

    $('table.codecart-scheduler-table').on('click', '.js-scheduler-edit', function (event) {
      if (!url || !$panel.length) { return; }
      event.preventDefault();

      var $link = $(this);
      var id = parseInt($link.attr('data-id'), 10);
      var $target = $link.closest('tr');
      if (!id || !$target.length) { return; }

      // No hard page navigation: the loading indicator occupies the EXACT
      // table row immediately below the clicked task.
      restoreEditor(true);
      editorRow = $('<tr class="ccp-scheduler-editor-row"><td colspan="8"></td></tr>').insertAfter($target);
      var $holder = editorRow.children('td');
      var $progress = $('<div class="ccp-scheduler-editor-loading" role="status"></div>');
      $progress.append($('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>'));
      $progress.append(document.createTextNode(' ' + ($panel.attr('data-loading') || 'Loading task…')));
      $holder.append($progress);
      var requestedRow = editorRow;

      pendingRequest = $.ajax({
        url: url,
        method: 'GET',
        data: {scheduler_id: id},
        dataType: 'json',
        timeout: 15000,
        cache: false
      }).done(function (result) {
        if (editorRow !== requestedRow) { return; }
        if (!result || result.success !== true || +result.scheduler_id !== id) {
          $progress.removeClass('ccp-scheduler-editor-loading')
            .addClass('ccp-scheduler-editor-error')
            .text((result && result.error) || $panel.attr('data-load-error') || 'Unable to load task.');
          return;
        }
        populateForm(result);
        $progress.remove();
        $panel.detach().hide().appendTo($holder).fadeIn(160);
        // Avoid jumping away from the selected row as the form expands.
      }).fail(function (xhr, state) {
        if (state === 'abort' || editorRow !== requestedRow) { return; }
        var message = (xhr.responseJSON && xhr.responseJSON.error)
          || $panel.attr('data-load-error') || 'Unable to load task.';
        $progress.removeClass('ccp-scheduler-editor-loading')
          .addClass('ccp-scheduler-editor-error').text(message);
      }).always(function () {
        if (editorRow === requestedRow) { pendingRequest = null; }
      });
    });

    $panel.on('click', '.js-scheduler-cancel', function (event) {
      if (!editorRow) { return; }
      event.preventDefault();
      restoreEditor(true);
    });

    $('#input-scheduler-available').on('change', function () {
      if (+$form.find('input[name="scheduler_id"]').val() > 0) { return; }
      var $selected = $(this).find('option:selected');
      var route = $(this).val();
      if (!route) { return; }
      $form.find('input[name="route"]').val(route);
      $form.find('input[name="code"]').val($selected.attr('data-code') || 'custom.' + route.split('/').pop());
    });

    $add.on('click', function (event) {
      event.preventDefault();
      if (editorRow) { restoreEditor(true); }
      else { resetForm(); }
      if ($panel[0]) { $panel[0].scrollIntoView({behavior: 'smooth', block: 'start'}); }
    });
  });
})(jQuery);
