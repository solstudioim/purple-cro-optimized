<?php
/**
 * Plugin Name: Purple Optimize Toolkit
 * Description: Lightweight, evidence-based conversion features for WooCommerce and the Purple Optimize child theme.
 * Version: 0.11.10
 * Requires at least: 7.1
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 11.1
 * Plugin URI: https://github.com/solstudioim/purple-cro-optimized
 * Author: Sol J
 * Author URI: https://github.com/solstudioim
 * License: GPL-2.0-or-later
 * Text Domain: purple-optimize-toolkit
 *
 * @package PurpleOptimizeToolkit
 */

defined( 'ABSPATH' ) || exit;

define( 'POT_VERSION', '0.11.10' );
define( 'POT_FILE', __FILE__ );
define( 'POT_PATH', plugin_dir_path( __FILE__ ) );
define( 'POT_URL', plugin_dir_url( __FILE__ ) );

require_once POT_PATH . 'includes/storefront-presentation.php';
require_once POT_PATH . 'includes/checkout-extras.php';
require_once POT_PATH . 'includes/checkout-content.php';
require_once POT_PATH . 'includes/admin-settings.php';
require_once POT_PATH . 'includes/reviews.php';

/** Declare compatibility for WooCommerce storage and checkout features used by this plugin. */
function pot_declare_woocommerce_compatibility(): void {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', POT_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', POT_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'pot_declare_woocommerce_compatibility' );


/**
 * Default plugin settings.
 *
 * @return array<string, mixed>
 */
function pot_defaults(): array {
	return array(
		'promo_enabled'          => 1,
		'promo_text'             => __( 'Free shipping on qualifying orders', 'purple-optimize-toolkit' ),
		'promo_code'             => '',
		'promo_background'       => '',
		'instant_search'         => 1,
		'category_navigation'    => 1,
		'checkout_trust'         => 1,
		'footer_policies'        => 1,
		'post_purchase_account'  => 1,
		'wishlist'               => 1,
		'sticky_cart'            => 1,
		'mobile_sticky_checkout'=> 1,
		'sale_percentage'        => 1,
		'recent_sales'           => 0,
		'social_proof_show_names'=> 0,
		'social_proof_days'      => 30,
		'offer_funnel'           => 0,
		'offer_placement'        => 'pre_checkout',
		'upsell_product_id'      => 0,
		'upsell_discount'        => 50,
		'upsell_countdown'       => 10,
		'downsell_product_id'    => 0,
		'downsell_discount'      => 70,
		'downsell_countdown'     => 10,
		'product_guidance'      => 1,
		'checkout_labels'       => 1,
		'stock_threshold'        => 5,
		'free_shipping_threshold'=> 75,
		'reassurance_one'        => __( 'Delivery details shown at checkout', 'purple-optimize-toolkit' ),
		'reassurance_two'        => __( 'Returns policy available before purchase', 'purple-optimize-toolkit' ),
		'reassurance_three'      => __( 'Support is available when you need it', 'purple-optimize-toolkit' ),
	);
}

/**
 * Get merged settings.
 *
 * @return array<string, mixed>
 */
function pot_settings(): array {
	return wp_parse_args( get_option( 'pot_settings', array() ), pot_defaults() );
}

/**
 * Seed defaults without overwriting existing choices.
 */
function pot_activate(): void {
	add_option( 'pot_settings', pot_defaults() );
	pot_register_offer_route();
	flush_rewrite_rules();
	update_option( 'pot_version', POT_VERSION );
}
register_activation_hook( __FILE__, 'pot_activate' );

/**
 * Flush the dedicated offer route when the plugin is deactivated.
 */
function pot_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pot_deactivate' );

/**
 * Flush rewrite rules once when an active install receives a route upgrade.
 */
function pot_maybe_upgrade(): void {
	if ( POT_VERSION === get_option( 'pot_version' ) ) {
		return;
	}
	pot_register_offer_route();
	flush_rewrite_rules();
	update_option( 'pot_version', POT_VERSION );
}
add_action( 'init', 'pot_maybe_upgrade', 20 );

/**
 * Report a missing WooCommerce dependency.
 */
function pot_dependency_notice(): void {
	if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Purple Optimize Toolkit needs WooCommerce to provide storefront features.', 'purple-optimize-toolkit' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'pot_dependency_notice' );

/**
 * Register the settings array.
 */
function pot_register_settings(): void {
	register_setting(
		'pot_settings_group',
		'pot_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pot_sanitize_settings',
			'default'           => pot_defaults(),
		)
	);
}
add_action( 'admin_init', 'pot_register_settings' );

/**
 * Match Settings API access to the capability used by this settings screen.
 *
 * @since 0.8.1
 * @return string Required capability.
 */
function pot_settings_capability(): string {
	return 'manage_woocommerce';
}
add_filter( 'option_page_capability_pot_settings_group', 'pot_settings_capability' );

/**
 * Save only the offer-funnel switch, leaving product and placement choices intact.
 *
 * @since 0.8.1
 * @return void
 */
