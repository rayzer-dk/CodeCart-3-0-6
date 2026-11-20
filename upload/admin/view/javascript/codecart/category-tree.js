(function () {
  'use strict';

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  ready(function () {
    var host = document.getElementById('content');
    var config = document.getElementById('cc-tree-config');
    if (!host || !config || !config.dataset.ccTreeUrl || !window.fetch) { return; }
    var endpoint = config.dataset.ccTreeUrl;
    var autocomplete = config.dataset.ccTreeAutocomplete || '';
    var defaultError = config.dataset.ccTreeError || 'Could not save';
    var chooseMessage = config.dataset.ccTreeChoose || 'Select a category';
    var busy = false;

    function notify(message, ok) {
      var alertBox = host.querySelector('.cc-tree-notification');
      if (!alertBox) {
        alertBox = document.createElement('div');
        alertBox.className = 'cc-tree-notification';
        alertBox.setAttribute('role', 'status');
        host.insertBefore(alertBox, host.firstChild);
      }
      alertBox.className = 'cc-tree-notification alert ' + (ok ? 'alert-success' : 'alert-danger');
      alertBox.textContent = message;
    }

    function request(data) {
      if (busy) { return Promise.reject(new Error('Save already running')); }
      busy = true;
      var form = new URLSearchParams();
      Object.keys(data).forEach(function (key) { form.append(key, String(data[key])); });
      return fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json'},
        body: form.toString()
      }).then(function (response) {
        return response.json().then(function (result) {
          if (!response.ok || !result || result.success !== true) {
            throw new Error(result && result.error ? result.error : defaultError);
          }
          return result;
        });
      }).finally(function () { busy = false; });
    }

    var inputs = host.querySelectorAll('.cc-category-sort[data-cc-sort-id]');
    Array.prototype.forEach.call(host.querySelectorAll('.cc-category-sort-pencil'), function (button) {
      button.addEventListener('click', function () {
        var input = button.parentNode.querySelector('.cc-category-sort');
        if (!input || input.disabled) { return; }
        input.readOnly = false;
        input.focus();
        input.select();
      });
    });
    Array.prototype.forEach.call(inputs, function (input) {
      var save = function () {
        if (busy || input.disabled) { return; }
        var value = input.value.trim();
        var original = input.getAttribute('data-cc-sort-expected');
        if (value === original) { return; }
        if (!/^-?[0-9]{1,7}$/.test(value) || Number(value) < -999999 || Number(value) > 999999) {
          notify(defaultError, false);
          input.value = original;
          return;
        }
        input.disabled = true;
        request({action: 'sort', id: input.getAttribute('data-cc-sort-id'), value: value, expected: original})
          .then(function (result) {
            input.setAttribute('data-cc-sort-expected', String(result.value));
            input.value = String(result.value);
            input.classList.add('is-saved');
            window.setTimeout(function () { input.classList.remove('is-saved'); }, 1200);
          })
          .catch(function (error) {
            input.value = original;
            notify(error.message || defaultError, false);
          })
          .finally(function () { input.disabled = false; });
      };
      input.addEventListener('change', save);
      input.addEventListener('blur', function () { save(); input.readOnly = true; });
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          input.blur();
        }
        if (event.key === 'Escape') {
          input.value = input.getAttribute('data-cc-sort-expected');
          input.blur();
        }
      });
    });

    var rows = Array.prototype.slice.call(host.querySelectorAll('.cc-tree-row[data-cc-tree-id]'));
    if (!rows.length) { return; }

    var collapsed = Object.create(null);
    var expandAll = host.querySelector('.cc-tree-expand-all');
    // Start with the main categories visible; descendants expand in place.
    rows.forEach(function (row) {
      if (row.querySelector('.cc-tree-collapse:not(.is-placeholder)')) {
        collapsed[row.dataset.ccTreeId] = true;
      }
    });
    function refreshTree() {
      rows.forEach(function (row) {
        var ancestors = (row.getAttribute('data-cc-tree-path') || '').split('/').filter(Boolean);
        var hidden = ancestors.some(function (id) { return collapsed[id] === true; });
        row.hidden = hidden;
        var button = row.querySelector('.cc-tree-collapse');
        if (button && !button.disabled) {
          var isCollapsed = collapsed[row.dataset.ccTreeId] === true;
          button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
          button.classList.toggle('is-collapsed', isCollapsed);
        }
      });
    }

    if (expandAll) {
      function syncExpandAll() {
        var anyCollapsed = Object.keys(collapsed).some(function (id) { return collapsed[id] === true; });
        expandAll.setAttribute('aria-expanded', anyCollapsed ? 'false' : 'true');
        expandAll.classList.toggle('is-expanded', !anyCollapsed);
      }
      expandAll.addEventListener('click', function () {
        rows.forEach(function (row) {
          if (row.querySelector('.cc-tree-collapse:not(.is-placeholder)')) {
            collapsed[row.dataset.ccTreeId] = expandAll.getAttribute('aria-expanded') === 'true';
          }
        });
        refreshTree();
        syncExpandAll();
      });
    }
    refreshTree();
    rows.forEach(function (row) {
      var toggle = row.querySelector('.cc-tree-collapse');
      if (toggle && !toggle.disabled && !toggle.classList.contains('is-placeholder')) {
        var nameToggle = row.querySelector('.cc-tree-name-toggle');
        var onToggle = function () {
          var id = row.dataset.ccTreeId;
          collapsed[id] = !collapsed[id];
          refreshTree();
          if (expandAll) { syncExpandAll(); }
        };
        toggle.addEventListener('click', onToggle);
        if (nameToggle) { nameToggle.addEventListener('click', onToggle); }
      }
    });

    var dragging = null;
    var root = host.querySelector('.cc-tree-root[data-cc-tree-target="0"]');
    function validTarget(id) {
      if (!dragging) { return false; }
      if (id === '0') { return true; }
      if (id === dragging) { return false; }
      var target = host.querySelector('.cc-tree-row[data-cc-tree-id="' + id + '"]');
      if (!target) { return false; }
      // List rows do not carry a complete ancestor path; the server performs the authoritative cycle check.
      var ancestors = (target.dataset.ccTreePath || '').split('/').filter(Boolean);
      return ancestors.indexOf(dragging) < 0;
    }
    function move(id, parent) {
      if (id === parent || !/^[0-9]+$/.test(id) || !/^[0-9]+$/.test(parent)) { return; }
      request({action: 'move', id: id, parent_id: parent})
        .then(function (result) { if (result && result.moved === false) { notify(defaultError, false); return; } window.location.reload(); })
        .catch(function (error) { notify(error.message || defaultError, false); });
    }
    function dropOn(target) {
      var source = host.querySelector('.cc-tree-row[data-cc-tree-id="' + dragging + '"]');
      if (source && source.dataset.ccTreeParent === target.dataset.ccTreeParent) {
        request({action:'reorder', id:dragging, before_id:target.dataset.ccTreeId})
          .then(function (result) { if (result.moved === false) { notify(defaultError, false); return; } window.location.reload(); })
          .catch(function (error) { notify(error.message || defaultError, false); });
      } else {
        move(dragging, target.dataset.ccTreeId);
      }
    }
    rows.forEach(function (row) {
      // Drag events are dispatched at the draggable row, not necessarily at
      // the nested grab handle. Only start when pointerdown hit that handle.
      row.addEventListener('pointerdown', function (event) {
        row.dataset.ccDragAllowed = event.target.closest('.cc-tree-handle') ? '1' : '0';
      });
      row.addEventListener('dragstart', function (event) {
        if (row.dataset.ccDragAllowed !== '1') {
          event.preventDefault();
          return;
        }
        dragging = row.dataset.ccTreeId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', dragging);
        row.classList.add('is-dragging');
      });
      row.addEventListener('dragend', function () {
        row.dataset.ccDragAllowed = '0';
        dragging = null;
        rows.forEach(function (node) {
          node.classList.remove('is-over', 'is-dragging');
        });
        if (root) { root.classList.remove('is-over'); }
      });
      row.addEventListener('dragover', function (event) {
        if (!validTarget(row.dataset.ccTreeId)) { return; }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        row.classList.add('is-over');
      });
      row.addEventListener('dragleave', function () { row.classList.remove('is-over'); });
      row.addEventListener('drop', function (event) {
        if (!validTarget(row.dataset.ccTreeId)) { return; }
        event.preventDefault();
        event.stopPropagation();
        row.classList.remove('is-over');
        dropOn(row);
      });
    });
    if (root) {
      root.addEventListener('dragover', function (event) {
        if (!validTarget('0')) { return; }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        root.classList.add('is-over');
      });
      root.addEventListener('dragleave', function () { root.classList.remove('is-over'); });
      root.addEventListener('drop', function (event) {
        if (!validTarget('0')) { return; }
        event.preventDefault();
        root.classList.remove('is-over');
        move(dragging, '0');
      });
    }

    // Touch devices cannot use native HTML5 drag-and-drop reliably.
    // A deliberate handle gesture (12px threshold) moves to a highlighted row.
    var touchStart = null;
    rows.forEach(function (row) {
      var handle = row.querySelector('.cc-tree-handle');
      if (!handle) { return; }
      handle.addEventListener('pointerdown', function (event) {
        if (event.pointerType === 'mouse' || busy) { return; }
        touchStart = {id: row.dataset.ccTreeId, x: event.clientX, y: event.clientY, active: false};
        handle.setPointerCapture(event.pointerId);
      });
      handle.addEventListener('pointermove', function (event) {
        if (!touchStart || touchStart.id !== row.dataset.ccTreeId) { return; }
        if (!touchStart.active && Math.hypot(event.clientX - touchStart.x, event.clientY - touchStart.y) < 12) { return; }
        touchStart.active = true;
        dragging = touchStart.id;
        rows.forEach(function (item) { item.classList.remove('is-over'); });
        if (root) { root.classList.remove('is-over'); }
        var hovered = document.elementFromPoint(event.clientX, event.clientY);
        var target = hovered && hovered.closest('.cc-tree-row[data-cc-tree-id], .cc-tree-root[data-cc-tree-target]');
        if (target) {
          var targetId = target.dataset.ccTreeId || target.dataset.ccTreeTarget;
          if (validTarget(targetId)) { target.classList.add('is-over'); }
        }
      });
      function finishTouch(event, cancelled) {
        if (!touchStart || touchStart.id !== row.dataset.ccTreeId) { return; }
        var start = touchStart;
        touchStart = null;
        if (!cancelled && start.active) {
          var hovered = document.elementFromPoint(event.clientX, event.clientY);
          var target = hovered && hovered.closest('.cc-tree-row[data-cc-tree-id], .cc-tree-root[data-cc-tree-target]');
          var targetId = target && (target.dataset.ccTreeId || target.dataset.ccTreeTarget);
          if (targetId && validTarget(targetId)) { move(start.id, targetId); }
        }
        dragging = null;
        rows.forEach(function (item) { item.classList.remove('is-over'); });
        if (root) { root.classList.remove('is-over'); }
      }
      handle.addEventListener('pointerup', function (event) { finishTouch(event, false); });
      handle.addEventListener('pointercancel', function (event) { finishTouch(event, true); });
    });

    // Accessible keyboard/touch fallback: search for the target category using
    // the already existing admin autocomplete route, or choose the root.
    var modal = window.jQuery ? window.jQuery('#cc-tree-move-modal') : null;
    if (!modal || !modal.length) { return; }
    var currentId = '';
    var parentInput = modal.find('#cc-tree-parent-search');
    var parentId = modal.find('#cc-tree-parent-id');
    var parentLabel = modal.find('.cc-tree-selected-parent');

    rows.forEach(function (row) {
      var button = row.querySelector('.cc-tree-move');
      if (!button) { return; }
      button.addEventListener('click', function () {
        currentId = row.dataset.ccTreeId;
        modal.find('.cc-tree-moving-name').text(button.dataset.ccMoveName || '');
        parentInput.val('');
        parentId.val('0');
        parentLabel.text(modal.find('.cc-tree-select-root').text());
        modal.modal('show');
      });
    });
    modal.find('.cc-tree-select-root').on('click', function () {
      parentId.val('0');
      parentInput.val('');
      parentLabel.text(window.jQuery(this).text());
    });
    parentInput.on('input', function () {
      parentId.val('');
      parentLabel.text(chooseMessage);
    });
    if (window.jQuery.fn.autocomplete && autocomplete) {
      parentInput.autocomplete({
        source: function (term, response) {
          window.jQuery.ajax({
            url: autocomplete + '&filter_name=' + encodeURIComponent(term),
            dataType: 'json',
            success: function (items) {
              response(window.jQuery.map(items, function (item) {
                var id = item.category_id || item.blog_category_id;
                return {label: item.name, value: id};
              }));
            }
          });
        },
        select: function (item) {
          parentId.val(String(item.value));
          parentInput.val(item.label);
          parentLabel.text(item.label);
        }
      });
    }
    modal.find('.cc-tree-confirm-move').on('click', function () {
      var target = String(parentId.val());
      if (!currentId || !/^(0|[1-9][0-9]*)$/.test(target)) {
        parentInput.focus();
        notify(chooseMessage, false);
        return;
      }
      modal.modal('hide');
      move(currentId, target);
    });
  });
}());
