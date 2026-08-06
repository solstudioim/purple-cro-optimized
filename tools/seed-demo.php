<?php
/**
 * Seed the local Purple Optimize demonstration store.
 *
 * Run with: wp eval-file tools/seed-demo.php
 *
 * @package PurpleOptimize
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce must be active.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/**
 * Import a bundled Purple image once.
 *
 * @param string $filename Image filename.
 * @return int Attachment ID.
 */
function pot_seed_image( string $filename ): int {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pot_seed_image',
			'meta_value'     => $filename,
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$source = get_theme_root() . '/purple/assets/images/' . $filename;
	if ( ! is_readable( $source ) ) {
		return 0;
	}

	$tmp = wp_tempnam( $filename );
	if ( ! $tmp || ! copy( $source, $tmp ) ) {
		return 0;
	}

	$attachment_id = media_handle_sideload(
		array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		),
		0,
		ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) )
	);

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return 0;
	}

	update_post_meta( $attachment_id, '_pot_seed_image', $filename );
	return (int) $attachment_id;
}

/**
 * Ensure a product category exists.
 *
 * @param string $name Category name.
 * @return int Term ID.
 */
function pot_seed_category( string $name ): int {
	$term = term_exists( $name, 'product_cat' );
	if ( $term ) {
		return (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
	$created = wp_insert_term( $name, 'product_cat' );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}

/**
 * Create or refresh a local policy page owned by the demo seeder.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Page content.
 * @return int Page ID.
 */
function pot_seed_page( string $slug, string $title, string $content ): int {
	$page = get_page_by_path( $slug );
	$data = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content );
	if ( $page ) {
		$data['ID'] = $page->ID;
		$page_id    = wp_update_post( $data );
	} else {
		$page_id = wp_insert_post( $data );
	}
	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_pot_seed_page', '1' );
		return (int) $page_id;
	}
	return 0;
}

/**
 * Add one verified-looking demonstration review, clearly attributed as demo data.
 *
 * @param int $product_id Product ID.
 */
function pot_seed_review( int $product_id ): void {
	$existing = get_comments(
		array(
			'post_id'    => $product_id,
			'meta_key'   => '_pot_seed_review',
			'meta_value' => '1',
			'count'      => true,
		)
	);
	if ( $existing ) {
		WC_Comments::clear_transients( $product_id );
		return;
	}

	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => 'Demo shopper',
			'comment_author_email' => 'demo@example.test',
			'comment_content'      => 'Demo review: clear product details and an easy purchase flow.',
			'comment_approved'     => 1,
			'comment_type'         => 'review',
		)
	);
	if ( $comment_id ) {
		add_comment_meta( $comment_id, 'rating', 5, true );
		add_comment_meta( $comment_id, '_pot_seed_review', '1', true );
		WC_Comments::clear_transients( $product_id );
	}
}

/**
 * Create or refresh a simple product.
 *
 * @param array<string, mixed> $data Product data.
 * @return int Product ID.
 */
