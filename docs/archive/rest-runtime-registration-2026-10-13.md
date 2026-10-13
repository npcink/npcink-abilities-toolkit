# REST Runtime Registration — 2026-10-13

Real HTTPS Core discovery exposed 44 abilities and no Toolkit abilities while
WP-CLI exposed the Toolkit catalog. WordPress defines `REST_REQUEST` after
`init`; `wp_is_rest_endpoint()` checks REST dispatch state rather than the URI.
The old init-only package decision therefore skipped Toolkit on REST requests.

Plugin now retries context-aware built-in package registration on
`rest_api_init` at priority 0, before REST consumers initialize the Abilities
registry. A one-time guard prevents duplicate package hooks and callbacks.
Anonymous frontend boot stays light, and the host package filter, frontend
context filter, operator read-only mode and host commit gate still apply.
No ability ids, schemas, callbacks or approval contracts change.

The lifecycle regression failed before the fix and passed afterward. It covers
init without REST context, REST initialization before dispatch, repeat boot,
disabled write packages and operator read-only mode. Real signed HTTPS Core
discovery on magick-ai.local then exposed 209 abilities, including 165 Toolkit
abilities. Local test:core, test:all, analyse:phpstan and check:boundary passed.
The changed-file PHPStan ratchet is checked on the committed source.

This is unreleased source. Revert this focused patch to roll back; no data
migration or option change is required. The unrelated local structure baseline
changes are excluded from this patch.
