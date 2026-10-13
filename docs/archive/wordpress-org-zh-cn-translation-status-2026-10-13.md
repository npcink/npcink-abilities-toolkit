# WordPress.org zh_CN Translation Status

Date: 2026-10-13. Status: complete local draft; import and editor approval open.

The public stable-readme zh_CN project currently has 145 strings: 0 approved,
86 waiting and 59 untranslated, with no fuzzy entries or warnings. This is a
new snapshot; the July record remains historical evidence of the original
101-string submission.

[Project](https://translate.wordpress.org/projects/wp-plugins/npcink-abilities-toolkit/stable-readme/zh-cn/default/)

## Prepared material

The [PO](../../marketing/translation/stable-readme-zh_CN.po) preserves all 86
existing waiting suggestions and translates all 59 remaining current strings.
It also includes the planned 0.7.0 changelog and updated uninstall answer, which
GlotPress can match only after that source is refreshed. `msgfmt --check` passes.
No new import or translation approval is claimed.

The [PTE request draft](../../marketing/translation/pte-request-zh_CN.md) names
`muze233` and zh_CN. It has not been sent to the Polyglots team.

## Completion path

1. With an authenticated WordPress.org browser session, import the PO and record
   GlotPress's actual added/updated counts and warnings. Browser automation is
   currently unavailable on this device; do not confuse a local file with a
   submitted suggestion.
2. After 0.7.0 publication, export the current source again and reconcile any
   changed or added strings before declaring complete coverage.
3. Send the PTE request only with the maintainer's explicit authorization to
   contact Polyglots, or have the maintainer post the prepared draft.
4. A locale/project translation editor must approve the suggestions. Verify
   approved counts and the Chinese plugin directory after cache refresh.

Bundled `languages/*.po` and `.mo` are runtime starter translations. They do
not approve or publish the WordPress.org listing.
