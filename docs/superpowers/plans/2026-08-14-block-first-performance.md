# Block-First Storefront Performance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduce initial storefront weight and rendering work while preserving Purple, WooCommerce Blocks, and all current conversion behavior.

**Architecture:** Establish a reproducible five-route mobile benchmark, then optimize the measured render path in isolated layers: typography and media priority in the child theme, route-aware behavior in the toolkit, and safe diagnostics for backend bottlenecks. WooCommerce cart, checkout, payment, Store API, mini-cart, and Interactivity API dependencies remain intact.

**Tech Stack:** WordPress block theme, WooCommerce Blocks, PHP 7.4+, browser-native JavaScript, CSS, Node.js contract tests, Chrome DevTools Protocol, Valet.

## Global Constraints

- Preserve the current block theme, block cart, block checkout, design, accessibility, and CRO behavior.
- Do not add jQuery, polling, duplicate Store API requests, a build step, an external font service, or a performance plugin.
- Do not remove WooCommerce dependencies based only on filenames.
- Homepage, shop, and product target 400 KB or less initial transfer where test content permits.
- Public routes target 45 requests or fewer where required WooCommerce dependencies permit.
- Cart and checkout correctness take priority over synthetic scores.
- Apply changes only to the local codebase and `purple-optimize.test`.
- Do not stage, commit, push, or alter GitHub until the user approves the local result.

---

### Task 1: Reproducible performance measurement

**Files:**
- Create: `tools/measure-storefront-performance.mjs`
- Create: `docs/performance/2026-08-14-block-first-baseline.md`
- Modify: `tests/performance-contracts.test.mjs`

**Interfaces:**
- Consumes: Chrome DevTools endpoint from `POT_CDP_URL`, storefront origin from `POT_SITE_URL`.
- Produces: One JSON object per route containing URL, TTFB, DOMContentLoaded, load, transfer bytes, request count, and resource-type totals.

- [ ] **Step 1: Add a failing measurement contract**

Assert that the measurement script exists, covers `/`, `/shop/`, `/product/petal-crew-sweater/`, `/cart/`, and `/checkout/`, applies a 390 x 844 mobile viewport, and records transfer and request totals without writing browser profiles into the repository.

- [ ] **Step 2: Run the contract and confirm RED**

Run: `node --test tests/performance-contracts.test.mjs`

Expected: FAIL because `tools/measure-storefront-performance.mjs` does not exist.

- [ ] **Step 3: Implement the CDP measurement script**

Use browser-native `fetch` and `WebSocket`; accept `POT_CDP_URL` and `POT_SITE_URL`; create and close one target per route; enable Network and Page domains; apply the mobile viewport and simulated 4G profile; emit newline-delimited JSON only.

- [ ] **Step 4: Record the approved baseline**

Write the measured table from the design spec and label it local simulated-4G engineering evidence rather than production field data.

- [ ] **Step 5: Verify GREEN**

Run: `node --test tests/performance-contracts.test.mjs`

Expected: PASS.

---

### Task 2: System-font render path and media priority

**Files:**
- Modify: `wp-content/themes/purple-optimize/theme.json`
- Modify: `wp-content/themes/purple-optimize/style.css`
- Modify: `wp-content/themes/purple-optimize/patterns/storefront-home.php`
- Modify: `wp-content/themes/purple-optimize/functions.php`
- Modify: `tests/performance-contracts.test.mjs`

**Interfaces:**
- Consumes: Existing hero and product-gallery block markup.
- Produces: A system-font global style, one eager/high-priority hero image, and lazy/low-priority non-primary gallery images with intrinsic dimensions preserved.

- [ ] **Step 1: Add failing typography and media contracts**

Assert that `theme.json` defines and applies a system stack, the homepage hero block declares `loading:"eager"` and `fetchPriority:"high"`, and the gallery filter preserves the primary image while marking later images lazy/low.

- [ ] **Step 2: Run the contract and confirm RED**

Run: `node --test tests/performance-contracts.test.mjs`

Expected: FAIL on missing system font and hero priority attributes.

- [ ] **Step 3: Implement the minimal render-path changes**

Add a `system` font-family preset using `-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`, apply it globally, remove the Jost fallback from child CSS, add high-priority attributes to the single hero image, and keep the existing gallery tag-processor logic for non-primary images.

- [ ] **Step 4: Verify GREEN and visual stability**

Run the performance contract, render homepage and product screenshots at 390 x 844 and desktop width, and confirm no layout overflow or missing imagery.

---

### Task 3: Route-aware toolkit execution

**Files:**
- Modify: `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`
- Modify: `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js`
- Modify: `tests/performance-contracts.test.mjs`
- Modify: `tests/storefront-content-contracts.test.mjs`

