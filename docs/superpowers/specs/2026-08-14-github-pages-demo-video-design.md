# GitHub Pages demo video design

## Goal

Publish a concise, silent product demo for Purple CRO Optimized that proves the
storefront and offer funnel work, while giving GitHub visitors a clear path to
the child-theme repository and the original Woo Purple parent source.

## Source and safety

- Record only `http://purple-optimize.test`.
- Sync the local site to the committed child theme and toolkit before capture.
- Use test products, test payment details, and a newly created test order.
- Do not show real customer data, production orders, credentials, browser chrome,
  notifications, or unrelated admin screens.
- Do not modify `solwooplayground.blog` while producing the demo.

## Storyboard

Target duration: 75 to 95 seconds at 1920 by 1080 pixels.

1. **Opening, 3 seconds**
   - Purple CRO Optimized title.
   - Caption: "Conversion-focused WooCommerce child theme."
2. **Homepage, 8 seconds**
   - Hero, trust strip, categories, and featured products.
   - Caption: "Clear discovery, trust, and product hierarchy."
3. **Catalog, 8 seconds**
   - Category navigation, filters, product cards, and sorting.
   - Caption: "Fast product-led catalog navigation."
4. **Product decision page, 12 seconds**
   - Gallery, restrained buy box, price, reassurance, and purchase action.
   - Include one narrow/mobile view of the sticky add-to-cart action.
   - Caption: "One focused purchase surface on every screen."
5. **Add-to-cart feedback, 6 seconds**
   - Add the product and show the success animation plus aligned notice.
   - Caption: "Immediate, accessible cart confirmation."
6. **Pre-checkout offer funnel, 12 seconds**
   - Continue from cart to the full-page upsell.
   - Reject the upsell and reveal the configured downsell.
   - Accept the downsell and continue to checkout.
   - Caption: "Optional upsell, then a relevant downsell."
7. **Checkout, 10 seconds**
   - Show the enclosed checkout, order summary, delivery, and test payment.
   - Complete the test order.
   - Caption: "Native WooCommerce checkout stays in control."
8. **Post-purchase offer, 10 seconds**
   - Show the separate post-purchase offer after the completed order.
   - Make clear that acceptance starts a separate checkout and never silently
     changes or recharges the completed order.
   - Caption: "Post-purchase offers remain explicit and separate."
9. **Toolkit validation, 8 seconds**
   - Briefly show the Purple Optimize settings screen and offer-product health
     warnings.
   - Caption: "Invalid offer products are flagged before they break the funnel."
10. **End card, 5 seconds**
    - Link to `solstudioim/purple-cro-optimized`.
    - Link to the original Woo Purple source at
      `woocommerce/woo-themes/tree/trunk/purple`.
    - Caption: "Built on Woo Purple. Open source under GPL."

## Capture and editing approach

- Use deterministic browser automation for route changes and interactions.
- Capture real rendered states from the local site.
- Assemble the final video with restrained cuts, short crossfades, and subtle
  crops or pans; do not use decorative motion that competes with the interface.
- Render an H.264 MP4 with a browser-compatible pixel format and no audio track.
- Target a practical Pages payload under 20 MB. Reduce frame rate or duration
  before reducing text legibility.
- Export a 16:9 poster image from the opening or strongest product frame.

## GitHub Pages presentation

- Publish a responsive static page from repository-owned source.
- Lead with the video, a descriptive poster, native controls, and a concise text
  summary for visitors who do not play it.
- Include sections for the demonstrated journey, installation requirements,
  current child-theme/toolkit versions, and project principles.
- Link to the child-theme repository and the canonical WooCommerce Purple source.
- Do not describe Purple as being maintained in a WordPress.com repository.
- Keep the video usable without autoplay. If muted inline preview is used, it
  must pause when not visible and preserve native controls.

## Repository structure

- Store the Pages source in a dedicated repository directory selected after
  checking the existing Pages configuration.
- Store the compressed video and poster beside the page or in a clearly named
  media subdirectory.
- Add a reproducible capture/build script and a short maintenance note so the
  demo can be regenerated after future releases.
- Do not commit temporary frames, test-order exports, browser profiles, or raw
  recordings.

## Failure handling

- Stop capture if checkout exposes non-test customer data.
- If a configured offer is invalid, repair the local test configuration and
  restart that segment rather than editing around a broken state.
- If the video exceeds the target payload, shorten pauses and reduce frame rate;
  retain 1080p captions and interface readability.
- If GitHub Pages is not configured, add the smallest repository-native Pages
  workflow and verify its deployment before reporting a public URL.

## Verification

- Theme and toolkit versions match the committed release before recording.
- Every storyboard state is visible and captioned in the final MP4.
- The upsell rejection reveals the downsell.
- Checkout completion leads to an explicit post-purchase offer segment.
- The MP4 has no audio stream and plays in a Chromium browser.
- The Pages layout works at desktop and mobile widths without overflow.
- Both repository links resolve to the intended GitHub locations.
- The public Pages deployment succeeds and serves the final poster and video.
