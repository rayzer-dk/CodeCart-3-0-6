/* Consent-only guest contact bridge for AJAX checkout forms.
 * No contact collection or persistence without unchecked-by-default explicit opt-in.
 * Loaded only on eligible checkout routes when abandoned-cart guest capture is enabled.
 */
(function () {
  'use strict';
  function ready(fn) {
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); }
    else { fn(); }
  }
  ready(function () {
    if (!window.jQuery) { return; }
    var $ = window.jQuery;
    var base = document.querySelector('base');
    var root = base ? base.href : window.location.origin + '/';
    var settingsUrl;
    try {
      settingsUrl = new URL('index.php?route=extension/module/codecart_guest_cart_contact/settings', root);
      if (settingsUrl.origin !== window.location.origin) { return; }
    } catch (ignore) { return; }
    var settings = null, container = null, checkbox = null, feedback = null, timer = 0, pending = null;
    var observer = null, scanTimer = 0;

    function select(parent, selector) {
      if (!selector || !parent) { return null; }
      try { return parent.querySelector(selector); } catch (ignore) { return null; }
    }
    function selectAll(parent, selector) {
      if (!selector || !parent) { return []; }
      try { return Array.prototype.slice.call(parent.querySelectorAll(selector)); }
      catch (ignore) { return []; }
    }
    function field(box, key) {
      var configured = settings && settings.selectors && settings.selectors[key];
      var fallback = {
        firstname: '[name="firstname"],[name="customer[firstname]"],[name="payment_firstname"],[name$="[firstname]"]',
        lastname: '[name="lastname"],[name="customer[lastname]"],[name="payment_lastname"],[name$="[lastname]"]',
        email: '[name="email"],[name="customer[email]"],[name="payment_email"],[name$="[email]"]',
        telephone: '[name="telephone"],[name="phone"],[name="customer[telephone]"],[name="payment_telephone"],[name$="[telephone]"]'
      };
      return select(box, configured) || select(box, fallback[key]);
    }
    function choices() {
      if (!settings) { return []; }
      var custom = (settings.selectors && settings.selectors.form) || '';
      if (custom) { return selectAll(document, custom); }
      return selectAll(document, 'form[name="unicheckout-customer-form"], form#unicheckout-customer-form, #unicheckout form, #checkout-checkout form, #collapse-payment-address form, form#checkout-form, form[name="checkout"]');
    }
    function findContainer() {
      var all = choices();
      for (var i = 0; i < all.length; i++) {
        if (field(all[i], 'email')) { return all[i]; }
      }
      // Some AJAX themes render customer inputs in a container without a form tag.
      // Restrict fallback to a checkout area; never attach to newsletter in the footer.
      if (!settings.selectors || !settings.selectors.form) {
        var areas = selectAll(document, '#checkout-checkout, #unicheckout, #unicheckout-customer, .unicheckout-customer-form');
        for (var j = 0; j < areas.length; j++) {
          if (field(areas[j], 'email')) { return areas[j]; }
        }
      }
      return null;
    }
    function value(key) {
      var input = field(container, key);
      return input ? String(input.value || '').trim() : '';
    }
    function showError() {
      if (feedback && feedback.isConnected) {
        feedback.textContent = (settings && settings.error_label) || '';
        feedback.style.display = 'block';
      }
    }
    function save(revoke) {
      if (!settings || !checkbox || !container || !container.isConnected) { return; }
      if (!revoke && !checkbox.checked) { return; }
      var data = {nonce:settings.nonce, ccp_cart_recovery_consent:revoke ? '0' : '1'};
      if (!revoke) {
        data.email = value('email');
        data.firstname = value('firstname');
        data.lastname = value('lastname');
        data.telephone = value('telephone');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) { showError(); return; }
      }
      if (pending && pending.readyState !== 4) { pending.abort(); }
      var request = $.ajax({url:settings.save_url, type:'POST', data:data, dataType:'json', cache:false});
      pending = request;
      request.done(function (result) {
        if (!result || !result.success) { showError(); }
        else if (feedback) { feedback.style.display = 'none'; feedback.textContent = ''; }
      });
      request.fail(function (xhr, status) { if (status !== 'abort') { showError(); } });
      request.always(function () { if (pending === request) { pending = null; } });
    }
    function schedule() {
      if (!checkbox || !checkbox.checked) { return; }
      window.clearTimeout(timer);
      timer = window.setTimeout(function () { save(false); }, 1100);
    }
    function bind(box) {
      if (!box || !box.isConnected || !field(box, 'email')) { return; }
      if (select(box, 'input[name="ccp_cart_recovery_consent"]')) { return; }
      container = box;
      var wrapper = document.createElement('div');
      wrapper.className = 'ccp-cart-recovery-consent';
      wrapper.style.cssText = 'margin:12px 0;padding:10px 12px;border:1px solid #dce5ef;border-radius:5px;';
      var label = document.createElement('label');
      label.style.cssText = 'display:flex;gap:8px;align-items:flex-start;font-weight:400;cursor:pointer;';
      checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.name = 'ccp_cart_recovery_consent';
      checkbox.value = '1';
      checkbox.checked = false;
      checkbox.style.marginTop = '3px';
      var caption = document.createElement('span');
      caption.textContent = settings.label || '';
      label.appendChild(checkbox);
      label.appendChild(caption);
      wrapper.appendChild(label);
      feedback = document.createElement('small');
      feedback.className = 'ccp-cart-recovery-error';
      feedback.setAttribute('role', 'alert');
      feedback.style.cssText = 'display:none;color:#ac3030;margin-top:5px;line-height:1.45;';
      wrapper.appendChild(feedback);
      box.appendChild(wrapper);
      checkbox.addEventListener('change', function () {
        if (checkbox.checked) { schedule(); }
        else { window.clearTimeout(timer); save(true); }
      });
      box.addEventListener('input', schedule);
      box.addEventListener('change', function (e) { if (e.target !== checkbox) { schedule(); } });
    }
    function scan() {
      scanTimer = 0;
      if (!settings) { return; }
      if (container && container.isConnected && checkbox && checkbox.isConnected) { return; }
      container = null;
      checkbox = null;
      feedback = null;
      bind(findContainer());
    }
    function queueScan() {
      if (scanTimer) { return; }
      scanTimer = window.setTimeout(scan, 120);
    }
    $.getJSON(settingsUrl.toString()).done(function (cfg) {
      if (!cfg || !cfg.enabled || !/^[a-f0-9]{48}$/.test(cfg.nonce)) { return; }
      try {
        var saveUrl = new URL(cfg.save_url, root);
        if (saveUrl.origin !== window.location.origin || saveUrl.protocol !== 'https:') { return; }
      } catch (ignore) { return; }
      settings = cfg;
      scan();
      // AJAX checkout forms can appear or be replaced after DOMContentLoaded.
      observer = new MutationObserver(queueScan);
      observer.observe(document.body || document.documentElement, {childList:true, subtree:true});
    });
    document.addEventListener('click', function (event) {
      if (!checkbox || !checkbox.checked || !event.target || !event.target.closest) { return; }
      if (event.target.closest('#confirm-button, #button-confirm, #button-guest, .unicheckout__confirm, .unicheckout__button-confirm')) {
        save(false);
      }
    }, true);
  });
})();
