# Purovitalis and Human Tonik Storefront Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the existing child-theme presentation with a cohesive Purovitalis-inspired storefront and selectively add Human Tonik's evidence-backed persuasion patterns across home, catalog, product, cart, and checkout.

**Architecture:** The `purple-optimize` child theme owns design tokens, block templates, template parts, and editorial patterns. The `purple-optimize-toolkit` plugin continues to own functional CRO behavior and gains only small pure helpers and presentation hooks for option economics, proof, policy consistency, and buy-box anchors. Existing WooCommerce blocks remain the transaction engine; no cart or checkout actions are duplicated.

**Tech Stack:** WordPress block theme templates, WooCommerce Blocks, PHP 7.4+, CSS, vanilla JavaScript, Node test runner, PHP contract scripts, WP-CLI, Laravel Valet.

## Global Constraints

- Preserve Purple identity, real store content, WooCommerce behavior, accessibility, existing performance work, and toolkit capabilities.
- Purovitalis and Human Tonik are structural references only; do not copy branding, content, imagery, claims, or distinctive assets.
- Orange is exclusive to purchase actions; purple remains the identity and navigation color.
- No fabricated reviews, claims, purchases, scarcity, guarantees, shipping promises, popularity labels, viewer counters, press logos, or expert endorsements.
- No new CSS framework, JavaScript framework, icon library, remote font, animation library, or third-party runtime.
- Never render duplicate Add to cart, Proceed to checkout, or Place Order controls.
- Maintain 44px touch targets, keyboard focus, semantic headings, reduced-motion support, and WCAG AA contrast.
- Preserve WordPress 6.7+, WooCommerce, and PHP 7.4+ compatibility documented by the project.
- Install and verify every approved iteration on `purple-optimize.test`.
- Do not commit, stage, push, package, deploy, or publish. The user will review the local site before authorizing a commit.

---

## File Structure

- `wp-content/themes/purple-optimize/theme.json`: child-theme global settings and block-level typography/control defaults.
- `wp-content/themes/purple-optimize/style.css`: the complete visual system and scoped page layouts; superseded rules are replaced, not layered indefinitely.
- `wp-content/themes/purple-optimize/templates/front-page.html`: homepage conversion sequence.
- `wp-content/themes/purple-optimize/templates/archive-product.html`: shop/category hierarchy.
- `wp-content/themes/purple-optimize/templates/single-product.html`: product decision and supporting-content sequence.
- `wp-content/themes/purple-optimize/templates/page-cart.html`: cart composition.
- `wp-content/themes/purple-optimize/templates/page-checkout.html`: enclosed checkout composition.
- `wp-content/themes/purple-optimize/parts/header.html`: clean storefront header.
- `wp-content/themes/purple-optimize/parts/checkout-header.html`: linked identity and help affordance.
- `wp-content/themes/purple-optimize/parts/footer.html`: structured shop/help/policy footer.
- `wp-content/themes/purple-optimize/patterns/storefront-home.php`: editable homepage section composition using existing media/content.
- `wp-content/themes/purple-optimize/patterns/product-story.php`: optional product persuasion and objection-handling sections.
- `wp-content/themes/purple-optimize/patterns/product-comparison.php`: optional evidence-maintained comparison table.
- `wp-content/plugins/purple-optimize-toolkit/includes/storefront-presentation.php`: pure calculations and markup policies with standalone contract coverage.
- `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`: WooCommerce adapters for proof, buy-box anchors, and policy-backed reassurance.
- `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.css`: visual alignment for functional CRO components.
- `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js`: focus-safe return-to-buy-box behavior only; native purchase behavior stays intact.
- `tests/storefront-visual-contracts.test.mjs`: CSS and template structure contracts.
- `tests/storefront-presentation-contracts.php`: pure PHP policy contracts.
- `tests/storefront-content-contracts.test.mjs`: template/pattern integrity and anti-fabrication contracts.

---

### Task 1: Lock the New Visual and Content Contracts

