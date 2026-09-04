/** Local-only admin/storefront test. Original options and temporary login are restored. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = process.env.POT_WP_PATH;
assert.ok(root, 'Set POT_WP_PATH to a local WordPress install.');
const wp = (code, input) => execFileSync('/opt/homebrew/opt/php@8.1/bin/php', ['/usr/local/bin/wp', 'eval', code, `--path=${root}`], { encoding: 'utf8', input }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /\.(test|localhost)$/);
const original = JSON.parse(wp('echo wp_json_encode(array("settings"=>get_option("pot_settings"),"extras"=>get_option("pot_checkout_settings")));'));
const snapshot = () => JSON.parse(wp('echo wp_json_encode(array("settings"=>pot_settings(),"extras"=>get_option("pot_checkout_settings")));'));
const browser = await chromium.launch({ headless: true });
let auth;
try {
	// Exercise the pre-checkout redirect using existing catalog products; no orders are placed.
	const fixture = JSON.parse(wp('$s=pot_settings(); $ids=array(); foreach(wc_get_products(array("type"=>"simple","status"=>"publish","limit"=>30)) as $p){if(!pot_offer_product_issue_for_product($p)){$ids[]=$p->get_id();}} if(count($ids)<2){throw new Exception("Need two eligible local products");} $s["offer_placement"]="pre_checkout"; $s["upsell_product_id"]=$ids[0]; $s["upsell_countdown"]=0; $s["offer_funnel"]=1; update_option("pot_settings",$s); echo wp_json_encode(array("base"=>$ids[1]));'));
	const before = snapshot();
	auth = JSON.parse(wp('$u=get_users(array("role"=>"administrator","number"=>1))[0]; $t=WP_Session_Tokens::get_instance($u->ID)->create(time()+300); echo wp_json_encode(array("id"=>$u->ID,"token"=>$t,"name"=>LOGGED_IN_COOKIE,"cookie"=>wp_generate_auth_cookie($u->ID,time()+300,"logged_in",$t),"adminName"=>AUTH_COOKIE,"adminCookie"=>wp_generate_auth_cookie($u->ID,time()+300,"auth",$t)));'));
	const adminContext = await browser.newContext();
	await adminContext.addCookies([{ name: auth.name, value: auth.cookie, url: site }, { name: auth.adminName, value: auth.adminCookie, url: site }]);
	const page = await adminContext.newPage();
	page.setDefaultTimeout(15000);
	await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize`, { waitUntil: 'domcontentloaded' });
	const toggle = page.locator('#pot-offer-enabled');
	assert.equal(await toggle.isChecked(), true);
	await toggle.click();
	await page.getByRole('status').filter({ hasText: 'Disabled — saved.' }).waitFor();
	const after = snapshot();
	assert.equal(after.settings.offer_funnel, 0);
	assert.deepEqual({ ...after.settings, offer_funnel: before.settings.offer_funnel }, before.settings);
	assert.deepEqual(after.extras, before.extras);
	await page.reload({ waitUntil: 'domcontentloaded' });
	assert.equal(await toggle.isChecked(), false);

	const guest = await browser.newContext();
	const cart = await guest.request.get(`${site}/wp-json/wc/store/v1/cart`);
	const added = await guest.request.post(`${site}/wp-json/wc/store/v1/cart/add-item`, { headers: { Nonce: cart.headers().nonce }, data: { id: fixture.base, quantity: 1 } });
	assert.equal(added.status(), 201);
	const shopper = await guest.newPage();
	await shopper.goto(`${site}/checkout/`, { waitUntil: 'domcontentloaded' });
	assert.match(shopper.url(), /\/checkout\//);
	await shopper.goto(`${site}/special-offer/?step=upsell`, { waitUntil: 'domcontentloaded' });
	assert.match(shopper.url(), /\/checkout\//, 'Disabled bookmarked offers return to checkout.');

	// Server security and client failure recovery.
	const denied = await guest.request.post(`${site}/wp-admin/admin-ajax.php`, { form: { action: 'pot_set_offer_enabled', enabled: '1' } });
	assert.equal(denied.status(), 400);
	const invalidNonce = await adminContext.request.post(`${site}/wp-admin/admin-ajax.php`, { form: { action: 'pot_set_offer_enabled', enabled: '1', nonce: 'invalid' } });
	assert.equal(invalidNonce.status(), 403);
	await page.route('**/admin-ajax.php', (route) => route.request().postData()?.includes('action=pot_set_offer_enabled') ? route.fulfill({ status: 500, contentType: 'application/json', body: '{"success":false}' }) : route.continue());
	await toggle.click();
	await page.getByRole('status').filter({ hasText: 'Could not save.' }).waitFor();
	assert.equal(await toggle.isChecked(), false);
	await page.unroute('**/admin-ajax.php');
	await toggle.click();
	await page.getByRole('status').filter({ hasText: 'Enabled — saved.' }).waitFor();
	assert.equal(snapshot().settings.offer_funnel, 1);
	await shopper.goto(`${site}/checkout/`, { waitUntil: 'domcontentloaded' });
	assert.match(shopper.url(), /\/special-offer\//);
	await page.reload({ waitUntil: 'domcontentloaded' });
	assert.equal(await toggle.isChecked(), true);
	console.log('PASS: immediate enable/disable, reload persistence, other settings preserved, disabled checkout/bookmark bypass, enabled redirect, nonce/auth rejection, failure rollback/retry.');
} finally {
	wp('$a=json_decode(file_get_contents("php://stdin"),true); update_option("pot_settings",$a["settings"]);', JSON.stringify(original));
	if (auth) wp('$a=json_decode(file_get_contents("php://stdin"),true); WP_Session_Tokens::get_instance($a["id"])->destroy($a["token"]);', JSON.stringify({ id: auth.id, token: auth.token }));
	await browser.close();
	assert.deepEqual(JSON.parse(wp('echo wp_json_encode(get_option("pot_settings"));')), original.settings);
	console.log('Original settings restored; temporary admin session revoked.');
}
