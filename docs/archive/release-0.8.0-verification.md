# 0.8.0 Release Verification

Status: candidate; publication is not yet established.
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
  external adoption claim, or inclusion of the pending UI PR #271.
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

Candidate and immutable merged-revision gate results will be recorded here or
in the linked release PR before publication. A source merge alone does not
establish WordPress.org publication. The WordPress.org SVN and public package
must be checked against the exact verified Git source.

The site-owned UI branch and its local merge into a separate master worktree
are excluded. The release branch starts from protected `origin/master`.

## Remaining Work

- Complete revision-bound release gates and local Qwen review.
- Merge through protected CI; tag the verified merged revision.
- Prepare and inspect SVN trunk/new tag, publish with existing Keychain
  credentials if available, then compare remote/public package bytes.
- Complete the pending UI PR separately, including its failing source gates,
  body contract and visual acceptance; refresh storefront screenshots before
  any subsequent release carrying that UI.
