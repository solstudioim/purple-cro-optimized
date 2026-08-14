import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const repositoryRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const toolkitSource = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js'),
	'utf8'
);
const toolkitPhp = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php'),
	'utf8'
);
const themeFunctions = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/themes/purple-optimize/functions.php'),
	'utf8'
);
const themeJson = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/themes/purple-optimize/theme.json'),
	'utf8'
);
const themeCss = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/themes/purple-optimize/style.css'),
	'utf8'
);
const homepagePattern = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/themes/purple-optimize/patterns/storefront-home.php'),
	'utf8'
);
const importerSource = fs.readFileSync(
	path.join(repositoryRoot, 'tools/import-open-media-gallery.php'),
	'utf8'
);
const measurementPath = path.join(repositoryRoot, 'tools/measure-storefront-performance.mjs');

function runToolkitOnEmptyPage() {
	let ready;
	let intervals = 0;
	let observers = 0;
	const body = {
		addEventListener() {},
		classList: { contains: () => false },
	};
	const document = {
		body,
		addEventListener: (event, callback) => {
			if (event === 'DOMContentLoaded') ready = callback;
		},
		querySelector: () => null,
		querySelectorAll: () => [],
	};
	const window = {
		purpleOptimize: {},
		document,
		localStorage: { getItem: () => null, setItem() {} },
		setInterval: () => {
			intervals += 1;
			return intervals;
		},
		clearInterval() {},
		setTimeout() {},
		clearTimeout() {},
	};
	class MutationObserver {
		constructor() {
			observers += 1;
		}
		observe() {}
		disconnect() {}
	}

	vm.runInNewContext(toolkitSource, {
		window,
		document,
		MutationObserver,
		IntersectionObserver: class {},
		navigator: {},
		URL,
		AbortController,
	});
	assert.equal(typeof ready, 'function', 'toolkit registers its DOM-ready initializer');
	ready();

	return { intervals, observers };
}

test('unrelated pages start no intervals or mutation observers', () => {
	assert.deepEqual(runToolkitOnEmptyPage(), { intervals: 0, observers: 0 });
});

