# REST Client Quickstart

This guide shows the smallest path for a REST client to discover and run
abilities from a WordPress site where `npcink-abilities-toolkit` is active.

## Requirements

- WordPress 7.0+ with the WordPress Abilities API available, or the official
  Abilities API compatibility plugin active.
- PHP 8.0+ on the WordPress site.
- `npcink-abilities-toolkit` installed and active.
- An authenticated WordPress user with the capability required by the ability.

## Find The Endpoints

After activation, open the admin discovery page:

- With a Npcink AI host menu: `Npcink AI -> Abilities`
- Standalone fallback: `Tools -> Abilities API Packages`

The page exposes copyable endpoint values for the current site. The standard
REST routes are:

```text
GET /wp-json/wp-abilities/v1/categories
GET /wp-json/wp-abilities/v1/abilities
GET /wp-json/wp-abilities/v1/abilities/{namespace}/{name}
GET|POST /wp-json/wp-abilities/v1/abilities/{namespace}/{name}/run
GET /wp-json/npcink-abilities-toolkit/v1/contract
```

The ability id uses `namespace/name`; the REST path keeps the slash as path
segments. For example, `npcink-abilities-toolkit/site-info` becomes:

```text
/wp-json/wp-abilities/v1/abilities/npcink-abilities-toolkit/site-info/run
```

The Toolkit contract endpoint is for host/runtime discovery and requires a
REST caller with `manage_options`. It returns metadata such as plugin version,
contract versions, registered ability count, stable ability/workflow hashes,
and the host-governed write boundary. It does not replace the WordPress
Abilities API catalog and does not run abilities.

## Authentication

Use normal WordPress REST authentication. For server-to-server testing, an
Application Password is usually the simplest option:

```bash
curl -sS \
  -u "admin:APPLICATION_PASSWORD" \
  "https://example.test/wp-json/wp-abilities/v1/abilities"
```

For browser-side wp-admin tools, use the logged-in user's REST nonce:

```bash
curl -sS \
  -H "X-WP-Nonce: REST_NONCE" \
  "https://example.test/wp-json/wp-abilities/v1/abilities"
```

Do not expose Application Passwords in browser JavaScript or committed fixtures.

## Discover The Catalog

Fetch the catalog first:

```bash
curl -sS \
  -u "admin:APPLICATION_PASSWORD" \
  "https://example.test/wp-json/wp-abilities/v1/abilities"
```

Before running an ability, inspect:

- `label` and `description`
- `input_schema` and `output_schema`
- `category`
- `risk_level`
- `requires_confirm` or `requires_approval`
- `meta.show_in_rest`
- `meta.mcp.annotations`
- `meta.npcink` only when the host intentionally consumes Npcink projection
  metadata

Clients should not assume every discovered ability is safe to run. Capability
checks still happen at execution time.

## Run A Read Ability

`npcink-abilities-toolkit/site-info` is the smallest read check:

```bash
curl -sS \
  -u "admin:APPLICATION_PASSWORD" \
  "https://example.test/wp-json/wp-abilities/v1/abilities/npcink-abilities-toolkit/site-info/run"
```

For abilities that accept query parameters, pass only fields declared by the
input schema:

```bash
curl -sS \
  -u "admin:APPLICATION_PASSWORD" \
  "https://example.test/wp-json/wp-abilities/v1/abilities/npcink-abilities-toolkit/list-posts/run?post_type=post&per_page=5"
```

## Run A Write-Proposal Or Host-Governed Ability

Write-like abilities are host-governed. They default to dry-run or proposal
behavior and must not be treated as direct commits by generic REST clients.

Example dry-run request:

```bash
curl -sS \
  -u "admin:APPLICATION_PASSWORD" \
  -H "Content-Type: application/json" \
  -X POST \
  -d '{"title":"Draft title","dry_run":true,"commit":false}' \
  "https://example.test/wp-json/wp-abilities/v1/abilities/npcink-abilities-toolkit/create-draft/run"
```

Only a host runtime with its own caller identity, scope checks, approval record,
audit trail, and commit authorization should request final writes. This package
does not issue app keys, store approvals, enforce quotas, or own final host
governance.

## Minimal Client Flow

1. Confirm `/wp-json/` exposes `wp-abilities/v1`.
2. Fetch categories and abilities.
3. Find the target ability id.
4. Check schema, risk, and permission metadata.
5. Run a read ability first, usually `npcink-abilities-toolkit/site-info`.
6. Keep write and destructive abilities in dry-run/proposal mode unless a host
   approval path exists outside this package.
7. On failure, use [Troubleshooting](troubleshooting.md).