**Files:**
- Modify: `tests/storefront-visual-contracts.test.mjs`
- Modify: `tests/storefront-presentation-contracts.php`
- Create: `tests/storefront-content-contracts.test.mjs`

**Interfaces:**
- Consumes: current CSS, PHP helpers, child templates, and pattern source files.
- Produces: executable contracts for design tokens, one-action hierarchy, page composition, native-state option economics, proof integrity, and policy disclosure.

- [ ] **Step 1: Add failing visual contracts**

Add assertions that require the new tokens and page-specific surfaces:

```js
assert.match(themeCss, /--pot-canvas:\s*#f7f4ee/);
assert.match(themeCss, /--pot-ink:\s*#17131f/);
assert.match(themeCss, /--pot-action:\s*#c2410c/);
assert.match(themeCss, /\.pot-home-hero\s*\{/);
assert.match(themeCss, /\.pot-product-decision\s*\{/);
assert.match(themeCss, /\.pot-product-proof\s*\{/);
assert.match(themeCss, /\.pot-cart-summary\s*\{/);
assert.match(themeCss, /body\.pot-enclosed-checkout/);
assert.doesNotMatch(themeCss, /linear-gradient|radial-gradient/);
```

- [ ] **Step 2: Add failing content contracts**

Create a Node test that loads all child templates and patterns and verifies the required page sequence and integrity rules:

```js
test('product story uses one native purchase surface and contextual anchors', () => {
	assert.match(singleProduct, /pot-product-decision/);
	assert.match(singleProduct, /purple-optimize\/product-story/);
	assert.equal((singleProduct.match(/woocommerce\/add-to-cart-with-options/g) || []).length, 1);
	assert.doesNotMatch(productStory, /currently viewing|people viewing|best seller/i);
});
```

Also assert that homepage, archive, cart, checkout, comparison, and footer files exist and expose their agreed classes.

- [ ] **Step 3: Add failing PHP contracts**

Define expected pure interfaces before implementation:

```php
pot_test_expect(
	array( 'regular' => 120.0, 'current' => 90.0, 'saved' => 30.0, 'percentage' => 25 )
	=== pot_presentation_price_economics( 120.0, 90.0 ),
	'Option economics must be derived from current prices.'
);
pot_test_expect(
	'' === pot_presentation_popularity_label( false, 'Popular' ),
	'Popularity labels require explicit merchant configuration.'
);
pot_test_expect(
	pot_presentation_policy_summary_is_safe( '30-day returns; return shipping applies.', array( 'return shipping' ) ),
	'Material limitations must remain visible in policy summaries.'
);
```

- [ ] **Step 4: Run contracts and confirm failure**

Run:

```bash
node --test tests/storefront-visual-contracts.test.mjs tests/storefront-content-contracts.test.mjs
php tests/storefront-presentation-contracts.php
```

Expected: failures for missing tokens, templates, patterns, and pure helper functions.

---

### Task 2: Replace Global Design Tokens, Header, and Footer

**Files:**
- Modify: `wp-content/themes/purple-optimize/theme.json`
- Modify: `wp-content/themes/purple-optimize/style.css`
- Create: `wp-content/themes/purple-optimize/parts/header.html`
- Create: `wp-content/themes/purple-optimize/parts/checkout-header.html`
- Create: `wp-content/themes/purple-optimize/parts/footer.html`

**Interfaces:**
- Consumes: Purple parent presets, toolkit `pot-*` classes, native Navigation/Search/Customer Account/Mini Cart blocks.
- Produces: stable tokens and child-owned header/footer template parts used by every page template.

- [ ] **Step 1: Define child-theme global styles**

Set theme-level typography, spacing, button, input, and link defaults without remote assets. Preserve `appearanceTools` and add content/wide widths of `720px` and `1200px`.

- [ ] **Step 2: Replace the CSS token layer**

Use this token contract at the start of `style.css`:

