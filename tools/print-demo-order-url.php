<?php
/**
 * Print the local seeded order-received URL for browser verification.
 *
 * @package PurpleOptimize
 */

defined( 'ABSPATH' ) || exit;

$orders = wc_get_orders( array( 'limit' => 1, 'meta_key' => '_pot_demo_order', 'meta_value' => '1' ) );
if ( ! $orders ) {
	WP_CLI::error( 'No seeded demo order found.' );
}
WP_CLI::line( $orders[0]->get_checkout_order_received_url() );
