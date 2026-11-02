# 0.8.0 Release Verification

Status: published and public package verified.
Date: 2026-11-02.

## Change Envelope

- Repository: `npcink-abilities-toolkit`; release metadata and verification for
  the already merged integration-profile and contract-hardening work.
- Expected files: plugin header/constant, `readme.txt`, `CHANGELOG.md`, this note;
  the proved parameterless REST read fix, reference provider, focused real
  WordPress regression and integration guides.
- Public contracts: opt-in registration profiles retain the full default;
  commit decisions require literal boolean `true`; existing ids and schemas
  remain stable. Core or the consuming host keeps final authorization truth.
- Non-goals: no new abilities, workflow runtime, approval store, product UI,
  external adoption claim, or inclusion of the separately merged UI PR #271.
- Gates: source suite, PHPStan, level-4 ratchets, boundary/wporg guards,
  revision-bound minimum/current WordPress smoke, mounted candidate Local
  smoke and packaged Plugin Check through `composer release:verify`.
- Matrix: central Toolkit/Core/Adapter gates with the Toolkit path explicitly
  pointing at the release candidate; sibling state reported independently.
- Rollback: revert before publication; after publication use a new version.
  Historical tags and SVN releases are immutable.

## Scope

This minor release adds the opt-in `core_read` and `read_only` registration
profiles and tightens the documented host boolean decision. It includes the
merged taxonomy approval/failure, media cleanup/rename, diagnostics redaction,
post-content round-trip, scheduling, schema-key, pagination, cache invalidation,
Plugin Check and deterministic package fixes recorded in `CHANGELOG.md`.

Independent HTTPS acceptance found that a real GET without `input` supplies
`null`, while `site-info` and the parameterless reference reads require an
object. Their input schemas now declare an empty default. Previously the
in-process proof supplied an explicit empty array and hid the failure. The
real WordPress proof now also executes those reads with no request input.

No database migration is required. Hosts must return boolean `true` rather than
truthy strings, integers or error objects. Profiles must be selected before
registration and only narrow built-in packages; third-party providers remain
responsible for their own callbacks and permissions.

## Verification And Publication

- Protected source PR: [#272](https://github.com/npcink/npcink-abilities-toolkit/pull/272).
  All required CI checks passed; merge and immutable Git tag `0.8.0` resolve to
  `22fe6de45453d1365779bc603c1cee202678187b`.
- Candidate `89f9bfe7510294f493771557f9fa92bae60bbf6f` and merged source have
  the same tree `1c67ef0a182c23b350c7effb64cc67d2578afb4d`. Both passed
  `composer release:verify`; the merged source also passed explicit
  `composer check:boundary` and `composer check:wporg` before tagging.
- Source suite: 17,169 main assertions in the merged-source gate; PHPStan 2.3.4 clean; changed-file
  level-4 ratchet 0 -> 0 for the parameterless read fix. Boundary, WordPress.org,
  total level-4 ratchet and packaged Plugin Check passed.
- Exact-revision M4 smoke: WordPress 6.9.4/PHP 8.0 and WordPress 7.0/PHP 8.5;
  independent provider/host proofs passed 31/151/43 assertions in full,
  read-only and core-read profiles on each stack. The Local site on WordPress
  7.1.4 passed 479 full and 58 light-profile smoke assertions; packaged Plugin Check reported no errors.
  The original development plugin mount was restored.
- Central quality matrix passed the checked Toolkit/Core/Adapter source gates.
  The Core checkout was one commit behind its remote; this is evidence for
  those checked revisions, not a platform-wide readiness or current-head claim.
- Local AI review: `qwen3.8-max`, final candidate receipt SHA-256
  `9033eae9dd0320d867f477325d7a512d98a3f2965fd58d406901121aed1b0005`.
  PR #272 records both accepted findings and exact-diff verification.
- WordPress.org SVN commit: `3779027`, using the existing verified maintainer
  Keychain cache noninteractively. Freshness had no out-of-date paths;
  changes were limited to trunk and the new `tags/0.8.0`. No passwords or application secrets
  entered chat, command arguments, source or logs.
- Git release ZIP SHA-256:
  `d1ebb04f652cc62c36f6af80fe0fb53a543a333d0335310c49a293c31b91cc4c`.
  Prepared trunk, prepared tag, exported remote tag at revision 3779027 and
  public versioned download each match all 96 package files byte for byte.
  Archive container metadata may differ; the file contents are identical.
- The public plugin-information API reports `0.8.0` and its versioned download.
  [GitHub release](https://github.com/npcink/npcink-abilities-toolkit/releases/tag/0.8.0)
  and [WordPress.org](https://wordpress.org/plugins/npcink-abilities-toolkit/)
  are published. Historical tags were not changed.

## Independent HTTPS Acceptance

The maintainer selected the existing Local WordPress site plus an independent
HTTP client. On `https://magick-ai.local`, curl used normal certificate
verification and real WordPress application passwords, without authentication
bypass. The final candidate passed 16 checks: authenticated actor/version,
paged discovery, independent provider and built-in reads, anonymous 401,
subscriber 403, invalid-input 400, reference proposal, built-in dry-run, and a
direct commit rejected for missing host approval. No proof content was created.
Temporary users, application passwords and the reference-provider MU loader
were removed; the normal plugin mount and existing MU plugins were preserved.

The omitted-input GET defect was discovered through this HTTP path and fixed
before publication. This proves external calling and denial boundaries for the
reference integration. It does not prove third-party product adoption, a
production approval UI, or durable approval/replay storage. Approved writes and
single-use replay are separately exercised by the isolated WordPress host proof.

Sanitized receipts and complete local logs are retained under the main
checkout's ignored `build/closeout-2026-11-02/`, including the release gates,
M4 evidence log, HTTPS receipt, matrix, Qwen review and SVN transaction.

## Post-Release Admin Follow-Up

UI PR #271 merged after the release source and is excluded from tag `0.8.0`.
Its source checks pass. Browser acceptance of the merged UI plus the focused
rendering extraction covered overview, read-only on/off with the original
option restored, successful Site Info, clipboard endpoint, HTTP 200 discovery,
and risk filtering (182 total -> 142 read abilities). The browser logged zero
errors, and its temporary administrator and sessions were deleted. Screenshots
are retained locally under `build/admin-proof/` in the main checkout.

The extraction reduces `Test_Page` from 1,518 to 1,346 lines and
`Read_Only_Mode` from 141 to 120, below their pre-UI ceilings. The structure
plan records why this slice was justified; other refactors stay conditional.
Refresh WordPress.org screenshots before a later release that includes this UI.
