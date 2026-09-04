<?php
/**
 * Local WP-CLI integration tests; fixtures are restored/removed after the run.
 * Run: wp eval-file tests/checkout-extras-integration.php [prepare|cleanup].
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! preg_match( '/\.(test|localhost)$/', wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
	throw new RuntimeException( 'Run only against a local .test/.localhost WordPress installation.' );
}

function pot_checkout_expect( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function pot_checkout_cleanup_fixture(): void {
	$fixture = get_option( 'pot_checkout_test_fixture' );
	if ( ! $fixture ) {
		return;
	}
	foreach ( $fixture['products'] as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'pot-checkout-test' === $product->get_meta( '_pot_test_fixture' ) ) {
			$product->delete( true );
		}
	}
	if ( false === $fixture['settings'] ) {
		delete_option( 'pot_checkout_settings' );
	} else {
		update_option( 'pot_checkout_settings', $fixture['settings'] );
	}
	if ( false === ( $fixture['content_id'] ?? false ) ) {
		delete_option( 'pot_checkout_content_id' );
	} else {
		update_option( 'pot_checkout_content_id', $fixture['content_id'] );
	}
	delete_option( 'pot_checkout_test_fixture' );
}

$mode = $args[0] ?? 'test';
if ( 'cleanup' === $mode ) {
	pot_checkout_cleanup_fixture();
	WP_CLI::success( 'Checkout test products removed and original settings restored.' );
	return;
}
pot_checkout_expect( ! get_option( 'pot_checkout_test_fixture' ), 'Existing fixtures require cleanup before a new run.' );
$fixture = array( 'settings' => get_option( 'pot_checkout_settings', false ), 'content_id' => get_option( 'pot_checkout_content_id', false ), 'products' => array() );
update_option( 'pot_checkout_test_fixture', $fixture );
delete_option( 'pot_checkout_content_id' );
$prepared = false;

try {
	$images = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	foreach ( array( 'Base product', 'Travel pouch', 'Gift wrap', 'Care kit', 'Storage case' ) as $index => $name ) {
		$product = new WC_Product_Simple();
		$product->set_name( 'Checkout test: ' . $name );
		$product->set_status( 'publish' );
		$product->set_regular_price( $index ? '20.00' : '100.00' );
		if ( $index ) {
			$product->set_sale_price( '15.00' );
		}
		$product->set_virtual( true );
		$product->set_tax_status( 'none' );
		$product->set_short_description( 'An optional extra for your order.' );
		$product->set_image_id( $images[0] ?? 0 );
		$product->update_meta_data( '_pot_test_fixture', 'pot-checkout-test' );
		$fixture['products'][] = $product->save();
		update_option( 'pot_checkout_test_fixture', $fixture );
	}
	$rows = array_map( function ( $id ) { return array( 'product_id' => $id, 'title' => '', 'description' => '' ); }, array_slice( $fixture['products'], 1 ) );
	$settings = array(
		'upsells_enabled' => 1, 'upsells' => $rows, 'content_enabled' => 1,
		'content' => '<h3>Helpful checkout information</h3><p>Review delivery and returns before placing your order.</p><ul><li>Delivery details at checkout</li><li>Contact our team for help</li></ul>' . ( $images ? wp_get_attachment_image( $images[0], 'medium', false, array( 'alt' => 'Checkout test image' ) ) : '' ),
	);
	update_option( 'pot_checkout_settings', $settings );
	$sanitized = pot_sanitize_checkout_settings( array(
		'upsells_enabled' => 1,
		'upsells' => array( $rows[0], $rows[0], array( 'product_id' => array( 7 ) ), $rows[1], $rows[2] ),
		'content' => '<script>alert(1)</script><img src="https://example.test/photo.jpg" onerror="alert(1)"><form><input></form><h3>Help</h3>',
	) );
	pot_checkout_expect( 2 === count( $sanitized['upsells'] ), 'Configuration must be bounded, deduplicated, and reject malformed IDs.' );
	pot_checkout_expect( false === strpos( $sanitized['content'], '<script' ) && false === strpos( $sanitized['content'], 'onerror' ) && false === strpos( $sanitized['content'], '<form' ), 'Unsafe rich text must be removed.' );
	pot_checkout_expect( false !== strpos( $sanitized['content'], '<img' ) && false !== strpos( $sanitized['content'], '<h3>' ), 'Safe image/text content must survive.' );
	pot_checkout_expect( 0 === pot_sanitize_checkout_settings( null )['upsells_enabled'], 'Missing controls must disable features.' );
	pot_checkout_expect( 'manage_woocommerce' === apply_filters( 'option_page_capability_pot_checkout_settings_group', 'manage_options' ), 'Shop managers must be able to save checkout settings.' );

	wc_load_cart();
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $fixture['products'][0], 1 );
	$id = $fixture['products'][1];
	pot_checkout_expect( 4 === count( pot_checkout_cart_data()['offers'] ), 'All four independent offers must be returned.' );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true, 'price' => 0, 'quantity' => 999 ) );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) );
	$lines = pot_checkout_product_lines( $id );
	pot_checkout_expect( 1 === count( $lines ) && 1 === (int) reset( $lines )['quantity'], 'Repeated add requests must produce exactly one unit.' );
	WC()->cart->calculate_totals();
	pot_checkout_expect( 115.0 === (float) WC()->cart->get_subtotal(), 'The server must use the real catalog sale price, ignoring submitted price/quantity.' );
	pot_checkout_expect( pot_checkout_cart_data()['offers'][0]['added'], 'Cart response must expose selected state.' );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => false ) );
	pot_checkout_expect( ! pot_checkout_product_lines( $id ), 'Unchecking must remove the add-on.' );
	WC()->cart->add_to_cart( $id, 1 );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => false ) );
	pot_checkout_expect( 1 === count( pot_checkout_product_lines( $id ) ), 'Removal must not delete a normal catalog addition.' );
	pot_checkout_expect( ! pot_checkout_cart_data()['offers'][0]['added'], 'Normal cart items must not be presented as removable add-ons.' );
	foreach ( pot_checkout_product_lines( $id ) as $key => $line ) { WC()->cart->remove_cart_item( $key ); }
	foreach ( array(
		array( 'id' => $id, 'selected' => 'true' ),
		array( 'id' => 999999999, 'selected' => true ),
		array( 'id' => array( $id ), 'selected' => true ),
	) as $bad_request ) {
		$rejected = false;
		try { pot_update_checkout_upsell( $bad_request ); } catch ( Exception $error ) { $rejected = true; }
		pot_checkout_expect( $rejected, 'Malformed and unconfigured selections must be rejected.' );
	}
	$product = wc_get_product( $id );
	$product->set_stock_status( 'outofstock' );
	$product->save();
	pot_checkout_expect( 3 === count( pot_checkout_cart_data()['offers'] ), 'Out-of-stock offers must disappear.' );
	$rejected = false;
	try { pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) ); } catch ( Exception $error ) { $rejected = true; }
	pot_checkout_expect( $rejected, 'A stale page cannot add an unavailable product.' );
	$product->set_stock_status( 'instock' );
	$product->save();
	update_option( 'pot_checkout_settings', array_merge( $settings, array( 'upsells_enabled' => 0, 'content_enabled' => 0 ) ) );
	pot_checkout_expect( array( 'offers' => array(), 'content' => '' ) === pot_checkout_cart_data(), 'Disabled features must return no public content.' );
	$rejected = false;
	try { pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) ); } catch ( Exception $error ) { $rejected = true; }
	pot_checkout_expect( $rejected, 'Disabled upsells must reject stale acceptance requests.' );
	update_option( 'pot_checkout_settings', $settings );
	WC()->cart->empty_cart();
	pot_checkout_expect( array() === pot_checkout_cart_data()['offers'], 'Empty carts must not show offers.' );
	if ( 'prepare' === $mode ) {
		$prepared = true;
		WP_CLI::line( wp_json_encode( array( 'products' => $fixture['products'], 'home' => home_url() ) ) );
	} else {
		WP_CLI::success( 'Checkout integration contracts passed.' );
	}
} finally {
	if ( ! $prepared ) {
		pot_checkout_cleanup_fixture();
	}
}