function pot_ajax_set_offer_enabled(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot change these settings.', 'purple-optimize-toolkit' ) ), 403 );
	}
	check_ajax_referer( 'pot_set_offer_enabled', 'nonce' );
	$enabled = isset( $_POST['enabled'] ) ? wp_unslash( $_POST['enabled'] ) : null;
	if ( ! in_array( $enabled, array( '0', '1' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Choose enabled or disabled.', 'purple-optimize-toolkit' ) ), 400 );
	}
	$settings = pot_settings();
	if ( '1' === $enabled ) {
		$issue = pot_offer_product_issue( absint( $settings['upsell_product_id'] ?? 0 ), true );
		if ( '' === $issue ) { $issue = pot_offer_product_issue( absint( $settings['downsell_product_id'] ?? 0 ) ); }
		if ( $issue ) { wp_send_json_error( array( 'message' => pot_offer_product_issue_message( $issue ) ), 400 ); }
	}
	$settings['offer_funnel'] = (int) $enabled;
	update_option( 'pot_settings', $settings );
	$saved = ! empty( pot_settings()['offer_funnel'] );
	if ( $saved !== ( '1' === $enabled ) ) {
		wp_send_json_error( array( 'message' => __( 'The setting could not be saved. Please try again.', 'purple-optimize-toolkit' ) ), 500 );
	}
	wp_send_json_success( array( 'enabled' => $saved ) );
}
add_action( 'wp_ajax_pot_set_offer_enabled', 'pot_ajax_set_offer_enabled' );

/**
 * Sanitize settings submitted through the Settings API.
 *
 * @param mixed $input Raw settings.
 * @return array<string, mixed>
 */
function pot_sanitize_settings( $input ): array {
	$input    = pot_scope_settings_input( is_array( $input ) ? $input : array() );
	$defaults = pot_defaults();
	$output   = array();

	foreach ( array( 'promo_enabled', 'instant_search', 'category_navigation', 'checkout_trust', 'footer_policies', 'post_purchase_account', 'wishlist', 'sticky_cart', 'mobile_sticky_checkout', 'sale_percentage', 'recent_sales', 'social_proof_show_names', 'offer_funnel', 'product_guidance', 'checkout_labels' ) as $key ) {
		$output[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
	}

	$output['promo_text']              = sanitize_text_field( $input['promo_text'] ?? $defaults['promo_text'] );
	$output['promo_code']              = sanitize_text_field( $input['promo_code'] ?? '' );
	$output['promo_background']        = sanitize_hex_color( is_string( $input['promo_background'] ?? null ) ? $input['promo_background'] : '' ) ?: '';
	$output['stock_threshold']         = max( 0, min( 100, absint( $input['stock_threshold'] ?? $defaults['stock_threshold'] ) ) );
	$output['free_shipping_threshold'] = max( 0, (float) ( $input['free_shipping_threshold'] ?? $defaults['free_shipping_threshold'] ) );
	$output['social_proof_days']       = max( 1, min( 365, absint( $input['social_proof_days'] ?? $defaults['social_proof_days'] ) ) );
	$output['upsell_product_id']       = absint( $input['upsell_product_id'] ?? 0 );
	$output['upsell_discount']         = max( 0, min( 100, absint( $input['upsell_discount'] ?? $defaults['upsell_discount'] ) ) );
	$output['upsell_countdown']        = max( 0, min( 1440, absint( $input['upsell_countdown'] ?? $defaults['upsell_countdown'] ) ) );
	$output['downsell_product_id']     = absint( $input['downsell_product_id'] ?? 0 );
	$output['downsell_discount']       = max( 0, min( 100, absint( $input['downsell_discount'] ?? $defaults['downsell_discount'] ) ) );
	$output['downsell_countdown']      = max( 0, min( 1440, absint( $input['downsell_countdown'] ?? $defaults['downsell_countdown'] ) ) );
	$placement                         = sanitize_key( $input['offer_placement'] ?? $defaults['offer_placement'] );
	$output['offer_placement']         = in_array( $placement, array( 'pre_checkout', 'checkout_inline', 'post_purchase' ), true ) ? $placement : $defaults['offer_placement'];
	$output['reassurance_one']         = sanitize_text_field( $input['reassurance_one'] ?? $defaults['reassurance_one'] );
	$output['reassurance_two']         = sanitize_text_field( $input['reassurance_two'] ?? $defaults['reassurance_two'] );
	$output['reassurance_three']       = sanitize_text_field( $input['reassurance_three'] ?? $defaults['reassurance_three'] );

	return $output;
}

/**
 * Register the WooCommerce submenu.
 */
function pot_admin_menu(): void {
	add_submenu_page(
		class_exists( 'WooCommerce' ) ? 'woocommerce' : 'options-general.php',
		__( 'Purple Optimize', 'purple-optimize-toolkit' ),
		__( 'Purple Optimize', 'purple-optimize-toolkit' ),
		'manage_woocommerce',
		'purple-optimize',
		'pot_render_settings_page'
	);
}
add_action( 'admin_menu', 'pot_admin_menu' );

/**
 * Load WooCommerce's product search control on this plugin's settings screen.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function pot_admin_assets( string $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, array( 'woocommerce_page_purple-optimize', 'settings_page_purple-optimize' ), true ) ) {
		return;
	}
	wp_enqueue_style( 'woocommerce_admin_styles' );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'pot-admin-settings', POT_URL . 'assets/admin-settings.css', array(), POT_VERSION );
	wp_enqueue_script( 'pot-admin-settings', POT_URL . 'assets/admin-settings.js', array( 'jquery', 'wc-enhanced-select', 'wp-color-picker' ), POT_VERSION, true );
	wp_enqueue_script( 'wc-enhanced-select' );
	pot_reviews_assets();
	wp_localize_script( 'pot-admin-settings', 'potReviewBuilder', array(
		'url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'pot_preview_reviews' ),
		'loading' => __( 'Generating preview…', 'purple-optimize-toolkit' ),
		'error' => __( 'Could not generate the preview. Check your selection and try again.', 'purple-optimize-toolkit' ),
		'changed' => __( 'Selection changed. Generate a new shortcode and preview.', 'purple-optimize-toolkit' ),
		'copied' => __( 'Shortcode copied.', 'purple-optimize-toolkit' ),
		'copyHelp' => __( 'Select and copy the shortcode below.', 'purple-optimize-toolkit' ),
	) );
	wp_enqueue_script( 'pot-admin-offer-toggle', POT_URL . 'assets/admin-offer-toggle.js', array(), POT_VERSION, true );
	wp_localize_script( 'pot-admin-offer-toggle', 'potOfferToggle', array(
		'url'      => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'pot_set_offer_enabled' ),
		'saving'   => __( 'Saving…', 'purple-optimize-toolkit' ),
		'enabled'  => __( 'Enabled — saved.', 'purple-optimize-toolkit' ),
		'disabled' => __( 'Disabled — saved.', 'purple-optimize-toolkit' ),
		'error'    => __( 'Could not save. Please try again or use Save Changes.', 'purple-optimize-toolkit' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'pot_admin_assets' );

/**
 * Output one checkbox settings row.
 *
 * @param string $key      Setting key.
 * @param string $label    Label.
 * @param array  $settings Current settings.
 */
function pot_checkbox_row( string $key, string $label, array $settings ): void {
	?>
	<tr>
		<th scope="row"><label for="pot-setting-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td><label><input type="checkbox" id="pot-setting-<?php echo esc_attr( $key ); ?>" name="pot_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>> <?php esc_html_e( 'Enabled', 'purple-optimize-toolkit' ); ?></label></td>
	</tr>
	<?php
}

/**
 * Return the static eligibility issue for a WooCommerce offer product.
 *
 * @param WC_Product|false $product Selected product, or false when missing.
 * @return string Stable issue code, or an empty string when eligible.
 */
function pot_offer_product_issue_for_product( $product ): string {
	return pot_presentation_offer_product_issue(
		(bool) $product,
		$product ? $product->get_type() : '',
		$product ? $product->get_status() : '',
		$product ? $product->is_visible() : false,
		$product ? $product->is_purchasable() : false,
		$product ? $product->is_in_stock() : false
	);
}

/**
 * Return an actionable admin message for an offer-product issue.
 *
 * @param string $issue Stable issue code.
 * @return string Localized message, or an empty string when eligible.
 */
function pot_offer_product_issue_message( string $issue ): string {
	switch ( $issue ) {
		case 'not_selected':
			return __( 'Select a published, in-stock simple product before enabling offers.', 'purple-optimize-toolkit' );
		case 'missing':
			return __( 'The selected product no longer exists. Select a different product.', 'purple-optimize-toolkit' );
		case 'not_simple':
			return __( 'The selected product is not a simple product. Select a different simple product.', 'purple-optimize-toolkit' );
		case 'not_published':
			return __( 'The selected product is not published. Publish it or select a different product.', 'purple-optimize-toolkit' );
		case 'not_visible':
			return __( 'The selected product is hidden from the catalog. Make it visible or select a different product.', 'purple-optimize-toolkit' );
		case 'not_purchasable':
			return __( 'The selected product cannot currently be purchased. Update it or select a different product.', 'purple-optimize-toolkit' );
		case 'out_of_stock':
			return __( 'The selected product is out of stock. Restock it or select a different product.', 'purple-optimize-toolkit' );
		default:
			return '';
	}
}

/**
 * Return the issue for one configured product ID.
 *
 * @param int  $product_id Selected product ID.
 * @param bool $required   Whether an empty selection is invalid.
 * @return string Stable issue code, or an empty string when eligible.
 */
function pot_offer_product_issue( int $product_id, bool $required = false ): string {
	if ( 0 === $product_id ) {
		return $required ? 'not_selected' : '';
	}

	return pot_offer_product_issue_for_product( wc_get_product( $product_id ) );
}

/**
 * Output a searchable simple-product setting.
 *
 * @param string $key      Setting key.
 * @param string $label    Field label.
 * @param array  $settings Current settings.
 * @param bool   $required Whether the offer requires a selection.
 */
function pot_product_select_row( string $key, string $label, array $settings, bool $required = false ): void {
	$product_id = absint( $settings[ $key ] ?? 0 );
	$product    = $product_id ? wc_get_product( $product_id ) : false;
	$issue      = pot_offer_product_issue( $product_id, $required );
	$warning_id = 'pot-' . $key . '-warning';
	$help_id    = 'pot-' . $key . '-help';
	?>
	<tr>
		<th scope="row"><label for="pot-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<select class="wc-product-search" id="pot-<?php echo esc_attr( $key ); ?>" name="pot_settings[<?php echo esc_attr( $key ); ?>]" style="width: 400px;" data-placeholder="<?php esc_attr_e( 'Search for a simple product…', 'purple-optimize-toolkit' ); ?>" data-action="woocommerce_json_search_products" data-allow_clear="true" aria-describedby="<?php echo esc_attr( $help_id . ( $issue ? ' ' . $warning_id : '' ) ); ?>">
				<?php if ( $product ) : ?>
				<option value="<?php echo esc_attr( (string) $product_id ); ?>" selected><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
				<?php elseif ( $product_id ) : ?>
				<option value="<?php echo esc_attr( (string) $product_id ); ?>" selected><?php echo esc_html( sprintf( __( 'Product #%d (missing)', 'purple-optimize-toolkit' ), $product_id ) ); ?></option>
				<?php endif; ?>
			</select>
			<p class="description" id="<?php echo esc_attr( $help_id ); ?>"><?php esc_html_e( 'Only a published, visible, purchasable, in-stock simple product can be offered.', 'purple-optimize-toolkit' ); ?></p>
			<?php if ( $issue ) : ?>
				<p class="notice notice-error inline pot-offer-product-warning" id="<?php echo esc_attr( $warning_id ); ?>"><strong><?php esc_html_e( 'Offer unavailable:', 'purple-optimize-toolkit' ); ?></strong> <?php echo esc_html( pot_offer_product_issue_message( $issue ) ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render the Settings API form.
 */
function pot_render_settings_page(): void {
	pot_render_workspace();
}

/**
 * Return the runtime features relevant to the current route.
 *
 * The bundle remains cacheable while inactive features avoid observers, timers,
 * storage access, and DOM work on routes where they cannot render.
 *
 * @param array<string, mixed> $settings Plugin settings.
 * @return array<string, bool>
 */
function pot_frontend_feature_flags(array $settings): array {
	$is_offer   = pot_is_offer_page();
	$is_product = is_product();
	$is_cart    = is_cart();
	$is_checkout = is_checkout();
	$is_account = is_account_page();

	return array(
		'components'      => ( ! pot_is_offer_page() && ! pot_is_active_checkout() && ( ! empty( $settings['category_navigation'] ) || ! empty( $settings['footer_policies'] ) ) ) || ! empty( $settings['sale_percentage'] ) || ( ( $is_cart || pot_is_active_checkout() ) && ! empty( $settings['mobile_sticky_checkout'] ) ),
		'promo'           => ! empty( $settings['promo_enabled'] ) && ! $is_offer && ! pot_is_active_checkout(),
		'search'          => ! empty( $settings['instant_search'] ) && ! $is_offer && ! pot_is_active_checkout(),
		'wishlist'        => ! empty( $settings['wishlist'] ) && ! $is_offer && ! pot_is_active_checkout(),
		'countdowns'      => ( $is_product && ! empty( $settings['product_guidance'] ) ) || $is_offer,
		'product'         => $is_product && ! empty( $settings['sticky_cart'] ),
		'cart'            => ( $is_cart || pot_is_active_checkout() ) && (float) $settings['free_shipping_threshold'] > 0,
		'checkout'        => pot_is_active_checkout() && ! empty( $settings['checkout_labels'] ),
		'commerce'        => ( $is_cart || $is_checkout || $is_account || $is_offer ) && ( (float) $settings['free_shipping_threshold'] > 0 || ! empty( $settings['checkout_trust'] ) || ! empty( $settings['post_purchase_account'] ) || ! empty( $settings['offer_funnel'] ) ),
		'offer'           => $is_offer || ( ! empty( $settings['offer_funnel'] ) && ( $is_cart || $is_checkout ) ),
		'recentPurchases' => ! empty( $settings['recent_sales'] ) && ! $is_offer && ! $is_cart && ! $is_checkout && ! $is_account,
	);
}

/**
 * Enqueue the small front-end bundle.
 */
function pot_enqueue_assets(): void {
	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$settings = pot_settings();
	if ( ! in_array( true, pot_frontend_feature_flags( $settings ), true ) ) {
		return;
	}
	$category = is_product_category() ? get_queried_object_id() : 0;
	wp_enqueue_style( 'purple-optimize-toolkit', POT_URL . 'assets/toolkit.css', array(), POT_VERSION );
	wp_enqueue_script(
		'purple-optimize-toolkit',
		POT_URL . 'assets/toolkit.js',
		array(),
		POT_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'purple-optimize-toolkit',
		'purpleOptimize',
		array(
			'features'          => pot_frontend_feature_flags( $settings ),
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'pot_search_products' ),
			'instantSearch'     => ! empty( $settings['instant_search'] ),
			'wishlist'          => ! empty( $settings['wishlist'] ),
			'freeShipping'      => (float) $settings['free_shipping_threshold'],
			'currencySymbol'    => get_woocommerce_currency_symbol(),
			'noResults'         => __( 'No matching products found.', 'purple-optimize-toolkit' ),
			'searching'         => __( 'Searching…', 'purple-optimize-toolkit' ),
			'addWishlist'       => __( 'Save to wishlist', 'purple-optimize-toolkit' ),
			'removeWishlist'    => __( 'Remove from wishlist', 'purple-optimize-toolkit' ),
			'copied'            => __( 'Copied', 'purple-optimize-toolkit' ),
			'chooseOptions'     => __( 'Choose options', 'purple-optimize-toolkit' ),
			'offerExpired'      => __( 'Offer expired', 'purple-optimize-toolkit' ),
			'category'          => absint( $category ),
			'requiredLabel'     => __( 'Required', 'purple-optimize-toolkit' ),
			'optionalLabel'     => __( 'Optional', 'purple-optimize-toolkit' ),
			'daysLabel'         => __( 'Days', 'purple-optimize-toolkit' ),
			'hoursLabel'        => __( 'Hours', 'purple-optimize-toolkit' ),
			'minutesLabel'      => __( 'Minutes', 'purple-optimize-toolkit' ),
			'secondsLabel'      => __( 'Seconds', 'purple-optimize-toolkit' ),
			'cartUrl'           => wc_get_cart_url(),
			'addedToCart'       => __( 'Added to cart ✓', 'purple-optimize-toolkit' ),
			'freeShippingReached' => __( 'You reached the configured free-shipping threshold.', 'purple-optimize-toolkit' ),
			'freeShippingRemaining' => __( 'Add %s more to reach the configured free-shipping threshold.', 'purple-optimize-toolkit' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'pot_enqueue_assets', 40 );

/**
 * Identify the normal checkout form without receipt, payment, or offer routes.
 *
 * @return bool
 */
function pot_is_active_checkout(): bool {
	return is_checkout()
		&& ! is_wc_endpoint_url( 'order-received' )
		&& ! is_wc_endpoint_url( 'order-pay' )
		&& ! pot_is_offer_page();
}

/**
 * Add the opt-in CSS scope for native mobile cart and checkout actions.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function pot_body_classes( array $classes ): array {
	$settings = pot_settings();
	if ( ! empty( $settings['mobile_sticky_checkout'] ) && ( is_cart() || pot_is_active_checkout() ) ) {
		$classes[] = 'pot-mobile-sticky-checkout-enabled';
	}
	return $classes;
}
add_filter( 'body_class', 'pot_body_classes' );

/**
 * Choose the higher-contrast black/white foreground for a custom background.
 *
 * @param string $background Valid three- or six-digit hex color.
 * @return string Hex foreground color, or empty for an invalid background.
 */
function pot_promo_text_color( string $background ): string {
	$background = sanitize_hex_color( $background );
	if ( ! $background ) {
		return '';
	}
	$hex = substr( $background, 1 );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$linear = array();
	foreach ( str_split( $hex, 2 ) as $channel ) {
		$value = hexdec( $channel ) / 255;
		$linear[] = $value <= 0.04045 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
	}
	$luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
	return 1.05 / ( $luminance + 0.05 ) >= ( $luminance + 0.05 ) / 0.05 ? '#ffffff' : '#000000';
}

/**
 * Render the configurable promotion strip.
 */
function pot_promotion_strip(): void {
	$settings = pot_settings();
	if ( pot_is_offer_page() || pot_is_active_checkout() ) {
		return;
	}
	$background = sanitize_hex_color( (string) $settings['promo_background'] );
	$custom_style = $background ? '--pot-promo-background:' . $background . ';--pot-promo-text:' . pot_promo_text_color( $background ) . ';' : '';
	$promotion = ! empty( $settings['promo_enabled'] ) && '' !== trim( (string) $settings['promo_text'] );
	if ( ! $promotion ) {
		return;
	}
	?>
	<aside class="pot-promo has-global-padding is-layout-constrained" style="<?php echo esc_attr( $custom_style ); ?>" aria-label="<?php esc_attr_e( 'Store promotion', 'purple-optimize-toolkit' ); ?>">
		<div class="pot-promo-inner alignwide">
		<span><?php echo esc_html( (string) $settings['promo_text'] ); ?></span>
		<?php if ( '' !== trim( (string) $settings['promo_code'] ) ) : ?>
		<button class="pot-copy-code" type="button" data-code="<?php echo esc_attr( (string) $settings['promo_code'] ); ?>"><?php echo esc_html( (string) $settings['promo_code'] ); ?></button>
		<?php endif; ?>
		</div>
	</aside>
	<?php
}
add_action( 'wp_body_open', 'pot_promotion_strip', 5 );

/**
 * Preserve the child's native cart-drawer behavior without replacing the header.
 *
 * Run before WooCommerce supplies block defaults so an explicit merchant choice,
 * including "none", takes precedence.
 *
 * @param array $block Parsed block before default attributes are applied.
 * @return array Parsed block with a default mini-cart behavior.
 */
function pot_default_mini_cart_behavior( array $block ): array {
	if ( 'purple-optimize' === get_stylesheet()
		&& 'woocommerce/mini-cart' === ( $block['blockName'] ?? '' )
		&& ! array_key_exists( 'addToCartBehaviour', $block['attrs'] ?? array() ) ) {
		$block['attrs']['addToCartBehaviour'] = 'open_drawer';
	}
	return $block;
}
add_filter( 'render_block_data', 'pot_default_mini_cart_behavior' );

/**
 * Add wishlist access beside the parent's existing mini-cart control.
 *
 * @param string $html Native mini-cart markup.
 * @return string Native markup with an optional sibling utility link.
 */
function pot_header_wishlist_link( string $html ): string {
	if ( empty( pot_settings()['wishlist'] ) || pot_is_offer_page() || pot_is_active_checkout() ) {
		return $html;
	}
	$url = pot_wishlist_url();
	if ( ! $url ) {
		return $html;
	}
	$link = '<a class="pot-wishlist-link pot-header-wishlist" href="' . esc_url( $url ) . '"><span aria-hidden="true">♡</span><span class="screen-reader-text">' . esc_html__( 'Wishlist', 'purple-optimize-toolkit' ) . '</span> (<span class="pot-wishlist-count">0</span>)</a>';
	return $link . $html;
}
add_filter( 'render_block_woocommerce/mini-cart', 'pot_header_wishlist_link' );

/**
 * Expose the top-level product taxonomy without requiring menu editing.
 */
function pot_category_navigation(): void {
	$settings = pot_settings();
	if ( pot_is_offer_page() || pot_is_active_checkout() || empty( $settings['category_navigation'] ) ) {
		return;
	}
	$categories = get_terms( pot_presentation_category_query_args( absint( get_option( 'default_product_cat' ) ) ) );
	if ( is_wp_error( $categories ) || ! $categories ) {
		return;
	}
	?>
	<div class="pot-category-container has-global-padding is-layout-constrained">
	<nav class="pot-category-nav alignwide" aria-label="<?php esc_attr_e( 'Shop categories', 'purple-optimize-toolkit' ); ?>">
		<strong><?php esc_html_e( 'Shop categories', 'purple-optimize-toolkit' ); ?></strong>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'All products', 'purple-optimize-toolkit' ); ?></a>
		<?php foreach ( $categories as $category ) : ?>
		<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" <?php echo is_product_category( $category->slug ) ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $category->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	</div>
	<?php
}

/** Append discovery links below a native header without replacing its blocks. */
function pot_categories_after_header( string $html, array $block ): string {
	$attrs = $block['attrs'] ?? array();
	if ( 'header' !== ( $attrs['slug'] ?? '' ) && 'header' !== ( $attrs['area'] ?? '' ) && 'header' !== ( $attrs['tagName'] ?? '' ) ) {
		return $html;
	}
	ob_start();
	pot_category_navigation();
	return $html . ob_get_clean();
}
add_filter( 'render_block_core/template-part', 'pot_categories_after_header', 10, 2 );

/**
 * Keep one discreet support path in the enclosed checkout header.
 */
function pot_checkout_help_link(): void {
	if ( empty( pot_settings()['checkout_trust'] ) ) { return; }
	$contact = get_page_by_path( 'contact' );
	$url     = $contact instanceof WP_Post ? get_permalink( $contact ) : '';
	$url     = pot_presentation_checkout_help_url(
		pot_is_active_checkout(),
		$contact instanceof WP_Post ? $contact->post_status : '',
		is_string( $url ) ? $url : ''
	);

	if ( '' === $url ) {
		return;
	}

	printf(
		'<a class="pot-checkout-help" href="%1$s">%2$s</a>',
		esc_url( $url ),
		esc_html__( 'Need help?', 'purple-optimize-toolkit' )
	);
}
add_action( 'wp_body_open', 'pot_checkout_help_link', 7 );

/**
 * Search published WooCommerce products.
 */
function pot_search_products(): void {
	check_ajax_referer( 'pot_search_products', 'nonce' );
	$term        = sanitize_text_field( wp_unslash( $_GET['term'] ?? '' ) );
	$category_id = absint( $_GET['category'] ?? 0 );

	if ( strlen( $term ) < 2 ) {
		wp_send_json_success( array() );
	}

	$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			's'                      => $term,
			'posts_per_page'         => 6,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
	);
	if ( $category_id && term_exists( $category_id, 'product_cat' ) ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_id ) );
	}
	$query = new WP_Query( $args );
	$posts = $query->posts;

	// SKU searches are intentionally bounded and merged with normal title/content results.
	$sku_query = new WP_Query(
		array_merge(
			$args,
			array(
				's'          => '',
				'meta_query' => array( array( 'key' => '_sku', 'value' => $term, 'compare' => 'LIKE' ) ),
			)
		)
	);
	$posts = array_slice( array_values( array_reduce( array_merge( $posts, $sku_query->posts ), static function ( array $carry, WP_Post $post ): array { $carry[ $post->ID ] = $post; return $carry; }, array() ) ), 0, 6 );

	// A small typo fallback helps short catalog searches without adding a full search index.
	if ( ! $posts ) {
		$fallback = wc_get_products( array( 'status' => 'publish', 'limit' => 80, 'category' => $category_id ? array( get_term_field( 'slug', $category_id, 'product_cat' ) ) : array(), 'return' => 'objects' ) );
		foreach ( $fallback as $candidate ) {
			$name = strtolower( remove_accents( $candidate->get_name() ) );
			$needle = strtolower( remove_accents( $term ) );
			$words = preg_split( '/\s+/', $name ) ?: array();
			$close = array_filter( $words, static function ( string $word ) use ( $needle ): bool { return levenshtein( $needle, $word ) <= ( strlen( $needle ) > 5 ? 2 : 1 ); } );
			if ( $close || preg_match( '/\b' . preg_quote( $needle, '/' ) . '/i', $name ) ) {
				$posts[] = get_post( $candidate->get_id() );
			}
			if ( count( $posts ) >= 6 ) {
				break;
			}
		}
	}

	$items = array();
	foreach ( $posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$items[] = array(
			'id'    => $product->get_id(),
			'name'  => $product->get_name(),
			'url'   => $product->get_permalink(),
			'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
			'price' => wp_kses_post( $product->get_price_html() ),
			'sku'   => $product->get_sku(),
		);
	}

	wp_send_json_success( $items );
}
add_action( 'wp_ajax_pot_search_products', 'pot_search_products' );
add_action( 'wp_ajax_nopriv_pot_search_products', 'pot_search_products' );

/**
 * Calculate an honest discount percentage.
 *
 * @param WC_Product $product Product object.
 * @return int
 */
function pot_discount_percentage( WC_Product $product ): int {
	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();
	return pot_presentation_discount_percentage( $regular, $sale, $product->is_on_sale() );
}

/**
 * Add calculated badges to Woo product sale badge blocks.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function pot_filter_sale_badge_block( string $content, array $block ): string {
	$settings = pot_settings();
	if ( empty( $settings['sale_percentage'] ) || 'woocommerce/product-sale-badge' !== ( $block['blockName'] ?? '' ) ) {
		return $content;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return $content;
	}
	$percentage = pot_discount_percentage( $product );
	if ( $percentage < 1 ) {
		return $content;
	}
	return pot_presentation_sale_badge_html( $percentage, __( 'Save %d%%', 'purple-optimize-toolkit' ) );
}
add_filter( 'render_block', 'pot_filter_sale_badge_block', 10, 2 );

/**
 * Remove only native stock text replaced by the toolkit's factual warning.
 *
 * @param string     $html    Native availability markup.
 * @param WC_Product $product Product object.
 * @return string
 */
function pot_coordinate_product_stock_message( string $html, WC_Product $product ): string {
	if ( ! is_product() || empty( pot_settings()['product_guidance'] ) ) {
		return $html;
	}

	$settings = pot_settings();
	$stock    = $product->managing_stock() ? $product->get_stock_quantity() : null;
	$suppress = pot_presentation_suppresses_native_stock(
		$product->managing_stock(),
		null === $stock ? null : (int) $stock,
		(int) $settings['stock_threshold'],
		$product->is_in_stock(),
		$product->is_on_backorder( 1 )
	);

	return $suppress ? '' : $html;
}
add_filter( 'woocommerce_get_stock_html', 'pot_coordinate_product_stock_message', 10, 2 );

/**
 * Coordinate WooCommerce's block stock indicator with the toolkit warning.
 *
 * @param string               $content Rendered block markup.
 * @param array<string, mixed> $block   Parsed block.
 * @return string
 */
function pot_filter_stock_indicator_block( string $content, array $block ): string {
	if ( empty( pot_settings()['product_guidance'] ) || ! is_product() || 'woocommerce/product-stock-indicator' !== ( $block['blockName'] ?? '' ) ) {
		return $content;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return $content;
	}

	$settings = pot_settings();
	$stock    = $product->managing_stock() ? $product->get_stock_quantity() : null;
	$suppress = pot_presentation_suppresses_native_stock(
		$product->managing_stock(),
		null === $stock ? null : (int) $stock,
		(int) $settings['stock_threshold'],
		$product->is_in_stock(),
		$product->is_on_backorder( 1 )
	);

	return pot_presentation_filter_stock_block( $content, (string) $block['blockName'], $suppress );
}
add_filter( 'render_block', 'pot_filter_stock_indicator_block', 9, 2 );

/**
 * Build the product conversion panel from real product data.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function pot_product_panel( WC_Product $product ): string {
	$settings = pot_settings();
	$guidance = ! empty( $settings['product_guidance'] );
	if ( ! $guidance && empty( $settings['wishlist'] ) ) { return ''; }
	$stock    = $guidance && $product->managing_stock() ? $product->get_stock_quantity() : null;
	$sale_end = $guidance ? $product->get_date_on_sale_to() : false;
	$reviews  = $guidance ? (int) $product->get_review_count() : 0;
	$featured = $reviews ? get_comments( array( 'post_id' => $product->get_id(), 'status' => 'approve', 'type' => 'review', 'number' => 1, 'orderby' => 'comment_meta_value_num', 'meta_key' => 'rating', 'order' => 'DESC' ) ) : array();
	$photo_credits = array();
	foreach ( $product->get_gallery_image_ids() as $attachment_id ) {
		$source_url = (string) get_post_meta( $attachment_id, '_pot_media_source_url', true );
		if ( ! $source_url ) {
			continue;
		}
		$photo_credits[] = array(
			'title'       => get_the_title( $attachment_id ),
			'creator'     => (string) get_post_meta( $attachment_id, '_pot_media_creator', true ),
			'license'     => (string) get_post_meta( $attachment_id, '_pot_media_license', true ),
			'license_url' => (string) get_post_meta( $attachment_id, '_pot_media_license_url', true ),
			'source_url'  => $source_url,
		);
	}

	ob_start();
	?>
	<section class="pot-buy-box" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
		<?php if ( $reviews ) : ?>
		<a class="pot-review-summary" href="#reviews" aria-label="<?php echo esc_attr( sprintf( _n( 'Rated %1$s out of 5 from %2$d review', 'Rated %1$s out of 5 from %2$d reviews', $reviews, 'purple-optimize-toolkit' ), $product->get_average_rating(), $reviews ) ); ?>">
			<span aria-hidden="true">★★★★★</span> <strong><?php echo esc_html( $product->get_average_rating() ); ?>/5</strong> · <?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $reviews, 'purple-optimize-toolkit' ), $reviews ) ); ?>
		</a>
		<?php endif; ?>
		<?php if ( null !== $stock && $stock > 0 && $stock <= (int) $settings['stock_threshold'] ) : ?>
		<p class="pot-stock"><span aria-hidden="true">●</span> <?php echo esc_html( sprintf( _n( 'Only %d item left in stock', 'Only %d items left in stock', $stock, 'purple-optimize-toolkit' ), $stock ) ); ?></p>
		<?php endif; ?>
		<?php if ( $sale_end && $sale_end->getTimestamp() > time() ) : ?>
		<p class="pot-countdown" data-end="<?php echo esc_attr( gmdate( 'c', $sale_end->getTimestamp() ) ); ?>"><span><?php esc_html_e( 'Scheduled offer ends in', 'purple-optimize-toolkit' ); ?></span> <strong data-countdown role="timer"></strong></p>
		<?php endif; ?>
		<?php if ( ! empty( $settings['wishlist'] ) ) : ?>
		<button class="pot-wishlist-button wp-element-button" type="button" data-product="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-title="<?php echo esc_attr( $product->get_name() ); ?>" data-url="<?php echo esc_url( $product->get_permalink() ); ?>" data-image="<?php echo esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ) ); ?>" data-price="<?php echo esc_attr( wp_strip_all_tags( $product->get_price_html() ) ); ?>" aria-pressed="false">♡ <span><?php esc_html_e( 'Save to wishlist', 'purple-optimize-toolkit' ); ?></span></button>
		<?php endif; ?>
		<?php if ( $guidance ) : ?>
		<ul class="pot-reassurance" aria-label="<?php esc_attr_e( 'Purchase information', 'purple-optimize-toolkit' ); ?>">
			<li><?php echo esc_html( (string) $settings['reassurance_one'] ); ?></li>
			<li><?php echo esc_html( (string) $settings['reassurance_two'] ); ?></li>
			<li><?php echo esc_html( (string) $settings['reassurance_three'] ); ?></li>
		</ul>
		<?php endif; ?>
		<?php if ( $featured ) : ?>
		<blockquote class="pot-featured-review"><p>“<?php echo esc_html( wp_trim_words( $featured[0]->comment_content, 24 ) ); ?>”</p><cite><?php echo esc_html( $featured[0]->comment_author ); ?> · <?php esc_html_e( 'approved review', 'purple-optimize-toolkit' ); ?></cite></blockquote>
		<?php endif; ?>
		<?php if ( $photo_credits ) : ?>
		<details class="pot-photo-credits">
			<summary><?php esc_html_e( 'Gallery photo credits', 'purple-optimize-toolkit' ); ?></summary>
			<ul>
			<?php foreach ( $photo_credits as $credit ) : ?>
				<li><a href="<?php echo esc_url( $credit['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $credit['title'] ); ?></a> — <?php echo esc_html( $credit['creator'] ); ?>, <?php if ( $credit['license_url'] ) : ?><a href="<?php echo esc_url( $credit['license_url'] ); ?>" target="_blank" rel="license noopener"><?php echo esc_html( $credit['license'] ); ?></a><?php else : ?><?php echo esc_html( $credit['license'] ); ?><?php endif; ?></li>
			<?php endforeach; ?>
			</ul>
		</details>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Append the panel to the single-product add-to-cart block.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function pot_filter_add_to_cart_block( string $content, array $block ): string {
	if ( 'woocommerce/add-to-cart-with-options' !== ( $block['blockName'] ?? '' ) || ! is_product() ) {
		return $content;
	}
	global $product;
	return $product instanceof WC_Product ? $content . pot_product_panel( $product ) : $content;
}
add_filter( 'render_block', 'pot_filter_add_to_cart_block', 20, 2 );

/**
 * Return recent purchases backed by real WooCommerce orders.
 *
 * Customer first names are omitted unless the merchant explicitly enables them.
 *
 * @param array<string, mixed> $settings Plugin settings.
 * @return array<int, array<string, string>>
 */
function pot_recent_purchase_events( array $settings ): array {
	$days       = max( 1, min( 365, (int) $settings['social_proof_days'] ) );
	$show_names = ! empty( $settings['social_proof_show_names'] );
	$cache_key  = 'pot_recent_purchases_' . $days . '_' . ( $show_names ? 'names' : 'anonymous' );
	$cached     = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$orders = wc_get_orders(
		array(
			'status'       => array( 'wc-processing', 'wc-completed' ),
			'limit'        => 12,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'date_created' => '>' . ( time() - ( $days * DAY_IN_SECONDS ) ),
		)
	);
	$events = array();

	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}
			$first_name = trim( $order->get_billing_first_name() );
			$events[]   = array(
				'name'  => $show_names && '' !== $first_name ? $first_name : __( 'Someone', 'purple-optimize-toolkit' ),
				'title' => $product->get_name(),
				'url'   => $product->get_permalink(),
				'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
				'time'  => human_time_diff( $order->get_date_created()->getTimestamp(), time() ) . ' ' . __( 'ago', 'purple-optimize-toolkit' ),
			);
			break;
		}
		if ( count( $events ) >= 8 ) {
			break;
		}
	}

	set_transient( $cache_key, $events, 15 * MINUTE_IN_SECONDS );
	return $events;
}

