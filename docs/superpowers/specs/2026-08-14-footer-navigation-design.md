# Footer Navigation Design

## Goal

Replace the three repeated generic Navigation blocks with distinct, relevant footer link groups that work correctly on a fresh theme installation.

## Structure

- Shop: Shop all, Knitwear, Accessories, Wishlist
- Help: FAQs, Shipping & Returns, Contact Us, My Account
- About: About Us, Privacy Policy, Terms & Conditions

## Implementation

Use theme-owned `core/navigation-link` blocks inside each existing footer column. Links use confirmed local routes and do not depend on WordPress saved navigation entities, preventing the header menu from being repeated in every footer column.

## Scope

- Preserve the existing footer layout, brand statement, colors, and copyright line.
- Change only the three footer navigation groups.
- Keep the links editable through the Site Editor after installation.

## Verification

- Each group renders only its assigned links and labels.
- No group repeats the complete header navigation.
- Every link resolves on the local test site.
- The installed footer template part matches the repository source.
