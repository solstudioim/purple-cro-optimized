<?php
/** Verify native drawer configuration on an isolated WordPress installation. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
$expect = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$expect( 'purple-optimize' === get_stylesheet(), 'Activate Purple Optimize for this test.' );
$handler = 'data-wp-on-document--wc-blocks_added_to_cart---open-drawer';
$expect( false !== strpos( do_blocks( '<!-- wp:woocommerce/mini-cart /-->' ), $handler ), 'Unconfigured native cart must open after adding.' );
$expect( false === strpos( do_blocks( '<!-- wp:woocommerce/mini-cart {"addToCartBehaviour":"none"} /-->' ), $handler ), 'Explicit none must remain closed after adding.' );
$expect( false !== strpos( do_blocks( '<!-- wp:woocommerce/mini-cart {"addToCartBehaviour":"open_drawer"} /-->' ), $handler ), 'Explicit open must stay enabled.' );
$paragraph = parse_blocks( '<!-- wp:paragraph --><p>Keep me</p><!-- /wp:paragraph -->' )[0];
$expect( $paragraph === apply_filters( 'render_block_data', $paragraph, $paragraph, null ), 'Unrelated blocks must be untouched.' );
$parent_theme = static function () { return 'purple'; };
add_filter( 'stylesheet', $parent_theme );
try {
	$expect( false === strpos( do_blocks( '<!-- wp:woocommerce/mini-cart /-->' ), $handler ), 'Standalone parent behavior must remain untouched.' );
} finally {
	remove_filter( 'stylesheet', $parent_theme );
}
echo "PASS: native mini-cart default, explicit choices, unrelated blocks and parent isolation.\n";
