/* Progressive, accessible presentation of static admin form help text. */
(function () {
  'use strict';
  function enhance(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var groups = scope.querySelectorAll('.form-group');
    for (var i = 0; i < groups.length; i++) {
      var group = groups[i];
      if (group.dataset.ccpHelpEnhanced === '1') { continue; }
      var label = group.querySelector('label.control-label, label[for]');
      if (!label) { continue; }
      var help = null;
      for (var j = 0; j < group.children.length; j++) {
        var child = group.children[j];
        if (child.classList && (child.classList.contains('help-block') || child.classList.contains('form-text'))) {
          help = child;
          break;
        }
      }
      if (!help || help.querySelector('a,button,input,select,textarea') || !help.textContent.trim()) {
        continue;
      }
      if (help.classList.contains('text-danger') || help.classList.contains('has-error')) {
        continue;
      }
      var explanation = help.textContent.trim().replace(/\s+/g, ' ');
      if (explanation.length > 900) { continue; }
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'ccp-admin-help-icon';
      button.setAttribute('aria-label', explanation);
      button.setAttribute('title', explanation);
      button.setAttribute('data-toggle', 'tooltip');
      button.setAttribute('data-placement', 'top');
      var icon = document.createElement('i');
      icon.className = 'fa fa-question-circle';
      icon.setAttribute('aria-hidden', 'true');
      button.appendChild(icon);
      label.insertAdjacentElement('afterend', button);
      help.hidden = true;
      group.dataset.ccpHelpEnhanced = '1';
      if (window.jQuery && window.jQuery.fn.tooltip) {
        window.jQuery(button).tooltip({container:'body', placement:'top'});
      }
    }
  }
  function init() {
    enhance(document);
    if (window.jQuery) {
      window.jQuery(document).on('shown.bs.tab shown.bs.collapse', function () { enhance(document); });
      window.jQuery(document).ajaxComplete(function () { enhance(document); });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, {once:true});
  } else { init(); }
})();
