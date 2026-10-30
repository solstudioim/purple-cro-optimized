---
slug: mini-cart-drawer
title: Compare parent and CRO mini-cart opening after add to cart
status: in_progress
created: 2026-10-30
updated: 2026-10-30
---

## Goal

Identify why the CRO demo does not open the native cart drawer after add to cart while the Purple demo does.

## Context

Compare live guest behavior, rendered native block settings, parent source and companion hooks. No live configuration changes authorized for investigation.

## References

- https://purpledemo.wpcomstaging.com/
- https://purplecrodemo.wpcomstaging.com/product/lilac-knit-beanie/
- wp-content/themes/purple/parts/header.html
- wp-content/plugins/purple-optimize-toolkit/

## Next Steps

- Implementation and local validation complete; sync the tested source and provide the 0.11.5 plugin ZIP. Live site remains unchanged until that plugin update is installed.

## Findings

- Live CRO guest cart: native add click emitted wc-blocks_added_to_cart with preserveCartData true, count changed 0 to 1, drawer stayed aria-hidden true. Mini-cart lacks data-wp-on-document--wc-blocks_added_to_cart---open-drawer.
- Official demo product classic-merino-turtleneck-sweater has data-add-to-cart-behaviour open_drawer and native openDrawer added-event handler.
- Bundled parent parts/header.html line 24 leaves mini-cart attributes unset; WooCommerce 11.2.1 mini-cart block.json defaults addToCartBehaviour to none. MiniCart.php lines 385-386 emit auto-open handler only for open_drawer.
- Historical child header at 2c2316d explicitly set addToCartBehaviour open_drawer. Parent-native change eeef815 deleted the child header, removing this intentional behavior alongside styling. Confirmed configuration regression; add-to-cart itself succeeds. Companion wishlist sibling filter preserves native mini-cart HTML.
- Recommended surgical recovery: restore native open_drawer behavior through companion block configuration while retaining parent header/style and respecting explicit merchant block choices. No source/config deployment changes made during diagnosis.

- Manual header cart click opens the same native drawer (aria-hidden false, role dialog). Confirms drawer functionality is intact; missing automatic opening is the isolated regression. Test used a separate browser context and added one item without checkout/payment.

## Implementation

- User authorized fix. Restore native default at render_block_data, limited to Purple Optimize child; retain explicit none/open_drawer and unrelated block/theme choices. Toolkit 0.11.5 distinguishes corrected ZIP. Validate actual rendering and browser add-to-cart behavior before syncing.

- WordPress integration passed: native default handler, explicit none/open choices, unrelated blocks and parent-only isolation. Real browser tests passed on WC11.2.1 + Gutenberg24.3 at 1440/390px: item added, drawer opens, count updates, close/reopen works. 40 Node contracts, PHP syntax and diff whitespace pass. Screenshots and packages under /Users/sol/Codex/artifacts/purple-mini-cart-fix/.
