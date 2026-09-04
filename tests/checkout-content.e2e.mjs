/** Local-only native editor + checkout test; restores settings and deletes only its own document. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = process.env.POT_WP_PATH;
assert.ok(root, 'Set POT_WP_PATH to a local WordPress install.');
const wp = (code, input) => execFileSync('/opt/homebrew/opt/php@8.1/bin/php', ['/usr/local/bin/wp', 'eval', code, `--path=${root}`], { encoding: 'utf8', input }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /\.(test|localhost)$/);
const original = JSON.parse(wp('echo wp_json_encode(array("id"=>get_option("pot_checkout_content_id",false),"extras"=>get_option("pot_checkout_settings",false)));'));
const out = new URL('../artifacts/checkout-block-editor/', import.meta.url).pathname;
await mkdir(out, { recursive: true });
let auth, id;
const browser = await chromium.launch({ headless: true });
try {
	wp('delete_option("pot_checkout_content_id");$s=pot_checkout_settings();$s["content_enabled"]=1;$s["content"]="<p>Legacy <strong>preserved</strong> content.</p>";update_option("pot_checkout_settings",$s);');
	auth = JSON.parse(wp('$u=get_users(array("role"=>"administrator","number"=>1))[0];$t=WP_Session_Tokens::get_instance($u->ID)->create(time()+600);echo wp_json_encode(array("id"=>$u->ID,"token"=>$t,"name"=>LOGGED_IN_COOKIE,"cookie"=>wp_generate_auth_cookie($u->ID,time()+600,"logged_in",$t),"adminName"=>AUTH_COOKIE,"adminCookie"=>wp_generate_auth_cookie($u->ID,time()+600,"auth",$t)));'));
	const admin = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
	await admin.addCookies([{ name: auth.name, value: auth.cookie, url: site }, { name: auth.adminName, value: auth.adminCookie, url: site }]);
	const page = await admin.newPage();
	page.setDefaultTimeout(20000);
	await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize`, { waitUntil: 'domcontentloaded' });
	assert.equal(await page.locator('#pot_checkout_content').count(), 0, 'Old rich-text editor removed.');
	const invalid = await admin.request.get(`${site}/wp-admin/admin-post.php?action=pot_edit_checkout_content&_wpnonce=invalid`);
	assert.equal(invalid.status(), 403);
	await page.locator('#pot-edit-checkout-content').click();
	await page.waitForURL('**/post.php?post=**');
	id = Number(new URL(page.url()).searchParams.get('post'));
	await page.waitForFunction(() => window.wp?.data?.select('core/editor')?.getCurrentPostId());
	assert.equal(wp('echo get_post((int)get_option("pot_checkout_content_id"))->post_content;'), '<p>Legacy <strong>preserved</strong> content.</p>');
	const close = page.getByRole('button', { name: 'Close', exact: true });
	if (await close.isVisible()) await close.click();
	await page.evaluate(() => {
		const { createBlock: block } = wp.blocks;
		const blocks = [
			block('core/heading', { content: 'Helpful delivery information', level: 2 }),
			block('core/paragraph', { content: 'A <strong>bold promise</strong> with an <em>important detail</em>.' }),
			block('core/paragraph', { content: 'Custom text styling', align: 'right', style: { color: { text: '#aa2211' }, typography: { fontSize: '19px' } } }),
			block('core/list', {}, [block('core/list-item', { content: 'Tracked delivery' }), block('core/list-item', { content: 'Contact our team' })]),
			block('core/group', { layout: { type: 'flex', flexWrap: 'nowrap' }, style: { spacing: { blockGap: '12px' }, color: { background: '#eeeeff' } } }, [
				block('core/paragraph', { content: 'First group item' }), block('core/paragraph', { content: 'Second group item' }),
			]),
			block('core/columns', {}, [block('core/column', {}, [block('core/paragraph', { content: 'Column one' })]), block('core/column', {}, [block('core/paragraph', { content: 'Column two' })])]),
			block('core/spacer', { height: '32px' }),
			block('core/buttons', {}, [block('core/button', { text: 'Read delivery details', url: '/shop/' })]),
		];
		wp.data.dispatch('core/block-editor').resetBlocks(blocks);
	});
	const image = JSON.parse(wp('$a=get_posts(array("post_type"=>"attachment","post_mime_type"=>"image","numberposts"=>1));echo wp_json_encode($a?array("id"=>$a[0]->ID,"url"=>wp_get_attachment_url($a[0]->ID)):null);'));
	assert.ok(image, 'Need one existing Media Library image.');
	await page.evaluate((image) => wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('core/image', { id: image.id, url: image.url, alt: 'Checkout test illustration', sizeSlug: 'medium', width: '120px' })), image);
	await page.getByRole('button', { name: 'Save', exact: true }).click();
	await page.waitForFunction(() => !wp.data.select('core/editor').isSavingPost() && !wp.data.select('core/editor').isEditedPostDirty());
	await page.reload({ waitUntil: 'domcontentloaded' });
	await page.waitForFunction(() => wp?.data?.select('core/block-editor')?.getBlocks().length > 5);
	assert.equal(await page.evaluate(() => wp.data.select('core/block-editor').getBlocks().every(b => b.isValid)), true);
	assert.equal(await page.evaluate(() => wp.data.select('core/block-editor').getBlocks().some(b => b.name === 'core/image')), true);
	await page.screenshot({ path: `${out}/editor.png` });
	const editor = await page.locator('iframe[name="editor-canvas"]').count() ? page.frameLocator('iframe[name="editor-canvas"]') : page.locator('.editor-styles-wrapper');
	await editor.locator('[data-type="core/heading"]').filter({ hasText: 'Helpful delivery information' }).waitFor();
	const boldStyle = await editor.locator('strong').first().evaluate(el => ({ weight: getComputedStyle(el).fontWeight, font: getComputedStyle(el.parentElement).fontSize, color: getComputedStyle(el.parentElement).color }));
	const guest = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
	const cart = await guest.request.get(`${site}/wp-json/wc/store/v1/cart`);
	const product = Number(wp('foreach(wc_get_products(array("type"=>"simple","status"=>"publish","limit"=>30)) as $p){if(!pot_offer_product_issue_for_product($p)){echo $p->get_id();break;}}'));
	assert.equal((await guest.request.post(`${site}/wp-json/wc/store/v1/cart/add-item`, { headers: { Nonce: cart.headers().nonce }, data: { id: product, quantity: 1 } })).status(), 201);
	const front = await guest.newPage();
	const errors = [];
	front.on('pageerror', error => errors.push(error.message));
	await front.goto(`${site}/checkout/`, { waitUntil: 'domcontentloaded' });
	for (let n = 0; n < 2 && front.url().includes('/special-offer/'); n++) {
		const old = front.url();
		await Promise.all([front.waitForURL(u => u.href !== old, { waitUntil: 'domcontentloaded' }), front.locator('.pot-offer-reject').click()]);
	}
	const panel = front.locator('.pot-checkout-content');
	await panel.getByRole('heading', { name: 'Helpful delivery information' }).waitFor();
	assert.deepEqual(await panel.locator('strong').evaluate(el => ({ weight: getComputedStyle(el).fontWeight, font: getComputedStyle(el.parentElement).fontSize, color: getComputedStyle(el.parentElement).color })), boldStyle);
	assert.equal(boldStyle.weight, '700');
	const customStyle = el => ({ color: getComputedStyle(el).color, size: getComputedStyle(el).fontSize, align: getComputedStyle(el).textAlign });
	const frontCustom = await panel.getByText('Custom text styling', { exact: true }).evaluate(customStyle);
	const editorCustom = await editor.getByText('Custom text styling', { exact: true }).evaluate(customStyle);
	assert.deepEqual({ ...frontCustom, size: null }, { ...editorCustom, size: null });
	assert.ok(Math.abs(parseFloat(frontCustom.size) - parseFloat(editorCustom.size)) < 1, 'Core fluid typography can vary slightly with canvas width.');
	assert.equal(await panel.locator('.wp-block-group').evaluate(el => getComputedStyle(el).display), 'flex');
	assert.equal(await panel.locator('.wp-block-group').evaluate(el => getComputedStyle(el).gap), '12px');
	assert.equal(await panel.locator('.wp-block-columns').evaluate(el => getComputedStyle(el).display), 'flex');
	assert.equal(await panel.locator('li').count(), 2);
	assert.equal(await panel.getByAltText('Checkout test illustration').evaluate(el => el.complete && el.naturalWidth > 0), true);
	await panel.screenshot({ path: `${out}/checkout-desktop.png` });
	await front.setViewportSize({ width: 390, height: 844 });
	assert.equal(await front.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'Mobile has no horizontal overflow.');
	await panel.screenshot({ path: `${out}/checkout-mobile.png` });
	// Settings save must preserve both the legacy backup and the published document.
	const source = wp('echo hash("sha256",pot_checkout_content_source());');
	await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize`, { waitUntil: 'domcontentloaded' });
	await page.locator('[name="pot_checkout_settings[content_enabled]"]').uncheck();
	await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#pot-checkout-settings input[type="submit"]').click()]);
	assert.equal(wp('echo hash("sha256",pot_checkout_content_source());'), source);
	await front.reload({ waitUntil: 'networkidle' });
	assert.equal(await front.locator('.pot-checkout-content').count(), 0);
	assert.deepEqual(errors, [], 'No uncaught checkout JavaScript errors.');
	console.log('PASS: native editor, migration, core block save/reload, image, layout CSS, bold parity, mobile, disable and nonce rejection.');
} finally {
	if (!id) id = Number(wp('echo (int)get_option("pot_checkout_content_id");'));
	wp('$a=json_decode(file_get_contents("php://stdin"),true);if($a["fixture"] && $a["fixture"]!==$a["original"]["id"]){wp_delete_post($a["fixture"],true);}if(false===$a["original"]["id"]){delete_option("pot_checkout_content_id");}else{update_option("pot_checkout_content_id",$a["original"]["id"]);}if(false===$a["original"]["extras"]){delete_option("pot_checkout_settings");}else{update_option("pot_checkout_settings",$a["original"]["extras"]);}', JSON.stringify({ original, fixture: id }));
	if (auth) wp('$a=json_decode(file_get_contents("php://stdin"),true);WP_Session_Tokens::get_instance($a["id"])->destroy($a["token"]);', JSON.stringify({ id: auth.id, token: auth.token }));
	await browser.close();
}
