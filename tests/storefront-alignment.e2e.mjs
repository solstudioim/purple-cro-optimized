/** Local storefront insertion points and native container geometry. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
assert.ok(process.env.POT_WP_PATH && process.env.POT_ARTIFACTS);
const wp = code => execFileSync(process.env.POT_PHP || 'php',['-d','error_reporting=22527','/usr/local/bin/wp','eval',code,`--path=${process.env.POT_WP_PATH}`],{encoding:'utf8'}).trim();
const site=wp('echo home_url();');
assert.match(new URL(site).hostname,/^(localhost|127\.0\.0\.1)$/);
const original=wp('echo base64_encode(serialize(array(get_option("pot_settings"),get_option("pot_checkout_settings"))));');
const products=JSON.parse(wp('$p=array_values(array_filter(wc_get_products(array("type"=>"simple","status"=>"publish","limit"=>20)),function($p){return !pot_offer_product_issue_for_product($p);}));echo wp_json_encode(array_map(function($p){return array("id"=>$p->get_id(),"price"=>(float)$p->get_price());},array_slice($p,0,2)));'));
assert.equal(products.length,2);
const browser=await chromium.launch({headless:true});
const context=await browser.newContext();
const page=await context.newPage();
const errors=[];page.on('pageerror',error=>errors.push(error.message));
const output=process.env.POT_ARTIFACTS;mkdirSync(output,{recursive:true});
const results=[];
async function aligned(first,second,label){
 const a=await first.boundingBox(),b=await second.boundingBox();
 assert.ok(a&&b,label+' missing');
 assert.ok(Math.abs(a.x-b.x)<=1 && Math.abs(a.width-b.width)<=1,`${label}: ${JSON.stringify({a,b})}`);
 results.push({label,x:a.x,width:a.width,referenceX:b.x,referenceWidth:b.width});
}
try {
 wp(`$s=pot_defaults();$s['offer_funnel']=0;$s['free_shipping_threshold']=${products[0].price+products[1].price/2};update_option('pot_settings',$s);update_option('pot_checkout_settings',array('upsells_enabled'=>1,'upsells'=>array(array('product_id'=>${products[1].id},'title'=>'Local alignment add-on','description'=>''))));`);
 const cart=await context.request.get(`${site}/wp-json/wc/store/v1/cart`);
 await context.request.post(`${site}/wp-json/wc/store/v1/cart/add-item`,{headers:{Nonce:cart.headers().nonce},data:{id:products[0].id,quantity:1}});
 for(const width of [1440,390]){
  await page.setViewportSize({width,height:width===390?844:1000});
  await page.goto(`${site}/shop/`,{waitUntil:'networkidle'});
  const header=page.locator('.wp-block-template-part').filter({has:page.locator('.wp-block-woocommerce-mini-cart')}).first();
  assert.equal(await header.locator('.pot-wishlist-link').count(),1,'Wishlist belongs in native header tools.');
  assert.equal(await page.locator('.pot-promo .pot-wishlist-link').count(),0,'Notice must not contain wishlist.');
  await aligned(page.locator('.pot-category-nav'),header.locator('.alignwide').first(),`category/header ${width}`);
  assert.ok(await page.locator('.pot-category-nav').evaluate(el=>Boolean(el.closest('.pot-category-container').previousElementSibling?.querySelector('.pot-wishlist-link'))),'Categories follow the native header.');
  await page.screenshot({path:`${output}/shop-${width}.png`,fullPage:false});
  for(const route of ['cart','checkout']){
   await page.goto(`${site}/${route}/`,{waitUntil:'networkidle'});
   const native=page.locator(route==='cart'?'.wp-block-woocommerce-cart':'.wp-block-woocommerce-checkout').first();
   await native.waitFor();
   await aligned(page.locator('.pot-shipping-progress'),native,`${route}/shipping ${width}`);
   assert.equal(await page.locator('.pot-shipping-progress').evaluate(el=>el.parentElement===document.querySelector('.wp-block-woocommerce-'+location.pathname.split('/')[1])?.parentElement),true);
   assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`${route} overflow at ${width}`);
   await page.screenshot({path:`${output}/${route}-${width}.png`,fullPage:true});
  }
 }
 await page.locator('.pot-checkout-upsell button').first().click();
 await page.locator('.pot-shipping-progress').filter({hasText:'You reached the configured free-shipping threshold.'}).waitFor();
 assert.deepEqual(errors,[]);
 writeFileSync(`${output}/geometry.json`,JSON.stringify(results,null,2));
 console.log('PASS: wishlist in native header, category strip below header, native container edges at desktop/mobile, checkout shipping progress reacts to add-on totals.');
}finally{
 wp(`$s=unserialize(base64_decode('${original}'));update_option('pot_settings',$s[0]);update_option('pot_checkout_settings',$s[1]);`);
 await browser.close();
}
