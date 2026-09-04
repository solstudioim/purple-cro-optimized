/** Local-only browser checks. Run after the PHP integration runner's prepare mode. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = process.env.POT_WP_PATH;
assert.ok(root, 'Set POT_WP_PATH to a local WordPress install.');
const wp = (code, input) => execFileSync('/opt/homebrew/opt/php@8.1/bin/php', ['/usr/local/bin/wp', 'eval', code, `--path=${root}`], { encoding: 'utf8', input }).trim();
const fixture = JSON.parse(wp('echo wp_json_encode(get_option("pot_checkout_test_fixture"));'));
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /\.(test|localhost)$/);
assert.equal(fixture.products.length, 5);
const artifacts = new URL('../artifacts/checkout-extras/', import.meta.url).pathname;
await mkdir(artifacts, { recursive: true });
console.log('Fixtures loaded; launching browser.');
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
const page = await context.newPage();
page.setDefaultTimeout(15000);
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
try {
	const cartResponse = await context.request.get(`${site}/wp-json/wc/store/v1/cart`);
	console.log('Guest cart loaded.');
	const nonce = cartResponse.headers().nonce;
	assert.ok(nonce, 'Store API must issue a nonce.');
	const rejected = await context.request.post(`${site}/wp-json/wc/store/v1/cart/extensions`, { data: { namespace: 'purple-checkout', data: { id: fixture.products[1], selected: true } } });
	assert.equal(rejected.status(), 401, 'A mutation without the Store API nonce must be rejected.');
	const added = await context.request.post(`${site}/wp-json/wc/store/v1/cart/add-item`, { headers: { Nonce: nonce }, data: { id: fixture.products[0], quantity: 1 } });
	assert.equal(added.status(), 201);
	await page.goto(`${site}/checkout/`, { waitUntil: 'domcontentloaded' });
	for (let step = 0; step < 2 && page.url().includes('/special-offer/'); step++) {
		const previous = page.url();
		await Promise.all([page.waitForURL((url) => url.href !== previous, { waitUntil: 'domcontentloaded' }), page.locator('.pot-offer-reject').click()]);
	}
	console.log('Checkout page loaded:', page.url());
	await page.locator('.pot-checkout-upsell').first().waitFor();
	assert.equal(await page.locator('.pot-checkout-upsell').count(), 4);
	assert.equal(await page.locator('.pot-checkout-content').count(), 1);
	assert.equal(await page.locator('.pot-checkout-content img').count(), 1);
	assert.equal(await page.locator('.pot-checkout-upsell input:checked').count(), 0);
	const correctPlacement = await page.evaluate(() => {
		const upsells = document.querySelector('.pot-checkout-upsells');
		const actions = document.querySelector('.wc-block-checkout__actions_row');
		const content = document.querySelector('.pot-checkout-content');
		const summary = document.querySelector('.wp-block-woocommerce-checkout-order-summary-block');
		return Boolean(upsells.compareDocumentPosition(actions) & Node.DOCUMENT_POSITION_FOLLOWING) && Boolean(summary.compareDocumentPosition(content) & Node.DOCUMENT_POSITION_FOLLOWING);
	});
	assert.ok(correctPlacement, 'Extras must occupy the requested positions.');
	const email = page.locator('#email');
	await email.fill('checkout-test@example.test');
	await page.locator('.pot-checkout-upsell button').first().click();
	await page.getByRole('status').filter({ hasText: 'Added Checkout test:' }).waitFor();
	assert.equal(await email.inputValue(), 'checkout-test@example.test');
	assert.equal(await page.locator('.pot-checkout-upsell input:checked').count(), 1);
	const cartState = () => page.evaluate(() => wp.data.select(wc.wcBlocksData.CART_STORE_KEY).getCartData());
	let cart = await cartState();
	assert.equal(cart.items.length, 2);
	assert.equal(cart.totals.total_items, '11500');
	await page.reload({ waitUntil: 'domcontentloaded' });
	await page.locator('.pot-checkout-upsell input:checked').waitFor();
	await page.locator('.pot-checkout-upsell input').nth(1).click();
	await page.getByRole('status').filter({ hasText: 'Added Checkout test: Gift wrap' }).waitFor();
	cart = await cartState();
	assert.equal(cart.totals.total_items, '13000');
	await page.locator('.pot-checkout-upsell input').first().click();
	await page.getByRole('status').filter({ hasText: 'Removed Checkout test:' }).waitFor();
	cart = await cartState();
	assert.equal(cart.totals.total_items, '11500');
	assert.equal(cart.items.length, 2);
	await page.evaluate(() => window.scrollTo(0, 0));
	await page.screenshot({ path: `${artifacts}desktop.png`, fullPage: true });
	await page.setViewportSize({ width: 390, height: 844 });
	await page.screenshot({ path: `${artifacts}mobile.png`, fullPage: true });
	assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'Mobile checkout must not overflow.');
	assert.equal(await page.locator('.wc-block-components-checkout-place-order-button').count(), 1, 'Keep one native Place order button.');
	// A failed request must remain recoverable and must not select an extra.
	wp(`$p=wc_get_product(${Number(fixture.products[3])}); $p->set_stock_status('outofstock'); $p->save();`);
	await page.locator('.pot-checkout-upsell button').nth(2).click();
	await page.getByRole('alert').filter({ hasText: 'This add-on cannot be added.' }).waitFor();
	assert.equal(await page.locator('.pot-checkout-upsell input').nth(2).isChecked(), false);
	wp(`$p=wc_get_product(${Number(fixture.products[3])}); $p->set_stock_status('instock'); $p->save();`);
	await page.locator('.pot-checkout-upsell button').nth(2).click();
	await page.getByRole('status').filter({ hasText: 'Added Checkout test: Care kit' }).waitFor();
	assert.equal(await page.locator('.pot-checkout-upsell input').nth(2).isChecked(), true);

	// Use a short-lived local admin session; never print or save the cookie/token.
	const auth = JSON.parse(wp('$u=get_users(array("role"=>"administrator","number"=>1))[0]; $t=WP_Session_Tokens::get_instance($u->ID)->create(time()+300); echo wp_json_encode(array("id"=>$u->ID,"token"=>$t,"name"=>LOGGED_IN_COOKIE,"cookie"=>wp_generate_auth_cookie($u->ID,time()+300,"logged_in",$t),"adminName"=>AUTH_COOKIE,"adminCookie"=>wp_generate_auth_cookie($u->ID,time()+300,"auth",$t)));'));
	const adminContext = await browser.newContext();
	try {
		await adminContext.addCookies([{ name: auth.name, value: auth.cookie, url: site }, { name: auth.adminName, value: auth.adminCookie, url: site }]);
		const admin = await adminContext.newPage();
		admin.setDefaultTimeout(15000);
		await admin.goto(`${site}/wp-admin/admin.php?page=purple-optimize`, { waitUntil: 'domcontentloaded' });
		const form = admin.locator('#pot-checkout-settings');
		await form.waitFor();
		await form.getByRole('button', { name: 'Add Media' }).click();
		await admin.locator('.media-modal').waitFor();
		await admin.locator('.media-modal-close').click();
		await form.locator('#pot-checkout-title-0').fill('A helpful optional extra');
		await form.locator('#pot_checkout_content-html').click();
		await form.locator('#pot_checkout_content').fill('<h3>Shop with confidence</h3><p>Read our delivery and returns information.</p>');
		await Promise.all([admin.waitForURL('**/admin.php?page=purple-optimize&settings-updated=true', { waitUntil: 'domcontentloaded' }), form.getByRole('button', { name: 'Save checkout features' }).click()]);
		assert.equal(await admin.locator('#pot-checkout-title-0').inputValue(), 'A helpful optional extra');
		await page.reload({ waitUntil: 'domcontentloaded' });
		await page.locator('.pot-checkout-content').getByRole('heading', { name: 'Shop with confidence' }).waitFor();
		assert.equal(await page.locator('.pot-checkout-upsell strong').first().innerText(), 'A helpful optional extra');
		// Each feature can be disabled without changing the earlier funnel settings.
		await admin.locator('[name="pot_checkout_settings[upsells_enabled]"]').uncheck();
		await admin.locator('[name="pot_checkout_settings[content_enabled]"]').uncheck();
		await Promise.all([admin.waitForEvent('domcontentloaded'), admin.getByRole('button', { name: 'Save checkout features' }).click()]);
		await page.reload({ waitUntil: 'domcontentloaded' });
		await page.locator('.wc-block-components-checkout-place-order-button').waitFor();
		assert.equal(await page.locator('.pot-checkout-upsells, .pot-checkout-content').count(), 0);
	} finally {
		// Keep the temporary secret off command lines, logs, and disk.
		wp('$a=json_decode(file_get_contents("php://stdin"),true); WP_Session_Tokens::get_instance($a["id"])->destroy($a["token"]);', JSON.stringify({ id: auth.id, token: auth.token }));
		await adminContext.close();
	}
	assert.deepEqual(errors, [], 'Frontend must have no uncaught JS errors.');
	console.log('PASS: four offers, live prices/totals, add/remove, retained email, reload persistence, text/image panel, placement, nonce rejection, failure recovery, mobile overflow, one Place order button, admin save/read-back, Media Library, disabled state.');
	console.log(`Screenshots: ${artifacts}`);
} catch (error) {
	await page.screenshot({ path: `${artifacts}failure.png`, fullPage: true });
	console.error('URL:', page.url(), 'Browser errors:', errors);
	throw error;
} finally {
	await browser.close();
}
