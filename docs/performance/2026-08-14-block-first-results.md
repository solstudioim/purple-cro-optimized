# Block-first performance results

Measured on 2026-08-14 against `purple-optimize.test` in isolated headless Chrome at 390 × 844 with simulated 4G. These local engineering measurements are useful for regressions; they are not field Core Web Vitals.

| Route | Transfer | Requests | TTFB | DOM ready | Change from baseline |
| --- | ---: | ---: | ---: | ---: | --- |
| Home | 727,723 B | 43 | 702 ms | 2,521 ms | Transfer flat; TTFB 84 ms faster |
| Shop | 321,998 B | 59 | 329 ms | 1,891 ms | Transfer +6,009 B; TTFB 12 ms faster |
| Product | 484,216 B | 68 | 336 ms | 2,035 ms | Transfer flat; remaining 6.6 KB font is WooCommerce's icon font |
| Cart | 818,836 B | 78 | 274 ms | 4,666 ms | Transfer flat; DOM ready 17 ms faster |
| Checkout request with empty cart | 818,836 B | 78 | 497 ms | 4,891 ms | Redirected to cart; transfer flat |

## What improved

- The storefront body now resolves to the operating-system font stack. No child-theme webfont is requested.
- The homepage hero is the only explicitly eager, high-priority theme image; non-primary product gallery images remain lazy and low priority.
- Toolkit behavior is route-aware, so irrelevant observers, timers, storage reads, and DOM initialization do not run.
- The dependency-free toolkit script is deferred.
- WooCommerce block cart and checkout assets remain intact.
- Mobile product sticky add-to-cart was browser-verified: `$112.00` stays visually on one line and the live region announces `Added to cart ✓`.
- Cart removal was browser-verified: the banner changed from the reached-threshold message to `Add $75.00 more…` immediately from WooCommerce's data store, without polling or a custom request.

## Largest remaining constraints

- Home transfer is dominated by six images (about 537 KB).
- Product transfer is dominated by eleven images (about 246 KB).
- Cart and checkout are dominated by WooCommerce block scripts (about 703 KB). Those assets are intentionally retained to preserve the supported block experience.
- Local timing variation is larger than the byte-level change from route-gating, which is primarily a main-thread/runtime improvement rather than a bundle-size reduction.

The next material payload gain should come from production image derivatives and delivery configuration, measured against real product photography. Replacing Woo's maintained block cart/checkout with custom classic templates is not recommended for this theme.
