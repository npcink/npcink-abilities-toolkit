# Write-Package Structural Slices Closeout

Date: 2026-09-30.

## Change Envelope

- Target repository: `npcink-abilities-toolkit`.
- Focused module: `includes/Packages/Core_Write_Package.php` decomposition,
  write-path correctness, and the surrounding verification assets.
- Intended change: resume the paused structural split plan with
  evidence-triggered write-side slices, repair the defects surfaced by the
  advisory review, realign drifted indentation, ratchet PHPStan, and dispose of
  the superseded analysis branch.
- Explicit non-goals: no ability id, schema, annotation, callback, permission,
  dry-run default, or final authorization changes; no definitions move; no
  `tests/run.php` restructuring; no workflow runtime ownership.
- Public contracts touched: none.
- Landed pull requests: #161 (media backup trait), #162 (`wp_delete_file`
  void-semantics fix), #163 (cloud media transaction trait), #164 (indentation
  realignment), #165 (PHPStan level 3).
- Required gates run per pull request: `composer test:all`,
  `composer analyse:phpstan`, `composer check:boundary`, `git diff --check`,
  plus the advisory `ocr review` and required GitHub checks.
- Cross-repository matrix: not required; no consumed surface changed.
- Rollback: each pull request is a single squash commit, individually
  revertible.

## What Landed

- `Core_Write_Package.php` shrank from 9,263 to 6,588 lines across two
  byte-identical slice extractions: `Media_Backup_Write_Methods` (24 methods —
  retention cleanup, restore transactions, replacement lineage; evidence:
  PRs #107/#110/#113/#114 repeatedly crossed that responsibility after the
  pause) and `Cloud_Media_Write_Methods` (38 methods — derivative adoption,
  exclusive cloud writes, compare-and-swap mutations with locked-row rollback;
  evidence: five merged PRs crossed the machinery plus maintainer
  authorization).
- A real production defect was fixed: two write paths treated void-returning
  `wp_delete_file()` as a boolean. In production every `rename-media-file`
  commit failed after copying and discarded the copied target, and backup
  expiry history lagged one cycle. The unit-test stub now returns void like
  WordPress core, so the suite proves the fixed paths under core-faithful
  semantics.
- Long-standing indentation drift (18 whole methods in the class plus three
  spots in the backup trait) was realigned as a pure whitespace change.
- The analysis level moved from PHPStan 2 to 3; level 3 surfaced exactly one
  finding (a by-ref breadcrumbs type), fixed with one `@var`.
- The superseded `codex/fix-phpstan-memory` branch was verified against master
  (identical memory-limit line already landed via #109; older dependency
  versions) and deleted locally and remotely.

The structural sequence is back to paused per `structural-split-plan.md`.
Remote intake (upload pipeline) and all non-media clusters remain in the class
and need independent change evidence before any further extraction.

## Durable Lessons

1. **The paused plan governs resumption.** File size alone is not a resume
   trigger. Each slice needs named pull-request evidence or maintainer
   authorization, lands as one focused PR, and returns to stabilization.
   `tests/run.php` pins method ownership per slice; extend those assertions
   with the moved-method pattern (the class must not duplicate the method, the
   trait must own it) and widen cross-file guarantee checks to the combined
   sources instead of weakening them.
2. **Test stubs must be core-faithful.** A bool-returning `wp_delete_file()`
   stub masked a production defect on a registered ability. When a core
   function returns void, the stub must too. The counter-evidence technique is
   cheap and worth recording: revert the source fix, keep the faithful stub,
   and watch the suite fail — then restore.
3. **Prove mechanical moves by reconstruction, not by reading diffs.** Rebuild
   the post-move file as `HEAD` minus the exact method ranges plus the one
   `use` line and byte-compare. Large git diffs can be misleading: relocating a
   misindented region between two removals renders as delete-plus-readd pairs
   that look like new content.
4. **Prove whitespace-only changes two ways.** `git diff -w` empty proves no
   non-whitespace byte changed; PHP's tokenizer (no multi-line string literal
   tokens in the file) proves a removed leading tab cannot alter string
   content. Run the full gate set anyway.
5. **The advisory reviewer times out deterministically on large new files**
   (observed on ~1,050-1,650-line trait files across three reviews; all other
   requests completed). For byte-identical moves with a reconstruction proof
   this is acceptable — record it in the pull request body rather than
   splitting sound code to please the tool. The CI advisory check still runs.
6. **Dispose of superseded branches on content, not ancestry.** After squash
   merges, `git branch -d` refuses; verify with `git cherry` (patch already
   upstream) or a diff against master showing only version-forward movement
   before using `-D`, and fetch first.
7. **Ratchet static analysis only when it pays.** One level at a time; if the
   next level's findings are more than a handful, record the level as the
   ceiling instead of paying down everything at once.
