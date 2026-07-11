# WordPress.org Listing Draft - English

## Plugin Name

Npcink Abilities Toolkit

## Short Description

Expose and inspect WordPress Abilities API capabilities for AI hosts and clients.

## Tags

abilities api, agents, ai, automation, developer tools

## Description

Npcink Abilities Toolkit helps a WordPress site expose, review, and safely
inspect Abilities API capabilities for AI hosts and clients.

The admin page is built for site operators first: it shows site ability status,
available abilities, read-only versus host-approved risk posture, two safe
read-only checks, and developer connection values when a host or client needs
to connect.

For developers and host runtimes, the plugin includes ability registration
helpers, category helpers, schema normalization, annotation normalization,
built-in WordPress read abilities, proposal-oriented write abilities,
diagnostics abilities, and optional catalog projection for Npcink AI hosts.

This plugin is independently published by Npcink and remains useful on its own.
It can be consumed directly by clients that use the WordPress Abilities API or
by host plugins that need a stable ability catalog, including optional Npcink AI
hosts.

Npcink Abilities Toolkit complements official WordPress AI, MCP, and Abilities API
ecosystem plugins. It is not a model client, MCP transport, cloud runtime,
workflow engine, billing system, quota system, or final approval layer for
WordPress writes.

## FAQ Draft

### Does this plugin run AI models?

No. It exposes WordPress abilities and support information through the WordPress
Abilities API. Model routing, prompt selection, hosted runtime execution, and
workflow execution belong to a separate host product or client.

### Will it change site content by itself?

No. The admin checks are read-only. Write-like abilities require a host runtime
with its own approval, authorization, and audit layer before any final commit.

### Do I need Npcink AI?

No. Npcink AI is an optional consumer. Other plugins and clients can use the
same WordPress Abilities API contracts.

### What do the safe checks prove?

Site Info proves that an authorized ability client can read basic WordPress
site information. Redacted Diagnostics proves that the site can return a
support-friendly environment summary with sensitive fields omitted.

### Do diagnostics expose secrets?

No. The summary intentionally omits Npcink AI settings, MCP settings, API keys,
database names, table prefixes, filesystem paths, error logs, and external HTTP
probes.

## Screenshot Captions

1. Site ability status overview with available ability count, write safeguards,
   host detection, and next actions.
2. Available Abilities catalog with filters, risk grouping, availability, and
   technical details for support.
3. Safe Checks tab explaining what each check proves before showing summary
   results and raw response support details.
4. Developer Access tab with copyable REST endpoint values, raw discovery
   fetches, and ability ID export.

## Key Features

- Register reusable read-only and proposal-oriented WordPress abilities.
- Normalize ability schemas and annotations for safer agent consumption.
- Provide built-in WordPress read, diagnostics, host composition recipe
  discovery, and comment helper abilities.
- Use canonical `npcink-abilities-toolkit/*` ability ids for abilities owned by this plugin.
- Expose contracts that host plugins can govern through their own approval,
  preflight, and audit layers.
- Keep Npcink AI integration optional instead of making Npcink AI the owner of
  the Abilities API layer.

## Who This Is For

- WordPress plugin authors building Abilities API providers.
- Host plugins that need a stable ability catalog.
- Agent clients that consume WordPress Abilities API contracts.
- Developers separating ability definition from governance, transport, model
  routing, and cloud execution.

## Requirements

- WordPress 6.9 or later. WordPress 6.9 introduced the Abilities API used by
  this plugin.
- PHP 8.0 or later.

## Integration Boundary

In a Npcink AI host setup:

- Npcink Abilities Toolkit owns ability definitions and callbacks.
- Npcink AI Core owns governance, approval, preflight, and audit.
- Npcink AI Adapter owns OpenClaw channel adaptation.
- Npcink AI Cloud Addon owns cloud service connection.

This separation keeps the ability layer reusable, inspectable, and compatible
with the broader WordPress ecosystem.
