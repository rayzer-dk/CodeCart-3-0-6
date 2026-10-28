/* Explicit reminder consent for the current logged-in checkout session. */
(function () {
  'use strict';
  function ready(fn) { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); }
  ready(function () {
    var holder = document.querySelector('#ccq-contact-card, #checkout-checkout #content, #content .unicheckout, #content');
    if (!holder || !window.jQuery) return;
    var base = document.querySelector('base');
    var endpoint = new URL('index.php?route=extension/module/codecart_registered_cart_consent/settings', base ? base.href : location.origin + '/');
    if (endpoint.origin !== location.origin) return;
    var $ = window.jQuery;
    $.getJSON(endpoint.href).done(function (cfg) {
      if (!cfg || !cfg.enabled || !/^[a-f0-9]{48}$/.test(cfg.nonce)) return;
      var row = document.createElement('div');
      row.className = 'ccp-registered-cart-consent';
      row.style.cssText = 'margin:10px 0;padding:9px 12px;border:1px solid #d6e2ef;border-radius:5px;';
      var label = document.createElement('label');
      label.style.cssText = 'display:flex;gap:8px;font-weight:400;align-items:flex-start;';
      var box = document.createElement('input'); box.type = 'checkbox'; box.name = 'ccp_registered_cart_consent'; box.value = '1'; box.checked = !!cfg.checked;
      var span = document.createElement('span'); span.textContent = cfg.label || '';
      label.appendChild(box); label.appendChild(span); row.appendChild(label);
      holder.insertBefore(row, holder.firstChild);
      box.addEventListener('change', function () {
        $.ajax({url:cfg.save_url,method:'POST',data:{nonce:cfg.nonce,consent:box.checked?'1':'0'},dataType:'json'})
        .done(function (result) { if (!result || !result.success) box.checked = false; })
        .fail(function () { box.checked = false; });
      });
    });
  });
})();
