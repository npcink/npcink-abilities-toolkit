# Npcink Abilities Toolkit

Standalone WordPress Abilities API plugin for packaging and registering agent-callable abilities.

## What You Get

Install this plugin and your AI assistant gets a safe toolbox for WordPress: it can read articles, media, comments, menus, and site diagnostics, suggest tags and excerpts, and follow documented article, media, and comment workflow recipes. Every write-like ability defaults to a dry-run preview, and a real commit only happens after your host product approves it — the approval decision and the audit trail stay with the host, never with this package. It works with any WordPress Abilities API client; Npcink AI is just one optional consumer.

- Site owners: review everything on the **AI Ability Set** admin page — no AI model runs here, and nothing is written without host approval.
- Plugin authors: expose your own abilities through [docs/third-party-plugin-guide.md](docs/third-party-plugin-guide.md).
- Host products: govern commits through the [Host Approval Contract](docs/host-approval-contract.md) and pick a deployment shape in [docs/host-profiles.md](docs/host-profiles.md) and [docs/permission-matrix.md](docs/permission-matrix.md).
- REST and external clients: start with [docs/rest-client-quickstart.md](docs/rest-client-quickstart.md).
- Common setup, authentication, permission, and dry-run failures are covered in [docs/troubleshooting.md](docs/troubleshooting.md).

## Requirements

- WordPress 6.9+ with the Abilities API available
- PHP 8.0+

## Minimal Example

```php
add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'npcink_abilities_toolkit_register_readonly' ) ) {
			return;
		}

		npcink_abilities_toolkit_register_readonly(
			'acme/site-summary',
			array(
				'label'            => 'Site Summary',
				'description'      => 'Returns basic site information.',
				'capability'       => 'manage_options',
				'input_schema'     => array( 'type' => 'object' ),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array( 'type' => 'string' ),
						'url'  => array( 'type' => 'string' ),
					),
				),
				'execute_callback' => static function () {
					return array(
						'name' => get_bloginfo( 'name' ),
						'url'  => home_url(),
					);
				},
			)
		);
	}
);
```

When Npcink AI is installed, provider plugins may opt into canonical projection so their registered abilities appear in `npcink_ai_open_platform_ability_catalog` as `wp_ability` backend entries. Other plugins can ignore that integration and consume the same abilities through the standard WordPress Abilities API.

See [examples/core-governance-consumer.php](examples/core-governance-consumer.php)
for a minimal consumer-side example that discovers a real ability id, reads
schema/risk metadata, and prepares a `npcink-ai-core` proposal payload without
this package owning Core governance.

The consumer handoff fixture and release-candidate governance checks are
available through:

```bash
composer check:consumer
composer check:workflow-consumer
composer check:official-stack
composer check:mcp-exposure
composer check:provider-demo
composer check:catalog
```

Use `scripts/audit-ability-catalog.php` with one or more catalog JSON files to
detect duplicate ability ids and governance metadata drift before merging
cross-repo Core integration changes.

## Admin Page

After activating the plugin with a Npcink AI host plugin, open
**Npcink AI -> AI Ability Set** in wp-admin. When this standalone package
is installed without a Npcink AI host menu, open **Tools -> AI Ability
Set** instead.

The default page is intended for site operators. It shows site ability status
and read-only status indicators:

- WordPress Abilities API support
- available ability count
- whether write-like abilities require host approval
- whether a Npcink AI host menu is detected
- direct links to available abilities, safe checks, and developer connection information

The Ability Catalog and Checks views under Developer Tools can:

- filter abilities by name, description, category, risk, technical ID, and page size
- show user-facing ability labels, descriptions, risk posture, availability, and technical details
- run `npcink-abilities-toolkit/site-info` and a bounded `npcink-abilities-toolkit/wp-diagnostics-summary` as read-only checks
- explain what each check proves and what it does not prove before it runs
- show check results as a plain summary table, with raw JSON kept behind a support disclosure

The Connection view under Developer Tools can:

- copy endpoint values for external clients and host products
- fetch `/wp-json/wp-abilities/v1/abilities` with the current logged-in user's REST nonce
- fetch `/wp-json/wp-abilities/v1/categories`
- copy ability IDs for host/catalog audits

This page is an ability status, review, check, and connection surface. It does
not run showcase workflows, model calls, write abilities, approval flows, or
demo abilities. It never triggers host workflows or approvals.
It must never display, store, or act on approval, quota, audit, or workflow
state owned by any host runtime.

