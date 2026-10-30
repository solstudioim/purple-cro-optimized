---
slug: sync-purple-parent
title: Sync child theme with current WooCommerce Purple parent
status: resolved
created: 2026-10-30
updated: 2026-10-30
---

# Thread: Sync child theme with current WooCommerce Purple parent

## Goal

Update the vendored parent and reconcile child overrides with current Purple while preserving the CRO design and existing work.

## Context

- Existing uncommitted work: README, header, toolkit PHP/JS, content contract test, screenshot; preserve it.
- Parent was sourced from woocommerce/woo-themes; requested source is now woocommerce/woocommerce trunk/themes/purple.
- Upstream comparison pinned to latest path commit 2414b5c966ae7e79840e9b2bb6db607a4a703e8e.

## References

- https://github.com/woocommerce/woocommerce/tree/trunk/themes/purple
- /Users/sol/Codex/artifacts/purple-parent-sync/

## Outcome

- Vendored parent synchronized byte-for-byte to all 209 upstream files; obsolete files removed. Original 188-file parent backup verified with SHA256 under the artifact directory.
- Child release 0.5.13: WordPress 7.1/PHP 7.4/WooCommerce 11.1 requirements; inherited navigation overlay; product-only search; gallery controls aligned with upstream. CRO layout and existing drawer changes preserved.
- Attribution links and release documentation updated to WooCommerce monorepo.
- 59 Node tests pass; parent/child PHP syntax and git diff whitespace checks pass.
- Read-only WordPress 7.1.3 runtime loaded repository themes and verified asset versions, compatibility styles, 111 registered child blocks, inherited parts, and merged system typography.
- Existing local store uses commerce-clarity with WC 11.0.0. No activation, site install, browser validation, plugin upgrade, or checkout performed. Changes remain local and uncommitted.

## Next Steps

- Before deployment, test desktop/mobile storefront, navigation, cart and checkout with WooCommerce 11.1+ and Purple Optimize active on staging.
- Review Site Editor saved templates separately; file updates do not replace database customizations.
