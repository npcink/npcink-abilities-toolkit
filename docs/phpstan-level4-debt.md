# PHPStan level-4 debt classification — 2026-11-02

Current status after the complete November 3 adjudication: **526 diagnostics,
ceiling 526, zero analyzer errors; every occurrence in the fresh 579-finding
inventory has an explicit disposition**. This is not numeric zero or 526 bugs.
The older sections below remain dated batch evidence; their pending counts are
historical. The [per-location adjudication](evidence/phpstan-level4-adjudication-2026-11-03.json)
is the current decision ledger. New source/tool changes require a fresh report.

The complete report contains 659 diagnostics before this batch and 653 after
it, with zero analyzer-level errors. These numbers are not runtime defect
counts. Normal source analysis remains level 3; the changed-file and release
total ratchets use level 4. Vendor tooling was synchronized first. The initial
128 MiB standalone attempt exhausted memory and was discarded as incomplete;
the complete reports use `--memory-limit=1G`.

## Rules

| Identifier | Before | After |
| --- | ---: | ---: |
| `nullCoalesce.property` | 259 | 259 |
| `function.alreadyNarrowedType` | 246 | 245 |
| `nullCoalesce.offset` | 113 | 112 |
| `booleanAnd.rightAlwaysTrue` | 7 | 7 |
| `notIdentical.alwaysTrue` | 7 | 7 |
| `isset.property` | 5 | 5 |
| `ternary.alwaysTrue` | 5 | 5 |
| `function.impossibleType` | 4 | 3 |
| `greater.alwaysTrue` | 3 | 3 |
| `booleanNot.alwaysFalse` | 3 | 3 |
| `identical.alwaysFalse` | 2 | 1 |
| `deadCode.unreachable` | 2 | 1 |
| `method.unused` | 1 | 0 |
| `isset.offset` | 1 | 1 |
| `if.alwaysFalse` | 1 | 1 |

The three largest families contain 618 of the original 659 findings: property
coalescing, already-narrowed function arguments and array-offset coalescing.
These require provenance review. A declared WordPress type, an internal array,
and a filterable third-party value do not justify the same edit. Do not remove
defensive guards in bulk or globally ignore a rule family.

## Files

The largest remaining owners are below; the companion JSON includes every file
and rule count, combining trait diagnostics with their class-context entries.

| File under includes/ | Remaining |
| --- | ---: |
| `Packages/Read_Traits/Post_Primitives_Read_Methods.php` | 76 |
| `Packages/Core_Write_Package.php` | 69 |
| `Packages/Read_Traits/Diagnostics_Read_Methods.php` | 51 |
| `Packages/Read_Traits/Page_Pattern_Read_Methods.php` | 36 |
| `Packages/Read_Traits/Page_Read_Methods.php` | 35 |
| `Packages/Read_Traits/Block_Theme_Read_Methods.php` | 32 |
| `Packages/Write_Traits/Cloud_Media_Write_Methods.php` | 31 |
| `Packages/Read_Traits/Content_Refresh_SEO_Read_Methods.php` | 28 |
| `Packages/Read_Traits/Taxonomy_Read_Methods.php` | 27 |
| `Packages/Core_Read_Package.php` | 24 |
| `Packages/Read_Traits/Content_Inventory_Read_Methods.php` | 24 |
| `Packages/Read_Traits/Media_Read_Methods.php` | 24 |
| `Packages/Core_Comment_Package.php` | 23 |
| `Packages/Read_Traits/Article_Optimization_Read_Methods.php` | 16 |
| `Packages/Read_Traits/Comment_Read_Methods.php` | 16 |

## Completed batch: proven local invariants

- Autoloader: a successful namespace-prefix match makes the subsequent substring
  a string. Remove the impossible `false` comparison while retaining empty-name
  and dot/path rejection.
- Media list: `meta_query` has not been initialized before the first optional ALT
  clause. Initialize it directly; retain merging for the later optional clause.
- Site style baseline: every extractor branch returns an array, including
  validated cached values and the empty profile. Remove the unreachable fallback
  and redundant array test; retain guards at transient/WordPress boundaries.
