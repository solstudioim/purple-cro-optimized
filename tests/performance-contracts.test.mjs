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
const themeFunctions = fs.readFileSync(
	path.join(repositoryRoot, 'wp-content/themes/purple-optimize/functions.php'),
	'utf8'
);
const importerSource = fs.readFileSync(
	path.join(repositoryRoot, 'tools/import-open-media-gallery.php'),
	'utf8'
);

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
