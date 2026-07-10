# 0.5.3 Release Verification

Status: candidate.
Date: 2026-07-10.

This note records the `0.5.3` security-hardening candidate. The release keeps
Toolkit as the WordPress ability-contract owner while making write controls,
post-meta reads, settings patches, remote media intake, and plugin bootstrap
safer by default.

## Scope

- Require explicit commit intent for write and destructive callbacks; fail
  safe when write controls are absent or conflict.
- Require explicit non-sensitive post-meta keys and prevent context readers
  from falling back to every stored value.
- Require a host-provided setting target allowlist, permanently reject
  credential-like targets, and avoid returning reversible setting fragments.
- Validate downloaded media before it enters uploads and remove the obsolete
  in-memory upload path.
- Replace unconditional implementation includes with internal autoloading and
  lazy package construction, protected by a cold-bootstrap performance budget.
- Keep approval truth, audit truth, final authorization, runtime state, model
  routing, and consumer product policy outside Toolkit.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | 163 ability contracts, 6 workflow recipes, 6242 assertions, boundary checks, WordPress.org guards, performance budgets, and PHP syntax lint passed. |
| `composer analyse:phpstan` | Pass | PHPStan completed successfully with the package's PHP 8.0 baseline. |
| Full WordPress smoke | Pass | The Local.app site completed 435 authenticated contract and behavior assertions. |
| Light-profile WordPress smoke | Pass | The Local.app site completed 56 package-profile assertions. |
| Plugin package check | Pass | The packaged WordPress.org surface completed Plugin Check without a blocking error. |
| Adapter consumer compatibility | Pending | Adapter must distinguish execution-profile support from site-specific setting-target readiness before release. |
| Central cross-repo quality matrix | Pending | Run after the Adapter compatibility change is verified. |
| WordPress.org publication | Pending | Publish only from the verified `master` release commit. |

## Release Decision

Do not publish `0.5.3` until the Adapter compatibility proof and central
cross-repository matrix pass. The Toolkit security defaults are intentional;
consumers must not weaken them or hardcode site-specific setting names into a
channel adapter.

## Post-Release Observation

Use existing host diagnostics, test output, and quality matrices to observe:

- rejected unallowlisted setting targets;
- legacy post-meta calls that omit an explicit key;
- PHP 8.0 and PHP 8.3 CI stability;
- cold-bootstrap and bounded runtime performance budgets.

Do not add Toolkit-owned telemetry, approval records, audit storage, queues, or
runtime state for this observation period.
