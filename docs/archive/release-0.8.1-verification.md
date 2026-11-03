# 0.8.1 Release Candidate Verification

Status: preparation candidate; not tagged or published.
Date: 2026-11-03.

## Change Envelope

Toolkit patch metadata for the already merged Quiet admin/locales (#271,
#273, #276-#278), preflight omission (#280), measured media-query evidence
(#281), proven-invariant PHPStan batches (#282, #283, #285), and Chinese slug
translation (#284). Expected files: plugin header/version constant, readme,
changelog and this note. No new runtime behavior in this metadata commit.

Retain ability ids, schemas, permissions, dry-run and host approval ownership.
No database migration. Do not retag 0.8.0, deploy to production, claim third-party
adoption, force-delete branches, or commit SVN during preparation.
Rollback before publication: revert the release metadata commit. After any
publication, use a new version rather than replacing historical artifacts.

## Included Assets And Runtime Proof

All four WordPress.org exports are 1366x920 and already match the preserved
screenshot candidates byte for byte. No duplicate screenshot changes are
needed. The admin source since #278 has no further layout change in this patch;
readme screenshot captions still describe Overview, Catalog, Checks and
Connection. Before publication, confirm these images against the final source.

The Local operator opted to retain the current preflight report and accumulate
repeat-use data. A normal TLS curl client ran the same previously authorized
read-only draft preflight three times via Adapter/Core: HTTP 200, complete five
sections, unchanged semantic results, no body leakage, corrected Chinese status,
no old smoke recommendation targets. Temporary application credentials were
revoked. Article, metadata and terms were unchanged. Two source-matched legacy
smoke fixtures were backed up and reversibly moved to trash; unrelated content
was preserved. These are agent-operated Local samples, not measured human time
saving or third-party sustained usage. No content/SEO change or publication.

## Required Candidate Gates

The clean candidate must pass test:all, ordinary PHPStan, global level-four
ratchet (645), diff integrity, explicit boundary and WordPress.org guards,
revision-bound minimum/current WordPress smoke, mounted Local full/light smoke,
and packaged Plugin Check through release:verify. Local Docker is unavailable;
use the established M4 runner and exact-HEAD evidence, never stale receipts.

Run the central Toolkit/Core/Adapter gate matrix and record exact local sibling
revisions and any upstream lag. Review the final committed diff with the active
configured local model, triage every finding, and use the repository publisher.
All required CI must pass on the exact PR head before protected merge.

Keep raw receipts, gate logs, matrix, source SHA, screenshot hashes, ZIP checksum
and SVN preparation inventory in the task's ignored build evidence directory
and completed PR description. After merge, build from clean master; verify its
package tree matches the reviewed candidate and bind any new runtime evidence
to the actual final source. This note alone does not assert later gate success.

## Publication Boundary

Candidate preparation does not create Git tags, GitHub releases or SVN commits.
Use only a fresh, verified SVN working copy for staging trunk, assets and the
new 0.8.1 tag directory. Record source/package/asset hashes before a maintainer
publishes. WordPress.org credentials must be entered by the maintainer in their
terminal. Preserve existing tags and confirm the final remote/public package
separately after any future publication.