function pot_seed_simple_product( array $data ): int {
	$product_id = wc_get_product_id_by_sku( $data['sku'] );
	$product    = $product_id ? wc_get_product( $product_id ) : new WC_Product_Simple();
	if ( ! $product instanceof WC_Product_Simple ) {
		return 0;
	}

	$product->set_name( $data['name'] );
	$product->set_slug( sanitize_title( $data['name'] ) );
	$product->set_sku( $data['sku'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( (string) $data['regular_price'] );
	$product->set_price( (string) ( $data['sale_price'] ?: $data['regular_price'] ) );
	$product->set_sale_price( (string) $data['sale_price'] );
	$product->set_description( $data['description'] );
	$product->set_short_description( $data['short_description'] );
	$product->set_category_ids( $data['categories'] );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( (int) $data['stock'] );
	$product->set_stock_status( 'instock' );
	$product->set_image_id( pot_seed_image( $data['image'] ) );
	if ( $data['sale_price'] ) {
		$product->set_date_on_sale_from( time() - DAY_IN_SECONDS );
		$product->set_date_on_sale_to( time() + ( 3 * DAY_IN_SECONDS ) );
	} else {
		$product->set_date_on_sale_from( null );
		$product->set_date_on_sale_to( null );
	}
	$product_id = $product->save();
	pot_seed_review( $product_id );
	return $product_id;
}

/**
 * Create the demo variable product once.
 *
 * @param int $category_id Product category.
 * @return int Product ID.
 */
function pot_seed_variable_product( int $category_id ): int {
	$product_id = wc_get_product_id_by_sku( 'POT-VAR-001' );
	$product    = $product_id ? wc_get_product( $product_id ) : new WC_Product_Variable();
	if ( ! $product instanceof WC_Product_Variable ) {
		return 0;
	}

	$product->set_name( 'Everyday Merino Crew' );
	$product->set_slug( 'everyday-merino-crew' );
	$product->set_sku( 'POT-VAR-001' );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_description( 'A demonstration variable product for testing Purple variation chips, inventory, and the sticky purchase bar.' );
	$product->set_short_description( 'Choose a size without leaving the product page.' );
	$product->set_category_ids( array( $category_id ) );
	$product->set_image_id( pot_seed_image( 'green-sweater-woman.webp' ) );

	$attribute = new WC_Product_Attribute();
	$attribute->set_id( 0 );
	$attribute->set_name( 'Size' );
	$attribute->set_options( array( 'Small', 'Medium', 'Large' ) );
	$attribute->set_position( 0 );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	$product->set_attributes( array( $attribute ) );
	$product_id = $product->save();

	if ( ! $product->get_children() ) {
		foreach ( array( 'Small' => 89, 'Medium' => 89, 'Large' => 94 ) as $size => $price ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( array( 'size' => $size ) );
			$variation->set_regular_price( (string) $price );
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( 'Medium' === $size ? 3 : 12 );
			$variation->set_stock_status( 'instock' );
			$variation->save();
		}
	}

	pot_seed_review( $product_id );
	return $product_id;
}

WC_Install::create_pages();

$knitwear    = pot_seed_category( 'Knitwear' );
$accessories = pot_seed_category( 'Accessories' );

$products = array(
	array( 'name' => 'Lavender Rib Beanie', 'sku' => 'POT-001', 'regular_price' => 42, 'sale_price' => 34, 'stock' => 4, 'image' => 'product-beanie-lavander-purple-theme-3.webp', 'categories' => array( $accessories ), 'short_description' => 'Soft rib knit with a close, comfortable fit. One size with flexible stretch.', 'description' => '<h3>Details</h3><ul><li>Soft rib-knit construction</li><li>Flexible one-size fit</li><li>Fold-over cuff</li></ul><h3>Care</h3><p>Hand wash cool and dry flat. Product colors can vary slightly by display.</p>' ),
	array( 'name' => 'Golden Everyday Socks', 'sku' => 'POT-002', 'regular_price' => 24, 'sale_price' => '', 'stock' => 18, 'image' => 'product-socks-yellow-purple-theme-1.webp', 'categories' => array( $accessories ), 'short_description' => 'Breathable everyday socks with a cushioned sole and comfortable rib cuff.', 'description' => '<h3>Details</h3><ul><li>Cushioned sole</li><li>Breathable knit</li><li>Comfort rib cuff</li></ul><h3>Care</h3><p>Machine wash cool with similar colors.</p>' ),
	array( 'name' => 'Slate Walking Socks', 'sku' => 'POT-003', 'regular_price' => 28, 'sale_price' => '', 'stock' => 11, 'image' => 'product-socks-gray-purple-theme-2.webp', 'categories' => array( $accessories ), 'short_description' => 'Cushioned walking socks designed for longer days and cooler conditions.', 'description' => '<h3>Details</h3><ul><li>Dense cushioned sole</li><li>Supportive rib cuff</li><li>Mid-calf length</li></ul><p>Machine wash cool and air dry.</p>' ),
	array( 'name' => 'Coastal Blue Sweater', 'sku' => 'POT-004', 'regular_price' => 118, 'sale_price' => 96, 'stock' => 9, 'image' => 'blue-sweater-man.webp', 'categories' => array( $knitwear ), 'short_description' => 'A relaxed merino layer in coastal blue with a regular-length hem.', 'description' => '<h3>Fit and details</h3><ul><li>Relaxed fit</li><li>Regular length</li><li>Rib cuffs and hem</li></ul><h3>Care</h3><p>Hand wash cool and reshape while damp.</p>' ),
	array( 'name' => 'Petal Crew Sweater', 'sku' => 'POT-005', 'regular_price' => 112, 'sale_price' => '', 'stock' => 16, 'image' => 'pink-sweater-woman.webp', 'categories' => array( $knitwear ), 'short_description' => 'Lightweight warmth in a soft petal tone with a classic crew neck.', 'description' => '<h3>Fit and details</h3><ul><li>Classic crew neck</li><li>Lightweight knit</li><li>Regular fit</li></ul><p>Hand wash cool and dry flat.</p>' ),
	array( 'name' => 'Ember Roll Neck', 'sku' => 'POT-006', 'regular_price' => 126, 'sale_price' => '', 'stock' => 7, 'image' => 'orange-sweater-woman-cropped.webp', 'categories' => array( $knitwear ), 'short_description' => 'Warm textured knit with a structured roll neck and relaxed body.', 'description' => '<h3>Fit and details</h3><ul><li>Structured roll neck</li><li>Relaxed body</li><li>Textured knit</li></ul><p>Hand wash cool and store folded.</p>' ),
);

$product_ids = array();
foreach ( $products as $product_data ) {
	$product_ids[ $product_data['sku'] ] = pot_seed_simple_product( $product_data );
}
$product_ids['POT-VAR-001'] = pot_seed_variable_product( $knitwear );

// Seed native WooCommerce product relationships used by the offer engine.
$beanie = wc_get_product( $product_ids['POT-001'] );
if ( $beanie ) {
	$beanie->set_cross_sell_ids( array( $product_ids['POT-002'] ) );
	$beanie->set_upsell_ids( array( $product_ids['POT-004'] ) );
	$beanie->save();
}

// Create one clearly local completed order so the social-proof component has real order data.
$demo_orders = wc_get_orders(
	array(
		'limit'      => 1,
		'status'     => array_keys( wc_get_order_statuses() ),
		'meta_query' => array(
			array(
				'key'   => '_pot_demo_order',
				'value' => '1',
			),
		),
	)
);
if ( ! $demo_orders && $beanie ) {
	$demo_order = wc_create_order();
	if ( ! is_wp_error( $demo_order ) ) {
		$demo_order->add_product( $beanie, 1 );
		$demo_order->set_billing_first_name( 'James' );
		$demo_order->set_billing_last_name( 'Demo' );
		$demo_order->set_billing_email( 'james@example.test' );
		$demo_order->set_created_via( 'purple-optimize-demo' );
		$demo_order->set_date_created( time() - ( 2 * HOUR_IN_SECONDS ) );
		$demo_order->update_meta_data( '_pot_demo_order', '1' );
		$demo_order->calculate_totals();
		$demo_order->set_status( 'completed', 'Local Purple Optimize demonstration order.' );
		$demo_order->save();
	}
}

$home_id = (int) get_option( 'page_on_front' );
if ( ! $home_id ) {
	$home_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Home',
			'post_name'    => 'home',
			'post_content' => '',
		)
	);
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

$wishlist = get_page_by_path( 'wishlist' );
if ( ! $wishlist ) {
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Wishlist',
			'post_name'    => 'wishlist',
			'post_content' => '<!-- wp:shortcode -->[purple_optimize_wishlist]<!-- /wp:shortcode -->',
		)
	);
}

