# Npcink AI Integration Contract

`npcink-abilities-toolkit` is a standalone WordPress Abilities API plugin. The Npcink AI plugin is an optional consumer, not the owner or runtime host of this project.

The same registered abilities must remain usable by other plugins and clients through the standard WordPress Abilities API.

Minimum recommended `npcink-abilities-toolkit` version for this contract: `0.3.0`.
Npcink AI should update its own integration contract whenever it requires a
newer version.

## Allowed Integration

Npcink AI may:

- detect public functions such as `npcink_abilities_toolkit_register_readonly()`
- consume ability definitions through `npcink_abilities_toolkit_get_registered()`
- consume projected entries through `npcink_ai_open_platform_ability_catalog`
- discover abilities through WordPress Abilities API functions such as
  `wp_get_ability()` and REST `/wp-abilities/v1/*`
- execute WordPress abilities through the standard `wp_ability` backend path
- require this plugin for migrated generic WordPress read abilities in the current development profile

## Disallowed Integration

Npcink AI must not:

- `require_once` files from this plugin's internal `includes/` directory
- depend on concrete classes under `Npcink_Abilities_Toolkit\Registry`
- move model routing, workflow runtime, skills runtime, Cloud runtime, billing, quota, or approval commit ownership into this plugin
- treat `meta.show_in_rest` as equivalent to `meta.mcp.public` or `meta.npcink.channels`

## Runtime Boundary

This plugin owns registration and contract normalization.

For migrated official `npcink-abilities-toolkit/*` abilities, this plugin may also project
catalog rows into Npcink AI with `executor_type=wp_ability`. That projection is
discovery metadata only; execution still goes through WordPress Abilities API
and Npcink AI's consuming runtime.

Projected catalog rows are intentionally thin. They identify the WordPress
ability, carry schemas, annotations, risk level, confirmation requirement, and
optional lightweight Npcink AI catalog projection metadata. They do not publish Open API
policy, backend priority, catalog fallback behavior, MCP policy, quota, audit,
or tool-policy decisions. Those remain Npcink AI runtime responsibilities.
When Npcink AI consumes a thin row, it should derive runtime fields in its own
catalog normalization layer instead of requiring this package to emit them.

Npcink AI owns only its consuming runtime:

- Open API Gateway
- app key authentication
- scope and quota enforcement
- audit trails
- proposal approval and final commit
- workflow and skill runtime
- Cloud execution enhancement

## Discovery And Execution Flow

1. Npcink AI checks that this plugin is installed and the WordPress Abilities
   API is available.
2. Npcink AI discovers abilities through WordPress Abilities API discovery or
   the optional Npcink AI catalog projection.
3. Npcink AI records the WordPress ability id and treats `executor_type=wp_ability`
   as the execution backend.
4. Npcink AI applies caller identity, scopes, quota, audit, and approval policy
   in its own runtime.
5. Npcink AI executes the WordPress ability through the standard WordPress
   ability path.
6. For write or destructive abilities, Npcink AI supplies host approval context
   only after its own approval flow succeeds.

## Failure Modes

- Missing `npcink-abilities-toolkit`: Npcink AI should report the package as a
  missing dependency for migrated generic WordPress ability ids.
- Missing WordPress Abilities API: Npcink AI should disable `wp_ability`
  execution and report the WordPress version/API requirement.
- Duplicate ability id: Npcink AI should fail its duplicate-id audit and remove
  local duplicate configs/callbacks instead of shadowing this package.
- Missing host approval: write and destructive abilities must remain dry-run or
  fail closed; they must not silently commit.
- Missing projected catalog entry: Npcink AI may still discover the ability
  through WordPress Abilities API, but projected catalog consumers will not see
  it unless `project_to_npcink_catalog` is true.

## Migration Rule

Move helpers first. Move abilities later. Move final write ownership never.

When a migrated read ability is owned by this plugin, Npcink AI should not register a duplicate WordPress ability for the same id. During the current no-user development stage, Npcink AI should delete fallback configs and callbacks for migrated read abilities instead of keeping hidden duplicate owners.

Fallback definitions and callbacks for migrated ids must not be reintroduced
without a new ADR that explains the release profile, duplicate avoidance rule,
and removal criteria.

## Duplicate Id Audit

Cross-repo development should compare Npcink AI local ability ids with this
package's migrated ability inventory. The audit belongs to the Npcink AI
development workflow or an explicitly invoked local script. This package's CI
must not require the Npcink AI repository to be present.

If a local audit script is added later, it should accept an external manifest or
path as input, skip cleanly when the input is absent, and print the conflicting
ids with enough context for Npcink AI to remove the duplicate owner.

The cleanup posture is recorded in [ADR 0002](adr/0002-standalone-owner-and-magick-ai-cleanup.md).

The independent-project split rules are recorded in [npcink-ai-project-split-contract.md](npcink-ai-project-split-contract.md). That contract is the current source of truth for cross-project development, host-governed write/destructive behavior, duplicate registration audits, and version compatibility once Npcink AI and this plugin are developed as separate projects.

## Minimum Version Matrix

| Consumer need | Minimum `npcink-abilities-toolkit` version |
| --- | --- |
| Public registration helpers, migrated baseline read/write/destructive abilities, and optional Npcink AI projection | `0.2.0` |
| Host-selectable package/sub-pack gating, thin Npcink projection defaults, projection-row filter, and explicit read/comment sub-pack maps | `0.3.0` |
