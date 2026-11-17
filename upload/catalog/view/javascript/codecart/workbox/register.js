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

  // Install affordance is independent of the browser cache. It appears only when
  // the browser exposes a real install prompt, or when Safari needs manual steps.
  if (pwaEnabled && document.documentElement.getAttribute('data-ccp-pwa') === '1') {
    var standalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    var iosStandalone = window.navigator.standalone === true;
    if (!standalone && !iosStandalone) {
      var userAgent = window.navigator.userAgent || '';
      var ios = /iPhone|iPad|iPod/.test(userAgent) ||
        (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
      var safari = /Safari/.test(userAgent) && !/CriOS|FxiOS|EdgiOS|Chrome|Chromium|Edg|OPR/.test(userAgent);
      var macSafari = safari && /Macintosh/.test(userAgent) && !ios;
      var availablePrompt = null;
      var installButton = null;
      var help = null;
      var labels = document.documentElement.dataset;

      function hideInstall() {
        if (installButton) installButton.hidden = true;
        if (help) help.hidden = true;
      }

      function createInstallButton() {
        if (installButton) return installButton;
        var footer = document.querySelector('.ccp-footer-bottom');
        if (!footer) return null;
        installButton = document.createElement('button');
        installButton.type = 'button';
        installButton.className = 'btn btn-default btn-sm ccp-pwa-install-button';
        installButton.setAttribute('aria-label', labels.ccpPwaInstall || 'Install app');
        installButton.textContent = labels.ccpPwaInstall || 'Install app';
        installButton.hidden = true;
        help = document.createElement('span');
        help.className = 'ccp-pwa-install-help';
        help.setAttribute('role', 'status');
        help.hidden = true;
        footer.appendChild(installButton);
        footer.appendChild(help);
        installButton.addEventListener('click', function () {
          if (availablePrompt) {
            var prompt = availablePrompt;
            availablePrompt = null;
            installButton.disabled = true;
            prompt.prompt();
            Promise.resolve(prompt.userChoice).then(function (choice) {
              installButton.disabled = false;
              if (choice && choice.outcome === 'accepted') hideInstall();
              else installButton.hidden = true;
            }).catch(function () {
              installButton.disabled = false;
              installButton.hidden = true;
            });
          } else if (ios || macSafari) {
            help.textContent = ios
              ? (labels.ccpPwaIosHelp || 'Safari: Share → Add to Home Screen')
              : (labels.ccpPwaMacHelp || 'Safari: File → Add to Dock');
            help.hidden = !help.hidden;
          }
        });
        return installButton;
      }

      window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        availablePrompt = event;
        if (createInstallButton()) installButton.hidden = false;
      });

      window.addEventListener('appinstalled', function () {
        availablePrompt = null;
        hideInstall();
      });

      if (ios || macSafari) {
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', function () {
            if (createInstallButton()) installButton.hidden = false;
          }, { once: true });
        } else if (createInstallButton()) {
          installButton.hidden = false;
        }
      }
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
