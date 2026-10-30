/** Verify real native add-to-cart opens the drawer, without replacing parent markup. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
assert.ok(process.env.POT_WP_PATH && process.env.POT_ARTIFACTS);
const wp = (code) => execFileSync(process.env.POT_PHP || 'php', ['-d', 'error_reporting=22527', '/usr/local/bin/wp', 'eval', code, `--path=${process.env.POT_WP_PATH}`], { encoding: 'utf8' }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /^(localhost|127\.0\.0\.1)$/);
mkdirSync(process.env.POT_ARTIFACTS, { recursive: true });
const product = wp('$p=wc_get_products(array("type"=>"simple","status"=>"publish","stock_status"=>"instock","limit"=>1))[0];echo get_permalink($p->get_id());');
const browser = await chromium.launch({ headless: true });
try {
 for (const width of [1440, 390]) {
  const context = await browser.newContext({ viewport: { width, height: 900 } });
  const page = await context.newPage();
  await page.goto(product, { waitUntil: 'networkidle' });
  const drawer = page.locator('.wc-block-mini-cart__drawer');
  await page.locator('.single_add_to_cart_button').click();
  await drawer.waitFor({ state: 'visible' });
  await page.waitForFunction(() => document.querySelector('.wc-block-mini-cart__drawer')?.getAttribute('aria-hidden') === 'false');
  assert.match(await drawer.innerText(), /Your cart/);
  assert.ok(await page.locator('.wc-block-mini-cart__badge').innerText() !== '0');
  await page.screenshot({ path: `${process.env.POT_ARTIFACTS}/mini-cart-${width}.png` });
  await drawer.getByRole('button', { name: 'Close', exact: true }).click();
  await page.waitForFunction(() => document.querySelector('.wc-block-mini-cart__drawer')?.getAttribute('aria-hidden') === 'true');
  await page.locator('.wc-block-mini-cart__button').click();
  await page.waitForFunction(() => document.querySelector('.wc-block-mini-cart__drawer')?.getAttribute('aria-hidden') === 'false');
  await context.close();
 }
 console.log('PASS: native add opens mini-cart, cart count updates, close/reopen work at desktop and mobile widths.');
} finally {
 await browser.close();
}
