# Bilingual Publishing Notes

## English

Npcink Abilities Toolkit uses the `npcink-abilities-toolkit` text domain in runtime PHP
strings, so it is prepared for WordPress translation workflows.

For WordPress.org publishing, use `listing-copy-en.md` as the primary plugin
directory copy. Use `listing-copy-zh.md` for Chinese launch posts,
documentation, marketplace-adjacent pages, or as the source for future Chinese
translation work.

If bundled translation files are added later, use a standard `languages/`
directory and keep generated `.pot`, `.po`, and `.mo` files separate from this
`sj` publishing workspace.

Recommended release flow:

1. Keep source code strings in English.
2. Keep all runtime strings wrapped with the `npcink-abilities-toolkit` text domain.
3. Generate a POT file before release if bundled translations are needed.
4. Translate Chinese strings through the WordPress.org translation workflow or a
   project-owned `zh_CN` translation file.
5. Keep `marketing/` for listing copy, image prompts, and release artwork only.

## Chinese

Npcink Abilities Toolkit 的 PHP 运行时字符串使用 `npcink-abilities-toolkit` text domain，
因此已经具备接入 WordPress 翻译流程的基础。

发布到 WordPress.org 时，建议使用 `listing-copy-en.md` 作为插件目录主文案。
`listing-copy-zh.md` 用于中文发布文章、中文文档、国内渠道页面，或作为未来中文
翻译工作的源稿。

如果后续需要随插件包内置翻译文件，建议使用标准 `languages/` 目录，并将生成的
`.pot`、`.po`、`.mo` 文件和当前 `sj` 发布素材工作区分开。

推荐发布流程：

1. 源代码字符串继续保持英文。
2. 所有运行时字符串继续使用 `npcink-abilities-toolkit` text domain。
3. 如果需要内置翻译，在发布前生成 POT 文件。
4. 中文翻译可以走 WordPress.org 翻译流程，也可以维护项目自己的 `zh_CN` 翻译文件。
5. `marketing/` 只用于上架文案、图片提示词和发布素材。

## Scenario Strings In The POT (English)

The workflow scenario titles and natural task examples are contract data in
`includes/Workflow/Workflow_Definition_Provider.php`. They are localized for
the admin card view through the literal translation map in
`includes/Admin/Scenario_Translations.php` (one `__()` call per string, keyed
by the English contract text). Because those calls are literal msgids, standard
extraction such as `wp i18n make-pot` rediscovers them; do not replace the map
with dynamic `__()` calls on provider values — the packaged plugin must keep
passing the WordPress.org single-string-literal review rule, and the guard in
`tests/run.php` fails if the map, the template, or any bundled locale loses a
scenario entry. Recompile the `.mo` files with `msgfmt` after locale edits.
## POT 中的场景条目（中文）

工作流场景标题与自然任务示例是
`includes/Workflow/Workflow_Definition_Provider.php` 中的契约数据，后台卡片视图
通过 `includes/Admin/Scenario_Translations.php` 中的字面量映射表本地化（每个字
符串一个 `__()` 调用，以英文契约文本为键）。因为这些调用是字面量 msgid，标准
提取工具（如 `wp i18n make-pot`）可以重新发现它们；不要把映射表改回对提供器
取值动态调用 `__()`——打包插件必须继续通过 WordPress.org 的"单一字符串字面量"
审查规则。`tests/run.php` 中的守卫在映射表、模板或任一内置语言包丢失场景条目
时会立即失败。语言包修改后请用 `msgfmt` 重新编译 `.mo`。
