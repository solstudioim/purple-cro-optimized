import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const parent = path.join(root, 'wp-content/themes/purple');
const child = path.join(root, 'wp-content/themes/purple-optimize');
const files = (directory) => fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
	const name = path.join(directory, entry.name);
	return entry.isDirectory() ? files(name) : [name];
});
const sources = files(parent).concat(files(child)).filter((file) => /\.(php|html)$/.test(file));
const patterns = new Set(sources.flatMap((file) => [...fs.readFileSync(file, 'utf8').matchAll(/\* Slug:\s*(\S+)/g)].map((match) => match[1])));

test('child pattern and template-part references resolve after the parent sync', () => {
	for (const file of files(child).filter((name) => /\.(php|html)$/.test(name))) {
		const content = fs.readFileSync(file, 'utf8');
		for (const match of content.matchAll(/<!-- wp:(pattern|template-part) (\{[^\n]*?\})\s*\/-->/g)) {
			const { slug } = JSON.parse(match[2]);
			if (match[1] === 'pattern') {
				assert.ok(patterns.has(slug), `${file}: missing pattern ${slug}`);
			} else {
				assert.ok([child, parent].some((theme) => fs.existsSync(path.join(theme, 'parts', `${slug}.html`))), `${file}: missing part ${slug}`);
			}
		}
	}
});

test('child requirements do not understate the parent requirements', () => {
	const header = (theme, key) => fs.readFileSync(path.join(theme, 'style.css'), 'utf8').match(new RegExp(`^${key}:\\s*(.+)$`, 'm'))?.[1];
	const compare = (a, b) => a.split('.').map(Number).reduce((result, value, index) => result || Math.sign(value - Number(b.split('.')[index] || 0)), 0);
	for (const key of ['Requires at least', 'Requires PHP', 'WC requires at least']) {
		assert.ok(header(child, key), `Child is missing ${key}`);
		assert.ok(compare(header(child, key), header(parent, key)) >= 0, `${key} is lower than the parent requirement`);
	}
});

test('child mobile navigation resolves to a registered parent overlay', () => {
	const header = fs.readFileSync(path.join(child, 'parts/header.html'), 'utf8');
	const navigation = JSON.parse(header.match(/<!-- wp:navigation (\{.*?\}) \/-->/)[1]);
	const parts = JSON.parse(fs.readFileSync(path.join(parent, 'theme.json'), 'utf8')).templateParts;
	assert.ok(parts.some((part) => part.name === navigation.overlay && part.area === 'navigation-overlay'));
	assert.ok(fs.existsSync(path.join(parent, 'parts', `${navigation.overlay}.html`)));
	assert.ok(navigation.metadata.ignoredHookedBlocks.includes('woocommerce/customer-account'));
});
