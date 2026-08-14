# Purovitalis-Inspired Storefront Redesign

Date: 2026-08-14
Status: Approved direction; Human Tonik CRO additions incorporated; awaiting final written-spec review

## Goal

Replace the current Alessi-inspired presentation with a cohesive, conversion-focused storefront informed by Purovitalis across the homepage, product archives, single products, cart, and checkout. Preserve Purple identity, real store content, WooCommerce behavior, accessibility, performance work, and all existing toolkit capabilities.

Purovitalis is a structural and visual reference only. Its branding, copy, imagery, clinical claims, subscription model, and distinctive assets will not be copied.

Human Tonik is a secondary reference for persuasion architecture: contextual proof, quantity economics, objection handling, long-page purchase anchors, comparison content, and risk reversal. Its visual density, health claims, pressure tactics, branding, and content are not references to reproduce.

## Selected Approach

Use **Purovitalis structure with Purple identity**:

- Adopt its calm editorial rhythm, product-led layouts, progressive disclosure, trust placement, and focused purchase hierarchy.
- Retain Purple's brand palette and existing product content, with orange reserved for purchase actions.
- Replace prior presentation rules where they conflict with this design rather than layering another theme treatment on top.
- Preserve existing CRO features and their truthful data requirements.
- Use Human Tonik's strongest direct-response patterns selectively beneath the calmer Purovitalis-inspired visual system.

Alternatives rejected:

1. **Cosmetic reskin only:** faster, but would leave the page architecture fragmented and miss the reference site's strongest conversion patterns.
2. **Close visual reproduction:** creates brand and content mismatch, risks copying distinctive expression, and would import supplement-specific assumptions that do not fit Purple.

## Experience Principles

1. Each viewport has one obvious next action.
2. Product benefits appear before supporting detail.
3. Trust is placed beside the decision it supports, not collected in decorative badge walls.
4. Product discovery is organized around shopper intent and category, not promotional clutter.
5. Long-form content uses progressive disclosure and alternating editorial sections.
6. Cart and checkout become progressively quieter as purchase intent increases.
7. Scarcity, savings, reviews, and social proof remain factual and data-backed.
8. Persuasion deepens as the shopper moves down the page; it does not make the first viewport noisy.
9. Guarantees and reassurance never promise more than the linked policy provides.

## Visual System

- Canvas: warm off-white with white decision surfaces and softly tinted editorial bands.
- Brand: Purple's deep purple remains the identity color for navigation, headings, links, and section accents.
- Purchase actions: orange remains exclusive to Add to cart, Proceed to checkout, Place Order, and accepted offers.
- Typography: use the existing local font system; create an editorial hierarchy through scale, weight, line length, and whitespace rather than adding webfonts.
- Geometry: restrained 6–12px radii; avoid pill-shaped containers except compact tags or selectors.
- Elevation: borders and tonal contrast first; shadows only for sticky or floating behavior.
- Media: preserve stable aspect ratios and existing responsive image behavior.
- Motion: subtle state transitions only, with reduced-motion support.

## Global Header and Footer

- Use a slim factual announcement strip followed by a clean white header.
- Keep the brand prominent and navigation legible; simplify competing utility treatments.
- Preserve search, account, and cart access while reducing their visual weight.
- Organize category discovery clearly on desktop and mobile without duplicating destinations.
- Use a substantial but orderly footer for shopping, help, policies, and brand context.
- Active checkout retains only linked store identity and discreet help access.

## Homepage

The homepage follows a deliberate conversion sequence:

1. **Hero:** concise value proposition, one primary CTA, one quiet secondary route, and dominant product/lifestyle imagery.
2. **Trust strip:** three or four factual promises such as shipping, returns, craft, support, or secure payment.
3. **Shop by category:** visual category cards that let shoppers self-segment quickly.
4. **Featured products:** compact product cards with image, title, short benefit line when available, price, rating, and clear purchase route.
5. **Brand story:** editorial image-and-copy section explaining provenance, materials, craft, or product philosophy.
6. **Proof:** verified reviews or testimonials with restrained presentation.
7. **Supporting discovery:** complementary collection or educational content only when real content exists.
8. **FAQ:** concise purchase-objection handling.

The homepage may include one concise comparison or differentiation section when the merchant has maintainable, factual comparison data. It must not become a competitor-attack page or repeat the same proof already shown above.

Existing content remains the source of truth. Missing merchant content produces an intentionally simpler section rather than invented claims.

## Product Category and Shop

- Introduce a compact editorial archive header with title, optional description, and breadcrumbs.
- Make filters and sorting easy to find without visually dominating products.
- Use consistent media ratios and a responsive grid optimized for scanning.
- Product cards show one promotion signal, product name, concise benefit or category context where available, rating, price, and one clear action.
- Remove decorative hover lift and excessive borders; use subtle image or text-state changes.
- Preserve category-scoped search, native filtering, pagination, variation behavior, wishlist, sale calculations, and post-add cart access.
- On mobile, prioritize products first and keep filter/sort controls compact and reachable.

## Single Product

