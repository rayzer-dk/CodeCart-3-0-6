/* CodeCart PRO: event-driven admin feedback; no polling or extra dependencies. */
(function (window, document) {
  'use strict';
  var timers = typeof WeakMap === 'function' ? new WeakMap() : null;
  function enabled() {
    if (document.documentElement.getAttribute('data-ccp-motion') !== 'balanced') { return false; }
    return !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }
  function flashRow(node) {
    if (!enabled() || !node || !node.closest) { return; }
    var row = node.closest('tr');
    if (!row || row.classList.contains('ccp-order-preview-row')) { return; }
    if (timers && timers.has(row)) { window.clearTimeout(timers.get(row)); }
    row.classList.remove('ccp-motion-row-saved');
    void row.offsetWidth; // Restart only for an explicit successful save.
    row.classList.add('ccp-motion-row-saved');
    var timeout = window.setTimeout(function () {
      row.classList.remove('ccp-motion-row-saved');
      if (timers) { timers.delete(row); }
    }, 2800);
    if (timers) { timers.set(row, timeout); }
  }
  window.CodeCartAdminMotion = { flashRow: flashRow, enabled: enabled };
})(window, document);
