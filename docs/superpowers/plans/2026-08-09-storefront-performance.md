# Purple CRO Storefront Performance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove avoidable storefront background work and defer hidden product-gallery originals while preserving every approved CRO feature.

**Architecture:** Keep the existing small toolkit bundle, but make its timer and placement observers lifecycle-aware. Add product-gallery loading attributes through the Purple Optimize child theme with `WP_HTML_Tag_Processor`, and make the development media importer resize and recompress future open-media imports without rewriting existing attachments.

**Tech Stack:** WordPress 6.7+, PHP 7.4+, WooCommerce Blocks, vanilla JavaScript, Node.js built-in test runner, `WP_HTML_Tag_Processor`, `WP_Image_Editor`, Laravel Valet.

## Global Constraints

- Do not modify the Purple parent theme or WooCommerce.
- Do not dequeue WooCommerce block scripts or styles.
- Do not add runtime dependencies, caching plugins, or host/CDN configuration.
- Keep the primary product image eager with high fetch priority.
- Preserve responsive `srcset`, gallery navigation, zoom, offer expiry enforcement, and all existing CRO settings.
- Do not destructively rewrite existing media.
- Do not claim production Core Web Vitals from local Valet measurements.

---

## File Map

- `tests/performance-contracts.test.mjs`: dependency-free runtime and source-contract regression tests.
- `docs/performance/2026-08-09-before.json`: raw pre-change measurements and limitations.
- `docs/performance/2026-08-09-after.json`: matching post-change measurements.
- `docs/performance/2026-08-09-comparison.md`: explicit improved, neutral, and regressed results.
- `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js`: countdown and mutation-observer lifecycle.
- `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`: toolkit version only; existing feature behavior remains intact.
- `wp-content/themes/purple-optimize/functions.php`: scoped product-gallery loading-attribute filter.
- `wp-content/themes/purple-optimize/style.css`: child-theme version only.
- `tools/import-open-media-gallery.php`: bounded image resizing, quality, WebP support, and safe fallback.
- `README.md`: portable performance behavior and deployment caveat.
- `FEATURE-AUDIT.md`: honest performance status in the feature matrix.

### Task 1: Add performance contract tests

**Files:**
- Create: `tests/performance-contracts.test.mjs`
- Create: `docs/performance/2026-08-09-before.json`

**Interfaces:**
- Consumes: `toolkit.js`, child-theme `functions.php`, and `tools/import-open-media-gallery.php` as source files.
- Produces: `node --test tests/performance-contracts.test.mjs`, the regression command used by all later tasks.

- [ ] **Step 1: Create a dependency-free storefront harness**

Create a Node test that evaluates `toolkit.js` in `node:vm`. Supply empty
`querySelector()`/`querySelectorAll()` results, capture the `DOMContentLoaded`
callback, instrument `window.setInterval`, and count constructed
`MutationObserver` instances.

```js
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
    setInterval: () => { intervals += 1; return intervals; },
    clearInterval() {},
    setTimeout() {},
    clearTimeout() {},
  };
  class MutationObserver {
    constructor() { observers += 1; }
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
  ready();
  return { intervals, observers };
}
```

- [ ] **Step 2: Add the failing runtime assertion**

```js
test('unrelated pages start no intervals or mutation observers', () => {
  assert.deepEqual(runToolkitOnEmptyPage(), { intervals: 0, observers: 0 });
});
```

- [ ] **Step 3: Add failing PHP source contracts**

```js
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
```

- [ ] **Step 4: Run the tests and verify red state**

Run: `node --test tests/performance-contracts.test.mjs`

Expected: three failures: one interval and one observer exist on the empty
page, the gallery filter is absent, and importer optimization constants are
absent.

- [ ] **Step 5: Persist the exact before state**

Write `docs/performance/2026-08-09-before.json` with:

- runtime commit `af49ce3` (the last commit that changed storefront runtime);
- five compressed HTTP samples per home, shop, product, cart, and checkout route;
- median TTFB and compressed HTML size per route;
- browser asset counts for home, shop, and product;
- product image/source sizes observed on disk;
- the empty-page harness result showing one idle interval and one unrelated
  mutation observer before optimization;
- tooling limitations: WP-CLI could not connect to the Valet database and the
  in-app browser did not expose navigation/resource timing entries.

- [ ] **Step 6: Validate the baseline JSON**

Run: `jq empty docs/performance/2026-08-09-before.json`

Expected: exit zero.

- [ ] **Step 7: Commit the failing tests and baseline**

