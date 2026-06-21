=== Npcink Abilities Toolkit ===
Contributors: muze233
Tags: abilities api, agents, ai, automation
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Standalone WordPress Abilities API package toolkit for registering agent-callable abilities.

== Description ==

Npcink Abilities Toolkit provides a standalone toolkit for plugin authors who want to expose safe, agent-callable abilities through the WordPress Abilities API.

The plugin owns ability registration helpers, category helpers, schema normalization, annotation normalization, and optional canonical projection for Npcink AI when Npcink AI is installed.

It can be used by any WordPress plugin or client that consumes the WordPress Abilities API. Npcink AI is one optional consumer, not the owner of this plugin.

It does not own model routing, cloud execution, billing, quota, workflow runtime, MCP governance, or final write approval/governance.

Read-only host composition recipe metadata may document how hosts can compose abilities, but those records do not run queues, schedule jobs, execute workflows, or create a second registry.

Host composition recipe metadata is available through read-only helpers and Abilities API discovery abilities for hosts that need catalog discovery without execution ownership.

== External Services and Remote Requests ==

This plugin does not automatically contact Npcink AI, model providers, analytics services, tracking services, or cloud services.

Some abilities can prepare request payloads for a separate host or cloud add-on, but this plugin does not send those payloads to an external service by itself.

The `npcink-abilities-toolkit/upload-media-from-url` ability can download a media file from a URL provided by an authenticated caller when an approved host runtime commits that ability. In that case WordPress sends an HTTP request to the caller-provided URL in order to fetch the media file for the local media library. The remote endpoint is chosen by the caller, not by this plugin.

== Requirements ==

* WordPress 7.0 or later. This release intentionally targets the WordPress Abilities API baseline available in WordPress 7.0+.
* PHP 8.0 or later.
* The WordPress Abilities API REST routes must be available before third-party
  provider plugins or external clients can discover and run abilities.

== Public API ==

* `npcink_abilities_toolkit_register_category( $category_id, $args )`
* `npcink_abilities_toolkit_register_readonly( $ability_id, $definition )`
* `npcink_abilities_toolkit_register_write_proposal( $ability_id, $definition )`
* `npcink_abilities_toolkit_normalize_schema( $schema, $default_type )`
* `npcink_abilities_toolkit_normalize_annotations( $annotations, $risk_level )`
* `npcink_abilities_toolkit_get_registered()`
* `npcink_abilities_toolkit_get_workflow_definitions()`
* `npcink_abilities_toolkit_get_workflow_definition( $recipe_id )`

== Third-Party Integration Quickstart ==

Provider plugins should wait until `plugins_loaded`, check that the public
helper exists, and then register their abilities through the helper functions:

`if ( function_exists( 'npcink_abilities_toolkit_register_readonly' ) ) { npcink_abilities_toolkit_register_readonly( 'acme/site-summary', $definition ); }`

Do not include files from this plugin's `includes/` directory or instantiate
classes in the `Npcink_Abilities_Toolkit` namespace. Those are implementation
details.

Use `npcink_abilities_toolkit_register_readonly()` for read-only context and
diagnostic abilities. Use `npcink_abilities_toolkit_register_write_proposal()`
only when the callback returns a proposal, preview, diff, or handoff payload.
Third-party provider callbacks should not perform final host-governed commits.
Approval, audit, quota, and final write authorization belong to the consuming
host runtime.

REST clients should first discover the catalog through:

* `/wp-json/wp-abilities/v1/categories`
* `/wp-json/wp-abilities/v1/abilities`
* `/wp-json/wp-abilities/v1/abilities/{namespace}/{name}/run`
* `/wp-json/npcink-abilities-toolkit/v1/contract`

The contract endpoint is a compatibility and boundary discovery endpoint for
authenticated host runtimes. It does not replace the WordPress Abilities API
catalog and does not run abilities.

Full provider examples and REST client notes are maintained in the public
repository:

`https://github.com/muze-page/npcink-abilities-toolkit`

If the `wp-abilities/v1` REST routes are missing, enable the WordPress
Abilities API baseline or compatibility plugin before connecting third-party
providers or clients.

== Admin Page ==

After activation with a Npcink AI host plugin, open Npcink AI -> Ability Diagnostics in wp-admin. When this standalone package is installed without a Npcink AI host menu, open Tools -> Abilities Toolkit Diagnostics instead.

The page verifies Abilities API availability, shows package health, filters the registered ability catalog with labels and descriptions, provides copyable REST endpoint values for host/client setup, and can run two official read-only checks: site info and bounded redacted diagnostics summary. It is a connection and discovery surface; it does not run showcase workflows, model calls, write abilities, or demo abilities.

