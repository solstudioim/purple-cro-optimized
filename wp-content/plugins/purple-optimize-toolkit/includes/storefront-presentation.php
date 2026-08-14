<?php
/**
 * Pure storefront presentation policies shared by the WooCommerce adapters.
 *
 * @package PurpleOptimizeToolkit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the top-level product-category query arguments.
 *
 * @param int $default_category_id WooCommerce's configured default category.
 * @return array<string, mixed>
 */
function pot_presentation_category_query_args( int $default_category_id ): array {
	$args = array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => true,
		'number'     => 8,
	);

	if ( $default_category_id > 0 ) {
		$args['exclude'] = array( $default_category_id );
	}

	return $args;
}

/**
 * Add the enclosed state only to an active, normal checkout form.
 *
 * @param string[] $classes           Existing body classes.
 * @param bool     $is_checkout       Whether this is checkout.
 * @param bool     $is_order_received Whether the receipt endpoint is active.
 * @param bool     $is_order_pay      Whether the order-pay endpoint is active.
 * @param bool     $is_offer_page     Whether the toolkit offer route is active.
 * @return string[]
 */
function pot_presentation_checkout_classes( array $classes, bool $is_checkout, bool $is_order_received, bool $is_order_pay, bool $is_offer_page ): array {
	if ( $is_checkout && ! $is_order_received && ! $is_order_pay && ! $is_offer_page ) {
		$classes[] = 'pot-enclosed-checkout';
	}

	return array_values( array_unique( $classes ) );
}

/**
 * Expose a published help destination only on the active checkout.
 *
 * @param bool   $is_active_checkout Whether the normal checkout form is active.
 * @param string $page_status        Contact page status.
 * @param string $url                Contact page URL.
 * @return string
 */
function pot_presentation_checkout_help_url( bool $is_active_checkout, string $page_status, string $url ): string {
	if ( ! $is_active_checkout || 'publish' !== $page_status || '' === trim( $url ) ) {
		return '';
	}

	return $url;
}

/**
 * Render one calculated savings badge.
 *
 * @param int    $percentage  Discount percentage.
 * @param string $label_format Translatable label format containing one integer placeholder.
 * @return string
 */
function pot_presentation_sale_badge_html( int $percentage, string $label_format = 'Save %d%%' ): string {
	if ( $percentage < 1 ) {
		return '';
	}

	$label = sprintf( $label_format, min( 100, $percentage ) );
	return '<span class="pot-discount-badge">' . htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' ) . '</span>';
}

/**
 * Calculate a percentage only for a currently active WooCommerce sale.
 *
 * @param float $regular_price Regular catalog price.
 * @param float $sale_price    Active sale price.
 * @param bool  $is_on_sale    WooCommerce's current sale state.
 * @return int
 */
function pot_presentation_discount_percentage( float $regular_price, float $sale_price, bool $is_on_sale ): int {
	if ( ! $is_on_sale || $regular_price <= 0 || $sale_price <= 0 || $sale_price >= $regular_price ) {
		return 0;
	}

	return (int) round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
}

/**
 * Decide whether the toolkit warning safely replaces native availability.
 *
 * @param bool     $managing_stock Whether WooCommerce manages product stock.
 * @param int|null $stock          Current quantity.
 * @param int      $threshold      Toolkit low-stock threshold.
 * @param bool     $is_in_stock    Whether the product can be purchased.
 * @param bool     $is_on_backorder Whether backorder context must be retained.
 * @return bool
 */
function pot_presentation_suppresses_native_stock( bool $managing_stock, ?int $stock, int $threshold, bool $is_in_stock, bool $is_on_backorder ): bool {
	return $managing_stock
		&& null !== $stock
		&& $stock > 0
		&& $stock <= max( 0, $threshold )
		&& $is_in_stock
		&& ! $is_on_backorder;
}

/**
 * Remove only the WooCommerce stock-indicator block replaced by the toolkit.
 *
 * @param string $content    Rendered block markup.
 * @param string $block_name Parsed block name.
 * @param bool   $suppress   Whether the toolkit warning is visible.
 * @return string
 */
function pot_presentation_filter_stock_block( string $content, string $block_name, bool $suppress ): string {
	if ( $suppress && 'woocommerce/product-stock-indicator' === $block_name ) {
		return '';
	}

	return $content;
}

/**
 * Calculate option pricing from current WooCommerce values.
 *
 * @param float $regular Regular price.
 * @param float $current Current price.
 * @return array{regular: float, current: float, saved: float, percentage: int}
 */
function pot_presentation_price_economics( float $regular, float $current ): array {
	if ( $regular <= 0 || $current <= 0 || $current > $regular ) {
		return array(
			'regular'    => max( 0.0, $regular ),
			'current'    => max( 0.0, $current ),
			'saved'      => 0.0,
			'percentage' => 0,
		);
	}

	$saved = $regular - $current;
	return array(
		'regular'    => $regular,
		'current'    => $current,
		'saved'      => $saved,
		'percentage' => (int) round( ( $saved / $regular ) * 100 ),
	);
}

/**
 * Return a popularity label only when the merchant explicitly configured it.
 *
 * @param bool   $configured Whether the label is explicitly enabled.
 * @param string $label      Merchant-authored label.
 * @return string
 */
function pot_presentation_popularity_label( bool $configured, string $label ): string {
	return $configured ? trim( $label ) : '';
}

/**
 * Confirm that a short policy summary retains material limitations.
 *
 * @param string   $summary        Policy summary.
 * @param string[] $material_terms Terms that must remain visible.
 * @return bool
 */
function pot_presentation_policy_summary_is_safe( string $summary, array $material_terms ): bool {
	$plain      = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $summary ) : strip_tags( $summary );
	$normalized = strtolower( trim( $plain ) );
	if ( '' === $normalized ) {
		return false;
	}

	foreach ( $material_terms as $term ) {
		$term = strtolower( trim( (string) $term ) );
		if ( '' !== $term && false === strpos( $normalized, $term ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Explain why a configured offer product is not eligible.
 *
 * @param bool   $exists      Whether the selected product still exists.
 * @param string $type        WooCommerce product type.
 * @param string $status      WordPress post status.
 * @param bool   $visible     Whether the product is catalog visible.
 * @param bool   $purchasable Whether WooCommerce considers it purchasable.
 * @param bool   $in_stock    Whether the product is in stock.
 * @return string Stable issue code, or an empty string when eligible.
 */
function pot_presentation_offer_product_issue( bool $exists, string $type, string $status, bool $visible, bool $purchasable, bool $in_stock ): string {
	if ( ! $exists ) {
		return 'missing';
	}
	if ( 'simple' !== $type ) {
		return 'not_simple';
	}
	if ( 'publish' !== $status ) {
		return 'not_published';
	}
	if ( ! $visible ) {
		return 'not_visible';
	}
	if ( ! $purchasable ) {
		return 'not_purchasable';
	}
	if ( ! $in_stock ) {
		return 'out_of_stock';
	}

	return '';
}
