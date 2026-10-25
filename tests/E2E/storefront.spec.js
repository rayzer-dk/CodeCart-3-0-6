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
