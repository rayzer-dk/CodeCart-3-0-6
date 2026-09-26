const { chromium } = require('playwright');
(async () => {
  const base = process.env.BASE || 'http://127.0.0.1:8080';
  const pid = process.env.PRODUCT_ID;
  if (!pid) throw new Error('PRODUCT_ID is required');
  const browser = await chromium.launch({headless:true});
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', e => errors.push(String(e)));
  await page.goto(`${base}/index.php?route=product/product&product_id=${pid}`, {waitUntil:'networkidle'});
  await page.locator('#button-cart').click();
  await page.goto(`${base}/index.php?route=checkout/checkout`, {waitUntil:'networkidle'});
  const guest = page.locator('input[name="account"][value="guest"]');
  if (await guest.count()) { await guest.check(); await page.locator('#button-account').click(); }
  await page.locator('#input-payment-firstname').waitFor({state:'visible'});
  await page.locator('#input-payment-firstname').fill('Browser');
  await page.locator('#input-payment-lastname').fill('Tester');
  await page.locator('#input-payment-email').fill('browser@example.test');
  await page.locator('#input-payment-telephone').fill('+4512345678');
  if (await page.locator('#input-payment-address-1').count()) await page.locator('#input-payment-address-1').fill('Browser Street 1');
  if (await page.locator('#input-payment-city').count()) await page.locator('#input-payment-city').fill('Kyiv');
  if (await page.locator('#input-payment-postcode').count()) await page.locator('#input-payment-postcode').fill('01001');
  await page.locator('#button-guest').click();
  await page.locator('#button-shipping-method').waitFor({state:'visible'});
  const shipping = page.locator('input[name="shipping_method"]');
  if (await shipping.count()) await shipping.first().check();
  await page.locator('#button-shipping-method').click();
  await page.locator('#button-payment-method').waitFor({state:'visible'});
  const payment = page.locator('input[name="payment_method"]');
  if (await payment.count()) await payment.first().check();
  const agree = page.locator('input[name="agree"]');
  if (await agree.count()) await agree.check();
  await page.locator('#button-payment-method').click();
  await page.locator('#button-confirm').waitFor({state:'visible'});
  await Promise.all([
    page.waitForURL(/checkout\/success/, {timeout:20000}),
    page.locator('#button-confirm').click()
  ]);
  if (errors.length) throw new Error('Browser page errors: ' + errors.join(' | '));
  if (!/checkout\/success/.test(page.url())) throw new Error('Checkout did not reach success');
  await browser.close();
  console.log('Chromium checkout PASS');
})().catch(e => { console.error(e); process.exit(1); });
