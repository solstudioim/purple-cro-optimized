<?php
/**
 * Optional checkout add-ons and merchant-authored order-summary content.
 *
 * @package PurpleOptimizeToolkit
 * @since 0.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get checkout settings independently of the existing offer sequence.
 *
 * @since 0.8.0
 * @return array Checkout settings.
 */
function pot_checkout_settings(): array {
	return wp_parse_args( get_option( 'pot_checkout_settings', array() ), array(
		'upsells_enabled' => 0,
		'upsells'         => array(),
		'content_enabled' => 0,
		'content'         => '',
	) );
}

/**
 * Sanitize bounded add-on configuration and safe, media-capable rich text.
 *
 * @since 0.8.0
 * @param mixed $input Submitted settings.
 * @return array Sanitized settings.
 */
function pot_sanitize_checkout_settings( $input ): array {
	$input  = is_array( $input ) ? $input : array();
	$output = array(
		'upsells_enabled' => empty( $input['upsells_enabled'] ) ? 0 : 1,
		'content_enabled' => empty( $input['content_enabled'] ) ? 0 : 1,
		'content'         => wp_kses_post( is_string( $input['content'] ?? null ) ? $input['content'] : '' ),
		'upsells'         => array(),
	);
	$seen = array();
	foreach ( array_slice( (array) ( $input['upsells'] ?? array() ), 0, 4 ) as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$id = is_scalar( $row['product_id'] ?? null ) ? absint( $row['product_id'] ) : 0;
		if ( ! $id || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$output['upsells'][] = array(
			'product_id'  => $id,
			'title'       => sanitize_text_field( is_string( $row['title'] ?? null ) ? $row['title'] : '' ),
			'description' => sanitize_textarea_field( is_string( $row['description'] ?? null ) ? $row['description'] : '' ),
		);
	}
	return $output;
}

/**
 * Register the separate settings form and its shop-manager capability.
 *
 * @since 0.8.0
 * @return void
 */
function pot_register_checkout_settings(): void {
	register_setting( 'pot_checkout_settings_group', 'pot_checkout_settings', array(
		'type' => 'array', 'sanitize_callback' => 'pot_sanitize_checkout_settings',
	) );
}
add_action( 'admin_init', 'pot_register_checkout_settings' );
add_filter( 'option_page_capability_pot_checkout_settings_group', function () { return 'manage_woocommerce'; } );

/**
 * Render checkout controls in the existing WooCommerce toolkit screen.
 *
 * @since 0.8.0
 * @return void
 */
function pot_render_checkout_settings(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$settings = pot_checkout_settings();
	?>
	<form method="post" action="options.php" id="pot-checkout-settings">
		<?php settings_fields( 'pot_checkout_settings_group' ); ?>
		<h2><?php esc_html_e( 'Checkout add-ons and helpful content', 'purple-optimize-toolkit' ); ?></h2>
		<p><?php esc_html_e( 'Optional add-ons appear above Place order. Helpful content appears below the order summary. These settings are independent of the upsell/downsell funnel.', 'purple-optimize-toolkit' ); ?></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><?php esc_html_e( 'Checkout add-ons', 'purple-optimize-toolkit' ); ?></th><td><label><input type="checkbox" name="pot_checkout_settings[upsells_enabled]" value="1" <?php checked( $settings['upsells_enabled'] ); ?>> <?php esc_html_e( 'Enable optional checkout add-ons', 'purple-optimize-toolkit' ); ?></label>
			<p class="description"><?php esc_html_e( 'Choose up to four published, visible, in-stock simple products. Images and prices come from the catalog, including real sale prices. Nothing is preselected.', 'purple-optimize-toolkit' ); ?></p></td></tr>
			<?php for ( $index = 0; $index < 4; ++$index ) :
				$row     = $settings['upsells'][ $index ] ?? array();
				$id      = absint( $row['product_id'] ?? 0 );
				$product = $id ? wc_get_product( $id ) : false;
				$prefix  = 'pot_checkout_settings[upsells][' . $index . ']';
				$issue   = $id ? pot_offer_product_issue( $id ) : '';
				?>
			<tr><th scope="row"><label for="pot-checkout-product-<?php echo esc_attr( (string) $index ); ?>"><?php echo esc_html( sprintf( __( 'Add-on %d', 'purple-optimize-toolkit' ), $index + 1 ) ); ?></label></th><td>
				<select class="wc-product-search" style="width: 100%; max-width: 480px;" id="pot-checkout-product-<?php echo esc_attr( (string) $index ); ?>" name="<?php echo esc_attr( $prefix ); ?>[product_id]" data-action="woocommerce_json_search_products" data-allow_clear="true" data-placeholder="<?php esc_attr_e( 'Search for a simple product…', 'purple-optimize-toolkit' ); ?>">
					<?php if ( $id ) : ?><option value="<?php echo esc_attr( (string) $id ); ?>" selected><?php echo esc_html( $product ? wp_strip_all_tags( $product->get_formatted_name() ) : '#' . $id ); ?></option><?php endif; ?>
				</select>
				<?php if ( $issue ) : ?><p class="notice notice-warning inline"><?php echo esc_html( pot_offer_product_issue_message( $issue ) ); ?></p><?php endif; ?>
				<p><label for="pot-checkout-title-<?php echo esc_attr( (string) $index ); ?>"><?php esc_html_e( 'Optional headline', 'purple-optimize-toolkit' ); ?></label><br><input class="large-text" id="pot-checkout-title-<?php echo esc_attr( (string) $index ); ?>" name="<?php echo esc_attr( $prefix ); ?>[title]" value="<?php echo esc_attr( $row['title'] ?? '' ); ?>"></p>
				<p><label for="pot-checkout-description-<?php echo esc_attr( (string) $index ); ?>"><?php esc_html_e( 'Optional description', 'purple-optimize-toolkit' ); ?></label><br><textarea class="large-text" rows="3" id="pot-checkout-description-<?php echo esc_attr( (string) $index ); ?>" name="<?php echo esc_attr( $prefix ); ?>[description]"><?php echo esc_textarea( $row['description'] ?? '' ); ?></textarea></p>
				<p class="description"><?php esc_html_e( 'Leave copy blank to use the product name and short description. Clear the product to remove this add-on.', 'purple-optimize-toolkit' ); ?></p>
			</td></tr>
			<?php endfor; ?>
			<tr><th scope="row"><?php esc_html_e( 'Helpful checkout content', 'purple-optimize-toolkit' ); ?></th><td><label><input type="checkbox" name="pot_checkout_settings[content_enabled]" value="1" <?php checked( $settings['content_enabled'] ); ?>> <?php esc_html_e( 'Show text and images below the order summary', 'purple-optimize-toolkit' ); ?></label>
			<p class="description"><?php esc_html_e( 'Add headings, lists, links, and images from the Media Library. Use only accurate delivery, returns, support, and security claims. Scripts and embedded forms are not allowed.', 'purple-optimize-toolkit' ); ?></p>
			<?php wp_editor( $settings['content'], 'pot_checkout_content', array( 'textarea_name' => 'pot_checkout_settings[content]', 'textarea_rows' => 12, 'media_buttons' => true ) ); ?>
			</td></tr>
		</table>
		<?php submit_button( __( 'Save checkout features', 'purple-optimize-toolkit' ) ); ?>
	</form>
	<?php
}
add_action( 'pot_after_settings_form', 'pot_render_checkout_settings' );

/**
 * Find all cart lines for a product, without touching unrelated cart items.
 *
 * @since 0.8.0
 * @param int $id Product ID.
 * @return array Matching cart lines keyed by cart item key.
 */
function pot_checkout_product_lines( int $id ): array {
	return array_filter( WC()->cart->get_cart(), function ( $item ) use ( $id ) {
		return $id === (int) $item['product_id'];
	} );
}

/**
 * Provide current product prices and selection state in every Store API cart response.
 *
 * @since 0.8.0
 * @return array Public checkout data, with no customer information.
 */
function pot_checkout_cart_data(): array {
	$settings = pot_checkout_settings();
	$result   = array( 'offers' => array(), 'content' => '' );
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return $result;
	}
	if ( $settings['content_enabled'] ) {
		$result['content'] = wp_kses_post( wpautop( $settings['content'] ) );
	}
	if ( ! $settings['upsells_enabled'] ) {
		return $result;
	}
	foreach ( $settings['upsells'] as $row ) {
		$product = wc_get_product( $row['product_id'] );
		if ( pot_offer_product_issue_for_product( $product ) ) {
			continue;
		}
		$lines = pot_checkout_product_lines( $product->get_id() );
		$added = false;
		foreach ( $lines as $item ) {
			$added = $added || ! empty( $item['pot_checkout_upsell'] );
		}
		$current_price = wc_get_price_to_display( $product, array( 'display_context' => 'cart' ) );
		$price_html    = wc_price( $current_price );
		if ( $product->is_on_sale() ) {
			$regular_price = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price(), 'display_context' => 'cart' ) );
			$price_html    = wc_format_sale_price( $regular_price, $current_price );
		}
		$result['offers'][] = array(
			'id'          => $product->get_id(),
			'title'       => $row['title'] ?: $product->get_name(),
			'description' => $row['description'] ?: wp_strip_all_tags( $product->get_short_description() ),
			'image'       => wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'alt' => '' ) ) ),
			'price'       => wp_kses_post( $price_html . $product->get_price_suffix() ),
			'added'       => $added,
			'in_cart'     => (bool) $lines,
		);
	}
	return $result;
}

