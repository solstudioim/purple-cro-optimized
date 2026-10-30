<?php
/** Combined-stack checks. Run with wp eval-file on an isolated development install. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
$expect = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$expect( function_exists( 'pot_render_workspace' ), 'Companion plugin must be active.' );
$expect( 'purple-optimize' === get_stylesheet() && 'purple' === get_template(), 'Activate the child with its parent for combined checks.' );
$expect( purple_optimize_environment_ready( pot_environment_checks() ), 'Combined version requirements must pass.' );
$original = get_option( 'pot_settings', null );
$checkout = get_option( 'pot_checkout_settings', null );
$post     = $_POST;
$user     = get_current_user_id();
$admin    = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $admin->ID );
pot_register_settings();
try {
	$settings = pot_defaults();
	$disabled = $settings;
	foreach ( array( 'promo_enabled', 'instant_search', 'category_navigation', 'checkout_trust', 'footer_policies', 'post_purchase_account', 'wishlist', 'sticky_cart', 'mobile_sticky_checkout', 'sale_percentage', 'product_guidance', 'checkout_labels', 'free_shipping_threshold' ) as $key ) { $disabled[ $key ] = 0; }
	$expect( ! in_array( true, pot_frontend_feature_flags( $disabled ), true ), 'Explicitly disabled features must enqueue no enhancements.' );
	foreach ( array( 'category_navigation', 'footer_policies', 'sale_percentage' ) as $feature ) {
		$standalone = $disabled;
		$standalone[ $feature ] = 1;
		$expect( pot_frontend_feature_flags( $standalone )['components'], $feature . ' needs its component styles when enabled alone.' );
	}
	$expect( ! is_dir( get_stylesheet_directory() . '/templates' ) && ! is_dir( get_stylesheet_directory() . '/parts' ), 'Child must inherit parent layouts.' );
	$settings['promo_text'] = 'Keep my promotion';
	$settings['promo_background'] = '#d4af37';
	$expect( '#ffffff' === pot_promo_text_color( '#000' ), 'Dark backgrounds require white text.' );
	$expect( '#000000' === pot_promo_text_color( '#fff' ), 'Light backgrounds require black text.' );
	$invalid_color = pot_sanitize_settings( array_merge( $settings, array( 'promo_background' => 'red;position:fixed' ) ) );
	$expect( '' === $invalid_color['promo_background'], 'Custom background must reject CSS injection.' );
	$invalid_color = pot_sanitize_settings( array_merge( $settings, array( 'promo_background' => array( '#fff' ) ) ) );
	$expect( '' === $invalid_color['promo_background'], 'Malformed background must fall back to the parent theme.' );
	$settings['offer_funnel'] = 1;
	$settings['upsell_discount'] = 23;
	$settings['recent_sales'] = 1;
	update_option( 'pot_settings', $settings );
	$before = pot_settings();
	$expect( count( pot_settings_sections() ) === 6, 'All six workspace sections must exist.' );
	foreach ( array( 'storefront', 'products', 'checkout', 'offers', 'privacy' ) as $section ) {
		$keys = pot_settings_section_keys( $section );
		$input = array_intersect_key( $before, array_flip( $keys ) );
		$_POST = array( 'pot_settings_section' => $section, '_wpnonce' => wp_create_nonce( 'pot_settings_group-options' ) );
		$input['promo_text'] = 'Cross-section overwrite attempt';
		if ( 'storefront' === $section ) { $input['promo_text'] = $before['promo_text']; unset( $input['wishlist'] ); }
		$saved = pot_sanitize_settings( $input );
		foreach ( $before as $key => $value ) {
			if ( ! in_array( $key, $keys, true ) ) { $expect( $saved[ $key ] === $value, $section . ' changed unrelated ' . $key ); }
		}
		if ( 'storefront' === $section ) { $expect( 0 === $saved['wishlist'], 'Unchecked controls must turn off.' ); }
	}
	$_POST = array( 'pot_settings_section' => 'privacy', '_wpnonce' => 'invalid' );
	$invalid = pot_sanitize_settings( array( 'recent_sales' => 0 ) );
	foreach ( $before as $key => $value ) { $expect( $invalid[ $key ] === $value, 'Invalid nonce changed ' . $key . ': ' . wp_json_encode( array( $value, $invalid[ $key ] ) ) ); }
	$_POST = array( 'pot_settings_section' => 'unknown', '_wpnonce' => wp_create_nonce( 'pot_settings_group-options' ) );
	$invalid = pot_sanitize_settings( array() );
	foreach ( $before as $key => $value ) { $expect( $invalid[ $key ] === $value, 'Unknown section changed ' . $key ); }
	$_POST = array();
	foreach ( array_keys( pot_settings_sections() ) as $section ) {
		$_GET['section'] = $section;
		ob_start(); pot_render_workspace(); $html = ob_get_clean();
		$expect( false !== strpos( $html, 'aria-current="page"' ), 'Navigation must identify the selected section.' );
		$expect( false !== strpos( $html, 'pot-workspace-main' ), 'Workspace must render.' );
		if ( 'offers' === $section ) { $expect( 1 === substr_count( $html, 'id="pot-offer-enabled"' ), 'Exactly one offer enable control.' ); }
	}
	$expect( $checkout === get_option( 'pot_checkout_settings', null ), 'Rendering/settings tests must not change checkout add-ons.' );
	wp_set_current_user( 0 );
	ob_start(); pot_render_workspace(); $html = ob_get_clean();
	$expect( '' === $html, 'Anonymous users may not view settings.' );
	// Resolve all inherited parent blocks against the actual WordPress/WooCommerce registry.
	$walk = static function ( $blocks ) use ( &$walk, $expect ) {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) { $expect( WP_Block_Type_Registry::get_instance()->is_registered( $block['blockName'] ), 'Unregistered block: ' . $block['blockName'] ); }
			$walk( $block['innerBlocks'] ?? array() );
		}
	};
	foreach ( array_merge( glob( get_template_directory() . '/templates/*.html' ), glob( get_template_directory() . '/parts/*.html' ) ) as $file ) { $walk( parse_blocks( file_get_contents( $file ) ) ); }
	WP_CLI::success( 'Combined version checks, six workspace sections, scoped saves, unrelated settings preservation, nonce rejection, access control, preview rendering and registered blocks passed.' );
} finally {
	$_POST = array();
	if ( null === $original ) { delete_option( 'pot_settings' ); } else { update_option( 'pot_settings', $original ); }
	$_POST = $post;
	wp_set_current_user( $user );
}
