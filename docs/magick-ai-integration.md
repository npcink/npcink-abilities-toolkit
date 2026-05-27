# Magick AI Integration Contract

`magick-ai-abilities` is a standalone WordPress Abilities API plugin. The Magick AI plugin is an optional consumer, not the owner or runtime host of this project.

The same registered abilities must remain usable by other plugins and clients through the standard WordPress Abilities API.

## Allowed Integration

Magick AI may:

- detect public functions such as `magick_ai_abilities_register_readonly()`
- consume ability definitions through `magick_ai_abilities_get_registered()`
- consume projected entries through `magick_ai_open_platform_ability_catalog`
- execute WordPress abilities through the standard `wp_ability` backend path
- require this plugin for migrated generic WordPress read abilities in the current development profile

## Disallowed Integration

Magick AI must not:

- `require_once` files from this plugin's internal `includes/` directory
- depend on concrete classes under `Magick_AI_Abilities\Registry`
- move model routing, workflow runtime, skills runtime, Cloud runtime, billing, quota, or approval commit ownership into this plugin
- treat `meta.show_in_rest` as equivalent to `meta.mcp.public` or `meta.magick.channels`

## Runtime Boundary

This plugin owns registration and contract normalization.

For migrated official `magick-ai/*` read abilities, this plugin may also project catalog rows into Magick AI with `executor_type=wp_ability`. That projection is discovery metadata only; execution still goes through WordPress Abilities API and Magick AI's consuming runtime.

Magick AI owns only its consuming runtime:

- Open API Gateway
- app key authentication
- scope and quota enforcement
- audit trails
- proposal approval and final commit
- workflow and skill runtime
- Cloud execution enhancement

## Migration Rule

Move helpers first. Move abilities later. Move final write ownership never.

When a migrated read ability is owned by this plugin, Magick AI should not register a duplicate WordPress ability for the same id. During the current no-user development stage, Magick AI should delete fallback configs and callbacks for migrated read abilities instead of keeping hidden duplicate owners.

The cleanup posture is recorded in [ADR 0002](adr/0002-standalone-owner-and-magick-ai-cleanup.md).
