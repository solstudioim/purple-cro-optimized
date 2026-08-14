# Commerce Clarity Submission Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the distributable child theme to Commerce Clarity and make its WordPress.org package and representative Theme Unit Test views pass all child-owned requirements.

**Architecture:** Keep Woo's Purple theme as the immutable parent and rename only the child-owned directory, metadata, PHP namespace, pattern namespace, documentation, and tests. Add generic responsive-content containment at the child layer, then verify an exact Git-built ZIP in a disposable WordPress installation so parent-derived Theme Check output is separated from child-package results.

**Tech Stack:** WordPress block child theme, PHP 7.4+, CSS, Node.js built-in test runner, WP-CLI, WordPress Importer, Theme Check, headless Chrome.

## Global Constraints

- Public theme name: `Commerce Clarity`.
- Theme directory, slug, and text domain: `commerce-clarity`.
- Parent declaration remains exactly `Template: purple`.
- Repository name and `purple-optimize-toolkit` plugin name remain unchanged.
- Do not bundle the Purple parent, WooCommerce, Toolkit, repository metadata, tests, or development files in the theme ZIP.
- Do not modify the active live `solwooplayground.blog` theme in this plan.
- Preserve the existing unrelated worktree changes in `.env`, `AGENTS.md`, and the performance screenshot.

---

### Task 1: Lock the directory-safe identity in contract tests

**Files:**
- Create: `tests/wordpress-org-theme-contracts.test.mjs`
- Modify: `tests/storefront-content-contracts.test.mjs`
- Modify: `tests/storefront-visual-contracts.test.mjs`

**Interfaces:**
- Consumes: repository root and theme files through Node's `fs` module.
- Produces: tests that use `wp-content/themes/commerce-clarity` as the canonical theme path and reject stale child-owned `purple-optimize` identifiers.

- [ ] **Step 1: Write failing identity and package-source tests**

Add tests that assert:

```js
assert.equal(header('Theme Name'), 'Commerce Clarity');
assert.equal(header('Template'), 'purple');
assert.equal(header('Text Domain'), 'commerce-clarity');
assert.match(readme, /^=== Commerce Clarity ===$/m);
assert.doesNotMatch(childFiles, /purple-optimize\//);
assert.equal(forbiddenFiles.length, 0);
```

The file walker must reject hidden files, ZIPs, SQL, logs, shell scripts,
`phpcs.xml.dist`, `node_modules`, `vendor`, and mixed CRLF/LF content inside the
child directory.

- [ ] **Step 2: Run the new tests and verify RED**

Run: `node --test tests/wordpress-org-theme-contracts.test.mjs tests/storefront-content-contracts.test.mjs tests/storefront-visual-contracts.test.mjs`

Expected: FAIL because `wp-content/themes/commerce-clarity` does not exist and existing contracts still resolve `purple-optimize`.

- [ ] **Step 3: Update existing contract paths only**

Change their child-theme path constants to:

```js
const theme = path.join(root, 'wp-content/themes/commerce-clarity');
```

Do not weaken assertions about Toolkit names or the GitHub repository URL.

- [ ] **Step 4: Re-run and confirm failures now concern production identity**

Run the Step 2 command.

Expected: FAIL on missing `commerce-clarity` production files, proving the tests exercise the future canonical package.

- [ ] **Step 5: Commit tests**

```bash
git add tests/wordpress-org-theme-contracts.test.mjs tests/storefront-content-contracts.test.mjs tests/storefront-visual-contracts.test.mjs
git commit -m "Test Commerce Clarity submission identity"
```

### Task 2: Rename the canonical child theme and internal namespace

**Files:**
- Rename: `wp-content/themes/purple-optimize/` to `wp-content/themes/commerce-clarity/`
- Modify: `wp-content/themes/commerce-clarity/style.css`
- Modify: `wp-content/themes/commerce-clarity/functions.php`
- Modify: `wp-content/themes/commerce-clarity/readme.txt`
- Modify: `wp-content/themes/commerce-clarity/templates/front-page.html`
- Modify: `wp-content/themes/commerce-clarity/templates/single-product.html`
- Modify: `wp-content/themes/commerce-clarity/patterns/*.php`

