import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
const plugin = new URL('../wp-content/plugins/purple-optimize-toolkit/', import.meta.url);
const read = (file) => readFileSync(new URL(file, plugin), 'utf8');
const php = read('includes/checkout-extras.php');
const js = read('assets/checkout-extras.js');

test('checkout enhancements are native inner blocks with scoped assets', () => {
	for (const name of ['checkout-upsells', 'checkout-content']) {
		const metadata = JSON.parse(read(`blocks/${name}/block.json`));
		assert.equal(metadata.apiVersion, 3);
		assert.equal(metadata.supports.inserter, false);
		assert.equal(metadata.viewScript, 'pot-checkout-extras');
		assert.match(read(`blocks/${name}/render.php`), /get_block_wrapper_attributes/);
		assert.ok(js.includes(metadata.name));
	}
	assert.match(php, /render_block_woocommerce\/checkout-actions-block/);
	assert.match(php, /render_block_woocommerce\/checkout-order-summary-block/);
	assert.doesNotMatch(js, /MutationObserver|insertBefore|createPortal/);
});

test('checkout changes use the Store API without posting the checkout form', () => {
	assert.match(php, /woocommerce_store_api_register_update_callback/);
	assert.match(js, /extensionCartUpdate/);
	assert.match(js, /overwriteDirtyCustomerData: false/);
	assert.match(js, /type: 'button'/);
	assert.match(js, /role: 'alert'/);
	assert.match(js, /role: 'status'/);
	assert.doesNotMatch(js, /location\.reload|\.submit\(/);
});

test('checkout configuration remains independent and safely editable', () => {
	assert.match(php, /settings_fields\( 'pot_checkout_settings_group' \)/);
	assert.match(php, /current_user_can\( 'manage_woocommerce' \)/);
	assert.match(php, /Open block editor/);
	assert.doesNotMatch(php, /wp_editor\(/);
	assert.match(php, /wp_kses_post/);
	assert.match(php, /'display_context' => 'cart'/);
	assert.doesNotMatch(php, /update_post_meta\(|wp_insert_post\(/);
});


test('checkout registers extension components before the frontend hydration entrypoint', () => {
 assert.match(php, /woocommerce_blocks_checkout_block_registration/);
 const integration = read('includes/class-checkout-integration.php');
 assert.match(integration, /implements \\Automattic\\WooCommerce\\Blocks\\Integrations\\IntegrationInterface/);
 assert.match(integration, /pot_register_checkout_assets\(\)/);
 assert.match(integration, /get_script_handles/);
 assert.match(integration, /array\( 'pot-checkout-extras' \)/);
});
