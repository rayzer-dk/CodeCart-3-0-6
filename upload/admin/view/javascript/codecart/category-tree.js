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

    Array.prototype.forEach.call(host.querySelectorAll('.cc-category-index-toggle[data-cc-index-id]'), function (input) {
      input.addEventListener('change', function () {
        if (busy) { input.checked = !input.checked; return; }
        var previous = input.getAttribute('data-cc-index-expected');
        var next = input.checked ? '1' : '0';
        if (next === previous) { return; }
        input.disabled = true;
        request({action:'index', id:input.getAttribute('data-cc-index-id'), expected:previous, value:next})
          .then(function (result) {
            input.setAttribute('data-cc-index-expected', String(result.value));
            input.checked = String(result.value) === '1';
             if (window.CodeCartAdminMotion) { window.CodeCartAdminMotion.flashRow(input); }
          })
          .catch(function (error) {
            input.checked = previous === '1';
            notify(error.message || defaultError, false);
          })
          .finally(function () { input.disabled = false; });
      });
    });

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
            if (window.CodeCartAdminMotion) { window.CodeCartAdminMotion.flashRow(input); }
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
    // Reopen the destination branch after a successful server-side move.
    // Otherwise the item is saved but hidden inside the default-collapsed tree.
    var restoreKey = 'ccp-tree-reveal-' + window.location.pathname + '-' + (config.dataset.ccTreeKind || 'catalog');
    var revealId = '';
    var changedRowKey = restoreKey + '-saved';
    var changedRowId = '';
    try {
      changedRowId = window.sessionStorage.getItem(changedRowKey) || '';
      window.sessionStorage.removeItem(changedRowKey);
    } catch (ignore) {}
    try {
      revealId = window.sessionStorage.getItem(restoreKey) || '';
      window.sessionStorage.removeItem(restoreKey);
    } catch (ignore) {}
    if (/^[1-9][0-9]*$/.test(revealId)) {
      var revealRow = host.querySelector('.cc-tree-row[data-cc-tree-id="' + revealId + '"]');
      if (revealRow) {
        (revealRow.dataset.ccTreePath || '').split('/').filter(Boolean).concat([revealId]).forEach(function (id) {
          collapsed[id] = false;
        });
      }
    }
    function rememberDestination(id) {
      try { window.sessionStorage.setItem(restoreKey, String(id)); } catch (ignore) {}
    }
    function rememberChangedRow(id) {
      if (!window.CodeCartAdminMotion || !window.CodeCartAdminMotion.enabled()) { return; }
      try { window.sessionStorage.setItem(changedRowKey, String(id)); } catch (ignore) {}
    }

    function refreshTree() {
      rows.forEach(function (row) {
        var ancestors = (row.getAttribute('data-cc-tree-path') || '').split('/').filter(Boolean);
        var hidden = ancestors.some(function (id) { return collapsed[id] === true; });
        if (row.hidden !== hidden) { row.hidden = hidden; }
        var button = row.querySelector('.cc-tree-collapse');
        if (button && !button.disabled) {
          var isCollapsed = collapsed[row.dataset.ccTreeId] === true;
          button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
          var nameButton = row.querySelector('.cc-tree-name-toggle');
          if (nameButton) {
            nameButton.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            nameButton.setAttribute('title', isCollapsed ? (button.getAttribute('title') || '') : (button.getAttribute('data-collapse-label') || button.getAttribute('title') || ''));
          }
          button.classList.toggle('is-collapsed', isCollapsed);
          var icon = button.querySelector('i');
          if (icon) {
            icon.classList.remove('fa-angle-up', 'fa-angle-down', 'fa-angle-right');
            icon.classList.add(isCollapsed ? 'fa-angle-right' : 'fa-angle-down');
          }
        }
      });
    }

    function syncExpandAll() {
        var anyCollapsed = Object.keys(collapsed).some(function (id) { return collapsed[id] === true; });
        expandAll.setAttribute('aria-expanded', anyCollapsed ? 'false' : 'true');
        expandAll.classList.toggle('is-expanded', !anyCollapsed);
        var icon = expandAll.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-angles-up', 'fa-angles-down');
          icon.classList.add(anyCollapsed ? 'fa-angles-down' : 'fa-angles-up');
        }
        var caption = expandAll.querySelector('.cc-tree-expand-label');
        var nextLabel = expandAll.getAttribute(anyCollapsed ? 'data-expand-label' : 'data-collapse-label');
        if (caption && nextLabel) { caption.textContent = nextLabel; }
        if (nextLabel) { expandAll.setAttribute('title', nextLabel); expandAll.setAttribute('aria-label', nextLabel); }
    }
    if (expandAll) {
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
    if (expandAll) { syncExpandAll(); }
    if (/^[1-9][0-9]*$/.test(changedRowId) && window.CodeCartAdminMotion) {
      var changedRow = host.querySelector('.cc-tree-row[data-cc-tree-id="' + changedRowId + '"]');
      if (changedRow && !changedRow.hidden) { window.CodeCartAdminMotion.flashRow(changedRow); }
    }
    var treeTable = host.querySelector('.cc-category-list-drag');
    if (treeTable) { treeTable.classList.add('cc-tree-ready'); }
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
        if (nameToggle) {
          nameToggle.setAttribute('aria-expanded', collapsed[row.dataset.ccTreeId] ? 'false' : 'true');
          nameToggle.addEventListener('click', onToggle);
        }
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
        .then(function (result) { if (result && result.moved === false) { notify(defaultError, false); return; } if (parent !== '0') { rememberDestination(parent); } rememberChangedRow(id); window.location.reload(); })
        .catch(function (error) { notify(error.message || defaultError, false); });
    }
    function dropOn(target, event) {
      var source = host.querySelector('.cc-tree-row[data-cc-tree-id="' + dragging + '"]');
      if (!source || source === target || !validTarget(target.dataset.ccTreeId)) { return; }
      // Only the top quarter means "reorder before". The rest means
      // "move inside this category", even when both rows are siblings.
      var rect = target.getBoundingClientRect();
      var sortBefore = !!event && event.clientY <= rect.top + rect.height * 0.25;
      if (sortBefore && source.dataset.ccTreeParent === target.dataset.ccTreeParent) {
        request({action:'reorder', id:dragging, before_id:target.dataset.ccTreeId})
          .then(function (result) {
            if (result.moved === false) { notify(defaultError, false); return; }
            rememberDestination(target.dataset.ccTreeId);
            rememberChangedRow(dragging);
            window.location.reload();
          }).catch(function (error) { notify(error.message || defaultError, false); });
      } else {
        move(dragging, target.dataset.ccTreeId);
      }
    }
    rows.forEach(function (row) {
      // Drag events are dispatched at the draggable row, not necessarily at
      // the nested grab handle. Only start when pointerdown hit that handle.
      row.addEventListener('pointerdown', function (event) {
        row.dataset.ccDragAllowed = event.target && event.target.closest('.cc-tree-handle') ? '1' : '0';
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
        dropOn(row, event);
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

    // Pointer capture avoids native HTML5 table-row dragging inconsistencies.
    if (window.PointerEvent) {
      rows.forEach(function (row) { row.draggable = false; });
    }
    function pointerTarget(x, y) {
      var hovered = document.elementFromPoint(x, y);
      var target = hovered && hovered.closest('.cc-tree-row[data-cc-tree-id], .cc-tree-root[data-cc-tree-target]');
      if (root) {
        var box = root.getBoundingClientRect();
        if (x >= box.left && x <= box.right && y >= box.top && y <= box.bottom) {
          return root;
        }
      }
      return target;
    }

    function scrollForRoot(event) {
      if (event.clientY < 105 && window.scrollY > 0) { window.scrollBy(0, -22); }
      var table = host.querySelector('.cc-category-list-drag');
      var wrapper = table && table.closest('.table-responsive');
      if (wrapper && wrapper.scrollTop > 0) {
        var rect = wrapper.getBoundingClientRect();
        if (event.clientY < rect.top + 45) {
          wrapper.scrollTop = Math.max(0, wrapper.scrollTop - 20);
        }
      }
    }
    var pointerDrag = null;
    rows.forEach(function (row) {
      var handle = row.querySelector('.cc-tree-handle');
      if (!handle || !window.PointerEvent) { return; }
      handle.addEventListener('pointerdown', function (event) {
        if (busy || (event.pointerType === 'mouse' && event.button !== 0)) { return; }
        pointerDrag = {id: row.dataset.ccTreeId, pointerId: event.pointerId,
          x: event.clientX, y: event.clientY, active: false};
        if (handle.setPointerCapture) { handle.setPointerCapture(event.pointerId); }
      });
      handle.addEventListener('pointermove', function (event) {
        if (!pointerDrag || pointerDrag.id !== row.dataset.ccTreeId ||
            pointerDrag.pointerId !== event.pointerId) { return; }
        if (!pointerDrag.active &&
            Math.hypot(event.clientX - pointerDrag.x, event.clientY - pointerDrag.y) < 9) { return; }
        pointerDrag.active = true;
        dragging = pointerDrag.id;
        row.classList.add('is-dragging');
        event.preventDefault();
        rows.forEach(function (item) { item.classList.remove('is-over'); });
        if (root) { root.classList.remove('is-over'); }
        scrollForRoot(event);
        var target = pointerTarget(event.clientX, event.clientY);
        if (target && !target.hidden) {
          var targetId = target.dataset.ccTreeId || target.dataset.ccTreeTarget;
          if (validTarget(targetId)) { target.classList.add('is-over'); }
        }
      });
      function finishPointer(event, cancelled) {
        if (!pointerDrag || pointerDrag.id !== row.dataset.ccTreeId ||
            pointerDrag.pointerId !== event.pointerId) { return; }
        var start = pointerDrag;
        pointerDrag = null;
        if (!cancelled && start.active) {
          var target = pointerTarget(event.clientX, event.clientY);
          var targetId = target && (target.dataset.ccTreeId || target.dataset.ccTreeTarget);
          if (targetId && validTarget(targetId)) {
            if (target.dataset.ccTreeTarget === '0') { move(start.id, '0'); }
            else { dropOn(target, event); }
          }
        }
        dragging = null;
        rows.forEach(function (item) { item.classList.remove('is-over', 'is-dragging'); });
        if (root) { root.classList.remove('is-over'); }
        if (handle.hasPointerCapture && handle.hasPointerCapture(event.pointerId)) {
          handle.releasePointerCapture(event.pointerId);
        }
      }
      handle.addEventListener('pointerup', function (event) { finishPointer(event, false); });
      handle.addEventListener('pointercancel', function (event) { finishPointer(event, true); });
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
