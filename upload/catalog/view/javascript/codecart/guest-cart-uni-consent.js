/* Optional UniShop2 checkout contact recovery. Loaded only when enabled on that route. */
(function () {
  'use strict';
  function ready(fn) { if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); }
  ready(function () {
    var form = document.querySelector('form[name="unicheckout-customer-form"]');
    if (!form || !window.jQuery) return;
    var base = document.querySelector('base');
    var root = base ? base.href : location.origin + '/';
    var settingsUrl = new URL('index.php?route=extension/module/codecart_guest_cart_contact/settings', root);
    if (settingsUrl.origin !== location.origin) return;
    var settings = null, tick = null, pending = null, stamp = 0;
    var $ = window.jQuery;
    var container = document.createElement('div');
    container.className = 'ccp-cart-recovery-consent';
    container.style.cssText = 'margin:12px 0;padding:10px 12px;border:1px solid #dce5ef;border-radius:5px;';
    var label = document.createElement('label');
    label.style.cssText = 'display:flex;gap:8px;align-items:flex-start;font-weight:400;cursor:pointer;';
    tick = document.createElement('input');
    tick.type = 'checkbox'; tick.name = 'ccp_cart_recovery_consent'; tick.value = '1';
    tick.style.marginTop = '3px';
    var caption = document.createElement('span');
    label.appendChild(tick); label.appendChild(caption); container.appendChild(label);
    // Attach to customer form only, not to newsletter subscription in the footer.
    $.getJSON(settingsUrl.toString()).done(function (cfg) {
      if (!cfg || !cfg.enabled || !/^[a-f0-9]{48}$/.test(cfg.nonce)) return;
      settings = cfg; caption.textContent = cfg.label || '';
      if (!form.isConnected) return;
      form.appendChild(container);
    });
    function send(revoke) {
      if (!settings || !tick) return;
      if (!tick.checked && !revoke) return;
      var input = function (name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; };
      var data = {nonce:settings.nonce,ccp_cart_recovery_consent:revoke?'0':'1'};
      if (!revoke) {
        data.firstname = input('firstname'); data.lastname = input('lastname');
        data.telephone = input('telephone'); data.email = input('email');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) return;
      }
      if (pending) pending.abort();
      pending = $.ajax({url:settings.save_url,type:'POST',data:data,dataType:'json'});
      pending.always(function(){ pending=null; });
    }
    function schedule() {
      if (!settings || !tick.checked) return;
      clearTimeout(stamp);
      stamp = setTimeout(function(){send(false);}, 1100);
    }
    tick.addEventListener('change', function () { if (tick.checked) schedule(); else {clearTimeout(stamp);send(true);} });
    form.addEventListener('input', schedule);
    form.addEventListener('change', function(event) { if (event.target !== tick) schedule(); });
    // Flush on button interaction before UniShop2 starts its own AJAX validation.
    document.addEventListener('click',function(event){
      if (tick && tick.checked && event.target && event.target.closest && event.target.closest('#confirm-button, #button-confirm, .unicheckout__confirm, .unicheckout__button-confirm')) send(false);
    },true);
  });
})();
