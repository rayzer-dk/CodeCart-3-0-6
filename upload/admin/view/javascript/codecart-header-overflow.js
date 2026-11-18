/* CodeCart mobile header: move third-party icon DOM nodes into an overflow panel.
 * Original nodes (with listeners, forms and dropdowns) are never cloned.
 * The normal desktop header is reconstructed on resize. */
(function () {
  'use strict';
  function init() {
    var header = document.getElementById('header');
    if (!header) { return; }
    var nav = header.querySelector('.navbar-right');
    var more = header.querySelector('.ccp-header-more');
    var trigger = document.getElementById('ccp-header-more-toggle');
    var panel = document.getElementById('ccp-header-overflow-panel');
    var box = panel && panel.querySelector('.ccp-header-overflow-items');
    if (!nav || !more || !trigger || !box) { return; }

    var tracked = Array.prototype.slice.call(nav.children).filter(function (li) { return li !== more; });
    var busy = false;
    var scheduled = false;

    function closePanel() {
      panel.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    }
    function label(node, restore) {
      var current = node.querySelector('.ccp-header-overflow-label');
      if (restore) {
        if (current) { current.remove(); }
        return;
      }
      var a = node.querySelector(':scope > a, :scope > button');
      if (!a || current) { return; }
      var name = a.getAttribute('aria-label') || a.getAttribute('title') || '';
      if (name) {
        var span = document.createElement('span');
        span.className = 'ccp-header-overflow-label';
        span.textContent = name;
        a.appendChild(span);
      }
    }
    function isPinned(node) {
      return node.classList.contains('ccp-admin-profile') ||
        !!node.querySelector(':scope > a[href*="common/logout"]');
    }
    function rank(node) {
      var builtIn = 'ccp-modification-refresh-menu ccp-admin-theme-menu ccp-admin-language-menu ccp-notification-menu ccp-admin-profile'.split(' ');
      if (!builtIn.some(function (cls) { return node.classList.contains(cls); }) && !isPinned(node)) { return 0; }
      if (node.classList.contains('ccp-modification-refresh-menu')) { return 1; }
      if (node.classList.contains('ccp-admin-theme-menu')) { return 2; }
      if (node.classList.contains('ccp-admin-language-menu')) { return 3; }
      if (node.classList.contains('ccp-notification-menu')) { return 4; }
      return 10;
    }
    function availableWidth() {
      var parent = nav.parentElement;
      var width = parent ? parent.getBoundingClientRect().width : window.innerWidth;
      if (!parent) { return width; }
      Array.prototype.slice.call(parent.children).forEach(function (item) {
        if (item === nav || item === panel || item.hidden) { return; }
        var style = window.getComputedStyle(item);
        if (style.display === 'none' || style.position === 'absolute') { return; }
        width -= item.getBoundingClientRect().width;
      });
      return Math.max(0, Math.min(window.innerWidth, width) - 8);
    }
    function overflows() {
      var width = 0;
      Array.prototype.slice.call(nav.children).forEach(function (item) {
        if (item.hidden) { return; }
        var style = window.getComputedStyle(item);
        if (style.display === 'none') { return; }
        width += Math.max(item.getBoundingClientRect().width, 40);
      });
      // A flex row that extends outside the viewport may report scrollWidth
      // equal to clientWidth, even while multiple icons are invisible.
      return width > availableWidth() + 1;
    }
    function refresh() {
      if (busy) { return; }
      busy = true;
      try {
        closePanel();
        tracked.forEach(function (item) {
          label(item, true);
          nav.insertBefore(item, more);
        });
        more.hidden = true;
        if (!window.matchMedia('(max-width: 767px)').matches) { return; }
        if (!overflows()) { return; }

        more.hidden = false;
        var candidates = tracked.filter(function (node) { return !isPinned(node); });
        candidates.sort(function (a, b) { return rank(a) - rank(b); });
        for (var i = 0; i < candidates.length && overflows(); i++) {
          var node = candidates[i];
          label(node, false);
          box.appendChild(node);
        }
        more.hidden = box.children.length === 0;
      } finally { busy = false; }
    }
    function schedule() {
      if (scheduled) { return; }
      scheduled = true;
      window.requestAnimationFrame(function () { scheduled = false; refresh(); });
    }
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      var open = panel.hidden;
      panel.hidden = !open;
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (event) {
      if (!panel.hidden && !panel.contains(event.target) && !more.contains(event.target)) { closePanel(); }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !panel.hidden) { closePanel(); trigger.focus(); }
    });
    window.addEventListener('resize', schedule, {passive:true});
    window.addEventListener('load', schedule);
    if (typeof MutationObserver !== 'undefined') {
      new MutationObserver(function () {
        var additions = Array.prototype.slice.call(nav.children).filter(function (item) {
          return item !== more && tracked.indexOf(item) === -1;
        });
        if (additions.length) {
          tracked = tracked.concat(additions);
          nav.appendChild(more);
          schedule();
        }
      }).observe(nav, {childList:true});
    }
    refresh();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
}());
