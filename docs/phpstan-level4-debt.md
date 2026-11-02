# PHPStan level-4 debt classification — 2026-11-02

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
