# WordPress.org zh_CN Translation Status

Date: 2026-10-14. Status: import complete; editor approval and directory cache
verification open.

The public Stable Readme project currently has 146 source strings. After the
authenticated import, GlotPress reported `146 translations added`; the page
now shows 146 translated, 0 waiting, 0 untranslated, 0 fuzzy, and 0 warnings.
The source set was exported again after the 0.7.0 publication; the local PO
matches all 146 current `msgid` values and carries a Chinese translation for
each one.

[Project](https://translate.wordpress.org/projects/wp-plugins/npcink-abilities-toolkit/stable-readme/zh-cn/default/)

## Prepared material

The [PO](../../marketing/translation/stable-readme-zh_CN.po) was reconciled
against the current GlotPress export and imported through the logged-in browser
session. `msgfmt --check` and placeholder checks pass. The page counts record
the import result; editor approval and public directory visibility are tracked
separately.

The [PTE request draft](../../marketing/translation/pte-request-zh_CN.md) names
`muze233` and zh_CN and remains unsent.

## Completion path

1. Have a locale/project translation editor approve the imported translations,
   then
   verify the approved count and the Chinese plugin directory after cache
   refresh.
2. Send the PTE request only with the maintainer's explicit authorization to
   contact Polyglots, or have the maintainer post the prepared draft.

Bundled `languages/*.po` and `.mo` are runtime starter translations. They do
not approve or publish the WordPress.org listing.

## Cross-repository baseline

The central quality matrix was rerun on 2026-10-14 after all configured
checkouts reported clean status. Toolkit, Core, Adapter, Workflow Toolbox,
Cloud Addon, Eval Lab, and AI Cloud all passed their configured gates. The
matrix did not change any sibling checkout or switch its branch.