**Interfaces:**
- Consumes: WordPress conditional tags, toolkit settings, and existing DOM feature roots.
- Produces: `pot_frontend_feature_flags(array $settings): array<string,bool>` and localized `features` flags used to skip setup work for features that cannot render on the current route.

- [ ] **Step 1: Add failing route-feature contracts**

Assert that PHP exposes explicit flags for search, wishlist, product helpers, cart progress, checkout helpers, offers, and recent purchases; JavaScript gates each setup group by its flag; unrelated pages still create no observers, intervals, or requests.

- [ ] **Step 2: Run focused tests and confirm RED**

Run:

```bash
node --test tests/performance-contracts.test.mjs tests/storefront-content-contracts.test.mjs
php tests/storefront-presentation-contracts.php
```

Expected: FAIL because no route-feature map exists.

- [ ] **Step 3: Implement server-owned feature flags**

Compute flags from settings plus `is_front_page()`, `is_shop()`, `is_product_taxonomy()`, `is_product()`, `is_cart()`, `is_checkout()`, `is_account_page()`, and offer-route state. Localize only scalar configuration required by enabled features.

- [ ] **Step 4: Gate JavaScript initialization**

Use `config.features || {}` and call feature setup functions only when the corresponding flag is true. Keep every setup function’s existing DOM guard as defense in depth. Do not add dynamic imports or additional initial requests.

- [ ] **Step 5: Verify GREEN**

Run the focused tests and confirm public routes without eligible features start no timers or observers.

---

### Task 4: Safe script strategy and backend diagnostics

**Files:**
- Modify: `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`
- Create: `docs/performance/2026-08-14-block-first-diagnostics.md`
- Modify: `tests/performance-contracts.test.mjs`

**Interfaces:**
- Consumes: WordPress script-loader strategy API and local WP-CLI installation.
- Produces: Deferred toolkit execution plus a read-only diagnostic record for TTFB, autoloaded options, object cache, cron, and profiler availability.

- [ ] **Step 1: Add a failing script-strategy contract**

Assert that the toolkit script is enqueued in the footer with `strategy => defer`, while no dependency handles are dequeued.

- [ ] **Step 2: Run the contract and confirm RED**

Run: `node --test tests/performance-contracts.test.mjs`

Expected: FAIL because the current enqueue call uses the legacy boolean footer argument.

- [ ] **Step 3: Implement defer safely**

Use the WordPress enqueue argument array:

```php
array(
	'in_footer' => true,
	'strategy'  => 'defer',
)
```

Do not modify WooCommerce script handles.

- [ ] **Step 4: Capture read-only backend diagnostics**

Record five warmed curl TTFB samples per route, WordPress/PHP/WooCommerce versions, autoloaded option bytes, object-cache drop-in status, due cron count, and whether `wp profile` or `wp doctor` is available. Do not install tools or change configuration.

- [ ] **Step 5: Verify GREEN**

Run performance and PHP syntax contracts.

---

### Task 5: Local synchronization and before/after verification

**Files:**
- Create: `docs/performance/2026-08-14-block-first-results.md`
- Modify only if a regression is found: files from Tasks 2-4 and their tests.

**Interfaces:**
- Consumes: Measurement script and completed performance changes.
- Produces: Local installed artifacts matching source plus a before/after route table and stated coverage limits.

- [ ] **Step 1: Run the complete automated suite**

Run:

```bash
node --test tests/storefront-visual-contracts.test.mjs tests/storefront-content-contracts.test.mjs tests/performance-contracts.test.mjs
php tests/storefront-presentation-contracts.php
find wp-content/themes/purple-optimize wp-content/plugins/purple-optimize-toolkit -name '*.php' -print0 | xargs -0 -n1 php -l
node --check wp-content/plugins/purple-optimize-toolkit/assets/toolkit.js
git diff --check
```

- [ ] **Step 2: Sync source to `purple-optimize.test`**

Copy only modified child-theme and toolkit files. Read back hashes. Update a local Site Editor override only if it masks an edited filesystem template part.

- [ ] **Step 3: Exercise commerce behavior**

Verify add to cart, mini-cart, cart quantity changes, item removal, free-shipping banner recalculation, checkout fields, and place-order rendering without submitting an order.

- [ ] **Step 4: Re-run the identical browser benchmark**

Run the repository measurement script against a fresh isolated Chrome profile and record route-level before/after transfer, requests, and navigation timings.

- [ ] **Step 5: Document results and limits**

State which budgets passed, which are constrained by required WooCommerce Blocks payload, and that local synthetic numbers are not production Core Web Vitals.

- [ ] **Step 6: Preserve review-first Git state**

Run `git diff --cached --quiet` and `git status --short`. Do not stage or commit.
