# Ability Response Shapes

Status: active contract guidance.
Date: 2026-09-29. Convergence decision added 2026-10-02.

This document defines the response shape families for read-risk abilities and
the registration gate that keeps them from drifting silently. External clients
should read this before parsing ability responses; hosts and provider plugins
must register any new shape in the fixture described below.

## Shape Families

Every read ability's response belongs to exactly one machine-recognized
family, derived from its declared `output_schema` top-level properties:

| Family | Marker | Shape | Examples |
| --- | --- | --- | --- |
| `success_envelope` | declares `success` | `{ success, data, meta, message }` with the payload inside `data` | most report, suggestion, and plan abilities |
| `paginated_collection` | declares `total` and `items` | raw paginated rows with no envelope | `list-comments`, `list-menus`, `list-terms` |
| `raw` | neither marker | a single resource object, a redacted report, or a manifest | `get-post`, `wp-ops-diagnostics-detail`, `list-workflow-recipes` |

The `raw` family intentionally groups several sub-conventions that predate the
envelope pattern: single-resource objects (`get-post`, `get-term`), redacted
diagnostics reports (`wp-diagnostics-summary`, `wp-ops-diagnostics-detail`),
the workflow manifest (`list-workflow-recipes`), and page-listing variants
that paginate with `pages`/`has_more` instead of `items` (`list-pages`).

**Unifying these families is a future contract decision, not a maintenance
edit.** Live consumers across repositories already parse every family, so any
unification must go through a contract review with consumer migration notes.

## Convergence Decision (2026-10-02)

The shape review on 2026-10-02 recorded the following decision instead of
changing live contracts in place:

1. **Existing families are frozen.** No currently registered ability changes
   family, marker keys, or pagination field names. Consumers keep a single
   parser per family and no migration is required.
2. **New read abilities register in `success_envelope` only.** A new read
   ability must declare `success` plus `data`/`meta` so its derived family is
   `success_envelope`; registrations for new ids in `raw` or
   `paginated_collection` need an explicit contract-review note in the PR.
3. **New list abilities use the shared pagination contract.** Input:
   `page` (1-based) and `per_page` (default 20, maximum 100). Response:
   rows under `data.items`, totals under `data.total`, and the echoed
   `page`/`per_page` under `meta`. The pre-existing mixed defaults
   (10/20/50/100) and caps (50/100) stay as-is for existing abilities.
4. **A later major contract version may migrate existing abilities** to the
   unified envelope with compatibility aliases and consumer migration notes;
   that migration is out of scope for maintenance edits.

The registration gate below keeps enforcing all three live families while
this convergence path runs; nothing in this section weakens the fixture.

## Registration Gate

Run:

```bash
composer check:shapes
```

The gate enforces, for every registered read-risk ability:

1. an entry exists in
   [tests/fixtures/ability-response-shapes.json](../tests/fixtures/ability-response-shapes.json);
2. the entry's `keys` equal the declared `output_schema` top-level property
   names (either side drifting fails);
3. the entry's `family` matches the derived family from the declared keys;
4. entries marked `runtime_verified: true` are executed with an empty input
   and their actual top-level keys must still equal the registered keys;
5. no stale entries remain for abilities that no longer exist.

Adding a read ability, changing an `output_schema`, or changing a callback's
top-level response keys requires updating the fixture in the same change.
Entries are marked `runtime_verified: true` when the ability runs with an
empty input and its runtime keys match the declaration; abilities that need
required inputs or richer WordPress stubs stay `runtime_verified: false`
until a verification input is added to the generator.

## Notable Registrations

- `get-block-theme-context` previously declared an empty `output_schema`
  while returning twelve top-level keys; the declaration was completed when
  this gate was introduced, which is exactly the class of drift the fixture
  now prevents.
