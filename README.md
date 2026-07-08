# Npcink Abilities Toolkit

Standalone WordPress Abilities API plugin for packaging and registering agent-callable abilities.

## Scope

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

## Requirements

- WordPress 7.0+ with the Abilities API available
- PHP 8.0+

## External Integration Paths

Use [docs/README.md](docs/README.md) as the documentation entry point for
external integration and debugging:

- plugin authors providing abilities should start with
  [docs/third-party-plugin-guide.md](docs/third-party-plugin-guide.md);
- REST and external clients should start with
  [docs/rest-client-quickstart.md](docs/rest-client-quickstart.md);
- host products should start with [docs/host-profiles.md](docs/host-profiles.md)
  and [docs/permission-matrix.md](docs/permission-matrix.md);
- common setup, authentication, permission, and dry-run failures are covered in
  [docs/troubleshooting.md](docs/troubleshooting.md).

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

The 0.1 public API freeze is documented in [docs/public-api-freeze-0.1.md](docs/public-api-freeze-0.1.md).
The migration boundary from the Npcink AI plugin is documented in [docs/adr/0001-migrate-abilities-from-magick-ai.md](docs/adr/0001-migrate-abilities-from-magick-ai.md).
The independent-project split and Npcink AI integration boundary are documented in [docs/npcink-ai-project-split-contract.md](docs/npcink-ai-project-split-contract.md), which is the canonical ownership source when boundary documents disagree.
The built-in abilities are grouped by product purpose in [docs/first-party-ability-packs.md](docs/first-party-ability-packs.md).
Recommended host-side workflow compositions are documented as reference recipes in [docs/workflow-recipes.md](docs/workflow-recipes.md).
The machine-readable workflow definition field rules are documented in [docs/workflow-definition-contract.md](docs/workflow-definition-contract.md).
The article workflow ability map is documented in [docs/article-workflow-abilities-v1.md](docs/article-workflow-abilities-v1.md).
Static agent and MCP usage guidance rules are documented in [docs/agent-usage-metadata.md](docs/agent-usage-metadata.md).
The Core governance handoff rules are documented in [docs/core-governance-handoff-guide.md](docs/core-governance-handoff-guide.md).
The Core handoff catalog snapshot, permission matrix, and schema boundary audit
are documented in [docs/core-governance-catalog-snapshot.md](docs/core-governance-catalog-snapshot.md),
[docs/permission-matrix.md](docs/permission-matrix.md), and
[docs/schema-boundary-audit.md](docs/schema-boundary-audit.md).
Recommended full and light host profiles are documented in [docs/host-profiles.md](docs/host-profiles.md).
Performance and caching rules are documented in [docs/performance-and-caching.md](docs/performance-and-caching.md).
Security and governance gates for this package's contract boundary are
documented in [docs/security-and-governance-gates.md](docs/security-and-governance-gates.md).
Official WordPress AI stack compatibility guidance is documented in
[docs/official-wordpress-ai-stack-compatibility.md](docs/official-wordpress-ai-stack-compatibility.md).
The 0.3 stabilization surface is tracked in [docs/ability-acceptance-matrix.md](docs/ability-acceptance-matrix.md), [docs/agent-workflow-validation.md](docs/agent-workflow-validation.md), and [docs/release-0.3-scope.md](docs/release-0.3-scope.md). Npcink AI consumers that depend on package gating, thin projection defaults, or explicit sub-pack maps should require version `0.3.0` or newer.
The 0.5 release verification line is tracked in [docs/release-0.5-verification.md](docs/release-0.5-verification.md), with maintenance patches recorded in [docs/release-0.5.1-verification.md](docs/release-0.5.1-verification.md) and [docs/release-0.5.2-verification.md](docs/release-0.5.2-verification.md).
The 0.5 ability contract readiness plan is tracked in [docs/ability-contract-readiness-0.5.md](docs/ability-contract-readiness-0.5.md).
The 2026-07-08 Core/Adapter/Product reuse readiness observation is tracked in
[docs/ability-contract-reuse-readiness-2026-07-08.md](docs/ability-contract-reuse-readiness-2026-07-08.md).
The next-stage operating standard for freeze/observe mode, workflow proof,
performance gates, and security boundaries is tracked in
[docs/next-stage-operating-standard.md](docs/next-stage-operating-standard.md).
The 2026-06-17 freeze/observe proof phase closeout is recorded in
[docs/freeze-observe-phase-closeout-2026-06-17.md](docs/freeze-observe-phase-closeout-2026-06-17.md).
The admin page scope is documented in [docs/admin-surface-standard.md](docs/admin-surface-standard.md).
Release notes are tracked in [CHANGELOG.md](CHANGELOG.md), and the WordPress plugin directory style metadata lives in [readme.txt](readme.txt).
Bundled starter translations live in [languages](languages) and cover the admin connection/discovery surface, API ability labels/descriptions, and common runtime error messages for Simplified Chinese, Japanese, Korean, French, German, Spanish, and Brazilian Portuguese. The package only ships locale files that are intentionally maintained in this repository; incomplete bundled locale packs are removed until they can be maintained as a complete starter set. WordPress.org directory translations remain managed through translate.wordpress.org/GlotPress and are not a runtime authority owned by this plugin.

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

