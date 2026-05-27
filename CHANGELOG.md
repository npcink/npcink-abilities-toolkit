# Changelog

## 0.1.0 - Unreleased

- Established Magick AI Abilities as a standalone WordPress Abilities API capability-package plugin.
- Added public helpers for category registration, readonly abilities, write-proposal abilities, schema normalization, annotation normalization, and registered ability inspection.
- Added the migrated WordPress read-only package: `site-info`, `list-post-types`, `list-taxonomies`, `count-posts`, `list-pages-tree`, `list-posts`, `get-post`, `resolve-url-to-post`, `get-post-blocks`, `list-post-revisions`, `list-media`, `list-terms`, `list-taxonomy-terms`, `list-categories`, `list-tags`, `get-term`, `propose-post-excerpt`, `list-users`, `list-comments`, `list-menus`, `get-menu`, `search-posts`, `get-post-stats`, `list-revisions`, `get-post-meta`, `list-pages`, `get-page`, and `inspect-page-structure`.
- Added standalone redacted WordPress diagnostics ability: `magick-ai-abilities/wp-diagnostics-summary`.
- Added a wp-admin test page under Tools -> Abilities API Packages.
- Added environment checks for Abilities API functions, REST routes, REST nonce usage, and Magick App Key non-usage.
- Added optional compatibility projection into the Magick AI catalog for provider abilities.
- Added integration rules for optional Magick AI consumption and a 0.1 public API freeze document.
- Added a WP-CLI smoke test for real WordPress environments.
- Added lightweight regression tests and PHP syntax linting.
