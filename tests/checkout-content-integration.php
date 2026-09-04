<?php
/** Local-only native-content regression checks; fixture documents/options are restored. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! preg_match( '/\.(test|localhost)$/', wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
	throw new RuntimeException( 'Run with WP-CLI on a local test site only.' );
}
$original_id = get_option( 'pot_checkout_content_id', false );
$original_settings = get_option( 'pot_checkout_settings', false );
$original_user = get_current_user_id();
$id = 0;
$checks = 0;
$check = function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
};
try {
	delete_option( 'pot_checkout_content_id' );
	$check( pot_checkout_content_source() === pot_checkout_settings()['content'], 'Legacy fallback must preserve content.' );
	$saved = pot_sanitize_checkout_settings( array( 'content_enabled' => 1 ) );
	$check( $saved['content'] === wp_kses_post( pot_checkout_settings()['content'] ), 'Checkbox-only saves preserve legacy copy.' );
	$id = wp_insert_post( array( 'post_type' => 'pot_checkout_info', 'post_status' => 'publish', 'post_title' => 'POT content regression fixture', 'post_content' => '<!-- wp:heading --><h2 class="wp-block-heading">Helpful heading</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong>Bold copy</strong> and a <a href="https://example.com">link</a>.</p><!-- /wp:paragraph -->' ) );
	update_option( 'pot_checkout_content_id', $id );
	$html = pot_checkout_content_html();
	$check( str_contains( $html, '<h2' ) && str_contains( $html, '<strong>Bold copy</strong>' ), 'Core blocks render with formatting.' );
	$check( ! str_contains( $html, '<!-- wp:' ) && ! str_contains( $html, '<p><h2' ), 'No raw delimiters or wpautop corruption.' );
	foreach ( array( 'draft', 'private', 'trash' ) as $status ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => $status ) );
		$check( '' === pot_checkout_content_source(), 'Non-public documents never leak: ' . $status );
	}
	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish', 'post_password' => 'fixture' ) );
	$check( '' === pot_checkout_content_source(), 'Protected content never leaks.' );
	$ran = false;
	register_block_type( 'pot-test/forbidden', array( 'render_callback' => function () use ( &$ran ) { $ran = true; return 'FORBIDDEN'; } ) );
	wp_update_post( array( 'ID' => $id, 'post_password' => '', 'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:pot-test/forbidden /--><!-- wp:paragraph --><p>Safe <script>alert(1)</script><img src="x" onerror="bad()"></p><!-- /wp:paragraph --></div><!-- /wp:group -->' ) );
	$html = pot_checkout_content_html();
	$check( ! $ran && ! str_contains( $html, 'FORBIDDEN' ), 'Nested unsupported callbacks never run.' );
	$check( ! str_contains( $html, '<script' ) && ! str_contains( $html, 'onerror' ), 'Unsafe HTML is removed.' );
	wp_update_post( array( 'ID' => $id, 'post_content' => '<!-- wp:html --><p>Converted legacy HTML</p><script>alert(1)</script><!-- /wp:html -->' ) );
	$html = pot_checkout_content_html();
	$check( str_contains( $html, 'Converted legacy HTML' ) && ! str_contains( $html, '<script' ), 'Native HTML conversion remains visible and sanitized.' );
	$block = parse_blocks( '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"secret"}}}}} --><p>Static text</p><!-- /wp:paragraph -->' )[0];
	$check( ! isset( pot_checkout_content_filter_block( $block )['attrs']['metadata']['bindings'] ), 'External data bindings are removed.' );
	$manager = new WP_User( 0 );
	$manager->allcaps = get_role( 'shop_manager' )->capabilities;
	$type = get_post_type_object( 'pot_checkout_info' );
	$check( $manager->has_cap( $type->cap->edit_posts ), 'Shop managers can edit checkout content.' );
	wp_set_current_user( 0 );
	$response = rest_do_request( new WP_REST_Request( 'POST', '/wp/v2/pot_checkout_info/' . $id ) );
	$check( in_array( $response->get_status(), array( 401, 403 ), true ), 'Unauthenticated writes rejected.' );
	$other = new WP_Block_Editor_Context( array( 'post' => new WP_Post( (object) array( 'post_type' => 'page' ) ) ) );
	$check( true === pot_checkout_content_allowed_blocks( true, $other ), 'Other editors are unchanged.' );
	WP_CLI::success( $checks . ' native checkout-content integration checks passed.' );
} finally {
	wp_set_current_user( $original_user );
	if ( $id ) { wp_delete_post( $id, true ); }
	if ( false === $original_id ) { delete_option( 'pot_checkout_content_id' ); } else { update_option( 'pot_checkout_content_id', $original_id ); }
	if ( false === $original_settings ) { delete_option( 'pot_checkout_settings' ); } else { update_option( 'pot_checkout_settings', $original_settings ); }
}
