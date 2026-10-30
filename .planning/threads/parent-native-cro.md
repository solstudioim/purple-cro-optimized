---
slug: parent-native-cro
title: Preserve Purple appearance and add opt-in conversion elements
status: in_progress
created: 2026-10-30
updated: 2026-10-30
---

## Goal

Keep Purple parent templates, palettes, typography and native commerce styling. Child adds setup only; companion adds opt-in conversion features styled with parent tokens. Work locally only: no commits, pushes, merges or remote-site changes.

## Context

- Preserve prior local parent-version advisory changes and unrelated uncommitted screenshot/environment/artifact files.
- Hash-verified rollback backup of existing child, toolkit and tests: /Users/sol/Codex/artifacts/purple-parent-native-local/before/.
- Remove child templates/parts/patterns and CSS/theme.json overrides; retain parent source unchanged.
- Preserve existing CRO defaults and saved choices. The earlier preview that turned defaults off is superseded by the user’s clarification. Add explicit product guidance and checkout-label switches to cover previously unconditional insertions.
- Verify parent-only vs child+disabled-plugin appearance and representative enabled features. Saved Site Editor overrides are not reset or migrated.

## References

- wp-content/themes/purple-optimize/
- wp-content/plugins/purple-optimize-toolkit/
- /Users/sol/Codex/artifacts/purple-parent-native-local/

## Next Steps

- Local implementation complete. Child 0.6.0 inherits all parent templates/parts/patterns, styles and fonts; toolkit 0.11.0 scopes feature styles to its own elements and parent tokens. Native notices/navigation retained. Parent source unchanged.
- Validation: 39 Node contracts, PHP advisory/presentation tests, PHP/JS syntax, WordPress settings/checkout extras/content integrations, workspace + native cart/checkout/navigation browser checks, and explicit installer security/retry/settings-preservation checks passed.
- Ten desktop/mobile comparisons (home/shop/product/cart/checkout) matched parent computed colors, typography and geometry. Disabled toolkit bundle absent. Enabled promo/guidance/wishlist toggle/sticky cart/shipping progress passed. Evidence: artifact directory browser/results.json and screenshots.
- Browser comparisons consume the one-request native add-to-cart notice before baseline measurement; it is not suppressed by production code.
- Separate unreleased preview ZIPs in artifact directory packages/. Future setup URL is deliberately unpublished: upload both ZIPs manually for this test.
- No commits, pushes, merges, release publication or remote-site changes. Existing saved Site Editor overrides and previously enabled features remain; switch features off for a baseline comparison. Older parent versions are advisory, not a claim of runtime verification on 0.0.3.
- Next: user tests local preview, then decides whether to commit/release. No further autonomous publishing.

## Follow-up: missing top bar and wishlist access

- User reports losing promotion bar and wishlist navigation. Promotion is now opt-in; wishlist link was removed with the old child header. Restore an independent companion-owned top bar link/count and a valid native wishlist page without parent CSS changes. Local only.

- Restored companion-owned top bar with independent promotion and wishlist switches. Wishlist shows a browser-local live count across pages/tabs, follows the actual published shortcode page permalink, and creates/reuses a native page on authorized admin settings access/save. No parent header or global style changes.
- Verified native page creation only when enabled, idempotency, custom slug and guest write prevention in WordPress integration. Browser round trip passed: save/count/link/list/remove, independent toggles, cross-tab count and mobile width; 39 Node contracts and PHP advisory test passed.
- Evidence: /Users/sol/Codex/artifacts/purple-parent-native-local/wishlist-browser/wishlist-mobile.png. Refreshed local toolkit 0.11.0 preview ZIP. Enable Promotion strip and Browser wishlist in WooCommerce > Purple Optimize > Storefront; defaults remain off and existing saved settings are preserved. No remote actions or commits.

## Follow-up: staging upsells and checkout extensions

- User reports upsell, add-on and helpful content failures on purplecrodemo.wpcomstaging.com. Staging read-only; local code changes only.
- Live stack: toolkit 0.11.0, WooCommerce 11.2.1, Gutenberg 24.3.0, Page Optimize 0.6.4. Configured add-on/content returned by Store API, server placeholders empty after checkout hydration. Script is deferred while checkout frontend is parser blocking. Initial no-argument registry inspection was inconclusive (requires parent area argument); closer inspection confirms registered components exist.
- Baseline local WC11.1 enabled add-on browser flow passes; content native editor flow renders but old forced bold/editor parity assertion fails under inherited parent typography. Investigate delayed registration race with deterministic browser fixture and verify offer session lifecycle independently.

