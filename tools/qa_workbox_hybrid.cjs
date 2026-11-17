/* Contract check for the optional Workbox layer; no network or browser required. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const get = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const worker = get('upload/codecart-sw.js');
const registration = get('upload/catalog/view/javascript/codecart/workbox/register.js');
const header = get('upload/catalog/controller/common/header.php');
const setting = get('upload/admin/controller/setting/setting.php');
const store = get('upload/admin/controller/setting/store.php');
const startup = get('upload/catalog/controller/startup/startup.php');

for (const twig of [
  'upload/catalog/view/theme/default/template/common/header.twig',
  'upload/catalog/view/theme/codecart/template/common/header.twig'
]) {
  const text = get(twig);
  assert.match(text, /data-ccp-workbox=/);
  assert.match(text, /if codecart_workbox_enabled/);
  assert.match(text, /elseif codecart_workbox_used/);
}
assert.match(header, /config_codecart_workbox_status/);
assert.match(header, /codecart_workbox_used/);
assert.match(setting, /config_codecart_workbox_used/);
assert.match(store, /config_codecart_workbox_used/);
assert.match(startup, /config_codecart_workbox_status' => 0/);
assert.match(startup, /config_codecart_workbox_used' => 0/);
assert.match(startup, /foreach \(\$workbox_flags as \$key => \$enabled\)/);
assert.match(registration, /getRegistration/);
assert.match(registration, /scriptURL !== workerUrl\.href/);
assert.doesNotMatch(worker, /skipWaiting\s*\(/);
assert.doesNotMatch(worker, /(?:navigation|navigate).*CacheFirst/i);
assert.match(worker, /workbox\.routing\.registerRoute/);
assert.match(worker, /catalog\/view\/javascript\/codecart\/workbox/);

function check(scope) {
  const routes = [];
  const events = {};
  const cacheNames = {};
  class StubStrategy { constructor(opts) { this.options = opts; } }
  const workbox = {
    setConfig() {},
    core: { setCacheNameDetails() {} },
    cacheableResponse: { CacheableResponsePlugin: class { constructor(opts) { this.opts = opts; } } },
    expiration: { ExpirationPlugin: class { constructor(opts) { this.opts = opts; } } },
    strategies: { CacheFirst: StubStrategy, StaleWhileRevalidate: StubStrategy },
    routing: { registerRoute(matcher, strategy) { routes.push([matcher, strategy]); } }
  };
  const context = {
    URL, encodeURIComponent, Array, Set, self: {
      registration: { scope },
      addEventListener(name, listener) { events[name] = listener; },
      clients: { claim() { return Promise.resolve(); } }
    },
    caches: { keys() { return Promise.resolve([]); }, delete() { return Promise.resolve(true); } },
    importScripts(url) { assert.match(url, /workbox-sw\.js$/); },
    workbox
  };
  vm.runInNewContext(worker, context, { filename: 'codecart-sw.js', timeout: 2000 });
  assert.equal(routes.length, 2);
  assert.equal(typeof events.activate, 'function');
  const matches = (index, input, destination, method='GET') => {
    return routes[index][0]({ url: new URL(input, scope), request: { destination, method } });
  };
  assert.equal(matches(0, 'catalog/view/javascript/common.js?v=3.0.6.0', 'script'), true);
  assert.equal(matches(0, 'catalog/view/theme/codecart/stylesheet/stylesheet.css?v=3.0.6.0', 'style'), true);
  assert.equal(matches(0, 'catalog/view/javascript/common.js', 'script'), false);
  assert.equal(matches(0, 'catalog/view/javascript/common.js?v=3&token=private', 'script'), false);
  assert.equal(matches(0, 'catalog/view/javascript/common.js?v=3', 'script', 'POST'), false);
  assert.equal(matches(0, 'index.php?route=checkout/checkout', 'document'), false);
  assert.equal(matches(0, 'index.php?route=api/order', 'script'), false);
  assert.equal(matches(1, 'image/cache/example-500x500.webp', 'image'), true);
  assert.equal(matches(1, 'image/catalog/original.jpg', 'image'), false);
  assert.equal(matches(1, 'system/storage/download/example.png', 'image'), false);
  assert.equal(matches(1, 'catalog/view/theme/codecart/fonts/inter/inter-regular.woff2', 'font'), true);
  assert.equal(matches(1, 'image/cache/variant.png?key=private', 'image'), false);
  assert.equal(matches(1, 'index.php?route=checkout/checkout', 'document'), false);
  assert.equal(matches(1, 'image/cache/x.png', 'image', 'POST'), false);
  assert.equal(matches(0, 'https://elsewhere.invalid/catalog/view/javascript/common.js?v=3', 'script'), false);
}
check('https://store.example/');
check('https://store.example/shop/');
console.log('CodeCart Workbox hybrid contract: PASS (two scopes, restricted static routes, OFF gates)');
