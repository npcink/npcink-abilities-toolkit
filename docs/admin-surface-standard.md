# Abilities Admin Surface Standard

Status: active for `Magick AI -> Abilities` and the standalone
`Tools -> Abilities API Packages` fallback.

## Purpose

The Abilities admin page is an ability-package status and smoke-test surface.
It exists to confirm that WordPress Abilities API registration, schemas,
callbacks, categories, and demo execution are available.

## Default View

The default page should show:

- compact environment status for WordPress Abilities API support;
- registered ability count and demo ability state;
- an ability catalog table grouped or scannable by `ability_id`;
- per-ability signals for category, risk, callback availability, and schema
  availability.

## Advanced / Test Details

Low-frequency details should be behind explicit advanced entries:

- raw REST endpoint URLs;
- browser REST fetch buttons;
- demo ability enable/disable control;
- raw registered ability id dump;
- compatibility projection notes for Magick AI consumers.

## Do Not Add

Abilities admin must not add:

- Core proposal approval, preflight, or audit workflows;
- OpenClaw handoff or Application Password generation;
- Cloud API key, runtime, billing, or entitlement controls;
- model routing, prompt/preset settings, queues, workflow runtime, MCP
  governance, or final write approval truth.

## Verification

Static contracts should assert that the page remains a package/status surface,
keeps the standalone Tools fallback, and does not become a Magick AI runtime or
governance console.