- Remove the unused private `write_media_file` wrapper. Production uses
  `write_cloud_media_file_exclusive`, which retains uploads containment,
  failure injection and exclusive file creation. Retarget the existing
  out-of-uploads reflection test and source assertion to that active method.

These changes remove six diagnostics, not six proven runtime defects. The
numeric ceiling moves from 675 to 653: 16 were already absent before this batch,
and six were removed here. No new suppression, broad cast or PHPDoc override
was introduced.

## Retained findings and next batches

- `Media_Backup_Write_Methods`: retain the file-deletion failure check. A filter
  can veto deletion and filesystem state can change between checks; an analyzer
  that treats repeated `is_file` results as stable does not prove deletion.
- `Diagnostics_Read_Methods`: retain string-valued `WP_DEBUG_LOG` support.
  WordPress stub/config constant narrowing to boolean is not the runtime
  contract; assess a focused constant-type model before changing this branch.
- Core/content-refresh `fields=ids` post queries: inspect host filters and the
  isolated-runtime fallback before removing object handling.
- Comment count fallback: inspect the supported isolated implementation and
  filter output before trusting the unreachable-array diagnosis from stubs.
- Work through internal, locally constructed arrays first. Treat externally
  supplied/filterable values and filesystem/WordPress stubs as separate batches,
  each with runtime evidence and changed-file ratchet checks.

The next batch should begin with one file/rule combination from the ownership
list, prove its value provenance, and run existing behavioral tests. This is
not authorization to delete all remaining defensive code or raise the baseline.

[Machine-readable classification](evidence/phpstan-level4-debt-2026-11-02.json)
records before/after totals and ownership. Full reports remain local under
`/tmp/toolkit-level4-before-full.json` and `/tmp/toolkit-level4-after.json`.

## Follow-up batch: locally constructed taxonomy candidates

`suggest_post_taxonomy_terms` initializes its own candidate list and creates
every row with a non-null string `taxonomy` key. Its category/tag filters run
immediately after sorting; no filter, cache read or external callback can
replace those rows between construction and use. Access that required key
directly in the two filters. Retain the guards on queried term objects,
related evidence and match-profile values.

The first-batch table/JSON above remains the 659-to-653 classification snapshot.
This follow-up removes two `nullCoalesce.offset` diagnostics in one file:
Taxonomy_Read_Methods goes from 27 to 25 findings, the complete inventory from
653 to 651, and the numeric ceiling from 653 to 651. It changes no public
schema, candidate ranking, permissions or write posture and adds no ignore,
type override or cast. Existing taxonomy suggestion and review-set behavior
checks cover the affected path; no implementation-mirroring test was added.

## Follow-up batch: local dashboard and calendar status counts — 2026-11-03

Both `get_site_operations_dashboard` and `get_publishing_calendar_context`
initialize a local `status_counts` array and populate it by iterating a fixed
literal status list. Every `draft` and `pending` key is assigned an integer,
including when an external query has no total. No filter or callback receives
this array before its summary reads. Remove the six redundant `?? 0` defaults
on those required keys. Keep the fallback on external query totals and all
post-object, input, and capability guards.

The complete level-4 inventory decreases from 651 to 645 and the numeric ceiling
follows it. These six diagnostics are local array-provenance findings, not six
runtime defects. Existing dashboard/calendar behavioral checks and the
permission-filtered calendar checks cover output and access behavior; no new
ignore, type assertion, query, runtime authority or public schema is introduced.
The earlier classification table and JSON remain the dated 659-to-653 snapshot.

## Follow-up batch: local Gutenberg catalog arrays — 2026-11-03

After `composer install` (no dependency changes), PHPStan 2.3.4 reproduced the
645 diagnostics at `fdf0a2e`; the complete JSON report had zero analyzer errors.
The [current classification](evidence/phpstan-level4-debt-2026-11-03.json)
refreshes every file/rule count and combines trait class-context entries.

