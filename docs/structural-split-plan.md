# Structural Split Plan

Status: active incremental refactor plan.
Date: 2026-07-11.

This plan reduces review cost and change collisions without combining structural
moves with ability changes. Every slice must preserve public ability ids,
schemas, annotations, callbacks, lazy loading, dry-run defaults, and host-owned
approval and final authorization.

## Baseline Inventory

The first inventory measured the largest PHP files before the initial slice:

| File | Baseline lines | Primary responsibility |
| --- | ---: | --- |
| `includes/Packages/Core_Write_Package.php` | 8,183 | Write definitions, callbacks, shared guards, and media operations. |
| `includes/Packages/Read_Traits/Media_Read_Methods.php` | 7,771 | Media analysis and planning reads. |
| `tests/run.php` | 7,761 | Source-level contract and behavior assertions. |
| `includes/Packages/Core_Read_Package.php` | 5,680 | Read definitions plus shared read helpers. |
| `includes/Packages/Read_Traits/Page_Pattern_Read_Methods.php` | 4,599 | Page pattern composition and review. |
| `includes/Packages/Read_Traits/Block_Theme_Read_Methods.php` | 3,196 | Block-theme inspection and planning. |

Line count is a triage signal, not an architectural goal. Split only when a
cohesive responsibility can move without changing its public contract.

## Accepted Sequence

1. **Post attribute writes — completed in the first slice.**
   `set-post-slug`, `set-post-author`, `set-post-template`, and
   `set-post-format` live in `Post_Attribute_Write_Methods`. The owning class
   still registers the definitions and supplies shared governance helpers.
2. **Site Editor and Gutenberg writes.** Extract post/template block mutation
   callbacks only after the first trait pattern has remained stable through a
   full source gate and real-host proof.
3. **Media write lifecycle.** Split remote intake, derivative materialization,
   file replacement/rollback, and reference repair into bounded traits. Keep
   each slice separate because these paths have different security and rollback
   risks.
4. **Definition providers.** Move large read/write definition arrays only after
   callback ownership is stable; definitions must continue to bind to the same
   object callbacks and metadata.
5. **Test suites.** Split `tests/run.php` by contract surface last, preserving a
   single default `composer test` entrypoint and aggregate assertion result.

Do not execute the remaining sequence automatically as one refactor. Open one
focused pull request per slice and re-evaluate the next boundary after merge.

## Gate Per Slice

Each extraction must pass:

```bash
composer test:all
composer analyse:phpstan
composer check:boundary
composer perf:bootstrap
git diff --check
```

Run the relevant real WordPress smoke when a moved callback depends on WordPress
runtime behavior. Block-theme or Site Editor slices must also run
`composer smoke:block-theme-host-proof`.

## Ownership Rule

Traits own cohesive method implementations only. `Core_Read_Package` and
`Core_Write_Package` remain the package composition roots and definition owners.
No trait may introduce workflow runtime, routing decisions, approval storage,
audit storage, queues, schedules, retries, leases, or final authorization.
