<?php
/** Session lifecycle regression, run only on an isolated local WordPress site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! preg_match( '/^(localhost|127\.0\.0\.1)$/', wp_parse_url( home_url(), PHP_URL_HOST ) ) ) { throw new RuntimeException( 'Local fixture only.' ); }
$fixture = get_option( 'pot_checkout_test_fixture' );
if ( ! $fixture ) { throw new RuntimeException( 'Prepare checkout fixtures first.' ); }
$expect = static function ( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } };
wc_load_cart();
try {
 WC()->cart->empty_cart();
 WC()->cart->add_to_cart( $fixture['products'][0], 1 );
 $settings = pot_defaults();
 $settings['offer_funnel'] = 1;
 $settings['upsell_product_id'] = $fixture['products'][1];
 WC()->session->set( 'pot_offer_funnel_complete', 1 );
 WC()->session->set( 'pot_post_purchase_parent_order', 123 );
 WC()->session->set( 'pot_inline_offer_stage', 'resolved' );
 pot_sync_offer_session( $settings );
 $expect( ! WC()->session->get( 'pot_offer_funnel_complete' ), 'Legacy completion must not suppress a newly scoped offer.' );
 $expect( 123 === WC()->session->get( 'pot_post_purchase_parent_order' ), 'Context refresh must preserve the separate follow-up order association.' );
 WC()->session->set( 'pot_offer_funnel_complete', 1 );
 WC()->session->set( 'pot_offer_expiry_upsell', array( 'product_id' => $fixture['products'][1], 'expires' => time() + 60 ) );
 $expiry = WC()->session->get( 'pot_offer_expiry_upsell' );
 pot_sync_offer_session( $settings );
 $expect( 1 === WC()->session->get( 'pot_offer_funnel_complete' ), 'An unchanged cart must keep its decision.' );
 $expect( $expiry === WC()->session->get( 'pot_offer_expiry_upsell' ), 'Refreshing must not restart the timer.' );
 WC()->cart->add_to_cart( $fixture['products'][1], 1, 0, array(), array( 'pot_offer_step' => 'upsell' ) );
 pot_sync_offer_session( $settings );
 $expect( 1 === WC()->session->get( 'pot_offer_funnel_complete' ), 'An accepted offer must not restart the funnel.' );
 $settings['upsell_discount'] = 25;
 pot_sync_offer_session( $settings );
 $expect( ! WC()->session->get( 'pot_offer_funnel_complete' ), 'Changed offer configuration must be reconsidered.' );
 WC()->session->set( 'pot_offer_funnel_complete', 1 );
 WC()->cart->add_to_cart( $fixture['products'][2], 1 );
 pot_sync_offer_session( $settings );
 $expect( ! WC()->session->get( 'pot_offer_funnel_complete' ), 'A changed normal cart must be reconsidered.' );
 WC()->cart->empty_cart();
 WC()->session->set( 'pot_inline_offer_stage', 'resolved' );
 WC()->cart->add_to_cart( $fixture['products'][0], 1 );
 $expect( ! WC()->session->get( 'pot_inline_offer_stage' ), 'First normal item must clear stale inline-only state.' );
 WP_CLI::success( 'Legacy state, unchanged-cart decisions/timers, accepted offers, configuration/cart changes and new-cart inline reset passed.' );
} finally {
 WC()->cart->empty_cart();
}
