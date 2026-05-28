# Magick AI Abilities

Standalone WordPress Abilities API plugin for packaging and registering agent-callable abilities.

## Scope

This project is an independent Abilities API capability-package plugin. It can be used by any WordPress plugin that wants to expose abilities to agents, and by clients that consume the WordPress Abilities API directly.

Magick AI is only one optional consumer/integration target. This plugin is not a Magick AI runtime module and must remain useful without Magick AI installed.

This project owns the WordPress Abilities API registration layer:

- ability categories
- read-only ability registration
- write-proposal ability registration
- schema and metadata normalization
- low-risk WordPress read ability packages
- optional compatibility projection for Magick AI when Magick AI is installed

It does not own model routing, cloud execution, billing, quota, workflow runtime, MCP governance, or final write approval/governance.

## Requirements

- WordPress 6.9+ with the Abilities API available
- PHP 7.2+

## Public API

```php
magick_ai_abilities_register_category( $category_id, $args );
magick_ai_abilities_register_readonly( $ability_id, $definition );
magick_ai_abilities_register_write_proposal( $ability_id, $definition );
magick_ai_abilities_normalize_schema( $schema, $default_type );
magick_ai_abilities_normalize_annotations( $annotations, $risk_level );
magick_ai_abilities_get_registered();
```

The 0.1 public API freeze is documented in [docs/public-api-freeze-0.1.md](docs/public-api-freeze-0.1.md).
The migration boundary from the Magick AI plugin is documented in [docs/adr/0001-migrate-abilities-from-magick-ai.md](docs/adr/0001-migrate-abilities-from-magick-ai.md).
The independent-project split and Magick AI integration boundary are documented in [docs/magick-ai-project-split-contract.md](docs/magick-ai-project-split-contract.md).
The built-in abilities are grouped by product purpose in [docs/first-party-ability-packs.md](docs/first-party-ability-packs.md).
The 0.3 stabilization surface is tracked in [docs/ability-acceptance-matrix.md](docs/ability-acceptance-matrix.md), [docs/agent-workflow-validation.md](docs/agent-workflow-validation.md), and [docs/release-0.3-scope.md](docs/release-0.3-scope.md).
Release notes are tracked in [CHANGELOG.md](CHANGELOG.md), and the WordPress plugin directory style metadata lives in [readme.txt](readme.txt).

## Minimal Example

```php
add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'magick_ai_abilities_register_readonly' ) ) {
			return;
		}

		magick_ai_abilities_register_readonly(
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

When Magick AI is installed, provider plugins may opt into compatibility projection so their registered abilities appear in `magick_ai_open_platform_ability_catalog` as `wp_ability` backend entries. Other plugins can ignore that integration and consume the same abilities through the standard WordPress Abilities API.

## Test Page

After activating the plugin, open **Tools -> Abilities API Packages** in wp-admin.

The page can:

- verify whether the WordPress Abilities API is available
- fetch `/wp-json/wp-abilities/v1/abilities` with the current logged-in user's REST nonce
- fetch `/wp-json/wp-abilities/v1/categories`
- enable and run a demo read-only ability: `magick-ai-abilities/site-summary`

## Built-In WordPress Read and Comment Packages

The migrated core read and deterministic comment packages provide these read-only WordPress abilities:

- `magick-ai/site-info`
- `magick-ai/list-post-types`
- `magick-ai/list-taxonomies`
- `magick-ai/count-posts`
- `magick-ai/list-pages-tree`
- `magick-ai/list-posts`
- `magick-ai/get-post`
- `magick-ai/resolve-url-to-post`
- `magick-ai/get-post-blocks`
- `magick-ai/list-post-revisions`
- `magick-ai/list-media`
- `magick-ai/list-terms`
- `magick-ai/list-taxonomy-terms`
- `magick-ai/list-categories`
- `magick-ai/list-tags`
- `magick-ai/get-term`
- `magick-ai/propose-post-excerpt`
- `magick-ai/list-users`
- `magick-ai/list-comments`
- `magick-ai/build-comment-moderation-suggest`
- `magick-ai/compose-comment-moderation-result`
- `magick-ai/build-comment-mention-reply-suggest`
- `magick-ai/read-comment-trigger-queue`
- `magick-ai/compose-comment-mention-reply-result`
- `magick-ai/build-comment-moderation-batch-suggest`
- `magick-ai/compose-comment-moderation-batch-result`
- `magick-ai/list-menus`
- `magick-ai/get-menu`
- `magick-ai/search-posts`
- `magick-ai/resolve-post-metadata-plan`
- `magick-ai/resolve-internal-link-targets`
- `magick-ai/build-inline-image-blocks`
- `magick-ai/build-media-seo-assets`
- `magick-ai/geo-analyze`
- `magick-ai/optimize-media-metadata`
- `magick-ai/position-inline-image-blocks`
- `magick-ai/build-article-optimization-report`
- `magick-ai/seo-report-context`
- `magick-ai/read-post-optimization-context`
- `magick-ai/build-article-single-optimization-suggest`
- `magick-ai/build-article-optimization-apply-plan`
- `magick-ai/compose-article-optimization-apply-result`
- `magick-ai/extract-reference-post-style`
- `magick-ai/extract-style-baseline`
- `magick-ai/build-article-production-fingerprint`
- `magick-ai/check-article-production-duplicate`
- `magick-ai/review-article-output-light`
- `magick-ai/compose-article-production-result`
- `magick-ai/compose-article-draft-result`
- `magick-ai/resolve-article-publication-decision`
- `magick-ai/build-article-style-profile`
- `magick-ai/get-post-stats`
- `magick-ai/list-revisions`
- `magick-ai/get-post-meta`
- `magick-ai/list-pages`
- `magick-ai/get-page`
- `magick-ai/inspect-page-structure`

The `magick-ai/*` ids are preserved for compatibility during the migration from the Magick AI plugin. They do not project into Magick AI by default.

## Built-In WordPress Diagnostics

The standalone diagnostics package provides:

- `magick-ai-abilities/wp-diagnostics-summary`

This ability returns a redacted WordPress-only environment summary for agents and other plugins. It reports site hosts, WordPress/PHP runtime details, active theme summary, plugin counts, REST/Abilities API availability, cron counts, and update counts. It intentionally omits Magick AI settings, MCP settings, API keys, database names, table prefixes, filesystem paths, error log contents, and external HTTP probes.

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

Check that the standalone package has not drifted into Magick AI runtime ownership:

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
