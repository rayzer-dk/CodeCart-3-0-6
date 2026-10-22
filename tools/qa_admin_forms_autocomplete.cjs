'use strict';

const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const root = path.resolve(process.env.CODECART_SOURCE || path.join(__dirname, '..'));
const admin = path.join(root, 'upload/admin');
const origin = 'http://codecart-qa.invalid';
let checks = 0;
const failures = [];

function html(body, setup = '') {
  return `<!doctype html><html><head><script src="/view/javascript/jquery/jquery-3.7.1.min.js"></script>
    <script src="/view/javascript/bootstrap/js/bootstrap.min.js"></script>
    <script src="/view/javascript/common.js"></script></head><body>${body}<script>${setup}</script></body></html>`;
}

(async () => {
  const browser = await chromium.launch(process.env.CHROME_BIN
    ? { headless: true, executablePath: process.env.CHROME_BIN }
    : { headless: true, channel: 'chrome' });
  async function fixture(body, setup = '') {
    const context = await browser.newContext();
    const page = await context.newPage();
    const posts = [];
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', async route => {
      const request = route.request();
      if (!request.url().startsWith(origin + '/')) return route.abort();
      const pathname = new URL(request.url()).pathname;
      if (request.method() === 'POST') {
        posts.push({ path: pathname, body: request.postData() });
        return route.fulfill({ contentType: 'text/plain', body: 'Captured by isolated QA fixture.' });
      }
      if (pathname === '/') return route.fulfill({ contentType: 'text/html', body: html(body, setup) });
      const filename = path.resolve(admin, '.' + pathname);
      if (!filename.startsWith(admin + path.sep) || !fs.existsSync(filename)) return route.fulfill({ status: 404, body: '' });
      return route.fulfill({ contentType: 'application/javascript', body: fs.readFileSync(filename) });
    });
    await page.goto(origin + '/');
    await page.waitForFunction(() => !!document.getElementById('codecart-ajax-progress'));
    return { page, posts, errors, close: () => context.close() };
  }
  async function verify(name, test) {
    try { await test(); checks++; console.log('PASS ' + name); }
    catch (error) { failures.push(name + ': ' + error.message); console.error('FAIL ' + name + ': ' + error.message); }
  }
  const forms = `<iframe name="main-sink" hidden></iframe><iframe name="other-sink" hidden></iframe>
    <button type="submit" form="form-main" id="save">Save</button>
    <button type="submit" form="form-main" id="copy" formaction="/copy">Copy</button>
    <button type="submit" form="form-main" id="cancel">Cancel</button>
    <form id="form-main" action="/save" method="post" target="main-sink"><input id="title" name="title" value="valid" required></form>
    <form id="form-other" action="/other" method="post" target="other-sink"><input name="other" value="1"></form>`;
  const hooks = `window.submitted=[];$('form').on('submit',function(){submitted.push(this.id);});
    $('#copy').on('click',function(){$('#form-main').attr('action',$(this).attr('formaction'));});
    $('#cancel').on('click',function(){return false;});`;
  for (const [name, action, expectedPath, valid] of [
    ['external save targets one form', p => p.click('#save'), '/save', true],
    ['copy preserves template action handler', p => p.click('#copy'), '/copy', true],
    ['required blocks native POST', async p => { await p.fill('#title', ''); await p.click('#save'); }, null, false],
    ['cancelled click does not submit', p => p.click('#cancel'), null, true],
    ['Enter submits the associated form once', p => p.press('#title', 'Enter'), '/save', true],
    ['module programmatic submit remains available', p => p.evaluate(() => $('#form-main').submit()), '/save', true]
  ]) {
    await verify(name, async () => {
      const f = await fixture(forms, hooks);
      try {
        await action(f.page);
        await f.page.waitForTimeout(250);
        assert.equal(await f.page.locator('#form-main').evaluate(form => form.checkValidity()), valid);
        assert.deepEqual(f.posts.map(post => post.path), expectedPath ? [expectedPath] : []);
        assert.deepEqual(await f.page.evaluate(() => submitted), expectedPath ? ['form-main'] : []);
        assert.deepEqual(f.errors, []);
      } finally { await f.close(); }
    });
  }
  const fields = '<input id="lookup"><input id="second"><button type="button" id="outside">Outside</button>';
  const autocomplete = `window.lookups=[];window.selected=[];
    $('#lookup,#second').autocomplete({source:function(query,callback){lookups.push({id:this.id,query:query,callback:callback});},
    select:function(item){selected.push({id:this.id,value:item.value});$(this).val(item.label);}});`;
  async function request(f, value) {
    const before = await f.page.evaluate(() => lookups.length);
    await f.page.fill('#lookup', value);
    await f.page.press('#lookup', 'End');
    await f.page.waitForFunction(n => lookups.length > n, before);
    return f.page.evaluate(() => lookups.length - 1);
  }
  async function respond(f, index, label, value = 1) {
    await f.page.evaluate(({ index, label, value }) => lookups[index].callback([{ label, value }]), { index, label, value });
  }
  await verify('latest response wins and changed input rejects pending response', async () => {
    const f = await fixture(fields, autocomplete);
    try {
      const old = await request(f, 'a');
      const fresh = await request(f, 'ab');
      await respond(f, fresh, 'new ab', 2);
      await respond(f, old, 'old a');
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').innerText(), 'new ab');
      await f.page.fill('#lookup', 'changed without keydown');
      await respond(f, fresh, 'obsolete');
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').innerText(), 'new ab');
    } finally { await f.close(); }
  });
  await verify('blur and Escape invalidate pending callbacks and debounce', async () => {
    const f = await fixture(fields, autocomplete);
    try {
      const pending = await request(f, 'a');
      await f.page.click('#outside');
      await f.page.waitForTimeout(230);
      await respond(f, pending, 'late blur');
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').isVisible(), false);
      const escaped = await request(f, 'same');
      await f.page.press('#lookup', 'Escape');
      await respond(f, escaped, 'late Escape');
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').isVisible(), false);
      const before = await f.page.evaluate(() => lookups.length);
      await f.page.press('#lookup', 'End');
      await f.page.press('#lookup', 'Escape');
      await f.page.waitForTimeout(230);
      assert.equal(await f.page.evaluate(() => lookups.length), before);
    } finally { await f.close(); }
  });
  await verify('refocus rejects old same-value response; empty selector remains selectable', async () => {
    const f = await fixture(fields, autocomplete);
    try {
      const old = await request(f, '');
      assert.equal(await f.page.evaluate(index => lookups[index].query, old), '');
      await f.page.click('#outside');
      await f.page.focus('#lookup');
      await f.page.waitForFunction(n => lookups.length > n + 1, old);
      const fresh = await f.page.evaluate(() => lookups.length - 1);
      await respond(f, fresh, 'None', 0);
      await respond(f, old, 'obsolete');
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').innerText(), 'None');
      await f.page.locator('#lookup + .dropdown-menu a').click();
      assert.deepEqual(await f.page.evaluate(() => selected), [{ id: 'lookup', value: 0 }]);
    } finally { await f.close(); }
  });
  await verify('grouped mouse selection preserves blur grace and independent fields', async () => {
    const f = await fixture(fields, autocomplete);
    try {
      const first = await request(f, 'first');
      await f.page.focus('#second');
      await f.page.waitForFunction(() => lookups.some(item => item.id === 'second'));
      const second = await f.page.evaluate(() => lookups.findIndex(item => item.id === 'second'));
      await f.page.evaluate(index => lookups[index].callback([{ label: 'Group item', value: 7, category: 'Group' }]), second);
      await respond(f, first, 'late first');
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').isVisible(), false);
      await f.page.locator('#second + .dropdown-menu li[data-value="7"] a').click();
      assert.deepEqual(await f.page.evaluate(() => selected), [{ id: 'second', value: 7 }]);
      assert.equal(await f.page.inputValue('#second'), 'Group item');
      // The public response method remains usable by existing module integrations.
      await f.page.evaluate(() => document.getElementById('lookup').response([{ label: 'Direct API', value: 8 }]));
      assert.equal(await f.page.locator('#lookup + .dropdown-menu').innerText(), 'Direct API');
    } finally { await f.close(); }
  });
  await browser.close();
  console.log('RESULT ' + checks + ' scenarios passed, ' + failures.length + ' failed; native browser POSTs captured by fixture only.');
  if (failures.length) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
