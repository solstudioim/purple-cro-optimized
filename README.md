# Purple CRO Optimized

Purple CRO Optimized is a conversion-focused WooCommerce implementation built
on Woo's Purple block theme. It applies common ecommerce CRO patterns and
implements the 16 recommendations in Baymard's ecommerce CRO framework.

**Current child-theme release:** `0.5.12`

**Current toolkit release:** `0.8.0`

**Demo video:** [Watch the complete storefront journey](https://solstudioim.github.io/purple-cro-optimized/)

The project is deliberately split into two packages:

- `wp-content/themes/purple-optimize`: a child theme for presentation and layout.
- `wp-content/plugins/purple-optimize-toolkit`: optional store functionality.

The child theme is built on the
[Woo Purple parent theme](https://github.com/woocommerce/woo-themes/tree/trunk/purple),
created by Automattic and distributed under GPLv2 or later. The canonical
upstream source used for local development is vendored at
`wp-content/themes/purple` from `woocommerce/woo-themes` (`trunk/purple`).

Purple is maintained in WooCommerce's public `woocommerce/woo-themes`
repository. It is not sourced from a WordPress.com repository.

## WordPress.org submission preparation

The directory-ready child-theme package is limited to
`wp-content/themes/purple-optimize`; the optional toolkit plugin is never bundled
inside the theme ZIP. See
[`docs/wordpress-org-theme-submission.md`](docs/wordpress-org-theme-submission.md)
for the packaging command, review checklist, upstream attribution, and the two
items that require confirmation from the Themes Team before submission.

## Included conversion features

- Configurable promotion strip and coupon copy button.
- Category-scoped instant product search with SKU lookup, bounded typo tolerance,
  keyboard navigation, and a nonce-protected WooCommerce endpoint.
- Top-level product-category navigation and real policy/footer destinations.
- Honest percentage sale badges calculated from catalog prices.
- Low-stock messaging based only on WooCommerce inventory.
- Eye-catching sale countdowns with animated seconds, shown only when a real scheduled sale end exists.
- Guest wishlist stored in the visitor's browser, with a wishlist shortcode.
- Free-shipping progress based on the configured threshold and live cart subtotal.
- Sticky single-product add-to-cart bar.
- Prominent real review summaries and an approved featured review near the buy box.
- Product reassurance plus checkout progress, trust/policy guidance, and visible
  Required/Optional field labels.
- Privacy-controlled recent-purchase proof backed only by processing/completed
  WooCommerce orders; anonymous and disabled by default.
- A manually configured upsell and optional downsell with three placements:
  full-page before checkout, passive inline before Place Order, or after purchase.
  Each offer has its own real discount and countdown duration.
- Post-purchase acceptance starts a separate checkout and order; it never mutates
  or silently recharges the completed order.
- Accepted offers are added once, priced securely in the WooCommerce cart, and
  then hidden while the buyer proceeds to the normal checkout.
- Single-product add-to-cart confirmations reveal one clear View cart action
  below the existing purchase button.
- Optional mobile CSS treatment that keeps WooCommerce’s existing cart checkout
  and checkout completion controls fixed in reach without rendering duplicates.
- Sticky product add-to-cart remains available on larger screens and is hidden
  on mobile; offer-page Yes/No decisions remain sticky within the offer page.
- Purple's existing mini-cart, quantity steppers, variation chips, product gallery,
  product filters, ratings, size-chart pattern, related products, and checkout.
- A post-purchase account invitation, seeded local policy pages and an editor-side
  checklist for product photography and description readiness.
- A separate, repeatable `tools/import-open-media-gallery.php` importer adds two
  curated Openverse/Wikimedia Commons images per demo product. It validates
  license, MIME type, dimensions, and file size; stores source metadata; and
  renders visible gallery photo credits on product pages.

The toolkit intentionally does not manufacture purchases, names, viewer counts,
or arbitrary scarcity. Showing real customer first names is an explicit setting
that should only be used with an appropriate privacy basis.

## Premium CRO presentation

The default presentation uses a restrained product-led hierarchy: deep purple
for identity, orange only for purchase decisions, flat CRO components, consistent
4:5 catalog media, one current sale badge, and one factual stock message. The cart
retains policy guidance while the active checkout becomes an enclosed flow with a
clickable store identity and the single native Place Order action.

Product, offer, wishlist, recent-purchase proof, countdown, sticky cart/checkout,
and configurable upsell/downsell capabilities remain intact. Visual restraint does
not remove merchant controls or manufacture urgency.

See [`FEATURE-AUDIT.md`](FEATURE-AUDIT.md) for the Baymard 16-item implementation
matrix and the remaining merchant-content or gateway dependencies.

## Installation

Install the parent theme, child theme, and plugin in that order. Activate
`Purple Optimize`, then activate `Purple Optimize Toolkit`.

Requirements: WordPress 6.7 or newer, PHP 7.4 or newer, and WooCommerce.

After activation, open the toolkit settings at **WP Admin → WooCommerce →
Purple Optimize**. The direct admin path is
`/wp-admin/admin.php?page=purple-optimize`. The menu is available to
Administrators and Shop Managers with the `manage_woocommerce` capability. If
it is missing, confirm that both WooCommerce and Purple Optimize Toolkit are
active.

Use `[purple_optimize_wishlist]` on a page to render the browser wishlist.

### Checkout add-ons and helpful content

In **WooCommerce → Purple Optimize**, scroll to **Checkout add-ons and helpful
content**. This section has its own **Save checkout features** button.

- Enable up to four independent add-ons above the native **Place order** button.
  Search for simple products and optionally replace each headline/description.
  Images and current prices (including real catalog sales) come from the products;
  use the product editor to change images or prices. Nothing is preselected.
- Shoppers can add or remove an extra by checkbox or button. WooCommerce updates
  the order summary and totals without reloading or clearing entered details.
  Repeated acceptance does not increase quantity; products already in the cart
  cannot be duplicated or removed through the add-on control.
- Enable the helpful-content panel to display rich text and images below the
  order summary. Use the visual editor and **Add Media** for headings, lists,
  policy links, and images. Include only claims that accurately describe the store.
- Both features default to off and are independent of the existing offer funnel.
  These additions target the **WooCommerce Checkout block**, not the legacy
  `[woocommerce_checkout]` shortcode. The panel stacks with the summary on mobile.

Verification: `node --test tests/*.test.mjs` and
`wp eval-file tests/checkout-extras-integration.php` on a local `.test` install.
For browser checks, run the integration script with `prepare`, run
`tests/checkout-extras.e2e.mjs` with `POT_WP_PATH` and `PLAYWRIGHT_MODULE` set, then
run the integration script with `cleanup` to remove test products and restore
the previous settings. No payment or order is submitted by these checks.

For a populated development store, run:

`wp eval-file tools/seed-demo.php`

Run the open-media importer only after the demo products exist:

`wp eval-file tools/import-open-media-gallery.php`

The deterministic seeder does not call external APIs automatically. The curated
manifest is stored in `tools/open-media-gallery.json`, so API search ordering
cannot silently change the catalog.

## Performance

The toolkit starts countdown intervals and checkout-placement observers only on
pages where their matching components exist, and disconnects them when no work
remains. The child theme keeps the visible product image eager and high priority
while marking hidden gallery images lazy and low priority without removing
responsive sources or gallery interactions.

Future demo-photo imports are bounded to 1600 pixels, saved at quality 82, and
converted to WebP when the active WordPress image editor supports it. Existing
attachments are not rewritten.

Local before/after evidence is stored in [`docs/performance`](docs/performance).
Those measurements validate this implementation, not production Core Web
Vitals. Production page caching, object caching, image delivery, CDN behavior,
and field performance monitoring remain hosting and deployment responsibilities.

For this local project, every approved iteration is installed and verified on
`purple-optimize.test`; a repository change alone is not treated as the completed
iteration.
