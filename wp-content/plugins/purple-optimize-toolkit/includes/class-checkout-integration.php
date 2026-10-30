<?php
/**
 * Load checkout extension components before WooCommerce hydrates its inner blocks.
 *
 * @package PurpleOptimizeToolkit
 */

defined( 'ABSPATH' ) || exit;

/** Checkout integrations are dependencies of WooCommerce's frontend entrypoint. */
class POT_Checkout_Integration implements \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface {
	/** Return the unique integration identifier. */
	public function get_name() {
		return 'purple-checkout';
	}

	/** Register assets before WooCommerce builds the checkout dependency graph. */
	public function initialize() {
		pot_register_checkout_assets();
	}

	/** Return components only when an optional checkout feature is enabled. */
	public function get_script_handles() {
		$settings = pot_checkout_settings();
		return $settings['upsells_enabled'] || $settings['content_enabled'] ? array( 'pot-checkout-extras' ) : array();
	}

	/** These automatically placed blocks have no editor component. */
	public function get_editor_script_handles() {
		return array();
	}

	/** Shopper data comes from the authenticated Store API cart extension. */
	public function get_script_data() {
		return array();
	}
}
