# Footer Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace repeated generic footer menus with three relevant, theme-owned link groups.

**Architecture:** Keep the existing block-theme footer structure and replace each empty `core/navigation` block with explicit `core/navigation-link` children. This avoids saved-navigation entity reuse while preserving Site Editor compatibility.

**Tech Stack:** WordPress block-theme HTML, Node.js contract tests, Valet local WordPress.

## Global Constraints

- Preserve the existing footer layout, brand statement, colors, and copyright line.
- Change only the three footer navigation groups.
- Keep the links editable through the Site Editor after installation.
- Do not stage or commit until the user approves the local result.

---

### Task 1: Theme-owned footer link groups

**Files:**
- Modify: `tests/storefront-content-contracts.test.mjs`
- Modify: `wp-content/themes/purple-optimize/parts/footer.html`

**Interfaces:**
- Consumes: Existing `pot-site-footer` four-column block structure.
- Produces: Three explicit `core/navigation` groups with unique link labels and routes.

- [ ] **Step 1: Write the failing contract test**

Assert that the footer contains one occurrence of each approved label, exactly three Navigation blocks with nested links, and no self-closing generic Navigation block.

- [ ] **Step 2: Run the focused test and confirm it fails**

Run: `node --test tests/storefront-content-contracts.test.mjs`

Expected: FAIL because the current footer contains three self-closing generic Navigation blocks and none of the approved grouped links.

- [ ] **Step 3: Replace the generic blocks**

Use these exact groups in `parts/footer.html`:

- Shop: `/shop/`, `/product-category/knitwear/`, `/product-category/accessories/`, `/wishlist/`
- Help: `/faqs/`, `/shipping-returns/`, `/contact/`, `/my-account/`
- About: `/about/`, `/privacy-policy/`, `/terms-conditions/`

Each link must be a `core/navigation-link` child of its group’s vertical `core/navigation` block.

- [ ] **Step 4: Run focused and full tests**

Run:

```bash
node --test tests/storefront-content-contracts.test.mjs
node --test tests/storefront-visual-contracts.test.mjs tests/storefront-content-contracts.test.mjs tests/performance-contracts.test.mjs
git diff --check
```

Expected: All tests pass and the diff check is clean.

- [ ] **Step 5: Sync and verify the local template part**

Copy the footer file to `/Users/sol/sites/purple-optimize/wp-content/themes/purple-optimize/parts/footer.html`. If a `wp_template_part` database override exists for the child-theme footer, update only that local override to the same markup. Read back the rendered homepage and verify each approved label appears once in the footer and every URL responds successfully.

- [ ] **Step 6: Preserve review-first Git state**

Run `git diff --cached --quiet` and `git status --short`. Do not stage or commit.
