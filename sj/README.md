# Magick AI Abilities Listing Assets

This folder is the working area for WordPress.org listing copy and visual assets.
It is intentionally excluded from release archives through `.gitattributes`.

## Directory Layout

- `positioning.md` - product positioning, audience, boundaries, and series map.
- `listing-copy-en.md` - English WordPress.org listing draft.
- `listing-copy-zh.md` - Chinese listing draft for reuse on Chinese channels.
- `image-prompts.md` - reusable AI image prompts for the icon and banner.
- `translation-notes.md` - bilingual listing and runtime translation notes.
- `license-notes.md` - image provenance and release notes.
- `source/` - original generated or editable source images.
- `exports/wordpress-org/` - final WordPress.org asset filenames and dimensions.
- `review/` - screenshots or review previews before publishing.

## WordPress.org Image Exports

The files that should be copied to the WordPress.org SVN top-level `assets/`
directory are:

- `exports/wordpress-org/banner-1544x500.png`
- `exports/wordpress-org/banner-772x250.png`
- `exports/wordpress-org/icon-256x256.png`
- `exports/wordpress-org/icon-128x128.png`

Do not copy this whole `sj` folder into the plugin zip.

## Bilingual Release Notes

The plugin code uses the `magick-ai-abilities` text domain for translatable
runtime strings. The WordPress.org listing should use English as the primary
directory copy, while `listing-copy-zh.md` can be reused for Chinese launch
posts, documentation, and marketplace-adjacent pages.