### Primary decision area

- Use a large gallery beside a compact, sticky-capable buy box on desktop.
- Order information as: breadcrumb, title, verified rating, short benefit proposition, price/savings, options, quantity, Add to cart, and factual reassurance.
- Use one full-width orange purchase action.
- Keep wishlist and secondary links visually subordinate.
- Show at most one savings treatment and one inventory message.
- Preserve WooCommerce validation, variation availability, backorders, and accessible status updates.

### Quantity and bundle options

- When a product has genuine WooCommerce variations, grouped products, or merchant-configured bundles, present them as accessible option cards.
- Each card may show quantity, relevant unit or serving count, current total, regular total, calculated savings, and per-unit economics when WooCommerce contains the required data.
- `Best value`, `Popular`, or equivalent labels require explicit merchant configuration or a documented data rule. They are never inferred from visual preference.
- Selection must update the native WooCommerce variation or bundle state rather than create a parallel cart implementation.
- A plain native control remains the resilient fallback when enhanced presentation is unavailable.

### Supporting content

- Place a compact reassurance row immediately below the purchase decision.
- Present product benefits, materials/specifications, care or usage, provenance, shipping/returns, and FAQs in scannable sections or accessible disclosure components.
- Use alternating image/text editorial bands only when matching product media and content exist.
- Keep verified review summaries near the buy box and the full review experience lower on the page.
- Preserve related products, photo attribution, countdowns tied to real schedules, and the existing desktop sticky purchase feature.
- Do not introduce subscription choices unless the product genuinely supports them.

### Product persuasion sequence

Long-form product pages use the following content order when the underlying content exists:

1. Desired outcome and primary benefits.
2. Who the product is for.
3. How it works or how it is made.
4. How to use, wear, size, or care for it.
5. What makes it different.
6. Contextual social proof.
7. Materials, specifications, provenance, and supporting evidence.
8. Delivery, returns, warranty, and other objection handling.
9. FAQ and related products.

Sections with no real merchant content are omitted. The template does not manufacture filler to make every page equally long.

### Contextual purchase anchors

- Long product pages may place restrained `Choose your options`, `Return to purchase`, or equivalent anchors after major persuasion sections.
- Anchors return focus and scroll position to the native buy box or activate the existing desktop purchase surface.
- They do not render duplicate Add to cart forms or bypass variation validation.
- Anchor behavior must respect reduced motion and remain understandable without JavaScript.

## Contextual Proof System

- Near the title: real average rating and review count linked to the review section.
- Near the buy box: at most one approved, product-relevant testimonial or concise proof point.
- Within supporting content: customer, craft, material, testing, or professional evidence only when the merchant supplies a verifiable source.
- Lower on the page: the native full review experience and optional user-generated content.
- Dedicated reviews page: aggregate distribution, filterable or paginated real reviews, and a review-submission route when WooCommerce permits it.
- Repeated proof must add new information rather than restate the same quote or rating.
- `Verified`, professional titles, publication logos, customer counts, and performance claims require an evidence source and must not be inferred from appearance.

## Cart

- Keep the full store header but reduce unrelated promotion around the cart.
- Use a clear two-column desktop layout: editable line items and a visually contained order summary.
- Surface free-shipping progress, delivery expectations, returns, and secure-payment reassurance near the relevant decision.
- Preserve coupons, quantities, removal, totals, cross-sells, notices, and the single native checkout action.
- On mobile, keep the existing native checkout action reachable without cloning it or covering validation and notices.
- Cross-sells appear after the main cart decision and remain visually secondary.

## Checkout

- Use the existing enclosed checkout state.
- Retain linked store identity, discreet help access, checkout progress language, and no unrelated navigation or footer link farm.
- Use a clear main form and sticky-capable order summary on desktop.
- Group contact, delivery, and payment information with strong headings, plain labels, and visible Required/Optional status.
- Preserve guest checkout, coupons, validation, payment methods, legal consent, inline offers, and one native Place Order action.
- Reassurance remains factual and close to the order summary or final action.
- Mobile layout remains single-column and keeps the native Place Order action reachable without duplication.

## Existing CRO Toolkit

The redesign preserves:

- promotion and coupon strip controls;
- category-aware product search;
- real sale percentages and scheduled countdowns;
- real low-stock messaging;
- wishlist behavior;
- shipping progress;
- verified review summaries;
- configured recent-purchase proof and privacy defaults;
- upsell/downsell placements and safe order behavior;
- checkout labels and policy guidance;
- mobile cart/checkout action behavior;
- product-gallery performance attributes.

## Comparison and Decision-Support Patterns

- Provide an optional editor pattern for product-to-product, collection-to-collection, or product-type comparisons.
- Suitable fields include use case, material, fit, durability, care, repairability, origin, warranty, included items, and current price.
- Comparisons should help shoppers choose, not merely declare Purple the winner.
- Competitor-named comparisons require merchant-provided evidence, a source note, and a visible `Last reviewed` date.
- Comparison values are editorial content, not automatically scraped from another site.
- When comparison data cannot be maintained, use a neutral `Which product is right for me?` selector instead.

