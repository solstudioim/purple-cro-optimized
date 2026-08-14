# WordPress.org theme submission preparation

## Package scope

Submit only the `purple-optimize` child-theme directory. Do not include the
Purple parent theme, Purple Optimize Toolkit, demo tools, repository metadata,
performance captures, or environment files.

Build the ZIP from a committed release:

```sh
git archive --format=zip \
  --prefix=purple-optimize/ \
  -o purple-optimize-0.5.12.zip \
  HEAD:wp-content/themes/purple-optimize
```

## Upstream and licensing

- Parent: [Woo Purple](https://github.com/woocommerce/woo-themes/tree/trunk/purple)
- Parent author: Automattic
- Parent license: GPLv2 or later
- Child license: GPLv2 or later
- The child theme does not bundle parent assets or the companion plugin.
- The screenshot is 1200 by 900 pixels and is declared CC0 in `readme.txt`.

## Automated and manual checks

- Confirm `style.css`, `templates/index.html`, `theme.json`, `readme.txt`, and
  `screenshot.png` are present in the ZIP.
- Confirm the ZIP has one top-level `purple-optimize/` directory.
- Run the current Theme Check plugin against the release candidate.
- Test with the official Theme Unit Test content.
- Test activation with Purple installed and with WooCommerce both active and
  inactive; the admin must not show PHP errors or warnings.
- Verify storefront, Site Editor, keyboard navigation, narrow layouts, RTL, and
  translation strings.
- Confirm the WordPress.org contributor username in `readme.txt` before upload.

## Confirm with the Themes Team before upload

1. Purple is currently distributed through Woo's public repository and
   WordPress.com rather than the WordPress.org theme directory. Confirm whether
   the parent must be accepted into the directory first so dependency
   installation and review can work correctly.
2. Reviewer guidance says child-theme names should not contain the parent-theme
   name. Confirm whether `Purple Optimize` is acceptable or select a distinct
   public name and slug before the first submission.

These are submission blockers, not packaging failures. Ask in the WordPress.org
`#themes` Slack channel before reserving a name or uploading the ZIP.
