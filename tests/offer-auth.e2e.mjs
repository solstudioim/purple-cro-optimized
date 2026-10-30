/** Local-only guest/customer offer sessions and focused offer layout. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { randomUUID } from 'node:crypto';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
assert.ok(process.env.POT_WP_PATH && process.env.POT_ARTIFACTS);
const wp = (code,input) => execFileSync(process.env.POT_PHP || 'php',['-d','error_reporting=22527','/usr/local/bin/wp','eval',code,`--path=${process.env.POT_WP_PATH}`],{encoding:'utf8',input}).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname,/^(localhost|127\.0\.0\.1)$/);
const fixture = JSON.parse(wp('echo wp_json_encode(get_option("pot_checkout_test_fixture"));'));
assert.equal(fixture.products.length,5);
const original = wp('echo base64_encode(serialize(get_option("pot_settings")));');
const login = 'pot_offer_' + randomUUID().slice(0,8);
const password = randomUUID();
const user = Number(wp('$v=json_decode(file_get_contents("php://stdin"),true);$id=wp_insert_user(array("user_login"=>$v["login"],"user_pass"=>$v["password"],"role"=>"customer"));if(is_wp_error($id)){throw new Exception($id->get_error_message());}echo $id;',JSON.stringify({login,password})));
const browser = await chromium.launch({headless:true});
const errors=[];
mkdirSync(process.env.POT_ARTIFACTS,{recursive:true});
async function navigate(page,url){await page.goto(url,{waitUntil:'networkidle'});}
async function reject(page){await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('.pot-offer-reject').click()]);}
async function signIn(page){
 await navigate(page,`${site}/wp-login.php`);
 await page.locator('#user_login').fill(login);
 await page.locator('#user_pass').fill(password);
 await Promise.all([page.waitForURL(url=>!url.pathname.endsWith('wp-login.php'),{waitUntil:'networkidle'}),page.locator('#wp-submit').click()]);
}
try {
 wp(`$s=pot_defaults();$s['offer_funnel']=1;$s['offer_placement']='pre_checkout';$s['upsell_product_id']=${fixture.products[1]};$s['downsell_product_id']=${fixture.products[2]};update_option('pot_settings',$s);`);
 for (const authenticated of (process.env.POT_OFFER_IDENTITY === 'customer' ? [true] : [false,true])) {
  const context=await browser.newContext({viewport:{width:1440,height:1000}});
  const page=await context.newPage();
  page.on('pageerror',error=>errors.push(error.message));
  if(authenticated) await signIn(page);
  await navigate(page,`${site}/?add-to-cart=${fixture.products[0]}`);
  if(authenticated){
   const customer=wp(`wp_set_current_user(${user});$s=new WC_Session_Handler();$s->init();$s->set('pot_offer_funnel_complete',1);$s->set('pot_inline_offer_stage','resolved');$s->__unset('pot_offer_context');$s->save_data();echo $s->get_customer_id();`);
   assert.equal(Number(customer),user,'Seed the signed-in customer session, not a guest cart.');
  }
  await navigate(page,`${site}/checkout/`);
  await page.locator('.pot-offer-card').waitFor();
  assert.equal(new URL(page.url()).searchParams.get('step'),'upsell');
  const expiry=await page.locator('[data-offer-expiry]').getAttribute('data-offer-expiry');
  await page.reload({waitUntil:'networkidle'});
  assert.equal(await page.locator('[data-offer-expiry]').getAttribute('data-offer-expiry'),expiry,'Offer refresh must retain its countdown.');
  const card=await page.locator('.pot-offer-card').evaluate(el=>({border:getComputedStyle(el).borderTopWidth,radius:getComputedStyle(el).borderRadius,width:el.getBoundingClientRect().width}));
  assert.ok(parseFloat(card.border)>0 && parseFloat(card.radius)>0 && card.width<1440,'Offer has a focused boxed boundary.');
  await page.screenshot({path:`${process.env.POT_ARTIFACTS}/${authenticated?'customer':'guest'}-upsell-desktop.png`,fullPage:true});
  await page.setViewportSize({width:390,height:844});
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'No mobile offer overflow.');
  await page.screenshot({path:`${process.env.POT_ARTIFACTS}/${authenticated?'customer':'guest'}-upsell-mobile.png`,fullPage:true});
  await reject(page);
  assert.equal(new URL(page.url()).searchParams.get('step'),'downsell');
  await reject(page);
  await page.locator('.wc-block-components-checkout-place-order-button').waitFor();
  await page.reload({waitUntil:'networkidle'});
  assert.equal(await page.locator('.pot-offer-card').count(),0,'Unchanged cart keeps its decline.');
  await navigate(page,`${site}/?add-to-cart=${fixture.products[3]}`);
  await navigate(page,`${site}/checkout/`);
  await page.locator('.pot-offer-card').waitFor();
  assert.equal(new URL(page.url()).searchParams.get('step'),'upsell','Changed cart is eligible again for either identity.');
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('.pot-offer-accept').click()]);
  await page.locator('.wc-block-components-checkout-place-order-button').waitFor();
  await page.reload({waitUntil:'networkidle'});
  assert.equal(await page.locator('.pot-offer-card').count(),0,'Accepting does not cause an offer loop.');
  await context.close();
  console.log(`PASS: ${authenticated?'signed-in customer with stale legacy state':'guest'} offer, decline/refresh, changed cart, acceptance/no-loop, desktop/mobile focused card.`);
 }
 assert.deepEqual(errors,[]);
} finally {
 wp(`update_option('pot_settings',unserialize(base64_decode('${original}')));require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user(${user});`);
 await browser.close();
}