```css
:root {
	--pot-brand: #5b21b6;
	--pot-brand-dark: #2f145f;
	--pot-action: #c2410c;
	--pot-action-hover: #9a3412;
	--pot-canvas: #f7f4ee;
	--pot-surface: #ffffff;
	--pot-surface-soft: #efe9df;
	--pot-ink: #17131f;
	--pot-muted: #696270;
	--pot-border: #ded6ca;
	--pot-positive: #166534;
	--pot-radius-sm: 6px;
	--pot-radius: 10px;
	--pot-radius-lg: 18px;
	--pot-shadow-float: 0 16px 40px rgb(23 19 31 / 12%);
}
```

- [ ] **Step 3: Build the storefront header**

Compose Site Logo/Title, Navigation, Product Search, Customer Account, and Mini Cart in one responsive header. Keep the toolkit promotion/category rows compatible and give search a real label.

- [ ] **Step 4: Build the checkout header**

Render only linked Site Logo/Title and a short secure-checkout label. The existing toolkit help link remains available through `wp_body_open`.

- [ ] **Step 5: Build the footer**

Use Shop, Help, and About navigation columns plus a concise brand statement and copyright. Keep toolkit policy links as the canonical policy surface.

- [ ] **Step 6: Run visual/content contracts**

Run the Node contracts. Expected: global token and template-part assertions pass; page-template assertions remain failing.

---

### Task 3: Build the Homepage and Catalog Journey

**Files:**
- Create: `wp-content/themes/purple-optimize/templates/front-page.html`
- Create: `wp-content/themes/purple-optimize/templates/archive-product.html`
- Create: `wp-content/themes/purple-optimize/patterns/storefront-home.php`
- Modify: `wp-content/themes/purple-optimize/style.css`

**Interfaces:**
- Consumes: child header/footer, Purple parent product collections, seeded catalog, toolkit category navigation and real sale/review output.
- Produces: homepage sequence and catalog grid consumed by the local store.

- [ ] **Step 1: Implement the editable homepage pattern**

Register `purple-optimize/storefront-home` with this semantic order and classes:

```html
<section class="pot-home-hero">...</section>
<section class="pot-trust-strip">...</section>
<section class="pot-category-discovery">...</section>
<section class="pot-featured-products">...</section>
<section class="pot-brand-story">...</section>
<section class="pot-product-proof">...</section>
<section class="pot-home-faq">...</section>
```

Use existing Purple media and WooCommerce product-collection blocks. Do not insert invented review text or merchant claims; use neutral editable headings where data-backed blocks supply the content.

- [ ] **Step 2: Implement the homepage template**

Render header, a constrained `main`, the new pattern, and footer. Add `pot-home` to the main wrapper.

- [ ] **Step 3: Implement the archive template**

Render notices, breadcrumbs, query title, term description, product filters, sorting, and the parent product grid inside `pot-catalog` and `pot-catalog-grid` wrappers.

- [ ] **Step 4: Add responsive homepage and catalog CSS**

Create a two-column editorial hero, compact trust strip, three-column category cards, borderless product cards, and mobile-first one/two-column fallbacks. Preserve a `4 / 5` catalog-media ratio and stable hover states without transforms.

- [ ] **Step 5: Run contracts**

Expected: homepage and catalog content/visual contracts pass.

---

### Task 4: Build the Product Decision and Persuasion System

**Files:**
- Create: `wp-content/themes/purple-optimize/templates/single-product.html`
- Create: `wp-content/themes/purple-optimize/patterns/product-story.php`
- Create: `wp-content/themes/purple-optimize/patterns/product-comparison.php`
- Modify: `wp-content/plugins/purple-optimize-toolkit/includes/storefront-presentation.php`
- Modify: `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`
- Modify: `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js`
- Modify: `wp-content/themes/purple-optimize/style.css`

**Interfaces:**
- Consumes: native Woo product gallery, summary, rating, price, add-to-cart-with-options, details, related products, real Woo review records and prices.
- Produces: one native buy box, option economics helpers, contextual proof slot, long-form content pattern, and focus-safe return anchors.

- [ ] **Step 1: Implement pure price and label policies**

Add:

