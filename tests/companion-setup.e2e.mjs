/** Destructive setup fixtures: use only a disposable local copy, never a live store. */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, renameSync, writeFileSync, readFileSync, copyFileSync, rmSync } from 'node:fs';
import path from 'node:path';
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = process.env.POT_WP_PATH;
const artifactRoot = process.env.POT_ARTIFACTS || '/Users/sol/Codex/artifacts/purple-companion-revamp/setup';
assert.ok(process.env.POT_DISPOSABLE_SITE === '1', 'This test requires POT_DISPOSABLE_SITE=1.');
assert.ok(root && process.env.POT_TOOLKIT_ZIP, 'Set POT_WP_PATH and POT_TOOLKIT_ZIP.');
const wp = (code) => execFileSync(process.env.POT_PHP || 'php', ['-d', 'error_reporting=22527', '/usr/local/bin/wp', 'eval', code, `--path=${root}`], { encoding: 'utf8' }).trim();
const site = wp('echo home_url();');
assert.match(new URL(site).hostname, /(^localhost$|^127\.0\.0\.1$|\.(test|localhost)$)/);
mkdirSync(artifactRoot, { recursive: true });
const pluginPath = path.join(root, 'wp-content/plugins/purple-optimize-toolkit');
const backup = path.join(artifactRoot, 'toolkit-before-setup');
assert.ok(!existsSync(backup), 'Remove or relocate the previous setup backup before rerunning.');
const muDir = path.join(root, 'wp-content/mu-plugins');
mkdirSync(muDir, { recursive: true });
const fixturePath = path.join(muDir, 'pot-test-package.php');
assert.ok(!existsSync(fixturePath), 'Do not replace an existing fixture.');
const saved = wp('echo base64_encode(serialize(get_option("pot_settings")));');
const fixtureZip = path.join(artifactRoot, 'toolkit-install-fixture.zip');
copyFileSync(process.env.POT_TOOLKIT_ZIP, fixtureZip);
const zipBase64 = Buffer.from(fixtureZip).toString('base64');
writeFileSync(fixturePath, `<?php
// Test-only: exercise WordPress's real ZIP installer using a local release package.
add_filter('upgrader_pre_download', static function($reply,$package){
 if ($package !== 'https://github.com/solstudioim/purple-cro-optimized/releases/download/v0.6.0/purple-optimize-toolkit-0.11.3.zip') return $reply;
 if (!get_option('pot_setup_fixture_allow')) return new WP_Error('simulated_unavailable','Test package unavailable');
 return base64_decode('${zipBase64}');
},10,2);
`);
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();
page.setDefaultTimeout(20000);
let moved = false;
try {
	wp('require_once ABSPATH."wp-admin/includes/plugin.php"; deactivate_plugins("purple-optimize-toolkit/purple-optimize-toolkit.php");');
	renameSync(pluginPath, backup); moved = true;
	await page.goto(`${site}/wp-login.php`);
	await page.locator('#user_login').fill(process.env.POT_TEST_USER || 'compat');
	await page.locator('#user_pass').fill(process.env.POT_TEST_PASSWORD || 'local-development-only');
	await Promise.all([page.waitForURL((url) => !url.pathname.endsWith('wp-login.php')), page.locator('#wp-submit').click()]);
	const setup = `${site}/wp-admin/themes.php?page=purple-optimize-setup`;
	await page.goto(setup);
	const install = page.getByRole('button', { name: 'Install and activate companion toolkit', exact: true });
	await install.waitFor();
	assert.equal(existsSync(pluginPath), false, 'Visiting setup must never install a plugin.');
	const invalid = await context.request.post(`${site}/wp-admin/admin-post.php`, { form: { action: 'purple_optimize_setup_companion', _wpnonce: 'invalid' } });
	assert.equal(invalid.status(), 403);
	const get = await context.request.get(`${site}/wp-admin/admin-post.php?action=purple_optimize_setup_companion`);
	assert.equal(get.status(), 405);
	const guest = await browser.newContext();
	const denied = await guest.request.post(`${site}/wp-admin/admin-post.php`, { form: { action: 'purple_optimize_setup_companion' } });
	assert.equal(denied.status(), 400);
	await guest.close();
	// Shop managers may configure the toolkit, but may not install plugins.
	wp('$id=wp_create_user("pot_setup_manager","local-manager-only","manager@example.test"); if(is_wp_error($id)){throw new Exception("Test manager already exists");} (new WP_User($id))->set_role("shop_manager");');
	const managerContext = await browser.newContext();
	const manager = await managerContext.newPage();
	await manager.goto(`${site}/wp-login.php`);
	await manager.locator('#user_login').fill('pot_setup_manager');
	await manager.locator('#user_pass').fill('local-manager-only');
	await Promise.all([manager.waitForURL((url) => !url.pathname.endsWith('wp-login.php')), manager.locator('#wp-submit').click()]);
	const forbidden = await managerContext.request.post(`${site}/wp-admin/admin-post.php`, { form: { action: 'purple_optimize_setup_companion' } });
	assert.equal(forbidden.status(), 403);
	await managerContext.close();
	// First simulate a missing GitHub package, then use the actual locally built ZIP.
	await Promise.all([page.waitForURL('**setup_error=*'), install.click()]);
	await page.getByText(/Installation could not finish/).waitFor();
	assert.equal(existsSync(pluginPath), false, 'Failed installation must remain retryable.');
	wp('update_option("pot_setup_fixture_allow",1);');
	await Promise.all([page.waitForURL('**/admin.php?page=purple-optimize'), install.click()]);
	await page.getByRole('heading', { name: 'Compatibility checks' }).waitFor();
	assert.equal(wp('echo is_plugin_active("purple-optimize-toolkit/purple-optimize-toolkit.php") ? "yes" : "no";'), 'yes');
	assert.equal(wp('echo base64_encode(serialize(get_option("pot_settings")));'), saved, 'Installing/activating must preserve existing settings.');
	await page.goto(setup);
	await page.getByRole('link', { name: 'Open toolkit settings' }).waitFor();
	await page.screenshot({ path: path.join(artifactRoot, 'installed.png'), fullPage: true });
	// An installed inactive toolkit uses activation only; no download is needed.
	wp('deactivate_plugins("purple-optimize-toolkit/purple-optimize-toolkit.php"); delete_option("pot_setup_fixture_allow");');
	await page.reload();
	await Promise.all([page.waitForURL('**/admin.php?page=purple-optimize'), page.getByRole('button', { name: 'Activate companion toolkit', exact: true }).click()]);
	assert.equal(wp('echo is_plugin_active("purple-optimize-toolkit/purple-optimize-toolkit.php") ? "yes" : "no";'), 'yes');
	// An outdated installed copy is never overwritten or activated by setup.
	wp('deactivate_plugins("purple-optimize-toolkit/purple-optimize-toolkit.php");');
	const mainFile = path.join(pluginPath, 'purple-optimize-toolkit.php');
	writeFileSync(mainFile, readFileSync(mainFile, 'utf8').replace('Version: 0.11.3', 'Version: 0.9.0'));
	await page.goto(setup);
	await page.getByText(/Update the installed toolkit to 0.11.3/).waitFor();
	assert.equal(await page.getByRole('button', { name: 'Activate companion toolkit', exact: true }).count(), 0);
	// The screen deliberately offers upload rather than activation for an old copy.
	assert.equal(wp('echo is_plugin_active("purple-optimize-toolkit/purple-optimize-toolkit.php") ? "yes" : "no";'), 'no');
	console.log('PASS: no automatic installation, invalid nonce and GET rejected, guest/shop-manager denied, outdated copy guarded, failed package retry, real ZIP installation, activation read-back, settings preservation, installed-inactive activation.');
} finally {
	rmSync(fixturePath, { force: true });
	rmSync(fixtureZip, { force: true });
	wp('delete_option("pot_setup_fixture_allow"); $u=get_user_by("login","pot_setup_manager"); if($u){require_once ABSPATH."wp-admin/includes/user.php";wp_delete_user($u->ID);}');
	if (moved) {
		rmSync(pluginPath, { force: true, recursive: true });
		renameSync(backup, pluginPath);
		wp('activate_plugin("purple-optimize-toolkit/purple-optimize-toolkit.php");');
	}
	await browser.close();
}
