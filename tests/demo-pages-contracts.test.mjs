import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const page = fs.readFileSync(path.join(root, 'docs/demo/index.html'), 'utf8');
const css = fs.readFileSync(path.join(root, 'docs/demo/styles.css'), 'utf8');

test('GitHub Pages workflow deploys the demo directory with required permissions', () => {
  const workflow = fs.readFileSync(path.join(root, '.github/workflows/pages.yml'), 'utf8');

  for (const requiredValue of [
    'actions/configure-pages',
    'actions/upload-pages-artifact',
    'actions/deploy-pages',
    'pages: write',
    'id-token: write',
    'path: docs/demo',
  ]) {
    assert.match(workflow, new RegExp(requiredValue));
  }
});

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

test('demo media build inputs and Pages assets exist', () => {
  const buildScript = path.join(root, 'tools/demo/build-video.sh');
  const reproducibilityCheck = path.join(root, 'tools/demo/verify-video-reproducibility.sh');
  const captions = path.join(root, 'tools/demo/captions.txt');

  assert.ok(fs.existsSync(buildScript), 'expected reproducible video build script');
  assert.ok(fs.existsSync(reproducibilityCheck), 'expected repeat-build video verifier');
  assert.ok(fs.existsSync(captions), 'expected storyboard captions');

  const buildText = fs.readFileSync(buildScript, 'utf8');
  for (const requiredSetting of [
    '-threads 1',
    '-filter_threads 1',
    '-filter_complex_threads 1',
    '-fflags +bitexact',
    '-map_metadata -1',
  ]) {
    assert.match(buildText, new RegExp(requiredSetting.replace(/[+]/g, '\\+')));
  }

  const reproducibilityText = fs.readFileSync(reproducibilityCheck, 'utf8');
  assert.match(reproducibilityText, /build-video\.sh/);
  assert.match(reproducibilityText, /shasum -a 256/);
  assert.match(reproducibilityText, /hashes differ/);

  const captionText = fs.readFileSync(captions, 'utf8');
  for (const requiredCaption of [
    'Pre-checkout upsell',
    'Downsell',
    'Native WooCommerce checkout',
    'Post-purchase offer',
  ]) {
    assert.match(captionText, new RegExp(requiredCaption));
  }

  assert.ok(
    fs.existsSync(path.join(root, 'docs/demo/assets/purple-cro-demo.mp4')),
    'expected Pages MP4 asset',
  );
  assert.ok(
    fs.existsSync(path.join(root, 'docs/demo/assets/purple-cro-demo-poster.webp')),
    'expected Pages poster asset',
  );
});

test('storyboard text alternative mirrors the 18-frame demo sequence', () => {
  const items = [...page.matchAll(/<li>(.*?)<\/li>/gs)].map((match) => match[1]);
  assert.equal(items.length, 18, 'expected one text-alternative item per video frame');

  const requiredSequence = [
    'Purple CRO Optimized',
    'discovery, trust, and product hierarchy',
    'catalog navigation',
    'focused purchase surface',
    'Mobile sticky cart',
    'accessible cart confirmation',
    'Cart summary',
    'Pre-checkout upsell',
    'Downsell appears only after the upsell is rejected',
    'Downsell accepted',
    'Native WooCommerce checkout',
    'Test payment',
    'Completed order confirmed',
    'Post-purchase offer',
    'separate checkout',
    'Toolkit validation',
    'Warnings flag invalid products',
    'Built on Woo Purple',
  ];

  for (const [index, expected] of requiredSequence.entries()) {
    assert.match(items[index], new RegExp(expected));
  }

  for (const unsupportedClaim of ['instant search', 'variations', 'wishlist']) {
    assert.doesNotMatch(page, new RegExp(unsupportedClaim, 'i'));
  }
});
