# GitHub Pages Demo Video Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a silent 75–95 second demo from `purple-optimize.test` that shows the complete pre-checkout upsell/downsell and post-purchase offer journey, then publish it on this repository's GitHub Pages site.

**Architecture:** Capture a deterministic set of real browser states from the local WooCommerce store into a temporary directory, then use a repository-owned FFmpeg script to turn those states into a captioned H.264 MP4 and poster. Publish only the compressed media and a small responsive static page through a GitHub Actions Pages workflow; keep raw frames, test customer data, and local browser state out of Git.

**Tech Stack:** WordPress/WooCommerce local Valet site, Chrome browser automation, WP-CLI, Bash, FFmpeg/ffprobe, static HTML/CSS, Node test runner, GitHub Actions Pages.

## Global Constraints

- Record only `http://purple-optimize.test`; do not modify `solwooplayground.blog` during production.
- Use test products, test payment details, and a newly created test order only.
- Produce 1920×1080 H.264 MP4 video with no audio stream and a target payload under 20 MB.
- Cover homepage, catalog, product, mobile sticky cart, add-to-cart feedback, pre-checkout upsell rejection, downsell acceptance, checkout completion, post-purchase offer, and toolkit validation.
- Link Purple only to the canonical WooCommerce source at `https://github.com/woocommerce/woo-themes/tree/trunk/purple`; do not describe it as a WordPress.com repository.
- Do not commit raw frames, browser profiles, credentials, test-order exports, `.env`, `AGENTS.md`, or unrelated performance screenshots.

---

### Task 1: Correct upstream wording and define the Pages contract

**Files:**
- Modify: `README.md`
- Modify: `docs/wordpress-org-theme-submission.md`
- Create: `tests/demo-pages-contracts.test.mjs`
- Create: `docs/demo/index.html`
- Create: `docs/demo/styles.css`
- Create: `docs/demo/.nojekyll`

**Interfaces:**
- Consumes: child-theme version from `wp-content/themes/purple-optimize/style.css`; toolkit version from `wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php`.
- Produces: a static page expecting `assets/purple-cro-demo.mp4` and `assets/purple-cro-demo-poster.webp`.

- [ ] **Step 1: Write the failing Pages contract**

Create `tests/demo-pages-contracts.test.mjs` with assertions that:

```js
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const page = fs.readFileSync(path.join(root, 'docs/demo/index.html'), 'utf8');
const css = fs.readFileSync(path.join(root, 'docs/demo/styles.css'), 'utf8');

test('demo page exposes an accessible non-autoplay video', () => {
  assert.match(page, /<video[^>]+controls/);
  assert.match(page, /poster="assets\/purple-cro-demo-poster\.webp"/);
  assert.match(page, /<source src="assets\/purple-cro-demo\.mp4" type="video\/mp4">/);
  assert.doesNotMatch(page, /\sautoplay(?:\s|=|>)/);
});

test('demo page links the project and canonical Woo Purple source', () => {
  assert.match(page, /https:\/\/github\.com\/solstudioim\/purple-cro-optimized/);
  assert.match(page, /https:\/\/github\.com\/woocommerce\/woo-themes\/tree\/trunk\/purple/);
  assert.doesNotMatch(page, /WordPress\.com repository/i);
});

test('demo page has a narrow responsive layout', () => {
  assert.match(css, /max-width:\s*1200px/);
  assert.match(css, /@media\s*\(max-width:\s*720px\)/);
  assert.match(css, /video\s*\{[^}]*width:\s*100%/s);
});
```

- [ ] **Step 2: Run the contract to verify it fails**

Run: `node --test tests/demo-pages-contracts.test.mjs`

Expected: FAIL because `docs/demo/index.html` and `docs/demo/styles.css` do not exist.

- [ ] **Step 3: Add the minimal responsive Pages shell**

Create `docs/demo/index.html` with one `<main>`, a hero heading, the native video
element defined by the contract, a text alternative summarizing the ten
storyboard steps, installation requirements, version labels `0.5.12` and
`0.7.4`, and two external repository links using
`rel="noreferrer noopener"`. Create `docs/demo/styles.css` using the theme's
warm canvas, deep-purple identity, orange action color, a 1200px centered
container, visible keyboard focus, and a 720px mobile breakpoint. Add an empty
`docs/demo/.nojekyll`.

Update repository documentation to say only:

```markdown
Purple is maintained in WooCommerce's public `woocommerce/woo-themes`
repository. It is not sourced from a WordPress.com repository.
```

- [ ] **Step 4: Run the Pages contract**

Run: `node --test tests/demo-pages-contracts.test.mjs`

