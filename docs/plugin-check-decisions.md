# Plugin Check Decisions

Plugin Check findings require data-flow review. Fix invalid input handling;
retain necessary operations with a narrow inline exception that explains the
invariant. A clean report does not establish that all security or performance
risks are absent. Do not suppress entire files or sniff families.

## Input And Conditional Requests

Admin dismissal and read-only mode handlers sanitize unslashed nonce values
before verification. The read-only checkbox rejects array-shaped input before
changing its option. Redirect targets must be strings, pass `esc_url_raw()`,
and then pass WordPress redirect-host validation. Capabilities and nonce checks
remain mandatory for writes.

Contract ETag revalidation reads `If-None-Match` from `WP_REST_Request` through
`get_header()`. It does not use process-global `$_SERVER` state, which can differ
from an internally dispatched REST request. The existing ETag matcher handles
quoted tags, weak tags, lists, and wildcard semantics.

Behavioral evidence: `tests/run/10-registry-plugin-boot.php` covers denial,
array-shaped nonce/checkbox values and request-header/global disagreement;
`tests/audit-wordpress.php` exercises matching and mismatched real REST headers.

## Retained Operations

| Source / finding | Decision and reason | Evidence |
| --- | --- | --- |
| `Plugin::load_textdomain`, discouraged `load_plugin_textdomain` | Keep the call to register bundled `languages/` fallback for Git/manual installs. WordPress.org language packs keep precedence. Removing it would break the maintained starter-pack contract. | Boot regression checks the registered language path; `check:wporg` verifies the bundled PO/MO set. |
| `Health_Notices::render_redirect_error`, nonce recommendation | Read-only display selects the fixed `nonce` error message; it does not dismiss a notice or change metadata. The actual dismissal handler verifies nonce and capability. | Health-notice dismissal rejection and persistence tests. |
| `cloud_media_cas_update_post_meta`, slow `meta_key` | The key is an in-memory mutation-record field, not query arguments. | Cloud media rollback, drift and transaction tests in `tests/run/60-media-file-operations.php`. |
| `cloud_media_atomic_compare_and_swap_post_meta`, uncached START/COMMIT | These statements must execute against the live connection and cannot be served from a result cache. Keep the row lock, exact readback and cache invalidation. | Transaction failure/reentrancy regressions in `tests/run/60-media-file-operations.php`. |
| `cloud_media_rollback_locked_post_meta_mutation`, uncached ROLLBACK | Compensation must execute against the live transaction even after a failure. | Rollback-failure and poisoned-transaction regressions in the same suite. |
| `Media_Backup_Write_Methods` cleanup query, slow `meta_key` | Keep the fixed history-key filter with attachment-ID cursor paging, a hard candidate cap plus one sentinel, and no total-row count. This bounds emitted records, not worst-case database latency. | Media-backup cleanup cursor/cap tests in `tests/run/70-cloud-media-backups.php`. |

The retained inline exceptions account for seven original warnings: one
translation registration, one read-only error flag, one mutation-record field,
three transaction statements, and one bounded cleanup query. New findings must
be investigated against their current source and runtime; this table is not a
blanket exception for similar-looking code.

The [2026-11-02 database experiment](media-backup-query-benchmark.md) measures
100k/1m synthetic postmeta datasets across distributions and cursor positions.
It retains the current query: the EXISTS alternative regresses on dense data.
An initial empty-result plan still visited 500k rows, confirming that the
result cap is not a scan or latency guarantee. Plans and repeat timings are
recorded separately from this retained Plugin Check exception.
