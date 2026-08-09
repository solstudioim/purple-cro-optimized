# Premium CRO styling before and after

Date: 2026-08-09

Environment: Laravel Valet at `http://purple-optimize.test`

Theme: Purple Optimize `0.3.0` → `0.4.0`

Toolkit: Purple Optimize Toolkit `0.6.0` → `0.7.0`

## Outcome

The installed storefront now uses a simpler product-led hierarchy while retaining
the existing CRO feature set. The visible changes are CSS-first, with small PHP
presentation policies for current-sale badges, default-category exclusion, scoped
stock-message coordination, and enclosed checkout state.

Confirmed in a real headless browser against the Valet site:

- Solid purple promotion strip; no CRO gradients or decorative component shadows.
- `Uncategorized` removed by excluding WooCommerce's configured default category.
- Transparent text-link treatment for the homepage secondary CTA.
- Product cards use stable 4:5 media and no hover lift.
- The checked product exposes one Add to cart button, one toolkit low-stock message,
  no redundant native stock indicator, and no inactive-sale badge.
- Adding the product exposes one View cart link.
- Cart exposes one native checkout action and retains the policy/trust panel.
- Active checkout has no promotion/category/policy bars, retains its clickable site
  title, exposes one native Place Order button, and suppresses the footer link farm.
- Native cart and checkout action containers remain fixed on mobile; product mobile
  sticky Add to cart remains disabled.
- No functional JavaScript console errors were found. The only browser console 404
  was the site's pre-existing missing `/favicon.ico`.

## Visual evidence

| Route | Before | After |
| --- | --- | --- |
| Home desktop | [before](screenshots/2026-08-09-premium-before/desktop-home.jpg) | [after](screenshots/2026-08-09-premium-after/desktop-home.jpg) |
| Shop desktop | [before](screenshots/2026-08-09-premium-before/desktop-shop.jpg) | [after](screenshots/2026-08-09-premium-after/desktop-shop.jpg) |
| Product desktop | [before](screenshots/2026-08-09-premium-before/desktop-product.jpg) | [after](screenshots/2026-08-09-premium-after/desktop-product.jpg) |
| Cart desktop | [before](screenshots/2026-08-09-premium-before/desktop-cart.jpg) | [after](screenshots/2026-08-09-premium-after/desktop-cart.jpg) |
| Checkout desktop | [before](screenshots/2026-08-09-premium-before/desktop-checkout.jpg) | [after](screenshots/2026-08-09-premium-after/desktop-checkout.jpg) |
| Home mobile | [before](screenshots/2026-08-09-premium-before/mobile-home.jpg) | [after](screenshots/2026-08-09-premium-after/mobile-home.jpg) |
| Shop mobile | [before](screenshots/2026-08-09-premium-before/mobile-shop.jpg) | [after](screenshots/2026-08-09-premium-after/mobile-shop.jpg) |
| Product mobile | [before](screenshots/2026-08-09-premium-before/mobile-product.jpg) | [after](screenshots/2026-08-09-premium-after/mobile-product.jpg) |
| Cart mobile | [before](screenshots/2026-08-09-premium-before/mobile-cart.jpg) | [after](screenshots/2026-08-09-premium-after/mobile-cart.jpg) |
| Checkout mobile | [before](screenshots/2026-08-09-premium-before/mobile-checkout.jpg) | [after](screenshots/2026-08-09-premium-after/mobile-checkout.jpg) |

## Frontend asset size

Compared with the prior optimized baseline in `2026-08-09-after.json`:

| Theme + toolkit assets | Prior | Current | Delta |
| --- | ---: | ---: | ---: |
| Raw | 39,840 B | 39,667 B | -173 B (-0.43%) |
| Gzip | 10,561 B | 10,399 B | -162 B (-1.53%) |

The styling iteration therefore reduced the measured first-party theme/toolkit
payload despite adding the presentation rules.

## Current five-sample HTTP check

The same compressed curl method was repeated after final installation. Median
results are retained as an environment health check, not claimed as a causal
speed improvement over the earlier run.

| Route | HTTP | Median TTFB | Median compressed HTML |
| --- | ---: | ---: | ---: |
| Home | 200 | 208.224 ms | 34,951 B |
| Shop | 200 | 215.581 ms | 42,185 B |
| Product | 200 | 220.142 ms | 40,501 B |
| Cart | 200 | 174.918 ms | 48,295 B |
| Checkout | 200 | 166.976 ms | 43,547 B |

All medians remain under the project's 250 ms local threshold. They are 18–24%
slower than the earlier local run across every route, including routes unaffected
by the new product policies. That broad movement is treated as Valet/PHP/database
run variance, not as evidence of a frontend styling gain. The payload result is
repeatable; production speed still requires field Core Web Vitals and hosting data.

## Verification commands

```text
node --test tests/*.test.mjs
php -d assert.exception=1 tests/storefront-presentation-contracts.php
php -l wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php
php -l wp-content/plugins/purple-optimize-toolkit/includes/storefront-presentation.php
jq empty wp-content/themes/purple-optimize/theme.json
git diff --check
```