```bash
git add tests/performance-contracts.test.mjs docs/performance/2026-08-09-before.json
git commit -m "test: capture storefront performance baseline"
```

### Task 2: Make toolkit runtime work lifecycle-aware

**Files:**
- Modify: `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js:135-224`
- Modify: `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php:2-18`
- Test: `tests/performance-contracts.test.mjs`

**Interfaces:**
- Consumes: existing `.pot-countdown`, `.pot-inline-offer`, `.pot-checkout-trust`, and `.pot-account-invitation` markup.
- Produces: `setupCountdowns()` that creates and clears one interval only when required; `moveCheckoutEnhancements()` that observes only unresolved, present helpers.

- [ ] **Step 1: Guard and clear the product countdown interval**

Replace the unconditional countdown loop with this lifecycle:

```js
const countdowns = [...document.querySelectorAll('.pot-countdown')];
if (!countdowns.length) return;

const update = () => {
  let active = 0;
  countdowns.forEach((node) => {
    if (!node.isConnected) return;
    const remaining = Math.max(0, new Date(node.dataset.end).getTime() - Date.now());
    const output = node.querySelector('[data-countdown]');
    if (!remaining) { node.remove(); return; }
    active += 1;
    renderTimer(output, remaining);
  });
  return active > 0;
};
if (!update()) return;
const interval = window.setInterval(() => {
  if (!update()) window.clearInterval(interval);
}, 1000);
```

- [ ] **Step 2: Guard and disconnect the placement observer**

Make `moveCheckoutEnhancements()` return immediately when none of its helper
elements exist. Track unresolved destinations and observe only while at least
one present helper still needs an asynchronously rendered Woo target.

```js
function moveCheckoutEnhancements() {
  const inlineOffer = document.querySelector('.pot-inline-offer');
  const trust = document.querySelector('.pot-checkout-trust');
  const accountInvitation = document.querySelector('.pot-account-invitation');
  if (!inlineOffer && !trust && !accountInvitation) return;

  let observer;
  const place = () => {
    let pending = false;
    const actions = document.querySelector('.wc-block-checkout__actions_row');
    const cart = document.querySelector('.wp-block-woocommerce-cart, .wc-block-cart');
    if (inlineOffer) {
      if (actions && inlineOffer.nextElementSibling !== actions) actions.parentNode.insertBefore(inlineOffer, actions);
      else if (!actions) pending = true;
    }
    if (trust) {
      if (cart && trust.nextElementSibling !== cart) cart.parentNode.insertBefore(trust, cart);
      else if (!cart) pending = true;
    }
    if (accountInvitation) {
      const receipt = document.querySelector('main .woocommerce-order, main .wp-block-woocommerce-order-confirmation-status, main');
      if (receipt && accountInvitation.parentNode !== receipt) receipt.append(accountInvitation);
      else if (!receipt) pending = true;
    }
    if (!pending) observer?.disconnect();
    return pending;
  };
  if (place()) {
    observer = new MutationObserver(place);
    observer.observe(document.body, { childList: true, subtree: true });
  }
}
```

- [ ] **Step 3: Raise the toolkit version**

Change the plugin header and `POT_VERSION` from `0.5.0` to `0.6.0` so deployed
assets receive a new cache key.

- [ ] **Step 4: Run focused verification**

Run:

```bash
node --test tests/performance-contracts.test.mjs
node --check wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js
php -l wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php
```

Expected: runtime test passes; gallery and importer contract tests still fail;
syntax checks pass.

- [ ] **Step 5: Commit the runtime optimization**

```bash
git add wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php
git commit -m "perf: stop idle toolkit timers and observers"
```

### Task 3: Defer hidden product-gallery originals

**Files:**
- Modify: `wp-content/themes/purple-optimize/functions.php`
- Modify: `wp-content/themes/purple-optimize/style.css:1-14`
- Test: `tests/performance-contracts.test.mjs`

**Interfaces:**
- Consumes: rendered `woocommerce/product-gallery` block HTML.
- Produces: `purple_optimize_lazy_gallery_images( string $block_content ): string`.

- [ ] **Step 1: Add the scoped gallery filter**

