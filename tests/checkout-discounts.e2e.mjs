/** Optional add-on pricing through real guest and signed-in customer checkout flows. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { randomUUID } from 'node:crypto';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
assert.ok(process.env.POT_WP_PATH && process.env.POT_ARTIFACTS);
const wp = (code, input) => execFileSync(process.env.POT_PHP || 'php', ['-d','error_reporting=22527','/usr/local/bin/wp','eval',code,`--path=${process.env.POT_WP_PATH}`], { encoding:'utf8', input }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /^(localhost|127\.0\.0\.1)$/);
const fixture = JSON.parse(wp('echo wp_json_encode(get_option("pot_checkout_test_fixture"));'));
assert.equal(fixture.products.length, 5);
const original = wp('echo base64_encode(serialize(array(get_option("pot_settings"),get_option("pot_checkout_settings"))));');
const login = 'pot_addon_' + randomUUID().slice(0,8);
const password = randomUUID();
const user = Number(wp('$v=json_decode(file_get_contents("php://stdin"),true);$id=wp_insert_user(array("user_login"=>$v["login"],"user_pass"=>$v["password"],"role"=>"customer"));if(is_wp_error($id))throw new Exception($id->get_error_message());echo $id;', JSON.stringify({login,password})));
const browser = await chromium.launch({ headless:true });
mkdirSync(process.env.POT_ARTIFACTS, { recursive:true });
const errors = [];
try {
 wp('$s=pot_settings();$s["offer_funnel"]=0;update_option("pot_settings",$s);$c=pot_checkout_settings();$c["upsells_enabled"]=1;$c["upsells"][0]["discount"]=20;$c["upsells"][0]["title"]="";$c["upsells"][1]["discount"]="";$c["upsells"][2]["discount"]=0;$c["upsells"][3]["discount"]=100;update_option("pot_checkout_settings",pot_sanitize_checkout_settings($c));');
 for (const authenticated of [false,true]) {
  const context = await browser.newContext({ viewport:{width:1440,height:1000} });
  const page = await context.newPage();
  page.on('pageerror', error=>errors.push(error.message));
  if (authenticated) {
   await page.goto(`${site}/wp-login.php`);
   await page.locator('#user_login').fill(login);
   await page.locator('#user_pass').fill(password);
   await Promise.all([page.waitForURL(url=>!url.pathname.endsWith('wp-login.php')),page.locator('#wp-submit').click()]);
  }
  await page.goto(`${site}/?add-to-cart=${fixture.products[0]}`,{waitUntil:'networkidle'});
  await page.goto(`${site}/checkout/`,{waitUntil:'networkidle'});
  const rows = page.locator('.pot-checkout-upsell');
  await rows.first().waitFor();
  assert.equal(await rows.count(),4);
  assert.match(await rows.nth(0).locator('.pot-checkout-upsell-price').innerText(), /12\.00/);
  assert.equal(await rows.nth(0).locator('.pot-checkout-upsell-savings').innerText(),'40% off · Save $8.00');
  assert.equal(await rows.nth(1).locator('.pot-checkout-upsell-savings').innerText(),'25% off · Save $5.00','Blank keeps only the native catalog sale saving');
  assert.equal(await rows.nth(2).locator('.pot-checkout-upsell-savings').innerText(),'25% off · Save $5.00','Zero keeps only the native catalog sale saving');
  const state = () => page.evaluate(()=>wp.data.select(wc.wcBlocksData.CART_STORE_KEY).getCartData());
  await rows.nth(0).getByRole('button').click();
  await page.getByRole('status').filter({hasText:'Added Checkout test: Travel pouch'}).waitFor();
  let cart = await state();
  assert.equal(cart.totals.total_items,'11200');
  assert.equal(cart.items.find(item=>item.id===fixture.products[1]).prices.price,'1200');
  const nativeItem = page.locator('.wc-block-components-order-summary-item').filter({hasText:'Checkout test: Travel pouch'});
  assert.equal(await nativeItem.locator('.wc-block-components-sale-badge').innerText(),'Save $8.00','Native summary keeps its normal currency badge');
  assert.ok((await rows.nth(0).locator('.pot-checkout-upsell-savings').innerText()).endsWith(await nativeItem.locator('.wc-block-components-sale-badge').innerText()), 'Combined label must use the same currency saving as the unchanged summary');
  assert.equal(await rows.nth(0).locator('.pot-checkout-upsell-savings').count(),1,'One combined savings label on each add-on card');
  assert.ok(cart.items.find(item=>item.id===fixture.products[1]).item_data.some(data=>data.value.includes('20% off')));
  await page.reload({waitUntil:'networkidle'});
  await rows.nth(0).locator('input:checked').waitFor();
  assert.equal((await state()).totals.total_items,'11200','A real Woo session reload keeps the accepted price');
  await rows.nth(1).getByRole('button').click();
  await page.getByRole('status').filter({hasText:'Added Checkout test: Gift wrap'}).waitFor();
  assert.equal((await state()).totals.total_items,'12700','Blank add-on uses current catalog sale price');
  await rows.nth(0).getByRole('button').click();
  await page.getByRole('status').filter({hasText:'Removed Checkout test: Travel pouch'}).waitFor();
  assert.equal((await state()).totals.total_items,'11500');
  await rows.nth(0).getByRole('button').click();
  await page.getByRole('status').filter({hasText:'Added Checkout test: Travel pouch'}).waitFor();
  await page.screenshot({path:`${process.env.POT_ARTIFACTS}/${authenticated?'customer':'guest'}-discount-desktop.png`,fullPage:true});
  await page.setViewportSize({width:390,height:844});
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  await page.screenshot({path:`${process.env.POT_ARTIFACTS}/${authenticated?'customer':'guest'}-discount-mobile.png`,fullPage:true});
  await context.close();
  console.log(`PASS: ${authenticated?'signed-in customer':'guest'} displayed discount, native line price/totals, blank/zero fallback, accepted session reload and add/remove.`);
 }
 // Save/read-back of the optional field is independent of general CRO settings.
 const admin = await browser.newContext();
 const page = await admin.newPage();
 await page.goto(`${site}/wp-login.php`);
 await page.locator('#user_login').fill(process.env.POT_TEST_USER || 'compat');
 await page.locator('#user_pass').fill(process.env.POT_TEST_PASSWORD || 'local-development-only');
 await Promise.all([page.waitForURL(url=>!url.pathname.endsWith('wp-login.php')),page.locator('#wp-submit').click()]);
 await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize&section=checkout`);
 const before = wp('echo wp_json_encode(pot_settings());');
 const save = async () => Promise.all([page.waitForURL('**settings-updated=true'),page.locator('#pot-checkout-settings').getByRole('button',{name:'Save checkout features'}).click()]);
 await page.locator('#pot-checkout-discount-0').fill('25');
 await save();
 assert.equal(await page.locator('#pot-checkout-discount-0').inputValue(),'25');
 assert.equal(wp('echo pot_checkout_settings()["upsells"][0]["discount"];'),'25');
 await page.screenshot({path:`${process.env.POT_ARTIFACTS}/addon-discount-settings.png`,fullPage:true});
 await page.locator('#pot-checkout-discount-0').fill('');
 await save();
 assert.equal(await page.locator('#pot-checkout-discount-0').inputValue(),'');
 assert.equal(wp('echo wp_json_encode(pot_checkout_settings()["upsells"][0]["discount"]);'),'""');
 assert.equal(before,wp('echo wp_json_encode(pot_settings());'));
 assert.deepEqual(errors,[]);
 await admin.close();
 console.log('PASS: optional percentage saves/read-back, clearing stays blank, unrelated settings unchanged.');
} finally {
 wp(`$v=unserialize(base64_decode('${original}'));update_option('pot_settings',$v[0]);update_option('pot_checkout_settings',$v[1]);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user(${user});`);
 await browser.close();
}
