<?php
/**
 * Native WordPress block editing for the helpful checkout panel.
 *
 * @package PurpleOptimizeToolkit
 * @since 0.9.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register a non-public document with native editor, media, autosaves and revisions.
 *
 * @since 0.9.0
 * @return void
 */
function pot_register_checkout_content(): void {
	register_post_type( 'pot_checkout_info', array(
		'labels' => array(
			'name'          => __( 'Helpful checkout content', 'purple-optimize-toolkit' ),
			'singular_name' => __( 'Helpful checkout content', 'purple-optimize-toolkit' ),
			'edit_item'     => __( 'Edit helpful checkout content', 'purple-optimize-toolkit' ),
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'show_in_rest'        => true,
		'rewrite'             => false,
		'query_var'           => false,
		'supports'            => array( 'title', 'editor', 'revisions' ),
		'map_meta_cap'        => false,
		'capabilities'        => array_fill_keys( array(
			'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
			'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts',
			'delete_published_posts', 'delete_others_posts', 'edit_private_posts',
			'edit_published_posts', 'create_posts',
		), 'manage_woocommerce' ),
	) );
}
add_action( 'init', 'pot_register_checkout_content' );

/**
 * Content-only blocks: exclude forms, checkout blocks, shortcodes and remote embeds.
 *
 * @since 0.9.0
 * @return string[] Supported block names.
 */
function pot_checkout_content_blocks(): array {
	return array(
		'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/image',
		'core/gallery', 'core/group', 'core/columns', 'core/column', 'core/media-text',
		'core/buttons', 'core/button', 'core/separator', 'core/spacer', 'core/quote',
		'core/table', 'core/freeform', 'core/html',
	);
}

/**
 * Limit only this document's inserter, leaving other WordPress editors untouched.
 *
 * @since 0.9.0
 * @param bool|string[] $allowed Existing allowed blocks.
 * @param WP_Block_Editor_Context $context Editor context.
 * @return bool|string[] Allowed blocks.
 */
function pot_checkout_content_allowed_blocks( $allowed, $context ) {
	return isset( $context->post ) && 'pot_checkout_info' === $context->post->post_type
		? pot_checkout_content_blocks() : $allowed;
}
add_filter( 'allowed_block_types_all', 'pot_checkout_content_allowed_blocks', 10, 2 );

/**
 * Open the native editor, copying legacy content once without deleting the original.
 *
 * @since 0.9.0
 * @return void
 */
function pot_edit_checkout_content(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You cannot edit checkout content.', 'purple-optimize-toolkit' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'pot_edit_checkout_content' );
	$id   = absint( get_option( 'pot_checkout_content_id', 0 ) );
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'pot_checkout_info' !== $post->post_type || 'trash' === $post->post_status ) {
		$legacy = $id ? '' : pot_checkout_settings()['content'];
		$id = wp_insert_post( wp_slash( array(
			'post_type'    => 'pot_checkout_info',
			'post_status'  => trim( $legacy ) ? 'publish' : 'draft',
			'post_title'   => __( 'Helpful checkout content', 'purple-optimize-toolkit' ),
			'post_content' => wp_kses_post( $legacy ),
		) ), true );
		if ( is_wp_error( $id ) ) {
			wp_die( esc_html__( 'Could not create the checkout document. Please try again.', 'purple-optimize-toolkit' ) );
		}
		update_option( 'pot_checkout_content_id', $id, false );
	}
	wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
	exit;
}
add_action( 'admin_post_pot_edit_checkout_content', 'pot_edit_checkout_content' );

/**
 * Return only published content; drafts, password-protected and deleted documents stay hidden.
 *
 * @since 0.9.0
 * @return string Stored block markup, or legacy HTML before migration.
 */
function pot_checkout_content_source(): string {
	$id = absint( get_option( 'pot_checkout_content_id', 0 ) );
	if ( ! $id ) {
		return pot_checkout_settings()['content'];
	}
	$post = get_post( $id );
	return $post && 'pot_checkout_info' === $post->post_type && 'publish' === $post->post_status && ! $post->post_password
		? $post->post_content : '';
}

/**
 * Enforce the content-only block list before running any server render callbacks.
 *
 * @since 0.9.0
 * @param array $block Parsed block.
 * @return array Safe parsed block (empty for unsupported types).
 */
function pot_checkout_content_filter_block( array $block ): array {
	if ( $block['blockName'] && ! in_array( $block['blockName'], pot_checkout_content_blocks(), true ) ) {
		return array( 'blockName' => null, 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
	}
	// Bindings can read external data even on otherwise static core blocks.
	unset( $block['attrs']['metadata']['bindings'] );
	$block['innerBlocks'] = array_map( 'pot_checkout_content_filter_block', $block['innerBlocks'] );
	return $block;
}

/**
 * Render core blocks with WordPress's layout and style support, then sanitize HTML.
 *
 * @since 0.9.0
 * @return string Safe shopper-facing HTML.
 */
function pot_checkout_content_html(): string {
	$source = pot_checkout_content_source();
	if ( ! has_blocks( $source ) ) {
		return wp_kses_post( wpautop( $source ) );
	}
	$blocks = array_map( 'pot_checkout_content_filter_block', parse_blocks( $source ) );
	return wp_kses_post( do_blocks( serialize_blocks( $blocks ) ) );
}

/**
 * Load block assets before checkout's head; Store API responses cannot enqueue CSS there.
 *
 * @since 0.9.0
 * @return void
 */
function pot_checkout_content_assets(): void {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() && pot_checkout_settings()['content_enabled'] ) {
		pot_checkout_content_html();
		wp_enqueue_style( 'pot-checkout-content' );
	}
}
add_action( 'wp_enqueue_scripts', 'pot_checkout_content_assets', 20 );

/**
 * Apply the panel's typography inside this native editor, including its iframe.
 *
 * @since 0.9.0
 * @return void
 */
function pot_checkout_content_editor_assets(): void {
	$screen = is_admin() ? get_current_screen() : null;
	if ( $screen && 'pot_checkout_info' === $screen->post_type ) {
		wp_enqueue_style( 'pot-checkout-content' );
	}
}
add_action( 'enqueue_block_assets', 'pot_checkout_content_editor_assets' );