/**
 * Add/remove one configured product through the nonce-protected Store API.
 *
 * Guest access is intentional: Store API authenticates the shopper's cart, not
 * admin capabilities. Prices, quantity, and the product allowlist are server-owned.
 *
 * @since 0.8.0
 * @param mixed $data Extension request data.
 * @return void
 * @throws Exception When the selection is invalid or cannot be added.
 */
function pot_update_checkout_upsell( $data ): void {
	$settings = pot_checkout_settings();
	if ( ! is_array( $data ) || ! isset( $data['id'], $data['selected'] ) || ! is_bool( $data['selected'] ) || ! is_int( $data['id'] ) || $data['id'] < 1 || ! WC()->cart || WC()->cart->is_empty() ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pot_invalid_addon', __( 'Please refresh checkout and try again.', 'purple-optimize-toolkit' ), 400 );
	}
	$id    = absint( $data['id'] );
	$lines = pot_checkout_product_lines( $id );
	if ( ! $data['selected'] ) {
		// Removal remains possible if the merchant disables or changes the offer.
		foreach ( $lines as $key => $item ) {
			if ( ! empty( $item['pot_checkout_upsell'] ) ) {
				WC()->cart->remove_cart_item( $key );
			}
		}
		return;
	}
	if ( ! $settings['upsells_enabled'] || ! in_array( $id, array_column( $settings['upsells'], 'product_id' ), true ) ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pot_unavailable_addon', __( 'This checkout add-on is no longer available.', 'purple-optimize-toolkit' ), 400 );
	}
	if ( $lines ) {
		return; // Repeated acceptance never increases quantity or duplicates a line.
	}
	$product = wc_get_product( $id );
	if ( pot_offer_product_issue_for_product( $product ) || ! apply_filters( 'woocommerce_add_to_cart_validation', true, $id, 1 ) ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pot_invalid_product', __( 'This add-on cannot be added. Please choose another item.', 'purple-optimize-toolkit' ), 400 );
	}
	if ( ! WC()->cart->add_to_cart( $id, 1, 0, array(), array( 'pot_checkout_upsell' => true ) ) ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pot_addon_failed', __( 'Could not add this item. Please check its availability and try again.', 'purple-optimize-toolkit' ), 400 );
	}
}