== Built-In Abilities ==

The plugin includes migrated low-risk WordPress read abilities, deterministic comment helpers, and host-governed WordPress write/destructive abilities using canonical `npcink-abilities-toolkit/*` ids.

It also includes `npcink-abilities-toolkit/wp-diagnostics-summary`, a redacted WordPress-only diagnostics summary for Abilities API clients. This summary intentionally omits Npcink AI settings, MCP settings, API keys, database names, table prefixes, filesystem paths, error logs, and external HTTP probes.

It also includes `npcink-abilities-toolkit/search-posts` and `npcink-abilities-toolkit/search-post-meta`, bounded local WordPress search helpers for keyword and explicit post-meta discovery. These are read-only helpers and do not call external search indexes or mutate content.

It also includes `npcink-abilities-toolkit/list-workflow-recipes` and `npcink-abilities-toolkit/get-workflow-recipe`, read-only host composition recipe metadata discovery abilities. These expose metadata only and do not execute workflow runtime behavior.

Core governance handoff docs include a catalog snapshot, permission matrix, and schema boundary audit for hosts that consume this plugin through `npcink-ai-core`.

== Developer Verification ==

For the default local source gate, run:

`composer test:all`

When the plugin is installed in a local WordPress site, run:

`WP_PATH=/path/to/wordpress composer smoke:wp`

For isolated bounded-chain performance validation, run:

`composer perf:smoke`

== Changelog ==

= 0.5.2 =

* Added read-only review artifacts for image candidates, internal link candidates, taxonomy suggestions, and comment reply suggestions.
* Kept candidate review helpers suggestion-only and host-governed; they do not create proposals, approve work, execute writes, or contact external providers.
* Expanded acceptance contracts and first-party pack documentation for the new handoff artifacts.

= 0.5.1 =

* Added Composer dependency advisory audit to the default local and CI source gate.
* Aligned CI and PHPStan with the package PHP 8.0 runtime floor.
* Published the canonical public GitHub repository and documented the post-publication gate baseline.
* Upgraded GitHub Actions checkout to the Node 24-compatible `actions/checkout@v5`.

= 0.5.0 =

* Improved the admin page as a connection and discovery surface with clearer package status, catalog navigation, copyable REST endpoints, and two bounded read-only checks.
* Added bundled translation templates and eight starter locale packs for the admin connection/discovery surface, API ability labels/descriptions, and common runtime error messages.
* Removed development demo ability controls from the admin page and kept showcase, model-call, write, and workflow execution outside this package surface.
* Renamed nonproduction cleanup abilities and media cleanup inputs to avoid public `test` terminology in released ability ids and schema fields.
* Added taxonomy assignment proposal support and strengthened Core consumer handoff checks for harvested workflow surfaces.
* Hardened media replacement and Cloud derivative adoption previews so approved replacements can repair exact post-content media URLs, including old intermediate-size URLs.
* Documented the foundation-layer testing strategy and default local source gate.

= 0.4.0 =

* Added read-only host composition recipe metadata discovery abilities and public PHP helpers.
* Added Core governance handoff documentation, a catalog snapshot fixture, a permission matrix, and a schema boundary audit.
* Hardened write-like contracts with `requires_approval`, explicit dry-run and commit defaults, and bounded idempotency keys.
* Expanded REST verification coverage for governance metadata and schemas.
* Added a consumer example for preparing Core proposal payloads from discovered ability contracts.

= 0.3.0 =

* Added package and sub-pack filters so hosts can keep a full catalog by default or opt into a light `core_wordpress_read` profile.
* Kept public third-party helpers read-only/write-proposal oriented and documented that final commit authorization belongs to the host runtime.
* Made Npcink AI catalog projection thin by default and added a projection-row filter for host-owned policy expansion.
* Established `npcink-abilities-toolkit/*` as the canonical id namespace for abilities owned by this plugin.
* Added explicit read/comment sub-pack maps as the split point for future source-file extraction.
* Verified Npcink AI catalog compatibility against a WordPress site.

= 0.2.0 =

* Stabilized the public helper contract and ability metadata rules.
* Documented host-governed write/destructive semantics and the Npcink AI integration boundary.
* Added first-party ability pack grouping and 0.2 candidate verification evidence.
* Strengthened lightweight tests for schema controls, Npcink catalog projection, provider defaults, and invalid ability ids.
* Verified WordPress REST coverage and Npcink AI consumer split-boundary checks.

= 0.1.0 =

* Initial standalone release.
* Added migrated WordPress read abilities, deterministic comment helpers, host-governed write/destructive abilities, and standalone redacted WordPress diagnostics.
