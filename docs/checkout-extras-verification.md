# Checkout extras — 0.8.0 verification

Verified 2026-09-04 on local `http://purple-optimize.test` with WordPress 7.1,
WooCommerce 11.0.0, PHP 8.4 serving HTTP, PHP 8.1 for integration checks, and
headless Chromium. The existing pre-checkout upsell/downsell funnel remained enabled.

## Passed

- Four independently selectable add-ons above the native Place order action.
- Catalog sale prices, live totals, checkbox/button add and remove, reload
  persistence, and preservation of the entered email address.
- Duplicate acceptance produces one unit; submitted quantity and price cannot
  override server values. Normal cart items cannot be removed by the add-on UI.
- Missing Store API nonce rejected; malformed, unconfigured, disabled, and
  out-of-stock product selections rejected. Real stock-change error displayed;
  retry works after restocking.
- Merchant-authored heading, text, list, and image below the order summary.
- Admin Media Library opens; settings save and read back in admin and storefront.
  Both features can be disabled independently of the existing offer funnel.
- Unsafe HTML removed while safe text/images remain; settings capability is
  `manage_woocommerce` and WordPress Settings API supplies the form nonce.
- Desktop 1440px and mobile 390px layout; no horizontal mobile overflow; one native
  Place order button; no uncaught storefront JavaScript errors.
- Existing Node contracts, PHP presentation contracts, new checkout contracts,
  PHP syntax checks, and whitespace checks.

## Scope and cleanup

- The new features support the WooCommerce Checkout block, not classic shortcode
  checkout. No order or payment was submitted; gateway, shipping-provider, and
  production performance certification are outside this verification.
- Temporary test products were deleted, the short-lived admin session revoked,
  and the prior checkout settings restored. Both new features remain off until
  configured with the merchant's own products and content.
- New plugin files were installed and checked against the repository. Existing
  unrelated uncommitted edits were excluded from the feature commit.

## Reproduce

1. Run `node --test tests/*.test.mjs` and `php tests/storefront-presentation-contracts.php`.
2. On a local `.test` installation, run
   `wp eval-file tests/checkout-extras-integration.php prepare`.
3. Set `POT_WP_PATH` to that installation and `PLAYWRIGHT_MODULE` to an installed
   Playwright module, then run `node tests/checkout-extras.e2e.mjs`.
4. Always run `wp eval-file tests/checkout-extras-integration.php cleanup`, even
   if browser checks fail. The integration runner without `prepare` cleans up
   its own fixtures automatically.

The browser runner currently uses the local macOS PHP/WP-CLI executable paths;
adjust those two paths when running elsewhere.

The cart integration follows WooCommerce's
[Store API cart-update extension flow](https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/extend-store-api-update-cart/).