/**
 * Register the distraction-free offer page route.
 */
function pot_register_offer_route(): void {
	add_rewrite_rule( '^special-offer/?$', 'index.php?pot_offer_page=1', 'top' );
}
add_action( 'init', 'pot_register_offer_route' );

/**
 * Register the offer-page query variable.
 *
 * @param string[] $vars Public query variables.
 * @return string[]
 */
function pot_offer_query_vars( array $vars ): array {
	$vars[] = 'pot_offer_page';
	return $vars;
}
add_filter( 'query_vars', 'pot_offer_query_vars' );

/**
 * Determine whether the current request is the offer page.
 */
function pot_is_offer_page(): bool {
	return '1' === (string) get_query_var( 'pot_offer_page' );
}

/**
 * Use the plugin's standalone offer template.
 *
 * @param string $template Resolved WordPress template.
 * @return string
 */
function pot_offer_template( string $template ): string {
	return pot_is_offer_page() ? POT_PATH . 'templates/offer-funnel.php' : $template;
}
add_filter( 'template_include', 'pot_offer_template', 99 );

/**
 * Give the virtual offer route a useful browser title.
 *
 * @param string $title Existing document title.
 * @return string
 */
function pot_offer_document_title( string $title ): string {
	return pot_is_offer_page() ? __( 'Special checkout offer', 'purple-optimize-toolkit' ) : $title;
}
add_filter( 'pre_get_document_title', 'pot_offer_document_title' );

