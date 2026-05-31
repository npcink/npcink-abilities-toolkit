# WordPress.org 上架文案草稿 - 中文

## 插件名称

Magick AI Abilities

## 简短描述

面向 WordPress Abilities API 的能力内容包与 callback 合约层。

## 标签建议

abilities api, agents, ai, automation, developer tools

## 插件介绍

Magick AI Abilities 为 WordPress Abilities API 提供可复用的能力内容包和
callback 合约规范。

它帮助插件作者和 host runtime 通过稳定的合约层暴露可被 agent 安全调用的
WordPress 能力。插件包含能力注册 helper、分类 helper、schema 规范化、
annotation 规范化、内置 WordPress 只读能力、提案式写入能力、诊断能力，
以及面向 Magick AI host 的可选兼容投影。

这个插件属于 Magick AI 系列插件，但它本身保持独立可用。它可以被标准
WordPress Abilities API 客户端直接消费，也可以被需要稳定能力目录的 host
插件消费。

Magick AI Abilities 和官方 WordPress AI、MCP、Abilities API 生态插件是互补关系。
它不是模型客户端、MCP transport、云端运行时、workflow engine、计费系统、
额度系统，也不拥有 WordPress 写入操作的最终审批层。

## 核心功能

- 注册可复用的只读能力和提案式写入能力。
- 规范化 ability schema 和 annotation，方便 agent 安全消费。
- 提供内置 WordPress 读取、诊断、host composition recipe discovery、评论辅助能力。
- 在需要时保留兼容性的 `magick-ai/*` ability id。
- 将能力合约暴露给 host 插件，由 host 自己完成审批、preflight、audit。
- 保持 Magick AI 集成可选，而不是让 Magick AI 拥有 Abilities API 层。

## 适合谁使用

- 正在构建 Abilities API provider 的 WordPress 插件作者。
- 需要稳定能力目录的 host 插件。
- 消费 WordPress Abilities API 合约的 agent/client。
- 希望把能力定义、治理、transport、模型路由、云端执行分层处理的开发者。

## 环境要求

- WordPress 7.0 或更高版本。本版本有意面向 WordPress 7.0+ 提供的
  WordPress Abilities API 基线。
- PHP 8.0 或更高版本。

## 系列插件边界

在 Magick AI 系列插件中：

- Magick AI Abilities 负责能力定义和 ability callback。
- Magick AI Core 负责治理、审批、preflight、audit。
- Magick AI Adapter 负责 OpenClaw 通道适配。
- Magick AI Cloud Addon 负责链接云端服务。

这个分层让能力层保持可复用、可审查，并且更容易和 WordPress 官方生态互补。
