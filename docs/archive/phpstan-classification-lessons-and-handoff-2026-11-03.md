# PHPStan Classification Lessons And Handoff — 2026-11-03

Historical source snapshot: `58316974edc84185631ffe3c29da8e352f018851`
(PR #293). Recheck live Git, dependencies and analysis before acting. This note
consolidates the discussion and delivery evidence for other AI sessions; active
rules remain in [AGENTS.md](../../AGENTS.md) and the
[audit standard](../system-audit-and-closeout-standard.md).

## Outcome And Meaning

The requested work began as one bounded batch, with no promise to clear level 4.
After the maintainer asked for better throughput and continuous execution,
related findings were grouped by their actual value producers. Investigation,
verification and publication continued without asking agreement after each
batch. This did not authorize broad guard removal or numerical zero.

| Merged PR | Full configured-path diagnostics | Squash merge |
| --- | --- | --- |
| [#289](https://github.com/npcink/npcink-abilities-toolkit/pull/289) | 640 → 638 | `1ecb3be118bfae7b2b8d334e17c4fa61ce8129e0` |
| [#290](https://github.com/npcink/npcink-abilities-toolkit/pull/290) | 638 → 634 | `a2305c9e307c6b1b65c59919d354cfa7bd2814b3` |
| [#291](https://github.com/npcink/npcink-abilities-toolkit/pull/291) | 634 → 602 | `c1756b8d3ca9370abd24abf6ca5c6c09364c2c8b` |
| [#292](https://github.com/npcink/npcink-abilities-toolkit/pull/292) | 602 → 579 | `72e308c7fffe09c1b0248d8e2cb358f01af64c64` |
| [#293](https://github.com/npcink/npcink-abilities-toolkit/pull/293) | 579 → 526 | `58316974edc84185631ffe3c29da8e352f018851` |

Net reduction: 114. The final batch simplified 49 proven local/native/control-flow
occurrences and corrected four parameter models while retaining runtime checks.
No new runtime bug was established. The measured total ceiling is 526; no broad
ignore, analyzer scope change or dependency change was used to lower the count.

All 579 final-batch input occurrences have explicit revision-bound decisions:
53 removed, 374 retained boundary defenses, 118 retained model/runtime
discrepancies, 24 accepted uniform recursive checks and ten accepted redundant
checks. Thus 526 remain visible and zero dispositions are undecided. These are
diagnostic occurrences, including duplicate class contexts, not 526 known bugs
or a claim that production has no bugs. The
[classification history](../phpstan-level4-debt.md),
[per-location ledger](../evidence/phpstan-level4-adjudication-2026-11-03.json) and
[final-batch reasoning](phpstan-complete-adjudication-2026-11-03.md) retain detail.

## Reusable Development Lessons

- Improve throughput by proving a shared producer once and mapping every affected
  consumer/occurrence to that proof. Keep semantic groups bounded; a routing
  inventory alone is not a semantic decision ledger. Avoid repeating an entire
  publication cycle for every trivial check when related proofs can be reviewed
  together without weakening gates.
- Trace real constructors, private writers, filters, cache reads, helper return
  statements and intervening mutation. Public visibility or an array PHPDoc
  alone does not establish reliable inner values. Key presence does not justify
  deleting a cast, trim, sanitizer or other normalization with runtime meaning.
- Retain mutable WordPress object defaults, external/filter/cache/parser/media
  validation, filesystem re-observation and isolated-runtime compatibility.
  Preserve permission, approval, dry-run, bounds and transaction compensation.
  Some accepted guards are deliberately redundant for current normalized paths;
  do not invent a claim that every retained guard is always necessary.
- Synchronize Composer first. Require analysis completion, actual configured
  paths and zero analyzer/global errors; explain count drift before editing.
  A selected trait's 0/0 is insufficient: compare full configured paths at exact
  committed base/head. Build fresh baselines from the complete tracked tree
  (read-tree/checkout-index), since release export-ignore can omit tools/tests.
- Reuse behavior fixtures and compare meaningful data/error fields. Document
  each time/PID normalization and excluded transport metadata; never normalize
  business/security differences away or claim new real-site acceptance.
- Review the final committed diff through the active central local AI policy.
  Check every finding against source and record fix/accept reasoning; model
  output is not automatic approval. A failed or oversized request is not a
  passed review, and rerunning to obtain fewer findings is not triage.
- Separate classification completion, code delivery and Git cleanup. Stop
  numerical cleanup when further edits would require unrelated model/runtime
  redesign. Reopen specific decisions for producer/API/runtime/stub changes or
  observed failures; no further count-only batch is required.
- Continue already authorized work to its reviewable result and consolidate the
  final report. Brief progress updates can report a meaningful change; repeated
  agreement requests after each batch add delay without evidence or safety.

## Verification And Limits

Final source: PHP 8.4.21 / PHPStan 2.3.4; Composer synchronized. Source gates pass
with 17,191 main assertions plus existing media, taxonomy, audit and compensation
checks; ordinary level 3 has zero errors. Committed changed-file ratchet:
132 → 93. Full configured-path comparison: 579 → 526, exactly 53 removals,
526 identical surviving messages through exact equal-source-line mapping,
no file growth and zero analyzer errors. Total ratchet: 526 ≤ 526.
Forty-one existing fixture outputs: 38 exact, three equivalent after 13
individually verified PID path fields. Diff checks pass.

Final candidate `49b73dc3538f67228b217e9ffed19dd247ce0eaf` and merged source
have the same tree `b6d42680a44f6f419e511cf5e435168597574ca2`.
Locally initiated DeepSeek review returned 30 findings, each accepted with
source evidence and exact-diff triage verified. All five required PR checks
passed before protected squash auto-merge; no bypass or direct master push.

No release/tag/SVN, real article/SEO change, new production or human repeat-use
validation, other repository/chat edit or application-attachment cleanup was
performed. This handoff does not expand that scope.

## Git Cleanup Handoff

After #293, verified clean master matched origin/master, one main worktree,
remote master only and no open PR. Five local topic heads remained because
`git branch -d` rejected their squash ancestry:

| Local branch | Exact retained head | Merged PR |
| --- | --- | --- |
| `codex/phpstan-comment-priority` | `93949571b61a011d6be50c0469c2aacfa7485d6e` | #289 |
| `codex/phpstan-topic-counts` | `9db6086bdf1e7f7d10c7a99bcfb5ec4e853a34f7` | #290 |
| `codex/phpstan-owned-shapes` | `112d032902d87497f861598ba288dfbd7fbe339f` | #291 |
| `codex/phpstan-helper-invariants` | `0361ebcf4185dadc2d72ac108a129c8ccc0059fd` | #292 |
| `codex/phpstan-complete-triage` | `49b73dc3538f67228b217e9ffed19dd247ce0eaf` | #293 |

`composer closeout:check` exited 1 only for these PR-verified retained heads;
it was not reported green. No force deletion or ref/ancestry rewriting occurred.
The task-created temporary fresh baseline was removed after preserving reports.

Raw reports, model receipt/triage, final closeout, exact PR receipts and deletion
refusals remain in ignored `build/phpstan-complete-triage-2026-11-03/`.
`pre-closeout-all-refs.bundle` was verified, SHA-256
`2f070dc9d4b9747ee9b71dc2c49c3198acb60282e1a75ac16a8685e31384afbe`.
These local artifacts may not exist in a new clone; this tracked summary and
GitHub receipts remain the portable record. Preserve local recovery material.

The maintainer requested a new-session deletion prompt. This historical note
itself grants no force-deletion authorization. The new human-issued prompt must
name exact branch/head pairs and explicitly grant any `git branch -D` exception.
Recheck every head, merged PR, merge ancestry on freshly fetched origin/master,
required checks, worktree ownership and a fresh verified recovery bundle. Try
`-d` first; a changed or unproved head stays intact. Never treat the older
22-branch authorization as covering these refs. The documentation PR delivering
this handoff may add a further topic branch; obtain its exact PR/head receipt
and include it explicitly in the new prompt rather than deleting by wildcard.