$shipping_page = pot_seed_page( 'shipping-returns', 'Shipping & Returns', '<h2>Demo shipping policy</h2><p>Standard delivery costs $8. Orders reaching $75 qualify for the configured free-shipping method. Delivery timing and destination eligibility are confirmed at checkout.</p><h2>Demo returns policy</h2><p>This local demonstration accepts unused items in original condition within 30 days. A real merchant must replace this text with its applicable process, exclusions, costs, and statutory rights before launch.</p>' );
$privacy_page  = pot_seed_page( 'privacy-policy', 'Privacy Policy', '<p>This local demonstration stores only the data needed to test WordPress and WooCommerce. It is not a production privacy notice. A real merchant must document its controllers, purposes, legal bases, retention, processors, and customer rights.</p>' );
$terms_page    = pot_seed_page( 'terms-conditions', 'Terms & Conditions', '<p>These are local demonstration terms only. Prices, inventory, discounts, products, and orders on this site are test data and do not form a real-world sale.</p>' );
pot_seed_page( 'about', 'About', '<p>Purple Optimize is a local demonstration storefront built on WooCommerce and the Purple theme. Replace this page with the merchant story, sourcing evidence, and contact details before launch.</p>' );
pot_seed_page( 'contact', 'Contact Us', '<p>Demo support hours: Monday to Friday, 10:00–17:00. This local site does not send messages; a production merchant must add a monitored contact channel.</p>' );
pot_seed_page( 'faqs', 'Frequently Asked Questions', '<h2>When will my order arrive?</h2><p>Available delivery methods and costs are shown at checkout.</p><h2>Can I return an item?</h2><p>See the Shipping & Returns page for the local demonstration policy.</p>' );
pot_seed_page( 'journal', 'Journal', '<p>Product care, materials, sizing, and store updates can be published here.</p>' );
update_option( 'wp_page_for_privacy_policy', $privacy_page );
update_option( 'woocommerce_terms_page_id', $terms_page );

