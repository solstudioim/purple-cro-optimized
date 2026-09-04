import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
const read = (name) => readFileSync(new URL(`../wp-content/plugins/purple-optimize-toolkit/${name}`, import.meta.url), 'utf8');
const php = read('includes/checkout-content.php');

test('helpful content uses the native revision-capable WordPress editor', () => {
	assert.match(php, /register_post_type\( 'pot_checkout_info'/);
	assert.match(php, /'show_in_rest'\s+=> true/);
	assert.match(php, /'publicly_queryable'\s+=> false/);
	assert.match(php, /array\( 'title', 'editor', 'revisions' \)/);
	assert.match(php, /get_edit_post_link/);
	assert.match(php, /check_admin_referer\( 'pot_edit_checkout_content' \)/);
	assert.match(php, /current_user_can\( 'manage_woocommerce' \)/);
});

test('blocks render through core with an enforced content-only boundary', () => {
	assert.match(php, /do_blocks\( serialize_blocks\( \$blocks \) \)/);
	assert.match(php, /array_map\( 'pot_checkout_content_filter_block', \$block\['innerBlocks'\] \)/);
	assert.match(php, /unset\( \$block\['attrs'\]\['metadata'\]\['bindings'\] \)/);
	assert.match(php, /wp_kses_post\( do_blocks/);
	assert.match(php, /'publish' === \$post->post_status && ! \$post->post_password/);
	assert.doesNotMatch(php, /do_shortcode|apply_filters\( 'the_content'/);
});

test('shared typography and block assets reach both editor and checkout', () => {
	assert.match(php, /enqueue_block_assets/);
	assert.match(php, /wp_enqueue_scripts/);
	assert.match(php, /'pot_checkout_info' === \$screen->post_type/);
	assert.deepEqual(JSON.parse(read('blocks/checkout-content/block.json')).style, ['pot-checkout-extras', 'pot-checkout-content']);
	const css = read('assets/checkout-content.css');
	assert.match(css, /\.editor-styles-wrapper/);
	assert.match(css, /:is\(strong, b\).*font-weight: 700/);
});