```php
function purple_optimize_lazy_gallery_images( string $block_content ): string {
    if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor( $block_content );
    while ( $processor->next_tag( 'IMG' ) ) {
        $loading  = $processor->get_attribute( 'loading' );
        $priority = $processor->get_attribute( 'fetchpriority' );
        if ( 'eager' === $loading || 'high' === $priority ) {
            continue;
        }
        if ( null === $loading ) {
            $processor->set_attribute( 'loading', 'lazy' );
        }
        if ( null === $priority ) {
            $processor->set_attribute( 'fetchpriority', 'low' );
        }
    }
    return $processor->get_updated_html();
}
add_filter( 'render_block_woocommerce/product-gallery', 'purple_optimize_lazy_gallery_images' );
```

- [ ] **Step 2: Raise the child-theme version**

Change `Version: 0.2.0` to `Version: 0.3.0` in `style.css`.

- [ ] **Step 3: Run focused verification**

Run:

```bash
node --test tests/performance-contracts.test.mjs
php -l wp-content/themes/purple-optimize/functions.php
```

Expected: runtime and gallery tests pass; importer contract still fails; PHP
lint passes.

- [ ] **Step 4: Install only the theme files on Valet**

Copy `functions.php` and `style.css` to the matching
`/Users/sol/sites/purple-optimize/wp-content/themes/purple-optimize/` paths.

- [ ] **Step 5: Verify rendered gallery markup**

Open `/product/coastal-blue-sweater/` and verify:

- the visible primary image remains `loading="eager" fetchpriority="high"`;
- non-primary images without a strategy receive `loading="lazy" fetchpriority="low"`;
- the gallery, thumbnails, and full-screen interaction remain present;
- the browser console has no errors.

- [ ] **Step 6: Commit the gallery optimization**

```bash
git add wp-content/themes/purple-optimize/functions.php wp-content/themes/purple-optimize/style.css
git commit -m "perf: defer hidden product gallery images"
```

### Task 4: Optimize future open-media imports

**Files:**
- Modify: `tools/import-open-media-gallery.php`
- Test: `tests/performance-contracts.test.mjs`

**Interfaces:**
- Consumes: a validated temporary JPEG, PNG, or WebP path and MIME type.
- Produces: `pot_prepare_open_media_image( string $tmp, string $mime ): array{path:string,mime:string,extension:string}`.

- [ ] **Step 1: Add explicit limits**

```php
const POT_OPEN_MEDIA_MAX_DIMENSION = 1600;
const POT_OPEN_MEDIA_QUALITY       = 82;
```

- [ ] **Step 2: Add safe preparation and fallback**

Use `wp_get_image_editor()` to resize only when width or height exceeds 1600,
set quality to 82, and select WebP only when
`wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) )` is true.
Save to a unique temporary filename. On editor, resize, or save error, return
the original path, MIME type, and extension unchanged. Delete the original
temporary download only after a different optimized file is saved.

```php
function pot_prepare_open_media_image( string $tmp, string $mime ): array {
	$extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
	$fallback   = array( 'path' => $tmp, 'mime' => $mime, 'extension' => $extensions[ $mime ] );
	$editor     = wp_get_image_editor( $tmp );
	if ( is_wp_error( $editor ) ) {
		return $fallback;
	}
	$size = $editor->get_size();
	if ( ! is_array( $size ) || empty( $size['width'] ) || empty( $size['height'] ) ) {
		return $fallback;
	}
	if ( max( (int) $size['width'], (int) $size['height'] ) > POT_OPEN_MEDIA_MAX_DIMENSION ) {
		$resized = $editor->resize( POT_OPEN_MEDIA_MAX_DIMENSION, POT_OPEN_MEDIA_MAX_DIMENSION, false );
		if ( is_wp_error( $resized ) ) {
			return $fallback;
		}
	}
	$editor->set_quality( POT_OPEN_MEDIA_QUALITY );
	$target_mime = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? 'image/webp' : $mime;
	$target_ext  = $extensions[ $target_mime ];
	$target_name = wp_unique_filename( dirname( $tmp ), basename( $tmp ) . '-pot-optimized.' . $target_ext );
	$saved       = $editor->save( dirname( $tmp ) . '/' . $target_name, $target_mime );
	if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
		return $fallback;
	}
	if ( $saved['path'] !== $tmp && file_exists( $tmp ) ) {
		unlink( $tmp );
	}
	return array( 'path' => $saved['path'], 'mime' => $target_mime, 'extension' => $target_ext );
}

$prepared = pot_prepare_open_media_image( $tmp, $mime );
$tmp      = $prepared['path'];
$mime     = $prepared['mime'];
$filename = sanitize_file_name(
    strtolower( $sku . '-' . $record['provider'] . '-' . substr( md5( $record['source_id'] ), 0, 10 ) . '.' . $prepared['extension'] )
);
```

