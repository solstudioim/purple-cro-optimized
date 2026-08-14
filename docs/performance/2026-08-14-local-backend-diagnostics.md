# Local backend performance diagnostics

Captured on 2026-08-14 against `purple-optimize.test`. These are development-environment diagnostics, not production capacity measurements.

| Check | Result | Interpretation |
| --- | ---: | --- |
| WordPress | 7.0.4 | Current local core version |
| WooCommerce | 11.0.0 | Current local plugin version |
| PHP | 8.4.6 | Modern runtime; WP-CLI 2.8.1 emits PHP 8.4 deprecation notices |
| Autoloaded options | 77,616 bytes | Small; no immediate autoload-bloat concern |
| Persistent object cache | No | Expected for a small Valet test site; production should be assessed separately |
| Due cron events | 18 | Review only if this persists or grows after normal cron execution |

## Guardrails applied

- Preserved WooCommerce's block cart and block checkout scripts and styles.
- Did not dequeue or deregister WooCommerce assets.
- Deferred only the dependency-free Purple Optimize Toolkit script.
- Used route flags to avoid observers, timers, storage reads, and DOM work for toolkit features that cannot render on the current page.
- Made no database, cache, cron, PHP, web-server, or plugin-activation changes during diagnostics.