Expected: PASS.

- [ ] **Step 5: Commit the page contract and shell**

```bash
git add README.md docs/wordpress-org-theme-submission.md docs/demo tests/demo-pages-contracts.test.mjs
git commit -m "Add GitHub Pages demo shell"
```

### Task 2: Synchronize and prepare the local funnel

**Files:**
- Create temporarily: `/private/tmp/purple-cro-demo/frames/*.png`
- Do not modify tracked source files.

**Interfaces:**
- Consumes: committed `purple-optimize` theme and `purple-optimize-toolkit` plugin.
- Produces: a local store with valid simple, visible, purchasable, in-stock upsell and downsell products and named capture states.

- [ ] **Step 1: Inspect the local target before writing**

Run:

```bash
node /Users/sol/Claude/woo-growth-team-toolset/.claude/skills/wp-wpcli-and-ops/scripts/wpcli_inspect.mjs --path=/Users/sol/sites/purple-optimize
wp --path=/Users/sol/sites/purple-optimize theme get purple-optimize --fields=name,status,version --format=json
wp --path=/Users/sol/sites/purple-optimize plugin get purple-optimize-toolkit --fields=name,status,version --format=json
```

Expected: the path resolves to `purple-optimize.test`; both packages are present.

- [ ] **Step 2: Install exact committed package archives**

Run:

```bash
git archive --format=zip --prefix=purple-optimize/ -o /private/tmp/purple-optimize-demo.zip HEAD:wp-content/themes/purple-optimize
git archive --format=zip --prefix=purple-optimize-toolkit/ -o /private/tmp/purple-optimize-toolkit-demo.zip HEAD:wp-content/plugins/purple-optimize-toolkit
wp --path=/Users/sol/sites/purple-optimize theme install /private/tmp/purple-optimize-demo.zip --force
wp --path=/Users/sol/sites/purple-optimize plugin install /private/tmp/purple-optimize-toolkit-demo.zip --force
wp --path=/Users/sol/sites/purple-optimize theme get purple-optimize --fields=name,status,version --format=json
wp --path=/Users/sol/sites/purple-optimize plugin get purple-optimize-toolkit --fields=name,status,version --format=json
```

Expected: both installs succeed; Purple Optimize remains active at `0.5.12`
and Purple Optimize Toolkit remains active at `0.7.4`.

- [ ] **Step 3: Validate offer products before capture**

Run one purposeful command that prints candidate simple products with the exact
eligibility fields used by the toolkit:

```bash
wp --path=/Users/sol/sites/purple-optimize eval '
$rows = array();
foreach ( wc_get_products( array( "status" => "publish", "type" => "simple", "limit" => 50 ) ) as $product ) {
    $rows[] = array(
        "id" => $product->get_id(),
        "name" => $product->get_name(),
        "visible" => $product->is_visible(),
        "purchasable" => $product->is_purchasable(),
        "in_stock" => $product->is_in_stock(),
        "price" => $product->get_price(),
    );
}
echo wp_json_encode( $rows, JSON_PRETTY_PRINT );
'
```

Expected: at least two different rows have `visible`, `purchasable`, and
`in_stock` set to true and a non-empty price.

- [ ] **Step 4: Configure the pre-checkout funnel**

Update only `pot_settings` using one command that deterministically selects the
first two eligible products by ascending ID and preserves every unrelated
setting:

```bash
wp --path=/Users/sol/sites/purple-optimize eval '
$eligible = array_values(
    array_filter(
        wc_get_products( array( "status" => "publish", "type" => "simple", "limit" => 50, "orderby" => "ID", "order" => "ASC" ) ),
        static function ( $product ) {
            return $product->is_visible() && $product->is_purchasable() && $product->is_in_stock() && "" !== $product->get_price();
        }
    )
);
if ( count( $eligible ) < 2 ) {
    throw new RuntimeException( "Two eligible simple products are required." );
}
$settings = get_option( "pot_settings", array() );
$settings = array_merge(
    $settings,
    array(
        "offer_funnel" => 1,
        "offer_placement" => "pre_checkout",
        "upsell_product_id" => $eligible[0]->get_id(),
        "upsell_discount" => 20,
        "upsell_countdown" => 10,
        "downsell_product_id" => $eligible[1]->get_id(),
        "downsell_discount" => 30,
        "downsell_countdown" => 10,
    )
);
update_option( "pot_settings", $settings );
echo wp_json_encode( array( "upsell" => $eligible[0]->get_id(), "downsell" => $eligible[1]->get_id(), "settings" => get_option( "pot_settings" ) ), JSON_PRETTY_PRINT );
'
```

