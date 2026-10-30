=== Purple Optimize Toolkit ===
Contributors: solstudioim
Tags: woocommerce, checkout, upsell, wishlist
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.11.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optional conversion features and guided settings for the Purple Optimize WooCommerce child theme.

== Description ==

Manage promotions, product guidance, a browser wishlist, cart and checkout helpers,
optional upsell/downsell offers, checkout add-ons, and merchant-authored helpful content.
The settings workspace lives under WooCommerce > Purple Optimize, with a guided
Overview, local version checks, and sections for each part of the shopping journey.

Requires WooCommerce 11.1 or newer. The tested theme pairing is Purple 1.0.3+
and Purple Optimize 0.6.0+. Other themes require their own storefront verification.

Existing CRO feature defaults remain enabled on fresh installations. Parent colors,
typography and native commerce controls remain inherited; existing choices are preserved.
Product guidance and checkout field labels have separate switches.
Enable Promotion strip and Browser wishlist in the Storefront section to show
a highlighted store notice and a separate header wishlist link with a live saved-item count.
An administrator enabling wishlist creates or reuses a native shortcode page;
merchant-authored pages are never replaced. Saved items stay in browser storage.
Recent-purchase proof and customer-name display are off by default. The wishlist
uses browser storage. Recent-purchase proof uses processing/completed WooCommerce
orders; names must remain off unless the merchant has an appropriate privacy basis.
No external analytics service is used. Offers never silently recharge an order;
after-purchase acceptance starts a separate checkout and order.

Local preview 0.11.10 is unpublished. Upload the local ZIP for testing; do not use
the child setup download action until the matching GitHub asset is published.
Older Purple versions show a recommendation, without blocking toolkit installation.

== Installation ==

1. Install and activate WooCommerce 11.1+ on WordPress 7.1+ and PHP 7.4+.
2. Upload purple-optimize-toolkit-0.11.10.zip under Plugins > Add New > Upload Plugin.
3. Activate the toolkit, then open WooCommerce > Purple Optimize.
4. Review compatibility checks and settings. Existing choices are preserved.

The toolkit and child theme are distributed as separate GitHub release packages:
https://github.com/solstudioim/purple-cro-optimized/releases
This plugin is not currently listed on WordPress.org. This readme prepares its
metadata for a future submission; it does not imply directory approval.

== Frequently Asked Questions ==

= Does installing the theme silently install the toolkit? =
No. Appearance > Purple Optimize setup offers an explicit installation/activation
action using the versioned GitHub release package. ZIP upload is also supported.

= How do updates work before WordPress.org listing? =
Download the newer toolkit ZIP from a published GitHub release and upload it through
Plugins > Add New > Upload Plugin. Review WordPress's replacement confirmation.
The setup action never overwrites an installed toolkit. No GitHub background updater is included.

= Do version checks prove full compatibility? =
No. They check installed minimum versions locally. The combined theme and plugin
must also pass automated and storefront checks before deployment.

= How are settings saved? =
Each section has an explicit save action; checkout add-ons use their own labeled
Save checkout features action. The offer enable/disable switch saves immediately
and restores the previous state if saving fails. Settings in other sections are retained.

== Changelog ==

= 0.11.10 =
* Match add-on card savings labels and regular-price comparisons with native WooCommerce order summaries.
* Use one combined percentage/currency label on add-on cards, with native Store API rounding and unchanged WooCommerce summary rendering.

= 0.11.9 =
* Add optional per-add-on percentage discounts; blank or zero preserves catalog pricing.
* Preserve accepted prices in cart sessions, native totals and order details.

= 0.11.8 =
* Replace the review navigation bar with subtle borderless arrows inside multi-review rotators.
* Keep accessible navigation labels, screen-reader position and optional pause control.

= 0.11.7 =
* Use a borderless, theme-tinted review card inside the existing checkout content frame.
* Retain the standalone review card outline.

= 0.11.6 =
* Rename Privacy to Social proof while retaining existing recent-purchase settings.
* Add native review selection, shortcode generation and preview with static/rotating layouts.
* Support the reviews shortcode in helpful checkout content without enabling unrelated shortcodes.

= 0.11.5 =
* Restore native mini-cart opening after add to cart for the Purple Optimize child.
* Preserve explicit block behavior choices without replacing parent templates or styling.

= 0.11.4 =
* Add a native promotion background color picker with clear-to-parent fallback.
* Choose a contrasting text color automatically for custom backgrounds.

= 0.11.3 =
* Move wishlist access from the promotion notice to native header cart controls.
* Place category navigation below the header in the parent wide container.
* Align shipping progress with native cart/checkout containers and update checkout eligibility live.

= 0.11.2 =
* Scope offer decisions to cart/configuration for both guest and signed-in shoppers.
* Clear stale legacy suppression while respecting unchanged-cart decisions.
* Add a focused boxed upsell/downsell card using Purple design tokens.

= 0.11.1 =
* Keep existing CRO feature defaults while inheriting parent presentation.
* Restore wishlist top-bar access and browser-local saved-item count.
* Register checkout extensions before hydration on WooCommerce/Gutenberg stacks.
* Preserve configured add-ons, published helpful content and all offer placements.

= 0.10.0 =
* Add a responsive workspace sidebar and guided setup Overview.
* Add local compatibility checks for the combined theme and toolkit stack.
* Preserve unrelated settings on section saves and retain immediate offer switching.
* Add a catalog-backed saved offer preview and unsaved-edit protection.

= 0.9.0 =
* Add native WordPress block editing for helpful checkout content.
