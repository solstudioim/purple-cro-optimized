# Block-First Performance Baseline

Measured on 2026-08-14 using isolated headless Chrome, a 390 x 844 mobile viewport, and simulated 4G latency against `purple-optimize.test`.

| Route | Transfer | Requests | TTFB | DOMContentLoaded | Load |
|---|---:|---:|---:|---:|---:|
| Homepage | 728 KB | 43 | 786 ms | 2,472 ms | 4,546 ms |
| Shop | 316 KB | 58 | 341 ms | 1,821 ms | 1,822 ms |
| Product | 484 KB | 68 | 320 ms | 1,858 ms | 3,015 ms |
| Cart | 819 KB | 78 | 299 ms | 4,683 ms | 4,686 ms |
| Checkout | 819 KB | 78 | 515 ms | 4,903 ms | 4,906 ms |

Dominant payloads were homepage imagery (538 KB), product imagery (246 KB), and required WooCommerce Blocks JavaScript on cart and checkout (696 KB).

These are local engineering measurements for before-and-after comparison. They are not production Core Web Vitals or field data.
