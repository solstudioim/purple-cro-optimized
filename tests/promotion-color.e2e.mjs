/** Local-only promotion background save, contrast and parent-color reset. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
assert.ok(process.env.POT_WP_PATH && process.env.POT_ARTIFACTS);
const wp = code => execFileSync(process.env.POT_PHP || 'php',['-d','error_reporting=22527','/usr/local/bin/wp','eval',code,`--path=${process.env.POT_WP_PATH}`],{encoding:'utf8'}).trim();
const site=wp('echo home_url();');assert.match(new URL(site).hostname,/^(localhost|127\.0\.0\.1)$/);
const original=wp('echo base64_encode(serialize(get_option("pot_settings")));');
const browser=await chromium.launch({headless:true});
const page=await browser.newPage();
const front=await browser.newPage();
const errors=[];page.on('pageerror',error=>errors.push(error.message));front.on('pageerror',error=>errors.push(error.message));
mkdirSync(process.env.POT_ARTIFACTS,{recursive:true});
async function save(){await Promise.all([page.waitForURL('**settings-updated=true'),page.locator('#pot-section-settings').getByRole('button',{name:'Save changes',exact:true}).click()]);}
try{
 wp('$s=pot_settings();$s["promo_enabled"]=1;$s["promo_background"]="";update_option("pot_settings",$s);');
 await page.goto(`${site}/wp-login.php`);
 await page.locator('#user_login').fill(process.env.POT_TEST_USER || 'compat');
 await page.locator('#user_pass').fill(process.env.POT_TEST_PASSWORD || 'local-development-only');
 await Promise.all([page.waitForURL(url=>!url.pathname.endsWith('wp-login.php')),page.locator('#wp-submit').click()]);
 await page.goto(`${site}/wp-admin/admin.php?page=purple-optimize&section=storefront`);
 const unchanged=JSON.parse(wp('echo wp_json_encode(pot_settings());'));delete unchanged.promo_background;
 for(const [color,background,text] of [['#f7d96b','rgb(247, 217, 107)','rgb(0, 0, 0)'],['#123456','rgb(18, 52, 86)','rgb(255, 255, 255)']]){
  await page.locator('.wp-color-result').click();
  await page.locator('#pot-promo-background').fill(color);
  await page.locator('#pot-promo-background').press('Tab');
  await save();
  assert.equal(wp('echo pot_settings()["promo_background"];'),color);
  const other=JSON.parse(wp('echo wp_json_encode(pot_settings());'));delete other.promo_background;assert.deepEqual(other,unchanged);
  await front.goto(site,{waitUntil:'networkidle'});
  assert.deepEqual(await front.locator('.pot-promo').evaluate(el=>({background:getComputedStyle(el).backgroundColor,text:getComputedStyle(el).color})),{background,text});
 }
 await page.locator('.wp-color-result').click();
 await page.locator('.wp-picker-clear').click();
 await save();
 assert.equal(wp('echo pot_settings()["promo_background"];'),'');
 await front.reload({waitUntil:'networkidle'});
 assert.equal(await front.locator('.pot-promo').evaluate(el=>el.style.getPropertyValue('--pot-promo-background')),'');
 await page.screenshot({path:`${process.env.POT_ARTIFACTS}/picker-reset.png`,fullPage:true});
 assert.deepEqual(errors,[]);
 console.log('PASS: native color picker, persisted background, automatic dark/light text, unrelated settings preserved, clear restores parent background.');
}finally{
 wp(`update_option('pot_settings',unserialize(base64_decode('${original}')));`);
 await browser.close();
}