update_option( 'blogname', 'Purple Optimize Store' );
update_option( 'blogdescription', 'A local conversion-focused WooCommerce demonstration' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );
update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
update_option( 'permalink_structure', '/%postname%/' );

$cod = get_option( 'woocommerce_cod_settings', array() );
update_option( 'woocommerce_cod_settings', array_merge( $cod, array( 'enabled' => 'yes', 'title' => 'Pay on delivery' ) ) );

$zone             = new WC_Shipping_Zone( 0 );
$shipping_methods = $zone->get_shipping_methods();
$method_ids       = wp_list_pluck( $shipping_methods, 'id' );
if ( ! in_array( 'flat_rate', $method_ids, true ) ) {
	$instance_id = $zone->add_shipping_method( 'flat_rate' );
	update_option( 'woocommerce_flat_rate_' . $instance_id . '_settings', array( 'title' => 'Standard delivery', 'tax_status' => 'taxable', 'cost' => '8' ) );
}
if ( ! in_array( 'free_shipping', $method_ids, true ) ) {
	$instance_id = $zone->add_shipping_method( 'free_shipping' );
	update_option( 'woocommerce_free_shipping_' . $instance_id . '_settings', array( 'title' => 'Free shipping', 'requires' => 'min_amount', 'min_amount' => '75', 'ignore_discounts' => 'no' ) );
}

$coupon_id = wc_get_coupon_id_by_code( 'WELCOME10' );
if ( ! $coupon_id ) {
	$coupon = new WC_Coupon();
	$coupon->set_code( 'WELCOME10' );
	$coupon->set_discount_type( 'percent' );
	$coupon->set_amount( 10 );
	$coupon->set_description( 'Local Purple Optimize demo coupon.' );
	$coupon->save();
}

update_option(
	'pot_settings',
	array_merge(
		pot_defaults(),
		array(
			'promo_text'              => 'Welcome offer: 10% off your first demo order',
			'promo_code'              => 'WELCOME10',
			'free_shipping_threshold' => 75,
			'recent_sales'            => 1,
			'social_proof_show_names' => 1,
			'offer_funnel'            => 1,
			'upsell_product_id'       => $product_ids['POT-004'],
			'upsell_discount'         => 50,
			'upsell_countdown'        => 10,
			'downsell_product_id'     => $product_ids['POT-002'],
			'downsell_discount'       => 70,
			'downsell_countdown'      => 10,
		)
	)
);

delete_transient( 'pot_recent_purchases_30_names' );

flush_rewrite_rules();
wc_delete_product_transients();

WP_CLI::success( 'Purple Optimize demo content is ready.' );