/**
 * Register Store API extension callbacks after WooCommerce Blocks loads.
 *
 * @since 0.8.0
 * @return void
 */
function pot_register_checkout_api(): void {
	woocommerce_store_api_register_update_callback( array(
		'namespace' => 'purple-checkout', 'callback' => 'pot_update_checkout_upsell',
	) );
	woocommerce_store_api_register_endpoint_data( array(
		'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
		'namespace' => 'purple-checkout', 'data_callback' => 'pot_checkout_cart_data',
		'schema_callback' => function () {
			return array(
				'offers'  => array( 'type' => 'array', 'readonly' => true, 'items' => array( 'type' => 'object' ) ),
				'content' => array( 'type' => 'string', 'readonly' => true ),
			);
		},
		'schema_type' => ARRAY_A,
	) );
}
add_action( 'woocommerce_blocks_loaded', 'pot_register_checkout_api' );

/**
 * Register automatically placed native Checkout inner blocks and scoped assets.
 *
 * @since 0.8.0
 * @return void
 */
function pot_register_checkout_blocks(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	wp_register_script( 'pot-checkout-extras', POT_URL . 'assets/checkout-extras.js', array( 'wc-blocks-checkout', 'wc-blocks-data-store', 'wp-element', 'wp-data', 'wp-i18n' ), (string) filemtime( POT_PATH . 'assets/checkout-extras.js' ), true );
	wp_set_script_translations( 'pot-checkout-extras', 'purple-optimize-toolkit' );
	wp_register_style( 'pot-checkout-extras', POT_URL . 'assets/checkout-extras.css', array(), (string) filemtime( POT_PATH . 'assets/checkout-extras.css' ) );
	foreach ( array( 'checkout-upsells', 'checkout-content' ) as $name ) {
		register_block_type( POT_PATH . 'blocks/' . $name );
	}
}
add_action( 'init', 'pot_register_checkout_blocks' );

/**
 * Insert add-ons before the native checkout action and content after the summary.
 *
 * Server-rendered siblings are hydrated by WooCommerce's inner-block registry;
 * no nested checkout forms, DOM reparenting, or persistent page edits are needed.
 *
 * @since 0.8.0
 * @param string $html Rendered native block.
 * @param array  $block Parsed native block.
 * @return string Checkout markup.
 */
function pot_insert_checkout_extras( string $html, array $block ): string {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
		return $html;
	}
	$settings = pot_checkout_settings();
	if ( 'woocommerce/checkout-actions-block' === $block['blockName'] && $settings['upsells_enabled'] ) {
		return render_block( array( 'blockName' => 'purple-optimize/checkout-upsells', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) ) . $html;
	}
	if ( 'woocommerce/checkout-order-summary-block' === $block['blockName'] && $settings['content_enabled'] && trim( $settings['content'] ) ) {
		return $html . render_block( array( 'blockName' => 'purple-optimize/checkout-content', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
	}
	return $html;
}
add_filter( 'render_block_woocommerce/checkout-actions-block', 'pot_insert_checkout_extras', 10, 2 );
add_filter( 'render_block_woocommerce/checkout-order-summary-block', 'pot_insert_checkout_extras', 10, 2 );
