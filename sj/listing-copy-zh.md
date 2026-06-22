# WordPress.org Stable Readme 中文翻译稿

用途：提交到 translate.wordpress.org 的 `Chinese (China) -> Stable Readme
(latest release)`。WordPress.org 插件目录页的中文展示来自 GlotPress
翻译审核，不会自动读取插件包内的 `languages/*.po` 或本文件。

项目地址：

`https://translate.wordpress.org/projects/wp-plugins/npcink-abilities-toolkit/stable-readme/zh-cn/default/`

当前状态记录：

- 2026-06-22 检查时，`Stable Readme (latest release)` 的 Chinese (China)
  显示 `Translated (0)`、`Untranslated (101)`。
- 2026-06-22 已使用 WordPress.org 账号通过 GlotPress PO 导入提交 101 条中文
  translation suggestions。页面返回 `101 translations were added`。
- 提交后状态为 `Waiting (101)`、`Untranslated (0)`、`Warnings (0)`。
- 当前账号没有显示 approve/bulk approve 控件，所以这些翻译还需要 Chinese
  (China) locale editor 或有权限的 project translation editor 审核。审核通过
  后，WordPress.org 中文插件目录页才会显示中文。

后续审核流程：

1. 打开上面的 Stable Readme 中文项目地址。
2. 切换到 `Waiting (101)`。
3. 由有权限的 reviewer 审核并 approve。
4. 审核通过后等待 WordPress.org 插件目录缓存刷新。

## 标题

Npcink Abilities Toolkit

## 标签

abilities api, agents, ai, automation

## 简短描述

为 AI host 和客户端暴露并检查 WordPress Abilities API 能力，不运行模型，也不写入内容。

## Description

Npcink Abilities Toolkit 帮助 WordPress 站点为 AI host 和客户端暴露、审查并安全检查 Abilities API 能力。

后台页面首先面向站点管理员。它会显示站点的能力包是否正常工作、当前有哪些能力可用、哪些能力是只读的，以及哪些类似写入的能力需要 host 审批。

对于开发者和 host runtime，插件也提供能力注册 helper、分类 helper、schema 规范化、annotation 规范化、REST discovery 值，以及在安装 Npcink AI 时可选的 Npcink AI canonical projection。

任何消费 WordPress Abilities API 的 WordPress 插件或客户端都可以使用它。Npcink AI 是一个可选消费者，并不是这个插件的所有者。

它不会运行 AI 模型、执行 workflow、路由 prompt、联系模型提供商、管理计费或额度、拥有 MCP 治理，也不会审批最终的 WordPress 写入。

只读的 host composition recipe metadata 可以记录 host 如何组合能力，但这些记录不会运行队列、调度任务、执行 workflow，也不会创建第二套 registry。

Host composition recipe metadata 通过只读 helper 和 Abilities API discovery abilities 提供给需要 catalog discovery、但不需要执行所有权的 host。

## External Services and Remote Requests

这个插件不会自动联系 Npcink AI、模型提供商、分析服务、追踪服务或云服务。

部分能力可以为独立的 host 或 cloud add-on 准备请求 payload，但这个插件本身不会把这些 payload 发送到外部服务。

`npcink-abilities-toolkit/upload-media-from-url` 能力可以在已审批的 host runtime commit 该能力时，从已认证调用方提供的 URL 下载媒体文件。在这种情况下，WordPress 会向调用方提供的 URL 发送 HTTP 请求，以便把媒体文件获取到本地媒体库。远程端点由调用方选择，不由本插件选择。

## Requirements

* WordPress 7.0 或更高版本。本版本有意面向 WordPress 7.0+ 提供的 WordPress Abilities API 基线。
* PHP 8.0 或更高版本。
* 在第三方 provider 插件或外部客户端发现并运行能力之前，WordPress Abilities API REST routes 必须可用。

## Public API

* `npcink_abilities_toolkit_register_category( $category_id, $args )`
* `npcink_abilities_toolkit_register_readonly( $ability_id, $definition )`
* `npcink_abilities_toolkit_register_write_proposal( $ability_id, $definition )`
* `npcink_abilities_toolkit_normalize_schema( $schema, $default_type )`
* `npcink_abilities_toolkit_normalize_annotations( $annotations, $risk_level )`
* `npcink_abilities_toolkit_get_registered()`
* `npcink_abilities_toolkit_get_workflow_definitions()`
* `npcink_abilities_toolkit_get_workflow_definition( $recipe_id )`

## Third-Party Integration Quickstart

Provider 插件应该等待 `plugins_loaded`，检查 public helper 是否存在，然后通过 helper 函数注册自己的能力：

`if ( function_exists( 'npcink_abilities_toolkit_register_readonly' ) ) { npcink_abilities_toolkit_register_readonly( 'acme/site-summary', $definition ); }`

