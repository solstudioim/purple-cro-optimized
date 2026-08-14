# Commerce Clarity Submission Hardening Design

## Goal

Prepare the child theme for WordPress.org review under the distinct public name
**Commerce Clarity**, while retaining Woo's Purple theme as its parent and
preserving the storefront design and WooCommerce block behavior.

## Identity and compatibility

- Rename the child-theme directory from `purple-optimize` to
  `commerce-clarity`.
- Set the public theme name to `Commerce Clarity`, the text domain to
  `commerce-clarity`, and keep `Template: purple` unchanged.
- Rename child-owned PHP functions, style handles, pattern slugs, template
  references, documentation, tests, and release commands consistently.
- Keep the repository name and optional Purple Optimize Toolkit plugin name
  unchanged. They are separate projects and are not bundled in the theme ZIP.
- Bump the theme version for the identity and submission-readiness change.

## Responsive Theme Unit Test fix

- Add narrowly scoped content safeguards for standard WordPress posts and
  pages: flexible descendants, wrapping long text, responsive media, and
  horizontal containment for intrinsically wide tables and preformatted
  content.
- Preserve deliberate horizontal scrolling inside the existing product
  comparison component.
- Avoid broad clipping on `body`; content should reflow or scroll at the
  component boundary instead of becoming inaccessible.

## Parent-theme review handling

- Package and audit only the child directory.
- Document that Theme Check automatically includes the Purple parent and can
  attribute the parent's text domain and development files to the child.
- Do not alter the vendored parent solely to silence a child-theme false
  positive. Record the exact automated output and the child-only package
  evidence for the reviewer.
- Keep the unresolved WordPress.org availability of the Purple parent as an
  explicit submission gate; code cannot make an unpublished parent installable
  from the directory.

## Tests and release evidence

- Add repository contract tests that fail for the old identity, stale pattern
  namespace, prohibited package files, mixed line endings, invalid metadata,
  and missing responsive content safeguards.
- Run the full existing test suite, PHP lint, JSON validation, and ZIP-content
  audit.
- Install the exact release ZIP with the Purple parent in a disposable
  WordPress site, import all official Theme Unit Test records, enable
  `WP_DEBUG`, and inspect representative desktop and 390-pixel mobile views.
- Run the current WordPress.org Theme Check plugin and distinguish child-owned
  failures from parent-derived output.

## Deployment boundary

- Update the repository and the local `.test` site after verification.
- Do not rename or redeploy the live `solwooplayground.blog` installation in
  this pass; changing an active theme slug is a deployment migration and needs
  a separate explicit approval after local review.

## Success criteria

- The distributable ZIP has one `commerce-clarity/` root and contains no
  development or prohibited files.
- Child metadata, namespace, documentation, tests, and version agree.
- Official Theme Unit Test representative views have no horizontal page-level
  overflow at 390 pixels and produce no theme PHP warnings, notices, or fatals.
- All child-owned required checks pass. Any remaining automated warning is
  proven to originate in the separately maintained Purple parent and is
  documented as a review dependency rather than misreported as a child pass.
