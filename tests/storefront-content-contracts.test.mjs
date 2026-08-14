import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const theme = path.join(root, 'wp-content/themes/purple-optimize');
const read = (relativePath) => fs.readFileSync(path.join(theme, relativePath), 'utf8');
const toolkitJs = fs.readFileSync(path.join(root, 'wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js'), 'utf8');

test('the child theme owns every primary commerce template', () => {
	for (const template of ['front-page.html', 'archive-product.html', 'single-product.html', 'page-cart.html', 'page-checkout.html']) {
		assert.ok(fs.existsSync(path.join(theme, 'templates', template)), `Missing ${template}`);
	}
});

test('homepage follows the approved conversion sequence', () => {
	const homepage = read('patterns/storefront-home.php');
	const sequence = ['pot-home-hero', 'pot-trust-strip', 'pot-category-discovery', 'pot-featured-products', 'pot-brand-story', 'pot-product-proof', 'pot-home-faq'];
	let position = -1;
	for (const className of sequence) {
		const next = homepage.indexOf(`class=\"wp-block-group alignwide ${className}`) >= 0
			? homepage.indexOf(`class=\"wp-block-group alignwide ${className}`)
			: homepage.indexOf(`class=\"wp-block-group alignfull ${className}`);
		assert.ok(next > position, `${className} must appear in the approved order`);
		position = next;
	}
});

test('product story uses one native purchase surface and contextual anchors', () => {
	const singleProduct = read('templates/single-product.html');
	const productStory = read('patterns/product-story.php');
	assert.match(singleProduct, /pot-product-decision/);
	assert.match(singleProduct, /purple-optimize\/product-story/);
	assert.equal((singleProduct.match(/woocommerce\/add-to-cart-with-options/g) || []).length, 1);
	assert.match(productStory, /pot-return-to-buy-box/);
	assert.doesNotMatch(productStory, /currently viewing|people viewing|best seller/i);
});

test('comparison content is neutral and maintainable', () => {
	const comparison = read('patterns/product-comparison.php');
	assert.match(comparison, /Last reviewed/);
	assert.match(comparison, /This product/);
	assert.match(comparison, /Alternative/);
	assert.doesNotMatch(comparison, /Purovitalis|Human Tonik|AG1/i);
});

test('cart and checkout keep one native transactional content surface', () => {
	const cart = read('templates/page-cart.html');
	const checkout = read('templates/page-checkout.html');
	assert.match(cart, /pot-cart-layout/);
	assert.match(checkout, /pot-checkout-layout/);
	assert.equal((cart.match(/wp:post-content/g) || []).length, 1);
	assert.equal((checkout.match(/wp:post-content/g) || []).length, 1);
	assert.doesNotMatch(checkout, /template-part \{\"slug\":\"footer\"/);
});

test('catalog navigation follows the parent Purple wide container', () => {
	const catalog = read('templates/archive-product.html');
	assert.match(catalog, /wp:group \{"align":"wide","className":"pot-catalog-intro","layout":\{"type":"constrained"\}\}/);
	const intro = catalog.match(/pot-catalog-intro[\s\S]*?<!-- \/wp:group -->/u)?.[0] || '';
	assert.match(intro, /wp:woocommerce\/breadcrumbs/);
	assert.match(intro, /pot-catalog-heading/);
});

test('return-to-buy-box links enhance native anchors without submitting a duplicate form', () => {
	assert.match(toolkitJs, /function setupReturnToBuyBox\(\)/);
	assert.match(toolkitJs, /querySelectorAll\('\.pot-return-to-buy-box'\)/);
	assert.match(toolkitJs, /querySelector\('#pot-product-buy-box'\)/);
	assert.doesNotMatch(toolkitJs, /pot-return-to-buy-box[\s\S]{0,400}\.click\(\)/);
	assert.match(toolkitJs, /setupReturnToBuyBox\(\);/);
});

test('mobile sticky cart announces and animates successful additions', () => {
	assert.match(toolkitJs, /matchMedia\('\(max-width: 782px\)'\)/);
	assert.match(toolkitJs, /wc-blocks_added_to_cart/);
	assert.match(toolkitJs, /added_to_cart/);
	assert.match(toolkitJs, /pot-cart-flyer/);
	assert.match(toolkitJs, /aria-live/);
	assert.match(toolkitJs, /is-added/);
	assert.match(toolkitJs, /prefers-reduced-motion: reduce/);
});

test('footer navigation groups contain distinct theme-owned links', () => {
	const footer = read('parts/footer.html');
	const links = [
		['Shop all', '/shop/'],
		['Knitwear', '/product-category/knitwear/'],
		['Accessories', '/product-category/accessories/'],
		['Wishlist', '/wishlist/'],
		['FAQs', '/faqs/'],
		['Shipping & Returns', '/shipping-returns/'],
		['Contact Us', '/contact/'],
		['My Account', '/my-account/'],
		['About Us', '/about/'],
		['Privacy Policy', '/privacy-policy/'],
		['Terms & Conditions', '/terms-conditions/'],
	];
	assert.equal((footer.match(/<!-- wp:navigation \{/g) || []).length, 3);
	assert.doesNotMatch(footer, /<!-- wp:navigation \{[^\n]+\/-->/);
	for (const [label, url] of links) {
		assert.equal((footer.match(new RegExp(`"label":"${label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}"`, 'g')) || []).length, 1, `${label} must appear once`);
		assert.match(footer, new RegExp(`"url":"${url.replaceAll('/', '\\/')}"`));
	}
});
