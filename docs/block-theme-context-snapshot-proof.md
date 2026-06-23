# Block Theme Context Snapshot Proof

Status: 0.5.x proof-hardening candidate.
Date: 2026-06-15.

This note records the scope for the block theme site context snapshot work. It
is intended to support host workflow proof for bounded block theme layout
planning without changing this package into a layout runtime, template writer,
or approval system.

## Decision

Continue freeze/observe mode. The block theme snapshot work is allowed only as
contract hardening for existing abilities:

- `npcink-abilities-toolkit/get-block-theme-context`;
- `npcink-abilities-toolkit/build-block-theme-site-plan`.

It must not add a new first-party ability id, a workflow runtime, a template
write executor, approval storage, audit state, model routing, prompt ownership,
or final WordPress mutation policy.

## Proof Target

The host proof target is a lightweight block theme intent routing replay:

1. A host receives a natural-language request for homepage, blog, article, or
   page template layout changes.
2. The host resolves the intended route and discovers the existing abilities
   through the WordPress Abilities API.
3. `get-block-theme-context` returns factual site context: front-page reading
   settings, template resolution, bounded content inventory, CTA candidates, and
   existing override hashes.
4. `build-block-theme-site-plan` returns a reviewable preview or a fail-closed
   result.
5. Write actions remain proposal targets for the host. Final template updates
   require host approval and write execution outside this package.

## Contract Hardening

The snapshot may expose factual, bounded WordPress context:

- `reading_settings` for `show_on_front`, `page_on_front`, and
  `page_for_posts`;
- `template_resolution` for `front-page`, `home`, and `index`;
- `content_inventory` with bounded counts and candidate CTA pages;
- `existing_overrides` with template ids and content hashes;
- preview metadata such as `template_resolver`, `cta_resolution`, and
  `page_content_enabled`.

Homepage layout planning must fail closed when a CTA cannot resolve to a
caller-supplied URL or a trusted existing page. In that case the plan should
return `cta_link_unresolved`, mark the preview as not proposal-ready, and emit
no write action.

## Boundary

Allowed in this package:

- read block theme, template, and content context;
- explain deterministic template resolution;
- build bounded proposal previews;
- validate block editor quality gates;
- return risk, warning, and fail-closed metadata.

Not allowed in this package:

- choose final site strategy for a product host;
- run template writes directly from natural-language intent;
- publish, approve, retry, schedule, or audit template changes;
- maintain layout workflow state;
- patch `theme.json`, global styles, navigation, raw template HTML, custom CSS,
  non-core blocks, or arbitrary unprofiled template composition.

## Failure Classification

Only the last two failure classes can justify future toolkit code:

| Failure class | Owner | Toolkit ability change? |
| --- | --- | --- |
| Host cannot choose when to call a workflow | Host or adapter | No |
| Prompt, router, or model selection fails | Host or AI runtime | No |
| Approval, audit, or preflight is missing | Core or governance host | No |
| Provider, quota, billing, or cloud execution fails | Cloud or runtime | No |
| WordPress-local block theme context is missing | Toolkit | Maybe |
| Proposal or fail-closed schema cannot be verified | Toolkit | Maybe |

## Version Guidance

This work fits a possible `0.5.2` maintenance release if it remains limited to
documentation, replay fixtures, contract snapshots, and existing-ability
hardening.

Do not treat it as `0.6.0` unless a real host proof fails and identifies one to
three small, reusable, WordPress-only contract gaps that cannot be solved by
host routing, prompt design, approval flow, or adapter behavior.

## Verification

Before merging this proof-hardening branch, run:

```bash
composer test:all
composer analyse:phpstan
git diff --check
```

When a real WordPress site is available, add:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

Record any failure as host-owned, toolkit-owned, or deferred before creating a
new ability proposal.

## PR 44 Merge Record

PR: [#44 Harden block theme context proof contracts](https://github.com/muze-page/npcink-abilities-toolkit/pull/44)
Merge commit: `3ac2fbc626a6a3e67fce7bcf337399280d046302`
Merged: 2026-06-15.

The merged branch kept the project in freeze/observe mode while hardening
existing contracts. It did not add a new ability id.

The accepted changes were split into four reviewable commits:

- `e3a02d7 Add block theme site context snapshot`;
- `348a799 Document block theme context proof boundary`;
- `10821fe Handle guardrailed block theme intent prompts`;
- `efee46b Expose media derivative batch eligibility details`.

### What Changed

The block theme snapshot work added factual homepage and template context to
the existing block theme read/planning surface:

- reading settings for posts-front and static-front configurations;
- template resolution for `front-page`, `home`, and `index`;
- bounded content inventory and CTA candidate pages;
- existing template override hashes;
- homepage CTA resolution and `cta_link_unresolved` fail-closed behavior;
- homepage static-page content-slot decisions.

The guardrailed intent routing work keeps prompts such as "do not write
theme.json" or "不要改 global styles" from being misclassified as unsupported
write requests when those terms are used as negative guardrails. Positive
requests for navigation, global styles, theme files, or raw template HTML still
fail closed.

The media derivative batch work made the existing batch-plan output easier for
hosts to review and retry:

- eligible candidates now carry `status`, `reason`, and `result_ref`;
- skipped rows are also exposed as `blocked_items`;
- the output schema declares `eligibility_summary`, `blocked_items`,
  `retryable`, `retry_guidance`, and `operator_next_action`;
- the plan remains read-only, dry-run oriented, and host-governed.

### Verification Evidence

Before PR creation and merge, the branch passed:

```bash
composer test:all
composer analyse:phpstan
git diff --check
```

GitHub Actions passed on the PR for:

- `php (8.0)`;
- `php (8.3)`.

The Local.app WordPress smoke was rerun against the documented local site:

```bash
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP=/opt/homebrew/bin/php \
WP_CLI_ERROR_REPORTING=8191 \
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" \
composer smoke:wp
```

Smoke result:

- default profile: `Smoke OK: 323 assertions`;
- light profile: `Smoke OK: 25 assertions`.

### Cleanup Result

After the PR merged:

- local `master` was fast-forwarded to `origin/master`;
- the topic branch was removed remotely;
- no auxiliary worktrees remained;
- the checkout returned to one clean local worktree on `master`.

### Follow-Up Posture

After PR 44, continue freeze/observe mode. Do not open another ability branch
from candidate lists alone.

Useful next proof work should happen from a host perspective:

- run a real `npcink-abilities-toolkit/recipes/article-optimization` consumption proof;
- run a block theme intent replay against a host path that discovers the
  abilities through WordPress Abilities API;
- classify any failures as host-owned, toolkit-owned, or deferred before
  proposing code.

Only create another toolkit PR when a failed host proof identifies a small,
reusable, WordPress-only contract gap that existing abilities cannot satisfy by
composition.
