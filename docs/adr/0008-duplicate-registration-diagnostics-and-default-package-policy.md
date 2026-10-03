# ADR 0008: Duplicate Ability Registration Diagnostics and Default Package Policy

Status: accepted
Date: 2026-10-03

## Context

The 0.5.4 closeout recorded two governance questions (issue #116) that needed
an explicit product decision instead of an inherited default:

1. Duplicate ability registration could be skipped silently. Two distinct
   paths behaved this way: the registrar's `add()` overwrote an existing
   ability id in the same registrar instance (last-writer-wins), and
   `register_single_with_wordpress()` returned early when
   `wp_has_ability()` reported the id as already registered in WordPress.
2. The `core_write` and `core_destructive` packages booted enabled by
   default alongside every other package in `Plugin::get_enabled_packages()`.

Related prior decisions sharpen the frame:

- ADR 0007 and the 0.5.7 user-experience hardening established a fail-loud
  direction for broken contracts, but that direction was applied to request
  surfaces where a loud failure affects one request. Registration runs at
  boot, where a thrown failure can take a whole site down.
- The delete-attachment decision (PR #176) established that same-domain
  abilities with different input schemas, previews, and post-conditions are
  not duplicates; duplicate here means a second registration of the same
  ability id.
- A per-package boot filter and a light package profile already exist as
  opt-down mechanisms for write and destructive packages.

## Decision

### 1. Duplicate registration emits bounded diagnostics; semantics stay compatible

Re-registering an ability id that already exists in the same registrar
instance keeps the existing last-writer-wins overwrite, and a registration
that WordPress already owns keeps the existing skip. Both paths now emit an
`abilities.registration.duplicate` observability event whose payload records
the ability id, the registration surface (`toolkit_registry` or
`wordpress_registry`), and whether the normalized contract fields
(label, description, category, input schema, output schema, meta) changed
between the previous and incoming definition.

Rationale:

- A hard failure (exception or fatal) at boot trades site availability for a
  condition that also occurs in benign double-boot flows. That trade is not
  justified while no consumer defect has been observed.
- Staying fully silent hides the one genuinely dangerous case: a silent
  contract swap on an existing id. The diagnostics flag exactly that case
  (`contract_changed: true`) through the existing local observability
  surface without changing any public behavior.
- Escalating to a hard failure remains a 1.0 candidate decision, with the
  diagnostics providing the evidence base for it.

### 2. `core_write` and `core_destructive` stay default-enabled as an explicit decision

The seven-package default boot map stays as is, including `core_write` and
`core_destructive`. This is now an explicit product and security decision,
not an inherited default, resting on the compensating controls:

- Write-like and destructive abilities default to dry-run previews; a real
  commit requires host approval through the Host Approval Contract, which
  this repository does not own or bypass.
- The package boot map filter and the light profile provide per-site
  opt-down for operators that want a read-mostly surface.
- The standalone product story requires the packages to exist for hosts to
  discover and govern; opt-in packages would break every existing consumer
  contract, including the standalone site-owner flow documented in the
  readme.

Changing this default later requires a new ADR, an upgrade note, and a
separate release, per the issue's acceptance criteria.

## Consequences

- Tests assert the duplicate diagnostics: same-fingerprint
  re-registration reports `contract_changed: false`, changed-contract
  re-registration reports `contract_changed: true`, and the
  WordPress-level skip emits the `wordpress_registry` variant.
- Tests assert the seven-package default boot map and that the package
  filter can disable `core_write` and `core_destructive`.
- No upgrade or compatibility note is required because no default changed.
- Issue #116 closes with this ADR as the recorded decision.
