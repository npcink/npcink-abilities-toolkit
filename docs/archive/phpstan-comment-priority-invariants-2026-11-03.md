# Comment Priority Score Invariant Batch

Date: 2026-11-03 (Asia/Shanghai). Base:
`bf11b3840891d1eca18b99cf2c9675d6a173342b`.
The containing PR records final committed head, local model review, required
CI and protected squash merge. This record describes source verification,
not release readiness or a post-merge Git-state certificate.

## Change Envelope

Toolkit only; focus on the two local priority-score defaults in
`includes/Packages/Core_Comment_Package.php`, the total ceiling, existing debt
classification/JSON and this archive record. No ability schema, permission,
query, public contract, host write authority or release surface changes.
No other repository/chat, human repeat-use validation, 0.8.1 publication,
tag, SVN, real article/SEO data or app attachment work. Cross-repo matrix is
unnecessary. Rollback is a focused revert PR restoring the comparator and
previous ceiling, with the normal gates.

## Live Baseline And Provenance

Fresh fetch/prune found clean master aligned with origin/master at the base
above, one worktree, only local/remote master and no open PRs. No unrelated
local changes existed. `composer install --no-interaction` changed no dependency.
PHP 8.4.21 and PHPStan 2.3.4 completed level-4 analysis using
`phpstan.neon.dist`, its full configured paths, JSON output and 4G memory.
The initial **640**, zero analyzer errors and no global errors reproduced the
prior snapshot; there is no unexplained source/tool/config count drift.

At base line 516 both comparator operands come exclusively from the method's
local `items` array. Every appended row includes `priority_score`, initialized
by `(int) round($confidence * 50)` and adjusted by integer additions only.
The private classifier supplies confidence; its existing float conversion and
score conversion remain. No reference to the local row array escapes before
sorting, including through filterable WordPress sanitizers that receive
individual values rather than the queue. Remove only two `?? 0` defaults;
retain existing comparator casts. Empty/single-row queues never need the
comparator. Numeric ordering and equal-score comparisons are unchanged.

Classification: two redundant local defaults; zero confirmed runtime defects.
All 21 remaining file findings are deferred, including enabled-pack filter
output, get_comments array/object guards and WP_Comment property defaults.
Those defenses, external input handling, normalization, bounded query and
host-governed next-action metadata remain. Required keys alone are not evidence
that externally obtained values have reliable types. No suppression, invented
PHPDoc, new conversion or analysis configuration change is used.

## Verification And Limits

The existing `tests/run/40-block-theme-comment-flows.php` assertions cover queue
success, count, descending priority scores and compliance handoff. Reuse them
through `composer test:core` and `composer test:all`; no implementation-mirroring
test is added. Required source verification comprises those commands,
`composer analyse:phpstan`, `composer check:phpstan-ratchet` after committing,
`composer check:phpstan-total` and `git diff --check`.

Complete before/after JSON reports show **640 → 638**, zero analyzer errors;
Core_Comment_Package **23 → 21**, nullCoalesce.offset **100 → 98**, all other
file/rule counts unchanged. The ceiling follows the actual total to 638.
Full committed base/head reports supplement the changed-file ratchet. The
fresh base uses `git read-tree` plus `git checkout-index` to extract the complete
tracked tree and its own Composer installation, without export-ignore omissions.
Raw reports, input hashes, comparison identities, gate logs and review receipt
stay under ignored `build/phpstan-local-invariants-2026-11-03/`. Tracked
classification retains report/input hashes and the provenance decision.

These checks prove source and isolated fixture behavior. They do not establish
new Local WordPress runtime, production, release or human repeat-use acceptance.
The remaining 638 findings are diagnostic debt, not 638 established bugs; seven
previously reviewed boundary findings remain and 631 await semantic adjudication.
The next candidate is local content-refresh topic-count arrays, subject to a
fresh focused provenance review. No second batch is needed to accept this one.

After protected merge, synchronize master, inspect worktrees and remotes and run
`composer closeout:check`. Use `git branch -d` for the new topic; if squash
ancestry makes it refuse, preserve the branch and record the exact PR/head/merge
exception. Historical force-delete permission does not apply; do not use `-D`
or rewrite ancestry/refs to bypass deletion protection.
