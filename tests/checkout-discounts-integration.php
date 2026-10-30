<?php
/** Pricing tests on reversible local checkout fixtures; no payment is submitted. */
$args = array( 'prepare' );
require __DIR__ . '/checkout-extras-integration.php';
$original_user = get_current_user_id();
$order = null;
$coupon = null;
$tax_rate = 0;
$tax_class = '';
$tax_options = array();
try {
	$id = $fixture['products'][1];
	$settings = pot_checkout_settings();
	pot_checkout_expect( 0 === pot_checkout_accepted_discount( array( 'pot_checkout_upsell' => true, 'pot_checkout_discount' => 20 ) ), 'Incomplete session data must not advertise a discount.' );
	pot_checkout_expect( 0 === pot_checkout_accepted_discount( array( 'pot_checkout_upsell' => true, 'pot_checkout_discount' => 20, 'pot_checkout_base_price' => array( 15 ) ) ), 'Malformed stored prices must not affect totals.' );
	foreach ( array( '' => 0, '0' => 0, '20' => 20, '-10' => 0, '500' => 100, 'invalid' => 0 ) as $input => $expected ) {
		pot_checkout_expect( $expected === pot_checkout_discount( (string) $input ), 'Discount input must be safe and bounded.' );
	}
	pot_checkout_expect( 0 === pot_checkout_discount( array( 20 ) ), 'Malformed discount arrays must not create a discount.' );
	foreach ( array( '', '0' ) as $value ) {
		$copy = $settings;
		$copy['upsells'][0]['discount'] = $value;
		update_option( 'pot_checkout_settings', pot_sanitize_checkout_settings( $copy ) );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $fixture['products'][0], 1 );
		pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true, 'discount' => 100, 'price' => 0 ) );
		WC()->cart->calculate_totals();
		pot_checkout_expect( 115.0 === (float) WC()->cart->get_subtotal(), 'Blank/zero must keep catalog sale price and ignore spoofed discounts.' );
		pot_checkout_expect( 0 === pot_checkout_cart_data()['offers'][0]['discount'], 'No extra saving may be advertised for blank/zero.' );
	}
	// Reproduce the reported 124 -> 117.80 example without changing the arithmetic.
	$product = wc_get_product( $id );
	$product->set_regular_price( '124' );
	$product->set_sale_price( '' );
	$product->save();
	$settings['upsells'][0]['discount'] = 5;
	update_option( 'pot_checkout_settings', pot_sanitize_checkout_settings( $settings ) );
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $fixture['products'][0], 1 );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) );
	WC()->cart->calculate_totals();
	$offer = pot_checkout_cart_data()['offers'][0];
	pot_checkout_expect( 5 === $offer['discount'] && '5' === $offer['saving_percentage'] && false !== strpos( $offer['price'], '117.80' ) && false !== strpos( $offer['savings'], '6.20' ), '5% of 124 is a currency saving of 6.20, not 6.2%.' );
	$precision = get_option( 'woocommerce_price_num_decimals', false );
	try {
		update_option( 'woocommerce_price_num_decimals', 2 );
		pot_checkout_expect( 0.0 === pot_checkout_savings( 1.004, 0.996 ), 'Round each price like the Store API before subtracting savings.' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		pot_checkout_expect( 6.0 === pot_checkout_savings( 124, 117.8 ), 'Zero-decimal currency savings must match native rounding.' );
		update_option( 'woocommerce_price_num_decimals', 3 );
		pot_checkout_expect( abs( 0.008 - pot_checkout_savings( 1.004, 0.996 ) ) < 0.000001, 'Three-decimal currencies must retain their precision.' );
	} finally {
		if ( false === $precision ) { delete_option( 'woocommerce_price_num_decimals' ); } else { update_option( 'woocommerce_price_num_decimals', $precision ); }
	}
	$product->set_regular_price( '20' );
	$product->set_sale_price( '15' );
	$product->save();
	$settings['upsells'][0]['discount'] = 20;
	update_option( 'pot_checkout_settings', pot_sanitize_checkout_settings( $settings ) );
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $fixture['products'][0], 1 );
	$data = pot_checkout_cart_data()['offers'][0];
	pot_checkout_expect( 20 === $data['discount'] && false !== strpos( $data['price'], '12.00' ) && false !== strpos( $data['price'], '20.00' ) && false !== strpos( $data['savings'], '8.00' ), 'Preview must match native regular/current display while applying 20% to the current sale price.' );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true, 'discount' => 100 ) );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) );
	WC()->cart->calculate_totals();
	WC()->cart->calculate_totals();
	$lines = pot_checkout_product_lines( $id );
	$accepted_key = array_key_first( $lines );
	$accepted = $lines[ $accepted_key ];
	pot_checkout_expect( 112.0 === (float) WC()->cart->get_subtotal(), 'Repeated calculations must not compound the discount.' );
	pot_checkout_expect( 20 === $accepted['pot_checkout_discount'] && 15.0 === $accepted['pot_checkout_base_price'], 'Accepted terms must be server-owned snapshots.' );
	pot_checkout_expect( 15.0 === (float) wc_get_product( $id )->get_price(), 'Catalog price must never be changed.' );
	$normal_key = WC()->cart->add_to_cart( $id, 1 );
	WC()->cart->calculate_totals();
	pot_checkout_expect( 127.0 === (float) WC()->cart->get_subtotal() && 15.0 === (float) WC()->cart->get_cart_item( $normal_key )['data']->get_price(), 'A normal catalog addition must stay full price beside the discounted line.' );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => false ) );
	WC()->cart->calculate_totals();
	pot_checkout_expect( 115.0 === (float) WC()->cart->get_subtotal() && isset( pot_checkout_product_lines( $id )[ $normal_key ] ), 'Removal must leave the normal product untouched.' );
	pot_checkout_expect( 0 === pot_checkout_cart_data()['offers'][0]['discount'], 'Already-in-cart normal products must not advertise an unavailable add-on discount.' );
	WC()->cart->remove_cart_item( $normal_key );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) );
	$settings['upsells'][0]['discount'] = 50;
	update_option( 'pot_checkout_settings', pot_sanitize_checkout_settings( $settings ) );
	WC()->cart->calculate_totals();
	pot_checkout_expect( 112.0 === (float) WC()->cart->get_subtotal() && 20 === pot_checkout_cart_data()['offers'][0]['discount'], 'Changing configuration must preserve an already accepted price.' );
	$session = WC()->cart->get_cart_for_session();
	WC()->cart->empty_cart();
	foreach ( $session as $key => $line ) {
		$line['data'] = wc_get_product( $line['product_id'] );
		WC()->cart->cart_contents[ $key ] = $line;
	}
	WC()->cart->calculate_totals();
	pot_checkout_expect( 112.0 === (float) WC()->cart->get_subtotal(), 'Restoring products and metadata from session must restore accepted pricing.' );
	$lines = pot_checkout_product_lines( $id );
	$line = reset( $lines );
	pot_checkout_expect( false !== strpos( wp_json_encode( pot_checkout_addon_item_data( array(), $line ) ), '20% off' ), 'Native cart details must identify the accepted discount.' );
	$order = wc_create_order();
	WC()->checkout()->create_order_line_items( $order, WC()->cart );
	$order->save();
	$found = false;
	foreach ( $order->get_items() as $order_item ) {
		if ( $id === $order_item->get_product_id() ) {
			$found = 12.0 === (float) $order_item->get_total() && '20% off' === $order_item->get_meta( 'Checkout add-on discount' );
		}
	}
	pot_checkout_expect( $found, 'The actual order line must retain the discounted amount and metadata.' );
	$coupon = new WC_Coupon();
	$coupon->set_code( 'pot-addon-fixture-' . $id );
	$coupon->set_discount_type( 'percent' );
	$coupon->set_amount( 10 );
	$coupon->save();
	WC()->cart->apply_coupon( $coupon->get_code() );
	WC()->cart->calculate_totals();
	pot_checkout_expect( abs( 11.2 - (float) WC()->cart->get_discount_total() ) < 0.001, 'Native coupons must calculate from the accepted add-on price.' );
	WC()->cart->remove_coupons();
	// Tax-inclusive display must use the discounted amount, not the catalog suffix price.
	foreach ( array( 'woocommerce_calc_taxes', 'woocommerce_prices_include_tax', 'woocommerce_tax_display_cart', 'woocommerce_tax_based_on' ) as $key ) { $tax_options[ $key ] = get_option( $key, false ); }
	update_option( 'woocommerce_calc_taxes', 'yes' );
	update_option( 'woocommerce_prices_include_tax', 'no' );
	update_option( 'woocommerce_tax_display_cart', 'incl' );
	update_option( 'woocommerce_tax_based_on', 'shipping' );
	$tax_class = 'pot-addon-' . $id;
	$created_class = WC_Tax::create_tax_class( 'POT add-on fixture ' . $id, $tax_class );
	pot_checkout_expect( ! is_wp_error( $created_class ), 'Create an isolated tax class for the pricing fixture.' );
	$tax_rate = WC_Tax::_insert_tax_rate( array( 'tax_rate_country' => 'US', 'tax_rate_state' => '', 'tax_rate' => '10', 'tax_rate_name' => 'Fixture tax', 'tax_rate_priority' => 1, 'tax_rate_compound' => 0, 'tax_rate_shipping' => 0, 'tax_rate_order' => 1, 'tax_rate_class' => $tax_class ) );
	$product = wc_get_product( $id );
	$product->set_tax_status( 'taxable' );
	$product->set_tax_class( $tax_class );
	pot_checkout_expect( $tax_class === $product->get_tax_class(), 'Tax checks must use the isolated class, not an existing store rate.' );
	$product->save();
	WC()->customer->set_shipping_country( 'US' );
	WC()->customer->set_shipping_state( 'CA' );
	WC()->customer->set_is_vat_exempt( false );
	WC()->cart->cart_contents[ array_key_first( pot_checkout_product_lines( $id ) ) ]['data'] = wc_get_product( $id );
	WC()->cart->calculate_totals();
	pot_checkout_expect( abs( 1.2 - (float) WC()->cart->get_subtotal_tax() ) < 0.001, 'Native tax must use the discounted $12 line.' );
	pot_checkout_expect( false !== strpos( pot_checkout_cart_data()['offers'][0]['price'], '13.20' ), 'Tax-inclusive offer price must match the taxable cart line.' );
	// Maximum and changed settings apply only to a new acceptance.
	$settings['upsells'][0]['discount'] = 100;
	update_option( 'pot_checkout_settings', pot_sanitize_checkout_settings( $settings ) );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => false ) );
	pot_update_checkout_upsell( array( 'id' => $id, 'selected' => true ) );
	WC()->cart->calculate_totals();
	pot_checkout_expect( 100.0 === (float) WC()->cart->get_subtotal(), '100% discount must make only the accepted add-on free.' );
	WP_CLI::success( 'Blank/zero discounts, server validation, sale pricing, repeated totals, catalog isolation, accepted snapshots/session restoration, native order values, coupons and taxes passed.' );
} finally {
	wp_set_current_user( $original_user );
	WC()->cart->empty_cart();
	if ( $order ) { $order->delete( true ); }
	if ( $coupon ) { $coupon->delete( true ); }
	if ( $tax_rate ) { WC_Tax::_delete_tax_rate( $tax_rate ); }
	if ( $tax_class ) { WC_Tax::delete_tax_class_by( 'slug', $tax_class ); }
	foreach ( $tax_options as $key => $value ) { if ( false === $value ) { delete_option( $key ); } else { update_option( $key, $value ); } }
	pot_checkout_cleanup_fixture();
}
