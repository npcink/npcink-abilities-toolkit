# ADR 0001: Migrate Abilities API Packages From Magick AI

Status: accepted
Date: 2026-05-27

## Context

The Magick AI plugin contains a large `includes/abilities` surface with mixed responsibilities:

- WordPress Abilities API category and ability registration.
- Read-only WordPress site/content ability definitions.
- Write-like ability schemas and callbacks.
- Magick AI runtime bridges, Agent Gateway exposure, scopes, quota, audit, observability, model routing, workflow runtime, and final write governance.

The standalone `magick-ai-abilities` plugin should become the Abilities API capability-package plugin. The Magick AI plugin should become a consumer and operations/governance surface, reducing day-to-day ability-definition development pressure in the main plugin.

## Decision

Migrate Abilities API package ownership into `magick-ai-abilities`, but do not move Magick AI control-plane ownership.

`magick-ai-abilities` owns:

- Abilities API categories.
- Ability definitions and schemas.
- Read-only WordPress ability callbacks that depend only on WordPress core APIs.
- Write-proposal ability contracts that do not commit final writes.
- Public provider-plugin helpers.
- Optional Magick AI compatibility projection, disabled by default.

The Magick AI plugin owns:

- App key authentication.
- Agent Gateway and Open API routes.
- Scope, quota, audit, telemetry, and observability enforcement.
- Model routing and workflow/skill runtime.
- Two-phase confirmation and final WordPress writes.
- Operations/admin management surfaces.

## First Migration Batch

The first low-risk batch moves these WordPress read-only abilities into `magick-ai-abilities` while preserving legacy ability ids for compatibility:

- `magick-ai/site-info`
- `magick-ai/list-post-types`
- `magick-ai/list-taxonomies`
- `magick-ai/count-posts`

These abilities live in `Magick_AI_Abilities\Packages\Core_Read_Package`.

## Compatibility Rules

- Existing `magick-ai/*` ids are preserved until consumers have a documented alias migration path.
- `project_to_magick_catalog` remains false by default for third-party provider abilities.
- Migrated official `magick-ai/*` read abilities may explicitly project into the Magick AI catalog through the compatibility bridge after Magick AI deletes its fallback definitions.
- Built-in package abilities should not become Agent Gateway, quota, audit, or model runtime owners.
- The Magick AI plugin may retain fallback definitions during the transition for sites that have not installed this plugin.
- Duplicate WordPress ability registration must be avoided through `wp_has_ability()` checks.

## Consequences

This reduces new ability development pressure in the main Magick AI plugin without forcing an immediate destructive removal. Later phases can remove fallback definitions from Magick AI after real-site smoke tests confirm the standalone package is required and active.
