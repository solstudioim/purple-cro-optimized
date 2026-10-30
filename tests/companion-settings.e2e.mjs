/** Isolated/local admin and storefront checks; original settings are restored. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = process.env.POT_WP_PATH;
assert.ok(root, 'Set POT_WP_PATH to an isolated local install.');
const php = process.env.POT_PHP || 'php';
const wp = (code) => execFileSync(php, ['-d', 'error_reporting=22527', '/usr/local/bin/wp', 'eval', code, `--path=${root}`], { encoding: 'utf8' }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /(^localhost$|^127\.0\.0\.1$|\.(test|localhost)$)/);
const artifacts = process.env.POT_ARTIFACTS || '/Users/sol/Codex/artifacts/purple-companion-revamp/browser';
mkdirSync(artifacts, { recursive: true });
const original = wp('echo base64_encode(serialize(array(get_option("pot_settings"),get_option("pot_checkout_settings"))));');
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
let shop;
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
page.setDefaultTimeout(20000);
try {
	if (!process.env.POT_STOREFRONT_ONLY) {
	await page.goto(`${site}/wp-login.php`);
	await page.locator('#user_login').fill(process.env.POT_TEST_USER || 'compat');
	await page.locator('#user_pass').fill(process.env.POT_TEST_PASSWORD || 'local-development-only');
	await Promise.all([page.waitForURL((url) => !url.pathname.endsWith('wp-login.php'), { waitUntil: 'domcontentloaded' }), page.locator('#wp-submit').click()]);
	await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize`);
	await page.getByRole('heading', { name: 'Compatibility checks' }).waitFor();
	assert.equal(await page.locator('main .widefat tbody tr').count(), 6);
	assert.equal(await page.getByRole('cell', { name: 'Needs attention', exact: true }).count(), 0);
	await page.screenshot({ path: `${artifacts}/overview-desktop.png`, fullPage: true });
	const before = JSON.parse(wp('echo wp_json_encode(pot_settings());'));
	for (const section of ['storefront', 'products', 'checkout', 'privacy', 'offers']) {
		await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize&section=${section}`);
		assert.equal(await page.locator('.pot-workspace-nav [aria-current="page"]').count(), 1);
		const form = page.locator('#pot-section-settings');
		await Promise.all([page.waitForURL('**settings-updated=true'), form.getByRole('button', { name: 'Save changes', exact: true }).click()]);
		assert.match(page.url(), new RegExp(`section=${section}`));
		assert.deepEqual(JSON.parse(wp('echo wp_json_encode(pot_settings());')), before, `Saving ${section} changed stored settings`);
	}
	await page.screenshot({ path: `${artifacts}/offers-desktop.png`, fullPage: true });
	const toggle = page.locator('#pot-offer-enabled');
	const initial = await toggle.isChecked();
	await toggle.setChecked(!initial);
	await page.locator('#pot-offer-toggle-status').filter({ hasText: 'saved.' }).waitFor();
	assert.equal(JSON.parse(wp('echo wp_json_encode(pot_settings()["offer_funnel"]);')), initial ? 0 : 1);
	await toggle.setChecked(initial);
	await page.locator('#pot-offer-toggle-status').filter({ hasText: 'saved.' }).waitFor();
	// Failed immediate saving restores the persisted state.
	await page.route('**/admin-ajax.php', (route) => route.request().postData()?.includes('action=pot_set_offer_enabled') ? route.fulfill({ status: 500, contentType: 'application/json', body: '{"success":false}' }) : route.continue());
	await toggle.setChecked(!initial);
	await page.locator('#pot-offer-toggle-status').filter({ hasText: 'Could not save' }).waitFor();
	assert.equal(await toggle.isChecked(), initial);
	await page.unroute('**/admin-ajax.php');
	// Actual nonce validation, not a mocked save response.
	const denied = await context.request.post(`${site}/wp-admin/admin-ajax.php`, { form: { action: 'pot_set_offer_enabled', enabled: '1', nonce: 'invalid' } });
	assert.equal(denied.status(), 403);
	await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize&section=checkout`);
	const extrasBefore = JSON.parse(wp('echo wp_json_encode(pot_checkout_settings());'));
	const extraForm = page.locator('#pot-checkout-settings');
	await Promise.all([page.waitForURL('**settings-updated=true'), extraForm.getByRole('button', { name: 'Save checkout features' }).click()]);
	assert.deepEqual(JSON.parse(wp('echo wp_json_encode(pot_checkout_settings());')), extrasBefore);
	assert.deepEqual(JSON.parse(wp('echo wp_json_encode(pot_settings());')), before);
	await page.setViewportSize({ width: 390, height: 844 });
	await page.screenshot({ path: `${artifacts}/checkout-settings-mobile.png`, fullPage: true });
	assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile admin overflow');
	await page.goto(`${site}/wp-admin/themes.php?page=purple-optimize-setup`);
	await page.getByRole('link', { name: 'Open toolkit settings' }).waitFor();
	}
	// Disable the optional redirect funnel for the baseline cart/checkout journey.
	wp('$s=pot_settings(); $s["offer_funnel"]=0; update_option("pot_settings",$s);');
	// Guest storefront and native add-to-cart drawer, with no order submitted.
	const guest = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
	shop = await guest.newPage();
	shop.on('pageerror', (error) => errors.push(error.message));
	await shop.goto(site);
	await shop.locator('header.wp-block-template-part').waitFor();
	await shop.screenshot({ path: `${artifacts}/home-desktop.png`, fullPage: true });
	const productUrl = wp('$p=wc_get_products(array("type"=>"simple","status"=>"publish","limit"=>1))[0]; echo get_permalink($p->get_id());');
	await shop.goto(productUrl);
	await shop.locator('.wp-block-woocommerce-add-to-cart-with-options').waitFor();
	const add = shop.locator('.wp-block-woocommerce-add-to-cart-with-options .wp-block-woocommerce-product-button button').first();
	await add.click();
	await shop.waitForFunction(() => document.querySelector('.wc-block-mini-cart__drawer')?.getAttribute('aria-hidden') === 'false');
	await shop.locator('.wc-block-mini-cart__drawer').waitFor({ state: 'visible' });
	await shop.locator('.wc-block-mini-cart__drawer .wc-block-components-product-name:visible').first().waitFor({ state: 'visible' });
	await shop.goto(`${site}/cart/`);
	await shop.locator('.wc-block-cart__submit-button').waitFor();
	await Promise.all([shop.waitForURL('**/checkout/**'), shop.locator('.wc-block-cart__submit-button').click()]);
	await shop.locator('.wc-block-components-checkout-place-order-button').waitFor();
	assert.equal(await shop.locator('.wc-block-components-checkout-place-order-button').count(), 1);
	await shop.screenshot({ path: `${artifacts}/checkout-desktop.png`, fullPage: true });
	await shop.setViewportSize({ width: 390, height: 844 });
	assert.ok(await shop.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile checkout overflow');
	await shop.screenshot({ path: `${artifacts}/checkout-mobile.png`, fullPage: true });
	await shop.goto(site);
	await shop.locator('.wp-block-navigation__responsive-container-open').click();
	await shop.getByRole('button', { name: /close/i }).first().waitFor();
	assert.ok(await shop.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile navigation overflow');
	await guest.close();
	assert.deepEqual(errors, []);
	console.log('PASS: combined requirements, scoped saves/read-back, immediate-save success/failure, nonce rejection, independent checkout settings, responsive admin, active toolkit setup, product add-to-cart drawer, cart, checkout and mobile navigation.');
} catch (error) {
	await page.screenshot({ path: `${artifacts}/failure.png`, fullPage: true });
	if (shop) { await shop.screenshot({ path: `${artifacts}/storefront-failure.png`, fullPage: true }); console.error('Storefront URL:', shop.url(), (await shop.locator('main').innerText()).slice(0,1200)); }
	throw error;
} finally {
	wp(`$v=unserialize(base64_decode('${original}')); update_option('pot_settings',$v[0]); update_option('pot_checkout_settings',$v[1]);`);
	await browser.close();
}
