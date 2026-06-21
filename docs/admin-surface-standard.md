# Abilities Admin Surface Standard

Status: active for `Npcink AI -> Ability Diagnostics` and the standalone
`Tools -> Abilities Toolkit Diagnostics` fallback.

## Purpose

The Abilities admin page is an ability-package status and REST-check surface.
It exists to confirm that WordPress Abilities API registration, schemas,
callbacks, categories, and authenticated REST discovery are available.

## Default View

The default page should show:

- compact environment status for WordPress Abilities API support;
- registered ability count and callback readiness;
- stable, shareable admin URLs for tabs and read-only filters;
- an ability catalog table grouped or scannable by `ability_id`, label,
  description, and category;
- per-ability signals for category, risk, callback availability, and schema
  availability.

## Connection Values

Connection and low-frequency details should be split into explicit sections:

- REST endpoint URLs should be visible on the Connections screen with copy actions;
- the Toolkit contract endpoint should be visible as a copyable host/runtime
  discovery value;
- browser REST fetch buttons should be grouped as discovery fetches;
- authenticated REST discovery checks should be distinct from endpoint copy values;
- at most two official read-only ability checks may be visible: site info and bounded redacted diagnostics summary;
- copyable registered ability ID export;
- compatibility projection notes for Npcink AI consumers.

## Ability Diagnostics

The page should avoid looking like a general settings page. Plugin-list action
links, page headings, and menu labels should use diagnostics language rather
than Settings language unless a future page actually stores configuration.

Do not add demo/showcase execution buttons, model-call buttons, write buttons,
or workflow-run buttons to this package surface. Real workflow execution belongs
to a host product such as Npcink AI/Core, not to the capability package admin
page.

## Time Display

Ability callbacks may return UTC, ISO, or explicitly named `*_gmt` fields as
machine-readable contract values. Keep those output field names and semantics stable.

If the Abilities admin page ever shows a timestamp to a human operator, format
the visible value through the WordPress site timezone as `Y-m-d H:i:s`. Do not
print raw UTC strings, ISO timestamps, or `*_gmt` ability values directly in
the human-facing admin UI unless the label explicitly describes a
machine/debug value.

## Do Not Add

Abilities admin must not add:

- Core proposal approval, preflight, or audit workflows;
- OpenClaw handoff or Application Password generation;
- Cloud API key, runtime, billing, or entitlement controls;
- model routing, prompt/preset settings, queues, workflow runtime, MCP
  governance, or final write approval truth.

## Verification

Static contracts should assert that the page remains a package/status surface,
keeps the standalone Tools fallback, and does not become a Npcink AI runtime or
governance console.