The Available Abilities and Checks tabs can:

- filter abilities by name, description, category, risk, technical ID, and page size
- show user-facing ability labels, descriptions, risk posture, availability, and technical details
- run `npcink-abilities-toolkit/site-info` and a bounded `npcink-abilities-toolkit/wp-diagnostics-summary` as read-only checks
- explain what each check proves and what it does not prove before it runs
- show check results as a plain summary table, with raw JSON kept behind a support disclosure

The Developer Access tab can:

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

- `npcink-abilities-toolkit/site-info`
- `npcink-abilities-toolkit/list-post-types`
- `npcink-abilities-toolkit/list-taxonomies`
- `npcink-abilities-toolkit/count-posts`
- `npcink-abilities-toolkit/list-pages-tree`
- `npcink-abilities-toolkit/list-posts`
- `npcink-abilities-toolkit/get-post`
- `npcink-abilities-toolkit/resolve-url-to-post`
- `npcink-abilities-toolkit/get-post-blocks`
- `npcink-abilities-toolkit/list-post-revisions`
- `npcink-abilities-toolkit/list-media`
- `npcink-abilities-toolkit/resolve-media-attachment-by-url`
- `npcink-abilities-toolkit/list-terms`
- `npcink-abilities-toolkit/list-taxonomy-terms`
- `npcink-abilities-toolkit/list-categories`
- `npcink-abilities-toolkit/list-tags`
- `npcink-abilities-toolkit/get-term`
- `npcink-abilities-toolkit/suggest-post-taxonomy-terms`
- `npcink-abilities-toolkit/build-taxonomy-tag-review-set`
- `npcink-abilities-toolkit/propose-post-taxonomy-terms`
- `npcink-abilities-toolkit/propose-post-excerpt`
- `npcink-abilities-toolkit/list-users`
- `npcink-abilities-toolkit/list-comments`
- `npcink-abilities-toolkit/build-comment-moderation-suggest`
- `npcink-abilities-toolkit/compose-comment-moderation-result`
- `npcink-abilities-toolkit/build-comment-mention-reply-suggest`
- `npcink-abilities-toolkit/read-comment-trigger-queue`
- `npcink-abilities-toolkit/get-comment-compliance-handoff`
- `npcink-abilities-toolkit/compose-comment-mention-reply-result`
- `npcink-abilities-toolkit/build-comment-moderation-batch-suggest`
- `npcink-abilities-toolkit/compose-comment-moderation-batch-result`
- `npcink-abilities-toolkit/list-menus`
- `npcink-abilities-toolkit/get-menu`
- `npcink-abilities-toolkit/search-posts`
- `npcink-abilities-toolkit/search-post-meta`
- `npcink-abilities-toolkit/resolve-post-metadata-plan`
- `npcink-abilities-toolkit/resolve-internal-link-targets`
- `npcink-abilities-toolkit/build-inline-image-blocks`
- `npcink-abilities-toolkit/build-media-seo-assets`
- `npcink-abilities-toolkit/geo-analyze`
- `npcink-abilities-toolkit/optimize-media-metadata`
- `npcink-abilities-toolkit/inspect-media-asset`
- `npcink-abilities-toolkit/build-media-derivative-cloud-request`
- `npcink-abilities-toolkit/build-media-optimization-plan`
- `npcink-abilities-toolkit/build-image-candidate-review-artifact`
- `npcink-abilities-toolkit/build-media-alt-caption-review-set`
- `npcink-abilities-toolkit/build-image-candidate-adoption-plan`
- `npcink-abilities-toolkit/upload-media-from-url`
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/position-inline-image-blocks`
- `npcink-abilities-toolkit/build-article-optimization-report`
- `npcink-abilities-toolkit/seo-report-context`
- `npcink-abilities-toolkit/read-post-optimization-context`
- `npcink-abilities-toolkit/build-article-single-optimization-suggest`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
- `npcink-abilities-toolkit/build-content-metadata-apply-plan`
- `npcink-abilities-toolkit/compose-article-optimization-apply-result`
- `npcink-abilities-toolkit/get-article-publish-preflight-context`
- `npcink-abilities-toolkit/get-old-article-refresh-context`
- `npcink-abilities-toolkit/extract-reference-post-style`
- `npcink-abilities-toolkit/extract-style-baseline`
- `npcink-abilities-toolkit/build-article-production-fingerprint`
- `npcink-abilities-toolkit/check-article-production-duplicate`
- `npcink-abilities-toolkit/review-article-output-light`
- `npcink-abilities-toolkit/compose-article-production-result`
- `npcink-abilities-toolkit/compose-article-draft-result`
- `npcink-abilities-toolkit/resolve-article-publication-decision`

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
- `npcink-abilities-toolkit/build-article-style-profile`
- `npcink-abilities-toolkit/get-post-stats`
- `npcink-abilities-toolkit/list-revisions`
- `npcink-abilities-toolkit/get-post-meta`
- `npcink-abilities-toolkit/list-pages`
- `npcink-abilities-toolkit/get-page`
- `npcink-abilities-toolkit/inspect-page-structure`

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
