const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const args = process.argv.slice(2);
const argument = name => args.includes(name) ? args[args.indexOf(name) + 1] : '';
const configPath = argument('--disposable-config');
if (!configPath) {
  throw new Error('Usage: node tools/qa_product_image_settings.cjs --disposable-config /staging/upload/admin/config.php --evidence-dir /staging/evidence');
}
const evidence = path.resolve(argument('--evidence-dir') || 'build/qa-product-image-settings');
fs.mkdirSync(evidence, { recursive: true });
const config = JSON.parse(execFileSync(process.env.PHP_BIN || 'php', ['-r',
  'require $argv[1]; echo json_encode(array("url"=>HTTP_SERVER,"host"=>DB_HOSTNAME,"port"=>DB_PORT,"user"=>DB_USERNAME,"password"=>DB_PASSWORD,"database"=>DB_DATABASE,"prefix"=>DB_PREFIX));', configPath], { encoding: 'utf8' }));
if (!['localhost', '127.0.0.1'].includes(new URL(config.url).hostname) || !['localhost', '127.0.0.1'].includes(config.host) || !/^[A-Za-z0-9_]+$/.test(config.prefix)) {
  throw new Error('This mutation fixture requires an explicitly supplied disposable local installation.');
}
const sql = query => execFileSync(process.env.MYSQL_BIN || 'mysql', ['--no-defaults', '--host=' + config.host,
  '--port=' + config.port, '--user=' + config.user, '--default-character-set=utf8mb4', '--batch', '--raw', '--skip-column-names',
  config.database, '-e', query], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, env: { ...process.env, MYSQL_PWD: config.password } }).trim();
const table = config.prefix + 'setting';
const groupWhere = "`code` IN ('theme_codecart','theme_default')";
const snapshot = () => JSON.parse(sql("SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('setting_id',setting_id,'store_id',store_id,'code',`code`,'key',`key`,'value',`value`,'serialized',serialized)),JSON_ARRAY()) FROM (SELECT * FROM " + table + ' WHERE ' + groupWhere + ' ORDER BY setting_id) s'));
const originalSettings = snapshot();
const canonical = rows => JSON.stringify(rows.sort((a, b) => a.setting_id - b.setting_id));
const textSql = value => value === '' ? "''" : "CONVERT(0x" + Buffer.from(String(value)).toString('hex') + ' USING utf8mb4)';
const contexts = ['common', 'catalog', 'gallery', 'thumbnail'];
const options = ['fit', 'background', 'upscale'];
const fieldName = (context, option) => 'theme_codecart_image_' + (context === 'common' ? '' : context + '_') + option;
const defaults = { fit: 'contain', background: 'white', upscale: '3' };
const selectedValues = {};
const checks = [];
const errors = [];
const check = (name, passed) => { assert.ok(passed, name); checks.push(name); console.log('PASS ' + name); };
const policyValues = () => Object.fromEntries(JSON.parse(sql("SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('key',`key`,'value',`value`)),JSON_ARRAY()) FROM " + table + " WHERE store_id=0 AND `code`='theme_codecart'"))
  .filter(row => contexts.some(context => options.some(option => row.key === fieldName(context, option)))).map(row => [row.key, row.value]));
