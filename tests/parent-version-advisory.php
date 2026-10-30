<?php
/** Isolated behavior checks for the companion installer requirements policy. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'WC_VERSION', '11.1.0' );
$parent_version = '0.0.3';
function __( $text, $domain = '' ) { return $text; }
function add_action( ...$args ) {}
function get_template() { return 'purple'; }
function get_bloginfo( $key ) { return '7.1'; }
function wp_get_theme( $slug = '' ) {
	global $parent_version;
	return new class( $slug ? $parent_version : '0.5.14' ) {
		private $version;
		public function __construct( $version ) { $this->version = $version; }
		public function get( $key ) { return $this->version; }
	};
}
require dirname( __DIR__ ) . '/wp-content/themes/purple-optimize/inc/companion-setup.php';
$expect = static function ( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
};
foreach ( array( '0.0.1', '0.0.3', '1.0.3' ) as $version ) {
	$parent_version = $version;
	$checks = purple_optimize_environment_checks();
	$expect( purple_optimize_environment_ready( $checks ), 'Parent ' . $version . ' blocked setup.' );
	$expect( $checks['parent']['ok'] === version_compare( $version, '1.0.3', '>=' ), 'Parent baseline result was falsified.' );
}
foreach ( array( 'wordpress', 'php', 'child', 'woo' ) as $key ) {
	$checks = purple_optimize_environment_checks();
	$checks[ $key ]['ok'] = false;
	$expect( ! purple_optimize_environment_ready( $checks ), $key . ' requirement was bypassed.' );
}
echo "PASS: older Purple parents allow setup without falsifying the baseline; all other requirements still block.\n";
