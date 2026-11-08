const { test, expect } = require('@playwright/test');

test('storefront renders the CodeCart home page', async ({ page }) => {
  const response = await page.goto('/');
  expect(response).not.toBeNull();
  expect(response.ok()).toBe(true);
  await expect(page.locator('#common-home #content')).toBeVisible();
  await expect(page).toHaveTitle(/\S/);
  const html = await page.locator('body').innerText();
  expect(html).not.toMatch(/Fatal error|Uncaught Exception|mysqli_sql_exception/i);
});

test('a purchasable product can be added to the guest cart', async ({ page }) => {
  const id = process.env.E2E_PRODUCT_ID;
  if (!id || !/^\d+$/.test(id)) {
    throw new Error('E2E_PRODUCT_ID must identify a seeded, in-stock QA product.');
  }

  const response = await page.goto('/index.php?route=product/product&product_id=' + id);
  expect(response).not.toBeNull();
  expect(response.ok()).toBe(true);
  await expect(page.locator('#product-product h1')).toBeVisible();
  await expect(page.locator('#button-cart')).toBeVisible();

  const addResponse = page.waitForResponse(
    res => res.url().includes('route=checkout/cart/add') && res.request().method() === 'POST'
  );
  await page.locator('#button-cart').click();
  const result = await addResponse;
  expect(result.ok()).toBe(true);
  const cartResult = await result.json();
  expect(cartResult.success, JSON.stringify(cartResult)).toBeTruthy();

  const cartPage = await page.goto('/index.php?route=checkout/cart');
  expect(cartPage).not.toBeNull();
  expect(cartPage.ok()).toBe(true);
  await expect(page.locator('#content input[name^="quantity"]')).toHaveCount(1);
});

test('slideshow finishes a slide change with a single transition', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('#slideshow0.ccp-slider--ready')).toBeVisible();

  // The 460 ms fallback timer, not transitionend, ends the move: the case that used to slide the strip back.
  const transitions = await page.evaluate(async () => {
    const root = document.querySelector('#slideshow0');
    const track = root.querySelector('.ccp-slider__track');
    const slider = window.CodeCartSlider.get(root);
    slider.userPaused = true;
    slider.stop();
    let count = 0;
    track.addEventListener('transitionrun', event => { if (event.target === track) { count += 1; } });
    root.addEventListener('transitionend', event => { if (event.target === track) { event.stopImmediatePropagation(); } }, true);
    root.querySelector('.ccp-slider__next').click();
    await new Promise(resolve => setTimeout(resolve, 1200));
    return count;
  });
  expect(transitions).toBe(1);
});

test('a theme that links jquery-2.1.1 still gets jQuery 3 with Migrate', async ({ page }) => {
  await page.route('**/jquery-stub-check.html', route => route.fulfill({
    contentType: 'text/html',
    body: '<!doctype html><meta charset="utf-8"><script src="/catalog/view/javascript/jquery/jquery-2.1.1.min.js"></script><p>stub</p>',
  }));
  await page.goto('/jquery-stub-check.html');
  const loaded = await page.evaluate(() => ({ jquery: window.jQuery ? window.jQuery.fn.jquery : null, migrate: window.jQuery ? window.jQuery.migrateVersion : null }));
  expect(loaded.jquery).toMatch(/^3\./);
  expect(loaded.migrate).toMatch(/^3\./);
});
