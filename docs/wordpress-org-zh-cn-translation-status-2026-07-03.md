# WordPress.org zh_CN Translation Status

Date: 2026-07-03

This note records the current Simplified Chinese translation state for the
WordPress.org listing of `npcink-abilities-toolkit`.

## Summary

The Chinese translation has been submitted to translate.wordpress.org, but it
has not been approved yet.

Current public GlotPress status for `Stable Readme (latest release)` in
`Chinese (China)`:

- `All (101)`
- `Translated (0)`
- `Untranslated (0)`
- `Waiting (101)`
- `Changes requested (0)`
- `Fuzzy (0)`
- `Warnings (0)`

The practical meaning is:

- the 101 Chinese suggestions exist in GlotPress;
- the WordPress.org Chinese plugin page will not show them until approval;
- approval must be performed by a Chinese (China) locale editor or a project
  translation editor with sufficient permissions.

## Local Evidence

The original submission is recorded in
[`sj/listing-copy-zh.md`](../sj/listing-copy-zh.md):

- On 2026-06-22, `Stable Readme (latest release)` for `Chinese (China)` showed
  `Translated (0)` and `Untranslated (101)`.
- The filled PO was imported through GlotPress.
- GlotPress returned `101 translations were added`.
- After import, the status was `Waiting (101)`, `Untranslated (0)`, and
  `Warnings (0)`.

The post-publication closeout note
[`docs/wordpress-org-0.5.2-post-publication-closeout-2026-06-22.md`](wordpress-org-0.5.2-post-publication-closeout-2026-06-22.md)
records the same completion state and points to the archived PO:

`sj/translation/stable-readme-zh_CN.po`

## Runtime Locale Files Are Separate

The plugin package already ships repository-maintained starter locale files
under `languages/`, including zh_CN runtime translations.

Those files are not the authority for the localized WordPress.org plugin
directory page. WordPress.org directory translations are managed separately
through translate.wordpress.org / GlotPress, as documented in `readme.txt` and
the release runbook.

Do not treat a local `languages/*.po` update as approval or publication of the
WordPress.org listing translation.

## Approval Path

The next step is a Polyglots/PTE request for this plugin and locale.

Recommended Make Polyglots post:

```text
PTE Request for Npcink Abilities Toolkit

Hello Polyglots team,

I am the plugin author of Npcink Abilities Toolkit:
- https://wordpress.org/plugins/npcink-abilities-toolkit/

I have imported Chinese (China) translations for Stable Readme.
There are currently 101 zh_CN strings waiting for review.

I would like to request Project Translation Editor access for this plugin for
zh_CN, so I can review and maintain the Chinese translations.

WordPress.org username: muze233

Thank you.

o #zh_CN - @muze233
```

Use the `#zh_CN` locale tag and the Polyglots editor request flow so the
Chinese locale team is notified. After approval, re-check the GlotPress status
and the localized plugin directory page after WordPress.org cache refreshes.

## Status Terms

- `Waiting`: suggestions have been submitted but are not live.
- `Translated`: approved translations that can be used by WordPress.org.
- `Untranslated`: source strings without a current translation suggestion.
- `Warnings`: translations that need correction before approval.