## Built-In WordPress Read, Handoff, and Comment Packages

The migrated core read, suggestion, handoff, and deterministic comment packages
provide these WordPress abilities. Abilities whose names include request,
apply-plan, cloud, decision, or trigger-queue wording return bounded context,
request payloads, plans, or host-review artifacts only unless the documented
host-governed write contract requires a separate approval envelope.

The canonical id list lives in
[docs/first-party-ability-packs.md](docs/first-party-ability-packs.md); the
Ability Catalog in wp-admin shows the same ids on a live site. Coverage by
group:

- Site and content reads: site info, post types, taxonomies, posts, pages,
  page trees, revisions, blocks, comments, users, terms, categories, tags,
  menus, metadata, and bounded search helpers.
- Media helpers: media listing, URL resolution, asset inspection,
  optimization plans, alt/caption review sets, derivative requests, and
  rename or adoption plans.
- Content production: article optimization reports, style extraction and
  baselines, production fingerprints, duplicate checks, production and draft
  results, and publication decisions.
- Handoff and comment helpers: moderation suggestions and batch results,
  mention replies, compliance handoffs, and bounded trigger-queue context.

When this summary drifts from the canonical list, fix this summary instead of
re-inlining ids here.

`resolve-internal-link-targets` returns both generic internal-link target rows
and an `internal_link_candidates.v1` artifact for editor or third-party review
surfaces. Hosts may pass already gathered related-content evidence for ranking
context, but provider search, vector stores, and Site Knowledge runtimes remain
host-owned.

`read-comment-trigger-queue` is a frozen compatibility ability id for bounded
comment trigger context. It only reads local context for host review; it does
not create, persist, lease, retry, schedule, drain, or own queues.

`upload-media-from-url` is write-like and external-request capable, but it
defaults to a dry-run preview. A real upload requires a separate host approval
envelope; the caller chooses the remote URL, and this package stores no provider
credentials, remote-service configuration, model routing, or cloud execution
truth for that operation.

The `npcink-abilities-toolkit/*` ids are canonical under the Npcink Abilities Toolkit namespace. Built-in migrated ids may explicitly project into Npcink AI as thin `wp_ability` canonical rows; third-party provider abilities still do not project into Npcink AI by default.

## Built-In WordPress Diagnostics

The standalone diagnostics package provides:

- `npcink-abilities-toolkit/wp-diagnostics-summary`
- `npcink-abilities-toolkit/wp-ops-diagnostics-detail`

The summary ability returns a redacted WordPress-only environment summary for agents and other plugins. It reports site hosts, WordPress/PHP runtime details including extension availability, active theme summary, active plugin details, current caller roles/capabilities, object cache status, rewrite/permalink status, HTTPS status, REST/Abilities API availability, cron counts, and update counts.

The ops detail ability returns bounded follow-up diagnostics for support flows: plugin lists with slug/name/version/author/update status/requirements/dependencies/Npcink AI hint, current caller identity plus local Npcink AI permission inferences, PHP extensions, object cache and page-cache drop-in status, rewrite/permalink and HTTPS status, server summary, database version/count/size estimates, cron hook names and next-run times, error log availability plus severity counts, optional redacted and structured log contents when `include_log_contents` is true, custom post type summaries, role capability lists, widget/sidebar summaries, block-theme registry counts, search and integration hints, SEO meta-key/sitemap/robots hints, and SEO/security/performance summaries. Plugin row groups are bounded with `max_plugins_per_group`; inactive plugins are omitted by default and can be requested with `include_inactive_plugins`. Both diagnostics abilities intentionally omit Npcink AI settings, MCP settings, API keys, database names, table prefixes, table names, filesystem paths, unredacted error log contents, cron argument values, and external HTTP probes.

Related read abilities also expose operational context needed by support clients: `npcink-abilities-toolkit/search-posts` supports bounded local keyword search with post type, status, author, date/modified, and taxonomy filters, `npcink-abilities-toolkit/search-post-meta` searches explicitly named non-sensitive post meta keys, `npcink-abilities-toolkit/list-posts` supports author/date/modified/taxonomy/order filters, `npcink-abilities-toolkit/get-post-context` includes template/status/SEO/media/block details, term lists can include bounded `sample_posts`, users include an `author_profile`, comments include their post context, media rows include attachment usage context, and `npcink-abilities-toolkit/get-menu` returns both flat items and a nested tree.