In `Gutenberg_Block_Capability_Catalog_Read_Methods`, all five composer profiles
are local array literals containing a non-null string `surface`. No filter,
cache or external callback replaces them before `array_filter`. Remove its
redundant `is_array` and missing-field fallback. The inspection helper also
initializes `surface_kind` and `post_type` in its initial literal and in both
replacement literals; local updates never remove them. Read those required
keys in surface selection, retaining the existing string casts because
`sanitize_key` values are filterable. Even a null value still becomes the same
empty string as the former `?? ''` expression. Values from input, post objects
and parser output retain their existing sanitization and guards.

This removes five redundant local-invariant findings: four `nullCoalesce.offset`
and one `function.alreadyNarrowedType`. The file goes from 12 to 7; total and
ceiling go from 645 to **640**. No runtime defect is established by this batch.
The remaining file findings are five WordPress post-property defaults and two
parser-return array checks. Retain them pending separate boundary/model review.
The other 633 findings are inventoried, not adjudicated as defects or safe edits.

| Rule family | Before | After | Next provenance decision |
| --- | ---: | ---: | --- |
| `nullCoalesce.property` | 259 | 259 | Object/stub guarantees versus supported runtime objects |
| `function.alreadyNarrowedType` | 245 | 244 | Internal results versus external/filter/cache/parser returns |
| `nullCoalesce.offset` | 104 | 100 | Required local fields versus filterable or third-party shapes |
| All other rules | 37 | 37 | Branch-specific control flow, constants and filesystem behavior |

The existing catalog/composer, inspection and permission behavior checks are
reused. No ignore, PHPDoc override, new cast, query, public schema or
write authority is introduced. Full reports stay in ignored
`build/maintenance-2026-11-03/`; hashes and focused provenance remain in the
tracked classification. See the [stage record](archive/phpstan-and-local-git-maintenance-2026-11-03.md)
for preservation and historical Git exceptions.

## Follow-up batch: local comment priority scores — 2026-11-03

At `bf11b3840891d1eca18b99cf2c9675d6a173342b`, a fresh `composer install`
made no dependency changes and PHPStan 2.3.4 reproduced **640** diagnostics
with zero analyzer errors over all configured paths. The new batch is recorded
in `follow_up_batches` in the [classification JSON](evidence/phpstan-level4-debt-2026-11-03.json);
its root before/after fields remain the earlier Gutenberg snapshot.

`Core_Comment_Package::get_comment_action_priority_queue` initializes `items`
locally and appends only literal rows with `priority_score`. The score starts
with `(int) round($confidence * 50)` and changes only through integer additions.
No filter, cache or external callback receives those rows before `usort`.
Remove the two `?? 0` defaults in its comparator, retaining the original integer
casts and every normalization step. These are redundant local defaults, not
two confirmed runtime bugs or a claim about external comment objects.

The file decreases **23 → 21**, repository total and ceiling **640 → 638**,
and `nullCoalesce.offset` **100 → 98**. Every other file and rule count stays
unchanged. All 21 remaining findings in this file are deferred: filter-return
checks, queried comment array/object checks and WordPress property defaults.
They remain unchanged; this batch does not establish that they are removable.
The previous seven reviewed Gutenberg boundary findings remain; **631** other
remaining diagnostics still await semantic adjudication.

Existing comment queue sorting/count/success and compliance-handoff checks are
reused. No new ignore, PHPDoc, conversion, public contract or runtime authority
is introduced. Stop after this batch; a later batch can examine locally built
content-refresh topic counts with its own provenance decision. See the
[batch record](archive/phpstan-comment-priority-invariants-2026-11-03.md) for
scope, evidence and limitations.

## Follow-up batch: local topic coverage counts — 2026-11-03

Fresh analysis at `1ecb3be118bfae7b2b8d334e17c4fa61ce8129e0`, after a
no-change `composer install`, reproduces 638 diagnostics with PHPStan 2.3.4
and zero analyzer errors. The next `follow_up_batches` entry in the existing
[classification JSON](evidence/phpstan-level4-debt-2026-11-03.json) records this
batch separately from the earlier snapshots.

