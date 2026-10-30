<?php
/**
 * Explicit companion setup and read-only environment checks.
 *
 * @package PurpleOptimize
 * @since 0.5.14
 */

defined( 'ABSPATH' ) || exit;

/** Return the tested minimums and installed versions without contacting a service. */
function purple_optimize_environment_checks(): array {
	$parent = wp_get_theme( get_template() );
	$child  = wp_get_theme();
	$checks = array(
		'wordpress' => array( 'label' => __( 'WordPress', 'purple-optimize' ), 'version' => get_bloginfo( 'version' ), 'minimum' => '7.1' ),
		'php'       => array( 'label' => __( 'PHP', 'purple-optimize' ), 'version' => PHP_VERSION, 'minimum' => '7.4' ),
		'parent'    => array( 'label' => __( 'Purple parent', 'purple-optimize' ), 'version' => $parent->get( 'Version' ), 'minimum' => '1.0.3', 'required' => false ),
		'child'     => array( 'label' => __( 'Purple Optimize', 'purple-optimize' ), 'version' => $child->get( 'Version' ), 'minimum' => '0.5.14' ),
		'woo'       => array( 'label' => __( 'WooCommerce', 'purple-optimize' ), 'version' => defined( 'WC_VERSION' ) ? WC_VERSION : '', 'minimum' => '11.1' ),
	);
	foreach ( $checks as &$check ) {
		$check['ok'] = '' !== $check['version'] && version_compare( $check['version'], $check['minimum'], '>=' );
	}
	unset( $check );
	return $checks;
}

/** Only required checks block setup; the parent version is an advisory baseline. */
function purple_optimize_environment_ready( array $checks ): bool {
	foreach ( $checks as $check ) {
		if ( ( $check['required'] ?? true ) && ! $check['ok'] ) {
			return false;
		}
	}
	return true;
}

/** Register a setup destination even when the optional plugin is absent. */
function purple_optimize_setup_menu(): void {
	add_theme_page( __( 'Purple Optimize setup', 'purple-optimize' ), __( 'Purple Optimize setup', 'purple-optimize' ), 'edit_theme_options', 'purple-optimize-setup', 'purple_optimize_setup_page' );
}
add_action( 'admin_menu', 'purple_optimize_setup_menu' );

/** Show one persistent, dismissible-by-choice invitation until setup is visited. */
function purple_optimize_setup_notice(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || get_user_meta( get_current_user_id(), 'purple_optimize_setup_seen', true ) ) {
		return;
	}
	printf( '<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>', esc_html__( 'Complete your Purple Optimize setup to check requirements and add optional conversion features.', 'purple-optimize' ), esc_url( admin_url( 'themes.php?page=purple-optimize-setup' ) ), esc_html__( 'Review setup', 'purple-optimize' ) );
}
add_action( 'admin_notices', 'purple_optimize_setup_notice' );

