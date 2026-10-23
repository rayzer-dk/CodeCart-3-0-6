const { chromium } = require('playwright');
const { JSDOM } = require('jsdom');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const assert = require('node:assert/strict');

const args = process.argv.slice(2);
const argument = name => args.includes(name) ? args[args.indexOf(name) + 1] : '';
const configPath = argument('--disposable-config');
if (!configPath) throw new Error('Usage: node tools/qa_product_images_runtime.cjs --disposable-config /staging/upload/config.php --evidence-dir /staging/evidence');
const evidence = path.resolve(argument('--evidence-dir') || 'build/qa-product-images-runtime');
fs.mkdirSync(evidence, { recursive: true });
const php = (code, ...values) => execFileSync(process.env.PHP_BIN || 'php', ['-r', code, ...values], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
const config = JSON.parse(php('require $argv[1]; echo json_encode(array("url"=>HTTP_SERVER,"image"=>DIR_IMAGE,"host"=>DB_HOSTNAME,"port"=>DB_PORT,"user"=>DB_USERNAME,"password"=>DB_PASSWORD,"database"=>DB_DATABASE,"prefix"=>DB_PREFIX,"avif"=>function_exists("imageavif")));', configPath));
if (!['localhost', '127.0.0.1'].includes(new URL(config.url).hostname) || !['localhost', '127.0.0.1'].includes(config.host) || !/^[A-Za-z0-9_]+$/.test(config.prefix)) throw new Error('This fixture requires an explicitly supplied disposable local installation.');
const sql = query => execFileSync(process.env.MYSQL_BIN || 'mysql', ['--no-defaults', '--host=' + config.host, '--port=' + config.port, '--user=' + config.user,
  '--default-character-set=utf8mb4', '--batch', '--raw', '--skip-column-names', config.database, '-e', query],
{ encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, env: { ...process.env, MYSQL_PWD: config.password } }).trim();
const settingTable = config.prefix + 'setting';
const configKeys = ['config_theme', 'config_image_webp', 'config_image_avif', 'config_image_webp_quality', 'config_image_avif_quality'];
const whereSettings = "`code` IN ('theme_codecart','theme_default') OR (store_id=0 AND `key` IN (" + configKeys.map(key => "'" + key + "'").join(',') + '))';
const settingsSnapshot = () => JSON.parse(sql("SELECT COALESCE(JSON_ARRAYAGG(JSON_OBJECT('setting_id',setting_id,'store_id',store_id,'code',`code`,'key',`key`,'value',`value`,'serialized',serialized)),JSON_ARRAY()) FROM (SELECT * FROM " + settingTable + ' WHERE ' + whereSettings + ' ORDER BY setting_id) s'));
const canonical = rows => JSON.stringify(rows.sort((a, b) => a.setting_id - b.setting_id));
const textSql = value => String(value) === '' ? "''" : 'CONVERT(0x' + Buffer.from(String(value)).toString('hex') + ' USING utf8mb4)';
const setSetting = (key, value) => {
  const code = key.startsWith('theme_') ? 'theme_codecart' : 'config';
  sql('DELETE FROM ' + settingTable + " WHERE store_id=0 AND `key`='" + key + "'; INSERT INTO " + settingTable + " SET store_id=0,`code`='" + code + "',`key`='" + key + "',`value`=" + textSql(value) + ',serialized=0');
};
const originalSettings = settingsSnapshot();
const product = JSON.parse(sql("SELECT JSON_OBJECT('product_id',p.product_id,'image',p.image,'viewed',p.viewed,'category_id',p2c.category_id) FROM " + config.prefix + 'product p INNER JOIN ' + config.prefix + 'product_to_store p2s ON p2s.product_id=p.product_id INNER JOIN ' + config.prefix + 'product_to_category p2c ON p2c.product_id=p.product_id WHERE p.status=1 AND p.date_available<=NOW() AND p2s.store_id=0 ORDER BY p.product_id,p2c.category_id LIMIT 1'));
const productUrl = config.url + 'index.php?route=product/product&product_id=' + product.product_id;
const categoryUrl = config.url + 'index.php?route=product/category&path=' + product.category_id + '&limit=1000';
const suffix = 'qa-policy-' + crypto.randomBytes(6).toString('hex');
const sources = { large: 'catalog/' + suffix + '-large.jpg', tiny: 'catalog/' + suffix + '-tiny.jpg', alpha: 'catalog/' + suffix + '-alpha.png' };
const createdRows = [];
const createdFiles = [];
const checks = [];
const browserErrors = [];
const check = (name, passed, details) => { checks.push({ name, passed: !!passed, ...(details ? { details } : {}) }); console.log((passed ? 'PASS ' : 'FAIL ') + name + (passed || !details ? '' : ' ' + JSON.stringify(details))); return passed; };
const imageFile = uri => {
  const relative = decodeURIComponent(new URL(uri, config.url).pathname.split('/image/')[1] || '');
  const target = path.resolve(config.image, relative);
  assert.ok(relative.startsWith('cache/') && relative.includes(suffix) && target.startsWith(path.resolve(config.image) + path.sep), 'only inspect this fixture image cache');
  return target;
};
const imageInfo = uri => {
  const file = imageFile(uri);
  const data = JSON.parse(php('$p=$argv[1];$i=getimagesize($p);$im=imagecreatefromstring(file_get_contents($p));$pixels=array();foreach(array(array(0,0),array($i[0]-1,0),array(0,$i[1]-1),array($i[0]-1,$i[1]-1)) as $xy){$c=imagecolorat($im,$xy[0],$xy[1]);$pixels[]=array("alpha"=>($c>>24)&127,"r"=>($c>>16)&255,"g"=>($c>>8)&255,"b"=>$c&255);}echo json_encode(array("width"=>$i[0],"height"=>$i[1],"mime"=>$i["mime"],"pixels"=>$pixels));', file));
  data.sha256 = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
  return data;
};
const dimensions = (info, width, height) => info.width === width && info.height === height;
const noWhitePadding = info => info.pixels.every(pixel => Math.min(pixel.r, pixel.g, pixel.b) < 220);
const getDocument = async (url, accept = '') => {
  const response = await fetch(url, { headers: { Accept: accept } });
  const html = await response.text();
  assert.equal(response.status, 200, 'storefront HTTP response');
  assert.ok(!/Fatal error|Parse error|Uncaught (?:Error|Exception)/.test(html), 'storefront renders without PHP errors');
  return { document: new JSDOM(html).window.document, response };
};
const mainImage = document => {
  const image = document.querySelector('#ccp-main-product-image-img');
  const anchor = document.querySelector('#ccp-main-product-image');
  assert.ok(image && anchor, 'product gallery rendered');
  return { uri: image.src, width: Number(image.getAttribute('width')), height: Number(image.getAttribute('height')), popup: anchor.href,
    popupWidth: Number(anchor.getAttribute('data-pswp-width')), popupHeight: Number(anchor.getAttribute('data-pswp-height')) };
};
const verifyMain = (name, document, width, height, mode = 'contain') => {
  const main = mainImage(document);
  const file = imageInfo(main.uri);
  const popup = imageInfo(main.popup);
  check(name + ' cached dimensions', dimensions(file, width, height), file);
  check(name + ' main and popup metadata match physical files', dimensions(file, main.width, main.height) && dimensions(popup, main.popupWidth, main.popupHeight), { main, file, popup });
  check(name + ' explicit mode cache and no white padding', main.uri.includes('-' + mode) && noWhitePadding(file), { uri: main.uri, pixels: file.pixels });
  return { main, file, popup };
};
let browser;
let fatal;
(async () => {
  try {
    fs.writeFileSync(path.join(evidence, 'settings-snapshot.json'), JSON.stringify(originalSettings, null, 2));
    for (const [name, relative] of Object.entries(sources)) {
      const target = path.join(config.image, relative);
      assert.ok(!fs.existsSync(target), 'unique fixture image path');
      php('$p=$argv[1];$kind=$argv[2];$w=$kind==="tiny"?100:500;$h=$kind==="tiny"?100:300;$im=imagecreatetruecolor($w,$h);if($kind==="alpha"){imagealphablending($im,false);imagesavealpha($im,true);imagefill($im,0,0,imagecolorallocatealpha($im,0,0,0,127));imagefilledrectangle($im,250,0,499,299,imagecolorallocatealpha($im,21,101,169,0));imagepng($im,$p);}else{imagefill($im,0,0,imagecolorallocate($im,21,101,169));imagefilledrectangle($im,20,20,(int)($w/2),(int)($h/2),imagecolorallocate($im,238,189,54));imagejpeg($im,$p,95);}imagedestroy($im);', target, name);
      createdFiles.push(target);
    }
    sql('UPDATE ' + config.prefix + 'product SET image=' + textSql(sources.large) + ' WHERE product_id=' + product.product_id);
    for (const [index, source] of [sources.tiny, sources.alpha].entries()) {
      createdRows.push(Number(sql('INSERT INTO ' + config.prefix + 'product_image SET product_id=' + product.product_id + ',image=' + textSql(source) + ',sort_order=' + (-100 + index) + '; SELECT LAST_INSERT_ID()')));
    }
    for (const [key, value] of Object.entries({ config_theme: 'codecart', config_image_webp: '0', config_image_avif: '0',
      theme_codecart_image_fit: 'contain', theme_codecart_image_background: 'white', theme_codecart_image_upscale: '3',
      theme_codecart_image_popup_width: '1400', theme_codecart_image_popup_height: '1050', theme_codecart_image_thumb_width: '500', theme_codecart_image_thumb_height: '500',
      theme_codecart_image_product_width: '400', theme_codecart_image_product_height: '400', theme_codecart_image_additional_width: '100', theme_codecart_image_additional_height: '100',
      theme_codecart_dark_mode_status: '1', theme_codecart_dark_mode_default: 'light' })) setSetting(key, value);
    for (const context of ['catalog', 'gallery', 'thumbnail']) for (const option of ['fit', 'background', 'upscale']) setSetting('theme_codecart_image_' + context + '_' + option, 'inherit');

    let result = await getDocument(productUrl);
    const normal = verifyMain('default 3x JPEG 500x300', result.document, 1400, 840);
    check('WebP OFF keeps JPEG even without Accept', normal.file.mime === 'image/jpeg' && normal.main.uri.endsWith('.jpg'));
    const tiny = result.document.querySelector('a.ccp-product-gallery-thumb[data-main-src*="' + suffix + '-tiny"]');
    assert.ok(tiny, 'tiny additional fixture rendered');
    check('tiny 100x100 main and popup respect default 3x cap', dimensions(imageInfo(tiny.getAttribute('data-main-src')), 300, 300) && dimensions(imageInfo(tiny.href), 300, 300));
    check('tiny popup metadata matches 300x300 file', Number(tiny.getAttribute('data-pswp-width')) === 300 && Number(tiny.getAttribute('data-pswp-height')) === 300);
    const alphaThumb = result.document.querySelector('a.ccp-product-gallery-thumb img[src*="' + suffix + '-alpha"]');
    const alphaThumbFile = imageInfo(alphaThumb.src);
    check('additional thumbnail dimensions match physical file', dimensions(alphaThumbFile, Number(alphaThumb.getAttribute('width')), Number(alphaThumb.getAttribute('height'))), { html: { width: alphaThumb.getAttribute('width'), height: alphaThumb.getAttribute('height') }, file: alphaThumbFile });
    for (const [cap, width, height, tinySize] of [['1', 500, 300, 100], ['2', 1000, 600, 200], ['0', 1400, 840, 1050]]) {
      setSetting('theme_codecart_image_gallery_upscale', cap);
      result = await getDocument(productUrl);
      verifyMain('gallery override upscale ' + cap, result.document, width, height);
      const thumb = result.document.querySelector('a.ccp-product-gallery-thumb[data-main-src*="' + suffix + '-tiny"]');
      check('tiny gallery override upscale ' + cap, dimensions(imageInfo(thumb.getAttribute('data-main-src')), tinySize, tinySize));
    }
    setSetting('theme_codecart_image_gallery_fit', 'cover');
    result = await getDocument(productUrl);
    const covered = verifyMain('cover unlimited', result.document, 1400, 1050, 'cover');
    check('cover and contain use distinct cache URLs', covered.main.uri !== normal.main.uri);
    setSetting('theme_codecart_image_gallery_fit', 'contain');
    setSetting('theme_codecart_image_gallery_upscale', '3');
    const category = await getDocument(categoryUrl);
    const catalogImage = category.document.querySelector('.product-thumb img[src*="' + suffix + '-large"]');
    assert.ok(catalogImage, 'product is rendered in its category');
    const catalogFile = imageInfo(catalogImage.src);
    check('actual catalog card uses contain 400x240 without padding', dimensions(catalogFile, 400, 240) && catalogImage.src.includes('-contain') && noWhitePadding(catalogFile), catalogFile);
    check('catalog card metadata matches physical derivative', dimensions(catalogFile, Number(catalogImage.getAttribute('width')), Number(catalogImage.getAttribute('height'))), { html: { width: catalogImage.getAttribute('width'), height: catalogImage.getAttribute('height') }, file: catalogFile });

    browser = await chromium.launch(process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN, headless: true } : { channel: 'chrome', headless: true });
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    page.on('pageerror', error => browserErrors.push(error.message));
    await page.goto(productUrl, { waitUntil: 'networkidle' });
    for (const background of ['white', 'theme', 'dark']) {
      setSetting('theme_codecart_image_background', background);
      const changed = await getDocument(productUrl);
      const changedMain = mainImage(changed.document);
      check(background + ' background preserves image URI and bytes', changedMain.uri === normal.main.uri && imageInfo(changedMain.uri).sha256 === normal.file.sha256);
      for (const dark of [false, true]) {
        await page.evaluate(mode => localStorage.setItem('ccp-theme', mode), dark ? 'dark' : 'light');
        for (const width of [1280, 390]) {
          await page.setViewportSize({ width, height: 900 });
          await page.goto(productUrl, { waitUntil: 'networkidle' });
          const styles = await page.evaluate(() => {
            const main = document.querySelector('#ccp-main-product-image');
            const thumb = document.querySelector('.ccp-product-gallery-thumb');
            const image = document.querySelector('#ccp-main-product-image-img');
            const box = main.getBoundingClientRect();
            const bitmap = image.getBoundingClientRect();
            return { main: getComputedStyle(main).backgroundColor, thumb: getComputedStyle(thumb).backgroundColor,
              dark: document.documentElement.classList.contains('ccp-theme-dark'), width: innerWidth,
              inViewport: box.x >= 0 && box.right <= innerWidth + 1 && bitmap.x >= box.x && bitmap.right <= box.right + 1 };
          });
          const expected = background === 'white' ? 'rgb(255, 255, 255)' : background === 'dark' ? 'rgb(23, 30, 39)' : 'rgba(0, 0, 0, 0)';
          check('gallery/thumbnail CSS ' + background + ' ' + (dark ? 'dark' : 'light') + ' ' + width, styles.main === expected && styles.thumb === expected && styles.dark === dark && styles.inViewport, styles);
          await page.locator('.ccp-product-media').screenshot({ path: path.join(evidence, 'gallery-' + background + '-' + (dark ? 'dark' : 'light') + '-' + width + '.png') });
          await page.goto(categoryUrl, { waitUntil: 'networkidle' });
          const color = await page.locator('.product-thumb .image').filter({ has: page.locator('img[src*="' + suffix + '-large"]') }).evaluate(node => getComputedStyle(node).backgroundColor);
          check('catalog CSS ' + background + ' ' + (dark ? 'dark' : 'light') + ' ' + width, color === expected, { color, expected });
        }
      }
    }

    setSetting('config_image_webp', '1');
    result = await getDocument(productUrl, 'text/html,image/webp');
    const modern = mainImage(result.document);
    const webp = imageInfo(modern.uri);
    check('manual WebP enable negotiates real 1400x840 WebP without padding', webp.mime === 'image/webp' && dimensions(webp, 1400, 840) && noWhitePadding(webp), webp);
    check('WebP negotiation sends Vary Accept', (result.response.headers.get('vary') || '').toLowerCase().includes('accept'));
    const modernAlpha = result.document.querySelector('a.ccp-product-gallery-thumb[data-main-src*="' + suffix + '-alpha"]');
    const alphaWebp = imageInfo(modernAlpha.getAttribute('data-main-src'));
    check('PNG to WebP preserves dimensions and alpha', dimensions(alphaWebp, 1400, 840) && alphaWebp.mime === 'image/webp' && alphaWebp.pixels[0].alpha === 127 && alphaWebp.pixels[1].alpha === 0, alphaWebp);
    result = await getDocument(productUrl);
    check('no Accept keeps JPEG with WebP enabled', imageInfo(mainImage(result.document).uri).mime === 'image/jpeg');
    setSetting('config_image_webp', '0');
    result = await getDocument(productUrl, 'text/html,image/webp');
    check('WebP OFF performs no conversion despite WebP Accept', imageInfo(mainImage(result.document).uri).mime === 'image/jpeg');
    if (config.avif) {
      setSetting('config_image_avif', '1');
      result = await getDocument(productUrl, 'text/html,image/avif');
      const avif = imageInfo(mainImage(result.document).uri);
      check('AVIF-only conversion works and sends Vary Accept', avif.mime === 'image/avif' && dimensions(avif, 1400, 840) && (result.response.headers.get('vary') || '').toLowerCase().includes('accept'), avif);
    }
    check('storefront has no browser JavaScript errors', browserErrors.length === 0, browserErrors);
  } catch (error) {
    fatal = error;
  } finally {
    if (browser) await browser.close();
    if (createdRows.length) sql('DELETE FROM ' + config.prefix + 'product_image WHERE product_image_id IN (' + createdRows.join(',') + ')');
    sql('UPDATE ' + config.prefix + 'product SET image=' + textSql(product.image) + ',viewed=' + Number(product.viewed) + ' WHERE product_id=' + product.product_id);
    const values = originalSettings.map(row => '(' + [row.setting_id, row.store_id, textSql(row.code), textSql(row.key), textSql(row.value), row.serialized].join(',') + ')');
    sql('START TRANSACTION; DELETE FROM ' + settingTable + ' WHERE ' + whereSettings + ';' + (values.length ? 'INSERT INTO ' + settingTable + ' (setting_id,store_id,`code`,`key`,`value`,serialized) VALUES ' + values.join(',') + ';' : '') + 'COMMIT;');
    check('theme and config settings restored exactly', canonical(settingsSnapshot()) === canonical(originalSettings));
    for (const file of createdFiles) if (fs.existsSync(file)) fs.unlinkSync(file);
    const removeOwnDerivatives = directory => {
      for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
        const target = path.join(directory, entry.name);
        if (entry.isDirectory()) removeOwnDerivatives(target);
        else if (entry.name.startsWith(suffix + '-')) fs.unlinkSync(target);
      }
    };
    const cacheRoot = path.resolve(config.image, 'cache/catalog');
    assert.ok(cacheRoot.startsWith(path.resolve(config.image) + path.sep), 'fixture cleanup stays within the supplied image directory');
    if (fs.existsSync(cacheRoot)) removeOwnDerivatives(cacheRoot);
    const passed = checks.filter(check => check.passed).length;
    fs.writeFileSync(path.join(evidence, 'summary.json'), JSON.stringify({ passed, failed: checks.length - passed + (fatal ? 1 : 0), avifSupported: config.avif, checks, browserErrors, fatal: fatal ? fatal.message : null }, null, 2));
  }
  if (fatal) throw fatal;
  if (checks.some(check => !check.passed)) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