## Built-In Workflow Definition Discovery

The standalone workflow definition package provides:

- `npcink-abilities-toolkit/list-workflow-recipes`
- `npcink-abilities-toolkit/get-workflow-recipe`

These abilities return read-only recipe definitions for host-side ability composition. They do not execute workflow steps, schedule work, approve writes, route models, select prompts, audit runs, or commit final WordPress writes.

## Public API

```php
npcink_abilities_toolkit_register_category( $category_id, $args );
npcink_abilities_toolkit_register_readonly( $ability_id, $definition );
npcink_abilities_toolkit_register_write_proposal( $ability_id, $definition );
npcink_abilities_toolkit_normalize_schema( $schema, $default_type );
npcink_abilities_toolkit_normalize_annotations( $annotations, $risk_level );
npcink_abilities_toolkit_get_registered();
npcink_abilities_toolkit_get_workflow_definitions();
npcink_abilities_toolkit_get_workflow_definition( $recipe_id );
```

`npcink_abilities_toolkit_get_registered()` is a package inspection helper for
registered Toolkit abilities. It is not an authoritative replacement for
WordPress Abilities API discovery or execution, and it does not make this
package a second ability registry.

The workflow definition helpers return read-only recipe metadata for host-side
composition. They are not a workflow registry, execution engine, scheduler,
approval store, audit store, model router, prompt registry, or final write
authority.

Runtime contract discovery is available through:

```text
GET /wp-json/npcink-abilities-toolkit/v1/contract
```

The contract endpoint requires a WordPress REST caller with `manage_options`
and returns non-secret metadata for host runtimes, including the active plugin
version, contract versions, registered ability count, stable catalog hashes,
workflow definition hash, and write-boundary posture. It is a discovery
endpoint only; clients should still use the WordPress Abilities API catalog for
ability definitions and execution. It also reports Adapter-facing
compatibility, catalog/schema ownership, callback-free hash posture, and the
host-governed write boundary. It never returns callbacks, approval records,
audit truth, runtime state, prompt material, model routing, provider secrets,
or cloud execution truth.
The contract endpoint is not authoritative for admission, approval, audit,
routing, catalog policy, or execution; hosts must enforce their own governance,
and the WordPress Abilities API remains the ability discovery and execution
surface.
Normalized write-like ability contracts also expose `implementation_posture`
metadata so governance consumers can verify dry-run-first, host-governed posture
without treating Toolkit as an approval store, audit store, runtime, or final
write authority.
Hosts govern commits through the documented, host-agnostic
[Host Approval Contract](docs/host-approval-contract.md); Npcink AI is only the
first implementer.

## Documentation Map

Use [docs/README.md](docs/README.md) as the documentation entry point for
external integration, debugging, and maintainer references.

User-facing contracts and guides:

- Public API freeze: [docs/public-api-freeze-0.1.md](docs/public-api-freeze-0.1.md)
- Migration boundary from the Npcink AI plugin: [docs/adr/0001-migrate-abilities-from-magick-ai.md](docs/adr/0001-migrate-abilities-from-magick-ai.md)
- Independent-project split and Npcink AI integration boundary (canonical ownership source): [docs/npcink-ai-project-split-contract.md](docs/npcink-ai-project-split-contract.md)
- Built-in ability grouping by product purpose: [docs/first-party-ability-packs.md](docs/first-party-ability-packs.md)
- Host-side workflow compositions as reference recipes: [docs/workflow-recipes.md](docs/workflow-recipes.md)
- Machine-readable workflow definition field rules: [docs/workflow-definition-contract.md](docs/workflow-definition-contract.md)
- Article workflow ability map: [docs/article-workflow-abilities-v1.md](docs/article-workflow-abilities-v1.md)
- Static agent and MCP usage guidance: [docs/agent-usage-metadata.md](docs/agent-usage-metadata.md)
- Core governance handoff rules: [docs/core-governance-handoff-guide.md](docs/core-governance-handoff-guide.md)
- Core handoff catalog snapshot, permission matrix, and schema boundary audit: [docs/core-governance-catalog-snapshot.md](docs/core-governance-catalog-snapshot.md), [docs/permission-matrix.md](docs/permission-matrix.md), [docs/schema-boundary-audit.md](docs/schema-boundary-audit.md)
- Full and light host profiles: [docs/host-profiles.md](docs/host-profiles.md)
- Performance and caching rules: [docs/performance-and-caching.md](docs/performance-and-caching.md)
- Security and governance gates: [docs/security-and-governance-gates.md](docs/security-and-governance-gates.md)
- Official WordPress AI stack compatibility: [docs/official-wordpress-ai-stack-compatibility.md](docs/official-wordpress-ai-stack-compatibility.md)
- 2026-07-08 Core/Adapter/Product reuse readiness observation: [docs/ability-contract-reuse-readiness-2026-07-08.md](docs/ability-contract-reuse-readiness-2026-07-08.md)
- Release notes: [CHANGELOG.md](CHANGELOG.md); WordPress plugin directory metadata: [readme.txt](readme.txt)

