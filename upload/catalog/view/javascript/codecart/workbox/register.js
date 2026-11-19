/* CodeCart PRO: register the optional static-asset Workbox worker.
 * Third-party Service Workers are never replaced. The HTML only loads this file when ON.
 */
(function () {
  'use strict';
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
  var pwaEnabled = document.documentElement.getAttribute('data-ccp-pwa') === '1';
  var workboxEnabled = document.documentElement.getAttribute('data-ccp-workbox') === '1';
  if (!pwaEnabled && !workboxEnabled) return;
  var baseElement = document.querySelector('base[href]');
  var baseUrl;
  try {
    baseUrl = new URL(baseElement ? baseElement.href : document.baseURI, window.location.href);
  } catch (error) { return; }
  if (baseUrl.origin !== window.location.origin) return;
  var staticWorker = new URL('codecart-sw.js', baseUrl);
  var appWorker = new URL('codecart-app-sw.js', baseUrl);
  var workerUrl = workboxEnabled ? staticWorker : appWorker;
  var owned = [staticWorker.href, appWorker.href];
  var scopeUrl = new URL('./', baseUrl);

  // Show an install suggestion only when the PWA feature is enabled and the browser supports it.
  // Remember dismissals per store, and respect the administrator's configured number of days.
  if (pwaEnabled) {
    var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    if (!standalone) {
      var ua = navigator.userAgent || '';
      var ios = /iPhone|iPad|iPod/.test(ua) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
      var safari = /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS|Chrome|Chromium|Edg|OPR/.test(ua);
      var macSafari = safari && /Macintosh/.test(ua) && !ios;
      var labels = document.documentElement.dataset;
      var days = parseInt(labels.ccpPwaPromptDays, 10);
      if (!isFinite(days) || days < 1 || days > 90) days = 7;
      var storageKey = 'ccp-pwa-dismissed:' + scopeUrl.pathname;
      var deferredPrompt = null;
      var installBar = null;

      function isDismissed() {
        try {
          var at = parseInt(localStorage.getItem(storageKey) || '0', 10);
          return at > 0 && Date.now() < at + days * 86400000;
        } catch (ignore) { return false; }
      }

      function dismissInstall() {
        try { localStorage.setItem(storageKey, String(Date.now())); } catch (ignore) {}
        if (installBar) { installBar.remove(); installBar = null; }
      }

      function createInstallBar() {
        if (installBar || isDismissed() || document.documentElement.getAttribute('data-ccp-pwa') !== '1') return;
        if (!document.body) return;
        var bar = document.createElement('aside');
        bar.className = 'ccp-pwa-install-banner';
        bar.setAttribute('aria-label', labels.ccpPwaInstall || 'Install app');
        var content = document.createElement('div');
        content.className = 'ccp-pwa-install-banner-content';
        var copy = document.createElement('div');
        copy.className = 'ccp-pwa-install-copy';
        var title = document.createElement('strong');
        title.textContent = labels.ccpPwaBannerTitle || 'Install our store';
        var description = document.createElement('span');
        description.textContent = labels.ccpPwaBannerDesc || 'Add this store to your device for quick access.';
        copy.appendChild(title);
        copy.appendChild(description);
        var action = document.createElement('button');
        action.type = 'button';
        action.className = 'ccp-pwa-install-action';
        action.textContent = labels.ccpPwaInstall || 'Install';
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'ccp-pwa-install-dismiss';
        close.setAttribute('aria-label', labels.ccpPwaDismiss || 'Close');
        close.setAttribute('title', labels.ccpPwaDismiss || 'Close');
        close.textContent = '\u00d7';
        close.addEventListener('click', dismissInstall);
        action.addEventListener('click', function () {
          if (deferredPrompt) {
            var prompt = deferredPrompt;
            deferredPrompt = null;
            action.disabled = true;
            prompt.prompt();
            Promise.resolve(prompt.userChoice).then(function (choice) {
              action.disabled = false;
              if (choice && choice.outcome === 'accepted') dismissInstall();
              else dismissInstall();
            }).catch(function () { action.disabled = false; dismissInstall(); });
          } else if (ios || macSafari) {
            description.textContent = ios
              ? (labels.ccpPwaIosHelp || 'Safari: Share > Add to Home Screen')
              : (labels.ccpPwaMacHelp || 'Safari: File > Add to Dock');
            action.hidden = true;
          }
        });
        content.appendChild(copy);
        content.appendChild(action);
        content.appendChild(close);
        bar.appendChild(content);
        document.body.insertBefore(bar, document.body.firstChild);
        installBar = bar;
      }

      function whenReady(callback) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback, {once: true});
        else callback();
      }

      window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        whenReady(createInstallBar);
      });
      window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        dismissInstall();
      });
      if (ios || macSafari) whenReady(createInstallBar);
    }
  }

  window.addEventListener('load', function () {
    navigator.serviceWorker.getRegistration(scopeUrl.href).then(function (registration) {
      if (registration && registration.scope !== scopeUrl.href) return;
      var workers = registration ? [registration.active, registration.waiting, registration.installing] : [];
      if (workers.some(function (worker) {
        return worker && owned.indexOf(worker.scriptURL) === -1;
      })) return;
      return navigator.serviceWorker.register(workerUrl.href, {
        scope: scopeUrl.pathname,
        updateViaCache: 'none'
      });
    }).catch(function () {
      // Network, worker and browser failures must not affect checkout or the storefront.
    });
  }, { once: true });
})();