```php
function pot_presentation_price_economics( float $regular, float $current ): array {
	if ( $regular <= 0 || $current <= 0 || $current > $regular ) {
		return array( 'regular' => max( 0.0, $regular ), 'current' => max( 0.0, $current ), 'saved' => 0.0, 'percentage' => 0 );
	}
	$saved = $regular - $current;
	return array( 'regular' => $regular, 'current' => $current, 'saved' => $saved, 'percentage' => (int) round( ( $saved / $regular ) * 100 ) );
}

function pot_presentation_popularity_label( bool $configured, string $label ): string {
	return $configured ? trim( $label ) : '';
}
```

- [ ] **Step 2: Implement the single-product template**

Use one `woocommerce/add-to-cart-with-options` block inside `.pot-product-decision`. Keep gallery and summary side by side, then render `purple-optimize/product-story`, native product details/reviews, and related products.

- [ ] **Step 3: Implement the product-story pattern**

Provide editor-ready sections for benefits, audience fit, making/care, differentiation, specifications/provenance, shipping/returns, and FAQ. Every optional block uses neutral prompts in the editor and should be removable without breaking the layout.

- [ ] **Step 4: Implement the comparison pattern**

Provide a responsive table with neutral `Feature`, `This product`, and `Alternative` headings plus a visible `Last reviewed` paragraph. Do not name competitors or pre-populate claims.

- [ ] **Step 5: Render one contextual product proof slot**

Reuse the toolkit's existing approved-review lookup. Add a `.pot-contextual-proof` wrapper only when a real approved review exists; otherwise render nothing.

- [ ] **Step 6: Add return-to-buy-box anchors**

Render links with `href="#pot-product-buy-box"` and `.pot-return-to-buy-box`. In JavaScript, enhance clicks only to call `focus({ preventScroll: true })` on the buy-box heading after native anchor navigation; use smooth scrolling only when reduced motion is not requested.

- [ ] **Step 7: Style the decision and persuasion system**

Use a large gallery, compact white buy box, full-width native orange purchase action, accessible option cards around native radios/selectors, restrained proof, alternating editorial bands, responsive tables, and non-sticky mobile buy box.

- [ ] **Step 8: Run PHP and Node contracts**

Expected: option economics, anti-fabrication, one-native-form, anchor, pattern, and product visual contracts pass.

---

### Task 5: Rebuild Cart and Enclosed Checkout Presentation

**Files:**
- Create: `wp-content/themes/purple-optimize/templates/page-cart.html`
- Create: `wp-content/themes/purple-optimize/templates/page-checkout.html`
- Modify: `wp-content/plugins/purple-optimize-toolkit/includes/storefront-presentation.php`
- Modify: `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`
- Modify: `wp-content/themes/purple-optimize/style.css`

**Interfaces:**
- Consumes: native Woo Cart/Checkout blocks, existing shipping progress, policy links, checkout help, required/optional labels, inline offer, and mobile native-action behavior.
- Produces: quiet cart and checkout hierarchy plus policy-safe summaries.

- [ ] **Step 1: Add the policy-summary safety helper**

Implement:

```php
function pot_presentation_policy_summary_is_safe( string $summary, array $material_terms ): bool {
	$normalized = strtolower( wp_strip_all_tags( $summary ) );
	foreach ( $material_terms as $term ) {
		if ( '' !== trim( $term ) && false === strpos( $normalized, strtolower( trim( $term ) ) ) ) {
			return false;
		}
	}
	return '' !== trim( $normalized );
}
```

Adapters must fall back to a neutral linked policy label when a configured summary fails this check.

- [ ] **Step 2: Implement the cart template**

Render header, title/notices, native post content, and native upsells in `.pot-cart-layout`. CSS targets native `.wc-block-cart__main` and `.wc-block-cart__sidebar` to create the two-column decision/summary layout without moving or cloning actions.

- [ ] **Step 3: Implement the checkout template**

Render the child checkout header, notices, and native checkout content inside `.pot-checkout-layout`. Do not render the storefront footer. Existing `pot-enclosed-checkout` logic remains the state gate.

- [ ] **Step 4: Style cart and checkout**

