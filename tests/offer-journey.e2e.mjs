/** Local-only offer-placement journeys; never submit payment or send order email. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
assert.ok(process.env.POT_WP_PATH);
const wp = (code) => execFileSync(process.env.POT_PHP || 'php', ['-d','error_reporting=22527','/usr/local/bin/wp','eval',code,`--path=${process.env.POT_WP_PATH}`], { encoding:'utf8' }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /^(localhost|127\.0\.0\.1)$/);
const fixture = JSON.parse(wp('echo wp_json_encode(get_option("pot_checkout_test_fixture"));'));
assert.equal(fixture.products.length,5,'Prepare checkout test fixtures first.');
const original = wp('echo base64_encode(serialize(get_option("pot_settings")));');
const browser = await chromium.launch({ headless:true });
let order;
const errors = [];
const configure = (placement) => wp(`$s=pot_defaults();$s['offer_funnel']=1;$s['offer_placement']='${placement}';$s['upsell_product_id']=${fixture.products[1]};$s['downsell_product_id']=${fixture.products[2]};update_option('pot_settings',$s);`);
async function guest() {
 const context = await browser.newContext();
 const page = await context.newPage();
 page.on('pageerror',error=>errors.push(error.message));
 const cart = await context.request.get(`${site}/wp-json/wc/store/v1/cart`);
 const add = await context.request.post(`${site}/wp-json/wc/store/v1/cart/add-item`, { headers:{Nonce:cart.headers().nonce},data:{id:fixture.products[0],quantity:1} });
 assert.equal(add.status(),201);
 return {context,page};
}
async function click(page, selector) {
 await Promise.all([page.waitForNavigation({ waitUntil:'networkidle' }),page.locator(selector).click()]);
}
try {
 for (const placement of ['pre_checkout','checkout_inline']) {
  configure(placement);
  const {context,page} = await guest();
  await page.goto(`${site}/checkout/`, { waitUntil:'networkidle' });
  const surface = placement === 'pre_checkout' ? '.pot-offer-shell' : '.pot-inline-offer';
  await page.locator(`${surface} .pot-offer-reject`).waitFor();
  if (placement === 'checkout_inline') {
   assert.ok(await page.evaluate(()=>Boolean(document.querySelector('.pot-inline-offer').compareDocumentPosition(document.querySelector('.wc-block-checkout__actions_row')) & Node.DOCUMENT_POSITION_FOLLOWING)));
  }
  await click(page, `${surface} .pot-offer-reject`);
  if (placement === 'pre_checkout') assert.equal(new URL(page.url()).searchParams.get('step'),'downsell');
  else assert.equal(await page.locator('[name="pot_offer_step"]').inputValue(),'downsell');
  await click(page, `${surface} .pot-offer-accept`);
  assert.match(page.url(),/\/checkout\//);
  await page.locator('.wc-block-components-checkout-place-order-button').waitFor();
  const cart = await (await context.request.get(`${site}/wp-json/wc/store/v1/cart`)).json();
  assert.equal(cart.items.find(item=>item.id===fixture.products[2]).prices.price,'450');
  assert.equal(cart.totals.total_items,'10450');
  await page.reload({waitUntil:'networkidle'});
  assert.equal(await page.locator('.pot-inline-offer,.pot-offer-shell').count(),0,'Resolved offers do not repeat for the same cart.');
  await context.close();
  console.log(`PASS: ${placement} upsell -> reject -> downsell -> accept, discounted cart total, no repeated offer.`);
 }
 configure('post_purchase');
 // An offline synthetic order exercises the receipt redirect; no payment/email hooks run.
 order = JSON.parse(wp(`$o=wc_create_order();$o->add_product(wc_get_product(${fixture.products[0]}),1);$o->calculate_totals();$o->save();echo wp_json_encode(array('id'=>$o->get_id(),'url'=>$o->get_checkout_order_received_url(),'total'=>$o->get_total(),'items'=>$o->get_item_count()));`));
 const context = await browser.newContext();
 const page = await context.newPage();
 page.on('pageerror',error=>errors.push(error.message));
 await page.goto(order.url,{waitUntil:'networkidle'});
 await page.locator('.pot-offer-shell .pot-offer-accept').waitFor();
 assert.match(page.url(),/context=post_purchase/);
 await click(page,'.pot-offer-accept');
 await page.locator('.wc-block-components-checkout-place-order-button').waitFor();
 const cart = await (await context.request.get(`${site}/wp-json/wc/store/v1/cart`)).json();
 assert.equal(cart.items.length,1);
 assert.equal(cart.items[0].id,fixture.products[1]);
 assert.equal(cart.items[0].prices.price,'750');
 const unchanged = JSON.parse(wp(`$o=wc_get_order(${order.id});echo wp_json_encode(array('total'=>$o->get_total(),'items'=>$o->get_item_count()));`));
 assert.deepEqual(unchanged,{total:order.total,items:order.items},'Completed/source order must remain unchanged.');
 await context.close();
 assert.deepEqual(errors,[]);
 console.log('PASS: post-purchase offer opens separate discounted checkout and leaves the source order unchanged; no payment submitted.');
} finally {
 wp(`update_option('pot_settings',unserialize(base64_decode('${original}')));`);
 if(order) wp(`$o=wc_get_order(${order.id});if($o){$o->delete(true);}`);
 await browser.close();
}
