## Scope

- [ ] Ability contract changed
- [ ] WordPress.org release surface changed
- [ ] Read/write/governance boundary checked
- [ ] Documentation or release note updated where needed

## Verification

- [ ] `composer test:all`
- [ ] `composer analyse:phpstan`
- [ ] `git diff --check`
- [ ] `WP_PATH=/path/to/wordpress composer release:verify` if release-facing

## Boundary

- [ ] Ability definitions remain reusable WordPress contracts, not model routing, workflow runtime, approval storage, billing, quota, cloud execution, or MCP gateway policy.
- [ ] Write-like behavior remains dry-run or host-governed where the existing contracts require it.

## Risk

- Residual risk:
- Rollback plan:

## Release Impact

- [ ] No release needed
- [ ] Needs `CHANGELOG.md`
- [ ] Needs `readme.txt` / plugin header version update
- [ ] Needs `docs/release-X.Y.Z-verification.md`

## Notes

Summarize the behavior change, boundary decision, and any known follow-up.
