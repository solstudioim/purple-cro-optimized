# Alessi-Inspired CRO Styling Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply the approved flat, premium CRO hierarchy to the Purple child theme and toolkit, install it on `purple-optimize.test`, and produce repeatable before/after evidence.

**Architecture:** Keep presentation in the child theme and toolkit CSS. Add a small pure PHP presentation-policy module for the default-category exclusion, checkout body state, single sale badge, and low-stock coordination, then consume it from the existing toolkit bootstrap. Preserve all CRO business logic and JavaScript lifecycle work.

**Tech Stack:** WordPress 6.7+, PHP 7.4+, WooCommerce blocks, CSS, Node test runner, PHP CLI, WP-CLI, Laravel Valet.

## Global Constraints

- Retain brand purple `#5b21b6`, dark purple `#3b0764`, purchase orange `#c2410c`, and hover orange `#9a3412`.
- Add no JavaScript/CSS framework, webfont, icon library, remote API call, new image, or animation library.
- Keep the product-page mobile sticky Add to cart disabled.
- Cart and checkout must each use one existing native sticky action, never a duplicate.
- Preserve every current CRO setting and funnel behavior.
- Install and verify each approved iteration on `http://purple-optimize.test`.

---

### Task 1: Test presentation policies before implementation

**Files:**
- Create: `tests/storefront-presentation-contracts.php`
- Create: `wp-content/plugins/purple-optimize-toolkit/includes/storefront-presentation.php`

**Interfaces:**
- Produces: `pot_presentation_category_query_args(int): array`, `pot_presentation_checkout_classes(array,bool,bool,bool,bool): array`, `pot_presentation_sale_badge_html(int): string`, and `pot_presentation_suppresses_native_stock(bool,?int,int,bool,bool): bool`.

- [ ] **Step 1: Write the failing behavior test** with literal expectations for excluded category IDs, active checkout states, one replacement badge, and safe low-stock suppression.

```php
assert( array( 7 ) === pot_presentation_category_query_args( 7 )['exclude'] );
assert( in_array( 'pot-enclosed-checkout', pot_presentation_checkout_classes( array(), true, false, false, false ), true ) );
assert( ! in_array( 'pot-enclosed-checkout', pot_presentation_checkout_classes( array(), true, true, false, false ), true ) );
assert( 1 === substr_count( pot_presentation_sale_badge_html( 19 ), 'Save 19%' ) );
assert( pot_presentation_suppresses_native_stock( true, 3, 5, true, false ) );
assert( ! pot_presentation_suppresses_native_stock( true, 0, 5, false, false ) );
```

- [ ] **Step 2: Run `php -d assert.exception=1 tests/storefront-presentation-contracts.php`** and confirm it fails because the production module is absent.
- [ ] **Step 3: Implement the four pure functions** using integer bounds, strict booleans, one escaped badge string, and conservative availability rules.
- [ ] **Step 4: Re-run the PHP test** and confirm all assertions pass.

### Task 2: Integrate presentation policies into WooCommerce rendering

**Files:**
- Modify: `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`
- Modify: `tests/storefront-presentation-contracts.php`

**Interfaces:**
- Consumes: the four pure functions from Task 1.
- Produces: query exclusion in `pot_category_navigation()`, replacement output in `pot_filter_sale_badge_block()`, `pot-enclosed-checkout` from `body_class`, and scoped native-stock filtering.

- [ ] **Step 1: Extend the failing test** to verify the module is loaded by the plugin bootstrap contract and that native stock is suppressed only when a visible toolkit warning replaces it.
- [ ] **Step 2: Run both test files** and confirm the new integration expectation fails.
- [ ] **Step 3: Require the presentation module**, pass WooCommerce's `default_product_cat` to the term query, return only the calculated badge, register the checkout body class, and filter native availability only for qualifying single-product requests.
- [ ] **Step 4: Bump plugin version to `0.7.0`** in the header and `POT_VERSION`.
- [ ] **Step 5: Run `php -l` on both PHP production files and run both test files** until green.

### Task 3: Apply the premium flat visual hierarchy

**Files:**
- Modify: `wp-content/themes/purple-optimize/style.css`
- Modify: `wp-content/themes/purple-optimize/theme.json`
- Modify: `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.css`
- Modify: `tests/storefront-visual-contracts.test.mjs`

**Interfaces:**
- Consumes: existing markup and `pot-enclosed-checkout`.
- Produces: one purchase-action system, flat product/CRO surfaces, enclosed checkout, responsive native sticky actions, and restrained offer/social-proof components.

