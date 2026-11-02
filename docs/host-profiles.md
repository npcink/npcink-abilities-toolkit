# Host Profiles

Status: active guidance.

`npcink-abilities-toolkit` supports opt-in `core_read` and `read_only` profiles plus the compatible
`full` default.
Hosts should choose explicitly instead of treating the full built-in surface as
the only safe deployment shape.

Hosts that govern write and destructive commits follow the host-agnostic
[Host Approval Contract](host-approval-contract.md).

## Named Profile Selection

Install this in a site-owned MU plugin before `plugins_loaded`:

```php
add_filter(
    'npcink_abilities_toolkit_registration_profile',
    static function () { return 'core_read'; }
);
```

| Profile | Built-in read surface | Built-in writes/deletion | Npcink bridge |
| --- | --- | --- | --- |
| `core_read` | Generic WordPress reads only | Disabled | Disabled |
| `read_only` | All read/context and comment helpers, diagnostics and recipe metadata | Disabled | Available |
| `full` (compatible default) | All built-in reads | Dry-run first, host approval required | Available |

Invalid values fall back to `core_read`. Select once for the request, before
registration; changing the filter after boot cannot unregister existing
abilities. Existing package and sub-pack filters can narrow the selected
profile. Neither those filters nor a per-ability read filter can widen it.
The operator read-only setting still wins over `full`.

Profiles cover **Toolkit built-in packages only**. Third-party abilities keep
their provider's registration and permission rules; a `read_only` profile is
not a sandbox for arbitrary PHP callbacks. Read-risk plans may name future
write actions without executing them.

For vertical deployments, select `read_only` and narrow
`npcink_abilities_toolkit_enabled_read_packs` to the required existing sub-packs
(for example `core_wordpress_read` plus `media_governance`). Keep
`workflow_definitions` only when the host needs recipe discovery, and check the
referenced entrypoints are enabled before selecting a recipe. Recipes do not
execute or re-enable omitted abilities. Use `full` plus explicit package
opt-outs only when approved writes are required.

See [ADR 0010](adr/0010-opt-in-registration-profiles.md). Full default behavior
is retained under ADR 0008; the operator switch already shipped under ADR 0009.

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

Use `core_read` for third-party plugins, read-only integrations, or hosts
that only need generic WordPress content discovery.

Example:

```php
add_filter(
	'npcink_abilities_toolkit_registration_profile',
	static function () { return 'core_read'; }
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

Choose `core_read` for generic WordPress reads. Choose `read_only` when the host
also needs workflow context bundles, recipe discovery, diagnostics or comment
helpers. Choose `full` when it needs write previews or approved commits and has
a concrete governance path for them. This integration guidance does not change
the compatible `full` installation default.