**Interfaces:**
- Consumes: parent handle `purple-style` and parent slug `purple`.
- Produces: `commerce_clarity_styles(): void`, `commerce_clarity_lazy_gallery_images(string): string`, style handle `commerce-clarity-style`, and pattern slugs under `commerce-clarity/*`.

- [ ] **Step 1: Rename the tracked theme directory**

Use `git mv wp-content/themes/purple-optimize wp-content/themes/commerce-clarity`.

- [ ] **Step 2: Apply the canonical metadata**

Set the stylesheet headers to:

```css
Theme Name: Commerce Clarity
Description: A conversion-focused child theme for Woo's Purple block theme.
Version: 0.6.0
Template: purple
Text Domain: commerce-clarity
```

Keep the existing GitHub Theme URI, author, WordPress/PHP version floors,
license, and allowed tags.

- [ ] **Step 3: Rename child-owned code and pattern identifiers**

Replace only child-owned identifiers:

```php
function commerce_clarity_styles(): void
function commerce_clarity_lazy_gallery_images( string $block_content ): string
```

Use `commerce-clarity-style` for the enqueue handle and
`commerce-clarity/storefront-home`, `commerce-clarity/product-story`, and
`commerce-clarity/product-comparison` for pattern slugs/references. Keep all
Toolkit identifiers unchanged.

- [ ] **Step 4: Update the theme readme identity and changelog**

Change the readme heading and theme references to Commerce Clarity, set stable
tag `0.6.0`, and add a `0.6.0` entry explaining the directory-safe identity and
submission hardening. Retain the Purple parent attribution and source URL.

- [ ] **Step 5: Run identity contracts and PHP/JSON checks**

Run:

```bash
node --test tests/wordpress-org-theme-contracts.test.mjs tests/storefront-content-contracts.test.mjs tests/storefront-visual-contracts.test.mjs
find wp-content/themes/commerce-clarity -name '*.php' -print0 | xargs -0 -n1 php -l
jq empty wp-content/themes/commerce-clarity/theme.json
```

Expected: identity contracts pass; all PHP files report no syntax errors; `jq` exits 0.

- [ ] **Step 6: Commit the canonical rename**

```bash
git add wp-content/themes/commerce-clarity wp-content/themes/purple-optimize tests
git commit -m "Rename child theme to Commerce Clarity"
```

### Task 3: Fix page-level mobile overflow with a regression contract

**Files:**
- Modify: `tests/wordpress-org-theme-contracts.test.mjs`
- Modify: `wp-content/themes/commerce-clarity/style.css`

**Interfaces:**
- Consumes: standard block markup rendered inside `main` and `.wp-block-post-content`.
- Produces: responsive CSS that keeps page-level `scrollWidth <= clientWidth` while allowing local scrolling for tables and `pre` blocks.

- [ ] **Step 1: Add the failing responsive CSS contract**

Assert that the stylesheet provides all of these behaviors:

```js
assert.match(css, /:where\(main, \.wp-block-post-content\).*min-width:\s*0/s);
assert.match(css, /overflow-wrap:\s*anywhere/);
assert.match(css, /:where\(table, pre\).*overflow-x:\s*auto/s);
assert.match(css, /:where\(img, video, iframe, svg\).*max-width:\s*100%/s);
```

- [ ] **Step 2: Run the responsive contract and verify RED**

Run: `node --test tests/wordpress-org-theme-contracts.test.mjs`

Expected: FAIL because the generic standard-content safeguards are absent.

- [ ] **Step 3: Add minimal content-boundary CSS**

Add a clearly labeled standard-content section that:

