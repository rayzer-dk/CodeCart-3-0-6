/* CodeCart PRO 3.0.6: optional storefront static-cache worker.
 * No HTML, API, price, cart, customer, checkout, order or admin response is cached.
 * The browser uses locally vendored, version-verified Workbox 7.4.1 only.
 */
'use strict';
const CCP_RELEASE = '3.0.6.0-hybrid-1';
const APP_SCOPE = new URL(self.registration.scope);
const CACHE_PREFIX = 'ccp-workbox-' + encodeURIComponent(APP_SCOPE.pathname) + '-';
const WORKBOX_DIR = new URL('catalog/view/javascript/codecart/workbox/v7.4.1/', APP_SCOPE);
importScripts(WORKBOX_DIR.href + 'workbox-sw.js');
workbox.setConfig({ modulePathPrefix: WORKBOX_DIR.href, debug: false });
workbox.core.setCacheNameDetails({ prefix: CACHE_PREFIX.slice(0, -1), suffix: CCP_RELEASE });

const versionedCache = CACHE_PREFIX + 'static-' + CCP_RELEASE;
const imageCache = CACHE_PREFIX + 'public-images-' + CCP_RELEASE;
const ownPath = (url) => url.origin === APP_SCOPE.origin &&
  url.pathname.startsWith(APP_SCOPE.pathname) ?
  url.pathname.slice(APP_SCOPE.pathname.length) : '';

const cacheResponse = () => new workbox.cacheableResponse.CacheableResponsePlugin({ statuses: [200] });
const expiration = (maxEntries, days) => new workbox.expiration.ExpirationPlugin({
  maxEntries, maxAgeSeconds: days * 86400, purgeOnQuotaError: true,
});

// Only public JS/CSS with a revision marker; stale-while-revalidate avoids pinning
// outdated OpenCart assets when a third-party module reuses its v= query value.
workbox.routing.registerRoute(({ url, request }) => {
  if (request.method !== 'GET' || !['script', 'style'].includes(request.destination)) return false;
  const path = ownPath(url);
  if (!/^catalog\/view\/(?:javascript|theme)\/[a-z0-9_./-]+\.(?:js|css)$/i.test(path)) return false;
  if (Array.from(url.searchParams.keys()).length !== 1) return false;
  const revision = url.searchParams.get('v');
  return revision !== null && /^[a-z0-9._-]{1,64}$/i.test(revision);
}, new workbox.strategies.StaleWhileRevalidate({
  cacheName: versionedCache,
  plugins: [cacheResponse(), expiration(120, 30)],
}));

// Public image derivatives and theme assets are safe to reuse; no private /media or /system paths.
workbox.routing.registerRoute(({ url, request }) => {
  if (request.method !== 'GET' || url.search || !['image', 'font'].includes(request.destination)) return false;
  const path = ownPath(url);
  if (request.destination === 'image') {
    return /^image\/cache\/[a-z0-9_./-]+\.(?:avif|webp|png|jpe?g|gif|svg)$/i.test(path) ||
      /^catalog\/view\/theme\/[a-z0-9_./-]+\.(?:png|jpe?g|webp|avif|svg)$/i.test(path);
  }
  return /^catalog\/view\/(?:theme|javascript)\/[a-z0-9_./-]+\.(?:woff2?|ttf|otf)$/i.test(path);
}, new workbox.strategies.StaleWhileRevalidate({
  cacheName: imageCache,
  plugins: [cacheResponse(), expiration(160, 14)],
}));

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    for (const key of await caches.keys()) {
      if (key.startsWith(CACHE_PREFIX) && key !== versionedCache && key !== imageCache) {
        await caches.delete(key);
      }
    }
    await self.clients.claim();
  })());
});
