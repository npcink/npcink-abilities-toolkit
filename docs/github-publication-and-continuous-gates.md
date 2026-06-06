# GitHub Publication And Continuous Gates

Status: active operations note.
Date: 2026-06-07

This note records the public GitHub handoff and the current continuous
performance and security gates for `npcink-abilities-toolkit`.

## Repository State

The canonical public source repository is:

```text
https://github.com/muze-page/npcink-abilities-toolkit
```

GitHub repository state verified on 2026-06-07:

- owner/name: `muze-page/npcink-abilities-toolkit`;
- visibility: public;
- default branch: `master`;
- published branch: `master`;
- published tags: `0.2.0`, `0.4.0`, `0.5.0`.

The local checkout now uses GitHub as `origin`:

```text
origin  https://github.com/muze-page/npcink-abilities-toolkit.git
```

The previous Gitee remote, `git@gitee.com:gitgreat/npcink-abilities-toolkit.git`,
was removed from the local checkout to avoid accidental pushes to the old host.
If another local checkout still points to Gitee, rename or remove that remote
before release work.

## Continuous Gate Baseline

The default source gate is:

```bash
composer test:all
```

It now includes:

- Composer metadata validation;
- Composer dependency advisory audit from `composer audit --locked`;
- project boundary checks;
- ability contract readiness;
- consumer and workflow handoff checks;
- official WordPress AI stack compatibility checks;
- MCP exposure audit;
- provider demo smoke;
- ability catalog audit;
- WordPress.org review guard;
- bounded performance smoke;
- lightweight regression tests;
- PHP syntax linting.

The CI matrix now matches the package runtime floor:

- PHP `8.0`;
- PHP `8.3`.

PHPStan also analyzes against PHP `8.0`, matching `composer.json`'s
`php >=8.0` requirement.

## Verified Commands

The following local checks passed after adding the dependency audit gate and
aligning PHP version targets:

```bash
composer validate --no-check-publish
composer audit:composer
composer test:all
composer analyse:phpstan
```

The publication baseline `master` GitHub Actions run for commit
`2c6d288 Add dependency audit to default gate` completed successfully.

## Known Historical CI Signal

The pushed historical tag `0.5.0` has a failed GitHub Actions run because that
tag's workflow still runs PHP `7.2` while the package already requires
`php >=8.0`.

Do not move the published `0.5.0` tag only to make that historical CI green.
Use the current `master` baseline for the next patch release instead.

## Release Follow-Up

Before the next public patch release:

1. Run the default source gate:

```bash
composer test:all
composer analyse:phpstan
```

2. Run real-site WordPress smoke when a site is available:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

3. Record the smoke result in the next release verification note.
4. Tag a new patch release, for example `0.5.1`, from the verified `master`
   commit instead of retagging `0.5.0`.