/**
 * Return the configured product when it is eligible for this cart.
 *
 * @param string               $step     upsell or downsell.
 * @param array<string, mixed> $settings Plugin settings.
 * @return WC_Product|null
 */
function pot_offer_product( string $step, array $settings ): ?WC_Product {
	$product_id = absint( $settings[ $step . '_product_id' ] ?? 0 );
	$product    = $product_id ? wc_get_product( $product_id ) : false;
	if ( pot_offer_product_issue_for_product( $product ) ) {
		return null;
	}
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( (int) $cart_item['product_id'] === $product_id ) {
				return null;
			}
		}
	}
	return $product;
}

/**
 * Build the offer page URL for a funnel step.
 *
 * @param string        $step         upsell or downsell.
 * @param string        $context      pre_checkout or post_purchase.
 * @param WC_Order|null $source_order Completed source order for post-purchase offers.
 * @return string
 */
function pot_offer_url( string $step, string $context = 'pre_checkout', ?WC_Order $source_order = null ): string {
	$args = array( 'step' => $step );
	if ( 'post_purchase' === $context && $source_order ) {
		$args['context']  = 'post_purchase';
		$args['order_id'] = $source_order->get_id();
		$args['key']      = $source_order->get_order_key();
	}
	return add_query_arg( $args, home_url( '/special-offer/' ) );
}

