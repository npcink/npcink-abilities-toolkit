=== Magick AI Abilities ===
Contributors: muze233
Tags: abilities api, agents, ai, automation
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Standalone WordPress Abilities API package toolkit for registering agent-callable abilities.

== Description ==

Magick AI Abilities provides a standalone toolkit for plugin authors who want to expose safe, agent-callable abilities through the WordPress Abilities API.

The plugin owns ability registration helpers, category helpers, schema normalization, annotation normalization, and optional compatibility projection for Magick AI when Magick AI is installed.

It can be used by any WordPress plugin or client that consumes the WordPress Abilities API. Magick AI is one optional consumer, not the owner of this plugin.

It does not own model routing, cloud execution, billing, quota, workflow runtime, MCP governance, or final write approval/governance.

Read-only host composition recipe metadata may document how hosts can compose abilities, but those records do not run queues, schedule jobs, execute workflows, or create a second registry.

Host composition recipe metadata is available through read-only helpers and Abilities API discovery abilities for hosts that need catalog discovery without execution ownership.

== Requirements ==

* WordPress 7.0 or later. This release intentionally targets the WordPress Abilities API baseline available in WordPress 7.0+.
* PHP 8.0 or later.

== Public API ==

* `magick_ai_abilities_register_category( $category_id, $args )`
* `magick_ai_abilities_register_readonly( $ability_id, $definition )`
* `magick_ai_abilities_register_write_proposal( $ability_id, $definition )`
* `magick_ai_abilities_normalize_schema( $schema, $default_type )`
* `magick_ai_abilities_normalize_annotations( $annotations, $risk_level )`
* `magick_ai_abilities_get_registered()`
* `magick_ai_abilities_get_workflow_definitions()`
* `magick_ai_abilities_get_workflow_definition( $recipe_id )`

== Test Page ==

After activation with a Magick AI host plugin, open Magick AI -> Abilities in wp-admin. When this standalone package is installed without a Magick AI host menu, open Tools -> Abilities API Packages instead.

The page verifies Abilities API availability, fetches the authenticated REST ability and category catalogs, and can enable/run a demo readonly ability.

== Built-In Abilities ==

The plugin includes migrated low-risk WordPress read abilities, deterministic comment helpers, and host-governed WordPress write/destructive abilities using preserved `magick-ai/*` ids for compatibility.

It also includes `magick-ai-abilities/wp-diagnostics-summary`, a redacted WordPress-only diagnostics summary for Abilities API clients. This summary intentionally omits Magick AI settings, MCP settings, API keys, database names, table prefixes, filesystem paths, error logs, and external HTTP probes.

It also includes `magick-ai/search-posts` and `magick-ai/search-post-meta`, bounded local WordPress search helpers for keyword and explicit post-meta discovery. These are read-only helpers and do not call external search indexes or mutate content.

It also includes `magick-ai-abilities/list-workflow-recipes` and `magick-ai-abilities/get-workflow-recipe`, read-only host composition recipe metadata discovery abilities. These expose metadata only and do not execute workflow runtime behavior.

Core governance handoff docs include a catalog snapshot, permission matrix, and schema boundary audit for hosts that consume this plugin through `magick-ai-core`.

== Developer Smoke Test ==

When the plugin is installed in a local WordPress site, run:

`WP_PATH=/path/to/wordpress composer smoke:wp`

For isolated bounded-chain performance validation, run:

`composer perf:smoke`

== Changelog ==

= 0.4.0 =

* Added read-only host composition recipe metadata discovery abilities and public PHP helpers.
* Added Core governance handoff documentation, a catalog snapshot fixture, a permission matrix, and a schema boundary audit.
* Hardened write-like contracts with `requires_approval`, explicit dry-run and commit defaults, and bounded idempotency keys.
* Expanded smoke coverage for REST-exposed governance metadata and schemas.
* Added a consumer example for preparing Core proposal payloads from discovered ability contracts.

= 0.3.0 =

* Added package and sub-pack filters so hosts can keep a full catalog by default or opt into a light `core_wordpress_read` profile.
* Kept public third-party helpers read-only/write-proposal oriented and documented that final commit authorization belongs to the host runtime.
* Made Magick AI catalog projection thin by default and added a projection-row filter for host-owned policy expansion.
* Preserved migrated `magick-ai/*` ids as compatibility ids while documenting the future deprecation/successor migration rule.
* Added explicit read/comment sub-pack maps as the split point for future source-file extraction.
* Verified Magick AI catalog compatibility against the local development site.

= 0.2.0 =

* Stabilized the public helper contract and ability metadata rules.
* Documented host-governed write/destructive semantics and the Magick AI integration boundary.
* Added first-party ability pack grouping and 0.2 candidate verification evidence.
* Strengthened lightweight tests for schema controls, Magick catalog projection, provider defaults, and invalid ability ids.
* Verified Local WP smoke coverage and Magick AI consumer split-boundary checks.

= 0.1.0 =

* Initial standalone development release.
* Added migrated WordPress read abilities, deterministic comment helpers, host-governed write/destructive abilities, and standalone redacted WordPress diagnostics.