Expected: the printed settings contain two different product IDs and
`offer_placement` is `pre_checkout`.

- [ ] **Step 5: Reset only capture-specific cart/session state**

Use a new incognito-like browser context or clear only the local demo browser's
WooCommerce cart cookies. Do not delete products, orders, users, or store data.

### Task 3: Capture the real funnel states

**Files:**
- Create temporarily: `/private/tmp/purple-cro-demo/frames/01-opening.png` through `/private/tmp/purple-cro-demo/frames/18-toolkit-validation.png`
- Create: `tools/demo/capture-checklist.md`

**Interfaces:**
- Consumes: the configured local store from Task 2.
- Produces: 18 ordered 1920×1080 PNG frames with no browser chrome or personal information.

- [ ] **Step 1: Create the capture checklist**

Document exact filenames and states:

```text
01-opening.png              homepage hero
02-home-trust.png           trust strip and categories
03-catalog.png              catalog with filters and product cards
04-product.png              centered gallery and buy box
05-mobile-sticky.png        narrow sticky add-to-cart state
06-cart-success.png         aligned success notice and animation end state
07-cart.png                 cart summary and checkout action
08-upsell.png               full-page pre-checkout upsell
09-downsell.png             downsell shown after rejecting upsell
10-downsell-accepted.png    accepted downsell in cart
11-checkout-contact.png     enclosed checkout contact and delivery
12-checkout-payment.png     test payment and order action
13-order-confirmed.png      completed test order without customer details
14-post-purchase.png        explicit post-purchase offer
15-post-purchase-detail.png separate-checkout explanation
16-toolkit-valid.png        valid offer selections
17-toolkit-warning.png      invalid product warning example
18-end-card-source.png      clean repository/source end card input
```

- [ ] **Step 2: Capture homepage through cart success**

Use browser viewport control at 1920×1080, navigate the local homepage, catalog,
and chosen product, then capture the named states. Temporarily use a 390×844
viewport only for `05-mobile-sticky.png`, reset to 1920×1080 immediately, and
compose that mobile capture on the 1920×1080 canvas during video assembly.

- [ ] **Step 3: Capture upsell rejection and downsell acceptance**

Proceed from cart to the pre-checkout offer. Capture `08-upsell.png`, reject the
upsell, verify the URL or heading changes to downsell, capture
`09-downsell.png`, accept it once, and capture the resulting cart state.

- [ ] **Step 4: Complete and capture a test checkout**

Use clearly synthetic details (`Demo Customer`, `demo@example.test`) and the
site's test gateway. Capture contact and payment states without exposing stored
addresses or real order data. Complete one order and capture only its success
heading and safe summary.

- [ ] **Step 5: Configure and capture the post-purchase placement**

Change only `offer_placement` to `post_purchase`, create a second synthetic test
checkout if required by the funnel, and capture the explicit follow-up offer and
separate-checkout explanation. Re-read `pot_settings` afterward.

- [ ] **Step 6: Capture toolkit validation states**

Capture the settings page once with valid products. Then temporarily select a
safe local hidden or missing test product to show the warning, capture it, and
restore the valid product IDs before leaving the local site.

- [ ] **Step 7: Audit all frames**

Inspect every frame for credentials, real names, email addresses, street
addresses, order IDs, browser notifications, and unrelated tabs. Retake any
unsafe frame. Confirm all desktop captures are 1920×1080 and the mobile capture
is 390×844.

### Task 4: Build and validate the silent video

**Files:**
- Create: `tools/demo/build-video.sh`
- Create: `tools/demo/captions.txt`
- Create: `docs/demo/assets/purple-cro-demo.mp4`
- Create: `docs/demo/assets/purple-cro-demo-poster.webp`
- Modify: `tests/demo-pages-contracts.test.mjs`

**Interfaces:**
- Consumes: ordered PNG frames from `/private/tmp/purple-cro-demo/frames` and caption lines from `tools/demo/captions.txt`.
- Produces: H.264/yuv420p MP4 with no audio and a 1920×1080 WebP poster.

- [ ] **Step 1: Extend the failing media contract**

Add tests that assert the build script and captions exist, the captions include
`Pre-checkout upsell`, `Downsell`, `Native WooCommerce checkout`, and
`Post-purchase offer`, and the Pages media paths exist. Run the test and confirm
it fails because those artifacts do not yet exist.

- [ ] **Step 2: Implement the deterministic FFmpeg build**

Create an executable Bash script that:

- accepts a frame directory as argument one and `docs/demo/assets` as argument two;
- validates all 18 named PNG files before starting;
- creates short 30fps 1920×1080 H.264 segments using `scale`, `pad`, `zoompan`,
  `fade`, and a bottom caption box;
