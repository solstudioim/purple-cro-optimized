<?php
/** Self-contained review shortcode builder; no changes to saved store settings. */
defined( 'ABSPATH' ) || exit;
?>
<section class="pot-settings-card pot-review-builder">
<h3><?php esc_html_e( 'Reviews & testimonials', 'purple-optimize-toolkit' ); ?></h3>
<p><?php esc_html_e( 'Reuse approved WooCommerce product reviews on checkout, your homepage, or other pages. Each shortcode keeps its own selection; native product reviews stay unchanged.', 'purple-optimize-toolkit' ); ?></p>
<form id="pot-reviews-configurator">
<table class="form-table" role="presentation"><tbody>
<tr><th scope="row"><label for="pot-reviews-source"><?php esc_html_e( 'Review source', 'purple-optimize-toolkit' ); ?></label></th><td>
<select id="pot-reviews-source" name="source"><option value="all"><?php esc_html_e( 'All products', 'purple-optimize-toolkit' ); ?></option><option value="products"><?php esc_html_e( 'Selected products', 'purple-optimize-toolkit' ); ?></option><option value="reviews"><?php esc_html_e( 'Specific review IDs', 'purple-optimize-toolkit' ); ?></option></select>
<p class="description"><?php esc_html_e( 'Only approved reviews on published, unprotected products are eligible. Product attribution is always shown.', 'purple-optimize-toolkit' ); ?></p></td></tr>
<tr data-review-source="products" hidden><th scope="row"><label for="pot-reviews-products"><?php esc_html_e( 'Product IDs', 'purple-optimize-toolkit' ); ?></label></th><td>
<input type="text" class="regular-text" id="pot-reviews-products" name="products" placeholder="22, 18" aria-describedby="pot-reviews-products-help">
<p class="description" id="pot-reviews-products-help"><?php esc_html_e( 'Enter one or more product IDs separated by commas, or add products using search below. Reviews from these products are combined.', 'purple-optimize-toolkit' ); ?></p>
<label for="pot-reviews-product-search"><?php esc_html_e( 'Find products', 'purple-optimize-toolkit' ); ?></label><br>
<select id="pot-reviews-product-search" class="wc-product-search" style="width:100%;max-width:480px" multiple data-action="woocommerce_json_search_products" data-allow_clear="true" data-placeholder="<?php esc_attr_e( 'Search products to add their IDs…', 'purple-optimize-toolkit' ); ?>"></select>
</td></tr>
<tr data-review-source="reviews" hidden><th scope="row"><label for="pot-reviews-ids"><?php esc_html_e( 'Review IDs', 'purple-optimize-toolkit' ); ?></label></th><td><input type="text" class="regular-text" id="pot-reviews-ids" name="review_ids" placeholder="101, 102" aria-describedby="pot-reviews-ids-help"><p class="description" id="pot-reviews-ids-help"><?php esc_html_e( 'Comma-separated review IDs. Find the ID in the review edit link under Products → Reviews. Unapproved or deleted reviews will never appear.', 'purple-optimize-toolkit' ); ?></p></td></tr>
<tr><th scope="row"><label for="pot-reviews-limit"><?php esc_html_e( 'Maximum reviews', 'purple-optimize-toolkit' ); ?></label></th><td><input id="pot-reviews-limit" name="limit" type="number" min="1" max="12" value="3" class="small-text"></td></tr>
<tr><th scope="row"><label for="pot-reviews-rating"><?php esc_html_e( 'Minimum rating', 'purple-optimize-toolkit' ); ?></label></th><td><select id="pot-reviews-rating" name="min_rating"><option value="0"><?php esc_html_e( 'Any rating', 'purple-optimize-toolkit' ); ?></option><?php for ( $rating = 1; $rating <= 5; $rating++ ) : ?><option value="<?php echo esc_attr( (string) $rating ); ?>"><?php echo esc_html( sprintf( __( '%d stars or higher', 'purple-optimize-toolkit' ), $rating ) ); ?></option><?php endfor; ?></select></td></tr>
<tr><th scope="row"><label for="pot-reviews-order"><?php esc_html_e( 'Order', 'purple-optimize-toolkit' ); ?></label></th><td><select id="pot-reviews-order" name="order"><option value="newest"><?php esc_html_e( 'Newest first', 'purple-optimize-toolkit' ); ?></option><option value="oldest"><?php esc_html_e( 'Oldest first', 'purple-optimize-toolkit' ); ?></option></select></td></tr>
<tr><th scope="row"><label for="pot-reviews-layout"><?php esc_html_e( 'Display', 'purple-optimize-toolkit' ); ?></label></th><td><select id="pot-reviews-layout" name="layout"><option value="list"><?php esc_html_e( 'Static review cards', 'purple-optimize-toolkit' ); ?></option><option value="rotator"><?php esc_html_e( 'Review rotator', 'purple-optimize-toolkit' ); ?></option></select><p class="description"><?php esc_html_e( 'Use a maximum of 1 for a single testimonial. Static cards are recommended at checkout.', 'purple-optimize-toolkit' ); ?></p></td></tr>
<tr data-review-rotation hidden><th scope="row"><label for="pot-reviews-autoplay"><?php esc_html_e( 'Automatic rotation', 'purple-optimize-toolkit' ); ?></label></th><td><select id="pot-reviews-autoplay" name="autoplay"><option value="0"><?php esc_html_e( 'Off — visitors use Previous and Next', 'purple-optimize-toolkit' ); ?></option><option value="5"><?php esc_html_e( 'Every 5 seconds', 'purple-optimize-toolkit' ); ?></option><option value="8"><?php esc_html_e( 'Every 8 seconds', 'purple-optimize-toolkit' ); ?></option><option value="12"><?php esc_html_e( 'Every 12 seconds', 'purple-optimize-toolkit' ); ?></option></select><p class="description"><?php esc_html_e( 'Rotation pauses on hover or focus and respects reduced motion. Visitors can pause it.', 'purple-optimize-toolkit' ); ?></p></td></tr>
<tr><th scope="row"><label for="pot-reviews-author"><?php esc_html_e( 'Reviewer name', 'purple-optimize-toolkit' ); ?></label></th><td><select id="pot-reviews-author" name="author"><option value="first_name"><?php esc_html_e( 'First name only', 'purple-optimize-toolkit' ); ?></option><option value="anonymous"><?php esc_html_e( 'Anonymous customer', 'purple-optimize-toolkit' ); ?></option></select><p class="description"><?php esc_html_e( 'Uses the public review author. Email addresses are never displayed. Verified owner appears only when WooCommerce confirms it.', 'purple-optimize-toolkit' ); ?></p></td></tr>
<tr><th scope="row"><label for="pot-reviews-heading"><?php esc_html_e( 'Optional heading', 'purple-optimize-toolkit' ); ?></label></th><td><input class="regular-text" id="pot-reviews-heading" name="heading" type="text" maxlength="160"></td></tr>
</tbody></table>
<p><button class="button button-secondary" type="submit" id="pot-generate-reviews"><?php esc_html_e( 'Generate shortcode & preview', 'purple-optimize-toolkit' ); ?></button></p>
<p id="pot-reviews-status" role="status" aria-live="polite"></p>
<div id="pot-reviews-result" hidden>
<label for="pot-reviews-shortcode"><strong><?php esc_html_e( 'Your shortcode', 'purple-optimize-toolkit' ); ?></strong></label>
<textarea id="pot-reviews-shortcode" class="large-text code" rows="3" readonly></textarea>
<p><button class="button" type="button" id="pot-copy-reviews"><?php esc_html_e( 'Copy shortcode', 'purple-optimize-toolkit' ); ?></button></p>
<p><?php esc_html_e( 'Paste into a Shortcode block on a page, a shortcode-compatible widget, or Cart & checkout → Helpful checkout content → Open block editor. Publish that content to display it. This does not insert reviews automatically.', 'purple-optimize-toolkit' ); ?></p>
<h4><?php esc_html_e( 'Preview', 'purple-optimize-toolkit' ); ?></h4>
<p class="description"><?php esc_html_e( 'Layout preview only. On the storefront, colors and typography inherit your active theme.', 'purple-optimize-toolkit' ); ?></p>
<div id="pot-reviews-preview"></div>
</div>
</form>
</section>