- [ ] **Step 3: Preserve failure cleanup**

Ensure `media_handle_sideload()` receives the prepared path and every error
branch unlinks the active temporary path. Do not modify attachments found by
the existing `_pot_media_source_id` lookup.

- [ ] **Step 4: Run focused verification**

Run:

```bash
node --test tests/performance-contracts.test.mjs
php -l tools/import-open-media-gallery.php
```

Expected: all contract tests and PHP lint pass.

- [ ] **Step 5: Commit importer safeguards**

```bash
git add tools/import-open-media-gallery.php tests/performance-contracts.test.mjs
git commit -m "perf: optimize future open media imports"
```

### Task 5: Document, install, and verify the complete release

**Files:**
- Modify: `README.md`
- Modify: `FEATURE-AUDIT.md`
- Create: `docs/performance/2026-08-09-after.json`
- Create: `docs/performance/2026-08-09-comparison.md`
- Test: all files and local Valet routes

**Interfaces:**
- Consumes: completed runtime, gallery, and importer tasks.
- Produces: documented, locally installed, regression-checked release ready for GitHub.

- [ ] **Step 1: Document the optimization boundaries**

Add a `Performance` section to `README.md` stating that the toolkit avoids idle
timers and unrelated observers, hidden gallery images are lazy/low priority,
future demo imports are bounded and compressed, and production caching/CDN and
field Core Web Vitals remain deployment responsibilities.

Update `FEATURE-AUDIT.md` to describe the performance foundation without a
guaranteed score.

- [ ] **Step 2: Run static verification**

```bash
node --test tests/performance-contracts.test.mjs
node --check wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js
php -l wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php
php -l wp-content/plugins/purple-optimize-toolkit/templates/offer-funnel.php
php -l wp-content/themes/purple-optimize/functions.php
php -l tools/import-open-media-gallery.php
jq empty wp-content/themes/purple-optimize/theme.json tools/open-media-gallery.json
git diff --check
```

Expected: every command exits zero.

- [ ] **Step 3: Install changed runtime files on Valet**

Copy the changed toolkit assets/plugin file and child-theme files to their
matching paths under `/Users/sol/sites/purple-optimize`. Do not run the media
importer because existing media must not be rewritten.

- [ ] **Step 4: Repeat HTTP baselines**

Collect five compressed-response samples for home, shop, product, cart, and
checkout.
Expected: median TTFB remains below 250 ms on content routes.

- [ ] **Step 5: Verify storefront regressions**

Check home, shop, product, cart, checkout, wishlist, instant search, social
proof, sticky controls, View cart, empty-cart behavior, and configured offer
placements. Confirm the product timer updates each second and expired offers
retain existing secure rejection behavior.

- [ ] **Step 6: Verify gallery request behavior and console health**

Confirm primary versus hidden gallery loading attributes in the rendered DOM,
inventory page assets, and inspect browser error logs. Record local evidence as
directional, not production Core Web Vitals.

- [ ] **Step 7: Persist matching after measurements**

Write `docs/performance/2026-08-09-after.json` using the same keys, routes,
five-sample method, and browser asset-count method as the before file. Include
the empty-page harness result and rendered primary/hidden gallery attributes.

- [ ] **Step 8: Write the comparison report**

Create `docs/performance/2026-08-09-comparison.md` with a table containing
metric, before, after, delta, and verdict columns. Classify each row as
improved, neutral, or regressed. Include acceptance-criteria pass/fail status
and the local-versus-production limitation.

- [ ] **Step 9: Validate measurement artifacts**

```bash
jq empty docs/performance/2026-08-09-before.json docs/performance/2026-08-09-after.json
rg -n "Improved|Neutral|Regressed|PASS|FAIL" docs/performance/2026-08-09-comparison.md
```

Expected: both JSON files validate and the report contains explicit verdicts.

- [ ] **Step 10: Verify installed-file parity**

Use `cmp -s` for every copied toolkit and child-theme file. Expected: all
workspace and Valet files match.

- [ ] **Step 11: Commit documentation and measurement evidence**

```bash
git add README.md FEATURE-AUDIT.md docs/performance/2026-08-09-after.json docs/performance/2026-08-09-comparison.md
git commit -m "docs: record storefront performance improvement"
```

- [ ] **Step 12: Push the completed release**

```bash
git push origin main
```

Expected: GitHub `main` advances to the final performance commit and local
`main` is clean and synchronized with `origin/main`.
