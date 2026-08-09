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
