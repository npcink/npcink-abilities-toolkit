# ADR 0009: Operator Read-Only Package Opt-Out

Date: 2026-10-11

Status: accepted, implementation deferred to the 0.6 line (or the first
real demand signal, whichever comes first).

## Context

ADR 0008 keeps all seven built-in packages enabled by default and controls
them through the `npcink_abilities_toolkit_enabled_packages` filter, which
host products own. The 2026-10 end-user audit found the gap in that design:
a standalone site owner — no host product, no PHP author — has no way to stop
their site registering write and destructive abilities. The 0.5.12 batch
shipped documentation and a copy-paste mu-plugin snippet for read-only mode
(`docs/troubleshooting.md`, "Turn Ability Packages On Or Off"), which is the
right floor, but a visible switch is the strongest trust signal for a plugin
whose store listing leads with "every write-like ability is a preview by
default". It also preempts the predictable WordPress.org review or support
question: why does this plugin register delete-post abilities out of the box.

Constraints from the repository boundary: package enablement is a
registration-time concern and must stay one. An operator setting must not
become workflow runtime state, approval storage, quota, audit, or a second
control plane. ADR 0008's default policy (everything on, host filters narrow)
must not change retroactively.

## Decision

When implemented, add exactly one operator-facing setting, "read-only mode",
with narrowing-only semantics:

1. **One option, one direction.** A single site option
   `npcink_abilities_toolkit_read_only_mode` (autoload off, default off, so
   0.5.x behavior is unchanged). When on, `core_write` and
   `core_destructive` never register. It can never enable anything.
2. **Precedence is an AND.** Effective enablement for a write-like package is
   `host_filter_result && ! read_only_mode`. The stored option can only force
   write-like packages off; it can never override a host that disabled a
   package, and it never touches `core_read`, `core_comment`,
   `npcink_catalog_bridge`, `admin_test_page`, or `read_cache_hooks`.
3. **Site-owner authority is final but visible.** If a host product needs
   write abilities while read-only mode is on, the host surfaces the
   conflict to the operator through its own UI. The Toolkit never
   auto-disables the setting and the host filter can never widen past it.
4. **UI stays minimal.** A checkbox in the Overview ability-package section
   of the admin status page (inside the `admin_test_page` package), with a
   one-line explanation of what turns off. Effect applies on the next
   request; no confirmation flow, no settings screen of its own.
5. **Lifecycle.** Uninstall removes the option. Multisite keeps it per site,
   matching how package registration resolves per site context.
6. **Localization and tests.** New admin strings ship in all seven bundled
   locales at full parity using the literal translation-map pattern; tests
   cover the AND precedence in both directions, the default-off behavior,
   and uninstall cleanup.

## Consequences

- Standalone operators get a one-click answer to "make this site read-only
  for AI", and the packages list becomes honest about who controls what:
  filter profile from hosts, read-only mode from the operator.
- Two inputs now shape package state (host filter, operator option). The AND
  rule keeps them composable and unambiguous; the packages list should show
  the effective state and name read-only mode when it is the reason a
  write-like package is off.
- The setting is deliberately registration-level only. It does not change
  ability contracts, dry-run semantics, host approval, idempotency, or any
  runtime governance; those remain exactly as the host approval contract
  defines.
- Deferring the implementation keeps the 0.5.x line unchanged while locking
  the semantics now, so the eventual implementation is mechanical review
  rather than a design discussion.