Use calm white surfaces, concise borders, sticky desktop summaries, single-column mobile flow, visible validation, and existing native sticky cart/checkout actions. Keep coupons, payment methods, legal consent, totals, and edit-cart access functional.

- [ ] **Step 5: Run contracts**

Expected: enclosed-state, policy, single-native-action, cart, and checkout contracts pass.

---

### Task 6: Align Toolkit CRO Components With the New System

**Files:**
- Modify: `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.css`
- Modify: `wp-content/plugins/purple-optimize-toolkit/templates/offer-funnel.php`
- Modify: `tests/storefront-visual-contracts.test.mjs`

**Interfaces:**
- Consumes: child tokens and existing toolkit markup/behavior.
- Produces: visually coherent promo, search, savings, stock, countdown, wishlist, shipping progress, proof, inline offer, and offer-funnel surfaces.

- [ ] **Step 1: Replace toolkit visual rules**

Use child tokens with literal fallbacks. Remove decorative gradients, oversized radii, pulsing animation, and non-behavioral shadows. Preserve live timers, dismissals, wishlist state, focus visibility, and status semantics.

- [ ] **Step 2: Refine the offer template hierarchy**

Keep the existing form, nonce, product, discount, timer, accept/reject values, and fine print unchanged. Adjust wrapper classes only as needed for media, proof, price, action, and policy hierarchy.

- [ ] **Step 3: Add visual regression contracts**

Assert flat toolkit surfaces, no urgency animations, token use, and unchanged accept/reject form values.

- [ ] **Step 4: Run all repository contracts**

Run:

```bash
node --test tests/storefront-visual-contracts.test.mjs tests/storefront-content-contracts.test.mjs tests/performance-contracts.test.mjs
php tests/storefront-presentation-contracts.php
```

Expected: all tests pass.

---

### Task 7: Install and Verify on `purple-optimize.test`

**Files:**
- Modify only if verification reveals a defect in the implementation files above.
- Capture: `docs/performance/screenshots/2026-08-14-purovitalis-human-tonik/` for local review evidence.

**Interfaces:**
- Consumes: completed child theme and toolkit implementation.
- Produces: verified local storefront and screenshots for user review; no repository commit.

- [ ] **Step 1: Validate source before installation**

Run PHP syntax checks for every changed PHP file, JavaScript syntax checks, all contract tests, and `git diff --check`.

- [ ] **Step 2: Confirm the Valet site path and active packages**

Run:

```bash
valet links
wp theme status purple-optimize --path=/Users/sol/sites/purple-optimize
wp plugin status purple-optimize-toolkit --path=/Users/sol/sites/purple-optimize
```

The Valet link resolves to `/Users/sol/sites/purple-optimize`. Reconfirm that exact mapping before any copy or WP-CLI operation; do not use an unresolved variable as a destructive target.

- [ ] **Step 3: Install the working tree packages**

Copy only `wp-content/themes/purple-optimize` and `wp-content/plugins/purple-optimize-toolkit` into their exact matching directories under the resolved local site's `wp-content`. Preserve the parent Purple theme and unrelated local content.

- [ ] **Step 4: Read back installed versions and active state**

Use WP-CLI to confirm the child theme and toolkit files resolve from the local site and remain active. Read back the installed stylesheet header and plugin version.

- [ ] **Step 5: Verify the buyer journey**

Check homepage, shop/category, one simple product, one variable/grouped product when available, cart, checkout, and offer path at desktop and mobile widths. Confirm one native action, real reviews/prices/stock, keyboard focus, no horizontal overflow at 320px, and no console errors.

- [ ] **Step 6: Capture review screenshots**

Capture desktop and mobile screenshots for homepage, shop/category, product, cart, and checkout. Store them under the dated screenshot directory.

- [ ] **Step 7: Re-run tests after local fixes**

Run all contracts, PHP/JS syntax checks, and `git diff --check` again. Report any unverified gateway/device-conditional behavior separately.

- [ ] **Step 8: Hand off for user review**

Provide the local URL, summarize implemented surfaces and coverage gaps, link the screenshot directory, and explicitly state that nothing was committed, staged, pushed, or deployed.
