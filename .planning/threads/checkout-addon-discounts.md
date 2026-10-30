---
slug: checkout-addon-discounts
title: Optional percentage discounts for checkout add-ons
status: resolved
created: 2026-10-30
updated: 2026-10-30
---

## Goal

Add an optional per-product checkout add-on percentage discount. Blank or zero preserves current catalog pricing. Keep guest/customer carts, checkout totals and order line values correct.

## Context

Existing add-ons use independent pot_checkout_settings, server-owned product allowlist and Store API cart updates. Apply percentage off current active catalog price (including sale), like funnel offers. Snapshot accepted terms on the tagged add-on line, apply idempotently, do not discount catalog additions, and preserve existing configurations without migration. No live deployment or remote sync requested for this feature.

## References

- wp-content/plugins/purple-optimize-toolkit/includes/checkout-extras.php
- wp-content/plugins/purple-optimize-toolkit/assets/checkout-extras.js
- tests/checkout-extras-integration.php
- tests/checkout-extras.e2e.mjs

## Next Steps

- Complete locally in toolkit 0.11.9. User uploads the new toolkit ZIP for staging testing; no live deployment or remote sync performed.

## Outcome and verification

- Optional integer 0–100 percentage per checkout add-on. Blank remains blank on save; zero or legacy missing field means no extra discount. Positive values apply to the active catalog price (including sale), using a server-created accepted base/percentage snapshot only on tagged add-on cart lines.
- New cart pricing hook is idempotent and clones each line product to prevent shared-object leakage. Native tax-aware offer display, discount label, cart details and order metadata match accepted pricing. Existing normal catalog/funnel lines are not converted; removal preserves normal additions. Config changes preserve already accepted terms; remove/re-add uses current configuration.
- Existing add-ons without discount metadata keep native pricing. Invalid/incomplete price snapshots do not advertise/apply a discount. Client-supplied price/percentage/quantity cannot set the discount.
- PHP tests passed for blank/zero/bounds/malformed values, sale price basis, duplicate add/recalculation, ordinary-line isolation, config stability, session restoration, real order line values/meta, coupon calculations and an isolated 10% tax class. Test cleanup method corrected to the actual WC_Tax::delete_tax_class_by API and all new fixtures cleaned.
- Browser tests passed for real guest and customer login flows: 15 -> 12 at 20%, totals 112/127/115 across selection/removal, refresh persistence, blank/zero at 15, no mobile overflow, discount-field admin save/read-back and blank clearing verified independently through WP-CLI. Existing delayed-hydration checkout add-on/content/admin flow passed. Existing settings integration and all 40 Node contracts pass; syntax and whitespace checks pass.
- Updated the old source contract forbidding all set_price calls: cart-only discounting is now intentional. Retained its ban on catalog/post writes; integration independently proves original catalog price remains 15.
- Evidence/packages: /Users/sol/Codex/artifacts/purple-checkout-addon-discounts/. Synthetic order, coupon, tax class/rate, customer and checkout products removed. Saved add-on/general settings restored by the browser fixtures. Parent source unchanged. No payment submitted, live-site edits, commit or push for this feature.

## Consistent saving labels

- Live Chrome read-only check confirmed 124 -> 117.80 on the Colorblock Knit Cardigan; add-on card says 5% off and native summary says Save $6.20 (currency, not 6.2%). Native item details separately show Checkout add-on discount: 5% off. No duplicated percent label inside the live add-on card.
- User requests consistent display. Use native Save currency amount on the add-on card, retain configured extra percentage only in native item details, and compare the same regular/current prices as Woo's summary (including catalog sales). Use Woo Store API MoneyFormatter before subtraction to match minor-unit rounding. Pricing/accepted snapshot/totals unchanged; local patch only.

- User explicitly selected “5% off · Save $6.20”, then clarified the existing native order summary must stay as before. Final scope: one combined card label, unchanged native summary and configured extra-percent item detail. Card percentage and amount share the native regular/final rounded-price basis, so an existing catalog sale plus extra discount is represented as the truthful overall saving. No Woo summary filters, native markup changes or live-site writes.

- Final preview 0.11.10 verified locally. Add-on card uses one “% off · Save currency” label; native summary and Checkout add-on discount item detail are unchanged. Exact 124 -> 117.80 case confirms 5% and 6.20; 0/2/3 currency-decimal rounding checks passed. Existing sale example 20 -> 15 then extra 20% -> 12 truthfully shows overall 40% off / Save 8 on card, matching native summary Save 8; configured extra 20% remains identified in native add-on detail.
- Guest/customer browser checks assert card saving equals the native summary badge amount and only one combined label exists per card; cart prices/totals, session reload and admin saves still pass. All 40 Node contracts, PHP/JS syntax and whitespace checks pass. Evidence/package: /Users/sol/Codex/artifacts/purple-discount-label-consistency/. No native summary filters, changed prices, live writes or remote sync.

## Release and freeze — 2026-10-30

- User confirmed 0.11.10 works and authorized committing and merging the companion changes into remote main.
- Include preceding locally tested review/testimonial features and checkout add-on discounts that form this version. Preserve unrelated working-tree changes.
- Development is frozen pending upstream Purple developer feedback. No further features or styling work until the maintainer reopens development.
- References: README.md feedback baseline; companion 0.11.10; child 0.6.0.
- Next step: collect upstream feedback; do not deploy or publish release assets as part of repository sync.
