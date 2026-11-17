/* CodeCart PRO optional installable web app: network-only worker.
 * No HTTP response (including HTML, cart or customer data) is cached.
 */
'use strict';
const APP_SCOPE = new URL(self.registration.scope);
const CACHE_PREFIX = 'ccp-workbox-' + encodeURIComponent(APP_SCOPE.pathname) + '-';
self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});
self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    for (const name of await caches.keys()) {
      if (name.startsWith(CACHE_PREFIX)) await caches.delete(name);
    }
    await self.clients.claim();
  })());
});
self.addEventListener('fetch', (event) => {
  if (event.request.mode === 'navigate' && event.request.method === 'GET' &&
      new URL(event.request.url).origin === self.location.origin) {
    event.respondWith(fetch(event.request));
  }
});
