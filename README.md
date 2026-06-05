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

## Requirements

- WordPress 7.0+ with the Abilities API available
- PHP 8.0+

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

The 0.1 public API freeze is documented in [docs/public-api-freeze-0.1.md](docs/public-api-freeze-0.1.md).
The migration boundary from the Npcink AI plugin is documented in [docs/adr/0001-migrate-abilities-from-magick-ai.md](docs/adr/0001-migrate-abilities-from-magick-ai.md).
The independent-project split and Npcink AI integration boundary are documented in [docs/npcink-ai-project-split-contract.md](docs/npcink-ai-project-split-contract.md).
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
The 0.3 stabilization surface is tracked in [docs/ability-acceptance-matrix.md](docs/ability-acceptance-matrix.md), [docs/agent-workflow-validation.md](docs/agent-workflow-validation.md), and [docs/release-0.3-scope.md](docs/release-0.3-scope.md). Npcink AI consumers that depend on package gating, thin projection defaults, or explicit sub-pack maps should require version `0.3.0` or newer.
The 0.5 unreleased observation line is tracked in [docs/release-0.5-unreleased-verification.md](docs/release-0.5-unreleased-verification.md).
The 0.5 ability contract readiness plan is tracked in [docs/ability-contract-readiness-0.5.md](docs/ability-contract-readiness-0.5.md).
The admin page scope is documented in [docs/admin-surface-standard.md](docs/admin-surface-standard.md).
Release notes are tracked in [CHANGELOG.md](CHANGELOG.md), and the WordPress plugin directory style metadata lives in [readme.txt](readme.txt).

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
composer check:catalog
```

Use `scripts/audit-ability-catalog.php` with one or more catalog JSON files to
detect duplicate ability ids and governance metadata drift before merging
cross-repo Core integration changes.

## Test Page

After activating the plugin with a Npcink AI host plugin, open
**Npcink AI -> Abilities** in wp-admin. When this standalone package is
installed without a Npcink AI host menu, open **Tools -> Abilities API
Packages** instead.

The default page shows package readiness and the registered ability catalog:

- WordPress Abilities API support
- registered ability count and demo ability state
- ability category, risk, callback, and schema signals

Advanced checks are kept behind disclosures and can:

- verify whether the WordPress Abilities API is available
- fetch `/wp-json/wp-abilities/v1/abilities` with the current logged-in user's REST nonce
- fetch `/wp-json/wp-abilities/v1/categories`
- enable and run a demo read-only ability: `npcink-abilities-toolkit/site-summary`

## Built-In WordPress Read and Comment Packages

The migrated core read and deterministic comment packages provide these read-only WordPress abilities:

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
- `npcink-abilities-toolkit/build-media-rename-plan`
- `npcink-abilities-toolkit/position-inline-image-blocks`
- `npcink-abilities-toolkit/build-article-optimization-report`
- `npcink-abilities-toolkit/seo-report-context`
- `npcink-abilities-toolkit/read-post-optimization-context`
- `npcink-abilities-toolkit/build-article-single-optimization-suggest`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
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

Run the lightweight regression tests:

```bash
composer test
```

Run syntax linting:

```bash
composer lint:php
```

Run both:

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

Local app socket examples are documented in [docs/local-wpcli-smoke.md](docs/local-wpcli-smoke.md).
The smoke test covers REST discovery, individual ability execution, and the
workflow chains documented in [docs/agent-workflow-validation.md](docs/agent-workflow-validation.md).

Validate composer metadata:

```bash
composer validate:composer
```

The demo provider plugin lives in `examples/demo-plugin/`.
