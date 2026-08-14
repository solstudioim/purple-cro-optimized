# Block-First Storefront Performance Design

## Goal

Make Purple Optimize materially faster while preserving the current block theme, WooCommerce block cart and checkout, storefront design, and conversion functionality.

## Evidence Baseline

Measured locally in isolated headless Chrome at a 390 x 844 viewport with simulated 4G latency:

| Route | Transfer | Requests | Dominant payload |
|---|---:|---:|---|
| Homepage | 728 KB | 43 | 538 KB images |
| Shop | 316 KB | 58 | Block scripts and styles |
| Product | 484 KB | 68 | 246 KB gallery images |
| Cart | 819 KB | 78 | 696 KB WooCommerce Blocks JavaScript |
| Checkout | 819 KB | 78 | 696 KB WooCommerce Blocks JavaScript |

These are reproducible local engineering measurements, not production Core Web Vitals.

## Performance Budgets

- Homepage, shop, and product initial transfer: 400 KB or less where test content permits.
- Public storefront routes: 45 requests or fewer where required WooCommerce block dependencies permit.
- Child-theme and toolkit code must add no initial network requests beyond their versioned CSS and route-required JavaScript.
- Production-like mobile LCP target: 2.5 seconds or less.
- CLS target: 0.1 or less.
- INP target: 200 milliseconds or less.
- Cart and checkout correctness, gateway compatibility, and accessibility take priority over a synthetic score.

## Architecture

### 1. Repeatable measurement

Add a repository-owned browser measurement script that captures the same five routes, mobile viewport, throttling profile, transfer size, request count, and navigation timings before and after changes. Store summarized evidence in the project, not browser profiles or temporary runtime data.

### 2. Media delivery

- Preserve the current visual design and crops.
- Generate lighter responsive variants for theme-owned hero media.
- Declare intrinsic dimensions and responsive source information.
- Give only the above-the-fold primary image high fetch priority.
- Keep below-the-fold and non-primary gallery media lazy and low priority.
- Do not degrade product-detail usefulness or accessible alternative text.

### 3. Typography and render path

- Use a system-font stack so no font file is required for first render.
- Preserve the current hierarchy, sizing, and approximate visual character.
- Avoid a build step, runtime font loader, or external font service.
- Keep the existing small, readable CSS architecture; do not introduce a framework.

### 4. Route-aware toolkit assets

- Map each toolkit feature to the routes and markup that use it.
- Load storefront interaction code only when at least one supported feature can render.
- Keep cart and checkout enhancements in a minimal commerce path.
- Do not poll, duplicate WooCommerce Store API requests, or add jQuery.
- Preserve instant search, wishlist, promotion, purchase feedback, shipping progress, offers, and accessibility behavior where enabled.

### 5. Safe WordPress and WooCommerce asset control

- Remove an asset only when its owning block or feature is absent from the rendered route.
- Never dequeue a cart, checkout, payment, mini-cart, Store API, or Interactivity API dependency based only on its filename.
- Add regression tests for route decisions and confirm WooCommerce updates cannot silently remove required dependencies.
- Prefer WordPress block-style loading and native script strategies over manual concatenation.

### 6. Backend diagnostics

- Capture TTFB samples, autoloaded option size, object-cache status, cron condition, and available WP-CLI profiling support.
- Make code changes only for a measured bottleneck in the child theme or toolkit.
- Do not install cache plugins, alter server configuration, enable production profiling, or flush caches as part of this local implementation.

## Verification

- Run existing storefront, presentation, syntax, and performance contracts.
- Add tests for system-font use, media priority, route-aware asset loading, and absence of polling or duplicate requests.
- Re-run the identical five-route browser benchmark after implementation.
- Compare before and after route totals and document any budget that cannot be met because of required WooCommerce Blocks payload.
- Exercise add to cart, mini-cart, cart quantity changes, removal, free-shipping progress, checkout fields, and place-order rendering.
- Preserve unstaged, uncommitted review-first Git state until the user approves the result.

## Explicit Non-Goals

- Replacing the Purple block theme.
- Replacing block cart or block checkout with classic templates.
- Copying or purchasing proprietary UltraFastWoo code.
- Removing CRO functionality without evidence and explicit approval.
- Claiming local synthetic measurements are production field data.