不要 include 本插件 `includes/` 目录下的文件，也不要实例化 `Npcink_Abilities_Toolkit` namespace 里的类。那些都是实现细节。

只读上下文和诊断能力应该使用 `npcink_abilities_toolkit_register_readonly()`。只有当 callback 返回 proposal、preview、diff 或 handoff payload 时，才使用 `npcink_abilities_toolkit_register_write_proposal()`。第三方 provider callback 不应该执行最终的 host-governed commit。审批、audit、额度和最终写入授权属于消费方 host runtime。

REST 客户端应该先通过以下地址发现 catalog：

* `/wp-json/wp-abilities/v1/categories`
* `/wp-json/wp-abilities/v1/abilities`
* `/wp-json/wp-abilities/v1/abilities/{namespace}/{name}/run`
* `/wp-json/npcink-abilities-toolkit/v1/contract`

Contract endpoint 是面向已认证 host runtime 的兼容性和边界 discovery endpoint。它不会替代 WordPress Abilities API catalog，也不会运行 abilities。

完整的 provider 示例和 REST client 说明维护在公开仓库中：

`https://github.com/muze-page/npcink-abilities-toolkit`

如果缺少 `wp-abilities/v1` REST routes，请在连接第三方 provider 或客户端之前，启用 WordPress Abilities API 基线或兼容插件。

## Admin Page

与 Npcink AI host 插件一起启用后，在 wp-admin 中打开 Npcink AI -> AI Abilities。单独安装本包且没有 Npcink AI host 菜单时，打开 Tools -> Site AI Abilities。

这个页面首先面向站点管理员：它显示站点 ability 状态，用清晰标签和风险姿态分组可用 abilities，并可以运行两个官方只读检查：site info 和有边界的 redacted diagnostics summary。Checks tab 会在运行之前说明每个检查证明什么、不能证明什么。检查结果会以普通 summary table 展示，raw JSON 保留在 support disclosure 中。Developer Access 为 host/client 设置保留可复制的 REST endpoint 值、raw discovery fetch 和 ability ID export。它不会运行展示 workflow、模型调用、write abilities、审批流程或 demo abilities。

## Frequently Asked Questions

### Does this plugin run AI models?

不会。Npcink Abilities Toolkit 通过 WordPress Abilities API 暴露 WordPress abilities 和支持信息。模型路由、prompt 选择、托管 runtime 执行和 workflow 执行属于独立的 host 产品或客户端。

### Will this plugin change my posts, media, terms, comments, or settings by itself?

不会。后台页面检查都是只读的。部分内置 abilities 会描述类似写入或破坏性的操作，但最终 commit 需要 host runtime 自己提供审批、授权和 audit 层。

### Do I need Npcink AI to use this plugin?

不需要。Npcink AI 是可选消费者。这个插件也可以被其他消费 WordPress Abilities API 的插件或客户端使用。

### What do the Safe Checks prove?

Site Info 证明已授权的 ability client 可以读取基础 WordPress 站点信息。Redacted Diagnostics 证明站点可以返回适合支持排查的环境摘要，并省略敏感字段。这些检查不会调用模型、生成内容、联系外部服务，也不会自动修复配置。

### Does Redacted Diagnostics expose secrets?

不会。Diagnostics summary 会有意省略 Npcink AI 设置、MCP 设置、API keys、数据库名、table prefix、filesystem paths、error logs 和 external HTTP probes。

### What should I do if abilities are not visible to a host product or AI client?

打开 Tools -> Site AI Abilities；如果存在 Npcink AI host 菜单，则打开 Npcink AI -> AI Abilities。使用 Checks tab 确认安全的只读响应，然后使用 Available Abilities 查看站点暴露了哪些能力。开发者和 host 产品可以在 Developer Access 中获取 REST endpoint 值和 raw discovery responses。

### What if the wp-abilities/v1 REST routes are missing?

在客户端可以发现和运行 abilities 之前，WordPress Abilities API routes 必须可用。请为目标站点启用 WordPress Abilities API 基线或兼容插件。

## Screenshots

1. 站点 ability 状态概览，包含可用 ability 数量、写入保护、host 检测和下一步操作。
2. Available Abilities catalog，包含筛选、风险分组、可用性和用于支持排查的技术细节。
3. Safe Checks tab，在显示 summary results 和 raw response support details 之前，说明每个检查证明什么。
4. Developer Access tab，包含可复制的 REST endpoint 值、raw discovery fetch 和 ability ID export。

## Built-In Abilities

插件包含迁移后的低风险 WordPress read abilities、确定性的 comment helpers，以及使用规范 `npcink-abilities-toolkit/*` ids 的 host-governed WordPress write/destructive abilities。