/**
 * Validate a completed order referenced by a post-purchase offer URL.
 *
 * @return WC_Order|null
 */
function pot_post_purchase_source_order(): ?WC_Order {
	$order_id = absint( $_GET['order_id'] ?? get_query_var( 'order-received' ) );
	$key      = wc_clean( wp_unslash( $_GET['key'] ?? '' ) );
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	if ( ! $order || '' === $key || ! hash_equals( $order->get_order_key(), $key ) || $order->has_status( array( 'cancelled', 'failed', 'refunded' ) ) ) {
		return null;
	}
	return $order;
}

/**
 * End the funnel and proceed to checkout.
 *
 * Post-purchase acceptance always creates a separate follow-up order.
 *
 * @param string        $context      pre_checkout or post_purchase.
 * @param WC_Order|null $source_order Completed source order.
 */
function pot_finish_offer_funnel( string $context = 'pre_checkout', ?WC_Order $source_order = null ): void {
	if ( WC()->session ) {
		WC()->session->__unset( 'pot_offer_expiry_upsell' );
		WC()->session->__unset( 'pot_offer_expiry_downsell' );
		if ( 'post_purchase' === $context && $source_order ) {
			WC()->session->set( 'pot_post_purchase_parent_order', $source_order->get_id() );
		} else {
			WC()->session->set( 'pot_offer_funnel_complete', 1 );
		}
	}
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}

/**
 * Move to the next step after a rejection or expiry.
 *
 * @param string               $step     Current step.
 * @param array<string, mixed> $settings Plugin settings.
 * @param string               $context  pre_checkout or post_purchase.
 * @param WC_Order|null        $source_order Completed source order.
 */
function pot_reject_offer( string $step, array $settings, string $context = 'pre_checkout', ?WC_Order $source_order = null ): void {
	if ( WC()->session ) {
		WC()->session->__unset( 'pot_offer_expiry_' . $step );
	}
	if ( 'upsell' === $step && pot_offer_product( 'downsell', $settings ) ) {
		wp_safe_redirect( pot_offer_url( 'downsell', $context, $source_order ) );
		exit;
	}
	if ( 'post_purchase' === $context && $source_order ) {
		wp_safe_redirect( $source_order->get_checkout_order_received_url() );
		exit;
	}
	pot_finish_offer_funnel( $context, $source_order );
}

