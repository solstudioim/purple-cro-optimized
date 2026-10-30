/** Compare inherited presentation on an isolated local WordPress fixture. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = process.env.POT_WP_PATH;
assert.ok(root, 'POT_WP_PATH must identify an isolated local fixture.');
const wp = (code) => execFileSync(process.env.POT_PHP || 'php', ['-d', 'error_reporting=22527', '/usr/local/bin/wp', 'eval', code, `--path=${root}`], { encoding: 'utf8' }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /^(localhost|127\.0\.0\.1)$/);
const artifacts = process.env.POT_ARTIFACTS;
assert.ok(artifacts, 'Set POT_ARTIFACTS for screenshots and results.');
mkdirSync(artifacts, { recursive: true });
const original = wp('echo base64_encode(serialize(array(get_stylesheet(),get_option("active_plugins"),get_option("pot_settings",null),get_option("pot_checkout_settings",null))));');
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();
const errors = [];
page.on('pageerror', (error) => errors.push(error.message));
const results = [];
const product = JSON.parse(wp('$p=wc_get_products(array("type"=>"simple","status"=>"publish","limit"=>1))[0]; echo wp_json_encode(array("id"=>$p->get_id(),"url"=>get_permalink($p->get_id())));'));
async function capture(route, label) {
 await page.goto(route, { waitUntil: 'networkidle' });
 await page.evaluate(async () => { await document.fonts.ready; await Promise.all([...document.images].map((img) => img.decode().catch(() => {}))); });
 const snapshot = await page.evaluate(() => {
  const selectors = ['header.wp-block-template-part','main','footer.wp-block-template-part','.wp-block-site-title','.wp-block-post-title','.wp-block-woocommerce-product-price','.wc-block-cart__submit-button','.wc-block-components-checkout-place-order-button'];
  return Object.fromEntries(selectors.map((selector) => [selector, [...document.querySelectorAll(selector)].map((element) => {
   const css = getComputedStyle(element), box = element.getBoundingClientRect();
   return { color: css.color, background: css.backgroundColor, font: css.fontFamily, size: css.fontSize, weight: css.fontWeight, width: Math.round(box.width), height: Math.round(box.height), x: Math.round(box.x), y: Math.round(box.y) };
  })]));
 });
 await page.screenshot({ path: `${artifacts}/${label}.png`, fullPage: true, animations: 'disabled' });
 return snapshot;
}
try {
 wp('$s=pot_defaults();foreach(array("promo_enabled","instant_search","category_navigation","checkout_trust","footer_policies","post_purchase_account","wishlist","sticky_cart","mobile_sticky_checkout","sale_percentage","product_guidance","checkout_labels","free_shipping_threshold") as $key){$s[$key]=0;}update_option("pot_settings",$s); update_option("pot_checkout_settings",array());');
 await page.goto(`${site}/?add-to-cart=${product.id}`, { waitUntil: 'networkidle' });
 // Consume the one-request WooCommerce add-to-cart notice before comparing routes.
 await page.goto(`${site}/cart/`, { waitUntil: 'networkidle' });
 for (const width of [1440,390]) {
  await page.setViewportSize({ width, height: width === 390 ? 844 : 1000 });
  for (const [name, url] of [['home',site],['shop',`${site}/shop/`],['product',product.url],['cart',`${site}/cart/`],['checkout',`${site}/checkout/`]]) {
   wp('deactivate_plugins("purple-optimize-toolkit/purple-optimize-toolkit.php"); switch_theme("purple");');
   const parent = await capture(url, `parent-${name}-${width}`);
   wp('switch_theme("purple-optimize"); activate_plugin("purple-optimize-toolkit/purple-optimize-toolkit.php");');
   const child = await capture(url, `child-${name}-${width}`);
   assert.deepEqual(child, parent, `${name} at ${width}px changed parent presentation`);
   assert.equal(await page.locator('link#purple-optimize-toolkit-css,script#purple-optimize-toolkit-js').count(), 0, 'Disabled features must load no toolkit bundle');
   results.push({ route: name, width, parentPresentation: 'identical', toolkitBundle: 'absent' });
  }
 }
 wp('$s=pot_defaults(); foreach(array("promo_enabled","product_guidance","wishlist","sticky_cart","category_navigation","footer_policies","checkout_trust") as $key){$s[$key]=1;} $s["promo_text"]="Local conversion preview"; $s["free_shipping_threshold"]=75; update_option("pot_settings",$s);');
 await capture(product.url, 'enabled-product-mobile');
 assert.equal(await page.locator('.pot-promo').count(), 1);
 assert.equal(await page.locator('.pot-buy-box').count(), 1);
 assert.equal(await page.locator('.pot-wishlist-button').count(), 1);
 await page.locator('.pot-wishlist-button').click();
 assert.equal(await page.locator('.pot-wishlist-button').getAttribute('aria-pressed'), 'true');
 await page.evaluate(() => scrollTo(0, document.body.scrollHeight));
 await page.locator('.pot-sticky-cart.is-visible').waitFor();
 await capture(`${site}/cart/`, 'enabled-cart-mobile');
 assert.equal(await page.locator('.pot-shipping-progress').count(), 1);
 assert.equal(await page.locator('.wc-block-cart__submit-button').count(), 1);
 assert.deepEqual(errors, []);
 writeFileSync(`${artifacts}/results.json`, JSON.stringify({ comparisons: results, enabledFeatures: 'promo, guidance, wishlist toggle, mobile sticky cart, shipping progress, native cart action passed', pageErrors: errors }, null, 2));
 console.log('PASS: 10 parent/child computed-presentation comparisons, no disabled toolkit bundle, and enabled conversion features.');
} finally {
 wp(`$v=unserialize(base64_decode('${original}')); switch_theme($v[0]); update_option('active_plugins',$v[1]); foreach(array('pot_settings'=>2,'pot_checkout_settings'=>3) as $key=>$i){if(null===$v[$i]){delete_option($key);}else{update_option($key,$v[$i]);}}`);
 await browser.close();
}