In `get_site_topic_coverage_report`, every `topic_map` row is a local literal
with `topic`, `count` and `post_ids`. `count` starts at zero and is incremented
before use. `array_values`, sorting and `array_slice` preserve these fields;
the local map/rows never escape to a filter, cache or external callback before
the affected reads. Remove four missing-field defaults: two comparator counts,
the gap-test count and the gap topic. Preserve existing integer/string casts,
term sanitization, input/query/object guards, permissions and scan bounds.

The trait decreases **28 → 24**, total and ceiling **638 → 634**, and
`nullCoalesce.offset` **98 → 94**; all other file/rule counts stay unchanged.
These are four redundant local defaults, not four confirmed runtime bugs.
The other 24 trait findings remain deferred, including WordPress properties,
query/helper return checks, isolated-runtime object fallback and regex matches.
Seven previously reviewed boundary findings remain; **627** other diagnostics
await semantic adjudication. No ignore, PHPDoc or conversion is added.

Existing topic-coverage fixtures are reused, with their full baseline/head
outputs compared for descending counts and one-post coverage gaps. A complete
tracked baseline and full configured-path committed reports supplement the
trait-only changed-file ratchet; 0/0 alone is not class-context evidence.
See the [batch record](archive/phpstan-topic-coverage-invariants-2026-11-03.md)
for rationale and verification limits. Stop at this batch; the same file's
local refresh-priority scores are a candidate for a later provenance review.

## Grouped batch: internally owned result shapes — 2026-11-03

The [source-group inventory](evidence/phpstan-level4-source-groups-2026-11-03.json)
routes every one of the 634 baseline diagnostics to a producer/call-chain queue,
preserving file, line, rule, duplicate findings and class context. Routing is
not semantic adjudication. Its ten proof cases establish the 32 edits in this
batch; all other rows retain their pending or previously reviewed status.

| Investigation queue | Baseline | After |
| --- | ---: | ---: |
| Proved internal shapes (this batch) | 32 | 0 |
| Previously reviewed Gutenberg boundaries | 7 | 7 |
| WordPress object properties | 259 | 259 |
| Query/object producer chains | 76 | 76 |
| Block/parser normalization chains | 46 | 46 |
| Media/filesystem transaction chains | 51 | 51 |
| Runtime filter/registry chains | 65 | 65 |
| Regex/native results | 9 | 9 |
| Helper/control-flow provenance still unresolved | 89 | 89 |

One PR combines required fields from the private content normalizer, the shared
taxonomy resolver after WP_Error exits, immediately assigned stats, and locally
constructed report rows. Keep original casts and sanitizers: field presence
does not establish reliability of filterable values. External inputs, term
creation results, query objects, caches, parser returns and filesystem defenses
are unchanged. No suppressions, PHPDoc, conversions or authority are added.

Complete reports show **634 → 602**, zero analyzer errors: 26 offset defaults
and six redundant array predicates disappear across eight files. All other
messages are identical; ceiling becomes 602. Seven previously reviewed findings
remain; 595 still need semantic decisions. Existing behavior gates and 14
baseline/head fixture data comparisons cover the edits. See the concise
[batch evidence](archive/phpstan-grouped-invariants-2026-11-03.md).

Continue by shared producer, starting with the 89 unresolved helper/control-flow
locations; inspect the producer once, verify each caller, and combine compatible
edits before running PR gates. WordPress/filter/parser/file queues need their
own model/boundary decisions and do not carry a numeric removal target.

## Grouped batch: helper and local control-flow invariants — 2026-11-03

A fresh Composer-synchronized full report at
`c1756b8d3ca9370abd24abf6ca5c6c09364c2c8b` reproduces 602 findings with zero
analyzer errors. The next source-inventory entry proves **23** redundant
findings: locally constructed menu/template/article/media rows, already
normalized helper results, local insertion maps, nonempty keyword/length
invariants and metadata history validated before local iteration.

Total and ceiling become **579**; the unresolved helper queue decreases
89 → 65 (66 diagnostics remain in that group, including the retained mode
default). Seven previously reviewed boundary findings and one newly reviewed
filterable mode default remain; 571 findings still need semantic adjudication. No runtime defect is established. All other
diagnostic messages are unchanged after accounting for source line shifts.
External input/filter/cache/upload/image/filesystem defenses, field defaults
where values remain mixed, casts, sanitizers and governance checks remain.

