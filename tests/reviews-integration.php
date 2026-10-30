<?php
/** Native review/shortcode checks and reversible local browser fixtures. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! preg_match( '/(^localhost$|^127\.0\.0\.1$|\.(test|localhost)$)/', wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
	throw new RuntimeException( 'Run on an isolated local WordPress site only.' );
}
$mode = $args[0] ?? 'test';
$cleanup = static function () {
	$fixture = get_option( 'pot_reviews_test_fixture', array() );
	foreach ( $fixture['comments'] ?? array() as $id ) { wp_delete_comment( $id, true ); }
	foreach ( $fixture['posts'] ?? array() as $id ) { wp_delete_post( $id, true ); }
	delete_option( 'pot_reviews_test_fixture' );
};
if ( 'cleanup' === $mode ) { $cleanup(); return; }
if ( get_option( 'pot_reviews_test_fixture' ) ) { throw new RuntimeException( 'Clean the previous review fixture before preparing another.' ); }
$fixture = array( 'posts' => array(), 'comments' => array() );
$expect = static function ( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } };
$original_content = get_option( 'pot_checkout_content_id', false );
$prepared = false;
try {
	foreach ( array( 'public', 'other', 'protected', 'draft', 'private' ) as $kind ) {
		$id = wp_insert_post( array( 'post_type' => 'product', 'post_status' => in_array( $kind, array( 'draft', 'private' ), true ) ? $kind : 'publish', 'post_password' => 'protected' === $kind ? 'secret' : '', 'post_title' => 'Review fixture ' . $kind ) );
		$fixture['posts'][] = $id;
		$fixture[ $kind ] = $id;
	}
	foreach ( array(
		array( 'public', '1', 'First native review <img src=x onerror=bad()>', 5 ),
		array( 'public', '1', 'Second native review', 4 ),
		array( 'other', '1', 'Another product review', 2 ),
		array( 'public', '0', 'PENDING SECRET', 5 ),
		array( 'public', 'spam', 'SPAM SECRET', 5 ),
		array( 'protected', '1', 'PROTECTED SECRET', 5 ),
		array( 'draft', '1', 'DRAFT SECRET', 5 ),
		array( 'private', '1', 'PRIVATE SECRET', 5 ),
	) as $index => $row ) {
		$id = wp_insert_comment( array( 'comment_post_ID' => $fixture[ $row[0] ], 'comment_type' => 'review', 'comment_approved' => $row[1], 'comment_content' => $row[2], 'comment_author' => 'Alex Example', 'comment_author_email' => 'alex@example.test', 'comment_date' => gmdate( 'Y-m-d H:i:s', time() - 3600 + $index ), 'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 3600 + $index ) ) );
		add_comment_meta( $id, 'rating', $row[3] );
		add_comment_meta( $id, 'verified', 0 === $index ? '1' : '0' );
		$fixture['comments'][] = $id;
	}
	update_option( 'pot_reviews_test_fixture', $fixture );
	$ids = implode( ',', $fixture['comments'] );
	$html = pot_reviews_shortcode( array( 'source' => 'reviews', 'review_ids' => $ids, 'limit' => 12 ) );
	$expect( 3 === substr_count( $html, '<figure' ), 'Only three eligible approved public reviews should render.' );
	foreach ( array( 'PENDING SECRET', 'SPAM SECRET', 'PROTECTED SECRET', 'DRAFT SECRET', 'PRIVATE SECRET', 'onerror', '<img', 'alex@example.test', 'Alex Example' ) as $secret ) {
		$expect( false === strpos( $html, $secret ), 'Do not expose unsafe/private data: ' . $secret );
	}
	$expect( false !== strpos( $html, 'Verified owner' ) && 1 === substr_count( $html, 'pot-testimonial-verified' ), 'Only genuine verified owner receives a badge.' );
	$expect( false !== strpos( $html, 'Review fixture public' ), 'Always attribute the source product.' );
	$html = pot_reviews_shortcode( array( 'source' => 'products', 'products' => (string) $fixture['public'], 'min_rating' => 5 ) );
	$expect( 1 === substr_count( $html, '<figure' ) && false !== strpos( $html, 'First native review' ), 'Product and rating filters work together.' );
	$expect( '' === pot_reviews_shortcode( array( 'source' => 'products', 'products' => 'invalid' ) ), 'Invalid product selection cannot broaden to all reviews.' );
	$expect( '' === pot_reviews_shortcode( array( 'source' => 'reviews', 'review_ids' => '' ) ), 'Empty selected review IDs fail closed.' );
	$config = pot_reviews_config( array( 'limit' => 9999, 'autoplay' => 1, 'layout' => 'rotator', 'products' => array( 'bad' ), 'heading' => '<script>bad()</script>Heading' ) );
	$expect( 12 === $config['limit'] && 5 === $config['autoplay'] && '' === $config['products'] && false === strpos( $config['heading'], '<' ), 'Malformed attributes are bounded and sanitized.' );
	$html = pot_reviews_shortcode( array( 'source' => 'reviews', 'review_ids' => (string) $fixture['comments'][1], 'author' => 'anonymous' ) );
	$expect( false !== strpos( $html, '>Customer</span>' ) && false === strpos( $html, 'Alex' ), 'Anonymous mode hides the review author.' );
	$latest = pot_reviews_query( pot_reviews_config( array( 'source' => 'products', 'products' => (string) $fixture['public'], 'order' => 'newest' ) ) );
	$expect( (int) $latest[0]->comment_ID === $fixture['comments'][1], 'Newest first is deterministic.' );
	$oldest = pot_reviews_query( pot_reviews_config( array( 'source' => 'products', 'products' => (string) $fixture['public'], 'order' => 'oldest' ) ) );
	$expect( (int) $oldest[0]->comment_ID === $fixture['comments'][0], 'Oldest first is deterministic.' );
	$ran = false;
	add_shortcode( 'pot_forbidden', static function () use ( &$ran ) { $ran = true; return 'FORBIDDEN'; } );
	$shortcode = pot_reviews_build_shortcode( pot_reviews_config( array( 'source' => 'products', 'products' => (string) $fixture['public'], 'layout' => 'rotator' ) ) );
	$doc = wp_insert_post( array( 'post_type' => 'pot_checkout_info', 'post_status' => 'publish', 'post_title' => 'Review checkout fixture', 'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:shortcode -->' . $shortcode . '[pot_forbidden]<!-- /wp:shortcode --></div><!-- /wp:group -->' ) );
	$fixture['posts'][] = $doc;
	$fixture['document'] = $doc;
	update_option( 'pot_reviews_test_fixture', $fixture );
	update_option( 'pot_checkout_content_id', $doc );
	$html = pot_checkout_content_html();
	$expect( ! $ran && false === strpos( $html, 'FORBIDDEN' ), 'Checkout never executes unrelated shortcodes.' );
	$expect( false !== strpos( $html, 'First native review' ) && false !== strpos( $html, 'data-pot-review-rotator' ), 'Approved shortcode renders inside nested checkout content with rotator attributes intact.' );
	wp_set_comment_status( $fixture['comments'][0], 'hold' );
	$expect( false === strpos( pot_checkout_content_html(), 'First native review' ), 'Moderation changes apply immediately.' );
	wp_set_comment_status( $fixture['comments'][0], 'approve' );
	$expect( 'Social proof' === pot_settings_sections()['privacy'], 'Old section key now has the new label.' );
	WP_CLI::success( 'Review eligibility, selections, bounds, attribution, escaping, moderation and restricted checkout shortcode checks passed.' );
	if ( 'prepare' === $mode ) { $prepared = true; echo wp_json_encode( $fixture ) . "\n"; }
} finally {
	if ( false === $original_content ) { delete_option( 'pot_checkout_content_id' ); } else { update_option( 'pot_checkout_content_id', $original_content ); }
	if ( ! $prepared ) { $cleanup(); }
}
