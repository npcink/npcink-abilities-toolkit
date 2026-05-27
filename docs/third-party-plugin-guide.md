# Provider Plugin Guide

Any WordPress plugin can use this toolkit to provide Abilities API capability packages. Magick AI is an optional consumer; other clients can discover and run the same abilities through the standard WordPress Abilities API.

## Register a Provider Category

Use `magick_ai_abilities_register_category()` when your plugin owns a group of abilities:

```php
magick_ai_abilities_register_category(
	'acme-demo',
	array(
		'label'       => 'ACME Demo Abilities',
		'description' => 'Abilities provided by the ACME demo plugin.',
	)
);
```

## Register a Read-Only Ability

Use `magick_ai_abilities_register_readonly()` for diagnostics, discovery, and context retrieval.

The toolkit sets:

- `readonly=true`
- `destructive=false`
- `idempotent=true`
- `show_in_rest=true`
- default capability `manage_options`

## Opt Into Magick AI Compatibility Projection

Abilities are not projected into Magick AI by default. If your provider plugin intentionally wants Magick AI compatibility, set:

```php
'project_to_magick_catalog' => true,
```

The ability remains a normal WordPress Abilities API ability; the projection only adds a Magick AI catalog entry when Magick AI is installed.

## Register a Write Proposal Ability

Use `magick_ai_abilities_register_write_proposal()` for write-like operations.

The callback should return a proposal object instead of committing:

```php
array(
	'proposal_id' => 'proposal-123',
	'subject_refs' => array(),
	'diff' => array(),
	'next_actions' => array( 'approve', 'reject' ),
)
```

Final approval and commit should stay in the host plugin or another governed local control path.

## Local Smoke Test

When this plugin is installed in a WordPress site with WP-CLI available:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

The smoke test verifies Abilities API functions, REST catalog discovery, demo ability execution, and blocked anonymous REST access.
