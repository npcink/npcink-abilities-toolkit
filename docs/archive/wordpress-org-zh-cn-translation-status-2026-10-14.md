# WordPress.org zh_CN Translation Status

Date: 2026-10-14. Status: imported, approved, and visible in the Chinese
plugin directory.

The public Stable Readme project currently has 146 source strings. After the
authenticated import, GlotPress reported `146 translations added`; the public
project now shows 146 translated, 0 waiting, 0 untranslated, 0 fuzzy, and 0
warnings. The current entries show status `current` and are attributed to
`Npcink (muze233)`. The source set was exported again after the 0.7.0
publication; the local PO matches all 146 current `msgid` values and carries a
Chinese translation for each one.

[Project](https://translate.wordpress.org/projects/wp-plugins/npcink-abilities-toolkit/stable-readme/zh-cn/default/)

## Prepared material

The [PO](../../marketing/translation/stable-readme-zh_CN.po) was reconciled
against the current GlotPress export and imported through the logged-in browser
session. `msgfmt --check` and placeholder checks pass. A fresh public fetch of
the project confirms the approved counts above.

The localized directory page is also visible at
<https://cn.wordpress.org/plugins/npcink-abilities-toolkit/>. It serves the
0.7.0 package, uses the Chinese Stable Readme text for the description and
installation sections, and links the project back to GlotPress. The generic
`zh-cn.wordpress.org` host currently redirects to the WordPress.org home page;
the active Chinese directory host is `cn.wordpress.org`.

The [PTE request record](../../marketing/translation/pte-request-zh_CN.md)
was published on 2026-10-14 under `@muze233` and `#zh_CN`:

<https://make.wordpress.org/polyglots/2026/10/14/pte-request-for-npcink-abilities-toolkit/>

The closeout audit subsequently found that this duplicated the
[July multi-plugin request](https://make.wordpress.org/polyglots/2026/07/03/pte-request-for-npcink-workflow-toolbox/),
which already included Toolkit and received
[`#zh_CN done.` on July 7](https://make.wordpress.org/polyglots/2026/07/03/pte-request-for-npcink-workflow-toolbox/#comment-305520).
The [live Toolkit project editor list](https://translate.wordpress.org/locale/zh-cn/default/wp-plugins/npcink-abilities-toolkit/)
names `muze233`. The earlier claim that first-time access was still pending was
incorrect; maintaining future translations does not require a new grant.

## Completion path

1. Recheck the GlotPress counts and Chinese directory after the next Stable
   Readme source change or plugin release.
2. Use the existing project editor access to maintain future zh_CN Stable
   Readme updates; search completed requests and verify permissions before
   requesting access again.

Bundled `languages/*.po` and `.mo` are runtime starter translations. They do
not approve or publish the WordPress.org listing.

## Cross-repository baseline

The central quality matrix was rerun on 2026-10-14 after all configured
checkouts reported clean status. Toolkit, Core, Adapter, Workflow Toolbox,
Cloud Addon, Eval Lab, and AI Cloud all passed their configured gates. The
matrix did not change any sibling checkout or switch its branch.

That passing run is historical gate evidence. The later
[session closeout inventory](session-closeout-2026-10-14.md) found new active
Core and AI Cloud edits in a status-only matrix; it does not claim the whole
platform is currently clean.
