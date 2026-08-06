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