它也包含 `npcink-abilities-toolkit/wp-diagnostics-summary`，这是面向 Abilities API client 的、只包含 WordPress 信息的 redacted diagnostics summary。该摘要会有意省略 Npcink AI 设置、MCP 设置、API keys、数据库名、table prefix、filesystem paths、error logs 和 external HTTP probes。

它也包含 `npcink-abilities-toolkit/search-posts` 和 `npcink-abilities-toolkit/search-post-meta`，用于 keyword 和明确 post-meta discovery 的有边界本地 WordPress search helpers。这些都是只读 helper，不会调用外部 search index，也不会修改内容。

它也包含 `npcink-abilities-toolkit/list-workflow-recipes` 和 `npcink-abilities-toolkit/get-workflow-recipe`，这是只读的 host composition recipe metadata discovery abilities。它们只暴露 metadata，不执行 workflow runtime behavior。

Core governance handoff 文档包含 catalog snapshot、permission matrix 和 schema boundary audit，供通过 `npcink-ai-core` 消费本插件的 host 使用。

## Developer Verification

默认本地源码 gate：

`composer test:all`

当插件安装在本地 WordPress 站点中时：

`WP_PATH=/path/to/wordpress composer smoke:wp`

如需隔离验证有边界链路性能：

`composer perf:smoke`

## Changelog

### 0.5.2

* 添加 image candidates、internal link candidates、taxonomy suggestions 和 comment reply suggestions 的只读 review artifacts。
* 保持 candidate review helpers 为 suggestion-only 和 host-governed；它们不会创建 proposals、approve work、execute writes 或 contact external providers。
* 为新的 handoff artifacts 扩展 acceptance contracts 和 first-party pack documentation。

### 0.5.1

* 为默认本地和 CI source gate 添加 Composer dependency advisory audit。
* 让 CI 和 PHPStan 对齐 package 的 PHP 8.0 runtime floor。
* 发布规范的公开 GitHub 仓库，并记录 post-publication gate baseline。
* 将 GitHub Actions checkout 升级到兼容 Node 24 的 `actions/checkout@v5`。

### 0.5.0

* 改进后台页面，让它成为更清晰的连接和 discovery surface，包含 package status、catalog navigation、可复制 REST endpoints 和两个有边界的只读 checks。
* 添加 bundled translation templates 和八个 starter locale packs，覆盖后台 connection/discovery surface、API ability labels/descriptions 和常见 runtime error messages。
* 从后台页面移除 development demo ability controls，并把 showcase、model-call、write 和 workflow execution 留在本包 surface 之外。
* 重命名非生产 cleanup abilities 和 media cleanup inputs，避免 released ability ids 和 schema fields 中出现公开 `test` 术语。
* 添加 taxonomy assignment proposal support，并加强 harvested workflow surfaces 的 Core consumer handoff checks。
* 强化 media replacement 和 Cloud derivative adoption previews，使已审批 replacement 可以修复精确 post-content media URLs，包括旧 intermediate-size URLs。
* 记录 foundation-layer testing strategy 和默认本地 source gate。

### 0.4.0

* 添加只读 host composition recipe metadata discovery abilities 和 public PHP helpers。
* 添加 Core governance handoff documentation、catalog snapshot fixture、permission matrix 和 schema boundary audit。
* 使用 `requires_approval`、明确 dry-run 和 commit defaults、以及有边界 idempotency keys 强化 write-like contracts。
* 扩展 governance metadata 和 schemas 的 REST verification coverage。
* 添加从已发现 ability contracts 准备 Core proposal payloads 的 consumer example。

### 0.3.0

* 添加 package 和 sub-pack filters，让 host 可以默认保留完整 catalog，或选择轻量 `core_wordpress_read` profile。
* 保持 public third-party helpers 为 read-only/write-proposal oriented，并记录 final commit authorization 属于 host runtime。
* 默认让 Npcink AI catalog projection 保持 thin，并添加 projection-row filter 供 host-owned policy expansion 使用。
* 将 `npcink-abilities-toolkit/*` 确立为本插件拥有 abilities 的规范 id namespace。
* 添加 explicit read/comment sub-pack maps，作为未来 source-file extraction 的 split point。
* 基于 WordPress 站点验证 Npcink AI catalog compatibility。

### 0.2.0

* 稳定 public helper contract 和 ability metadata rules。
* 记录 host-governed write/destructive semantics 和 Npcink AI integration boundary。
* 添加 first-party ability pack grouping 和 0.2 candidate verification evidence。
* 加强 schema controls、Npcink catalog projection、provider defaults 和 invalid ability ids 的 lightweight tests。
* 验证 WordPress REST coverage 和 Npcink AI consumer split-boundary checks。

### 0.1.0

* 初始 standalone release。
* 添加迁移后的 WordPress read abilities、deterministic comment helpers、host-governed write/destructive abilities，以及 standalone redacted WordPress diagnostics。