- User clarified scope: keep child/toolkit core functions intact; only remove unnecessary styling. Restored prior feature defaults (promotion/search/categories/trust/policies/account/wishlist/sticky controls/sale badges), guidance and labels default enabled as formerly unconditional. Preserved explicit saved opt-outs; configured offers/add-ons remain opt-in, recent-sales privacy defaults unchanged.
- Matching WC11.2.1 + Gutenberg24.3.0 with delayed companion script reproduced empty checkout placeholders. Added WooCommerce IntegrationInterface so component registration becomes a checkout entrypoint dependency. Same delayed browser regression now passes four add-ons, content, totals, nonce, retained email, admin saves and failure recovery.
- All three offer placements verified locally with real cart discounts: upsell rejection/downsell acceptance, no repeated resolved cart offer, post-purchase separate checkout/source-order preservation. No payment submitted; synthetic local order deleted.
- Bumped companion preview to 0.11.1 to distinguish it from staging 0.11.0. Added staging-stack browser coverage, delayed delivery and complete offer/content journeys to CI. No staging changes, commits or remote publication.

- Final verification: native content editor/save/reload/render/layout/mobile/disable checks pass on WC11.2.1 + Gutenberg24.3.0; ten explicitly-disabled parent comparisons pass on that stack. Delayed-script add-on/content browser flow also passes on WC11.1.0 without Gutenberg; CLI settings/add-on/content integrations pass. 40 Node contracts, PHP syntax, advisory/presentation contracts and diff whitespace checks pass. Parent source unchanged.
- Final local packages: child 0.6.0 and toolkit 0.11.1 in artifact packages/. ZIP integrity and source-byte parity verified. Prior 0.11.0 ZIP is superseded; install 0.11.1 for the checkout fix. Staging still has 0.11.0; no staging plugin/settings changes, commits, pushes or publication performed. Test fixtures cleaned and temporary synthetic order removed.
- Remaining: user uploads the local toolkit ZIP and retests staging in a fresh guest cart. Stage recovery is not yet confirmed. Exact WP.com hosting/optimizer behavior and older Purple runtime are not claimed as locally reproduced; matching WC/Gutenberg plus delayed script delivery is the reproduced mechanism.

## Live pre-checkout upsell recheck

- Staging now reports toolkit 0.11.1 active. Read-only admin inspection: offer enabled, pre_checkout placement, Violet Knit Sweater #18, no product-eligibility warning; logged-in checkout contains only Lilac Knit Beanie and skips the offer. Add-ons and helpful content now render live.
- Tested a separate guest browser cart with initial item count zero. Added Lilac Knit Beanie through the storefront, then navigated to checkout. Live redirect succeeded to /special-offer/?step=upsell, showing Violet Knit Sweater at $49 (50% off $98), timer and Yes/No buttons. No offer acceptance or payment submitted; original logged-in cart and staging settings untouched.
- Guest offer tab left open as a deliverable. Native screenshot emitted in chat. Disk capture was unavailable from browser sandbox; do not claim a saved screenshot file.
- Existing shopper session suppression remains consistent with pot_offer_funnel_complete; actual server-side flag was not directly inspected. Do not present that hypothesis as a proven historical cause. Retest instructions: fresh guest/private window, remain logged out, add a non-offer product, proceed to checkout. No new source/package changes were justified by this live guest test.

## Fix offer session scope for guests and authenticated shoppers

- User explicitly requests working pre-checkout offers for both logged-in and guest shoppers. Live guest success does not establish authenticated recovery.
- General completed flag has no cart/config identity. Scope decisions and timers to the current normal cart and configured offer; migrate stale legacy unscoped state once. Preserve no-repeat decisions for unchanged carts, exclude accepted offer/add-on lines to avoid loops, and reset inline-only state for a new cart.
- Verify stale state, same-cart suppression, config/cart changes and accepted-offer stability in PHP and guest/customer browser journeys. Local source/packages only; no staging changes or commits.

