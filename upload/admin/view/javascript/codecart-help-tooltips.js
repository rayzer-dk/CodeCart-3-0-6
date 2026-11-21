/* Present non-interactive field descriptions as accessible, compact help icons. */
(function () {
  'use strict';
  function enhance(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var groups = scope.querySelectorAll('.form-group');
    for (var i = 0; i < groups.length; i++) {
      var group = groups[i];
      if (group.dataset.ccpHelpEnhanced === '1') { continue; }
      var label = group.querySelector('label.control-label, label[for]');
      if (!label || label.querySelector('.ccp-admin-help-icon') ||
          group.querySelector('.ccp-admin-help-icon')) { continue; }
      var help = group.querySelector('.help-block, .form-text, .text-muted');
      if (!help || help.closest('label') || help.closest('.alert, .invalid-feedback, .has-error') ||
          help.querySelector('a, button, input, select, textarea, code, pre, ul, ol, table') ||
          help.classList.contains('text-danger') || help.classList.contains('has-error')) { continue; }
      var explanation = (help.textContent || '').trim().replace(/\s+/g, ' ');
      if (!explanation || explanation.length > 1500) { continue; }
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'ccp-admin-help-icon';
      button.setAttribute('aria-label', explanation);
      button.setAttribute('title', explanation);
      button.setAttribute('data-toggle', 'tooltip');
      button.setAttribute('data-placement', 'top');
      var icon = document.createElement('i');
      icon.className = 'fa-solid fa-circle-question';
      icon.setAttribute('aria-hidden', 'true');
      button.appendChild(icon);
      label.insertAdjacentElement('afterend', button);
      help.hidden = true;
      group.dataset.ccpHelpEnhanced = '1';
      if (window.jQuery && window.jQuery.fn.tooltip) {
        window.jQuery(button).tooltip({container:'body', placement:'top', trigger:'hover focus', html:false});
      }
    }
  }
  function init() {
    enhance(document);
    if (window.jQuery) {
      window.jQuery(document).on('shown.bs.tab shown.bs.collapse', function () { enhance(document); });
      window.jQuery(document).ajaxComplete(function () { enhance(document); });
    }
    if (window.MutationObserver) {
      var observer = new MutationObserver(function (mutations) {
        for (var i=0; i<mutations.length; i++) {
          if (mutations[i].addedNodes.length) { enhance(document); break; }
        }
      });
      observer.observe(document.body, {childList:true, subtree:true});
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, {once:true});
  } else { init(); }
})();
