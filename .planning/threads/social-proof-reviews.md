---
slug: social-proof-reviews
title: Social proof review shortcode configurator
status: resolved
created: 2026-10-30
updated: 2026-10-30
---

## Goal

Rename Privacy to Social proof and add a configurator generating theme-styled review shortcodes for reuse outside native product reviews.

## Context

Keep privacy URL/key and recent-purchase settings intact. Native approved WooCommerce reviews only: all products, selected product IDs, or specific review IDs. Self-contained generated shortcodes support multiple placements. Static list and opt-in accessible rotator. Keep product attribution and genuine verified-owner labels. No automatic placements or live-site edits.

## References

- wp-content/plugins/purple-optimize-toolkit/includes/admin-settings.php
- wp-content/plugins/purple-optimize-toolkit/includes/checkout-content.php
- tests/checkout-content-integration.php

## Next Steps

- Implementation complete locally. Install toolkit 0.11.6 ZIP on the demo, generate a shortcode under Social proof, and paste it into the desired Shortcode block. No remote sync or live deployment in this task.

## Implementation and verification

- Label Privacy renamed Social proof, retaining privacy section key and existing settings/save ownership. Recent purchases card remains independently saved.
- Added includes/reviews.php and admin-reviews.php: approved native review query, products/all/review-ID sources, native Woo product search + manual IDs, bounded count/rating/order, name choice, heading, shortcode generation, nonce/capability-protected matching preview. Configurator never writes global settings.
- Added component-scoped reviews.css and reviews.js: inherit theme colors/fonts, static cards, opt-in rotator/autoplay, previous/next, hover/focus/pause, reduced motion, all-visible no-JS fallback, isolated multi-instance state and dynamic checkout insertion handling.
- Checkout allows core/shortcode only by converting the plugin review shortcode to safe HTML. No generic do_shortcode call; unrelated shortcode callbacks remain blocked. No automatic placement, no external scraping, and native product-page reviews unchanged.
- PHP integrations passed: review eligibility, approved/pending/spam/protected/draft/private boundaries, product/review selection, rating/order/count bounds, escaping, first-name/anonymous attribution, verified-owner provenance, immediate moderation, restricted nested checkout rendering. Existing settings and native checkout content integration passed; 40 Node contracts and syntax/whitespace checks passed.
- Browser on WC11.2.1 + Gutenberg24.3 passed: old section URL/new label; native product search; generation/preview/no saved-setting mutation; selected review IDs; invalid nonce and guest rejection; independent placements; desktop/mobile inherited typography; manual navigation; hydrated checkout; no-JS fallback; reduced motion; autoplay, hover and persistent explicit pause. Evidence: /Users/sol/Codex/artifacts/purple-social-proof-reviews/browser/.
- Toolkit preview 0.11.6; parent source unchanged. Local test fixtures/settings restored. No live-site edits or remote commit/push for this feature.

- Existing workspace/browser regression passed after correcting an obsolete test that manually clicked the already-open cart drawer. Existing cart-drawer desktop/mobile checks pass. PHP parent advisory/presentation checks and all 40 Node contracts pass. Review/checkout fixture settings restored and no synthetic reviews retained.

## Checkout review styling refinement

- User supplied screenshot with an outer checkout content frame and soft lavender inner review. Remove only the inner review outline in .pot-checkout-content, using parent theme-5 for the tint and inherited text. Standalone review cards retain their frame. Preview 0.11.7 distinguishes the changed CSS from installed 0.11.6. Local only; verify existing review browser flow and visual screenshot before packaging.

- Styling refinement complete locally in 0.11.7. Browser review flow passed; checkout mobile screenshot visually confirms the existing outer border and a borderless theme-tinted inner card. Standalone card border remains scoped outside checkout. All 40 Node contracts and whitespace checks pass. Updated packages and screenshot under /Users/sol/Codex/artifacts/purple-review-card-style/. No live deployment or remote sync.

## Subtle rotator controls

- User requests no navigation bar/counter; multi-review rotators should use small borderless left/right arrows within the review box. Keep single reviews free of controls, accessible names and a screen-reader-only position counter. Optional autoplay retains a small pause/play icon. Preview 0.11.8; local only.

- 0.11.8 refinement verified: review browser suite passes manual arrow navigation, independent instances, hydrated checkout, autoplay/pause, no-JS fallback and reduced motion. Mobile screenshot confirms borderless subtle edge arrows and no visible counter/navigation bar. 40 Node contracts, syntax and whitespace checks pass. Packages/evidence: /Users/sol/Codex/artifacts/purple-review-rotator-arrows/. No live deployment or remote sync.
