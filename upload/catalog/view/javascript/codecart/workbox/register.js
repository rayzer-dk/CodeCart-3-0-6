/* CodeCart PRO: register the optional static-asset Workbox worker.
 * Third-party Service Workers are never replaced. The HTML only loads this file when ON.
 */
(function () {
  'use strict';
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
  var baseElement = document.querySelector('base[href]');
  var baseUrl;
  try {
    baseUrl = new URL(baseElement ? baseElement.href : document.baseURI, window.location.href);
  } catch (error) { return; }
  if (baseUrl.origin !== window.location.origin) return;
  var workerUrl = new URL('codecart-sw.js', baseUrl);
  var scopeUrl = new URL('./', baseUrl);

  window.addEventListener('load', function () {
    navigator.serviceWorker.getRegistration(scopeUrl.href).then(function (registration) {
      if (registration && registration.scope !== scopeUrl.href) return;
      var workers = registration ? [registration.active, registration.waiting, registration.installing] : [];
      if (workers.some(function (worker) {
        return worker && worker.scriptURL !== workerUrl.href;
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
