# Alessi-Inspired Premium CRO Styling Design

Date: 2026-08-09
Status: Approved direction; implementation pending

## Goal

Refine Purple CRO Optimized into a simpler, premium storefront with a clear purchase hierarchy while preserving every existing CRO capability and the completed performance work.

The direction takes cues from Alessi's reported ecommerce redesign: strong product imagery, restrained interface styling, and a friction-light buying journey. Shopify reports a 109% conversion-rate improvement and 210% revenue growth for that broader migration and redesign. Those results are context, not a forecast or evidence that styling alone caused the improvement.

Baymard guidance is the decision framework: make the primary purchase action unmistakable, reduce competing product-page signals, preserve category hierarchy, and keep checkout focused.

Research references:

- [Shopify: Alessi case study](https://www.shopify.com/uk/case-studies/alessi)
- [Baymard: Ecommerce UX audit and product-page guidance](https://baymard.com/learn/ecommerce-ux-audit)
- [Baymard: Checkout flow optimization](https://baymard.com/learn/checkout-flow-ux-optimization)
- [Baymard: Ecommerce category-page design](https://baymard.com/learn/ecommerce-category-page)
- [Baymard: Mobile ecommerce design](https://baymard.com/blog/mobile-commerce-design)

## Design Principles

1. Products lead; interface decoration recedes.
2. One dominant purchase color and one clear primary action per decision area.
3. Urgency, proof, reassurance, and savings remain available but never compete at equal visual weight.
4. Checkout removes unrelated navigation while preserving a clear logo escape path and access to help.
5. Improvements remain CSS-first and add no frontend libraries, fonts, animations, or image payload.
6. Existing CRO settings and features remain configurable; this work changes presentation and hierarchy, not merchant capability.

## Visual System

- Canvas: warm white with white content surfaces.
- Text: near-black primary text and muted neutral secondary text.
- Brand: retain deep purple `#5b21b6` and dark purple `#3b0764` for identity, navigation states, links, and restrained accents.
- Purchase action: retain orange `#c2410c`, with `#9a3412` hover/focus treatment, exclusively for Add to cart, checkout, Place Order, and offer acceptance.
- Feedback colors: green for confirmed success and red only for genuine errors or material low-stock warnings.
- Corners: tighten general controls and cards to approximately 4–8px.
- Shadows: remove decorative card shadows. Use only a subtle shadow where elevation communicates behavior, such as a menu, overlay, or sticky action.
- Motion: remove card lift, button translation, pulsing urgency, and nonessential animation. Live countdown seconds continue to update without pulsing.
- Controls: primary controls are at least 48px high; all interactive mobile targets are at least 44px.
- Focus: every interactive control receives a visible keyboard focus treatment with adequate contrast.

## Global Storefront Chrome

- Replace the purple-to-orange promotional gradient with a solid dark-purple strip and no shadow.
- Use a plain white category navigation row with a single divider and restrained active/hover states.
- Exclude WooCommerce's configured default product category from the category navigation, rather than relying on its label being `Uncategorized`.
- Keep the existing brand/site identity and information architecture.
- Remove competing gradients, thick outlines, oversized radii, and decorative shadows from CRO components.
- Use one consistent orange purchase-action treatment across home, catalog, product, cart, checkout, and offer views.

## Homepage

- Keep a product-led hero with stronger image emphasis.
- Shorten hero copy to one concise value proposition.
- Present one orange primary CTA. Render any secondary destination as a quiet text link rather than a competing filled button.
- Keep promotional content secondary to product discovery.
- Preserve existing homepage functionality and media; do not introduce new image downloads in this phase.

## Catalog and Product Cards

- Use a consistent 4:5 image area so grids scan cleanly despite differing source assets.
- Prefer borderless cards or a minimal divider over boxed, elevated cards.
- Remove hover lift and heavy shadow; use a subtle image or color response only if it does not trigger layout movement.
- Keep title, price, review signal, and quick add in a compact, predictable hierarchy.
- Keep quick add visually secondary until the shopper interacts, but retain the orange purchase treatment.
- Preserve the existing post-add `View cart` link behavior.
- Display one sale signal. When the toolkit can calculate a percentage, replace the native sale text with one `Save N%` badge; otherwise retain WooCommerce's native sale badge.

## Product Page

- Preserve the large gallery and give it primary visual weight.
- Tighten the buy box so title, price, savings, variation/quantity controls, and Add to cart read as one decision group.
- Use a full-width orange Add to cart button on the buy-box column where layout permits.
- Show one savings chip and one stock message.
- If the toolkit renders its configured low-stock warning for a managed-stock product at or below the threshold, suppress only the redundant native availability sentence on that single-product view. Normal availability, backorder, and out-of-stock information must remain present and accessible.
- Replace the toolkit buy-box gradient and shadow with a white surface and simple dividers.
- Restyle the live countdown as a compact, high-contrast information row. Hours, minutes, and animated seconds remain live; pulsing and decorative timer shadows are removed.
- Present reassurance as compact icon-and-text rows, not separate green cards.
- Present the featured review as a plain quotation with a restrained purple rule.
- Keep the wishlist as a secondary outline/text action.
- Keep photo attribution available without competing with purchase information.
- Keep mobile product sticky Add to cart disabled, per the existing product-page decision.

## Cart

- Keep the standard store navigation and the existing shipping/policy guidance on the cart page.
- Maintain one native Proceed to checkout action. On mobile, make that existing action sticky through CSS; never render a duplicate checkout button.
- Simplify the free-shipping progress and trust guidance to flat surfaces, subtle borders, and concise copy.
- Preserve coupons, cart editing, totals, cross-sells, and all native WooCommerce behavior.

## Enclosed Checkout

- Add a checkout-specific body state for the active checkout form only. Exclude order-received, order-pay, and standalone offer-funnel views unless their own template explicitly calls for the state.
- On active checkout, hide the promotion strip, category navigation, main menu, search, account/cart utilities, and footer link farm.
- Keep a clickable site logo/title as the escape path and provide one discreet help/contact access point when configured.
- Preserve the checkout form, required/optional labels, validation, payment methods, order summary, coupons, the configurable inline offer, legal consent, and native Place Order button.
- Maintain one native Place Order action. On mobile, make that existing action sticky through CSS without cloning it.
- Do not relocate cart-oriented shipping/policy guidance to the checkout header.
- Ensure hidden chrome is not merely visually obscured while remaining keyboard-focusable.

## Upsell and Downsell Funnel

- Preserve all configured placements: before checkout/purchase, before Place Order, and post-purchase where supported.
- Preserve manually selected upsell and downsell products, conditional hiding after addition, one-time discount presentation, yes/no routing, and normal order completion.
- Simplify the full-page offer to a white, product-led two-column composition with a subtle boundary.
- Use orange for acceptance and a quiet outline/text treatment for rejection; both must remain unambiguous and keyboard accessible.
- Keep the offer countdown live but visually restrained.
- Remove radial gradients, oversized radii, and heavy card shadows.

## Social Proof, Wishlist, and Supporting CRO

- Preserve opt-in recent-purchase social proof and its current frequency/configuration. Do not imply fabricated real-time activity or increase interruption frequency.
- Restyle social proof as a compact neutral notification with a subtle accent and clear dismissal.
- Flatten wishlist cards and supporting notices; remove lift effects and decorative shadows.
- Preserve all current settings, tracking hooks, fallback behavior, and accessibility announcements.

## Responsive and Accessibility Requirements

- Verify home, catalog, product, cart, checkout, and offer views at representative desktop and mobile widths.
- Prevent horizontal overflow and keep price, quantity, variation, coupon, and payment controls usable at 320px width.
- Maintain at least 44px touch targets, visible focus, semantic headings, descriptive control labels, and reduced-motion support.
- Sticky mobile cart and checkout actions must not cover validation errors, notices, payment controls, cookie controls, or the final page content.
- Reflow the offer page to image, offer summary, acceptance, then rejection on narrow screens.

## Implementation Boundaries

Primary files:

- `wp-content/themes/purple-optimize/style.css`: visual tokens and theme-level home, catalog, product, cart, checkout, and responsive styling.
- `wp-content/themes/purple-optimize/functions.php`: narrowly scoped theme classes or markup hooks if needed.
- `wp-content/plugins/purple-optimize-toolkit/assets/toolkit.css`: flat CRO component presentation and offer/social-proof styling.
- `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`: default-category exclusion, single sale badge behavior, checkout body state, and scoped stock-message coordination.
- `wp-content/plugins/purple-optimize-toolkit/templates/offer-funnel.php`: only the minimal structural changes needed for the approved two-column hierarchy.
- Existing tests plus targeted contracts for new markup/body-state behavior.

Version numbers and changelog/readme entries will be updated during implementation, not as part of this design-only commit.

## Performance Constraints

- No new JavaScript framework, CSS framework, icon library, webfont, animation library, remote API call, or third-party runtime.
- No new storefront image payload in this phase.
- Prefer existing markup, CSS custom properties, and server-rendered classes.
- Avoid layout shifts: reserve media dimensions and do not insert late-loading promotional UI above primary content.
- Keep selectors scoped and avoid broad DOM observers or new timers. The existing visible countdown is the only continuing second-level timer behavior.
- The performance work recorded in the existing baseline and follow-up report must not regress.

## Non-Goals

- Reproducing Alessi, Shoptimizer, or any other storefront pixel-for-pixel.
- Claiming the published Alessi results as an expected outcome.
- Removing or redesigning CRO settings and business logic.
- Replacing WooCommerce cart or checkout actions with custom duplicates.
- Adding product-page mobile sticky Add to cart.
- Adding new photography, a new font, or a JavaScript animation layer.

## Acceptance and Verification

1. Capture before/after desktop and mobile screenshots for home, shop/category, product, cart, checkout, and the full-page offer.
2. Confirm the visual hierarchy uses one orange purchase action and no CRO gradients or decorative card shadows.
3. Confirm only one sale badge and one stock message appear in the applicable product state.
4. Confirm the default product category is absent from the category navigation.
5. Confirm checkout is enclosed while logo/help, form, order summary, inline offer, legal consent, and the single native Place Order action remain functional and keyboard accessible.
6. Confirm cart and checkout each expose one native sticky mobile action, while product pages expose no sticky mobile Add to cart.
7. Exercise upsell/downsell yes/no routing, discounts, product removal/hiding rules, and order completion.
8. Run PHP syntax checks, JavaScript syntax checks, existing contract tests, new targeted tests, and `git diff --check`.
9. Inspect browser console/network output for errors, unexpected third-party requests, layout shifts, and duplicate controls.
10. Repeat the existing performance measurement method and compare before/after asset size and storefront metrics. Treat statistically noisy single-run changes as directional only.
