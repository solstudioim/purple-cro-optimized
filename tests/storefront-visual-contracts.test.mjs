import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
const read = (file) => fs.readFileSync(new URL('../' + file, import.meta.url), 'utf8');
const child = read('wp-content/themes/purple-optimize/style.css');
const css = read('wp-content/plugins/purple-optimize-toolkit/assets/toolkit.css');

test('child stylesheet contains metadata only', () => {
 assert.equal(child.replace(/\/\*[\s\S]*?\*\//g, '').trim(), '');
});

test('conversion styles inherit parent colors without global overrides', () => {
 assert.match(css, /var\(--wp--preset--color--theme-/);
 assert.doesNotMatch(css, /#[0-9a-f]{3,8}\b|--pot-(canvas|ink|action)|font-family:/i);
 const rules = css.replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/([^{}]+)\{/g);
 for (const [, selectors] of rules) {
  if (selectors.trim().startsWith('@')) continue;
  for (const selector of selectors.split(',')) assert.match(selector.trim(), /^(?:\.pot-|body\.pot-)/, selector);
 }
});

test('sticky checkout changes require the explicit opt-in body class', () => {
 assert.match(css, /body\.pot-mobile-sticky-checkout-enabled \.wc-block-cart__submit-container/);
 assert.doesNotMatch(css, /pot-enclosed-checkout/);
 assert.match(css, /@media \(prefers-reduced-motion: reduce\)/);
 assert.match(css, /white-space: nowrap/);
});

test('checkout content inherits storefront typography', () => {
 for (const name of ['checkout-extras.css', 'checkout-content.css']) {
  const source = read('wp-content/plugins/purple-optimize-toolkit/assets/' + name);
  assert.doesNotMatch(source, /font-family:|#[0-9a-f]{3,8}\b|\.editor-styles-wrapper/i);
 }
});
