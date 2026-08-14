import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const themeCss = fs.readFileSync(path.join(root, 'wp-content/themes/purple-optimize/style.css'), 'utf8');
const toolkitCss = fs.readFileSync(path.join(root, 'wp-content/plugins/purple-optimize-toolkit/assets/toolkit.css'), 'utf8');

function declarations(css, selector) {
	const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	const match = css.match(new RegExp(`${escaped}\\s*\\{([^}]+)\\}`));
	assert.ok(match, `Missing CSS rule: ${selector}`);
	return match[1];
}

test('catalog presentation is stable and product-led', () => {
	const card = declarations(themeCss, '.wp-block-woocommerce-product-template > li');
	assert.doesNotMatch(card, /box-shadow|transition:\s*transform/);
	assert.doesNotMatch(declarations(themeCss, '.wp-block-woocommerce-product-template > li:hover'), /transform/);
	assert.match(declarations(themeCss, '.wp-block-woocommerce-product-image img'), /aspect-ratio:\s*4\s*\/\s*5/);
});

test('the storefront uses the approved warm editorial token system', () => {
	assert.match(themeCss, /--pot-canvas:\s*#f7f4ee/);
	assert.match(themeCss, /--pot-ink:\s*#17131f/);
	assert.match(themeCss, /--pot-action:\s*#c2410c/);
	assert.match(themeCss, /--pot-radius-lg:\s*18px/);
	assert.doesNotMatch(themeCss, /linear-gradient|radial-gradient/);
});

test('major journey surfaces have explicit visual boundaries', () => {
	for (const selector of ['.pot-home-hero', '.pot-product-decision', '.pot-product-proof', '.pot-cart-summary']) {
		assert.match(themeCss, new RegExp(`${selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*\\{`), `Missing ${selector}`);
	}
});

test('single product decision content uses one restrained centered container', () => {
	const productPage = declarations(themeCss, 'body.single-product main.pot-product-page');
	assert.match(productPage, /width:\s*min\(calc\(100% - 2rem\),\s*1120px\)\s*!important/);
	assert.match(productPage, /max-width:\s*1120px\s*!important/);
	assert.match(productPage, /margin-inline:\s*auto\s*!important/);
	const decision = declarations(themeCss, '.pot-product-page > .pot-product-decision');
	assert.match(decision, /width:\s*100%\s*!important/);
	assert.match(decision, /max-width:\s*100%\s*!important/);
	assert.match(decision, /margin-inline:\s*0\s*!important/);
	const notices = declarations(themeCss, '.pot-product-page > .wp-block-woocommerce-store-notices');
	assert.match(notices, /width:\s*100%/);
	assert.match(notices, /max-width:\s*100%/);
	assert.match(notices, /margin-inline:\s*0\s*!important/);
});

test('purchase actions use one strong color without decorative movement', () => {
	const actions = declarations(themeCss, '.single_add_to_cart_button,\n.wp-block-woocommerce-product-button .wp-block-button__link,\n.wc-block-cart__submit-button,\n.wc-block-components-checkout-place-order-button');
	assert.match(actions, /background:\s*var\(--pot-action\)/);
	assert.doesNotMatch(actions, /box-shadow|transform/);
	assert.doesNotMatch(declarations(themeCss, '.single_add_to_cart_button:hover,\n.wp-block-woocommerce-product-button .wp-block-button__link:hover,\n.wc-block-cart__submit-button:hover,\n.wc-block-components-checkout-place-order-button:hover'), /box-shadow|transform/);
	const heroSecondary = declarations(themeCss, '.home main .wp-block-buttons .wp-block-button + .wp-block-button .wp-block-button__link');
	assert.match(heroSecondary, /background:\s*transparent/);
	assert.match(heroSecondary, /border:\s*0/);
});

test('conversion components use flat surfaces and calm urgency', () => {
	for (const selector of ['.pot-promo', '.pot-buy-box', '.pot-countdown', '.pot-offer-funnel-page', '.pot-inline-offer']) {
		assert.doesNotMatch(declarations(toolkitCss, selector), /gradient|box-shadow/, `${selector} must remain flat`);
	}
	assert.doesNotMatch(declarations(toolkitCss, '.pot-timer-seconds'), /animation|box-shadow/);
	assert.doesNotMatch(declarations(toolkitCss, '.pot-stock span'), /animation/);
});

test('active checkout is enclosed and native actions remain the sticky target', () => {
	assert.match(themeCss, /body\.pot-enclosed-checkout[^{]*\.pot-promo[^{]*\{[^}]*display:\s*none/);
	assert.match(themeCss, /body\.pot-enclosed-checkout[^{]*\.pot-category-nav[^{]*\{[^}]*display:\s*none/);
	assert.match(themeCss, /body\.pot-enclosed-checkout[^{]*footer[^{]*\{[^}]*display:\s*none/);
	const sticky = declarations(toolkitCss, 'body.pot-mobile-sticky-checkout-enabled .wc-block-cart__submit-container,\n\tbody.pot-mobile-sticky-checkout-enabled .wc-block-checkout__actions_row');
	assert.match(sticky, /position:\s*fixed/);
	assert.doesNotMatch(toolkitCss, /@keyframes\s+pot-(timer-tick|urgency-pulse)/);
});

test('the promotion strip can wrap without mobile overflow', () => {
	assert.match(declarations(toolkitCss, '.pot-promo'), /flex-wrap:\s*wrap/);
});

test('narrow checkout fields can stack without horizontal overflow', () => {
	assert.match(themeCss, /\.wc-block-components-address-form__first_name[^{]*\.wc-block-components-address-form__phone\s*\{[^}]*flex:\s*1\s+0\s+100%\s*!important/);
	assert.match(themeCss, /\.pot-checkout-header \.wp-block-site-title\s*\{[^}]*font-size:\s*0\.95rem/);
	assert.match(themeCss, /\.wc-block-components-sidebar-layout\s*\{[^}]*min-width:\s*0\s*!important[^}]*max-width:\s*100%\s*!important/);
	assert.match(themeCss, /\.pot-shipping-progress\s*\{[^}]*max-width:\s*calc\(100vw - 2rem\)\s*!important/);
});

test('footer typography remains readable against the dark footer surface', () => {
	const footerText = declarations(themeCss, `.pot-site-footer,
.pot-site-footer *`);
	assert.match(footerText, /color:\s*#fff\s*!important/);
	assert.match(declarations(themeCss, '.pot-site-footer a'), /color:\s*#fff\s*!important/);
});

test('product sticky cart is mobile-only and has success animation styles', () => {
	assert.match(declarations(toolkitCss, '.pot-sticky-cart'), /display:\s*none/);
	assert.match(toolkitCss, /@media \(max-width:\s*782px\)[\s\S]*\.pot-sticky-cart\s*\{[^}]*display:\s*flex/);
	assert.match(toolkitCss, /\.pot-cart-flyer\s*\{/);
	assert.match(toolkitCss, /\.pot-sticky-cart\.is-added/);
	assert.match(toolkitCss, /@media \(prefers-reduced-motion:\s*reduce\)[\s\S]*\.pot-cart-flyer\s*\{[^}]*display:\s*none/);
});

test('sticky cart keeps the currency symbol and price on one line', () => {
	assert.match(declarations(toolkitCss, '.pot-sticky-cart > div > span > span'), /display:\s*block/);
	assert.doesNotMatch(toolkitCss, /\.pot-sticky-cart span span\s*\{/);
	const amount = declarations(toolkitCss, '.pot-sticky-cart .woocommerce-Price-amount');
	assert.match(amount, /display:\s*inline-flex/);
	assert.match(amount, /white-space:\s*nowrap/);
	assert.match(declarations(toolkitCss, '.pot-sticky-cart .woocommerce-Price-currencySymbol'), /display:\s*inline/);
});

test('desktop cart and checkout cards have a deliberate gutter', () => {
	assert.match(themeCss, /@media \(min-width:\s*782px\)[\s\S]*\.woocommerce-cart \.wc-block-components-sidebar-layout,[\s\S]*\.woocommerce-checkout \.wc-block-components-sidebar-layout\s*\{[^}]*display:\s*grid[^}]*grid-template-columns:[^}]*gap:\s*clamp\(/);
	assert.match(themeCss, /@media \(min-width:\s*782px\)[\s\S]*\.woocommerce-cart \.wc-block-cart__main,[\s\S]*\.woocommerce-checkout \.wc-block-checkout__sidebar\s*\{[^}]*width:\s*auto\s*!important/);
});

test('desktop cart and checkout keep content left and order summary right', () => {
	assert.match(themeCss, /@media \(min-width:\s*782px\)[\s\S]*\.woocommerce-cart \.wc-block-cart__main,[\s\S]*\.woocommerce-checkout \.wc-block-checkout__main\s*\{[^}]*grid-column:\s*1[^}]*grid-row:\s*1/);
	assert.match(themeCss, /@media \(min-width:\s*782px\)[\s\S]*\.woocommerce-cart \.wc-block-cart__sidebar,[\s\S]*\.woocommerce-checkout \.wc-block-checkout__sidebar\s*\{[^}]*grid-column:\s*2[^}]*grid-row:\s*1/);
});

test('commerce banners and transactional layout share one container width', () => {
	const surfaces = declarations(themeCss, `.woocommerce-cart .pot-shipping-progress,
.woocommerce-cart .pot-checkout-trust,
.woocommerce-checkout .pot-shipping-progress,
.woocommerce-checkout .pot-checkout-trust`);
	assert.match(surfaces, /width:\s*100%/);
	assert.match(surfaces, /max-width:\s*none/);
	assert.match(surfaces, /margin-inline:\s*0/);
});

test('checkout help and security labels cannot overlap', () => {
	assert.match(declarations(themeCss, 'body.pot-enclosed-checkout .pot-secure-checkout'), /margin-right:\s*8\.5rem\s*!important/);
	assert.match(themeCss, /@media \(max-width:\s*520px\)[\s\S]*\.pot-checkout-help\s*\{[^}]*display:\s*none/);
	assert.match(themeCss, /@media \(max-width:\s*520px\)[\s\S]*\.pot-secure-checkout\s*\{[^}]*margin-right:\s*0/);
});