test('shipping progress follows live WooCommerce cart totals without polling or requests', () => {
	let ready;
	let subscriber;
	let totals = {
		total_items: '7500',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_prefix: '$',
		currency_suffix: '',
	};
	const message = { textContent: '' };
	const fill = { style: {} };
	const barAttributes = {};
	const bar = {
		querySelector: () => fill,
		setAttribute: (name, value) => { barAttributes[name] = value; },
	};
	const progress = {
		dataset: { target: '75' },
		hidden: false,
		querySelector: (selector) => selector === 'p' ? message : bar,
	};
	const cartRoot = { parentNode: { insertBefore() {} } };
	const document = {
		body: { addEventListener() {}, classList: { contains: () => false } },
		addEventListener: (event, callback) => { if (event === 'DOMContentLoaded') ready = callback; },
		querySelector: (selector) => {
			if (selector === '#pot-shipping-progress') return progress;
			if (selector.includes('.wp-block-woocommerce-cart')) return cartRoot;
			return null;
		},
		querySelectorAll: () => [],
	};
	const window = {
		purpleOptimize: {
			features: { cart: true, commerce: true },
			freeShippingReached: 'You reached the configured free-shipping threshold.',
			freeShippingRemaining: 'Add %s more to reach the configured free-shipping threshold.',
		},
		document,
		wp: { data: {
			select: () => ({ getCartTotals: () => totals }),
			subscribe: (callback) => { subscriber = callback; return () => {}; },
		} },
		localStorage: { getItem: () => null, setItem() {} },
		requestAnimationFrame: (callback) => callback(),
		setInterval: () => { throw new Error('shipping progress must not poll'); },
		clearInterval() {}, setTimeout() {}, clearTimeout() {},
	};
	class MutationObserver { observe() {} disconnect() {} }

	vm.runInNewContext(toolkitSource, {
		window, document, MutationObserver, IntersectionObserver: class {}, navigator: {}, URL, AbortController, Intl,
	});
	ready();
	assert.equal(message.textContent, 'You reached the configured free-shipping threshold.');

	totals = { ...totals, total_items: '5000' };
	subscriber();
	assert.equal(message.textContent, 'Add $25.00 more to reach the configured free-shipping threshold.');
	assert.equal(barAttributes['aria-valuenow'], '67');
	assert.equal(fill.style.width, '66.66666666666666%');
	assert.doesNotMatch(toolkitSource, /function setupShippingProgress[\s\S]*?fetch\(/);
});

test('child theme lazily marks non-primary Woo gallery images', () => {
	assert.match(themeFunctions, /render_block_woocommerce\/product-gallery/);
	assert.match(themeFunctions, /WP_HTML_Tag_Processor/);
	assert.match(themeFunctions, /set_attribute\(\s*'loading',\s*'lazy'/);
	assert.match(themeFunctions, /set_attribute\(\s*'fetchpriority',\s*'low'/);
});

test('media importer bounds and optimizes future originals', () => {
	assert.match(importerSource, /POT_OPEN_MEDIA_MAX_DIMENSION/);
	assert.match(importerSource, /POT_OPEN_MEDIA_QUALITY/);
	assert.match(importerSource, /wp_get_image_editor/);
	assert.match(importerSource, /wp_image_editor_supports/);
});

test('storefront performance measurement is reproducible across primary routes', () => {
	assert.ok(fs.existsSync(measurementPath), 'Missing storefront performance measurement script');
	const source = fs.readFileSync(measurementPath, 'utf8');
	for (const route of ['/', '/shop/', '/product/petal-crew-sweater/', '/cart/', '/checkout/']) {
		assert.match(source, new RegExp(`['"]${route.replaceAll('/', '\\/')}['"]`));
	}
	assert.match(source, /POT_CDP_URL/);
	assert.match(source, /POT_SITE_URL/);
	assert.match(source, /width:\s*390/);
	assert.match(source, /height:\s*844/);
	assert.match(source, /transferBytes/);
	assert.match(source, /resourceCount/);
});

test('first render uses system fonts and prioritizes only the homepage hero', () => {
	assert.match(themeJson, /"slug":\s*"system"/);
	assert.match(themeJson, /-apple-system, BlinkMacSystemFont/);
	assert.match(themeJson, /"fontFamily":\s*"var\(--wp--preset--font-family--system\)"/);
	assert.doesNotMatch(themeCss, /Jost/);
	assert.match(homepagePattern, /"loading":"eager"/);
	assert.match(homepagePattern, /"fetchPriority":"high"/);
	assert.equal((homepagePattern.match(/"fetchPriority":"high"/g) || []).length, 1);
});

test('toolkit exposes explicit route-aware feature flags without dequeuing Woo assets', () => {
	assert.match(toolkitPhp, /function pot_frontend_feature_flags\(array \$settings\): array/);
	for (const feature of ['promo', 'search', 'wishlist', 'product', 'cart', 'checkout', 'commerce', 'offer', 'recentPurchases']) {
		assert.match(toolkitPhp, new RegExp(`'${feature}'\\s*=>`));
	}
	assert.match(toolkitPhp, /'features'\s*=>\s*pot_frontend_feature_flags\(\s*\$settings\s*\)/);
	assert.doesNotMatch(toolkitPhp, /wp_dequeue_(?:script|style)/);
});

test('toolkit initializers are gated by localized route features', () => {
	assert.match(toolkitSource, /const features = config\.features \|\| \{\};/);
	assert.match(toolkitSource, /if \(features\.search\) setupSearch\(\);/);
	assert.match(toolkitSource, /if \(features\.product\) \{/);
	assert.match(toolkitSource, /if \(features\.cart\) setupShippingProgress\(\);/);
	assert.match(toolkitSource, /if \(features\.checkout\) labelCheckoutFields\(\);/);
	assert.match(toolkitSource, /if \(features\.recentPurchases\) setupRecentPurchases\(\);/);
});

test('toolkit script is deferred without altering WooCommerce dependencies', () => {
	assert.match(toolkitPhp, /wp_enqueue_script\([\s\S]*?'in_footer'\s*=>\s*true[\s\S]*?'strategy'\s*=>\s*'defer'/);
	assert.doesNotMatch(toolkitPhp, /wp_(?:dequeue|deregister)_(?:script|style)/);
});
