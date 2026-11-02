# ADR 0010: Opt-In Registration Profiles

Status: accepted.
Date: 2026-11-02.

## Context

The full default catalog is an explicit compatibility decision in ADR 0008.
ADR 0009 already gives operators a narrowing-only read-only switch. Changing
existing installations to a smaller default would remove discoverable abilities
from consumers, so a maintenance change must not silently flip that default.
The light profile previously required hosts to coordinate multiple filters.

## Decision

Add `npcink_abilities_toolkit_registration_profile`, selected before
`plugins_loaded`, with `full`, `read_only`, and `core_read` values. Keep `full`
as the default. Apply the profile as an intersection after existing package
filters; apply the operator read-only choice last. For `core_read`, bound read
sub-packs even when a per-ability filter returns true. Invalid values select
`core_read`, never widen to the full surface.

Profiles select built-in registrations only. They do not change ability ids,
schemas, risk, permission callbacks, provider registrations or commit authority.
`read_only` keeps all built-in read/context/proposal artifacts and comment
helpers while disabling write and destructive packages. `core_read` also removes
optional read sub-packs, comment helpers and the Npcink catalog bridge. Both keep
the operator status page and read-cache hooks unless a host disables them.

Use existing sub-pack filters for vertical subsets; do not create a second
ability catalog or a product workflow registry. Provider plugins remain
responsible for their own ability risk and execution behavior.

## Consequences

- A new read-only integration can choose a small registration set with one hook.
- Existing consumers retain the default catalog. A future default change still
  needs a separate ADR, upgrade guidance and release decision.
- Lower-level filters and operator settings can narrow profiles, never widen
  past them. Profile selection is fixed for the request and is not runtime
  approval or authorization.
- Public discovery remains the WordPress Abilities API. Profile tests boot each
  configuration separately; the real WordPress provider/host proof exercises
  REST discovery and execution independently of Npcink Core and Adapter.
