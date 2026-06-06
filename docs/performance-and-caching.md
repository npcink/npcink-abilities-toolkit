# Performance And Caching

Status: active for 0.3 stabilization.

This project improves host efficiency without becoming a workflow runtime. The
performance strategy is to reduce repeated read calls, keep expensive scans
bounded, and cache only read-only data that is safe to recompute.

## Strategy

1. Prefer read-only aggregator abilities for common host workflows.
2. Keep every list or scan bounded by `per_page`, `page`, or explicit limits.
3. Cache only deterministic read-only reports.
4. Include the current user in cache keys when output can depend on capability
   checks.
5. Invalidate by version bump on WordPress content, term, and attachment
   changes instead of deleting individual transient keys.
6. Measure the same bounded ability chains before broadening the ability
   surface.

## Aggregator Abilities

These abilities reduce host round trips while staying read-only:

- `npcink-abilities-toolkit/get-article-publish-preflight-context`
- `npcink-abilities-toolkit/get-old-article-refresh-context`
- `npcink-abilities-toolkit/get-comment-compliance-handoff`
- `npcink-abilities-toolkit/get-site-operations-dashboard`
- `npcink-abilities-toolkit/build-article-workflow-context`

They do not store workflow state, approve writes, schedule work, or commit
WordPress mutations. They only return context bundles that a host can use for
its own approval and orchestration.

## Cache Pilot

The first cache pilot covers:

- `npcink-abilities-toolkit/get-content-inventory-health`
- `npcink-abilities-toolkit/get-seo-geo-gap-report`

The cache uses WordPress transients with a short TTL and a versioned key. The
cache key includes:

- ability cache namespace;
- normalized bounded input;
- current user id;
- `npcink_abilities_toolkit_read_cache_version`.

The version is bumped from plugin hooks for post, term, and attachment changes.
This keeps stale read-only reports bounded without making cache entries a
canonical truth source.

Do not cache:

- write or destructive abilities;
- dry-run previews that include approval-sensitive host context;
- data that depends on secrets, runtime credentials, quota, audit, or model
  routing;
- unbounded scans.

## Performance Smoke

Run the isolated performance smoke with:

```bash
composer perf:smoke
```

The smoke measures representative bounded chains:

- content inventory health;
- cached SEO/GEO gap report;
- article publish preflight context;
- old article refresh context;
- comment compliance handoff.

Budgets are intentionally conservative and local. They catch accidental
explosions in scan cost or aggregator composition, not production latency.

For a real WordPress site, also run:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

Record real-site smoke results in release verification when a Local WP site is
available.

## Next-Stage Gate

Before broadening the ability surface or promoting another workflow recipe:

1. Run `composer perf:smoke`.
2. Confirm the promoted workflow can prefer an existing read-only entrypoint or
   bounded read/proposal chain.
3. Add a new performance smoke target only when the workflow adds a new
   aggregator, cache path, or repeated scan pattern.
4. Do not add cache coverage for write/destructive abilities or
   approval-sensitive previews.
5. Record real-site `composer smoke:wp` evidence in the relevant release
   verification note when a Local WordPress site is available.

The next promoted workflow definitions, `workflow/wordpress_article_optimization`
and `workflow/wordpress_article_media_handoff`, intentionally reuse existing
read/proposal abilities instead of introducing new scan or cache behavior.