```css
:where(main, .wp-block-post-content),
:where(main, .wp-block-post-content) > * { min-width: 0; }
:where(main, .wp-block-post-content) { overflow-wrap: anywhere; }
:where(main, .wp-block-post-content) :where(img, video, iframe, svg) {
  max-width: 100%;
  height: auto;
}
:where(main, .wp-block-post-content) :where(table, pre) {
  display: block;
  max-width: 100%;
  overflow-x: auto;
}
```

Do not add `overflow-x: hidden` to `html` or `body`.

- [ ] **Step 4: Run the responsive contract and full repository suite**

Run:

```bash
node --test tests/wordpress-org-theme-contracts.test.mjs
node --test tests/*.test.mjs
php tests/storefront-presentation-contracts.php
```

Expected: all tests pass.

- [ ] **Step 5: Commit the responsive fix**

```bash
git add wp-content/themes/commerce-clarity/style.css tests/wordpress-org-theme-contracts.test.mjs
git commit -m "Contain standard content on mobile"
```

### Task 4: Update release, installation, and demo documentation

**Files:**
- Modify: `README.md`
- Modify: `docs/wordpress-org-theme-submission.md`
- Modify: `docs/demo/index.html`
- Modify: `tests/demo-pages-contracts.test.mjs`

**Interfaces:**
- Consumes: canonical theme directory `commerce-clarity`, version `0.6.0`, repository URL, and parent URL.
- Produces: installation and release commands that generate a single-root Commerce Clarity ZIP without renaming the Toolkit or repository.

- [ ] **Step 1: Add failing documentation contracts**

Require the demo and submission documentation to contain `Commerce Clarity`,
`commerce-clarity`, `0.6.0`, and the upstream Purple URL, and reject commands
that package `HEAD:wp-content/themes/purple-optimize`.

- [ ] **Step 2: Run documentation tests and verify RED**

Run: `node --test tests/demo-pages-contracts.test.mjs tests/wordpress-org-theme-contracts.test.mjs`

Expected: FAIL on stale public theme identity and package commands.

- [ ] **Step 3: Update documentation and release command**

Use this canonical command:

```bash
git archive --format=zip --prefix=commerce-clarity/ \
  -o commerce-clarity-0.6.0.zip \
  HEAD:wp-content/themes/commerce-clarity
```

State that the parent is maintained in WooCommerce's public `woo-themes`
repository. Do not mention WordPress.com. Document the parent-directory review
gate and the fact that the optional Toolkit is installed separately.

- [ ] **Step 4: Run documentation and full tests**

Run: `node --test tests/*.test.mjs`

Expected: all tests pass.

- [ ] **Step 5: Commit documentation**

```bash
git add README.md docs/wordpress-org-theme-submission.md docs/demo/index.html tests/demo-pages-contracts.test.mjs tests/wordpress-org-theme-contracts.test.mjs
git commit -m "Document Commerce Clarity release package"
```

### Task 5: Build and audit the exact release ZIP

**Files:**
- Verify: `wp-content/themes/commerce-clarity/**`
- Produce outside repo: `/private/tmp/commerce-clarity-0.6.0.zip`

**Interfaces:**
- Consumes: committed theme tree at `HEAD`.
- Produces: exact release ZIP used for all runtime review checks.

- [ ] **Step 1: Build the ZIP from Git**

Run:

```bash
git archive --format=zip --prefix=commerce-clarity/ \
  -o /private/tmp/commerce-clarity-0.6.0.zip \
  HEAD:wp-content/themes/commerce-clarity
```

- [ ] **Step 2: Audit ZIP structure and content**

Run `unzip -Z1` and verify one top-level `commerce-clarity/` directory, required
block-theme files, no prohibited/development files, and no
`purple-optimize/` child path. Extract to a fresh `mktemp -d` directory and run
PHP lint, `jq empty theme.json`, screenshot dimensions, and line-ending checks.

- [ ] **Step 3: Confirm package contracts against committed HEAD**

Run: `node --test tests/wordpress-org-theme-contracts.test.mjs`

Expected: all package-source checks pass.

### Task 6: Verify Theme Unit Test and Theme Check in a disposable site

