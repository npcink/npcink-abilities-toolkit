# Schema Boundary Audit

Status: active audit note.

This audit records the current hardening state for built-in write and
destructive abilities.

## Current Guarantees

- Normalized write/destructive inputs include `dry_run`, `commit`, and
  `idempotency_key`.
- `dry_run` defaults to `true`; `commit` defaults to `false`.
- `idempotency_key` is bounded to 190 characters.
- First-party write/destructive input schemas use `additionalProperties=false`.
- Write/destructive abilities expose both `requires_confirm=true` and
  `requires_approval=true`.
- `set-post-seo-meta` no longer treats omitted SEO fields as empty-string
  writes. At least one explicit metadata field is required, and title-only
  updates preserve the existing description.
- `approve-comment` permission behavior is tested so dry-run preview requires
  `moderate_comments`.

## Remaining Watch Items

These are not blockers for Core governance handoff, but they should be reviewed
before broad public automation:

- Some string fields intentionally remain unbounded because WordPress core owns
  final sanitization or the field is content-sized, such as post `content`.
- Some object fields intentionally allow typed extension data, such as
  `create-draft` metadata and preview objects.
- Bulk and remote-media abilities already carry operation limits, but hosts
  should still enforce rate limits and proposal review before exposing them to
  unattended agents.

## Verification

The lightweight tests enforce the common write controls and selected high-value
behavior. The WordPress smoke test verifies that REST ability details expose
`risk_level`, `requires_approval`, `input_schema`, and `output_schema` for the
Core handoff abilities.
