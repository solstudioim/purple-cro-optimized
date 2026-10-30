---
slug: parent-version-advisory
title: Make Purple parent version advisory for companion installation
status: resolved
created: 2026-10-30
updated: 2026-10-30
---

## Goal

Allow toolkit install/activation on WordPress.com Purple 0.0.x; keep 1.0.3 as an honest tested baseline and retain every other prerequisite.

## Context

- User clarified that the checklist must not block installation because WordPress.com uses a lower parent version number. This is not a request to repair hosting permissions or claim older-parent storefront compatibility.
- Actual guard is in child inc/companion-setup.php, not the companion. Updated both display and POST readiness checks through a shared policy helper.
- Companion checklist retains the baseline warning and labels the parent version recommended.
- No version bump or overwrite of published v0.5.14. Manual test packages retain their existing versions and are stored in a separate artifact directory.
- New PHP policy test covers 0.0.1/0.0.3/1.0.3 and failures of other prerequisites; 60 existing Node contracts pass. Added policy test to CI.

## References

- wp-content/themes/purple-optimize/inc/companion-setup.php
- wp-content/plugins/purple-optimize-toolkit/includes/admin-settings.php
- tests/parent-version-advisory.php
- /Users/sol/Codex/artifacts/purple-parent-advisory-test/

## Next Steps

- Verified the actual setup screen renders Install and activate companion toolkit with 0.0.3 metadata and an honest warning; restored parent metadata and reactivated the local toolkit afterward.
- Manual replacement ZIPs built in the artifact directory. No remote site changes, commits, pushes or published-release replacements were performed.
- User can upload both test ZIPs to replace the installed child/toolkit; no staging-site changes or public release publication are included in this local edit.
