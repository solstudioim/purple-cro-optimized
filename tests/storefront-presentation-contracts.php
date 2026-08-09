<?php
/**
 * Behavior contracts for the storefront presentation policies.
 */

declare( strict_types = 1 );

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

require_once dirname( __DIR__ ) . '/wp-content/plugins/purple-optimize-toolkit/includes/storefront-presentation.php';

/**
 * Fail with a useful message without relying on PHP's optional assertions.
 *
 * @param bool   $condition Contract result.
 * @param string $message   Failure explanation.
 */
function pot_test_expect( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$category_args = pot_presentation_category_query_args( 7 );
pot_test_expect( array( 7 ) === $category_args['exclude'], 'The configured default category must be excluded.' );
pot_test_expect( ! isset( pot_presentation_category_query_args( 0 )['exclude'] ), 'An unset default category must not add an empty exclusion.' );

$active_checkout = pot_presentation_checkout_classes( array( 'woocommerce-checkout' ), true, false, false, false );
pot_test_expect( in_array( 'pot-enclosed-checkout', $active_checkout, true ), 'The active checkout must receive the enclosed state.' );
pot_test_expect( ! in_array( 'pot-enclosed-checkout', pot_presentation_checkout_classes( array(), true, true, false, false ), true ), 'Order received must keep normal chrome.' );
pot_test_expect( ! in_array( 'pot-enclosed-checkout', pot_presentation_checkout_classes( array(), true, false, true, false ), true ), 'Order pay must keep normal chrome.' );
pot_test_expect( ! in_array( 'pot-enclosed-checkout', pot_presentation_checkout_classes( array(), true, false, false, true ), true ), 'Offer pages own their layout.' );
pot_test_expect( 'https://store.test/contact/' === pot_presentation_checkout_help_url( true, 'publish', 'https://store.test/contact/' ), 'A published contact page must remain accessible from checkout.' );
pot_test_expect( '' === pot_presentation_checkout_help_url( false, 'publish', 'https://store.test/contact/' ), 'Help access must be scoped to active checkout.' );
pot_test_expect( '' === pot_presentation_checkout_help_url( true, 'draft', 'https://store.test/contact/' ), 'A draft contact page must not be exposed.' );

$badge = pot_presentation_sale_badge_html( 19 );
pot_test_expect( 1 === substr_count( $badge, 'Save 19%' ), 'A calculated discount must render exactly one savings message.' );
pot_test_expect( '' === pot_presentation_sale_badge_html( 0 ), 'A missing discount must not manufacture a badge.' );
pot_test_expect( 20 === pot_presentation_discount_percentage( 100.0, 80.0, true ), 'An active valid sale must calculate its real percentage.' );
pot_test_expect( 0 === pot_presentation_discount_percentage( 100.0, 80.0, false ), 'An inactive stored sale price must not create a badge.' );
pot_test_expect( 0 === pot_presentation_discount_percentage( 100.0, 120.0, true ), 'An invalid sale price must not create a badge.' );

pot_test_expect( pot_presentation_suppresses_native_stock( true, 3, 5, true, false ), 'A visible toolkit low-stock warning must replace redundant native availability.' );
pot_test_expect( ! pot_presentation_suppresses_native_stock( true, 0, 5, false, false ), 'Out-of-stock availability must remain visible.' );
pot_test_expect( ! pot_presentation_suppresses_native_stock( true, 3, 5, true, true ), 'Backorder messaging must remain visible.' );
pot_test_expect( ! pot_presentation_suppresses_native_stock( false, null, 5, true, false ), 'Unmanaged stock must retain native availability.' );
pot_test_expect( ! pot_presentation_suppresses_native_stock( true, 8, 5, true, false ), 'Normal in-stock availability above the threshold must remain visible.' );

$native_stock_block = '<div class="wc-block-components-product-stock-indicator">3 in stock</div>';
pot_test_expect( '' === pot_presentation_filter_stock_block( $native_stock_block, 'woocommerce/product-stock-indicator', true ), 'The redundant block stock indicator must be removed.' );
pot_test_expect( $native_stock_block === pot_presentation_filter_stock_block( $native_stock_block, 'woocommerce/product-stock-indicator', false ), 'The stock block must remain when no toolkit warning replaces it.' );
pot_test_expect( $native_stock_block === pot_presentation_filter_stock_block( $native_stock_block, 'core/paragraph', true ), 'Unrelated blocks must never be removed.' );

fwrite( STDOUT, "Storefront presentation contracts passed.\n" );