- uses only text from `tools/demo/captions.txt`;
- concatenates segments in storyboard order;
- maps video only with `-an`, `-c:v libx264`, `-pix_fmt yuv420p`, `-movflags +faststart`;
- exports the first polished product frame as a 1920×1080 WebP poster;
- removes only its own temporary segment directory on success or failure.

- [ ] **Step 3: Run the build and media contract**

Run:

```bash
bash -n tools/demo/build-video.sh
tools/demo/build-video.sh /private/tmp/purple-cro-demo/frames docs/demo/assets
node --test tests/demo-pages-contracts.test.mjs
```

Expected: shell syntax PASS, build exit 0, Node contract PASS.

- [ ] **Step 4: Probe the final media**

Run:

```bash
ffprobe -v error -show_entries stream=codec_name,codec_type,width,height,pix_fmt -show_entries format=duration,size -of json docs/demo/assets/purple-cro-demo.mp4
```

Expected: one video stream only, codec `h264`, 1920×1080, pixel format
`yuv420p`, duration 75–95 seconds, size below 20,000,000 bytes.

- [ ] **Step 5: Visually inspect the complete MP4**

Play the output from beginning to end. Verify every caption is readable, no frame
contains sensitive data, upsell rejection visibly leads to downsell, checkout
precedes post-purchase, and the end card uses the WooCommerce repository link.

- [ ] **Step 6: Commit the reproducible build and final media**

```bash
git add tools/demo docs/demo/assets tests/demo-pages-contracts.test.mjs
git commit -m "Add silent full-funnel demo video"
```

### Task 5: Add and deploy GitHub Pages

**Files:**
- Create: `.github/workflows/pages.yml`
- Modify: `README.md`
- Modify: `tests/demo-pages-contracts.test.mjs`

**Interfaces:**
- Consumes: complete `docs/demo` static site.
- Produces: GitHub Pages deployment at `https://solstudioim.github.io/purple-cro-optimized/`.

- [ ] **Step 1: Add a failing deployment contract**

Extend `tests/demo-pages-contracts.test.mjs` to require a workflow containing
`actions/configure-pages`, `actions/upload-pages-artifact`,
`actions/deploy-pages`, `pages: write`, `id-token: write`, and artifact path
`docs/demo`. Run the contract and confirm it fails because the workflow is
missing.

- [ ] **Step 2: Add the minimal Pages workflow**

Create `.github/workflows/pages.yml` triggered on pushes to `main` that checks
out the repository, configures Pages, uploads `docs/demo`, and deploys it in a
`github-pages` environment with the required permissions and deployment
concurrency.

- [ ] **Step 3: Link the demo from the repository README**

Add a prominent `Demo video` link to
`https://solstudioim.github.io/purple-cro-optimized/`, keep the direct canonical
Woo Purple link, and do not claim the Pages URL is live until deployment
verification succeeds.

- [ ] **Step 4: Run the complete local verification suite**

Run:

```bash
node --test tests/*.test.mjs
php -d assert.exception=1 tests/storefront-presentation-contracts.php
find wp-content/themes/purple-optimize wp-content/plugins/purple-optimize-toolkit -name '*.php' -print0 | xargs -0 -n1 php -l
jq empty wp-content/themes/purple-optimize/theme.json tools/open-media-gallery.json
bash -n tools/demo/build-video.sh
git diff --check
```

Expected: all commands exit 0.

- [ ] **Step 5: Commit and push the Pages release**

```bash
git add .github/workflows/pages.yml README.md tests/demo-pages-contracts.test.mjs docs/demo
git commit -m "Publish demo on GitHub Pages"
git push origin main
```

- [ ] **Step 6: Enable Pages through GitHub Actions if needed**

Read current Pages state with:

```bash
gh api repos/solstudioim/purple-cro-optimized/pages
```

If it returns 404, create the Pages configuration once with:

```bash
gh api --method POST repos/solstudioim/purple-cro-optimized/pages -f build_type=workflow
```

Do not change visibility, branch protection, collaborators, or repository
permissions.

- [ ] **Step 7: Verify deployment and public playback**

Watch the Pages workflow to completion, open the public URL, verify HTTP 200 for
the page, poster, and MP4, play the video, check the browser console, and inspect
desktop plus mobile widths. Confirm both GitHub links resolve correctly.

- [ ] **Step 8: Record final deployment evidence**

Report the public Pages URL, final commit SHA, workflow conclusion, video
duration/size/codec, and the exact local/live package versions used in the demo.
