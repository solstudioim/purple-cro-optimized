# Demo capture checklist

- [x] `01-opening.png` — homepage hero
- [x] `02-home-trust.png` — trust strip and categories
- [x] `03-catalog.png` — catalog with filters and product cards
- [x] `04-product.png` — centered gallery and buy box
- [x] `05-mobile-sticky.png` — narrow sticky add-to-cart state (390x844 source)
- [x] `06-cart-success.png` — aligned success notice and animation end state
- [x] `07-cart.png` — cart summary and checkout action
- [x] `08-upsell.png` — full-page pre-checkout upsell
- [x] `09-downsell.png` — downsell shown after rejecting upsell
- [x] `10-downsell-accepted.png` — accepted downsell in cart
- [x] `11-checkout-contact.png` — enclosed checkout contact and delivery
- [x] `12-checkout-payment.png` — test payment and order action
- [x] `13-order-confirmed.png` — completed test order without customer details
- [x] `14-post-purchase.png` — explicit post-purchase offer
- [x] `15-post-purchase-detail.png` — separate-checkout explanation
- [x] `16-toolkit-valid.png` — valid offer selections
- [x] `17-toolkit-warning.png` — invalid product warning example
- [x] `18-end-card-source.png` — clean repository/source end card input

Safety audit: no credentials, real names, personal email or street addresses, order IDs, browser notifications, unrelated tabs, or live-site interaction.

## Review corrections

- [x] `13-order-confirmed.png` contains only the genuine rendered WooCommerce order-received page; no replacement storefront copy or redaction was injected.
- [x] `11-checkout-contact.png` and `12-checkout-payment.png` visibly identify the temporarily relabeled gateway as local demo/test payment with no external processing.
- [x] `16-toolkit-valid.png` visibly shows valid upsell ID 13 and downsell ID 15 together.
- [x] Gateway settings, valid offer IDs, pre-checkout placement, and the isolated cart were restored and read back after capture.
