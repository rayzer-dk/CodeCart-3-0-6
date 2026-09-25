(function (window, document) {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
  }

  function isDesktop() {
    return window.matchMedia ? window.matchMedia('(min-width: 992px)').matches : window.innerWidth >= 992;
  }

  function initMobile(menu) {
    var mobile = menu.querySelector('.ccp-mobile-catalog');
    var body = mobile ? mobile.querySelector('#ccp-mobile-catalog-body') : null;
    if (!mobile || !body || mobile.getAttribute('data-ccp-mobile-ready') === '1') { return; }
    mobile.setAttribute('data-ccp-mobile-ready', '1');

    var backdrop = mobile.querySelector('.ccp-mobile-catalog-backdrop');
    var closeButton = mobile.querySelector('.ccp-mobile-drawer-close');
    var openButton = mobile.querySelector('.ccp-mobile-catalog-button');
    var $body = window.jQuery ? window.jQuery(body) : null;

    function setOverlay(open) {
      document.documentElement.classList.toggle('ccp-mobile-menu-open', !!open);
      document.body.classList.toggle('ccp-mobile-menu-open', !!open);
      if (backdrop) { backdrop.setAttribute('aria-hidden', open ? 'false' : 'true'); }
    }

    function setBranch(branch, button, open) {
      if (!branch) { return; }
      branch.classList.remove('collapsing');
      branch.classList.add('collapse');
      branch.classList.toggle('in', !!open);
      branch.style.height = '';
      branch.setAttribute('aria-hidden', open ? 'false' : 'true');
      var item = branch.parentNode;
      if (item && item.classList) { item.classList.toggle('is-open', !!open); }
      if (button) {
        button.classList.toggle('collapsed', !open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    }

    function resetBranches() {
      var branches = mobile.querySelectorAll('.ccp-mobile-catalog-children.in');
      for (var i = 0; i < branches.length; i++) {
        var selector = '#' + branches[i].id;
        var button = branches[i].id ? mobile.querySelector('.ccp-mobile-branch-toggle[data-target="' + selector + '"]') : null;
        setBranch(branches[i], button, false);
      }
    }

    function setDrawer(open) {
      body.classList.remove('collapsing');
      body.classList.add('collapse');
      body.classList.toggle('in', !!open);
      body.style.height = '';
      if (openButton) {
        openButton.classList.toggle('collapsed', !open);
        openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      setOverlay(!!open);
      if (!open) { resetBranches(); }
    }

    function closeDrawer() {
      setDrawer(false);
    }

    if ($body) {
      $body.off('.ccpMobileCatalog')
        .on('show.bs.collapse.ccpMobileCatalog shown.bs.collapse.ccpMobileCatalog', function (event) {
          if (event.target === body) { setOverlay(true); }
        })
        .on('hide.bs.collapse.ccpMobileCatalog hidden.bs.collapse.ccpMobileCatalog', function (event) {
          if (event.target === body) { resetBranches(); setOverlay(false); }
        });

      window.jQuery(mobile).off('.ccpMobileBranch')
        .on('show.bs.collapse.ccpMobileBranch', '.ccp-mobile-catalog-children', function (event) {
          if (event.target !== this) { return; }
          this.setAttribute('aria-hidden', 'false');
          var item = this.parentNode;
          if (item && item.classList) { item.classList.add('is-open'); }
        })
        .on('hide.bs.collapse.ccpMobileBranch hidden.bs.collapse.ccpMobileBranch', '.ccp-mobile-catalog-children', function (event) {
          if (event.target !== this) { return; }
          this.setAttribute('aria-hidden', 'true');
          var item = this.parentNode;
          if (item && item.classList) { item.classList.remove('is-open'); }
        });
    }

    var branchButtons = mobile.querySelectorAll('.ccp-mobile-branch-toggle[data-target]');
    for (var b = 0; b < branchButtons.length; b++) {
      branchButtons[b].addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var selector = this.getAttribute('data-target') || '';
        var branch = selector && selector.charAt(0) === '#' ? mobile.querySelector(selector) : null;
        if (!branch) { return; }
        setBranch(branch, this, !branch.classList.contains('in'));
      }, false);
    }

    if (openButton) {
      openButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        setDrawer(!body.classList.contains('in'));
      }, false);
    }

    if (backdrop) { backdrop.addEventListener('click', closeDrawer, false); }
    if (closeButton) { closeButton.addEventListener('click', closeDrawer, false); }

    window.addEventListener('resize', function () { if (isDesktop()) { closeDrawer(); } });
  }

  function initHorizontal(menu) {
    var desktop = menu.querySelector('.ccp-horizontal-desktop');
    if (!desktop || desktop.getAttribute('data-ccp-horizontal-ready') === '1') { return; }
    desktop.setAttribute('data-ccp-horizontal-ready', '1');

    var triggers = desktop.querySelectorAll('[data-ccp-menu-panel]');
    var topItems = desktop.querySelectorAll('.ccp-horizontal-tabs > li');
    var panels = desktop.querySelectorAll('.ccp-horizontal-panel');
    var closeTimer = 0;

    function clearTimer() { if (closeTimer) { window.clearTimeout(closeTimer); closeTimer = 0; } }
    function closePanels() {
      clearTimer();
      for (var i = 0; i < triggers.length; i++) { triggers[i].classList.remove('is-panel-open'); }
      for (var j = 0; j < panels.length; j++) { panels[j].classList.remove('is-open'); panels[j].setAttribute('aria-hidden', 'true'); }
    }
    function openPanel(trigger) {
      if (!isDesktop() || !trigger) { return; }
      var id = trigger.getAttribute('data-ccp-menu-panel');
      var panel = id ? document.getElementById(id) : null;
      if (!panel) { return; }
      clearTimer();
      for (var i = 0; i < triggers.length; i++) { triggers[i].classList.toggle('is-panel-open', triggers[i] === trigger); }
      for (var j = 0; j < panels.length; j++) {
        var active = panels[j] === panel;
        panels[j].classList.toggle('is-open', active);
        panels[j].setAttribute('aria-hidden', active ? 'false' : 'true');
      }
    }
    function scheduleClose() { clearTimer(); closeTimer = window.setTimeout(closePanels, 220); }

    for (var i = 0; i < triggers.length; i++) {
      (function (trigger) {
        trigger.addEventListener('mouseenter', function () { openPanel(trigger); }, false);
        trigger.addEventListener('focusin', function () { openPanel(trigger); }, false);
      })(triggers[i]);
    }
    for (var t = 0; t < topItems.length; t++) {
      (function (item) {
        if (item.hasAttribute('data-ccp-menu-panel')) { return; }
        item.addEventListener('mouseenter', function () { closePanels(); }, false);
        item.addEventListener('focusin', function () { closePanels(); }, false);
      })(topItems[t]);
    }
    for (var p = 0; p < panels.length; p++) {
      panels[p].addEventListener('mouseenter', clearTimer, false);
      panels[p].addEventListener('mouseleave', scheduleClose, false);
    }
    desktop.addEventListener('mouseleave', scheduleClose, false);
    desktop.addEventListener('mouseenter', clearTimer, false);
    desktop.addEventListener('keydown', function (event) { if (event.key === 'Escape' || event.keyCode === 27) { closePanels(); } }, false);
    window.addEventListener('resize', function () { if (!isDesktop()) { closePanels(); } });
  }

  function initVertical(menu) {
    var dropdown = menu.querySelector('.ccp-catalog-dropdown');
    var popup = menu.querySelector('.ccp-catalog-menu');
    var level1 = menu.querySelector('.ccp-catalog-level1');
    if (!dropdown || !popup || !level1) { return; }

    function fit(item) {
      if (!isDesktop()) { popup.style.minHeight = ''; level1.style.minHeight = ''; return; }
      popup.style.minHeight = ''; level1.style.minHeight = '';
      var sub = item ? item.querySelector('.ccp-catalog-level2') : null;
      var height = Math.max(level1.scrollHeight || 0, sub ? sub.scrollHeight || 0 : 0);
      if (height) { popup.style.minHeight = height + 'px'; level1.style.minHeight = height + 'px'; if (sub) { sub.style.minHeight = height + 'px'; } }
    }

    var items = [];
    for (var i = 0; i < level1.children.length; i++) {
      if (level1.children[i].classList && level1.children[i].classList.contains('ccp-catalog-item')) { items.push(level1.children[i]); }
    }
    for (var j = 0; j < items.length; j++) {
      (function (item) {
        item.addEventListener('mouseenter', function () { fit(item); }, false);
        item.addEventListener('focusin', function () { fit(item); }, false);
      })(items[j]);
    }
    if (window.jQuery) { window.jQuery(dropdown).on('shown.bs.dropdown', function () { fit(null); }); }
  }

  ready(function () {
    var menu = document.getElementById('menu');
    if (!menu) { return; }
    initMobile(menu);
    if (menu.classList.contains('ccp-menu-horizontal-mode')) { initHorizontal(menu); }
    if (menu.classList.contains('ccp-menu-vertical-mode')) { initVertical(menu); }
  });
})(window, document);
