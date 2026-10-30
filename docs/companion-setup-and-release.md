> Local preview: child 0.6.0 and toolkit 0.11.3 are unpublished. Upload both locally built ZIPs manually for testing. The previous released flow below describes 0.5.14; do not publish a tag until local review is complete.

# Companion setup, settings and releases

The approved interface combines a persistent workspace sidebar with a guided
Overview. It uses native WordPress forms and WooCommerce product search while
following Woo's grouped-card layout and admin color scheme. It does not require
the experimental WooCommerce React Settings UI feature flag.

## Merchant flow

1. Install Purple and the Purple Optimize 0.5.14 child theme on WordPress
   7.1+, PHP 7.4+, and WooCommerce 11.1+.
2. Open **Appearance → Purple Optimize setup**. Local requirements are checked
   automatically; viewing the screen never downloads or activates a plugin.
3. Choose **Install and activate companion toolkit**. The action downloads the
   fixed `v0.5.14/purple-optimize-toolkit-0.10.0.zip` release asset from this repo.
   If no release is published or filesystem permissions prevent installation,
   download/build the separate ZIP and use **Plugins → Add New → Upload Plugin**.
4. If a supported toolkit is installed but inactive, the action activates it
   without downloading another copy. Older installed copies must be updated by
   ZIP upload; this flow never overwrites them or resets their settings.
5. Review **WooCommerce → Purple Optimize → Overview**, then follow its links
   to Storefront, Product pages, Cart & checkout, Offers and Privacy.

A section save changes only settings owned by that section. Checkout add-ons and
helpful content have their own **Save checkout features** action. The offer
switch still saves immediately with failure recovery; configure and save eligible
products before switching it on. The saved offer preview uses current catalog
prices and the saved discount, not unsaved edits. Appearance changes remain in
the Site Editor. Unsaved changes trigger the browser's standard navigation prompt.

On multisite, a network administrator installs/activates the toolkit using Network
Admin; the child setup screen explains this rather than attempting a network mutation.
Purple 1.0.3 is the tested parent baseline, not an installation requirement. An
older parent version shows a warning and still allows companion installation;
WordPress, PHP, child-theme and WooCommerce requirements remain enforced.
Version checks establish minimum requirements, not universal compatibility or
confirmation that merchant-customized templates are correct.

## Separate packages

From the repository root:

```sh
python3 tools/build-packages.py
```

The default output is the resolved local directory
`/Users/sol/Codex/artifacts/purple-cro-optimized/releases/`. The script packages the
current working tree, including uncommitted edits, into two independent installable
ZIPs and writes `packages.json` with SHA256 hashes. The theme never bundles the
plugin. Environment files, tests and repository metadata are outside both packages.
For a release, build from the reviewed, committed revision.

The **Build release packages** GitHub workflow also builds both ZIPs. Manual runs
retain downloadable Actions artifacts. Pushing a reviewed `v0.5.14` tag creates a
**draft** GitHub release containing the ZIPs; a maintainer reviews and publishes it
before the theme's install action can download the public asset. No release has
been published merely by adding the workflow. Future versions must update the
fixed installer URL and matching asset version together.

Until WordPress.org approval, updates are explicit ZIP replacements through
WordPress's upload confirmation. There is no background GitHub updater. The plugin
readme prepares directory metadata; listing/approval and contributor-name review
remain separate work. Before submitting the theme to WordPress.org, replace its
GitHub plugin recommendation/installer with the approved WordPress.org slug and
review current Theme Directory rules. Do not submit the GitHub installer as-is.

## Verification

**Theme and companion compatibility** runs on pushes, PRs and manual requests:

- Node contracts, storefront PHP contracts and PHP syntax checks.
- Isolated WordPress 7.1 with WooCommerce 11.1.0 and latest, on PHP 7.4 and 8.3.
- Combined version checks, registered child blocks, section rendering, scoped
  saves, nonce/access checks and preservation of other settings.
- Browser checks on PHP 8.3/WooCommerce 11.1.0, including settings persistence,
  failure recovery, mobile layout/navigation, native mini-cart, cart and checkout.
- An installer test using the real WordPress ZIP installer with a **test-only local
  package source**; it covers failed downloads, retries, activation and preservation.

Local runs of PHP integration tests require an isolated WordPress installation:

```sh
wp eval-file tests/companion-settings-integration.php --path=/path/to/isolated/site
POT_WP_PATH=/path/to/isolated/site node tests/companion-settings.e2e.mjs
```

Set `PLAYWRIGHT_MODULE` if Playwright is outside normal module resolution. Set
`POT_PHP` if needed. The installer browser test additionally requires
`POT_DISPOSABLE_SITE=1` and `POT_TOOLKIT_ZIP=/absolute/path/to/package.zip`; it moves
the disposable site's plugin directory and restores it in `finally`. Never run
that test on a merchant store. Tests never place an order or submit a payment.

Changes to any parent, child or companion integration require checks across all
three components, as recorded in the project AGENTS.md.