/** Render installation, activation, and recovery choices without automatic mutations. */
function purple_optimize_setup_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	// Visiting setup dismisses the invitation, not any feature or compatibility issue.
	update_user_meta( get_current_user_id(), 'purple_optimize_setup_seen', 1 );
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$file      = 'purple-optimize-toolkit/purple-optimize-toolkit.php';
	$installed = isset( get_plugins()[ $file ] );
	$active    = is_plugin_active( $file );
	$checks    = purple_optimize_environment_checks();
	$toolkit_ok = ! $installed || version_compare( get_plugins()[ $file ]['Version'], '0.11.10', '>=' );
	$ready     = purple_optimize_environment_ready( $checks );
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Purple Optimize setup', 'purple-optimize' ); ?></h1>
	<p><?php esc_html_e( 'The theme provides your store layout. The optional toolkit adds conversion features and can be installed separately.', 'purple-optimize' ); ?></p>
	<?php if ( isset( $_GET['setup_error'] ) && is_string( $_GET['setup_error'] ) ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['setup_error'] ) ) ); ?></p></div>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Compatibility checks', 'purple-optimize' ); ?></h2>
	<p><?php esc_html_e( 'Checked automatically when this page loads. Purple 1.0.3 is the tested baseline; an older parent does not block companion installation. Verify storefront behavior when testing another parent version.', 'purple-optimize' ); ?></p>
	<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Component', 'purple-optimize' ); ?></th><th><?php esc_html_e( 'Installed', 'purple-optimize' ); ?></th><th><?php esc_html_e( 'Required / recommended', 'purple-optimize' ); ?></th><th><?php esc_html_e( 'Status', 'purple-optimize' ); ?></th></tr></thead><tbody>
	<?php foreach ( $checks as $check ) : ?>
	<tr><th><?php echo esc_html( $check['label'] ); ?></th><td><?php echo esc_html( $check['version'] ?: __( 'Not active', 'purple-optimize' ) ); ?></td><td><?php echo esc_html( $check['minimum'] . ( false === ( $check['required'] ?? true ) ? __( ' (recommended)', 'purple-optimize' ) : '' ) ); ?></td><td><?php echo esc_html( $check['ok'] ? __( 'Meets minimum', 'purple-optimize' ) : ( false === ( $check['required'] ?? true ) ? __( 'Older parent — testing allowed', 'purple-optimize' ) : __( 'Needs attention', 'purple-optimize' ) ) ); ?></td></tr>
	<?php endforeach; ?>
	<tr><th><?php esc_html_e( 'Companion toolkit', 'purple-optimize' ); ?></th><td><?php echo esc_html( $installed ? get_plugins()[ $file ]['Version'] : __( 'Not installed', 'purple-optimize' ) ); ?></td><td>0.11.10</td><td><?php echo esc_html( ! $toolkit_ok ? __( 'Update needed', 'purple-optimize' ) : ( $active ? __( 'Active', 'purple-optimize' ) : __( 'Optional setup', 'purple-optimize' ) ) ); ?></td></tr>
	</tbody></table>
	<h2><?php esc_html_e( 'Companion toolkit', 'purple-optimize' ); ?></h2>
	<?php if ( ! $toolkit_ok ) : ?><p class="notice notice-warning inline"><?php esc_html_e( 'Update the installed toolkit to 0.11.10 or newer using its ZIP before continuing. Your settings will be kept.', 'purple-optimize' ); ?></p>
	<?php elseif ( $active ) : ?>
	<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=purple-optimize' ) ); ?>"><?php esc_html_e( 'Open toolkit settings', 'purple-optimize' ); ?></a></p>
	<?php elseif ( $ready && current_user_can( 'activate_plugins' ) && ( $installed || current_user_can( 'install_plugins' ) ) && ! is_multisite() ) : ?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<input type="hidden" name="action" value="purple_optimize_setup_companion"><?php wp_nonce_field( 'purple_optimize_setup_companion' ); ?>
	<?php submit_button( $installed ? __( 'Activate companion toolkit', 'purple-optimize' ) : __( 'Install and activate companion toolkit', 'purple-optimize' ), 'primary', 'submit', false ); ?>
	</form>
	<?php else : ?>
	<p><?php esc_html_e( 'Resolve the requirements above, or ask an administrator to install and activate the toolkit. On multisite, use Network Admin.', 'purple-optimize' ); ?></p>
	<?php endif; ?>
	<p><?php esc_html_e( 'For now, installation uses the versioned GitHub release ZIP. No plugin is downloaded until you choose to install it. WordPress.org distribution is planned; it is not listed there yet.', 'purple-optimize' ); ?></p>
	<p><a href="https://github.com/solstudioim/purple-cro-optimized/releases"><?php esc_html_e( 'Download packages from GitHub releases', 'purple-optimize' ); ?></a>
	<?php if ( current_user_can( 'install_plugins' ) ) : ?> · <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=upload' ) ); ?>"><?php esc_html_e( 'Upload toolkit ZIP instead', 'purple-optimize' ); ?></a><?php endif; ?></p>
	<p><?php esc_html_e( 'If no release is published yet, build the separate ZIPs using the repository packaging script. Existing toolkit versions and settings are never overwritten by this setup action; update an installed copy using Plugins → Add New → Upload Plugin.', 'purple-optimize' ); ?></p></div>
	<?php
}

/** Install only the fixed release package, on an authorized POST, then verify activation. */
function purple_optimize_setup_companion(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'activate_plugins' ) || is_multisite() ) {
		wp_die( esc_html__( 'You cannot install or activate the companion here.', 'purple-optimize' ), '', array( 'response' => 403 ) );
	}
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( esc_html__( 'Use the setup form to continue.', 'purple-optimize' ), '', array( 'response' => 405 ) );
	}
	check_admin_referer( 'purple_optimize_setup_companion' );
	$redirect = admin_url( 'themes.php?page=purple-optimize-setup' );
	$checks   = purple_optimize_environment_checks();
	if ( ! purple_optimize_environment_ready( $checks ) ) {
		wp_safe_redirect( add_query_arg( 'setup_error', __( 'Resolve the compatibility requirements before activating the toolkit.', 'purple-optimize' ), $redirect ) );
		exit;
	}
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$file = 'purple-optimize-toolkit/purple-optimize-toolkit.php';
	if ( ! isset( get_plugins()[ $file ] ) ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( esc_html__( 'You cannot install plugins.', 'purple-optimize' ), '', array( 'response' => 403 ) );
		}
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( 'https://github.com/solstudioim/purple-cro-optimized/releases/download/v0.6.0/purple-optimize-toolkit-0.11.10.zip' );
		if ( is_wp_error( $result ) || ! $result || is_wp_error( $skin->result ) || $skin->get_errors()->has_errors() ) {
			wp_safe_redirect( add_query_arg( 'setup_error', __( 'Installation could not finish. Check that the GitHub release package exists, retry, or upload the toolkit ZIP using Plugins → Add New.', 'purple-optimize' ), $redirect ) );
			exit;
		}
		wp_clean_plugins_cache();
	}
	$plugins = get_plugins();
	if ( isset( $plugins[ $file ] ) && version_compare( $plugins[ $file ]['Version'], '0.11.10', '<' ) ) {
		wp_safe_redirect( add_query_arg( 'setup_error', __( 'Update the installed toolkit ZIP to 0.11.10 or newer before activation.', 'purple-optimize' ), $redirect ) );
		exit;
	}
	$result = activate_plugin( $file );
	if ( is_wp_error( $result ) || ! is_plugin_active( $file ) ) {
		wp_safe_redirect( add_query_arg( 'setup_error', __( 'Activation did not finish. Review Plugins for details and retry; your settings have been kept.', 'purple-optimize' ), $redirect ) );
		exit;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=purple-optimize' ) );
	exit;
}
add_action( 'admin_post_purple_optimize_setup_companion', 'purple_optimize_setup_companion' );
