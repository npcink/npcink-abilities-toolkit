# WordPress.org zh_CN Translation Status

Date: 2026-10-14. Status: current local draft; import and editor approval open.

The public Stable Readme project currently has 146 source strings: 0 approved,
86 waiting, 60 untranslated, and no fuzzy entries or warnings. The source set
was exported again after the 0.7.0 publication; the local PO now matches all
146 current `msgid` values and carries a Chinese translation for each one.

[Project](https://translate.wordpress.org/projects/wp-plugins/npcink-abilities-toolkit/stable-readme/zh-cn/default/)

## Prepared material

The [PO](../../marketing/translation/stable-readme-zh_CN.po) was reconciled
against the current GlotPress export. It preserves the 86 existing waiting
suggestions and supplies the 60 strings that are currently untranslated online.
`msgfmt --check` passes; no import or editor approval is claimed yet.

The [PTE request draft](../../marketing/translation/pte-request-zh_CN.md) names
`muze233` and zh_CN and remains unsent.

## Completion path

1. With the authenticated WordPress.org browser session, import the PO and
   record GlotPress's actual added/updated counts and warnings.
2. Have a locale/project translation editor approve the suggestions, then
   verify the approved count and the Chinese plugin directory after cache
   refresh.
3. Send the PTE request only with the maintainer's explicit authorization to
   contact Polyglots, or have the maintainer post the prepared draft.

Bundled `languages/*.po` and `.mo` are runtime starter translations. They do
not approve or publish the WordPress.org listing.

## Cross-repository baseline

The central quality matrix was rerun on 2026-10-14 after all configured
checkouts reported clean status. Toolkit, Core, Adapter, Workflow Toolbox,
Cloud Addon, Eval Lab, and AI Cloud all passed their configured gates. The
matrix did not change any sibling checkout or switch its branch.
