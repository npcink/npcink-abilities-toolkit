# PHPStan 依赖升级与收尾记录（2026-09-09）

## 背景

Dependabot PR #109 将 PHPStan 2.2.7 升级到 2.2.13，同时升级
`phpstan-wordpress` 到 2.0.4 与 WordPress stubs 到 7.1.0。升级后的 CI 在
PHP 8.0 和 8.3 上都在 PHPStan 并行 worker 中超过原有 2 GiB 限制。

## 定位结论

这是静态分析进程的内存峰值变化，不是插件运行时崩溃，也不是已发现的业务
类型错误。使用升级后的锁文件在隔离环境复现；即使关闭并行，单进程峰值仍
超过 2 GiB，说明主要压力来自新版分析器与 stubs 的类型索引，而非 worker
数量累加。

## 处理决定

将 `composer analyse:phpstan` 的显式内存上限从 2G 调整为 4G。保留完整
PHPStan 分析、原有级别和 WordPress smoke，不通过关闭分析、降低检查范围或
隐藏错误来恢复绿色。4G 是 CI 运行器可提供且足以覆盖当前代码规模的明确
资源预算；若未来代码规模继续增长，应拆分分析范围或优化 stubs 使用，而
不是无限提高上限。

## 验证

- 本地 PHPStan 2.2.13：`[OK] No errors`。
- GitHub CI：PHP 8.0、PHP 8.3、最低/当前 WordPress smoke、PR body contract
  全部通过。
- PR #109 已按 exact head guard squash merge。

## 可复用开发经验

1. 依赖升级失败先在锁文件对应的隔离 worktree 复现，避免用主线旧 vendor
   得出错误结论。
2. 区分资源失败与规则失败：先看失败阶段、PHP 版本、分析器版本和内存上限，
   再决定是否需要改代码。
3. 调整资源预算必须保留质量门禁；任何跳过 PHPStan 的临时方案都不能作为
   合并修复。
4. 推送修复后只接受新提交的 CI 结果，旧 run 的失败不能覆盖新 revision 的
   证据。
5. 合并后同步本地主线并 prune 已删除的 Dependabot tracking ref，最后检查
   worktree、stash、ahead/behind 和开放 PR，才能宣称收尾完成。

## 回滚

如 4G 在受限运行器上不可用，可回滚本文件对应的 composer 修改并将依赖锁
回退到上一组已验证版本；不得保留一个没有 PHPStan 证据的绿色状态。
