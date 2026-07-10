# Schema Boundary Audit

Status: active audit note.

This audit records the current hardening state for built-in write and
destructive abilities.

## Current Guarantees

- Normalized write/destructive inputs include `dry_run`, `commit`, and
  `idempotency_key`.
- `dry_run` defaults to `true`; `commit` defaults to `false`.
- A mutation requires both `commit=true` and valid host approval context.
  `commit=false` and `dry_run=true` both force a preview, including when a
  caller supplies conflicting control values.
- `idempotency_key` is bounded to 190 characters.
- First-party write/destructive input schemas use `additionalProperties=false`.
- Intentional input schema extension points are recorded in
  `scripts/check-ability-contracts.php`; new `additionalProperties` usage must
  either be tightened or added to that allowlist with a reviewed reason.
- Write/destructive abilities expose both `requires_confirm=true` and
  `requires_approval=true`.
- All host-governed write/destructive abilities expose `implementation_posture`
  metadata with
  WordPress reference patterns, required host evidence, and verification
  contracts while keeping approval storage, audit truth, workflow runtime,
  queues, model routing, and provider credentials out of Toolkit.
- `set-post-seo-meta` no longer treats omitted SEO fields as empty-string
  writes. At least one explicit metadata field is required, and title-only
  updates preserve the existing description.
- `approve-comment` permission behavior is tested so dry-run preview requires
  `moderate_comments`.
- Post-meta reads require an explicit non-sensitive key. Context and page
  readers accept only explicitly supplied safe meta-key allowlists and never
  fall back to every stored value.
- `patch-setting-value` accepts only explicit host-approved setting targets,
  rejects credential-like names, and returns summary hashes rather than setting
  value fragments.
- Remote media is validated in a temporary file before it is copied into the
  uploads directory or registered as an attachment.

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
`risk_level`, `requires_approval`, `input_schema`, `output_schema`, and
`implementation_posture` metadata for representative Core handoff abilities.