/**
 * Return a session-persistent countdown expiry for an offer.
 *
 * @param string     $step    Current funnel step.
 * @param WC_Product $product Offered product.
 * @param int        $minutes Configured duration.
 * @return int Unix timestamp, or 0 when countdown is disabled.
 */
function pot_offer_expiry( string $step, WC_Product $product, int $minutes ): int {
	if ( $minutes < 1 || ! WC()->session ) {
		return 0;
	}
	$key    = 'pot_offer_expiry_' . $step;
	$stored = WC()->session->get( $key );
	if ( ! is_array( $stored ) || (int) ( $stored['product_id'] ?? 0 ) !== $product->get_id() ) {
		$stored = array(
			'product_id' => $product->get_id(),
			'expires'    => time() + ( $minutes * MINUTE_IN_SECONDS ),
		);
		WC()->session->set( $key, $stored );
	}
	return (int) $stored['expires'];
}

/**
 * Scope offer decisions to the current cart and configured offer, for all shoppers.
 *
 * Old unscoped completion flags cannot establish that the present offer was seen.
 * Offer/add-on lines are excluded so accepting an extra never restarts the funnel.
 *
 * @param array<string, mixed> $settings Current settings.
 */
function pot_sync_offer_session( array $settings ): void {
	if ( ! WC()->session || ! WC()->cart ) {
		return;
	}
	$cart = array();
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		if ( ! empty( $item['pot_offer_step'] ) || ! empty( $item['pot_checkout_upsell'] ) ) {
			continue;
		}
		$cart[ $key ] = array( (int) $item['product_id'], (int) ( $item['variation_id'] ?? 0 ), (float) $item['quantity'] );
	}
	ksort( $cart );
	$config = array_intersect_key( $settings, array_flip( array( 'offer_placement', 'upsell_product_id', 'upsell_discount', 'upsell_countdown', 'downsell_product_id', 'downsell_discount', 'downsell_countdown' ) ) );
	ksort( $config );
	$context = hash( 'sha256', wp_json_encode( array( $cart, $config ) ) );
	if ( WC()->session->get( 'pot_offer_context' ) !== $context ) {
		foreach ( array( 'pot_offer_funnel_complete', 'pot_inline_offer_stage', 'pot_offer_expiry_upsell', 'pot_offer_expiry_downsell' ) as $key ) {
			WC()->session->__unset( $key );
		}
		WC()->session->set( 'pot_offer_context', $context );
	}
}

/**
 * Intercept checkout and process offer-page decisions.
 */
