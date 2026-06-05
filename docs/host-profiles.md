# Host Profiles

Status: active guidance.

`npcink-abilities-toolkit` supports a full default profile and a narrow light profile.
Hosts should choose explicitly instead of treating the full built-in surface as
the only safe deployment shape.

## Full Profile

Use the full profile when the host is a governed agent runtime such as Npcink AI
Core, a local automation environment, or another product that can handle caller
identity, scopes, approval, audit, quota, dry-run previews, and final write
authorization.

Default boot enables:

- `core_read`
- `core_write`
- `core_destructive`
- `core_comment`
- `npcink_catalog_bridge`
- `admin_test_page`
- `read_cache_hooks`

The default read sub-packs include:

- `core_wordpress_read`
- `wordpress_diagnostics`
- `workflow_definitions`
- `comment_workflow_context`
- `article_workflow_context`
- `content_operations`
- `media_governance`
- `taxonomy_governance`
- `page_governance`
- `seo_geo_support`

Full profile is appropriate only when write and destructive abilities remain
host-governed. Direct clients should receive previews or fail closed unless the
host provides approved commit context.

## Light Core Read Profile

Use the light profile for third-party plugins, read-only integrations, or hosts
that only need generic WordPress content discovery.

Example:

```php
add_filter(
	'npcink_abilities_toolkit_enabled_packages',
	static function ( $packages ) {
		$packages['core_write']       = false;
		$packages['core_destructive'] = false;
		$packages['core_comment']     = false;
		return $packages;
	}
);

add_filter(
	'npcink_abilities_toolkit_enabled_read_packs',
	static function () {
		return array( 'core_wordpress_read' );
	}
);
```

This profile keeps generic read abilities such as `npcink-abilities-toolkit/site-info`,
`npcink-abilities-toolkit/get-post`, and `npcink-abilities-toolkit/list-posts`.

It disables optional workflow, diagnostics, write, destructive, and comment
helper abilities, including:

- `npcink-abilities-toolkit/wp-diagnostics-summary`
- `npcink-abilities-toolkit/wp-ops-diagnostics-detail`
- `npcink-abilities-toolkit/list-workflow-recipes`
- `npcink-abilities-toolkit/get-workflow-recipe`
- `npcink-abilities-toolkit/get-site-operations-dashboard`
- `npcink-abilities-toolkit/get-comment-queue-health`
- `npcink-abilities-toolkit/create-draft`

## Selection Rule

Default to the light profile unless the host has a concrete governance path for
write and destructive abilities. Use the full profile for controlled hosts that
need workflow context bundles, workflow recipe discovery, diagnostics, comments,
or host-governed write previews.