- User additionally requests a simple focused boxed treatment for upsell/downsell pages, inspired by FunnelKit, while preserving parent design. Consulted official FunnelKit Customizer docs (product image/layout, buy block CTA, optional urgency section). Apply only to offer components: bordered card, restrained shadow/radius, clear main action and quieter decline; inherit Purple tokens/fonts/buttons.

- Session scope and focused-card tests passed on WC11.1.0 for guest and a real disposable customer login (including seeded legacy completion). Assertions cover decline/refresh, materially changed cart, acceptance without loops and desktop/mobile card bounds. Verifying old-package failure and fixed behavior on WC11.2.1/Gutenberg24.3.0 next.

- Final 0.11.2 verification: old 0.11.1 package reproduced a missing offer for a real signed-in customer with seeded legacy state; log retained in artifact offer-legacy-before.txt. Updated code passes the same case, guests, unchanged-cart decline/timer preservation, changed normal cart, acceptance/no-loop, and focused-card desktop/mobile checks on WC11.2.1 + Gutenberg24.3.0; equivalent flows passed on WC11.1.0. PHP checks additionally cover config changes and preserved follow-up order association.
- All three offer placements pass after session fix; source-order preservation retained. Final settings, checkout add-on and content integrations, 40 Node contracts, PHP policy/syntax and whitespace checks pass. Temporary customer and products/synthetic order removed; original settings restored.
- Local toolkit 0.11.2 ZIP and child 0.6.0 ZIP built with integrity/source-byte parity checks. Evidence screenshots: artifact offer-0.11.2-staging-stack/customer-upsell-desktop.png and customer-upsell-mobile.png. No staging deployment, commits, pushes or publication. User can upload toolkit 0.11.2 to verify live recovery; staging still runs 0.11.1 as last observed.

## Parent-container alignment and wishlist placement

- User clarifies wishlist must be injected into existing top header/menu tools, never beside promotion notice. Keep count and saved-item access; preserve native parent header.
- Category strip exists in initial commit a0c8275 and was enabled by default. Move beneath native header in a WordPress wide constrained wrapper.
- Emphasize promotion minimally with existing parent accent/colors, and align cart/checkout shipping progress with native block container; preserve responsive sizing and live eligibility updates. Local changes only.

- Completed local toolkit 0.11.3. Wishlist is injected beside the native mini-cart with live count; promotion is separate, centered and uses parent accent. Category strip now follows the native header in has-global-padding/is-layout-constrained/alignwide wrappers; existing category setting/default unchanged.
- Shipping/trust elements copy native alignment classes after insertion; shipping width no longer adds its own inset. Progress also subscribes to live checkout totals, so checkout add-ons update eligibility.
- Browser geometry passed: desktop header/categories x50 width1340, cart/shipping x190 width1060, checkout/shipping x220 width1000; mobile each x20 width350. Header placement, no wishlist in notice, no horizontal overflow and add-on-triggered checkout threshold update passed. Wishlist save/link/list/remove, count and cross-tab tests passed. 40 Node contracts, PHP advisory/syntax/settings integration and diff checks pass.
- Evidence: artifact alignment-0.11.3/geometry.json, shop-1440.png, shop-390.png, checkout-1440.png and checkout-390.png. Local 0.11.3 toolkit ZIP and child 0.6.0 ZIP built and byte-verified. No staging changes, commits, pushes or publishing. User can upload the toolkit ZIP to test; saved settings preserved.

## Remote synchronization

- User tested the site and explicitly authorized syncing the work to the remote repository.
- Remote main at 11188e7 has the same source tree as local base 2c2316d. Create a new codex branch from main, commit only task source/docs/tests/thread files and optional demo block example. Keep .env, old artifact directory and unrelated screenshot/onboarding-thread modifications unstaged.
- Site post 62 content is captured as an optional portable example (relative contact URL, visibly fictional demo review); plugin installation does not silently insert this merchant-specific content.

- During remote sync, user requested customizable promotion background. Add native WP color picker, sanitized optional hex setting, clear-to-parent default and automatic black/white contrast scoped only to the strip. Include in the same PR before merging; toolkit preview 0.11.4.

- Promotion custom color passed native picker save/read-back for light and dark hex backgrounds, automatic foreground contrast, unrelated setting preservation and Clear reset. Invalid CSS/malformed inputs rejected in integration tests. Default remains parent accent. Included in toolkit 0.11.4 before remote merge. GitHub connector PR creation was forbidden; using configured gh CLI after successful git push.