function pot_handle_offer_funnel(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! class_exists( 'WooCommerce' ) || ! WC()->cart || ! WC()->session ) {
		return;
	}

	$settings = pot_settings();
	if ( empty( $settings['offer_funnel'] ) ) {
		return;
	}

	$placement = (string) $settings['offer_placement'];
	if ( in_array( $placement, array( 'pre_checkout', 'checkout_inline' ), true ) && ( pot_is_active_checkout() || pot_is_offer_page() ) ) {
		pot_sync_offer_session( $settings );
	}
	if ( 'pre_checkout' === $placement && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_checkout_pay_page() && ! WC()->cart->is_empty() ) {
		if ( WC()->session->get( 'pot_offer_funnel_complete' ) ) {
			return;
		}
		if ( pot_offer_product( 'upsell', $settings ) ) {
			wp_safe_redirect( pot_offer_url( 'upsell' ) );
			exit;
		}
		if ( pot_offer_product( 'downsell', $settings ) ) {
			wp_safe_redirect( pot_offer_url( 'downsell' ) );
			exit;
		}
		WC()->session->set( 'pot_offer_funnel_complete', 1 );
		return;
	}

	if ( 'post_purchase' === $placement && is_wc_endpoint_url( 'order-received' ) ) {
		$order = pot_post_purchase_source_order();
		if ( ! $order || $order->get_meta( '_pot_post_purchase_parent_order' ) || (int) WC()->session->get( 'pot_post_purchase_offered_order' ) === $order->get_id() ) {
			return;
		}
		WC()->session->set( 'pot_post_purchase_offered_order', $order->get_id() );
		if ( pot_offer_product( 'upsell', $settings ) ) {
			wp_safe_redirect( pot_offer_url( 'upsell', 'post_purchase', $order ) );
			exit;
		}
		if ( pot_offer_product( 'downsell', $settings ) ) {
			wp_safe_redirect( pot_offer_url( 'downsell', 'post_purchase', $order ) );
			exit;
		}
		return;
	}

	if ( ! pot_is_offer_page() ) {
		return;
	}

	$context      = 'post_purchase' === sanitize_key( wp_unslash( $_GET['context'] ?? '' ) ) ? 'post_purchase' : 'pre_checkout';
	$source_order = 'post_purchase' === $context ? pot_post_purchase_source_order() : null;
	if ( 'post_purchase' === $context && ! $source_order ) {
		wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
		exit;
	}
	if ( 'post_purchase' === $context && 'post_purchase' !== $placement ) {
		wp_safe_redirect( $source_order->get_checkout_order_received_url() );
		exit;
	}
	if ( 'pre_checkout' === $context && 'pre_checkout' !== $placement ) {
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
	status_header( 200 );
	nocache_headers();
	if ( 'pre_checkout' === $context && WC()->cart->is_empty() ) {
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	$step = sanitize_key( wp_unslash( $_GET['step'] ?? 'upsell' ) );
	if ( ! in_array( $step, array( 'upsell', 'downsell' ), true ) ) {
		$step = 'upsell';
	}
	$product = pot_offer_product( $step, $settings );
	if ( ! $product ) {
		pot_reject_offer( $step, $settings, $context, $source_order );
	}

	$discount = max( 0, min( 100, absint( $settings[ $step . '_discount' ] ) ) );
	$minutes  = max( 0, min( 1440, absint( $settings[ $step . '_countdown' ] ) ) );
	$expiry   = pot_offer_expiry( $step, $product, $minutes );
	if ( $expiry && time() >= $expiry ) {
		pot_reject_offer( $step, $settings, $context, $source_order );
	}

	if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		$nonce  = sanitize_text_field( wp_unslash( $_POST['pot_offer_nonce'] ?? '' ) );
		$action = sanitize_key( wp_unslash( $_POST['pot_offer_action'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'pot_offer_' . $step ) ) {
			wp_die(
				esc_html__( 'This offer decision expired. Please return to checkout and try again.', 'purple-optimize-toolkit' ),
				esc_html__( 'Offer session expired', 'purple-optimize-toolkit' ),
				array( 'response' => 403 )
			);
		}
		if ( 'accept' === $action && ( ! $expiry || time() < $expiry ) ) {
			$original_price = (float) $product->get_price();
			$added          = WC()->cart->add_to_cart(
				$product->get_id(),
				1,
				0,
				array(),
				array(
					'pot_offer_step'           => $step,
					'pot_offer_context'        => $context,
					'pot_offer_discount'       => $discount,
					'pot_offer_original_price' => $original_price,
				)
			);
			if ( $added ) {
				WC()->cart->calculate_totals();
				pot_finish_offer_funnel( $context, $source_order );
			}
			wc_add_notice( __( 'The offer product could not be added. Please try again or continue without it.', 'purple-optimize-toolkit' ), 'error' );
		} else {
			pot_reject_offer( $step, $settings, $context, $source_order );
		}
	}

	$GLOBALS['pot_offer_context'] = array(
		'step'             => $step,
		'product'          => $product,
		'discount'         => $discount,
		'original_price'   => (float) $product->get_price(),
		'discounted_price' => (float) $product->get_price() * ( 1 - ( $discount / 100 ) ),
		'expiry'           => $expiry,
		'context'          => $context,
		'source_order'     => $source_order,
	);
}
add_action( 'template_redirect', 'pot_handle_offer_funnel', 5 );

/**
 * Return the active inline checkout offer step.
 *
 * @param array<string, mixed> $settings Plugin settings.
 * @return string Empty when the sequence is resolved.
 */
function pot_inline_offer_step( array $settings ): string {
	if ( ! WC()->session ) {
		return '';
	}
	$stage = sanitize_key( (string) WC()->session->get( 'pot_inline_offer_stage', 'upsell' ) );
	if ( 'resolved' === $stage ) {
		return '';
	}
	if ( 'downsell' === $stage && pot_offer_product( 'downsell', $settings ) ) {
		return 'downsell';
	}
	if ( 'upsell' === $stage && pot_offer_product( 'upsell', $settings ) ) {
		return 'upsell';
	}
	if ( pot_offer_product( 'downsell', $settings ) ) {
		WC()->session->set( 'pot_inline_offer_stage', 'downsell' );
		return 'downsell';
	}
	WC()->session->set( 'pot_inline_offer_stage', 'resolved' );
	return '';
}

/**
 * Render the passive offer card that is moved before Place Order.
 */
function pot_render_inline_offer(): void {
	$settings = pot_settings();
	if ( empty( $settings['offer_funnel'] ) || 'checkout_inline' !== $settings['offer_placement'] || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$step = pot_inline_offer_step( $settings );
	if ( ! $step ) {
		return;
	}
	$product  = pot_offer_product( $step, $settings );
	$discount = max( 0, min( 100, absint( $settings[ $step . '_discount' ] ) ) );
	$expiry   = pot_offer_expiry( $step, $product, absint( $settings[ $step . '_countdown' ] ) );
	if ( $expiry && time() >= $expiry ) {
		WC()->session->set( 'pot_inline_offer_stage', 'upsell' === $step ? 'downsell' : 'resolved' );
		return;
	}
	?>
	<aside class="pot-inline-offer" aria-labelledby="pot-inline-offer-title">
		<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); ?>
		<div class="pot-inline-offer-copy">
			<span><?php echo esc_html( 'downsell' === $step ? __( 'One last option', 'purple-optimize-toolkit' ) : __( 'Optional checkout offer', 'purple-optimize-toolkit' ) ); ?></span>
			<h3 id="pot-inline-offer-title"><?php echo esc_html( $product->get_name() ); ?></h3>
			<p><?php echo wp_kses_post( wc_price( (float) $product->get_price() * ( 1 - ( $discount / 100 ) ) ) ); ?> <del><?php echo wp_kses_post( wc_price( (float) $product->get_price() ) ); ?></del> · <?php echo esc_html( sprintf( __( '%d%% off', 'purple-optimize-toolkit' ), $discount ) ); ?></p>
			<?php if ( $expiry ) : ?><p class="pot-offer-timer" data-offer-expiry="<?php echo esc_attr( (string) ( $expiry * 1000 ) ); ?>"><span><?php esc_html_e( 'Offer ends in', 'purple-optimize-toolkit' ); ?></span> <strong data-offer-countdown role="timer"></strong></p><?php endif; ?>
			<form class="pot-offer-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pot_inline_offer_decision">
				<input type="hidden" name="pot_offer_step" value="<?php echo esc_attr( $step ); ?>">
				<?php wp_nonce_field( 'pot_inline_offer_' . $step, 'pot_offer_nonce' ); ?>
				<button class="pot-offer-accept wp-element-button" type="submit" name="pot_offer_action" value="accept"><?php esc_html_e( 'Add this offer', 'purple-optimize-toolkit' ); ?></button>
				<button class="pot-offer-reject wp-element-button" type="submit" name="pot_offer_action" value="reject"><?php esc_html_e( 'No thanks', 'purple-optimize-toolkit' ); ?></button>
			</form>
		</div>
	</aside>
	<?php
}

/**
 * Process an inline offer decision and return to checkout.
 */
function pot_handle_inline_offer_decision(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	if ( null === WC()->cart ) {
		wc_load_cart();
	}
	$settings = pot_settings();
	$step     = sanitize_key( wp_unslash( $_POST['pot_offer_step'] ?? '' ) );
	$action   = sanitize_key( wp_unslash( $_POST['pot_offer_action'] ?? '' ) );
	$nonce    = sanitize_text_field( wp_unslash( $_POST['pot_offer_nonce'] ?? '' ) );
	if ( empty( $settings['offer_funnel'] ) || 'checkout_inline' !== $settings['offer_placement'] || ! in_array( $step, array( 'upsell', 'downsell' ), true ) || ! wp_verify_nonce( $nonce, 'pot_inline_offer_' . $step ) ) {
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
	$product = pot_offer_product( $step, $settings );
	$expiry  = $product ? pot_offer_expiry( $step, $product, absint( $settings[ $step . '_countdown' ] ) ) : 0;
	if ( 'accept' === $action && $product && ( ! $expiry || time() < $expiry ) ) {
		$discount = max( 0, min( 100, absint( $settings[ $step . '_discount' ] ) ) );
		$added    = WC()->cart->add_to_cart( $product->get_id(), 1, 0, array(), array( 'pot_offer_step' => $step, 'pot_offer_context' => 'checkout_inline', 'pot_offer_discount' => $discount, 'pot_offer_original_price' => (float) $product->get_price() ) );
		if ( $added ) {
			WC()->session->set( 'pot_inline_offer_stage', 'resolved' );
			WC()->cart->calculate_totals();
		}
	} else {
		WC()->session->set( 'pot_inline_offer_stage', 'upsell' === $step && pot_offer_product( 'downsell', $settings ) ? 'downsell' : 'resolved' );
	}
	WC()->session->__unset( 'pot_offer_expiry_' . $step );
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}
add_action( 'admin_post_pot_inline_offer_decision', 'pot_handle_inline_offer_decision' );
add_action( 'admin_post_nopriv_pot_inline_offer_decision', 'pot_handle_inline_offer_decision' );

/**
 * Mark a post-purchase offer checkout as a separate follow-up order.
 *
 * @param WC_Order $order New order.
 */
function pot_mark_post_purchase_order( WC_Order $order ): void {
	if ( WC()->session && WC()->session->get( 'pot_post_purchase_parent_order' ) ) {
		$order->update_meta_data( '_pot_post_purchase_parent_order', absint( WC()->session->get( 'pot_post_purchase_parent_order' ) ) );
		WC()->session->__unset( 'pot_post_purchase_parent_order' );
	}
}
add_action( 'woocommerce_checkout_create_order', 'pot_mark_post_purchase_order' );

/**
 * Apply a configured funnel discount to its session-scoped cart item.
 *
 * @param WC_Cart $cart Cart object.
 */
function pot_apply_offer_prices( WC_Cart $cart ): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	foreach ( $cart->get_cart() as $cart_item ) {
		if ( ! isset( $cart_item['pot_offer_original_price'], $cart_item['pot_offer_discount'] ) || ! $cart_item['data'] instanceof WC_Product ) {
			continue;
		}
		$price    = max( 0, (float) $cart_item['pot_offer_original_price'] );
		$discount = max( 0, min( 100, (int) $cart_item['pot_offer_discount'] ) );
		$cart_item['data']->set_price( $price * ( 1 - ( $discount / 100 ) ) );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'pot_apply_offer_prices', 20 );

/**
 * Show the exclusive discount in cart and checkout item details.
 *
 * @param array<int, array<string, string>> $data      Existing item data.
 * @param array<string, mixed>              $cart_item Cart item.
 * @return array<int, array<string, string>>
 */
function pot_offer_item_data( array $data, array $cart_item ): array {
	if ( isset( $cart_item['pot_offer_discount'] ) ) {
		$data[] = array(
			'key'   => __( 'Exclusive offer', 'purple-optimize-toolkit' ),
			'value' => sprintf( __( '%d%% off', 'purple-optimize-toolkit' ), (int) $cart_item['pot_offer_discount'] ),
		);
	}
	return $data;
}
add_filter( 'woocommerce_get_item_data', 'pot_offer_item_data', 10, 2 );

/**
 * Preserve the offer discount as order-line metadata.
 *
 * @param WC_Order_Item_Product $item          Order line item.
 * @param string                $cart_item_key Cart item key.
 * @param array<string, mixed>  $values        Cart item values.
 */
function pot_offer_order_item_meta( WC_Order_Item_Product $item, string $cart_item_key, array $values ): void {
	if ( isset( $values['pot_offer_discount'] ) ) {
		$item->add_meta_data( __( 'Exclusive offer', 'purple-optimize-toolkit' ), sprintf( __( '%d%% off', 'purple-optimize-toolkit' ), (int) $values['pot_offer_discount'] ), true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'pot_offer_order_item_meta', 10, 3 );

/**
 * Start a fresh funnel when the first normal product enters a new cart.
 *
 * This covers Store API carts, where removing the final block-cart item does
 * not always fire the classic cart-emptied action in the same request path.
 *
 * @param string $cart_item_key Cart item key.
 * @param int    $product_id   Product ID.
 * @param float  $quantity     Added quantity.
 * @param int    $variation_id Variation ID.
 * @param array  $variation    Variation attributes.
 * @param array  $cart_data    Custom cart item data.
 */
function pot_reset_funnel_for_new_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_data ): void {
	if ( ! WC()->session || ! WC()->cart || ! empty( $cart_data['pot_offer_step'] ) || ! empty( $cart_data['pot_checkout_upsell'] ) ) {
		return;
	}
	if ( WC()->cart->get_cart_contents_count() <= (float) $quantity ) {
		pot_reset_funnel_on_empty();
	}
}
add_action( 'woocommerce_add_to_cart', 'pot_reset_funnel_for_new_cart', 10, 6 );

/**
 * Clear funnel state with an emptied cart.
 */
function pot_reset_funnel_on_empty(): void {
	if ( WC()->session ) {
		WC()->session->__unset( 'pot_offer_context' );
		WC()->session->__unset( 'pot_offer_funnel_complete' );
		WC()->session->__unset( 'pot_inline_offer_stage' );
		WC()->session->__unset( 'pot_post_purchase_parent_order' );
		WC()->session->__unset( 'pot_offer_expiry_upsell' );
		WC()->session->__unset( 'pot_offer_expiry_downsell' );
	}
}
add_action( 'woocommerce_cart_emptied', 'pot_reset_funnel_on_empty' );

/**
 * Return published policy links that can be shown without placeholders.
 *
 * @return array<string, string>
 */
function pot_policy_links(): array {
	$links = array();
	$shipping = get_page_by_path( 'shipping-returns' );
	$privacy  = (int) get_option( 'wp_page_for_privacy_policy' );
	$terms    = (int) get_option( 'woocommerce_terms_page_id' );
	if ( $shipping instanceof WP_Post && 'publish' === $shipping->post_status ) {
		$links[ __( 'Shipping & returns', 'purple-optimize-toolkit' ) ] = get_permalink( $shipping );
	}
	if ( $privacy && 'publish' === get_post_status( $privacy ) ) {
		$links[ __( 'Privacy policy', 'purple-optimize-toolkit' ) ] = get_permalink( $privacy );
	}
	if ( $terms && 'publish' === get_post_status( $terms ) ) {
		$links[ __( 'Terms & conditions', 'purple-optimize-toolkit' ) ] = get_permalink( $terms );
	}
	return $links;
}

/**
 * Render pre-checkout guidance on the cart using factual site configuration.
 */
function pot_checkout_trust_panel(): void {
	$settings = pot_settings();
	if ( empty( $settings['checkout_trust'] ) || ! is_cart() ) {
		return;
	}
	$links = pot_policy_links();
	?>
	<aside class="pot-checkout-trust" aria-label="<?php esc_attr_e( 'Before checkout', 'purple-optimize-toolkit' ); ?>">
		<strong><?php esc_html_e( 'Ready for checkout?', 'purple-optimize-toolkit' ); ?></strong>
		<span><?php esc_html_e( 'Review delivery and store policies now. Shipping, payment options, and the final total are confirmed at checkout.', 'purple-optimize-toolkit' ); ?></span>
		<?php foreach ( $links as $label => $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
	</aside>
	<?php
}

/**
 * Render real policy destinations near the footer.
 */
function pot_footer_policy_links(): void {
	$settings = pot_settings();
	$links    = pot_policy_links();
	if ( empty( $settings['footer_policies'] ) || pot_is_offer_page() || pot_is_active_checkout() || ! $links ) {
		return;
	}
	?>
	<nav class="pot-footer-policies" aria-label="<?php esc_attr_e( 'Store policies', 'purple-optimize-toolkit' ); ?>">
		<?php foreach ( $links as $label => $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Invite guest purchasers to create an account after the transaction.
 */
function pot_account_invitation(): void {
	$settings = pot_settings();
	if ( empty( $settings['post_purchase_account'] ) || is_user_logged_in() || ! is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}
	?>
	<aside class="pot-account-invitation">
		<div><strong><?php esc_html_e( 'Save time on your next visit', 'purple-optimize-toolkit' ); ?></strong><span><?php esc_html_e( 'Create an account after checkout to keep addresses and order history together.', 'purple-optimize-toolkit' ); ?></span></div>
		<a class="wp-element-button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Create an account', 'purple-optimize-toolkit' ); ?></a>
	</aside>
	<?php
}

/**
 * Render movable cart, checkout, and sticky-product helpers.
 */
function pot_footer_helpers(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$settings = pot_settings();
	pot_render_inline_offer();
	pot_checkout_trust_panel();
	pot_account_invitation();
	if ( ! empty( $settings['recent_sales'] ) && ! pot_is_offer_page() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
		$events = pot_recent_purchase_events( $settings );
		if ( $events ) {
			?>
		<aside class="pot-social-proof" data-events="<?php echo esc_attr( wp_json_encode( $events ) ); ?>" aria-live="polite" aria-atomic="true" hidden>
			<img src="" alt="">
			<div><span><?php esc_html_e( 'Recent purchase', 'purple-optimize-toolkit' ); ?></span><p></p><small></small></div>
			<button type="button" aria-label="<?php esc_attr_e( 'Dismiss recent purchases', 'purple-optimize-toolkit' ); ?>">&times;</button>
		</aside>
			<?php
		}
	}

	if ( ( is_cart() || ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) ) && (float) $settings['free_shipping_threshold'] > 0 && WC()->cart ) {
		$threshold = (float) $settings['free_shipping_threshold'];
		$subtotal  = (float) WC()->cart->get_displayed_subtotal();
		$remaining = max( 0, $threshold - $subtotal );
		$progress  = min( 100, ( $subtotal / $threshold ) * 100 );
		?>
		<section id="pot-shipping-progress" class="pot-shipping-progress" data-target="<?php echo esc_attr( (string) $threshold ); ?>" aria-live="polite" aria-atomic="true">
			<p><?php echo $remaining > 0 ? wp_kses_post( sprintf( __( 'Add %s more to reach the configured free-shipping threshold.', 'purple-optimize-toolkit' ), wc_price( $remaining ) ) ) : esc_html__( 'You reached the configured free-shipping threshold.', 'purple-optimize-toolkit' ); ?></p>
			<div role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) round( $progress ) ); ?>"><span style="width:<?php echo esc_attr( (string) $progress ); ?>%"></span></div>
		</section>
		<?php
	}

	if ( is_product() && ! empty( $settings['sticky_cart'] ) ) {
		global $product;
		if ( $product instanceof WC_Product ) {
			?>
		<aside class="pot-sticky-cart" aria-hidden="true" data-product-type="<?php echo esc_attr( $product->get_type() ); ?>">
			<div><?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); ?><span><strong><?php echo esc_html( $product->get_name() ); ?></strong><span><?php echo wp_kses_post( $product->get_price_html() ); ?></span></span></div>
			<button type="button" class="wp-element-button"><span class="pot-sticky-cart-label"><?php echo esc_html( $product->is_type( 'simple' ) ? $product->single_add_to_cart_text() : __( 'Choose options', 'purple-optimize-toolkit' ) ); ?></span></button>
			<span class="pot-sticky-cart-status screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></span>
		</aside>
		<?php
		}
	}

}
add_action( 'wp_footer', 'pot_footer_helpers', 20 );
add_action( 'wp_footer', 'pot_footer_policy_links', 30 );

/**
 * Add a product-content readiness checklist to the editor.
 */
function pot_product_readiness_meta_box(): void {
	add_meta_box( 'pot-product-readiness', __( 'Conversion readiness', 'purple-optimize-toolkit' ), 'pot_render_product_readiness_meta_box', 'product', 'side', 'default' );
}
add_action( 'add_meta_boxes_product', 'pot_product_readiness_meta_box' );

/**
 * Render non-blocking photography and description guidance.
 *
 * @param WP_Post $post Product post.
 */
function pot_render_product_readiness_meta_box( WP_Post $post ): void {
	$product = wc_get_product( $post->ID );
	if ( ! $product ) {
		return;
	}
	$image_count = ( $product->get_image_id() ? 1 : 0 ) + count( $product->get_gallery_image_ids() );
	$checks = array(
		array( $image_count >= 3, sprintf( __( '%d product images (aim for at least 3 useful angles)', 'purple-optimize-toolkit' ), $image_count ) ),
		array( '' !== trim( $product->get_short_description() ), __( 'Scannable short description', 'purple-optimize-toolkit' ) ),
		array( '' !== trim( $product->get_description() ), __( 'Detailed description and specifications', 'purple-optimize-toolkit' ) ),
	);
	echo '<ul class="pot-readiness-checklist">';
	foreach ( $checks as $check ) {
		echo '<li><span aria-hidden="true">' . ( $check[0] ? '✓' : '○' ) . '</span> ' . esc_html( $check[1] ) . '</li>';
	}
	echo '</ul><p class="description">' . esc_html__( 'Use accurate images, scale/context, and product-specific copy. This checklist does not replace content review.', 'purple-optimize-toolkit' ) . '</p>';
}

/**
 * Find a published native page containing the wishlist shortcode.
 *
 * @return int Page ID, or zero when no published destination exists.
 */
function pot_wishlist_page_id(): int {
	$saved = get_post( (int) get_option( 'pot_wishlist_page_id', 0 ) );
	if ( $saved instanceof WP_Post && 'page' === $saved->post_type && 'publish' === $saved->post_status && ! $saved->post_password && has_shortcode( $saved->post_content, 'purple_optimize_wishlist' ) ) {
		return (int) $saved->ID;
	}
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 's' => 'purple_optimize_wishlist', 'posts_per_page' => -1 ) );
	foreach ( $pages as $page ) {
		if ( ! $page->post_password && has_shortcode( $page->post_content, 'purple_optimize_wishlist' ) ) {
			return (int) $page->ID;
		}
	}
	return 0;
}