Existing source gates and 32 fixture outputs are reused: 25 match directly;
seven require explicitly recorded clock/PID normalization. Complete committed
base/head reports cover class contexts even where selected-trait analysis has
no findings. See the [batch record](archive/phpstan-helper-invariants-2026-11-03.md)
for verification limits. Continue by producer; the remaining helper queue is
not a safe-deletion list and has no numeric removal target.

## Complete remaining-inventory adjudication — 2026-11-03

At `72e308c7fffe09c1b0248d8e2cb358f01af64c64`, synchronized Composer tooling
reproduces **579** diagnostics over all configured paths with PHP 8.4.21 /
PHPStan 2.3.4 and zero analyzer errors. The 579-to-526 change is explained by
49 proven internal/native/control-flow simplifications and four accurate
parameter-type corrections; no runtime defect is established. Across PRs
#289–#292 and this continuation, the original 640 inventory falls by 114.
The total ceiling follows the measured result, **526**, without suppression,
configuration expansion/contraction or dependency changes.

| Disposition of the 579 input occurrences | Count |
| --- | ---: |
| Simplified proven local/native/control-flow invariants | 49 |
| Corrected inaccurate parameter models; runtime guards retained | 4 |
| Retained WordPress/input/decoder/compatibility defenses | 374 |
| Retained filter/stub/isolated-runtime/filesystem model discrepancies | 118 |
| Accepted uniform recursive traversal defenses | 24 |
| Accepted individually reviewed redundancy at bounded interfaces | 10 |

Every occurrence, including same-line duplicate rules and trait class context,
has its base location, expression, enclosing function and explicit decision in
54 proof groups. The immutable source/tree and per-file hashes bind the ledger;
this is semantic disposition, not relabeling the older 634 source-routing list.
All 526 surviving diagnostics remain visible. An accepted redundant guard is
not asserted necessary on every current path, and retention is not a claim
that a malformed production object was observed.

Simplifications cover private category/catalog storage; validated SEO/download
helper results; local media-reference pairs, payloads and assigned fingerprints;
filtered history/inline block rows; mandatory successful regex captures; style
outer arrays and enum count-map keys; URL branch invariants; native extension,
glob and DateTime error shapes. Existing casts, trims and sanitizers remain.
Correct the PHPDoc of public checksum/timestamp validators and block-error
initialization, plus raw media-asset elements, to reflect the arbitrary input
they already validate. No runtime validation is deleted to make those types fit.

Retain mutable WordPress fields, raw query/cache/filter/registry/parser/media
returns, optional alpha capture handling, unguarded regex failure defaults,
image fact checks, compatibility getters, transactional snapshot checks,
post-delete and repeated readability observations. Keep string WP_DEBUG_LOG,
fields=ids object fallback and isolated comment-array count behavior. Keep the
filterable action mode and term slug/name defaults: required keys do not prove
non-null sanitized values. Some traversal and aggregation checks are knowingly
redundant for normalized current producers; accepting that bounded defense
avoids an unrelated traversal rewrite merely for a smaller number.

Existing source gates and 17191 main assertions pass, along with flagged-media,
taxonomy failure, audit and rename compensation checks. Ordinary level 3 is
zero. Forty-one existing fixture data/error outputs compare equivalently;
38 match exactly and three differ only in 13 individually verified fixture PID
fields. Outer transport/cache metadata is excluded. Full committed reports use
a complete tracked baseline with independent Composer installation and exact
source-line mapping; changed-file ratchet alone is insufficient for traits.
Final committed ratchets, AI triage, required CI and protected merge receipts
belong to the PR and ignored evidence directory. See the
[complete adjudication record](archive/phpstan-complete-adjudication-2026-11-03.md).

The current inventory has **zero undecided dispositions**, with **526 accepted
retained diagnostics**. This closes the requested classification pass without
promising numeric zero, production validation or release readiness. Reopen
specific decisions for changed producers, supported runtimes, stub versions or
observed behavior; the ledger records those triggers. A future cleanup should
select one such trigger, rather than delete accepted defenses for a count target.
