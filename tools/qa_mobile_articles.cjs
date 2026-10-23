'use strict';

const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

// Uses the standard demonstration articles in a disposable local installation.
const base = new URL(process.argv[2] || 'http://127.0.0.1:8134/');
assert.ok(['127.0.0.1', 'localhost', '[::1]'].includes(base.hostname), 'A local test installation is required');
const source = path.resolve(process.env.CODECART_SOURCE || path.join(__dirname, '..'));
const evidence = process.env.CODECART_QA_EVIDENCE;
const routes = [
  { name: 'latest', route: 'blog/latest', grid: true },
  { name: 'category', route: 'blog/category&blog_category_id=69', grid: true },
  { name: 'article', route: 'blog/article&article_id=120' },
  { name: 'product', route: 'product/product&product_id=30' }
];
const results = [];
const failures = [];

(async () => {
  const browser = await chromium.launch(process.env.CHROME_BIN
    ? { headless: true, executablePath: process.env.CHROME_BIN }
    : { headless: true, channel: 'chrome' });
  try {
    for (const width of [320, 360, 390, 414, 575, 576, 600, 767, 1280]) {
      const context = await browser.newContext({ viewport: { width, height: 900 } });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      // Test the current source assets against the installed server-rendered pages.
      await page.route('**/catalog/view/javascript/codecart/modules/native-modules.js*', route => route.fulfill({
        contentType: 'application/javascript',
        body: fs.readFileSync(path.join(source, 'upload/catalog/view/javascript/codecart/modules/native-modules.js'))
      }));
      for (const entry of routes) {
        for (const dark of [false, true]) {
          const label = `${entry.name} ${width}px ${dark ? 'dark' : 'light'}`;
          try {
            await page.goto(new URL('index.php?route=' + entry.route, base).href);
            await page.locator('#content').waitFor();
            await page.evaluate(isDark => document.documentElement.classList.toggle('ccp-theme-dark', isDark), dark);
            await page.waitForTimeout(180);
            const layout = await page.evaluate(() => {
              const grid = document.querySelector('#content .ccp-article-grid');
              const modules = [...document.querySelectorAll('#content .ccp-native-module--articles')]
                .filter(node => node.getBoundingClientRect().width > 0)
                .map(node => {
                  const viewport = node.querySelector('.ccp-native-module-viewport').getBoundingClientRect();
                  const item = node.querySelector('.ccp-native-module-item').getBoundingClientRect();
                  return { title: node.querySelector('h3')?.textContent, columns: viewport.width / item.width };
                });
              return {
                desktopColumns: document.querySelector('#content').matches('.col-sm-12,.col-md-12') ? 5 : 4,
                gridColumns: grid ? getComputedStyle(grid).gridTemplateColumns.split(' ').length : 0,
                gridItems: grid ? grid.children.length : 0,
                modules,
                overflow: document.documentElement.scrollWidth > innerWidth + 1
              };
            });
            assert.equal(layout.overflow, false, 'No page horizontal overflow');
            if (entry.grid) {
              assert.ok(layout.gridItems >= 2, 'Multiple real article cards are present');
              assert.equal(layout.gridColumns, width < 768 ? 2 : layout.desktopColumns, 'Blog grid columns');
            }
            assert.ok(layout.modules.length > 0, 'An embedded article module is present');
            for (const module of layout.modules) {
              const expected = width < 768 ? 2 : layout.desktopColumns;
              assert.ok(Math.abs(module.columns - expected) < 0.02, `${module.title}: expected ${expected} cards, got ${module.columns}`);
            }
            assert.deepEqual(errors, [], 'No browser JavaScript errors');
            if (entry.grid) {
              for (const mode of ['list', 'compact', 'grid']) {
                await page.locator('#article-' + mode + '-view').click();
                const display = await page.locator('.ccp-article-grid').evaluate(node => {
                  const item = node.firstElementChild;
                  return { width: item.getBoundingClientRect().width, gridWidth: node.getBoundingClientRect().width, mode: item.className };
                });
                assert.ok(display.mode.includes('article-' + mode), `Article ${mode} mode remains selectable`);
                if (mode !== 'grid') assert.ok(Math.abs(display.width - display.gridWidth) < 1, `${mode} keeps full-width rows`);
              }
            }
            if (width < 768 && entry.name === 'product') {
              const customColumns = await page.evaluate(() => {
                const original = document.querySelector('#content .ccp-native-module--articles');
                const clone = original.cloneNode(true);
                clone.classList.remove('ccp-native-module--ready');
                clone.style.setProperty('--ccp-module-cols-mobile', '1');
                original.after(clone);
                window.CodeCartNativeModules.init(clone.parentElement);
                const viewport = clone.querySelector('.ccp-native-module-viewport').getBoundingClientRect();
                const item = clone.querySelector('.ccp-native-module-item').getBoundingClientRect();
                const columns = viewport.width / item.width;
                clone.remove();
                return columns;
              });
              assert.ok(Math.abs(customColumns - 1) < 0.02, 'Explicit merchant mobile=1 remains respected');
            }
            if (evidence && [320, 390, 767, 1280].includes(width)) {
              fs.mkdirSync(evidence, { recursive: true });
              const target = page.locator('#content .ccp-article-grid, #content .ccp-native-module--articles').first();
              await target.screenshot({ path: path.join(evidence, `${entry.name}-${width}-${dark ? 'dark' : 'light'}.png`) });
            }
            results.push({ label, ...layout });
            console.log(`PASS ${label}`);
          } catch (error) {
            failures.push({ label, error: error.message });
            console.error(`FAIL ${label}: ${error.message}`);
          }
        }
      }
      await context.close();
    }
  } finally {
    await browser.close();
  }
  if (evidence) fs.writeFileSync(path.join(evidence, 'results.json'), JSON.stringify({ results, failures }, null, 2));
  console.log(`${results.length} passed, ${failures.length} failed`);
  if (failures.length) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