## Policy-Backed Risk Reversal

- Shipping, delivery, returns, exchanges, warranty, payment, and support messages use one merchant-configured content source wherever practical.
- Short reassurance copy links to the complete applicable policy.
- Summary copy must surface material limitations such as eligibility windows, excluded items, return costs, handling fees, geographic limits, and first-order restrictions.
- The product, cart, checkout, footer, and promotional surfaces must not contradict one another.
- Guarantees, refund rates, delivery dates, and customer-service response times are shown only when factual and configured.

Toolkit presentation will be rewritten to match the new system. Business logic changes only where required to avoid duplicate output or support the approved hierarchy.

## Architecture and Boundaries

- Child theme owns global tokens, WooCommerce block styling, template overrides, template parts, and patterns.
- Toolkit owns functional CRO markup and behavior; its CSS adopts the child theme tokens.
- Prefer block templates and patterns over large PHP-rendered page structures.
- Use narrowly scoped PHP hooks only where block markup cannot express a required state.
- Do not add a CSS or JavaScript framework, icon library, remote font, animation library, or new third-party runtime.
- Replace superseded child-theme selectors instead of accumulating override layers.
- Do not modify the vendored Purple parent theme unless a verified child-theme limitation makes it unavoidable.

## Content and Error States

- No fabricated reviews, claims, purchases, scarcity, guarantees, shipping promises, or product benefits.
- No fabricated viewer counters, popularity labels, press logos, expert endorsements, or competitor claims.
- Optional sections disappear cleanly when their underlying content is absent.
- Empty categories retain useful navigation and a clear empty state.
- Out-of-stock and unavailable variations remain explicit and block invalid purchasing.
- Checkout errors remain adjacent to the affected controls and keyboard accessible.
- JavaScript enhancements must leave a usable server-rendered baseline.

## Responsive and Accessibility Requirements

- Verify at 320px, representative mobile, tablet, and desktop widths.
- Maintain at least 44px touch targets and visible keyboard focus.
- Preserve semantic heading order, accessible names, status announcements, and form labels.
- Avoid horizontal overflow, clipped prices, inaccessible filter drawers, and sticky controls covering content.
- Respect reduced motion and maintain WCAG AA color contrast for text and controls.
- Ensure hidden checkout chrome is removed from focus order.

## Performance Requirements

- Preserve current gallery loading and responsive image behavior.
- Add no new remote dependencies or frontend framework.
- Reserve media dimensions to avoid layout shift.
- Avoid broad DOM observers, continuous animation, or new second-level timers.
- Keep templates server-rendered and CSS-first.
- Compare the result with the existing local performance evidence; any regression must be investigated before completion.

## Implementation Sequence

1. Consolidate the visual tokens and global chrome.
2. Replace homepage template/pattern composition.
3. Replace archive structure and product-card presentation.
4. Replace single-product decision area and supporting content hierarchy.
5. Restyle cart and checkout into the quieter transactional flow.
6. Align toolkit components and offer views with the new system.
7. Add the contextual proof, bundle-option, objection-handling, purchase-anchor, comparison, and policy-backed reassurance patterns.
8. Remove superseded presentation rules.
9. Verify behavior, accessibility, responsive layouts, and performance.

## Acceptance Criteria

1. Homepage, category, product, cart, and checkout visibly belong to one design system.
2. Each major decision area exposes one dominant orange purchase action.
3. Existing WooCommerce and toolkit capabilities continue to work without duplicate controls or messages.
4. Product pages clearly separate the initial purchase decision from deeper supporting content.
5. Cart and checkout progressively reduce distraction while retaining help, policies, validation, and native actions.
6. No Purovitalis branding, copy, health claims, imagery, or subscription assumptions are reproduced.
7. No fabricated proof, urgency, savings, inventory, or guarantees appear.
8. Layouts pass representative desktop/mobile visual checks, keyboard checks, and 320px overflow checks.
9. PHP syntax, JavaScript syntax, contract tests, and `git diff --check` pass.
10. Before/after screenshots cover homepage, shop/category, product, cart, and checkout at desktop and mobile widths.
11. Existing performance contracts pass and no material asset or runtime regression remains unexplained.
12. Quantity and bundle option cards reflect native WooCommerce state, current prices, and calculated savings without duplicating cart logic.
13. Long product pages provide contextual return-to-buy-box anchors without duplicate purchase forms.
14. Proof, popularity, comparison, and guarantee claims have explicit content or data sources.
15. Reassurance summaries match their linked policies, including material limitations.

## Non-Goals

- Pixel-for-pixel reproduction of Purovitalis.
- Replacing store content with supplement-oriented content.
- Adding subscriptions, quizzes, loyalty programs, or apps without real business requirements.
- Adding referral programs, installment methods, or competitor-comparison automation without real business requirements and maintainable data.
- Rebuilding native WooCommerce cart or checkout logic.
- Changing offer economics, inventory, product pricing, shipping rules, or payment configuration.
- Deploying, publishing, or changing a live store.
