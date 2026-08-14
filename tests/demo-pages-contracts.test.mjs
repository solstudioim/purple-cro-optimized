import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const page = fs.readFileSync(path.join(root, 'docs/demo/index.html'), 'utf8');
const css = fs.readFileSync(path.join(root, 'docs/demo/styles.css'), 'utf8');

test('demo page exposes an accessible non-autoplay video', () => {
  assert.match(page, /<video[^>]+controls/);
  assert.match(page, /poster="assets\/purple-cro-demo-poster\.webp"/);
  assert.match(page, /<source src="assets\/purple-cro-demo\.mp4" type="video\/mp4">/);
  assert.doesNotMatch(page, /\sautoplay(?:\s|=|>)/);
});

test('demo page links the project and canonical Woo Purple source', () => {
  assert.match(page, /https:\/\/github\.com\/solstudioim\/purple-cro-optimized/);
  assert.match(page, /https:\/\/github\.com\/woocommerce\/woo-themes\/tree\/trunk\/purple/);
  assert.doesNotMatch(page, /WordPress\.com repository/i);
});

test('demo page has a narrow responsive layout', () => {
  assert.match(css, /max-width:\s*1200px/);
  assert.match(css, /@media\s*\(max-width:\s*720px\)/);
  assert.match(css, /video\s*\{[^}]*width:\s*100%/s);
});
