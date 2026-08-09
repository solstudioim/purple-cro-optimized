# Purple CRO Storefront Performance Design

## Goal

Make the Purple Optimize child theme and Purple Optimize Toolkit materially
faster without removing conversion features, changing offer behavior, or
depending on a particular host, CDN, or optimization plugin.

This work improves portable theme and plugin code. Production hosting, CDN,
full-page caching, persistent object caching, and field Core Web Vitals remain
deployment responsibilities and are outside this implementation.

## Baseline

Five repeated local HTTP samples on `purple-optimize.test` showed median TTFB
of approximately 0.18 to 0.21 seconds for the home, shop, and product routes.
The cart route was approximately 0.14 seconds. These local Valet results show a
healthy backend baseline but do not predict production or real-device Core Web
Vitals.

The product page exposed approximately 70 to 76 assets, including 27 scripts,
29 stylesheets, 11 or more images, and two fonts. The toolkit contributes only
about 9 KB of compressed CSS and JavaScript. The larger opportunities are
unnecessary background JavaScript work and eager discovery of hidden full-size
gallery images, including source files around 591 KB and 205 KB.

## Architecture

Presentation and image-loading markup changes remain in the Purple Optimize
child theme where possible. Conversion behavior and toolkit runtime changes
remain in `purple-optimize-toolkit`. The parent Purple theme and WooCommerce
files remain unmodified and upgradeable.

The existing single toolkit asset bundle remains intact. Its compressed size
is small enough that splitting it would add requests and complexity without a
meaningful payload reduction.

## Runtime Optimization

### Countdown lifecycle

- Query countdown elements before creating an interval.
- Create one shared one-second interval only when at least one product
  countdown exists.
- Remove expired countdowns and clear the interval when no active countdowns
  remain.
- Keep offer countdown expiry enforcement unchanged.
- Continue respecting `prefers-reduced-motion` for visual animation.

### Observer lifecycle

- Do not create the cart, checkout, or account-placement observer when none of
  its target elements exist and the route cannot create them.
- Scope checkout field-label observation to checkout only.
- Scope cart-empty observation to cart only.
- Disconnect placement observers after their relevant elements are placed and
  no dynamic checkout target remains.
- Preserve compatibility with WooCommerce Blocks, whose checkout and cart
  content can render asynchronously.

### Existing features

Instant search remains input-triggered and abortable. Wishlist, recent-purchase
proof, sticky controls, free-shipping progress, View cart confirmation, and
upsell/downsell behavior remain unchanged.

## Product Gallery Loading

Use a scoped `render_block_woocommerce/product-gallery` filter with
`WP_HTML_Tag_Processor`:

- Preserve the visible primary product image as eager and high priority.
- Preserve existing responsive `srcset` and `sizes` attributes.
- Add `loading="lazy"` and `fetchpriority="low"` only to gallery images that
  lack an explicit loading strategy and are not the primary high-priority
  image.
- Do not alter images outside the WooCommerce product gallery block.
- Do not remove zoom, full-screen viewing, thumbnails, or gallery navigation.

## Importer Safeguards

Future Openverse and Wikimedia demo imports will:

- retain source and license metadata;
- enforce the existing MIME, dimensions, and file-size validation;
- resize oversized originals to a bounded maximum dimension before attachment
  metadata is generated;
- use WordPress image-editor quality controls and WebP output when the active
  image editor supports it;
- fall back safely to the validated source format when WebP conversion is not
  available.

Existing media is not destructively rewritten by this change.

## Explicit Non-goals

- Dequeuing WooCommerce block scripts or styles.
- Modifying WooCommerce or the Purple parent theme.
- Installing caching or image-optimization plugins.
- Changing Nginx, CDN, page-cache, object-cache, or production infrastructure.
- Claiming production Core Web Vitals from local Valet measurements.

## Failure Handling

- If `WP_HTML_Tag_Processor` is unavailable, return the original gallery
  markup unchanged.
- If an image cannot be edited or converted, keep the validated original and
  continue the import with its source metadata intact.
- JavaScript initializers return without side effects when their required DOM
  targets do not exist.
- Countdown expiry continues to disable and reject expired offers securely.

## Verification

Run the same checks before and after implementation:

1. Five HTTP samples for home, shop, product, cart, and checkout routes.
2. Browser asset inventory and product gallery request behavior.
3. JavaScript syntax check, PHP lint, JSON validation, and `git diff --check`.
4. Browser console error check.
5. Product timer and expiry behavior.
6. Product add-to-cart and single View cart confirmation.
7. Cart guidance and sticky native checkout action.
8. Checkout field labels, completion action, and optional inline offer.
9. Pre-checkout and post-purchase offer routes.
10. Wishlist, instant search, social proof, and empty-cart behavior.
11. Workspace-to-Valet file comparison after installation.

## Acceptance Criteria

- Local median TTFB remains below 250 ms on the measured content routes.
- Pages without product countdowns create no countdown interval.
- Unrelated pages create no toolkit-wide mutation observer.
- Hidden gallery images without an explicit strategy receive lazy, low-priority
  attributes while the primary image remains eager and high priority.
- No approved conversion feature is removed or behaviorally regressed.
- No new PHP, JavaScript, browser-console, or markup-processing errors appear.
- Documentation describes the optimization honestly without guaranteeing a
  universal conversion or performance score.