- [ ] **Step 1: Write a failing Node visual-contract test** that parses rule bodies and asserts: 4:5 card media, orange primary controls, no transform on product-card hover, no gradients in `.pot-promo`, `.pot-buy-box`, `.pot-countdown`, `.pot-offer-funnel-page`, or `.pot-inline-offer`, hidden checkout chrome under `.pot-enclosed-checkout`, and no timer/stock animation.
- [ ] **Step 2: Run `node --test tests/storefront-visual-contracts.test.mjs`** and confirm the current decorated styles fail those contracts.
- [ ] **Step 3: Update child-theme tokens and commerce layout** to a warm-white canvas, 4–8px radii, borderless/minimally divided cards, 4:5 images, 48px orange purchase actions, no lift, a tighter product buy column, and a flat checkout main area.
- [ ] **Step 4: Update toolkit CSS** to a solid promo, plain category bar, single flat badge, compact stock/countdown/reassurance/review treatments, simplified shipping/trust/wishlist/social proof, and a white two-column offer funnel.
- [ ] **Step 5: Add enclosed-checkout CSS** that removes promo/category/main navigation/footer link-farm from layout and keyboard flow while retaining clickable site identity, checkout content, and help-compatible header content.
- [ ] **Step 6: Preserve responsive behavior** with 44px targets, one fixed native cart/checkout action, no mobile sticky product CTA, and no sticky-content overlap.
- [ ] **Step 7: Bump the child theme version to `0.4.0`**, run the visual test, and confirm it passes.

### Task 4: Verify code, behavior, and performance before installation

**Files:**
- Modify: `README.md`
- Create: `docs/performance/2026-08-09-premium-cro-before-after.md`

**Interfaces:**
- Consumes: completed theme/plugin source and existing 2026-08-09 performance baseline.
- Produces: release notes and an evidence record for this iteration.

- [ ] **Step 1: Update README presentation and installation notes** without changing the established feature claims.
- [ ] **Step 2: Run `node --test tests/*.test.mjs`, both PHP contract scripts, PHP syntax checks, JSON validation for `theme.json`, and `git diff --check`**.
- [ ] **Step 3: Record raw/gzip child-theme and toolkit asset sizes** and compare them to the existing after-performance report.
- [ ] **Step 4: Document the visual and asset-size before state**, with the local-only evidence limitation.

### Task 5: Install and validate on Valet

**Files:**
- Copy source packages into: `/Users/sol/sites/purple-optimize/wp-content/themes/purple-optimize`
- Copy source packages into: `/Users/sol/sites/purple-optimize/wp-content/plugins/purple-optimize-toolkit`
- Modify: `docs/performance/2026-08-09-premium-cro-before-after.md`

**Interfaces:**
- Consumes: verified repository packages.
- Produces: active local iteration and browser/HTTP evidence.

- [ ] **Step 1: Inspect Valet paths/links and confirm the target resolves to `/Users/sol/sites/purple-optimize`**.
- [ ] **Step 2: Capture before screenshots for home, shop, product, cart, and checkout when a current pre-install page remains available**; otherwise identify the existing screenshots/evidence as the baseline.
- [ ] **Step 3: Copy only the child-theme and toolkit package files to the confirmed local WordPress site**, preserving uploads, settings, database, and unrelated themes/plugins.
- [ ] **Step 4: Read back active theme/plugin state and versions with WP-CLI**, then request home, shop, product, cart, and checkout routes.
- [ ] **Step 5: Verify in the browser at desktop and mobile widths**: one sale badge, one stock message, live seconds, post-add View cart, cart guidance, one native sticky action per cart/checkout, enclosed checkout, and accessible offer yes/no controls.
- [ ] **Step 6: Capture after screenshots and repeat the five-sample route/asset-size measurements**.
- [ ] **Step 7: Finish the evidence document with actual results and limitations**, then run the complete verification suite once more.

### Task 6: Commit the completed iteration

**Files:**
- Stage only the implementation, tests, README, plan, and evidence files.

- [ ] **Step 1: Inspect `git status --short` and leave `.env` and `AGENTS.md` untouched and unstaged.**
- [ ] **Step 2: Review the final diff against every acceptance item in the approved design spec.**
- [ ] **Step 3: Run the full verification command immediately before committing.**
- [ ] **Step 4: Commit with `Implement premium CRO storefront styling`.**