**Files:**
- Install temporarily: `/private/tmp/commerce-clarity-review/`
- Capture temporarily: `/private/tmp/commerce-clarity-review-*.png`
- Record: `docs/wordpress-org-theme-submission.md`

**Interfaces:**
- Consumes: exact release ZIP, committed Purple parent, official WPTT XML, WordPress Importer, Theme Check.
- Produces: reproducible HTTP, debug-log, overflow, screenshot, and Theme Check evidence.

- [ ] **Step 1: Create an isolated local WordPress site**

Create `/private/tmp/commerce-clarity-review` with its own database and confirm
its `wp-config.php` points only to the disposable database before import.

- [ ] **Step 2: Install exact packages and official test data**

Install the committed Purple parent, install and activate
`/private/tmp/commerce-clarity-0.6.0.zip`, enable `WP_DEBUG`, install the
WordPress Importer and Theme Check plugins, and import:

`https://raw.githubusercontent.com/WPTT/theme-unit-test/master/themeunittestdata.wordpress.xml`

Expected: all 186 XML records process and Commerce Clarity 0.6.0 is active with parent Purple.

- [ ] **Step 3: Measure representative runtime states**

Serve the disposable site on localhost. Verify homepage, About The Tests,
Keyboard navigation, search, and 404 responses. Use headless Chrome at
1440x1100 and 390x844. In the page context assert:

```js
document.documentElement.scrollWidth <= document.documentElement.clientWidth
```

Capture screenshots and inspect them for clipped content. Clear the debug log
before frontend requests and require no theme PHP fatal, warning, or notice.

- [ ] **Step 4: Run Theme Check and classify every result**

Run current Theme Check against Commerce Clarity. For each result, prove its
source path. Child-owned required errors block completion. Parent-owned output
must be reported with the child ZIP listing showing the implicated parent file
is absent.

- [ ] **Step 5: Record exact results and clean up**

Update the submission document with the WordPress version, Theme Check plugin
version, test record count, viewport results, debug-log result, remaining parent
dependency gate, and the exact commands. Drop only the disposable database,
remove the disposable directory, and stop its localhost server.

- [ ] **Step 6: Commit review evidence**

```bash
git add docs/wordpress-org-theme-submission.md
git commit -m "Record Commerce Clarity submission checks"
```

### Task 7: Install and verify on the local `.test` site

**Files:**
- Install from: `/private/tmp/commerce-clarity-0.6.0.zip`
- Target: `/Users/sol/sites/purple-optimize/wp-content/themes/commerce-clarity/`

**Interfaces:**
- Consumes: exact verified ZIP and existing Purple parent.
- Produces: active local Commerce Clarity 0.6.0 installation for user review.

- [ ] **Step 1: Read the current local theme state**

Confirm the exact local path, active theme, parent presence, and absence or
presence of a `commerce-clarity` directory. Do not delete the previous
`purple-optimize` directory during this migration.

- [ ] **Step 2: Install and activate the exact ZIP**

Use WP-CLI to install `/private/tmp/commerce-clarity-0.6.0.zip`, activate
`commerce-clarity`, and flush relevant WordPress caches.

- [ ] **Step 3: Read back activation and render key pages**

Require WP-CLI to report Commerce Clarity 0.6.0 active with parent Purple.
Verify homepage, shop, product, cart, and checkout routes return expected
responses and the mobile unit-test overflow assertion remains true.

- [ ] **Step 4: Run final repository verification**

Run:

```bash
node --test tests/*.test.mjs
php tests/storefront-presentation-contracts.php
find wp-content/themes/commerce-clarity wp-content/plugins/purple-optimize-toolkit -name '*.php' -print0 | xargs -0 -n1 php -l
jq empty wp-content/themes/commerce-clarity/theme.json
git diff --check
git status --short
```

Expected: all tests and lint checks pass; status contains only intentional
Commerce Clarity commits plus the pre-existing unrelated screenshot, `.env`,
and `AGENTS.md` changes.
