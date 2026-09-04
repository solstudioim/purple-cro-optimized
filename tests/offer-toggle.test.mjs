import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
const plugin = new URL('../wp-content/plugins/purple-optimize-toolkit/', import.meta.url);
const php = readFileSync(new URL('purple-optimize-toolkit.php', plugin), 'utf8');
const js = readFileSync(new URL('assets/admin-offer-toggle.js', plugin), 'utf8');

function setup(fetch) {
	const events = {};
	const checkbox = { checked: true, setAttribute() {}, removeAttribute() {}, addEventListener: (name, fn) => { events[name] = fn; }, form: { addEventListener: (name, fn) => { events[`form:${name}`] = fn; } } };
	const status = {};
	vm.runInNewContext(js, { document: { getElementById: (id) => id.endsWith('status') ? status : checkbox }, fetch, URLSearchParams, potOfferToggle: { url: '/admin-ajax.php', nonce: 'test', saving: 'Saving', enabled: 'Enabled', disabled: 'Disabled', error: 'Error' } });
	return { checkbox, status, events };
}

test('one click saves the explicit desired state without other settings', async () => {
	const ui = setup(async (url, request) => {
		assert.equal(url, '/admin-ajax.php');
		assert.equal(request.credentials, 'same-origin');
		assert.deepEqual([...request.body.keys()], ['action', 'nonce', 'enabled']);
		assert.equal(request.body.get('enabled'), '0');
		return { ok: true, json: async () => ({ success: true, data: { enabled: false } }) };
	});
	ui.checkbox.checked = false;
	await ui.events.change();
	assert.equal(ui.checkbox.checked, false);
	assert.equal(ui.status.textContent, 'Disabled');
});

test('failed save restores the prior value and allows retry', async () => {
	const ui = setup(async () => { throw new Error('offline'); });
	ui.checkbox.checked = false;
	await ui.events.change();
	assert.equal(ui.checkbox.checked, true);
	assert.equal(ui.status.textContent, 'Error');
});

test('pending save blocks repeated clicks and conflicting form submission', async () => {
	let finish;
	let calls = 0;
	const ui = setup(() => { calls++; return new Promise((resolve) => { finish = resolve; }); });
	ui.checkbox.checked = false;
	const pending = ui.events.change();
	await ui.events.change();
	let prevented = 0;
	ui.events.click({ preventDefault: () => { prevented++; } });
	ui.events['form:submit']({ preventDefault: () => { prevented++; } });
	assert.equal(prevented, 2);
	assert.equal(calls, 1);
	finish({ ok: true, json: async () => ({ success: true, data: { enabled: false } }) });
	await pending;
});

test('the one-click endpoint enforces permissions and nonce, and the control is unique', () => {
	const handler = php.slice(php.indexOf('function pot_ajax_set_offer_enabled'), php.indexOf("add_action( 'wp_ajax_pot_set_offer_enabled'"));
	assert.match(handler, /current_user_can\( 'manage_woocommerce' \)/);
	assert.match(handler, /check_ajax_referer\( 'pot_set_offer_enabled', 'nonce' \)/);
	assert.match(handler, /in_array\( \$enabled, array\( '0', '1' \), true \)/);
	assert.doesNotMatch(php, /wp_ajax_nopriv_pot_set_offer_enabled/);
	assert.equal((php.match(/name="pot_settings\[offer_funnel\]"/g) || []).length, 1);
	assert.doesNotMatch(php, /pot_checkbox_row\( 'offer_funnel'/);
});
