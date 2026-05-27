# Third-Party Plugin Guide

## Register a Read-Only Ability

Use `magick_ai_abilities_register_readonly()` for diagnostics, discovery, and context retrieval.

The toolkit sets:

- `readonly=true`
- `destructive=false`
- `idempotent=true`
- `show_in_rest=true`
- default capability `manage_options`

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
