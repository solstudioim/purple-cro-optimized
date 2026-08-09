<?php
/**
 * Purple Optimize child theme setup.
 *
 * @package PurpleOptimize
 */

declare( strict_types = 1 );

/**
 * Enqueue the child theme stylesheet after Purple.
 */
function purple_optimize_styles(): void {
	wp_enqueue_style(
		'purple-optimize-style',
		get_stylesheet_uri(),
		array( 'purple-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'purple_optimize_styles', 20 );

/**
 * Defer non-primary product gallery images without changing gallery sources.
 *
 * @param string $block_content Rendered product gallery markup.
 * @return string Updated gallery markup.
 */
function purple_optimize_lazy_gallery_images( string $block_content ): string {
	if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );
	while ( $processor->next_tag( 'IMG' ) ) {
		$loading  = $processor->get_attribute( 'loading' );
		$priority = $processor->get_attribute( 'fetchpriority' );
		if ( 'eager' === $loading || 'high' === $priority ) {
			continue;
		}
		if ( null === $loading ) {
			$processor->set_attribute( 'loading', 'lazy' );
		}
		if ( null === $priority ) {
			$processor->set_attribute( 'fetchpriority', 'low' );
		}
	}

	return $processor->get_updated_html();
}
add_filter( 'render_block_woocommerce/product-gallery', 'purple_optimize_lazy_gallery_images' );