Bundled starter translations live in [languages](languages) and cover the admin connection/discovery surface, API ability labels/descriptions, and common runtime error messages for Simplified Chinese, Japanese, Korean, French, German, Spanish, and Brazilian Portuguese. The package only ships locale files that are intentionally maintained in this repository; incomplete bundled locale packs are removed until they can be maintained as a complete starter set. WordPress.org directory translations remain managed through translate.wordpress.org/GlotPress and are not a runtime authority owned by this plugin.

Release and maintainer records (0.3/0.5 stabilization, readiness plans, operating standards, phase closeouts, admin surface standard) are indexed in the maintainer section of [docs/README.md](docs/README.md).

## Boundaries And Ownership

This project is an independent Abilities API capability-package plugin. It can be used by any WordPress plugin that wants to expose abilities to agents, and by clients that consume the WordPress Abilities API directly.

Npcink AI is only one optional consumer/integration target. This plugin is not a Npcink AI runtime module and must remain useful without Npcink AI installed.

This project owns the WordPress Abilities API registration layer:

- ability categories
- read-only ability registration
- write-proposal ability registration
- first-party host-governed dry-run/write and destructive callbacks
- schema and metadata normalization
- low-risk WordPress read ability packages
- host-governed WordPress write/destructive ability packages
- optional canonical projection for Npcink AI when Npcink AI is installed

Host-governed callbacks default to dry-run previews. A real commit requires approval context from Npcink AI Core, Adapter, or another host runtime.

It does not own model routing, cloud execution, billing, quota, workflow runtime, MCP governance, admission, approval storage, audit truth, or final commit authorization.

This package may provide bounded WordPress callback implementations for generic
read, write, and destructive operations, but it does not decide whether a commit
is allowed, store approval state, own audit truth, or act as the control plane
for writes.

Cross-project platform coordination starts from the
`npcink-workflow-toolbox` repository's `docs/platform/README.md`. This
repository remains the authoritative owner for Toolkit ability contracts,
schemas, dry-run previews, and host-governed callbacks.

## Development

The testing strategy is documented in
[docs/testing-strategy.md](docs/testing-strategy.md). The default local release
source gate is:

```bash
composer test:all
```

Run the lightweight regression tests:

```bash
composer test
```

Run syntax linting:

```bash
composer lint:php
```

Run syntax, contract, governance, performance, and lightweight regression gates:

```bash
composer test:all
```

Run the Core consumer handoff and catalog governance checks:

```bash
composer check:consumer
composer check:catalog
```

Run the registered first-party ability contract readiness audit:

```bash
composer check:contracts
```

Run the isolated bounded-chain performance smoke:

```bash
composer perf:smoke
```

Check that the standalone package has not drifted into Npcink AI runtime ownership:

```bash
composer check:boundary
```

Run the WordPress smoke test from a site where the plugin is installed:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

Run the reusable Docker E2E environment against the official WordPress AI stack:

```bash
composer e2e:official-stack
```

Use `composer e2e:official-stack -- --fresh` to reset Docker volumes, or
`composer e2e:official-stack -- --setup-only` to keep the environment running
for manual browser checks. Details are documented in
[docs/official-stack-e2e.md](docs/official-stack-e2e.md).

Local app socket examples are documented in [docs/local-wpcli-smoke.md](docs/local-wpcli-smoke.md).
The smoke test covers REST discovery, individual ability execution, and the
workflow chains documented in [docs/agent-workflow-validation.md](docs/agent-workflow-validation.md).

Validate composer metadata:

```bash
composer validate:composer
```

The demo provider plugin lives in `examples/demo-plugin/`.
