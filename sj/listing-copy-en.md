# WordPress.org Listing Draft - English

## Plugin Name

Magick AI Abilities

## Short Description

Ability packages and callback contracts for the WordPress Abilities API.

## Tags

abilities api, agents, ai, automation, developer tools

## Description

Magick AI Abilities provides reusable ability packages and callback contracts for
the WordPress Abilities API.

It helps plugin authors and host runtimes expose safe, agent-callable WordPress
capabilities through a stable contract layer. The plugin includes ability
registration helpers, category helpers, schema normalization, annotation
normalization, built-in WordPress read abilities, proposal-oriented write
abilities, diagnostics abilities, and optional compatibility projection for
Magick AI hosts.

This plugin is part of the Magick AI plugin family, but it remains useful on its
own. It can be consumed directly by clients that use the WordPress Abilities API
or by host plugins that need a stable ability catalog.

Magick AI Abilities complements official WordPress AI, MCP, and Abilities API
ecosystem plugins. It is not a model client, MCP transport, cloud runtime,
workflow engine, billing system, quota system, or final approval layer for
WordPress writes.

## Key Features

- Register reusable read-only and proposal-oriented WordPress abilities.
- Normalize ability schemas and annotations for safer agent consumption.
- Provide built-in WordPress read, diagnostics, workflow discovery, and comment
  helper abilities.
- Preserve compatibility-oriented `magick-ai/*` ability ids where needed.
- Expose contracts that host plugins can govern through their own approval,
  preflight, and audit layers.
- Keep Magick AI integration optional instead of making Magick AI the owner of
  the Abilities API layer.

## Who This Is For

- WordPress plugin authors building Abilities API providers.
- Host plugins that need a stable ability catalog.
- Agent clients that consume WordPress Abilities API contracts.
- Developers separating ability definition from governance, transport, model
  routing, and cloud execution.

## Requirements

- WordPress 7.0 or later with the Abilities API available.
- PHP 8.0 or later.

## Series Boundary

In the Magick AI plugin family:

- Magick AI Abilities owns ability definitions and callbacks.
- Magick AI Core owns governance, approval, preflight, and audit.
- Magick AI Adapter owns OpenClaw channel adaptation.
- Magick AI Cloud Addon owns cloud service connection.

This separation keeps the ability layer reusable, inspectable, and compatible
with the broader WordPress ecosystem.
