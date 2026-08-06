<?php
/**
 * Switch the local demo offer placement for verification.
 *
 * Run with: wp eval-file tools/set-offer-placement.php pre_checkout
 *
 * @package PurpleOptimize
 */

defined( 'ABSPATH' ) || exit;

$placement = sanitize_key( $args[0] ?? '' );
if ( ! in_array( $placement, array( 'pre_checkout', 'checkout_inline', 'post_purchase' ), true ) ) {
	WP_CLI::error( 'Use pre_checkout, checkout_inline, or post_purchase.' );
}

$settings                    = get_option( 'pot_settings', array() );
$settings['offer_placement'] = $placement;
update_option( 'pot_settings', $settings );
WP_CLI::success( 'Offer placement set to ' . $placement . '.' );
