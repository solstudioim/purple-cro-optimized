import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const theme = path.join(root, 'wp-content/themes/purple-optimize');
const read = (relativePath) => fs.readFileSync(path.join(theme, relativePath), 'utf8');
const toolkitJs = fs.readFileSync(path.join(root, 'wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js'), 'utf8');
const toolkitPhp = fs.readFileSync(path.join(root, 'wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php'), 'utf8');

test('README displays the current child theme release', () => {
	const stylesheet = read('style.css');
	const readme = fs.readFileSync(path.join(root, 'README.md'), 'utf8');
	const version = stylesheet.match(/^Version:\s*(\S+)/m)?.[1];
	assert.ok(version, 'Theme stylesheet must declare a version');
	assert.ok(readme.includes(`**Current child-theme release:** \`${version}\``));
});

test('README displays the current toolkit release', () => {
	const readme = fs.readFileSync(path.join(root, 'README.md'), 'utf8');
	const version = toolkitPhp.match(/^ \* Version:\s*(\S+)/m)?.[1];
	assert.ok(version, 'Toolkit plugin must declare a version');
	assert.ok(readme.includes(`**Current toolkit release:** \`${version}\``));
});

test('child theme carries WordPress.org release metadata and upstream attribution', () => {
	const stylesheet = read('style.css');
	const themeReadmePath = path.join(theme, 'readme.txt');
	assert.ok(fs.existsSync(themeReadmePath), 'WordPress.org requires a theme readme.txt');
	const themeReadme = fs.readFileSync(themeReadmePath, 'utf8');
	const projectReadme = fs.readFileSync(path.join(root, 'README.md'), 'utf8');
	const version = stylesheet.match(/^Version:\s*(\S+)/m)?.[1];
	assert.match(stylesheet, /^Theme URI:\s*https:\/\/github\.com\/solstudioim\/purple-cro-optimized$/m);
	assert.match(stylesheet, /^Requires at least:\s*7\.1$/m);
	assert.match(stylesheet, /^Tested up to:\s*7\.1$/m);
	assert.match(themeReadme, new RegExp(`^Stable tag:\\s*${version?.replaceAll('.', '\\.')}$`, 'm'));
	assert.match(themeReadme, /https:\/\/github\.com\/woocommerce\/woocommerce\/tree\/trunk\/themes\/purple/);
	assert.match(themeReadme, /Purple WordPress Theme, \(C\) 2026 Automattic/);
	assert.match(projectReadme, /\[Woo Purple parent theme\]\(https:\/\/github\.com\/woocommerce\/woocommerce\/tree\/trunk\/themes\/purple\)/);
});

test('README identifies the toolkit admin menu and access requirements', () => {
	const readme = fs.readFileSync(path.join(root, 'README.md'), 'utf8');
	assert.match(readme, /WooCommerce →\s+Purple Optimize/);
	assert.match(readme, /admin\.php\?page=purple-optimize/);
	assert.match(readme, /manage_woocommerce/);
});

test('child inherits all parent templates, parts and patterns', () => {
 for (const directory of ['templates', 'parts', 'patterns']) assert.equal(fs.existsSync(path.join(theme, directory)), false);
 assert.deepEqual(JSON.parse(read('theme.json')), { $schema: 'https://schemas.wp.org/trunk/theme.json', version: 3 });
 assert.doesNotMatch(read('functions.php'), /wp_enqueue_style|render_block/);
});

test('native notices and merchant navigation are preserved', () => {
 assert.doesNotMatch(toolkitJs, /setupSingleProductCartConfirmationCleanup|notice\.remove\(/);
 assert.doesNotMatch(toolkitPhp, /pot_resolve_placeholder_navigation|pot_render_placeholder_navigation/);
});

test('CRO defaults preserve functionality while merchant opt-outs remain possible', () => {
 const defaults = toolkitPhp.slice(toolkitPhp.indexOf('function pot_defaults'), toolkitPhp.indexOf('function pot_settings'));
 for (const key of ['promo_enabled', 'instant_search', 'category_navigation', 'checkout_trust', 'footer_policies', 'post_purchase_account', 'wishlist', 'sticky_cart', 'mobile_sticky_checkout', 'sale_percentage', 'product_guidance', 'checkout_labels']) {
  assert.match(defaults, new RegExp(`'${key}'\\s*=>\\s*1\\b`), key);
 }
 assert.match(defaults, /'free_shipping_threshold'\s*=>\s*75/);
 for (const key of ['recent_sales','social_proof_show_names','offer_funnel']) assert.match(defaults, new RegExp(`'${key}'\\s*=>\\s*0\\b`));
 assert.match(toolkitPhp, /wp_parse_args\( get_option\( 'pot_settings'/);
 assert.match(toolkitPhp, /in_array\( true, pot_frontend_feature_flags/);
});

test('mobile sticky cart enhances the native WooCommerce purchase action', () => {
 assert.match(toolkitJs, /wc-blocks_added_to_cart/);
 assert.match(toolkitJs, /prefers-reduced-motion: reduce/);
 assert.match(toolkitJs, /aria-live/);
 assert.match(toolkitPhp, /wp-element-button/);
});