let browser;
let failure;
async function verifyPreviewOnly() {
  const before = canonical(snapshot());
  let posts = 0;
  try {
    browser = await chromium.launch(process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN, headless: true } : { channel: 'chrome', headless: true });
    const page = await browser.newPage();
    await page.goto(config.url, { waitUntil: 'domcontentloaded' });
    await page.locator('[name="username"]').fill(process.env.QA_ADMIN_USERNAME || 'qaadmin');
    await page.locator('[name="password"]').fill(process.env.QA_ADMIN_PASSWORD || 'QA-Local-212-only');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('button[type="submit"]').click()]);
    const token = new URL(page.url()).searchParams.get('user_token');
    await page.goto(config.url + 'index.php?route=extension/theme/codecart&store_id=0&user_token=' + encodeURIComponent(token), { waitUntil: 'domcontentloaded' });
    page.on('request', request => { if (request.method() === 'POST') posts++; });
    const fit = page.locator('#image-gallery-fit');
    const details = page.locator('.ccp-image-settings details').filter({ has: fit });
    await details.locator('summary').click();
    await fit.selectOption('cover');
    for (const [cap, expected] of [['1', 36], ['2', 72], ['3', 108], ['0', 140]]) {
      await page.locator('#image-gallery-upscale').selectOption(cap);
      const rendered = await page.locator('[data-image-preview="gallery"] svg').evaluate(node => ({ width: node.getBoundingClientRect().width, height: node.getBoundingClientRect().height, viewBox: node.getAttribute('viewBox') }));
      check('cover preview cap ' + cap + ' crops centered and scales to ' + expected + 'px', Math.abs(rendered.width - expected) < 0.1 && Math.abs(rendered.height - expected) < 0.1 && rendered.viewBox === '60 0 180 180');
    }
    await fit.selectOption('contain');
    await page.locator('#image-gallery-upscale').selectOption('1');
    const rendered = await page.locator('[data-image-preview="gallery"] svg').evaluate(node => ({ width: node.getBoundingClientRect().width, height: node.getBoundingClientRect().height, viewBox: node.getAttribute('viewBox') }));
    check('contain preview 1x preserves whole 60x36 image', rendered.width === 60 && rendered.height === 36 && rendered.viewBox === '0 0 300 180');
    check('targeted preview sends no POST and changes no settings', posts === 0 && canonical(snapshot()) === before);
    fs.writeFileSync(path.join(evidence, 'preview-only.json'), JSON.stringify({ passed: checks.length, failed: 0, checks }, null, 2));
  } finally {
    if (browser) await browser.close();
  }
}
if (args.includes('--preview-only')) {
  verifyPreviewOnly().catch(error => { console.error(error); process.exitCode = 1; });
} else {
(async () => {
  try {
    fs.writeFileSync(path.join(evidence, 'settings-snapshot.json'), JSON.stringify(originalSettings, null, 2));
    const policyFields = contexts.flatMap(context => options.map(option => fieldName(context, option)));
    sql('DELETE FROM ' + table + " WHERE store_id=0 AND `code`='theme_codecart' AND `key` IN (" + policyFields.map(value => '0x' + Buffer.from(value).toString('hex')).join(',') + ')');
    const beforePreview = canonical(snapshot());
    browser = await chromium.launch(process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN, headless: true } : { channel: 'chrome', headless: true });
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(config.url, { waitUntil: 'domcontentloaded' });
    await page.locator('[name="username"]').fill(process.env.QA_ADMIN_USERNAME || 'qaadmin');
    await page.locator('[name="password"]').fill(process.env.QA_ADMIN_PASSWORD || 'QA-Local-212-only');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('button[type="submit"]').click()]);
    const token = new URL(page.url()).searchParams.get('user_token');
    assert.ok(token, 'admin login');
    const url = config.url + 'index.php?route=extension/theme/codecart&store_id=0&user_token=' + encodeURIComponent(token);
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    check('theme form loads without PHP or Twig errors', await page.locator('#form-theme').count() === 1 && !/Fatal error|Uncaught|Twig\\Error/.test(await page.content()));
    const policy = page.locator('.ccp-image-settings');
    check('all twelve policy selects are rendered', await policy.locator('select').count() === 12);
    for (const context of contexts) {
      for (const option of options) {
        const expected = context === 'common' ? defaults[option] : 'inherit';
        check(context + ' ' + option + ' has safe default', await page.locator('[name="' + fieldName(context, option) + '"]').inputValue() === expected);
      }
    }
    let savePosts = 0;
    page.on('request', request => { if (request.method() === 'POST' && /route=extension(?:%2F|\/)theme(?:%2F|\/)codecart/.test(request.url())) savePosts++; });
    const select = async (context, option, value) => {
      const field = page.locator('[name="' + fieldName(context, option) + '"]');
      if (context !== 'common') {
        const details = policy.locator('details').filter({ has: field });
        if (!(await details.evaluate(node => node.open))) await details.locator('summary').click();
      }
      await field.selectOption(value);
    };
    const preview = context => page.locator('[data-image-preview="' + context + '"]').evaluate(box => ({
      background: getComputedStyle(box).backgroundColor,
      width: box.querySelector('svg').getBoundingClientRect().width,
      height: box.querySelector('svg').getBoundingClientRect().height,
      viewBox: box.querySelector('svg').getAttribute('viewBox')
    }));
    await select('common', 'upscale', '1');
    let rendered = await preview('catalog');
    check('1x limit previews natural dimensions', Math.abs(rendered.width - 60) < 0.1 && Math.abs(rendered.height - 36) < 0.1);
    await select('common', 'upscale', '2');
    await select('common', 'background', 'theme');
    rendered = await preview('catalog');
    check('catalog inherits common 2x and theme background', Math.abs(rendered.width - 120) < 0.1 && Math.abs(rendered.height - 72) < 0.1 && rendered.background === 'rgb(232, 237, 243)');
    await select('gallery', 'fit', 'cover');
    await select('gallery', 'background', 'dark');
    await select('gallery', 'upscale', '0');
    rendered = await preview('gallery');
    check('gallery override previews centered cover with unlimited enlargement', Math.abs(rendered.width - 140) < 0.1 && Math.abs(rendered.height - 140) < 0.1 && rendered.viewBox === '60 0 180 180' && rendered.background === 'rgb(23, 30, 39)');
    await select('thumbnail', 'background', 'white');
    check('preview changes send no POST and do not change settings', savePosts === 0 && canonical(snapshot()) === beforePreview);
    for (const details of await policy.locator('details').all()) {
      if (!(await details.evaluate(node => node.open))) await details.locator('summary').click();
    }
    await policy.scrollIntoViewIfNeeded();
    await page.screenshot({ path: path.join(evidence, 'desktop-light.png') });
    await page.locator('#ccp-admin-theme-toggle').click();
    await policy.scrollIntoViewIfNeeded();
    await page.screenshot({ path: path.join(evidence, 'desktop-dark.png') });
    const contrast = await policy.evaluate(root => {
      const luminance = color => {
        const rgb = color.match(/[\d.]+/g).slice(0, 3).map(value => {
          const channel = Number(value) / 255;
          return channel <= 0.04045 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
      };
      const foreground = luminance(getComputedStyle(root.querySelector('label')).color);
      const background = luminance(getComputedStyle(root).backgroundColor);
      return (Math.max(foreground, background) + 0.05) / (Math.min(foreground, background) + 0.05);
    });
    check('dark mode labels remain readable against the policy surface', contrast >= 4.5);
    await page.setViewportSize({ width: 390, height: 844 });
    await policy.scrollIntoViewIfNeeded();
    const boxes = await policy.locator('select').evaluateAll(nodes => nodes.map(node => { const rect = node.getBoundingClientRect(); return { x: rect.x, right: rect.right, width: rect.width }; }));
    check('mobile policy selects fit the 390px viewport', boxes.every(box => box.x >= 0 && box.right <= 391 && box.width >= 100));
    await page.screenshot({ path: path.join(evidence, 'mobile-dark.png') });
    await page.locator('#ccp-admin-theme-toggle').click();
    await policy.scrollIntoViewIfNeeded();
    await page.screenshot({ path: path.join(evidence, 'mobile-light.png') });
    for (const context of contexts) for (const option of options) selectedValues[fieldName(context, option)] = await page.locator('[name="' + fieldName(context, option) + '"]').inputValue();
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('button[form="form-theme"][type="submit"]').click()]);
    check('standard Save submits exactly one POST', savePosts === 1);
    const saved = policyValues();
    check('standard Save persists all twelve selections', Object.entries(selectedValues).every(([key, value]) => saved[key] === value));
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    for (const [key, value] of Object.entries(selectedValues)) assert.equal(await page.locator('[name="' + key + '"]').inputValue(), value, key + ' reopened value');
    check('reopening form restores all twelve selections', true);
    const result = await page.evaluate(async names => {
      const form = document.querySelector('#form-theme');
      const body = new FormData(form);
      names.forEach((name, index) => {
        body.delete(name);
        if (index === 0) body.set(name + '[]', 'cover');
        else body.set(name, 'invalid-policy-value');
      });
      const response = await fetch(form.action, { method: 'POST', body, credentials: 'same-origin' });
      return { status: response.status, html: await response.text() };
    }, policyFields);
    check('invalid POST handled without PHP errors', result.status === 200 && !/Fatal error|Uncaught|Parse error/.test(result.html));
    const sanitized = policyValues();
    check('invalid scalar and array values normalize to safe defaults', contexts.every(context => options.every(option => sanitized[fieldName(context, option)] === (context === 'common' ? defaults[option] : 'inherit'))));
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    for (const context of contexts) for (const option of options) assert.equal(await page.locator('[name="' + fieldName(context, option) + '"]').inputValue(), context === 'common' ? defaults[option] : 'inherit');
    check('sanitized values persist and reopen correctly', true);
    check('no browser JavaScript errors', errors.length === 0);
  } catch (error) {
    failure = error;
  } finally {
    if (browser) await browser.close();
    const inserts = originalSettings.map(row => '(' + [row.setting_id, row.store_id, textSql(row.code), textSql(row.key), textSql(row.value), row.serialized].join(',') + ')');
    sql('START TRANSACTION; DELETE FROM ' + table + ' WHERE ' + groupWhere + ';' + (inserts.length ? 'INSERT INTO ' + table + ' (setting_id,store_id,`code`,`key`,`value`,serialized) VALUES ' + inserts.join(',') + ';' : '') + 'COMMIT;');
    check('original CodeCart and Default settings restored exactly', canonical(snapshot()) === canonical(originalSettings));
    fs.writeFileSync(path.join(evidence, 'summary.json'), JSON.stringify({ passed: checks.length, failed: failure ? 1 : 0, checks, errors, failure: failure ? failure.message : null }, null, 2));
  }
  if (failure) throw failure;
})().catch(error => { console.error(error); process.exitCode = 1; });
}
