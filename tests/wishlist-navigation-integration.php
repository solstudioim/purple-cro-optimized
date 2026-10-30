<?php
/** Verify wishlist page provisioning only on an isolated WordPress fixture. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
$original_settings = get_option( 'pot_settings', null );
$original_page = get_option( 'pot_wishlist_page_id', null );
$original_user = get_current_user_id();
$created = 0;
$force_missing = static function ( $posts, $query ) {
	return 'purple_optimize_wishlist' === $query->get( 's' ) ? array() : $posts;
};
$expect = static function ( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } };
try {
	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
	wp_set_current_user( $admin->ID );
	add_filter( 'posts_pre_query', $force_missing, 10, 2 );
	update_option( 'pot_wishlist_page_id', 0 );
	$settings = pot_settings();
	$settings['wishlist'] = 0;
	update_option( 'pot_settings', $settings );
	pot_ensure_wishlist_page();
	$expect( 0 === (int) get_option( 'pot_wishlist_page_id' ), 'Disabled wishlist must not provision a page.' );
	$settings['wishlist'] = 1;
	update_option( 'pot_settings', $settings );
	pot_ensure_wishlist_page();
	$created = (int) get_option( 'pot_wishlist_page_id' );
	$expect( $created > 0, 'Enabling wishlist must create a published destination.' );
	$expect( 'publish' === get_post_status( $created ), 'Destination must be published.' );
	$expect( has_shortcode( get_post_field( 'post_content', $created ), 'purple_optimize_wishlist' ), 'Destination needs the shortcode.' );
	pot_ensure_wishlist_page();
	$expect( $created === (int) get_option( 'pot_wishlist_page_id' ), 'Repeated provisioning must reuse the page.' );
	wp_update_post( array( 'ID' => $created, 'post_name' => 'saved-items-local-test' ) );
	$expect( get_permalink( $created ) === pot_wishlist_url(), 'Toolbar must follow the actual page permalink.' );
	wp_set_current_user( 0 );
	update_option( 'pot_wishlist_page_id', 0 );
	pot_ensure_wishlist_page();
	$expect( 0 === (int) get_option( 'pot_wishlist_page_id' ), 'Guest requests may not create pages.' );
	WP_CLI::success( 'Wishlist opt-in, native page creation, idempotency, custom slug URL and guest write prevention passed.' );
} finally {
	remove_filter( 'posts_pre_query', $force_missing, 10 );
	wp_set_current_user( 0 );
	if ( null === $original_settings ) { delete_option( 'pot_settings' ); } else { update_option( 'pot_settings', $original_settings ); }
	if ( null === $original_page ) { delete_option( 'pot_wishlist_page_id' ); } else { update_option( 'pot_wishlist_page_id', $original_page ); }
	if ( $created ) { wp_delete_post( $created, true ); }
	wp_set_current_user( $original_user );
}