/** Return the real published wishlist destination without assuming its slug. */
function pot_wishlist_url(): string {
	$id = pot_wishlist_page_id();
	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Provision the native wishlist page only for an administrator's enabled feature.
 * Reuse existing shortcode pages and never replace merchant-authored content.
 */
function pot_ensure_wishlist_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) || empty( pot_settings()['wishlist'] ) ) {
		return;
	}
	$id = pot_wishlist_page_id();
	if ( ! $id ) {
		$id = wp_insert_post( array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => __( 'Wishlist', 'purple-optimize-toolkit' ),
			'post_name' => 'wishlist',
			'post_content' => '<!-- wp:shortcode -->[purple_optimize_wishlist]<!-- /wp:shortcode -->',
		), true );
	}
	if ( ! is_wp_error( $id ) && $id ) {
		update_option( 'pot_wishlist_page_id', (int) $id );
	}
}
add_action( 'admin_init', 'pot_ensure_wishlist_page' );
add_action( 'update_option_pot_settings', 'pot_ensure_wishlist_page' );

/**
 * Wishlist page shortcode. Client-side rendering avoids storing guest PII.
 *
 * @return string
 */
function pot_wishlist_shortcode(): string {
	return '<div class="pot-wishlist-page" data-empty="' . esc_attr__( 'Your wishlist is empty.', 'purple-optimize-toolkit' ) . '"><p>' . esc_html__( 'Loading wishlist…', 'purple-optimize-toolkit' ) . '</p></div>';
}
add_shortcode( 'purple_optimize_wishlist', 'pot_wishlist_shortcode' );
