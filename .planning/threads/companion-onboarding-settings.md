---
slug: companion-onboarding-settings
title: Companion onboarding and WooCommerce settings redesign
status: in_progress
created: 2026-10-30
updated: 2026-10-30
---

# Thread: Companion onboarding and WooCommerce settings redesign

## Goal

Ease child-theme adoption with explicit one-click companion setup and modern WooCommerce-aligned settings.

## Context

- User agreed with proposed one-click installation/activation onboarding and native WooCommerce settings direction.
- User selected Guided setup within Workspace sidebar and authorized completing the revamp, automatic compatibility checks, and GitHub distribution of separate theme and companion packages. WordPress.org listing is a future distribution goal.
- AGENTS.md now requires companion review and verification whenever syncing parent/child compatibility.
- Proposals: Section tabs, Workspace sidebar, Guided setup. Illustrative states only; do not imply real compatibility success or saving.
- Preserve existing settings and the immediate-save offer switch when implementing. Experimental Woo Settings UI needs a stable fallback.

## References

- wp-content/plugins/purple-optimize-toolkit/purple-optimize-toolkit.php
- wp-content/plugins/purple-optimize-toolkit/includes/checkout-extras.php
- https://developer.woocommerce.com/2026/07/08/unifying-extension-settings-in-woocommerce/
- /Users/sol/Codex/artifacts/purple-companion-ui/companion-wireframes.html

## Preview repair

- User reported "Invalid visualization read request" for the artifact-directory reference. Original file exists and parses; reissued identical content from the chat-scoped visualization directory. Canonical deliverable remains in the project artifacts directory. Host rendering confirmation remains with the newly emitted preview.

## Implementation outcome

- Implemented approved Workspace sidebar with guided Overview; native controls, six bookmarkable sections, saved offer preview and unsaved-edit protection.
- Settings saves are section-scoped and preserve other settings; independent checkout form and immediate offer switch retained. Offer activation validates configured products.
- Child 0.5.14 adds automatic local requirements checks and explicit install/activate setup from a fixed GitHub release ZIP. No background installation or overwriting installed copies. Old-version, capability, nonce, GET and multisite paths are bounded.
- Companion 0.10.0 adds distribution metadata and HPOS/block-checkout compatibility declarations.
- Added deterministic separate ZIP packaging, GitHub draft-release packaging workflow and combined PHP/WooCommerce CI matrix with browser tests.
- Local verification passes: 60 Node contracts, PHP contracts/syntax, combined WordPress integration, full admin/storefront browser, checkout add-ons, native content editor, offer toggles and real ZIP installer (local test-only download fixture).
- Isolated runtime: WP 7.1.4, WC 11.1.0, PHP 8.4.6; tested both legacy order storage and HPOS. Existing local store was not reconfigured.
- Fixed observed desktop native cart button visibility, long mobile title overflow and nested inherited FAQ margin overflow.
- Evidence: /Users/sol/Codex/artifacts/purple-companion-revamp/verification.md. Separate ZIPs and hashes: /Users/sol/Codex/artifacts/purple-cro-optimized/releases/.
- Review branch: codex/companion-workspace-setup. Retained existing mini-cart source improvements; unrelated existing screenshot, environment file and artifact directory are excluded from staging.

## Next Steps

- Inspect hosted CI and address any environment-specific failures.
- Review/merge the draft PR, then tag v0.5.14 and publish its draft release to make the fixed GitHub installer URL available.
- WordPress.org listing remains future work; replace the GitHub theme installer with the approved directory slug before Theme Directory submission.
