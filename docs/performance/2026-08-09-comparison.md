# Storefront performance comparison

Date: 2026-08-09  
Environment: local Laravel Valet at `purple-optimize.test`  
Runtime before: `af49ce3`  
Runtime after: `43b2ffe`

Five sequential compressed HTTP samples were collected on each route. A change
within 5% is classified as Neutral because local PHP, database, and warm-up
variance is larger than the implementation's expected server-side effect.

| Metric | Before | After | Delta | Verdict |
| --- | ---: | ---: | ---: | --- |
| Empty-page toolkit intervals | 1 | 0 | -1 | Improved |
| Empty-page unrelated body observers | 1 | 0 | -1 | Improved |
| Theme-enforced hidden gallery `lazy/low` strategy | Absent | Present | Enforced | Improved |
| Home median TTFB | 173.651 ms | 175.526 ms | +1.875 ms (+1.08%) | Neutral |
| Shop median TTFB | 179.662 ms | 180.960 ms | +1.298 ms (+0.72%) | Neutral |
| Product median TTFB | 186.627 ms | 187.080 ms | +0.453 ms (+0.24%) | Neutral |
| Cart median TTFB | 138.658 ms | 141.088 ms | +2.430 ms (+1.75%) | Neutral |
| Checkout median TTFB | 130.299 ms | 135.729 ms | +5.430 ms (+4.17%) | Neutral |
| Home compressed HTML | 35,014 B | 35,014 B | 0 B | Neutral |
| Shop compressed HTML | 42,023 B | 42,023 B | 0 B | Neutral |
| Product compressed HTML | 39,463 B | 39,468 B | +5 B | Neutral |
| Cart compressed HTML | 46,748 B | 46,747 B | -1 B | Neutral |
| Checkout compressed HTML | 42,250 B | 42,253 B | +3 B | Neutral |
| Toolkit JavaScript gzip | 4,723 B | 4,847 B | +124 B (+2.63%) | Regressed |
| Theme + toolkit total gzip | 10,436 B | 10,561 B | +125 B (+1.20%) | Regressed |
| Browser console errors | Not observed | 0 | No new errors | Neutral |

## Acceptance result

**PASS**

- PASS: the contract harness changed from one idle interval and one unrelated
  observer to zero of each.
- PASS: every content-route median remained below the 250 ms local threshold.
- PASS: the primary product image remained `loading="eager"` and
  `fetchpriority="high"`; eight checked non-primary gallery images were
  `loading="lazy"`, `fetchpriority="low"`, and retained `srcset`.
- PASS: the timer updated, add to cart produced one View cart link, the cart and
  checkout each retained one native primary action, empty-cart behavior worked,
  checkout labels remained present, and no browser-console errors were recorded.
- PASS: no existing attachment was rewritten and the open-media importer was
  not executed during verification.
- PASS with explicit tradeoff: the lifecycle guards add 124 gzip bytes to the
  toolkit JavaScript and the version-only theme change adds one gzip byte. This
  is accepted because it removes continuous work from unrelated pages and
  bounds observers to unresolved, present components.

The browser asset categorizer used for the before inventory was unavailable in
the after-state browser, so asset-count deltas are intentionally not claimed.
Current DOM declaration counts are retained in the after JSON for auditability.

## Interpretation

This implementation improves avoidable frontend work and image scheduling. It
does not materially change local server response time, and the measured TTFB
movements are within the stated noise band. Production page caching, object
caching, CDN/image delivery, and field Core Web Vitals still need validation in
the eventual hosting environment.
