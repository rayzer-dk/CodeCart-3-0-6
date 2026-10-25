const { test, expect } = require('@playwright/test');

test('unauthenticated admin dashboard requires login', async ({ page }) => {
  const response = await page.goto('/admin/index.php?route=common/dashboard');
  expect(response).not.toBeNull();
  expect(response.ok()).toBe(true);
  await expect(page.locator('#input-username')).toBeVisible();
  await expect(page.locator('#input-password')).toHaveAttribute('type', 'password');
});

test('admin authenticates and displays a token-protected dashboard', async ({ page }) => {
  const username = process.env.E2E_ADMIN_USERNAME;
  const password = process.env.E2E_ADMIN_PASSWORD;
  if (!username || !password) {
    throw new Error('E2E_ADMIN_USERNAME and E2E_ADMIN_PASSWORD are required for isolated QA.');
  }

  const loginPage = await page.goto('/admin/');
  expect(loginPage).not.toBeNull();
  expect(loginPage.ok()).toBe(true);

  await page.locator('#input-username').fill(username);
  await page.locator('#input-password').fill(password);
  await Promise.all([
    page.waitForURL(/user_token=[a-zA-Z0-9]+/, { timeout: 20000 }),
    page.locator('form button[type="submit"]').click(),
  ]);
  await expect(page.locator('#content')).toBeVisible();
  await expect(page.locator('#input-password')).toHaveCount(0);
});
