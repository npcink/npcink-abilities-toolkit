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

## Release Impact

- [ ] No release needed
- [ ] Needs `CHANGELOG.md`
- [ ] Needs `readme.txt` / plugin header version update
- [ ] Needs `docs/release-X.Y.Z-verification.md`

## Notes

Summarize the behavior change, boundary decision, and any known follow-up.
