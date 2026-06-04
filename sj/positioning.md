# Npcink Abilities Toolkit Positioning

## English

Npcink Abilities Toolkit is the ability content package and contract specification
layer for the WordPress Abilities API.

It provides reusable ability definitions, ability callbacks, categories, schema
normalization, annotation normalization, and safe agent-callable WordPress
capabilities. It can be consumed directly through the WordPress Abilities API or
used by host plugins that need a stable ability catalog.

Npcink Abilities Toolkit can participate in a Magick AI host setup:

- `npcink-abilities-toolkit` - ability definitions and ability callbacks.
- `magick-ai-core` - governance, approval, preflight, and audit.
- `magick-ai-adapter` - OpenClaw channel adaptation that calls Core and the
  Abilities API.
- `magick-ai-cloud-addon` - cloud service connection.

It complements official WordPress AI, MCP, and Abilities API ecosystem plugins.
It is not an AI product plugin, MCP transport, model client, cloud runtime,
billing system, quota system, workflow engine, or final write approval layer.

## Chinese

Npcink Abilities Toolkit 是 WordPress Abilities API 生态中的能力内容包和合约规范层。

它负责可复用的能力定义、ability callback、能力分类、schema 规范化、annotation
规范化，以及可被 agent 安全调用的 WordPress 能力。它既可以被标准 WordPress
Abilities API 直接消费，也可以被需要稳定能力目录的 host 插件消费。

Npcink Abilities Toolkit 是 Magick AI 系列插件的一部分：

- `npcink-abilities-toolkit` - 能力定义和 ability callback。
- `magick-ai-core` - 治理、审批、preflight、audit。
- `magick-ai-adapter` - OpenClaw 通道适配，调用 Core 和 Abilities API。
- `magick-ai-cloud-addon` - 链接云端服务。

它和官方 WordPress AI、MCP、Abilities API 生态插件是互补关系，不是直接替代关系。
它不是 AI 产品插件、MCP transport、模型客户端、云端运行时、计费系统、额度系统、
workflow engine，也不拥有最终写入审批层。
