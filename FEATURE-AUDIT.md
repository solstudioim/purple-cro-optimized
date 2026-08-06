# Purple CRO Optimized feature audit

Audit date: 2026-08-06

Purple CRO Optimized combines common ecommerce conversion patterns with the
[Baymard 16-item CRO framework](https://baymard.com/learn/ecommerce-cro).
“Implemented foundation” means the theme or companion plugin supplies the
behavior; it does not claim a guaranteed conversion lift for every audience.

## Common ecommerce CRO features

| Capability | Status | Implementation |
| --- | --- | --- |
| Promotion and coupon strip | Included | Configurable promotion copy and coupon-code copy action. |
| Product autocomplete | Included foundation | Category scope, SKU lookup, bounded typo tolerance, keyboard traversal, and no-results feedback. |
| Sale presentation | Included | Percentage savings calculated from real catalog prices. |
| Product gallery | Included | Native Purple/Woo gallery plus two curated open-media images per demo product. |
| Product reviews near purchase action | Included | Real average, count, anchor, and featured approved review. |
| Sticky product action | Included on larger screens | Product, image, price, and purchase action; deliberately disabled on mobile. |
| Native mobile cart and checkout actions | Included, configurable | CSS repositions WooCommerce's existing buttons without rendering duplicates. |
| Low-stock messaging | Included | Uses managed WooCommerce inventory only. |
| Sale and offer countdowns | Included | Animated days, hours, minutes, and seconds are shown only for configured, time-bound sale or offer data. |
| Free-shipping progress | Included | Uses live cart subtotal and a configured threshold. |
| Wishlist | Included foundation | Browser-based guest wishlist with a dedicated page. |
| Upsell and downsell | Included | Manually selected products, separate discounts/timers, and pre-checkout, inline, or post-purchase placement. |
| Post-purchase offer safety | Included | Acceptance starts a separate checkout and order; the completed order is never silently changed or recharged. |
| Recent-purchase proof | Included, opt-in | Real processing/completed orders only; anonymous by default; names require explicit enablement. |
| Checkout clarity and trust | Included | Progress language, factual reassurance, real policies, and Required/Optional labels. |
| Product-content readiness | Included | Editor checklist for image coverage and clear short/detailed descriptions. |

## Baymard 16-item CRO framework

| # | Baymard recommendation | Status | Purple CRO Optimized implementation |
| --- | --- | --- | --- |
| 1 | Optimize the ecommerce checkout process | Implemented foundation | Focused guest checkout, progress guidance, shipping threshold, one native completion action, and configurable passive/pre/post-purchase offers. The forced pre-checkout mode remains optional and should be tested against the direct flow. |
| 2 | Add trust signals | Implemented foundation | Factual checkout guidance, configured policy links, returns information, and SSL-aware language without fabricated seals. |
| 3 | Clearly display product reviews | Implemented | Average rating, review count, anchor link, featured approved review, and Woo's full review UI use real review records. |
| 4 | Optimize websites for mobile users | Implemented foundation | Responsive Purple layouts, configurable sticky native cart/checkout controls, and sticky offer decisions. Product sticky Add to Cart is intentionally disabled on mobile. |
| 5 | Improve product photography | Implemented demo foundation / content-dependent | Each demo product has a primary image plus two curated, openly licensed gallery photos. Imports validate raster type, dimensions, size, and license; visible credits preserve attribution. Production media must depict the merchant's exact product. |
| 6 | Make the checkout form easy to understand | Implemented foundation | Woo Blocks guest checkout plus a visible Contact → Delivery → Payment sequence and final-total/payment guidance. |
| 7 | Label optional and required checkout fields | Implemented | Rendered checkout inputs are annotated Required or Optional, including fields re-rendered by Woo Blocks. |
| 8 | Be careful with live chat | Implemented safe default | No automatic or interruptive chat popup is added. |
| 9 | Make the website easy to navigate | Implemented foundation | Top-level product categories, real footer destinations, responsive navigation, breadcrumbs, filters, and search. |
| 10 | Put shipping and returns information in the footer | Implemented | Dedicated Shipping & Returns, Privacy, and Terms destinations are rendered in the footer policy bar. |
| 11 | Encourage account creation at the right time | Implemented | Guest checkout remains available; a benefit-led account invitation appears after purchase. |
| 12 | Allow users to search within categories | Implemented | Product-category pages preserve their taxonomy scope in autocomplete suggestions. |
| 13 | Streamline mobile payment | Implemented theme support / gateway-dependent | Responsive checkout and one configurable sticky native Place Order control are included. Express wallets and payment ordering depend on the installed gateway. |
| 14 | Hide coupon and promotional fields behind a link | Implemented | Woo cart and checkout expose coupons through the collapsed Add coupons control. |
| 15 | Make product descriptions clear | Implemented support | Demo products use scannable summaries, specifications, and care information; the editor checklist flags missing content. |
| 16 | Make autocomplete work correctly | Implemented foundation | Nonce-protected suggestions support category scope, SKU lookup, bounded typo tolerance, keyboard traversal, and visible no-results feedback. |

All 16 recommendations are addressed. Fourteen have a theme/plugin
implementation or safe default. Exact product photography and express mobile
payments additionally require merchant media or gateway capability.

## Integrity and privacy

Recent-purchase social proof is only appropriate when it reflects an eligible
real order and the store has a lawful, disclosed basis for displaying customer
information. Production defaults are disabled and anonymous. No random names,
locations, viewer counts, purchases, inventory figures, or arbitrary countdowns
are manufactured.

## Sources

- [Baymard: 16 ecommerce CRO tips](https://baymard.com/learn/ecommerce-cro)
- [Baymard: reduce cart abandonment](https://baymard.com/learn/reduce-cart-abandonment)
- [Openverse API](https://api.openverse.org/)
- [Wikimedia Commons Imageinfo API](https://www.mediawiki.org/wiki/API:Imageinfo/en)
