# Changelog

## 0.5.9 - 2026-10-08

User-experience localization and onboarding release; no ability contract, schema, or behavior changes; ability ids and response shapes are identical to 0.5.8.

- Localized the admin workflow scenario cards (#213): the twelve recipe titles and thirty-six natural task examples now render in the site language through the plugin text domain while the recipe payloads (REST discovery, runtime contract projection, replay fixtures) remain untranslated English contract data; the renderer moved from `Test_Page` into the new `Admin\Scenario_Cards` class, shrinking `includes/Admin/Test_Page.php` from 1,452 to 1,409 lines with the structure baseline regenerated, and a guard test reconstructs line-wrapped msgids to fail loudly if a template regeneration ever drops the 48 hand-maintained scenario entries from the pot or any of the seven bundled locales — the hand-maintenance rule is documented in `marketing/translation-notes.md` after the advisory review flagged the make-pot blind spot.
- Added the post-activation welcome notice (#213, `Admin\Welcome_Notice`): a one-time, per-admin success notice on the Plugins and Dashboard screens links to the ability status page, clears itself once the page has been visited or the notice dismissed through the same admin-post + nonce pattern as the health notices, stays silent for activations without an admin session (CLI, network bulk activation), and boots only inside the `admin_test_page` package so it never points at a disabled page. The admin page heading now reads the translated "AI Ability Set", matching the registered menu label.
- Overview attention warnings now link the troubleshooting guide (stable `blob/master` class constant, `noopener noreferrer`; allow-list recorded in the admin surface standard), and the Connection values table explains that the abilities and categories endpoints are public discovery values while the contract endpoint requires a signed-in admin session (it returns an authentication error in a private browser window); catalog filter selects submit on change with the Apply button kept as the no-JS fallback (#213).
- Uninstall now removes the per-admin notice state (`npcink_abilities_toolkit_welcome_pending` and the pre-existing `npcink_abilities_toolkit_health_notices_dismissed`) through `delete_metadata(..., true)` on single and multisite, covered by new uninstall-harness assertions; the readme uninstall FAQ was updated accordingly (#213).
- Backfilled all seven bundled locales (zh_CN, de_DE, es_ES, fr_FR, ja, ko_KR, pt_BR) with the 50 new msgids — 48 scenario strings plus the welcome-notice button and the connection-values note — at full template parity, recompiled the `.mo` files with `msgfmt`, and gave the readme description an operator-facing first paragraph with the installation steps mentioning the welcome notice (#213).
- Documented in `docs/admin-surface-standard.md` (#213) that host products wanting the `Npcink AI -> AI Ability Set` placement must register the shared `npcink-ai` top-level menu below `admin_menu` priority 40, and in `AGENTS.md` (#214) that new `includes/` files should pre-check the CI-only level-4 changed-file ratchet locally after #213 hit the conservation rule with provably-true defensive guards.

## 0.5.8 - 2026-10-07

Maintenance and repository-health release; no ability contract, schema, or behavior changes.

- Added the structure ratchet (`composer check:structure-ratchet`, wired into `composer test:all` and the PHP CI job): every analyzed source file under `includes/` plus the bootstrap must stay at or below its recorded peak line count in `tests/fixtures/structure-ratchet-baseline.json` (new files capped at 1,500 lines), and slice pull requests regenerate the baseline so each shrink becomes the new ceiling; the gate caught its first growth on its first run. Added the PHPStan level-4 total ratchet (`composer check:phpstan-total`, wired into `composer release:verify`) locking the repository-wide level-4 count at a baseline that moves downward at release time only — 739 at introduction, lowered to 675 with this release. Hardened the per-request changed-files ratchet with a conservation rule: relocated findings in newly added files count against the changed-file total instead of reading as new debt, deleted and renamed source sides release their base counts into the total (`--no-renames`), and newness is keyed on base-revision existence; verified with synthetic growth and rename probes.
- Reduced `includes/Packages/Read_Traits/Media_Read_Methods.php` from 6,612 to 5,190 lines through two behavior-preserving structural slices recorded in the structural split plan: the image-candidate/adoption block (12 methods, 929 lines) moved byte-for-byte into the new `Media_Candidate_Adoption_Read_Methods` trait, and the reference-repair helper block (17 private methods, 491 lines) moved byte-for-byte into the new `Media_Reference_Repair_Read_Methods` trait; both carry reconstruction and pure-deletion purity proofs plus moved-method ownership assertions in the test suite, and the class composition in `Core_Read_Package` is unchanged, so ability ids, schemas, annotations, and callback behavior are identical.
- Removed 62 dead null-coalescing expressions flagged by PHPStan level 4 (54 in media reads, 8 in the diagnostics comparators) where the left side is provably non-nullable — stub-typed `WP_Post`/`WP_Term` properties and in-file array shapes — each line runtime-identical. Deliberately kept the equivalent fallbacks on filterable surfaces (`wp_get_attachment_metadata()` sizes, cron option reads), on `wp_upload_dir()`/theme-mod guards, and on every read fed by the unit harness's partial fixture objects, where the `??` defaults are live guards; the boundary is documented in the pull requests.
- Made the required PR body contract check skip pull requests authored by `dependabot[bot]` (keyed on the PR author, not the event actor, so human edits on a Dependabot PR still skip), unblocking maintenance bumps that machine-generated bodies can never satisfy; merged the `actions/github-script` v9 and `alibaba/open-code-review` v1.12.11 bumps with the pin comments recording the verified tag-to-commit pairs.
- Moved 27 dated one-time records (closeouts, release verifications, post-publication notes, status snapshots) into `docs/archive/`, slimmed the documentation index to evergreen entries plus a short still-cited list, and set the documentation cadence: closeout records are written per milestone rather than per pull request, new dated records go directly into `archive/`, and process/CI work is budgeted (batched into post-release maintenance windows, otherwise roughly one process pull request per three product pull requests).
- Renamed the listing-assets directory `sj/` to `marketing/` (pure renames; the WordPress.org release asset path default, `.gitattributes` export-ignore, and every reference updated), reversing the earlier optional-churn decline as a recorded maintainer decision, and added `composer closeout:check` verifying the documented post-merge steady state (clean `master` level with origin, no merged local topic branches, no stale remote topic branches) so cleanup no longer depends on memory.
- Added the `composer test:core` iteration tier (about 25 seconds: composer metadata, boundary, contracts, response shapes, the structure ratchet, the full behavioral suite, and lint) and documented the three-tier gate ladder (iteration / pre-PR / release) in `AGENTS.md` and the solo workflow guide.
- Folded in the previously unreleased quality work: the per-request PHPStan level-4 changed-files ratchet, the `wp_get_ability()` array/object diagnostic fix from ADR 0008 with duplicate-registration observability events, and the split of the `tests/run.php` suite into ordered part files with the identical aggregate assertion count.

## 0.5.7 - 2026-10-03

- Added `composer check:phpstan-ratchet` (`scripts/check-phpstan-ratchet.sh`) and wired it into the PHP CI job: for every analyzed PHP file a pull request touches, the PHPStan level-4 error count must not exceed the base revision's count, computed by re-analyzing the touched paths in a detached worktree of the base commit. Existing level-4 debt never blocks a change and the gate skips cleanly when no analyzed file changed or the base cannot be prepared, so the 726-finding level-4 backlog cannot grow; its first catch was the `wp_get_ability()` array/object dead-code bug fixed below.
- Corrected the WordPress-side duplicate diagnostic from ADR 0008: `wp_get_ability()` returns a `WP_Ability` object, not an array, so the previously shipped `is_array()` comparison could never run against real WordPress. The diagnostic now compares the fields the object actually exposes (label, description, meta) and marks the event `contract_comparison: partial`, keeps the `warn`/`unavailable` path for unreadable abilities, and the unit harness stubs `WP_Ability` with the same core-faithful getter surface so the dead-code class of bug cannot recur silently. Found by a level-4 PHPStan run on the changed file.
- Decided issue #116 through ADR 0008 and implemented its duplicate-registration half: re-registering an existing ability id in the same registrar (last-writer-wins overwrite) and registrations skipped because WordPress already owns the id now emit `abilities.registration.duplicate` observability events recording the surface (`toolkit_registry` / `wordpress_registry`) and whether the contract-bearing fields changed (status `error` flags the silent-contract-swap risk); the seven-package default boot map stays default-enabled — including `core_write` and `core_destructive` — now as an explicit product decision resting on dry-run defaults, the host approval contract, and the per-site package filter, with tests covering duplicate diagnostics and package defaults.
- Split the `tests/run.php` regression suite (9,937 lines, the repository's largest PHP file) into an ordered aggregator over eleven `tests/run/` part files cut along the original execution order, recorded as the completed test-suite slice of the structural split plan; the suite keeps its single `composer test` entrypoint and aggregates the identical 16,657 assertions, with the load-bearing include order documented in the aggregator header.

- Kept the packaged plugin Plugin Check error-free: the uninstall backup-tree removal (added with the user-experience hardening batch) now deletes files and symlinks only through `wp_delete_file()` — skipping deletion when the WordPress file API is absent instead of falling back to raw `unlink()` — and its one remaining `rmdir()` carries an explicit checker annotation because WordPress ships no directory-removal API and the manual recursion is the symlink-safe implementation under test; the uninstall harness now stubs `wp_delete_file` with core-faithful void semantics.
- Backfilled the bundled starter locales to full template parity: de_DE, es_ES, fr_FR, ja, ko_KR, and pt_BR each gained the ~476 strings added since 0.5.4 (the two-audience admin rework, the workflow scenario view, and the user-experience hardening notices), zh_CN gained the 24 newest strings, and the POT template was regenerated against the 0.5.7 headers so every bundled locale is complete again; the six older locale files also gained their missing `Plural-Forms` headers and the untranslated plugin-name convention.
- User-experience hardening batch 1: direct post-meta writes (`set-post-seo-meta`, `adopt-article-audio`) now invalidate the bounded read cache (skipping `_edit_lock`/`_edit_last` churn) instead of serving stale reports for up to ten minutes; dismissible admin health notices fail loud when Abilities API registration functions are missing or the ability catalog is empty; uninstall removes the media replacement history meta and the `uploads/npcink-abilities-toolkit-backups` tree behind `npcink_abilities_toolkit_uninstall_preserve_media_backups` and `npcink_abilities_toolkit_uninstall_preserve_media_history` host filters (documented in the readme FAQ); the recursive delete never traverses symlinks; the admin overview gains a read-only Ability packages section with a visible write-safeguard note.
- User-experience hardening batch 2: the runtime contract reports `ability_catalog_available` from the live `/wp-abilities/v1/abilities` route instead of a hardcoded true; the contract endpoint serves a quoted sha256 `ETag` with filterable `Cache-Control` (default `private, no-cache`, so every poll revalidates and a matching `If-None-Match` returns an empty 304); the response-shape convergence decision (existing families frozen, new read abilities register `success_envelope` with the shared `page`/`per_page` contract) is recorded in `docs/ability-response-shapes.md`.
- User-experience hardening batch 3: readme.txt gains an Installation section, drops the contributor-only Developer Verification section, points at the canonical `npcink` repository URL, and describes the current Overview + Developer Tools screenshots with an SVN refresh reminder; the README built-in ability list becomes a summary pointing at `docs/first-party-ability-packs.md` as the canonical id list; admin hover-only titles became visible text, status tiles show their detail line, the workflow scenario view renders an explicit empty state, copy buttons restore their label after two seconds with an explicit copy-failure fallback, request buttons disable while running with prefixed request-failure messages, the page title registers translated, and the AJAX check verifies the nonce before the capability check.
- Added `scripts/check-wporg-svn-freshness.sh` and wired it into `composer release:prepare-wporg`: release preparation now fails closed when the local WordPress.org SVN working copy is out of date against the remote, instead of staging a release onto a stale base whose phantom scheduled adds break the credential commit. The check runs non-interactively under a stable locale, reports the real `svn` failure output, and offers an explicit `ALLOW_SKIP_SVN_FRESHNESS=1` maintainer escape hatch for outage-time preparation. Covered by the new `composer test:wporg-svn-freshness-guard` gate, which runs in `composer test:all`.

## 0.5.6 - 2026-10-02

- Escaped the admin workflow scenario-count output and excluded local agent-tool dotfiles from release packaging, keeping the packaged Plugin Check error-free.
- Extracted the cloud derivative adoption and compare-and-swap transaction machinery into `Cloud_Media_Write_Methods` as the second evidence-triggered write-side slice (crossed by at least five merged PRs since the pause); `Core_Write_Package.php` shrinks from 8,220 to 6,588 lines with no ability id, schema, callback, dry-run, or authorization change.
- Fixed two write paths that treated void-returning `wp_delete_file()` as a boolean: `rename-media-file` commits now verify the delete with `is_file()` instead of always failing and discarding the copied target, and expired-backup cleanup records expiry on the run that removes the file instead of lagging a cycle. The unit-test `wp_delete_file` stub now returns void like WordPress core, so both paths are tested under core-faithful semantics.
- Extracted the media backup lifecycle (retention cleanup, restore transactions, replacement lineage) into `Media_Backup_Write_Methods` as the evidence-triggered write-side slice recorded in the structural split plan; `Core_Write_Package.php` shrinks from 9,263 to 8,220 lines with no ability id, schema, callback, dry-run, or authorization change.
- Restructured the admin page around two audience tabs: an Overview that answers site owners in one screen (large stat tiles, a plain-language capability summary as category chips, four unique task buttons including a count-carrying scenario entry, boundary messaging folded into the header) and a Developer Tools tab with task-based sub navigation (Connection, Ability Catalog, Checks, Workflow scenarios; raw output areas render on demand); legacy tab URLs alias to their sub-section.
- Fixed the admin asset cache: admin.css/admin.js now version by file modification time so styling changes between releases are no longer pinned to stale browser caches.
- Added five machine-readable workflow recipes: article-production, media-seo-handoff, media-governance-scan, site-operations-scan, and diagnostics-triage (7 -> 12 cases), each with replay-fixture locks and real-site smoke coverage.
- Documented the host-agnostic Host Approval Contract for governing write and destructive commits, with a fail-closed third-party host example and a dedicated `check:host-approval` gate; the contract is referenced from the platform index and the Adapter/Core contract docs.
- Added a read-only workflow scenario overview and integration documentation links to the admin page, including standalone-install next-step guidance.
- Added contract gates: ability response shape registration (`check:shapes`, 126 read abilities), scope semantics with an explicit capability-gated list, and locale-independent standalone admin smoke assertions.
- Completed the `get-block-theme-context` output schema declaration to match its twelve-key runtime response.
- Reordered the root README and documentation entry point to lead with user value; added the three-persona walkthrough standard and closeout records for future sessions.
- Backfilled the zh_CN locale to full parity (1,052 translated strings) after regenerating the stale POT template.

## 0.5.5 - 2026-09-05

- Isolated the packaged Plugin Check regression test from inherited `WP_CLI_PHP`, error-reporting, and database socket settings so the cross-repository release gate remains deterministic.

## 0.5.4 - 2026-09-04

- Added bounded media backup retention cleanup with an explicit manual-confirmation exception for exact-manifest batches.
- Aligned cleanup scheduling with the enabled package profile and cleared the maintenance cron during uninstall.
- Hardened internal-link candidates to require semantic body evidence and preserve a visible editor handoff.
- Added media fingerprint and replacement-lineage evidence to governed media operations.

## 0.5.3 - 2026-07-10

- Corrected the minimum WordPress version to 6.9, where the Abilities API was
  introduced, while keeping WordPress 7.0 as the current tested-up-to line.
- Replaced the ability-count floor in the official-stack gate with explicit
  representative contracts so quality checks do not reward catalog growth.
- Recorded Toolkit as the canonical owner of reusable static workflow
  definitions while keeping execution, governance, and runtime state in hosts.
- Made built-in write and destructive callbacks fail safe when `commit=false`
  or `dry_run=true`, including conflicting control values.
- Restricted post-meta reads to explicit non-sensitive keys and removed
  unscoped fallbacks that exposed every stored post-meta value.
- Required hosts to explicitly allowlist non-sensitive targets before
  `patch-setting-value` can preview or commit a change, and replaced reversible
  setting fragments with bounded hashes and lengths.
- Validated remote media in a temporary file before copying it into uploads,
  and removed the obsolete in-memory upload fallback.
- Added namespace autoloading, lazy package construction, and a cold-bootstrap
  performance budget to reduce unconditional plugin load cost.
- Updated ability, security, schema, performance, testing, and local WP-CLI
  documentation for the hardened contracts and current verification baseline.
- Removed the incomplete bundled Traditional Chinese (`zh_TW`) starter locale
  pack from the source tree until it can be maintained as a complete
  translation set.
- Documented that bundled starter locales are repository-maintained runtime
  files, while WordPress.org directory translations remain managed separately
  through translate.wordpress.org/GlotPress.

## 0.5.2 - 2026-06-22

- Reworked the wp-admin surface around ordinary site operators: renamed the
  entry point to Site AI Abilities, added overview, available abilities, checks,
  and developer access tabs, and kept developer details available without making
  them the default experience.
- Added user-facing safe check summaries with purpose tables and collapsed raw
  JSON so site owners can understand what the checks prove without reading API
  payloads first.
- Refreshed the Chinese admin translation catalog for the updated settings page,
  check result summaries, plugin action links, and WordPress.org listing copy.
- Prepared WordPress.org listing content, FAQ, screenshots, banner/icon notes,
  and release assets for the operator-focused positioning.
- Added read-only review artifacts for image candidates, internal link
  candidates, taxonomy suggestions, and comment reply suggestions while keeping
  final writes host-governed.
- Published WordPress.org SVN revision 3581125 after `composer release:verify`
  completed with Plugin Check reporting no errors.

## 0.5.1 - 2026-06-07

- Added Composer dependency advisory audit to the default `composer test:all`
  source gate.
- Aligned GitHub Actions and PHPStan with the package PHP 8.0 runtime floor.
- Published the canonical public GitHub repository and documented the
  post-publication gate baseline.
- Upgraded GitHub Actions checkout to the Node 24-compatible
  `actions/checkout@v5`.

## 0.5.0 - 2026-06-06

- Improved the admin page as a connection and discovery surface with clearer package status, catalog navigation, copyable REST endpoints, and two bounded read-only checks.
- Added bundled translation templates and eight starter locale packs for the admin connection/discovery surface, API ability labels/descriptions, and common runtime error messages.
- Removed development demo ability controls from the admin page and kept showcase, model-call, write, and workflow execution outside this package surface.
- Renamed nonproduction cleanup abilities and media cleanup inputs to avoid public `test` terminology in released ability ids and schema fields.
- Updated `npcink-abilities-toolkit/adopt-cloud-media-derivative` and `npcink-abilities-toolkit/replace-media-file` to preview and commit exact post-content media URL repairs, including old intermediate-size image URLs, when an approved replacement switches an attachment to a new local file.
- Added `npcink-abilities-toolkit/propose-post-taxonomy-terms`, a deterministic taxonomy assignment proposal helper that targets `npcink-abilities-toolkit/set-post-terms` without mutating posts or creating terms.
- Added a 0.5 verification note that records the taxonomy terms Core consumer proof and keeps the package in freeze/observe mode until a concrete workflow gap appears.
- Added a Npcink AI main repo harvest checkpoint that maps content/media/comment/batch cleanup signals to existing abilities and confirms that no new ability batch should start from candidate lists alone.
- Expanded the Core consumer handoff check to assert the five harvested surfaces and their corresponding host-governed write targets expose Abilities API discovery metadata, schemas, dry-run controls, and approval metadata.
- Documented the foundation-layer testing strategy and clarified that `composer test:all` is the default local source gate for contract, governance, performance, and lightweight regression coverage.

## 0.4.0 - 2026-05-30

- Added a Core governance catalog snapshot fixture covering draft creation, SEO metadata, comment approval, and workflow definition discovery contracts.
- Added Core consumer handoff and catalog audit checks for release-candidate governance validation.
- Added permission matrix and schema boundary audit documentation for first-party write/destructive abilities.
- Added a Core governance consumer example that discovers ability contracts and prepares a proposal payload without moving Core governance into this package.
- Hardened write-like contract metadata with `requires_approval`, dry-run and commit defaults, and bounded idempotency keys.
- Expanded WordPress smoke coverage to assert REST-exposed governance metadata and schemas for Core handoff abilities.
- Tightened `set-post-seo-meta` so omitted metadata fields do not overwrite existing values, and added permission coverage for comment approval dry-runs.
- Added read-only workflow definition discovery through PHP helpers and Abilities API abilities without introducing workflow runtime ownership.
- Added host profile guidance for full governed hosts and light core-read integrations.
- Added workflow definition contract tests that keep the replay fixture aligned with the production provider and reject runtime/governance fields.
- Split the standalone WordPress diagnostics read ability definition into a dedicated read definitions provider while preserving registration order and behavior.
- Split built-in comment helper ability definitions into a dedicated comment definitions provider while preserving callback ownership, sub-pack classification, and registration behavior.
- Split generic `core_wordpress_read` ability definitions into a dedicated read definitions provider while preserving callback ownership, sub-pack classification, and registration order.

## 0.3.0 - 2026-05-29

- Added the 0.3 ability acceptance matrix, agent workflow validation plan, and stabilization scope documents.
- Added package and sub-pack filters for host-selectable built-in package composition while preserving full default registration.
- Added explicit read/comment sub-pack maps as the stable entry point for future definition-provider extraction.
- Kept public third-party helpers read-only/write-proposal oriented and documented that final commit authorization belongs to host runtimes.
- Made Npcink AI catalog projection thin by default and added a single projected-row filter for host-owned catalog expansion.
- Established `npcink-abilities-toolkit/*` as the canonical id namespace for abilities owned by this plugin.
- Verified Npcink AI local Catalog compatibility: authenticated catalog page returned HTTP 200, the capabilities endpoint returned 198 entries, and projected rows omitted host-owned runtime policy fields.
- Added workflow recipe guidance that documents host-side ability composition without moving workflow runtime ownership into this package.
- Added read-only workflow context bundles for article publish preflight, old article refresh discovery, and comment compliance handoff.
- Added short-TTL read caching for selected bounded reports and a `composer perf:smoke` performance smoke command.
- Expanded WordPress smoke coverage from individual ability execution to the publish preflight, content refresh discovery, and comment compliance workflow chains.
- Added `npcink-abilities-toolkit/get-post-context`, a read-only post context bundle for agent workflows.
- Added `npcink-abilities-toolkit/get-content-publishing-checklist`, a read-only pre-publish readiness checklist.
- Added `npcink-abilities-toolkit/get-content-inventory-health`, a bounded read-only inventory health scan.
- Added `npcink-abilities-toolkit/get-bulk-publishing-checklist`, a batched wrapper around the publishing checklist.
- Added `npcink-abilities-toolkit/get-internal-link-opportunity-report`, a post-id based internal link opportunity report.
- Added `npcink-abilities-toolkit/get-media-inventory-health`, a bounded read-only media metadata health scan.
- Added `npcink-abilities-toolkit/get-post-seo-geo-readiness`, a deterministic single-post SEO/GEO readiness snapshot.
- Added `npcink-abilities-toolkit/get-site-topic-coverage-report`, a bounded topic coverage summary for site content.
- Added `npcink-abilities-toolkit/get-taxonomy-inventory-health`, a bounded read-only taxonomy term governance scan.
- Added `npcink-abilities-toolkit/get-revision-change-risk-report`, a read-only revision change-risk summary for pre-write review.
- Added `npcink-abilities-toolkit/get-comment-queue-health`, a read-only moderation queue health summary.
- Added `npcink-abilities-toolkit/get-site-operations-dashboard`, a read-only site operations summary for Agent triage.
- Added `npcink-abilities-toolkit/get-post-publish-risk-report`, a read-only per-post publish risk report.
- Added `npcink-abilities-toolkit/get-content-refresh-opportunities`, a read-only refresh opportunity scanner.
- Added `npcink-abilities-toolkit/get-internal-link-graph-health`, a bounded internal-link graph health report.
- Added `npcink-abilities-toolkit/get-media-cleanup-opportunities`, a read-only media cleanup opportunity scanner.
- Added `npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions`, a read-only taxonomy consolidation suggestion report.
- Added `npcink-abilities-toolkit/get-comment-action-priority-queue`, a prioritized read-only comment handoff queue.
- Added `npcink-abilities-toolkit/get-page-structure-health`, a read-only page structure health report.
- Added `npcink-abilities-toolkit/get-seo-geo-gap-report`, a read-only SEO/GEO gap report.
- Added `npcink-abilities-toolkit/get-site-style-baseline`, a read-only site style baseline wrapper.
- Added `npcink-abilities-toolkit/build-article-workflow-context`, a read-only article workflow context bundle.
- Added `npcink-abilities-toolkit/get-publishing-calendar-context`, a read-only publishing calendar context report.
- Updated first-party pack documentation, migration inventory, smoke coverage, and lightweight tests for the new abilities.

## 0.2.0 - 2026-05-28

- Stabilized the public helper contract and documented parameters, defaults, failure modes, and examples.
- Documented the ability contract for ids, categories, schemas, annotations, risk levels, scopes, Npcink AI metadata, MCP metadata, deprecation, and successor fields.
- Added host-governed write/destructive semantics for `dry_run`, `commit`, `idempotency_key`, `requires_confirm`, `preview`, and `commit_required`.
- Kept third-party public registration limited to category, readonly, write-proposal, schema/annotation normalization, and registered ability inspection helpers.
- Added first-party ability pack grouping for content context, publishing, comment compliance, diagnostics, and SEO/GEO support.
- Added Npcink AI consumer verification evidence, including duplicate-id audit expectations and `wp_ability` projection checks.
- Expanded lightweight tests for write controls, output schema controls, invalid ability ids, provider projection defaults, and Npcink catalog projection behavior.
- Verified Local WP smoke coverage and recorded 0.2 candidate evidence.

## 0.1.0 - 2026-05-28

- Established Npcink Abilities Toolkit as a standalone WordPress Abilities API capability-package plugin.
- Added public helpers for category registration, readonly abilities, write-proposal abilities, schema normalization, annotation normalization, and registered ability inspection.
- Added the migrated WordPress read-only package: `site-info`, `list-post-types`, `list-taxonomies`, `count-posts`, `list-pages-tree`, `list-posts`, `get-post`, `resolve-url-to-post`, `get-post-blocks`, `list-post-revisions`, `list-media`, `list-terms`, `list-taxonomy-terms`, `list-categories`, `list-tags`, `get-term`, `propose-post-excerpt`, `list-users`, `list-comments`, `list-menus`, `get-menu`, `search-posts`, `get-post-stats`, `list-revisions`, `get-post-meta`, `list-pages`, `get-page`, and `inspect-page-structure`.
- Added the migrated deterministic comment helper package: `build-comment-moderation-suggest`, `compose-comment-moderation-result`, `build-comment-mention-reply-suggest`, `read-comment-trigger-queue`, `compose-comment-mention-reply-result`, `build-comment-moderation-batch-suggest`, and `compose-comment-moderation-batch-result`.
- Added migrated host-governed WordPress write and destructive packages with dry-run defaults and approval-context commit gating.
- Added standalone redacted WordPress diagnostics ability: `npcink-abilities-toolkit/wp-diagnostics-summary`.
- Added a wp-admin test page under Tools -> Abilities API Packages.
- Added environment checks for Abilities API functions, REST routes, REST nonce usage, and Magick App Key non-usage.
- Added optional compatibility projection into the Npcink AI catalog for provider abilities.
- Added integration rules for optional Npcink AI consumption and a 0.1 public API freeze document.
- Added a WP-CLI smoke test for real WordPress environments.
- Added lightweight regression tests and PHP syntax linting.
