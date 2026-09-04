# Native checkout-content editor — 0.9.0

Verified on 2026-09-04 against the local `purple-optimize.test` installation
(WordPress 7.1, WooCommerce 11.0.0, Commerce Clarity theme).

## Implementation

- WooCommerce → Purple Optimize → Checkout add-ons and helpful content →
  **Open block editor** opens the standard WordPress post editor.
- A non-public `pot_checkout_info` document uses core editor, media, REST saves,
  autosaves, and revisions. Editing requires `manage_woocommerce` (including shop managers).
- The existing enable checkbox and order-summary placement are retained.
- The original option content is retained as a pre-migration fallback/backup.
  Opening the editor copies it once; a published legacy copy remains visible.
  A new empty document starts as a draft. Thereafter only its published,
  non-password-protected version is displayed; missing/private/trashed content is hidden.
- WordPress renders the supported content blocks. Their assets are collected
  on the checkout page before the head, not only inside Store API responses.
- Editor and checkout share scoped typography. Core image, list, button,
  group, column, spacer, and explicit text-style settings remain available.
- The server enforces the content-block list recursively before callbacks run,
  removes data bindings, and sanitizes rendered HTML. No forms, checkout blocks,
  shortcodes, or external embed execution are introduced.

## Verification

- `node --test tests/*.test.mjs`: 55 committed source/contract tests pass
  (56 with the existing uncommitted local test).
- `php tests/storefront-presentation-contracts.php`: existing PHP contracts.
- `wp eval-file tests/checkout-content-integration.php`: 15 checks; legacy preservation,
  native rendering, unpublished/password protection, nested callback blocking,
  HTML sanitization, data-binding exclusion, capabilities, REST write protection,
  and scope isolation. Temporary documents/options are restored.
- `tests/checkout-content.e2e.mjs`: real native editor and Save button; save/reload
  with valid blocks, existing Media Library image, lists, group layout/gap,
  columns, custom color/type/alignment, shared bold/default text color, mobile
  overflow, disabled state, invalid nonce, and checkout JavaScript errors.
- Existing checkout-add-on integration/browser tests: product selection,
  live totals, removal, retained customer details, unavailable products,
  disabled features, error recovery, and settings preservation.

No orders or payments were submitted. Temporary test products/documents and
login sessions were removed; original settings were restored after each test.

## Installed content migration

The saved Elementor text wrappers were converted into one core heading and eight
core paragraphs. Text preservation was checked before saving and against actual
checkout output. The original settings copy remains unchanged. No images existed
in that saved HTML, so none were invented or added during migration.

Screenshots are local artifacts in `artifacts/checkout-block-editor/`:
`actual-editor.png`, `actual-checkout-desktop.png`, `actual-checkout-mobile.png`.
These demonstrate local behavior, not production deployment or cross-theme certification.
